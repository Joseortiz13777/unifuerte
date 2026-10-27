<?php
session_start();

// PROTECCIÓN ESTRICTA CONTRA EL BOTÓN "ATRÁS" DEL NAVEGADOR
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../conexion.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: ../portal_ventas/index.php");
    exit();
}

$error_login = "";

if (isset($_POST['btn_ingresar'])) {
    $usuario = mysqli_real_escape_string($conexion, trim($_POST['usuario']));
    $password = mysqli_real_escape_string($conexion, trim($_POST['password']));
    
    if (!empty($usuario) && !empty($password)) {
        $sql = "SELECT ID, nombre_usuario, rol, nombre, apellido, permisos FROM usuario WHERE nombre_usuario = '$usuario' AND contrasena = '$password'";
        $resultado = mysqli_query($conexion, $sql);
        
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $row = mysqli_fetch_assoc($resultado);
            $rol = strtolower($row['rol']);
            
            if (strpos($row['permisos'] ?? '', 'BLOCKED') !== false) {
                $error_login = "Acceso denegado: Su usuario ha sido suspendido o bloqueado por el Administrador.";
            } else {
                if (in_array($rol, ['ventas', 'admin', 'master', 'jefe_ventas', 'logistica', 'licitaciones', 'supervisor_licitaciones', 'jefe_diseno', 'disenador'])) {
                    $_SESSION['usuario_id'] = $row['ID'];
                    $_SESSION['usuario'] = $row['nombre'];
                    $_SESSION['rol'] = $rol;
                    
                    // ==========================================
                    // AUDITORÍA: REGISTRO DE INGRESO
                    // ==========================================
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
                    $id_u = $row['ID'];
                    mysqli_query($conexion, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_u, 'SISTEMA', 'SESIÓN', 0, NOW(), 'Inicio de sesión exitoso. IP: $ip')");
                    
                    header("Location: ../portal_ventas/index.php");
                    exit();
                } else {
                    $error_login = "Acceso denegado: Tu rol ($rol) no tiene permisos de entrada.";
                }
            }
        } else {
            $error_login = "Usuario o contraseña incorrectos.";
        }
    } else {
        $error_login = "Por favor, rellene todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Unifuerte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        .login-bg { min-height: 100vh; background: linear-gradient(135deg, #0B4C8C 0%, #083766 100%); display: flex; align-items: center; justify-content: center; }
        .login-card { background: white; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); width: 100%; max-width: 420px; padding: 40px 30px; }
        .cursor-pointer { cursor: pointer; }
    </style>
    
    <link rel="icon" href="../img/camisa.jpg" type="image/jpeg">
</head>
<body>
    <div class="login-bg">
        <div class="login-card text-center">
            <img src="../img/unifuerte.jpg" alt="Unifuerte Logo" class="mb-4" style="max-height: 80px; object-fit: contain;">
            <h4 class="fw-bold mb-1" style="color: #0B4C8C;">Portal de Operaciones</h4>
            <p class="text-muted small mb-4">Inicie sesión para acceder al sistema</p>
            
            <?php if(!empty($error_login)): ?>
                <div class="alert alert-danger py-2 small fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error_login; ?></div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <div class="mb-3 text-start">
                    <label class="form-label small fw-bold text-secondary">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" name="usuario" class="form-control bg-light border-start-0" placeholder="Su usuario de acceso" required>
                    </div>
                </div>
                
                <div class="mb-4 text-start">
                    <label class="form-label small fw-bold text-secondary">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" id="inputPassword" class="form-control bg-light border-start-0 border-end-0" placeholder="••••••••" required>
                        <span class="input-group-text bg-light border-start-0 cursor-pointer" id="togglePassword">
                            <i class="bi bi-eye text-muted" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" name="btn_ingresar" class="btn w-100 py-2 fw-bold text-white shadow-sm" style="background-color: #FF7518; border: none; border-radius: 8px;">
                    Ingresar al Sistema
                </button>
            </form>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#inputPassword');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>