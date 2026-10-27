<?php
// Archivo: guardar_datos.php

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['html'])) {
    
    // Verificamos qué archivo JSON nos envía JavaScript. Si no envía, por defecto es el de la v15.
    $nombre_archivo = isset($data['archivo']) ? $data['archivo'] : 'documento_data_v15.json';
    
    // Por seguridad, limpiamos el nombre para que no se guarden archivos fuera de la carpeta
    $nombre_archivo = basename($nombre_archivo);

    // Guardamos apuntando al archivo dinámico que corresponda (Marinicol o Unifuerte)
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