<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);

session_start();
require_once '../conexion.php';

if (!isset($_GET['id']) || !isset($_SESSION['usuario_id'])) {
    die("Acceso denegado: Faltan permisos o el ID de la orden.");
}

$id_orden = intval($_GET['id']);

mysqli_set_charset($conexion, 'utf8mb4');
$crearTablaNotas = mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS notas_entrega_orden (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    consecutivo INT NOT NULL,
    nro_nota VARCHAR(50) NOT NULL,
    fecha DATE NOT NULL,
    tipo_entrega VARCHAR(20) NOT NULL DEFAULT 'Parcial',
    estado VARCHAR(20) NOT NULL DEFAULT 'Activa',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orden_consecutivo (id_orden, consecutivo),
    UNIQUE KEY uq_nro_nota (nro_nota),
    KEY idx_orden_fecha (id_orden, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$agregarTipoEntrega = mysqli_query($conexion, "ALTER TABLE notas_entrega_orden ADD COLUMN IF NOT EXISTS tipo_entrega VARCHAR(20) NOT NULL DEFAULT 'Parcial' AFTER fecha");
$crearTablaItemsNotas = mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS notas_entrega_orden_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_nota INT NOT NULL,
    prenda VARCHAR(255) NOT NULL,
    talla VARCHAR(255) NOT NULL,
    cantidad INT NOT NULL,
    KEY idx_nota_item (id_nota),
    CONSTRAINT fk_nota_entrega_orden_items FOREIGN KEY (id_nota)
        REFERENCES notas_entrega_orden(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (!$crearTablaNotas || !$agregarTipoEntrega || !$crearTablaItemsNotas) {
    die('No se pudo preparar el registro de notas de entrega: ' . htmlspecialchars(mysqli_error($conexion), ENT_QUOTES, 'UTF-8'));
}

// Obtener datos principales de la orden, cliente y cotización
$sql = "SELECT o.*, c.nombre AS cliente_nombre, c.cedula_Rif, c.direccion, c.tlf, c.email, c.comprador, cot.nro_cotizacion
        FROM orden_fabrica o 
        LEFT JOIN cliente c ON o.id_cliente = c.ID 
        LEFT JOIN cotizacion cot ON o.id_cotizacion = cot.ID
        WHERE o.ID = $id_orden";

$res = mysqli_query($conexion, $sql);

if (!$res) {
    die("Error SQL en la consulta principal.");
}

$orden = mysqli_fetch_assoc($res);

if(!$orden) {
    die("Orden no encontrada en la base de datos.");
}

$qTracking = mysqli_query($conexion, "SELECT estado_general FROM tracking_orden WHERE id_orden_fabrica = $id_orden LIMIT 1");
$trackingOrden = $qTracking ? mysqli_fetch_assoc($qTracking) : null;
$ordenEntregada = ($trackingOrden['estado_general'] ?? '') === 'Entregado';

function clave_linea_entrega($prenda, $talla) {
    return $prenda . "\0" . $talla;
}

function safe_html($str) {
    if ($str === null) return '';
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// 1. Extraer prendas base desde detalle_orden_fabrica
$prendas = array();
$id_detalles = array();
$tiene_fabrica = false;

$sql_detalles = "SELECT d.ID, d.tipo_prenda, d.cantidad, m.nombre as tela_nombre, m.color as tela_color 
                 FROM detalle_orden_fabrica d 
                 LEFT JOIN materia_prima m ON d.id_materia = m.ID 
                 WHERE d.id_orden_fabrica = $id_orden";

$q_det = mysqli_query($conexion, $sql_detalles);

if ($q_det) {
    while($r = mysqli_fetch_assoc($q_det)) {
        $tiene_fabrica = true;
        // Evitamos el bug de SQL LIKE '%Arte%' que filtraba palabras como "pARTE"
        if (stripos($r['tipo_prenda'], 'Arte Final') !== false) {
            continue;
        }
        $prendas[] = $r;
        $id_detalles[] = $r['ID'];
    }
}

// 2. Extraer medidas exactas y agruparlas (Si hay detalles en fábrica)
$mediciones = array();
if (!empty($id_detalles)) {
    $ids_str = implode(',', $id_detalles);
    $sql_med = "SELECT mp.genero, mp.talla_asignada, mp.cantidad, mp.tela, d.tipo_prenda 
                FROM medicion_personal mp 
                JOIN detalle_orden_fabrica d ON mp.id_detalle_orden_fabrica = d.ID 
                WHERE mp.id_detalle_orden_fabrica IN ($ids_str)";
                
    $q_med = mysqli_query($conexion, $sql_med);
    if ($q_med) {
        while($m = mysqli_fetch_assoc($q_med)) {
            $mediciones[] = $m;
        }
    }
}

$resumen = array();

// =========================================================
// LOGICA DE RECOPILACIÓN DE ARTÍCULOS EN CASCADA
// =========================================================

// Nivel A: Si hay mediciones de personal (Máximo detalle de fábrica)
if (!empty($mediciones)) {
    foreach($mediciones as $m) {
        $val_prenda = isset($m['tipo_prenda']) ? $m['tipo_prenda'] : '';
        $prenda = strtoupper((string)$val_prenda);
        $tela = !empty($m['tela']) ? strtoupper((string)$m['tela']) : '';
        $llave_prenda = $prenda . ($tela !== '' ? " (TELA: $tela)" : "");
        
        $talla = strtoupper(trim(isset($m['talla_asignada']) ? $m['talla_asignada'] : ''));
        $gen = strtoupper(isset($m['genero']) ? $m['genero'] : '');
        
        $cant = isset($m['cantidad']) ? intval($m['cantidad']) : 1;
        if ($cant <= 0) $cant = 1;
        
        if(!isset($resumen[$llave_prenda])) {
            $resumen[$llave_prenda] = array('total' => 0, 'desglose' => array());
        }
        
        $llave_talla = "TALLA $talla $gen";
        if(!isset($resumen[$llave_prenda]['desglose'][$llave_talla])) {
            $resumen[$llave_prenda]['desglose'][$llave_talla] = 0;
        }
        
        $resumen[$llave_prenda]['desglose'][$llave_talla] += $cant;
        $resumen[$llave_prenda]['total'] += $cant;
    }
} 
// Nivel B: Si no hay mediciones, pero sí hay artículos registrados en fábrica
elseif (!empty($prendas)) {
    foreach($prendas as $p) {
        $val_prenda = isset($p['tipo_prenda']) ? $p['tipo_prenda'] : '';
        $prenda = strtoupper((string)$val_prenda);
        $tela = !empty($p['tela_nombre']) ? strtoupper((string)$p['tela_nombre']) : '';
        $llave_prenda = $prenda . ($tela !== '' ? " (TELA: $tela)" : "");
        
        if(!isset($resumen[$llave_prenda])) {
            $resumen[$llave_prenda] = array('total' => 0, 'desglose' => array());
        }
        $resumen[$llave_prenda]['total'] += intval($p['cantidad']);
        
        $llave_talla = "CANTIDAD GENERAL (SIN ESPECIFICAR)";
        if(!isset($resumen[$llave_prenda]['desglose'][$llave_talla])) {
            $resumen[$llave_prenda]['desglose'][$llave_talla] = 0;
        }
        $resumen[$llave_prenda]['desglose'][$llave_talla] += intval($p['cantidad']);
    }
} 
// Nivel C: Licitaciones o Envíos Directos (No pasan por fábrica, jalar de cotización)
elseif (!$tiene_fabrica && !empty($orden['id_cotizacion'])) {
    $id_cotizacion = intval($orden['id_cotizacion']);
    $sql_det_cot = "SELECT tipo_prenda, cantidad, genero, especificaciones_tallas 
                    FROM detalle_cotizacion 
                    WHERE id_cotizacion = $id_cotizacion";
    $q_det_cot = mysqli_query($conexion, $sql_det_cot);
    
    if ($q_det_cot) {
        while($r = mysqli_fetch_assoc($q_det_cot)) {
            // Evitamos el bug de LIKE '%Arte%' excluyendo directamente por PHP
            if (stripos($r['tipo_prenda'], 'Arte Final') !== false) {
                continue;
            }

            $val_prenda = isset($r['tipo_prenda']) ? $r['tipo_prenda'] : '';
            $prenda_nombre = strtoupper((string)$val_prenda);
            $gen_base = strtoupper(isset($r['genero']) ? $r['genero'] : '');
            
            $tiene_specs = false;
            if (!empty($r['especificaciones_tallas'])) {
                $specs = json_decode($r['especificaciones_tallas'], true);
                if (is_array($specs) && count($specs) > 0) {
                    $tiene_specs = true;
                    foreach($specs as $sp) {
                        $tela = !empty($sp['tela']) ? strtoupper((string)$sp['tela']) : '';
                        $llave_prenda = $prenda_nombre . ($tela !== '' ? " (TELA: $tela)" : "");
                        
                        $talla = strtoupper(trim(isset($sp['talla']) ? $sp['talla'] : ''));
                        $gen = strtoupper(isset($sp['genero']) ? $sp['genero'] : $gen_base);
                        
                        $cant = isset($sp['cantidad']) ? intval($sp['cantidad']) : 1;
                        if ($cant <= 0) $cant = 1;
                        
                        if(!isset($resumen[$llave_prenda])) {
                            $resumen[$llave_prenda] = array('total' => 0, 'desglose' => array());
                        }
                        
                        $llave_talla = "TALLA $talla $gen";
                        if(!isset($resumen[$llave_prenda]['desglose'][$llave_talla])) {
                            $resumen[$llave_prenda]['desglose'][$llave_talla] = 0;
                        }
                        
                        $resumen[$llave_prenda]['desglose'][$llave_talla] += $cant;
                        $resumen[$llave_prenda]['total'] += $cant;
                    }
                }
            }
            
            if (!$tiene_specs) {
                if(!isset($resumen[$prenda_nombre])) {
                    $resumen[$prenda_nombre] = array('total' => 0, 'desglose' => array());
                }
                $resumen[$prenda_nombre]['total'] += intval($r['cantidad']);
                
                $llave_talla = "CANTIDAD GENERAL (SIN ESPECIFICAR)";
                if(!isset($resumen[$prenda_nombre]['desglose'][$llave_talla])) {
                    $resumen[$prenda_nombre]['desglose'][$llave_talla] = 0;
                }
                $resumen[$prenda_nombre]['desglose'][$llave_talla] += intval($r['cantidad']);
            }
        }
    }
}

$lineasOrden = array();
$entregadoPorLinea = array();
$qEntregas = mysqli_query($conexion, "
    SELECT i.prenda, i.talla, SUM(i.cantidad) AS cantidad_entregada
    FROM notas_entrega_orden_items i
    JOIN notas_entrega_orden n ON n.id = i.id_nota
    WHERE n.id_orden = $id_orden AND n.estado = 'Activa'
    GROUP BY i.prenda, i.talla");
if (!$qEntregas) {
    die('No se pudo consultar el historial de entregas: ' . safe_html(mysqli_error($conexion)));
}
while ($entrega = mysqli_fetch_assoc($qEntregas)) {
    $entregadoPorLinea[clave_linea_entrega($entrega['prenda'], $entrega['talla'])] = intval($entrega['cantidad_entregada']);
}
foreach ($resumen as $prenda => $datos) {
    foreach ($datos['desglose'] as $talla => $cantidad) {
        $clave = clave_linea_entrega($prenda, $talla);
        $cantidadTotal = intval($cantidad);
        $yaEntregado = intval($entregadoPorLinea[$clave] ?? 0);
        $disponible = $ordenEntregada ? 0 : max(0, $cantidadTotal - $yaEntregado);
        $lineasOrden[] = array(
            'prenda' => $prenda,
            'talla' => $talla,
            'total' => $cantidadTotal,
            'entregado' => $yaEntregado,
            'disponible' => $disponible
        );
    }
}
$totalPendiente = array_sum(array_column($lineasOrden, 'disponible'));

$notaId = intval($_GET['nota'] ?? 0);
$notaGenerada = null;
if ($notaId > 0) {
    $qNota = mysqli_query($conexion, "SELECT id, nro_nota, fecha, tipo_entrega FROM notas_entrega_orden WHERE id = $notaId AND id_orden = $id_orden AND estado = 'Activa' LIMIT 1");
    $notaGenerada = $qNota ? mysqli_fetch_assoc($qNota) : null;
    if (!$notaGenerada) {
        http_response_code(404);
        die('No se encontró la nota de entrega solicitada para esta orden.');
    }
    $resumen = array();
    $qItemsNota = mysqli_query($conexion, "SELECT prenda, talla, cantidad FROM notas_entrega_orden_items WHERE id_nota = $notaId ORDER BY id");
    if (!$qItemsNota) {
        die('No se pudo cargar el detalle de la nota: ' . safe_html(mysqli_error($conexion)));
    }
    while ($itemNota = mysqli_fetch_assoc($qItemsNota)) {
        if (!isset($resumen[$itemNota['prenda']])) {
            $resumen[$itemNota['prenda']] = array('total' => 0, 'desglose' => array());
        }
        $resumen[$itemNota['prenda']]['total'] += intval($itemNota['cantidad']);
        $resumen[$itemNota['prenda']]['desglose'][$itemNota['talla']] =
            ($resumen[$itemNota['prenda']]['desglose'][$itemNota['talla']] ?? 0) + intval($itemNota['cantidad']);
    }
    $nro_nota = $notaGenerada['nro_nota'];
    $fecha_actual = date('d/m/Y', strtotime($notaGenerada['fecha']));
} else {
    $fecha_actual = date('d/m/Y');
    $nro_nota = '';
}

$errorNota = '';
$cantidadesIngresadas = array();
$accionNota = (string)($_POST['accion'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($accionNota, ['crear_nota_parcial', 'crear_nota_total'], true)) {
    if ($ordenEntregada) {
        $errorNota = 'La orden ya figura como entregada; no se pueden registrar cantidades adicionales.';
    } else {
        $cantidades = is_array($_POST['cantidad'] ?? null) ? $_POST['cantidad'] : array();
        $cantidadesIngresadas = $cantidades;
        $seleccion = array();
        $totalSeleccionado = 0;
        foreach ($lineasOrden as $indice => $linea) {
            $valor = $accionNota === 'crear_nota_total'
                ? (string)$linea['disponible']
                : trim((string)($cantidades[$indice] ?? '0'));
            if ($valor === '' || !ctype_digit($valor)) {
                $errorNota = 'Las cantidades deben ser números enteros iguales o mayores que cero.';
                break;
            }
            $cantidad = intval($valor);
            if ($cantidad > $linea['disponible']) {
                $errorNota = 'La cantidad de "' . $linea['prenda'] . ' - ' . $linea['talla'] . '" supera lo pendiente por entregar.';
                break;
            }
            if ($cantidad > 0) {
                $seleccion[] = array('prenda' => $linea['prenda'], 'talla' => $linea['talla'], 'cantidad' => $cantidad);
                $totalSeleccionado += $cantidad;
            }
        }
        if ($errorNota === '' && $totalSeleccionado === 0) {
            $errorNota = $accionNota === 'crear_nota_total'
                ? 'No hay cantidades pendientes para generar la nota total.'
                : 'Indica al menos una cantidad pendiente mayor que cero para generar la nota parcial.';
        }
        if ($errorNota === '') {
            if (!mysqli_begin_transaction($conexion)) {
                die('No se pudo iniciar el registro de la entrega: ' . safe_html(mysqli_error($conexion)));
            }
            $qBloqueo = mysqli_query($conexion, "SELECT ID FROM orden_fabrica WHERE ID = $id_orden FOR UPDATE");
            if (!$qBloqueo || mysqli_num_rows($qBloqueo) === 0) {
                mysqli_rollback($conexion);
                die('No se pudo bloquear la orden para registrar la entrega.');
            }
            $qTrackingActual = mysqli_query($conexion, "SELECT estado_general FROM tracking_orden WHERE id_orden_fabrica = $id_orden LIMIT 1 FOR UPDATE");
            if (!$qTrackingActual) {
                mysqli_rollback($conexion);
                die('No se pudo verificar el estado actual de entrega: ' . safe_html(mysqli_error($conexion)));
            }
            $trackingActual = mysqli_fetch_assoc($qTrackingActual);
            $ordenEntregadaActual = ($trackingActual['estado_general'] ?? '') === 'Entregado';
            $qEntregasActualizadas = mysqli_query($conexion, "
                SELECT i.prenda, i.talla, SUM(i.cantidad) AS cantidad_entregada
                FROM notas_entrega_orden_items i
                JOIN notas_entrega_orden n ON n.id = i.id_nota
                WHERE n.id_orden = $id_orden AND n.estado = 'Activa'
                GROUP BY i.prenda, i.talla");
            if (!$qEntregasActualizadas) {
                mysqli_rollback($conexion);
                die('No se pudo verificar el saldo pendiente: ' . safe_html(mysqli_error($conexion)));
            }
            $entregadoActual = array();
            while ($entregaActual = mysqli_fetch_assoc($qEntregasActualizadas)) {
                $entregadoActual[clave_linea_entrega($entregaActual['prenda'], $entregaActual['talla'])] =
                    intval($entregaActual['cantidad_entregada']);
            }
            if ($accionNota === 'crear_nota_total') {
                foreach ($lineasOrden as $lineaActual) {
                    $clave = clave_linea_entrega($lineaActual['prenda'], $lineaActual['talla']);
                    $disponibleActual = max(0, $lineaActual['total'] - intval($entregadoActual[$clave] ?? 0));
                    if ($lineaActual['disponible'] !== $disponibleActual) {
                        mysqli_rollback($conexion);
                        $errorNota = 'El saldo pendiente cambió mientras se preparaba la entrega total. Actualiza la página y verifica las cantidades.';
                        break;
                    }
                }
            }
            foreach ($seleccion as $itemSeleccionado) {
                if ($errorNota !== '') {
                    break;
                }
                $clave = clave_linea_entrega($itemSeleccionado['prenda'], $itemSeleccionado['talla']);
                $totalActual = 0;
                foreach ($lineasOrden as $lineaActual) {
                    if (clave_linea_entrega($lineaActual['prenda'], $lineaActual['talla']) === $clave) {
                        $totalActual = $lineaActual['total'];
                        break;
                    }
                }
                if ($ordenEntregadaActual || $itemSeleccionado['cantidad'] > max(0, $totalActual - intval($entregadoActual[$clave] ?? 0))) {
                    mysqli_rollback($conexion);
                    $errorNota = 'El saldo de prendas cambió mientras se preparaba la nota. Actualiza la página y verifica las cantidades.';
                    break;
                }
            }
            if ($errorNota === '') {
                $qConsecutivo = mysqli_query($conexion, "SELECT COALESCE(MAX(consecutivo), 0) + 1 AS siguiente FROM notas_entrega_orden WHERE id_orden = $id_orden");
                if (!$qConsecutivo) {
                    mysqli_rollback($conexion);
                    die('No se pudo generar el número de nota: ' . safe_html(mysqli_error($conexion)));
                }
                $consecutivo = intval(mysqli_fetch_assoc($qConsecutivo)['siguiente']);
                $nroNuevo = 'NE-' . str_pad((string)$id_orden, 5, '0', STR_PAD_LEFT) . '-' . str_pad((string)$consecutivo, 2, '0', STR_PAD_LEFT);
                $fechaNueva = date('Y-m-d');
                $nroSql = mysqli_real_escape_string($conexion, $nroNuevo);
                $tipoEntrega = $accionNota === 'crear_nota_total' ? 'Total' : 'Parcial';
                $tipoEntregaSql = mysqli_real_escape_string($conexion, $tipoEntrega);
                if (!mysqli_query($conexion, "INSERT INTO notas_entrega_orden (id_orden, consecutivo, nro_nota, fecha, tipo_entrega) VALUES ($id_orden, $consecutivo, '$nroSql', '$fechaNueva', '$tipoEntregaSql')")) {
                    mysqli_rollback($conexion);
                    die('No se pudo guardar la nota de entrega: ' . safe_html(mysqli_error($conexion)));
                }
                $nuevoIdNota = mysqli_insert_id($conexion);
                foreach ($seleccion as $itemSeleccionado) {
                    $prendaSql = mysqli_real_escape_string($conexion, $itemSeleccionado['prenda']);
                    $tallaSql = mysqli_real_escape_string($conexion, $itemSeleccionado['talla']);
                    $cantidadSql = intval($itemSeleccionado['cantidad']);
                    if (!mysqli_query($conexion, "INSERT INTO notas_entrega_orden_items (id_nota, prenda, talla, cantidad) VALUES ($nuevoIdNota, '$prendaSql', '$tallaSql', $cantidadSql)")) {
                        mysqli_rollback($conexion);
                        die('No se pudo guardar el detalle de la nota: ' . safe_html(mysqli_error($conexion)));
                    }
                }
                if (!mysqli_commit($conexion)) {
                    mysqli_rollback($conexion);
                    die('No se pudo confirmar el registro de la entrega: ' . safe_html(mysqli_error($conexion)));
                }
                header('Location: nota_entrega.php?id=' . $id_orden . '&nota=' . $nuevoIdNota);
                exit;
            }
        }
    }
}

$qHistorialNotas = mysqli_query($conexion, "
    SELECT n.id, n.nro_nota, n.fecha, n.tipo_entrega, COALESCE(SUM(i.cantidad), 0) AS total_prendas
    FROM notas_entrega_orden n
    LEFT JOIN notas_entrega_orden_items i ON i.id_nota = n.id
    WHERE n.id_orden = $id_orden AND n.estado = 'Activa'
    GROUP BY n.id, n.nro_nota, n.fecha
    ORDER BY n.consecutivo DESC");
if (!$qHistorialNotas) {
    die('No se pudo cargar el historial de notas: ' . safe_html(mysqli_error($conexion)));
}
$historialNotas = array();
while ($notaHistorial = mysqli_fetch_assoc($qHistorialNotas)) {
    $historialNotas[] = $notaHistorial;
}
$tipoEntregaTitulo = $notaGenerada
    ? strtoupper((string)($notaGenerada['tipo_entrega'] ?? 'Parcial'))
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Entrega <?= safe_html($nro_nota) ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }
        body { background: <?= $notaGenerada ? "'#525659'" : "'#f1f5f9'" ?>; margin: 0; font-family: 'Arial', sans-serif; color: #000; font-size: 11px; }

        .top-bar { background-color: #13131A; padding: 15px; text-align: center; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .btn-print { padding: 10px 25px; font-size: 14px; font-weight: bold; background: #FF7518; color: #FFF; border: none; cursor: pointer; border-radius: 8px; transition: 0.3s; }
        .btn-print:hover { background: #e05e00; }

        .vista-previa { width: 100%; overflow-x: auto; padding: 80px 20px 40px 20px; text-align: center; }
        #documento_pdf { width: 800px; max-width: 800px; margin: 0 auto; text-align: left; background: transparent; }
        .selector-entrega { max-width: 1000px; margin: 32px auto; padding: 24px; background: #fff; border-radius: 14px; box-shadow: 0 8px 24px rgba(15, 23, 42, .12); }
        .selector-entrega h1 { color: #0B4C8C; margin-top: 0; }
        .tabla-cantidades th { background: #eaf1f8; text-align: left; }
        .tabla-cantidades td, .tabla-cantidades th { border: 1px solid #d7e0e8; padding: 9px; }
        .campo-cantidad { width: 105px; padding: 7px; }
        .aviso-historico { background: #fff7ed; border: 1px solid #fed7aa; padding: 12px; border-radius: 8px; margin: 14px 0; color: #7c2d12; }
        .lista-notas { margin: 0; padding-left: 20px; }
        
        .hoja-a4 { background: white; width: 100%; padding: 40px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); margin-bottom: 20px; position: relative; }

        @media print { 
            @page { size: letter portrait; margin: 0; }
            body { background: white !important; }
            .vista-previa { padding: 0 !important; }
            .hoja-a4 { box-shadow: none !important; margin: 0 !important; padding: 40px !important; }
            .no-print { display: none !important; } 
        }
        <?php if (!$notaGenerada): ?>
        @media (max-width: 700px) {
            .selector-entrega { margin: 16px 10px; padding: 16px; }
            .tabla-cantidades { font-size: 12px; }
            .campo-cantidad { width: 76px; }
        }
        <?php endif; ?>

        table { width: 100%; border-collapse: collapse; margin-bottom: 0; border: 1.5px solid #000; }
        th, td { border: 1.5px solid #000; padding: 4px 6px; vertical-align: middle; }
        
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .header-logo img { height: 90px; }
        .header-info { text-align: right; font-size: 9px; line-height: 1.3; font-weight: bold; color: #333; }
        
        .title-bar { display: flex; border: 1.5px solid #000; background-color: #d9d9d9; margin-bottom: 0; border-bottom: none; }
        .title-left { flex: 1; padding: 5px; font-size: 16px; font-weight: 900; text-align: center; letter-spacing: 1px; border-right: 1.5px solid #000; }
        .title-right { width: 150px; padding: 5px; font-size: 12px; text-align: center; color: #b30015; font-weight: bold; }

        .info-table td { font-size: 10px; background-color: #d9d9d9; font-weight: bold; width: 25%; text-transform: uppercase; }
        .info-table td.val { background-color: #fff; font-weight: normal; width: 75%; text-transform: uppercase; }
        .info-table td.split-val { background-color: #fff; font-weight: normal; width: 25%; text-transform: uppercase; }

        .items-header { background-color: #d9d9d9; font-size: 12px; font-weight: bold; font-style: italic; text-align: center; padding: 8px; border: 1.5px solid #000; border-top: none; border-bottom: none; }
        
        .items-table th { background-color: #d9d9d9; font-size: 11px; text-align: center; font-style: italic; }
        .items-table td { font-size: 10px; height: 22px; }
        
        .footer-firmas { display: flex; justify-content: space-between; margin-top: 50px; padding: 0 40px; position: relative; }
        .firma-box { text-align: center; position: relative; width: 220px; }
        .firma-linea { border-top: 1.5px solid #000; padding-top: 5px; font-size: 11px; margin-top: 80px; position: relative; z-index: 2; }
        
        .img-firma-claritza { 
            position: absolute; 
            bottom: 25px; 
            left: 50%; 
            transform: translateX(-50%); 
            width: 120px; 
            height: auto; 
            z-index: 1; 
            mix-blend-mode: multiply;
        }
    </style>
</head>
<body>

    <?php if (!$notaGenerada): ?>
    <main class="selector-entrega">
        <h1>Nota de entrega — <?= safe_html($orden['nro_orden_fabrica'] ?? ('Orden ' . $id_orden)) ?></h1>
        <p><strong>Cliente:</strong> <?= safe_html($orden['cliente_nombre'] ?? '') ?></p>
        <p>Indica las cantidades que se entregarán en esta nota. El sistema descuenta automáticamente lo registrado en notas anteriores.</p>
        <?php if ($errorNota !== ''): ?>
            <div class="alerta-error" role="alert" style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:12px;border-radius:8px;margin:14px 0;"><?= safe_html($errorNota) ?></div>
        <?php endif; ?>
        <?php if (count($historialNotas) === 0): ?>
            <div class="aviso-historico">Las notas impresas antes de habilitar este registro no tienen cantidades guardadas aquí. Verifica si hubo entregas previas antes de crear la primera nota parcial.</div>
        <?php else: ?>
            <div class="mb-3"><strong>Prendas registradas en notas anteriores:</strong> <?= array_sum(array_map(function ($nota) { return intval($nota['total_prendas']); }, $historialNotas)) ?></div>
        <?php endif; ?>

        <?php if ($ordenEntregada): ?>
            <div class="aviso-historico">Esta orden ya figura como entregada. No quedan cantidades disponibles para nuevas notas.</div>
        <?php endif; ?>

        <?php if ($historialNotas): ?>
            <section class="mb-4">
                <h2 style="font-size:17px;color:#0B4C8C;">Notas generadas para esta orden</h2>
                <ul class="lista-notas">
                    <?php foreach ($historialNotas as $notaHistorial): ?>
                                <li><a href="nota_entrega.php?id=<?= $id_orden ?>&nota=<?= intval($notaHistorial['id']) ?>" target="_blank" rel="noopener"><?= safe_html($notaHistorial['nro_nota']) ?></a> — <?= safe_html(strtoupper($notaHistorial['tipo_entrega'] ?? 'Parcial')) ?> — <?= date('d/m/Y', strtotime($notaHistorial['fecha'])) ?> — <?= intval($notaHistorial['total_prendas']) ?> prendas</li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <form method="post">
            <table class="tabla-cantidades" style="width:100%;border-collapse:collapse;margin-bottom:18px;">
                <thead><tr><th>Prenda</th><th>Talla / detalle</th><th>Total orden</th><th>Ya entregadas</th><th>Pendientes</th><th>Entregar ahora</th></tr></thead>
                <tbody>
                    <?php foreach ($lineasOrden as $indice => $linea): ?>
                    <tr>
                        <td><?= safe_html($linea['prenda']) ?></td>
                        <td><?= safe_html($linea['talla']) ?></td>
                        <td><?= intval($linea['total']) ?></td>
                        <td><?= intval($linea['entregado']) ?></td>
                        <td><?= intval($linea['disponible']) ?></td>
                        <td><input class="campo-cantidad" type="number" name="cantidad[<?= $indice ?>]" min="0" max="<?= intval($linea['disponible']) ?>" step="1" value="<?= safe_html($cantidadesIngresadas[$indice] ?? '0') ?>" <?= $ordenEntregada || $linea['disponible'] === 0 ? 'disabled' : '' ?> aria-label="Cantidad a entregar de <?= safe_html($linea['prenda'] . ' ' . $linea['talla']) ?>"></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$lineasOrden): ?>
                    <tr><td colspan="6">No se encontraron prendas para esta orden.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <button type="submit" name="accion" value="crear_nota_total" style="padding:11px 20px;background:#0B4C8C;color:#fff;border:0;border-radius:8px;font-weight:bold;cursor:pointer;margin-right:8px;" <?= $ordenEntregada || !$lineasOrden || $totalPendiente === 0 ? 'disabled' : '' ?>>Nota de entrega total</button>
            <button type="submit" name="accion" value="crear_nota_parcial" style="padding:11px 20px;background:#FF7518;color:#fff;border:0;border-radius:8px;font-weight:bold;cursor:pointer;" <?= $ordenEntregada || !$lineasOrden || $totalPendiente === 0 ? 'disabled' : '' ?>>Nota de entrega parcial</button>
            <a href="index.php" style="margin-left:12px;color:#0B4C8C;">Volver a Envíos</a>
        </form>
    </main>
    <?php else: ?>
    <div class="top-bar no-print">
        <button id="btnDescargar" class="btn-print" onclick="descargarPDF()">📥 Imprimir / Descargar Nota de Entrega</button>
        <a href="nota_entrega.php?id=<?= $id_orden ?>" style="color:white;margin-left:14px;">Crear otra nota</a>
    </div>

    <div class="vista-previa">
        <div id="documento_pdf">
            <div class="hoja-a4" id="hoja-captura">
                
                <div class="header-top">
                    <div class="header-logo">
                        <img src="../img/unifuerte.jpg" alt="Unifuerte Logo">
                    </div>
                    <div class="header-info">
                        UNIFUERTE, C.A<br>
                        R.I.F.: J-41282764-2/R.U.C 1829-2021<br>
                        SENCAMER REM-42739<br>
                        CR. UD-321 EDIFICIO PARCELA 321-16-10A-1,<br>
                        PISO 1, OFICINA A,B,C, ZONA INDUSTRIAL, UD 321<br>
                        CIUDAD GUAYANA. PUERTO ORDAZ, ESTADO BOLIVAR<br>
                        CONTACTO: (0286) 9940628 - 04241546880<br>
                        EMAIL: CLIENTEUNIFUERTE@GMAIL.COM - CLIENTEUNIFUERTE1@GMAIL.COM
                    </div>
                </div>

                <div class="title-bar">
                    <div class="title-left">NOTA DE ENTREGA — <?= safe_html($tipoEntregaTitulo) ?></div>
                    <div class="title-right">Nro. <?= safe_html($nro_nota) ?></div>
                </div>

                <table class="info-table">
                    <tr>
                        <td>Fecha:</td>
                        <td class="val" colspan="3"><?= $fecha_actual ?></td>
                    </tr>
                    <tr>
                        <td>Nombre y Apellido o Razón Social:</td>
                        <td class="val" colspan="3"><?= safe_html(isset($orden['cliente_nombre']) ? $orden['cliente_nombre'] : '') ?></td>
                    </tr>
                    <tr>
                        <td>Cédula o R.I.F:</td>
                        <td class="val" colspan="3"><?= safe_html(isset($orden['cedula_Rif']) ? $orden['cedula_Rif'] : '') ?></td>
                    </tr>
                    <tr>
                        <td>Dirección:</td>
                        <td class="val" colspan="3"><?= safe_html(isset($orden['direccion']) ? $orden['direccion'] : '') ?></td>
                    </tr>
                    <tr>
                        <td>Comprador:</td>
                        <td class="split-val"><?= safe_html(isset($orden['comprador']) ? $orden['comprador'] : '') ?></td>
                        <td>Teléfono:</td>
                        <td class="split-val"><?= safe_html(isset($orden['tlf']) ? $orden['tlf'] : '') ?></td>
                    </tr>
                    <tr>
                        <td>Email:</td>
                        <td class="val" colspan="3"><?= safe_html(isset($orden['email']) ? $orden['email'] : '') ?></td>
                    </tr>
                </table>

                <div class="items-header">
                    Estimado Cliente, reciba nuestra entrega del siguiente material:
                </div>

                <table class="items-table" style="border-top: none;">
                    <thead>
                        <tr>
                            <th style="width: 44%;">Prenda / descripción</th>
                            <th style="width: 18%;">Sexo</th>
                            <th style="width: 23%;">Talla</th>
                            <th style="width: 15%;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_general = 0;
                        foreach ($resumen as $prenda => $datos):
                            $total_general += (int)$datos['total'];
                            foreach ($datos['desglose'] as $talla => $cantidad):
                                $talla = trim((string)$talla);
                                $sexo = 'No especificado';
                                $tallaVisible = $talla;
                                if (preg_match('/^TALLA\s+(.+?)\s+(DAMA|CABALLERO|FEMENINO|MASCULINO|MUJER|HOMBRE|UNISEX|NIÑA|NIÑO|NINA|NINO|ACCESORIO|NO\s+APLICA)$/iu', $talla, $coincidencias)) {
                                    $tallaVisible = trim($coincidencias[1]);
                                    $sexo = mb_strtoupper($coincidencias[2], 'UTF-8');
                                } elseif (preg_match('/^TALLA\s+(.+)$/iu', $talla, $coincidencias)) {
                                    $tallaVisible = trim($coincidencias[1]);
                                } elseif (stripos($talla, 'CANTIDAD GENERAL') === 0) {
                                    $tallaVisible = 'Sin especificar';
                                }
                                ?>
                                <tr>
                                    <td style="font-weight:bold;text-align:left;padding:6px;"><?= safe_html($prenda) ?></td>
                                    <td style="text-align:center;"><?= safe_html($sexo) ?></td>
                                    <td style="text-align:center;"><?= safe_html($tallaVisible) ?></td>
                                    <td style="text-align:center;font-weight:bold;"><?= (int)$cantidad ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" style="background-color:#d9d9d9;text-align:right;font-weight:bold;">TOTAL DE PRENDAS</td>
                            <td style="background-color:#d9d9d9;text-align:center;font-weight:bold;font-size:12px;"><?= $total_general ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="footer-firmas">
                    <div class="firma-box">
                        <div class="firma-linea">
                            <strong>Recibido:</strong>
                        </div>
                    </div>

                    <div class="firma-box">
                        <img src="img/firma.jpeg" class="img-firma-claritza" alt="Firma Claritza">
                        <div class="firma-linea">
                            <strong>Claritza Pinto</strong><br>
                            Director Comercial Unifuerte C.A.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
    function descargarPDF() {
        const btn = document.getElementById('btnDescargar');
        btn.innerHTML = '⏳ Generando PDF...';
        btn.disabled = true;

        const elemento = document.getElementById('hoja-captura');
        elemento.style.boxShadow = 'none';

        const opciones = {
            margin:       0, 
            filename:     'Nota_Entrega_<?= safe_html($nro_nota) ?>.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 2, useCORS: true }, 
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' } 
        };

        html2pdf().set(opciones).from(elemento).save().then(() => {
            btn.innerHTML = '📥 Imprimir / Descargar Nota de Entrega';
            btn.disabled = false;
            elemento.style.boxShadow = '0 5px 15px rgba(0,0,0,0.5)'; 
        });
    }
    </script>
    <?php endif; ?>
</body>
</html>