<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once '../../conexion.php'; 

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol'])) {
    header("Location: ../../login/index.php");
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

if (!tieneVector(21) && !tieneVector(27)) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>
            <h2>Acceso Denegado</h2>
            <p>No tienes los privilegios necesarios ([21] o [27]) para acceder al módulo de Inventario y Recetas.</p>
            <a href='../../portal_ventas/index.php'>Volver al inicio</a>
         </div>");
}

// Cargar listas previas para selects (Se usan dentro de las vistas incluidas)
$categorias = [];
$res_cat = mysqli_query($conexion, "SELECT * FROM mp_categorias ORDER BY nombre ASC");
if($res_cat) while($row = mysqli_fetch_assoc($res_cat)) $categorias[] = $row;

$subcategorias = [];
$res_sub = mysqli_query($conexion, "SELECT * FROM mp_subcategorias ORDER BY nombre ASC");
if($res_sub) while($row = mysqli_fetch_assoc($res_sub)) $subcategorias[] = $row;

// CON ESTA CONSULTA INYECTAMOS LA CATEGORÍA Y SUBCATEGORÍA AL NOMBRE PARA BUSCAR FÁCIL
$materia_prima = [];
$res_mp = mysqli_query($conexion, "
    SELECT m.ID, 
    CONCAT(IFNULL(c.nombre, 'Sin Cat'), ' > ', IFNULL(s.nombre, 'General'), ' | ', m.nombre, IF(m.color != '', CONCAT(' (', m.color, ')'), '')) as nombre, 
    m.unidad_medida, m.costo 
    FROM materia_prima m 
    LEFT JOIN mp_subcategorias s ON m.id_mp_subcategoria = s.id 
    LEFT JOIN mp_categorias c ON s.id_mp_categoria = c.id 
    ORDER BY c.nombre ASC, m.nombre ASC
");
if($res_mp) while($row = mysqli_fetch_assoc($res_mp)) $materia_prima[] = $row;

// IGUAL PARA LAS PRENDAS
$productos = [];
$res_prod = mysqli_query($conexion, "
    SELECT p.id, 
    CONCAT(IFNULL(c.nombre, 'Catálogo'), ' | ', p.nombre) as nombre 
    FROM productos p
    LEFT JOIN subcategorias s ON p.id_subcategoria = s.id
    LEFT JOIN categorias c ON s.id_categoria = c.id
    ORDER BY c.nombre ASC, p.nombre ASC
");
if($res_prod) while($row = mysqli_fetch_assoc($res_prod)) $productos[] = $row;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unifuerte - Inventario y Recetas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="icon" href="../../img/camisa.jpg" type="image/jpeg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        @font-face {
            font-family: 'Sugo Display';
            src: url('../../fons/SugoDisplay.otf') format('opentype'),
                 url('../../fons/SugoDisplay.ttf') format('truetype');
            font-weight: normal; font-style: normal;
        }
        .font-sugo { font-family: 'Sugo Display', sans-serif !important; letter-spacing: 1px; }

        body { background-color: #f8fafc; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        
        .sidebar { top: 0; left: 0; height: 100vh; height: 100dvh; background: #0a2540; color: white; width: 260px; position: fixed; z-index: 1040; transition: transform 0.3s ease; display: flex; flex-direction: column; }
        .sidebar-logo { border-bottom: 1px solid rgba(255,255,255,0.1); padding: 20px; flex-shrink: 0; }
        .sidebar-menu-scroll { flex: 1; overflow-y: auto; overflow-x: hidden; padding-bottom: 20px; }
        .sidebar-menu-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-menu-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
        .sidebar-footer { margin-top: auto; padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.1); background: #062b50; flex-shrink: 0; }
        
        .nav-link-custom { color: #d0d6e0; padding: 12px 20px; display: block; text-decoration: none; font-weight: 500; transition: 0.2s; margin-bottom: 5px; cursor: pointer; }
        .nav-link-custom:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link-custom.active { background: #083766; color: #ffffff; border-left: 4px solid #FF7518; border-radius: 0 8px 8px 0; margin-right: 15px;}
        
        .main-content { margin-left: 260px; padding: 30px 40px; transition: margin 0.3s ease; background-color: #f8fafc; min-height: 100vh; padding-bottom: 80px;}
        .overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1030; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; padding: 15px; padding-bottom: 80px;} .overlay.active { display: block; } .mobile-header { display: flex !important; } }
        
        .table-hover tbody tr:hover { background-color: #f1f5f9; cursor: pointer; }
        .receta-cart-box { background: #fdfdfd; border: 2px dashed #0B4C8C; border-radius: 10px; padding: 15px; }
        .lista-cart-items { max-height: 250px; overflow-y: auto; }

        .select2-container .select2-selection--single { height: 38px !important; border: 1px solid #0b4c8c !important; border-radius: 6px !important; display: flex !important; align-items: center !important; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { color: #212529 !important; font-weight: 500; }
    </style>
</head>
<body>
    <input type="hidden" id="mis_vectores" value="<?= $datos_usr['permisos'] ?>">

    <!-- HEADER MÓVIL CON LOGO -->
    <div class="d-none mobile-header text-white p-3 justify-content-between align-items-center w-100 position-sticky top-0" style="z-index: 1020; background-color: #0B4C8C !important;">
        <div class="d-flex align-items-center">
            <img src="../../img/camisa.jpg" style="height:30px; object-fit:contain; border-radius: 4px;" class="me-2 bg-white p-1" alt="Logo">
            <h5 class="m-0 font-sugo">Unifuerte</h5>
        </div>
        <button class="btn btn-outline-light btn-sm" id="btnHamburguesa"><i class="bi bi-list fs-3"></i></button>
    </div>
    <div class="overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo d-flex align-items-center mb-4">
            <div class="bg-white rounded p-1 me-2"><img src="../../img/camisa.jpg" style="height:30px; object-fit:contain;" alt="Logo"></div>
            <div>
                <h5 class="m-0 text-white font-sugo">Unifuerte</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Módulo Inventario</small>
            </div>
        </div>
        
        <div class="sidebar-menu-scroll px-2">
            <div class="bg-primary bg-opacity-25 rounded p-3 mb-4 mx-2" style="background-color: rgba(255, 117, 24, 0.2) !important;">
                <div class="fw-bold text-white"><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($datos_usr['nombre']); ?></div>
                <small class="text-white-50"><?= strtoupper(str_replace('_', ' ', $rol_actual)) ?></small>
            </div>
            
            <?php if(tieneVector(21)): ?>
            <a class="nav-link-custom active" id="btnMenuCatMp"><i class="bi bi-folder-fill me-2"></i> 1. Categorías MP</a>
            <a class="nav-link-custom" id="btnMenuSubCatMp"><i class="bi bi-diagram-3-fill me-2"></i> 2. Subcategorías MP</a>
            <a class="nav-link-custom" id="btnMenuMp"><i class="bi bi-box-seam me-2"></i> 3. Materia Prima</a>
            <a class="nav-link-custom" id="btnMenuProveedores"><i class="bi bi-person-vcard me-2"></i> 4. Proveedores</a>
            <a class="nav-link-custom" id="btnMenuCompras"><i class="bi bi-cart-check-fill me-2"></i> 5. Compras (Inventario)</a>
            <a class="nav-link-custom" id="btnMenuPatrones"><i class="bi bi-bezier me-2"></i> 6. Patrones de Costura</a>
            <a class="nav-link-custom" id="btnMenuGastos"><i class="bi bi-tools me-2"></i> 8. Gastos por Servicios</a>
            <a class="nav-link-custom" id="btnMenuConsumos"><i class="bi bi-scissors me-2"></i> 9. Consumo de Órdenes</a>
            <?php endif; ?>

            <?php if(tieneVector(27)): ?>
            <a class="nav-link-custom <?= !tieneVector(21) ? 'active' : '' ?>" id="btnMenuReceta"><i class="bi bi-file-earmark-ruled me-2"></i> 7. Ficha Técnica</a>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-footer">
            <a href="../../portal_ventas/index.php" class="text-info text-decoration-none fw-bold d-block mb-3"><i class="bi bi-arrow-left-circle me-2"></i> Volver a Ventas</a>
            <a href="../../login/logout.php" class="text-danger text-decoration-none fw-bold d-block"><i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión</a>
        </div>
    </div>

    <!-- CONTENIDO PRINCIPAL SPA -->
    <div class="main-content">
        <?php 
        if(tieneVector(21)){
            include 'vistas/1_categorias_sub.php';
            include 'vistas/2_materia_prima.php';
            include 'vistas/3_proveedores.php';
            include 'vistas/4_compras.php';
            include 'vistas/5_gastos.php';
            include 'vistas/6_patrones.php';
        }
        if(tieneVector(27)){
            include 'vistas/7_recetas.php';
        }
        if(file_exists('modal_estado_cuenta_proveedor.php')) {
            include 'modal_estado_cuenta_proveedor.php'; 
        }
        ?>

        <!-- ================= VISTA DE CONSUMOS DE ÓRDENES ================= -->
        <div id="vistaConsumos" class="vista-spa" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold" style="color: #0B4C8C;"><i class="bi bi-scissors me-2"></i> Consumo de Órdenes (Inventario)</h3>
            </div>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th>Nro Orden</th>
                                    <th>Fecha Emisión</th>
                                    <th>Cliente</th>
                                    <th>Estado Inventario</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyOrdenesConsumo">
                                <!-- Llenado por JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= MODAL PREVISUALIZAR CONSUMO ================= -->
        <div class="modal fade" id="modalConsumoOrden" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header text-white" style="background-color: #0B4C8C;">
                        <h5 class="modal-title fw-bold"><i class="bi bi-calculator"></i> Previsualización de Consumo: <span id="lbl_orden_consumo"></span></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <p class="text-muted small mb-3">El sistema ha cruzado las prendas de esta orden con las recetas guardadas. Esto es lo que se requiere del almacén:</p>
                        <div class="table-responsive bg-white rounded shadow-sm border p-2">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Insumo / Materia Prima</th>
                                        <th class="text-center">Stock Actual</th>
                                        <th class="text-end">A Descontar</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyDetalleConsumo">
                                    <!-- Llenado por JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success fw-bold" id="btnConfirmarDescuento" onclick="confirmarDescuentoInventario()"><i class="bi bi-check-circle"></i> Confirmar Descuento</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- ================= FIN VISTA CONSUMOS ================= -->

    </div>

    <script>
        const subcategoriasMap = <?= json_encode($subcategorias); ?>;
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- === ARCHIVOS JS MODULARIZADOS === -->
    <script src="js/mod_catalogo_mp.js?v=<?= time(); ?>"></script>
    <script src="js/mod_proveedores_compras.js?v=<?= time(); ?>"></script>
    <script src="js/mod_otros.js?v=<?= time(); ?>"></script>
    <script src="js/app_core.js?v=<?= time(); ?>"></script>
    <script src="../../auditoria/auditoria_espia.js?v=<?= time(); ?>"></script>
</body>
</html>