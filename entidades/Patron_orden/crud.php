<?php
// Archivo: Unifuerte/entidades/Patron_orden/crud.php
require_once '../../conexion.php'; 
$accion = $_POST['accion'] ?? '';

if ($accion == 'crear') { 
    $stmt = $pdo->prepare("INSERT INTO patron_ordencomp (Orden_Compra, Patron, id_tarea) VALUES (?, ?, ?)"); 
    $stmt->execute([$_POST['Orden_Compra'], $_POST['Patron'], $_POST['id_tarea']]); 
    echo json_encode(['status' => 'success']); 
    exit; 
}
if ($accion == 'editar') { 
    $stmt = $pdo->prepare("UPDATE patron_ordencomp SET Orden_Compra=?, Patron=?, id_tarea=? WHERE ID=?"); 
    $stmt->execute([$_POST['Orden_Compra'], $_POST['Patron'], $_POST['id_tarea'], $_POST['id']]); 
    echo json_encode(['status' => 'success']); 
    exit; 
}
if ($accion == 'eliminar') { 
    $stmt = $pdo->prepare("DELETE FROM patron_ordencomp WHERE ID=?"); 
    $stmt->execute([$_POST['id']]); 
    echo json_encode(['status' => 'success']); 
    exit; 
}
?>