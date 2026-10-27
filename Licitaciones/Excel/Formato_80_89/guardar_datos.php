<?php
// Archivo: Unifuerte/Excel/Formatos_80_89/guardar_datos.php

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['html'])) {
    
    // Identifica el JSON objetivo enviado por JS. Si es Unifuerte usa la versiиоn v38 por defecto
    $nombre_archivo = isset($data['archivo']) ? $data['archivo'] : 'documento_data_v38.json';
    $nombre_archivo = basename($nombre_archivo); // Seguridad extra

    // Guarda el HTML directamente en el archivo JSON especикfico
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