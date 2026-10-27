<?php
ob_start();
session_start();
error_reporting(0);
$id_usuario = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;
session_write_close();

header('Content-Type: application/json; charset=utf-8');

function guardarFotoTarea(array $archivo): string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || !isset($archivo['tmp_name'], $archivo['size'])
        || !is_uploaded_file((string)$archivo['tmp_name'])
        || (int)$archivo['size'] > 5 * 1024 * 1024
        || !function_exists('finfo_open')
        || !function_exists('getimagesize')) {
        throw new Exception("Adjunta una imagen JPG, PNG o WEBP válida de hasta 5 MB.");
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) throw new Exception("No se pudieron validar imágenes.");
    $mime = finfo_file($finfo, (string)$archivo['tmp_name']);
    finfo_close($finfo);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || @getimagesize((string)$archivo['tmp_name']) === false) {
        throw new Exception("Adjunta una imagen JPG, PNG o WEBP válida de hasta 5 MB.");
    }
    $directory = __DIR__ . '/../../inventario/uploads/tareas/';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new Exception("No se pudo crear el directorio de imágenes.");
    }
    $filename = 'tarea_' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file((string)$archivo['tmp_name'], $directory . $filename)) {
        throw new Exception("No se pudo guardar la imagen adjunta.");
    }
    return $filename;
}

function eliminarFotoTarea(string $filename): void
{
    if (!preg_match('/^tarea_[a-f0-9]{24}\.(jpg|png|webp)$/i', $filename)) return;
    $path = __DIR__ . '/../../inventario/uploads/tareas/' . $filename;
    if (is_file($path) && !unlink($path)) {
        error_log('Administración tareas: no se pudo eliminar el archivo de imagen ' . $filename);
    }
}

try {
    require_once '../../conexion.php';
    if (!isset($conexion) || !$conexion) throw new Exception("Fallo de conexion a la BD.");
    mysqli_set_charset($conexion, "utf8mb4");

    if ($id_usuario === 0) throw new Exception("Sesion expirada.");
    $qRol = mysqli_query($conexion, "SELECT rol FROM usuario WHERE ID = $id_usuario LIMIT 1");
    $datosRol = $qRol ? mysqli_fetch_assoc($qRol) : null;
    if (!$datosRol || !in_array(strtolower(trim((string)$datosRol['rol'])), ['master', 'admin'], true)) {
        http_response_code(403);
        throw new Exception("No tienes permiso para gestionar tareas de Administración.");
    }

    // Auto-parches de base de datos para soportar los 4 Objetivos Fijos y tareas ancladas por objetivo
    mysqli_query($conexion, "ALTER TABLE tareas_diarias ADD COLUMN IF NOT EXISTS anclada TINYINT(1) NOT NULL DEFAULT 1");
    mysqli_query($conexion, "ALTER TABLE tareas_diarias ADD COLUMN IF NOT EXISTS nro_objetivo TINYINT(1) NOT NULL DEFAULT 1");
    mysqli_query($conexion, "ALTER TABLE tareas_diarias ADD COLUMN IF NOT EXISTS archivada TINYINT(1) NOT NULL DEFAULT 0");

    mysqli_query($conexion, "
        CREATE TABLE IF NOT EXISTS tareas_objetivos_titulos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            nro_objetivo TINYINT(1) NOT NULL,
            titulo VARCHAR(255) NOT NULL DEFAULT '',
            actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_usr_obj (id_usuario, nro_objetivo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Asegurar que existan los 4 objetivos por defecto para este usuario
    for ($i = 1; $i <= 4; $i++) {
        $tituloDef = mysqli_real_escape_string($conexion, "Objetivo #$i");
        mysqli_query($conexion, "INSERT IGNORE INTO tareas_objetivos_titulos (id_usuario, nro_objetivo, titulo) VALUES ($id_usuario, $i, '$tituloDef')");
    }

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'get_tareas_hoy') {
        $fecha = date('Y-m-d');
        
        // 1. Obtener los títulos de las 4 tarjetas de objetivos
        $objetivos = [
            1 => "Objetivo #1",
            2 => "Objetivo #2",
            3 => "Objetivo #3",
            4 => "Objetivo #4"
        ];
        $qObj = mysqli_query($conexion, "SELECT nro_objetivo, titulo FROM tareas_objetivos_titulos WHERE id_usuario = $id_usuario ORDER BY nro_objetivo ASC");
        if ($qObj) {
            while ($rObj = mysqli_fetch_assoc($qObj)) {
                $num = intval($rObj['nro_objetivo']);
                if ($num >= 1 && $num <= 4) {
                    $objetivos[$num] = (string)$rObj['titulo'];
                }
            }
        }

        // 2. Obtener las tareas activas (de hoy o ancladas a los objetivos y no archivadas)
        $q = mysqli_query($conexion, "
            SELECT * FROM tareas_diarias 
            WHERE id_usuario = $id_usuario 
              AND archivada = 0 
              AND (fecha_tarea = '$fecha' OR anclada = 1) 
            ORDER BY anclada DESC, estado ASC, id DESC
        ");
        if (!$q) throw new Exception("No se pudieron cargar las tareas de los objetivos.");
        
        $data = [];
        while($r = mysqli_fetch_assoc($q)) {
            $nro = intval($r['nro_objetivo'] ?? 1);
            if ($nro < 1 || $nro > 4) $nro = 1;
            $r['nro_objetivo'] = $nro;
            $data[] = $r;
        }
        echo json_encode([
            'success' => true,
            'data' => $data,
            'objetivos' => $objetivos
        ]);
        exit;
    }

    if ($accion === 'guardar_titulo_objetivo') {
        $nro = intval($_POST['nro_objetivo'] ?? 0);
        $tituloTexto = trim((string)($_POST['titulo'] ?? ''));
        if ($nro < 1 || $nro > 4) {
            throw new Exception("Número de objetivo inválido.");
        }
        if ($tituloTexto === '') {
            $tituloTexto = "Objetivo #$nro";
        }
        if (mb_strlen($tituloTexto, 'UTF-8') > 250) {
            $tituloTexto = mb_substr($tituloTexto, 0, 250, 'UTF-8');
        }
        $tituloSql = mysqli_real_escape_string($conexion, $tituloTexto);
        $sql = "INSERT INTO tareas_objetivos_titulos (id_usuario, nro_objetivo, titulo)
                VALUES ($id_usuario, $nro, '$tituloSql')
                ON DUPLICATE KEY UPDATE titulo = '$tituloSql'";
        if (!mysqli_query($conexion, $sql)) {
            throw new Exception("No se pudo guardar el título del objetivo.");
        }
        echo json_encode(['success' => true, 'titulo' => $tituloTexto]);
        exit;
    }

    if ($accion === 'crear_tarea') {
        $tareaTexto = trim((string)($_POST['tarea'] ?? ''));
        $fechaTexto = trim((string)($_POST['fecha_tarea'] ?? date('Y-m-d')));
        $nroObjetivo = intval($_POST['nro_objetivo'] ?? 1);
        if ($nroObjetivo < 1 || $nroObjetivo > 4) $nroObjetivo = 1;

        if ($tareaTexto === '' || mb_strlen($tareaTexto, 'UTF-8') > 1000) {
            throw new Exception("Describe la tarea para este objetivo.");
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaTexto)) {
            $fechaTexto = date('Y-m-d');
        }
        $tarea = mysqli_real_escape_string($conexion, $tareaTexto);
        $fecha = mysqli_real_escape_string($conexion, $fechaTexto);
        
        $nombre_foto = guardarFotoTarea($_FILES['foto'] ?? []);

        // Las tareas creadas dentro de un objetivo quedan ancladas por defecto (anclada = 1)
        $sql = "INSERT INTO tareas_diarias (id_usuario, nro_objetivo, tarea, foto, estado, fecha_tarea, anclada, archivada) 
                VALUES ($id_usuario, $nroObjetivo, '$tarea', '$nombre_foto', 0, '$fecha', 1, 0)";
        if(mysqli_query($conexion, $sql)) {
            echo json_encode(['success' => true]);
        } else {
            eliminarFotoTarea($nombre_foto);
            throw new Exception("No se pudo guardar la tarea en la base de datos.");
        }
        exit;
    }

    if ($accion === 'editar_tarea') {
        $id = (int)($_POST['id_tarea'] ?? 0);
        $tareaTexto = trim((string)($_POST['tarea'] ?? ''));
        $nroObjetivo = intval($_POST['nro_objetivo'] ?? 1);
        if ($nroObjetivo < 1 || $nroObjetivo > 4) $nroObjetivo = 1;

        if ($id < 1 || $tareaTexto === '' || mb_strlen($tareaTexto, 'UTF-8') > 1000) {
            throw new Exception("La tarea seleccionada o su descripción no es válida.");
        }
        $tarea = mysqli_real_escape_string($conexion, $tareaTexto);
        $qFotoAnterior = mysqli_query($conexion, "SELECT foto FROM tareas_diarias WHERE id = $id AND id_usuario = $id_usuario LIMIT 1");
        $tareaAnterior = $qFotoAnterior ? mysqli_fetch_assoc($qFotoAnterior) : null;
        if (!$tareaAnterior) throw new Exception("No se encontró la tarea seleccionada.");
        
        $fotoAnterior = (string)($tareaAnterior['foto'] ?? '');
        $fotoNueva = guardarFotoTarea($_FILES['foto'] ?? []);
        $quitarFoto = (string)($_POST['quitar_foto'] ?? '') === '1';
        $fotoFinal = $fotoNueva !== '' ? $fotoNueva : ($quitarFoto ? '' : $fotoAnterior);
        $fotoFinalSql = mysqli_real_escape_string($conexion, $fotoFinal);
        
        $updated = mysqli_query($conexion, "
            UPDATE tareas_diarias 
            SET tarea = '$tarea', nro_objetivo = $nroObjetivo, foto = '$fotoFinalSql' 
            WHERE id = $id AND id_usuario = $id_usuario
        ");
        if (!$updated) {
            if ($fotoNueva !== '') eliminarFotoTarea($fotoNueva);
            throw new Exception("No se pudo actualizar la tarea.");
        }
        if ($fotoAnterior !== '' && ($fotoNueva !== '' || $quitarFoto)) eliminarFotoTarea($fotoAnterior);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($accion === 'anclar_tarea') {
        $id = (int)($_POST['id'] ?? 0);
        $anclada = (int)($_POST['anclada'] ?? -1);
        if ($id < 1 || !in_array($anclada, [0, 1], true)) {
            throw new Exception("La tarea seleccionada no es válida.");
        }
        $archivadaSql = $anclada === 1 ? ", archivada = 0" : "";
        $updated = mysqli_query(
            $conexion,
            "UPDATE tareas_diarias SET anclada = $anclada $archivadaSql WHERE id = $id AND id_usuario = $id_usuario"
        );
        if (!$updated) throw new Exception("No se pudo anclar la tarea al objetivo.");
        echo json_encode(['success' => true]);
        exit;
    }

    if ($accion === 'toggle_tarea') {
        $id = (int)($_POST['id'] ?? 0);
        $estado = (int)($_POST['estado'] ?? -1);
        if ($id < 1 || !in_array($estado, [0, 1], true)) throw new Exception("El estado de la tarea no es válido.");
        if (!mysqli_query($conexion, "UPDATE tareas_diarias SET estado = $estado WHERE id = $id AND id_usuario = $id_usuario")) {
            throw new Exception("No se pudo actualizar el estado de la tarea.");
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($accion === 'limpiar_completadas_objetivo') {
        $nro = intval($_POST['nro_objetivo'] ?? 0);
        $whereObj = ($nro >= 1 && $nro <= 4) ? "AND nro_objetivo = $nro" : "";
        mysqli_query($conexion, "UPDATE tareas_diarias SET archivada = 1, anclada = 0 WHERE id_usuario = $id_usuario AND estado = 1 $whereObj");
        echo json_encode(['success' => true]);
        exit;
    }

    if ($accion === 'eliminar_tarea') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) throw new Exception("La tarea seleccionada no es válida.");
        $qFoto = mysqli_query($conexion, "SELECT foto FROM tareas_diarias WHERE id = $id AND id_usuario = $id_usuario LIMIT 1");
        $tareaAEliminar = $qFoto ? mysqli_fetch_assoc($qFoto) : null;
        if (!$tareaAEliminar) throw new Exception("No se encontró la tarea seleccionada.");
        $deleted = mysqli_query($conexion, "DELETE FROM tareas_diarias WHERE id = $id AND id_usuario = $id_usuario");
        if (!$deleted) throw new Exception("No se pudo eliminar la tarea.");
        eliminarFotoTarea((string)($tareaAEliminar['foto'] ?? ''));
        echo json_encode(['success' => true]);
        exit;
    }

    if ($accion === 'get_historial') {
        $fecha = date('Y-m-d');
        $q = mysqli_query($conexion, "
            SELECT t.*, COALESCE(o.titulo, CONCAT('Objetivo #', t.nro_objetivo)) AS nombre_objetivo
            FROM tareas_diarias t
            LEFT JOIN tareas_objetivos_titulos o ON o.id_usuario = t.id_usuario AND o.nro_objetivo = t.nro_objetivo
            WHERE t.id_usuario = $id_usuario 
              AND (t.archivada = 1 OR (t.fecha_tarea < '$fecha' AND t.anclada = 0))
            ORDER BY t.fecha_tarea DESC, t.id DESC
        ");
        if (!$q) throw new Exception("No se pudo cargar el historial de tareas.");
        $data = [];
        while($r = mysqli_fetch_assoc($q)) {
            $data[] = $r;
        }
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    throw new Exception("Accion no valida.");

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    exit;
}
?>