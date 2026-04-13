<?php
session_start();

// 1. VERIFICACIÓN DE PERMISOS
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// 2. CONFIGURACIÓN Y PAGINACIÓN
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 3. FILTROS
$filtro_carrera = $_GET['carrera'] ?? '';
$filtro_turno = $_GET['turno'] ?? '';
$filtro_activo = $_GET['activo'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// 4. CONSULTA PRINCIPAL
$sql = "SELECT g.*, 
               c.nombre as carrera_nombre,
               COUNT(DISTINCT a.id_alumno) as total_alumnos,
               COUNT(DISTINCT hm.id_maestro) as total_maestros,
               COUNT(DISTINCT m.id_materia) as total_materias
        FROM grupos g 
        LEFT JOIN carreras c ON g.id_carrera = c.id_carrera
        LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo AND a.activo = 'Activo'
        LEFT JOIN horarios_maestros hm ON g.id_grupo = hm.id_grupo AND hm.estatus = 'Activo'
        LEFT JOIN materias m ON hm.id_materia = m.id_materia
        WHERE 1=1";

$params = [];

if ($filtro_carrera) {
    $sql .= " AND g.id_carrera = :carrera";
    $params['carrera'] = $filtro_carrera;
}

if ($filtro_turno) {
    $sql .= " AND g.turno = :turno";
    $params['turno'] = $filtro_turno;
}

if ($filtro_activo !== '') {
    $sql .= " AND g.activo = :activo";
    $params['activo'] = $filtro_activo;
}

if ($filtro_busqueda) {
    $sql .= " AND (g.nombre LIKE :busqueda OR 
                    g.descripcion LIKE :busqueda OR
                    c.nombre LIKE :busqueda)";
    $params['busqueda'] = "%$filtro_busqueda%";
}

$sql .= " GROUP BY g.id_grupo 
          ORDER BY c.nombre, g.nombre 
          LIMIT :limit OFFSET :offset";

$stmt = $con->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. TOTAL PARA PAGINACIÓN
$sql_total = "SELECT COUNT(*) as total FROM grupos g WHERE 1=1";
if ($filtro_carrera) $sql_total .= " AND g.id_carrera = " . (int)$filtro_carrera;
if ($filtro_turno) $sql_total .= " AND g.turno = '" . $con->quote($filtro_turno) . "'";
if ($filtro_activo !== '') $sql_total .= " AND g.activo = " . (int)$filtro_activo;
if ($filtro_busqueda) $sql_total .= " AND (g.nombre LIKE '%" . $con->quote($filtro_busqueda) . "%' OR g.descripcion LIKE '%" . $con->quote($filtro_busqueda) . "%')";

$total_grupos = $con->query($sql_total)->fetchColumn();
$total_paginas = ceil($total_grupos / $limit);

// 6. DATOS PARA SELECTS
$carreras = $con->query("SELECT id_carrera, nombre FROM carreras WHERE activo = 1 ORDER BY nombre")->fetchAll();
$turnos = $con->query("SELECT DISTINCT turno FROM grupos WHERE turno IS NOT NULL AND turno != '' ORDER BY turno")->fetchAll(PDO::FETCH_ASSOC);

$total_grupos_activos = $con->query("SELECT COUNT(*) FROM grupos WHERE activo = 1")->fetchColumn();
$total_alumnos_grupos = $con->query("SELECT COUNT(DISTINCT a.id_alumno) FROM grupos g LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo WHERE g.activo = 1 AND a.activo = 'Activo'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Grupos | CECyTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --accent: #8bc34a;
            --bg: #f4f7f6;
            --white: #ffffff;
            --text-main: #1e293b;
            --text-sub: #64748b;
            --shadow: 0 4px 25px rgba(0,0,0,0.08);
            --radius: 20px;
        }

        body { 
            background-color: var(--bg); 
            font-family: 'Inter', sans-serif; 
            color: var(--text-main);
            padding-top: 90px;
        }

        /* --- NAVBAR --- */
        .navbar {
            background: var(--white);
            height: 70px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 5%; position: fixed;
            top: 0; left: 0; right: 0; z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.04);
        }
        .navbar-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--primary); font-weight: 800; }
        .navbar-brand img { height: 42px; }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 25px; }

        /* --- STATS --- */
        .stats-container {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px; margin-bottom: 30px;
        }
        .stat-card {
            background: var(--white); padding: 25px; border-radius: var(--radius);
            box-shadow: var(--shadow); display: flex; align-items: center; gap: 20px;
            border-bottom: 4px solid var(--primary);
        }
        .stat-icon {
            width: 60px; height: 60px; background: #f0fdf4; color: var(--primary);
            border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
        }
        .stat-info h3 { font-size: 1.8rem; font-weight: 800; line-height: 1; margin-bottom: 5px; }
        .stat-info p { font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; letter-spacing: 0.5px; }

        /* --- CARDS & FILTERS --- */
        .main-card { 
            background: var(--white); border-radius: var(--radius); 
            padding: 30px; margin-bottom: 30px; box-shadow: var(--shadow);
        }
        .filter-header {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; align-items: end;
        }
        .label-style { display: block; font-size: 0.7rem; font-weight: 800; color: var(--primary); margin-bottom: 8px; text-transform: uppercase; }
        .input-custom {
            width: 100%; padding: 12px 18px; border-radius: 12px; border: 1.5px solid #e2e8f0; 
            font-size: 0.9rem; transition: 0.3s;
        }
        .input-custom:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(26, 83, 48, 0.05); }

        /* --- TABLE --- */
        .table-responsive { overflow-x: auto; margin-top: 20px; }
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .data-table th { padding: 15px 25px; color: var(--text-sub); font-size: 0.75rem; text-transform: uppercase; font-weight: 800; }
        .data-table td { background: var(--white); padding: 20px 25px; border-top: 1px solid #f8fafc; border-bottom: 1px solid #f8fafc; }
        .data-table td:first-child { border-left: 1px solid #f8fafc; border-radius: 18px 0 0 18px; }
        .data-table td:last-child { border-right: 1px solid #f8fafc; border-radius: 0 18px 18px 0; }
        .data-table tr:hover td { background: #fdfdfd; transform: scale(1.002); }

        /* --- BADGES & TAGS --- */
        .group-name { font-weight: 800; color: var(--primary); font-size: 1.1rem; }
        .badge-status { padding: 6px 14px; border-radius: 10px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .status-on { background: #dcfce7; color: #166534; }
        .status-off { background: #fee2e2; color: #991b1b; }

        /* --- ACTIONS --- */
        .btn-circle {
            width: 38px; height: 38px; border-radius: 12px; display: inline-flex;
            align-items: center; justify-content: center; color: white; 
            text-decoration: none; transition: 0.3s; font-size: 0.9rem;
        }
        .btn-circle:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        /* --- PAGINATION --- */
        .pagination { display: flex; justify-content: center; gap: 10px; list-style: none; margin-top: 20px; }
        .page-link { 
            padding: 10px 18px; border-radius: 12px; background: white;
            text-decoration: none; color: var(--primary); font-weight: 700; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .active .page-link { background: var(--primary); color: white; }

        .btn-main {
            background: var(--primary); color: white; padding: 12px 25px; 
            border-radius: 12px; text-decoration: none; font-weight: 700; 
            font-size: 0.9rem; display: inline-flex; align-items: center; gap: 10px;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="Logo">
            <span>CECyTE Control</span>
        </a>
        <div style="display: flex; gap: 15px;">
            <a href="main.php" class="btn-main" style="background: #f1f5f9; color: #475569;"><i class="fas fa-home"></i> Inicio</a>
            <a href="logout.php" class="btn-main" style="background: #fee2e2; color: #dc2626;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="container">
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-info">
                    <h3><?= $total_grupos ?></h3>
                    <p>Total Grupos</p>
                </div>
            </div>
            <div class="stat-card" style="border-color: var(--accent);">
                <div class="stat-icon" style="background: #f7fee7; color: var(--accent);"><i class="fas fa-toggle-on"></i></div>
                <div class="stat-info">
                    <h3><?= $total_grupos_activos ?></h3>
                    <p>Grupos Activos</p>
                </div>
            </div>
            <div class="stat-card" style="border-color: #3b82f6;">
                <div class="stat-icon" style="background: #eff6ff; color: #3b82f6;"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-info">
                    <h3><?= $total_alumnos_grupos ?></h3>
                    <p>Alumnos Totales</p>
                </div>
            </div>
        </div>

        <div class="main-card">
            <form method="GET" class="filter-header">
                <div>
                    <label class="label-style">Filtrar por nombre</label>
                    <input type="text" name="busqueda" class="input-custom" placeholder="Ej. 401..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                </div>
                <div>
                    <label class="label-style">Carrera</label>
                    <select name="carrera" class="input-custom">
                        <option value="">Todas las especialidades</option>
                        <?php foreach($carreras as $c): ?>
                            <option value="<?= $c['id_carrera'] ?>" <?= $filtro_carrera == $c['id_carrera'] ? 'selected' : '' ?>><?= $c['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label-style">Turno</label>
                    <select name="turno" class="input-custom">
                        <option value="">Cualquier turno</option>
                        <?php foreach($turnos as $t): ?>
                            <option value="<?= $t['turno'] ?>" <?= $filtro_turno == $t['turno'] ? 'selected' : '' ?>><?= $t['turno'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-main" style="flex: 1; justify-content: center;"><i class="fas fa-search"></i></button>
                    <a href="gestion_grupos.php" class="btn-main" style="background: #64748b;"><i class="fas fa-sync-alt"></i></a>
                </div>
            </form>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin: 40px 0 20px 0;">
            <h2 style="font-weight: 800; color: var(--primary); font-size: 1.4rem;">Listado de Grupos</h2>
            <a href="nuevo_grupo.php" class="btn-main"><i class="fas fa-plus-circle"></i> Agregar Grupo</a>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Grupo / Turno</th>
                        <th>Especialidad</th>
                        <th>Estadísticas</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grupos as $g): ?>
                    <tr>
                        <td>
                            <div class="group-name"><?= $g['nombre'] ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-sub); font-weight: 600;"><i class="far fa-clock"></i> <?= $g['turno'] ?: 'Pendiente' ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #475569; font-size: 0.85rem;"><?= $g['carrera_nombre'] ?: 'Tronco Común' ?></div>
                            <div style="font-size: 0.7rem; color: #94a3b8;"><?= substr($g['descripcion'], 0, 40) ?>...</div>
                        </td>
                        <td>
                            <div style="font-size: 0.8rem; font-weight: 600; color: #1e293b;">
                                <i class="fas fa-users" style="color: var(--accent); width: 20px;"></i> <?= $g['total_alumnos'] ?> <span style="color: #94a3b8; font-weight: 400;">Alumnos</span>
                            </div>
                            <div style="font-size: 0.8rem; font-weight: 600; color: #1e293b;">
                                <i class="fas fa-chalkboard-teacher" style="color: #3b82f6; width: 20px;"></i> <?= $g['total_maestros'] ?> <span style="color: #94a3b8; font-weight: 400;">Docentes</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge-status <?= $g['activo'] ? 'status-on' : 'status-off' ?>">
                                <?= $g['activo'] ? '• Activo' : '• Inactivo' ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <a href="ver_grupo.php?id=<?= $g['id_grupo'] ?>" class="btn-circle" style="background: #3b82f6;" title="Ver"><i class="fas fa-eye"></i></a>
                                <a href="editar_grupo_2.php?id=<?= $g['id_grupo'] ?>" class="btn-circle" style="background: #f59e0b;" title="Editar"><i class="fas fa-pen"></i></a>
                                <a href="asignar_alumnos_grupo.php?id_grupo=<?= $g['id_grupo'] ?>" class="btn-circle" style="background: var(--primary);" title="Alumnos"><i class="fas fa-user-plus"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_paginas > 1): ?>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a href="?page=<?= $i ?>&busqueda=<?= urlencode($filtro_busqueda) ?>&carrera=<?= $filtro_carrera ?>&turno=<?= urlencode($filtro_turno) ?>&activo=<?= $filtro_activo ?>" class="page-link"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
        <?php endif; ?>
    </div>
    
    <div style="height: 50px;"></div>
</body>
</html>