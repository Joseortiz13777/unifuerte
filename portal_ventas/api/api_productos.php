<?php
// portal_ventas/api_productos.php
header('Content-Type: application/json');
$conexion = mysqli_connect("localhost", "root", "", "unifuerte_bd");
mysqli_set_charset($conexion, "utf8mb4");

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

if ($accion == 'listar') {
    $query = mysqli_query($conexion, "SELECT * FROM productos ORDER BY orden ASC, nombre ASC");
    $productos = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $productos[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $productos]);
}

elseif ($accion == 'guardar') {
    $id = intval($_POST['id_producto'] ?? 0);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre'] ?? '');
    $categoria = mysqli_real_escape_string($conexion, $_POST['categoria'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? '');
    $etiqueta = mysqli_real_escape_string($conexion, $_POST['etiqueta'] ?? '');
    $orden = intval($_POST['orden'] ?? 0);

    // Lógica para subir la foto
    $foto_sql = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $nombre_archivo = time() . '_' . basename($_FILES['foto']['name']);
        $ruta_destino = 'images/uploads/' . $nombre_archivo;
        
        // Asegúrate de que la carpeta exista
        if (!file_exists('images/uploads')) {
            mkdir('images/uploads', 0777, true);
        }

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
            $foto_sql = ", foto='$nombre_archivo'";
        }
    }

    if ($id > 0) {
        // Actualizar
        $sql = "UPDATE productos SET nombre='$nombre', categoria='$categoria', precio='$precio', descripcion='$descripcion', etiqueta='$etiqueta', orden='$orden' $foto_sql WHERE id=$id";
    } else {
        // Insertar
        $foto_insert = isset($nombre_archivo) ? "'$nombre_archivo'" : "NULL";
        $sql = "INSERT INTO productos (nombre, categoria, precio, descripcion, etiqueta, orden, foto) 
                VALUES ('$nombre', '$categoria', '$precio', '$descripcion', '$etiqueta', '$orden', $foto_insert)";
    }

    if (mysqli_query($conexion, $sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
}

elseif ($accion == 'eliminar') {
    $id = intval($_POST['id_producto'] ?? 0);
    mysqli_query($conexion, "DELETE FROM productos WHERE id=$id");
    echo json_encode(['success' => true]);
}
?>