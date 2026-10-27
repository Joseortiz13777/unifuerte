<?php
ob_start();
session_start();
error_reporting(0); 
$id_usuario = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;
session_write_close(); 

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../conexion.php';
    if (!$conexion) throw new Exception("Fallo de conexion a la BD.");
    mysqli_set_charset($conexion, "utf8mb4");

    if ($id_usuario === 0) throw new Exception("Sesion expirada.");

    // Asegurar columna cuenta_por_cobrar en cotizacion para órdenes enviadas sin pago por el Master
    mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS cuenta_por_cobrar TINYINT(1) NOT NULL DEFAULT 0");

    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    $mes_num = isset($_POST['mes']) ? (int)$_POST['mes'] : (int)date('n'); 
    $anio_num = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $tasa_actual_global = (float)($_POST['tasa_bcv'] ?? 0);

    function limpiarUTF8($str) { return mb_convert_encoding($str ?? '', 'UTF-8', 'auto'); }

    function obtenerTasaHistorica($conexion, $fecha, $tasa_actual) {
        $fecha = $fecha ?? date('Y-m-d');
        $q_t = mysqli_query($conexion, "
            SELECT tasa_bcv FROM (
                SELECT tasa_bcv, fecha FROM finanzas_movimientos WHERE tasa_bcv > 10
                UNION ALL
                SELECT tasa_bcv, fecha_pago AS fecha FROM nomina_lotes WHERE tasa_bcv > 10
                UNION ALL
                SELECT tasa_bcv, fecha_pago AS fecha FROM pago_gastos_servicios WHERE tasa_bcv > 10
                UNION ALL
                SELECT tasa_bcv, fecha_pago AS fecha FROM pago_compra WHERE tasa_bcv > 10
            ) AS tasas 
            WHERE fecha <= '$fecha' 
            ORDER BY fecha DESC LIMIT 1
        ");
        if ($q_t && $r_t = mysqli_fetch_assoc($q_t)) { return (float)$r_t['tasa_bcv']; }
        return $tasa_actual > 10 ? $tasa_actual : 842.40; 
    }

    if ($accion === 'get_ultima_tasa') {
        $fecha = date('Y-m-d');
        $q_t = mysqli_query($conexion, "
            SELECT tasa_bcv FROM (
                SELECT tasa_bcv, fecha FROM finanzas_movimientos WHERE tasa_bcv > 10
                UNION ALL
                SELECT tasa_bcv, fecha_pago AS fecha FROM nomina_lotes WHERE tasa_bcv > 10
                UNION ALL
                SELECT tasa_bcv, fecha_pago AS fecha FROM pago_gastos_servicios WHERE tasa_bcv > 10
                UNION ALL
                SELECT tasa_bcv, fecha_pago AS fecha FROM pago_compra WHERE tasa_bcv > 10
            ) AS tasas 
            WHERE fecha <= '$fecha' 
            ORDER BY fecha DESC LIMIT 1
        ");
        $tasa = 842.40; 
        if ($q_t && $r_t = mysqli_fetch_assoc($q_t)) { 
            $tasa = (float)$r_t['tasa_bcv']; 
        }
        echo json_encode(['success' => true, 'tasa' => $tasa]);
        exit;
    }

    if ($accion === 'get_resumen_principal') {
        $desglose_cotizaciones = 0; $desglose_nomina = 0; $desglose_compras = 0;
        $desglose_ingresos_man = 0; $desglose_gastos_man = 0;
        $por_cobrar = 0; $por_pagar = 0;
        
        $q_cot = mysqli_query($conexion, "SELECT nro_cotizacion, total, estado_pago, pago_valor_usd, pago_fecha, enviado_logistica, cuenta_por_cobrar, tipo_cotizacion FROM cotizacion WHERE pausado = 0 AND estado_pago != 'Anulado'");
        if($q_cot) {
            while ($row = mysqli_fetch_assoc($q_cot)) {
                $total_cot = (float)$row['total'];
                $estado = strtolower(trim($row['estado_pago']));
                $es_muestra = strtoupper(trim($row['tipo_cotizacion'] ?? '')) === 'MUESTRA';
                $pago_usd_raw = preg_replace('/[^0-9.,|]/', '', str_replace(['vert{}', 'ert{}', ' | '], '|', $row['pago_valor_usd'] ?? ''));
                $pago_fecha_raw = preg_replace('/[^0-9\/|]/', '', str_replace(['vert{}', 'ert{}', ' | '], '|', $row['pago_fecha'] ?? ''));
                
                $arr_montos = explode('|', $pago_usd_raw); $arr_fechas = explode('|', $pago_fecha_raw); $total_abonado = 0;
                foreach ($arr_montos as $i => $m_str) {
                    if (trim($m_str) !== '') {
                        $monto = (float)str_replace(',', '.', trim($m_str));
                        $fecha = trim($arr_fechas[$i] ?? '');
                        $total_abonado += $monto;
                        if ($mes_num == 0) { $desglose_cotizaciones += $monto; } 
                        else if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha, $m)) {
                            if ((int)$m[3] === $anio_num && (int)$m[2] === $mes_num) { $desglose_cotizaciones += $monto; }
                        }
                    }
                }
                $es_exigible = !$es_muestra && $estado !== 'pagado' && (
                    in_array($estado, ['pago inicial', 'pago medio'], true)
                    || intval($row['enviado_logistica'] ?? 0) === 1
                    || intval($row['cuenta_por_cobrar'] ?? 0) === 1
                );
                if ($es_exigible) {
                    $deuda = $total_cot - $total_abonado;
                    if ($deuda > 0) $por_cobrar += $deuda;
                }
            }
        }

        $q_com = mysqli_query($conexion, "SELECT ID, costo, monto_pagado, fecha_compra, estado_pago, moneda FROM compra WHERE estado_pago != 'Anulado'");
        if($q_com) {
            while ($row = mysqli_fetch_assoc($q_com)) {
                $moneda = strtoupper(trim($row['moneda'] ?? 'USD'));
                $pagado = (float)$row['monto_pagado']; $costo = (float)$row['costo']; $estado = strtolower(trim($row['estado_pago']));
                
                if ($moneda === 'BS' || $moneda === 'VES') {
                    $tasa_hist = obtenerTasaHistorica($conexion, $row['fecha_compra'], $tasa_actual_global);
                    $costo_usd = $costo / $tasa_hist; $pagado_usd = $pagado / $tasa_hist;
                } else { $costo_usd = $costo; $pagado_usd = $pagado; }

                if ($estado !== 'pagado') { $deuda_usd = $costo_usd - $pagado_usd; if ($deuda_usd > 0) $por_pagar += $deuda_usd; }
                if ($mes_num == 0) { $desglose_compras += $costo_usd; } 
                else if(!empty($row['fecha_compra'])) {
                    $ts = strtotime($row['fecha_compra']);
                    if ((int)date('Y', $ts) === $anio_num && (int)date('n', $ts) === $mes_num) { $desglose_compras += $costo_usd; }
                }
            }
        }

        $q_os = mysqli_query($conexion, "SELECT costo, monto_pagado, estado_pago, COALESCE(fecha_pago_real, fecha_orden) as fecha_referencia FROM ordenes_servicio WHERE estado_pago != 'Anulado'");
        if($q_os) {
            while ($row = mysqli_fetch_assoc($q_os)) {
                $pagado = (float)$row['monto_pagado']; $costo = (float)$row['costo']; $estado = strtolower(trim($row['estado_pago']));
                if ($estado !== 'pagado') { $deuda = $costo - $pagado; if ($deuda > 0) $por_pagar += $deuda; }
                if ($pagado > 0) {
                    if ($mes_num == 0) { $desglose_gastos_man += $pagado; } 
                    else if(!empty($row['fecha_referencia'])) {
                        $ts = strtotime($row['fecha_referencia']);
                        if ((int)date('Y', $ts) === $anio_num && (int)date('n', $ts) === $mes_num) { $desglose_gastos_man += $pagado; }
                    }
                }
            }
        }

        $q_nom = mysqli_query($conexion, "SELECT total_usd, monto_pagado, fecha_pago, estado FROM nomina_lotes");
        if($q_nom) {
            while ($row = mysqli_fetch_assoc($q_nom)) {
                $tot = (float)$row['total_usd']; $pag = (float)$row['monto_pagado'];
                if (strtolower(trim($row['estado'])) !== 'finalizada') {
                    $deuda = $tot - $pag; if($deuda > 0) $por_pagar += $deuda;
                } else {
                    if ($mes_num == 0) { $desglose_nomina += $tot; } 
                    else if(!empty($row['fecha_pago'])) {
                        $ts = strtotime($row['fecha_pago']);
                        if ((int)date('Y', $ts) === $anio_num && (int)date('n', $ts) === $mes_num) { $desglose_nomina += $tot; }
                    }
                }
            }
        }

        $q_gas = mysqli_query($conexion, "SELECT monto_total, monto_pagado, estado_pago FROM gastos_servicios WHERE estado_pago != 'Anulado'");
        if($q_gas) {
            while ($row = mysqli_fetch_assoc($q_gas)) {
                if (strtolower(trim($row['estado_pago'])) !== 'pagado') {
                    $deuda = (float)$row['monto_total'] - (float)$row['monto_pagado']; if ($deuda > 0) $por_pagar += $deuda;
                }
            }
        }

        $q_pgas = mysqli_query($conexion, "SELECT monto_usd, fecha_pago FROM pago_gastos_servicios");
        if($q_pgas) {
            while ($row = mysqli_fetch_assoc($q_pgas)) {
                $pagado = (float)$row['monto_usd'];
                if ($mes_num == 0) { $desglose_gastos_man += $pagado; }
                else if(!empty($row['fecha_pago'])) {
                    $ts = strtotime($row['fecha_pago']);
                    if ((int)date('Y', $ts) === $anio_num && (int)date('n', $ts) === $mes_num) { $desglose_gastos_man += $pagado; }
                }
            }
        }

        $filtro_fecha = ($mes_num == 0) ? "1=1" : "MONTH(fecha) = $mes_num AND YEAR(fecha) = $anio_num";
        $q_mov = mysqli_query($conexion, "SELECT monto, tipo FROM finanzas_movimientos WHERE $filtro_fecha");
        if ($q_mov) {
            while ($row = mysqli_fetch_assoc($q_mov)) {
                if ($row['tipo'] === 'GASTO') $desglose_gastos_man += (float)$row['monto'];
                else if ($row['tipo'] === 'INGRESO') $desglose_ingresos_man += (float)$row['monto'];
            }
        }

        $cuentas_saldos = [];
        $q_cta = mysqli_query($conexion, "SELECT * FROM finanzas_cuentas ORDER BY moneda DESC");
        if($q_cta) {
            while($r = mysqli_fetch_assoc($q_cta)) {
                $r['nombre'] = limpiarUTF8($r['nombre']); $r['nro_cuenta'] = limpiarUTF8($r['nro_cuenta'] ?? ''); $cuentas_saldos[] = $r;
            }
        }

        echo json_encode(['success' => true, 'por_cobrar' => round($por_cobrar, 2), 'por_pagar' => round($por_pagar, 2), 'desglose_cotizaciones' => round($desglose_cotizaciones, 2), 'desglose_nomina' => round($desglose_nomina, 2), 'desglose_compras' => round($desglose_compras, 2), 'desglose_gastos_man' => round($desglose_gastos_man, 2), 'desglose_ingresos_man' => round($desglose_ingresos_man, 2), 'ingresos_mes' => round($desglose_cotizaciones + $desglose_ingresos_man, 2), 'gastos_mes' => round($desglose_nomina + $desglose_compras + $desglose_gastos_man, 2), 'cuentas' => $cuentas_saldos], JSON_INVALID_UTF8_SUBSTITUTE); exit;
    }

    if ($accion === 'get_lista_cobrar') {
        $lista = [];
        // Incluye cotizaciones con abono parcial O cotizaciones enviadas a logística sin pago por autorización del Master
        $sql = "SELECT c.ID, c.nro_cotizacion, cli.nombre as cliente, c.sub_total, c.descuento, c.iva, c.total,
                       c.estado_pago, c.pago_valor_usd, cli.tlf as telefono, c.referencias_pago, c.fotos_pagos,
                       c.pago_fecha, c.pago_tasa_bcv, c.pago_banco, c.tipo_cotizacion, c.enviado_logistica, c.cuenta_por_cobrar
                FROM cotizacion c
                JOIN cliente cli ON c.id_cliente = cli.ID 
                WHERE c.pausado = 0
                  AND UPPER(TRIM(COALESCE(c.tipo_cotizacion, ''))) != 'MUESTRA'
                  AND LOWER(TRIM(c.estado_pago)) NOT IN ('pagado', 'anulado')
                  AND (
                      LOWER(TRIM(c.estado_pago)) IN ('pago inicial', 'pago medio')
                      OR c.enviado_logistica IN (1, 2)
                      OR c.cuenta_por_cobrar = 1
                  )
                ORDER BY c.ID DESC";
        $query = mysqli_query($conexion, $sql);
        
        if($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                $total_cot = (float)$row['total']; 
                $pago_usd_raw = preg_replace('/[^0-9.,|]/', '', str_replace(['vert{}', 'ert{}', ' | '], '|', $row['pago_valor_usd'] ?? ''));
                $arr_montos = explode('|', $pago_usd_raw); $total_abonado = 0;
                
                foreach ($arr_montos as $m_str) { 
                    if (trim($m_str) !== '') $total_abonado += (float)str_replace(',', '.', trim($m_str)); 
                }
                
                $deuda = $total_cot - $total_abonado;
                
                if ($deuda > 0.01) { 
                    $lista[] = [
                        'id' => $row['ID'],
                        'cotizacion' => limpiarUTF8($row['nro_cotizacion']), 
                        'cliente' => limpiarUTF8($row['cliente']), 
                        'deuda' => round($deuda, 2), 
                        'telefono' => limpiarUTF8($row['telefono']),
                        'sub_total' => (float)$row['sub_total'],
                        'descuento' => (float)$row['descuento'],
                        'iva' => (float)$row['iva'],
                        'total' => $total_cot,
                        'referencias' => limpiarUTF8($row['referencias_pago'] ?? ''),
                        'fotos' => limpiarUTF8($row['fotos_pagos'] ?? ''),
                        'fecha' => limpiarUTF8($row['pago_fecha'] ?? ''),
                        'tasa' => limpiarUTF8($row['pago_tasa_bcv'] ?? ''),
                        'usd' => limpiarUTF8($row['pago_valor_usd'] ?? ''),
                        'banco' => limpiarUTF8($row['pago_banco'] ?? ''),
                        'tipo_cot' => limpiarUTF8($row['tipo_cotizacion'] ?? ''),
                        'sin_abono_master' => ($total_abonado <= 0) ? 1 : 0
                    ]; 
                }
            }
        }
        echo json_encode(['success' => true, 'data' => $lista], JSON_INVALID_UTF8_SUBSTITUTE); exit;
    }
    
    // Obtener las filas o items del ojito mágico
    if ($accion === 'get_detalles_cotizacion') {
        $id_cotizacion = intval($_GET['id_cotizacion'] ?? 0);
        $data = []; 
        $q = mysqli_query($conexion, "SELECT * FROM detalle_cotizacion WHERE id_cotizacion = $id_cotizacion");
        while ($row = mysqli_fetch_assoc($q)) {
            $data[] = [
                'tipo_prenda' => limpiarUTF8($row['tipo_prenda']),
                'genero' => limpiarUTF8($row['genero']),
                'detalle_diseno' => limpiarUTF8($row['detalle_diseno']),
                'cantidad' => $row['cantidad'],
                'precio_unitario' => (float)$row['precio_unitario'],
                'subtotal_linea' => (float)$row['subtotal_linea']
            ];
        }
        echo json_encode(['success' => true, 'data' => $data], JSON_INVALID_UTF8_SUBSTITUTE); exit;
    }
    
    if ($accion === 'get_lista_pagar') {
        $lista = [];
        
        $q_nom = mysqli_query($conexion, "
            SELECT np.id as id_bd, np.id_lote, ne.nombre, ne.apellido, ne.cargo, ne.departamento, np.total_percibir, np.total_pagado 
            FROM nomina_pagos np 
            JOIN nomina_empleados ne ON np.id_empleado = ne.id 
            WHERE np.estado != 'Pagado'
        ");
        if($q_nom) {
            while ($row = mysqli_fetch_assoc($q_nom)) { 
                $deuda = (float)$row['total_percibir'] - (float)$row['total_pagado'];
                if ($deuda > 0) {
                    $lista[] = [
                        'id_bd' => $row['id_bd'], 
                        'tipo_bd' => 'nomina_empleado', 
                        'documento' => limpiarUTF8($row['nombre'] . ' ' . $row['apellido']), 
                        'proveedor' => limpiarUTF8($row['cargo'] . ' (' . $row['departamento'] . ')'), 
                        'deuda' => round($deuda, 2), 
                        'deuda_usd' => round($deuda, 2), 
                        'tipo' => 'Lote #00' . $row['id_lote'], 
                        'telefono' => '', 
                        'moneda' => 'USD'
                    ]; 
                }
            }
        }

        $q_com = mysqli_query($conexion, "SELECT c.ID as id_bd, c.nro_factura, p.nombre as proveedor, p.tlf as telefono, c.costo, c.monto_pagado, c.moneda, c.fecha_compra,
            (SELECT GROUP_CONCAT(pc.id ORDER BY pc.id ASC SEPARATOR ',') FROM pago_compra pc WHERE pc.id_compra = c.ID) as pago_ids
            FROM compra c LEFT JOIN provedor p ON p.ID = c.id_provedor WHERE c.estado_pago != 'Pagado' AND c.estado_pago != 'Anulado'");
        if($q_com) {
            while ($row = mysqli_fetch_assoc($q_com)) {
                $moneda = strtoupper(trim($row['moneda'] ?? 'USD'));
                $costo = (float)$row['costo'];
                $pagado = (float)$row['monto_pagado'];
                if ($moneda === 'BS' || $moneda === 'VES') {
                    $tasa_hist = obtenerTasaHistorica($conexion, $row['fecha_compra'], $tasa_actual_global);
                    $deuda_usd = ($costo - $pagado) / $tasa_hist;
                } else {
                    $deuda_usd = $costo - $pagado;
                }

                if ($deuda_usd > 0) {
                    $lista[] = ['id_bd' => $row['id_bd'], 'tipo_bd' => 'compra', 'documento' => 'Compra ' . $row['nro_factura'], 'proveedor' => limpiarUTF8($row['proveedor']), 'deuda' => round($deuda_usd, 2), 'deuda_usd' => round($deuda_usd, 2), 'tipo' => 'Insumo Textil', 'telefono' => limpiarUTF8($row['telefono']), 'moneda' => 'USD', 'pago_ids' => $row['pago_ids'] ?? ''];
                }
            }
        }

        $q_gas = mysqli_query($conexion, "SELECT g.id as id_bd, g.factura, p.nombre as proveedor, p.tlf as telefono, g.monto_total, g.monto_pagado,
            (SELECT GROUP_CONCAT(pg.id ORDER BY pg.id ASC SEPARATOR ',') FROM pago_gastos_servicios pg WHERE pg.id_gasto = g.id) as pago_ids
            FROM gastos_servicios g LEFT JOIN provedor p ON p.ID = g.id_proveedor WHERE g.estado_pago != 'Pagado' AND g.estado_pago != 'Anulado'");
        if($q_gas) {
            while ($row = mysqli_fetch_assoc($q_gas)) {
                $deuda = (float)$row['monto_total'] - (float)$row['monto_pagado'];
                if ($deuda > 0) $lista[] = ['id_bd' => $row['id_bd'], 'tipo_bd' => 'gasto', 'documento' => 'Gasto Adm. ' . $row['factura'], 'proveedor' => limpiarUTF8($row['proveedor']), 'deuda' => round($deuda, 2), 'deuda_usd' => round($deuda, 2), 'tipo' => 'Gasto Operativo', 'telefono' => limpiarUTF8($row['telefono']), 'moneda' => 'USD', 'pago_ids' => $row['pago_ids'] ?? ''];
            }
        }
        
        $q_columna_metodos = mysqli_query($conexion, "SHOW COLUMNS FROM ordenes_servicio LIKE 'metodo_pago'");
        $tieneColumnaMetodos = $q_columna_metodos && mysqli_num_rows($q_columna_metodos) > 0;
        $selectMetodosPago = $tieneColumnaMetodos ? ', o.metodo_pago' : ", '' AS metodo_pago";
        $q_os = mysqli_query($conexion, "SELECT o.id as id_bd, p.nombre as proveedor, p.tlf as telefono, o.costo, o.monto_pagado$selectMetodosPago FROM ordenes_servicio o LEFT JOIN provedor p ON p.ID = o.id_proveedor WHERE o.estado_pago != 'Pagado' AND o.estado_pago != 'Anulado'");
        if($q_os) {
            while ($row = mysqli_fetch_assoc($q_os)) {
                $deuda = (float)$row['costo'] - (float)$row['monto_pagado'];
                if ($deuda > 0) {
                    $cantidadPagos = count(array_filter(explode('|', (string)($row['metodo_pago'] ?? ''))));
                    $lista[] = ['id_bd' => $row['id_bd'], 'tipo_bd' => 'os', 'documento' => 'Orden de Servicio OS-' . $row['id_bd'], 'proveedor' => limpiarUTF8($row['proveedor']), 'deuda' => round($deuda, 2), 'deuda_usd' => round($deuda, 2), 'tipo' => 'Servicio Contratado', 'telefono' => limpiarUTF8($row['telefono']), 'moneda' => 'USD', 'cantidad_pagos' => $cantidadPagos];
                }
            }
        }

        usort($lista, function($a, $b) { return $b['deuda_usd'] <=> $a['deuda_usd']; });
        echo json_encode(['success' => true, 'data' => $lista], JSON_INVALID_UTF8_SUBSTITUTE); exit;
    }

    if ($accion === 'pagar_deuda_dashboard') {
        $id_deuda = intval($_POST['id_deuda']);
        $tipo_deuda = mysqli_real_escape_string($conexion, trim($_POST['tipo_deuda']));
        $monto_pagar = (float)$_POST['monto_pagar']; 
        $tasa_bcv = (float)($_POST['tasa_bcv'] ?? 0);
        $metodo = mysqli_real_escape_string($conexion, trim($_POST['metodo_pago']));
        $ref = mysqli_real_escape_string($conexion, trim($_POST['referencia'] ?? 'S/N'));
        if ($id_deuda <= 0 || $monto_pagar <= 0 || !is_finite($monto_pagar)) {
            echo json_encode(['success' => false, 'msg' => 'Indica una deuda y un monto de pago válidos.']); exit;
        }
        $pago_id_recibo = 0;
        $pago_numero_recibo = 0;
        
        $fecha_pago_sql = date('Y-m-d'); $fecha_visual = date('d/m/Y');
        $archivos_nombres = [];
        if (isset($_FILES['comprobante'])) {
            $directorio = '../../inventario/uploads/gastos/'; 
            if (!is_dir($directorio)) mkdir($directorio, 0777, true);
            $total_archivos = count($_FILES['comprobante']['name']);
            for ($i = 0; $i < $total_archivos; $i++) {
                if ($_FILES['comprobante']['error'][$i] == UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['comprobante']['name'][$i], PATHINFO_EXTENSION);
                    $nombre_archivo = 'pago_univ_' . time() . '_' . rand(100,999) . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($_FILES['comprobante']['tmp_name'][$i], $directorio . $nombre_archivo)) { $archivos_nombres[] = $nombre_archivo; }
                }
            }
        }
        $string_archivos = implode(',', $archivos_nombres);

        if ($tipo_deuda === 'nomina_empleado') {
            $q = mysqli_query($conexion, "SELECT id_lote, total_percibir, total_pagado, comprobante_foto, metodo_pago, referencia FROM nomina_pagos WHERE id = $id_deuda");
            if($n = mysqli_fetch_assoc($q)){
                $nuevo_pagado = (float)$n['total_pagado'] + $monto_pagar;
                $estado_nuevo = ($nuevo_pagado >= ((float)$n['total_percibir'] - 0.01)) ? 'Pagado' : 'Pendiente';
                
                $detalle_metodo = $metodo . " ($" . number_format($monto_pagar, 2, '.', '') . " el " . $fecha_visual . " Tasa: " . $tasa_bcv . ")";
                $nuevo_metodo = empty($n['metodo_pago']) ? $detalle_metodo : $n['metodo_pago'] . " | " . $detalle_metodo;
                $nueva_ref = empty($n['referencia']) ? $ref : $n['referencia'] . " | " . $ref;
                $nuevo_comp = empty($n['comprobante_foto']) ? $string_archivos : (empty($string_archivos) ? $n['comprobante_foto'] : $n['comprobante_foto'] . ',' . $string_archivos);
                
                mysqli_query($conexion, "UPDATE nomina_pagos SET total_pagado = $nuevo_pagado, estado = '$estado_nuevo', metodo_pago = '$nuevo_metodo', referencia = '$nueva_ref', comprobante_foto = '$nuevo_comp', fecha_pago = '$fecha_pago_sql', tasa_bcv = $tasa_bcv WHERE id = $id_deuda");

                $id_lote = (int)$n['id_lote'];
                $q_sum = mysqli_query($conexion, "SELECT SUM(total_pagado) as sum_pagado FROM nomina_pagos WHERE id_lote = $id_lote");
                $sum_pagado = mysqli_fetch_assoc($q_sum)['sum_pagado'] ?? 0;
                mysqli_query($conexion, "UPDATE nomina_lotes SET monto_pagado = $sum_pagado, estado = IF($sum_pagado >= total_usd - 0.01, 'Finalizada', 'Pendiente') WHERE id = $id_lote");
            }
        } elseif ($tipo_deuda === 'os') {
            $q_os = mysqli_query($conexion, "SELECT costo, monto_pagado, metodo_pago, referencia, comprobante_pago FROM ordenes_servicio WHERE id = $id_deuda");
            if($os = mysqli_fetch_assoc($q_os)){
                $nuevo_pagado = (float)$os['monto_pagado'] + $monto_pagar;
                $estado_nuevo = ($nuevo_pagado >= ((float)$os['costo'] - 0.01)) ? 'Pagado' : 'Pago Parcial';

                $detalle_metodo = $metodo . " ($" . number_format($monto_pagar, 2, '.', '') . " el " . $fecha_visual . " Tasa: " . $tasa_bcv . ")";
                $nuevo_metodo = empty($os['metodo_pago']) ? $detalle_metodo : $os['metodo_pago'] . " | " . $detalle_metodo;
                $pago_numero_recibo = count(array_filter(explode('|', (string)($os['metodo_pago'] ?? '')))) + 1;
                $nueva_ref = empty($os['referencia']) ? $ref : $os['referencia'] . " | " . $ref;
                $nuevo_comp = empty($os['comprobante_pago']) ? $string_archivos : (empty($string_archivos) ? $os['comprobante_pago'] : $os['comprobante_pago'] . ',' . $string_archivos);
                
                mysqli_query($conexion, "UPDATE ordenes_servicio SET estado_pago = '$estado_nuevo', monto_pagado = $nuevo_pagado, fecha_pago_real = '$fecha_pago_sql', metodo_pago = '$nuevo_metodo', referencia = '$nueva_ref', comprobante_pago = '$nuevo_comp' WHERE id = $id_deuda");
            }
        } elseif ($tipo_deuda === 'gasto') {
            $q = mysqli_query($conexion, "SELECT monto_total, monto_pagado FROM gastos_servicios WHERE id = $id_deuda");
            if($g = mysqli_fetch_assoc($q)){
                $nuevo_pagado = (float)$g['monto_pagado'] + $monto_pagar;
                $estado_nuevo = ($nuevo_pagado >= ((float)$g['monto_total'] - 0.01)) ? 'Pagado' : 'Pago Parcial';
                
                $monto_bs = $monto_pagar * $tasa_bcv;
                mysqli_query($conexion, "INSERT INTO pago_gastos_servicios (id_gasto, fecha_pago, monto_usd, monto_bs, tasa_bcv, metodo_pago, referencia, comprobante) VALUES ($id_deuda, '$fecha_pago_sql', $monto_pagar, $monto_bs, $tasa_bcv, '$metodo', '$ref', '$string_archivos')");
                $pago_id_recibo = mysqli_insert_id($conexion);
                mysqli_query($conexion, "UPDATE gastos_servicios SET monto_pagado = $nuevo_pagado, estado_pago = '$estado_nuevo' WHERE id = $id_deuda");
            }
        } elseif ($tipo_deuda === 'compra') {
            $q = mysqli_query($conexion, "SELECT costo, monto_pagado, moneda, fecha_compra FROM compra WHERE ID = $id_deuda");
            if($c = mysqli_fetch_assoc($q)){
                $moneda = strtoupper(trim($c['moneda'] ?? 'USD'));
                $monto_usd = $monto_pagar;
                $monto_bs = $monto_pagar * $tasa_bcv;
                
                if ($moneda === 'BS' || $moneda === 'VES') {
                    $tasa_hist = obtenerTasaHistorica($conexion, $c['fecha_compra'], $tasa_bcv);
                    $abono_nativo = $monto_usd * $tasa_hist;
                    $nuevo_pagado = (float)$c['monto_pagado'] + $abono_nativo;
                } else {
                    $nuevo_pagado = (float)$c['monto_pagado'] + $monto_usd;
                }
                
                $estado_nuevo = ($nuevo_pagado >= ((float)$c['costo'] - 0.5)) ? 'Pagado' : 'Pago Parcial';
                
                mysqli_query($conexion, "INSERT INTO pago_compra (id_compra, fecha_pago, monto_usd, monto_bs, tasa_bcv, metodo_pago, referencia, comprobante) VALUES ($id_deuda, '$fecha_pago_sql', $monto_usd, $monto_bs, $tasa_bcv, '$metodo', '$ref', '$string_archivos')");
                $pago_id_recibo = mysqli_insert_id($conexion);
                mysqli_query($conexion, "UPDATE compra SET monto_pagado = $nuevo_pagado, estado_pago = '$estado_nuevo' WHERE ID = $id_deuda");
            }
        }
        $reciboUrl = '';
        if ($pago_id_recibo > 0 && in_array($tipo_deuda, ['gasto', 'compra'], true)) {
            $reciboUrl = 'documentos/recibo_pago.php?tipo=' . rawurlencode($tipo_deuda) . '&id=' . $id_deuda . '&pago_id=' . $pago_id_recibo;
        } elseif ($pago_numero_recibo > 0 && $tipo_deuda === 'os') {
            $reciboUrl = 'documentos/recibo_pago.php?tipo=os&id=' . $id_deuda . '&pago_numero=' . $pago_numero_recibo;
        }
        echo json_encode(['success' => true, 'recibo_url' => $reciboUrl]); exit;
    }

    if ($accion === 'get_proveedores') {
        $q = mysqli_query($conexion, "SELECT ID, nombre FROM provedor ORDER BY nombre ASC");
        $data = [];
        if($q) while($r = mysqli_fetch_assoc($q)) { $r['nombre'] = limpiarUTF8($r['nombre']); $data[] = $r; }
        echo json_encode(['success' => true, 'data' => $data], JSON_INVALID_UTF8_SUBSTITUTE); exit;
    }

    if ($accion === 'crear_cuenta_pagar') {
        $id_proveedor = (int)$_POST['id_proveedor'];
        $fecha = mysqli_real_escape_string($conexion, trim($_POST['fecha']));
        $factura = mysqli_real_escape_string($conexion, trim($_POST['factura']));
        $monto_total = (float)$_POST['monto_total'];
        $detalles_json = mysqli_real_escape_string($conexion, $_POST['detalles_json']);

        $sql = "INSERT INTO gastos_servicios (id_proveedor, fecha, factura, descripcion, monto_total, monto_pagado, estado_pago)
                VALUES ($id_proveedor, '$fecha', '$factura', '$detalles_json', $monto_total, 0.00, 'Sin Pagar')";

        if(mysqli_query($conexion, $sql)) { echo json_encode(['success' => true]); } 
        else { echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]); }
        exit;
    }

    // =====================================
    // OBTENER DATOS DEL GASTO PARA EDITAR
    // =====================================
    if ($accion === 'get_gasto_editar') {
        $id = intval($_POST['id']);
        $tipo = $_POST['tipo'];

        $q_prov = mysqli_query($conexion, "SELECT ID, nombre FROM provedor ORDER BY nombre ASC");
        $proveedores = [];
        while($r = mysqli_fetch_assoc($q_prov)) { $r['nombre'] = limpiarUTF8($r['nombre']); $proveedores[] = $r; }

        $data = [];
        if ($tipo === 'gasto') {
            $q = mysqli_query($conexion, "SELECT id_proveedor, fecha, factura, descripcion, monto_total FROM gastos_servicios WHERE id = $id");
            if ($r = mysqli_fetch_assoc($q)) {
                $data = $r;
                $items = json_decode($r['descripcion'], true);
                if (!is_array($items)) {
                    $items = [['descripcion' => limpiarUTF8($r['descripcion']), 'costo' => (float)$r['monto_total']]];
                } else {
                    foreach($items as &$item) {
                        $item['descripcion'] = limpiarUTF8($item['descripcion'] ?? $item['desc'] ?? $item['servicio'] ?? 'Servicio');
                        $item['costo'] = (float)$item['costo'];
                    }
                }
                $data['items'] = $items;
            }
        } else if ($tipo === 'os') {
            $q = mysqli_query($conexion, "SELECT id_proveedor, fecha_orden as fecha, id as factura, servicio as descripcion, costo as monto_total FROM ordenes_servicio WHERE id = $id");
            if ($r = mysqli_fetch_assoc($q)) {
                $data = $r;
                $data['factura'] = 'OS-'.$r['factura'];
                $items = json_decode($r['descripcion'], true);
                if (!is_array($items)) {
                    $items = [['descripcion' => limpiarUTF8($r['descripcion']), 'costo' => (float)$r['monto_total']]];
                } else {
                    foreach($items as &$item) {
                        $item['descripcion'] = limpiarUTF8($item['desc'] ?? $item['servicio'] ?? $item['descripcion'] ?? 'Servicio');
                        $item['costo'] = (float)$item['costo'];
                    }
                }
                $data['items'] = $items;
            }
        }
        
        echo json_encode(['success' => true, 'proveedores' => $proveedores, 'data' => $data], JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    // =====================================
    // GUARDAR LA EDICIÓN DEL GASTO
    // =====================================
    if ($accion === 'editar_cuenta_pagar') {
        $id = intval($_POST['id_gasto']);
        $tipo = $_POST['tipo_gasto'];
        $id_proveedor = (int)$_POST['id_proveedor'];
        $fecha = mysqli_real_escape_string($conexion, trim($_POST['fecha']));
        $factura = mysqli_real_escape_string($conexion, trim($_POST['factura']));
        $monto_total = (float)$_POST['monto_total'];
        $detalles_json = mysqli_real_escape_string($conexion, $_POST['detalles_json']);

        if ($tipo === 'gasto') {
            $sql = "UPDATE gastos_servicios SET id_proveedor = $id_proveedor, fecha = '$fecha', factura = '$factura', descripcion = '$detalles_json', monto_total = $monto_total WHERE id = $id";
        } else if ($tipo === 'os') {
            $items = json_decode($_POST['detalles_json'], true);
            $os_items = [];
            foreach($items as $i) {
                $os_items[] = ['desc' => $i['descripcion'], 'costo' => $i['costo']];
            }
            $os_json = mysqli_real_escape_string($conexion, json_encode($os_items));
            $sql = "UPDATE ordenes_servicio SET id_proveedor = $id_proveedor, fecha_orden = '$fecha', servicio = '$os_json', costo = $monto_total WHERE id = $id";
        }

        if(mysqli_query($conexion, $sql)) { echo json_encode(['success' => true]); } 
        else { echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]); }
        exit;
    }

    // =====================================
    // EL OJITO - OBTENER DETALLES DE LA DEUDA
    // =====================================
    if ($accion === 'get_detalles_deuda') {
        $id_deuda = intval($_POST['id']);
        $tipo = $_POST['tipo'];
        $items = [];
        $info = ['doc' => '', 'proveedor' => '', 'total' => 0, 'pagado' => 0, 'resta' => 0, 'moneda' => 'USD'];
        $comprobantes_adjuntos = [];
        $pagos_historial = [];

        $add_comps = function(&$arr, $str) {
            if(!$str) return;
            $pts = explode(',', $str);
            foreach($pts as $p) {
                $p = trim($p);
                if($p) {
                    if (strpos($p, 'os_pago_') !== false || strpos($p, 'pago_gasto_') !== false || strpos($p, 'pago_univ_') !== false) {
                        $ruta = '../inventario/uploads/gastos/' . $p;
                    } elseif (strpos($p, 'pago_') !== false) {
                        $ruta = '../inventario/uploads/compras/' . $p;
                    } elseif (strpos($p, 'aval_') !== false) {
                        $ruta = '../recursos_humanos/uploads/nomina/' . $p; 
                    } else {
                        $ruta = '../inventario/uploads/gastos/' . $p;
                    }
                    $arr[] = ['ruta' => $ruta, 'nombre' => $p];
                }
            }
        };

        if ($tipo === 'nomina_empleado') {
            $q = mysqli_query($conexion, "SELECT np.*, ne.nombre, ne.apellido FROM nomina_pagos np JOIN nomina_empleados ne ON np.id_empleado = ne.id WHERE np.id = $id_deuda");
            if($r = mysqli_fetch_assoc($q)){
                $info['doc'] = 'Nomina: ' . limpiarUTF8($r['nombre'] . ' ' . $r['apellido']); 
                $info['proveedor'] = 'Lote #00' . $r['id_lote']; 
                $info['total'] = (float)$r['total_percibir'];
                $info['pagado'] = (float)$r['total_pagado'];
                $info['resta'] = max(0, $info['total'] - $info['pagado']);

                $items[] = ['desc' => 'Sueldo + Asignaciones (Quincena)', 'cant' => 1, 'precio' => $info['total'], 'sub' => $info['total']];
                $add_comps($comprobantes_adjuntos, $r['comprobante_foto']);
                
                if (!empty($r['metodo_pago'])) {
                    $metodos = explode('|', $r['metodo_pago']);
                    $refs = explode('|', $r['referencia']);
                    foreach($metodos as $idx => $str) {
                        $str = trim($str);
                        if(!$str) continue;
                        if(preg_match('/^(.*?)\s\(\$([0-9.,]+)\sel\s([0-9\/\-]+)(?:\sTasa:\s([0-9.,]+))?.*?\)$/', $str, $matches)) {
                            $pagos_historial[] = ['fecha' => $matches[3], 'metodo' => trim($matches[1]), 'ref' => trim($refs[$idx] ?? 'S/N'), 'monto' => (float)str_replace(',', '', $matches[2])];
                        } else {
                            $pagos_historial[] = ['fecha' => 'N/D', 'metodo' => $str, 'ref' => trim($refs[$idx] ?? 'S/N'), 'monto' => 0];
                        }
                    }
                }
            }
        } elseif ($tipo === 'gasto') {
            $q = mysqli_query($conexion, "SELECT * FROM gastos_servicios WHERE id = $id_deuda");
            if($r = mysqli_fetch_assoc($q)){
                $idp = (int)$r['id_proveedor'];
                $qp = mysqli_query($conexion, "SELECT nombre FROM provedor WHERE ID = $idp");
                $rp = mysqli_fetch_assoc($qp);
                
                $info['doc'] = 'Gasto Adm. ' . $r['factura']; 
                $info['proveedor'] = limpiarUTF8($rp['nombre'] ?? 'N/D'); 
                $info['total'] = (float)$r['monto_total'];
                $info['pagado'] = (float)$r['monto_pagado'];
                $info['resta'] = max(0, $info['total'] - $info['pagado']);
                
                $desc = $r['descripcion'];
                $json = json_decode($desc, true);
                if(is_array($json)){
                    foreach($json as $j) { 
                        $items[] = ['desc' => limpiarUTF8($j['descripcion'] ?? $j['desc'] ?? 'Servicio'), 'cant' => 1, 'precio' => (float)$j['costo'], 'sub' => (float)$j['costo']]; 
                    }
                } else {
                    $items[] = ['desc' => limpiarUTF8($desc), 'cant' => 1, 'precio' => $info['total'], 'sub' => $info['total']];
                }

                $q_pagos = mysqli_query($conexion, "SELECT fecha_pago, metodo_pago, referencia, comprobante, monto_usd FROM pago_gastos_servicios WHERE id_gasto = $id_deuda ORDER BY id ASC");
                if($q_pagos) { 
                    while($rp = mysqli_fetch_assoc($q_pagos)) { 
                        $add_comps($comprobantes_adjuntos, $rp['comprobante']); 
                        $pagos_historial[] = ['fecha' => date('d/m/Y', strtotime($rp['fecha_pago'])), 'metodo' => limpiarUTF8($rp['metodo_pago']), 'ref' => limpiarUTF8($rp['referencia']), 'monto' => (float)$rp['monto_usd']];
                    } 
                }
            }
        } elseif ($tipo === 'compra') {
            $q = mysqli_query($conexion, "SELECT * FROM compra WHERE ID = $id_deuda");
            if($r = mysqli_fetch_assoc($q)){
                $moneda = strtoupper(trim($r['moneda'] ?? 'USD'));
                $idp = (int)$r['id_provedor'];
                $qp = mysqli_query($conexion, "SELECT nombre FROM provedor WHERE ID = $idp");
                $rp = mysqli_fetch_assoc($qp);

                $costo = (float)$r['costo'];
                $pagado = (float)$r['monto_pagado'];
                
                if ($moneda === 'BS' || $moneda === 'VES') {
                    $tasa = obtenerTasaHistorica($conexion, $r['fecha_compra'], $tasa_actual_global);
                    $costo_usd = $costo / $tasa;
                    $pagado_usd = $pagado / $tasa;
                } else {
                    $costo_usd = $costo;
                    $pagado_usd = $pagado;
                }

                $info['doc'] = 'Compra ' . $r['nro_factura']; 
                $info['proveedor'] = limpiarUTF8($rp['nombre'] ?? 'N/D'); 
                $info['total'] = $costo_usd;
                $info['pagado'] = $pagado_usd;
                $info['resta'] = max(0, $info['total'] - $info['pagado']);
                $info['moneda'] = 'USD'; 

                $qd = mysqli_query($conexion, "SELECT d.cantidad, d.precio_unitario, m.nombre FROM detalle_compra d JOIN materia_prima m ON d.id_materia = m.ID WHERE d.id_compra = $id_deuda");
                if($qd) {
                    while($rd = mysqli_fetch_assoc($qd)){
                        $p_unit = (float)$rd['precio_unitario'];
                        if ($moneda === 'BS' || $moneda === 'VES') {
                            $tasa2 = obtenerTasaHistorica($conexion, $r['fecha_compra'], $tasa_actual_global);
                            $p_unit = $p_unit / $tasa2;
                        }
                        $items[] = ['desc' => limpiarUTF8($rd['nombre']), 'cant' => $rd['cantidad'], 'precio' => $p_unit, 'sub' => $rd['cantidad'] * $p_unit];
                    }
                }

                $q_pagos = mysqli_query($conexion, "SELECT fecha_pago, metodo_pago, referencia, comprobante, monto_usd, monto_bs FROM pago_compra WHERE id_compra = $id_deuda ORDER BY id ASC");
                if($q_pagos) { 
                    while($rp = mysqli_fetch_assoc($q_pagos)) { 
                        $add_comps($comprobantes_adjuntos, $rp['comprobante']); 
                        $pagos_historial[] = ['fecha' => date('d/m/Y', strtotime($rp['fecha_pago'])), 'metodo' => limpiarUTF8($rp['metodo_pago']), 'ref' => limpiarUTF8($rp['referencia']), 'monto' => (float)$rp['monto_usd']];
                    } 
                }
            }
        } elseif ($tipo === 'os') {
            $q = mysqli_query($conexion, "SELECT * FROM ordenes_servicio WHERE id = $id_deuda");
            if($r = mysqli_fetch_assoc($q)){
                $idp = isset($r['id_proveedor']) ? (int)$r['id_proveedor'] : (int)($r['id_provedor'] ?? 0);
                $qp = mysqli_query($conexion, "SELECT nombre FROM provedor WHERE ID = $idp");
                $rp = mysqli_fetch_assoc($qp);

                $info['doc'] = 'Orden Servicio OS-' . $r['id']; 
                $info['proveedor'] = limpiarUTF8($rp['nombre'] ?? 'N/D'); 
                $info['total'] = (float)$r['costo'];
                $info['pagado'] = (float)$r['monto_pagado'];
                $info['resta'] = max(0, $info['total'] - $info['pagado']);

                $json = json_decode($r['servicio'], true);
                if(is_array($json)){
                    foreach($json as $j) { 
                        $items[] = ['desc' => limpiarUTF8($j['servicio'] ?? $j['descripcion'] ?? $j['desc'] ?? $j['concepto'] ?? 'Servicio'), 'cant' => 1, 'precio' => (float)$j['costo'], 'sub' => (float)$j['costo']]; 
                    }
                } else {
                    $items[] = ['desc' => limpiarUTF8($r['servicio']), 'cant' => 1, 'precio' => $info['total'], 'sub' => $info['total']];
                }

                $add_comps($comprobantes_adjuntos, $r['comprobante_pago']);
                
                if (!empty($r['metodo_pago'])) {
                    $metodos = explode('|', $r['metodo_pago']);
                    $refs = explode('|', $r['referencia']);
                    foreach($metodos as $idx => $str) {
                        $str = trim($str);
                        if(!$str) continue;
                        if(preg_match('/^(.*?)\s\(\$([0-9.,]+)\sel\s([0-9\/\-]+)(?:\sTasa:\s([0-9.,]+))?.*?\)$/', $str, $matches)) {
                            $pagos_historial[] = ['fecha' => $matches[3], 'metodo' => trim($matches[1]), 'ref' => trim($refs[$idx] ?? 'S/N'), 'monto' => (float)str_replace(',', '', $matches[2])];
                        } else {
                            $pagos_historial[] = ['fecha' => 'N/D', 'metodo' => $str, 'ref' => trim($refs[$idx] ?? 'S/N'), 'monto' => 0];
                        }
                    }
                }
            }
        }
        echo json_encode(['success' => true, 'info' => $info, 'items' => $items, 'comprobantes' => $comprobantes_adjuntos, 'pagos' => $pagos_historial], JSON_INVALID_UTF8_SUBSTITUTE); 
        exit;
    }

    if ($accion === 'get_desglose_ingresos') {
        $lista = [];
        $sql = "SELECT c.nro_cotizacion, cli.nombre as cliente, c.pago_valor_usd, c.pago_fecha FROM cotizacion c JOIN cliente cli ON c.id_cliente = cli.ID WHERE c.pausado = 0 AND c.estado_pago != 'Anulado'";
        $query = mysqli_query($conexion, $sql);
        if($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                $m_raw = explode('|', preg_replace('/[^0-9.,|]/', '', str_replace(['vert{}', 'ert{}', ' | '], '|', trim($row['pago_valor_usd'] ?? ''))));
                $f_raw = explode('|', preg_replace('/[^0-9\/|]/', '', str_replace(['vert{}', 'ert{}', ' | '], '|', trim($row['pago_fecha'] ?? ''))));
                foreach ($m_raw as $i => $str_monto) {
                    $monto = (float)str_replace(',', '.', trim($str_monto));
                    $fecha = trim($f_raw[$i] ?? '');
                    if ($monto > 0 && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha, $m)) {
                        if ($mes_num == 0 || ((int)$m[3] === $anio_num && (int)$m[2] === $mes_num)) {
                            $lista[] = ['nro_cotizacion' => limpiarUTF8($row['nro_cotizacion']), 'cliente' => limpiarUTF8($row['cliente']), 'fecha' => $fecha, 'monto_abonado_mes' => $monto];
                        }
                    }
                }
            }
        }
        echo json_encode(['success' => true, 'data' => $lista], JSON_INVALID_UTF8_SUBSTITUTE); exit;
    }
    
    if ($accion === 'get_desglose_egresos') {
        $lista = [];
        $filtro_compra = ($mes_num == 0) ? "1=1" : "MONTH(c.fecha_compra) = $mes_num AND YEAR(c.fecha_compra) = $anio_num";
        $q_com = mysqli_query($conexion, "SELECT c.ID as id_bd, c.nro_factura, p.nombre as proveedor, p.tlf as telefono, c.monto_pagado, c.costo, c.fecha_compra, c.moneda FROM compra c LEFT JOIN provedor p ON c.id_provedor = p.ID WHERE c.estado_pago = 'Pagado' AND $filtro_compra");
        if($q_com) {
            while ($row = mysqli_fetch_assoc($q_com)) {
                $moneda = strtoupper(trim($row['moneda'] ?? 'USD'));
                $costo = (float)$row['costo'];
                if ($moneda === 'BS' || $moneda === 'VES') {
                    $tasa = obtenerTasaHistorica($conexion, $row['fecha_compra'], $tasa_actual_global);
                    $monto_usd = $costo / $tasa;
                } else {
                    $monto_usd = $costo;
                }
                $lista[] = ['id_bd' => $row['id_bd'], 'tipo_bd' => 'compra', 'documento' => 'Compra ' . $row['nro_factura'], 'proveedor' => limpiarUTF8($row['proveedor'] ?? 'Desconocido'), 'monto' => $monto_usd, 'tipo' => 'Insumo Textil', 'fecha' => $row['fecha_compra'], 'telefono' => limpiarUTF8($row['telefono'] ?? ''), 'comprobante' => '', 'moneda' => 'USD'];
            }
        }
        $filtro_os = ($mes_num == 0) ? "1=1" : "MONTH(COALESCE(o.fecha_pago_real, o.fecha_orden)) = $mes_num AND YEAR(COALESCE(o.fecha_pago_real, o.fecha_orden)) = $anio_num";
        $q_os = mysqli_query($conexion, "SELECT o.id as id_bd, p.nombre as proveedor, p.tlf as telefono, o.costo, COALESCE(o.fecha_pago_real, o.fecha_orden) as fecha_pago FROM ordenes_servicio o LEFT JOIN provedor p ON o.id_proveedor = p.ID WHERE o.estado_pago = 'Pagado' AND $filtro_os");
        if($q_os) {
            while ($row = mysqli_fetch_assoc($q_os)) {
                $lista[] = ['id_bd' => $row['id_bd'], 'tipo_bd' => 'os', 'documento' => 'Orden Serv. OS-' . $row['id_bd'], 'proveedor' => limpiarUTF8($row['proveedor'] ?? 'Desconocido'), 'monto' => (float)$row['costo'], 'tipo' => 'Servicio Contratado', 'fecha' => $row['fecha_pago'], 'telefono' => limpiarUTF8($row['telefono'] ?? ''), 'comprobante' => '', 'moneda' => 'USD'];
            }
        }
        $filtro_gas = ($mes_num == 0) ? "1=1" : "MONTH(g.fecha) = $mes_num AND YEAR(g.fecha) = $anio_num";
        $q_gas = mysqli_query($conexion, "SELECT g.id as id_bd, g.factura, p.nombre as proveedor, p.tlf as telefono, g.monto_total, g.fecha FROM gastos_servicios g LEFT JOIN provedor p ON g.id_proveedor = p.ID WHERE g.estado_pago = 'Pagado' AND $filtro_gas");
        if($q_gas) {
            while ($row = mysqli_fetch_assoc($q_gas)) {
                $lista[] = ['id_bd' => $row['id_bd'], 'tipo_bd' => 'gasto', 'documento' => 'Gasto Adm. ' . $row['factura'], 'proveedor' => limpiarUTF8($row['proveedor'] ?? 'Desconocido'), 'monto' => (float)$row['monto_total'], 'tipo' => 'Gasto Operativo', 'fecha' => $row['fecha'], 'telefono' => limpiarUTF8($row['telefono'] ?? ''), 'comprobante' => '', 'moneda' => 'USD'];
            }
        }
        $filtro_nom = ($mes_num == 0) ? "1=1" : "MONTH(fecha_pago) = $mes_num AND YEAR(fecha_pago) = $anio_num";
        $q_nom = mysqli_query($conexion, "SELECT id as id_bd, fecha_pago, total_usd FROM nomina_lotes WHERE estado = 'Finalizada' AND $filtro_nom");
        if($q_nom) {
            while ($row = mysqli_fetch_assoc($q_nom)) { 
                $lista[] = ['id_bd' => $row['id_bd'], 'tipo_bd' => 'nomina', 'documento' => 'Lote Nomina #' . $row['id_bd'], 'proveedor' => 'Trabajadores RRHH', 'monto' => (float)$row['total_usd'], 'tipo' => 'Nomina', 'fecha' => $row['fecha_pago'], 'telefono' => '', 'comprobante' => '', 'moneda' => 'USD']; 
            }
        }
        echo json_encode(['success' => true, 'data' => $lista], JSON_INVALID_UTF8_SUBSTITUTE); exit;
    }

    if ($accion === 'get_historial_proveedor') {
        $proveedor = mysqli_real_escape_string($conexion, trim($_POST['proveedor']));
        $lista = [];

        $q_com = mysqli_query($conexion, "SELECT c.nro_factura, c.fecha_compra, c.costo as monto_total, c.monto_pagado, c.estado_pago, c.moneda, c.ID FROM compra c JOIN provedor p ON c.id_provedor = p.ID WHERE p.nombre = '$proveedor' AND c.estado_pago != 'Anulado'");
        if($q_com) {
            while ($row = mysqli_fetch_assoc($q_com)) {
                $moneda = strtoupper(trim($row['moneda'] ?? 'USD'));
                $usdTotal = (float)$row['monto_total'];
                $usdPagado = (float)$row['monto_pagado'];
                if ($moneda === 'BS' || $moneda === 'VES') {
                    $tasa_h = obtenerTasaHistorica($conexion, $row['fecha_compra'], $tasa_actual_global);
                    $usdTotal = $usdTotal / $tasa_h;
                    $usdPagado = $usdPagado / $tasa_h;
                }
                
                $lista[] = [
                    'fecha' => $row['fecha_compra'],
                    'documento' => 'Compra ' . $row['nro_factura'],
                    'tipo' => 'Insumo',
                    'monto_total_nativo' => $usdTotal,
                    'pagado_nativo' => $usdPagado,
                    'monto_total_usd' => $usdTotal,
                    'pagado_usd' => $usdPagado,
                    'estado' => $row['estado_pago'],
                    'moneda' => 'USD',
                    'timestamp' => strtotime($row['fecha_compra'] ?? date('Y-m-d'))
                ];
            }
        }

        $q_os = mysqli_query($conexion, "SELECT o.id, COALESCE(o.fecha_pago_real, o.fecha_orden) as fecha, o.costo as monto_total, o.monto_pagado, o.estado_pago FROM ordenes_servicio o JOIN provedor p ON o.id_proveedor = p.ID WHERE p.nombre = '$proveedor' AND o.estado_pago != 'Anulado'");
        if($q_os) {
            while ($row = mysqli_fetch_assoc($q_os)) {
                $lista[] = [
                    'fecha' => $row['fecha'],
                    'documento' => 'OS-' . $row['id'],
                    'tipo' => 'Servicio',
                    'monto_total_nativo' => (float)$row['monto_total'],
                    'pagado_nativo' => (float)$row['monto_pagado'],
                    'monto_total_usd' => (float)$row['monto_total'],
                    'pagado_usd' => (float)$row['monto_pagado'],
                    'estado' => $row['estado_pago'],
                    'moneda' => 'USD',
                    'timestamp' => strtotime($row['fecha'] ?? date('Y-m-d'))
                ];
            }
        }

        $q_gas = mysqli_query($conexion, "SELECT g.factura, g.fecha, g.monto_total, g.monto_pagado, g.estado_pago FROM gastos_servicios g JOIN provedor p ON g.id_proveedor = p.ID WHERE p.nombre = '$proveedor' AND g.estado_pago != 'Anulado'");
        if($q_gas) {
            while ($row = mysqli_fetch_assoc($q_gas)) {
                $lista[] = [
                    'fecha' => $row['fecha'],
                    'documento' => 'Gasto ' . $row['factura'],
                    'tipo' => 'Gasto',
                    'monto_total_nativo' => (float)$row['monto_total'],
                    'pagado_nativo' => (float)$row['monto_pagado'],
                    'monto_total_usd' => (float)$row['monto_total'],
                    'pagado_usd' => (float)$row['monto_pagado'],
                    'estado' => $row['estado_pago'],
                    'moneda' => 'USD',
                    'timestamp' => strtotime($row['fecha'] ?? date('Y-m-d'))
                ];
            }
        }

        usort($lista, function($a, $b) { return $b['timestamp'] <=> $a['timestamp']; });
        echo json_encode(['success' => true, 'data' => $lista], JSON_INVALID_UTF8_SUBSTITUTE); exit;
    }

    if ($accion === 'get_historial_movimientos') {
        $data = [];
        $agregar_item = function($tipo, $fecha, $monto, $categoria, $descripcion, $cuenta = 'N/A', $editable = false, $id = 0, $source = 'manual') use (&$data) {
            if (empty($fecha)) return;
            $fecha_ok = $fecha;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_ok)) {
                $fecha_ok = date('d/m/Y', strtotime($fecha_ok));
            }
            $data[] = [
                'id' => $id,
                'tipo' => strtoupper(trim($tipo)),
                'fecha' => $fecha_ok,
                'monto' => (float)$monto,
                'categoria' => limpiarUTF8($categoria ?: 'General'),
                'descripcion' => limpiarUTF8($descripcion ?: 'Sin descripcion'),
                'cuenta' => limpiarUTF8($cuenta ?: 'N/A'),
                'editable' => (bool)$editable,
                'source' => $source,
                'timestamp' => strtotime($fecha)
            ];
        };

        $filtroManual = ($mes_num == 0) ? '1=1' : "MONTH(m.fecha) = $mes_num AND YEAR(m.fecha) = $anio_num";
        $sqlManual = "SELECT m.id, m.fecha, m.tipo, m.monto, c.nombre as categoria, m.descripcion, cu.nombre as cuenta FROM finanzas_movimientos m LEFT JOIN finanzas_categorias c ON m.id_categoria = c.id LEFT JOIN finanzas_cuentas cu ON m.id_cuenta = cu.id WHERE $filtroManual ORDER BY m.fecha DESC, m.id DESC";
        $qManual = mysqli_query($conexion, $sqlManual);
        if ($qManual) {
            while ($r = mysqli_fetch_assoc($qManual)) {
                $agregar_item($r['tipo'], $r['fecha'], $r['monto'], $r['categoria'] ?? 'Sin Categoria', $r['descripcion'] ?? 'Sin descripcion', $r['cuenta'] ?? 'N/A', true, (int)$r['id'], 'manual');
            }
        }

        $qCot = mysqli_query($conexion, "SELECT ID, nro_cotizacion, pago_valor_usd, pago_fecha, total FROM cotizacion WHERE pausado = 0 AND estado_pago != 'Anulado'");
        if ($qCot) {
            while ($row = mysqli_fetch_assoc($qCot)) {
                $monto_raw = preg_replace('/[^0-9.,|]/', '', str_replace(['vert{}', 'ert{}', ' | '], '|', $row['pago_valor_usd'] ?? ''));
                $fecha_raw = preg_replace('/[^0-9\/|]/', '', str_replace(['vert{}', 'ert{}', ' | '], '|', $row['pago_fecha'] ?? ''));
                $montos = array_filter(array_map('trim', preg_split('/\|/', $monto_raw) ?: []), fn($valor) => $valor !== '');
                $fechas = array_map('trim', preg_split('/\|/', $fecha_raw) ?: []);

                foreach ($montos as $idx => $monto) {
                    $monto_num = (float)str_replace(',', '.', $monto);
                    $fecha_pag = $fechas[$idx] ?? '';
                    if ($monto_num <= 0 || empty($fecha_pag)) continue;
                    $mes_ok = ($mes_num == 0) ? true : (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha_pag, $matches) && (int)$matches[3] === $anio_num && (int)$matches[2] === $mes_num);
                    if (!$mes_ok) continue;
                    $agregar_item('INGRESO', $fecha_pag, $monto_num, 'Cotización', 'Cobro de cotización ' . ($row['nro_cotizacion'] ?? $row['ID']), 'Ventas', false, (int)$row['ID'], 'cotizacion');
                }
            }
        }

        $qCompra = mysqli_query($conexion, "SELECT ID, nro_factura, fecha_compra, costo, moneda FROM compra WHERE estado_pago != 'Anulado'");
        if ($qCompra) {
            while ($row = mysqli_fetch_assoc($qCompra)) {
                if (empty($row['fecha_compra'])) continue;
                $mes_ok = ($mes_num == 0) ? true : ((int)date('n', strtotime($row['fecha_compra'])) === $mes_num && (int)date('Y', strtotime($row['fecha_compra'])) === $anio_num);
                if (!$mes_ok) continue;
                $agregar_item('EGRESO', $row['fecha_compra'], (float)$row['costo'], 'Compra', 'Compra ' . ($row['nro_factura'] ?: 'N/F'), $row['moneda'] ?: 'USD', false, (int)$row['ID'], 'compra');
            }
        }

        $qGasto = mysqli_query($conexion, "SELECT id, factura, fecha, monto_total FROM gastos_servicios WHERE estado_pago != 'Anulado'");
        if ($qGasto) {
            while ($row = mysqli_fetch_assoc($qGasto)) {
                if (empty($row['fecha'])) continue;
                $mes_ok = ($mes_num == 0) ? true : ((int)date('n', strtotime($row['fecha'])) === $mes_num && (int)date('Y', strtotime($row['fecha'])) === $anio_num);
                if (!$mes_ok) continue;
                $agregar_item('EGRESO', $row['fecha'], (float)$row['monto_total'], 'Servicio', 'Gasto ' . ($row['factura'] ?: 'N/F'), 'Gastos', false, (int)$row['id'], 'gasto');
            }
        }

        $qNomina = mysqli_query($conexion, "SELECT id, fecha_pago, total_usd, estado FROM nomina_lotes WHERE estado != 'Anulado'");
        if ($qNomina) {
            while ($row = mysqli_fetch_assoc($qNomina)) {
                if (empty($row['fecha_pago'])) continue;
                $mes_ok = ($mes_num == 0) ? true : ((int)date('n', strtotime($row['fecha_pago'])) === $mes_num && (int)date('Y', strtotime($row['fecha_pago'])) === $anio_num);
                if (!$mes_ok) continue;
                $agregar_item('EGRESO', $row['fecha_pago'], (float)$row['total_usd'], 'Nómina', 'Lote de nómina #' . ($row['id'] ?: 'N/F'), 'Nomina', false, (int)$row['id'], 'nomina');
            }
        }

        $qServicio = mysqli_query($conexion, "SELECT id, COALESCE(fecha_pago_real, fecha_orden) as fecha, costo FROM ordenes_servicio WHERE estado_pago != 'Anulado'");
        if ($qServicio) {
            while ($row = mysqli_fetch_assoc($qServicio)) {
                if (empty($row['fecha'])) continue;
                $mes_ok = ($mes_num == 0) ? true : ((int)date('n', strtotime($row['fecha'])) === $mes_num && (int)date('Y', strtotime($row['fecha'])) === $anio_num);
                if (!$mes_ok) continue;
                $agregar_item('EGRESO', $row['fecha'], (float)$row['costo'], 'Servicio', 'Orden de servicio OS-' . ($row['id'] ?: 'N/F'), 'Proveedores', false, (int)$row['id'], 'servicio');
            }
        }

        usort($data, function($a, $b) { return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0); });
        echo json_encode(['success' => true, 'data' => $data], JSON_INVALID_UTF8_SUBSTITUTE); exit;
    }
    
    if ($accion === 'eliminar_movimiento') {
        $id = intval($_POST['id']); $q_mov = mysqli_query($conexion, "SELECT tipo, monto_bs, monto, id_cuenta, moneda_origen FROM finanzas_movimientos WHERE id = $id");
        if ($mov = mysqli_fetch_assoc($q_mov)) {
            $tipo = $mov['tipo']; $m_rev = $mov['moneda_origen'] === 'BS' ? (float)$mov['monto_bs'] : (float)$mov['monto']; $idc = (int)$mov['id_cuenta'];
            mysqli_begin_transaction($conexion);
            if (mysqli_query($conexion, "DELETE FROM finanzas_movimientos WHERE id = $id")) {
                if ($idc > 0) { $op = $tipo === 'INGRESO' ? '-' : '+'; mysqli_query($conexion, "UPDATE finanzas_cuentas SET saldo = saldo $op $m_rev WHERE id = $idc"); }
                mysqli_commit($conexion); echo json_encode(['success' => true]); exit;
            } else { mysqli_rollback($conexion); }
        }
    }
    
    if ($accion === 'guardar_movimiento') {
        $tipo = mysqli_real_escape_string($conexion, $_POST['tipo']); $fecha = mysqli_real_escape_string($conexion, $_POST['fecha']); $moneda = mysqli_real_escape_string($conexion, $_POST['moneda_origen']); $tasa_bcv = (float)$_POST['tasa_bcv']; $monto_ingresado = (float)$_POST['monto']; $id_cuenta = (int)$_POST['id_cuenta']; $id_categoria = (int)$_POST['id_categoria']; $referencia = mysqli_real_escape_string($conexion, trim($_POST['referencia'])); $descripcion = mysqli_real_escape_string($conexion, trim($_POST['descripcion']));
        $m_usd = $monto_ingresado; $m_bs = 0; if ($moneda === 'BS') { $m_bs = $monto_ingresado; $m_usd = $tasa_bcv > 0 ? ($m_bs / $tasa_bcv) : 0; }
        mysqli_begin_transaction($conexion);
        mysqli_query($conexion, "INSERT INTO finanzas_movimientos (tipo, fecha, monto, monto_bs, tasa_bcv, moneda_origen, id_categoria, id_cuenta, descripcion, referencia, id_usuario) VALUES ('$tipo', '$fecha', $m_usd, $m_bs, $tasa_bcv, '$moneda', $id_categoria, $id_cuenta, '$descripcion', '$referencia', $id_usuario)");
        $op = $tipo === 'INGRESO' ? '+' : '-'; mysqli_query($conexion, "UPDATE finanzas_cuentas SET saldo = saldo $op $monto_ingresado WHERE id = $id_cuenta");
        mysqli_commit($conexion); echo json_encode(['success' => true]); exit;
    }

    if ($accion === 'get_configuracion') {
        $ctas = []; $cats = [];
        $q1 = mysqli_query($conexion, "SELECT * FROM finanzas_cuentas ORDER BY moneda DESC");
        if($q1) while($r = mysqli_fetch_assoc($q1)) { $r['nombre'] = limpiarUTF8($r['nombre']); $r['nro_cuenta'] = limpiarUTF8($r['nro_cuenta'] ?? ''); $r['pm_cedula'] = limpiarUTF8($r['pm_cedula'] ?? ''); $r['pm_tlf'] = limpiarUTF8($r['pm_tlf'] ?? ''); $r['pm_banco'] = limpiarUTF8($r['pm_banco'] ?? ''); $ctas[] = $r; }
        $q2 = mysqli_query($conexion, "SELECT * FROM finanzas_categorias ORDER BY tipo ASC, nombre ASC");
        if($q2) while($r = mysqli_fetch_assoc($q2)) { $r['nombre'] = limpiarUTF8($r['nombre']); $cats[] = $r; }
        echo json_encode(['success' => true, 'cuentas' => $ctas, 'categorias' => $cats], JSON_INVALID_UTF8_SUBSTITUTE); exit;
    }
    
    if ($accion === 'crear_categoria') { $nombre = mysqli_real_escape_string($conexion, trim($_POST['nombre'])); $tipo = mysqli_real_escape_string($conexion, trim($_POST['tipo'])); if(mysqli_query($conexion, "INSERT INTO finanzas_categorias (nombre, tipo) VALUES ('$nombre', '$tipo')")) { echo json_encode(['success' => true]); exit; } }
    if ($accion === 'editar_categoria') { $id = (int)$_POST['id']; $nombre = mysqli_real_escape_string($conexion, trim($_POST['nombre'])); if(mysqli_query($conexion, "UPDATE finanzas_categorias SET nombre = '$nombre' WHERE id = $id")) { echo json_encode(['success' => true]); exit; } }
    if ($accion === 'eliminar_categoria') { $id = (int)$_POST['id']; mysqli_query($conexion, "UPDATE finanzas_movimientos SET id_categoria = NULL WHERE id_categoria = $id"); if(mysqli_query($conexion, "DELETE FROM finanzas_categorias WHERE id = $id")) { echo json_encode(['success' => true]); exit; } }
    if ($accion === 'crear_cuenta') { $nombre = mysqli_real_escape_string($conexion, trim($_POST['nombre'])); $moneda = mysqli_real_escape_string($conexion, trim($_POST['moneda'])); $nro = mysqli_real_escape_string($conexion, trim($_POST['nro_cuenta'])); $saldo = (float)$_POST['saldo']; $pm_banco = mysqli_real_escape_string($conexion, trim($_POST['pm_banco'] ?? '')); $pm_cedula = mysqli_real_escape_string($conexion, trim($_POST['pm_cedula'] ?? '')); $pm_tlf = mysqli_real_escape_string($conexion, trim($_POST['pm_tlf'] ?? '')); if(mysqli_query($conexion, "INSERT INTO finanzas_cuentas (nombre, nro_cuenta, moneda, saldo, pm_banco, pm_cedula, pm_tlf) VALUES ('$nombre', '$nro', '$moneda', $saldo, '$pm_banco', '$pm_cedula', '$pm_tlf')")) { echo json_encode(['success' => true]); exit; } }
    if ($accion === 'editar_cuenta') { $id = (int)$_POST['id']; $nombre = mysqli_real_escape_string($conexion, trim($_POST['nombre'])); $nro = mysqli_real_escape_string($conexion, trim($_POST['nro_cuenta'])); $pm_banco = mysqli_real_escape_string($conexion, trim($_POST['pm_banco'] ?? '')); $pm_cedula = mysqli_real_escape_string($conexion, trim($_POST['pm_cedula'] ?? '')); $pm_tlf = mysqli_real_escape_string($conexion, trim($_POST['pm_tlf'] ?? '')); if(mysqli_query($conexion, "UPDATE finanzas_cuentas SET nombre = '$nombre', nro_cuenta = '$nro', pm_banco = '$pm_banco', pm_cedula = '$pm_cedula', pm_tlf = '$pm_tlf' WHERE id = $id")) { echo json_encode(['success' => true]); exit; } else { echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]); exit; } }
    if ($accion === 'eliminar_cuenta') { $id = (int)$_POST['id']; mysqli_query($conexion, "UPDATE finanzas_movimientos SET id_cuenta = NULL WHERE id_cuenta = $id"); if(mysqli_query($conexion, "DELETE FROM finanzas_cuentas WHERE id = $id")) { echo json_encode(['success' => true]); exit; } }

    throw new Exception("Accion no valida.");

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    exit;
}
?>