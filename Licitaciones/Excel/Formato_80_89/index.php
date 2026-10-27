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
    $archivo_json = 'formato_80_89_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'documento_data_v38.json'; // Nombre V38 Original
}

$html_contenido = '';

if(file_exists($archivo_json)) {
    $json_data = json_decode(file_get_contents($archivo_json), true);
    $html_contenido = $json_data['html'] ?? '';
}

// Datos de las 29 posiciones
$items = [
    1 => ['S', 11], 2 => ['M', 72], 3 => ['L', 135],
    4 => ['XL', 148], 5 => ['2XL', 60], 6 => ['3XL', 15],
    7 => ['4XL', 5], 8 => ['5XL', 4], 9 => ['GORRA', 450],
    10 => ['BOLSA', 450], 11 => ['XS', 4], 12 => ['S', 47],
    13 => ['M', 165], 14 => ['L', 228], 15 => ['XL', 160],
    16 => ['2XL', 36], 17 => ['3XL', 9], 18 => ['4XL', 4],
    19 => ['5XL', 1], 20 => ['6XL', 1], 21 => ['GORRA', 655],
    22 => ['BOLSA', 655], 23 => ['M', 16], 24 => ['L', 103],
    25 => ['XL', 167], 26 => ['2XL', 31], 27 => ['3XL', 10],
    28 => ['GORRA', 327], 29 => ['BOLSA', 327]
];

function getData($type) {
    if ($type === 'GORRA') {
        return [
            'mat' => 'GORRA DEPORTIVA',
            'desc' => '<b>GORRA TIPO BEISBOL COLOR NEGRO</b> PERSONALIZACIÓN EN BORDADO PLANO: LOGO EN EL FRENTE (PDVSA GAS) EN COLOR BLANCO LADO LATERAL DERECHO (DIRECCIÓN DE PRODUCCIÓN)'
        ];
    } else if ($type === 'BOLSA') {
        return [
            'mat' => 'BOLSAS ECOLOGICAS 35 CM ANCHO X 60 ALTO',
            'desc' => '<b>BOLSA TIPO ECOLOGICA COLOR BLANCO</b> TIMBRADA CON EL LOGO DE PDVSA GAS EN ROJO'
        ];
    } else {
        return [
            'mat' => 'CHAQUETA IMPERM CORTA VIENTOS TALLA ' . $type,
            'desc' => '<b>CHAQUETA IMPERMEABLE CORTAVIENTOS TALLA ' . $type . ':</b><br>CHAQUETA DEPORTIVA, MATERIAL 100% POLIESTER, COLOR NEGRO, TIPO CUELLO COMPLETO TIPO TORTUGA, LOGITUD DE MANGA 65, TIPO DE MANGA LARGA, TALLA ' . $type . ', COLOR DEL EMBLEMA VER ESPECIFICACIONES ANEXAS, DIMENSIÓN BOLSILLOS 2, DISEÑO VER ESPECIFICACIONES ANEXAS.<br>LOGO PDVSA GAS FRONTAL LADO IZQUIERDO BORDADO EN BLANCO DIRECCIÓN DE PRODUCCIÓN FRONTAL LADO DERECHO BORDADO BLANCO'
        ];
    }
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

    // Paginación exacta de las imágenes (8 páginas para los 29 ítems)
    $pages = [
        [1, 2, 3],        // Pág 1
        [4, 5, 6],        // Pág 2
        [7, 8, 9, 10, 11],// Pág 3
        [12, 13, 14],     // Pág 4
        [15, 16, 17],     // Pág 5
        [18, 19, 20, 21], // Pág 6
        [22, 23, 24, 25], // Pág 7
        [26, 27, 28, 29]  // Pág 8
    ];

    $html_contenido = '';

    // =====================================================================
    // PÁGINAS 1 a 8: FORMATO II.2 - OFERTA ECONÓMICA
    // =====================================================================
    foreach ($pages as $index => $page_items) {
        $html_contenido .= '<div class="hoja">';
        $html_contenido .= $encabezado;

        if ($index == 0) {
            $html_contenido .= '
            <div class="titulo" style="font-size: 10pt; margin-top:0;">
                FORMATO II.2 - FORMATO DE PRESENTACIÓN DE LA OFERTA ECONÓMICA<br><br>
                PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable-linea" contenteditable="true" style="min-width: 40px;">0394</span><br>
                REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"<br><br>
                <u style="font-size: 11pt;">ADQUISICIÓN DE BIENES</u><br><br>
                <u style="font-size: 9.5pt; font-weight: normal;">VALIDEZ DE LA OFERTA: NOVENTA DIAS HABILES (90 días)</u>
            </div>';
        } else {
            $html_contenido .= '<div style="height: 20px;"></div>'; 
        }

        // Agregamos la clase tabla-items para que el JS sepa dónde agregar filas
        $html_contenido .= '<table class="tabla-oferta tabla-excel tabla-items">';
        $html_contenido .= '
            <colgroup>
                <col style="width: 4%;">
                <col style="width: 7%;">
                <col style="width: 17%;">
                <col style="width: 35%;">
                <col style="width: 5%;">
                <col style="width: 6%;">
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 10%;">
            </colgroup>
            <thead>
                <tr>
                    <th style="background-color: #4b5563 !important; color: white !important;">POS.</th>
                    <th style="background-color: #4b5563 !important; color: white !important;">CÓDIGO</th>
                    <th style="background-color: #4b5563 !important; color: white !important;">MATERIAL</th>
                    <th style="background-color: #4b5563 !important; color: white !important;">ESPECIFICACIONES<br>TÉCNICAS</th>
                    <th style="background-color: #4b5563 !important; color: white !important;">CANT</th>
                    <th style="background-color: #4b5563 !important; color: white !important;">UNIDAD<br>DE<br>MEDIDA</th>
                    <th style="background-color: #4b5563 !important; color: white !important;">PRECIO<br>UNITARIO<br>(MONEDA)</th>
                    <th style="background-color: #4b5563 !important; color: white !important;">IVA<br>SOBRE<br>PRECIO<br>UNITARIO<br>(MONEDA)</th>
                    <th style="background-color: #4b5563 !important; color: white !important;">PRECIO<br>TOTAL<br><br>(MONEDA)</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($page_items as $pos) {
            $type = $items[$pos][0];
            $qty = $items[$pos][1];
            $data = getData($type);

            $html_contenido .= '<tr>';
            $html_contenido .= '<td><b>' . $pos . '</b></td>';
            $html_contenido .= $td_edit; // CÓDIGO
            $html_contenido .= '<td class="td-con-fondo" contenteditable="true" style="font-size: 6pt !important; padding: 4px !important;">' . $data['mat'] . '</td>';
            $html_contenido .= '<td class="td-con-fondo td-desc" contenteditable="true">' . $data['desc'] . '</td>';
            $html_contenido .= '<td class="td-con-fondo" contenteditable="true"><b>' . $qty . '</b></td>';
            $html_contenido .= '<td class="td-con-fondo" contenteditable="true">UND</td>';
            $html_contenido .= $td_edit; // PRECIO UNITARIO
            $html_contenido .= $td_edit; // IVA
            $html_contenido .= $td_edit; // PRECIO TOTAL
            $html_contenido .= '</tr>';
        }

        $html_contenido .= '</tbody></table>';

        if ($index == count($pages) - 1) {
            $html_contenido .= '
            <p style="font-size: 8pt; font-weight: bold; margin-top: 15px; line-height: 1.3;">
                <u>Nota:</u> En caso que la oferta sea parcial (no cotizan todas las posiciones), deben presentar solo la oferta de los renglones cotizados, pero conservando la numeración (posición) original establecida en el pliego de condiciones y/o condiciones de la contratación. <br>
                CADA PÁGINA PERTENECIENTE AL FORMATO DE OFERTA TÉCNICA DEBE ESTAR DEBIDAMENTE FIRMADA Y SELLADA POR LA EMPRESA, EN CASO CONTRARIO LA OFERTA SERÁ RECHAZADA. EN CASO DE REPUESTOS APLICA LO ESTABLECIDO EN EL ARTÍCULO 66 N° 4 DEL DRVFLCP.
            </p>
            
            <div style="margin-top: 20px; font-size: 9pt; text-align: left;">
                En (ciudad), a los <span class="campo-editable-linea" style="min-width: 50px;" contenteditable="true"></span> 
                ( <span class="campo-editable-linea" style="min-width: 30px;" contenteditable="true"></span> ) días del mes de 
                <span class="campo-editable-linea" style="min-width: 120px;" contenteditable="true"></span> 
                20<span class="campo-editable-linea" style="min-width: 30px;" contenteditable="true"></span>.
            </div>
            
            <div class="firmas" style="margin-top: 30px; font-size: 9pt; line-height: 1.6;">
                <u>FIRMA DEL REPRESENTANTE</u><br>
                <u>C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA</u><br>
                <u>SELLO DE LA CONTRATISTA</u>
            </div>';
        }

        $html_contenido .= '
            <div style="border-top: 2px solid #000; padding-top: 5px; text-align: center; font-weight: bold; font-size: 8pt; position: absolute; bottom: 30px; width: calc(100% - 1.6in);">
                -BARIVEN, S.A.-
                <span style="float: right; font-weight: normal; font-size: 7pt;">FAF:2025-07-15</span>
            </div>
        </div>';
    }

    // =====================================================================
    // PÁGINA 9: RESUMEN DE LA OFERTA - EMPRESAS MERCANTILES (Comprimida)
    // =====================================================================
    $html_contenido .= '<div class="hoja">';
    $html_contenido .= $encabezado;

    $html_contenido .= '
        <div class="titulo" style="font-size: 8.5pt; margin-top: 5px; line-height: 1.2;">
            RESUMEN DE LA OFERTA POR ELEMENTOS DE COSTOS PARA EMPRESAS MERCANTILES<br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable-linea" contenteditable="true" style="min-width: 40px;">0394</span><br><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"<br><br>
            <u style="font-size: 9pt;">PRESTACIÓN DE SERVICIOS Y/U OBRAS</u>
        </div>
        
        <p style="font-size: 7.5pt; margin-top: 6px;">La oferta para los trabajos descritos en el procedimiento indicado es por el precio total de <span class="campo-editable-linea" contenteditable="true" style="min-width: 250px;">(colocar monto en letras)</span> (<span class="campo-editable-linea" contenteditable="true" style="min-width: 100px;">colocar monto en números</span>)</p>
        
        <p style="font-size: 7.5pt; margin-top: 4px;">Discriminados de la siguiente forma:</p>
        
        <table class="tabla-oferta tabla-excel">
            <colgroup>
                <col style="width: 60%;">
                <col style="width: 40%;">
            </colgroup>
            <thead>
                <tr>
                    <th style="background-color: #9ca3af !important; color: white !important; font-size: 7.5pt !important;">ELEMENTOS DE COSTO</th>
                    <th style="background-color: #9ca3af !important; color: white !important; font-size: 7.5pt !important;">TOTAL (MONEDA)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td style="text-align: left; padding-left: 10px !important;">1. LABOR</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">2. MATERIALES</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">3. EQUIPOS</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">4. IMPLEMENTOS DE SEGURIDAD</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">5. GASTOS MÉDICOS</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">6. SUB-TOTAL ESTIMADO (1+2+3+4+5)</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">7. ADMINISTRACIÓN</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">8. UTILIDAD</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">9. SUB TOTAL OFERTA (6+7+8)</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">10. I.V.A (XX%)</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">11. TOTAL GENERAL (9+10)</td>' . $td_edit . '</tr>
            </tbody>
        </table>

        <div style="font-size: 7pt; margin-top: 8px; line-height: 1.25;">
            12. GASTOS REEMBOLSABLES: <span class="campo-editable-linea" contenteditable="true" style="min-width: 200px;"></span><br>
            13. TIEMPO DE EJECUCIÓN: <span class="campo-editable-linea" contenteditable="true" style="min-width: 150px;">(COLOCAR MONTO EN LETRAS)</span> (<span class="campo-editable-linea" contenteditable="true" style="min-width: 80px;">EN NÚMEROS</span>) DÍAS XXXXXX.<br>
            14. HORAS-HOMBRE: NÓMINA DIARIA: <span class="campo-editable-linea" contenteditable="true" style="min-width: 80px;"></span> TOTAL H/H: <span class="campo-editable-linea" contenteditable="true" style="min-width: 80px;"></span><br>
            15. RÉGIMEN LABORAL: <span class="campo-editable-linea" contenteditable="true" style="min-width: 100px;"></span> OTROS: <span class="campo-editable-linea" contenteditable="true" style="min-width: 100px;"></span><br>
            16. UBICACIÓN RÉGIMEN: CAMPO: <span class="campo-editable-linea" contenteditable="true" style="min-width: 50px;"></span> CIUDAD: <span class="campo-editable-linea" contenteditable="true" style="min-width: 50px;"></span> MIXTO <span class="campo-editable-linea" contenteditable="true" style="min-width: 50px;"></span><br>
            17. EL COSTO DE LA LABOR INCLUYE, SIN LIMITACIÓN SOBRETIEMPO, BENEFICIOS AL PERSONAL Y LOS EFECTOS DE LOS DECRETOS EMITIDOS POR EL GOBIERNO, VIGENTES A LA FECHA DE PRESENTACIÓN DE LA OFERTA.
        </div>

        <p style="font-size: 7.5pt; margin-top: 6px; text-align: justify;">
            Nos comprometemos a mantener esta cotización por un tiempo mínimo de validez de <span class="campo-editable-linea" contenteditable="true" style="min-width: 150px; font-weight:bold;">(colocar monto en letras)</span> <b>(<span class="campo-editable-linea" contenteditable="true" style="min-width: 80px;">números</span>) días</b> <span class="campo-editable-linea" contenteditable="true" style="min-width: 100px;"></span> a partir de la fecha de entrega definitiva de esta cotización.
        </p>

        <p style="font-size: 6.5pt; text-align: justify; margin-top: 6px; line-height: 1.2;">
            <b>NOTA:</b> Para todos los montos se debe considerar dos (2) decimales. | En caso de ser LOTTT o CCC No aplica el renglón Gastos Médicos.<br>
            En los casos de CCTP Régimen Ciudad no aplica el renglón Gastos Médicos.
        </p>

        <div style="margin-top: 8px; font-size: 7.5pt; text-align: left;">
            En (ciudad), a los <span class="campo-editable-linea" style="min-width: 50px;" contenteditable="true"></span> 
            ( <span class="campo-editable-linea" style="min-width: 30px;" contenteditable="true"></span> ) días del mes de 
            <span class="campo-editable-linea" style="min-width: 120px;" contenteditable="true"></span> 
            20<span class="campo-editable-linea" style="min-width: 30px;" contenteditable="true"></span>.
        </div>
        
        <div class="firmas" style="margin-top: 10px; font-size: 7.5pt; line-height: 1.3;">
            <u>FIRMA DEL REPRESENTANTE</u><br>
            <u>C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA</u><br>
            <u>SELLO DE LA CONTRATISTA</u>
        </div>

        <div style="border-top: 2px solid #000; padding-top: 5px; text-align: center; font-weight: bold; font-size: 8pt; position: absolute; bottom: 30px; width: calc(100% - 1.6in);">
            -BARIVEN, S.A.-
            <span style="float: right; font-weight: normal; font-size: 7pt;">FAF:2025-07-15</span>
        </div>
    </div>';

    // =====================================================================
    // PÁGINA 10: RESUMEN DE LA OFERTA - COOPERATIVAS (Comprimida)
    // =====================================================================
    $html_contenido .= '<div class="hoja">';
    $html_contenido .= $encabezado;

    $html_contenido .= '
        <div class="titulo" style="font-size: 8.5pt; margin-top: 5px; line-height: 1.2;">
            RESUMEN DE LA OFERTA POR ELEMENTOS DE COSTOS PARA COOPERATIVAS<br>
            PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable-linea" contenteditable="true" style="min-width: 40px;">0394</span><br><br>
            REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
        </div>
        
        <p style="font-size: 7.5pt; margin-top: 6px;">La oferta para los trabajos descritos en el procedimiento indicado es por el precio total de Bs. <span class="campo-editable-linea" contenteditable="true" style="min-width: 250px;">(colocar monto en letras)</span> (<span class="campo-editable-linea" contenteditable="true" style="min-width: 100px;">colocar monto en números</span>)</p>
        
        <p style="font-size: 7.5pt; margin-top: 4px;">Discriminados de la siguiente forma:</p>
        
        <table class="tabla-oferta tabla-excel">
            <colgroup>
                <col style="width: 60%;">
                <col style="width: 40%;">
            </colgroup>
            <thead>
                <tr>
                    <th style="background-color: #9ca3af !important; color: white !important; font-size: 7.5pt !important;">ELEMENTOS DE COSTO</th>
                    <th style="background-color: #9ca3af !important; color: white !important; font-size: 7.5pt !important;">TOTAL (MONEDA)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td style="text-align: left; padding-left: 10px !important;">1. ANTICIPO SOCIETARIO</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">2. MATERIALES</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">3. EQUIPOS</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">4. IMPLEMENTOS DE SEGURIDAD</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">5. GASTOS MÉDICOS</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">6. SUB-TOTAL ESTIMADO (1+2+3+4+5)</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">7. ADMINISTRACIÓN</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">8. EXCEDENTE</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">9. TOTAL OFERTA (6+7+8)</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">10. I.V.A (XX%)</td>' . $td_edit . '</tr>
                <tr><td style="text-align: left; padding-left: 10px !important;">11. TOTAL GENERAL (9+10)</td>' . $td_edit . '</tr>
            </tbody>
        </table>

        <div style="font-size: 7pt; margin-top: 8px; line-height: 1.25;">
            12. GASTOS REEMBOLSABLES: <span class="campo-editable-linea" contenteditable="true" style="min-width: 200px;"></span><br>
            13. TIEMPO DE EJECUCIÓN: <span class="campo-editable-linea" contenteditable="true" style="min-width: 150px;">(COLOCAR MONTO EN LETRAS)</span> (<span class="campo-editable-linea" contenteditable="true" style="min-width: 80px;">EN NÚMEROS</span>) DÍAS XXXXXX.<br>
            14. HORAS-HOMBRE: NÓMINA DIARIA: <span class="campo-editable-linea" contenteditable="true" style="min-width: 80px;"></span> TOTAL H/H: <span class="campo-editable-linea" contenteditable="true" style="min-width: 80px;"></span><br>
            15. UBICACIÓN RÉGIMEN: CAMPO: <span class="campo-editable-linea" contenteditable="true" style="min-width: 50px;"></span> CIUDAD: <span class="campo-editable-linea" contenteditable="true" style="min-width: 50px;"></span> MIXTO <span class="campo-editable-linea" contenteditable="true" style="min-width: 50px;"></span>
        </div>

        <p style="font-size: 7.5pt; margin-top: 6px; text-align: justify;">
            Nos comprometemos a mantener esta cotización por un tiempo mínimo de validez de <span class="campo-editable-linea" contenteditable="true" style="min-width: 150px; font-weight:bold;">(colocar monto en letras)</span> <b>(<span class="campo-editable-linea" contenteditable="true" style="min-width: 80px;">números</span>) días</b> <span class="campo-editable-linea" contenteditable="true" style="min-width: 100px;"></span> a partir de la fecha de entrega definitiva de esta cotización.
        </p>
        
        <div style="margin-top: 8px; font-size: 7.5pt; text-align: left;">
            En (ciudad), a los <span class="campo-editable-linea" style="min-width: 50px;" contenteditable="true"></span> 
            ( <span class="campo-editable-linea" style="min-width: 30px;" contenteditable="true"></span> ) días del mes de 
            <span class="campo-editable-linea" style="min-width: 120px;" contenteditable="true"></span> 
            20<span class="campo-editable-linea" style="min-width: 30px;" contenteditable="true"></span>.
        </div>
        
        <div class="firmas" style="margin-top: 10px; font-size: 7.5pt; line-height: 1.3;">
            <u>FIRMA DEL REPRESENTANTE</u><br>
            <u>C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA</u><br>
            <u>SELLO DE LA CONTRATISTA</u>
        </div>
        
        <p style="font-size: 6.5pt; text-align: left; margin-top: 6px; font-weight: bold;">
            NOTA: Para todos los montos se debe considerar dos (2) decimales.
        </p>

        <div style="border-top: 2px solid #000; padding-top: 5px; text-align: center; font-weight: bold; font-size: 8pt; position: absolute; bottom: 30px; width: calc(100% - 1.6in);">
            -BARIVEN, S.A.-
            <span style="float: right; font-weight: normal; font-size: 7pt;">FAF:2025-07-15</span>
        </div>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Oferta Económica - <?php echo $nombre_empresa; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        * { box-sizing: border-box !important; }

        .editor-container { background-color: #e5e7eb; padding: 40px; display: block; min-height: 100vh; }
        
        /* BARRA FLOTANTE */
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
        
        /* HOJA TAMAÑO CARTA HORIZONTAL (LANDSCAPE) */
        .hoja { 
            background: white; 
            width: 11in; 
            min-height: 8.5in; 
            padding: 0.8in; 
            margin: 0 auto 30px auto; 
            font-family: 'Arial', sans-serif; 
            font-size: 8pt; 
            line-height: 1.4; 
            outline: none; 
            position: relative; 
            page-break-after: always; 
            text-align: justify; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.2); 
            overflow: visible; 
        }

        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
        .logo-container img { width: 130px; cursor: default; } 
        .info-fiscal { text-align: right; font-size: 7.5pt; line-height: 1.2; max-width: 65%; }
        .titulo { font-size: 10pt; font-weight: bold; text-align: center; margin-bottom: 10px; }
        
        .tabla-oferta { width: 100% !important; max-width: 100% !important; border-collapse: collapse !important; table-layout: fixed !important; margin-top: 10px; }
        .tabla-oferta th, .tabla-oferta td { border: 1px solid #000 !important; padding: 4px 2px !important; text-align: center; vertical-align: middle; font-size: 6.5pt !important; word-wrap: break-word !important; overflow-wrap: break-word !important; word-break: break-word !important; white-space: normal !important; }
        .tabla-oferta th { font-weight: bold; font-size: 6.5pt !important; padding: 6px 2px !important;}
        .td-desc { text-align: justify !important; font-size: 6pt !important; line-height: 1.1; padding: 5px !important;}
        
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

        @page { 
            size: letter landscape; 
            margin: 0 !important; 
        } 

        @media print {
            html, body, main, .main-content, .editor-container, #documento-editable { 
                display: block !important; 
                position: static !important;
                width: 100% !important;
                height: auto !important; 
                min-height: auto !important;
                max-height: none !important;
                overflow: visible !important; 
                margin: 0 !important; 
                padding: 0 !important;
                background: transparent !important;
            }

            .toolbar, .sidebar, .header { 
                display: none !important; 
            }
            
            .hoja { 
                display: block !important;
                position: relative !important;
                width: 11in !important; 
                height: 8.45in !important; 
                max-height: 8.45in !important;
                margin: 0 !important; 
                padding: 0.55in 0.6in !important; 
                box-sizing: border-box !important;
                box-shadow: none !important; 
                border: none !important; 
                page-break-after: always !important; 
                page-break-inside: avoid !important;
                overflow: hidden !important; 
            }

            .hoja:last-of-type, .hoja:last-child {
                page-break-after: avoid !important;
            }

            .campo-editable-linea { border: none !important; color: black !important; }
            .td-con-fondo { background-color: transparent !important; }
            
            th[style*="background-color: #4b5563"] { background-color: #4b5563 !important; color: white !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            th[style*="background-color: #9ca3af"] { background-color: #9ca3af !important; color: white !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            
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
                    <!-- Botones para agregar filas -->
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
            // Buscamos todas las tablas que tienen la clase 'tabla-items' (páginas 1 a 8)
            let tablas = document.querySelectorAll('.tabla-items');
            if(tablas.length === 0) return;
            
            // Agregamos la fila en la ÚLTIMA tabla de items (Página 8)
            let ultimaTabla = tablas[tablas.length - 1];
            let tbody = ultimaTabla.querySelector('tbody');
            
            if(!tbody || tbody.children.length === 0) return;
            
            // Clonamos la última fila
            let ultimaFila = tbody.lastElementChild;
            let nuevaFila = ultimaFila.cloneNode(true);
            
            // Incrementamos el número de posición (columna 1)
            let numTexto = ultimaFila.cells[0].innerText.trim();
            let numActual = parseInt(numTexto) || 0;
            nuevaFila.cells[0].innerHTML = '<b>' + (numActual + 1) + '</b>';
            
            // Limpiamos y preparamos las demás celdas
            for(let i=1; i<nuevaFila.cells.length; i++) {
                nuevaFila.cells[i].innerHTML = ''; 
                nuevaFila.cells[i].setAttribute('contenteditable', 'true');
                nuevaFila.cells[i].style.backgroundColor = 'transparent';
                nuevaFila.cells[i].style.color = '#111';
            }
            
            tbody.appendChild(nuevaFila);
        }

        function quitarFila() {
            let tablas = document.querySelectorAll('.tabla-items');
            if(tablas.length === 0) return;
            
            let ultimaTabla = tablas[tablas.length - 1];
            let tbody = ultimaTabla.querySelector('tbody');
            
            // Verificamos que quede al menos 1 fila en la última tabla
            if (tbody && tbody.children.length > 1) {
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

            let tablas = document.querySelectorAll('.tabla-excel');
            
            // Unir las primeras 8 páginas (Oferta Económica) en una sola tabla de Excel
            let tablaMaestra = document.createElement('table');
            tablaMaestra.border = '1';
            tablaMaestra.style.borderCollapse = 'collapse';
            tablaMaestra.appendChild(tablas[0].querySelector('thead').cloneNode(true));
            let tbody = document.createElement('tbody');

            for(let i=0; i<8; i++) {
                if(tablas[i]) {
                    let clonTabla = tablas[i].cloneNode(true);
                    let editables = clonTabla.querySelectorAll('[contenteditable="true"]');
                    editables.forEach(ed => {
                        let textNode = document.createTextNode(ed.innerText.trim());
                        ed.parentNode.replaceChild(textNode, ed);
                    });
                    let filas = clonTabla.querySelectorAll('tbody tr');
                    filas.forEach(fila => tbody.appendChild(fila));
                }
            }
            tablaMaestra.appendChild(tbody);
            
            htmlExcel += '<h2 style="text-align: center;">FORMATO II.2 - OFERTA ECONÓMICA</h2><br>';
            htmlExcel += tablaMaestra.outerHTML + '<br><br>';

            // Agregar Tablas de Resumen Mercantil y Cooperativa
            for(let i=8; i<10; i++) {
                if(tablas[i]) {
                    let clonTabla = tablas[i].cloneNode(true);
                    let editables = clonTabla.querySelectorAll('[contenteditable="true"]');
                    editables.forEach(ed => {
                        let textNode = document.createTextNode(ed.innerText.trim());
                        ed.parentNode.replaceChild(textNode, ed);
                    });
                    
                    let tituloExtra = (i === 8) ? "RESUMEN EMPRESAS MERCANTILES" : "RESUMEN COOPERATIVAS";
                    htmlExcel += '<h2 style="text-align: center;">' + tituloExtra + '</h2><br>';
                    htmlExcel += clonTabla.outerHTML + '<br><br>';
                }
            }

            htmlExcel += `
                </body>
                </html>
            `;

            let blob = new Blob(['\ufeff' + htmlExcel], { type: 'application/vnd.ms-excel' });
            let url = URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'Oferta_Economica.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>