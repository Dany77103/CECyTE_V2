<?php
// Conexi�n a la base de datos usando PDO
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cecyte_sc";

try {
    $con = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexi�n a la base de datos: " . $e->getMessage());
}

// Funci�n para obtener datos de la base de datos usando PDO
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

// Funci�n para obtener un solo dato
function obtenerDato($query, $params = []) {
    global $con;
    try {
        $stmt = $con->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : null;
    } catch (PDOException $e) {
        return null;
    }
}

session_start();

// Verificar si el usuario ha iniciado sesi�n
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Verificar permisos (solo administradores)
if ($_SESSION['rol'] !== 'admin') {
    header('Location: acceso_denegado.php');
    exit;
}

// Procesar filtros
$filtros = [];
$parametros = [];

$semestre_filtro = isset($_GET['semestre']) ? intval($_GET['semestre']) : '';
$grupo_filtro = isset($_GET['grupo']) ? $_GET['grupo'] : '';
$carrera_filtro = isset($_GET['carrera']) ? $_GET['carrera'] : '';
$periodo_filtro = isset($_GET['periodo']) ? $_GET['periodo'] : '';
$genero_filtro = isset($_GET['genero']) ? $_GET['genero'] : '';
$turno_filtro = isset($_GET['turno']) ? $_GET['turno'] : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$exportar = isset($_GET['exportar']) ? $_GET['exportar'] : '';

// Construir condiciones WHERE
$condiciones = [];
if ($semestre_filtro) {
    $condiciones[] = "a.id_semestre = ?";
    $parametros[] = $semestre_filtro;
}
if ($grupo_filtro) {
    $condiciones[] = "a.id_grupo = ?";
    $parametros[] = $grupo_filtro;
}
if ($carrera_filtro) {
    $condiciones[] = "a.id_carrera = ?";
    $parametros[] = $carrera_filtro;
}
if ($genero_filtro) {
    $condiciones[] = "a.genero = ?";
    $parametros[] = $genero_filtro;
}
if ($turno_filtro) {
    $condiciones[] = "a.turno = ?";
    $parametros[] = $turno_filtro;
}

$where_sql = !empty($condiciones) ? "WHERE " . implode(" AND ", $condiciones) : "";

// Obtener opciones para filtros
$semestres = obtenerDatos("SELECT DISTINCT id_semestre FROM alumnos ORDER BY id_semestre");
$grupos = obtenerDatos("SELECT DISTINCT id_grupo FROM alumnos WHERE id_grupo IS NOT NULL ORDER BY id_grupo");
$carreras = obtenerDatos("SELECT id_carrera, nombre_carrera FROM carreras ORDER BY nombre_carrera");
$generos = obtenerDatos("SELECT DISTINCT genero FROM alumnos WHERE genero IS NOT NULL ORDER BY genero");
$turnos = obtenerDatos("SELECT DISTINCT turno FROM alumnos WHERE turno IS NOT NULL ORDER BY turno");

// Obtener estad�sticas principales
$total_alumnos = obtenerDato("SELECT COUNT(*) as total FROM alumnos")['total'] ?? 0;
$total_maestros = obtenerDato("SELECT COUNT(*) as total FROM maestros")['total'] ?? 0;
$total_carreras = obtenerDato("SELECT COUNT(*) as total FROM carreras")['total'] ?? 0;
$promedio_general = obtenerDato("SELECT ROUND(AVG(calificacion), 2) as promedio FROM calificaciones")['promedio'] ?? 0;

// Estad�sticas avanzadas

// 1. Distribuci�n por g�nero
$distribucion_genero = obtenerDatos("
    SELECT genero, COUNT(*) as cantidad 
    FROM alumnos 
    WHERE genero IS NOT NULL 
    GROUP BY genero 
    ORDER BY cantidad DESC
");

// 2. Distribuci�n por semestre
$distribucion_semestre = obtenerDatos("
    SELECT id_semestre, COUNT(*) as cantidad 
    FROM alumnos 
    GROUP BY id_semestre 
    ORDER BY id_semestre
");

// 3. Promedio de calificaciones por carrera
$promedio_carreras = obtenerDatos("
    SELECT c.nombre_carrera, ROUND(AVG(cal.calificacion), 2) as promedio, COUNT(*) as total_calificaciones
    FROM calificaciones cal
    INNER JOIN alumnos a ON cal.id_alumno = a.id_alumno
    INNER JOIN carreras c ON a.id_carrera = c.id_carrera
    GROUP BY c.nombre_carrera
    ORDER BY promedio DESC
");

// 4. Asistencia por mes
$asistencia_mensual = obtenerDatos("
    SELECT 
        DATE_FORMAT(fecha, '%Y-%m') as mes,
        COUNT(*) as total_registros,
        SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) as asistencias,
        ROUND((SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as porcentaje_asistencia
    FROM asistencias_clase
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha, '%Y-%m')
    ORDER BY mes DESC
");

// 5. Rendimiento por docente
$rendimiento_docente = obtenerDatos("
    SELECT 
        m.nombre,
        COUNT(DISTINCT cal.id_alumno) as alumnos_atendidos,
        ROUND(AVG(cal.calificacion), 2) as promedio_calificaciones,
        COUNT(CASE WHEN cal.calificacion >= 8 THEN 1 END) as aprobados,
        COUNT(CASE WHEN cal.calificacion < 8 THEN 1 END) as reprobados,
        ROUND((COUNT(CASE WHEN cal.calificacion >= 8 THEN 1 END) / COUNT(*)) * 100, 2) as porcentaje_aprobacion
    FROM calificaciones cal
    INNER JOIN maestros m ON cal.id_maestro = m.id_maestro
    GROUP BY m.id_maestro, m.nombre
    HAVING alumnos_atendidos > 0
    ORDER BY promedio_calificaciones DESC
    LIMIT 15
");

// 6. Deserci�n por semestre
$desercion_semestre = obtenerDatos("
    SELECT 
        a.id_semestre,
        COUNT(*) as total_alumnos,
        SUM(CASE WHEN haa.id_estatus = (SELECT id_estatus FROM estatus WHERE tipoEstatus = 'baja') THEN 1 ELSE 0 END) as bajas,
        ROUND((SUM(CASE WHEN haa.id_estatus = (SELECT id_estatus FROM estatus WHERE tipoEstatus = 'baja') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as porcentaje_bajas
    FROM alumnos a
    LEFT JOIN historialacademicoalumnos haa ON a.id_alumno = haa.id_alumno
    GROUP BY a.id_semestre
    ORDER BY a.id_semestre
");

// 7. Eficiencia terminal por carrera
$eficiencia_carrera = obtenerDatos("
    SELECT 
        c.nombre_carrera,
        COUNT(DISTINCT a.id_alumno) as total_inscritos,
        SUM(CASE WHEN a.estatus = 'egresado' THEN 1 ELSE 0 END) as egresados,
        ROUND((SUM(CASE WHEN a.estatus = 'egresado' THEN 1 ELSE 0 END) / COUNT(DISTINCT a.id_alumno)) * 100, 2) as eficiencia_terminal
    FROM alumnos a
    INNER JOIN carreras c ON a.id_carrera = c.id_carrera
    WHERE a.estatus IS NOT NULL
    GROUP BY c.nombre_carrera
    ORDER BY eficiencia_terminal DESC
");

// 8. Discapacidades
$discapacidades = obtenerDatos("
    SELECT 
        d.tipo_discapacidad,
        COUNT(*) as cantidad,
        ROUND((COUNT(*) / (SELECT COUNT(*) FROM alumnos WHERE id_discapacidad IS NOT NULL)) * 100, 2) as porcentaje
    FROM alumnos a
    INNER JOIN discapacidades d ON a.id_discapacidad = d.id_discapacidad
    WHERE a.id_discapacidad IS NOT NULL
    GROUP BY d.tipo_discapacidad
    ORDER BY cantidad DESC
");

// 9. Edad promedio por semestre
$edad_promedio = obtenerDatos("
    SELECT 
        id_semestre,
        ROUND(AVG(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())), 1) as edad_promedio,
        MIN(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())) as edad_minima,
        MAX(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())) as edad_maxima,
        COUNT(*) as total
    FROM alumnos
    WHERE fecha_nacimiento IS NOT NULL
    GROUP BY id_semestre
    ORDER BY id_semestre
");

// 10. Distribuci�n por turno
$distribucion_turno = obtenerDatos("
    SELECT 
        turno,
        COUNT(*) as cantidad,
        ROUND((COUNT(*) / (SELECT COUNT(*) FROM alumnos WHERE turno IS NOT NULL)) * 100, 2) as porcentaje
    FROM alumnos
    WHERE turno IS NOT NULL
    GROUP BY turno
    ORDER BY cantidad DESC
");

// 11. Calificaciones por rango
$calificaciones_rango = obtenerDatos("
    SELECT 
        CASE 
            WHEN calificacion >= 9 THEN '9-10 (Excelente)'
            WHEN calificacion >= 8 THEN '8-8.9 (Bueno)'
            WHEN calificacion >= 7 THEN '7-7.9 (Regular)'
            WHEN calificacion >= 6 THEN '6-6.9 (Suficiente)'
            ELSE '0-5.9 (Reprobado)'
        END as rango,
        COUNT(*) as cantidad,
        ROUND((COUNT(*) / (SELECT COUNT(*) FROM calificaciones)) * 100, 2) as porcentaje
    FROM calificaciones
    GROUP BY CASE 
        WHEN calificacion >= 9 THEN '9-10 (Excelente)'
        WHEN calificacion >= 8 THEN '8-8.9 (Bueno)'
        WHEN calificacion >= 7 THEN '7-7.9 (Regular)'
        WHEN calificacion >= 6 THEN '6-6.9 (Suficiente)'
        ELSE '0-5.9 (Reprobado)'
    END
    ORDER BY CASE 
        WHEN rango LIKE '9%' THEN 1
        WHEN rango LIKE '8%' THEN 2
        WHEN rango LIKE '7%' THEN 3
        WHEN rango LIKE '6%' THEN 4
        ELSE 5
    END
");

// 12. Alumnos con beca
$alumnos_beca = obtenerDatos("
    SELECT 
        beca,
        COUNT(*) as cantidad,
        ROUND((COUNT(*) / (SELECT COUNT(*) FROM alumnos)) * 100, 2) as porcentaje
    FROM alumnos
    WHERE beca IS NOT NULL
    GROUP BY beca
    ORDER BY beca
");

// 13. Asistencia por d�a de la semana
$asistencia_dia = obtenerDatos("
    SELECT 
        DAYNAME(fecha) as dia,
        COUNT(*) as total_registros,
        SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) as asistencias,
        ROUND((SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as porcentaje_asistencia
    FROM asistencias_clase
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DAYNAME(fecha), DAYOFWEEK(fecha)
    ORDER BY DAYOFWEEK(fecha)
");

// 14. Estado civil de alumnos
$estado_civil = obtenerDatos("
    SELECT 
        estado_civil,
        COUNT(*) as cantidad,
        ROUND((COUNT(*) / (SELECT COUNT(*) FROM alumnos WHERE estado_civil IS NOT NULL)) * 100, 2) as porcentaje
    FROM alumnos
    WHERE estado_civil IS NOT NULL
    GROUP BY estado_civil
    ORDER BY cantidad DESC
");

// 15. Distribuci�n por tipo de sangre
$tipo_sangre = obtenerDatos("
    SELECT 
        tipo_sangre,
        COUNT(*) as cantidad,
        ROUND((COUNT(*) / (SELECT COUNT(*) FROM alumnos WHERE tipo_sangre IS NOT NULL)) * 100, 2) as porcentaje
    FROM alumnos
    WHERE tipo_sangre IS NOT NULL
    GROUP BY tipo_sangre
    ORDER BY cantidad DESC
");

// Preparar datos para gr�ficos
$chart_data = [
    'genero_labels' => array_column($distribucion_genero, 'genero'),
    'genero_data' => array_column($distribucion_genero, 'cantidad'),
    
    'semestre_labels' => array_map(function($s) { return "Semestre " . $s['id_semestre']; }, $distribucion_semestre),
    'semestre_data' => array_column($distribucion_semestre, 'cantidad'),
    
    'carrera_labels' => array_column($promedio_carreras, 'nombre_carrera'),
    'carrera_data' => array_column($promedio_carreras, 'promedio'),
    
    'mes_labels' => array_column($asistencia_mensual, 'mes'),
    'asistencia_data' => array_column($asistencia_mensual, 'porcentaje_asistencia'),
    
    'desercion_labels' => array_map(function($d) { return "Sem " . $d['id_semestre']; }, $desercion_semestre),
    'desercion_data' => array_column($desercion_semestre, 'porcentaje_bajas'),
    
    'eficiencia_labels' => array_column($eficiencia_carrera, 'nombre_carrera'),
    'eficiencia_data' => array_column($eficiencia_carrera, 'eficiencia_terminal'),
    
    'discapacidad_labels' => array_column($discapacidades, 'tipo_discapacidad'),
    'discapacidad_data' => array_column($discapacidades, 'cantidad'),
    
    'edad_labels' => array_map(function($e) { return "Sem " . $e['id_semestre']; }, $edad_promedio),
    'edad_promedio_data' => array_column($edad_promedio, 'edad_promedio'),
    'edad_minima_data' => array_column($edad_promedio, 'edad_minima'),
    'edad_maxima_data' => array_column($edad_promedio, 'edad_maxima'),
    
    'turno_labels' => array_column($distribucion_turno, 'turno'),
    'turno_data' => array_column($distribucion_turno, 'cantidad'),
    
    'calificacion_labels' => array_column($calificaciones_rango, 'rango'),
    'calificacion_data' => array_column($calificaciones_rango, 'cantidad'),
    
    'beca_labels' => array_column($alumnos_beca, 'beca'),
    'beca_data' => array_column($alumnos_beca, 'cantidad'),
    
    'dia_labels' => array_column($asistencia_dia, 'dia'),
    'dia_data' => array_column($asistencia_dia, 'porcentaje_asistencia'),
    
    'estado_civil_labels' => array_column($estado_civil, 'estado_civil'),
    'estado_civil_data' => array_column($estado_civil, 'cantidad'),
    
    'sangre_labels' => array_column($tipo_sangre, 'tipo_sangre'),
    'sangre_data' => array_column($tipo_sangre, 'cantidad'),
];

// Si se solicita exportar datos
if ($exportar == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="estadisticas_cecyte_' . date('Y-m-d') . '.xls"');
    // Aqu� ir�a la l�gica para generar Excel
    exit;
}
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
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Date Range Picker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    
    <style>
        :root {
            --verde-oscuro: #1a5330;
            --verde-principal: #2e7d32;
            --verde-medio: #4caf50;
            --verde-claro: #8bc34a;
            --verde-brillante: #81c784;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .navbar-main {
            background: linear-gradient(90deg, var(--verde-oscuro), var(--verde-principal));
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .management-container {
            padding: 20px;
            max-width: 1800px;
            margin: 0 auto;
        }
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.08);
            border-left: 6px solid var(--verde-principal);
        }
        
        .page-title {
            color: var(--verde-oscuro);
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        
        /* Panel de estad�sticas principales */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-top: 4px solid var(--verde-principal);
            text-align: center;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(46, 125, 50, 0.15);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: var(--verde-principal);
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2.8rem;
            font-weight: 700;
            color: var(--verde-oscuro);
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* Filtros avanzados */
        .filters-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .filter-title {
            color: var(--verde-oscuro);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        /* Gr�ficos y visualizaciones */
        .charts-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .section-title {
            color: var(--verde-oscuro);
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .chart-container {
            background: #f8fff8;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #e0f2e0;
            height: 350px;
            position: relative;
        }
        
        /* Tablas de datos */
        .data-tables {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background: var(--verde-principal);
            color: white;
        }
        
        .data-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--verde-oscuro);
        }
        
        .data-table tbody tr {
            border-bottom: 1px solid #dee2e6;
            transition: background 0.3s ease;
        }
        
        .data-table tbody tr:hover {
            background: #f1f8e9;
        }
        
        .data-table td {
            padding: 12px 15px;
        }
        
        /* Botones de acci�n */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-export {
            background: var(--verde-principal);
            color: white;
        }
        
        .btn-export:hover {
            background: var(--verde-oscuro);
            color: white;
        }
        
        .btn-refresh {
            background: #2196f3;
            color: white;
        }
        
        .btn-refresh:hover {
            background: #0b7dda;
        }
        
        .btn-print {
            background: #ff9800;
            color: white;
        }
        
        .btn-print:hover {
            background: #e68900;
        }
        
        /* Badges personalizados */
        .badge-excelente {
            background: #4caf50;
            color: white;
        }
        
        .badge-bueno {
            background: #8bc34a;
            color: white;
        }
        
        .badge-regular {
            background: #ffc107;
            color: #333;
        }
        
        .badge-bajo {
            background: #f44336;
            color: white;
        }
        
        /* Tarjetas de an�lisis */
        .analysis-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .analysis-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 5px solid;
        }
        
        .analysis-card.rendimiento {
            border-left-color: #4caf50;
        }
        
        .analysis-card.desercion {
            border-left-color: #f44336;
        }
        
        .analysis-card.asistencia {
            border-left-color: #2196f3;
        }
        
        .analysis-card.eficiencia {
            border-left-color: #9c27b0;
        }
        
        .analysis-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .analysis-content {
            font-size: 0.9rem;
            color: #666;
        }
        
        .analysis-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .analysis-card.rendimiento .analysis-value {
            color: #4caf50;
        }
        
        .analysis-card.desercion .analysis-value {
            color: #f44336;
        }
        
        .analysis-card.asistencia .analysis-value {
            color: #2196f3;
        }
        
        .analysis-card.eficiencia .analysis-value {
            color: #9c27b0;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: #e8f5e9;
            border-radius: 8px;
            padding: 12px 20px;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .management-container {
                padding: 15px;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .btn-action {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .charts-grid {
                gap: 15px;
            }
            
            .chart-container {
                height: 300px;
            }
        }

        /* Nuevos estilos para m�s gr�ficas */
        .more-charts-btn {
            background: #f8fff8;
            border: 2px dashed #4caf50;
            color: #4caf50;
            padding: 15px;
            text-align: center;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .more-charts-btn:hover {
            background: #4caf50;
            color: white;
        }
        
        .chart-container-small {
            height: 280px;
        }
        
        .chart-grid-4 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        @media (max-width: 1200px) {
            .chart-grid-4 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar Principal -->
    <nav class="navbar navbar-main navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="main.php">
                <i class="fas fa-chart-line"></i> CECYTE - Gesti&oacute;n Estad&iacute;sticas
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="main.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Gesti&oacute;n
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="gestion_alumnos.php">Alumnos</a></li>
                            <li><a class="dropdown-item" href="gestion_maestros.php">Maestros</a></li>
                            <li><a class="dropdown-item" href="gestion_carreras.php">Carreras</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item active" href="gestion_estadisticas.php">Estad&iacute;sticas</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="estadisticas.php">
                            <i class="fas fa-chart-bar"></i> Ver Estad&iacute;sticas
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['username'] ?? 'Usuario'; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="configuracion.php">Configuraci&oacute;n</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">Cerrar Sesi&oacute;n</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="management-container">
        <nav aria-label="breadcrumb" class="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="main.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="estadisticas.php">Estad&iacute;sticas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Gesti&oacute;n de Estad&iacute;sticas</li>
            </ol>
        </nav>

        <!-- Encabezado de p�gina -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-line me-2"></i>Panel de Gesti&oacute;n de Estad&iacute;sticas
            </h1>
            <p class="page-subtitle">
                An&aacute;lisis avanzado, generaci&oacute;n de reportes y gesti&oacute;n de indicadores acad&eacute;micos
            </p>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <span class="badge bg-success me-2"><?php echo $total_alumnos; ?> Alumnos</span>
                    <span class="badge bg-primary me-2"><?php echo $total_maestros; ?> Maestros</span>
                    <span class="badge bg-info"><?php echo $total_carreras; ?> Carreras</span>
                </div>
                <div class="text-muted">
                    &Uacute;ltima actualizaci&oacute;n: <?php echo date('d/m/Y H:i:s'); ?>
                </div>
            </div>
        </div>

        <!-- Panel de estad�sticas principales -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-number"><?php echo $total_alumnos; ?></div>
                <div class="stat-label">Total de Alumnos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-number"><?php echo $total_maestros; ?></div>
                <div class="stat-label">Total de Maestros</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-number"><?php echo $total_carreras; ?></div>
                <div class="stat-label">Carreras Activas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-number">
                    <?php echo $promedio_general; ?>
                </div>
                <div class="stat-label">Promedio General</div>
            </div>
        </div>

        <!-- Panel de filtros -->
        <div class="filters-panel">
            <h4 class="filter-title">
                <i class="fas fa-filter me-2"></i>Filtros Avanzados de An&aacute;lisis
            </h4>
            
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Semestre</label>
                    <select class="form-select" name="semestre">
                        <option value="">Todos los semestres</option>
                        <?php foreach ($semestres as $semestre): ?>
                            <option value="<?php echo $semestre['id_semestre']; ?>" 
                                <?php echo $semestre_filtro == $semestre['id_semestre'] ? 'selected' : ''; ?>>
                                Semestre <?php echo $semestre['id_semestre']; ?>
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
                                <?php echo $grupo_filtro == $grupo['id_grupo'] ? 'selected' : ''; ?>>
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
                                <?php echo $carrera_filtro == $carrera['id_carrera'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($carrera['nombre_carrera']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">G&eacute;nero</label>
                    <select class="form-select" name="genero">
                        <option value="">Todos</option>
                        <?php foreach ($generos as $genero): ?>
                            <option value="<?php echo $genero['genero']; ?>" 
                                <?php echo $genero_filtro == $genero['genero'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($genero['genero']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Turno</label>
                    <select class="form-select" name="turno">
                        <option value="">Todos</option>
                        <?php foreach ($turnos as $turno): ?>
                            <option value="<?php echo $turno['turno']; ?>" 
                                <?php echo $turno_filtro == $turno['turno'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($turno['turno']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Rango de Fechas</label>
                    <input type="text" class="form-control" id="daterange" name="daterange" 
                           placeholder="Seleccionar rango de fechas">
                    <input type="hidden" name="fecha_inicio" id="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                    <input type="hidden" name="fecha_fin" id="fecha_fin" value="<?php echo $fecha_fin; ?>">
                </div>
                
                <div class="col-md-12 mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-filter me-2"></i>Aplicar Filtros
                    </button>
                    <a href="gestion_estadisticas.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Limpiar Filtros
                    </a>
                    
                    <div class="float-end">
                        <span class="badge bg-info me-2">
                            <i class="fas fa-database me-1"></i> Datos en tiempo real
                        </span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tarjetas de an�lisis -->
        <div class="analysis-cards">
            <div class="analysis-card rendimiento">
                <h5 class="analysis-title">
                    <i class="fas fa-chart-line"></i> Rendimiento Acad&eacute;mico
                </h5>
                <div class="analysis-value">
                    <?php echo $promedio_general; ?>
                </div>
                <div class="analysis-content">
                    Promedio general de calificaciones del sistema
                </div>
            </div>
            
            <div class="analysis-card desercion">
                <h5 class="analysis-title">
                    <i class="fas fa-exclamation-triangle"></i> &Iacute;ndice de Deserci&oacute;n
                </h5>
                <div class="analysis-value">
                    <?php 
                    $desercion_total = !empty($desercion_semestre) ? 
                        round(array_sum(array_column($desercion_semestre, 'porcentaje_bajas')) / count($desercion_semestre), 1) : 0;
                    echo $desercion_total . '%';
                    ?>
                </div>
                <div class="analysis-content">
                    Promedio de deserci&oacute;n por semestre
                </div>
            </div>
            
            <div class="analysis-card asistencia">
                <h5 class="analysis-title">
                    <i class="fas fa-calendar-check"></i> Asistencia Promedio
                </h5>
                <div class="analysis-value">
                    <?php 
                    $asistencia_promedio = !empty($asistencia_mensual) ? 
                        round(array_sum(array_column($asistencia_mensual, 'porcentaje_asistencia')) / count($asistencia_mensual), 1) : 0;
                    echo $asistencia_promedio . '%';
                    ?>
                </div>
                <div class="analysis-content">
                    &Uacute;ltimos 6 meses
                </div>
            </div>
            
            <div class="analysis-card eficiencia">
                <h5 class="analysis-title">
                    <i class="fas fa-graduation-cap"></i> Eficiencia Terminal
                </h5>
                <div class="analysis-value">
                    <?php 
                    $eficiencia_total = !empty($eficiencia_carrera) ? 
                        round(array_sum(array_column($eficiencia_carrera, 'eficiencia_terminal')) / count($eficiencia_carrera), 1) : 0;
                    echo $eficiencia_total . '%';
                    ?>
                </div>
                <div class="analysis-content">
                    Promedio por carrera
                </div>
            </div>
        </div>

        <!-- Secci�n de gr�ficos principales -->
        <div class="charts-section">
            <h4 class="section-title">
                <i class="fas fa-chart-pie me-2"></i>Visualizaci&oacute;n de Datos Principales
            </h4>
            
            <div class="charts-grid">
                <!-- Gr�fico 1: Distribuci�n por g�nero -->
                <div class="chart-container">
                    <canvas id="chartGenero"></canvas>
                </div>
                
                <!-- Gr�fico 2: Alumnos por semestre -->
                <div class="chart-container">
                    <canvas id="chartSemestre"></canvas>
                </div>
                
                <!-- Gr�fico 3: Promedio por carrera -->
                <div class="chart-container">
                    <canvas id="chartCarreras"></canvas>
                </div>
                
                <!-- Gr�fico 4: Asistencia mensual -->
                <div class="chart-container">
                    <canvas id="chartAsistencia"></canvas>
                </div>
                
                <!-- Gr�fico 5: Deserci�n por semestre -->
                <div class="chart-container">
                    <canvas id="chartDesercion"></canvas>
                </div>
                
                <!-- Gr�fico 6: Eficiencia terminal -->
                <div class="chart-container">
                    <canvas id="chartEficiencia"></canvas>
                </div>
            </div>
        </div>

        <!-- Bot�n para mostrar m�s gr�ficas -->
        <div class="more-charts-btn" onclick="toggleMoreCharts()">
            <i class="fas fa-chart-bar me-2"></i>Mostrar m&aacute;s gr&aacute;ficas estad&iacute;sticas
        </div>

        <!-- Secci�n de gr�ficas adicionales (oculta inicialmente) -->
        <div class="charts-section" id="moreChartsSection" style="display: none;">
            <h4 class="section-title">
                <i class="fas fa-chart-bar me-2"></i>Gr&aacute;ficas Estad&iacute;sticas Adicionales
            </h4>
            
            <div class="charts-grid">
                <!-- Gr�fico 7: Discapacidades -->
                <div class="chart-container">
                    <canvas id="chartDiscapacidad"></canvas>
                </div>
                
                <!-- Gr�fico 8: Edad promedio por semestre -->
                <div class="chart-container">
                    <canvas id="chartEdad"></canvas>
                </div>
                
                <!-- Gr�fico 9: Distribuci�n por turno -->
                <div class="chart-container">
                    <canvas id="chartTurno"></canvas>
                </div>
                
                <!-- Gr�fico 10: Calificaciones por rango -->
                <div class="chart-container">
                    <canvas id="chartCalificaciones"></canvas>
                </div>
                
                <!-- Gr�fico 11: Alumnos con beca -->
                <div class="chart-container">
                    <canvas id="chartBeca"></canvas>
                </div>
                
                <!-- Gr�fico 12: Asistencia por d�a -->
                <div class="chart-container">
                    <canvas id="chartDia"></canvas>
                </div>
            </div>
            
            <!-- Segunda fila de gr�ficas peque�as -->
            <div class="chart-grid-4 mt-4">
                <div class="chart-container chart-container-small">
                    <canvas id="chartEstadoCivil"></canvas>
                </div>
                
                <div class="chart-container chart-container-small">
                    <canvas id="chartTipoSangre"></canvas>
                </div>
            </div>
        </div>

        <!-- Secci�n de tablas de datos -->
        <div class="data-tables">
            <h4 class="section-title">
                <i class="fas fa-table me-2"></i>Tablas de An&aacute;lisis Detallado
            </h4>
            
            <!-- Pestanas para diferentes tablas -->
            <ul class="nav nav-tabs mb-4" id="dataTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="rendimiento-tab" data-bs-toggle="tab" data-bs-target="#rendimiento" type="button">
                        <i class="fas fa-chart-line me-2"></i>Rendimiento Docente
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="desercion-tab" data-bs-toggle="tab" data-bs-target="#desercion" type="button">
                        <i class="fas fa-exclamation-triangle me-2"></i>Deserci&oacute;n
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="discapacidad-tab" data-bs-toggle="tab" data-bs-target="#discapacidad" type="button">
                        <i class="fas fa-wheelchair me-2"></i>Discapacidades
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="edad-tab" data-bs-toggle="tab" data-bs-target="#edad" type="button">
                        <i class="fas fa-users me-2"></i>Distribuci&oacute;n por Edad
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="calificaciones-tab" data-bs-toggle="tab" data-bs-target="#calificaciones" type="button">
                        <i class="fas fa-star me-2"></i>Calificaciones por Rango
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="turno-tab" data-bs-toggle="tab" data-bs-target="#turno" type="button">
                        <i class="fas fa-clock me-2"></i>Distribuci&oacute;n por Turno
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="dataTabsContent">
                <!-- Tabla de rendimiento docente -->
                <div class="tab-pane fade show active" id="rendimiento" role="tabpanel">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Docente</th>
                                    <th>Alumnos Atendidos</th>
                                    <th>Promedio Calificaciones</th>
                                    <th>Aprobados</th>
                                    <th>Reprobados</th>
                                    <th>% Aprobaci&oacute;n</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rendimiento_docente as $docente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($docente['nombre']); ?></td>
                                    <td><?php echo $docente['alumnos_atendidos']; ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                            if ($docente['promedio_calificaciones'] >= 8.5) echo 'badge-excelente';
                                            elseif ($docente['promedio_calificaciones'] >= 7) echo 'badge-bueno';
                                            elseif ($docente['promedio_calificaciones'] >= 6) echo 'badge-regular';
                                            else echo 'badge-bajo';
                                            ?>
                                        ">
                                            <?php echo $docente['promedio_calificaciones']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $docente['aprobados']; ?></td>
                                    <td><?php echo $docente['reprobados']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar 
                                                <?php 
                                                if ($docente['porcentaje_aprobacion'] >= 80) echo 'bg-success';
                                                elseif ($docente['porcentaje_aprobacion'] >= 60) echo 'bg-warning';
                                                else echo 'bg-danger';
                                                ?>
                                            " style="width: <?php echo $docente['porcentaje_aprobacion']; ?>%">
                                                <?php echo $docente['porcentaje_aprobacion']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tabla de deserci�n -->
                <div class="tab-pane fade" id="desercion" role="tabpanel">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Semestre</th>
                                    <th>Total Alumnos</th>
                                    <th>Bajas</th>
                                    <th>% Deserci&oacute;n</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($desercion_semestre as $item): ?>
                                <tr>
                                    <td>Semestre <?php echo $item['id_semestre']; ?></td>
                                    <td><?php echo $item['total_alumnos']; ?></td>
                                    <td><?php echo $item['bajas']; ?></td>
                                    <td><?php echo $item['porcentaje_bajas']; ?>%</td>
                                    <td>
                                        <?php 
                                        $color = '';
                                        $estado = '';
                                        if ($item['porcentaje_bajas'] > 15) {
                                            $color = 'danger';
                                            $estado = 'Cr�tico';
                                        } elseif ($item['porcentaje_bajas'] > 10) {
                                            $color = 'warning';
                                            $estado = 'Alto';
                                        } elseif ($item['porcentaje_bajas'] > 5) {
                                            $color = 'info';
                                            $estado = 'Moderado';
                                        } else {
                                            $color = 'success';
                                            $estado = 'Bajo';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo $estado; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tabla de discapacidades -->
                <div class="tab-pane fade" id="discapacidad" role="tabpanel">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tipo de Discapacidad</th>
                                    <th>Cantidad</th>
                                    <th>Porcentaje</th>
                                    <th>Proporci&oacute;n</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($discapacidades as $disc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($disc['tipo_discapacidad']); ?></td>
                                    <td><?php echo $disc['cantidad']; ?></td>
                                    <td><?php echo $disc['porcentaje']; ?>%</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-info" style="width: <?php echo $disc['porcentaje']; ?>%">
                                                <?php echo $disc['porcentaje']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tabla de distribuci�n por edad -->
                <div class="tab-pane fade" id="edad" role="tabpanel">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Semestre</th>
                                    <th>Edad Promedio</th>
                                    <th>Edad M&iacute;nima</th>
                                    <th>Edad M&aacute;xima</th>
                                    <th>Total Alumnos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($edad_promedio as $edad): ?>
                                <tr>
                                    <td>Semestre <?php echo $edad['id_semestre']; ?></td>
                                    <td><?php echo $edad['edad_promedio']; ?> a�os</td>
                                    <td><?php echo $edad['edad_minima']; ?> a�os</td>
                                    <td><?php echo $edad['edad_maxima']; ?> a�os</td>
                                    <td><?php echo $edad['total']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tabla de calificaciones por rango -->
                <div class="tab-pane fade" id="calificaciones" role="tabpanel">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rango de Calificaci&oacute;n</th>
                                    <th>Cantidad</th>
                                    <th>Porcentaje</th>
                                    <th>Proporci&oacute;n</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($calificaciones_rango as $calif): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($calif['rango']); ?></td>
                                    <td><?php echo $calif['cantidad']; ?></td>
                                    <td><?php echo $calif['porcentaje']; ?>%</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $calif['porcentaje']; ?>%">
                                                <?php echo $calif['porcentaje']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tabla de distribuci�n por turno -->
                <div class="tab-pane fade" id="turno" role="tabpanel">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Turno</th>
                                    <th>Cantidad</th>
                                    <th>Porcentaje</th>
                                    <th>Proporci&oacute;n</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($distribucion_turno as $turno): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($turno['turno']); ?></td>
                                    <td><?php echo $turno['cantidad']; ?></td>
                                    <td><?php echo $turno['porcentaje']; ?>%</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-primary" style="width: <?php echo $turno['porcentaje']; ?>%">
                                                <?php echo $turno['porcentaje']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de acci�n -->
        <div class="action-buttons">
            <button class="btn-action btn-export" onclick="exportarExcel()">
                <i class="fas fa-file-excel"></i> Exportar a Excel
            </button>
            
            <button class="btn-action btn-export" onclick="generarReportePDF()">
                <i class="fas fa-file-pdf"></i> Generar Reporte PDF
            </button>
            
            <button class="btn-action btn-refresh" onclick="actualizarDatos()">
                <i class="fas fa-sync-alt"></i> Actualizar Datos
            </button>
            
            <button class="btn-action btn-print" onclick="imprimirReporte()">
                <i class="fas fa-print"></i> Imprimir Reporte
            </button>
            
            <a href="configuracion_estadisticas.php" class="btn-action btn-export">
                <i class="fas fa-cog"></i> Configurar Indicadores
            </a>
        </div>

        <!-- Pie de p�gina -->
        <footer class="mt-5 pt-4 border-top text-center text-muted">
            <p>Sistema de Gesti&oacute;n de Estad&iacute;sticas - CECyTE Santa Catarina N.L.</p>
            <p class="small">� <?php echo date('Y'); ?> Todos los derechos reservados</p>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        // Datos para gr�ficos
        const chartData = <?php echo json_encode($chart_data); ?>;
        
        // Inicializar date range picker
        $(function() {
            $('#daterange').daterangepicker({
                opens: 'left',
                locale: {
                    format: 'DD/MM/YYYY',
                    separator: ' - ',
                    applyLabel: 'Aplicar',
                    cancelLabel: 'Cancelar',
                    daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                    monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
                }
            }, function(start, end, label) {
                $('#fecha_inicio').val(start.format('YYYY-MM-DD'));
                $('#fecha_fin').val(end.format('YYYY-MM-DD'));
            });
        });
        
        // Funci�n para mostrar/ocultar m�s gr�ficas
        function toggleMoreCharts() {
            const section = document.getElementById('moreChartsSection');
            const btn = document.querySelector('.more-charts-btn i');
            if (section.style.display === 'none') {
                section.style.display = 'block';
                btn.className = 'fas fa-chart-bar me-2';
                document.querySelector('.more-charts-btn').innerHTML = '<i class="fas fa-chart-bar me-2"></i>Ocultar gr�ficas adicionales';
            } else {
                section.style.display = 'none';
                btn.className = 'fas fa-chart-bar me-2';
                document.querySelector('.more-charts-btn').innerHTML = '<i class="fas fa-chart-bar me-2"></i>Mostrar m�s gr�ficas estad�sticas';
            }
        }
        
        // Inicializar gr�ficos
        document.addEventListener('DOMContentLoaded', function() {
            // Gr�fico 1: Distribuci�n por g�nero
            if (chartData.genero_labels && chartData.genero_labels.length > 0) {
                new Chart(document.getElementById('chartGenero'), {
                    type: 'pie',
                    data: {
                        labels: chartData.genero_labels,
                        datasets: [{
                            data: chartData.genero_data,
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(255, 206, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)'
                            ],
                            borderColor: [
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 99, 132, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Distribuci�n por G�nero'
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 2: Alumnos por semestre
            if (chartData.semestre_labels && chartData.semestre_labels.length > 0) {
                new Chart(document.getElementById('chartSemestre'), {
                    type: 'bar',
                    data: {
                        labels: chartData.semestre_labels,
                        datasets: [{
                            label: 'Alumnos',
                            data: chartData.semestre_data,
                            backgroundColor: 'rgba(46, 125, 50, 0.7)',
                            borderColor: 'rgba(46, 125, 50, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Alumnos por Semestre'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 3: Promedio por carrera
            if (chartData.carrera_labels && chartData.carrera_labels.length > 0) {
                new Chart(document.getElementById('chartCarreras'), {
                    type: 'bar',
                    data: {
                        labels: chartData.carrera_labels,
                        datasets: [{
                            label: 'Promedio',
                            data: chartData.carrera_data,
                            backgroundColor: 'rgba(33, 150, 243, 0.7)',
                            borderColor: 'rgba(33, 150, 243, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Promedio por Carrera'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 10
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 4: Asistencia mensual
            if (chartData.mes_labels && chartData.mes_labels.length > 0) {
                new Chart(document.getElementById('chartAsistencia'), {
                    type: 'line',
                    data: {
                        labels: chartData.mes_labels,
                        datasets: [{
                            label: 'Asistencia (%)',
                            data: chartData.asistencia_data,
                            borderColor: 'rgba(255, 152, 0, 1)',
                            backgroundColor: 'rgba(255, 152, 0, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Asistencia Mensual'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 5: Deserci�n por semestre
            if (chartData.desercion_labels && chartData.desercion_labels.length > 0) {
                new Chart(document.getElementById('chartDesercion'), {
                    type: 'bar',
                    data: {
                        labels: chartData.desercion_labels,
                        datasets: [{
                            label: 'Deserci�n (%)',
                            data: chartData.desercion_data,
                            backgroundColor: chartData.desercion_data.map(val => 
                                val > 15 ? 'rgba(244, 67, 54, 0.7)' : 
                                val > 10 ? 'rgba(255, 152, 0, 0.7)' : 
                                val > 5 ? 'rgba(33, 150, 243, 0.7)' : 
                                'rgba(76, 175, 80, 0.7)'
                            ),
                            borderColor: chartData.desercion_data.map(val => 
                                val > 15 ? 'rgba(244, 67, 54, 1)' : 
                                val > 10 ? 'rgba(255, 152, 0, 1)' : 
                                val > 5 ? 'rgba(33, 150, 243, 1)' : 
                                'rgba(76, 175, 80, 1)'
                            ),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Deserci�n por Semestre'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 6: Eficiencia terminal
            if (chartData.eficiencia_labels && chartData.eficiencia_labels.length > 0) {
                new Chart(document.getElementById('chartEficiencia'), {
                    type: 'bar',
                    data: {
                        labels: chartData.eficiencia_labels,
                        datasets: [{
                            label: 'Eficiencia (%)',
                            data: chartData.eficiencia_data,
                            backgroundColor: 'rgba(156, 39, 176, 0.7)',
                            borderColor: 'rgba(156, 39, 176, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Eficiencia Terminal por Carrera'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 7: Discapacidades
            if (chartData.discapacidad_labels && chartData.discapacidad_labels.length > 0) {
                new Chart(document.getElementById('chartDiscapacidad'), {
                    type: 'doughnut',
                    data: {
                        labels: chartData.discapacidad_labels,
                        datasets: [{
                            data: chartData.discapacidad_data,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 206, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(153, 102, 255, 0.8)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Distribuci�n de Discapacidades'
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 8: Edad promedio por semestre
            if (chartData.edad_labels && chartData.edad_labels.length > 0) {
                new Chart(document.getElementById('chartEdad'), {
                    type: 'line',
                    data: {
                        labels: chartData.edad_labels,
                        datasets: [
                            {
                                label: 'Edad Promedio',
                                data: chartData.edad_promedio_data,
                                borderColor: 'rgba(54, 162, 235, 1)',
                                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Edad M�nima',
                                data: chartData.edad_minima_data,
                                borderColor: 'rgba(75, 192, 192, 1)',
                                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                borderWidth: 2,
                                fill: false,
                                tension: 0.4
                            },
                            {
                                label: 'Edad M�xima',
                                data: chartData.edad_maxima_data,
                                borderColor: 'rgba(255, 99, 132, 1)',
                                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                                borderWidth: 2,
                                fill: false,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Edad por Semestre'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                title: {
                                    display: true,
                                    text: 'Edad (a�os)'
                                }
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 9: Distribuci�n por turno
            if (chartData.turno_labels && chartData.turno_labels.length > 0) {
                new Chart(document.getElementById('chartTurno'), {
                    type: 'pie',
                    data: {
                        labels: chartData.turno_labels,
                        datasets: [{
                            data: chartData.turno_data,
                            backgroundColor: [
                                'rgba(255, 159, 64, 0.8)',
                                'rgba(255, 205, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(54, 162, 235, 0.8)'
                            ],
                            borderColor: [
                                'rgba(255, 159, 64, 1)',
                                'rgba(255, 205, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(54, 162, 235, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Distribuci�n por Turno'
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 10: Calificaciones por rango
            if (chartData.calificacion_labels && chartData.calificacion_labels.length > 0) {
                new Chart(document.getElementById('chartCalificaciones'), {
                    type: 'bar',
                    data: {
                        labels: chartData.calificacion_labels,
                        datasets: [{
                            label: 'Cantidad',
                            data: chartData.calificacion_data,
                            backgroundColor: chartData.calificacion_labels.map(label => {
                                if (label.includes('Excelente')) return 'rgba(76, 175, 80, 0.7)';
                                if (label.includes('Bueno')) return 'rgba(139, 195, 74, 0.7)';
                                if (label.includes('Regular')) return 'rgba(255, 193, 7, 0.7)';
                                if (label.includes('Suficiente')) return 'rgba(255, 152, 0, 0.7)';
                                return 'rgba(244, 67, 54, 0.7)';
                            }),
                            borderColor: chartData.calificacion_labels.map(label => {
                                if (label.includes('Excelente')) return 'rgba(76, 175, 80, 1)';
                                if (label.includes('Bueno')) return 'rgba(139, 195, 74, 1)';
                                if (label.includes('Regular')) return 'rgba(255, 193, 7, 1)';
                                if (label.includes('Suficiente')) return 'rgba(255, 152, 0, 1)';
                                return 'rgba(244, 67, 54, 1)';
                            }),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Calificaciones por Rango'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 11: Alumnos con beca
            if (chartData.beca_labels && chartData.beca_labels.length > 0) {
                new Chart(document.getElementById('chartBeca'), {
                    type: 'doughnut',
                    data: {
                        labels: chartData.beca_labels,
                        datasets: [{
                            data: chartData.beca_data,
                            backgroundColor: [
                                'rgba(76, 175, 80, 0.8)',
                                'rgba(244, 67, 54, 0.8)'
                            ],
                            borderColor: [
                                'rgba(76, 175, 80, 1)',
                                'rgba(244, 67, 54, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Alumnos con Beca'
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 12: Asistencia por d�a
            if (chartData.dia_labels && chartData.dia_labels.length > 0) {
                new Chart(document.getElementById('chartDia'), {
                    type: 'bar',
                    data: {
                        labels: chartData.dia_labels,
                        datasets: [{
                            label: 'Asistencia (%)',
                            data: chartData.dia_data,
                            backgroundColor: 'rgba(33, 150, 243, 0.7)',
                            borderColor: 'rgba(33, 150, 243, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Asistencia por D�a de la Semana'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 13: Estado civil
            if (chartData.estado_civil_labels && chartData.estado_civil_labels.length > 0) {
                new Chart(document.getElementById('chartEstadoCivil'), {
                    type: 'pie',
                    data: {
                        labels: chartData.estado_civil_labels,
                        datasets: [{
                            data: chartData.estado_civil_data,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 206, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Estado Civil'
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Gr�fico 14: Tipo de sangre
            if (chartData.sangre_labels && chartData.sangre_labels.length > 0) {
                new Chart(document.getElementById('chartTipoSangre'), {
                    type: 'doughnut',
                    data: {
                        labels: chartData.sangre_labels,
                        datasets: [{
                            data: chartData.sangre_data,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 206, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(153, 102, 255, 0.8)',
                                'rgba(255, 159, 64, 0.8)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Tipo de Sangre'
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });
        
        // Funciones de acci�n
        function exportarExcel() {
            window.location.href = 'gestion_estadisticas.php?exportar=excel';
        }
        
        function generarReportePDF() {
            alert('Funcionalidad de generaci�n de PDF en desarrollo');
            // Aqu� ir�a la l�gica para generar PDF
        }
        
        function actualizarDatos() {
            location.reload();
        }
        
        function imprimirReporte() {
            window.print();
        }
        
        // Actualizaci�n autom�tica cada 5 minutos
        setInterval(actualizarDatos, 300000);
    </script>
</body>
</html>