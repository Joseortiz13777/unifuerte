<?php
// Archivo: Unifuerte/entidades/Orden_Compra/crud.php
require_once '../../conexion.php'; 
$accion = $_POST['accion'] ?? '';

// === LÓGICA MÁGICA PARA SUBIR LA FOTO AL SERVIDOR ===
$foto_path = $_POST['foto_actual'] ?? null;
if (isset($_FILES['foto_file']) && $_FILES['foto_file']['error'] === UPLOAD_ERR_OK) {
    $dir = '../../uploads/';
    if (!is_dir($dir)) mkdir($dir, 0777, true); // Crea la carpeta automáticamente si no existe
    
    // Generar un nombre seguro y único para la imagen
    $extension = pathinfo($_FILES['foto_file']['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . rand(1000, 9999) . '.' . $extension;
    
    // Mover el archivo a la carpeta principal /uploads
    if (move_uploaded_file($_FILES['foto_file']['tmp_name'], $dir . $filename)) {
        $foto_path = $filename; // Guardamos solo el nombre de la foto en la BD
    }
}

if ($accion == 'crear') { 
    $stmt = $pdo->prepare("INSERT INTO orden_compra (id_cliente, nro_orden_cotizacion, fecha_emision, fecha_entrega, es_express, id_usuario_vendedor, acepta_entregas_parciales, descripcion, foto, sub_total, Flete, iva, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); 
    $stmt->execute([$_POST['id_cliente'], $_POST['nro_orden_cotizacion'], $_POST['fecha_emision'], $_POST['fecha_entrega'], $_POST['es_express'], $_POST['id_usuario_vendedor'], $_POST['acepta_entregas_parciales'], $_POST['descripcion'], $foto_path, $_POST['sub_total'], $_POST['Flete'], $_POST['iva'], $_POST['total']]); 
    echo json_encode(['status' => 'success']); exit; 
}

if ($accion == 'editar') { 
    $stmt = $pdo->prepare("UPDATE orden_compra SET id_cliente=?, nro_orden_cotizacion=?, fecha_emision=?, fecha_entrega=?, es_express=?, id_usuario_vendedor=?, acepta_entregas_parciales=?, descripcion=?, foto=?, sub_total=?, Flete=?, iva=?, total=? WHERE ID=?"); 
    $stmt->execute([$_POST['id_cliente'], $_POST['nro_orden_cotizacion'], $_POST['fecha_emision'], $_POST['fecha_entrega'], $_POST['es_express'], $_POST['id_usuario_vendedor'], $_POST['acepta_entregas_parciales'], $_POST['descripcion'], $foto_path, $_POST['sub_total'], $_POST['Flete'], $_POST['iva'], $_POST['total'], $_POST['id']]); 
    echo json_encode(['status' => 'success']); exit; 
}

if ($accion == 'eliminar') { 
    $pdo->prepare("DELETE FROM orden_compra WHERE ID=?")->execute([$_POST['id']]); 
    echo json_encode(['status' => 'success']); exit; 
}
?>