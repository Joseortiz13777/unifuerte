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

// Buscar todos los pagos de esa quincena
$q = mysqli_query($conexion, "
    SELECT p.*, e.nombre, e.apellido, e.cedula, e.cargo, e.departamento 
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
    'DISEÑO' => [],
    'CONFECCION' => [],
    'ACABADO FINAL' => [],
    'PERSONAL DE APOYO' => [],
    'OTROS' => []
];

$tasa_bcv_general = 0;

while ($r = mysqli_fetch_assoc($q)) {
    $c = strtoupper($r['cargo']);
    $d = strtoupper($r['departamento']);
    
    if (strpos($d, 'ADMINISTRA') !== false || strpos($c, 'GERENTE') !== false || strpos($c, 'PRESIDENTE') !== false) {
        $dep = 'ADMINISTRACION';
    } elseif (strpos($d, 'DISEÑO') !== false || strpos($c, 'DISEÑO') !== false || strpos($c, 'DISENO') !== false) {
        $dep = 'DISEÑO';
    } elseif (strpos($d, 'CONFECCION') !== false || strpos($c, 'COSTURERA') !== false || strpos($c, 'CORTE') !== false || strpos($c, 'CORTADORA') !== false || strpos($c, 'AYUDANTE') !== false) {
        $dep = 'CONFECCION';
    } elseif (strpos($d, 'ACABADO') !== false || strpos($d, 'BORDADO') !== false || strpos($c, 'BORDAD') !== false) {
        $dep = 'ACABADO FINAL';
    } elseif (strpos($d, 'APOYO') !== false || strpos($c, 'LIMPIEZA') !== false || strpos($c, 'CONTADOR') !== false || strpos($c, 'TECNICO') !== false || strpos($c, 'TESISTA') !== false || strpos($c, 'PASANTE') !== false) {
        $dep = 'PERSONAL DE APOYO';
    } else {
        $dep = 'OTROS';
    }
    
    $departamentos[$dep][] = $r;
    $tasa_bcv_general = $r['tasa_bcv'];
}

// Colores exactos basados en la foto
$bg_colors = [
    'ADMINISTRACION' => '#FFF2CC', // Amarillo
    'DISEÑO' => '#DDEBF7', // Azul claro
    'CONFECCION' => '#E4DFEC', // Morado
    'ACABADO FINAL' => '#E2EFDA', // Verde claro
    'PERSONAL DE APOYO' => '#FCE4D6', // Naranja suave
    'OTROS' => '#EDEDED' // Gris
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nómina de Pago - <?php echo date('d/m/Y', strtotime($fecha_pago)); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; color: #000; }
        .hoja { max-width: 900px; margin: auto; }
        .table { width: 100%; border-collapse: collapse; text-align: center; margin-top: 15px; }
        .table th, .table td { border: 1px solid #000; padding: 6px; vertical-align: middle; }
        
        .bg-header { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; font-weight: bold; }
        
        .header-boxes { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
        .logo { height: 60px; object-fit: contain; }
        .box-info { border: 1px solid #000; text-align: center; width: 220px; font-size: 12px; font-weight: bold; }
        .box-info .title { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; padding: 4px; border-bottom: 1px solid #000; }
        .box-info .content { padding: 6px; }
        
        .text-start { text-align: left; padding-left: 10px !important; }
        .text-end { text-align: right; padding-right: 10px !important; }
        
        @media print {
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

    <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir Resumen</button>

    <div class="hoja">
        <div class="header-boxes">
            <div>
                <img src="../../img/unifuerte.jpg" class="logo" alt="Logo">
            </div>
            
            <div class="box-info" style="border: 2px solid #000;">
                <div class="title" style="font-size: 14px;">RESUMEN DE NÓMINA</div>
                <div class="content" style="font-size: 11px;">Fecha: <?php echo date('d/m/Y', strtotime($fecha_pago)); ?></div>
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
                    <th style="width: 5%;">Nro.</th>
                    <th class="text-start" style="width: 35%;">Nombre y Apellido</th>
                    <th style="width: 30%;">Cargo</th>
                    <th style="width: 15%;">Total a pagar en $</th>
                    <th style="width: 15%;">Pago en bolívares</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $nro = 1;
                $totales = [
                    'pagar_usd' => 0, 'pagar_bs' => 0
                ];

                foreach ($departamentos as $nombre_dep => $empleados) {
                    if (count($empleados) == 0) continue;

                    $color = $bg_colors[$nombre_dep];
                    echo "<tr><td colspan='5' style='background-color: {$color} !important; font-weight: bold; font-size: 12px; letter-spacing: 1px; -webkit-print-color-adjust: exact;'>{$nombre_dep}</td></tr>";

                    foreach ($empleados as $e) {
                        $pagar_usd = floatval($e['total_pagado']);
                        $pagar_bs = floatval($e['pago_bs']);

                        $totales['pagar_usd'] += $pagar_usd; 
                        $totales['pagar_bs'] += $pagar_bs;

                        echo "<tr>";
                        echo "<td>{$nro}</td>";
                        echo "<td class='text-start'>" . mb_strtoupper($e['nombre'] . ' ' . $e['apellido'], 'UTF-8') . "</td>";
                        echo "<td>" . mb_strtoupper($e['cargo'], 'UTF-8') . "</td>";
                        echo "<td>$ " . number_format($pagar_usd, 2) . "</td>";
                        echo "<td>Bs. " . number_format($pagar_bs, 2) . "</td>";
                        echo "</tr>";
                        
                        $nro++;
                    }
                }
                ?>
            </tbody>
            <tfoot>
                <tr class="bg-header" style="border: 2px solid #000; font-size: 13px;">
                    <td colspan="3" class="text-end" style="padding-right: 15px !important;">TOTAL GENERAL</td>
                    <td>$ <?php echo number_format($totales['pagar_usd'], 2); ?></td>
                    <td>Bs. <?php echo number_format($totales['pagar_bs'], 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>