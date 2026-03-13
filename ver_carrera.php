<?php
session_start();

// Verificar permisos
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Verificar que se proporcionó un ID de carrera
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_carreras.php');
    exit();
}

$id_carrera = (int)$_GET['id'];

// Obtener información completa de la carrera
$sql_carrera = "SELECT c.*,
                       COUNT(DISTINCT g.id_grupo) as total_grupos,
                       COUNT(DISTINCT a.id_alumno) as total_alumnos,
                       COUNT(DISTINCT CASE WHEN a.activo = 'Activo' THEN a.id_alumno END) as alumnos_activos,
                       (SELECT COUNT(DISTINCT hm.id_maestro) 
                        FROM horarios_maestros hm 
                        JOIN grupos g2 ON hm.id_grupo = g2.id_grupo 
                        WHERE g2.id_carrera = c.id_carrera) as total_maestros
                FROM carreras c 
                LEFT JOIN grupos g ON c.id_carrera = g.id_carrera 
                LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo 
                WHERE c.id_carrera = :id_carrera";
$stmt_carrera = $con->prepare($sql_carrera);
$stmt_carrera->bindValue(':id_carrera', $id_carrera, PDO::PARAM_INT);
$stmt_carrera->execute();
$carrera = $stmt_carrera->fetch(PDO::FETCH_ASSOC);

if (!$carrera) {
    header('Location: gestion_carreras.php');
    exit();
}

// Obtener grupos de la carrera
$sql_grupos = "SELECT g.*, 
                      COUNT(DISTINCT a.id_alumno) as total_alumnos
               FROM grupos g 
               LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo AND a.activo = 'Activo'
               WHERE g.id_carrera = :id_carrera
               GROUP BY g.id_grupo 
               ORDER BY g.semestre, g.nombre";
$stmt_grupos = $con->prepare($sql_grupos);
$stmt_grupos->bindValue(':id_carrera', $id_carrera, PDO::PARAM_INT);
$stmt_grupos->execute();
$grupos = $stmt_grupos->fetchAll(PDO::FETCH_ASSOC);

// Obtener distribución por semestre
$sql_semestres = "SELECT semestre, 
                         COUNT(*) as total_grupos,
                         SUM(total_alumnos) as total_alumnos
                  FROM (
                    SELECT g.semestre, 
                           COUNT(DISTINCT a.id_alumno) as total_alumnos
                    FROM grupos g 
                    LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo AND a.activo = 'Activo'
                    WHERE g.id_carrera = :id_carrera
                    GROUP BY g.id_grupo
                  ) as subquery
                  GROUP BY semestre
                  ORDER BY semestre";
$stmt_semestres = $con->prepare($sql_semestres);
$stmt_semestres->bindValue(':id_carrera', $id_carrera, PDO::PARAM_INT);
$stmt_semestres->execute();
$distribucion_semestres = $stmt_semestres->fetchAll(PDO::FETCH_ASSOC);

// Obtener distribución por turno
$sql_turnos = "SELECT turno, 
                      COUNT(*) as total_grupos,
                      SUM(total_alumnos) as total_alumnos
               FROM (
                 SELECT g.turno, 
                        COUNT(DISTINCT a.id_alumno) as total_alumnos
                 FROM grupos g 
                 LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo AND a.activo = 'Activo'
                 WHERE g.id_carrera = :id_carrera
                 GROUP BY g.id_grupo
               ) as subquery
               GROUP BY turno
               ORDER BY turno";
$stmt_turnos = $con->prepare($sql_turnos);
$stmt_turnos->bindValue(':id_carrera', $id_carrera, PDO::PARAM_INT);
$stmt_turnos->execute();
$distribucion_turnos = $stmt_turnos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($carrera['nombre']); ?> - Detalles - CECYTE</title>
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

        .btn-danger {
            background: #dc3545;
            color: white;
            border: 1px solid #c82333;
        }

        .btn-danger:hover {
            background: #c82333;
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

        .distribucion-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .distribucion-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #c8e6c9;
            border-top: 4px solid #4caf50;
        }

        .distribucion-card h4 {
            color: #1a5330;
            margin-bottom: 15px;
            font-size: 16px;
            text-align: center;
        }

        .distribucion-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #c8e6c9;
        }

        .distribucion-item:last-child {
            border-bottom: none;
        }

        .distribucion-label {
            color: #2e7d32;
            font-weight: 600;
        }

        .distribucion-value {
            color: #1a5330;
            font-weight: 700;
        }

        .descripcion-box {
            background: #f9fff9;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #c8e6c9;
            margin-bottom: 30px;
            line-height: 1.6;
            color: #2e7d32;
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
            
            .distribucion-grid {
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
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-graduation-cap"></i> Detalles de Carrera</h1>
            <div class="nav-links">
                <a href="main.php"><i class="fas fa-home"></i> Panel Principal</a>
                <a href="gestion_carreras.php"><i class="fas fa-graduation-cap"></i> Carreras</a>
                <a href="gestion_alumnos.php"><i class="fas fa-users"></i> Alumnos</a>
                <a href="gestion_maestros.php"><i class="fas fa-chalkboard-teacher"></i> Maestros</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesi&oacute;n</a>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="card">
            <h2><i class="fas fa-chart-line"></i> Estadísticas de la Carrera</h2>
            <div class="stats-card">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $carrera['total_grupos'] ?? 0; ?></div>
                    <div class="stat-label">Grupos Totales</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $carrera['total_alumnos'] ?? 0; ?></div>
                    <div class="stat-label">Alumnos Totales</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $carrera['alumnos_activos'] ?? 0; ?></div>
                    <div class="stat-label">Alumnos Activos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $carrera['total_maestros'] ?? 0; ?></div>
                    <div class="stat-label">Maestros Asignados</div>
                </div>
            </div>
        </div>
        
        <!-- Información Principal -->
        <div class="card">
            <h2><i class="fas fa-info-circle"></i> Información de la Carrera</h2>
            
            <div class="info-grid">
                <div class="info-section">
                    <h3><i class="fas fa-id-card"></i> Datos Básicos</h3>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-hashtag"></i> Clave:</div>
                        <div class="info-value">
                            <span class="badge badge-primary"><?php echo htmlspecialchars($carrera['clave']); ?></span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-graduation-cap"></i> Nombre:</div>
                        <div class="info-value" style="font-weight: 600; color: #1a5330;">
                            <?php echo htmlspecialchars($carrera['nombre']); ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-university"></i> Modalidad:</div>
                        <div class="info-value">
                            <span class="badge badge-success"><?php echo htmlspecialchars($carrera['modalidad'] ?? 'Escolarizada'); ?></span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-clock"></i> Duración:</div>
                        <div class="info-value">
                            <?php echo ($carrera['duracion_semestres'] ?? 6) . ' semestres'; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-circle"></i> Estatus:</div>
                        <div class="info-value">
                            <span class="estatus-badge <?php echo $carrera['activo'] ? 'estatus-activo' : 'estatus-inactivo'; ?>">
                                <i class="fas fa-<?php echo $carrera['activo'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?php echo $carrera['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-calendar-alt"></i> Fechas</h3>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-calendar-plus"></i> Creada:</div>
                        <div class="info-value">
                            <?php echo date('d/m/Y H:i', strtotime($carrera['created_at'])); ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-calendar-check"></i> Actualizada:</div>
                        <div class="info-value">
                            <?php echo date('d/m/Y H:i', strtotime($carrera['updated_at'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-user-tie"></i> Departamento</h3>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-user"></i> Jefe:</div>
                        <div class="info-value">
                            <?php echo $carrera['jefe_departamento'] ? htmlspecialchars($carrera['jefe_departamento']) : '<span style="color: #8bc34a;">No asignado</span>'; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-envelope"></i> Correo:</div>
                        <div class="info-value">
                            <?php echo $carrera['correo_departamento'] ? htmlspecialchars($carrera['correo_departamento']) : '<span style="color: #8bc34a;">No asignado</span>'; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-phone"></i> Teléfono:</div>
                        <div class="info-value">
                            <?php echo $carrera['telefono_departamento'] ? htmlspecialchars($carrera['telefono_departamento']) : '<span style="color: #8bc34a;">No asignado</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Descripción -->
            <?php if (!empty($carrera['descripcion'])): ?>
            <div class="descripcion-box">
                <h3 style="color: #1a5330; margin-bottom: 15px; font-size: 18px;">
                    <i class="fas fa-align-left"></i> Descripción
                </h3>
                <p><?php echo nl2br(htmlspecialchars($carrera['descripcion'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Distribuciones -->
            <div class="distribucion-grid">
                <?php if (count($distribucion_semestres) > 0): ?>
                <div class="distribucion-card">
                    <h4><i class="fas fa-layer-group"></i> Distribución por Semestre</h4>
                    <?php foreach ($distribucion_semestres as $dist): ?>
                    <div class="distribucion-item">
                        <span class="distribucion-label"><?php echo $dist['semestre']; ?>° Semestre:</span>
                        <span class="distribucion-value">
                            <?php echo $dist['total_grupos']; ?> grupos (<?php echo $dist['total_alumnos'] ?? 0; ?> alumnos)
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (count($distribucion_turnos) > 0): ?>
                <div class="distribucion-card">
                    <h4><i class="fas fa-clock"></i> Distribución por Turno</h4>
                    <?php foreach ($distribucion_turnos as $dist): ?>
                    <div class="distribucion-item">
                        <span class="distribucion-label"><?php echo htmlspecialchars($dist['turno']); ?>:</span>
                        <span class="distribucion-value">
                            <?php echo $dist['total_grupos']; ?> grupos (<?php echo $dist['total_alumnos'] ?? 0; ?> alumnos)
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Grupos de la Carrera -->
        <div class="card">
            <h2><i class="fas fa-users"></i> Grupos de esta Carrera</h2>
            
            <?php if (count($grupos) > 0): ?>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Grupo</th>
                            <th><i class="fas fa-layer-group"></i> Semestre</th>
                            <th><i class="fas fa-clock"></i> Turno</th>
                            <th><i class="fas fa-chalkboard"></i> Salón</th>
                            <th><i class="fas fa-users"></i> Alumnos</th>
                            <th><i class="fas fa-circle"></i> Estatus</th>
                            <th><i class="fas fa-cogs"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grupos as $grupo): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($grupo['nombre']); ?></strong>
                                <?php if ($grupo['tutor']): ?>
                                <div style="font-size: 13px; color: #2e7d32;">
                                    <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($grupo['tutor']); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo $grupo['semestre']; ?>° Semestre</span>
                            </td>
                            <td>
                                <span class="badge badge-success"><?php echo htmlspecialchars($grupo['turno']); ?></span>
                            </td>
                            <td>
                                <?php if ($grupo['salon']): ?>
                                <span class="badge"><?php echo htmlspecialchars($grupo['salon']); ?></span>
                                <?php else: ?>
                                <span style="color: #8bc34a;">No asignado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #2e7d32;"><?php echo $grupo['total_alumnos'] ?? 0; ?></div>
                                <div style="font-size: 11px; color: #1a5330;">alumnos</div>
                            </td>
                            <td>
                                <span class="estatus-badge <?php echo $grupo['activo'] ? 'estatus-activo' : 'estatus-inactivo'; ?>">
                                    <i class="fas fa-<?php echo $grupo['activo'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                    <?php echo $grupo['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="ver_grupo.php?id=<?php echo $grupo['id_grupo']; ?>" 
                                       class="btn btn-info btn-small" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editar_grupo.php?id=<?php echo $grupo['id_grupo']; ?>" 
                                       class="btn btn-success btn-small" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="alumnos_grupo.php?id=<?php echo $grupo['id_grupo']; ?>" 
                                       class="btn btn-primary btn-small" title="Alumnos">
                                        <i class="fas fa-users"></i>
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
                    <h3>No hay grupos registrados</h3>
                    <p>Esta carrera no tiene grupos asignados. Puedes crear el primer grupo.</p>
                    <a href="nuevo_grupo.php?carrera=<?php echo $id_carrera; ?>" class="btn btn-success" style="margin-top: 20px;">
                        <i class="fas fa-plus-circle"></i> Crear Primer Grupo
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Botones de Acción -->
        <div class="action-buttons">
            <a href="gestion_carreras.php" class="btn btn-volver">
                <i class="fas fa-arrow-left"></i> Volver a Carreras
            </a>
            <a href="editar_carrera.php?id=<?php echo $id_carrera; ?>" class="btn btn-success">
                <i class="fas fa-edit"></i> Editar Carrera
            </a>
            <a href="grupos_carrera.php?id=<?php echo $id_carrera; ?>" class="btn btn-primary">
                <i class="fas fa-users"></i> Gestionar Grupos
            </a>
            <a href="plan_estudios.php?id=<?php echo $id_carrera; ?>" class="btn btn-warning">
                <i class="fas fa-book"></i> Plan de Estudios
            </a>
            <a href="reporte_carrera.php?id=<?php echo $id_carrera; ?>" class="btn btn-info">
                <i class="fas fa-chart-pie"></i> Generar Reporte
            </a>
            <?php if ($carrera['activo']): ?>
                <a href="desactivar_carrera.php?id=<?php echo $id_carrera; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('¿Está seguro de desactivar esta carrera?')">
                    <i class="fas fa-ban"></i> Desactivar Carrera
                </a>
            <?php else: ?>
                <a href="activar_carrera.php?id=<?php echo $id_carrera; ?>" 
                   class="btn btn-success"
                   onclick="return confirm('¿Está seguro de activar esta carrera?')">
                    <i class="fas fa-check"></i> Activar Carrera
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>