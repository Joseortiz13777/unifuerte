<?php 
require_once '../../conexion.php'; 

// 1. Capturamos la empresa seleccionada por URL
$empresa = isset($_GET['empresa']) ? $_GET['empresa'] : '';

// 2. Si NO hay empresa seleccionada, mostramos la ventana emergente y detenemos la carga
if (empty($empresa)) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seleccionar Empresa</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f3f4f6; font-family: "Inter", sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .opcion-empresa { cursor:pointer; padding: 15px; border: 2px solid #e5e7eb; border-radius: 15px; transition: all 0.3s; text-align: center; width: 45%; background: white;}
        .opcion-empresa:hover { transform: scale(1.05); }
        .opcion-unifuerte:hover { border-color: #2563eb; }
        .opcion-marinicol:hover { border-color: #059669; }
        .opcion-empresa img { width: 150px; height: 150px; object-fit: contain; border-radius: 10px; }
        .opcion-empresa p { font-weight: bold; margin-top: 15px; font-size: 1.1rem; color: #111827; }
    </style>
</head>
<body>
    <script>
        Swal.fire({
            title: '¿De qué empresa es este documento?',
            html: `
                <div style="display: flex; justify-content: space-around; gap: 10px; margin-top: 20px;">
                    <div class="opcion-empresa opcion-unifuerte" onclick="window.location.href='?empresa=unifuerte'">
                        <img src="../../img/unifuerte.jpg" alt="Unifuerte">
                        <p>Unifuerte</p>
                    </div>
                    <div class="opcion-empresa opcion-marinicol" onclick="window.location.href='?empresa=marinicol'">
                        <img src="../../img/marinicole.jpg" alt="Marinicol">
                        <p>Marinicol</p>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            width: '600px',
            background: '#ffffff'
        });
    </script>
</body>
</html>
<?php
    exit; // Detiene todo hasta que el usuario elija
}

// 3. Lógica de asignación de datos según la empresa elegida
if ($empresa === 'marinicol') {
    $nombre_empresa = 'MARINICOL Y ASOCIADOS, C.A';
    $rif_empresa = 'J-311662278';
    $direccion_empresa = 'DOMICILIO FISCAL CR UD-321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A, B, C.<br>ZONA ZONA INDUSTRIAL 321, PTO ORDAZ CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/marinicole.jpg';
    $archivo_json = 'formato_123_124_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'formato_123_124_data.json'; // Nombre original para Unifuerte
}

// Intentar cargar los datos guardados en el JSON si existe
$html_contenido = '';
if(file_exists($archivo_json)) {
    $json_data = json_decode(file_get_contents($archivo_json), true);
    $html_contenido = $json_data['html'] ?? '';
}

// Si el JSON está vacío o no existe, cargamos la plantilla
if (empty($html_contenido)) {
    $html_contenido = '
        <div class="hoja">
            <div class="header-top">
                <div class="logo-container">
                    <img src="' . $logo_empresa . '" alt="Logo Empresa" ondblclick="desbloquearEdicion()">
                </div>
                <div class="info-fiscal">
                    <b>' . $nombre_empresa . '</b><br>
                    RIF: ' . $rif_empresa . '<br>
                    ' . $direccion_empresa . '
                </div>
            </div>
            
            <div class="titulo">
                CAPITULO V - MODELOS DE FIANZAS<br>
                MODELO DE FIANZA FIEL CUMPLIMIENTO
            </div>
            
            <p>Yo, <span class="campo-editable">[NOMBRES Y APELLIDOS]</span>, venezolano(a), mayor de edad, portador(a) de la cédula de identidad N° <span class="campo-editable">[00000000]</span>, procediendo en este acto en mi carácter de apoderado(a) de <span class="campo-editable">[NOMBRE ASEGURADORA]</span> sociedad inscrita por ante el registro <span class="campo-editable">[REGISTRO]</span> del estado <span class="campo-editable">[ESTADO]</span>, el día <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> de <span class="campo-editable">[AÑO]</span>, bajo el N° <span class="campo-editable">[NUMERO]</span>, Tomo <span class="campo-editable">[TOMO]</span>, según consta en poder debidamente autenticado por ante la Notaría Pública <span class="campo-editable">[NOTARIA]</span> en fecha <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> de <span class="campo-editable">[AÑO]</span>, bajo el N° <span class="campo-editable">[NUMERO]</span>, Tomo <span class="campo-editable">[TOMO]</span> e inscrita en la Superintendencia de la Actividad Aseguradora bajo el N° <span class="campo-editable">[NUM_SUDEASEG]</span>, en lo adelante denominada "LA COMPAÑÍA", por el presente documento declaro que: constituyo a mi representada en fiadora solidaria y principal pagadora de <span class="campo-editable">' . $nombre_empresa . '</span>, domiciliada en <span class="campo-editable">CIUDAD GUAYANA</span> y constituida por documento inscrito en el registro <span class="campo-editable">[REGISTRO MERCANTIL]</span> del estado <span class="campo-editable">BOLÍVAR</span> en fecha <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> de <span class="campo-editable">[AÑO]</span>, bajo el N° <span class="campo-editable">[NUMERO]</span>, Tomo <span class="campo-editable">[TOMO]</span>, en lo adelante denominado "EL AFIANZADO", hasta por la cantidad de <span class="campo-editable">[MONTO EN LETRAS USD]</span> Dólares Estados Unidos de América (USD: <span class="campo-editable">[MONTO NUMEROS USD]</span>), equivalente al monto de <span class="campo-editable">[MONTO LETRAS BS]</span> Bolívares con <span class="campo-editable">[CENTIMOS]</span> céntimos (BS. <span class="campo-editable">[MONTO NUMEROS BS]</span>) de conformidad con la publicación del tipo de cambio referencial del Banco Central de Venezuela de fecha <span class="campo-editable">[FECHA BCV]</span>, a fin de dar cumplimiento a lo establecido en el artículo 130 de la Ley del Banco Central de Venezuela para garantizar a BARIVEN, S.A., filial de Petróleos de Venezuela, S.A., sociedad mercantil, domiciliada en la ciudad de Caracas, Venezuela, debidamente inscrita por ante el registro mercantil Primero de la Circunscripción Judicial del Distrito Federal y estado Miranda, en fecha 23 de diciembre de 1975, bajo el N° 31, Tomo 59-A Segundo, cuya última modificación aparece inscrita en la citada oficina de registro, en fecha 15 de febrero de 2007, bajo el N° 52, Tomo 19-A-PRO, en lo sucesivo denominado "EL ACREEDOR", el fiel, cabal y oportuno cumplimiento por parte de "EL AFIANZADO" de todas y cada una de las obligaciones que resulten a su cargo y a favor de "el acreedor", según contrato N° <span class="campo-editable">[NUMERO CONTRATO]</span>, el cual tiene por objeto "<span class="campo-editable">ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN</span>", en adelante "EL CONTRATO". La presente fianza comenzará a regir a partir de la firma del contrato y tendrá validez hasta que el órgano o ente contratante dicte el acto administrativo de liberación de las fianzas, y una vez que se efectúe la recepción definitiva del objeto de la contratación, se otorgue el finiquito contable, la evaluación de desempeño, se certifique el cumplimiento del compromiso de responsabilidad social cuando aplique, o cualquier otra obligación que a "EL AFIANZADO" imponga la legislación que regula la materia de contrataciones públicas o el contrato. Queda expresamente convenido que el presente contrato de fianza no puede ser cancelado, ni efectuarse cambio alguno en él, sin dar previo aviso por escrito al "ACREEDOR" por lo menos con sesenta (60) días de anticipación; y a tal efecto, deberá obtenerse certificación escrita de la fecha de recibo por parte del "ACREEDOR", contándose el precitado lapso a partir de dicha fecha. En caso de ejecución de la presente fianza, la misma será pagada en Dólares Americanos (USD), sin ningún tipo de rebajas ni deducciones. La no cancelación de la prima por parte de "LA AFIANZADA", no será causal para la anulación de manera unilateral por parte de la compañía. Esta fianza se regirá por los términos y condiciones establecidos en el mencionado contrato de fianza de fiel cumplimiento, por el Decreto Con Rango, Valor y Fuerza de Ley de la Actividad Aseguradora publicado en la Gaceta Oficial de la República Bolivariana de Venezuela N° 6.211 Extraordinario de fecha 30 de diciembre de 2015, por el Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas publicado en la Gaceta Oficial de la República Bolivariana De Venezuela N° 6.145 Extraordinario de fecha 19 de noviembre de 2014; y por su Reglamento, publicado en la Gaceta Oficial de la República Bolivariana de Venezuela N° 39.181 de fecha 19 de mayo de 2009, de conformidad con los términos y condiciones particulares aquí descritos y por las condiciones generales anexa. "LA COMPAÑÍA" renuncia expresamente a los beneficios acordados por los artículos 1833, 1834 y 1836 del Código Civil vigente. Se fija como domicilio especial para todos los efectos de este contrato, la ciudad de Caracas, a la Jurisdicción de cuyos tribunales declaran las partes someterse, con exclusión de cualquier otro.</p>
        </div>

        <div class="hoja">
            <div class="header-top">
                <div class="logo-container">
                    <img src="' . $logo_empresa . '" alt="Logo Empresa" ondblclick="desbloquearEdicion()">
                </div>
                <div class="info-fiscal">
                    <b>' . $nombre_empresa . '</b><br>
                    RIF: ' . $rif_empresa . '
                </div>
            </div>
            
            <div class="titulo">
                MODELO DE FIANZA DE ANTICIPO
            </div>
            
            <p>Yo, <span class="campo-editable">[NOMBRE APODERADO]</span>, venezolano, mayor de edad, domiciliado en <span class="campo-editable">[DOMICILIO]</span> y titular de la cédula de identidad N° <span class="campo-editable">[00000000]</span>, procediendo en este acto en mi carácter de Gerente de la Sucursal <span class="campo-editable">[NOMBRE SUCURSAL]</span> de entidad <span class="campo-editable">[NOMBRE ASEGURADORA]</span>, sociedad mercantil inscrita bajo el N° <span class="campo-editable">[NUMERO]</span> en el libro de registro de empresas de seguros llevado por la Superintendencia de la Actividad Aseguradora del Ministerio de Finanzas, constituida según documento originalmente inscrito en el registro mercantil de la Circunscripción Judicial del Distrito <span class="campo-editable">[DISTRITO]</span> del estado <span class="campo-editable">[ESTADO]</span>, bajo el N° <span class="campo-editable">[NUMERO]</span>, Tomo <span class="campo-editable">[TOMO]</span>, el <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> de <span class="campo-editable">[AÑO]</span>, con posteriores modificaciones en su denominación social, la primera inscrita por ante la citada oficina de registro mercantil, de fecha <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> de <span class="campo-editable">[AÑO]</span>, bajo el N° <span class="campo-editable">[NUMERO]</span>, Tomo <span class="campo-editable">[TOMO]</span> y la segunda por ante el registro mercantil de la Circunscripción Judicial del Distrito <span class="campo-editable">[DISTRITO]</span>, el <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> de <span class="campo-editable">[AÑO]</span>, bajo el N° <span class="campo-editable">[NUMERO]</span>, Tomo <span class="campo-editable">[TOMO]</span>. Suficientemente facultado para este acto según poder autenticado por ante la Notaría Pública <span class="campo-editable">[NOTARIA]</span> del Municipio <span class="campo-editable">[MUNICIPIO]</span>, bajo el N° <span class="campo-editable">[NUMERO]</span>, Tomo <span class="campo-editable">[TOMO]</span> de fecha <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> del 20<span class="campo-editable">[AÑO]</span>, y por lo resuelto en la reunión de la Junta Directiva del <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> del 20<span class="campo-editable">[AÑO]</span>, según acta N° <span class="campo-editable">[NUMERO ACTA]</span>, en lo adelante denominada "la compañía", DECLARO: constituyo a mi representada en fiadora solidaria y principal pagadora de la empresa mercantil: <span class="campo-editable">' . $nombre_empresa . '</span>, inscrita en el Juzgado <span class="campo-editable">[JUZGADO]</span> de <span class="campo-editable">[INSTANCIA]</span> Instancia en lo Civil y Mercantil de la Circunscripción Judicial del estado <span class="campo-editable">BOLÍVAR</span>, en fecha <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> de <span class="campo-editable">[AÑO]</span>, bajo el N° <span class="campo-editable">[NUMERO]</span>, folio <span class="campo-editable">[FOLIO]</span> del libro <span class="campo-editable">[LIBRO]</span>, representada en este acto por el ciudadano <span class="campo-editable">[NOMBRE REPRESENTANTE]</span>, titular de la cédula de identidad N° <span class="campo-editable">[CEDULA]</span>, en su carácter de <span class="campo-editable">[CARGO]</span>, según se evidencia en acta de Asamblea Extraordinaria de los Accionistas celebrada el día <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> del 20<span class="campo-editable">[AÑO]</span>, punto, y registrada por ante el registro mercantil Segundo de la Circunscripción Judicial del estado <span class="campo-editable">BOLÍVAR</span>, el día <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> del 20<span class="campo-editable">[AÑO]</span>, bajo el N° <span class="campo-editable">[NUMERO]</span>, Tomo <span class="campo-editable">[TOMO]</span>, <span class="campo-editable">[TRIMESTRE]</span> Trimestre, en lo sucesivo denominado "EL AFIANZADO" hasta por la cantidad de <span class="campo-editable">[MONTO LETRAS USD]</span> Dólares de los Estados Unidos de América (USD), <span class="campo-editable">[MONTO NUMEROS USD]</span> que de manera referencial y a los solos efectos de dar cumplimiento a lo establecido en el artículo 130 de la Ley del Banco Central de Venezuela, al cambio de <span class="campo-editable">[MONTO LETRAS BS]</span> Bolívares sin céntimos (BS. <span class="campo-editable">[MONTO NUMEROS BS]</span>,00), para garantizar a BARIVEN, S.A., filial de Petróleos de Venezuela, S.A., sociedad mercantil, domiciliada en la ciudad de Caracas, República Bolivariana de Venezuela, debidamente inscrita por ante el registro mercantil Primero de la Circunscripción Judicial del Distrito Federal y estado Miranda, en fecha 23 de diciembre de 1975, bajo el N° 31, tomo 59-A Segundo, cuya última modificación aparece inscrita en la citada oficina de registro en fecha 15 de Febrero de 2007, bajo el N° 52, Tomo 19-A, en lo sucesivo denominado "EL ACREEDOR", el reintegro del anticipo que por la cantidad ya mencionada hará "EL AFIANZADO", según contrato N° <span class="campo-editable">[NUMERO CONTRATO]</span>, celebrado entre ambos para la ADQUISICIÓN DE: "<span class="campo-editable">ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN</span>". La presente fianza comenzará a regir a partir de la fecha en que "el afianzado" reciba el aludido anticipo y permanecerá en vigencia hasta cuando se haya efectuado su total reintegro mediante las deducciones del porcentaje de amortización establecido en el contrato que debe efectuar "EL ACREEDOR" de cada valuación pagada a "EL AFIANZADO". El monto de esta fianza se reducirá progresivamente en la misma medida en que se fuere amortizando el anticipo y en ningún caso ese monto afianzado podrá ser inferior a la parte no amortizada del anticipo según el régimen de amortización establecido en el contrato. "LA COMPAÑÍA" renuncia expresamente a los beneficios acordados por los artículos 1833, 1834 y 1836 del Código Civil vigente. Queda entendido y convenido que las condiciones generales del contrato de Fianza de Anticipo anexas al presente documento, forman parte integrante del presente contrato. En caso de ejecución de la presente fianza, la misma será pagada en Dólares Americanos (USD), sin ningún tipo de rebajas ni deducciones. La no cancelación de la prima por parte de la afianzada, no será causal para la anulación de manera unilateral por parte de la compañía. esta fianza se regirá por los términos y condiciones establecidos en el mencionado contrato de fianza de anticipo, por el Decreto con Rango, Valor y Fuerza de Ley de la Actividad Aseguradora publicado en la Gaceta Oficial de la República Bolivariana de Venezuela N° 6.211 Extraordinario de fecha 30 de diciembre de 2015, por el Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas, publicado en la Gaceta Oficial de la República Bolivariana de Venezuela N° 6.145 Extraordinario de fecha 19 de noviembre de 2014; y por su Reglamento, publicado en la Gaceta Oficial de la República Bolivariana de Venezuela N° 39.181 de fecha 19 de mayo de 2009, de conformidad con los términos y condiciones particulares aquí descritos y por las condiciones generales anexa. Se fija como domicilio especial para todos los efectos de este contrato, la ciudad de Caracas, a la jurisdicción de cuyos tribunales declaran las partes someterse con exclusión de cualquier otro.</p>
        </div>
    ';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formatos 123 y 124 - PDVSA <?php echo $nombre_empresa; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        .editor-container { background-color: #e5e7eb; padding: 40px; display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
        .toolbar { position: sticky; top: 0; background: transparent; width: 100%; max-width: 850px; display: flex; justify-content: flex-end; align-items: center; z-index: 100; margin-bottom: 20px; pointer-events: none;}
        
        /* LETRA A 10pt Y MÁRGENES MÍNIMOS PARA FORZARLA EN LA HOJA */
        .hoja { background: white; width: 8.5in; height: 11in; padding: 0.3in 0.4in; box-sizing: border-box; box-shadow: 0 5px 10px rgba(0,0,0,0.2); margin: 0 auto 30px auto; font-family: 'Arial', sans-serif; font-size: 10pt; line-height: 1.15; outline: none; position: relative; page-break-after: always; text-align: justify; overflow: hidden;}
        .hoja:last-child { page-break-after: auto; }
        .hoja p { text-align: justify; margin-bottom: 6px; }

        /* ENCABEZADO COMPACTO */
        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 4px; margin-bottom: 8px; }
        .logo-container img { width: 110px; cursor: default; }
        .info-fiscal { text-align: right; font-size: 8pt; line-height: 1.15; max-width: 65%; word-wrap: break-word;}
        
        /* TÍTULOS */
        .titulo { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 8px; text-transform: uppercase; }
        
        .campo-editable { background-color: #fef08a; border: 1px dashed #ca8a04; display: inline-block; padding: 0px 2px; border-radius: 2px; color: #111; }

        /* BOTONES FLOTANTES */
        .btn-guardar, .btn-pdf { pointer-events: auto; }
        .btn-guardar { background-color: var(--color-green); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: none; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-guardar:hover { opacity: 0.9; }
        .btn-pdf { background-color: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-pdf:hover { opacity: 0.9; }

        @page { size: letter; margin: 0; }
        @media print {
            html, body, main, .main-content, .editor-container, #documento-editable { display: block !important; height: auto !important; overflow: visible !important; position: static !important; margin: 0 !important; padding: 0 !important; background: white !important;}
            .toolbar, .sidebar, .header { display: none !important; }
            .hoja { box-shadow: none !important; margin: 0 !important; padding: 0.3in 0.4in !important; width: 8.5in !important; height: 11in !important; page-break-after: always !important; border: none !important; box-sizing: border-box !important;}
            .campo-editable { background: transparent !important; border: none !important; padding: 0 !important; color: black !important;}
            ::-webkit-scrollbar { display: none !important; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="ph-bold ph-factory"></i></div> 
            <?php echo ($empresa === 'marinicol') ? 'Marinicol' : 'Unifuerte'; ?>
        </div>
        <nav class="nav-menu">
            <a href="../../../portal_ventas/index.php?modulo=licitaciones" class="nav-item">
                <i class="ph ph-arrow-left"></i> Volver a Menú
            </a>
            <a href="index.php" class="nav-item" style="margin-top: 15px;">
                <i class="ph ph-arrows-left-right"></i> Cambiar Empresa
            </a>
        </nav>
    </aside>

    <main class="main-content" style="padding: 0;">
        <div class="editor-container">
            <div class="toolbar">
                <div style="display:flex; gap: 10px;">
                    <button id="btn-guardar" class="btn-guardar" onclick="guardarCambios()"><i class="ph-bold ph-floppy-disk"></i> Guardar Cambios</button>
                    <button class="btn btn-pdf" onclick="window.print()"><i class="ph-bold ph-file-pdf"></i> Imprimir / Guardar PDF</button>
                </div>
            </div>

            <div id="documento-editable" contenteditable="false">
                <?php echo $html_contenido; ?>
            </div>
        </div>
    </main>

    <script>
        function desbloquearEdicion() {
            Swal.fire({
                title: 'Desbloquear Edición',
                html: `
                    <div style="position: relative; width: 100%; max-width: 260px; margin: 15px auto;">
                        <input type="password" id="swal-input-password" class="swal2-input" placeholder="Contraseña..." style="width: 100%; margin: 0; padding-right: 40px; box-sizing: border-box;">
                        <i class="ph-bold ph-eye" id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 22px; color: #6b7280;"></i>
                    </div>
                `,
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Desbloquear',
                confirmButtonColor: '#059669',
                didOpen: () => {
                    const toggleBtn = document.getElementById('togglePassword');
                    const passInput = document.getElementById('swal-input-password');
                    toggleBtn.addEventListener('click', function() {
                        const isPassword = passInput.getAttribute('type') === 'password';
                        passInput.setAttribute('type', isPassword ? 'text' : 'password');
                        this.classList.toggle('ph-eye');
                        this.classList.toggle('ph-eye-closed');
                    });
                },
                preConfirm: () => {
                    return document.getElementById('swal-input-password').value;
                }
            }).then((result) => {
                if (result.value === 'clarita') {
                    document.getElementById('documento-editable').contentEditable = "true";
                    document.getElementById('btn-guardar').style.display = "inline-flex";
                    Swal.fire({
                        icon: 'success',
                        title: 'Modo edición activado',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Contraseña incorrecta',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });
        }

        function guardarCambios() {
            let contenido = document.getElementById('documento-editable').innerHTML;
            let archivoDestino = '<?php echo $archivo_json; ?>';

            fetch('guardar_datos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ html: contenido, archivo: archivoDestino })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cambios guardados',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: data.message
                    });
                }
            });
        }
    </script>
</body>
</html>