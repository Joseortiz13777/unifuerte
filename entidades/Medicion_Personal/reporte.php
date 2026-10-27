<?php 
require_once '../../conexion.php'; 
$sql = "SELECT m.*, d.tipo_prenda, o.nro_orden_cotizacion 
        FROM medicion_personal m 
        LEFT JOIN detalle_orden_compra d ON m.id_detalle_orden_compra = d.ID 
        LEFT JOIN orden_compra o ON d.id_orden_compra = o.ID 
        ORDER BY m.nombre ASC";
$datos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Reporte Medidas</title><style> @page { size: landscape; } body{font-family:Arial,sans-serif;font-size:10px;padding:20px;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ccc;padding:5px;text-align:center;}th{background:#f4f5f8;font-weight:bold;}.header{text-align:center;border-bottom:2px solid #111;padding-bottom:10px;}</style></head>
<body onload="window.print()"><div class="header"><h2>Sastrería: Dotación y Tallas de Personal - Unifuerte</h2><p>Generado: <?= date('d/m/Y h:i A') ?></p></div>
<table><thead><tr><th>Prenda Asignada</th><th>Nombre Empleado</th><th>Género</th><th>Talla Asignada</th><th>Hombro</th><th>Busto/Tórax</th><th>Cintura</th><th>Cadera</th><th>Largo Cam.</th><th>Largo Pant.</th></tr></thead>
<tbody><?php foreach($datos as $d): $txt_prenda = "Ord: " . ($d['nro_orden_cotizacion']??'') . " - " . ($d['tipo_prenda']??''); ?>
<tr><td style="font-weight:bold; text-align:left; color:green;"><?= htmlspecialchars($txt_prenda) ?></td><td style="text-align:left; font-weight:bold;"><?= htmlspecialchars($d['nombre'] ?? '') ?></td><td><?= htmlspecialchars($d['genero'] ?? '') ?></td><td style="font-weight:bold; color:red;"><?= htmlspecialchars($d['talla_asignada'] ?? '') ?></td><td><?= htmlspecialchars($d['hombro'] ?? '') ?></td><td><?= htmlspecialchars($d['busto_torax'] ?? '') ?></td><td><?= htmlspecialchars($d['cintura'] ?? '') ?></td><td><?= htmlspecialchars($d['cadera'] ?? '') ?></td><td><?= htmlspecialchars($d['largo_camisa'] ?? '') ?></td><td><?= htmlspecialchars($d['largo_pantalon'] ?? '') ?></td></tr>
<?php endforeach; ?></tbody></table></body></html>