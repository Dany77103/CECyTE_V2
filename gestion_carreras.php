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
if ($filtro_modalidad) $sql_total .= " AND c.modalidad = " . $con->quote($filtro_modalidad);
if ($filtro_duracion) $sql_total .= " AND c.duracion_semestres = " . (int)$filtro_duracion;
if ($filtro_activo !== '') $sql_total .= " AND c.activo = " . (int)$filtro_activo;
if ($filtro_busqueda) $sql_total .= " AND (c.nombre LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                            c.clave LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                            c.descripcion LIKE '%" . $con->quote($filtro_busqueda) . "%')";

$total_carreras = $con->query($sql_total)->fetchColumn();
$total_paginas = ceil($total_carreras / $limit);

$modalidades = $con->query("SELECT DISTINCT modalidad FROM carreras WHERE modalidad IS NOT NULL AND modalidad != '' ORDER BY modalidad")->fetchAll();
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
            --verde-oscuro: #1a5330;
            --verde-principal: #2e7d32;
            --verde-claro: #4caf50;
            --verde-suave: #e8f5e9;
            --blanco: #ffffff;
            --fondo: #f4f7f6;
            --sombra: 0 4px 15px rgba(0,0,0,0.1);
        }

        body { background-color: var(--fondo); font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }

        .header { background: var(--blanco); padding: 20px 30px; border-radius: 12px; margin-bottom: 25px; box-shadow: var(--sombra); display: flex; justify-content: space-between; align-items: center; border-left: 8px solid var(--verde-oscuro); }
        .header h1 { color: var(--verde-oscuro); font-size: 24px; display: flex; align-items: center; gap: 12px; }

        .nav-links a { text-decoration: none; padding: 10px 15px; border-radius: 8px; font-weight: 600; color: var(--verde-principal); background: var(--verde-suave); margin-left: 10px; transition: 0.3s; }
        .nav-links a:hover { background: var(--verde-principal); color: white; }

        .card { background: var(--blanco); border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: var(--sombra); }
        .card h2 { color: var(--verde-oscuro); font-size: 20px; margin-bottom: 20px; border-bottom: 2px solid var(--verde-suave); padding-bottom: 10px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stat-box { background: var(--verde-suave); padding: 20px; border-radius: 10px; text-align: center; }
        .stat-val { font-size: 28px; font-weight: 800; color: var(--verde-oscuro); }

        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end; }
        .input-group label { font-size: 13px; font-weight: 600; color: #555; }
        .input-group input, .input-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }

        .btn { padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--verde-principal); color: white; }
        
        .styled-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .styled-table thead { background: var(--verde-oscuro); color: white; }
        .styled-table th, .styled-table td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .active-badge { background: #c8e6c9; color: #2e7d32; }
        .inactive-badge { background: #ffcdd2; color: #c62828; }

        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .page-link { padding: 8px 14px; border-radius: 4px; background: white; border: 1px solid #ddd; color: var(--verde-principal); text-decoration: none; }
        .page-link.active { background: var(--verde-principal); color: white; }
    </style>
</head>
<body>

<div class="container">
    <header class="header">
        <h1><i class="fas fa-graduation-cap"></i> Gestión de Carreras</h1>
        <nav class="nav-links">
            <a href="main.php"><i class="fas fa-home"></i> Inicio</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </nav>
    </header>

    <div class="card">
        <h2><i class="fas fa-search"></i> Filtros</h2>
        <form method="GET" class="filter-form">
            <div class="input-group" style="grid-column: span 2;">
                <label>Búsqueda</label>
                <input type="text" name="busqueda" value="<?= htmlspecialchars($filtro_busqueda) ?>">
            </div>
            <div class="input-group">
                <label>Modalidad</label>
                <select name="modalidad">
                    <option value="">Todas</option>
                    <?php foreach ($modalidades as $mod): ?>
                        <option value="<?= $mod['modalidad'] ?>" <?= $filtro_modalidad == $mod['modalidad'] ? 'selected' : '' ?>><?= $mod['modalidad'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
        </form>
    </div>

    <div class="card">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Nombre</th>
                    <th>Alumnos</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($carreras as $c): ?>
                <tr>
                    <td><strong><?= $c['clave'] ?></strong></td>
                    <td><?= $c['nombre'] ?></td>
                    <td><?= $c['total_alumnos'] ?></td>
                    <td><span class="badge <?= $c['activo'] ? 'active-badge' : 'inactive-badge' ?>"><?= $c['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                    <td>
                        <a href="editar_carrera.php?id=<?= $c['id_carrera'] ?>"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>