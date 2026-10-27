<?php
session_start();
error_reporting(0);

require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$id_vale = intval($_GET['id'] ?? 0);

$q = mysqli_query($conexion, "
    SELECT c.*, e.nombre, e.apellido, e.cedula 
    FROM nomina_cuentas c 
    INNER JOIN nomina_empleados e ON c.id_empleado = e.id 
    WHERE c.id = $id_vale AND c.tipo_cuenta = 'Por Cobrar'
");

if (mysqli_num_rows($q) == 0) {
    die("Vale no encontrado o ya no está disponible.");
}
$vale = mysqli_fetch_assoc($q);

$fecha_vale = date('d/m/Y', strtotime($vale['fecha']));
$nro_vale = sprintf("%06d", $vale['id']);
$concepto = mb_strtoupper($vale['concepto'], 'UTF-8');
$nombre_completo = mb_strtoupper($vale['nombre'] . ' ' . $vale['apellido'], 'UTF-8');

// Limpieza estricta de la cédula para que NUNCA salga V-V-
$ced_limpia = "V-" . ltrim(preg_replace('/^V\s*-\s*/i', '', trim($vale['cedula'])));

$moneda = $vale['moneda'] ?? 'USD';
$tasa_bcv = floatval($vale['tasa_bcv'] ?? 0);
$monto_original = floatval(($vale['monto_original'] > 0) ? $vale['monto_original'] : $vale['monto']);
$monto_usd = floatval($vale['monto']);

$monto_principal = $monto_original;
$enteros = floor($monto_principal);
$decimales = round(($monto_principal - $enteros) * 100);

if ($moneda === 'BS') {
    $simbolo_principal = 'Bs.';
    $texto_moneda = ($enteros == 1) ? 'BOLÍVAR' : 'BOLÍVARES';
    $monto_secundario = "Equivalente en Nómina: USD $ " . number_format($monto_usd, 2) . " (Tasa: Bs " . number_format($tasa_bcv, 2) . ")";
} else {
    $simbolo_principal = 'USD $';
    $texto_moneda = ($enteros == 1) ? 'DÓLAR AMERICANO' : 'DÓLARES AMERICANOS';
    $monto_secundario = $tasa_bcv > 0 ? "Equivalente Entregado: Bs " . number_format($monto_original * $tasa_bcv, 2) . " (Tasa: Bs " . number_format($tasa_bcv, 2) . ")" : "";
}

function numeroALetras($num) {
    $unidades = ["", "UN", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE"];
    $decenas = ["", "DIEZ", "VEINTE", "TREINTA", "CUARENTA", "CINCUENTA", "SESENTA", "SETENTA", "OCHENTA", "NOVENTA"];
    $especiales = [11=>"ONCE", 12=>"DOCE", 13=>"TRECE", 14=>"CATORCE", 15=>"QUINCE", 16=>"DIECISEIS", 17=>"DIECISIETE", 18=>"DIECIOCHO", 19=>"DIECINUEVE", 20=>"VEINTE"];
    
    $num = floor($num);
    if ($num == 0) return "CERO";
    if ($num == 100) return "CIEN";
    if (isset($especiales[$num])) return $especiales[$num];
    
    if ($num < 10) return $unidades[$num];
    if ($num < 100) {
        $dec = floor($num / 10);
        $uni = $num % 10;
        if ($uni == 0) return $decenas[$dec];
        if ($dec == 2) return "VEINTI" . $unidades[$uni];
        return $decenas[$dec] . " Y " . $unidades[$uni];
    }
    if ($num > 100 && $num < 1000) {
        $cen = floor($num / 100);
        $resto = $num % 100;
        $centenas = ["", "CIENTO", "DOSCIENTOS", "TRESCIENTOS", "CUATROCIENTOS", "QUINIENTOS", "SEISCIENTOS", "SETECIENTOS", "OCHOCIENTOS", "NOVECIENTOS"];
        return $centenas[$cen] . ($resto > 0 ? " " . numeroALetras($resto) : "");
    }
    return $num;
}

$monto_letras = numeroALetras($enteros) . " " . $texto_moneda . " CON " . sprintf("%02d", $decimales) . "/100";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vale Nro <?= $nro_vale ?> - <?= $nombre_completo ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #525659; margin: 0; padding: 20px; }
        @page { size: letter; margin: 0; }
        .page { 
            width: 21.59cm; 
            height: 27.94cm; 
            background: #fff; 
            margin: 0 auto; 
            padding: 1.5cm; 
            box-shadow: 0 0 10px rgba(0,0,0,0.5); 
            box-sizing: border-box; 
        }
        .recibo-container { 
            border: 2px solid #0B4C8C; 
            border-radius: 10px; 
            padding: 20px 30px; 
            margin-bottom: 0; 
            position: relative;
            height: 11cm;
            box-sizing: border-box;
        }
        .linea-corte { border-top: 1px dashed #999; margin: 20px 0; text-align: center; }
        .linea-corte::after { content: '✂ Copia Empresa / Copia Trabajador ✂'; background: #fff; padding: 0 15px; color: #999; font-size: 11px; position: relative; top: -8px; }
        .header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
        .logo { max-width: 150px; object-fit: contain; }
        .title-box { text-align: center; flex-grow: 1; margin-top: 10px; }
        .title-box h1 { color: #0B4C8C; margin: 0; font-size: 26px; letter-spacing: 2px; text-transform: uppercase; }
        .title-box .nro { color: #d9534f; font-size: 18px; font-weight: bold; margin-top: 5px; }
        .amount-box { border: 2px solid #0B4C8C; padding: 8px 20px; font-size: 18px; font-weight: bold; border-radius: 6px; margin-top: 10px; display: inline-block; background-color: #f8f9fa; }
        .row-text { font-size: 14px; margin-bottom: 15px; line-height: 1.6; display: flex; align-items: baseline; }
        .row-text strong { margin-right: 10px; white-space: nowrap; color: #333; }
        .row-text .line { border-bottom: 1px solid #666; flex-grow: 1; padding-left: 10px; font-weight: bold; font-family: monospace; font-size: 15px; color: #000; }
        .nota-descuento { text-align: center; font-size: 11px; font-style: italic; color: #555; margin-top: 20px; margin-bottom: 15px; }
        .nota-equivalencia { text-align: center; font-size: 12px; font-weight: bold; color: #0B4C8C; margin-bottom: 15px; }
        .firmas-container { display: flex; justify-content: space-between; margin-top: 30px; text-align: center; position: relative;}
        .firma-box { width: 40%; position: relative; }
        .firma-line { border-top: 1px solid #000; padding-top: 5px; font-size: 12px; font-weight: bold; margin-top: 50px; }
        .sello-img { position: absolute; bottom: 15px; left: 50%; transform: translateX(-50%); max-height: 80px; max-width: 150px; opacity: 0.85; z-index: 0;}
        .firma-texto { position: relative; z-index: 1; background-color: rgba(255,255,255,0.7); padding: 0 5px; }
        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .page { box-shadow: none; margin: 0; width: 21.59cm; height: 27.94cm; }
            .no-print { display: none !important; }
        }
        .btn-print { position: fixed; bottom: 30px; right: 30px; background: #0B4C8C; color: #fff; border: none; padding: 15px 25px; border-radius: 50px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 1000; }
        .btn-print:hover { background: #FF7518; }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir Vale</button>

    <div class="page">
        <?php for($i=0; $i<2; $i++): ?>
        <div class="recibo-container">
            <div class="header-row">
                <div>
                    <img src="../../img/unifuerte.jpg" class="logo" alt="Unifuerte">
                    <div style="font-size: 10px; color: #666; margin-top: 5px; max-width: 180px;">
                        RIF: J-41282764-2<br>Zona Industrial UD-321<br>Puerto Ordaz, Edo. Bolívar
                    </div>
                </div>
                
                <div class="title-box">
                    <h1>RECIBO</h1>
                    <div class="nro">Nº <?= $nro_vale ?></div>
                </div>

                <div style="text-align: right;">
                    <div class="amount-box">
                        <?= $simbolo_principal ?> <?= number_format($monto_principal, 2) ?>
                    </div>
                    <div style="font-size: 12px; margin-top: 10px; font-weight: bold; color: #444;">
                        Fecha: <?= $fecha_vale ?>
                    </div>
                </div>
            </div>

            <div class="row-text">
                <strong>He recibido de:</strong>
                <span class="line">UNIFUERTE, C.A.</span>
            </div>

            <div class="row-text">
                <strong>La suma de:</strong>
                <span class="line"><?= $monto_letras ?></span>
            </div>

            <div class="row-text">
                <strong>Por concepto de:</strong>
                <span class="line"><?= $concepto ?></span>
            </div>

            <?php if($monto_secundario != ""): ?>
            <div class="nota-equivalencia">
                <?= $monto_secundario ?>
            </div>
            <?php endif; ?>

            <div class="nota-descuento">
                * Autorizo expresamente a la empresa a descontar el monto en dólares señalado de mi próxima liquidación de nómina. *
            </div>

            <div class="firmas-container">
                <div class="firma-box">
                    <div class="firma-line">
                        <span class="firma-texto">Entregué Conforme (Gerencia)</span>
                    </div>
                    <img src="../../envios/img/firma.jpeg" class="sello-img" alt="Sello Empresa" onerror="this.onerror=null; this.src='../../envios/img/firma.jpg'; if(this.width==0){ this.style.display='none'; }">
                </div>

                <div class="firma-box">
                    <div class="firma-line" style="margin-top: 10px;">
                        <span class="firma-texto"><?= $nombre_completo ?></span><br>
                        <span class="firma-texto" style="font-weight: normal; font-size: 11px;">C.I: <?= $ced_limpia ?></span><br>
                        <span class="firma-texto" style="font-size: 11px;">Recibí Conforme</span>
                    </div>
                </div>
            </div>
        </div>
        <?php if($i == 0): ?><div class="linea-corte"></div><?php endif; ?>
        <?php endfor; ?>
    </div>
</body>
</html>