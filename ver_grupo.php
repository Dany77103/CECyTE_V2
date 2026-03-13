<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: grupos.php");
    exit();
}

$grupo_id = $_GET['id'];

// Obtener información del grupo
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ?");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header("Location: grupos.php");
    exit();
}

// Obtener alumnos del grupo
$stmt = $pdo->prepare("SELECT a.* FROM alumnos a 
                      INNER JOIN grupo_alumnos ga ON a.id = ga.alumno_id 
                      WHERE ga.grupo_id = ?");
$stmt->execute([$grupo_id]);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener maestros del grupo
$stmt = $pdo->prepare("SELECT m.* FROM maestros m 
                      INNER JOIN grupo_maestros gm ON m.id = gm.maestro_id 
                      WHERE gm.grupo_id = ?");
$stmt->execute([$grupo_id]);
$maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener horario del grupo
$stmt = $pdo->prepare("SELECT * FROM horarios WHERE grupo_id = ? ORDER BY dia_semana, hora_inicio");
$stmt->execute([$grupo_id]);
$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Grupo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Grupo: <?= htmlspecialchars($grupo['nombre']) ?></h2>
            <div>
                <a href="editar_grupo.php?id=<?= $grupo_id ?>" class="btn btn-warning">Editar</a>
                <a href="grupos.php" class="btn btn-secondary">Volver</a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h4>Información General</h4>
                    <p><strong>Descripción:</strong> <?= htmlspecialchars($grupo['descripcion']) ?></p>
                    <p><strong>Nivel:</strong> <?= htmlspecialchars($grupo['nivel']) ?></p>
                    <p><strong>Año Escolar:</strong> <?= htmlspecialchars($grupo['anio_escolar']) ?></p>
                    <p><strong>Capacidad:</strong> <?= htmlspecialchars($grupo['capacidad']) ?> alumnos</p>
                    <p><strong>Creado:</strong> <?= date('d/m/Y', strtotime($grupo['fecha_creacion'])) ?></p>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="info-card">
                    <h4>Estadísticas</h4>
                    <p><strong>Alumnos inscritos:</strong> <?= count($alumnos) ?></p>
                    <p><strong>Maestros asignados:</strong> <?= count($maestros) ?></p>
                    <p><strong>Clases programadas:</strong> <?= count($horarios) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Tabs para secciones -->
        <ul class="nav nav-tabs mt-4" id="grupoTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="alumnos-tab" data-bs-toggle="tab" data-bs-target="#alumnos" type="button">Alumnos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="maestros-tab" data-bs-toggle="tab" data-bs-target="#maestros" type="button">Maestros</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="horario-tab" data-bs-toggle="tab" data-bs-target="#horario" type="button">Horario</button>
            </li>
        </ul>
        
        <div class="tab-content p-3 border border-top-0 rounded-bottom" id="grupoTabsContent">
            <!-- Tab Alumnos -->
            <div class="tab-pane fade show active" id="alumnos">
                <div class="d-flex justify-content-between mb-3">
                    <h4>Alumnos del Grupo</h4>
                    <a href="asignar_alumnos_grupo.php?grupo_id=<?= $grupo_id ?>" class="btn btn-sm btn-primary">Asignar Alumnos</a>
                </div>
                
                <?php if (empty($alumnos)): ?>
                    <p class="text-muted">No hay alumnos asignados a este grupo.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Matrícula</th>
                                    <th>Email</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alumnos as $index => $alumno): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellidos']) ?></td>
                                    <td><?= htmlspecialchars($alumno['matricula']) ?></td>
                                    <td><?= htmlspecialchars($alumno['email']) ?></td>
                                    <td>
                                        <a href="ver_alumno.php?id=<?= $alumno['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        <a href="eliminar_alumno_grupo.php?grupo_id=<?= $grupo_id ?>&alumno_id=<?= $alumno['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('¿Eliminar alumno del grupo?')">Quitar</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab Maestros -->
            <div class="tab-pane fade" id="maestros">
                <div class="d-flex justify-content-between mb-3">
                    <h4>Maestros del Grupo</h4>
                    <a href="asignar_maestros_grupo.php?grupo_id=<?= $grupo_id ?>" class="btn btn-sm btn-primary">Asignar Maestros</a>
                </div>
                
                <?php if (empty($maestros)): ?>
                    <p class="text-muted">No hay maestros asignados a este grupo.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Especialidad</th>
                                    <th>Email</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maestros as $index => $maestro): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellidos']) ?></td>
                                    <td><?= htmlspecialchars($maestro['especialidad']) ?></td>
                                    <td><?= htmlspecialchars($maestro['email']) ?></td>
                                    <td>
                                        <a href="ver_maestro.php?id=<?= $maestro['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        <a href="eliminar_maestro_grupo.php?grupo_id=<?= $grupo_id ?>&maestro_id=<?= $maestro['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('¿Eliminar maestro del grupo?')">Quitar</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab Horario -->
            <div class="tab-pane fade" id="horario">
                <div class="d-flex justify-content-between mb-3">
                    <h4>Horario del Grupo</h4>
                    <a href="horario_grupo.php?grupo_id=<?= $grupo_id ?>" class="btn btn-sm btn-primary">Gestionar Horario</a>
                </div>
                
                <?php if (empty($horarios)): ?>
                    <p class="text-muted">No hay horario asignado para este grupo.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Día</th>
                                    <th>Hora Inicio</th>
                                    <th>Hora Fin</th>
                                    <th>Materia</th>
                                    <th>Maestro</th>
                                    <th>Aula</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                foreach ($dias as $dia): 
                                    $clases_dia = array_filter($horarios, function($h) use ($dia) {
                                        return $h['dia_semana'] == $dia;
                                    });
                                    
                                    if (!empty($clases_dia)): 
                                        foreach ($clases_dia as $horario): ?>
                                        <tr>
                                            <td><strong><?= $dia ?></strong></td>
                                            <td><?= substr($horario['hora_inicio'], 0, 5) ?></td>
                                            <td><?= substr($horario['hora_fin'], 0, 5) ?></td>
                                            <td><?= htmlspecialchars($horario['materia']) ?></td>
                                            <td><?= htmlspecialchars($horario['maestro_nombre']) ?></td>
                                            <td><?= htmlspecialchars($horario['aula']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>