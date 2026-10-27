<?php
function claveCifradoPortalCliente(): string
{
    $secreto = getenv('PORTAL_CLIENT_CREDENTIALS_KEY');
    if (!is_string($secreto) || $secreto === '') {
        global $pass;
        $secreto = isset($pass) ? (string)$pass : '';
    }
    if ($secreto === '') {
        error_log('Portal clientes: falta configurar una clave para cifrar credenciales.');
        return '';
    }
    return hash_hmac('sha256', 'unifuerte-portal-client-credentials-v1', $secreto, true);
}

function cifrarContrasenaPortalCliente(string $contrasena): string
{
    $clave = claveCifradoPortalCliente();
    if ($clave === '' || !function_exists('openssl_encrypt')) {
        error_log('Portal clientes: OpenSSL o la clave de cifrado no están disponibles.');
        return '';
    }

    $nonce = random_bytes(12);
    $etiqueta = '';
    $cifrado = openssl_encrypt($contrasena, 'aes-256-gcm', $clave, OPENSSL_RAW_DATA, $nonce, $etiqueta);
    if (!is_string($cifrado) || strlen($etiqueta) !== 16) {
        error_log('Portal clientes: no se pudo cifrar la contraseña.');
        return '';
    }
    return base64_encode($nonce . $etiqueta . $cifrado);
}

function descifrarContrasenaPortalCliente(string $valorCifrado)
{
    $clave = claveCifradoPortalCliente();
    $datos = base64_decode($valorCifrado, true);
    if ($clave === '' || !function_exists('openssl_decrypt') || !is_string($datos) || strlen($datos) <= 28) {
        error_log('Portal clientes: no se pudo preparar el descifrado de la contraseña.');
        return false;
    }

    $nonce = substr($datos, 0, 12);
    $etiqueta = substr($datos, 12, 16);
    $cifrado = substr($datos, 28);
    $contrasena = openssl_decrypt($cifrado, 'aes-256-gcm', $clave, OPENSSL_RAW_DATA, $nonce, $etiqueta);
    if (!is_string($contrasena) || $contrasena === '') {
        error_log('Portal clientes: la contraseña cifrada no pudo validarse.');
        return false;
    }
    return $contrasena;
}
