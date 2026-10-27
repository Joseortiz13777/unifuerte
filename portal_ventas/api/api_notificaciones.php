<?php
session_start();
require_once '../../conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false]); exit;
}

$id_u = intval($_SESSION['usuario_id']);
$q_usr = mysqli_query($conexion, "SELECT rol, permisos FROM usuario WHERE ID = $id_u");
$datos_usr = mysqli_fetch_assoc($q_usr);
$permisos_arr = explode(',', $datos_usr['permisos'] ?? '');
$rol = strtolower($datos_usr['rol']);

$departamento_filtro = $_GET['departamento'] ?? 'todos'; // Recibimos el filtro del dropdown
$data = [];

// =========================================================================
// PARTE 1: ALERTAS INTELIGENTES DE RE-UNIFORME (Solo si tiene Vector 22)
// =========================================================================
// (No cambian, porque son alertas del sistema, no de auditoría)
if (in_array('22', $permisos_arr)) {
    $filtro_mis_clientes = "";
    if ($rol !== 'master' && $rol !== 'admin' && $rol !== 'jefe_ventas' && !in_array('7', $permisos_arr)) {
        $filtro_mis_clientes = " AND c.id_vendedor = $id_u ";
    }

    $sql_reuniforme = "
        SELECT cli.nombre AS cliente_nombre, cli.veces_uniforma, MAX(c.fecha_emision) as ultima_cotizacion 
        FROM cliente cli JOIN cotizacion c ON cli.ID = c.id_cliente WHERE cli.veces_uniforma > 0 $filtro_mis_clientes GROUP BY cli.ID
    ";

    $query_re = mysqli_query($conexion, $sql_reuniforme);
    $fecha_actual = new DateTime();

    if ($query_re) {
        while ($cliente = mysqli_fetch_assoc($query_re)) {
            $veces = intval($cliente['veces_uniforma']);
            if ($veces == 0) continue; 
            $meses_intervalo = floor(12 / $veces); 
            $ultima_fecha = new DateTime($cliente['ultima_cotizacion']);
            $fecha_proximo_uniforme = clone $ultima_fecha;
            $fecha_proximo_uniforme->modify("+$meses_intervalo months");

            $diferencia_dias = $fecha_actual->diff($fecha_proximo_uniforme)->format("%r%a");

            if ($diferencia_dias <= 15) {
                $estado = ($diferencia_dias < 0) ? "Vencido hace " . abs($diferencia_dias) . " días" : "Faltan $diferencia_dias días";
                $frecuencia_txt = ($veces == 1) ? "Anual" : (($veces == 2) ? "Semestral" : (($veces == 3) ? "Cuatrimestral" : "Trimestral"));

                $data[] = [
                    'accion' => 'ESTADO_PAGO', // Color naranja
                    'tabla_afectada' => 'ALERTA COMERCIAL',
                    'detalle_cambio' => "El cliente <b>{$cliente['cliente_nombre']}</b> está próximo a renovar dotación ($frecuencia_txt). $estado.",
                    'fecha_format' => "Meta: " . $fecha_proximo_uniforme->format('d/m/Y'),
                    'nombre' => 'Sistema', 'apellido' => 'CRM', 'rol' => 'Ventas'
                ];
            }
        }
    }
}

// =========================================================================
// PARTE 2: NOTIFICACIONES DE AUDITORÍA NORMALES (Depende de los Vectores 16, 23, 24, 25)
// =========================================================================

// Determinar qué roles PUEDE ver el usuario conectado (Seguridad Base)
$roles_permitidos = [];
if ($rol === 'master' || in_array('16', $permisos_arr)) {
    $roles_permitidos = ['all']; // Vector 16 lo ve TODO
} else {
    // Si no tiene el 16, construimos su matriz de permisos
    if (in_array('23', $permisos_arr)) $roles_permitidos[] = "'ventas'";
    if (in_array('24', $permisos_arr)) { $roles_permitidos[] = "'licitaciones'"; $roles_permitidos[] = "'supervisor_licitaciones'"; }
    if (in_array('25', $permisos_arr)) { $roles_permitidos[] = "'logistica'"; $roles_permitidos[] = "'admin'"; $roles_permitidos[] = "'jefe_ventas'"; }
}

$filtro_sql = " u.rol != 'master' "; // Nadie espía al master

// Si no lo ve todo, filtramos lo permitido
if (!in_array('all', $roles_permitidos) && count($roles_permitidos) > 0) {
    $filtro_sql .= " AND u.rol IN (" . implode(',', $roles_permitidos) . ") ";
} elseif (!in_array('all', $roles_permitidos) && count($roles_permitidos) == 0) {
    $filtro_sql .= " AND 1=0 "; // No tiene permisos de ver a nadie, query en blanco
}

// Ahora aplicamos el FILTRO DEL DROPDOWN del usuario (Si eligió ver solo uno)
if ($departamento_filtro === 'ventas') {
    $filtro_sql .= " AND u.rol = 'ventas' ";
} elseif ($departamento_filtro === 'licitaciones') {
    $filtro_sql .= " AND u.rol IN ('licitaciones', 'supervisor_licitaciones') ";
} elseif ($departamento_filtro === 'logistica') {
    $filtro_sql .= " AND u.rol = 'logistica' ";
} elseif ($departamento_filtro === 'admin') {
    $filtro_sql .= " AND u.rol IN ('admin', 'jefe_ventas') ";
}

$sql = "SELECT h.accion, h.tabla_afectada, h.detalle_cambio, h.fecha_hora, u.nombre, u.apellido, u.rol 
        FROM historial_actividad h JOIN usuario u ON h.id_usuario = u.ID 
        WHERE $filtro_sql
        ORDER BY h.fecha_hora DESC LIMIT 10";

$query = mysqli_query($conexion, $sql);
if($query){
    while($row = mysqli_fetch_assoc($query)){
        $row['fecha_format'] = date('d/m/Y H:i', strtotime($row['fecha_hora']));
        $data[] = $row;
    }
}

echo json_encode(['success' => true, 'data' => $data]);
?>