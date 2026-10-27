<?php
// Archivo: Unifuerte/Documentos/Formatos_126_144/guardar_datos.php

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['html'])) {
    
    // Identifica el JSON objetivo enviado por JS. Si es Unifuerte usa la versión v13 por defecto
    $nombre_archivo = isset($data['archivo']) ? $data['archivo'] : 'documento_data_v13.json';
    $nombre_archivo = basename($nombre_archivo); // Seguridad extra

    // IMPORTANTE: Guardamos apuntando a v13 (o marinicol) para que procese el contrato con sus 19 páginas exactas
    $resultado = file_put_contents($nombre_archivo, json_encode(['html' => $data['html']]));
    
    if ($resultado !== false) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo escribir el archivo.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos.']);
}
?>