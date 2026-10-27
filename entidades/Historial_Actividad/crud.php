<?php
// Archivo: Unifuerte/entidades/Historial_Actividad/crud.php
require_once '../../conexion.php'; 
$accion = $_POST['accion'] ?? '';

if ($accion == 'crear') { 
    $stmt = $pdo->prepare("INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES (?, ?, ?, ?, ?, ?)"); 
    $stmt->execute([$_POST['id_usuario'], $_POST['accion_txt'], $_POST['tabla_afectada'], $_POST['id_registro_afectado'], $_POST['fecha_hora'], $_POST['detalle_cambio']]); 
    echo json_encode(['status' => 'success']); exit; 
}
if ($accion == 'editar') { 
    $stmt = $pdo->prepare("UPDATE historial_actividad SET id_usuario=?, accion=?, tabla_afectada=?, id_registro_afectado=?, fecha_hora=?, detalle_cambio=? WHERE ID=?"); 
    $stmt->execute([$_POST['id_usuario'], $_POST['accion_txt'], $_POST['tabla_afectada'], $_POST['id_registro_afectado'], $_POST['fecha_hora'], $_POST['detalle_cambio'], $_POST['id']]); 
    echo json_encode(['status' => 'success']); exit; 
}
if ($accion == 'eliminar') { 
    $stmt = $pdo->prepare("DELETE FROM historial_actividad WHERE ID=?"); 
    $stmt->execute([$_POST['id']]); 
    echo json_encode(['status' => 'success']); exit; 
}
?>