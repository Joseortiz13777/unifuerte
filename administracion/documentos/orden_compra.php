<?php
session_start();
require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
    http_response_code(401);
    exit('Debes iniciar sesión para emitir esta orden.');
}

$rol = strtolower((string)$_SESSION['rol']);
if (!in_array($rol, ['master', 'admin'], true)) {
    http_response_code(403);
    exit('No tienes permiso para emitir órdenes de compra.');
}

$idGasto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idGasto === false || $idGasto === null) {
    http_response_code(400);
    exit('El registro indicado no es válido.');
}

$tipo = $_GET['tipo'] ?? '';
if (!in_array($tipo, ['gasto', 'compra'], true)) {
    http_response_code(400);
    exit('El tipo de orden indicado no es válido.');
}

if ($tipo === 'compra') {
    $sql = 'SELECT c.ID AS id, c.fecha_compra AS fecha, c.nro_factura AS factura,
                   c.concepto_pdf AS descripcion, c.costo AS monto_total, c.moneda,
                   p.nombre AS proveedor_nombre, p.RIF_cedula AS proveedor_rif,
                   p.tlf AS proveedor_telefono, p.ubicacion AS proveedor_direccion
            FROM compra c
            LEFT JOIN provedor p ON p.ID = c.id_provedor
            WHERE c.ID = ?
            LIMIT 1';
} else {
    $sql = 'SELECT g.id, g.fecha, g.factura, g.descripcion, g.monto_total, g.moneda,
                   p.nombre AS proveedor_nombre, p.RIF_cedula AS proveedor_rif,
                   p.tlf AS proveedor_telefono, p.ubicacion AS proveedor_direccion
            FROM gastos_servicios g
            LEFT JOIN provedor p ON p.ID = g.id_proveedor
            WHERE g.id = ?
            LIMIT 1';
}

$consulta = mysqli_prepare(
    $conexion,
    $sql
);
if (!$consulta) {
    error_log('Orden de compra: ' . mysqli_error($conexion));
    http_response_code(500);
    exit('No se pudo consultar la orden de compra.');
}

mysqli_stmt_bind_param($consulta, 'i', $idGasto);
if (!mysqli_stmt_execute($consulta)) {
    error_log('Orden de compra: ' . mysqli_stmt_error($consulta));
    http_response_code(500);
    exit('No se pudo consultar la orden de compra.');
}

$resultado = mysqli_stmt_get_result($consulta);
if (!$resultado) {
    error_log('Orden de compra: ' . mysqli_stmt_error($consulta));
    mysqli_stmt_close($consulta);
    http_response_code(500);
    exit('No se pudo leer la orden de compra.');
}
$gasto = $resultado ? mysqli_fetch_assoc($resultado) : null;
mysqli_stmt_close($consulta);
if (!$gasto) {
    http_response_code(404);
    exit('No se encontró el registro solicitado.');
}

function escaparOrdenCompra($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function dineroOrdenCompra($valor): string
{
    return number_format((float)$valor, 2, ',', '.');
}

$lineas = [];
$subtotal = 0.0;
if ($tipo === 'compra') {
    $consultaDetalle = mysqli_prepare(
        $conexion,
        'SELECT m.nombre AS descripcion, d.cantidad, d.precio_unitario,
                d.descuento_porc, d.costo_neto
         FROM detalle_compra d
         LEFT JOIN materia_prima m ON m.ID = d.id_materia
         WHERE d.id_compra = ?
         ORDER BY d.ID'
    );
    if (!$consultaDetalle) {
        error_log('Detalle de orden de compra: ' . mysqli_error($conexion));
        http_response_code(500);
        exit('No se pudieron consultar los productos de la orden.');
    }
    mysqli_stmt_bind_param($consultaDetalle, 'i', $idGasto);
    if (!mysqli_stmt_execute($consultaDetalle)) {
        error_log('Detalle de orden de compra: ' . mysqli_stmt_error($consultaDetalle));
        http_response_code(500);
        exit('No se pudieron consultar los productos de la orden.');
    }
    $resultadoDetalle = mysqli_stmt_get_result($consultaDetalle);
    if (!$resultadoDetalle) {
        error_log('Detalle de orden de compra: ' . mysqli_stmt_error($consultaDetalle));
        mysqli_stmt_close($consultaDetalle);
        http_response_code(500);
        exit('No se pudieron leer los productos de la orden.');
    }
    while ($item = mysqli_fetch_assoc($resultadoDetalle)) {
        $cantidad = (float)$item['cantidad'];
        $precioBase = (float)$item['precio_unitario'];
        $descuento = (float)$item['descuento_porc'];
        $precioUnitario = (float)$item['costo_neto'];
        if ($precioUnitario <= 0) {
            $precioUnitario = $precioBase * (1 - ($descuento / 100));
        }
        $totalLinea = $cantidad * $precioUnitario;
        $subtotal += $totalLinea;
        $lineas[] = [
            'descripcion' => $item['descripcion'] ?: 'Producto',
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'total' => $totalLinea,
        ];
    }
    mysqli_stmt_close($consultaDetalle);
} else {
    $items = json_decode((string)$gasto['descripcion'], true);
    if (!is_array($items)) {
        $items = [[
            'descripcion' => $gasto['descripcion'] ?: 'Concepto del gasto',
            'cantidad' => 1,
            'costo' => (float)$gasto['monto_total'],
        ]];
    }

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $cantidad = isset($item['cantidad']) && is_numeric($item['cantidad']) && (float)$item['cantidad'] > 0
            ? (float)$item['cantidad']
            : 1.0;
        $descripcion = $item['descripcion'] ?? $item['desc'] ?? $item['servicio'] ?? $item['concepto'] ?? 'Concepto';

        if (isset($item['precio_unitario']) && is_numeric($item['precio_unitario'])) {
            $precioUnitario = (float)$item['precio_unitario'];
        } else {
            $precioUnitario = (float)($item['costo'] ?? $item['monto'] ?? 0);
        }

        $totalLinea = $cantidad * $precioUnitario;
        $subtotal += $totalLinea;
        $lineas[] = [
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'total' => $totalLinea,
        ];
    }
}

if (!$lineas) {
    $descripcionAlternativa = $tipo === 'compra' ? 'Productos de la compra' : 'Concepto del gasto';
    $lineas[] = [
        'descripcion' => $gasto['descripcion'] ?: $descripcionAlternativa,
        'cantidad' => 1,
        'precio_unitario' => (float)$gasto['monto_total'],
        'total' => (float)$gasto['monto_total'],
    ];
    $subtotal = (float)$gasto['monto_total'];
}

$moneda = strtoupper((string)($gasto['moneda'] ?? 'USD')) === 'BS' ? 'Bs' : '$';
$numeroOrden = ($tipo === 'compra' ? 'OC-CMP-' : 'OC-GAS-') . str_pad((string)$idGasto, 6, '0', STR_PAD_LEFT);
$fechaOrden = !empty($gasto['fecha']) ? date('d/m/Y', strtotime($gasto['fecha'])) : date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escaparOrdenCompra($numeroOrden) ?> - Unifuerte</title>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body { margin: 0; padding: 24px; background: #e8edf2; color: #17212b; font-family: Arial, sans-serif; }
        .toolbar { display: flex; justify-content: center; align-items: end; flex-wrap: wrap; gap: 12px; margin: 0 auto 18px; }
        .toolbar label { display: grid; gap: 4px; font-size: 12px; font-weight: 700; }
        .toolbar input { width: 155px; padding: 8px; border: 1px solid #aeb8c2; border-radius: 4px; }
        .toolbar button { border: 0; border-radius: 4px; padding: 10px 16px; color: #fff; background: #0b4c8c; font-weight: 700; cursor: pointer; }
        .document { width: 8.5in; min-height: 11in; margin: auto; padding: 16mm 15mm; background: #fff; box-shadow: 0 3px 16px #0002; }

        /* ENCABEZADO IDÉNTICO AL DE LA COTIZACIÓN */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .logo-img { max-height: 70px; }
        .company-info { text-align: right; font-weight: bold; font-size: 10px; line-height: 1.25; color: #000; }

        .title-box { width: 100%; border: 2px solid #000; border-collapse: collapse; text-align: center; margin-bottom: 14px; }
        .title-box td { padding: 6px 10px; border: 2px solid #000; font-size: 16px; font-weight: bold; text-transform: uppercase; background-color: #fff; vertical-align: middle; color: #000; }
        .title-box td.nro-box { width: 220px; padding: 6px 14px; font-size: 12px; white-space: nowrap; }

        .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 16px 0 20px; }
        .party { min-height: 84px; padding: 12px; border: 1px solid #aab6c2; }
        .party h3 { margin: 0 0 8px; color: #0b4c8c; font-size: 11px; text-transform: uppercase; }
        .party p { margin: 4px 0; font-size: 11px; }
        .items { width: 100%; border-collapse: collapse; }
        .items th, .items td { border: 1px solid #8c9aa8; padding: 8px; font-size: 10px; vertical-align: top; }
        .items thead th { background: #0b4c8c; color: #fff; text-align: center; }
        .items .number { text-align: right; white-space: nowrap; }
        .summary { width: 48%; margin: 16px 0 0 auto; border-collapse: collapse; }
        .summary th, .summary td { border: 1px solid #8c9aa8; padding: 7px 9px; font-size: 10px; }
        .summary th { background: #eef3f7; text-align: left; }
        .summary td { text-align: right; font-weight: 700; white-space: nowrap; }
        .summary .grand-total th, .summary .grand-total td { background: #0b4c8c; color: #fff; font-size: 12px; }
        .notice { margin-top: 22px; padding: 12px; border: 1px solid #d09b36; background: #fff8e8; color: #5c4212; text-align: center; font-size: 12px; font-weight: 700; line-height: 1.45; }
        .notice .fiscal-details { display: block; margin-top: 4px; font-size: 10.5px; font-weight: 700; color: #3d2b0b; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; margin-top: 62px; text-align: center; font-size: 10px; }
        .signature { min-height: 88px; display: flex; flex-direction: column; justify-content: end; }
        .signature img { display: block; width: 150px; height: 52px; object-fit: contain; margin: 0 auto -4px; }
        .signature-line { border-top: 1px solid #586575; padding-top: 7px; }
        .signature-line strong { display: block; margin-top: 3px; }
        @media print {
            @page { size: letter portrait; margin: 0; }
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
            .document { width: 8.5in; min-height: 11in; margin: 0; padding: 14mm 15mm; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <label>Envío y gestión (<?= escaparOrdenCompra($moneda) ?>)
            <input id="envio_gestion" type="number" min="0" step="0.01" value="0">
        </label>
        <label>ISLR retenido (<?= escaparOrdenCompra($moneda) ?>)
            <input id="islr" type="number" min="0" step="0.01" value="0">
        </label>
        <button type="button" onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>

    <main class="document">
        <!-- ENCABEZADO IDÉNTICO AL DE LA COTIZACIÓN -->
        <table class="header-table">
            <tr>
                <td style="width: 40%; vertical-align: top;">
                    <img src="../../img/unifuerte.jpg" class="logo-img" alt="Unifuerte Logo">
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
                <td>ORDEN DE COMPRA</td>
                <td class="nro-box">
                    NRO. <span style="font-size: 14px;"><?= escaparOrdenCompra($numeroOrden) ?></span><br>
                    <span style="font-size: 11px; font-weight: bold; display: inline-block; margin-top: 2px;">FECHA: <?= escaparOrdenCompra($fechaOrden) ?></span>
                </td>
            </tr>
        </table>

        <section class="parties">
            <div class="party">
                <h3>Solicitado por</h3>
                <p><strong>UNIFUERTE, C.A.</strong></p>
                <p>R.I.F.: J-41282764-2</p>
                <p>Referencia: <?= escaparOrdenCompra($gasto['factura'] ?: 'S/N') ?></p>
            </div>
            <div class="party">
                <h3>Proveedor</h3>
                <p><strong><?= escaparOrdenCompra($gasto['proveedor_nombre'] ?: 'Proveedor no disponible') ?></strong></p>
                <p>RIF / C.I.: <?= escaparOrdenCompra($gasto['proveedor_rif'] ?: 'N/D') ?></p>
                <p>Teléfono: <?= escaparOrdenCompra($gasto['proveedor_telefono'] ?: 'N/D') ?></p>
            </div>
        </section>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 12%;">Cantidad solicitada</th>
                    <th>Descripción</th>
                    <th style="width: 20%;">Precio unitario</th>
                    <th style="width: 20%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lineas as $linea): ?>
                <tr>
                    <td class="number"><?= escaparOrdenCompra(rtrim(rtrim(number_format($linea['cantidad'], 2, '.', ''), '0'), '.')) ?></td>
                    <td><?= nl2br(escaparOrdenCompra($linea['descripcion'])) ?></td>
                    <td class="number"><?= escaparOrdenCompra($moneda) ?> <?= dineroOrdenCompra($linea['precio_unitario']) ?></td>
                    <td class="number"><?= escaparOrdenCompra($moneda) ?> <?= dineroOrdenCompra($linea['total']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="summary">
            <tr><th>Subtotal</th><td><?= escaparOrdenCompra($moneda) ?> <span id="subtotal"><?= dineroOrdenCompra($subtotal) ?></span></td></tr>
            <tr><th>Envío y gestión</th><td><?= escaparOrdenCompra($moneda) ?> <span id="envio-gestion-total">0,00</span></td></tr>
            <tr><th>Gravable</th><td><?= escaparOrdenCompra($moneda) ?> <span id="gravable"><?= dineroOrdenCompra($subtotal) ?></span></td></tr>
            <tr><th>IVA (16 %)</th><td><?= escaparOrdenCompra($moneda) ?> <span id="iva"><?= dineroOrdenCompra($subtotal * 0.16) ?></span></td></tr>
            <tr><th>ISLR retenido</th><td>- <?= escaparOrdenCompra($moneda) ?> <span id="islr-total">0,00</span></td></tr>
            <tr class="grand-total"><th>TOTAL A PAGAR</th><td><?= escaparOrdenCompra($moneda) ?> <span id="total-pagar"><?= dineroOrdenCompra($subtotal * 1.16) ?></span></td></tr>
        </table>

        <div class="notice">
            EMITIR FACTURA A NOMBRE DE UNIFUERTE, C.A.
            <span class="fiscal-details">
                R.I.F: J-41282764-2<br>
                DIRECCIÓN FISCAL: CR. UD-321, EDIFICIO PARCELA 321-16-10A-1, PISO 1, OFICINA A, B, C, ZONA INDUSTRIAL UD 321, CIUDAD GUAYANA, PUERTO ORDAZ, ESTADO BOLÍVAR
            </span>
        </div>

        <section class="signatures">
            <div class="signature">
                <img src="../img/firma.jpeg" alt="Firma de Unifuerte">
                <div class="signature-line">Firma y sello de<strong>UNIFUERTE, C.A.</strong></div>
            </div>
            <div class="signature">
                <div class="signature-line">Firma y sello del proveedor<strong><?= escaparOrdenCompra($gasto['proveedor_nombre'] ?: 'PROVEEDOR') ?></strong></div>
            </div>
        </section>
    </main>

    <script>
        const subtotal = <?= json_encode(round($subtotal, 2)) ?>;
        const campoEnvioGestion = document.getElementById('envio_gestion');
        const campoIslr = document.getElementById('islr');
        const formatoDinero = valor => Number(valor).toLocaleString('es-VE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        function actualizarTotales() {
            const envioGestion = Math.max(0, Number(campoEnvioGestion.value) || 0);
            const islr = Math.max(0, Number(campoIslr.value) || 0);
            const gravable = subtotal + envioGestion;
            const iva = gravable * 0.16;

            document.getElementById('envio-gestion-total').textContent = formatoDinero(envioGestion);
            document.getElementById('gravable').textContent = formatoDinero(gravable);
            document.getElementById('iva').textContent = formatoDinero(iva);
            document.getElementById('islr-total').textContent = formatoDinero(islr);
            document.getElementById('total-pagar').textContent = formatoDinero(gravable + iva - islr);
        }

        [campoEnvioGestion, campoIslr].forEach(campo => campo.addEventListener('input', actualizarTotales));
    </script>
</body>
</html>