<?php
// Archivo: Unifuerte/entidades/Cliente/crud.php
require_once '../../conexion.php';

$accion = $_POST['accion'] ?? '';

if ($accion == 'crear') {
    $stmt = $pdo->prepare("INSERT INTO cliente (nombre, cedula_Rif, direccion, comprador, email, tlf) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['nombre'], $_POST['cedula_Rif'], $_POST['direccion'], $_POST['comprador'], $_POST['email'], $_POST['tlf']]);
    echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
    exit;
}

if ($accion == 'editar') {
    $stmt = $pdo->prepare("UPDATE cliente SET nombre=?, cedula_Rif=?, direccion=?, comprador=?, email=?, tlf=? WHERE ID=?");
    $stmt->execute([$_POST['nombre'], $_POST['cedula_Rif'], $_POST['direccion'], $_POST['comprador'], $_POST['email'], $_POST['tlf'], $_POST['id']]);
    echo json_encode(['status' => 'success']);
    exit;
}

if ($accion == 'eliminar') {
    $stmt = $pdo->prepare("DELETE FROM cliente WHERE ID=?");
    $stmt->execute([$_POST['id']]);
    echo json_encode(['status' => 'success']);
    exit;
}
?>