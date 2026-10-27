<?php
session_start();
require_once '../conexion.php';
require_once '../garantia_textil.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../login/"); exit(); }

$id_usuario = intval($_SESSION['usuario_id']);
$q_usr = mysqli_query($conexion, "SELECT rol, permisos, nombre, apellido FROM usuario WHERE ID = $id_usuario");
$datos_usr = mysqli_fetch_assoc($q_usr);

$rol_actual = strtolower(trim($datos_usr['rol'] ?? ''));
$permisos_arr = array_map('trim', explode(',', $datos_usr['permisos'] ?? ''));
$es_jefe_diseno = $rol_actual === 'jefe_diseno' || in_array('55', $permisos_arr, true);
$es_operador_area = in_array($rol_actual, ['disenador', 'cortador', 'corte', 'bordador', 'bordado'], true) || in_array('56', $permisos_arr, true);
$es_disenador = $es_operador_area;

$puede_ver_prealertas = $rol_actual === 'master' || in_array('13', $permisos_arr, true);
$es_solo_prealertas = $rol_actual !== 'master'
    && in_array('13', $permisos_arr, true)
    && !in_array('36', $permisos_arr, true)
    && !$es_jefe_diseno
    && !$es_disenador;
$vista_solicitada = $_GET['vista'] ?? '';
$solicita_vista_diseno = in_array($vista_solicitada, ['diseno', 'diseno_archivador'], true) && ($es_jefe_diseno || $es_disenador);
$es_solo_diseno = in_array($rol_actual, ['disenador', 'cortador', 'corte', 'bordador', 'bordado'], true) ||
    (($es_jefe_diseno || $es_disenador) && $rol_actual !== 'master' && !in_array('36', $permisos_arr, true)) ||
    $solicita_vista_diseno;

if ($rol_actual !== 'master' && !in_array('36', $permisos_arr) && !$es_jefe_diseno && !$es_disenador && !in_array('13', $permisos_arr, true)) {
    die("<div style='padding:50px; text-align:center; font-family:sans-serif;'><h3>Acceso Denegado</h3><p>No tienes el Vector [36] para acceder a Gestión de Envíos.</p><a href='../portal_ventas/index.php'>Volver</a></div>");
}

$solo_diseno_attr = $es_solo_diseno ? 'solo-diseno' : '';
// CONTROL ABSOLUTO: IGNORAMOS SI ES MASTER, TIENE QUE TENER LA CASILLA MARCADA OBLIGATORIAMENTE
$puede_solicitar_insumo = (in_array('37', $permisos_arr)) ? 1 : 0;
$puede_ver_insumos = (in_array('38', $permisos_arr)) ? 1 : 0;
// SEMÁFORO DE PRIORIDAD DE ENVÍOS (Vector 48)
$puede_marcar_urgente = (in_array('48', $permisos_arr) || $rol_actual === 'master') ? 1 : 0;
$garantias_abiertas = 0;
if (!$es_solo_diseno && !$es_solo_prealertas) {
    if (!asegurarTablaGarantiasTextiles($conexion)) {
        http_response_code(500);
        exit('No se pudo preparar la bandeja de garantías textiles.');
    }
    $q_garantias = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM garantias_textiles WHERE estado <> 'Finalizada' AND aprobacion_estado = 'Aprobada'");
    if (!$q_garantias) {
        error_log('Envíos: no se pudo contar garantías abiertas: ' . mysqli_error($conexion));
        http_response_code(500);
        exit('No se pudo cargar el aviso de garantías textiles.');
    }
    $garantias_abiertas = (int)(mysqli_fetch_assoc($q_garantias)['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envíos y Tracking - Unifuerte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../img/camisa.jpg" type="image/jpeg">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @font-face { font-family: 'Sugo Display'; src: url('../fons/SugoDisplay.otf') format('opentype'), url('../fons/SugoDisplay.ttf') format('truetype'); font-weight: normal; font-style: normal; }
        .font-sugo { font-family: 'Sugo Display', sans-serif !important; letter-spacing: 1px; }

        /* ESTRUCTURA FLEXBOX RESPONSIVA CON SIDEBAR COLAPSABLE */
        body {
            display: flex !important;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
            margin: 0;
            background-color: #f8fafc;
            font-family: 'Segoe UI', sans-serif;
        }

        .sidebar {
            position: fixed !important;
            top: 0;
            left: -300px !important;
            transform: none !important;
            width: 260px;
            height: 100vh;
            height: 100dvh;
            background: #0a2540 !important;
            color: white;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            transition: left 0.3s ease, width 0.3s ease !important;
        }

        .sidebar.active,
        .sidebar.show {
            left: 0 !important;
        }

        .sidebar-logo { border-bottom: 1px solid rgba(255,255,255,0.1); padding: 20px; flex-shrink: 0; }
        .sidebar-menu-scroll { flex: 1; overflow-y: auto; overflow-x: hidden; padding-bottom: 20px; }
        .sidebar-footer { margin-top: auto; padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.1); background: #062b50; flex-shrink: 0; }

        .nav-link-custom { color: #d0d6e0; padding: 12px 20px; display: flex; align-items: center; text-decoration: none; font-weight: 500; transition: 0.2s; margin-bottom: 5px; cursor: pointer; border-radius: 0 8px 8px 0; margin-right: 15px; }
        .nav-link-custom:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link-custom.active { background: #083766; color: #ffffff; border-left: 4px solid #0dcaf0; }

        .overlay-menu {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 1030;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .overlay-menu.show,
        .overlay-menu.active {
            display: block;
            opacity: 1;
        }

        .main-content {
            flex-grow: 1;
            min-width: 0;
            height: calc(100vh - 60px);
            overflow-y: auto;
            margin-left: 0 !important;
            padding: 15px;
            background-color: #ffffff;
            padding-bottom: 80px;
            transition: all 0.3s ease;
        }

        @media (min-width: 768px) {
            body {
                flex-direction: row;
                overflow: hidden;
            }
            .main-content {
                height: 100vh;
                padding: 20px 40px 80px 40px;
            }
            .sidebar {
                position: relative !important;
                left: 0 !important;
                flex-shrink: 0;
                width: 260px;
            }
            .sidebar.collapsed { width: 75px !important; }
            .sidebar.collapsed .nav-text,
            .sidebar.collapsed .badge { display: none !important; }
            .sidebar.collapsed .nav-link-custom {
                justify-content: center !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                margin-right: 0 !important;
            }
            .sidebar.collapsed .nav-link-custom i {
                margin-right: 0 !important;
                font-size: 1.4rem;
            }
            .sidebar.collapsed .user-box { padding: 10px !important; }
            .sidebar.collapsed .user-icon { margin-right: 0 !important; font-size: 1.5rem; }
            .sidebar.collapsed .sidebar-logo {
                justify-content: center;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            .sidebar.collapsed .sidebar-logo .logo-icon-box { margin-right: 0 !important; margin-left: 0 !important; }
            .sidebar.collapsed .sidebar-footer a { justify-content: center; }
            .sidebar.collapsed .sidebar-footer a i { margin-right: 0 !important; font-size: 1.5rem; }
        }

        #vistaDiseno { min-height: calc(100vh - 40px); }
        #iframeDiseno { display: block; width: 100%; height: calc(100vh - 45px); min-height: 620px; border: 0; background: #f8fafc; }
        body.solo-diseno #headerPrincipal,
        body.solo-diseno #contenedor_ordenes,
        body.solo-diseno #vistaNotasManuales,
        body.solo-diseno .main-content > .modal { display: none !important; }
        body.solo-diseno .main-content { padding: 0; }
        body.solo-diseno #vistaDiseno { min-height: 100vh; }
        body.solo-diseno #iframeDiseno { height: 100vh; }
        
        .modal-body::-webkit-scrollbar { width: 6px; }
        .modal-body::-webkit-scrollbar-thumb { background-color: #ccc; border-radius: 10px; }

        /* ANIMACIÓN PARA PEDIDOS URGENTES (SEMÁFORO) */
        @keyframes pulsePrioridad {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); border-color: #dc3545; }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); border-color: #dc3545; }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); border-color: #dc3545; }
        }
        .card-urgente {
            animation: pulsePrioridad 1.5s infinite;
            background-color: #fff5f5 !important;
        }
    </style>
    
    <datalist id="lista_fases_sugeridas">
        <option value="Diseño de Tallas">
        <option value="Patronaje">
        <option value="Impresión">
        <option value="Sublimación">
        <option value="Corte">
        <option value="Confección">
        <option value="Bordado">
        <option value="Auditoría">
        <option value="Empacado">
        <option value="Listo para entregar">
        <option value="Recepción de Mercancía">
        <option value="Chequeo de Mercancía">
        <option value="Reempacado">
        <option value="Tracking Interno País origen">
        <option value="Recepción para Envío Aéreo / Marítimo">
    </datalist>
</head>
<body class="<?= htmlspecialchars($solo_diseno_attr, ENT_QUOTES, 'UTF-8') ?>">

    <!-- OVERLAY PARA CERRAR MENÚ AL TOCAR AFUERA EN MÓVIL -->
    <div class="overlay-menu" id="mobileOverlay"></div>

    <!-- CABECERA MÓVIL CON EL BOTÓN HAMBURGUESA Y LA CAMISA -->
    <div class="d-flex d-md-none mobile-header bg-dark text-white p-3 justify-content-between align-items-center w-100 position-sticky top-0 shadow-sm" style="z-index: 1020; background-color: #0B4C8C !important;">
        <div class="d-flex align-items-center" style="cursor:pointer;" id="btnCamisaMovil" title="Abrir/Cerrar Menú">
            <img src="../img/camisa.jpg" style="height:30px; object-fit:contain; border-radius: 4px;" class="me-2 bg-white p-1" alt="Logo">
            <h5 class="m-0 font-sugo">Unifuerte</h5>
        </div>
        <button type="button" class="btn btn-outline-light btn-sm" id="btnHamburguesa" style="border-radius: 6px; padding: 4px 10px;">
            <i class="bi bi-list fs-3"></i>
        </button>
    </div>

    <div class="sidebar" id="sidebar">
        <!-- LOGO Y BOTÓN DE COLAPSAR (TOCAR LA CAMISA OCULTA/EXPANDE EL MENÚ) -->
        <div class="sidebar-logo d-flex align-items-center mb-4">
            <a href="#" id="btnToggleSidebarEnvios" class="text-decoration-none logo-icon-box me-2" title="Colapsar/Expandir Menú">
                <img src="../img/camisa.jpg" style="height: 35px; width: 35px; object-fit: contain; border-radius: 6px; background-color: #fff; padding: 3px;" alt="Logo">
            </a>
            <div class="nav-text">
                <h5 class="m-0 text-white font-sugo">Unifuerte</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Operaciones</small>
            </div>
        </div>

        <div class="sidebar-menu-scroll px-2">
            <div class="bg-primary bg-opacity-25 rounded p-3 mb-4 mx-2 user-box" style="background-color: rgba(13, 202, 240, 0.2) !important;" title="Tu Perfil Actual">
                <div class="fw-bold text-white d-flex align-items-center justify-content-center justify-content-md-start">
                    <i class="bi bi-person-circle me-2 user-icon"></i>
                    <span class="nav-text"><?php echo htmlspecialchars($datos_usr['nombre']); ?></span>
                </div>
                <div class="nav-text text-center text-md-start mt-1">
                    <small class="text-white-50"><?= strtoupper(str_replace('_', ' ', $rol_actual)) ?></small>
                </div>
            </div>

            <?php if(!$es_solo_diseno && $puede_ver_prealertas): ?>
            <?php if($es_solo_prealertas): ?>
            <a href="../portal_ventas/index.php" class="nav-link-custom" title="Volver a Ventas"><i class="bi bi-arrow-left-circle-fill me-2"></i> <span class="nav-text">Volver a Ventas</span></a>
            <?php endif; ?>
            <a href="#" class="nav-link-custom <?= $es_solo_prealertas ? 'active' : '' ?>" id="btnNavPrealertasProduccion" onclick="window.irAPrealertasProduccion()" title="Prealertas de Producción">
                <i class="bi bi-bell-fill text-warning me-2"></i> <span class="nav-text">Prealertas de Producción</span>
                <span class="badge bg-danger rounded-pill ms-2 d-none" id="badge_prealertas_produccion">0</span>
            </a>
            <?php endif; ?>
            <?php if(!$es_solo_diseno && !$es_solo_prealertas): ?>
            <a href="../portal_ventas/index.php" class="nav-link-custom" title="Volver a Ventas"><i class="bi bi-arrow-left-circle-fill me-2"></i> <span class="nav-text">Volver a Ventas</span></a>
            <a href="#" class="nav-link-custom active" id="btnNavEnProceso" onclick="cambiarVista('proceso')" title="Envíos en Proceso"><i class="bi bi-truck me-2"></i> <span class="nav-text">Envíos en Proceso</span></a>
            <a href="#" class="nav-link-custom" id="btnNavArchivador" onclick="cambiarVista('archivador')" title="Archivador (Finalizados)"><i class="bi bi-archive-fill me-2 text-warning"></i> <span class="nav-text">Archivador (Finalizados)</span></a>
            <a href="#" class="nav-link-custom" id="btnNavGarantiasTextiles" onclick="cambiarVista('garantias')" title="Garantías Textiles">
                <i class="bi bi-shield-exclamation me-2 text-danger"></i> <span class="nav-text">Garantías Textiles</span>
                <span class="badge bg-danger rounded-pill ms-2 <?= $garantias_abiertas ? '' : 'd-none' ?>" id="badge_garantias_textiles"><?= $garantias_abiertas ?></span>
            </a>
            
            <a href="#" class="nav-link-custom mt-2" id="btnNavNotasManuales" onclick="cambiarVista('notas_manuales')" title="Notas de Entrega Manual"><i class="bi bi-file-earmark-text me-2 text-info"></i> <span class="nav-text">Notas de Entrega Manual</span></a>
            
            <?php if($puede_ver_insumos === 1): ?>
            <a href="#" class="nav-link-custom mt-3 border-top border-secondary pt-3" id="btnNavInsumos" onclick="abrirBandejaInsumos()" title="Insumos Textiles">
                <i class="bi bi-basket-fill text-danger me-2"></i> <span class="nav-text">Insumos Textiles</span> 
                <span class="badge bg-danger rounded-pill ms-2 d-none" id="badge_insumos_pendientes">0</span>
            </a>
            <?php endif; ?>
            <?php elseif($es_jefe_diseno || $es_disenador): ?>
            <a href="../portal_ventas/index.php" class="nav-link-custom" title="Volver a Ventas"><i class="bi bi-arrow-left-circle-fill me-2"></i> <span class="nav-text">Volver a Ventas</span></a>
            <?php endif; ?>

            <?php if($es_jefe_diseno || $es_disenador): ?>
            <a href="<?= $es_solo_diseno ? 'index.php?vista=diseno' : '#' ?>" class="nav-link-custom <?= $es_solo_diseno && in_array($vista_solicitada, ['', 'diseno'], true) ? 'active' : '' ?>" id="btnNavDiseno" <?= $es_solo_diseno ? '' : 'onclick="cambiarVista(\'diseno\')"' ?> title="Diseño, Corte y Bordado">
                <i class="bi bi-palette-fill me-2 text-info"></i> <span class="nav-text">Diseño / Corte / Bordado</span>
            </a>
            <a href="<?= $es_solo_diseno ? 'index.php?vista=diseno_archivador' : '#' ?>" class="nav-link-custom <?= $es_solo_diseno && $vista_solicitada === 'diseno_archivador' ? 'active' : '' ?>" id="btnNavArchivadorDiseno" <?= $es_solo_diseno ? '' : 'onclick="cambiarVista(\'diseno_archivador\')"' ?> title="Archivador de Áreas">
                <i class="bi bi-archive-fill me-2 text-warning"></i> <span class="nav-text">Archivador de Áreas</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <a href="../login/logout.php" class="text-danger text-decoration-none fw-bold d-flex align-items-center" title="Cerrar Sesión"><i class="bi bi-box-arrow-left me-2"></i> <span class="nav-text">Cerrar Sesión</span></a>
        </div>
    </div>

    <div class="main-content">
        <!-- HEADER ORIGINAL (Proceso y Archivador) -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3 border-bottom pb-3" id="headerPrincipal">
            <h3 class="fw-bold m-0" style="color: #0B4C8C;" id="tituloPagina">
                <?php if($es_solo_prealertas): ?>
                    <i class="bi bi-bell-fill me-2 text-warning"></i>Prealertas de Producción
                <?php else: ?>
                    <i class="bi bi-truck me-2"></i> Control de Envíos en Proceso
                <?php endif; ?>
            </h3>

            <?php if(!$es_solo_prealertas): ?>
            <div class="d-flex flex-column flex-md-row align-items-center gap-3 w-100" style="max-width: 500px; justify-content: flex-end;">
                <!-- METRIC: PRENDAS DEL MES -->
                <div id="metricPrendasMes" class="bg-success text-white px-3 py-2 rounded shadow-sm d-flex flex-column align-items-center justify-content-center" style="min-width: 170px; cursor: pointer;" title="Ver detalle por orden">
                    <span class="small fw-bold" style="opacity: 0.9; font-size: 0.75rem;">PRENDAS REALIZADAS ESTE MES</span>
                    <span class="fw-bold m-0" style="font-size: 1.2rem;" id="prendasMesCount">0</span>
                    <span class="small fw-bold" style="font-size: 0.72rem;" id="prendasMetaRestantes">Faltan 500 para la meta</span>
                </div>
                <div class="position-relative w-100" style="max-width: 350px;" id="divBuscadorEnvios">
                    <i class="bi bi-search position-absolute top-50 translate-middle-y text-muted" style="left: 15px;"></i>
                    <input type="text" id="buscadorEnvios" class="form-control shadow-sm" placeholder="Buscar cliente u orden..." style="padding-left: 40px; border-radius: 20px; border: 1px solid #ced4da;">
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if(!$es_solo_diseno && $puede_ver_prealertas): ?>
        <section id="seccionPrealertasProduccion" class="mb-4">
            <div class="card border-warning shadow-sm">
                <div class="card-header bg-warning-subtle d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="fw-bold m-0 text-dark"><i class="bi bi-bell-fill text-warning me-2"></i>Prealerta de producción</h5>
                        <small class="text-muted">Cotizaciones en preparación. Revisa los modelos y solicita insumos antes de que llegue la orden.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-dark fw-bold" onclick="window.cargarPrealertasProduccion(false, true)">
                        <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                    </button>
                </div>
                <div class="card-body bg-light">
                    <div id="listaPrealertasProduccion" class="row g-3"></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- CONTENEDOR ÓRDENES DEL SISTEMA -->
        <?php if(!$es_solo_prealertas): ?>
        <div class="row" id="contenedor_ordenes"></div>
        <?php endif; ?>

        <?php if($es_jefe_diseno || $es_disenador): ?>
        <section id="vistaDiseno" style="<?= $es_solo_diseno ? '' : 'display:none;' ?>">
            <iframe id="iframeDiseno" src="diseno.php?embebido=1<?= $es_solo_diseno && $vista_solicitada === 'diseno_archivador' ? '&amp;vista=archivador' : '' ?>" title="Módulo de Diseño, Corte y Bordado"></iframe>
        </section>
        <?php endif; ?>

        <!-- ================= VISTA: NOTAS DE ENTREGA MANUALES ================= -->
        <div id="vistaNotasManuales" style="display: none;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3 border-bottom pb-3">
                <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-file-earmark-text me-2"></i> Notas de Entrega Personalizadas</h3>
                <button class="btn text-white fw-bold shadow-sm" style="background-color: #FF7518; border-radius: 20px;" onclick="abrirModalNotaManual()">
                    <i class="bi bi-plus-circle-fill"></i> Crear Nota Manual
                </button>
            </div>

            <div class="bg-white rounded shadow-sm border p-3 table-responsive">
                <table class="table table-hover align-middle table-sm" style="font-size: 0.9rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Nro. Nota</th>
                            <th>Fecha</th>
                            <th>Cliente / Razón Social</th>
                            <th>Contacto / RIF</th>
                            <th class="text-center">Artículos</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyNotasManuales">
                    </tbody>
                </table>
            </div>
        </div>

        <section id="vistaGarantiasTextiles" style="display: none;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3 border-bottom pb-3">
                <div>
                    <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-shield-exclamation me-2 text-danger"></i> Garantías Textiles</h3>
                    <p class="text-muted mb-0">Gestiona las solicitudes de clientes y mantenlas visibles hasta finalizar cada caso.</p>
                </div>
                <button class="btn btn-outline-primary fw-bold" onclick="window.cargarGarantiasTextiles()"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
            </div>
            <div id="listaGarantiasTextiles" class="row g-3"></div>
        </section>

    </div>

    <!-- MODAL DETALLE DE PRENDAS DEL MES -->
    <div class="modal fade" id="modalDetallePrendasMes" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: #198754;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-check-circle-fill me-2"></i> Prendas realizadas este mes</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body bg-light">
                    <div id="resumenPrendasMes" class="small text-muted mb-3"></div>
                    <div id="listaPrendasMes" class="d-flex flex-column gap-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TIMELINE -->
    <div class="modal fade" id="modalTimeline" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: #0B4C8C;">
                    <h5 class="modal-title fw-bold" id="tituloModalTimeline"><i class="bi bi-map-fill"></i> Progreso de la Orden</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 border-bottom pb-3">
                        <div>
                            <button class="btn btn-sm btn-outline-primary fw-bold me-1" id="btnAvisarAvance"><i class="bi bi-bell-fill"></i> Notificar a Ventas</button>
                            <button class="btn btn-sm btn-outline-dark fw-bold me-1" id="btnNotaEntrega"><i class="bi bi-file-earmark-text"></i> Nota de Entrega</button>
                            <button class="btn btn-sm btn-outline-secondary fw-bold" id="btnConfigurarFases"><i class="bi bi-list-task"></i> Editar Fases</button>
                        </div>
                        <div class="text-end">
                            <span id="msgFinalizarRestringido" class="text-danger small fw-bold d-none"></span>
                            <button class="btn btn-sm btn-outline-danger fw-bold d-none me-1" id="btnFaltanInsumos" onclick="abrirModalSolicitarInsumo()"><i class="bi bi-exclamation-triangle-fill"></i> Solicitar Insumo</button>
                            <button class="btn btn-sm btn-danger fw-bold d-none me-1" id="btnAvisarCobro"><i class="bi bi-whatsapp"></i> Cobrar Saldo</button>
                            <button class="btn btn-sm btn-success fw-bold d-none" id="btnFinalizarEnvio"><i class="bi bi-check-circle-fill"></i> FINALIZAR PRODUCCIÓN</button>
                            <button class="btn btn-sm btn-warning fw-bold text-dark d-none" id="btnGarantiaTextil"><i class="bi bi-shield-check"></i> Garantía Textil</button>
                        </div>
                    </div>

                    <input type="hidden" id="current_id_orden">
                    <input type="hidden" id="current_id_cotizacion">
                    <input type="hidden" id="current_id_tracking">
                    <input type="hidden" id="current_hash">

                    <div class="timeline" id="cuerpoTimeline"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR FASE -->
    <div class="modal fade" id="modalEditarFase" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form id="formFase">
                    <div class="modal-header" style="background-color: #FF7518;">
                        <h6 class="modal-title fw-bold text-white"><i class="bi bi-pencil"></i> Actualizar Avance</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <input type="hidden" id="fase_id" name="id_fase">
                        <input type="hidden" name="accion" value="actualizar_fase">
                        <input type="hidden" id="fase_nombre_oculto" name="nombre_fase">

                        <div class="mb-3">
                            <label class="fw-bold small text-muted">Estado del Proceso</label>
                            <select class="form-select border-primary fw-bold" name="estado" id="fase_estado">
                                <option value="0">Pendiente (Gris)</option>
                                <option value="1">En Proceso (Amarillo)</option>
                                <option value="2">Terminado (Verde)</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="fw-bold small text-muted">Fecha Inicio</label>
                                <input type="date" class="form-control" name="fecha_inicio" id="fase_inicio">
                            </div>
                            <div class="col-6">
                                <label class="fw-bold small text-muted">Fecha Fin</label>
                                <input type="date" class="form-control" name="fecha_fin" id="fase_fin">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small text-muted">Mensaje General / Observación (Opcional)</label>
                            <textarea class="form-control" name="observacion" id="fase_observacion" rows="2" placeholder="Ej: Se ajustó el color de hilo..."></textarea>
                        </div>

                        <div class="mb-3 p-3 bg-white border border-success rounded d-none" id="div_factura_fase">
                            <label class="fw-bold small text-success"><i class="bi bi-receipt"></i> Subir Guía/Factura (Requerido para entregar)</label>
                            <input type="file" class="form-control form-control-sm border-success mt-1" name="factura_file" id="input_factura_fase" accept="image/*,application/pdf">
                            <div id="link_factura_guardada" class="mt-2 text-center" style="display:none;"></div>
                        </div>

                        <div class="mb-3" id="contenedor_galeria_fase" style="display: none;">
                            <label class="fw-bold small text-muted border-bottom w-100 mb-2 pb-1">Fotos Guardadas (Añade o edita su descripción)</label>
                            <div id="galeria_fotos_fase" class="d-flex flex-wrap gap-2"></div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold small text-muted">Añadir Evidencia Fotográfica (Sube Varias)</label>
                            <input type="file" class="form-control" accept="image/*" multiple id="input_fotos_fase">
                            <div id="leyendas_nuevas_container" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white">
                        <button type="submit" class="btn text-white w-100 fw-bold" style="background-color: #0B4C8C;">Guardar Avance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL CONFIGURAR FASES -->
    <div class="modal fade" id="modalConfigurarFases" tabindex="-1" style="z-index: 1055;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h6 class="modal-title fw-bold"><i class="bi bi-list-task"></i> Configurar Pasos del Envío</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <p class="small text-muted mb-2">Ordena, elimina o añade fases a esta orden específica.</p>
                    <ul class="list-group mb-3" id="listaFasesConfig" style="max-height: 400px; overflow-y: auto;"></ul>

                    <div class="card card-body border-primary">
                        <h6 class="fw-bold small text-primary mb-2">Añadir Nueva Fase</h6>
                        <div class="d-flex gap-2">
                            <input type="text" id="nueva_fase_icono" class="form-control form-control-sm text-center" placeholder="✂️" style="width: 50px;">
                            <input type="text" id="nueva_fase_nombre" class="form-control form-control-sm" list="lista_fases_sugeridas" placeholder="Nombre de la fase">
                            <button class="btn btn-sm btn-primary fw-bold" onclick="agregarFase()"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL SOLICITAR INSUMOS (SOPORTA TANTO ORDEN COMO PRE-ORDEN/COTIZACIÓN) -->
    <div class="modal fade" id="modalSolicitarInsumo" tabindex="-1" style="z-index: 1065;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Crear Lista de Faltantes</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="alert alert-warning small">
                        Añade uno por uno los materiales que faltan para: <b id="lbl_orden_insumo"></b>.
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-muted">¿Qué insumo falta?</label>
                        <div class="input-group mb-3 shadow-sm">
                            <input type="text" class="form-control border-danger" id="input_nuevo_insumo" placeholder="Ej: Hilo azul marino 100m">
                            <button class="btn btn-danger fw-bold" type="button" id="btn_add_insumo"><i class="bi bi-plus-lg"></i> Añadir</button>
                        </div>
                        
                        <label class="fw-bold small text-muted mb-2">Elementos en la Lista:</label>
                        <ul class="list-group shadow-sm" id="lista_items_insumos_ul">
                        </ul>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm fw-bold" id="btn_enviar_lista_insumos"><i class="bi bi-send-fill"></i> Enviar a Compras</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FICHA DE LIQUIDACIÓN Y PAGO -->
    <div class="modal fade" id="modalFichaPago" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header text-white" style="background-color: #0B4C8C;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2"></i> Ficha de Liquidación y Pago Administrativo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <form id="formFichaPago">
                        <input type="hidden" id="ficha_id_orden" name="id_orden">
                        
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <div>
                                <h5 class="fw-bold text-primary m-0" id="ficha_nro_orden">OF-0000</h5>
                                <small class="text-muted"><i class="bi bi-person-fill"></i> <span id="ficha_cliente">Cliente</span> | Vendedor: <span id="ficha_vendedor">Asesor</span></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-secondary fs-6" id="ficha_nro_cotizacion">COT-0000</span>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="small fw-bold text-muted">Cotización ($)</label>
                                <input type="text" id="ficha_cotizacion" class="form-control fw-bold bg-white text-dark" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold text-muted">Comisión Oficina (%)</label>
                                <input type="number" step="0.01" id="ficha_porc_oficina" name="porc_oficina" class="form-control border-primary fw-bold" value="10">
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold text-muted">Comisión Vendedor (%)</label>
                                <input type="number" step="0.01" id="ficha_porc_vendedor" name="porc_vendedor" class="form-control border-primary fw-bold" value="5">
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Tasa BCV (Bs)</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" id="ficha_tasa" name="tasa_bcv" class="form-control border-success fw-bold text-success">
                                    <button class="btn btn-outline-success" type="button" onclick="window.consultarTasaBCV()" title="Actualizar Tasa de Hoy"><i class="bi bi-arrow-clockwise"></i></button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Pago o Abono Recibido ($)</label>
                                <input type="number" step="0.01" id="ficha_abono" name="abonos" class="form-control border-warning fw-bold text-dark">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="p-2 border rounded shadow-sm bg-white border-start border-4 border-info">
                                    <span class="d-block small text-muted fw-bold mb-1">OFICINA</span>
                                    <h5 class="text-dark fw-bold m-0" id="res_ofi_bs">Bs. 0.00</h5>
                                    <small class="text-primary fw-bold" id="res_ofi_usd">$ 0.00</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 border rounded shadow-sm bg-white border-start border-4 border-primary">
                                    <span class="d-block small text-muted fw-bold mb-1">VENDEDOR</span>
                                    <h5 class="text-dark fw-bold m-0" id="res_ven_bs">Bs. 0.00</h5>
                                    <small class="text-primary fw-bold" id="res_ven_usd">$ 0.00</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 border rounded shadow-sm bg-white border-start border-4 border-warning">
                                    <span class="d-block small text-muted fw-bold mb-1">ADMINISTRADOR</span>
                                    <h5 class="text-dark fw-bold m-0" id="res_adm_bs">Bs. 0.00</h5>
                                    <small class="text-primary fw-bold" id="res_adm_usd">$ 0.00</small>
                                </div>
                            </div>
                            <div class="col-md-12 mt-2">
                                <div class="d-flex justify-content-between p-2 rounded" style="background-color: #ffea75; border: 1px solid #d4c04a;">
                                    <strong class="text-dark">Comprobación:</strong>
                                    <strong class="text-dark" id="res_comprobacion_bs">Bs. 0.00</strong>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-list-check"></i> Tabla de Consumos (Bs.)</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.agregarFilaConsumo()"><i class="bi bi-plus-lg"></i> Agregar</button>
                                </div>
                                <div class="table-responsive border rounded bg-white">
                                    <table class="table table-sm table-hover align-middle mb-0" id="tabla_consumos">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Concepto / Ítem</th>
                                                <th class="text-end" style="width: 150px;">Monto (Bs)</th>
                                                <th class="text-center" style="width: 50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody_consumos">
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td class="fw-bold text-end">TOTAL CONSUMOS:</td>
                                                <td class="fw-bold text-danger text-end" id="total_consumos_bs">Bs. 0.00</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-white shadow-sm h-100 d-flex flex-column">
                                    <div class="mb-auto">
                                        <label class="small fw-bold text-muted d-block mb-1">Estado de la Liquidación</label>
                                        <select id="ficha_estado" name="estado_ficha" class="form-select border-primary fw-bold mb-4">
                                            <option value="Pendiente">Pendiente</option>
                                            <option value="Liquidado">Liquidado / Terminado</option>
                                        </select>

                                        <div class="p-3 text-center rounded" style="background-color: #f1f5f9; border: 2px dashed #ccc;">
                                            <span class="d-block fw-bold text-muted mb-1">SALDO A FAVOR (Bs)</span>
                                            <h4 class="text-danger fw-bold m-0" id="res_saldo_pendiente">Bs. 0.00</h4>
                                            <small class="text-muted">(Abono Bs - Total Consumos Bs)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </form>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn text-white fw-bold px-4" style="background-color: #FF7518;" id="btnGuardarFicha"><i class="bi bi-save me-1"></i> Guardar Ficha de Pago</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR NOTA MANUAL -->
    <div class="modal fade" id="modalNotaManual" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: #0B4C8C;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pen"></i> Generar Nota de Entrega Manual</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <form id="formNotaManual">
                        <input type="hidden" name="accion" value="guardar_nota_manual">
                        
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">1. Datos del Cliente</h6>
                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">Razón Social / Nombre *</label>
                                <input type="text" name="cliente_nombre" class="form-control border-primary" required>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Cédula o R.I.F *</label>
                                <input type="text" name="cedula_rif" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Fecha Emisión *</label>
                                <input type="date" name="fecha" id="nota_manual_fecha" class="form-control border-primary" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted">Comprador (Opcional)</label>
                                <input type="text" name="comprador" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted">Teléfono</label>
                                <input type="text" name="telefono" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="small fw-bold text-muted">Dirección Completa *</label>
                                <input type="text" name="direccion" class="form-control" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-end border-bottom pb-2 mb-3">
                            <h6 class="fw-bold text-primary m-0">2. Artículos y Tallas a Entregar</h6>
                            <button type="button" class="btn btn-sm btn-outline-success fw-bold" onclick="agregarFilaNotaManual()"><i class="bi bi-plus-lg"></i> Agregar Fila</button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Descripción General de la Prenda / Artículo</th>
                                        <th style="width: 150px;">Talla/Género</th>
                                        <th class="text-center" style="width: 100px;">Cantidad</th>
                                        <th class="text-center" style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyItemsNotaManual">
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn text-white fw-bold px-4" style="background-color: #FF7518;" onclick="$('#formNotaManual').submit()"><i class="bi bi-save"></i> Generar y Guardar Nota</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const PERMISO_SOLICITAR_INSUMO = <?= $puede_solicitar_insumo ?> === 1;
        const PERMISO_VER_INSUMOS = <?= $puede_ver_insumos ?> === 1;
        const PERMISO_PRIORIDAD = <?= $puede_marcar_urgente ?> === 1; 
        const USUARIO_ACTUAL = "<?= htmlspecialchars($datos_usr['nombre'] . ' ' . $datos_usr['apellido']) ?>";
        
        // ================= LOGICA MENU COLAPSABLE (CAMISA) Y MÓVIL =================
        document.addEventListener("DOMContentLoaded", function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const btnHamburguesa = document.getElementById('btnHamburguesa');
            const btnCamisaMovil = document.getElementById('btnCamisaMovil');
            const btnToggleSidebarPc = document.getElementById('btnToggleSidebarEnvios');

            function toggleMenuMovil(e) {
                if (e) { e.preventDefault(); e.stopPropagation(); }
                sidebar.classList.toggle('show');
                sidebar.classList.toggle('active');
                overlay.classList.toggle('show');
                overlay.classList.toggle('active');
            }

            function cerrarMenuMovil() {
                sidebar.classList.remove('show', 'active');
                overlay.classList.remove('show', 'active');
            }

            if (btnHamburguesa) btnHamburguesa.addEventListener('click', toggleMenuMovil);
            if (btnCamisaMovil) btnCamisaMovil.addEventListener('click', toggleMenuMovil);
            if (overlay) overlay.addEventListener('click', cerrarMenuMovil);

            if (btnToggleSidebarPc) {
                btnToggleSidebarPc.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (window.innerWidth >= 768) {
                        sidebar.classList.toggle('collapsed');
                    } else {
                        cerrarMenuMovil();
                    }
                });
            }

            document.querySelectorAll('.nav-link-custom').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 768) {
                        cerrarMenuMovil();
                    }
                });
            });
        });

        window.irAPrealertasProduccion = function() {
            const sec = document.getElementById('seccionPrealertasProduccion');
            if (sec) {
                sec.style.display = 'block';
                sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            if (typeof window.cargarPrealertasProduccion === 'function') {
                window.cargarPrealertasProduccion(false, true);
            }
        };
    </script>
    <?php if(!$es_solo_diseno && !$es_solo_prealertas): ?>
    <script src="js/app.js?v=<?= time(); ?>"></script>
    <script src="js/notas_manuales.js?v=<?= time(); ?>"></script>
    <script src="../auditoria/auditoria_espia.js?v=<?= time(); ?>"></script>
    <?php endif; ?>
    <?php if(!$es_solo_diseno && $puede_ver_prealertas): ?>
    <script src="js/prealertas_produccion.js?v=<?= time(); ?>"></script>
    <?php endif; ?>
</body>
</html>