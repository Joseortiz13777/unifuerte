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
    $archivo_json = 'formato_43_46_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'formato_43_46_data.json'; // Nombre para aislar los datos
}

// Intentar cargar los datos guardados en el JSON si existe
$html_contenido = '';
if(file_exists($archivo_json)) {
    $json_data = json_decode(file_get_contents($archivo_json), true);
    $html_contenido = $json_data['html'] ?? '';
}

// Si el JSON está vacío o no existe, cargamos la plantilla perfectamente centrada
if (empty($html_contenido)) {
    
    $encabezado = '
        <div class="header-top">
            <div class="logo-container">
                <img src="' . $logo_empresa . '" alt="Logo Empresa" ondblclick="desbloquearEdicion()">
            </div>
            <div class="info-fiscal">
                <b>' . $nombre_empresa . '</b><br>
                RIF: ' . $rif_empresa . '<br>
                ' . $direccion_empresa . '
            </div>
        </div>';

    $html_contenido = '
        <!-- PÁGINA 1 -->
        <div class="hoja">
            ' . $encabezado . '
            
            <div class="titulo">
                FORMATO I.1 - MANIFESTACIÓN DE VOLUNTAD PARA PARTICIPAR<br><br>
                PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable">0394</span><br>
                REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES<br>ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
            </div>
            
            <p>Yo, <span class="campo-editable">[NOMBRES Y APELLIDOS]</span>, cédula de identidad N° V-<span class="campo-editable">[00000000]</span>, de nacionalidad <span class="campo-editable">[NACIONALIDAD]</span>, estado civil <span class="campo-editable">[ESTADO CIVIL]</span>, profesión (u ocupación) <span class="campo-editable">[PROFESIÓN]</span>, domiciliado en (dirección completa, Ciudad, Municipio y estado) <span class="campo-editable">[DIRECCIÓN COMPLETA]</span>, actuando en este acto en mi carácter de (indicar cargo) <span class="campo-editable">[CARGO]</span>, de la Empresa (indicar datos completos de la empresa OFERENTE: Registro Mercantil) <span class="campo-editable">' . $nombre_empresa . '</span> ("el oferente") debidamente facultado para este acto por (indicar las cláusulas del Documento Constitutivo, Acta de Asamblea o Documento Poder que lo faculta para tal tipo de representación) <span class="campo-editable">[CLÁUSULAS/DOCUMENTO]</span>, en relación con el Procedimiento de Contratación "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN" (nombre del procedimiento)" N° A-182-26-<span class="campo-editable">0394</span>, referente a la (adquisición de bienes, prestación de servicio o ejecución de obra) <span class="campo-editable">adquisición de bienes</span>, llevado a efecto por EL ENTE CONTRATANTE., por el presente documento <b>Manifiesto la voluntad de participar</b> en el procedimiento de contratación N° A-182-26-<span class="campo-editable">0394</span> cuyo objeto es "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN".</p>
            
            <p>Además, declaro bajo fe de juramento, la veracidad y legalidad de toda la información suministrada en los documentos consignados. Así mismo, autorizo a EL ENTE CONTRATANTE, a realizar prácticas de auditoría para comprobar el contenido de cualquier información durante cualquiera de las fases del proceso de contratación, sujeto a las sanciones previstas en el Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas (DRVFLCP) y las establecidas en el Pliego de Condiciones.</p>
            
            <p>A los efectos de cualquier notificación pertinente al procedimiento, la dirección de mi representada es <span class="campo-editable">[DIRECCIÓN DE NOTIFICACIÓN]</span>, Correo Electrónico: <span class="campo-editable">[CORREO ELECTRÓNICO]</span>.</p>
            
            <p>En (ciudad) <span class="campo-editable">[CIUDAD]</span>, a los <span class="campo-editable">[DÍA]</span> días del mes de <span class="campo-editable">[MES]</span> de 20<span class="campo-editable">[AÑO]</span>.</p>
            
            <div class="firmas">
                <div class="linea-firma"></div>
                <b>FIRMA DEL REPRESENTANTE</b><br>
                C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
                SELLO DE LA CONTRATISTA
            </div>
        </div>

        <!-- PÁGINA 2 -->
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
                FORMATO I.2. - MODELO DE FIANZA. GARANTÍA PARA EL MANTENIMIENTO DE LA OFERTA<br><br>
                PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable">0394</span><br>
                REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES<br>ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
            </div>
            
            <p>Yo, <span class="campo-editable">[NOMBRE DEL APODERADO]</span>, venezolano(a), mayor de edad, portador(a) de la cédula de identidad N° <span class="campo-editable">[CÉDULA]</span>, procediendo en este acto en mi carácter de Apoderado(a) de <span class="campo-editable">[NOMBRE ASEGURADORA O BANCO]</span> sociedad inscrita por ante el registro <span class="campo-editable">[REGISTRO]</span> del Estado <span class="campo-editable">[ESTADO]</span>, el día <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> de <span class="campo-editable">[AÑO]</span>, bajo el N° <span class="campo-editable">[NRO]</span>, Tomo <span class="campo-editable">[TOMO]</span>, según consta en poder debidamente autenticado por ante la Notaría <span class="campo-editable">[NOTARÍA]</span> en fecha <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> de <span class="campo-editable">[AÑO]</span>, bajo el N° <span class="campo-editable">[NRO]</span>, Tomo <span class="campo-editable">[TOMO]</span> e inscrita en la Superintendencia de Seguros bajo el N° <span class="campo-editable">[NRO SEGURO]</span>, en lo adelante denominada "EL ENTE CONTRATANTE", por el presente documento declaro que: Constituyo a mi representada en fiadora solidaria y principal pagadora de <span class="campo-editable">' . $nombre_empresa . '</span>, domiciliada en <span class="campo-editable">CIUDAD GUAYANA</span> y constituida por documento inscrito en el Registro <span class="campo-editable">[REGISTRO MERCANTIL]</span> del Estado <span class="campo-editable">BOLÍVAR</span>, en fecha <span class="campo-editable">[DÍA]</span> de <span class="campo-editable">[MES]</span> de <span class="campo-editable">[AÑO]</span>, bajo el N° <span class="campo-editable">[NRO]</span>, Tomo <span class="campo-editable">[TOMO]</span>, en lo adelante denominado "EL AFIANZADO", hasta por la cantidad de bolívares (Bs.) <span class="campo-editable">[MONTO EN LETRAS]</span> ( <span class="campo-editable">[MONTO EN NÚMEROS]</span> ), para garantizar a BARIVEN, S.A., Filial de Petróleos de Venezuela, S.A., Sociedad Mercantil, domiciliada en la ciudad de Caracas, República Bolivariana de Venezuela, debidamente inscrita por ante el Registro Mercantil Primero de la Circunscripción Judicial del Distrito Federal y Estado Miranda, en fecha 23 de diciembre de 1975, bajo el N° 31, Tomo 59-A Segundo, cuya última modificación aparece inscrita en la citada oficina de registro en fecha 15 de febrero de 2007, bajo el N° 52, Tomo 19-A, en lo sucesivo denominado "EL ACREEDOR", el cumplimiento del compromiso asumido por "EL AFIANZADO" para con "EL ACREEDOR" de mantener invariable (salvo acuerdo en contrario entre ambos) los términos de la oferta que aquél presentare en caso de participar en el proceso de contratación No. <span class="campo-editable">A-182-26-0394</span>, a que fuera invitado por "EL ACREEDOR" para objeto <span class="campo-editable">[ADQUISICIÓN DE CHAQUETAS Y GORRAS]</span>, así como también del cumplimiento de su obligación de suscribir el respectivo contrato con el "ACREEDOR" en la oportunidad que éste determine en el supuesto de que "EL AFIANZADO" resultare favorecido con el otorgamiento de la adjudicación. La indemnización de los daños y perjuicios que se le causaren al "ACREEDOR" por el incumplimiento de las obligaciones afianzadas, será pagada por "EL ENTE CONTRATANTE" al "ACREEDOR", sin rebajas ni deducciones de ningún género, a más tardar dentro de los treinta (30) días siguientes a la fecha en que "EL ACREEDOR" presente una declaración escrita y firmada por un representante autorizado, indicando que "el afianzado" ha incurrido en incumplimiento de los compromisos de mantener la oferta inalterada y/o de celebrar el contrato en caso de haber resultado favorecido con el otorgamiento de la adjudicación. "EL ENTE CONTRATANTE" renuncia expresamente a los beneficios acordados por los artículos 1833, 1834 y 1836 del código civil vigente. La presente fianza comenzará a regir a partir de la apertura de la oferta por parte del "ACREEDOR" y deberá tener una validez de <span class="campo-editable">[NOVENTA]</span> ( <span class="campo-editable">90</span> ) días <span class="campo-editable">[HÁBILES/CONTINUOS]</span>. Queda expresamente convenido que el presente contrato de fianza no puede ser cancelado, ni efectuarse cambio alguno en él, sin dar previo aviso por escrito al "ACREEDOR" por lo menos con Noventa (90) días de anticipación; y a tal efecto, deberá obtenerse certificación escrita de la fecha de recibo por parte del "ACREEDOR", contándose el precitado lapso a partir de dicha fecha. "EL ENTE CONTRATANTE", se obliga a mantener confidencialidad hasta tanto tenga lugar la apertura de las ofertas por "EL ACREEDOR", respecto del presente instrumento y cualquier información con él relacionada, frente a terceros distintos del "AFIANZADO", asumiendo la responsabilidad de indemnizar los daños que la violación de este compromiso pueda causarle al "AFIANZADO" y al "ACREEDOR". La presente fianza no se regirá por el Decreto N° 1417, de fecha 31 de julio de 1996, según publicación de Gaceta Oficial de la República Bolivariana de Venezuela, de fecha 16 de septiembre de 1996, N° 5.096 extraordinario o cualquier otro que lo sustituya. La indemnización a que haya lugar será pagada por "EL ENTE CONTRATANTE", a más tardar dentro de los treinta (30) días siguientes a la constatación definitiva del hecho que da lugar a la indemnización y del monto correspondiente. "EL ACREEDOR" deberá notificar a "EL ENTE CONTRATANTE", por escrito, la ocurrencia de cualquier hecho o circunstancia que pueda dar origen a reclamo amparado por esta</p>
        </div>

        <!-- PÁGINA 3 -->
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
            
            <p>fianza, dentro de los Noventa (90) días continuos siguientes al conocimiento de dicha ocurrencia. Se fija como domicilio especial para todos los efectos de este contrato, la ciudad de Caracas, a la jurisdicción de cuyos tribunales declaran las partes someterse, con exclusión de cualquier otro.</p>
        </div>

        <!-- PÁGINA 4 -->
        <div class="hoja">
            ' . $encabezado . '
            
            <div class="titulo">
                FORMATO I.3. - CARTA DE AUTORIZACIÓN PARA DESCONTAR EL APORTE PARA EL FONDO SOCIAL<br><br>
                PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable">0394</span><br>
                REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES<br>ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
            </div>
            
            <p>Yo, <span class="campo-editable">[NOMBRES Y APELLIDOS]</span>, cédula de identidad N° V-<span class="campo-editable">[00000000]</span>, de nacionalidad <span class="campo-editable">[NACIONALIDAD]</span>, estado civil <span class="campo-editable">[ESTADO CIVIL]</span>, profesión (u ocupación) <span class="campo-editable">[PROFESIÓN]</span>, domiciliado en (dirección completa, Ciudad, Municipio y estado) <span class="campo-editable">[DIRECCIÓN COMPLETA]</span>, actuando en este acto en mi carácter de (indicar cargo) <span class="campo-editable">[CARGO]</span>, de la Empresa (indicar datos completos de la empresa OFERENTE: Registro Mercantil) <span class="campo-editable">' . $nombre_empresa . '</span> ("el oferente") debidamente facultado para este acto por (indicar las cláusulas del Documento Constitutivo, Acta de Asamblea o Documento Poder que lo faculta para tal tipo de representación) <span class="campo-editable">[CLÁUSULAS/DOCUMENTO]</span>, en relación con el Procedimiento de Contratación "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN" (nombre del procedimiento)" N° A-182-26-<span class="campo-editable">0394</span>, referente a la (adquisición de bienes, prestación de servicio o ejecución de obra) <span class="campo-editable">adquisición de bienes</span>, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento y en aras de llevar a cabo el apoyo, incentivo y ejecución de los programas sociales que tengan por finalidad el beneficio de las comunidades, autorizo a (indicar empresa o filial) <span class="campo-editable">BARIVEN S.A.</span>, a descontar del pago de las valuaciones correspondientes a la ejecución del contrato, el porcentaje del tres (3%) correspondiente a mi contribución al fondo social como parte del programa de empresas de producción social establecido por (indicar empresa o filial) <span class="campo-editable">BARIVEN S.A.</span> y el cual forma parte de este procedimiento de selección de contratistas.</p>
            
            <p>En (ciudad) <span class="campo-editable">[CIUDAD]</span>, a los <span class="campo-editable">[DÍA]</span> días del mes de <span class="campo-editable">[MES]</span> de 20<span class="campo-editable">[AÑO]</span>.</p>
            
            <div class="firmas">
                <div class="linea-firma"></div>
                <b>FIRMA DEL REPRESENTANTE</b><br>
                C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
                SELLO DE LA CONTRATISTA
            </div>
        </div>
    ';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Formatos 43-46 - <?php echo $nombre_empresa; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        .editor-container { background-color: #e5e7eb; padding: 40px; display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
        .toolbar { position: sticky; top: 0; background: transparent; width: 100%; max-width: 850px; display: flex; justify-content: flex-end; align-items: center; z-index: 100; margin-bottom: 20px; pointer-events: none;}
        
        /* HOJA TIPO CARTA ESTRICTA */
        .hoja { background: white; width: 8.5in; min-height: 11in; padding: 0.8in; box-sizing: border-box; box-shadow: 0 5px 10px rgba(0,0,0,0.2); margin: 0 auto 30px auto; font-family: 'Arial', sans-serif; font-size: 11pt; line-height: 1.5; outline: none; position: relative; page-break-after: always; text-align: justify; }
        .hoja p { text-align: justify; margin-bottom: 10px; }

        /* ENCABEZADO CON FLEXBOX ALINEADO AL FONDO (bottom) */
        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 30px; }
        .logo-container img { width: 150px; cursor: default; } /* Cursor default para no delatar */
        .info-fiscal { text-align: right; font-size: 8pt; line-height: 1.3; max-width: 65%; word-wrap: break-word;}
        
        /* TÍTULOS PERFECTAMENTE CENTRADOS */
        .titulo { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 20px; text-transform: uppercase; }
        
        .campo-editable { background-color: #fef08a; border: 1px dashed #ca8a04; display: inline-block; padding: 0px 4px; border-radius: 3px; color: #111; }
        
        .firmas { margin-top: 60px; text-align: left; }
        .linea-firma { width: 300px; border-bottom: 1px solid #000; margin-bottom: 5px; }

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
            .hoja { box-shadow: none !important; margin: 0 !important; padding: 0.8in !important; width: 8.5in !important; height: 11in !important; page-break-after: always !important; border: none !important; box-sizing: border-box !important;}
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