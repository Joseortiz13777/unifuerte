<?php
// Archivo: Unifuerte/entidades/Usuario/crud.php
require_once '../../conexion.php'; $accion = $_POST['accion'] ?? '';
if ($accion == 'crear') {
    $stmt = $pdo->prepare("INSERT INTO usuario (nombre_usuario, contrasena, rol, nombre, apellido, cedula, tlf, profesion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['nombre_usuario'], $_POST['contrasena'], $_POST['rol'], $_POST['nombre'], $_POST['apellido'], $_POST['cedula'], $_POST['tlf'], $_POST['profesion']]);
    echo json_encode(['status' => 'success']); exit;
}
if ($accion == 'editar') {
    $stmt = $pdo->prepare("UPDATE usuario SET nombre_usuario=?, contrasena=?, rol=?, nombre=?, apellido=?, cedula=?, tlf=?, profesion=? WHERE ID=?");
    $stmt->execute([$_POST['nombre_usuario'], $_POST['contrasena'], $_POST['rol'], $_POST['nombre'], $_POST['apellido'], $_POST['cedula'], $_POST['tlf'], $_POST['profesion'], $_POST['id']]);
    echo json_encode(['status' => 'success']); exit;
}
if ($accion == 'eliminar') { $pdo->prepare("DELETE FROM usuario WHERE ID=?")->execute([$_POST['id']]); echo json_encode(['status' => 'success']); exit; }
?>