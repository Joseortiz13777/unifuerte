<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/index.php");
    exit();
}

$id_usuario = intval($_SESSION['usuario_id']);
$q_usr = mysqli_query($conexion, "SELECT nombre, apellido FROM usuario WHERE ID = $id_usuario");
$datos_usr = mysqli_fetch_assoc($q_usr);
$nombre_completo = $datos_usr['nombre'] . ' ' . $datos_usr['apellido'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Tareas y Objetivos - Unifuerte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../portal_ventas/css/estilos_portal.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="css/estilos_admin.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="background-color: #f8f9fa;">

    <!-- HEADER MOVIL -->
    <div class="d-none mobile-header bg-dark text-white p-3 justify-content-between align-items-center w-100 position-sticky top-0" style="z-index: 1020; background-color: #0B4C8C !important;">
        <div class="d-flex align-items-center"><img src="../img/camisa.jpg" style="height:30px; border-radius: 4px;" class="me-2 bg-white p-1" alt="Logo"><h5 class="m-0 font-sugo">Unifuerte</h5></div>
        <button class="btn btn-outline-light btn-sm" onclick="window.location.href='index.php'"><i class="bi bi-arrow-left fs-3"></i></button>
    </div>

    <div class="container py-4">
        <?php include 'vistas/tab_tareas.php'; ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const pane = document.getElementById('tab-tareas');
            if (pane) pane.classList.add('show', 'active');
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/logica_tareas.js?v=<?= time(); ?>"></script>
</body>
</html>