<?php
echo "<h3>Debug Login</h3>";

// 1. Verificar sesión
echo "session_status(): " . session_status() . "<br>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "Sesión iniciada<br>";
}

// 2. Verificar conexión a BD
try {
    $pdo = new PDO('mysql:host=localhost;dbname=cecyte_sc', 'root', '');
    echo "? Conexión a BD exitosa<br>";
    
    // 3. Verificar tabla usuarios
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() > 0) {
        echo "? Tabla 'usuarios' existe<br>";
        
        // 4. Mostrar usuarios existentes
        $users = $pdo->query("SELECT id, username FROM usuarios")->fetchAll();
        echo "Usuarios en BD:<br>";
        foreach ($users as $user) {
            echo "- ID: {$user['id']}, Usuario: {$user['username']}<br>";
        }
    } else {
        echo "? Tabla 'usuarios' NO existe<br>";
    }
    
} catch (PDOException $e) {
    echo "? Error BD: " . $e->getMessage() . "<br>";
}

// 5. Formulario de prueba
echo '<form method="post">
    Usuario: <input type="text" name="username"><br>
    Contraseña: <input type="password" name="password"><br>
    <input type="submit" value="Probar Login">
</form>';

// 6. Probar login si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM usuarios WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        echo "<pre>Usuario encontrado: ";
        print_r($user);
        echo "</pre>";
        
        // Verificar contraseña
        if (password_verify($password, $user['password'])) {
            echo "? Contraseña VERIFICADA (hash coincide)";
        } else {
            echo "? Contraseña NO coincide<br>";
            echo "Hash en BD: " . $user['password'] . "<br>";
            echo "Hash de lo ingresado: " . password_hash($password, PASSWORD_DEFAULT);
        }
    } else {
        echo "Usuario no encontrado";
    }
}
?>