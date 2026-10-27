<?php
mysqli_report(MYSQLI_REPORT_OFF);

function validar_codigo_cotizacion($codigo) {
    if (!preg_match('/^#UNI-\d{4}-(\d{3,})$/D', $codigo, $coincidencias)) {
        return false;
    }

    $numero = (int)$coincidencias[1];
    return $numero > 0
        && $coincidencias[1] === str_pad((string)$numero, 3, '0', STR_PAD_LEFT);
}

function reservar_codigo_cotizacion(mysqli $conexion, $codigoManual = null) {
    $anio = date('Y');
    $nombreBloqueo = 'cotizacion_nro_' . $anio;
    $nombreBloqueoSql = mysqli_real_escape_string($conexion, $nombreBloqueo);
    $qBloqueo = mysqli_query($conexion, "SELECT GET_LOCK('$nombreBloqueoSql', 10) AS bloqueado");

    if (!$qBloqueo) {
        return ['success' => false, 'msg' => 'No se pudo bloquear la secuencia de cotizaciones: ' . mysqli_error($conexion)];
    }

    $bloqueo = mysqli_fetch_assoc($qBloqueo);
    if (intval($bloqueo['bloqueado'] ?? 0) !== 1) {
        return ['success' => false, 'msg' => 'No se pudo reservar un número de cotización. Intente nuevamente.'];
    }

    if ($codigoManual !== null) {
        if (!validar_codigo_cotizacion($codigoManual)) {
            liberar_bloqueo_codigo_cotizacion($conexion, $nombreBloqueo);
            return ['success' => false, 'msg' => 'El número debe tener el formato #UNI-AAAA-001, por ejemplo #UNI-2026-202.'];
        }

        $codigoSql = mysqli_real_escape_string($conexion, $codigoManual);
        $qExistente = mysqli_query($conexion, "SELECT ID FROM cotizacion WHERE nro_cotizacion='$codigoSql' LIMIT 1");
        if (!$qExistente) {
            $error = mysqli_error($conexion);
            liberar_bloqueo_codigo_cotizacion($conexion, $nombreBloqueo);
            return ['success' => false, 'msg' => 'No se pudo validar el número de cotización: ' . $error];
        }
        if (mysqli_num_rows($qExistente) > 0) {
            liberar_bloqueo_codigo_cotizacion($conexion, $nombreBloqueo);
            return ['success' => false, 'msg' => 'Ese número de cotización ya está registrado en el sistema.'];
        }

        return [
            'success' => true,
            'codigo' => $codigoManual,
            'bloqueo' => $nombreBloqueo
        ];
    }

    $prefijo = '#UNI-' . $anio . '-';
    $prefijoSql = mysqli_real_escape_string($conexion, $prefijo);
    $qCodigos = mysqli_query($conexion, "SELECT nro_cotizacion FROM cotizacion WHERE nro_cotizacion LIKE '{$prefijoSql}%'");
    if (!$qCodigos) {
        $error = mysqli_error($conexion);
        liberar_bloqueo_codigo_cotizacion($conexion, $nombreBloqueo);
        return ['success' => false, 'msg' => 'No se pudo consultar la secuencia de cotizaciones: ' . $error];
    }

    $maximo = 0;
    while ($fila = mysqli_fetch_assoc($qCodigos)) {
        $codigo = $fila['nro_cotizacion'];
        if (validar_codigo_cotizacion($codigo) && strpos($codigo, $prefijo) === 0) {
            $numero = (int)substr($codigo, strlen($prefijo));
            if ($numero > $maximo) {
                $maximo = $numero;
            }
        }
    }

    return [
        'success' => true,
        'codigo' => $prefijo . str_pad((string)($maximo + 1), 3, '0', STR_PAD_LEFT),
        'bloqueo' => $nombreBloqueo
    ];
}

function liberar_bloqueo_codigo_cotizacion(mysqli $conexion, $nombreBloqueo) {
    $nombreBloqueoSql = mysqli_real_escape_string($conexion, $nombreBloqueo);
    $qLiberar = mysqli_query($conexion, "SELECT RELEASE_LOCK('$nombreBloqueoSql') AS liberado");
    if (!$qLiberar || intval(mysqli_fetch_assoc($qLiberar)['liberado'] ?? 0) !== 1) {
        error_log('No se pudo liberar el bloqueo de secuencia de cotizaciones: ' . mysqli_error($conexion));
        return false;
    }
    return true;
}
?>