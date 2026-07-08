<?php
declare(strict_types=1);

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Configuración por defecto de XAMPP
    $host = "localhost";
    // $port = "3307"; // <-- Por si solicita un puerto ingresar el puerto de su BD
    $db   = 'cafeteria_db';
    $user = 'root';
    $pass = ''; // XAMPP por defecto NO tiene contraseña. Se deja en blanco.
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
   // $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";// <-- AQUÍ SE AÑADIÓ port={$port}; A LA CADENA DE CONEXIÓN

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        throw new PDOException("Error de conexión a XAMPP: " . $e->getMessage(), (int) $e->getCode());
    }
}
