<?php
session_start();
require_once '../../conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesión expirada']); exit;
}

// COMPROBACIÓN DEL VECTOR 5 ESTRICTAMENTE
$id_admin = intval($_SESSION['usuario_id']);
$q = mysqli_query($conexion, "SELECT permisos FROM usuario WHERE ID = $id_admin");
$admin_dat = mysqli_fetch_assoc($q);
$permisos_admin = explode(',', $admin_dat['permisos'] ?? '');

if (!in_array('5', $permisos_admin)) {
    echo json_encode(['success' => false, 'msg' => 'No tienes permiso para gestionar usuarios (Vector 5).']); exit;
}

function registrar_auditoria($con, $id_usr, $accion, $tabla, $id_reg, $detalle) {
    $acc = mysqli_real_escape_string($con, $accion);
    $tab = mysqli_real_escape_string($con, $tabla);
    $det = mysqli_real_escape_string($con, $detalle);
    mysqli_query($con, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usr, '$acc', '$tab', $id_reg, NOW(), '$det')");
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

if ($accion == 'listar') {
    $sql = "SELECT ID, nombre_usuario, rol, nombre, apellido, cedula, tlf, email, tiene_meta, permisos FROM usuario ORDER BY rol ASC, nombre ASC";
    $query = mysqli_query($conexion, $sql);
    $data = [];
    if($query){ while($row = mysqli_fetch_assoc($query)){ $data[] = $row; } }
    echo json_encode(['success' => true, 'data' => $data]); exit;
}

if ($accion == 'obtener') {
    $id = intval($_GET['id']);
    $query = mysqli_query($conexion, "SELECT * FROM usuario WHERE ID = $id");
    echo json_encode(['success' => true, 'data' => mysqli_fetch_assoc($query)]); exit;
}

if ($accion == 'guardar') {
    $id = intval($_POST['id_usuario'] ?? 0);
    $user = mysqli_real_escape_string($conexion, trim($_POST['nombre_usuario']));
    $pass = mysqli_real_escape_string($conexion, trim($_POST['contrasena']));
    $rol = mysqli_real_escape_string($conexion, trim($_POST['rol']));
    $nombre = mysqli_real_escape_string($conexion, trim($_POST['nombre']));
    $apellido = mysqli_real_escape_string($conexion, trim($_POST['apellido']));
    $cedula = mysqli_real_escape_string($conexion, trim($_POST['cedula']));
    $tlf = mysqli_real_escape_string($conexion, trim($_POST['tlf']));
    $profesion = mysqli_real_escape_string($conexion, trim($_POST['profesion']));
    $email = mysqli_real_escape_string($conexion, trim($_POST['email']));
    $tiene_meta = isset($_POST['tiene_meta']) ? 1 : 0;
    
    $permisos_str = '';
    if (isset($_POST['permisos']) && is_array($_POST['permisos'])) {
        $permisos_str = implode(',', $_POST['permisos']);
    }

    if ($id > 0) {
        $sql = "UPDATE usuario SET nombre_usuario='$user', rol='$rol', nombre='$nombre', apellido='$apellido', cedula='$cedula', tlf='$tlf', profesion='$profesion', email='$email', tiene_meta=$tiene_meta, permisos='$permisos_str'";
        if(!empty($pass)) $sql .= ", contrasena='$pass'";
        $sql .= " WHERE ID=$id";
        
        if (mysqli_query($conexion, $sql)) {
            registrar_auditoria($conexion, $id_admin, 'EDITAR', 'usuario', $id, "Editó usuario ID $id ($user)");
            echo json_encode(['success' => true]);
        } else echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    } else {
        $sql = "INSERT INTO usuario (nombre_usuario, contrasena, rol, nombre, apellido, cedula, tlf, profesion, email, tiene_meta, permisos) 
                VALUES ('$user', '$pass', '$rol', '$nombre', '$apellido', '$cedula', '$tlf', '$profesion', '$email', $tiene_meta, '$permisos_str')";
        if (mysqli_query($conexion, $sql)) {
            registrar_auditoria($conexion, $id_admin, 'CREAR', 'usuario', mysqli_insert_id($conexion), "Creó usuario ($user)");
            echo json_encode(['success' => true]);
        } else echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($accion == 'eliminar') {
    $id = intval($_POST['id']);
    if($id == $id_admin) { echo json_encode(['success' => false, 'msg' => 'No puedes eliminarte a ti mismo.']); exit; }
    
    // Romper restricciones de clave foránea antes de eliminar al usuario (Limpia el camino y no da error en BD)
    mysqli_query($conexion, "DELETE FROM historial_actividad WHERE id_usuario = $id");
    mysqli_query($conexion, "UPDATE cotizacion SET id_vendedor = NULL WHERE id_vendedor = $id");
    mysqli_query($conexion, "UPDATE orden_fabrica SET id_usuario_vendedor = NULL WHERE id_usuario_vendedor = $id");
    mysqli_query($conexion, "DELETE FROM tareas WHERE Usuario = $id");
    
    if(mysqli_query($conexion, "DELETE FROM usuario WHERE ID = $id")) {
        registrar_auditoria($conexion, $id_admin, 'ELIMINAR', 'usuario', $id, "Eliminó usuario ID $id");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'No se pudo eliminar el usuario: ' . mysqli_error($conexion)]);
    }
    exit;
}
?>