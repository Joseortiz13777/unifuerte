<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once '../conexion.php';

// ==============================================================================
// ENDPOINT FANTASMA: Sube los Anexos de la Licitacion sin tocar las APIs originales
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_fantasma_subir_anexos'])) {
    header('Content-Type: application/json');
    $id_cot = intval($_POST['id_cotizacion'] ?? 0);
    
    if ($id_cot > 0 && !empty($_FILES['anexos_licitacion']['name'][0])) {
        $dir_uploads = "../images/ordenes/";
        if (!is_dir($dir_uploads)) @mkdir($dir_uploads, 0777, true);

        $fotos_guardadas = [];
        foreach ($_FILES['anexos_licitacion']['name'] as $i => $name) {
            if ($_FILES['anexos_licitacion']['error'][$i] === 0) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $nuevo_nombre = "anexolic_" . time() . "_" . rand(1000,9999) . "." . $ext;
                if (move_uploaded_file($_FILES['anexos_licitacion']['tmp_name'][$i], $dir_uploads . $nuevo_nombre)) {
                    $fotos_guardadas[] = $nuevo_nombre;
                }
            }
        }

        if (!empty($fotos_guardadas)) {
            $nuevas_fotos = implode(',', $fotos_guardadas);
            $q = mysqli_query($conexion, "SELECT fotos_diseno FROM cotizacion WHERE ID = $id_cot");
            $row = mysqli_fetch_assoc($q);
            $existentes = $row['fotos_diseno'] ? trim($row['fotos_diseno']) : '';
            $final = $existentes ? $existentes . ',' . $nuevas_fotos : $nuevas_fotos;
            
            mysqli_query($conexion, "UPDATE cotizacion SET fotos_diseno = '$final' WHERE ID = $id_cot");
        }
    }
    echo json_encode(['success' => true]);
    exit();
}
// ==============================================================================

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol'])) {
    header("Location: ../login/index.php");
    exit();
}

mysqli_query($conexion, "ALTER TABLE usuario ADD COLUMN IF NOT EXISTS permisos VARCHAR(255) DEFAULT '1,4'");
mysqli_query($conexion, "ALTER TABLE cliente ADD COLUMN IF NOT EXISTS redes_sociales VARCHAR(255) DEFAULT NULL");
mysqli_query($conexion, "ALTER TABLE cliente ADD COLUMN IF NOT EXISTS veces_uniforma INT DEFAULT 1");
mysqli_query($conexion, "ALTER TABLE cliente ADD COLUMN IF NOT EXISTS cantidad_personas INT DEFAULT 0");

mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS pausado TINYINT(1) DEFAULT 0");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS enviado_logistica TINYINT(1) DEFAULT 0");
mysqli_query($conexion, "ALTER TABLE detalle_cotizacion ADD COLUMN IF NOT EXISTS especificaciones_tallas TEXT DEFAULT NULL");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS aplica_iva TINYINT(1) DEFAULT 1");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS porcentaje_descuento_manual DECIMAL(5,2) DEFAULT 0.00");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS descuento_logistica DECIMAL(10,2) DEFAULT 0.00");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS tipo_desc_logistica VARCHAR(10) DEFAULT 'monto'");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS flete DECIMAL(10,2) DEFAULT 0.00");

mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS comision_pagada TINYINT(1) DEFAULT 0");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS comision_porc DECIMAL(5,2) DEFAULT 0.00");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS comision_monto DECIMAL(10,2) DEFAULT 0.00");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS comision_fecha VARCHAR(50) DEFAULT ''");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS comision_tasa DECIMAL(10,2) DEFAULT 0.00");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS comision_banco_rec VARCHAR(100) DEFAULT ''");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS comision_banco_emi VARCHAR(100) DEFAULT ''");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS comision_ref VARCHAR(100) DEFAULT ''");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS comision_foto VARCHAR(255) DEFAULT ''");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS fotos_diseno TEXT DEFAULT NULL");
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS noti_wa INT DEFAULT 0");

// NUEVAS COLUMNAS PARA LA FICHA PILOTO DE CONFECCIÓN, CHECKLISTS Y EMPAQUE (BOLSAS / SACOS)
mysqli_query($conexion, "ALTER TABLE cotizacion ADD COLUMN IF NOT EXISTS ficha_confeccion_json LONGTEXT DEFAULT NULL");
mysqli_query($conexion, "ALTER TABLE orden_fabrica ADD COLUMN IF NOT EXISTS ficha_confeccion_json LONGTEXT DEFAULT NULL");

$id_usuario = intval($_SESSION['usuario_id']);
$q_usr = mysqli_query($conexion, "SELECT rol, permisos, nombre, apellido FROM usuario WHERE ID = $id_usuario");
$datos_usr = mysqli_fetch_assoc($q_usr);

$rol_actual = strtolower($datos_usr['rol']);
$permisos_arr = array_map('trim', explode(',', $datos_usr['permisos'] ?? ''));
$csrfPortalCliente = $_SESSION['portal_cliente_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['portal_cliente_csrf'] = $csrfPortalCliente;

function tieneVector($num) {
    global $permisos_arr;
    return in_array((string)$num, $permisos_arr);
}

$pendientes_insumos = 0;
$puede_ver_bandeja_insumos = tieneVector(38) ? 1 : 0;
// NUEVOS PRIVILEGIOS GLOBALES
$puede_omitir_60 = (tieneVector(49) || $rol_actual === 'master') ? 1 : 0;
$puede_editar_bloqueadas = (tieneVector(51) || $rol_actual === 'master') ? 'true' : 'false';

if ($puede_ver_bandeja_insumos === 1) {
    $q_ins = @mysqli_query($conexion, "SELECT COUNT(*) as cant FROM insumos_faltantes WHERE estado = 0");
    if ($q_ins) {
        $r_ins = mysqli_fetch_assoc($q_ins);
        $pendientes_insumos = intval($r_ins['cant']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_cliente_ajax'])) {
    header('Content-Type: application/json');
    if (!tieneVector(2)) { echo json_encode(['success'=>false, 'msg'=>'No tienes el Vector [2] para registrar/editar clientes.']); exit; }
    
    $id_cliente = intval($_POST['id_cliente'] ?? 0);
    $nombre = mysqli_real_escape_string($conexion, trim($_POST['nombre'] ?? ''));
    $cedula = mysqli_real_escape_string($conexion, trim($_POST['cedula_rif'] ?? ''));
    $tlf = mysqli_real_escape_string($conexion, trim($_POST['tlf'] ?? ''));
    $comprador = mysqli_real_escape_string($conexion, trim($_POST['comprador'] ?? ''));
    $email = mysqli_real_escape_string($conexion, trim($_POST['email'] ?? ''));
    $direccion = mysqli_real_escape_string($conexion, trim($_POST['direccion'] ?? ''));
    $redes = mysqli_real_escape_string($conexion, trim($_POST['redes_sociales'] ?? ''));
    $veces_uniforma = intval($_POST['veces_uniforma'] ?? 1);
    $cantidad_personas = intval($_POST['cantidad_personas'] ?? 0);

    if (!empty($nombre) && !empty($cedula)) {
        if ($id_cliente > 0) {
            $sql = "UPDATE cliente SET nombre='$nombre', cedula_Rif='$cedula', direccion='$direccion', comprador='$comprador', email='$email', tlf='$tlf', redes_sociales='$redes', veces_uniforma=$veces_uniforma, cantidad_personas=$cantidad_personas WHERE ID=$id_cliente";
            if(mysqli_query($conexion, $sql)) {
                mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usuario, 'EDITAR', 'cliente', $id_cliente, NOW(), 'Editó cliente: $nombre')");
                echo json_encode(['success' => true, 'modo' => 'editar', 'id' => $id_cliente, 'nombre' => $nombre, 'cedula' => $cedula, 'tlf' => $tlf, 'comprador' => $comprador, 'email' => $email, 'direccion' => $direccion, 'redes' => $redes, 'veces_uniforma' => $veces_uniforma, 'cantidad_personas' => $cantidad_personas]);
            } else echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
        } else {
            $sql = "INSERT INTO cliente (nombre, cedula_Rif, direccion, comprador, email, tlf, redes_sociales, veces_uniforma, cantidad_personas) VALUES ('$nombre', '$cedula', '$direccion', '$comprador', '$email', '$tlf', '$redes', $veces_uniforma, $cantidad_personas)";
            if(mysqli_query($conexion, $sql)) {
                $new_id = mysqli_insert_id($conexion);
                mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usuario, 'CREAR', 'cliente', $new_id, NOW(), 'Registró nuevo cliente: $nombre')");
                echo json_encode(['success' => true, 'modo' => 'crear', 'id' => $new_id, 'nombre' => $nombre, 'cedula' => $cedula, 'tlf' => $tlf, 'comprador' => $comprador, 'email' => $email, 'direccion' => $direccion, 'redes' => $redes, 'veces_uniforma' => $veces_uniforma, 'cantidad_personas' => $cantidad_personas]);
            } else echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
        }
    } else {
        echo json_encode(['success' => false, 'msg' => 'Nombre y Cédula obligatorios.']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_eliminar_cliente'])) {
    header('Content-Type: application/json');
    if (!tieneVector(3)) { echo json_encode(['success' => false, 'msg' => 'No tienes permiso para eliminar clientes (Vector 3).']); exit; }
    
    $id_elim = intval($_POST['id_cliente']);
    if(mysqli_query($conexion, "DELETE FROM cliente WHERE ID = $id_elim")) {
        mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usuario, 'ELIMINAR', 'cliente', $id_elim, NOW(), 'Eliminó cliente ID $id_elim')");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Error al eliminar. ' . mysqli_error($conexion)]);
    }
    exit();
}

$clientes_bd = [];
$res_cli = mysqli_query($conexion, "SELECT * FROM cliente ORDER BY nombre ASC");
if ($res_cli) while($row_cli = mysqli_fetch_assoc($res_cli)) $clientes_bd[] = $row_cli;

$metodos_pago_bd = [];
$res_mp = @mysqli_query($conexion, "SELECT id, forma_pago FROM cotizacion_formas_pago ORDER BY id ASC");
if ($res_mp) while($row_mp = mysqli_fetch_assoc($res_mp)) $metodos_pago_bd[] = $row_mp;

$lista_vendedores = [];
$lista_todos_usuarios = [];

if (tieneVector(7) || tieneVector(20)) {
    $res_vend = mysqli_query($conexion, "SELECT ID, nombre, apellido FROM usuario WHERE rol IN ('ventas', 'jefe_ventas') ORDER BY nombre ASC");
    if($res_vend) while($rv = mysqli_fetch_assoc($res_vend)) $lista_vendedores[] = $rv;
}

if (tieneVector(16) || tieneVector(23) || tieneVector(24) || tieneVector(25)) {
    $res_all = mysqli_query($conexion, "SELECT ID, nombre, apellido, rol FROM usuario ORDER BY nombre ASC");
    if($res_all) while($ru = mysqli_fetch_assoc($res_all)) $lista_todos_usuarios[] = $ru;
}

$telas_bd = [];
$q_telas = mysqli_query($conexion, "SELECT m.ID, m.nombre, m.color, IFNULL(s.nombre, 'Sin Categoría') as subcategoria 
                                    FROM materia_prima m 
                                    LEFT JOIN mp_subcategorias s ON m.id_mp_subcategoria = s.id 
                                    LEFT JOIN mp_categorias c ON s.id_mp_categoria = c.id 
                                    WHERE c.id = 1 
                                    OR s.nombre IN ('Camisería', 'Pantalón', 'Batas') 
                                    OR m.id_mp_subcategoria IS NULL 
                                    OR m.id_mp_subcategoria = 0 
                                    ORDER BY s.nombre ASC, m.nombre ASC");
if ($q_telas) while($r = mysqli_fetch_assoc($q_telas)) $telas_bd[] = $r;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unifuerte - Portal de Operaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos_portal.css?v=<?= time(); ?>">
    <link rel="icon" href="../img/camisa.jpg" type="image/jpeg">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- ESTILOS PARA EL SIDEBAR COLAPSABLE, BODY FLEXBOX Y VISTA PREVIA DE FICHA PILOTO -->
    <style>
    body {
        display: flex !important;
        flex-direction: column;
        min-height: 100vh;
        overflow-x: hidden;
        margin: 0;
        background-color: #f4f5f8;
    }
    
    .sidebar {
        position: fixed !important; 
        top: 0;
        left: -300px !important;
        transform: none !important;
        width: 260px;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        transition: left 0.3s ease !important;
        background-color: #0b1a2e !important;
        z-index: 1040;
    }
    
    .sidebar.active {
        left: 0 !important;
    }

    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0,0,0,0.5);
        z-index: 1030;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .overlay.active {
        display: block;
        opacity: 1;
    }
    
    .main-content {
        flex-grow: 1;
        min-width: 0; 
        height: calc(100vh - 60px);
        overflow-y: auto;
        margin-left: 0 !important; 
        transition: all 0.3s ease;
    }
    
    @media (min-width: 768px) {
        body {
            flex-direction: row;
            overflow: hidden;
        }

        .main-content {
            height: 100vh;
        }
        
        .sidebar { 
            position: relative !important;
            left: 0 !important;
            transform: none !important;
            flex-shrink: 0;
            width: 260px; 
            transition: width 0.3s ease !important;
        }
        
        .sidebar.collapsed { width: 75px !important; }
        
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .badge { display: none !important; }
        
        .sidebar.collapsed .nav-link-custom,
        .sidebar.collapsed .sidebar-menu-scroll > a {
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        
        .sidebar.collapsed .nav-link-custom i,
        .sidebar.collapsed .sidebar-menu-scroll > a i {
            margin-right: 0 !important;
            font-size: 1.4rem;
        }
        
        .sidebar.collapsed .user-box { padding: 10px !important; }
        .sidebar.collapsed .user-icon { margin-right: 0 !important; font-size: 1.5rem; }
        
        .sidebar.collapsed .sidebar-logo {
            justify-content: center;
            padding-left: 0 !important;
        }
        
        .sidebar.collapsed .sidebar-logo .logo-icon-box { margin-right: 0 !important; margin-left: 0 !important; }
        .sidebar.collapsed .sidebar-footer a { justify-content: center; }
        .sidebar.collapsed .sidebar-footer a i { margin-right: 0 !important; font-size: 1.5rem; }
    }

    /* ESTILOS DE LA VISTA PREVIA EN VIVO: ORDEN DE CONFECCIÓN (FICHA PILOTO) */
    .ficha-piloto-doc {
        background: #ffffff;
        color: #111827;
        font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
        font-size: 11px;
        line-height: 1.35;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 16px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.12);
        max-width: 820px;
        margin: 0 auto;
    }
    .ficha-piloto-doc .fp-header {
        border: 1.5px solid #1e293b;
        padding: 8px 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .ficha-piloto-doc .fp-badge-title {
        background: #0f2942;
        color: #fff;
        font-weight: 800;
        font-size: 13px;
        padding: 5px 18px;
        border-radius: 4px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        display: inline-block;
    }
    .ficha-piloto-doc .fp-subtitle-red {
        color: #b91c1c;
        font-weight: 800;
        font-size: 9.5px;
        letter-spacing: 0.3px;
        margin-top: 3px;
        text-transform: uppercase;
    }
    .ficha-piloto-doc .fp-box {
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 8px 10px;
        margin-bottom: 10px;
        background: #fff;
    }
    .ficha-piloto-doc .fp-sec-title {
        background: #e9eef5;
        border-left: 4px solid #0f2942;
        color: #0f2942;
        font-weight: 800;
        font-size: 10.5px;
        padding: 4px 8px;
        margin-bottom: 8px;
        text-transform: uppercase;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ficha-piloto-doc .fp-field-line {
        border-bottom: 1px dotted #94a3b8;
        padding: 2px 0;
        margin-bottom: 4px;
        display: flex;
        gap: 6px;
    }
    .ficha-piloto-doc .fp-field-line strong {
        color: #1e293b;
        white-space: nowrap;
    }
    .ficha-piloto-doc .fp-check-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 3px 8px;
        font-size: 10px;
    }
    .ficha-piloto-doc .fp-check-item {
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ficha-piloto-doc .fp-chk-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 12px;
        height: 12px;
        border: 1.3px solid #1e293b;
        font-size: 9px;
        font-weight: 900;
        line-height: 1;
        flex-shrink: 0;
    }
    .ficha-piloto-doc .fp-chk-box.checked {
        background: #0f2942;
        color: #fff;
    }
    .ficha-piloto-doc .fp-table-tallas {
        width: 100%;
        border-collapse: collapse;
        font-size: 10.5px;
    }
    .ficha-piloto-doc .fp-table-tallas th {
        background: #0f2942;
        color: #fff;
        padding: 4px 6px;
        text-align: center;
        border: 1px solid #0f2942;
        font-weight: 700;
    }
    .ficha-piloto-doc .fp-table-tallas td {
        padding: 4px 6px;
        text-align: center;
        border: 1px solid #cbd5e1;
        font-weight: 600;
    }
    .ficha-piloto-doc .fp-empaque-box {
        border: 1.5px solid #38bdf8;
        background: #f0f9ff;
        border-radius: 5px;
        padding: 8px 10px;
        margin-bottom: 10px;
    }
    .ficha-piloto-doc .fp-rotulo-preview {
        border: 1px dashed #0284c7;
        background: #ffffff;
        padding: 5px 10px;
        text-align: center;
        font-weight: 700;
        color: #0f172a;
        border-radius: 4px;
        margin-top: 6px;
    }
    .ficha-piloto-doc .fp-firmas-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-top: 8px;
    }
    .ficha-piloto-doc .fp-firma-card {
        border: 1px solid #cbd5e1;
        height: 52px;
        padding: 4px;
        text-align: center;
        font-size: 9.5px;
        color: #475569;
        font-weight: 600;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }
    </style>
    
    <datalist id="lista_telas_inteligente">
        <?php foreach($telas_bd as $t): ?>
            <option value="<?= htmlspecialchars($t['nombre'] . ' (' . $t['color'] . ')') ?>">Cat: <?= htmlspecialchars($t['subcategoria'] ?? 'General') ?></option>
        <?php endforeach; ?>
    </datalist>

    <datalist id="lista_bancos_receptores">
        <option value="Banco de Venezuela (Líder del mercado)">
        <option value="Banco del Tesoro">
        <option value="Banco Digital de los Trabajadores (Bicentenario)">
        <option value="Banco Agrícola de Venezuela">
        <option value="BANFANB">
        <option value="Bandes">
        <option value="Banco de Desarrollo de la Mujer">
        <option value="Banesco">
        <option value="Banco Mercantil">
        <option value="BBVA Provincial">
        <option value="Banco Nacional de Crédito (BNC)">
        <option value="Bancamiga">
        <option value="Banco Plaza">
        <option value="Banco Activo">
        <option value="Banco Caroní">
        <option value="Binance">
        <option value="Zelle">
        <option value="Banesco Panamá">
        <option value="Divisas Efectivo">
    </datalist>
</head>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const btnHamburguesa = document.getElementById("btnHamburguesa");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebarOverlay");

    if (btnHamburguesa && sidebar && overlay) {
        btnHamburguesa.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            sidebar.classList.add("active");
            overlay.classList.add("active");
        });

        overlay.addEventListener("click", function(e) {
            e.preventDefault();
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
        });

        const enlacesMenu = document.querySelectorAll(".sidebar a");
        enlacesMenu.forEach(enlace => {
            enlace.addEventListener("click", function() {
                if (window.innerWidth < 768) {
                    sidebar.classList.remove("active");
                    overlay.classList.remove("active");
                }
            });
        });
    }
});
</script>
<body>
    
    <!-- VARIABLES GLOBALES OCULTAS PARA JS -->
    <input type="hidden" id="mis_vectores" value="<?= $datos_usr['permisos'] ?>">
    <input type="hidden" id="csrf_portal_cliente" value="<?= htmlspecialchars($csrfPortalCliente, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" id="permiso_omitir_60" value="<?= $puede_omitir_60 ?>">
    <input type="hidden" id="usuario_es_master" value="<?= $rol_actual === 'master' ? 1 : 0 ?>">
    <input type="hidden" id="permiso_editar_bloq" value="<?= $puede_editar_bloqueadas ?>">
    <input type="hidden" id="nombre_asesor_actual_global" value="<?= htmlspecialchars(trim(($datos_usr['nombre'] ?? '') . ' ' . ($datos_usr['apellido'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
    
    <!-- NUEVAS VARIABLES PARA RESTRICCIÓN DE ORDENES -->
    <input type="hidden" id="id_usuario_actual_global" value="<?= $id_usuario ?>">
    <input type="hidden" id="permiso_ver_todas_ordenes" value="<?= (tieneVector(54) || $rol_actual === 'master') ? 1 : 0 ?>">

    <!-- ENCABEZADO MOVIL -->
    <div class="d-flex d-md-none mobile-header text-white p-3 justify-content-between align-items-center w-100 position-sticky top-0 shadow-sm" style="z-index: 1020; background-color: #0B4C8C !important;">
        <div class="d-flex align-items-center">
            <img src="../img/camisa.jpg" style="height:30px; object-fit:contain; border-radius: 4px;" class="me-2 bg-white p-1" alt="Logo">
            <h5 class="m-0 font-sugo">Unifuerte</h5>
        </div>
        <button type="button" class="btn btn-outline-light btn-sm" onclick="event.stopPropagation(); document.getElementById('sidebar').classList.toggle('active'); document.getElementById('sidebarOverlay').classList.toggle('active');">
            <i class="bi bi-list fs-3"></i>
        </button>
    </div>
    <div class="overlay" id="sidebarOverlay" onclick="document.getElementById('sidebar').classList.remove('active'); this.classList.remove('active');"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo d-flex align-items-center mb-4 mt-3 ps-3">
            <a href="#" id="btnToggleSidebarPc" class="text-decoration-none logo-icon-box me-3" title="Colapsar/Expandir Menu">
                <img src="../img/camisa.jpg" style="height: 35px; width: 35px; object-fit: contain; border-radius: 6px; background-color: #fff; padding: 3px;" alt="Logo">
            </a>
            <div class="nav-text">
                <h5 class="m-0 text-white font-sugo" style="font-size: 1.2rem;">Unifuerte</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Operaciones</small>
            </div>
        </div>
        
        <div class="sidebar-menu-scroll px-2">
            <div class="bg-primary bg-opacity-25 rounded p-3 mb-4 mx-2 user-box" style="background-color: rgba(255, 117, 24, 0.2) !important;" title="Tu Perfil Actual">
                <div class="fw-bold text-white d-flex align-items-center justify-content-center justify-content-md-start">
                    <i class="bi bi-person-circle me-2 user-icon"></i>
                    <span class="nav-text"><?php echo htmlspecialchars($datos_usr['nombre']); ?></span>
                </div>
                <div class="nav-text text-center text-md-start mt-1">
                    <small class="text-white-50"><?= strtoupper(str_replace('_', ' ', $rol_actual)) ?></small>
                </div>
            </div>
            
            <?php if(tieneVector(1)): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuClientes" title="Directorio de Clientes"><i class="bi bi-people-fill me-2"></i> <span class="nav-text">Directorio de Clientes</span></a>
            <?php endif; ?>

            <?php if(in_array($rol_actual, ['master', 'ventas', 'jefe_ventas'], true)): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuClientesCotizar" title="Clientes a Cotizar"><i class="bi bi-person-lines-fill me-2"></i> <span class="nav-text">Clientes a Cotizar</span></a>
            <?php endif; ?>
            
            <?php if(tieneVector(4)): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuCotizador" title="Cotizador Catalogo"><i class="bi bi-calculator me-2"></i> <span class="nav-text">Cotizador Catalogo</span></a>
            <?php endif; ?>

            <?php if(tieneVector(26)): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuCotizadorLicitaciones" title="Cotizador Especial"><i class="bi bi-ui-checks-grid me-2"></i> <span class="nav-text">Cotizador Especial</span></a>
            <?php endif; ?>
            
            <?php if(tieneVector(52)): ?>
                <a class="nav-link-custom text-info d-flex align-items-center" id="btnMenuMuestras" title="Muestras Fisicas"><i class="bi bi-box2-heart me-2"></i> <span class="nav-text">Muestras Fisicas</span></a>
            <?php endif; ?>

            <?php if(tieneVector(6) || tieneVector(7)): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuCotizaciones" title="Historial Cotizaciones">
                    <i class="bi bi-file-earmark-text me-2"></i> <span class="nav-text">Historial Cotizaciones</span> 
                    <span class="badge bg-danger rounded-pill ms-1 pulse-wa" id="puntoRojoVentas" style="display:none; font-size: 0.6rem; vertical-align: top;">!</span>
                </a>
            <?php endif; ?>

            <?php if($rol_actual === 'master'): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuCotizacionesEliminadas" title="Archivador de Cotizaciones">
                    <i class="bi bi-archive-fill me-2"></i> <span class="nav-text">Cotizaciones Eliminadas</span>
                </a>
            <?php endif; ?>

            <?php if(tieneVector(19) || tieneVector(20)): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuDashboard" title="<?= tieneVector(20) ? 'Panel Gerencial' : 'Mi Desempeno' ?>"><i class="bi bi-bar-chart-fill me-2"></i> <span class="nav-text"><?= tieneVector(20) ? 'Panel Gerencial' : 'Mi Desempeno' ?></span></a>
            <?php endif; ?>
            
            <?php if(tieneVector(10)): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuOrdenes" title="Ordenes de Fabrica"><i class="bi bi-clipboard2-pulse me-2"></i> <span class="nav-text">Ordenes de Fabrica</span></a>
            <?php endif; ?>

            <?php if(tieneVector(55) || tieneVector(56) || in_array($rol_actual, ['jefe_diseno', 'disenador'], true)): ?>
                <a class="nav-link-custom d-flex align-items-center" href="../envios/index.php?vista=diseno" title="Módulo de Diseño"><i class="bi bi-palette-fill me-2"></i> <span class="nav-text">Módulo de Diseño</span></a>
            <?php endif; ?>

            <?php if(tieneVector(14)): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuGestion" title="Gestion de Catalogo"><i class="bi bi-boxes me-2"></i> <span class="nav-text">Gestion de Catalogo</span></a>
            <?php endif; ?>

            <?php if(tieneVector(15)): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuUsuarios" title="Gestion de Usuarios"><i class="bi bi-shield-lock-fill me-2"></i> <span class="nav-text">Gestion de Usuarios</span></a>
            <?php endif; ?>

            <?php if(tieneVector(16) || tieneVector(23) || tieneVector(24) || tieneVector(25)): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuAuditoria" title="Auditoria (Historial)"><i class="bi bi-eye-fill me-2"></i> <span class="nav-text">Auditoria (Historial)</span></a>
            <?php endif; ?>

            <?php if(tieneVector(17) || tieneVector(18)): ?>
                <a class="nav-link-custom d-flex align-items-center" id="btnMenuLicitaciones" title="Portafolios (PDF)"><i class="bi bi-briefcase-fill me-2"></i> <span class="nav-text">Portafolios (PDF)</span></a>
            <?php endif; ?>

            <?php if(tieneVector(13)): ?>
                <a class="nav-link-custom text-warning mt-3 border-top pt-3 border-secondary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalNotifLogistica" title="Alertas Produccion">
                    <i class="bi bi-box-seam me-2"></i> <span class="nav-text">Alertas Produccion</span>
                    <span class="badge bg-danger rounded-pill ms-2" id="badge_pendientes_menu" style="display:none;">0</span>
                </a>
            <?php endif; ?>

            <?php if(tieneVector(16) || tieneVector(22) || tieneVector(23) || tieneVector(24) || tieneVector(25)): ?>
                <a class="nav-link-custom text-info d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalNotificacionesAuditoria" title="Alertas / Notificaciones">
                    <i class="bi bi-bell-fill me-2"></i> <span class="nav-text">Alertas / Notificaciones</span>
                    <span class="badge bg-danger rounded-pill ms-2" id="puntoRojoNotifMenu" style="display:none;">!</span>
                </a>
            <?php endif; ?>

            <?php if(tieneVector(21) || tieneVector(27)): ?>
                <a href="../crud/inventario/index.php" class="text-success mt-2 border-top pt-3 border-secondary d-flex align-items-center fw-bold" style="padding: 12px 20px; text-decoration: none; transition: 0.2s; margin-bottom: 5px;" onmouseover="this.style.color='#ffffff'; this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.color='#198754'; this.style.backgroundColor='transparent'" title="Modulo Inventario / Recetas">
                    <i class="bi bi-box-seam me-2"></i> <span class="nav-text">Modulo Inventario / Recetas</span>
                </a>
            <?php endif; ?>

            <?php if(tieneVector(13) || tieneVector(36) || tieneVector(38)): ?>
                <a href="../envios/index.php" class="text-info mt-2 border-top pt-3 border-secondary d-flex align-items-center fw-bold position-relative" style="padding: 12px 20px; text-decoration: none; transition: 0.2s; margin-bottom: 5px;" onmouseover="this.style.color='#ffffff'; this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.color='#0dcaf0'; this.style.backgroundColor='transparent'" title="Gestion de Envios">
                    <i class="bi bi-truck me-2"></i> <span class="nav-text">Gestion de Envios</span>
                    <?php if($puede_ver_bandeja_insumos === 1 && $pendientes_insumos > 0): ?>
                        <span class="badge bg-danger rounded-pill position-absolute badge-ruidoso" style="right: 15px; top: 12px;">
                            <i class="bi bi-exclamation-triangle-fill"></i> <span class="nav-text">Insumos (<?= $pendientes_insumos ?>)</span>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <?php if(tieneVector(39) || tieneVector(40) || tieneVector(41) || tieneVector(42)): ?>
                <a href="../Nomina/index.php" class="mt-2 border-top pt-3 border-secondary d-flex align-items-center fw-bold position-relative" style="padding: 12px 20px; text-decoration: none; transition: 0.2s; margin-bottom: 5px; color: #a855f7 !important;" onmouseover="this.style.color='#ffffff !important'; this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.color='#a855f7 !important'; this.style.backgroundColor='transparent'" title="Gestion de Nomina">
                    <i class="bi bi-cash-stack me-2"></i> <span class="nav-text">Gestion de Nomina</span>
                </a>
            <?php endif; ?>

            <?php if(tieneVector(45)): ?>
                <a href="../administracion/index.php" class="text-info mt-2 border-top pt-3 border-secondary d-flex align-items-center fw-bold position-relative" style="padding: 12px 20px; text-decoration: none; transition: 0.2s; margin-bottom: 5px;" onmouseover="this.style.color='#ffffff'; this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.color='#0dcaf0'; this.style.backgroundColor='transparent'" title="Administracion">
                    <i class="bi bi-building text-info me-2"></i> <span class="nav-text">Administracion</span>
                </a>
            <?php endif; ?>

            <?php if(tieneVector(46)): ?>
                <a href="../inventarioPrenda/index.php" class="text-success mt-2 border-top pt-3 border-secondary d-flex align-items-center fw-bold position-relative" style="padding: 12px 20px; text-decoration: none; transition: 0.2s; margin-bottom: 5px;" onmouseover="this.style.color='#ffffff'; this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.color='#198754'; this.style.backgroundColor='transparent'" title="Inventario Prendas">
                    <i class="bi bi-tags text-success me-2"></i> <span class="nav-text">Inventario Prendas</span>
                </a>
            <?php endif; ?>

            <?php if(tieneVector(47)): ?>
                <a href="../tareas/index.php" class="mt-2 border-top pt-3 border-secondary d-flex align-items-center fw-bold position-relative" style="padding: 12px 20px; text-decoration: none; transition: 0.2s; margin-bottom: 5px; color: #f59e0b !important;" onmouseover="this.style.color='#ffffff !important'; this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.color='#f59e0b !important'; this.style.backgroundColor='transparent'" title="Gestion de Tareas">
                    <i class="bi bi-list-task me-2"></i> <span class="nav-text">Gestion de Tareas</span>
                </a>
            <?php endif; ?>

            <a href="../ayuda/index.php" class="mt-2 border-top pt-3 border-secondary d-flex align-items-center fw-bold position-relative" style="padding: 12px 20px; text-decoration: none; transition: 0.2s; margin-bottom: 5px; color: #0dcaf0 !important;" onmouseover="this.style.color='#ffffff !important'; this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.color='#0dcaf0 !important'; this.style.backgroundColor='transparent'" title="Centro de Ayuda">
                <i class="bi bi-life-preserver me-2"></i> <span class="nav-text">Centro de Ayuda</span>
            </a>
        </div>
        
        <div class="sidebar-footer">
            <a href="../login/logout.php" class="text-danger text-decoration-none d-flex align-items-center justify-content-center fw-bold" style="padding: 15px 20px;" title="Cerrar Sesion">
                <i class="bi bi-box-arrow-left me-2"></i> <span class="nav-text">Cerrar Sesion</span>
            </a>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODALES GLOBALES -->
    <!-- ============================================== -->

    <!-- Modal Notificaciones Logistica -->
    <div class="modal fade" id="modalNotifLogistica" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #FF7518;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-circle"></i> Pendientes por Orden</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="list-group list-group-flush" id="listaCotizacionesProduccion">
                        <li class="list-group-item text-muted text-center py-3">Cargando...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Centro de Alertas/Auditoria -->
    <div class="modal fade" id="modalNotificacionesAuditoria" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #0B4C8C;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-bell"></i> Centro de Alertas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="bg-light p-2 border-bottom">
                    <select id="filtro_departamento_notif" class="form-select form-select-sm fw-bold" style="color: #0B4C8C;" onchange="cargarNotificacionesAuditoria()">
                        <option value="todos">Todos los departamentos permitidos</option>
                        <option value="ventas">Solo Ventas y Cotizaciones</option>
                        <option value="licitaciones">Solo Licitaciones</option>
                        <option value="logistica">Solo Produccion/Logistica</option>
                        <option value="admin">Solo Administracion</option>
                    </select>
                </div>
                <div class="modal-body p-0">
                    <ul class="list-group list-group-flush" id="contenedorNotificaciones">
                        <li class="list-group-item text-muted text-center py-3">Cargando...</li>
                    </ul>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="#" class="text-decoration-none fw-bold text-primary" onclick="$('#modalNotificacionesAuditoria').modal('hide'); $('#btnMenuAuditoria').click();">Ver todo el historial general</a>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL REGLA AZUL: Especificar Tallas, Telas, Checklists de Confección, Empaque (Bolsas/Sacos) y Vista Previa en Vivo -->
    <div class="modal fade" id="modalEspecificarDetalles" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <form id="formGuardarEspecificaciones" enctype="multipart/form-data" class="modal-content border-0 shadow">
                <input type="hidden" id="id_cotizacion_especificar" value="">
                
                <div class="modal-header text-white py-2 px-3 flex-shrink-0 d-flex justify-content-between align-items-center" style="background-color: #0B4C8C;">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="modal-title fw-bold m-0"><i class="bi bi-rulers"></i> Especificar Tallas, Telas y Ficha Piloto de Confección</h5>
                        <span class="badge bg-warning text-dark ms-2 d-none d-md-inline-block" id="badgeNroCotSpec"></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm d-none d-lg-inline-flex" role="group">
                            <button type="button" class="btn btn-light fw-bold active" id="btnModoDivididoSpec" onclick="window.cambiarModoVistaSpec('dividido')"><i class="bi bi-layout-split"></i> Pantalla Dividida</button>
                            <button type="button" class="btn btn-outline-light fw-bold" id="btnModoFormularioSpec" onclick="window.cambiarModoVistaSpec('formulario')"><i class="bi bi-ui-checks"></i> Solo Formulario</button>
                            <button type="button" class="btn btn-outline-light fw-bold" id="btnModoPreviewSpec" onclick="window.cambiarModoVistaSpec('preview')"><i class="bi bi-eye"></i> Solo Vista Previa</button>
                        </div>
                        <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body p-0 bg-light overflow-hidden">
                    <div class="row g-0 h-100">
                        <!-- COLUMNA IZQUIERDA: FORMULARIO DE TALLAS, PERSONAS, FOTOS Y CHECKLISTS -->
                        <div class="col-12 col-lg-7 h-100 overflow-auto p-3" id="colFormularioSpecs">
                            <div class="p-3 bg-white border rounded shadow-sm mb-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                    <label class="small fw-bold text-primary m-0"><i class="bi bi-images"></i> Imágenes de Referencia / Diseño (Sube todas las fotos necesarias; elige cuáles van en Vista Frente y Vista Espalda)</label>
                                    <span class="badge bg-info text-dark">Soporta múltiples imágenes y PDF</span>
                                </div>
                                <input type="file" name="fotos_diseno[]" id="fotos_diseno_specs" class="form-control form-control-sm border-primary" accept="image/*,application/pdf" multiple>
                                <div id="fotos_diseno_previas_regla" class="mt-2 d-flex flex-wrap gap-2"></div>
                                <div id="selectores_frente_espalda_specs" class="row g-2 mt-2 pt-2 border-top" style="display:none;">
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted mb-1"><i class="bi bi-person-bounding-box text-primary"></i> Foto para VISTA FRENTE (Ficha Piloto):</label>
                                        <select id="fp_foto_frente" class="form-select form-select-sm border-primary fw-bold" onchange="window.actualizarVistaPreviaFichaConfeccion()">
                                            <option value="">Automática (Primera imagen)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted mb-1"><i class="bi bi-person-badge text-primary"></i> Foto para VISTA ESPALDA (Ficha Piloto):</label>
                                        <select id="fp_foto_espalda" class="form-select form-select-sm border-primary fw-bold" onchange="window.actualizarVistaPreviaFichaConfeccion()">
                                            <option value="">Automática (Segunda imagen)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div id="cuerpoEspecificaciones"></div>
                        </div>

                        <!-- COLUMNA DERECHA: VISTA PREVIA EN TIEMPO REAL DE LA ORDEN DE CONFECCIÓN -->
                        <div class="col-12 col-lg-5 h-100 overflow-auto p-3 border-start" id="colPreviewSpecs" style="background-color: #e2e8f0;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark small"><i class="bi bi-eye-fill text-primary"></i> VISTA PREVIA EN TIEMPO REAL — ORDEN DE CONFECCIÓN (FICHA PILOTO)</span>
                                <span class="badge bg-dark">Actualización Automática</span>
                            </div>
                            <div id="contenedorVistaPreviaFicha"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-white py-2 px-3 flex-shrink-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn text-white fw-bold px-4 shadow-sm" style="background-color: #0B4C8C;" id="btnGuardarSpecs"><i class="bi bi-floppy-fill"></i> Guardar Tallas, Telas y Ficha de Confección</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL PAGO DE COMISION -->
    <div class="modal fade" id="modalComision" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background-color: #FFD700;">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-coin"></i> Pago de Comision</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formPagoComision" enctype="multipart/form-data">
                    <div class="modal-body bg-light text-dark">
                        <input type="hidden" name="accion" value="pagar_comision">
                        <input type="hidden" name="id_cotizacion" id="com_id_cotizacion">
                        
                        <div class="row g-2 mb-2 text-center border-bottom pb-2">
                            <div class="col-6 border-end">
                                <span class="small fw-bold text-muted">Nro. Cotizacion:</span><br>
                                <span id="com_lbl_nro_cot" class="fw-bold text-dark fs-6">N/A</span>
                            </div>
                            <div class="col-6">
                                <span class="small fw-bold text-muted">Cliente:</span><br>
                                <span id="com_lbl_cliente" class="fw-bold text-primary fs-6">N/A</span>
                            </div>
                        </div>

                        <div class="alert alert-warning py-2 small fw-bold text-center mb-3">
                            Base Imponible del Pedido: <br><span id="com_lbl_base_imponible" class="text-danger fs-5">0.00 $</span>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="small fw-bold text-muted">Comision (%)</label>
                                <input type="number" step="0.01" name="comision_porc" id="com_porc" class="form-control" required>
                            </div>
                            <div class="col-4">
                                <label class="small fw-bold text-muted">Comisión generada ($)</label>
                                <input type="text" id="com_monto_calculado" name="comision_monto" class="form-control bg-white fw-bold text-primary" readonly required>
                            </div>
                            <div class="col-4">
                                <label class="small fw-bold text-muted">Equiv. (Bs)</label>
                                <input type="text" id="com_monto_bs" class="form-control bg-light fw-bold text-success" readonly placeholder="0.00 Bs">
                            </div>
                        </div>

                        <div id="com_resumen_prestamo" class="alert alert-info py-2 small mb-3" data-deuda="0" data-vinculo="pendiente">
                            <div class="d-flex justify-content-between"><span>Saldo pendiente del préstamo:</span><strong id="com_deuda_pendiente">$0.00</strong></div>
                            <div class="d-flex justify-content-between"><span>Descuento aplicado a la deuda:</span><strong id="com_descuento_prestamo" class="text-danger">$0.00</strong></div>
                            <div class="d-flex justify-content-between border-top mt-1 pt-1"><span>Neto a pagar al vendedor:</span><strong id="com_neto_pagar" class="text-success">$0.00</strong></div>
                            <div id="com_aviso_vinculo" class="mt-1"></div>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="small fw-bold text-muted">Fecha de Pago</label>
                                <input type="date" name="comision_fecha" id="com_fecha" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="small fw-bold text-muted">Tasa BCV</label>
                                <input type="number" step="0.01" name="comision_tasa" id="com_tasa" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="small fw-bold text-muted">Banco Emisor</label>
                                <select name="comision_banco_emi" id="com_banco_emi" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Provincial">Provincial</option>
                                    <option value="Banesco">Banesco</option>
                                    <option value="Mercantil">Mercantil</option>
                                    <option value="Venezuela">Venezuela</option>
                                    <option value="Nacional de Credito">Nacional de Credito</option>
                                    <option value="Bancamiga">Bancamiga</option>
                                    <option value="Binance">Binance</option>
                                    <option value="Zelle">Zelle</option>
                                    <option value="Banesco Panama">Banesco Panama</option>
                                    <option value="Divisas Efectivo">Divisas Efectivo</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="small fw-bold text-muted">Banco Receptor</label>
                                <input type="text" name="comision_banco_rec" id="com_banco_rec" class="form-control" list="lista_bancos_receptores" placeholder="Buscar banco..." autocomplete="off" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Numero de Referencia</label>
                            <input type="text" name="comision_ref" id="com_ref" class="form-control" required>
                        </div>
                        
                        <div class="mb-3" id="div_com_foto_input">
                            <label class="small fw-bold text-muted">Adjuntar Comprobante</label>
                            <input type="file" name="comision_foto" id="com_foto" class="form-control" accept="image/*,application/pdf">
                        </div>
                        <div id="div_com_foto_ver" class="mb-3 text-center" style="display:none;">
                            <a href="#" target="_blank" id="link_com_foto" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-file-earmark-image"></i> Ver Comprobante Guardado</a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-warning fw-bold text-dark" id="btnGuardarComision">Registrar Comision</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL REGISTRO DE PAGO (CON CAMPO MONTO BS) -->
    <div class="modal fade" id="modalPagoCotizacion" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: #0B4C8C;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin"></i> Registrar Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formPagoCotizacion" enctype="multipart/form-data">
                    <div class="modal-body bg-light">
                        <input type="hidden" name="accion" id="pago_accion_form" value="cambiar_estado">
                        <input type="hidden" name="edit_pago_index" id="pago_edit_index" value="-1">
                        <input type="hidden" name="id" id="pago_id_cotizacion">
                        <input type="hidden" name="estado" id="pago_estado_cotizacion">
                        <input type="hidden" id="pago_total_cotizacion">
                        <input type="hidden" id="pago_subtotal_cotizacion"> 
                        <input type="hidden" id="pago_tasa_factura">
                        <input type="hidden" id="pago_retencion_base_iva_usd">
                        <input type="hidden" id="pago_retencion_base_imponible_usd">
                        
                        <div class="row g-2 mb-2 text-center border-bottom pb-2">
                            <div class="col-6 border-end">
                                <span class="small fw-bold text-muted">Nro. Cotizacion:</span><br>
                                <span id="pago_lbl_nro_cot" class="fw-bold text-dark fs-6">N/A</span>
                            </div>
                            <div class="col-6">
                                <span class="small fw-bold text-muted">Cliente:</span><br>
                                <span id="pago_lbl_cliente" class="fw-bold text-primary fs-6">N/A</span>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 small fw-bold text-center mb-2">
                            Estas marcando esta cotizacion como: <br><span id="pago_lbl_estado" class="text-uppercase text-danger fs-5"></span>
                        </div>

                        <div id="alerta_pago_inicial" class="alert alert-warning py-2 small fw-bold text-center mb-3" style="display:none; line-height: 1.5;">
                            Para desbloquear el pedido debe acumular el 60% del Subtotal.<br>
                            60% Requerido: <span id="pago_minimo_req" class="text-primary fs-6">0.00 $</span><br>
                            Abonado: <span id="pago_abonado_actual" class="text-success fs-6">0.00 $</span><br>
                            Falta para desbloquear: <span id="pago_falta_req" class="text-danger fs-5">0.00 $</span>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="small fw-bold text-muted">Fecha de Pago</label>
                                <input type="date" name="pago_fecha" id="pago_fecha" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="small fw-bold text-muted">Banco Receptor</label>
                                <select name="pago_banco" id="pago_banco" class="form-select">
                                    <option value="">Seleccione...</option>
                                    <option value="Provincial">Provincial</option>
                                    <option value="Banesco">Banesco</option>
                                    <option value="Mercantil">Mercantil</option>
                                    <option value="Venezuela">Venezuela</option>
                                    <option value="Nacional de Credito">Nacional de Credito</option>
                                    <option value="Bancamiga">Bancamiga</option>
                                    <option value="Binance">Binance</option>
                                    <option value="Zelle">Zelle</option>
                                    <option value="Banesco Panama">Banesco Panama</option>
                                    <option value="Divisas Efectivo">Divisas Efectivo</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="small fw-bold text-muted">Tasa BCV</label>
                                <input type="number" step="0.01" name="pago_tasa_bcv" id="pago_tasa_bcv" class="form-control" placeholder="Ej: 36.50">
                            </div>
                            <div class="col-4">
                                <label class="small fw-bold text-muted">Monto en Bs</label>
                                <input type="number" step="0.01" id="pago_valor_bs" class="form-control text-success fw-bold" placeholder="Ej: 1000">
                            </div>
                            <div class="col-4">
                                <label class="small fw-bold text-muted">Valor en USD</label>
                                <input type="number" min="0" step="0.01" name="pago_valor_usd" id="pago_valor_usd" class="form-control fw-bold text-primary" placeholder="Ej: 150.00 (puede ser 0 si paga con retenciones)">
                            </div>
                        </div>

                        <?php if ($rol_actual === 'master'): ?>
                        <div class="border rounded bg-white p-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="pago_con_retenciones" value="1" id="pago_con_retenciones">
                                <label class="form-check-label fw-bold text-primary" for="pago_con_retenciones">
                                    Este pago incluye retenciones fiscales
                                </label>
                            </div>
                            <div id="pago_retenciones_panel" class="row g-2 mt-1" style="display:none;">
                                <div class="col-4">
                                    <label class="small fw-bold text-muted">Ret. IVA (%)</label>
                                    <input type="number" min="0" max="100" step="0.01" name="retencion_iva_pct" id="retencion_iva_pct" class="form-control" value="0">
                                </div>
                                <div class="col-4">
                                    <label class="small fw-bold text-muted">Ret. ISLR (%)</label>
                                    <input type="number" min="0" max="100" step="0.01" name="retencion_islr_pct" id="retencion_islr_pct" class="form-control" value="0">
                                </div>
                                <div class="col-4">
                                    <label class="small fw-bold text-muted">Ret. municipal (%)</label>
                                    <input type="number" min="0" max="100" step="0.01" name="retencion_municipal_pct" id="retencion_municipal_pct" class="form-control" value="0">
                                </div>
                                <div class="col-4">
                                    <label class="small fw-bold text-muted">Ret. IVA (Bs)</label>
                                    <input type="number" min="0" step="0.01" name="retencion_iva_bs" id="retencion_iva_bs" class="form-control" value="0" readonly>
                                </div>
                                <div class="col-4">
                                    <label class="small fw-bold text-muted">Ret. ISLR (Bs)</label>
                                    <input type="number" min="0" step="0.01" name="retencion_islr_bs" id="retencion_islr_bs" class="form-control" value="0" readonly>
                                </div>
                                <div class="col-4">
                                    <label class="small fw-bold text-muted">Ret. municipal (Bs)</label>
                                    <input type="number" min="0" step="0.01" name="retencion_municipal_bs" id="retencion_municipal_bs" class="form-control" value="0" readonly>
                                </div>
                                <div class="col-12 small text-muted">
                                    Indica el porcentaje manualmente. IVA se calcula sobre el IVA facturado; ISLR y municipal sobre la base imponible. Los montos en Bs se calculan automáticamente con la tasa BCV.
                                    <div id="pago_retenciones_equivalente" class="fw-bold text-primary mt-1"></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Numero de Referencia(s)</label>
                            <input type="text" name="referencia" id="pago_referencia" class="form-control" placeholder="Ej: Ref 123456 - Zelle JuanPerez" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Imagen Pago Comprobante (Opcional)</label>
                            <input type="file" name="fotos_pago[]" id="pago_fotos" class="form-control" accept="image/*,application/pdf" multiple>
                            <small class="text-muted">Puedes seleccionar varias fotos a la vez.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold" id="btnGuardarPago">Guardar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL VER DETALLE COTIZACIÓN -->
    <div class="modal fade" id="modalVerDetalleCotizacion" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: #0B4C8C;"><h5 class="modal-title fw-bold"><i class="bi bi-list-check"></i> Detalle de la Cotizacion</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body bg-light">
                    <div class="table-responsive">
                        <table class="table table-hover bg-white rounded shadow-sm">
                            <thead class="table-light"><tr><th>Prenda / Arte</th><th>Posicion / Detalle</th><th class="text-center">Cant.</th><th class="text-end">Precio Unit.</th><th class="text-end">Subtotal</th></tr></thead>
                            <tbody id="detallesCotizacionTablaBody"></tbody>
                        </table>
                    </div>
                    <div class="d-flex flex-column align-items-end mt-3 border-top pt-2">
                        <div class="text-muted small">Subtotal: <span id="det_modal_subtotal" class="fw-bold text-dark"></span></div>
                        <div class="text-danger small">Descuento: <span id="det_modal_descuento" class="fw-bold"></span></div>
                        <div class="text-muted small">IVA (16%): <span id="det_modal_iva" class="fw-bold text-dark"></span></div>
                        <h4 class="fw-bold mt-2" style="color: #0B4C8C;">Total: <span id="det_modal_total"></span></h4>
                    </div>
                    
                    <div id="box_comprobantes_vista" class="mt-4 border-top pt-3" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark m-0"><i class="bi bi-receipt"></i> Historial de Pagos del Cliente</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white shadow-sm align-middle text-center" style="font-size: 0.8rem;">
                                <thead class="table-light"><tr><th>Fecha</th><th>Tasa BCV</th><th>Monto USD</th><th>Banco</th><th>Referencia</th><th>Comprobantes</th></tr></thead>
                                <tbody id="tbody_pagos_multiples"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE COBRANZA (WHATSAPP) -->
    <div class="modal fade" id="modalCobranza" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: #0B4C8C;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-whatsapp"></i> Gestion de Cobranza</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fw-bold">Total del Pedido:</span>
                        <span id="cobranza_lbl_total" class="fw-bold text-dark">0.00 $</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted fw-bold">Abonado hasta ahora:</span>
                        <span id="cobranza_lbl_abonado" class="fw-bold text-success">0.00 $</span>
                    </div>
                    <div class="alert alert-warning py-2 small fw-bold text-center mb-3">
                        Saldo Pendiente a Cobrar: <br><span id="cobranza_lbl_restante" class="text-danger fs-3">0.00 $</span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Mensaje a enviar (Puedes editarlo libremente)</label>
                        <textarea id="cobranza_mensaje" class="form-control" rows="8"></textarea>
                    </div>
                    
                    <input type="hidden" id="cobranza_tlf_cliente">
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary fw-bold" onclick="window.copiarMensajeCobranza()"><i class="bi bi-clipboard"></i> Copiar</button>
                    <button type="button" class="btn btn-success fw-bold" onclick="window.enviarWhatsAppCobranza()"><i class="bi bi-send"></i> Enviar por WhatsApp</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE USUARIO (INTEGRADO EN INDEX) -->
    <div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: #0B4C8C;"><h5 class="modal-title fw-bold" id="modalUsuarioLabel">Usuario</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body bg-light">
                    <form id="formUsuario">
                        <input type="hidden" name="id_usuario" id="id_usuario_modal" value="0">
                        <div class="row g-2 mb-2">
                            <div class="col-4"><label class="small fw-bold text-muted">Nombre</label><input type="text" name="nombre" id="u_nombre" class="form-control form-control-sm" required></div>
                            <div class="col-4"><label class="small fw-bold text-muted">Apellido</label><input type="text" name="apellido" id="u_apellido" class="form-control form-control-sm" required></div>
                            <div class="col-4"><label class="small fw-bold text-muted">Cedula</label><input type="text" name="cedula" id="u_cedula" class="form-control form-control-sm" required></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-4"><label class="small fw-bold text-muted">Telefono</label><input type="text" name="tlf" id="u_tlf" class="form-control form-control-sm" required></div>
                            <div class="col-4"><label class="small fw-bold text-muted">Profesion/Cargo</label><input type="text" name="profesion" id="u_profesion" class="form-control form-control-sm"></div>
                            <div class="col-4"><label class="small fw-bold text-muted">Email</label><input type="email" name="email" id="u_email" class="form-control form-control-sm"></div>
                        </div>
                        <hr>
                        <div class="row g-2 mb-2">
                            <div class="col-4"><label class="small fw-bold text-muted text-danger">Usuario (Login)</label><input type="text" name="nombre_usuario" id="u_nombre_usuario" class="form-control form-control-sm border-danger" required></div>
                            <div class="col-4"><label class="small fw-bold text-muted text-danger">Contrasena</label><input type="text" name="contrasena" id="u_contrasena" class="form-control form-control-sm border-danger" placeholder="Dejar vacia para no cambiar"></div>
                            <div class="col-4"><label class="small fw-bold text-muted text-primary">Nivel Nominal (Rol Visual)</label>
                                <select name="rol" id="u_rol" class="form-select form-select-sm border-primary fw-bold" required>
                                    <option value="ventas">Asesor de Ventas</option>
                                    <option value="jefe_ventas">Jefe de Ventas</option>
                                    <option value="logistica">Logistica y Produccion</option>
                                    <option value="admin">Administrador</option>
                                    <option value="licitaciones">Licitaciones (Visor)</option>
                                    <option value="supervisor_licitaciones">Supervisor Licitaciones</option>
                                    <option value="jefe_diseno">Jefe de Diseño</option>
                                    <option value="disenador">Diseñador</option>
                                    <option value="master">Master</option>
                                    <option value="nomina">Nomina (RRHH)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3 p-3 bg-white border rounded">
                            <label class="small fw-bold text-muted text-primary mb-2 d-block border-bottom pb-1"><i class="bi bi-shield-check"></i> Asignacion de Permisos</label>
                            <div class="row g-2" style="font-size: 0.8rem;">
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="1" class="check-permiso"> [1] Ver Directorio de Clientes</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="2" class="check-permiso text-primary"> [2] Crear y Editar Clientes</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="3" class="check-permiso text-danger"> [3] Eliminar Clientes</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="4" class="check-permiso"> [4] Acceso al Cotizador (Catalogo)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="5" class="check-permiso text-primary"> [5] Generar Cotizaciones</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="26" class="check-permiso text-primary"> [26] Cotizador Especial (Licitacion)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="6" class="check-permiso"> [6] Ver Cotizaciones (Solo Propias)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="7" class="check-permiso text-primary"> [7] Ver Cotizaciones (Todas - Gerencia)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="8" class="check-permiso text-warning"> [8] Editar Cotizaciones</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="9" class="check-permiso text-danger"> [9] Eliminar Cotizaciones</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="10" class="check-permiso"> [10] Ver Ordenes de Fabrica</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="11" class="check-permiso text-primary"> [11] Generar Ordenes de Fabrica</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="12" class="check-permiso text-danger"> [12] Eliminar Ordenes de Fabrica</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="13" class="check-permiso text-warning"> [13] Prealertas de Producción (Gerencia de Logística)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="14" class="check-permiso text-success"> [14] Gestion de Catalogo</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="15" class="check-permiso text-success"> [15] Gestion de Usuarios</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="16" class="check-permiso text-success"> [16] Auditoria TOTAL Y General</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="17" class="check-permiso"> [17] Licitaciones (Visor)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="18" class="check-permiso text-primary"> [18] Licitaciones (Administrar)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="19" class="check-permiso"> [19] Panel Estadisticas (Mi Desempeno)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="20" class="check-permiso text-primary"> [20] Panel Gerencial (Todas y Modificar Metas)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="21" class="check-permiso text-success"> [21] Gestion Almacen y MP</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="27" class="check-permiso text-success"> [27] Crear Recetas de Prendas</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="28" class="check-permiso text-primary"> [28] Especificar Tallas y Telas (Oculto en Cotizador)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="29" class="check-permiso text-primary"> [29] Enviar a Logistica / Produccion</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="22" class="check-permiso text-success"> [22] WhatsApp / Alertas de Re-uniforme</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="23" class="check-permiso text-info"> [23] Auditoria: Ver Dpto. Ventas</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="24" class="check-permiso text-info"> [24] Auditoria: Ver Licitaciones</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="25" class="check-permiso text-info"> [25] Auditoria: Ver Logistica/Admin</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="30" class="check-permiso text-warning"> [30] Editar Cotizaciones Especiales</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="31" class="check-permiso text-danger"> [31] Otorgar Descuento (% / Especial)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="32" class="check-permiso text-danger"> [32] Exonerar / Modificar I.V.A</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="33" class="check-permiso text-danger"> [33] Descuento Logistica de Envio</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="34" class="check-permiso text-danger"> [34] Asignar Precio de Flete</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="35" class="check-permiso text-warning"> [35] Pagar / Gestionar Comisiones <i class="bi bi-coin text-warning"></i></label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="36" class="check-permiso text-info"> [36] Gestion de Envios y Avances <i class="bi bi-truck text-info"></i></label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="37" class="check-permiso text-warning"> [37] Solicitar Insumo Textil (Operario) 🧵</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="38" class="check-permiso text-danger"> [38] Bandeja Insumos / Compras 🛒</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="39" class="check-permiso" style="color: #a855f7;"> [39] Modulo Nomina (Acceso) 💵</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="40" class="check-permiso" style="color: #a855f7;"> [40] Nomina: Registro 📝</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="41" class="check-permiso" style="color: #a855f7;"> [41] Nomina: Pagos 💰</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="42" class="check-permiso" style="color: #a855f7;"> [42] Nomina: Prestaciones 🏆</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="43" class="check-permiso text-danger"> [43] Revertir Orden a Ventas</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="44" class="check-permiso text-success"> [44] Envio Directo (Sin Orden) 📦</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="45" class="check-permiso text-info"> [45] Modulo Administracion 🏢</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="46" class="check-permiso text-success"> [46] Modulo Inv. Prendas 👕</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="47" class="check-permiso" style="color: #f59e0b;"> [47] Gestion de Tareas ✅</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="48" class="check-permiso text-danger"> [48] Semaforo de Envios (Prioridad) 🛑</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="49" class="check-permiso text-danger fw-bold"> [49] Omitir Restriccion 60% (Pago Inicial) 🔓</label></div>
                                
                                <?php if(tieneVector(50) || $rol_actual === 'master'): ?>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="50" class="check-permiso text-danger fw-bold"> [50] Bloquear / Desbloquear Usuarios 🚫</label></div>
                                <?php endif; ?>
                                
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="51" class="check-permiso text-warning fw-bold"> [51] Editar Cotiz. Bloqueadas (Sin Limite) 🔓</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="52" class="check-permiso text-info fw-bold"> [52] Modulo Muestras Fisicas 🎁</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="53" class="check-permiso text-success fw-bold"> [53] Alterar Ordenes de Fabrica ⚙️</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="54" class="check-permiso text-primary fw-bold"> [54] Ver Órdenes de Todos (Quitar = Solo Propias)</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="55" class="check-permiso text-primary fw-bold"> [55] Jefe de Diseño: Asignar y Priorizar Órdenes</label></div>
                                <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="56" class="check-permiso text-success fw-bold"> [56] Diseñador: Ver Órdenes Asignadas y Actualizar Estado</label></div>
                            </div>
                            <div class="mt-3 border-top pt-2">
                                <label class="d-flex align-items-center fw-bold text-dark"><input type="checkbox" name="tiene_meta" id="u_tiene_meta" value="1" class="me-2" style="transform: scale(1.2);"> ¿Trabaja con Metas de Ventas?</label>
                            </div>
                            
                            <?php if(tieneVector(50) || $rol_actual === 'master'): ?>
                            <div class="mt-2 border-top pt-2" style="background-color: #ffe5e5; padding: 10px; border-radius: 6px;">
                                <label class="d-flex align-items-center fw-bold text-danger"><input type="checkbox" name="permisos[]" value="BLOCKED" id="u_bloqueado" class="me-2 check-permiso" style="transform: scale(1.4);"> 🚫 BLOQUEAR ACCESO AL SISTEMA A ESTE USUARIO</label>
                            </div>
                            <?php endif; ?>
                            
                        </div>
                        
                        <div class="text-end border-top pt-3"><button type="button" class="btn btn-sm btn-outline-secondary me-2 fw-bold" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-sm text-white fw-bold shadow-sm" style="background-color: #FF7518;" id="btnGuardarUser">Guardar Datos</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <?php
        if(tieneVector(1)) include 'vistas/clientes.php';
        if(in_array($rol_actual, ['master', 'ventas', 'jefe_ventas'], true)) include 'vistas/clientes_cotizar.php';
        if(tieneVector(4)) include 'vistas/ventas.php';
        if(tieneVector(26)) include 'vistas/licitaciones_cotizador.php';
        if(tieneVector(14)) include 'vistas/gestion.php';
        if(tieneVector(6) || tieneVector(7)) include 'vistas/cotizaciones.php';
        if(tieneVector(19) || tieneVector(20)) include 'vistas/dashboard.php';
        if(tieneVector(15)) include 'vistas/usuarios.php';
        if(tieneVector(16) || tieneVector(23) || tieneVector(24) || tieneVector(25)) include 'vistas/auditoria.php';
        if(tieneVector(10)) include 'vistas/ordenes.php';
        if(tieneVector(17) || tieneVector(18)) include 'vistas/licitaciones_visor.php';
        if($rol_actual === 'master') include 'vistas/cotizaciones_eliminadas.php';
        ?>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="js/init_index.js?v=<?= time(); ?>"></script>
    <script src="js/logica_spa.js?v=<?= time(); ?>"></script>
    <script src="js/especificaciones.js?v=<?= time(); ?>"></script>
    <?php if($rol_actual === 'master'): ?>
        <script src="js/cotizaciones_eliminadas.js?v=<?= time(); ?>"></script>
    <?php endif; ?>
    
    <?php if(tieneVector(19) || tieneVector(20)): ?>
        <script src="js/dashboard.js?v=<?= time(); ?>"></script>
    <?php endif; ?>
    
    <script src="js/usuarios.js?v=<?= time(); ?>"></script>
    
    <?php if(tieneVector(16) || tieneVector(23) || tieneVector(24) || tieneVector(25)): ?>
        <script src="js/auditoria.js?v=<?= time(); ?>"></script>
    <?php endif; ?>
    
    <?php if(tieneVector(13) || tieneVector(16) || tieneVector(22) || tieneVector(23) || tieneVector(24) || tieneVector(25)): ?>
        <script src="js/notificaciones.js?v=<?= time(); ?>"></script>
    <?php endif; ?>
    
    <?php if(tieneVector(17) || tieneVector(18)): ?>
        <script src="js/licitaciones.js?v=<?= time(); ?>"></script>
    <?php endif; ?>

    <script src="js/operaciones_portal.js?v=<?= time(); ?>"></script>
    <script src="../auditoria/auditoria_espia.js?v=<?= time(); ?>"></script>
    
</body>
</html>