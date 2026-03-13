<?php
// lista_alumnos.php
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
$filtro_discapacidad = '';
$por_pagina = 10;
$pagina_actual = 1;

// Procesar parámetros de búsqueda
if (isset($_GET['busqueda'])) {
    $busqueda = trim($_GET['busqueda']);
}

if (isset($_GET['genero'])) {
    $filtro_genero = intval($_GET['genero']);
}

if (isset($_GET['discapacidad'])) {
    $filtro_discapacidad = intval($_GET['discapacidad']);
}

if (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) {
    $pagina_actual = intval($_GET['pagina']);
}

// Calcular offset para paginación
$offset = ($pagina_actual - 1) * $por_pagina;

try {
    // Construir consulta con filtros
    $sql = "SELECT a.*, g.genero, d.tipo_discapacidad 
            FROM alumnos a 
            LEFT JOIN generos g ON a.id_genero = g.id_genero 
            LEFT JOIN discapacidades d ON a.id_discapacidad = d.id_discapacidad 
            WHERE 1=1";
    
    $params = [];
    
    // Aplicar búsqueda
    if (!empty($busqueda)) {
        $sql .= " AND (a.nombre LIKE :busqueda OR 
                      a.apellido_paterno LIKE :busqueda OR 
                      a.apellido_materno LIKE :busqueda OR 
                      a.matricula LIKE :busqueda OR 
                      a.correo_institucional LIKE :busqueda)";
        $params[':busqueda'] = "%$busqueda%";
    }
    
    // Aplicar filtro de género
    if (!empty($filtro_genero)) {
        $sql .= " AND a.id_genero = :genero";
        $params[':genero'] = $filtro_genero;
    }
    
    // Aplicar filtro de discapacidad
    if (!empty($filtro_discapacidad)) {
        $sql .= " AND a.id_discapacidad = :discapacidad";
        $params[':discapacidad'] = $filtro_discapacidad;
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
    $sql .= " ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $con->prepare($sql);
    
    // Vincular parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener datos para filtros
    $sql_generos = "SELECT * FROM generos ORDER BY genero";
    $generos = $con->query($sql_generos)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_discapacidades = "SELECT * FROM discapacidades ORDER BY tipo_discapacidad";
    $discapacidades = $con->query($sql_discapacidades)->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener alumnos: " . $e->getMessage());
}

// Mensajes de éxito/error
$mensaje = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'created':
            $mensaje = '<div class="alert alert-success">Alumno creado exitosamente.</div>';
            break;
        case 'updated':
            $mensaje = '<div class="alert alert-success">Alumno actualizado exitosamente.</div>';
            break;
        case 'deleted':
            $mensaje = '<div class="alert alert-success">Alumno eliminado exitosamente.</div>';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'delete':
            $mensaje = '<div class="alert alert-danger">Error al eliminar el alumno.</div>';
            break;
        case 'not_found':
            $mensaje = '<div class="alert alert-danger">Alumno no encontrado.</div>';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Alumnos - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
        
        .btn-warning {
            background-color: var(--verde-claro);
            border-color: var(--verde-claro);
            color: #333;
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
        
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background-color: #f8d7da;
            color: #721c24;
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
        
        .pagination .page-link:hover {
            background-color: #e8f5e9;
            color: var(--verde-oscuro);
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
        
        .export-buttons {
            margin-bottom: 20px;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--verde-medio);
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
                    <h1><i class='bx bx-user'></i> Lista de Alumnos</h1>
                    <p class="mb-0">Sistema de Gesti&oacute;n Acad&eacute;mica CECyTE</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="main.php" class="btn btn-light me-2">
                        <i class='bx bx-arrow-back'></i> Volver al Sistema
                    </a>
                    <a href="nuevo_alumno.php" class="btn btn-success">
                        <i class='bx bx-plus'></i> Nuevo Alumno
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
                    <div class="stats-label">Total de Alumnos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($alumnos); ?></div>
                    <div class="stats-label">Mostrando</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_paginas; ?></div>
                    <div class="stats-label">P&aacute;ginas</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $por_pagina; ?></div>
                    <div class="stats-label">Por P&aacute;gina</div>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filtros-container">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="busqueda" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                           value="<?php echo htmlspecialchars($busqueda); ?>" 
                           placeholder="Nombre, apellido, matr&iacute;cula o email">
                </div>
                
                <div class="col-md-3">
                    <label for="genero" class="form-label">G&eacute;nero</label>
                    <select class="form-select" id="genero" name="genero">
                        <option value="">Todos los g&eacute;neros</option>
                        <?php foreach ($generos as $genero): ?>
                            <option value="<?php echo $genero['id_genero']; ?>" 
                                <?php echo ($filtro_genero == $genero['id_genero']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($genero['genero']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="discapacidad" class="form-label">Discapacidad</label>
                    <select class="form-select" id="discapacidad" name="discapacidad">
                        <option value="">Todas las discapacidades</option>
                        <?php foreach ($discapacidades as $discapacidad): ?>
                            <option value="<?php echo $discapacidad['id_discapacidad']; ?>" 
                                <?php echo ($filtro_discapacidad == $discapacidad['id_discapacidad']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($discapacidad['tipo_discapacidad']); ?>
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
            
            <!-- Botones de exportación -->
            <div class="row mt-3">
                <div class="col-md-12 export-buttons">
                    <div class="btn-group">
                        <a href="exportarA_excel.php?<?php echo http_build_query($_GET); ?>" 
   class="btn btn-outline-danger">
                            <i class='bx bx-download'></i> Exportar a Excel
                        </a>
                        <a href="exportarA_pdf.php?<?php echo http_build_query($_GET); ?>" 
   class="btn btn-outline-danger">
                            <i class='bx bxs-file-pdf'></i> Exportar a PDF
                        </a>
                        <a href="javascript:window.print()" class="btn btn-outline-info">
                            <i class='bx bx-printer'></i> Imprimir
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de alumnos -->
        <div class="table-container">
            <?php if (empty($alumnos)): ?>
                <div class="alert alert-info text-center">
                    <i class='bx bx-info-circle'></i> No se encontraron alumnos con los criterios de b&uacute;squeda.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Foto</th>
                                <th>Matr&iacute;cula</th>
                                <th>Nombre Completo</th>
                                <th>G&eacute;nero</th>
                                <th>Email Institucional</th>
                                <th>Celular</th>
                                <th>Discapacidad</th>
                                <th>Fecha Alta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $contador = $offset + 1; ?>
                            <?php foreach ($alumnos as $alumno): ?>
                                <tr>
                                    <td><?php echo $contador++; ?></td>
                                    <td>
                                        <?php if (!empty($alumno['rutaImagen'])): ?>
                                            <img src="<?php echo htmlspecialchars($alumno['rutaImagen']); ?>" 
                                                 alt="Foto" class="avatar">
                                        <?php else: ?>
                                            <div class="avatar bg-light text-center d-flex align-items-center justify-content-center">
                                                <i class='bx bx-user' style="font-size: 1.2rem; color: #666;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($alumno['matricula']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($alumno['apellido_paterno'] . ' ' . 
                                              $alumno['apellido_materno'] . ', ' . $alumno['nombre']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($alumno['genero'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($alumno['correo_institucional']); ?>">
                                            <?php echo htmlspecialchars($alumno['correo_institucional']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($alumno['numCelular'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($alumno['telefono_celular']); ?>">
                                                <?php echo htmlspecialchars($alumno['teleono_celular']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($alumno['tipo_discapacidad'])): ?>
                                            <span class="badge bg-warning text-dark">
                                                <?php echo htmlspecialchars($alumno['tipo_discapacidad']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Ninguna</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($alumno['fecha_ingreso'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="editar_alumnos.php?matricula=<?php echo urlencode($alumno['matricula']); ?>" 
                                               class="btn btn-warning btn-sm" title="Editar">
                                                <i class='bx bx-edit'></i>
                                            </a>
                                            <a href="ver_alumno.php?matricula=<?php echo urlencode($alumno['matricula']); ?>" 
                                               class="btn btn-info btn-sm" title="Ver">
                                                <i class='bx bx-show'></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="confirmarEliminacion('<?php echo $alumno['matricula']; ?>', 
                                                                                 '<?php echo addslashes($alumno['nombre'] . ' ' . $alumno['apellido_paterno']); ?>')"
                                                    title="Eliminar">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                            <a href="qr_alumno.php?matricula=<?php echo urlencode($alumno['matricula']); ?>" 
                                               class="btn btn-primary btn-sm" title="Generar QR">
                                                <i class='bx bx-qr'></i>
                                            </a>
											<a href="gestionar_fotos.php?matricula=<?php echo urlencode($alumno['matricula']); ?>" 
                                               class="btn btn-primary btn-sm" title="Subir Foto">
                                                <i class='bx bx-image-add'></i>
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
                    <nav aria-label="Paginación de alumnos">
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
                        | Mostrando <?php echo count($alumnos); ?> de <?php echo $total_registros; ?> alumnos
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
        
        <!-- Pie de página -->
        <div class="mt-4 text-center text-muted">
            <p>CECyTE SANTA CATARINA N.L. | Sistema de Gesti&oacute;n Acad&eacute;mica</p>
            <p><?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class='bx bx-trash'></i> Confirmar Eliminaci&oacute;n
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Est&aacute; seguro que desea eliminar al alumno <strong id="nombreAlumno"></strong>?</p>
                    <p class="text-danger"><strong>Esta acci&oacute;n no se puede deshacer.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="btnEliminarConfirmar" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Función para confirmar eliminación
        function confirmarEliminacion(matricula, nombre) {
            const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
            document.getElementById('nombreAlumno').textContent = nombre;
            document.getElementById('btnEliminarConfirmar').href = 'eliminar_alumno.php?matriculaAlumno=' + encodeURIComponent(matricula);
            modal.show();
        }
        
        // Inicializar DataTable (opcional - descomentar si quieres usar DataTables)
        /*
        $(document).ready(function() {
            $('table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: <?php echo $por_pagina; ?>,
                responsive: true
            });
        });
        */
        
        // Función para filtrar en tiempo real
        document.getElementById('busqueda').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
        
        // Actualizar contador de resultados
        document.addEventListener('DOMContentLoaded', function() {
            const totalResultados = <?php echo $total_registros; ?>;
            const mostrando = <?php echo count($alumnos); ?>;
            
            console.log(`Mostrando ${mostrando} de ${totalResultados} alumnos`);
            
            // Agregar tooltips a los botones
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        // Función para limpiar filtros
        function limpiarFiltros() {
            window.location.href = 'lista_alumnos.php';
        }
    </script>
</body>
</html>