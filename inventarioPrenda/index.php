<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
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
$rol_actual = strtolower($datos_usr['rol']);
$permisos_arr = explode(',', $datos_usr['permisos'] ?? '');

function tieneVector($num) {
    global $permisos_arr;
    return in_array((string)$num, $permisos_arr);
}

if (!tieneVector(46)) {
    echo "<h2 style='text-align:center; margin-top:50px; color:red;'>Acceso Denegado. No tienes el privilegio [46].</h2>";
    exit();
}

// ==============================================================================
// AUTO-CREACIÓN Y REPARACIÓN DE TABLAS AISLADAS 
// ==============================================================================
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS `inv_fisico_sedes` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `nombre` varchar(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS `inv_fisico_estantes` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `id_sede` int(11) NOT NULL, `codigo` varchar(50) NOT NULL, `nombre` varchar(100) NOT NULL, `niveles` int(11) DEFAULT 5, `columnas` int(11) DEFAULT 2) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS `inv_fisico_marcas` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `nombre` varchar(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS `inv_fisico_categorias` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `nombre` varchar(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS `inv_fisico_subcategorias` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `id_categoria` int(11) NOT NULL, `nombre` varchar(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS `inv_fisico_prendas` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_sede` int(11) DEFAULT NULL, `id_estante` int(11) DEFAULT NULL,
    `id_marca` int(11) DEFAULT NULL, `id_categoria` int(11) DEFAULT NULL, `id_subcategoria` int(11) DEFAULT NULL,
    `codigo_lote` varchar(50) NOT NULL, `articulo` varchar(150) NOT NULL, `color` varchar(50) DEFAULT '', `genero` varchar(50) DEFAULT 'Unisex',
    `talla` varchar(20) NOT NULL, `stock` int(11) DEFAULT 0, `precio_base` decimal(10,2) DEFAULT 0.00,
    `nivel_estante` int(11) DEFAULT 1, `lado_estante` varchar(20) DEFAULT '1', `orden_apilado` int(11) DEFAULT 0,
    `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

mysqli_query($conexion, "ALTER TABLE `inv_fisico_prendas` ADD COLUMN IF NOT EXISTS `id_categoria` int(11) DEFAULT NULL AFTER `id_marca`;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_prendas` ADD COLUMN IF NOT EXISTS `id_subcategoria` int(11) DEFAULT NULL AFTER `id_categoria`;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_prendas` ADD COLUMN IF NOT EXISTS `id_sede` int(11) DEFAULT NULL AFTER `id_subcategoria`;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_prendas` ADD COLUMN IF NOT EXISTS `id_estante` int(11) DEFAULT NULL AFTER `id_sede`;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_prendas` ADD COLUMN IF NOT EXISTS `color` varchar(50) DEFAULT '' AFTER `articulo`;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_prendas` ADD COLUMN IF NOT EXISTS `genero` varchar(50) DEFAULT 'Unisex' AFTER `color`;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_prendas` ADD COLUMN IF NOT EXISTS `precio_base` decimal(10,2) DEFAULT 0.00 AFTER `stock`;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_prendas` ADD COLUMN IF NOT EXISTS `detalles_extra` TEXT DEFAULT NULL AFTER `precio_base`;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_prendas` ADD COLUMN IF NOT EXISTS `orden_apilado` int(11) DEFAULT 0 AFTER `lado_estante`;");

mysqli_query($conexion, "UPDATE inv_fisico_prendas SET lado_estante = '1' WHERE lado_estante = 'izq'");
mysqli_query($conexion, "UPDATE inv_fisico_prendas SET lado_estante = '2' WHERE lado_estante = 'der'");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS `inv_fisico_notas` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_cliente` int(11) NOT NULL, `id_vendedor` int(11) DEFAULT NULL,
    `nro_nota` varchar(50) NOT NULL, `total_usd` decimal(10,2) DEFAULT 0.00,
    `estado` varchar(20) DEFAULT 'Pendiente', `fecha_emision` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

mysqli_query($conexion, "ALTER TABLE `inv_fisico_notas` ADD COLUMN IF NOT EXISTS `id_vendedor` int(11) DEFAULT NULL AFTER `id_cliente`;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_notas` ADD COLUMN IF NOT EXISTS `estado` varchar(20) DEFAULT 'Pendiente' AFTER `total_usd`;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_notas` ADD COLUMN IF NOT EXISTS `comprobante` varchar(255) DEFAULT NULL;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_notas` ADD COLUMN IF NOT EXISTS `metodo_pago` varchar(100) DEFAULT NULL;");
mysqli_query($conexion, "ALTER TABLE `inv_fisico_notas` ADD COLUMN IF NOT EXISTS `referencia` varchar(100) DEFAULT NULL;");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS `inv_fisico_notas_detalle` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_nota` int(11) NOT NULL, `id_prenda` int(11) NOT NULL,
    `cantidad` int(11) NOT NULL, `precio_unitario` decimal(10,2) NOT NULL, `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
// ==============================================================================

$clientes_bd = [];
$res_cli = mysqli_query($conexion, "SELECT ID, nombre, cedula_Rif FROM cliente ORDER BY nombre ASC");
if ($res_cli) while($row_cli = mysqli_fetch_assoc($res_cli)) $clientes_bd[] = $row_cli;

$sedes_bd = []; 
$res_sd = mysqli_query($conexion, "SELECT * FROM inv_fisico_sedes ORDER BY nombre ASC"); 
if ($res_sd) while($row = mysqli_fetch_assoc($res_sd)) $sedes_bd[] = $row;

$marcas_bd = []; $res_m = mysqli_query($conexion, "SELECT * FROM inv_fisico_marcas ORDER BY nombre ASC"); if ($res_m) while($row = mysqli_fetch_assoc($res_m)) $marcas_bd[] = $row;
$categorias_bd = []; $res_c = mysqli_query($conexion, "SELECT * FROM inv_fisico_categorias ORDER BY nombre ASC"); if ($res_c) while($row = mysqli_fetch_assoc($res_c)) $categorias_bd[] = $row;
$subcategorias_bd = []; $res_s = mysqli_query($conexion, "SELECT * FROM inv_fisico_subcategorias ORDER BY nombre ASC"); if ($res_s) while($row = mysqli_fetch_assoc($res_s)) $subcategorias_bd[] = $row;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Físico - Unifuerte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../portal_ventas/css/estilos_portal.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="css/estilos_inventario.css?v=<?= time(); ?>">
    <link rel="icon" href="../img/camisa.jpg" type="image/jpeg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>.btn-ordenar { width: 28px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 4px; background: #fff; border: 1px solid #dee2e6; color: #495057; transition: 0.2s;} .btn-ordenar:hover:not(:disabled) { background: #0B4C8C; color: #fff; border-color: #0B4C8C; } .btn-ordenar:disabled { opacity: 0.3; cursor: not-allowed; }</style>
</head>
<body>

    <div class="d-none mobile-header bg-dark text-white p-3 justify-content-between align-items-center w-100 position-sticky top-0" style="z-index: 1020; background-color: #0B4C8C !important;">
        <div class="d-flex align-items-center"><img src="../img/camisa.jpg" style="height:30px; border-radius: 4px;" class="me-2 bg-white p-1" alt="Logo"><h5 class="m-0 font-sugo">Unifuerte</h5></div>
        <button class="btn btn-outline-light btn-sm" id="btnHamburguesa"><i class="bi bi-list fs-3"></i></button>
    </div>
    <div class="overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo d-flex align-items-center mb-4">
            <div class="bg-white rounded p-1 me-2"><img src="../img/camisa.jpg" style="height:30px; object-fit:contain;" alt="Logo"></div>
            <div><h5 class="m-0 text-white font-sugo">Unifuerte</h5><small class="text-white-50" style="font-size: 0.7rem;">Inv. Físico</small></div>
        </div>
        
        <div class="sidebar-menu-scroll px-2">
            <div class="bg-primary bg-opacity-25 rounded p-3 mb-4 mx-2" style="background-color: rgba(25, 135, 84, 0.2) !important;">
                <div class="fw-bold text-white"><i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($datos_usr['nombre']); ?></div>
                <small class="text-white-50"><?= strtoupper(str_replace('_', ' ', $rol_actual)) ?></small>
            </div>
            
            <?php if(tieneVector(4)): ?>
                <a href="../portal_ventas/index.php" class="nav-link-custom"><i class="bi bi-shop me-2"></i> Volver a Ventas</a>
            <?php endif; ?>
            <?php if(tieneVector(46)): ?>
                <a href="#" class="text-success mt-2 border-top pt-3 border-secondary d-block fw-bold position-relative nav-link-custom active" style="background: rgba(25,135,84,0.15); border-left: 4px solid #198754;"><i class="bi bi-tags text-success me-2"></i> Inventario Prendas</a>
                <div class="ms-4 mt-2 mb-3">
                    <a href="#" class="nav-link-custom text-white-50 small mb-1" id="btnMenuStock" onclick="mostrarVista('vistaStock')"><i class="bi bi-box-seam me-2"></i> Stock Físico</a>
                    <a href="#" class="nav-link-custom text-white-50 small mb-1" id="btnMenuReportes" onclick="mostrarVista('vistaReportes')"><i class="bi bi-diagram-3 me-2"></i> Organizador de Estante</a>
                    <a href="#" class="nav-link-custom text-white-50 small mb-1" id="btnMenuNotas" onclick="mostrarVista('vistaNotas')"><i class="bi bi-receipt me-2"></i> Historial y Entregas</a>
                    <a href="#" class="nav-link-custom text-white-50 small mb-1" id="btnMenuCategorias" onclick="mostrarVista('vistaClasificacion')"><i class="bi bi-diagram-2 me-2"></i> Marcas y Categorías</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-footer">
            <a href="../login/logout.php" class="text-danger text-decoration-none fw-bold d-block"><i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión</a>
        </div>
    </div>

    <div class="main-content">

        <!-- ================= VISTA: STOCK FÍSICO ================= -->
        <div id="vistaStock" class="vista-modulo">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <h3 class="fw-bold m-0" style="color: #198754;"><i class="bi bi-tags-fill me-2"></i> Inventario de Prendas Físicas</h3>
                <button class="btn text-white fw-bold shadow-sm" style="background-color: #198754;" onclick="abrirModalFormPrenda()"><i class="bi bi-plus-lg me-1"></i> Registrar Prenda al Estante</button>
            </div>

            <div class="bg-white p-3 rounded shadow-sm border mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-md-2">
                        <select class="form-select border-success fw-bold text-success" id="filtro_sede" onchange="cambioSedeStock(this.value)">
                            <option value="">Todas las Sedes</option>
                            <?php foreach($sedes_bd as $sd): ?><option value="<?= $sd['id'] ?>"><?= htmlspecialchars($sd['nombre']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select border-primary fw-bold text-primary" id="filtro_estante" onchange="cargarStock()">
                            <option value="">Seleccione Sede...</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="text" id="buscadorStock" class="form-control" placeholder="Buscar color, talla..." onkeyup="cargarStock()"></div>
                    <div class="col-md-2"><select class="form-select" id="filtro_cat" onchange="cargarStock()"><option value="">Categorías...</option><?php foreach($categorias_bd as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2"><select class="form-select" id="filtro_sub" onchange="cargarStock()"><option value="">Subcat...</option><?php foreach($subcategorias_bd as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2"><select class="form-select" id="filtro_marca" onchange="cargarStock()"><option value="">Marcas...</option><?php foreach($marcas_bd as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option><?php endforeach; ?></select></div>
                </div>
            </div>

            <div class="table-responsive bg-white rounded shadow-sm border p-3">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>Lote</th><th>Ubicación</th><th>Prenda / Artículo</th><th class="text-center">Talla</th><th class="text-center">Stock</th><th class="text-center">Precio</th><th class="text-center" style="min-width: 150px;">Acciones</th></tr></thead>
                    <tbody id="tablaStockBody"><tr><td colspan="7" class="text-center text-muted">Cargando...</td></tr></tbody>
                </table>
            </div>
            
            <button class="floating-cart" data-bs-toggle="modal" data-bs-target="#modalCarritoInv" style="background-color: #198754;"><i class="bi bi-cart3"></i> <span class="badge text-success bg-white" id="lblBurbujaInv">0</span></button>
        </div>

        <!-- ================= VISTA: ORGANIZADOR DE ESTANTE ================= -->
        <div id="vistaReportes" class="vista-modulo" style="display:none;">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <h3 class="fw-bold m-0" style="color: #198754;"><i class="bi bi-diagram-3 me-2"></i> Reporte Organizador de Estante</h3>
                <div class="d-flex gap-2">
                    <select class="form-select border-success fw-bold text-success" id="filtro_sede_reporte" onchange="cambioSedeReporte(this.value)">
                        <option value="">Todas las Sedes</option>
                        <?php foreach($sedes_bd as $sd): ?><option value="<?= $sd['id'] ?>"><?= htmlspecialchars($sd['nombre']) ?></option><?php endforeach; ?>
                    </select>
                    <select class="form-select border-primary fw-bold text-primary" id="filtro_estante_reporte" onchange="cargarReportes()">
                        <option value="">Todos los Estantes</option>
                    </select>
                    <button class="btn btn-outline-secondary fw-bold shadow-sm bg-white" onclick="cargarReportes()"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                </div>
            </div>
            <div id="contenedorReporteEstante"><div class="text-center py-5 text-muted"><div class="spinner-border text-success"></div><br>Cargando posiciones...</div></div>
        </div>

        <!-- ================= VISTA: CLASIFICACIÓN ================= -->
        <div id="vistaClasificacion" class="vista-modulo" style="display:none;">
            <h3 class="fw-bold mb-4" style="color: #198754;"><i class="bi bi-diagram-2 me-2"></i> Configuración de Clasificación</h3>
            <div class="row g-4">
                <div class="col-md-4"><div class="card shadow-sm border-0 h-100" style="border-radius: 12px; border: 2px solid #0dcaf0 !important;"><div class="card-body text-center p-4"><i class="bi bi-building fs-1 text-info mb-2"></i><h5 class="fw-bold">1. Sedes / Tiendas</h5><button class="btn btn-outline-info w-100 fw-bold rounded-pill" onclick="abrirModalMantenimiento('Sedes')">Gestionar Sedes</button></div></div></div>
                <div class="col-md-4"><div class="card shadow-sm border-0 h-100" style="border-radius: 12px; border: 2px solid #0B4C8C !important;"><div class="card-body text-center p-4"><i class="bi bi-archive-fill fs-1 text-primary mb-2"></i><h5 class="fw-bold">2. Estantes / Repisas</h5><button class="btn btn-outline-primary w-100 fw-bold rounded-pill" onclick="abrirModalMantenimiento('Estantes')">Gestionar Estantes</button></div></div></div>
                <div class="col-md-4"><div class="card shadow-sm border-0 h-100" style="border-radius: 12px; border: 2px solid #e0e6ed !important;"><div class="card-body text-center p-4"><i class="bi bi-folder-fill fs-1 text-secondary mb-2"></i><h5 class="fw-bold">3. Categorías</h5><button class="btn btn-outline-success w-100 fw-bold rounded-pill" onclick="abrirModalMantenimiento('Categorías')">Gestionar Categorías</button></div></div></div>
                <div class="col-md-4"><div class="card shadow-sm border-0 h-100" style="border-radius: 12px; border: 2px solid #e0e6ed !important;"><div class="card-body text-center p-4"><i class="bi bi-diagram-3-fill fs-1 text-secondary mb-2"></i><h5 class="fw-bold">4. Subcategorías</h5><button class="btn btn-outline-success w-100 fw-bold rounded-pill" onclick="abrirModalMantenimiento('Subcategorías')">Gestionar Subcat.</button></div></div></div>
                <div class="col-md-4"><div class="card shadow-sm border-0 h-100" style="border-radius: 12px; border: 2px solid #e0e6ed !important;"><div class="card-body text-center p-4"><i class="bi bi-tags-fill fs-1 text-secondary mb-2"></i><h5 class="fw-bold">5. Marcas</h5><button class="btn btn-outline-success w-100 fw-bold rounded-pill" onclick="abrirModalMantenimiento('Marcas')">Gestionar Marcas</button></div></div></div>
            </div>
        </div>

        <!-- ================= VISTA: HISTORIAL Y ENTREGAS ================= -->
        <div id="vistaNotas" class="vista-modulo" style="display:none;">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <h3 class="fw-bold m-0" style="color: #198754;"><i class="bi bi-receipt me-2"></i> Historial Cotizaciones y Entregas</h3>
                <button class="btn btn-outline-success fw-bold bg-white shadow-sm" onclick="cargarHistorialNotas()"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
            </div>

            <div class="bg-white p-3 rounded shadow-sm border">
                <ul class="nav nav-tabs mb-3" id="tabNotas">
                    <li class="nav-item"><button class="nav-link active fw-bold text-warning" data-bs-toggle="tab" data-bs-target="#notasActivas" type="button"><i class="bi bi-file-earmark-text"></i> Pendientes de Pago</button></li>
                    <li class="nav-item"><button class="nav-link fw-bold text-success" data-bs-toggle="tab" data-bs-target="#notasFinalizadas" type="button"><i class="bi bi-check-circle-fill"></i> Pagadas (Notas Generadas)</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="notasActivas"><div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Nro. Documento</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th><th class="text-end">Total ($)</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr></thead><tbody id="tablaNotasActivasBody"><tr><td colspan="7" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-success"></div> Cargando...</td></tr></tbody></table></div></div>
                    <div class="tab-pane fade" id="notasFinalizadas"><div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Nro. Documento</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th><th class="text-end">Total ($)</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr></thead><tbody id="tablaNotasFinalizadasBody"><tr><td colspan="7" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-success"></div> Cargando...</td></tr></tbody></table></div></div>
                </div>
            </div>
        </div>

    </div> <!-- Fin Main Content -->

    <!-- ================= MODALES DEL SISTEMA ================= -->

    <!-- Modal Visualizador Estante -->
    <div class="modal fade" id="modalVisualizadorEstante" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="background-color: transparent;">
                <div class="modal-header border-0 text-white justify-content-center position-relative" style="background-color: #5c3a21; border-radius: 10px 10px 0 0;"><h5 class="modal-title fw-bold font-sugo text-uppercase" style="letter-spacing: 2px;" id="lbl_titulo_estante_modal"><i class="bi bi-archive-fill"></i> ESTANTERÍA</h5><button type="button" class="btn-close btn-close-white position-absolute end-0 me-3" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-0" style="background-color: #f4e8d8;">
                    
                    <div class="bookcase-container" id="bookcase_dinamico"></div>

                    <div id="estante_contenido_detalle" class="p-3" style="background-color: #fff; border-top: 2px dashed #5c3a21; max-height: 250px; overflow-y: auto; display: none;"><h6 class="fw-bold mb-2" style="color: #5c3a21;"><i class="bi bi-box2-fill"></i> ¿Qué hay en este hueco?</h6><ul class="list-group list-group-flush small" id="lista_prendas_slot"></ul></div>
                </div>
                <div class="modal-footer justify-content-center bg-dark text-white border-0" style="border-radius: 0 0 10px 10px;"><h6 class="m-0 fw-bold blink-text text-uppercase" id="lbl_ubicacion_exacta">Ubicación...</h6></div>
            </div>
        </div>
    </div>

    <!-- Modal Prenda -->
    <div class="modal fade" id="modalFormPrenda" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: #198754;"><h5 class="modal-title fw-bold" id="tituloModalPrenda"><i class="bi bi-box-seam"></i> Registrar Prenda Física</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body bg-light">
                    <form id="formGuardarPrenda">
                        <input type="hidden" id="inv_id_prenda" value="0">
                        <div class="row g-2 mb-3">
                            <div class="col-md-4"><label class="small fw-bold text-muted">Código de Lote</label><input type="text" id="inv_lote" class="form-control fw-bold border-secondary bg-white text-primary" readonly></div>
                            <div class="col-md-4"><label class="small fw-bold text-muted">Categoría</label><select id="inv_categoria" class="form-select border-secondary"><option value="">Seleccione...</option><?php foreach($categorias_bd as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-4"><label class="small fw-bold text-muted">Subcategoría</label><select id="inv_subcategoria" class="form-select border-secondary"><option value="">Seleccione Categoría primero</option><?php foreach($subcategorias_bd as $s): ?><option value="<?= $s['id'] ?>" data-cat="<?= $s['id_categoria'] ?>"><?= htmlspecialchars($s['nombre']) ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-4"><label class="small fw-bold text-muted">Artículo Específico *</label><input type="text" id="inv_articulo" class="form-control border-secondary" placeholder="Ej: Pantalón Jean" required></div>
                            <div class="col-md-3"><label class="small fw-bold text-muted">Color</label><input type="text" id="inv_color" class="form-control border-secondary" placeholder="Ej: Azul Oscuro"></div>
                            <div class="col-md-3"><label class="small fw-bold text-muted">Marca</label><select id="inv_marca" class="form-select border-secondary"><option value="">Seleccione...</option><?php foreach($marcas_bd as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-2"><label class="small fw-bold text-muted">Género</label><select id="inv_genero" class="form-select border-secondary"><option value="Unisex">Unisex</option><option value="Dama">Dama</option><option value="Caballero">Caballero</option><option value="Niño/a">Niño/a</option></select></div>
                        </div>

                        <!-- ATRIBUTOS ESPECÍFICOS DE GORRAS (OCULTO POR DEFECTO) -->
                        <div id="div_detalles_gorra" class="p-3 bg-white border border-info rounded mb-3 shadow-sm" style="display:none;">
                            <h6 class="fw-bold text-info mb-2"><i class="bi bi-ui-radios"></i> Atributos Específicos para Gorras</h6>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="small fw-bold text-muted">Paneles</label>
                                    <select id="gorra_paneles" class="form-select border-info">
                                        <option value="">No aplica</option>
                                        <option value="5">5 Paneles</option>
                                        <option value="6">6 Paneles</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="small fw-bold text-muted">Tipo de Tela</label>
                                    <select id="gorra_tela" class="form-select border-info">
                                        <option value="">No aplica</option>
                                        <option value="Drill">Drill</option>
                                        <option value="Sintética">Sintética</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="small fw-bold text-muted">Malla</label>
                                    <select id="gorra_malla" class="form-select border-info">
                                        <option value="">No aplica</option>
                                        <option value="Completa">Completa</option>
                                        <option value="Microperforada">Microperforada</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="small fw-bold text-muted">Tipo de Broche</label>
                                    <select id="gorra_broche" class="form-select border-info">
                                        <option value="">No aplica</option>
                                        <option value="Mágico de Tela">Mágico de Tela</option>
                                        <option value="Mágico de Goma">Mágico de Goma</option>
                                        <option value="De Hebilla">De Hebilla</option>
                                        <option value="Plástico">Completamente Plástico</option>
                                        <option value="Cerrada">Cerrada</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4"><label class="small fw-bold text-muted">Talla *</label><input type="text" id="inv_talla" class="form-control text-center fw-bold fs-5 text-primary border-secondary" placeholder="Ej: M o 32" required></div>
                            <div class="col-4"><label class="small fw-bold text-muted">Stock a Ingresar *</label><input type="number" id="inv_stock" class="form-control text-center fw-bold fs-5 text-success border-secondary" min="0" required></div>
                            <div class="col-4"><label class="small fw-bold text-muted">Precio Base ($) *</label><input type="number" step="0.01" id="inv_precio" class="form-control text-center fw-bold fs-5 text-dark border-secondary" min="0" required></div>
                        </div>
                        <div class="p-3 bg-white border rounded">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-geo-alt-fill text-danger"></i> Ubicación en Almacén / Estantería</h6>
                            <div class="row g-2">
                                <div class="col-md-6"><label class="small fw-bold text-muted">Sede / Almacén</label><select id="inv_sede" class="form-select fw-bold border-success" onchange="cambioSedePrenda(this.value)"><option value="">Seleccione...</option><?php foreach($sedes_bd as $sd): ?><option value="<?= $sd['id'] ?>"><?= htmlspecialchars($sd['nombre']) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-6"><label class="small fw-bold text-muted">Estante</label><select id="inv_estante" class="form-select fw-bold border-success" onchange="cambioEstantePrenda(this.value)"><option value="">Seleccione Sede...</option></select></div>
                                <div class="col-md-6"><label class="small fw-bold text-muted">Nivel (Fila)</label><select id="inv_nivel" class="form-select fw-bold border-primary"><option value="1">Nivel 1</option></select></div>
                                <div class="col-md-6"><label class="small fw-bold text-muted">Lado (Columna)</label><select id="inv_lado" class="form-select fw-bold border-primary"><option value="1">Columna 1</option></select></div>
                            </div>
                        </div>
                        <div class="text-end border-top pt-3 mt-3"><button type="button" class="btn btn-outline-secondary fw-bold me-2" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success fw-bold"><i class="bi bi-save"></i> Guardar en Estante</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Mantenimiento -->
    <div class="modal fade" id="modalMantenimientoCat" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl"> 
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #198754;">
                    <h5 class="modal-title fw-bold" id="tituloMantenimientoCat"><i class="bi bi-gear"></i> Gestión</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <form id="formNuevaClasificacion" class="mb-4">
                        <input type="hidden" id="mant_tipo" value="">
                        <label class="small fw-bold text-muted mb-1" id="lbl_mant_nombre">Registrar Nuevo:</label>
                        <div class="input-group">
                            <span class="input-group-text label-cat bg-white text-muted fw-bold" style="display:none;">Padre:</span>
                            <select id="mant_select_cat" class="form-select" style="display:none; max-width:200px;"><option value="">Seleccione...</option><?php foreach($categorias_bd as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option><?php endforeach; ?></select>
                            
                            <span class="input-group-text label-sede bg-white text-muted fw-bold" style="display:none;">Sede:</span>
                            <select id="mant_select_sede" class="form-select" style="display:none; max-width:200px;"><option value="">Seleccione...</option><?php foreach($sedes_bd as $sd): ?><option value="<?= $sd['id'] ?>"><?= htmlspecialchars($sd['nombre']) ?></option><?php endforeach; ?></select>
                            
                            <span class="input-group-text label-estantes bg-white text-muted fw-bold" style="display:none;">Cód:</span>
                            <input type="text" id="mant_codigo" class="form-control" style="display:none; max-width:100px;" placeholder="Ej: A1">
                            
                            <span class="input-group-text bg-white text-muted fw-bold">Nombre:</span>
                            <input type="text" id="mant_nombre" class="form-control" placeholder="Nombre / Descripción..." required>
                            
                            <span class="input-group-text label-estantes bg-white text-muted fw-bold" style="display:none;">Filas:</span>
                            <input type="number" id="mant_niveles" class="form-control" style="display:none; max-width:90px;" placeholder="Filas" min="1" value="5" title="Niveles (Filas)">
                            
                            <span class="input-group-text label-estantes bg-white text-muted fw-bold" style="display:none;">Cols:</span>
                            <input type="number" id="mant_columnas" class="form-control" style="display:none; max-width:90px;" placeholder="Columnas" min="1" value="2" title="Lados (Columnas)">
                            
                            <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-save me-1"></i> Guardar</button>
                        </div>
                    </form>
                    <h6 class="fw-bold text-dark border-bottom pb-2">Listado Actual</h6>
                    <div class="table-responsive bg-white rounded border" style="max-height: 400px; overflow-y:auto;">
                        <table class="table table-sm table-hover align-middle m-0">
                            <thead class="table-light"><tr><th>Detalle</th><th class="text-center" style="width:60px;">Acción</th></tr></thead>
                            <tbody id="tbodyMantenimiento"><tr><td colspan="2" class="text-center text-muted">Cargando...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales Carrito -->
    <div class="modal fade" id="modalAddCarrito" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content border-0 shadow"><div class="modal-header text-dark" style="background-color: #ffc107;"><h5 class="modal-title fw-bold"><i class="bi bi-cart-plus"></i> Añadir a Cotización</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body bg-light"><form id="formAddCarrito"><input type="hidden" id="add_id_prenda"><input type="hidden" id="add_nombre_prenda"><div class="alert alert-light border shadow-sm text-center"><h6 class="fw-bold text-dark mb-1" id="add_lbl_prenda">Nombre Prenda</h6><small class="text-muted">Stock Disp: <strong id="add_lbl_stock" class="text-success fs-6">0</strong></small></div><div class="mb-3"><label class="small fw-bold text-muted">Cantidad a Vender</label><input type="number" id="add_cantidad" class="form-control text-center fw-bold fs-5 border-success" min="1" required></div><div class="mb-3"><label class="small fw-bold text-muted">Precio Unitario ($)</label><input type="number" step="0.01" id="add_precio" class="form-control text-center fw-bold fs-5 border-primary" required></div><div class="text-center border-top pt-3"><button type="submit" class="btn btn-warning text-dark fw-bold w-100"><i class="bi bi-cart-plus"></i> Agregar Prenda</button></div></form></div></div></div></div>
    
    <div class="modal fade" id="modalCarritoInv" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable"><div class="modal-content border-0 shadow-lg"><div class="modal-header border-0 text-white" style="background-color: #198754;"><h5 class="modal-title fw-bold"><i class="bi bi-cart-fill"></i> Prendas para Cotización Inmediata</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body bg-light"><div class="row"><div class="col-lg-7"><h6 class="fw-bold text-success mb-2"><i class="bi bi-list-check"></i> Resumen de Prendas</h6><div class="table-responsive bg-white rounded border shadow-sm mb-3"><table class="table table-sm align-middle m-0" style="font-size:0.85rem;"><thead class="table-light"><tr><th>Descripción</th><th class="text-center">Cant</th><th class="text-end">P.Unit</th><th class="text-end">Subt</th><th></th></tr></thead><tbody id="tbodyCarritoInv"><tr><td colspan="5" class="text-center text-muted py-3">Carrito vacío</td></tr></tbody></table></div><div class="text-end"><h5 class="fw-bold text-dark">Total: <span id="lblTotalCarritoInv" class="text-success">0.00 $</span></h5></div></div><div class="col-lg-5"><div class="bg-white p-3 rounded shadow-sm border h-100"><h6 class="fw-bold text-success mb-3"><i class="bi bi-person-fill"></i> Datos del Cliente</h6><div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="checkNuevoCliente" onchange="toggleFormularioCliente()"><label class="form-check-label small fw-bold text-primary" for="checkNuevoCliente">Registrar Cliente Nuevo</label></div><div id="div_seleccionar_cliente"><label class="small fw-bold text-muted">Buscar en BD</label><select id="cart_cliente_existente" class="form-select border-primary"><option value="">Seleccione o busque un cliente...</option><?php foreach($clientes_bd as $c): ?><option value="<?= $c['ID'] ?>"><?= htmlspecialchars($c['cedula_Rif'].' - '.$c['nombre']) ?></option><?php endforeach; ?></select></div><div id="div_crear_cliente" style="display:none;"><div class="mb-2"><input type="text" id="cart_cli_nombre" class="form-control form-control-sm" placeholder="Nombre / Razón Social"></div><div class="mb-2"><input type="text" id="cart_cli_cedula" class="form-control form-control-sm" placeholder="Cédula / RIF"></div><div class="mb-2"><input type="text" id="cart_cli_tlf" class="form-control form-control-sm" placeholder="Teléfono"></div></div><button type="button" class="btn btn-success fw-bold w-100 mt-4" id="btnGenerarFacturaInv" onclick="generarNotaEntrega()"><i class="bi bi-file-earmark-text"></i> Generar Cotización</button></div></div></div></div></div></div></div>

    <!-- Modal Pagar y Generar Nota de Entrega -->
    <div class="modal fade" id="modalPagarNota" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-dark" style="background-color: #ffc107;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-currency-dollar"></i> Pagar Cotización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <form id="formPagarNota" enctype="multipart/form-data">
                        <input type="hidden" id="pagar_id_nota" name="id_nota">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Método de Pago</label>
                            <select class="form-select border-primary" id="pagar_metodo" name="metodo_pago" required>
                                <option value="">Seleccione...</option>
                                <option value="Pago Móvil">Pago Móvil</option>
                                <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                                <option value="Divisas Efectivo">Divisas Efectivo</option>
                                <option value="Zelle">Zelle</option>
                                <option value="Binance">Binance</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Referencia</label>
                            <input type="text" class="form-control border-primary" id="pagar_ref" name="referencia" placeholder="Nro de Referencia" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Comprobante de Pago (Foto o PDF)</label>
                            <input type="file" class="form-control border-success" id="pagar_comprobante" name="comprobante" accept="image/*,.pdf" required>
                        </div>
                        <div class="text-end border-top pt-3">
                            <button type="button" class="btn btn-outline-secondary fw-bold me-2" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning fw-bold text-dark"><i class="bi bi-check2-circle"></i> Confirmar Pago</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../portal_ventas/js/operaciones_portal.js?v=<?= time(); ?>"></script>
    <script src="js/logica_inventario.js?v=<?= time(); ?>"></script>
    <script src="../auditoria/auditoria_espia.js?v=<?= time(); ?>"></script>
</body>
</html>