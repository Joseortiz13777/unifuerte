<?php
// Archivo: Unifuerte/entidades/Orden_Compra/reporte.php
require_once '../../conexion.php'; 
$sql = "SELECT o.*, c.nombre as cl_nombre FROM orden_compra o LEFT JOIN cliente c ON o.id_cliente = c.ID ORDER BY o.fecha_emision DESC"; 
$ordenes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Reporte Libro de Ventas</title>
    <style> body {font-family:Arial, sans-serif;font-size:11px;padding:20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:6px;text-align:left;} th{background:#f4f5f8;font-weight:bold;} .header{text-align:center;border-bottom:2px solid #111;padding-bottom:10px;margin-bottom:20px;} </style>
</head>
<body onload="window.print()">
    <div class="header"><h2>Libro de Órdenes Emitidas a Clientes (Ventas) - Unifuerte</h2><p>Generado el: <?= date('d/m/Y h:i A') ?></p></div>
    <table>
        <thead><tr><th>ID</th><th>Cliente</th><th>Nro Orden</th><th>Fecha Emisión</th><th>Fecha Entrega</th><th>Total ($)</th></tr></thead>
        <tbody>
            <?php foreach($ordenes as $o): ?>
            <tr><td><?= $o['ID'] ?></td><td><?= htmlspecialchars($o['cl_nombre'] ?? 'N/A') ?></td><td><?= htmlspecialchars($o['nro_orden_cotizacion'] ?? '') ?></td><td><?= htmlspecialchars($o['fecha_emision'] ?? '') ?></td><td><?= htmlspecialchars($o['fecha_entrega'] ?? '') ?></td><td style="font-weight:bold; color:green;">$<?= htmlspecialchars($o['total'] ?? '0.00') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>