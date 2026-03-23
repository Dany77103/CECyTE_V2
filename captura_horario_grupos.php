<?php
// captura_horarios_grupos.php - VERSIÓN CON TEXTO MÁS GRANDE
session_start();
require_once 'config.php';
require_once 'conexion.php';

if (!isset($_SESSION['username']) || $_SESSION['rol'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Consultas de preparación (Igual que el anterior)
$query_grupos = "SELECT * FROM grupos WHERE activo = 1 ORDER BY semestre, nombre";
$grupos_result = $con->query($query_grupos);

$query_maestros_list = "SELECT id_maestro, nombre, apellido_paterno FROM maestros WHERE activo = 'Activo' ORDER BY apellido_paterno";
$maestros_res = $con->query($query_maestros_list);
$maestros = $maestros_res->fetchAll(PDO::FETCH_ASSOC);

$query_materias = "SELECT * FROM materias ORDER BY materia";
$materias_result = $con->query($query_materias);
$materias = $materias_result->fetchAll(PDO::FETCH_ASSOC);

$query_aulas = "SELECT * FROM aulas WHERE activo = 1 ORDER BY nombre";
$aulas_res = $con->query($query_aulas);
$aulas = $aulas_res->fetchAll(PDO::FETCH_ASSOC);

$dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
$bloques_horarios = [
    ['11:45', '12:30'], ['12:30', '13:15'], ['13:15', '14:00'],
    ['14:00', '14:45'], ['14:45', '15:30'], ['15:30', '16:15'], ['16:15', '17:00']
];

// Lógica de guardado (Se mantiene igual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_grupo'])) {
    $id_grupo = intval($_POST['id_grupo']);
    $periodo = $_POST['periodo'];
    $con->beginTransaction();
    try {
        $del = $con->prepare("DELETE FROM horarios_maestros WHERE id_grupo = ? AND periodo = ?");
        $del->execute([$id_grupo, $periodo]);
        $ins = $con->prepare("INSERT INTO horarios_maestros (id_grupo, id_materia, id_maestro, id_aula, dia, hora_inicio, hora_fin, periodo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($dias as $dia) {
            if (isset($_POST['horario'][$dia])) {
                foreach ($_POST['horario'][$dia] as $idx => $datos) {
                    if (!empty($datos['materia']) && !empty($datos['maestro'])) {
                        $bloque = $bloques_horarios[$idx];
                        $ins->execute([$id_grupo, $datos['materia'], $datos['maestro'], $datos['aula'] ?: null, $dia, $bloque[0], $bloque[1], $periodo]);
                    }
                }
            }
        }
        $con->commit();
        $_SESSION['mensaje'] = "Horario actualizado.";
    } catch (Exception $e) {
        $con->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header("Location: captura_horarios_grupos.php?grupo=" . $id_grupo);
    exit();
}

$horario_actual = [];
if (isset($_GET['grupo'])) {
    $id_grupo = intval($_GET['grupo']);
    $stmt = $con->prepare("SELECT * FROM horarios_maestros WHERE id_grupo = ? AND periodo = 'FEB 2026-JUL 2026'");
    $stmt->execute([$id_grupo]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $horario_actual[$row['dia']][$row['hora_inicio']] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Captura por Grupos | CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { --primary-dark: #1a5330; --verde-claro: #f1f8f1; }
        body { background-color: var(--verde-claro); font-family: 'Segoe UI', sans-serif; font-size: 1.1rem; }
        
        /* Texto más grande en encabezados */
        h4 { font-size: 1.8rem; font-weight: 700; color: var(--primary-dark); }
        
        /* Selectores de la parte superior más grandes */
        .form-label { font-size: 1.1rem; }
        .form-select-lg-custom { font-size: 1.2rem; padding: 10px; }

        /* Estilos de la tabla con texto aumentado */
        .table-horario { font-size: 1rem; background: white; }
        .table-horario thead th { font-size: 1.1rem; padding: 15px; }
        
        .celda-input { min-width: 180px; padding: 10px !important; }
        
        /* Selectores dentro de la tabla */
        .select-custom { 
            font-size: 0.95rem; 
            padding: 6px; 
            margin-bottom: 5px; 
            border-radius: 8px;
        }

        .badge-hora { 
            background: var(--primary-dark); 
            color: white; 
            padding: 8px; 
            border-radius: 6px; 
            display: block; 
            font-size: 1rem;
        }

        .btn-lg-custom { font-size: 1.2rem; padding: 12px 30px; }
    </style>
</head>
<body>
    <div class="container-fluid py-5">
        <div class="d-flex justify-content-between align-items-center mb-4 px-3">
            <h4><i class='bx bxs-group'></i> Captura de Horario por Grupo</h4>
            <a href="horarios.php" class="btn btn-outline-secondary">Volver al Menú</a>
        </div>

        <div class="card shadow-sm mb-4 border-0" style="border-radius: 15px;">
            <div class="card-body p-4">
                <form method="GET" class="row g-4 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label fw-bold text-secondary">Seleccionar Grupo del Plantel</label>
                        <select name="grupo" class="form-select form-select-lg-custom" onchange="this.form.submit()">
                            <option value="">-- Seleccione un grupo para editar --</option>
                            <?php while ($g = $grupos_result->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?= $g['id_grupo'] ?>" <?= (isset($_GET['grupo']) && $_GET['grupo'] == $g['id_grupo']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['nombre'] . " - " . $g['semestre'] . "º Semestre") ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold text-secondary">Ciclo Escolar</label>
                        <input type="text" class="form-control form-control-lg bg-light" value="FEB 2026-JUL 2026" readonly>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($_GET['grupo'])): ?>
        <form method="POST">
            <input type="hidden" name="id_grupo" value="<?= $_GET['grupo'] ?>">
            <input type="hidden" name="periodo" value="FEB 2026-JUL 2026">
            
            <div class="table-responsive shadow-sm" style="border-radius: 15px;">
                <table class="table table-bordered table-horario text-center align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 120px;">Bloque</th>
                            <?php foreach ($dias as $d): ?><th><?= $d ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bloques_horarios as $idx => $b): ?>
                        <tr>
                            <td class="bg-light"><span class="badge-hora"><?= $b[0] ?> - <?= $b[1] ?></span></td>
                            <?php foreach ($dias as $dia): 
                                $h_key = $b[0] . ':00';
                                $act = $horario_actual[$dia][$h_key] ?? null;
                            ?>
                            <td class="celda-input">
                                <label class="small text-muted d-block text-start mb-1">Materia:</label>
                                <select name="horario[<?= $dia ?>][<?= $idx ?>][materia]" class="form-select select-custom">
                                    <option value="">-- Materia --</option>
                                    <?php foreach ($materias as $m): ?>
                                        <option value="<?= $m['id_materia'] ?>" <?= ($act && $act['id_materia'] == $m['id_materia']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['materia']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <label class="small text-muted d-block text-start mb-1">Docente:</label>
                                <select name="horario[<?= $dia ?>][<?= $idx ?>][maestro]" class="form-select select-custom">
                                    <option value="">-- Maestro --</option>
                                    <?php foreach ($maestros as $mtro): ?>
                                        <option value="<?= $mtro['id_maestro'] ?>" <?= ($act && $act['id_maestro'] == $mtro['id_maestro']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($mtro['apellido_paterno'] . " " . $mtro['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <label class="small text-muted d-block text-start mb-1">Aula:</label>
                                <select name="horario[<?= $dia ?>][<?= $idx ?>][aula]" class="form-select select-custom">
                                    <option value="">-- Aula --</option>
                                    <?php foreach ($aulas as $au): ?>
                                        <option value="<?= $au['id_aula'] ?>" <?= ($act && $act['id_aula'] == $au['id_aula']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($au['nombre']) ?>
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
            <div class="text-end mt-4 px-3">
                <button type="submit" class="btn btn-success btn-lg-custom shadow"><i class='bx bx-save'></i> Guardar Horario Completo</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>