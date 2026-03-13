<?php
// login_simple.php - SIN CONFIGURACIONES COMPLEJAS

// 1. Iniciar sesión SIN configuraciones
session_start();

// 2. Conexión directa (sin archivos externos)
try {
    $con = new PDO(
        'mysql:host=localhost;dbname=cecyte_sc;charset=utf8',
        'root',
        ''
    );
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a BD: " . $e->getMessage());
}

// 3. Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        echo "Error: Campos vacíos";
        exit;
    }
    
    $sql = "SELECT id, username, password FROM usuarios WHERE username = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch();
        
        // Verificar contraseña (ajusta según cómo las guardes)
        // Si usas password_hash():
        if (password_verify($password, $user['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            echo "Login exitoso! Redirigiendo...";
            header("Refresh: 2; URL=main.php");
            exit;
        } 
        // Si las contraseñas están en texto plano (NO RECOMENDADO):
        // elseif ($password === $user['password']) {
        //     // login exitoso
        // }
        else {
            echo "Contraseña incorrecta";
        }
    } else {
        echo "Usuario no encontrado";
    }
}
?>