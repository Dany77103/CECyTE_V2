<?php
session_start();

// Verificar permisos
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Paginación
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filtros
$filtro_carrera = $_GET['carrera'] ?? '';
$filtro_turno = $_GET['turno'] ?? '';
$filtro_activo = $_GET['activo'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Obtener grupos con filtros
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

// Obtener total para paginación
$sql_total = "SELECT COUNT(*) as total FROM grupos g WHERE 1=1";
if ($filtro_carrera) $sql_total .= " AND g.id_carrera = " . (int)$filtro_carrera;
if ($filtro_turno) $sql_total .= " AND g.turno = '" . $con->quote($filtro_turno) . "'";
if ($filtro_activo !== '') $sql_total .= " AND g.activo = " . (int)$filtro_activo;
if ($filtro_busqueda) $sql_total .= " AND (g.nombre LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                             g.descripcion LIKE '%" . $con->quote($filtro_busqueda) . "%')";

$total_grupos = $con->query($sql_total)->fetchColumn();
$total_paginas = ceil($total_grupos / $limit);

// Obtener opciones para filtros
$carreras = $con->query("SELECT id_carrera, nombre FROM carreras WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Obtener turnos únicos
$turnos_query = $con->query("SELECT DISTINCT turno FROM grupos WHERE turno IS NOT NULL AND turno != '' ORDER BY turno");
$turnos = $turnos_query->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$total_grupos_activos = $con->query("SELECT COUNT(*) FROM grupos WHERE activo = 1")->fetchColumn();
$total_grupos_inactivos = $con->query("SELECT COUNT(*) FROM grupos WHERE activo = 0")->fetchColumn();
$total_alumnos_grupos = $con->query("SELECT COUNT(DISTINCT a.id_alumno) FROM grupos g LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo WHERE g.activo = 1 AND a.activo = 'Activo'")->fetchColumn();
$total_maestros_grupos = $con->query("SELECT COUNT(DISTINCT hm.id_maestro) FROM grupos g LEFT JOIN horarios_maestros hm ON g.id_grupo = hm.id_grupo WHERE g.activo = 1 AND hm.estatus = 'Activo'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Grupos - CECYTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #1a5330;
            --primary: #2e7d32;
            --primary-light: #4caf50;
            --accent: #8bc34a;
            --bg-light: #f4f7f6;
            --text-dark: #1a5330;
            --white: #ffffff;
        }

        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg-light);
            background-image: linear-gradient(180deg, #e8f5e9 0%, #f4f7f6 300px);
            min-height: 100vh;
            color: #333;
            padding: 20px;
        }

        .container { max-width: 1400px; margin: 0 auto; }

        /* Header Modernizado */
        .header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: var(--white);
            padding: 20px 30px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(26, 83, 48, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 { font-size: 24px; display: flex; align-items: center; gap: 12px; }
        .header h1 i { color: var(--accent); }

        .nav-links { display: flex; gap: 10px; }
        .nav-links a {
            text-decoration: none;
            color: rgba(255,255,255,0.9);
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
            background: rgba(255,255,255,0.1);
        }
        .nav-links a:hover { background: var(--accent); color: var(--primary-dark); transform: translateY(-2px); }

        /* Cards Estilizadas */
        .card {
            background: var(--white);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e0e0e0;
        }

        .card-title {
            color: var(--primary-dark);
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Estadísticas Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-box {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            border-left: 5px solid var(--primary);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            text-align: center;
        }

        .stat-val { font-size: 28px; font-weight: 800; color: var(--primary); }
        .stat-lab { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #666; font-weight: 600; }

        /* Formulario de Filtros */
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .input-group { display: flex; flex-direction: column; gap: 8px; }
        .input-group label { font-size: 13px; font-weight: 700; color: var(--primary-dark); }
        
        select, input[type="text"] {
            padding: 12px;
            border: 1.5px solid #dce1de;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        select:focus, input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1); }

        /* Botones Personalizados */
        .btn {
            padding: 12px 20px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-search { background: var(--primary); color: white; }
        .btn-search:hover { background: var(--primary-dark); }
        
        .btn-add { background: var(--accent); color: var(--primary-dark); }
        .btn-add:hover { background: #7cb342; transform: scale(1.02); }

        .btn-clear { background: #f0f0f0; color: #666; }

        /* Tabla Refinada */
        .table-container { overflow-x: auto; margin-top: 10px; }
        
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .custom-table th {
            background: transparent;
            color: #888;
            font-size: 12px;
            text-transform: uppercase;
            padding: 10px 15px;
            text-align: left;
        }

        .custom-table tr { transition: 0.3s; }

        .custom-table td {
            background: white;
            padding: 15px;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }

        .custom-table td:first-child { border-left: 1px solid #f0f0f0; border-radius: 12px 0 0 12px; }
        .custom-table td:last-child { border-right: 1px solid #f0f0f0; border-radius: 0 12px 12px 0; }

        .custom-table tr:hover td {
            background: #f9fff9;
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        /* Badges */
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .active-bg { background: #e8f5e9; color: #2e7d32; }
        .inactive-bg { background: #ffebee; color: #c62828; }

        .group-name { font-weight: 700; color: var(--primary-dark); font-size: 15px; }
        .career-name { color: #666; font-size: 13px; margin-top: 4px; }

        /* Mini Stats en Fila */
        .mini-stats { display: flex; gap: 10px; margin-top: 8px; }
        .m-item { font-size: 11px; background: #f4f7f6; padding: 2px 8px; border-radius: 4px; color: #555; }

        /* Acciones */
        .actions-cell { display: flex; gap: 5px; }
        .action-btn {
            width: 35px; height: 35px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px;
            color: white;
            transition: 0.2s;
        }
        .edit { background: #4caf50; }
        .view { background: #2196f3; }
        .assign { background: #ff9800; }
        .action-btn:hover { opacity: 0.8; transform: translateY(-2px); }

        /* Paginación */
        .pagination {
            display: flex; justify-content: center; gap: 8px; margin-top: 30px;
        }
        .page-link {
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            background: white;
            border-radius: 8px;
            text-decoration: none;
            color: var(--primary);
            font-weight: 700;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .page-link.active { background: var(--primary); color: white; }

        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .filter-form { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <header class="header">
        <h1><i class="fas fa-layer-group"></i> Panel de Grupos</h1>
        <nav class="nav-links">
            <a href="main.php">Inicio</a>
            <a href="gestion_alumnos.php">Alumnos</a>
            <a href="gestion_maestros.php">Maestros</a>
            <a href="logout.php" style="background: #e53935;">Salir</a>
        </nav>
    </header>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-val"><?php echo $total_grupos_activos + $total_grupos_inactivos; ?></div>
            <div class="stat-lab">Grupos Registrados</div>
        </div>
        <div class="stat-box" style="border-left-color: #8bc34a;">
            <div class="stat-val"><?php echo $total_grupos_activos; ?></div>
            <div class="stat-lab">Grupos Activos</div>
        </div>
        <div class="stat-box" style="border-left-color: #2196f3;">
            <div class="stat-val"><?php echo $total_alumnos_grupos; ?></div>
            <div class="stat-lab">Alumnos Totales</div>
        </div>
        <div class="stat-box" style="border-left-color: #ff9800;">
            <div class="stat-val"><?php echo $total_maestros_grupos; ?></div>
            <div class="stat-lab">Maestros en Horario</div>
        </div>
    </div>

    <div class="card" style="margin-top: 25px;">
        <div class="card-title"><i class="fas fa-search"></i> Búsqueda Avanzada</div>
        <form method="GET" class="filter-form">
            <div class="input-group">
                <label>Palabra Clave</label>
                <input type="text" name="busqueda" placeholder="Nombre del grupo..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
            </div>
            <div class="input-group">
                <label>Carrera</label>
                <select name="carrera">
                    <option value="">Todas</option>
                    <?php foreach ($carreras as $c): ?>
                        <option value="<?php echo $c['id_carrera']; ?>" <?php echo $filtro_carrera == $c['id_carrera'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label>Turno</label>
                <select name="turno">
                    <option value="">Todos</option>
                    <?php foreach ($turnos as $t): ?>
                        <option value="<?php echo htmlspecialchars($t['turno']); ?>" <?php echo $filtro_turno == $t['turno'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['turno']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group" style="flex-direction: row; gap: 5px;">
                <button type="submit" class="btn btn-search"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="gestion_grupos.php" class="btn btn-clear">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div class="card-title" style="margin-bottom: 0;"><i class="fas fa-list-ul"></i> Registros de Grupos</div>
            <a href="nuevo_grupo.php" class="btn btn-add"><i class="fas fa-plus"></i> Nuevo Grupo</a>
        </div>

        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID / Grupo</th>
                        <th>Especialidad / Carrera</th>
                        <th>Población</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grupos as $g): ?>
                    <tr>
                        <td>
                            <div class="group-name"><?php echo htmlspecialchars($g['nombre']); ?></div>
                            <div style="font-size: 11px; color: #999;">Turno: <?php echo htmlspecialchars($g['turno'] ?: 'N/A'); ?></div>
                        </td>
                        <td>
                            <div class="career-name"><?php echo htmlspecialchars($g['carrera_nombre'] ?: 'No asignada'); ?></div>
                        </td>
                        <td>
                            <div class="mini-stats">
                                <span class="m-item"><i class="fas fa-user-graduate"></i> <?php echo $g['total_alumnos']; ?></span>
                                <span class="m-item"><i class="fas fa-chalkboard-teacher"></i> <?php echo $g['total_maestros']; ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="badge-status <?php echo $g['activo'] ? 'active-bg' : 'inactive-bg'; ?>">
                                <?php echo $g['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <a href="ver_grupo.php?id=<?php echo $g['id_grupo']; ?>" class="action-btn view" title="Ver"><i class="fas fa-eye"></i></a>
                                <a href="editar_grupo.php?id=<?php echo $g['id_grupo']; ?>" class="action-btn edit" title="Editar"><i class="fas fa-pen"></i></a>
                                <a href="asignar_alumnos_grupo.php?id_grupo=<?php echo $g['id_grupo']; ?>" class="action-btn assign" title="Alumnos" style="background:#673ab7;"><i class="fas fa-user-plus"></i></a>
                                <a href="horario_grupo.php?id=<?php echo $g['id_grupo']; ?>" class="action-btn view" title="Horario" style="background:#009688;"><i class="fas fa-calendar-alt"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?page=<?php echo $i; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>&carrera=<?php echo $filtro_carrera; ?>&turno=<?php echo urlencode($filtro_turno); ?>&activo=<?php echo $filtro_activo; ?>" 
                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                   <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>