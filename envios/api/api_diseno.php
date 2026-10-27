<?php
session_start();
require_once '../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$esDescarga = $accion === 'descargar_zip';
$esImagenAvance = $accion === 'ver_imagen_avance';
if (!$esDescarga && !$esImagenAvance) {
    header('Content-Type: application/json; charset=utf-8');
}

function responder_diseno($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    if ($esDescarga || $esImagenAvance) {
        http_response_code(401);
        exit('Sesión expirada.');
    }
    responder_diseno(['success' => false, 'msg' => 'Sesión expirada.'], 401);
}

$idUsuario = intval($_SESSION['usuario_id']);
$qUsuario = mysqli_query($conexion, "SELECT rol, permisos FROM usuario WHERE ID = $idUsuario");
$usuario = $qUsuario ? mysqli_fetch_assoc($qUsuario) : null;
$rolUsuario = strtolower(trim($usuario['rol'] ?? ''));
$permisos = array_map('trim', explode(',', $usuario['permisos'] ?? ''));
$esJefeDiseno = $rolUsuario === 'jefe_diseno' || $rolUsuario === 'master' || in_array('55', $permisos, true);
$esDisenador = in_array($rolUsuario, ['disenador', 'cortador', 'corte', 'bordador', 'bordado'], true) || in_array('56', $permisos, true);

if (!$esJefeDiseno && !$esDisenador) {
    if ($esDescarga || $esImagenAvance) {
        http_response_code(403);
        exit('No tienes permisos para acceder al módulo de Diseño, Corte y Bordado.');
    }
    responder_diseno(['success' => false, 'msg' => 'No tienes permisos para acceder al módulo de Diseño, Corte y Bordado.'], 403);
}

$crearTabla = "
    CREATE TABLE IF NOT EXISTS asignaciones_diseno (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_orden INT NOT NULL,
        id_disenador INT NOT NULL,
        area VARCHAR(30) NOT NULL DEFAULT 'Diseño',
        tareas_asignadas TEXT DEFAULT NULL,
        prioridad INT NOT NULL DEFAULT 0,
        estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
        fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fecha_inicio DATETIME DEFAULT NULL,
        fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        fecha_finalizado DATETIME DEFAULT NULL,
        KEY idx_disenador_prioridad (id_disenador, prioridad),
        KEY idx_orden_area (id_orden, area)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!mysqli_query($conexion, $crearTabla)) {
    if ($esDescarga) {
        http_response_code(500);
        exit('No se pudo preparar el módulo de diseño.');
    }
    responder_diseno(['success' => false, 'msg' => 'No se pudo preparar el módulo de diseño.'], 500);
}

// Migrar columnas y permitir que una misma orden se asigne a Diseño, Corte y Bordado
mysqli_query($conexion, "ALTER TABLE asignaciones_diseno ADD COLUMN IF NOT EXISTS fecha_inicio DATETIME DEFAULT NULL AFTER fecha_asignacion");
mysqli_query($conexion, "ALTER TABLE asignaciones_diseno ADD COLUMN IF NOT EXISTS area VARCHAR(30) NOT NULL DEFAULT 'Diseño' AFTER id_disenador");
mysqli_query($conexion, "ALTER TABLE asignaciones_diseno ADD COLUMN IF NOT EXISTS tareas_asignadas TEXT DEFAULT NULL AFTER area");

// Eliminar restricción antigua uq_orden_disenador si impide asignar el mismo usuario a 2 áreas de la misma orden, y crear índice único por (id_orden, area)
$qIdxOld = mysqli_query($conexion, "SHOW INDEX FROM asignaciones_diseno WHERE Key_name = 'uq_orden_disenador'");
if ($qIdxOld && mysqli_num_rows($qIdxOld) > 0) {
    mysqli_query($conexion, "ALTER TABLE asignaciones_diseno DROP INDEX uq_orden_disenador");
}
$qIdxArea = mysqli_query($conexion, "SHOW INDEX FROM asignaciones_diseno WHERE Key_name = 'uq_orden_area'");
if ($qIdxArea && mysqli_num_rows($qIdxArea) === 0) {
    mysqli_query($conexion, "ALTER TABLE asignaciones_diseno ADD UNIQUE KEY uq_orden_area (id_orden, area)");
}

if (!mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS avances_diseno (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_asignacion INT NOT NULL,
        id_disenador INT NOT NULL,
        fase VARCHAR(120) NOT NULL,
        comentario TEXT DEFAULT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_avance_asignacion_fecha (id_asignacion, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
")) {
    responder_diseno(['success' => false, 'msg' => 'No se pudo preparar el historial de avances.'], 500);
}
mysqli_query($conexion, "ALTER TABLE avances_diseno MODIFY COLUMN fase VARCHAR(120) NOT NULL");

if (!mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS avances_diseno_imagenes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_avance INT NOT NULL,
        archivo VARCHAR(255) NOT NULL,
        mime VARCHAR(50) NOT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_imagen_avance (id_avance)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
")) {
    responder_diseno(['success' => false, 'msg' => 'No se pudo preparar el almacenamiento de imágenes.'], 500);
}

function normalizar_area_diseno(string $area): string {
    $limpia = mb_strtolower(trim($area), 'UTF-8');
    if (strpos($limpia, 'cort') !== false) return 'Corte';
    if (strpos($limpia, 'bord') !== false) return 'Bordado';
    return 'Diseño';
}

function inferir_area_usuario(string $rol): string {
    $r = mb_strtolower(trim($rol), 'UTF-8');
    if (strpos($r, 'cort') !== false) return 'Corte';
    if (strpos($r, 'bord') !== false) return 'Bordado';
    return 'Diseño';
}

function orden_asignada_a($conexion, $idOrden, $idDisenador) {
    $idOrden = intval($idOrden);
    $idDisenador = intval($idDisenador);
    $q = mysqli_query($conexion, "SELECT id FROM asignaciones_diseno WHERE id_orden = $idOrden AND id_disenador = $idDisenador LIMIT 1");
    return $q && mysqli_num_rows($q) > 0;
}

function adjuntar_avances_diseno($conexion, array &$asignaciones): void {
    if (!$asignaciones) {
        return;
    }
    $ids = array_values(array_unique(array_map(
        static function ($asignacion) { return intval($asignacion['id_asignacion']); },
        $asignaciones
    )));
    $ids = array_filter($ids, static function ($id) { return $id > 0; });
    if (!$ids) {
        return;
    }
    $idsSql = implode(',', $ids);
    $q = mysqli_query($conexion, "
        SELECT a.id AS id_avance, a.id_asignacion, a.fase, a.comentario, a.fecha,
               u.nombre AS nombre_disenador, u.apellido AS apellido_disenador,
               i.id AS id_imagen, i.archivo
        FROM avances_diseno a
        JOIN usuario u ON u.ID = a.id_disenador
        LEFT JOIN avances_diseno_imagenes i ON i.id_avance = a.id
        WHERE a.id_asignacion IN ($idsSql)
        ORDER BY a.fecha DESC, a.id DESC, i.id ASC
    ");
    if (!$q) {
        throw new RuntimeException('No se pudo cargar el historial de avances.');
    }

    $porAsignacion = [];
    while ($fila = mysqli_fetch_assoc($q)) {
        $idAsignacion = intval($fila['id_asignacion']);
        $idAvance = intval($fila['id_avance']);
        if (!isset($porAsignacion[$idAsignacion][$idAvance])) {
            $porAsignacion[$idAsignacion][$idAvance] = [
                'id' => $idAvance,
                'fase' => $fila['fase'],
                'comentario' => $fila['comentario'],
                'fecha' => $fila['fecha'],
                'disenador' => trim(($fila['nombre_disenador'] ?? '') . ' ' . ($fila['apellido_disenador'] ?? '')),
                'imagenes' => []
            ];
        }
        if (!empty($fila['id_imagen'])) {
            $porAsignacion[$idAsignacion][$idAvance]['imagenes'][] = [
                'id' => intval($fila['id_imagen']),
                'archivo' => $fila['archivo']
            ];
        }
    }

    foreach ($asignaciones as &$asignacion) {
        $lista = array_values($porAsignacion[intval($asignacion['id_asignacion'])] ?? []);
        $asignacion['avances'] = $lista;
        $asignacion['fase_actual'] = $lista[0]['fase'] ?? '';
    }
    unset($asignacion);
}

if ($accion === 'ver_imagen_avance') {
    $idImagen = intval($_GET['id'] ?? 0);
    $qImagen = mysqli_query($conexion, "
        SELECT i.archivo, i.mime, a.id_asignacion, ad.id_disenador
        FROM avances_diseno_imagenes i
        JOIN avances_diseno a ON a.id = i.id_avance
        JOIN asignaciones_diseno ad ON ad.id = a.id_asignacion
        WHERE i.id = $idImagen
        LIMIT 1
    ");
    $imagen = $qImagen ? mysqli_fetch_assoc($qImagen) : null;
    if (!$imagen || (!$esJefeDiseno && intval($imagen['id_disenador']) !== $idUsuario)) {
        http_response_code(404);
        exit('No se encontró la imagen o no tienes acceso.');
    }
    $ruta = __DIR__ . '/../../images/avances_diseno/' . basename($imagen['archivo']);
    if (!is_file($ruta)) {
        http_response_code(404);
        exit('La imagen de avance ya no está disponible.');
    }
    header('Content-Type: ' . $imagen['mime']);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=3600');
    header('Content-Length: ' . filesize($ruta));
    readfile($ruta);
    exit;
}

if ($accion === 'editar_avance') {
    if (!$esDisenador && !$esJefeDiseno) {
        responder_diseno(['success' => false, 'msg' => 'No tienes permiso para editar avances.'], 403);
    }
    $idAvance = intval($_POST['id_avance'] ?? 0);
    $fase = trim((string)($_POST['fase'] ?? ''));
    $comentario = trim((string)($_POST['comentario'] ?? ''));
    if ($idAvance < 1 || $fase === '' || mb_strlen($fase, 'UTF-8') > 120 || mb_strlen($comentario, 'UTF-8') > 2000) {
        responder_diseno(['success' => false, 'msg' => 'Escribe el nombre de la fase (máx. 120 caracteres) y un detalle válido.'], 400);
    }
    $condUsuario = $esJefeDiseno ? "" : "AND ad.id_disenador = $idUsuario";
    $qAvance = mysqli_query($conexion, "
        SELECT a.id
        FROM avances_diseno a
        JOIN asignaciones_diseno ad ON ad.id = a.id_asignacion
        WHERE a.id = $idAvance $condUsuario AND ad.estado <> 'Finalizado'
        LIMIT 1
    ");
    if (!$qAvance || mysqli_num_rows($qAvance) === 0) {
        responder_diseno(['success' => false, 'msg' => 'No se encontró un avance editable de tu asignación activa.'], 404);
    }
    $faseSql = mysqli_real_escape_string($conexion, $fase);
    $comentarioSql = mysqli_real_escape_string($conexion, $comentario);
    if (!mysqli_query($conexion, "
        UPDATE avances_diseno
        SET fase = '$faseSql', comentario = '$comentarioSql'
        WHERE id = $idAvance
    ")) {
        responder_diseno(['success' => false, 'msg' => 'No se pudieron actualizar la fase y el comentario.'], 500);
    }
    responder_diseno(['success' => true, 'msg' => 'Fase y detalle actualizados.']);
}

if ($accion === 'agregar_imagenes_avance') {
    if (!$esDisenador && !$esJefeDiseno) {
        responder_diseno(['success' => false, 'msg' => 'No tienes permiso para agregar imágenes.'], 403);
    }
    $idAvance = intval($_POST['id_avance'] ?? 0);
    $condUsuario = $esJefeDiseno ? "" : "AND ad.id_disenador = $idUsuario";
    $qAvance = mysqli_query($conexion, "
        SELECT a.id, ad.estado
        FROM avances_diseno a
        JOIN asignaciones_diseno ad ON ad.id = a.id_asignacion
        WHERE a.id = $idAvance $condUsuario
        LIMIT 1
    ");
    $avance = $qAvance ? mysqli_fetch_assoc($qAvance) : null;
    if (!$avance) {
        responder_diseno(['success' => false, 'msg' => 'No se encontró un avance de tu asignación.'], 404);
    }
    if ($avance['estado'] === 'Finalizado') {
        responder_diseno(['success' => false, 'msg' => 'Reabre la orden antes de agregar imágenes.'], 409);
    }
    if (empty($_FILES['imagenes']) || !is_array($_FILES['imagenes']['name'])) {
        responder_diseno(['success' => false, 'msg' => 'Selecciona al menos una imagen para agregar.'], 400);
    }
    $totalNuevas = count($_FILES['imagenes']['name']);
    if ($idAvance < 1 || $totalNuevas < 1 || $totalNuevas > 5) {
        responder_diseno(['success' => false, 'msg' => 'Puedes agregar entre 1 y 5 imágenes por operación.'], 400);
    }
    $qTotal = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM avances_diseno_imagenes WHERE id_avance = $idAvance");
    $totalActual = $qTotal ? mysqli_fetch_assoc($qTotal) : null;
    if (!$totalActual || intval($totalActual['total']) + $totalNuevas > 20) {
        responder_diseno(['success' => false, 'msg' => 'Cada fase permite un máximo total de 20 imágenes.'], 400);
    }

    $mimesPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $archivos = [];
    for ($i = 0; $i < $totalNuevas; $i++) {
        $error = intval($_FILES['imagenes']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        $tmp = $_FILES['imagenes']['tmp_name'][$i] ?? '';
        $size = intval($_FILES['imagenes']['size'][$i] ?? 0);
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp) || $size < 1 || $size > 8 * 1024 * 1024) {
            responder_diseno(['success' => false, 'msg' => 'Cada imagen debe pesar como máximo 8 MB y cargarse correctamente.'], 400);
        }
        $mime = $finfo->file($tmp);
        if (!isset($mimesPermitidos[$mime]) || @getimagesize($tmp) === false) {
            responder_diseno(['success' => false, 'msg' => 'Solo se permiten imágenes JPG, PNG o WebP válidas.'], 400);
        }
        $archivos[] = [
            'tmp' => $tmp,
            'mime' => $mime,
            'nombre' => 'avance_diseno_' . bin2hex(random_bytes(16)) . '.' . $mimesPermitidos[$mime]
        ];
    }

    $directorio = __DIR__ . '/../../images/avances_diseno';
    if (!is_dir($directorio) && !mkdir($directorio, 0750, true) && !is_dir($directorio)) {
        responder_diseno(['success' => false, 'msg' => 'No se pudo abrir la carpeta de imágenes de avances.'], 500);
    }
    if (!mysqli_begin_transaction($conexion)) {
        responder_diseno(['success' => false, 'msg' => 'No se pudo iniciar el agregado de imágenes.'], 500);
    }
    $guardados = [];
    $errorGuardado = false;
    foreach ($archivos as $archivo) {
        $ruta = $directorio . DIRECTORY_SEPARATOR . $archivo['nombre'];
        if (!move_uploaded_file($archivo['tmp'], $ruta)) {
            $errorGuardado = true;
            break;
        }
        $guardados[] = $ruta;
        $nombreSql = mysqli_real_escape_string($conexion, $archivo['nombre']);
        $mimeSql = mysqli_real_escape_string($conexion, $archivo['mime']);
        if (!mysqli_query($conexion, "
            INSERT INTO avances_diseno_imagenes (id_avance, archivo, mime)
            VALUES ($idAvance, '$nombreSql', '$mimeSql')
        ")) {
            $errorGuardado = true;
            break;
        }
    }
    if ($errorGuardado || !mysqli_commit($conexion)) {
        mysqli_rollback($conexion);
        foreach ($guardados as $ruta) {
            if (is_file($ruta)) {
                unlink($ruta);
            }
        }
        responder_diseno(['success' => false, 'msg' => 'No se pudieron agregar las imágenes al avance.'], 500);
    }
    responder_diseno(['success' => true, 'msg' => 'Imágenes agregadas a la fase.']);
}

if ($accion === 'eliminar_avance') {
    if (!$esDisenador && !$esJefeDiseno) {
        responder_diseno(['success' => false, 'msg' => 'No tienes permiso para eliminar fases.'], 403);
    }
    $idAvance = intval($_POST['id_avance'] ?? 0);
    if ($idAvance < 1) {
        responder_diseno(['success' => false, 'msg' => 'La fase seleccionada no es válida.'], 400);
    }
    if (!mysqli_begin_transaction($conexion)) {
        responder_diseno(['success' => false, 'msg' => 'No se pudo iniciar la eliminación de la fase.'], 500);
    }
    $condUsuario = $esJefeDiseno ? "" : "AND ad.id_disenador = $idUsuario";
    $qAvance = mysqli_query($conexion, "
        SELECT a.id
        FROM avances_diseno a
        JOIN asignaciones_diseno ad ON ad.id = a.id_asignacion
        WHERE a.id = $idAvance
          $condUsuario
          AND ad.estado <> 'Finalizado'
        LIMIT 1
        FOR UPDATE
    ");
    if (!$qAvance || mysqli_num_rows($qAvance) !== 1) {
        mysqli_rollback($conexion);
        responder_diseno(['success' => false, 'msg' => 'No se encontró una fase editable de tu asignación activa.'], 404);
    }
    $qImagenes = mysqli_query($conexion, "
        SELECT id, archivo
        FROM avances_diseno_imagenes
        WHERE id_avance = $idAvance
        FOR UPDATE
    ");

    $directorio = __DIR__ . '/../../images/avances_diseno';
    $archivosTemporales = [];
    if ($qImagenes) {
        while ($imagen = mysqli_fetch_assoc($qImagenes)) {
            $nombreArchivo = basename($imagen['archivo']);
            $rutaOriginal = $directorio . DIRECTORY_SEPARATOR . $nombreArchivo;
            if (!is_file($rutaOriginal)) continue;
            $rutaTemporal = $directorio . DIRECTORY_SEPARATOR . 'eliminando_' . bin2hex(random_bytes(12)) . '.tmp';
            if (@rename($rutaOriginal, $rutaTemporal)) {
                $archivosTemporales[] = ['original' => $rutaOriginal, 'temporal' => $rutaTemporal];
            }
        }
    }

    $eliminadasImagenes = mysqli_query($conexion, "DELETE FROM avances_diseno_imagenes WHERE id_avance = $idAvance");
    $eliminadoAvance = $eliminadasImagenes && mysqli_query($conexion, "DELETE FROM avances_diseno WHERE id = $idAvance");
    if (!$eliminadoAvance || !mysqli_commit($conexion)) {
        mysqli_rollback($conexion);
        foreach ($archivosTemporales as $archivo) {
            @rename($archivo['temporal'], $archivo['original']);
        }
        responder_diseno(['success' => false, 'msg' => 'No se pudo eliminar la fase.'], 500);
    }
    foreach ($archivosTemporales as $archivo) {
        if (is_file($archivo['temporal'])) {
            @unlink($archivo['temporal']);
        }
    }
    responder_diseno(['success' => true, 'msg' => 'Fase eliminada correctamente.']);
}

if ($accion === 'eliminar_imagen_avance') {
    if (!$esDisenador && !$esJefeDiseno) {
        responder_diseno(['success' => false, 'msg' => 'No tienes permiso para eliminar imágenes.'], 403);
    }
    $idImagen = intval($_POST['id_imagen'] ?? 0);
    $condUsuario = $esJefeDiseno ? "" : "AND ad.id_disenador = $idUsuario";
    $qImagen = mysqli_query($conexion, "
        SELECT i.id, i.archivo
        FROM avances_diseno_imagenes i
        JOIN avances_diseno a ON a.id = i.id_avance
        JOIN asignaciones_diseno ad ON ad.id = a.id_asignacion
        WHERE i.id = $idImagen $condUsuario AND ad.estado <> 'Finalizado'
        LIMIT 1
    ");
    $imagen = $qImagen ? mysqli_fetch_assoc($qImagen) : null;
    if (!$imagen) {
        responder_diseno(['success' => false, 'msg' => 'No se encontró una imagen que puedas eliminar.'], 404);
    }
    $directorio = __DIR__ . '/../../images/avances_diseno';
    $rutaOriginal = $directorio . DIRECTORY_SEPARATOR . basename($imagen['archivo']);
    if (is_file($rutaOriginal)) {
        @unlink($rutaOriginal);
    }
    mysqli_query($conexion, "DELETE FROM avances_diseno_imagenes WHERE id = $idImagen");
    responder_diseno(['success' => true, 'msg' => 'Imagen eliminada del avance.']);
}

// GUARDAR AVANCE CON FASE ESCRITA LIBREMENTE (IMÁGENES OPCIONALES)
if ($accion === 'guardar_avance') {
    if (!$esDisenador && !$esJefeDiseno) {
        responder_diseno(['success' => false, 'msg' => 'No tienes permiso para registrar avances.'], 403);
    }
    $idAsignacion = intval($_POST['id_asignacion'] ?? 0);
    $fase = trim((string)($_POST['fase'] ?? ''));
    $comentario = trim((string)($_POST['comentario'] ?? ''));

    if ($idAsignacion < 1 || $fase === '' || mb_strlen($fase, 'UTF-8') > 120 || mb_strlen($comentario, 'UTF-8') > 2000) {
        responder_diseno(['success' => false, 'msg' => 'Escribe el nombre de la fase (máximo 120 caracteres) y un detalle válido.'], 400);
    }

    $condUsuario = $esJefeDiseno ? "" : "AND id_disenador = $idUsuario";
    $qAsignacion = mysqli_query($conexion, "
        SELECT id, estado FROM asignaciones_diseno
        WHERE id = $idAsignacion $condUsuario
        LIMIT 1
    ");
    $asignacion = $qAsignacion ? mysqli_fetch_assoc($qAsignacion) : null;
    if (!$asignacion) {
        responder_diseno(['success' => false, 'msg' => 'La orden no está asignada a tu usuario.'], 403);
    }
    if ($asignacion['estado'] === 'Finalizado') {
        responder_diseno(['success' => false, 'msg' => 'Reabre el proceso antes de registrar otra fase.'], 409);
    }

    // Procesar imágenes si se adjuntaron (opcionales para permitir fases por escrito en Corte/Bordado/Diseño)
    $archivos = [];
    if (!empty($_FILES['imagenes']) && is_array($_FILES['imagenes']['name'])) {
        $totalArchivos = count($_FILES['imagenes']['name']);
        $mimesPermitidos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        for ($i = 0; $i < $totalArchivos; $i++) {
            $error = intval($_FILES['imagenes']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $tmp = $_FILES['imagenes']['tmp_name'][$i] ?? '';
            $size = intval($_FILES['imagenes']['size'][$i] ?? 0);
            if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp) || $size < 1 || $size > 8 * 1024 * 1024) {
                responder_diseno(['success' => false, 'msg' => 'Cada imagen adjunta debe pesar máximo 8 MB.'], 400);
            }
            $mime = $finfo->file($tmp);
            if (!isset($mimesPermitidos[$mime]) || @getimagesize($tmp) === false) {
                responder_diseno(['success' => false, 'msg' => 'Solo se permiten imágenes JPG, PNG o WebP válidas.'], 400);
            }
            $archivos[] = [
                'tmp' => $tmp,
                'mime' => $mime,
                'extension' => $mimesPermitidos[$mime],
                'nombre' => 'avance_diseno_' . bin2hex(random_bytes(16)) . '.' . $mimesPermitidos[$mime]
            ];
        }
        if (count($archivos) > 5) {
            responder_diseno(['success' => false, 'msg' => 'Puedes adjuntar hasta 5 imágenes por fase.'], 400);
        }
    }

    $directorio = __DIR__ . '/../../images/avances_diseno';
    if (!empty($archivos) && !is_dir($directorio) && !mkdir($directorio, 0750, true) && !is_dir($directorio)) {
        responder_diseno(['success' => false, 'msg' => 'No se pudo crear la carpeta de imágenes de avances.'], 500);
    }

    $comentarioSql = mysqli_real_escape_string($conexion, $comentario);
    $faseSql = mysqli_real_escape_string($conexion, $fase);
    if (!mysqli_begin_transaction($conexion)) {
        responder_diseno(['success' => false, 'msg' => 'No se pudo iniciar el registro de la fase.'], 500);
    }

    $guardados = [];
    $qAvance = mysqli_query($conexion, "
        INSERT INTO avances_diseno (id_asignacion, id_disenador, fase, comentario)
        VALUES ($idAsignacion, $idUsuario, '$faseSql', '$comentarioSql')
    ");
    $idAvance = $qAvance ? mysqli_insert_id($conexion) : 0;
    $errorGuardado = !$qAvance;

    foreach ($archivos as $archivo) {
        if ($errorGuardado) break;
        $ruta = $directorio . DIRECTORY_SEPARATOR . $archivo['nombre'];
        if (!move_uploaded_file($archivo['tmp'], $ruta)) {
            $errorGuardado = true;
            break;
        }
        $guardados[] = $ruta;
        $nombreSql = mysqli_real_escape_string($conexion, $archivo['nombre']);
        $mimeSql = mysqli_real_escape_string($conexion, $archivo['mime']);
        if (!mysqli_query($conexion, "
            INSERT INTO avances_diseno_imagenes (id_avance, archivo, mime)
            VALUES ($idAvance, '$nombreSql', '$mimeSql')
        ")) {
            $errorGuardado = true;
            break;
        }
    }

    if (!$errorGuardado && !mysqli_query($conexion, "
        UPDATE asignaciones_diseno
        SET estado = 'En Proceso', fecha_inicio = COALESCE(fecha_inicio, NOW()), fecha_actualizacion = NOW()
        WHERE id = $idAsignacion AND estado = 'Pendiente'
    ")) {
        $errorGuardado = true;
    }

    if ($errorGuardado || !mysqli_commit($conexion)) {
        mysqli_rollback($conexion);
        foreach ($guardados as $ruta) {
            if (is_file($ruta)) unlink($ruta);
        }
        responder_diseno(['success' => false, 'msg' => 'No se pudo guardar la fase escrita.'], 500);
    }
    responder_diseno(['success' => true, 'msg' => 'Fase registrada correctamente.']);
}

if ($accion === 'listar') {
    if ($esJefeDiseno) {
        $q = mysqli_query($conexion, "
            SELECT a.id AS id_asignacion, a.id_orden, a.id_disenador, a.area, a.tareas_asignadas,
                   a.prioridad, a.estado,
                   a.fecha_asignacion, a.fecha_inicio, a.fecha_actualizacion, a.fecha_finalizado,
                   CASE WHEN a.fecha_inicio IS NOT NULL
                        THEN TIMESTAMPDIFF(SECOND, a.fecha_inicio, COALESCE(a.fecha_finalizado, NOW()))
                        ELSE NULL END AS duracion_segundos,
                   o.nro_orden_fabrica, o.fecha_entrega, o.foto, o.descripcion AS descripcion_orden,
                   c.nombre AS cliente, u.nombre AS nombre_disenador, u.apellido AS apellido_disenador, u.rol AS rol_disenador,
                   IFNULL(t.estado_general, 'Pendiente') AS estado_envio,
                   IFNULL(fases.total_fases, 0) AS total_fases,
                   IFNULL(fases.fases_completadas, 0) AS fases_completadas,
                   fases.fases_finalizadas
            FROM asignaciones_diseno a
            JOIN orden_fabrica o ON o.ID = a.id_orden
            LEFT JOIN cliente c ON c.ID = o.id_cliente
            JOIN usuario u ON u.ID = a.id_disenador
            LEFT JOIN tracking_orden t ON t.id_orden_fabrica = o.ID
            LEFT JOIN (
                SELECT tf.id_tracking, COUNT(*) AS total_fases,
                       SUM(CASE WHEN tf.estado = 2 THEN 1 ELSE 0 END) AS fases_completadas,
                       GROUP_CONCAT(CASE WHEN tf.estado = 2 THEN tf.fase_nombre END ORDER BY tf.orden SEPARATOR ', ') AS fases_finalizadas
                FROM tracking_fases tf
                GROUP BY tf.id_tracking
            ) fases ON fases.id_tracking = t.id
            ORDER BY a.prioridad ASC, o.fecha_entrega ASC, o.ID DESC, a.area ASC");
        if (!$q) {
            responder_diseno(['success' => false, 'msg' => 'No se pudieron cargar las órdenes asignadas.'], 500);
        }

        $asignaciones = [];
        while ($row = mysqli_fetch_assoc($q)) {
            $row['tiene_fotos'] = !empty($row['foto']);
            $row['area'] = $row['area'] ?: 'Diseño';
            unset($row['foto']);
            $asignaciones[] = $row;
        }
        try {
            adjuntar_avances_diseno($conexion, $asignaciones);
        } catch (RuntimeException $e) {
            responder_diseno(['success' => false, 'msg' => $e->getMessage()], 500);
        }

        // Cargar todas las órdenes de fábrica para que puedan ser emitidas a Diseño, Corte y/o Bordado
        $ordenes = [];
        $qOrdenes = mysqli_query($conexion, "
            SELECT o.ID, o.nro_orden_fabrica, o.fecha_entrega, o.foto, c.nombre AS cliente
            FROM orden_fabrica o
            LEFT JOIN cliente c ON c.ID = o.id_cliente
            LEFT JOIN tracking_orden t ON t.id_orden_fabrica = o.ID
            WHERE IFNULL(t.estado_general, 'Pendiente') NOT IN ('Entregado')
            ORDER BY o.ID DESC
        ");
        if (!$qOrdenes) {
            responder_diseno(['success' => false, 'msg' => 'No se pudieron cargar las órdenes disponibles.'], 500);
        }
        while ($row = mysqli_fetch_assoc($qOrdenes)) {
            $row['tiene_fotos'] = !empty($row['foto']);
            unset($row['foto']);
            $ordenes[] = $row;
        }

        // Incluir diseñadores, cortadores y bordadores
        $disenadores = [];
        $qDisenadores = mysqli_query($conexion, "
            SELECT ID, nombre, apellido, rol
            FROM usuario
            WHERE LOWER(TRIM(rol)) IN ('disenador', 'cortador', 'corte', 'bordador', 'bordado', 'jefe_diseno')
               OR FIND_IN_SET('56', REPLACE(permisos, ' ', '')) > 0
               OR FIND_IN_SET('55', REPLACE(permisos, ' ', '')) > 0
            ORDER BY nombre, apellido
        ");
        if (!$qDisenadores) {
            responder_diseno(['success' => false, 'msg' => 'No se pudo cargar la lista de personal de Diseño, Corte y Bordado.'], 500);
        }
        while ($row = mysqli_fetch_assoc($qDisenadores)) {
            $row['area_sugerida'] = inferir_area_usuario($row['rol'] ?? '');
            $disenadores[] = $row;
        }

        responder_diseno([
            'success' => true,
            'es_jefe' => true,
            'asignaciones' => $asignaciones,
            'ordenes' => $ordenes,
            'disenadores' => $disenadores
        ]);
    }

    // Vista de Operador (Diseñador, Cortador o Bordador)
    $q = mysqli_query($conexion, "
        SELECT a.id AS id_asignacion, a.id_orden, a.area, a.tareas_asignadas, a.prioridad, a.estado,
               a.fecha_asignacion, a.fecha_inicio, a.fecha_actualizacion, a.fecha_finalizado,
               CASE WHEN a.fecha_inicio IS NOT NULL
                    THEN TIMESTAMPDIFF(SECOND, a.fecha_inicio, COALESCE(a.fecha_finalizado, NOW()))
                    ELSE NULL END AS duracion_segundos,
               o.nro_orden_fabrica, o.fecha_entrega, o.foto, o.descripcion AS descripcion_orden,
               c.nombre AS cliente, IFNULL(t.estado_general, 'Pendiente') AS estado_envio
        FROM asignaciones_diseno a
        JOIN orden_fabrica o ON o.ID = a.id_orden
        LEFT JOIN cliente c ON c.ID = o.id_cliente
        LEFT JOIN tracking_orden t ON t.id_orden_fabrica = o.ID
        WHERE a.id_disenador = $idUsuario
        ORDER BY a.prioridad ASC, o.fecha_entrega ASC, o.ID DESC");
    if (!$q) {
        responder_diseno(['success' => false, 'msg' => 'No se pudieron cargar tus órdenes asignadas.'], 500);
    }

    $asignaciones = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $row['tiene_fotos'] = !empty($row['foto']);
        $row['area'] = $row['area'] ?: 'Diseño';
        unset($row['foto']);
        $asignaciones[] = $row;
    }
    try {
        adjuntar_avances_diseno($conexion, $asignaciones);
    } catch (RuntimeException $e) {
        responder_diseno(['success' => false, 'msg' => $e->getMessage()], 500);
    }
    responder_diseno(['success' => true, 'es_jefe' => false, 'asignaciones' => $asignaciones]);
}

if ($accion === 'asignar') {
    if (!$esJefeDiseno) {
        responder_diseno(['success' => false, 'msg' => 'Solo el Jefe de Diseño puede asignar órdenes y tareas.'], 403);
    }
    $idOrden = intval($_POST['id_orden'] ?? 0);
    $areaPost = trim((string)($_POST['area'] ?? 'Diseño'));
    $prioridad = max(0, intval($_POST['prioridad'] ?? 1));
    $tareasAsignadas = trim((string)($_POST['tareas_asignadas'] ?? ''));
    $tareasSql = mysqli_real_escape_string($conexion, $tareasAsignadas);

    if ($idOrden < 1) {
        responder_diseno(['success' => false, 'msg' => 'Selecciona una Orden de Fábrica válida.'], 400);
    }

    $qOrden = mysqli_query($conexion, "SELECT ID FROM orden_fabrica WHERE ID = $idOrden LIMIT 1");
    if (!$qOrden || mysqli_num_rows($qOrden) === 0) {
        responder_diseno(['success' => false, 'msg' => 'La Orden de Fábrica seleccionada no existe.'], 404);
    }

    // Permitir emitir a las 3 áreas de una sola vez (TODAS) o a un área individual
    $asignacionesSolicitadas = [];
    if (strtoupper($areaPost) === 'TODAS') {
        $idDis = intval($_POST['id_disenador_diseno'] ?? 0);
        $idCor = intval($_POST['id_disenador_corte'] ?? 0);
        $idBor = intval($_POST['id_disenador_bordado'] ?? 0);
        if ($idDis > 0) $asignacionesSolicitadas['Diseño'] = $idDis;
        if ($idCor > 0) $asignacionesSolicitadas['Corte'] = $idCor;
        if ($idBor > 0) $asignacionesSolicitadas['Bordado'] = $idBor;
        if (empty($asignacionesSolicitadas)) {
            responder_diseno(['success' => false, 'msg' => 'Selecciona al menos un encargado para Diseño, Corte o Bordado.'], 400);
        }
    } else {
        $areaNorm = normalizar_area_diseno($areaPost);
        $idDisenador = intval($_POST['id_disenador'] ?? 0);
        if ($idDisenador < 1) {
            responder_diseno(['success' => false, 'msg' => "Selecciona el encargado para el área de $areaNorm."], 400);
        }
        $asignacionesSolicitadas[$areaNorm] = $idDisenador;
    }

    $creadas = 0;
    $actualizadas = 0;
    foreach ($asignacionesSolicitadas as $areaItem => $idEncargado) {
        $areaSql = mysqli_real_escape_string($conexion, $areaItem);
        $qExiste = mysqli_query($conexion, "SELECT id FROM asignaciones_diseno WHERE id_orden = $idOrden AND area = '$areaSql' LIMIT 1");
        if ($qExiste && ($filaEx = mysqli_fetch_assoc($qExiste))) {
            $idAsigEx = intval($filaEx['id']);
            $sqlUpd = "UPDATE asignaciones_diseno 
                       SET id_disenador = $idEncargado, 
                           prioridad = $prioridad, 
                           tareas_asignadas = IF('$tareasSql' <> '', '$tareasSql', tareas_asignadas)
                       WHERE id = $idAsigEx";
            if (mysqli_query($conexion, $sqlUpd)) {
                $actualizadas++;
            }
        } else {
            $sqlIns = "INSERT INTO asignaciones_diseno (id_orden, id_disenador, area, tareas_asignadas, prioridad)
                       VALUES ($idOrden, $idEncargado, '$areaSql', '$tareasSql', $prioridad)";
            if (mysqli_query($conexion, $sqlIns)) {
                $creadas++;
            }
        }
    }

    if ($creadas === 0 && $actualizadas === 0) {
        responder_diseno(['success' => false, 'msg' => 'No se pudo registrar la asignación de la orden.'], 500);
    }

    responder_diseno([
        'success' => true,
        'msg' => "Orden emitida correctamente ($creadas nueva(s), $actualizadas actualizada(s))."
    ]);
}

if ($accion === 'editar_tareas_asignadas') {
    if (!$esJefeDiseno) {
        responder_diseno(['success' => false, 'msg' => 'Solo el Jefe de Diseño puede editar las tareas asignadas.'], 403);
    }
    $idAsignacion = intval($_POST['id_asignacion'] ?? 0);
    $tareas = trim((string)($_POST['tareas_asignadas'] ?? ''));
    if ($idAsignacion < 1) {
        responder_diseno(['success' => false, 'msg' => 'Asignación no válida.'], 400);
    }
    $tareasSql = mysqli_real_escape_string($conexion, $tareas);
    if (!mysqli_query($conexion, "UPDATE asignaciones_diseno SET tareas_asignadas = '$tareasSql' WHERE id = $idAsignacion")) {
        responder_diseno(['success' => false, 'msg' => 'No se pudieron guardar las tareas asignadas.'], 500);
    }
    responder_diseno(['success' => true, 'msg' => 'Tareas actualizadas correctamente.']);
}

if ($accion === 'actualizar_prioridad') {
    if (!$esJefeDiseno) {
        responder_diseno(['success' => false, 'msg' => 'Solo el jefe de diseño puede cambiar prioridades.'], 403);
    }
    $idAsignacion = intval($_POST['id_asignacion'] ?? 0);
    $prioridad = intval($_POST['prioridad'] ?? -1);
    if ($idAsignacion < 1 || $prioridad < 0) {
        responder_diseno(['success' => false, 'msg' => 'La prioridad debe ser un número igual o mayor que cero.'], 400);
    }
    if (!mysqli_query($conexion, "UPDATE asignaciones_diseno SET prioridad = $prioridad WHERE id = $idAsignacion")) {
        responder_diseno(['success' => false, 'msg' => 'No se pudo actualizar la prioridad.'], 500);
    }
    responder_diseno(['success' => true]);
}

if ($accion === 'quitar_asignacion') {
    if (!$esJefeDiseno) {
        responder_diseno(['success' => false, 'msg' => 'Solo el jefe de diseño puede quitar asignaciones.'], 403);
    }
    $idAsignacion = intval($_POST['id_asignacion'] ?? 0);
    if ($idAsignacion < 1) {
        responder_diseno(['success' => false, 'msg' => 'La asignación indicada no es válida.'], 400);
    }
    $qAvances = mysqli_query($conexion, "SELECT id FROM avances_diseno WHERE id_asignacion = $idAsignacion LIMIT 1");
    if ($qAvances && mysqli_num_rows($qAvances) > 0) {
        responder_diseno(['success' => false, 'msg' => 'No se puede quitar esta asignación porque ya tiene fases registradas.'], 409);
    }
    if (!mysqli_query($conexion, "DELETE FROM asignaciones_diseno WHERE id = $idAsignacion")) {
        responder_diseno(['success' => false, 'msg' => 'No se pudo quitar la asignación.'], 500);
    }
    responder_diseno(['success' => true]);
}

if ($accion === 'actualizar_estado') {
    if (!$esDisenador && !$esJefeDiseno) {
        responder_diseno(['success' => false, 'msg' => 'No tienes permiso para actualizar el estado.'], 403);
    }
    $idAsignacion = intval($_POST['id_asignacion'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    if ($idAsignacion < 1 || !in_array($estado, ['En Proceso', 'Finalizado'], true)) {
        responder_diseno(['success' => false, 'msg' => 'Estado no válido.'], 400);
    }
    $fechaFinalizado = $estado === 'Finalizado' ? 'NOW()' : 'NULL';
    $condUsuario = $esJefeDiseno ? "" : "AND id_disenador = $idUsuario";
    $qAsignacion = mysqli_query($conexion, "SELECT id, estado FROM asignaciones_diseno WHERE id = $idAsignacion $condUsuario");
    $asignacion = $qAsignacion ? mysqli_fetch_assoc($qAsignacion) : null;
    if (!$asignacion) {
        responder_diseno(['success' => false, 'msg' => 'No se encontró la asignación para actualizar.'], 404);
    }
    if ($estado === 'Finalizado' && $asignacion['estado'] !== 'En Proceso') {
        responder_diseno(['success' => false, 'msg' => 'Debes iniciar el proceso antes de finalizarlo.'], 409);
    }
    $q = mysqli_query($conexion, "
        UPDATE asignaciones_diseno
        SET estado = '" . mysqli_real_escape_string($conexion, $estado) . "',
            fecha_inicio = IF('$estado' = 'En Proceso', COALESCE(fecha_inicio, NOW()), fecha_inicio),
            fecha_finalizado = $fechaFinalizado,
            fecha_actualizacion = NOW()
        WHERE id = $idAsignacion $condUsuario");
    if (!$q) {
        responder_diseno(['success' => false, 'msg' => 'No se pudo actualizar el estado.'], 500);
    }
    responder_diseno(['success' => true]);
}

if ($accion === 'descargar_zip') {
    $idOrden = intval($_GET['id'] ?? 0);
    if ($idOrden < 1 || (!$esJefeDiseno && !orden_asignada_a($conexion, $idOrden, $idUsuario))) {
        http_response_code(403);
        exit('No tienes acceso a las fotos de esta orden.');
    }
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        exit('La extensión ZIP no está disponible en el servidor.');
    }

    $q = mysqli_query($conexion, "SELECT nro_orden_fabrica, foto FROM orden_fabrica WHERE ID = $idOrden");
    $orden = $q ? mysqli_fetch_assoc($q) : null;
    if (!$orden || empty($orden['foto'])) {
        http_response_code(404);
        exit('Esta orden no tiene imágenes adjuntas.');
    }

    $zipPath = tempnam(sys_get_temp_dir(), 'diseno_');
    if ($zipPath === false) {
        http_response_code(500);
        exit('No se pudo preparar el archivo comprimido.');
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
        unlink($zipPath);
        http_response_code(500);
        exit('No se pudo crear el archivo comprimido.');
    }

    $archivosAgregados = 0;
    foreach (explode(',', $orden['foto']) as $nombreArchivo) {
        $nombreArchivo = basename(trim($nombreArchivo));
        if ($nombreArchivo === '' || $nombreArchivo === '.' || $nombreArchivo === '..') {
            continue;
        }
        $rutas = [
            __DIR__ . '/../../images/ordenes/' . $nombreArchivo,
            __DIR__ . '/../../images/uploads/' . $nombreArchivo
        ];
        foreach ($rutas as $ruta) {
            if (is_file($ruta)) {
                $zip->addFile($ruta, $nombreArchivo);
                $archivosAgregados++;
                break;
            }
        }
    }
    $zip->close();

    if ($archivosAgregados === 0) {
        unlink($zipPath);
        http_response_code(404);
        exit('No se encontraron los archivos de esta orden en el servidor.');
    }

    $nroLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '', $orden['nro_orden_fabrica']);
    $nombreZip = 'Fotos_Orden_' . ($nroLimpio ?: $idOrden) . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $nombreZip . '"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    unlink($zipPath);
    exit;
}

responder_diseno(['success' => false, 'msg' => 'Acción no válida.'], 400);
?>