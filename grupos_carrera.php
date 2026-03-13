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

// Obtener información de la carrera
$sql_carrera = "SELECT * FROM carreras WHERE id_carrera = :id_carrera";
$stmt_carrera = $con->prepare($sql_carrera);
$stmt_carrera->bindValue(':id_carrera', $id_carrera, PDO::PARAM_INT);
$stmt_carrera->execute();
$carrera = $stmt_carrera->fetch(PDO::FETCH_ASSOC);

if (!$carrera) {
    header('Location: gestion_carreras.php');
    exit();
}

// Paginación para grupos
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filtros para grupos
$filtro_semestre = $_GET['semestre'] ?? '';
$filtro_turno = $_GET['turno'] ?? '';
$filtro_activo = $_GET['activo'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Obtener grupos de la carrera con filtros
$sql_grupos = "SELECT g.*, 
                      COUNT(DISTINCT a.id_alumno) as total_alumnos,
                      (SELECT COUNT(DISTINCT hm.id_maestro) 
                       FROM horarios_maestros hm 
                       WHERE hm.id_grupo = g.id_grupo) as total_maestros,
                      (SELECT GROUP_CONCAT(DISTINCT m.materia SEPARATOR ', ') 
                       FROM horarios_maestros hm 
                       JOIN materias m ON hm.id_materia = m.id_materia 
                       WHERE hm.id_grupo = g.id_grupo 
                       LIMIT 3) as materias_destacadas
               FROM grupos g 
               LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo AND a.activo = 'Activo'
               WHERE g.id_carrera = :id_carrera";

$params = ['id_carrera' => $id_carrera];

if ($filtro_semestre !== '') {
    $sql_grupos .= " AND g.semestre = :semestre";
    $params['semestre'] = $filtro_semestre;
}

if ($filtro_turno) {
    $sql_grupos .= " AND g.turno = :turno";
    $params['turno'] = $filtro_turno;
}

if ($filtro_activo !== '') {
    $sql_grupos .= " AND g.activo = :activo";
    $params['activo'] = $filtro_activo;
}

if ($filtro_busqueda) {
    $sql_grupos .= " AND (g.nombre LIKE :busqueda OR 
                          g.salon LIKE :busqueda OR 
                          g.tutor LIKE :busqueda)";
    $params['busqueda'] = "%$filtro_busqueda%";
}

$sql_grupos .= " GROUP BY g.id_grupo 
                 ORDER BY g.semestre, g.nombre 
                 LIMIT :limit OFFSET :offset";

$stmt_grupos = $con->prepare($sql_grupos);
foreach ($params as $key => $value) {
    $stmt_grupos->bindValue(':' . $key, $value);
}
$stmt_grupos->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_grupos->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_grupos->execute();
$grupos = $stmt_grupos->fetchAll(PDO::FETCH_ASSOC);

// Obtener total para paginación
$sql_total = "SELECT COUNT(*) as total FROM grupos WHERE id_carrera = " . $id_carrera;
if ($filtro_semestre !== '') $sql_total .= " AND semestre = " . (int)$filtro_semestre;
if ($filtro_turno) $sql_total .= " AND turno = '" . $con->quote($filtro_turno) . "'";
if ($filtro_activo !== '') $sql_total .= " AND activo = " . (int)$filtro_activo;
if ($filtro_busqueda) $sql_total .= " AND (nombre LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                             salon LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                             tutor LIKE '%" . $con->quote($filtro_busqueda) . "%')";

$total_grupos = $con->query($sql_total)->fetchColumn();
$total_paginas = ceil($total_grupos / $limit);

// Obtener opciones para filtros
$semestres = $con->query("SELECT DISTINCT semestre FROM grupos WHERE id_carrera = $id_carrera AND semestre IS NOT NULL ORDER BY semestre")->fetchAll();
$turnos = $con->query("SELECT DISTINCT turno FROM grupos WHERE id_carrera = $id_carrera AND turno IS NOT NULL AND turno != '' ORDER BY turno")->fetchAll();

// Obtener estadísticas de la carrera
$sql_stats = "SELECT 
                COUNT(DISTINCT g.id_grupo) as total_grupos,
                COUNT(DISTINCT a.id_alumno) as total_alumnos,
                COUNT(DISTINCT CASE WHEN a.activo = 'Activo' THEN a.id_alumno END) as alumnos_activos,
                (SELECT COUNT(DISTINCT hm.id_maestro) 
                 FROM horarios_maestros hm 
                 JOIN grupos g2 ON hm.id_grupo = g2.id_grupo 
                 WHERE g2.id_carrera = $id_carrera) as total_maestros
              FROM carreras c 
              LEFT JOIN grupos g ON c.id_carrera = g.id_carrera 
              LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo 
              WHERE c.id_carrera = $id_carrera";
$stats = $con->query($sql_stats)->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupos de <?php echo htmlspecialchars($carrera['nombre']); ?> - CECYTE</title>
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1a5330;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #8bc34a;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            background-color: #f9fff9;
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: #2e7d32;
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
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

        .btn-purple {
            background: #4caf50;
            color: white;
            border: 1px solid #2e7d32;
        }

        .btn-purple:hover {
            background: #2e7d32;
            transform: translateY(-3px);
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 14px;
        }

        .btn-action-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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
            padding: 18px 15px;
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

        .estatus-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            min-width: 80px;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .estatus-activo {
            background: #c8e6c9;
            color: #1a5330;
            border-color: #4caf50;
        }

        .estatus-inactivo {
            background: #ffebee;
            color: #c62828;
            border-color: #ef5350;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin: 2px;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .badge-info {
            background: #c8e6c9;
            color: #1a5330;
            border-color: #4caf50;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }

        .badge-success {
            background: #c8e6c9;
            color: #1a5330;
            border-color: #4caf50;
        }

        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
            border-color: #d6d8db;
        }

        .badge-semestre {
            background: #1a5330;
            color: white;
            border-color: #2e7d32;
            font-weight: 700;
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

        .paginacion {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .paginacion .btn {
            min-width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .paginacion .btn.active {
            background: #2e7d32;
            color: white;
            font-weight: bold;
            border-color: #1a5330;
        }

        .resultados-info {
            background: #c8e6c9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4caf50;
        }

        .resultados-info p {
            margin: 0;
            color: #1a5330;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .acciones-superiores {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .busqueda-rapida {
            display: flex;
            gap: 10px;
            flex: 1;
            max-width: 400px;
        }

        .busqueda-rapida input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #8bc34a;
            border-radius: 8px;
            font-size: 15px;
            background-color: #f9fff9;
        }

        .busqueda-rapida input:focus {
            border-color: #2e7d32;
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
        }

        .descripcion-corta {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #2e7d32;
        }

        .carrera-info {
            background: #f9fff9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #c8e6c9;
            border-left: 5px solid #2e7d32;
        }

        .carrera-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .carrera-header h2 {
            margin: 0;
            color: #1a5330;
            font-size: 24px;
        }

        .carrera-clave {
            background: #1a5330;
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .carrera-detalles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .detalle-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detalle-item i {
            color: #4caf50;
            font-size: 18px;
        }

        .detalle-item label {
            font-weight: 600;
            color: #1a5330;
            min-width: 120px;
        }

        .detalle-item span {
            color: #2e7d32;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #1a5330;
            background-color: #c8e6c9;
            border-radius: 10px;
            border: 2px dashed #8bc34a;
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #4caf50;
        }

        .empty-state h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: #1a5330;
        }

        .empty-state p {
            font-size: 16px;
            max-width: 500px;
            margin: 0 auto;
            color: #2e7d32;
        }

        .turno-badge {
            background: #c8e6c9;
            color: #1a5330;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #4caf50;
        }

        .materias-list {
            font-size: 12px;
            color: #2e7d32;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .acciones-superiores {
                flex-direction: column;
                align-items: stretch;
            }
            
            .busqueda-rapida {
                max-width: 100%;
            }
            
            .carrera-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .carrera-detalles {
                grid-template-columns: 1fr;
            }
            
            .stats-card {
                grid-template-columns: 1fr;
            }
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-users"></i> Grupos de <?php echo htmlspecialchars($carrera['nombre']); ?></h1>
            <div class="nav-links">
                <a href="main.php"><i class="fas fa-home"></i> Panel Principal</a>
                <a href="gestion_carreras.php"><i class="fas fa-graduation-cap"></i> Carreras</a>
                <a href="gestion_alumnos.php"><i class="fas fa-users"></i> Alumnos</a>
                <a href="gestion_maestros.php"><i class="fas fa-chalkboard-teacher"></i> Maestros</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesi&oacute;n</a>
            </div>
        </div>
        
        <!-- Información de la Carrera -->
        <div class="carrera-info">
            <div class="carrera-header">
                <h2><?php echo htmlspecialchars($carrera['nombre']); ?></h2>
                <span class="carrera-clave"><?php echo htmlspecialchars($carrera['clave']); ?></span>
            </div>
            
            <div class="carrera-detalles">
                <div class="detalle-item">
                    <i class="fas fa-university"></i>
                    <label>Modalidad:</label>
                    <span><?php echo htmlspecialchars($carrera['modalidad'] ?? 'Escolarizada'); ?></span>
                </div>
                <div class="detalle-item">
                    <i class="fas fa-clock"></i>
                    <label>Duraci&oacute;n:</label>
                    <span><?php echo ($carrera['duracion_semestres'] ?? 6) . ' semestres'; ?></span>
                </div>
                <div class="detalle-item">
                    <i class="fas fa-align-left"></i>
                    <label>Descripci&oacute;n:</label>
                    <span><?php echo htmlspecialchars(substr($carrera['descripcion'] ?? 'Sin descripción', 0, 100)); ?>
                    <?php if (strlen($carrera['descripcion'] ?? '') > 100): ?>...<?php endif; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas de la Carrera -->
        <div class="card">
            <h2><i class="fas fa-chart-line"></i> Estadísticas de la Carrera</h2>
            <div class="stats-card">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_grupos'] ?? 0; ?></div>
                    <div class="stat-label">Total de Grupos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_alumnos'] ?? 0; ?></div>
                    <div class="stat-label">Alumnos Totales</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['alumnos_activos'] ?? 0; ?></div>
                    <div class="stat-label">Alumnos Activos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_maestros'] ?? 0; ?></div>
                    <div class="stat-label">Maestros Asignados</div>
                </div>
            </div>
        </div>
        
        <!-- Tarjeta de Filtros para Grupos -->
        <div class="card">
            <h2><i class="fas fa-filter"></i> Filtros de Grupos</h2>
            
            <!-- Búsqueda Rápida -->
            <div class="acciones-superiores">
                <div class="busqueda-rapida">
                    <input type="text" 
                           name="busqueda" 
                           placeholder="Buscar por nombre, sal&oacute;n o tutor..." 
                           value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                    <button type="submit" class="btn btn-primary" id="btnBuscar">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <a href="nuevo_grupo.php?carrera=<?php echo $id_carrera; ?>" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Nuevo Grupo
                </a>
            </div>
            
            <!-- Formulario de Filtros -->
            <form method="GET" class="form-grid">
                <input type="hidden" name="id" value="<?php echo $id_carrera; ?>">
                
                <div class="form-group">
                    <label><i class="fas fa-layer-group"></i> Semestre:</label>
                    <select name="semestre">
                        <option value="">Todos los Semestres</option>
                        <?php foreach ($semestres as $semestre): ?>
                        <option value="<?php echo $semestre['semestre']; ?>"
                                <?php echo $filtro_semestre == $semestre['semestre'] ? 'selected' : ''; ?>>
                            <?php echo $semestre['semestre']; ?>° Semestre
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Turno:</label>
                    <select name="turno">
                        <option value="">Todos los Turnos</option>
                        <?php foreach ($turnos as $turno): ?>
                        <option value="<?php echo htmlspecialchars($turno['turno']); ?>"
                                <?php echo $filtro_turno == $turno['turno'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($turno['turno']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-circle"></i> Estatus:</label>
                    <select name="activo">
                        <option value="">Todos los Estatus</option>
                        <option value="1" <?php echo $filtro_activo === '1' ? 'selected' : ''; ?>>Activo</option>
                        <option value="0" <?php echo $filtro_activo === '0' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Aplicar Filtros
                    </button>
                    <a href="grupos_carrera.php?id=<?php echo $id_carrera; ?>" class="btn btn-secondary">
                        <i class="fas fa-broom"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Tarjeta de Lista de Grupos -->
        <div class="card">
            <h2><i class="fas fa-list"></i> Lista de Grupos</h2>
            
            <?php if (count($grupos) > 0): ?>
                <div class="resultados-info">
                    <p><i class="fas fa-info-circle"></i> 
                        Mostrando <?php echo count($grupos); ?> de <?php echo $total_grupos; ?> grupos
                        <?php if ($filtro_busqueda): ?>
                            - Resultados para: "<?php echo htmlspecialchars($filtro_busqueda); ?>"
                        <?php endif; ?>
                    </p>
                </div>
                
                <table class="tabla">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Grupo</th>
                            <th><i class="fas fa-layer-group"></i> Semestre</th>
                            <th><i class="fas fa-clock"></i> Turno</th>
                            <th><i class="fas fa-chalkboard"></i> Sal&oacute;n</th>
                            <th><i class="fas fa-chart-bar"></i> Estad&iacute;sticas</th>
                            <th><i class="fas fa-book"></i> Materias</th>
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
                                <span class="badge badge-semestre"><?php echo $grupo['semestre']; ?>° Semestre</span>
                            </td>
                            <td>
                                <span class="turno-badge"><?php echo htmlspecialchars($grupo['turno']); ?></span>
                            </td>
                            <td>
                                <?php if ($grupo['salon']): ?>
                                <span class="badge badge-info"><?php echo htmlspecialchars($grupo['salon']); ?></span>
                                <?php else: ?>
                                <span style="color: #8bc34a;">No asignado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 15px;">
                                    <div title="Alumnos">
                                        <div style="font-weight: 700; color: #2e7d32;"><?php echo $grupo['total_alumnos'] ?? 0; ?></div>
                                        <div style="font-size: 11px; color: #1a5330;">Alumnos</div>
                                    </div>
                                    <div title="Maestros">
                                        <div style="font-weight: 700; color: #4caf50;"><?php echo $grupo['total_maestros'] ?? 0; ?></div>
                                        <div style="font-size: 11px; color: #1a5330;">Maestros</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="materias-list" title="<?php echo htmlspecialchars($grupo['materias_destacadas'] ?? 'Sin materias asignadas'); ?>">
                                    <?php if ($grupo['materias_destacadas']): ?>
                                        <?php echo htmlspecialchars($grupo['materias_destacadas']); ?>
                                    <?php else: ?>
                                        <span style="color: #8bc34a;">Sin materias</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="estatus-badge <?php echo $grupo['activo'] ? 'estatus-activo' : 'estatus-inactivo'; ?>">
                                    <i class="fas fa-<?php echo $grupo['activo'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                    <?php echo $grupo['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-action-group">
                                    <a href="ver_grupo_2.php?id=<?php echo $grupo['id_grupo']; ?>" 
                                       class="btn btn-info btn-small" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editar_grupo_2.php?id=<?php echo $grupo['id_grupo']; ?>" 
                                       class="btn btn-success btn-small" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="alumnos_grupo.php?id=<?php echo $grupo['id_grupo']; ?>" 
                                       class="btn btn-primary btn-small" title="Alumnos">
                                        <i class="fas fa-users"></i>
                                    </a>
                                    <a href="horarios_grupo.php?id=<?php echo $grupo['id_grupo']; ?>" 
                                       class="btn btn-warning btn-small" title="Horarios">
                                        <i class="fas fa-clock"></i>
                                    </a>
                                    <a href="reporte_grupo.php?id=<?php echo $grupo['id_grupo']; ?>" 
                                       class="btn btn-secondary btn-small" title="Reportes">
                                        <i class="fas fa-chart-pie"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                <div class="paginacion">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?php echo $id_carrera; ?>&page=<?php echo $page-1; ?>&semestre=<?php echo $filtro_semestre; ?>&turno=<?php echo urlencode($filtro_turno); ?>&activo=<?php echo $filtro_activo; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>"
                           class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_paginas, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                        <a href="?id=<?php echo $id_carrera; ?>&page=<?php echo $i; ?>&semestre=<?php echo $filtro_semestre; ?>&turno=<?php echo urlencode($filtro_turno); ?>&activo=<?php echo $filtro_activo; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>"
                           class="btn <?php echo $i == $page ? 'btn-primary active' : 'btn-secondary'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_paginas): ?>
                        <a href="?id=<?php echo $id_carrera; ?>&page=<?php echo $page+1; ?>&semestre=<?php echo $filtro_semestre; ?>&turno=<?php echo urlencode($filtro_turno); ?>&activo=<?php echo $filtro_activo; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>"
                           class="btn btn-secondary">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No se encontraron grupos</h3>
                    <p>No hay grupos registrados para esta carrera con los criterios de b&uacute;squeda seleccionados.</p>
                    <div class="action-buttons" style="justify-content: center;">
                        <a href="nuevo_grupo.php?carrera=<?php echo $id_carrera; ?>" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Crear Primer Grupo
                        </a>
                        <a href="grupos_carrera.php?id=<?php echo $id_carrera; ?>" class="btn btn-primary">
                            <i class="fas fa-redo"></i> Ver todos los grupos
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Botones de Acción -->
        <div class="action-buttons">
            <a href="gestion_carreras.php" class="btn btn-volver">
                <i class="fas fa-arrow-left"></i> Volver a Carreras
            </a>
            <a href="ver_carrera.php?id=<?php echo $id_carrera; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> Ver Detalles de Carrera
            </a>
            <a href="editar_carrera.php?id=<?php echo $id_carrera; ?>" class="btn btn-success">
                <i class="fas fa-edit"></i> Editar Carrera
            </a>
            <a href="plan_estudios.php?id=<?php echo $id_carrera; ?>" class="btn btn-warning">
                <i class="fas fa-book"></i> Plan de Estudios
            </a>
            <a href="reporte_carrera.php?id=<?php echo $id_carrera; ?>" class="btn btn-secondary">
                <i class="fas fa-chart-pie"></i> Reportes
            </a>
        </div>
    </div>
    
    <script>
        // Agregar funcionalidad a la búsqueda rápida
        document.getElementById('btnBuscar').addEventListener('click', function() {
            const busqueda = document.querySelector('.busqueda-rapida input').value;
            const params = new URLSearchParams(window.location.search);
            params.set('busqueda', busqueda);
            window.location.search = params.toString();
        });
        
        // Permitir búsqueda con Enter
        document.querySelector('.busqueda-rapida input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('btnBuscar').click();
            }
        });
        
        // Mejorar la experiencia de los selects
        document.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>