<?php
session_start();
error_reporting(0); // Evita que advertencias rompan la respuesta JSON

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesión expirada']);
    exit;
}

// Asegurar columnas necesarias en nomina_cuentas
@mysqli_query($conexion, "ALTER TABLE nomina_cuentas ADD COLUMN IF NOT EXISTS moneda VARCHAR(10) DEFAULT 'USD'");
@mysqli_query($conexion, "ALTER TABLE nomina_cuentas ADD COLUMN IF NOT EXISTS tasa_bcv DECIMAL(15,4) DEFAULT 0.0000");
@mysqli_query($conexion, "ALTER TABLE nomina_cuentas ADD COLUMN IF NOT EXISTS monto_original DECIMAL(15,2) DEFAULT 0.00");
@mysqli_query($conexion, "ALTER TABLE nomina_cuentas ADD COLUMN IF NOT EXISTS id_lote_pago INT DEFAULT 0");

mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS nomina_comision_abonos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_cotizacion INT NOT NULL,
        id_cuenta INT NOT NULL,
        id_empleado INT NOT NULL,
        monto DECIMAL(15,2) NOT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_comision_cuenta (id_cotizacion, id_cuenta),
        KEY idx_empleado_fecha (id_empleado, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS nomina_prestamos_vendedores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_vendedor INT NOT NULL,
        monto_original DECIMAL(15,2) NOT NULL,
        saldo_pendiente DECIMAL(15,2) NOT NULL,
        concepto VARCHAR(255) NOT NULL,
        fecha DATE NOT NULL,
        moneda VARCHAR(10) NOT NULL DEFAULT 'USD',
        tasa_bcv DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
        KEY idx_vendedor_estado (id_vendedor, estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS nomina_prestamo_vendedor_abonos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_cotizacion INT NOT NULL,
        id_prestamo INT NOT NULL,
        id_vendedor INT NOT NULL,
        monto DECIMAL(15,2) NOT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_comision_prestamo (id_cotizacion, id_prestamo),
        KEY idx_vendedor_fecha (id_vendedor, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$action = $_POST['action'] ?? '';

function requiereGestionPrestamosVendedores($conexion) {
    $idUsuario = intval($_SESSION['usuario_id'] ?? 0);
    $q = mysqli_query($conexion, "SELECT permisos FROM usuario WHERE ID = $idUsuario");
    $usuario = $q ? mysqli_fetch_assoc($q) : null;
    $permisos = explode(',', $usuario['permisos'] ?? '');
    return in_array('41', $permisos, true);
}

function validarVendedorNomina($conexion, $idVendedor) {
    $idVendedor = intval($idVendedor);
    $q = mysqli_query($conexion, "SELECT ID FROM usuario WHERE ID = $idVendedor AND LOWER(rol) IN ('ventas', 'jefe_ventas')");
    return $q && mysqli_num_rows($q) === 1;
}

if (in_array($action, [
    'listar_vendedores',
    'listar_prestamos_vendedores',
    'guardar_prestamo_vendedor',
    'obtener_prestamo_vendedor',
    'eliminar_prestamo_vendedor',
    'archivar_prestamo_vendedor'
], true) && !requiereGestionPrestamosVendedores($conexion)) {
    echo json_encode(['success' => false, 'msg' => 'No tienes permiso para gestionar préstamos de vendedores.']);
    exit;
}

if ($action === 'listar_vendedores') {
    $q = mysqli_query($conexion, "
        SELECT ID AS id, nombre, apellido, cedula, rol
        FROM usuario
        WHERE LOWER(rol) IN ('ventas', 'jefe_ventas')
        ORDER BY nombre ASC, apellido ASC
    ");
    if (!$q) {
        echo json_encode(['success' => false, 'msg' => 'No se pudieron cargar los vendedores del Portal.']);
        exit;
    }
    $vendedores = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $row['nombre_completo'] = trim($row['nombre'] . ' ' . $row['apellido']);
        $vendedores[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $vendedores]);
    exit;
}

if ($action === 'listar_prestamos_vendedores') {
    $q = mysqli_query($conexion, "
        SELECT p.*, u.nombre, u.apellido, u.cedula, u.rol,
               COALESCE((SELECT SUM(a.monto) FROM nomina_prestamo_vendedor_abonos a WHERE a.id_prestamo = p.id), 0) AS total_abonado
        FROM nomina_prestamos_vendedores p
        JOIN usuario u ON u.ID = p.id_vendedor
        WHERE p.estado != 'Archivado'
        ORDER BY p.fecha DESC, p.id DESC
    ");
    if (!$q) {
        echo json_encode(['success' => false, 'msg' => 'No se pudieron cargar los préstamos de vendedores.']);
        exit;
    }
    $prestamos = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $row['fecha_fmt'] = date('d/m/Y', strtotime($row['fecha']));
        $row['monto_original_fmt'] = ($row['moneda'] === 'BS' ? 'Bs ' : '$') . number_format((float)$row['monto_original'], 2);
        $row['total_abonado_fmt'] = number_format((float)$row['total_abonado'], 2);
        $row['saldo_pendiente_fmt'] = number_format((float)$row['saldo_pendiente'], 2);
        $prestamos[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $prestamos]);
    exit;
}

if ($action === 'guardar_prestamo_vendedor') {
    $idPrestamo = intval($_POST['id_prestamo'] ?? 0);
    $idVendedor = intval($_POST['id_vendedor'] ?? 0);
    $concepto = mysqli_real_escape_string($conexion, trim($_POST['concepto'] ?? ''));
    $fecha = mysqli_real_escape_string($conexion, trim($_POST['fecha'] ?? ''));
    $moneda = ($_POST['moneda'] ?? 'USD') === 'BS' ? 'BS' : 'USD';
    $tasa = floatval($_POST['tasa_bcv'] ?? 0);
    $montoOriginal = floatval($_POST['monto'] ?? 0);
    if ($idVendedor <= 0 || $montoOriginal <= 0 || $concepto === '' || $fecha === '') {
        echo json_encode(['success' => false, 'msg' => 'Selecciona un vendedor e indica concepto, fecha y monto válidos.']);
        exit;
    }
    if (!validarVendedorNomina($conexion, $idVendedor)) {
        echo json_encode(['success' => false, 'msg' => 'Solo puedes asignar préstamos a usuarios con rol Ventas o Jefe de Ventas.']);
        exit;
    }
    if ($moneda === 'BS' && $tasa <= 0) {
        echo json_encode(['success' => false, 'msg' => 'Indica una tasa BCV válida para un préstamo en Bolívares.']);
        exit;
    }

    if ($idPrestamo > 0) {
        $qPrestamo = mysqli_query($conexion, "SELECT saldo_pendiente FROM nomina_prestamos_vendedores WHERE id = $idPrestamo AND estado = 'Pendiente'");
        $prestamo = $qPrestamo ? mysqli_fetch_assoc($qPrestamo) : null;
        $qAbonos = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM nomina_prestamo_vendedor_abonos WHERE id_prestamo = $idPrestamo");
        $abonos = $qAbonos ? mysqli_fetch_assoc($qAbonos) : null;
        if (!$prestamo || !$abonos || (int)$abonos['total'] > 0) {
            echo json_encode(['success' => false, 'msg' => 'Solo se pueden editar préstamos pendientes que todavía no tengan abonos.']);
            exit;
        }
    }

    $montoUsd = $moneda === 'BS' ? round($montoOriginal / $tasa, 2) : round($montoOriginal, 2);
    $sql = $idPrestamo > 0
        ? "UPDATE nomina_prestamos_vendedores SET id_vendedor=$idVendedor, monto_original=$montoOriginal, saldo_pendiente=$montoUsd, concepto='$concepto', fecha='$fecha', moneda='$moneda', tasa_bcv=$tasa WHERE id=$idPrestamo AND estado='Pendiente'"
        : "INSERT INTO nomina_prestamos_vendedores (id_vendedor, monto_original, saldo_pendiente, concepto, fecha, moneda, tasa_bcv) VALUES ($idVendedor, $montoOriginal, $montoUsd, '$concepto', '$fecha', '$moneda', $tasa)";
    if (!mysqli_query($conexion, $sql)) {
        echo json_encode(['success' => false, 'msg' => 'No se pudo guardar el préstamo: ' . mysqli_error($conexion)]);
        exit;
    }
    echo json_encode(['success' => true, 'id_prestamo' => $idPrestamo > 0 ? $idPrestamo : mysqli_insert_id($conexion)]);
    exit;
}

if ($action === 'obtener_prestamo_vendedor') {
    $idPrestamo = intval($_POST['id_prestamo'] ?? 0);
    $q = mysqli_query($conexion, "SELECT * FROM nomina_prestamos_vendedores WHERE id = $idPrestamo");
    if (!$q || !($row = mysqli_fetch_assoc($q))) {
        echo json_encode(['success' => false, 'msg' => 'No se encontró el préstamo solicitado.']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $row]);
    exit;
}

if ($action === 'eliminar_prestamo_vendedor' || $action === 'archivar_prestamo_vendedor') {
    $idPrestamo = intval($_POST['id_prestamo'] ?? 0);
    $qAbonos = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM nomina_prestamo_vendedor_abonos WHERE id_prestamo = $idPrestamo");
    $abonos = $qAbonos ? mysqli_fetch_assoc($qAbonos) : null;
    if (!$abonos) {
        echo json_encode(['success' => false, 'msg' => 'No se pudo verificar el historial de abonos del préstamo.']);
        exit;
    }
    if ($action === 'eliminar_prestamo_vendedor') {
        if ((int)$abonos['total'] > 0) {
            echo json_encode(['success' => false, 'msg' => 'No se puede eliminar un préstamo que ya tiene abonos de comisión.']);
            exit;
        }
        $ok = mysqli_query($conexion, "DELETE FROM nomina_prestamos_vendedores WHERE id = $idPrestamo");
    } else {
        $ok = mysqli_query($conexion, "UPDATE nomina_prestamos_vendedores SET estado = 'Archivado' WHERE id = $idPrestamo AND saldo_pendiente = 0");
        if ($ok && mysqli_affected_rows($conexion) === 0) {
            echo json_encode(['success' => false, 'msg' => 'Solo se pueden archivar préstamos completamente pagados.']);
            exit;
        }
    }
    echo json_encode($ok ? ['success' => true] : ['success' => false, 'msg' => 'No se pudo actualizar el préstamo.']);
    exit;
}

// =========================================================================
// 1. LISTAR VALES DE NÓMINA (POR COBRAR / DESCUENTO AL TRABAJADOR)
// =========================================================================
if ($action === 'listar') {
    $q = mysqli_query($conexion, "
        SELECT c.*, e.nombre, e.apellido, e.cedula
        FROM nomina_cuentas c 
        INNER JOIN nomina_empleados e ON c.id_empleado = e.id 
        WHERE c.tipo_cuenta = 'Por Cobrar' AND c.estado != 'Archivado'
        ORDER BY FIELD(c.estado, 'Pendiente') DESC, c.fecha DESC, c.id DESC
    ");
    if (!$q) {
        echo json_encode(['success' => false, 'msg' => 'No se pudieron cargar los vales y su saldo de préstamos.']);
        exit;
    }
    $data = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $r['fecha_fmt'] = date('d/m/Y', strtotime($r['fecha']));
        $moneda = $r['moneda'] ?? 'USD';
        $monto_orig = floatval(($r['monto_original'] > 0) ? $r['monto_original'] : $r['monto']);
        $monto_usd = floatval($r['monto']);

        if ($moneda === 'BS') {
            $r['monto_mostrar'] = 'Bs ' . number_format($monto_orig, 2) . ' ($' . number_format($monto_usd, 2) . ')';
        } else {
            $r['monto_mostrar'] = '$' . number_format($monto_usd, 2);
        }

        $r['cedula'] = "V-" . ltrim(preg_replace('/^[VEJGC]\s*-\s*/i', '', trim($r['cedula'])));
        $data[] = $r;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// =========================================================================
// 2. LISTAR DEUDAS DE NÓMINA (POR PAGAR / SE LE DEBE AL TRABAJADOR EN USD)
// =========================================================================
if ($action === 'listar_deudas_nomina') {
    $q = mysqli_query($conexion, "
        SELECT c.*, e.nombre, e.apellido, e.cedula, e.cargo
        FROM nomina_cuentas c
        INNER JOIN nomina_empleados e ON c.id_empleado = e.id
        WHERE c.tipo_cuenta = 'Por Pagar' AND c.estado != 'Archivado'
        ORDER BY FIELD(c.estado, 'Pendiente') DESC, c.fecha DESC, c.id DESC
    ");
    if (!$q) {
        echo json_encode(['success' => false, 'msg' => 'No se pudieron cargar las deudas de nómina.']);
        exit;
    }
    $data = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $r['fecha_fmt'] = date('d/m/Y', strtotime($r['fecha']));
        $r['monto_usd_fmt'] = '$' . number_format(floatval($r['monto']), 2);
        $r['cedula'] = "V-" . ltrim(preg_replace('/^[VEJGC]\s*-\s*/i', '', trim($r['cedula'])));
        $data[] = $r;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// =========================================================================
// 3. GUARDAR O EDITAR DEUDA DE NÓMINA A TRABAJADOR (POR PAGAR EN USD)
// =========================================================================
if ($action === 'guardar_deuda_nomina') {
    $id_deuda = intval($_POST['id_deuda'] ?? 0);
    $id_emp = intval($_POST['id_empleado'] ?? 0);
    $concepto = mysqli_real_escape_string($conexion, trim($_POST['concepto'] ?? ''));
    $fecha = mysqli_real_escape_string($conexion, trim($_POST['fecha'] ?? date('Y-m-d')));
    $monto_usd = round(floatval($_POST['monto'] ?? 0), 2);

    if ($id_emp <= 0 || $monto_usd <= 0 || $concepto === '') {
        echo json_encode(['success' => false, 'msg' => 'Seleccione al trabajador, ingrese el motivo de la deuda y el monto en dólares ($).']);
        exit;
    }

    if ($id_deuda > 0) {
        $sql = "UPDATE nomina_cuentas
                SET id_empleado = $id_emp,
                    monto = $monto_usd,
                    concepto = '$concepto',
                    fecha = '$fecha',
                    moneda = 'USD',
                    tasa_bcv = 0,
                    monto_original = $monto_usd
                WHERE id = $id_deuda AND tipo_cuenta = 'Por Pagar'";
    } else {
        $sql = "INSERT INTO nomina_cuentas (id_empleado, tipo_cuenta, monto, concepto, fecha, estado, moneda, tasa_bcv, monto_original)
                VALUES ($id_emp, 'Por Pagar', $monto_usd, '$concepto', '$fecha', 'Pendiente', 'USD', 0, $monto_usd)";
    }

    if (mysqli_query($conexion, $sql)) {
        $id_generado = ($id_deuda > 0) ? $id_deuda : mysqli_insert_id($conexion);
        echo json_encode(['success' => true, 'id_deuda' => $id_generado]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Error al guardar la deuda de nómina: ' . mysqli_error($conexion)]);
    }
    exit;
}

// =========================================================================
// 4. CAMBIAR ESTADO DE VALE O DEUDA (REHACER A PENDIENTE O MARCAR PROCESADO)
// =========================================================================
if ($action === 'cambiar_estado_cuenta') {
    $id_cuenta = intval($_POST['id_cuenta'] ?? 0);
    $nuevo_estado = ($_POST['estado'] ?? 'Pendiente') === 'Procesado' ? 'Procesado' : 'Pendiente';
    if ($id_cuenta <= 0) {
        echo json_encode(['success' => false, 'msg' => 'ID inválido']);
        exit;
    }
    if (mysqli_query($conexion, "UPDATE nomina_cuentas SET estado = '$nuevo_estado' WHERE id = $id_cuenta")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'No se pudo cambiar el estado.']);
    }
    exit;
}

// =========================================================================
// 5. GUARDAR O EDITAR VALE (POR COBRAR)
// =========================================================================
if ($action === 'guardar') {
    $id_vale = intval($_POST['id_vale'] ?? 0);
    $id_emp = intval($_POST['id_empleado'] ?? 0);
    $concepto = mysqli_real_escape_string($conexion, trim($_POST['concepto'] ?? ''));
    $fecha = mysqli_real_escape_string($conexion, trim($_POST['fecha'] ?? ''));
    $moneda = mysqli_real_escape_string($conexion, trim($_POST['moneda'] ?? 'USD'));
    $tasa_bcv = floatval($_POST['tasa_bcv'] ?? 0);
    $monto_original = floatval($_POST['monto'] ?? 0);
    
    if ($id_emp <= 0 || $monto_original <= 0) {
        echo json_encode(['success' => false, 'msg' => 'Debe seleccionar un empleado e indicar un monto válido.']);
        exit;
    }

    if ($moneda === 'BS' && $tasa_bcv <= 0) {
        echo json_encode(['success' => false, 'msg' => 'Debe ingresar una Tasa BCV válida para préstamos en Bolívares.']);
        exit;
    }

    $monto_usd = $monto_original;
    if ($moneda === 'BS' && $tasa_bcv > 0) {
        $monto_usd = round($monto_original / $tasa_bcv, 2);
    }

    if ($id_vale > 0) {
        $sql = "UPDATE nomina_cuentas 
                SET id_empleado = $id_emp, 
                    monto = $monto_usd, 
                    concepto = '$concepto', 
                    fecha = '$fecha', 
                    moneda = '$moneda', 
                    tasa_bcv = $tasa_bcv, 
                    monto_original = $monto_original 
                WHERE id = $id_vale AND tipo_cuenta = 'Por Cobrar'";
    } else {
        $sql = "INSERT INTO nomina_cuentas (id_empleado, tipo_cuenta, monto, concepto, fecha, estado, moneda, tasa_bcv, monto_original) 
                VALUES ($id_emp, 'Por Cobrar', $monto_usd, '$concepto', '$fecha', 'Pendiente', '$moneda', $tasa_bcv, $monto_original)";
    }
            
    $res = mysqli_query($conexion, $sql);

    if ($res) {
        $id_generado = ($id_vale > 0) ? $id_vale : mysqli_insert_id($conexion);
        echo json_encode(['success' => true, 'id_vale' => $id_generado]);
    } else {
        if ($id_vale == 0) {
            $sql_fallback = "INSERT INTO nomina_cuentas (id_empleado, tipo_cuenta, monto, concepto, fecha, estado) 
                             VALUES ($id_emp, 'Por Cobrar', $monto_usd, '$concepto', '$fecha', 'Pendiente')";
            if (mysqli_query($conexion, $sql_fallback)) {
                $id_generado = mysqli_insert_id($conexion);
                echo json_encode(['success' => true, 'id_vale' => $id_generado]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'msg' => 'Error en base de datos al registrar.']);
    }
    exit;
}

// =========================================================================
// 6. ELIMINAR VALE O DEUDA
// =========================================================================
if ($action === 'eliminar') {
    $id_cuenta = intval($_POST['id_cuenta']);
    if (mysqli_query($conexion, "DELETE FROM nomina_cuentas WHERE id = $id_cuenta")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Error al eliminar']);
    }
    exit;
}

// =========================================================================
// 7. ARCHIVAR VALE O DEUDA
// =========================================================================
if ($action === 'archivar') {
    $id_cuenta = intval($_POST['id_cuenta']);
    if (mysqli_query($conexion, "UPDATE nomina_cuentas SET estado = 'Archivado' WHERE id = $id_cuenta")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Error al archivar']);
    }
    exit;
}

// =========================================================================
// 8. OBTENER VALE O DEUDA PARA EDITAR
// =========================================================================
if ($action === 'obtener') {
    $id_vale = intval($_POST['id_vale']);
    $q = mysqli_query($conexion, "SELECT * FROM nomina_cuentas WHERE id = $id_vale");
    if ($q && $r = mysqli_fetch_assoc($q)) {
        echo json_encode(['success' => true, 'data' => $r]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'No se encontró el registro solicitado.']);
    }
    exit;
}
?>