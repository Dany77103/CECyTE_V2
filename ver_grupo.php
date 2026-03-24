<?php
session_start();
require_once 'conexion.php';

// ESTA ES LA CLAVE: Usar la sesión que tu sistema reconoce
if (!isset($_SESSION['loggedin']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php'); // Si falla, te manda al login, no al main
    exit();
}

// Capturamos el ID que viene de gestion_grupos.php
$id_grupo = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_grupo === 0) {
    header('Location: gestion_grupos.php');
    exit();
}

try {
    // Consulta para traer datos del grupo y nombre de la carrera
    $query = "SELECT g.*, c.nombre as carrera_nombre 
              FROM grupos g 
              LEFT JOIN carreras c ON g.id_carrera = c.id_carrera 
              WHERE g.id_grupo = ?";
    $stmt = $con->prepare($query);
    $stmt->execute([$id_grupo]);
    $grupo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$grupo) {
        header('Location: gestion_grupos.php?error=no_existe');
        exit();
    }

    // Consulta para traer la lista de alumnos de este grupo
    $stmtAlumnos = $con->prepare("SELECT matricula, nombre, apellido_paterno, apellido_materno 
                                 FROM alumnos 
                                 WHERE id_grupo = ? AND activo = 'Activo'");
    $stmtAlumnos->execute([$id_grupo]);
    $alumnos = $stmtAlumnos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGA-CECYTE | Detalle de Grupo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --cecyte-green: #1a5330; }
        .card-header { background-color: var(--cecyte-green); color: white; border-radius: 15px 15px 0 0 !important; }
        .btn-back { background-color: #6c757d; color: white; border-radius: 10px; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow border-0" style="border-radius: 15px;">
            <div class="card-header p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="fas fa-users me-2"></i> Grupo: <?= htmlspecialchars($grupo['nombre']) ?></h2>
                    <a href="gestion_grupos.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Carrera:</strong> <?= htmlspecialchars($grupo['carrera_nombre'] ?? 'No asignada') ?></p>
                        <p><strong>Turno:</strong> <?= htmlspecialchars($grupo['turno'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Estado:</strong> <span class="badge bg-success">Activo</span></p>
                        <p><strong>Total Alumnos:</strong> <?= count($alumnos) ?></p>
                    </div>
                </div>

                <h4 class="text-secondary border-bottom pb-2">Lista de Alumnos Inscritos</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Nombre Completo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumnos as $al): ?>
                            <tr>
                                <td><?= htmlspecialchars($al['matricula']) ?></td>
                                <td><?= htmlspecialchars($al['apellido_paterno'] . " " . $al['apellido_materno'] . " " . $al['nombre']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($alumnos)): ?>
                                <tr><td colspan="2" class="text-center">No hay alumnos en este grupo.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>