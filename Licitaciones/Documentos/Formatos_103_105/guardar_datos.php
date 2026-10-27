<?php
// Archivo: Unifuerte/Documentos/Formatos_103_105/guardar_datos.php

// Recibe los datos enviados por Javascript
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['html'])) {
    
    // Identifica el JSON objetivo enviado por JS
    $nombre_archivo = isset($data['archivo']) ? $data['archivo'] : 'formato_103_105_data.json';
    $nombre_archivo = basename($nombre_archivo); // Seguridad extra

    // Guarda el HTML directamente en el archivo JSON específico
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