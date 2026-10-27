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
    $archivo_json = 'formato_106_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'documento_data_v36.json'; // Archivo v36 para el Cronograma de Unifuerte
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
    // PÁGINA 1: ANEXO FORMATO II.11 - CRONOGRAMA DE ENTREGA
    // =====================================================================
    $html_contenido .= '<div class="hoja">';
    $html_contenido .= $encabezado;

    $html_contenido .= '
        <div class="titulo" style="font-size: 8.5pt; margin-top: 0px; margin-bottom: 5px;">
            ANEXO FORMATO II.11 - CRONOGRAMA DE ENTREGA<br><br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable-linea" contenteditable="true" style="min-width: 40px;">0394</span><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A<br>LA DIRECCIÓN DE PRODUCCIÓN"
        </div>
        
        <table class="tabla-oferta" id="tabla-cronograma">
            <colgroup>
                <col style="width: 12%;">
                <col style="width: 32%;">
                <col style="width: 16%;">
                <col style="width: 13.3%;">
                <col style="width: 13.3%;">
                <col style="width: 13.4%;">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="3">CRONOGRAMA DE EJECUCION</th>
                    <th colspan="1">FECHA</th>
                    <th colspan="2">
                        <span class="campo-editable-linea" contenteditable="true" style="min-width: 100px;">&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    </th>
                </tr>
                <tr>
                    <th rowspan="2" style="background-color: #d1d5db !important; font-size: 7.5pt !important;">NRO DE ACT</th>
                    <th rowspan="2" style="background-color: #d1d5db !important; font-size: 7.5pt !important;">DESCRIPCION DE LA<br>ACTIVIDAD</th>
                    <th rowspan="2" style="background-color: #d1d5db !important; font-size: 7.5pt !important;">TIEMPO EN<br>EJECUCION</th>
                    <th colspan="3" style="background-color: #d1d5db !important; font-size: 7.5pt !important;">Tx expresados en Días...</th>
                </tr>
                <tr>
                    <th style="background-color: #d1d5db !important; font-size: 7.5pt !important;">To</th>
                    <th style="background-color: #d1d5db !important; font-size: 7.5pt !important;">T1</th>
                    <th style="background-color: #d1d5db !important; font-size: 7.5pt !important;">T2</th>
                </tr>
            </thead>
            <tbody>
                <tr class="fila-lote">
                    <td class="num-lote"><b>1</b></td>
                    <td class="td-con-fondo" contenteditable="true"><b>LOTE 1</b></td>
                    <td class="td-con-fondo" contenteditable="true"><b>10</b></td>
                    <td class="td-con-fondo" contenteditable="true" style="background-color: #9ca3af !important; font-weight: bold; color: white;">10</td>
                    ' . $td_edit . $td_edit . '
                </tr>
                <tr class="fila-lote">
                    <td class="num-lote"><b>2</b></td>
                    <td class="td-con-fondo" contenteditable="true"><b>LOTE 2</b></td>
                    <td class="td-con-fondo" contenteditable="true"><b>20</b></td>
                    ' . $td_edit . '
                    <td class="td-con-fondo" contenteditable="true" style="background-color: #9ca3af !important; font-weight: bold; color: white;">20</td>
                    ' . $td_edit . '
                </tr>
                <tr class="fila-lote">
                    <td class="num-lote"><b>3</b></td>
                    <td class="td-con-fondo" contenteditable="true"><b>LOTE 3</b></td>
                    <td class="td-con-fondo" contenteditable="true"><b>30</b></td>
                    ' . $td_edit . $td_edit . '
                    <td class="td-con-fondo" contenteditable="true" style="background-color: #9ca3af !important; font-weight: bold; color: white;">30</td>
                </tr>
                
                <!-- FILAS INFERIORES FIJAS -->
                <tr id="fila-firma">
                    <td colspan="3" style="height: 35px; border-right: 1px solid #000 !important;"></td>
                    <td colspan="3" style="vertical-align: bottom; font-weight: bold; font-size: 7.5pt !important; padding-bottom: 5px !important;">FIRMA Y SELLO DE LA EMPRESA</td>
                </tr>
                <tr id="fila-observaciones">
                    <td colspan="6" style="text-align: left; padding: 5px 8px !important; font-size: 7pt !important; line-height: 1.2;">
                        <i>OBSERVACIONES COMPLEMENTARIAS:</i><br>
                        <b>1)</b> Los lapsos de entrega comenzarán a correr a partir de la firma del contrato.<br>
                        <b>2)</b> Forma de transporte: <span class="campo-editable-linea" contenteditable="true" style="min-width: 250px;"></span><br>
                        <b>3)</b> En cada entrega, los productos se despacharán y llegarán en varios lotes, para la mejor producción, retiramiento, inspección y distribución.<br>
                        <b>4)</b> Cualquier otra condición particular:<br>
                        <span class="campo-editable-linea" contenteditable="true" style="width: 100%; min-height: 10px; display: inline-block; margin-top: 2px;"></span>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: auto; border-top: 2px solid #000; padding-top: 5px; text-align: center; font-weight: bold; font-size: 7.5pt; width: 100%;">
            -BARIVEN, S.A.-
            <span style="float: right; font-weight: normal; font-size: 6.5pt;">FAF:2025-07-15</span>
        </div>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Cronograma de Entrega - <?php echo $nombre_empresa; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        * { box-sizing: border-box !important; }

        .editor-container { background-color: #e5e7eb; padding: 40px; display: block; min-height: 100vh; }
        .toolbar { position: sticky; top: 0; background: transparent; width: 100%; max-width: 11in; display: flex; justify-content: flex-end; align-items: center; z-index: 100; margin: 0 auto 15px auto; pointer-events: none;}
        
        /* HOJA TAMAÑO CARTA HORIZONTAL (Flexbox para el footer) */
        .hoja { 
            background: white; 
            width: 11in;  
            min-height: 7.5in; 
            padding: 0.4in 0.5in; 
            margin: 0 auto 30px auto; 
            font-family: 'Arial', sans-serif; 
            font-size: 8pt; 
            line-height: 1.3; 
            outline: none; 
            display: flex; 
            flex-direction: column; 
            page-break-after: avoid; 
            text-align: justify; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.2); 
            overflow: visible; 
        }

        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 5px; }
        .logo-container img { width: 110px; cursor: default; }
        .info-fiscal { text-align: right; font-size: 7pt; line-height: 1.2; max-width: 65%; }
        .titulo { font-size: 9pt; font-weight: bold; text-align: center; margin-bottom: 10px; }
        
        /* TABLA */
        .tabla-oferta { width: 100% !important; max-width: 100% !important; border-collapse: collapse !important; table-layout: fixed !important; margin-top: 5px; margin-bottom: 10px; }
        .tabla-oferta th, .tabla-oferta td { border: 1px solid #000 !important; padding: 4px !important; text-align: center; vertical-align: middle; font-size: 7.5pt !important; word-wrap: break-word !important; overflow-wrap: break-word !important; word-break: break-word !important; white-space: normal !important; }
        .tabla-oferta th { background-color: white !important; font-weight: bold; }
        
        .td-con-fondo { background-color: transparent !important; outline: none; cursor: text; }
        .td-con-fondo:focus, .td-con-fondo:active { background-color: #fef08a !important; }

        .campo-editable-linea { background-color: transparent; display: inline-block; padding: 0px 4px; border-bottom: 1px dashed #ca8a04; color: #111; outline: none; cursor: text; }
        .campo-editable-linea:focus { background-color: #fef08a; }

        .btn-guardar, .btn-pdf, .btn-excel, .btn-agregar, .btn-quitar { pointer-events: auto; font-weight: 600; cursor: pointer; border-radius: 8px; border: none; padding: 10px 15px; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }
        .btn-guardar { background-color: var(--color-green); color: white; display: none; margin-left: 10px;}
        .btn-agregar { background-color: #3b82f6; color: white; display: none; margin-left: 10px;}
        .btn-quitar { background-color: #ef4444; color: white; display: none; margin-left: 5px;}
        .btn-excel { background-color: #10b981; color: white; margin-left: 10px;}
        .btn-pdf { background-color: #6b7280; color: white; margin-left: 10px;}
        .btn-guardar:hover, .btn-pdf:hover, .btn-excel:hover, .btn-agregar:hover, .btn-quitar:hover { opacity: 0.9; }

        /* IMPRESIÓN FORZADA 1 SOLA HOJA CARTA HORIZONTAL */
        @page { 
            size: letter landscape; 
            margin: 0.5in; 
        } 
        
        @media print {
            body { font-family: 'Arial', sans-serif; font-size: 8pt; background: white; margin: 0; padding: 0; }
            html, main, .main-content, .editor-container, #documento-editable { display: block !important; margin: 0 !important; padding: 0 !important; background: transparent !important; }
            .toolbar, .sidebar, .header { display: none !important; }
            
            .hoja { 
                width: 100% !important; 
                min-height: 7.5in !important; 
                height: auto !important;
                margin: 0 !important; 
                padding: 0 !important; 
                box-shadow: none !important; 
                border: none !important; 
                display: flex !important; 
                flex-direction: column !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important; 
                page-break-inside: avoid !important;
                overflow: visible !important; 
            }

            .campo-editable-linea { border: none !important; color: black !important;}
            .td-con-fondo { background-color: transparent !important; }
            .tabla-oferta th { -webkit-print-color-adjust: exact; print-color-adjust: exact;}
            
            td[style*="background-color: #9ca3af"] { background-color: #9ca3af !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
                <div style="display:flex; gap: 5px;">
                    <!-- Botones de Acciones Dinámicas para las Filas -->
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
            let tabla = document.getElementById('tabla-cronograma');
            if(!tabla) return;
            
            let tbody = tabla.querySelector('tbody');
            // Buscamos todas las filas que son de lote (evitamos clonar la firma)
            let filasLote = tbody.querySelectorAll('.fila-lote');
            if(filasLote.length === 0) return;
            
            let ultimaFila = filasLote[filasLote.length - 1];
            let nuevaFila = ultimaFila.cloneNode(true);
            
            // Incrementamos el número del lote
            let numActual = filasLote.length;
            nuevaFila.querySelector('.num-lote').innerHTML = '<b>' + (numActual + 1) + '</b>';
            
            // Limpiamos los textos editables para que el usuario escriba
            let celdasEditables = nuevaFila.querySelectorAll('.td-con-fondo');
            celdasEditables.forEach((celda, index) => {
                if(index === 0) {
                    celda.innerHTML = '<b>LOTE ' + (numActual + 1) + '</b>';
                } else {
                    celda.innerHTML = '';
                    // Quitamos el color de fondo gris si se clonó de una celda gris, para que quede limpia
                    celda.style.backgroundColor = 'transparent';
                    celda.style.color = '#111';
                }
            });
            
            // Insertamos la nueva fila justo ANTES de la fila de firmas
            let filaFirma = document.getElementById('fila-firma');
            tbody.insertBefore(nuevaFila, filaFirma);
        }

        function quitarFila() {
            let tabla = document.getElementById('tabla-cronograma');
            if(!tabla) return;
            
            let tbody = tabla.querySelector('tbody');
            let filasLote = tbody.querySelectorAll('.fila-lote');
            
            // Verificamos que quede al menos 1 fila
            if (filasLote.length > 1) {
                let ultimaFila = filasLote[filasLote.length - 1];
                tbody.removeChild(ultimaFila);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'No puedes eliminar todas las filas. Debe quedar al menos una.',
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
                        td { vertical-align: middle; padding: 4px; border: 1px solid #000; font-family: Arial, sans-serif; font-size: 8pt;} 
                        th { text-align: center; border: 1px solid #000; font-family: Arial, sans-serif; font-size: 8pt; }
                    </style>
                </head>
                <body>
            `;

            let tablas = document.querySelectorAll('table');
            
            tablas.forEach((tabla, index) => {
                let clonTabla = tabla.cloneNode(true);
                
                let editables = clonTabla.querySelectorAll('[contenteditable="true"]');
                editables.forEach(ed => {
                    let textNode = document.createTextNode(ed.innerText.trim());
                    ed.parentNode.replaceChild(textNode, ed);
                });

                htmlExcel += '<table border="1" style="border-collapse: collapse; margin-bottom: 20px;">' + clonTabla.innerHTML + '</table><br>';
            });

            htmlExcel += '</body></html>';

            let blob = new Blob(['\ufeff', htmlExcel], {type: 'application/vnd.ms-excel'});
            let url = URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'Cronograma_Entrega_PDVSA.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>