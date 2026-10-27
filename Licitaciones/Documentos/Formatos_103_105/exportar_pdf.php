<?php
// Archivo: Unifuerte/Documentos/Formatos_103_105/exportar_pdf.php

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

$monto_capital = $_POST['monto_capital'] ?? '';
$monto_nacional = $_POST['monto_nacional'] ?? '';
$monto_extranjero = $_POST['monto_extranjero'] ?? '';
$otra_moneda = $_POST['otra_moneda'] ?? 'NO APLICA';

$tiempo_letras = $_POST['tiempo_letras'] ?? 'CERO';
$tiempo_numeros = $_POST['tiempo_numeros'] ?? '0';

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
    <title>Imprimir Formatos 103-105</title>
    <style>
        @page { size: letter; margin: 1.5cm 2.5cm; } /* Márgenes verticales reducidos */
        
        /* LETRA PEQUEÑA (9pt) PARA EVITAR QUE SE CORTE */
        body { font-family: 'Arial', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; background: #fff; }
        .document-page { page-break-after: always; padding-bottom: 10px; }
        .document-page:last-child { page-break-after: auto; }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .bold { font-weight: bold; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-table td { border: 1px solid #000; padding: 6px; text-align: center; font-size: 8.5pt; font-weight: bold; }
        .logo-img { max-width: 130px; height: auto; }
        
        .caja-logo-oferente { border: 1px solid #000; padding: 10px; width: 250px; text-align: center; font-size: 8pt; font-weight: bold; margin-bottom: 15px; }
        
        .titulo-formato { font-size: 9.5pt; font-weight: bold; text-align: center; margin-bottom: 15px; text-transform: uppercase;}
        .parrafo-legal { text-align: justify; margin-bottom: 8px; }
        
        .zona-firmas { margin-top: 30px; }
        .linea-firma { width: 300px; border-bottom: 1px solid #000; margin-bottom: 5px; }
        .texto-firma { font-size: 9pt; line-height: 1.2; }
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
            FORMATO II.10.- DECLARACIÓN BAJO FE DE JURAMENTO SOBRE LA COMPOSICIÓN DEL CAPITAL SOCIAL DE LA EMPRESA<br><br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<?php echo $nro_procedimiento; ?><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
        </div>

        <p class="parrafo-legal">
            Yo, <span class="bold"><?php echo $nombres; ?></span>, cédula de identidad N° <span class="bold"><?php echo $cedula; ?></span>, de nacionalidad <span class="bold"><?php echo $nacionalidad; ?></span>, estado civil <span class="bold"><?php echo $estado_civil; ?></span>, profesión (u ocupación) <span class="bold"><?php echo $profesion; ?></span>, domiciliado en <span class="bold"><?php echo $domicilio_rep; ?></span>, actuando en este acto en mi carácter de <span class="bold"><?php echo $cargo; ?></span>, de la empresa <span class="bold"><?php echo $empresa; ?></span> ("el oferente") debidamente facultado para este acto por <span class="bold"><?php echo $registro_mercantil; ?></span>, en relación con el procedimiento de contratación <span class="bold">"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</span> N° A-182-26-<span class="bold"><?php echo $nro_procedimiento; ?></span>, referente a la <span class="bold"><?php echo $tipo_adquisicion; ?></span>, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento declaro bajo fe de juramento y sujeto a las sanciones previstas en el Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas (DRVFLCP), que: la información que a continuación se señala es cierta y que la misma se adecúa fielmente a la composición de la empresa y a los porcentajes nacionales que se aplicarán en caso de otorgarse a mi representada la contratación a que se contrae.
        </p>

        <p class="parrafo-legal">INFORMACIÓN REQUERIDA:</p>
        <p class="parrafo-legal">COMPOSICIÓN DEL CAPITAL SOCIAL DE LA EMPRESA</p>
        <p class="parrafo-legal">Capital de la Empresa</p>
        <p class="parrafo-legal">Monto de Capital Social de la Empresa: Bs. <span class="bold"><?php echo $monto_capital; ?></span></p>
        <p class="parrafo-legal">Monto del Capital Social Nacional: Bs. <span class="bold"><?php echo $monto_nacional; ?></span></p>
        <p class="parrafo-legal">Monto del Capital Social Extranjero: Bs. <span class="bold"><?php echo $monto_extranjero; ?></span></p>
        <p class="parrafo-legal">(En caso de ser necesaria la utilización de otra moneda, favor indicar la misma) <span class="bold"><?php echo $otra_moneda; ?></span></p>

        <p class="parrafo-legal">
            Autorizo de manera expresa e irrevocable a <span class="bold">EL ENTE CONTRATANTE</span> para que esta información sea investigada y verificada en cualquier momento del proceso de Contratación y durante la ejecución del contrato y en caso de que la misma sea falsa o no se ajuste a los porcentajes antes señalados <span class="bold">EL ENTE CONTRATANTE</span>, sin perjuicio del ejercicio de las sanciones legales podrá optar entre descontar o recobrar lo pagado de más, producto de la aplicación de las Medidas o rescindir el convenio otorgado, sin pago de indemnización alguna.
        </p>
        
        <p class="parrafo-legal">
            Asimismo, me comprometo a que los subcontratistas que sean requeridos para la ejecución del Convenio cumplirán al menos con los porcentajes de participación nacional antes señalados.
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
        
        <p class="parrafo-legal"><b>INSTRUCCIONES GENERALES:</b></p>
    </div>

    <div class="document-page">
        <table class="header-table">
            <tr>
                <td width="20%"><img src="<?php echo $logo_dinamico; ?>" class="logo-img" alt="Logo Empresa"></td>
                <td width="60%">"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</td>
                <td width="20%">No. A-182-26-<?php echo $nro_procedimiento; ?></td>
            </tr>
        </table>

        <p class="parrafo-legal"><b>1.</b> La declaración jurada debe ser hecha por el declarante bajo juramento de decir verdad, en papel común y sin timbres fiscales o estampillas.</p>
        <p class="parrafo-legal"><b>2.</b> No requiere la firma de personas distintas del declarante, ni ser autenticada ante oficinas públicas.</p>
        <p class="parrafo-legal"><b>3.</b> El declarante debe estampar su firma en original al final de la declaración y en cada una de las páginas que la conformen.</p>
        <p class="parrafo-legal"><b>4.</b> La omisión de este formato implicará la pérdida del beneficio de las Medidas, pero el Oferente continuará participando en el proceso de contratación.</p>
        <p class="parrafo-legal">Esta información deberá formar parte integrante del sobre contentivo de la oferta técnica y será utilizada como criterio de evaluación de las ofertas.</p>
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
            FORMATO II.11. - DECLARACIÓN JURADA DE DISPONIBILIDAD PARA EL INICIO DE LA ACTIVIDAD DE LA CONTRATACIÓN.<br><br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<?php echo $nro_procedimiento; ?><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
        </div>

        <p class="parrafo-legal">
            Yo, <span class="bold"><?php echo $nombres; ?></span>, cédula de identidad N° <span class="bold"><?php echo $cedula; ?></span>, de nacionalidad <span class="bold"><?php echo $nacionalidad; ?></span>, estado civil <span class="bold"><?php echo $estado_civil; ?></span>, profesión (u ocupación) <span class="bold"><?php echo $profesion; ?></span>, domiciliado en <span class="bold"><?php echo $domicilio_rep; ?></span>, actuando en este acto en mi carácter de <span class="bold"><?php echo $cargo; ?></span>, de la empresa <span class="bold"><?php echo $empresa; ?></span> ("el oferente") debidamente facultado para este acto por <span class="bold"><?php echo $registro_mercantil; ?></span>, en relación con el procedimiento de contratación <span class="bold">"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</span> N° A-182-26-<span class="bold"><?php echo $nro_procedimiento; ?></span>, referente a la <span class="bold"><?php echo $tipo_adquisicion; ?></span>, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento declaro bajo fe de juramento y sujeto a las sanciones previstas en el DRVFLCP y su reglamento vigente que la empresa a la cual represento tiene la disponibilidad de iniciar la ejecución de la obra en (colocar en letras) <span class="bold"><?php echo $tiempo_letras; ?></span> (colocar en números) <span class="bold"><?php echo $tiempo_numeros; ?></span> días (inmediatamente).
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