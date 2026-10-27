<?php
$host = "localhost";
$user = "soyunifu_user";
$pass = "unifuerte2026";
$db = "soyunifu_sistema";

$conexion = mysqli_connect($host, $user, $pass, $db);

if (!$conexion) {
    die("Error de conexión a la BD: " . mysqli_connect_error());
}

// Configurar charset para evitar problemas con tildes y ñ
mysqli_set_charset($conexion, "utf8mb4");

// Opcional: También podemos inicializar PDO aquí para los scripts que ya lo usan
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión PDO: " . $e->getMessage());
}
?>