<?php
session_start();

// Verificar permisos (Sin tocar lógica de back)
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Paginación
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filtros
$filtro_carrera = $_GET['carrera'] ?? '';
$filtro_grupo = $_GET['grupo'] ?? '';
$filtro_estatus = $_GET['activo'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Obtener alumnos con filtros
$sql = "SELECT a.*, c.nombre as carrera_nombre, g.nombre as grupo_nombre 
        FROM alumnos a 
        LEFT JOIN carreras c ON a.id_carrera = c.id_carrera 
        LEFT JOIN grupos g ON a.id_grupo = g.id_grupo 
        WHERE 1=1";

$params = [];

if ($filtro_carrera) {
    $sql .= " AND a.id_carrera = :carrera";
    $params['carrera'] = $filtro_carrera;
}

if ($filtro_grupo) {
    $sql .= " AND a.id_grupo = :grupo";
    $params['grupo'] = $filtro_grupo;
}

if ($filtro_estatus) {
    $sql .= " AND a.activo = :activo";
    $params['activo'] = $filtro_estatus;
}

if ($filtro_busqueda) {
    $sql .= " AND (a.matricula LIKE :busqueda OR 
                    a.nombre LIKE :busqueda OR 
                    a.apellido_paterno LIKE :busqueda OR 
                    a.apellido_materno LIKE :busqueda OR
                    a.correo_institucional LIKE :busqueda)";
    $params['busqueda'] = "%$filtro_busqueda%";
}

$sql .= " ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre 
          LIMIT :limit OFFSET :offset";

$stmt = $con->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener total para paginación
$sql_total = "SELECT COUNT(*) as total FROM alumnos a WHERE 1=1";
if ($filtro_carrera) $sql_total .= " AND a.id_carrera = " . (int)$filtro_carrera;
if ($filtro_grupo) $sql_total .= " AND a.id_grupo = " . (int)$filtro_grupo;
if ($filtro_estatus) $sql_total .= " AND a.activo = " . $con->quote($filtro_estatus);
if ($filtro_busqueda) $sql_total .= " AND (a.matricula LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                           a.nombre LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                           a.apellido_paterno LIKE '%" . $con->quote($filtro_busqueda) . "%' OR 
                                           a.apellido_materno LIKE '%" . $con->quote($filtro_busqueda) . "%' OR
                                           a.correo_institucional LIKE '%" . $con->quote($filtro_busqueda) . "%')";

$total_alumnos = $con->query($sql_total)->fetchColumn();
$total_paginas = ceil($total_alumnos / $limit);

// Obtener opciones para filtros
$carreras = $con->query("SELECT id_carrera, nombre FROM carreras WHERE activo = 1 ORDER BY nombre")->fetchAll();
$grupos = $con->query("SELECT id_grupo, nombre FROM grupos WHERE activo = 1 ORDER BY nombre")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Alumnos - CECYTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --verde-oscuro-1: #1a5330;
            --verde-oscuro-2: #2e7d32;
            --verde-medio: #4caf50;
            --verde-claro: #8bc34a;
            --verde-muy-claro: #f1f8e9;
            --blanco: #ffffff;
            --sombra: rgba(0, 0, 0, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, sans-serif; }

        body { background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); min-height: 100vh; padding: 20px; color: #333; }

        .container { max-width: 1400px; margin: 0 auto; }

        .header { background: var(--blanco); padding: 20px 30px; border-radius: 15px; margin-bottom: 25px; box-shadow: 0 4px 15px var(--sombra); display: flex; justify-content: space-between; align-items: center; border-left: 8px solid var(--verde-oscuro-1); }

        .header h1 { color: var(--verde-oscuro-1); font-size: 24px; display: flex; align-items: center; gap: 12px; }

        .nav-links { display: flex; gap: 10px; }

        .nav-links a { text-decoration: none; padding: 10px 15px; border-radius: 8px; font-size: 14px; font-weight: 600; transition: all 0.3s; background: var(--verde-muy-claro); color: var(--verde-oscuro-2); border: 1px solid var(--verde-claro); }

        .nav-links a:hover { background: var(--verde-oscuro-2); color: white; transform: translateY(-2px); }

        .card { background: var(--blanco); border-radius: 15px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 15px var(--sombra); }

        .card h2 { color: var(--verde-oscuro-2); font-size: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid var(--verde-muy-claro); padding-bottom: 10px; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: flex-end; }

        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 700; color: var(--verde-oscuro-1); }

        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }

        .btn { padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; font-size: 14px; text-decoration: none; }

        .btn-primary { background: var(--verde-oscuro-2); color: white; }
        .btn-secondary { background: #757575; color: white; }

        .table-container { overflow-x: auto; }
        .tabla { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        .tabla thead { background: var(--verde-oscuro-1); color: white; }
        .tabla th, .tabla td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }

        .estatus-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .estatus-activo { background: #c8e6c9; color: #2e7d32; }
        .estatus-inactivo { background: #ffcdd2; color: #c62828; }

        .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; color: white; margin-right: 3px; font-size: 13px; }
        
        .paginacion { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .page-link { padding: 8px 14px; border-radius: 4px; background: white; border: 1px solid #ddd; color: var(--verde-oscuro-2); text-decoration: none; }
        .page-link.active { background: var(--verde-oscuro-2); color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-user-graduate"></i> CECYTE - Gestión de Alumnos</h1>
        <div class="nav-links">
            <a href="main.php"><i class="fas fa-home"></i> Inicio</a>
            <a href="nuevo_alumno2.php" style="background: var(--verde-oscuro-2); color: white;"><i class="fas fa-plus"></i> Nuevo Alumno</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-search"></i> Filtros y Búsqueda</h2>
        <form method="GET" class="form-grid">
            <div class="form-group" style="grid-column: span 2;">
                <label>Búsqueda General</label>
                <input type="text" name="busqueda" placeholder="Nombre, matrícula o correo..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
            </div>
            <div class="form-group">
                <label>Carrera</label>
                <select name="carrera">
                    <option value="">Todas</option>
                    <?php foreach ($carreras as $c): ?>
                        <option value="<?= $c['id_carrera'] ?>" <?= $filtro_carrera == $c['id_carrera'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Estatus</label>
                <select name="activo">
                    <option value="">Todos</option>
                    <option value="Activo" <?= $filtro_estatus == 'Activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="Inactivo" <?= $filtro_estatus == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="gestion_alumnos.php" class="btn btn-secondary"><i class="fas fa-sync"></i></a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-container">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Nombre del Alumno</th>
                        <th>Carrera / Grupo</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alumnos as $al): ?>
                    <tr>
                        <td><strong>#<?= htmlspecialchars($al['matricula']) ?></strong></td>
                        <td><?= htmlspecialchars($al['nombre'] . ' ' . $al['apellido_paterno'] . ' ' . $al['apellido_materno']) ?></td>
                        <td>
                            <div><?= htmlspecialchars($al['carrera_nombre'] ?? 'N/A') ?></div>
                            <small style="color: var(--verde-oscuro-2); font-weight: bold;">[<?= htmlspecialchars($al['grupo_nombre'] ?? 'S/G') ?>]</small>
                        </td>
                        <td>
                            <span class="estatus-badge <?= ($al['activo'] == 'Activo') ? 'estatus-activo' : 'estatus-inactivo' ?>">
                                <?= $al['activo'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="ver_alumno2.php?matricula=<?= $al['matricula'] ?>" class="btn-action" style="background: #2196f3;" title="Ver"><i class="fas fa-eye"></i></a>
                            <a href="editar_alumnos2.php?matricula=<?= $al['matricula'] ?>" class="btn-action" style="background: #4caf50;" title="Editar"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_paginas > 1): ?>
            <div class="paginacion">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?page=<?= $i ?>&busqueda=<?= urlencode($filtro_busqueda) ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>