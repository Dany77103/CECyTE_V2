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
try {
    $con = new PDO("mysql:host=localhost;dbname=cecyte_sc;charset=utf8mb4", "root", "");
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

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
        
        // Eliminar foto anterior si existe
        $stmt = $con->prepare("SELECT rutaImagen FROM alumnos WHERE matricula = ?");
        $stmt->execute([$matricula]);
        $foto_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($foto_actual['rutaImagen']) && file_exists($foto_actual['rutaImagen'])) {
            unlink($foto_actual['rutaImagen']);
        }
        
        // Generar nuevo nombre
        $nuevo_nombre = $matricula . '.' . $extension;
        $ruta_destino = DIRECTORIO_IMAGENES . $nuevo_nombre;
        
        // Mover archivo
        if (move_uploaded_file($tmp_name, $ruta_destino)) {
            // Actualizar base de datos
            $stmt = $con->prepare("UPDATE alumnos SET rutaImagen = ? WHERE matricula = ?");
            if ($stmt->execute([$ruta_destino, $matricula])) {
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
// 5. PROCESAR ELIMINACIÓN DE FOTO
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_foto'])) {
    $matricula = trim($_POST['matricula']);
    
    $stmt = $con->prepare("SELECT rutaImagen, nombre, apellido_paterno FROM alumnos WHERE matricula = ?");
    $stmt->execute([$matricula]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($alumno && !empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])) {
        if (unlink($alumno['rutaImagen'])) {
            $stmt = $con->prepare("UPDATE alumnos SET rutaImagen = NULL WHERE matricula = ?");
            $stmt->execute([$matricula]);
            $_SESSION['success'] = "Foto eliminada para: " . $alumno['nombre'] . " " . $alumno['apellido_paterno'];
        } else {
            $_SESSION['error'] = "No se pudo eliminar el archivo";
        }
    } else {
        $_SESSION['error'] = "No se encontró foto para eliminar";
    }
    
    header("Location: gestionar_fotos.php");
    exit();
}

// ==============================================
// 6. OBTENER ALUMNOS PARA MOSTRAR - VERSIÓN CORREGIDA
// ==============================================
$por_pagina = 12;
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

if ($pagina > $total_paginas && $total_paginas > 0) {
    $pagina = $total_paginas;
}

// Ahora obtener datos con paginación - CORREGIDO
$offset = ($pagina - 1) * $por_pagina;

// IMPORTANTE: En MySQL, LIMIT y OFFSET deben ser valores literales, no parámetros preparados
// Así que los concatenamos directamente
$sql = $sql_base . " LIMIT $por_pagina OFFSET $offset";

$stmt = $con->prepare($sql);

// Enlazar parámetros para la consulta principal
foreach ($params as $i => $param) {
    $stmt->bindValue($i + 1, $param, $tipos[$i]);
}

$stmt->execute();
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar fotos
$con_foto = 0;
$sin_foto = 0;
foreach ($alumnos as $alumno) {
    if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])) {
        $con_foto++;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .foto-container {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            margin: 0 auto;
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
        }
        .card-alumno:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .badge-estado {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Mensajes -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Título y botón volver -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">?? Gestionar Fotos</h1>
            <a href="main.php" class="btn btn-outline-secondary">
                ? Volver al Menú
            </a>
        </div>
        
        <!-- Filtros -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text">??</span>
                    <input type="text" class="form-control" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="tipo_busqueda">
                    <option value="matricula" <?php echo $tipo_busqueda == 'matricula' ? 'selected' : ''; ?>>Matrícula</option>
                    <option value="nombre" <?php echo $tipo_busqueda == 'nombre' ? 'selected' : ''; ?>>Nombre</option>
                    <option value="apellido_paterno" <?php echo $tipo_busqueda == 'apellido_paterno' ? 'selected' : ''; ?>>Apellido</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="filtro_foto">
                    <option value="todos" <?php echo $filtro_foto == 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <option value="con_foto" <?php echo $filtro_foto == 'con_foto' ? 'selected' : ''; ?>>Con foto</option>
                    <option value="sin_foto" <?php echo $filtro_foto == 'sin_foto' ? 'selected' : ''; ?>>Sin foto</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
        
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?php echo $total_alumnos; ?></h5>
                        <p class="card-text">Total Alumnos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?php echo $con_foto; ?></h5>
                        <p class="card-text">Con Foto</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?php echo $sin_foto; ?></h5>
                        <p class="card-text">Sin Foto</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?php echo $por_pagina; ?></h5>
                        <p class="card-text">Por Página</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mostrar información de paginación -->
        <div class="alert alert-info">
            Mostrando <?php echo count($alumnos); ?> de <?php echo $total_alumnos; ?> alumnos.
            Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>.
        </div>
        
        <!-- Lista de alumnos -->
        <?php if (empty($alumnos)): ?>
            <div class="alert alert-warning text-center">
                No se encontraron alumnos con los filtros aplicados.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($alumnos as $alumno): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="card card-alumno position-relative">
                            <!-- Badge de estado -->
                            <?php if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])): ?>
                                <span class="badge bg-success badge-estado">? Con foto</span>
                            <?php else: ?>
                                <span class="badge bg-danger badge-estado">? Sin foto</span>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <!-- Foto -->
                                <div class="foto-container mb-3">
                                    <?php if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])): ?>
                                        <img src="<?php echo htmlspecialchars($alumno['rutaImagen']); ?>" 
                                             alt="Foto de <?php echo htmlspecialchars($alumno['nombre']); ?>"
                                             class="foto-alumno"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"100\" height=\"100\" viewBox=\"0 0 24 24\"><path fill=\"%236c757d\" d=\"M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\"/></svg>'">
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="#6c757d" viewBox="0 0 16 16">
                                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Información -->
                                <h6 class="card-title text-center">
                                    <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno']); ?>
                                </h6>
                                <p class="card-text text-muted small text-center">
                                    Matrícula: <?php echo htmlspecialchars($alumno['matricula']); ?>
                                </p>
                                
                                <!-- Formularios -->
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="matricula" value="<?php echo htmlspecialchars($alumno['matricula']); ?>">
                                    
                                    <div class="mb-2">
                                        <input type="file" class="form-control form-control-sm" name="foto" accept=".jpg,.jpeg,.png,.gif" required>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="subir_foto" class="btn btn-success btn-sm">
                                            Subir Foto
                                        </button>
                                        
                                        <?php if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])): ?>
                                            <button type="submit" name="eliminar_foto" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('¿Estás seguro de eliminar la foto de <?php echo addslashes($alumno['nombre'] . ' ' . $alumno['apellido_paterno']); ?>?')">
                                                Eliminar Foto
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
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($pagina > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>">
                                « Primera
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">
                                ‹ Anterior
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php 
                    // Mostrar solo algunas páginas alrededor de la actual
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
                                Siguiente ›
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $total_paginas])); ?>">
                                Última »
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="text-center mt-2">
                <small class="text-muted">
                    Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
                </small>
            </div>
        <?php endif; ?>
        
        <!-- Limpiar filtros -->
        <div class="text-center mt-4">
            <a href="gestionar_fotos.php" class="btn btn-outline-secondary">
                Limpiar Filtros
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Cerrar alertas automáticamente después de 5 segundos
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Validar tamaño de archivo antes de enviar
    document.querySelectorAll('input[type="file"]').forEach(function(input) {
        input.addEventListener('change', function() {
            if (this.files[0].size > <?php echo MAX_TAMANO; ?>) {
                alert('El archivo es demasiado grande. Máximo 5MB.');
                this.value = '';
            }
        });
    });
    </script>
</body>
</html>