<?php
// horario_maestros_captura.php - VERSIÓN ACTUALIZADA Y CORREGIDA
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

// 1. CONFIGURACIÓN DE BLOQUES (Normalizados a HH:MM:SS para la BD)
$dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
$bloques_horarios = [
    ['11:45:00', '12:30:00'],
    ['12:30:00', '13:15:00'],
    ['13:15:00', '14:00:00'],
    ['14:00:00', '14:45:00'],
    ['14:45:00', '15:30:00'],
    ['15:30:00', '16:15:00'],
    ['16:15:00', '17:00:00']
];

// 2. PROCESAR FORMULARIO (GUARDADO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_maestro'])) {
    $id_maestro = intval($_POST['id_maestro']);
    $periodo = $_POST['periodo'];
    
    try {
        $con->beginTransaction();

        // Eliminar registros previos de este maestro y periodo
        $delete_stmt = $con->prepare("DELETE FROM horarios_maestros WHERE id_maestro = ? AND periodo = ?");
        $delete_stmt->execute([$id_maestro, $periodo]);

        $insert_stmt = $con->prepare("
            INSERT INTO horarios_maestros (id_maestro, id_materia, dia, hora_inicio, hora_fin, id_aula, id_grupo, periodo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (isset($_POST['horario'])) {
            foreach ($_POST['horario'] as $dia => $bloques) {
                foreach ($bloques as $idx => $datos) {
                    if (!empty($datos['materia'])) {
                        $hora = $bloques_horarios[$idx];
                        $materia = intval($datos['materia']);
                        $aula_id = !empty($datos['aula']) ? intval($datos['aula']) : NULL;
                        $grupo_id = !empty($datos['grupo']) ? intval($datos['grupo']) : NULL;
                        
                        $insert_stmt->execute([
                            $id_maestro, 
                            $materia, 
                            $dia, 
                            $hora[0], 
                            $hora[1], 
                            $aula_id, 
                            $grupo_id, 
                            $periodo
                        ]);
                    }
                }
            }
        }
        
        $con->commit();
        $_SESSION['mensaje'] = "Horario guardado exitosamente";
        
        // --- CAMBIO SOLICITADO AQUÍ ---
        // Redirigir a la consulta del maestro recién guardado
        header("Location: consulta_horario_maestro.php?maestro=" . $id_maestro);
        exit();

    } catch (Exception $e) {
        if ($con->inTransaction()) $con->rollBack();
        $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
    }
}

// 3. CARGA DE DATOS PARA LOS SELECTS
$query_maestros = "SELECT * FROM maestros WHERE activo = 'Activo' ORDER BY apellido_paterno, nombre";
$maestros_result = $con->query($query_maestros);

$materias = $con->query("SELECT * FROM materias ORDER BY materia")->fetchAll(PDO::FETCH_ASSOC);
$grupos = $con->query("SELECT * FROM grupos WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$aulas = $con->query("SELECT * FROM aulas WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// 4. OBTENER HORARIO ACTUAL
$horario_actual = [];
if (isset($_GET['maestro'])) {
    $id_sel = intval($_GET['maestro']);
    $stmt = $con->prepare("SELECT * FROM horarios_maestros WHERE id_maestro = ? AND periodo = 'FEB 2026-JUL 2026'");
    $stmt->execute([$id_sel]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $horario_actual[$row['dia']][$row['hora_inicio']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captura de Horarios - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { --primary-dark: #0f172a; --accent-green: #059669; }
        body { background-color: #f1f5f9; color: #334155; font-family: 'Segoe UI', sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card-header { background: white; border-bottom: 1px solid #e2e8f0; padding: 1.5rem; }
        .table-horario { font-size: 0.85rem; }
        .celda-horario { background: #fff; transition: background 0.2s; min-width: 150px; }
        .select-horario { font-size: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; }
        .badge-hora { background: #e2e8f0; color: #475569; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container-xxl py-5">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class='bx bx-check-circle'></i> <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-primary-dark">Gestión de Horarios</h2>
                <p class="text-muted">Configuración de carga académica para maestros.</p>
            </div>
            <a href="horario.php" class="btn btn-outline-secondary"><i class='bx bx-left-arrow-alt'></i> Volver</a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-center g-3">
                    <div class="col-md-9">
                        <label class="form-label small fw-bold">Seleccionar Docente</label>
                        <select name="maestro" class="form-select" onchange="this.form.submit()" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($maestros_result as $maestro): ?>
                                <option value="<?php echo $maestro['id_maestro']; ?>" <?php echo (isset($_GET['maestro']) && $_GET['maestro'] == $maestro['id_maestro']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($maestro['apellido_paterno'] . ' ' . $maestro['apellido_materno'] . ' ' . $maestro['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
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
                    <h5 class="mb-0"><i class='bx bx-chalkboard'></i> Horario de <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido_paterno']); ?></h5>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()"><i class='bx bx-printer'></i></button>
                </div>
                <div class="card-body">
                    <form method="POST" action=""> 
                        <input type="hidden" name="id_maestro" value="<?php echo $id_maestro; ?>">
                        <input type="hidden" name="periodo" value="FEB 2026-JUL 2026">
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-horario align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Bloque</th>
                                        <?php foreach ($dias as $d): ?><th><?php echo $d; ?></th><?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bloques_horarios as $idx => $b): ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge-hora"><?php echo substr($b[0], 0, 5); ?></span>
                                        </td>
                                        <?php foreach ($dias as $dia): 
                                            $h_inicio = $b[0];
                                            $ha = isset($horario_actual[$dia][$h_inicio]) ? $horario_actual[$dia][$h_inicio] : null;
                                        ?>
                                        <td class="celda-horario">
                                            <select name="horario[<?php echo $dia; ?>][<?php echo $idx; ?>][materia]" class="form-select select-horario materia-select mb-1">
                                                <option value="">- Materia -</option>
                                                <?php foreach ($materias as $mat): ?>
                                                    <option value="<?php echo $mat['id_materia']; ?>" 
                                                        <?php echo ($ha && $ha['id_materia'] == $mat['id_materia']) ? 'selected' : ''; ?>
                                                        data-color="<?php echo $mat['color'] ?? '#3b82f6'; ?>">
                                                        <?php echo htmlspecialchars($mat['materia']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            
                                            <select name="horario[<?php echo $dia; ?>][<?php echo $idx; ?>][grupo]" class="form-select select-horario mb-1">
                                                <option value="">- Grupo -</option>
                                                <?php foreach ($grupos as $g): ?>
                                                    <option value="<?php echo $g['id_grupo']; ?>" <?php echo ($ha && $ha['id_grupo'] == $g['id_grupo']) ? 'selected' : ''; ?>>
                                                        <?php echo $g['nombre']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            
                                            <select name="horario[<?php echo $dia; ?>][<?php echo $idx; ?>][aula]" class="form-select select-horario">
                                                <option value="">- Aula -</option>
                                                <?php foreach ($aulas as $a): ?>
                                                    <option value="<?php echo $a['id_aula']; ?>" <?php echo ($ha && $ha['id_aula'] == $a['id_aula']) ? 'selected' : ''; ?>>
                                                        <?php echo $a['nombre']; ?>
                                                    </option>
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
                            <button type="submit" class="btn btn-success btn-lg px-5 fw-bold">
                                <i class='bx bx-save'></i> Guardar Horario
                            </button>
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
            const col = opt.getAttribute('data-color') || '#3b82f6';
            const cell = s.closest('td');
            if (s.value) {
                cell.style.backgroundColor = col + '15';
                cell.style.borderLeft = '4px solid ' + col;
            } else {
                cell.style.backgroundColor = '';
                cell.style.borderLeft = '1px solid #dee2e6';
            }
        }
        
        document.querySelectorAll('.materia-select').forEach(s => {
            s.addEventListener('change', () => applyColor(s));
            applyColor(s); 
        });
    </script>
</body>
</html>