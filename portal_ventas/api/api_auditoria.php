<?php
session_start();
require_once '../../conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesiиоn expirada']); exit;
}

// Identificar al usuario
$id_u = intval($_SESSION['usuario_id']);
$q_usr = mysqli_query($conexion, "SELECT rol, permisos FROM usuario WHERE ID = $id_u");
$u_dat = mysqli_fetch_assoc($q_usr);
$rol = strtolower($u_dat['rol']);
$permisos = explode(',', $u_dat['permisos'] ?? '');

// Verificar si tiene al menos UN permiso para auditar
if ($rol !== 'master' && !in_array('16', $permisos) && !in_array('23', $permisos) && !in_array('24', $permisos) && !in_array('25', $permisos)) {
    echo json_encode(['success' => false, 'msg' => 'No tienes permisos de Auditorикa.']); exit;
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? 'listar';

if ($accion === 'obtener_usuario') {
    $id_req = intval($_GET['id_usuario']);
    $q = mysqli_query($conexion, "SELECT nombre_usuario, rol, nombre, apellido, cedula, tlf, profesion, email FROM usuario WHERE ID = $id_req");
    if ($row = mysqli_fetch_assoc($q)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'El usuario no existe o fue eliminado de la base de datos.']);
    }
    exit;
}

if ($accion === 'listar') {
    $desde = $_GET['desde'] ?? date('Y-m-01'); 
    $hasta = $_GET['hasta'] ?? date('Y-m-t');
    
    // Filtros que el usuario mandио por los Selects
    $departamento_filtro = $_GET['departamento'] ?? 'todos';
    $usuario_especifico = intval($_GET['usuario'] ?? 0);

    // Determinar permisos base
    $roles_permitidos = [];
    if ($rol === 'master' || in_array('16', $permisos)) {
        $roles_permitidos = ['all']; 
    } else {
        if (in_array('23', $permisos)) $roles_permitidos[] = "'ventas'";
        if (in_array('24', $permisos)) { $roles_permitidos[] = "'licitaciones'"; $roles_permitidos[] = "'supervisor_licitaciones'"; }
        if (in_array('25', $permisos)) { $roles_permitidos[] = "'logistica'"; $roles_permitidos[] = "'admin'"; $roles_permitidos[] = "'jefe_ventas'"; }
    }

    $filtro_sql = "1=1";

    if (!in_array('all', $roles_permitidos) && count($roles_permitidos) > 0) {
        $filtro_sql .= " AND u.rol IN (" . implode(',', $roles_permitidos) . ") ";
    } elseif (!in_array('all', $roles_permitidos) && count($roles_permitidos) == 0) {
        $filtro_sql .= " AND 1=0 ";
    }

    // Filtros del Usuario de la Interfaz
    if ($departamento_filtro === 'ventas') $filtro_sql .= " AND u.rol = 'ventas' ";
    if ($departamento_filtro === 'licitaciones') $filtro_sql .= " AND u.rol IN ('licitaciones', 'supervisor_licitaciones') ";
    if ($departamento_filtro === 'logistica') $filtro_sql .= " AND u.rol = 'logistica' ";
    if ($departamento_filtro === 'admin') $filtro_sql .= " AND u.rol IN ('admin', 'jefe_ventas', 'master') ";

    if ($usuario_especifico > 0) {
        $filtro_sql .= " AND u.ID = $usuario_especifico ";
    }

    $sql = "SELECT h.fecha_hora, h.accion, h.tabla_afectada, h.detalle_cambio, u.ID as id_usuario_actor, u.nombre, u.apellido, u.rol AS rol_actor 
            FROM historial_actividad h 
            LEFT JOIN usuario u ON h.id_usuario = u.ID 
            WHERE DATE(h.fecha_hora) BETWEEN '$desde' AND '$hasta' AND $filtro_sql
            ORDER BY h.fecha_hora DESC";

    $query = mysqli_query($conexion, $sql);
    $data = [];
    if($query){
        while($row = mysqli_fetch_assoc($query)){
            $row['fecha_hora'] = date('d/m/Y h:i A', strtotime($row['fecha_hora']));
            $data[] = $row;
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}
?>