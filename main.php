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
    <title>Panel Administrativo - CECYTE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --secondary: #1b5e20;
            --bg: #f0f2f5;
            --white: #ffffff;
            --text-dark: #333;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            color: var(--text-dark);
            min-height: 100vh;
        }
        
        .header { 
            background: var(--white); 
            padding: 1.2rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
        }

        .header h1 { font-size: 1.5rem; color: var(--secondary); font-weight: 700; }
        
        .user-info { display: flex; align-items: center; gap: 20px; }
        
        .btn-logout { 
            background: var(--primary); 
            color: white; 
            padding: 8px 16px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 600; 
            transition: background 0.3s;
        }

        .btn-logout:hover { background: var(--secondary); }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .welcome-card { 
            background: var(--white); 
            padding: 2rem; 
            border-radius: 20px; 
            margin-bottom: 2rem; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            border-left: 6px solid var(--primary);
        }

        .admin-menu { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 20px; 
        }
        
        .menu-card { 
            background: var(--white); 
            padding: 2rem; 
            border-radius: 15px; 
            text-align: center; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
            text-decoration: none; 
            color: var(--text-dark);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        /* Estilo para los iconos */
        .menu-card i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1.2rem;
        }

        .menu-card h3 { margin-bottom: 0.5rem; color: var(--secondary); font-size: 1.1rem; }
        .menu-card p { font-size: 0.9rem; color: #666; }
        
        .footer { text-align: center; margin-top: 40px; padding: 20px; color: #777; font-size: 0.9rem; }
        
        .user-role { 
            display: inline-block; 
            background: #e8f5e9; 
            color: var(--primary); 
            padding: 5px 15px; 
            border-radius: 20px; 
            margin-top: 10px; 
            font-weight: 600; 
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>SGA-CECYTE</h1>
        <div class="user-info">
            <span><i class="fa-solid fa-user-circle"></i> <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
        </div>
    </header>
    
    <div class="container">
        <div class="welcome-card">
            <h2 id="welcomeMsg">Panel de Control Administrativo</h2>
            <p><strong>Rol:</strong> <?php echo htmlspecialchars($_SESSION['rol']); ?></p>
            <span class="user-role">Panel Administrativo</span>
        </div>
        
        <div class="admin-menu">
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="gestion_usuarios.php" class="menu-card">
                    <i class="fa-solid fa-users-gear"></i>
                    <h3>Gestión de Usuarios</h3>
                    <p>Administrar usuarios del sistema</p>
                </a>
            <?php endif; ?>
            
            <a href="gestion_maestros.php" class="menu-card">
                <i class="fa-solid fa-chalkboard-user"></i>
                <h3>Gestión de Maestros</h3>
                <p>Administrar información de maestros</p>
            </a>
            
            <a href="gestion_alumnos.php" class="menu-card">
                <i class="fa-solid fa-user-graduate"></i>
                <h3>Gestión de Alumnos</h3>
                <p>Administrar información de alumnos</p>
            </a>
            
            <a href="captura_horario_maestros.php" class="menu-card">
                <i class="fa-solid fa-calendar-check"></i>
                <h3>Captura de Horarios</h3>
                <p>Horario de los maestros</p>
            </a>
            
            <a href="gestion_carreras.php" class="menu-card">
                <i class="fa-solid fa-book-bookmark"></i>
                <h3>Gestión de Carreras</h3>
                <p>Administrar carreras</p>
            </a>
            
            <a href="gestion_estadisticas.php" class="menu-card">
                <i class="fa-solid fa-chart-pie"></i>
                <h3>Análisis General</h3>
                <p>Análisis de datos global</p>
            </a>
            
            <a href="gestionar_fotos.php" class="menu-card">
                <i class="fa-solid fa-camera-retro"></i>
                <h3>Gestionar Fotos</h3>
                <p>Fotos de alumnos</p>
            </a>
            
            <a href="gestionar_grupos.php" class="menu-card">
                <i class="fa-solid fa-layer-group"></i>
                <h3>Gestionar Grupos</h3>
                <p>Grupos, alumnos y maestros</p>
            </a>
            
            <a href="seleccionar_clase.php" class="menu-card">
                <i class="fa-solid fa-square-poll-vertical"></i>
                <h3>Asistencia y Calif.</h3>
                <p>Administrar asistencia</p>
            </a>
            
            <a href="consulta_asistencia_alumnos.php" class="menu-card">
                <i class="fa-solid fa-clipboard-list"></i>
                <h3>Consulta Asistencia</h3>
                <p>Reporte de alumnos</p>
            </a>
            
            <a href="registro.php" class="menu-card">
                <i class="fa-solid fa-address-book"></i>
                <h3>Gestión de Registro</h3>
                <p>Registros de datos</p>
            </a>
            
            <a href="reportes.php" class="menu-card">
                <i class="fa-solid fa-file-invoice"></i>
                <h3>Gestión de Reportes</h3>
                <p>Administrar reportes</p>
            </a>
            
            <a href="estadisticas.php" class="menu-card">
                <i class="fa-solid fa-chart-line"></i>
                <h3>Reportes Estadísticos</h3>
                <p>Generar estadísticas</p>
            </a>
            
            <a href="configuracion.php" class="menu-card">
                <i class="fa-solid fa-sliders"></i>
                <h3>Configuración</h3>
                <p>Ajustes del sistema</p>
            </a>
            
            <a href="perfil2.php" class="menu-card">
                <i class="fa-solid fa-circle-user"></i>
                <h3>Mi Perfil</h3>
                <p>Configuración del perfil</p>
            </a>
        </div>
        
        <footer class="footer">
            <p>Sistema de Gestión Académica CECYTE © <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <script>
        const hour = new Date().getHours();
        let greeting = (hour < 12) ? 'Buenos días' : (hour < 19) ? 'Buenas tardes' : 'Buenas noches';
        document.getElementById('welcomeMsg').textContent = `${greeting}, <?php echo htmlspecialchars($_SESSION['username']); ?>`;
    </script>
</body>
</html>