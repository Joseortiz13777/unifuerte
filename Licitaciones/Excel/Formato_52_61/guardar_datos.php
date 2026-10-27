<?php
// Archivo: Unifuerte/Excel/Formatos_52_61/guardar_datos.php

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['html'])) {
    
    // Identificamos el JSON enviado por JS. Si es Unifuerte usa la versiиоn v27
    $nombre_archivo = isset($data['archivo']) ? $data['archivo'] : 'documento_data_v27.json';
    $nombre_archivo = basename($nombre_archivo); // Seguridad extra

    // Guarda el HTML (incluyendo las filas nuevas o eliminadas) en el archivo JSON correspondiente
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