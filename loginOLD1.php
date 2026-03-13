<?php
session_start();
require_once 'conexion.php'; // Tu archivo de conexión

// Si ya está logueado, redirigir según su rol
if (isset($_SESSION['loggedin'])) {
    if ($_SESSION['tipo_usuario'] === 'maestro') {
        header('Location: seleccionar_clase.php');
    } else {
        header('Location: main.php');
    }
    exit;
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identificador = trim($_POST['identificador'] ?? '');
    $password = $_POST['password'] ?? '';
    $tipo_login = $_POST['tipo_login'] ?? 'maestro'; // 'maestro' o 'usuario'
    
    if (empty($identificador) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        try {
            if ($tipo_login === 'maestro') {
                // LOGIN PARA MAESTROS
                $sql = "SELECT id_maestro, numEmpleado, nombre, correo_institucional, estado 
                        FROM maestros 
                        WHERE numEmpleado = :identificador AND estado = 'Activo' 
                        LIMIT 1";
                
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':identificador', $identificador);
                $stmt->execute();
                
                if ($stmt->rowCount() === 1) {
                    $maestro = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Verificar contraseña (simplificado para pruebas)
                    // En producción, deberías usar password_hash()
                    if ($password === $identificador) { // Contraseña = número de empleado para pruebas
                        $_SESSION['loggedin'] = true;
                        $_SESSION['tipo_usuario'] = 'maestro';
                        $_SESSION['user_id'] = $maestro['id_maestro'];
                        $_SESSION['username'] = $maestro['numEmpleado'];
                        $_SESSION['nombre_completo'] = $maestro['nombre'];
                        $_SESSION['email'] = $maestro['correo_institucional'];
                        
                        header('Location: seleccionar_clase.php');
                        exit;
                    } else {
                        $error = "Contraseña incorrecta";
                    }
                } else {
                    $error = "Número de empleado no encontrado o inactivo";
                }
                
            } else {
                // LOGIN PARA USUARIOS DEL SISTEMA (admin, etc.)
                $sql = "SELECT id, username, password, rol 
                        FROM usuarios 
                        WHERE username = :identificador 
                        LIMIT 1";
                
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':identificador', $identificador);
                $stmt->execute();
                
                if ($stmt->rowCount() === 1) {
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Verificar contraseña con hash
                    if (password_verify($password, $usuario['password'])) {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['tipo_usuario'] = 'sistema';
                        $_SESSION['user_id'] = $usuario['id'];
                        $_SESSION['username'] = $usuario['username'];
                        $_SESSION['rol'] = $usuario['rol'] ?? 'usuario';
                        
                        header('Location: main.php');
                        exit;
                    } else {
                        $error = "Contraseña incorrecta";
                    }
                } else {
                    $error = "Usuario no encontrado";
                }
            }
            
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = "Error en el servidor. Por favor, intente más tarde.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema CECYTE - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .login-type {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .login-type label {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            flex: 1;
            transition: all 0.3s;
        }
        
        .login-type input[type="radio"] {
            display: none;
        }
        
        .login-type input[type="radio"]:checked + span {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .login-type span {
            display: block;
            width: 100%;
            text-align: center;
            padding: 8px;
            border-radius: 3px;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-login:hover {
            background: #5a67d8;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c33;
        }
        
        .test-credentials {
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .test-credentials h4 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .test-credentials ul {
            list-style: none;
            padding-left: 0;
        }
        
        .test-credentials li {
            margin-bottom: 5px;
            padding-left: 15px;
            position: relative;
        }
        
        .test-credentials li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #667eea;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>CECYTE</h1>
            <p>Sistema de Gestión Escolar</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="tipo_login">Tipo de Usuario:</label>
                <div class="login-type">
                    <label>
                        <input type="radio" name="tipo_login" value="maestro" checked>
                        <span>Maestro</span>
                    </label>
                    <label>
                        <input type="radio" name="tipo_login" value="usuario">
                        <span>Administrador</span>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="identificador" id="label-identificador">
                    Número de Empleado:
                </label>
                <input type="text" 
                       id="identificador" 
                       name="identificador" 
                       required 
                       placeholder="Ej: M1 o admin">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required 
                       placeholder="Ingresa tu contraseña">
            </div>
            
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>
        
        <div class="test-credentials">
            <h4>Credenciales de Prueba:</h4>
            <ul>
                <li><strong>Maestro:</strong> Usuario: M1, Contraseña: M1</li>
                <li><strong>Administrador:</strong> Usuario: admin, Contraseña: admin123</li>
            </ul>
        </div>
    </div>

    <script>
        // Cambiar etiqueta según tipo de usuario
        const tipoLoginRadios = document.querySelectorAll('input[name="tipo_login"]');
        const labelIdentificador = document.getElementById('label-identificador');
        const inputIdentificador = document.getElementById('identificador');
        
        function actualizarEtiqueta() {
            const tipo = document.querySelector('input[name="tipo_login"]:checked').value;
            
            if (tipo === 'maestro') {
                labelIdentificador.textContent = 'Número de Empleado:';
                inputIdentificador.placeholder = 'Ej: M1, M2, M3';
            } else {
                labelIdentificador.textContent = 'Nombre de Usuario:';
                inputIdentificador.placeholder = 'Ej: admin, eme, mon';
            }
        }
        
        tipoLoginRadios.forEach(radio => {
            radio.addEventListener('change', actualizarEtiqueta);
        });
        
        // Inicializar
        actualizarEtiqueta();
    </script>
</body>
</html>