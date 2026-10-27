<?php
// Archivo: Unifuerte/Documentos/Formatos_107/exportar_pdf.php

$nombres = $_POST['nombres'] ?? '';
$cedula = $_POST['cedula'] ?? '';
$nacionalidad = $_POST['nacionalidad'] ?? '';
$estado_civil = $_POST['estado_civil'] ?? '';
$profesion = $_POST['profesion'] ?? '';
$cargo = $_POST['cargo'] ?? '';
$domicilio_rep = $_POST['domicilio_rep'] ?? '';
$empresa = $_POST['empresa'] ?? '';
$registro_mercantil = $_POST['registro_mercantil'] ?? '';
$nro_procedimiento = $_POST['nro_procedimiento'] ?? '0394';
$ciudad_firma = $_POST['ciudad_firma'] ?? 'Ciudad Guayana';
$tipo_adquisicion = $_POST['tipo_adquisicion'] ?? 'adquisición de bienes';

$vigencia_sobre = $_POST['vigencia_sobre'] ?? 'entrega del bien';
$vigencia_letras = $_POST['vigencia_letras'] ?? 'DOCE';
$vigencia_numeros = $_POST['vigencia_numeros'] ?? '12';
$tipo_dias = $_POST['tipo_dias'] ?? 'meses continuos';
$contados_partir = $_POST['contados_partir'] ?? 'entrega del bien';

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
    <title>Imprimir Formato 107</title>
    <style>
        @page { size: letter; margin: 2.5cm; }
        
        /* LETRA 10pt */
        body { font-family: 'Arial', sans-serif; font-size: 10pt; line-height: 1.5; color: #000; background: #fff; }
        .document-page { page-break-after: auto; padding-bottom: 10px; }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .bold { font-weight: bold; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .header-table td { border: 1px solid #000; padding: 6px; text-align: center; font-size: 9pt; font-weight: bold; }
        .logo-img { max-width: 150px; height: auto; }
        
        .caja-logo-oferente { border: 1px solid #000; padding: 10px; width: 250px; text-align: center; font-size: 8pt; font-weight: bold; margin-bottom: 20px; }
        
        .titulo-formato { font-size: 10.5pt; font-weight: bold; text-align: center; margin-bottom: 20px; text-transform: uppercase;}
        .parrafo-legal { text-align: justify; margin-bottom: 12px; }
        
        .zona-firmas { margin-top: 50px; }
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
            FORMATO II.12. DECLARACIÓN JURADA DE DISPONIBILIDAD PARA LA GARANTÍA DE LA EJECUCIÓN DE LA ACTIVIDAD DE LA CONTRATACIÓN<br><br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<?php echo $nro_procedimiento; ?><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
        </div>

        <p class="parrafo-legal">
            Yo, <span class="bold"><?php echo $nombres; ?></span>, cédula de identidad N° <span class="bold"><?php echo $cedula; ?></span>, de nacionalidad <span class="bold"><?php echo $nacionalidad; ?></span>, estado civil <span class="bold"><?php echo $estado_civil; ?></span>, profesión (u ocupación) <span class="bold"><?php echo $profesion; ?></span>, domiciliado en <span class="bold"><?php echo $domicilio_rep; ?></span>, actuando en este acto en mi carácter de <span class="bold"><?php echo $cargo; ?></span>, de la empresa <span class="bold"><?php echo $empresa; ?></span> ("el oferente") debidamente facultado para este acto por <span class="bold"><?php echo $registro_mercantil; ?></span>, en relación con el procedimiento de contratación <span class="bold">"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</span> N° A-182-26-<span class="bold"><?php echo $nro_procedimiento; ?></span>, referente a la <span class="bold"><?php echo $tipo_adquisicion; ?></span>, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento declaro bajo fe de juramento y sujeto a las sanciones previstas en el DRVFLCP y su reglamento vigente que la empresa a la cual represento, "garantiza que los bienes son nuevos, libres de defectos y no repotenciados; garantía la cual cubre y protege el CONTRATANTE contra defectos de fábrica o mala calidad de los bienes y la mano de obra empleada," la cual tendrá una vigencia sobre la <span class="bold"><?php echo $vigencia_sobre; ?></span> de <span class="bold"><?php echo $vigencia_letras; ?></span> (<span class="bold"><?php echo $vigencia_numeros; ?></span>) <span class="bold"><?php echo $tipo_dias; ?></span>, contados a partir de la <span class="bold"><?php echo $contados_partir; ?></span>.
        </p>

        <p class="parrafo-legal" style="margin-top: 40px;">En <span class="bold"><?php echo $ciudad_firma; ?></span>, a los <span class="bold"><?php echo $dia; ?></span> días del mes de <span class="bold"><?php echo $mes; ?></span> de <span class="bold"><?php echo $anio; ?></span>.</p>

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