<?php 
require_once '../../conexion.php'; 
$datos = $pdo->query("SELECT t.*, u.nombre as emp_nombre, u.apellido as emp_apellido FROM tareas t LEFT JOIN usuario u ON t.Usuario = u.ID ORDER BY t.estado ASC, t.Orden_Compra DESC")->fetchAll(PDO::FETCH_ASSOC); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Reporte Tareas</title>
    <style>body{font-family:Arial,sans-serif;font-size:12px;padding:20px;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ccc;padding:8px;text-align:left;}th{background:#f4f5f8;font-weight:bold;}.header{text-align:center;border-bottom:2px solid #111;padding-bottom:10px;}</style>
</head>
<body onload="window.print()">
    <div class="header"><h2>Reporte de Tareas Asignadas de Producción - Unifuerte</h2><p>Generado: <?= date('d/m/Y h:i A') ?></p></div>
    <table>
        <tr><th>ID Tarea</th><th>Orden de Producción</th><th>Empleado Asignado</th><th>Estado Actual</th></tr>
        <?php foreach($datos as $d): ?>
        <tr><td><?= $d['ID'] ?></td><td style="font-weight:bold;">Ord. <?= htmlspecialchars($d['Orden_Compra']) ?></td><td><?= htmlspecialchars(($d['emp_nombre'] ?? '').' '.($d['emp_apellido'] ?? 'N/A')) ?></td><td style="font-weight:bold;"><?= htmlspecialchars($d['estado']) ?></td></tr>
        <?php endforeach; ?>
    </table>
</body>
</html>