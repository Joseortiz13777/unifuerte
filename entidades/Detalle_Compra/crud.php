<?php
// Archivo: Unifuerte/entidades/Detalle_Compra/crud.php
require_once '../../conexion.php'; $accion = $_POST['accion'] ?? '';
if ($accion == 'crear') { $stmt = $pdo->prepare("INSERT INTO detalle_compra (id_compra, id_materia, cantidad, precio_unitario) VALUES (?, ?, ?, ?)"); $stmt->execute([$_POST['id_compra'], $_POST['id_materia'], $_POST['cantidad'], $_POST['precio_unitario']]); echo json_encode(['status' => 'success']); exit; }
if ($accion == 'editar') { $stmt = $pdo->prepare("UPDATE detalle_compra SET id_compra=?, id_materia=?, cantidad=?, precio_unitario=? WHERE ID=?"); $stmt->execute([$_POST['id_compra'], $_POST['id_materia'], $_POST['cantidad'], $_POST['precio_unitario'], $_POST['id']]); echo json_encode(['status' => 'success']); exit; }
if ($accion == 'eliminar') { $stmt = $pdo->prepare("DELETE FROM detalle_compra WHERE ID=?"); $stmt->execute([$_POST['id']]); echo json_encode(['status' => 'success']); exit; }
?>