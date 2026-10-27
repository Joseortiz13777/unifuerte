<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once '../conexion.php';

// Protección de sesión
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol'])) {
    header("Location: ../login/index.php");
    exit();
}

// =========================================================================
// CREAR LA TABLA AUTOMÁTICAMENTE SI NO EXISTE
// =========================================================================
$sql_table = "CREATE TABLE IF NOT EXISTS ayuda_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    fotos TEXT DEFAULT NULL,
    estado VARCHAR(50) DEFAULT 'Pendiente',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
mysqli_query($conexion, $sql_table);

$id_usuario = intval($_SESSION['usuario_id']);
$q_usr = mysqli_query($conexion, "SELECT rol, permisos, nombre, apellido FROM usuario WHERE ID = $id_usuario");
$datos_usr = mysqli_fetch_assoc($q_usr);

$rol_actual = strtolower($datos_usr['rol']);
$permisos_arr = explode(',', $datos_usr['permisos'] ?? '');

function tieneVector($num) {
    global $permisos_arr;
    return in_array((string)$num, $permisos_arr);
}

$puede_marcar_corregido = ($rol_actual === 'master') ? 1 : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco de Mejoras - Unifuerte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../portal_ventas/css/estilos_portal.css?v=<?= time(); ?>">
    <link rel="icon" href="../img/camisa.jpg" type="image/jpeg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
    /* Estructura Base Flexbox */
    body { display: flex !important; flex-direction: column; min-height: 100vh; overflow-x: hidden; margin: 0; background-color: #f4f5f8; }
    
    .sidebar { position: fixed !important; top: 0; left: -300px; width: 260px; height: 100vh; overflow-y: auto; overflow-x: hidden; transition: left 0.3s ease; background-color: #0b1a2e !important; z-index: 1040; }
    .sidebar.active { left: 0; }
    .overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0,0,0,0.5); z-index: 1030; display: none; opacity: 0; transition: opacity 0.3s ease; }
    .overlay.active { display: block; opacity: 1; }
    
    .main-content { flex-grow: 1; min-width: 0; height: calc(100vh - 60px); overflow-y: auto; margin-left: 0 !important; transition: all 0.3s ease; background-color: #f4f5f8; padding: 20px; }
    
    /* Adaptación para PC */
    @media (min-width: 768px) {
        body { flex-direction: row; overflow: hidden; }
        .main-content { height: 100vh; padding: 30px 40px; }
        .sidebar { position: relative !important; left: 0; flex-shrink: 0; width: 260px; transition: width 0.3s ease; }
        .sidebar.collapsed { width: 75px !important; }
        .sidebar.collapsed .nav-text, .sidebar.collapsed .badge { display: none !important; }
        .sidebar.collapsed .nav-link-custom { justify-content: center !important; padding-left: 0 !important; padding-right: 0 !important; }
        .sidebar.collapsed .nav-link-custom i { margin-right: 0 !important; font-size: 1.4rem; }
        .sidebar.collapsed .user-box { padding: 10px !important; }
        .sidebar.collapsed .user-icon { margin-right: 0 !important; font-size: 1.5rem; }
        .sidebar.collapsed .sidebar-logo { justify-content: center; padding-left: 0 !important; }
        .sidebar.collapsed .sidebar-logo .logo-icon-box { margin-right: 0 !important; margin-left: 0 !important; }
        .sidebar.collapsed .sidebar-footer a { justify-content: center; }
        .sidebar.collapsed .sidebar-footer a i { margin-right: 0 !important; font-size: 1.5rem; }
    }

    /* ESTILOS DE LOS TICKETS Y MODAL */
    .header-ayuda { background: linear-gradient(135deg, #0B4C8C 0%, #062b50 100%); color: white; padding: 30px; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 10px 20px rgba(11, 76, 140, 0.15); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;}
    @keyframes pulseBtn { 0% { box-shadow: 0 0 0 0 rgba(255, 117, 24, 0.7); } 70% { box-shadow: 0 0 0 15px rgba(255, 117, 24, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 117, 24, 0); } }
    .btn-reportar { background-color: #FF7518; color: white; border: none; padding: 12px 25px; font-size: 1.1rem; font-weight: bold; border-radius: 50px; transition: 0.3s; animation: pulseBtn 2s infinite; display: inline-flex; align-items: center;}
    .btn-reportar:hover { background-color: #e66310; transform: translateY(-2px); color: white; }
    .ticket-card { background: #fff; border: 1px solid #eef2f5; border-radius: 16px; box-shadow: 0 5px 15px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s; overflow: hidden; }
    .ticket-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(11, 76, 140, 0.1); }
    .ticket-card.estado-pendiente { border-left: 5px solid #0dcaf0; }
    .ticket-card.estado-corregido { border-left: 5px solid #198754; opacity: 0.85; }
    .foto-thumb { height: 70px; width: 70px; object-fit: cover; border-radius: 8px; border: 2px solid #e9ecef; transition: 0.3s; cursor: pointer; }
    .foto-thumb:hover { border-color: #0B4C8C; transform: scale(1.05); }
    .caja-descripcion { background: #f8fafc; border-radius: 8px; padding: 15px; font-size: 0.95rem; color: #444; border: 1px dashed #ced4da; margin-bottom: 15px; flex-grow: 1; }
    .upload-area { border: 2px dashed #0B4C8C; background-color: #f0f4f8; padding: 20px; border-radius: 12px; text-align: center; transition: 0.3s; cursor: pointer; }
    .upload-area:hover { background-color: #e2eaf3; border-color: #FF7518; }
    .upload-area i { font-size: 2.5rem; color: #0B4C8C; }
    .custom-file-input { display: none; }
    </style>
</head>
<body>
    
    <input type="hidden" id="id_usuario_actual_global" value="<?= $id_usuario ?>">
    <input type="hidden" id="rol_actual_global" value="<?= $rol_actual ?>">
    <input type="hidden" id="puede_marcar_corregido" value="<?= $puede_marcar_corregido ?>">

    <!-- ENCABEZADO MOVIL -->
    <div class="d-flex d-md-none mobile-header text-white p-3 justify-content-between align-items-center w-100 position-sticky top-0 shadow-sm" style="z-index: 1020; background-color: #0B4C8C !important;">
        <div class="d-flex align-items-center">
            <img src="../img/camisa.jpg" style="height:30px; object-fit:contain; border-radius: 4px;" class="me-2 bg-white p-1" alt="Logo">
            <h5 class="m-0 font-sugo">Unifuerte</h5>
        </div>
        <button class="btn btn-outline-light btn-sm" id="btnHamburguesa"><i class="bi bi-list fs-3"></i></button>
    </div>
    
    <div class="overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo d-flex align-items-center mb-4 mt-3 ps-3">
            <a href="#" id="btnToggleSidebarPc" class="text-decoration-none logo-icon-box me-3" title="Colapsar/Expandir Menu">
                <img src="../img/camisa.jpg" style="height: 35px; width: 35px; object-fit: contain; border-radius: 6px; background-color: #fff; padding: 3px;" alt="Logo">
            </a>
            <div class="nav-text">
                <h5 class="m-0 text-white font-sugo" style="font-size: 1.2rem;">Unifuerte</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Mejoras</small>
            </div>
        </div>
        
        <div class="sidebar-menu-scroll px-2">
            <div class="bg-primary bg-opacity-25 rounded p-3 mb-4 mx-2 user-box" style="background-color: rgba(255, 117, 24, 0.2) !important;">
                <div class="fw-bold text-white d-flex align-items-center justify-content-center justify-content-md-start">
                    <i class="bi bi-person-circle me-2 user-icon"></i>
                    <span class="nav-text"><?php echo htmlspecialchars($datos_usr['nombre']); ?></span>
                </div>
                <div class="nav-text text-center text-md-start mt-1">
                    <small class="text-white-50"><?= strtoupper(str_replace('_', ' ', $rol_actual)) ?></small>
                </div>
            </div>
            
            <a href="../portal_ventas/index.php" class="nav-link-custom d-flex align-items-center" title="Volver al Portal">
                <i class="bi bi-arrow-left-circle-fill me-2 text-warning"></i> <span class="nav-text">Volver al Portal</span>
            </a>

            <a href="index.php" class="nav-link-custom active d-flex align-items-center" title="Banco de Mejoras">
                <i class="bi bi-lightbulb-fill me-2"></i> <span class="nav-text">Banco de Mejoras</span>
            </a>
        </div>
        
        <div class="sidebar-footer">
            <a href="../login/logout.php" class="text-danger text-decoration-none d-flex align-items-center justify-content-center fw-bold" style="padding: 15px 20px;" title="Cerrar Sesion">
                <i class="bi bi-box-arrow-left me-2"></i> <span class="nav-text">Cerrar Sesion</span>
            </a>
        </div>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="main-content">
        
        <div class="header-ayuda">
            <div>
                <h2 class="fw-bold mb-2"><i class="bi bi-rocket-takeoff-fill text-warning"></i> Banco de Mejoras</h2>
                <p class="mb-0 fs-6" style="color: #d0d6e0;">Ayúdanos a evolucionar el sistema. Sugiere nuevas funciones o dinos qué podemos hacer mejor.</p>
            </div>
            <button class="btn-reportar shadow" onclick="abrirModalTicket()">
                <i class="bi bi-lightbulb-fill me-2"></i> Sugerir Nueva Mejora
            </button>
        </div>

        <div class="row g-4" id="contenedorTickets">
            <div class="col-12 text-center py-5 text-muted fw-bold">
                <div class="spinner-border text-primary" role="status"></div><br>Cargando banco de mejoras...
            </div>
        </div>
    </div>

    <!-- MODAL PARA CREAR/EDITAR SUGERENCIA -->
    <div class="modal fade" id="modalTicket" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header text-white" style="background-color: #0B4C8C; border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title fw-bold" id="tituloModalTicket"><i class="bi bi-lightbulb-fill text-warning"></i> Sugerir Nueva Mejora</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formTicket" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="guardar_ticket">
                    <input type="hidden" name="id_ticket" id="t_id_ticket" value="0">
                    
                    <div class="modal-body bg-white px-4">
                        
                        <div class="mb-4 mt-2">
                            <label class="fw-bold text-dark mb-1"><i class="bi bi-star-fill text-primary"></i> Título de la Mejora *</label>
                            <input type="text" name="titulo" id="t_titulo" class="form-control form-control-lg bg-light" placeholder="Ej: Agregar un botón para duplicar prendas" required style="border-radius: 10px; font-size: 1rem;">
                        </div>
                        
                        <div class="mb-4">
                            <label class="fw-bold text-dark mb-1"><i class="bi bi-text-paragraph text-primary"></i> Descripción Detallada *</label>
                            <textarea name="descripcion" id="t_descripcion" class="form-control bg-light" rows="5" placeholder="Explica detalladamente la mejora que sugieres, en qué módulo te gustaría verla, y por qué sería útil..." required style="border-radius: 10px; resize: none;"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold text-dark mb-2"><i class="bi bi-image text-primary"></i> Adjuntar Ejemplos (Fotos / Referencias)</label>
                            
                            <!-- Área de subida clickeable -->
                            <div class="upload-area" onclick="document.getElementById('t_fotos').click()">
                                <i class="bi bi-cloud-arrow-up-fill"></i>
                                <h6 class="fw-bold mt-2 text-dark">Toca aquí para subir imágenes de referencia</h6>
                                <p class="text-muted small m-0">Puedes elegir varias imágenes a la vez</p>
                            </div>
                            <!-- Input real oculto -->
                            <input type="file" name="fotos[]" id="t_fotos" class="custom-file-input" accept="image/*" multiple>
                            
                            <!-- Contenedor donde aparecen las miniaturas en vivo -->
                            <div id="fotos_nuevas_preview" class="d-flex flex-wrap gap-2 mt-3"></div>
                        </div>

                        <!-- Contenedor de fotos que ya estaban guardadas en la BD -->
                        <div id="fotos_existentes_container" class="mt-4 p-3 bg-light border rounded" style="display:none;">
                            <h6 class="fw-bold small text-muted border-bottom pb-2 m-0"><i class="bi bi-images"></i> Imágenes Guardadas (Toca la ❌ para eliminar)</h6>
                            <div id="t_fotos_preview" class="d-flex flex-wrap gap-2 pt-3"></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light" style="border-radius: 0 0 16px 16px;">
                        <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal" style="border-radius: 50px; padding: 8px 20px;">Cancelar</button>
                        <button type="submit" class="btn text-white fw-bold" style="background-color: #0B4C8C; border-radius: 50px; padding: 8px 20px;" id="btnGuardarTicket">
                            <i class="bi bi-send-check-fill me-1"></i> Enviar Sugerencia
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/ayuda.js?v=<?= time(); ?>"></script>
    
    <script>
    $(document).ready(function() {
        
        // LÓGICA INFALIBLE SOLICITADA PARA EL BOTÓN HAMBURGUESA
        $(document).off('click', '#btnHamburguesa').on('click', '#btnHamburguesa', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#sidebar').attr('style', 'display: block !important; left: 0px !important; position: fixed !important; z-index: 1050 !important; top: 0 !important; height: 100vh !important; width: 260px !important; background-color: #0b1a2e !important; transition: left 0.3s ease !important;');
            $('#sidebarOverlay').css({'display': 'block', 'opacity': '1', 'z-index': '1040', 'transition': 'opacity 0.3s ease'});
        });

        // Cerrar menú al tocar el fondo oscuro
        $(document).on('click', '#sidebarOverlay, .sidebar a', function() {
            if ($(window).width() < 768) {
                $('#sidebar').attr('style', 'display: block !important; left: -300px !important; position: fixed !important; z-index: 1050 !important; top: 0 !important; height: 100vh !important; width: 260px !important; background-color: #0b1a2e !important; transition: left 0.3s ease !important;');
                $('#sidebarOverlay').css({'opacity': '0'});
                setTimeout(() => { $('#sidebarOverlay').css('display', 'none'); }, 300);
            }
        });
        
        // Logica para colapsar en PC
        $('#btnToggleSidebarPc').on('click', function(e) {
            e.preventDefault();
            $('#sidebar').toggleClass('collapsed');
        });
    });
    </script>
    
    <script src="../auditoria/auditoria_espia.js?v=<?= time(); ?>"></script>
</body>
</html>