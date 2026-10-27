<?php
session_start();
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../conexion.php';
require_once __DIR__ . '/portal_documento_auth.php';

// Verificamos si pasaron el ID
if (!isset($_GET['id'])) {
    die("Acceso denegado o ID inválido.");
}

$id_cot = intval($_GET['id']);
$llave_url = $_GET['k'] ?? ''; 
$accesoPortal = validarTokenDocumentoPortal($conexion, 'cotizacion', $id_cot);

$sql = "SELECT c.*, cli.nombre AS cliente_nombre, cli.cedula_Rif, cli.direccion, cli.comprador, cli.email, cli.tlf AS cliente_tlf,
               u.nombre AS v_nombre, u.apellido AS v_apellido, u.tlf AS v_tlf, u.profesion AS v_profesion, u.email AS v_email,
               fp.forma_pago AS metodo_pago_texto
        FROM cotizacion c 
        LEFT JOIN cliente cli ON c.id_cliente = cli.ID 
        LEFT JOIN usuario u ON c.id_vendedor = u.ID
        LEFT JOIN cotizacion_formas_pago fp ON c.id_forma_pago = fp.id
        WHERE c.ID = $id_cot";
$res = mysqli_query($conexion, $sql);
$cotizacion = $res ? mysqli_fetch_assoc($res) : null;

if (!$cotizacion) die("Cotización no encontrada en el sistema.");

if (!isset($_SESSION['usuario_id']) && !$accesoPortal) {
    if (isset($_GET['portal_token'])) {
        http_response_code(403);
        die('El enlace del portal venció o no corresponde a este documento.');
    }
    $llave_esperada = base64_encode($id_cot . '-' . $cotizacion['nro_cotizacion']);
    if ($llave_url !== $llave_esperada) {
        die("<div style='text-align:center; padding: 50px; font-family: sans-serif;'>
                <h2 style='color:#E11D48;'>Acceso Protegido</h2>
                <p>Este enlace es privado, ha expirado o está mal escrito.<br>Por favor, solicite a su asesor de Unifuerte un nuevo enlace.</p>
             </div>");
    }
}

// TRAEMOS LOS DETALLES DE LA COTIZACIÓN HACIENDO LEFT JOIN CON LOS PRODUCTOS PARA LA FOTO
$sql_det = "SELECT d.*, p.foto FROM detalle_cotizacion d LEFT JOIN productos p ON d.id_producto = p.id WHERE d.id_cotizacion = $id_cot ORDER BY d.ID ASC";
$res_det = mysqli_query($conexion, $sql_det);
$detalles = [];
$total_prendas = 0;
if ($res_det) {
    while ($row = mysqli_fetch_assoc($res_det)) {
        $detalles[] = $row;
        $total_prendas += intval($row['cantidad']);
    }
}

$metodo_pago_texto = $cotizacion['metodo_pago_texto'] ?? "A convenir";
$metodo_entrega = $cotizacion['metodo_entrega'] ?? "A convenir con el cliente";

function formatoBs($num) { return number_format(floatval($num), 2, ',', '.'); }

// CÁLCULOS FINANCIEROS EN CASCADA PARA EL PDF
$sub_total = floatval($cotizacion['sub_total']);
$anticipo_fabricacion = round($sub_total * 0.60, 2);
$servicio_logistico = floatval($cotizacion['servicio_logistico'] ?? 0);
$flete = floatval($cotizacion['flete'] ?? 0);
$descuento = floatval($cotizacion['descuento']);
$porcentaje_desc = floatval($cotizacion['porcentaje_descuento_manual'] ?? 0);

// Base imponible = (SubTotal + Servicio Logístico + Flete) - Descuento
$base_imponible = ($sub_total + $servicio_logistico + $flete) - $descuento;
if ($base_imponible < 0) $base_imponible = 0;

$iva = floatval($cotizacion['iva']);
$aplica_iva = intval($cotizacion['aplica_iva'] ?? 1);
$igtf = floatval($cotizacion['igtf']);
$total_pagar = floatval($cotizacion['total']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización <?= htmlspecialchars($cotizacion['nro_cotizacion']) ?></title>
    <!-- LIBRERÍA HTML2PDF PARA DESCARGA DIRECTA -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * {
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
            box-sizing: border-box;
        }

        body { 
            background: #525659; 
            margin: 0; 
            font-family: 'Arial', sans-serif; 
            color: #000;
        }

        /* BARRA SUPERIOR FIJA PARA EL BOTÓN */
        .top-bar {
            background-color: #13131A;
            padding: 15px;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .btn-print { 
            padding: 12px 30px; 
            font-size: 16px; 
            font-weight: bold; 
            background: #FF7518; 
            color: #FFF; 
            border: none; 
            cursor: pointer; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); 
            transition: 0.3s;
        }
        .btn-print:hover { background: #e05e00; transform: scale(1.05); }
        .btn-print:disabled { background: #666; cursor: not-allowed; transform: none; }

        /* CONTENEDOR RESPONSIVO: EVITA QUE SE APLASTE EN CELULARES */
        .vista-previa {
            width: 100%;
            overflow-x: auto; 
            padding: 90px 20px 40px 20px; 
            text-align: center;
        }

        /* TAMAÑO CARTA EXACTO */
        #documento_pdf {
            background: white; 
            width: 794px; 
            min-width: 794px; 
            min-height: 1123px; 
            margin: 0 auto; 
            padding: 40px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.5); 
            text-align: left; 
        }

        .row-clear::after { content: ""; clear: both; display: table; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .logo-img { max-height: 70px; }
        .company-info { text-align: right; font-weight: bold; font-size: 10px; line-height: 1.2; }
        
        .title-box { width: 100%; border: 2px solid #000; border-collapse: collapse; text-align: center; margin-bottom: 10px; }
        .title-box td { padding: 6px 10px; border: 2px solid #000; font-size: 16px; font-weight: bold; text-transform: uppercase; background-color: #fff; vertical-align: middle; }
        .title-box td.nro-box { width: 210px; padding: 6px 14px; font-size: 13px; white-space: nowrap; }
        
        .client-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 2px solid #000; }
        .client-table td { border: 1px solid #000; padding: 4px; font-size: 11px; vertical-align: middle; }
        .client-table td.lbl { font-weight: bold; background-color: #f0f0f0; } 
        .client-table td.val { font-weight: bold; }
        
        .greeting { text-align: center; font-size: 18px; font-style: italic; font-weight: bold; margin: 15px 0; letter-spacing: 0.5px; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 2px solid #000; }
        .items-table th { border: 1px solid #000; padding: 6px; text-align: center; font-size: 11px; background-color: #f0f0f0; }
        .items-table td { border: 1px solid #000; padding: 6px; text-align: center; vertical-align: middle; font-weight: bold; font-size: 11px;}
        .items-table td.desc { text-align: left; font-weight: normal; font-size: 10px; }
        
        .totals-table { width: 100%; border-collapse: collapse; font-size: 12px; font-weight: bold; }
        .totals-table td { padding: 6px 8px; text-align: right; border-bottom: 1px solid #000; vertical-align: middle; }
        .totals-table td.lbl { width: 68%; }
        .totals-table tr.advance-row td { 
            background-color: #fff1e6; 
            color: #b45309; 
            font-size: 12px; 
            padding: 8px;
            border-top: 1px solid #000;
            border-bottom: 2px solid #000;
        }
        .totals-table .sub-lbl {
            display: block;
            font-size: 10px;
            font-weight: normal;
            color: #9a3412;
            margin-top: 2px;
        }
        
        .footer-notes { clear: both; width: 100%; border: 2px solid #000; border-collapse: collapse; margin-top: 20px; }
        .footer-notes th { border: 1px solid #000; background-color: #f0f0f0; padding: 4px; font-size: 12px; }
        .footer-notes td { border: 1px solid #000; padding: 4px; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="top-bar no-print">
        <button id="btnDescargar" class="btn-print" onclick="descargarPDF()">📥 Descargar Cotización PDF</button>
    </div>

    <div class="vista-previa">
        <div id="documento_pdf">
            <table class="header-table">
                <tr>
                    <td style="width: 40%; vertical-align: top;">
                        <img src="../img/unifuerte.jpg" class="logo-img" alt="Unifuerte Logo">
                    </td>
                    <td class="company-info" style="width: 60%;">
                        UNIFUERTE, C.A<br>
                        R.I.F: J-41282764-2 / R.U.C 1829-2021<br>
                        SENCAMER 0012216TN-21<br>
                        CR. UD-321, EDIFICIO PARCELA 321-16-10A-1<br>
                        PISO 1, OFICINA, A,B,C, ZONA INDUSTRIAL UD 321<br>
                        CIUDAD GUAYANA, PUERTO ORDAZ, ESTADO BOLIVAR<br>
                        CONTACTO: 04241546889<br>
                        EMAIL: CLIENTEUNIFUERTE@GMAIL.COM
                    </td>
                </tr>
            </table>

            <table class="title-box">
                <tr>
                    <td>COTIZACIÓN</td>
                    <td class="nro-box">
                        NRO.<br>
                        <span style="font-size: 15px; display: inline-block; margin-top: 2px;"><?= htmlspecialchars(str_replace('COT-', '', $cotizacion['nro_cotizacion'])) ?></span>
                    </td>
                </tr>
            </table>

            <table class="client-table">
                <tr>
                    <td class="lbl" style="width: 15%;">Fecha</td>
                    <td class="val" style="width: 35%;"><?= date('d/m/Y', strtotime($cotizacion['fecha_emision'])) ?></td>
                    <td class="lbl" style="width: 25%;">Nombre o Razón Social</td>
                    <td class="val" style="width: 25%; text-transform: uppercase;">
                        <?= htmlspecialchars($cotizacion['cliente_nombre'] ?? '') ?> 
                        <?= $cotizacion['contribuyente_especial'] == 1 ? '<span style="font-size:9px; color:#555;">(Contribuyente Especial)</span>' : '' ?>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Cédula o R.I.F</td>
                    <td class="val" colspan="3" style="text-transform: uppercase;"><?= htmlspecialchars($cotizacion['cedula_Rif'] ?? '') ?></td>
                </tr>
                <tr>
                    <td class="lbl">Dirección</td>
                    <td class="val" style="text-transform: uppercase;"><?= htmlspecialchars($cotizacion['direccion'] ?? '') ?></td>
                    <td class="lbl">Teléfono</td>
                    <td class="val"><?= htmlspecialchars($cotizacion['cliente_tlf'] ?? '') ?></td>
                </tr>
            </table>

            <table class="client-table">
                <tr>
                    <td class="lbl" style="width: 15%;">Comprador</td>
                    <td class="val" style="width: 35%; text-transform: uppercase;"><?= htmlspecialchars($cotizacion['comprador'] ?? '') ?></td>
                    <td class="lbl" style="width: 25%;">Teléfono</td>
                    <td class="val" style="width: 25%;"><?= htmlspecialchars($cotizacion['cliente_tlf'] ?? '') ?></td>
                </tr>
                <tr>
                    <td class="lbl">Email</td>
                    <td class="val" colspan="3" style="text-transform: none;"><?= htmlspecialchars($cotizacion['email'] ?? '') ?></td>
                </tr>
            </table>

            <table class="client-table">
                <tr>
                    <td class="lbl" style="width: 15%;">Vendedor</td>
                    <td class="val" colspan="3" style="text-transform: uppercase;">
                        <?= htmlspecialchars(trim(($cotizacion['v_nombre'] ?? '') . ' ' . ($cotizacion['v_apellido'] ?? ''))) ?>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Email</td>
                    <td class="val" colspan="3" style="text-transform: none;"><?= htmlspecialchars($cotizacion['v_email'] ?? '') ?></td>
                </tr>
            </table>

            <div class="greeting">Estimado Cliente, reciba nuestra mejor oferta;</div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Código</th>
                        <th style="width: 15%;">Foto / Ref.</th>
                        <th style="width: 8%;">Cant</th>
                        <th style="width: 42%;">Descripción Detallada</th>
                        <th style="width: 12%;">Precio<br>Unitario</th>
                        <th style="width: 13%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $codigo_base = 69350; 
                    foreach ($detalles as $det): 
                        $codigo_base++;
                        $sub_linea_neto = floatval($det['precio_unitario']) * intval($det['cantidad']);

                        $foto_ruta = '../img/unifuerte.jpg';
                        if (!empty($det['foto'])) {
                            if (file_exists('../../images/uploads/' . $det['foto'])) {
                                $foto_ruta = '../../images/uploads/' . $det['foto'];
                            } elseif (file_exists('../images/uploads/' . $det['foto'])) {
                                $foto_ruta = '../images/uploads/' . $det['foto'];
                            }
                        }

                        $detalle_limpio = preg_replace('/\[Para:.*?\]\s*-\s*/', '', $det['detalle_diseno'] ?? '');
                        
                        $genero_txt = '';
                        if (!empty($det['genero']) && $det['genero'] !== 'No especificado') {
                            $g_lbl = $det['genero'];
                            if (strpos($g_lbl, 'Talla Extra') !== false) {
                                $g_lbl = 'Tallas Extras';
                            }
                            $genero_txt = ' / ' . htmlspecialchars($g_lbl);
                        }
                    ?>
                    <tr>
                        <td>U<?= $codigo_base ?></td>
                        <td style="text-align: center; padding: 2px;">
                            <img src="<?= $foto_ruta ?>" style="max-height: 45px; max-width: 50px; object-fit: contain; border-radius: 4px;">
                        </td>
                        <td style="font-size: 14px;"><?= intval($det['cantidad']) ?></td>
                        <td class="desc">
                            <?= htmlspecialchars($det['tipo_prenda']) ?><?= $genero_txt ?>
                            <?php if (!empty($detalle_limpio)): ?>
                                <br><span style="color: #444; font-size: 9px;"><?= htmlspecialchars($detalle_limpio) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>$ <?= formatoBs($det['precio_unitario']) ?></td>
                        <td style="font-size: 13px;">
                            $ <?= formatoBs($sub_linea_neto) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="row-clear" style="margin-bottom: 20px;">
                <div style="width: 53%; float: left;">
                    <?php if (!empty($cotizacion['notas'])): ?>
                    <div style="border: 2px solid #000; margin-bottom: 10px;">
                        <div style="background-color: #f0f0f0; border-bottom: 1px solid #000; padding: 4px; font-size: 10px; font-weight: bold; text-align: center;">NOTAS ADICIONALES</div>
                        <div style="padding: 6px; font-size: 10px; white-space: pre-wrap; font-weight: bold;"><?= htmlspecialchars($cotizacion['notas']) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($cotizacion['no_incluye'])): ?>
                    <div style="border: 2px solid #E11D48;">
                        <div style="background-color: #ffe4e6; color: #E11D48; border-bottom: 1px solid #E11D48; padding: 4px; font-size: 10px; font-weight: bold; text-align: center;">LO QUE NO INCLUYE ESTA COTIZACIÓN</div>
                        <div style="padding: 6px; font-size: 10px; white-space: pre-wrap; color: #E11D48; font-weight: bold;"><?= htmlspecialchars($cotizacion['no_incluye']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="width: 44%; float: right;">
                    <table class="totals-table">
                        <tr>
                            <td class="lbl">Sub-Total $</td>
                            <td><?= formatoBs($sub_total) ?></td>
                        </tr>

                        <?php if ($servicio_logistico > 0): ?>
                        <tr>
                            <td class="lbl" style="color: #0B4C8C;">Servicio Logístico de Envío $</td>
                            <td style="color: #0B4C8C;"><?= formatoBs($servicio_logistico) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($flete > 0): ?>
                        <tr>
                            <td class="lbl" style="color: #0B4C8C;">Flete $</td>
                            <td style="color: #0B4C8C;"><?= formatoBs($flete) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($descuento > 0): ?>
                        <tr>
                            <td class="lbl" style="color: red;">Descuento (<?= $porcentaje_desc ?>%) $</td>
                            <td style="color: red;">- <?= formatoBs($descuento) ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <td class="lbl" style="color: #000; font-size: 13px;">Base Imponible $</td>
                            <td style="color: #000; font-size: 13px;"><?= formatoBs($base_imponible) ?></td>
                        </tr>
                        
                        <?php if ($aplica_iva == 1 && $iva > 0): ?>
                        <tr>
                            <td class="lbl">I.V.A (16%) $</td>
                            <td><?= formatoBs($iva) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($igtf > 0): ?>
                        <tr>
                            <td class="lbl" style="color: #DAA520;">IGTF (3%) $</td>
                            <td style="color: #DAA520;"><?= formatoBs($igtf) ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <td class="lbl" style="font-size: 15px; color: #0B4C8C;">Total a Pagar $</td>
                            <td style="font-size: 15px; color: #0B4C8C;"><?= formatoBs($total_pagar) ?></td>
                        </tr>

                        <tr class="advance-row">
                            <td class="lbl">
                                Anticipo mínimo (60%) $
                                <span class="sub-lbl">Requerido para iniciar fabricación</span>
                            </td>
                            <td style="font-size: 14px;"><?= formatoBs($anticipo_fabricacion) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <table class="footer-notes">
                <tr><th colspan="1">NOTAS INFORMATIVAS</th></tr>
                <tr><td>El Precio Unitario de la prenda no incluye el Impuesto de Ley</td></tr>
                <tr><td>Los colores de los hilos para bordar es el color más aproximado al color digital del logotipo, enviamos muestra física para su aprobación</td></tr>
                <tr><td>Los colores de la tela del proveedor es lo mas aproximado a los colores del diseño digital, se envía muestra física y ficha técnica de la tela para su aprobación</td></tr>
                <tr><td>Las Prendas descritas son según tallas regulares preestablecida (S-M-L-XL), las tallas extras aplican cargos adicionales</td></tr>
                <tr><td>Estimación de tiempo de entrega 30 días corrientes con entregas parciales semanales de producción una vez pagado el anticipo y aprobada la muestra digital visión 360</td></tr>
                <tr><td>Disponemos del tallaje completo en Camisería de dama y caballeros tallas Regulares (XS, S, M, L, XL, y 2XL) para comprobar la veracidad de las tallas solicitadas y así evitar inconvenientes</td></tr>
                <tr><td>Para trabajos Urgentes debe pagar la orden de contado mas un recargo adicional del 10% por Servicio Express para subsanar gastos de pago tiempos extras nocturnos, alimentación y transporte del equipo que se hará cargo de la orden</td></tr>
                <tr><td>Garantía del 100% de confección 3 días hábiles de recibir pedido</td></tr>
                <tr><td>Aceptamos pagos multimonedas y en Bolívares Soberanos a Tasa del Banco Central de Venezuela</td></tr>
                <tr><td style="color: #0B4C8C;">Método de pago acordado: <?= htmlspecialchars($metodo_pago_texto) ?></td></tr>
                <tr><td style="color: #0B4C8C; font-size: 11px;">MÉTODO Y LUGAR DE ENTREGA: <?= htmlspecialchars($metodo_entrega) ?></td></tr>
            </table>
        </div>
    </div>

    <!-- SCRIPT DE DESCARGA DIRECTA -->
    <script>
    function descargarPDF() {
        const btn = document.getElementById('btnDescargar');
        btn.innerHTML = '⏳ Generando, por favor espere...';
        btn.disabled = true;

        const elemento = document.getElementById('documento_pdf');
        elemento.style.boxShadow = 'none';

        const opciones = {
            margin:       [10, 10, 10, 10], 
            filename:     'Cotizacion_Unifuerte_<?= addslashes(str_replace("#", "", $cotizacion["nro_cotizacion"])) ?>.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 2, useCORS: true, width: 794 },
            jsPDF:        { unit: 'mm', format: 'letter', orientation: 'portrait' }
        };

        html2pdf().set(opciones).from(elemento).save().then(() => {
            btn.innerHTML = '📥 Descargar Cotización PDF';
            btn.disabled = false;
            elemento.style.boxShadow = '0 5px 15px rgba(0,0,0,0.5)';
        });
    }
    </script>
</body>
</html>