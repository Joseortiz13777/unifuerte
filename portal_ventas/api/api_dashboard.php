<?php
ob_start();
session_start();
error_reporting(0);
require_once '../../conexion.php';
require_once '../ingresos_dashboard.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'msg' => 'Sesión expirada']); exit;
}

$id_usuario = intval($_SESSION['usuario_id']);
$q_usr = mysqli_query($conexion, "SELECT rol, permisos FROM usuario WHERE ID = $id_usuario");
$datos_usr = mysqli_fetch_assoc($q_usr);
$permisos_arr = explode(',', $datos_usr['permisos'] ?? '');
$rol_real = strtolower($datos_usr['rol']);

// Control estricto de Vectores
$es_gerencia = in_array('20', $permisos_arr) || $rol_real === 'master';
$es_personal = in_array('19', $permisos_arr);

if (!$es_gerencia && !$es_personal) {
    ob_clean();
    echo json_encode(['success' => false, 'msg' => 'Sin permisos para ver estadísticas']); exit;
}

// Crear tablas/columnas en BD automáticamente si no existen
mysqli_query($conexion, "ALTER TABLE usuario ADD COLUMN IF NOT EXISTS meta_ventas DECIMAL(15,2) DEFAULT 0.00");
mysqli_query($conexion, "ALTER TABLE usuario ADD COLUMN IF NOT EXISTS tiene_meta TINYINT(1) DEFAULT 0");
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS metas_empresa (id INT PRIMARY KEY, meta_global DECIMAL(15,2) DEFAULT 0.00)");

// Columnas para el Robot (Cron Job)
mysqli_query($conexion, "ALTER TABLE metas_empresa ADD COLUMN IF NOT EXISTS correo_cierre VARCHAR(255) DEFAULT NULL");
mysqli_query($conexion, "ALTER TABLE metas_empresa ADD COLUMN IF NOT EXISTS freq_dias INT DEFAULT 1");
mysqli_query($conexion, "ALTER TABLE metas_empresa ADD COLUMN IF NOT EXISTS freq_hora VARCHAR(10) DEFAULT '18:00'");
mysqli_query($conexion, "ALTER TABLE metas_empresa ADD COLUMN IF NOT EXISTS ultimo_envio DATE DEFAULT NULL");

if(mysqli_num_rows(mysqli_query($conexion, "SELECT id FROM metas_empresa WHERE id=1")) == 0) {
    mysqli_query($conexion, "INSERT INTO metas_empresa (id, meta_global) VALUES (1, 0.00)");
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? 'cargar';

if ($accion == 'guardar_meta_global') {
    if (!$es_gerencia) { ob_clean(); echo json_encode(['success'=>false, 'msg'=>'Sin permisos']); exit; }
    $meta = floatval($_POST['meta']);
    mysqli_query($conexion, "UPDATE metas_empresa SET meta_global = $meta WHERE id=1");
    mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usuario, 'EDITAR', 'metas_empresa', 1, NOW(), 'Actualizó la meta global de ventas a $$meta')");
    ob_clean();
    echo json_encode(['success'=>true]); exit;
}

if ($accion == 'guardar_meta_vendedor') {
    if (!$es_gerencia) { ob_clean(); echo json_encode(['success'=>false, 'msg'=>'Sin permisos']); exit; }
    $id_v = intval($_POST['id_vendedor']);
    $meta = floatval($_POST['meta']);
    mysqli_query($conexion, "UPDATE usuario SET meta_ventas = $meta WHERE ID=$id_v");
    mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usuario, 'EDITAR', 'usuario', $id_v, NOW(), 'Asignó meta individual de $$meta al vendedor ID $id_v')");
    ob_clean();
    echo json_encode(['success'=>true]); exit;
}

if ($accion == 'cargar') {
    $desde = $_GET['desde'] ?? date('Y-m-01'); 
    $hasta = $_GET['hasta'] ?? date('Y-m-t');
    $vendedor_req = $_GET['vendedor'] ?? '';
    
    $where = "c.estado_pago IN ('Pagado', 'Pago Medio', 'Pago Inicial') AND c.pausado = 0 AND DATE(c.fecha_emision) BETWEEN '$desde' AND '$hasta'";

    $modo_general = false;
    $meta_actual = 0;
    $titulo_meta = "";

    if ($es_gerencia) {
        if (!empty($vendedor_req)) {
            $vendedor_req = intval($vendedor_req);
            $where .= " AND c.id_vendedor = $vendedor_req";
            $r_vend = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT meta_ventas, nombre FROM usuario WHERE ID=$vendedor_req"));
            $meta_actual = floatval($r_vend['meta_ventas'] ?? 0);
            $titulo_meta = "Meta de " . ($r_vend['nombre'] ?? 'Vendedor');
        } else {
            $modo_general = true;
            $r_meta = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT meta_global FROM metas_empresa WHERE id=1"));
            $meta_actual = floatval($r_meta['meta_global']);
            $titulo_meta = "Meta Global Empresa";
        }
    } else {
        $where .= " AND c.id_vendedor = $id_usuario";
        $r_vend = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT meta_ventas FROM usuario WHERE ID=$id_usuario"));
        $meta_actual = floatval($r_vend['meta_ventas'] ?? 0);
        $titulo_meta = "Mi Meta Asignada";
    }

    $id_vendedor_pagos = $es_gerencia ? intval($vendedor_req) : $id_usuario;
    $pagos_periodo = obtenerPagosCotizacionesDashboard($conexion, $desde, $hasta, $id_vendedor_pagos);
    if ($pagos_periodo === false) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se pudieron cargar los pagos del período.']);
        exit;
    }

    $ingresos_periodo = 0;
    $cotizaciones_periodo = [];
    foreach ($pagos_periodo as $pago) {
        $ingresos_periodo += $pago['monto'];
        $cotizaciones_periodo[$pago['id_cotizacion']] = true;
    }

    $q_prod = mysqli_query($conexion, "SELECT d.tipo_prenda as nombre, SUM(d.cantidad) as cantidad, SUM(d.subtotal_linea) as monto FROM detalle_cotizacion d JOIN cotizacion c ON d.id_cotizacion = c.ID WHERE $where GROUP BY d.tipo_prenda ORDER BY monto DESC LIMIT 10");
    $prods = []; while($r = mysqli_fetch_assoc($q_prod)) $prods[] = $r;

    $q_cat = mysqli_query($conexion, "SELECT cat.nombre, SUM(d.subtotal_linea) as monto FROM detalle_cotizacion d JOIN cotizacion c ON d.id_cotizacion = c.ID LEFT JOIN productos p ON d.id_producto = p.id LEFT JOIN subcategorias s ON p.id_subcategoria = s.id LEFT JOIN categorias cat ON s.id_categoria = cat.id WHERE $where AND cat.nombre IS NOT NULL GROUP BY cat.id ORDER BY monto DESC LIMIT 10");
    $cats = []; while($r = mysqli_fetch_assoc($q_cat)) $cats[] = $r;

    $q_cli = mysqli_query($conexion, "SELECT cli.nombre, SUM(c.total) as gastado, COUNT(c.ID) as compras FROM cotizacion c JOIN cliente cli ON c.id_cliente = cli.ID WHERE $where GROUP BY cli.ID ORDER BY gastado DESC LIMIT 20");
    $clientes = []; while($r = mysqli_fetch_assoc($q_cli)) $clientes[] = $r;

    $vendedores = [];
    if ($es_gerencia && !empty($pagos_periodo)) {
        $ids_vendedores_con_pago = array_values(array_unique(array_filter(array_column($pagos_periodo, 'id_vendedor'))));
        if (!empty($ids_vendedores_con_pago)) {
            $ids_sql = implode(',', array_map('intval', $ids_vendedores_con_pago));
            $q_vend = mysqli_query($conexion, "
                SELECT u.ID, CONCAT_WS(' ', u.nombre, NULLIF(u.apellido, '')) as nombre,
                       u.meta_ventas, u.tiene_meta
                FROM usuario u
                WHERE u.ID IN ($ids_sql)
                ORDER BY nombre
            ");
            while($r = mysqli_fetch_assoc($q_vend)) $vendedores[] = $r;
        }
    }

    function calcularPrendasDashboard($conexion, $idOrden) {
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

    $total_prendas_mes = 0;
    $total_pedidos_finalizados_mes = 0;
    $desde_sql = mysqli_real_escape_string($conexion, $desde);
    $hasta_sql = mysqli_real_escape_string($conexion, $hasta);

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

    $q_notas = mysqli_query($conexion, "
        SELECT i.cantidad
        FROM nota_entrega_manual n
        JOIN nota_entrega_manual_items i ON n.id = i.id_nota
        WHERE n.estado = 'Activa'
          AND DATE(n.fecha) BETWEEN '$desde_sql' AND '$hasta_sql'
    ");
    if (!$q_notas) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se pudieron cargar las prendas de las notas de entrega.']);
        exit;
    }
    while ($r = mysqli_fetch_assoc($q_notas)) {
        $total_prendas_mes += (int)$r['cantidad'];
    }

    $q_track = mysqli_query($conexion, "
        SELECT t.id_orden_fabrica, t.prendas_totales
        FROM tracking_orden t
        WHERE t.estado_general IN ('Terminado', 'Entregado')
          AND t.fecha_terminado >= '$desde_sql'
          AND t.fecha_terminado < DATE_ADD('$hasta_sql', INTERVAL 1 DAY)
    ");
    if (!$q_track) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se pudieron cargar las órdenes finalizadas de Envíos.']);
        exit;
    }
    while ($r = mysqli_fetch_assoc($q_track)) {
        $cantidad = (int)$r['prendas_totales'];
        if ($cantidad <= 0) {
            $cantidad = calcularPrendasDashboard($conexion, (int)$r['id_orden_fabrica']);
        }
        $total_prendas_mes += $cantidad;
        $total_pedidos_finalizados_mes++;
    }

    ob_clean();
    echo json_encode([
        'success' => true, 
        'ingresos' => $ingresos_periodo,
        'cant_ventas' => count($cotizaciones_periodo),
        'pagos_periodo' => $pagos_periodo,
        'meta' => $meta_actual,
        'titulo_meta' => $titulo_meta,
        'modo_general' => $modo_general,
        'productos' => $prods, 
        'categorias' => $cats, 
        'clientes' => $clientes, 
        'vendedores' => $vendedores,
        'prendas_mes' => $total_prendas_mes,
        'pedidos_finalizados_mes' => $total_pedidos_finalizados_mes
    ]);
    exit;
}

// =========================================================================================
// MÓDULO EXCLUSIVO MASTER: CIERRE GERENCIAL (PROGRAMACIÓN Y ENVÍO DE LINKS)
// =========================================================================================

// Cargar la configuración del robot al abrir el modal
if ($accion == 'cargar_config_cierre') {
    $q = mysqli_query($conexion, "SELECT correo_cierre, freq_dias, freq_hora FROM metas_empresa WHERE id=1");
    if ($q) {
        ob_clean(); echo json_encode(['success'=>true, 'data'=>mysqli_fetch_assoc($q)]); exit;
    }
    ob_clean(); echo json_encode(['success'=>false]); exit;
}

// Guardar la configuración del robot desde el modal
if ($accion == 'guardar_config_cierre') {
    if ($rol_real !== 'master') { ob_clean(); echo json_encode(['success'=>false, 'msg'=>'Solo Master']); exit; }
    $email = mysqli_real_escape_string($conexion, $_POST['correo']);
    $dias = (int)$_POST['dias'];
    $hora = mysqli_real_escape_string($conexion, $_POST['hora']);
    
    mysqli_query($conexion, "UPDATE metas_empresa SET correo_cierre='$email', freq_dias=$dias, freq_hora='$hora' WHERE id=1");
    mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usuario, 'SISTEMA', 'CRON', 1, NOW(), 'Programó automatización de Cierre Gerencial (Email: $email, Días: $dias, Hora: $hora)')");
    ob_clean(); echo json_encode(['success'=>true]); exit;
}

// Envío Manual del Cierre
if ($accion == 'enviar_cierre_gerencial') {
    if ($rol_real !== 'master') {
        ob_clean(); echo json_encode(['success' => false, 'msg' => 'Solo Master']); exit;
    }

    $email_destino = mysqli_real_escape_string($conexion, trim($_POST['email']));
    $desde = mysqli_real_escape_string($conexion, $_POST['desde']);
    $hasta = mysqli_real_escape_string($conexion, $_POST['hasta']);
    
    if (!filter_var($email_destino, FILTER_VALIDATE_EMAIL)) {
        ob_clean(); echo json_encode(['success' => false, 'msg' => 'Correo inválido']); exit;
    }

    $token = md5('cierre' . $desde . $hasta . 'unifuerte');
    $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'sistema.soyunifuerte.com';
    
    // RUTA PARA DESCARGAR EL PDF (con todo el informe desglosado)
    $link_pdf = "$protocolo://$host/portal_ventas/cierre_pdf.php?desde=$desde&hasta=$hasta&t=$token";

    $fecha_formato = date('d/m/Y', strtotime($desde)) . " al " . date('d/m/Y', strtotime($hasta));
    
    $html_body = "
    <div style='font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 40px; text-align: center;'>
        <div style='background-color: #ffffff; max-width: 500px; margin: 0 auto; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);'>
            <h2 style='color: #0B4C8C; margin-top: 0;'>Cierre Gerencial de Operaciones</h2>
            <p style='color: #555; font-size: 15px;'>El reporte detallado de <b>Ventas, Producción, Tesorería y Auditoría de Empleados</b> para el período del <strong>$fecha_formato</strong> ha sido generado con éxito.</p>
            <br>
            <a href='$link_pdf' style='display: inline-block; background-color: #FF7518; color: #ffffff; padding: 15px 30px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 16px;'>Descargar Cierre Gerencial en PDF</a>
            <br><br><br>
            <hr style='border: none; border-top: 1px solid #eee;'>
            <p style='color: #aaa; font-size: 11px; margin-bottom: 0;'>
                Este enlace contiene información confidencial.<br>
                &copy; Unifuerte C.A. Todos los derechos reservados.
            </p>
        </div>
    </div>";

    $asunto = "Cierre Gerencial: " . date('d/m/Y', strtotime($desde)) . " al " . date('d/m/Y', strtotime($hasta));
    $cabeceras  = "MIME-Version: 1.0\r\n";
    $cabeceras .= "Content-type: text/html; charset=UTF-8\r\n";
    $cabeceras .= "From: Unifuerte Operaciones <noreply@soyunifuerte.com>\r\n";

    if (@mail($email_destino, $asunto, $html_body, $cabeceras)) {
        mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usuario, 'SISTEMA', 'REPORTES', 0, NOW(), 'Generó y envió enlace de Cierre Gerencial al correo: $email_destino')");
        ob_clean(); echo json_encode(['success' => true]);
    } else {
        ob_clean(); echo json_encode(['success' => false, 'msg' => 'El servidor bloqueó el envío de correo. Funciones mail deshabilitadas por hosting.']);
    }
    exit;
}
?>