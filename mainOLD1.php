<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CECyTE - Sistema de Gesti&oacute;n Acad&eacute;mica</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="stylesII.css">
    
    <style>
        /* PALETA DE COLORES VERDE - 4 TONOS */
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed: 80px;
            
            /* Tono 1: Verde Principal (Más oscuro) */
            --verde-principal: #1b5e20;  /* Verde forestal oscuro */
            /* Tono 2: Verde Secundario */
            --verde-secundario: #2e7d32; /* Verde bosque */
            /* Tono 3: Verde Acento */
            --verde-acento: #4caf50;     /* Verde esmeralda */
            /* Tono 4: Verde Claro */
            --verde-claro: #81c784;      /* Verde menta claro */
            
            --text-color: #ecf0f1;
            --text-color-dark: #2c3e50;
            --bg-color-light: #f8f9fa;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        .main-container {
            display: flex;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        /* Sidebar con tono 1 (Verde Principal) */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--verde-principal), var(--verde-secundario));
            color: var(--text-color);
            position: fixed;
            height: 100vh;
            overflow-y: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 3px 0 15px rgba(0,0,0,0.1);
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
            background: var(--verde-acento);
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
            background: rgba(255,255,255,0.1);
            border-left-color: var(--verde-claro);
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
            background: var(--verde-principal);
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


        .user-section {
            position: relative;
            margin-top: auto;
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
            background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e9 100%);
        }
        
        .sidebar.collapsed ~ .content-wrapper {
            margin-left: var(--sidebar-collapsed);
        }
        
        /* Header fijo con tono 2 (Verde Secundario) */
        .main-header {
            background: linear-gradient(90deg, var(--verde-secundario), var(--verde-acento));
            color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        /* Botones con tonos de verde */
        .btn-outline-verde {
            border-color: var(--verde-acento);
            color: var(--verde-acento);
        }
        
        .btn-outline-verde:hover {
            background-color: var(--verde-acento);
            color: white;
        }
        
        /* Badges con tonos de verde */
        .badge-verde-principal {
            background-color: var(--verde-principal);
            color: white;
        }
        
        .badge-verde-secundario {
            background-color: var(--verde-secundario);
            color: white;
        }
        
        .badge-verde-acento {
            background-color: var(--verde-acento);
            color: white;
        }
        
        .badge-verde-claro {
            background-color: var(--verde-claro);
            color: var(--text-color-dark);
        }
        
        /* Contenido de la reseña histórica con tono 4 (Verde Claro) */
        .historia-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border-left: 5px solid var(--verde-claro);
        }
        
        .historia-header {
            border-bottom: 2px solid #e8f5e9;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .historia-title {
            color: var(--verde-principal);
            font-weight: 600;
        }
        
        .historia-content {
            line-height: 1.8;
            color: #555;
            text-align: justify;
        }
        
        /* Footer con tono 1 (Verde Principal) */
        .footer-verde {
            background: linear-gradient(90deg, var(--verde-principal), var(--verde-secundario));
            color: white;
        }
        
        /* Tarjetas con bordes de verde */
        .card-verde {
            border-top: 4px solid var(--verde-acento);
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        
        .card-verde:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.2);
        }
        
        /* Elementos de formulario con verde */
        .form-control:focus {
            border-color: var(--verde-claro);
            box-shadow: 0 0 0 0.25rem rgba(129, 199, 132, 0.25);
        }
        
        /* Dropdown menu con tonos de verde */
        .dropdown-menu {
            border-color: var(--verde-claro);
        }
        
        .dropdown-item:hover {
            background-color: #e8f5e9;
            color: var(--verde-principal);
        }
        
        /* Alertas con tonos de verde */
        .alert-verde {
            background-color: #e8f5e9;
            border-color: var(--verde-claro);
            color: var(--verde-principal);
        }
        
        /* Progress bars con verde */
        .progress-verde {
            background-color: var(--verde-claro);
        }
        
        .progress-verde .progress-bar {
            background-color: var(--verde-acento);
        }
        
        /* Tablas con acentos verdes */
        .table-verde thead {
            background-color: var(--verde-secundario);
            color: white;
        }
        
        .table-verde tbody tr:hover {
            background-color: #e8f5e9;
        }
        
        /* Navegación con pestañas verdes */
        .nav-tabs-verde .nav-link {
            color: var(--verde-principal);
        }
        
        .nav-tabs-verde .nav-link.active {
            background-color: var(--verde-acento);
            color: white;
            border-color: var(--verde-acento);
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
            
            .historia-container {
                margin: 15px;
                padding: 20px;
            }
        }
        
        /* Animaciones adicionales */
        @keyframes pulse-verde {
            0% {
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(76, 175, 80, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
            }
        }
        
        .pulse-verde {
            animation: pulse-verde 2s infinite;
        }
        
        /* Fondo para secciones importantes */
        .bg-verde-claro {
            background-color: #e8f5e9;
        }
        
        /* Texto con colores de la paleta */
        .text-verde-principal {
            color: var(--verde-principal);
        }
        
        .text-verde-secundario {
            color: var(--verde-secundario);
        }
        
        .text-verde-acento {
            color: var(--verde-acento);
        }
        
        .text-verde-claro {
            color: var(--verde-claro);
        }
        
        /* Botones con gradientes de verde */
        .btn-verde-principal {
            background: linear-gradient(135deg, var(--verde-principal), var(--verde-secundario));
            color: white;
            border: none;
        }
        
        .btn-verde-secundario {
            background: linear-gradient(135deg, var(--verde-secundario), var(--verde-acento));
            color: white;
            border: none;
        }
        
        .btn-verde-acento {
            background: linear-gradient(135deg, var(--verde-acento), var(--verde-claro));
            color: white;
            border: none;
        }
        
        .btn-verde-claro {
            background: linear-gradient(135deg, var(--verde-claro), #e8f5e9);
            color: var(--verde-principal);
            border: none;
        }
        
        /* Efectos hover para botones */
        .btn-verde-principal:hover,
        .btn-verde-secundario:hover,
        .btn-verde-acento:hover,
        .btn-verde-claro:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar mejorado -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <div class="logo-name">SISTEMA DE GESTION ACADEMICA</div>
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
                        <span class="link-text">Registro de Información</span>
                        <span class="tooltip">Registro de Información</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="reportes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>">
                        <i class='bx bx-pencil'></i>
                        <span class="link-text">Generar Reportes</span>
                        <span class="tooltip">Generar Reportes</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="estadisticas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'estadisticas.php' ? 'active' : ''; ?>">
                        <i class='bx bx-chart'></i>
                        <span class="link-text">Estadísticas</span>
                        <span class="tooltip">Ver Estadísticas</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="qr_asistencia.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'qr_asistencia.php' ? 'active' : ''; ?>">
                        <i class='bx bx-qr-scan'></i>
                        <span class="link-text">Asistencia QR</span>
                        <span class="tooltip">Sistema de Asistencia QR</span>
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
                        <span class="link-text">Configuración</span>
                        <span class="tooltip">Configuración del Sistema</span>
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
                    <span class="link-text">Cerrar Sesión</span>
                    <span class="tooltip">Cerrar Sesión</span>
                </a>
            </div>
        </aside>
        
        <!-- Contenido principal -->
        <div class="content-wrapper">
            <!-- Header -->
            <header class="main-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Bienvenido, <?php echo $_SESSION['username'] ?? 'Usuario'; ?></h5>
                    <div class="d-flex align-items-center">
                        <span class="badge badge-verde-claro me-3">SGA-CECyTE Santa Catarina N.L.</span>
                        <div class="dropdown">
                            <button class="btn btn-outline-verde btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class='bx bx-user-circle'></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Contenido de la página -->
            <main class="container-fluid py-4">
                <div class="historia-container">
                    <div class="historia-header">
                        <h2 class="historia-title">Reseña Histórica - CECyTE Nuevo León</h2>
                    </div>
                    <div class="historia-content">
                        <p>
                            El Colegio de Estudios Científicos y Tecnológicos del Estado de Nuevo León (CECyTE NL) se creó el 18 de agosto de 1993, a través de un acuerdo de colaboración entre la secretaría de educación pública del gobierno federal, en representación del Dr. Ernesto Zedillo Ponce de León, y el Gobierno de Nuevo León, en representación del Lic. Sócrates Uauhtémoc Rizzo García, presidente del Tribunal Constitucional.
                        </p>
                        <p>
                            Este acuerdo se ratificó mediante el decreto de creación 287, emitido el 11 de mayo de 1994, y modificado con el decreto 340 el 19 de mayo de 2003. En sus comienzos, el CECyTE NL se fundó como una nueva alternativa de educación de nivel medio superior en la región, proporcionando servicios en cuatro establecimientos situados en los municipios de Apodaca, García, Linares y Marín.
                        </p>
                        <p>
                            El programa educativo local contemplaba tres profesiones técnicas: administración, electrónica y programación. El Colegio implementó el bachillerato general conocido como Educación Media Superior a Distancia (EMSAD), comenzando con un establecimiento en el municipio de Lampazos de Naranjo, N.L.
                        </p>
                        <p>
                            En la actualidad, el CECyTE NL dispone de 17 establecimientos que imparten el Bachillerato Tecnológico con 20 áreas técnicas autorizadas, junto con 17 Centros EMSAD que ofrecen educación remota. El Colegio se ha enfocado en potencializar sus índices estadísticos fundamentales, tales como la eficiencia para terminal con el abandono escolar y la reprobación, además de reforzar la formación de los profesores, el trabajo en equipo, la educación dual, la electromovilidad, la inclusión, el crecimiento socioemocional y la salud integral de la comunidad educativa.
                        </p>
                    </div>
                    
                    <!-- Tarjetas informativas con los colores de la paleta -->
                    <div class="row mt-5">
                        <div class="col-md-3 mb-4">
                            <div class="card card-verde h-100 text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-verde-principal">
                                        <i class='bx bxs-graduation'></i> Misión
                                    </h5>
                                    <p class="card-text">Formar técnicos profesionales a través de un bachillerato tecnológico de calidad.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card card-verde h-100 text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-verde-secundario">
                                        <i class='bx bxs-bulb'></i> Visión
                                    </h5>
                                    <p class="card-text">Ser la mejor opción de educación media superior tecnológica en Nuevo León.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card card-verde h-100 text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-verde-acento">
                                        <i class='bx bxs-star'></i> Valores
                                    </h5>
                                    <p class="card-text">Excelencia, responsabilidad, honestidad, respeto y trabajo en equipo.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card card-verde h-100 text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-verde-claro">
                                        <i class='bx bxs-compass'></i> Compromiso
                                    </h5>
                                    <p class="card-text">Educación integral para el desarrollo de competencias profesionales.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sección de estadísticas rápidas -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card card-verde">
                            <div class="card-header bg-verde-claro">
                                <h5 class="mb-0 text-verde-principal">
                                    <i class='bx bx-stats'></i> Estadísticas del Sistema
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-3">
                                        <div class="p-3 bg-verde-claro rounded">
                                            <h3 class="text-verde-principal mb-1">1,250</h3>
                                            <p class="text-verde-secundario mb-0">Alumnos Activos</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="p-3 bg-verde-claro rounded">
                                            <h3 class="text-verde-principal mb-1">85</h3>
                                            <p class="text-verde-secundario mb-0">Maestros</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="p-3 bg-verde-claro rounded">
                                            <h3 class="text-verde-principal mb-1">24</h3>
                                            <p class="text-verde-secundario mb-0">Grupos</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="p-3 bg-verde-claro rounded">
                                            <h3 class="text-verde-principal mb-1">98%</h3>
                                            <p class="text-verde-secundario mb-0">Satisfacción</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- Footer -->
            <footer class="footer-verde text-white text-center py-3 mt-4">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-md-start mb-3 mb-md-0">
                            <p class="mb-1">
                                <i class='bx bxs-map'></i> CECyTE SANTA CATARINA N.L.
                            </p>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <p class="mb-0">
                                <i class='bx bxs-phone'></i> Contacto: (81) 1234-5678
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <p class="mb-0">
                                <i class='bx bxs-copyright'></i> <?php echo date("Y"); ?> Sistema de Gesti&oacute;n Acad&eacute;mica
                            </p>
                        </div>
                    </div>
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
        
        // Resaltar elemento activo
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.nav-link').forEach(item => {
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
        
        // Efecto de animación para las tarjetas
        document.querySelectorAll('.card-verde').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 5px 15px rgba(76, 175, 80, 0.2)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            });
        });
        
        // Mostrar la fecha actual
        const fechaActual = new Date();
        const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const fechaFormateada = fechaActual.toLocaleDateString('es-ES', opciones);
        
        // Puedes agregar esto donde quieras mostrar la fecha
        // console.log('Fecha actual:', fechaFormateada);
    </script>
</body>
</html>