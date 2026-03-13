<?php
// horario_maestros_captura.php - VERSIÓN PDO
session_start();

// Incluir configuraciones
require_once 'config.php';
require_once 'conexion.php';

// Verificar si es administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Verificar que la conexión se haya establecido
if (!isset($con)) {
    die("Error: No se pudo conectar a la base de datos. Verifica el archivo conexion.php");
}

// Obtener maestros activos
$query_maestros = "SELECT m.* FROM maestros m WHERE m.activo = 'Activo' ORDER BY m.apellido_paterno, m.nombre";
$maestros_result = $con->query($query_maestros);

if (!$maestros_result) {
    die("Error en consulta de maestros: " . print_r($con->errorInfo(), true));
}

// Obtener materias
$query_materias = "SELECT * FROM materias ORDER BY materia";
$materias_result = $con->query($query_materias);
$materias = [];
if ($materias_result) {
    while ($row = $materias_result->fetch(PDO::FETCH_ASSOC)) {
        $materias[$row['id_materia']] = $row;
    }
}

// Obtener grupos
$query_grupos = "SELECT * FROM grupos WHERE activo = 1 ORDER BY semestre, nombre";
$grupos_result = $con->query($query_grupos);
$grupos = [];
if ($grupos_result) {
    while ($row = $grupos_result->fetch(PDO::FETCH_ASSOC)) {
        $grupos[$row['id_grupo']] = $row;
    }
}

// Verificar si existe la tabla aulas
try {
    $table_check = $con->query("SHOW TABLES LIKE 'aulas'");
    $aulas_existe = $table_check->rowCount() > 0;
} catch (PDOException $e) {
    $aulas_existe = false;
}

$aulas = [];
if ($aulas_existe) {
    $query_aulas = "SELECT * FROM aulas WHERE activo = 1 ORDER BY nombre";
    $aulas_result = $con->query($query_aulas);
    if ($aulas_result) {
        while ($row = $aulas_result->fetch(PDO::FETCH_ASSOC)) {
            $aulas[$row['id_aula']] = $row;
        }
    }
} else {
    $aulas = [
        1 => ['id_aula' => 1, 'nombre' => 'Lab 1'],
        2 => ['id_aula' => 2, 'nombre' => 'Lab 2'],
        3 => ['id_aula' => 3, 'nombre' => 'Aula 3'],
        4 => ['id_aula' => 4, 'nombre' => 'Aula 5'],
        5 => ['id_aula' => 5, 'nombre' => 'Aula 12'],
        6 => ['id_aula' => 6, 'nombre' => 'Aula 4'],
        7 => ['id_aula' => 7, 'nombre' => 'Taller 1']
    ];
}

// Días y horas
$dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
$bloques_horarios = [
    ['11:45', '12:30'],
    ['12:30', '13:15'],
    ['13:15', '14:00'],
    ['14:00', '14:45'],
    ['14:45', '15:30'],
    ['15:30', '16:15'],
    ['16:15', '17:00']
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_maestro'], $_POST['periodo'])) {
        $id_maestro = intval($_POST['id_maestro']);
        $periodo = $_POST['periodo'];
        
        try {
            $table_check = $con->query("SHOW TABLES LIKE 'horarios_maestros'");
            $table_exists = $table_check->rowCount() > 0;
        } catch (PDOException $e) {
            $table_exists = false;
        }
        
        if (!$table_exists) {
            $_SESSION['error'] = "La tabla 'horarios_maestros' no existe.";
        } else {
            $delete_stmt = $con->prepare("DELETE FROM horarios_maestros WHERE id_maestro = ? AND periodo = ?");
            if ($delete_stmt->execute([$id_maestro, $periodo])) {
                $insert_stmt = $con->prepare("
                    INSERT INTO horarios_maestros (id_maestro, id_materia, dia, hora_inicio, hora_fin, id_aula, id_grupo, periodo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insert_success = true;
                foreach ($dias as $dia) {
                    if (isset($_POST['horario'][$dia])) {
                        foreach ($_POST['horario'][$dia] as $hora_idx => $datos) {
                            if (!empty($datos['materia'])) {
                                $hora = $bloques_horarios[$hora_idx];
                                $materia = intval($datos['materia']);
                                $aula_id = !empty($datos['aula']) ? intval($datos['aula']) : NULL;
                                $grupo_id = !empty($datos['grupo']) ? intval($datos['grupo']) : NULL;
                                
                                if (!$insert_stmt->execute([$id_maestro, $materia, $dia, $hora[0], $hora[1], $aula_id, $grupo_id, $periodo])) {
                                    $insert_success = false;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                
                if ($insert_success) {
                    $_SESSION['mensaje'] = "Horario guardado exitosamente";
                } else {
                    $_SESSION['error'] = "Error al guardar algunos horarios";
                }
                
                header("Location: horario_maestros_captura.php?maestro=" . $id_maestro);
                exit();
            } else {
                $_SESSION['error'] = "Error al eliminar horarios anteriores";
            }
        }
    } else {
        $_SESSION['error'] = "Faltan datos requeridos";
    }
}

// Obtener horario actual
$horario_actual = [];
if (isset($_GET['maestro'])) {
    $id_maestro = intval($_GET['maestro']);
    try {
        $table_check = $con->query("SHOW TABLES LIKE 'horarios_maestros'");
        $table_exists = $table_check->rowCount() > 0;
    } catch (PDOException $e) { $table_exists = false; }
    
    if ($table_exists) {
        $query_horario = "SELECT * FROM horarios_maestros WHERE id_maestro = ? AND periodo = 'FEB 2026-JUL 2026'";
        $stmt = $con->prepare($query_horario);
        $stmt->execute([$id_maestro]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultados as $row) {
            $horario_actual[$row['dia']][$row['hora_inicio']] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captura de Horarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { --primary-dark: #0f172a; --accent-green: #059669; }
        body { background-color: #f1f5f9; color: #334155; font-family: 'Segoe UI', sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card-header { background: white; border-bottom: 1px solid #e2e8f0; border-radius: 12px 12px 0 0 !important; padding: 1.5rem; }
        .table-horario { font-size: 0.85rem; }
        .celda-horario { background: #fff; transition: background 0.2s; }
        .select-horario { font-size: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; }
        .btn-action { border-radius: 8px; font-weight: 600; }
        .badge-hora { background: #e2e8f0; color: #475569; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container-xxl py-5">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-primary-dark">Gestión de Horarios</h2>
                <p class="text-muted">Configuración de carga académica para maestros.</p>
            </div>
            <a href="main.php" class="btn btn-outline-secondary"><i class='bx bx-left-arrow-alt'></i> Volver</a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-center g-3">
                    <div class="col-md-9">
                        <label class="form-label small fw-bold">Seleccionar Docente</label>
                        <select name="maestro" class="form-select" onchange="this.form.submit()" required>
                            <option value="">-- Seleccionar --</option>
                            <?php while ($maestro = $maestros_result->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $maestro['id_maestro']; ?>" <?php echo (isset($_GET['maestro']) && $_GET['maestro'] == $maestro['id_maestro']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($maestro['apellido_paterno'] . ' ' . $maestro['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Periodo</label>
                        <input type="text" class="form-control bg-light" value="FEB 2026-JUL 2026" readonly>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($_GET['maestro'])): 
            $id_maestro = intval($_GET['maestro']);
            $stmt = $con->prepare("SELECT * FROM maestros WHERE id_maestro = ?");
            $stmt->execute([$id_maestro]);
            $m = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($m): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class='bx bx-chalkboard'></i> Horario de <?php echo $m['nombre'] . ' ' . $m['apellido_paterno']; ?></h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()"><i class='bx bx-printer'></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="id_maestro" value="<?php echo $id_maestro; ?>">
                        <input type="hidden" name="periodo" value="FEB 2026-JUL 2026">
                        <div class="table-responsive">
                            <table class="table table-bordered table-horario align-middle">
                                <thead class="table-light text-center">
                                    <tr><th>Bloque</th><?php foreach ($dias as $d): ?><th><?php echo $d; ?></th><?php endforeach; ?></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bloques_horarios as $idx => $b): ?>
                                    <tr>
                                        <td class="text-center"><span class="badge-hora"><?php echo $b[0]; ?></span></td>
                                        <?php foreach ($dias as $dia): 
                                            $hk = $b[0] . ':00';
                                            $ha = isset($horario_actual[$dia][$hk]) ? $horario_actual[$dia][$hk] : null;
                                        ?>
                                        <td class="celda-horario">
                                            <select name="horario[<?php echo $dia; ?>][<?php echo $idx; ?>][materia]" class="form-select select-horario materia-select mb-1">
                                                <option value="">- Materia -</option>
                                                <?php foreach ($materias as $idm => $mat): ?>
                                                    <option value="<?php echo $idm; ?>" <?php echo ($ha && $ha['id_materia'] == $idm) ? 'selected' : ''; ?> data-color="<?php echo $mat['color'] ?? '#dbeafe'; ?>">
                                                        <?php echo htmlspecialchars($mat['materia']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select name="horario[<?php echo $dia; ?>][<?php echo $idx; ?>][grupo]" class="form-select select-horario mb-1">
                                                <option value="">- Grupo -</option>
                                                <?php foreach ($grupos as $idg => $g): ?>
                                                    <option value="<?php echo $idg; ?>" <?php echo ($ha && $ha['id_grupo'] == $idg) ? 'selected' : ''; ?>><?php echo $g['nombre']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select name="horario[<?php echo $dia; ?>][<?php echo $idx; ?>][aula]" class="form-select select-horario">
                                                <option value="">- Aula -</option>
                                                <?php foreach ($aulas as $ida => $a): ?>
                                                    <option value="<?php echo $ida; ?>" <?php echo ($ha && $ha['id_aula'] == $ida) ? 'selected' : ''; ?>><?php echo $a['nombre']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-success btn-lg btn-action px-5"><i class='bx bx-save'></i> Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function applyColor(s) {
            const opt = s.options[s.selectedIndex];
            const col = opt.getAttribute('data-color');
            const cell = s.closest('td');
            cell.style.backgroundColor = (s.value) ? col + '20' : '';
            cell.style.borderLeft = (s.value) ? '4px solid ' + col : '1px solid #dee2e6';
        }
        document.querySelectorAll('.materia-select').forEach(s => {
            s.addEventListener('change', () => applyColor(s));
            applyColor(s);
        });
    </script>
</body>
</html>