<?php
session_start();
error_reporting(0);
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}
require_once '../../conexion.php';
require_once '../../garantia_textil.php';
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$id_usuario = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;
session_write_close(); 

header('Content-Type: application/json; charset=utf-8');

if ($id_usuario === 0) {
    ob_clean();
    echo json_encode(['success' => false, 'msg' => 'Sesión expirada']); 
    exit;
}

mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS nomina_prestamos_vendedores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_vendedor INT NOT NULL,
        monto_original DECIMAL(15,2) NOT NULL,
        saldo_pendiente DECIMAL(15,2) NOT NULL,
        concepto VARCHAR(255) NOT NULL,
        fecha DATE NOT NULL,
        moneda VARCHAR(10) NOT NULL DEFAULT 'USD',
        tasa_bcv DECIMAL(15,4) NOT NULL DEFAULT 0.00,
        estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
        KEY idx_vendedor_estado (id_vendedor, estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS nomina_prestamo_vendedor_abonos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_cotizacion INT NOT NULL,
        id_prestamo INT NOT NULL,
        id_vendedor INT NOT NULL,
        monto DECIMAL(15,2) NOT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_comision_prestamo (id_cotizacion, id_prestamo),
        KEY idx_vendedor_fecha (id_vendedor, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS factura_fiscal_secuencia (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
        siguiente_numero BIGINT UNSIGNED NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS factura_fiscal_cotizacion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_cotizacion INT NOT NULL,
        numero_factura BIGINT UNSIGNED NOT NULL UNIQUE,
        numero_control VARCHAR(60) NOT NULL,
        tasa_bcv DECIMAL(15,4) NOT NULL,
        id_usuario INT NOT NULL,
        fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        numero_orden_compra VARCHAR(100) DEFAULT NULL,
        estado VARCHAR(20) NOT NULL DEFAULT 'Activa',
        motivo_anulacion VARCHAR(255) DEFAULT NULL,
        fecha_anulacion DATETIME DEFAULT NULL,
        id_usuario_anulo INT DEFAULT NULL,
        KEY idx_cotizacion_estado (id_cotizacion, estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$q_col_id_fac = mysqli_query($conexion, "SHOW COLUMNS FROM factura_fiscal_cotizacion LIKE 'id'");
if ($q_col_id_fac && mysqli_num_rows($q_col_id_fac) === 0) {
    @mysqli_query($conexion, "ALTER TABLE factura_fiscal_cotizacion DROP PRIMARY KEY, ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST, ADD INDEX idx_id_cotizacion (id_cotizacion)");
}
mysqli_query($conexion, "INSERT IGNORE INTO factura_fiscal_secuencia (id, siguiente_numero) VALUES (1, 290)");

$q_usr = mysqli_query($conexion, "SELECT permisos, rol FROM usuario WHERE ID = $id_usuario");
$datos_usr = mysqli_fetch_assoc($q_usr);
$permisos_arr = explode(',', $datos_usr['permisos'] ?? '');
$rol_actual = strtolower(trim($datos_usr['rol'] ?? ''));

$puede_ver_propias = in_array('6', $permisos_arr);
$puede_ver_todas = in_array('7', $permisos_arr);
$puede_editar = in_array('8', $permisos_arr);
$puede_eliminar = in_array('9', $permisos_arr);
$puede_comisiones = in_array('35', $permisos_arr); 
$es_master = $rol_actual === 'master';

function parsearSeriePagosCotizacion($valor): array {
    $texto = str_replace(['ert{}', '<br>', '<br/>', '<br />', "\r", "\n"], '|', (string)$valor);
    return preg_split('/\s*\|\s*/', trim($texto, " |\t\n\r\0\x0B"), -1, PREG_SPLIT_NO_EMPTY) ?: [];
}

function totalRetencionesUsdCotizacion($retencionesJson, $tasa): ?float {
    $retencionesJson = trim((string)$retencionesJson);
    if ($retencionesJson === '') {
        return 0.0;
    }
    $retencionesRaiz = json_decode($retencionesJson);
    if (!is_array($retencionesRaiz) || json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    $retenciones = json_decode($retencionesJson, true);

    $totalUsd = 0.0;
    foreach ($retenciones as $retencion) {
        if (!is_array($retencion)) {
            return null;
        }
        $tasaItem = (float)$tasa > 0 ? (float)$tasa : (float)($retencion['tasa_bcv'] ?? 0);
        $montoRegistro = 0.0;
        foreach (['iva_bs', 'islr_bs', 'municipal_bs'] as $campo) {
            $valor = $retencion[$campo] ?? 0;
            if (!is_numeric($valor) || !is_finite((float)$valor) || (float)$valor < 0) {
                return null;
            }
            $montoRegistro += (float)$valor;
        }
        if ($montoRegistro > 0 && $tasaItem <= 0) {
            return null;
        }
        if ($tasaItem > 0) {
            $totalUsd += $montoRegistro / $tasaItem;
        }
    }
    return round($totalUsd, 2);
}

function registrar_auditoria($con, $id_usr, $accion, $tabla, $id_reg, $detalle) {
    $acc = mysqli_real_escape_string($con, $accion);
    $tab = mysqli_real_escape_string($con, $tabla);
    $det = mysqli_real_escape_string($con, $detalle);
    @mysqli_query($con, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usr, '$acc', '$tab', $id_reg, NOW(), '$det')");
}

function es_cargo_talla_extra_cotizacion($tipo_prenda): bool {
    return preg_match('/^tallas?\s+extras?\b/i', trim((string)$tipo_prenda)) === 1;
}

function genero_especificacion_valido_cotizacion($tipo_prenda, $genero): bool {
    $genero = trim((string)$genero);
    $nombre = mb_strtolower(trim((string)$tipo_prenda), 'UTF-8');
    if (strpos($nombre, 'gorra') !== false || strpos($nombre, 'visera') !== false || strpos($nombre, 'vicera') !== false) {
        return $genero === 'Accesorio';
    }
    if (strpos($nombre, 'bota') !== false || strpos($nombre, 'zapato') !== false || strpos($nombre, 'calzado') !== false) {
        return $genero === 'No aplica';
    }
    return in_array($genero, ['Dama', 'Caballero', 'Niña', 'Niño', 'Juvenil', 'Unisex'], true);
}

function obtener_tela_base_cotizacion($detalle_diseno): string {
    if (!preg_match('/\bTela:\s*([^|]+)/i', (string)$detalle_diseno, $matches)) {
        return '';
    }
    $tela = trim($matches[1]);
    return in_array(mb_strtolower($tela, 'UTF-8'), ['n/a', 'no aplica', 'no especificada'], true) ? '' : $tela;
}

function validar_especificaciones_cotizacion(mysqli $conexion, int $id_cotizacion): array {
    $q = mysqli_query($conexion, "
        SELECT tipo_prenda, cantidad, detalle_diseno, especificaciones_tallas
        FROM detalle_cotizacion
        WHERE id_cotizacion = $id_cotizacion
    ");
    if (!$q) {
        return ['success' => false, 'msg' => 'No se pudieron revisar las reglas de la cotización.'];
    }

    $errores = [];
    while ($detalle = mysqli_fetch_assoc($q)) {
        if (
            stripos($detalle['detalle_diseno'] ?? '', 'Ubicación:') !== false
            || preg_match('/^(bordado|serigraf|dtf|vinil)/i', trim((string)($detalle['tipo_prenda'] ?? ''))) === 1
            || es_cargo_talla_extra_cotizacion($detalle['tipo_prenda'])
        ) {
            continue;
        }

        $nombre = trim($detalle['tipo_prenda'] ?? '') ?: 'Renglón sin descripción';
        $esperado = intval($detalle['cantidad'] ?? 0);
        $specs = json_decode($detalle['especificaciones_tallas'] ?? '', true);
        if (!is_array($specs) || count($specs) === 0) {
            $errores[] = "$nombre: faltan tallas y telas.";
            continue;
        }

        $asignado = 0;
        $fila_incompleta = false;
        foreach ($specs as $spec) {
            if (!is_array($spec)) {
                $fila_incompleta = true;
                continue;
            }
            $cantidad = intval($spec['cantidad'] ?? 0);
            $asignado += $cantidad;
            if (
                $cantidad < 1
                || trim((string)($spec['talla'] ?? '')) === ''
                || trim((string)($spec['tela'] ?? '')) === ''
                || !genero_especificacion_valido_cotizacion($detalle['tipo_prenda'], $spec['genero'] ?? '')
            ) {
                $fila_incompleta = true;
            }
        }

        if ($fila_incompleta) {
            $errores[] = "$nombre: completa género, cantidad, talla y tela en cada línea.";
        } elseif ($asignado !== $esperado) {
            $errores[] = "$nombre: las tallas suman $asignado y la cotización requiere $esperado.";
        }
    }

    return empty($errores)
        ? ['success' => true, 'msg' => '']
        : ['success' => false, 'msg' => "Revisa nuevamente las reglas azules antes de enviar a logística:\n- " . implode("\n- ", $errores)];
}

function buscarUsuarioVendedorDeCotizacion($conexion, $idCotizacion) {
    $idCotizacion = intval($idCotizacion);
    $qVendedor = mysqli_query($conexion, "
        SELECT u.ID, u.rol, u.nombre, u.apellido
        FROM cotizacion c
        LEFT JOIN usuario u ON u.ID = c.id_vendedor
        WHERE c.ID = $idCotizacion
    ");
    if (!$qVendedor || !($vendedor = mysqli_fetch_assoc($qVendedor))) {
        return ['success' => false, 'msg' => 'No se pudo obtener el vendedor de la cotización.'];
    }
    if (empty($vendedor['ID']) || !in_array(strtolower($vendedor['rol'] ?? ''), ['ventas', 'jefe_ventas'], true)) {
        return [
            'success' => true,
            'encontrado' => false,
            'saldo' => 0.0,
            'vendedor' => trim(($vendedor['nombre'] ?? '') . ' ' . ($vendedor['apellido'] ?? ''))
        ];
    }

    $qSaldo = mysqli_query($conexion, "
        SELECT COALESCE(SUM(saldo_pendiente), 0) AS saldo
        FROM nomina_prestamos_vendedores
        WHERE id_vendedor = " . intval($vendedor['ID']) . "
          AND estado = 'Pendiente'
          AND saldo_pendiente > 0
    ");
    if (!$qSaldo || !($saldo = mysqli_fetch_assoc($qSaldo))) {
        return ['success' => false, 'msg' => 'No se pudo consultar el saldo pendiente del préstamo.'];
    }

    return [
        'success' => true,
        'encontrado' => true,
        'id_vendedor' => intval($vendedor['ID']),
        'saldo' => round((float)$saldo['saldo'], 2),
        'vendedor' => trim(($vendedor['nombre'] ?? '') . ' ' . ($vendedor['apellido'] ?? ''))
    ];
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

if ($accion === 'obtener_factura_fiscal') {
    if (!$puede_ver_propias && !$puede_ver_todas) {
        echo json_encode(['success' => false, 'msg' => 'No tienes permiso para facturar cotizaciones.']);
        exit;
    }
    $id_cotizacion = intval($_POST['id'] ?? 0);
    $q_cotizacion = mysqli_query($conexion, "SELECT ID, id_vendedor, tipo_cotizacion FROM cotizacion WHERE ID = $id_cotizacion LIMIT 1");
    $cotizacion = $q_cotizacion ? mysqli_fetch_assoc($q_cotizacion) : null;
    if (!$cotizacion) {
        echo json_encode(['success' => false, 'msg' => 'No se encontró la cotización seleccionada.']);
        exit;
    }
    if (!$puede_ver_todas && intval($cotizacion['id_vendedor']) !== $id_usuario) {
        echo json_encode(['success' => false, 'msg' => 'No tienes permiso para facturar esta cotización.']);
        exit;
    }
    if (($cotizacion['tipo_cotizacion'] ?? '') === 'MUESTRA') {
        echo json_encode(['success' => false, 'msg' => 'Las muestras físicas no admiten factura fiscal.']);
        exit;
    }

    $q_factura = mysqli_query($conexion, "
        SELECT numero_factura, numero_control, tasa_bcv, numero_orden_compra, estado, motivo_anulacion, fecha_emision, fecha_anulacion
        FROM factura_fiscal_cotizacion
        WHERE id_cotizacion = $id_cotizacion
        ORDER BY numero_factura DESC
    ");
    $factura_activa = null;
    $anuladas = [];
    if ($q_factura) {
        while ($fila_fac = mysqli_fetch_assoc($q_factura)) {
            $item_fac = [
                'numero_raw' => intval($fila_fac['numero_factura']),
                'numero' => str_pad((string)$fila_fac['numero_factura'], 8, '0', STR_PAD_LEFT),
                'control' => $fila_fac['numero_control'],
                'tasa' => $fila_fac['tasa_bcv'],
                'orden_compra' => $fila_fac['numero_orden_compra'] ?? '',
                'estado' => $fila_fac['estado'] ?? 'Activa',
                'motivo_anulacion' => $fila_fac['motivo_anulacion'] ?? '',
                'fecha_emision' => !empty($fila_fac['fecha_emision']) ? date('d/m/Y H:i', strtotime($fila_fac['fecha_emision'])) : '',
                'fecha_anulacion' => !empty($fila_fac['fecha_anulacion']) ? date('d/m/Y H:i', strtotime($fila_fac['fecha_anulacion'])) : ''
            ];
            if (strtoupper($item_fac['estado']) === 'ANULADA') {
                $anuladas[] = $item_fac;
            } elseif ($factura_activa === null) {
                $factura_activa = $item_fac;
            }
        }
    }

    echo json_encode(['success' => true, 'data' => $factura_activa, 'anuladas' => $anuladas]);
    exit;
}

if ($accion === 'anular_factura_fiscal') {
    if (!$puede_editar && !$es_master && !$puede_ver_todas) {
        echo json_encode(['success' => false, 'msg' => 'No tienes permiso para anular facturas fiscales.']);
        exit;
    }
    $id_cotizacion = intval($_POST['id'] ?? 0);
    $numero_factura = intval($_POST['numero_factura'] ?? 0);
    $motivo = trim((string)($_POST['motivo'] ?? 'Anulada por el usuario'));
    if ($id_cotizacion <= 0) {
        echo json_encode(['success' => false, 'msg' => 'Cotización inválida.']);
        exit;
    }

    mysqli_begin_transaction($conexion);
    $cond_num = $numero_factura > 0 ? "AND numero_factura = $numero_factura" : "";
    $q_fac = mysqli_query($conexion, "
        SELECT numero_factura
        FROM factura_fiscal_cotizacion
        WHERE id_cotizacion = $id_cotizacion AND (estado IS NULL OR estado = 'Activa') $cond_num
        ORDER BY numero_factura DESC
        LIMIT 1
        FOR UPDATE
    ");
    $fac_row = $q_fac ? mysqli_fetch_assoc($q_fac) : null;
    if (!$fac_row) {
        mysqli_rollback($conexion);
        echo json_encode(['success' => false, 'msg' => 'No se encontró una factura activa para anular.']);
        exit;
    }
    $num_anular = intval($fac_row['numero_factura']);
    $motivo_sql = mysqli_real_escape_string($conexion, mb_substr($motivo, 0, 250, 'UTF-8'));

    $upd = mysqli_query($conexion, "
        UPDATE factura_fiscal_cotizacion
        SET estado = 'Anulada', motivo_anulacion = '$motivo_sql', fecha_anulacion = NOW(), id_usuario_anulo = $id_usuario
        WHERE id_cotizacion = $id_cotizacion AND numero_factura = $num_anular
    ");
    if (!$upd || !mysqli_commit($conexion)) {
        mysqli_rollback($conexion);
        echo json_encode(['success' => false, 'msg' => 'No se pudo anular la factura fiscal.']);
        exit;
    }

    $num_fmt = str_pad((string)$num_anular, 8, '0', STR_PAD_LEFT);
    registrar_auditoria($conexion, $id_usuario, 'ANULAR', 'factura_fiscal_cotizacion', $id_cotizacion, "Anuló la factura fiscal N° $num_fmt. Motivo: $motivo");

    echo json_encode(['success' => true, 'numero_anulado' => $num_fmt, 'msg' => "La factura N° $num_fmt fue marcada como ANULADA."]);
    exit;
}

if ($accion === 'guardar_orden_compra_factura_final') {
    $id_cotizacion = intval($_POST['id'] ?? 0);
    $ordenCompra = trim((string)($_POST['orden_compra'] ?? '')) ?: 'S/N';
    $q_cotizacion = mysqli_query($conexion, "
        SELECT c.id_vendedor, c.tipo_cotizacion, f.numero_factura
        FROM cotizacion c
        LEFT JOIN factura_fiscal_cotizacion f ON f.id_cotizacion = c.ID AND (f.estado IS NULL OR f.estado = 'Activa')
        WHERE c.ID = $id_cotizacion
        LIMIT 1
    ");
    $cotizacion = $q_cotizacion ? mysqli_fetch_assoc($q_cotizacion) : null;
    if (!$cotizacion || $cotizacion['numero_factura'] === null) {
        echo json_encode(['success' => false, 'msg' => 'Primero debes emitir la factura fiscal activa.']);
        exit;
    }
    $ordenCompraSql = mysqli_real_escape_string($conexion, $ordenCompra);
    $numFacActiva = intval($cotizacion['numero_factura']);
    mysqli_query($conexion, "UPDATE factura_fiscal_cotizacion SET numero_orden_compra = '$ordenCompraSql' WHERE id_cotizacion = $id_cotizacion AND numero_factura = $numFacActiva");
    echo json_encode(['success' => true]);
    exit;
}

if ($accion === 'guardar_factura_fiscal') {
    $id_cotizacion = intval($_POST['id'] ?? 0);
    $numero_control = trim((string)($_POST['control'] ?? ''));
    $tasa = filter_var($_POST['tasa'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($id_cotizacion <= 0 || $numero_control === '' || $tasa === false || $tasa <= 0) {
        echo json_encode(['success' => false, 'msg' => 'Ingresa un número de control y una tasa BCV válidos.']);
        exit;
    }

    mysqli_begin_transaction($conexion);
    $q_existente = mysqli_query($conexion, "
        SELECT numero_factura FROM factura_fiscal_cotizacion
        WHERE id_cotizacion = $id_cotizacion AND (estado IS NULL OR estado = 'Activa')
        ORDER BY numero_factura DESC LIMIT 1 FOR UPDATE
    ");
    $factura_existente = $q_existente ? mysqli_fetch_assoc($q_existente) : null;

    if ($factura_existente) {
        $numero_factura = intval($factura_existente['numero_factura']);
        $control_sql = mysqli_real_escape_string($conexion, $numero_control);
        mysqli_query($conexion, "UPDATE factura_fiscal_cotizacion SET numero_control = '$control_sql', tasa_bcv = " . floatval($tasa) . " WHERE id_cotizacion = $id_cotizacion AND numero_factura = $numero_factura");
        mysqli_commit($conexion);
    } else {
        $q_secuencia = mysqli_query($conexion, "SELECT siguiente_numero FROM factura_fiscal_secuencia WHERE id = 1 FOR UPDATE");
        $secuencia = $q_secuencia ? mysqli_fetch_assoc($q_secuencia) : ['siguiente_numero' => 290];
        $numero_factura = intval($secuencia['siguiente_numero']);
        $control_sql = mysqli_real_escape_string($conexion, $numero_control);
        mysqli_query($conexion, "INSERT INTO factura_fiscal_cotizacion (id_cotizacion, numero_factura, numero_control, tasa_bcv, id_usuario, estado) VALUES ($id_cotizacion, $numero_factura, '$control_sql', " . floatval($tasa) . ", $id_usuario, 'Activa')");
        mysqli_query($conexion, "UPDATE factura_fiscal_secuencia SET siguiente_numero = siguiente_numero + 1 WHERE id = 1");
        mysqli_commit($conexion);
    }

    echo json_encode(['success' => true, 'numero' => str_pad((string)$numero_factura, 8, '0', STR_PAD_LEFT)]);
    exit;
}

if ($accion == 'pausar_cotizacion') {
    if (!$puede_editar && !$es_master) { 
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [8] para editar cotizaciones.']); 
        exit; 
    }
    $id_cotizacion = intval($_POST['id_cotizacion'] ?? 0);
    $estado_nuevo = intval($_POST['estado_nuevo'] ?? 0);
    if (mysqli_query($conexion, "UPDATE cotizacion SET pausado = $estado_nuevo WHERE ID = $id_cotizacion")) {
        ob_clean();
        echo json_encode(['success' => true]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($accion == 'listar') {
    if (!$puede_ver_propias && !$puede_ver_todas && !$es_master) {
        ob_clean();
        echo json_encode(['success' => true, 'data' => [], 'es_master_actual' => $es_master]); 
        exit;
    }

    $desde = mysqli_real_escape_string($conexion, $_GET['desde'] ?? '');
    $hasta = mysqli_real_escape_string($conexion, $_GET['hasta'] ?? '');
    $estado = mysqli_real_escape_string($conexion, $_GET['estado'] ?? '');
    $tracking = mysqli_real_escape_string($conexion, $_GET['tracking'] ?? '');
    $vendedor = intval($_GET['vendedor'] ?? 0);
    $cliente = intval($_GET['cliente'] ?? 0);
    $where = "WHERE 1=1";
    
    if (!$puede_ver_todas && !$es_master) {
        $where .= " AND c.id_vendedor = $id_usuario";
    } else {
        if ($vendedor > 0) $where .= " AND c.id_vendedor = $vendedor";
    }
    if ($cliente > 0) $where .= " AND c.id_cliente = $cliente";
    if (!empty($desde)) $where .= " AND DATE(c.fecha_emision) >= '$desde'";
    if (!empty($hasta)) $where .= " AND DATE(c.fecha_emision) <= '$hasta'";
    
    if (!empty($estado)) {
        if ($estado === 'con_pagos') {
            $where .= " AND c.estado_pago IN ('Pago Inicial', 'Pago Medio', 'Pagado')";
        } else if (strtolower($estado) !== 'todos') {
            $where .= " AND c.estado_pago = '$estado'";
        }
    }

    if (!empty($tracking)) {
        if ($tracking === 'sin_tracking') {
            $where .= " AND t.id IS NULL AND o.ID IS NULL";
        } else if (strtolower($tracking) !== 'todos') {
            $where .= " AND t.estado_general = '$tracking'";
        }
    }

    $whereClientes = ($puede_ver_todas || $es_master) ? '' : "WHERE c.id_vendedor = $id_usuario";
    $qClientes = mysqli_query($conexion, "SELECT DISTINCT cli.ID, cli.nombre FROM cotizacion c JOIN cliente cli ON cli.ID = c.id_cliente $whereClientes ORDER BY cli.nombre");
    $clientes = [];
    if ($qClientes) {
        while ($rowCliente = mysqli_fetch_assoc($qClientes)) {
            $clientes[] = ['ID' => intval($rowCliente['ID']), 'nombre' => $rowCliente['nombre']];
        }
    }

    $sql = "SELECT c.ID, c.nro_cotizacion, c.fecha_emision, c.notas, c.sub_total, c.descuento,
                   c.servicio_logistico, c.flete, c.iva, c.total, c.estado_pago, c.id_cliente, 
                   c.referencias_pago, c.fotos_pagos, c.pago_fecha, c.pago_tasa_bcv, c.pago_valor_usd, c.pago_banco, c.pago_retenciones, c.tipo_cotizacion, c.enviado_logistica,
                   c.igtf, c.comision_pagada, c.noti_wa, c.pausado AS cotizacion_pausada, c.cuenta_por_cobrar,
                   f.numero_factura AS numero_factura_fiscal,
                   f.tasa_bcv AS tasa_factura_fiscal,
                   (SELECT GROUP_CONCAT(LPAD(fa.numero_factura, 8, '0') ORDER BY fa.numero_factura DESC SEPARATOR ', ')
                    FROM factura_fiscal_cotizacion fa
                    WHERE fa.id_cotizacion = c.ID AND fa.estado = 'Anulada') AS facturas_anuladas_lista,
                   cli.nombre AS cliente_nombre, cli.tlf AS cliente_tlf, u.nombre AS v_nombre, u.apellido AS v_apellido,
                   t.hash_publico, t.estado_general as tracking_estado 
            FROM cotizacion c 
            JOIN cliente cli ON c.id_cliente = cli.ID 
            LEFT JOIN factura_fiscal_cotizacion f ON f.id_cotizacion = c.ID AND (f.estado IS NULL OR f.estado = 'Activa')
            LEFT JOIN usuario u ON c.id_vendedor = u.ID 
            LEFT JOIN orden_fabrica o ON c.ID = o.id_cotizacion 
            LEFT JOIN tracking_orden t ON o.ID = t.id_orden_fabrica 
            $where ORDER BY c.ID DESC"; 
            
    $query = mysqli_query($conexion, $sql);
    $data = [];
    $hay_notificaciones = false;
    
    if ($query) {
        while ($row = mysqli_fetch_assoc($query)) {
            $row['fecha_emision'] = date('d/m/Y', strtotime($row['fecha_emision']));
            $row['numero_factura_fiscal'] = $row['numero_factura_fiscal'] !== null
                ? str_pad((string)$row['numero_factura_fiscal'], 8, '0', STR_PAD_LEFT)
                : '';
            $row['facturas_anuladas_lista'] = $row['facturas_anuladas_lista'] ?? '';
            $row['vendedor_nombre'] = trim(($row['v_nombre'] ?? '') . ' ' . ($row['v_apellido'] ?? '')) ?: 'Desconocido';
            if (($row['noti_wa'] ?? 0) > 0) { $hay_notificaciones = true; }
            $data[] = $row;
        }
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => $data,
        'clientes' => $clientes,
        'hay_notificaciones' => $hay_notificaciones,
        'es_master_actual' => $es_master
    ]); 
    exit;
}

if ($accion == 'obtener') {
    if (!$puede_editar && !$es_master) { 
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [8] para editar.']); 
        exit; 
    }
    $id = intval($_GET['id'] ?? 0);
    $data = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM cotizacion WHERE ID = $id"));
    $detalles = []; 
    $q = mysqli_query($conexion, "SELECT d.*, c.nombre as categoria FROM detalle_cotizacion d LEFT JOIN productos p ON d.id_producto = p.id LEFT JOIN subcategorias s ON p.id_subcategoria = s.id LEFT JOIN categorias c ON s.id_categoria = c.id WHERE d.id_cotizacion = $id");
    while ($row = mysqli_fetch_assoc($q)) $detalles[] = $row;
    
    ob_clean();
    echo json_encode(['success' => true, 'data' => $data, 'detalles' => $detalles]); 
    exit;
}

if ($accion == 'detalles') {
    $id_cotizacion = intval($_GET['id_cotizacion'] ?? 0);
    $data = []; 
    $q = mysqli_query($conexion, "SELECT * FROM detalle_cotizacion WHERE id_cotizacion = $id_cotizacion");
    while ($row = mysqli_fetch_assoc($q)) $data[] = $row;
    
    ob_clean();
    echo json_encode(['success' => true, 'data' => $data]); 
    exit;
}

if ($accion == 'detalles_y_recursos') {
    $id_cotizacion = intval($_GET['id_cotizacion'] ?? 0);
    $q_cot = mysqli_query($conexion, "
        SELECT c.*,
               cli.nombre AS cliente_nombre, cli.cedula_Rif AS cliente_rif,
               cli.tlf AS cliente_tlf, cli.comprador AS cliente_comprador,
               cli.direccion AS cliente_direccion,
               u.nombre AS v_nombre, u.apellido AS v_apellido
        FROM cotizacion c
        LEFT JOIN cliente cli ON c.id_cliente = cli.ID
        LEFT JOIN usuario u ON c.id_vendedor = u.ID
        WHERE c.ID = $id_cotizacion
        LIMIT 1
    ");
    $cot = $q_cot ? mysqli_fetch_assoc($q_cot) : [];
    $data = [];
    $ficha_guardada = null;
    $q = mysqli_query($conexion, "SELECT * FROM detalle_cotizacion WHERE id_cotizacion = $id_cotizacion");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            if ($ficha_guardada === null && !empty($row['especificaciones_tallas'])) {
                $sp_dec = json_decode($row['especificaciones_tallas'], true);
                if (is_array($sp_dec) && !empty($sp_dec[0]['_ficha_piloto'])) {
                    $ficha_guardada = $sp_dec[0]['_ficha_piloto'];
                }
            }
            $data[] = $row;
        }
    }

    $cotizacion_info = [
        'id_cotizacion' => intval($cot['ID'] ?? $id_cotizacion),
        'nro_cotizacion' => $cot['nro_cotizacion'] ?? ('#' . $id_cotizacion),
        'fecha_emision' => !empty($cot['fecha_emision']) ? date('d/m/Y', strtotime($cot['fecha_emision'])) : date('d/m/Y'),
        'notas' => $cot['notas'] ?? '',
        'metodo_entrega' => $cot['metodo_entrega'] ?? ($cot['cliente_direccion'] ?? ''),
        'cliente_nombre' => $cot['cliente_nombre'] ?? '',
        'cliente_rif' => $cot['cliente_rif'] ?? '',
        'cliente_tlf' => $cot['cliente_tlf'] ?? '',
        'cliente_comprador' => $cot['cliente_comprador'] ?? '',
        'cliente_direccion' => $cot['cliente_direccion'] ?? '',
        'vendedor_nombre' => trim(($cot['v_nombre'] ?? '') . ' ' . ($cot['v_apellido'] ?? ''))
    ];
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => $data,
        'fotos_diseno' => $cot['fotos_diseno'] ?? '',
        'ficha_confeccion_json' => $ficha_guardada,
        'cotizacion_info' => $cotizacion_info
    ]); 
    exit;
}

// =========================================================================
// GUARDAR ESPECIFICACIONES Y FICHA PILOTO EN LA TABLA EXISTENTE detalle_cotizacion
// =========================================================================
if ($accion == 'guardar_especificaciones') {
    $id_cot = intval($_POST['id_cotizacion'] ?? 0);
    $payload = json_decode($_POST['data'] ?? '[]', true);
    $ficha_confeccion_raw = trim((string)($_POST['ficha_confeccion_json'] ?? ''));

    if (!in_array('28', $permisos_arr) && !in_array('29', $permisos_arr) && !$es_master) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Necesitas el Vector [28] o [29] para guardar las especificaciones.']);
        exit;
    }
    if ($id_cot <= 0 || !is_array($payload)) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se recibieron reglas válidas para guardar.']);
        exit;
    }

    $fotos_nuevas = [];
    $mapa_fotos_nuevas = [];
    if (isset($_FILES['fotos_diseno'])) {
        $dir_uploads = "../../images/ordenes/"; 
        if (!is_dir($dir_uploads)) @mkdir($dir_uploads, 0777, true);
        $total_archivos = is_array($_FILES['fotos_diseno']['name']) ? count($_FILES['fotos_diseno']['name']) : 0;
        $indice_cola = 0;
        for ($i = 0; $i < $total_archivos; $i++) {
            if ($_FILES['fotos_diseno']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['fotos_diseno']['name'][$i], PATHINFO_EXTENSION));
                $nombre_archivo = "diseno_" . time() . "_" . rand(100, 999) . "_" . $i . "." . $ext;
                if (move_uploaded_file($_FILES['fotos_diseno']['tmp_name'][$i], $dir_uploads . $nombre_archivo)) {
                    $fotos_nuevas[] = $nombre_archivo;
                    $mapa_fotos_nuevas['nueva:' . $indice_cola] = 'guardada:' . $nombre_archivo;
                    $indice_cola++;
                }
            }
        }
    }

    if (!empty($fotos_nuevas)) {
        $q_curr = mysqli_query($conexion, "SELECT fotos_diseno FROM cotizacion WHERE ID = $id_cot");
        $curr = $q_curr ? mysqli_fetch_assoc($q_curr) : [];
        $fotos_final = $curr['fotos_diseno'] ?? '';
        $fotos_str = implode(',', $fotos_nuevas);
        $fotos_final = empty($fotos_final) ? $fotos_str : ($fotos_final . "," . $fotos_str);
        $fotos_final_sql = mysqli_real_escape_string($conexion, $fotos_final);
        mysqli_query($conexion, "UPDATE cotizacion SET fotos_diseno = '$fotos_final_sql' WHERE ID = $id_cot");
    }

    $ficha_arr_guardar = null;
    if ($ficha_confeccion_raw !== '') {
        $dec_f = json_decode($ficha_confeccion_raw, true);
        if (is_array($dec_f)) {
            if (!empty($dec_f['foto_frente']) && isset($mapa_fotos_nuevas[$dec_f['foto_frente']])) {
                $dec_f['foto_frente'] = $mapa_fotos_nuevas[$dec_f['foto_frente']];
            }
            if (!empty($dec_f['foto_espalda']) && isset($mapa_fotos_nuevas[$dec_f['foto_espalda']])) {
                $dec_f['foto_espalda'] = $mapa_fotos_nuevas[$dec_f['foto_espalda']];
            }
            $ficha_arr_guardar = $dec_f;
        }
    }

    $primera_iteracion = true;
    foreach ($payload as $item) {
        if (!is_array($item)) continue;
        $id_det = intval($item['id_detalle'] ?? 0);
        if ($id_det <= 0) continue;

        $specs_arr = json_decode($item['especificaciones'] ?? '[]', true);
        if (!is_array($specs_arr)) continue;

        if ($primera_iteracion && $ficha_arr_guardar !== null && isset($specs_arr[0])) {
            $specs_arr[0]['_ficha_piloto'] = $ficha_arr_guardar;
            $primera_iteracion = false;
        }

        $specs_sql = mysqli_real_escape_string($conexion, json_encode($specs_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        mysqli_query($conexion, "UPDATE detalle_cotizacion SET especificaciones_tallas = '$specs_sql' WHERE ID = $id_det AND id_cotizacion = $id_cot");
    }

    registrar_auditoria($conexion, $id_usuario, 'EDITAR', 'detalle_cotizacion', $id_cot, "Guardó especificaciones y Ficha Piloto para la cotización ID $id_cot");
    ob_clean();
    echo json_encode(['success' => true]);
    exit;
}

// =========================================================================
// ENVIAR A LOGÍSTICA Y DISPARAR LA ALERTA DE PRODUCCIÓN
// =========================================================================
if ($accion == 'enviar_logistica') {
    $id_cot = intval($_POST['id_cotizacion'] ?? 0);
    if (!in_array('29', $permisos_arr) && !$es_master) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [29] para enviar cotizaciones a logística.']);
        exit;
    }

    $forzar_sin_pago_master = $es_master && (intval($_POST['forzar_sin_pago_master'] ?? 0) === 1);

    $q_cot = mysqli_query($conexion, "
        SELECT c.*, cli.nombre AS cliente_nombre, f.tasa_bcv AS tasa_factura_fiscal
        FROM cotizacion c
        LEFT JOIN cliente cli ON cli.ID = c.id_cliente
        LEFT JOIN factura_fiscal_cotizacion f ON f.id_cotizacion = c.ID AND (f.estado IS NULL OR f.estado = 'Activa')
        WHERE c.ID = $id_cot
        LIMIT 1
    ");
    $cotizacion = $q_cot ? mysqli_fetch_assoc($q_cot) : false;
    if (!$cotizacion) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se encontró la cotización.']);
        exit;
    }

    $q_orden_existente = mysqli_query($conexion, "SELECT ID FROM orden_fabrica WHERE id_cotizacion = $id_cot LIMIT 1");
    if ($q_orden_existente && mysqli_fetch_assoc($q_orden_existente)) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Esta cotización ya tiene una Orden de Fábrica emitida.']);
        exit;
    }

    $total_abonado = 0.0;
    $pagos_limpios = str_replace(['ert{}', '<br>', '<br/>', '<br />', "\r", "\n"], '|', $cotizacion['pago_valor_usd'] ?? '');
    foreach (preg_split('/[\s]*\|[\s]*/', $pagos_limpios) as $pago) {
        $monto = preg_replace('/[^0-9.-]/', '', $pago);
        if ($monto !== '') {
            $total_abonado += round((float)$monto, 2);
        }
    }
    $ret_usd = totalRetencionesUsdCotizacion($cotizacion['pago_retenciones'] ?? '', $cotizacion['tasa_factura_fiscal'] ?? 0);
    if ($ret_usd !== null) {
        $total_abonado += $ret_usd;
    }
    $total_abonado = round($total_abonado, 2);

    if (strtoupper(trim($cotizacion['tipo_cotizacion'] ?? '')) !== 'MUESTRA' && !$forzar_sin_pago_master) {
        $base_imponible = round((float)$cotizacion['total'] - (float)$cotizacion['iva'] - (float)$cotizacion['igtf'], 2);
        $porcentaje_minimo = $es_master ? 0.50 : 0.60;
        $minimo_requerido = round($base_imponible * $porcentaje_minimo, 2);
        if ($total_abonado <= 0 || $total_abonado < ($minimo_requerido - 0.10)) {
            $porcentaje_texto = (int)($porcentaje_minimo * 100);
            ob_clean();
            echo json_encode([
                'success' => false,
                'msg' => "Se requiere un pago mínimo del $porcentaje_texto% para enviar esta cotización a logística."
            ]);
            exit;
        }
    }

    $validacion_specs = validar_especificaciones_cotizacion($conexion, $id_cot);
    if (!$validacion_specs['success']) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => $validacion_specs['msg']]);
        exit;
    }

    // Actualizar enviado_logistica = 1 para disparar la Alerta de Producción
    $upd_ok = mysqli_query($conexion, "UPDATE cotizacion SET enviado_logistica = 1 WHERE ID = $id_cot");
    if (!$upd_ok) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se pudo registrar el envío a logística: ' . mysqli_error($conexion)]);
        exit;
    }

    // Marcar prealerta_produccion y cuenta_por_cobrar si existen las columnas en la BD
    @mysqli_query($conexion, "UPDATE cotizacion SET prealerta_produccion = 1 WHERE ID = $id_cot");
    if ($es_master && $forzar_sin_pago_master) {
        @mysqli_query($conexion, "UPDATE cotizacion SET cuenta_por_cobrar = 1 WHERE ID = $id_cot");
    }

    $saldo_pendiente = max(0, round((float)$cotizacion['total'] - $total_abonado, 2));
    $mensaje_respuesta = $forzar_sin_pago_master
        ? "Cotización enviada a Alertas de Producción (Autorización Master sin pago). Ahora puedes emitir la Orden de Fábrica y su PDF."
        : "¡Alerta de Producción enviada a Logística! Ya aparece en 'Alertas Producción' y en 'Órdenes de Fábrica' lista para emitir su PDF.";

    registrar_auditoria($conexion, $id_usuario, 'ESTADO_PAGO', 'cotizacion', $id_cot, "Envió cotización ID $id_cot a Alertas de Producción / Logística");

    ob_clean();
    echo json_encode([
        'success' => true,
        'id_cotizacion' => $id_cot,
        'nro_cotizacion' => $cotizacion['nro_cotizacion'] ?? ('#' . $id_cot),
        'msg' => $mensaje_respuesta
    ]);
    exit;
}

if ($accion == 'enviar_directo_envios') {
    if (!in_array('44', $permisos_arr) && !$es_master) { 
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Sin Vector [44] para enviar directo a envíos.']); 
        exit; 
    }
    $id_cot = intval($_POST['id_cotizacion'] ?? 0);
    mysqli_query($conexion, "UPDATE cotizacion SET enviado_logistica = 2 WHERE ID = $id_cot");
    
    $q_cot = mysqli_query($conexion, "SELECT id_cliente, id_vendedor, nro_cotizacion FROM cotizacion WHERE ID = $id_cot");
    $cot = mysqli_fetch_assoc($q_cot);
    $id_cli = intval($cot['id_cliente'] ?? 0);
    $id_ven = intval($cot['id_vendedor'] ?? 0);
    $nro = $cot['nro_cotizacion'] ?? '';
    $fecha = date('Y-m-d');
    
    $q_max = mysqli_query($conexion, "SELECT MAX(ID) as max_id FROM orden_fabrica");
    $siguiente = intval(mysqli_fetch_assoc($q_max)['max_id'] ?? 0) + 1;
    $nro_orden = "DIR-" . str_pad($siguiente, 5, "0", STR_PAD_LEFT);
    
    mysqli_query($conexion, "INSERT INTO orden_fabrica (id_cliente, id_cotizacion, nro_orden_fabrica, fecha_emision, fecha_entrega, es_express, id_usuario_vendedor, acepta_entregas_parciales, descripcion, fecha_ingreso_orden) 
                             VALUES ($id_cli, $id_cot, '$nro_orden', '$fecha', '$fecha', 0, $id_ven, 1, 'ENVÍO DIRECTO (Sin pasar por producción)', '$fecha')");

    registrar_auditoria($conexion, $id_usuario, 'ENVÍO DIRECTO', 'cotizacion', $id_cot, "Envió la cotización $nro DIRECTO a módulo de Envíos");
    ob_clean();
    echo json_encode(['success' => true]);
    exit;
}

if ($accion == 'limpiar_pagos') {
    if (!$puede_editar && !$es_master) { 
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Permiso denegado.']); 
        exit; 
    }
    $id = intval($_POST['id'] ?? 0);
    $sql = "UPDATE cotizacion SET estado_pago = 'sin pagar', referencias_pago = NULL, fotos_pagos = NULL, pago_fecha = NULL, pago_tasa_bcv = NULL, pago_valor_usd = NULL, pago_banco = NULL WHERE ID = $id";
    if (mysqli_query($conexion, $sql)) {
        @mysqli_query($conexion, "UPDATE cotizacion SET pago_retenciones = NULL, prealerta_produccion = 0 WHERE ID = $id");
        ob_clean();
        echo json_encode(['success' => true]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($accion == 'cambiar_estado') { 
    if (!$puede_editar && !$es_master) { 
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [8] para cambiar estados.']); 
        exit; 
    }
    
    $id = intval($_POST['id'] ?? 0);
    $referencia = mysqli_real_escape_string($conexion, trim($_POST['referencia'] ?? ''));
    $pago_fecha = mysqli_real_escape_string($conexion, trim($_POST['pago_fecha'] ?? ''));
    $pago_tasa = mysqli_real_escape_string($conexion, trim($_POST['pago_tasa_bcv'] ?? ''));
    $pago_usd = mysqli_real_escape_string($conexion, trim($_POST['pago_valor_usd'] ?? ''));
    $pago_banco = mysqli_real_escape_string($conexion, trim($_POST['pago_banco'] ?? ''));

    $fotos_nuevas = [];
    if (isset($_FILES['fotos_pago'])) {
        $dir_uploads = "../../images/pagos/"; 
        if (!is_dir($dir_uploads)) @mkdir($dir_uploads, 0777, true);
        $total_archivos = is_array($_FILES['fotos_pago']['name']) ? count($_FILES['fotos_pago']['name']) : 0;
        for ($i = 0; $i < $total_archivos; $i++) {
            if ($_FILES['fotos_pago']['error'][$i] == UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['fotos_pago']['name'][$i], PATHINFO_EXTENSION));
                $nombre_archivo = "pago_" . time() . "_" . rand(100, 999) . "_" . $i . "." . $ext;
                if (move_uploaded_file($_FILES['fotos_pago']['tmp_name'][$i], $dir_uploads . $nombre_archivo)) {
                    $fotos_nuevas[] = $nombre_archivo;
                }
            }
        }
    }

    $q_curr = mysqli_query($conexion, "SELECT * FROM cotizacion WHERE ID = $id");
    $curr = $q_curr ? mysqli_fetch_assoc($q_curr) : null;
    if (!$curr) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se encontró la cotización.']);
        exit;
    }

    $refs_final = $curr['referencias_pago'] ?? '';
    if (!empty($referencia)) { $refs_final = empty($refs_final) ? $referencia : $refs_final . " | " . $referencia; }

    $fotos_final = $curr['fotos_pagos'] ?? '';
    if (!empty($fotos_nuevas)) { $fotos_str = implode(',', $fotos_nuevas); $fotos_final = empty($fotos_final) ? $fotos_str : $fotos_final . "," . $fotos_str; }

    $fecha_final = $curr['pago_fecha'] ?? '';
    if (!empty($pago_fecha)) { $fecha_final = empty($fecha_final) ? date('d/m/Y', strtotime($pago_fecha)) : $fecha_final . " | " . date('d/m/Y', strtotime($pago_fecha)); }

    $tasa_final = $curr['pago_tasa_bcv'] ?? '';
    if (!empty($pago_tasa)) { $tasa_final = empty($tasa_final) ? $pago_tasa : $tasa_final . " | " . $pago_tasa; }

    $usd_final = $curr['pago_valor_usd'] ?? '';
    if ($pago_usd !== '') {
        $usd_final = trim((string)$usd_final) === '' ? $pago_usd : $usd_final . " | " . $pago_usd;
    }

    $banco_final = $curr['pago_banco'] ?? '';
    if (!empty($pago_banco)) { $banco_final = empty($banco_final) ? $pago_banco : $banco_final . " | " . $pago_banco; }

    $total_cotizacion = round(floatval($curr['total'] ?? 0), 2);
    $total_abonado = 0;
    foreach (parsearSeriePagosCotizacion($usd_final) as $p) {
        $num_limpio = preg_replace('/[^0-9.-]/', '', $p);
        if ($num_limpio !== '') {
            $total_abonado += round(floatval($num_limpio), 2);
        }
    }

    if ($total_abonado <= 0) {
        $estado_calculado = 'sin pagar';
    } else if ($total_abonado >= ($total_cotizacion - 0.50)) { 
        $estado_calculado = 'Pagado';
    } else {
        $estado_calculado = 'Pago Inicial';
    }

    $sql_update = "UPDATE cotizacion SET
        estado_pago = '$estado_calculado',
        referencias_pago = '$refs_final',
        fotos_pagos = '$fotos_final',
        pago_fecha = '$fecha_final',
        pago_tasa_bcv = '$tasa_final',
        pago_valor_usd = '$usd_final',
        pago_banco = '$banco_final'
        WHERE ID = $id";
    
    if (mysqli_query($conexion, $sql_update)) {
        registrar_auditoria($conexion, $id_usuario, 'ESTADO_PAGO', 'cotizacion', $id, "Registró pago ($estado_calculado)");
        ob_clean();
        echo json_encode(['success' => true, 'id_cotizacion' => $id, 'pago_index' => 0]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]); 
    }
    exit;
}

if ($accion == 'eliminar') { 
    if (!$puede_eliminar && !$es_master) { 
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [9] para eliminar cotizaciones.']); 
        exit; 
    }
    $id = intval($_POST['id'] ?? 0);
    mysqli_query($conexion, "DELETE FROM detalle_cotizacion WHERE id_cotizacion = $id");
    if (mysqli_query($conexion, "DELETE FROM cotizacion WHERE ID = $id")) {
        ob_clean();
        echo json_encode(['success' => true]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($accion == 'previsualizar_comision') {
    $id = intval($_GET['id'] ?? $_POST['id_cotizacion'] ?? 0);
    $monto = max(0, floatval($_GET['monto'] ?? $_POST['comision_monto'] ?? 0));
    $vinculo = buscarUsuarioVendedorDeCotizacion($conexion, $id);
    $deuda = (float)($vinculo['saldo'] ?? 0);
    ob_clean();
    echo json_encode([
        'success' => true,
        'vendedor_encontrado' => !empty($vinculo['encontrado']),
        'vendedor' => $vinculo['vendedor'] ?? '',
        'deuda_pendiente' => $deuda,
        'descuento' => min($monto, $deuda),
        'neto_pagar' => max(0, $monto - $deuda)
    ]);
    exit;
}

if ($accion == 'obtener_comision') {
    $id = intval($_GET['id'] ?? 0);
    $q = mysqli_query($conexion, "SELECT * FROM cotizacion WHERE ID = $id");
    $data = $q ? mysqli_fetch_assoc($q) : null;
    if ($data) {
        $base_imponible = (floatval($data['sub_total'] ?? 0) + floatval($data['descuento_logistica'] ?? 0) + floatval($data['flete'] ?? 0)) - floatval($data['descuento'] ?? 0);
        $data['base_imponible'] = max(0, $base_imponible);
        ob_clean();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Cotización no encontrada']);
    }
    exit;
}
?>