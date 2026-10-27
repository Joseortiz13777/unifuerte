<?php
function validarTokenDocumentoPortal(mysqli $conexion, string $tipo, int $idDocumento): bool
{
    $token = (string)($_GET['portal_token'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $token) || $idDocumento < 1) {
        return false;
    }

    $hash = hash('sha256', $token);
    $stmt = mysqli_prepare(
        $conexion,
        "SELECT id_cliente FROM portal_cliente_document_tokens
         WHERE token_hash = ? AND tipo_documento = ? AND id_documento = ? AND vence_en > NOW()
         LIMIT 1"
    );
    if (!$stmt) {
        error_log('Portal documento: no se pudo preparar la validación del enlace.');
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ssi', $hash, $tipo, $idDocumento);
    if (!mysqli_stmt_execute($stmt)) {
        error_log('Portal documento: no se pudo validar el enlace. ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }
    $resultado = mysqli_stmt_get_result($stmt);
    $tokenRow = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($stmt);
    if (!$tokenRow) {
        return false;
    }
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store, private');

    $idCliente = (int)$tokenRow['id_cliente'];
    if ($tipo === 'orden') {
        $verificar = mysqli_prepare(
            $conexion,
            "SELECT ID FROM orden_fabrica WHERE ID = ? AND id_cliente = ? LIMIT 1"
        );
    } else {
        $verificar = mysqli_prepare(
            $conexion,
            "SELECT ID FROM cotizacion
             WHERE ID = ? AND id_cliente = ? AND pausado = 0
             LIMIT 1"
        );
    }
    if (!$verificar) {
        error_log('Portal documento: no se pudo preparar la comprobación de propiedad.');
        return false;
    }
    mysqli_stmt_bind_param($verificar, 'ii', $idDocumento, $idCliente);
    if (!mysqli_stmt_execute($verificar)) {
        error_log('Portal documento: no se pudo comprobar la propiedad. ' . mysqli_stmt_error($verificar));
        mysqli_stmt_close($verificar);
        return false;
    }
    $resultadoPropiedad = mysqli_stmt_get_result($verificar);
    $perteneceAlCliente = $resultadoPropiedad && mysqli_fetch_assoc($resultadoPropiedad);
    mysqli_stmt_close($verificar);
    if (!$perteneceAlCliente) {
        return false;
    }

    if ($tipo === 'factura') {
        $verificarFactura = mysqli_prepare(
            $conexion,
            "SELECT id_cotizacion FROM factura_fiscal_cotizacion WHERE id_cotizacion = ? LIMIT 1"
        );
        if (!$verificarFactura) {
            error_log('Portal documento: no se pudo preparar la validación de factura.');
            return false;
        }
        mysqli_stmt_bind_param($verificarFactura, 'i', $idDocumento);
        if (!mysqli_stmt_execute($verificarFactura)) {
            error_log('Portal documento: no se pudo validar la factura. ' . mysqli_stmt_error($verificarFactura));
            mysqli_stmt_close($verificarFactura);
            return false;
        }
        $resultadoFactura = mysqli_stmt_get_result($verificarFactura);
        $existeFactura = $resultadoFactura && mysqli_fetch_assoc($resultadoFactura);
        mysqli_stmt_close($verificarFactura);
        return (bool)$existeFactura;
    }

    return true;
}
