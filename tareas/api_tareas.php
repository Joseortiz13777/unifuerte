<?php
session_start();
error_reporting(0); // Apagamos errores visuales para no romper el JSON
require_once '../conexion.php';

// Limpiamos el buffer por si conexion.php tiene espacios en blanco
ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesión expirada']); exit;
}

$id_usuario = intval($_SESSION['usuario_id']);

// AUTO-PARCHE DE BASE DE DATOS
@mysqli_query($conexion, "ALTER TABLE tareas_diarias ADD COLUMN IF NOT EXISTS prioridad VARCHAR(20) DEFAULT 'Media'");
@mysqli_query($conexion, "ALTER TABLE tareas_diarias ADD COLUMN IF NOT EXISTS id_empleado INT DEFAULT 0");
@mysqli_query($conexion, "ALTER TABLE tareas_diarias ADD COLUMN IF NOT EXISTS departamento_tarea VARCHAR(50) DEFAULT 'Envíos'");
@mysqli_query($conexion, "ALTER TABLE tareas_diarias CHANGE estado estado INT DEFAULT 0"); 

// === INYECTOR DE DATOS SIMULADOS ===
// Si la tabla está vacía, metemos datos de prueba con tus empleados reales
$check_empty = mysqli_query($conexion, "SELECT COUNT(*) as total FROM tareas_diarias");
$count_tareas = mysqli_fetch_assoc($check_empty)['total'];

if ($count_tareas == 0) {
    // Buscar 3 empleados activos al azar para asignarles las tareas
    $emps = [];
    $qe = mysqli_query($conexion, "SELECT id FROM nomina_empleados WHERE estado='Activo' LIMIT 3");
    while($r = mysqli_fetch_assoc($qe)) { $emps[] = $r['id']; }
    
    $e1 = $emps[0] ?? 0;
    $e2 = $emps[1] ?? $e1;
    $e3 = $emps[2] ?? $e1;

    $sql_simulacion = "INSERT INTO tareas_diarias (id_usuario, tarea, estado, fecha_tarea, prioridad, id_empleado, departamento_tarea) VALUES 
    ($id_usuario, 'Cortar 50 metros de tela Atlética para el pedido de PDVSA.', 0, CURDATE(), 'Alta', $e1, 'Corte'),
    ($id_usuario, 'Ensamblar 20 camisas Columbia (Cliente: CVG Logística).', 1, CURDATE(), 'Media', $e2, 'Confección'),
    ($id_usuario, 'Bordar 15 logos frontales para el equipo de Softbol (Leyendas de Manoa).', 0, CURDATE(), 'Media', $e3, 'Bordado'),
    ($id_usuario, 'Preparar paquete de envío por Tealca para el cliente en Caracas.', 2, CURDATE(), 'Baja', $e1, 'Logística y Envíos')";
    
    @mysqli_query($conexion, $sql_simulacion);
}
// ====================================

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

if ($accion === 'listar_empleados') {
    $q = mysqli_query($conexion, "SELECT id, nombre, apellido, cargo, departamento FROM nomina_empleados WHERE estado = 'Activo' ORDER BY nombre ASC");
    $data = [];
    if($q) { while ($row = mysqli_fetch_assoc($q)) { $data[] = $row; } }
    
    ob_clean();
    echo json_encode(['success' => true, 'data' => $data]); exit;
}

if ($accion === 'listar_tareas') {
    $q = mysqli_query($conexion, "SELECT t.*, e.nombre AS emp_nombre, e.apellido AS emp_apellido, e.cargo 
                                  FROM tareas_diarias t 
                                  LEFT JOIN nomina_empleados e ON t.id_empleado = e.id 
                                  ORDER BY FIELD(t.prioridad, 'Alta', 'Media', 'Baja'), t.id DESC");
    $data = [];
    if($q){ while ($row = mysqli_fetch_assoc($q)) { $data[] = $row; } }
    
    ob_clean();
    echo json_encode(['success' => true, 'data' => $data]); exit;
}

if ($accion === 'guardar_tarea') {
    $tarea = mysqli_real_escape_string($conexion, trim($_POST['tarea']));
    $id_empleado = intval($_POST['id_empleado']);
    $prioridad = mysqli_real_escape_string($conexion, $_POST['prioridad']);
    $departamento = mysqli_real_escape_string($conexion, $_POST['departamento']);
    $fecha = date('Y-m-d');

    $sql = "INSERT INTO tareas_diarias (id_usuario, tarea, estado, fecha_tarea, prioridad, id_empleado, departamento_tarea) 
            VALUES ($id_usuario, '$tarea', 0, '$fecha', '$prioridad', $id_empleado, '$departamento')";
    
    $res = mysqli_query($conexion, $sql);
    ob_clean();
    if ($res) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    exit;
}

if ($accion === 'actualizar_estado') {
    $id_tarea = intval($_POST['id_tarea']);
    $nuevo_estado = intval($_POST['estado']);

    $res = mysqli_query($conexion, "UPDATE tareas_diarias SET estado = $nuevo_estado WHERE id = $id_tarea");
    ob_clean();
    if ($res) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    exit;
}

if ($accion === 'eliminar_tarea') {
    $id_tarea = intval($_POST['id_tarea']);
    $res = mysqli_query($conexion, "DELETE FROM tareas_diarias WHERE id = $id_tarea");
    ob_clean();
    if ($res) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    exit;
}
?>