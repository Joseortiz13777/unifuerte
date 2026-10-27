<?php
// Archivo: Unifuerte/Documentos/Formatos_123_124/exportar_pdf.php

$nombres = $_POST['nombres'] ?? '';
$cedula = $_POST['cedula'] ?? '';
$nombre_aseguradora = $_POST['nombre_aseguradora'] ?? '';
$registro_aseguradora = $_POST['registro_aseguradora'] ?? '';
$estado_aseguradora = $_POST['estado_aseguradora'] ?? '';
$num_sudeaseg = $_POST['num_sudeaseg'] ?? '';
$registro_mercantil = $_POST['registro_mercantil'] ?? '';

$monto_letras_usd = $_POST['monto_letras_usd'] ?? '';
$monto_numeros_usd = $_POST['monto_numeros_usd'] ?? '';
$monto_letras_bs = $_POST['monto_letras_bs'] ?? '';
$monto_numeros_bs = $_POST['monto_numeros_bs'] ?? '';
$centimos_bs = $_POST['centimos_bs'] ?? '00';
$fecha_bcv = $_POST['fecha_bcv'] ?? '';

$nro_contrato = $_POST['nro_contrato'] ?? '';
$empresa = $_POST['empresa'] ?? ''; // Variable esperada si este archivo es alimentado por un POST form

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
    <title>Imprimir Formatos 123 y 124</title>
    <style>
        /* MÁRGENES MÍNIMOS PARA QUE ENCAJE EL TEXTO A 10pt */
        @page { size: letter; margin: 1cm; } 
        
        /* LETRA A 10pt Y REDUCCIÓN DE INTERLINEADO */
        body { font-family: 'Arial', sans-serif; font-size: 10pt; line-height: 1.15; color: #000; background: #fff; }
        .document-page { page-break-after: always; padding-bottom: 5px; }
        .document-page:last-child { page-break-after: auto; }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .bold { font-weight: bold; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { border: 1px solid #000; padding: 4px; text-align: center; font-size: 9pt; font-weight: bold; }
        .logo-img { max-width: 110px; height: auto; }
        
        .titulo-formato { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 8px; text-transform: uppercase;}
        .parrafo-legal { text-align: justify; margin-bottom: 6px; }
    </style>
</head>
<body onload="window.print()">

    <div class="document-page">
        <table class="header-table">
            <tr>
                <td width="20%"><img src="<?php echo $logo_dinamico; ?>" class="logo-img" alt="Logo Empresa"></td>
                <td width="60%">"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</td>
                <td width="20%">No. A-182-26-0394</td>
            </tr>
        </table>

        <div class="titulo-formato">
            CAPITULO V - MODELOS DE FIANZAS<br>
            MODELO DE FIANZA FIEL CUMPLIMIENTO
        </div>

        <p class="parrafo-legal">
            Yo, <span class="bold"><?php echo $nombres; ?></span>, venezolano(a), mayor de edad, portador(a) de la cédula de identidad N° <span class="bold"><?php echo $cedula; ?></span>, procediendo en este acto en mi carácter de apoderado(a) de <span class="bold"><?php echo $nombre_aseguradora; ?></span> sociedad inscrita por ante el registro <span class="bold"><?php echo $registro_aseguradora; ?></span> del estado <span class="bold"><?php echo $estado_aseguradora; ?></span>, el día ___ de ________ de ______, bajo el N° ____, Tomo _____, según consta en poder debidamente autenticado por ante la Notaría Pública ____________ en fecha ___ de _________ de ______, bajo el N° ____, Tomo ______ e inscrita en la Superintendencia de la Actividad Aseguradora bajo el N° <span class="bold"><?php echo $num_sudeaseg; ?></span>, en lo adelante denominada "LA COMPAÑÍA", por el presente documento declaro que: constituyo a mi representada en fiadora solidaria y principal pagadora de <span class="bold"><?php echo (!empty($empresa) ? $empresa : 'UNIFUERTE, C.A.'); ?></span>, domiciliada en <span class="bold">CIUDAD GUAYANA</span> y constituida por documento inscrito en el registro <span class="bold"><?php echo $registro_mercantil; ?></span> del estado <span class="bold">BOLÍVAR</span> en fecha ___ de _______ de ______, bajo el N° _____, Tomo ______, en lo adelante denominado "EL AFIANZADO", hasta por la cantidad de <span class="bold"><?php echo $monto_letras_usd; ?></span> Dólares Estados Unidos de América (USD: <span class="bold"><?php echo $monto_numeros_usd; ?></span>), equivalente al monto de <span class="bold"><?php echo $monto_letras_bs; ?></span> Bolívares con <span class="bold"><?php echo $centimos_bs; ?></span> céntimos (BS. <span class="bold"><?php echo $monto_numeros_bs; ?></span>) de conformidad con la publicación del tipo de cambio referencial del Banco Central de Venezuela de fecha <span class="bold"><?php echo $fecha_bcv; ?></span>, a fin de dar cumplimiento a lo establecido en el artículo 130 de la Ley del Banco Central de Venezuela para garantizar a BARIVEN, S.A., filial de Petróleos de Venezuela, S.A., sociedad mercantil, domiciliada en la ciudad de Caracas, Venezuela, debidamente inscrita por ante el registro mercantil Primero de la Circunscripción Judicial del Distrito Federal y estado Miranda, en fecha 23 de diciembre de 1975, bajo el N° 31, Tomo 59-A Segundo, cuya última modificación aparece inscrita en la citada oficina de registro, en fecha 15 de febrero de 2007, bajo el N° 52, Tomo 19-A-PRO, en lo sucesivo denominado "EL ACREEDOR", el fiel, cabal y oportuno cumplimiento por parte de "EL AFIANZADO" de todas y cada una de las obligaciones que resulten a su cargo y a favor de "el acreedor", según contrato N° <span class="bold"><?php echo $nro_contrato; ?></span>, el cual tiene por objeto "<span class="bold">ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN</span>", en adelante "EL CONTRATO". La presente fianza comenzará a regir a partir de la firma del contrato y tendrá validez hasta que el órgano o ente contratante dicte el acto administrativo de liberación de las fianzas, y una vez que se efectúe la recepción definitiva del objeto de la contratación, se otorgue el finiquito contable, la evaluación de desempeño, se certifique el cumplimiento del compromiso de responsabilidad social cuando aplique, o cualquier otra obligación que a "EL AFIANZADO" imponga la legislación que regula la materia de contrataciones públicas o el contrato. Queda expresamente convenido que el presente contrato de fianza no puede ser cancelado, ni efectuarse cambio alguno en él, sin dar previo aviso por escrito al "ACREEDOR" por lo menos con sesenta (60) días de anticipación; y a tal efecto, deberá obtenerse certificación escrita de la fecha de recibo por parte del "ACREEDOR", contándose el precitado lapso a partir de dicha fecha. En caso de ejecución de la presente fianza, la misma será pagada en Dólares Americanos (USD), sin ningún tipo de rebajas ni deducciones. La no cancelación de la prima por parte de "LA AFIANZADA", no será causal para la anulación de manera unilateral por parte de la compañía. Esta fianza se regirá por los términos y condiciones establecidos en el mencionado contrato de fianza de fiel cumplimiento, por el Decreto Con Rango, Valor y Fuerza de Ley de la Actividad Aseguradora publicado en la Gaceta Oficial de la República Bolivariana de Venezuela N° 6.211 Extraordinario de fecha 30 de diciembre de 2015, por el Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas publicado en la Gaceta Oficial de la República Bolivariana De Venezuela N° 6.145 Extraordinario de fecha 19 de noviembre de 2014; y por su Reglamento, publicado en la Gaceta Oficial de la República Bolivariana de Venezuela N° 39.181 de fecha 19 de mayo de 2009, de conformidad con los términos y condiciones particulares aquí descritos y por las condiciones generales anexa. "LA COMPAÑÍA" renuncia expresamente a los beneficios acordados por los artículos 1833, 1834 y 1836 del Código Civil vigente. Se fija como domicilio especial para todos los efectos de este contrato, la ciudad de Caracas, a la Jurisdicción de cuyos tribunales declaran las partes someterse, con exclusión de cualquier otro.
        </p>
    </div>

    <div class="document-page">
        <table class="header-table">
            <tr>
                <td width="20%"><img src="<?php echo $logo_dinamico; ?>" class="logo-img" alt="Logo Empresa"></td>
                <td width="60%">"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</td>
                <td width="20%">No. A-182-26-0394</td>
            </tr>
        </table>

        <div class="titulo-formato">
            MODELO DE FIANZA DE ANTICIPO
        </div>

        <p class="parrafo-legal">
            Yo, <span class="bold"><?php echo $nombres; ?></span>, venezolano, mayor de edad, domiciliado en ___________ y titular de la cédula de identidad N° <span class="bold"><?php echo $cedula; ?></span>, procediendo en este acto en mi carácter de Gerente de la Sucursal ______________ de entidad <span class="bold"><?php echo $nombre_aseguradora; ?></span>, sociedad mercantil inscrita bajo el N° ______ en el libro de registro de empresas de seguros llevado por la Superintendencia de la Actividad Aseguradora del Ministerio de Finanzas, constituida según documento originalmente inscrito en el registro mercantil de la Circunscripción Judicial del Distrito _________ del estado ___________, bajo el N° ____, Tomo _____, el ___ de _______ de _______, con posteriores modificaciones en su denominación social, la primera inscrita por ante la citada oficina de registro mercantil, de fecha ___ de _______ de _______, bajo el N° ____, Tomo ______ y la segunda por ante el registro mercantil de la Circunscripción Judicial del Distrito _________, el ___ de _________ de ________, bajo el N° _____, Tomo _____. Suficientemente facultado para este acto según poder autenticado por ante la Notaría Pública _________ del Municipio ___________, bajo el N° ____, Tomo _____ de fecha ___ de ________ del 20___, y por lo resuelto en la reunión de la Junta Directiva del ___ de ________ del 20___, según acta N° _______, en lo adelante denominada "la compañía", DECLARO: constituyo a mi representada en fiadora solidaria y principal pagadora de la empresa mercantil: <span class="bold"><?php echo (!empty($empresa) ? $empresa : 'UNIFUERTE, C.A.'); ?></span>, inscrita en el Juzgado ________ de _________ Instancia en lo Civil y Mercantil de la Circunscripción Judicial del estado <span class="bold">BOLÍVAR</span>, en fecha ___ de _______ de ______, bajo el N° ____, folio _____ del libro ____, representada en este acto por el ciudadano ______________, titular de la cédula de identidad N° ___________, en su carácter de _____________, según se evidencia en acta de Asamblea Extraordinaria de los Accionistas celebrada el día ___ de _______ del 20___, punto, y registrada por ante el registro mercantil Segundo de la Circunscripción Judicial del estado <span class="bold">BOLÍVAR</span>, el día ___ de _______ del 20___, bajo el N° ____, Tomo _______, ________ Trimestre, en lo sucesivo denominado "EL AFIANZADO" hasta por la cantidad de <span class="bold"><?php echo $monto_letras_usd; ?></span> Dólares de los Estados Unidos de América (USD), <span class="bold"><?php echo $monto_numeros_usd; ?></span> que de manera referencial y a los solos efectos de dar cumplimiento a lo establecido en el artículo 130 de la Ley del Banco Central de Venezuela, al cambio de <span class="bold"><?php echo $monto_letras_bs; ?></span> Bolívares sin céntimos (BS. <span class="bold"><?php echo $monto_numeros_bs; ?></span>,00), para garantizar a BARIVEN, S.A., filial de Petróleos de Venezuela, S.A., sociedad mercantil, domiciliada en la ciudad de Caracas, República Bolivariana de Venezuela, debidamente inscrita por ante el registro mercantil Primero de la Circunscripción Judicial del Distrito Federal y estado Miranda, en fecha 23 de diciembre de 1975, bajo el N° 31, tomo 59-A Segundo, cuya última modificación aparece inscrita en la citada oficina de registro en fecha 15 de Febrero de 2007, bajo el N° 52, Tomo 19-A, en lo sucesivo denominado "EL ACREEDOR", el reintegro del anticipo que por la cantidad ya mencionada hará "EL AFIANZADO", según contrato N° <span class="bold"><?php echo $nro_contrato; ?></span>, celebrado entre ambos para la ADQUISICIÓN DE: "<span class="bold">ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN</span>". La presente fianza comenzará a regir a partir de la fecha en que "el afianzado" reciba el aludido anticipo y permanecerá en vigencia hasta cuando se haya efectuado su total reintegro mediante las deducciones del porcentaje de amortización establecido en el contrato que debe efectuar "EL ACREEDOR" de cada valuación pagada a "EL AFIANZADO". El monto de esta fianza se reducirá progresivamente en la misma medida en que se fuere amortizando el anticipo y en ningún caso ese monto afianzado podrá ser inferior a la parte no amortizada del anticipo según el régimen de amortización establecido en el contrato. "LA COMPAÑÍA" renuncia expresamente a los beneficios acordados por los artículos 1833, 1834 y 1836 del Código Civil vigente. Queda entendido y convenido que las condiciones generales del contrato de Fianza de Anticipo anexas al presente documento, forman parte integrante del presente contrato. En caso de ejecución de la presente fianza, la misma será pagada en Dólares Americanos (USD), sin ningún tipo de rebajas ni deducciones. La no cancelación de la prima por parte de la afianzada, no será causal para la anulación de manera unilateral por parte de la compañía. esta fianza se regirá por los términos y condiciones establecidos en el mencionado contrato de fianza de anticipo, por el Decreto con Rango, Valor y Fuerza de Ley de la Actividad Aseguradora publicado en la Gaceta Oficial de la República Bolivariana de Venezuela N° 6.211 Extraordinario de fecha 30 de diciembre de 2015, por el Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas, publicado en la Gaceta Oficial de la República Bolivariana de Venezuela N° 6.145 Extraordinario de fecha 19 de noviembre de 2014; y por su Reglamento, publicado en la Gaceta Oficial de la República Bolivariana de Venezuela N° 39.181 de fecha 19 de mayo de 2009, de conformidad con los términos y condiciones particulares aquí descritos y por las condiciones generales anexa. Se fija como domicilio especial para todos los efectos de este contrato, la ciudad de Caracas, a la jurisdicción de cuyos tribunales declaran las partes someterse con exclusión de cualquier otro.
        </p>
    </div>

</body>
</html>