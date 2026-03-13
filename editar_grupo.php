<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: grupos.php");
    exit();
}

$grupo_id = $_GET['id'];
$error = '';
$exito = '';

// Obtener datos actuales del grupo
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ?");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header("Location: grupos.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $nivel = $_POST['nivel'];
    $anio_escolar = $_POST['anio_escolar'];
    $capacidad = $_POST['capacidad'];
    $estado = $_POST['estado'];
    
    if (empty($nombre) || empty($nivel)) {
        $error = 'El nombre y nivel son obligatorios';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE grupos SET 
                                   nombre = ?, 
                                   descripcion = ?, 
                                   nivel = ?, 
                                   anio_escolar = ?, 
                                   capacidad = ?,
                                   estado = ?,
                                   fecha_actualizacion = NOW()
                                   WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $nivel, $anio_escolar, $capacidad, $estado, $grupo_id]);
            
            $exito = 'Grupo actualizado exitosamente';
            
            // Actualizar datos del grupo en la variable
            $grupo = array_merge($grupo, [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'nivel' => $nivel,
                'anio_escolar' => $anio_escolar,
                'capacidad' => $capacidad,
                'estado' => $estado
            ]);
        } catch (PDOException $e) {
            $error = 'Error al actualizar el grupo: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Grupo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h2 class="mb-4">Editar Grupo: <?= htmlspecialchars($grupo['nombre']) ?></h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if ($exito): ?>
                    <div class="alert alert-success"><?= $exito ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Grupo *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?= htmlspecialchars($grupo['nombre']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($grupo['descripcion']) ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nivel" class="form-label">Nivel *</label>
                            <select class="form-select" id="nivel" name="nivel" required>
                                <option value="">Seleccionar nivel</option>
                                <option value="Primaria" <?= $grupo['nivel'] == 'Primaria' ? 'selected' : '' ?>>Primaria</option>
                                <option value="Secundaria" <?= $grupo['nivel'] == 'Secundaria' ? 'selected' : '' ?>>Secundaria</option>
                                <option value="Bachillerato" <?= $grupo['nivel'] == 'Bachillerato' ? 'selected' : '' ?>>Bachillerato</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="anio_escolar" class="form-label">Año Escolar</label>
                            <input type="text" class="form-control" id="anio_escolar" name="anio_escolar" 
                                   value="<?= htmlspecialchars($grupo['anio_escolar']) ?>"
                                   placeholder="Ej: 2024-2025">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="capacidad" class="form-label">Capacidad Máxima</label>
                            <input type="number" class="form-control" id="capacidad" name="capacidad" 
                                   min="1" value="<?= htmlspecialchars($grupo['capacidad']) ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="activo" <?= $grupo['estado'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= $grupo['estado'] == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                <option value="completado" <?= $grupo['estado'] == 'completado' ? 'selected' : '' ?>>Completado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <p><strong>Fecha creación:</strong> <?= date('d/m/Y H:i', strtotime($grupo['fecha_creacion'])) ?></p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Actualizar Grupo</button>
                    <a href="ver_grupo.php?id=<?= $grupo_id ?>" class="btn btn-secondary">Cancelar</a>
                    <a href="grupos.php" class="btn btn-link">Volver a lista</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>