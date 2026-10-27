<?php
// Archivo: Unifuerte/Documentos/Formatos_62_72/guardar_datos.php

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['html'])) {
    
    // Identificamos el JSON enviado por JS. Si es Unifuerte usa la versi«Ñn v4
    $nombre_archivo = isset($data['archivo']) ? $data['archivo'] : 'documento_data_v4.json';
    $nombre_archivo = basename($nombre_archivo); // Seguridad extra

    // IMPORTANTE: Guardamos apuntando a v4 (o a marinicol) para que aplique el nuevo dise«Ðo centrado
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