<?php
// Archivo: Unifuerte/entidades/Compra/crud.php
require_once '../../conexion.php'; 
$accion = $_POST['accion'] ?? '';

if ($accion == 'crear') { 
    $stmt = $pdo->prepare("INSERT INTO compra (id_provedor, fecha_compra, estado, costo) VALUES (?, ?, ?, ?)"); 
    $stmt->execute([$_POST['id_provedor'], $_POST['fecha_compra'], $_POST['estado'], $_POST['costo']]); 
    echo json_encode(['status' => 'success']); 
    exit; 
}
if ($accion == 'editar') { 
    $stmt = $pdo->prepare("UPDATE compra SET id_provedor=?, fecha_compra=?, estado=?, costo=? WHERE ID=?"); 
    $stmt->execute([$_POST['id_provedor'], $_POST['fecha_compra'], $_POST['estado'], $_POST['costo'], $_POST['id']]); 
    echo json_encode(['status' => 'success']); 
    exit; 
}
if ($accion == 'eliminar') { 
    $stmt = $pdo->prepare("DELETE FROM compra WHERE ID=?"); 
    $stmt->execute([$_POST['id']]); 
    echo json_encode(['status' => 'success']); 
    exit; 
}
?>