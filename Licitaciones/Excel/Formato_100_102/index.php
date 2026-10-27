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
    $archivo_json = 'formato_100_102_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'documento_data_v35.json'; // Nombre V35
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

    // Celdas editables invisibles que se iluminan al hacer clic
    $td_edit = '<td class="td-con-fondo" contenteditable="true"></td>';
    $td_edit_center = '<td class="td-con-fondo" contenteditable="true" style="text-align: center;"></td>';

    $html_contenido = '';

    // =====================================================================
    // PÁGINA 1: FORMATO II.8 - ESTRUCTURA DE COSTO
    // =====================================================================
    $html_contenido .= '<div class="hoja">';
    $html_contenido .= $encabezado;

    $html_contenido .= '
        <div class="titulo" style="font-size: 10pt; margin-top: 10px;">
            FORMATO II.8. - ESTRUCTURA DE COSTO APLICANDO ARANCELES<br><br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable-linea" contenteditable="true" style="min-width: 40px;">0394</span><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
        </div>
        
        <table class="tabla-oferta">
            <colgroup>
                <col style="width: 5%;">
                <col style="width: 7%;">
                <col style="width: 38%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="2" style="text-align: left; padding-left: 5px !important; border-right: none !important;">TÉRMINOS<br><br><span style="font-size: 6pt; font-weight: normal;">Abreviatura</span></th>
                    <th style="border-left: none !important;"></th>
                    <th style="background-color: #d1d5db !important; font-size: 7.5pt !important;">VALORES EN ABSOLUTO EN<br>(DIVISA)</th>
                    <th style="background-color: #d1d5db !important; font-size: 7.5pt !important;">VALORES EN ABSOLUTO EN<br>(MONEDA NACIONAL)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><b>A</b></td><td></td><td style="text-align: left;">VALOR DE LA MERCANCIA A PUERTA DE FABRICA</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>B</b></td><td>Desc.</td><td style="text-align: left;">DESCUENTO DE FABRICA (EXPRESADO EN PORCENTAJE)</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>C</b></td><td>EMP.</td><td style="text-align: left;">EMPAQUE Y MANEJO</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>D</b></td><td>FOB</td><td style="text-align: left;">VALOR DE LA MERCANCIA EN LA NAVE EN EL PUERTO DE SALIDA (DESDE HASTA C) FOB)</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>E</b></td><td>FLETE</td><td style="text-align: left;">FLETE DESDE EL PUERTO DE SALIDA A PUERTO VENEZOLANO</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>F</b></td><td>SEGURO</td><td style="text-align: left;">SEGURO PARA CUBRIR LA MERCANCIA</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>G</b></td><td>CIF</td><td style="text-align: left;">SUMA DESDE (D) HASTA (F) TOTAL COSTO COMPONENTE DE IMPORTACION - C.I.F (XX)</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>H</b></td><td>TA.</td><td style="text-align: left;">TASA DEL SERVICIO DE ADUANA (XXX)</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>I</b></td><td>GA.</td><td style="text-align: left;">GRAVAMEN ARANCELARIO (XXX)</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>J</b></td><td>CA.</td><td style="text-align: left;">COMISION AGENTE ADUANAL (XXX)</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>K</b></td><td>TR.</td><td style="text-align: left;">TRANSPORTE ADUANA ALMACEN IMPUTABLE A LA MERCANCIA (XXX)</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>L</b></td><td>GF.</td><td style="text-align: left;">GASTOS DE FINANCIAMIENTO (XXX)</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>M</b></td><td>GA.</td><td style="text-align: left;">GASTOS ADMINISTRATIVOS (XXX)</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>N</b></td><td>CUT.</td><td style="text-align: left;">COSTO UNITARIO TOTAL (XXX)</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>O</b></td><td>UTIL.</td><td style="text-align: left;">UTILIDAD BENEFICIO (XXX)</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>P</b></td><td>TPCN</td><td style="text-align: left;">(N+O) TOTAL, PRECIO COMPONENTE NACIONAL XXX</td>' . $td_edit . $td_edit . '</tr>
                <tr><td><b>Q</b></td><td>PVC</td><td style="text-align: left;">TOTAL, PRECIO VENTA COMPONENTE XXX</td>' . $td_edit . $td_edit . '</tr>
            </tbody>
        </table>

        <table class="tabla-oferta">
            <colgroup>
                <col style="width: 5%;">
                <col style="width: 7%;">
                <col style="width: 38%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
            </colgroup>
            <tbody>
                <tr>
                    <td style="border: none !important;"></td>
                    <td style="border: none !important;"></td>
                    <td style="text-align: right; border: none !important; padding-right: 10px !important;">FACTOR</td>
                    <td style="background-color: #d1d5db !important; border: 1px solid #000 !important;"></td>
                    <td style="border: none !important;"></td>
                </tr>
                <tr>
                    <td style="border-top: 1px solid #000 !important; border-left: 1px solid #000 !important;"><b>R</b></td>
                    <td style="border-top: 1px solid #000 !important;">CI</td>
                    <td style="text-align: left; border-top: 1px solid #000 !important; border-right: 1px solid #000 !important;">COMPONENTE IMPORTADO (CI)</td>
                    <td style="text-align: center; border: 1px solid #000 !important;">1</td>
                    <td style="border: none !important;"></td>
                </tr>
                <tr>
                    <td style="border-left: 1px solid #000 !important;"><b>S</b></td>
                    <td>FI</td>
                    <td style="text-align: left; border-right: 1px solid #000 !important;">FACTOR IMPORTACION (FI)</td>
                    ' . $td_edit_center . '
                    <td style="text-align: center; font-size: 7.5pt !important; border: 1px solid #000 !important;">POSICIÓN EN DIVISA</td>
                </tr>
                <tr>
                    <td style="border-left: 1px solid #000 !important;"><b>T</b></td>
                    <td>FN</td>
                    <td style="text-align: left; border-right: 1px solid #000 !important;">FACTOR NACIONAL (FN)</td>
                    ' . $td_edit_center . '
                    <td style="text-align: center; font-size: 7.5pt !important; border: 1px solid #000 !important;">POSICIÓN EN MONEDA<br>NACIONAL</td>
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #000 !important; border-left: 1px solid #000 !important;"><b>U</b></td>
                    <td style="border-bottom: 1px solid #000 !important;">FM</td>
                    <td style="text-align: left; border-bottom: 1px solid #000 !important; border-right: 1px solid #000 !important;">TOTAL, FACTOR MULTIPLICADOR (R+S+T)</td>
                    <td style="border-bottom: 1px solid #000 !important;"></td>
                    <td style="text-align: center; border: 1px solid #000 !important;">TOTAL</td>
                </tr>
            </tbody>
        </table>

        <div style="font-size: 8.5pt; margin-top: 15px; line-height: 1.5;">
            Tasa cambiaria para el cálculo de la base imponible de los tributos aduaneros:<br>
            <b>Proveedores exclusivos en el territorio nacional de insumos especializados a PDVSA: Tasa de cambio Oficial emitida por el Banco Central de Venezuela.</b><br>
            <b>Demás proveedores: considerar el tipo de cambio correspondiente al origen de las divisas.</b><br>
            FLETE = % FLETE * FOB
        </div>
    </div>';

    // =====================================================================
    // PÁGINA 2: LEYENDAS Y FIRMAS DEL FORMATO II.8
    // =====================================================================
    $html_contenido .= '<div class="hoja">';
    $html_contenido .= $encabezado;

    $html_contenido .= '
        <div style="font-size: 9pt; line-height: 1.6; margin-top: 20px;">
            SEGURO = % SEGURO * FOB<br>
            UTIL = % UTIL * CUT<br>
            <b>LEYENDA:</b><br>
            <i>F.O.B. = FREE ON BOARD (PUERTO DE CARGA CONVENIDO)</i><br>
            <i>C.I.F = F.O.B + SEGURO Y FLETE</i><br><br>
            <i><b>NOTA: Los gastos administrativos y la utilidad beneficio deben ser calculados únicamente en base al componente nacional.</b></i>
        </div>
        
        <div style="margin-top: 40px; font-size: 9pt; text-align: left;">
            En (ciudad), a los <span class="campo-editable-linea" style="min-width: 50px;" contenteditable="true"></span> 
            ( <span class="campo-editable-linea" style="min-width: 30px;" contenteditable="true"></span> ) días del mes de 
            <span class="campo-editable-linea" style="min-width: 120px;" contenteditable="true"></span> 
            20<span class="campo-editable-linea" style="min-width: 30px;" contenteditable="true"></span>.
        </div>
        
        <div class="firmas">
            <div class="linea-firma"></div>
            <b>FIRMA DEL REPRESENTANTE</b><br>
            C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
            SELLO DE LA CONTRATISTA
        </div>
    </div>';

    // =====================================================================
    // PÁGINA 3: FORMATO II.9 - PRECIO SUGERIDO REPUESTOS
    // =====================================================================
    $html_contenido .= '<div class="hoja">';
    $html_contenido .= $encabezado;

    $html_contenido .= '
        <div class="titulo" style="font-size: 10pt; margin-top: 10px;">
            FORMATO II.9. - FORMATO DE PRECIO SUGERIDO DE REPUESTOS<br><br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable-linea" contenteditable="true">0394</span><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
        </div>
        
        <table class="tabla-oferta" id="tabla-repuestos">
            <colgroup>
                <col style="width: 3%;">
                <col style="width: 5%;">
                <col style="width: 14%;">
                <col style="width: 5.5%;">
                <col style="width: 5.5%;">
                <col style="width: 5.5%;">
                <col style="width: 5.5%;">
                <col style="width: 5.5%;">
                <col style="width: 5.5%;">
                <col style="width: 5.5%;">
                <col style="width: 5.5%;">
                <col style="width: 5.5%;">
                <col style="width: 5.5%;">
                <col style="width: 5.5%;">
                <col style="width: 5.5%;">
                <col style="width: 12%;">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="3" style="border: none !important; background-color: white !important;"></th>
                    <th colspan="6" style="font-size: 6.5pt !important;">FACTOR DE IMPORTACIÓN (DIVISAS)</th>
                    <th colspan="7" style="font-size: 6.5pt !important;">FACTOR NACIONAL (MONEDA)</th>
                </tr>
                <tr>
                    <th class="td-vertical"><div class="vertical-text">Reng.</div></th>
                    <th class="td-vertical"><div class="vertical-text">CATÁLOGO</div></th>
                    <th class="td-vertical"><div class="vertical-text">DESCRIPCIÓN</div></th>
                    <th class="td-vertical"><div class="vertical-text">(A) COSTO DE FABRICA</div></th>
                    <th class="td-vertical"><div class="vertical-text">(B) EMPAQUE Y MANEJO</div></th>
                    <th class="td-vertical"><div class="vertical-text">C = (A+B) FOB</div></th>
                    <th class="td-vertical"><div class="vertical-text">(D) FLETE DESDE<br>PUERTO DE SALIDA</div></th>
                    <th class="td-vertical"><div class="vertical-text">(E) SEGURO CUBRIR<br>MERCANCIA</div></th>
                    <th class="td-vertical"><div class="vertical-text">F = (C+D+E)<br>FACTOR DIVISAS</div></th>
                    <th class="td-vertical"><div class="vertical-text">(G) TASA DE<br>ADUANA</div></th>
                    <th class="td-vertical"><div class="vertical-text">(H) GRAVAMEN<br>ARANCELARIO</div></th>
                    <th class="td-vertical"><div class="vertical-text">(I) COMISION AGENTE<br>ADUANAL</div></th>
                    <th class="td-vertical"><div class="vertical-text">(J) TRANSPORTE ADUANA<br>ALMACEN</div></th>
                    <th class="td-vertical"><div class="vertical-text">(K) GASTOS DE<br>FINANC. (MONEDA)</div></th>
                    <th class="td-vertical"><div class="vertical-text">(L) GASTOS<br>ADMINISTRATIVOS</div></th>
                    <th class="td-vertical"><div class="vertical-text">M= (G+H+I+J+K+L)<br>COSTO UNITARIO TOTAL</div></th>
                </tr>
            </thead>
            <tbody>';
            
    for ($i = 1; $i <= 3; $i++) {
        $html_contenido .= '<tr><td><b>' . $i . '</b></td>';
        for ($j = 0; $j < 15; $j++) {
            $html_contenido .= $td_edit;
        }
        $html_contenido .= '</tr>';
    }

    $html_contenido .= '
            </tbody>
        </table>
        
        <div style="margin-top: 30px; font-size: 9pt; text-align: left;">
            En (ciudad), a los <span class="campo-editable-linea" style="min-width: 50px;" contenteditable="true"></span> 
            ( <span class="campo-editable-linea" style="min-width: 30px;" contenteditable="true"></span> ) días del mes de 
            <span class="campo-editable-linea" style="min-width: 120px;" contenteditable="true"></span> 
            20<span class="campo-editable-linea" style="min-width: 30px;" contenteditable="true"></span>.
        </div>
        
        <div class="firmas">
            <div class="linea-firma"></div>
            <b>FIRMA DEL REPRESENTANTE</b><br>
            C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
            SELLO DE LA CONTRATISTA
        </div>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $nombre_empresa; ?> - Estructura Costos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        * { box-sizing: border-box !important; } 

        .editor-container { background-color: #e5e7eb; padding: 40px; display: block; min-height: 100vh; }
        
        .toolbar { position: sticky; top: 0; background: transparent; width: 100%; max-width: 11in; display: flex; justify-content: flex-end; align-items: center; z-index: 100; margin: 0 auto 20px auto; pointer-events: none;}
        
        /* HOJA TAMAÑO CARTA HORIZONTAL (LANDSCAPE) */
        .hoja { 
            background: white; 
            width: 11in; 
            min-height: 8.5in; 
            padding: 0.8in; 
            box-sizing: border-box; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.2); 
            margin: 0 auto 30px auto; 
            font-family: 'Arial', sans-serif; 
            font-size: 8pt; 
            line-height: 1.5; 
            outline: none; 
            position: relative; 
            page-break-after: always; 
            text-align: justify; 
            overflow: visible; 
        }

        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .logo-container img { width: 130px; cursor: default; }
        .info-fiscal { text-align: right; font-size: 7pt; line-height: 1.3; max-width: 60%; word-wrap: break-word;}
        
        .titulo { font-size: 10pt; font-weight: bold; text-align: center; margin-bottom: 15px; text-transform: uppercase; }

        /* TABLAS CON REGLAS ESTRICTAS ANTI-DESBORDE Y SIN WIDTHS EN LÍNEA */
        .tabla-oferta { 
            width: 100% !important; 
            max-width: 100% !important;
            margin: 15px auto !important;
            table-layout: fixed !important; 
            border-collapse: collapse !important;
            font-size: 6pt !important;
        }
        
        .tabla-oferta th, .tabla-oferta td { 
            padding: 3px !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            word-break: break-word !important; 
            border: 1px solid #000 !important;
            text-align: center; 
            vertical-align: middle;
        }
        .tabla-oferta th { background-color: white !important; font-weight: bold; }
        
        /* TEXTO VERTICAL PARA LA CABECERA DE LA HOJA 3 */
        .td-vertical { height: 130px; vertical-align: bottom; padding: 2px 1px !important; }
        .vertical-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: normal;
            max-height: 120px;
            font-size: 5.5pt;
            text-align: left;
            margin: 0 auto;
        }

        /* CELDAS Y LINEAS INVISIBLES HASTA QUE SE HACE CLIC (FOCUS) */
        .td-con-fondo { background-color: transparent !important; outline: none; cursor: text; }
        .td-con-fondo:focus, .td-con-fondo:active { background-color: #fef08a !important; }

        .campo-editable-linea { background-color: transparent; display: inline-block; padding: 0px 4px; border-bottom: 1px dashed #ca8a04; color: #111; outline: none; cursor: text; }
        .campo-editable-linea:focus { background-color: #fef08a; }

        .firmas { margin-top: 40px; text-align: left; }
        .linea-firma { width: 250px; border-bottom: 1px solid #000; margin-bottom: 5px; }

        .btn-guardar, .btn-pdf, .btn-excel, .btn-agregar, .btn-quitar { pointer-events: auto; font-weight: 600; cursor: pointer; border-radius: 8px; border: none; padding: 10px 15px; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }
        .btn-guardar { background-color: var(--color-green); color: white; display: none; }
        .btn-agregar { background-color: #3b82f6; color: white; display: none; margin-left: 10px;}
        .btn-quitar { background-color: #ef4444; color: white; display: none; margin-left: 5px;}
        .btn-excel { background-color: #10b981; color: white; margin-left: 10px;}
        .btn-pdf { background-color: #6b7280; color: white; margin-left: 10px;}
        .btn-guardar:hover, .btn-pdf:hover, .btn-excel:hover, .btn-agregar:hover, .btn-quitar:hover { opacity: 0.9; }

       /* LÓGICA DE IMPRESIÓN - CARTA VERTICAL */
        @page { 
            size: letter portrait; 
            margin: 1.2cm; 
        } 
        
        @media print {
            body { 
                font-family: 'Arial', sans-serif; 
                font-size: 8pt; 
                background: white; 
                margin: 0; 
                padding: 0; 
            }
            
            html, main, .main-content, .editor-container, #documento-editable { 
                display: block !important; 
                height: auto !important; 
                position: static !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                background: transparent !important; 
                overflow: visible !important;
            }
            
            .toolbar, .sidebar, .header { display: none !important; }
            
            .hoja { 
                width: 100% !important; 
                height: auto !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                box-shadow: none !important; 
                border: none !important; 
                
                page-break-after: auto !important;
                page-break-before: always !important; 
                page-break-inside: auto !important;
                
                overflow: visible !important; 
            }

            .hoja:first-of-type {
                page-break-before: auto !important; 
            }

            tr {
                page-break-inside: avoid !important;
            }

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
            let tabla = document.getElementById('tabla-repuestos');
            if(!tabla) return;
            
            let tbody = tabla.querySelector('tbody');
            let ultimaFila = tbody.lastElementChild;
            let nuevaFila = ultimaFila.cloneNode(true);
            
            // Obtener el número actual (de la primera celda) y sumarle 1
            let numActual = parseInt(ultimaFila.cells[0].innerText) || tbody.children.length;
            nuevaFila.cells[0].innerHTML = '<b>' + (numActual + 1) + '</b>';
            
            // Limpiar el contenido de las celdas editables para que nazca en blanco
            for(let i=1; i<nuevaFila.cells.length; i++){
                nuevaFila.cells[i].innerHTML = ''; 
            }
            
            tbody.appendChild(nuevaFila);
        }

        function quitarFila() {
            let tabla = document.getElementById('tabla-repuestos');
            if(!tabla) return;
            
            let tbody = tabla.querySelector('tbody');
            // Verificamos que quede al menos 1 fila
            if (tbody.children.length > 1) {
                tbody.removeChild(tbody.lastElementChild);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'No puedes eliminar todas las filas. Debe quedar al menos una.',
                    confirmButtonColor: '#059669'
                });
            }
        }

        // ==========================================
        // GUARDAR DATOS EN EL JSON
        // ==========================================
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
        // EXPORTACIÓN A EXCEL SIN TEXTOS VERTICALES
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
            `;

            let tablas = document.querySelectorAll('table');
            
            tablas.forEach((tabla, index) => {
                let clonTabla = tabla.cloneNode(true);
                
                let editables = clonTabla.querySelectorAll('[contenteditable="true"]');
                editables.forEach(ed => {
                    let textNode = document.createTextNode(ed.innerText.trim());
                    ed.parentNode.replaceChild(textNode, ed);
                });

                let verticales = clonTabla.querySelectorAll('.vertical-text');
                verticales.forEach(v => {
                    v.parentNode.innerHTML = v.innerHTML.replace(/<br>/g, ' ').replace(/(<([^>]+)>)/ig, ''); 
                });

                htmlExcel += '<table border="1" style="border-collapse: collapse; margin-bottom: 30px;">' + clonTabla.innerHTML + '</table><br>';
            });

            htmlExcel += '</body></html>';

            let blob = new Blob(['\ufeff', htmlExcel], {type: 'application/vnd.ms-excel'});
            let url = URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'Estructura_Costos_PDVSA.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>