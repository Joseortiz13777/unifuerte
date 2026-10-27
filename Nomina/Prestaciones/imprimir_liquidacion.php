<?php
session_start();
error_reporting(0);

// Conexión subiendo dos niveles (Prestaciones -> Nomina -> raíz)
require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$id_prestacion = intval($_GET['id'] ?? 0);

$q = mysqli_query($conexion, "
    SELECT p.*, e.nombre, e.apellido, e.cedula, e.cargo, e.fecha_ingreso, e.salario_base 
    FROM nomina_prestaciones p
    INNER JOIN nomina_empleados e ON p.id_empleado = e.id
    WHERE p.id = $id_prestacion
");

if (!$q || mysqli_num_rows($q) == 0) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Liquidación no encontrada.</h2></div>");
}
$prest = mysqli_fetch_assoc($q);

// ==========================================
// MATEMÁTICAS LOTTT PARA DESGLOSE DE LA HOJA
// ==========================================
$salario_mensual = floatval($prest['salario_base']) * 2;
$salario_diario_basico = $salario_mensual / 30;

$alic_bv = (15 * $salario_diario_basico) / 360;
$alic_uti = (30 * $salario_diario_basico) / 360;

$salario_integral_diario = $salario_diario_basico + $alic_bv + $alic_uti;
$salario_promedio = $salario_diario_basico + $alic_bv;

$ingreso = new DateTime($prest['fecha_ingreso']);
$egreso = new DateTime($prest['fecha_calculo']);
$diff = $ingreso->diff($egreso);
$anios = $diff->y;
$meses = $diff->m;
$dias = $diff->d;

$meses_antiguedad = ($anios * 12) + $meses;
if ($dias >= 15) {
    $meses_antiguedad += 1; 
}
$dias_prestaciones = $meses_antiguedad * 5; 
$monto_prestaciones = $dias_prestaciones * $salario_integral_diario;

$meses_fraccion = $meses + ($dias / 30);
if ($anios == 0 && $meses_fraccion == 0 && $dias > 0) {
    $meses_fraccion = $dias / 30; 
}

$dias_vac_frac = (15 / 12) * $meses_fraccion;
$dias_bvac_frac = (15 / 12) * $meses_fraccion;
$dias_uti_frac = (30 / 12) * $meses_fraccion;

$monto_vac_frac = $dias_vac_frac * $salario_diario_basico;
$monto_bvac_frac = $dias_bvac_frac * $salario_diario_basico;
$monto_uti_frac = $dias_uti_frac * $salario_promedio;

$indemnizacion_despido = ($prest['motivo'] === 'Despido Injustificado') ? $monto_prestaciones : 0;

$total_asig = $monto_prestaciones + $monto_vac_frac + $monto_bvac_frac + $monto_uti_frac + $indemnizacion_despido;
$total_neto = floatval($prest['monto_total']);

$deducciones = $total_asig - $total_neto;
if ($deducciones < 0) $deducciones = 0;

// ==========================================
// CONVERTIR MONTO FINAL A LETRAS
// ==========================================
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
    if ($num >= 1000 && $num < 1000000) {
        $miles = floor($num / 1000);
        $resto = $num % 1000;
        $str_miles = ($miles == 1) ? "MIL" : numeroALetras($miles) . " MIL";
        return $str_miles . ($resto > 0 ? " " . numeroALetras($resto) : "");
    }
    return $num;
}

$centavos = explode('.', number_format($total_neto, 2, '.', ''))[1] ?? '00';
$monto_letras = numeroALetras($total_neto);

$meses_es = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
$dia_impresion = date('d');
$mes_impresion = $meses_es[date('n') - 1];
$anio_impresion = date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidación - <?php echo htmlspecialchars($prest['nombre'] . ' ' . $prest['apellido']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #000;
            background-color: #525659;
            margin: 0;
            padding: 20px;
        }
        .hoja {
            background-color: #fff;
            width: 21.5cm;
            margin: 0 auto;
            padding: 2cm 2.5cm;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            box-sizing: border-box;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        .logo {
            max-width: 200px; 
            height: auto;
            object-fit: contain;
        }
        .info-header {
            text-align: right;
            font-size: 9px;
            color: #444;
            line-height: 1.4;
        }
        .titulo {
            text-align: center;
            font-size: 15px;
            margin-bottom: 25px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 6px 10px;
            text-align: left;
        }
        .bg-light {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        .table-no-border {
            border: none;
        }
        .table-no-border td {
            border: none;
            padding: 4px 10px;
        }
        .table-desglose th {
            text-align: center;
            background-color: #f5f5f5;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .firmas {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }
        .firma-box {
            text-align: center;
            width: 30%;
        }
        .linea-firma {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 11px;
            font-weight: bold;
        }
        @media print {
            body { background-color: #fff; padding: 0; }
            .hoja { box-shadow: none; margin: 0; width: 100%; padding: 1.5cm 2cm; }
            .no-print { display: none !important; }
        }
        .btn-print {
            position: fixed; bottom: 30px; right: 30px;
            background-color: #0B4C8C; color: #fff;
            border: none; padding: 15px 25px; border-radius: 50px;
            font-size: 16px; font-weight: bold; cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            z-index: 1000;
        }
        .btn-print:hover { background-color: #FF7518; }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir Liquidación</button>

    <div class="hoja">
        
        <div class="header">
            <img src="../../img/unifuerte.jpg" class="logo" alt="Logo Unifuerte">
            <div class="info-header">
                RIF J-41282764-2<br>
                CR. UD-321, EDIFICIO PARCELA 321-16-10A-1, PISO 1,<br>
                OFICINA A,B,C, ZONA INDUSTRIAL UD-321 CIUDAD GUAYANA<br>
                PUERTO ORDAZ ESTADO BOLÍVAR<br>
                CONTACTO: (0286) 9940629-0424-1546889<br>
                EMAIL: CLIENTEUNIFUERTE@GMAIL.COM
            </div>
        </div>

        <div class="titulo"><u>LIQUIDACIÓN DEFINITIVA</u></div>

        <!-- TABLA DATOS DEL TRABAJADOR -->
        <table>
            <tr>
                <td class="bg-light" style="width: 25%;">APELLIDOS Y NOMBRES:</td>
                <td colspan="3"><?php echo mb_strtoupper($prest['nombre'] . ' ' . $prest['apellido'], 'UTF-8'); ?></td>
            </tr>
            <tr>
                <td class="bg-light">C.I. No.</td>
                <td><?php echo $prest['cedula']; ?></td>
                <td class="bg-light" style="width: 15%;">CARGO:</td>
                <td><?php echo mb_strtoupper($prest['cargo'], 'UTF-8'); ?></td>
            </tr>
            <tr>
                <td class="bg-light">Fecha de Ingreso:</td>
                <td><?php echo date('d-m-Y', strtotime($prest['fecha_ingreso'])); ?></td>
                <td class="bg-light">Fecha de Egreso:</td>
                <td><?php echo date('d-m-Y', strtotime($prest['fecha_calculo'])); ?></td>
            </tr>
            <tr>
                <td class="bg-light">Tiempo total de servicios:</td>
                <td colspan="3"><?php echo "$anios años, $meses meses, $dias días"; ?></td>
            </tr>
            <tr>
                <td class="bg-light">Motivo del Retiro:</td>
                <td colspan="3"><?php echo mb_strtoupper($prest['motivo'], 'UTF-8'); ?></td>
            </tr>
        </table>

        <!-- TABLA DATOS SALARIALES -->
        <table class="table-no-border" style="margin-bottom: 30px;">
            <tr>
                <td style="width: 25%;"><b>Sueldo mensual:</b></td>
                <td>$<?php echo number_format($salario_mensual, 2); ?></td>
            </tr>
            <tr>
                <td><b>Sueldo Diario Básico:</b></td>
                <td>$<?php echo number_format($salario_diario_basico, 2); ?></td>
            </tr>
            <tr>
                <td><b>Sueldo Promedio:</b></td>
                <td>$<?php echo number_format($salario_promedio, 2); ?></td>
            </tr>
            <tr>
                <td><b>Sueldo Integral:</b></td>
                <td>$<?php echo number_format($salario_integral_diario, 2); ?></td>
            </tr>
        </table>

        <p style="font-size: 11px; margin-bottom: 5px;">
            Indemnizaciones y demas conceptos que me corresponden por el tiempo de servicio, de acuerdo a la liquidación siguiente:
        </p>

        <!-- TABLA DESGLOSE -->
        <table class="table-desglose" style="border: none;">
            <tr style="border-top: 2px solid #000; border-bottom: 2px solid #000;">
                <th style="border: none; text-align: left;">Conceptos Ley (LOTTT)</th>
                <th style="border: none;" class="text-center">Días</th>
                <th style="border: none;" class="text-right">Salario Base</th>
                <th style="border: none;" class="text-right">Asignaciones ($)</th>
            </tr>
            <tr>
                <td style="border: none;">Garantía sobre prestaciones Sociales Según Art. 142</td>
                <td style="border: none;" class="text-center"><?php echo number_format($dias_prestaciones, 2); ?> Días</td>
                <td style="border: none;" class="text-right">$<?php echo number_format($salario_integral_diario, 2); ?></td>
                <td style="border: none;" class="text-right">$<?php echo number_format($monto_prestaciones, 2); ?></td>
            </tr>
            <tr>
                <td style="border: none;">Vacaciones Fraccionadas art. 196</td>
                <td style="border: none;" class="text-center"><?php echo number_format($dias_vac_frac, 2); ?> Días</td>
                <td style="border: none;" class="text-right">$<?php echo number_format($salario_diario_basico, 2); ?></td>
                <td style="border: none;" class="text-right">$<?php echo number_format($monto_vac_frac, 2); ?></td>
            </tr>
            <tr>
                <td style="border: none;">Bono Vacacional Fraccionado art. 196</td>
                <td style="border: none;" class="text-center"><?php echo number_format($dias_bvac_frac, 2); ?> Días</td>
                <td style="border: none;" class="text-right">$<?php echo number_format($salario_diario_basico, 2); ?></td>
                <td style="border: none;" class="text-right">$<?php echo number_format($monto_bvac_frac, 2); ?></td>
            </tr>
            <tr>
                <td style="border: none;">Utilidades fraccionadas art. 131</td>
                <td style="border: none;" class="text-center"><?php echo number_format($dias_uti_frac, 2); ?> Días</td>
                <td style="border: none;" class="text-right">$<?php echo number_format($salario_promedio, 2); ?></td>
                <td style="border: none;" class="text-right">$<?php echo number_format($monto_uti_frac, 2); ?></td>
            </tr>
            
            <?php if ($indemnizacion_despido > 0): ?>
            <tr>
                <td style="border: none; color:red;">Indemnización Despido Injustificado art. 92</td>
                <td style="border: none; color:red;" class="text-center">N/A</td>
                <td style="border: none; color:red;" class="text-right">N/A</td>
                <td style="border: none; color:red;" class="text-right">$<?php echo number_format($indemnizacion_despido, 2); ?></td>
            </tr>
            <?php endif; ?>

            <tr>
                <td colspan="3" style="border: none;" class="text-right"><i>sub total</i></td>
                <td style="border: none;" class="text-right"><b>$<?php echo number_format($total_asig, 2); ?></b></td>
            </tr>
            <tr>
                <td colspan="3" style="border: none; padding-top: 15px;" class="text-right"><b>TOTAL ASIGNACIONES</b></td>
                <td style="border: none; padding-top: 15px;" class="text-right"><u style="font-weight: bold;">$<?php echo number_format($total_asig, 2); ?></u></td>
            </tr>
            <tr>
                <td colspan="4" style="border: none; padding-top: 25px;"><b><u>DEDUCCIONES</u></b></td>
            </tr>
            <tr>
                <td colspan="2" style="border: none;">Préstamos / Vales pendientes</td>
                <td style="border: none;" class="text-right text-danger">$<?php echo number_format($deducciones, 2); ?></td>
                <td style="border: none;"></td>
            </tr>
            <tr>
                <td colspan="3" style="border: none;" class="text-right"><b>TOTAL DEDUCCIONES</b></td>
                <td style="border: none;" class="text-right text-danger"><u style="font-weight: bold;">$<?php echo number_format($deducciones, 2); ?></u></td>
            </tr>
            <tr>
                <td colspan="3" style="border: none; font-size: 15px;" class="text-right"><b>NETO A PAGAR ($)</b></td>
                <td style="border: none; font-size: 15px;" class="text-right"><u style="font-weight: bold;">$<?php echo number_format($total_neto, 2); ?></u></td>
            </tr>
        </table>

        <!-- DECLARACIÓN DE CONFORMIDAD -->
        <p style="margin-top: 30px; text-align: justify; line-height: 1.6;">
            He recibido de la Empresa <b>UNIFUERTE, C.A.</b>, la cantidad de <b><?php echo $monto_letras; ?> CON <?php echo $centavos; ?> CENTIMOS DÓLARES AMERICANOS ($<?php echo number_format($total_neto, 2); ?>)</b> 
            por concepto de la liquidación DEFINITIVA derivados de mi tiempo como <b><?php echo mb_strtoupper($prest['cargo'], 'UTF-8'); ?></b> 
            cantidad que recibo a mi entera satisfacción no quedando nada por reclamar por tales conceptos, ni por ningún otro motivo.
        </p>
        
        <p style="margin-top: 20px;">
            Puerto Ordaz, <?php echo $dia_impresion; ?> de <?php echo $mes_impresion; ?> de <?php echo $anio_impresion; ?>.
        </p>

        <!-- FIRMAS -->
        <div class="firmas">
            <div class="firma-box">
                <div class="linea-firma">ELABORADO POR:</div>
                <div style="font-size: 10px; margin-top: 5px;">Recursos Humanos</div>
            </div>
            <div class="firma-box">
                <div class="linea-firma">APROBADO POR:</div>
                <div style="font-size: 10px; margin-top: 5px;">Claritza Pinto (Presidente)</div>
            </div>
            <div class="firma-box">
                <div class="linea-firma">RECIBIDO CONFORME</div>
                <div style="font-size: 10px; margin-top: 5px;"><?php echo mb_strtoupper($prest['nombre'] . ' ' . $prest['apellido'], 'UTF-8'); ?><br>C.I: <?php echo $prest['cedula']; ?></div>
            </div>
        </div>

    </div>

</body>
</html>