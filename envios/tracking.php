<?php
error_reporting(0);
require_once '../conexion.php';
require_once '../garantia_textil.php';

if (!isset($_GET['h'])) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h3>Enlace inválido</h3></div>");
}

$hash = mysqli_real_escape_string($conexion, $_GET['h']);

// Buscar la orden por el hash
$sql = "SELECT t.*, o.nro_orden_fabrica, o.id_cliente, c.nombre as cliente_nombre, cot.nro_cotizacion, cot.estado_pago, o.ID as id_orden 
        FROM tracking_orden t 
        JOIN orden_fabrica o ON t.id_orden_fabrica = o.ID 
        JOIN cotizacion cot ON o.id_cotizacion = cot.ID 
        JOIN cliente c ON o.id_cliente = c.ID 
        WHERE t.hash_publico = '$hash'";
$res = mysqli_query($conexion, $sql);
$orden = mysqli_fetch_assoc($res);

if (!$orden) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h3>El enlace de seguimiento no existe o ha expirado.</h3></div>");
}

$id_tracking = $orden['id'];
$q_fases = mysqli_query($conexion, "SELECT * FROM tracking_fases WHERE id_tracking = $id_tracking ORDER BY orden ASC");
if (!asegurarTablaGarantiasTextiles($conexion)) {
    http_response_code(500);
    exit('No se pudo cargar el seguimiento de la garantía textil.');
}
$stmtGarantias = mysqli_prepare(
    $conexion,
    "SELECT id, motivo, evidencias_json, estado, aprobacion_estado, nota_aprobacion, historial_json, creado_en, actualizado_en, finalizado_en
     FROM garantias_textiles
     WHERE id_orden = ? AND id_cliente = ?
     ORDER BY id ASC"
);
if (!$stmtGarantias) {
    error_log('Tracking público: no se pudieron consultar garantías textiles: ' . mysqli_error($conexion));
    http_response_code(500);
    exit('No se pudo cargar el seguimiento de la garantía textil.');
}
$idOrdenGarantia = (int)$orden['id_orden'];
$idClienteGarantia = (int)$orden['id_cliente'];
mysqli_stmt_bind_param($stmtGarantias, 'ii', $idOrdenGarantia, $idClienteGarantia);
if (!mysqli_stmt_execute($stmtGarantias)) {
    error_log('Tracking público: falló la consulta de garantías textiles: ' . mysqli_stmt_error($stmtGarantias));
    mysqli_stmt_close($stmtGarantias);
    http_response_code(500);
    exit('No se pudo cargar el seguimiento de la garantía textil.');
}
$resultGarantias = mysqli_stmt_get_result($stmtGarantias);
$garantiasTextiles = [];
while ($garantia = mysqli_fetch_assoc($resultGarantias)) {
    $evidencias = json_decode((string)$garantia['evidencias_json'], true);
    $garantia['evidencias'] = is_array($evidencias) ? $evidencias : [];
    $historial = json_decode((string)$garantia['historial_json'], true);
    $garantia['historial'] = is_array($historial) ? $historial : [];
    $garantiasTextiles[] = $garantia;
}
mysqli_stmt_close($stmtGarantias);

// FUNCIÓN PARA EVITAR CORRUPCIÓN Y MOSTRAR ÍCONOS PROFESIONALES (INCLUYE INTERNACIONAL)
function getIconoSeguro($nombreFase) {
    $n = mb_strtolower($nombreFase, 'UTF-8');
    
    // Fases de Garantía y Producción Normal
    if (strpos($n, 'garantia') !== false || strpos($n, 'garantía') !== false) return '<i class="bi bi-shield-fill-exclamation text-danger"></i>';
    if (strpos($n, 'talla') !== false) return '<i class="bi bi-rulers text-dark"></i>';
    if (strpos($n, 'patron') !== false) return '<i class="bi bi-bezier text-dark"></i>';
    if (strpos($n, 'impres') !== false) return '<i class="bi bi-printer-fill text-dark"></i>';
    if (strpos($n, 'sublimaci') !== false) return '<i class="bi bi-palette-fill text-dark"></i>';
    if (strpos($n, 'corte') !== false) return '<i class="bi bi-scissors text-dark"></i>';
    if (strpos($n, 'confecci') !== false) return '<i class="bi bi-intersect text-dark"></i>';
    if (strpos($n, 'bordad') !== false) return '<i class="bi bi-record-circle text-dark"></i>';
    
    // Fases Internacionales y Similares
    if (strpos($n, 'auditor') !== false || strpos($n, 'chequeo') !== false) return '<i class="bi bi-search text-dark"></i>';
    if (strpos($n, 'empacad') !== false || strpos($n, 'recepción de mercancía') !== false || strpos($n, 'recepcion de mercancia') !== false) return '<i class="bi bi-box-seam-fill text-dark"></i>';
    if (strpos($n, 'reempacado') !== false) return '<i class="bi bi-tags-fill text-dark"></i>';
    if (strpos($n, 'aéreo') !== false || strpos($n, 'aereo') !== false || strpos($n, 'marítimo') !== false || strpos($n, 'maritimo') !== false || strpos($n, 'avion') !== false || strpos($n, 'barco') !== false) return '<i class="bi bi-airplane-fill text-dark"></i>';
    if (strpos($n, 'listo') !== false || strpos($n, 'entregar') !== false || strpos($n, 'tracking') !== false) return '<i class="bi bi-truck text-dark"></i>';
    
    return '<i class="bi bi-check-circle-fill text-dark"></i>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Pedido - Unifuerte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../img/camisa.jpg" type="image/jpeg">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .header-brand { background-color: #0B4C8C; color: white; padding: 15px 0; border-bottom: 5px solid #FF7518; }
        .card-tracking { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 30px; }
        .timeline { position: relative; padding: 20px 0; margin: 0; list-style: none; }
        .timeline::before { content: ''; position: absolute; top: 0; bottom: 0; left: 30px; width: 4px; background: #e9ecef; border-radius: 4px; }
        .timeline-item { position: relative; margin-bottom: 25px; padding-left: 70px; }
        .timeline-icon { position: absolute; left: 13px; top: 0; width: 38px; height: 38px; border-radius: 50%; background: #fff; border: 3px solid #ced4da; display: flex; align-items: center; justify-content: center; z-index: 1; font-size: 1.2rem; }
        .timeline-content { background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        
        .estado-2 .timeline-icon { border-color: #198754; color: #198754; }
        .estado-1 .timeline-icon { border-color: #FF7518; color: #FF7518; box-shadow: 0 0 0 4px rgba(255,117,24,0.2); }
        .estado-0 .timeline-icon { border-color: #ced4da; color: #6c757d; }
        
        .estado-2 .timeline-content { border-left: 4px solid #198754; }
        .estado-1 .timeline-content { border-left: 4px solid #FF7518; }
        
        .fase-garantia .timeline-content { border: 2px solid #dc3545; background-color: #fff5f5; }
        .fase-garantia .timeline-icon { border-color: #dc3545 !important; }

        .img-evidencia { width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; cursor: pointer; transition: 0.2s; }
        .img-evidencia:hover { transform: scale(1.05); }
        .garantia-evidencia { width: 110px; height: 110px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
    </style>
</head>
<body>

    <div class="header-brand text-center">
        <img src="../img/unifuerte.jpg" alt="Unifuerte" style="height: 50px; border-radius: 5px; background: white; padding: 5px; margin-bottom: 10px;">
        <h4 class="fw-bold m-0">Portal de Seguimiento al Cliente</h4>
    </div>

    <div class="container mt-4 mb-5" style="max-width: 800px;">
        <div class="card card-tracking">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h5 class="text-muted mb-1">Número de Cotización:</h5>
                    <h2 class="fw-bold" style="color: #0B4C8C;"><?= htmlspecialchars(!empty($orden['nro_cotizacion']) ? $orden['nro_cotizacion'] : $orden['nro_orden_fabrica']) ?></h2>
                    <h6 class="fw-bold mt-3">Cliente: <span class="text-primary"><?= htmlspecialchars($orden['cliente_nombre']) ?></span></h6>
                </div>
                
                <div class="d-flex justify-content-center mb-4">
                    <a href="nota_entrega.php?id=<?= $orden['id_orden'] ?>" target="_blank" class="btn fw-bold text-white shadow-sm" style="background-color: #FF7518; border-radius: 30px; padding: 10px 25px;">
                        <i class="bi bi-file-earmark-text"></i> Ver / Descargar Nota de Entrega
                    </a>
                </div>

                <div class="alert alert-info small text-center fw-bold">
                    <i class="bi bi-info-circle-fill me-1"></i> A continuación se muestra el progreso en tiempo real de su pedido en nuestra fábrica.<br>
                    <i class="bi bi-clock-history me-1 mt-1 d-inline-block"></i> El tracking se actualizará todos los días a las 3:00 PM.
                </div>

                <div class="timeline mt-4">
                    <?php 
                    while ($f = mysqli_fetch_assoc($q_fases)): 
                        $estado = intval($f['estado']);
                        $status_badge = '<span class="badge bg-secondary">Pendiente</span>';
                        if ($estado == 1) $status_badge = '<span class="badge bg-warning text-dark">En Proceso</span>';
                        if ($estado == 2) $status_badge = '<span class="badge bg-success">Terminado</span>';

                        $is_garantia = (stripos($f['fase_nombre'], 'garantia') !== false || stripos($f['fase_nombre'], 'garantía') !== false);
                        $clase_garantia = $is_garantia ? 'fase-garantia' : '';
                        
                        // INYECCIÓN DEL ÍCONO PROFESIONAL
                        $icono = getIconoSeguro($f['fase_nombre']);

                        $fotos_html = '';
                        if (!empty($f['foto'])) {
                            $fotos = explode(',', $f['foto']);
                            $fotos_html = '<div class="d-flex flex-wrap gap-2 mt-3">';
                            foreach ($fotos as $img) {
                                $img = trim($img);
                                if (!empty($img)) {
                                    $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
                                    if ($ext === 'pdf') {
                                        $fotos_html .= '<a href="../../images/envios/'.htmlspecialchars($img).'" target="_blank" class="btn btn-outline-danger btn-sm fw-bold d-flex flex-column align-items-center justify-content-center p-2" style="width: 120px; height: 120px; border-radius: 8px;"><i class="bi bi-file-pdf-fill fs-1"></i> Ver PDF</a>';
                                    } else {
                                        $fotos_html .= '<a href="../../images/envios/'.htmlspecialchars($img).'" target="_blank"><img src="../../images/envios/'.htmlspecialchars($img).'" class="img-evidencia shadow-sm"></a>';
                                    }
                                }
                            }
                            $fotos_html .= '</div>';
                        }

                        $fechas = '';
                        if (!empty($f['fecha_inicio']) || !empty($f['fecha_fin'])) {
                            $inicio = !empty($f['fecha_inicio']) ? date('d/m/Y', strtotime($f['fecha_inicio'])) : '---';
                            $fin = !empty($f['fecha_fin']) ? date('d/m/Y', strtotime($f['fecha_fin'])) : '---';
                            $fechas = '<div class="small text-muted mt-1"><i class="bi bi-calendar-event"></i> ' . $inicio . ' al ' . $fin . '</div>';
                        }
                    ?>
                    
                    <div class="timeline-item estado-<?= $estado ?> <?= $clase_garantia ?>">
                        <div class="timeline-icon shadow-sm"><?= $icono ?></div>
                        <div class="timeline-content">
                            <?php if($is_garantia): ?>
                                <div class="bg-danger text-white text-center fw-bold p-1 mb-2 rounded small"><i class="bi bi-shield-exclamation"></i> FASE DE GARANTÍA TEXTIL</div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold m-0 <?= $is_garantia ? 'text-danger' : 'text-dark' ?>"><?= htmlspecialchars($f['fase_nombre']) ?></h6>
                                <?= $status_badge ?>
                            </div>
                            
                            <?= $fechas ?>

                            <?php if(!empty($f['observacion'])): ?>
                                <div class="small mt-2 p-2 rounded" style="background-color: #f8f9fa; border-left: 3px solid <?= $is_garantia ? '#dc3545' : '#FF7518' ?>;">
                                    <i>"<?= htmlspecialchars($f['observacion']) ?>"</i>
                                </div>
                            <?php endif; ?>

                            <?= $fotos_html ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <?php if ($garantiasTextiles): ?>
                    <section class="mt-4 pt-3 border-top">
                        <h5 class="fw-bold text-danger"><i class="bi bi-shield-exclamation me-2"></i>Seguimiento de garantía textil</h5>
                        <?php foreach ($garantiasTextiles as $garantia): ?>
                            <article class="card border-danger mb-3">
                                <div class="card-body">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <strong>Solicitud #<?= (int)$garantia['id'] ?></strong>
                                        <?php $estadoVisibleGarantia = $garantia['aprobacion_estado'] === 'Pendiente' ? 'Pendiente de aprobación del vendedor' : ($garantia['aprobacion_estado'] === 'Rechazada' ? 'Solicitud rechazada' : $garantia['estado']); ?>
                                        <?php $claseEstadoGarantia = $garantia['aprobacion_estado'] === 'Rechazada' ? 'bg-secondary' : ($garantia['estado'] === 'Finalizada' ? 'bg-success' : ($garantia['aprobacion_estado'] === 'Pendiente' ? 'bg-warning text-dark' : 'bg-danger')); ?>
                                        <span class="badge <?= $claseEstadoGarantia ?>"><?= htmlspecialchars($estadoVisibleGarantia, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <p class="mt-3 mb-2"><?= nl2br(htmlspecialchars($garantia['motivo'], ENT_QUOTES, 'UTF-8')) ?></p>
                                    <?php if ($garantia['evidencias']): ?>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($garantia['evidencias'] as $evidencia): ?>
                                                <?php
                                                $nombreEvidencia = (string)($evidencia['nombre'] ?? '');
                                                if (!preg_match('/^[a-f0-9]{40}\.(jpg|png|webp)$/i', $nombreEvidencia)) {
                                                    continue;
                                                }
                                                $urlEvidencia = 'https://cliente.soyunifuerte.com/index.php?img=' . rawurlencode($nombreEvidencia);
                                                ?>
                                                <a href="<?= htmlspecialchars($urlEvidencia, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                                    <img src="<?= htmlspecialchars($urlEvidencia, ENT_QUOTES, 'UTF-8') ?>" class="garantia-evidencia" alt="Evidencia de garantía">
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($garantia['aprobacion_estado'] === 'Rechazada' && !empty($garantia['nota_aprobacion'])): ?>
                                        <p class="text-danger small mt-2 mb-0"><?= nl2br(htmlspecialchars($garantia['nota_aprobacion'], ENT_QUOTES, 'UTF-8')) ?></p>
                                    <?php endif; ?>
                                    <?php if ($garantia['historial']): ?>
                                        <ol class="small mt-3 mb-0">
                                            <?php foreach ($garantia['historial'] as $movimiento): ?>
                                                <li><strong><?= htmlspecialchars((string)($movimiento['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <?php if (!empty($movimiento['fecha'])): ?> — <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$movimiento['fecha'])), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ol>
                                    <?php endif; ?>
                                    <small class="text-muted d-block mt-3">Actualizada: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($garantia['actualizado_en'])), ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

            </div>
        </div>
        
        <div class="text-center text-muted small mt-4">
            &copy; <?= date('Y') ?> Unifuerte C.A. Todos los derechos reservados.<br>
            Desarrollado con ❤️ para nuestros clientes.
        </div>
    </div>

</body>
</html>