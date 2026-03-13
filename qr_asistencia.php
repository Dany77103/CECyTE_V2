<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cecyte_sc";

try {
    $con = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

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
    <title>CECYTE - Sistema de Asistencia QR</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- QR Scanner CSS -->
    <link rel="stylesheet" href="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="styles.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed: 80px;
            
            /* PALETA DE 4 TONOS VERDE - INSPIRADA EN CECYTE */
            --verde-oscuro: #1a5330;      /* Verde más oscuro */
            --verde-principal: #2e7d32;   /* Verde principal */
            --verde-medio: #4caf50;       /* Verde medio */
            --verde-claro: #8bc34a;       /* Verde claro */
            --verde-brillante: #81c784;   /* Verde brillante para acentos */
            
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
        
        /* Estilos para el sistema de asistencia QR */
        .qr-container-main {
            padding: 30px;
        }
        
        .page-title {
            color: var(--verde-oscuro);
            margin-bottom: 30px;
            font-weight: 700;
            border-bottom: 3px solid var(--verde-claro);
            padding-bottom: 15px;
        }
        
        /* Tarjetas principales */
        .qr-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 30px;
            border: 1px solid rgba(139, 195, 74, 0.2);
            background: white;
        }
        
        .qr-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(46, 125, 50, 0.15);
            border-color: var(--verde-medio);
        }
        
        .card-header-qr {
            background: linear-gradient(90deg, var(--verde-oscuro), var(--verde-principal));
            color: white;
            padding: 20px;
            border-bottom: none;
        }
        
        /* Sección de escaneo */
        .scan-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.05);
            text-align: center;
            margin: 20px 0;
            border: 1px solid rgba(139, 195, 74, 0.1);
        }
        
        #qr-reader {
            width: 100%;
            margin: 20px 0;
        }
        
        #qr-reader-results {
            font-size: 1.1rem;
            margin-top: 20px;
            padding: 15px;
            background: #f1f8e9;
            border-radius: 8px;
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        /* Botones personalizados */
        .btn-qr {
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            min-width: 160px;
        }
        
        .btn-scan {
            background: linear-gradient(90deg, var(--verde-principal), var(--verde-oscuro));
            color: white;
        }
        
        .btn-scan:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
        }
        
        .btn-generate {
            background: linear-gradient(90deg, var(--verde-medio), var(--verde-principal));
            color: white;
        }
        
        .btn-generate:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .btn-export {
            background: linear-gradient(90deg, var(--verde-claro), #7cb342);
            color: #333;
        }
        
        .btn-export:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(139, 195, 74, 0.3);
            color: #333;
        }
        
        /* Botón principal en verde */
        .btn-primary {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        
        .btn-primary:hover {
            background-color: #256028;
            border-color: #256028;
        }
        
        /* Botón secundario */
        .btn-outline-primary {
            color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
            color: white;
        }
        
        /* Botón info */
        .btn-info {
            background-color: var(--verde-medio);
            border-color: var(--verde-medio);
        }
        
        /* Tarjetas de estadísticas */
        .stats-card-qr {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.05);
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        .stats-card-qr:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(46, 125, 50, 0.1);
        }
        
        .stats-number-qr {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--verde-principal);
            margin-bottom: 10px;
        }
        
        .stats-label-qr {
            color: #5d6d5f;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Tablas personalizadas */
        .table-qr {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.05);
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        .table-qr thead {
            background: var(--verde-oscuro);
            color: white;
        }
        
        .table-qr tbody tr {
            transition: background 0.3s ease;
        }
        
        .table-qr tbody tr:hover {
            background: #f1f8e9;
        }
        
        /* Alertas personalizadas */
        .alert-qr {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.05);
        }
        
        .alert-info {
            background-color: #e8f5e9;
            border-color: #c8e6c9;
            color: #2e7d32;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            border-color: #a5d6a7;
            color: #1a5330;
        }
        
        .alert-warning {
            background-color: #fff8e1;
            border-color: #ffecb3;
            color: #8d6e00;
        }
        
        .alert-danger {
            background-color: #ffebee;
            border-color: #ffcdd2;
            color: #c62828;
        }
        
        /* QR Preview */
        .qr-preview-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(46, 125, 50, 0.1);
            text-align: center;
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        /* Filtros */
        .filters-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.05);
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        /* Badge verde para el header */
        .badge.bg-success {
            background-color: var(--verde-principal) !important;
        }
        
        .badge.bg-warning {
            background-color: var(--verde-claro) !important;
            color: #333;
        }
        
        .badge.bg-primary {
            background-color: var(--verde-oscuro) !important;
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
            
            .qr-container-main {
                padding: 15px;
            }
            
            .btn-qr {
                min-width: 100%;
                margin-bottom: 10px;
            }
            
            .scan-container {
                padding: 15px;
            }
            
            .stats-number-qr {
                font-size: 2rem;
            }
        }
        
        /* Navegación interna */
        .qr-nav {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.05);
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        .qr-nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .qr-nav-item {
            flex: 1;
            min-width: 120px;
        }
        
        .qr-nav-link {
            display: block;
            padding: 10px 15px;
            text-align: center;
            background: #f1f8e9;
            border-radius: 5px;
            color: var(--verde-oscuro);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(139, 195, 74, 0.3);
        }
        
        .qr-nav-link:hover,
        .qr-nav-link.active {
            background: var(--verde-principal);
            color: white;
            border-color: var(--verde-principal);
        }
        
        /* Indicadores de estado */
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-entrada {
            background-color: var(--verde-principal);
        }
        
        .status-salida {
            background-color: var(--verde-claro);
        }
        
        .status-pendiente {
            background-color: #f44336;
        }
        
        /* Modal */
        .modal-header {
            background: linear-gradient(90deg, var(--verde-oscuro), var(--verde-principal));
            color: white;
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .qr-card {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Scanner personalizado */
        #qr-reader {
            border: 2px solid var(--verde-medio) !important;
            border-radius: 10px;
            overflow: hidden;
        }
        
        #qr-reader__scan_region {
            background-color: rgba(26, 83, 48, 0.1) !important;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <div class="logo-name">SISTEMA QR</div>
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
                    <a href="main.php" class="nav-link">
                        <i class='bx bx-home-alt-2'></i>
                        <span class="link-text">Inicio</span>
                        <span class="tooltip">Inicio</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="registro.php" class="nav-link">
                        <i class='bx bx-file'></i>
                        <span class="link-text">Registro</span>
                        <span class="tooltip">Registro de Informaci&oacute;n</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="reportes.php" class="nav-link">
                        <i class='bx bx-pencil'></i>
                        <span class="link-text">Reportes</span>
                        <span class="tooltip">Generar Reportes</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="estadisticas.php" class="nav-link">
                        <i class='bx bx-chart'></i>
                        <span class="link-text">Estad&iacute;sticas</span>
                        <span class="tooltip">Ver Estad&iacute;sticas</span>
                    </a>
                </li>
				
				
				<li class="nav-item">
                    <a href="seleccionar_clase.php" class="nav-link">
                        <i class='bx bx-chart'></i>
                        <span class="link-text">Seleccionar Clase</span>
                        <span class="tooltip">Selecciona la clase de alumnos</span>
                    </a>
                </li>
				
				                
                <li class="nav-item">
                    <a href="updo.php" class="nav-link">
                        <i class='bx bx-folder'></i>
                        <span class="link-text">Archivos</span>
                        <span class="tooltip">Subir/Descargar Archivos</span>
                    </a>
                </li>
                
                <!-- Separador -->
                <li class="nav-item my-4">
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 0 20px;">
                </li>
                
                <!-- Enlace activo para QR -->
                <li class="nav-item">
                    <a href="qr_asistencia.php" class="nav-link active">
                        <i class='bx bx-qr-scan'></i>
                        <span class="link-text">Asistencia QR</span>
                        <span class="tooltip">Sistema de Asistencia QR</span>
                    </a>
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
                    <h5 class="mb-0">Sistema de Asistencia QR - CECyTE</h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-3">CECyTE Santa Catarina N.L.</span>
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
            <main class="qr-container-main">
                <!-- Navegación interna -->
                <nav class="qr-nav">
                    <ul class="qr-nav-list">
                        <li class="qr-nav-item">
                            <a href="#escaneo" class="qr-nav-link active" onclick="mostrarSeccion('escaneo')">
                                <i class="fas fa-camera me-2"></i> Escanear
                            </a>
                        </li>
                        <li class="qr-nav-item">
                            <a href="#generador" class="qr-nav-link" onclick="mostrarSeccion('generador')">
                                <i class="fas fa-qrcode me-2"></i> Generar QR
                            </a>
                        </li>
                        <li class="qr-nav-item">
                            <a href="#registros" class="qr-nav-link" onclick="mostrarSeccion('registros')">
                                <i class="fas fa-history me-2"></i> Historial
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <!-- Alertas -->
                <div id="alertContainer"></div>
                
                <h1 class="page-title">
                    <i class='bx bx-qr-scan'></i> Sistema de Asistencia QR
                </h1>
                
                <!-- Sección de Escaneo -->
                <div class="qr-card" id="escaneo">
                    <div class="card-header-qr">
                        <h3 class="mb-0"><i class="fas fa-camera me-2"></i> Escanear C&oacute;digo QR</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="scan-container">
                                    <div id="qr-reader"></div>
                                    <div id="qr-reader-results"></div>
                                </div>
                                <div class="text-center mt-4">
                                    <button class="btn btn-qr btn-scan" id="startScanner">
                                        <i class="fas fa-play me-2"></i> Iniciar Esc&aacute;ner
                                    </button>
                                    <button class="btn btn-outline-success ms-2" id="stopScanner">
                                        <i class="fas fa-stop me-2"></i> Detener Esc&aacute;ner
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card-qr mb-4">
                                    <div class="stats-number-qr" id="totalHoy">0</div>
                                    <div class="stats-label-qr">Asistencias Hoy</div>
                                </div>
                                <div class="stats-card-qr mb-4">
                                    <div class="stats-number-qr" id="totalPendientes">0</div>
                                    <div class="stats-label-qr">Pendientes de Salida</div>
                                </div>
                                <div class="alert alert-info alert-qr">
                                    <h5><i class="fas fa-info-circle me-2"></i> Instrucciones:</h5>
                                    <ol class="mb-0">
                                        <li>Permite el acceso a la c&aacute;mara</li>
                                        <li>Coloca el c&oacute;digo QR frente a la c&aacute;mara</li>
                                        <li>El sistema registrar&aacute; autom&aacute;ticamente entrada/salida</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de Generación de QR -->
                <div class="qr-card" id="generador" style="display: none;">
                    <div class="card-header-qr">
                        <h3 class="mb-0"><i class="fas fa-qrcode me-2"></i> Generar C&oacute;digos QR</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="searchAlumno" placeholder="Buscar alumno por nombre o matrícula">
                                    <button class="btn btn-outline-success" type="button" id="btnBuscar">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-qr btn-generate" id="generateAllQR">
                                    <i class="fas fa-sync-alt me-2"></i> Generar Todos los QR
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-qr" id="alumnosTable">
                                <thead>
                                    <tr>
                                        <th>Matr&iacute;cula</th>
                                        <th>Nombre</th>
                                        <th>Grupo</th>
                                        <th>Estado QR</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="alumnosBody">
                                    <!-- Los datos se cargarán por AJAX -->
                                </tbody>
                            </table>
                        </div>

                        <!-- QR Preview Modal -->
                        <div class="modal fade" id="qrModal" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">C&oacute;digo QR del Alumno</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <div class="qr-preview-container mb-3">
                                            <div id="qrPreview"></div>
                                        </div>
                                        <div id="alumnoInfo"></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-primary" onclick="descargarQR()">
                                            <i class="fas fa-download me-2"></i> Descargar QR
                                        </button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de Historial -->
                <div class="qr-card" id="registros" style="display: none;">
                    <div class="card-header-qr">
                        <h3 class="mb-0"><i class="fas fa-history me-2"></i> Historial de Asistencias</h3>
                    </div>
                    <div class="card-body">
                        <div class="filters-container">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label for="fechaFiltro" class="form-label">Fecha</label>
                                    <input type="date" class="form-control" id="fechaFiltro" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="grupoFiltro" class="form-label">Grupo</label>
                                    <select class="form-select" id="grupoFiltro">
                                        <option value="">Todos los grupos</option>
                                        <!-- Opciones de grupos se cargarán por AJAX -->
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button class="btn btn-qr btn-export w-100" onclick="exportarExcel()">
                                        <i class="fas fa-file-excel me-2"></i> Exportar Excel
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-qr" id="asistenciasTable">
                                <thead>
                                    <tr>
                                        <th>Matr&iacute;cula</th>
                                        <th>Nombre</th>
                                        <th>Fecha</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="asistenciasBody">
                                    <!-- Los datos se cargarán por AJAX -->
                                </tbody>
                            </table>
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
    <!-- QR Scanner -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <!-- QR Code Generator -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <!-- Excel Export -->
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    
    <script>
        // Variables globales
        let html5QrCode;
        let qrGenerado = null;
        let alumnoActual = null;
        let seccionActual = 'escaneo';

        // Función para mostrar secciones
        function mostrarSeccion(seccion) {
            // Ocultar todas las secciones
            document.querySelectorAll('.qr-card').forEach(card => {
                card.style.display = 'none';
            });
            
            // Mostrar la sección seleccionada
            document.getElementById(seccion).style.display = 'block';
            
            // Actualizar navegación interna
            document.querySelectorAll('.qr-nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector(`.qr-nav-link[href="#${seccion}"]`).classList.add('active');
            
            // Actualizar variable de sección actual
            seccionActual = seccion;
            
            // Cargar datos específicos de la sección
            if (seccion === 'generador') {
                cargarAlumnos();
            } else if (seccion === 'registros') {
                cargarHistorial();
            }
        }

        // Inicializar el escáner QR
        function initScanner() {
            html5QrCode = new Html5Qrcode("qr-reader");
            
            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                // Detener el escáner temporalmente
                stopScanner();
                
                // Procesar el código QR
                procesarQR(decodedText);
                
                // Reiniciar después de 2 segundos
                setTimeout(() => {
                    startScanner();
                }, 2000);
            };
            
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            };
            
            return { qrCodeSuccessCallback, config };
        }

        // Iniciar escáner
        function startScanner() {
            const { qrCodeSuccessCallback, config } = initScanner();
            
            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    const cameraId = devices[0].id;
                    html5QrCode.start(
                        cameraId,
                        config,
                        qrCodeSuccessCallback,
                        error => {
                            console.error(error);
                        }
                    );
                    document.getElementById('qr-reader-results').innerHTML = '<div class="alert alert-info">Esc&aacute;ner iniciado. Acerca el c&oacute;digo QR a la c&aacute;mara.</div>';
                } else {
                    showAlert('No se encontraron c&aacute;maras disponibles', 'danger');
                }
            }).catch(err => {
                showAlert('Error al acceder a la c&aacute;mara: ' + err, 'danger');
            });
        }

        // Detener escáner
        function stopScanner() {
            if (html5QrCode) {
                html5QrCode.stop().then(ignore => {
                    document.getElementById('qr-reader-results').innerHTML = '<div class="alert alert-warning">Esc&aacute;ner detenido.</div>';
                }).catch(err => {
                    console.error("Error al detener esc&aacute;ner:", err);
                });
            }
        }

        // Procesar código QR escaneado
        function procesarQR(codigoQR) {
            document.getElementById('qr-reader-results').innerHTML = '<div class="alert alert-info">Procesando c&oacute;digo QR...</div>';
            
            $.ajax({
                url: 'procesar_qr.php',
                type: 'POST',
                data: {
                    codigo_qr: codigoQR,
                    action: 'registrar'
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        showAlert(data.message, 'success');
                        actualizarEstadisticas();
                        // Si estamos en la sección de registros, actualizar la tabla
                        if (seccionActual === 'registros') {
                            cargarHistorial();
                        }
                    } else {
                        showAlert(data.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('Error al conectar con el servidor', 'danger');
                }
            });
        }

        // Mostrar alerta
        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show alert-qr" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('#alertContainer').html(alertHtml);
            
            // Auto-eliminar alerta después de 5 segundos
            setTimeout(() => {
                $('#alertContainer').empty();
            }, 5000);
        }

        // Cargar lista de alumnos
        function cargarAlumnos(busqueda = '') {
            $.ajax({
                url: 'procesar_qr.php',
                type: 'GET',
                data: { 
                    action: 'get_alumnos',
                    search: busqueda 
                },
                success: function(response) {
                    const alumnos = JSON.parse(response);
                    let html = '';
                    
                    if (alumnos.length === 0) {
                        html = '<tr><td colspan="5" class="text-center">No se encontraron alumnos</td></tr>';
                    } else {
                        alumnos.forEach(alumno => {
                            html += `
                                <tr>
                                    <td>${alumno.matricula}</td>
                                    <td>${alumno.nombre}</td>
                                    <td>${alumno.grupo}</td>
                                    <td>
                                        <span class="badge ${alumno.qr_generado ? 'bg-success' : 'bg-warning'}">
                                            ${alumno.qr_generado ? 'Generado' : 'Pendiente'}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="generarQRIndividual(${alumno.id})">
                                            <i class="fas fa-qrcode"></i> Generar QR
                                        </button>
                                        ${alumno.qr_generado ? `
                                            <button class="btn btn-sm btn-info" onclick="verQR(${alumno.id})">
                                                <i class="fas fa-eye"></i> Ver
                                            </button>
                                        ` : ''}
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    
                    $('#alumnosBody').html(html);
                },
                error: function() {
                    $('#alumnosBody').html('<tr><td colspan="5" class="text-center text-danger">Error al cargar los datos</td></tr>');
                }
            });
        }

        // Generar QR para un alumno individual
        function generarQRIndividual(alumnoId) {
            $.ajax({
                url: 'procesar_qr.php',
                type: 'POST',
                data: {
                    action: 'generar_qr',
                    alumno_id: alumnoId
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        showAlert(data.message, 'success');
                        alumnoActual = data.alumno;
                        mostrarQRModal(data.qr_code);
                    } else {
                        showAlert(data.message, 'danger');
                    }
                }
            });
        }

        // Generar todos los QR
        function generarTodosQR() {
            if (confirm('¿Estás seguro de generar c&oacute;digos QR para todos los alumnos?')) {
                $.ajax({
                    url: 'procesar_qr.php',
                    type: 'POST',
                    data: {
                        action: 'generar_todos_qr'
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        showAlert(data.message, data.success ? 'success' : 'warning');
                        cargarAlumnos();
                    }
                });
            }
        }

        // Mostrar QR en modal
        function mostrarQRModal(qrData) {
            const modal = new bootstrap.Modal(document.getElementById('qrModal'));
            const qrPreview = document.getElementById('qrPreview');
            const alumnoInfo = document.getElementById('alumnoInfo');
            
            // Limpiar contenido previo
            qrPreview.innerHTML = '';
            
            // Generar QR visual
            QRCode.toCanvas(qrPreview, qrData, {
                width: 200,
                margin: 2,
                color: {
                    dark: '#1a5330',  // Verde oscuro
                    light: '#FFFFFF'
                }
            }, function(error) {
                if (error) {
                    console.error(error);
                    qrPreview.innerHTML = '<div class="alert alert-danger">Error al generar QR</div>';
                }
            });
            
            // Mostrar información del alumno
            alumnoInfo.innerHTML = `
                <h5>${alumnoActual.nombre}</h5>
                <p class="mb-1">Matrícula: ${alumnoActual.matricula}</p>
                <p>Grupo: ${alumnoActual.grupo}</p>
                <small class="text-muted">Escanea este c&oacute;digo para registrar asistencia</small>
            `;
            
            // Guardar QR para descarga
            qrGenerado = {
                data: qrData,
                nombre: alumnoActual.nombre.replace(/\s+/g, '_'),
                matricula: alumnoActual.matricula
            };
            
            modal.show();
        }

        // Ver QR existente
        function verQR(alumnoId) {
            $.ajax({
                url: 'procesar_qr.php',
                type: 'POST',
                data: {
                    action: 'ver_qr',
                    alumno_id: alumnoId
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        alumnoActual = data.alumno;
                        mostrarQRModal(data.qr_code);
                    }
                }
            });
        }

        // Descargar QR
        function descargarQR() {
            if (!qrGenerado) return;
            
            const canvas = document.querySelector('#qrPreview canvas');
            if (!canvas) return;
            
            const link = document.createElement('a');
            link.download = `QR_${qrGenerado.nombre}_${qrGenerado.matricula}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        }

        // Cargar historial de asistencias
        function cargarHistorial() {
            const fecha = $('#fechaFiltro').val();
            const grupo = $('#grupoFiltro').val();
            
            $.ajax({
                url: 'procesar_qr.php',
                type: 'GET',
                data: {
                    action: 'get_asistencias',
                    fecha: fecha,
                    grupo: grupo
                },
                success: function(response) {
                    const asistencias = JSON.parse(response);
                    let html = '';
                    
                    if (asistencias.length === 0) {
                        html = '<tr><td colspan="6" class="text-center">No hay registros de asistencia</td></tr>';
                    } else {
                        asistencias.forEach(asistencia => {
                            const estado = asistencia.hora_salida ? 
                                '<span class="badge bg-success">Completo</span>' : 
                                '<span class="badge bg-warning">En clase</span>';
                            
                            html += `
                                <tr>
                                    <td>${asistencia.matricula}</td>
                                    <td>${asistencia.nombre}</td>
                                    <td>${asistencia.fecha}</td>
                                    <td>${asistencia.hora_entrada || '-'}</td>
                                    <td>${asistencia.hora_salida || '-'}</td>
                                    <td>${estado}</td>
                                </tr>
                            `;
                        });
                    }
                    
                    $('#asistenciasBody').html(html);
                },
                error: function() {
                    $('#asistenciasBody').html('<tr><td colspan="6" class="text-center text-danger">Error al cargar los datos</td></tr>');
                }
            });
        }

        // Actualizar estadísticas
        function actualizarEstadisticas() {
            $.ajax({
                url: 'procesar_qr.php',
                type: 'GET',
                data: { action: 'get_stats' },
                success: function(response) {
                    const stats = JSON.parse(response);
                    $('#totalHoy').text(stats.total_hoy);
                    $('#totalPendientes').text(stats.pendientes_salida);
                }
            });
        }

        // Exportar a Excel
        function exportarExcel() {
            const fecha = $('#fechaFiltro').val();
            const grupo = $('#grupoFiltro').val();
            
            $.ajax({
                url: 'procesar_qr.php',
                type: 'GET',
                data: {
                    action: 'export_excel',
                    fecha: fecha,
                    grupo: grupo
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    
                    if (data.length === 0) {
                        showAlert('No hay datos para exportar', 'warning');
                        return;
                    }
                    
                    // Crear libro de Excel
                    const ws = XLSX.utils.json_to_sheet(data);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Asistencias");
                    
                    // Generar nombre del archivo
                    const fechaStr = fecha || 'todas';
                    const grupoStr = grupo || 'todos';
                    const filename = `Asistencias_${fechaStr}_${grupoStr}.xlsx`;
                    
                    // Descargar
                    XLSX.writeFile(wb, filename);
                },
                error: function() {
                    showAlert('Error al exportar a Excel', 'danger');
                }
            });
        }

        // Cargar opciones de grupos
        function cargarGrupos() {
            $.ajax({
                url: 'procesar_qr.php',
                type: 'GET',
                data: { action: 'get_grupos' },
                success: function(response) {
                    const grupos = JSON.parse(response);
                    let html = '<option value="">Todos los grupos</option>';
                    
                    grupos.forEach(grupo => {
                        html += `<option value="${grupo}">${grupo}</option>`;
                    });
                    
                    $('#grupoFiltro').html(html);
                }
            });
        }

        // Inicializar sidebar
        document.getElementById('btn-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            
            const icon = this;
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.remove('bx-menu');
                icon.classList.add('bx-menu-alt-right');
            } else {
                icon.classList.remove('bx-menu-alt-right');
                icon.classList.add('bx-menu');
            }
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

        // Event Listeners
        $(document).ready(function() {
            // Cargar datos iniciales
            cargarAlumnos();
            cargarHistorial();
            cargarGrupos();
            actualizarEstadisticas();
            
            // Inicializar escáner al cargar
            initScanner();
            
            // Eventos
            $('#startScanner').click(startScanner);
            $('#stopScanner').click(stopScanner);
            $('#generateAllQR').click(generarTodosQR);
            $('#btnBuscar').click(() => cargarAlumnos($('#searchAlumno').val()));
            $('#searchAlumno').keypress(function(e) {
                if (e.which === 13) cargarAlumnos($(this).val());
            });
            $('#fechaFiltro, #grupoFiltro').change(cargarHistorial);
            
            // Navegación por anclas
            document.querySelectorAll('.qr-nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = this.getAttribute('href').substring(1);
                    mostrarSeccion(target);
                });
            });
            
            // Actualizar estadísticas cada 30 segundos
            setInterval(actualizarEstadisticas, 30000);
            
            // Efectos hover para tarjetas
            document.querySelectorAll('.qr-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 15px 35px rgba(46, 125, 50, 0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 10px 30px rgba(46, 125, 50, 0.08)';
                });
            });
            
            // Efectos para tarjetas de estadísticas
            document.querySelectorAll('.stats-card-qr').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 20px rgba(46, 125, 50, 0.1)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 5px 15px rgba(46, 125, 50, 0.05)';
                });
            });
            
            window.addEventListener('resize', handleResize);
            window.addEventListener('load', handleResize);
        });
    </script>
</body>
</html>