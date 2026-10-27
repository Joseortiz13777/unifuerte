<?php
session_start();
error_reporting(0);

require_once '../../../conexion.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesión expirada']); exit;
}

$id_admin = intval($_SESSION['usuario_id']);
$q = mysqli_query($conexion, "SELECT rol, permisos FROM usuario WHERE ID = $id_admin");
$admin_dat = mysqli_fetch_assoc($q);
$permisos_admin = explode(',', $admin_dat['permisos'] ?? '');

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

$es_inventario = in_array($accion, [
    'listar_cat', 'guardar_cat', 'eliminar_cat', 
    'listar_subcat', 'guardar_subcat', 'eliminar_subcat', 
    'listar_mp', 'guardar_mp', 'eliminar_mp',
    'listar_proveedores', 'guardar_proveedor', 'eliminar_proveedor',
    'listar_compras', 'guardar_compra', 'ver_detalle_compra', 'marcar_compra_recibida', 'eliminar_compra',
    'listar_patrones', 'guardar_patron', 'eliminar_patron',
    'guardar_pago_compra', 'listar_pagos_compra', 'eliminar_pago_compra',
    'listar_tipos_prov', 'guardar_tipo_prov', 'eliminar_tipo_prov',
    'listar_servicios_prov', 'guardar_servicio_prov', 'eliminar_servicio_prov',
    'listar_gastos', 'guardar_gasto', 'eliminar_gasto',
    'listar_pagos_gasto', 'guardar_pago_gasto', 'eliminar_pago_gasto',
    'listar_ordenes_produccion', 'ver_consumo_orden', 'aplicar_descuento_orden',
    'listar_ordenes_servicio', 'guardar_orden_servicio', 'eliminar_orden_servicio', 'pagar_orden_servicio'
]);
$es_receta = in_array($accion, [
    'listar_recetas_agrupadas', 'listar_detalle_receta', 'guardar_receta_multiple', 'eliminar_item_receta', 'eliminar_receta_completa'
]);

if ($es_inventario && !in_array('21', $permisos_admin)) {
    echo json_encode(['success' => false, 'msg' => 'No tienes permiso para gestionar el almacén (Vector 21).']); exit;
}
if ($es_receta && !in_array('27', $permisos_admin)) {
    echo json_encode(['success' => false, 'msg' => 'No tienes permiso para gestionar recetas (Vector 27).']); exit;
}

function registrar_auditoria($con, $id_usr, $accion, $tabla, $id_reg, $detalle) {
    $acc = mysqli_real_escape_string($con, $accion);
    $tab = mysqli_real_escape_string($con, $tabla);
    $det = mysqli_real_escape_string($con, $detalle);
    mysqli_query($con, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usr, '$acc', '$tab', $id_reg, NOW(), '$det')");
}

// ================= AUTO-INSTALADOR Y MIGRACIONES SEGURAS =================
// Esta función obliga a crear las columnas si la base de datos no las tiene
function checkAddColumn($con, $table, $column, $def) {
    $res = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if($res && mysqli_num_rows($res) == 0) {
        mysqli_query($con, "ALTER TABLE `$table` ADD COLUMN `$column` $def");
    }
}

checkAddColumn($conexion, 'patron', 'foto', "VARCHAR(255) DEFAULT NULL");

// Columnas para COMPRAS
checkAddColumn($conexion, 'compra', 'nro_factura', "VARCHAR(100) DEFAULT NULL");
checkAddColumn($conexion, 'compra', 'nro_control', "VARCHAR(100) DEFAULT NULL");
checkAddColumn($conexion, 'compra', 'banco', "VARCHAR(100) DEFAULT NULL");
checkAddColumn($conexion, 'compra', 'referencia', "VARCHAR(100) DEFAULT NULL");
checkAddColumn($conexion, 'compra', 'iva', "DECIMAL(10,2) DEFAULT 0.00");
checkAddColumn($conexion, 'compra', 'descuento', "DECIMAL(10,2) DEFAULT 0.00");
checkAddColumn($conexion, 'compra', 'archivo_factura', "VARCHAR(255) DEFAULT NULL");
checkAddColumn($conexion, 'compra', 'moneda', "VARCHAR(10) DEFAULT 'USD'");
checkAddColumn($conexion, 'compra', 'tasa_bcv', "DECIMAL(15,4) DEFAULT 0.0000");
checkAddColumn($conexion, 'compra', 'monto_bs', "DECIMAL(15,2) DEFAULT 0.00");
checkAddColumn($conexion, 'compra', 'monto_usd', "DECIMAL(15,2) DEFAULT 0.00");
checkAddColumn($conexion, 'compra', 'monto_pagado', "DECIMAL(10,2) DEFAULT 0.00");
checkAddColumn($conexion, 'compra', 'estado_pago', "VARCHAR(50) DEFAULT 'Sin Pagar'");

checkAddColumn($conexion, 'detalle_compra', 'descuento_porc', "DECIMAL(5,2) DEFAULT 0.00");
checkAddColumn($conexion, 'detalle_compra', 'costo_neto', "DECIMAL(10,2) DEFAULT 0.00");

// Columnas para PROVEEDOR
checkAddColumn($conexion, 'provedor', 'nro_cuenta', "VARCHAR(100) DEFAULT ''");
checkAddColumn($conexion, 'provedor', 'tipo_cuenta', "VARCHAR(50) DEFAULT ''");
checkAddColumn($conexion, 'provedor', 'tipo_proveedor', "VARCHAR(100) DEFAULT 'General'");
checkAddColumn($conexion, 'provedor', 'pm_cedula', "VARCHAR(50) DEFAULT ''");
checkAddColumn($conexion, 'provedor', 'pm_telefono', "VARCHAR(50) DEFAULT ''");
checkAddColumn($conexion, 'provedor', 'pm_banco', "VARCHAR(100) DEFAULT ''");
checkAddColumn($conexion, 'provedor', 'servicio_ofrecido', "VARCHAR(150) DEFAULT ''");

// Columnas para GASTOS ADMINISTRATIVOS
checkAddColumn($conexion, 'gastos_servicios', 'moneda', "VARCHAR(10) DEFAULT 'USD'");
checkAddColumn($conexion, 'gastos_servicios', 'tasa_bcv', "DECIMAL(15,4) DEFAULT 0.0000");
checkAddColumn($conexion, 'gastos_servicios', 'monto_bs', "DECIMAL(15,2) DEFAULT 0.00");
checkAddColumn($conexion, 'gastos_servicios', 'monto_usd', "DECIMAL(15,2) DEFAULT 0.00");

checkAddColumn($conexion, 'orden_fabrica', 'inventario_descontado', "TINYINT(1) DEFAULT 0");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS sys_tipo_proveedor (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL UNIQUE)");
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS sys_servicios_proveedor (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL UNIQUE)");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS pago_compra (
    id INT AUTO_INCREMENT PRIMARY KEY, id_compra INT NOT NULL, fecha_pago DATE, monto_usd DECIMAL(10,2) DEFAULT 0.00,
    monto_bs DECIMAL(10,2) DEFAULT 0.00, tasa_bcv DECIMAL(10,2) DEFAULT 0.00, metodo_pago VARCHAR(100),
    referencia VARCHAR(100), comprobante VARCHAR(255)
)");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS gastos_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY, id_proveedor INT NOT NULL, fecha DATE, factura VARCHAR(100),
    descripcion TEXT, monto_total DECIMAL(10,2) DEFAULT 0.00, monto_pagado DECIMAL(10,2) DEFAULT 0.00,
    estado_pago VARCHAR(50) DEFAULT 'Sin Pagar', fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    moneda VARCHAR(10) DEFAULT 'USD', tasa_bcv DECIMAL(15,4) DEFAULT 0.0000, monto_bs DECIMAL(15,2) DEFAULT 0.00, monto_usd DECIMAL(15,2) DEFAULT 0.00
)");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS pago_gastos_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY, id_gasto INT NOT NULL, fecha_pago DATE, monto_usd DECIMAL(10,2) DEFAULT 0.00,
    metodo_pago VARCHAR(100), referencia VARCHAR(100), comprobante VARCHAR(255), fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS ordenes_servicio (
    id INT AUTO_INCREMENT PRIMARY KEY, id_proveedor INT NOT NULL, servicio TEXT NOT NULL, 
    costo DECIMAL(10,2) NOT NULL DEFAULT 0.00, monto_pagado DECIMAL(10,2) DEFAULT 0.00, 
    fecha_orden DATE NOT NULL, fecha_pago_limite DATE NOT NULL, estado_pago VARCHAR(50) DEFAULT 'Sin Pagar', 
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS receta_master (
    id INT AUTO_INCREMENT PRIMARY KEY, id_producto INT NOT NULL, nombre_receta VARCHAR(150) NOT NULL, 
    foto VARCHAR(255) DEFAULT NULL, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
)");

checkAddColumn($conexion, 'receta_prenda', 'id_receta_master', "INT DEFAULT 0");

$q_mig = mysqli_query($conexion, "SELECT id_producto, tipo_prenda FROM receta_prenda WHERE id_receta_master = 0 GROUP BY id_producto, tipo_prenda");
if($q_mig && mysqli_num_rows($q_mig) > 0) {
    while($rmig = mysqli_fetch_assoc($q_mig)) {
        $id_p = $rmig['id_producto']; $tp = mysqli_real_escape_string($conexion, $rmig['tipo_prenda']);
        mysqli_query($conexion, "INSERT INTO receta_master (id_producto, nombre_receta) VALUES ($id_p, '$tp')");
        $new_id = mysqli_insert_id($conexion);
        mysqli_query($conexion, "UPDATE receta_prenda SET id_receta_master = $new_id WHERE id_producto = $id_p AND id_receta_master = 0 AND tipo_prenda = '$tp'");
    }
}

// ================= TIPOS Y SERVICIOS DE PROVEEDOR =================
if ($accion == 'listar_tipos_prov') {
    $q = mysqli_query($conexion, "SELECT * FROM sys_tipo_proveedor ORDER BY nombre ASC");
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}
if ($accion == 'guardar_tipo_prov') {
    $id = intval($_POST['id']); $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    if($id > 0) $sql = "UPDATE sys_tipo_proveedor SET nombre='$nombre' WHERE id=$id";
    else $sql = "INSERT INTO sys_tipo_proveedor (nombre) VALUES ('$nombre')";
    if(mysqli_query($conexion, $sql)) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>mysqli_error($conexion)]); } exit;
}
if ($accion == 'eliminar_tipo_prov') {
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "DELETE FROM sys_tipo_proveedor WHERE id=$id")) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>'En uso.']); } exit;
}
if ($accion == 'listar_servicios_prov') {
    $q = mysqli_query($conexion, "SELECT * FROM sys_servicios_proveedor ORDER BY nombre ASC");
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}
if ($accion == 'guardar_servicio_prov') {
    $id = intval($_POST['id']); $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    if($id > 0) $sql = "UPDATE sys_servicios_proveedor SET nombre='$nombre' WHERE id=$id";
    else $sql = "INSERT INTO sys_servicios_proveedor (nombre) VALUES ('$nombre')";
    if(mysqli_query($conexion, $sql)) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>mysqli_error($conexion)]); } exit;
}
if ($accion == 'eliminar_servicio_prov') {
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "DELETE FROM sys_servicios_proveedor WHERE id=$id")) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>'En uso.']); } exit;
}

// ================= GASTOS ADMINISTRATIVOS =================
if ($accion == 'listar_gastos') {
    $sql = "SELECT g.*, p.nombre as proveedor_nombre FROM gastos_servicios g JOIN provedor p ON g.id_proveedor = p.ID ORDER BY g.fecha DESC, g.id DESC";
    $q = mysqli_query($conexion, $sql);
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}
if ($accion == 'guardar_gasto') {
    $id_gasto = intval($_POST['id_gasto'] ?? 0);
    $id_prov = intval($_POST['id_proveedor'] ?? 0);
    $fecha = mysqli_real_escape_string($conexion, $_POST['fecha'] ?? '');
    $factura = mysqli_real_escape_string($conexion, $_POST['factura'] ?? '');
    $desc = mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? '');
    $monto_total = floatval($_POST['monto_total'] ?? 0);
    
    $moneda = mysqli_real_escape_string($conexion, $_POST['moneda'] ?? 'USD');
    $tasa_bcv = floatval($_POST['tasa_bcv'] ?? 0);
    $monto_bs = floatval($_POST['monto_bs'] ?? 0);
    $monto_usd = floatval($_POST['monto_usd'] ?? 0);
    
    // Capturamos la condición de pago del formulario
    $es_credito = intval($_POST['es_credito'] ?? 1); 

    if ($id_prov <= 0 || $fecha === '' || $monto_total < 0) {
        echo json_encode(['success' => false, 'msg' => 'Proveedor, fecha y monto válido son obligatorios.']);
        exit;
    }

    if (!mysqli_begin_transaction($conexion)) {
        echo json_encode(['success' => false, 'msg' => 'No se pudo iniciar el guardado del gasto.']);
        exit;
    }
    try {
        if ($id_gasto > 0) {
            $q_chk = mysqli_query($conexion, "SELECT monto_pagado FROM gastos_servicios WHERE id = $id_gasto");
            if (!$q_chk) {
                throw new RuntimeException('No se pudo consultar el gasto que intentas editar.');
            }
            $old = mysqli_fetch_assoc($q_chk);
            if (!$old) {
                throw new RuntimeException('El gasto que intentas editar ya no existe.');
            }
            $monto_pagado = floatval($old['monto_pagado']);

            // Si se edita y se marca "Contado", y no tenía pagos previos, se salda la deuda
            if ($es_credito === 0 && $monto_pagado == 0) { 
                $monto_pagado = $monto_total; 
                $estado_pago = 'Pagado'; 
            } else {
                $estado_pago = 'Sin Pagar';
                if ($monto_pagado >= $monto_total) $estado_pago = 'Pagado';
                else if ($monto_pagado > 0) $estado_pago = 'Abonado';
            }

            $guardado = mysqli_query($conexion, "UPDATE gastos_servicios SET id_proveedor=$id_prov, fecha='$fecha', factura='$factura', descripcion='$desc', monto_total=$monto_total, estado_pago='$estado_pago', monto_pagado=$monto_pagado, moneda='$moneda', tasa_bcv=$tasa_bcv, monto_bs=$monto_bs, monto_usd=$monto_usd WHERE id=$id_gasto");
        } else {
            // Si es un gasto nuevo, se asigna el pago total si es de contado
            $monto_pagado = ($es_credito === 0) ? $monto_total : 0.00;
            $estado_pago = ($es_credito === 0) ? 'Pagado' : 'Sin Pagar';

            $guardado = mysqli_query($conexion, "INSERT INTO gastos_servicios (id_proveedor, fecha, factura, descripcion, monto_total, monto_pagado, estado_pago, moneda, tasa_bcv, monto_bs, monto_usd) VALUES ($id_prov, '$fecha', '$factura', '$desc', $monto_total, $monto_pagado, '$estado_pago', '$moneda', $tasa_bcv, $monto_bs, $monto_usd)");
        }
        if (!$guardado) {
            throw new RuntimeException('No se pudo guardar el gasto: ' . mysqli_error($conexion));
        }
        if (!mysqli_commit($conexion)) {
            throw new RuntimeException('No se pudo confirmar el guardado del gasto.');
        }
        echo json_encode(['success'=>true]);
    } catch (Throwable $e) {
        mysqli_rollback($conexion);
        error_log('Error al guardar gasto de inventario: ' . $e->getMessage());
        echo json_encode(['success'=>false, 'msg'=>$e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar el gasto.']);
    } exit;
}
if ($accion == 'eliminar_gasto') {
    $id = intval($_POST['id']);
    mysqli_query($conexion, "DELETE FROM pago_gastos_servicios WHERE id_gasto = $id");
    if(mysqli_query($conexion, "DELETE FROM gastos_servicios WHERE id=$id")) echo json_encode(['success'=>true]); 
    else echo json_encode(['success'=>false]); exit;
}
if ($accion == 'listar_pagos_gasto') {
    $id_gasto = intval($_GET['id_gasto']);
    $q = mysqli_query($conexion, "SELECT * FROM pago_gastos_servicios WHERE id_gasto = $id_gasto ORDER BY fecha_pago DESC, id DESC");
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    $q_g = mysqli_query($conexion, "SELECT monto_total, monto_pagado, estado_pago, moneda FROM gastos_servicios WHERE id = $id_gasto");
    $gasto = mysqli_fetch_assoc($q_g);
    echo json_encode(['success' => true, 'data' => $data, 'resumen' => $gasto]); exit;
}
if ($accion == 'guardar_pago_gasto') {
    $id_gasto = intval($_POST['id_gasto']);
    $fecha = mysqli_real_escape_string($conexion, $_POST['fecha_pago']);
    $monto_usd = floatval($_POST['monto_usd'] ?? 0);
    $metodo_pago = mysqli_real_escape_string($conexion, $_POST['metodo_pago'] ?? '');
    $referencia = mysqli_real_escape_string($conexion, $_POST['referencia'] ?? '');

    $archivo_val = ""; $archivo_col = "";
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] == UPLOAD_ERR_OK) {
        $directorio = '../uploads/gastos/';
        if (!is_dir($directorio)) mkdir($directorio, 0777, true);
        $ext = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'pago_gasto_' . time() . '_' . rand(100,999) . '.' . $ext;
        if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $directorio . $nombre_archivo)) {
            $archivo_col = ", comprobante"; $archivo_val = ", '$nombre_archivo'";
        }
    }
    mysqli_begin_transaction($conexion);
    try {
        mysqli_query($conexion, "INSERT INTO pago_gastos_servicios (id_gasto, fecha_pago, monto_usd, metodo_pago, referencia $archivo_col) VALUES ($id_gasto, '$fecha', $monto_usd, '$metodo_pago', '$referencia' $archivo_val)");
        $q_sum = mysqli_query($conexion, "SELECT SUM(monto_usd) as total_pagado FROM pago_gastos_servicios WHERE id_gasto = $id_gasto");
        $sum_row = mysqli_fetch_assoc($q_sum);
        $total_pagado = floatval($sum_row['total_pagado']);
        $q_costo = mysqli_query($conexion, "SELECT monto_total FROM gastos_servicios WHERE id = $id_gasto");
        $costo_row = mysqli_fetch_assoc($q_costo);
        $total_deuda = floatval($costo_row['monto_total']);

        $estado_pago = 'Sin Pagar';
        if ($total_pagado >= $total_deuda) $estado_pago = 'Pagado';
        else if ($total_pagado > 0) $estado_pago = 'Abonado';

        mysqli_query($conexion, "UPDATE gastos_servicios SET monto_pagado = $total_pagado, estado_pago = '$estado_pago' WHERE id = $id_gasto");
        mysqli_commit($conexion);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        mysqli_rollback($conexion); echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    } exit;
}
if ($accion == 'eliminar_pago_gasto') {
    $id_pago = intval($_POST['id']);
    mysqli_begin_transaction($conexion);
    try {
        $q_p = mysqli_query($conexion, "SELECT id_gasto FROM pago_gastos_servicios WHERE id = $id_pago");
        $r_p = mysqli_fetch_assoc($q_p); $id_gasto = $r_p['id_gasto'];
        mysqli_query($conexion, "DELETE FROM pago_gastos_servicios WHERE id = $id_pago");
        $q_sum = mysqli_query($conexion, "SELECT SUM(monto_usd) as total_pagado FROM pago_gastos_servicios WHERE id_gasto = $id_gasto");
        $sum_row = mysqli_fetch_assoc($q_sum); $total_pagado = floatval($sum_row['total_pagado']);
        $q_costo = mysqli_query($conexion, "SELECT monto_total FROM gastos_servicios WHERE id = $id_gasto");
        $costo_row = mysqli_fetch_assoc($q_costo); $total_deuda = floatval($costo_row['monto_total']);

        $estado_pago = 'Sin Pagar';
        if ($total_pagado >= $total_deuda) $estado_pago = 'Pagado';
        else if ($total_pagado > 0) $estado_pago = 'Abonado';
        mysqli_query($conexion, "UPDATE gastos_servicios SET monto_pagado = $total_pagado, estado_pago = '$estado_pago' WHERE id = $id_gasto");
        mysqli_commit($conexion);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        mysqli_rollback($conexion); echo json_encode(['success' => false, 'msg' => 'Error al eliminar.']);
    } exit;
}

// ================= CATEGORÍAS MP =================
if ($accion == 'listar_cat') {
    $q = mysqli_query($conexion, "SELECT * FROM mp_categorias ORDER BY nombre ASC");
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}
if ($accion == 'guardar_cat') {
    $id = intval($_POST['id']); $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    if($id > 0) { $sql = "UPDATE mp_categorias SET nombre='$nombre' WHERE id=$id"; } 
    else { $sql = "INSERT INTO mp_categorias (nombre) VALUES ('$nombre')"; }
    if(mysqli_query($conexion, $sql)) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>mysqli_error($conexion)]); } exit;
}
if ($accion == 'eliminar_cat') {
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "DELETE FROM mp_categorias WHERE id=$id")) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>'En uso.']); } exit;
}

// ================= SUBCATEGORÍAS =================
if ($accion == 'listar_subcat') {
    $q = mysqli_query($conexion, "SELECT s.*, c.nombre as categoria_padre FROM mp_subcategorias s INNER JOIN mp_categorias c ON s.id_mp_categoria = c.id ORDER BY c.nombre, s.nombre");
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}
if ($accion == 'guardar_subcat') {
    $id = intval($_POST['id']); $id_cat = intval($_POST['id_mp_categoria']); $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    if($id > 0) { $sql = "UPDATE mp_subcategorias SET nombre='$nombre', id_mp_categoria=$id_cat WHERE id=$id"; } 
    else { $sql = "INSERT INTO mp_subcategorias (nombre, id_mp_categoria) VALUES ('$nombre', $id_cat)"; }
    if(mysqli_query($conexion, $sql)) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false]); } exit;
}
if ($accion == 'eliminar_subcat') {
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "DELETE FROM mp_subcategorias WHERE id=$id")) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>'En uso.']); } exit;
}

// ================= MATERIA PRIMA =================
if ($accion == 'listar_mp') {
    $q = mysqli_query($conexion, "SELECT m.*, s.nombre as subcategoria, c.nombre as categoria_nombre FROM materia_prima m LEFT JOIN mp_subcategorias s ON m.id_mp_subcategoria = s.id LEFT JOIN mp_categorias c ON s.id_mp_categoria = c.id ORDER BY m.nombre ASC");
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}
if ($accion == 'guardar_mp') {
    $id = intval($_POST['ID']);
    $id_sub = intval($_POST['id_mp_subcategoria']);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $color = mysqli_real_escape_string($conexion, $_POST['color'] ?? '');
    $codigo_color = mysqli_real_escape_string($conexion, $_POST['codigo_color'] ?? '');
    $textura = mysqli_real_escape_string($conexion, $_POST['textura'] ?? '');
    $tamano = mysqli_real_escape_string($conexion, $_POST['tamano_dimension'] ?? '');
    $origen = mysqli_real_escape_string($conexion, $_POST['origen_procedencia'] ?? '');
    $comp = mysqli_real_escape_string($conexion, $_POST['composicion'] ?? '');
    $desc = mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? '');
    $und = mysqli_real_escape_string($conexion, $_POST['unidad_medida']);
    $costo = floatval($_POST['costo']);
    $stock = floatval($_POST['stock_actual'] ?? 0);
    $stock_min = floatval($_POST['stock_minimo'] ?? 0);

    if($id == 0) {
        $q_chk = mysqli_query($conexion, "SELECT ID FROM materia_prima WHERE nombre='$nombre' AND color='$color' AND tamano_dimension='$tamano'");
        if(mysqli_num_rows($q_chk) > 0) { echo json_encode(['success'=>false, 'msg'=>'Ya existe.']); exit; }
    }

    $foto_update = ""; $foto_insert_col = ""; $foto_insert_val = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $directorio = '../uploads/';
        if (!is_dir($directorio)) mkdir($directorio, 0777, true);
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombre_foto = 'mp_' . time() . '_' . rand(100,999) . '.' . $ext;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $directorio . $nombre_foto)) {
            $foto_update = ", foto='$nombre_foto'"; $foto_insert_col = ", foto"; $foto_insert_val = ", '$nombre_foto'";
        }
    }

    if($id > 0) {
        $sql = "UPDATE materia_prima SET id_mp_subcategoria=$id_sub, nombre='$nombre', color='$color', codigo_color='$codigo_color', textura='$textura', tamano_dimension='$tamano', origen_procedencia='$origen', unidad_medida='$und', costo=$costo, stock_actual=$stock, stock_minimo=$stock_min, composicion='$comp', descripcion='$desc' $foto_update WHERE ID=$id";
    } else {
        $sql = "INSERT INTO materia_prima (id_mp_subcategoria, nombre, color, codigo_color, textura, tamano_dimension, origen_procedencia, unidad_medida, costo, stock_actual, stock_minimo, composicion, descripcion $foto_insert_col) VALUES ($id_sub, '$nombre', '$color', '$codigo_color', '$textura', '$tamano', '$origen', '$und', $costo, $stock, $stock_min, '$comp', '$desc' $foto_insert_val)";
    }
    if(mysqli_query($conexion, $sql)) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>mysqli_error($conexion)]); } exit;
}
if ($accion == 'eliminar_mp') {
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "DELETE FROM materia_prima WHERE ID=$id")) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>'En uso.']); } exit;
}

// ================= PROVEEDORES =================
if ($accion == 'listar_proveedores') {
    $sql = "SELECT p.*, ( COALESCE((SELECT SUM(costo - monto_pagado) FROM compra WHERE id_provedor = p.ID AND estado_pago != 'Pagado'), 0) + COALESCE((SELECT SUM(monto_total - monto_pagado) FROM gastos_servicios WHERE id_proveedor = p.ID AND estado_pago != 'Pagado'), 0) ) AS deuda_total FROM provedor p ORDER BY p.nombre ASC";
    $q = mysqli_query($conexion, $sql);
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}
if ($accion == 'guardar_proveedor') {
    $id = intval($_POST['ID']); $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']); $rif = mysqli_real_escape_string($conexion, $_POST['RIF_cedula'] ?? ''); $contacto = mysqli_real_escape_string($conexion, $_POST['contacto'] ?? ''); $tlf = mysqli_real_escape_string($conexion, $_POST['tlf'] ?? ''); $flete = floatval($_POST['flete_internacional'] ?? 0); $ubic = mysqli_real_escape_string($conexion, $_POST['ubicacion'] ?? ''); $desc = mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? ''); $nro_cuenta = mysqli_real_escape_string($conexion, $_POST['nro_cuenta'] ?? ''); $tipo_cuenta = mysqli_real_escape_string($conexion, $_POST['tipo_cuenta'] ?? ''); $tipo_proveedor = mysqli_real_escape_string($conexion, $_POST['tipo_proveedor'] ?? 'General'); $servicio_ofrecido = mysqli_real_escape_string($conexion, $_POST['servicio_ofrecido'] ?? ''); $pm_cedula = mysqli_real_escape_string($conexion, $_POST['pm_cedula'] ?? ''); $pm_telefono = mysqli_real_escape_string($conexion, $_POST['pm_telefono'] ?? ''); $pm_banco = mysqli_real_escape_string($conexion, $_POST['pm_banco'] ?? '');

    if($id > 0) {
        $sql = "UPDATE provedor SET nombre='$nombre', RIF_cedula='$rif', contacto='$contacto', tlf='$tlf', flete_internacional=$flete, ubicacion='$ubic', descripcion='$desc', nro_cuenta='$nro_cuenta', tipo_cuenta='$tipo_cuenta', tipo_proveedor='$tipo_proveedor', servicio_ofrecido='$servicio_ofrecido', pm_cedula='$pm_cedula', pm_telefono='$pm_telefono', pm_banco='$pm_banco' WHERE ID=$id";
    } else {
        $sql = "INSERT INTO provedor (nombre, RIF_cedula, contacto, tlf, flete_internacional, ubicacion, descripcion, nro_cuenta, tipo_cuenta, tipo_proveedor, servicio_ofrecido, pm_cedula, pm_telefono, pm_banco) VALUES ('$nombre', '$rif', '$contacto', '$tlf', $flete, '$ubic', '$desc', '$nro_cuenta', '$tipo_cuenta', '$tipo_proveedor', '$servicio_ofrecido', '$pm_cedula', '$pm_telefono', '$pm_banco')";
    }

    if(mysqli_query($conexion, $sql)) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>mysqli_error($conexion)]); } exit;
}
if ($accion == 'eliminar_proveedor') {
    $id = intval($_POST['id']);
    mysqli_begin_transaction($conexion);
    try {
        $q_compras = mysqli_query($conexion, "SELECT ID FROM compra WHERE id_provedor = $id");
        if ($q_compras) {
            while($c = mysqli_fetch_assoc($q_compras)) {
                $id_compra = $c['ID'];
                mysqli_query($conexion, "DELETE FROM detalle_compra WHERE id_compra = $id_compra");
                mysqli_query($conexion, "DELETE FROM pago_compra WHERE id_compra = $id_compra");
            }
        }
        $q_servicios = mysqli_query($conexion, "SELECT id FROM gastos_servicios WHERE id_proveedor = $id");
        if ($q_servicios) {
            while($s = mysqli_fetch_assoc($q_servicios)) {
                $id_gasto = $s['id']; mysqli_query($conexion, "DELETE FROM pago_gastos_servicios WHERE id_gasto = $id_gasto");
            }
        }
        mysqli_query($conexion, "DELETE FROM compra WHERE id_provedor = $id");
        mysqli_query($conexion, "DELETE FROM gastos_servicios WHERE id_proveedor = $id");
        mysqli_query($conexion, "DELETE FROM ordenes_servicio WHERE id_proveedor = $id");
        mysqli_query($conexion, "DELETE FROM provedor WHERE ID = $id");
        mysqli_commit($conexion); echo json_encode(['success'=>true]);
    } catch (Exception $e) { mysqli_rollback($conexion); echo json_encode(['success'=>false, 'msg'=>'Error: ' . $e->getMessage()]); } exit;
}

// ================= COMPRAS Y CUENTAS POR PAGAR =================
if ($accion == 'listar_compras') {
    $sql = "SELECT c.*, p.nombre as proveedor_nombre FROM compra c JOIN provedor p ON c.id_provedor = p.ID ORDER BY c.fecha_compra DESC, c.ID DESC";
    $q = mysqli_query($conexion, $sql);
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}

if ($accion == 'ver_detalle_compra') {
    $id = intval($_POST['id']);
    $sql = "SELECT d.*, m.nombre as mp_nombre, m.unidad_medida FROM detalle_compra d JOIN materia_prima m ON d.id_materia = m.ID WHERE d.id_compra = $id";
    $q = mysqli_query($conexion, $sql);
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    $q2 = mysqli_query($conexion, "SELECT * FROM compra WHERE ID = $id");
    $compra_datos = mysqli_fetch_assoc($q2);
    echo json_encode(['success' => true, 'data' => $data, 'compra' => $compra_datos]); exit;
}

if ($accion == 'guardar_compra') {
    $id_compra_edit = intval($_POST['id_compra'] ?? 0);
    $id_prov = intval($_POST['id_proveedor']);
    $fecha = mysqli_real_escape_string($conexion, $_POST['fecha_compra']);
    $estado = mysqli_real_escape_string($conexion, $_POST['estado']);
    $total = floatval($_POST['total'] ?? 0);
    $es_credito = intval($_POST['es_credito'] ?? 1); 

    $nro_factura = mysqli_real_escape_string($conexion, $_POST['nro_factura'] ?? '');
    $nro_control = mysqli_real_escape_string($conexion, $_POST['nro_control'] ?? '');
    $banco = mysqli_real_escape_string($conexion, $_POST['banco'] ?? '');
    $referencia = mysqli_real_escape_string($conexion, $_POST['referencia'] ?? '');
    $iva = floatval($_POST['iva'] ?? 0);
    $descuento = floatval($_POST['descuento'] ?? 0);
    $moneda = mysqli_real_escape_string($conexion, $_POST['moneda'] ?? 'USD');
    $tasa_bcv = floatval($_POST['tasa_bcv'] ?? 0);
    $monto_bs = floatval($_POST['monto_bs'] ?? 0);
    $monto_usd = floatval($_POST['monto_usd'] ?? 0);

    $insumos = json_decode($_POST['insumos'], true);
    if (!is_array($insumos) || count($insumos) == 0) { echo json_encode(['success' => false, 'msg' => 'No hay insumos.']); exit; }

    $archivo_update = ""; $archivo_insert_col = ""; $archivo_insert_val = "";
    if (isset($_FILES['archivo_factura']) && $_FILES['archivo_factura']['error'] == UPLOAD_ERR_OK) {
        $directorio = '../uploads/compras/';
        if (!is_dir($directorio)) mkdir($directorio, 0777, true);
        $ext = pathinfo($_FILES['archivo_factura']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'factura_' . time() . '_' . rand(100,999) . '.' . $ext;
        if (move_uploaded_file($_FILES['archivo_factura']['tmp_name'], $directorio . $nombre_archivo)) {
            $archivo_update = ", archivo_factura='$nombre_archivo'"; $archivo_insert_col = ", archivo_factura"; $archivo_insert_val = ", '$nombre_archivo'";
        }
    }

    mysqli_begin_transaction($conexion);
    try {
        if ($id_compra_edit > 0) {
            $q_chk = mysqli_query($conexion, "SELECT estado, monto_pagado FROM compra WHERE ID = $id_compra_edit");
            $comp_old = mysqli_fetch_assoc($q_chk);
            $monto_pagado = floatval($comp_old['monto_pagado']);

            if ($es_credito === 0 && $monto_pagado == 0) { $monto_pagado = $total; $estado_pago = 'Pagado'; } 
            else {
                $estado_pago = 'Sin Pagar';
                if ($monto_pagado >= $total) $estado_pago = 'Pagado';
                else if ($monto_pagado > 0) $estado_pago = 'Abonado';
            }

            if ($comp_old && $comp_old['estado'] === 'Recibido') {
                $q_old_det = mysqli_query($conexion, "SELECT id_materia, cantidad FROM detalle_compra WHERE id_compra = $id_compra_edit");
                while($old_d = mysqli_fetch_assoc($q_old_det)) {
                    $id_m = $old_d['id_materia']; $c_old = $old_d['cantidad'];
                    mysqli_query($conexion, "UPDATE materia_prima SET stock_actual = stock_actual - $c_old WHERE ID = $id_m");
                }
            }

            mysqli_query($conexion, "DELETE FROM detalle_compra WHERE id_compra = $id_compra_edit");

            $sql_c = "UPDATE compra SET id_provedor=$id_prov, fecha_compra='$fecha', estado='$estado', costo=$total,
                      nro_factura='$nro_factura', nro_control='$nro_control', banco='$banco', referencia='$referencia', 
                      iva=$iva, descuento=$descuento, moneda='$moneda', tasa_bcv=$tasa_bcv, monto_bs=$monto_bs, monto_usd=$monto_usd, estado_pago='$estado_pago', monto_pagado=$monto_pagado $archivo_update
                      WHERE ID = $id_compra_edit";
            mysqli_query($conexion, $sql_c);
            $id_compra = $id_compra_edit;
        } else {
            $monto_pagado = ($es_credito === 0) ? $total : 0.00;
            $estado_pago = ($es_credito === 0) ? 'Pagado' : 'Sin Pagar';

            $sql_c = "INSERT INTO compra (id_provedor, fecha_compra, estado, costo, nro_factura, nro_control, banco, referencia, iva, descuento, moneda, tasa_bcv, monto_bs, monto_usd, estado_pago, monto_pagado $archivo_insert_col) 
                      VALUES ($id_prov, '$fecha', '$estado', $total, '$nro_factura', '$nro_control', '$banco', '$referencia', $iva, $descuento, '$moneda', $tasa_bcv, $monto_bs, $monto_usd, '$estado_pago', $monto_pagado $archivo_insert_val)";
            mysqli_query($conexion, $sql_c);
            $id_compra = mysqli_insert_id($conexion);
        }

        foreach($insumos as $ins) {
            $id_mp = intval($ins['id_mp']);
            $cant = floatval($ins['cantidad']);
            $precio_u = floatval($ins['costo_base']);
            $desc_p = floatval($ins['desc_porc']);
            $costo_n = floatval($ins['costo_neto']);

            $sql_d = "INSERT INTO detalle_compra (id_compra, id_materia, cantidad, precio_unitario, descuento_porc, costo_neto) VALUES ($id_compra, $id_mp, $cant, $precio_u, $desc_p, $costo_n)";
            mysqli_query($conexion, $sql_d);

            if ($estado === 'Recibido') {
                mysqli_query($conexion, "UPDATE materia_prima SET stock_actual = stock_actual + $cant WHERE ID = $id_mp");
            }
        }

        mysqli_commit($conexion);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    } exit;
}

if ($accion == 'marcar_compra_recibida') {
    $id_compra = intval($_POST['id']);
    $q_chk = mysqli_query($conexion, "SELECT estado FROM compra WHERE ID = $id_compra");
    $compra = mysqli_fetch_assoc($q_chk);

    if ($compra && $compra['estado'] !== 'Recibido') {
        mysqli_begin_transaction($conexion);
        try {
            mysqli_query($conexion, "UPDATE compra SET estado = 'Recibido' WHERE ID = $id_compra");
            $q_det = mysqli_query($conexion, "SELECT id_materia, cantidad FROM detalle_compra WHERE id_compra = $id_compra");
            while($d = mysqli_fetch_assoc($q_det)) {
                $id_mp = intval($d['id_materia']); $cant = floatval($d['cantidad']);
                mysqli_query($conexion, "UPDATE materia_prima SET stock_actual = stock_actual + $cant WHERE ID = $id_mp");
            }
            mysqli_commit($conexion); echo json_encode(['success' => true]);
        } catch (Exception $e) { mysqli_rollback($conexion); echo json_encode(['success' => false, 'msg' => 'Error.']); }
    } else { echo json_encode(['success' => false, 'msg' => 'Ya recibida.']); } exit;
}

if ($accion == 'eliminar_compra') {
    $id = intval($_POST['id']);
    mysqli_begin_transaction($conexion);
    try {
        $q_chk = mysqli_query($conexion, "SELECT estado FROM compra WHERE ID = $id");
        $comp_old = mysqli_fetch_assoc($q_chk);

        if ($comp_old && $comp_old['estado'] === 'Recibido') {
            $q_old_det = mysqli_query($conexion, "SELECT id_materia, cantidad FROM detalle_compra WHERE id_compra = $id");
            while($old_d = mysqli_fetch_assoc($q_old_det)) {
                $id_m = $old_d['id_materia']; $c_old = $old_d['cantidad'];
                mysqli_query($conexion, "UPDATE materia_prima SET stock_actual = stock_actual - $c_old WHERE ID = $id_m");
            }
        }
        mysqli_query($conexion, "DELETE FROM pago_compra WHERE id_compra = $id");
        mysqli_query($conexion, "DELETE FROM detalle_compra WHERE id_compra = $id");
        mysqli_query($conexion, "DELETE FROM compra WHERE ID = $id");

        mysqli_commit($conexion); echo json_encode(['success' => true]);
    } catch (Exception $e) { mysqli_rollback($conexion); echo json_encode(['success' => false, 'msg' => $e->getMessage()]); } exit;
}

if ($accion == 'listar_pagos_compra') {
    $id_compra = intval($_GET['id_compra']);
    $q = mysqli_query($conexion, "SELECT * FROM pago_compra WHERE id_compra = $id_compra ORDER BY fecha_pago DESC, id DESC");
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    $q_comp = mysqli_query($conexion, "SELECT costo, monto_pagado, estado_pago, moneda FROM compra WHERE ID = $id_compra");
    $compra = mysqli_fetch_assoc($q_comp);
    echo json_encode(['success' => true, 'data' => $data, 'resumen' => $compra]); exit;
}

if ($accion == 'guardar_pago_compra') {
    $id_compra = intval($_POST['id_compra']);
    $fecha = mysqli_real_escape_string($conexion, $_POST['fecha_pago']);
    $monto_usd = floatval($_POST['monto_usd'] ?? 0);
    $monto_bs = floatval($_POST['monto_bs'] ?? 0);
    $tasa_bcv = floatval($_POST['tasa_bcv'] ?? 0);
    $metodo_pago = mysqli_real_escape_string($conexion, $_POST['metodo_pago'] ?? '');
    $referencia = mysqli_real_escape_string($conexion, $_POST['referencia'] ?? '');

    $archivo_val = ""; $archivo_col = "";
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] == UPLOAD_ERR_OK) {
        $directorio = '../uploads/compras/pagos/';
        if (!is_dir($directorio)) mkdir($directorio, 0777, true);
        $ext = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'pago_' . time() . '_' . rand(100,999) . '.' . $ext;
        if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $directorio . $nombre_archivo)) {
            $archivo_col = ", comprobante"; $archivo_val = ", '$nombre_archivo'";
        }
    }

    mysqli_begin_transaction($conexion);
    try {
        $sql_p = "INSERT INTO pago_compra (id_compra, fecha_pago, monto_usd, monto_bs, tasa_bcv, metodo_pago, referencia $archivo_col) VALUES ($id_compra, '$fecha', $monto_usd, $monto_bs, $tasa_bcv, '$metodo_pago', '$referencia' $archivo_val)";
        mysqli_query($conexion, $sql_p);

        $q_sum = mysqli_query($conexion, "SELECT SUM(monto_usd) as total_pagado FROM pago_compra WHERE id_compra = $id_compra");
        $sum_row = mysqli_fetch_assoc($q_sum);
        $total_pagado = floatval($sum_row['total_pagado']);

        $q_costo = mysqli_query($conexion, "SELECT costo FROM compra WHERE ID = $id_compra");
        $costo_row = mysqli_fetch_assoc($q_costo);
        $total_deuda = floatval($costo_row['costo']);

        $estado_pago = 'Sin Pagar';
        if ($total_pagado >= $total_deuda) $estado_pago = 'Pagado';
        else if ($total_pagado > 0) $estado_pago = 'Abonado';

        mysqli_query($conexion, "UPDATE compra SET monto_pagado = $total_pagado, estado_pago = '$estado_pago' WHERE ID = $id_compra");
        mysqli_commit($conexion); echo json_encode(['success' => true]);
    } catch (Exception $e) { mysqli_rollback($conexion); echo json_encode(['success' => false, 'msg' => $e->getMessage()]); } exit;
}

if ($accion == 'eliminar_pago_compra') {
    $id_pago = intval($_POST['id']);
    mysqli_begin_transaction($conexion);
    try {
        $q_p = mysqli_query($conexion, "SELECT id_compra FROM pago_compra WHERE id = $id_pago");
        $r_p = mysqli_fetch_assoc($q_p); $id_compra = $r_p['id_compra'];
        mysqli_query($conexion, "DELETE FROM pago_compra WHERE id = $id_pago");

        $q_sum = mysqli_query($conexion, "SELECT SUM(monto_usd) as total_pagado FROM pago_compra WHERE id_compra = $id_compra");
        $sum_row = mysqli_fetch_assoc($q_sum); $total_pagado = floatval($sum_row['total_pagado']);
        $q_costo = mysqli_query($conexion, "SELECT costo FROM compra WHERE ID = $id_compra");
        $costo_row = mysqli_fetch_assoc($q_costo); $total_deuda = floatval($costo_row['costo']);

        $estado_pago = 'Sin Pagar';
        if ($total_pagado >= $total_deuda) $estado_pago = 'Pagado';
        else if ($total_pagado > 0) $estado_pago = 'Abonado';

        mysqli_query($conexion, "UPDATE compra SET monto_pagado = $total_pagado, estado_pago = '$estado_pago' WHERE ID = $id_compra");
        mysqli_commit($conexion); echo json_encode(['success' => true]);
    } catch (Exception $e) { mysqli_rollback($conexion); echo json_encode(['success' => false, 'msg' => 'Error al eliminar.']); } exit;
}

// ================= PATRONES DE COSTURA =================
if ($accion == 'listar_patrones') {
    $q = mysqli_query($conexion, "SELECT * FROM patron ORDER BY codigo_patron ASC");
    $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}

if ($accion == 'guardar_patron') {
    $id = intval($_POST['ID']); $codigo = mysqli_real_escape_string($conexion, $_POST['codigo_patron']); $categoria = mysqli_real_escape_string($conexion, $_POST['categoria'] ?? ''); $talla = mysqli_real_escape_string($conexion, $_POST['Talla'] ?? ''); $medidas = mysqli_real_escape_string($conexion, $_POST['medidas'] ?? ''); $desc = mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? '');

    $foto_update = ""; $foto_insert_col = ""; $foto_insert_val = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $directorio = '../uploads/patrones/';
        if (!is_dir($directorio)) mkdir($directorio, 0777, true);
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombre_foto = 'patron_' . time() . '_' . rand(100,999) . '.' . $ext;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $directorio . $nombre_foto)) {
            $foto_update = ", foto='$nombre_foto'"; $foto_insert_col = ", foto"; $foto_insert_val = ", '$nombre_foto'";
        }
    }

    if($id > 0) { $sql = "UPDATE patron SET codigo_patron='$codigo', categoria='$categoria', Talla='$talla', medidas='$medidas', descripcion='$desc' $foto_update WHERE ID=$id"; } 
    else { $sql = "INSERT INTO patron (codigo_patron, categoria, Talla, medidas, descripcion $foto_insert_col) VALUES ('$codigo', '$categoria', '$talla', '$medidas', '$desc' $foto_insert_val)"; }
    if(mysqli_query($conexion, $sql)) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>mysqli_error($conexion)]); } exit;
}

if ($accion == 'eliminar_patron') {
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "DELETE FROM patron WHERE ID=$id")) { echo json_encode(['success'=>true]); } else { echo json_encode(['success'=>false, 'msg'=>'Error.']); } exit;
}

// ================= RECETAS MÚLTIPLES =================
if ($accion == 'listar_recetas_agrupadas') {
    $sql = "SELECT rm.id as id_receta_master, rm.nombre_receta, rm.foto, p.nombre as producto_nombre, p.id as id_producto, COUNT(rp.ID) as cant_insumos, SUM(rp.Costo_unitario) as costo_total FROM receta_master rm INNER JOIN productos p ON rm.id_producto = p.id LEFT JOIN receta_prenda rp ON rm.id = rp.id_receta_master GROUP BY rm.id ORDER BY p.nombre ASC, rm.nombre_receta ASC";
    $q = mysqli_query($conexion, $sql); $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}

if ($accion == 'listar_detalle_receta') {
    $id_rm = intval($_GET['id_receta_master']);
    $sql = "SELECT r.ID as id_receta, r.id_materia_prima, r.cantidad_por_unidad, r.Costo_unitario as costo_linea, m.nombre as mp_nombre, m.unidad_medida, m.costo as mp_costo FROM receta_prenda r INNER JOIN materia_prima m ON r.id_materia_prima = m.ID WHERE r.id_receta_master = $id_rm ORDER BY m.nombre ASC";
    $q = mysqli_query($conexion, $sql); $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}

if ($accion == 'guardar_receta_multiple') {
    $id_receta_master = intval($_POST['id_receta_master'] ?? 0);
    $id_prod = intval($_POST['id_producto']);
    $nombre_receta = mysqli_real_escape_string($conexion, $_POST['nombre_receta']);
    $insumos = json_decode($_POST['insumos'], true);

    if (!is_array($insumos) || count($insumos) == 0) { echo json_encode(['success' => false, 'msg' => 'La lista de insumos está vacía.']); exit; }

    $archivo_update = ""; $archivo_insert_col = ""; $archivo_insert_val = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $directorio = '../uploads/recetas/';
        if (!is_dir($directorio)) mkdir($directorio, 0777, true);
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'receta_' . time() . '_' . rand(100,999) . '.' . $ext;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $directorio . $nombre_archivo)) {
            $archivo_update = ", foto='$nombre_archivo'"; $archivo_insert_col = ", foto"; $archivo_insert_val = ", '$nombre_archivo'";
        }
    }

    mysqli_begin_transaction($conexion);
    try {
        if ($id_receta_master > 0) {
            mysqli_query($conexion, "UPDATE receta_master SET id_producto=$id_prod, nombre_receta='$nombre_receta' $archivo_update WHERE id=$id_receta_master");
            mysqli_query($conexion, "DELETE FROM receta_prenda WHERE id_receta_master = $id_receta_master");
        } else {
            mysqli_query($conexion, "INSERT INTO receta_master (id_producto, nombre_receta $archivo_insert_col) VALUES ($id_prod, '$nombre_receta' $archivo_insert_val)");
            $id_receta_master = mysqli_insert_id($conexion);
        }

        foreach($insumos as $ins) {
            $id_mp = intval($ins['id_mp']); $cant = floatval($ins['cantidad']); $costo = floatval($ins['costo_linea']);
            $sql = "INSERT INTO receta_prenda (id_producto, tipo_prenda, id_receta_master, id_materia_prima, cantidad_por_unidad, Costo_unitario) VALUES ($id_prod, '$nombre_receta', $id_receta_master, $id_mp, $cant, $costo)";
            mysqli_query($conexion, $sql);
        }

        mysqli_commit($conexion); echo json_encode(['success'=>true]); 
    } catch(Exception $e) { mysqli_rollback($conexion); echo json_encode(['success'=>false, 'msg'=>'Error al guardar la receta.']); } exit;
}

if ($accion == 'eliminar_item_receta') {
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "DELETE FROM receta_prenda WHERE ID=$id")) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false]); exit;
}

if ($accion == 'eliminar_receta_completa') {
    $id_receta_master = intval($_POST['id_receta_master']);
    if(mysqli_query($conexion, "DELETE FROM receta_master WHERE id=$id_receta_master")) {
        mysqli_query($conexion, "DELETE FROM receta_prenda WHERE id_receta_master=$id_receta_master");
        echo json_encode(['success'=>true]); 
    } else { echo json_encode(['success'=>false, 'msg'=>mysqli_error($conexion)]); } exit;
}

// ================= CONSUMOS =================
if ($accion == 'listar_ordenes_produccion') {
    $sql = "SELECT o.ID, o.nro_orden_fabrica, o.fecha_emision, c.nombre AS cliente_nombre, o.inventario_descontado FROM orden_fabrica o LEFT JOIN cliente c ON o.id_cliente = c.ID ORDER BY o.inventario_descontado ASC, o.ID DESC LIMIT 100";
    $q = mysqli_query($conexion, $sql); $data = []; if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode(['success' => true, 'data' => $data]); exit;
}

if ($accion == 'ver_consumo_orden' || $accion == 'aplicar_descuento_orden') {
    $id_orden = intval($_POST['id_orden']);

    if ($accion == 'aplicar_descuento_orden') {
        $q_chk = mysqli_query($conexion, "SELECT inventario_descontado, nro_orden_fabrica FROM orden_fabrica WHERE ID = $id_orden");
        $orden = mysqli_fetch_assoc($q_chk);
        if (!$orden) { echo json_encode(['success'=>false, 'msg'=>'Orden no encontrada']); exit; }
        if ($orden['inventario_descontado'] == 1) { echo json_encode(['success'=>false, 'msg'=>'Esta orden ya fue descontada anteriormente.']); exit; }
    }

    $q_detalles = mysqli_query($conexion, "SELECT dc.id_producto, dc.tipo_prenda, dc.cantidad, dc.especificaciones_tallas FROM orden_fabrica o JOIN detalle_cotizacion dc ON o.id_cotizacion = dc.id_cotizacion WHERE o.ID = $id_orden");

    $consumo_por_mp = []; 
    while ($det = mysqli_fetch_assoc($q_detalles)) {
        $id_prod = intval($det['id_producto']);
        $tipo_prenda = mysqli_real_escape_string($conexion, $det['tipo_prenda']);
        $cantidad_total_linea = floatval($det['cantidad']);

        $specs = json_decode($det['especificaciones_tallas'], true);
        $desglose = [];

        if (is_array($specs) && count($specs) > 0) {
            foreach ($specs as $sp) {
                $desglose[] = [ 'talla' => mysqli_real_escape_string($conexion, $sp['talla'] ?? ''), 'genero' => mysqli_real_escape_string($conexion, $sp['genero'] ?? ''), 'cantidad' => floatval($sp['cantidad']) ];
            }
        } else {
            $desglose[] = [ 'talla' => '', 'genero' => '', 'cantidad' => $cantidad_total_linea ];
        }

        foreach ($desglose as $item) {
            $cant_req = $item['cantidad'];
            if ($cant_req <= 0) continue;

            $talla = $item['talla'];
            $genero = $item['genero'];

            $sql_receta = "SELECT id FROM receta_master WHERE (id_producto = $id_prod OR nombre_receta = '$tipo_prenda') ORDER BY (nombre_receta LIKE '%$talla%' AND nombre_receta LIKE '%$genero%') DESC, (nombre_receta LIKE '%$talla%') DESC, id ASC LIMIT 1";

            $q_rm = mysqli_query($conexion, $sql_receta);
            $id_rm = 0;
            if ($r_rm = mysqli_fetch_assoc($q_rm)) { $id_rm = intval($r_rm['id']); }

            if ($id_rm > 0) {
                $q_ins = mysqli_query($conexion, "SELECT rp.id_materia_prima, rp.cantidad_por_unidad, mp.nombre, mp.unidad_medida, mp.stock_actual FROM receta_prenda rp JOIN materia_prima mp ON rp.id_materia_prima = mp.ID WHERE rp.id_receta_master = $id_rm");

                while ($ins = mysqli_fetch_assoc($q_ins)) {
                    $id_mp = $ins['id_materia_prima'];
                    $total_mp_requerido = floatval($ins['cantidad_por_unidad']) * $cant_req;

                    if (!isset($consumo_por_mp[$id_mp])) {
                        $consumo_por_mp[$id_mp] = [ 'id_mp' => $id_mp, 'mp_nombre' => $ins['nombre'], 'unidad_medida' => $ins['unidad_medida'], 'stock_actual' => floatval($ins['stock_actual']), 'consumo_total' => 0 ];
                    }
                    $consumo_por_mp[$id_mp]['consumo_total'] += $total_mp_requerido;
                }
            }
        }
    }

    $data_final = array_values($consumo_por_mp);

    if ($accion == 'ver_consumo_orden') { echo json_encode(['success' => true, 'data' => $data_final]); exit; } 

    if ($accion == 'aplicar_descuento_orden') {
        if (count($data_final) == 0) { echo json_encode(['success' => false, 'msg' => 'No hay prendas con recetas.']); exit; }

        mysqli_begin_transaction($conexion);
        try {
            foreach ($data_final as $item) {
                $id_mp = intval($item['id_mp']);
                $descuento = floatval($item['consumo_total']);
                mysqli_query($conexion, "UPDATE materia_prima SET stock_actual = stock_actual - $descuento WHERE ID = $id_mp");
            }
            mysqli_query($conexion, "UPDATE orden_fabrica SET inventario_descontado = 1 WHERE ID = $id_orden");
            mysqli_commit($conexion); echo json_encode(['success' => true]);
        } catch (Exception $e) { mysqli_rollback($conexion); echo json_encode(['success' => false, 'msg' => 'Error.']); } exit;
    }
}

// ================= ÓRDENES DE SERVICIO =================
if ($accion == 'listar_ordenes_servicio') {
    $q = "SELECT o.*, p.nombre as proveedor_nombre, p.tlf as proveedor_tlf FROM ordenes_servicio o LEFT JOIN provedor p ON o.id_proveedor = p.ID ORDER BY o.id DESC";
    $res = mysqli_query($conexion, $q); $data = []; if($res) { while ($row = mysqli_fetch_assoc($res)) { $data[] = $row; } }
    echo json_encode(['success' => true, 'data' => $data]); exit;
}
if ($accion == 'guardar_orden_servicio') {
    $id = intval($_POST['id_orden_servicio'] ?? 0); $id_proveedor = intval($_POST['id_proveedor']); $servicio = mysqli_real_escape_string($conexion, $_POST['servicio']); $costo = floatval($_POST['costo']); $fecha_orden = mysqli_real_escape_string($conexion, $_POST['fecha_orden']); $fecha_pago_limite = mysqli_real_escape_string($conexion, $_POST['fecha_pago_limite']);

    if ($id > 0) { $q = "UPDATE ordenes_servicio SET id_proveedor=$id_proveedor, servicio='$servicio', costo=$costo, fecha_orden='$fecha_orden', fecha_pago_limite='$fecha_pago_limite' WHERE id=$id"; } 
    else { $q = "INSERT INTO ordenes_servicio (id_proveedor, servicio, costo, fecha_orden, fecha_pago_limite) VALUES ($id_proveedor, '$servicio', $costo, '$fecha_orden', '$fecha_pago_limite')"; }
    if(mysqli_query($conexion, $q)) { echo json_encode(['success' => true]); } else { echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]); } exit;
}
if ($accion == 'eliminar_orden_servicio') {
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "DELETE FROM ordenes_servicio WHERE id=$id")) { echo json_encode(['success' => true]); } else { echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]); } exit;
}
if ($accion == 'pagar_orden_servicio') {
    $id = intval($_POST['id']);
    if(mysqli_query($conexion, "UPDATE ordenes_servicio SET estado_pago='Pagado', monto_pagado=costo WHERE id=$id")) { echo json_encode(['success' => true]); } else { echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]); } exit;
}
?>