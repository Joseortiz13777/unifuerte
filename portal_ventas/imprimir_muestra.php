<?php
session_start();
require_once '../conexion.php';
require_once __DIR__ . '/portal_documento_auth.php';

// Verificamos si pasaron el ID
if (!isset($_GET['id'])) {
    die("Acceso denegado o ID inválido.");
}

$id_cot = intval($_GET['id']);
$llave_url = $_GET['k'] ?? ''; 
$accesoPortal = validarTokenDocumentoPortal($conexion, 'muestra', $id_cot);

$sql = "SELECT c.*, cli.nombre AS cliente_nombre, cli.cedula_Rif, cli.direccion, cli.comprador, cli.email, cli.tlf AS cliente_tlf,
               u.nombre AS v_nombre, u.apellido AS v_apellido, u.tlf AS v_tlf, u.profesion AS v_profesion, u.email AS v_email
        FROM cotizacion c 
        JOIN cliente cli ON c.id_cliente = cli.ID 
        LEFT JOIN usuario u ON c.id_vendedor = u.ID
        WHERE c.ID = $id_cot";
$res = mysqli_query($conexion, $sql);
$cotizacion = mysqli_fetch_assoc($res);

if(!$cotizacion) die("Muestra no encontrada en el sistema.");

if (!isset($_SESSION['usuario_id']) && !$accesoPortal) {
    if (isset($_GET['portal_token'])) {
        http_response_code(403);
        die('El enlace del portal venció o no corresponde a este documento.');
    }
    $llave_esperada = base64_encode($id_cot . '-' . $cotizacion['nro_cotizacion']);
    if ($llave_url !== $llave_esperada) {
        die("<div style='text-align:center; padding: 50px; font-family: sans-serif;'>
                <h2 style='color:#E11D48;'>Acceso Protegido</h2>
                <p>Este enlace es privado, ha expirado o está mal escrito.<br>Por favor, solicite a su asesor de Unifuerte un nuevo enlace.</p>
             </div>");
    }
}

// TRAEMOS LOS DETALLES HACIENDO LEFT JOIN CON LOS PRODUCTOS PARA LA FOTO
$sql_det = "SELECT d.*, p.foto FROM detalle_cotizacion d LEFT JOIN productos p ON d.id_producto = p.id WHERE d.id_cotizacion = $id_cot";
$res_det = mysqli_query($conexion, $sql_det);
$detalles = [];
$total_prendas = 0;
while($row = mysqli_fetch_assoc($res_det)) {
    $detalles[] = $row;
    $total_prendas += $row['cantidad'];
}

$metodo_entrega = $cotizacion['metodo_entrega'] ?? "A convenir con el cliente";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muestra <?= htmlspecialchars($cotizacion['nro_cotizacion']) ?></title>
    <!-- LIBRERÍA HTML2PDF PARA DESCARGA DIRECTA -->
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

        /* BARRA SUPERIOR FIJA PARA EL BOTÓN */
        .top-bar {
            background-color: #17a2b8;
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
            background: #0B4C8C; 
            color: #FFF; 
            border: none; 
            cursor: pointer; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); 
            transition: 0.3s;
        }
        .btn-print:hover { background: #083c6f; transform: scale(1.05); }
        .btn-print:disabled { background: #666; cursor: not-allowed; transform: none; }

        /* CONTENEDOR RESPONSIVO */
        .vista-previa {
            width: 100%;
            overflow-x: auto; 
            padding: 90px 20px 40px 20px; 
            text-align: center;
        }

        /* TAMAÑO CARTA EXACTO */
        #documento_pdf {
            background: white; 
            width: 794px; 
            min-width: 794px; 
            min-height: 1123px; 
            margin: 0 auto; 
            padding: 40px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.5); 
            text-align: left; 
        }

        .row-clear::after { content: ""; clear: both; display: table; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .logo-img { max-height: 70px; }
        .company-info { text-align: right; font-weight: bold; font-size: 10px; line-height: 1.2; }
        
        .title-box { width: 100%; border: 2px solid #000; border-collapse: collapse; text-align: center; margin-bottom: 10px; }
        .title-box td { padding: 4px; border: 2px solid #000; font-size: 16px; font-weight: bold; text-transform: uppercase; background-color: #17a2b8; color: #fff;}
        .title-box td.nro-box { width: 150px; font-size: 14px; background-color: #fff; color: #000; }
        
        .client-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 2px solid #000; }
        .client-table td { border: 1px solid #000; padding: 4px; font-size: 11px; vertical-align: middle; }
        .client-table td.lbl { font-weight: bold; background-color: #f0f0f0; } 
        .client-table td.val { font-weight: bold; }
        
        .greeting { text-align: center; font-size: 18px; font-style: italic; font-weight: bold; margin: 15px 0; letter-spacing: 0.5px; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 2px solid #000; }
        .items-table th { border: 1px solid #000; padding: 6px; text-align: center; font-size: 12px; background-color: #17a2b8; color: white;}
        .items-table td { border: 1px solid #000; padding: 6px; text-align: center; vertical-align: middle; font-weight: bold; font-size: 11px;}
        .items-table td.desc { text-align: left; font-weight: normal; font-size: 11px; }
        
        .footer-notes { clear: both; width: 100%; border: 2px solid #000; border-collapse: collapse; margin-top: 20px; }
        .footer-notes th { border: 1px solid #000; background-color: #f0f0f0; padding: 4px; font-size: 12px; }
        .footer-notes td { border: 1px solid #000; padding: 4px; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="top-bar no-print">
        <button id="btnDescargar" class="btn-print" onclick="descargarPDF()">📥 Descargar Muestra Físicas PDF</button>
    </div>

    <div class="vista-previa">
        <div id="documento_pdf">
            <table class="header-table">
                <tr>
                    <td style="width: 40%; vertical-align: top;">
                        <img src="../img/unifuerte.jpg" class="logo-img" alt="Unifuerte Logo">
                    </td>
                    <td class="company-info" style="width: 60%;">
                        UNIFUERTE, C.A<br>
                        R.I.F: J-41282764-2 / R.U.C 1829-2021<br>
                        SENCAMER 0012216TN-21<br>
                        CR. UD-321, EDIFICIO PARCELA 321-16-10A-1<br>
                        PISO 1, OFICINA, A,B,C, ZONA INDUSTRIAL UD 321<br>
                        CIUDAD GUAYANA, PUERTO ORDAZ, ESTADO BOLIVAR<br>
                        CONTACTO: 04241546889<br>
                        EMAIL: CLIENTEUNIFUERTE@GMAIL.COM - CLIENTEUNIFUERTE1@GMAIL.COM
                    </td>
                </tr>
            </table>

            <table class="title-box">
                <tr>
                    <td>SOLICITUD DE MUESTRA FÍSICA</td>
                    <td class="nro-box">
                        Doc. Nro.<br>
                        <span style="font-size: 16px;"><?= htmlspecialchars(str_replace('MUESTRA-', '', $cotizacion['nro_cotizacion'])) ?></span>
                    </td>
                </tr>
            </table>

            <table class="client-table">
                <tr>
                    <td class="lbl" style="width: 15%;">Fecha</td>
                    <td class="val" style="width: 35%;"><?= date('d/m/Y', strtotime($cotizacion['fecha_emision'])) ?></td>
                    <td class="lbl" style="width: 25%;">Nombre o Razón Social</td>
                    <td class="val" style="width: 25%; text-transform: uppercase;">
                        <?= htmlspecialchars($cotizacion['cliente_nombre']) ?> 
                        <?= $cotizacion['contribuyente_especial'] == 1 ? '<span style="font-size:9px; color:#555;">(Contribuyente Especial)</span>' : '' ?>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Cédula o R.I.F</td>
                    <td class="val" colspan="3" style="text-transform: uppercase;"><?= htmlspecialchars($cotizacion['cedula_Rif']) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Dirección</td>
                    <td class="val" style="text-transform: uppercase;"><?= htmlspecialchars($cotizacion['direccion']) ?></td>
                    <td class="lbl">Teléfono</td>
                    <td class="val"><?= htmlspecialchars($cotizacion['cliente_tlf']) ?></td>
                </tr>
            </table>

            <table class="client-table">
                <tr>
                    <td class="lbl" style="width: 15%;">Comprador</td>
                    <td class="val" style="width: 35%; text-transform: uppercase;"><?= htmlspecialchars($cotizacion['comprador']) ?></td>
                    <td class="lbl" style="width: 25%;">Teléfono</td>
                    <td class="val" style="width: 25%;"><?= htmlspecialchars($cotizacion['cliente_tlf']) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Email</td>
                    <td class="val" colspan="3" style="text-transform: none;"><?= htmlspecialchars($cotizacion['email']) ?></td>
                </tr>
            </table>

            <table class="client-table">
                <tr>
                    <td class="lbl" style="width: 15%;">Asesor a cargo</td>
                    <td class="val" colspan="3" style="text-transform: uppercase;">
                        <?= htmlspecialchars($cotizacion['v_nombre'] . ' ' . $cotizacion['v_apellido']) ?>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Email</td>
                    <td class="val" colspan="3" style="text-transform: none;"><?= htmlspecialchars($cotizacion['v_email']) ?></td>
                </tr>
            </table>

            <div class="greeting">Estimado Cliente, a continuación el detalle de su Muestra:</div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Código</th>
                        <th style="width: 15%;">Foto / Ref.</th>
                        <th style="width: 10%;">Cant</th>
                        <th style="width: 65%;">Descripción Detallada y Especificaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $codigo_base = 69350; 
                    foreach($detalles as $det): 
                        $codigo_base++;

                        $foto_ruta = '../img/unifuerte.jpg';
                        if (!empty($det['foto'])) {
                            if (file_exists('../../images/uploads/' . $det['foto'])) {
                                $foto_ruta = '../../images/uploads/' . $det['foto'];
                            } elseif (file_exists('../images/uploads/' . $det['foto'])) {
                                $foto_ruta = '../images/uploads/' . $det['foto'];
                            }
                        }

                        $detalle_limpio = preg_replace('/\[Para:.*?\]\s*-\s*/', '', $det['detalle_diseno'] ?? '');
                        
                        $genero_txt = '';
                        if(!empty($det['genero']) && $det['genero'] !== 'No especificado') {
                            $g_lbl = $det['genero'];
                            if (strpos($g_lbl, 'Talla Extra') !== false) {
                                $g_lbl = 'Tallas Extras';
                            }
                            $genero_txt = ' / ' . htmlspecialchars($g_lbl);
                        }
                    ?>
                    <tr>
                        <td>U<?= $codigo_base ?></td>
                        <td style="text-align: center; padding: 2px;">
                            <img src="<?= $foto_ruta ?>" style="max-height: 60px; max-width: 60px; object-fit: contain; border-radius: 4px;">
                        </td>
                        <td style="font-size: 15px; color:#17a2b8;"><?= $det['cantidad'] ?></td>
                        <td class="desc">
                            <strong><?= htmlspecialchars($det['tipo_prenda']) ?><?= $genero_txt ?></strong>
                            <?php if(!empty($detalle_limpio)): ?>
                                <br><span style="color: #444; font-size: 10px;"><?= htmlspecialchars($detalle_limpio) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="row-clear" style="margin-bottom: 20px;">
                <div style="width: 100%;">
                    <?php if(!empty($cotizacion['notas'])): ?>
                    <div style="border: 2px solid #000; margin-bottom: 10px;">
                        <div style="background-color: #f0f0f0; border-bottom: 1px solid #000; padding: 4px; font-size: 11px; font-weight: bold; text-align: center;">NOTAS DE PRODUCCIÓN Y TALLER</div>
                        <div style="padding: 10px; font-size: 11px; white-space: pre-wrap; font-weight: bold;"><?= htmlspecialchars($cotizacion['notas']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <table class="footer-notes">
                <tr><th colspan="1">NOTAS INFORMATIVAS DE LA MUESTRA</th></tr>
                <tr><td>Este documento representa una solicitud de confección física de muestra y <strong>carece de valor comercial o fiscal</strong>.</td></tr>
                <tr><td>Los colores de los hilos para bordar es el color más aproximado al color digital del logotipo.</td></tr>
                <tr><td>Los colores de la tela del proveedor es lo mas aproximado a los colores del diseño digital, se envía muestra física y ficha técnica de la tela para su aprobación.</td></tr>
                <tr><td>La Muestra será enviada al cliente para evaluación de tela, bordados y confección antes de iniciar la producción en masa.</td></tr>
                <tr><td style="color: #0B4C8C; font-size: 11px;">MÉTODO Y LUGAR DE ENTREGA DE MUESTRA: <?= htmlspecialchars($metodo_entrega) ?></td></tr>
            </table>
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
            filename:     'Muestra_Fisica_<?= $cotizacion["nro_cotizacion"] ?>.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 2, useCORS: true, width: 794 },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opciones).from(elemento).save().then(() => {
            btn.innerHTML = '📥 Descargar Muestra Físicas PDF';
            btn.disabled = false;
            elemento.style.boxShadow = '0 5px 15px rgba(0,0,0,0.5)';
        });
    }
    </script>
</body>
</html>