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
if ($filtro_estatus) $sql_total .= " AND a.activo = '" . $con->quote($filtro_estatus) . "'";
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
            --verde-primario: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #8bc34a;
            --verde-oscuro: #1a5330;
            --blanco: #ffffff;
            --fondo: #f4f7f6;
            --gris-texto: #555;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background-color: var(--fondo);
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container { max-width: 1400px; margin: 0 auto; }

        /* Header Modernizado */
        .header {
            background: var(--blanco);
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid var(--verde-primario);
        }

        .header h1 { color: var(--verde-oscuro); font-size: 1.6rem; display: flex; align-items: center; gap: 12px; }

        .nav-links { display: flex; gap: 10px; }
        .nav-links a {
            text-decoration: none; padding: 10px 15px; border-radius: 6px;
            font-size: 0.9rem; font-weight: 600; color: white;
            transition: 0.3s; display: flex; align-items: center; gap: 6px;
        }
        .link-home { background: var(--verde-oscuro); }
        .link-user { background: var(--verde-primario); }
        .link-prof { background: var(--verde-secundario); }
        .link-logout { background: #d32f2f; }
        .nav-links a:hover { transform: translateY(-2px); opacity: 0.9; }

        /* Tarjetas */
        .card {
            background: var(--blanco);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }

        .card h2 {
            color: var(--verde-oscuro);
            font-size: 1.3rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        /* Formulario de Filtros */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: flex-end;
        }

        .form-group label {
            display: block; margin-bottom: 6px;
            font-size: 0.85rem; font-weight: 700; color: var(--gris-texto);
        }

        .form-group select, .form-group input {
            width: 100%; padding: 10px; border: 1px solid #ccc;
            border-radius: 6px; font-size: 0.9rem;
        }

        .form-group select:focus, .form-group input:focus {
            border-color: var(--verde-primario); outline: none;
        }

        /* Botones */
        .btn {
            border: none; padding: 10px 20px; border-radius: 6px;
            cursor: pointer; font-weight: 600; transition: 0.3s;
            display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem;
            text-decoration: none;
        }
        .btn-search { background: var(--verde-primario); color: white; }
        .btn-clear { background: #eee; color: var(--gris-texto); }
        .btn-add { background: var(--verde-secundario); color: white; margin-bottom: 20px; }
        .btn:hover { filter: brightness(1.1); }

        /* Tabla Estilizada */
        .table-responsive { overflow-x: auto; margin-top: 10px; }
        .tabla {
            width: 100%; border-collapse: collapse; min-width: 1000px;
        }
        .tabla thead { background: var(--verde-primario); color: white; }
        .tabla th { padding: 15px; text-align: left; font-size: 0.85rem; text-transform: uppercase; }
        .tabla td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; color: #333; }
        .tabla tr:hover { background-color: #f9f9f9; }

        /* Badges */
        .badge {
            padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;
        }
        .badge-activo { background: #c8e6c9; color: #2e7d32; }
        .badge-inactivo { background: #ffcdd2; color: #c62828; }
        .badge-grupo { background: #e3f2fd; color: #1565c0; }

        /* Acciones */
        .btn-action {
            width: 32px; height: 32px; display: inline-flex;
            align-items: center; justify-content: center; border-radius: 4px;
            color: white; font-size: 0.8rem; margin-right: 2px;
        }
        .bg-view { background: #0288d1; }
        .bg-edit { background: #388e3c; }
        .bg-grade { background: #f57c00; }
        .bg-date { background: #7b1fa2; }

        /* Paginación */
        .paginacion { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .page-link {
            padding: 8px 14px; border-radius: 4px; background: white;
            border: 1px solid #ddd; color: var(--verde-primario); text-decoration: none;
        }
        .page-link.active { background: var(--verde-primario); color: white; border-color: var(--verde-primario); }

        @media (max-width: 900px) {
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .nav-links { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="container">
    <header class="header">
        <h1><i class="fas fa-graduation-cap"></i> CECYTE Gestión</h1>
        <nav class="nav-links">
            <a href="main.php" class="link-home"><i class="fas fa-home"></i> Inicio</a>
            <a href="gestion_usuarios.php" class="link-user"><i class="fas fa-user-cog"></i> Usuarios</a>
            <a href="gestion_maestros.php" class="link-prof"><i class="fas fa-chalkboard-teacher"></i> Maestros</a>
            <a href="logout.php" class="link-logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </nav>
    </header>

    <section class="card">
        <h2><i class="fas fa-search"></i> Panel de Búsqueda</h2>
        <form method="GET" id="filterForm">
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Búsqueda rápida</label>
                    <input type="text" name="busqueda" placeholder="Nombre, matrícula o correo..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                </div>
                <div class="form-group">
                    <label>Carrera</label>
                    <select name="carrera" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?= $c['id_carrera'] ?>" <?= $filtro_carrera == $c['id_carrera'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Grupo</label>
                    <select name="grupo" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <?php foreach ($grupos as $g): ?>
                            <option value="<?= $g['id_grupo'] ?>" <?= $filtro_grupo == $g['id_grupo'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estatus</label>
                    <select name="activo" onchange="this.form.submit()">
                        <option value="">Cualquiera</option>
                        <option value="Activo" <?= $filtro_estatus == 'Activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="Inactivo" <?= $filtro_estatus == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-search"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="gestion_alumnos.php" class="btn btn-clear">Limpiar</a>
                </div>
            </div>
        </form>
    </section>

    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2><i class="fas fa-list-ul"></i> Listado de Alumnos (<?= $total_alumnos ?>)</h2>
            <a href="nuevo_alumno2.php" class="btn btn-add"><i class="fas fa-plus"></i> Registrar Alumno</a>
        </div>

        <?php if (count($alumnos) > 0): ?>
            <div class="table-responsive">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Nombre Completo</th>
                            <th>Carrera / Grupo</th>
                            <th>Contacto</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos as $al): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($al['matricula']) ?></strong></td>
                            <td>
                                <?= htmlspecialchars($al['apellido_paterno'] . ' ' . $al['apellido_materno'] . ' ' . $al['nombre']) ?>
                            </td>
                            <td>
                                <div style="font-size: 0.8rem;"><?= htmlspecialchars($al['carrera_nombre'] ?? 'Sin Carrera') ?></div>
                                <span class="badge badge-grupo"><?= htmlspecialchars($al['grupo_nombre'] ?? 'S/G') ?></span>
                            </td>
                            <td>
                                <div style="font-size: 0.8rem; color: var(--verde-primario);">
                                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($al['correo_institucional']) ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--gris-texto);">
                                    <i class="fas fa-phone"></i> <?= htmlspecialchars($al['telefono_celular']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $al['activo'] === 'Activo' ? 'badge-activo' : 'badge-inactivo' ?>">
                                    <?= htmlspecialchars($al['activo']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="ver_alumno2.php?matricula=<?= $al['matricula'] ?>" class="btn-action bg-view" title="Ver"><i class="fas fa-eye"></i></a>
                                <a href="editar_alumnos2.php?matricula=<?= $al['matricula'] ?>" class="btn-action bg-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                <a href="calificaciones_alumno.php?matricula=<?= $al['matricula'] ?>" class="btn-action bg-grade" title="Calificaciones"><i class="fas fa-star"></i></a>
                                <a href="asistencias_alumno.php?matricula=<?= $al['matricula'] ?>" class="btn-action bg-date" title="Asistencias"><i class="fas fa-calendar-alt"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div class="paginacion">
                    <?php for ($i = max(1, $page - 3); $i <= min($total_paginas, $page + 3); $i++): ?>
                        <a href="?page=<?= $i ?>&busqueda=<?= urlencode($filtro_busqueda) ?>&carrera=<?= $filtro_carrera ?>&grupo=<?= $filtro_grupo ?>&activo=<?= $filtro_estatus ?>" 
                           class="page-link <?= $i == $page ? 'active' : '' ?>">
                           <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-search-minus" style="font-size: 3rem; margin-bottom: 10px;"></i>
                <p>No se encontraron resultados para los filtros aplicados.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

</body>
</html>