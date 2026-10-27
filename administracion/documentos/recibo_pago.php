<?php
session_start();
require_once '../../conexion.php';

// 1. AUTO-CREACIÓN DE LA TABLA (Si no existe)
$sql_create = "CREATE TABLE IF NOT EXISTS descripcion_egreso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_bd VARCHAR(50) NOT NULL,
    id_bd INT NOT NULL,
    datos_json TEXT,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (tipo_bd, id_bd)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
mysqli_query($conexion, $sql_create);

// 2. RECEPCIÓN DE GUARDADO AUTOMÁTICO DE LOS CAMBIOS (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_edicion') {
    $tipo_post = mysqli_real_escape_string($conexion, $_POST['tipo']);
    $id_post = intval($_POST['id']);
    $datos_json = mysqli_real_escape_string($conexion, $_POST['datos_json']);

    if (empty($_POST['datos_json'])) {
        mysqli_query($conexion, "DELETE FROM descripcion_egreso WHERE tipo_bd = '$tipo_post' AND id_bd = $id_post");
    } else {
        $sql_upsert = "INSERT INTO descripcion_egreso (tipo_bd, id_bd, datos_json) 
                       VALUES ('$tipo_post', $id_post, '$datos_json') 
                       ON DUPLICATE KEY UPDATE datos_json = '$datos_json'";
        mysqli_query($conexion, $sql_upsert);
    }
    echo json_encode(['success' => true]);
    exit;
}

// 3. SEGURIDAD DE VISTA PÚBLICA VS ADMIN
$es_admin = isset($_SESSION['usuario_id']) && isset($_SESSION['rol']) && (strtolower($_SESSION['rol']) === 'admin' || strtolower($_SESSION['rol']) === 'master');
$ed = $es_admin ? 'contenteditable="true" class="editable"' : 'class="editable"';

// 4. LÓGICA DE EXTRACCIÓN DE DATOS PRINCIPAL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'plantilla';
$pago_id = max(0, intval($_GET['pago_id'] ?? 0));
$pago_numero = max(0, intval($_GET['pago_numero'] ?? 0));

$prov_nombre = "N/D";
$prov_doc = "N/D";
$prov_tlf = "N/D";
$prov_dir = "N/D";
$fecha_pago = date('Y-m-d');
$doc_nro = "N/D";
$monto_total = 0;
$monto_deuda = 0;
$monto_pago_actual = null;
$tasa_bcv = 0;
$monto_bs = 0;
$numero_pago = str_pad($id, 6, "0", STR_PAD_LEFT);
$items = [];
$pagos = [];
$moneda = 'USD'; // Por defecto se rige en USD

function limpiarUTF8($str) {
    if ($str === null) return '';
    return mb_convert_encoding($str, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
}

function formatoBs($num) {
    return number_format((float)$num, 2, ',', '.');
}

if ($tipo === 'compra') {
    $q = mysqli_query($conexion, "SELECT * FROM compra WHERE ID = $id");
    if ($q && $r = mysqli_fetch_assoc($q)) {
        $moneda = strtoupper(trim($r['moneda'] ?? 'USD'));
        $id_prov = isset($r['id_provedor']) ? intval($r['id_provedor']) : 0;
        $qp = mysqli_query($conexion, "SELECT nombre, RIF_cedula, tlf, ubicacion FROM provedor WHERE ID = $id_prov");
        if ($qp && $rp = mysqli_fetch_assoc($qp)) {
            $prov_nombre = $rp['nombre'] ?: "N/D";
            $prov_doc = $rp['RIF_cedula'] ?: "N/D";
            $prov_tlf = $rp['tlf'] ?: "N/D";
            $prov_dir = $rp['ubicacion'] ?: "N/D";
        }
        $fecha_pago = $r['fecha_compra'] ?? date('Y-m-d');
        $monto_total = $r['monto_pagado'] ?? 0;
        $monto_deuda = (float)($r['costo'] ?? 0);
        $doc_nro = "COMPRA-" . ($r['nro_factura'] ?? $id);

        $qd = mysqli_query($conexion, "SELECT d.cantidad, d.precio_unitario, m.nombre FROM detalle_compra d JOIN materia_prima m ON d.id_materia = m.ID WHERE d.id_compra = $id");
        if ($qd) {
            while ($rd = mysqli_fetch_assoc($qd)) {
                $items[] = ['desc' => $rd['nombre'], 'cant' => $rd['cantidad'], 'precio' => $rd['precio_unitario'], 'sub' => $rd['cantidad'] * $rd['precio_unitario']];
            }
        }
        
        $q_pagos = mysqli_query($conexion, "SELECT id, fecha_pago, metodo_pago, referencia, monto_usd, tasa_bcv, monto_bs FROM pago_compra WHERE id_compra = $id ORDER BY id ASC");
        if ($q_pagos) {
            $indicePago = 0;
            while ($rp = mysqli_fetch_assoc($q_pagos)) {
                $indicePago++;
                $monto_mostrar = ($moneda === 'BS' || $moneda === 'VES') ? (float)$rp['monto_bs'] : (float)$rp['monto_usd'];
                $pagos[] = ['fecha' => date('d/m/Y', strtotime($rp['fecha_pago'])), 'metodo' => limpiarUTF8($rp['metodo_pago']), 'ref' => limpiarUTF8($rp['referencia']), 'monto' => $monto_mostrar];
                if (($pago_id > 0 && intval($rp['id']) === $pago_id) || ($pago_numero > 0 && $indicePago === $pago_numero)) {
                    $monto_pago_actual = $monto_mostrar;
                    $numero_pago = 'AB-' . str_pad((string)$rp['id'], 6, '0', STR_PAD_LEFT);
                }
                if (isset($rp['tasa_bcv']) && $rp['tasa_bcv'] > 0) $tasa_bcv = (float)$rp['tasa_bcv'];
                if (isset($rp['monto_bs']) && $rp['monto_bs'] > 0) $monto_bs += (float)$rp['monto_bs'];
            }
        }
        if ($monto_bs == 0 && $tasa_bcv > 0) $monto_bs = $monto_total * $tasa_bcv;
    }
} elseif ($tipo === 'gasto') {
    $q = mysqli_query($conexion, "SELECT * FROM gastos_servicios WHERE id = $id");
    if ($q && $r = mysqli_fetch_assoc($q)) {
        $id_prov = isset($r['id_proveedor']) ? intval($r['id_proveedor']) : 0;
        $qp = mysqli_query($conexion, "SELECT nombre, RIF_cedula, tlf, ubicacion FROM provedor WHERE ID = $id_prov");
        if ($qp && $rp = mysqli_fetch_assoc($qp)) {
            $prov_nombre = $rp['nombre'] ?: "N/D";
            $prov_doc = $rp['RIF_cedula'] ?: "N/D";
            $prov_tlf = $rp['tlf'] ?: "N/D";
            $prov_dir = $rp['ubicacion'] ?: "N/D";
        }
        $moneda = strtoupper(trim($r['moneda'] ?? 'USD'));
        $fecha_pago = $r['fecha'] ?? date('Y-m-d');
        $monto_total = $r['monto_pagado'] ?? 0;
        $monto_deuda = (float)($r['monto_total'] ?? 0);
        $doc_nro = "GASTO-" . ($r['factura'] ?? $r['doc_referencia'] ?? $id);
        
        $desc_texto = $r['descripcion'] ?? $r['concepto'] ?? $r['observacion'] ?? 'Sin descripción';
        $json = json_decode($r['descripcion'] ?? '', true);
        if (is_array($json)) {
            foreach ($json as $j) { 
                $items[] = ['desc' => $j['descripcion'] ?? $j['concepto_gasto'] ?? $j['concepto'] ?? $j['observacion'] ?? 'Sin descripción', 'cant' => $j['cantidad'] ?? 1, 'precio' => $j['costo'] ?? $j['monto'] ?? 0, 'sub' => ($j['costo'] ?? $j['monto'] ?? 0) * ($j['cantidad'] ?? 1)]; 
            }
        } else {
            $items[] = ['desc' => $desc_texto, 'cant' => $r['cantidad'] ?? 1, 'precio' => $monto_deuda, 'sub' => $monto_deuda];
        }
        $q_pagos = mysqli_query($conexion, "SELECT id, fecha_pago, metodo_pago, referencia, monto_usd, tasa_bcv, monto_bs FROM pago_gastos_servicios WHERE id_gasto = $id ORDER BY id ASC");
        if ($q_pagos) {
            $indicePago = 0;
            while ($rp = mysqli_fetch_assoc($q_pagos)) {
                $indicePago++;
                $monto_mostrar = ($moneda === 'BS' || $moneda === 'VES') ? (float)$rp['monto_bs'] : (float)$rp['monto_usd'];
                $pagos[] = ['fecha' => date('d/m/Y', strtotime($rp['fecha_pago'])), 'metodo' => limpiarUTF8($rp['metodo_pago']), 'ref' => limpiarUTF8($rp['referencia']), 'monto' => $monto_mostrar];
                if (($pago_id > 0 && intval($rp['id']) === $pago_id) || ($pago_numero > 0 && $indicePago === $pago_numero)) {
                    $monto_pago_actual = $monto_mostrar;
                    $numero_pago = 'AB-' . str_pad((string)$rp['id'], 6, '0', STR_PAD_LEFT);
                }
                if (isset($rp['tasa_bcv']) && $rp['tasa_bcv'] > 0) $tasa_bcv = (float)$rp['tasa_bcv'];
                if (isset($rp['monto_bs']) && $rp['monto_bs'] > 0) $monto_bs += (float)$rp['monto_bs'];
            }
        }
        if ($monto_bs == 0 && $tasa_bcv > 0) $monto_bs = $monto_total * $tasa_bcv;
    }
} elseif ($tipo === 'os') {
    $q = mysqli_query($conexion, "SELECT * FROM ordenes_servicio WHERE id = $id");
    if ($q && $r = mysqli_fetch_assoc($q)) {
        $id_prov = isset($r['id_proveedor']) ? intval($r['id_proveedor']) : (int)($r['id_provedor'] ?? 0);
        $qp = mysqli_query($conexion, "SELECT nombre, RIF_cedula, tlf, ubicacion FROM provedor WHERE ID = $id_prov");
        if ($qp && $rp = mysqli_fetch_assoc($qp)) {
            $prov_nombre = $rp['nombre'] ?: "N/D";
            $prov_doc = $rp['RIF_cedula'] ?: "N/D";
            $prov_tlf = $rp['tlf'] ?: "N/D";
            $prov_dir = $rp['ubicacion'] ?: "N/D";
        }
        $fecha_pago = $r['fecha_pago_real'] ?? $r['fecha_orden'] ?? $r['fecha'] ?? date('Y-m-d');
        $monto_total = $r['monto_pagado'] ?? 0;
        $monto_deuda = (float)($r['costo'] ?? 0);
        $doc_nro = "OS-" . $id;
        
        $json = json_decode($r['descripcion'] ?? '', true);
        if (is_array($json)) {
            foreach ($json as $j) { 
                $items[] = ['desc' => $j['servicio'] ?? $j['descripcion'] ?? $j['concepto'] ?? 'Sin descripción', 'cant' => $j['cantidad'] ?? 1, 'precio' => $j['costo'] ?? $j['monto'] ?? 0, 'sub' => ($j['costo'] ?? $j['monto'] ?? 0) * ($j['cantidad'] ?? 1)]; 
            }
        } else {
            $desc_texto = $r['descripcion'] ?? $r['concepto'] ?? $r['observacion'] ?? 'Sin descripción';
            $items[] = ['desc' => $desc_texto, 'cant' => 1, 'precio' => $monto_total, 'sub' => $monto_total];
        }

        if (!empty($r['metodo_pago'])) {
            $metodos = explode('|', $r['metodo_pago']);
            $refs = explode('|', $r['referencia'] ?? '');
            foreach ($metodos as $idx => $str) {
                $str = trim($str);
                if (!$str) continue;
                if (preg_match('/^(.*?)\s\(\$([0-9.,]+)\sel\s([0-9\/\-]+)(?:\sTasa:\s([0-9.,]+))?.*?\)$/', $str, $matches)) {
                    $pagos[] = ['fecha' => $matches[3], 'metodo' => trim($matches[1]), 'ref' => trim($refs[$idx] ?? 'S/N'), 'monto' => (float)str_replace(',', '', $matches[2])];
                    if ($pago_numero > 0 && $idx + 1 === $pago_numero) {
                        $monto_pago_actual = (float)str_replace(',', '', $matches[2]);
                        $numero_pago = str_pad((string)$id, 6, '0', STR_PAD_LEFT) . '-AB-' . str_pad((string)$pago_numero, 2, '0', STR_PAD_LEFT);
                    }
                    if (isset($matches[4]) && $matches[4] > 0) {
                        $tasa_bcv = (float)str_replace(',', '', $matches[4]);
                        $monto_bs += ((float)str_replace(',', '', $matches[2])) * $tasa_bcv;
                    }
                } else {
                    $pagos[] = ['fecha' => 'N/D', 'metodo' => $str, 'ref' => trim($refs[$idx] ?? 'S/N'), 'monto' => 0];
                }
            }
        }
    }
} elseif ($tipo === 'nomina') {
    $q = mysqli_query($conexion, "SELECT * FROM nomina_lotes WHERE id = $id");
    if ($q && $r = mysqli_fetch_assoc($q)) {
        $prov_nombre = "Nómina General de Trabajadores";
        $prov_doc = "N/A";
        $prov_tlf = "N/A";
        $prov_dir = "Administración Unifuerte";
        $fecha_pago = $r['fecha_pago'] ?? date('Y-m-d');
        $monto_total = $r['monto_pagado'] ?? 0;
        $doc_nro = "LOTE-NOM-" . $id;

        $tasa_bcv = (float)($r['tasa_bcv'] ?? 0);
        $monto_bs = (float)($r['total_bs'] ?? 0);

        $items[] = ['desc' => "Pago de Salarios y Pasivos Laborales correspondientes al Lote " . $id, 'cant' => 1, 'precio' => $monto_total, 'sub' => $monto_total];
        $pagos[] = ['fecha' => date('d/m/Y', strtotime($fecha_pago)), 'metodo' => 'Transferencia Bancaria Masiva / Efectivo', 'ref' => 'S/N', 'monto' => $monto_total];
    }
}

if (count($pagos) === 1) {
    $metodo_ref_texto = $pagos[0]['metodo'] . (!empty($pagos[0]['ref']) && $pagos[0]['ref'] !== 'S/N' ? ' / REF: ' . $pagos[0]['ref'] : '');
} else {
    $metodo_ref_texto = 'VER HISTORIAL (SECCIÓN 4)';
}

// 5. EXTRACCIÓN DE EDICIONES PREVIAS EN LA BASE DE DATOS
$datos_editados_json = "null";
$q_edicion = mysqli_query($conexion, "SELECT datos_json FROM descripcion_egreso WHERE tipo_bd = '$tipo' AND id_bd = $id");
if ($q_edicion && $r_edicion = mysqli_fetch_assoc($q_edicion)) {
    if (!empty($r_edicion['datos_json'])) {
        $datos_editados_json = $r_edicion['datos_json'];
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Egreso #<?= htmlspecialchars($numero_pago) ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }
        body { background: #525659; margin: 0; font-family: 'Arial', sans-serif; color: #000; overflow-x: hidden; }
        
        .top-bar { 
            background-color: #13131A; 
            padding: 15px; 
            text-align: center; 
            position: fixed; 
            top: 0; 
            left: 0; 
            right: 0; 
            z-index: 1000; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.3); 
        }
        
        .btn-print { padding: 10px 20px; margin: 0 5px; font-size: 14px; font-weight: bold; background: #FF7518; color: #FFF; border: none; cursor: pointer; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); transition: 0.3s; display: inline-block; }
        .btn-print:hover { transform: scale(1.05); filter: brightness(1.1); }
        .btn-red { background: #dc3545; }

        .vista-previa { 
            width: 100%; 
            padding-top: <?php echo $es_admin ? '135px' : '90px'; ?>; 
            padding-bottom: 40px; 
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
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
            position: relative; 
            display: inline-block;
        }

        /* AUTO-ESCALA: Si es teléfono, centra y hace zoom-out hacia la izquierda. Mantiene la hoja completa y visible */
        @media (max-width: 820px) {
            .vista-previa {
                text-align: left; /* Clave para el zoom en móviles */
                overflow-x: hidden; 
                padding-left: 0;
            }
            #documento_pdf {
                margin: 0;
                transform-origin: top left !important;
            }
        }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .logo-img { max-height: 70px; }
        .company-info { text-align: right; font-weight: bold; font-size: 10px; line-height: 1.2; }

        .title-box { width: 100%; border: 2px solid #0B4C8C; border-collapse: collapse; text-align: center; margin-bottom: 20px; }
        .title-box td { padding: 8px; border: 2px solid #0B4C8C; font-size: 18px; font-weight: bold; text-transform: uppercase; background-color: #f0f4f8; color:#0B4C8C; }
        .title-box td.nro-box { width: 140px; font-size: 14px; background-color: #0B4C8C; color:#fff; }

        .section-title { font-size: 12px; font-weight: bold; background-color: #0B4C8C; color: white; padding: 5px 10px; margin-bottom: 5px; }

        .client-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 2px solid #000; }
        .client-table td { border: 1px solid #000; padding: 6px; font-size: 11px; vertical-align: middle; }
        .client-table td.lbl { font-weight: bold; background-color: #f0f0f0; width: 25%; } 
        .client-table td.val { font-weight: bold; width: 75%; text-transform: uppercase; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 2px solid #000; }
        .items-table th { border: 1px solid #000; padding: 8px; text-align: center; font-size: 11px; background-color: #f0f0f0; }
        .items-table td { border: 1px solid #000; padding: 8px; text-align: center; vertical-align: middle; font-weight: bold; font-size: 12px; }
        .items-table td.desc { text-align: left; }

        .total-box { float: right; width: 45%; border: 2px solid #000; background-color: #f0f4f8; margin-top: 10px; margin-bottom: 40px; }
        .total-box td { padding: 8px; font-weight: bold; }
        .total-box td.lbl { text-align: left; }
        .total-box td.val { text-align: right; }

        .row-clear::after { content: ""; clear: both; display: table; }

        .signatures { margin-top: 60px; width: 100%; text-align: center; font-size: 12px; font-weight: bold; }
        .signatures td { width: 50%; padding-top: 10px; vertical-align: bottom; height: 100px; }
        .signatures .line { border-top: 1px solid #000; width: 60%; margin: 5px auto; }
        .firma-img { max-height: 80px; position: relative; margin-bottom: -15px; z-index: 1; }

        .footer-notes { margin-top: 50px; width: 100%; border: 2px solid #000; border-collapse: collapse; }
        .footer-notes th { border: 1px solid #000; background-color: #f0f0f0; padding: 4px; font-size: 12px; }
        .footer-notes td { border: 1px solid #000; padding: 6px; font-size: 10px; font-weight: bold; text-align: center; }

        /* Activar diseño de resaltado solo si es Admin */
        <?php if ($es_admin): ?>
        .editable { transition: all 0.2s; border-radius: 3px; padding: 2px; min-width: 20px; display: inline-block; }
        .editable:hover { background-color: #fff3cd; outline: 1px dashed #FF7518; cursor: text; }
        .editable:focus { background-color: #fff; outline: 2px solid #FF7518; cursor: text; }
        <?php else: ?>
        .editable { display: inline-block; }
        <?php endif; ?>
    </style>
</head>
<body>

    <!-- BARRA SUPERIOR SEGÚN EL ROL -->
    <?php if ($es_admin): ?>
    <div class="top-bar no-print">
        <button id="btnDescargar" class="btn-print" onclick="descargarPDF()">📥 Descargar PDF</button>
        <button class="btn-print btn-red" onclick="limpiarCache()">🧹 Restaurar Original</button>
        <div id="estado-edicion" style="color:#bbb; font-size:12px; margin-top:10px;">
            💡 Edita cualquier texto haciendo clic. Se guardará de inmediato en la base de datos para el proveedor.
        </div>
    </div>
    <?php else: ?>
    <div class="top-bar no-print">
        <button id="btnDescargar" class="btn-print" onclick="descargarPDF()">📥 Descargar Comprobante PDF</button>
    </div>
    <?php endif; ?>

    <div class="vista-previa">
        <div id="documento_pdf">
            <table class="header-table">
                <tr>
                    <td style="width: 40%; vertical-align: top;">
                        <img src="../img/unifuerte.jpg" class="logo-img" alt="Unifuerte Logo" onerror="this.style.display='none'">
                    </td>
                    <td class="company-info" style="width: 60%;">
                        UNIFUERTE, C.A<br>
                        R.I.F: J-41282764-2 / R.U.C 1829-2021<br>
                        CR. UD-321, EDIFICIO PARCELA 321-16-10A-1<br>
                        PISO 1, OFICINA, A,B,C, ZONA INDUSTRIAL UD 321<br>
                        CIUDAD GUAYANA, PUERTO ORDAZ, ESTADO BOLIVAR<br>
                        CONTACTO: 04241546889<br>
                        EMAIL: ADMINISTRACION@UNIFUERTE.COM
                    </td>
                </tr>
            </table>

            <table class="title-box">
                <tr>
                    <td>COMPROBANTE DE PAGO A PROVEEDOR / EGRESO</td>
                    <td class="nro-box">
                        No. PAGO:<br>
                        <span <?= $ed ?> style="font-size: 18px;"><?= htmlspecialchars($numero_pago) ?></span>
                    </td>
                </tr>
            </table>

            <div class="section-title">1. DATOS DEL BENEFICIARIO / PROVEEDOR</div>
            <table class="client-table">
                <tr><td class="lbl">Nombre / Razón Social:</td><td class="val"><span <?= $ed ?>><?= htmlspecialchars($prov_nombre) ?></span></td></tr>
                <tr><td class="lbl">Cédula o R.I.F:</td><td class="val"><span <?= $ed ?>><?= htmlspecialchars($prov_doc) ?></span></td></tr>
                <tr><td class="lbl">Teléfono / Contacto:</td><td class="val"><span <?= $ed ?>><?= htmlspecialchars($prov_tlf) ?></span></td></tr>
                <tr><td class="lbl">Dirección:</td><td class="val"><span <?= $ed ?>><?= htmlspecialchars($prov_dir) ?></span></td></tr>
            </table>

            <div class="section-title">2. DETALLES BANCARIOS Y DE TRANSACCIÓN</div>
            <table class="client-table">
                <tr><td class="lbl">Fecha Emisión:</td><td class="val"><span <?= $ed ?>><?= date('d/m/Y', strtotime($fecha_pago)) ?></span></td></tr>
                <tr><td class="lbl">Método y Referencia:</td><td class="val" style="color:#0B4C8C;"><span <?= $ed ?>><?= htmlspecialchars($metodo_ref_texto) ?></span></td></tr>
                <tr><td class="lbl">Moneda de Cálculo:</td><td class="val"><span <?= $ed ?>><?= ($moneda === 'BS' || $moneda === 'VES') ? 'Bolívares Soberanos (BS)' : 'Dólares (USD) / Pagado al Equivalente en BS' ?></span></td></tr>
            </table>

            <div class="section-title">3. DESGLOSE DE SERVICIOS / FACTURAS PAGADAS</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Doc. Referencia</th>
                        <th style="width: 50%;">Descripción y Concepto del Gasto</th>
                        <th style="width: 10%;">Cant.</th>
                        <th style="width: 20%;">Importe Parcial</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $i): ?>
                    <tr>
                        <td><span <?= $ed ?>><?= htmlspecialchars($doc_nro) ?></span></td>
                        <td class="desc"><span <?= $ed ?>><?= htmlspecialchars($i['desc'] ?? 'Sin descripción') ?></span></td>
                        <td><span <?= $ed ?>><?= htmlspecialchars($i['cant'] ?? 1) ?></span></td>
                        <td><?= ($moneda === 'BS' || $moneda === 'VES') ? 'Bs ' : '$ ' ?><span <?= $ed ?>><?= formatoBs($i['sub'] ?? 0) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="section-title">4. HISTORIAL DE ABONOS / PAGOS REALIZADOS</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Fecha</th>
                        <th style="width: 40%;">Método de Pago</th>
                        <th style="width: 20%;">Referencia</th>
                        <th style="width: 20%;">Monto Abonado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $p): ?>
                    <tr>
                        <td><span <?= $ed ?>><?= htmlspecialchars($p['fecha']) ?></span></td>
                        <td class="desc" style="text-transform: uppercase;"><span <?= $ed ?>><?= htmlspecialchars($p['metodo']) ?></span></td>
                        <td><span <?= $ed ?>><?= htmlspecialchars($p['ref']) ?></span></td>
                        <td style="color:#0B4C8C;"><?= ($moneda === 'BS' || $moneda === 'VES') ? 'Bs ' : '$ ' ?><span <?=$ed ?>><?= $p['monto'] > 0 ? formatoBs($p['monto']) : 'Incluido' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pagos)): ?>
                    <tr><td colspan="4" style="color:#888;">No hay abonos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="row-clear">
                <table class="total-box">
                    <tr>
                        <td class="lbl" style="font-size: 13px;">TOTAL SERVICIO / COMPRA:</td>
                        <td class="val" style="font-size: 13px;"><?= ($moneda === 'BS' || $moneda === 'VES') ? 'Bs ' : '$ ' ?><?= formatoBs($monto_deuda) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl" style="font-size: 15px; color: #0B4C8C;">PAGO O ABONO:</td>
                        <td class="val" style="font-size: 15px; color: #0B4C8C;">
                            <?= ($moneda === 'BS' || $moneda === 'VES') ? 'Bs ' : '$ ' ?><?= formatoBs($monto_pago_actual !== null ? $monto_pago_actual : $monto_total) ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="lbl" style="font-size: 11px; color: #666;">TASA DE PAGO BCV:</td>
                        <td class="val" style="font-size: 11px; color: #666;">Bs <?= formatoBs($tasa_bcv) ?> / USD</td>
                    </tr>
                    <tr>
                        <td class="lbl" style="font-size: 11px; color: #666;">SALDO A PAGAR:</td>
                        <td class="val" style="font-size: 11px; color: #666;"><?= ($moneda === 'BS' || $moneda === 'VES') ? 'Bs ' : '$ ' ?><?= formatoBs(max(0, $monto_deuda - $monto_total)) ?></td>
                    </tr>
                    <?php if ($monto_bs > 0): ?>
                    <tr>
                        <td class="lbl" style="font-size: 14px; color: #0B4C8C;">MONTO PAGADO EN BS:</td>
                        <td class="val" style="font-size: 14px; color: #0B4C8C;">Bs <?= formatoBs($monto_bs) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <table class="signatures">
                <tr>
                    <td>
                        <img src="../img/firma.jpeg" class="firma-img" alt="Firma Autorizada" onerror="this.style.display='none'"><br>
                        <div class="line"></div>
                        Firma y Sello Oficial<br>
                        <strong>Administración Unifuerte, C.A</strong>
                    </td>
                    <td>
                        <br><br><br><br>
                        Recibido Conforme Por<br>
                        <strong><span <?= $ed ?>><?= htmlspecialchars($prov_nombre) ?></span></strong>
                    </td>
                </tr>
            </table>

            <table class="footer-notes">
                <tr><th>NOTAS DEL COMPROBANTE</th></tr>
                <tr><td>Este comprobante certifica que Unifuerte, C.A ha realizado la erogación de fondos descrita arriba y salda o abona las deudas correspondientes a la factura o contrato especificado. Cualquier duda o aclaratoria comuníquese con el departamento administrativo.</td></tr>
            </table>
        </div>
    </div>

    <script>
    const esAdmin = <?= $es_admin ? 'true' : 'false' ?>;
    const tipoBd = '<?= $tipo ?>';
    const idBd = <?= $id ?>;
    const datosGuardadosNube = <?= $datos_editados_json ?>;

    document.addEventListener('DOMContentLoaded', () => {
        // Redimensionar para teléfonos automáticamente
        ajustarEscalaMovil();

        // Carga los datos de la base de datos si existen
        if (datosGuardadosNube && typeof datosGuardadosNube === 'object') {
            document.querySelectorAll('.editable').forEach((el, index) => {
                if (datosGuardadosNube['campo_' + index] !== undefined && datosGuardadosNube['campo_' + index] !== '') {
                    el.innerText = datosGuardadosNube['campo_' + index];
                }
            });
            
            if (esAdmin) {
                let estado = document.getElementById('estado-edicion');
                if (estado) {
                    estado.innerText = "☁️ Mostrando datos editados (Traídos de la base de datos).";
                    estado.style.color = "#0dcaf0";
                }
            }
        }

        // Activación del guardado silencioso SOLAMENTE si es Admin
        if (esAdmin) {
            let timeout;
            document.querySelectorAll('.editable').forEach((el) => {
                el.addEventListener('input', () => {
                    let estado = document.getElementById('estado-edicion');
                    if (estado) {
                        estado.innerText = "⏳ Guardando cambios en la nube...";
                        estado.style.color = "#ffc107";
                    }
                    
                    clearTimeout(timeout);
                    timeout = setTimeout(autoGuardarNube, 1000); 
                });
            });
        }
    });

    // Nuevo sistema de auto-escala. 
    // Hace "zoom out" para que el PDF 100% real encaje visualmente en el teléfono.
    function ajustarEscalaMovil() {
        const doc = document.getElementById('documento_pdf');
        const vista = document.querySelector('.vista-previa');
        
        if (window.innerWidth < 820) {
            // Calculamos cuánto debemos reducir el documento de 794px para que quepa en la pantalla
            const scale = (window.innerWidth) / 794;
            
            doc.style.transform = `scale(${scale})`;
            doc.style.transformOrigin = 'top left'; 
            doc.style.margin = '0'; // Forzar que no haya margen para que empiece pegado al borde izquierdo
            
            // Ajustamos el alto del contenedor para que no deje un espacio en blanco gigante abajo
            const originalHeight = doc.offsetHeight;
            vista.style.height = (originalHeight * scale + 100) + 'px';
        } else {
            doc.style.transform = 'none';
            doc.style.transformOrigin = 'top center';
            doc.style.margin = '0 auto';
            vista.style.height = 'auto';
        }
    }

    // Escucha si voltean el teléfono
    window.addEventListener('resize', ajustarEscalaMovil);

    // Función exclusiva de Admin
    function autoGuardarNube() {
        let datosEditados = {};
        document.querySelectorAll('.editable').forEach((el, index) => {
            datosEditados['campo_' + index] = el.innerText.trim();
        });
        
        let formData = new FormData();
        formData.append('accion', 'guardar_edicion');
        formData.append('tipo', tipoBd);
        formData.append('id', idBd);
        formData.append('datos_json', JSON.stringify(datosEditados));

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            let estado = document.getElementById('estado-edicion');
            if (estado && res.success) {
                estado.innerText = "✅ Guardado automáticamente. El proveedor ya puede ver los cambios en su link.";
                estado.style.color = "#28a745";
            }
        }).catch(err => {
            let estado = document.getElementById('estado-edicion');
            if(estado) {
                estado.innerText = "❌ Error de conexión. No se guardaron los últimos cambios.";
                estado.style.color = "#dc3545";
            }
        });
    }

    // Función exclusiva de Admin
    function limpiarCache() {
        if(confirm('¿Seguro que quieres borrar tus ediciones y volver a los datos originales de la base de datos?')) {
            let formData = new FormData();
            formData.append('accion', 'guardar_edicion');
            formData.append('tipo', tipoBd);
            formData.append('id', idBd);
            formData.append('datos_json', '');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(() => location.reload());
        }
    }

    // Función compartida (Admin y Proveedores)
    function descargarPDF() {
        const btn = document.getElementById('btnDescargar');
        btn.innerHTML = '⏳ Generando, por favor espere...';
        btn.disabled = true;

        const elemento = document.getElementById('documento_pdf');
        window.scrollTo(0, 0); 
        
        // Guardar configuración actual antes de imprimir
        const prevTransform = elemento.style.transform;
        const prevTransformOrigin = elemento.style.transformOrigin;
        const prevMargin = elemento.style.margin;
        const prevBoxShadow = elemento.style.boxShadow;

        // Limpiar estilos momentáneamente para asegurar la captura perfecta a escala 100%
        elemento.style.transform = 'none';
        elemento.style.transformOrigin = 'initial';
        elemento.style.margin = '0 auto'; 
        elemento.style.boxShadow = 'none';

        const opciones = {
            margin:       5, 
            filename:     'Comprobante_Pago_Unifuerte_<?= $numero_pago ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, scrollY: 0 },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak:    { mode: 'avoid-all' }
        };

        html2pdf().set(opciones).from(elemento).save().then(() => {
            btn.innerHTML = esAdmin ? '📥 Descargar PDF' : '📥 Descargar Comprobante PDF';
            btn.disabled = false;
            
            // Restaurar los estilos y la escala móvil original
            elemento.style.transform = prevTransform;
            elemento.style.transformOrigin = prevTransformOrigin;
            elemento.style.margin = prevMargin;
            elemento.style.boxShadow = prevBoxShadow;
        });
    }
    </script>
</body>
</html>