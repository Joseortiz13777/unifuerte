<?php
// Archivo: Unifuerte/entidades/Receta_Prenda/reporte.php
require_once '../../conexion.php'; 
$sql = "SELECT rp.*, m.nombre as mat_nombre 
        FROM receta_prenda rp 
        LEFT JOIN materia_prima m ON rp.id_materia_prima = m.ID 
        ORDER BY rp.tipo_prenda ASC"; 
$recetas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Costeo Receta Prenda</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; padding: 20px;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f4f5f8; font-weight: bold; }
        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 20px; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>Costeo y Receta de Prendas - Unifuerte</h2>
        <p>Generado el: <?= date('d/m/Y h:i A') ?></p>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo de Prenda</th>
                <th>Material Asignado</th>
                <th>Cant. por Unidad</th>
                <th>Costo Unitario ($)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($recetas as $rp): ?>
            <tr>
                <td><?= $rp['ID'] ?></td>
                <td><?= htmlspecialchars($rp['tipo_prenda'] ?? '') ?></td>
                <td><?= htmlspecialchars($rp['mat_nombre'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($rp['cantidad_por_unidad'] ?? '') ?></td>
                <td style="font-weight: bold; color: green;">$<?= htmlspecialchars($rp['Costo_unitario'] ?? '0.00') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>