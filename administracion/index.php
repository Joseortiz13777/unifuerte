<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol'])) {
    header("Location: ../login/index.php");
    exit();
}

$id_usuario = intval($_SESSION['usuario_id']);
$q_usr = mysqli_query($conexion, "SELECT rol, nombre, apellido FROM usuario WHERE ID = $id_usuario");
$datos_usr = mysqli_fetch_assoc($q_usr);
$rol_actual = strtolower($datos_usr['rol']);

if ($rol_actual !== 'master' && $rol_actual !== 'admin') {
    echo "<h2 style='text-align:center; margin-top:50px; color:red;'>Acceso Denegado. Area exclusiva de Administracion.</h2>";
    exit();
}
$csrf_eventos = $_SESSION['admin_eventos_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['admin_eventos_csrf'] = $csrf_eventos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administracion Financiera - Unifuerte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../portal_ventas/css/estilos_portal.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="css/estilos_admin.css?v=<?= time(); ?>">
    <link rel="icon" href="../img/camisa.jpg" type="image/jpeg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body style="background-color: #f8f9fa;">

    <div class="d-none mobile-header bg-dark text-white p-3 justify-content-between align-items-center w-100 position-sticky top-0" style="z-index: 1020; background-color: #0B4C8C !important;">
        <div class="d-flex align-items-center"><img src="../img/camisa.jpg" style="height:30px; border-radius: 4px;" class="me-2 bg-white p-1" alt="Logo"><h5 class="m-0 font-sugo">Unifuerte</h5></div>
        <button class="btn btn-outline-light btn-sm" id="btnHamburguesa"><i class="bi bi-list fs-3"></i></button>
    </div>
    <div class="overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo d-flex align-items-center mb-4">
            <div class="bg-white rounded p-1 me-2"><img src="../img/camisa.jpg" style="height:30px; object-fit:contain;" alt="Logo"></div>
            <div><h5 class="m-0 text-white font-sugo">Unifuerte</h5><small class="text-white-50" style="font-size: 0.7rem;">Administracion</small></div>
        </div>
        
        <div class="sidebar-menu-scroll px-2">
            <div class="bg-primary bg-opacity-25 rounded p-3 mb-4 mx-2" style="background-color: rgba(11, 76, 140, 0.5) !important;">
                <div class="fw-bold text-white"><i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($datos_usr['nombre'] . ' ' . $datos_usr['apellido']); ?></div>
                <small class="text-white-50"><?= strtoupper(str_replace('_', ' ', $rol_actual)) ?></small>
            </div>
            
            <a href="../portal_ventas/index.php" class="nav-link-custom"><i class="bi bi-shop me-2"></i> Volver a Ventas</a>
            <a href="index.php" class="text-info mt-2 border-top pt-3 border-secondary d-block fw-bold position-relative nav-link-custom active" style="background: rgba(13, 202, 240, 0.15); border-left: 4px solid #0dcaf0;">
                <i class="bi bi-graph-up-arrow text-info me-2"></i> Dashboard Finanzas
            </a>
            
            <a href="#" onclick="abrirConfiguracion()" class="nav-link-custom mt-2 text-white-50"><i class="bi bi-gear me-2"></i> Gestor Cuentas y Categorias</a>
        </div>
        
        <div class="sidebar-footer">
            <a href="../login/logout.php" class="text-danger text-decoration-none fw-bold d-block"><i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesion</a>
        </div>
    </div>

    <div class="main-content p-3 p-md-4">
        
        <div class="row mb-4">
            <div class="col-12 col-md-8 mx-auto">
                <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded shadow-sm border">
                    <button class="btn btn-light border text-primary" id="btnMesAnt"><i class="bi bi-chevron-left"></i></button>
                    <h5 id="lblMesAnio" class="mb-0 fw-bold text-center w-100 text-dark">...</h5>
                    <button class="btn btn-light border text-primary" id="btnMesSig"><i class="bi bi-chevron-right"></i></button>
                    <button class="btn btn-outline-success ms-3 text-nowrap fw-bold" onclick="toggleHistorico()"><i class="bi bi-clock-history"></i> Todo el Historico</button>
                </div>
            </div>
        </div>

        <!-- Pestanas de Navegacion (Agregada Tareas del Dia al final) -->
        <ul class="nav nav-tabs mb-4 border-bottom-0 shadow-sm bg-white rounded flex-wrap" id="myTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-resumen" type="button"><i class="bi bi-speedometer2"></i> Resumen</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-historial" type="button"><i class="bi bi-journal-text"></i> Libro Mayor</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ingresos" type="button"><i class="bi bi-arrow-down-left-circle text-success"></i> Desglose Ingresos</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-egresos" type="button"><i class="bi bi-arrow-up-right-circle text-danger"></i> Desglose Egresos</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-cobrar" type="button"><i class="bi bi-cash-coin text-success"></i> Por Cobrar</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pagar" type="button"><i class="bi bi-file-invoice-dollar text-danger"></i> Por Pagar</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tareas" type="button"><i class="bi bi-card-checklist text-primary"></i> Tareas del Dia</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-eventos" type="button"><i class="bi bi-calendar-event text-warning"></i> Eventos y asesorías</button></li>
        </ul>

        <!-- Contenido de las Pestanas -->
        <div class="tab-content" id="myTabContent">
            <?php include 'vistas/tab_resumen.php'; ?>
            <?php include 'vistas/tab_historial.php'; ?>
            <?php include 'vistas/tab_desglose_ingresos.php'; ?>
            <?php include 'vistas/tab_desglose_egresos.php'; ?>
            <?php include 'vistas/tab_cobrar.php'; ?>
            <?php include 'vistas/tab_pagar.php'; ?>
            <?php include 'vistas/tab_tareas.php'; ?> <!-- NUEVA PESTANA -->
            <?php include 'vistas/tab_eventos.php'; ?>
        </div>
    </div>

    <!-- Modales de Finanzas -->
    <?php include 'vistas/modales.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/logica_admin.js?v=<?= time(); ?>"></script>
    <script src="js/logica_tareas.js?v=<?= time(); ?>"></script> <!-- SCRIPT TAREAS -->
    <script src="js/logica_eventos.js?v=<?= time(); ?>"></script>
    <script src="../auditoria/auditoria_espia.js?v=<?= time(); ?>"></script>
</body>
</html>