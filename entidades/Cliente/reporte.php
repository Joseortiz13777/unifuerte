<?php
// Archivo: Unifuerte/entidades/Cliente/reporte.php
require_once '../../conexion.php';
$stmt = $pdo->query("SELECT * FROM cliente ORDER BY nombre ASC");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Clientes - Unifuerte</title>
    <style>
        body { font-family: 'Arial', sans-serif; padding: 20px; color: #111; }
        .encabezado { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #111; padding-bottom: 10px; }
        h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        p { margin: 5px 0; color: #555; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #f4f5f8; font-weight: bold; }
        @media print {
            .btn-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="encabezado">
        <h1>Directorio de Clientes - Unifuerte</h1>
        <p>Reporte generado el: <?php echo date('d/m/Y h:i A'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Razón Social / Nombre</th>
                <th>Cédula o RIF</th>
                <th>Persona Contacto</th>
                <th>Teléfono</th>
                <th>Correo Electrónico</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($clientes as $c): ?>
            <tr>
                <td><?php echo $c['ID']; ?></td>
                <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                <td><?php echo htmlspecialchars($c['cedula_Rif']); ?></td>
                <td><?php echo htmlspecialchars($c['comprador']); ?></td>
                <td><?php echo htmlspecialchars($c['tlf']); ?></td>
                <td><?php echo htmlspecialchars($c['email']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>