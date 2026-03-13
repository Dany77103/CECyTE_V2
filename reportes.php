
<?php
// Incluir configuración común - usando ruta relativa segura
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    die("Error: Archivo de configuraci&oacute;n no encontrado en: " . $configPath);
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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="styles.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed: 80px;
            
            /* PALETA DE 4 TONOS VERDE - INSPIRADA EN CECYTE */
            --verde-oscuro: <?php echo VERDE_OSCURO; ?>;
            --verde-principal: <?php echo VERDE_PRINCIPAL; ?>;
            --verde-medio: <?php echo VERDE_MEDIO; ?>;
            --verde-claro: <?php echo VERDE_CLARO; ?>;
            --verde-brillante: <?php echo VERDE_BRILLANTE; ?>;
            
            --text-color: #ecf0f1;
            --hover-color: #4caf50;
        }
        
        .main-container {
            display: flex;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
        }
        
        /* Sidebar mejorado - Tono verde oscuro */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--verde-principal), #1b5e20);
            color: var(--text-color);
            position: fixed;
            height: 100vh;
            overflow-y: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 3px 0 15px rgba(26, 83, 48, 0.2);
			 display: flex;
			flex-direction: column;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }
        
        .sidebar-header {
            padding: 20px 15px;
            background-color: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(90deg, var(--verde-oscuro), #2e7d32);
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed .logo-name {
            opacity: 0;
            width: 0;
        }
        
        #btn-toggle {
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            background: rgba(255,255,255,0.1);
            padding: 8px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        #btn-toggle:hover {
            background: var(--verde-medio);
            transform: rotate(90deg);
        }
        
        .sidebar-menu {
            padding: 20px 0;
			flex-grow: 1;
			overflow-y: auto;
        }
        
        .nav-item {
            list-style: none;
            margin: 5px 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            border-left: 4px solid transparent;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: linear-gradient(90deg, rgba(76, 175, 80, 0.3), rgba(139, 195, 74, 0.2));
            border-left-color: var(--verde-brillante);
            color: white;
        }
        
        .nav-link i {
            font-size: 1.3rem;
            min-width: 40px;
            text-align: center;
        }
        
        .link-text {
            margin-left: 10px;
            white-space: nowrap;
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .link-text {
            opacity: 0;
            width: 0;
        }
        
        .tooltip {
            position: absolute;
            left: calc(var(--sidebar-collapsed) + 10px);
            background: var(--verde-oscuro);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1001;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .sidebar.collapsed .nav-link:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        .user-section {
            position: relative;
            
            width: 100%;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
			flex-shrink: 0;
        }
        
        .user-link {
            display: flex;
            align-items: center;
            color: var(--text-color);
            text-decoration: none;
            padding: 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .user-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        /* Contenido principal */
        .content-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
            min-height: 100vh;
        }
        
        .sidebar.collapsed ~ .content-wrapper {
            margin-left: var(--sidebar-collapsed);
        }
        
        /* Header fijo */
        .main-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(46, 125, 50, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 3px solid var(--verde-medio);
        }
        
        /* Estilos para las tarjetas de reportes */
        .reports-container {
            padding: 30px;
        }
        
        .page-title {
            color: var(--verde-oscuro);
            margin-bottom: 30px;
            font-weight: 700;
            border-bottom: 3px solid var(--verde-claro);
            padding-bottom: 15px;
        }
        
        .card-report {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
            border-top: 5px solid;
            border: 1px solid rgba(139, 195, 74, 0.2);
            background: white;
        }
        
        .card-report:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(46, 125, 50, 0.15);
            border-color: var(--verde-medio);
        }
        
        .card-report .card-body {
            padding: 25px;
            text-align: center;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--verde-oscuro);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
		.search-box {
			display: flex;
			align-items: center;
			padding: 12px 20px;
		}

		.search-box input {
			background: transparent;
			border: none;
			color: white;
			margin-left: 10px;
			width: 100%;
		}

		.search-box input::placeholder {
			color: rgba(255,255,255,0.7);
		}

		.sidebar.collapsed .search-box input {
			display: none;
		}

		.sidebar.collapsed .search-box {
			justify-content: center;
		}


        .card-text {
            color: #5d6d5f;
            margin-bottom: 20px;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .btn-report {
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            min-width: 140px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-report:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.2);
            text-decoration: none;
        }
        
        /* Colores específicos para cada tarjeta usando la paleta verde */
        .card-alumnos {
            border-top-color: var(--verde-oscuro);
        }
        
        .card-maestros {
            border-top-color: var(--verde-principal);
        }
        
        .card-calificaciones {
            border-top-color: var(--verde-medio);
        }
        
        .card-academico-maestros {
            border-top-color: var(--verde-claro);
        }
        
        .card-horarios {
            border-top-color: var(--verde-oscuro);
        }
        
        .card-asistencias {
            border-top-color: #66bb6a;
        }
        
        .card-qr {
            border-top-color: var(--verde-principal);
        }
        
        .card-materias {
            border-top-color: var(--verde-medio);
        }
        
        .card-fotos {
            border-top-color: var(--verde-claro);
        }
        
        /* Efecto para íconos en tarjetas */
        .card-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .card-alumnos .card-icon {
            color: var(--verde-oscuro);
        }
        
        .card-maestros .card-icon {
            color: var(--verde-principal);
        }
        
        .card-calificaciones .card-icon {
            color: var(--verde-medio);
        }
        
        .card-academico-maestros .card-icon {
            color: var(--verde-claro);
        }
        
        .card-horarios .card-icon {
            color: var(--verde-oscuro);
        }
        
        .card-asistencias .card-icon {
            color: #66bb6a;
        }
        
        .card-qr .card-icon {
            color: var(--verde-principal);
        }
        
        .card-materias .card-icon {
            color: var(--verde-medio);
        }
        
        .card-fotos .card-icon {
            color: var(--verde-claro);
        }
        
        /* Botones personalizados con la paleta verde */
        .btn-primary {
            background-color: var(--verde-oscuro);
            border-color: var(--verde-oscuro);
        }
        
        .btn-primary:hover {
            background-color: #144028;
            border-color: #144028;
        }
        
        .btn-success {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        
        .btn-success:hover {
            background-color: #256028;
            border-color: #256028;
        }
        
        .btn-secondary {
            background-color: var(--verde-medio);
            border-color: var(--verde-medio);
        }
        
        .btn-warning {
            background-color: var(--verde-claro);
            border-color: var(--verde-claro);
            color: #333;
        }
        
        .btn-warning:hover {
            background-color: #7cb342;
            border-color: #7cb342;
            color: #333;
        }
        
        .btn-dark {
            background-color: var(--verde-oscuro);
            border-color: var(--verde-oscuro);
        }
        
        .btn-light {
            background-color: #a5d6a7;
            border-color: #a5d6a7;
            color: #333;
        }
        
        .btn-light:hover {
            background-color: #81c784;
            border-color: #81c784;
            color: #333;
        }
        
        .btn-danger {
            background-color: #66bb6a;
            border-color: #66bb6a;
        }
        
        /* Badge verde para el header */
        .badge.bg-success {
            background-color: var(--verde-principal) !important;
        }
        
        /* Dropdown con tonos verdes */
        .dropdown-menu {
            border-color: rgba(139, 195, 74, 0.2);
        }
        
        .dropdown-item:hover {
            background-color: #f1f8e9;
            color: var(--verde-oscuro);
        }
        
        /* Footer verde */
        footer {
            background: linear-gradient(90deg, var(--verde-oscuro), var(--verde-principal)) !important;
            color: white;
            margin-top: 40px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: var(--sidebar-collapsed);
            }
            
            .sidebar:not(.collapsed) {
                width: var(--sidebar-width);
            }
            
            .content-wrapper {
                margin-left: var(--sidebar-collapsed);
            }
            
            .sidebar:not(.collapsed) ~ .content-wrapper {
                margin-left: var(--sidebar-width);
            }
            
            .reports-container {
                padding: 15px;
            }
            
            .card-title {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn-report {
                min-width: 120px;
                padding: 8px 20px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .card-report .card-body {
                padding: 20px 15px;
            }
            
            .btn-report {
                width: 100%;
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <div class="logo-name">SISTEMA DE REPORTES</div>
                    <i class='bx bx-menu' id="btn-toggle"></i>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <!-- Barra de búsqueda -->
                <li class="nav-item">
                    <div class="nav-link search-box">
                        <i class='bx bx-search'></i>
                        <input type="text" class="form-control" placeholder="Buscar..." id="sidebar-search">
                        <span class="tooltip">Buscar en el sistema</span>
                    </div>
                </li>
                
                <!-- Menú principal -->
                <li class="nav-item">
                    <a href="main.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'main.php' ? 'active' : ''; ?>">
                        <i class='bx bx-home-alt-2'></i>
                        <span class="link-text">Inicio</span>
                        <span class="tooltip">Inicio</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="registro.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'registro.php' ? 'active' : ''; ?>">
                        <i class='bx bx-file'></i>
                        <span class="link-text">Registro de Informaci&oacute;n</span>
                        <span class="tooltip">Registro de Informaci&oacute;n</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="reportes.php" class="nav-link active">
                        <i class='bx bx-pencil'></i>
                        <span class="link-text">Generar Reportes</span>
                        <span class="tooltip">Generar Reportes</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="estadisticas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'estadisticas.php' ? 'active' : ''; ?>">
                        <i class='bx bx-chart'></i>
                        <span class="link-text">Estad&iacute;sticas</span>
                        <span class="tooltip">Ver Estad&iacute;sticas</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="qr_asistencia.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'qr_asistencia.php' ? 'active' : ''; ?>">
                        <i class='bx bx-folder'></i>
                        <span class="link-text">Asistencia QR</span>
                        <span class="tooltip">Subir/Descargar Archivos</span>
                    </a>
                </li>
				
				
				<li class="nav-item">
                    <a href="seleccionar_clase.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'seleccionar_clase.php' ? 'active' : ''; ?>">
                        <i class='bx bx-folder'></i>
                        <span class="link-text">Seleccionar Clase</span>
                        <span class="tooltip">Selecciona la clase de alumnos</span>
                    </a>
                </li>
				
                
                <li class="nav-item">
                    <a href="updo.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'updo.php' ? 'active' : ''; ?>">
                        <i class='bx bx-folder'></i>
                        <span class="link-text">Archivos</span>
                        <span class="tooltip">Subir/Descargar Archivos</span>
                    </a>
                </li>
                
                <!-- Separador -->
                <li class="nav-item my-4">
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 0 20px;">
                </li>
                
                <!-- Opciones adicionales -->
                <li class="nav-item">
                    <a href="configuracion.php" class="nav-link">
                        <i class='bx bx-cog'></i>
                        <span class="link-text">Configuraci&oacute;n</span>
                        <span class="tooltip">Configuraci&oacute;n del Sistema</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="perfil.php" class="nav-link">
                        <i class='bx bx-user'></i>
                        <span class="link-text">Mi Perfil</span>
                        <span class="tooltip">Mi Perfil de Usuario</span>
                    </a>
                </li>
            </ul>
            
            <!-- Sección de usuario -->
            <div class="user-section">
                <a href="logout.php" class="user-link">
                    <i class='bx bx-log-out-circle' style="font-size: 1.5rem;"></i>
                    <span class="link-text">Cerrar Sesi&oacute;n</span>
                    <span class="tooltip">Cerrar Sesi&oacute;n</span>
                </a>
            </div>
        </aside>
        
        <!-- Contenido principal -->
        <div class="content-wrapper">
            <!-- Header -->
            <header class="main-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sistema de Reportes - CECyTE</h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-3">SGA-CECyTE Santa Catarina N.L.</span>
                        <div class="dropdown">
                            <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class='bx bx-user-circle'></i> <?php echo $_SESSION['username'] ?? 'Usuario'; ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="configuracion.php">Configuraci&oacute;n</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php">Cerrar Sesi&oacute;n</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Contenido de la página -->
            <main class="reports-container">
                <h1 class="page-title">
                    <i class='bx bx-pencil'></i> Sistema de Reportes
                </h1>
                
                <div class="row g-4">
                    <!-- Tarjeta para Reporte de Alumnos -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-report card-alumnos">
                            <div class="card-body">
                                <i class="fas fa-users card-icon"></i>
                                <h5 class="card-title">
                                    <i class="fas fa-users"></i> Reporte de Alumnos
                                </h5>
                                <p class="card-text">Genera un reporte detallado de los alumnos con informaci&oacute;n completa y filtros avanzados.</p>
                                <a href="lista_alumnos.php" class="btn btn-primary btn-report">
                                    Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta para Reporte de Maestros -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-report card-maestros">
                            <div class="card-body">
                                <i class="fas fa-chalkboard-teacher card-icon"></i>
                                <h5 class="card-title">
                                    <i class="fas fa-chalkboard-teacher"></i> Reporte de Maestros
                                </h5>
                                <p class="card-text">Genera un reporte detallado de los maestros con su informaci&oacute;n profesional y acad&eacute;mica.</p>
                                <a href="lista_maestros.php" class="btn btn-success btn-report">
                                    Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta para Reporte de Calificaciones -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-report card-calificaciones">
                            <div class="card-body">
                                <i class="fas fa-check-circle card-icon"></i>
                                <h5 class="card-title">
                                    <i class="fas fa-check-circle"></i> Reporte de Calificaciones
                                </h5>
                                <p class="card-text">Genera un reporte detallado de las calificaciones por grupo, materia y periodo.</p>
                                <a href="lista_calificaciones.php" class="btn btn-secondary btn-report">
                                    Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta para Reporte Academico de Maestros -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-report card-academico-maestros">
                            <div class="card-body">
                                <i class="fas fa-briefcase card-icon"></i>
                                <h5 class="card-title">
                                    <i class="fas fa-briefcase"></i> Reporte Acad&eacute;mico de Maestros
                                </h5>
                                <p class="card-text">Genera un reporte detallado acad&eacute;mico de maestros con su formaci&oacute;n y especialidades.</p>
                                <a href="lista_datos_academicos.php" class="btn btn-warning btn-report">
                                    Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta para Reporte de Horarios -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-report card-horarios">
                            <div class="card-body">
                                <i class="fas fa-calendar-alt card-icon"></i>
                                <h5 class="card-title">
                                    <i class="fas fa-calendar-alt"></i> Reporte de Horarios
                                </h5>
                                <p class="card-text">Genera un reporte detallado de los horarios de clases por grupo y maestro.</p>
                                <a href="lista_horarios.php" class="btn btn-dark btn-report">
                                    Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta para Reporte de Asistencias -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-report card-asistencias">
                            <div class="card-body">
                                <i class="fas fa-clipboard-list card-icon"></i>
                                <h5 class="card-title">
                                    <i class="fas fa-clipboard-list"></i> Reporte de Asistencias
                                </h5>
                                <p class="card-text">Genera un reporte detallado de asistencias de alumnos y maestros por periodo.</p>
                                <a href="lista_asistencias.php" class="btn btn-light btn-report">
                                    Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta para Sistema de Asistencia QR -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-report card-qr">
                            <div class="card-body">
                                <i class="fas fa-qrcode card-icon"></i>
                                <h5 class="card-title">
                                    <i class="fas fa-qrcode"></i> Sistema Asistencia QR
                                </h5>
                                <p class="card-text">Registro de asistencia mediante c&oacute;digos QR para alumnos con entrada y salida autom&aacute;tica.</p>
                                <a href="qr_asistencia.php" class="btn btn-primary btn-report">
                                    <i class="fas fa-external-link-alt me-2"></i> Acceder al Sistema
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta para Reporte de Materias -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-report card-materias">
                            <div class="card-body">
                                <i class="fas fa-book card-icon"></i>
                                <h5 class="card-title">
                                    <i class="fas fa-book"></i> Reporte de Materias
                                </h5>
                                <p class="card-text">Genera un reporte detallado de materias por carrera, semestre y horas cr&eacute;dito.</p>
                                <a href="lista_materias.php" class="btn btn-danger btn-report">
                                    Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta para Reporte de Fotos Alumnos -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-report card-fotos">
                            <div class="card-body">
                                <i class="fas fa-camera card-icon"></i>
                                <h5 class="card-title">
                                    <i class="fas fa-camera"></i> Reporte de Fotos Alumnos
                                </h5>
                                <p class="card-text">Genera un reporte detallado de fotos de alumnos con informaci&oacute;n visual y datos.</p>
                                <a href="lista_fotos.php" class="btn btn-success btn-report">
                                    Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- Footer -->
            <footer class="bg-success text-white text-center py-3 mt-5">
                <div class="container">
                    <p class="mb-1">SGA-CECyTE SANTA CATARINA N.L.</p>
                    <p class="mb-0">© <?php echo date("Y"); ?> Sistema de Gesti&oacute;n Acad&eacute;mica. Todos los derechos reservados.</p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Toggle del sidebar
        document.getElementById('btn-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            
            // Cambiar icono
            const icon = this;
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.remove('bx-menu');
                icon.classList.add('bx-menu-alt-right');
            } else {
                icon.classList.remove('bx-menu-alt-right');
                icon.classList.add('bx-menu');
            }
        });
        
        // Resaltar elemento activo en sidebar
        document.querySelectorAll('.sidebar-menu .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.sidebar-menu .nav-link').forEach(item => {
                    item.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
        
        // Buscar en el sidebar
        document.getElementById('sidebar-search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.nav-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm) || searchTerm === '') {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Auto-colapsar en móviles
        function handleResize() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                document.getElementById('btn-toggle').classList.remove('bx-menu');
                document.getElementById('btn-toggle').classList.add('bx-menu-alt-right');
            } else {
                sidebar.classList.remove('collapsed');
                document.getElementById('btn-toggle').classList.remove('bx-menu-alt-right');
                document.getElementById('btn-toggle').classList.add('bx-menu');
            }
        }
        
        window.addEventListener('resize', handleResize);
        window.addEventListener('load', handleResize);
        
        // Efecto hover mejorado para tarjetas
        document.querySelectorAll('.card-report').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
                this.style.boxShadow = '0 15px 35px rgba(46, 125, 50, 0.15)';
                this.style.borderColor = 'var(--verde-medio)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 10px 30px rgba(46, 125, 50, 0.08)';
                this.style.borderColor = 'rgba(139, 195, 74, 0.2)';
            });
        });
        
        // Animación para las tarjetas al cargar la página
        window.addEventListener('load', function() {
            const cards = document.querySelectorAll('.card-report');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 100);
            });
        });
        
        // Efecto pulsante para botones al hacer clic
        document.querySelectorAll('.btn-report').forEach(button => {
            button.addEventListener('click', function(e) {
                // Solo aplicar efecto si no es un enlace externo o especial
                if (!this.classList.contains('no-pulse')) {
                    this.classList.add('active');
                    setTimeout(() => {
                        this.classList.remove('active');
                    }, 300);
                }
            });
        });
    </script>
</body>
</html>
