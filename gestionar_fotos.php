<?php
// ==============================================
// 1. CONFIGURACIÓN BÁSICA (Lógica original intacta)
// ==============================================
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        
        if ($tamano > MAX_TAMANO) {
            $_SESSION['error'] = "El archivo es demasiado grande (>5MB)";
            header("Location: gestionar_fotos.php");
            exit();
        }
        
        $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
        if (!in_array($extension, $formatos_permitidos)) {
            $_SESSION['error'] = "Formato no permitido. Use JPG, PNG o GIF";
            header("Location: gestionar_fotos.php");
            exit();
        }
        
        if (!file_exists(DIRECTORIO_IMAGENES)) {
            mkdir(DIRECTORIO_IMAGENES, 0777, true);
        }
        
        $stmt = $con->prepare("SELECT matricula, nombre, apellido_paterno FROM alumnos WHERE matricula = ?");
        $stmt->execute([$matricula]);
        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$alumno) {
            $_SESSION['error'] = "Alumno no encontrado";
            header("Location: gestionar_fotos.php");
            exit();
        }
        
        $nuevo_nombre = $matricula . '.' . $extension;
        $ruta_destino = DIRECTORIO_IMAGENES . $nuevo_nombre;
        
        $stmt = $con->prepare("SELECT rutaImagen FROM alumnos WHERE matricula = ?");
        $stmt->execute([$matricula]);
        $foto_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($foto_actual['rutaImagen'])) {
            $ruta_anterior = DIRECTORIO_IMAGENES . $foto_actual['rutaImagen'];
            if (file_exists($ruta_anterior)) {
                unlink($ruta_anterior);
            }
        }
        
        if (move_uploaded_file($tmp_name, $ruta_destino)) {
            $stmt = $con->prepare("UPDATE alumnos SET rutaImagen = ? WHERE matricula = ?");
            if ($stmt->execute([$nuevo_nombre, $matricula])) {
                $_SESSION['success'] = "Foto subida correctamente.";
            } else {
                $_SESSION['error'] = "Error al actualizar en BD";
            }
        } else {
            $_SESSION['error'] = "Error al subir archivo";
        }
    }
    header("Location: gestionar_fotos.php");
    exit();
}

// ==============================================
// 5. PROCESAR ELIMINACIÓN
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_foto'])) {
    $matricula = trim($_POST['matricula']);
    $stmt = $con->prepare("SELECT rutaImagen FROM alumnos WHERE matricula = ?");
    $stmt->execute([$matricula]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($alumno && !empty($alumno['rutaImagen'])) {
        $ruta_imagen = DIRECTORIO_IMAGENES . $alumno['rutaImagen'];
        if (file_exists($ruta_imagen)) unlink($ruta_imagen);
        
        $stmt = $con->prepare("UPDATE alumnos SET rutaImagen = NULL WHERE matricula = ?");
        $stmt->execute([$matricula]);
        $_SESSION['success'] = "Foto eliminada.";
    }
    header("Location: gestionar_fotos.php");
    exit();
}

// ==============================================
// 6. OBTENER ALUMNOS (Paginación y Filtros)
// ==============================================
$por_pagina = 9;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$tipo_busqueda = isset($_GET['tipo_busqueda']) ? $_GET['tipo_busqueda'] : 'matricula';
$filtro_foto = isset($_GET['filtro_foto']) ? $_GET['filtro_foto'] : 'todos';

$sql_base = "FROM alumnos WHERE 1=1";
$params = [];

if (!empty($busqueda)) {
    $sql_base .= " AND $tipo_busqueda LIKE ?";
    $params[] = "%$busqueda%";
}

if ($filtro_foto == 'con_foto') $sql_base .= " AND rutaImagen IS NOT NULL AND rutaImagen != ''";
elseif ($filtro_foto == 'sin_foto') $sql_base .= " AND (rutaImagen IS NULL OR rutaImagen = '')";

$total_alumnos = $con->prepare("SELECT COUNT(*) " . $sql_base);
$total_alumnos->execute($params);
$total_registros = $total_alumnos->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

$offset = ($pagina - 1) * $por_pagina;
$sql = "SELECT matricula, nombre, apellido_paterno, apellido_materno, rutaImagen " . $sql_base . " ORDER BY apellido_paterno LIMIT $por_pagina OFFSET $offset";
$stmt = $con->prepare($sql);
$stmt->execute($params);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA-CECYTE | Gestión de Fotos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { --primary-color: #2e7d32; --secondary-color: #1b5e20; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .header-card { 
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
            color: white; padding: 25px; border-radius: 15px 15px 0 0; border: none;
        }

        .main-container { margin-top: -30px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 50px; }
        
        .card-alumno { 
            border: 1px solid #eee; border-radius: 15px; transition: 0.3s; background: #fff; overflow: hidden;
        }
        .card-alumno:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }

        .foto-preview {
            width: 100px; height: 100px; border-radius: 50%; object-fit: cover;
            border: 3px solid #e8f5e9; margin: 15px auto; display: block;
        }

        .no-foto {
            width: 100px; height: 100px; border-radius: 50%; background: #f0f0f0;
            display: flex; align-items: center; justify-content: center; margin: 15px auto;
            color: #ccc; font-size: 40px; border: 3px dashed #ddd;
        }

        .btn-primary { background-color: var(--primary-color); border: none; }
        .btn-primary:hover { background-color: var(--secondary-color); }
        
        .pagination .active .page-link { background-color: var(--primary-color); border-color: var(--primary-color); }
        .pagination .page-link { color: var(--primary-color); }

        .file-select { font-size: 0.8rem; border-radius: 20px; }
        .badge-status { position: absolute; top: 10px; right: 10px; border-radius: 20px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="header-card shadow-lg">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0"><i class='bx bx-camera-movie me-2'></i>Control de Identidad</h2>
                <p class="mb-0 opacity-75">Gestión de fotografías para expedientes de alumnos</p>
            </div>
            <a href="main.php" class="btn btn-light btn-sm rounded-pill px-3">
                <i class='bx bx-home-alt'></i> Panel Principal
            </a>
        </div>
    </div>

    <div class="main-container">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-pill" role="alert">
                <i class='bx bx-check-circle me-2'></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class='bx bx-search'></i></span>
                    <input type="text" name="busqueda" class="form-control bg-light border-0" placeholder="Buscar..." value="<?php echo $busqueda; ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="tipo_busqueda" class="form-select bg-light border-0 text-muted">
                    <option value="matricula" <?php if($tipo_busqueda=='matricula') echo 'selected'; ?>>Por Matrícula</option>
                    <option value="nombre" <?php if($tipo_busqueda=='nombre') echo 'selected'; ?>>Por Nombre</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="filtro_foto" class="form-select bg-light border-0 text-muted">
                    <option value="todos">Todos los registros</option>
                    <option value="con_foto" <?php if($filtro_foto=='con_foto') echo 'selected'; ?>>Con fotografía</option>
                    <option value="sin_foto" <?php if($filtro_foto=='sin_foto') echo 'selected'; ?>>Sin fotografía</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100 rounded-3"><i class='bx bx-filter'></i></button>
            </div>
        </form>

        <div class="row g-4">
            <?php foreach ($alumnos as $alumno): 
                $path = DIRECTORIO_IMAGENES . $alumno['rutaImagen'];
                $has_photo = (!empty($alumno['rutaImagen']) && file_exists($path));
            ?>
            <div class="col-md-4">
                <div class="card card-alumno p-3 text-center position-relative">
                    <span class="badge badge-status <?php echo $has_photo ? 'bg-success' : 'bg-warning text-dark'; ?>">
                        <?php echo $has_photo ? 'Registrada' : 'Pendiente'; ?>
                    </span>
                    
                    <?php if($has_photo): ?>
                        <img src="<?php echo $path; ?>" class="foto-preview shadow-sm" alt="Alumno">
                    <?php else: ?>
                        <div class="no-foto"><i class='bx bx-user'></i></div>
                    <?php endif; ?>

                    <h6 class="mb-1 text-uppercase fw-bold"><?php echo $alumno['nombre']; ?></h6>
                    <p class="text-muted small mb-3"><?php echo $alumno['apellido_paterno'] . ' | ' . $alumno['matricula']; ?></p>

                    <form method="POST" enctype="multipart/form-data" class="mt-2">
                        <input type="hidden" name="matricula" value="<?php echo $alumno['matricula']; ?>">
                        <div class="mb-2">
                            <input type="file" name="foto" class="form-control form-control-sm file-select" accept="image/*">
                        </div>
                        <div class="d-flex gap-1">
                            <button type="submit" name="subir_foto" class="btn btn-primary btn-sm w-100 rounded-pill">
                                <i class='bx bx-upload'></i> <?php echo $has_photo ? 'Cambiar' : 'Subir'; ?>
                            </button>
                            <?php if($has_photo): ?>
                                <button type="submit" name="eliminar_foto" class="btn btn-outline-danger btn-sm rounded-circle" onclick="return confirm('¿Eliminar foto?')">
                                    <i class='bx bx-trash'></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_paginas > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <?php for($i=1; $i<=$total_paginas; $i++): ?>
                    <li class="page-item <?php echo ($i==$pagina) ? 'active' : ''; ?>">
                        <a class="page-link shadow-sm mx-1 rounded-circle" href="?pagina=<?php echo $i; ?>&busqueda=<?php echo $busqueda; ?>&filtro_foto=<?php echo $filtro_foto; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>