<?php 
require_once '../../conexion.php'; 
$sql = "SELECT p.*, doc.tipo_prenda, o.nro_orden_cotizacion 
        FROM posicion p 
        LEFT JOIN detalle_orden_compra doc ON p.id_detalle_orden_compra = doc.ID 
        LEFT JOIN orden_compra o ON doc.id_orden_compra = o.ID 
        ORDER BY p.id_detalle_orden_compra DESC"; 
$datos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Reporte Posiciones</title>
    <style>body{font-family:Arial,sans-serif;font-size:12px;padding:20px;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ccc;padding:8px;text-align:left;}th{background:#f4f5f8;font-weight:bold;}.header{text-align:center;border-bottom:2px solid #111;padding-bottom:10px;}</style>
</head>
<body onload="window.print()">
    <div class="header"><h2>Ubicación Anatómica de Logos y Elementos por Prenda - Unifuerte</h2><p>Generado: <?= date('d/m/Y h:i A') ?></p></div>
    <table>
        <tr><th>ID Item (Prenda)</th><th>Orden Nro</th><th>Tipo de Prenda</th><th>Ubicación Anatómica</th><th>Elemento / Logo</th><th>Observación</th></tr>
        <?php foreach($datos as $d): ?>
        <tr><td style="font-weight:bold;">Prenda #<?= htmlspecialchars($d['id_detalle_orden_compra'] ?? '') ?></td><td>Ord. <?= htmlspecialchars($d['nro_orden_cotizacion'] ?? 'N/A') ?></td><td style="color:var(--color-green); font-weight:bold;"><?= htmlspecialchars($d['tipo_prenda'] ?? 'N/A') ?></td><td><?= htmlspecialchars($d['ubicacion'] ?? '') ?></td><td style="font-weight:bold; color:red;"><?= htmlspecialchars($d['elemento'] ?? '') ?></td><td><?= htmlspecialchars($d['observacion'] ?? '') ?></td></tr>
        <?php endforeach; ?>
    </table>
</body>
</html>