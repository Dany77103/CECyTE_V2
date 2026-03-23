<?php
session_start();
// Verificar sesión administrativa
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Inicializar variables
$id_grupo = $_GET['grupo'] ?? '';
$id_materia = $_GET['materia'] ?? '';
$parcial = $_GET['parcial'] ?? '1'; // Por defecto parcial 1

// Consultas para los filtros
$grupos = $con->query("SELECT id_grupo, nombre, semestre FROM grupos WHERE activo = 1 ORDER BY semestre, nombre")->fetchAll(PDO::FETCH_ASSOC);
$materias = $con->query("SELECT id_materia, materia FROM materias ORDER BY materia")->fetchAll(PDO::FETCH_ASSOC);

// Consulta de calificaciones si hay filtros seleccionados
$calificaciones = [];
if ($id_grupo && $id_materia) {
    $sql = "SELECT a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno, 
                   c.calificacion, c.faltas, c.observaciones
            FROM alumnos a
            LEFT JOIN calificaciones c ON a.id_alumno = c.id_alumno 
                AND c.id_materia = :materia 
                AND c.parcial = :parcial
            WHERE a.id_grupo = :grupo
            ORDER BY a.apellido_paterno, a.apellido_materno";
    
    $stmt = $con->prepare($sql);
    $stmt->execute([
        ':materia' => $id_materia,
        ':parcial' => $parcial,
        ':grupo'   => $id_grupo
    ]);
    $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Calificaciones | CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --primary: #1a5330; --bg: #f8faf9; }
        body { background-color: var(--bg); font-family: 'Inter', sans-serif; }
        .card-header-cecyte { background: var(--primary); color: white; border-radius: 15px 15px 0 0 !important; }
        .reprobado { color: #dc3545; font-weight: bold; }
        .aprobado { color: #198754; font-weight: bold; }
        .table-hover tbody tr:hover { background-color: #f1f8f1; }
        .filter-section { background: white; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        @media print { .no-print { display: none; } .card { border: none; } }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h3 style="color: var(--primary); font-weight: 700;">
            <i class="fa-solid fa-file-invoice me-2"></i>Consulta de Calificaciones
        </h3>
        <a href="main.php" class="btn btn-outline-secondary shadow-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver al Panel
        </a>
    </div>

    <div class="filter-section p-4 mb-4 no-print">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">Grupo</label>
                <select name="grupo" class="form-select" required>
                    <option value="">Seleccionar...</option>
                    <?php foreach ($grupos as $g): ?>
                        <option value="<?= $g['id_grupo'] ?>" <?= $id_grupo == $g['id_grupo'] ? 'selected' : '' ?>>
                            <?= $g['semestre'] ?>º - <?= htmlspecialchars($g['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Materia</label>
                <select name="materia" class="form-select" required>
                    <option value="">Seleccionar...</option>
                    <?php foreach ($materias as $m): ?>
                        <option value="<?= $m['id_materia'] ?>" <?= $id_materia == $m['id_materia'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['materia']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Parcial</label>
                <select name="parcial" class="form-select">
                    <option value="1" <?= $parcial == '1' ? 'selected' : '' ?>>Parcial 1</option>
                    <option value="2" <?= $parcial == '2' ? 'selected' : '' ?>>Parcial 2</option>
                    <option value="3" <?= $parcial == '3' ? 'selected' : '' ?>>Parcial 3</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100 fw-bold">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Consultar
                </button>
            </div>
        </form>
    </div>

    <?php if ($id_grupo && $id_materia): ?>
    <div class="card shadow-sm border-0" style="border-radius: 15px;">
        <div class="card-header card-header-cecyte py-3 d-flex justify-content-between align-items-center">
            <span class="fs-5 fw-bold">Lista de Calificaciones - Parcial <?= $parcial ?></span>
            <button onclick="window.print()" class="btn btn-light btn-sm no-print">
                <i class="fa-solid fa-print me-1"></i> Imprimir Reporte
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Matrícula</th>
                            <th>Nombre del Alumno</th>
                            <th class="text-center">Calificación</th>
                            <th class="text-center">Faltas</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($calificaciones) > 0): ?>
                            <?php foreach ($calificaciones as $row): 
                                $nota = $row['calificacion'] ?? 0;
                                $clase_nota = ($nota < 6) ? 'reprobado' : 'aprobado';
                            ?>
                            <tr>
                                <td class="ps-4 fw-medium"><?= $row['matricula'] ?></td>
                                <td class="text-uppercase">
                                    <?= htmlspecialchars($row['apellido_paterno'] . ' ' . $row['apellido_materno'] . ' ' . $row['nombre']) ?>
                                </td>
                                <td class="text-center">
                                    <span class="<?= $clase_nota ?> fs-5">
                                        <?= $row['calificacion'] !== null ? number_format($nota, 1) : '<span class="text-muted small">N/A</span>' ?>
                                    </span>
                                </td>
                                <td class="text-center"><?= $row['faltas'] ?? '0' ?></td>
                                <td class="small text-muted italic"><?= htmlspecialchars($row['observaciones'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    No se encontraron alumnos registrados en este grupo.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="text-center py-5">
        <i class="fa-solid fa-layer-group fa-4x mb-3 text-muted" style="opacity: 0.3;"></i>
        <p class="text-muted">Selecciona los filtros superiores para visualizar el historial académico.</p>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>