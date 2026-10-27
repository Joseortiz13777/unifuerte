<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../conexion.php';
require_once __DIR__ . '/../portal_cliente_crypto.php';
mysqli_set_charset($conexion, 'utf8mb4');

function responder_portal_clientes(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
if ($idUsuario < 1) {
    responder_portal_clientes(['success' => false, 'msg' => 'Tu sesión venció. Inicia sesión nuevamente.'], 401);
}

$qUsuario = mysqli_query($conexion, "SELECT permisos FROM usuario WHERE ID = $idUsuario LIMIT 1");
$usuario = $qUsuario ? mysqli_fetch_assoc($qUsuario) : null;
$permisos = array_map('trim', explode(',', (string)($usuario['permisos'] ?? '')));
if (!in_array('22', $permisos, true)) {
    responder_portal_clientes(['success' => false, 'msg' => 'No tienes permiso para enviar cotizaciones por WhatsApp.'], 403);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ($_POST['accion'] ?? '') !== 'credenciales_cotizacion') {
    responder_portal_clientes(['success' => false, 'msg' => 'Acción no válida.'], 400);
}
$csrfSesion = (string)($_SESSION['portal_cliente_csrf'] ?? '');
$csrfEnviado = (string)($_POST['csrf'] ?? '');
if ($csrfSesion === '' || !hash_equals($csrfSesion, $csrfEnviado)) {
    responder_portal_clientes(['success' => false, 'msg' => 'La sesión del formulario venció. Recarga el módulo de ventas.'], 403);
}

if (!mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS portal_cliente_accesos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_cliente INT NOT NULL,
        usuario VARCHAR(20) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        password_encrypted TEXT DEFAULT NULL,
        intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
        bloqueado_hasta DATETIME DEFAULT NULL,
        creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_portal_cliente (id_cliente),
        UNIQUE KEY uq_portal_usuario (usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
")) {
    responder_portal_clientes(['success' => false, 'msg' => 'No se pudo preparar el acceso al portal de clientes.'], 500);
}
if (
    !mysqli_query($conexion, "ALTER TABLE portal_cliente_accesos ADD COLUMN IF NOT EXISTS intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0")
    || !mysqli_query($conexion, "ALTER TABLE portal_cliente_accesos ADD COLUMN IF NOT EXISTS bloqueado_hasta DATETIME DEFAULT NULL")
    || !mysqli_query($conexion, "ALTER TABLE portal_cliente_accesos ADD COLUMN IF NOT EXISTS password_encrypted TEXT DEFAULT NULL")
) {
    responder_portal_clientes(['success' => false, 'msg' => 'No se pudo actualizar la seguridad del acceso del cliente.'], 500);
}

$idCotizacion = (int)($_POST['id_cotizacion'] ?? 0);
$stmtCotizacion = mysqli_prepare($conexion, "
    SELECT c.id_cliente, c.id_vendedor, cli.nombre, cli.tlf, c.estado_pago, c.pago_valor_usd
    FROM cotizacion c
    JOIN cliente cli ON cli.ID = c.id_cliente
    WHERE c.ID = ? AND c.pausado = 0
    LIMIT 1
");
if (!$stmtCotizacion) {
    responder_portal_clientes(['success' => false, 'msg' => 'No se pudo validar la cotización.'], 500);
}
mysqli_stmt_bind_param($stmtCotizacion, 'i', $idCotizacion);
if (!mysqli_stmt_execute($stmtCotizacion)) {
    mysqli_stmt_close($stmtCotizacion);
    responder_portal_clientes(['success' => false, 'msg' => 'No se pudo validar la cotización.'], 500);
}
$cotizacion = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCotizacion));
mysqli_stmt_close($stmtCotizacion);
if (!$cotizacion) {
    responder_portal_clientes(['success' => false, 'msg' => 'No se encontró una cotización activa para compartir.'], 404);
}
if (!in_array('7', $permisos, true) && (int)$cotizacion['id_vendedor'] !== $idUsuario) {
    responder_portal_clientes(['success' => false, 'msg' => 'No tienes permiso para compartir esta cotización.'], 403);
}

$pagosRegistrados = preg_split('/[\s]*\|[\s]*/', str_replace(['<br>', '<br/>', '<br />', "\r", "\n"], '|', (string)$cotizacion['pago_valor_usd']));
$totalAbonado = 0.0;
foreach ($pagosRegistrados as $pago) {
    $limpio = preg_replace('/[^0-9.-]/', '', (string)$pago);
    if ($limpio !== '' && is_numeric($limpio)) $totalAbonado += (float)$limpio;
}
if ($totalAbonado <= 0 || strtolower(trim((string)$cotizacion['estado_pago'])) === 'sin pagar') {
    responder_portal_clientes([
        'success' => true,
        'portal_disponible' => false,
        'nombre' => (string)$cotizacion['nombre'],
        'telefono' => (string)$cotizacion['tlf']
    ]);
}

$idCliente = (int)$cotizacion['id_cliente'];
$stmtAcceso = mysqli_prepare(
    $conexion,
    "SELECT usuario, password_encrypted
     FROM portal_cliente_accesos
     WHERE id_cliente = ?
     LIMIT 1"
);
if (!$stmtAcceso) {
    responder_portal_clientes(['success' => false, 'msg' => 'No se pudo consultar el acceso del cliente.'], 500);
}
mysqli_stmt_bind_param($stmtAcceso, 'i', $idCliente);
if (!mysqli_stmt_execute($stmtAcceso)) {
    mysqli_stmt_close($stmtAcceso);
    responder_portal_clientes(['success' => false, 'msg' => 'No se pudo consultar el acceso del cliente.'], 500);
}
$acceso = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtAcceso));
mysqli_stmt_close($stmtAcceso);

$usuarioPortal = $acceso['usuario'] ?? '';
if ($usuarioPortal === '') {
    for ($intento = 0; $intento < 5; $intento++) {
        $usuarioCandidato = (string)random_int(10000000, 99999999);
        $passwordPortal = (string)random_int(10000000, 99999999);
        $passwordHash = password_hash($passwordPortal, PASSWORD_DEFAULT);
        if (!is_string($passwordHash)) {
            responder_portal_clientes(['success' => false, 'msg' => 'No se pudo proteger la contraseña del cliente.'], 500);
        }
        $passwordCifrada = cifrarContrasenaPortalCliente($passwordPortal);
        if ($passwordCifrada === '') {
            responder_portal_clientes(['success' => false, 'msg' => 'No se pudo proteger la contraseña del portal.'], 500);
        }
        $stmtInsertar = mysqli_prepare(
            $conexion,
            "INSERT INTO portal_cliente_accesos
                (id_cliente, usuario, password_hash, password_encrypted)
             VALUES (?, ?, ?, ?)"
        );
        if (!$stmtInsertar) {
            responder_portal_clientes(['success' => false, 'msg' => 'No se pudo crear el acceso del cliente.'], 500);
        }
        mysqli_stmt_bind_param($stmtInsertar, 'isss', $idCliente, $usuarioCandidato, $passwordHash, $passwordCifrada);
        $insertado = mysqli_stmt_execute($stmtInsertar);
        $errorInsertar = mysqli_stmt_errno($stmtInsertar);
        mysqli_stmt_close($stmtInsertar);
        if ($insertado) {
            $usuarioPortal = $usuarioCandidato;
            responder_portal_clientes([
                'success' => true,
                'portal_disponible' => true,
                'nombre' => (string)$cotizacion['nombre'],
                'telefono' => (string)$cotizacion['tlf'],
                'usuario' => $usuarioPortal,
                'contrasena' => $passwordPortal,
                'credenciales_nuevas' => true,
                'portal_url' => 'https://cliente.soyunifuerte.com/'
            ]);
        }
        if ($errorInsertar !== 1062) {
            responder_portal_clientes(['success' => false, 'msg' => 'No se pudo crear el acceso del cliente.'], 500);
        }
        $stmtAcceso = mysqli_prepare(
            $conexion,
            "SELECT usuario, password_encrypted
             FROM portal_cliente_accesos
             WHERE id_cliente = ?
             LIMIT 1"
        );
        if (!$stmtAcceso) {
            responder_portal_clientes(['success' => false, 'msg' => 'No se pudo consultar el acceso del cliente.'], 500);
        }
        mysqli_stmt_bind_param($stmtAcceso, 'i', $idCliente);
        if (!mysqli_stmt_execute($stmtAcceso)) {
            mysqli_stmt_close($stmtAcceso);
            responder_portal_clientes(['success' => false, 'msg' => 'No se pudo consultar el acceso del cliente.'], 500);
        }
        $accesoResultado = mysqli_stmt_get_result($stmtAcceso);
        $acceso = $accesoResultado ? mysqli_fetch_assoc($accesoResultado) : null;
        mysqli_stmt_close($stmtAcceso);
        if ($acceso) {
            $usuarioPortal = (string)$acceso['usuario'];
            break;
        }
        $usuarioPortal = '';
    }
}

if ($usuarioPortal === '') {
    responder_portal_clientes(['success' => false, 'msg' => 'No se pudo asignar un usuario numérico al cliente. Intenta nuevamente.'], 500);
}

$passwordPortal = '';
if (!empty($acceso['password_encrypted'])) {
    $passwordPortal = descifrarContrasenaPortalCliente((string)$acceso['password_encrypted']);
    if ($passwordPortal === false) {
        responder_portal_clientes(['success' => false, 'msg' => 'No se pudo recuperar la contraseña guardada. Contacta al administrador.'], 500);
    }
}

if ($passwordPortal === '') {
    $passwordPortal = (string)random_int(10000000, 99999999);
    $passwordHash = password_hash($passwordPortal, PASSWORD_DEFAULT);
    if (!is_string($passwordHash)) {
        responder_portal_clientes(['success' => false, 'msg' => 'No se pudo proteger la contraseña del cliente.'], 500);
    }
    $passwordCifrada = cifrarContrasenaPortalCliente($passwordPortal);
    if ($passwordCifrada === '') {
        responder_portal_clientes(['success' => false, 'msg' => 'No se pudo proteger la contraseña del portal.'], 500);
    }
    $stmtActualizar = mysqli_prepare(
        $conexion,
        "UPDATE portal_cliente_accesos
         SET password_hash = ?, password_encrypted = ?,
             intentos_fallidos = 0, bloqueado_hasta = NULL
         WHERE id_cliente = ?"
    );
    if (!$stmtActualizar) {
        responder_portal_clientes(['success' => false, 'msg' => 'No se pudo actualizar la contraseña guardada del cliente.'], 500);
    }
    mysqli_stmt_bind_param($stmtActualizar, 'ssi', $passwordHash, $passwordCifrada, $idCliente);
    $actualizado = mysqli_stmt_execute($stmtActualizar) && mysqli_stmt_affected_rows($stmtActualizar) === 1;
    mysqli_stmt_close($stmtActualizar);
    if (!$actualizado) {
        responder_portal_clientes(['success' => false, 'msg' => 'No se pudo guardar la contraseña del cliente.'], 500);
    }
}

$stmtDesbloquear = mysqli_prepare(
    $conexion,
    "UPDATE portal_cliente_accesos
     SET intentos_fallidos = 0, bloqueado_hasta = NULL
     WHERE id_cliente = ?"
);
if (!$stmtDesbloquear) {
    responder_portal_clientes(['success' => false, 'msg' => 'No se pudo actualizar la seguridad del acceso del cliente.'], 500);
}
mysqli_stmt_bind_param($stmtDesbloquear, 'i', $idCliente);
$desbloqueado = mysqli_stmt_execute($stmtDesbloquear);
mysqli_stmt_close($stmtDesbloquear);
if (!$desbloqueado) {
    responder_portal_clientes(['success' => false, 'msg' => 'No se pudo actualizar la seguridad del acceso del cliente.'], 500);
}

responder_portal_clientes([
    'success' => true,
    'portal_disponible' => true,
    'nombre' => (string)$cotizacion['nombre'],
    'telefono' => (string)$cotizacion['tlf'],
    'usuario' => $usuarioPortal,
    'contrasena' => $passwordPortal,
    'credenciales_nuevas' => false,
    'portal_url' => 'https://cliente.soyunifuerte.com/'
]);
