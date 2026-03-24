<?php
// consulta_horario_maestro.php
session_start();
require_once 'config.php';
require_once 'conexion.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// 1. Obtener lista de maestros para el selector
$query_maestros = "SELECT id_maestro, nombre, apellido_paterno, apellido_materno 
                   FROM maestros 
                   WHERE activo = 'Activo' 
                   ORDER BY apellido_paterno ASC";
$maestros_res = $con->query($query_maestros);
$maestros = $maestros_res->fetchAll(PDO::FETCH_ASSOC);

// Configuración de visualización
$dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
$bloques_horarios = [
    ['11:45', '12:30'], ['12:30', '13:15'], ['13:15', '14:00'],
    ['14:00', '14:45'], ['14:45', '15:30'], ['15:30', '16:15'], ['16:15', '17:00']
];

$horario_maestro = [];
$maestro_seleccionado = null;

// 2. Si se selecciona un maestro, cargar su agenda
if (isset($_GET['maestro']) && !empty($_GET['maestro'])) {
    $id_maestro = intval($_GET['maestro']);
    
    // Obtener nombre del maestro para el título
    $stmt_m = $con->prepare("SELECT nombre, apellido_paterno FROM maestros WHERE id_maestro = ?");
    $stmt_m->execute([$id_maestro]);
    $maestro_seleccionado = $stmt_m->fetch(PDO::FETCH_ASSOC);

    // Consulta principal: Unimos con materias, grupos y aulas
    $sql = "SELECT h.*, m.materia, g.nombre as grupo_nombre, g.semestre, a.nombre as aula_nombre 
            FROM horarios_maestros h
            INNER JOIN materias m ON h.id_materia = m.id_materia
            INNER JOIN grupos g ON h.id_grupo = g.id_grupo
            LEFT JOIN aulas a ON h.id_aula = a.id_aula
            WHERE h.id_maestro = ? AND h.periodo = 'FEB 2026-JUL 2026'
            ORDER BY h.dia, h.hora_inicio";
            
    $stmt = $con->prepare($sql);
    $stmt->execute([$id_maestro]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Usamos la hora de inicio como llave para acomodar en la tabla
        $horario_maestro[$row['dia']][$row['hora_inicio']] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta Horario Maestro | CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { --primary-dark: #1a5330; --verde-fondo: #f8faf8; }
        body { background-color: var(--verde-fondo); font-family: 'Segoe UI', sans-serif; }
        .header-print { background: var(--primary-dark); color: white; border-radius: 15px 15px 0 0; }
        .table-horario { background: white; border-radius: 15px; overflow: hidden; border: none; }
        .table-horario thead { background: #343a40; color: white; }
        .celda-clase { min-height: 80px; transition: all 0.2s; background: #fff; }
        .info-materia { font-weight: 700; color: var(--primary-dark); font-size: 0.9rem; margin-bottom: 2px; }
        .info-sub { font-size: 0.75rem; color: #666; }
        .badge-hora { background: #e9ecef; color: #495057; font-weight: 600; padding: 5px 10px; border-radius: 5px; display: inline-block; }
        @media print { .no-print { display: none; } .card { border: none; shadow: none; } }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card shadow-sm border-0 mb-4 no-print" style="border-radius: 15px;">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0"><i class='bx bx-search-alt'></i> Consultar Agenda Docente</h4>
                    </div>
                    <div class="col-md-4">
                        <form method="GET" id="formMaestro">
                            <select name="maestro" class="form-select form-select-lg" onchange="this.form.submit()" style="border-radius: 10px;">
                                <option value="">-- Seleccione Docente --</option>
                                <?php foreach ($maestros as $m): ?>
                                    <option value="<?= $m['id_maestro'] ?>" <?= (isset($_GET['maestro']) && $_GET['maestro'] == $m['id_maestro']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['apellido_paterno'] . " " . $m['apellido_materno'] . " " . $m['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-2 text-end">
                        <a href="horarios.php" class="btn btn-outline-secondary w-100">Volver</a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($maestro_seleccionado): ?>
        <div class="card shadow border-0" style="border-radius: 15px;">
            <div class="header-print p-4 text-center">
                <h3 class="mb-1">CECyTE Santa Catarina</h3>
                <h5 class="mb-0 text-uppercase">Horario de Actividades: <?= htmlspecialchars($maestro_seleccionado['nombre'] . " " . $maestro_seleccionado['apellido_paterno']) ?></h5>
                <small>Ciclo Escolar: FEB 2026 - JUL 2026</small>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-horario text-center align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="12%">Horario</th>
                            <?php foreach ($dias as $d): ?><th><?= $d ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bloques_horarios as $b): ?>
                        <tr>
                            <td class="bg-light">
                                <span class="badge-hora"><?= $b[0] ?> - <?= $b[1] ?></span>
                            </td>
                            <?php foreach ($dias as $dia): 
                                $h_inicio = $b[0] . ':00';
                                $clase = $horario_maestro[$dia][$h_inicio] ?? null;
                            ?>
                            <td class="celda-clase">
                                <?php if ($clase): ?>
                                    <div class="info-materia"><?= htmlspecialchars($clase['materia']) ?></div>
                                    <div class="info-sub">
                                        <i class='bx bxs-group'></i> <?= htmlspecialchars($clase['semestre'] . "º " . $clase['grupo_nombre']) ?><br>
                                        <i class='bx bxs-door-open'></i> <?= htmlspecialchars($clase['aula_nombre'] ?: 'S/A') ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-end p-3 no-print">
                <button onclick="window.print()" class="btn btn-dark px-4"><i class='bx bx-printer'></i> Imprimir Horario</button>
            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-info text-center p-5" style="border-radius: 15px; border: 2px dashed #ccc;">
                <i class='bx bx-info-circle' style="font-size: 3rem;"></i>
                <p class="mt-3 fs-5">Por favor, seleccione un maestro del menú superior para visualizar su carga académica.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>