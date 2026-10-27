<?php
// Archivo: Unifuerte/entidades/Provedor/reporte.php
require_once '../../conexion.php';
$proveedores = $pdo->query("SELECT * FROM provedor ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Reporte Proveedores</title>
    <style> body{font-family:Arial, sans-serif; font-size:12px; color:#111;} table{width:100%; border-collapse:collapse; margin-top:20px;} th,td{border:1px solid #ccc; padding:8px; text-align:left;} th{background:#f4f5f8;} .header{text-align:center; border-bottom: 2px solid #111; padding-bottom: 10px;} @media print { .btn-print { display: none; } } </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>Directorio de Proveedores - Unifuerte</h2>
        <p>Generado el: <?= date('d/m/Y h:i A') ?></p>
    </div>
    <table>
        <tr><th>ID</th><th>Empresa</th><th>RIF/Cédula</th><th>Contacto</th><th>Teléfono</th><th>Ubicación</th><th>Flete</th></tr>
        <?php foreach($proveedores as $p): ?>
        <tr><td><?= $p['ID'] ?></td><td><?= htmlspecialchars($p['nombre'] ?? '') ?></td><td><?= htmlspecialchars($p['RIF_cedula'] ?? '') ?></td><td><?= htmlspecialchars($p['contacto'] ?? '') ?></td><td><?= htmlspecialchars($p['tlf'] ?? '') ?></td><td><?= htmlspecialchars($p['ubicacion'] ?? '') ?></td><td>$<?= htmlspecialchars($p['flete_internacional'] ?? '') ?></td></tr>
        <?php endforeach; ?>
    </table>
</body>
</html>