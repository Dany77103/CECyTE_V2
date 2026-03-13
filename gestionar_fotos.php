<?php
// ==============================================
// 1. CONFIGURACIÓN BÁSICA
// ==============================================
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// ==============================================
// 2. CONEXIÓN A LA BASE DE DATOS
// ==============================================
require_once 'conexion.php';

// ==============================================
// 3. CONFIGURACIÓN
// ==============================================
define('DIRECTORIO_IMAGENES', 'img/alumnos/');
define('MAX_TAMANO', 5 * 1024 * 1024); // 5MB
$formatos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];

// ==============================================
// 4. PROCESAR SUBIDA DE FOTO
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subir_foto'])) {
    $matricula = trim($_POST['matricula']);
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $archivo = $_FILES['foto'];
        $nombre_archivo = $archivo['name'];
        $tamano = $archivo['size'];
        $tmp_name = $archivo['tmp_name'];
        
        // Validar tamaño
        if ($tamano > MAX_TAMANO) {
            $_SESSION['error'] = "El archivo es demasiado grande (>5MB)";
            header("Location: gestionar_fotos.php");
            exit();
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
        if (!in_array($extension, $formatos_permitidos)) {
            $_SESSION['error'] = "Formato no permitido. Use JPG, PNG o GIF";
            header("Location: gestionar_fotos.php");
            exit();
        }
        
        // Crear directorio si no existe
        if (!file_exists(DIRECTORIO_IMAGENES)) {
            mkdir(DIRECTORIO_IMAGENES, 0777, true);
        }
        
        // Verificar si el alumno existe
        $stmt = $con->prepare("SELECT matricula, nombre, apellido_paterno FROM alumnos WHERE matricula = ?");
        $stmt->execute([$matricula]);
        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$alumno) {
            $_SESSION['error'] = "Alumno no encontrado";
            header("Location: gestionar_fotos.php");
            exit();
        }
        
        // Generar nuevo nombre (solo guardamos el nombre del archivo, no la ruta completa)
        $nuevo_nombre = $matricula . '.' . $extension;
        $ruta_destino = DIRECTORIO_IMAGENES . $nuevo_nombre;
        
        // Eliminar foto anterior si existe
        $stmt = $con->prepare("SELECT rutaImagen FROM alumnos WHERE matricula = ?");
        $stmt->execute([$matricula]);
        $foto_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($foto_actual['rutaImagen'])) {
            $ruta_anterior = DIRECTORIO_IMAGENES . $foto_actual['rutaImagen'];
            if (file_exists($ruta_anterior)) {
                unlink($ruta_anterior);
            }
        }
        
        // Mover archivo
        if (move_uploaded_file($tmp_name, $ruta_destino)) {
            // Actualizar base de datos - solo guardamos el nombre del archivo
            $stmt = $con->prepare("UPDATE alumnos SET rutaImagen = ? WHERE matricula = ?");
            if ($stmt->execute([$nuevo_nombre, $matricula])) {
                $_SESSION['success'] = "Foto subida para: " . $alumno['nombre'] . " " . $alumno['apellido_paterno'];
            } else {
                $_SESSION['error'] = "Error al actualizar en BD";
            }
        } else {
            $_SESSION['error'] = "Error al subir archivo";
        }
    } else {
        $_SESSION['error'] = "No se seleccionó archivo o hubo un error";
    }
    
    header("Location: gestionar_fotos.php");
    exit();
}

// ==============================================
// 5. PROCESAR ELIMINACIÓN DE FOTO - VERSIÓN MEJORADA
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_foto'])) {
    $matricula = trim($_POST['matricula']);
    
    // Verificar si el alumno existe
    $stmt = $con->prepare("SELECT rutaImagen, nombre, apellido_paterno FROM alumnos WHERE matricula = ?");
    $stmt->execute([$matricula]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$alumno) {
        $_SESSION['error'] = "Alumno no encontrado";
        header("Location: gestionar_fotos.php");
        exit();
    }
    
    // Verificar si tiene foto
    if (empty($alumno['rutaImagen'])) {
        $_SESSION['error'] = "El alumno no tiene foto para eliminar";
        header("Location: gestionar_fotos.php");
        exit();
    }
    
    // Construir ruta completa
    $ruta_imagen = DIRECTORIO_IMAGENES . $alumno['rutaImagen'];
    
    // Verificar si el archivo existe
    if (file_exists($ruta_imagen)) {
        // Intentar eliminar el archivo físico
        if (unlink($ruta_imagen)) {
            // Actualizar la base de datos
            $stmt = $con->prepare("UPDATE alumnos SET rutaImagen = NULL WHERE matricula = ?");
            if ($stmt->execute([$matricula])) {
                $_SESSION['success'] = "Foto eliminada para: " . $alumno['nombre'] . " " . $alumno['apellido_paterno'];
            } else {
                $_SESSION['error'] = "Error al actualizar la base de datos";
            }
        } else {
            $_SESSION['error'] = "No se pudo eliminar el archivo físico. Verifique permisos.";
        }
    } else {
        // Si no existe el archivo físico, limpiar la BD de todos modos
        $stmt = $con->prepare("UPDATE alumnos SET rutaImagen = NULL WHERE matricula = ?");
        if ($stmt->execute([$matricula])) {
            $_SESSION['success'] = "Registro de foto limpiado para: " . $alumno['nombre'] . " " . $alumno['apellido_paterno'];
        } else {
            $_SESSION['error'] = "Error al limpiar registro de foto";
        }
    }
    
    header("Location: gestionar_fotos.php");
    exit();
}

// ==============================================
// 6. OBTENER ALUMNOS PARA MOSTRAR
// ==============================================
$por_pagina = 9; // 9 fotos por página (3x3 grid)
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;

$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$tipo_busqueda = isset($_GET['tipo_busqueda']) ? $_GET['tipo_busqueda'] : 'matricula';
$filtro_foto = isset($_GET['filtro_foto']) ? $_GET['filtro_foto'] : 'todos';

// Construir consulta base sin paginación
$sql_base = "SELECT matricula, nombre, apellido_paterno, apellido_materno, rutaImagen FROM alumnos WHERE 1=1";
$params = [];
$tipos = [];

if (!empty($busqueda)) {
    if ($tipo_busqueda == 'matricula') {
        $sql_base .= " AND matricula LIKE ?";
        $params[] = "%$busqueda%";
        $tipos[] = PDO::PARAM_STR;
    } elseif ($tipo_busqueda == 'nombre') {
        $sql_base .= " AND nombre LIKE ?";
        $params[] = "%$busqueda%";
        $tipos[] = PDO::PARAM_STR;
    } elseif ($tipo_busqueda == 'apellido_paterno') {
        $sql_base .= " AND apellido_paterno LIKE ?";
        $params[] = "%$busqueda%";
        $tipos[] = PDO::PARAM_STR;
    }
}

if ($filtro_foto == 'con_foto') {
    $sql_base .= " AND rutaImagen IS NOT NULL AND rutaImagen != ''";
} elseif ($filtro_foto == 'sin_foto') {
    $sql_base .= " AND (rutaImagen IS NULL OR rutaImagen = '')";
}

$sql_base .= " ORDER BY apellido_paterno, apellido_materno, nombre";

// Primero, contar el total de registros
$sql_count = "SELECT COUNT(*) as total FROM ($sql_base) as subquery";
$stmt_count = $con->prepare($sql_count);

// Enlazar parámetros para count
foreach ($params as $i => $param) {
    $stmt_count->bindValue($i + 1, $param, $tipos[$i]);
}

$stmt_count->execute();
$total_alumnos = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_alumnos / $por_pagina);

if ($total_paginas > 0 && $pagina > $total_paginas) {
    $pagina = $total_paginas;
}

// Ahora obtener datos con paginación
$offset = ($pagina - 1) * $por_pagina;
$sql = $sql_base . " LIMIT $por_pagina OFFSET $offset";

$stmt = $con->prepare($sql);
foreach ($params as $i => $param) {
    $stmt->bindValue($i + 1, $param, $tipos[$i]);
}

$stmt->execute();
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar fotos para estadísticas
$con_foto = 0;
$sin_foto = 0;
foreach ($alumnos as $alumno) {
    if (!empty($alumno['rutaImagen'])) {
        $ruta_completa = DIRECTORIO_IMAGENES . $alumno['rutaImagen'];
        if (file_exists($ruta_completa)) {
            $con_foto++;
        } else {
            $sin_foto++;
        }
    } else {
        $sin_foto++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Fotos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .foto-container {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            overflow: hidden;
            border: 3px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            margin: 0 auto 15px auto;
        }
        .foto-alumno {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .card-alumno {
            height: 100%;
            transition: transform 0.2s;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .card-alumno:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #2e7d32;
        }
        .badge-estado {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.7rem;
            padding: 4px 8px;
        }
        .card-body {
            padding: 15px;
        }
        .card-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 5px;
            height: 40px;
            overflow: hidden;
        }
        .card-text {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        .header-card {
            background-color: #2e7d32;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
        }
        .form-container {
            background: white;
            padding: 25px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .pagination .page-link {
            color: #2e7d32;
        }
        .pagination .page-item.active .page-link {
            background-color: #2e7d32;
            border-color: #2e7d32;
            color: white;
        }
        .info-box {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .stat-card {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            color: white;
            margin-bottom: 15px;
        }
        .stat-card h5 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        .stat-card p {
            font-size: 0.9rem;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Título y botón volver -->
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><i class='bx bx-camera'></i> Gestionar Fotos de Alumnos</h3>
                    <p class="mb-0 mt-1">Sube, visualiza o elimina fotos de los alumnos</p>
                </div>
                <a href="main.php" class="btn btn-light">
                    <i class='bx bx-arrow-back'></i> Volver al Men&uacute;
                </a>
            </div>
        </div>
        
        <div class="form-container">
            <!-- Mensajes -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class='bx bx-check-circle'></i> <?php echo $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class='bx bx-error'></i> <?php echo $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- Información importante -->
            <div class="info-box">
                <h6><i class='bx bx-info-circle'></i> Informaci&oacute;n importante</h6>
                <p class="mb-0">Solo se permiten archivos JPG, PNG o GIF de m&aacute;ximo 5MB. La foto se renombrar&aacute; autom&aacute;ticamente con la matr&iacute;cula del alumno.</p>
            </div>
            
            <!-- Filtros -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" class="form-control" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar alumno...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="tipo_busqueda">
                        <option value="matricula" <?php echo $tipo_busqueda == 'matricula' ? 'selected' : ''; ?>>Por Matr&iacute;cula</option>
                        <option value="nombre" <?php echo $tipo_busqueda == 'nombre' ? 'selected' : ''; ?>>Por Nombre</option>
                        <option value="apellido_paterno" <?php echo $tipo_busqueda == 'apellido_paterno' ? 'selected' : ''; ?>>Por Apellido</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="filtro_foto">
                        <option value="todos" <?php echo $filtro_foto == 'todos' ? 'selected' : ''; ?>>Todos los alumnos</option>
                        <option value="con_foto" <?php echo $filtro_foto == 'con_foto' ? 'selected' : ''; ?>>Solo con foto</option>
                        <option value="sin_foto" <?php echo $filtro_foto == 'sin_foto' ? 'selected' : ''; ?>>Solo sin foto</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class='bx bx-filter-alt'></i>
                    </button>
                </div>
            </form>
            
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card" style="background-color: #2e7d32;">
                        <h5><?php echo $total_alumnos; ?></h5>
                        <p>Total Alumnos</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background-color: #28a745;">
                        <h5><?php echo $con_foto; ?></h5>
                        <p>Con Foto</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background-color: #ffc107; color: #212529;">
                        <h5><?php echo $sin_foto; ?></h5>
                        <p>Sin Foto</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background-color: #17a2b8;">
                        <h5><?php echo $por_pagina; ?></h5>
                        <p>Por P&aacute;gina</p>
                    </div>
                </div>
            </div>
            
            <!-- Información de paginación -->
            <div class="alert alert-info mb-4">
                <i class='bx bx-info-circle'></i> 
                Mostrando <?php echo count($alumnos); ?> de <?php echo $total_alumnos; ?> alumnos.
                P&aacute;gina <?php echo $pagina; ?> de <?php echo $total_paginas; ?>.
            </div>
            
            <!-- Lista de alumnos -->
            <?php if (empty($alumnos)): ?>
                <div class="alert alert-warning text-center">
                    <i class='bx bx-info-circle'></i> No se encontraron alumnos con los filtros aplicados.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($alumnos as $alumno): 
                        $tiene_foto = !empty($alumno['rutaImagen']);
                        $ruta_completa = $tiene_foto ? DIRECTORIO_IMAGENES . $alumno['rutaImagen'] : '';
                        $existe_archivo = $tiene_foto && file_exists($ruta_completa);
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="card card-alumno position-relative">
                                <!-- Badge de estado -->
                                <?php if ($existe_archivo): ?>
                                    <span class="badge bg-success badge-estado"><i class='bx bx-camera'></i> Con foto</span>
                                <?php else: ?>
                                    <span class="badge bg-danger badge-estado"><i class='bx bx-x-circle'></i> Sin foto</span>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <!-- Foto -->
                                    <div class="foto-container">
                                        <?php if ($existe_archivo): ?>
                                            <img src="<?php echo htmlspecialchars($ruta_completa); ?>" 
                                                 alt="Foto de <?php echo htmlspecialchars($alumno['nombre']); ?>"
                                                 class="foto-alumno"
                                                 onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"120\" height=\"120\" viewBox=\"0 0 24 24\"><path fill=\"%236c757d\" d=\"M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\"/></svg>'">
                                        <?php else: ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="#6c757d" viewBox="0 0 16 16">
                                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Información -->
                                    <h6 class="card-title text-center">
                                        <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno']); ?>
                                    </h6>
                                    <p class="card-text text-muted small text-center">
                                        Matr&iacute;cula: <strong><?php echo htmlspecialchars($alumno['matricula']); ?></strong>
                                    </p>
                                    
                                    <!-- Formularios -->
                                    <form method="POST" enctype="multipart/form-data" class="form-alumno" data-matricula="<?php echo htmlspecialchars($alumno['matricula']); ?>">
                                        <input type="hidden" name="matricula" value="<?php echo htmlspecialchars($alumno['matricula']); ?>">
                                        
                                        <div class="mb-3">
                                            <input type="file" class="form-control form-control-sm" name="foto" accept=".jpg,.jpeg,.png,.gif">
                                            <small class="text-muted">Seleccione una imagen</small>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="subir_foto" class="btn btn-success btn-sm">
                                                <i class='bx bx-upload'></i> Subir/Reemplazar
                                            </button>
                                            
                                            <?php if ($existe_archivo): ?>
                                                <button type="button" class="btn btn-danger btn-sm btn-eliminar-foto" 
                                                        data-matricula="<?php echo htmlspecialchars($alumno['matricula']); ?>">
                                                    <i class='bx bx-trash'></i> Eliminar Foto
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Navegaci&oacute;n de p&aacute;ginas" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>">
                                    <i class='bx bx-chevrons-left'></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">
                                    <i class='bx bx-chevron-left'></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        // Mostrar páginas alrededor de la actual
                        $inicio = max(1, $pagina - 2);
                        $fin = min($total_paginas, $pagina + 2);
                        
                        for ($i = $inicio; $i <= $fin; $i++): 
                        ?>
                            <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">
                                    <i class='bx bx-chevron-right'></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $total_paginas])); ?>">
                                    <i class='bx bx-chevrons-right'></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center mt-2">
                    <small class="text-muted">
                        P&aacute;gina <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
                    </small>
                </div>
            <?php endif; ?>
            
            <!-- Limpiar filtros -->
            <div class="text-center mt-4 pt-3 border-top">
                <a href="gestionar_fotos.php" class="btn btn-outline-secondary">
                    <i class='bx bx-x-circle'></i> Limpiar Filtros
                </a>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel"><i class='bx bx-trash text-danger'></i> Confirmar Eliminaci&oacute;n</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Est&aacute; seguro de que desea eliminar la foto del alumno?</p>
                    <p class="mb-0"><strong>Matr&iacute;cula:</strong> <span id="matricula-alumno"></span></p>
                    <div class="alert alert-warning mt-3">
                        <i class='bx bx-info-circle'></i> Esta acci&oacute;n no se puede deshacer.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="matricula" id="delete-matricula">
                        <button type="submit" name="eliminar_foto" class="btn btn-danger">S&iacute;, Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const confirmDeleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        const deleteForm = document.getElementById('deleteForm');
        const matriculaSpan = document.getElementById('matricula-alumno');
        const deleteMatriculaInput = document.getElementById('delete-matricula');
        
        // Manejar el clic en botones de eliminar
        document.querySelectorAll('.btn-eliminar-foto').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const matricula = this.getAttribute('data-matricula');
                
                // Configurar el modal
                matriculaSpan.textContent = matricula;
                deleteMatriculaInput.value = matricula;
                
                // Mostrar el modal
                confirmDeleteModal.show();
            });
        });
        
        // Validar tamaño de archivo antes de enviar
        document.querySelectorAll('input[type="file"]').forEach(function(input) {
            input.addEventListener('change', function() {
                const maxSize = <?php echo MAX_TAMANO; ?>;
                if (this.files[0] && this.files[0].size > maxSize) {
                    alert('El archivo es demasiado grande. M&aacute;ximo 5MB.');
                    this.value = '';
                }
            });
        });
        
        // Validar que se seleccione un archivo antes de subir
        document.querySelectorAll('form.form-alumno').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (e.submitter.name === 'subir_foto') {
                    const fileInput = this.querySelector('input[type="file"]');
                    if (!fileInput.value) {
                        e.preventDefault();
                        alert('Por favor, seleccione un archivo para subir.');
                        return false;
                    }
                }
            });
        });
        
        // Cerrar alertas automáticamente después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('alert-dismissible')) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 5000);
    });
    </script>
</body>
</html>