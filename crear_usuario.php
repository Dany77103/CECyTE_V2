<?php
// crear_usuario.php
require_once 'conexion.php';

$username = 'mon';
$password = '123';

// Hashear la contraseña
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO usuarios (username, password, rol) VALUES (:username, :password, 'admin')";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password_hash);
    
    if ($stmt->execute()) {
        echo "Usuario creado exitosamente:<br>";
        echo "Usuario: $username<br>";
        echo "Contraseña: $password<br>";
        echo "Hash: $password_hash";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>