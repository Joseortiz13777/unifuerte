<?php
// Archivo: Unifuerte/entidades/Tarea/crud.php
require_once '../../conexion.php'; 
$accion = $_POST['accion'] ?? '';

if ($accion == 'crear') { 
    $stmt = $pdo->prepare("INSERT INTO tareas (Orden_Compra, Usuario, estado) VALUES (?, ?, ?)"); 
    $stmt->execute([$_POST['Orden_Compra'], $_POST['Usuario'], $_POST['estado']]); 
    echo json_encode(['status' => 'success']); 
    exit; 
}
if ($accion == 'editar') { 
    $stmt = $pdo->prepare("UPDATE tareas SET Orden_Compra=?, Usuario=?, estado=? WHERE ID=?"); 
    $stmt->execute([$_POST['Orden_Compra'], $_POST['Usuario'], $_POST['estado'], $_POST['id']]); 
    echo json_encode(['status' => 'success']); 
    exit; 
}
if ($accion == 'eliminar') { 
    $stmt = $pdo->prepare("DELETE FROM tareas WHERE ID=?"); 
    $stmt->execute([$_POST['id']]); 
    echo json_encode(['status' => 'success']); 
    exit; 
}
?>