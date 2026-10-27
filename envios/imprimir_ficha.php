<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario_id'])) { die("Acceso denegado. Sesión expirada."); }

$id_orden = intval($_GET['id'] ?? 0);
if ($id_orden == 0) die("ID de orden inválido.");

// Extraer todos los datos cruzados
$sql = "SELECT o.nro_orden_fabrica, c.nombre as cliente, cot.nro_cotizacion, cot.total as cot_total,
               fp.*, u.nombre as ven_nom, u.apellido as ven_ape
        FROM orden_fabrica o
        JOIN cotizacion cot ON o.id_cotizacion = cot.ID
        JOIN cliente c ON o.id_cliente = c.ID
        LEFT JOIN usuario u ON cot.id_vendedor = u.ID
        LEFT JOIN ficha_pago_envio fp ON o.ID = fp.id_orden
        WHERE o.ID = $id_orden";

$q = mysqli_query($conexion, $sql);
$data = mysqli_fetch_assoc($q);

if (!$data) die("No se encontró la orden solicitada.");

// Realizar todos los cálculos matemáticos para la impresión
$tasa = floatval($data['tasa_bcv'] ?? 0);
$porc_ofi = floatval($data['porc_oficina'] ?? 10);
$porc_ven = floatval($data['porc_vendedor'] ?? 5);
$abono_usd = floatval($data['monto_abonos_manual'] ?? 0);
$cot_total_usd = floatval($data['cot_total']);

$cot_total_bs = $cot_total_usd * $tasa;
$com_ofi_usd = $cot_total_usd * ($porc_ofi / 100);
$com_ven_usd = $cot_total_usd * ($porc_ven / 100);
$adm_usd = $cot_total_usd - $com_ofi_usd - $com_ven_usd;

$com_ofi_bs = $com_ofi_usd * $tasa;
$com_ven_bs = $com_ven_usd * $tasa;
$adm_bs = $adm_usd * $tasa;
$comprobacion_bs = $com_ofi_bs + $com_ven_bs + $adm_bs;

$abono_bs = $abono_usd * $tasa;

$consumos = [];
if (!empty($data['consumos_json'])) {
    $consumos = json_decode($data['consumos_json'], true);
}

$total_consumos_bs = 0;
foreach($consumos as $c) {
    $total_consumos_bs += floatval($c['monto']);
}

$saldo_pendiente = $abono_bs - $total_consumos_bs;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha de Liquidación - <?php echo $data['nro_orden_fabrica']; ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; margin: 0; padding: 30px; color: #000; }
        .header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
        .header img { height: 45px; } /* Ajustado para el logo unifuerte.jpg */
        .header-info { text-align: right; }
        .header-info h2 { margin: 0; font-size: 16px; text-transform: uppercase; font-weight: bold; background: #e0e0e0; padding: 4px 10px; border: 1px solid #000; display: inline-block; }
        .nro-orden { font-weight: bold; font-size: 14px; margin-bottom: 5px; border: 1px solid #000; padding: 4px 10px; display: inline-block;}
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: center; }
        th { background-color: #eaeaea; font-size: 11px; text-transform: uppercase; }
        .bg-yellow { background-color: #ffea75 !important; -webkit-print-color-adjust: exact; font-weight: bold; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        
        .box-container { display: flex; justify-content: space-between; gap: 40px; margin-bottom: 20px; align-items: flex-start; }
        .summary-box { width: 45%; }
        .consumos-table { width: 45%; }
        .consumos-table th, .consumos-table td { padding: 4px 8px; font-size: 11px; }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <!-- Reemplazo de camisa.jpg por unifuerte.jpg -->
        <img src="../../img/unifuerte.jpg" alt="Logo Unifuerte">
        <div class="header-info">
            <div class="nro-orden">Nro. <?php echo $data['nro_orden_fabrica']; ?> / <?php echo $data['nro_cotizacion']; ?></div><br>
            <h2>CLIENTE: <?php echo htmlspecialchars($data['cliente']); ?></h2>
            <p style="margin: 5px 0 0 0; font-size: 12px;"><b>Vendedor:</b> <?php echo htmlspecialchars($data['ven_nom'] . ' ' . $data['ven_ape']); ?></p>
        </div>
    </div>

    <!-- TABLA 1: DATOS GENERALES -->
    <table>
        <thead>
            <tr>
                <th>Cotización</th>
                <th>Comisión Oficina <?php echo $porc_ofi; ?>%</th>
                <th>Comisión Vendedor <?php echo $porc_ven; ?>%</th>
                <th>Tasa BCV</th>
                <th>Total en Bs.</th>
                <th>Comisión Oficina (Bs)</th>
                <th>Comisión Vendedor (Bs)</th>
                <th>Pago o Abono Recibido</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="fw-bold">$ <?php echo number_format($cot_total_usd, 2); ?></td>
                <td>$ <?php echo number_format($com_ofi_usd, 2); ?></td>
                <td>$ <?php echo number_format($com_ven_usd, 2); ?></td>
                <td>Bs. <?php echo number_format($tasa, 4); ?></td>
                <td class="bg-yellow">Bs. <?php echo number_format($cot_total_bs, 2); ?></td>
                <td>Bs. <?php echo number_format($com_ofi_bs, 2); ?></td>
                <td>Bs. <?php echo number_format($com_ven_bs, 2); ?></td>
                <td class="fw-bold bg-yellow">$ <?php echo number_format($abono_usd, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="box-container">
        <!-- TABLA 2: DISTRIBUCIÓN -->
        <div class="summary-box">
            <table style="width: 100%;">
                <tr>
                    <th class="text-left">Oficina</th>
                    <td>Bs. <?php echo number_format($com_ofi_bs, 2); ?></td>
                    <td>$ <?php echo number_format($com_ofi_usd, 2); ?></td>
                </tr>
                <tr>
                    <th class="text-left">Vendedor</th>
                    <td>Bs. <?php echo number_format($com_ven_bs, 2); ?></td>
                    <td>$ <?php echo number_format($com_ven_usd, 2); ?></td>
                </tr>
                <tr>
                    <th class="text-left">Administrador</th>
                    <td class="bg-yellow">Bs. <?php echo number_format($adm_bs, 2); ?></td>
                    <td>$ <?php echo number_format($adm_usd, 2); ?></td>
                </tr>
                <tr>
                    <th class="text-left">Comprobación</th>
                    <td colspan="2" class="bg-yellow text-center fw-bold">Bs. <?php echo number_format($comprobacion_bs, 2); ?></td>
                </tr>
            </table>
        </div>

        <!-- TABLA 3: CONSUMOS Y SALDOS -->
        <div class="consumos-table">
            <table>
                <thead>
                    <tr>
                        <th colspan="2">CONSUMOS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($consumos as $c): ?>
                    <tr>
                        <td class="text-left fw-bold"><?php echo htmlspecialchars($c['item']); ?></td>
                        <td class="text-right">Bs. <?php echo number_format(floatval($c['monto']), 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th class="text-right">TOTAL</th>
                        <td class="bg-yellow text-right fw-bold">Bs. <?php echo number_format($total_consumos_bs, 2); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <table style="margin-top: 15px; width: 100%;">
                <tr>
                    <th class="text-left" style="font-size: 14px;">Saldo Pendiente</th>
                    <td class="bg-yellow text-right fw-bold" style="font-size: 14px;">Bs. <?php echo number_format($saldo_pendiente, 2); ?></td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>