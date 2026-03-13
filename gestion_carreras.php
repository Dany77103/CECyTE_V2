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
$filtro_modalidad = $_GET['modalidad'] ?? '';
$filtro_duracion = $_GET['duracion'] ?? '';
$filtro_activo = $_GET['activo'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Obtener carreras con filtros
$sql = "SELECT c.*, 
                COUNT(DISTINCT g.id_grupo) as total_grupos,
                COUNT(DISTINCT a.id_alumno) as total_alumnos,
                (SELECT COUNT(DISTINCT hm.id_maestro) 
                 FROM horarios_maestros hm 
                 JOIN grupos g2 ON hm.id_grupo = g2.id_grupo 
                 WHERE g2.id_carrera = c.id_carrera) as total_maestros
        FROM carreras c 
        LEFT JOIN grupos g ON c.id_carrera = g.id_carrera AND g.activo = 1
        LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo AND a.activo = 'Activo'
        WHERE 1=1";

$params = [];

if ($filtro_modalidad) {
    $sql .= " AND c.modalidad = :modalidad";
    $params['modalidad'] = $filtro_modalidad;
}

if ($filtro_duracion) {
    $sql .= " AND c.duracion_semestres = :duracion";
    $params['duracion'] = $filtro_duracion;
}

if ($filtro_activo !== '') {
    $sql .= " AND c.activo = :activo";
    $params['activo'] = $filtro_activo;
}

if ($filtro_busqueda) {
    $sql .= " AND (c.nombre LIKE :busqueda OR 
                    c.clave LIKE :busqueda OR 
                    c.descripcion LIKE :busqueda)";
    $params['busqueda'] = "%$filtro_busqueda%";
}

$sql .= " GROUP BY c.id_carrera 
          ORDER BY c.nombre 
          LIMIT :limit OFFSET :offset";

$stmt = $con->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener total para paginación
$sql_total = "SELECT COUNT(*) as total FROM carreras c WHERE 1=1";
if ($filtro_modalidad) $sql_total .= " AND c.modalidad = '" . $con->quote($filtro_modalidad) . "'";
if ($filtro_duracion) $sql_total .= " AND c.duracion_semestres = " . (int)$filtro_duracion;
if ($filtro_activo !== '') $sql_total .= " AND c.activo = " . (int)$filtro_activo;
if ($filtro_busqueda) $sql_total .= " AND (c.nombre LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                              c.clave LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                              c.descripcion LIKE '%" . $con->quote($filtro_busqueda) . "%')";

$total_carreras = $con->query($sql_total)->fetchColumn();
$total_paginas = ceil($total_carreras / $limit);

// Obtener opciones para filtros
$modalidades = $con->query("SELECT DISTINCT modalidad FROM carreras WHERE modalidad IS NOT NULL AND modalidad != '' ORDER BY modalidad")->fetchAll();
$duraciones = $con->query("SELECT DISTINCT duracion_semestres FROM carreras WHERE duracion_semestres IS NOT NULL ORDER BY duracion_semestres")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Carreras - CECYTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #1a5330;
            --primary: #2e7d32;
            --primary-light: #4caf50;
            --accent: #8bc34a;
            --bg-light: #f4f7f6;
            --text-main: #333;
            --white: #ffffff;
        }

        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-main);
            padding: 20px;
        }

        .container { max-width: 1400px; margin: 0 auto; }

        /* Header Profesional */
        .header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .header h1 { font-size: 24px; display: flex; align-items: center; gap: 12px; }

        .nav-links { display: flex; gap: 10px; }
        .nav-links a {
            color: white; text-decoration: none; padding: 10px 15px;
            border-radius: 6px; font-size: 14px; background: rgba(255,255,255,0.1);
            transition: 0.3s; display: flex; align-items: center; gap: 8px;
        }
        .nav-links a:hover { background: rgba(255,255,255,0.2); transform: translateY(-2px); }

        /* Tarjetas de Contenido */
        .card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 4px solid var(--primary);
        }

        .card h2 {
            color: var(--primary-dark);
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Estadísticas Modernas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #eee;
            transition: 0.3s;
        }
        .stat-box:hover { border-color: var(--primary-light); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .stat-val { font-size: 28px; font-weight: 800; color: var(--primary); }
        .stat-lab { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 1px; }

        /* Formulario y Filtros */
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .input-group { display: flex; flex-direction: column; gap: 5px; }
        .input-group label { font-size: 13px; font-weight: 600; color: #555; }
        .input-group input, .input-group select {
            padding: 10px; border: 1px solid #ddd; border-radius: 6px; outline: none;
        }
        .input-group input:focus { border-color: var(--primary); }

        /* Botones */
        .btn {
            padding: 10px 20px; border-radius: 6px; border: none;
            cursor: pointer; font-weight: 600; display: inline-flex;
            align-items: center; gap: 8px; transition: 0.3s; text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--primary-light); color: white; }
        .btn-clear { background: #e0e0e0; color: #444; }

        /* Tabla Estilizada */
        .table-container { overflow-x: auto; }
        .styled-table {
            width: 100%; border-collapse: collapse; margin-top: 15px;
            font-size: 14px;
        }
        .styled-table thead tr { background: #f8f9fa; color: var(--primary-dark); text-align: left; }
        .styled-table th, .styled-table td { padding: 15px; border-bottom: 1px solid #eee; }
        .styled-table tbody tr:hover { background-color: #f1f8f1; }

        /* Badges */
        .badge {
            padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold;
        }
        .active-badge { background: #e8f5e9; color: #2e7d32; }
        .inactive-badge { background: #ffebee; color: #c62828; }
        .info-badge { background: #e3f2fd; color: #1565c0; }

        /* Paginación */
        .pagination {
            display: flex; justify-content: center; gap: 5px; margin-top: 20px;
        }
        .page-link {
            padding: 8px 12px; border-radius: 4px; background: white;
            border: 1px solid #ddd; color: var(--text-main); text-decoration: none;
        }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }

        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .nav-links { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="container">
    <header class="header">
        <h1><i class="fas fa-graduation-cap"></i> CECyTE Sistema de Carreras</h1>
        <nav class="nav-links">
            <a href="main.php"><i class="fas fa-th"></i> Dashboard</a>
            <a href="gestion_alumnos.php"><i class="fas fa-user-graduate"></i> Alumnos</a>
            <a href="gestion_usuarios.php"><i class="fas fa-cog"></i> Ajustes</a>
            <a href="logout.php" style="background: #c62828;"><i class="fas fa-power-off"></i> Salir</a>
        </nav>
    </header>

    <div class="card">
        <h2><i class="fas fa-chart-pie"></i> Resumen Académico</h2>
        <div class="stats-grid">
            <?php
            $total_carreras_activas = $con->query("SELECT COUNT(*) FROM carreras WHERE activo = 1")->fetchColumn();
            $total_carreras_inactivas = $con->query("SELECT COUNT(*) FROM carreras WHERE activo = 0")->fetchColumn();
            $total_grupos = $con->query("SELECT COUNT(DISTINCT g.id_grupo) FROM carreras c LEFT JOIN grupos g ON c.id_carrera = g.id_carrera WHERE c.activo = 1")->fetchColumn();
            $total_alumnos = $con->query("SELECT COUNT(DISTINCT a.id_alumno) FROM carreras c LEFT JOIN grupos g ON c.id_carrera = g.id_carrera LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo WHERE c.activo = 1 AND a.activo = 'Activo'")->fetchColumn();
            ?>
            <div class="stat-box">
                <div class="stat-val"><?php echo $total_carreras_activas + $total_carreras_inactivas; ?></div>
                <div class="stat-lab">Total Carreras</div>
            </div>
            <div class="stat-box">
                <div class="stat-val" style="color: var(--primary-light);"><?php echo $total_carreras_activas; ?></div>
                <div class="stat-lab">Activas</div>
            </div>
            <div class="stat-box">
                <div class="stat-val" style="color: #fb8c00;"><?php echo $total_grupos; ?></div>
                <div class="stat-lab">Grupos</div>
            </div>
            <div class="stat-box">
                <div class="stat-val" style="color: #1e88e5;"><?php echo $total_alumnos; ?></div>
                <div class="stat-lab">Alumnos</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2><i class="fas fa-search"></i> Búsqueda y Filtros</h2>
            <a href="nueva_carrera.php" class="btn btn-success"><i class="fas fa-plus"></i> Nueva Carrera</a>
        </div>
        
        <form method="GET" class="filter-form">
            <div class="input-group" style="grid-column: span 2;">
                <label>Término de búsqueda</label>
                <input type="text" name="busqueda" placeholder="Nombre, clave..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
            </div>
            <div class="input-group">
                <label>Modalidad</label>
                <select name="modalidad">
                    <option value="">Todas</option>
                    <?php foreach ($modalidades as $mod): ?>
                        <option value="<?php echo $mod['modalidad']; ?>" <?php echo $filtro_modalidad == $mod['modalidad'] ? 'selected' : ''; ?>>
                            <?php echo $mod['modalidad']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label>Estatus</label>
                <select name="activo">
                    <option value="">Todos</option>
                    <option value="1" <?php echo $filtro_activo === '1' ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo $filtro_activo === '0' ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="gestion_carreras.php" class="btn btn-clear">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2><i class="fas fa-list-ul"></i> Carreras Registradas</h2>
        <div class="table-container">
            <?php if (count($carreras) > 0): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Clave</th>
                        <th>Nombre de la Carrera</th>
                        <th>Modalidad</th>
                        <th>Duración</th>
                        <th>Estadísticas</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($carreras as $carrera): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($carrera['clave']); ?></strong></td>
                        <td>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($carrera['nombre']); ?></div>
                            <small style="color: #888;"><?php echo htmlspecialchars(substr($carrera['descripcion'] ?? '', 0, 60)); ?>...</small>
                        </td>
                        <td><span class="badge info-badge"><?php echo htmlspecialchars($carrera['modalidad'] ?: 'Escolarizada'); ?></span></td>
                        <td><?php echo $carrera['duracion_semestres']; ?> Sem.</td>
                        <td>
                            <div style="font-size: 12px;">
                                <span><i class="fas fa-users"></i> <?php echo $carrera['total_alumnos']; ?></span> | 
                                <span><i class="fas fa-layer-group"></i> <?php echo $carrera['total_grupos']; ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?php echo $carrera['activo'] ? 'active-badge' : 'inactive-badge'; ?>">
                                <?php echo $carrera['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="editar_carrera.php?id=<?php echo $carrera['id_carrera']; ?>" title="Editar" style="color: var(--primary);"><i class="fas fa-edit"></i></a>
                                <a href="ver_detalles.php?id=<?php echo $carrera['id_carrera']; ?>" title="Ver" style="color: #1e88e5;"><i class="fas fa-eye"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="pagination">
                <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&busqueda=<?php echo $filtro_busqueda; ?>&modalidad=<?php echo $filtro_modalidad; ?>&activo=<?php echo $filtro_activo; ?>" 
                       class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                       <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>

            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #888;">
                    <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 10px;"></i>
                    <p>No se encontraron carreras con los filtros seleccionados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>