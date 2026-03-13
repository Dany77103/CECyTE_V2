<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Simulación de configuración del sistema
$configuracion = [
    'notificaciones_email' => true,
    'notificaciones_push' => false,
    'tema_oscuro' => false,
    'idioma' => 'es',
    'resultados_por_pagina' => 25,
    'formato_fecha' => 'd/m/Y',
    'auto_guardado' => true,
    'zona_horaria' => 'America/Mexico_City'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraci&oacute;n - Sistema de Reportes</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="styles.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed: 80px;
            --primary-color: #1a5330;    /* Verde muy oscuro */
            --secondary-color: #2e7d32;  /* Verde oscuro */
            --accent-color: #4caf50;     /* Verde medio */
            --light-green: #8bc34a;      /* Verde claro */
            --very-light-green: #c8e6c9; /* Verde muy claro */
            --text-color: #ffffff;
            --hover-color: #8bc34a;
        }
        
        .main-container {
            display: flex;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: var(--text-color);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 3px 0 15px rgba(26, 83, 48, 0.1);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }
        
        .sidebar-header {
            padding: 20px 15px;
            background-color: rgba(26, 83, 48, 0.2);
            border-bottom: 1px solid rgba(139, 195, 74, 0.2);
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
            background: rgba(139, 195, 74, 0.2);
            padding: 8px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        #btn-toggle:hover {
            background: var(--accent-color);
            transform: rotate(90deg);
        }
        
        .sidebar-menu {
            padding: 20px 0;
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
            background: rgba(139, 195, 74, 0.15);
            border-left-color: var(--light-green);
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
            background: var(--primary-color);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1001;
            box-shadow: 0 2px 10px rgba(26, 83, 48, 0.2);
        }
        
        .sidebar.collapsed .nav-link:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        .user-section {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px;
            background: rgba(26, 83, 48, 0.2);
            border-top: 1px solid rgba(139, 195, 74, 0.2);
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
            background: rgba(139, 195, 74, 0.15);
            color: white;
        }
        
        .content-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            background: var(--very-light-green);
        }
        
        .sidebar.collapsed ~ .content-wrapper {
            margin-left: var(--sidebar-collapsed);
        }
        
        .main-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(46, 125, 50, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 2px solid var(--light-green);
        }
        
        /* Estilos específicos para configuración */
        .config-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .config-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(46, 125, 50, 0.08);
            border: 1px solid var(--light-green);
        }
        
        .config-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
        }
        
        .config-title {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .config-subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .config-body {
            padding: 30px;
        }
        
        .config-section {
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--very-light-green);
        }
        
        .section-title {
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }
        
        .config-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: var(--very-light-green);
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid #e0f2e1;
        }
        
        .config-item:hover {
            background: #e0f2e1;
            border-color: var(--light-green);
        }
        
        .item-info h6 {
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .item-info p {
            margin: 0;
            color: #2e7d32;
            font-size: 0.9rem;
        }
        
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-select, .form-control {
            max-width: 250px;
            border-color: var(--light-green);
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }
        
        /* Estilos para switches personalizados */
        .form-check-input:checked {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .form-check-input:focus {
            border-color: var(--light-green);
            box-shadow: 0 0 0 0.25rem rgba(139, 195, 74, 0.25);
        }
        
        .danger-zone {
            background: #fff5f5;
            border: 2px solid #f1aeb5;
            border-radius: 10px;
            padding: 25px;
            margin-top: 40px;
        }
        
        .danger-title {
            color: #dc3545;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .danger-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        /* Botones personalizados con la paleta verde */
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary {
            color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-success, .badge.bg-success {
            background-color: var(--accent-color) !important;
            border-color: var(--accent-color);
        }
        
        .btn-outline-secondary {
            color: var(--light-green);
            border-color: var(--light-green);
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--light-green);
            border-color: var(--light-green);
            color: white;
        }
        
        footer.bg-success {
            background-color: var(--primary-color) !important;
            border-top: 3px solid var(--secondary-color);
        }
        
        /* Estilos para dropdowns */
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: var(--very-light-green);
        }
        
        .dropdown-item.active, .dropdown-item:active {
            background-color: var(--accent-color);
        }
        
        /* Estilos para modales */
        .modal-header {
            background-color: var(--very-light-green);
            border-bottom: 2px solid var(--light-green);
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
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
            
            .config-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .form-select, .form-control {
                max-width: 100%;
                width: 100%;
            }
            
            .danger-buttons {
                flex-direction: column;
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
                    <a href="updo.php" class="nav-link">
                        <i class='bx bx-folder'></i>
                        <span class="link-text">Archivos</span>
                        <span class="tooltip">Subir/Descargar Archivos</span>
                    </a>
                </li>
                
                <li class="nav-item my-4">
                    <hr style="border-color: rgba(139, 195, 74, 0.2); margin: 0 20px;">
                </li>
                
                <li class="nav-item">
                    <a href="configuracion.php" class="nav-link active">
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
            <header class="main-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" style="color: var(--primary-color);">Configuraci&oacute;n del Sistema</h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-3">CECyTE Santa Catarina N.L.</span>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class='bx bx-user-circle'></i>
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
            
            <main class="container-fluid py-4">
                <div class="config-container">
                    <div class="config-card">
                        <!-- Encabezado -->
                        <div class="config-header">
                            <h2 class="config-title">Configuraci&oacute;n del Sistema</h2>
                            <p class="config-subtitle">Personaliza tu experiencia en el sistema de reportes</p>
                        </div>
                        
                        <!-- Cuerpo -->
                        <div class="config-body">
                            <!-- Notificaciones -->
                            <div class="config-section">
                                <h4 class="section-title">
                                    <i class='bx bx-bell'></i>
                                    Notificaciones
                                </h4>
                                
                                <div class="config-grid">
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Notificaciones por Email</h6>
                                            <p>Recibe notificaciones importantes en tu correo electr&oacute;nico</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="notifEmail" <?php echo $configuracion['notificaciones_email'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="notifEmail"></label>
                                        </div>
                                    </div>
                                    
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Notificaciones Push</h6>
                                            <p>Recibe notificaciones emergentes en el navegador</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="notifPush" <?php echo $configuracion['notificaciones_push'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="notifPush"></label>
                                        </div>
                                    </div>
                                    
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Reportes Completados</h6>
                                            <p>Notificarme cuando se completen mis reportes</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="notifReportes" checked>
                                            <label class="form-check-label" for="notifReportes"></label>
                                        </div>
                                    </div>
                                    
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Recordatorios Semanales</h6>
                                            <p>Recordatorios de tareas pendientes</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="notifRecordatorios" checked>
                                            <label class="form-check-label" for="notifRecordatorios"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Apariencia -->
                            <div class="config-section">
                                <h4 class="section-title">
                                    <i class='bx bx-palette'></i>
                                    Apariencia
                                </h4>
                                
                                <div class="config-grid">
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Tema Oscuro</h6>
                                            <p>Activar modo oscuro para el sistema</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="temaOscuro" <?php echo $configuracion['tema_oscuro'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="temaOscuro"></label>
                                        </div>
                                    </div>
                                    
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Idioma</h6>
                                            <p>Selecciona el idioma de la interfaz</p>
                                        </div>
                                        <select class="form-select" id="idioma">
                                            <option value="es" <?php echo $configuracion['idioma'] == 'es' ? 'selected' : ''; ?>>Espa&ntilde;ol</option>
                                            <option value="en" <?php echo $configuracion['idioma'] == 'en' ? 'selected' : ''; ?>>Ingl&eacute;s</option>
                                        </select>
                                    </div>
                                    
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Formato de Fecha</h6>
                                            <p>Formato en que se muestran las fechas</p>
                                        </div>
                                        <select class="form-select" id="formatoFecha">
                                            <option value="d/m/Y" <?php echo $configuracion['formato_fecha'] == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/AAAA</option>
                                            <option value="m/d/Y" <?php echo $configuracion['formato_fecha'] == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/AAAA</option>
                                            <option value="Y-m-d" <?php echo $configuracion['formato_fecha'] == 'Y-m-d' ? 'selected' : ''; ?>>AAAA-MM-DD</option>
                                        </select>
                                    </div>
                                    
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Resultados por P&aacute;gina</h6>
                                            <p>Cantidad de elementos a mostrar en listados</p>
                                        </div>
                                        <select class="form-select" id="resultadosPagina">
                                            <option value="10" <?php echo $configuracion['resultados_por_pagina'] == 10 ? 'selected' : ''; ?>>10</option>
                                            <option value="25" <?php echo $configuracion['resultados_por_pagina'] == 25 ? 'selected' : ''; ?>>25</option>
                                            <option value="50" <?php echo $configuracion['resultados_por_pagina'] == 50 ? 'selected' : ''; ?>>50</option>
                                            <option value="100" <?php echo $configuracion['resultados_por_pagina'] == 100 ? 'selected' : ''; ?>>100</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Funcionalidad -->
                            <div class="config-section">
                                <h4 class="section-title">
                                    <i class='bx bx-slider-alt'></i>
                                    Funcionalidad
                                </h4>
                                
                                <div class="config-grid">
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Auto-guardado</h6>
                                            <p>Guardar autom&aacute;ticamente los cambios en formularios</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="autoGuardado" <?php echo $configuracion['auto_guardado'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="autoGuardado"></label>
                                        </div>
                                    </div>
                                    
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Mostrar Tutorial</h6>
                                            <p>Mostrar tutorial de uso al iniciar sesi&oacute;n</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="mostrarTutorial" checked>
                                            <label class="form-check-label" for="mostrarTutorial"></label>
                                        </div>
                                    </div>
                                    
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Zona Horaria</h6>
                                            <p>Zona horaria para las fechas del sistema</p>
                                        </div>
                                        <select class="form-select" id="zonaHoraria">
                                            <option value="America/Mexico_City" <?php echo $configuracion['zona_horaria'] == 'America/Mexico_City' ? 'selected' : ''; ?>>Ciudad de M&eacute;xico</option>
                                            <option value="America/New_York">Nueva York</option>
                                            <option value="America/Los_Angeles">Los &Aacute;ngeles</option>
                                            <option value="Europe/Madrid">Madrid</option>
                                        </select>
                                    </div>
                                    
                                    <div class="config-item">
                                        <div class="item-info">
                                            <h6>Exportar Formato</h6>
                                            <p>Formato por defecto para exportar datos</p>
                                        </div>
                                        <select class="form-select" id="formatoExportar">
                                            <option value="pdf" selected>PDF</option>
                                            <option value="excel">Excel</option>
                                            <option value="csv">CSV</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Zona de peligro -->
                            <div class="danger-zone">
                                <h4 class="danger-title">
                                    <i class='bx bx-error-circle'></i>
                                    Zona de Peligro
                                </h4>
                                
                                <p class="text-danger mb-4">Estas acciones son irreversibles. Por favor, proceda con precauci&oacute;n.</p>
                                
                                <div class="danger-buttons">
                                    <button class="btn btn-outline-secondary" id="limpiarCache">
                                        <i class='bx bx-trash'></i> Limpiar Cache del Sistema
                                    </button>
                                    
                                    <button class="btn btn-outline-secondary" id="reiniciarEstadisticas">
                                        <i class='bx bx-reset'></i> Reiniciar Estad&iacute;sticas
                                    </button>
                                    
                                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#eliminarCuentaModal">
                                        <i class='bx bx-user-x'></i> Eliminar Mi Cuenta
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Botones de acción -->
                            <div class="d-flex justify-content-between mt-4">
                                <button class="btn btn-outline-secondary" id="restablecerConfig">
                                    <i class='bx bx-reset'></i> Restablecer Configuraci&oacute;n
                                </button>
                                
                                <div class="d-flex gap-3">
                                    <button class="btn btn-outline-primary" id="exportarConfig">
                                        <i class='bx bx-export'></i> Exportar Configuraci&oacute;n
                                    </button>
                                    
                                    <button class="btn btn-primary" id="guardarConfig">
                                        <i class='bx bx-save'></i> Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- Footer -->
            <footer class="bg-success text-white text-center py-3 mt-4">
                <div class="container">
                    <p class="mb-1">CECyTE SANTA CATARINA N.L.</p>
                    <p class="mb-0">© <?php echo date("Y"); ?> Sistema de Reportes. Todos los derechos reservados.</p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Modal para eliminar cuenta -->
    <div class="modal fade" id="eliminarCuentaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Eliminar Cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class='bx bx-error'></i>
                        <strong>Advertencia:</strong> Esta acci&oacute;n es irreversible.
                    </div>
                    
                    <p>Al eliminar tu cuenta:</p>
                    <ul>
                        <li>Todos tus datos personales ser&aacute;n eliminados permanentemente</li>
                        <li>Tus reportes y estadísticas se perder&aacute;n</li>
                        <li>No podr&aacute;s recuperar el acceso al sistema</li>
                    </ul>
                    
                    <div class="mb-3">
                        <label for="confirmDelete" class="form-label">
                            Escribe "ELIMINAR" para confirmar:
                        </label>
                        <input type="text" class="form-control" id="confirmDelete" placeholder="ELIMINAR">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmEliminar" disabled>Eliminar Cuenta</button>
                </div>
            </div>
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
            
            const icon = this;
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.remove('bx-menu');
                icon.classList.add('bx-menu-alt-right');
            } else {
                icon.classList.remove('bx-menu-alt-right');
                icon.classList.add('bx-menu');
            }
        });
        
        // Validación para eliminar cuenta
        document.getElementById('confirmDelete').addEventListener('input', function(e) {
            const confirmBtn = document.getElementById('confirmEliminar');
            confirmBtn.disabled = e.target.value !== 'ELIMINAR';
        });
        
        // Acciones de configuración
        document.getElementById('guardarConfig').addEventListener('click', function() {
            // Aquí iría la lógica para guardar la configuración
            alert('Configuración guardada exitosamente.');
        });
        
        document.getElementById('restablecerConfig').addEventListener('click', function() {
            if (confirm('¿Estás seguro de que deseas restablecer toda la configuración a los valores por defecto?')) {
                // Aquí iría la lógica para restablecer configuración
                location.reload();
            }
        });
        
        document.getElementById('exportarConfig').addEventListener('click', function() {
            // Aquí iría la lógica para exportar configuración
            alert('Configuración exportada exitosamente.');
        });
        
        document.getElementById('limpiarCache').addEventListener('click', function() {
            if (confirm('¿Estás seguro de que deseas limpiar la cache del sistema?')) {
                alert('Cache limpiada exitosamente.');
            }
        });
        
        document.getElementById('reiniciarEstadisticas').addEventListener('click', function() {
            if (confirm('¿Estás seguro de que deseas reiniciar todas las estadísticas?')) {
                alert('Estadísticas reiniciadas.');
            }
        });
        
        document.getElementById('confirmEliminar').addEventListener('click', function() {
            if (confirm('¿ESTÁS ABSOLUTAMENTE SEGURO? Esta acción no se puede deshacer.')) {
                // Aquí iría la lógica para eliminar la cuenta
                alert('Cuenta eliminada. Serás redirigido al inicio.');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 2000);
            }
        });
        
        // Validar todos los campos de selección
        document.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', function() {
                // Aquí podrías agregar validación específica
                console.log(`Configuración cambiada: ${this.id} = ${this.value}`);
            });
        });
        
        // Validar todos los switches
        document.querySelectorAll('.form-check-input').forEach(switchEl => {
            switchEl.addEventListener('change', function() {
                console.log(`Switch cambiado: ${this.id} = ${this.checked}`);
            });
        });
    </script>
</body>
</html>