<?php
session_start();
error_reporting(0);
require_once '../conexion.php';
require_once 'ingresos_dashboard.php';

// Validar parámetros de la URL
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');
$token = $_GET['t'] ?? '';

// Verificación de Seguridad del Link
$token_esperado = md5('cierre' . $desde . $hasta . 'unifuerte');
if ($token !== $token_esperado) {
    die("<h2 style='text-align:center; padding: 50px; color: red;'>Acceso Denegado o Enlace Caducado.</h2>");
}

// =========================================================================
// 1. VENTAS: COTIZACIONES REALIZADAS, PAGOS INICIALES Y TOTALES
// =========================================================================
$ventas_usuarios = [];
$total_ingresos_reales = 0;
$total_cotizaciones_global = 0;

$where_v = "c.pausado = 0 AND DATE(c.fecha_emision) BETWEEN '$desde' AND '$hasta'";
$q_cot = mysqli_query($conexion, "SELECT u.nombre, u.apellido, c.estado_pago FROM cotizacion c LEFT JOIN usuario u ON c.id_vendedor = u.ID WHERE $where_v");

while ($row = mysqli_fetch_assoc($q_cot)) {
    $nombreVend = trim(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? ''));
    if (empty($nombreVend)) $nombreVend = "Desconocido";
    
    if (!isset($ventas_usuarios[$nombreVend])) {
        $ventas_usuarios[$nombreVend] = ['cotizaciones' => 0, 'pago_inicial' => 0, 'pago_total' => 0, 'monto' => 0];
    }
    
    $ventas_usuarios[$nombreVend]['cotizaciones']++;
    $total_cotizaciones_global++;
    
    $estadoLC = strtolower(trim($row['estado_pago']));
    
    if ($estadoLC === 'pagado') {
        $ventas_usuarios[$nombreVend]['pago_total']++;
    } else if ($estadoLC === 'pago inicial' || $estadoLC === 'pago medio') {
        $ventas_usuarios[$nombreVend]['pago_inicial']++;
    }

}

$pagos_periodo = obtenerPagosCotizacionesDashboard($conexion, $desde, $hasta);
if ($pagos_periodo === false) {
    die('No se pudieron cargar los pagos del período.');
}
foreach ($pagos_periodo as $pago) {
    $nombreVend = $pago['vendedor_nombre'];
    if (!isset($ventas_usuarios[$nombreVend])) {
        $ventas_usuarios[$nombreVend] = ['cotizaciones' => 0, 'pago_inicial' => 0, 'pago_total' => 0, 'monto' => 0];
    }
    $ventas_usuarios[$nombreVend]['monto'] += $pago['monto'];
    $total_ingresos_reales += $pago['monto'];
}
arsort($ventas_usuarios);

// =========================================================================
// 2. LOGÍSTICA: PRENDAS REALIZADAS Y ENTREGADAS
// =========================================================================
$total_prendas_terminadas = 0;
$total_prendas_entregadas = 0;
$total_pedidos_finalizados = 0;

mysqli_query($conexion, "
    UPDATE tracking_orden t
    SET fecha_terminado = (
        SELECT MAX(fecha_hora)
        FROM historial_actividad
        WHERE tabla_afectada = 'tracking_orden'
          AND id_registro_afectado = t.id_orden_fabrica
          AND (detalle_cambio LIKE '%Finalizó%' OR detalle_cambio LIKE '%Confirmó físicamente%')
    )
    WHERE t.estado_general IN ('Terminado', 'Entregado')
      AND (t.fecha_terminado IS NULL OR t.fecha_terminado = '0000-00-00 00:00:00')
");
mysqli_query($conexion, "
    UPDATE tracking_orden
    SET fecha_terminado = fecha_creacion
    WHERE estado_general IN ('Terminado', 'Entregado')
      AND (fecha_terminado IS NULL OR fecha_terminado = '0000-00-00 00:00:00')
");

$desde_sql = mysqli_real_escape_string($conexion, $desde);
$hasta_sql = mysqli_real_escape_string($conexion, $hasta);
$q_track = mysqli_query($conexion, "
    SELECT t.id_orden_fabrica, t.estado_general, t.prendas_totales
    FROM tracking_orden t
    WHERE t.estado_general IN ('Terminado', 'Entregado')
      AND t.fecha_terminado >= '$desde_sql'
      AND t.fecha_terminado < DATE_ADD('$hasta_sql', INTERVAL 1 DAY)
");
if (!$q_track) {
    die('No se pudo cargar el resumen de órdenes finalizadas para el cierre gerencial.');
}
while ($r = mysqli_fetch_assoc($q_track)) {
    $cantidad = (int)$r['prendas_totales'];
    if ($cantidad <= 0) {
        $cantidad = calcularPrendasActivasEnvios($conexion, (int)$r['id_orden_fabrica']);
    }
    $total_prendas_terminadas += $cantidad;
    $total_pedidos_finalizados++;

    if ($r['estado_general'] === 'Entregado') {
        $total_prendas_entregadas += $cantidad;
    }
}

// =========================================================================
// 3. ENVÍOS: PRENDAS EN CURSO E INSUMOS TEXTILES PENDIENTES
// =========================================================================
function calcularPrendasActivasEnvios($conexion, $idOrden) {
    $idOrden = intval($idOrden);
    $qCotizacion = mysqli_query($conexion, "SELECT id_cotizacion FROM orden_fabrica WHERE ID = $idOrden");
    if (!$qCotizacion || !($orden = mysqli_fetch_assoc($qCotizacion)) || empty($orden['id_cotizacion'])) {
        return 0;
    }

    $idCotizacion = intval($orden['id_cotizacion']);
    $excluir = [
        'arte final', 'bordado', 'vinil', 'dtf', 'serigraf', 'servicio',
        'matriz', 'ponchado', 'diseño', 'delivery', 'flete', 'descuento',
        'cobro adicional', 'parche', 'tallas extras', 'talla extra',
        'empaque', 'impresion', 'impresión'
    ];

    $total = 0;
    $qDetalles = mysqli_query($conexion, "SELECT tipo_prenda, cantidad FROM detalle_cotizacion WHERE id_cotizacion = $idCotizacion");
    if ($qDetalles) {
        while ($detalle = mysqli_fetch_assoc($qDetalles)) {
            $tipo = mb_strtolower(trim($detalle['tipo_prenda'] ?? ''), 'UTF-8');
            $noEsPrenda = false;
            foreach ($excluir as $texto) {
                if (strpos($tipo, $texto) !== false) {
                    $noEsPrenda = true;
                    break;
                }
            }
            if (!$noEsPrenda) {
                $total += intval($detalle['cantidad']);
            }
        }
    }

    if ($total === 0) {
        $qOrden = mysqli_query($conexion, "SELECT tipo_prenda, cantidad FROM detalle_orden_fabrica WHERE id_orden_fabrica = $idOrden");
        if ($qOrden) {
            while ($detalle = mysqli_fetch_assoc($qOrden)) {
                $tipo = mb_strtolower(trim($detalle['tipo_prenda'] ?? ''), 'UTF-8');
                if (strpos($tipo, 'bordado') === false && strpos($tipo, 'tallas extras') === false && strpos($tipo, 'talla extra') === false) {
                    $total += intval($detalle['cantidad']);
                }
            }
        }
    }

    return $total;
}

$ordenes_envio_activas = [];
$total_prendas_en_envios = 0;
$q_ordenes_envio = mysqli_query($conexion, "
    SELECT o.ID, o.nro_orden_fabrica, c.nombre AS cliente
    FROM orden_fabrica o
    JOIN cotizacion cot ON cot.ID = o.id_cotizacion
    JOIN cliente c ON c.ID = o.id_cliente
    LEFT JOIN tracking_orden t ON t.id_orden_fabrica = o.ID
    WHERE IFNULL(t.estado_general, 'Pendiente') NOT IN ('Terminado', 'Entregado')
    ORDER BY o.ID DESC
");
if (!$q_ordenes_envio) {
    die('No se pudo cargar el estado actual de Envíos para el cierre gerencial.');
}
while ($orden = mysqli_fetch_assoc($q_ordenes_envio)) {
    $orden['prendas'] = calcularPrendasActivasEnvios($conexion, $orden['ID']);
    $total_prendas_en_envios += $orden['prendas'];
    $ordenes_envio_activas[] = $orden;
}

$insumos_textiles_pendientes = [];
$q_insumos_pendientes = mysqli_query($conexion, "
    SELECT i.descripcion, i.check_estados, o.nro_orden_fabrica, c.nombre AS cliente
    FROM insumos_faltantes i
    JOIN orden_fabrica o ON o.ID = i.id_orden
    LEFT JOIN cliente c ON c.ID = o.id_cliente
    WHERE i.estado = 0
    ORDER BY i.fecha_solicitud ASC, o.ID DESC
");
if (!$q_insumos_pendientes) {
    die('No se pudo cargar la lista de compras textiles pendientes de Envíos.');
}
while ($solicitud = mysqli_fetch_assoc($q_insumos_pendientes)) {
    $items = json_decode($solicitud['descripcion'], true);
    if (!is_array($items) && json_last_error() === JSON_ERROR_NONE) {
        $items = [$items];
    } elseif (!is_array($items)) {
        $items = preg_split('/\r\n|\r|\n/', (string)$solicitud['descripcion']);
    }

    $estados = json_decode($solicitud['check_estados'] ?? '', true);
    if (!is_array($estados)) {
        $estados = [];
    }

    foreach ($items as $indice => $item) {
        if (!is_scalar($item)) {
            continue;
        }
        $nombreItem = trim((string)$item);
        if ($nombreItem === '') {
            continue;
        }

        $marcado = $estados[$indice] ?? false;
        $yaRecibido = in_array($marcado, [true, 1, '1', 'true', 'on'], true);
        if ($yaRecibido) {
            continue;
        }

        $insumos_textiles_pendientes[] = [
            'orden' => $solicitud['nro_orden_fabrica'],
            'cliente' => $solicitud['cliente'] ?? 'Cliente no registrado',
            'producto' => $nombreItem
        ];
    }
}

// =========================================================================
// 4. ADMINISTRACIÓN: EGRESOS Y CAJA
// =========================================================================
function obtenerTasaHistorica($conexion, $fecha) {
    $q_t = mysqli_query($conexion, "SELECT tasa_bcv FROM (SELECT tasa_bcv, fecha FROM finanzas_movimientos WHERE tasa_bcv > 10 UNION ALL SELECT tasa_bcv, fecha_pago AS fecha FROM nomina_lotes WHERE tasa_bcv > 10 UNION ALL SELECT tasa_bcv, fecha_pago AS fecha FROM pago_gastos_servicios WHERE tasa_bcv > 10 UNION ALL SELECT tasa_bcv, fecha_pago AS fecha FROM pago_compra WHERE tasa_bcv > 10) AS tasas WHERE fecha <= '$fecha' ORDER BY fecha DESC LIMIT 1");
    if ($q_t && $r_t = mysqli_fetch_assoc($q_t)) return (float)$r_t['tasa_bcv'];
    return 842.40;
}

$egresos_compras = 0; $egresos_os = 0; $egresos_nomina = 0; $egresos_admin = 0;
$q_com = mysqli_query($conexion, "SELECT costo, fecha_compra, moneda FROM compra WHERE estado_pago = 'Pagado' AND DATE(fecha_compra) BETWEEN '$desde' AND '$hasta'");
while ($r = mysqli_fetch_assoc($q_com)) {
    $costo = (float)$r['costo'];
    if (strtoupper(trim($r['moneda'])) === 'BS' || strtoupper(trim($r['moneda'])) === 'VES') {
        $egresos_compras += ($costo / obtenerTasaHistorica($conexion, $r['fecha_compra'])); 
    } else { $egresos_compras += $costo; }
}

$q_os = mysqli_query($conexion, "SELECT costo FROM ordenes_servicio WHERE estado_pago = 'Pagado' AND DATE(COALESCE(fecha_pago_real, fecha_orden)) BETWEEN '$desde' AND '$hasta'");
while ($r = mysqli_fetch_assoc($q_os)) $egresos_os += (float)$r['costo'];

$q_gas = mysqli_query($conexion, "SELECT monto_total FROM gastos_servicios WHERE estado_pago = 'Pagado' AND DATE(fecha) BETWEEN '$desde' AND '$hasta'");
while ($r = mysqli_fetch_assoc($q_gas)) $egresos_admin += (float)$r['monto_total'];

$q_nom = mysqli_query($conexion, "SELECT total_usd FROM nomina_lotes WHERE estado = 'Finalizada' AND DATE(fecha_pago) BETWEEN '$desde' AND '$hasta'");
while ($r = mysqli_fetch_assoc($q_nom)) $egresos_nomina += (float)$r['total_usd'];

$q_mov = mysqli_query($conexion, "SELECT monto, tipo FROM finanzas_movimientos WHERE DATE(fecha) BETWEEN '$desde' AND '$hasta'");
while ($r = mysqli_fetch_assoc($q_mov)) {
    if ($r['tipo'] === 'GASTO') $egresos_admin += (float)$r['monto'];
    else if ($r['tipo'] === 'INGRESO') $total_ingresos_reales += (float)$r['monto'];
}

$total_egresos_reales = $egresos_compras + $egresos_os + $egresos_admin + $egresos_nomina;
$balance_neto = $total_ingresos_reales - $total_egresos_reales;

// =========================================================================
// 5. AUDITORÍA: DESEMPEÑO Y ALERTAS POR EMPLEADO
// =========================================================================
$actividad_usuarios = [];
$q_audit = mysqli_query($conexion, "
    SELECT u.nombre, u.apellido, u.rol, COUNT(h.ID) as total_acciones,
           SUM(CASE WHEN h.accion IN ('CREAR', 'REGISTRAR') THEN 1 ELSE 0 END) as creaciones,
           SUM(CASE WHEN h.accion IN ('EDITAR', 'ESTADO_PAGO', 'ALTERAR ORDEN') THEN 1 ELSE 0 END) as ediciones,
           SUM(CASE WHEN h.accion IN ('ELIMINAR', 'ELIMINAR/REVERTIR') THEN 1 ELSE 0 END) as eliminaciones,
           SUM(CASE WHEN h.accion = 'ALERTA DE ROBO' THEN 1 ELSE 0 END) as robos
    FROM historial_actividad h
    JOIN usuario u ON h.id_usuario = u.ID
    WHERE DATE(h.fecha_hora) BETWEEN '$desde' AND '$hasta'
    GROUP BY u.ID
    ORDER BY total_acciones DESC
");
while ($row = mysqli_fetch_assoc($q_audit)) { $actividad_usuarios[] = $row; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre Gerencial Unifuerte</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #e9ecef; margin: 0; padding: 20px; color: #333; }
        .document-wrapper { background: #fff; max-width: 800px; margin: 0 auto; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 8px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid #FF7518; padding-bottom: 20px; margin-bottom: 30px; }
        .header img { max-height: 60px; }
        .header h2 { margin: 0; color: #0B4C8C; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 14px; font-weight: bold; }
        
        h3 { color: #FF7518; border-bottom: 2px solid #f0f0f0; padding-bottom: 8px; margin-top: 30px; font-size: 18px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background-color: #f8f9fa; color: #333; text-align: center; }
        td.amount { text-align: right; font-weight: bold; }
        
        .kpi-container { display: flex; justify-content: space-between; gap: 15px; margin-bottom: 20px; }
        .kpi-box { flex: 1; background: #f2fcf5; border: 2px solid #198754; border-radius: 8px; padding: 15px; text-align: center; }
        .kpi-box.orange { background: #fff8f0; border-color: #fd7e14; }
        .kpi-box p { margin: 0; font-size: 13px; color: #333; font-weight: bold; }
        .kpi-box h1 { margin: 10px 0 0 0; font-size: 32px; }

        .btn-download { display: block; width: 100%; max-width: 800px; margin: 0 auto 20px auto; text-align: center; background: #0B4C8C; color: #fff; padding: 15px; font-size: 18px; font-weight: bold; text-decoration: none; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .btn-download:hover { background: #083766; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .document-wrapper { box-shadow: none; border-radius: 0; padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    
    <button class="btn-download no-print" onclick="descargarCierre()">📥 Descargar Reporte Completo en PDF</button>

    <div class="document-wrapper" id="informe-pdf">
        <div class="header">
            <div>
                <h2>Cierre Gerencial</h2>
                <p>Período: <?= date('d/m/Y', strtotime($desde)) ?> al <?= date('d/m/Y', strtotime($hasta)) ?></p>
            </div>
            <img src="../img/unifuerte.jpg" alt="Unifuerte Logo" style="max-height: 60px;">
        </div>

        <h3>1. Rendimiento del Departamento de Ventas</h3>
        <table>
            <tr>
                <th style="text-align: left;">Asesor de Ventas</th>
                <th title="Total de Cotizaciones Emitidas">Cotiz. Realizadas</th>
                <th title="Cotizaciones con adelanto pagado">Pago Inicial</th>
                <th title="Cotizaciones canceladas al 100%">Pago Total</th>
                <th style="text-align: right;">Ingreso Recaudado</th>
            </tr>
            <?php if(count($ventas_usuarios) > 0): ?>
                <?php foreach($ventas_usuarios as $nombre => $datos): ?>
                <tr>
                    <td style="font-weight: bold;"><?= htmlspecialchars($nombre) ?></td>
                    <td style="text-align: center;"><?= $datos['cotizaciones'] ?></td>
                    <td style="text-align: center; color: #FF7518; font-weight:bold;"><?= $datos['pago_inicial'] ?></td>
                    <td style="text-align: center; color: #198754; font-weight:bold;"><?= $datos['pago_total'] ?></td>
                    <td class="amount" style="color: #198754;">$ <?= number_format($datos['monto'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center; color: #999;">Sin ventas en el período.</td></tr>
            <?php endif; ?>
        </table>

        <h3>Detalle de pagos del período</h3>
        <table>
            <tr>
                <th>Fecha de pago</th>
                <th>Cotización</th>
                <th>Cliente</th>
                <th>Asesor</th>
                <th>Estado</th>
                <th>Referencia / Banco</th>
                <th style="text-align: right;">Monto ($)</th>
            </tr>
            <?php if (count($pagos_periodo) > 0): ?>
                <?php foreach ($pagos_periodo as $pago): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></td>
                    <td><?= htmlspecialchars($pago['nro_cotizacion'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($pago['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($pago['vendedor_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($pago['estado_pago'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(implode(' / ', array_filter([$pago['referencia'], $pago['banco']])), ENT_QUOTES, 'UTF-8') ?: '—' ?></td>
                    <td class="amount">$ <?= number_format($pago['monto'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center; color: #999;">No hay pagos registrados en el período.</td></tr>
            <?php endif; ?>
        </table>

        <h3>2. Productividad de Logística y Taller Textil</h3>
        <div class="kpi-container">
            <div class="kpi-box orange">
                <p>Prendas terminadas en Envíos</p>
                <h1 style="color: #fd7e14;"><?= number_format($total_prendas_terminadas) ?></h1>
            </div>
            <div class="kpi-box">
                <p>Prendas Efectivamente Entregadas al Cliente</p>
                <h1 style="color: #198754;"><?= number_format($total_prendas_entregadas) ?></h1>
            </div>
            <div class="kpi-box orange">
                <p>Pedidos finalizados en Envíos</p>
                <h1 style="color: #fd7e14;"><?= number_format($total_pedidos_finalizados) ?></h1>
            </div>
        </div>

        <h3>3. Estado Actual de Envíos y Compras Textiles</h3>
        <p style="font-size: 11px; color: #666;">Existencias de órdenes activas y solicitudes textiles pendientes al <?= date('d/m/Y') ?>; estos datos corresponden al estado actual y no al rango histórico del cierre.</p>
        <div class="kpi-container">
            <div class="kpi-box orange">
                <p>Prendas en Envíos (órdenes no finalizadas)</p>
                <h1 style="color: #fd7e14;"><?= number_format($total_prendas_en_envios) ?></h1>
            </div>
            <div class="kpi-box">
                <p>Órdenes activas en Envíos</p>
                <h1 style="color: #198754;"><?= count($ordenes_envio_activas) ?></h1>
            </div>
        </div>

        <table>
            <tr>
                <th style="text-align: left;">Orden activa de Envíos</th>
                <th style="text-align: left;">Cliente</th>
                <th>Prendas</th>
            </tr>
            <?php if(count($ordenes_envio_activas) > 0): ?>
                <?php foreach($ordenes_envio_activas as $orden): ?>
                <tr>
                    <td style="font-weight: bold;"><?= htmlspecialchars($orden['nro_orden_fabrica'] ?? 'Sin número', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($orden['cliente'] ?? 'Cliente no registrado', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align: center;"><?= number_format((int)$orden['prendas']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align: center; color: #999;">No hay órdenes activas en Envíos.</td></tr>
            <?php endif; ?>
        </table>

        <h4 style="color: #0B4C8C; margin: 18px 0 8px;">Compras textiles pendientes de Envíos (<?= count($insumos_textiles_pendientes) ?> productos)</h4>
        <table>
            <tr>
                <th style="text-align: left;">Orden</th>
                <th style="text-align: left;">Cliente</th>
                <th style="text-align: left;">Producto / Insumo pendiente</th>
            </tr>
            <?php if(count($insumos_textiles_pendientes) > 0): ?>
                <?php foreach($insumos_textiles_pendientes as $insumo): ?>
                <tr>
                    <td style="font-weight: bold;"><?= htmlspecialchars($insumo['orden'] ?? 'Sin número', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($insumo['cliente'] ?? 'Cliente no registrado', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="color: #dc3545; font-weight: bold;"><?= htmlspecialchars($insumo['producto'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align: center; color: #198754; font-weight: bold;">No hay productos textiles pendientes de compra para Envíos.</td></tr>
            <?php endif; ?>
        </table>

        <h3>4. Flujo de Caja y Administración (Tesorería)</h3>
        <table style="font-size: 14px;">
            <tr><td style="text-align:left;">Total Ingresos Recaudados</td><td class="amount" style="color: #198754;">$ <?= number_format($total_ingresos_reales, 2) ?></td></tr>
            <tr><td style="text-align:left;">Egresos: Nóminas Procesadas</td><td class="amount" style="color: #dc3545;">$ <?= number_format($egresos_nomina, 2) ?></td></tr>
            <tr><td style="text-align:left;">Egresos: Insumos y Textiles Comprados</td><td class="amount" style="color: #dc3545;">$ <?= number_format($egresos_compras, 2) ?></td></tr>
            <tr><td style="text-align:left;">Egresos: Servicios Externos (Costura/Estampado)</td><td class="amount" style="color: #dc3545;">$ <?= number_format($egresos_os, 2) ?></td></tr>
            <tr><td style="text-align:left;">Gastos Operativos Administrativos</td><td class="amount" style="color: #dc3545;">$ <?= number_format($egresos_admin, 2) ?></td></tr>
            <tr style="background-color: #f8f9fa;">
                <td style="text-align:left; font-weight: bold; font-size: 16px;">BALANCE NETO GENERADO</td>
                <td class="amount" style="font-size: 18px; color: <?= $balance_neto >= 0 ? '#198754' : '#dc3545' ?>;">$ <?= number_format($balance_neto, 2) ?></td>
            </tr>
        </table>

        <h3>5. Auditoría de Actividad y Desempeño por Empleado</h3>
        <table style="font-size: 11px;">
            <tr>
                <th style="text-align: left;">Usuario (Empleado)</th>
                <th>Rol</th>
                <th>Operaciones Creadas</th>
                <th>Registros Editados</th>
                <th style="color:#dc3545;">Borrados ⚠️</th>
                <th style="color:#dc3545;">Alertas de Robo (DLP) 🚨</th>
                <th style="background:#e9ecef;">Total General</th>
            </tr>
            <?php if(count($actividad_usuarios) > 0): ?>
                <?php foreach($actividad_usuarios as $usr): ?>
                <tr>
                    <td style="font-weight: bold;"><?= htmlspecialchars(trim($usr['nombre'] . ' ' . $usr['apellido'])) ?></td>
                    <td style="text-align: center; text-transform: uppercase;"><?= htmlspecialchars(str_replace('_', ' ', $usr['rol'])) ?></td>
                    <td style="text-align: center; color: #198754; font-weight:bold;"><?= $usr['creaciones'] ?></td>
                    <td style="text-align: center; color: #fd7e14; font-weight:bold;"><?= $usr['ediciones'] ?></td>
                    <td style="text-align: center; color: #dc3545; font-weight:bold;"><?= $usr['eliminaciones'] ?></td>
                    <td style="text-align: center; color: #dc3545; font-weight:bold; background-color: #fff5f5;"><?= $usr['robos'] ?></td>
                    <td style="text-align: center; font-weight: bold; background: #f8f9fa;"><?= $usr['total_acciones'] ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center; color: #999;">Sin actividad registrada en la auditoría.</td></tr>
            <?php endif; ?>
        </table>
        
        <div style="text-align: center; font-size: 10px; color: #999; margin-top: 40px; border-top: 1px solid #eee; padding-top: 10px;">
            Reporte Generado Automáticamente por el Sistema de Inteligencia Gerencial Unifuerte.
        </div>
    </div>

    <script>
    window.onload = function() {
        const btn = document.querySelector('.btn-download');
        btn.innerHTML = '⏳ Preparando PDF...';
        btn.disabled = true;

        const opt = {
            margin:       10,
            filename:     'Cierre_Gerencial_<?= $desde ?>_al_<?= $hasta ?>.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        setTimeout(() => {
            html2pdf().set(opt).from(document.getElementById('informe-pdf')).save().then(() => {
                btn.innerHTML = '✅ Descarga Exitosa. (Click para descargar de nuevo)';
                btn.disabled = false;
            });
        }, 1000); 
    }

    function descargarCierre() {
        window.onload(); 
    }
    </script>
</body>
</html>