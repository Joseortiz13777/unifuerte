<?php
// Archivo: Unifuerte/entidades/Mat_Prov/crud.php
require_once '../../conexion.php'; $accion = $_POST['accion'] ?? '';
if ($accion == 'crear') { $stmt = $pdo->prepare("INSERT INTO mat_prov (id_provedor, id_materia, precio_actual, calidad, presentacion, fecha_actualizacion) VALUES (?, ?, ?, ?, ?, ?)"); $stmt->execute([$_POST['id_provedor'], $_POST['id_materia'], $_POST['precio_actual'], $_POST['calidad'], $_POST['presentacion'], $_POST['fecha_actualizacion']]); echo json_encode(['status' => 'success']); exit; }
if ($accion == 'editar') { $stmt = $pdo->prepare("UPDATE mat_prov SET id_provedor=?, id_materia=?, precio_actual=?, calidad=?, presentacion=?, fecha_actualizacion=? WHERE ID=?"); $stmt->execute([$_POST['id_provedor'], $_POST['id_materia'], $_POST['precio_actual'], $_POST['calidad'], $_POST['presentacion'], $_POST['fecha_actualizacion'], $_POST['id']]); echo json_encode(['status' => 'success']); exit; }
if ($accion == 'eliminar') { $stmt = $pdo->prepare("DELETE FROM mat_prov WHERE ID=?"); $stmt->execute([$_POST['id']]); echo json_encode(['status' => 'success']); exit; }
?>