<?php
// Incluir configuración común (Lógica intacta)
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    die("Error: Archivo de configuración no encontrado.");
}
verificarSesion();
$con = conectarDB();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración | CECyTE</title>
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

        /* --- NAVBAR (IDÉNTICA AL DASHBOARD) --- */
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

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }

        .navbar-brand img { height: 45px; width: auto; }

        .navbar-brand span {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.2rem;
            letter-spacing: -0.5px;
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
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 20px; 
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .section-header { 
            background: var(--white); 
            padding: 30px; 
            border-radius: 20px; 
            margin-bottom: 30px; 
            box-shadow: var(--shadow-md); 
            border-left: 6px solid var(--primary);
        }

        .section-header h2 { color: var(--primary); font-size: 1.6rem; margin-bottom: 5px; }
        .section-header p { color: var(--secondary); font-weight: 500; }

        /* --- GRILLA DE TARJETAS (ESTILO DASHBOARD) --- */
        .admin-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 25px; 
            margin-bottom: 50px;
        }
        
        .admin-card { 
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
        
        .admin-card:hover { 
            transform: translateY(-10px); 
            box-shadow: var(--shadow-md); 
            border-color: var(--primary-light);
        }

        /* Barra de acento inferior animada */
        .admin-card::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 100%; height: 4px;
            background: var(--primary);
            transform: scaleX(0);
            transition: var(--transition);
        }

        .admin-card:hover::after { transform: scaleX(1); }
        
        .admin-card i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .admin-card:hover i { transform: scale(1.1); color: var(--primary-light); }

        .admin-card h3 { 
            margin-bottom: 10px; 
            color: #1a1a1a; 
            font-size: 1.15rem; 
            font-weight: 700;
        }
        
        .admin-card p { 
            font-size: 0.85rem; 
            color: var(--secondary);
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .btn-access {
            margin-top: auto;
            background: var(--bg);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .admin-card:hover .btn-access {
            background: var(--primary);
            color: var(--white);
        }
        
        .footer { 
            text-align: center; 
            padding: 40px 0; 
            color: var(--secondary); 
            font-size: 0.85rem; 
        }

        @media (max-width: 768px) {
            .navbar-brand span { display: none; }
            .admin-grid { grid-template-columns: 1fr; }
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
            <a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-power-off"></i> Salir del Sistema
            </a>
        </div>
    </nav>
    
    <div class="container">
        <div class="section-header">
            <h2>Panel de Administración</h2>
            <p>Gestión integral de alumnos, personal y registros académicos.</p>
        </div>
        
        <div class="admin-grid">
            
            <a href="nuevo_alumno.php" class="admin-card">
                <i class="fa-solid fa-user-plus"></i>
                <h3>Alta de Alumnos</h3>
                <p>Inscripción y registro de nuevos estudiantes al sistema escolar.</p>
                <span class="btn-access">Registrar</span>
            </a>

            <a href="nuevo_maestro.php" class="admin-card">
                <i class="fa-solid fa-chalkboard-user"></i>
                <h3>Alta de Personal</h3>
                <p>Gestión y registro de docentes y personal administrativo.</p>
                <span class="btn-access">Registrar</span>
            </a>

            <a href="datos_laborales.php" class="admin-card">
                <i class="fa-solid fa-briefcase"></i>
                <h3>Datos Laborales</h3>
                <p>Información contractual y perfiles profesionales del personal.</p>
                <span class="btn-access">Consultar</span>
            </a>

            <a href="datos_academicos.php" class="admin-card">
                <i class="fa-solid fa-graduation-cap"></i>
                <h3>Datos Académicos</h3>
                <p>Historial de formación, grados obtenidos y especialidades.</p>
                <span class="btn-access">Consultar</span>
            </a>

            <a href="historial_academico.php" class="admin-card">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <h3>Historial Estudiantil</h3>
                <p>Consulta de trayectoria académica y kardex de alumnos.</p>
                <span class="btn-access">Ver Trayectoria</span>
            </a>

            <a href="seleccionar_clase.php" class="admin-card">
                <i class="fa-solid fa-file-signature"></i>
                <h3>Calificaciones</h3>
                <p>Control de evaluaciones y reportes de aprovechamiento escolar.</p>
                <span class="btn-access">Gestionar</span>
            </a>

            <a href="horario_maestros_captura.php" class="admin-card">
                <i class="fa-solid fa-calendar-check"></i>
                <h3>Horarios</h3>
                <p>Planificación de carga horaria y disponibilidad docente.</p>
                <span class="btn-access">Planificar</span>
            </a>

        </div>
        
        <footer class="footer">
            <p>CECyTE Santa Catarina — Gestión Administrativa &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>

</body>
</html>