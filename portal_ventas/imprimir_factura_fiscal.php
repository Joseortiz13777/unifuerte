<?php
session_start();
require_once '../conexion.php';
require_once __DIR__ . '/portal_documento_auth.php';

$id_cot = intval($_GET['id'] ?? 0);
$num_fac_especifica = intval($_GET['factura'] ?? 0);

if ($id_cot <= 0) {
    http_response_code(400);
    die('La cotización indicada no es válida.');
}
$accesoPortal = validarTokenDocumentoPortal($conexion, 'factura', $id_cot);
if (!isset($_SESSION['usuario_id']) && !$accesoPortal) {
    http_response_code(isset($_GET['portal_token']) ? 403 : 401);
    die(isset($_GET['portal_token'])
        ? 'El enlace del portal venció o no corresponde a esta factura.'
        : 'Debes iniciar sesión para generar la factura.');
}

$condicionFactura = $num_fac_especifica > 0
    ? "AND f.numero_factura = $num_fac_especifica"
    : "AND (f.estado IS NULL OR f.estado = 'Activa')";

$sql = "SELECT c.*, cli.nombre AS cliente_nombre, cli.cedula_Rif, cli.direccion,
               cli.comprador, cli.email, cli.tlf AS cliente_tlf,
               u.nombre AS vendedor_nombre, u.apellido AS vendedor_apellido,
               f.numero_factura AS factura_numero, f.numero_control AS factura_control,
               f.tasa_bcv AS factura_tasa, f.estado AS factura_estado,
               f.motivo_anulacion AS factura_motivo_anulacion,
               f.fecha_emision AS factura_fecha_emision
        FROM cotizacion c
        JOIN cliente cli ON cli.ID = c.id_cliente
        LEFT JOIN usuario u ON u.ID = c.id_vendedor
        LEFT JOIN factura_fiscal_cotizacion f ON f.id_cotizacion = c.ID $condicionFactura
        WHERE c.ID = $id_cot
        ORDER BY f.numero_factura DESC
        LIMIT 1";
$resultado = mysqli_query($conexion, $sql);
if (!$resultado) {
    http_response_code(500);
    die('No se pudo cargar la cotización seleccionada.');
}
$cotizacion = mysqli_fetch_assoc($resultado);

if (!$cotizacion) {
    http_response_code(404);
    die('No se encontró la cotización seleccionada.');
}
if ($cotizacion['factura_numero'] === null) {
    http_response_code(404);
    die('La factura aún no ha sido emitida o se encuentra anulada.');
}
if (($cotizacion['tipo_cotizacion'] ?? '') === 'MUESTRA') {
    http_response_code(400);
    die('Las muestras físicas no admiten factura fiscal.');
}

if (!$accesoPortal) {
    $q_permisos = mysqli_query($conexion, "SELECT permisos FROM usuario WHERE ID = " . intval($_SESSION['usuario_id']) . " LIMIT 1");
    $datos_permisos = $q_permisos ? mysqli_fetch_assoc($q_permisos) : null;
    $permisos_usuario = explode(',', $datos_permisos['permisos'] ?? '');
    if (!in_array('6', $permisos_usuario, true) && !in_array('7', $permisos_usuario, true)) {
        http_response_code(403);
        die('No tienes permiso para consultar cotizaciones.');
    }
    if (!in_array('7', $permisos_usuario, true) && intval($cotizacion['id_vendedor']) !== intval($_SESSION['usuario_id'])) {
        http_response_code(403);
        die('No tienes permiso para consultar esta factura.');
    }
}

$numero_factura = str_pad((string)$cotizacion['factura_numero'], 8, '0', STR_PAD_LEFT);
$numero_control = trim((string)$cotizacion['factura_control']);
$tasa = (float)$cotizacion['factura_tasa'];
$es_anulada = strtoupper(trim((string)($cotizacion['factura_estado'] ?? 'Activa'))) === 'ANULADA';
$motivo_anulacion = trim((string)($cotizacion['factura_motivo_anulacion'] ?? ''));
$numero_factura_html = htmlspecialchars($numero_factura, ENT_QUOTES, 'UTF-8');
$numero_control_html = htmlspecialchars($numero_control, ENT_QUOTES, 'UTF-8');

$res_detalles = mysqli_query($conexion, "
    SELECT d.*, p.foto
    FROM detalle_cotizacion d
    LEFT JOIN productos p ON p.id = d.id_producto
    WHERE d.id_cotizacion = $id_cot
    ORDER BY d.ID
");
if (!$res_detalles) {
    http_response_code(500);
    die('No se pudieron cargar los productos de la cotización.');
}

$detalles = [];
while ($fila = mysqli_fetch_assoc($res_detalles)) {
    $detalles[] = $fila;
}

function formatoFacturaFiscal($monto) {
    return number_format((float)$monto, 2, ',', '.');
}

$sub_total = (float)$cotizacion['sub_total'];
$servicio_logistico = (float)($cotizacion['servicio_logistico'] ?? 0);
$flete = (float)($cotizacion['flete'] ?? 0);
$descuento = (float)$cotizacion['descuento'];
$base_imponible = max(0, $sub_total + $servicio_logistico + $flete - $descuento);
$iva = (float)$cotizacion['iva'];
$igtf = (float)$cotizacion['igtf'];
$total = (float)$cotizacion['total'];
$aplica_iva = intval($cotizacion['aplica_iva'] ?? 1) === 1;
$fecha_emision = !empty($cotizacion['factura_fecha_emision']) ? date('d/m/Y', strtotime($cotizacion['factura_fecha_emision'])) : date('d/m/Y');
$tasa_formato = formatoFacturaFiscal($tasa);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?= $numero_factura_html ?><?= $es_anulada ? ' (ANULADA)' : '' ?> - Unifuerte</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { margin: 0; background: #525659; color: #111; font-family: Arial, sans-serif; }
        .top-bar { position: fixed; z-index: 5; inset: 0 0 auto; display: flex; justify-content: center; gap: 12px; padding: 12px; background: #13131A; }
        .top-bar button { padding: 10px 22px; border: 0; border-radius: 7px; color: #fff; font-weight: 700; cursor: pointer; }
        .btn-print { background: #0B4C8C; }
        .btn-download { background: #FF7518; }
        .preview { width: 100%; overflow-x: auto; padding: 76px 12px 32px; }
        #documento_pdf { position: relative; width: 794px; min-height: 1050px; margin: 0 auto; padding: 34px; background: #fff; box-shadow: 0 5px 15px rgba(0,0,0,.45); }
        .watermark-anulada { position: absolute; top: 45%; left: 50%; transform: translate(-50%, -50%) rotate(-35deg); font-size: 95px; font-weight: 900; color: rgba(220, 53, 69, 0.22); border: 10px solid rgba(220, 53, 69, 0.22); padding: 10px 40px; border-radius: 15px; pointer-events: none; z-index: 10; text-transform: uppercase; letter-spacing: 6px; }
        .banner-anulada { background: #f8d7da; color: #842029; border: 2px solid #dc3545; padding: 8px 12px; margin-bottom: 12px; font-size: 11px; font-weight: 700; text-align: center; border-radius: 4px; }
        .header { width: 100%; border: 1px solid #0B4C8C; border-collapse: collapse; margin-bottom: 12px; }
        .header td { padding: 7px; vertical-align: middle; }
        .logo { max-width: 190px; max-height: 74px; object-fit: contain; }
        .company { text-align: right; font-size: 9px; line-height: 1.35; }
        .company strong { font-size: 12px; }
        .invoice-meta, .client, .items, .totals { width: 100%; border-collapse: collapse; }
        .invoice-meta, .client { margin-bottom: 10px; border: 1px solid #0B4C8C; }
        .invoice-meta td, .client td { padding: 5px 7px; border: 1px solid #0B4C8C; font-size: 10px; }
        .invoice-title { font-size: 15px; font-weight: 800; text-align: center; color: #0B4C8C; }
        .invoice-number { color: #a40000; font-size: 13px !important; font-weight: 800; }
        .label { width: 16%; background: #eef4f9; font-weight: 700; }
        .value { font-weight: 700; text-transform: uppercase; }
        .items { border: 1px solid #0B4C8C; margin-top: 12px; }
        .items th, .items td { border: 1px solid #0B4C8C; padding: 5px; font-size: 9px; }
        .items th { background: #eaf1f7; text-align: center; }
        .items td.num { text-align: right; white-space: nowrap; }
        .items td.qty { text-align: center; }
        .description { width: 48%; }
        .totals-wrap { display: flex; justify-content: flex-end; margin-top: 220px; }
        .totals { width: 100%; border: 1px solid #0B4C8C; }
        .totals th, .totals td { padding: 4px 7px; border: 1px solid #0B4C8C; font-size: 9px; }
        .totals th { background: #eef4f9; text-align: center; }
        .totals td.money { text-align: right; white-space: nowrap; }
        .total-row { font-weight: 800; background: #eaf1f7; }
        .rate-note { margin-top: 8px; border: 1px solid #0B4C8C; padding: 7px; text-align: center; font-size: 8px; line-height: 1.4; }
        .notice { margin-top: 8px; text-align: center; color: #555; font-size: 8px; }
        @media print {
            @page { size: letter portrait; margin: 8mm; }
            body { background: #fff; }
            .top-bar { display: none; }
            .preview { padding: 0; overflow: visible; }
            #documento_pdf { width: 100%; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .totals-wrap { margin-top: 170px; }
        }
    </style>
</head>
<body>
    <div class="top-bar no-print">
        <button class="btn-print" id="btnImprimir" type="button">Imprimir</button>
        <button class="btn-download" id="btnDescargar" type="button">Descargar PDF</button>
    </div>

    <main class="preview">
        <div id="documento_pdf">
            <?php if ($es_anulada): ?>
                <div class="watermark-anulada">ANULADA</div>
                <div class="banner-anulada">
                    DOCUMENTO ANULADO SIN VALOR FISCAL<?= $motivo_anulacion !== '' ? ' — MOTIVO: ' . htmlspecialchars($motivo_anulacion, ENT_QUOTES, 'UTF-8') : '' ?>
                </div>
            <?php endif; ?>

            <table class="header">
                <tr>
                    <td style="width: 38%;"><img class="logo" src="../img/unifuerte.jpg" alt="Logo Unifuerte"></td>
                    <td class="company">
                        <strong>UNIFUERTE, C.A.</strong><br>
                        R.I.F: J-41282764-2 / R.U.C 1829-2021<br>
                        SENCAMER 0012216TN-21<br>
                        CR. UD-321, EDIFICIO PARCELA 321-16-10A-1<br>
                        PISO 1, OFICINAS A, B, C, ZONA INDUSTRIAL UD 321<br>
                        CIUDAD GUAYANA, PUERTO ORDAZ, ESTADO BOLÍVAR<br>
                        TELÉFONO: 04241546889<br>
                        EMAIL: CLIENTEUNIFUERTE@GMAIL.COM
                    </td>
                </tr>
            </table>

            <table class="invoice-meta">
                <tr>
                    <td class="invoice-title" rowspan="3">ORDEN DE FACTURACIÓN<?= $es_anulada ? '<br><span style="color:#dc3545;font-size:11px;">(ANULADA)</span>' : '' ?></td>
                    <td class="label">N° FACTURA</td>
                    <td class="invoice-number"><?= $numero_factura_html ?></td>
                    <td class="label">Fecha de emisión</td>
                    <td><?= $fecha_emision ?></td>
                </tr>
                <tr>
                    <td class="label">N° CONTROL</td>
                    <td class="invoice-number"><?= $numero_control_html ?></td>
                    <td class="label">Cotización ref.</td>
                    <td><?= htmlspecialchars($cotizacion['nro_cotizacion'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <td class="label">Tasa de cambio</td>
                    <td class="invoice-number" colspan="3">Bs <?= $tasa_formato ?> / USD</td>
                </tr>
            </table>

            <table class="client">
                <tr>
                    <td class="label">Cliente</td>
                    <td class="value" colspan="3"><?= htmlspecialchars($cotizacion['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <td class="label">RIF / C.I.</td>
                    <td class="value"><?= htmlspecialchars($cotizacion['cedula_Rif'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="label">Teléfono</td>
                    <td><?= htmlspecialchars($cotizacion['cliente_tlf'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <td class="label">Dirección</td>
                    <td class="value" colspan="3"><?= htmlspecialchars($cotizacion['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <td class="label">Contacto</td>
                    <td><?= htmlspecialchars($cotizacion['comprador'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="label">Asesor</td>
                    <td><?= htmlspecialchars(trim(($cotizacion['vendedor_nombre'] ?? '') . ' ' . ($cotizacion['vendedor_apellido'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            </table>

            <table class="items">
                <thead>
                    <tr>
                        <th style="width: 10%;">Código</th>
                        <th class="description">Descripción</th>
                        <th style="width: 10%;">Cantidad</th>
                        <th style="width: 16%;">Precio unitario Bs</th>
                        <th style="width: 16%;">Total Bs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $indice => $detalle):
                        $cantidad = (float)$detalle['cantidad'];
                        $precio = (float)$detalle['precio_unitario'];
                        $descripcion = trim((string)$detalle['tipo_prenda']);
                        if (!empty($detalle['genero']) && $detalle['genero'] !== 'No especificado') {
                            $descripcion .= ' / ' . $detalle['genero'];
                        }
                        $detalle_diseno = preg_replace('/\[Para:.*?\]\s*-\s*/', '', (string)($detalle['detalle_diseno'] ?? ''));
                    ?>
                    <tr>
                        <td class="qty">U<?= str_pad((string)($indice + 1), 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?><?php if ($detalle_diseno !== ''): ?><br><span style="font-size:8px;"><?= nl2br(htmlspecialchars($detalle_diseno, ENT_QUOTES, 'UTF-8')) ?></span><?php endif; ?></td>
                        <td class="qty"><?= formatoFacturaFiscal($cantidad) ?></td>
                        <td class="num">Bs <?= formatoFacturaFiscal($precio * $tasa) ?></td>
                        <td class="num">Bs <?= formatoFacturaFiscal($cantidad * $precio * $tasa) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$detalles): ?>
                    <tr><td colspan="5" style="text-align:center;">La cotización no tiene productos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="totals-wrap">
                <table class="totals">
                    <thead>
                        <tr><th>Concepto</th><th>USD</th><th>Bolívares (Bs)</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Sub-total</td><td class="money">$ <?= formatoFacturaFiscal($sub_total) ?></td><td class="money">Bs <?= formatoFacturaFiscal($sub_total * $tasa) ?></td></tr>
                        <?php if ($servicio_logistico > 0): ?><tr><td>Servicio logístico</td><td class="money">$ <?= formatoFacturaFiscal($servicio_logistico) ?></td><td class="money">Bs <?= formatoFacturaFiscal($servicio_logistico * $tasa) ?></td></tr><?php endif; ?>
                        <?php if ($flete > 0): ?><tr><td>Flete</td><td class="money">$ <?= formatoFacturaFiscal($flete) ?></td><td class="money">Bs <?= formatoFacturaFiscal($flete * $tasa) ?></td></tr><?php endif; ?>
                        <?php if ($descuento > 0): ?><tr><td>Descuento</td><td class="money">- $ <?= formatoFacturaFiscal($descuento) ?></td><td class="money">- Bs <?= formatoFacturaFiscal($descuento * $tasa) ?></td></tr><?php endif; ?>
                        <tr><td>Base imponible</td><td class="money">$ <?= formatoFacturaFiscal($base_imponible) ?></td><td class="money">Bs <?= formatoFacturaFiscal($base_imponible * $tasa) ?></td></tr>
                        <?php if ($aplica_iva && $iva > 0): ?><tr><td>I.V.A.</td><td class="money">$ <?= formatoFacturaFiscal($iva) ?></td><td class="money">Bs <?= formatoFacturaFiscal($iva * $tasa) ?></td></tr><?php endif; ?>
                        <?php if ($igtf > 0): ?><tr><td>I.G.T.F.</td><td class="money">$ <?= formatoFacturaFiscal($igtf) ?></td><td class="money">Bs <?= formatoFacturaFiscal($igtf * $tasa) ?></td></tr><?php endif; ?>
                        <tr class="total-row"><td>Total de operación</td><td class="money">$ <?= formatoFacturaFiscal($total) ?></td><td class="money">Bs <?= formatoFacturaFiscal($total * $tasa) ?></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="rate-note">
                Los importes en bolívares se calcularon utilizando la tasa BCV del día del pago indicada en este documento:
                <strong>Bs <?= $tasa_formato ?> por USD</strong>.
            </div>
            <div class="notice"><strong>SU FACTURA FÍSICA ORIGINAL LLEGARÁ CON SU PEDIDO</strong></div>
        </div>
    </main>

    <script>
        function abrirVistaImpresion() {
            window.print();
        }

        function descargarPDF() {
            const boton = document.getElementById('btnDescargar');
            boton.disabled = true;
            boton.textContent = 'Generando PDF...';

            if (typeof html2pdf === 'function') {
                html2pdf().set({
                    margin: 8,
                    filename: 'Factura_Unifuerte_<?= rawurlencode($numero_factura) ?><?= $es_anulada ? "_ANULADA" : "" ?>.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true },
                    jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' }
                }).from(document.getElementById('documento_pdf')).save().then(function() {
                    boton.disabled = false;
                    boton.textContent = 'Descargar PDF';
                }).catch(function() {
                    boton.disabled = false;
                    boton.textContent = 'Descargar PDF';
                    abrirVistaImpresion();
                });
                return;
            }

            boton.disabled = false;
            boton.textContent = 'Descargar PDF';
            abrirVistaImpresion();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const btnImprimir = document.getElementById('btnImprimir');
            const btnDescargar = document.getElementById('btnDescargar');

            if (btnImprimir) {
                btnImprimir.addEventListener('click', abrirVistaImpresion);
            }
            if (btnDescargar) {
                btnDescargar.addEventListener('click', descargarPDF);
            }
        });
    </script>
</body>
</html>