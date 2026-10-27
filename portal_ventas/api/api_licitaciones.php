<?php
session_start();
require_once '../../conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesión expirada']); exit;
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

// Permisos estandarizados
$id_usuario = intval($_SESSION['usuario_id']);
$q_usr = mysqli_query($conexion, "SELECT rol, permisos FROM usuario WHERE ID = $id_usuario");
$datos_usr = mysqli_fetch_assoc($q_usr);
$rol = strtolower($datos_usr['rol']);
$es_supervisor = ($rol === 'master' || $rol === 'supervisor_licitaciones');

if ($accion == 'registrar_ingreso') {
    $sql = "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usuario, 'INGRESÓ', 'licitaciones', 0, NOW(), 'Visualizó el panel de Licitaciones')";
    mysqli_query($conexion, $sql);
    echo json_encode(['success' => true]);
    exit;
}

if ($accion == 'cargar_todo') {
    $empresas = [];
    $q_emp = mysqli_query($conexion, "SELECT * FROM licitacion_empresas ORDER BY nombre ASC");
    while($e = mysqli_fetch_assoc($q_emp)) {
        $e['documentos'] = [];
        $empresas[$e['id']] = $e;
    }

    // El supervisor ve todo, los usuarios normales solo ven los visibles (visible = 1)
    $filtro_visibilidad = $es_supervisor ? "1=1" : "visible = 1";
    $q_doc = mysqli_query($conexion, "SELECT * FROM licitacion_documentos WHERE $filtro_visibilidad ORDER BY titulo ASC");
    
    while($d = mysqli_fetch_assoc($q_doc)) {
        if(isset($empresas[$d['id_empresa']])) {
            $empresas[$d['id_empresa']]['documentos'][] = $d;
        }
    }
    
    echo json_encode(['success' => true, 'data' => array_values($empresas), 'es_supervisor' => $es_supervisor]);
    exit;
}

if ($accion == 'toggle_visibilidad') {
    if(!$es_supervisor) { echo json_encode(['success' => false, 'msg' => 'No autorizado']); exit; }
    $id = intval($_POST['id']);
    $estado = intval($_POST['estado']);
    
    if(mysqli_query($conexion, "UPDATE licitacion_documentos SET visible = $estado WHERE id = $id")) {
        $estado_txt = $estado == 1 ? 'Mostró' : 'Ocultó';
        mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usuario, 'EDITAR', 'licitacion_documentos', $id, NOW(), '$estado_txt un documento de licitación')");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($accion == 'guardar_empresa') {
    if(!$es_supervisor) { echo json_encode(['success' => false, 'msg' => 'No autorizado']); exit; }
    $nombre = mysqli_real_escape_string($conexion, trim($_POST['nombre']));
    
    if(mysqli_query($conexion, "INSERT INTO licitacion_empresas (nombre) VALUES ('$nombre')")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($accion == 'guardar_documento') {
    if(!$es_supervisor) { echo json_encode(['success' => false, 'msg' => 'No autorizado']); exit; }
    $id_doc = intval($_POST['id_doc'] ?? 0);
    $id_empresa = intval($_POST['id_empresa']);
    $titulo = mysqli_real_escape_string($conexion, trim($_POST['titulo']));
    $tipo_doc = mysqli_real_escape_string($conexion, trim($_POST['tipo_doc']));
    $ruta = mysqli_real_escape_string($conexion, trim($_POST['ruta']));
    $descripcion = mysqli_real_escape_string($conexion, trim($_POST['descripcion'] ?? 'Acceder a documentos de esta sección.'));

    if ($id_doc > 0) {
        $sql = "UPDATE licitacion_documentos SET id_empresa=$id_empresa, titulo='$titulo', tipo_doc='$tipo_doc', ruta='$ruta', descripcion='$descripcion' WHERE id=$id_doc";
    } else {
        $sql = "INSERT INTO licitacion_documentos (id_empresa, titulo, tipo_doc, ruta, descripcion) VALUES ($id_empresa, '$titulo', '$tipo_doc', '$ruta', '$descripcion')";
    }

    if(mysqli_query($conexion, $sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($accion == 'eliminar_documento') {
    if(!$es_supervisor) { echo json_encode(['success' => false, 'msg' => 'No autorizado']); exit; }
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "DELETE FROM licitacion_documentos WHERE id = $id")) {
        echo json_encode(['success' => true]);
    }
    exit;
}
?>