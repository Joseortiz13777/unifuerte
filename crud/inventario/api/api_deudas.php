<?php
session_start();
error_reporting(0);
// Ajusta la ruta a tu conexion
require_once '../../../conexion.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Sesiиоn expirada']); exit;
}

$id_proveedor = intval($_GET['id_proveedor'] ?? 0);

$deudas = [];
$total_deuda = 0;

// 1. Deudas por compras de TEXTILES / INSUMOS (Inventario)
$sql_compras = "SELECT ID as id_compra, fecha_compra, nro_factura, nro_control, referencia, costo, monto_pagado, estado_pago, moneda 
        FROM compra 
        WHERE id_provedor = $id_proveedor AND estado_pago != 'Pagado'
        ORDER BY fecha_compra ASC";

$q_compras = mysqli_query($conexion, $sql_compras);
if($q_compras) {
    while($row = mysqli_fetch_assoc($q_compras)) {
        $costo = floatval($row['costo']);
        $pagado = floatval($row['monto_pagado']);
        $pendiente = $costo - $pagado;
        
        if ($pendiente > 0) {
            $row['monto_pendiente'] = $pendiente;
            $row['tipo_deuda'] = 'Insumo'; // Identificador clave para el JS
            $deudas[] = $row;
            $total_deuda += $pendiente; 
        }
    }
}

// 2. Deudas por GASTOS DE SERVICIOS (Mantenimiento, Luz, Aires Acondicionados, Honorarios, etc.)
$sql_servicios = "SELECT id as id_compra, fecha as fecha_compra, factura as nro_factura, '' as nro_control, descripcion as referencia, monto_total as costo, monto_pagado, estado_pago, 'USD' as moneda 
                  FROM gastos_servicios 
                  WHERE id_proveedor = $id_proveedor AND estado_pago != 'Pagado'
                  ORDER BY fecha ASC";
$q_servicios = mysqli_query($conexion, $sql_servicios);
if($q_servicios) {
    while($row = mysqli_fetch_assoc($q_servicios)) {
        $costo = floatval($row['costo']);
        $pagado = floatval($row['monto_pagado']);
        $pendiente = $costo - $pagado;
        
        if ($pendiente > 0) {
            $row['monto_pendiente'] = $pendiente;
            $row['tipo_deuda'] = 'Servicio'; // Identificador clave para el JS
            $deudas[] = $row;
            $total_deuda += $pendiente; 
        }
    }
}

// 3. Ordenar todas las deudas juntas por fecha (las mивs antiguas primero)
usort($deudas, function($a, $b) {
    return strtotime($a['fecha_compra']) - strtotime($b['fecha_compra']);
});

echo json_encode([
    'success' => true,
    'total_deuda' => $total_deuda,
    'compras' => $deudas
]);
?>