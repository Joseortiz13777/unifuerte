<?php
// Archivo: Unifuerte/entidades/Provedor/crud.php
require_once '../../conexion.php';

$accion = $_POST['accion'] ?? '';

if ($accion == 'crear') {
    $stmt = $pdo->prepare("INSERT INTO provedor (nombre, contacto, tlf, flete_internacional, ubicacion, RIF_cedula, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['nombre'], $_POST['contacto'], $_POST['tlf'], $_POST['flete_internacional'], $_POST['ubicacion'], $_POST['RIF_cedula'], $_POST['descripcion']]);
    echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
    exit;
}

if ($accion == 'editar') {
    $stmt = $pdo->prepare("UPDATE provedor SET nombre=?, contacto=?, tlf=?, flete_internacional=?, ubicacion=?, RIF_cedula=?, descripcion=? WHERE ID=?");
    $stmt->execute([$_POST['nombre'], $_POST['contacto'], $_POST['tlf'], $_POST['flete_internacional'], $_POST['ubicacion'], $_POST['RIF_cedula'], $_POST['descripcion'], $_POST['id']]);
    echo json_encode(['status' => 'success']);
    exit;
}

if ($accion == 'eliminar') {
    $stmt = $pdo->prepare("DELETE FROM provedor WHERE ID=?");
    $stmt->execute([$_POST['id']]);
    echo json_encode(['status' => 'success']);
    exit;
}
?>