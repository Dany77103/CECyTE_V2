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
function obtenerDatos($query, $params = []) {
    global $con;
    try {
        $stmt = $con->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Función para ejecutar consultas de actualización
function ejecutarConsulta($query, $params = []) {
    global $con;
    try {
        $stmt = $con->prepare($query);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        return false;
    }
}

session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Verificar permisos (ejemplo: solo administradores pueden gestionar estadísticas)
if ($_SESSION['rol'] !== 'admin') {
    header('Location: acceso_denegado.php');
    exit;
}

// Procesar filtros
$filtros = [];
$filtro_semestre = isset($_GET['semestre']) ? intval($_GET['semestre']) : '';
$filtro_grupo = isset($_GET['grupo']) ? $_GET['grupo'] : '';
$filtro_carrera = isset($_GET['carrera']) ? $_GET['carrera'] : '';
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '';
$filtro_fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$filtro_fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Construir condiciones WHERE dinámicas
$condiciones = [];
$parametros = [];

if (!empty($filtro_semestre)) {
    $condiciones[] = "a.id_semestre = ?";
    $parametros[] = $filtro_semestre;
}

if (!empty($filtro_grupo)) {
    $condiciones[] = "a.id_grupo = ?";
    $parametros[] = $filtro_grupo;
}

if (!empty($filtro_carrera)) {
    $condiciones[] = "c.id_carrera = ?";
    $parametros[] = $filtro_carrera;
}

$where = "";
if (!empty($condiciones)) {
    $where = "WHERE " . implode(" AND ", $condiciones);
}

// CONSULTAS CON FILTROS
$queryAlumnosFiltrados = "SELECT COUNT(*) AS total FROM alumnos a 
                         LEFT JOIN carreras c ON a.id_carrera = c.id_carrera 
                         $where";

$queryCalificacionesFiltradas = "SELECT AVG(cal.calificacion) as promedio, 
                                COUNT(*) as total 
                                FROM calificaciones cal
                                INNER JOIN alumnos a ON cal.id_alumno = a.id_alumno
                                LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
                                $where";

// OBTENER DATOS CON FILTROS
$alumnosFiltrados = obtenerDatos($queryAlumnosFiltrados, $parametros)[0]['total'] ?? 0;
$calificacionesFiltradas = obtenerDatos($queryCalificacionesFiltradas, $parametros)[0] ?? ['promedio' => 0, 'total' => 0];

// Obtener listas para filtros
$semestres = obtenerDatos("SELECT DISTINCT id_semestre FROM alumnos ORDER BY id_semestre");
$grupos = obtenerDatos("SELECT DISTINCT id_grupo FROM alumnos WHERE id_grupo IS NOT NULL ORDER BY id_grupo");
$carreras = obtenerDatos("SELECT id_carrera, nombre_carrera FROM carreras ORDER BY nombre_carrera");
$periodos = obtenerDatos("SELECT DISTINCT YEAR(fecha) as año, MONTH(fecha) as mes FROM asistencias_qr ORDER BY año DESC, mes DESC LIMIT 12");

// Obtener estadísticas avanzadas
$queryTendenciaAlumnos = "SELECT 
    YEAR(fecha_registro) as año,
    MONTH(fecha_registro) as mes,
    COUNT(*) as cantidad
    FROM alumnos 
    WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY YEAR(fecha_registro), MONTH(fecha_registro)
    ORDER BY año DESC, mes DESC
    LIMIT 12";

$queryDesercion = "SELECT 
    s.nombre_semestre,
    COUNT(*) as total,
    SUM(CASE WHEN haa.id_estatus = (SELECT id_estatus FROM estatus WHERE tipoEstatus = 'baja') THEN 1 ELSE 0 END) as bajas,
    ROUND((SUM(CASE WHEN haa.id_estatus = (SELECT id_estatus FROM estatus WHERE tipoEstatus = 'baja') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as porcentaje
    FROM alumnos a
    LEFT JOIN semestres s ON a.id_semestre = s.id_semestre
    LEFT JOIN historialacademicoalumnos haa ON a.id_alumno = haa.id_alumno
    GROUP BY s.nombre_semestre
    ORDER BY s.nombre_semestre";

$queryEficienciaTerminal = "SELECT 
    c.nombre_carrera,
    COUNT(DISTINCT a.id_alumno) as inscritos,
    SUM(CASE WHEN a.id_estatus = (SELECT id_estatus FROM estatus WHERE tipoEstatus = 'egresado') THEN 1 ELSE 0 END) as egresados,
    ROUND((SUM(CASE WHEN a.id_estatus = (SELECT id_estatus FROM estatus WHERE tipoEstatus = 'egresado') THEN 1 ELSE 0 END) / COUNT(DISTINCT a.id_alumno)) * 100, 2) as eficiencia
    FROM alumnos a
    LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
    WHERE c.nombre_carrera IS NOT NULL
    GROUP BY c.nombre_carrera
    ORDER BY eficiencia DESC";

$queryRendimientoDocente = "SELECT 
    m.nombre,
    COUNT(DISTINCT cal.id_alumno) as alumnos_atendidos,
    AVG(cal.calificacion) as promedio_calificaciones,
    COUNT(CASE WHEN cal.calificacion >= 8 THEN 1 END) as aprobados,
    COUNT(CASE WHEN cal.calificacion < 8 THEN 1 END) as reprobados
    FROM calificaciones cal
    INNER JOIN maestros m ON cal.id_maestro = m.id_maestro
    GROUP BY m.id_maestro, m.nombre
    HAVING alumnos_atendidos > 0
    ORDER BY promedio_calificaciones DESC
    LIMIT 15";

$queryAsistenciaPorDia = "SELECT 
    DAYNAME(fecha) as dia_semana,
    AVG(CASE WHEN hora_entrada IS NOT NULL THEN 1 ELSE 0 END) * 100 as asistencia_promedio
    FROM asistencias_qr
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DAYNAME(fecha), DAYOFWEEK(fecha)
    ORDER BY DAYOFWEEK(fecha)";

// Ejecutar consultas avanzadas
$tendenciaAlumnos = obtenerDatos($queryTendenciaAlumnos);
$desercion = obtenerDatos($queryDesercion);
$eficienciaTerminal = obtenerDatos($queryEficienciaTerminal);
$rendimientoDocente = obtenerDatos($queryRendimientoDocente);
$asistenciaPorDia = obtenerDatos($queryAsistenciaPorDia);

// Preparar datos para gráficas avanzadas
$tendenciaLabels = array_column($tendenciaAlumnos, 'mes_anio');
$tendenciaData = array_column($tendenciaAlumnos, 'cantidad');

$desercionLabels = array_column($desercion, 'nombre_semestre');
$desercionData = array_column($desercion, 'porcentaje');

$eficienciaLabels = array_column($eficienciaTerminal, 'nombre_carrera');
$eficienciaData = array_column($eficienciaTerminal, 'eficiencia');

$docenteLabels = array_column($rendimientoDocente, 'nombre');
$docentePromedio = array_column($rendimientoDocente, 'promedio_calificaciones');

$diasLabels = array_column($asistenciaPorDia, 'dia_semana');
$asistenciaDiasData = array_column($asistenciaPorDia, 'asistencia_promedio');

// Calcular métricas clave
$totalAlumnos = obtenerDatos("SELECT COUNT(*) as total FROM alumnos")[0]['total'] ?? 0;
$totalMaestros = obtenerDatos("SELECT COUNT(*) as total FROM maestros")[0]['total'] ?? 0;

$indiceDesercion = 0;
if (!empty($desercion)) {
    $totalDesercion = array_sum(array_column($desercion, 'bajas'));
    $indiceDesercion = $totalAlumnos > 0 ? round(($totalDesercion / $totalAlumnos) * 100, 2) : 0;
}

$indiceEficiencia = 0;
if (!empty($eficienciaTerminal)) {
    $sumaEficiencia = array_sum(array_column($eficienciaTerminal, 'eficiencia'));
    $indiceEficiencia = count($eficienciaTerminal) > 0 ? round($sumaEficiencia / count($eficienciaTerminal), 2) : 0;
}

// Convertir datos a JSON para JavaScript
$dataJSON = json_encode([
    'tendenciaLabels' => $tendenciaLabels,
    'tendenciaData' => $tendenciaData,
    'desercionLabels' => $desercionLabels,
    'desercionData' => $desercionData,
    'eficienciaLabels' => $eficienciaLabels,
    'eficienciaData' => $eficienciaData,
    'docenteLabels' => $docenteLabels,
    'docentePromedio' => $docentePromedio,
    'diasLabels' => $diasLabels,
    'asistenciaDiasData' => $asistenciaDiasData,
    'totalAlumnos' => $totalAlumnos,
    'totalMaestros' => $totalMaestros,
    'indiceDesercion' => $indiceDesercion,
    'indiceEficiencia' => $indiceEficiencia,
    'alumnosFiltrados' => $alumnosFiltrados,
    'promedioFiltrado' => round($calificacionesFiltradas['promedio'], 2)
]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CECYTE - Gesti&oacute;n de Estad&iacute;sticas</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Date Range Picker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed: 80px;
            --verde-oscuro: #1a5330;
            --verde-principal: #2e7d32;
            --verde-medio: #4caf50;
            --verde-claro: #8bc34a;
            --verde-brillante: #81c784;
            --text-color: #ecf0f1;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
        }
        
        .main-container {
            display: flex;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            position: relative;
        }
        
        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--verde-principal), #1b5e20);
            color: var(--text-color);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 3px 0 15px rgba(26, 83, 48, 0.2);
            display: flex;
            flex-direction: column;
            left: 0;
            top: 0;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }
        
        .sidebar-header {
            padding: 20px 15px;
            background: linear-gradient(90deg, var(--verde-oscuro), #2e7d32);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }
        
        .logo-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            transition: all 0.3s ease;
            flex-grow: 1;
        }
        
        .sidebar.collapsed .logo-name {
            opacity: 0;
            width: 0;
            display: none;
        }
        
        #btn-toggle {
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            background: rgba(255,255,255,0.1);
            padding: 8px;
            border-radius: 5px;
            transition: all 0.3s ease;
            border: none;
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #btn-toggle:hover {
            background: var(--verde-medio);
            transform: rotate(90deg);
        }
        
        /* CONTENIDO PRINCIPAL */
        .content-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
            overflow-x: hidden;
        }
        
        .sidebar.collapsed ~ .content-wrapper {
            margin-left: var(--sidebar-collapsed);
            width: calc(100% - var(--sidebar-collapsed));
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
        
        /* Contenedor principal */
        .management-container {
            padding: 30px;
        }
        
        .page-title {
            color: var(--verde-oscuro);
            margin-bottom: 30px;
            font-weight: 700;
            border-bottom: 3px solid var(--verde-claro);
            padding-bottom: 15px;
        }
        
        /* Panel de control de estadísticas */
        .stats-control-panel {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        .control-panel-title {
            color: var(--verde-oscuro);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        /* Filtros avanzados */
        .advanced-filters {
            background: #f8fff8;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #d4edd4;
        }
        
        .filter-section-title {
            color: var(--verde-oscuro);
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--verde-claro);
        }
        
        /* Indicadores de rendimiento */
        .performance-indicators {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .indicator-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.05);
            transition: all 0.3s ease;
            border-top: 4px solid;
        }
        
        .indicator-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(46, 125, 50, 0.1);
        }
        
        .indicator-card.desercion {
            border-top-color: #f44336;
        }
        
        .indicator-card.eficiencia {
            border-top-color: #4caf50;
        }
        
        .indicator-card.rendimiento {
            border-top-color: #2196f3;
        }
        
        .indicator-card.asistencia {
            border-top-color: #ff9800;
        }
        
        .indicator-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .indicator-card.desercion .indicator-value {
            color: #f44336;
        }
        
        .indicator-card.eficiencia .indicator-value {
            color: #4caf50;
        }
        
        .indicator-card.rendimiento .indicator-value {
            color: #2196f3;
        }
        
        .indicator-card.asistencia .indicator-value {
            color: #ff9800;
        }
        
        .indicator-label {
            color: #5d6d5f;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Gráficas de gestión */
        .management-charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 768px) {
            .management-charts-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .management-chart-wrapper {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        .management-chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .management-chart-title {
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
        
        /* Tablas de análisis */
        .analysis-table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        .analysis-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .analysis-table thead {
            background: var(--verde-oscuro);
            color: white;
        }
        
        .analysis-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .analysis-table tbody tr {
            border-bottom: 1px solid #dee2e6;
            transition: background 0.3s ease;
        }
        
        .analysis-table tbody tr:hover {
            background: #f1f8e9;
        }
        
        .analysis-table td {
            padding: 15px;
        }
        
        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-export {
            background: var(--verde-principal);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-export:hover {
            background: var(--verde-oscuro);
            color: white;
        }
        
        .btn-generate {
            background: #2196f3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-generate:hover {
            background: #0b7dda;
        }
        
        /* Alertas y notificaciones */
        .alert-warning {
            background-color: #fff3e0;
            border-color: #ffe0b2;
            color: #e65100;
        }
        
        .alert-info {
            background-color: #e8f5e9;
            border-color: #c8e6c9;
            color: #2e7d32;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: var(--sidebar-collapsed);
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
                width: var(--sidebar-width);
            }
            
            .content-wrapper {
                margin-left: 0;
                width: 100%;
            }
            
            .sidebar.show ~ .content-wrapper {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }
        }
        
        @media (max-width: 768px) {
            .management-container {
                padding: 15px;
            }
            
            .performance-indicators {
                grid-template-columns: 1fr;
            }
            
            .management-chart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
        
        /* Badges para estados */
        .badge-desercion {
            background-color: #ffcdd2;
            color: #c62828;
        }
        
        .badge-eficiencia {
            background-color: #c8e6c9;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar (similar al de estadisticas.php) -->
       <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <div class="logo-name">GESTIÓN ESTADÍSTICAS</div>
                    <i class='bx bx-menu' id="btn-toggle"></i>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li class="nav-item">
                    <a href="main.php" class="nav-link">
                        <i class='bx bx-home-alt-2'></i>
                        <span class="link-text">Inicio</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="gestion_alumnos.php" class="nav-link">
                        <i class='bx bx-user'></i>
                        <span class="link-text">Alumnos</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="gestion_maestros.php" class="nav-link">
                        <i class='bx bx-chalkboard'></i>
                        <span class="link-text">Maestros</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="gestion_carreras.php" class="nav-link">
                        <i class='bx bx-book'></i>
                        <span class="link-text">Carreras</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="estadisticas.php" class="nav-link">
                        <i class='bx bx-chart'></i>
                        <span class="link-text">Estadísticas</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="gestion_estadisticas.php" class="nav-link active">
                        <i class='bx bx-line-chart'></i>
                        <span class="link-text">Gestión Estadísticas</span>
                    </a>
                </li>
                
                <li class="nav-item separator">
                    <hr>
                </li>
                
                <li class="nav-item">
                    <a href="perfil.php" class="nav-link">
                        <i class='bx bx-user-circle'></i>
                        <span class="link-text">Mi Perfil</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class='bx bx-log-out'></i>
                        <span class="link-text">Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Contenido principal -->
        <div class="content-wrapper">
            <header class="main-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Gestión Avanzada de Estadísticas - CECyTE</h5>
                    
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-3"><?php echo $_SESSION['username'] ?? 'Administrador'; ?></span>
                        <div class="dropdown">
                            <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class='bx bx-cog'></i> Opciones
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="configuracion_estadisticas.php">Configurar Indicadores</a></li>
                                <li><a class="dropdown-item" href="reportes_personalizados.php">Reportes Personalizados</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="management-container">
                <h1 class="page-title">
                    <i class='bx bx-line-chart'></i> Panel de Gestión de Estadísticas
                </h1>
                
                <!-- Panel de control -->
                <div class="stats-control-panel">
                    <h3 class="control-panel-title">
                        <i class="fas fa-sliders-h"></i> Control de Estadísticas
                    </h3>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Use los filtros para analizar datos específicos. Los indicadores se actualizarán automáticamente.
                    </div>
                    
                    <!-- Filtros avanzados -->
                    <div class="advanced-filters">
                        <h5 class="filter-section-title">
                            <i class="fas fa-filter"></i> Filtros Avanzados
                        </h5>
                        
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Semestre</label>
                                <select class="form-select" name="semestre">
                                    <option value="">Todos los semestres</option>
                                    <?php foreach ($semestres as $sem): ?>
                                        <option value="<?php echo $sem['id_semestre']; ?>" 
                                            <?php echo $filtro_semestre == $sem['id_semestre'] ? 'selected' : ''; ?>>
                                            Semestre <?php echo $sem['id_semestre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Grupo</label>
                                <select class="form-select" name="grupo">
                                    <option value="">Todos los grupos</option>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <option value="<?php echo $grupo['id_grupo']; ?>" 
                                            <?php echo $filtro_grupo == $grupo['id_grupo'] ? 'selected' : ''; ?>>
                                            Grupo <?php echo $grupo['id_grupo']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Carrera</label>
                                <select class="form-select" name="carrera">
                                    <option value="">Todas las carreras</option>
                                    <?php foreach ($carreras as $carrera): ?>
                                        <option value="<?php echo $carrera['id_carrera']; ?>" 
                                            <?php echo $filtro_carrera == $carrera['id_carrera'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($carrera['nombre_carrera']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Período</label>
                                <select class="form-select" name="periodo">
                                    <option value="">Todos los períodos</option>
                                    <?php foreach ($periodos as $periodo): 
                                        $periodoTexto = $periodo['año'] . '-' . str_pad($periodo['mes'], 2, '0', STR_PAD_LEFT);
                                    ?>
                                        <option value="<?php echo $periodoTexto; ?>" 
                                            <?php echo $filtro_periodo == $periodoTexto ? 'selected' : ''; ?>>
                                            <?php echo $periodoTexto; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Rango de Fechas</label>
                                <input type="text" class="form-control" name="daterange" 
                                       value="<?php echo $filtro_fecha_inicio && $filtro_fecha_fin ? 
                                       $filtro_fecha_inicio . ' - ' . $filtro_fecha_fin : ''; ?>" />
                                <input type="hidden" name="fecha_inicio" id="fecha_inicio" value="<?php echo $filtro_fecha_inicio; ?>">
                                <input type="hidden" name="fecha_fin" id="fecha_fin" value="<?php echo $filtro_fecha_fin; ?>">
                            </div>
                            
                            <div class="col-md-12 mt-3">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-filter"></i> Aplicar Filtros
                                </button>
                                <a href="gestion_estadisticas.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Limpiar Filtros
                                </a>
                                
                                <div class="float-end">
                                    <span class="badge bg-info me-2">
                                        Resultados filtrados: <?php echo $alumnosFiltrados; ?> alumnos
                                    </span>
                                    <span class="badge bg-warning">
                                        Promedio: <?php echo round($calificacionesFiltradas['promedio'], 2); ?>
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Indicadores de rendimiento -->
                    <div class="performance-indicators">
                        <div class="indicator-card desercion">
                            <i class="fas fa-user-slash fa-2x text-danger"></i>
                            <div class="indicator-value"><?php echo $indiceDesercion; ?>%</div>
                            <div class="indicator-label">Índice de Deserción</div>
                        </div>
                        
                        <div class="indicator-card eficiencia">
                            <i class="fas fa-graduation-cap fa-2x text-success"></i>
                            <div class="indicator-value"><?php echo $indiceEficiencia; ?>%</div>
                            <div class="indicator-label">Eficiencia Terminal</div>
                        </div>
                        
                        <div class="indicator-card rendimiento">
                            <i class="fas fa-chart-line fa-2x text-primary"></i>
                            <div class="indicator-value"><?php echo $calificacionesFiltradas['promedio']; ?></div>
                            <div class="indicator-label">Rendimiento Académico</div>
                        </div>
                        
                        <div class="indicator-card asistencia">
                            <i class="fas fa-calendar-check fa-2x text-warning"></i>
                            <div class="indicator-value">
                                <?php 
                                $asistenciaPromedio = !empty($asistenciaDiasData) ? 
                                    round(array_sum($asistenciaDiasData) / count($asistenciaDiasData), 1) : 0;
                                echo $asistenciaPromedio;
                                ?>%
                            </div>
                            <div class="indicator-label">Asistencia Promedio</div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficas de gestión -->
                <div class="management-charts-grid">
                    <!-- Gráfica 1: Tendencia de matrícula -->
                    <div class="management-chart-wrapper">
                        <div class="management-chart-header">
                            <h5 class="management-chart-title">Tendencia de Matrícula (Últimos 12 meses)</h5>
                            <div class="chart-actions">
                                <button class="btn-chart-action" onclick="descargarGrafica('chartTendencia')">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartTendencia"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfica 2: Deserción por semestre -->
                    <div class="management-chart-wrapper">
                        <div class="management-chart-header">
                            <h5 class="management-chart-title">Deserción por Semestre</h5>
                            <div class="chart-actions">
                                <button class="btn-chart-action" onclick="descargarGrafica('chartDesercion')">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartDesercion"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfica 3: Eficiencia terminal por carrera -->
                    <div class="management-chart-wrapper">
                        <div class="management-chart-header">
                            <h5 class="management-chart-title">Eficiencia Terminal por Carrera</h5>
                            <div class="chart-actions">
                                <button class="btn-chart-action" onclick="descargarGrafica('chartEficiencia')">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartEficiencia"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfica 4: Asistencia por día de la semana -->
                    <div class="management-chart-wrapper">
                        <div class="management-chart-header">
                            <h5 class="management-chart-title">Asistencia por Día de la Semana</h5>
                            <div class="chart-actions">
                                <button class="btn-chart-action" onclick="descargarGrafica('chartAsistenciaDias')">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartAsistenciaDias"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Análisis de rendimiento docente -->
                <div class="analysis-table-container">
                    <h4 class="control-panel-title mb-4">
                        <i class="fas fa-chalkboard-teacher"></i> Análisis de Rendimiento Docente
                    </h4>
                    
                    <div class="table-responsive">
                        <table class="analysis-table">
                            <thead>
                                <tr>
                                    <th>Docente</th>
                                    <th>Alumnos Atendidos</th>
                                    <th>Promedio Calificaciones</th>
                                    <th>Aprobados</th>
                                    <th>Reprobados</th>
                                    <th>Tasa de Aprobación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rendimientoDocente as $docente): 
                                    $tasaAprobacion = $docente['alumnos_atendidos'] > 0 ? 
                                        round(($docente['aprobados'] / $docente['alumnos_atendidos']) * 100, 2) : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($docente['nombre']); ?></td>
                                    <td><?php echo $docente['alumnos_atendidos']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $docente['promedio_calificaciones'] >= 8 ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo round($docente['promedio_calificaciones'], 2); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $docente['aprobados']; ?></td>
                                    <td><?php echo $docente['reprobados']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?php echo $tasaAprobacion >= 80 ? 'bg-success' : ($tasaAprobacion >= 60 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                 role="progressbar" style="width: <?php echo $tasaAprobacion; ?>%">
                                                <?php echo $tasaAprobacion; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Análisis de deserción -->
                <div class="analysis-table-container">
                    <h4 class="control-panel-title mb-4">
                        <i class="fas fa-exclamation-triangle"></i> Análisis de Deserción
                    </h4>
                    
                    <div class="table-responsive">
                        <table class="analysis-table">
                            <thead>
                                <tr>
                                    <th>Semestre</th>
                                    <th>Total Alumnos</th>
                                    <th>Bajas</th>
                                    <th>Porcentaje Deserción</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($desercion as $item): 
                                    $estado = $item['porcentaje'] > 15 ? 'Crítico' : 
                                             ($item['porcentaje'] > 10 ? 'Alto' : 
                                             ($item['porcentaje'] > 5 ? 'Moderado' : 'Bajo'));
                                    $badgeClass = $item['porcentaje'] > 15 ? 'badge-desercion' : 
                                                 ($item['porcentaje'] > 10 ? 'bg-warning' : 
                                                 ($item['porcentaje'] > 5 ? 'bg-info' : 'badge-eficiencia'));
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nombre_semestre']); ?></td>
                                    <td><?php echo $item['total']; ?></td>
                                    <td><?php echo $item['bajas']; ?></td>
                                    <td><?php echo $item['porcentaje']; ?>%</td>
                                    <td>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo $estado; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="action-buttons">
                    <button class="btn-export" onclick="generarReporteCompleto()">
                        <i class="fas fa-file-pdf"></i> Generar Reporte Completo
                    </button>
                    
                    <button class="btn-export" onclick="exportarDatosExcel()">
                        <i class="fas fa-file-excel"></i> Exportar a Excel
                    </button>
                    
                    <button class="btn-generate" onclick="generarAlertas()">
                        <i class="fas fa-bell"></i> Generar Alertas
                    </button>
                    
                    <button class="btn-generate" onclick="mostrarPronosticos()">
                        <i class="fas fa-chart-line"></i> Ver Pronósticos
                    </button>
                </div>
                
                <!-- Información del sistema -->
                <div class="alert alert-info mt-4">
                    <i class="fas fa-database me-2"></i>
                    <strong>Sistema de Gestión de Estadísticas</strong> | 
                    Última actualización: <?php echo date('d/m/Y H:i:s'); ?> | 
                    Datos analizados: <?php echo $totalAlumnos; ?> alumnos, <?php echo $totalMaestros; ?> maestros
                </div>
            </main>
            
            <!-- Footer -->
            <footer class="bg-success text-white text-center py-3 mt-5">
                <div class="container">
                    <p class="mb-1">SGA-CECyTE SANTA CATARINA N.L. - Módulo de Gestión de Estadísticas</p>
                    <p class="mb-0">© <?php echo date("Y"); ?> Sistema de Gestión Académica Avanzada</p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        // Datos desde PHP
        const data = <?php echo $dataJSON; ?>;
        
        // Inicializar date range picker
        $(function() {
            $('input[name="daterange"]').daterangepicker({
                opens: 'left',
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' - ',
                    applyLabel: 'Aplicar',
                    cancelLabel: 'Cancelar',
                    fromLabel: 'Desde',
                    toLabel: 'Hasta',
                    customRangeLabel: 'Personalizado',
                    daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                    monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                    firstDay: 1
                }
            }, function(start, end, label) {
                $('#fecha_inicio').val(start.format('YYYY-MM-DD'));
                $('#fecha_fin').val(end.format('YYYY-MM-DD'));
            });
        });
        
        // Variables para instancias de gráficas
        let chartTendencia, chartDesercion, chartEficiencia, chartAsistenciaDias;
        
        // Función para inicializar todas las gráficas
        function inicializarGraficas() {
            // Destruir gráficas existentes
            if (chartTendencia) chartTendencia.destroy();
            if (chartDesercion) chartDesercion.destroy();
            if (chartEficiencia) chartEficiencia.destroy();
            if (chartAsistenciaDias) chartAsistenciaDias.destroy();
            
            // Crear nuevas gráficas
            crearGraficaTendencia();
            crearGraficaDesercion();
            crearGraficaEficiencia();
            crearGraficaAsistenciaDias();
        }
        
        // Gráfica de tendencia
        function crearGraficaTendencia() {
            const ctx = document.getElementById('chartTendencia').getContext('2d');
            chartTendencia = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.tendenciaLabels.map((_, i) => `Mes ${i+1}`),
                    datasets: [{
                        label: 'Matrícula',
                        data: data.tendenciaData,
                        borderColor: 'rgba(46, 125, 50, 1)',
                        backgroundColor: 'rgba(46, 125, 50, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: getChartOptions('Tendencia de Matrícula', {
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
                                text: 'Meses'
                            }
                        }
                    }
                })
            });
        }
        
        // Gráfica de deserción
        function crearGraficaDesercion() {
            const ctx = document.getElementById('chartDesercion').getContext('2d');
            chartDesercion = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.desercionLabels,
                    datasets: [{
                        label: 'Porcentaje de Deserción',
                        data: data.desercionData,
                        backgroundColor: data.desercionData.map(val => 
                            val > 15 ? 'rgba(244, 67, 54, 0.7)' : 
                            val > 10 ? 'rgba(255, 152, 0, 0.7)' : 
                            val > 5 ? 'rgba(33, 150, 243, 0.7)' : 
                            'rgba(76, 175, 80, 0.7)'
                        ),
                        borderColor: data.desercionData.map(val => 
                            val > 15 ? 'rgba(244, 67, 54, 1)' : 
                            val > 10 ? 'rgba(255, 152, 0, 1)' : 
                            val > 5 ? 'rgba(33, 150, 243, 1)' : 
                            'rgba(76, 175, 80, 1)'
                        ),
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Deserción por Semestre', {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Porcentaje (%)'
                            }
                        }
                    }
                })
            });
        }
        
        // Gráfica de eficiencia
        function crearGraficaEficiencia() {
            const ctx = document.getElementById('chartEficiencia').getContext('2d');
            chartEficiencia = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.eficienciaLabels,
                    datasets: [{
                        label: 'Eficiencia Terminal (%)',
                        data: data.eficienciaData,
                        backgroundColor: 'rgba(33, 150, 243, 0.7)',
                        borderColor: 'rgba(33, 150, 243, 1)',
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Eficiencia Terminal por Carrera', {
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Porcentaje (%)'
                            }
                        }
                    }
                })
            });
        }
        
        // Gráfica de asistencia por días
        function crearGraficaAsistenciaDias() {
            const ctx = document.getElementById('chartAsistenciaDias').getContext('2d');
            chartAsistenciaDias = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.diasLabels,
                    datasets: [{
                        label: 'Asistencia Promedio (%)',
                        data: data.asistenciaDiasData,
                        borderColor: 'rgba(255, 152, 0, 1)',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: getChartOptions('Asistencia por Día de la Semana', {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Asistencia (%)'
                            }
                        }
                    }
                })
            });
        }
        
        // Función auxiliar para opciones de gráfica
        function getChartOptions(title, customOptions = {}) {
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: title,
                        font: {
                            size: 16
                        }
                    }
                }
            };
            
            return { ...defaultOptions, ...customOptions };
        }
        
        // Función para descargar gráfica
        function descargarGrafica(chartId) {
            const link = document.createElement('a');
            link.download = `grafica_${chartId}_${new Date().toISOString().slice(0,10)}.png`;
            link.href = document.getElementById(chartId).toDataURL('image/png');
            link.click();
        }
        
        // Funciones de gestión
        function generarReporteCompleto() {
            if (confirm('¿Generar reporte completo de estadísticas?')) {
                alert('Reporte en proceso de generación...');
                // Aquí iría la lógica para generar el reporte PDF
            }
        }
        
        function exportarDatosExcel() {
            if (confirm('¿Exportar datos a Excel?')) {
                // Crear datos para exportación
                const datosExportar = {
                    'Indicadores Principales': [
                        ['Índice de Deserción', data.indiceDesercion + '%'],
                        ['Eficiencia Terminal', data.indiceEficiencia + '%'],
                        ['Total Alumnos', data.totalAlumnos],
                        ['Total Maestros', data.totalMaestros]
                    ],
                    'Deserción por Semestre': data.desercionLabels.map((label, i) => [
                        label, 
                        data.desercionData[i] + '%'
                    ])
                };
                
                alert('Datos preparados para exportación a Excel');
                // Aquí iría la lógica para exportar a Excel
            }
        }
        
        function generarAlertas() {
            const alertas = [];
            
            // Analizar deserción
            if (data.indiceDesercion > 10) {
                alertas.push(`?? ALERTA: Índice de deserción alto (${data.indiceDesercion}%)`);
            }
            
            // Analizar eficiencia
            if (data.indiceEficiencia < 70) {
                alertas.push(`?? ALERTA: Eficiencia terminal baja (${data.indiceEficiencia}%)`);
            }
            
            // Mostrar alertas
            if (alertas.length > 0) {
                alert('Alertas generadas:\n\n' + alertas.join('\n'));
            } else {
                alert('? No se detectaron problemas críticos.');
            }
        }
        
        function mostrarPronosticos() {
            const pronosticos = `
            ?? PRONÓSTICOS PARA EL PRÓXIMO PERÍODO:
            
            1. Matrícula estimada: ${Math.round(data.totalAlumnos * 1.05)} alumnos (+5%)
            2. Eficiencia terminal proyectada: ${(data.indiceEficiencia * 1.02).toFixed(2)}%
            3. Deserción esperada: ${(data.indiceDesercion * 0.95).toFixed(2)}%
            
            ?? RECOMENDACIONES:
            - Implementar programa de retención estudiantil
            - Fortalecer tutorías académicas
            - Mejorar seguimiento de casos de riesgo
            `;
            
            alert(pronosticos);
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
        
        // Inicializar gráficas al cargar
        document.addEventListener('DOMContentLoaded', function() {
            inicializarGraficas();
            
            // Efecto de animación para indicadores
            document.querySelectorAll('.indicator-card').forEach((card, index) => {
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
        });
    </script>
</body>
</html>