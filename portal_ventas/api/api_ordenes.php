<?php
session_start();
error_reporting(0);
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}
require_once '../../conexion.php';
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$id_usuario = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;
session_write_close();

header('Content-Type: application/json; charset=utf-8');

if ($id_usuario === 0) {
    ob_clean();
    echo json_encode(['success' => false, 'msg' => 'Sesión expirada']);
    exit;
}

// Verificar columnas en orden_fabrica compatibles con MySQL 8
$q_col_fotos = mysqli_query($conexion, "SHOW COLUMNS FROM orden_fabrica LIKE 'fotos_diseno'");
$tiene_col_fotos_orden = ($q_col_fotos && mysqli_num_rows($q_col_fotos) > 0);
if (!$tiene_col_fotos_orden) {
    @mysqli_query($conexion, "ALTER TABLE orden_fabrica ADD COLUMN fotos_diseno TEXT DEFAULT NULL");
    $q_col_fotos2 = mysqli_query($conexion, "SHOW COLUMNS FROM orden_fabrica LIKE 'fotos_diseno'");
    $tiene_col_fotos_orden = ($q_col_fotos2 && mysqli_num_rows($q_col_fotos2) > 0);
}

$q_usr = mysqli_query($conexion, "SELECT permisos, rol FROM usuario WHERE ID = $id_usuario");
$datos_usr = mysqli_fetch_assoc($q_usr);
$permisos_arr = array_map('trim', explode(',', $datos_usr['permisos'] ?? ''));
$rol_actual = strtolower(trim($datos_usr['rol'] ?? ''));
$es_master = ($rol_actual === 'master');

function tienePermisoOrd($num) {
    global $permisos_arr, $es_master;
    return $es_master || in_array((string)$num, $permisos_arr, true);
}

function registrar_auditoria_ord($con, $id_usr, $accion, $tabla, $id_reg, $detalle) {
    $acc = mysqli_real_escape_string($con, $accion);
    $tab = mysqli_real_escape_string($con, $tabla);
    $det = mysqli_real_escape_string($con, $detalle);
    @mysqli_query($con, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usr, '$acc', '$tab', $id_reg, NOW(), '$det')");
}

function obtenerIdOrdenRequest(): int {
    return intval($_POST['id'] ?? $_POST['id_orden'] ?? $_POST['id_orden_fabrica'] ?? $_GET['id'] ?? $_GET['id_orden'] ?? 0);
}

function destruirOrdenYDependencias(mysqli $conexion, int $id_orden): bool {
    @mysqli_query($conexion, "SET FOREIGN_KEY_CHECKS = 0");

    $q_tr = @mysqli_query($conexion, "SELECT id FROM tracking_orden WHERE id_orden_fabrica = $id_orden");
    if ($q_tr) {
        while ($rt = mysqli_fetch_assoc($q_tr)) {
            $id_tr = intval($rt['id']);
            if ($id_tr > 0) {
                @mysqli_query($conexion, "DELETE FROM tracking_detalle WHERE id_tracking = $id_tr");
                @mysqli_query($conexion, "DELETE FROM tracking_etapa_fotos WHERE id_tracking = $id_tr");
            }
        }
    }

    @mysqli_query($conexion, "DELETE FROM tracking_orden WHERE id_orden_fabrica = $id_orden");
    @mysqli_query($conexion, "DELETE FROM medicion_personal WHERE id_orden_fabrica = $id_orden");
    @mysqli_query($conexion, "DELETE FROM diseno_asignaciones_orden WHERE id_orden = $id_orden");
    @mysqli_query($conexion, "DELETE FROM garantias_textiles WHERE id_orden = $id_orden");

    $ok = @mysqli_query($conexion, "DELETE FROM orden_fabrica WHERE ID = $id_orden");
    @mysqli_query($conexion, "SET FOREIGN_KEY_CHECKS = 1");

    return (bool)$ok;
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

// =========================================================================
// 1. LISTAR ÓRDENES DE FÁBRICA
// =========================================================================
if ($accion === 'listar_ordenes' || $accion === 'listar') {
    if (!tienePermisoOrd(10)) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Sin permiso para ver órdenes.']);
        exit;
    }

    $cliente_filtro = intval($_GET['cliente'] ?? 0);
    $ve_todas = tienePermisoOrd(54);

    $where = "WHERE 1=1";
    if (!$ve_todas) {
        $where .= " AND o.id_usuario_vendedor = $id_usuario";
    }
    if ($cliente_filtro > 0) {
        $where .= " AND o.id_cliente = $cliente_filtro";
    }

    $whereCli = $ve_todas ? "" : "WHERE o.id_usuario_vendedor = $id_usuario";
    $qCli = mysqli_query($conexion, "
        SELECT DISTINCT cli.ID, cli.nombre
        FROM orden_fabrica o
        JOIN cliente cli ON cli.ID = o.id_cliente
        $whereCli
        ORDER BY cli.nombre ASC
    ");
    $clientes = [];
    if ($qCli) {
        while ($rc = mysqli_fetch_assoc($qCli)) {
            $clientes[] = ['ID' => intval($rc['ID']), 'nombre' => $rc['nombre']];
        }
    }

    $sql = "SELECT o.*, c.nro_cotizacion,
                   cli.nombre AS cliente_nombre, cli.nombre AS cliente,
                   u.nombre AS v_nombre, u.apellido AS v_apellido
            FROM orden_fabrica o
            LEFT JOIN cotizacion c ON o.id_cotizacion = c.ID
            LEFT JOIN cliente cli ON o.id_cliente = cli.ID
            LEFT JOIN usuario u ON o.id_usuario_vendedor = u.ID
            $where
            ORDER BY o.ID DESC";

    $res = mysqli_query($conexion, $sql);
    $data = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $row['fecha_emision'] = !empty($row['fecha_emision']) ? date('d/m/Y', strtotime($row['fecha_emision'])) : '';
            $row['fecha_entrega'] = !empty($row['fecha_entrega']) ? date('d/m/Y', strtotime($row['fecha_entrega'])) : '';
            $row['cliente'] = $row['cliente_nombre'] ?? 'Cliente no asignado';
            $row['nombre_cliente'] = $row['cliente'];
            $row['vendedor_nombre'] = trim(($row['v_nombre'] ?? '') . ' ' . ($row['v_apellido'] ?? ''));
            if ($row['vendedor_nombre'] === '') $row['vendedor_nombre'] = 'No asignado';
            $row['vendedor'] = $row['vendedor_nombre'];
            $data[] = $row;
        }
    }

    ob_clean();
    echo json_encode(['success' => true, 'data' => $data, 'clientes' => $clientes]);
    exit;
}

// =========================================================================
// 2. ALERTAS DE PRODUCCIÓN: ESTRICTAMENTE SOLO LAS ENVIADAS A LOGÍSTICA (c.enviado_logistica = 1)
// =========================================================================
if (in_array($accion, [
    'obtener_cotizaciones_aprobadas',
    'cotizaciones_disponibles',
    'pendientes_produccion',
    'cotizaciones_pendientes',
    'obtener_pendientes',
    'alertas_produccion'
], true)) {
    // REGLA ESTRICTA: Solo mostrar cotizaciones donde el vendedor presionó "Enviar a Logística" (c.enviado_logistica = 1)
    // y que todavía NO tengan una orden de fábrica creada (o.ID IS NULL).
    $sql = "SELECT c.ID, c.nro_cotizacion, c.id_cliente, c.id_vendedor, c.estado_pago,
                   c.fecha_emision, c.total, c.notas, c.fotos_diseno,
                   c.enviado_logistica, c.prealerta_produccion, c.tipo_cotizacion,
                   cli.nombre AS cliente_nombre, cli.nombre AS cliente,
                   u.nombre AS v_nombre, u.apellido AS v_apellido
            FROM cotizacion c
            JOIN cliente cli ON c.id_cliente = cli.ID
            LEFT JOIN usuario u ON c.id_vendedor = u.ID
            LEFT JOIN orden_fabrica o ON c.ID = o.id_cotizacion
            WHERE o.ID IS NULL
              AND c.enviado_logistica = 1
              AND (c.pausado IS NULL OR c.pausado = 0)
            ORDER BY c.ID DESC";

    $res = mysqli_query($conexion, $sql);
    $data = [];

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $row['fecha_fmt'] = !empty($row['fecha_emision']) ? date('d/m/Y', strtotime($row['fecha_emision'])) : '';
            $row['vendedor_nombre'] = trim(($row['v_nombre'] ?? '') . ' ' . ($row['v_apellido'] ?? ''));
            if ($row['vendedor_nombre'] === '') $row['vendedor_nombre'] = 'Ventas';
            $row['vendedor'] = $row['vendedor_nombre'];
            $row['cliente'] = $row['cliente_nombre'];
            $row['es_enviada_logistica'] = 1;
            $data[] = $row;
        }
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => $data,
        'pendientes' => $data,
        'enviadas_logistica' => $data,
        'prealertas' => [],
        'cantidad' => count($data),
        'total' => count($data)
    ]);
    exit;
}

// =========================================================================
// 3. OBTENER CORRELATIVO DE NUEVA ORDEN (MISMO CÓDIGO FINAL DE LA COTIZACIÓN)
// =========================================================================
if ($accion === 'obtener_correlativo_orden') {
    $id_cot = intval($_GET['id_cotizacion'] ?? $_POST['id_cotizacion'] ?? 0);
    $nro = '';

    if ($id_cot > 0) {
        $q_c = mysqli_query($conexion, "SELECT nro_cotizacion FROM cotizacion WHERE ID = $id_cot LIMIT 1");
        if ($q_c && ($r_c = mysqli_fetch_assoc($q_c)) && !empty($r_c['nro_cotizacion'])) {
            if (preg_match('/(\d+)\s*$/', trim($r_c['nro_cotizacion']), $m)) {
                $nro = "OF-" . $m[1];
            }
        }
    }

    if ($nro === '') {
        $q = mysqli_query($conexion, "SELECT MAX(ID) AS max_id FROM orden_fabrica");
        $r = $q ? mysqli_fetch_assoc($q) : ['max_id' => 0];
        $sig = intval($r['max_id'] ?? 0) + 1;
        $nro = "OF-" . str_pad((string)$sig, 4, "0", STR_PAD_LEFT);
    }

    ob_clean();
    echo json_encode(['success' => true, 'nro' => $nro]);
    exit;
}

// =========================================================================
// 4. OBTENER DETALLES DE LA COTIZACIÓN PARA ARMAR LA ORDEN
// =========================================================================
if ($accion === 'obtener_detalles_para_orden') {
    $id_cot = intval($_GET['id_cotizacion'] ?? $_POST['id_cotizacion'] ?? 0);
    $q_cot = mysqli_query($conexion, "SELECT fotos_diseno, notas FROM cotizacion WHERE ID = $id_cot");
    $cot = $q_cot ? mysqli_fetch_assoc($q_cot) : [];

    $q = mysqli_query($conexion, "SELECT * FROM detalle_cotizacion WHERE id_cotizacion = $id_cot ORDER BY ID ASC");
    $data = [];
    $ficha_extraida = null;
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            if ($ficha_extraida === null && !empty($row['especificaciones_tallas'])) {
                $sp = json_decode($row['especificaciones_tallas'], true);
                if (is_array($sp) && !empty($sp[0]['_ficha_piloto'])) {
                    $ficha_extraida = $sp[0]['_ficha_piloto'];
                }
            }
            $data[] = $row;
        }
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => $data,
        'fotos_diseno' => $cot['fotos_diseno'] ?? '',
        'ficha_confeccion_json' => $ficha_extraida
    ]);
    exit;
}

// =========================================================================
// 5. GUARDAR NUEVA ORDEN DE FÁBRICA Y EMITIR PDF
// =========================================================================
if ($accion === 'guardar_orden' || $accion === 'crear') {
    if (!tienePermisoOrd(11)) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [11] para generar órdenes de fábrica.']);
        exit;
    }

    $id_cotizacion = intval($_POST['id_cotizacion'] ?? 0);
    $id_cliente = intval($_POST['id_cliente'] ?? 0);
    $id_vendedor = intval($_POST['id_vendedor'] ?? 0);
    $nro_orden = mysqli_real_escape_string($conexion, trim($_POST['nro_orden'] ?? ''));
    $fecha_emision = mysqli_real_escape_string($conexion, trim($_POST['fecha_emision'] ?? date('Y-m-d')));
    $fecha_entrega = mysqli_real_escape_string($conexion, trim($_POST['fecha_entrega'] ?? date('Y-m-d')));
    $acepta_parciales = intval($_POST['acepta_parciales'] ?? 1);
    $es_express = isset($_POST['es_express']) ? 1 : 0;
    $descripcion = mysqli_real_escape_string($conexion, trim($_POST['descripcion_general'] ?? $_POST['descripcion'] ?? ''));
    $fotos_existentes = trim($_POST['fotos_existentes'] ?? '');
    $ficha_raw = trim((string)($_POST['ficha_confeccion_json'] ?? ''));

    if ($id_cotizacion <= 0) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Selecciona una cotización válida para emitir la Orden de Fábrica.']);
        exit;
    }

    // Verificar que la cotización exista y que realmente haya sido enviada a logística (enviado_logistica = 1)
    $q_info_cot = mysqli_query($conexion, "SELECT id_cliente, id_vendedor, nro_cotizacion, fotos_diseno, enviado_logistica FROM cotizacion WHERE ID = $id_cotizacion LIMIT 1");
    $r_ic = $q_info_cot ? mysqli_fetch_assoc($q_info_cot) : null;
    if (!$r_ic) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'La cotización seleccionada no existe.']);
        exit;
    }
    if (intval($r_ic['enviado_logistica'] ?? 0) !== 1) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Esta cotización no ha sido enviada a Logística. Primero debes enviarla desde el Historial de Cotizaciones.']);
        exit;
    }

    if ($id_cliente <= 0) $id_cliente = intval($r_ic['id_cliente']);
    if ($id_vendedor <= 0) $id_vendedor = intval($r_ic['id_vendedor']);
    if ($fotos_existentes === '' && !empty($r_ic['fotos_diseno'])) {
        $fotos_existentes = $r_ic['fotos_diseno'];
    }
    if (!empty($r_ic['nro_cotizacion']) && preg_match('/(\d+)\s*$/', trim($r_ic['nro_cotizacion']), $mCod)) {
        $nro_orden = mysqli_real_escape_string($conexion, "OF-" . $mCod[1]);
    }

    if ($nro_orden === '') {
        $q_m = mysqli_query($conexion, "SELECT MAX(ID) AS max_id FROM orden_fabrica");
        $r_m = $q_m ? mysqli_fetch_assoc($q_m) : ['max_id' => 0];
        $nro_orden = "OF-" . str_pad((string)(intval($r_m['max_id'] ?? 0) + 1), 4, "0", STR_PAD_LEFT);
    }

    $q_dup = mysqli_query($conexion, "SELECT ID FROM orden_fabrica WHERE id_cotizacion = $id_cotizacion LIMIT 1");
    if ($q_dup && ($fila_dup = mysqli_fetch_assoc($q_dup))) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'id_orden_existente' => intval($fila_dup['ID']),
            'msg' => 'Esta cotización ya tiene una orden de fábrica registrada.'
        ]);
        exit;
    }

    $fotos_nuevas = [];
    $mapa_fotos_nuevas = [];
    if (isset($_FILES['fotos_diseno'])) {
        $dir_uploads = "../../images/ordenes/";
        if (!is_dir($dir_uploads)) @mkdir($dir_uploads, 0777, true);
        $total_archivos = is_array($_FILES['fotos_diseno']['name']) ? count($_FILES['fotos_diseno']['name']) : 0;
        $indice_cola = 0;
        for ($i = 0; $i < $total_archivos; $i++) {
            if ($_FILES['fotos_diseno']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['fotos_diseno']['name'][$i], PATHINFO_EXTENSION));
                $nombre_archivo = "ord_" . time() . "_" . rand(100, 999) . "_" . $i . "." . $ext;
                if (move_uploaded_file($_FILES['fotos_diseno']['tmp_name'][$i], $dir_uploads . $nombre_archivo)) {
                    $fotos_nuevas[] = $nombre_archivo;
                    $mapa_fotos_nuevas['nueva:' . $indice_cola] = 'guardada:' . $nombre_archivo;
                    $indice_cola++;
                }
            }
        }
    }

    $fotos_final = $fotos_existentes;
    if (!empty($fotos_nuevas)) {
        $nuevas_str = implode(',', $fotos_nuevas);
        $fotos_final = $fotos_final !== '' ? ($fotos_final . ',' . $nuevas_str) : $nuevas_str;
    }
    $fotos_final_sql = mysqli_real_escape_string($conexion, $fotos_final);
    if ($fotos_final !== '' && $id_cotizacion > 0) {
        mysqli_query($conexion, "UPDATE cotizacion SET fotos_diseno = '$fotos_final_sql' WHERE ID = $id_cotizacion");
    }

    if ($tiene_col_fotos_orden) {
        $sql_ins = "INSERT INTO orden_fabrica (
                        id_cliente, id_cotizacion, nro_orden_fabrica, fecha_emision, fecha_entrega,
                        es_express, id_usuario_vendedor, acepta_entregas_parciales, descripcion,
                        fotos_diseno, fecha_ingreso_orden
                    ) VALUES (
                        $id_cliente, $id_cotizacion, '$nro_orden', '$fecha_emision', '$fecha_entrega',
                        $es_express, $id_vendedor, $acepta_parciales, '$descripcion',
                        '$fotos_final_sql', '$fecha_emision'
                    )";
    } else {
        $sql_ins = "INSERT INTO orden_fabrica (
                        id_cliente, id_cotizacion, nro_orden_fabrica, fecha_emision, fecha_entrega,
                        es_express, id_usuario_vendedor, acepta_entregas_parciales, descripcion,
                        fecha_ingreso_orden
                    ) VALUES (
                        $id_cliente, $id_cotizacion, '$nro_orden', '$fecha_emision', '$fecha_entrega',
                        $es_express, $id_vendedor, $acepta_parciales, '$descripcion',
                        '$fecha_emision'
                    )";
    }

    if (!mysqli_query($conexion, $sql_ins)) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Error al crear la orden: ' . mysqli_error($conexion)]);
        exit;
    }

    $id_orden = mysqli_insert_id($conexion);
    @mysqli_query($conexion, "UPDATE cotizacion SET enviado_logistica = 1, prealerta_produccion = 2 WHERE ID = $id_cotizacion");

    if ($ficha_raw !== '') {
        $ficha_arr = json_decode($ficha_raw, true);
        if (is_array($ficha_arr)) {
            if (!empty($ficha_arr['foto_frente']) && isset($mapa_fotos_nuevas[$ficha_arr['foto_frente']])) {
                $ficha_arr['foto_frente'] = $mapa_fotos_nuevas[$ficha_arr['foto_frente']];
            }
            if (!empty($ficha_arr['foto_espalda']) && isset($mapa_fotos_nuevas[$ficha_arr['foto_espalda']])) {
                $ficha_arr['foto_espalda'] = $mapa_fotos_nuevas[$ficha_arr['foto_espalda']];
            }
            if (!empty($ficha_arr['fichas_por_prenda']) && is_array($ficha_arr['fichas_por_prenda'])) {
                foreach ($ficha_arr['fichas_por_prenda'] as $kFp => $vFp) {
                    if (!empty($vFp['foto_frente']) && isset($mapa_fotos_nuevas[$vFp['foto_frente']])) {
                        $ficha_arr['fichas_por_prenda'][$kFp]['foto_frente'] = $mapa_fotos_nuevas[$vFp['foto_frente']];
                    }
                    if (!empty($vFp['foto_espalda']) && isset($mapa_fotos_nuevas[$vFp['foto_espalda']])) {
                        $ficha_arr['fichas_por_prenda'][$kFp]['foto_espalda'] = $mapa_fotos_nuevas[$vFp['foto_espalda']];
                    }
                }
            }
            $obs_ficha = mysqli_real_escape_string($conexion, '[FICHA_JSON]' . json_encode($ficha_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            mysqli_query($conexion, "INSERT INTO medicion_personal (id_orden_fabrica, nombre_personal, hombro, busto_torax, cintura, cadera, largo_camisa, largo_brazo, largo_pantalon, tiro_total, ancho_muslo, ancho_rodilla, ancho_talon, observacion)
                                     VALUES ($id_orden, '__FICHA_PILOTO__', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '$obs_ficha')");
        }
    }

    $medidas_tallas = json_decode($_POST['medidas_tallas'] ?? '[]', true);
    if (is_array($medidas_tallas)) {
        foreach ($medidas_tallas as $mt) {
            $nom = mysqli_real_escape_string($conexion, trim($mt['nombre_personal'] ?? ''));
            $obs = mysqli_real_escape_string($conexion, trim($mt['observacion'] ?? ''));
            mysqli_query($conexion, "INSERT INTO medicion_personal (id_orden_fabrica, nombre_personal, hombro, busto_torax, cintura, cadera, largo_camisa, largo_brazo, largo_pantalon, tiro_total, ancho_muslo, ancho_rodilla, ancho_talon, observacion)
                                     VALUES ($id_orden, '$nom', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '$obs')");
        }
    }

    $medidas_sastreria = json_decode($_POST['medidas_sastreria'] ?? '[]', true);
    if (is_array($medidas_sastreria)) {
        foreach ($medidas_sastreria as $ms) {
            $nom = mysqli_real_escape_string($conexion, trim($ms['nombre_personal'] ?? ''));
            $hombro = floatval($ms['hombro'] ?? 0);
            $busto = floatval($ms['busto_torax'] ?? 0);
            $cintura = floatval($ms['cintura'] ?? 0);
            $cadera = floatval($ms['cadera'] ?? 0);
            $lcam = floatval($ms['largo_camisa'] ?? 0);
            $lbra = floatval($ms['largo_brazo'] ?? 0);
            $lpan = floatval($ms['largo_pantalon'] ?? 0);
            $tiro = floatval($ms['tiro_total'] ?? 0);
            $muslo = floatval($ms['ancho_muslo'] ?? 0);
            $rod = floatval($ms['ancho_rodilla'] ?? 0);
            $tal = floatval($ms['ancho_talon'] ?? 0);
            $obs = mysqli_real_escape_string($conexion, trim($ms['observacion'] ?? '[SASTRERIA]'));

            mysqli_query($conexion, "INSERT INTO medicion_personal (id_orden_fabrica, nombre_personal, hombro, busto_torax, cintura, cadera, largo_camisa, largo_brazo, largo_pantalon, tiro_total, ancho_muslo, ancho_rodilla, ancho_talon, observacion)
                                     VALUES ($id_orden, '$nom', $hombro, $busto, $cintura, $cadera, $lcam, $lbra, $lpan, $tiro, $muslo, $rod, $tal, '$obs')");
        }
    }

    registrar_auditoria_ord($conexion, $id_usuario, 'CREAR', 'orden_fabrica', $id_orden, "Emitió Orden de Fábrica $nro_orden (Cotización ID $id_cotizacion)");

    ob_clean();
    echo json_encode([
        'success' => true,
        'id_orden' => $id_orden,
        'id' => $id_orden,
        'nro_orden' => $nro_orden,
        'url_pdf' => "imprimir_orden.php?id=$id_orden"
    ]);
    exit;
}

// =========================================================================
// 6. OBTENER ORDEN COMPLETA PARA ALTERAR / EDITAR (VECTOR 53)
// =========================================================================
if ($accion === 'obtener_orden_completa' || $accion === 'obtener') {
    if (!tienePermisoOrd(53)) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [53] para alterar órdenes de fábrica.']);
        exit;
    }

    $id_orden = obtenerIdOrdenRequest();
    $sql = "SELECT o.*, c.nro_cotizacion, c.fotos_diseno AS fotos_cotizacion,
                   cli.nombre AS cliente_nombre,
                   u.nombre AS v_nombre, u.apellido AS v_apellido
            FROM orden_fabrica o
            LEFT JOIN cotizacion c ON o.id_cotizacion = c.ID
            LEFT JOIN cliente cli ON o.id_cliente = cli.ID
            LEFT JOIN usuario u ON o.id_usuario_vendedor = u.ID
            WHERE o.ID = $id_orden
            LIMIT 1";
    $q = mysqli_query($conexion, $sql);
    $orden = $q ? mysqli_fetch_assoc($q) : null;

    if (!$orden) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se encontró la orden de fábrica.']);
        exit;
    }

    $orden['vendedor_nombre'] = trim(($orden['v_nombre'] ?? '') . ' ' . ($orden['v_apellido'] ?? ''));
    if (empty($orden['fotos_diseno']) && !empty($orden['fotos_cotizacion'])) {
        $orden['fotos_diseno'] = $orden['fotos_cotizacion'];
    }

    $tallas = [];
    $sastreria = [];
    $ficha_guardada = null;

    $q_med = mysqli_query($conexion, "SELECT * FROM medicion_personal WHERE id_orden_fabrica = $id_orden ORDER BY ID ASC");
    if ($q_med) {
        while ($m = mysqli_fetch_assoc($q_med)) {
            $obs = $m['observacion'] ?? '';
            $nom_pers = trim($m['nombre_personal'] ?? '');

            if ($nom_pers === '__FICHA_PILOTO__' || strpos($obs, '[FICHA_JSON]') === 0) {
                $json_str = preg_replace('/^\[FICHA_JSON\]/', '', $obs);
                $dec = json_decode($json_str, true);
                if (is_array($dec)) $ficha_guardada = $dec;
                continue;
            }

            if (strpos($obs, '[TALLA:') !== false) {
                preg_match('/\[TALLA:(.*?)\|GEN:(.*?)\|CANT:(.*?)\|PRENDA:(.*?)(?:\|TELA:(.*?))?\]/', $obs, $match);
                $resto = trim(preg_replace('/\[TALLA:.*?\]/', '', $obs));
                $persona = '';
                $nota = '';
                if (preg_match('/Persona:\s*(.*?)\.(?:\s*Nota:\s*(.*))?$/i', $resto, $mPN)) {
                    $persona = trim($mPN[1] ?? '');
                    $nota = trim($mPN[2] ?? '');
                } elseif (stripos($resto, 'Nota:') === 0) {
                    $nota = trim(substr($resto, 5));
                } else {
                    $nota = $resto;
                }

                $tallas[] = [
                    'talla' => $match[1] ?? 'M',
                    'gen' => $match[2] ?? 'Caballero',
                    'cant' => intval($match[3] ?? 1),
                    'prenda' => $match[4] ?? 'Prenda',
                    'tela' => $match[5] ?? '',
                    'persona' => $persona,
                    'nota' => $nota
                ];
            } else {
                $sastreria[] = $m;
            }
        }
    }

    if ($ficha_guardada === null && !empty($orden['id_cotizacion'])) {
        $id_cot_v = intval($orden['id_cotizacion']);
        $q_dc = mysqli_query($conexion, "SELECT especificaciones_tallas FROM detalle_cotizacion WHERE id_cotizacion = $id_cot_v");
        if ($q_dc) {
            while ($rdc = mysqli_fetch_assoc($q_dc)) {
                if (!empty($rdc['especificaciones_tallas'])) {
                    $sp = json_decode($rdc['especificaciones_tallas'], true);
                    if (is_array($sp) && !empty($sp[0]['_ficha_piloto'])) {
                        $ficha_guardada = $sp[0]['_ficha_piloto'];
                        break;
                    }
                }
            }
        }
    }

    $orden['ficha_confeccion_json'] = $ficha_guardada;

    ob_clean();
    echo json_encode([
        'success' => true,
        'orden' => $orden,
        'data' => $orden,
        'tallas' => $tallas,
        'sastreria' => $sastreria
    ]);
    exit;
}

// =========================================================================
// 7. GUARDAR ALTERACIÓN DE ORDEN DE FÁBRICA EXISTENTE (VECTOR 53)
// =========================================================================
if ($accion === 'editar_orden_existente' || $accion === 'editar') {
    if (!tienePermisoOrd(53)) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [53] para alterar órdenes de fábrica.']);
        exit;
    }

    $id_orden = obtenerIdOrdenRequest();
    $fecha_entrega = mysqli_real_escape_string($conexion, trim($_POST['fecha_entrega'] ?? date('Y-m-d')));
    $acepta_parciales = intval($_POST['acepta_parciales'] ?? 1);
    $es_express = isset($_POST['es_express']) ? 1 : 0;
    $descripcion = mysqli_real_escape_string($conexion, trim($_POST['descripcion_general'] ?? $_POST['descripcion'] ?? ''));
    $fotos_existentes = trim($_POST['fotos_existentes'] ?? '');
    $ficha_raw = trim((string)($_POST['ficha_confeccion_json'] ?? ''));

    if ($id_orden <= 0) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'ID de orden inválido.']);
        exit;
    }

    $q_ord_curr = mysqli_query($conexion, "SELECT id_cotizacion, nro_orden_fabrica FROM orden_fabrica WHERE ID = $id_orden LIMIT 1");
    $ord_curr = $q_ord_curr ? mysqli_fetch_assoc($q_ord_curr) : null;
    if (!$ord_curr) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Orden no encontrada.']);
        exit;
    }
    $id_cot_vinc = intval($ord_curr['id_cotizacion'] ?? 0);

    $fotos_nuevas = [];
    $mapa_fotos_nuevas = [];
    if (isset($_FILES['fotos_diseno'])) {
        $dir_uploads = "../../images/ordenes/";
        if (!is_dir($dir_uploads)) @mkdir($dir_uploads, 0777, true);
        $total_archivos = is_array($_FILES['fotos_diseno']['name']) ? count($_FILES['fotos_diseno']['name']) : 0;
        $indice_cola = 0;
        for ($i = 0; $i < $total_archivos; $i++) {
            if ($_FILES['fotos_diseno']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['fotos_diseno']['name'][$i], PATHINFO_EXTENSION));
                $nombre_archivo = "ord_edit_" . time() . "_" . rand(100, 999) . "_" . $i . "." . $ext;
                if (move_uploaded_file($_FILES['fotos_diseno']['tmp_name'][$i], $dir_uploads . $nombre_archivo)) {
                    $fotos_nuevas[] = $nombre_archivo;
                    $mapa_fotos_nuevas['nueva:' . $indice_cola] = 'guardada:' . $nombre_archivo;
                    $indice_cola++;
                }
            }
        }
    }

    $fotos_final = $fotos_existentes;
    if (!empty($fotos_nuevas)) {
        $nuevas_str = implode(',', $fotos_nuevas);
        $fotos_final = $fotos_final !== '' ? ($fotos_final . ',' . $nuevas_str) : $nuevas_str;
    }
    $fotos_final_sql = mysqli_real_escape_string($conexion, $fotos_final);

    if ($id_cot_vinc > 0 && $fotos_final !== '') {
        mysqli_query($conexion, "UPDATE cotizacion SET fotos_diseno = '$fotos_final_sql' WHERE ID = $id_cot_vinc");
    }

    $sql_fotos_set = $tiene_col_fotos_orden ? ", fotos_diseno = '$fotos_final_sql'" : "";
    $sql_upd = "UPDATE orden_fabrica SET
                    fecha_entrega = '$fecha_entrega',
                    acepta_entregas_parciales = $acepta_parciales,
                    es_express = $es_express,
                    descripcion = '$descripcion'
                    $sql_fotos_set
                WHERE ID = $id_orden";

    if (!mysqli_query($conexion, $sql_upd)) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se pudo actualizar la orden: ' . mysqli_error($conexion)]);
        exit;
    }

    mysqli_query($conexion, "DELETE FROM medicion_personal WHERE id_orden_fabrica = $id_orden");

    if ($ficha_raw !== '') {
        $ficha_arr = json_decode($ficha_raw, true);
        if (is_array($ficha_arr)) {
            if (!empty($ficha_arr['foto_frente']) && isset($mapa_fotos_nuevas[$ficha_arr['foto_frente']])) {
                $ficha_arr['foto_frente'] = $mapa_fotos_nuevas[$ficha_arr['foto_frente']];
            }
            if (!empty($ficha_arr['foto_espalda']) && isset($mapa_fotos_nuevas[$ficha_arr['foto_espalda']])) {
                $ficha_arr['foto_espalda'] = $mapa_fotos_nuevas[$ficha_arr['foto_espalda']];
            }
            $obs_ficha = mysqli_real_escape_string($conexion, '[FICHA_JSON]' . json_encode($ficha_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            mysqli_query($conexion, "INSERT INTO medicion_personal (id_orden_fabrica, nombre_personal, hombro, busto_torax, cintura, cadera, largo_camisa, largo_brazo, largo_pantalon, tiro_total, ancho_muslo, ancho_rodilla, ancho_talon, observacion)
                                     VALUES ($id_orden, '__FICHA_PILOTO__', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '$obs_ficha')");
        }
    }

    $medidas_tallas = json_decode($_POST['medidas_tallas'] ?? '[]', true);
    if (is_array($medidas_tallas)) {
        foreach ($medidas_tallas as $mt) {
            $nom = mysqli_real_escape_string($conexion, trim($mt['nombre_personal'] ?? ''));
            $obs = mysqli_real_escape_string($conexion, trim($mt['observacion'] ?? ''));
            mysqli_query($conexion, "INSERT INTO medicion_personal (id_orden_fabrica, nombre_personal, hombro, busto_torax, cintura, cadera, largo_camisa, largo_brazo, largo_pantalon, tiro_total, ancho_muslo, ancho_rodilla, ancho_talon, observacion)
                                     VALUES ($id_orden, '$nom', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '$obs')");
        }
    }

    $medidas_sastreria = json_decode($_POST['medidas_sastreria'] ?? '[]', true);
    if (is_array($medidas_sastreria)) {
        foreach ($medidas_sastreria as $ms) {
            $nom = mysqli_real_escape_string($conexion, trim($ms['nombre_personal'] ?? ''));
            $hombro = floatval($ms['hombro'] ?? 0);
            $busto = floatval($ms['busto_torax'] ?? 0);
            $cintura = floatval($ms['cintura'] ?? 0);
            $cadera = floatval($ms['cadera'] ?? 0);
            $lcam = floatval($ms['largo_camisa'] ?? 0);
            $lbra = floatval($ms['largo_brazo'] ?? 0);
            $lpan = floatval($ms['largo_pantalon'] ?? 0);
            $tiro = floatval($ms['tiro_total'] ?? 0);
            $muslo = floatval($ms['ancho_muslo'] ?? 0);
            $rod = floatval($ms['ancho_rodilla'] ?? 0);
            $tal = floatval($ms['ancho_talon'] ?? 0);
            $obs = mysqli_real_escape_string($conexion, trim($ms['observacion'] ?? '[SASTRERIA]'));

            mysqli_query($conexion, "INSERT INTO medicion_personal (id_orden_fabrica, nombre_personal, hombro, busto_torax, cintura, cadera, largo_camisa, largo_brazo, largo_pantalon, tiro_total, ancho_muslo, ancho_rodilla, ancho_talon, observacion)
                                     VALUES ($id_orden, '$nom', $hombro, $busto, $cintura, $cadera, $lcam, $lbra, $lpan, $tiro, $muslo, $rod, $tal, '$obs')");
        }
    }

    registrar_auditoria_ord($conexion, $id_usuario, 'EDITAR', 'orden_fabrica', $id_orden, "Alteró Orden de Fábrica " . ($ord_curr['nro_orden_fabrica'] ?? ''));

    ob_clean();
    echo json_encode(['success' => true]);
    exit;
}

// =========================================================================
// 8. REVERTIR ORDEN A VENTAS (LIMPIA enviado_logistica = 0 Y prealerta_produccion = 0)
// =========================================================================
if ($accion === 'revertir_orden' || $accion === 'revertir' || $accion === 'devolver_ventas') {
    if (!tienePermisoOrd(43)) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [43] para revertir órdenes a ventas.']);
        exit;
    }

    $id_orden = obtenerIdOrdenRequest();
    if ($id_orden <= 0) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se recibió el ID de la orden a revertir.']);
        exit;
    }

    $q_ord = mysqli_query($conexion, "SELECT id_cotizacion, nro_orden_fabrica FROM orden_fabrica WHERE ID = $id_orden LIMIT 1");
    $ord = $q_ord ? mysqli_fetch_assoc($q_ord) : null;
    if (!$ord) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se encontró la orden para revertir.']);
        exit;
    }

    $id_cot = intval($ord['id_cotizacion'] ?? 0);
    if ($id_cot > 0) {
        // Devolver a Ventas SIN alerta hasta que el vendedor vuelva a enviarla a Logística
        @mysqli_query($conexion, "UPDATE cotizacion SET enviado_logistica = 0 WHERE ID = $id_cot");
        @mysqli_query($conexion, "UPDATE cotizacion SET prealerta_produccion = 0 WHERE ID = $id_cot");
    }

    if (destruirOrdenYDependencias($conexion, $id_orden)) {
        registrar_auditoria_ord($conexion, $id_usuario, 'REVERTIR', 'orden_fabrica', $id_orden, "Revirtió Orden " . ($ord['nro_orden_fabrica'] ?? '') . " a Ventas.");
        ob_clean();
        echo json_encode(['success' => true, 'msg' => 'Orden revertida a Ventas exitosamente. No generará alerta hasta que Ventas la vuelva a enviar a Logística.']);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Error al eliminar la orden durante la reversión: ' . mysqli_error($conexion)]);
    }
    exit;
}

// =========================================================================
// 9. ELIMINAR ORDEN DE FÁBRICA (LIMPIA enviado_logistica = 0 Y prealerta_produccion = 0)
// =========================================================================
if ($accion === 'eliminar_orden' || $accion === 'eliminar' || $accion === 'borrar') {
    if (!tienePermisoOrd(12)) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No tienes el Vector [12] para eliminar órdenes.']);
        exit;
    }

    $id_orden = obtenerIdOrdenRequest();
    if ($id_orden <= 0) {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'No se recibió el ID de la orden a eliminar.']);
        exit;
    }

    $q_ord = mysqli_query($conexion, "SELECT id_cotizacion, nro_orden_fabrica FROM orden_fabrica WHERE ID = $id_orden LIMIT 1");
    $ord = $q_ord ? mysqli_fetch_assoc($q_ord) : null;

    if (destruirOrdenYDependencias($conexion, $id_orden)) {
        if ($ord && intval($ord['id_cotizacion']) > 0) {
            $id_c = intval($ord['id_cotizacion']);
            @mysqli_query($conexion, "UPDATE cotizacion SET enviado_logistica = 0 WHERE ID = $id_c");
            @mysqli_query($conexion, "UPDATE cotizacion SET prealerta_produccion = 0 WHERE ID = $id_c");
        }
        registrar_auditoria_ord($conexion, $id_usuario, 'ELIMINAR', 'orden_fabrica', $id_orden, "Eliminó Orden de Fábrica " . ($ord['nro_orden_fabrica'] ?? ''));
        ob_clean();
        echo json_encode(['success' => true, 'msg' => 'Orden eliminada correctamente.']);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Error al eliminar la orden: ' . mysqli_error($conexion)]);
    }
    exit;
}
?>