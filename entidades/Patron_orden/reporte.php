<?php 
require_once '../../conexion.php'; 
$datos = $pdo->query("SELECT po.*, p.categoria as patron_cat, p.Talla as patron_talla FROM patron_ordencomp po LEFT JOIN patron p ON po.Patron = p.ID ORDER BY po.Orden_Compra DESC")->fetchAll(PDO::FETCH_ASSOC); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Reporte Moldes</title>
    <style>body{font-family:Arial,sans-serif;font-size:12px;padding:20px;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ccc;padding:8px;text-align:left;}th{background:#f4f5f8;font-weight:bold;}.header{text-align:center;border-bottom:2px solid #111;padding-bottom:10px;}</style>
</head>
<body onload="window.print()">
    <div class="header"><h2>Moldería Aplicada por Orden de Producción - Unifuerte</h2><p>Generado: <?= date('d/m/Y h:i A') ?></p></div>
    <table>
        <tr><th>Orden Nro</th><th>ID Tarea (Prod)</th><th>Patrón Asignado (ID)</th><th>Categoría de Prenda Base</th><th>Talla Estándar</th></tr>
        <?php foreach($datos as $d): ?>
        <tr><td style="font-weight:bold;">Ord. <?= htmlspecialchars($d['Orden_Compra']) ?></td><td>Tarea ID: <?= htmlspecialchars($d['id_tarea'] ?? 'N/A') ?></td><td>Molde #<?= htmlspecialchars($d['Patron'] ?? '') ?></td><td><?= htmlspecialchars($d['patron_cat'] ?? 'N/A') ?></td><td style="color:red; font-weight:bold;"><?= htmlspecialchars($d['patron_talla'] ?? 'N/A') ?></td></tr>
        <?php endforeach; ?>
    </table>
</body>
</html>