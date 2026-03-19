<?php
session_start();

// VERIFICAR QUE SEA USUARIO DEL SISTEMA (no maestro)
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo | CECyTE</title>
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
            padding-top: 90px; /* Espacio para el header fijo */
        }

        /* --- NAVBAR SUPERIOR --- */
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

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }

        .navbar-brand img {
            height: 45px;
            width: auto;
        }

        .navbar-brand span {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.2rem;
            letter-spacing: -0.5px;
        }

        .user-controls {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-logout { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 8px 16px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.85rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-logout:hover { background: #f5c6cb; transform: translateY(-2px); }

        /* --- CONTENEDOR --- */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        
        /* Bienvenida */
        .welcome-section { 
            background: var(--white); 
            padding: 30px; 
            border-radius: 20px; 
            margin-bottom: 30px; 
            box-shadow: var(--shadow-md); 
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 6px solid var(--primary);
        }

        .welcome-text h2 { color: var(--primary); font-size: 1.6rem; margin-bottom: 5px; }
        .welcome-text p { color: var(--secondary); font-weight: 500; }

        .role-badge { 
            background: #e8f5e9; 
            color: var(--primary-light); 
            padding: 6px 15px; 
            border-radius: 12px; 
            font-weight: 700; 
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        /* --- GRILLA DE MENÚ --- */
        .admin-menu { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); 
            gap: 25px; 
            margin-bottom: 50px;
        }
        
        .menu-card { 
            background: var(--white); 
            padding: 30px 20px; 
            border-radius: 20px; 
            text-align: center; 
            box-shadow: var(--shadow-sm); 
            text-decoration: none; 
            color: #333;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .menu-card:hover { 
            transform: translateY(-10px); 
            box-shadow: var(--shadow-md); 
            border-color: var(--primary-light);
        }

        .menu-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
            transform: scaleX(0);
            transition: var(--transition);
        }

        .menu-card:hover::after { transform: scaleX(1); }
        
        .menu-card i {
            font-size: 2.8rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .menu-card:hover i { transform: scale(1.1); color: var(--primary-light); }

        .menu-card h3 { 
            margin-bottom: 8px; 
            color: #1a1a1a; 
            font-size: 1.1rem; 
            font-weight: 700;
        }
        
        .menu-card p { 
            font-size: 0.85rem; 
            color: var(--secondary);
            line-height: 1.4;
        }
        
        .footer { 
            text-align: center; 
            padding: 40px 0; 
            color: var(--secondary); 
            font-size: 0.85rem; 
            font-weight: 500;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .navbar-brand span { display: none; }
            .welcome-section { flex-direction: column; text-align: center; gap: 15px; }
            .admin-menu { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo_cecyte.jpg" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div class="user-controls">
            <span style="font-size: 0.9rem; font-weight: 600; color: var(--secondary);">
                <i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-power-off"></i> Salir
            </a>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-section">
            <div class="welcome-text">
                <h2 id="welcomeMsg">Cargando bienvenida...</h2>
                <p>Bienvenido al Sistema de Gestión Académica</p>
            </div>
            <div class="role-badge">
                <i class="fa-solid fa-shield-check"></i> <?php echo htmlspecialchars($_SESSION['rol']); ?>
            </div>
        </div>
        
        <div class="admin-menu">
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="gestion_usuarios.php" class="menu-card">
                    <i class="fa-solid fa-users-gear"></i>
                    <h3>Gestión de Usuarios</h3>
                    <p>Administrar cuentas y niveles de acceso al sistema</p>
                </a>
            <?php endif; ?>
            
            <a href="gestion_maestros.php" class="menu-card">
                <i class="fa-solid fa-chalkboard-user"></i>
                <h3>Gestión de Maestros</h3>
                <p>Control de expedientes y datos de docentes</p>
            </a>
            
            <a href="gestion_alumnos.php" class="menu-card">
                <i class="fa-solid fa-user-graduate"></i>
                <h3>Gestión de Alumnos</h3>
                <p>Administración de matrículas y datos escolares</p>
            </a>
            
            <a href="captura_horario_maestros.php" class="menu-card">
                <i class="fa-solid fa-calendar-days"></i>
                <h3>Captura de Horarios</h3>
                <p>Asignación y gestión de tiempos docentes</p>
            </a>
            
            <a href="gestion_carreras.php" class="menu-card">
                <i class="fa-solid fa-book-open"></i>
                <h3>Gestión de Carreras</h3>
                <p>Catálogo de oferta educativa institucional</p>
            </a>
            
            <a href="gestion_estadisticas.php" class="menu-card">
                <i class="fa-solid fa-chart-simple"></i>
                <h3>Análisis General</h3>
                <p>Visualización de métricas y datos globales</p>
            </a>
            
            <a href="gestionar_fotos.php" class="menu-card">
                <i class="fa-solid fa-camera"></i>
                <h3>Gestionar Fotos</h3>
                <p>Galería y control de imágenes de alumnos</p>
            </a>
            
            <a href="gestionar_grupos.php" class="menu-card">
                <i class="fa-solid fa-layer-group"></i>
                <h3>Gestionar Grupos</h3>
                <p>Organización de grupos, alumnos y tutores</p>
            </a>
            
            <a href="seleccionar_clase.php" class="menu-card">
                <i class="fa-solid fa-list-check"></i>
                <h3>Asistencia y Calif.</h3>
                <p>Registro de pases de lista y evaluaciones</p>
            </a>
            
            <a href="consulta_asistencia_alumnos.php" class="menu-card">
                <i class="fa-solid fa-clipboard-user"></i>
                <h3>Consulta Asistencia</h3>
                <p>Reportes individuales y grupales de asistencia</p>
            </a>
            
            <a href="registro.php" class="menu-card">
                <i class="fa-solid fa-file-signature"></i>
                <h3>Gestión de Registro</h3>
                <p>Bitácora de movimientos y nuevos registros</p>
            </a>
            
            <a href="reportes.php" class="menu-card">
                <i class="fa-solid fa-file-pdf"></i>
                <h3>Gestión de Reportes</h3>
                <p>Generación de documentos y actas oficiales</p>
            </a>
            
            <a href="estadisticas.php" class="menu-card">
                <i class="fa-solid fa-chart-line"></i>
                <h3>Estadísticas</h3>
                <p>Reportes estadísticos detallados</p>
            </a>
            
            <a href="configuracion.php" class="menu-card">
                <i class="fa-solid fa-gears"></i>
                <h3>Configuración</h3>
                <p>Ajustes generales del sistema y parámetros</p>
            </a>
            
            <a href="perfil2.php" class="menu-card">
                <i class="fa-solid fa-user-pen"></i>
                <h3>Mi Perfil</h3>
                <p>Actualizar datos personales y contraseña</p>
            </a>
        </div>
        
        <footer class="footer">
            <p>CECyTE Santa Catarina — Sistema de Gestión Académica &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <script>
        // Saludo dinámico según la hora
        const hour = new Date().getHours();
        let greeting = (hour < 12) ? '¡Buenos días!' : (hour < 19) ? '¡Buenas tardes!' : '¡Buenas noches!';
        document.getElementById('welcomeMsg').textContent = `${greeting}, <?php echo htmlspecialchars($_SESSION['username']); ?>`;
    </script>
</body>
</html>