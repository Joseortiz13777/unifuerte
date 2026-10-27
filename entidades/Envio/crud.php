<?php
// Archivo: Unifuerte/entidades/Envio/crud.php
require_once '../../conexion.php'; $accion = $_POST['accion'] ?? '';
$campos = ['id_orden_compra', 'empresa_courier', 'numero_seguimiento', 'agencia_origen', 'agencia_destino', 'destinatario', 'peso', 'fecha_ingreso_orden_confeccion', 'fecha_insumo_textil_listo', 'fecha_envio_tetris_sublimacion', 'fecha_toma_medidas', 'fecha_corte', 'fecha_inicio_confeccion', 'fecha_limpieza_empacado', 'fecha_listo_enviar'];
if ($accion == 'crear') {
    $sql = "INSERT INTO envio (" . implode(',', $campos) . ") VALUES (" . str_repeat('?,', count($campos)-1) . "?)";
    $valores = array_map(function($c) { return ($_POST[$c] === '') ? null : $_POST[$c]; }, $campos); 
    $pdo->prepare($sql)->execute($valores); echo json_encode(['status' => 'success']); exit;
}
if ($accion == 'editar') {
    $sql = "UPDATE envio SET " . implode('=?,', $campos) . "=? WHERE ID=?";
    $valores = array_map(function($c) { return ($_POST[$c] === '') ? null : $_POST[$c]; }, $campos); $valores[] = $_POST['id']; 
    $pdo->prepare($sql)->execute($valores); echo json_encode(['status' => 'success']); exit;
}
if ($accion == 'eliminar') { $pdo->prepare("DELETE FROM envio WHERE ID=?")->execute([$_POST['id']]); echo json_encode(['status' => 'success']); exit; }
?>