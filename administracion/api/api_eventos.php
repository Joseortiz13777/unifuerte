<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

function responder_eventos(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
if ($idUsuario < 1) {
    responder_eventos(['success' => false, 'msg' => 'Sesión expirada.'], 401);
}
$qUsuario = mysqli_query($conexion, "SELECT rol FROM usuario WHERE ID = $idUsuario LIMIT 1");
$usuario = $qUsuario ? mysqli_fetch_assoc($qUsuario) : null;
$rol = strtolower(trim((string)($usuario['rol'] ?? '')));
if (!in_array($rol, ['master', 'admin'], true)) {
    responder_eventos(['success' => false, 'msg' => 'Área exclusiva de Administración.'], 403);
}

if (!mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS solicitudes_asesoria (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_cliente INT NOT NULL,
        nombre_cliente VARCHAR(180) NOT NULL,
        telefono_cliente VARCHAR(60) NOT NULL DEFAULT '',
        motivo VARCHAR(120) NOT NULL,
        modalidad VARCHAR(60) NOT NULL,
        fecha_preferida DATETIME NOT NULL,
        necesidad VARCHAR(1000) NOT NULL DEFAULT '',
        estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
        id_usuario_respuesta INT DEFAULT NULL,
        respuesta_en DATETIME DEFAULT NULL,
        nota_respuesta VARCHAR(500) DEFAULT NULL,
        creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_solicitud_estado_fecha (estado, fecha_preferida),
        KEY idx_solicitud_cliente_fecha (id_cliente, creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
")) {
    responder_eventos(['success' => false, 'msg' => 'No se pudo preparar el módulo de eventos.'], 500);
}

$accion = (string)($_POST['accion'] ?? '');
if ($accion === 'listar') {
    $query = mysqli_query($conexion, "
        SELECT id, nombre_cliente, telefono_cliente, motivo, modalidad,
               fecha_preferida, necesidad, estado, creado_en
        FROM solicitudes_asesoria
        ORDER BY FIELD(estado, 'Pendiente', 'Aceptada', 'Rechazada'), fecha_preferida ASC, id DESC
        LIMIT 300
    ");
    if (!$query) {
        responder_eventos(['success' => false, 'msg' => 'No se pudieron cargar las solicitudes de asesoría.'], 500);
    }
    $data = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }
    responder_eventos(['success' => true, 'data' => $data]);
}

if (!in_array($accion, ['resolver', 'eliminar'], true)) {
    responder_eventos(['success' => false, 'msg' => 'Acción no válida.'], 400);
}
$csrfSesion = (string)($_SESSION['admin_eventos_csrf'] ?? '');
$csrfEnviado = (string)($_POST['csrf'] ?? '');
if ($csrfSesion === '' || !hash_equals($csrfSesion, $csrfEnviado)) {
    responder_eventos(['success' => false, 'msg' => 'La sesión del formulario venció. Recarga la página.'], 403);
}

$idEvento = (int)($_POST['id'] ?? 0);
if ($accion === 'eliminar') {
    if ($idEvento < 1) {
        responder_eventos(['success' => false, 'msg' => 'La solicitud indicada no es válida.'], 400);
    }
    $stmtEliminar = mysqli_prepare($conexion, "DELETE FROM solicitudes_asesoria WHERE id = ?");
    if (!$stmtEliminar) {
        responder_eventos(['success' => false, 'msg' => 'No se pudo preparar la eliminación de la solicitud.'], 500);
    }
    mysqli_stmt_bind_param($stmtEliminar, 'i', $idEvento);
    $eliminada = mysqli_stmt_execute($stmtEliminar);
    $filasEliminadas = mysqli_stmt_affected_rows($stmtEliminar);
    mysqli_stmt_close($stmtEliminar);
    if (!$eliminada) {
        responder_eventos(['success' => false, 'msg' => 'No se pudo eliminar la solicitud.'], 500);
    }
    if ($filasEliminadas !== 1) {
        responder_eventos(['success' => false, 'msg' => 'La solicitud ya no existe. Actualiza la lista.'], 404);
    }
    responder_eventos(['success' => true, 'msg' => 'Solicitud eliminada.']);
}

$estado = (string)($_POST['estado'] ?? '');
if ($idEvento < 1 || !in_array($estado, ['Aceptada', 'Rechazada'], true)) {
    responder_eventos(['success' => false, 'msg' => 'La solicitud o decisión no son válidas.'], 400);
}
$stmtEvento = mysqli_prepare($conexion, "SELECT fecha_preferida, estado FROM solicitudes_asesoria WHERE id = ? LIMIT 1");
if (!$stmtEvento) {
    responder_eventos(['success' => false, 'msg' => 'No se pudo validar la solicitud.'], 500);
}
mysqli_stmt_bind_param($stmtEvento, 'i', $idEvento);
if (!mysqli_stmt_execute($stmtEvento)) {
    mysqli_stmt_close($stmtEvento);
    responder_eventos(['success' => false, 'msg' => 'No se pudo validar la solicitud.'], 500);
}
$evento = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtEvento));
mysqli_stmt_close($stmtEvento);
if (!$evento || $evento['estado'] !== 'Pendiente') {
    responder_eventos(['success' => false, 'msg' => 'La solicitud ya fue atendida o no existe. Actualiza la lista.'], 409);
}

if ($estado === 'Aceptada') {
    $stmtConflicto = mysqli_prepare(
        $conexion,
        "SELECT id FROM solicitudes_asesoria
         WHERE estado = 'Aceptada' AND fecha_preferida = ? AND id <> ?
         LIMIT 1"
    );
    if (!$stmtConflicto) {
        responder_eventos(['success' => false, 'msg' => 'No se pudo validar la disponibilidad del horario.'], 500);
    }
    mysqli_stmt_bind_param($stmtConflicto, 'si', $evento['fecha_preferida'], $idEvento);
    if (!mysqli_stmt_execute($stmtConflicto)) {
        mysqli_stmt_close($stmtConflicto);
        responder_eventos(['success' => false, 'msg' => 'No se pudo validar la disponibilidad del horario.'], 500);
    }
    $conflicto = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtConflicto));
    mysqli_stmt_close($stmtConflicto);
    if ($conflicto) {
        responder_eventos(['success' => false, 'msg' => 'Ya hay una asesoría aceptada en ese horario. Contacta al cliente para coordinar otra fecha.'], 409);
    }
}

$stmtActualizar = mysqli_prepare(
    $conexion,
    "UPDATE solicitudes_asesoria
     SET estado = ?, id_usuario_respuesta = ?, respuesta_en = NOW()
     WHERE id = ? AND estado = 'Pendiente'"
);
if (!$stmtActualizar) {
    responder_eventos(['success' => false, 'msg' => 'No se pudo guardar la decisión.'], 500);
}
mysqli_stmt_bind_param($stmtActualizar, 'sii', $estado, $idUsuario, $idEvento);
$actualizado = mysqli_stmt_execute($stmtActualizar) && mysqli_stmt_affected_rows($stmtActualizar) === 1;
mysqli_stmt_close($stmtActualizar);
if (!$actualizado) {
    responder_eventos(['success' => false, 'msg' => 'No se pudo guardar la decisión de la solicitud. Actualiza e inténtalo nuevamente.'], 500);
}

responder_eventos([
    'success' => true,
    'msg' => $estado === 'Aceptada'
        ? 'Asesoría confirmada. Usa el botón WhatsApp para enviar la confirmación al cliente.'
        : 'Solicitud rechazada. Usa el botón WhatsApp para coordinar otra fecha con el cliente.'
]);
