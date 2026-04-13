<?php
session_start();

// VERIFICAR QUE SEA USUARIO DEL SISTEMA
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Simulación de datos (estos normalmente vendrían de tu consulta SQL a la tabla de usuarios)
$nombre_usuario = $_SESSION['username'];
$rol_usuario = $_SESSION['rol'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | CECyTE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --secondary: #6c757d;
            --white: #ffffff;
            --bg: #f4f6f9;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            color: #333;
            padding-top: 90px;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- NAVBAR (IDÉNTICO AL DASHBOARD) --- */
        .navbar {
            background: var(--white);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand { display: flex; align-items: center; gap: 15px; text-decoration: none; }
        .navbar-brand img { height: 45px; width: auto; }
        .navbar-brand span { font-weight: 700; color: var(--primary); font-size: 1.2rem; letter-spacing: -0.5px; }

        .user-controls { display: flex; align-items: center; gap: 20px; }
        .btn-logout { 
            background: #f8d7da; color: #721c24; padding: 8px 16px; border-radius: 8px; 
            text-decoration: none; font-weight: 600; font-size: 0.85rem;
            transition: var(--transition); display: flex; align-items: center; gap: 8px;
        }
        .btn-logout:hover { background: #f5c6cb; transform: translateY(-2px); }

        /* --- CONTENEDOR --- */
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            padding: 0 20px; 
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        /* Sección de Encabezado de Perfil */
        .profile-header { 
            background: var(--white); 
            padding: 40px; 
            border-radius: 20px; 
            margin-bottom: 30px; 
            box-shadow: var(--shadow-md); 
            display: flex;
            align-items: center;
            gap: 30px;
            border-left: 6px solid var(--primary);
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: #e8f5e9;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .profile-title h2 { color: var(--primary); font-size: 1.8rem; margin-bottom: 5px; }
        
        .role-badge { 
            background: #e8f5e9; 
            color: var(--primary-light); 
            padding: 6px 15px; 
            border-radius: 12px; 
            font-weight: 700; 
            font-size: 0.8rem;
            text-transform: uppercase;
            display: inline-block;
        }

        /* --- GRILLA DE INFORMACIÓN (Estilo Cards del Dashboard) --- */
        .profile-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 25px; 
            margin-bottom: 50px;
        }
        
        .info-card { 
            background: var(--white); 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: var(--shadow-sm); 
            border: 1px solid rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .info-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
        .info-card::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 4px;
            background: var(--primary); transform: scaleX(0); transition: var(--transition);
        }
        .info-card:hover::after { transform: scaleX(1); }

        .info-card h3 { 
            color: var(--primary); 
            font-size: 1.1rem; 
            margin-bottom: 20px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }

        .data-group { margin-bottom: 15px; }
        .data-group label { 
            display: block; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            color: var(--secondary); 
            font-weight: 700;
            margin-bottom: 4px;
        }
        .data-group p { font-size: 1rem; font-weight: 500; color: #1a1a1a; }

        /* Botones de acción */
        .btn-primary-custom {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }
        .btn-primary-custom:hover { background: var(--primary-light); transform: translateY(-2px); }

        .footer { 
            text-align: center; padding: 40px 0; color: var(--secondary); 
            font-size: 0.85rem; font-weight: 500;
        }

        @media (max-width: 768px) {
            .profile-header { flex-direction: column; text-align: center; }
            .navbar-brand span { display: none; }
        }
    </style>
</head>
<body>
    
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div class="user-controls">
            <a href="main.php" style="text-decoration: none; color: var(--primary); font-weight: 600; font-size: 0.9rem;">
                <i class="fa-solid fa-house"></i> Panel
            </a>
            <a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-power-off"></i> Salir
            </a>
        </div>
    </nav>
    
    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fa-solid fa-circle-user"></i>
            </div>
            <div class="profile-title">
                <p style="color: var(--secondary); font-weight: 600; margin-bottom: 5px;">Configuración de cuenta</p>
                <h2><?php echo htmlspecialchars($nombre_usuario); ?></h2>
                <div class="role-badge">
                    <i class="fa-solid fa-shield-check"></i> <?php echo htmlspecialchars($rol_usuario); ?>
                </div>
            </div>
        </div>
        
        <div class="profile-grid">
            <div class="info-card">
                <h3><i class="fa-solid fa-address-card"></i> Información Personal</h3>
                <div class="data-group">
                    <label>Nombre de Usuario</label>
                    <p><?php echo htmlspecialchars($nombre_usuario); ?></p>
                </div>
                <div class="data-group">
                    <label>Rol de Acceso</label>
                    <p><?php echo htmlspecialchars($rol_usuario); ?></p>
                </div>
                <div class="data-group">
                    <label>Institución</label>
                    <p>CECyTE Santa Catarina</p>
                </div>
            </div>

            <div class="info-card">
                <h3><i class="fa-solid fa-lock"></i> Seguridad</h3>
                <p style="color: var(--secondary); font-size: 0.85rem; margin-bottom: 20px;">
                    Se recomienda cambiar su contraseña periódicamente para mantener la seguridad de su cuenta.
                </p>
                <button class="btn-primary-custom">
                    <i class="fa-solid fa-key"></i> Cambiar Contraseña
                </button>
            </div>
        </div>
        
        <footer class="footer">
            <p>CECyTE Santa Catarina — Sistema de Gestión Académica &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>

</body>
</html>