<?php

function asegurarTablaGarantiasTextiles(mysqli $conexion): bool
{
    $sql = "CREATE TABLE IF NOT EXISTS garantias_textiles (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_orden INT NOT NULL,
        id_cotizacion INT NOT NULL,
        id_cliente INT NOT NULL,
        motivo TEXT NOT NULL,
        evidencias_json LONGTEXT NOT NULL,
        estado VARCHAR(30) NOT NULL DEFAULT 'Recibida',
        aprobacion_estado VARCHAR(20) NOT NULL DEFAULT 'Aprobada',
        nota_aprobacion TEXT DEFAULT NULL,
        id_usuario_aprueba INT DEFAULT NULL,
        aprobado_en DATETIME DEFAULT NULL,
        historial_json LONGTEXT DEFAULT NULL,
        creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        finalizado_en DATETIME DEFAULT NULL,
        id_usuario_actualiza INT DEFAULT NULL,
        KEY idx_garantias_orden_estado (id_orden, estado),
        KEY idx_garantias_cotizacion (id_cotizacion),
        KEY idx_garantias_cliente (id_cliente, creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!mysqli_query($conexion, $sql)) {
        error_log('No se pudo preparar la tabla de garant¨ªas textiles: ' . mysqli_error($conexion));
        return false;
    }

    foreach ([
        "ALTER TABLE garantias_textiles ADD COLUMN IF NOT EXISTS aprobacion_estado VARCHAR(20) NOT NULL DEFAULT 'Aprobada'",
        "ALTER TABLE garantias_textiles ADD COLUMN IF NOT EXISTS nota_aprobacion TEXT DEFAULT NULL",
        "ALTER TABLE garantias_textiles ADD COLUMN IF NOT EXISTS id_usuario_aprueba INT DEFAULT NULL",
        "ALTER TABLE garantias_textiles ADD COLUMN IF NOT EXISTS aprobado_en DATETIME DEFAULT NULL"
    ] as $migration) {
        if (!mysqli_query($conexion, $migration)) {
            error_log('No se pudo preparar la aprobaci¨®n de garant¨ªas textiles: ' . mysqli_error($conexion));
            return false;
        }
    }

    return true;
}

function estadosGarantiaTextil(): array
{
    return ['Recibida', 'En evaluaci¨®n', 'En proceso', 'Verificaci¨®n', 'Finalizada'];
}
