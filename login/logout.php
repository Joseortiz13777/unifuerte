<?php
session_start();

// ==========================================
// AUDITORÍA: REGISTRO DE SALIDA (TIEMPO)
// ==========================================
if (isset($_SESSION['usuario_id'])) {
    require_once '../conexion.php';
    $id_u = intval($_SESSION['usuario_id']);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_u, 'SISTEMA', 'SESIÓN', 0, NOW(), 'Cierre de sesión (Salió del sistema). IP: $ip')");
}

// Vaciamos y destruimos la sesión
$_SESSION = array();
session_destroy();

// Le decimos al navegador que OLVIDE la página anterior
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Fecha en el pasado

// Redirigimos al login
header("Location: index.php");
exit();
?>