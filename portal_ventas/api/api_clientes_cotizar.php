<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión requerida.']);
    exit;
}

$rol_actual = strtolower((string)($_SESSION['rol'] ?? ''));
if (!in_array($rol_actual, ['master', 'ventas', 'jefe_ventas'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes acceso a este módulo.']);
    exit;
}

function asegurar_tablas_clientes_cotizar($conexion) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS clientes_cotizar (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            empresa VARCHAR(255) DEFAULT '',
            telefono VARCHAR(80) DEFAULT '',
            producto VARCHAR(255) DEFAULT '',
            estado VARCHAR(30) DEFAULT 'pendiente',
            fecha_contacto_wa DATE NULL,
            asignado VARCHAR(255) DEFAULT '',
            vendedor_id INT NULL,
            directorio_cliente_id INT NULL,
            archivado TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS clientes_cotizar_notas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            texto TEXT NOT NULL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_cliente_id (cliente_id),
            CONSTRAINT fk_clientes_cotizar_notas FOREIGN KEY (cliente_id) REFERENCES clientes_cotizar(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS clientes_cotizar_fotos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            nombre_archivo VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            tipo VARCHAR(80) DEFAULT '',
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_cliente_id (cliente_id),
            CONSTRAINT fk_clientes_cotizar_fotos FOREIGN KEY (cliente_id) REFERENCES clientes_cotizar(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($queries as $sql) {
        if (!mysqli_query($conexion, $sql)) {
            throw new RuntimeException(mysqli_error($conexion));
        }
    }

    $columnas = [
        'fecha_contacto_wa' => "ALTER TABLE clientes_cotizar ADD COLUMN fecha_contacto_wa DATE NULL",
        'vendedor_id' => "ALTER TABLE clientes_cotizar ADD COLUMN vendedor_id INT NULL",
        'directorio_cliente_id' => "ALTER TABLE clientes_cotizar ADD COLUMN directorio_cliente_id INT NULL"
    ];
    foreach ($columnas as $nombre => $sql) {
        $result = mysqli_query($conexion, "SHOW COLUMNS FROM clientes_cotizar LIKE '$nombre'");
        if (!$result) {
            throw new RuntimeException(mysqli_error($conexion));
        }
        if (mysqli_num_rows($result) === 0 && !mysqli_query($conexion, $sql)) {
            throw new RuntimeException(mysqli_error($conexion));
        }
    }
}

function guardar_archivo_cliente($conexion, $file, $cliente_id) {
    $directorio = __DIR__ . '/../../images/clientes_cotizar/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0777, true);
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
    if ($finfo) {
        finfo_close($finfo);
    }
    $extensiones_permitidas = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/bmp' => 'bmp',
        'application/pdf' => 'pdf'
    ];
    if (!isset($extensiones_permitidas[$mime])) {
        return null;
    }

    $nombre_seguro = 'cotizar_' . bin2hex(random_bytes(12)) . '.' . $extensiones_permitidas[$mime];
    $ruta = $directorio . $nombre_seguro;
    if (!move_uploaded_file($file['tmp_name'], $ruta)) {
        return null;
    }

    $url_relativa = '../images/clientes_cotizar/' . $nombre_seguro;
    $tipo = mysqli_real_escape_string($conexion, $mime);

    $sql = "INSERT INTO clientes_cotizar_fotos (cliente_id, nombre_archivo, url, tipo) VALUES ($cliente_id, '$nombre_seguro', '$url_relativa', '$tipo')";
    if (!mysqli_query($conexion, $sql)) {
        @unlink($ruta);
        return null;
    }

    return ['nombre' => $nombre_seguro, 'url' => $url_relativa, 'tipo' => $tipo];
}

function cargar_registro_cliente($conexion, $id) {
    $id = intval($id);
    $sql = "SELECT * FROM clientes_cotizar WHERE id = $id LIMIT 1";
    $result = mysqli_query($conexion, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }

    $cliente = mysqli_fetch_assoc($result);
    $cliente['notas'] = [];
    $cliente['fotos'] = [];

    $notas = mysqli_query($conexion, "SELECT id, texto, fecha FROM clientes_cotizar_notas WHERE cliente_id = $id ORDER BY fecha DESC");
    while ($nota = mysqli_fetch_assoc($notas)) {
        $cliente['notas'][] = [
            'id' => intval($nota['id']),
            'texto' => $nota['texto'],
            'fecha' => $nota['fecha']
        ];
    }

    $fotos = mysqli_query($conexion, "SELECT id, nombre_archivo, url, tipo, fecha FROM clientes_cotizar_fotos WHERE cliente_id = $id ORDER BY fecha DESC");
    while ($foto = mysqli_fetch_assoc($fotos)) {
        $cliente['fotos'][] = [
            'id' => intval($foto['id']),
            'name' => $foto['nombre_archivo'],
            'url' => $foto['url'],
            'type' => $foto['tipo'],
            'fecha' => $foto['fecha']
        ];
    }

    return $cliente;
}

try {
    asegurar_tablas_clientes_cotizar($conexion);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo inicializar la base de datos del módulo.']);
    exit;
}

$accion = $_POST['action'] ?? $_GET['action'] ?? '';
$id_usuario_actual = intval($_SESSION['usuario_id']);

if ($accion === 'vendedores' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($rol_actual !== 'master') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Solo master puede asignar vendedores.']);
        exit;
    }

    $result = mysqli_query($conexion, "
        SELECT ID, nombre, apellido
        FROM usuario
        WHERE rol IN ('ventas', 'jefe_ventas')
          AND FIND_IN_SET('BLOCKED', REPLACE(COALESCE(permisos, ''), ' ', '')) = 0
        ORDER BY nombre, apellido
    ");
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo cargar la lista de vendedores.']);
        exit;
    }
    $vendedores = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $vendedores[] = [
            'id' => intval($row['ID']),
            'nombre' => trim($row['nombre'] . ' ' . $row['apellido'])
        ];
    }
    echo json_encode(['success' => true, 'items' => $vendedores]);
    exit;
}

if ($accion === 'buscar_directorio' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($rol_actual !== 'master') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Solo master puede consultar el directorio de clientes.']);
        exit;
    }

    $termino = trim((string)($_GET['q'] ?? ''));
    if (strlen($termino) < 2) {
        echo json_encode(['success' => true, 'items' => []]);
        exit;
    }

    $termino_sql = mysqli_real_escape_string($conexion, $termino);
    $sql = "SELECT ID, nombre, cedula_Rif, tlf, comprador
            FROM cliente
            WHERE nombre LIKE '%$termino_sql%'
               OR cedula_Rif LIKE '%$termino_sql%'
               OR tlf LIKE '%$termino_sql%'
               OR comprador LIKE '%$termino_sql%'
            ORDER BY nombre ASC
            LIMIT 20";
    $result = mysqli_query($conexion, $sql);
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo consultar el directorio de clientes.']);
        exit;
    }

    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            'id' => intval($row['ID']),
            'nombre' => $row['nombre'],
            'cedula_rif' => $row['cedula_Rif'],
            'telefono' => $row['tlf'],
            'comprador' => $row['comprador']
        ];
    }

    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}

if ($rol_actual !== 'master' && !(
    ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'listar') ||
    ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($accion, ['guardar_nota', 'subir_fotos'], true))
)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Los vendedores solo pueden consultar sus propias tarjetas y agregarles notas o evidencias.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'listar') {
    $where = $rol_actual === 'master' ? '' : 'WHERE vendedor_id = ' . $id_usuario_actual;
    $sql = "SELECT * FROM clientes_cotizar $where ORDER BY archivado ASC, created_at DESC";
    $result = mysqli_query($conexion, $sql);
    $items = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $row['notas'] = [];
        $row['fotos'] = [];

        $notas = mysqli_query($conexion, "SELECT id, texto, fecha FROM clientes_cotizar_notas WHERE cliente_id = " . intval($row['id']) . " ORDER BY fecha DESC");
        while ($nota = mysqli_fetch_assoc($notas)) {
            $row['notas'][] = [
                'id' => intval($nota['id']),
                'texto' => $nota['texto'],
                'fecha' => $nota['fecha']
            ];
        }

        $fotos = mysqli_query($conexion, "SELECT id, nombre_archivo, url, tipo, fecha FROM clientes_cotizar_fotos WHERE cliente_id = " . intval($row['id']) . " ORDER BY fecha DESC");
        while ($foto = mysqli_fetch_assoc($fotos)) {
            $row['fotos'][] = [
                'id' => intval($foto['id']),
                'name' => $foto['nombre_archivo'],
                'url' => $foto['url'],
                'type' => $foto['tipo'],
                'fecha' => $foto['fecha']
            ];
        }

        $items[] = $row;
    }

    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'guardar') {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : 0;
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $empresa = trim((string)($_POST['empresa'] ?? ''));
    $telefono = trim((string)($_POST['telefono'] ?? ''));
    $producto = trim((string)($_POST['producto'] ?? ''));
    $estado = trim((string)($_POST['estado'] ?? 'pendiente'));
    $fecha_contacto_wa = trim((string)($_POST['fecha_contacto_wa'] ?? ''));
    $vendedor_id = intval($_POST['vendedor_id'] ?? 0);
    $directorio_cliente_id = intval($_POST['directorio_cliente_id'] ?? 0);
    $asignado = '';

    if ($nombre === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
        exit;
    }

    if ($fecha_contacto_wa !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_contacto_wa)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La fecha de contacto por WhatsApp no es válida.']);
        exit;
    }
    if ($fecha_contacto_wa !== '' && !checkdate(
        intval(substr($fecha_contacto_wa, 5, 2)),
        intval(substr($fecha_contacto_wa, 8, 2)),
        intval(substr($fecha_contacto_wa, 0, 4))
    )) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La fecha de contacto por WhatsApp no es válida.']);
        exit;
    }
    $fecha_contacto_wa_sql = $fecha_contacto_wa !== '' ? "'" . mysqli_real_escape_string($conexion, $fecha_contacto_wa) . "'" : 'NULL';
    if ($vendedor_id > 0) {
        $vendedor_id = intval($vendedor_id);
        $result_vendedor = mysqli_query($conexion, "
            SELECT nombre, apellido, permisos
            FROM usuario
            WHERE ID = $vendedor_id
              AND rol IN ('ventas', 'jefe_ventas')
            LIMIT 1
        ");
        if (!$result_vendedor || mysqli_num_rows($result_vendedor) === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El vendedor seleccionado no es válido.']);
            exit;
        }
        $vendedor = mysqli_fetch_assoc($result_vendedor);
        $esta_bloqueado = strpos(',' . str_replace(' ', '', (string)$vendedor['permisos']) . ',', ',BLOCKED,') !== false;
        if ($esta_bloqueado) {
            $puede_conservar_asignacion = false;
            if ($id > 0) {
                $actual = mysqli_query($conexion, "SELECT vendedor_id FROM clientes_cotizar WHERE id = $id LIMIT 1");
                if ($actual && ($registro_actual = mysqli_fetch_assoc($actual))) {
                    $puede_conservar_asignacion = intval($registro_actual['vendedor_id']) === $vendedor_id;
                }
            }
            if (!$puede_conservar_asignacion) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No se puede asignar un usuario bloqueado. Selecciona un vendedor activo.']);
                exit;
            }
        }
        $asignado = trim($vendedor['nombre'] . ' ' . $vendedor['apellido']);
    } else {
        $vendedor_id = 0;
    }
    $vendedor_id_sql = $vendedor_id > 0 ? (string)$vendedor_id : 'NULL';
    if ($directorio_cliente_id > 0) {
        $result_cliente = mysqli_query($conexion, "SELECT ID FROM cliente WHERE ID = $directorio_cliente_id LIMIT 1");
        if (!$result_cliente || mysqli_num_rows($result_cliente) === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El cliente seleccionado del directorio ya no existe.']);
            exit;
        }
    } else {
        $directorio_cliente_id = 0;
    }
    $directorio_cliente_id_sql = $directorio_cliente_id > 0 ? (string)$directorio_cliente_id : 'NULL';
    $estado = in_array($estado, ['pendiente', 'en_revision', 'cotizado', 'cerrado'], true) ? $estado : 'pendiente';

    if ($id > 0) {
        $sql = "UPDATE clientes_cotizar SET
            nombre = '" . mysqli_real_escape_string($conexion, $nombre) . "',
            empresa = '" . mysqli_real_escape_string($conexion, $empresa) . "',
            telefono = '" . mysqli_real_escape_string($conexion, $telefono) . "',
            producto = '" . mysqli_real_escape_string($conexion, $producto) . "',
            estado = '" . mysqli_real_escape_string($conexion, $estado) . "',
            fecha_contacto_wa = $fecha_contacto_wa_sql,
            vendedor_id = $vendedor_id_sql,
            directorio_cliente_id = $directorio_cliente_id_sql,
            asignado = '" . mysqli_real_escape_string($conexion, $asignado) . "'
            WHERE id = $id";

        if (!mysqli_query($conexion, $sql)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => mysqli_error($conexion)]);
            exit;
        }

        $cliente_id = $id;
    } else {
        $sql = "INSERT INTO clientes_cotizar (nombre, empresa, telefono, producto, estado, fecha_contacto_wa, vendedor_id, directorio_cliente_id, asignado)
            VALUES (
                '" . mysqli_real_escape_string($conexion, $nombre) . "',
                '" . mysqli_real_escape_string($conexion, $empresa) . "',
                '" . mysqli_real_escape_string($conexion, $telefono) . "',
                '" . mysqli_real_escape_string($conexion, $producto) . "',
                '" . mysqli_real_escape_string($conexion, $estado) . "',
                $fecha_contacto_wa_sql,
                $vendedor_id_sql,
                $directorio_cliente_id_sql,
                '" . mysqli_real_escape_string($conexion, $asignado) . "'
            )";

        if (!mysqli_query($conexion, $sql)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => mysqli_error($conexion)]);
            exit;
        }

        $cliente_id = mysqli_insert_id($conexion);
    }

    $notas_json = $_POST['notas'] ?? '';
    if ($notas_json !== '') {
        $notas = json_decode((string)$notas_json, true);
        if (!is_array($notas)) {
            $notas = [];
        }
        foreach ($notas as $nota) {
            $texto_nota = trim((string)($nota['texto'] ?? ''));
            if ($texto_nota === '') continue;
            $fecha_nota = isset($nota['fecha']) && $nota['fecha'] !== '' ? $nota['fecha'] : date('Y-m-d H:i:s');
            $sql_nota = "INSERT INTO clientes_cotizar_notas (cliente_id, texto, fecha) VALUES ($cliente_id, '" . mysqli_real_escape_string($conexion, $texto_nota) . "', '$fecha_nota')";
            mysqli_query($conexion, $sql_nota);
        }
    }

    if (!empty($_FILES['fotos']['name'][0])) {
        $total = count($_FILES['fotos']['name']);
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['fotos']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $archivo = [
                'name' => $_FILES['fotos']['name'][$i],
                'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
                'type' => $_FILES['fotos']['type'][$i],
                'error' => $_FILES['fotos']['error'][$i]
            ];
            guardar_archivo_cliente($conexion, $archivo, $cliente_id);
        }
    }

    echo json_encode(['success' => true, 'id' => $cliente_id, 'message' => 'Cliente guardado con éxito.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'guardar_nota') {
    $id = intval($_POST['id'] ?? 0);
    $texto = trim((string)($_POST['texto'] ?? ''));

    if ($id <= 0 || $texto === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Falta el cliente o la nota.']);
        exit;
    }

    if ($rol_actual !== 'master') {
        $propietario = mysqli_query($conexion, "SELECT vendedor_id FROM clientes_cotizar WHERE id = $id LIMIT 1");
        $registro = $propietario ? mysqli_fetch_assoc($propietario) : null;
        if (!$registro || intval($registro['vendedor_id']) !== $id_usuario_actual) {
            http_response_code($registro ? 403 : 404);
            echo json_encode(['success' => false, 'message' => 'Solo puedes agregar notas a tus propios clientes asignados.']);
            exit;
        }
    }

    $sql = "INSERT INTO clientes_cotizar_notas (cliente_id, texto) VALUES ($id, '" . mysqli_real_escape_string($conexion, $texto) . "')";
    if (!mysqli_query($conexion, $sql)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => mysqli_error($conexion)]);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'subir_fotos') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0 || empty($_FILES['fotos']['name'][0])) {
        echo json_encode(['success' => false, 'message' => 'No se enviaron fotos.']);
        exit;
    }

    if ($rol_actual !== 'master') {
        $propietario = mysqli_query($conexion, "SELECT vendedor_id FROM clientes_cotizar WHERE id = $id LIMIT 1");
        $registro = $propietario ? mysqli_fetch_assoc($propietario) : null;
        if (!$registro || intval($registro['vendedor_id']) !== $id_usuario_actual) {
            http_response_code($registro ? 403 : 404);
            echo json_encode(['success' => false, 'message' => 'Solo puedes agregar evidencias a tus propios clientes asignados.']);
            exit;
        }
    }

    $guardadas = 0;
    $total = count($_FILES['fotos']['name']);
    for ($i = 0; $i < $total; $i++) {
        if ($_FILES['fotos']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        $archivo = [
            'name' => $_FILES['fotos']['name'][$i],
            'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
            'type' => $_FILES['fotos']['type'][$i],
            'error' => $_FILES['fotos']['error'][$i]
        ];
        $resultado = guardar_archivo_cliente($conexion, $archivo, $id);
        if ($resultado) {
            $guardadas++;
        }
    }

    if ($guardadas === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar ninguna evidencia.']);
        exit;
    }

    echo json_encode(['success' => true, 'guardadas' => $guardadas]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'eliminar_foto') {
    $id = intval($_POST['id'] ?? 0);
    $foto_id = intval($_POST['foto_id'] ?? 0);
    if ($id <= 0 || $foto_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cliente o evidencia inválida.']);
        exit;
    }

    $resultado_foto = mysqli_query($conexion, "
        SELECT nombre_archivo
        FROM clientes_cotizar_fotos
        WHERE id = $foto_id AND cliente_id = $id
        LIMIT 1
    ");
    if (!$resultado_foto) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo consultar la evidencia.']);
        exit;
    }
    $foto = mysqli_fetch_assoc($resultado_foto);
    if (!$foto) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'La evidencia ya no existe en esta tarjeta.']);
        exit;
    }

    if (!mysqli_query($conexion, "DELETE FROM clientes_cotizar_fotos WHERE id = $foto_id AND cliente_id = $id")) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo quitar la evidencia de la tarjeta.']);
        exit;
    }

    $directorio = realpath(__DIR__ . '/../../images/clientes_cotizar');
    $nombre_archivo = basename((string)$foto['nombre_archivo']);
    $ruta = $directorio ? $directorio . DIRECTORY_SEPARATOR . $nombre_archivo : '';
    $warning = '';
    if ($ruta !== '' && is_file($ruta) && !unlink($ruta)) {
        error_log('No se pudo eliminar el archivo de evidencia de clientes a cotizar: ' . $nombre_archivo);
        $warning = 'Se quitó de la tarjeta, pero no se pudo borrar el archivo físico.';
    }

    echo json_encode(['success' => true, 'warning' => $warning]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'archivar') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Cliente inválido.']);
        exit;
    }

    $current = cargar_registro_cliente($conexion, $id);
    if (!$current) {
        echo json_encode(['success' => false, 'message' => 'No existe el cliente.']);
        exit;
    }

    $nuevo = $current['archivado'] ? 0 : 1;
    $estado = $nuevo ? 'archivado' : 'pendiente';
    $sql = "UPDATE clientes_cotizar SET archivado = $nuevo, estado = '$estado' WHERE id = $id";
    if (mysqli_query($conexion, $sql)) {
        echo json_encode(['success' => true, 'archivado' => $nuevo]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => mysqli_error($conexion)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'eliminar') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Cliente inválido.']);
        exit;
    }

    $fotos = mysqli_query($conexion, "SELECT url, nombre_archivo FROM clientes_cotizar_fotos WHERE cliente_id = $id");
    while ($foto = mysqli_fetch_assoc($fotos)) {
        $ruta = __DIR__ . '/../../' . ltrim($foto['url'], '/');
        if (file_exists($ruta)) {
            @unlink($ruta);
        }
    }

    mysqli_query($conexion, "DELETE FROM clientes_cotizar_notas WHERE cliente_id = $id");
    mysqli_query($conexion, "DELETE FROM clientes_cotizar_fotos WHERE cliente_id = $id");
    $sql = "DELETE FROM clientes_cotizar WHERE id = $id";
    if (mysqli_query($conexion, $sql)) {
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => mysqli_error($conexion)]);
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Acción no soportada.']);
