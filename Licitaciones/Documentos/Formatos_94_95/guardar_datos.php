<?php
// Archivo: Unifuerte/Documentos/Formatos_94_95/guardar_datos.php

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['html'])) {
    
    // Identifica el JSON objetivo enviado por JS. Si es Unifuerte usa la versión v7 por defecto
    $nombre_archivo = isset($data['archivo']) ? $data['archivo'] : 'documento_data_v7.json';
    $nombre_archivo = basename($nombre_archivo); // Seguridad extra

    // Guardamos la información apuntando al archivo dinámico que corresponda
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