<?php
session_start();
// Validación de sesión y rol
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
    <title>Gestión de Horarios | CECyTE</title>
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

        /* --- ANIMACIÓN (Igual al Dashboard) --- */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- NAVBAR --- */
        .navbar {
            background: var(--white);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand { display: flex; align-items: center; gap: 15px; text-decoration: none; }
        .navbar-brand img { height: 45px; }
        .navbar-brand span { font-weight: 700; color: var(--primary); font-size: 1.2rem; }

        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 20px; 
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* --- SECCIÓN DE BIENVENIDA / CABECERA --- */
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

        /* --- GRILLA DE TARJETAS (Mismo estilo que el menú) --- */
        .admin-menu { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 25px; 
            margin-bottom: 50px;
        }
        
        .menu-card { 
            background: var(--white); 
            padding: 35px 25px; 
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

        .menu-card i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .menu-card h3 { 
            margin-bottom: 10px; 
            color: #1a1a1a; 
            font-size: 1.2rem; 
            font-weight: 700;
        }
        
        .menu-card p { 
            font-size: 0.9rem; 
            color: var(--secondary);
            line-height: 1.5;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--secondary);
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 12px;
            background: #e9ecef;
            transition: var(--transition);
            margin-bottom: 20px;
        }

        .btn-back:hover { background: #dee2e6; color: var(--primary); }

        @media (max-width: 768px) {
            .navbar-brand span { display: none; }
            .admin-menu { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div style="display: flex; align-items: center; gap: 20px;">
            <span style="font-size: 0.9rem; font-weight: 600; color: var(--secondary);">
                <i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="main.php" class="btn-back" style="margin-bottom:0; padding: 8px 15px; font-size: 0.8rem;">
                <i class="fa-solid fa-house"></i> Inicio
            </a>
        </div>
    </nav>
    
    <div class="container">
        <a href="main.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Volver al Panel
        </a>

        <div class="welcome-section">
            <div class="welcome-text">
                <h2>Gestión de Horarios Académicos</h2>
                <p>Administración y visualización de carga académica institucional</p>
            </div>
            <div style="font-size: 2.5rem; color: var(--primary); opacity: 0.2;">
                <i class="fa-solid fa-calendar-week"></i>
            </div>
        </div>
        
        <div class="admin-menu">
            <a href="captura_horario_grupos.php" class="menu-card">
                <i class="fa-solid fa-users-rectangle"></i>
                <h3>Capturar por Grupo</h3>
                <p>Configuración detallada de materias y aulas para cada grupo y semestre.</p>
            </a>
            
            <a href="captura_horario_maestros.php" class="menu-card">
                <i class="fa-solid fa-chalkboard-teacher"></i>
                <h3>Capturar por Maestro</h3>
                <p>Asignación de carga horaria y disponibilidad por docente del plantel.</p>
            </a>
            
            <a href="consulta_horarios.php" class="menu-card">
                <i class="fa-solid fa-table-list"></i>
                <h3>Consulta por Grupo</h3>
                <p>Vista final de horarios, impresión y exportación para alumnos.</p>
            </a>
            
            <a href="consulta_horario_maestro.php" class="menu-card">
                <i class="fa-solid fa-user-clock"></i>
                <h3>Horario del Maestro</h3>
                <p>Visualización de la agenda individual de clases para cada docente.</p>
            </a>
        </div>
        
        <footer style="text-align: center; padding: 40px 0; color: var(--secondary); font-size: 0.85rem;">
            <p>CECyTE Santa Catarina — Sistema de Gestión Académica &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>

</body>
</html>