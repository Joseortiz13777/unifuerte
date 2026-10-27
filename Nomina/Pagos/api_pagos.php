<?php
session_start();
error_reporting(0);

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesi��n expirada']);
    exit;
}

@mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS nomina_lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    fecha_pago DATE NOT NULL,
    tasa_bcv DECIMAL(15,4) DEFAULT 0.00,
    total_usd DECIMAL(15,2) DEFAULT 0.00,
    total_bs DECIMAL(15,2) DEFAULT 0.00,
    estado VARCHAR(20) DEFAULT 'Pendiente',
    comprobante_foto VARCHAR(255),
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

@mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS asistencia (
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
  fecha_registro datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

@mysqli_query($conexion, "ALTER TABLE asistencia MODIFY COLUMN estado varchar(30) DEFAULT 'Presente';");
@mysqli_query($conexion, "ALTER TABLE asistencia ADD COLUMN IF NOT EXISTS minutos_extra int(11) DEFAULT 0 AFTER minutos_retardo;");
@mysqli_query($conexion, "ALTER TABLE asistencia ADD COLUMN IF NOT EXISTS es_feriado tinyint(1) DEFAULT 0 AFTER minutos_extra;");
@mysqli_query($conexion, "ALTER TABLE asistencia ADD COLUMN IF NOT EXISTS pago_doble tinyint(1) DEFAULT 0 AFTER es_feriado;");
@mysqli_query($conexion, "ALTER TABLE nomina_cuentas ADD COLUMN IF NOT EXISTS id_lote_pago INT DEFAULT 0;");

function convertir_hora_nomina_a_minutos($hora) {
    if (!$hora || !preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', trim((string)$hora), $partes)) {
        return null;
    }
    return intval($partes[1]) * 60 + intval($partes[2]);
}

const VALOR_HORA_NOMINA = 2.88;

function calcular_montos_nomina(array $row): array {
    $sueldo_quincena = floatval($row['sueldo_quincena'] ?? 0);
    $cesta_ticket = floatval($row['cesta_ticket'] ?? 0);
    $bono_ayuda = floatval($row['bono_ayuda'] ?? 0);
    $qty_sabados = floatval($row['qty_sabados'] ?? 0);
    $qty_he_diurna = floatval($row['qty_he_diurna'] ?? 0);
    $qty_he_noct = floatval($row['qty_he_noct'] ?? 0);
    $bono_prod = floatval($row['bono_prod'] ?? 0);
    $pago_pendiente = floatval($row['pago_pendiente'] ?? 0);
    $dias_falta = floatval($row['dias_falta'] ?? 0);
    $horas_falta = floatval($row['horas_falta'] ?? 0);
    $vales = floatval($row['vales'] ?? 0);

    $salario_diario = round($sueldo_quincena / 30, 4);
    $total_percibir = round($sueldo_quincena + $cesta_ticket + $bono_ayuda, 2);
    $valor_sabados = round($qty_sabados * $salario_diario, 2);
    $valor_he_diurna = round($qty_he_diurna * VALOR_HORA_NOMINA, 2);
    $valor_he_noct = round($qty_he_noct * VALOR_HORA_NOMINA, 2);
    $total_asignaciones = round($total_percibir + $valor_sabados + $valor_he_diurna + $valor_he_noct + $bono_prod + $pago_pendiente, 2);
    $monto_descuento_dias = round($dias_falta * $salario_diario, 2);
    $monto_descuento_horas = round($horas_falta * VALOR_HORA_NOMINA, 2);
    $total_deducciones = round($monto_descuento_dias + $monto_descuento_horas + $vales, 2);

    return array_merge($row, [
        'sueldo_quincena' => $sueldo_quincena,
        'cesta_ticket' => $cesta_ticket,
        'bono_ayuda' => $bono_ayuda,
        'total_percibir' => $total_percibir,
        'salario_diario' => $salario_diario,
        'valor_hora' => VALOR_HORA_NOMINA,
        'qty_sabados' => $qty_sabados,
        'valor_sabados' => $valor_sabados,
        'qty_he_diurna' => $qty_he_diurna,
        'valor_he_diurna' => $valor_he_diurna,
        'qty_he_noct' => $qty_he_noct,
        'valor_he_noct' => $valor_he_noct,
        'bono_prod' => $bono_prod,
        'pago_pendiente' => $pago_pendiente,
        'total_asignaciones' => $total_asignaciones,
        'dias_falta' => $dias_falta,
        'horas_falta' => $horas_falta,
        'monto_descuento_dias' => $monto_descuento_dias,
        'monto_descuento_horas' => $monto_descuento_horas,
        'vales' => $vales,
        'total_deducciones' => $total_deducciones,
        'total_pagar' => max(0, round($total_asignaciones - $total_deducciones, 2))
    ]);
}

/**
 * Calcula las faltas, feriados trabajados, horas de retardo/falta y horas extras
 * evaluando tanto las columnas minutos_retardo/minutos_extra como las horas de entrada/salida.
 */
function obtener_resumen_asistencia_periodo($conexion, $id_emp, $horario, $f_inicio, $f_fin) {
    $id_emp = intval($id_emp);
    $horario = intval($horario) > 0 ? intval($horario) : 1;

    $dias_falta = 0.0;
    $dias_feriados_trabajados = 0.0;
    $total_min_retardo = 0;
    $total_min_extra = 0;

    $q = mysqli_query($conexion, "SELECT * FROM asistencia WHERE id_empleado = $id_emp AND fecha BETWEEN '$f_inicio' AND '$f_fin'");
    if (!$q) {
        return [0.0, 0.0, 0.0, 0.0];
    }

    while ($row = mysqli_fetch_assoc($q)) {
        $estado = trim($row['estado'] ?? 'Presente');
        $pago_doble = intval($row['pago_doble'] ?? 0);
        $es_feriado = intval($row['es_feriado'] ?? 0);
        $fecha_dia = $row['fecha'];

        if ($estado === 'Falta') {
            $dias_falta += 1.0;
            continue;
        }

        if ($estado === 'Feriado' || ($pago_doble === 1 && $estado !== 'Falta Feriado' && $estado !== 'Permiso')) {
            $dias_feriados_trabajados += 1.0;
            continue;
        }

        if ($estado === 'Presente' && $es_feriado === 0) {
            $min_ret_bd = max(0, intval($row['minutos_retardo'] ?? 0));
            $min_ext_bd = max(0, intval($row['minutos_extra'] ?? 0));

            // Compensar tardanzas con el tiempo extra; usar los minutos guardados solo si faltan horas.
            $inicio_descuento = ($horario === 2) ? 480 : 450; // H2: 8:00 AM | H1 y H3: 7:30 AM
            $hora_fin = ($horario === 2) ? 720 : (($horario === 3) ? 840 : 960); // H2: 12:00 PM | H3: 2:00 PM | H1: 4:00 PM
            if ($horario === 1 && date('N', strtotime($fecha_dia)) === '5') {
                $hora_fin = 900; // Viernes H1 sale a las 3:00 PM
            }

            $m_ent = convertir_hora_nomina_a_minutos($row['hora_entrada'] ?? '');
            $m_sal = convertir_hora_nomina_a_minutos($row['hora_salida'] ?? '');
            if ($m_ent === null && $m_sal === null) {
                $total_min_retardo += $min_ret_bd;
                $total_min_extra += $min_ext_bd;
                continue;
            }

            $minutos_llegada_tarde = $m_ent !== null ? max(0, $m_ent - $inicio_descuento) : 0;
            $minutos_salida_temprana = $m_sal !== null ? max(0, $hora_fin - $m_sal) : 0;
            $minutos_extra_salida = $m_sal !== null ? max(0, $m_sal - $hora_fin) : 0;
            $minutos_por_compensar = $minutos_llegada_tarde + $minutos_salida_temprana;

            $total_min_retardo += max(0, $minutos_por_compensar - $minutos_extra_salida);
            $total_min_extra += max(0, $minutos_extra_salida - $minutos_por_compensar);
        }
    }

    $horas_falta = round($total_min_retardo / 60, 2);
    $horas_extras = round($total_min_extra / 60, 2);

    return [$dias_falta, $dias_feriados_trabajados, $horas_falta, $horas_extras];
}

$action = $_POST['action'] ?? '';

if ($action === 'generar_previa') {
    $q_emp = mysqli_query($conexion, "SELECT * FROM nomina_empleados WHERE estado = 'Activo' ORDER BY cargo ASC, nombre ASC");
    $nomina = [];

    $f_inicio = mysqli_real_escape_string($conexion, $_POST['fecha_inicio'] ?? date('Y-m-01'));
    $f_fin = mysqli_real_escape_string($conexion, $_POST['fecha_fin'] ?? date('Y-m-t'));
    $id_lote_edit = intval($_POST['id_lote_edit'] ?? 0);

    while ($emp = mysqli_fetch_assoc($q_emp)) {
        $id_emp = intval($emp['id']);
        $horario = intval($emp['horario'] ?? 1);

        $salario_base = floatval($emp['salario_base']);
        $cesta_ticket = floatval($emp['cesta_ticket']);
        $bono_ayuda = floatval($emp['bonos']);

        // Vales Por Cobrar (Descuentos al trabajador) pendientes o asociados al lote en edici��n
        $cond_lote = $id_lote_edit > 0
            ? "(estado = 'Pendiente' OR id_lote_pago = $id_lote_edit)"
            : "estado = 'Pendiente'";

        $q_cobrar = mysqli_query($conexion, "SELECT SUM(monto) as total_deuda FROM nomina_cuentas WHERE id_empleado = $id_emp AND tipo_cuenta = 'Por Cobrar' AND $cond_lote");
        $r_cobrar = $q_cobrar ? mysqli_fetch_assoc($q_cobrar) : null;
        $vales = round(floatval($r_cobrar['total_deuda'] ?? 0), 2);

        // Deudas de N��mina Por Pagar (Lo que se le debe al trabajador en USD)
        $q_pagar = mysqli_query($conexion, "SELECT SUM(monto) as total_extra FROM nomina_cuentas WHERE id_empleado = $id_emp AND tipo_cuenta = 'Por Pagar' AND $cond_lote");
        $r_pagar = $q_pagar ? mysqli_fetch_assoc($q_pagar) : null;
        $pago_pendiente = round(floatval($r_pagar['total_extra'] ?? 0), 2);

        // Resumen exacto de Asistencia en el rango seleccionado
        [$dias_falta_auto, $dias_feriados_trabajados, $horas_falta_auto, $horas_extras_auto] = obtener_resumen_asistencia_periodo(
            $conexion,
            $id_emp,
            $horario,
            $f_inicio,
            $f_fin
        );

        // C��lculos base iniciales para que nunca viajen en 0.00
        $s_diario = round($salario_base / 30, 4);
        $s_hora = VALOR_HORA_NOMINA;
        $percibir = round($salario_base + $cesta_ticket + $bono_ayuda, 2);
        $val_sabados = round($dias_feriados_trabajados * $s_diario, 2);
        $val_he_diurna = round($horas_extras_auto * VALOR_HORA_NOMINA, 2);
        $asig_total = round($percibir + $val_sabados + $val_he_diurna + $pago_pendiente, 2);
        $desc_dias = round($dias_falta_auto * $s_diario, 2);
        $desc_horas = round($horas_falta_auto * VALOR_HORA_NOMINA, 2);
        $deduc_total = round($desc_dias + $desc_horas + $vales, 2);
        $total_pagar = max(0, round($asig_total - $deduc_total, 2));

        $ced_limpia = "V-" . ltrim(preg_replace('/^[VEJGC]\s*-\s*/i', '', trim($emp['cedula'])));

        $nomina[] = [
            'id_empleado' => $id_emp,
            'cedula' => $ced_limpia,
            'nombre_completo' => mb_strtoupper($emp['nombre'] . ' ' . $emp['apellido'], 'UTF-8'),
            'cargo' => mb_strtoupper($emp['cargo'] ?: 'SIN CARGO', 'UTF-8'),

            'salario_base' => $salario_base,
            'sueldo_quincena' => $salario_base,
            'cesta_ticket' => $cesta_ticket,
            'bono_ayuda' => $bono_ayuda,

            'total_percibir' => $percibir,
            'salario_diario' => $s_diario,
            'valor_hora' => $s_hora,

            'qty_sabados' => $dias_feriados_trabajados,
            'valor_sabados' => $val_sabados,
            'qty_he_diurna' => $horas_extras_auto,
            'valor_he_diurna' => $val_he_diurna,
            'qty_he_noct' => 0.00,
            'valor_he_noct' => 0.00,
            'bono_prod' => 0.00,
            'pago_pendiente' => $pago_pendiente,

            'total_asignaciones' => $asig_total,

            'dias_falta' => $dias_falta_auto,
            'horas_falta' => $horas_falta_auto,
            'monto_descuento_dias' => $desc_dias,
            'monto_descuento_horas' => $desc_horas,

            'vales' => $vales,
            'total_deducciones' => $deduc_total,
            'total_pagar' => $total_pagar
        ];
    }
    echo json_encode(['success' => true, 'data' => $nomina]);
    exit;
}

if ($action === 'procesar_nomina') {
    $id_lote_edit = intval($_POST['id_lote_edit'] ?? 0);
    $fecha_inicio = mysqli_real_escape_string($conexion, $_POST['fecha_inicio'] ?? '');
    $fecha_fin = mysqli_real_escape_string($conexion, $_POST['fecha_fin'] ?? '');
    $fecha_pago = mysqli_real_escape_string($conexion, $_POST['fecha_pago'] ?? '');
    $tasa_bcv = floatval($_POST['tasa_bcv'] ?? 0);
    $datos_nomina = json_decode($_POST['datos_nomina'] ?? '[]', true);

    if (!is_array($datos_nomina) || empty($datos_nomina)) {
        echo json_encode(['success' => false, 'msg' => 'No se recibieron datos v��lidos de la n��mina.']);
        exit;
    }

    foreach ($datos_nomina as $indice => $row_nomina) {
        if (!is_array($row_nomina) || intval($row_nomina['id_empleado'] ?? 0) <= 0) {
            echo json_encode(['success' => false, 'msg' => 'La nómina contiene una fila de empleado inválida.']);
            exit;
        }
        $datos_nomina[$indice] = calcular_montos_nomina($row_nomina);
    }

    mysqli_begin_transaction($conexion);
    try {
        $total_usd_lote = 0;
        foreach ($datos_nomina as $row) {
            $total_usd_lote += floatval($row['total_pagar'] ?? $row['total_pagado'] ?? 0);
        }
        $total_bs_lote = round($total_usd_lote * $tasa_bcv, 2);

        if ($id_lote_edit > 0) {
            $sql_lote = "UPDATE nomina_lotes SET fecha_inicio='$fecha_inicio', fecha_fin='$fecha_fin', fecha_pago='$fecha_pago', tasa_bcv=$tasa_bcv, total_usd=$total_usd_lote, total_bs=$total_bs_lote WHERE id=$id_lote_edit";
            mysqli_query($conexion, $sql_lote);
            $id_lote = $id_lote_edit;
        } else {
            $sql_lote = "INSERT INTO nomina_lotes (fecha_inicio, fecha_fin, fecha_pago, tasa_bcv, total_usd, total_bs, estado) VALUES ('$fecha_inicio', '$fecha_fin', '$fecha_pago', $tasa_bcv, $total_usd_lote, $total_bs_lote, 'Pendiente')";
            mysqli_query($conexion, $sql_lote);
            $id_lote = mysqli_insert_id($conexion);
        }

        foreach ($datos_nomina as $row) {
            $id_emp = intval($row['id_empleado']);
            $sueldo_quincena = floatval($row['sueldo_quincena'] ?? 0);
            $cesta_ticket = floatval($row['cesta_ticket'] ?? 0);
            $bono_ayuda = floatval($row['bono_ayuda'] ?? 0);
            $total_percibir = floatval($row['total_percibir'] ?? 0);
            $salario_diario = floatval($row['salario_diario'] ?? 0);
            $valor_hora = floatval($row['valor_hora'] ?? 0);

            $qty_sabados = floatval($row['qty_sabados'] ?? 0);
            $valor_sabados = floatval($row['valor_sabados'] ?? 0);
            $qty_he_diurna = floatval($row['qty_he_diurna'] ?? 0);
            $valor_he_diurna = floatval($row['valor_he_diurna'] ?? 0);
            $qty_he_noct = floatval($row['qty_he_noct'] ?? 0);
            $valor_he_noct = floatval($row['valor_he_noct'] ?? 0);
            $bono_prod = floatval($row['bono_prod'] ?? 0);
            $pago_pendiente = floatval($row['pago_pendiente'] ?? 0);
            $total_asignaciones = floatval($row['total_asignaciones'] ?? 0);

            $dias_falta = floatval($row['dias_falta'] ?? 0);
            $horas_falta = floatval($row['horas_falta'] ?? 0);
            $monto_descuento_dias = floatval($row['monto_descuento_dias'] ?? 0);
            $monto_descuento_horas = floatval($row['monto_descuento_horas'] ?? 0);
            $vales = floatval($row['vales'] ?? 0);
            $total_deducciones = floatval($row['total_deducciones'] ?? 0);

            $total_pagar = floatval($row['total_pagar'] ?? $row['total_pagado'] ?? 0);
            $pago_bs = round($total_pagar * $tasa_bcv, 2);

            $asignaciones_extras_resumen = round($valor_sabados + $valor_he_diurna + $valor_he_noct + $bono_prod + $pago_pendiente, 2);

            if ($id_lote_edit > 0) {
                $q_check = mysqli_query($conexion, "SELECT id FROM nomina_pagos WHERE id_lote=$id_lote AND id_empleado=$id_emp");
                if ($q_check && mysqli_num_rows($q_check) > 0) {
                    $sql_pago = "UPDATE nomina_pagos SET fecha_inicio_periodo='$fecha_inicio', fecha_fin_periodo='$fecha_fin', fecha_pago='$fecha_pago', salario_base_periodo=$total_percibir, asignaciones_extras=$asignaciones_extras_resumen, deducciones=$total_deducciones, sueldo_quincena=$sueldo_quincena, cesta_ticket=$cesta_ticket, bono_ayuda=$bono_ayuda, total_percibir=$total_percibir, salario_diario=$salario_diario, valor_hora=$valor_hora, qty_sabados=$qty_sabados, valor_sabados=$valor_sabados, qty_he_diurna=$qty_he_diurna, valor_he_diurna=$valor_he_diurna, qty_he_noct=$qty_he_noct, valor_he_noct=$valor_he_noct, bono_prod=$bono_prod, pago_pendiente=$pago_pendiente, total_asignaciones=$total_asignaciones, dias_falta=$dias_falta, horas_falta=$horas_falta, monto_descuento_dias=$monto_descuento_dias, monto_descuento_horas=$monto_descuento_horas, vales=$vales, total_deducciones=$total_deducciones, total_pagado=$total_pagar, pago_bs=$pago_bs, tasa_bcv=$tasa_bcv WHERE id_lote=$id_lote AND id_empleado=$id_emp";
                } else {
                    $sql_pago = "INSERT INTO nomina_pagos (id_lote, id_empleado, fecha_inicio_periodo, fecha_fin_periodo, fecha_pago, salario_base_periodo, asignaciones_extras, deducciones, sueldo_quincena, cesta_ticket, bono_ayuda, total_percibir, salario_diario, valor_hora, qty_sabados, valor_sabados, qty_he_diurna, valor_he_diurna, qty_he_noct, valor_he_noct, bono_prod, pago_pendiente, total_asignaciones, dias_falta, horas_falta, monto_descuento_dias, monto_descuento_horas, vales, total_deducciones, total_pagado, pago_bs, tasa_bcv, estado) VALUES ($id_lote, $id_emp, '$fecha_inicio', '$fecha_fin', '$fecha_pago', $total_percibir, $asignaciones_extras_resumen, $total_deducciones, $sueldo_quincena, $cesta_ticket, $bono_ayuda, $total_percibir, $salario_diario, $valor_hora, $qty_sabados, $valor_sabados, $qty_he_diurna, $valor_he_diurna, $qty_he_noct, $valor_he_noct, $bono_prod, $pago_pendiente, $total_asignaciones, $dias_falta, $horas_falta, $monto_descuento_dias, $monto_descuento_horas, $vales, $total_deducciones, $total_pagar, $pago_bs, $tasa_bcv, 'Pagado')";
                }
            } else {
                $sql_pago = "INSERT INTO nomina_pagos (id_lote, id_empleado, fecha_inicio_periodo, fecha_fin_periodo, fecha_pago, salario_base_periodo, asignaciones_extras, deducciones, sueldo_quincena, cesta_ticket, bono_ayuda, total_percibir, salario_diario, valor_hora, qty_sabados, valor_sabados, qty_he_diurna, valor_he_diurna, qty_he_noct, valor_he_noct, bono_prod, pago_pendiente, total_asignaciones, dias_falta, horas_falta, monto_descuento_dias, monto_descuento_horas, vales, total_deducciones, total_pagado, pago_bs, tasa_bcv, estado) VALUES ($id_lote, $id_emp, '$fecha_inicio', '$fecha_fin', '$fecha_pago', $total_percibir, $asignaciones_extras_resumen, $total_deducciones, $sueldo_quincena, $cesta_ticket, $bono_ayuda, $total_percibir, $salario_diario, $valor_hora, $qty_sabados, $valor_sabados, $qty_he_diurna, $valor_he_diurna, $qty_he_noct, $valor_he_noct, $bono_prod, $pago_pendiente, $total_asignaciones, $dias_falta, $horas_falta, $monto_descuento_dias, $monto_descuento_horas, $vales, $total_deducciones, $total_pagar, $pago_bs, $tasa_bcv, 'Pagado')";
            }
            mysqli_query($conexion, $sql_pago);

            // Marcar vales y deudas como Procesado y enlazarlos a este lote por si se elimina o edita despu��s
            mysqli_query($conexion, "UPDATE nomina_cuentas SET estado = 'Procesado', id_lote_pago = $id_lote WHERE id_empleado = $id_emp AND estado = 'Pendiente'");
        }
        mysqli_commit($conexion);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        mysqli_rollback($conexion); 
        echo json_encode(['success' => false, 'msg' => 'Error al guardar: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'listar_historial_lotes') {
    $q = mysqli_query($conexion, "SELECT * FROM nomina_lotes ORDER BY id DESC");
    $data = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $r['fecha_pago_fmt'] = date('d/m/Y', strtotime($r['fecha_pago']));
        $r['periodo_fmt'] = date('d/m/Y', strtotime($r['fecha_inicio'])) . ' al ' . date('d/m/Y', strtotime($r['fecha_fin']));
        $data[] = $r;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($action === 'cargar_lote_editar') {
    $id_lote = intval($_POST['id_lote']);
    $q_lote = mysqli_query($conexion, "SELECT * FROM nomina_lotes WHERE id = $id_lote");
    $lote = mysqli_fetch_assoc($q_lote);
    $q_pagos = mysqli_query($conexion, "SELECT p.*, e.nombre, e.apellido, e.cedula, e.cargo, e.salario_base FROM nomina_pagos p INNER JOIN nomina_empleados e ON p.id_empleado = e.id WHERE p.id_lote = $id_lote ORDER BY e.cargo ASC, e.nombre ASC");
    $nomina = [];
    while ($p = mysqli_fetch_assoc($q_pagos)) {
        $id_emp = intval($p['id_empleado']);
        $ced_limpia = "V-" . ltrim(preg_replace('/^[VEJGC]\s*-\s*/i', '', trim($p['cedula'])));
        $p['cedula'] = $ced_limpia;
        $p['nombre_completo'] = mb_strtoupper($p['nombre'] . ' ' . $p['apellido'], 'UTF-8');
        $p['cargo'] = mb_strtoupper($p['cargo'] ?: 'SIN CARGO', 'UTF-8');

        // Sumar tambi��n si se agregaron nuevos vales o deudas en estado 'Pendiente' despu��s de crear el lote
        $q_val_nuevos = mysqli_query($conexion, "SELECT SUM(monto) as m FROM nomina_cuentas WHERE id_empleado = $id_emp AND tipo_cuenta = 'Por Cobrar' AND estado = 'Pendiente'");
        $r_val_nuevos = $q_val_nuevos ? mysqli_fetch_assoc($q_val_nuevos) : null;
        $p['vales'] = round(floatval($p['vales']) + floatval($r_val_nuevos['m'] ?? 0), 2);

        $q_deu_nuevas = mysqli_query($conexion, "SELECT SUM(monto) as m FROM nomina_cuentas WHERE id_empleado = $id_emp AND tipo_cuenta = 'Por Pagar' AND estado = 'Pendiente'");
        $r_deu_nuevas = $q_deu_nuevas ? mysqli_fetch_assoc($q_deu_nuevas) : null;
        $p['pago_pendiente'] = round(floatval($p['pago_pendiente']) + floatval($r_deu_nuevas['m'] ?? 0), 2);

        $p['total_pagar'] = floatval($p['total_pagado'] ?? 0);
        $nomina[] = $p;
    }
    echo json_encode(['success' => true, 'lote' => $lote, 'data' => $nomina]);
    exit;
}

if ($action === 'listar_detalles_lote') {
    $id_lote = intval($_POST['id_lote']);
    $q = mysqli_query($conexion, "SELECT p.*, e.nombre, e.apellido, e.cedula FROM nomina_pagos p INNER JOIN nomina_empleados e ON p.id_empleado = e.id WHERE p.id_lote = $id_lote ORDER BY e.nombre ASC");
    $data = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $r['cedula'] = "V-" . ltrim(preg_replace('/^[VEJGC]\s*-\s*/i', '', trim($r['cedula'])));
        $data[] = $r;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($action === 'eliminar_lote') {
    $id_lote = intval($_POST['id_lote']);
    // Revertir a 'Pendiente' los vales y deudas que hab��an sido procesados en este lote
    mysqli_query($conexion, "UPDATE nomina_cuentas SET estado = 'Pendiente', id_lote_pago = 0 WHERE id_lote_pago = $id_lote");
    mysqli_query($conexion, "DELETE FROM nomina_pagos WHERE id_lote = $id_lote");
    mysqli_query($conexion, "DELETE FROM nomina_lotes WHERE id = $id_lote");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'finalizar_lote') {
    $id_lote = intval($_POST['id_lote']);
    mysqli_query($conexion, "UPDATE nomina_lotes SET estado = 'Finalizada' WHERE id = $id_lote");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'subir_comprobante') {
    $id_pago = intval($_POST['id_pago']);
    $q_exist = mysqli_query($conexion, "SELECT comprobante_foto FROM nomina_pagos WHERE id=$id_pago");
    $row_exist = mysqli_fetch_assoc($q_exist);
    $existentes = (!empty($row_exist['comprobante_foto'])) ? explode(',', $row_exist['comprobante_foto']) : [];
    $nuevos_archivos = [];
    if (isset($_FILES['comprobantes'])) {
        for($i = 0; $i < count($_FILES['comprobantes']['name']); $i++) {
            if ($_FILES['comprobantes']['error'][$i] == 0) {
                $filename = 'aval_empleado_' . $id_pago . '_' . time() . '_' . $i . '.' . pathinfo($_FILES['comprobantes']['name'][$i], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['comprobantes']['tmp_name'][$i], '../../uploads/' . $filename)) {
                    $nuevos_archivos[] = $filename;
                }
            }
        }
    }
    if (!empty($nuevos_archivos)) {
        mysqli_query($conexion, "UPDATE nomina_pagos SET comprobante_foto='" . implode(',', array_merge($existentes, $nuevos_archivos)) . "' WHERE id=$id_pago");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'No se subi�� archivo']);
    }
    exit;
}

if ($action === 'limpiar_comprobantes') {
    $id_pago = intval($_POST['id_pago']);
    mysqli_query($conexion, "UPDATE nomina_pagos SET comprobante_foto=NULL WHERE id=$id_pago");
    echo json_encode(['success' => true]);
    exit;
}
?>