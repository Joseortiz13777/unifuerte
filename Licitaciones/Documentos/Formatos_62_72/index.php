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
    $archivo_json = 'formato_62_72_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    // IMPORTANTE: Cambiamos a v4 para forzar al sistema a centrar las tablas y blindar los márgenes (Unifuerte)
    $archivo_json = 'documento_data_v4.json'; 
}

// Intentar cargar los datos guardados en el JSON si existe
$html_contenido = '';
if(file_exists($archivo_json)) {
    $json_data = json_decode(file_get_contents($archivo_json), true);
    $html_contenido = $json_data['html'] ?? '';
}

// Si el JSON está vacío o no existe, generamos la plantilla
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

    $comun_inicio = '<p>Yo, <span class="campo-editable">[NOMBRES Y APELLIDOS]</span>, cédula de identidad N° V-<span class="campo-editable">[00000000]</span>, de nacionalidad <span class="campo-editable">[NACIONALIDAD]</span>, estado civil <span class="campo-editable">[ESTADO CIVIL]</span>, profesión (u ocupación) <span class="campo-editable">[PROFESIÓN]</span>, domiciliado en (dirección completa, ciudad, municipio y estado) <span class="campo-editable">[DIRECCIÓN COMPLETA]</span>, actuando en este acto en mi carácter de (indicar cargo) <span class="campo-editable">[CARGO]</span>, de la empresa (indicar datos completos de la empresa oferente: registro mercantil) <span class="campo-editable">' . $nombre_empresa . '</span> ("el oferente") debidamente facultado para este acto por (indicar las cláusulas del documento constitutivo, acta de asamblea o documento poder que lo faculta para tal tipo de representación) <span class="campo-editable">[CLÁUSULAS/DOCUMENTO]</span>, en relación con el procedimiento de contratación <b>"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</b> N° A-182-26-<span class="campo-editable">0394</span>, referente a la (adquisición de bienes, prestación de servicio o ejecución de obra) <span class="campo-editable">adquisición de bienes</span>, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento ';

    $comun_fin = '
        <p>En (ciudad) <span class="campo-editable">[CIUDAD]</span>, a los <span class="campo-editable">[DÍA]</span> días del mes de <span class="campo-editable">[MES]</span> de 20<span class="campo-editable">[AÑO]</span>.</p>
        <div class="firmas">
            <div class="linea-firma"></div>
            <b>FIRMA DEL REPRESENTANTE</b><br>
            C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
            SELLO DE LA CONTRATISTA
        </div>';

    $td_editable = '<td><div class="campo-editable-tabla"><br></div></td>';

    $formatos = [
        [
            'titulo' => 'FORMATO I.9. - DECLARACIÓN JURADA SOBRE LA CONFORMACIÓN Y ORGANIZACIÓN DE LA EMPRESA',
            'contenido' => $comun_inicio . '<b>Declaro bajo fe de Juramento</b> que mi representada, no cuenta dentro de su conformación y organización, con personas naturales que participen como socios, miembros o administradores de alguna empresa, sociedad o agrupación que se encuentre inhabilitada conforme al Decreto con Rango Valor y Fuerza de Ley de Contrataciones Públicas.</p>'
        ],
        [
            'titulo' => 'FORMATO I.10. - DECLARACIÓN JURADA DE NO ESTAR INVOLUCRADA EN LITIGIOS Y RECLAMOS JUDICIALES',
            'contenido' => $comun_inicio . '<b>Declaro bajo fe de Juramento:</b> Que mi Representada, no se encuentra inmersa en demandas, reclamos o juicios legales o extrajudiciales ante los organismos judiciales nacionales y extranjero, que pudieran comprometer el patrimonio y/o control parcial o total de posesión de mi representada.</p>'
        ],
        [
            'titulo' => 'FORMATO I.11. - DECLARACIÓN BAJO FE DE JURAMENTO DE CONOCIMIENTO DEL SITIO DONDE SERÁ ENTREGADO EL BIEN, EJECUTADA LA OBRA O PRESTADO EL SERVICIO',
            'contenido' => $comun_inicio . '<b>Declaro bajo fe de Juramento</b> y sujeto a las sanciones previstas en el Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas (DRVFLCP), que la empresa la cual represento conoce el lugar y condiciones del mismo, donde será entregado el Bien, Ejecutada la Obra o Prestado el Servicio.</p>'
        ],
        [
            'titulo' => 'FORMATO I.12. - DECLARACIÓN JURADA DE NO INCURRIR EN PROCESO DE DESCAPITALIZACION',
            'contenido' => $comun_inicio . '<b>Declaro bajo fe de Juramento</b> que mi Representada, no se encuentra descapitalizada de acuerdo a lo establecido en el Artículo 264 del Código de Comercio, y nos sometemos a cualquier auditoría que considere el órgano y ente contratante.</p>'
        ],
        [
            'titulo' => 'FORMATO I.13. - DECLARACIÓN BAJO FE DE JURAMENTO SOBRE LA DISPONIBILIDAD DE SERVICIO DE ATENCIÓN AL CLIENTE',
            'contenido' => $comun_inicio . '<b>Declaro bajo fe de Juramento</b> y sujeto a las sanciones previstas en el Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas (DRVFLCP) que la empresa (indicar nombre): <span class="campo-editable">' . $nombre_empresa . '</span></p>
            <ul style="margin:5px 0;"><li>Posee ATENCIÓN AL CLIENTE, TÉCNICO - LOGÍSTICO para los bienes ofertados en:</li></ul>
            <table class="tabla-pdf">
                <colgroup><col style="width: 25%;"><col style="width: 50%;"><col style="width: 25%;"></colgroup>
                <tr><th>ESTADO</th><th>DIRECCIÓN</th><th>TELÉFONOS</th></tr>
                <tr>' . str_repeat($td_editable, 3) . '</tr>
                <tr>' . str_repeat($td_editable, 3) . '</tr>
            </table>'
        ],
        [
            'titulo' => 'FORMATO I.14. - DECLARACIÓN JURADA DE ACEPTAR LAS NOTIFICACIONES ELECTRÓNICAS',
            'contenido' => $comun_inicio . '<b>Autorizo</b> que todas las notificaciones derivadas de mi participación en el presente proceso de contratación, sean enviadas al correo electrónico <span class="campo-editable">[CORREO ELECTRÓNICO]</span>.</p>
            <p>Asimismo, me comprometo a enviar acuse de recibo de la recepción de las notificaciones por esa misma vía.</p>'
        ],
        [
            'titulo' => 'FORMATO I.15. - DECLARACIÓN JURADA DE LA EXPERIENCIA DE LA EMPRESA',
            'contenido' => $comun_inicio . '<b>Declaro Bajo Fe de Juramento</b> y sujeto a las sanciones previstas en el DRVFLCP, que la Empresa a la cual represento ha ejecutado Contratos con alcances similares dentro o fuera de LA COMPAÑÍA. A continuación, se indica la Lista de los trabajos realizados:</p>
            <table class="tabla-pdf tabla-pequena">
                <colgroup>
                    <col style="width: 11%;"><col style="width: 14%;"><col style="width: 15%;"><col style="width: 9%;">
                    <col style="width: 7%;"><col style="width: 7%;"><col style="width: 10%;"><col style="width: 10%;">
                    <col style="width: 8%;"><col style="width: 9%;">
                </colgroup>
                <tr>
                    <th rowspan="2">ENTE<br>CONTRAT.</th>
                    <th rowspan="2">DIRECCIÓN<br>DEL ENTE</th>
                    <th rowspan="2">DESCRIPCIÓN<br>ACTIVIDAD</th>
                    <th rowspan="2">TIEMPO<br>REQ.<br>EJEC.</th>
                    <th colspan="2">EJECUCIÓN<br>CONTRATO</th>
                    <th rowspan="2">MONTO<br>CONTRATO</th>
                    <th colspan="3">PERSONA CONTACTO</th>
                </tr>
                <tr>
                    <th>INICIO</th>
                    <th>FIN</th>
                    <th>NOMBRE</th>
                    <th>TEL.</th>
                    <th>E-MAIL</th>
                </tr>
                <tr>' . str_repeat($td_editable, 10) . '</tr>
                <tr>' . str_repeat($td_editable, 10) . '</tr>
                <tr>' . str_repeat($td_editable, 10) . '</tr>
            </table>
            <p style="font-size: 8pt; line-height: 1.2;"><b>NOTA: EL ENTE CONTRATANTE</b> se reserva el derecho de solicitar las copias de cartas de referencia emitidas por la Empresa a la cual se le prestó la obra. Igualmente, se reserva el derecho de solicitar copias de las órdenes de compra y/o notas de entrega a la empresa para verificar la veracidad de la información.</p>'
        ],
        [
            'titulo' => 'FORMATO I.16. - DECLARACIÓN JURADA DE LA CAPACIDAD TÉCNICA DEL PERSONAL DE LA EMPRESA',
            'contenido' => $comun_inicio . '<b>Declaro bajo fe de Juramento</b> y sujeto a las sanciones previstas en el Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas (DRVFLCP), que la empresa a la cual represento se compromete a disponer del siguiente personal clave que laborará en la ejecución de la obra y/o prestación del servicio y que posee experiencia comprobable en trabajos similares al servicio u obra.</p>
            <p style="margin:5px 0;">LISTA DEL PERSONAL</p>
            <table class="tabla-pdf">
                <colgroup><col style="width: 15%;"><col style="width: 35%;"><col style="width: 35%;"><col style="width: 15%;"></colgroup>
                <tr><th>CANTIDAD</th><th>PERFIL REQUERIDO</th><th>CARGO A OCUPAR</th><th>EXPERIENCIA<br>(AÑOS)</th></tr>
                <tr>' . str_repeat($td_editable, 4) . '</tr>
                <tr>' . str_repeat($td_editable, 4) . '</tr>
            </table>'
        ],
        [
            'titulo' => 'FORMATO I.17. - DECLARACIÓN JURADA DE LOS EQUIPOS DISPONIBLES',
            'contenido' => $comun_inicio . '<b>Declaro bajo fe de Juramento</b> y sujeto a las sanciones previstas en el DRVFLCP y las establecidas en el Pliego de Condiciones, referente a la <b>"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</b>, que la Empresa a la cual represento se compromete a disponer de los siguientes equipos requeridos para la ejecución de la obra:</p>
            <table class="tabla-pdf">
                <colgroup><col style="width: 10%;"><col style="width: 45%;"><col style="width: 20%;"><col style="width: 25%;"></colgroup>
                <tr><th>CANT.</th><th>DESCRIPCIÓN</th><th>CAPACIDAD<br>REQUERIDA</th><th>CONDICIÓN<br>(PROPIO / ALQ.)</th></tr>
                <tr>' . str_repeat($td_editable, 4) . '</tr>
                <tr>' . str_repeat($td_editable, 4) . '</tr>
            </table>
            <p>Autorizo de manera expresa e irrevocable a <b>EL ENTE CONTRATANTE</b> para que esta información sea investigada y verificada en cualquier momento del procedimiento de contratación y garantizo que los equipos estarán disponibles en el patio o instalación ubicada en <span class="campo-editable">[UBICACIÓN DEL PATIO]</span> (el área debe estar en el <span class="campo-editable">[ÁREA/ESTADO]</span>), en un plazo no mayor de 48 horas después de recibir su notificación.</p>'
        ],
        [
            'titulo' => 'FORMATO I.18. - DECLARACIÓN JURADA DE INSTALACIÓN DE BASE OPERACIONAL',
            'contenido' => '<p>Yo, <span class="campo-editable">[NOMBRES Y APELLIDOS]</span>, cédula de identidad N° V-<span class="campo-editable">[00000000]</span>, de nacionalidad <span class="campo-editable">[NACIONALIDAD]</span>, estado civil <span class="campo-editable">[ESTADO CIVIL]</span>, profesión <span class="campo-editable">[PROFESIÓN]</span>, domiciliado en <span class="campo-editable">[DIRECCIÓN COMPLETA]</span>, actuando en este acto en mi carácter de <span class="campo-editable">[CARGO]</span>, de la empresa <span class="campo-editable">' . $nombre_empresa . '</span> debidamente facultado por <span class="campo-editable">[CLÁUSULAS/DOCUMENTO]</span>, en relación con el procedimiento de contratación <b>"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</b> N° A-182-26-<span class="campo-editable">0394</span>, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento declaro que la Empresa la cual represento instalará una base operacional, en un lapso no mayor a treinta (30) días continuos contados a partir de la fecha de la notificación de adjudicación.</p>
            <p>Autorizo de manera expresa e irrevocable a <b>EL ENTE CONTRATANTE</b>, para que esta información sea investigada y verificada en cualquier momento del procedimiento de contratación y/o durante la ejecución de la obra y en caso de que la misma sea falsa, sin perjuicio del ejercicio de las sanciones legales podrá optar entre descontar o recobrar lo pagado demás, producto de la aplicación de las medidas o rescindir el contrato otorgado, sin pago de indemnización alguna.</p>'
        ]
    ];

    foreach($formatos as $f) {
        $html_contenido .= '
        <div class="hoja">
            ' . $encabezado . '
            <div class="titulo">
                ' . $f['titulo'] . '<br><br>
                PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable">0394</span><br>
                REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES<br>ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
            </div>
            ' . $f['contenido'] . $comun_fin . '
        </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Licitaciones PDVSA - <?php echo $nombre_empresa; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        *, *:before, *:after { box-sizing: border-box !important; }

        .editor-container { background-color: #e5e7eb; padding: 40px; display: block; min-height: 100vh; }
        .toolbar { position: sticky; top: 0; background: transparent; width: 100%; max-width: 8.5in; display: flex; justify-content: flex-end; align-items: center; z-index: 100; margin: 0 auto 20px auto; pointer-events: none;}
        
        .hoja { 
            background: white; 
            width: 8.5in; 
            min-height: 11in; 
            padding: 0.8in; /* En pantalla usamos padding para ver el margen */
            margin: 0 auto 30px auto; 
            font-family: 'Arial', sans-serif; 
            font-size: 11pt; 
            line-height: 1.5; 
            outline: none; 
            position: relative; 
            page-break-after: always; 
            text-align: justify; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.2); 
            overflow: visible; 
            box-sizing: border-box;
        }
        .hoja p { text-align: justify; margin-bottom: 10px; }

        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 30px; }
        .logo-container img { width: 150px; cursor: default; } 
        .info-fiscal { text-align: right; font-size: 8pt; line-height: 1.3; max-width: 65%; word-wrap: break-word;}
        .titulo { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 20px; text-transform: uppercase; }
        
        .campo-editable { 
            background-color: #fef08a; border: 1px dashed #ca8a04; display: inline-block; padding: 0px 4px; border-radius: 3px; color: #111; min-height: 1em; word-wrap: break-word; overflow-wrap: break-word;
        }

        /* ==============================================================
           AQUÍ LA SOLUCIÓN: TABLAS PEQUEÑAS (85%) Y CENTRADAS
           ============================================================== */
        .tabla-pdf { 
            width: 85% !important; /* Las tablas normales ya no se estiran al 100% */
            max-width: 100% !important; 
            border-collapse: collapse !important; 
            table-layout: fixed !important; 
            margin: 15px auto !important; /* EL "auto" LAS CENTRA PERFECTAMENTE */
            font-size: 8pt !important; 
            box-sizing: border-box !important;
        }
        
        /* EXCEPCIÓN: La tabla I.15 (10 columnas) sí necesita todo el espacio disponible */
        .tabla-pdf.tabla-pequena {
            width: 100% !important;
        }

        .tabla-pdf th, .tabla-pdf td { 
            border: 1px solid #000 !important; 
            padding: 4px 2px !important; 
            text-align: center !important; 
            vertical-align: middle !important; 
            word-wrap: break-word !important; 
            overflow-wrap: break-word !important; 
            word-break: break-word !important; /* Mantiene los textos dentro de la celda */
            white-space: normal !important;
        }
        .tabla-pdf th { background-color: #f0f0f0 !important; font-weight: bold; font-size: 7.5pt !important; }
        
        /* Ajuste miniatura EXCLUSIVO para la tabla de 10 columnas */
        .tabla-pequena th { font-size: 5.5pt !important; padding: 2px 1px !important; }
        .tabla-pequena td { font-size: 6pt !important; padding: 2px 1px !important; }
        
        .campo-editable-tabla { 
            display: block; width: 100%; min-height: 15px; outline: none; word-wrap: break-word !important; overflow-wrap: break-word !important;
        }
        [contenteditable="true"] .campo-editable-tabla { background-color: #fef08a; border: 1px dashed #ca8a04;}

        .firmas { margin-top: 60px; text-align: left; }
        .linea-firma { width: 300px; border-bottom: 1px solid #000; margin-bottom: 5px; }

        .btn-guardar, .btn-pdf { pointer-events: auto; }
        .btn-guardar { background-color: var(--color-green); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: none; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-pdf { background-color: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-guardar:hover, .btn-pdf:hover { opacity: 0.9; }

        /* ========================================================================
           LA MAGIA DEL PDF: El navegador maneja el margen para evitar desbordes
           ======================================================================== */
        @page { 
            size: letter; 
            margin: 0.8in; /* El navegador crea una "pared" invisible a 0.8 pulgadas */
        }
        
        @media print {
            html, body, main, .main-content, .editor-container, #documento-editable { 
                display: block !important; height: auto !important; overflow: visible !important; position: static !important; margin: 0 !important; padding: 0 !important; background: white !important;
            }
            .toolbar, .sidebar, .header { display: none !important; }
            
            .hoja { 
                /* IMPORTANTÍSIMO: Quitamos el padding interno de la hoja. 
                   Al dejarle el trabajo al @page de arriba, texto y tabla comparten 
                   la misma caja y es imposible que algo se salga a la derecha. */
                padding: 0 !important; 
                margin: 0 !important; 
                width: 100% !important; 
                max-width: 100% !important;
                height: auto !important; 
                page-break-after: always !important; 
                box-shadow: none !important; 
                border: none !important; 
                box-sizing: border-box !important;
                overflow: visible !important;
            }
            .campo-editable { background: transparent !important; border: none !important; padding: 0 !important; color: black !important;}
            [contenteditable="true"] .campo-editable-tabla { background: transparent !important; border: none !important;}
            .tabla-pdf th { background-color: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;}
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