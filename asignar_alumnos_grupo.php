<?php
session_start();
require_once 'conexion.php';

// 1. VERIFICACIÓN DE PERMISOS (Actualizada para coincidir con tu sistema)
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// 2. COMPATIBILIDAD DE VARIABLE DE CONEXIÓN
// Tu código usa $pdo, pero tu conexion.php usa $con. Esto lo soluciona:
$pdo = $con; 

// 3. VERIFICACIÓN DEL ID (Corregida la ruta de redirección)
if (!isset($_GET['id_grupo']) || !is_numeric($_GET['id_grupo'])) {
    header("Location: gestion_grupos.php"); // Eliminada la 'r' de gestionar
    exit();
}

$grupo_id = $_GET['id_grupo'];
$error = '';
$exito = '';

// --- TODA LA LÓGICA ORIGINAL SE MANTIENE IGUAL ---

// Obtener información del grupo
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE id_grupo = ?");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header("Location: gestion_grupos.php");
    exit();
}

// Obtener alumnos ya asignados
$stmt = $pdo->prepare("SELECT id_alumno FROM alumnos WHERE id_grupo = ?");
$stmt->execute([$grupo_id]);
$alumnos_asignados = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Procesar asignación/eliminación
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asignar'])) {
    $alumnos_seleccionados = $_POST['alumnos'] ?? [];
    
    // Validar capacidad (Usando el campo 'capacidad_maxima' si así está en tu DB, o 'capacidad' como estaba en tu código)
    $capacidad_campo = isset($grupo['capacidad_maxima']) ? $grupo['capacidad_maxima'] : $grupo['capacidad'];

    if (count($alumnos_seleccionados) > $capacidad_campo) {
        $error = "Error: La capacidad máxima del grupo es {$capacidad_campo} alumnos. Has seleccionado " . count($alumnos_seleccionados) . ".";
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE alumnos SET id_grupo = NULL WHERE id_grupo = ?");
            $stmt->execute([$grupo_id]);
            
            if (!empty($alumnos_seleccionados)) {
                $placeholders = rtrim(str_repeat('?,', count($alumnos_seleccionados)), ',');
                $sql = "UPDATE alumnos SET id_grupo = ? WHERE id_alumno IN ($placeholders)";
                $params = array_merge([$grupo_id], $alumnos_seleccionados);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
            
            $pdo->commit();
            $exito = 'Alumnos asignados exitosamente al grupo';
            $alumnos_asignados = $alumnos_seleccionados;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error al asignar alumnos: ' . $e->getMessage();
        }
    }
}

// Obtener todos los alumnos activos
$stmt = $pdo->prepare("
    SELECT 
        id_alumno, matricula, nombre, apellido_paterno, apellido_materno,
        correo_institucional AS email, telefono_celular AS telefono, activo AS estado
    FROM alumnos 
    WHERE activo = 'Activo' 
    ORDER BY apellido_paterno, apellido_materno, nombre
");
$stmt->execute();
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Alumnos al Grupo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .selected { background-color: #e7f3ff !important; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Asignar Alumnos al Grupo: <?= htmlspecialchars($grupo['nombre']) ?></h2>
            <a href="gestion_grupos.php" class="btn btn-secondary">Volver al Listado</a>
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
                <p><strong>Capacidad:</strong> <?= htmlspecialchars($capacidad_campo) ?> alumnos</p>
                <p><strong>Alumnos actuales:</strong> <?= count($alumnos_asignados) ?> / <?= htmlspecialchars($capacidad_campo) ?></p>
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Seleccionar Alumnos</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">Seleccionar Todos</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">Deseleccionar Todos</button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($alumnos)): ?>
                        <p class="text-muted">No hay alumnos disponibles.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="tablaAlumnos" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">Seleccionar</th>
                                        <th>Nombre</th>
                                        <th>Matrícula</th>
                                        <th>Email</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alumnos as $alumno): 
                                        $asignado = in_array($alumno['id_alumno'], $alumnos_asignados);
                                        $nombre_completo = $alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'] . ' ' . $alumno['nombre'];
                                    ?>
                                    <tr class="<?= $asignado ? 'selected' : '' ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input" name="alumnos[]" 
                                                   value="<?= $alumno['id_alumno'] ?>" 
                                                   <?= $asignado ? 'checked' : '' ?>>
                                        </td>
                                        <td><?= htmlspecialchars($nombre_completo) ?></td>
                                        <td><?= htmlspecialchars($alumno['matricula']) ?></td>
                                        <td><?= htmlspecialchars($alumno['email']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $alumno['estado'] == 'Activo' ? 'success' : 'danger' ?>">
                                                <?= $alumno['estado'] ?>
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
                    <a href="gestion_grupos.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        var table = $('#tablaAlumnos').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json" },
            "pageLength": 10
        });
        
        $('#selectAll').on('click', function() {
            $('input[name="alumnos[]"]').prop('checked', true).closest('tr').addClass('selected');
        });
        
        $('#deselectAll').on('click', function() {
            $('input[name="alumnos[]"]').prop('checked', false).closest('tr').removeClass('selected');
        });
        
        $('input[name="alumnos[]"]').on('change', function() {
            $(this).closest('tr').toggleClass('selected', $(this).is(':checked'));
        });
    });
    </script>
</body>
</html>