<?php
// SCRIPT DE EJECUCIÓN AUTOMÁTICA PARA EL CIERRE GERENCIAL
error_reporting(0);
require_once '../conexion.php';

// Validar que exista la configuración en la Base de Datos
$q = @mysqli_query($conexion, "SELECT * FROM metas_empresa WHERE id = 1");
if (!$q) exit("No configurado.");
$conf = mysqli_fetch_assoc($q);

$correo = $conf['correo_cierre'];
$dias_frecuencia = (int)$conf['freq_dias'];
$hora_obj = $conf['freq_hora']; 
$ultimo_envio = $conf['ultimo_envio']; 

// Si no hay correo configurado, no hacer nada
if (empty($correo) || $dias_frecuencia <= 0 || empty($hora_obj)) {
    exit("El robot no tiene instrucciones.");
}

$hora_actual = date('H:i');
$hoy = date('Y-m-d');

// Si ya se envió hoy, detener para no llenar de spam el correo
if ($ultimo_envio === $hoy) {
    exit("Reporte de hoy ya enviado.");
}

// Calcular cuántos días han pasado desde el último envío
$dias_pasados = 999;
if (!empty($ultimo_envio)) {
    $dias_pasados = (strtotime($hoy) - strtotime($ultimo_envio)) / (60 * 60 * 24);
}

// Verificar si ya pasaron los días necesarios (Diario=1, Semanal=7, Mensual=30)
if ($dias_pasados >= $dias_frecuencia) {
    
    // Verificar si ya es la hora estipulada
    if (strtotime($hora_actual) >= strtotime($hora_obj)) {
        
        // CALCULAR EL RANGO
        $dias_restar = $dias_frecuencia - 1;
        $desde = date('Y-m-d', strtotime("-$dias_restar days"));
        $hasta = $hoy;
        
        $token = md5('cierre' . $desde . $hasta . 'unifuerte');
        
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'sistema.soyunifuerte.com';
        $link_pdf = "$protocolo://$host/portal_ventas/cierre_pdf.php?desde=$desde&hasta=$hasta&t=$token";

        // ARMADO DEL CORREO
        $fecha_formato = date('d/m/Y', strtotime($desde)) . " al " . date('d/m/Y', strtotime($hasta));
        
        $html_body = "
        <div style='font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 40px; text-align: center;'>
            <div style='background-color: #ffffff; max-width: 500px; margin: 0 auto; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);'>
                <img src='https://sistema.soyunifuerte.com/img/unifuerte.jpg' alt='Unifuerte C.A.' style='max-height: 60px; margin-bottom: 20px;'>
                <h2 style='color: #0B4C8C; margin-top: 0;'>Cierre Gerencial (Automático)</h2>
                <p style='color: #555; font-size: 15px;'>El robot del sistema ha compilado el reporte de <b>Ventas, Producción, Administración y Auditoría</b> para el período del <strong>$fecha_formato</strong>.</p>
                <br>
                <a href='$link_pdf' style='display: inline-block; background-color: #198754; color: #ffffff; padding: 15px 30px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 16px;'>Descargar Reporte Completo en PDF</a>
                <br><br><br>
                <hr style='border: none; border-top: 1px solid #eee;'>
                <p style='color: #aaa; font-size: 11px; margin-bottom: 0;'>
                    Este enlace es seguro y fue generado automáticamente en base a su programación de Cierre Gerencial.<br>
                    &copy; Unifuerte C.A. Todos los derechos reservados.
                </p>
            </div>
        </div>";

        $asunto = "Cierre Automático: " . date('d/m/Y', strtotime($hasta));
        $cabeceras  = "MIME-Version: 1.0\r\n";
        $cabeceras .= "Content-type: text/html; charset=UTF-8\r\n";
        $cabeceras .= "From: Unifuerte Robot <noreply@soyunifuerte.com>\r\n";

        // ENVIAR Y MARCAR COMO ENVIADO
        if (@mail($correo, $asunto, $html_body, $cabeceras)) {
            @mysqli_query($conexion, "UPDATE metas_empresa SET ultimo_envio = '$hoy' WHERE id = 1");
            echo "Reporte enviado con éxito.";
        } else {
            echo "Error al enviar correo. Posible bloqueo del servidor.";
        }
    } else {
        echo "Aún no es la hora.";
    }
} else {
    echo "Aún no han pasado los $dias_frecuencia días.";
}
?>