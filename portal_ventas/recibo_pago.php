<?php
session_start();
require_once '../conexion.php';
require_once __DIR__ . '/portal_documento_auth.php';

$id_cotizacion = isset($_GET['id']) ? intval($_GET['id']) : 0;
$abono_get = isset($_GET['abono']) ? trim($_GET['abono']) : 'total';
$esReciboTotal = $abono_get === 'total' || $abono_get === '';
$tasa_get = isset($_GET['tasa']) ? trim($_GET['tasa']) : '';
$ref_url = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if ($id_cotizacion == 0) { die("Recibo no encontrado"); }
if (isset($_GET['portal_token']) && !validarTokenDocumentoPortal($conexion, 'recibo', $id_cotizacion)) {
    http_response_code(403);
    die('El enlace del portal venció o no corresponde a este recibo.');
}

$q = mysqli_query($conexion, "SELECT c.*, cli.nombre as cliente_nombre, f.tasa_bcv AS tasa_factura_fiscal
                              FROM cotizacion c 
                              JOIN cliente cli ON c.id_cliente = cli.ID 
                              LEFT JOIN factura_fiscal_cotizacion f ON f.id_cotizacion = c.ID
                              WHERE c.ID = $id_cotizacion");
$cot = mysqli_fetch_assoc($q);

if (!$cot) { die("Cotización no encontrada"); }

$total = floatval($cot['total']);
$total_abonado = 0.0;
$pagos_usd = [];
$tasas_pago = [];

function numerosSeparadosRecibo($valor) {
    $texto = strip_tags(str_replace(['<br>', '<br/>', '<br />'], '|', (string)$valor));
    $partes = preg_split('/\s*\|\s*/', $texto);
    $numeros = [];
    foreach ($partes as $parte) {
        $limpio = preg_replace('/[^0-9,.-]/', '', trim($parte));
        if ($limpio === '' || !preg_match('/\d/', $limpio)) {
            $numeros[] = null;
            continue;
        }
        if (strpos($limpio, ',') !== false && strpos($limpio, '.') === false) {
            $limpio = str_replace(',', '.', $limpio);
        } else {
            $limpio = str_replace(',', '', $limpio);
        }
        $numeros[] = is_numeric($limpio) ? (float)$limpio : null;
    }
    return $numeros;
}

function textosSeparadosRecibo($valor) {
    $texto = str_ireplace(['ert{}', '<br>', '<br/>', '<br />'], '|', (string)$valor);
    $texto = strip_tags($texto);
    $partes = preg_split('/[\s]*\|[\s]*/', $texto);
    return array_values(array_filter(array_map('trim', $partes), function ($parte) {
        return $parte !== '';
    }));
}

// 1. Extraer y sumar TODOS los pagos registrados
if (trim((string)($cot['pago_valor_usd'] ?? '')) !== '') {
    $pagos_usd = numerosSeparadosRecibo($cot['pago_valor_usd']);
    foreach ($pagos_usd as $monto) {
        if ($monto !== null) {
            $total_abonado += $monto;
        }
    }
}
$tasas_pago = numerosSeparadosRecibo($cot['pago_tasa_bcv'] ?? '');
$totalAbonadoEfectivo = round($total_abonado, 2);

$retencionesJson = trim((string)($cot['pago_retenciones'] ?? ''));
$retencionesRaiz = $retencionesJson === '' ? [] : json_decode($retencionesJson);
if ($retencionesJson !== '' && (!is_array($retencionesRaiz) || json_last_error() !== JSON_ERROR_NONE)) {
    http_response_code(500);
    exit('El historial de retenciones está dañado y no se puede generar un recibo correcto.');
}
if ($retencionesJson === '') {
    $pagosRetenciones = [];
} else {
    $pagosRetenciones = json_decode($retencionesJson, true);
}
$tasaFacturaFiscal = (float)($cot['tasa_factura_fiscal'] ?? 0);
foreach ($pagosRetenciones as $registroRetencion) {
    if (!is_array($registroRetencion)) {
        http_response_code(500);
        exit('El historial contiene un registro de retención inválido.');
    }
    $montoRetenidoRegistro = 0.0;
    foreach (['iva_bs', 'islr_bs', 'municipal_bs'] as $campoRetencion) {
        $monto = $registroRetencion[$campoRetencion] ?? 0;
        if (!is_numeric($monto) || !is_finite((float)$monto) || (float)$monto < 0) {
            http_response_code(500);
            exit('El historial contiene un monto de retención inválido.');
        }
        $montoRetenidoRegistro += (float)$monto;
    }
    $tasaRegistro = $tasaFacturaFiscal > 0 ? $tasaFacturaFiscal : (float)($registroRetencion['tasa_bcv'] ?? 0);
    if ($montoRetenidoRegistro > 0 && $tasaRegistro <= 0) {
        http_response_code(500);
        exit('Falta una tasa BCV válida para calcular las retenciones del recibo.');
    }
}
$indicePago = filter_var($_GET['pago_idx'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$retencionesSeleccionadas = [];
if ($esReciboTotal || $indicePago === false || $indicePago === null) {
    $retencionesSeleccionadas = $pagosRetenciones;
} elseif (isset($pagosRetenciones[$indicePago]) && is_array($pagosRetenciones[$indicePago])) {
    $retencionesSeleccionadas = [$pagosRetenciones[$indicePago]];
}
$retencionesBsRecibo = ['iva_bs' => 0.0, 'islr_bs' => 0.0, 'municipal_bs' => 0.0];
$retencionesPorcentajes = ['iva' => [], 'islr' => [], 'municipal' => []];
$retencionesUsdPago = 0.0;
foreach ($pagosRetenciones as $registroRetencion) {
    if (!is_array($registroRetencion)) continue;
    $tasaRetencion = $tasaFacturaFiscal > 0 ? $tasaFacturaFiscal : (float)($registroRetencion['tasa_bcv'] ?? 0);
    foreach (array_keys($retencionesBsRecibo) as $campoRetencion) {
        $montoRetenido = max(0, (float)($registroRetencion[$campoRetencion] ?? 0));
        if ($tasaRetencion > 0) {
            $retencionesUsdPago += $montoRetenido / $tasaRetencion;
        }
    }
}
$retencionesUsdSeleccionadas = 0.0;
foreach ($retencionesSeleccionadas as $registroRetencion) {
    if (!is_array($registroRetencion)) continue;
    $tasaRetencion = $tasaFacturaFiscal > 0 ? $tasaFacturaFiscal : (float)($registroRetencion['tasa_bcv'] ?? 0);
    foreach (array_keys($retencionesBsRecibo) as $campoRetencion) {
        $retencionesBsRecibo[$campoRetencion] += max(0, (float)($registroRetencion[$campoRetencion] ?? 0));
    }
    foreach (array_keys($retencionesPorcentajes) as $tipoRetencion) {
        $porcentaje = (float)($registroRetencion[$tipoRetencion . '_pct'] ?? 0);
        if ($porcentaje > 0 && !in_array($porcentaje, $retencionesPorcentajes[$tipoRetencion], true)) {
            $retencionesPorcentajes[$tipoRetencion][] = $porcentaje;
        }
    }
    if ($tasaRetencion <= 0) continue;
    $retencionesUsdSeleccionadas += (
        (float)($registroRetencion['iva_bs'] ?? 0)
        + (float)($registroRetencion['islr_bs'] ?? 0)
        + (float)($registroRetencion['municipal_bs'] ?? 0)
    ) / $tasaRetencion;
}
$retencionesUsdSeleccionadas = round($retencionesUsdSeleccionadas, 2);
$total_abonado = round($total_abonado + $retencionesUsdPago, 2);

$saldo = $total - $total_abonado;
if ($saldo < 0) $saldo = 0;

// 2. Determinar el monto final para imprimir en el recibo
if ($esReciboTotal) {
    $hayRegistrosPago = count($pagos_usd) > 0 || count($pagosRetenciones) > 0;
    $abono = $hayRegistrosPago ? $totalAbonadoEfectivo : $total;
    if (empty($ref_url)) {
        $referencias_pago = textosSeparadosRecibo($cot['referencias_pago'] ?? '');
        $ref_url = $referencias_pago
            ? implode(' / ', $referencias_pago)
            : 'Varios / Historial Acumulado';
    }
} else {
    $abono = floatval($abono_get);
    if (empty($ref_url)) $ref_url = 'Depósito / Efectivo';
}

$tasa_recibo = is_numeric($tasa_get) && (float)$tasa_get > 0 ? (float)$tasa_get : null;
$monto_bs_recibo = null;
$tasas_usadas = [];
if ($esReciboTotal) {
    $montoBsCalculado = 0.0;
    $todosLosPagosTienenTasa = count($pagos_usd) > 0;
    foreach ($pagos_usd as $indice => $montoPago) {
        if ($montoPago === null) {
            continue;
        }
        $tasaPago = $tasas_pago[$indice] ?? null;
        if ($tasaPago === null || $tasaPago <= 0) {
            $todosLosPagosTienenTasa = false;
            continue;
        }
        $montoBsCalculado += $montoPago * $tasaPago;
        $tasas_usadas[] = $tasaPago;
    }
    if ($todosLosPagosTienenTasa) {
        $monto_bs_recibo = $montoBsCalculado;
        $tasas_usadas = array_values(array_unique($tasas_usadas));
    }
} elseif ($tasa_recibo !== null) {
    $monto_bs_recibo = $abono * $tasa_recibo;
    $tasas_usadas = [$tasa_recibo];
} else {
    foreach ($pagos_usd as $indice => $montoPago) {
        if ($montoPago !== null && abs($montoPago - $abono) < 0.01 && isset($tasas_pago[$indice]) && $tasas_pago[$indice] > 0) {
            $tasa_recibo = $tasas_pago[$indice];
            $monto_bs_recibo = $abono * $tasa_recibo;
            $tasas_usadas = [$tasa_recibo];
            break;
        }
    }
}
$tasa_texto_recibo = $tasas_usadas
    ? implode(' / ', array_map(function ($tasa) { return number_format($tasa, 2, ',', '.'); }, $tasas_usadas))
    : null;

$nro_recibo = "REC-" . str_pad($id_cotizacion, 5, "0", STR_PAD_LEFT) . "-" . date('Hs');
$fecha_actual = date('d/m/Y');
$dia = date('d'); $mes = date('m'); $anio = date('Y');

// 3. Transformar a letras (USD)
$fmt = new NumberFormatter("es", NumberFormatter::SPELLOUT);
$enteros = intval($abono);
$centavos = str_pad(round(($abono - $enteros) * 100), 2, "0", STR_PAD_RIGHT);
$abono_letras = ucfirst($fmt->format($enteros)) . " con " . $centavos . "/100";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago - Unifuerte</title>
    <style>
        body { font-family: 'Arial', sans-serif; background: #eef2f5; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .receipt-container { 
            width: 800px; 
            background: #fff; 
            border: 3px solid #000080; 
            border-radius: 15px; 
            padding: 30px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
            position: relative;
        }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .date-box { border: 2px solid #000080; border-radius: 8px; overflow: hidden; text-align: center; display: flex; }
        .date-box div { padding: 5px 15px; border-right: 2px solid #000080; font-weight: bold; color: #000080; }
        .date-box div:last-child { border-right: none; }
        .date-box .header-row { background: #000080; color: white; border-bottom: 2px solid #000080; font-size: 12px; }
        .date-box .val-row { font-size: 16px; padding: 10px; color: #000;}
        
        .title-area { text-align: center; flex-grow: 1; }
        .title-area h2 { color: #d32f2f; margin: 0; font-size: 20px; letter-spacing: 2px; }
        .title-area h1 { color: #000080; margin: 5px 0 0 0; font-size: 38px; font-weight: 900; letter-spacing: 5px;}
        
        .currency-box { border: 2px solid #000080; border-radius: 8px; width: 150px; font-weight: bold; color: #000080;}
        .currency-box div { border-bottom: 2px solid #000080; padding: 5px 10px; display:flex; justify-content: space-between;}
        .currency-box div:last-child { border-bottom: none; }
        .currency-box span { color: #000; }

        .form-row { margin-top: 25px; font-size: 18px; font-weight: bold; color: #000080; display: flex; align-items: baseline; }
        .form-row .line { flex-grow: 1; border-bottom: 1.5px solid #000; margin-left: 10px; color: #000; font-weight: normal; font-size: 18px; text-transform: uppercase; padding-bottom: 2px;}
        
        .checkbox-row { margin-top: 30px; display: flex; align-items: center; justify-content: space-between; font-weight: bold; color: #000080;}
        .check-box { width: 20px; height: 20px; border: 2px solid #000080; display: inline-block; vertical-align: middle; margin-left: 5px; }
        .small-line { border-bottom: 1.5px solid #000; display: inline-block; width: 150px; color: #000; font-weight:normal; text-align:center;}
        
        .footer-row { margin-top: 40px; display: flex; justify-content: space-between; font-weight: bold; color: #000080;}
        .signature-box { text-align: center; width: 250px; }
        .signature-line { border-bottom: 1.5px solid #000; height: 30px; margin-bottom: 5px;}
        .total-box { display: flex; align-items: center; font-size: 20px; }
        .total-box .box { border: 2px solid #000080; border-radius: 8px; padding: 5px 20px; margin-left: 10px; color: #000;}
        .withholdings { margin-top: 14px; border: 1px solid #000080; padding: 9px 12px; color: #000080; font-size: 13px; font-weight: bold; }
        .withholdings table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .withholdings td { padding: 3px 5px; color: #111; }
        .withholdings td:last-child { text-align: right; white-space: nowrap; }

        /* Editable visual hint */
        .editable { cursor: text; outline: none; border-radius: 4px; transition: background 0.3s; }
        .editable:hover { background-color: #ffffcc; }
        .editable:focus { background-color: #fffacd; box-shadow: 0 0 5px rgba(0,0,128,0.3); }

        /* Watermark */
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.05; width: 400px; z-index: 0; pointer-events: none;}
        .content-layer { position: relative; z-index: 1; }

        .btn-container { margin-top: 20px; display: flex; gap: 15px; justify-content: center; }
        .btn-print { background: #000080; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .btn-reset { background: #d32f2f; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; }

        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { box-shadow: none; border: 3px solid #000080 !important; margin: 0 auto; }
            .btn-container { display: none; }
            .editable:hover { background-color: transparent; }
            .editable:focus { box-shadow: none; background-color: transparent;}
        }
    </style>
</head>
<body>
    <div>
        <div class="receipt-container">
            <img src="../img/unifuerte.jpg" class="watermark">
            <div class="content-layer">
                <div class="header">
                    <div style="display:flex; flex-direction: column;">
                        <img src="../img/unifuerte.jpg" style="height: 60px; object-fit: contain; margin-bottom: 5px;">
                        <div class="date-box">
                            <div style="padding:0;"><div class="header-row">DÍA</div><div class="val-row editable" id="edit-dia" contenteditable="true"><?= $dia ?></div></div>
                            <div style="padding:0;"><div class="header-row">MES</div><div class="val-row editable" id="edit-mes" contenteditable="true"><?= $mes ?></div></div>
                            <div style="padding:0; border-right:none;"><div class="header-row">AÑO</div><div class="val-row editable" id="edit-anio" contenteditable="true"><?= $anio ?></div></div>
                        </div>
                    </div>

                    <div class="title-area">
                        <h2>Nº <span class="editable" id="edit-nro" contenteditable="true"><?= $nro_recibo ?></span></h2>
                        <h1>RECIBO</h1>
                    </div>

                    <div class="currency-box">
                        <div>Bs. <span class="editable" id="edit-monto-bs" contenteditable="true"><?= $monto_bs_recibo !== null ? number_format($monto_bs_recibo, 2, ',', '.') : '---' ?></span></div>
                        <div>$ USD <span class="editable" id="edit-monto-usd" contenteditable="true"><?= number_format($abono, 2, ',', '.') ?></span></div>
                        <div>Cotiz. <span class="editable" id="edit-cotiz" contenteditable="true"><?= $cot['nro_cotizacion'] ?></span></div>
                    </div>
                </div>

                <div class="form-row">
                    Recibí de: <div class="line editable" id="edit-cliente" contenteditable="true"><?= htmlspecialchars($cot['cliente_nombre']) ?></div>
                </div>

                <div class="form-row">
                    La suma de: <div class="line editable" id="edit-letras" contenteditable="true"><?= $abono_letras ?> Dólares / USD</div>
                </div>

                <div class="form-row">
                    Por concepto de: <div class="line editable" id="edit-concepto" contenteditable="true">Abono/Pago a Pedido/Cotización Nro. <?= $cot['nro_cotizacion'] ?></div>
                </div>
                
                <?php if ($tasa_texto_recibo !== null): ?>
                <div class="editable" id="edit-tasa" contenteditable="true" style="margin-top: 12px; text-align: right; color: #000080; font-weight: bold;">
                    Tasa BCV aplicada: Bs <?= $tasa_texto_recibo ?> por USD
                </div>
                <?php endif; ?>
                
                <?php if (array_sum($retencionesBsRecibo) > 0): ?>
                <div class="withholdings">
                    Retenciones fiscales de este recibo
                    <table>
                        <?php foreach (['iva' => 'Ret. IVA', 'islr' => 'Ret. ISLR', 'municipal' => 'Ret. municipal'] as $tipo => $etiqueta):
                            $campoBs = $tipo . '_bs';
                            $porcentajesTexto = $retencionesPorcentajes[$tipo]
                                ? ' (' . implode(' / ', array_map(function ($porcentaje) { return rtrim(rtrim(number_format($porcentaje, 2, ',', ''), '0'), ','); }, $retencionesPorcentajes[$tipo])) . '%)'
                                : '';
                        ?>
                        <tr><td><?= $etiqueta . $porcentajesTexto ?></td><td>Bs <span class="editable" id="edit-ret-<?= $tipo ?>" contenteditable="true"><?= number_format($retencionesBsRecibo[$campoBs], 2, ',', '.') ?></span></td></tr>
                        <?php endforeach; ?>
                        <tr><td>Total retenciones (USD)</td><td>$ <span class="editable" id="edit-ret-total" contenteditable="true"><?= number_format($retencionesUsdSeleccionadas, 2, ',', '.') ?></span></td></tr>
                    </table>
                </div>
                <?php endif; ?>

                <div class="checkbox-row">
                    <div>En efectivo <div class="check-box"></div></div>
                    <div>Transferencia/Zelle <div class="check-box" style="background:#000080;"></div></div>
                    <div>Ref/N°: <div class="small-line editable" id="edit-ref" contenteditable="true" style="font-size: 14px;"><?= htmlspecialchars($ref_url) ?></div></div>
                </div>
                
                <div class="checkbox-row" style="justify-content: flex-end; margin-top: 15px;">
                    <div>A Cuenta <div class="small-line editable" id="edit-acuenta" contenteditable="true" style="width: 100px;">$ <?= number_format($total_abonado, 2, ',', '.') ?></div></div>
                    <div style="margin-left: 20px;">Saldo <div class="small-line editable" id="edit-saldo" contenteditable="true" style="width: 100px; border: 2px solid #000080; border-radius:5px; height: 22px;">$ <?= number_format($saldo, 2, ',', '.') ?></div></div>
                </div>

                <div class="footer-row">
                    <div class="signature-box">
                        <div class="signature-line">Unifuerte C.A.</div>
                        Recibí conforme
                    </div>
                    <div class="signature-box">
                        <div class="signature-line editable" id="edit-firma-cliente" contenteditable="true"><?= htmlspecialchars($cot['cliente_nombre']) ?></div>
                        Entregué Conforme
                    </div>
                    <div class="total-box">
                        TOTAL <div class="box editable" id="edit-total" contenteditable="true">$ <?= number_format($total, 2, ',', '.') ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="btn-container">
            <button class="btn-print" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
            <button class="btn-reset" onclick="restablecerRecibo()">🔄 Restablecer</button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Clave única para guardar los datos por recibo
            const idRecibo = "<?= $id_cotizacion ?>_<?= $abono_get ?>";
            const prefix = "recibo_unifuerte_" + idRecibo + "_";
            
            const editables = document.querySelectorAll(".editable");

            // Cargar datos guardados si existen
            editables.forEach(el => {
                const key = prefix + el.id;
                const savedValue = localStorage.getItem(key);
                
                if (savedValue !== null) {
                    el.innerHTML = savedValue;
                }

                // Auto-guardar mientras el usuario escribe
                el.addEventListener("input", function() {
                    localStorage.setItem(key, this.innerHTML);
                });
            });
        });

        // Función para borrar las modificaciones y recargar
        function restablecerRecibo() {
            if(confirm("¿Estás seguro de que deseas borrar los cambios y restablecer los valores originales del recibo?")) {
                const idRecibo = "<?= $id_cotizacion ?>_<?= $abono_get ?>";
                const prefix = "recibo_unifuerte_" + idRecibo + "_";
                
                const editables = document.querySelectorAll(".editable");
                editables.forEach(el => {
                    localStorage.removeItem(prefix + el.id);
                });
                
                // Recargar la página para traer los datos originales de la base de datos
                location.reload();
            }
        }
    </script>
</body>
</html>