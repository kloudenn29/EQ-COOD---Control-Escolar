<?php
/**
 * conexion.php
 * ------------------------------------------------------------
 * Archivo central de conexión a la base de datos MySQL.
 * Usa PDO porque permite "consultas preparadas", que evitan
 * inyección SQL (una buena práctica de seguridad básica).
 *
 * Todos los demás archivos PHP del sistema incluyen este
 * archivo con: require_once '../includes/conexion.php';
 * ------------------------------------------------------------
 */

// Datos de conexión por defecto de XAMPP
$host = 'localhost';
$nombre_bd = 'sistema_escolar';
$usuario_bd = 'root';
$password_bd = ''; // XAMPP no pone contraseña al usuario root por defecto

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$nombre_bd;charset=utf8mb4",
        $usuario_bd,
        $password_bd
    );
    // Si algo falla, que lance una excepción en vez de fallar en silencio
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $error) {
    die('Error al conectar con la base de datos: ' . $error->getMessage());
}
