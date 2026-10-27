<?php
session_start();
error_reporting(0);

require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesiиоn expirada']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'listar') {
    $q = mysqli_query($conexion, "SELECT * FROM nomina_empleados ORDER BY estado ASC, nombre ASC");
    $data = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = $r;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($action === 'guardar') {
    $id = intval($_POST['id_empleado'] ?? 0);
    $cedula = mysqli_real_escape_string($conexion, $_POST['cedula']);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $apellido = mysqli_real_escape_string($conexion, $_POST['apellido']);
    $cargo = mysqli_real_escape_string($conexion, $_POST['cargo']);
    $horario = intval($_POST['horario'] ?? 1);
    $departamento = mysqli_real_escape_string($conexion, $_POST['departamento'] ?? '');
    $telefono = mysqli_real_escape_string($conexion, $_POST['telefono']);
    
    $banco = mysqli_real_escape_string($conexion, $_POST['banco'] ?? '');
    $nro_cuenta = mysqli_real_escape_string($conexion, $_POST['nro_cuenta'] ?? '');
    
    $salario = floatval($_POST['salario']);
    $cesta_ticket = floatval($_POST['cesta_ticket']);
    $bonos = floatval($_POST['bonos']);
    
    $frecuencia = mysqli_real_escape_string($conexion, $_POST['frecuencia']);
    $fecha_ingreso = mysqli_real_escape_string($conexion, $_POST['fecha_ingreso']);
    $estado = mysqli_real_escape_string($conexion, $_POST['estado'] ?? 'Activo');

    if ($id > 0) {
        $sql = "UPDATE nomina_empleados SET 
                cedula='$cedula', nombre='$nombre', apellido='$apellido', 
                cargo='$cargo', horario=$horario, departamento='$departamento', telefono='$telefono', 
                banco='$banco', nro_cuenta='$nro_cuenta', 
                salario_base=$salario, cesta_ticket=$cesta_ticket, bonos=$bonos, 
                frecuencia_pago='$frecuencia', fecha_ingreso='$fecha_ingreso', estado='$estado' 
                WHERE id=$id";
    } else {
        $sql = "INSERT INTO nomina_empleados 
                (cedula, nombre, apellido, cargo, horario, departamento, telefono, banco, nro_cuenta, salario_base, cesta_ticket, bonos, frecuencia_pago, fecha_ingreso, estado) 
                VALUES 
                ('$cedula', '$nombre', '$apellido', '$cargo', $horario, '$departamento', '$telefono', '$banco', '$nro_cuenta', $salario, $cesta_ticket, $bonos, '$frecuencia', '$fecha_ingreso', 'Activo')";
    }

    if (mysqli_query($conexion, $sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($action === 'eliminar') {
    $id = intval($_POST['id']);
    if (mysqli_query($conexion, "DELETE FROM nomina_empleados WHERE id = $id")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($action === 'cambiar_estado') {
    $id = intval($_POST['id']);
    $estado = mysqli_real_escape_string($conexion, $_POST['estado']);
    if (mysqli_query($conexion, "UPDATE nomina_empleados SET estado = '$estado' WHERE id = $id")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($action === 'ver_perfil') {
    $id = intval($_POST['id']);
    
    $q_emp = mysqli_query($conexion, "SELECT * FROM nomina_empleados WHERE id = $id");
    if(mysqli_num_rows($q_emp) == 0){
        echo json_encode(['success' => false, 'msg' => 'No encontrado']);
        exit;
    }
    $emp = mysqli_fetch_assoc($q_emp);

    $date_in = new DateTime($emp['fecha_ingreso']);
    $date_now = new DateTime();
    $emp['dias_trabajados'] = $date_in->diff($date_now)->days;

    $q_pagos = mysqli_query($conexion, "SELECT * FROM nomina_pagos WHERE id_empleado = $id ORDER BY id DESC LIMIT 12");
    $pagos = [];
    $total_historico = 0;

    while($p = mysqli_fetch_assoc($q_pagos)) {
        $p['fecha_pago_fmt'] = date('d/m/Y', strtotime($p['fecha_pago']));
        $p['periodo_fmt'] = date('d/m/Y', strtotime($p['fecha_inicio_periodo'])) . ' al ' . date('d/m/Y', strtotime($p['fecha_fin_periodo']));
        $total_historico += floatval($p['total_pagado']);
        $pagos[] = $p;
    }
    
    $emp['pagos_historial'] = $pagos;
    $emp['total_pagado_historico'] = $total_historico;

    echo json_encode(['success' => true, 'data' => $emp]);
    exit;
}
?>