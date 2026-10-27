<?php
session_start();
require_once '../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');
header('Content-Type: application/json; charset=utf-8');

$idUsuario = intval($_SESSION['usuario_id'] ?? 0);
session_write_close();

function responder_archivador($datos, $estado = 200) {
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($idUsuario < 1) {
    responder_archivador(['success' => false, 'msg' => 'Sesión expirada.'], 401);
}

$qUsuario = mysqli_query($conexion, "SELECT rol FROM usuario WHERE ID = $idUsuario");
$usuario = $qUsuario ? mysqli_fetch_assoc($qUsuario) : null;
if (strtolower(trim($usuario['rol'] ?? '')) !== 'master') {
    responder_archivador(['success' => false, 'msg' => 'Solo el usuario Master puede acceder al archivador.'], 403);
}

$crearArchivo = mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS cotizaciones_eliminadas (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        cotizacion_id_original INT NOT NULL,
        nro_cotizacion VARCHAR(100) NOT NULL,
        cliente_nombre VARCHAR(255) DEFAULT NULL,
        fecha_eliminacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        id_usuario_elimino INT NOT NULL,
        datos_cotizacion LONGTEXT NOT NULL,
        datos_detalles LONGTEXT NOT NULL,
        datos_ordenes LONGTEXT NOT NULL,
        fecha_restauracion DATETIME DEFAULT NULL,
        id_usuario_restauro INT DEFAULT NULL,
        KEY idx_cotizacion_original (cotizacion_id_original),
        KEY idx_fecha_eliminacion (fecha_eliminacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
if (!$crearArchivo) {
    responder_archivador(['success' => false, 'msg' => 'No se pudo preparar el archivador: ' . mysqli_error($conexion)], 500);
}

function insertar_fila_archivada($conexion, $tabla, $fila) {
    if (!is_array($fila) || !$fila) {
        return false;
    }
    $columnas = [];
    $valores = [];
    foreach ($fila as $columna => $valor) {
        $columnas[] = '`' . str_replace('`', '``', $columna) . '`';
        $valores[] = $valor === null
            ? 'NULL'
            : "'" . mysqli_real_escape_string($conexion, (string)$valor) . "'";
    }
    return mysqli_query(
        $conexion,
        'INSERT INTO `' . $tabla . '` (' . implode(', ', $columnas) . ') VALUES (' . implode(', ', $valores) . ')'
    );
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

if ($accion === 'listar') {
    $q = mysqli_query($conexion, "
        SELECT a.id, a.cotizacion_id_original, a.nro_cotizacion, a.cliente_nombre,
               a.id_usuario_elimino, a.fecha_eliminacion, a.fecha_restauracion,
               ue.nombre AS nombre_elimino, ue.apellido AS apellido_elimino,
               ur.nombre AS nombre_restauro, ur.apellido AS apellido_restauro,
               JSON_LENGTH(a.datos_detalles) AS cantidad_detalles,
               JSON_LENGTH(a.datos_ordenes) AS cantidad_ordenes
        FROM cotizaciones_eliminadas a
        LEFT JOIN usuario ue ON ue.ID = a.id_usuario_elimino
        LEFT JOIN usuario ur ON ur.ID = a.id_usuario_restauro
        ORDER BY a.id DESC
        LIMIT 500
    ");
    if (!$q) {
        responder_archivador(['success' => false, 'msg' => 'No se pudo cargar el historial: ' . mysqli_error($conexion)], 500);
    }
    $archivos = [];
    while ($fila = mysqli_fetch_assoc($q)) {
        $archivos[] = $fila;
    }
    responder_archivador(['success' => true, 'data' => $archivos]);
}

if ($accion === 'restaurar') {
    $idArchivo = intval($_POST['id_archivo'] ?? 0);
    if ($idArchivo < 1) {
        responder_archivador(['success' => false, 'msg' => 'Identificador de archivo no válido.'], 400);
    }
    if (!mysqli_begin_transaction($conexion)) {
        responder_archivador(['success' => false, 'msg' => 'No se pudo iniciar la restauración.'], 500);
    }

    $qArchivo = mysqli_query($conexion, "SELECT * FROM cotizaciones_eliminadas WHERE id = $idArchivo FOR UPDATE");
    $archivo = $qArchivo ? mysqli_fetch_assoc($qArchivo) : null;
    if (!$archivo) {
        mysqli_rollback($conexion);
        responder_archivador(['success' => false, 'msg' => 'No se encontró la cotización archivada.'], 404);
    }
    if (!empty($archivo['fecha_restauracion'])) {
        mysqli_rollback($conexion);
        responder_archivador(['success' => false, 'msg' => 'Esta cotización ya fue restaurada.'], 409);
    }

    $cotizacion = json_decode($archivo['datos_cotizacion'], true);
    $detalles = json_decode($archivo['datos_detalles'], true);
    $ordenes = json_decode($archivo['datos_ordenes'], true);
    if (!is_array($cotizacion) || !is_array($detalles) || !is_array($ordenes)
        || intval($cotizacion['ID'] ?? 0) !== intval($archivo['cotizacion_id_original'])
        || trim((string)($cotizacion['nro_cotizacion'] ?? '')) !== $archivo['nro_cotizacion']) {
        mysqli_rollback($conexion);
        responder_archivador(['success' => false, 'msg' => 'El archivo está incompleto o no coincide con sus datos de referencia.'], 500);
    }

    $idCotizacion = intval($archivo['cotizacion_id_original']);
    $nroCotizacionSql = mysqli_real_escape_string($conexion, $archivo['nro_cotizacion']);
    $qConflicto = mysqli_query($conexion, "
        SELECT ID, nro_cotizacion
        FROM cotizacion
        WHERE ID = $idCotizacion OR nro_cotizacion = '$nroCotizacionSql'
        LIMIT 1
        FOR UPDATE
    ");
    if (!$qConflicto) {
        mysqli_rollback($conexion);
        responder_archivador(['success' => false, 'msg' => 'No se pudo validar si la cotización ya existe: ' . mysqli_error($conexion)], 500);
    }
    if (mysqli_num_rows($qConflicto) > 0) {
        $conflicto = mysqli_fetch_assoc($qConflicto);
        mysqli_rollback($conexion);
        responder_archivador([
            'success' => false,
            'msg' => 'No se restauró para evitar sobrescribir otra cotización. Ya existe ID ' . $conflicto['ID'] . ' / ' . $conflicto['nro_cotizacion'] . '.'
        ], 409);
    }

    if (!insertar_fila_archivada($conexion, 'cotizacion', $cotizacion)) {
        $error = mysqli_error($conexion);
        mysqli_rollback($conexion);
        responder_archivador(['success' => false, 'msg' => 'No se pudo restaurar la cotización: ' . $error], 500);
    }

    foreach ($detalles as $detalle) {
        if (!is_array($detalle)) {
            mysqli_rollback($conexion);
            responder_archivador(['success' => false, 'msg' => 'El archivo contiene un detalle inválido.'], 500);
        }
        unset($detalle['ID']);
        $detalle['id_cotizacion'] = $idCotizacion;
        if (!insertar_fila_archivada($conexion, 'detalle_cotizacion', $detalle)) {
            $error = mysqli_error($conexion);
            mysqli_rollback($conexion);
            responder_archivador(['success' => false, 'msg' => 'No se pudieron restaurar los detalles: ' . $error], 500);
        }
    }

    $idsOrdenes = array_values(array_unique(array_filter(array_map('intval', $ordenes), function ($id) {
        return $id > 0;
    })));
    $ordenesVinculadas = 0;
    if ($idsOrdenes) {
        $listaIds = implode(',', $idsOrdenes);
        $qActualizarOrdenes = mysqli_query($conexion, "
            UPDATE orden_fabrica
            SET id_cotizacion = $idCotizacion
            WHERE ID IN ($listaIds)
              AND id_cotizacion IS NULL
              AND id_cliente = " . intval($cotizacion['id_cliente'])
        );
        if (!$qActualizarOrdenes) {
            $error = mysqli_error($conexion);
            mysqli_rollback($conexion);
            responder_archivador(['success' => false, 'msg' => 'La cotización se restauró, pero no se pudieron recuperar sus órdenes: ' . $error], 500);
        }
        $ordenesVinculadas = mysqli_affected_rows($conexion);
    }

    $qMarcarRestaurada = mysqli_query($conexion, "
        UPDATE cotizaciones_eliminadas
        SET fecha_restauracion = NOW(), id_usuario_restauro = $idUsuario
        WHERE id = $idArchivo AND fecha_restauracion IS NULL
    ");
    if (!$qMarcarRestaurada || mysqli_affected_rows($conexion) !== 1) {
        $error = mysqli_error($conexion);
        mysqli_rollback($conexion);
        responder_archivador(['success' => false, 'msg' => 'No se pudo marcar el archivo como restaurado: ' . $error], 500);
    }

    if (!mysqli_commit($conexion)) {
        mysqli_rollback($conexion);
        responder_archivador(['success' => false, 'msg' => 'No se pudo confirmar la restauración.'], 500);
    }
    responder_archivador([
        'success' => true,
        'msg' => 'Cotización restaurada correctamente.',
        'ordenes_vinculadas' => $ordenesVinculadas
    ]);
}

responder_archivador(['success' => false, 'msg' => 'Acción no válida.'], 400);
