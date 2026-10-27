<?php
// Archivo: Unifuerte/Documentos/Formatos_43_46/exportar_pdf.php

$nombres = $_POST['nombres'] ?? '';
$cedula = $_POST['cedula'] ?? '';
$nacionalidad = $_POST['nacionalidad'] ?? '';
$estado_civil = $_POST['estado_civil'] ?? '';
$profesion = $_POST['profesion'] ?? '';
$cargo = $_POST['cargo'] ?? '';
$domicilio_rep = $_POST['domicilio_rep'] ?? '';
$empresa = $_POST['empresa'] ?? '';
$correo = $_POST['correo'] ?? '';
$registro_mercantil = $_POST['registro_mercantil'] ?? '';
$nro_procedimiento = $_POST['nro_procedimiento'] ?? '0394';
$ciudad_firma = $_POST['ciudad_firma'] ?? 'Ciudad Guayana';

// Asignar el logo dinámicamente si en el texto de la empresa incluyeron Marinicol
$logo_dinamico = (stripos($empresa, 'marinicole') !== false || stripos($empresa, 'marinicol') !== false) ? '../../img/marinicole.jpg' : '../../img/unifuerte.jpg';

$dia = date('d');
$mes_letras = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$mes = $mes_letras[date('n')-1];
$anio = date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Documentos PDVSA</title>
    <style>
        @page { size: letter; margin: 2.5cm; }
        body { font-family: 'Arial', sans-serif; font-size: 11pt; line-height: 1.5; color: #000; background: #fff; }
        .document-page { page-break-after: always; padding-bottom: 20px; }
        .document-page:last-child { page-break-after: auto; }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .bold { font-weight: bold; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .header-table td { border: 1px solid #000; padding: 8px; text-align: center; font-size: 10pt; font-weight: bold; }
        .logo-img { max-width: 150px; height: auto; }
        
        .caja-logo-oferente { border: 1px solid #000; padding: 10px; width: 250px; text-align: center; font-size: 9pt; font-weight: bold; margin-bottom: 20px; }
        
        .titulo-formato { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 20px; text-transform: uppercase;}
        .parrafo-legal { text-align: justify; margin-bottom: 15px; }
        
        .zona-firmas { margin-top: 60px; }
        .linea-firma { width: 300px; border-bottom: 1px solid #000; margin-bottom: 5px; }
        .texto-firma { font-size: 10pt; line-height: 1.2; }
    </style>
</head>
<body onload="window.print()">

    <div class="document-page">
        <table class="header-table">
            <tr>
                <td width="20%"><img src="<?php echo $logo_dinamico; ?>" class="logo-img" alt="Logo Empresa"></td>
                <td width="60%">"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</td>
                <td width="20%">No. A-182-26-<?php echo $nro_procedimiento; ?></td>
            </tr>
        </table>

        <div class="caja-logo-oferente">
            LOGO DE LA EMPRESA<br>OFERENTE O ALIANZA<br>OFERENTE, SEGÚN APLIQUE
        </div>

        <div class="titulo-formato">
            FORMATO I.1 - MANIFESTACIÓN DE VOLUNTAD PARA PARTICIPAR<br><br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<?php echo $nro_procedimiento; ?><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
        </div>

        <p class="parrafo-legal">
            Yo, <span class="bold"><?php echo $nombres; ?></span>, de nacionalidad <span class="bold"><?php echo $nacionalidad; ?></span>, estado civil <span class="bold"><?php echo $estado_civil; ?></span>, profesión (u ocupación) <span class="bold"><?php echo $profesion; ?></span>, domiciliado en <span class="bold"><?php echo $domicilio_rep; ?></span>, actuando en este acto en mi carácter de <span class="bold"><?php echo $cargo; ?></span>, de la Empresa <span class="bold"><?php echo (!empty($empresa) ? $empresa : 'UNIFUERTE, C.A.'); ?></span> ("el oferente") debidamente facultado para este acto por <span class="bold"><?php echo $registro_mercantil; ?></span>, en relación con el Procedimiento de Contratación "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN" N° A-182-26-<span class="bold"><?php echo $nro_procedimiento; ?></span>, referente a la adquisición de bienes, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento <span class="bold">Manifiesto la voluntad de participar</span> en el procedimiento de contratación N° A-182-26-<span class="bold"><?php echo $nro_procedimiento; ?></span> cuyo objeto es "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN".
        </p>

        <p class="parrafo-legal">
            Además, declaro bajo fe de juramento, la veracidad y legalidad de toda la información suministrada en los documentos consignados. Así mismo, autorizo a EL ENTE CONTRATANTE, a realizar prácticas de auditoría para comprobar el contenido de cualquier información durante cualquiera de las fases del proceso de contratación, sujeto a las sanciones previstas en el Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas (DRVFLCP) y las establecidas en el Pliego de Condiciones.
        </p>

        <p class="parrafo-legal">
            A los efectos de cualquier notificación pertinente al procedimiento, la dirección de mi representada es la antes citada, Correo Electrónico: <span class="bold"><?php echo $correo; ?></span>.
        </p>

        <p class="parrafo-legal">En <span class="bold"><?php echo $ciudad_firma; ?></span>, a los <span class="bold"><?php echo $dia; ?></span> días del mes de <span class="bold"><?php echo $mes; ?></span> de <span class="bold"><?php echo $anio; ?></span>.</p>

        <div class="zona-firmas">
            <div class="linea-firma"></div>
            <div class="texto-firma">
                FIRMA DEL REPRESENTANTE<br>
                C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
                SELLO DE LA CONTRATISTA
            </div>
        </div>
    </div>

    <div class="document-page">
        <table class="header-table">
            <tr>
                <td width="20%"><img src="<?php echo $logo_dinamico; ?>" class="logo-img" alt="Logo Empresa"></td>
                <td width="60%">"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</td>
                <td width="20%">No. A-182-26-<?php echo $nro_procedimiento; ?></td>
            </tr>
        </table>

        <div class="caja-logo-oferente">
            LOGO DE LA EMPRESA<br>OFERENTE O ALIANZA<br>OFERENTE, SEGÚN APLIQUE
        </div>

        <div class="titulo-formato">
            FORMATO I.3. - CARTA DE AUTORIZACIÓN PARA DESCONTAR EL APORTE PARA EL FONDO SOCIAL<br><br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<?php echo $nro_procedimiento; ?><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
        </div>

        <p class="parrafo-legal">
            Yo, <span class="bold"><?php echo $nombres; ?></span>, cédula de identidad N° <span class="bold"><?php echo $cedula; ?></span>, de nacionalidad <span class="bold"><?php echo $nacionalidad; ?></span>, estado civil <span class="bold"><?php echo $estado_civil; ?></span>, profesión (u ocupación) <span class="bold"><?php echo $profesion; ?></span>, domiciliado en <span class="bold"><?php echo $domicilio_rep; ?></span>, actuando en este acto en mi carácter de <span class="bold"><?php echo $cargo; ?></span>, de la Empresa <span class="bold"><?php echo (!empty($empresa) ? $empresa : 'UNIFUERTE, C.A.'); ?></span> ("el oferente") debidamente facultado para este acto por <span class="bold"><?php echo $registro_mercantil; ?></span>, en relación con el Procedimiento de Contratación "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN" N° A-182-26-<span class="bold"><?php echo $nro_procedimiento; ?></span>, referente a la adquisición de bienes, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento y en aras de llevar a cabo el apoyo, incentivo y ejecución de los programas sociales que tengan por finalidad el beneficio de las comunidades, autorizo a descontar del pago de las valuaciones correspondientes a la ejecución del contrato, el porcentaje del tres (3%) correspondiente a mi contribución al fondo social como parte del programa de empresas de producción social establecido, y el cual forma parte de este procedimiento de selección de contratistas.
        </p>

        <p class="parrafo-legal">En <span class="bold"><?php echo $ciudad_firma; ?></span>, a los <span class="bold"><?php echo $dia; ?></span> días del mes de <span class="bold"><?php echo $mes; ?></span> de <span class="bold"><?php echo $anio; ?></span>.</p>

        <div class="zona-firmas">
            <div class="linea-firma"></div>
            <div class="texto-firma">
                FIRMA DEL REPRESENTANTE<br>
                C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
                SELLO DE LA CONTRATISTA
            </div>
        </div>
    </div>

</body>
</html>