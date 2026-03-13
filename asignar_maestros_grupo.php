<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['grupo_id']) || !is_numeric($_GET['grupo_id'])) {
    header("Location: grupos.php");
    exit();
}

$grupo_id = $_GET['grupo_id'];
$error = '';
$exito = '';

// Obtener información del grupo
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ?");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header("Location: grupos.php");
    exit();
}

// Obtener maestros ya asignados al grupo
$stmt = $pdo->prepare("SELECT maestro_id FROM grupo_maestros WHERE grupo_id = ?");
$stmt->execute([$grupo_id]);
$maestros_asignados = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Procesar asignación/eliminación de maestros
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['asignar'])) {
        $maestros_seleccionados = $_POST['maestros'] ?? [];
        
        if (!empty($maestros_seleccionados)) {
            $pdo->beginTransaction();
            try {
                // Eliminar asignaciones previas
                $stmt = $pdo->prepare("DELETE FROM grupo_maestros WHERE grupo_id = ?");
                $stmt->execute([$grupo_id]);
                
                // Insertar nuevas asignaciones
                $stmt = $pdo->prepare("INSERT INTO grupo_maestros (grupo_id, maestro_id, fecha_asignacion) VALUES (?, ?, NOW())");
                foreach ($maestros_seleccionados as $maestro_id) {
                    $stmt->execute([$grupo_id, $maestro_id]);
                }
                
                $pdo->commit();
                $exito = 'Maestros asignados exitosamente al grupo';
                
                // Actualizar lista de maestros asignados
                $maestros_asignados = $maestros_seleccionados;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Error al asignar maestros: ' . $e->getMessage();
            }
        } else {
            // Si no se seleccionó ningún maestro, eliminar todas las asignaciones
            $stmt = $pdo->prepare("DELETE FROM grupo_maestros WHERE grupo_id = ?");
            $stmt->execute([$grupo_id]);
            $exito = 'Se eliminaron todos los maestros del grupo';
            $maestros_asignados = [];
        }
    }
}

// Obtener todos los maestros disponibles
$stmt = $pdo->prepare("SELECT * FROM maestros WHERE estado = 'activo' ORDER BY apellidos, nombre");
$stmt->execute();
$maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Maestros al Grupo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .selected {
            background-color: #e7f3ff !important;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Asignar Maestros al Grupo: <?= htmlspecialchars($grupo['nombre']) ?></h2>
            <a href="ver_grupo.php?id=<?= $grupo_id ?>" class="btn btn-secondary">Volver al Grupo</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($exito): ?>
            <div class="alert alert-success"><?= $exito ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Información del Grupo</h5>
            </div>
            <div class="card-body">
                <p><strong>Nivel:</strong> <?= htmlspecialchars($grupo['nivel']) ?></p>
                <p><strong>Descripción:</strong> <?= htmlspecialchars($grupo['descripcion']) ?></p>
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Seleccionar Maestros</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">Seleccionar Todos</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">Deseleccionar Todos</button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($maestros)): ?>
                        <p class="text-muted">No hay maestros disponibles en el sistema.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="tablaMaestros" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">Seleccionar</th>
                                        <th>Nombre</th>
                                        <th>Especialidad</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($maestros as $maestro): 
                                        $asignado = in_array($maestro['id'], $maestros_asignados);
                                    ?>
                                    <tr class="<?= $asignado ? 'selected' : '' ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input" name="maestros[]" 
                                                   value="<?= $maestro['id'] ?>" 
                                                   <?= $asignado ? 'checked' : '' ?>
                                                   data-maestro-id="<?= $maestro['id'] ?>">
                                        </td>
                                        <td><?= htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellidos']) ?></td>
                                        <td><?= htmlspecialchars($maestro['especialidad']) ?></td>
                                        <td><?= htmlspecialchars($maestro['email']) ?></td>
                                        <td><?= htmlspecialchars($maestro['telefono']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $maestro['estado'] == 'activo' ? 'success' : 'danger' ?>">
                                                <?= $maestro['estado'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <button type="submit" name="asignar" class="btn btn-primary">Guardar Asignaciones</button>
                    <a href="ver_grupo.php?id=<?= $grupo_id ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        // Inicializar DataTable
        var table = $('#tablaMaestros').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
            },
            "pageLength": 10
        });
        
        // Seleccionar todos
        $('#selectAll').on('click', function() {
            $('input[name="maestros[]"]').prop('checked', true).closest('tr').addClass('selected');
        });
        
        // Deseleccionar todos
        $('#deselectAll').on('click', function() {
            $('input[name="maestros[]"]').prop('checked', false).closest('tr').removeClass('selected');
        });
        
        // Marcar fila como seleccionada
        $('input[name="maestros[]"]').on('change', function() {
            if ($(this).is(':checked')) {
                $(this).closest('tr').addClass('selected');
            } else {
                $(this).closest('tr').removeClass('selected');
            }
        });
    });
    </script>
</body>
</html>