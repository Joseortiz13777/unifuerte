<?php
session_start();
error_reporting(0);
require_once '../../../conexion.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesiиоn expirada']); exit;
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$catalogo = $_POST['catalogo'] ?? $_GET['catalogo'] ?? '';

$tabla = ($catalogo === 'banco') ? 'sys_bancos' : 'sys_tipo_proveedor';

// Auto-crear la tabla en la BD si no existe para no tener que hacerlo a mano
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS $tabla (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL UNIQUE)");

if ($accion === 'listar') {
    $q = mysqli_query($conexion, "SELECT id, nombre AS text FROM $tabla ORDER BY nombre ASC");
    $data = [];
    if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode($data);
    exit;
} 
elseif ($accion === 'agregar') {
    $nombre = mysqli_real_escape_string($conexion, trim($_POST['nombre']));
    
    if(mysqli_query($conexion, "INSERT INTO $tabla (nombre) VALUES ('$nombre')")) {
        $id = mysqli_insert_id($conexion);
        echo json_encode(['success' => true, 'id' => $id, 'text' => $nombre]);
    } else {
        // Si ya existикa, lo buscamos para devolver su ID
        $q = mysqli_query($conexion, "SELECT id, nombre AS text FROM $tabla WHERE nombre = '$nombre'");
        if($q && $row = mysqli_fetch_assoc($q)) {
            echo json_encode(['success' => true, 'id' => $row['id'], 'text' => $row['text']]);
        } else {
            echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
        }
    }
    exit;
}
?>