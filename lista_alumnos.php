<?php
// lista_alumnos.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Lógica de búsqueda y filtrado (Mantenida del original)
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_genero = isset($_GET['genero']) ? intval($_GET['genero']) : '';
$filtro_discapacidad = isset($_GET['discapacidad']) ? intval($_GET['discapacidad']) : '';
$por_pagina = 10;
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

try {
    $sql = "SELECT a.*, g.genero, d.tipo_discapacidad 
            FROM alumnos a 
            LEFT JOIN generos g ON a.id_genero = g.id_genero 
            LEFT JOIN discapacidades d ON a.id_discapacidad = d.id_discapacidad 
            WHERE 1=1";
    
    $params = [];
    if (!empty($busqueda)) {
        $sql .= " AND (a.nombre LIKE :busqueda OR a.apellido_paterno LIKE :busqueda OR a.apellido_materno LIKE :busqueda OR a.matricula LIKE :busqueda)";
        $params[':busqueda'] = "%$busqueda%";
    }
    
    // ... (resto de la lógica de filtros y conteo que ya tenías)
    $stmt_count = $con->prepare("SELECT COUNT(*) as total FROM ($sql) as t");
    foreach ($params as $k => $v) $stmt_count->bindValue($k, $v);
    $stmt_count->execute();
    $total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $por_pagina);

    $sql .= " ORDER BY a.apellido_paterno LIMIT :limit OFFSET :offset";
    $stmt = $con->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $generos = $con->query("SELECT * FROM generos")->fetchAll(PDO::FETCH_ASSOC);
    $discapacidades = $con->query("SELECT * FROM discapacidades")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

$mensaje = ''; // Aquí iría tu lógica de alertas (created, updated, etc.)
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Alumnos - CECyTE</title>
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

        /* Estilo idéntico al Dashboard Principal */
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
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 5px solid var(--verde-principal);
            transition: transform 0.3s;
        }

        .stats-number { font-size: 1.8rem; font-weight: 700; color: var(--verde-oscuro); }
        .stats-label { color: #666; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }

        .content-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }

        .table th {
            background-color: var(--verde-oscuro);
            color: white;
            font-weight: 500;
        }

        .btn-primary { background-color: var(--verde-principal); border: none; }
        .btn-primary:hover { background-color: var(--verde-oscuro); }
        
        .avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--verde-claro); }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-6 fw-bold mb-0"><i class='bx bxs-user-detail'></i> Gestión de Alumnos</h1>
                <p class="opacity-75 mb-0">Panel de administración académica CECyTE</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="main.php" class="btn btn-light rounded-pill px-4 me-2">
                    <i class='bx bx-home-alt'></i> Panel Principal
                </a>
                <a href="nuevo_alumno.php" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">
                    <i class='bx bx-plus-circle'></i> Nuevo Alumno
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-4 text-center">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number"><?php echo $total_registros; ?></div>
                <div class="stats-label">Alumnos Registrados</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="border-left-color: var(--verde-claro);">
                <div class="stats-number"><?php echo count($alumnos); ?></div>
                <div class="stats-label">Registros en Vista</div>
            </div>
        </div>
    </div>

    <div class="content-container mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-bold">Búsqueda Rápida</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class='bx bx-search'></i></span>
                    <input type="text" class="form-control bg-light border-0" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Matrícula o Nombre...">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Género</label>
                <select class="form-select bg-light border-0" name="genero">
                    <option value="">Todos</option>
                    <?php foreach($generos as $g): ?>
                        <option value="<?php echo $g['id_genero']; ?>" <?php echo ($filtro_genero == $g['id_genero'])?'selected':''; ?>><?php echo $g['genero']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100 fw-bold">Aplicar</button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <a href="lista_alumnos.php" class="btn btn-outline-secondary w-100">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="content-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Perfil</th>
                        <th>Matrícula</th>
                        <th>Nombre Completo</th>
                        <th>Contacto</th>
                        <th>Discapacidad</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alumnos as $alumno): ?>
                    <tr>
                        <td>
                            <?php if (!empty($alumno['rutaImagen'])): ?>
                                <img src="<?php echo htmlspecialchars($alumno['rutaImagen']); ?>" class="avatar">
                            <?php else: ?>
                                <div class="avatar bg-light d-flex align-items-center justify-content-center">
                                    <i class='bx bx-user text-muted'></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-success-subtle text-success fw-bold"><?php echo $alumno['matricula']; ?></span></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'] . ', ' . $alumno['nombre']); ?></td>
                        <td>
                            <small class="d-block"><i class='bx bx-envelope'></i> <?php echo $alumno['correo_institucional']; ?></small>
                            <small class="text-muted"><i class='bx bx-phone'></i> <?php echo $alumno['telefono_celular'] ?? 'N/A'; ?></small>
                        </td>
                        <td>
                            <?php echo !empty($alumno['tipo_discapacidad']) 
                                ? '<span class="badge bg-warning text-dark">'.$alumno['tipo_discapacidad'].'</span>' 
                                : '<span class="text-muted small">Ninguna</span>'; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group border rounded-pill overflow-hidden">
                                <a href="editar_alumnos.php?matricula=<?php echo $alumno['matricula']; ?>" class="btn btn-white btn-sm text-warning" title="Editar"><i class='bx bxs-edit fs-5'></i></a>
                                <a href="qr_alumno.php?matricula=<?php echo $alumno['matricula']; ?>" class="btn btn-white btn-sm text-primary" title="QR"><i class='bx bx-qr fs-5'></i></a>
                                <button onclick="confirmarEliminacion('<?php echo $alumno['matricula']; ?>', '<?php echo addslashes($alumno['nombre']); ?>')" class="btn btn-white btn-sm text-danger"><i class='bx bxs-trash fs-5'></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_paginas > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($pagina_actual <= 1)?'disabled':''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual-1])); ?>">Anterior</a>
                </li>
                <li class="page-item active"><a class="page-link"><?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></a></li>
                <li class="page-item <?php echo ($pagina_actual >= $total_paginas)?'disabled':''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual+1])); ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>