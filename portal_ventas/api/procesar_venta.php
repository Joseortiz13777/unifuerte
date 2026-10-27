<?php
// Archivo: UNIFUERTE/portal_ventas/procesar_venta.php
header('Content-Type: application/json');

// Forzamos la zona horaria a Venezuela para registros exactos
date_default_timezone_set('America/Caracas');

require_once '../../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido']);
    exit;
}

try {
    // Iniciar transacción explícita
    $pdo->beginTransaction();

    // 1. Manejo y asignación del Cliente
    $idCliente = $_POST['id_cliente'] ?? '';
    
    if ($idCliente === 'NUEVO') {
        // Registrar el cliente dinámico al instante
        $stmtCli = $pdo->prepare("INSERT INTO cliente (nombre, cedula_Rif, direccion, comprador, email, tlf) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtCli->execute([
            $_POST['c_nombre'] ?? '',
            $_POST['c_rif'] ?? '',
            $_POST['c_direccion'] ?? '',
            $_POST['c_comprador'] ?? '',
            $_POST['c_email'] ?? '',
            $_POST['c_tlf'] ?? ''
        ]);
        $idCliente = $pdo->lastInsertId();
    }

    if (empty($idCliente)) {
        throw new Exception("Debe definir o seleccionar un cliente válido.");
    }

    // 2. Calcular montos financieros base de la orden
    $cantidades = $_POST['prenda_cantidad'] ?? [];
    $precios = $_POST['prenda_precio'] ?? [];
    
    $subTotal = 0;
    for ($i = 0; $i < count($cantidades); $i++) {
        $subTotal += (floatval($cantidades[$i]) * floatval($precios[$i]));
    }

    // Calcular recargo express si corresponde
    $esExpress = isset($_POST['es_express']) && $_POST['es_express'] === '1' ? 1 : 0;
    $flete = 0.00; 
    if ($esExpress) {
        $subTotal = $subTotal * 1.10; // Incremento del 10%
    }

    $iva = $subTotal * 0.16; // Tasa impositiva del 16% estándar
    $totalGeneral = $subTotal + $iva + $flete;

    // 3. Insertar registro maestro en orden_compra
    $fecha_registro_exacta = date('Y-m-d H:i:s'); // Hora exacta de Venezuela al darle clic
    $fecha_emision_form = !empty($_POST['fecha_emision']) ? $_POST['fecha_emision'] : date('Y-m-d');

    $stmtOrden = $pdo->prepare("INSERT INTO orden_compra (id_cliente, nro_orden_cotizacion, fecha_emision, fecha_entrega, es_express, id_usuario_vendedor, acepta_entregas_parciales, sub_total, Flete, iva, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Usamos temporalmente el ID de usuario del administrador por defecto
    $idVendedor = 2; 
    
    $stmtOrden->execute([
        $idCliente,
        $_POST['nro_orden_cotizacion'] ?? '',
        $fecha_emision_form, // Guardamos la fecha del formulario
        $_POST['fecha_entrega'] ?? '',
        $esExpress,
        $idVendedor,
        isset($_POST['acepta_entregas_parciales']) && $_POST['acepta_entregas_parciales'] === '1' ? 1 : 0,
        $subTotal,
        $flete,
        $iva,
        $totalGeneral
    ]);
    
    $idOrdenCompra = $pdo->lastInsertId();

    // 4. Mapear e insertar detalles de prendas (detalle_orden_compra)
    $prendasTipo = $_POST['prenda_tipo'] ?? [];
    $prendasMateria = $_POST['prenda_materia'] ?? [];
    $prendasColores = $_POST['prenda_colores'] ?? [];
    $prendasDiseno = $_POST['prenda_diseno'] ?? [];

    $ultimoIdDetallePrenda = null;

    $stmtDetalle = $pdo->prepare("INSERT INTO detalle_orden_compra (id_orden_compra, id_materia, tipo_prenda, talla, cantidad, precio_unitario, detalle_diseno, combinacion_colores) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    for ($i = 0; $i < count($prendasTipo); $i++) {
        if (!empty($prendasTipo[$i])) {
            $stmtDetalle->execute([
                $idOrdenCompra,
                !empty($prendasMateria[$i]) ? $prendasMateria[$i] : null,
                $prendasTipo[$i],
                'Surtido', 
                intval($cantidades[$i]),
                floatval($precios[$i]),
                $prendasDiseno[$i],
                $prendasColores[$i]
            ]);
            $ultimoIdDetallePrenda = $pdo->lastInsertId();
        }
    }

    // 5. Mapear e insertar dimensiones físicas del personal (medicion_personal)
    $pNombres = $_POST['p_nombre'] ?? [];
    $pGeneros = $_POST['p_genero'] ?? [];
    $pTallas = $_POST['p_talla'] ?? [];
    $pHombros = $_POST['p_hombro'] ?? [];
    $pBustos = $_POST['p_busto'] ?? [];
    $pCinturas = $_POST['p_cintura'] ?? [];
    $pCaderas = $_POST['p_cadera'] ?? [];
    $pLCamisas = $_POST['p_lcamisa'] ?? [];
    $pLBrazos = $_POST['p_lbrazo'] ?? [];
    $pLPantalones = $_POST['p_lpantalon'] ?? [];
    $pObservaciones = $_POST['p_obs'] ?? [];

    $stmtMedidas = $pdo->prepare("INSERT INTO medicion_personal (id_detalle_orden_compra, nombre, genero, talla_asignada, hombro, busto_torax, cintura, cadera, largo_camisa, largo_brazo, largo_pantalon, observacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    for ($j = 0; $j < count($pNombres); $j++) {
        if (!empty($pNombres[$j])) {
            $stmtMedidas->execute([
                $ultimoIdDetallePrenda, 
                $pNombres[$j],
                $pGeneros[$j],
                $pTallas[$j],
                !empty($pHombros[$j]) ? floatval($pHombros[$j]) : null,
                !empty($pBustos[$j]) ? floatval($pBustos[$j]) : null,
                !empty($pCinturas[$j]) ? floatval($pCinturas[$j]) : null,
                !empty($pCaderas[$j]) ? floatval($pCaderas[$j]) : null,
                !empty($pLCamisas[$j]) ? floatval($pLCamisas[$j]) : null,
                !empty($pLBrazos[$j]) ? floatval($pLBrazos[$j]) : null,
                !empty($pLPantalones[$j]) ? floatval($pLPantalones[$j]) : null,
                $pObservaciones[$j]
            ]);
        }
    }

    // Confirmar todas las operaciones si no se presentaron fallas
    $pdo->commit();
    echo json_encode(['status' => 'success', 'id_orden' => $idOrdenCompra]);

} catch (Exception $e) {
    // Revertir base de datos ante estados inesperados
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>