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
    <title>Gestión de Fotos | CECyTE</title>
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
            --success: #2e7d32;
            --warning: #ffa000;
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

        /* --- FORMULARIO FILTROS --- */
        .filter-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .form-control { 
            padding: 10px 15px; border-radius: 10px; border: 1px solid #ddd; 
            font-family: 'Inter', sans-serif; font-size: 0.9rem;
        }
        .form-select { 
            padding: 10px 15px; border-radius: 10px; border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        /* --- GRID DE ALUMNOS --- */
        .alumnos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .card-alumno {
            background: var(--white);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            position: relative;
            transition: transform 0.2s;
            border: 1px solid transparent;
        }
        .card-alumno:hover { transform: translateY(-5px); border-color: #e0e0e0; }

        .foto-preview {
            width: 110px; height: 110px; border-radius: 50%;
            object-fit: cover; margin: 10px auto 15px;
            border: 4px solid #f0f4f1;
            box-shadow: var(--shadow-sm);
        }

        .no-foto {
            width: 110px; height: 110px; border-radius: 50%;
            background: #f0f0f0; margin: 10px auto 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 40px; color: #ccc; border: 2px dashed #ddd;
        }

        .status-badge {
            position: absolute; top: 15px; right: 15px;
            font-size: 0.7rem; font-weight: 700; padding: 4px 12px;
            border-radius: 20px; text-transform: uppercase;
        }
        .bg-registrada { background: #e8f5e9; color: var(--success); }
        .bg-pendiente { background: #fff3e0; color: var(--warning); }

        .nombre-alumno { font-size: 1rem; font-weight: 700; color: var(--primary); margin-bottom: 5px; text-transform: uppercase; }
        .info-alumno { font-size: 0.85rem; color: var(--secondary); margin-bottom: 15px; }

        /* --- BOTONES --- */
        .btn { 
            padding: 10px 18px; border-radius: 10px; border: none; cursor: pointer; 
            font-weight: 600; font-size: 0.85rem; display: inline-flex; 
            align-items: center; gap: 8px; text-decoration: none; transition: 0.2s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: #e9ecef; color: var(--secondary); }
        .btn-danger-outline { background: transparent; border: 1px solid #ffcdd2; color: #d32f2f; }
        .btn-danger-outline:hover { background: #ffebee; }

        .input-file-custom { font-size: 0.75rem; width: 100%; margin-bottom: 10px; }

        /* --- PAGINACIÓN --- */
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 30px; list-style: none; }
        .page-link { 
            padding: 8px 16px; border-radius: 8px; background: var(--white);
            text-decoration: none; color: var(--primary); font-weight: 600;
            box-shadow: var(--shadow-sm);
        }
        .page-item.active .page-link { background: var(--primary); color: white; }

        .alert {
            padding: 15px; border-radius: 12px; margin-bottom: 20px;
            border-left: 5px solid var(--success); background: #e8f5e9; color: var(--success);
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div class="nav-actions">
            <a href="main.php" class="btn btn-secondary"><i class="fas fa-home"></i></a>
            <a href="logout.php" class="btn btn-secondary" style="color: #dc3545;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="container">
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Búsqueda de Alumnos</h2>
            </div>
            <form method="GET" class="filter-row">
                <input type="text" name="busqueda" class="form-control" style="flex: 2; min-width: 200px;" placeholder="Escribe matrícula o nombre..." value="<?= htmlspecialchars($busqueda) ?>">
                <select name="tipo_busqueda" class="form-select" style="flex: 1;">
                    <option value="matricula" <?= $tipo_busqueda=='matricula'?'selected':'' ?>>Por Matrícula</option>
                    <option value="nombre" <?= $tipo_busqueda=='nombre'?'selected':'' ?>>Por Nombre</option>
                </select>
                <select name="filtro_foto" class="form-select" style="flex: 1;">
                    <option value="todos">Todos</option>
                    <option value="con_foto" <?= $filtro_foto=='con_foto'?'selected':'' ?>>Con Foto</option>
                    <option value="sin_foto" <?= $filtro_foto=='sin_foto'?'selected':'' ?>>Sin Foto</option>
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="alumnos-grid">
            <?php foreach ($alumnos as $alumno): 
                $path = DIRECTORIO_IMAGENES . $alumno['rutaImagen'];
                $has_photo = (!empty($alumno['rutaImagen']) && file_exists($path));
            ?>
            <div class="card card-alumno">
                <span class="status-badge <?= $has_photo ? 'bg-registrada' : 'bg-pendiente' ?>">
                    <?= $has_photo ? 'Registrada' : 'Pendiente' ?>
                </span>
                
                <?php if($has_photo): ?>
                    <img src="<?= $path ?>" class="foto-preview" alt="Alumno">
                <?php else: ?>
                    <div class="no-foto"><i class="fas fa-user"></i></div>
                <?php endif; ?>

                <h3 class="nombre-alumno"><?= $alumno['nombre'] ?></h3>
                <p class="info-alumno">
                    <?= $alumno['apellido_paterno'] ?> <br>
                    <strong>ID: <?= $alumno['matricula'] ?></strong>
                </p>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="matricula" value="<?= $alumno['matricula'] ?>">
                    <input type="file" name="foto" class="form-control input-file-custom" accept="image/*">
                    
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" name="subir_foto" class="btn btn-primary" style="flex: 1; justify-content: center;">
                            <i class="fas fa-upload"></i> <?= $has_photo ? 'Cambiar' : 'Subir' ?>
                        </button>
                        
                        <?php if($has_photo): ?>
                            <button type="submit" name="eliminar_foto" class="btn btn-danger-outline" onclick="return confirm('¿Eliminar definitivamente?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_paginas > 1): ?>
        <ul class="pagination">
            <?php for($i=1; $i<=$total_paginas; $i++): ?>
                <li class="page-item <?= ($i==$pagina)?'active':'' ?>">
                    <a class="page-link" href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>&filtro_foto=<?= $filtro_foto ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
        <?php endif; ?>

    </div>

</body>
</html>