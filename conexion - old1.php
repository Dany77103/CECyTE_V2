<?php
// conexion.php
$host = 'localhost';      // Servidor de la base de datos
$dbname = 'cecyte_sc'; // Nombre de la base de datos
$username = 'root';       // Usuario de la base de datos
$password = '';           // Contraseña de la base de datos

try {
    $con = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Habilitar excepciones para errores
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>