<?php
session_start();
error_reporting(0);

// Conexión subiendo dos niveles (Registro -> Nomina -> raíz)
require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$id_empleado = intval($_GET['id'] ?? 0);

$query = mysqli_query($conexion, "SELECT * FROM nomina_empleados WHERE id = $id_empleado");
if (mysqli_num_rows($query) == 0) {
    die("Empleado no encontrado.");
}
$emp = mysqli_fetch_assoc($query);

// ==========================================
// FUNCIONES DE APOYO PARA FECHAS Y NÚMEROS
// ==========================================
$meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

$fecha_ingreso = strtotime($emp['fecha_ingreso']);
$dia_ingreso = date('d', $fecha_ingreso);
$mes_ingreso = $meses[date('n', $fecha_ingreso) - 1];
$anio_ingreso = date('Y', $fecha_ingreso);

$dia_actual = date('d');
$mes_actual = $meses[date('n') - 1];
$anio_actual = date('Y');

// Función para convertir números a letras (para la cláusula quinta)
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

$salario_letras = numeroALetras($emp['salario_base']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato - <?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13.5px;
            line-height: 1.5;
            color: #000;
            background-color: #525659;
            margin: 0;
            padding: 20px;
        }
        .hoja {
            background-color: #fff;
            width: 21.5cm;
            margin: 0 auto 20px auto;
            padding: 2.5cm 2.5cm;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            box-sizing: border-box;
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 220px; /* Tamaño ajustado para el logo unifuerte.jpg */
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
            margin-top: 30px;
            margin-bottom: 25px;
        }
        p {
            text-align: justify;
            margin-bottom: 15px;
        }
        .firmas {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }
        .firma-box {
            text-align: center;
            width: 40%;
        }
        .linea-firma {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 12px;
        }
        .salto-pagina {
            page-break-before: always;
        }
        /* Ocultar botones e interfaz gris al imprimir */
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

    <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir Contrato</button>

    <!-- PÁGINA 1 -->
    <div class="hoja">
        
        <div class="header">
            <!-- Ruta corregida al logo exacto que mostraste en la imagen -->
            <img src="../../img/unifuerte.jpg" class="logo" alt="Logo Unifuerte">
            <div class="info-header">
                CR. UD-321, EDIFICIO PARCELA 321 -10, PISO 1, OFICINA A,B,C<br>
                ZONA INDUSTRIAL UD-321 CIUDAD GUAYANA PUERTO ORDAZ ESTADO<br>
                BOLÍVAR. CONTACTO: 0424-1546889<br>
                EMAIL: CLIENTEUNIFUERTE@GMAIL.COM
            </div>
        </div>

        <div class="titulo"><b><u>CONTRATO TIEMPO INDETERMINADO</u></b></div>

        <p>
            Entre: <strong>UNIFUERTE, C.A</strong> empresa inscrita ante el Registro Mercantil 303-56296 de la Circunscripción Judicial del Estado Bolívar, bajo el N#. 14, Tomo 25-A, Segundo Trimestre de Fecha 28-06-2019, representada en este acto por <strong>CLARITZA ARGELIA PINTO PETIT</strong>, venezolana, mayor de edad, de estado civil SOLTERA, titular de la cédula de identidad V- <strong>14.986.366</strong>, domiciliada en <strong>PUERTO ORDAZ – ESTADO BOLÍVAR</strong> y hábil, para ésta que a los efectos del presente contrato en lo adelante se denominará <strong>LA EMPRESA</strong> por una parte, y por otra, a la Ciudadana <strong><?php echo mb_strtoupper($emp['nombre'] . ' ' . $emp['apellido'], 'UTF-8'); ?></strong> , venezolana, mayor de edad, de estado civil soltera, titular de la cédula de identidad V- <strong><?php echo htmlspecialchars($emp['cedula']); ?></strong>, domiciliada en <strong>Ciudad Guayana, Puerto Ordaz – Estado Bolívar</strong> y civilmente hábil, quien en lo adelante se denominará <strong>EL TRABAJADOR</strong> han convenido celebrar el presente contrato por tiempo indeterminado, el cual estará sometido a las siguientes clausulas:
        </p>

        <p>
            <u>PRIMERA CLÁUSULA: FORMA Y DURACIÓN DEL CONTRATO:</u> "LA ENTIDAD DE TRABAJO" celebra con "EL TRABAJADOR", el presente contrato a tiempo Indeterminado de conformidad con lo establecido en los artículos 59 y 61 de la Ley Orgánica del Trabajo de los trabajadores y trabajadoras (LOTTT). Publicada en Gaceta Oficial ext. 6.076 del 07 de mayo del 2012.
        </p>

        <p>
            <u>SEGUNDA CLÁUSULA: RESPONSABILIDAD:</u> Al asumir el cargo de <strong><?php echo mb_strtoupper($emp['cargo'] ?: 'TRABAJADOR', 'UTF-8'); ?></strong> se hace responsable de cumplir con las normas, políticas de recursos humanos y políticas generales de la <strong>ENTIDAD DE TRABAJO</strong>, así como también cumplir con las actividades y tareas asignadas por su jefe inmediato bajo ética y responsabilidad.
        </p>

        <p>
            <u>TERCERA CLÁUSULA : DESCRIPCIÓN DE LOS SERVICIOS A PRESTAR Y OBLIGACIONES DEL TRABAJADOR:</u> Se compromete a prestar servicios profesionales con exclusividad a la "ENTIDAD DE TRABAJO" con el cargo de "<strong><?php echo mb_strtoupper($emp['cargo'] ?: 'TRABAJADOR', 'UTF-8'); ?></strong>" teniendo entre sus funciones tales como: apoyar y asistir a la costurera tipo I siendo guiada bajo su tutela, ensamblar partes de las prendas antes de las costura final o hacer la función de ojal (color de botones en los uniformes, preparar las telas según las especificaciones del diseño), apoyo en las reparaciones y dar ajustes de menores en las prendas que sean necesarias, asegurarse que las máquinas de coser están en buen estado y reportar cualquier problema y cualquier otra labor inherente a su labores así como cualquier otra actividad temporal que se requiera.
        </p>
    </div>

    <!-- PÁGINA 2 (Forzada mediante CSS para impresión perfecta) -->
    <div class="hoja salto-pagina">
        
        <!-- El membrete se repite en la segunda hoja tal como se ve en tu foto 2 -->
        <div class="header">
            <img src="../../img/unifuerte.jpg" class="logo" alt="Logo Unifuerte">
            <div class="info-header">
                CR. UD-321, EDIFICIO PARCELA 321 -10, PISO 1, OFICINA A,B,C<br>
                ZONA INDUSTRIAL UD-321 CIUDAD GUAYANA PUERTO ORDAZ ESTADO<br>
                BOLÍVAR. CONTACTO: 0424-1546889<br>
                EMAIL: CLIENTEUNIFUERTE@GMAIL.COM
            </div>
        </div>

        <p>
            <u>CUARTA CLÁUSULA: JORNADA Y LUGAR DE TRABAJO:</u> La trabajadora <strong>SE COMPROMETE A PRESTAR A LA EMPRESA</strong> los servicios descritos en la Cláusula Primera del presente contrato, en la siguiente dirección: <strong>CARRERA UD-321, EDIFICIO PARCELA 321-16-10A, PISO A,B,C DE ZONA INDUSTRIAL 321</strong>, en el horario establecido que es el siguiente: Lunes a jueves de 7:00am a 4:00pm con una hora de descanso de 12:00pm a 1:00pm y Viernes de 7:00am a 3:00pm con una hora de descanso de 12:00pm a 1:00pm. La Empresa no labora los días <strong>SÁBADO Y DOMINGO</strong>.
        </p>

        <p>
            <u>QUINTA CLÁUSULA: LA EMPRESA</u> se obliga a pagar a "EL TRABAJADOR" un salario mensual equivalente a <strong><?php echo $salario_letras; ?> DOLARES AMERICANOS (USD $<?php echo number_format($emp['salario_base'], 2); ?>)</strong>, pagados en moneda de curso legal a la tasa de cambio oficial del Banco Central De Venezuela, vigente para la fecha de pago (quincenal, los días 15 y 30 de cada mes), mediante transferencia Bancaria.
        </p>

        <p>
            Así mismo se reconoce a "EL TRABAJADOR" un beneficio de Cesta Ticket mensual equivalente a <strong>USD $<?php echo number_format($emp['cesta_ticket'], 2); ?></strong>, y un bono no remunerativo mensual equivalente a <strong>USD $<?php echo number_format($emp['bonos'], 2); ?></strong> por concepto de ayuda de traslado y capacitación, el cual no genera incidencia salarial, conforme a la Jurisprudencia N# 099 de la Sala de casación Social del TSJ de fecha 31/03/2025 y (de acuerdo al Art. 105 de la LOTTT), pagados en moneda de curso legal a la tasa de cambio oficial del Banco Central De Venezuela vigente para la fecha de pago (quincenal, los días 15 y 30 de cada mes), mediante transferencia Bancaria. Todos los beneficios derivados de la relación laboral (vacaciones, utilidades, prestaciones sociales y demás conceptos previstos en la LOTTT) se calculan tomando como base el salario pactado en dólares pagados en moneda de curso legal a la tasa de cambio oficial del Banco Central De Venezuela vigente para la fecha de pago de su liquidación.
        </p>

        <p>
            <u>SEXTA CLÁUSULA:</u> "EL TRABAJADOR" se obliga a resguardar la mercancía, y las maquinarias que <strong>LA EMPRESA UNIFUERTE, C.A</strong> le suministra un kit de herramientas para la realización de los productos textiles, por lo que "EL TRABAJADOR" se obliga a evitar su deterioro, extravío, perdida o hurto.
        </p>

        <p>
            <u>SÉPTIMA CLÁUSULA:</u> Se hacen dos (2) ejemplares a un tono tenor y aun mismo efecto, uno para "EL TRABAJADOR" y uno para <strong>LA EMPRESA</strong> documento que manifiesta que recibió el día <strong><?php echo $dia_ingreso; ?></strong> del mes de <strong><?php echo $mes_ingreso; ?></strong> del <strong><?php echo $anio_ingreso; ?></strong> a las 8:00am de la mañana.
        </p>

        <p>
            <u>OCTAVA CLÁUSULA:</u> Ambas partes eligen al Estado Bolívar, como sede para las aplicaciones y ejecución del presente contrato por tiempo indeterminado. En Puerto Ordaz, Estado Bolívar, <strong><?php echo $dia_actual; ?></strong> días del mes de <strong><?php echo $mes_actual; ?></strong> del año <strong><?php echo $anio_actual; ?></strong>.
        </p>

        <div class="firmas">
            <div class="firma-box">
                <div class="linea-firma">POR LA EMPRESA</div>
            </div>
            <div class="firma-box">
                <div class="linea-firma">EL TRABAJADOR</div>
            </div>
        </div>

    </div>

</body>
</html>