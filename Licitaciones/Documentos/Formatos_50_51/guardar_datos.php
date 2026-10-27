<?php
// Archivo: Unifuerte/Documentos/Formatos_50_51/guardar_datos.php

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['html'])) {
    
    // Identificamos el JSON enviado por JS. Si es Unifuerte usa la versi«Ñn v11
    $nombre_archivo = isset($data['archivo']) ? $data['archivo'] : 'documento_data_50_51_v11.json';
    $nombre_archivo = basename($nombre_archivo); // Seguridad extra

    // IMPORTANTE: Guardamos apuntando a la v11 (o a marinicol) para que trabaje con la tabla peque«Ða
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