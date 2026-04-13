<?php
// Incluir configuración común
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    die("Error: Archivo de configuración no encontrado.");
}

// Verificar sesión
verificarSesion();

// Conectar a la base de datos
$con = conectarDB();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reportes | CECyTE</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- ANIMACIÓN DE ENTRADA --- */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- NAVBAR (Diseño unificado) --- */
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

        .navbar-brand img { height: 45px; width: auto; }

        .navbar-brand span {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.2rem;
            letter-spacing: -0.5px;
        }

        .user-controls { display: flex; align-items: center; gap: 20px; }

        /* --- CONTENEDOR Y TÍTULOS --- */
        .container-main { 
            flex: 1;
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 20px; 
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.6rem;
            margin: 0;
        }

        /* --- BOTÓN VOLVER --- */
        .btn-back {
            background: var(--white);
            color: var(--secondary);
            padding: 8px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.05);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #f8f9fa;
            color: var(--primary);
            transform: translateX(-5px);
        }

        /* --- TARJETAS DE REPORTE (Estilo Menu Card) --- */
        .card-report { 
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
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .card-report:hover { 
            transform: translateY(-10px); 
            box-shadow: var(--shadow-md); 
            border-color: var(--primary-light);
        }

        .card-report::after {
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

        .card-report:hover::after { transform: scaleX(1); }
        
        .card-report i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1.2rem;
            transition: var(--transition);
        }

        .card-report h3 { 
            margin-bottom: 10px; 
            color: #1a1a1a; 
            font-size: 1.15rem; 
            font-weight: 700;
        }
        
        .card-report p { 
            font-size: 0.85rem; 
            color: var(--secondary);
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .btn-generate {
            margin-top: auto;
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-generate:hover {
            background: var(--primary-light);
            color: white;
        }

        .footer { 
            text-align: center; 
            padding: 40px 0; 
            color: var(--secondary); 
            font-size: 0.85rem; 
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .navbar-brand span { display: none; }
            .page-header { flex-direction: column; gap: 15px; text-align: center; }
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
            <span style="font-size: 0.9rem; font-weight: 600; color: var(--secondary);">
                <i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
            </span>
            <a href="logout.php" class="btn-logout" style="background: #f8d7da; color: #721c24; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-power-off"></i> Salir
            </a>
        </div>
    </nav>

    <div class="container-main">
        <div class="page-header mt-4">
            <h2 class="page-title">Panel de Reportes Académicos</h2>
            <a href="main.php" class="btn-back">
                <i class='bx bx-left-arrow-alt'></i> Volver al Inicio
            </a>
        </div>
        
        <div class="row g-4 mb-5">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-report">
                    <i class="fas fa-users"></i>
                    <h3>Listado de Alumnos</h3>
                    <p>Consulta la base de datos completa de estudiantes, filtrada por grupo o carrera.</p>
                    <a href="lista_alumnos.php" class="btn-generate">Generar Reporte</a>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-report">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>Cuerpo Docente</h3>
                    <p>Información detallada de los profesores y sus asignaciones actuales.</p>
                    <a href="lista_maestros.php" class="btn-generate">Generar Reporte</a>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-report">
                    <i class="fas fa-chart-line"></i>
                    <h3>Calificaciones</h3>
                    <p>Reportes de rendimiento académico por parcial y promedios finales.</p>
                    <a href="lista_calificaciones.php" class="btn-generate">Generar Reporte</a>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-report">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Horarios de Clase</h3>
                    <p>Consulta la distribución de horas por salón, maestro y grupo.</p>
                    <a href="lista_horarios.php" class="btn-generate">Ver Horarios</a>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-report">
                    <i class="fas fa-book"></i>
                    <h3>Plan de Estudios</h3>
                    <p>Listado de materias vigentes, créditos y carga horaria por semestre.</p>
                    <a href="lista_materias.php" class="btn-generate">Ver Materias</a>
                </div>
            </div>
        </div>
        
        <footer class="footer">
            <p>CECyTE Santa Catarina — Sistema de Gestión Académica &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>