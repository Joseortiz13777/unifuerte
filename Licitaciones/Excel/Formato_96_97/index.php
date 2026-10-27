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
    $archivo_json = 'formato_96_97_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'documento_data_v37.json'; // Nombre V37 original
}

$html_contenido = '';

if(file_exists($archivo_json)) {
    $json_data = json_decode(file_get_contents($archivo_json), true);
    $html_contenido = $json_data['html'] ?? '';
}

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

    // Celdas editables que son transparentes hasta que se les hace clic
    $td_edit = 'class="td-con-fondo" contenteditable="true"';

    // =====================================================================
    // PÁGINA 1: FORMATO II.5 - AUTOCÁLCULO VAN (Ítems a - h completos)
    // =====================================================================
    $html_contenido .= '<div class="hoja">';
    $html_contenido .= $encabezado;

    $html_contenido .= '
        <div class="titulo" style="font-size: 11pt; margin-top: 10px; margin-bottom: 25px;">
            FORMATO II.5. – FORMATO PARA EL AUTOCÁLCULO DEL % VALOR AGREGADO NACIONAL (VAN)<br><br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable-linea" contenteditable="true" style="min-width: 40px;">0394</span><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
        </div>
        
        <table class="tabla-oferta">
            <colgroup>
                <col style="width: 70%;">
                <col style="width: 15%;">
                <col style="width: 15%;">
            </colgroup>
            <thead>
                <tr>
                    <th style="background-color: #e5e7eb !important; padding: 10px !important; text-align: left;">ELEMENTOS DE COSTOS DE LA OFERTA</th>
                    <th style="background-color: #e5e7eb !important; padding: 10px !important;">(Bs.)</th>
                    <th style="background-color: #e5e7eb !important; padding: 10px !important;">% VAN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="td-texto">a) Materia Prima e insumos de origen nacional de aplicación directa al bien, servicio u obra, envasado y empaque de fabricación nacional.</td>
                    <td ' . $td_edit . '>A</td>
                    <td ' . $td_edit . '>(a / D) x 100</td>
                </tr>
                <tr>
                    <td class="td-texto">b) Equipos de origen venezolano incorporados como activos fijos en la obra.</td>
                    <td ' . $td_edit . '>B</td>
                    <td ' . $td_edit . '>(b / D) x 100</td>
                </tr>
                <tr>
                    <td class="td-texto">c) Mano de obra utilizada en la República Bolivariana de Venezuela para la fabricación del bien, ejecución de la obra o prestación del servicio, así como para el envasado y empaquetado de los bienes. En caso de las cooperativas, cuando el trabajo es ejecutado directamente por los asociados, el Valor Agregado Nacional (VAN) por concepto de mano de obra se calculará con base a los anticipos societarios, compensaciones y demás regímenes de previsión y protección social previstos en los estatutos o reglamento interno de la cooperativa. Sin embargo, cuando las cooperativas excepcionalmente contraten los servicios del personal no asociado no deben exceder el veinte (20%) por ciento de los asociados a la cooperativa.</td>
                    <td ' . $td_edit . '>C</td>
                    <td ' . $td_edit . '>(c / D) x 100</td>
                </tr>
                <tr>
                    <td class="td-texto">d) Tecnología de origen nacional aplicada en la elaboración del bien, ejecución de la obra o prestación del servicio.</td>
                    <td ' . $td_edit . '>D</td>
                    <td ' . $td_edit . '>(d / D) x 100</td>
                </tr>
                <tr>
                    <td class="td-texto">e) Los estudios, la ingeniería conceptual y de detalle, así como la inspección y gerencia del sector servicio u obra, cuando formen parte integral de la oferta.</td>
                    <td ' . $td_edit . '>E</td>
                    <td ' . $td_edit . '>(e / D) x 100</td>
                </tr>
                <tr>
                    <td class="td-texto">f) Servicios profesionales y no profesionales prestados por las personas naturales, cooperativas, las pequeñas, medianas industrias y otras formas asociativas con domicilio principal en la República Bolivariana de Venezuela, empleados en la fabricación del bien, ejecución de la obra o prestación del servicio, siempre y cuando el setenta y cinco por ciento (75%) de estos servicios sea prestado por profesionales o personas de nacionalidad venezolana.</td>
                    <td ' . $td_edit . '>f</td>
                    <td ' . $td_edit . '>(f / D) x 100</td>
                </tr>
                <tr>
                    <td class="td-texto">g) Gastos financieros pagados en la República Bolivariana de Venezuela para la elaboración del bien, ejecución de la obra o prestación del servicio.</td>
                    <td ' . $td_edit . '>g</td>
                    <td ' . $td_edit . '>(g / D) x 100</td>
                </tr>
                <tr>
                    <td class="td-texto">h) Depreciación de equipos instalados en República Bolivariana de Venezuela utilizados para la fabricación del bien, ejecución de la obra o el servicio, de acuerdo a los siguientes criterios:<br>
                    h.1). Para la fabricación de bienes, prestación de servicios y ejecución de obras, la depreciación no podrá ser realizada en un tiempo menor de un (1) año para utensilios y herramientas, cuatro (4) años para maquinarias, equipos e instalación.<br>
                    h.2). El valor de depreciación será el valor histórico en libros o al valor según avalúo de activos.<br>
                    h.3). La alícuota será el resultante de aplicar el valor de depreciación anual obtenido de la maquinaria, equipos o instalaciones en el periodo realmente utilizado directamente en la fabricación del bien, ejecución de la obra o prestación del servicio.</td>
                    <td ' . $td_edit . '>h</td>
                    <td ' . $td_edit . '>(h / D) x 100</td>
                </tr>
            </tbody>
        </table>

        <div style="border-top: 2px solid #000; padding-top: 5px; text-align: center; font-weight: bold; font-size: 8pt; margin-top: 40px; margin-bottom: 20px;">
            -BARIVEN, S.A.-
            <span style="float: right; font-weight: normal; font-size: 7pt;">FAF:2025-07-15</span>
        </div>
    </div>';

    // =====================================================================
    // PÁGINA 2: FORMATO II.5 - AUTOCÁLCULO VAN (Cierre Financiero y Firmas)
    // =====================================================================
    $html_contenido .= '<div class="hoja">';
    $html_contenido .= $encabezado;

    $html_contenido .= '
        <div class="titulo">
            CÁLCULO DEL VALOR AGREGADO NACIONAL (CONTINUACIÓN)
        </div>

        <table class="tabla-oferta">
            <colgroup>
                <col style="width: 70%;">
                <col style="width: 15%;">
                <col style="width: 15%;">
            </colgroup>
            <tbody>
                <tr>
                    <td class="td-texto" style="font-weight: bold; text-align: left !important;">Utilidad</td>
                    <td ' . $td_edit . '></td>
                    <td ' . $td_edit . '></td>
                </tr>
                <tr>
                    <td class="td-texto" style="font-weight: bold; text-align: left !important;">Oferta Sin IVA</td>
                    <td ' . $td_edit . ' style="font-weight: bold;">Bs.S</td>
                    <td ' . $td_edit . '></td>
                </tr>
                <tr>
                    <td class="td-texto" style="font-weight: bold; text-align: left !important;">Total, en Bs.S</td>
                    <td ' . $td_edit . ' style="font-weight: bold;">Bs.S</td>
                    <td ' . $td_edit . '></td>
                </tr>
                <tr>
                    <td class="td-texto" style="font-weight: bold; text-align: left !important; background-color: #f3f4f6 !important;">Valor Agregado Nacional (VAN)</td>
                    <td ' . $td_edit . ' style="font-weight: bold; background-color: #f3f4f6 !important;">%</td>
                    <td ' . $td_edit . ' style="background-color: #f3f4f6 !important;"></td>
                </tr>
            </tbody>
        </table>

        <div style="font-size: 10pt; margin-top: 25px; line-height: 1.6; text-align: justify;">
            Fórmula establecida en la página del SNC <u style="color: blue;">http://www.snc.gob.ve/index.php/calculo-del-van-2/</u> <b>- Apoyo para el cálculo del Valor Agregado Nacional (VAN)</b><br><br>
            <b>Cálculo de % VAN total de la Oferta:</b><br>
            El porcentaje del precio, como resultado de sumar las contribuciones porcentuales en la formación del precio final de cada uno de los componentes de origen nacional que se utilizan para producir un bien, prestar un servicio o ejecutar una obra.
        </div>
        
        <div style="margin-top: 40px; font-size: 10pt; text-align: left;">
            En (ciudad), a los <span class="campo-editable-linea" contenteditable="true" style="min-width:50px;"></span> 
            ( <span class="campo-editable-linea" contenteditable="true" style="min-width:30px;"></span> ) días del mes de 
            <span class="campo-editable-linea" contenteditable="true" style="min-width:120px;"></span> 
            20<span class="campo-editable-linea" contenteditable="true" style="min-width:30px;"></span>.
        </div>
        
        <div class="firmas">
            <div class="linea-firma"></div>
            <b>FIRMA DEL REPRESENTANTE</b><br>
            C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
            SELLO DE LA CONTRATISTA
        </div>

        <div style="border-top: 2px solid #000; padding-top: 5px; text-align: center; font-weight: bold; font-size: 8pt; margin-top: 40px; margin-bottom: 20px;">
            -BARIVEN, S.A.-
            <span style="float: right; font-weight: normal; font-size: 7pt;">FAF:2025-07-15</span>
        </div>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Autocálculo VAN - <?php echo $nombre_empresa; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        *, *:before, *:after { box-sizing: border-box !important; }

        .editor-container { background-color: #e5e7eb; padding: 40px; display: block; min-height: 100vh; position: relative; }
        
        /* BARRA FLOTANTE PEGAJOSA (Sticky arriba) */
        .toolbar-wrapper { 
            position: sticky; 
            top: 20px; 
            z-index: 999999; 
            display: flex; 
            justify-content: flex-end; 
            width: 100%;
            max-width: 95%; /* Se ajusta al ancho de la hoja */
            margin: 0 auto 20px auto; 
            pointer-events: none; 
        }

        .toolbar { 
            background: rgba(255, 255, 255, 0.95); 
            padding: 10px 15px; 
            border-radius: 8px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            display: flex; 
            gap: 10px; 
            border: 1px solid #e5e7eb;
            pointer-events: auto; 
        }
        
        /* 
           =========================================================
           LA HOJA EN PANTALLA: ANCHA Y ADAPTABLE (Sin límite de 8.5in)
           =========================================================
        */
        .hoja { 
            background: white; 
            width: 95%; /* Toma casi todo el ancho disponible */
            max-width: 1200px; /* Evita que se vea exageradamente ancha en monitores gigantes */
            min-height: auto; 
            padding: 50px; /* Margen interno cómodo para leer en pantalla */
            margin: 0 auto 40px auto; 
            font-family: 'Arial', sans-serif; 
            font-size: 10pt; 
            line-height: 1.5; 
            outline: none; 
            position: relative; 
            text-align: justify; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.15); 
            border-radius: 8px; /* Bordes suaves en pantalla */
            overflow: visible; 
            box-sizing: border-box; 
        }

        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 25px; }
        .logo-container img { width: 140px; cursor: default; } 
        .info-fiscal { text-align: right; font-size: 8.5pt; line-height: 1.3; max-width: 65%; }
        
        .titulo { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 20px; line-height: 1.4; }
        
        /* TABLA CLEAN - 100% de la hoja en pantalla */
        .tabla-oferta { 
            width: 100% !important; 
            max-width: 100% !important; 
            border-collapse: collapse !important; 
            table-layout: fixed !important; 
            margin: 20px 0 !important; 
            font-size: 10pt !important; 
            box-sizing: border-box !important;
        }
        
        .tabla-oferta th, .tabla-oferta td { 
            border: 1px solid #000 !important; 
            padding: 10px 8px !important; /* Más espacio interior */
            text-align: center; 
            vertical-align: middle; 
            word-wrap: break-word !important; 
            overflow-wrap: break-word !important;
            word-break: break-word !important; 
            white-space: normal !important;
            box-sizing: border-box !important;
        }
        
        .tabla-oferta th { background-color: #e5e7eb !important; color: #374151 !important; font-weight: bold; font-size: 10pt !important; }
        .td-texto { text-align: justify !important; font-size: 10pt !important; line-height: 1.5; padding: 12px 10px !important;}
        
        .td-con-fondo { background-color: transparent !important; outline: none; cursor: text; }
        .td-con-fondo:focus, .td-con-fondo:active { background-color: #fef08a !important; }

        .campo-editable-linea { background-color: transparent; display: inline-block; padding: 0px 4px; border-bottom: 1px dashed #ca8a04; color: #111; outline: none; cursor: text; font-weight: bold; }
        .campo-editable-linea:focus { background-color: #fef08a; }

        .campo-editable-tabla { display: block; width: 100%; min-height: 15px; outline: none; word-wrap: break-word !important; overflow-wrap: break-word !important; }
        [contenteditable="true"] .campo-editable-tabla { background-color: transparent;}
        [contenteditable="true"]:focus .campo-editable-tabla { background-color: #fef08a;}

        .firmas { margin-top: 50px; text-align: left; line-height: 1.5; font-size: 10pt; }
        .linea-firma { width: 250px; border-bottom: 1px solid #000; margin-bottom: 5px; }

        .btn-guardar, .btn-pdf, .btn-excel, .btn-agregar, .btn-quitar { pointer-events: auto; font-weight: 600; cursor: pointer; border-radius: 6px; border: none; padding: 8px 12px; font-size: 0.85rem; display: flex; align-items: center; gap: 6px; }
        .btn-guardar { background-color: var(--color-green); color: white; display: none; }
        .btn-agregar { background-color: #3b82f6; color: white; display: none; }
        .btn-quitar { background-color: #ef4444; color: white; display: none; }
        .btn-excel { background-color: #10b981; color: white; }
        .btn-pdf { background-color: #2563eb; color: white; }
        .btn-guardar:hover, .btn-pdf:hover, .btn-excel:hover, .btn-agregar:hover, .btn-quitar:hover { opacity: 0.9; }

        /* 
           =========================================================
           LA MAGIA DEL PDF: ESTRICTAMENTE TAMAÑO CARTA
           =========================================================
        */
        @page { size: letter; margin: 0.6in; }
        
        @media print {
            html, body, main, .main-content, .editor-container, #documento-editable { 
                display: block !important; height: auto !important; overflow: visible !important; position: static !important; margin: 0 !important; padding: 0 !important; background: white !important;
            }
            .toolbar-wrapper, .sidebar, .header { display: none !important; }
            
            .hoja { 
                /* Se fuerza el tamaño físico para el PDF */
                width: 100% !important; 
                max-width: 100% !important; 
                height: auto !important; 
                padding: 0 !important; 
                margin: 0 !important; 
                border-radius: 0 !important;
                box-shadow: none !important; 
                border: none !important; 
                box-sizing: border-box !important; 
                overflow: visible !important;
                page-break-after: always !important; 
                font-size: 9pt !important; /* Se reduce la fuente al imprimir para que encaje */
            }
            
            /* Ajustes finos de impresión para que no se corte la tabla */
            .tabla-oferta { font-size: 8.5pt !important; }
            .tabla-oferta th, .tabla-oferta td { padding: 4px !important; }
            .td-texto { font-size: 8.5pt !important; padding: 6px !important; }
            
            tr { page-break-inside: avoid !important; }
            .campo-editable-linea { border: none !important; color: black !important;}
            .td-con-fondo { background-color: transparent !important; }
            .tabla-oferta th { -webkit-print-color-adjust: exact; print-color-adjust: exact;}
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
            
            <!-- Barra Flotante Sticky Mejorada -->
            <div class="toolbar-wrapper">
                <div class="toolbar">
                    <button id="btn-agregar" class="btn-agregar" onclick="agregarFila()"><i class="ph-bold ph-plus"></i> Fila</button>
                    <button id="btn-quitar" class="btn-quitar" onclick="quitarFila()"><i class="ph-bold ph-minus"></i> Fila</button>
                    
                    <button id="btn-guardar" class="btn-guardar" onclick="guardarCambios()"><i class="ph-bold ph-floppy-disk"></i> Guardar</button>
                    <button class="btn btn-excel" onclick="exportarExcel()"><i class="ph-bold ph-file-xls"></i> Excel</button>
                    <button class="btn btn-pdf" onclick="window.print()"><i class="ph-bold ph-file-pdf"></i> Imprimir PDF</button>
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
                    
                    // Mostrar los botones para agregar/quitar filas
                    document.getElementById('btn-agregar').style.display = "inline-flex";
                    document.getElementById('btn-quitar').style.display = "inline-flex";

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

        // ==========================================
        // FUNCIONES PARA AGREGAR / QUITAR FILAS 
        // ==========================================
        function agregarFila() {
            // Buscamos la tabla de la página 2
            let tablas = document.querySelectorAll('.tabla-oferta');
            if(tablas.length < 2) return;
            
            let tabla = tablas[1];
            let tbody = tabla.querySelector('tbody');
            if(!tbody) return;
            
            // Queremos insertar una fila NUEVA justo ANTES de la fila "TOTAL, OFERTA (%)"
            // Como no le pusimos clase a la fila total antes, buscaremos la última
            let filaTotal = tbody.lastElementChild; 
            
            // Creamos la nueva fila clonando la anterior a la total
            let filaAnterior = filaTotal.previousElementSibling;
            if(!filaAnterior) return;
            
            let nuevaFila = filaAnterior.cloneNode(true);
            
            // Limpiamos los textos editables para que el usuario escriba
            nuevaFila.cells[0].innerHTML = '<div class="campo-editable-tabla" contenteditable="true">Nombre Otra Empresa...</div>';
            nuevaFila.cells[0].style.fontWeight = 'normal'; // Quitamos la negrita
            
            for(let i=1; i<nuevaFila.cells.length; i++) {
                nuevaFila.cells[i].innerHTML = '<div class="campo-editable-tabla" contenteditable="true"><br></div>'; 
            }
            
            // Insertamos la nueva fila justo ANTES de la fila de TOTAL
            tbody.insertBefore(nuevaFila, filaTotal);
        }

        function quitarFila() {
            let tablas = document.querySelectorAll('.tabla-oferta');
            if(tablas.length < 2) return;
            
            let tabla = tablas[1];
            let tbody = tabla.querySelector('tbody');
            
            // Verificamos que queden filas suficientes (Las 5 base: Gran Empresa, PyMI, Coop, Otra, Total)
            if (tbody.children.length > 5) {
                // Eliminamos la fila justo arriba del TOTAL
                let filaTotal = tbody.lastElementChild;
                tbody.removeChild(filaTotal.previousElementSibling);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'No puedes eliminar las filas base de este formato.',
                    confirmButtonColor: '#059669'
                });
            }
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

        function exportarExcel() {
            let htmlExcel = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="utf-8">
                    <style> 
                        td { vertical-align: middle; padding: 5px; border: 1px solid #000; font-family: Arial, sans-serif; font-size: 9pt;} 
                        th { text-align: center; border: 1px solid #000; font-family: Arial, sans-serif; font-size: 9pt; }
                    </style>
                </head>
                <body>
            `;

            let tablas = document.querySelectorAll('.tabla-oferta');
            if(tablas.length > 0) {
                // Exportar la primera tabla
                let clon1 = tablas[0].cloneNode(true);
                clon1.querySelectorAll('[contenteditable="true"]').forEach(ed => { ed.innerHTML = ed.innerText; });
                htmlExcel += '<h2 style="text-align: center;">FORMATO II.5 - AUTOCÁLCULO VAN (PARTE 1)</h2><br>';
                htmlExcel += '<table border="1" style="border-collapse: collapse; margin-bottom: 30px;">' + clon1.innerHTML + '</table><br>';
            }
            
            if(tablas.length > 1) {
                // Exportar la segunda tabla
                let clon2 = tablas[1].cloneNode(true);
                clon2.querySelectorAll('[contenteditable="true"]').forEach(ed => { ed.innerHTML = ed.innerText; });
                htmlExcel += '<h2 style="text-align: center;">FORMATO II.5 - AUTOCÁLCULO VAN (PARTE 2)</h2><br>';
                htmlExcel += '<table border="1" style="border-collapse: collapse; margin-bottom: 30px;">' + clon2.innerHTML + '</table><br>';
            }

            htmlExcel += '</body></html>';

            let blob = new Blob(['\ufeff', htmlExcel], {type: 'application/vnd.ms-excel'});
            let url = URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'Autocalculo_VAN_PDVSA.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>