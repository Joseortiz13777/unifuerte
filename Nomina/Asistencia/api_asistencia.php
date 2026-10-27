<?php
session_start();
error_reporting(0);

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

require_once '../../conexion.php';

function convertir_hora_a_minutos($hora) {
    $hora = trim((string)$hora);
    if ($hora === '') return null;

    // Formato 24 horas: HH:MM o HH:MM:SS
    if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', $hora, $partes)) {
        return intval($partes[1]) * 60 + intval($partes[2]);
    }

    // Formato 12 horas: H:MM AM/PM
    if (preg_match('/^(1[0-2]|[1-9]):([0-5]\d)\s*(AM|PM)$/i', $hora, $partes)) {
        $h = intval($partes[1]) % 12;
        if (strtoupper($partes[3]) === 'PM') {
            $h += 12;
        }
        return $h * 60 + intval($partes[2]);
    }

    return null;
}

function normalizar_hora_sql($conexion, $hora) {
    $minutos = convertir_hora_a_minutos($hora);
    if ($minutos === null) {
        return "NULL";
    }
    $h = str_pad((string)floor($minutos / 60), 2, '0', STR_PAD_LEFT);
    $m = str_pad((string)($minutos % 60), 2, '0', STR_PAD_LEFT);
    return "'" . mysqli_real_escape_string($conexion, "$h:$m:00") . "'";
}

function calcular_tiempos_asistencia($entrada, $salida, $horario, $fecha) {
    $horario = intval($horario) > 0 ? intval($horario) : 1;
    $inicio_descuento = ($horario === 2) ? 480 : 450; // H2: 8:00 AM | H1 y H3: 7:30 AM
    $hora_fin = ($horario === 2) ? 720 : (($horario === 3) ? 840 : 960); // H2: 12:00 PM | H3: 2:00 PM | H1: 4:00 PM
    if ($horario === 1 && date('N', strtotime($fecha)) === '5') {
        $hora_fin = 900; // Viernes H1 sale a las 3:00 PM
    }
    $minutos_entrada = convertir_hora_a_minutos($entrada);
    $minutos_salida = convertir_hora_a_minutos($salida);
    $minutos_llegada_tarde = $minutos_entrada !== null
        ? max(0, $minutos_entrada - $inicio_descuento)
        : 0;
    $minutos_salida_temprana = $minutos_salida !== null
        ? max(0, $hora_fin - $minutos_salida)
        : 0;
    $minutos_extra_salida = $minutos_salida !== null
        ? max(0, $minutos_salida - $hora_fin)
        : 0;
    $minutos_por_compensar = $minutos_llegada_tarde + $minutos_salida_temprana;
    $minutos_retardo = max(0, $minutos_por_compensar - $minutos_extra_salida);
    $minutos_extra = max(0, $minutos_extra_salida - $minutos_por_compensar);

    return [$minutos_retardo, $minutos_extra];
}

function guardar_registro_asistencia_empleado($conexion, $fecha, $dia_feriado_global, $reg) {
    $id_emp = intval($reg['id_empleado'] ?? 0);
    if ($id_emp <= 0) {
        throw new Exception('ID de empleado inv��lido.');
    }

    $estado_recibido = trim($reg['estado'] ?? 'Presente');
    $es_feriado_reg = isset($reg['es_feriado']) ? intval($reg['es_feriado']) : 0;
    $es_feriado_item = ($dia_feriado_global === 1 || $es_feriado_reg === 1 || in_array($estado_recibido, ['Feriado', 'Falta Feriado'], true)) ? 1 : 0;

    $entrada = "NULL";
    $salida = "NULL";
    $retardo = 0;
    $extra = 0;
    $pago_doble = 0;

    if ($es_feriado_item === 1) {
        if ($estado_recibido === 'Falta' || $estado_recibido === 'Falta Feriado') {
            $estado_final = 'Falta Feriado';
            $pago_doble = 0;
        } elseif ($estado_recibido === 'Permiso') {
            $estado_final = 'Permiso';
            $pago_doble = 0;
        } else {
            $estado_final = 'Feriado';
            $pago_doble = 1;
        }
    } else {
        $estado_final = mysqli_real_escape_string($conexion, $estado_recibido);
        if ($estado_final === 'Presente') {
            $entrada = normalizar_hora_sql($conexion, $reg['hora_entrada'] ?? '');
            $salida = normalizar_hora_sql($conexion, $reg['hora_salida'] ?? '');

            $q_empleado = mysqli_query($conexion, "SELECT horario FROM nomina_empleados WHERE id = $id_emp LIMIT 1");
            if (!$q_empleado || mysqli_num_rows($q_empleado) === 0) {
                throw new Exception('No se encontr�� el horario del empleado ' . $id_emp . '.');
            }
            $empleado = mysqli_fetch_assoc($q_empleado);
            [$retardo_calc, $extra_calc] = calcular_tiempos_asistencia(
                $reg['hora_entrada'] ?? '',
                $reg['hora_salida'] ?? '',
                $empleado['horario'],
                $fecha
            );

            $retardo = $retardo_calc;
            $extra = $extra_calc;
        }
    }

    $q_existe = mysqli_query($conexion, "SELECT id FROM asistencia WHERE id_empleado = $id_emp AND fecha = '$fecha'");

    if ($q_existe && mysqli_num_rows($q_existe) > 0) {
        $r = mysqli_fetch_assoc($q_existe);
        $id_reg = intval($r['id']);
        $sql = "UPDATE asistencia SET 
                hora_entrada = $entrada, 
                hora_salida = $salida, 
                estado = '$estado_final', 
                minutos_retardo = $retardo,
                minutos_extra = $extra,
                es_feriado = $es_feriado_item,
                pago_doble = $pago_doble
                WHERE id = $id_reg";
    } else {
        $sql = "INSERT INTO asistencia (id_empleado, fecha, hora_entrada, hora_salida, estado, minutos_retardo, minutos_extra, es_feriado, pago_doble) 
                VALUES ($id_emp, '$fecha', $entrada, $salida, '$estado_final', $retardo, $extra, $es_feriado_item, $pago_doble)";
    }

    if (!mysqli_query($conexion, $sql)) {
        throw new Exception('Error al guardar asistencia: ' . mysqli_error($conexion));
    }

    return [
        'id_empleado' => $id_emp,
        'fecha' => $fecha,
        'estado_final' => $estado_final,
        'minutos_retardo' => $retardo,
        'minutos_extra' => $extra,
        'es_feriado' => $es_feriado_item,
        'pago_doble' => $pago_doble
    ];
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesi��n expirada']);
    exit;
}

// 1. CREAR LA TABLA SI NO EXISTE
$sql_crear_tabla = "CREATE TABLE IF NOT EXISTS asistencia (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_empleado int(11) NOT NULL,
  fecha date NOT NULL,
  hora_entrada time DEFAULT NULL,
  hora_salida time DEFAULT NULL,
  estado varchar(30) DEFAULT 'Presente',
  horas_trabajadas decimal(5,2) DEFAULT 0.00,
  minutos_retardo int(11) DEFAULT 0,
  minutos_extra int(11) DEFAULT 0,
  es_feriado tinyint(1) DEFAULT 0,
  pago_doble tinyint(1) DEFAULT 0,
  fecha_registro datetime DEFAULT current_timestamp(),
  KEY id_empleado (id_empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conexion, $sql_crear_tabla);

// 2. FORZAR LA CREACI�0�7N DE LAS COLUMNAS NECESARIAS
mysqli_query($conexion, "ALTER TABLE asistencia MODIFY COLUMN estado varchar(30) DEFAULT 'Presente';");
mysqli_query($conexion, "ALTER TABLE asistencia ADD COLUMN IF NOT EXISTS minutos_extra int(11) DEFAULT 0 AFTER minutos_retardo;");
mysqli_query($conexion, "ALTER TABLE asistencia ADD COLUMN IF NOT EXISTS es_feriado tinyint(1) DEFAULT 0 AFTER minutos_extra;");
mysqli_query($conexion, "ALTER TABLE asistencia ADD COLUMN IF NOT EXISTS pago_doble tinyint(1) DEFAULT 0 AFTER es_feriado;");

// 3. ASEGURAR COLUMNA HORARIO EN EMPLEADOS
mysqli_query($conexion, "ALTER TABLE nomina_empleados ADD COLUMN IF NOT EXISTS horario INT DEFAULT 1 AFTER cargo;");

$action = $_POST['action'] ?? '';

if ($action === 'listar_dia') {
    $fecha = mysqli_real_escape_string($conexion, $_POST['fecha']);
    
    $sql = "SELECT e.id as id_empleado, e.nombre, e.apellido, e.horario, 
                   a.id as id_asistencia, a.hora_entrada, a.hora_salida, a.estado, 
                   a.minutos_retardo, a.minutos_extra, a.es_feriado, a.pago_doble 
            FROM nomina_empleados e 
            LEFT JOIN asistencia a ON e.id = a.id_empleado AND a.fecha = '$fecha'
            WHERE e.estado = 'Activo'
            ORDER BY e.nombre ASC";
            
    $q = mysqli_query($conexion, $sql);
    
    if (!$q) {
        echo json_encode(['success' => false, 'msg' => 'Error SQL: ' . mysqli_error($conexion)]);
        exit;
    }

    $data = [];
    $dia_es_feriado = 0;

    while ($r = mysqli_fetch_assoc($q)) {
        if (!empty($r['es_feriado']) || $r['estado'] === 'Feriado' || $r['estado'] === 'Falta Feriado') {
            $dia_es_feriado = 1;
        }

        $r['guardado_bd'] = !empty($r['id_asistencia']) ? 1 : 0;

        if (empty($r['estado'])) {
            $r['estado'] = 'Presente';
            $r['estado_vista'] = 'Presente';
            $r['hora_entrada'] = '';
            $r['hora_salida'] = '';
            $r['minutos_retardo'] = 0;
            $r['minutos_extra'] = 0;
            $r['es_feriado'] = 0;
            $r['pago_doble'] = 0;
        } else {
            if ($r['estado'] === 'Falta Feriado') {
                $r['estado_vista'] = 'Falta';
            } elseif ($r['estado'] === 'Feriado') {
                $r['estado_vista'] = 'Feriado';
            } else {
                $r['estado_vista'] = $r['estado'];
            }
            $r['es_feriado'] = intval($r['es_feriado'] ?? 0);
            $r['pago_doble'] = intval($r['pago_doble'] ?? 0);
        }
        $r['horario'] = intval($r['horario']);
        $data[] = $r;
    }
    echo json_encode([
        'success' => true, 
        'es_feriado' => $dia_es_feriado,
        'data' => $data
    ]);
    exit;
}

if ($action === 'listar_rango_empleado') {
    $id_emp = intval($_POST['id_empleado'] ?? 0);
    $fecha_inicio = mysqli_real_escape_string($conexion, $_POST['fecha_inicio'] ?? '');
    $fecha_fin = mysqli_real_escape_string($conexion, $_POST['fecha_fin'] ?? '');

    if ($id_emp <= 0 || empty($fecha_inicio) || empty($fecha_fin) || $fecha_inicio > $fecha_fin) {
        echo json_encode(['success' => false, 'msg' => 'Rango de fechas o empleado inv��lido']);
        exit;
    }

    $q_emp = mysqli_query($conexion, "SELECT id, nombre, apellido, horario, cargo FROM nomina_empleados WHERE id = $id_emp LIMIT 1");
    if (!$q_emp || mysqli_num_rows($q_emp) === 0) {
        echo json_encode(['success' => false, 'msg' => 'Empleado no encontrado']);
        exit;
    }
    $emp_info = mysqli_fetch_assoc($q_emp);
    $emp_info['horario'] = intval($emp_info['horario']);

    $mapa_asistencia = [];
    $q_asis = mysqli_query($conexion, "SELECT * FROM asistencia WHERE id_empleado = $id_emp AND fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'");
    if ($q_asis) {
        while ($row = mysqli_fetch_assoc($q_asis)) {
            $mapa_asistencia[$row['fecha']] = $row;
        }
    }

    $mapa_feriados_globales = [];
    $q_fer = mysqli_query($conexion, "SELECT DISTINCT fecha FROM asistencia WHERE (es_feriado = 1 OR estado IN ('Feriado', 'Falta Feriado')) AND fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'");
    if ($q_fer) {
        while ($rf = mysqli_fetch_assoc($q_fer)) {
            $mapa_feriados_globales[$rf['fecha']] = 1;
        }
    }

    $ts_actual = strtotime($fecha_inicio . ' 12:00:00');
    $ts_fin = strtotime($fecha_fin . ' 12:00:00');
    $max_dias = 90;
    $contador = 0;
    $dias_rango = [];

    while ($ts_actual <= $ts_fin && $contador < $max_dias) {
        $f_str = date('Y-m-d', $ts_actual);
        $dia_num = intval(date('N', $ts_actual));
        $es_fin_semana = ($dia_num >= 6) ? 1 : 0;

        if (isset($mapa_asistencia[$f_str])) {
            $reg = $mapa_asistencia[$f_str];
            $estado_bd = $reg['estado'];
            if ($estado_bd === 'Falta Feriado') {
                $estado_vista = 'Falta';
            } elseif ($estado_bd === 'Feriado') {
                $estado_vista = 'Feriado';
            } else {
                $estado_vista = $estado_bd;
            }
            $dias_rango[] = [
                'fecha' => $f_str,
                'dia_semana' => $dia_num,
                'es_fin_semana' => $es_fin_semana,
                'guardado_bd' => 1,
                'id_asistencia' => intval($reg['id']),
                'estado' => $estado_bd,
                'estado_vista' => $estado_vista,
                'hora_entrada' => $reg['hora_entrada'] ?? '',
                'hora_salida' => $reg['hora_salida'] ?? '',
                'minutos_retardo' => intval($reg['minutos_retardo'] ?? 0),
                'minutos_extra' => intval($reg['minutos_extra'] ?? 0),
                'es_feriado' => intval($reg['es_feriado'] ?? 0),
                'pago_doble' => intval($reg['pago_doble'] ?? 0)
            ];
        } else {
            $es_fer_global = isset($mapa_feriados_globales[$f_str]) ? 1 : 0;
            $dias_rango[] = [
                'fecha' => $f_str,
                'dia_semana' => $dia_num,
                'es_fin_semana' => $es_fin_semana,
                'guardado_bd' => 0,
                'id_asistencia' => 0,
                'estado' => $es_fer_global ? 'Feriado' : 'Presente',
                'estado_vista' => $es_fer_global ? 'Feriado' : 'Presente',
                'hora_entrada' => '',
                'hora_salida' => '',
                'minutos_retardo' => 0,
                'minutos_extra' => 0,
                'es_feriado' => $es_fer_global,
                'pago_doble' => $es_fer_global
            ];
        }

        $ts_actual = strtotime('+1 day', $ts_actual);
        $contador++;
    }

    echo json_encode([
        'success' => true,
        'empleado' => $emp_info,
        'dias' => $dias_rango
    ]);
    exit;
}

if ($action === 'guardar_individual') {
    $fecha = mysqli_real_escape_string($conexion, $_POST['fecha'] ?? '');
    $dia_feriado_global = isset($_POST['es_feriado']) && intval($_POST['es_feriado']) === 1 ? 1 : 0;
    $registro = json_decode($_POST['registro'] ?? '', true);

    if (empty($fecha) || !is_array($registro)) {
        echo json_encode(['success' => false, 'msg' => 'Datos de empleado inv��lidos']);
        exit;
    }

    mysqli_begin_transaction($conexion);
    try {
        $resultado = guardar_registro_asistencia_empleado($conexion, $fecha, $dia_feriado_global, $registro);
        mysqli_commit($conexion);
        echo json_encode([
            'success' => true,
            'resultado' => $resultado,
            'hora_guardado' => date('h:i A')
        ]);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'guardar_rango_empleado') {
    $id_emp = intval($_POST['id_empleado'] ?? 0);
    $registros = json_decode($_POST['registros'] ?? '', true);

    if ($id_emp <= 0 || !is_array($registros)) {
        echo json_encode(['success' => false, 'msg' => 'Datos del rango inv��lidos']);
        exit;
    }

    mysqli_begin_transaction($conexion);
    try {
        foreach ($registros as $item) {
            $f_item = mysqli_real_escape_string($conexion, $item['fecha'] ?? '');
            if (empty($f_item)) continue;
            $item['id_empleado'] = $id_emp;
            $es_fer = ($item['estado'] === 'Feriado' || $item['estado'] === 'Falta Feriado' || !empty($item['es_feriado'])) ? 1 : 0;
            guardar_registro_asistencia_empleado($conexion, $f_item, $es_fer, $item);
        }
        mysqli_commit($conexion);
        echo json_encode([
            'success' => true,
            'hora_guardado' => date('h:i A')
        ]);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'guardar_lote') {
    $fecha = mysqli_real_escape_string($conexion, $_POST['fecha']);
    $dia_feriado_global = isset($_POST['es_feriado']) && intval($_POST['es_feriado']) === 1 ? 1 : 0;
    $registros = json_decode($_POST['registros'], true);

    if (!is_array($registros)) {
        echo json_encode(['success' => false, 'msg' => 'Formato de registros inv��lido']);
        exit;
    }

    mysqli_begin_transaction($conexion);
    try {
        foreach($registros as $reg) {
            guardar_registro_asistencia_empleado($conexion, $fecha, $dia_feriado_global, $reg);
        }
        mysqli_commit($conexion);
        echo json_encode([
            'success' => true,
            'hora_guardado' => date('h:i A')
        ]);
    } catch(Exception $e) {
        mysqli_rollback($conexion);
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}
?>