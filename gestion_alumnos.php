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
    <title>Gestión de Alumnos | CECyTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --secondary: #6c757d;
            --bg: #f4f6f9;
            --white: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { 
            background-color: var(--bg); 
            font-family: 'Inter', sans-serif; 
            color: #333;
            padding-top: 90px;
        }

        /* --- NAVBAR FIJA --- */
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
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand {
            display: flex; align-items: center; gap: 15px; text-decoration: none;
        }

        .navbar-brand img { height: 45px; width: auto; }
        .navbar-brand span { font-weight: 700; color: var(--primary); font-size: 1.2rem; }

        .nav-actions { display: flex; gap: 10px; }

        /* --- CONTENEDOR --- */
        .container { max-width: 1300px; margin: 0 auto; padding: 0 20px 40px; }

        .card { 
            background: var(--white); border-radius: 20px; padding: 25px; 
            margin-bottom: 25px; box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.02);
        }

        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;
        }

        .card-header h2 { font-size: 1.25rem; color: var(--primary); display: flex; align-items: center; gap: 10px; }

        /* --- FILTROS --- */
        .form-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
            gap: 15px; align-items: flex-end; 
        }

        .form-group label { display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: var(--secondary); }
        .form-group input, .form-group select { 
            width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 0.9rem; outline: none;
        }

        /* --- TABLA --- */
        .table-responsive { overflow-x: auto; }
        .tabla { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla th { 
            text-align: left; padding: 15px; color: var(--secondary); 
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 2px solid #eee; background: #f8f9fa;
        }
        .tabla td { padding: 15px; border-bottom: 1px solid #f1f1f1; font-size: 0.9rem; }
        .tabla tr:hover { background: #fafafa; }

        /* --- COMPONENTES --- */
        .btn { 
            padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; 
            font-weight: 600; font-size: 0.85rem; display: inline-flex; 
            align-items: center; gap: 8px; transition: var(--transition); text-decoration: none; 
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
        .btn-secondary { background: #e9ecef; color: var(--secondary); }
        
        .estatus-badge { padding: 5px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .estatus-activo { background: #d4edda; color: #155724; }
        .estatus-inactivo { background: #f8d7da; color: #721c24; }

        .btn-action { 
            width: 35px; height: 35px; display: inline-flex; align-items: center; 
            justify-content: center; border-radius: 8px; color: white; text-decoration: none;
        }

        /* PAGINACION */
        .paginacion { display: flex; justify-content: center; gap: 8px; margin-top: 25px; }
        .page-link { 
            padding: 8px 16px; border-radius: 8px; background: var(--white); 
            color: var(--primary); text-decoration: none; font-weight: 600;
            box-shadow: var(--shadow-sm); transition: var(--transition);
        }
        .page-link.active { background: var(--primary); color: white; }
        .page-link:hover:not(.active) { background: #f0f0f0; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo_cecyte.jpg" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div class="nav-actions">
            <a href="main.php" class="btn btn-secondary"><i class="fas fa-home"></i></a>
            <a href="logout.php" class="btn btn-secondary" style="color: #dc3545;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-search"></i> Filtros de Alumnos</h2>
                <a href="nuevo_alumno2.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Alumno</a>
            </div>
            <form method="GET" class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Búsqueda General</label>
                    <input type="text" name="busqueda" placeholder="Matrícula, nombre o correo..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                </div>
                <div class="form-group">
                    <label>Carrera</label>
                    <select name="carrera">
                        <option value="">Todas las carreras</option>
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
                <div class="form-group" style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="gestion_alumnos.php" class="btn btn-secondary"><i class="fas fa-sync"></i></a>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list-ul"></i> Listado de Estudiantes</h2>
                <span style="font-size: 0.85rem; color: var(--secondary); font-weight: 500;">Total: <?= $total_alumnos ?> alumnos</span>
            </div>
            <div class="table-responsive">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Nombre del Alumno</th>
                            <th>Carrera / Grupo</th>
                            <th>Estatus</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($alumnos)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--secondary);">No se encontraron resultados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($alumnos as $al): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--primary);">#<?= htmlspecialchars($al['matricula']) ?></td>
                                <td style="font-weight: 500;"><?= htmlspecialchars($al['nombre'] . ' ' . $al['apellido_paterno'] . ' ' . $al['apellido_materno']) ?></td>
                                <td>
                                    <div style="font-size: 0.85rem; color: #444;"><?= htmlspecialchars($al['carrera_nombre'] ?? 'N/A') ?></div>
                                    <span style="font-size: 0.75rem; color: var(--primary-light); font-weight: 700;">GRUPO: <?= htmlspecialchars($al['grupo_nombre'] ?? 'S/G') ?></span>
                                </td>
                                <td>
                                    <span class="estatus-badge <?= ($al['activo'] == 'Activo') ? 'estatus-activo' : 'estatus-inactivo' ?>">
                                        <?= $al['activo'] ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="ver_alumno2.php?matricula=<?= $al['matricula'] ?>" class="btn-action" style="background: #3b82f6;" title="Ver"><i class="fas fa-eye"></i></a>
                                    <a href="editar_alumnos2.php?matricula=<?= $al['matricula'] ?>" class="btn-action" style="background: #10b981; margin-left: 5px;" title="Editar"><i class="fas fa-edit"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div class="paginacion">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?page=<?= $i ?>&busqueda=<?= urlencode($filtro_busqueda) ?>&carrera=<?= $filtro_carrera ?>&activo=<?= $filtro_estatus ?>" 
                           class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>