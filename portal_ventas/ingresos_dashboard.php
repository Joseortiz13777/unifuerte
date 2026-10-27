<?php
function separarValoresIngresoDashboard($valor) {
    $valor = preg_replace('/(?:\x0B\s*)?v?ert\{\}(?:\s*\x0B)?/i', '|', (string)$valor);
    $valor = preg_replace('/<br\s*\/?>/i', '|', $valor);
    $valor = str_replace(["\r\n", "\r", "\n", "\x0B"], '|', $valor);
    return explode('|', $valor);
}

function normalizarFechaIngresoDashboard($fecha) {
    $fecha = trim((string)$fecha);

    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $fecha, $coincidencias)) {
        $anio = (int)$coincidencias[1];
        $mes = (int)$coincidencias[2];
        $dia = (int)$coincidencias[3];
    } elseif (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})/', $fecha, $coincidencias)) {
        $dia = (int)$coincidencias[1];
        $mes = (int)$coincidencias[2];
        $anio = (int)$coincidencias[3];
    } else {
        return null;
    }

    if (!checkdate($mes, $dia, $anio)) {
        return null;
    }

    return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
}

function normalizarFechaPagoCotizacionDashboard($fechaPago, $fechaEmision, $foto, $correlacionValida) {
    $fecha = normalizarFechaIngresoDashboard($fechaPago);
    $emision = normalizarFechaIngresoDashboard($fechaEmision);

    if ($fecha !== null && $emision !== null && $fecha < $emision && $correlacionValida
        && preg_match('/^pago_(\d{10})_/', (string)$foto, $coincidencias)) {
        $fechaComprobante = date('Y-m-d', (int)$coincidencias[1]);
        if ($fechaComprobante >= $emision) {
            return $fechaComprobante;
        }
    }

    return $fecha;
}

function obtenerPagosCotizacionesDashboard($conexion, $desde, $hasta, $idVendedor = 0) {
    $filtroVendedor = (int)$idVendedor > 0 ? ' AND c.id_vendedor = ' . (int)$idVendedor : '';
    $consulta = mysqli_query($conexion, "
        SELECT c.ID, c.nro_cotizacion, c.id_vendedor, c.estado_pago, c.fecha_emision,
               c.pago_fecha, c.pago_valor_usd, c.fotos_pagos, c.referencias_pago, c.pago_banco,
               cli.nombre AS cliente_nombre, u.nombre AS vendedor_nombre, u.apellido AS vendedor_apellido
        FROM cotizacion c
        LEFT JOIN cliente cli ON cli.ID = c.id_cliente
        LEFT JOIN usuario u ON u.ID = c.id_vendedor
        WHERE c.pago_fecha IS NOT NULL
          AND c.pago_valor_usd IS NOT NULL
          $filtroVendedor
    ");

    if (!$consulta) {
        return false;
    }

    $pagos = [];
    while ($cotizacion = mysqli_fetch_assoc($consulta)) {
        $fechas = separarValoresIngresoDashboard($cotizacion['pago_fecha']);
        $montos = separarValoresIngresoDashboard($cotizacion['pago_valor_usd']);
        $referencias = separarValoresIngresoDashboard($cotizacion['referencias_pago'] ?? '');
        $bancos = separarValoresIngresoDashboard($cotizacion['pago_banco'] ?? '');
        $fotos = array_values(array_filter(array_map('trim', explode(',', (string)($cotizacion['fotos_pagos'] ?? '')))));
        $cantidad = min(count($fechas), count($montos));
        $nombreVendedor = trim(($cotizacion['vendedor_nombre'] ?? '') . ' ' . ($cotizacion['vendedor_apellido'] ?? ''));

        for ($i = 0; $i < $cantidad; $i++) {
            $fechaPago = normalizarFechaPagoCotizacionDashboard(
                $fechas[$i],
                $cotizacion['fecha_emision'] ?? '',
                $fotos[$i] ?? '',
                count($fotos) === $cantidad
            );
            $montoTexto = preg_replace('/[^0-9.-]/', '', trim($montos[$i]));

            if ($fechaPago === null || $fechaPago < $desde || $fechaPago > $hasta || $montoTexto === '' || !is_numeric($montoTexto) || (float)$montoTexto <= 0) {
                continue;
            }

            $pagos[] = [
                'id_cotizacion' => (int)$cotizacion['ID'],
                'nro_cotizacion' => $cotizacion['nro_cotizacion'],
                'id_vendedor' => (int)$cotizacion['id_vendedor'],
                'vendedor_nombre' => $nombreVendedor !== '' ? $nombreVendedor : 'Desconocido',
                'cliente_nombre' => $cotizacion['cliente_nombre'] ?? 'Desconocido',
                'estado_pago' => $cotizacion['estado_pago'],
                'fecha_pago' => $fechaPago,
                'referencia' => trim($referencias[$i] ?? ''),
                'banco' => trim($bancos[$i] ?? ''),
                'monto' => (float)$montoTexto
            ];
        }
    }

    usort($pagos, function ($a, $b) {
        return [$b['fecha_pago'], $b['id_cotizacion']] <=> [$a['fecha_pago'], $a['id_cotizacion']];
    });

    return $pagos;
}
?>
