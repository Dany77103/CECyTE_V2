<?php
// lista_maestros.php
session_start();
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Variables para búsqueda y filtros
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_genero = isset($_GET['genero']) ? intval($_GET['genero']) : '';
$filtro_estatus = isset($_GET['estatus']) ? intval($_GET['estatus']) : '';
$por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? intval($_GET['pagina']) : 1;

$offset = ($pagina_actual - 1) * $por_pagina;

try {
    // Consulta base
    $sql = "SELECT m.*, g.genero, 
                   (SELECT tipoEstatus FROM estatus WHERE id_estatus = dl.id_estatus) as estatus_laboral
            FROM maestros m 
            LEFT JOIN generos g ON m.id_genero = g.id_genero 
            LEFT JOIN datoslaboralesmaestros dl ON m.numEmpleado = dl.numEmpleado
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($busqueda)) {
        $sql .= " AND (m.nombre LIKE :busqueda OR m.apellido_paterno LIKE :busqueda OR m.apellido_materno LIKE :busqueda OR m.numEmpleado LIKE :busqueda)";
        $params[':busqueda'] = "%$busqueda%";
    }
    
    if (!empty($filtro_genero)) {
        $sql .= " AND m.id_genero = :genero";
        $params[':genero'] = $filtro_genero;
    }

    // Paginación y ejecución
    $stmt_count = $con->prepare("SELECT COUNT(*) as total FROM ($sql) as count_table");
    foreach ($params as $key => $value) { $stmt_count->bindValue($key, $value); }
    $stmt_count->execute();
    $total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $por_pagina);

    $sql .= " ORDER BY m.apellido_paterno ASC LIMIT :limit OFFSET :offset";
    $stmt = $con->prepare($sql);
    foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }
    $stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Datos para selectores
    $generos = $con->query("SELECT * FROM generos ORDER BY genero")->fetchAll(PDO::FETCH_ASSOC);
    $estatus_list = $con->query("SELECT * FROM estatus ORDER BY tipoEstatus")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Lógica de alertas
$mensaje = '';
if (isset($_GET['success'])) {
    $mensaje = '<div class="alert alert-success alert-dismissible fade show"><i class="bx bx-check-circle"></i> Acción realizada con éxito. <button type="button" class="btn-close" data-bs-dismiss="dismiss"></button></div>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Maestros - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --verde-oscuro: #1a5330;
            --verde-principal: #2e7d32;
            --verde-medio: #4caf50;
            --verde-claro: #8bc34a;
        }
        
        body { background-color: #f8f9fa; }
        
        .page-header {
            background: linear-gradient(90deg, var(--verde-oscuro), var(--verde-principal));
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 5px solid var(--verde-principal);
            transition: transform 0.3s;
        }

        .stats-card:hover { transform: translateY(-5px); }
        
        .stats-number { font-size: 1.8rem; font-weight: 700; color: var(--verde-oscuro); }
        
        .filtros-container, .table-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .table thead {
            background-color: var(--verde-oscuro);
            color: white;
        }

        .btn-primary { background-color: var(--verde-principal); border: none; }
        .btn-primary:hover { background-color: var(--verde-oscuro); }

        .badge-status { padding: 6px 12px; border-radius: 50px; font-size: 0.75rem; }
        .badge-active { background-color: #d4edda; color: #155724; }
        .badge-inactive { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class='bx bx-chalkboard'></i> Gestión de Maestros</h1>
                <p class="mb-0 opacity-75">Panel de administración de personal docente CECyTE</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="main.php" class="btn btn-light btn-sm"><i class='bx bx-home'></i> Inicio</a>
                <a href="nuevo_maestro.php" class="btn btn-success btn-sm"><i class='bx bx-plus'></i> Agregar</a>
            </div>
        </div>
    </div>

    <?php echo $mensaje; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stats-card">
                <div class="stats-number"><?php echo $total_registros; ?></div>
                <div class="text-muted small uppercase">Total Docentes</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stats-card">
                <div class="stats-number"><?php echo count($maestros); ?></div>
                <div class="text-muted small">En Vista</div>
            </div>
        </div>
    </div>

    <div class="filtros-container">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class='bx bx-search'></i></span>
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre o nómina..." value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="genero" class="form-select">
                    <option value="">Género: Todos</option>
                    <?php foreach($generos as $g): ?>
                        <option value="<?php echo $g['id_genero']; ?>" <?php if($filtro_genero == $g['id_genero']) echo 'selected'; ?>><?php echo $g['genero']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
            <div class="col-md-2">
                <a href="lista_maestros.php" class="btn btn-outline-secondary w-100">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nómina</th>
                        <th>Nombre del Maestro</th>
                        <th>Email</th>
                        <th>Estatus</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($maestros)): ?>
                        <tr><td colspan="6" class="text-center py-4">No se encontraron resultados</td></tr>
                    <?php else: ?>
                        <?php foreach($maestros as $m): ?>
                        <tr>
                            <td><?php echo $m['id_maestro']; ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $m['numEmpleado']; ?></span></td>
                            <td>
                                <strong><?php echo htmlspecialchars($m['apellido_paterno'] . ' ' . $m['nombre']); ?></strong>
                            </td>
                            <td><small><?php echo htmlspecialchars($m['correo_institucional']); ?></small></td>
                            <td>
                                <?php 
                                $status_label = $m['estatus_laboral'] ?? 'Desconocido';
                                $class = (strpos(strtolower($status_label), 'activo') !== false) ? 'badge-active' : 'badge-inactive';
                                ?>
                                <span class="badge-status <?php echo $class; ?>"><?php echo $status_label; ?></span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm">
                                    <a href="ver_maestro.php?id=<?php echo $m['numEmpleado']; ?>" class="btn btn-white btn-sm text-primary"><i class='bx bx-show'></i></a>
                                    <a href="editar_maestro.php?id=<?php echo $m['numEmpleado']; ?>" class="btn btn-white btn-sm text-warning"><i class='bx bx-edit'></i></a>
                                    <button onclick="confirmarEliminacion('<?php echo $m['numEmpleado']; ?>')" class="btn btn-white btn-sm text-danger"><i class='bx bx-trash'></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_paginas > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for($i=1; $i<=$total_paginas; $i++): ?>
                    <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?>&busqueda=<?php echo $busqueda; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmarEliminacion(id) {
    if(confirm('¿Está seguro de eliminar este registro? Esta acción es irreversible.')) {
        window.location.href = 'eliminar_maestro.php?numEmpleado=' + id;
    }
}
</script>
</body>
</html>