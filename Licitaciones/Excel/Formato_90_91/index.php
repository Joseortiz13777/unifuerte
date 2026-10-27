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
    $archivo_json = 'formato_90_91_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'documento_data_v39.json'; // Nombre V39 original
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

    $td_edit = '<td class="td-con-fondo" contenteditable="true"></td>';
    $html_contenido = '';

    // =====================================================================
    // PÁGINA 1: FORMATO II.3 - ESTRUCTURA DE COSTOS (Parte 1: Items 1-25)
    // =====================================================================
    $html_contenido .= '<div class="hoja paisaje pagina-1-flex">';
    $html_contenido .= $encabezado;

    $html_contenido .= '
        <div class="titulo" style="font-size: 9pt; margin-top: 10px;">
            FORMATO II.3. - FORMATO DE ESTRUCTURAS DE COSTOS<br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable-linea" contenteditable="true">0394</span><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
        </div>
        
        <table class="tabla-oferta">
            <colgroup>
                <col style="width: 3%;"><col style="width: 4%;"><col style="width: 4%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 6%;"><col style="width: 6%;"><col style="width: 6%;"><col style="width: 6%;">
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2" class="td-vertical"><div class="vertical-text">POS/RNG</div></th>
                    <th rowspan="2" class="td-vertical"><div class="vertical-text">COD SAP</div></th>
                    <th rowspan="2" class="td-vertical"><div class="vertical-text">CANT (A)</div></th>
                    <th colspan="5" style="background-color: #d1d5db !important; font-size: 6pt !important;">COMPONENTE IMPORTADO</th>
                    <th colspan="10" style="background-color: #d1d5db !important; font-size: 6pt !important;">COMPONENTE NACIONAL</th>
                </tr>
                <tr>
                    <th class="td-vertical"><div class="vertical-text">MAT PRIMA (A)</div></th>
                    <th class="td-vertical"><div class="vertical-text">DESCARG (B)</div></th>
                    <th class="td-vertical"><div class="vertical-text">FLETE (C)</div></th>
                    <th class="td-vertical"><div class="vertical-text">SEGURO (D)</div></th>
                    <th class="td-vertical"><div class="vertical-text">TOT IMP (E)</div></th>
                    <th class="td-vertical"><div class="vertical-text">MAT PRIMA (F)</div></th>
                    <th class="td-vertical"><div class="vertical-text">MANO OBRA (G)</div></th>
                    <th class="td-vertical"><div class="vertical-text">MANTENIM (H)</div></th>
                    <th class="td-vertical"><div class="vertical-text">MAQUINARIA (I)</div></th>
                    <th class="td-vertical"><div class="vertical-text">DEPRECIAC (J)</div></th>
                    <th class="td-vertical"><div class="vertical-text">TRANS (K)</div></th>
                    <th class="td-vertical"><div class="vertical-text">GASTOS ADMIN (L)</div></th>
                    <th class="td-vertical"><div class="vertical-text">UTILIDAD (M)</div></th>
                    <th class="td-vertical"><div class="vertical-text">TOTAL (N)</div></th>
                    <th class="td-vertical"><div class="vertical-text">MONTO ADICIONAL (O)</div></th>
                </tr>
            </thead>
            <tbody>';
    for ($i = 1; $i <= 25; $i++) {
        $html_contenido .= '<tr class="fila-oferta"><td><b>' . $i . '</b></td>' . $td_edit . '<td></td>' . str_repeat($td_edit, 15) . '</tr>';
    }
    $html_contenido .= '</tbody></table></div>';

    // =====================================================================
    // PÁGINA 2: FORMATO II.3 - AUTOCÁLCULO (Parte 2: Items 26-29)
    // =====================================================================
    $html_contenido .= '<div class="hoja paisaje pagina-2-flex">';
    $html_contenido .= $encabezado;
    
    $html_contenido .= '<div class="contenido-hoja-2">'; 
    
    $html_contenido .= '
        <table class="tabla-oferta">
            <colgroup>
                <col style="width: 3%;"><col style="width: 4%;"><col style="width: 4%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 5%;"><col style="width: 6%;"><col style="width: 6%;"><col style="width: 6%;"><col style="width: 6%;">
            </colgroup>
            <tbody>';
    for ($i = 26; $i <= 29; $i++) {
        $html_contenido .= '<tr class="fila-oferta"><td><b>' . $i . '</b></td>' . $td_edit . '<td></td>' . str_repeat($td_edit, 15) . '</tr>';
    }
    $html_contenido .= '
            </tbody>
        </table>
        
        <div style="margin-top: 30px; font-size: 9pt; text-align: left;">
            En (ciudad), a los <span class="campo-editable-linea" contenteditable="true"></span> 
            ( <span class="campo-editable-linea" contenteditable="true"></span> ) días del mes de 
            <span class="campo-editable-linea" contenteditable="true"></span> 
            20<span class="campo-editable-linea" contenteditable="true"></span>.
        </div>
        
        <div class="firmas" style="margin-top: 40px; font-size: 9pt; line-height: 1.6;">
            <u>FIRMA DEL REPRESENTANTE</u><br>
            <u>C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA</u><br>
            <u>SELLO DE LA CONTRATISTA</u>
        </div>';

    $html_contenido .= '</div>'; // Fin de .contenido-hoja-2

    $html_contenido .= '
        <div class="pie-pagina-bariven">
            -BARIVEN, S.A.-
            <span style="float: right; font-weight: normal; font-size: 7pt;">FAF:2025-07-15</span>
        </div>';
    $html_contenido .= '</div>'; // Fin de .hoja.paisaje
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Estructura Costos II.3 - <?php echo $nombre_empresa; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        * { box-sizing: border-box !important; }
        .editor-container { background-color: #e5e7eb; padding: 40px; display: block; min-height: 100vh; position: relative; }
        
        /* LA BARRA FLOTANTE ABSOLUTA QUE SIGUE EL SCROLL DE LA PANTALLA */
        .toolbar { 
            position: fixed !important; 
            top: 30px !important; 
            right: 40px !important; 
            background: rgba(255, 255, 255, 0.95); 
            padding: 10px 15px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.15); 
            z-index: 999999 !important; 
            display: flex; 
            gap: 10px; 
            backdrop-filter: blur(5px);
            border: 1px solid #e5e7eb;
        }
        
        /* HOJA ACOSTADA (LANDSCAPE) */
        .hoja.paisaje { 
            background: white; width: 11in; min-height: 8.5in; padding: 0.5in; margin: 0 auto 30px auto; 
            font-family: 'Arial', sans-serif; font-size: 7pt; outline: none; position: relative; page-break-after: always; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.2); overflow: visible;
        }

        .pagina-2-flex { display: flex; flex-direction: column; }
        .contenido-hoja-2 { flex: 1; display: flex; flex-direction: column; }
        
        .pie-pagina-bariven {
            display: block; width: 100%; border-top: 2px solid #000; padding-top: 5px; text-align: center;
            font-weight: bold; font-size: 8pt; margin-top: auto; padding-bottom: 30px; 
        }

        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
        .logo-container img { width: 120px; cursor: default; } 
        .info-fiscal { text-align: right; font-size: 6pt; line-height: 1.2; max-width: 60%; }
        .titulo { font-size: 9pt; font-weight: bold; text-align: center; margin-bottom: 10px; }
        
        .tabla-oferta { width: 100% !important; max-width: 100% !important; border-collapse: collapse !important; table-layout: fixed !important; }
        .tabla-oferta th, .tabla-oferta td { border: 1px solid #000 !important; padding: 2px !important; text-align: center; vertical-align: middle; font-size: 6pt !important; word-wrap: break-word !important; }
        
        .td-vertical { height: 120px; vertical-align: bottom; padding: 2px !important; }
        .vertical-text { writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; font-size: 5pt; }

        .td-con-fondo { background-color: transparent !important; outline: none; cursor: text; }
        .td-con-fondo:focus { background-color: #fef08a !important; }
        .campo-editable-linea { background-color: transparent; border-bottom: 1px dashed #ca8a04; outline: none; cursor: text; padding: 0 5px; }
        .campo-editable-linea:focus { background-color: #fef08a; }

        .btn-guardar, .btn-pdf, .btn-excel, .btn-agregar, .btn-quitar { pointer-events: auto; font-weight: 600; cursor: pointer; border-radius: 8px; border: none; padding: 10px 15px; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }
        .btn-guardar { background-color: var(--color-green); color: white; display: none; }
        .btn-agregar { background-color: #3b82f6; color: white; display: none; margin-left: 10px;}
        .btn-quitar { background-color: #ef4444; color: white; display: none; margin-left: 5px;}
        .btn-excel { background-color: #10b981; color: white; margin-left: 10px;}
        .btn-pdf { background-color: #2563eb; color: white; margin-left: 10px;}
        .btn-guardar:hover, .btn-pdf:hover, .btn-excel:hover, .btn-agregar:hover, .btn-quitar:hover { opacity: 0.9; }

        /* CSS DE IMPRESIÓN FORZADA HORIZONTAL */
        @page { size: letter landscape; margin: 0.5in; } 
        @media print {
            body { font-family: 'Arial', sans-serif; font-size: 7pt; background: white; margin: 0; padding: 0; }
            html, main, .main-content, .editor-container, #documento-editable { display: block !important; margin: 0 !important; padding: 0 !important; background: transparent !important; overflow: visible !important;}
            .toolbar, .sidebar, .header { display: none !important; }
            
            .hoja.paisaje { 
                width: 100% !important; 
                min-height: 7.5in !important; 
                height: auto !important; /* Altura auto para que crezca si agregas filas */
                max-height: none !important;
                margin: 0 !important; 
                padding: 0 !important; 
                box-shadow: none !important; 
                border: none !important; 
                page-break-after: always !important; 
                page-break-inside: avoid !important;
                overflow: visible !important; 
            }

            .hoja.paisaje:last-of-type {
                page-break-after: auto !important;
            }
            
            .pagina-2-flex {
                display: flex !important;
                flex-direction: column !important;
                min-height: 7.5in !important;
            }

            .pie-pagina-bariven {
                display: block; width: 100%; margin-top: auto; border-top: 2px solid #000; padding-top: 5px; padding-bottom: 0 !important;
            }
            
            tr { page-break-inside: avoid !important; }
            .campo-editable-linea { border: none !important; color: black !important;}
            .td-con-fondo { background-color: transparent !important; }
            th[style*="background-color: #d1d5db"] { background-color: #d1d5db !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
                <i class="ph ph-arrow-left"></i> Volver a Licitaciones
            </a>
            <a href="index.php" class="nav-item" style="margin-top: 15px;">
                <i class="ph ph-arrows-left-right"></i> Cambiar Empresa
            </a>
        </nav>
    </aside>

    <main class="main-content" style="padding: 0;">
        <div class="editor-container">
            
            <!-- Barra Flotante Absoluta (Siempre Visible) -->
            <div class="toolbar">
                <!-- Botones para agregar filas -->
                <button id="btn-agregar" class="btn-agregar" onclick="agregarFila()"><i class="ph-bold ph-plus"></i> Fila</button>
                <button id="btn-quitar" class="btn-quitar" onclick="quitarFila()"><i class="ph-bold ph-minus"></i> Fila</button>

                <button id="btn-guardar" class="btn-guardar" onclick="guardarCambios()"><i class="ph-bold ph-floppy-disk"></i> Guardar</button>
                <button class="btn btn-excel" onclick="exportarExcel()"><i class="ph-bold ph-file-xls"></i> Exportar a Excel</button>
                <button class="btn btn-pdf" onclick="window.print()"><i class="ph-bold ph-file-pdf"></i> Imprimir a PDF</button>
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
                    
                    document.getElementById('btn-agregar').style.display = "inline-flex";
                    document.getElementById('btn-quitar').style.display = "inline-flex";
                    
                    Swal.fire({ icon: 'success', title: 'Modo edición activado', showConfirmButton: false, timer: 1500 });
                } else if (result.isConfirmed) {
                    Swal.fire({ icon: 'error', title: 'Contraseña incorrecta', showConfirmButton: false, timer: 1500 });
                }
            });
        }

        // ==========================================
        // FUNCIONES PARA AGREGAR / QUITAR FILAS
        // ==========================================
        function agregarFila() {
            let tablas = document.querySelectorAll('.tabla-oferta');
            if(tablas.length === 0) return;
            
            // Agregamos la fila en la ÚLTIMA tabla (Página 2)
            let ultimaTabla = tablas[tablas.length - 1];
            let tbody = ultimaTabla.querySelector('tbody');
            
            if(!tbody || tbody.children.length === 0) return;
            
            // Buscamos todas las filas de oferta
            let filasOferta = tbody.querySelectorAll('.fila-oferta');
            if(filasOferta.length === 0) return;

            let ultimaFila = filasOferta[filasOferta.length - 1];
            let nuevaFila = ultimaFila.cloneNode(true);
            
            let numTexto = ultimaFila.cells[0].innerText.trim();
            let numActual = parseInt(numTexto) || 0;
            nuevaFila.cells[0].innerHTML = '<b>' + (numActual + 1) + '</b>';
            
            // Limpiamos celdas editables
            for(let i=1; i<nuevaFila.cells.length; i++) {
                nuevaFila.cells[i].innerHTML = ''; 
                nuevaFila.cells[i].setAttribute('contenteditable', 'true');
            }
            
            tbody.appendChild(nuevaFila);
        }

        function quitarFila() {
            let tablas = document.querySelectorAll('.tabla-oferta');
            if(tablas.length === 0) return;
            
            let ultimaTabla = tablas[tablas.length - 1];
            let tbody = ultimaTabla.querySelector('tbody');
            let filasOferta = tbody.querySelectorAll('.fila-oferta');

            if (filasOferta.length > 1) {
                tbody.removeChild(filasOferta[filasOferta.length - 1]);
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
                    Swal.fire({ icon: 'success', title: 'Cambios guardados', showConfirmButton: false, timer: 1500 })
                    .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error al guardar', text: data.message });
                }
            });
        }

        function exportarExcel() {
            let htmlExcel = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"></head><body>`;
            
            // Unimos ambas tablas para el excel
            let tablas = document.querySelectorAll('.tabla-oferta');
            let tablaMaestra = document.createElement('table');
            tablaMaestra.border = '1';
            tablaMaestra.appendChild(tablas[0].querySelector('thead').cloneNode(true));
            let tbody = document.createElement('tbody');

            tablas.forEach(tabla => {
                let clon = tabla.cloneNode(true);
                clon.querySelectorAll('[contenteditable="true"]').forEach(ed => { ed.innerHTML = ed.innerText; });
                let filas = clon.querySelectorAll('tbody tr');
                filas.forEach(fila => tbody.appendChild(fila));
            });
            tablaMaestra.appendChild(tbody);

            // Limpiamos los saltos de línea de los encabezados verticales para excel
            tablaMaestra.querySelectorAll('.vertical-text').forEach(v => { v.innerText = v.innerText.replace(/<br>/g, ' '); });

            htmlExcel += '<table border="1">' + tablaMaestra.innerHTML + '</table><br>';
            htmlExcel += '</body></html>';

            let blob = new Blob(['\ufeff', htmlExcel], {type: 'application/vnd.ms-excel'});
            let url = URL.createObjectURL(blob);
            let a = document.createElement('a'); a.href = url; a.download = 'Estructura_Costos_II3.xls'; a.click();
        }
    </script>
</body>
</html>