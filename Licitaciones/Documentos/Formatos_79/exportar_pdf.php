<?php
// Archivo: Unifuerte/Documentos/Formatos_79/exportar_pdf.php

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

$monto_letras = $_POST['monto_letras'] ?? 'CERO';
$monto_numeros = $_POST['monto_numeros'] ?? '0,00';
$iva_letras = $_POST['iva_letras'] ?? 'CERO';
$iva_numeros = $_POST['iva_numeros'] ?? '0,00';
$total_letras = $_POST['total_letras'] ?? 'CERO';
$total_numeros = $_POST['total_numeros'] ?? '0,00';

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
    <title>Imprimir Formato II.1</title>
    <style>
        @page { size: letter; margin: 2.5cm; }
        body { font-family: 'Arial', sans-serif; font-size: 11pt; line-height: 1.5; color: #000; background: #fff; }
        .document-page { page-break-after: auto; padding-bottom: 20px; }
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
            FORMATO II.1. - CARTA DE PRESENTACIÓN DE LA OFERTA ECONÓMICA<br><br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<?php echo $nro_procedimiento; ?><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
        </div>

        <p class="parrafo-legal">
            Yo, <span class="bold"><?php echo $nombres; ?></span>, cédula de identidad N° <span class="bold"><?php echo $cedula; ?></span>, de nacionalidad <span class="bold"><?php echo $nacionalidad; ?></span>, estado civil <span class="bold"><?php echo $estado_civil; ?></span>, profesión (u ocupación) <span class="bold"><?php echo $profesion; ?></span>, domiciliado en <span class="bold"><?php echo $domicilio_rep; ?></span>, actuando en este acto en mi carácter de <span class="bold"><?php echo $cargo; ?></span>, de la empresa <span class="bold"><?php echo (!empty($empresa) ? $empresa : 'UNIFUERTE, C.A.'); ?></span> ("el oferente") debidamente facultado para este acto por <span class="bold"><?php echo $registro_mercantil; ?></span>, en relación con el procedimiento de contratación "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN" N° A-182-26-<span class="bold"><?php echo $nro_procedimiento; ?></span>, referente a la adquisición de bienes, llevado a efecto por el ente contratante, por el presente documento, nos permitimos presentar a su consideración nuestra mejor oferta económica para el proceso en referencia, la cual según consta en los documentos anexos, asciende a la suma de <span class="bold"><?php echo $monto_letras; ?></span> (<span class="bold"><?php echo $monto_numeros; ?></span>), más el Impuesto al Valor Agregado Nacional (IVA) de <span class="bold"><?php echo $iva_letras; ?></span> (<span class="bold"><?php echo $iva_numeros; ?></span>) para un total con IVA incluido de <span class="bold"><?php echo $total_letras; ?></span> (<span class="bold"><?php echo $total_numeros; ?></span>).
        </p>

        <p class="parrafo-legal">
            Reiterándonos a su disposición para cualquier aclaratoria, nos suscribimos.
        </p>

        <p class="parrafo-legal">
            La vigencia de la presente oferta es de <span class="bold">NOVENTA</span> (<span class="bold">90</span>) días.
        </p>

        <p class="parrafo-legal">
            Cantidad de posiciones/renglones ofertados: <span class="bold">29</span> posiciones/renglones (INDICAR EN CASO DE ADQUISICIÓN DE BIENES).
        </p>

        <p class="parrafo-legal">En <span class="bold"><?php echo $ciudad_firma; ?></span>, a los <span class="bold"><?php echo $dia; ?></span> días del mes de <span class="bold"><?php echo $mes; ?></span> de <span class="bold"><?php echo $anio; ?></span>.</p>

        <div class="zona-firmas">
            <div class="linea-firma"></div>
            <div class="texto-firma">
                <b>FIRMA DEL REPRESENTANTE</b><br>
                C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
                SELLO DE LA CONTRATISTA
            </div>
        </div>
    </div>

</body>
</html>