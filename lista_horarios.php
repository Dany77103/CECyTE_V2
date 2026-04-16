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
    <title>Horarios de Clase | CECyTE</title>
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

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            padding-top: 90px;
            min-height: 100vh;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* NAVBAR */
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

        /* CONTENEDOR */
        .container-main { 
            max-width: 900px; 
            margin: 0 auto; 
            padding: 0 20px; 
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .btn-back {
            background: var(--white);
            color: var(--secondary);
            padding: 8px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.05);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover { color: var(--primary); transform: translateX(-5px); }

        /* TARJETAS DE SELECCIÓN */
        .selection-card {
            background: var(--white);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
            border: 1px solid rgba(0,0,0,0.03);
            display: block;
            height: 100%;
            box-shadow: var(--shadow-sm);
        }

        .selection-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            background: #e8f5e9;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            transition: var(--transition);
        }

        .selection-card:hover .icon-circle {
            background: var(--primary);
            color: var(--white);
        }

        .selection-card h3 {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: #1a1a1a;
        }

        .selection-card p {
            color: var(--secondary);
            font-size: 0.95rem;
            margin-bottom: 0;
        }

        .footer { 
            text-align: center; 
            padding: 50px 0; 
            color: var(--secondary); 
            font-size: 0.85rem; 
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div class="user-controls d-flex align-items-center gap-3">
             <span class="d-none d-md-inline" style="font-size: 0.9rem; font-weight: 600; color: var(--secondary);">
                <i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
            </span>
        </div>
    </nav>

    <div class="container-main">
        <div class="page-header mt-4">
            <div>
                <h2 style="color: var(--primary); font-weight: 800; margin-bottom: 5px;">Horarios de Clase</h2>
                <p style="color: var(--secondary); margin: 0;">Selecciona el tipo de consulta que deseas realizar</p>
            </div>
            <a href="reportes.php" class="btn-back">
                <i class='bx bx-left-arrow-alt'></i> Volver a Reportes
            </a>
        </div>
        
        <div class="row g-4 justify-content-center">
            <div class="col-12 col-md-6">
                <a href="consulta_horarios.php" class="selection-card">
                    <div class="icon-circle">
                        <i class="fas fa-users-rectangle"></i>
                    </div>
                    <h3>Consulta de Grupos</h3>
                    <p>Visualiza el horario completo asignado a un grupo específico (ej. 1°A, 3°B).</p>
                </a>
            </div>

            <div class="col-12 col-md-6">
                <a href="consulta_horario_maestro.php" class="selection-card">
                    <div class="icon-circle">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Horarios de Maestros</h3>
                    <p>Consulta la agenda de clases y disponibilidad de cada docente del plantel.</p>
                </a>
            </div>
        </div>
        
        <footer class="footer">
            <p>CECyTE Santa Catarina — Sistema de Gestión Académica &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>