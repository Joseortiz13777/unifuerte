<?php
session_start();
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json; charset=utf-8');
require_once '../../conexion.php';
require_once __DIR__ . '/codigo_cotizacion.php';

// CREAR COLUMNAS SI NO EXISTEN
@mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS igtf DECIMAL(10,2) DEFAULT 0.00");
@mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS metodo_entrega VARCHAR(255) DEFAULT ''");
@mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS tipo_cotizacion VARCHAR(50) DEFAULT 'ESTANDAR'");
@mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS enviado_logistica TINYINT(1) DEFAULT 0");
@mysqli_query($conexion, "ALTER TABLE detalle_cotizacion ADD COLUMN IF NOT EXISTS especificaciones_tallas TEXT DEFAULT NULL");
@mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS aplica_iva TINYINT(1) DEFAULT 1");
@mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS porcentaje_descuento_manual DECIMAL(5,2) DEFAULT 0.00");
@mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS servicio_logistico DECIMAL(10,2) DEFAULT 0.00");
@mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS flete DECIMAL(10,2) DEFAULT 0.00");

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesión expirada.']);
    exit;
}

$id_vendedor = intval($_SESSION['usuario_id']);

// COMPROBAR VECTORES DEL USUARIO PARA VALIDACIÓN DE SEGURIDAD BACKEND
$q_perm = mysqli_query($conexion, "SELECT permisos FROM usuario WHERE ID = $id_vendedor");
$perm_row = $q_perm ? mysqli_fetch_assoc($q_perm) : null;
$vectores_usuario = explode(',',$perm_row['permisos'] ?? '');

$tiene_edicion_licitacion = in_array('30',$vectores_usuario);
$tiene_descuento_manual = in_array('31',$vectores_usuario);
$tiene_exonerar_iva = in_array('32',$vectores_usuario);
$tiene_desc_logistica = in_array('33',$vectores_usuario);
$tiene_flete = in_array('34',$vectores_usuario);
$tiene_muestra_fisica = in_array('52',$vectores_usuario);

$id_edit = intval($_POST['id_edit_cotizacion'] ?? 0);$id_cliente = intval($_POST['id_cliente'] ?? 0);$contribuyente = intval($_POST['contribuyente_especial'] ?? 0);$id_forma_pago = intval($_POST['id_forma_pago'] ?? 0);$metodo_entrega = mysqli_real_escape_string($conexion, trim($_POST['metodo_entrega'] ?? ''));
$notas_texto = trim((string)($_POST['notas'] ?? ''));
$notas_palabras = preg_split('/\s+/u', $notas_texto, -1, PREG_SPLIT_NO_EMPTY);
if (count($notas_palabras) > 10) {
    echo json_encode(['success' => false, 'msg' => 'La descripción corta admite un máximo de 10 palabras.']);
    exit;
}
$notas = mysqli_real_escape_string($conexion, $notas_texto);
$carrito = json_decode($_POST['carrito'] ?? '[]', true);

$tipo_cotizacion = mysqli_real_escape_string($conexion, trim($_POST['tipo_cotizacion'] ?? 'ESTANDAR'));
$nro_cotizacion_manual = trim($_POST['nro_cotizacion'] ?? '');

if ($id_edit > 0) {
    $q_estado_edicion = mysqli_query($conexion, "
        SELECT c.enviado_logistica, o.ID AS id_orden
        FROM cotizacion c
        LEFT JOIN orden_fabrica o ON o.id_cotizacion = c.ID
        WHERE c.ID = $id_edit
        LIMIT 1
    ");
    $estado_edicion = $q_estado_edicion ? mysqli_fetch_assoc($q_estado_edicion) : null;
    if (!$estado_edicion) {
        echo json_encode(['success' => false, 'msg' => 'No se encontró la cotización que intentas editar.']);
        exit;
    }
    if (intval($estado_edicion['enviado_logistica']) !== 0 || !empty($estado_edicion['id_orden'])) {
        echo json_encode([
            'success' => false,
            'msg' => 'Esta cotización ya fue enviada a logística o tiene una orden de fábrica. Reviértela primero para evitar que la orden quede distinta de la cotización.'
        ]);
        exit;
    }
}

// Capturar parámetros financieros
$aplica_iva = isset($_POST['aplica_iva']) ? intval($_POST['aplica_iva']) : 1;
$porcentaje_descuento_manual = isset($_POST['porcentaje_descuento_manual']) ? floatval($_POST['porcentaje_descuento_manual']) : 0.00;
$servicio_logistico = isset($_POST['descuento_logistica']) ? floatval($_POST['descuento_logistica']) : 0.00;
$flete = isset($_POST['flete']) ? floatval($_POST['flete']) : 0.00;

if (!$tiene_exonerar_iva) {$aplica_iva = 1; } 
if (!$tiene_descuento_manual) {$porcentaje_descuento_manual = 0.00; } 
if (!$tiene_desc_logistica) {$servicio_logistico = 0.00; }
if (!$tiene_flete) {$flete = 0.00; }

if ($id_edit > 0 && $tipo_cotizacion === 'LICITACION' && !$tiene_edicion_licitacion) {
    echo json_encode(['success' => false, 'msg' => 'Seguridad: No tienes el Vector [30] para editar cotizaciones especiales.']);
    exit;
}

if ($tipo_cotizacion === 'MUESTRA' && !$tiene_muestra_fisica) {
    echo json_encode(['success' => false, 'msg' => 'Seguridad: No tienes el Vector [52] para solicitar Muestras Físicas.']);
    exit;
}

if ($id_cliente === 0 || !is_array($carrito) || empty($carrito)) {
    echo json_encode(['success' => false, 'msg' => 'Falta el cliente o no has agregado productos al carrito.']);
    exit;
}

if ($tipo_cotizacion === 'ESTANDAR' && ($id_forma_pago === 0 || empty($metodo_entrega))) {
    echo json_encode(['success' => false, 'msg' => 'Para cotizaciones Estandar, el método de pago y de entrega son obligatorios.']);
    exit;
}

if ($tipo_cotizacion === 'LICITACION' && empty($nro_cotizacion_manual)) {
    echo json_encode(['success' => false, 'msg' => 'Debe ingresar manualmente un Número de Cotización Especial.']);
    exit;
}

// ================= CÁLCULOS MATEMÁTICOS LIMPIOS =================
$sub_total_bruto = 0;
$cantidad_renglones = count($carrito);

foreach ($carrito as$item) { 
    $cant = intval($item['cantidad'] ?? ($item['quantity'] ?? 1));$precio = floatval($item['precio'] ?? ($item['precio_unitario'] ?? ($item['finalPrice'] ?? 0)));$sub_linea = $cant * $precio;
    $sub_total_bruto +=$sub_linea; 
}

// Si es Muestra Física, forzamos todos los montos a $0.00
if ($tipo_cotizacion === 'MUESTRA') {$sub_total_bruto = 0;
    $monto_descuento = 0;
    $servicio_logistico = 0;
    $flete = 0;
    $base_imponible = 0;
    $iva = 0;
    $igtf = 0;
    $total = 0;
    $estado_pago = 'Muestra Física';
} else {
    // 1. Descuento (%) aplicado sobre el Subtotal Bruto
    $monto_descuento = $sub_total_bruto * ($porcentaje_descuento_manual / 100);

    // 2. Base Imponible (Subtotal - Descuento + Servicio Logístico + Flete)
    $base_imponible = $sub_total_bruto -$monto_descuento + $servicio_logistico +$flete;
    if ($base_imponible < 0)$base_imponible = 0;

    $nombre_pago = '';
    if ($id_forma_pago > 0) {
        $q_mp = mysqli_query($conexion, "SELECT forma_pago FROM cotizacion_formas_pago WHERE id = $id_forma_pago");
        if ($q_mp && ($r_mp = mysqli_fetch_assoc($q_mp))) {
            $nombre_pago =$r_mp['forma_pago'];
        }
    }

    // 3. IVA (16%) y IGTF (3%) calculados estrictamente sobre la Base Imponible
    $iva = $aplica_iva ? ($base_imponible * 0.16) : 0;
    $igtf = 0;
    if (strpos(strtolower($nombre_pago), 'divisa') !== false) {
        $igtf =$base_imponible * 0.03;
    }

    $total =$base_imponible + $iva +$igtf;
    $estado_pago = 'sin pagar';
}

$total_formateado = number_format($total, 2, ',', '.');

/**
 * Función auxiliar para validar que id_producto exista en la tabla `productos`
 * y evitar fallos de llave foránea (fk_detalle_cotizacion_producto).
 */
function obtener_id_producto_sql(mysqli $conexion,$id_p_raw) {
    $id_p = intval($id_p_raw);
    if ($id_p <= 0) {
        return 'NULL';
    }
    $chk_p = mysqli_query($conexion, "SELECT id FROM productos WHERE id = $id_p LIMIT 1");
    if ($chk_p && mysqli_num_rows($chk_p) > 0) {
        return (string)$id_p;
    }
    return 'NULL';
}

if ($id_edit > 0) {
    // ACTUALIZACIÓN
    $sql_update_nro = "";
    if ($tipo_cotizacion === 'LICITACION') {
        if (!validar_codigo_cotizacion($nro_cotizacion_manual)) {
            echo json_encode(['success' => false, 'msg' => 'El número debe tener el formato #UNI-AAAA-001, por ejemplo #UNI-2026-202.']);
            exit;
        }
        $nro_cotizacion_manual_sql = mysqli_real_escape_string($conexion,$nro_cotizacion_manual);
        $chk = mysqli_query($conexion, "SELECT ID FROM cotizacion WHERE nro_cotizacion='$nro_cotizacion_manual_sql' AND ID !=$id_edit");
        if (!$chk) {
            echo json_encode(['success' => false, 'msg' => 'No se pudo validar el número de cotización: ' . mysqli_error($conexion)]);
            exit;
        }
        if (mysqli_num_rows($chk) > 0) {
            echo json_encode(['success' => false, 'msg' => 'Ese número de cotización ya está registrado en el sistema.']);
            exit;
        }
        $sql_update_nro = "nro_cotizacion='$nro_cotizacion_manual_sql', ";
    }

    $sql_c = "UPDATE cotizacion SET 
              $sql_update_nro
              id_cliente=$id_cliente, id_forma_pago=" . ($id_forma_pago > 0 ? $id_forma_pago : 'NULL') . ", contribuyente_especial=$contribuyente, 
              notas='$notas', metodo_entrega='$metodo_entrega', 
              sub_total=$sub_total_bruto, descuento=$monto_descuento, iva=$iva, igtf=$igtf, total=$total,
              tipo_cotizacion='$tipo_cotizacion', aplica_iva=$aplica_iva, porcentaje_descuento_manual=$porcentaje_descuento_manual,
              servicio_logistico=$servicio_logistico, flete=$flete
              WHERE ID=$id_edit";

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $transaccion_iniciada = false;
    try {
        mysqli_begin_transaction($conexion);
        $transaccion_iniciada = true;
        $q_estado_bloqueado = mysqli_query($conexion, "SELECT enviado_logistica FROM cotizacion WHERE ID = $id_edit FOR UPDATE");
        $estado_bloqueado = mysqli_fetch_assoc($q_estado_bloqueado);
        $q_orden_bloqueada = mysqli_query($conexion, "SELECT ID FROM orden_fabrica WHERE id_cotizacion = $id_edit LIMIT 1");
        if (!$estado_bloqueado || intval($estado_bloqueado['enviado_logistica']) !== 0 || mysqli_fetch_assoc($q_orden_bloqueada)) {
            throw new RuntimeException('Esta cotización ya fue enviada a logística o tiene una orden. Revierte la orden antes de editarla.');
        }
        mysqli_query($conexion, $sql_c);

        // Repercutir el cambio de cliente en la orden de fábrica si existe
        mysqli_query($conexion, "UPDATE orden_fabrica SET id_cliente = $id_cliente WHERE id_cotizacion =$id_edit");

        $specs_previas = [];
        $specs_previas_producto = [];
        $q_prev = mysqli_query($conexion, "SELECT ID, id_producto, especificaciones_tallas FROM detalle_cotizacion WHERE id_cotizacion=$id_edit AND especificaciones_tallas IS NOT NULL");
        while ($rp = mysqli_fetch_assoc($q_prev)) {
            $specs_previas[intval($rp['ID'])] = $rp['especificaciones_tallas'];
            $specs_previas_producto[intval($rp['id_producto'])][] = $rp['especificaciones_tallas'];
        }

        mysqli_query($conexion, "DELETE FROM detalle_cotizacion WHERE id_cotizacion=$id_edit");

        foreach ($carrito as$item) {
            $id_p = intval($item['id_producto'] ?? ($item['id'] ?? 0));$id_p_sql = obtener_id_producto_sql($conexion,$id_p);
            $nombre_prenda =$item['nombre'] ?? ($item['tipo_prenda'] ?? ($item['descripcion'] ?? ($item['originalName'] ?? '')));$tipo_prenda = mysqli_real_escape_string($conexion,$nombre_prenda);
            $genero = mysqli_real_escape_string($conexion, $item['genero'] ?? 'No especificado');$cantidad = intval($item['cantidad'] ?? ($item['quantity'] ?? 1));
            $precio =$tipo_cotizacion === 'MUESTRA' ? 0 : floatval($item['precio'] ?? ($item['precio_unitario'] ?? ($item['finalPrice'] ?? 0)));$subtotal_linea = $cantidad * $precio;
            
            $talla_g = $item['talla_guardada'] ?? '';$tela_g = $item['tela_guardada'] ?? '';$detalle_raw = $item['detalle_diseno'] ?? '';
            if (stripos($detalle_raw, 'Ubicación:') === false) {
                $detalle_raw = preg_replace('/\[Para:.*?\]\s*-\s*/', '', $detalle_raw);
            }
            
            if (empty($detalle_raw) && (!empty($talla_g) || !empty($tela_g))) {$detalle_raw = "Talla: " . ($talla_g ?: 'N/A') . " \vert{} Tela: " . ($tela_g ?: 'N/A');
            }
            $detalle_diseno = mysqli_real_escape_string($conexion,$detalle_raw);

            $spec_sql = "NULL";
            if (isset($item['specs']) && !empty($item['specs'])) {$spec_str = mysqli_real_escape_string($conexion, json_encode($item['specs'], JSON_UNESCAPED_UNICODE));
                $spec_sql = "'$spec_str'";
            } elseif (isset($specs_previas[intval($item['id_detalle'] ?? 0)])) {
                $spec_str = mysqli_real_escape_string($conexion, $specs_previas[intval($item['id_detalle'])]);
                $spec_sql = "'$spec_str'";
            } elseif (isset($specs_previas_producto[$id_p]) && count($specs_previas_producto[$id_p]) === 1) {
                $spec_str = mysqli_real_escape_string($conexion, $specs_previas_producto[$id_p][0]);
                $spec_sql = "'$spec_str'";
            }

            mysqli_query($conexion, "INSERT INTO detalle_cotizacion (id_cotizacion, id_producto, tipo_prenda, genero, cantidad, precio_unitario, subtotal_linea, detalle_diseno, especificaciones_tallas) 
                                     VALUES ($id_edit, $id_p_sql, '$tipo_prenda', '$genero',$cantidad, $precio,$subtotal_linea, '$detalle_diseno',$spec_sql)");
        }
        
        $q_nro = mysqli_query($conexion, "SELECT nro_cotizacion FROM cotizacion WHERE ID=$id_edit");
        $nro = ($q_nro && ($r_nro = mysqli_fetch_assoc($q_nro))) ? $r_nro['nro_cotizacion'] :$nro_cotizacion_manual;
        $detalle_auditoria = mysqli_real_escape_string($conexion, "Alteró la cotización $nro. Modificada con $cantidad_renglones renglón(es). Total ajustado a: $$total_formateado");
        mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_vendedor, 'EDITAR', 'cotizacion', $id_edit, NOW(), '$detalle_auditoria')");

        mysqli_commit($conexion);
        $transaccion_iniciada = false;
        mysqli_report(MYSQLI_REPORT_OFF);
        echo json_encode(['success' => true, 'nro_cotizacion' => $nro, 'id_cotizacion' => $id_edit, 'tipo' =>$tipo_cotizacion]);
    } catch (Throwable $e) {
        if ($transaccion_iniciada) {
            mysqli_rollback($conexion);
        }
        mysqli_report(MYSQLI_REPORT_OFF);
        error_log('Error al editar cotización ' . $id_edit . ': ' . $e->getMessage());
        echo json_encode(['success' => false, 'msg' => $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar la cotización. No se aplicaron cambios; intenta nuevamente.']);
    }

} else {
    // CREACIÓN NUEVA
    $reserva_codigo = reservar_codigo_cotizacion($conexion,
        $tipo_cotizacion === 'LICITACION' ?$nro_cotizacion_manual : null
    );
    if (!$reserva_codigo['success']) {
        echo json_encode(['success' => false, 'msg' => $reserva_codigo['msg']]);
        exit;
    }
    $nro_cotizacion =$reserva_codigo['codigo'];

    $sql_c = "INSERT INTO cotizacion (id_cliente, id_vendedor, id_forma_pago, metodo_entrega, nro_cotizacion, contribuyente_especial, notas, sub_total, descuento, iva, igtf, total, estado_pago, tipo_cotizacion, aplica_iva, porcentaje_descuento_manual, servicio_logistico, flete) 
              VALUES ($id_cliente,$id_vendedor, " . ($id_forma_pago > 0 ?$id_forma_pago : 'NULL') . ", '$metodo_entrega', '$nro_cotizacion', $contribuyente, '$notas', $sub_total_bruto, $monto_descuento,$iva, $igtf,$total, '$estado_pago', '$tipo_cotizacion', $aplica_iva,$porcentaje_descuento_manual, $servicio_logistico,$flete)";

    $insertado = mysqli_query($conexion,$sql_c);
    // IMPORTANTE: Capturar el ID ANTES de liberar el bloqueo para que SELECT RELEASE_LOCK no reinicie mysqli_insert_id a 0
    $id_cotizacion = $insertado ? intval(mysqli_insert_id($conexion)) : 0;
    $error_insert =$insertado ? '' : mysqli_error($conexion);$errno_insert = $insertado ? 0 : mysqli_errno($conexion);

    if ($insertado && $id_cotizacion <= 0) {$nro_esc = mysqli_real_escape_string($conexion,$nro_cotizacion);
        $q_id = mysqli_query($conexion, "SELECT ID FROM cotizacion WHERE nro_cotizacion = '$nro_esc' ORDER BY ID DESC LIMIT 1");
        if ($q_id && ($r_id = mysqli_fetch_assoc($q_id))) {
            $id_cotizacion = intval($r_id['ID']);
        }
    }

    liberar_bloqueo_codigo_cotizacion($conexion,$reserva_codigo['bloqueo']);

    if ($insertado &&$id_cotizacion > 0) {
        foreach ($carrito as$item) {
            $id_p = intval($item['id_producto'] ?? ($item['id'] ?? 0));$id_p_sql = obtener_id_producto_sql($conexion,$id_p);
            $nombre_prenda =$item['nombre'] ?? ($item['tipo_prenda'] ?? ($item['descripcion'] ?? ($item['originalName'] ?? '')));$tipo_prenda = mysqli_real_escape_string($conexion,$nombre_prenda);
            $genero = mysqli_real_escape_string($conexion, $item['genero'] ?? 'No especificado');$cantidad = intval($item['cantidad'] ?? ($item['quantity'] ?? 1));
            $precio =$tipo_cotizacion === 'MUESTRA' ? 0 : floatval($item['precio'] ?? ($item['precio_unitario'] ?? ($item['finalPrice'] ?? 0)));$subtotal_linea = $cantidad * $precio;
            
            $talla_g = $item['talla_guardada'] ?? '';$tela_g = $item['tela_guardada'] ?? '';$detalle_raw = $item['detalle_diseno'] ?? '';
            if (stripos($detalle_raw, 'Ubicación:') === false) {
                $detalle_raw = preg_replace('/\[Para:.*?\]\s*-\s*/', '', $detalle_raw);
            }

            if (empty($detalle_raw) && (!empty($talla_g) || !empty($tela_g))) {$detalle_raw = "Talla: " . ($talla_g ?: 'N/A') . " \vert{} Tela: " . ($tela_g ?: 'N/A');
            }
            $detalle_diseno = mysqli_real_escape_string($conexion,$detalle_raw);

            $spec_sql = "NULL";
            if (isset($item['specs']) && !empty($item['specs'])) {$spec_str = mysqli_real_escape_string($conexion, json_encode($item['specs'], JSON_UNESCAPED_UNICODE));
                $spec_sql = "'$spec_str'";
            }

            mysqli_query($conexion, "INSERT INTO detalle_cotizacion (id_cotizacion, id_producto, tipo_prenda, genero, cantidad, precio_unitario, subtotal_linea, detalle_diseno, especificaciones_tallas) 
                                     VALUES ($id_cotizacion, $id_p_sql, '$tipo_prenda', '$genero',$cantidad, $precio,$subtotal_linea, '$detalle_diseno',$spec_sql)");
        }
        
        $detalle_auditoria = mysqli_real_escape_string($conexion, "Emitió nueva " . strtolower($tipo_cotizacion) . " $nro_cotizacion ($cantidad_renglones renglones). Total: $$total_formateado");
        mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_vendedor, 'CREAR', 'cotizacion', $id_cotizacion, NOW(), '$detalle_auditoria')");

        echo json_encode(['success' => true, 'nro_cotizacion' => $nro_cotizacion, 'id_cotizacion' => $id_cotizacion, 'tipo' =>$tipo_cotizacion]);
    } else {
        $mensaje =$errno_insert === 1062
            ? 'Ese número de cotización ya está registrado en el sistema. Intente nuevamente.'
            : ($error_insert ?: 'No se pudo obtener el ID de la cotización creada.');
        echo json_encode(['success' => false, 'msg' => $mensaje]);
    }
}
?>