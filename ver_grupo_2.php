<?php
session_start();

// Verificar permisos
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Verificar que se proporcionó un ID de grupo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_carreras.php');
    exit();
}

$id_grupo = (int)$_GET['id'];

// Obtener información completa del grupo
$sql_grupo = "SELECT g.*, 
                     c.nombre as carrera_nombre,
                     c.clave as carrera_clave,
                     COUNT(DISTINCT a.id_alumno) as total_alumnos,
                     COUNT(DISTINCT CASE WHEN a.activo = 'Activo' THEN a.id_alumno END) as alumnos_activos,
                     (SELECT COUNT(DISTINCT hm.id_maestro) 
                      FROM horarios_maestros hm 
                      WHERE hm.id_grupo = g.id_grupo) as total_maestros
              FROM grupos g 
              LEFT JOIN carreras c ON g.id_carrera = c.id_carrera 
              LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo 
              WHERE g.id_grupo = :id_grupo";
$stmt_grupo = $con->prepare($sql_grupo);
$stmt_grupo->bindValue(':id_grupo', $id_grupo, PDO::PARAM_INT);
$stmt_grupo->execute();
$grupo = $stmt_grupo->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header('Location: gestion_carreras.php');
    exit();
}

// Obtener alumnos del grupo
$sql_alumnos = "SELECT a.* 
                FROM alumnos a 
                WHERE a.id_grupo = :id_grupo 
                AND a.activo = 'Activo'
                ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre";
$stmt_alumnos = $con->prepare($sql_alumnos);
$stmt_alumnos->bindValue(':id_grupo', $id_grupo, PDO::PARAM_INT);
$stmt_alumnos->execute();
$alumnos = $stmt_alumnos->fetchAll(PDO::FETCH_ASSOC);

// Obtener horarios del grupo
$sql_horarios = "SELECT hm.*, 
                        m.materia,
                        au.nombre as aula_nombre,
                        ma.nombre as maestro_nombre,
                        ma.apellido_paterno as maestro_apellido
                 FROM horarios_maestros hm 
                 LEFT JOIN materias m ON hm.id_materia = m.id_materia 
                 LEFT JOIN aulas au ON hm.id_aula = au.id_aula 
                 LEFT JOIN maestros ma ON hm.id_maestro = ma.id_maestro 
                 WHERE hm.id_grupo = :id_grupo 
                 ORDER BY 
                   FIELD(hm.dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'),
                   hm.hora_inicio";
$stmt_horarios = $con->prepare($sql_horarios);
$stmt_horarios->bindValue(':id_grupo', $id_grupo, PDO::PARAM_INT);
$stmt_horarios->execute();
$horarios = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);

// Obtener maestros del grupo
$sql_maestros = "SELECT DISTINCT m.* 
                 FROM maestros m 
                 JOIN horarios_maestros hm ON m.id_maestro = hm.id_maestro 
                 WHERE hm.id_grupo = :id_grupo 
                 ORDER BY m.apellido_paterno, m.apellido_materno, m.nombre";
$stmt_maestros = $con->prepare($sql_maestros);
$stmt_maestros->bindValue(':id_grupo', $id_grupo, PDO::PARAM_INT);
$stmt_maestros->execute();
$maestros = $stmt_maestros->fetchAll(PDO::FETCH_ASSOC);

// Organizar horarios por día
$horarios_por_dia = [];
foreach ($horarios as $horario) {
    $dia = $horario['dia'];
    if (!isset($horarios_por_dia[$dia])) {
        $horarios_por_dia[$dia] = [];
    }
    $horarios_por_dia[$dia][] = $horario;
}

// Obtener estadísticas de asistencia
$sql_asistencia = "SELECT 
                    COUNT(DISTINCT fecha) as total_clases,
                    COUNT(CASE WHEN estado = 'Presente' THEN 1 END) as total_presentes,
                    COUNT(CASE WHEN estado = 'Falta' THEN 1 END) as total_faltas,
                    COUNT(CASE WHEN estado = 'Retardo' THEN 1 END) as total_retardos,
                    COUNT(CASE WHEN estado = 'Justificada' THEN 1 END) as total_justificadas
                   FROM asistencias_clase 
                   WHERE id_grupo = :id_grupo 
                   AND fecha >= CURDATE() - INTERVAL 30 DAY";
$stmt_asistencia = $con->prepare($sql_asistencia);
$stmt_asistencia->bindValue(':id_grupo', $id_grupo, PDO::PARAM_INT);
$stmt_asistencia->execute();
$estadisticas_asistencia = $stmt_asistencia->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($grupo['nombre']); ?> - Detalles - CECYTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #1a5330 0%, #2e7d32 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header h1 i {
            color: #c8e6c9;
        }

        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
        }

        .nav-links a:nth-child(1) { background: #2e7d32; }
        .nav-links a:nth-child(2) { background: #4caf50; }
        .nav-links a:nth-child(3) { background: #8bc34a; }
        .nav-links a:nth-child(4) { background: #1a5330; }
        .nav-links a:nth-child(5) { background: #4caf50; }

        .nav-links a:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            filter: brightness(110%);
        }

        .card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #4caf50;
        }

        .card h2 {
            color: #1a5330;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #c8e6c9;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h2 i {
            color: #2e7d32;
        }

        .card h3 {
            color: #1a5330;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 15px;
        }

        .btn-primary {
            background: linear-gradient(to right, #2e7d32, #4caf50);
            color: white;
            border: 1px solid #1a5330;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.4);
            background: linear-gradient(to right, #4caf50, #2e7d32);
        }

        .btn-secondary {
            background: #8bc34a;
            color: #1a5330;
            border: 1px solid #4caf50;
        }

        .btn-secondary:hover {
            background: #4caf50;
            color: white;
            transform: translateY(-3px);
        }

        .btn-success {
            background: #2e7d32;
            color: white;
            border: 1px solid #1a5330;
        }

        .btn-success:hover {
            background: #1a5330;
            transform: translateY(-3px);
        }

        .btn-info {
            background: #4caf50;
            color: white;
            border: 1px solid #2e7d32;
        }

        .btn-info:hover {
            background: #388e3c;
            transform: translateY(-3px);
        }

        .btn-warning {
            background: #8bc34a;
            color: #1a5330;
            border: 1px solid #4caf50;
        }

        .btn-warning:hover {
            background: #7cb342;
            color: white;
            transform: translateY(-3px);
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .badge-success {
            background: #c8e6c9;
            color: #1a5330;
            border-color: #4caf50;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }

        .badge-info {
            background: #c8e6c9;
            color: #1a5330;
            border-color: #4caf50;
        }

        .badge-primary {
            background: #1a5330;
            color: white;
            border-color: #2e7d32;
        }

        .stats-card {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #c8e6c9;
            transition: transform 0.3s;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #2e7d32;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #1a5330;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-section {
            background: #f9fff9;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #c8e6c9;
        }

        .info-section h3 {
            color: #1a5330;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c8e6c9;
            font-size: 18px;
        }

        .info-row {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #c8e6c9;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #1a5330;
            min-width: 150px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-value {
            color: #2e7d32;
            flex: 1;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #c8e6c9;
        }

        .tabla thead {
            background: linear-gradient(to right, #1a5330, #2e7d32);
            color: white;
        }

        .tabla th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 15px;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .tabla th:last-child {
            border-right: none;
        }

        .tabla tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.3s;
        }

        .tabla tbody tr:hover {
            background-color: #c8e6c9;
        }

        .tabla tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .tabla tbody tr:nth-child(even):hover {
            background-color: #c8e6c9;
        }

        .tabla td {
            padding: 15px;
            color: #1a5330;
            font-size: 14.5px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-volver {
            background: #8bc34a;
            color: #1a5330;
            border: 1px solid #4caf50;
        }

        .btn-volver:hover {
            background: #4caf50;
            color: white;
        }

        .horario-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .dia-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #c8e6c9;
            border-top: 4px solid #4caf50;
        }

        .dia-card h4 {
            color: #1a5330;
            margin-bottom: 15px;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #c8e6c9;
        }

        .clase-item {
            background: #f9fff9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            border-left: 4px solid #8bc34a;
        }

        .clase-item:last-child {
            margin-bottom: 0;
        }

        .clase-hora {
            font-weight: 700;
            color: #1a5330;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .clase-materia {
            color: #2e7d32;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .clase-detalles {
            font-size: 13px;
            color: #4caf50;
        }

        .clase-detalles i {
            margin-right: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #1a5330;
            background-color: #c8e6c9;
            border-radius: 10px;
            border: 2px dashed #8bc34a;
        }

        .empty-state i {
            font-size: 50px;
            margin-bottom: 20px;
            color: #4caf50;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #1a5330;
        }

        .empty-state p {
            font-size: 15px;
            max-width: 500px;
            margin: 0 auto;
            color: #2e7d32;
        }

        .estatus-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            min-width: 80px;
        }

        .estatus-activo {
            background: #c8e6c9;
            color: #1a5330;
            border: 1px solid #4caf50;
        }

        .estatus-inactivo {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }

        .alumno-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            margin-right: 10px;
        }

        .alumno-info {
            display: flex;
            align-items: center;
        }

        .alumno-nombre {
            font-weight: 600;
            color: #1a5330;
        }

        .alumno-matricula {
            font-size: 13px;
            color: #2e7d32;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .tabla {
                display: block;
                overflow-x: auto;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .horario-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-label {
                min-width: auto;
            }
            
            .alumno-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .alumno-avatar {
                margin-bottom: 5px;
            }
        }

        .carrera-info {
            background: #f9fff9;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #c8e6c9;
            border-left: 5px solid #2e7d32;
        }

        .carrera-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .carrera-header h2 {
            margin: 0;
            color: #1a5330;
            font-size: 20px;
        }

        .carrera-clave {
            background: #1a5330;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-users"></i> Detalles del Grupo</h1>
            <div class="nav-links">
                <a href="main.php"><i class="fas fa-home"></i> Panel Principal</a>
                <a href="gestion_carreras.php"><i class="fas fa-graduation-cap"></i> Carreras</a>
                <a href="grupos_carrera.php?id=<?php echo $grupo['id_carrera']; ?>"><i class="fas fa-users"></i> Grupos</a>
                <a href="gestion_alumnos.php"><i class="fas fa-users"></i> Alumnos</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesi&oacute;n</a>
            </div>
        </div>
        
        <!-- Información de la Carrera -->
        <div class="carrera-info">
            <div class="carrera-header">
                <h2><?php echo htmlspecialchars($grupo['carrera_nombre']); ?></h2>
                <span class="carrera-clave"><?php echo htmlspecialchars($grupo['carrera_clave']); ?></span>
            </div>
            <div style="color: #2e7d32; font-size: 14px;">
                <i class="fas fa-graduation-cap"></i> Carrera a la que pertenece el grupo
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="card">
            <h2><i class="fas fa-chart-line"></i> Estadísticas del Grupo</h2>
            <div class="stats-card">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $grupo['total_alumnos'] ?? 0; ?></div>
                    <div class="stat-label">Total Alumnos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $grupo['alumnos_activos'] ?? 0; ?></div>
                    <div class="stat-label">Alumnos Activos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $grupo['total_maestros'] ?? 0; ?></div>
                    <div class="stat-label">Maestros</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $estadisticas_asistencia['total_clases'] ?? 0; ?></div>
                    <div class="stat-label">Clases (30 días)</div>
                </div>
            </div>
        </div>
        
        <!-- Información Principal -->
        <div class="card">
            <h2><i class="fas fa-info-circle"></i> Información del Grupo</h2>
            
            <div class="info-grid">
                <div class="info-section">
                    <h3><i class="fas fa-id-card"></i> Datos Básicos</h3>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-hashtag"></i> Nombre:</div>
                        <div class="info-value">
                            <span style="font-weight: 600; color: #1a5330; font-size: 18px;">
                                <?php echo htmlspecialchars($grupo['nombre']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-layer-group"></i> Semestre:</div>
                        <div class="info-value">
                            <span class="badge badge-primary"><?php echo $grupo['semestre']; ?>° Semestre</span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-clock"></i> Turno:</div>
                        <div class="info-value">
                            <span class="badge badge-success"><?php echo htmlspecialchars($grupo['turno']); ?></span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-chalkboard"></i> Salón:</div>
                        <div class="info-value">
                            <?php if ($grupo['salon']): ?>
                                <span class="badge badge-info"><?php echo htmlspecialchars($grupo['salon']); ?></span>
                            <?php else: ?>
                                <span style="color: #8bc34a;">No asignado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-circle"></i> Estatus:</div>
                        <div class="info-value">
                            <span class="estatus-badge <?php echo $grupo['activo'] ? 'estatus-activo' : 'estatus-inactivo'; ?>">
                                <i class="fas fa-<?php echo $grupo['activo'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?php echo $grupo['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-calendar-alt"></i> Información Académica</h3>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-calendar"></i> Período:</div>
                        <div class="info-value">
                            <?php echo $grupo['periodo_escolar'] ? htmlspecialchars($grupo['periodo_escolar']) : '<span style="color: #8bc34a;">No definido</span>'; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-user-tie"></i> Tutor:</div>
                        <div class="info-value">
                            <?php echo $grupo['tutor'] ? htmlspecialchars($grupo['tutor']) : '<span style="color: #8bc34a;">No asignado</span>'; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-envelope"></i> Correo Tutor:</div>
                        <div class="info-value">
                            <?php echo $grupo['correo_tutor'] ? htmlspecialchars($grupo['correo_tutor']) : '<span style="color: #8bc34a;">No asignado</span>'; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-users"></i> Capacidad:</div>
                        <div class="info-value">
                            <?php 
                            $capacidad_actual = $grupo['alumnos_activos'] ?? 0;
                            $capacidad_maxima = $grupo['capacidad_maxima'] ?? 40;
                            $porcentaje = ($capacidad_maxima > 0) ? round(($capacidad_actual / $capacidad_maxima) * 100) : 0;
                            ?>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span><?php echo $capacidad_actual . ' / ' . $capacidad_maxima; ?></span>
                                <div style="flex: 1; background: #c8e6c9; height: 8px; border-radius: 4px;">
                                    <div style="width: <?php echo min($porcentaje, 100); ?>%; height: 100%; background: <?php echo $porcentaje >= 90 ? '#dc3545' : ($porcentaje >= 75 ? '#ffc107' : '#4caf50'); ?>; border-radius: 4px;"></div>
                                </div>
                                <span style="font-weight: 700; color: #1a5330;"><?php echo $porcentaje; ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-history"></i> Fechas</h3>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-calendar-plus"></i> Creado:</div>
                        <div class="info-value">
                            <?php echo date('d/m/Y H:i', strtotime($grupo['created_at'])); ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-calendar-check"></i> Actualizado:</div>
                        <div class="info-value">
                            <?php echo date('d/m/Y H:i', strtotime($grupo['updated_at'])); ?>
                        </div>
                    </div>
                    <?php if (!empty($grupo['descripcion'])): ?>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-align-left"></i> Descripción:</div>
                        <div class="info-value">
                            <?php echo nl2br(htmlspecialchars($grupo['descripcion'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Horarios del Grupo -->
        <div class="card">
            <h2><i class="fas fa-clock"></i> Horario del Grupo</h2>
            
            <?php if (count($horarios) > 0): ?>
                <div class="horario-grid">
                    <?php 
                    $dias_orden = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                    foreach ($dias_orden as $dia): 
                        if (isset($horarios_por_dia[$dia])): 
                    ?>
                    <div class="dia-card">
                        <h4><?php echo $dia; ?></h4>
                        <?php foreach ($horarios_por_dia[$dia] as $clase): ?>
                        <div class="clase-item">
                            <div class="clase-hora">
                                <?php echo date('H:i', strtotime($clase['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($clase['hora_fin'])); ?>
                            </div>
                            <div class="clase-materia">
                                <?php echo htmlspecialchars($clase['materia']); ?>
                            </div>
                            <div class="clase-detalles">
                                <?php if ($clase['maestro_nombre']): ?>
                                <div><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($clase['maestro_nombre'] . ' ' . $clase['maestro_apellido']); ?></div>
                                <?php endif; ?>
                                <?php if ($clase['aula_nombre']): ?>
                                <div><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($clase['aula_nombre']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clock"></i>
                    <h3>No hay horarios asignados</h3>
                    <p>Este grupo no tiene horarios registrados. Puedes asignar horarios desde la sección de horarios.</p>
                    <a href="horarios_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-success" style="margin-top: 20px;">
                        <i class="fas fa-plus-circle"></i> Asignar Horarios
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Alumnos del Grupo -->
        <div class="card">
            <h2><i class="fas fa-users"></i> Alumnos del Grupo</h2>
            
            <?php if (count($alumnos) > 0): ?>
                <div class="resultados-info" style="background: #c8e6c9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <p><i class="fas fa-info-circle"></i> 
                        Mostrando <?php echo count($alumnos); ?> alumnos activos en el grupo
                    </p>
                </div>
                
                <table class="tabla">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Alumno</th>
                            <th><i class="fas fa-id-card"></i> Matrícula</th>
                            <th><i class="fas fa-venus-mars"></i> Género</th>
                            <th><i class="fas fa-calendar"></i> Edad</th>
                            <th><i class="fas fa-phone"></i> Contacto</th>
                            <th><i class="fas fa-cogs"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos as $alumno): 
                            $edad = date_diff(date_create($alumno['fecha_nacimiento']), date_create('today'))->y;
                        ?>
                        <tr>
                            <td>
                                <div class="alumno-info">
                                    <div class="alumno-avatar">
                                        <?php echo strtoupper(substr($alumno['nombre'], 0, 1) . substr($alumno['apellido_paterno'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="alumno-nombre">
                                            <?php echo htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'] . ' ' . $alumno['nombre']); ?>
                                        </div>
                                        <?php if ($alumno['correo_institucional']): ?>
                                        <div style="font-size: 12px; color: #4caf50;">
                                            <?php echo htmlspecialchars($alumno['correo_institucional']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?php echo htmlspecialchars($alumno['matricula']); ?></span>
                            </td>
                            <td>
                                <?php 
                                $genero_icon = '';
                                switch ($alumno['id_genero']) {
                                    case 1: $genero_icon = 'mars'; $genero_text = 'Masculino'; break;
                                    case 2: $genero_icon = 'venus'; $genero_text = 'Femenino'; break;
                                    case 3: $genero_icon = 'transgender'; $genero_text = 'No binario'; break;
                                    default: $genero_icon = 'question'; $genero_text = 'Otro';
                                }
                                ?>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-<?php echo $genero_icon; ?>" style="color: #4caf50;"></i>
                                    <span><?php echo $genero_text; ?></span>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #1a5330;"><?php echo $edad; ?> años</span>
                                <div style="font-size: 12px; color: #2e7d32;">
                                    <?php echo date('d/m/Y', strtotime($alumno['fecha_nacimiento'])); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($alumno['telefono_celular']): ?>
                                <div style="font-size: 13px;">
                                    <i class="fas fa-mobile-alt" style="color: #4caf50;"></i>
                                    <?php echo htmlspecialchars($alumno['telefono_celular']); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="ver_alumno.php?id=<?php echo $alumno['id_alumno']; ?>" 
                                       class="btn btn-info btn-small" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editar_alumno.php?id=<?php echo $alumno['id_alumno']; ?>" 
                                       class="btn btn-success btn-small" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No hay alumnos en el grupo</h3>
                    <p>Este grupo no tiene alumnos asignados. Puedes agregar alumnos desde la gestión de alumnos.</p>
                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                        <a href="alumnos_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Agregar Alumnos
                        </a>
                        <a href="gestion_alumnos.php" class="btn btn-primary">
                            <i class="fas fa-users"></i> Gestionar Alumnos
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Botones de Acción -->
        <div class="action-buttons">
            <a href="grupos_carrera.php?id=<?php echo $grupo['id_carrera']; ?>" class="btn btn-volver">
                <i class="fas fa-arrow-left"></i> Volver a Grupos
            </a>
            <a href="editar_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-success">
                <i class="fas fa-edit"></i> Editar Grupo
            </a>
            <a href="alumnos_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-primary">
                <i class="fas fa-users"></i> Gestionar Alumnos
            </a>
            <a href="horarios_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-warning">
                <i class="fas fa-clock"></i> Gestionar Horarios
            </a>
            <a href="asistencias_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-info">
                <i class="fas fa-clipboard-check"></i> Asistencias
            </a>
            <a href="reporte_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-secondary">
                <i class="fas fa-chart-pie"></i> Generar Reporte
            </a>
            <?php if ($grupo['activo']): ?>
                <a href="desactivar_grupo.php?id=<?php echo $id_grupo; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('¿Está seguro de desactivar este grupo?')">
                    <i class="fas fa-ban"></i> Desactivar Grupo
                </a>
            <?php else: ?>
                <a href="activar_grupo.php?id=<?php echo $id_grupo; ?>" 
                   class="btn btn-success"
                   onclick="return confirm('¿Está seguro de activar este grupo?')">
                    <i class="fas fa-check"></i> Activar Grupo
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Funcionalidad para mostrar/ocultar detalles
        document.querySelectorAll('.clase-item').forEach(item => {
            item.addEventListener('click', function() {
                this.classList.toggle('expanded');
            });
        });
    </script>
</body>
</html>