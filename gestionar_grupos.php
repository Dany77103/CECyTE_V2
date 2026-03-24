<?php
session_start();

// 1. VERIFICACIÓN DE PERMISOS (Lógica original intacta)
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

// 5. TOTAL PARA PAGINACIÓN (Lógica original)
$sql_total = "SELECT COUNT(*) as total FROM grupos g WHERE 1=1";
if ($filtro_carrera) $sql_total .= " AND g.id_carrera = " . (int)$filtro_carrera;
if ($filtro_turno) $sql_total .= " AND g.turno = '" . $con->quote($filtro_turno) . "'";
if ($filtro_activo !== '') $sql_total .= " AND g.activo = " . (int)$filtro_activo;
if ($filtro_busqueda) $sql_total .= " AND (g.nombre LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                           g.descripcion LIKE '%" . $con->quote($filtro_busqueda) . "%')";

$total_grupos = $con->query($sql_total)->fetchColumn();
$total_paginas = ceil($total_grupos / $limit);

// 6. DATOS PARA SELECTS Y ESTADÍSTICAS
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
    <title>SGA-CECYTE | Grupos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --accent: #8bc34a;
            --bg: #f8fafb;
            --white: #ffffff;
            --text: #334155;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --border: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background-color: var(--bg); 
            font-family: 'Inter', sans-serif; 
            color: var(--text);
            padding-top: 80px;
        }

        /* --- NAVBAR --- */
        .navbar {
            background: var(--white);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .navbar-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--primary); font-weight: 800; }
        .navbar-brand img { height: 40px; }

        /* --- CONTENEDOR --- */
        .container { max-width: 1300px; margin: 0 auto; padding: 20px; }

        /* --- ESTADÍSTICAS --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 5px solid var(--primary-light);
        }
        .stat-icon {
            width: 50px; height: 50px;
            background: #e8f5e9;
            color: var(--primary-light);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }
        .stat-info h3 { font-size: 1.5rem; color: var(--primary); }
        .stat-info p { font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #64748b; }

        /* --- FILTROS --- */
        .card { 
            background: var(--white); border-radius: var(--border); 
            padding: 25px; margin-bottom: 25px; box-shadow: var(--shadow);
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .form-label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--primary); margin-bottom: 8px; text-transform: uppercase; }
        .form-control, .form-select {
            width: 100%; padding: 10px 15px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 0.9rem;
        }

        /* --- TABLA --- */
        .table-wrapper { overflow-x: auto; }
        .custom-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .custom-table th { padding: 12px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; text-align: left; }
        .custom-table tr td { background: var(--white); padding: 18px 20px; }
        .custom-table tr td:first-child { border-radius: 15px 0 0 15px; }
        .custom-table tr td:last-child { border-radius: 0 15px 15px 0; }
        .custom-table tr:hover td { background: #f1f5f9; }

        /* --- ELEMENTOS DE FILA --- */
        .group-tag { font-weight: 700; color: var(--primary); font-size: 1rem; }
        .sub-text { font-size: 0.8rem; color: #64748b; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .bg-active { background: #dcfce7; color: #166534; }
        .bg-inactive { background: #fee2e2; color: #991b1b; }

        /* --- BOTONES --- */
        .btn { 
            padding: 10px 20px; border-radius: 12px; border: none; cursor: pointer; 
            font-weight: 600; font-size: 0.85rem; display: inline-flex; 
            align-items: center; gap: 8px; text-decoration: none; transition: 0.2s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
        .btn-accent { background: var(--accent); color: var(--primary); }
        .btn-light { background: #f1f5f9; color: #475569; }

        .action-icon {
            width: 34px; height: 34px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            color: white; margin-right: 4px; transition: 0.2s;
        }
        .bg-view { background: #3b82f6; }
        .bg-edit { background: #f59e0b; }
        .bg-assign { background: #10b981; }

        /* --- PAGINACIÓN --- */
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 30px; list-style: none; }
        .page-link { 
            padding: 8px 16px; border-radius: 10px; background: var(--white);
            text-decoration: none; color: var(--primary); font-weight: 700; box-shadow: var(--shadow);
        }
        .page-item.active .page-link { background: var(--primary); color: white; }

        @media (max-width: 768px) { .filter-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo_cecyte.jpg" alt="Logo">
            <span>CECyTE Gestión</span>
        </a>
        <div style="display: flex; gap: 10px;">
            <a href="main.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Panel</a>
            <a href="logout.php" class="btn btn-light" style="color: #ef4444;"><i class="fas fa-power-off"></i></a>
        </div>
    </nav>

    <div class="container">
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-info">
                    <h3><?= $total_grupos ?></h3>
                    <p>Grupos Totales</p>
                </div>
            </div>
            <div class="stat-card" style="border-left-color: var(--accent);">
                <div class="stat-icon" style="background: #f0f4e8; color: var(--accent);"><i class="fas fa-check-double"></i></div>
                <div class="stat-info">
                    <h3><?= $total_grupos_activos ?></h3>
                    <p>Grupos Activos</p>
                </div>
            </div>
            <div class="stat-card" style="border-left-color: #3b82f6;">
                <div class="stat-icon" style="background: #eff6ff; color: #3b82f6;"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-info">
                    <h3><?= $total_alumnos_grupos ?></h3>
                    <p>Alumnos Inscritos</p>
                </div>
            </div>
        </div>

        <div class="card">
            <form method="GET" class="filter-grid">
                <div>
                    <label class="form-label">Buscar Grupo</label>
                    <input type="text" name="busqueda" class="form-control" placeholder="Nombre o clave..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                </div>
                <div>
                    <label class="form-label">Especialidad</label>
                    <select name="carrera" class="form-select">
                        <option value="">Todas las Carreras</option>
                        <?php foreach($carreras as $c): ?>
                            <option value="<?= $c['id_carrera'] ?>" <?= $filtro_carrera == $c['id_carrera'] ? 'selected' : '' ?>><?= $c['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Turno</label>
                    <select name="turno" class="form-select">
                        <option value="">Ambos Turnos</option>
                        <?php foreach($turnos as $t): ?>
                            <option value="<?= $t['turno'] ?>" <?= $filtro_turno == $t['turno'] ? 'selected' : '' ?>><?= $t['turno'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;"><i class="fas fa-filter"></i></button>
                    <a href="gestion_grupos.php" class="btn btn-light"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 1.1rem; color: var(--primary);"><i class="fas fa-list"></i> Relación de Grupos</h2>
                <a href="nuevo_grupo.php" class="btn btn-accent"><i class="fas fa-plus"></i> Nuevo Registro</a>
            </div>

            <div class="table-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Grupo / Turno</th>
                            <th>Carrera / Especialidad</th>
                            <th>Población</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grupos as $g): ?>
                        <tr>
                            <td>
                                <div class="group-tag"><?= $g['nombre'] ?></div>
                                <div class="sub-text"><?= $g['turno'] ?: 'No definido' ?></div>
                            </td>
                            <td>
                                <div class="sub-text" style="color: var(--primary); font-weight: 600;"><?= $g['carrera_nombre'] ?: 'N/A' ?></div>
                                <div style="font-size: 0.7rem; color: #94a3b8;"><?= $g['descripcion'] ?: '' ?></div>
                            </td>
                            <td>
                                <div class="sub-text"><i class="fas fa-users"></i> <?= $g['total_alumnos'] ?> Alumnos</div>
                                <div class="sub-text"><i class="fas fa-chalkboard-teacher"></i> <?= $g['total_maestros'] ?> Maestros</div>
                            </td>
                            <td>
                                <span class="badge <?= $g['activo'] ? 'bg-active' : 'bg-inactive' ?>">
                                    <?= $g['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td>
    <a href="ver_grupo.php?id=<?= $g['id_grupo'] ?>" 
       class="action-icon bg-view" 
       title="Ver Detalles">
       <i class="fas fa-eye"></i>
    </a>

    <a href="editar_grupo_2.php?id=<?= $g['id_grupo'] ?>" 
       class="action-icon bg-edit" 
       title="Editar Grupo">
       <i class="fas fa-edit"></i>
    </a>

    <a href="asignar_alumnos_grupo.php?id_grupo=<?= $g['id_grupo'] ?>" 
   class="action-icon bg-assign" 
   style="background: #10b981;" 
   title="Asignar Alumnos">
   <i class="fas fa-user-plus"></i>
</a>
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
    </div>
</body>
</html>