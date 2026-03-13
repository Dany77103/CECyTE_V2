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

$grupo_id = (int)$_GET['grupo_id'];
$error = '';
$exito = '';

// Obtener información del grupo (tabla grupos)
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE id_grupo = ?");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header("Location: grupos.php");
    exit();
}

// Obtener maestros asignados al grupo (para el select)
$stmt = $pdo->prepare("
    SELECT DISTINCT
        m.id_maestro,
        m.nombre,
        CONCAT(m.apellido_paterno, ' ', m.apellido_materno) AS apellidos,
        m.especialidad
    FROM maestros m
    INNER JOIN horarios_maestros hm ON m.id_maestro = hm.id_maestro
    WHERE hm.id_grupo = ?
      AND m.activo = 'Activo'
    ORDER BY m.apellido_paterno, m.apellido_materno, m.nombre
");
$stmt->execute([$grupo_id]);
$maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener todas las materias (para el select)
$stmt = $pdo->query("
    SELECT id_materia, materia 
    FROM materias 
    ORDER BY id_semestre, materia
");
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener todas las aulas activas (para el select)
$stmt = $pdo->query("
    SELECT id_aula, nombre 
    FROM aulas 
    WHERE activo = 1 
    ORDER BY edificio, nombre
");
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el horario actual del grupo desde horarios_maestros
$stmt = $pdo->prepare("
    SELECT 
        hm.id_horario,
        hm.dia,
        hm.hora_inicio,
        hm.hora_fin,
        hm.periodo,
        hm.estatus,
        m.id_materia,
        m.materia,
        ma.id_maestro,
        CONCAT(ma.nombre, ' ', ma.apellido_paterno, ' ', ma.apellido_materno) AS nombre_maestro,
        a.id_aula,
        a.nombre AS aula_nombre,
        a.edificio
    FROM horarios_maestros hm
    INNER JOIN materias m ON hm.id_materia = m.id_materia
    INNER JOIN maestros ma ON hm.id_maestro = ma.id_maestro
    LEFT JOIN aulas a ON hm.id_aula = a.id_aula
    WHERE hm.id_grupo = ?
      AND hm.estatus = 'Activo'
    ORDER BY 
        FIELD(hm.dia, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'),
        hm.hora_inicio
");
$stmt->execute([$grupo_id]);
$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Días de la semana
$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agregar_clase'])) {
        $dia = $_POST['dia'];
        $hora_inicio = $_POST['hora_inicio'];
        $hora_fin = $_POST['hora_fin'];
        $id_materia = (int)$_POST['id_materia'];
        $id_maestro = (int)$_POST['id_maestro'];
        $id_aula = (int)$_POST['id_aula'];
        $periodo = $grupo['periodo_escolar'] ?? 'FEB 2026 - JUL 2026'; // fallback

        // Validar que no se solape con otra clase del mismo grupo en el mismo día/horario
        $stmt = $pdo->prepare("
            SELECT id_horario 
            FROM horarios_maestros 
            WHERE id_grupo = ? 
              AND dia = ? 
              AND estatus = 'Activo'
              AND (
                  (hora_inicio < ? AND hora_fin > ?) OR
                  (hora_inicio >= ? AND hora_inicio < ?)
              )
        ");
        $stmt->execute([$grupo_id, $dia, $hora_fin, $hora_inicio, $hora_inicio, $hora_fin]);
        $solapado = $stmt->fetch();

        if ($solapado) {
            $error = 'Error: El horario se solapa con otra clase existente.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO horarios_maestros 
                        (id_grupo, id_maestro, id_materia, id_aula, dia, hora_inicio, hora_fin, periodo, estatus)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Activo')
                ");
                $stmt->execute([$grupo_id, $id_maestro, $id_materia, $id_aula, $dia, $hora_inicio, $hora_fin, $periodo]);

                $exito = 'Clase agregada al horario exitosamente.';

                // Refrescar horarios
                $stmt = $pdo->prepare("
                    SELECT 
                        hm.id_horario, hm.dia, hm.hora_inicio, hm.hora_fin, hm.periodo, hm.estatus,
                        m.id_materia, m.materia,
                        ma.id_maestro, CONCAT(ma.nombre, ' ', ma.apellido_paterno, ' ', ma.apellido_materno) AS nombre_maestro,
                        a.id_aula, a.nombre AS aula_nombre, a.edificio
                    FROM horarios_maestros hm
                    INNER JOIN materias m ON hm.id_materia = m.id_materia
                    INNER JOIN maestros ma ON hm.id_maestro = ma.id_maestro
                    LEFT JOIN aulas a ON hm.id_aula = a.id_aula
                    WHERE hm.id_grupo = ? AND hm.estatus = 'Activo'
                    ORDER BY FIELD(hm.dia, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'), hm.hora_inicio
                ");
                $stmt->execute([$grupo_id]);
                $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = 'Error al agregar la clase: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['eliminar_clase'])) {
        $clase_id = (int)$_POST['clase_id'];

        try {
            // Borrado lógico: cambiar estatus a 'Inactivo' en lugar de eliminar físicamente
            $stmt = $pdo->prepare("UPDATE horarios_maestros SET estatus = 'Inactivo' WHERE id_horario = ? AND id_grupo = ?");
            $stmt->execute([$clase_id, $grupo_id]);

            $exito = 'Clase eliminada del horario.';

            // Refrescar horarios
            $stmt = $pdo->prepare("
                SELECT 
                    hm.id_horario, hm.dia, hm.hora_inicio, hm.hora_fin, hm.periodo, hm.estatus,
                    m.id_materia, m.materia,
                    ma.id_maestro, CONCAT(ma.nombre, ' ', ma.apellido_paterno, ' ', ma.apellido_materno) AS nombre_maestro,
                    a.id_aula, a.nombre AS aula_nombre, a.edificio
                FROM horarios_maestros hm
                INNER JOIN materias m ON hm.id_materia = m.id_materia
                INNER JOIN maestros ma ON hm.id_maestro = ma.id_maestro
                LEFT JOIN aulas a ON hm.id_aula = a.id_aula
                WHERE hm.id_grupo = ? AND hm.estatus = 'Activo'
                ORDER BY FIELD(hm.dia, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'), hm.hora_inicio
            ");
            $stmt->execute([$grupo_id]);
            $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = 'Error al eliminar la clase: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario del Grupo: <?= htmlspecialchars($grupo['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .horario-table td { height: 90px; vertical-align: middle; }
        .clase-item {
            background-color: #e7f3ff;
            border-radius: 5px;
            padding: 8px;
            margin: 3px 0;
            font-size: 0.85rem;
        }
        .hora-col { width: 100px; text-align: center; font-weight: bold; }
        .dia-col { background-color: #f8f9fa; }
        .form-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Horario del Grupo: <?= htmlspecialchars($grupo['nombre']) ?></h2>
            <a href="ver_grupo.php?id=<?= $grupo_id ?>" class="btn btn-secondary">Volver al Grupo</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($exito): ?>
            <div class="alert alert-success"><?= $exito ?></div>
        <?php endif; ?>

        <!-- Formulario para agregar nueva clase -->
        <div class="form-container">
            <h4>Agregar Nueva Clase</h4>
            <form method="POST" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="dia" class="form-label">Día *</label>
                    <select class="form-select" id="dia" name="dia" required>
                        <option value="">Seleccionar día</option>
                        <?php foreach ($dias_semana as $dia): ?>
                            <option value="<?= $dia ?>"><?= $dia ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="hora_inicio" class="form-label">Hora inicio *</label>
                    <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required>
                </div>

                <div class="col-md-2">
                    <label for="hora_fin" class="form-label">Hora fin *</label>
                    <input type="time" class="form-control" id="hora_fin" name="hora_fin" required>
                </div>

                <div class="col-md-3">
                    <label for="id_materia" class="form-label">Materia *</label>
                    <select class="form-select" id="id_materia" name="id_materia" required>
                        <option value="">Seleccionar materia</option>
                        <?php foreach ($materias as $mat): ?>
                            <option value="<?= $mat['id_materia'] ?>"><?= htmlspecialchars($mat['materia']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="id_maestro" class="form-label">Maestro *</label>
                    <select class="form-select" id="id_maestro" name="id_maestro" required>
                        <option value="">Seleccionar maestro</option>
                        <?php foreach ($maestros as $mae): ?>
                            <option value="<?= $mae['id_maestro'] ?>">
                                <?= htmlspecialchars($mae['nombre'] . ' ' . $mae['apellidos']) ?>
                                (<?= htmlspecialchars($mae['especialidad']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($maestros)): ?>
                        <div class="form-text text-warning">
                            No hay maestros asignados a este grupo. Debes asignar al menos uno en "Horarios Maestros".
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="id_aula" class="form-label">Aula *</label>
                    <select class="form-select" id="id_aula" name="id_aula" required>
                        <option value="">Seleccionar aula</option>
                        <?php foreach ($aulas as $aul): ?>
                            <option value="<?= $aul['id_aula'] ?>">
                                <?= htmlspecialchars($aul['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-12 d-flex align-items-end">
                    <button type="submit" name="agregar_clase" class="btn btn-primary w-100"
                        <?= empty($maestros) ? 'disabled' : '' ?>>
                        Agregar Clase
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de horario semanal -->
        <div class="card">
            <div class="card-header">
                <h4>Horario Semanal</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered horario-table">
                        <thead class="table-light">
                            <tr>
                                <th class="hora-col">Hora</th>
                                <?php foreach ($dias_semana as $dia): ?>
                                    <th class="dia-col"><?= $dia ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($hora = 7; $hora <= 20; $hora++):
                                $hora_str = sprintf("%02d:00", $hora);
                            ?>
                            <tr>
                                <td class="hora-col"><?= $hora_str ?></td>
                                <?php foreach ($dias_semana as $dia): ?>
                                    <td>
                                        <?php
                                        $clases_hora = array_filter($horarios, function($clase) use ($dia, $hora) {
                                            return $clase['dia'] == $dia &&
                                                   (int)substr($clase['hora_inicio'], 0, 2) == $hora;
                                        });
                                        foreach ($clases_hora as $clase):
                                        ?>
                                            <div class="clase-item">
                                                <strong><?= htmlspecialchars($clase['materia']) ?></strong><br>
                                                <small><?= substr($clase['hora_inicio'], 0, 5) ?> - <?= substr($clase['hora_fin'], 0, 5) ?></small><br>
                                                <small>Prof. <?= htmlspecialchars($clase['nombre_maestro']) ?></small><br>
                                                <small>Aula: <?= htmlspecialchars($clase['aula_nombre'] ?? 'Sin aula') ?></small><br>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="clase_id" value="<?= $clase['id_horario'] ?>">
                                                    <button type="submit" name="eliminar_clase" class="btn btn-sm btn-danger mt-1"
                                                            onclick="return confirm('¿Eliminar esta clase del horario?')">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <p class="text-muted mb-0">Total de clases activas: <?= count($horarios) ?></p>
            </div>
        </div>

        <!-- Lista detallada de clases -->
        <div class="card mt-4">
            <div class="card-header">
                <h4>Lista de Clases</h4>
            </div>
            <div class="card-body">
                <?php if (empty($horarios)): ?>
                    <p class="text-muted">No hay clases programadas para este grupo.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Día</th>
                                    <th>Hora</th>
                                    <th>Materia</th>
                                    <th>Maestro</th>
                                    <th>Aula</th>
                                    <th>Período</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($horarios as $clase): ?>
                                <tr>
                                    <td><?= htmlspecialchars($clase['dia']) ?></td>
                                    <td><?= substr($clase['hora_inicio'], 0, 5) ?> - <?= substr($clase['hora_fin'], 0, 5) ?></td>
                                    <td><?= htmlspecialchars($clase['materia']) ?></td>
                                    <td><?= htmlspecialchars($clase['nombre_maestro']) ?></td>
                                    <td><?= htmlspecialchars($clase['aula_nombre'] ?? 'Sin asignar') ?></td>
                                    <td><?= htmlspecialchars($clase['periodo']) ?></td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="clase_id" value="<?= $clase['id_horario'] ?>">
                                            <button type="submit" name="eliminar_clase" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('¿Eliminar esta clase?')">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validar que hora_fin > hora_inicio
        const horaInicio = document.getElementById('hora_inicio');
        const horaFin = document.getElementById('hora_fin');
        function validarHoras() {
            if (horaInicio.value && horaFin.value) {
                if (horaFin.value <= horaInicio.value) {
                    horaFin.setCustomValidity('La hora de fin debe ser mayor que la hora de inicio');
                } else {
                    horaFin.setCustomValidity('');
                }
            }
        }
        horaInicio.addEventListener('change', validarHoras);
        horaFin.addEventListener('change', validarHoras);
    </script>
</body>
</html>