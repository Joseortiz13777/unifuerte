<?php
// Archivo: Unifuerte/entidades/Mat_Prov/reporte.php
require_once '../../conexion.php'; 
$sql = "SELECT mp.*, p.nombre as prov_nombre, m.nombre as mat_nombre 
        FROM mat_prov mp 
        LEFT JOIN provedor p ON mp.id_provedor = p.ID 
        LEFT JOIN materia_prima m ON mp.id_materia = m.ID 
        ORDER BY p.nombre ASC"; 
$matprov = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Relación Material-Proveedor</title>
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
        <h2>Precios de Materiales por Proveedor - Unifuerte</h2>
        <p>Generado el: <?= date('d/m/Y h:i A') ?></p>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Proveedor</th>
                <th>Materia Prima</th>
                <th>Precio ($)</th>
                <th>Calidad</th>
                <th>Presentación</th>
                <th>Última Act.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($matprov as $mp): ?>
            <tr>
                <td><?= $mp['ID'] ?></td>
                <td><?= htmlspecialchars($mp['prov_nombre'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($mp['mat_nombre'] ?? 'N/A') ?></td>
                <td style="font-weight: bold;">$<?= htmlspecialchars($mp['precio_actual'] ?? '0.00') ?></td>
                <td><?= htmlspecialchars($mp['calidad'] ?? '') ?></td>
                <td><?= htmlspecialchars($mp['presentacion'] ?? '') ?></td>
                <td><?= htmlspecialchars($mp['fecha_actualizacion'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>