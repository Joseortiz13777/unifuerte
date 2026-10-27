<?php
// Archivo: Unifuerte/entidades/Materia_Prima/reporte.php
require_once '../../conexion.php';
$materiales = $pdo->query("SELECT * FROM materia_prima ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Reporte Materia Prima</title>
    <style> body{font-family:Arial, sans-serif; font-size:11px; color:#111;} table{width:100%; border-collapse:collapse; margin-top:20px;} th,td{border:1px solid #ccc; padding:6px; text-align:left;} th{background:#f4f5f8;} .header{text-align:center; border-bottom: 2px solid #111; padding-bottom: 10px;} .alerta{color:#b91c1c; font-weight:bold; background:#fee2e2;} </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>Inventario Físico de Materia Prima e Insumos - Unifuerte</h2>
        <p>Generado el: <?= date('d/m/Y h:i A') ?></p>
    </div>
    <table>
        <tr><th>ID</th><th>Nombre</th><th>Color / Cód.</th><th>Textura</th><th>Dimensión</th><th>Origen</th><th>Unidad</th><th>Stock Actual</th><th>Punto Mín.</th><th>Costo ($)</th></tr>
        <?php foreach($materiales as $m): 
            $clase = ($m['stock_actual'] <= $m['stock_minimo']) ? 'alerta' : '';
        ?>
        <tr>
            <td><?= $m['ID'] ?></td><td><?= htmlspecialchars($m['nombre'] ?? '') ?></td><td><?= htmlspecialchars(($m['color'] ?? '')." / ".($m['codigo_color'] ?? '')) ?></td>
            <td><?= htmlspecialchars($m['textura'] ?? '') ?></td><td><?= htmlspecialchars($m['tamano_dimension'] ?? '') ?></td><td><?= htmlspecialchars($m['origen_procedencia'] ?? '') ?></td>
            <td><?= htmlspecialchars($m['unidad_medida'] ?? '') ?></td><td class="<?= $clase ?>"><?= htmlspecialchars($m['stock_actual'] ?? '') ?></td><td><?= htmlspecialchars($m['stock_minimo'] ?? '') ?></td>
            <td>$<?= htmlspecialchars($m['costo'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>