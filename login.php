<?php
session_start();
require_once 'conexion.php'; 

if (isset($_SESSION['loggedin'])) {
    switch ($_SESSION['rol']) {
        case 'maestro': case 'Maestro': header('Location: seleccionar_clase.php'); break;
        case 'admin': case 'administrador': header('Location: main.php'); break;
        case 'alumno': case 'Alumno': header('Location: panel_alumno.php'); break;
        case 'usuario': default: header('Location: main.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identificador = trim($_POST['identificador'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($identificador) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        try {
            $sql = "SELECT id, username, password, rol, activo FROM usuarios WHERE username = :identificador LIMIT 1";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':identificador', $identificador);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($usuario['activo'] != 1) {
                    $error = "Usuario inactivo. Contacta al administrador.";
                } elseif (password_verify($password, $usuario['password'])) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['tipo_usuario'] = 'sistema';
                    $_SESSION['user_id'] = $usuario['id'];
                    $_SESSION['username'] = $usuario['username'];
                    $_SESSION['rol'] = $usuario['rol'];
                    
                    switch (strtolower($usuario['rol'])) {
                        case 'maestro':
                            $sql_maestro = "SELECT id_maestro, numEmpleado, nombre, correo_institucional FROM maestros WHERE numEmpleado = :username AND activo = 'Activo' LIMIT 1";
                            $stmt_maestro = $con->prepare($sql_maestro);
                            $stmt_maestro->bindParam(':username', $usuario['username']);
                            $stmt_maestro->execute();
                            if ($stmt_maestro->rowCount() === 1) {
                                $maestro = $stmt_maestro->fetch(PDO::FETCH_ASSOC);
                                $_SESSION['maestro_id'] = $maestro['id_maestro'];
                                $_SESSION['nombre_completo'] = $maestro['nombre'];
                                $_SESSION['email'] = $maestro['correo_institucional'];
                                $_SESSION['numEmpleado'] = $maestro['numEmpleado'];
                            }
                            header('Location: seleccionar_clase.php');
                            break;
                        case 'alumno':
                            $sql_alumno = "SELECT id_alumno, matricula, nombre, apellido_paterno, apellido_materno, correo_institucional FROM alumnos WHERE matricula = :username AND activo = 'Activo' LIMIT 1";
                            $stmt_alumno = $con->prepare($sql_alumno);
                            $stmt_alumno->bindParam(':username', $usuario['username']);
                            $stmt_alumno->execute();
                            if ($stmt_alumno->rowCount() === 1) {
                                $alumno = $stmt_alumno->fetch(PDO::FETCH_ASSOC);
                                $_SESSION['alumno_id'] = $alumno['id_alumno'];
                                $_SESSION['matricula'] = $alumno['matricula'];
                                $_SESSION['nombre_completo'] = $alumno['nombre'] . ' ' . $alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'];
                                $_SESSION['email'] = $alumno['correo_institucional'];
                            }
                            header('Location: panel_alumno.php');
                            break;
                        case 'admin': case 'administrador': $_SESSION['nombre_completo'] = 'Administrador'; header('Location: main.php'); break;
                        default: $_SESSION['nombre_completo'] = $usuario['username']; header('Location: main.php');
                    }
                    exit;
                } else { $error = "Contraseña incorrecta"; }
            } else {
                // Lógica alternativa de maestro
                $sql_maestro = "SELECT id_maestro, numEmpleado, nombre, correo_institucional, activo FROM maestros WHERE numEmpleado = :identificador AND activo = 'Activo' LIMIT 1";
                $stmt_maestro = $con->prepare($sql_maestro);
                $stmt_maestro->bindParam(':identificador', $identificador);
                $stmt_maestro->execute();
                if ($stmt_maestro->rowCount() === 1) {
                    $maestro = $stmt_maestro->fetch(PDO::FETCH_ASSOC);
                    if ($password === $identificador) {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['tipo_usuario'] = 'maestro';
                        $_SESSION['maestro_id'] = $maestro['id_maestro'];
                        $_SESSION['username'] = $maestro['numEmpleado'];
                        $_SESSION['rol'] = 'maestro';
                        $_SESSION['nombre_completo'] = $maestro['nombre'];
                        $_SESSION['email'] = $maestro['correo_institucional'];
                        $_SESSION['numEmpleado'] = $maestro['numEmpleado'];
                        header('Location: seleccionar_clase.php');
                        exit;
                    } else { $error = "Contraseña incorrecta. Prueba con tu número de empleado."; }
                } else { $error = "Usuario no encontrado"; }
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2e7d32; --bg: #f0f2f5; --white: #ffffff; --error: #d32f2f; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg); height: 100vh; margin: 0; display: flex; flex-direction: column; }
        .top-header { background: var(--white); padding: 1.2rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .header-left { display: flex; align-items: center; gap: 15px; font-size: 1.2rem; }
        .header-right { font-weight: 600; color: var(--primary); font-size: 1.1rem; }
        .header-logo { height: 60px; }
        .main-container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .login-card { background: var(--white); padding: 4rem; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 900px; display: flex; gap: 40px; }
        .login-content, .info-section { flex: 1; }
        .info-section { border-left: 1px solid #eee; padding-left: 40px; color: #555; }
        .logo-box { text-align: center; margin-bottom: 2.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 16px; font-size: 1.2rem; border: 2px solid #e1e1e1; border-radius: 12px; }
        .btn-login { width: 100%; padding: 16px; font-size: 1.3rem; background: var(--primary); color: white; border: none; border-radius: 12px; cursor: pointer; }
        
        /* Estilos del termómetro */
        .strength-meter { height: 8px; width: 100%; background: #e1e1e1; border-radius: 4px; margin-top: 10px; overflow: hidden; }
        .strength-bar { height: 100%; width: 0%; transition: width 0.3s, background-color 0.3s; }
    </style>
</head>
<body>

    <header class="top-header">
        <div class="header-left">
            <img src="img/logo.png" alt="Logo" class="header-logo">
            <div><strong>CECYTE</strong> | Santa Catarina</div>
        </div>
        <div class="header-right" id="clock"></div>
    </header>

    <div class="main-container">
        <div class="login-card">
            <div class="login-content">
                <div class="logo-box"><h2>Iniciar Sesión</h2></div>
                <?php if (!empty($error)): ?><div style="color: var(--error); margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Usuario / Matrícula</label>
                        <input type="text" name="identificador" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" id="password" required oninput="checkStrength()">
                        <div class="strength-meter"><div id="strengthBar" class="strength-bar"></div></div>
                    </div>
                    <button type="submit" class="btn-login">Entrar</button>
                </form>
            </div>

            <div class="info-section">
                <h3>CECYTE Santa Catarina</h3>
                <p>Somos un plantel comprometido con la excelencia educativa, brindando una formación técnica integral que prepara a nuestros estudiantes para los desafíos del mundo profesional.</p>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            document.getElementById('clock').textContent = new Date().toLocaleTimeString('es-ES', { hour12: false });
        }
        setInterval(updateClock, 1000);
        updateClock();

        function checkStrength() {
            let password = document.getElementById('password').value;
            let bar = document.getElementById('strengthBar');
            let strength = 0;
            if (password.length > 6) strength += 25;
            if (password.match(/[A-Z]/)) strength += 25;
            if (password.match(/[0-9]/)) strength += 25;
            if (password.match(/[^A-Za-z0-9]/)) strength += 25;
            
            bar.style.width = strength + '%';
            bar.style.backgroundColor = strength < 50 ? '#d32f2f' : (strength < 100 ? '#fbc02d' : '#2e7d32');
        }
    </script>
</body>
</html>