<?php
// gestionar_fotos.php - Versión organizada y corregida
session_start();
require_once 'conexion.php';

// ==============================================
// 1. CONFIGURACIÓN Y VERIFICACIONES INICIALES
// ==============================================

// Activar errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// ==============================================
// 2. FUNCIONES AUXILIARES
// ==============================================

/**
 * Función para optimizar/redimensionar imágenes
 */
function optimizarImagen($ruta, $ancho_max = 800, $alto_max = 800, $calidad = 85) {
    // Verificar si GD está instalado
    if (!function_exists('gd_info')) {
        error_log("GD library no está instalada. Saltando optimización.");
        return false;
    }
    
    $info = @getimagesize($ruta);
    if (!$info) {
        error_log("No se pudo obtener información de la imagen: $ruta");
        return false;
    }
    
    $mime = $info['mime'];
    $ancho_orig = $info[0];
    $alto_orig = $info[1];
    
    // Si la imagen es más pequeña que los máximos, no redimensionar
    if ($ancho_orig <= $ancho_max && $alto_orig <= $alto_max) {
        return false;
    }
    
    // Crear imagen según el tipo
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($ruta);
            break;
        case 'image/png':
            $image = imagecreatefrompng($ruta);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($ruta);
            break;
        default:
            error_log("Tipo de imagen no soportado: $mime");
            return false;
    }
    
    if (!$image) {
        error_log("No se pudo crear imagen desde: $ruta");
        return false;
    }
    
    // Calcular nuevas dimensiones manteniendo proporción
    $ratio_orig = $ancho_orig / $alto_orig;
    
    if ($ancho_max / $alto_max > $ratio_orig) {
        $ancho_max = $alto_max * $ratio_orig;
    } else {
        $alto_max = $ancho_max / $ratio_orig;
    }
    
    // Crear nueva imagen
    $image_nueva = imagecreatetruecolor($ancho_max, $alto_max);
    
    // Preservar transparencia para PNG y GIF
    if ($mime == 'image/png' || $mime == 'image/gif') {
        imagecolortransparent($image_nueva, imagecolorallocatealpha($image_nueva, 0, 0, 0, 127));
        imagealphablending($image_nueva, false);
        imagesavealpha($image_nueva, true);
    }
    
    // Redimensionar
    imagecopyresampled($image_nueva, $image, 0, 0, 0, 0, 
                      $ancho_max, $alto_max, $ancho_orig, $alto_orig);
    
    // Guardar imagen optimizada
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($image_nueva, $ruta, $calidad);
            break;
        case 'image/png':
            imagepng($image_nueva, $ruta, 9);
            break;
        case 'image/gif':
            imagegif($image_nueva, $ruta);
            break;
    }
    
    // Liberar memoria
    imagedestroy($image);
    imagedestroy($image_nueva);
    
    return true;
}

// ==============================================
// 3. PROCESAMIENTO DE ACCIONES
// ==============================================

// Variables de configuración
$directorio_imagenes = 'img/alumnos/';
$max_tamano = 5 * 1024 * 1024; // 5MB
$formatos_permitidos = array('jpg', 'jpeg', 'png', 'gif');

// A. PROCESAR SUBIDA DE FOTO (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subir_foto'])) {
    error_log("=== INICIANDO SUBIDA DE FOTO ===");
    
    $matricula = trim($_POST['matricula']);
    $foto = $_FILES['foto'];
    
    // Validar matrícula
    if (empty($matricula)) {
        $_SESSION['error'] = "La matrícula es requerida.";
        header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
        exit();
    }
    
    // Validar archivo
    if (!isset($foto) || $foto['error'] !== UPLOAD_ERR_OK) {
        $mensajes_error = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario.',
            UPLOAD_ERR_PARTIAL => 'El archivo solo se subió parcialmente.',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'No se encontró el directorio temporal.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.'
        ];
        
        $error_code = $foto['error'] ?? UPLOAD_ERR_NO_FILE;
        $_SESSION['error'] = $mensajes_error[$error_code] ?? "Error desconocido al subir el archivo.";
        header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
        exit();
    }
    
    // 1. CREAR DIRECTORIO SI NO EXISTE
    if (!file_exists($directorio_imagenes)) {
        if (!mkdir($directorio_imagenes, 0755, true)) {
            $_SESSION['error'] = "No se pudo crear el directorio para las imágenes.";
            header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
            exit();
        }
    }
    
    // 2. VERIFICAR PERMISOS DEL DIRECTORIO
    if (!is_writable($directorio_imagenes)) {
        $_SESSION['error'] = "El directorio de imágenes no tiene permisos de escritura.";
        header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
        exit();
    }
    
    // 3. VERIFICAR QUE EL ALUMNO EXISTE
    try {
        $sql = "SELECT matricula, nombre, apellido_paterno FROM alumnos WHERE matricula = ?";
        $stmt = $con->prepare($sql);
        $stmt->execute([$matricula]);
        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$alumno) {
            $_SESSION['error'] = "No se encontró el alumno con matrícula: $matricula";
            header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al verificar el alumno: " . $e->getMessage();
        header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
        exit();
    }
    
    // 4. PROCESAR LA FOTO
    $nombre_archivo = $foto['name'];
    $tamano_archivo = $foto['size'];
    $archivo_temporal = $foto['tmp_name'];
    
    // Validar tamaño
    if ($tamano_archivo > $max_tamano) {
        $_SESSION['error'] = "El archivo es demasiado grande. Tamaño máximo: 5MB.";
        header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
        exit();
    }
    
    // Obtener extensión
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
    
    // Validar formato
    if (!in_array($extension, $formatos_permitidos)) {
        $_SESSION['error'] = "Formato de archivo no permitido. Formatos aceptados: " . implode(', ', $formatos_permitidos);
        header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
        exit();
    }
    
    // Verificar si es realmente una imagen
    $info_imagen = @getimagesize($archivo_temporal);
    if (!$info_imagen) {
        $_SESSION['error'] = "El archivo no es una imagen válida.";
        header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
        exit();
    }
    
    // 5. GENERAR NOMBRE ÚNICO Y RUTA
    $nombre_unico = $matricula . '_' . time() . '.' . $extension;
    $ruta_destino = $directorio_imagenes . $nombre_unico;
    
    // 6. MOVER ARCHIVO AL DIRECTORIO DESTINO
    if (!move_uploaded_file($archivo_temporal, $ruta_destino)) {
        $_SESSION['error'] = "Error al mover el archivo subido. Verifica los permisos del directorio.";
        error_log("Error move_uploaded_file: " . print_r(error_get_last(), true));
        header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
        exit();
    }
    
    // Cambiar permisos del archivo
    chmod($ruta_destino, 0644);
    
    // 7. OPTIMIZAR IMAGEN
    optimizarImagen($ruta_destino, 800, 800, 85);
    
    // 8. ELIMINAR FOTO ANTERIOR SI EXISTE
    try {
        $sql_select = "SELECT rutaImagen FROM alumnos WHERE matricula = ?";
        $stmt_select = $con->prepare($sql_select);
        $stmt_select->execute([$matricula]);
        $foto_anterior = $stmt_select->fetch(PDO::FETCH_ASSOC);
        
        if ($foto_anterior && !empty($foto_anterior['rutaImagen']) && file_exists($foto_anterior['rutaImagen'])) {
            if (unlink($foto_anterior['rutaImagen'])) {
                error_log("Foto anterior eliminada: " . $foto_anterior['rutaImagen']);
            }
        }
    } catch (Exception $e) {
        // Continuar aunque falle la eliminación de la foto anterior
        error_log("Error al eliminar foto anterior: " . $e->getMessage());
    }
    
    // 9. ACTUALIZAR BASE DE DATOS
    try {
        // Verificar si existe columna fecha_actualizacion_foto
        $stmt_check = $con->query("SHOW COLUMNS FROM alumnos LIKE 'fecha_actualizacion_foto'");
        $columna_existe = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($columna_existe) {
            $sql_update = "UPDATE alumnos SET rutaImagen = ?, fecha_actualizacion_foto = NOW() WHERE matricula = ?";
        } else {
            $sql_update = "UPDATE alumnos SET rutaImagen = ? WHERE matricula = ?";
        }
        
        $stmt_update = $con->prepare($sql_update);
        if ($stmt_update->execute([$ruta_destino, $matricula])) {
            $_SESSION['success'] = "? Foto subida exitosamente para: " . 
                                   htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno']);
            error_log("SUCCESS: Foto actualizada para matrícula: $matricula");
        } else {
            $errorInfo = $stmt_update->errorInfo();
            throw new Exception("Error UPDATE: " . $errorInfo[2]);
        }
        
    } catch (Exception $e) {
        // Si falla la BD, eliminar la imagen subida
        if (file_exists($ruta_destino)) {
            unlink($ruta_destino);
        }
        $_SESSION['error'] = "Error al actualizar la base de datos: " . $e->getMessage();
    }
    
    // Redirigir para evitar reenvío del formulario
    header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
    exit();
}

// B. PROCESAR ELIMINACIÓN DE FOTO (GET)
if (isset($_GET['eliminar_foto'])) {
    $matricula_eliminar = trim($_GET['eliminar_foto']);
    
    try {
        // Obtener ruta de la foto actual
        $sql = "SELECT rutaImagen, nombre, apellido_paterno FROM alumnos WHERE matricula = ?";
        $stmt = $con->prepare($sql);
        $stmt->execute([$matricula_eliminar]);
        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($alumno) {
            if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])) {
                // Eliminar archivo físico
                unlink($alumno['rutaImagen']);
                
                // Actualizar base de datos
                $sql_update = "UPDATE alumnos SET rutaImagen = NULL, fecha_actualizacion_foto = NULL WHERE matricula = ?";
                $stmt_update = $con->prepare($sql_update);
                $stmt_update->execute([$matricula_eliminar]);
                
                $_SESSION['success'] = "? Foto eliminada correctamente para: " . 
                                       htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno']);
            } else {
                $_SESSION['error'] = "No se encontró foto para eliminar.";
            }
        } else {
            $_SESSION['error'] = "No se encontró el alumno.";
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al eliminar la foto: " . $e->getMessage();
    }
    
    header("Location: gestionar_fotos.php?" . $_SERVER['QUERY_STRING']);
    exit();
}

// ==============================================
// 4. CONFIGURACIÓN DE PAGINACIÓN Y FILTROS
// ==============================================

$por_pagina = 12;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

// Variables de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$tipo_busqueda = isset($_GET['tipo_busqueda']) ? $_GET['tipo_busqueda'] : 'matricula';
$filtro_foto = isset($_GET['filtro_foto']) ? $_GET['filtro_foto'] : 'todos';

// Construir consulta base
$sql_base = "SELECT matricula, nombre, apellido_paterno, apellido_materno, 
                    rutaImagen, correo_institucional, fecha_actualizacion_foto,
                    CASE 
                        WHEN rutaImagen IS NOT NULL AND rutaImagen != '' THEN 'con_foto'
                        ELSE 'sin_foto'
                    END as estado_foto
             FROM alumnos 
             WHERE 1=1";
$params = [];
$tipos = [];

// Aplicar filtro de búsqueda
if (!empty($busqueda)) {
    if ($tipo_busqueda == 'matricula') {
        $sql_base .= " AND matricula LIKE ?";
        $params[] = "%$busqueda%";
        $tipos[] = PDO::PARAM_STR;
    } elseif ($tipo_busqueda == 'apellido_paterno') {
        $sql_base .= " AND apellido_paterno LIKE ?";
        $params[] = "%$busqueda%";
        $tipos[] = PDO::PARAM_STR;
    } elseif ($tipo_busqueda == 'nombre') {
        $sql_base .= " AND nombre LIKE ?";
        $params[] = "%$busqueda%";
        $tipos[] = PDO::PARAM_STR;
    }
}

// Aplicar filtro por estado de foto
if ($filtro_foto != 'todos') {
    if ($filtro_foto == 'con_foto') {
        $sql_base .= " AND rutaImagen IS NOT NULL AND rutaImagen != ''";
    } elseif ($filtro_foto == 'sin_foto') {
        $sql_base .= " AND (rutaImagen IS NULL OR rutaImagen = '')";
    }
}

$sql_base .= " ORDER BY apellido_paterno, apellido_materno, nombre";

// ==============================================
// 5. OBTENER DATOS PARA LA VISTA
// ==============================================

$total_registros = 0;
$alumnos = [];
$con_foto = 0;
$sin_foto = 0;

try {
    // Contar total de registros para paginación
    $sql_count = "SELECT COUNT(*) as total FROM ($sql_base) as subquery";
    $stmt_count = $con->prepare($sql_count);
    
    // Vincular parámetros si existen
    foreach ($params as $i => $param) {
        $stmt_count->bindValue($i + 1, $param, $tipos[$i]);
    }
    
    $stmt_count->execute();
    $total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $por_pagina);
    
    // Ajustar página actual si es mayor que el total
    if ($pagina_actual > $total_paginas && $total_paginas > 0) {
        $pagina_actual = $total_paginas;
    }
    
    // Calcular offset
    $offset = ($pagina_actual - 1) * $por_pagina;
    
    // Consulta con paginación
    $sql = $sql_base . " LIMIT ? OFFSET ?";
    $stmt = $con->prepare($sql);
    
    // Vincular parámetros de búsqueda
    $param_index = 1;
    foreach ($params as $i => $param) {
        $stmt->bindValue($param_index++, $param, $tipos[$i]);
    }
    
    // Vincular parámetros de paginación
    $stmt->bindValue($param_index++, $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue($param_index, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar alumnos con y sin foto
    foreach ($alumnos as $alumno) {
        if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])) {
            $con_foto++;
        } else {
            $sin_foto++;
        }
    }
    
} catch (PDOException $e) {
    die("Error al obtener alumnos: " . $e->getMessage());
}

// ==============================================
// 6. HTML - VISTA
// ==============================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Fotos de Alumnos</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
        }
        .foto-alumno {
            width: 100%;
            height: 100%;
            object-fit: cover;
    }
        .card-alumno {
            transition: transform 0.2s;
            height: 100%;
        }
        .card-alumno:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-eliminar {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .upload-form {
            margin-top: 10px;
        }
        .file-input-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        .file-input-label {
            display: block;
            padding: 8px 15px;
            background: #28a745;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            transition: background 0.3s;
        }
        .file-input-label:hover {
            background: #218838;
        }
        .file-input-container input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .badge-foto {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
        }
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Mensajes de éxito/error -->
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
        
        <!-- Encabezado -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h3 mb-0">?? Gestión de Fotos de Alumnos</h1>
                <p class="text-muted">Administra las fotografías de los estudiantes del sistema</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="card bg-light">
                    <div class="card-body py-2">
                        <small class="text-muted">Total: <?php echo $total_registros; ?> alumnos</small> | 
                        <span class="text-success">Con foto: <?php echo $con_foto; ?></span> | 
                        <span class="text-danger">Sin foto: <?php echo $sin_foto; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Barra de búsqueda y filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="gestionar_fotos.php" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class='bx bx-search'></i></span>
                            <input type="text" class="form-control" name="busqueda" 
                                   value="<?php echo htmlspecialchars($busqueda); ?>" 
                                   placeholder="Buscar alumno...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="tipo_busqueda">
                            <option value="matricula" <?php echo $tipo_busqueda == 'matricula' ? 'selected' : ''; ?>>Matrícula</option>
                            <option value="nombre" <?php echo $tipo_busqueda == 'nombre' ? 'selected' : ''; ?>>Nombre</option>
                            <option value="apellido_paterno" <?php echo $tipo_busqueda == 'apellido_paterno' ? 'selected' : ''; ?>>Apellido Paterno</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="filtro_foto">
                            <option value="todos" <?php echo $filtro_foto == 'todos' ? 'selected' : ''; ?>>Todos los alumnos</option>
                            <option value="con_foto" <?php echo $filtro_foto == 'con_foto' ? 'selected' : ''; ?>>Solo con foto</option>
                            <option value="sin_foto" <?php echo $filtro_foto == 'sin_foto' ? 'selected' : ''; ?>>Solo sin foto</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class='bx bx-filter'></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista de alumnos -->
        <div class="row">
            <?php foreach ($alumnos as $alumno): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card card-alumno">
                        <!-- Badge estado foto -->
                        <?php if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])): ?>
                            <span class="badge bg-success badge-foto">? Con foto</span>
                        <?php else: ?>
                            <span class="badge bg-danger badge-foto">? Sin foto</span>
                        <?php endif; ?>
                        
                        <!-- Foto del alumno -->
                        <div class="text-center p-3">
                            <div class="foto-container mx-auto">
                                <?php if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])): ?>
                                    <img src="<?php echo htmlspecialchars($alumno['rutaImagen']); ?>" 
                                         alt="Foto de <?php echo htmlspecialchars($alumno['nombre']); ?>"
                                         class="foto-alumno">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100">
                                        <i class='bx bx-user text-muted' style="font-size: 48px;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Información del alumno -->
                        <div class="card-body text-center">
                            <h6 class="card-title mb-1">
                                <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno']); ?>
                            </h6>
                            <p class="card-text text-muted small mb-1">
                                <?php echo htmlspecialchars($alumno['matricula']); ?>
                            </p>
                            <?php if (!empty($alumno['correo_institucional'])): ?>
                                <p class="card-text text-muted small mb-2">
                                    <?php echo htmlspecialchars($alumno['correo_institucional']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Botones de acción -->
                            <div class="d-grid gap-2">
                                <!-- Formulario para subir foto -->
                                <form method="POST" action="gestionar_fotos.php" enctype="multipart/form-data" 
                                      class="upload-form" onsubmit="return validarFormulario(this, '<?php echo $alumno['matricula']; ?>')">
                                    <input type="hidden" name="matricula" value="<?php echo htmlspecialchars($alumno['matricula']); ?>">
                                    
                                    <div class="file-input-container mb-2">
                                        <input type="file" class="form-control" name="foto" 
                                               accept=".jpg,.jpeg,.png,.gif" 
                                               id="foto_<?php echo $alumno['matricula']; ?>"
                                               onchange="mostrarPrevisualizacion(this, '<?php echo $alumno['matricula']; ?>')">
                                        <label for="foto_<?php echo $alumno['matricula']; ?>" class="file-input-label">
                                            <i class='bx bx-cloud-upload'></i> Seleccionar Foto
                                        </label>
                                    </div>
                                    
                                    <!-- Previsualización -->
                                    <div id="preview_<?php echo $alumno['matricula']; ?>" class="mb-2 text-center" style="display: none;">
                                        <img id="imgPreview_<?php echo $alumno['matricula']; ?>" 
                                             src="#" 
                                             alt="Previsualización" 
                                             style="max-width: 100%; border-radius: 5px; max-height: 80px;">
                                    </div>
                                    
                                    <button type="submit" name="subir_foto" 
                                            class="btn btn-success btn-sm w-100" 
                                            id="btn_subir_<?php echo $alumno['matricula']; ?>"
                                            disabled>
                                        <i class='bx bx-upload'></i> Subir Foto
                                    </button>
                                </form>
                                
                                <!-- Botón para eliminar foto (solo si tiene foto) -->
                                <?php if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])): ?>
                                    <a href="gestionar_fotos.php?eliminar_foto=<?php echo urlencode($alumno['matricula']); ?>&<?php echo $_SERVER['QUERY_STRING']; ?>" 
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('¿Estás seguro de eliminar la foto de <?php echo addslashes($alumno['nombre'] . ' ' . $alumno['apellido_paterno']); ?>?')">
                                        <i class='bx bx-trash'></i> Eliminar Foto
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Fecha de actualización -->
                        <?php if (!empty($alumno['fecha_actualizacion_foto'])): ?>
                            <div class="card-footer text-center py-1">
                                <small class="text-muted">
                                    <i class='bx bx-time'></i> 
                                    <?php echo date('d/m/Y', strtotime($alumno['fecha_actualizacion_foto'])); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($alumnos)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class='bx bx-info-circle'></i> No se encontraron alumnos con los filtros aplicados.
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <div class="pagination-container">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($pagina_actual > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="gestionar_fotos.php?pagina=1&<?php echo http_build_query($_GET); ?>">
                                    <i class='bx bx-chevrons-left'></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="gestionar_fotos.php?pagina=<?php echo $pagina_actual - 1; ?>&<?php echo http_build_query($_GET); ?>">
                                    <i class='bx bx-chevron-left'></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        // Mostrar números de página
                        $inicio = max(1, $pagina_actual - 2);
                        $fin = min($total_paginas, $pagina_actual + 2);
                        
                        for ($i = $inicio; $i <= $fin; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                                <a class="page-link" href="gestionar_fotos.php?pagina=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="gestionar_fotos.php?pagina=<?php echo $pagina_actual + 1; ?>&<?php echo http_build_query($_GET); ?>">
                                    <i class='bx bx-chevron-right'></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="gestionar_fotos.php?pagina=<?php echo $total_paginas; ?>&<?php echo http_build_query($_GET); ?>">
                                    <i class='bx bx-chevrons-right'></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // JavaScript para validación en el cliente
    function mostrarPrevisualizacion(input, matricula) {
        const file = input.files[0];
        const btnSubir = document.getElementById('btn_subir_' + matricula);
        const preview = document.getElementById('preview_' + matricula);
        const imgPreview = document.getElementById('imgPreview_' + matricula);
        
        if (file) {
            // Validar tamaño (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('El archivo es demasiado grande. Tamaño máximo: 5MB');
                input.value = '';
                btnSubir.disabled = true;
                preview.style.display = 'none';
                return;
            }
            
            // Validar tipo de archivo
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                alert('Formato de archivo no permitido. Use JPG, PNG o GIF.');
                input.value = '';
                btnSubir.disabled = true;
                preview.style.display = 'none';
                return;
            }
            
            // Mostrar previsualización
            const reader = new FileReader();
            reader.onload = function(e) {
                imgPreview.src = e.target.result;
                preview.style.display = 'block';
                btnSubir.disabled = false;
            }
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
            btnSubir.disabled = true;
        }
    }
    
    function validarFormulario(form, matricula) {
        const inputFile = form.querySelector('input[type="file"]');
        const btnSubir = document.getElementById('btn_subir_' + matricula);
        
        if (!inputFile.files[0]) {
            alert('Por favor seleccione una foto');
            return false;
        }
        
        // Mostrar mensaje de carga
        if (btnSubir) {
            btnSubir.innerHTML = '<i class="bx bx-loader bx-spin"></i> Subiendo...';
            btnSubir.disabled = true;
        }
        
        return true;
    }
    
    // Auto-cerrar alertas después de 5 segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    </script>
</body>
</html>
