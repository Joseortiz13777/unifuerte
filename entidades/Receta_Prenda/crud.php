<?php
// Archivo: Unifuerte/entidades/Receta_Prenda/crud.php
require_once '../../conexion.php'; $accion = $_POST['accion'] ?? '';
if ($accion == 'crear') { $stmt = $pdo->prepare("INSERT INTO receta_prenda (tipo_prenda, id_materia_prima, cantidad_por_unidad, Costo_unitario) VALUES (?, ?, ?, ?)"); $stmt->execute([$_POST['tipo_prenda'], $_POST['id_materia_prima'], $_POST['cantidad_por_unidad'], $_POST['Costo_unitario']]); echo json_encode(['status' => 'success']); exit; }
if ($accion == 'editar') { $stmt = $pdo->prepare("UPDATE receta_prenda SET tipo_prenda=?, id_materia_prima=?, cantidad_por_unidad=?, Costo_unitario=? WHERE ID=?"); $stmt->execute([$_POST['tipo_prenda'], $_POST['id_materia_prima'], $_POST['cantidad_por_unidad'], $_POST['Costo_unitario'], $_POST['id']]); echo json_encode(['status' => 'success']); exit; }
if ($accion == 'eliminar') { $stmt = $pdo->prepare("DELETE FROM receta_prenda WHERE ID=?"); $stmt->execute([$_POST['id']]); echo json_encode(['status' => 'success']); exit; }
?>