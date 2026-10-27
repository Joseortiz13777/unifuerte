<?php
session_start();
error_reporting(0);

// Conexión subiendo dos niveles
require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$id_lote = intval($_GET['id_lote'] ?? 0);

if ($id_lote === 0) {
    die("Número de lote inválido.");
}

// Buscar todos los pagos amarrados a este lote
$q = mysqli_query($conexion, "
    SELECT p.*, e.nombre, e.apellido, e.cedula, e.cargo, e.fecha_ingreso 
    FROM nomina_pagos p 
    INNER JOIN nomina_empleados e ON p.id_empleado = e.id 
    WHERE p.id_lote = $id_lote
    ORDER BY e.cargo ASC, e.nombre ASC
");

if (mysqli_num_rows($q) == 0) {
    die("No se encontraron recibos para este lote de nómina.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibos de Nómina - Lote #<?= $id_lote ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; background-color: #525659; margin: 0; padding: 20px; }

        @page {
            size: letter;
            margin: 0; /* Remueve los márgenes del navegador que causan el descuadre */
        }

        .page { 
            width: 21.59cm; /* Ancho exacto Carta */
            height: 27.94cm; /* Alto exacto Carta */
            background: #fff; 
            margin: 0 auto 20px auto; 
            padding: 1cm 1.5cm; 
            box-shadow: 0 0 10px rgba(0,0,0,0.5); 
            box-sizing: border-box; 
            page-break-after: always;
            page-break-inside: avoid;
        }
        
        .page:last-child { page-break-after: auto; }

        /* Fijar la altura del recibo para que no sobrepase media página */
        .recibo-container { 
            width: 100%; 
            height: 12.5cm; 
            margin-bottom: 0px; 
            padding-bottom: 5px; 
            box-sizing: border-box; 
            overflow: hidden; 
        }

        .linea-corte { border-top: 1px dashed #666; margin: 10px 0; position: relative; text-align: center; }
        .linea-corte::after { content: '✂ Copia Trabajador / Copia Empresa ✂'; background: #fff; padding: 0 10px; color: #666; font-size: 10px; position: relative; top: -7px; }

        .header-table { width: 100%; margin-bottom: 5px; border-collapse: collapse;}
        .header-table td { vertical-align: top; border: none; padding: 0; }
        .logo { max-width: 140px; max-height: 55px; object-fit: contain; }
        .empresa-info { text-align: center; font-size: 10px; line-height: 1.2; }
        .recibo-info { text-align: right; font-size: 11px; line-height: 1.3; }

        .info-table { width: 100%; font-size: 11px; margin-bottom: 5px; border-collapse: collapse;}
        .info-table td { border: none; padding: 2px 0; }
        .info-table strong { display: inline-block; width: 100px; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 10.5px; margin-bottom: 5px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 3px 5px; }
        .data-table th { text-align: center; font-weight: bold; background-color: #f2f2f2; }

        .col-conceptos { width: 40%; vertical-align: top; }
        .col-montos { width: 15%; vertical-align: top; text-align: right; }
        .col-deducciones { width: 30%; vertical-align: top; }

        .fila-totales { font-weight: bold; }
        .fila-totales td { padding: 4px; }
        .fila-totales-final { font-weight: bold; text-transform: uppercase; }

        .footer-table { width: 100%; margin-top: 5px; font-size: 11px; border-collapse: collapse;}
        .footer-table td { vertical-align: middle; border: none; padding: 0; }

        .firmas-table { width: 100%; margin-top: 15px; text-align: center; font-size: 11px; border-collapse: collapse;}
        .firmas-table td { border: none; vertical-align: bottom; }
        .linea-firma-container { position: relative; display: inline-block; width: 85%; padding-top: 3px; border-top: 1px solid #000; }

        .sello-img { 
            position: absolute; 
            bottom: 12px; 
            left: 50%; 
            transform: translateX(-50%); 
            max-height: 60px; /* Reducido levemente para que quepa bien en los 12.5cm */
            max-width: 150px; 
            object-fit: contain; 
            z-index: 0; 
            opacity: 0.9; 
        }
        .texto-firma { position: relative; z-index: 2; background-color: rgba(255,255,255,0.7); padding: 2px 5px; }

        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .page { box-shadow: none; margin: 0; padding: 1cm 1.5cm; width: 21.59cm; height: 27.94cm; }
            .no-print { display: none !important; }
        }
        .btn-print { position: fixed; bottom: 30px; right: 30px; background: #0B4C8C; color: #fff; border: none; padding: 15px 25px; border-radius: 50px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 1000; }
        .btn-print:hover { background: #FF7518; }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir Todos los Recibos</button>

    <?php 
    while ($pago = mysqli_fetch_assoc($q)): 

        $periodo = date('d/m/Y', strtotime($pago['fecha_inicio_periodo'])) . ' al ' . date('d/m/Y', strtotime($pago['fecha_fin_periodo']));
        $fecha_pago = date('d/m/Y', strtotime($pago['fecha_pago']));
        $fecha_ingreso = date('d/m/Y', strtotime($pago['fecha_ingreso']));
        $nro_control = date('Y-m', strtotime($pago['fecha_pago'])) . '/' . sprintf("%03d", $pago['id']);

        $nombre_completo = mb_strtoupper($pago['nombre'] . ' ' . $pago['apellido'], 'UTF-8');
        $cargo = mb_strtoupper($pago['cargo'], 'UTF-8');

        $c = $cargo;
        if (strpos($c, 'GERENTE') !== false || strpos($c, 'PRESIDENTE') !== false || strpos($c, 'ADMINISTRA') !== false) {
            $departamento = 'ADMINISTRACION';
        } elseif (strpos($c, 'DISEÑO') !== false || strpos($c, 'DISENO') !== false || strpos($c, 'SISTEMA') !== false) {
            $departamento = 'SISTEMAS / DISEÑO';
        } elseif (strpos($c, 'COSTURERA') !== false || strpos($c, 'CORTE') !== false || strpos($c, 'CORTADORA') !== false || strpos($c, 'AYUDANTE') !== false) {
            $departamento = 'PRODUCCIÓN / CONFECCION';
        } else {
            $departamento = 'OPERACIONES';
        }

        $tasa_bcv = floatval($pago['tasa_bcv']);

        $sueldo_bs = round(floatval($pago['sueldo_quincena']) * $tasa_bcv, 2);
        $cesta_bs = round(floatval($pago['cesta_ticket']) * $tasa_bcv, 2);
        $bono_bs = round(floatval($pago['bono_ayuda']) * $tasa_bcv, 2);

        $he_diurna_bs = round(floatval($pago['valor_he_diurna']) * $tasa_bcv, 2);
        $he_noct_bs = round(floatval($pago['valor_he_noct']) * $tasa_bcv, 2);
        $sabados_bs = round(floatval($pago['valor_sabados']) * $tasa_bcv, 2);
        $bono_prod_bs = round(floatval($pago['bono_prod']) * $tasa_bcv, 2);
        $pago_pend_bs = round(floatval($pago['pago_pendiente']) * $tasa_bcv, 2);

        $desc_dias_bs = round(floatval($pago['monto_descuento_dias']) * $tasa_bcv, 2);
        $desc_horas_bs = round(floatval($pago['monto_descuento_horas']) * $tasa_bcv, 2);
        $vales_bs = round(floatval($pago['vales']) * $tasa_bcv, 2);

        $qty_sabados = floatval($pago['qty_sabados']);
        $qty_he_diurna = floatval($pago['qty_he_diurna']);
        $qty_he_noct = floatval($pago['qty_he_noct']);
        $dias_f = floatval($pago['dias_falta']);
        $horas_f = floatval($pago['horas_falta']);
        $dias_trabajados = 15 - $dias_f; 

        $tot_asig_bs = round(floatval($pago['total_asignaciones']) * $tasa_bcv, 2);
        $tot_deduc_bs = round(floatval($pago['total_deducciones']) * $tasa_bcv, 2);
        $neto_bs = floatval($pago['pago_bs']); 
        $neto_usd = floatval($pago['total_pagado']);
    ?>

    <div class="page">

        <?php for($i=0; $i<2; $i++): ?>
        <div class="recibo-container">
            <table class="header-table">
                <tr>
                    <td style="width: 25%;">
                        <img src="../../img/unifuerte.jpg" class="logo" alt="Unifuerte">
                    </td>
                    <td style="width: 50%;" class="empresa-info">
                        <strong>UNIFUERTE, C.A</strong><br>
                        R.I.F: J-41282764-2 / R.U.C 1829-2021<br>
                        CR. UD-321, EDIFICIO PARCELA 321-16-10A-1<br>
                        PISO 1, OFICINA, A,B,C, ZONA INDUSTRIAL UD 321<br>
                        CIUDAD GUAYANA, PUERTO ORDAZ, ESTADO BOLIVAR<br>
                        CONTACTO: 04241546889 EMAIL: CLIENTEUNIFUERTE@GMAIL.COM
                    </td>
                    <td style="width: 25%;" class="recibo-info">
                        <strong>RECIBO DE PAGO DE NÓMINA</strong><br>
                        Periodo: <?= $periodo ?><br>
                        Fecha de Pago: <?= $fecha_pago ?><br>
                        Nro. De Control: <?= $nro_control ?>
                    </td>
                </tr>
            </table>

            <table class="info-table">
                <tr>
                    <td style="width: 50%;"><strong>Trabajador:</strong> <?= $nombre_completo ?></td>
                    <td style="width: 50%;"><strong>Fecha de Ingre:</strong> <?= $fecha_ingreso ?></td>
                </tr>
                <tr>
                    <td><strong>C.I:</strong> <?= $pago['cedula'] ?></td>
                    <td><strong>Departamento:</strong> <?= $departamento ?></td>
                </tr>
                <tr>
                    <td><strong>Cargo:</strong> <?= $cargo ?></td>
                    <td><strong>Jefe Inmediato:</strong> GERENTE GENERAL</td>
                </tr>
            </table>

            <table class="data-table">
                <thead>
                    <tr>
                        <th class="col-conceptos">CONCEPTOS</th>
                        <th class="col-montos">ASIGNACIONES<br>(Monto Bs.)</th>
                        <th class="col-deducciones">DEDUCCIONES</th>
                        <th class="col-montos">(Monto Bs)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="col-conceptos">
                            Salario básico 1ra. Quincena<br>
                            Días trabajados (<?= $dias_trabajados ?> días)<br>
                            Bono de Transporte y Estudio (Art. 105)<br>
                            Bono de Alimentación (Cestaticket BCV)<br>
                            Bonificaciones de Productividad<br>
                            Horas Extras Diurnas (<?= $qty_he_diurna ?> hrs)<br>
                            Horas Extras Nocturnas (<?= $qty_he_noct ?> hrs)<br>
                            Sábados Trabajados (<?= $qty_sabados ?> días)<br>
                            Pago Pendiente a Favor
                        </td>
                        <td class="col-montos">
                            <?= $sueldo_bs > 0 ? 'Bs ' . number_format($sueldo_bs, 2) : 'Bs -' ?><br>
                            <br>
                            <?= $bono_bs > 0 ? 'Bs ' . number_format($bono_bs, 2) : 'Bs -' ?><br>
                            <?= $cesta_bs > 0 ? 'Bs ' . number_format($cesta_bs, 2) : 'Bs -' ?><br>
                            <?= $bono_prod_bs > 0 ? 'Bs ' . number_format($bono_prod_bs, 2) : 'Bs -' ?><br>
                            <?= $he_diurna_bs > 0 ? 'Bs ' . number_format($he_diurna_bs, 2) : 'Bs -' ?><br>
                            <?= $he_noct_bs > 0 ? 'Bs ' . number_format($he_noct_bs, 2) : 'Bs -' ?><br>
                            <?= $sabados_bs > 0 ? 'Bs ' . number_format($sabados_bs, 2) : 'Bs -' ?><br>
                            <?= $pago_pend_bs > 0 ? 'Bs ' . number_format($pago_pend_bs, 2) : 'Bs -' ?>
                        </td>
                        <td class="col-deducciones">
                            S.O.S. (4%)<br>
                            Paro Forzoso (0.5%)<br>
                            F.A.O.V (1%)<br>
                            Retención I.S.L.R<br>
                            Préstamos Internos / Vales<br>
                            Horas de Ausencia (<?= $horas_f ?> hrs)<br>
                            Días Faltados sin justificativos (<?= $dias_f ?> días)
                        </td>
                        <td class="col-montos">
                            Bs -<br>
                            Bs -<br>
                            Bs -<br>
                            Bs -<br>
                            <?= $vales_bs > 0 ? 'Bs ' . number_format($vales_bs, 2) : 'Bs -' ?><br>
                            <?= $desc_horas_bs > 0 ? 'Bs ' . number_format($desc_horas_bs, 2) : 'Bs -' ?><br>
                            <?= $desc_dias_bs > 0 ? 'Bs ' . number_format($desc_dias_bs, 2) : 'Bs -' ?>
                        </td>
                    </tr>
                    <tr class="fila-totales">
                        <td>Total Asignaciones</td>
                        <td style="text-align: right;">Bs <?= number_format($tot_asig_bs, 2) ?></td>
                        <td>Total Deducciones</td>
                        <td style="text-align: right;">Bs <?= number_format($tot_deduc_bs, 2) ?></td>
                    </tr>
                    <tr class="fila-totales-final">
                        <td>TOTAL ASIGNACIONES</td>
                        <td style="text-align: right;">Bs <?= number_format($tot_asig_bs, 2) ?></td>
                        <td>TOTAL DEDUCCIONES</td>
                        <td style="text-align: right;">Bs <?= number_format($tot_deduc_bs, 2) ?></td>
                    </tr>
                </tbody>
            </table>

            <table class="footer-table">
                <tr>
                    <td style="width: 50%;">
                        <strong>MONTO NETO A COBRAR (Transferencia Bancaria)</strong><br>
                        "Banco Provincial UNIFUERTE, C.A" Cuenta Corriente ********3387<br><br>
                        <small style="color:#555;">(Equivalente a <strong>$<?= number_format($neto_usd, 2) ?></strong> - Tasa: Bs <?= number_format($tasa_bcv, 2) ?>)</small>
                    </td>
                    <td style="width: 50%; text-align: right; font-size: 14px; font-weight: bold; padding-right: 20px;">
                        Bs <?= number_format($neto_bs, 2) ?>
                    </td>
                </tr>
            </table>

            <table class="firmas-table">
                <tr>
                    <td style="width: 45%;">
                        <div class="linea-firma-container">
                            Firma del Trabajador &nbsp;&nbsp;&nbsp; C.I &nbsp;&nbsp;&nbsp; Fecha
                        </div>
                    </td>
                    <td style="width: 10%;"></td>
                    <td style="width: 45%;">
                        <div class="linea-firma-container">
                            <img src="../../envios/img/firma.jpeg" class="sello-img" alt="Sello Empresa" onerror="this.onerror=null; this.src='../../envios/img/firma.jpg'; if(this.width==0){ this.style.display='none'; }">
                            <span class="texto-firma">Firma y Sello de la Empresa</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <?php if($i == 0): ?>
            <div class="linea-corte"></div>
        <?php endif; ?>

        <?php endfor; ?>
    </div>

    <?php endwhile; ?>

</body>
</html>