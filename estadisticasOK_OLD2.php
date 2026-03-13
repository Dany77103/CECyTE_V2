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

// CONSULTAS PRINCIPALES (EXISTENTES)
$queryAlumnosActivos = "SELECT COUNT(*) AS alumnosActivos FROM historialacademicoalumnos haa
                        INNER JOIN estatus e ON e.id_estatus = haa.id_estatus
                        WHERE e.tipoEstatus = 'activo'";

$queryAlumnosBajaTemporal = "SELECT COUNT(*) AS alumnosBajaTemporal FROM historialacademicoalumnos haa
                             INNER JOIN estatus e ON e.id_estatus = haa.id_estatus
                             WHERE e.tipoEstatus = 'baja temporal'";

$queryMaestrosActivos = "SELECT COUNT(*) AS maestrosActivos FROM datoslaboralesmaestros dlm
                         INNER JOIN estatus e ON e.id_estatus = dlm.id_estatus
                         WHERE e.tipoEstatus = 'activo'";

// NUEVAS CONSULTAS PARA ESTADÍSTICAS ADICIONALES

// 1. ESTADÍSTICAS GENERALES
$queryTotalAlumnos = "SELECT COUNT(*) AS total FROM alumnos";
$queryTotalMaestros = "SELECT COUNT(*) AS total FROM maestros";
$queryTotalCalificaciones = "SELECT COUNT(*) AS total FROM calificaciones";

// 2. GÉNERO
$queryAlumnosPorGenero = "SELECT 
                            SUM(CASE WHEN genero = 'Masculino' THEN 1 ELSE 0 END) as masculino,
                            SUM(CASE WHEN genero = 'Femenino' THEN 1 ELSE 0 END) as femenino,
                            SUM(CASE WHEN genero IS NULL OR genero = '' THEN 1 ELSE 0 END) as no_especificado
                          FROM alumnos";

$queryMaestrosPorGenero = "SELECT 
                            SUM(CASE WHEN genero = 'Masculino' THEN 1 ELSE 0 END) as masculino,
                            SUM(CASE WHEN genero = 'Femenino' THEN 1 ELSE 0 END) as femenino,
                            SUM(CASE WHEN genero IS NULL OR genero = '' THEN 1 ELSE 0 END) as no_especificado
                          FROM maestros";

// 3. PROMEDIOS Y CALIFICACIONES
$queryPromedioCalificaciones = "SELECT AVG(calificacion) as promedio FROM calificaciones";
$queryCalificacionesPorRango = "SELECT 
    SUM(CASE WHEN calificacion >= 9 THEN 1 ELSE 0 END) as excelente,
    SUM(CASE WHEN calificacion >= 7 AND calificacion < 9 THEN 1 ELSE 0 END) as bueno,
    SUM(CASE WHEN calificacion >= 6 AND calificacion < 7 THEN 1 ELSE 0 END) as regular,
    SUM(CASE WHEN calificacion < 6 THEN 1 ELSE 0 END) as reprobado
FROM calificaciones";

// 4. GRUPOS Y SEMESTRES
$queryAlumnosPorGrupo = "SELECT id_grupo, COUNT(*) AS cantidad FROM alumnos GROUP BY id_grupo ORDER BY cantidad DESC LIMIT 10";
$queryAlumnosPorSemestre = " SELECT id_semestre, COUNT(*) AS cantidad FROM alumnos GROUP BY id_semestre ORDER BY id_semestre;";

// 5. ASISTENCIA
$queryAsistenciaPromedio = "SELECT 
                            DATE_FORMAT(fecha, '%Y-%m') as mes,
                            AVG(CASE WHEN hora_entrada IS NOT NULL THEN 1 ELSE 0 END) * 100 as asistencia_promedio
                            FROM asistencias_qr 
                            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                            GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                            ORDER BY mes DESC";

// 6. NUEVAS ESTADÍSTICAS: DISCAPACIDAD
$queryAlumnosConDiscapacidad = "
 SELECT COUNT(*) AS total FROM alumnos WHERE id_discapacidad = 1";
$queryTiposDiscapacidad = "SELECT a.id_discapacidad, COUNT(*) AS cantidad FROM alumnos a
			 LEFT JOIN discapacidades d ON a.id_discapacidad = d.id_discapacidad
                          WHERE a.id_discapacidad = 1  AND d.tipo_discapacidad IS NOT NULL 
                          GROUP BY d.tipo_discapacidad ORDER BY cantidad DESC";

// 7. NUEVAS ESTADÍSTICAS: DATOS LABORALES MAESTROS
$queryMaestrosPorContrato = "SELECT tipo_contrato, COUNT(*) AS cantidad FROM datoslaboralesmaestros 
                            GROUP BY tipo_contrato ORDER BY cantidad DESC";
$queryMaestrosPorDepartamento = "SELECT departamento, COUNT(*) as cantidad FROM datoslaboralesmaestros 
                                WHERE departamento IS NOT NULL GROUP BY departamento ORDER BY cantidad DESC";

// 8. NUEVAS ESTADÍSTICAS: EDADES DE ALUMNOS
$queryAlumnosPorEdad = "SELECT 
    CASE 
        WHEN edad < 15 THEN 'Menor 15'
        WHEN edad BETWEEN 15 AND 17 THEN '15-17'
        WHEN edad BETWEEN 18 AND 20 THEN '18-20'
        WHEN edad BETWEEN 21 AND 23 THEN '21-23'
        WHEN edad > 23 THEN 'Mayor 23'
        ELSE 'No especificada'
    END as rango_edad,
    COUNT(*) as cantidad
FROM alumnos 
GROUP BY rango_edad 
ORDER BY 
    CASE rango_edad
        WHEN 'Menor 15' THEN 1
        WHEN '15-17' THEN 2
        WHEN '18-20' THEN 3
        WHEN '21-23' THEN 4
        WHEN 'Mayor 23' THEN 5
        ELSE 6
    END";

// 9. NUEVAS ESTADÍSTICAS: ALUMNOS POR TURNO
$queryAlumnosPorTurno = "SELECT turno, COUNT(*) as cantidad FROM alumnos WHERE turno IS NOT NULL GROUP BY turno ORDER BY cantidad DESC";

// 10. NUEVAS ESTADÍSTICAS: ESTADO CIVIL
$queryAlumnosEstadoCivil = "SELECT estado_civil, COUNT(*) as cantidad FROM alumnos 
                           WHERE estado_civil IS NOT NULL GROUP BY estado_civil ORDER BY cantidad DESC";

// OBTENER DATOS PRINCIPALES (EXISTENTES)
$alumnosActivos = obtenerDatos($queryAlumnosActivos)[0]['alumnosActivos'] ?? 0;
$alumnosBajaTemporal = obtenerDatos($queryAlumnosBajaTemporal)[0]['alumnosBajaTemporal'] ?? 0;
$maestrosActivos = obtenerDatos($queryMaestrosActivos)[0]['maestrosActivos'] ?? 0;

// OBTENER DATOS ADICIONALES (EXISTENTES)
$totalAlumnos = obtenerDatos($queryTotalAlumnos)[0]['total'] ?? 0;
$totalMaestros = obtenerDatos($queryTotalMaestros)[0]['total'] ?? 0;
$totalCalificaciones = obtenerDatos($queryTotalCalificaciones)[0]['total'] ?? 0;
$generoData = obtenerDatos($queryAlumnosPorGenero)[0] ?? ['masculino' => 0, 'femenino' => 0, 'no_especificado' => 0];
$generoMaestrosData = obtenerDatos($queryMaestrosPorGenero)[0] ?? ['masculino' => 0, 'femenino' => 0, 'no_especificado' => 0];
$promedioCalificaciones = obtenerDatos($queryPromedioCalificaciones)[0]['promedio'] ?? 0;
$calificacionesRango = obtenerDatos($queryCalificacionesPorRango)[0] ?? ['excelente' => 0, 'bueno' => 0, 'regular' => 0, 'reprobado' => 0];
$alumnosPorGrupo = obtenerDatos($queryAlumnosPorGrupo);
$alumnosPorSemestre = obtenerDatos($queryAlumnosPorSemestre);
$asistenciaMensual = obtenerDatos($queryAsistenciaPromedio);

// OBTENER NUEVOS DATOS
$alumnosConDiscapacidad = obtenerDatos($queryAlumnosConDiscapacidad)[0]['total'] ?? 0;
$tiposDiscapacidad = obtenerDatos($queryTiposDiscapacidad);
$maestrosPorContrato = obtenerDatos($queryMaestrosPorContrato);
$maestrosPorDepartamento = obtenerDatos($queryMaestrosPorDepartamento);
$alumnosPorEdad = obtenerDatos($queryAlumnosPorEdad);
$alumnosPorTurno = obtenerDatos($queryAlumnosPorTurno);
$alumnosEstadoCivil = obtenerDatos($queryAlumnosEstadoCivil);

// Calcular porcentajes
$porcentajeDiscapacidad = $totalAlumnos > 0 ? round(($alumnosConDiscapacidad / $totalAlumnos) * 100, 2) : 0;

// Preparar datos para gráficas
$gruposLabels = array_column($alumnosPorGrupo, 'grupo');
$gruposData = array_column($alumnosPorGrupo, 'cantidad');

$semestresLabels = array_column($alumnosPorSemestre, 'semestre');
$semestresData = array_column($alumnosPorSemestre, 'cantidad');

$mesesLabels = array_column($asistenciaMensual, 'mes');
$asistenciaData = array_column($asistenciaMensual, 'asistencia_promedio');

// Preparar datos para nuevas gráficas
$discapacidadLabels = array_column($tiposDiscapacidad, 'tipo_discapacidad');
$discapacidadData = array_column($tiposDiscapacidad, 'cantidad');

$contratoLabels = array_column($maestrosPorContrato, 'tipo_contrato');
$contratoData = array_column($maestrosPorContrato, 'cantidad');

$departamentoLabels = array_column($maestrosPorDepartamento, 'departamento');
$departamentoData = array_column($maestrosPorDepartamento, 'cantidad');

$edadLabels = array_column($alumnosPorEdad, 'rango_edad');
$edadData = array_column($alumnosPorEdad, 'cantidad');

$turnoLabels = array_column($alumnosPorTurno, 'turno');
$turnoData = array_column($alumnosPorTurno, 'cantidad');

$estadoCivilLabels = array_column($alumnosEstadoCivil, 'estado_civil');
$estadoCivilData = array_column($alumnosEstadoCivil, 'cantidad');

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
    'generoMaestrosMasculino' => $generoMaestrosData['masculino'],
    'generoMaestrosFemenino' => $generoMaestrosData['femenino'],
    'generoMaestrosNoEspecificado' => $generoMaestrosData['no_especificado'],
    'promedioCalificaciones' => round($promedioCalificaciones, 2),
    'calificacionesExcelente' => $calificacionesRango['excelente'],
    'calificacionesBueno' => $calificacionesRango['bueno'],
    'calificacionesRegular' => $calificacionesRango['regular'],
    'calificacionesReprobado' => $calificacionesRango['reprobado'],
    'alumnosConDiscapacidad' => $alumnosConDiscapacidad,
    'porcentajeDiscapacidad' => $porcentajeDiscapacidad,
    'gruposLabels' => $gruposLabels,
    'gruposData' => $gruposData,
    'semestresLabels' => $semestresLabels,
    'semestresData' => $semestresData,
    'mesesLabels' => $mesesLabels,
    'asistenciaData' => $asistenciaData,
    'discapacidadLabels' => $discapacidadLabels,
    'discapacidadData' => $discapacidadData,
    'contratoLabels' => $contratoLabels,
    'contratoData' => $contratoData,
    'departamentoLabels' => $departamentoLabels,
    'departamentoData' => $departamentoData,
    'edadLabels' => $edadLabels,
    'edadData' => $edadData,
    'turnoLabels' => $turnoLabels,
    'turnoData' => $turnoData,
    'estadoCivilLabels' => $estadoCivilLabels,
    'estadoCivilData' => $estadoCivilData
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
        /* Estilos existentes... (mantener todos los estilos anteriores) */
        
        /* AGREGAR ESTILOS PARA NUEVAS SECCIONES */
        
        /* Sección de estadísticas especiales */
        .special-stats-section {
            margin-top: 40px;
            margin-bottom: 40px;
        }
        
        .section-title {
            color: var(--verde-oscuro);
            margin-bottom: 25px;
            font-weight: 700;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--verde-claro);
        }
        
        /* Tarjetas de estadísticas especiales */
        .special-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .special-stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(139, 195, 74, 0.2);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .special-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(46, 125, 50, 0.15);
            border-color: var(--verde-medio);
        }
        
        .special-stat-icon {
            font-size: 2.2rem;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .special-stat-card.discapacidad .special-stat-icon {
            background: rgba(255, 193, 7, 0.1);
            color: #ff9800;
        }
        
        .special-stat-card.contrato .special-stat-icon {
            background: rgba(33, 150, 243, 0.1);
            color: #2196f3;
        }
        
        .special-stat-card.edad .special-stat-icon {
            background: rgba(156, 39, 176, 0.1);
            color: #9c27b0;
        }
        
        .special-stat-card.turno .special-stat-icon {
            background: rgba(76, 175, 80, 0.1);
            color: var(--verde-medio);
        }
        
        .special-stat-content {
            flex: 1;
        }
        
        .special-stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .special-stat-card.discapacidad .special-stat-number {
            color: #ff9800;
        }
        
        .special-stat-card.contrato .special-stat-number {
            color: #2196f3;
        }
        
        .special-stat-card.edad .special-stat-number {
            color: #9c27b0;
        }
        
        .special-stat-card.turno .special-stat-number {
            color: var(--verde-medio);
        }
        
        .special-stat-label {
            color: #5d6d5f;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .special-stat-subtext {
            color: #888;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        /* Gráficas adicionales */
        .additional-charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 768px) {
            .additional-charts-grid {
                grid-template-columns: 1fr;
            }
            
            .special-stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Pestañas para categorías */
        .stats-tabs {
            margin-bottom: 30px;
        }
        
        .nav-tabs .nav-link {
            color: var(--verde-oscuro);
            font-weight: 600;
            border: none;
            padding: 12px 25px;
            border-radius: 10px 10px 0 0;
        }
        
        .nav-tabs .nav-link.active {
            background-color: white;
            color: var(--verde-principal);
            border-bottom: 3px solid var(--verde-principal);
        }
        
        .tab-pane {
            background: white;
            border-radius: 0 15px 15px 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        /* Indicadores de progreso */
        .progress-container {
            margin: 20px 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: #5d6d5f;
            font-weight: 500;
        }
        
        .progress-bar-custom {
            height: 10px;
            border-radius: 5px;
            background-color: #e8f5e9;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.3s ease;
        }
        
        .progress-fill.discapacidad {
            background: linear-gradient(90deg, #ff9800, #ffb74d);
        }
        
        .progress-fill.calificaciones {
            background: linear-gradient(90deg, var(--verde-principal), var(--verde-claro));
        }
        
        /* Tablas de datos detallados */
        .detailed-data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .detailed-data-table th {
            background: #f1f8e9;
            color: var(--verde-oscuro);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--verde-claro);
        }
        
        .detailed-data-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .detailed-data-table tr:hover {
            background-color: #f9fdf7;
        }
        
        /* Cards de resumen */
        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.05);
            border-left: 5px solid;
        }
        
        .summary-card.discapacidad {
            border-left-color: #ff9800;
        }
        
        .summary-card.laboral {
            border-left-color: #2196f3;
        }
        
        .summary-card.demografica {
            border-left-color: #9c27b0;
        }
        
        .summary-card-title {
            color: var(--verde-oscuro);
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .summary-card-title i {
            font-size: 1.2rem;
        }
        
        .summary-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .summary-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
        }
        
        .summary-list li:last-child {
            border-bottom: none;
        }
        
        .summary-value {
            font-weight: 600;
            color: var(--verde-principal);
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar (mantener igual) -->
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
                
                <!-- NUEVA SECCIÓN: Estadísticas Especiales -->
                <div class="special-stats-section">
                    <h3 class="section-title">
                        <i class="fas fa-chart-pie"></i> Estad&iacute;sticas Especiales
                    </h3>
                    
                    <div class="special-stats-grid">
                        <!-- Discapacidad -->
                        <div class="special-stat-card discapacidad">
                            <div class="special-stat-icon">
                                <i class="fas fa-wheelchair"></i>
                            </div>
                            <div class="special-stat-content">
                                <div class="special-stat-number"><?php echo $alumnosConDiscapacidad; ?></div>
                                <div class="special-stat-label">Alumnos con Discapacidad</div>
                                <div class="special-stat-subtext">
                                    <?php echo $porcentajeDiscapacidad; ?>% del total
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contratos maestros -->
                        <div class="special-stat-card contrato">
                            <div class="special-stat-icon">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <div class="special-stat-content">
                                <div class="special-stat-number"><?php echo count($maestrosPorContrato); ?></div>
                                <div class="special-stat-label">Tipos de Contrato</div>
                                <div class="special-stat-subtext">
                                    <?php echo $totalMaestros; ?> maestros registrados
                                </div>
                            </div>
                        </div>
                        
                        <!-- Edades -->
                        <div class="special-stat-card edad">
                            <div class="special-stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="special-stat-content">
                                <div class="special-stat-number"><?php echo count($alumnosPorEdad); ?></div>
                                <div class="special-stat-label">Rangos de Edad</div>
                                <div class="special-stat-subtext">
                                    Distribución demográfica
                                </div>
                            </div>
                        </div>
                        
                        <!-- Turnos -->
                        <div class="special-stat-card turno">
                            <div class="special-stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="special-stat-content">
                                <div class="special-stat-number"><?php echo count($alumnosPorTurno); ?></div>
                                <div class="special-stat-label">Turnos Activos</div>
                                <div class="special-stat-subtext">
                                    Distribución por horario
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pestañas para diferentes categorías -->
                <div class="stats-tabs">
                    <ul class="nav nav-tabs" id="statsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                <i class="fas fa-chart-bar me-2"></i>General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="discapacidad-tab" data-bs-toggle="tab" data-bs-target="#discapacidad" type="button" role="tab">
                                <i class="fas fa-wheelchair me-2"></i>Discapacidad
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="laboral-tab" data-bs-toggle="tab" data-bs-target="#laboral" type="button" role="tab">
                                <i class="fas fa-briefcase me-2"></i>Laboral
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="demografica-tab" data-bs-toggle="tab" data-bs-target="#demografica" type="button" role="tab">
                                <i class="fas fa-users me-2"></i>Demográfica
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="statsTabsContent">
                        <!-- Pestaña General -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <h4 class="chart-title mb-4">Estad&iacute;sticas Generales</h4>
                            
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
                                
                                <!-- Nueva Gráfica: Calificaciones por rango -->
                                <div class="chart-wrapper">
                                    <div class="chart-header">
                                        <h5 class="chart-subtitle">Calificaciones por Rango</h5>
                                        <div class="chart-actions">
                                            <button class="btn-chart-action" onclick="descargarGrafica('chartCalificacionesRango')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn-chart-action" onclick="imprimirGrafica('chartCalificacionesRango')">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="chartCalificacionesRango"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Nueva Gráfica: Alumnos por semestre -->
                                <div class="chart-wrapper">
                                    <div class="chart-header">
                                        <h5 class="chart-subtitle">Alumnos por Semestre</h5>
                                        <div class="chart-actions">
                                            <button class="btn-chart-action" onclick="descargarGrafica('chartSemestres')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn-chart-action" onclick="imprimirGrafica('chartSemestres')">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="chartSemestres"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestaña Discapacidad -->
                        <div class="tab-pane fade" id="discapacidad" role="tabpanel">
                            <h4 class="chart-title mb-4">Estad&iacute;sticas de Discapacidad</h4>
                            
                            <!-- Resumen de discapacidad -->
                            <div class="summary-card discapacidad">
                                <h5 class="summary-card-title">
                                    <i class="fas fa-info-circle"></i> Resumen de Discapacidad
                                </h5>
                                <ul class="summary-list">
                                    <li>
                                        <span>Total de alumnos:</span>
                                        <span class="summary-value"><?php echo $totalAlumnos; ?></span>
                                    </li>
                                    <li>
                                        <span>Alumnos con discapacidad:</span>
                                        <span class="summary-value"><?php echo $alumnosConDiscapacidad; ?></span>
                                    </li>
                                    <li>
                                        <span>Porcentaje de discapacidad:</span>
                                        <span class="summary-value"><?php echo $porcentajeDiscapacidad; ?>%</span>
                                    </li>
                                    <li>
                                        <span>Tipos de discapacidad registrados:</span>
                                        <span class="summary-value"><?php echo count($tiposDiscapacidad); ?></span>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Gráficas de discapacidad -->
                            <div class="additional-charts-grid">
                                <!-- Gráfica de tipos de discapacidad -->
                                <div class="chart-wrapper">
                                    <div class="chart-header">
                                        <h5 class="chart-subtitle">Tipos de Discapacidad</h5>
                                        <div class="chart-actions">
                                            <button class="btn-chart-action" onclick="descargarGrafica('chartDiscapacidad')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="chartDiscapacidad"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Indicador de porcentaje -->
                                <div class="chart-wrapper">
                                    <div class="chart-header">
                                        <h5 class="chart-subtitle">Proporción de Alumnos con Discapacidad</h5>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="chartDiscapacidadProporcion"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabla de tipos de discapacidad -->
                            <div class="mt-4">
                                <h5 class="mb-3">Distribución por Tipo de Discapacidad</h5>
                                <div class="table-responsive">
                                    <table class="detailed-data-table">
                                        <thead>
                                            <tr>
                                                <th>Tipo de Discapacidad</th>
                                                <th>Cantidad de Alumnos</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $totalDiscapacidad = $alumnosConDiscapacidad;
                                            foreach ($tiposDiscapacidad as $tipo):
                                                $porcentajeTipo = $totalDiscapacidad > 0 ? round(($tipo['cantidad'] / $totalDiscapacidad) * 100, 2) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo $tipo['tipo_discapacidad'] ?? 'No especificado'; ?></td>
                                                <td><?php echo $tipo['cantidad']; ?></td>
                                                <td><?php echo $porcentajeTipo; ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($tiposDiscapacidad)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No hay datos de discapacidad registrados</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestaña Laboral -->
                        <div class="tab-pane fade" id="laboral" role="tabpanel">
                            <h4 class="chart-title mb-4">Estad&iacute;sticas Laborales de Maestros</h4>
                            
                            <!-- Resumen laboral -->
                            <div class="summary-card laboral">
                                <h5 class="summary-card-title">
                                    <i class="fas fa-briefcase"></i> Resumen Laboral
                                </h5>
                                <ul class="summary-list">
                                    <li>
                                        <span>Total de maestros:</span>
                                        <span class="summary-value"><?php echo $totalMaestros; ?></span>
                                    </li>
                                    <li>
                                        <span>Maestros activos:</span>
                                        <span class="summary-value"><?php echo $maestrosActivos; ?></span>
                                    </li>
                                    <li>
                                        <span>Tipos de contrato:</span>
                                        <span class="summary-value"><?php echo count($maestrosPorContrato); ?></span>
                                    </li>
                                    <li>
                                        <span>Departamentos:</span>
                                        <span class="summary-value"><?php echo count($maestrosPorDepartamento); ?></span>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Gráficas laborales -->
                            <div class="additional-charts-grid">
                                <!-- Gráfica de contratos -->
                                <div class="chart-wrapper">
                                    <div class="chart-header">
                                        <h5 class="chart-subtitle">Maestros por Tipo de Contrato</h5>
                                        <div class="chart-actions">
                                            <button class="btn-chart-action" onclick="descargarGrafica('chartContratos')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="chartContratos"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Gráfica de departamentos -->
                                <div class="chart-wrapper">
                                    <div class="chart-header">
                                        <h5 class="chart-subtitle">Maestros por Departamento</h5>
                                        <div class="chart-actions">
                                            <button class="btn-chart-action" onclick="descargarGrafica('chartDepartamentos')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="chartDepartamentos"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tablas de datos laborales -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Distribución por Contrato</h5>
                                    <div class="table-responsive">
                                        <table class="detailed-data-table">
                                            <thead>
                                                <tr>
                                                    <th>Tipo de Contrato</th>
                                                    <th>Cantidad</th>
                                                    <th>Porcentaje</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($maestrosPorContrato as $contrato):
                                                    $porcentajeContrato = $totalMaestros > 0 ? round(($contrato['cantidad'] / $totalMaestros) * 100, 2) : 0;
                                                ?>
                                                <tr>
                                                    <td><?php echo $contrato['tipo_contrato'] ?? 'No especificado'; ?></td>
                                                    <td><?php echo $contrato['cantidad']; ?></td>
                                                    <td><?php echo $porcentajeContrato; ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5 class="mb-3">Distribución por Departamento</h5>
                                    <div class="table-responsive">
                                        <table class="detailed-data-table">
                                            <thead>
                                                <tr>
                                                    <th>Departamento</th>
                                                    <th>Cantidad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($maestrosPorDepartamento as $departamento): ?>
                                                <tr>
                                                    <td><?php echo $departamento['departamento']; ?></td>
                                                    <td><?php echo $departamento['cantidad']; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestaña Demográfica -->
                        <div class="tab-pane fade" id="demografica" role="tabpanel">
                            <h4 class="chart-title mb-4">Estad&iacute;sticas Demográficas</h4>
                            
                            <!-- Resumen demográfico -->
                            <div class="summary-card demografica">
                                <h5 class="summary-card-title">
                                    <i class="fas fa-users"></i> Resumen Demográfico
                                </h5>
                                <ul class="summary-list">
                                    <li>
                                        <span>Total alumnos:</span>
                                        <span class="summary-value"><?php echo $totalAlumnos; ?></span>
                                    </li>
                                    <li>
                                        <span>Total maestros:</span>
                                        <span class="summary-value"><?php echo $totalMaestros; ?></span>
                                    </li>
                                    <li>
                                        <span>Género alumnos (M/F):</span>
                                        <span class="summary-value"><?php echo $generoData['masculino']; ?> / <?php echo $generoData['femenino']; ?></span>
                                    </li>
                                    <li>
                                        <span>Género maestros (M/F):</span>
                                        <span class="summary-value"><?php echo $generoMaestrosData['masculino']; ?> / <?php echo $generoMaestrosData['femenino']; ?></span>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Gráficas demográficas -->
                            <div class="additional-charts-grid">
                                <!-- Gráfica de edades -->
                                <div class="chart-wrapper">
                                    <div class="chart-header">
                                        <h5 class="chart-subtitle">Alumnos por Rango de Edad</h5>
                                        <div class="chart-actions">
                                            <button class="btn-chart-action" onclick="descargarGrafica('chartEdades')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="chartEdades"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Gráfica de turnos -->
                                <div class="chart-wrapper">
                                    <div class="chart-header">
                                        <h5 class="chart-subtitle">Alumnos por Turno</h5>
                                        <div class="chart-actions">
                                            <button class="btn-chart-action" onclick="descargarGrafica('chartTurnos')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="chartTurnos"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Gráfica de estado civil -->
                                <div class="chart-wrapper">
                                    <div class="chart-header">
                                        <h5 class="chart-subtitle">Alumnos por Estado Civil</h5>
                                        <div class="chart-actions">
                                            <button class="btn-chart-action" onclick="descargarGrafica('chartEstadoCivil')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="chartEstadoCivil"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Gráfica de género maestros -->
                                <div class="chart-wrapper">
                                    <div class="chart-header">
                                        <h5 class="chart-subtitle">Maestros por Género</h5>
                                        <div class="chart-actions">
                                            <button class="btn-chart-action" onclick="descargarGrafica('chartGeneroMaestros')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="chartGeneroMaestros"></canvas>
                                    </div>
                                </div>
                            </div>
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
                                    <td>Alumnos con Discapacidad</td>
                                    <td><strong><?php echo $alumnosConDiscapacidad; ?></strong></td>
                                    <td>Alumnos con alguna discapacidad registrada</td>
                                    <td><span class="badge bg-warning">-</span></td>
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
                                <tr>
                                    <td>Tipos de Discapacidad</td>
                                    <td><strong><?php echo count($tiposDiscapacidad); ?></strong></td>
                                    <td>Tipos diferentes de discapacidad registrados</td>
                                    <td><span class="badge bg-warning">-</span></td>
                                </tr>
                                <tr>
                                    <td>Tipos de Contrato</td>
                                    <td><strong><?php echo count($maestrosPorContrato); ?></strong></td>
                                    <td>Tipos de contrato laboral registrados</td>
                                    <td><span class="badge bg-success">+1</span></td>
                                </tr>
                                <tr>
                                    <td>Departamentos</td>
                                    <td><strong><?php echo count($maestrosPorDepartamento); ?></strong></td>
                                    <td>Departamentos laborales registrados</td>
                                    <td><span class="badge bg-success">+1</span></td>
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
        let chartEstado, chartGenero, chartGrupos, chartAsistencia, chartCalificacionesRango, chartSemestres;
        let chartDiscapacidad, chartDiscapacidadProporcion, chartContratos, chartDepartamentos;
        let chartEdades, chartTurnos, chartEstadoCivil, chartGeneroMaestros;
        
        // Función para inicializar todas las gráficas
        function inicializarGraficas() {
            const chartType = document.querySelector('input[name="chartType"]:checked').value;
            
            // Destruir gráficas existentes
            if (chartEstado) chartEstado.destroy();
            if (chartGenero) chartGenero.destroy();
            if (chartGrupos) chartGrupos.destroy();
            if (chartAsistencia) chartAsistencia.destroy();
            if (chartCalificacionesRango) chartCalificacionesRango.destroy();
            if (chartSemestres) chartSemestres.destroy();
            
            // Crear nuevas gráficas
            crearGraficaEstado(chartType);
            crearGraficaGenero(chartType);
            crearGraficaGrupos(chartType);
            crearGraficaAsistencia(chartType);
            crearGraficaCalificacionesRango(chartType);
            crearGraficaSemestres(chartType);
        }
        
        // Función para inicializar gráficas de discapacidad
        function inicializarGraficasDiscapacidad() {
            if (chartDiscapacidad) chartDiscapacidad.destroy();
            if (chartDiscapacidadProporcion) chartDiscapacidadProporcion.destroy();
            
            crearGraficaDiscapacidad();
            crearGraficaDiscapacidadProporcion();
        }
        
        // Función para inicializar gráficas laborales
        function inicializarGraficasLaborales() {
            if (chartContratos) chartContratos.destroy();
            if (chartDepartamentos) chartDepartamentos.destroy();
            
            crearGraficaContratos();
            crearGraficaDepartamentos();
        }
        
        // Función para inicializar gráficas demográficas
        function inicializarGraficasDemograficas() {
            if (chartEdades) chartEdades.destroy();
            if (chartTurnos) chartTurnos.destroy();
            if (chartEstadoCivil) chartEstadoCivil.destroy();
            if (chartGeneroMaestros) chartGeneroMaestros.destroy();
            
            crearGraficaEdades();
            crearGraficaTurnos();
            crearGraficaEstadoCivil();
            crearGraficaGeneroMaestros();
        }
        
        // ===== FUNCIONES PARA CREAR GRÁFICAS =====
        
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
                            'rgba(26, 83, 48, 0.7)',
                            'rgba(139, 195, 74, 0.7)',
                            'rgba(46, 125, 50, 0.7)'
                        ],
                        borderColor: [
                            'rgba(26, 83, 48, 1)',
                            'rgba(139, 195, 74, 1)',
                            'rgba(46, 125, 50, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Estado de Alumnos y Maestros')
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
                        label: 'Distribución por Género',
                        data: [data.generoMasculino, data.generoFemenino, data.generoNoEspecificado],
                        backgroundColor: [
                            'rgba(26, 83, 48, 0.7)',
                            'rgba(76, 175, 80, 0.7)',
                            'rgba(139, 195, 74, 0.7)'
                        ],
                        borderColor: [
                            'rgba(26, 83, 48, 1)',
                            'rgba(76, 175, 80, 1)',
                            'rgba(139, 195, 74, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Distribución de Alumnos por Género')
            });
        }
        
        // Función para crear gráfica de grupos
        function crearGraficaGrupos(type) {
            const ctx = document.getElementById('chartGrupos').getContext('2d');
            chartGrupos = new Chart(ctx, {
                type: type === 'pie' || type === 'doughnut' ? 'bar' : type,
                data: {
                    labels: data.gruposLabels,
                    datasets: [{
                        label: 'Cantidad de Alumnos',
                        data: data.gruposData,
                        backgroundColor: 'rgba(46, 125, 50, 0.7)',
                        borderColor: 'rgba(46, 125, 50, 1)',
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Alumnos por Grupo', {
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
                })
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
                        backgroundColor: 'rgba(76, 175, 80, 0.2)',
                        borderColor: 'rgba(46, 125, 50, 1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: getChartOptions('Asistencia Promedio Mensual', {
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
                })
            });
        }
        
        // Función para crear gráfica de calificaciones por rango
        function crearGraficaCalificacionesRango(type) {
            const ctx = document.getElementById('chartCalificacionesRango').getContext('2d');
            chartCalificacionesRango = new Chart(ctx, {
                type: type,
                data: {
                    labels: ['Excelente (9-10)', 'Bueno (7-8.9)', 'Regular (6-6.9)', 'Reprobado (<6)'],
                    datasets: [{
                        label: 'Cantidad de Calificaciones',
                        data: [
                            data.calificacionesExcelente,
                            data.calificacionesBueno,
                            data.calificacionesRegular,
                            data.calificacionesReprobado
                        ],
                        backgroundColor: [
                            'rgba(46, 125, 50, 0.7)',
                            'rgba(76, 175, 80, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(244, 67, 54, 0.7)'
                        ],
                        borderColor: [
                            'rgba(46, 125, 50, 1)',
                            'rgba(76, 175, 80, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(244, 67, 54, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Calificaciones por Rango')
            });
        }
        
        // Función para crear gráfica de semestres
        function crearGraficaSemestres(type) {
            const ctx = document.getElementById('chartSemestres').getContext('2d');
            chartSemestres = new Chart(ctx, {
                type: type === 'pie' || type === 'doughnut' ? 'bar' : type,
                data: {
                    labels: data.semestresLabels,
                    datasets: [{
                        label: 'Cantidad de Alumnos',
                        data: data.semestresData,
                        backgroundColor: 'rgba(26, 83, 48, 0.7)',
                        borderColor: 'rgba(26, 83, 48, 1)',
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Alumnos por Semestre', {
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
                                text: 'Semestre'
                            }
                        }
                    }
                })
            });
        }
        
        // Función para crear gráfica de discapacidad
        function crearGraficaDiscapacidad() {
            const ctx = document.getElementById('chartDiscapacidad').getContext('2d');
            chartDiscapacidad = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.discapacidadLabels,
                    datasets: [{
                        label: 'Cantidad de Alumnos',
                        data: data.discapacidadData,
                        backgroundColor: 'rgba(255, 152, 0, 0.7)',
                        borderColor: 'rgba(255, 152, 0, 1)',
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Tipos de Discapacidad', {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad de Alumnos'
                            }
                        }
                    }
                })
            });
        }
        
        // Función para crear gráfica de proporción de discapacidad
        function crearGraficaDiscapacidadProporcion() {
            const ctx = document.getElementById('chartDiscapacidadProporcion').getContext('2d');
            const totalSinDiscapacidad = data.totalAlumnos - data.alumnosConDiscapacidad;
            
            chartDiscapacidadProporcion = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Con Discapacidad', 'Sin Discapacidad'],
                    datasets: [{
                        data: [data.alumnosConDiscapacidad, totalSinDiscapacidad],
                        backgroundColor: [
                            'rgba(255, 152, 0, 0.7)',
                            'rgba(76, 175, 80, 0.7)'
                        ],
                        borderColor: [
                            'rgba(255, 152, 0, 1)',
                            'rgba(76, 175, 80, 1)'
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
                                    const percentage = ((context.raw / data.totalAlumnos) * 100).toFixed(2);
                                    return `${context.label}: ${context.raw} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Función para crear gráfica de contratos
        function crearGraficaContratos() {
            const ctx = document.getElementById('chartContratos').getContext('2d');
            chartContratos = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data.contratoLabels,
                    datasets: [{
                        data: data.contratoData,
                        backgroundColor: [
                            'rgba(33, 150, 243, 0.7)',
                            'rgba(3, 169, 244, 0.7)',
                            'rgba(0, 188, 212, 0.7)',
                            'rgba(0, 150, 136, 0.7)',
                            'rgba(76, 175, 80, 0.7)'
                        ],
                        borderColor: [
                            'rgba(33, 150, 243, 1)',
                            'rgba(3, 169, 244, 1)',
                            'rgba(0, 188, 212, 1)',
                            'rgba(0, 150, 136, 1)',
                            'rgba(76, 175, 80, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Maestros por Tipo de Contrato')
            });
        }
        
        // Función para crear gráfica de departamentos
        function crearGraficaDepartamentos() {
            const ctx = document.getElementById('chartDepartamentos').getContext('2d');
            chartDepartamentos = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.departamentoLabels,
                    datasets: [{
                        label: 'Cantidad de Maestros',
                        data: data.departamentoData,
                        backgroundColor: 'rgba(33, 150, 243, 0.7)',
                        borderColor: 'rgba(33, 150, 243, 1)',
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Maestros por Departamento', {
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad de Maestros'
                            }
                        }
                    }
                })
            });
        }
        
        // Función para crear gráfica de edades
        function crearGraficaEdades() {
            const ctx = document.getElementById('chartEdades').getContext('2d');
            chartEdades = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.edadLabels,
                    datasets: [{
                        label: 'Cantidad de Alumnos',
                        data: data.edadData,
                        backgroundColor: 'rgba(156, 39, 176, 0.7)',
                        borderColor: 'rgba(156, 39, 176, 1)',
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Alumnos por Rango de Edad', {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad de Alumnos'
                            }
                        }
                    }
                })
            });
        }
        
        // Función para crear gráfica de turnos
        function crearGraficaTurnos() {
            const ctx = document.getElementById('chartTurnos').getContext('2d');
            chartTurnos = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.turnoLabels,
                    datasets: [{
                        data: data.turnoData,
                        backgroundColor: [
                            'rgba(46, 125, 50, 0.7)',
                            'rgba(76, 175, 80, 0.7)',
                            'rgba(139, 195, 74, 0.7)',
                            'rgba(197, 225, 165, 0.7)'
                        ],
                        borderColor: [
                            'rgba(46, 125, 50, 1)',
                            'rgba(76, 175, 80, 1)',
                            'rgba(139, 195, 74, 1)',
                            'rgba(197, 225, 165, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Alumnos por Turno')
            });
        }
        
        // Función para crear gráfica de estado civil
        function crearGraficaEstadoCivil() {
            const ctx = document.getElementById('chartEstadoCivil').getContext('2d');
            chartEstadoCivil = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data.estadoCivilLabels,
                    datasets: [{
                        data: data.estadoCivilData,
                        backgroundColor: [
                            'rgba(33, 150, 243, 0.7)',
                            'rgba(76, 175, 80, 0.7)',
                            'rgba(255, 152, 0, 0.7)',
                            'rgba(156, 39, 176, 0.7)'
                        ],
                        borderColor: [
                            'rgba(33, 150, 243, 1)',
                            'rgba(76, 175, 80, 1)',
                            'rgba(255, 152, 0, 1)',
                            'rgba(156, 39, 176, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Alumnos por Estado Civil')
            });
        }
        
        // Función para crear gráfica de género maestros
        function crearGraficaGeneroMaestros() {
            const ctx = document.getElementById('chartGeneroMaestros').getContext('2d');
            chartGeneroMaestros = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Masculino', 'Femenino', 'No Especificado'],
                    datasets: [{
                        data: [
                            data.generoMaestrosMasculino,
                            data.generoMaestrosFemenino,
                            data.generoMaestrosNoEspecificado
                        ],
                        backgroundColor: [
                            'rgba(33, 150, 243, 0.7)',
                            'rgba(233, 30, 99, 0.7)',
                            'rgba(158, 158, 158, 0.7)'
                        ],
                        borderColor: [
                            'rgba(33, 150, 243, 1)',
                            'rgba(233, 30, 99, 1)',
                            'rgba(158, 158, 158, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Maestros por Género')
            });
        }
        
        // Función auxiliar para obtener opciones de gráfica
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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw}`;
                            }
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
        
        // ===== EVENT LISTENERS Y CONFIGURACIÓN =====
        
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
        
        // Inicializar pestañas de Bootstrap
        const tabEls = document.querySelectorAll('#statsTabs button[data-bs-toggle="tab"]');
        tabEls.forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', function(event) {
                const targetId = event.target.getAttribute('data-bs-target');
                
                // Inicializar gráficas según la pestaña activa
                switch(targetId) {
                    case '#general':
                        inicializarGraficas();
                        break;
                    case '#discapacidad':
                        setTimeout(() => inicializarGraficasDiscapacidad(), 100);
                        break;
                    case '#laboral':
                        setTimeout(() => inicializarGraficasLaborales(), 100);
                        break;
                    case '#demografica':
                        setTimeout(() => inicializarGraficasDemograficas(), 100);
                        break;
                }
            });
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
                document.getElementById('btn-toggle').classlist.add('bx-menu');
            }
        }
        
        window.addEventListener('resize', handleResize);
        window.addEventListener('load', handleResize);
        
        // Inicializar gráficas al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar gráficas generales
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
            
            // Animación de tarjetas especiales
            document.querySelectorAll('.special-stat-card').forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 150 + 400);
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
        
        // Efecto hover para tarjetas especiales
        document.querySelectorAll('.special-stat-card').forEach(card => {
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