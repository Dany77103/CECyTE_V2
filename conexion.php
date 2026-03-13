<?php
// conexion.php - CONEXIÓN A BD CORREGIDA

// Verificar si config.php existe
if (!file_exists('config.php')) {
    die("ERROR: Archivo config.php no encontrado.");
}

require_once 'config.php';

// Verificar que las constantes estén definidas
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    die("ERROR: Constantes de configuración no definidas en config.php.");
}

try {
    $con = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS  // <-- Usa DB_PASS aquí
    );
    
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $con->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // NO uses return $con; porque no es una función
    // Simplemente deja $con disponible globalmente
    
} catch (PDOException $e) {
    die("Error de conexión a BD: " . $e->getMessage());
}

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

?>