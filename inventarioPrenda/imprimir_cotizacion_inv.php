<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);

session_start();
require_once '../conexion.php';

if (!isset($_GET['id']) || !isset($_SESSION['usuario_id'])) {
    die("Acceso denegado: Faltan permisos o el ID de la nota.");
}

$id_nota = intval($_GET['id']);

$sql = "SELECT n.*, c.nombre AS cliente_nombre, c.cedula_Rif, c.direccion, c.tlf, c.email, c.comprador,
               u.nombre AS v_nombre, u.apellido AS v_apellido, u.email AS v_email
        FROM inv_fisico_notas n 
        JOIN cliente c ON n.id_cliente = c.ID 
        LEFT JOIN usuario u ON n.id_vendedor = u.ID
        WHERE n.id = $id_nota";

$res = mysqli_query($conexion, $sql);
$nota = mysqli_fetch_assoc($res);

if(!$nota) die("Documento no encontrado en la base de datos.");

$fecha_actual = date('d/m/Y', strtotime($nota['fecha_emision']));
$nro_nota = $nota['nro_nota']; 
$nro_cotizacion = str_replace('NE-INV-', 'COT-INV-', $nro_nota);

$sql_detalles = "SELECT d.cantidad, d.precio_unitario, d.subtotal, 
                        p.articulo, p.talla, p.genero, p.color, p.detalles_extra, p.codigo_lote,
                        IFNULL(m.nombre, 'Genérico') as marca_nombre
                 FROM inv_fisico_notas_detalle d 
                 JOIN inv_fisico_prendas p ON d.id_prenda = p.id 
                 LEFT JOIN inv_fisico_marcas m ON p.id_marca = m.id
                 WHERE d.id_nota = $id_nota";

$q_det = mysqli_query($conexion, $sql_detalles);

$detalles_lineales = array();
$sub_total = 0;

while($r = mysqli_fetch_assoc($q_det)) {
    $prenda = strtoupper((string)$r['articulo']);
    $color = !empty($r['color']) ? " - " . strtoupper($r['color']) : "";
    $talla = strtoupper(trim((string)$r['talla']));
    $gen = strtoupper((string)$r['genero']);
    $cant = intval($r['cantidad']);
    if ($cant <= 0) $cant = 1;
    
    $extra_info = "";
    if(!empty($r['detalles_extra'])) {
        $extra_json = json_decode($r['detalles_extra'], true);
        if(is_array($extra_json)) {
            $extra_arr = [];
            if(isset($extra_json['paneles'])) $extra_arr[] = $extra_json['paneles']." Paneles";
            if(isset($extra_json['tela'])) $extra_arr[] = "Tela: ".$extra_json['tela'];
            if(isset($extra_json['malla'])) $extra_arr[] = "Malla: ".$extra_json['malla'];
            if(isset($extra_json['broche'])) $extra_arr[] = "Broche: ".$extra_json['broche'];
            if(!empty($extra_arr)) { $extra_info = implode(" | ", $extra_arr); }
        }
    }
    
    $detalles_lineales[] = [
        'codigo' => $r['codigo_lote'],
        'cantidad' => $cant,
        'tipo_prenda' => $prenda . $color . " - Talla: " . $talla,
        'genero' => $gen,
        'detalle_limpio' => $extra_info,
        'precio_unitario' => $r['precio_unitario'],
        'subtotal_linea' => $r['subtotal']
    ];
    $sub_total += floatval($r['subtotal']);
}

function safe_html($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function formatoBs($num) { return number_format($num, 2, ',', '.'); }

$metodo_pago_texto = "Pago de Contado / Venta Inmediata";
$metodo_entrega = "Entrega Inmediata (Retiro en Sede)";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización <?= safe_html($nro_cotizacion) ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }
        body { background: #525659; margin: 0; font-family: 'Arial', sans-serif; color: #000; font-size: 11px; }

        .top-bar { background-color: #13131A; padding: 15px; text-align: center; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .btn-print { padding: 10px 25px; font-size: 14px; font-weight: bold; background: #FF7518; color: #FFF; border: none; cursor: pointer; border-radius: 8px; transition: 0.3s; }
        .btn-print:hover { background: #e05e00; }

        .vista-previa { width: 100%; overflow-x: auto; padding: 80px 20px 40px 20px; text-align: center; }
        #documento_pdf { width: 794px; max-width: 794px; margin: 0 auto; text-align: left; background: transparent; }
        
        .hoja-a4 { background: white; width: 100%; padding: 40px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); position: relative; min-height: 1123px; }

        @media print { 
            @page { size: letter portrait; margin: 0; }
            body { background: white !important; }
            .vista-previa { padding: 0 !important; }
            .hoja-a4 { box-shadow: none !important; margin: 0 !important; padding: 40px !important; }
            .no-print { display: none !important; } 
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 0; border: 1.5px solid #000; }
        th, td { border: 1.5px solid #000; padding: 4px 6px; vertical-align: middle; }
        
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .header-logo img { height: 70px; }
        .header-info { text-align: right; font-size: 9px; line-height: 1.3; font-weight: bold; color: #333; }
        
        .title-bar { display: flex; border: 1.5px solid #000; background-color: #d9d9d9; margin-bottom: 0; border-bottom: none; }
        .title-left { flex: 1; padding: 5px; font-size: 16px; font-weight: 900; text-align: center; letter-spacing: 1px; border-right: 1.5px solid #000; }
        .title-right { width: 150px; padding: 5px; font-size: 12px; text-align: center; color: #b30015; font-weight: bold; }

        .info-table td { font-size: 10px; background-color: #d9d9d9; font-weight: bold; width: 25%; text-transform: uppercase; }
        .info-table td.val { background-color: #fff; font-weight: normal; width: 75%; text-transform: uppercase; }
        
        .items-table th { background-color: #d9d9d9; font-size: 11px; text-align: center; font-style: italic; }
        .items-table td { font-size: 10px; height: 22px; }
        .items-table td.desc { text-align: left; font-weight: normal; font-size: 10px; }
        
        .totals-table { width: 100%; border-collapse: collapse; font-size: 12px; font-weight: bold; border:none; margin-top:10px;}
        .totals-table td { padding: 4px 8px; text-align: right; border: 1px solid #000; }
        .totals-table td.lbl { width: 60%; background-color:#f0f0f0; }
        
        .footer-notes { clear: both; width: 100%; border: 2px solid #000; border-collapse: collapse; margin-top: 20px; }
        .footer-notes th { border: 1px solid #000; background-color: #f0f0f0; padding: 4px; font-size: 12px; }
        .footer-notes td { border: 1px solid #000; padding: 4px; font-size: 10px; font-weight: bold; }
        
        .row-clear::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>

    <div class="top-bar no-print">
        <button id="btnDescargar" class="btn-print" onclick="descargarPDF()">📥 Descargar Cotización</button>
    </div>

    <div class="vista-previa">
        <div id="documento_pdf">
            <div class="hoja-a4" id="hoja-cotizacion">
                <div class="header-top" style="margin-bottom:0;">
                    <div class="header-logo"><img src="../img/unifuerte.jpg" alt="Unifuerte Logo"></div>
                    <div class="header-info">
                        UNIFUERTE, C.A<br>R.I.F: J-41282764-2 / R.U.C 1829-2021<br>SENCAMER 0012216TN-21<br>
                        CR. UD-321, EDIFICIO PARCELA 321-16-10A-1<br>PISO 1, OFICINA, A,B,C, ZONA INDUSTRIAL UD 321<br>
                        CIUDAD GUAYANA, PUERTO ORDAZ, ESTADO BOLIVAR<br>CONTACTO: 04241546889<br>
                        EMAIL: CLIENTEUNIFUERTE@GMAIL.COM - CLIENTEUNIFUERTE1@GMAIL.COM
                    </div>
                </div>

                <table style="width: 100%; border: 2px solid #000; border-collapse: collapse; text-align: center; margin-bottom: 10px; margin-top: 10px;">
                    <tr>
                        <td style="padding: 4px; border: 2px solid #000; font-size: 16px; font-weight: bold; background-color: #fff;">COTIZACIÓN INMEDIATA</td>
                        <td style="width: 120px; font-size: 14px; font-weight: bold; background-color: #fff;">Nro.<br><span style="font-size: 16px;"><?= htmlspecialchars(str_replace('COT-INV-', '', $nro_cotizacion)) ?></span></td>
                    </tr>
                </table>

                <table class="info-table" style="margin-bottom: 10px;">
                    <tr><td class="lbl">Fecha</td><td class="val"><?= $fecha_actual ?></td><td class="lbl">Razón Social</td><td class="val"><?= safe_html($nota['cliente_nombre']) ?></td></tr>
                    <tr><td class="lbl">Cédula o R.I.F</td><td class="val" colspan="3"><?= safe_html($nota['cedula_Rif']) ?></td></tr>
                    <tr><td class="lbl">Dirección</td><td class="val"><?= safe_html($nota['direccion']) ?></td><td class="lbl">Teléfono</td><td class="val"><?= safe_html($nota['tlf']) ?></td></tr>
                </table>

                <table class="info-table" style="margin-bottom: 10px;">
                    <tr><td class="lbl">Comprador</td><td class="val"><?= safe_html($nota['comprador']) ?></td><td class="lbl">Teléfono</td><td class="val"><?= safe_html($nota['tlf']) ?></td></tr>
                    <tr><td class="lbl">Email</td><td class="val" colspan="3" style="text-transform: none;"><?= safe_html($nota['email']) ?></td></tr>
                </table>

                <table class="info-table" style="margin-bottom: 10px;">
                    <tr><td class="lbl">Vendedor</td><td class="val" colspan="3"><?= safe_html($nota['v_nombre'] . ' ' . $nota['v_apellido']) ?></td></tr>
                    <tr><td class="lbl">Email</td><td class="val" colspan="3" style="text-transform: none;"><?= safe_html($nota['v_email']) ?></td></tr>
                </table>

                <div style="text-align: center; font-size: 18px; font-style: italic; font-weight: bold; margin: 15px 0; letter-spacing: 0.5px;">Estimado Cliente, reciba nuestra mejor oferta;</div>

                <table class="items-table" style="margin-bottom: 15px;">
                    <thead>
                        <tr><th style="width: 15%;">Código</th><th style="width: 10%;">Cant</th><th style="width: 50%;">Descripción Detallada</th><th style="width: 12%;">Precio Unitario</th><th style="width: 13%;">Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($detalles_lineales as $det): 
                            $genero_txt = (!empty($det['genero']) && $det['genero'] !== 'No especificado') ? ' / '.safe_html($det['genero']) : '';
                        ?>
                        <tr>
                            <td style="text-align: center; font-weight: bold; font-size: 11px; padding: 6px;"><?= safe_html($det['codigo']) ?></td>
                            <td style="font-size: 14px; text-align: center; font-weight: bold; padding: 6px;"><?= $det['cantidad'] ?></td>
                            <td style="text-align: left; font-size: 10px; padding: 6px;">
                                <strong><?= safe_html($det['tipo_prenda']) ?><?= $genero_txt ?></strong>
                                <?php if(!empty($det['detalle_limpio'])): ?><br><span style="color: #444; font-size: 9px;"><?= safe_html($det['detalle_limpio']) ?></span><?php endif; ?>
                            </td>
                            <td style="text-align: center; font-weight: bold; font-size: 11px; padding: 6px;">$ <?= formatoBs($det['precio_unitario']) ?></td>
                            <td style="font-size: 13px; text-align: center; font-weight: bold; padding: 6px;">$ <?= formatoBs($det['subtotal_linea']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php 
                        $filas_impresas = count($detalles_lineales);
                        while($filas_impresas < 8) { echo "<tr><td style='height:22px;'></td><td></td><td></td><td></td><td></td></tr>"; $filas_impresas++; }
                        ?>
                    </tbody>
                </table>

                <div class="row-clear">
                    <div style="width: 55%; float: left;">
                        <div style="background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 10px; font-size: 12px; font-weight: bold; text-align: center; margin-top: 15px; border-radius: 5px;">
                            ⚠️ NOTA: El stock de esta mercancía ha sido apartado temporalmente en nuestro almacén físico para usted. Por favor, realice su pago y reporte el comprobante.
                        </div>
                    </div>
                    <div style="width: 42%; float: right;">
                        <table class="totals-table">
                            <tr><td class="lbl">Sub-Total $</td><td><?= formatoBs($sub_total) ?></td></tr>
                            <tr><td class="lbl" style="color: #000; font-size: 13px;">Base Imponible $</td><td style="color: #000; font-size: 13px;"><?= formatoBs($sub_total) ?></td></tr>
                            <tr><td class="lbl" style="font-size: 15px; color: #0B4C8C;">Total a Pagar $</td><td style="font-size: 15px; color: #0B4C8C;"><?= formatoBs($sub_total) ?></td></tr>
                        </table>
                    </div>
                </div>

                <table class="footer-notes">
                    <tr><th colspan="1">NOTAS INFORMATIVAS</th></tr>
                    <tr><td>El Precio Unitario de la prenda no incluye el Impuesto de Ley</td></tr>
                    <tr><td>Aceptamos pagos multimonedas y en Bolívares Soberanos a Tasa del Banco Central de Venezuela</td></tr>
                    <tr><td style="color: #0B4C8C;">Método de pago: <?= htmlspecialchars($metodo_pago_texto) ?></td></tr>
                    <tr><td style="color: #0B4C8C; font-size: 11px;">MÉTODO Y LUGAR DE ENTREGA: <?= htmlspecialchars($metodo_entrega) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <script>
    function descargarPDF() {
        const btn = document.getElementById('btnDescargar');
        btn.innerHTML = '⏳ Generando PDF...'; btn.disabled = true;
        const elemento = document.getElementById('documento_pdf');
        
        document.getElementById('hoja-cotizacion').style.boxShadow = 'none';

        const opciones = {
            margin:       [0, 0, 0, 0], 
            filename:     'Cotizacion_<?= safe_html($nro_cotizacion) ?>.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 2, useCORS: true, width: 794 }, 
            jsPDF:        { unit: 'px', format: [794, 1123], orientation: 'portrait' }
        };

        html2pdf().set(opciones).from(elemento).save().then(() => {
            btn.innerHTML = '📥 Descargar Cotización'; btn.disabled = false;
            document.getElementById('hoja-cotizacion').style.boxShadow = '0 5px 15px rgba(0,0,0,0.5)'; 
        });
    }
    </script>
</body>
</html>