<?php
session_start();
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
require_once '../conexion.php';
mysqli_set_charset($conexion, "utf8mb4");

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesión expirada']); exit;
}

$id_usuario_actual = intval($_SESSION['usuario_id']);
$rol_actual = strtolower($_SESSION['rol'] ?? '');
$es_master = ($rol_actual === 'master');
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

// =======================================================
if ($accion === 'listar') {
    $sql = "SELECT t.*, u.nombre as autor, u.apellido 
            FROM ayuda_tickets t 
            LEFT JOIN usuario u ON t.id_usuario = u.ID 
            ORDER BY t.estado DESC, t.id DESC"; // Muestra los Pendientes primero
    $q = mysqli_query($conexion, $sql);
    $data = [];
    
    while ($r = mysqli_fetch_assoc($q)) {
        $r['es_mio'] = ($r['id_usuario'] == $id_usuario_actual);
        $r['puede_editar'] = $r['es_mio'];
        $r['puede_eliminar'] = $r['es_mio'] || $es_master;
        $r['autor_full'] = $r['autor'] . ' ' . $r['apellido'];
        $data[] = $r;
    }
    
    echo json_encode(['success' => true, 'data' => $data, 'es_master' => $es_master]);
    exit;
}

// =======================================================
if ($accion === 'obtener') {
    $id = intval($_GET['id']);
    $q = mysqli_query($conexion, "SELECT * FROM ayuda_tickets WHERE id = $id");
    if ($r = mysqli_fetch_assoc($q)) {
        if ($r['id_usuario'] != $id_usuario_actual && !$es_master) {
            echo json_encode(['success' => false, 'msg' => 'No tienes permiso para editar el reporte de otro usuario.']); exit;
        }
        echo json_encode(['success' => true, 'data' => $r]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Ticket no encontrado']);
    }
    exit;
}

// =======================================================
if ($accion === 'guardar_ticket') {
    $id_ticket = intval($_POST['id_ticket']);
    $titulo = mysqli_real_escape_string($conexion, trim($_POST['titulo']));
    $desc = mysqli_real_escape_string($conexion, trim($_POST['descripcion']));
    
    if ($id_ticket > 0) {
        $q_check = mysqli_query($conexion, "SELECT id_usuario, fotos FROM ayuda_tickets WHERE id = $id_ticket");
        $old = mysqli_fetch_assoc($q_check);
        if ($old['id_usuario'] != $id_usuario_actual && !$es_master) {
            echo json_encode(['success' => false, 'msg' => 'No tienes permiso para editar esto.']); exit;
        }
        $fotos_actuales = $old['fotos'];
    } else {
        $fotos_actuales = "";
    }

    $fotos_nuevas = [];
    if (isset($_FILES['fotos'])) {
        $dir_uploads = "../images/ayuda/"; // RUTA CORREGIDA
        if (!is_dir($dir_uploads)) @mkdir($dir_uploads, 0777, true);
        
        $total = is_array($_FILES['fotos']['name']) ? count($_FILES['fotos']['name']) : 0;
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['fotos']['error'][$i] == UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION));
                $nombre_archivo = "ticket_" . time() . "_" . rand(100,999) . "_" . $i . "." . $ext;
                if (move_uploaded_file($_FILES['fotos']['tmp_name'][$i], $dir_uploads . $nombre_archivo)) {
                    $fotos_nuevas[] = $nombre_archivo;
                }
            }
        }
    }
    
    $foto_str_final = $fotos_actuales;
    if (!empty($fotos_nuevas)) {
        $str_nuevas = implode(',', $fotos_nuevas);
        $foto_str_final = empty($fotos_actuales) ? $str_nuevas : $fotos_actuales . ',' . $str_nuevas;
    }
    
    if ($id_ticket > 0) {
        $sql = "UPDATE ayuda_tickets SET titulo='$titulo', descripcion='$desc', fotos='$foto_str_final' WHERE id=$id_ticket";
    } else {
        $sql = "INSERT INTO ayuda_tickets (id_usuario, titulo, descripcion, fotos) VALUES ($id_usuario_actual, '$titulo', '$desc', '$foto_str_final')";
    }
    
    if (mysqli_query($conexion, $sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

// =======================================================
if ($accion === 'eliminar_foto') {
    $id_ticket = intval($_POST['id_ticket']);
    $foto = trim(mysqli_real_escape_string($conexion, $_POST['foto']));
    
    $q = mysqli_query($conexion, "SELECT id_usuario, fotos FROM ayuda_tickets WHERE id = $id_ticket");
    if ($row = mysqli_fetch_assoc($q)) {
        if ($row['id_usuario'] != $id_usuario_actual && !$es_master) {
            echo json_encode(['success' => false, 'msg' => 'Sin permisos']); exit;
        }
        
        $fotos_array = explode(',', $row['fotos']);
        $fotos_array = array_filter($fotos_array, function($f) use ($foto) { return trim($f) !== '' && trim($f) !== $foto; });
        $nuevo_str = implode(',', $fotos_array);
        
        mysqli_query($conexion, "UPDATE ayuda_tickets SET fotos = '$nuevo_str' WHERE id = $id_ticket");
        @unlink("../images/ayuda/" . $foto); // RUTA CORREGIDA
        echo json_encode(['success' => true]);
    }
    exit;
}

// =======================================================
if ($accion === 'marcar_corregido') {
    if (!$es_master) {
        echo json_encode(['success' => false, 'msg' => 'Solo la Gerencia (Master) puede marcar como corregido.']); exit;
    }
    
    $id = intval($_POST['id']);
    $estado = mysqli_real_escape_string($conexion, trim($_POST['estado']));
    
    if(mysqli_query($conexion, "UPDATE ayuda_tickets SET estado = '$estado' WHERE id = $id")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

// =======================================================
if ($accion === 'eliminar_ticket') {
    $id = intval($_POST['id']);
    
    $q = mysqli_query($conexion, "SELECT id_usuario, fotos FROM ayuda_tickets WHERE id = $id");
    if ($row = mysqli_fetch_assoc($q)) {
        if ($row['id_usuario'] != $id_usuario_actual && !$es_master) {
            echo json_encode(['success' => false, 'msg' => 'No puedes eliminar el reporte de otra persona.']); exit;
        }
        
        $fotos_array = explode(',', $row['fotos']);
        foreach($fotos_array as $f) { if(trim($f)!='') @unlink("../images/ayuda/" . trim($f)); } // RUTA CORREGIDA
        
        mysqli_query($conexion, "DELETE FROM ayuda_tickets WHERE id = $id");
        echo json_encode(['success' => true]);
    }
    exit;
}
?>