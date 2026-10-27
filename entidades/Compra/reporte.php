<?php
// Archivo: Unifuerte/entidades/Compra/reporte.php
require_once '../../conexion.php'; 
$sql = "SELECT c.*, p.nombre as prov_nombre FROM compra c LEFT JOIN provedor p ON c.id_provedor = p.ID ORDER BY c.fecha_compra DESC"; 
$compras = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Reporte Compras</title>
    <style> body {font-family:Arial, sans-serif;font-size:12px;padding:20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:8px;text-align:left;} th{background:#f4f5f8;font-weight:bold;} .header{text-align:center;border-bottom:2px solid #111;padding-bottom:10px;margin-bottom:20px;} </style>
</head>
<body onload="window.print()">
    <div class="header"><h2>Reporte de Compras de Insumos - Unifuerte</h2><p>Generado: <?= date('d/m/Y h:i A') ?></p></div>
    <table>
        <thead><tr><th>Factura Nro (ID)</th><th>Proveedor</th><th>Fecha Compra</th><th>Estado</th><th>Costo Total ($)</th></tr></thead>
        <tbody>
            <?php foreach($compras as $c): ?>
            <tr><td><?= $c['ID'] ?></td><td><?= htmlspecialchars($c['prov_nombre'] ?? 'N/A') ?></td><td><?= htmlspecialchars($c['fecha_compra'] ?? '') ?></td><td><?= htmlspecialchars($c['estado'] ?? '') ?></td><td style="font-weight:bold; color: green;">$<?= htmlspecialchars($c['costo'] ?? '0.00') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>