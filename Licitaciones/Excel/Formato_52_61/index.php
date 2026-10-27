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
    $archivo_json = 'formato_52_61_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'documento_data_v27.json'; 
}

$html_contenido = '';

if(file_exists($archivo_json)) {
    $json_data = json_decode(file_get_contents($archivo_json), true);
    $html_contenido = $json_data['html'] ?? '';
}

// Array con las 29 posiciones transcritas (plantilla original)
$items = [
    1 => ['S', 11], 2 => ['M', 72], 3 => ['L', 135],
    4 => ['XL', 148], 5 => ['2XL', 60],
    6 => ['3XL', 15], 7 => ['4XL', 5], 8 => ['5XL', 4],
    9 => ['GORRA', 450], 10 => ['BOLSA', 450],
    11 => ['XS', 4], 12 => ['S', 47],
    13 => ['M', 165], 14 => ['L', 228], 15 => ['XL', 160],
    16 => ['2XL', 36], 17 => ['3XL', 9], 18 => ['4XL', 4],
    19 => ['5XL', 1], 20 => ['6XL', 1], 21 => ['GORRA', 655],
    22 => ['BOLSA', 655], 23 => ['M', 16], 24 => ['L', 103], 25 => ['XL', 167],
    26 => ['2XL', 31], 27 => ['3XL', 10], 28 => ['GORRA', 327],
    29 => ['BOLSA', 327]
];

function obtenerDescripcion($type) {
    if ($type === 'GORRA') {
        return '<b>GORRA TIPO BEISBOL COLOR NEGRO</b> PERSONALIZACIÓN EN BORDADO PLANO: LOGO EN EL FRENTE (PDVSA GAS) EN COLOR BLANCO LADO LATERAL DERECHO (DIRECCIÓN DE PRODUCCIÓN)';
    } else if ($type === 'BOLSA') {
        return '<b>BOLSA TIPO ECOLOGICA COLOR BLANCO</b> TIMBRADA CON EL LOGO DE PDVSA GAS EN ROJO';
    } else {
        return '<b>CHAQUETA IMPERMEABLE CORTAVIENTOS TALLA ' . $type . ':</b><br>CHAQUETA DEPORTIVA, MATERIAL 100% POLIESTER, COLOR NEGRO, TIPO CUELLO COMPLETO TIPO TORTUGA, LOGITUD DE MANGA 65, TIPO DE MANGA LARGA, TALLA ' . $type . ', COLOR DEL EMBLEMA VER ESPECIFICACIONES ANEXAS, DIMENSIÓN BOLSILLOS 2, DISEÑO VER ESPECIFICACIONES ANEXAS.<br>LOGO PDVSA GAS FRONTAL LADO IZQUIERDO BORDADO EN BLANCO DIRECCIÓN DE PRODUCCIÓN FRONTAL LADO DERECHO BORDADO BLANCO';
    }
}

// Generamos la tabla dividida en 10 páginas si no existe el JSON
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

    // Distribución exacta de ítems por página
    $pages = [
        [1, 2, 3],        // Pág 1
        [4, 5],           // Pág 2
        [6, 7, 8],        // Pág 3
        [9, 10, 11, 12],  // Pág 4
        [13, 14, 15],     // Pág 5
        [16, 17, 18],     // Pág 6
        [19, 20, 21, 22], // Pág 7
        [23, 24, 25],     // Pág 8
        [26, 27, 28],     // Pág 9
        [29]              // Pág 10
    ];

    $html_contenido = '';
    $td_edit = '<td class="td-con-fondo" contenteditable="true"></td>';

    foreach ($pages as $index => $page_items) {
        $html_contenido .= '<div class="hoja paisaje">';
        $html_contenido .= $encabezado;

        if ($index == 0) {
            $html_contenido .= '
            <div class="titulo" style="margin-top:0;">
                FORMATO I.7 - FORMATO DE OFERTA TÉCNICA<br>
                PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable-linea" contenteditable="true">0394</span><br>
                REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
            </div>';
        } else {
            $html_contenido .= '<div style="height: 30px;"></div>'; 
        }

        $html_contenido .= '<table class="tabla-oferta tabla-excel">';
        $html_contenido .= '
            <thead>
                <tr>
                    <th style="width: 4%;">POS/<br>RGN</th>
                    <th style="width: 7%;">CÓDIGO</th>
                    <th style="width: 32%;">ESPECIFICACIONES TÉCNICAS<br>(DESCRIPCIÓN LARGA DEL MATERIAL)</th>
                    <th style="width: 5%;">CANTIDAD</th>
                    <th style="width: 5%;">UNIDAD DE<br>MEDIDA</th>
                    <th style="width: 7%;">ENTREGA<br>INMEDIATA</th>
                    <th style="width: 8%;">TIEMPO DE<br>ENTREGA<br>REQUERIDO</th>
                    <th style="width: 8%;">TIEMPO DE<br>ENTREGA<br>OFERTADO</th>
                    <th style="width: 8%;">MARCA</th>
                    <th style="width: 7%;">TIEMPO DE<br>GARANTÍA</th>
                    <th style="width: 9%;">OBSERVACIONES<br>/ COMENTARIOS</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($page_items as $pos) {
            $type = $items[$pos][0];
            $qty = $items[$pos][1];
            $desc = obtenerDescripcion($type);

            $html_contenido .= '<tr>';
            $html_contenido .= '<td><b>' . $pos . '</b></td>';
            $html_contenido .= $td_edit; 
            $html_contenido .= '<td class="td-desc">' . $desc . '</td>';
            $html_contenido .= '<td><b>' . $qty . '</b></td>';
            $html_contenido .= '<td>UND</td>';
            $html_contenido .= $td_edit; 
            $html_contenido .= '<td style="font-size: 6.5pt !important; font-weight: bold;">08 SEMANAS</td>'; 
            $html_contenido .= $td_edit; 
            $html_contenido .= $td_edit; 
            $html_contenido .= $td_edit; 
            $html_contenido .= '<td contenteditable="true" style="outline:none;"></td>'; 
            $html_contenido .= '</tr>';
        }

        $html_contenido .= '</tbody></table>';

        if ($index == count($pages) - 1) {
            $html_contenido .= '
            <p style="font-size: 8pt; font-weight: bold; margin-top: 15px;">NOTA: VALIDEZ DE LA OFERTA NOVENTA (90) DÍAS HÁBILES</p>
            <p style="font-size: 8pt; font-weight: bold; margin-top: -5px;">CANTIDAD DE POSICIONES / RENGLONES OFERTADOS: <span class="campo-editable-linea" style="min-width: 40px;" contenteditable="true">29</span> POSICIONES / RENGLONES</p>
            
            <table style="width: 100%; border: none !important; margin-top: 10px; font-size: 8.5pt;">
                <tr>
                    <td style="text-align: left; border: none !important; padding: 0;"><b>EMPRESA:</b> <span class="campo-editable-linea" contenteditable="true">' . $nombre_empresa . '</span></td>
                    <td style="text-align: center; border: none !important; padding: 0;"><b>RIF:</b> <span class="campo-editable-linea" contenteditable="true">' . $rif_empresa . '</span></td>
                    <td style="text-align: right; border: none !important; padding: 0;">En (ciudad) <span class="campo-editable-linea" contenteditable="true">[CIUDAD]</span>, a los <span class="campo-editable-linea" contenteditable="true">[DÍA]</span> días del mes de <span class="campo-editable-linea" contenteditable="true">[MES]</span> de 20<span class="campo-editable-linea" contenteditable="true">[AÑO]</span>.</td>
                </tr>
            </table>
            
            <div class="firmas" style="margin-top: 30px;">
                <div class="linea-firma"></div>
                <b style="font-size: 8pt;">FIRMA DEL REPRESENTANTE</b><br>
                <span style="font-size: 8pt;">C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA</span><br>
                <span style="font-size: 8pt;">SELLO DE LA CONTRATISTA</span>
            </div>

            <p style="font-size: 6.5pt; text-align: justify; margin-top: 15px; line-height: 1.2;">
            <b>Notas:</b><br>
            En caso que la oferta técnica sea parcial (no cotizan todas las posiciones), deben presentar solo la oferta de los renglones cotizados, pero conservando la numeración (posición) original establecida en el Pliego de Condiciones y/o Condiciones de la Contratación. <b>CADA PÁGINA PERTENECIENTE AL FORMATO DE OFERTA TÉCNICA DEBE ESTAR DEBIDAMENTE FIRMADA Y SELLADA POR LA EMPRESA, EN CASO CONTRARIO LA OFERTA SERÁ RECHAZADA.</b> EN CASO DE REPUESTOS APLICA LO ESTABLECIDO EN EL ARTÍCULO 66 N°4 DEL DRVFLCP.
            </p>';
        }

        $html_contenido .= '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Oferta Técnica - <?php echo $nombre_empresa; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        *, *:before, *:after { box-sizing: border-box !important; }

        .editor-container { background-color: #e5e7eb; padding: 40px; display: block; min-height: 100vh; }
        
        /* LA MAGIA DE LA BARRA FLOTANTE */
        .toolbar { 
            position: fixed; 
            top: 20px; 
            right: 40px; 
            background: rgba(255, 255, 255, 0.95); 
            padding: 10px 15px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.15); 
            z-index: 9999; 
            display: flex; 
            gap: 10px; 
            backdrop-filter: blur(5px);
        }
        
        /* HOJA TAMAÑO CARTA ACOSTADA (LANDSCAPE) */
        .hoja.paisaje { 
            background: white; 
            width: 11in; 
            min-height: 8.5in; 
            padding: 0.4in 0.5in; 
            margin: 0 auto 30px auto; 
            font-family: 'Arial', sans-serif; 
            font-size: 8pt; 
            line-height: 1.3; 
            outline: none; 
            position: relative; 
            page-break-after: always; 
            text-align: justify; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.2); 
            box-sizing: border-box;
        }

        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 15px; }
        .logo-container img { width: 130px; cursor: default; } 
        .info-fiscal { text-align: right; font-size: 7pt; line-height: 1.2; max-width: 65%; }
        .titulo { font-size: 9pt; font-weight: bold; text-align: center; margin-bottom: 10px; }
        
        /* CONFIGURACIÓN DE TABLAS */
        .tabla-oferta { width: 100% !important; border-collapse: collapse !important; table-layout: fixed; margin-top: 5px; }
        .tabla-oferta th, .tabla-oferta td { border: 1px solid #000 !important; padding: 4px 2px !important; text-align: center; vertical-align: middle; font-size: 6.5pt !important; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
        .tabla-oferta th { background-color: #f3f4f6 !important; font-weight: bold; font-size: 6pt !important;}
        .td-desc { text-align: justify !important; font-size: 6pt !important; line-height: 1.1; padding: 5px !important;}
        
        /* CELDAS AMARILLAS */
        .td-con-fondo { background-color: transparent !important; outline: none; }
        .td-con-fondo:focus, .td-con-fondo:active { background-color: #fef08a !important; }
        
        .campo-editable-linea { background-color: #fef08a; display: inline-block; padding: 0px 4px; border-radius: 3px; color: #111; outline: none; border: 1px dashed #ca8a04;}

        .linea-firma { width: 220px; border-bottom: 1px solid #000; margin-bottom: 5px; }

        /* BOTONES FLOTANTES */
        .btn-guardar, .btn-pdf, .btn-excel, .btn-agregar, .btn-quitar { pointer-events: auto; font-weight: 600; cursor: pointer; border-radius: 8px; border: none; padding: 10px 15px; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }
        .btn-guardar { background-color: var(--color-green); color: white; display: none; }
        .btn-agregar { background-color: #3b82f6; color: white; display: none; }
        .btn-quitar { background-color: #ef4444; color: white; display: none; }
        .btn-excel { background-color: #10b981; color: white; }
        .btn-pdf { background-color: #6b7280; color: white; }
        .btn-guardar:hover, .btn-pdf:hover, .btn-excel:hover, .btn-agregar:hover, .btn-quitar:hover { opacity: 0.9; }

        /* IMPRESIÓN FORZADA EN LANDSCAPE (HORIZONTAL) */
        @page { size: letter landscape; margin: 0.5in; }
        @media print {
            html, body, main, .main-content, .editor-container, #documento-editable { display: block !important; margin: 0 !important; padding: 0 !important; background: white !important;}
            .toolbar, .sidebar, .header { display: none !important; }
            
            /* Eliminamos max-height para que la tabla pueda crecer si le agregas filas */
            .hoja.paisaje { 
                width: 100% !important; 
                height: auto !important; 
                max-height: none !important; 
                padding: 0 !important; 
                margin: 0 !important; 
                page-break-after: always !important; 
                page-break-inside: auto !important;
                box-shadow: none !important; 
                border: none !important; 
                overflow: visible !important; 
            }
            tr { page-break-inside: avoid !important; }
            .campo-editable-linea { background: transparent !important; border: none !important; color: black !important;}
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
            <!-- Barra Flotante -->
            <div class="toolbar">
                <!-- Botones Dinámicos para Agregar o Quitar filas -->
                <button id="btn-agregar" class="btn-agregar" onclick="agregarFila()"><i class="ph-bold ph-plus"></i> Fila</button>
                <button id="btn-quitar" class="btn-quitar" onclick="quitarFila()"><i class="ph-bold ph-minus"></i> Fila</button>
                
                <button id="btn-guardar" class="btn-guardar" onclick="guardarCambios()"><i class="ph-bold ph-floppy-disk"></i> Guardar</button>
                <button class="btn btn-excel" onclick="exportarExcel()"><i class="ph-bold ph-file-xls"></i> Excel</button>
                <button class="btn btn-pdf" onclick="window.print()"><i class="ph-bold ph-file-pdf"></i> Imprimir PDF</button>
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
                    document.getElementById('btn-guardar').style.display = "flex";
                    document.getElementById('btn-agregar').style.display = "flex";
                    document.getElementById('btn-quitar').style.display = "flex";
                    
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
            // Buscamos todas las tablas de oferta
            let tablas = document.querySelectorAll('.tabla-oferta');
            if(tablas.length === 0) return;
            
            // Agregamos la fila en la ÚLTIMA tabla
            let ultimaTabla = tablas[tablas.length - 1];
            let tbody = ultimaTabla.querySelector('tbody');
            
            if(tbody.children.length === 0) return;
            
            // Clonamos la última fila
            let ultimaFila = tbody.lastElementChild;
            let nuevaFila = ultimaFila.cloneNode(true);
            
            // Incrementamos el número (columna 1)
            let numTexto = ultimaFila.cells[0].innerText.trim();
            let numActual = parseInt(numTexto) || 0;
            nuevaFila.cells[0].innerHTML = '<b>' + (numActual + 1) + '</b>';
            
            // Limpiamos las demás celdas para que el usuario escriba sus datos
            for(let i=1; i<nuevaFila.cells.length; i++) {
                nuevaFila.cells[i].innerHTML = ''; 
                nuevaFila.cells[i].setAttribute('contenteditable', 'true');
                nuevaFila.cells[i].style.backgroundColor = 'transparent';
                nuevaFila.cells[i].style.color = '#111';
            }
            
            tbody.appendChild(nuevaFila);
        }

        function quitarFila() {
            let tablas = document.querySelectorAll('.tabla-oferta');
            if(tablas.length === 0) return;
            
            let ultimaTabla = tablas[tablas.length - 1];
            let tbody = ultimaTabla.querySelector('tbody');
            
            // Verificamos que quede al menos 1 fila en la última tabla
            if (tbody.children.length > 1) {
                tbody.removeChild(tbody.lastElementChild);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'No puedes eliminar la última fila de esta página.',
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

        // ==========================================
        // EXPORTACIÓN A EXCEL 
        // ==========================================
        function exportarExcel() {
            let htmlExcel = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="utf-8">
                    <style> 
                        td { mso-number-format:"\\@"; vertical-align: middle; padding: 5px; border: 1px solid #000; font-family: Arial, sans-serif; font-size: 9pt;} 
                        th { background-color: #f0f0f0; font-weight: bold; text-align: center; border: 1px solid #000; font-family: Arial, sans-serif; font-size: 9pt; }
                    </style>
                </head>
                <body>
                    <h2 style="text-align: center;">FORMATO I.7 - FORMATO DE OFERTA TÉCNICA</h2><br>
            `;

            let tablas = document.querySelectorAll('.tabla-excel');
            
            // Contenedor fantasma para unir todas las filas en una sola tabla larga
            let tablaMaestra = document.createElement('table');
            tablaMaestra.border = '1';
            tablaMaestra.style.borderCollapse = 'collapse';

            tablaMaestra.appendChild(tablas[0].querySelector('thead').cloneNode(true));
            let tbody = document.createElement('tbody');

            tablas.forEach(tabla => {
                let clonTabla = tabla.cloneNode(true);
                let filas = clonTabla.querySelectorAll('tbody tr');
                filas.forEach(fila => {
                    let celdas = fila.querySelectorAll('td');
                    celdas.forEach(celda => {
                        celda.innerHTML = celda.innerText.trim(); 
                    });
                    tbody.appendChild(fila);
                });
            });

            tablaMaestra.appendChild(tbody);
            htmlExcel += tablaMaestra.outerHTML + '</body></html>';

            let blob = new Blob(['\ufeff', htmlExcel], {type: 'application/vnd.ms-excel'});
            let url = URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'Oferta_Tecnica_PDVSA.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>