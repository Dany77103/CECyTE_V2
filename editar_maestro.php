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
$busqueda = '';
$filtro_genero = '';
$por_pagina = 10;
$pagina_actual = 1;

// Procesar parámetros de búsqueda
if (isset($_GET['busqueda'])) {
    $busqueda = trim($_GET['busqueda']);
}

if (isset($_GET['genero'])) {
    $filtro_genero = intval($_GET['genero']);
}

if (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) {
    $pagina_actual = intval($_GET['pagina']);
}

// Calcular offset para paginación
$offset = ($pagina_actual - 1) * $por_pagina;

try {
    // Construir consulta con filtros
    $sql = "SELECT m.*, g.genero 
            FROM maestros m 
            LEFT JOIN generos g ON m.id_genero = g.id_genero 
            WHERE 1=1";
    
    $params = [];
    
    // Aplicar búsqueda
    if (!empty($busqueda)) {
        $sql .= " AND (m.nombre LIKE :busqueda OR 
                      m.apellido_paterno LIKE :busqueda OR 
                      m.apellido_materno LIKE :busqueda OR 
                      m.numEmpleado LIKE :busqueda OR 
                      m.correo_institucional LIKE :busqueda)";
        $params[':busqueda'] = "%$busqueda%";
    }
    
    // Aplicar filtro de género
    if (!empty($filtro_genero)) {
        $sql .= " AND m.id_genero = :genero";
        $params[':genero'] = $filtro_genero;
    }
    
    // Contar total de registros para paginación
    $sql_count = "SELECT COUNT(*) as total FROM ($sql) as count_table";
    $stmt_count = $con->prepare($sql_count);
    
    foreach ($params as $key => $value) {
        $stmt_count->bindValue($key, $value);
    }
    
    $stmt_count->execute();
    $total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $por_pagina);
    
    // Añadir orden y límite para la consulta principal
    $sql .= " ORDER BY m.apellido_paterno, m.apellido_materno, m.nombre 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $con->prepare($sql);
    
    // Vincular parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener datos para filtros
    $sql_generos = "SELECT * FROM generos ORDER BY genero";
    $generos = $con->query($sql_generos)->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener maestros: " . $e->getMessage());
}

// Mensajes de éxito/error
$mensaje = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'created':
            $mensaje = '<div class="alert alert-success">Maestro creado exitosamente.</div>';
            break;
        case 'updated':
            $mensaje = '<div class="alert alert-success">Maestro actualizado exitosamente.</div>';
            break;
        case 'deleted':
            $mensaje = '<div class="alert alert-success">Maestro eliminado exitosamente.</div>';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'delete':
            $mensaje = '<div class="alert alert-danger">Error al eliminar el maestro.</div>';
            break;
        case 'not_found':
            $mensaje = '<div class="alert alert-danger">Maestro no encontrado.</div>';
            break;
        case 'numero_invalido':
            $mensaje = '<div class="alert alert-danger">Número de empleado inválido.</div>';
            break;
    }
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
        
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        
        .container-fluid {
            max-width: 1400px;
        }
        
        .page-header {
            background: linear-gradient(90deg, var(--verde-oscuro), var(--verde-principal));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .filtros-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        
        .btn-primary:hover {
            background-color: #1b5e20;
            border-color: #1b5e20;
        }
        
        .btn-success {
            background-color: var(--verde-medio);
            border-color: var(--verde-medio);
        }
        
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .table th {
            background-color: var(--verde-oscuro);
            color: white;
            border-color: var(--verde-oscuro);
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(139, 195, 74, 0.1);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(76, 175, 80, 0.2);
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 5px solid var(--verde-principal);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--verde-oscuro);
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .action-buttons .btn {
            padding: 5px 10px;
            margin: 2px;
        }
        
        .pagination .page-link {
            color: var(--verde-principal);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
            color: white;
        }
        
        @media (max-width: 768px) {
            .container-fluid {
                padding: 10px;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .action-buttons .btn {
                padding: 3px 6px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Encabezado -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class='bx bx-chalkboard'></i> Lista de Maestros</h1>
                    <p class="mb-0">Sistema de Gestión Académica CECyTE</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="registro.php" class="btn btn-light me-2">
                        <i class='bx bx-arrow-back'></i> Volver al Sistema
                    </a>
                    <a href="nuevo_maestro.php" class="btn btn-success">
                        <i class='bx bx-plus'></i> Nuevo Maestro
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Mensajes -->
        <?php echo $mensaje; ?>
        
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_registros; ?></div>
                    <div class="stats-label">Total de Maestros</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($maestros); ?></div>
                    <div class="stats-label">Mostrando</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_paginas; ?></div>
                    <div class="stats-label">Páginas</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $por_pagina; ?></div>
                    <div class="stats-label">Por Página</div>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filtros-container">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-6">
                    <label for="busqueda" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                           value="<?php echo htmlspecialchars($busqueda); ?>" 
                           placeholder="Nombre, apellido, número de empleado o email">
                </div>
                
                <div class="col-md-4">
                    <label for="genero" class="form-label">Género</label>
                    <select class="form-select" id="genero" name="genero">
                        <option value="">Todos los géneros</option>
                        <?php foreach ($generos as $genero): ?>
                            <option value="<?php echo $genero['id_genero']; ?>" 
                                <?php echo ($filtro_genero == $genero['id_genero']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($genero['genero']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class='bx bx-search'></i> Buscar
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Tabla de maestros -->
        <div class="table-container">
            <?php if (empty($maestros)): ?>
                <div class="alert alert-info text-center">
                    <i class='bx bx-info-circle'></i> No se encontraron maestros con los criterios de búsqueda.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>N° Empleado</th>
                                <th>Nombre Completo</th>
                                <th>Género</th>
                                <th>Email Institucional</th>
                                <th>Celular</th>
                                <th>Fecha Alta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $contador = $offset + 1; ?>
                            <?php foreach ($maestros as $maestro): ?>
                                <tr>
                                    <td><?php echo $contador++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($maestro['numEmpleado']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($maestro['apellido_paterno'] . ' ' . 
                                              ($maestro['apellido_materno'] ? $maestro['apellido_materno'] . ', ' : ', ') . 
                                              $maestro['nombre']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($maestro['genero'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($maestro['correo_institucional']); ?>">
                                            <?php echo htmlspecialchars($maestro['correo_institucional']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($maestro['telefono_celular'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($maestro['telefono_celular']); ?>">
                                                <?php echo htmlspecialchars($maestro['telefono_celular']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($maestro['fechaAlta'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="editar_maestro.php?numEmpleado=<?php echo urlencode($maestro['numEmpleado']); ?>" 
                                               class="btn btn-warning btn-sm" title="Editar">
                                                <i class='bx bx-edit'></i>
                                            </a>
                                            <a href="ver_maestro.php?numEmpleado=<?php echo urlencode($maestro['numEmpleado']); ?>" 
                                               class="btn btn-info btn-sm" title="Ver">
                                                <i class='bx bx-show'></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="confirmarEliminacion('<?php echo $maestro['numEmpleado']; ?>', 
                                                                                 '<?php echo addslashes($maestro['nombre'] . ' ' . $maestro['apellido_paterno']); ?>')"
                                                    title="Eliminar">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                            <a href="datos_academicos.php?numEmpleado=<?php echo urlencode($maestro['numEmpleado']); ?>" 
                                               class="btn btn-primary btn-sm" title="Datos Académicos">
                                                <i class='bx bx-book'></i>
                                            </a>
                                            <a href="datos_laborales.php?numEmpleado=<?php echo urlencode($maestro['numEmpleado']); ?>" 
                                               class="btn btn-secondary btn-sm" title="Datos Laborales">
                                                <i class='bx bx-briefcase'></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginación de maestros">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagina_actual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>">
                                        <i class='bx bx-chevron-left'></i> Anterior
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <?php if ($i == 1 || $i == $total_paginas || abs($i - $pagina_actual) <= 2): ?>
                                    <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                                        <a class="page-link" 
                                           href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php elseif (abs($i - $pagina_actual) == 3): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($pagina_actual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>">
                                        Siguiente <i class='bx bx-chevron-right'></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    
                    <div class="text-center text-muted mt-2">
                        Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?> 
                        | Mostrando <?php echo count($maestros); ?> de <?php echo $total_registros; ?> maestros
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
        
        <!-- Pie de página -->
        <div class="mt-4 text-center text-muted">
            <p>CECyTE SANTA CATARINA N.L. | Sistema de Gestión Académica</p>
            <p><?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class='bx bx-trash'></i> Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar al maestro <strong id="nombreMaestro"></strong>?</p>
                    <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
                    <p class="text-warning"><small>Nota: También se eliminarán sus datos académicos y laborales asociados.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="btnEliminarConfirmar" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>
<div class="d-flex justify-content-between mt-4">
    <a href="lista_maestros.php" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> Volver a la Lista
    </a>
    <div>
        <!-- Botones de exportación para maestro individual -->
        <a href="exportar_excel.php?numEmpleado=<?php echo urlencode($maestro['numEmpleado']); ?>" 
           class="btn btn-success me-2" title="Exportar a Excel">
            <i class='bx bx-file'></i> Excel
        </a>
        <a href="exportar_pdf.php?numEmpleado=<?php echo urlencode($maestro['numEmpleado']); ?>" 
           class="btn btn-danger me-2" title="Exportar a PDF">
            <i class='bx bxs-file-pdf'></i> PDF
        </a>
        <button type="submit" class="btn btn-primary">
            <i class='bx bx-save'></i> Guardar Cambios
        </button>
    </div>
</div>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Función para confirmar eliminación
        function confirmarEliminacion(numEmpleado, nombre) {
            const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
            document.getElementById('nombreMaestro').textContent = nombre;
            document.getElementById('btnEliminarConfirmar').href = 'eliminar_maestro.php?numEmpleado=' + encodeURIComponent(numEmpleado);
            modal.show();
        }
        
        // Función para limpiar filtros
        function limpiarFiltros() {
            window.location.href = 'lista_maestros.php';
        }
    </script>
</body>
</html> 