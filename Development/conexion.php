<?php
$host = $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$database = $_SERVER['DB_NAME'] ?? getenv('DB_NAME') ?: 'sistema_tutorias';
$user = $_SERVER['DB_USER'] ?? getenv('DB_USER') ?: 'app_user';
$password = $_SERVER['DB_PASS'] ?? getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    error_log('No se pudo conectar a MariaDB: ' . $e->getMessage());
    exit('No se pudo conectar con la base de datos. Revisa la configuración del contenedor.');
}
