<?php
session_start();

// Verificar permisos
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
$filtro_estado = $_GET['estado'] ?? '';
$filtro_especialidad = $_GET['especialidad'] ?? '';
$filtro_estatus = $_GET['activo'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Obtener maestros con filtros
$sql = "SELECT m.*, 
               CONCAT(m.apellido_paterno, ' ', m.apellido_materno, ' ', m.nombre) as nombre_completo,
               (SELECT GROUP_CONCAT(DISTINCT hm.id_materia) 
                 FROM horarios_maestros hm 
                 WHERE hm.id_maestro = m.id_maestro AND hm.estatus = 'Activo') as materias_ids,
                (SELECT GROUP_CONCAT(DISTINCT g.nombre) 
                 FROM horarios_maestros hm 
                 JOIN grupos g ON hm.id_grupo = g.id_grupo 
                 WHERE hm.id_maestro = m.id_maestro AND hm.estatus = 'Activo') as grupos_asignados
        FROM maestros m 
        WHERE 1=1";

$params = [];

if ($filtro_estado) {
    $sql .= " AND m.id_estado = :estado";
    $params['estado'] = $filtro_estado;
}

if ($filtro_especialidad) {
    $sql .= " AND m.especialidad LIKE :especialidad";
    $params['especialidad'] = "%$filtro_especialidad%";
}

if ($filtro_estatus) {
    $sql .= " AND m.activo = :activo";
    $params['activo'] = $filtro_estatus;
}

if ($filtro_busqueda) {
    $sql .= " AND (m.numEmpleado LIKE :busqueda OR 
                    m.nombre LIKE :busqueda OR 
                    m.apellido_paterno LIKE :busqueda OR 
                    m.apellido_materno LIKE :busqueda OR
                    m.correo_institucional LIKE :busqueda)";
    $params['busqueda'] = "%$filtro_busqueda%";
}

$sql .= " ORDER BY m.apellido_paterno, m.apellido_materno, m.nombre 
          LIMIT :limit OFFSET :offset";

$stmt = $con->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener total para paginación
$sql_total = "SELECT COUNT(*) as total FROM maestros m WHERE 1=1";
if ($filtro_estado) $sql_total .= " AND m.id_estado = " . (int)$filtro_estado;
if ($filtro_especialidad) $sql_total .= " AND m.especialidad LIKE '%" . $con->quote($filtro_especialidad) . "%'";
if ($filtro_estatus) $sql_total .= " AND m.activo = " . $con->quote($filtro_estatus);

$total_maestros = $con->query($sql_total)->fetchColumn();
$total_paginas = ceil($total_maestros / $limit);

$estados = $con->query("SELECT id_estado, estado FROM estados ORDER BY estado")->fetchAll();
$especialidades = $con->query("SELECT DISTINCT especialidad FROM maestros WHERE especialidad IS NOT NULL AND especialidad != '' ORDER BY especialidad")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Maestros | CECyTE</title>
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
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }

        .navbar-brand img { height: 45px; width: auto; }

        .navbar-brand span {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.2rem;
        }

        .nav-actions { display: flex; gap: 10px; }

        /* --- CONTENEDOR --- */
        .container { max-width: 1300px; margin: 0 auto; padding: 0 20px 40px; }

        .card { 
            background: var(--white); 
            border-radius: 20px; 
            padding: 25px; 
            margin-bottom: 25px; 
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.02);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .card-header h2 { 
            font-size: 1.25rem; 
            color: var(--primary); 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }

        /* --- FORMULARIO FILTROS --- */
        .form-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
            align-items: flex-end; 
        }

        .form-group label { 
            display: block; 
            margin-bottom: 6px; 
            font-size: 0.85rem; 
            font-weight: 600; 
            color: var(--secondary); 
        }

        .form-group input, .form-group select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #e0e0e0; 
            border-radius: 8px; 
            font-size: 0.9rem;
            outline: none;
            transition: border 0.3s;
        }

        .form-group input:focus { border-color: var(--primary-light); }

        /* --- TABLA --- */
        .table-responsive { overflow-x: auto; }
        .tabla { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla thead { background: #f8f9fa; }
        .tabla th { 
            text-align: left; 
            padding: 15px; 
            color: var(--secondary); 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 1px;
            border-bottom: 2px solid #eee;
        }
        .tabla td { padding: 15px; border-bottom: 1px solid #f1f1f1; font-size: 0.9rem; }
        .tabla tr:hover { background: #fafafa; }

        /* --- BOTONES --- */
        .btn { 
            padding: 10px 20px; 
            border-radius: 8px; 
            border: none; 
            cursor: pointer; 
            font-weight: 600; 
            font-size: 0.85rem; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            transition: var(--transition); 
            text-decoration: none; 
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
        .btn-secondary { background: #e9ecef; color: var(--secondary); }
        .btn-secondary:hover { background: #dee2e6; }
        
        .btn-action { 
            width: 35px; 
            height: 35px; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 8px; 
            color: white; 
            margin-right: 5px; 
            text-decoration: none;
            transition: transform 0.2s;
        }
        .btn-action:hover { transform: scale(1.1); }

        /* --- BADGES --- */
        .estatus-badge { 
            padding: 5px 12px; 
            border-radius: 6px; 
            font-size: 0.75rem; 
            font-weight: 700; 
        }
        .estatus-activo { background: #d4edda; color: #155724; }
        .estatus-inactivo { background: #f8d7da; color: #721c24; }

        @media (max-width: 768px) {
            .navbar-brand span { display: none; }
            .card-header { flex-direction: column; gap: 15px; align-items: flex-start; }
        }
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
                <h2><i class="fas fa-filter"></i> Filtros de Búsqueda</h2>
                <a href="nuevo_maestro2.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Maestro</a>
            </div>
            <form method="GET" class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Búsqueda General</label>
                    <input type="text" name="busqueda" placeholder="Nombre, número de empleado o correo..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                </div>
                <div class="form-group">
                    <label>Especialidad</label>
                    <select name="especialidad">
                        <option value="">Todas las especialidades</option>
                        <?php foreach ($especialidades as $esp): ?>
                            <option value="<?php echo $esp['especialidad']; ?>" <?php echo ($filtro_especialidad == $esp['especialidad']) ? 'selected' : ''; ?>>
                                <?php echo $esp['especialidad']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;"><i class="fas fa-search"></i> Buscar</button>
                    <a href="gestion_maestros.php" class="btn btn-secondary" title="Limpiar Filtros"><i class="fas fa-sync"></i></a>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Listado de Docentes</h2>
                <span style="font-size: 0.8rem; color: var(--secondary); font-weight: 500;">Mostrando <?php echo count($maestros); ?> maestros</span>
            </div>
            <div class="table-responsive">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>N° Empleado</th>
                            <th>Nombre Completo</th>
                            <th>Especialidad</th>
                            <th>Estado</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($maestros)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--secondary);">No se encontraron maestros con los filtros seleccionados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($maestros as $m): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--primary);">#<?php echo $m['numEmpleado']; ?></td>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($m['nombre_completo']); ?></td>
                                <td><span style="color: var(--secondary);"><?php echo $m['especialidad'] ?: 'No asignada'; ?></span></td>
                                <td>
                                    <span class="estatus-badge <?php echo ($m['activo'] == 'Activo') ? 'estatus-activo' : 'estatus-inactivo'; ?>">
                                        <?php echo $m['activo']; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="ver_maestro2.php?numEmpleado=<?php echo $m['numEmpleado']; ?>" class="btn-action" style="background: #3b82f6;" title="Ver Detalles"><i class="fas fa-eye"></i></a>
                                    <a href="editar_maestro2.php?numEmpleado=<?php echo $m['numEmpleado']; ?>" class="btn-action" style="background: #10b981;" title="Editar"><i class="fas fa-edit"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>