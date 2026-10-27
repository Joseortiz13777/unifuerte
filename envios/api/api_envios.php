<?php
session_start();
require_once '../../conexion.php';
require_once '../../garantia_textil.php';
header('Content-Type: application/json');

// --- SEGURIDAD Y PRIVILEGIOS ---
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesión expirada']); exit;
}
$id_usr = intval($_SESSION['usuario_id']);
$q_usr = mysqli_query($conexion, "SELECT rol, permisos FROM usuario WHERE ID = $id_usr");
$datos_usr = mysqli_fetch_assoc($q_usr);
$permisos_arr = explode(',', $datos_usr['permisos'] ?? '');
$rol_actual = strtolower(trim($datos_usr['rol'] ?? ''));
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$es_accion_prealerta = in_array($accion, ['listar_prealertas_produccion', 'marcar_prealerta_revisada', 'solicitar_insumo', 'editar_insumo_historial'], true);
if ($rol_actual !== 'master' && !in_array('36', $permisos_arr) && !($es_accion_prealerta && (in_array('13', $permisos_arr) || in_array('37', $permisos_arr)))) {
    echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [36] para gestionar Envíos']); exit;
}

// ==============================================================================
// FUNCIÓN CENTRAL DE AUDITORÍA
// ==============================================================================
function registrar_auditoria($con, $id_usr, $accion, $tabla, $id_reg, $detalle) {
    $acc = mysqli_real_escape_string($con, $accion);
    $tab = mysqli_real_escape_string($con, $tabla);
    $det = mysqli_real_escape_string($con, $detalle);
    mysqli_query($con, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usr, '$acc', '$tab', $id_reg, NOW(), '$det')");
}

// ==============================================================================
// FUNCIÓN INTELIGENTE PARA CALCULAR PRENDAS DE LA ORDEN (EXACTITUD 100%)
// ==============================================================================
function calcular_prendas_orden($conexion, $id_orden) {
    $sql = "SELECT id_cotizacion FROM orden_fabrica WHERE ID = $id_orden";
    $res = mysqli_query($conexion, $sql);
    if(!$res) return 0;
    $orden = mysqli_fetch_assoc($res);
    if(!$orden || empty($orden['id_cotizacion'])) return 0;

    $id_cotizacion = intval($orden['id_cotizacion']);
    $total_general = 0;

    $q_cot = mysqli_query($conexion, "SELECT tipo_prenda, cantidad FROM detalle_cotizacion WHERE id_cotizacion = $id_cotizacion");
    if ($q_cot) {
        while($r = mysqli_fetch_assoc($q_cot)) {
            $n = mb_strtolower(trim($r['tipo_prenda']), 'UTF-8');
            
            if (
                strpos($n, 'arte final') !== false || 
                strpos($n, 'bordado') !== false || 
                strpos($n, 'vinil') !== false || 
                strpos($n, 'dtf') !== false || 
                strpos($n, 'serigraf') !== false || 
                strpos($n, 'servicio') !== false || 
                strpos($n, 'matriz') !== false || 
                strpos($n, 'ponchado') !== false || 
                strpos($n, 'diseño') !== false || 
                strpos($n, 'delivery') !== false || 
                strpos($n, 'flete') !== false || 
                strpos($n, 'descuento') !== false || 
                strpos($n, 'cobro adicional') !== false || 
                strpos($n, 'parche') !== false || 
                strpos($n, 'tallas extras') !== false || 
                strpos($n, 'talla extra') !== false || 
                strpos($n, 'empaque') !== false || 
                strpos($n, 'impresion') !== false || 
                strpos($n, 'impresión') !== false
            ) {
                continue;
            }
            
            $total_general += intval($r['cantidad']);
        }
    }
    
    if ($total_general == 0) {
        $q_det = mysqli_query($conexion, "SELECT tipo_prenda, cantidad FROM detalle_orden_fabrica WHERE id_orden_fabrica = $id_orden");
        if ($q_det) {
            while($r = mysqli_fetch_assoc($q_det)) {
                $n = mb_strtolower(trim($r['tipo_prenda']), 'UTF-8');
                if (strpos($n, 'bordado') !== false || strpos($n, 'tallas extras') !== false || strpos($n, 'talla extra') !== false) continue;
                $total_general += intval($r['cantidad']);
            }
        }
    }

    return $total_general;
}

// ================= INSTALADOR AUTOMÁTICO =================
mysqli_query($conexion, "ALTER TABLE tracking_fases MODIFY COLUMN foto TEXT");
mysqli_query($conexion, "ALTER TABLE tracking_fases ADD COLUMN IF NOT EXISTS observacion TEXT DEFAULT NULL");
mysqli_query($conexion, "ALTER TABLE tracking_fases ADD COLUMN IF NOT EXISTS foto_leyendas TEXT DEFAULT NULL");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS noti_wa INT DEFAULT 0");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS prealerta_produccion TINYINT NOT NULL DEFAULT 0 COMMENT '0 sin prealerta, 1 pendiente, 2 revisada'");

mysqli_query($conexion, "ALTER TABLE tracking_orden ADD COLUMN IF NOT EXISTS prioridad INT DEFAULT 0");
mysqli_query($conexion, "ALTER TABLE tracking_orden ADD COLUMN IF NOT EXISTS fecha_terminado DATETIME DEFAULT NULL");
mysqli_query($conexion, "ALTER TABLE tracking_orden ADD COLUMN IF NOT EXISTS prendas_totales INT DEFAULT 0");

$sql_tabla_insumos = "CREATE TABLE IF NOT EXISTS insumos_faltantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL DEFAULT 0,
    id_cotizacion INT DEFAULT NULL,
    descripcion TEXT NOT NULL,
    solicitante VARCHAR(100),
    estado INT DEFAULT 0,
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    check_estados TEXT DEFAULT NULL
)";
mysqli_query($conexion, $sql_tabla_insumos);
mysqli_query($conexion, "ALTER TABLE insumos_faltantes ADD COLUMN IF NOT EXISTS id_cotizacion INT DEFAULT NULL");

if (!asegurarTablaGarantiasTextiles($conexion)) {
    echo json_encode(['success' => false, 'msg' => 'No se pudo preparar el módulo de garantías textiles.']);
    exit;
}

if ($accion === 'listar_garantias_textiles') {
    $query = mysqli_query(
        $conexion,
        "SELECT g.id, g.id_orden, g.id_cotizacion, g.id_cliente, g.motivo, g.evidencias_json,
                g.estado, g.aprobacion_estado, g.historial_json, g.creado_en, g.actualizado_en, g.finalizado_en,
                o.nro_orden_fabrica, c.nro_cotizacion, cli.nombre AS cliente,
                t.hash_publico
         FROM garantias_textiles g
         JOIN orden_fabrica o ON o.ID = g.id_orden
         JOIN cotizacion c ON c.ID = g.id_cotizacion
         JOIN cliente cli ON cli.ID = g.id_cliente
         LEFT JOIN tracking_orden t ON t.id_orden_fabrica = g.id_orden
         WHERE g.aprobacion_estado = 'Aprobada'
         ORDER BY (g.estado = 'Finalizada') ASC, g.actualizado_en DESC, g.id DESC"
    );
    if (!$query) {
        error_log('Envíos: no se pudieron listar garantías textiles: ' . mysqli_error($conexion));
        echo json_encode(['success' => false, 'msg' => 'No se pudo cargar la bandeja de garantías textiles.']);
        exit;
    }
    $garantias = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $row['evidencias'] = json_decode((string)$row['evidencias_json'], true);
        $row['historial'] = json_decode((string)$row['historial_json'], true);
        if (!is_array($row['evidencias'])) $row['evidencias'] = [];
        if (!is_array($row['historial'])) $row['historial'] = [];
        $garantias[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $garantias, 'estados' => estadosGarantiaTextil()], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($accion === 'actualizar_garantia_textil') {
    $idGarantia = filter_var($_POST['id_garantia'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $estadoNuevo = trim((string)($_POST['estado'] ?? ''));
    if ($idGarantia === false || $idGarantia === null || !in_array($estadoNuevo, estadosGarantiaTextil(), true)) {
        echo json_encode(['success' => false, 'msg' => 'La garantía o el estado seleccionado no es válido.']);
        exit;
    }

    if (!mysqli_begin_transaction($conexion)) {
        error_log('Envíos: no se pudo iniciar la actualización de la garantía: ' . mysqli_error($conexion));
        echo json_encode(['success' => false, 'msg' => 'No se pudo iniciar la actualización de la garantía.']);
        exit;
    }
    $stmtGarantia = mysqli_prepare($conexion, "SELECT id_cotizacion, estado, historial_json FROM garantias_textiles WHERE id = ? AND aprobacion_estado = 'Aprobada' LIMIT 1 FOR UPDATE");
    if (!$stmtGarantia) {
        mysqli_rollback($conexion);
        error_log('Envíos: no se pudo preparar la actualización de garantía: ' . mysqli_error($conexion));
        echo json_encode(['success' => false, 'msg' => 'No se pudo actualizar la garantía.']);
        exit;
    }
    mysqli_stmt_bind_param($stmtGarantia, 'i', $idGarantia);
    if (!mysqli_stmt_execute($stmtGarantia)) {
        mysqli_rollback($conexion);
        error_log('Envíos: no se pudo consultar la garantía: ' . mysqli_stmt_error($stmtGarantia));
        mysqli_stmt_close($stmtGarantia);
        echo json_encode(['success' => false, 'msg' => 'No se pudo consultar la garantía.']);
        exit;
    }
    $garantiaActual = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtGarantia)) ?: null;
    mysqli_stmt_close($stmtGarantia);
    if (!$garantiaActual) {
        mysqli_rollback($conexion);
        echo json_encode(['success' => false, 'msg' => 'No se encontró una garantía aprobada para procesar.']);
        exit;
    }
    $estadosGarantia = estadosGarantiaTextil();
    $indiceActual = array_search($garantiaActual['estado'], $estadosGarantia, true);
    $indiceNuevo = array_search($estadoNuevo, $estadosGarantia, true);
    if ($indiceActual === false || $indiceNuevo === false || $indiceNuevo < $indiceActual || $indiceNuevo > $indiceActual + 1) {
        mysqli_rollback($conexion);
        echo json_encode(['success' => false, 'msg' => 'Avanza la garantía una fase a la vez; no se puede retroceder ni omitir fases.']);
        exit;
    }

    $historial = json_decode((string)$garantiaActual['historial_json'], true);
    if (!is_array($historial)) $historial = [];
    if ($garantiaActual['estado'] !== $estadoNuevo) {
        $historial[] = [
            'estado' => $estadoNuevo,
            'fecha' => date('c'),
            'actor' => (string)($datos_usr['rol'] ?? 'Logística'),
            'usuario' => $id_usr
        ];
    }
    $historialJson = json_encode($historial, JSON_UNESCAPED_UNICODE);
    if (!is_string($historialJson)) {
        mysqli_rollback($conexion);
        echo json_encode(['success' => false, 'msg' => 'No se pudo guardar el historial de fases.']);
        exit;
    }

    $stmtUpdate = mysqli_prepare(
        $conexion,
        "UPDATE garantias_textiles
         SET estado = ?, historial_json = ?, id_usuario_actualiza = ?,
             finalizado_en = IF(? = 'Finalizada', NOW(), NULL)
         WHERE id = ? AND estado = ?"
    );
    if (!$stmtUpdate) {
        mysqli_rollback($conexion);
        error_log('Envíos: no se pudo preparar el cambio de estado de garantía: ' . mysqli_error($conexion));
        echo json_encode(['success' => false, 'msg' => 'No se pudo actualizar la garantía.']);
        exit;
    }
    mysqli_stmt_bind_param($stmtUpdate, 'ssisis', $estadoNuevo, $historialJson, $id_usr, $estadoNuevo, $idGarantia, $garantiaActual['estado']);
    $updated = mysqli_stmt_execute($stmtUpdate);
    mysqli_stmt_close($stmtUpdate);
    if (!$updated) {
        mysqli_rollback($conexion);
        error_log('Envíos: falló el cambio de estado de garantía: ' . mysqli_error($conexion));
        echo json_encode(['success' => false, 'msg' => 'No se pudo actualizar el estado de la garantía.']);
        exit;
    }

    $idCotizacion = (int)$garantiaActual['id_cotizacion'];
    $notificacion = $estadoNuevo === 'Finalizada' ? 5 : 4;
    $stmtNotificacion = mysqli_prepare(
        $conexion,
        "UPDATE cotizacion
         SET noti_wa = IF(
             ? = 5 AND EXISTS (
                 SELECT 1 FROM garantias_textiles g
                 WHERE g.id_cotizacion = cotizacion.ID AND g.estado <> 'Finalizada'
             ),
             4,
             ?
         )
         WHERE ID = ?"
    );
    if (!$stmtNotificacion) {
        mysqli_rollback($conexion);
        error_log('Envíos: no se pudo preparar la notificación de Ventas para la garantía.');
        echo json_encode(['success' => false, 'msg' => 'La garantía cambió, pero no se pudo actualizar el aviso de Ventas.']);
        exit;
    }
    mysqli_stmt_bind_param($stmtNotificacion, 'iii', $notificacion, $notificacion, $idCotizacion);
    if (!mysqli_stmt_execute($stmtNotificacion)) {
        mysqli_rollback($conexion);
        error_log('Envíos: no se pudo activar la notificación de Ventas: ' . mysqli_stmt_error($stmtNotificacion));
        mysqli_stmt_close($stmtNotificacion);
        echo json_encode(['success' => false, 'msg' => 'La garantía cambió, pero no se pudo actualizar el aviso de Ventas.']);
        exit;
    }
    mysqli_stmt_close($stmtNotificacion);
    if (!mysqli_commit($conexion)) {
        mysqli_rollback($conexion);
        error_log('Envíos: no se pudo confirmar la actualización de la garantía.');
        echo json_encode(['success' => false, 'msg' => 'No se pudo confirmar la actualización de la garantía.']);
        exit;
    }
    registrar_auditoria(
        $conexion,
        $id_usr,
        'EDITAR',
        'garantias_textiles',
        (int)$idGarantia,
        "Actualizó la garantía textil a '$estadoNuevo'."
    );

    echo json_encode(['success' => true, 'msg' => 'Estado de la garantía actualizado.']);
    exit;
}

$sql_tabla_ficha = "CREATE TABLE IF NOT EXISTS ficha_pago_envio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL UNIQUE,
    tasa_bcv DECIMAL(12,4) DEFAULT 0.00,
    porc_oficina DECIMAL(5,2) DEFAULT 10.00,
    porc_vendedor DECIMAL(5,2) DEFAULT 5.00,
    monto_abonos_manual DECIMAL(12,2) DEFAULT NULL,
    consumos_json TEXT DEFAULT NULL,
    estado_ficha VARCHAR(50) DEFAULT 'Pendiente',
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conexion, $sql_tabla_ficha);

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS nota_entrega_manual (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nro_nota VARCHAR(50),
    fecha DATE,
    cliente_nombre VARCHAR(255),
    cedula_rif VARCHAR(50),
    direccion TEXT,
    telefono VARCHAR(50),
    email VARCHAR(255),
    comprador VARCHAR(100),
    estado VARCHAR(20) DEFAULT 'Activa',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS nota_entrega_manual_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_nota INT NOT NULL,
    prenda VARCHAR(255),
    talla VARCHAR(100),
    cantidad INT,
    FOREIGN KEY (id_nota) REFERENCES nota_entrega_manual(id) ON DELETE CASCADE
)");

$FASES_ESTANDAR = [
    ['nombre' => 'Diseño de Tallas', 'icono' => '📏'],
    ['nombre' => 'Patronaje', 'icono' => '📐'],
    ['nombre' => 'Impresión', 'icono' => '🖨️'],
    ['nombre' => 'Sublimación', 'icono' => '👕'],
    ['nombre' => 'Corte', 'icono' => '✂️'],
    ['nombre' => 'Confección', 'icono' => '🪡'],
    ['nombre' => 'Bordado', 'icono' => '🧵'],
    ['nombre' => 'Auditoría', 'icono' => '🔍'],
    ['nombre' => 'Empacado', 'icono' => '📦'],
    ['nombre' => 'Listo para entregar', 'icono' => '🚚']
];

if ($accion == 'listar_prealertas_produccion') {
    $sql_prealertas = "
        SELECT cot.ID, cot.nro_cotizacion, cot.fecha_emision, cot.prealerta_produccion,
               cot.total, cot.iva, cot.igtf, cot.pago_valor_usd, cot.pago_retenciones, cot.estado_pago,
               (SELECT f.tasa_bcv FROM factura_fiscal_cotizacion f WHERE f.id_cotizacion = cot.ID AND (f.estado IS NULL OR f.estado = 'Activa') ORDER BY f.numero_factura DESC LIMIT 1) AS tasa_factura_fiscal,
               cli.nombre AS cliente, u.nombre AS vendedor_nombre, u.apellido AS vendedor_apellido
        FROM cotizacion cot
        LEFT JOIN cliente cli ON cli.ID = cot.id_cliente
        LEFT JOIN usuario u ON u.ID = cot.id_vendedor
        LEFT JOIN orden_fabrica o ON o.id_cotizacion = cot.ID
        WHERE cot.enviado_logistica = 0
          AND (cot.pausado IS NULL OR cot.pausado = 0)
          AND o.ID IS NULL
          AND UPPER(COALESCE(cot.tipo_cotizacion, '')) <> 'MUESTRA'
        ORDER BY cot.ID DESC
    ";
    $q_prealertas = mysqli_query($conexion, $sql_prealertas);
    if (!$q_prealertas) {
        echo json_encode(['success' => false, 'msg' => 'No se pudieron cargar las prealertas de producción.']);
        exit;
    }

    $prealertas = [];
    while ($cotizacion = mysqli_fetch_assoc($q_prealertas)) {
        $id_cotizacion = intval($cotizacion['ID']);

        // Calcular total abonado (pagos en USD + retenciones convertidas a USD)
        $total_abonado = 0.0;
        if (trim((string)($cotizacion['pago_valor_usd'] ?? '')) !== '') {
            $texto_pagos = str_replace(['ert{}', '<br>', '<br/>', '<br />', "\r", "\n"], '|', (string)$cotizacion['pago_valor_usd']);
            $partes = preg_split('/\s*\|\s*/', trim($texto_pagos, " |\t\n\r\0\x0B"), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($partes as $p) {
                $num = preg_replace('/[^0-9.-]/', '', $p);
                if ($num !== '') {
                    $total_abonado += round(floatval($num), 2);
                }
            }
        }

        $retencionesJson = trim((string)($cotizacion['pago_retenciones'] ?? ''));
        if ($retencionesJson !== '') {
            $retArr = json_decode($retencionesJson, true);
            if (is_array($retArr)) {
                $tasaFac = floatval($cotizacion['tasa_factura_fiscal'] ?? 0);
                foreach ($retArr as $ret) {
                    if (is_array($ret)) {
                        $tasaItem = $tasaFac > 0 ? $tasaFac : floatval($ret['tasa_bcv'] ?? 0);
                        $montoBs = floatval($ret['iva_bs'] ?? 0) + floatval($ret['islr_bs'] ?? 0) + floatval($ret['municipal_bs'] ?? 0);
                        if ($montoBs > 0 && $tasaItem > 0) {
                            $total_abonado += ($montoBs / $tasaItem);
                        }
                    }
                }
            }
        }
        $total_abonado = round($total_abonado, 2);

        $total_cot = round(floatval($cotizacion['total'] ?? 0), 2);
        if (strtolower(trim($cotizacion['estado_pago'] ?? '')) === 'pagado' && $total_abonado <= 0) {
            $total_abonado = $total_cot;
        }

        // Base imponible y cálculo del 40% mínimo requerido para Prealerta de Producción
        $base_prealerta = max(0, round($total_cot - floatval($cotizacion['iva'] ?? 0) - floatval($cotizacion['igtf'] ?? 0), 2));
        $minimo_40 = round($base_prealerta * 0.40, 2);

        $cumple_40 = ($total_abonado > 0 && $total_abonado >= ($minimo_40 - 0.10));
        $estado_pre = intval($cotizacion['prealerta_produccion'] ?? 0);

        if (!$cumple_40 && $estado_pre === 0) {
            continue;
        }

        if ($cumple_40 && $estado_pre === 0) {
            $estado_pre = 1;
            mysqli_query($conexion, "UPDATE cotizacion SET prealerta_produccion = 1 WHERE ID = $id_cotizacion AND prealerta_produccion = 0");
        } elseif (!$cumple_40 && $estado_pre > 0) {
            mysqli_query($conexion, "UPDATE cotizacion SET prealerta_produccion = 0 WHERE ID = $id_cotizacion");
            continue;
        }

        $cotizacion['ID'] = $id_cotizacion;
        $cotizacion['prealerta_produccion'] = $estado_pre;
        $cotizacion['modelos'] = [];

        $q_modelos = mysqli_query($conexion, "
            SELECT tipo_prenda, genero, cantidad, detalle_diseno, especificaciones_tallas
            FROM detalle_cotizacion
            WHERE id_cotizacion = $id_cotizacion
            ORDER BY ID
        ");
        if (!$q_modelos) {
            echo json_encode(['success' => false, 'msg' => 'No se pudieron cargar las prendas de una prealerta.']);
            exit;
        }
        while ($detalle = mysqli_fetch_assoc($q_modelos)) {
            $tipo_prenda = trim((string)($detalle['tipo_prenda'] ?? ''));
            $tipo_normalizado = mb_strtolower($tipo_prenda, 'UTF-8');
            $detalle_diseno = (string)($detalle['detalle_diseno'] ?? '');
            if (
                $tipo_prenda === ''
                || preg_match('/^tallas?\s+extras?\b/u', $tipo_normalizado) === 1
                || stripos($detalle_diseno, 'Ubicación:') !== false
                || preg_match('/diseño|arte final|bordado|vinil|dtf|serigraf|servicio|matriz|ponchado|delivery|flete|descuento|cobro adicional|parche|empaque|impresi[oó]n/u', $tipo_normalizado) === 1
            ) {
                continue;
            }

            $especificaciones = json_decode($detalle['especificaciones_tallas'] ?? '', true);
            $tallas = [];
            if (is_array($especificaciones)) {
                foreach ($especificaciones as $especificacion) {
                    if (!is_array($especificacion)) {
                        continue;
                    }
                    $tallas[] = [
                        'cantidad' => max(0, intval($especificacion['cantidad'] ?? 0)),
                        'talla' => trim((string)($especificacion['talla'] ?? '')),
                        'tela' => trim((string)($especificacion['tela'] ?? ''))
                    ];
                }
            }

            $rango = '';
            if (preg_match('/\bTalla:\s*([^|]+)/i', $detalle_diseno, $matches)) {
                $rango = trim($matches[1]);
            }
            $tela = '';
            if (preg_match('/\bTela:\s*([^|]+)/i', $detalle_diseno, $matches)) {
                $tela = trim($matches[1]);
            }

            $cotizacion['modelos'][] = [
                'tipo_prenda' => $tipo_prenda,
                'genero' => trim((string)($detalle['genero'] ?? '')),
                'cantidad' => max(0, intval($detalle['cantidad'] ?? 0)),
                'rango_tallas' => $rango,
                'tela_cotizada' => $tela,
                'tallas' => $tallas
            ];
        }

        // Cargar insumos ya solicitados para esta pre-orden (cotización)
        $cotizacion['insumos_pedidos'] = [];
        $q_ins_pre = mysqli_query($conexion, "SELECT id, descripcion, check_estados, estado, fecha_solicitud FROM insumos_faltantes WHERE id_cotizacion = $id_cotizacion ORDER BY fecha_solicitud DESC");
        if ($q_ins_pre) {
            while ($ins_row = mysqli_fetch_assoc($q_ins_pre)) {
                $cotizacion['insumos_pedidos'][] = $ins_row;
            }
        }

        $cotizacion['fecha_emision'] = !empty($cotizacion['fecha_emision'])
            ? date('d/m/Y', strtotime($cotizacion['fecha_emision']))
            : '';

        unset($cotizacion['total'], $cotizacion['iva'], $cotizacion['igtf'], $cotizacion['pago_valor_usd'], $cotizacion['pago_retenciones'], $cotizacion['estado_pago'], $cotizacion['tasa_factura_fiscal']);
        $prealertas[] = $cotizacion;
    }

    usort($prealertas, function($a, $b) {
        if ($a['prealerta_produccion'] !== $b['prealerta_produccion']) {
            return $a['prealerta_produccion'] <=> $b['prealerta_produccion'];
        }
        return $b['ID'] <=> $a['ID'];
    });

    echo json_encode(['success' => true, 'data' => $prealertas]);
    exit;
}

if ($accion == 'marcar_prealerta_revisada') {
    if ($rol_actual !== 'master' && !in_array('13', $permisos_arr)) {
        echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [13] para revisar prealertas de producción.']);
        exit;
    }
    $id_cotizacion = intval($_POST['id_cotizacion'] ?? 0);
    if ($id_cotizacion <= 0) {
        echo json_encode(['success' => false, 'msg' => 'La cotización seleccionada no es válida.']);
        exit;
    }
    $q_revisar = mysqli_query($conexion, "
        UPDATE cotizacion
        SET prealerta_produccion = 2
        WHERE ID = $id_cotizacion
          AND enviado_logistica = 0
          AND NOT EXISTS (SELECT 1 FROM orden_fabrica o WHERE o.id_cotizacion = $id_cotizacion)
    ");
    if (!$q_revisar) {
        echo json_encode(['success' => false, 'msg' => 'No se pudo registrar la revisión de esta prealerta.']);
        exit;
    }
    if (mysqli_affected_rows($conexion) > 0) {
        registrar_auditoria($conexion, $id_usr, 'REVISAR', 'cotizacion', $id_cotizacion, "Revisó la prealerta de producción de la cotización ID $id_cotizacion");
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($accion == 'listar_ordenes') {
    $sql = "SELECT o.ID as id_orden, o.nro_orden_fabrica, o.fecha_emision, o.fecha_entrega,
            c.nombre as cliente, c.tlf, cot.estado_pago, cot.ID as id_cotizacion,
            IFNULL(t.estado_general, 'Pendiente') as estado_envio, t.hash_publico, t.id as id_tracking, t.prioridad,
            t.fecha_terminado,
            IFNULL(fp.estado_ficha, 'Pendiente') as estado_ficha,
            IFNULL(t.prendas_totales, 0) as prendas_totales,
            (SELECT COUNT(*) FROM garantias_textiles g WHERE g.id_orden = o.ID AND g.estado <> 'Finalizada') AS garantias_abiertas
            FROM orden_fabrica o
            JOIN cliente c ON o.id_cliente = c.ID
            JOIN cotizacion cot ON o.id_cotizacion = cot.ID
            LEFT JOIN tracking_orden t ON o.ID = t.id_orden_fabrica
            LEFT JOIN ficha_pago_envio fp ON o.ID = fp.id_orden
            ORDER BY 
              CASE 
                WHEN IFNULL(t.estado_general, 'Pendiente') = 'Entregado' THEN 3 
                WHEN IFNULL(t.estado_general, 'Pendiente') = 'Terminado' THEN 2 
                ELSE 1 
              END ASC, 
              CASE WHEN IFNULL(t.estado_general, 'Pendiente') NOT IN ('Entregado', 'Terminado') THEN IFNULL(t.prioridad, 0) ELSE 0 END DESC, 
              o.ID DESC";

    $query = mysqli_query($conexion, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($query)) {
        if (!empty($row['id_tracking'])) {
            $calc = calcular_prendas_orden($conexion, $row['id_orden']);
            if ($calc != intval($row['prendas_totales']) && $calc > 0) {
                mysqli_query($conexion, "UPDATE tracking_orden SET prendas_totales = $calc WHERE id = ".$row['id_tracking']);
                $row['prendas_totales'] = $calc;
            }
        }
        $data[] = $row; 
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($accion == 'obtener_prendas_mes') {
    $mes = date('m');
    $anio = date('Y');
    
    mysqli_query($conexion, "
        UPDATE tracking_orden t 
        SET fecha_terminado = (
            SELECT MAX(fecha_hora) FROM historial_actividad 
            WHERE tabla_afectada = 'tracking_orden' 
            AND id_registro_afectado = t.id_orden_fabrica 
            AND (detalle_cambio LIKE '%Finalizó%' OR detalle_cambio LIKE '%Confirmó físicamente%')
        )
        WHERE t.estado_general IN ('Terminado', 'Entregado') AND (t.fecha_terminado IS NULL OR t.fecha_terminado = '0000-00-00 00:00:00')
    ");
    
    mysqli_query($conexion, "UPDATE tracking_orden SET fecha_terminado = fecha_creacion WHERE estado_general IN ('Terminado', 'Entregado') AND (fecha_terminado IS NULL OR fecha_terminado = '0000-00-00 00:00:00')");

    $sql_mes = "SELECT o.ID as id_orden, o.nro_orden_fabrica, t.prendas_totales, t.fecha_terminado
                FROM tracking_orden t
                JOIN orden_fabrica o ON t.id_orden_fabrica = o.ID
                WHERE t.estado_general IN ('Terminado', 'Entregado')
                  AND MONTH(t.fecha_terminado) = $mes
                  AND YEAR(t.fecha_terminado) = $anio
                ORDER BY o.nro_orden_fabrica ASC";
    $q_mes = mysqli_query($conexion, $sql_mes);
    
    $total_mes = 0;
    $ordenes = [];
    if ($q_mes) {
        while($row = mysqli_fetch_assoc($q_mes)) {
            $cantidad = intval($row['prendas_totales']);
            if ($cantidad <= 0) {
                $cantidad = calcular_prendas_orden($conexion, $row['id_orden']);
            }

            if ($cantidad > 0) {
                $total_mes += $cantidad;
                $clienteNombre = '';
                $clienteSql = mysqli_query($conexion, "SELECT c.nombre FROM orden_fabrica o JOIN cliente c ON o.id_cliente = c.ID WHERE o.ID = " . intval($row['id_orden']));
                if ($clienteSql && mysqli_num_rows($clienteSql) > 0) {
                    $clienteRow = mysqli_fetch_assoc($clienteSql);
                    $clienteNombre = $clienteRow['nombre'] ?: '';
                }

                $ordenes[] = [
                    'id_orden' => intval($row['id_orden']),
                    'nro_orden_fabrica' => $row['nro_orden_fabrica'],
                    'cliente' => $clienteNombre,
                    'cantidad' => $cantidad,
                    'fecha_terminado' => $row['fecha_terminado']
                ];
                if ($cantidad != intval($row['prendas_totales']) && $row['prendas_totales'] !== null) {
                    mysqli_query($conexion, "UPDATE tracking_orden SET prendas_totales = $cantidad WHERE id_orden_fabrica = " . intval($row['id_orden']));
                }
            }
        }
    }
    
    echo json_encode(['success' => true, 'total' => $total_mes, 'ordenes' => $ordenes]);
    exit;
}

if ($accion == 'toggle_prioridad') {
    $id_orden = intval($_POST['id_orden']);
    $q = mysqli_query($conexion, "SELECT prioridad FROM tracking_orden WHERE id_orden_fabrica = $id_orden");
    
    if (mysqli_num_rows($q) > 0) {
        $r = mysqli_fetch_assoc($q);
        $nueva = ($r['prioridad'] == 1) ? 0 : 1;
        mysqli_query($conexion, "UPDATE tracking_orden SET prioridad = $nueva WHERE id_orden_fabrica = $id_orden");
        
        $estado_txt = $nueva == 1 ? "Activó" : "Desactivó";
        registrar_auditoria($conexion, $id_usr, 'EDITAR', 'tracking_orden', $id_orden, "$estado_txt la alerta de prioridad (Urgente) en la orden $id_orden");
    } else {
        $q_orden = mysqli_query($conexion, "SELECT c.enviado_logistica FROM orden_fabrica o JOIN cotizacion c ON o.id_cotizacion = c.ID WHERE o.ID = $id_orden");
        $data_orden = mysqli_fetch_assoc($q_orden);
        $es_envio_directo = ($data_orden['enviado_logistica'] == 2 || $data_orden['enviado_logistica'] == 44);
        
        $hash = md5($id_orden . time() . 'unifuerte');
        $calc_ini = calcular_prendas_orden($conexion, $id_orden);
        mysqli_query($conexion, "INSERT INTO tracking_orden (id_orden_fabrica, hash_publico, prioridad, prendas_totales) VALUES ($id_orden, '$hash', 1, $calc_ini)");
        $id_tracking = mysqli_insert_id($conexion);
        
        $fases_a_usar = $es_envio_directo ? [
            ['nombre' => 'Recepción de Mercancía', 'icono' => '📦'],
            ['nombre' => 'Chequeo de Mercancía', 'icono' => '🔍'],
            ['nombre' => 'Reempacado', 'icono' => '🏷️'],
            ['nombre' => 'Tracking Interno País origen', 'icono' => '🚚'],
            ['nombre' => 'Recepción para Envío Aéreo / Marítimo', 'icono' => '✈️']
        ] : $FASES_ESTANDAR;

        foreach ($fases_a_usar as $i => $f) {
            $orden = $i + 1;
            mysqli_query($conexion, "INSERT INTO tracking_fases (id_tracking, fase_nombre, fase_icono, orden) VALUES ($id_tracking, '{$f['nombre']}', '{$f['icono']}', $orden)");
        }
        registrar_auditoria($conexion, $id_usr, 'CREAR', 'tracking_orden', $id_orden, "Inició el tracking e impuso prioridad (Urgente) a la orden $id_orden");
    }
    
    echo json_encode(['success' => true]);
    exit;
}

if ($accion == 'cargar_fases') {
    $id_orden = intval($_POST['id_orden']);

    $q_orden = mysqli_query($conexion, "SELECT o.id_cotizacion, c.estado_pago, c.enviado_logistica, cli.tlf, cli.nombre as cliente, o.nro_orden_fabrica 
                                        FROM orden_fabrica o JOIN cotizacion c ON o.id_cotizacion = c.ID 
                                        JOIN cliente cli ON o.id_cliente = cli.ID WHERE o.ID = $id_orden");
    $data_orden = mysqli_fetch_assoc($q_orden);
    $id_cot_vinculada = intval($data_orden['id_cotizacion'] ?? 0);
    
    $es_envio_directo = ($data_orden['enviado_logistica'] == 2 || $data_orden['enviado_logistica'] == 44);

    $q_track = mysqli_query($conexion, "SELECT * FROM tracking_orden WHERE id_orden_fabrica = $id_orden");
    if (mysqli_num_rows($q_track) == 0) {
        $hash = md5($id_orden . time() . 'unifuerte');
        $calc_ini = calcular_prendas_orden($conexion, $id_orden);
        mysqli_query($conexion, "INSERT INTO tracking_orden (id_orden_fabrica, hash_publico, prendas_totales) VALUES ($id_orden, '$hash', $calc_ini)");
        $id_tracking = mysqli_insert_id($conexion);
        
        $fases_a_usar = $es_envio_directo ? [
            ['nombre' => 'Recepción de Mercancía', 'icono' => '📦'],
            ['nombre' => 'Chequeo de Mercancía', 'icono' => '🔍'],
            ['nombre' => 'Reempacado', 'icono' => '🏷️'],
            ['nombre' => 'Tracking Interno País origen', 'icono' => '🚚'],
            ['nombre' => 'Recepción para Envío Aéreo / Marítimo', 'icono' => '✈️']
        ] : $FASES_ESTANDAR;

        foreach ($fases_a_usar as $i => $f) {
            $orden = $i + 1;
            mysqli_query($conexion, "INSERT INTO tracking_fases (id_tracking, fase_nombre, fase_icono, orden) VALUES ($id_tracking, '{$f['nombre']}', '{$f['icono']}', $orden)");
        }
        registrar_auditoria($conexion, $id_usr, 'CREAR', 'tracking_orden', $id_orden, "Inició el módulo de tracking para la orden $id_orden");
    } else {
        $track = mysqli_fetch_assoc($q_track);
        $id_tracking = $track['id'];
        $hash = $track['hash_publico'];
    }

    $estado_pago = mb_strtolower(trim($data_orden['estado_pago']), 'UTF-8');
    $is_pagado = ($estado_pago === 'pagado' || strpos($estado_pago, 'muestra') !== false);

    $q_fases = mysqli_query($conexion, "SELECT * FROM tracking_fases WHERE id_tracking = $id_tracking ORDER BY orden ASC");
    $fases = [];
    $todas_completadas = true;
    $total_fases = 0;

    while ($f = mysqli_fetch_assoc($q_fases)) { 
        $f['fase_icono'] = htmlspecialchars($f['fase_icono'], ENT_QUOTES, 'UTF-8');
        $fases[] = $f;
        $total_fases++;
        if ($f['estado'] != 2) { $todas_completadas = false; }
    }
    if ($total_fases == 0) { $todas_completadas = false; }

    $cond_cot_ins = $id_cot_vinculada > 0 ? "OR id_cotizacion = $id_cot_vinculada" : "";
    $q_ins = mysqli_query($conexion, "SELECT id, descripcion, check_estados, estado, fecha_solicitud FROM insumos_faltantes WHERE id_orden = $id_orden $cond_cot_ins ORDER BY fecha_solicitud DESC");
    $insumos_pedidos = [];
    while ($ins = mysqli_fetch_assoc($q_ins)) {
        $insumos_pedidos[] = $ins;
    }

    echo json_encode([
        'success' => true, 
        'hash' => $hash, 
        'id_tracking' => $id_tracking,
        'fases' => $fases,
        'is_pagado' => $is_pagado,
        'estado_pago_real' => $data_orden['estado_pago'],
        'all_completed' => $todas_completadas,
        'tlf' => $data_orden['tlf'],
        'cliente' => $data_orden['cliente'],
        'nro_orden' => $data_orden['nro_orden_fabrica'],
        'estado_general' => $track['estado_general'] ?? 'Pendiente',
        'insumos_pedidos' => $insumos_pedidos
    ]);
    exit;
}

if ($accion == 'actualizar_fase') {
    $id_fase = intval($_POST['id_fase']);
    $estado = intval($_POST['estado']);
    $nombre_fase = mysqli_real_escape_string($conexion, $_POST['nombre_fase'] ?? 'Fase');
    $fecha_inicio = !empty($_POST['fecha_inicio']) ? "'{$_POST['fecha_inicio']}'" : "NULL";
    $fecha_fin = !empty($_POST['fecha_fin']) ? "'{$_POST['fecha_fin']}'" : "NULL";
    
    $obs = (string)($_POST['observacion'] ?? '');
    $observacion = mysqli_real_escape_string($conexion, $obs);

    $q_f = mysqli_query($conexion, "SELECT foto, foto_leyendas FROM tracking_fases WHERE id = $id_fase");
    $f_curr = mysqli_fetch_assoc($q_f);
    $fotos_existentes = !empty($f_curr['foto']) ? explode(',', $f_curr['foto']) : [];
    
    $leyendas_actuales = !empty($f_curr['foto_leyendas']) ? json_decode($f_curr['foto_leyendas'], true) : [];
    if (!is_array($leyendas_actuales)) $leyendas_actuales = [];

    if (isset($_POST['leyenda_existente']) && is_array($_POST['leyenda_existente'])) {
        foreach($_POST['leyenda_existente'] as $imgName => $cap) {
            $leyendas_actuales[$imgName] = trim($cap);
        }
    }

    $fotos_nuevas = [];
    $dir = "../../images/envios/";
    
    if (isset($_FILES['foto_fase'])) {
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $total = is_array($_FILES['foto_fase']['name']) ? count($_FILES['foto_fase']['name']) : 0;
        for ($i=0; $i<$total; $i++) {
            if ($_FILES['foto_fase']['error'][$i] == UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['foto_fase']['name'][$i], PATHINFO_EXTENSION));
                $nombre_foto = "fase_" . $id_fase . "_" . time() . "_" . $i . "." . $ext;
                if (move_uploaded_file($_FILES['foto_fase']['tmp_name'][$i], $dir . $nombre_foto)) {
                    $fotos_nuevas[] = $nombre_foto;
                    if (isset($_POST['leyenda_nueva'][$i]) && trim($_POST['leyenda_nueva'][$i]) !== '') {
                        $leyendas_actuales[$nombre_foto] = trim($_POST['leyenda_nueva'][$i]);
                    }
                }
            }
        }
    }
    
    if (isset($_FILES['factura_file']) && $_FILES['factura_file']['error'] == UPLOAD_ERR_OK) {
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['factura_file']['name'], PATHINFO_EXTENSION));
        $nombre_fact = "factura_" . $id_fase . "_" . time() . "." . $ext;
        if (move_uploaded_file($_FILES['factura_file']['tmp_name'], $dir . $nombre_fact)) {
            $fotos_nuevas[] = $nombre_fact;
            $leyendas_actuales[$nombre_fact] = "Factura PDF (Para Entrega)"; 
        }
    }

    $todas_fotos = array_merge($fotos_existentes, $fotos_nuevas);
    $todas_fotos = array_filter(array_map('trim', $todas_fotos)); 
    
    $leyendas_limpias = [];
    foreach($todas_fotos as $tf) {
        if(isset($leyendas_actuales[$tf]) && trim($leyendas_actuales[$tf]) !== '') {
            $leyendas_limpias[$tf] = $leyendas_actuales[$tf];
        }
    }

    $foto_sql_val = empty($todas_fotos) ? "NULL" : "'" . implode(',', $todas_fotos) . "'";
    $leyendas_sql_val = empty($leyendas_limpias) ? "NULL" : "'" . mysqli_real_escape_string($conexion, json_encode($leyendas_limpias, JSON_UNESCAPED_UNICODE)) . "'";

    if(mysqli_query($conexion, "UPDATE tracking_fases SET estado = $estado, fecha_inicio = $fecha_inicio, fecha_fin = $fecha_fin, observacion = '$observacion', foto = $foto_sql_val, foto_leyendas = $leyendas_sql_val WHERE id = $id_fase")) {
        registrar_auditoria($conexion, $id_usr, 'EDITAR', 'tracking_fases', $id_fase, "Actualizó la fase '$nombre_fase' (Estado: $estado)");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

if ($accion == 'eliminar_foto_fase') {
    $id_fase = intval($_POST['id_fase']);
    $foto_eliminar = mysqli_real_escape_string($conexion, trim($_POST['foto']));

    $q = mysqli_query($conexion, "SELECT foto, foto_leyendas FROM tracking_fases WHERE id = $id_fase");
    $f = mysqli_fetch_assoc($q);

    if ($f && !empty($f['foto'])) {
        $fotos = explode(',', $f['foto']);
        $fotos_nuevas = [];
        foreach ($fotos as $img) {
            if (trim($img) !== $foto_eliminar && !empty(trim($img))) {
                $fotos_nuevas[] = trim($img);
            }
        }

        $leyendas_actuales = !empty($f['foto_leyendas']) ? json_decode($f['foto_leyendas'], true) : [];
        if (isset($leyendas_actuales[$foto_eliminar])) {
            unset($leyendas_actuales[$foto_eliminar]);
        }

        $nueva_cadena = empty($fotos_nuevas) ? 'NULL' : "'" . implode(',', $fotos_nuevas) . "'";
        $leyendas_sql_val = empty($leyendas_actuales) ? 'NULL' : "'" . mysqli_real_escape_string($conexion, json_encode($leyendas_actuales, JSON_UNESCAPED_UNICODE)) . "'";

        mysqli_query($conexion, "UPDATE tracking_fases SET foto = $nueva_cadena, foto_leyendas = $leyendas_sql_val WHERE id = $id_fase");
        registrar_auditoria($conexion, $id_usr, 'ELIMINAR', 'tracking_fases', $id_fase, "Eliminó evidencia fotográfica/documento de la fase $id_fase");

        $ruta = "../../images/envios/" . $foto_eliminar;
        if (file_exists($ruta)) { unlink($ruta); }
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($accion == 'agregar_fase') {
    $id_t = intval($_POST['id_tracking']);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $icono = mysqli_real_escape_string($conexion, $_POST['icono']);
    $q_ord = mysqli_query($conexion, "SELECT MAX(orden) as max_ord FROM tracking_fases WHERE id_tracking = $id_t");
    $max = mysqli_fetch_assoc($q_ord)['max_ord'] ?? 0;
    $nuevo = $max + 1;
    if(mysqli_query($conexion, "INSERT INTO tracking_fases (id_tracking, fase_nombre, fase_icono, orden) VALUES ($id_t, '$nombre', '$icono', $nuevo)")) {
        registrar_auditoria($conexion, $id_usr, 'CREAR', 'tracking_fases', $id_t, "Agregó nueva fase manual ('$nombre') al tracking.");
        echo json_encode(['success'=>true]); 
    }
    exit;
}

if ($accion == 'eliminar_fase') {
    $id_fase = intval($_POST['id_fase']);
    if(mysqli_query($conexion, "DELETE FROM tracking_fases WHERE id = $id_fase")) {
        registrar_auditoria($conexion, $id_usr, 'ELIMINAR', 'tracking_fases', $id_fase, "Eliminó una fase de envío/producción.");
        echo json_encode(['success'=>true]); 
    }
    exit;
}

if ($accion == 'mover_fase') {
    $id_fase = intval($_POST['id_fase']);
    $dir = $_POST['direccion'];
    $q = mysqli_query($conexion, "SELECT id_tracking, orden FROM tracking_fases WHERE id = $id_fase");
    $actual = mysqli_fetch_assoc($q);
    $id_t = $actual['id_tracking'];
    $ord = $actual['orden'];

    if ($dir == 'up') {
        $q2 = mysqli_query($conexion, "SELECT id, orden FROM tracking_fases WHERE id_tracking = $id_t AND orden < $ord ORDER BY orden DESC LIMIT 1");
    } else {
        $q2 = mysqli_query($conexion, "SELECT id, orden FROM tracking_fases WHERE id_tracking = $id_t AND orden > $ord ORDER BY orden ASC LIMIT 1");
    }

    if (mysqli_num_rows($q2) > 0) {
        $otro = mysqli_fetch_assoc($q2);
        $id_otro = $otro['id'];
        $ord_otro = $otro['orden'];
        mysqli_query($conexion, "UPDATE tracking_fases SET orden = $ord_otro WHERE id = $id_fase");
        mysqli_query($conexion, "UPDATE tracking_fases SET orden = $ord WHERE id = $id_otro");
        registrar_auditoria($conexion, $id_usr, 'EDITAR', 'tracking_fases', $id_fase, "Modificó el orden de las fases en el timeline ($dir).");
    }
    echo json_encode(['success'=>true]); exit;
}

if ($accion == 'notificar_vendedor_avance') {
    $id_orden = intval($_POST['id_orden']);
    mysqli_query($conexion, "UPDATE cotizacion c JOIN orden_fabrica o ON c.ID = o.id_cotizacion
        SET c.noti_wa = IF(EXISTS (SELECT 1 FROM garantias_textiles g WHERE g.id_cotizacion = c.ID AND g.estado <> 'Finalizada'), 4, 1)
        WHERE o.ID = $id_orden AND c.noti_wa != 2 AND c.noti_wa != 3");
    registrar_auditoria($conexion, $id_usr, 'SISTEMA', 'cotizacion', $id_orden, "Envió notificación de AVANCE a departamento de Ventas");
    echo json_encode(['success' => true]);
    exit;
}

if ($accion == 'notificar_vendedor_cobro') {
    $id_orden = intval($_POST['id_orden']);
    mysqli_query($conexion, "UPDATE cotizacion c JOIN orden_fabrica o ON c.ID = o.id_cotizacion
        SET c.noti_wa = IF(EXISTS (SELECT 1 FROM garantias_textiles g WHERE g.id_cotizacion = c.ID AND g.estado <> 'Finalizada'), 4, 3)
        WHERE o.ID = $id_orden");
    registrar_auditoria($conexion, $id_usr, 'SISTEMA', 'cotizacion', $id_orden, "Envió notificación de COBRO a departamento de Ventas");
    echo json_encode(['success' => true]);
    exit;
}

if ($accion == 'notificar_vendedor_garantia') {
    $id_orden = intval($_POST['id_orden']);
    $tipo = isset($_POST['tipo']) ? intval($_POST['tipo']) : 4; 
    if (!in_array($tipo, [4, 5], true)) {
        echo json_encode(['success' => false, 'msg' => 'Tipo de aviso de garantía no válido.']);
        exit;
    }
    mysqli_query($conexion, "UPDATE cotizacion c JOIN orden_fabrica o ON c.ID = o.id_cotizacion
        SET c.noti_wa = IF(EXISTS (SELECT 1 FROM garantias_textiles g WHERE g.id_cotizacion = c.ID AND g.estado <> 'Finalizada'), 4, $tipo)
        WHERE o.ID = $id_orden");
    registrar_auditoria($conexion, $id_usr, 'SISTEMA', 'cotizacion', $id_orden, "Activó alerta de GARANTÍA ($tipo) a departamento de Ventas");
    echo json_encode(['success' => true]);
    exit;
}

if ($accion == 'finalizar_envio') {
    $id_orden = intval($_POST['id_orden']);
    $total_prendas = calcular_prendas_orden($conexion, $id_orden);
    
    mysqli_query($conexion, "UPDATE tracking_orden SET estado_general = 'Terminado', fecha_terminado = NOW(), prendas_totales = $total_prendas WHERE id_orden_fabrica = $id_orden");
    mysqli_query($conexion, "UPDATE cotizacion c JOIN orden_fabrica o ON c.ID = o.id_cotizacion
        SET c.noti_wa = IF(EXISTS (SELECT 1 FROM garantias_textiles g WHERE g.id_cotizacion = c.ID AND g.estado <> 'Finalizada'), 4, 2)
        WHERE o.ID = $id_orden");
    registrar_auditoria($conexion, $id_usr, 'EDITAR', 'tracking_orden', $id_orden, "Finalizó la producción de la orden. Pedido trasladado al Archivo.");
    echo json_encode(['success' => true]);
    exit;
}

if ($accion == 'entregar_envio') {
    $id_orden = intval($_POST['id_orden']);
    $total_prendas = calcular_prendas_orden($conexion, $id_orden);
    
    mysqli_query($conexion, "UPDATE tracking_orden SET estado_general = 'Entregado', fecha_terminado = IFNULL(fecha_terminado, NOW()), prendas_totales = $total_prendas WHERE id_orden_fabrica = $id_orden");
    registrar_auditoria($conexion, $id_usr, 'EDITAR', 'tracking_orden', $id_orden, "Confirmó físicamente la entrega del pedido al cliente.");
    echo json_encode(['success' => true]);
    exit;
}

if ($accion == 'solicitar_insumo') {
    $id_orden = intval($_POST['id_orden'] ?? 0);
    $id_cotizacion = intval($_POST['id_cotizacion'] ?? 0);
    $desc = mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? '');
    $solic = mysqli_real_escape_string($conexion, $_POST['solicitante'] ?? '');

    if ($id_orden <= 0 && $id_cotizacion <= 0) {
        echo json_encode(['success' => false, 'msg' => 'Debes indicar la orden o la pre-orden para solicitar insumos.']);
        exit;
    }

    if ($id_orden > 0 && $id_cotizacion <= 0) {
        $q_cot_ord = mysqli_query($conexion, "SELECT id_cotizacion FROM orden_fabrica WHERE ID = $id_orden LIMIT 1");
        if ($q_cot_ord && ($r_co = mysqli_fetch_assoc($q_cot_ord))) {
            $id_cotizacion = intval($r_co['id_cotizacion'] ?? 0);
        }
    }

    $id_cot_sql = $id_cotizacion > 0 ? $id_cotizacion : "NULL";
    
    if(mysqli_query($conexion, "INSERT INTO insumos_faltantes (id_orden, id_cotizacion, descripcion, solicitante) VALUES ($id_orden, $id_cot_sql, '$desc', '$solic')")) {
        $nuevo_id = mysqli_insert_id($conexion);
        $origen_txt = $id_orden > 0 ? "Orden ID $id_orden" : "Pre-orden (Cotización ID $id_cotizacion)";
        registrar_auditoria($conexion, $id_usr, 'CREAR', 'insumos_faltantes', $nuevo_id, "Solicitó material/insumo faltante a Compras ($origen_txt).");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'No se pudo registrar la solicitud de insumos.']);
    }
    exit;
}

if ($accion == 'editar_insumo_historial') {
    $id = intval($_POST['id']);
    $nueva_desc = mysqli_real_escape_string($conexion, $_POST['descripcion']); 
    
    if(mysqli_query($conexion, "UPDATE insumos_faltantes SET descripcion = '$nueva_desc' WHERE id = $id")) {
        registrar_auditoria($conexion, $id_usr, 'EDITAR', 'insumos_faltantes', $id, "Editó la lista de materiales/insumos solicitados.");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($accion == 'listar_insumos') {
    $sql = "SELECT i.id, i.descripcion, i.check_estados, i.solicitante, i.estado,
            COALESCE(o.nro_orden_fabrica, CONCAT('Pre-orden ', cot.nro_cotizacion), 'Pre-orden') as nro_orden_fabrica,
            COALESCE(c.nombre, c_cot.nombre, 'Cliente') as cliente, 
            DATE_FORMAT(i.fecha_solicitud, '%d/%m/%Y %h:%i %p') as fecha_format
            FROM insumos_faltantes i
            LEFT JOIN orden_fabrica o ON i.id_orden = o.ID
            LEFT JOIN cliente c ON o.id_cliente = c.ID
            LEFT JOIN cotizacion cot ON (i.id_cotizacion = cot.ID OR (i.id_cotizacion IS NULL AND o.id_cotizacion = cot.ID))
            LEFT JOIN cliente c_cot ON cot.id_cliente = c_cot.ID
            ORDER BY i.estado ASC, i.fecha_solicitud DESC";
    $query = mysqli_query($conexion, $sql);
    $data = [];
    if ($query) {
        while ($r = mysqli_fetch_assoc($query)) { $data[] = $r; }
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($accion == 'atender_insumo') {
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "UPDATE insumos_faltantes SET estado = 1 WHERE id = $id")) {
        registrar_auditoria($conexion, $id_usr, 'EDITAR', 'insumos_faltantes', $id, "Confirmó la entrega de insumo al Taller Textil.");
        echo json_encode(['success' => true]);
    }
    exit;
}

if ($accion == 'contar_insumos') {
    $q = mysqli_query($conexion, "SELECT COUNT(*) as cant FROM insumos_faltantes WHERE estado = 0");
    $r = mysqli_fetch_assoc($q);
    echo json_encode(['success' => true, 'cantidad' => intval($r['cant'])]);
    exit;
}

if ($accion == 'actualizar_checks_insumo') {
    $id = intval($_POST['id']);
    $checks = mysqli_real_escape_string($conexion, $_POST['checks']);
    mysqli_query($conexion, "UPDATE insumos_faltantes SET check_estados = '$checks' WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

function obtenerTasaBCVDia() {
    $tasa = 0.0;
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $json = @file_get_contents('https://ve.dolarapi.com/v1/dolares/oficial', false, $ctx);
    if ($json) {
        $data = json_decode($json, true);
        if (isset($data['promedio']) && floatval($data['promedio']) > 0) { return floatval($data['promedio']); }
    }
    $json2 = @file_get_contents('https://pydolarve.org/api/v1/dollar?page=bcv', false, $ctx);
    if ($json2) {
        $data2 = json_decode($json2, true);
        if (isset($data2['monitors']['usd']['price'])) { return floatval($data2['monitors']['usd']['price']); }
    }
    return 0.0;
}

if ($accion == 'obtener_ficha_pago') {
    $id_orden = intval($_POST['id_orden']);
    $sql = "SELECT o.ID as id_orden, o.nro_orden_fabrica, o.fecha_emision, c.nombre as cliente, c.tlf as cliente_tlf,
                   cot.ID as id_cotizacion, cot.nro_cotizacion, cot.total as cot_total, cot.sub_total as cot_subtotal, cot.iva as cot_iva, cot.estado_pago,
                   cot.pago_valor_usd, cot.pago_tasa_bcv, cot.pago_fecha, cot.comision_porc, cot.comision_monto,
                   u.nombre as vendedor_nombre, u.apellido as vendedor_apellido
            FROM orden_fabrica o
            JOIN cotizacion cot ON o.id_cotizacion = cot.ID JOIN cliente c ON o.id_cliente = c.ID LEFT JOIN usuario u ON cot.id_vendedor = u.ID
            WHERE o.ID = $id_orden";
    
    $q = mysqli_query($conexion, $sql);
    if (!$q || mysqli_num_rows($q) == 0) { echo json_encode(['success' => false, 'msg' => 'Orden no encontrada']); exit; }
    $orden = mysqli_fetch_assoc($q);

    $total_abonos = 0.0;
    if (!empty($orden['pago_valor_usd'])) {
        $partes_pagos = explode('|', $orden['pago_valor_usd']);
        foreach($partes_pagos as $p) { $total_abonos += floatval(trim($p)); }
    }
    if ((strtolower($orden['estado_pago']) === 'pagado' || strpos(strtolower($orden['estado_pago']), 'muestra') !== false) && $total_abonos == 0) { $total_abonos = floatval($orden['cot_total']); }

    $q_fp = mysqli_query($conexion, "SELECT * FROM ficha_pago_envio WHERE id_orden = $id_orden");
    $ficha_guardada = mysqli_fetch_assoc($q_fp);

    $tasa_dia = obtenerTasaBCVDia();
    $tasa_final = 0.0;
    if ($ficha_guardada && floatval($ficha_guardada['tasa_bcv']) > 0) {
        $tasa_final = floatval($ficha_guardada['tasa_bcv']);
    } elseif ($tasa_dia > 0) {
        $tasa_final = $tasa_dia;
    } else {
        if (!empty($orden['pago_tasa_bcv'])) {
            $tasas_arr = explode('|', $orden['pago_tasa_bcv']);
            $tasa_final = floatval(trim(end($tasas_arr)));
        }
    }

    $porc_ofi = ($ficha_guardada && isset($ficha_guardada['porc_oficina'])) ? floatval($ficha_guardada['porc_oficina']) : 10.0;
    $porc_ven = ($ficha_guardada && isset($ficha_guardada['porc_vendedor'])) ? floatval($ficha_guardada['porc_vendedor']) : (floatval($orden['comision_porc']) > 0 ? floatval($orden['comision_porc']) : 5.0);
    $abono_usar = ($ficha_guardada && $ficha_guardada['monto_abonos_manual'] !== null) ? floatval($ficha_guardada['monto_abonos_manual']) : $total_abonos;

    $consumos_default = [
        ['item' => 'COMISIÓN VENDEDOR', 'monto' => 0.0, 'auto' => 'vendedor'], ['item' => 'DIEZMO', 'monto' => 0.0, 'auto' => 'diezmo'],
        ['item' => 'CASTILLO', 'monto' => 0.0, 'auto' => ''], ['item' => 'TEALCA', 'monto' => 0.0, 'auto' => ''],
        ['item' => 'LIMPIEZA AL JARDÍN', 'monto' => 0.0, 'auto' => ''], ['item' => 'CASTILLO II', 'monto' => 0.0, 'auto' => ''],
        ['item' => 'ARNALDO', 'monto' => 0.0, 'auto' => ''], ['item' => 'CASTILLO III', 'monto' => 0.0, 'auto' => ''],
        ['item' => 'INTERNET 321', 'monto' => 0.0, 'auto' => ''], ['item' => 'NÓMINA', 'monto' => 0.0, 'auto' => '']
    ];

    $consumos = $consumos_default;
    if ($ficha_guardada && !empty($ficha_guardada['consumos_json'])) {
        $c_parsed = json_decode($ficha_guardada['consumos_json'], true);
        if (is_array($c_parsed) && count($c_parsed) > 0) { $consumos = $c_parsed; }
    }

    echo json_encode([
        'success' => true,
        'orden' => [
            'id_orden' => $orden['id_orden'], 'nro_orden' => $orden['nro_orden_fabrica'], 'nro_cotizacion' => $orden['nro_cotizacion'],
            'cliente' => $orden['cliente'], 'vendedor' => trim(($orden['vendedor_nombre'] ?? '') . ' ' . ($orden['vendedor_apellido'] ?? '')),
            'monto_cotizacion' => floatval($orden['cot_total']), 'estado_pago' => $orden['estado_pago']
        ],
        'ficha' => [
            'tasa_bcv' => $tasa_final, 'porc_oficina' => $porc_ofi, 'porc_vendedor' => $porc_ven,
            'abonos' => $abono_usar, 'consumos' => $consumos, 'estado_ficha' => $ficha_guardada['estado_ficha'] ?? 'Pendiente'
        ]
    ]);
    exit;
}

if ($accion == 'guardar_ficha_pago') {
    $id_orden = intval($_POST['id_orden']);
    $tasa_bcv = floatval($_POST['tasa_bcv']);
    $porc_ofi = floatval($_POST['porc_oficina']);
    $porc_ven = floatval($_POST['porc_vendedor']);
    $abonos = floatval($_POST['abonos']);
    $estado_ficha = mysqli_real_escape_string($conexion, $_POST['estado_ficha'] ?? 'Pendiente');
    $consumos_json = mysqli_real_escape_string($conexion, $_POST['consumos_json'] ?? '[]');

    $sql = "INSERT INTO ficha_pago_envio (id_orden, tasa_bcv, porc_oficina, porc_vendedor, monto_abonos_manual, consumos_json, estado_ficha)
            VALUES ($id_orden, $tasa_bcv, $porc_ofi, $porc_ven, $abonos, '$consumos_json', '$estado_ficha')
            ON DUPLICATE KEY UPDATE 
                tasa_bcv = $tasa_bcv, porc_oficina = $porc_ofi, porc_vendedor = $porc_ven,
                monto_abonos_manual = $abonos, consumos_json = '$consumos_json', estado_ficha = '$estado_ficha'";

    if (mysqli_query($conexion, $sql)) { 
        registrar_auditoria($conexion, $id_usr, 'EDITAR', 'ficha_pago_envio', $id_orden, "Guardó o actualizó ficha de pago administrativo ($estado_ficha).");
        echo json_encode(['success' => true]); 
    } else { 
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]); 
    }
    exit;
}

// ==============================================================================
// NOTAS DE ENTREGA MANUALES (PERSONALIZADAS)
// ==============================================================================
if ($accion == 'listar_notas_manuales') {
    $sql = "SELECT n.*, DATE_FORMAT(n.fecha, '%d/%m/%Y') as fecha_format,
            (SELECT SUM(cantidad) FROM nota_entrega_manual_items WHERE id_nota = n.id) as total_prendas
            FROM nota_entrega_manual n
            ORDER BY n.id DESC";
    $query = mysqli_query($conexion, $sql);
    $data = [];
    if($query) while ($row = mysqli_fetch_assoc($query)) { $data[] = $row; }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($accion == 'guardar_nota_manual') {
    $fecha = mysqli_real_escape_string($conexion, $_POST['fecha']);
    $cliente = mysqli_real_escape_string($conexion, $_POST['cliente_nombre']);
    $rif = mysqli_real_escape_string($conexion, $_POST['cedula_rif']);
    $dir = mysqli_real_escape_string($conexion, $_POST['direccion']);
    $tlf = mysqli_real_escape_string($conexion, $_POST['telefono'] ?? '');
    $email = mysqli_real_escape_string($conexion, $_POST['email'] ?? '');
    $comp = mysqli_real_escape_string($conexion, $_POST['comprador'] ?? '');

    mysqli_begin_transaction($conexion);
    try {
        $sql_nota = "INSERT INTO nota_entrega_manual (fecha, cliente_nombre, cedula_rif, direccion, telefono, email, comprador) 
                     VALUES ('$fecha', '$cliente', '$rif', '$dir', '$tlf', '$email', '$comp')";
        mysqli_query($conexion, $sql_nota);
        $id_nota = mysqli_insert_id($conexion);

        $nro_nota = "NEM-" . str_pad($id_nota, 4, "0", STR_PAD_LEFT);
        mysqli_query($conexion, "UPDATE nota_entrega_manual SET nro_nota = '$nro_nota' WHERE id = $id_nota");

        $prendas = $_POST['prenda'];
        $tallas = $_POST['talla'];
        $cantidades = $_POST['cantidad'];

        for ($i = 0; $i < count($prendas); $i++) {
            $p = mysqli_real_escape_string($conexion, $prendas[$i]);
            $t = mysqli_real_escape_string($conexion, $tallas[$i]);
            $c = intval($cantidades[$i]);
            if ($c > 0 && !empty($p)) {
                mysqli_query($conexion, "INSERT INTO nota_entrega_manual_items (id_nota, prenda, talla, cantidad) VALUES ($id_nota, '$p', '$t', $c)");
            }
        }
        
        mysqli_commit($conexion);
        registrar_auditoria($conexion, $id_usr, 'CREAR', 'nota_entrega_manual', $id_nota, "Creó nota de entrega manual Nro. $nro_nota");
        echo json_encode(['success' => true, 'id_nota' => $id_nota]);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        echo json_encode(['success' => false, 'msg' => 'Error al guardar la nota.']);
    }
    exit;
}

if ($accion == 'eliminar_nota_manual') {
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "DELETE FROM nota_entrega_manual WHERE id = $id")) {
        registrar_auditoria($conexion, $id_usr, 'ELIMINAR', 'nota_entrega_manual', $id, "Eliminó una nota de entrega manual y su registro.");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'No se pudo eliminar.']);
    }
    exit;
}
?>