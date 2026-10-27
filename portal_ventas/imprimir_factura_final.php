<?php
session_start();
require_once '../conexion.php';

$idCotizacion = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$numFacEspecifica = intval($_GET['factura'] ?? 0);

if ($idCotizacion === false) {
    http_response_code(400);
    exit('La cotización indicada no es válida.');
}
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit('Debes iniciar sesión para consultar esta factura.');
}

$idUsuario = (int)$_SESSION['usuario_id'];
$qPermisos = mysqli_query($conexion, "SELECT permisos FROM usuario WHERE ID = $idUsuario LIMIT 1");
$datosPermisos = $qPermisos ? mysqli_fetch_assoc($qPermisos) : null;
$permisos = array_map('trim', explode(',', (string)($datosPermisos['permisos'] ?? '')));
$puedeVerTodas = in_array('7', $permisos, true);
if (!in_array('6', $permisos, true) && !$puedeVerTodas) {
    http_response_code(403);
    exit('No tienes permiso para consultar cotizaciones.');
}

$condicionFactura = $numFacEspecifica > 0
    ? "AND f.numero_factura = $numFacEspecifica"
    : "AND (f.estado IS NULL OR f.estado = 'Activa')";

$resultado = mysqli_query($conexion, "
    SELECT c.*, cli.nombre AS cliente_nombre, cli.cedula_Rif, cli.direccion, cli.comprador,
           cli.email, cli.tlf AS cliente_tlf,
           u.nombre AS vendedor_nombre, u.apellido AS vendedor_apellido,
           f.numero_factura, f.numero_control, f.tasa_bcv, f.numero_orden_compra,
           f.fecha_emision AS factura_fecha, f.estado AS factura_estado,
           f.motivo_anulacion AS factura_motivo_anulacion
    FROM cotizacion c
    JOIN cliente cli ON cli.ID = c.id_cliente
    LEFT JOIN usuario u ON u.ID = c.id_vendedor
    LEFT JOIN factura_fiscal_cotizacion f ON f.id_cotizacion = c.ID $condicionFactura
    WHERE c.ID = $idCotizacion
    ORDER BY f.numero_factura DESC
    LIMIT 1
");
if (!$resultado) {
    error_log('Factura final: no se pudo cargar la cotización: ' . mysqli_error($conexion));
    http_response_code(500);
    exit('No se pudo cargar la factura fiscal final.');
}
$cotizacion = mysqli_fetch_assoc($resultado);
if (!$cotizacion) {
    http_response_code(404);
    exit('No se encontró la cotización seleccionada.');
}
if (intval($cotizacion['numero_factura'] ?? 0) <= 0) {
    http_response_code(404);
    exit('Emite la factura fiscal activa antes de imprimir la factura final.');
}
if (($cotizacion['tipo_cotizacion'] ?? '') === 'MUESTRA') {
    http_response_code(400);
    exit('Las muestras físicas no admiten factura fiscal.');
}
if (!$puedeVerTodas && intval($cotizacion['id_vendedor']) !== $idUsuario) {
    http_response_code(403);
    exit('No tienes permiso para consultar esta factura.');
}

$numeroFactura = str_pad((string)$cotizacion['numero_factura'], 8, '0', STR_PAD_LEFT);
$ordenCompra = trim((string)($cotizacion['numero_orden_compra'] ?? '')) ?: 'S/N';
$tasa = (float)$cotizacion['tasa_bcv'];
$esAnulada = strtoupper(trim((string)($cotizacion['factura_estado'] ?? 'Activa'))) === 'ANULADA';
$motivoAnulacion = trim((string)($cotizacion['factura_motivo_anulacion'] ?? ''));

if (!is_finite($tasa) || $tasa <= 0) {
    http_response_code(500);
    exit('La factura fiscal no tiene una tasa BCV válida registrada.');
}
$detallesResult = mysqli_query($conexion, "
    SELECT d.*, p.etiqueta AS codigo_producto
    FROM detalle_cotizacion d
    LEFT JOIN productos p ON p.id = d.id_producto
    WHERE d.id_cotizacion = $idCotizacion
    ORDER BY d.ID
");
if (!$detallesResult) {
    error_log('Factura final: no se pudieron consultar los detalles: ' . mysqli_error($conexion));
    http_response_code(500);
    exit('No se pudieron cargar los productos de la factura.');
}
$detalles = [];
while ($detalle = mysqli_fetch_assoc($detallesResult)) {
    $detalles[] = $detalle;
}

function htmlFacturaFinal($valor): string {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function dineroFacturaFinal($valor): string {
    return number_format((float)$valor, 2, ',', '.');
}

$subtotal = (float)$cotizacion['sub_total'];
$descuento = (float)$cotizacion['descuento'];
$servicio = (float)($cotizacion['servicio_logistico'] ?? 0);
$flete = (float)($cotizacion['flete'] ?? 0);
$baseImponible = max(0, $subtotal + $servicio + $flete - $descuento);
$iva = (float)$cotizacion['iva'];
$igtf = (float)$cotizacion['igtf'];
$total = (float)$cotizacion['total'];
$baseExenta = intval($cotizacion['aplica_iva'] ?? 1) === 1 ? 0 : $baseImponible;
$baseGravable = intval($cotizacion['aplica_iva'] ?? 1) === 1 ? $baseImponible : 0;
$fechaEmision = !empty($cotizacion['factura_fecha'])
    ? date('d/m/Y', strtotime($cotizacion['factura_fecha']))
    : date('d/m/Y');

$totalBs = $total * $tasa;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura fiscal final <?= htmlFacturaFinal($numeroFactura) ?><?= $esAnulada ? ' (ANULADA)' : '' ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { margin: 0; background: #525659; color: #111; font-family: Arial, sans-serif; }
        .toolbar { position: fixed; z-index: 5; inset: 0 0 auto; display: flex; justify-content: center; gap: 10px; padding: 12px; background: #13131a; }
        .toolbar button { border: 0; border-radius: 6px; padding: 10px 18px; color: #fff; font-weight: 700; cursor: pointer; }
        .print-button { background: #0b4c8c; }
        .pdf-button { background: #ff7518; }
        .preview { padding: 70px 12px 24px; }
        .page { position: relative; display: flex; flex-direction: column; width: 8.5in; height: 11in; margin: 0 auto; padding: 48mm 14mm 17mm; background: #fff; box-shadow: 0 4px 18px #2228; }
        .watermark-anulada { position: absolute; top: 48%; left: 50%; transform: translate(-50%, -50%) rotate(-35deg); font-size: 90px; font-weight: 900; color: rgba(220, 53, 69, 0.22); border: 10px solid rgba(220, 53, 69, 0.22); padding: 10px 35px; border-radius: 15px; pointer-events: none; z-index: 10; text-transform: uppercase; letter-spacing: 5px; }
        .banner-anulada { background: #f8d7da; color: #842029; border: 1px solid #dc3545; padding: 5px 8px; margin-bottom: 7px; font-size: 9px; font-weight: 700; text-align: center; }
        .invoice-heading { width: 100%; border: 1px solid #244b69; border-collapse: collapse; margin-bottom: 9px; }
        .invoice-heading td { border: 1px solid #244b69; padding: 7px 8px; font-size: 9px; }
        .invoice-heading .title { text-align: center; font-size: 14px; font-weight: 800; }
        .invoice-heading .number { font-weight: 800; color: #9b111e; }
        .client, .items, .totals { width: 100%; border-collapse: collapse; }
        .client { margin-bottom: 9px; }
        .client td { border: 1px solid #244b69; padding: 5px 6px; font-size: 9px; line-height: 1.25; }
        .label { width: 14%; font-weight: 700; }
        .client .value { text-transform: uppercase; }
        .items-container { flex: 1; min-height: 25mm; display: flex; align-items: flex-start; }
        .items th, .items td { border: 1px solid #244b69; padding: 5px; font-size: 8px; vertical-align: top; }
        .items th { background: #eaf1f7; text-align: center; }
        .items td.money { text-align: right; white-space: nowrap; }
        .items td.qty { text-align: center; }
        .totals-wrap { padding-top: 15px; }
        .totals { width: 100%; border-collapse: collapse; }
        .totals th, .totals td { border: 1px solid #244b69; padding: 4px 6px; font-size: 8px; }
        .totals th { background: #eaf1f7; text-align: center; }
        .totals td.money { text-align: right; white-space: nowrap; }
        .grand-total { font-weight: 800; background: #eaf1f7; }
        .note { border: 1px solid #244b69; margin-top: 7px; padding: 7px 9px; font-size: 8px; text-align: justify; line-height: 1.35; font-weight: 600; }
        .blank-after { height: 14mm; flex-shrink: 0; }
        @media print {
            @page { size: letter portrait; margin: 0; }
            body { background: #fff; }
            .toolbar { display: none; }
            .preview { padding: 0; }
            .page { width: 8.5in; height: 11in; margin: 0; padding: 48mm 14mm 17mm; box-shadow: none; display: flex; flex-direction: column; }
            tr, .totals-wrap, .note { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="print-button" type="button" onclick="window.print()">Imprimir factura final</button>
        <button class="pdf-button" id="downloadPdf" type="button">Descargar PDF</button>
    </div>
    <main class="preview">
        <section class="page" id="documento_pdf">
            <?php if ($esAnulada): ?>
                <div class="watermark-anulada">ANULADA</div>
                <div class="banner-anulada">
                    FACTURA ANULADA<?= $motivoAnulacion !== '' ? ' — MOTIVO: ' . htmlFacturaFinal($motivoAnulacion) : '' ?>
                </div>
            <?php endif; ?>

            <table class="invoice-heading">
                <tr>
                    <td rowspan="2" class="title" style="width:42%;">
                        FACTURA<?= $esAnulada ? ' (ANULADA)' : '' ?><br>
                        <span class="number">N.º <?= htmlFacturaFinal($numeroFactura) ?></span>
                    </td>
                    <td class="label">Fecha de emisión</td>
                    <td><?= htmlFacturaFinal($fechaEmision) ?></td>
                    <td class="label">Condición</td>
                    <td>Contado</td>
                </tr>
                <tr>
                    <td class="label">Vence</td>
                    <td><?= htmlFacturaFinal($fechaEmision) ?></td>
                    <td class="label">Orden de compra</td>
                    <td class="number"><?= htmlFacturaFinal($ordenCompra) ?></td>
                </tr>
            </table>

            <table class="client">
                <tr>
                    <td class="label">Cliente</td>
                    <td class="value" colspan="3"><?= htmlFacturaFinal($cotizacion['cliente_nombre']) ?></td>
                    <td class="label">Cotización</td>
                    <td class="value"><?= htmlFacturaFinal($cotizacion['nro_cotizacion']) ?></td>
                </tr>
                <tr>
                    <td class="label">RIF / C.I.</td>
                    <td class="value"><?= htmlFacturaFinal($cotizacion['cedula_Rif']) ?></td>
                    <td class="label">Teléfono</td>
                    <td><?= htmlFacturaFinal($cotizacion['cliente_tlf']) ?></td>
                    <td class="label">Asesor</td>
                    <td><?= htmlFacturaFinal(trim(($cotizacion['vendedor_nombre'] ?? '') . ' ' . ($cotizacion['vendedor_apellido'] ?? ''))) ?></td>
                </tr>
                <tr>
                    <td class="label">Dirección</td>
                    <td class="value" colspan="5"><?= htmlFacturaFinal($cotizacion['direccion']) ?></td>
                </tr>
                <tr>
                    <td class="label">Comprador</td>
                    <td><?= htmlFacturaFinal($cotizacion['comprador']) ?></td>
                    <td class="label">Correo</td>
                    <td colspan="3"><?= htmlFacturaFinal($cotizacion['email']) ?></td>
                </tr>
            </table>

            <div class="items-container">
            <table class="items">
                <thead>
                    <tr>
                        <th style="width:12%;">Código</th>
                        <th style="width:52%;">Descripción</th>
                        <th style="width:10%;">Cantidad</th>
                        <th style="width:13%;">Precio unitario Bs</th>
                        <th style="width:13%;">Total Bs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $indice => $detalle):
                        $cantidad = (float)$detalle['cantidad'];
                        $precio = (float)$detalle['precio_unitario'];
                        $totalLinea = $cantidad * $precio;
                        $codigo = trim((string)($detalle['codigo_producto'] ?? ''));
                        if ($codigo === '') {
                            $codigo = 'U' . str_pad((string)($detalle['id_producto'] ?? ($indice + 1)), 4, '0', STR_PAD_LEFT);
                        }
                        $descripcion = trim((string)$detalle['tipo_prenda']);
                        if (!empty($detalle['genero']) && $detalle['genero'] !== 'No especificado') {
                            $descripcion .= ' / ' . $detalle['genero'];
                        }
                        $detalleDiseno = preg_replace('/\[Para:.*?\]\s*-\s*/', '', (string)($detalle['detalle_diseno'] ?? ''));
                    ?>
                    <tr>
                        <td class="qty"><?= htmlFacturaFinal($codigo) ?></td>
                        <td><?= htmlFacturaFinal($descripcion) ?><?php if ($detalleDiseno !== ''): ?><br><?= nl2br(htmlFacturaFinal($detalleDiseno)) ?><?php endif; ?></td>
                        <td class="qty"><?= dineroFacturaFinal($cantidad) ?></td>
                        <td class="money">Bs <?= dineroFacturaFinal($precio * $tasa) ?></td>
                        <td class="money">Bs <?= dineroFacturaFinal($totalLinea * $tasa) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$detalles): ?>
                    <tr><td colspan="5" style="text-align:center;">La cotización no tiene productos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <div class="totals-wrap">
                <table class="totals">
                    <thead><tr><th style="width: 50%;">Concepto</th><th style="width: 25%;">USD</th><th style="width: 25%;">Bolívares (Bs)</th></tr></thead>
                    <tbody>
                        <tr><td>Sub-total</td><td class="money">$ <?= dineroFacturaFinal($subtotal) ?></td><td class="money">Bs <?= dineroFacturaFinal($subtotal * $tasa) ?></td></tr>
                        <?php if ($servicio > 0): ?><tr><td>Servicio logístico</td><td class="money">$ <?= dineroFacturaFinal($servicio) ?></td><td class="money">Bs <?= dineroFacturaFinal($servicio * $tasa) ?></td></tr><?php endif; ?>
                        <?php if ($flete > 0): ?><tr><td>Flete</td><td class="money">$ <?= dineroFacturaFinal($flete) ?></td><td class="money">Bs <?= dineroFacturaFinal($flete * $tasa) ?></td></tr><?php endif; ?>
                        <?php if ($descuento > 0): ?><tr><td>Descuento</td><td class="money">- $ <?= dineroFacturaFinal($descuento) ?></td><td class="money">- Bs <?= dineroFacturaFinal($descuento * $tasa) ?></td></tr><?php endif; ?>
                        <?php if ($baseExenta > 0): ?><tr><td>Base exenta</td><td class="money">$ <?= dineroFacturaFinal($baseExenta) ?></td><td class="money">Bs <?= dineroFacturaFinal($baseExenta * $tasa) ?></td></tr><?php endif; ?>
                        <?php if ($baseGravable > 0): ?><tr><td>Base imponible</td><td class="money">$ <?= dineroFacturaFinal($baseGravable) ?></td><td class="money">Bs <?= dineroFacturaFinal($baseGravable * $tasa) ?></td></tr><?php endif; ?>
                        <?php if ($iva > 0): ?><tr><td>I.V.A. (16%)</td><td class="money">$ <?= dineroFacturaFinal($iva) ?></td><td class="money">Bs <?= dineroFacturaFinal($iva * $tasa) ?></td></tr><?php endif; ?>
                        <?php if ($igtf > 0): ?><tr><td>I.G.T.F.</td><td class="money">$ <?= dineroFacturaFinal($igtf) ?></td><td class="money">Bs <?= dineroFacturaFinal($igtf * $tasa) ?></td></tr><?php endif; ?>
                        <tr class="grand-total"><td>Total de operación</td><td class="money">$ <?= dineroFacturaFinal($total) ?></td><td class="money">Bs <?= dineroFacturaFinal($totalBs) ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="note">A efecto de lo dispuesto en el artículo 25 de la Ley de Impuestos al Valor Agregado, se expresan los montos en Bolívares (Bs), tomando como referencia la Tasa del BCV a la hora de la emisión. Tasa de Cambio: <?= dineroFacturaFinal($tasa) ?> Bs/USD. Total Operación: $<?= dineroFacturaFinal($total) ?> (Bs <?= dineroFacturaFinal($totalBs) ?>).</div>
            <div class="blank-after"></div>
        </section>
    </main>
    <script>
        document.getElementById('downloadPdf').addEventListener('click', function() {
            const button = this;
            button.disabled = true;
            button.textContent = 'Generando PDF...';
            if (typeof html2pdf !== 'function') {
                button.disabled = false;
                button.textContent = 'Descargar PDF';
                window.print();
                return;
            }
            html2pdf().set({
                margin: 0,
                filename: 'Factura_Fiscal_Final_<?= rawurlencode($numeroFactura) ?><?= $esAnulada ? "_ANULADA" : "" ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            }).from(document.getElementById('documento_pdf')).save().then(function() {
                button.disabled = false;
                button.textContent = 'Descargar PDF';
            }).catch(function(error) {
                console.error('No se pudo descargar la factura final como PDF.', error);
                button.disabled = false;
                button.textContent = 'Descargar PDF';
                window.print();
            });
        });
    </script>
</body>
</html>