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
    <title>CECYTE - Sistema de Reportes</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --verde-oscuro: <?php echo VERDE_OSCURO; ?>;
            --verde-principal: <?php echo VERDE_PRINCIPAL; ?>;
            --verde-medio: <?php echo VERDE_MEDIO; ?>;
            --verde-claro: <?php echo VERDE_CLARO; ?>;
            --verde-brillante: <?php echo VERDE_BRILLANTE; ?>;
        }
        
        body {
            background: linear-gradient(135deg, #f8faf8 0%, #eef5ee 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Estilizado */
        .main-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            border-bottom: 4px solid var(--verde-principal);
        }

        .navbar-brand-custom {
            font-weight: 700;
            color: var(--verde-oscuro);
            text-decoration: none;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Contenedor de Reportes */
        .reports-wrapper {
            flex: 1;
            padding: 40px 20px;
        }

        .page-title {
            color: var(--verde-oscuro);
            font-weight: 800;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--verde-principal);
            border-radius: 2px;
        }

        /* Tarjetas de Reporte Mejoradas */
        .card-report {
            border: none;
            border-radius: 16px;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-top: 5px solid var(--verde-medio);
        }

        .card-report:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(46, 125, 50, 0.12);
        }

        .card-body {
            padding: 30px;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 20px;
            background: #f0f7f0;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            display: inline-block;
            transition: 0.3s;
        }

        .card-report:hover .card-icon {
            background: var(--verde-principal);
            color: white !important;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--verde-oscuro);
            margin-bottom: 15px;
        }

        .card-text {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }

        .btn-report {
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        /* Colores dinámicos por categoría */
        .report-alumnos { border-top-color: #2e7d32; }
        .report-alumnos .card-icon { color: #2e7d32; }
        
        .report-maestros { border-top-color: #388e3c; }
        .report-maestros .card-icon { color: #388e3c; }
        
        .report-calificaciones { border-top-color: #43a047; }
        .report-calificaciones .card-icon { color: #43a047; }

        /* Botón de volver */
        .btn-back {
            background: white;
            color: var(--verde-oscuro);
            border: 2px solid var(--verde-claro);
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: 0.3s;
        }

        .btn-back:hover {
            background: var(--verde-claro);
            color: white;
        }

        footer {
            background: white;
            padding: 20px 0;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <a href="main.php" class="navbar-brand-custom">
                <i class='bx bxs-school'></i>
                <span>SGA CECyTE</span>
            </a>
            
            <div class="d-flex align-items-center gap-3">
                <span class="d-none d-md-inline badge bg-light text-success border">Santa Catarina N.L.</span>
                <div class="dropdown">
                    <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <i class='bx bx-user-circle fs-4'></i> <?php echo $_SESSION['username'] ?? 'Admin'; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item" href="main.php"><i class='bx bx-home-alt me-2'></i>Inicio</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class='bx bx-log-out me-2'></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <main class="reports-wrapper container">
        
        <a href="main.php" class="btn btn-back">
            <i class='bx bx-left-arrow-alt'></i> Volver al Menú
        </a>

        <h2 class="page-title mb-4">Panel de Reportes Académicos</h2>
        
        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-report report-alumnos">
                    <div class="card-body">
                        <div>
                            <div class="card-icon"><i class="fas fa-users"></i></div>
                            <h5 class="card-title">Listado de Alumnos</h5>
                            <p class="card-text">Consulta la base de datos completa de estudiantes, filtrada por grupo o carrera.</p>
                        </div>
                        <a href="lista_alumnos.php" class="btn btn-success btn-report">Generar Reporte</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-report report-maestros">
                    <div class="card-body">
                        <div>
                            <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <h5 class="card-title">Cuerpo Docente</h5>
                            <p class="card-text">Información detallada de los profesores y sus asignaciones actuales.</p>
                        </div>
                        <a href="lista_maestros.php" class="btn btn-success btn-report">Generar Reporte</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-report report-calificaciones">
                    <div class="card-body">
                        <div>
                            <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                            <h5 class="card-title">Calificaciones</h5>
                            <p class="card-text">Reportes de rendimiento académico por parcial y promedios finales.</p>
                        </div>
                        <a href="lista_calificaciones.php" class="btn btn-success btn-report">Generar Reporte</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-report" style="border-top-color: var(--verde-oscuro);">
                    <div class="card-body">
                        <div>
                            <div class="card-icon" style="color: var(--verde-oscuro);"><i class="fas fa-qrcode"></i></div>
                            <h5 class="card-title">Asistencia QR</h5>
                            <p class="card-text">Historial de entradas y salidas registradas mediante el sistema automático.</p>
                        </div>
                        <a href="qr_asistencia.php" class="btn btn-success btn-report">Revisar Accesos</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-report" style="border-top-color: #8bc34a;">
                    <div class="card-body">
                        <div>
                            <div class="card-icon" style="color: #8bc34a;"><i class="fas fa-calendar-alt"></i></div>
                            <h5 class="card-title">Horarios de Clase</h5>
                            <p class="card-text">Consulta la distribución de horas por salón, maestro y grupo.</p>
                        </div>
                        <a href="lista_horarios.php" class="btn btn-success btn-report">Ver Horarios</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-report" style="border-top-color: #1b5e20;">
                    <div class="card-body">
                        <div>
                            <div class="card-icon" style="color: #1b5e20;"><i class="fas fa-book"></i></div>
                            <h5 class="card-title">Plan de Estudios</h5>
                            <p class="card-text">Listado de materias vigentes, créditos y carga horaria por semestre.</p>
                        </div>
                        <a href="lista_materias.php" class="btn btn-success btn-report">Ver Materias</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center">
        <div class="container">
            <p class="mb-0">© <?php echo date("Y"); ?> <b>CECyTE Santa Catarina N.L.</b> - Sistema de Gestión Académica</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>