<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();
require_once '../conexion.php';

if (!isset($_GET['id']) || !isset($_SESSION['usuario_id'])) {
    die("Acceso denegado: Faltan permisos o el ID de la nota.");
}

$id_nota = intval($_GET['id']);

$sql = "SELECT * FROM nota_entrega_manual WHERE id = $id_nota";
$res = mysqli_query($conexion, $sql);
if (!$res) die("Error SQL en la consulta principal.");

$orden = mysqli_fetch_assoc($res);
if(!$orden) die("Nota Manual no encontrada en la base de datos.");

// Formatear Fecha
$fecha_emision = date('d/m/Y', strtotime($orden['fecha']));

// Extraer prendas insertadas
$mediciones = array();
$sql_detalles = "SELECT * FROM nota_entrega_manual_items WHERE id_nota = $id_nota";
$q_det = mysqli_query($conexion, $sql_detalles);
if ($q_det) {
    while($r = mysqli_fetch_assoc($q_det)) {
        $mediciones[] = $r;
    }
}

// Agrupar por Prenda -> Talla (IGUAL A LA NOTA ORIGINAL PARA MANTENER LA ESTRUCTURA VISUAL)
$resumen = array();
foreach($mediciones as $m) {
    $prenda = strtoupper(trim((string)$m['prenda']));
    $talla = strtoupper(trim((string)$m['talla']));
    $cant = intval($m['cantidad']);
    if ($cant <= 0) $cant = 1;
    
    if(!isset($resumen[$prenda])) {
        $resumen[$prenda] = array('total' => 0, 'desglose' => array());
    }
    
    if(!isset($resumen[$prenda]['desglose'][$talla])) {
        $resumen[$prenda]['desglose'][$talla] = 0;
    }
    
    $resumen[$prenda]['desglose'][$talla] += $cant;
    $resumen[$prenda]['total'] += $cant;
}

function safe_html($str) {
    if ($str === null) return '';
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Entrega <?= safe_html($orden['nro_nota']) ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }
        body { background: #525659; margin: 0; font-family: 'Arial', sans-serif; color: #000; font-size: 11px; }

        .top-bar { background-color: #13131A; padding: 15px; text-align: center; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .btn-print { padding: 10px 25px; font-size: 14px; font-weight: bold; background: #FF7518; color: #FFF; border: none; cursor: pointer; border-radius: 8px; transition: 0.3s; }
        .btn-print:hover { background: #e05e00; }

        .vista-previa { width: 100%; overflow-x: auto; padding: 80px 20px 40px 20px; text-align: center; }
        #documento_pdf { width: 800px; max-width: 800px; margin: 0 auto; text-align: left; background: transparent; }
        
        .hoja-a4 { background: white; width: 100%; padding: 40px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); margin-bottom: 20px; position: relative; }

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
        .header-logo img { height: 90px; }
        .header-info { text-align: right; font-size: 9px; line-height: 1.3; font-weight: bold; color: #333; }
        
        .title-bar { display: flex; border: 1.5px solid #000; background-color: #d9d9d9; margin-bottom: 0; border-bottom: none; }
        .title-left { flex: 1; padding: 5px; font-size: 16px; font-weight: 900; text-align: center; letter-spacing: 1px; border-right: 1.5px solid #000; }
        .title-right { width: 150px; padding: 5px; font-size: 12px; text-align: center; color: #b30015; font-weight: bold; }

        .info-table td { font-size: 10px; background-color: #d9d9d9; font-weight: bold; width: 25%; text-transform: uppercase; }
        .info-table td.val { background-color: #fff; font-weight: normal; width: 75%; text-transform: uppercase; }
        .info-table td.split-val { background-color: #fff; font-weight: normal; width: 25%; text-transform: uppercase; }

        .items-header { background-color: #d9d9d9; font-size: 12px; font-weight: bold; font-style: italic; text-align: center; padding: 8px; border: 1.5px solid #000; border-top: none; border-bottom: none; }
        
        .items-table th { background-color: #d9d9d9; font-size: 11px; text-align: center; font-style: italic; }
        .items-table td { font-size: 10px; height: 22px; }
        
        /* ESTILOS DE LA ZONA DE FIRMAS */
        .footer-firmas { display: flex; justify-content: space-between; margin-top: 50px; padding: 0 40px; position: relative; }
        .firma-box { text-align: center; position: relative; width: 220px; }
        .firma-linea { border-top: 1.5px solid #000; padding-top: 5px; font-size: 11px; margin-top: 80px; position: relative; z-index: 2; }
        
        .img-firma-claritza { 
            position: absolute; 
            bottom: 25px; 
            left: 50%; 
            transform: translateX(-50%); 
            width: 120px; 
            height: auto; 
            z-index: 1; 
            mix-blend-mode: multiply;
        }
    </style>
</head>
<body>

    <div class="top-bar no-print">
        <button id="btnDescargar" class="btn-print" onclick="descargarPDF()">📥 Imprimir / Descargar Nota Manual</button>
    </div>

    <div class="vista-previa">
        <div id="documento_pdf">
            <div class="hoja-a4" id="hoja-captura">
                
                <div class="header-top">
                    <div class="header-logo">
                        <img src="../img/unifuerte.jpg" alt="Unifuerte Logo">
                    </div>
                    <div class="header-info">
                        UNIFUERTE, C.A<br>
                        R.I.F.: J-41282764-2/R.U.C 1829-2021<br>
                        SENCAMER REM-42739<br>
                        CR. UD-321 EDIFICIO PARCELA 321-16-10A-1,<br>
                        PISO 1, OFICINA A,B,C, ZONA INDUSTRIAL, UD 321<br>
                        CIUDAD GUAYANA. PUERTO ORDAZ, ESTADO BOLIVAR<br>
                        CONTACTO: (0286) 9940628 - 04241546880<br>
                        EMAIL: CLIENTEUNIFUERTE@GMAIL.COM - CLIENTEUNIFUERTE1@GMAIL.COM
                    </div>
                </div>

                <div class="title-bar">
                    <div class="title-left">NOTA DE ENTREGA</div>
                    <div class="title-right">Nro. <?= safe_html($orden['nro_nota']) ?></div>
                </div>

                <table class="info-table">
                    <tr>
                        <td>Fecha:</td>
                        <td class="val" colspan="3"><?= $fecha_emision ?></td>
                    </tr>
                    <tr>
                        <td>Nombre y Apellido o Razón Social:</td>
                        <td class="val" colspan="3"><?= safe_html($orden['cliente_nombre']) ?></td>
                    </tr>
                    <tr>
                        <td>Cédula o R.I.F:</td>
                        <td class="val" colspan="3"><?= safe_html($orden['cedula_rif']) ?></td>
                    </tr>
                    <tr>
                        <td>Dirección:</td>
                        <td class="val" colspan="3"><?= safe_html($orden['direccion']) ?></td>
                    </tr>
                    <tr>
                        <td>Comprador:</td>
                        <td class="split-val"><?= safe_html($orden['comprador']) ?></td>
                        <td>Teléfono:</td>
                        <td class="split-val"><?= safe_html($orden['telefono']) ?></td>
                    </tr>
                    <tr>
                        <td>Email:</td>
                        <td class="val" colspan="3"><?= safe_html($orden['email']) ?></td>
                    </tr>
                </table>

                <div class="items-header">
                    Estimado Cliente, reciba nuestra entrega del siguiente material:
                </div>

                <table class="items-table" style="border-top: none;">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Código</th>
                            <th style="width: 15%;">Cantidad</th>
                            <th style="width: 70%;">Descripción detallada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $filas_impresas = 0;
                        foreach($resumen as $prenda => $datos): ?>
                            <tr>
                                <td style="text-align: center;">-</td>
                                <td style="text-align: center; font-weight: bold; font-size:12px;"><?= $datos['total'] ?></td>
                                <td style="font-weight: bold; font-size:11px;"><?= safe_html($prenda) ?></td>
                            </tr>
                            <?php $filas_impresas++; ?>
                            
                            <tr>
                                <td></td>
                                <td></td>
                                <td style="font-style: italic; color: #444;">TALLAS:</td>
                            </tr>
                            <?php $filas_impresas++; ?>

                            <?php foreach($datos['desglose'] as $talla => $cant_talla): ?>
                            <tr>
                                <td></td>
                                <td style="text-align: center;"><?= $cant_talla ?></td>
                                <td style="padding-left: 20px; font-size: 10px;"><?= safe_html($talla) ?></td>
                            </tr>
                            <?php $filas_impresas++; endforeach; ?>
                            
                            <tr><td colspan="3" style="border-left:1.5px solid #000; border-right:1.5px solid #000; border-top:none; border-bottom:none;"></td></tr>
                            <?php $filas_impresas++; ?>
                        <?php endforeach; ?>

                        <?php 
                        $filas_minimas = 10;
                        while($filas_impresas < $filas_minimas) {
                            echo "<tr><td></td><td></td><td></td></tr>";
                            $filas_impresas++;
                        }
                        
                        $total_general = 0;
                        foreach($resumen as $p => $d) {
                            $total_general += $d['total'];
                        }
                        ?>
                        
                        <tr>
                            <td style="background-color: #d9d9d9;"></td>
                            <td style="text-align: center; font-weight: bold; font-size: 12px;"><?= $total_general ?></td>
                            <td style="background-color: #d9d9d9;"></td>
                        </tr>
                    </tbody>
                </table>

                <div class="footer-firmas">
                    <div class="firma-box">
                        <div class="firma-linea">
                            <strong>Recibido:</strong>
                        </div>
                    </div>

                    <div class="firma-box">
                        <img src="img/firma.jpeg" class="img-firma-claritza" alt="Firma Claritza">
                        <div class="firma-linea">
                            <strong>Claritza Pinto</strong><br>
                            Director Comercial Unifuerte C.A.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
    function descargarPDF() {
        const btn = document.getElementById('btnDescargar');
        btn.innerHTML = '⏳ Generando PDF...';
        btn.disabled = true;

        const elemento = document.getElementById('hoja-captura');
        elemento.style.boxShadow = 'none';

        const opciones = {
            margin:       0,
            filename:     'Nota_Entrega_<?= safe_html($orden['nro_nota']) ?>.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 2, useCORS: true }, 
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        html2pdf().set(opciones).from(elemento).save().then(() => {
            btn.innerHTML = '📥 Imprimir / Descargar Nota Manual';
            btn.disabled = false;
            elemento.style.boxShadow = '0 5px 15px rgba(0,0,0,0.5)'; 
        });
    }
    </script>
</body>
</html>