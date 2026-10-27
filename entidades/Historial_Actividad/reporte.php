<?php
// Archivo: Unifuerte/entidades/Historial_Actividad/reporte.php
require_once '../../conexion.php'; 
$sql = "SELECT h.*, u.nombre_usuario FROM historial_actividad h LEFT JOIN usuario u ON h.id_usuario = u.ID ORDER BY h.fecha_hora DESC LIMIT 500"; 
$historial = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Reporte Auditoría</title>
    <style> body {font-family: 'Courier New', Courier, monospace; font-size: 10px; padding: 20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #aaa;padding:6px;text-align:left;} th{background:#eee;font-weight:bold;} .header{text-align:center;border-bottom:1px dashed #111;padding-bottom:10px;margin-bottom:20px;} </style>
</head>
<body onload="window.print()">
    <div class="header"><h2>REPORTE DE SEGURIDAD Y LOGS (Últimos 500) - UNIFUERTE</h2><p>Generado: <?= date('d/m/Y h:i A') ?></p></div>
    <table>
        <thead><tr><th>Log ID</th><th>Usuario Resp.</th><th>Acción Realizada</th><th>Tabla BD</th><th>Registro ID</th><th>Fecha/Hora</th><th>Detalle Completo</th></tr></thead>
        <tbody>
            <?php foreach($historial as $h): ?>
            <tr><td><?= $h['ID'] ?></td><td><?= htmlspecialchars($h['nombre_usuario'] ?? 'Sistema') ?> (ID:<?= $h['id_usuario'] ?>)</td><td><?= htmlspecialchars($h['accion'] ?? '') ?></td><td><?= htmlspecialchars($h['tabla_afectada'] ?? '') ?></td><td><?= htmlspecialchars($h['id_registro_afectado'] ?? '') ?></td><td><?= htmlspecialchars($h['fecha_hora'] ?? '') ?></td><td><?= htmlspecialchars($h['detalle_cambio'] ?? '') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>