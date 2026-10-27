<?php
session_start();
require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit('Acceso denegado.');
}

function h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$desde = trim((string)($_GET['desde'] ?? ''));
$hasta = trim((string)($_GET['hasta'] ?? ''));
$idEmpleado = filter_var($_GET['id_empleado'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$fechaValida = static function (string $fecha): bool {
    $date = DateTime::createFromFormat('!Y-m-d', $fecha);
    return $date instanceof DateTime && $date->format('Y-m-d') === $fecha;
};

if (!$fechaValida($desde) || !$fechaValida($hasta) || $desde > $hasta || $idEmpleado === false) {
    http_response_code(400);
    exit('Selecciona un rango de fechas válido.');
}
$dias = (int)((strtotime($hasta) - strtotime($desde)) / 86400);
if ($dias > 180) {
    http_response_code(400);
    exit('El reporte de asistencia no puede superar 181 días.');
}

$filtroEmpleado = (int)$idEmpleado > 0 ? ' AND e.id = ' . (int)$idEmpleado : '';
$sql = "SELECT e.id, e.nombre, e.apellido, e.cedula, e.cargo, e.horario,
               a.fecha, a.hora_entrada, a.hora_salida, a.estado,
               a.minutos_retardo, a.minutos_extra, a.es_feriado, a.pago_doble
        FROM nomina_empleados e
        LEFT JOIN asistencia a ON a.id_empleado = e.id AND a.fecha BETWEEN ?
            AND ?
        WHERE (e.estado = 'Activo' OR e.id = " . (int)$idEmpleado . ")
            $filtroEmpleado
        ORDER BY e.nombre, e.apellido, a.fecha";
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    error_log('Asistencia: no se pudo preparar la hoja de impresión: ' . mysqli_error($conexion));
    http_response_code(500);
    exit('No se pudo preparar el reporte de asistencia.');
}
mysqli_stmt_bind_param($stmt, 'ss', $desde, $hasta);
if (!mysqli_stmt_execute($stmt)) {
    error_log('Asistencia: no se pudo ejecutar la hoja de impresión: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    http_response_code(500);
    exit('No se pudo cargar el reporte de asistencia.');
}
$result = mysqli_stmt_get_result($stmt);
$empleados = [];
while ($row = mysqli_fetch_assoc($result)) {
    $id = (int)$row['id'];
    if (!isset($empleados[$id])) {
        $empleados[$id] = [
            'nombre' => trim((string)$row['nombre'] . ' ' . (string)$row['apellido']),
            'cedula' => (string)($row['cedula'] ?? ''),
            'cargo' => (string)($row['cargo'] ?? ''),
            'horario' => (int)($row['horario'] ?? 1),
            'dias' => []
        ];
    }
    if (!empty($row['fecha'])) {
        $empleados[$id]['dias'][$row['fecha']] = $row;
    }
}
mysqli_stmt_close($stmt);

$diasRango = [];
$cursor = new DateTimeImmutable($desde);
$ultimoDia = new DateTimeImmutable($hasta);
for ($fecha = $cursor; $fecha <= $ultimoDia; $fecha = $fecha->modify('+1 day')) {
    $diasRango[] = $fecha->format('Y-m-d');
}
$horarios = [1 => 'H1 · 7:00 a. m. – 4:00 p. m.', 2 => 'H2 · 8:00 a. m. – 12:00 p. m.', 3 => 'H3 · 7:00 a. m. – 2:00 p. m.'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hoja de asistencia · <?= h($desde) ?> a <?= h($hasta) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; color: #15202b; font-family: Arial, sans-serif; font-size: 12px; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 20px; border-bottom: 2px solid #0b4c8c; padding-bottom: 12px; margin-bottom: 18px; }
        header img { max-width: 150px; max-height: 65px; object-fit: contain; }
        h1 { color: #0b4c8c; margin: 0 0 6px; font-size: 22px; }
        .periodo { font-weight: bold; }
        .empleado { margin: 16px 0 7px; padding: 8px 10px; background: #eef5fc; border-left: 4px solid #0b4c8c; }
        .empleado strong { font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #74808c; padding: 6px; text-align: center; }
        th { background: #e8edf2; }
        td:first-child, th:first-child { text-align: left; }
        .sin-registro { color: #657383; font-style: italic; }
        .estado-falta { color: #a61b1b; font-weight: bold; }
        .no-print { position: fixed; right: 22px; bottom: 22px; border: 0; border-radius: 24px; padding: 12px 20px; background: #0b4c8c; color: #fff; font-weight: bold; cursor: pointer; }
        @media print {
            @page { size: letter portrait; margin: 12mm; }
            body { padding: 0; font-size: 10px; }
            header { break-inside: avoid; }
            .empleado { break-after: avoid; }
            tr { break-inside: avoid; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimir hoja</button>
    <header>
        <img src="../../img/unifuerte.jpg" alt="Unifuerte">
        <div>
            <h1>HOJA DE ASISTENCIA</h1>
            <div class="periodo">Período: <?= h(date('d/m/Y', strtotime($desde))) ?> — <?= h(date('d/m/Y', strtotime($hasta))) ?></div>
        </div>
    </header>
    <?php if (!$empleados): ?>
        <p>No hay empleados disponibles para el reporte seleccionado.</p>
    <?php endif; ?>
    <?php foreach ($empleados as $empleado): ?>
        <section>
            <div class="empleado">
                <strong><?= h($empleado['nombre']) ?></strong>
                &nbsp; · C.I.: <?= h($empleado['cedula'] ?: '—') ?>
                &nbsp; · Cargo: <?= h($empleado['cargo'] ?: '—') ?>
                &nbsp; · <?= h($horarios[$empleado['horario']] ?? 'Horario no definido') ?>
            </div>
            <table>
                <thead>
                    <tr><th>Fecha</th><th>Día</th><th>Estado</th><th>Entrada</th><th>Salida</th><th>Retardo</th><th>Horas extra</th></tr>
                </thead>
                <tbody>
                <?php foreach ($diasRango as $fecha):
                    $registro = $empleado['dias'][$fecha] ?? null;
                    $dia = date('N', strtotime($fecha));
                    $nombresDias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
                    $estado = (string)($registro['estado'] ?? '');
                    $claseEstado = in_array($estado, ['Falta', 'Falta Feriado'], true) ? 'estado-falta' : '';
                    ?>
                    <tr>
                        <td><?= h(date('d/m/Y', strtotime($fecha))) ?></td>
                        <td><?= h($nombresDias[$dia]) ?></td>
                        <td class="<?= $claseEstado ?>"><?= $registro ? h($estado) : '<span class="sin-registro">Sin registro</span>' ?><?= !empty($registro['es_feriado']) ? ' · Feriado' : '' ?></td>
                        <td><?= $registro && $registro['hora_entrada'] ? h(date('h:i A', strtotime((string)$registro['hora_entrada']))) : '—' ?></td>
                        <td><?= $registro && $registro['hora_salida'] ? h(date('h:i A', strtotime((string)$registro['hora_salida']))) : '—' ?></td>
                        <td><?= $registro ? (int)$registro['minutos_retardo'] . ' min' : '—' ?></td>
                        <td><?= $registro ? number_format((int)$registro['minutos_extra'] / 60, 2, ',', '.') . ' h' : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endforeach; ?>
</body>
</html>
