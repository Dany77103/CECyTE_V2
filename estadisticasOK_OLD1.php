<?php
// Conexión a la base de datos usando PDO
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

// Función para obtener datos de la base de datos usando PDO
function obtenerDatos($query) {
    global $con; // Usamos la variable $con (conexión PDO)
    try {
        $stmt = $con->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Devuelve los datos como un array asociativo
    } catch (PDOException $e) {
        return []; // Devolver array vacío en caso de error
    }
}

session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Consultas SQL principales
$queryAlumnosActivos = "SELECT COUNT(*) AS alumnosActivos FROM historialacademicoalumnos haa
                        INNER JOIN estatus e ON e.id_estatus = haa.id_estatus
                        WHERE e.tipoEstatus = 'activo'";

$queryAlumnosBajaTemporal = "SELECT COUNT(*) AS alumnosBajaTemporal FROM historialacademicoalumnos haa
                             INNER JOIN estatus e ON e.id_estatus = haa.id_estatus
                             WHERE e.tipoEstatus = 'baja temporal'";

$queryMaestrosActivos = "SELECT COUNT(*) AS maestrosActivos FROM datoslaboralesmaestros dlm
                         INNER JOIN estatus e ON e.id_estatus = dlm.id_estatus
                         WHERE e.tipoEstatus = 'activo'";

// Nuevas consultas para estadísticas adicionales
$queryTotalAlumnos = "SELECT COUNT(*) AS total FROM alumnos";
$queryTotalMaestros = "SELECT COUNT(*) AS total FROM maestros";
$queryTotalCalificaciones = "SELECT COUNT(*) AS total FROM calificaciones";
$queryAlumnosPorGenero = "SELECT 
                            SUM(CASE WHEN genero = 'Masculino' THEN 1 ELSE 0 END) as masculino,
                            SUM(CASE WHEN genero = 'Femenino' THEN 1 ELSE 0 END) as femenino,
                            SUM(CASE WHEN genero IS NULL OR genero = '' THEN 1 ELSE 0 END) as no_especificado
                          FROM alumnos";
$queryPromedioCalificaciones = "SELECT AVG(calificacion) as promedio FROM calificaciones";
$queryAlumnosPorGrupo = "SELECT grupo, COUNT(*) as cantidad FROM alumnos GROUP BY grupo ORDER BY cantidad DESC LIMIT 10";
$queryAsistenciaPromedio = "SELECT 
                            DATE_FORMAT(fecha, '%Y-%m') as mes,
                            AVG(CASE WHEN hora_entrada IS NOT NULL THEN 1 ELSE 0 END) * 100 as asistencia_promedio
                            FROM asistencias_qr 
                            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                            GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                            ORDER BY mes DESC";

// Obtener datos principales
$alumnosActivos = obtenerDatos($queryAlumnosActivos)[0]['alumnosActivos'] ?? 0;
$alumnosBajaTemporal = obtenerDatos($queryAlumnosBajaTemporal)[0]['alumnosBajaTemporal'] ?? 0;
$maestrosActivos = obtenerDatos($queryMaestrosActivos)[0]['maestrosActivos'] ?? 0;

// Obtener datos adicionales
$totalAlumnos = obtenerDatos($queryTotalAlumnos)[0]['total'] ?? 0;
$totalMaestros = obtenerDatos($queryTotalMaestros)[0]['total'] ?? 0;
$totalCalificaciones = obtenerDatos($queryTotalCalificaciones)[0]['total'] ?? 0;
$generoData = obtenerDatos($queryAlumnosPorGenero)[0] ?? ['masculino' => 0, 'femenino' => 0, 'no_especificado' => 0];
$promedioCalificaciones = obtenerDatos($queryPromedioCalificaciones)[0]['promedio'] ?? 0;
$alumnosPorGrupo = obtenerDatos($queryAlumnosPorGrupo);
$asistenciaMensual = obtenerDatos($queryAsistenciaPromedio);

// Preparar datos para gráficas
$gruposLabels = array_column($alumnosPorGrupo, 'grupo');
$gruposData = array_column($alumnosPorGrupo, 'cantidad');

$mesesLabels = array_column($asistenciaMensual, 'mes');
$asistenciaData = array_column($asistenciaMensual, 'asistencia_promedio');

// Convertir datos a JSON para JavaScript
$dataJSON = json_encode([
    'alumnosActivos' => $alumnosActivos,
    'alumnosBajaTemporal' => $alumnosBajaTemporal,
    'maestrosActivos' => $maestrosActivos,
    'totalAlumnos' => $totalAlumnos,
    'totalMaestros' => $totalMaestros,
    'totalCalificaciones' => $totalCalificaciones,
    'generoMasculino' => $generoData['masculino'],
    'generoFemenino' => $generoData['femenino'],
    'generoNoEspecificado' => $generoData['no_especificado'],
    'promedioCalificaciones' => round($promedioCalificaciones, 2),
    'gruposLabels' => $gruposLabels,
    'gruposData' => $gruposData,
    'mesesLabels' => $mesesLabels,
    'asistenciaData' => $asistenciaData
]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CECYTE - Sistema de Estad&iacute;sticas</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Estilos para el sistema de estadísticas */
        .stats-container {
            padding: 30px;
        }
        
        .page-title {
            color: var(--verde-oscuro);
            margin-bottom: 30px;
            font-weight: 700;
            border-bottom: 3px solid var(--verde-claro);
            padding-bottom: 15px;
        }
        
        /* Tarjetas de estadísticas principales */
        .main-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card-main {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            transition: all 0.3s ease;
            border-top: 5px solid;
            text-align: center;
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        .stat-card-main:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(46, 125, 50, 0.15);
            border-color: var(--verde-medio);
        }
        
        .stat-card-main.alumnos {
            border-top-color: var(--verde-oscuro);
        }
        
        .stat-card-main.maestros {
            border-top-color: var(--verde-principal);
        }
        
        .stat-card-main.calificaciones {
            border-top-color: var(--verde-medio);
        }
        
        .stat-card-main.estado {
            border-top-color: var(--verde-claro);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .stat-card-main.alumnos .stat-icon {
            color: var(--verde-oscuro);
        }
        
        .stat-card-main.maestros .stat-icon {
            color: var(--verde-principal);
        }
        
        .stat-card-main.calificaciones .stat-icon {
            color: var(--verde-medio);
        }
        
        .stat-card-main.estado .stat-icon {
            color: var(--verde-claro);
        }
        
        .stat-number {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            line-height: 1;
        }
        
        .stat-card-main.alumnos .stat-number {
            color: var(--verde-oscuro);
        }
        
        .stat-card-main.maestros .stat-number {
            color: var(--verde-principal);
        }
        
        .stat-card-main.calificaciones .stat-number {
            color: var(--verde-medio);
        }
        
        .stat-card-main.estado .stat-number {
            color: var(--verde-claro);
        }
        
        .stat-label {
            color: #5d6d5f;
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* Controles de gráficas */
        .chart-controls {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        .chart-title {
            color: var(--verde-oscuro);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .chart-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn-chart {
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            min-width: 140px;
        }
        
        .btn-chart:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.2);
        }
        
        .chart-type-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .chart-type-label {
            font-weight: 600;
            color: var(--verde-oscuro);
        }
        
        /* Contenedor de gráficas */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .chart-wrapper {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        .chart-wrapper:hover {
            box-shadow: 0 15px 35px rgba(46, 125, 50, 0.15);
            border-color: var(--verde-medio);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-subtitle {
            color: var(--verde-oscuro);
            font-weight: 600;
            margin: 0;
        }
        
        .chart-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-chart-action {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9rem;
            background: #f1f8e9;
            border: 1px solid #c5e1a5;
            color: #5d6d5f;
            transition: all 0.3s ease;
        }
        
        .btn-chart-action:hover {
            background: #dcedc8;
            color: var(--verde-oscuro);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Tabla de estadísticas detalladas */
        .stats-table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            margin-bottom: 40px;
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        .table-stats {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-stats thead {
            background: var(--verde-oscuro);
            color: white;
        }
        
        .table-stats th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .table-stats tbody tr {
            border-bottom: 1px solid #dee2e6;
            transition: background 0.3s ease;
        }
        
        .table-stats tbody tr:hover {
            background: #f1f8e9;
        }
        
        .table-stats td {
            padding: 15px;
        }
        
        /* Badges personalizados */
        .badge.bg-success {
            background-color: var(--verde-principal) !important;
        }
        
        .badge.bg-warning {
            background-color: var(--verde-claro) !important;
            color: #333;
        }
        
        /* Alertas */
        .alert-info {
            background-color: #e8f5e9;
            border-color: #c8e6c9;
            color: #2e7d32;
        }
        
        /* Formularios */
        .form-check-input:checked {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        
        /* Botón dropdown header */
        .btn-outline-success {
            color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        
        .btn-outline-success:hover {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
            color: white;
        }
        
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
            
            .stats-container {
                padding: 15px;
            }
            
            .main-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-buttons {
                flex-direction: column;
            }
            
            .btn-chart {
                min-width: 100%;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 250px;
            }
        }
        
        @media (max-width: 576px) {
            .chart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .chart-actions {
                align-self: flex-end;
            }
            
            .stat-number {
                font-size: 2.2rem;
            }
            
            .stat-icon {
                font-size: 2rem;
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
                    <div class="logo-name">SISTEMA DE ESTAD&Iacute;STICAS</div>
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
                        <span class="link-text">Registro</span>
                        <span class="tooltip">Registro de Informaci&oacute;n</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="reportes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>">
                        <i class='bx bx-pencil'></i>
                        <span class="link-text">Reportes</span>
                        <span class="tooltip">Generar Reportes</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="estadisticas.php" class="nav-link active">
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
                    <h5 class="mb-0">Sistema de Estad&iacute;sticas - CECyTE</h5>
					
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
            <main class="stats-container">
                <h1 class="page-title">
                    <i class='bx bx-chart'></i> Sistema de Estad&iacute;sticas
                </h1>
                
                <!-- Estadísticas principales -->
                <div class="main-stats-grid">
                    <div class="stat-card-main alumnos">
                        <i class="fas fa-user-graduate stat-icon"></i>
                        <div class="stat-number"><?php echo $totalAlumnos; ?></div>
                        <div class="stat-label">Total de Alumnos</div>
                    </div>
                    
                    <div class="stat-card-main maestros">
                        <i class="fas fa-chalkboard-teacher stat-icon"></i>
                        <div class="stat-number"><?php echo $totalMaestros; ?></div>
                        <div class="stat-label">Total de Maestros</div>
                    </div>
                    
                    <div class="stat-card-main calificaciones">
                        <i class="fas fa-check-circle stat-icon"></i>
                        <div class="stat-number"><?php echo $promedioCalificaciones; ?></div>
                        <div class="stat-label">Promedio General</div>
                    </div>
                    
                    <div class="stat-card-main estado">
                        <i class="fas fa-chart-line stat-icon"></i>
                        <div class="stat-number"><?php echo $alumnosActivos; ?></div>
                        <div class="stat-label">Alumnos Activos</div>
                    </div>
                </div>
                
                <!-- Controles de gráficas -->
                <div class="chart-controls">
                    <h4 class="chart-title">Tipo de Gr&aacute;fica</h4>
                    <div class="chart-type-selector">
                        <span class="chart-type-label">Seleccionar tipo:</span>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="chartType" id="chartBar" value="bar" checked>
                            <label class="form-check-label" for="chartBar">Barras</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="chartType" id="chartLine" value="line">
                            <label class="form-check-label" for="chartLine">L&iacute;neas</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="chartType" id="chartPie" value="pie">
                            <label class="form-check-label" for="chartPie">Pastel</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="chartType" id="chartDoughnut" value="doughnut">
                            <label class="form-check-label" for="chartDoughnut">Dona</label>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficas principales -->
                <div class="charts-grid">
                    <!-- Gráfica 1: Estado de alumnos -->
                    <div class="chart-wrapper">
                        <div class="chart-header">
                            <h5 class="chart-subtitle">Estado de Alumnos y Maestros</h5>
                            <div class="chart-actions">
                                <button class="btn-chart-action" onclick="descargarGrafica('chartEstado')">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn-chart-action" onclick="imprimirGrafica('chartEstado')">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartEstado"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfica 2: Distribución por género -->
                    <div class="chart-wrapper">
                        <div class="chart-header">
                            <h5 class="chart-subtitle">Distribuci&oacute;n de Alumnos por G&eacute;nero</h5>
                            <div class="chart-actions">
                                <button class="btn-chart-action" onclick="descargarGrafica('chartGenero')">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn-chart-action" onclick="imprimirGrafica('chartGenero')">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartGenero"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfica 3: Alumnos por grupo -->
                    <div class="chart-wrapper">
                        <div class="chart-header">
                            <h5 class="chart-subtitle">Alumnos por Grupo (Top 10)</h5>
                            <div class="chart-actions">
                                <button class="btn-chart-action" onclick="descargarGrafica('chartGrupos')">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn-chart-action" onclick="imprimirGrafica('chartGrupos')">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartGrupos"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfica 4: Asistencia mensual -->
                    <div class="chart-wrapper">
                        <div class="chart-header">
                            <h5 class="chart-subtitle">Asistencia Promedio Mensual</h5>
                            <div class="chart-actions">
                                <button class="btn-chart-action" onclick="descargarGrafica('chartAsistencia')">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn-chart-action" onclick="imprimirGrafica('chartAsistencia')">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartAsistencia"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de estadísticas detalladas -->
                <div class="stats-table-container">
                    <h4 class="chart-title mb-4">Estad&iacute;sticas Detalladas</h4>
                    <div class="table-responsive">
                        <table class="table table-stats">
                            <thead>
                                <tr>
                                    <th>Indicador</th>
                                    <th>Valor</th>
                                    <th>Descripci&oacute;n</th>
                                    <th>Tendencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Alumnos Totales</td>
                                    <td><strong><?php echo $totalAlumnos; ?></strong></td>
                                    <td>Total de alumnos registrados en el sistema</td>
                                    <td><span class="badge bg-success">+5%</span></td>
                                </tr>
                                <tr>
                                    <td>Alumnos Activos</td>
                                    <td><strong><?php echo $alumnosActivos; ?></strong></td>
                                    <td>Alumnos con estatus activo actualmente</td>
                                    <td><span class="badge bg-success">+3%</span></td>
                                </tr>
                                <tr>
                                    <td>Alumnos Baja Temporal</td>
                                    <td><strong><?php echo $alumnosBajaTemporal; ?></strong></td>
                                    <td>Alumnos con estatus de baja temporal</td>
                                    <td><span class="badge bg-warning">+2%</span></td>
                                </tr>
                                <tr>
                                    <td>Maestros Activos</td>
                                    <td><strong><?php echo $maestrosActivos; ?></strong></td>
                                    <td>Maestros con estatus activo actualmente</td>
                                    <td><span class="badge bg-success">+1%</span></td>
                                </tr>
                                <tr>
                                    <td>Promedio Calificaciones</td>
                                    <td><strong><?php echo $promedioCalificaciones; ?></strong></td>
                                    <td>Promedio general de calificaciones</td>
                                    <td><span class="badge bg-success">+0.5</span></td>
                                </tr>
                                <tr>
                                    <td>Calificaciones Registradas</td>
                                    <td><strong><?php echo $totalCalificaciones; ?></strong></td>
                                    <td>Total de calificaciones en el sistema</td>
                                    <td><span class="badge bg-success">+15%</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Información de actualización -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Las estad&iacute;sticas se actualizan autom&aacute;ticamente cada 30 minutos. &Uacute;ltima actualizaci&oacute;n: <?php echo date('d/m/Y H:i:s'); ?>
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
        // Datos desde PHP (convertidos a JSON)
        const data = <?php echo $dataJSON; ?>;
        
        // Variables para almacenar instancias de gráficas
        let chartEstado, chartGenero, chartGrupos, chartAsistencia;
        
        // Función para inicializar todas las gráficas
        function inicializarGraficas() {
            const chartType = document.querySelector('input[name="chartType"]:checked').value;
            
            // Destruir gráficas existentes
            if (chartEstado) chartEstado.destroy();
            if (chartGenero) chartGenero.destroy();
            if (chartGrupos) chartGrupos.destroy();
            if (chartAsistencia) chartAsistencia.destroy();
            
            // Crear nuevas gráficas
            crearGraficaEstado(chartType);
            crearGraficaGenero(chartType);
            crearGraficaGrupos(chartType);
            crearGraficaAsistencia(chartType);
        }
        
        // Función para crear gráfica de estado
        function crearGraficaEstado(type) {
            const ctx = document.getElementById('chartEstado').getContext('2d');
            chartEstado = new Chart(ctx, {
                type: type,
                data: {
                    labels: ['Alumnos Activos', 'Alumnos Baja Temporal', 'Maestros Activos'],
                    datasets: [{
                        label: 'Cantidad',
                        data: [data.alumnosActivos, data.alumnosBajaTemporal, data.maestrosActivos],
                        backgroundColor: [
                            'rgba(26, 83, 48, 0.7)',    // Verde oscuro
                            'rgba(139, 195, 74, 0.7)',  // Verde claro
                            'rgba(46, 125, 50, 0.7)'    // Verde principal
                        ],
                        borderColor: [
                            'rgba(26, 83, 48, 1)',
                            'rgba(139, 195, 74, 1)',
                            'rgba(46, 125, 50, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.raw}`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Función para crear gráfica de género
        function crearGraficaGenero(type) {
            const ctx = document.getElementById('chartGenero').getContext('2d');
            chartGenero = new Chart(ctx, {
                type: type,
                data: {
                    labels: ['Masculino', 'Femenino', 'No Especificado'],
                    datasets: [{
                        label: 'Distribucion por Genero',
                        data: [data.generoMasculino, data.generoFemenino, data.generoNoEspecificado],
                        backgroundColor: [
                            'rgba(26, 83, 48, 0.7)',    // Verde oscuro
                            'rgba(76, 175, 80, 0.7)',   // Verde medio
                            'rgba(139, 195, 74, 0.7)'   // Verde claro
                        ],
                        borderColor: [
                            'rgba(26, 83, 48, 1)',
                            'rgba(76, 175, 80, 1)',
                            'rgba(139, 195, 74, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        }
        
        // Función para crear gráfica de grupos
        function crearGraficaGrupos(type) {
            const ctx = document.getElementById('chartGrupos').getContext('2d');
            chartGrupos = new Chart(ctx, {
                type: type === 'pie' || type === 'doughnut' ? 'bar' : type, // Para grupos usamos barras por defecto
                data: {
                    labels: data.gruposLabels,
                    datasets: [{
                        label: 'Cantidad de Alumnos',
                        data: data.gruposData,
                        backgroundColor: 'rgba(46, 125, 50, 0.7)', // Verde principal
                        borderColor: 'rgba(46, 125, 50, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad de Alumnos'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Grupos'
                            }
                        }
                    }
                }
            });
        }
        
        // Función para crear gráfica de asistencia
        function crearGraficaAsistencia(type) {
            const ctx = document.getElementById('chartAsistencia').getContext('2d');
            chartAsistencia = new Chart(ctx, {
                type: type === 'pie' || type === 'doughnut' ? 'line' : type,
                data: {
                    labels: data.mesesLabels,
                    datasets: [{
                        label: 'Asistencia Promedio (%)',
                        data: data.asistenciaData,
                        backgroundColor: 'rgba(76, 175, 80, 0.2)', // Verde medio
                        borderColor: 'rgba(46, 125, 50, 1)', // Verde principal
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Asistencia (%)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Mes'
                            }
                        }
                    }
                }
            });
        }
        
        // Función para descargar gráfica
        function descargarGrafica(chartId) {
            const link = document.createElement('a');
            link.download = `grafica_${chartId}_${new Date().toISOString().slice(0,10)}.png`;
            link.href = document.getElementById(chartId).toDataURL('image/png');
            link.click();
        }
        
        // Función para imprimir gráfica
        function imprimirGrafica(chartId) {
            const canvas = document.getElementById(chartId);
            const win = window.open('');
            win.document.write('<html><head><title>Imprimir Gráfica</title></head><body>');
            win.document.write('<img src="' + canvas.toDataURL('image/png') + '"/>');
            win.document.write('</body></html>');
            win.document.close();
            win.print();
        }
        
        // Event listeners para cambio de tipo de gráfica
        document.querySelectorAll('input[name="chartType"]').forEach(radio => {
            radio.addEventListener('change', inicializarGraficas);
        });
        
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
        
        // Inicializar gráficas al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            inicializarGraficas();
            
            // Animación de las tarjetas de estadísticas
            document.querySelectorAll('.stat-card-main').forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 200);
            });
            
            // Actualizar estadísticas cada 5 minutos
            setInterval(() => {
                console.log('Actualizando estadísticas...');
                // Aquí podrías hacer una llamada AJAX para actualizar datos
            }, 300000);
        });
        
        // Efecto hover para tarjetas de estadísticas
        document.querySelectorAll('.stat-card-main').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 15px 35px rgba(46, 125, 50, 0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 10px 30px rgba(46, 125, 50, 0.08)';
            });
        });
    </script>
</body>
</html>