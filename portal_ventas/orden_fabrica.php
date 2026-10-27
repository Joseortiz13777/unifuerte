<?php
// Archivo: Unifuerte/ventas/orden_fabrica.php
require_once '../../conexion.php';

$idOrden = $_GET['id_orden'] ?? 0;

// Consulta relacional del documento maestro
$query = "SELECT o.*, c.nombre AS cliente_nombre, c.cedula_Rif AS cliente_rif, c.direccion AS cliente_dir, c.comprador, c.email, c.tlf 
          FROM orden_compra o 
          JOIN cliente c ON o.id_cliente = c.ID 
          WHERE o.ID = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$idOrden]);
$orden = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    die("Error: La orden especificada no existe.");
}

// Consultar artículos y telas vinculadas
$stmtItems = $pdo->prepare("SELECT d.*, m.nombre AS tela_nombre, m.color AS tela_color 
                            FROM detalle_orden_compra d 
                            LEFT JOIN materia_prima m ON d.id_materia = m.ID 
                            WHERE d.id_orden_compra = ?");
$stmtItems->execute([$idOrden]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// Consultar el listado y tallaje del personal corporativo
$stmtPersonal = $pdo->prepare("SELECT mp.* FROM medicion_personal mp 
                               WHERE mp.id_detalle_orden_compra IN (
                                   SELECT ID FROM detalle_orden_compra WHERE id_orden_compra = ?
                               )");
$stmtPersonal->execute([$idOrden]);
$personal = $stmtPersonal->fetchAll(PDO::FETCH_ASSOC);

// Calcular matriz resumen de tallas automáticamente para el taller de corte
$resumenTallas = [];
foreach($personal as $p) {
    $t = $p['talla_asignada'];
    $g = $p['genero'];
    if(!isset($resumenTallas[$g][$t])) $resumenTallas[$g][$t] = 0;
    $resumenTallas[$g][$t]++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Fábrica Nro. <?= $orden['nro_orden_cotizacion'] ?> - Unifuerte</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #222; font-size: 11px; line-height: 1.4; padding: 20px; }
        .invoice-box { max-width: 900px; margin: auto; background: #FFF; }
        .table-header { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .logo-title { font-size: 24px; font-weight: bold; color: #13131A; letter-spacing: -1px; }
        .doc-title { font-size: 14px; font-weight: bold; text-align: right; text-transform: uppercase; color: #444; }
        
        .info-block { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-block td { padding: 5px; border: 1px solid #E4E4EB; vertical-align: top; }
        .title-sub { background: #13131A; color: #FFF; font-weight: bold; padding: 4px 8px; font-size: 10px; text-transform: uppercase; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .data-table th { background: #F4F5F8; color: #444; text-align: left; padding: 8px; border: 1px solid #E4E4EB; font-weight: 600; text-transform: uppercase; font-size: 9px; }
        .data-table td { padding: 8px; border: 1px solid #E4E4EB; }
        
        .grid-resumen { display: flex; gap: 20px; margin-bottom: 20px; }
        .resumen-card { border: 1px solid #E4E4EB; border-radius: 4px; padding: 10px; flex: 1; }
        .resumen-card h4 { margin-bottom: 5px; border-bottom: 1px solid #E4E4EB; padding-bottom: 3px; font-size: 10px; color: #555; text-transform: uppercase; }
        
        .badge { background: #000; color: #FFF; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="invoice-box">
        <div class="no-print" style="text-align:right; margin-bottom: 20px;">
            <button onclick="window.print();" style="padding:8px 20px; background:#13131A; color:#FFF; font-weight:bold; border:none; border-radius:4px; cursor:pointer;">🖨️ Imprimir Ficha de Taller</button>
        </div>

        <table class="table-header">
            <tr>
                <td>
                    <div class="logo-title">Unifuerte</div>
                    <span style="color:#777;">RIF: J-41282764-2<br>SENCAMER 0012216TN-21</span>
                </td>
                <td class="doc-title">
                    <span style="font-size:18px; color: #E11D48;">ORDEN DE FÁBRICA / CONFECCIÓN</span><br>
                    <strong>NRO: <?= htmlspecialchars($orden['nro_orden_cotizacion']) ?></strong><br>
                    <span>Fecha Emisión: <?= date('d/m/Y', strtotime($orden['fecha_emision'])) ?></span><br>
                    <strong>Fecha Entrega del Pedido: <?= date('d/m/Y', strtotime($orden['fecha_entrega'])) ?></strong>
                </td>
            </tr>
        </table>

        <div class="title-sub">Especificaciones Generales del Cliente</div>
        <table class="info-block">
            <tr>
                <td style="width: 50%;">
                    <strong>Razón Social:</strong> <?= htmlspecialchars($orden['cliente_nombre']) ?><br>
                    <strong>RIF / Cédula:</strong> <?= htmlspecialchars($orden['cliente_rif']) ?><br>
                    <strong>Dirección Fiscal/Entrega:</strong> <?= htmlspecialchars($orden['cliente_dir']) ?>
                </td>
                <td style="width: 50%;">
                    <strong>Contacto de Ventas (Comprador):</strong> <?= htmlspecialchars($orden['comprador']) ?><br>
                    <strong>Teléfono:</strong> <?= htmlspecialchars($orden['tlf']) ?><br>
                    <strong>Correo Electrónico:</strong> <?= htmlspecialchars($orden['email']) ?>
                </td>
            </tr>
        </table>

        <div class="title-sub">Ítems de Producción y Muestra Textil Solicitada</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Descripción de la Prenda</th>
                    <th>Textil Base Sugerido</th>
                    <th>Combinación Colores</th>
                    <th>Cant.</th>
                    <th>Detalles Críticos de Diseño</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $it): ?>
                <tr>
                    <td style="font-weight: bold;"><?= htmlspecialchars($it['tipo_prenda']) ?></td>
                    <td><?= htmlspecialchars($it['tela_nombre'] ?? 'No especificada') ?> (<?= htmlspecialchars($it['tela_color'] ?? 'N/A') ?>)</td>
                    <td><?= htmlspecialchars($it['combinacion_colores']) ?></td>
                    <td><strong><?= $it['cantidad'] ?></strong> unds</td>
                    <td><?= htmlspecialchars($it['detalle_diseno']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="title-sub">Resumen de Tallaje Agregado para Mesa de Corte</div>
        <div class="grid-resumen" style="margin-top:10px;">
            <?php if(!empty($resumenTallas)): ?>
                <?php foreach($resumenTallas as $genero => $tallas): ?>
                    <div class="resumen-card">
                        <h4>Distribución Tallas - <?= htmlspecialchars($genero) ?></h4>
                        <table style="width:100%;">
                            <tr>
                                <?php foreach($tallas as $t => $c): ?>
                                    <td style="padding:4px; text-align:center; border:1px solid #E4E4EB;">
                                        <strong><?= htmlspecialchars($t) ?></strong><br>
                                        <span style="font-size:12px; color:#E11D48; font-weight:bold;"><?= $c ?></span>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="padding:10px; color:#777;">No se definieron desgloses de tallas específicas.</p>
            <?php endif; ?>
        </div>

        <div class="title-sub">Anexo Técnico de Confección: Medidas de Precisión del Personal</div>
        <table class="data-table" style="margin-top:10px;">
            <thead>
                <tr>
                    <th>Nombre Empleado</th>
                    <th>Género</th>
                    <th>Talla</th>
                    <th>Hombro</th>
                    <th>Busto</th>
                    <th>Cintura</th>
                    <th>Cadera</th>
                    <th>L. Camisa</th>
                    <th>L. Brazo</th>
                    <th>L. Pantalón</th>
                    <th>Observaciones Especiales del Patronaje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($personal as $per): ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($per['nombre']) ?></td>
                    <td><?= htmlspecialchars($per['genero']) ?></td>
                    <td><span class="badge"><?= htmlspecialchars($per['talla_asignada']) ?></span></td>
                    <td><?= $per['hombro'] ?? '-' ?></td>
                    <td><?= $per['busto_torax'] ?? '-' ?></td>
                    <td><?= $per['cintura'] ?? '-' ?></td>
                    <td><?= $per['cadera'] ?? '-' ?></td>
                    <td><?= $per['largo_camisa'] ?? '-' ?></td>
                    <td><?= $per['largo_brazo'] ?? '-' ?></td>
                    <td><?= $per['largo_pantalon'] ?? '-' ?></td>
                    <td style="color:#555; font-style:italic;"><?= htmlspecialchars($per['observacion']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table style="width: 100%; border-top: 2px solid #13131A; padding-top: 10px; font-size: 9px; color: #666;">
            <tr>
                <td>
                    * Pedido Express: <strong><?= $orden['es_express'] ? 'SÍ (+10%)' : 'NO' ?></strong> | 
                    Entregas Parciales: <strong><?= $orden['acepta_entregas_parciales'] ? 'ACEPTA' : 'NO ACEPTA' ?></strong>
                </td>
                <td style="text-align: right;">
                    Garantía de Confección Unifuerte. Sistema Operativo Corporativo.
                </td>
            </tr>
        </table>
    </div>

</body>
</html>