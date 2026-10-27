<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol'])) {
    header("Location: ../login/index.php");
    exit();
}

$id_usuario = intval($_SESSION['usuario_id']);
$q_usr = mysqli_query($conexion, "SELECT rol, permisos, nombre, apellido FROM usuario WHERE ID = $id_usuario");
$datos_usr = mysqli_fetch_assoc($q_usr);
$permisos_arr = explode(',', $datos_usr['permisos'] ?? '');

function tieneVector($num) {
    global $permisos_arr;
    return in_array((string)$num, $permisos_arr);
}

if (!tieneVector(39)) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px;'><h2>Acceso Denegado</h2><p>No tienes el Vector [39] para acceder al Módulo de Nómina.</p><a href='../portal_ventas/index.php'>Volver al Portal</a></div>");
}

// Actualizaciones automáticas de BD
mysqli_query($conexion, "ALTER TABLE nomina_empleados ADD COLUMN IF NOT EXISTS horario INT DEFAULT 1 AFTER cargo;");
mysqli_query($conexion, "ALTER TABLE nomina_empleados ADD COLUMN IF NOT EXISTS departamento VARCHAR(100) AFTER horario;");
mysqli_query($conexion, "ALTER TABLE nomina_empleados ADD COLUMN IF NOT EXISTS banco VARCHAR(100) AFTER telefono;");
mysqli_query($conexion, "ALTER TABLE nomina_empleados ADD COLUMN IF NOT EXISTS nro_cuenta VARCHAR(20) AFTER banco;");

mysqli_query($conexion, "ALTER TABLE nomina_cuentas ADD COLUMN IF NOT EXISTS moneda VARCHAR(10) DEFAULT 'USD';");
mysqli_query($conexion, "ALTER TABLE nomina_cuentas ADD COLUMN IF NOT EXISTS tasa_bcv DECIMAL(15,4) DEFAULT 0.00;");
mysqli_query($conexion, "ALTER TABLE nomina_cuentas ADD COLUMN IF NOT EXISTS monto_original DECIMAL(15,2) DEFAULT 0.00;");
mysqli_query($conexion, "ALTER TABLE nomina_cuentas ADD COLUMN IF NOT EXISTS id_lote_pago INT DEFAULT 0;");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS nomina_prestaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    fecha_calculo DATE,
    dias_acumulados INT DEFAULT 0,
    salario_promedio DECIMAL(15,2) DEFAULT 0.00,
    monto_total DECIMAL(15,2) DEFAULT 0.00,
    motivo VARCHAR(100),
    estado VARCHAR(20) DEFAULT 'Pendiente',
    FOREIGN KEY (id_empleado) REFERENCES nomina_empleados(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// --- CARGA DIRECTA DE DATOS PARA EL DASHBOARD ---
$q_activos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM nomina_empleados WHERE estado = 'Activo'");
$tot_activos = mysqli_fetch_assoc($q_activos)['total'] ?? 0;

$q_pagado = mysqli_query($conexion, "SELECT SUM(total_pagado) as total FROM nomina_pagos WHERE MONTH(fecha_pago) = MONTH(CURRENT_DATE()) AND YEAR(fecha_pago) = YEAR(CURRENT_DATE())");
$tot_pagado = number_format(mysqli_fetch_assoc($q_pagado)['total'] ?? 0, 2);

$q_faltas = mysqli_query($conexion, "SELECT SUM(dias_falta) as total FROM nomina_pagos WHERE MONTH(fecha_pago) = MONTH(CURRENT_DATE()) AND YEAR(fecha_pago) = YEAR(CURRENT_DATE())");
$tot_faltas = floatval(mysqli_fetch_assoc($q_faltas)['total'] ?? 0);

// --- CARGA DE EMPLEADOS PARA LOS SELECTS ---
$q_lista_emp = mysqli_query($conexion, "SELECT id, cedula, nombre, apellido, fecha_ingreso FROM nomina_empleados WHERE estado = 'Activo' ORDER BY nombre ASC");
$opts_empleados = "";
while($emp = mysqli_fetch_assoc($q_lista_emp)){
    $f_ingreso = empty($emp['fecha_ingreso']) ? '' : $emp['fecha_ingreso'];
    $opts_empleados .= "<option value='{$emp['id']}' data-ingreso='{$f_ingreso}'>{$emp['nombre']} {$emp['apellido']} ({$emp['cedula']})</option>";
}

$inicio_quincena_default = date('Y-m-01');
$fin_quincena_default = (intval(date('d')) <= 15) ? date('Y-m-15') : date('Y-m-t');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unifuerte - Gestión de Nómina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../img/camisa.jpg" type="image/jpeg">
    <link rel="stylesheet" href="css/estilos.css?v=1.6">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo d-flex align-items-center mb-4">
        <div class="bg-white rounded p-1 me-2"><img src="../img/camisa.jpg" style="height:30px; object-fit:contain;" alt="Logo"></div>
        <div><h5 class="m-0 text-white font-sugo">Unifuerte</h5><small class="text-white-50" style="font-size: 0.7rem;">Recursos Humanos</small></div>
    </div>
    <div class="sidebar-menu-scroll px-2">
        <div class="rounded p-3 mb-4 mx-2" style="background-color: rgba(255, 117, 24, 0.2);">
            <div class="fw-bold text-white"><i class="bi bi-person-badge-fill me-2"></i><?php echo htmlspecialchars($datos_usr['nombre']); ?></div>
            <small class="text-white-50">NÓMINA / RRHH</small>
        </div>
        <a class="nav-link-custom active" onclick="mostrarVista('Dashboard', this)"><i class="bi bi-house-door-fill me-2"></i> Panel Resumen</a>
        <?php if(tieneVector(40)): ?><a class="nav-link-custom" onclick="mostrarVista('Registro', this)"><i class="bi bi-people-fill me-2"></i> Personal y Registro</a><?php endif; ?>
        <a class="nav-link-custom" onclick="mostrarVista('Asistencia', this)"><i class="bi bi-calendar2-check-fill me-2"></i> Hoja de Asistencia</a>
        <?php if(tieneVector(41)): ?><a class="nav-link-custom" onclick="mostrarVista('Pagos', this)"><i class="bi bi-wallet-fill me-2"></i> Generar Pagos</a><?php endif; ?>
        <?php if(tieneVector(42)): ?><a class="nav-link-custom" onclick="mostrarVista('Prestaciones', this)"><i class="bi bi-award-fill me-2"></i> Prestaciones Sociales</a><?php endif; ?>
        <?php if(tieneVector(41)): ?><a class="nav-link-custom" onclick="mostrarVista('Prestamos', this)"><i class="bi bi-cash-stack me-2"></i> Vales, Deudas y Préstamos</a><?php endif; ?>
        <a href="../portal_ventas/index.php" class="nav-link-custom text-info mt-4 border-top pt-3 border-secondary"><i class="bi bi-arrow-left-circle-fill me-2"></i> Volver a Operaciones</a>
    </div>
    <div class="sidebar-footer"><a href="../login/logout.php" class="text-danger text-decoration-none fw-bold d-block"><i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión</a></div>
</div>

<div class="main-content">
    <!-- VISTA DASHBOARD -->
    <div id="vistaDashboard" class="vista-spa">
        <h3 class="fw-bold m-0 mb-4" style="color: #0B4C8C;"><i class="bi bi-bar-chart-steps me-2"></i> Panel General de Nómina</h3>
        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="card card-stat p-4 text-center"><i class="bi bi-people fs-1" style="color: #0B4C8C;"></i><h2 class="fw-bold mt-2" id="resumen_empleados"><?php echo $tot_activos; ?></h2><span class="text-muted fw-bold">Empleados Activos</span></div></div>
            <div class="col-md-4"><div class="card card-stat p-4 text-center"><i class="bi bi-cash-coin fs-1 text-success"></i><h2 class="fw-bold mt-2">$<?php echo $tot_pagado; ?></h2><span class="text-muted fw-bold">Pagado este mes</span></div></div>
            <div class="col-md-4"><div class="card card-stat p-4 text-center"><i class="bi bi-calendar-x fs-1 text-warning"></i><h2 class="fw-bold mt-2"><?php echo $tot_faltas; ?></h2><span class="text-muted fw-bold">Faltas / Ausencias</span></div></div>
        </div>
    </div>

    <!-- VISTA REGISTRO DE PERSONAL -->
    <?php if(tieneVector(40)): ?>
    <div id="vistaRegistro" class="vista-spa" style="display: none;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-person-lines-fill me-2"></i> Directorio de Personal</h3>
            <button class="btn btn-nomina fw-bold px-4 rounded-pill shadow-sm" onclick="abrirModalEmpleado()"><i class="bi bi-plus-circle me-1"></i> Nuevo Empleado</button>
        </div>
        <div class="bg-white p-4 rounded shadow-sm border table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>Cédula</th><th>Trabajador</th><th>Cargo / Área</th><th>Datos Bancarios</th><th class="text-end">Salario ($)</th><th class="text-end">Cesta Ticket ($)</th><th class="text-end">Bono ($)</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr></thead>
                <tbody id="tablaEmpleadosBody"><tr><td colspan="9" class="text-center text-muted py-4">Cargando trabajadores...</td></tr></tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- VISTA ASISTENCIA -->
    <div id="vistaAsistencia" class="vista-spa" style="display: none;">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-clock-history me-2"></i> Hoja de Asistencia</h3>
                <small id="indicador_autoguardado_global" class="text-success fw-bold"><i class="bi bi-cloud-check-fill"></i> Auto-guardado automático activado</small>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="btn-group shadow-sm" role="group">
                    <button type="button" class="btn btn-outline-primary fw-bold active" id="btnModoTodosAsis" onclick="cambiarModoVistaAsistencia('todos')">
                        <i class="bi bi-calendar-day me-1"></i> Vista General (Por Día)
                    </button>
                    <button type="button" class="btn btn-outline-primary fw-bold" id="btnModoIndividualAsis" onclick="cambiarModoVistaAsistencia('individual')">
                        <i class="bi bi-calendar-range me-1"></i> Vista Individual (Rango de Fechas)
                    </button>
                </div>
                <button class="btn btn-primary fw-bold px-4 rounded-pill shadow-sm" id="btnGuardarPrincipalAsistencia" onclick="accionBotonGuardarPrincipalAsistencia()">
                    <i class="bi bi-save me-1"></i> <span id="lblBotonGuardarPrincipalAsis">Guardar Asistencia del Día</span>
                </button>
                <button type="button" class="btn btn-outline-dark fw-bold rounded-pill shadow-sm" onclick="imprimirHojaAsistencia()">
                    <i class="bi bi-printer me-1"></i> Imprimir asistencia
                </button>
            </div>
        </div>

        <div class="bg-white p-4 rounded shadow-sm border table-responsive">
            <!-- FILTRO MODO GENERAL (UN SOLO DÍA) -->
            <div id="bloqueFiltroDiaUnico" class="row mb-3 align-items-end g-3">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Seleccione la Fecha</label>
                    <input type="date" id="filtro_fecha_asistencia" class="form-control fw-bold border-primary" value="<?= date('Y-m-d') ?>" onchange="cargarAsistencia()">
                    <small id="fechaAsistenciaEtiqueta" class="d-block mt-1 fw-bold text-primary"></small>
                </div>
                <div class="col-md-9 text-md-end">
                    <span class="badge bg-light text-dark border p-2 shadow-sm fs-6 d-inline-block text-wrap text-start">
                        <i class="bi bi-info-circle text-primary"></i>
                        H1: 7am-4pm (viernes hasta 3pm) | H2: 8am-12pm | H3: 7am-2pm | Descuento por llegada tarde H1/H3 desde 7:30am
                    </span>
                </div>
            </div>

            <!-- PANEL MODO INDIVIDUAL (EMPLEADO + RANGO DE FECHAS DESDE / HASTA) -->
            <div id="panelModoIndividualAsistencia" class="p-3 mb-3 rounded border border-primary bg-primary bg-opacity-10 shadow-sm" style="display: none;">
                <div class="row g-2 align-items-end mb-3 pb-3 border-bottom border-primary border-opacity-25">
                    <div class="col-md-3">
                        <label class="small fw-bold text-dark"><i class="bi bi-calendar-event text-primary"></i> Fecha Desde (Inicio)</label>
                        <input type="date" id="filtro_fecha_inicio_ind" class="form-control fw-bold border-primary" value="<?= $inicio_quincena_default ?>" onchange="cargarAsistenciaRangoEmpleado()">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-dark"><i class="bi bi-calendar-check text-primary"></i> Fecha Hasta (Fin)</label>
                        <input type="date" id="filtro_fecha_fin_ind" class="form-control fw-bold border-primary" value="<?= $fin_quincena_default ?>" onchange="cargarAsistenciaRangoEmpleado()">
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-wrap gap-1 justify-content-md-end align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-dark fw-bold" onclick="setRangoRapidoAsistencia('q1')"><i class="bi bi-calendar2-week"></i> 1ra Quincena</button>
                            <button type="button" class="btn btn-sm btn-outline-dark fw-bold" onclick="setRangoRapidoAsistencia('q2')"><i class="bi bi-calendar2-week"></i> 2da Quincena</button>
                            <button type="button" class="btn btn-sm btn-outline-dark fw-bold" onclick="setRangoRapidoAsistencia('mes')"><i class="bi bi-calendar3"></i> Mes Actual</button>
                            <div class="form-check form-switch ms-2 mb-0 bg-white px-2 py-1 rounded border">
                                <input class="form-check-input" type="checkbox" id="chk_incluir_finde_ind" onchange="renderizarTablaRangoEmpleado()">
                                <label class="form-check-label small fw-bold" for="chk_incluir_finde_ind" style="cursor:pointer;">Ver Sáb/Dom</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row align-items-center g-2">
                    <div class="col-md-3 text-start">
                        <button type="button" class="btn btn-outline-primary bg-white fw-bold w-100 shadow-sm" id="btnAsisPrevEmp" onclick="cambiarEmpleadoIndividualPaso(-1)">
                            <i class="bi bi-arrow-left-circle me-1"></i> Empleado Anterior
                        </button>
                    </div>
                    <div class="col-md-6 text-center">
                        <label class="small fw-bold text-primary d-block mb-1">
                            <i class="bi bi-person-check-fill"></i> Seleccionar Trabajador (<span id="contador_empleado_individual">0 de 0</span>)
                        </label>
                        <select id="select_empleado_individual" class="form-select fw-bold border-primary text-center shadow-sm" onchange="seleccionarEmpleadoIndividual(this.value)"></select>
                    </div>
                    <div class="col-md-3 text-end">
                        <button type="button" class="btn btn-outline-primary bg-white fw-bold w-100 shadow-sm" id="btnAsisNextEmp" onclick="cambiarEmpleadoIndividualPaso(1)">
                            Siguiente Empleado <i class="bi bi-arrow-right-circle ms-1"></i>
                        </button>
                    </div>
                </div>

                <div class="row g-2 mt-3 text-center" id="resumenRangoEmpleadoBox">
                    <div class="col-6 col-md-3">
                        <div class="bg-white p-2 rounded border shadow-sm">
                            <small class="text-muted d-block fw-bold">Días Presente / Feriado</small>
                            <span class="fs-5 fw-bold text-success" id="ind_tot_presentes">0</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-white p-2 rounded border shadow-sm">
                            <small class="text-muted d-block fw-bold">Días de Falta</small>
                            <span class="fs-5 fw-bold text-danger" id="ind_tot_faltas">0</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-white p-2 rounded border shadow-sm">
                            <small class="text-muted d-block fw-bold">Retardo Acumulado</small>
                            <span class="fs-5 fw-bold text-danger" id="ind_tot_retardo">0m</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-white p-2 rounded border shadow-sm">
                            <small class="text-muted d-block fw-bold">Tiempo Extra Acumulado</small>
                            <span class="fs-5 fw-bold text-warning text-dark" id="ind_tot_extra">0m</span>
                        </div>
                    </div>
                </div>
            </div>

            <table class="table table-hover align-middle table-bordered text-center">
                <thead class="table-light">
                    <tr>
                        <th class="text-start" id="thColumnaPrincipalAsistencia">Trabajador y Horario</th>
                        <th>Estado</th>
                        <th>Hora Entrada</th>
                        <th>Hora Salida</th>
                        <th>Cálculo de Retardo y Extras</th>
                        <th style="width: 170px;">Guardado Individual</th>
                    </tr>
                </thead>
                <tbody id="tablaAsistenciaBody">
                    <tr><td colspan="6" class="text-muted">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- VISTA PAGOS (HISTORIAL DE LOTES Y ARCHIVO) -->
    <?php if(tieneVector(41)): ?>
    <div id="vistaPagos" class="vista-spa" style="display: none;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-wallet-fill me-2"></i> Gestión de Pagos (Lotes)</h3>
            <button class="btn btn-success fw-bold px-4 rounded-pill shadow-sm" onclick="abrirModalGenerarNomina()"><i class="bi bi-play-circle me-1"></i> Procesar Nueva Nómina</button>
        </div>
        <div class="bg-white p-4 rounded shadow-sm border">
            <ul class="nav nav-pills mb-4 border-bottom pb-3" id="pills-tab-pagos" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" id="pills-pendientes-tab" data-bs-toggle="pill" data-bs-target="#pills-pendientes" type="button" role="tab"><i class="bi bi-clock-history me-1"></i> Nóminas Pendientes</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="pills-archivo-tab" data-bs-toggle="pill" data-bs-target="#pills-archivo" type="button" role="tab"><i class="bi bi-archive-fill me-1"></i> Archivo Histórico</button></li>
            </ul>
            <div class="tab-content" id="pills-tabContentPagos">
                <div class="tab-pane fade show active" id="pills-pendientes" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="font-size: 0.9rem;">
                            <thead class="table-light"><tr><th class="text-center">Nro. Lote</th><th>Fecha de Pago</th><th>Período Laborado</th><th class="text-end">Total Pagado ($)</th><th class="text-end">Total Pagado (Bs)</th><th class="text-center">Estado</th><th class="text-center">Aval (Foto)</th><th class="text-center">Acciones y Documentos</th></tr></thead>
                            <tbody id="tablaPagosPendientesBody"><tr><td colspan="8" class="text-center text-muted py-4">No hay nóminas pendientes.</td></tr></tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-archivo" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="buscadorArchivo" class="form-control border-start-0 ps-0" placeholder="Buscar por Lote, Fecha o Período...">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="font-size: 0.9rem;">
                            <thead class="table-light"><tr><th class="text-center">Nro. Lote</th><th>Fecha de Pago</th><th>Período Laborado</th><th class="text-end">Total Pagado ($)</th><th class="text-end">Total Pagado (Bs)</th><th class="text-center">Estado</th><th class="text-center">Aval (Foto)</th><th class="text-center">Acciones y Documentos</th></tr></thead>
                            <tbody id="tablaPagosArchivoBody"><tr><td colspan="8" class="text-center text-muted py-4">El archivo está vacío.</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- VISTA PRESTACIONES Y LIQUIDACIONES -->
    <?php if(tieneVector(42)): ?>
    <div id="vistaPrestaciones" class="vista-spa" style="display: none;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-award-fill me-2"></i> Prestaciones Sociales y Liquidaciones</h3>
            <div>
                <button class="btn btn-secondary fw-bold px-3 rounded-pill shadow-sm me-2" id="btnToggleArchivadosPrest" onclick="toggleArchivadosPrestaciones()"><i class="bi bi-archive"></i> Ver Archivados</button>
                <button class="btn btn-danger fw-bold px-4 rounded-pill shadow-sm" onclick="abrirModalLiquidacion()"><i class="bi bi-calculator me-1"></i> Nueva Liquidación</button>
            </div>
        </div>
        <div class="bg-white p-4 rounded shadow-sm border table-responsive">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-person text-danger"></i> <span class="titulo-prestaciones-tabla">Personal Liquidado / Egresado</span></h6>
            <table class="table table-hover align-middle" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr><th>Fecha Egreso</th><th>Trabajador / Cédula</th><th>Motivo</th><th class="text-center">Días Garantía</th><th class="text-end">Salario Integral (Diario)</th><th class="text-end fw-bold text-danger">Total Liquidado ($)</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr>
                </thead>
                <tbody id="tablaPrestacionesBody"><tr><td colspan="8" class="text-center text-muted py-4">No hay liquidaciones registradas.</td></tr></tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- VISTA VALES, DEUDAS DE NÓMINA Y PRÉSTAMOS -->
    <?php if(tieneVector(41)): ?>
    <div id="vistaPrestamos" class="vista-spa" style="display: none;">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-cash-stack me-2"></i> Vales, Deudas de Nómina y Préstamos</h3>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-warning fw-bold px-4 rounded-pill shadow-sm" onclick="abrirModalVale()">
                    <i class="bi bi-dash-circle me-1"></i> Registrar Vale (Descuento)
                </button>
                <button class="btn btn-success fw-bold px-4 rounded-pill shadow-sm" onclick="abrirModalDeudaNomina()">
                    <i class="bi bi-plus-circle me-1"></i> Registrar Deuda a Trabajador ($)
                </button>
                <button class="btn btn-primary fw-bold px-4 rounded-pill shadow-sm" onclick="abrirModalPrestamoVendedor()">
                    <i class="bi bi-person-plus-fill me-1"></i> Préstamo a Vendedor
                </button>
            </div>
        </div>

        <!-- 1. TABLA DE VALES EMITIDOS (POR COBRAR / DESCUENTO AL TRABAJADOR) -->
        <div class="bg-white p-4 rounded shadow-sm border table-responsive">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark m-0"><i class="bi bi-arrow-down-right-circle-fill text-warning me-1"></i> Historial de Vales Emitidos (Por Descontar al Trabajador)</h6>
                <small class="text-muted">Los vales en estado <b>Por Descontar</b> se restan automáticamente en la columna "Préstamos (A cobrar)" al generar la nómina.</small>
            </div>
            <table class="table table-hover align-middle" style="font-size: 0.9rem;">
                <thead class="table-light"><tr><th>Fecha</th><th>Trabajador</th><th>Concepto</th><th class="text-end">Monto</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr></thead>
                <tbody id="tablaValesBody"><tr><td colspan="6" class="text-center text-muted py-4">Cargando vales...</td></tr></tbody>
            </table>
        </div>

        <!-- 2. NUEVA TABLA: DEUDAS DE NÓMINA CON EL PERSONAL (POR PAGAR AL TRABAJADOR EN USD) -->
        <div class="bg-white p-4 rounded shadow-sm border border-success border-opacity-50 table-responsive mt-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="fw-bold text-success m-0"><i class="bi bi-cash-coin me-1"></i> Deudas de Nómina con el Personal (A Pagar al Trabajador en USD)</h6>
                    <p class="small text-muted mb-0">Registre manualmente cualquier deuda pendiente a favor del trabajador en dólares. Se sumará automáticamente en la columna <b>"Pago Pendiente (Vales a Favor)"</b> al generar la nómina.</p>
                </div>
                <button class="btn btn-sm btn-outline-success fw-bold rounded-pill" onclick="abrirModalDeudaNomina()">
                    <i class="bi bi-plus-lg"></i> Agregar Deuda en USD
                </button>
            </div>
            <table class="table table-hover align-middle" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Trabajador</th>
                        <th>Concepto / Motivo de la Deuda</th>
                        <th class="text-end">Se le Debe ($ USD)</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaDeudasNominaBody">
                    <tr><td colspan="6" class="text-center text-muted py-4">Cargando deudas de nómina...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- 3. TABLA PRÉSTAMOS DE VENDEDORES -->
        <div class="bg-white p-4 rounded shadow-sm border table-responsive mt-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-graph-down-arrow text-primary"></i> Préstamos de Vendedores — Descuento de Comisiones</h6>
            <p class="small text-muted">Solo usuarios con rol Vendedor o Jefe de Ventas. Cada comisión registrada abona primero el préstamo pendiente más antiguo.</p>
            <table class="table table-hover align-middle" style="font-size: 0.9rem;">
                <thead class="table-light"><tr><th>Fecha</th><th>Vendedor del Portal</th><th>Concepto</th><th class="text-end">Original</th><th class="text-end">Abonado por comisiones</th><th class="text-end">Saldo USD</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr></thead>
                <tbody id="tablaPrestamosVendedoresBody"><tr><td colspan="8" class="text-center text-muted py-4">Cargando préstamos de vendedores...</td></tr></tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL PERFIL EMPLEADO -->
<div class="modal fade" id="modalPerfilEmpleado" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-vcard"></i> Perfil del Trabajador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><div class="card p-3 border-0 shadow-sm text-center h-100" style="border-bottom: 4px solid #3b82f6 !important;"><i class="bi bi-calendar3 fs-1 text-primary"></i><h3 class="fw-bold mt-2 mb-0" id="perfil_dias_trabajados">0</h3><small class="text-muted fw-bold">Días Trabajados en la Empresa</small></div></div>
                    <div class="col-md-4"><div class="card p-3 border-0 shadow-sm text-center h-100" style="border-bottom: 4px solid #10b981 !important;"><i class="bi bi-cash-stack fs-1 text-success"></i><h3 class="fw-bold mt-2 mb-0 text-success" id="perfil_total_pagado">$0.00</h3><small class="text-muted fw-bold">Total Histórico Pagado</small></div></div>
                    <div class="col-md-4"><div class="card p-3 border-0 shadow-sm h-100" style="border-bottom: 4px solid #FF7518 !important;"><h5 class="fw-bold text-dark mb-1" id="perfil_nombre">Nombre Empleado</h5><p class="mb-1 text-muted"><i class="bi bi-person-badge"></i> <span id="perfil_cargo">Cargo</span></p><p class="mb-1 text-muted"><i class="bi bi-card-text"></i> <span id="perfil_cedula">V-0000</span></p><p class="mb-0 text-muted"><i class="bi bi-calendar-event"></i> Ingreso: <span id="perfil_ingreso">00/00/0000</span></p></div></div>
                </div>
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-primary"></i> Historial Personal de Pagos</h6>
                <div class="table-responsive bg-white rounded border shadow-sm" style="max-height: 300px;">
                    <table class="table table-hover table-sm align-middle text-center mb-0">
                        <thead class="table-light sticky-top"><tr><th>Fecha Pago</th><th>Período</th><th>Ingresos Fijos</th><th>Bonos (+)</th><th>Deducciones (-)</th><th style="background-color: #f0fdfa;">Total ($)</th></tr></thead>
                        <tbody id="tbody_perfil_pagos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LIQUIDACIÓN (PRESTACIONES) -->
<div class="modal fade" id="modalLiquidacion" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-calculator"></i> Simulador de Liquidación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="formSimularLiquidacion" class="row g-3">
                    <div class="col-md-12">
                        <label class="small fw-bold text-muted">Empleado a Liquidar</label>
                        <select class="form-select fw-bold border-danger" id="liq_id_empleado" required>
                            <option value="">Seleccione...</option>
                            <?php echo $opts_empleados; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="small fw-bold text-muted">Fecha de Ingreso</label><input type="date" class="form-control" id="liq_fecha_ingreso" required></div>
                    <div class="col-md-4"><label class="small fw-bold text-muted">Fecha de Egreso</label><input type="date" class="form-control" id="liq_fecha_egreso" required></div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Motivo de Salida</label>
                        <select class="form-select" id="liq_motivo" required>
                            <option value="Renuncia Voluntaria">Renuncia Voluntaria</option>
                            <option value="Despido Injustificado">Despido Injustificado</option>
                            <option value="Despido Justificado">Despido Justificado</option>
                            <option value="Fin de Contrato">Fin de Contrato</option>
                        </select>
                    </div>
                    <div class="col-12 mt-3"><button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-lightning-charge"></i> Calcular Liquidación</button></div>
                </form>
                <div id="liq_resultados" style="display:none;" class="mt-4">
                    <h6 class="fw-bold text-danger border-bottom border-danger pb-2 mb-3"><i class="bi bi-receipt"></i> Hoja de Cálculo de Prestaciones (LOTTT)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><div class="p-2 border rounded bg-white"><small class="text-muted d-block">Días Calendario:</small><strong id="res_dias_cal"></strong></div></div>
                        <div class="col-md-4"><div class="p-2 border rounded bg-danger bg-opacity-10 border-danger"><small class="text-danger d-block">Días Faltados:</small><strong id="res_dias_falta" class="text-danger"></strong></div></div>
                        <div class="col-md-4"><div class="p-2 border rounded bg-success bg-opacity-10 border-success"><small class="text-success d-block">Días Efectivos Lab.:</small><strong id="res_dias_efectivos" class="text-success"></strong></div></div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><div class="p-2 border rounded bg-white"><small class="text-muted d-block">Tiempo Servicio:</small><strong id="res_tiempo"></strong></div></div>
                        <div class="col-md-4"><div class="p-2 border rounded bg-white"><small class="text-muted d-block">Salario Mensual Base:</small><strong id="res_salario_mensual"></strong></div></div>
                        <div class="col-md-4"><div class="p-2 border rounded bg-info bg-opacity-10 border-info"><small class="text-primary d-block">Salario Integral Diario:</small><strong id="res_salario_integral" class="text-primary"></strong></div></div>
                    </div>
                    <table class="table table-sm table-bordered align-middle bg-white text-end" style="font-size: 0.85rem;">
                        <thead class="table-light text-center"><tr><th class="text-start">Concepto de Ley</th><th>Asignaciones (+)</th><th>Deducciones (-)</th></tr></thead>
                        <tbody>
                            <tr><td class="text-start">Garantía Prestaciones (<span id="res_dias_prest"></span> Días)</td><td class="text-success fw-bold" id="res_monto_prest"></td><td></td></tr>
                            <tr><td class="text-start">Vacaciones Fraccionadas</td><td class="text-success" id="res_vac_frac"></td><td></td></tr>
                            <tr><td class="text-start">Bono Vacacional Fraccionado</td><td class="text-success" id="res_bvac_frac"></td><td></td></tr>
                            <tr><td class="text-start">Utilidades Fraccionadas</td><td class="text-success" id="res_uti_frac"></td><td></td></tr>
                            <tr id="fila_indemnizacion" style="display:none; background-color:#fef2f2;"><td class="text-start text-danger fw-bold">Indemnización (Art 92 LOTTT)</td><td class="text-danger fw-bold" id="res_indemnizacion"></td><td></td></tr>
                            <tr><td class="text-start">Vales / Préstamos Pendientes</td><td></td><td class="text-danger fw-bold" id="res_deuda"></td></tr>
                        </tbody>
                        <tfoot class="table-dark fs-5"><tr><td class="text-end">NETO A PAGAR:</td><td colspan="2" class="text-center text-success fw-bold" id="res_total_liquidar"></td></tr></tfoot>
                    </table>
                    <div class="alert alert-warning small fw-bold text-center mt-3"><i class="bi bi-exclamation-triangle"></i> ¡Atención! Al "Guardar", el empleado pasará a Inactivo y se cerrarán sus cuentas pendientes.</div>
                    <button class="btn btn-danger w-100 fw-bold shadow-sm" onclick="guardarLiquidacionDefinitiva()">Guardar y Liquidar Empleado Definitivamente</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL GENERAR NÓMINA (LISTÍN) -->
<div class="modal fade" id="modalGenerarNomina" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-table"></i> Generador de Listín de Nómina</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light" style="padding: 10px;">
                <form id="formFiltroNomina" class="row g-2 mb-3 p-3 bg-white border rounded shadow-sm align-items-end">
                    <input type="hidden" id="nom_id_lote_edit" value="0">
                    <div class="col-md-2"><label class="small fw-bold text-muted">Inicio del Período</label><input type="date" class="form-control" id="nom_fecha_inicio" required></div>
                    <div class="col-md-2"><label class="small fw-bold text-muted">Fin del Período</label><input type="date" class="form-control" id="nom_fecha_fin" required></div>
                    <div class="col-md-2"><label class="small fw-bold text-muted">Fecha del Pago</label><input type="date" class="form-control" id="nom_fecha_pago" required></div>
                    <div class="col-md-3"><label class="small fw-bold text-muted" style="color: #0B4C8C !important;">Tasa BCV (Bs)</label><input type="number" step="0.0001" class="form-control fw-bold border-success text-success" id="nom_tasa_bcv" placeholder="Ej: 880.11" value="0.00" onchange="renderTablaNomina()" oninput="renderTablaNomina()" required></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-calculator"></i> Sincronizar Asistencia / Vales</button></div>
                </form>
                <div id="box_previa_nomina" style="display:none;">
                    <div class="table-responsive bg-white border rounded shadow-sm">
                        <table class="table table-bordered table-sm align-middle text-center mb-0" style="font-size: 11px; min-width: 1800px;">
                            <thead class="table-light align-middle" style="background-color: #e2e8f0;">
                                <tr>
                                    <th rowspan="2" style="width: 80px;">Acción</th>
                                    <th rowspan="2" style="width: 150px;">Nombre y Apellido</th>
                                    <th colspan="4" style="background-color: #d1fae5; color: #065f46;">ASIGNACIONES FIJAS</th>
                                    <th colspan="5" style="background-color: #e0f2fe; color: #0369a1;">HORAS EXTRAS Y BONOS</th>
                                    <th rowspan="2" style="background-color: #bae6fd; color: #0369a1;">Pago Pendiente<br>(Deuda a Favor)</th>
                                    <th rowspan="2" style="background-color: #93c5fd; color: #075985;">TOTAL<br>ASIGNACIONES</th>
                                    <th colspan="5" style="background-color: #ffedd5; color: #9a3412;">DEDUCCIONES</th>
                                    <th rowspan="2" style="background-color: #fca5a5; color: #991b1b;">TOTAL<br>DEDUCCIONES</th>
                                    <th colspan="2" style="background-color: #dcfce7; color: #166534;">NETO A PAGAR</th>
                                </tr>
                                <tr style="background-color: #f1f5f9;">
                                    <th>Sueldo (Q)</th><th>Cesta Ticket</th><th>Bono</th><th>Total Percibir<br>Quincenal</th>
                                    <th>Días Extras<br>(Sáb/Feriado)</th>
                                    <th>H/E Diurna<br>($2.88/h)</th><th>H/E Noct.<br>($2.88/h)</th><th>Total $ H/E<br>y Sábados</th><th>Bono de Prod.</th>
                                    <th>Días Falta</th><th>Hrs Falta<br>($2.88/h)</th><th>$ Días F.<br>(salario / 30)</th><th>$ Hrs F.</th><th>Préstamos / Vales<br>(A cobrar)</th>
                                    <th>Total en $</th><th>Pago en Bs</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_previa_nomina"></tbody>
                            <tfoot class="table-light fw-bold" style="font-size: 13px;">
                                <tr>
                                    <td colspan="2" class="text-end bg-secondary text-white">TOTAL GENERAL:</td>
                                    <td class="text-success" id="tot_sueldo"></td><td class="text-success" id="tot_cesta"></td><td class="text-success" id="tot_bono"></td><td class="col-ingreso" id="tot_percibir"></td>
                                    <td></td><td></td><td></td><td class="text-primary" id="tot_he"></td><td class="text-primary" id="tot_prod"></td><td class="text-primary" id="tot_pend"></td><td class="bg-primary text-white" id="tot_asig"></td>
                                    <td></td><td></td><td class="text-danger" id="tot_df"></td><td class="text-danger" id="tot_hf"></td><td class="text-danger" id="tot_pres"></td><td class="bg-danger text-white" id="tot_deduc"></td>
                                    <td class="bg-success text-white" id="tot_neto_usd"></td><td class="bg-dark text-white" id="tot_neto_bs"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold px-4 shadow" id="btnProcesarNomina" style="display:none;" onclick="procesarNominaDefinitiva()">Aprobar y Guardar Nómina</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDICIÓN DE VALORES DE NÓMINA INDIVIDUAL -->
<div class="modal fade" id="modalEditarValoresNomina" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-lines-fill"></i> Editar Parámetros de Nómina</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <h6 id="editNom_nombre" class="fw-bold text-dark mb-0">Nombre Empleado</h6>
                <p id="editNom_cargo" class="text-muted small mb-3">Cargo</p>
                <div class="row g-2 mb-3">
                    <div class="col-12"><strong class="text-success border-bottom d-block mb-2">Asignaciones Fijas</strong></div>
                    <div class="col-4"><label class="form-label small">Sueldo (Q) USD</label><input type="number" step="0.01" class="form-control form-control-sm" id="editNom_sueldo"></div>
                    <div class="col-4"><label class="form-label small">Cesta Ticket USD</label><input type="number" step="0.01" class="form-control form-control-sm" id="editNom_cesta"></div>
                    <div class="col-4"><label class="form-label small">Bono Fijo USD</label><input type="number" step="0.01" class="form-control form-control-sm border-warning" id="editNom_bono"></div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-12"><strong class="text-info border-bottom d-block mb-2">Horas Extras, Bonos y Deuda a Favor</strong></div>
                    <div class="col-3"><label class="form-label small">Días Extras</label><input type="number" step="0.5" class="form-control form-control-sm" id="editNom_sabados" placeholder="Sábados"></div>
                    <div class="col-3"><label class="form-label small">H/E Diurna (Hrs)</label><input type="number" step="0.25" class="form-control form-control-sm" id="editNom_he_diurna"></div>
                    <div class="col-3"><label class="form-label small">H/E Noct. (Hrs)</label><input type="number" step="0.25" class="form-control form-control-sm" id="editNom_he_noct"></div>
                    <div class="col-3"><label class="form-label small">Bono Prod. ($)</label><input type="number" step="0.01" class="form-control form-control-sm border-primary" id="editNom_bono_prod"></div>
                    <div class="col-12 mt-2">
                        <label class="form-label small fw-bold text-primary">Pago Pendiente / Deuda a Favor del Trabajador ($ USD)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm border-info fw-bold" id="editNom_pago_pendiente">
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-12"><strong class="text-danger border-bottom d-block mb-2">Deducciones / Faltas / Vales</strong></div>
                    <div class="col-4"><label class="form-label small">Días Faltados</label><input type="number" step="0.5" class="form-control form-control-sm border-danger" id="editNom_dias_falta"></div>
                    <div class="col-4"><label class="form-label small">Horas Falta / Ret.</label><input type="number" step="0.25" class="form-control form-control-sm border-danger" id="editNom_horas_falta"></div>
                    <div class="col-4"><label class="form-label small fw-bold text-danger">Vales a Cobrar ($)</label><input type="number" step="0.01" class="form-control form-control-sm border-danger fw-bold" id="editNom_vales"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold" onclick="guardarEdicionNomina()"><i class="bi bi-save"></i> Aplicar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NUEVO EMPLEADO -->
<div class="modal fade" id="modalEmpleado" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill"></i> Registrar / Editar Empleado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="formEmpleado" class="row g-2">
                    <input type="hidden" name="action" value="guardar">
                    <input type="hidden" id="emp_id" name="id_empleado" value="0">
                    <div class="col-md-6"><label class="small fw-bold text-muted">Cédula</label><input type="text" class="form-control" id="emp_cedula" name="cedula" required></div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Teléfono</label><input type="text" class="form-control" id="emp_telefono" name="telefono" required></div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Nombres</label><input type="text" class="form-control" id="emp_nombre" name="nombre" required></div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Apellidos</label><input type="text" class="form-control" id="emp_apellido" name="apellido" required></div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Cargo</label><input type="text" class="form-control border-primary" id="emp_cargo" name="cargo" placeholder="Ej: Costurera Tipo I" required></div>
                    <div class="col-md-6 align-self-end"><button type="button" class="btn btn-secondary w-100 fw-bold" onclick="asignarSalarioSugerido()"><i class="bi bi-magic"></i> Auto-Asignar Salario</button></div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Horario Asignado</label>
                        <select class="form-select border-primary" id="emp_horario" name="horario" required>
                            <option value="1">Horario 1 (7:00 AM a 4:00 PM)</option>
                            <option value="2">Horario 2 (8:00 AM a 12:00 PM)</option>
                            <option value="3">Horario 3 (7:00 AM a 2:00 PM)</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Área / Departamento</label><input type="text" class="form-control" id="emp_departamento" name="departamento"></div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Banco</label><input type="text" class="form-control" id="emp_banco" name="banco" placeholder="Ej: Banesco"></div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Nro de Cuenta (20 Dígitos)</label><input type="text" class="form-control" id="emp_nro_cuenta" name="nro_cuenta"></div>
                    <div class="col-md-4"><label class="small fw-bold text-muted">Salario Base ($)</label><input type="number" step="0.01" class="form-control border-success text-success fw-bold" id="emp_salario" name="salario" required></div>
                    <div class="col-md-4"><label class="small fw-bold text-muted">Cesta Ticket ($)</label><input type="number" step="0.01" class="form-control border-success fw-bold" id="emp_cesta" name="cesta_ticket" value="0" required></div>
                    <div class="col-md-4"><label class="small fw-bold text-muted">Bonos Fijos ($)</label><input type="number" step="0.01" class="form-control border-warning fw-bold" id="emp_bonos" name="bonos" value="0" required></div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Frecuencia de Pago</label><select class="form-select" id="emp_frecuencia" name="frecuencia" required><option value="Semanal">Semanal</option><option value="Quincenal" selected>Quincenal</option><option value="Mensual">Mensual</option></select></div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Fecha de Ingreso</label><input type="date" class="form-control" id="emp_fecha_ingreso" name="fecha_ingreso" required></div>
                    <div class="col-12" id="div_estado_emp" style="display:none;"><label class="small fw-bold text-muted">Estado</label><select class="form-select" id="emp_estado" name="estado"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select></div>
                    <div class="col-12 mt-3 text-end"><button type="button" class="btn btn-outline-secondary fw-bold me-2" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-save"></i> Guardar Ficha</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL SUBIDA INDIVIDUAL DE AVALES -->
<div class="modal fade" id="modalSubidaIndividual" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-cloud-arrow-up"></i> Cargar Recibos Firmados (Aval) - Lote #<span id="lbl_lote_subida"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="table-responsive bg-white rounded shadow-sm border">
                    <table class="table table-sm align-middle table-hover mb-0">
                        <thead class="table-light"><tr><th>Trabajador</th><th class="text-end">Monto Cobrado</th><th class="text-center">Estado de Aval</th><th class="text-center">Acción</th></tr></thead>
                        <tbody id="tbody_subida_individual"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VER COMPROBANTES MULTIPLES -->
<div class="modal fade" id="modalVerComprobantes" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-images"></i> Comprobantes de Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light text-center" id="contenedor_comprobantes"></div>
        </div>
    </div>
</div>

<!-- MODAL NUEVO VALE / PRÉSTAMO (POR COBRAR AL TRABAJADOR) -->
<div class="modal fade" id="modalNuevoVale" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-cash-stack"></i> Registrar Vale de Nómina (Descuento)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="formNuevoVale" class="row g-3">
                    <input type="hidden" name="action" value="guardar">
                    <input type="hidden" id="vale_id" name="id_vale" value="0">
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Trabajador</label>
                        <select class="form-select fw-bold border-primary" id="vale_id_empleado" name="id_empleado" required>
                            <option value="">Seleccione...</option>
                            <?php echo $opts_empleados; ?>
                        </select>
                    </div>
                    <div class="col-12"><label class="small fw-bold text-muted">Concepto del Vale</label><input type="text" class="form-control" id="vale_concepto" name="concepto" placeholder="Ej: Adelanto de quincena" required></div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Fecha Emisión</label><input type="date" class="form-control" id="vale_fecha" name="fecha" required></div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Moneda</label>
                        <select class="form-select" id="vale_moneda" name="moneda" onchange="calcularEquivalenteVale()" required>
                            <option value="USD" selected>Dólares (USD)</option>
                            <option value="BS">Bolívares (Bs)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Monto</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                            <input type="number" step="0.01" min="0.01" class="form-control fw-bold" id="vale_monto" name="monto" onkeyup="calcularEquivalenteVale()" onchange="calcularEquivalenteVale()" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Tasa BCV</label>
                        <div class="input-group">
                            <span class="input-group-text">Bs</span>
                            <input type="number" step="0.0001" min="0" class="form-control" id="vale_tasa" name="tasa_bcv" value="0" onkeyup="calcularEquivalenteVale()" onchange="calcularEquivalenteVale()" required>
                        </div>
                    </div>
                    <div class="col-12 text-center mt-3"><div id="vale_equivalencia" class="alert alert-secondary p-2 mb-0 fw-bold small border">Ingrese Tasa BCV y Monto para ver la equivalencia</div></div>
                    <div class="col-12 mt-4 text-end">
                        <button type="button" class="btn btn-outline-secondary fw-bold me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning fw-bold shadow"><i class="bi bi-save"></i> Guardar Vale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NUEVA DEUDA DE NÓMINA A TRABAJADOR (POR PAGAR EN USD) -->
<div class="modal fade" id="modalDeudaNomina" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin"></i> Registrar Deuda de Nómina al Trabajador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="formDeudaNomina" class="row g-3">
                    <input type="hidden" name="action" value="guardar_deuda_nomina">
                    <input type="hidden" id="deuda_nomina_id" name="id_deuda" value="0">
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Persona / Trabajador al que se le debe</label>
                        <select class="form-select fw-bold border-success" id="deuda_id_empleado" name="id_empleado" required>
                            <option value="">Seleccione al trabajador...</option>
                            <?php echo $opts_empleados; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Concepto / Detalle de la Deuda</label>
                        <input type="text" class="form-control" id="deuda_concepto" name="concepto" placeholder="Ej: Diferencia de quincena anterior / Horas pendientes / Bono adeudado" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Fecha de Registro</label>
                        <input type="date" class="form-control" id="deuda_fecha" name="fecha" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-success">Cuánto se le debe ($ USD)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-success text-white fw-bold">$</span>
                            <input type="number" step="0.01" min="0.01" class="form-control fw-bold border-success text-success" id="deuda_monto" name="monto" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-success p-2 mb-0 small fw-bold border">
                            <i class="bi bi-info-circle-fill me-1"></i> Este monto en dólares se agregará automáticamente a favor del trabajador en la columna <b>"Pago Pendiente (Deuda a Favor)"</b> cuando procese la nómina.
                        </div>
                    </div>
                    <div class="col-12 mt-3 text-end">
                        <button type="button" class="btn btn-outline-secondary fw-bold me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold shadow"><i class="bi bi-save"></i> Guardar Deuda ($)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PRÉSTAMO A VENDEDOR -->
<div class="modal fade" id="modalPrestamoVendedor" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill"></i> Préstamo a Vendedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="formPrestamoVendedor" class="row g-3">
                    <input type="hidden" name="action" value="guardar_prestamo_vendedor">
                    <input type="hidden" id="prestamo_vendedor_id" name="id_prestamo" value="0">
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Vendedor / Jefe de Ventas del Portal</label>
                        <select class="form-select fw-bold border-primary" id="prestamo_id_vendedor" name="id_vendedor" required>
                            <option value="">Cargando usuarios vendedores...</option>
                        </select>
                    </div>
                    <div class="col-12"><label class="small fw-bold text-muted">Concepto del préstamo</label><input type="text" class="form-control" id="prestamo_vendedor_concepto" name="concepto" maxlength="255" required></div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Fecha</label><input type="date" class="form-control" id="prestamo_vendedor_fecha" name="fecha" required></div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Moneda del monto entregado</label>
                        <select class="form-select" id="prestamo_vendedor_moneda" name="moneda" onchange="calcularEquivalentePrestamoVendedor()" required>
                            <option value="USD">Dólares (USD)</option>
                            <option value="BS">Bolívares (Bs)</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Monto entregado</label><input type="number" step="0.01" min="0.01" class="form-control fw-bold" id="prestamo_vendedor_monto" name="monto" oninput="calcularEquivalentePrestamoVendedor()" required></div>
                    <div class="col-md-6"><label class="small fw-bold text-muted">Tasa BCV</label><input type="number" step="0.0001" min="0" class="form-control" id="prestamo_vendedor_tasa" name="tasa_bcv" value="0" oninput="calcularEquivalentePrestamoVendedor()" required></div>
                    <div class="col-12"><div id="prestamo_vendedor_equivalencia" class="alert alert-secondary p-2 mb-0 fw-bold small border">El saldo de la deuda se registra en dólares y se descontará de futuras comisiones.</div></div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-outline-secondary fw-bold me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-save"></i> Guardar préstamo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- INPUTS OCULTOS FOTOS -->
<input type="file" id="input_foto_recibo_indiv" style="display:none;" accept="image/*,application/pdf" multiple>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/nomina.js?v=<?php echo time(); ?>"></script>
</body>
</html>