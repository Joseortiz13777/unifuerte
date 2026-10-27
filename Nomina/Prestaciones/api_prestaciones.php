<?php
ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../conexion.php';

function enviarRespuesta($success, $msg = '', $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => $success];
    if ($msg !== '') $response['msg'] = $msg;
    if ($data !== null) $response['data'] = $data;
    
    $json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        echo '{"success":false,"msg":"Error interno de formato JSON."}';
    } else {
        echo $json;
    }
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    enviarRespuesta(false, 'Sesion expirada');
}

$action = $_POST['action'] ?? '';

// =========================================================
// 1. LISTAR HISTORIAL DE LIQUIDACIONES ACTIVAS
// =========================================================
if ($action === 'listar_historial') {
    $q = mysqli_query($conexion, "
        SELECT p.*, e.nombre, e.apellido, e.cedula 
        FROM nomina_prestaciones p
        INNER JOIN nomina_empleados e ON p.id_empleado = e.id
        WHERE p.estado != 'Archivado'
        ORDER BY p.id DESC
    ");
    $data = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $r['fecha_fmt'] = date('d/m/Y', strtotime($r['fecha_calculo']));
            $data[] = $r;
        }
    }
    enviarRespuesta(true, '', $data);
}

// =========================================================
// 2. LISTAR HISTORIAL DE LIQUIDACIONES ARCHIVADAS
// =========================================================
if ($action === 'listar_archivados') {
    $q = mysqli_query($conexion, "
        SELECT p.*, e.nombre, e.apellido, e.cedula 
        FROM nomina_prestaciones p
        INNER JOIN nomina_empleados e ON p.id_empleado = e.id
        WHERE p.estado = 'Archivado'
        ORDER BY p.id DESC
    ");
    $data = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $r['fecha_fmt'] = date('d/m/Y', strtotime($r['fecha_calculo']));
            $data[] = $r;
        }
    }
    enviarRespuesta(true, '', $data);
}

// =========================================================
// 3. SIMULADOR DE LIQUIDACIÓN
// =========================================================
if ($action === 'simular_liquidacion') {
    $id_empleado = intval($_POST['id_empleado'] ?? 0);
    $fecha_ingreso = trim($_POST['fecha_ingreso'] ?? '');
    $fecha_egreso = trim($_POST['fecha_egreso'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');

    if ($id_empleado <= 0 || empty($fecha_ingreso) || empty($fecha_egreso)) {
        enviarRespuesta(false, 'Faltan fechas obligatorias (Ingreso o Egreso) para el calculo.');
    }

    $q_emp = mysqli_query($conexion, "SELECT * FROM nomina_empleados WHERE id = $id_empleado");
    if (!$q_emp || mysqli_num_rows($q_emp) == 0) {
        enviarRespuesta(false, 'Empleado no encontrado en la base de datos.');
    }
    $emp = mysqli_fetch_assoc($q_emp);

    if ($emp['fecha_ingreso'] !== $fecha_ingreso) {
        $f_in_safe = mysqli_real_escape_string($conexion, $fecha_ingreso);
        mysqli_query($conexion, "UPDATE nomina_empleados SET fecha_ingreso = '$f_in_safe' WHERE id = $id_empleado");
    }
    
    $salario_mensual = floatval($emp['salario_base']) * 2;
    $salario_diario_basico = $salario_mensual / 30;

    $alic_bv = (15 * $salario_diario_basico) / 360;
    $alic_uti = (30 * $salario_diario_basico) / 360;
    
    $salario_integral_diario = $salario_diario_basico + $alic_bv + $alic_uti;
    $salario_promedio = $salario_diario_basico + $alic_bv;

    $fecha_ingreso = str_replace('/', '-', $fecha_ingreso);
    $fecha_egreso = str_replace('/', '-', $fecha_egreso);

    try {
        $ingreso = new DateTime($fecha_ingreso);
        $egreso = new DateTime($fecha_egreso);
    } catch (Exception $e) {
        enviarRespuesta(false, 'Error al procesar el formato de las fechas: ' . $e->getMessage());
    }
    
    if ($egreso < $ingreso) {
        enviarRespuesta(false, 'La fecha de egreso no puede ser menor a la de ingreso');
    }

    $diff = $ingreso->diff($egreso);
    $anios = $diff->y;
    $meses = $diff->m;
    $dias = $diff->d;
    $total_dias_calendario = $ingreso->diff($egreso)->days;
    
    $tiempo_servicio = "$anios anios, $meses meses, $dias dias";

    $q_faltas = mysqli_query($conexion, "SELECT SUM(dias_falta) as total_dias, SUM(horas_falta) as total_horas FROM nomina_pagos WHERE id_empleado = $id_empleado AND estado = 'Pagado'");
    $total_dias_falta = 0;
    $total_horas_falta = 0;

    if ($q_faltas) {
        $faltas_data = mysqli_fetch_assoc($q_faltas);
        $total_dias_falta = floatval($faltas_data['total_dias'] ?? 0);
        $total_horas_falta = floatval($faltas_data['total_horas'] ?? 0);
    }
    
    $dias_por_horas = $total_horas_falta / 8;
    $total_faltas_reales = $total_dias_falta + $dias_por_horas;
    
    $dias_efectivos = $total_dias_calendario - $total_faltas_reales;
    if ($dias_efectivos < 0) $dias_efectivos = 0;

    $meses_antiguedad = ($anios * 12) + $meses;
    if ($dias >= 15) {
        $meses_antiguedad += 1; 
    }
    
    $dias_prestaciones = $meses_antiguedad * 5; 
    $monto_prestaciones = $dias_prestaciones * $salario_integral_diario;

    $meses_fraccion = $meses + ($dias / 30);
    if ($anios == 0 && $meses_fraccion == 0 && $dias > 0) {
        $meses_fraccion = $dias / 30; 
    }

    $dias_vac_frac = (15 / 12) * $meses_fraccion;
    $dias_bvac_frac = (15 / 12) * $meses_fraccion;
    $dias_uti_frac = (30 / 12) * $meses_fraccion;

    $monto_vac_frac = $dias_vac_frac * $salario_diario_basico;
    $monto_bvac_frac = $dias_bvac_frac * $salario_diario_basico;
    $monto_uti_frac = $dias_uti_frac * $salario_promedio;

    $indemnizacion_despido = ($motivo === 'Despido Injustificado') ? $monto_prestaciones : 0;

    $q_deuda = mysqli_query($conexion, "SELECT SUM(monto) as total FROM nomina_cuentas WHERE id_empleado = $id_empleado AND tipo_cuenta = 'Por Cobrar' AND estado = 'Pendiente'");
    $deuda = ($q_deuda) ? floatval(mysqli_fetch_assoc($q_deuda)['total'] ?? 0) : 0;

    $total_liquidar = ($monto_prestaciones + $monto_vac_frac + $monto_bvac_frac + $monto_uti_frac + $indemnizacion_despido) - $deuda;
    if ($total_liquidar < 0) {
        $total_liquidar = 0;
    }

    $data = [
        'tiempo_servicio' => $tiempo_servicio,
        'dias_calendario' => $total_dias_calendario,
        'dias_falta' => round($total_faltas_reales, 2),
        'dias_efectivos' => round($dias_efectivos, 2),
        'salario_mensual' => $salario_mensual,
        'salario_integral_diario' => $salario_integral_diario,
        'dias_prestaciones' => $dias_prestaciones,
        'monto_prestaciones' => $monto_prestaciones,
        'vacaciones_fraccionadas' => $monto_vac_frac,
        'bono_vacacional_fraccionado' => $monto_bvac_frac,
        'utilidades_fraccionadas' => $monto_uti_frac,
        'indemnizacion_despido' => $indemnizacion_despido,
        'deducciones_pendientes' => $deuda,
        'total_liquidar' => $total_liquidar
    ];

    enviarRespuesta(true, '', $data);
}

// =========================================================
// 4. GUARDAR LIQUIDACIÓN DEFINITIVA
// =========================================================
if ($action === 'guardar_liquidacion') {
    $id_empleado = intval($_POST['id_empleado']);
    $fecha_egreso = mysqli_real_escape_string($conexion, $_POST['fecha_egreso']);
    $motivo = mysqli_real_escape_string($conexion, $_POST['motivo']);
    
    $datos_json = json_decode($_POST['datos_json'], true);
    $dias_acumulados = intval($datos_json['dias_prestaciones'] ?? 0);
    $salario_promedio = floatval($datos_json['salario_integral_diario'] ?? 0);
    $monto_total = floatval($datos_json['total_liquidar'] ?? 0);

    mysqli_begin_transaction($conexion);

    try {
        mysqli_query($conexion, "INSERT INTO nomina_prestaciones (id_empleado, fecha_calculo, dias_acumulados, salario_promedio, monto_total, motivo, estado) VALUES ($id_empleado, '$fecha_egreso', $dias_acumulados, $salario_promedio, $monto_total, '$motivo', 'Procesado')");
        mysqli_query($conexion, "UPDATE nomina_empleados SET estado = 'Inactivo' WHERE id = $id_empleado");
        mysqli_query($conexion, "UPDATE nomina_cuentas SET estado = 'Procesado (Liquidacion)' WHERE id_empleado = $id_empleado AND estado = 'Pendiente'");

        mysqli_commit($conexion);
        enviarRespuesta(true);

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        enviarRespuesta(false, 'Error al guardar: ' . $e->getMessage());
    }
}

// =========================================================
// 5. ELIMINAR Y REVERTIR LIQUIDACIÓN
// =========================================================
if ($action === 'eliminar') {
    $id_prestacion = intval($_POST['id_prestacion']);
    
    $q = mysqli_query($conexion, "SELECT id_empleado FROM nomina_prestaciones WHERE id = $id_prestacion");
    if ($q && mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);
        $id_empleado = $row['id_empleado'];

        mysqli_begin_transaction($conexion);
        try {
            mysqli_query($conexion, "DELETE FROM nomina_prestaciones WHERE id = $id_prestacion");
            mysqli_query($conexion, "UPDATE nomina_empleados SET estado = 'Activo' WHERE id = $id_empleado");
            mysqli_query($conexion, "UPDATE nomina_cuentas SET estado = 'Pendiente' WHERE id_empleado = $id_empleado AND estado = 'Procesado (Liquidacion)'");
            mysqli_commit($conexion);
            enviarRespuesta(true);
        } catch (Exception $e) {
            mysqli_rollback($conexion);
            enviarRespuesta(false, 'Error al revertir la base de datos: ' . $e->getMessage());
        }
    } else {
        enviarRespuesta(false, 'Registro no encontrado');
    }
}

// =========================================================
// 6. ARCHIVAR LIQUIDACIÓN
// =========================================================
if ($action === 'archivar') {
    $id_prestacion = intval($_POST['id_prestacion']);
    mysqli_query($conexion, "UPDATE nomina_prestaciones SET estado = 'Archivado' WHERE id = $id_prestacion");
    enviarRespuesta(true);
}

// =========================================================
// 7. DESARCHIVAR LIQUIDACIÓN
// =========================================================
if ($action === 'desarchivar') {
    $id_prestacion = intval($_POST['id_prestacion']);
    mysqli_query($conexion, "UPDATE nomina_prestaciones SET estado = 'Procesado' WHERE id = $id_prestacion");
    enviarRespuesta(true);
}

enviarRespuesta(false, 'Accion no reconocida.');
?>