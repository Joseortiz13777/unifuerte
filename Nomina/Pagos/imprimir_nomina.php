<?php
session_start();
error_reporting(0);

// Conexión a BD
require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$fecha_pago = mysqli_real_escape_string($conexion, $_GET['fecha'] ?? '');

if (empty($fecha_pago)) {
    die("Fecha de pago no especificada.");
}

// Buscar todos los pagos de esa quincena (Lote)
$q = mysqli_query($conexion, "
    SELECT p.*, e.nombre, e.apellido, e.cedula, e.cargo, e.frecuencia_pago 
    FROM nomina_pagos p 
    INNER JOIN nomina_empleados e ON p.id_empleado = e.id 
    WHERE p.fecha_pago = '$fecha_pago'
    ORDER BY e.cargo ASC, e.nombre ASC
");

if (mysqli_num_rows($q) == 0) {
    die("No se encontraron registros de pago para la fecha: " . $fecha_pago);
}

// Agrupar por Departamento
$departamentos = [
    'ADMINISTRACION' => [],
    'SISTEMAS / DISEÑO' => [],
    'PRODUCCION / CONFECCION' => [],
    'ACABADO FINAL / BORDADO' => [],
    'PERSONAL DE APOYO' => [],
    'OTROS' => []
];

$tasa_bcv_general = 0;

while ($r = mysqli_fetch_assoc($q)) {
    $c = strtoupper($r['cargo']);
    if (strpos($c, 'GERENTE') !== false || strpos($c, 'PRESIDENTE') !== false || strpos($c, 'ADMINISTRA') !== false) {
        $dep = 'ADMINISTRACION';
    } elseif (strpos($c, 'DISEÑO') !== false || strpos($c, 'DISENO') !== false || strpos($c, 'SISTEMA') !== false) {
        $dep = 'SISTEMAS / DISEÑO';
    } elseif (strpos($c, 'COSTURERA') !== false || strpos($c, 'CORTE') !== false || strpos($c, 'CORTADORA') !== false || strpos($c, 'AYUDANTE') !== false) {
        $dep = 'PRODUCCION / CONFECCION';
    } elseif (strpos($c, 'BORDAD') !== false || strpos($c, 'ACABADO') !== false) {
        $dep = 'ACABADO FINAL / BORDADO';
    } elseif (strpos($c, 'LIMPIEZA') !== false || strpos($c, 'CONTADOR') !== false || strpos($c, 'TECNICO') !== false || strpos($c, 'TESISTA') !== false || strpos($c, 'APOYO') !== false) {
        $dep = 'PERSONAL DE APOYO';
    } else {
        $dep = 'OTROS';
    }
    
    $departamentos[$dep][] = $r;
    $tasa_bcv_general = $r['tasa_bcv'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nómina General - <?php echo date('d/m/Y', strtotime($fecha_pago)); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 20px; color: #000; }
        .hoja { max-width: 100%; margin: auto; }
        .table { width: 100%; border-collapse: collapse; text-align: center; margin-top: 15px; }
        .table th, .table td { border: 1px solid #000; padding: 3px; vertical-align: middle; }
        
        /* TODO EN BLANCO, GRIS Y NEGRO */
        .bg-header { background-color: #e0e0e0 !important; -webkit-print-color-adjust: exact; font-weight: bold; }
        .bg-separator { background-color: #f5f5f5 !important; -webkit-print-color-adjust: exact; font-weight: bold; font-size: 11px; letter-spacing: 1px;}
        
        .header-boxes { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
        .logo { height: 60px; object-fit: contain; }
        .box-info { border: 1px solid #000; text-align: center; width: 220px; font-size: 11px; font-weight: bold; }
        .box-info .title { background-color: #e0e0e0 !important; -webkit-print-color-adjust: exact; padding: 4px; border-bottom: 1px solid #000; }
        .box-info .content { padding: 6px; }
        
        .text-start { text-align: left; padding-left: 5px !important; }
        .text-end { text-align: right; padding-right: 5px !important; }
        
        @media print {
            @page { size: landscape; margin: 1cm; }
            body { padding: 0; }
            .no-print { display: none !important; }
        }
        .btn-print {
            position: fixed; bottom: 30px; right: 30px; background-color: #0B4C8C; color: #fff;
            border: none; padding: 15px 25px; border-radius: 50px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 1000;
        }
        .btn-print:hover { background-color: #333; }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir Nómina General</button>

    <div class="hoja">
        <div class="header-boxes">
            <div>
                <img src="../../img/unifuerte.jpg" class="logo" alt="Logo">
            </div>
            
            <div class="box-info" style="border: 2px solid #000;">
                <div class="title" style="font-size: 13px;">NÓMINA GENERAL</div>
                <div class="content">Fecha: <?php echo date('d/m/Y', strtotime($fecha_pago)); ?></div>
            </div>

            <div class="box-info">
                <div class="title">Tasa BCV</div>
                <div class="content">Bs. <?php echo number_format($tasa_bcv_general, 2); ?></div>
                <div class="title">Pago en bolívares</div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr class="bg-header">
                    <th>Nro.</th>
                    <th class="text-start">Nombre y Apellido</th>
                    <th>cargo</th>
                    <th>Sueldo Quincena</th>
                    <th>Cesta ticket</th>
                    <th>Bono</th>
                    <th>Total a percibir<br>Quincenal</th>
                    
                    <!-- EXTRAS -->
                    <th>Hrs Extra<br>Diurna</th>
                    <th>Hrs Extra<br>Noct.</th>
                    <th>Sábados<br>Trabajados</th>
                    <th>Bono<br>Prod.</th>
                    <th>Pago<br>Pendiente</th>
                    
                    <!-- FALTAS -->
                    <th>Dias de falta</th>
                    <th>Horas de falta</th>
                    <th>Total $Dias de<br>falta</th>
                    <th>Total $Horas de<br>falta</th>
                    <th>Pago - Abono -<br>VALE</th>
                    <th>Total de<br>deducciones</th>
                    <th>Total a pagar en $</th>
                    <th>Pago en bolivares</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $nro = 1;
                $totales = [
                    'sueldo' => 0, 'cesta' => 0, 'bono' => 0, 'percibir' => 0, 
                    'he_diurna' => 0, 'he_noct' => 0, 'sabados' => 0, 'prod' => 0, 'pend' => 0,
                    'd_falta_usd' => 0, 'h_falta_usd' => 0, 'vales' => 0, 
                    'deducciones' => 0, 'pagar_usd' => 0, 'pagar_bs' => 0
                ];

                foreach ($departamentos as $nombre_dep => $empleados) {
                    if (count($empleados) == 0) continue;

                    echo "<tr><td colspan='20' class='bg-separator'>{$nombre_dep}</td></tr>";

                    foreach ($empleados as $e) {
                        $s_q = floatval($e['sueldo_quincena']);
                        $c_t = floatval($e['cesta_ticket']);
                        $b_a = floatval($e['bono_ayuda']);
                        $percibir = floatval($e['total_percibir']);
                        
                        $he_diurna = floatval($e['valor_he_diurna']);
                        $he_noct = floatval($e['valor_he_noct']);
                        $sabados = floatval($e['valor_sabados']);
                        $prod = floatval($e['bono_prod']);
                        $pend = floatval($e['pago_pendiente']);

                        $dias_f = floatval($e['dias_falta']);
                        $horas_f = floatval($e['horas_falta']);
                        $m_dias = floatval($e['monto_descuento_dias']);
                        $m_horas = floatval($e['monto_descuento_horas']);
                        
                        $vales = floatval($e['vales']);
                        $tot_deduc = floatval($e['total_deducciones']);
                        
                        $pagar_usd = floatval($e['total_pagado']);
                        $pagar_bs = floatval($e['pago_bs']);

                        // Sumatoria
                        $totales['sueldo'] += $s_q; $totales['cesta'] += $c_t; $totales['bono'] += $b_a; $totales['percibir'] += $percibir;
                        $totales['he_diurna'] += $he_diurna; $totales['he_noct'] += $he_noct; $totales['sabados'] += $sabados; $totales['prod'] += $prod; $totales['pend'] += $pend;
                        $totales['d_falta_usd'] += $m_dias; $totales['h_falta_usd'] += $m_horas; $totales['vales'] += $vales;
                        $totales['deducciones'] += $tot_deduc; $totales['pagar_usd'] += $pagar_usd; $totales['pagar_bs'] += $pagar_bs;

                        echo "<tr>";
                        echo "<td>{$nro}</td>";
                        echo "<td class='text-start'>" . mb_strtoupper($e['nombre'] . ' ' . $e['apellido'], 'UTF-8') . "</td>";
                        echo "<td>" . mb_strtoupper($e['cargo'], 'UTF-8') . "</td>";
                        echo "<td>$ " . number_format($s_q, 2) . "</td>";
                        echo "<td>$ " . number_format($c_t, 2) . "</td>";
                        echo "<td>$ " . number_format($b_a, 2) . "</td>";
                        echo "<td><strong>$ " . number_format($percibir, 2) . "</strong></td>";
                        
                        echo "<td>" . ($he_diurna > 0 ? "$ ".number_format($he_diurna, 2) : '-') . "</td>";
                        echo "<td>" . ($he_noct > 0 ? "$ ".number_format($he_noct, 2) : '-') . "</td>";
                        echo "<td>" . ($sabados > 0 ? "$ ".number_format($sabados, 2) : '-') . "</td>";
                        echo "<td>" . ($prod > 0 ? "$ ".number_format($prod, 2) : '-') . "</td>";
                        echo "<td>" . ($pend > 0 ? "$ ".number_format($pend, 2) : '-') . "</td>";

                        echo "<td>" . ($dias_f > 0 ? $dias_f : '') . "</td>";
                        echo "<td>" . ($horas_f > 0 ? $horas_f : '') . "</td>";
                        echo "<td>" . ($m_dias > 0 ? "$ ".number_format($m_dias, 2) : '-') . "</td>";
                        echo "<td>" . ($m_horas > 0 ? "$ ".number_format($m_horas, 2) : '-') . "</td>";
                        echo "<td>" . ($vales > 0 ? "$ ".number_format($vales, 2) : '-') . "</td>";
                        echo "<td>$ " . number_format($tot_deduc, 2) . "</td>";
                        
                        echo "<td><strong>$ " . number_format($pagar_usd, 2) . "</strong></td>";
                        echo "<td><strong>Bs. " . number_format($pagar_bs, 2) . "</strong></td>";
                        echo "</tr>";
                        
                        $nro++;
                    }
                }
                ?>
            </tbody>
            <tfoot>
                <tr class="bg-header" style="border: 2px solid #000; font-size: 11px;">
                    <td colspan="3" class="text-end" style="padding-right: 15px !important;">TOTAL GENERAL</td>
                    <td>$ <?php echo number_format($totales['sueldo'], 2); ?></td>
                    <td>$ <?php echo number_format($totales['cesta'], 2); ?></td>
                    <td>$ <?php echo number_format($totales['bono'], 2); ?></td>
                    <td>$ <?php echo number_format($totales['percibir'], 2); ?></td>
                    
                    <td>$ <?php echo number_format($totales['he_diurna'], 2); ?></td>
                    <td>$ <?php echo number_format($totales['he_noct'], 2); ?></td>
                    <td>$ <?php echo number_format($totales['sabados'], 2); ?></td>
                    <td>$ <?php echo number_format($totales['prod'], 2); ?></td>
                    <td>$ <?php echo number_format($totales['pend'], 2); ?></td>
                    
                    <td></td>
                    <td></td>
                    <td>$ <?php echo number_format($totales['d_falta_usd'], 2); ?></td>
                    <td>$ <?php echo number_format($totales['h_falta_usd'], 2); ?></td>
                    <td>$ <?php echo number_format($totales['vales'], 2); ?></td>
                    <td>$ <?php echo number_format($totales['deducciones'], 2); ?></td>
                    
                    <td>$ <?php echo number_format($totales['pagar_usd'], 2); ?></td>
                    <td>Bs. <?php echo number_format($totales['pagar_bs'], 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>