<?php
session_start();
require_once 'conexion.php'; // Archivo de conexión a BD

// Verificar permisos de administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: login.php");
    exit();
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $nivel = $_POST['nivel'];
    $anio_escolar = $_POST['anio_escolar'];
    $capacidad = $_POST['capacidad'];
    
    if (empty($nombre) || empty($nivel)) {
        $error = 'El nombre y nivel son obligatorios';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO grupos (nombre, descripcion, nivel, anio_escolar, capacidad, fecha_creacion) 
                                   VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$nombre, $descripcion, $nivel, $anio_escolar, $capacidad]);
            
            $exito = 'Grupo creado exitosamente';
            // Opcional: redirigir después de éxito
            // header("Location: ver_grupo.php?id=" . $pdo->lastInsertId());
        } catch (PDOException $e) {
            $error = 'Error al crear el grupo: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Grupo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h2 class="mb-4">Crear Nuevo Grupo</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if ($exito): ?>
                    <div class="alert alert-success"><?= $exito ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Grupo *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nivel" class="form-label">Nivel *</label>
                            <select class="form-select" id="nivel" name="nivel" required>
                                <option value="">Seleccionar nivel</option>
                                <option value="Primaria">Primaria</option>
                                <option value="Secundaria">Secundaria</option>
                                <option value="Bachillerato">Bachillerato</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="anio_escolar" class="form-label">Año Escolar</label>
                            <input type="text" class="form-control" id="anio_escolar" name="anio_escolar" 
                                   placeholder="Ej: 2024-2025">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="capacidad" class="form-label">Capacidad Máxima</label>
                        <input type="number" class="form-control" id="capacidad" name="capacidad" min="1" value="30">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Crear Grupo</button>
                    <a href="grupos.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>