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

// Obtener información del grupo
$sql_grupo = "SELECT g.*, c.nombre as carrera_nombre, c.clave as carrera_clave 
              FROM grupos g 
              LEFT JOIN carreras c ON g.id_carrera = c.id_carrera 
              WHERE g.id_grupo = :id_grupo";
$stmt_grupo = $con->prepare($sql_grupo);
$stmt_grupo->bindValue(':id_grupo', $id_grupo, PDO::PARAM_INT);
$stmt_grupo->execute();
$grupo = $stmt_grupo->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header('Location: gestion_carreras.php');
    exit();
}

// Paginación para alumnos
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filtros para alumnos
$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_estatus = $_GET['estatus'] ?? '';
$filtro_genero = $_GET['genero'] ?? '';

// Obtener alumnos del grupo con filtros
$sql_alumnos = "SELECT a.*, 
                       g.genero as genero_nombre
                FROM alumnos a 
                LEFT JOIN generos g ON a.id_genero = g.id_genero 
                WHERE a.id_grupo = :id_grupo";

$params = ['id_grupo' => $id_grupo];

if ($filtro_busqueda) {
    $sql_alumnos .= " AND (a.nombre LIKE :busqueda OR 
                          a.apellido_paterno LIKE :busqueda OR 
                          a.apellido_materno LIKE :busqueda OR 
                          a.matricula LIKE :busqueda OR 
                          a.curp LIKE :busqueda)";
    $params['busqueda'] = "%$filtro_busqueda%";
}

if ($filtro_estatus !== '') {
    if ($filtro_estatus === 'Activo') {
        $sql_alumnos .= " AND a.activo = 'Activo'";
    } elseif ($filtro_estatus === 'Inactivo') {
        $sql_alumnos .= " AND a.activo = 'Inactivo'";
    }
}

if ($filtro_genero !== '') {
    $sql_alumnos .= " AND a.id_genero = :genero";
    $params['genero'] = $filtro_genero;
}

$sql_alumnos .= " ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre 
                 LIMIT :limit OFFSET :offset";

$stmt_alumnos = $con->prepare($sql_alumnos);
foreach ($params as $key => $value) {
    $stmt_alumnos->bindValue(':' . $key, $value);
}
$stmt_alumnos->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_alumnos->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_alumnos->execute();
$alumnos = $stmt_alumnos->fetchAll(PDO::FETCH_ASSOC);

// Obtener total para paginación
$sql_total = "SELECT COUNT(*) as total FROM alumnos WHERE id_grupo = $id_grupo";
if ($filtro_busqueda) $sql_total .= " AND (nombre LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                             apellido_paterno LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                             apellido_materno LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                             matricula LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                             curp LIKE '%" . $con->quote($filtro_busqueda) . "%')";
if ($filtro_estatus !== '') {
    if ($filtro_estatus === 'Activo') {
        $sql_total .= " AND activo = 'Activo'";
    } elseif ($filtro_estatus === 'Inactivo') {
        $sql_total .= " AND activo = 'Inactivo'";
    }
}
if ($filtro_genero !== '') $sql_total .= " AND id_genero = " . (int)$filtro_genero;

$total_alumnos = $con->query($sql_total)->fetchColumn();
$total_paginas = ceil($total_alumnos / $limit);

// Obtener opciones para filtros
$generos = $con->query("SELECT * FROM generos ORDER BY genero")->fetchAll();

// Obtener estadísticas
$sql_stats = "SELECT 
                COUNT(*) as total_alumnos,
                COUNT(CASE WHEN activo = 'Activo' THEN 1 END) as alumnos_activos,
                COUNT(CASE WHEN activo = 'Inactivo' THEN 1 END) as alumnos_inactivos,
                COUNT(CASE WHEN beca = 'SI' THEN 1 END) as alumnos_beca,
                COUNT(DISTINCT id_genero) as generos_diferentes
              FROM alumnos 
              WHERE id_grupo = $id_grupo";
$stats = $con->query($sql_stats)->fetch(PDO::FETCH_ASSOC);

// Obtener alumnos sin grupo para agregar
$sql_sin_grupo = "SELECT a.* 
                  FROM alumnos a 
                  WHERE (a.id_grupo IS NULL OR a.id_grupo = 0 OR a.id_grupo = '')
                  AND a.activo = 'Activo'
                  ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre 
                  LIMIT 50";
$alumnos_sin_grupo = $con->query($sql_sin_grupo)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumnos de <?php echo htmlspecialchars($grupo['nombre']); ?> - CECYTE</title>
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

        .badge-beca {
            background: #20c997;
            color: white;
            border-color: #1ba87e;
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
            
            .action-buttons {
                justify-content: center;
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

        .alumnos-disponibles {
            background: #f9fff9;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            border: 1px solid #c8e6c9;
        }

        .alumnos-disponibles h3 {
            color: #1a5330;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alumnos-disponibles-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            padding: 10px;
        }

        .alumno-disponible-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #c8e6c9;
        }

        .alumno-disponible-item:last-child {
            border-bottom: none;
        }

        .alumno-disponible-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .select-alumno {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .checkbox-alumno {
            width: 20px;
            height: 20px;
            accent-color: #4caf50;
        }

        .btn-agregar-seleccionados {
            margin-top: 15px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-users"></i> Alumnos del Grupo</h1>
            <div class="nav-links">
                <a href="main.php"><i class="fas fa-home"></i> Panel Principal</a>
                <a href="ver_grupo.php?id=<?php echo $id_grupo; ?>"><i class="fas fa-eye"></i> Ver Grupo</a>
                <a href="grupos_carrera.php?id=<?php echo $grupo['id_carrera']; ?>"><i class="fas fa-users"></i> Grupos</a>
                <a href="gestion_alumnos.php"><i class="fas fa-users"></i> Alumnos</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesi&oacute;n</a>
            </div>
        </div>
        
        <!-- Información de la Carrera y Grupo -->
        <div class="carrera-info">
            <div class="carrera-header">
                <div>
                    <h2><?php echo htmlspecialchars($grupo['nombre']); ?></h2>
                    <div style="color: #2e7d32; font-size: 14px;">
                        <?php echo htmlspecialchars($grupo['carrera_nombre']); ?> • 
                        <?php echo $grupo['semestre']; ?>° Semestre • 
                        <?php echo htmlspecialchars($grupo['turno']); ?>
                    </div>
                </div>
                <span class="carrera-clave"><?php echo htmlspecialchars($grupo['carrera_clave']); ?></span>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="card">
            <h2><i class="fas fa-chart-line"></i> Estadísticas del Grupo</h2>
            <div class="stats-card">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_alumnos'] ?? 0; ?></div>
                    <div class="stat-label">Total Alumnos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['alumnos_activos'] ?? 0; ?></div>
                    <div class="stat-label">Alumnos Activos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['alumnos_beca'] ?? 0; ?></div>
                    <div class="stat-label">Con Beca</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $grupo['capacidad_maxima'] - ($stats['alumnos_activos'] ?? 0); ?></div>
                    <div class="stat-label">Cupos Disponibles</div>
                </div>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #f9fff9; border-radius: 8px; border: 1px solid #c8e6c9;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <span style="font-weight: 600; color: #1a5330;">Capacidad del Grupo</span>
                    <span style="font-weight: 700; color: #2e7d32;">
                        <?php echo $stats['alumnos_activos'] ?? 0; ?> / <?php echo $grupo['capacidad_maxima']; ?>
                    </span>
                </div>
                <div style="background: #c8e6c9; height: 10px; border-radius: 5px; overflow: hidden;">
                    <?php 
                    $porcentaje = $grupo['capacidad_maxima'] > 0 ? round((($stats['alumnos_activos'] ?? 0) / $grupo['capacidad_maxima']) * 100) : 0;
                    $color = $porcentaje >= 90 ? '#dc3545' : ($porcentaje >= 75 ? '#ffc107' : '#4caf50');
                    ?>
                    <div style="width: <?php echo min($porcentaje, 100); ?>%; height: 100%; background: <?php echo $color; ?>; border-radius: 5px;"></div>
                </div>
                <div style="text-align: center; margin-top: 5px; font-size: 14px; color: #1a5330;">
                    <?php echo $porcentaje; ?>% de capacidad utilizada
                </div>
            </div>
        </div>
        
        <!-- Tarjeta de Filtros -->
        <div class="card">
            <h2><i class="fas fa-filter"></i> Filtros de Búsqueda</h2>
            
            <!-- Búsqueda Rápida -->
            <div class="acciones-superiores">
                <div class="busqueda-rapida">
                    <input type="text" 
                           name="busqueda" 
                           placeholder="Buscar por nombre, apellido o matrícula..." 
                           value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                    <button type="submit" class="btn btn-primary" id="btnBuscar">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <a href="gestion_alumnos.php?grupo=<?php echo $id_grupo; ?>" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Nuevo Alumno
                </a>
            </div>
            
            <!-- Formulario de Filtros -->
            <form method="GET" class="form-grid">
                <input type="hidden" name="id" value="<?php echo $id_grupo; ?>">
                
                <div class="form-group">
                    <label><i class="fas fa-venus-mars"></i> Género:</label>
                    <select name="genero">
                        <option value="">Todos los Géneros</option>
                        <?php foreach ($generos as $genero): ?>
                        <option value="<?php echo $genero['id_genero']; ?>"
                                <?php echo $filtro_genero == $genero['id_genero'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genero['genero']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-circle"></i> Estatus:</label>
                    <select name="estatus">
                        <option value="">Todos los Estatus</option>
                        <option value="Activo" <?php echo $filtro_estatus == 'Activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="Inactivo" <?php echo $filtro_estatus == 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Aplicar Filtros
                    </button>
                    <a href="alumnos_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-secondary">
                        <i class="fas fa-broom"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Tarjeta de Lista de Alumnos -->
        <div class="card">
            <h2><i class="fas fa-list"></i> Lista de Alumnos</h2>
            
            <?php if (count($alumnos) > 0): ?>
                <div class="resultados-info">
                    <p><i class="fas fa-info-circle"></i> 
                        Mostrando <?php echo count($alumnos); ?> de <?php echo $total_alumnos; ?> alumnos
                        <?php if ($filtro_busqueda): ?>
                            - Resultados para: "<?php echo htmlspecialchars($filtro_busqueda); ?>"
                        <?php endif; ?>
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
                            <th><i class="fas fa-circle"></i> Estatus</th>
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
                                <span class="estatus-badge <?php echo $alumno['activo'] == 'Activo' ? 'estatus-activo' : 'estatus-inactivo'; ?>">
                                    <i class="fas fa-<?php echo $alumno['activo'] == 'Activo' ? 'check-circle' : 'times-circle'; ?>"></i>
                                    <?php echo $alumno['activo'] == 'Activo' ? 'Activo' : 'Inactivo'; ?>
                                </span>
                                <?php if ($alumno['beca'] == 'SI'): ?>
                                <span class="badge badge-beca" style="margin-top: 5px; display: block;">
                                    <i class="fas fa-award"></i> Beca
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="ver_alumno.php?id=<?php echo $alumno['id_alumno']; ?>" 
                                       class="btn btn-info btn-small" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editar_alumno.php?id=<?php echo $alumno['id_alumno']; ?>" 
                                       class="btn btn-success btn-small" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="quitar_alumno_grupo.php?alumno=<?php echo $alumno['id_alumno']; ?>&grupo=<?php echo $id_grupo; ?>" 
                                       class="btn btn-danger btn-small" title="Quitar del grupo"
                                       onclick="return confirm('¿Está seguro de quitar a este alumno del grupo?')">
                                        <i class="fas fa-user-minus"></i>
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
                        <a href="?id=<?php echo $id_grupo; ?>&page=<?php echo $page-1; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>&estatus=<?php echo $filtro_estatus; ?>&genero=<?php echo $filtro_genero; ?>"
                           class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_paginas, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                        <a href="?id=<?php echo $id_grupo; ?>&page=<?php echo $i; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>&estatus=<?php echo $filtro_estatus; ?>&genero=<?php echo $filtro_genero; ?>"
                           class="btn <?php echo $i == $page ? 'btn-primary active' : 'btn-secondary'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_paginas): ?>
                        <a href="?id=<?php echo $id_grupo; ?>&page=<?php echo $page+1; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>&estatus=<?php echo $filtro_estatus; ?>&genero=<?php echo $filtro_genero; ?>"
                           class="btn btn-secondary">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No se encontraron alumnos</h3>
                    <p>No hay alumnos en este grupo con los criterios de búsqueda seleccionados.</p>
                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                        <a href="gestion_alumnos.php?grupo=<?php echo $id_grupo; ?>" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Agregar Nuevo Alumno
                        </a>
                        <a href="alumnos_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-primary">
                            <i class="fas fa-redo"></i> Ver todos los alumnos
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Agregar Alumnos Existentes -->
        <?php if (count($alumnos_sin_grupo) > 0): ?>
        <div class="alumnos-disponibles">
            <h3><i class="fas fa-user-plus"></i> Agregar Alumnos Existentes</h3>
            <p style="color: #2e7d32; margin-bottom: 15px;">
                Alumnos sin grupo asignado que pueden ser agregados a este grupo:
            </p>
            
            <form method="POST" action="agregar_alumnos_grupo.php">
                <input type="hidden" name="id_grupo" value="<?php echo $id_grupo; ?>">
                
                <div class="alumnos-disponibles-list">
                    <?php foreach ($alumnos_sin_grupo as $alumno): ?>
                    <div class="alumno-disponible-item">
                        <div class="alumno-disponible-info">
                            <input type="checkbox" 
                                   class="checkbox-alumno" 
                                   name="alumnos[]" 
                                   value="<?php echo $alumno['id_alumno']; ?>"
                                   id="alumno_<?php echo $alumno['id_alumno']; ?>">
                            <div class="alumno-avatar" style="width: 30px; height: 30px; font-size: 12px;">
                                <?php echo strtoupper(substr($alumno['nombre'], 0, 1) . substr($alumno['apellido_paterno'], 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #1a5330; font-size: 14px;">
                                    <?php echo htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'] . ' ' . $alumno['nombre']); ?>
                                </div>
                                <div style="font-size: 12px; color: #2e7d32;">
                                    <?php echo htmlspecialchars($alumno['matricula']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="select-alumno">
                            <label for="alumno_<?php echo $alumno['id_alumno']; ?>" style="font-size: 12px; color: #4caf50; cursor: pointer;">
                                Seleccionar
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" class="btn btn-primary btn-agregar-seleccionados">
                    <i class="fas fa-user-plus"></i> Agregar Alumnos Seleccionados al Grupo
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Botones de Acción -->
        <div class="action-buttons">
            <a href="ver_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-volver">
                <i class="fas fa-arrow-left"></i> Volver al Grupo
            </a>
            <a href="editar_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-success">
                <i class="fas fa-edit"></i> Editar Grupo
            </a>
            <a href="gestion_alumnos.php?grupo=<?php echo $id_grupo; ?>" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Nuevo Alumno
            </a>
            <a href="importar_alumnos.php?grupo=<?php echo $id_grupo; ?>" class="btn btn-warning">
                <i class="fas fa-file-import"></i> Importar Alumnos
            </a>
            <a href="exportar_alumnos.php?grupo=<?php echo $id_grupo; ?>" class="btn btn-info">
                <i class="fas fa-file-export"></i> Exportar Lista
            </a>
            <a href="reporte_alumnos_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-secondary">
                <i class="fas fa-chart-pie"></i> Reporte de Grupo
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
        
        // Seleccionar/deseleccionar todos los alumnos disponibles
        const checkboxes = document.querySelectorAll('.checkbox-alumno');
        const selectAllBtn = document.createElement('button');
        selectAllBtn.type = 'button';
        selectAllBtn.className = 'btn btn-secondary btn-small';
        selectAllBtn.innerHTML = '<i class="fas fa-check-double"></i> Seleccionar Todos';
        selectAllBtn.style.marginBottom = '10px';
        
        if (checkboxes.length > 0) {
            document.querySelector('.alumnos-disponibles h3').insertAdjacentElement('afterend', selectAllBtn);
            
            selectAllBtn.addEventListener('click', function() {
                const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
                checkboxes.forEach(checkbox => {
                    checkbox.checked = !allChecked;
                });
                this.innerHTML = allChecked ? 
                    '<i class="fas fa-check-double"></i> Seleccionar Todos' : 
                    '<i class="fas fa-times"></i> Deseleccionar Todos';
            });
        }
        
        // Confirmar antes de quitar alumno del grupo
        document.querySelectorAll('a[title="Quitar del grupo"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('¿Está seguro de quitar a este alumno del grupo?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>