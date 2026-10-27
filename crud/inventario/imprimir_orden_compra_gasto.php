<?php
session_start();
require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit('Debes iniciar sesión para emitir esta orden.');
}

$idUsuario = intval($_SESSION['usuario_id']);
$qPermisos = mysqli_query($conexion, "SELECT permisos FROM usuario WHERE ID = $idUsuario LIMIT 1");
$datosPermisos = $qPermisos ? mysqli_fetch_assoc($qPermisos) : null;
$permisos = array_map('trim', explode(',', (string)($datosPermisos['permisos'] ?? '')));
if (!in_array('21', $permisos, true)) {
    http_response_code(403);
    exit('No tienes permiso para emitir órdenes desde inventario.');
}

$idGasto = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idGasto === false) {
    http_response_code(400);
    exit('El gasto indicado no es válido.');
}

$resultado = mysqli_query($conexion, "
    SELECT g.*, p.nombre AS proveedor_nombre, p.RIF_cedula AS proveedor_rif,
           p.contacto AS proveedor_contacto, p.tlf AS proveedor_telefono,
           p.ubicacion AS proveedor_direccion
    FROM gastos_servicios g
    LEFT JOIN provedor p ON p.ID = g.id_proveedor
    WHERE g.id = $idGasto
    LIMIT 1
");
if (!$resultado) {
    error_log('Orden de compra de gasto: ' . mysqli_error($conexion));
    http_response_code(500);
    exit('No se pudo consultar el gasto.');
}
$gasto = mysqli_fetch_assoc($resultado);
if (!$gasto) {
    http_response_code(404);
    exit('No se encontró el gasto solicitado.');
}

function htmlOrdenGasto($valor): string {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function dineroOrdenGasto($valor): string {
    return number_format((float)$valor, 2, ',', '.');
}

$numeroOrden = 'OC-GAS-' . str_pad((string)$idGasto, 6, '0', STR_PAD_LEFT);
$fechaOrden = !empty($gasto['fecha']) ? date('d/m/Y', strtotime($gasto['fecha'])) : date('d/m/Y');
$moneda = strtoupper((string)($gasto['moneda'] ?? 'USD')) === 'BS' ? 'Bs' : '$';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orden <?= htmlOrdenGasto($numeroOrden) ?> - Unifuerte</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #e8edf2; color: #17212b; font-family: Arial, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: 10px; margin: 0 auto 18px; }
        .toolbar button { border: 0; border-radius: 4px; padding: 10px 16px; color: #fff; background: #0b4c8c; font-weight: 700; cursor: pointer; }
        .toolbar .close { background: #59636e; }
        .document { width: 8.5in; min-height: 11in; margin: auto; padding: 18mm 16mm; background: #fff; box-shadow: 0 3px 16px #0002; }
        .top { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0b4c8c; padding-bottom: 14px; }
        .brand { display: flex; align-items: center; gap: 12px; color: #0b4c8c; }
        .brand img { width: 54px; height: 54px; object-fit: contain; }
        .brand h1 { margin: 0; font-size: 19px; }
        .brand p { margin: 3px 0 0; color: #586575; font-size: 10px; }
        .order-number { text-align: right; }
        .order-number h2 { margin: 0 0 6px; color: #0b4c8c; font-size: 17px; }
        .order-number strong { font-size: 13px; }
        .supplier { width: 100%; margin: 22px 0; border-collapse: collapse; }
        .supplier th, .supplier td, .items th, .items td { border: 1px solid #8c9aa8; padding: 9px; font-size: 11px; text-align: left; vertical-align: top; }
        .supplier th, .items th { width: 19%; background: #eef3f7; }
        .items { width: 100%; border-collapse: collapse; }
        .items thead th { background: #0b4c8c; color: #fff; text-align: center; }
        .items .amount { text-align: right; white-space: nowrap; }
        .summary { width: 44%; margin: 18px 0 0 auto; border-collapse: collapse; }
        .summary th, .summary td { border: 1px solid #8c9aa8; padding: 9px; font-size: 11px; }
        .summary th { background: #eef3f7; text-align: left; }
        .summary td { text-align: right; font-weight: 700; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-top: 100px; text-align: center; font-size: 10px; }
        .signatures div { border-top: 1px solid #586575; padding-top: 8px; }
        @media print {
            @page { size: letter portrait; margin: 0; }
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
            .document { width: 8.5in; min-height: 11in; margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir / Guardar PDF</button>
        <button class="close" type="button" onclick="window.close()">Cerrar</button>
    </div>
    <main class="document">
        <header class="top">
            <div class="brand">
                <img src="../../img/camisa.jpg" alt="Unifuerte">
                <div><h1>UNIFUERTE</h1><p>COMPRAS Y SERVICIOS</p></div>
            </div>
            <div class="order-number">
                <h2>ORDEN DE COMPRA / SERVICIO</h2>
                <strong><?= htmlOrdenGasto($numeroOrden) ?></strong><br>
                <span>Fecha: <?= htmlOrdenGasto($fechaOrden) ?></span>
            </div>
        </header>

        <table class="supplier">
            <tr><th>Proveedor</th><td colspan="3"><?= htmlOrdenGasto($gasto['proveedor_nombre'] ?? 'Proveedor no disponible') ?></td></tr>
            <tr><th>RIF / C.I.</th><td><?= htmlOrdenGasto($gasto['proveedor_rif'] ?? '') ?></td><th>Contacto</th><td><?= htmlOrdenGasto($gasto['proveedor_contacto'] ?? '') ?></td></tr>
            <tr><th>Teléfono</th><td><?= htmlOrdenGasto($gasto['proveedor_telefono'] ?? '') ?></td><th>Dirección</th><td><?= htmlOrdenGasto($gasto['proveedor_direccion'] ?? '') ?></td></tr>
            <tr><th>Referencia</th><td colspan="3"><?= htmlOrdenGasto($gasto['factura'] ?? '') ?: 'S/N' ?></td></tr>
        </table>

        <table class="items">
            <thead><tr><th style="width: 12%;">Cant.</th><th>Descripción del bien o servicio</th><th style="width: 25%;">Precio / Total</th></tr></thead>
            <tbody><tr><td class="amount">1</td><td><?= nl2br(htmlOrdenGasto($gasto['descripcion'] ?? '')) ?></td><td class="amount"><?= $moneda ?> <?= dineroOrdenGasto($gasto['monto_total']) ?></td></tr></tbody>
        </table>

        <table class="summary">
            <tr><th>Total <?= htmlOrdenGasto($moneda) ?></th><td><?= $moneda ?> <?= dineroOrdenGasto($gasto['monto_total']) ?></td></tr>
            <?php if ((float)($gasto['tasa_bcv'] ?? 0) > 0): ?>
            <tr><th>Tasa BCV</th><td><?= dineroOrdenGasto($gasto['tasa_bcv']) ?></td></tr>
            <?php if ($moneda === '$' && (float)($gasto['monto_bs'] ?? 0) > 0): ?>
            <tr><th>Equivalente</th><td>Bs <?= dineroOrdenGasto($gasto['monto_bs']) ?></td></tr>
            <?php elseif ($moneda === 'Bs' && (float)($gasto['monto_usd'] ?? 0) > 0): ?>
            <tr><th>Equivalente</th><td>$ <?= dineroOrdenGasto($gasto['monto_usd']) ?></td></tr>
            <?php endif; endif; ?>
        </table>

        <div class="signatures"><div>Solicitado por<br><strong>UNIFUERTE, C.A.</strong></div><div>Autorizado por<br><strong>Claritza Pinto</strong></div></div>
    </main>
</body>
</html>