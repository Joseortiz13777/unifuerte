<?php
session_start();
require_once '../conexion.php';
header('Content-Type: application/json');

// Si no hay sesión, no hace nada (evita errores)
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sin sesión']); 
    exit;
}

$id_usuario = intval($_SESSION['usuario_id']);
$accion = mysqli_real_escape_string($conexion, $_POST['accion'] ?? 'ALERTA DE ROBO');
$tabla = mysqli_real_escape_string($conexion, $_POST['modulo'] ?? 'SISTEMA');
$detalle = mysqli_real_escape_string($conexion, $_POST['detalle'] ?? 'Actividad sospechosa');

// Guarda el movimiento sigilosamente en el historial
$sql = "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) 
        VALUES ($id_usuario, '$accion', '$tabla', 0, NOW(), '$detalle')";

if (mysqli_query($conexion, $sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>