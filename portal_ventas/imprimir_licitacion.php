<?php
session_start();
error_reporting(0); // Evita errores 500
require_once '../conexion.php';
require_once __DIR__ . '/portal_documento_auth.php';

if (!isset($_GET['id'])) {
    die("Acceso denegado o ID inválido.");
}

$id_cot = intval($_GET['id']);
$llave_url = $_GET['k'] ?? ''; 
$accesoPortal = validarTokenDocumentoPortal($conexion, 'licitacion', $id_cot);

$sql = "SELECT c.*, cli.nombre AS cliente_nombre, cli.cedula_Rif, cli.direccion, cli.comprador, cli.email, cli.tlf AS cliente_tlf,
               u.nombre AS v_nombre, u.apellido AS v_apellido, u.tlf AS v_tlf, u.profesion AS v_profesion, u.email AS v_email,
               fp.forma_pago AS metodo_pago_texto
        FROM cotizacion c 
        JOIN cliente cli ON c.id_cliente = cli.ID 
        LEFT JOIN usuario u ON c.id_vendedor = u.ID
        LEFT JOIN cotizacion_formas_pago fp ON c.id_forma_pago = fp.id
        WHERE c.ID = $id_cot";
$res = mysqli_query($conexion, $sql);
$cotizacion = mysqli_fetch_assoc($res);

if(!$cotizacion) die("Cotización no encontrada en el sistema.");

if (!isset($_SESSION['usuario_id']) && !$accesoPortal) {
    if (isset($_GET['portal_token'])) {
        http_response_code(403);
        die('El enlace del portal venció o no corresponde a este documento.');
    }
    $llave_esperada = base64_encode($id_cot . '-' . $cotizacion['nro_cotizacion']);
    if ($llave_url !== $llave_esperada) {
        die("<div style='text-align:center; padding: 50px; font-family: sans-serif;'>
                <h2 style='color:#E11D48;'>Acceso Protegido</h2>
                <p>Este enlace es privado o ha expirado.<br>Solicite a su asesor de Unifuerte un nuevo enlace.</p>
             </div>");
    }
}

$sql_det = "SELECT * FROM detalle_cotizacion WHERE id_cotizacion = $id_cot";
$res_det = mysqli_query($conexion, $sql_det);
$detalles = [];
if($res_det) {
    while($row = mysqli_fetch_assoc($res_det)) {
        $detalles[] = $row;
    }
}

$metodo_pago_texto = $cotizacion['metodo_pago_texto'] ?? "A convenir";
$metodo_entrega = $cotizacion['metodo_entrega'] ?? "A convenir con el cliente";

function formatoBs($num) { return number_format((float)$num, 2, ',', '.'); }

// Función experta para incrustar las imágenes directamente en el código de la página (Base64)
function getBase64Image($path) {
    if (file_exists($path)) {
        $type = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if($type == 'jpg') $type = 'jpeg';
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    return '';
}

// CÁLCULOS FINANCIEROS
$sub_total = floatval($cotizacion['sub_total'] ?? 0);
$servicio_logistico = floatval($cotizacion['servicio_logistico'] ?? 0);
$flete = floatval($cotizacion['flete'] ?? 0);
$descuento = floatval($cotizacion['descuento'] ?? 0);
$porcentaje_desc = floatval($cotizacion['porcentaje_descuento_manual'] ?? 0);

$base_imponible = ($sub_total + $servicio_logistico + $flete) - $descuento;
if ($base_imponible < 0) $base_imponible = 0;

$iva = floatval($cotizacion['iva'] ?? 0);
$igtf = floatval($cotizacion['igtf'] ?? 0);
$total_pagar = floatval($cotizacion['total'] ?? 0);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización Especial <?= htmlspecialchars($cotizacion['nro_cotizacion']) ?></title>
    <!-- LIBRERÍA HTML2PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * {
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
            box-sizing: border-box;
        }

        body { 
            background: #525659; 
            margin: 0; 
            font-family: 'Arial', sans-serif; 
            color: #000;
        }

        .top-bar {
            background-color: #13131A;
            padding: 15px;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .btn-print { 
            padding: 12px 30px; 
            font-size: 16px; 
            font-weight: bold; 
            background: #FF7518; 
            color: #FFF; 
            border: none; 
            cursor: pointer; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); 
            transition: 0.3s;
        }
        .btn-print:hover { background: #e05e00; transform: scale(1.05); }
        .btn-print:disabled { background: #666; cursor: not-allowed; transform: none; }

        .vista-previa {
            width: 100%;
            overflow-x: auto; 
            padding: 90px 20px 40px 20px; 
            text-align: center;
        }

        #documento_pdf {
            background: white; 
            width: 794px; 
            min-width: 794px; 
            margin: 0 auto; 
            padding: 40px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.5); 
            text-align: left; 
        }

        .row-clear::after { content: ""; clear: both; display: table; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .logo-img { max-height: 90px; }
        .company-info { text-align: right; font-weight: bold; font-size: 12px; line-height: 1.2; }

        .title-row { text-align: center; font-size: 16px; font-weight: bold; color: #000; margin-bottom: 10px; }

        .client-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 2px solid #000; }
        .client-table td { border: 1px solid #000; padding: 6px; font-size: 12px; vertical-align: middle; }
        .client-table td.lbl { font-weight: bold; background-color: #f0f0f0; } 
        .client-table td.val { font-weight: bold; }

        .greeting { text-align: center; font-size: 18px; font-style: italic; font-weight: bold; margin: 20px 0; letter-spacing: 0.5px; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 2px solid #000; table-layout: auto; }
        .items-table th { border: 2px solid #000; padding: 8px; text-align: center; font-size: 12px; background-color: #f0f0f0; }
        
        tr { page-break-inside: avoid !important; break-inside: avoid !important; }
        .items-table td { border: 1px solid #000; padding: 8px; text-align: center; vertical-align: middle; font-weight: bold; font-size: 13px;}
        
        /* CORRECCIÓN: Se quitó el white-space: pre-wrap para que no genere sangrías por los espacios del código PHP y se ajustó el padding */
        .items-table td.desc { 
            text-align: left !important;
            font-weight: normal; 
            font-size: 12px; 
            word-break: break-word; 
            line-height: 1.4;
            padding: 12px 5px; 
        }

        .totals-table { width: 40%; float: right; border-collapse: collapse; font-size: 13px; font-weight: bold; margin-bottom: 20px; }
        .totals-table td { padding: 6px 8px; text-align: right; border-bottom: 1px solid #000; }
        .totals-table td.lbl { width: 60%; }

        .footer-notes { clear: both; width: 100%; border: 2px solid #000; border-collapse: collapse; margin-top: 20px; page-break-inside: avoid; }
        .footer-notes th { border: 1px solid #000; background-color: #f0f0f0; padding: 4px; font-size: 12px; }
        .footer-notes td { border: 1px solid #000; padding: 4px; font-size: 10px; font-weight: bold; }

        .anexo-container {
            width: 100%;
            text-align: center;
            padding-top: 30px;
            page-break-before: always !important; 
        }
    </style>
</head>
<body>

    <div class="top-bar no-print">
        <button id="btnDescargar" class="btn-print" onclick="descargarPDF()">📥 Descargar Licitación PDF</button>
    </div>

    <div class="vista-previa">
        <div id="documento_pdf">

            <table class="header-table">
                <tr>
                    <td style="width: 40%; vertical-align: middle;">
                        <img src="<?= getBase64Image(__DIR__ . '/../img/unifuerte.jpg') ?>" class="logo-img" alt="Unifuerte Logo">
                    </td>
                    <td class="company-info" style="width: 60%;">
                        UNIFUERTE, C.A<br>
                        R.I.F: J-41282764-2 / R.U.C 1829-2021<br>
                        SENCAMER 0012216TN-21<br>
                        CR. UD-321, EDIFICIO PARCELA 321-16-10A-1<br>
                        PISO 1, OFICINA, A,B,C, ZONA INDUSTRIAL UD 321<br>
                        CIUDAD GUAYANA, PUERTO ORDAZ, ESTADO BOLIVAR<br>
                        CONTACTO: (0286) 9940629 - 04241546889<br>
                        EMAIL: CLIENTEUNIFUERTE@GMAIL.COM - CLIENTEUNIFUERTE1@GMAIL.COM
                    </td>
                </tr>
            </table>

            <div class="title-row">
                COTIZACIÓN ESPECIAL / PETICIÓN DE OFERTA NRO. <?= htmlspecialchars(str_replace('COT-', '', $cotizacion['nro_cotizacion'])) ?>
            </div>

            <table class="client-table">
                <tr>
                    <td class="lbl" style="width: 15%;">Fecha:</td>
                    <td class="val" style="width: 35%;"><?= date('d/m/Y', strtotime($cotizacion['fecha_emision'])) ?></td>
                    <td class="lbl" style="width: 25%;">Nombre y Apellido o Razón Social:</td>
                    <td class="val" style="width: 25%; text-transform: uppercase;">
                        <?= htmlspecialchars($cotizacion['cliente_nombre']) ?> 
                        <?= $cotizacion['contribuyente_especial'] == 1 ? '<span style="font-size:9px; color:#555;"><br>(Contribuyente Especial)</span>' : '' ?>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Cédula o R.I.F:</td>
                    <td class="val" colspan="3" style="text-transform: uppercase;"><?= htmlspecialchars($cotizacion['cedula_Rif']) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Dirección:</td>
                    <td class="val" colspan="3" style="text-transform: uppercase;"><?= htmlspecialchars($cotizacion['direccion']) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Comprador:</td>
                    <td class="val" style="text-transform: uppercase;"><?= htmlspecialchars($cotizacion['comprador']) ?></td>
                    <td class="lbl">Teléfono:</td>
                    <td class="val"><?= htmlspecialchars($cotizacion['cliente_tlf']) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Email:</td>
                    <td class="val" colspan="3" style="text-transform: none;"><?= htmlspecialchars($cotizacion['email']) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Vendedor:</td>
                    <td class="val" colspan="3" style="text-transform: uppercase;"><?= htmlspecialchars($cotizacion['v_nombre'] . ' ' . $cotizacion['v_apellido']) ?></td>
                </tr>
            </table>

            <div class="greeting">Estimado Cliente; agradecemos su confianza</div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">Pos.</th>
                        <th style="width: 8%;">Cant.</th>
                        <!-- TÍTULO CENTRADO -->
                        <th style="width: 48%; text-align: center;">Descripción Detallada</th>
                        <th style="width: 16%;">Precio<br>Unitario</th>
                        <th style="width: 20%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $pos = 0; 
                    foreach($detalles as $det): 
                        $pos++;
                        $pos_str = str_pad($pos, 4, '0', STR_PAD_LEFT);
                        $sub_linea_neto = $det['precio_unitario'] * $det['cantidad'];

                        $detalle_limpio = preg_replace('/\[Para:.*?\]\s*-\s*/', '', $det['detalle_diseno'] ?? '');

                        $genero_txt = '';
                        if(!empty($det['genero']) && $det['genero'] !== 'No especificado') {
                            $genero_txt = ' / ' . htmlspecialchars($det['genero']);
                        }
                    ?>
                    <tr>
                        <td style="font-size: 14px;"><?= $pos_str ?></td>
                        <td style="font-size: 14px;"><?= $det['cantidad'] ?></td>
                        
                        <!-- CONTENIDO COMPLETAMENTE A LA IZQUIERDA SIN SANGRÍA -->
                        <td class="desc" align="left"><?= nl2br(trim(htmlspecialchars($det['tipo_prenda'] ?? ''))) ?><?= $genero_txt ?><?php if(!empty($detalle_limpio)): ?><br><span style="color: #444; font-size: 10px;"><?= nl2br(trim(htmlspecialchars($detalle_limpio))) ?></span><?php endif; ?></td>
                        
                        <td>$ <?= formatoBs($det['precio_unitario']) ?></td>
                        <td>$ <?= formatoBs($sub_linea_neto) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="row-clear">
                <div style="width: 55%; float: left;">
                    <?php if(!empty($cotizacion['notas'])): ?>
                    <div style="border: 2px solid #000; margin-bottom: 10px;">
                        <div style="background-color: #f0f0f0; border-bottom: 1px solid #000; padding: 4px; font-size: 10px; font-weight: bold; text-align: center;">NOTAS ADICIONALES</div>
                        <div style="padding: 6px; font-size: 10px; white-space: pre-wrap; font-weight: bold;"><?= htmlspecialchars($cotizacion['notas']) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($cotizacion['no_incluye'])): ?>
                    <div style="border: 2px solid #E11D48;">
                        <div style="background-color: #ffe4e6; color: #E11D48; border-bottom: 1px solid #E11D48; padding: 4px; font-size: 10px; font-weight: bold; text-align: center;">LO QUE NO INCLUYE ESTA COTIZACIÓN</div>
                        <div style="padding: 6px; font-size: 10px; white-space: pre-wrap; color: #E11D48; font-weight: bold;"><?= htmlspecialchars($cotizacion['no_incluye']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="width: 42%; float: right;">
                    <table class="totals-table" style="width: 100%;">
                        <tr>
                            <td class="lbl" style="text-align: left;">Sub-Total:</td>
                            <td>$ <?= formatoBs($sub_total) ?></td>
                        </tr>

                        <?php if($servicio_logistico > 0): ?>
                        <tr>
                            <td class="lbl" style="text-align: left; color: #0B4C8C;">Serv. Logístico de Envío:</td>
                            <td style="color: #0B4C8C;">$ <?= formatoBs($servicio_logistico) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if($flete > 0): ?>
                        <tr>
                            <td class="lbl" style="text-align: left; color: #0B4C8C;">Flete:</td>
                            <td style="color: #0B4C8C;">$ <?= formatoBs($flete) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if($descuento > 0): ?>
                        <tr>
                            <td class="lbl" style="text-align: left; color: red;">Descuento (<?= $porcentaje_desc ?>%):</td>
                            <td style="color: red;">$ - <?= formatoBs($descuento) ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <td class="lbl" style="text-align: left; color: #000; font-size: 13px;">Base Imponible:</td>
                            <td style="color: #000; font-size: 13px;">$ <?= formatoBs($base_imponible) ?></td>
                        </tr>

                        <?php if ($iva > 0): ?>
                        <tr>
                            <td class="lbl" style="text-align: left;">I.V.A (16%):</td>
                            <td>$ <?= formatoBs($iva) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if($igtf > 0): ?>
                        <tr>
                            <td class="lbl" style="text-align: left; color: #DAA520;">IGTF (3%):</td>
                            <td style="color: #DAA520;">$ <?= formatoBs($igtf) ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <td class="lbl" style="font-size: 15px; color: #0B4C8C; text-align: left;">Total a Pagar:</td>
                            <td style="font-size: 15px; color: #0B4C8C;">$ <?= formatoBs($total_pagar) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <table class="footer-notes">
                <tr><th colspan="1">NOTAS INFORMATIVAS</th></tr>
                <tr><td>El Precio Unitario del producto no incluye los Impuestos de Ley</td></tr>
                <tr><td>Para trabajos Urgentes debe pagar la orden de contado mas un recargo adicional del 10% por Servicio Express</td></tr>
                <tr><td>Garantía Textil del 100%: Notificar dentro de los 5 días siguientes luego de recibir el pedido</td></tr>
                <tr><td>Aceptamos pagos multimonedas y en Bolívares Soberanos a Tasa del Banco Central de Venezuela</td></tr>
                <tr><td style="color: #0B4C8C;">Método de pago acordado: <?= htmlspecialchars($metodo_pago_texto) ?></td></tr>
                <tr><td style="color: #0B4C8C; font-size: 11px;">MÉTODO Y LUGAR DE ENTREGA: <?= htmlspecialchars($metodo_entrega) ?></td></tr>
            </table>

            <div style="text-align: center; font-style: italic; font-weight: bold; font-size: 14px; margin-top: 20px;">Será un verdadero placer servirles...</div>
            
            <?php 
            if (!empty($cotizacion['fotos_diseno'])): 
                $fotos_array = explode(',', $cotizacion['fotos_diseno']);
                foreach($fotos_array as $img):
                    $img = trim($img);
                    if(empty($img)) continue;
                    
                    $ruta_img = __DIR__ . '/../images/ordenes/' . $img; 
                    if(!file_exists($ruta_img)) continue; 
                    
                    $ext = strtolower(pathinfo($ruta_img, PATHINFO_EXTENSION));
            ?>
                <div class="html2pdf__page-break"></div>
                
                <div class="anexo-container">
                    <h3 style="color: #0B4C8C; margin-bottom: 25px; font-weight: 900; text-transform: uppercase;">ANEXO A LA COTIZACIÓN</h3>
                    
                    <?php if ($ext === 'pdf'): ?>
                        <div style="width: 85%; margin: 0 auto; border: 4px dashed #0B4C8C; padding: 60px 20px; border-radius: 15px; background-color: #f0f8ff;">
                            <h1 style="color: #E11D48; font-size: 35px; margin-bottom: 20px;">📄 DOCUMENTO PDF ANEXADO</h1>
                            <p style="font-size: 16px; color: #333; margin-bottom: 25px;">Se ha adjuntado un archivo PDF como anexo técnico o referencial.</p>
                            <a href="../images/ordenes/<?= $img ?>" target="_blank" style="display: inline-block; font-size: 18px; font-weight: bold; color: #FFF; background-color: #0B4C8C; padding: 15px 30px; text-decoration: none; border-radius: 8px;">Clic aquí para abrir el PDF Original</a>
                        </div>
                    <?php else: ?>
                        <img src="<?= getBase64Image($ruta_img) ?>" style="max-width: 95%; max-height: 800px; object-fit: contain; border: 2px solid #ccc; padding: 10px; border-radius: 8px; background: #fff;">
                    <?php endif; ?>
                </div>
            <?php 
                endforeach; 
            endif; 
            ?>

        </div>
    </div>

    <script>
    function descargarPDF() {
        const btn = document.getElementById('btnDescargar');
        btn.innerHTML = '⏳ Generando, por favor espere...';
        btn.disabled = true;

        const elemento = document.getElementById('documento_pdf');
        elemento.style.boxShadow = 'none';

        const opciones = {
            margin:       [10, 10, 10, 10], 
            filename:     'Cotización_Unifuerte_<?= str_replace("COT-", "", $cotizacion["nro_cotizacion"]) ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, width: 794, logging: false }, 
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak:    { mode: ['css', 'legacy'] }
        };

        html2pdf().set(opciones).from(elemento).save().then(() => {
            btn.innerHTML = '📥 Descargar Licitación PDF';
            btn.disabled = false;
            elemento.style.boxShadow = '0 5px 15px rgba(0,0,0,0.5)';
        });
    }
    </script>
</body>
</html>