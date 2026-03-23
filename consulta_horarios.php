<?php
session_start();
require_once 'config.php';
require_once 'conexion.php';

// Si no está logueado, redirigir (opcional, dependiendo de si quieres que sea público)
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$periodo_actual = "FEB 2026-JUL 2026";

// Obtener grupos activos para el buscador
$query_grupos = "SELECT * FROM grupos WHERE activo = 1 ORDER BY semestre, nombre";
$grupos_res = $con->query($query_grupos);

$horario_view = [];
$grupo_nombre = "";

if (isset($_GET['grupo']) && !empty($_GET['grupo'])) {
    $id_grupo = intval($_GET['grupo']);
    
    // Obtener nombre del grupo seleccionado
    $stmt_g = $con->prepare("SELECT nombre, semestre FROM grupos WHERE id_grupo = ?");
    $stmt_g->execute([$id_grupo]);
    $g_info = $stmt_g->fetch(PDO::FETCH_ASSOC);
    $grupo_nombre = $g_info['nombre'] . " - " . $g_info['semestre'] . "º Semestre";

    // Consulta detallada con JOINs para ver nombres en lugar de IDs
    $query_h = "SELECT h.*, m.materia, ma.nombre as m_nom, ma.apellido_paterno as m_pat, a.nombre as aula_nom 
                FROM horarios_maestros h
                LEFT JOIN materias m ON h.id_materia = m.id_materia
                LEFT JOIN maestros ma ON h.id_maestro = ma.id_maestro
                LEFT JOIN aulas a ON h.id_aula = a.id_aula
                WHERE h.id_grupo = ? AND h.periodo = ?
                ORDER BY h.dia, h.hora_inicio";
    
    $stmt = $con->prepare($query_h);
    $stmt->execute([$id_grupo, $periodo_actual]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $horario_view[$row['dia']][$row['hora_inicio']] = $row;
    }
}

$dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
$bloques = [
    ['11:45', '12:30'], ['12:30', '13:15'], ['13:15', '14:00'],
    ['14:00', '14:45'], ['14:45', '15:30'], ['15:30', '16:15'], ['16:15', '17:00']
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Horarios | CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root { --primary-dark: #1a5330; --verde-claro: #f1f8f1; }
        body { background-color: var(--verde-claro); font-family: 'Segoe UI', sans-serif; font-size: 1.1rem; }
        .header-title { color: var(--primary-dark); font-weight: 700; }
        .table-consulta { background: white; border-radius: 15px; overflow: hidden; border: none; }
        .table-consulta thead { background-color: var(--primary-dark); color: white; }
        .badge-hora { background: #e9ecef; color: #495057; padding: 8px; border-radius: 6px; font-weight: 600; display: block; }
        .info-materia { color: var(--primary-dark); font-weight: 700; font-size: 1rem; margin-bottom: 2px; }
        .info-maestro { color: #6c757d; font-size: 0.9rem; }
        .info-aula { color: #1a5330; font-weight: 600; font-size: 0.85rem; background: #d4edda; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 5px; }
        @media print { .no-print { display: none; } .container-fluid { width: 100%; } }
    </style>
</head>
<body>
    <div class="container-fluid py-5">
        <div class="d-flex justify-content-between align-items-center mb-4 px-4 no-print">
            <h4 class="header-title"><i class='bx bx-calendar-event'></i> Consulta de Horarios</h4>
            <div>
                <button onclick="window.print()" class="btn btn-primary me-2"><i class='bx bx-printer'></i> Imprimir</button>
                <a href="horarios.php" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 no-print" style="border-radius: 15px;">
            <div class="card-body p-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Seleccionar Grupo:</label>
                        <select name="grupo" class="form-select form-select-lg" onchange="this.form.submit()">
                            <option value="">-- Elige un grupo --</option>
                            <?php while ($g = $grupos_res->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?= $g['id_grupo'] ?>" <?= (isset($_GET['grupo']) && $_GET['grupo'] == $g['id_grupo']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['nombre'] . " - " . $g['semestre'] . "º Semestre") ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 text-center">
                        <p class="mb-2 small text-muted text-uppercase fw-bold">Periodo Escolar</p>
                        <span class="badge bg-dark p-2 px-3 fs-6"><?= $periodo_actual ?></span>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($grupo_nombre)): ?>
            <div class="card shadow-sm border-0 table-consulta">
                <div class="card-header bg-white py-3 border-0 text-center">
                    <h3 class="header-title mb-0">GRUPO: <?= htmlspecialchars($grupo_nombre) ?></h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center mb-0">
                        <thead>
                            <tr>
                                <th style="width: 150px;">HORA</th>
                                <?php foreach ($dias as $d): ?><th><?= strtoupper($d) ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloques as $b): ?>
                            <tr>
                                <td class="bg-light">
                                    <span class="badge-hora"><?= $b[0] ?> - <?= $b[1] ?></span>
                                </td>
                                <?php foreach ($dias as $dia): 
                                    $h_key = $b[0] . ':00';
                                    $data = $horario_view[$dia][$h_key] ?? null;
                                ?>
                                <td style="height: 100px; min-width: 160px;">
                                    <?php if ($data): ?>
                                        <div class="info-materia"><?= htmlspecialchars($data['materia']) ?></div>
                                        <div class="info-maestro text-uppercase small">
                                            <?= htmlspecialchars($data['m_pat'] . " " . $data['m_nom']) ?>
                                        </div>
                                        <div class="info-aula">
                                            <i class='bx bx-door-open'></i> <?= htmlspecialchars($data['aula_nom']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">--</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="text-center mt-4 no-print">
                 <a href="exportar_excel_grupo.php?grupo=<?= $_GET['grupo'] ?>" class="btn btn-outline-success">
                    <i class='bx bxs-file-export'></i> Descargar Excel
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center shadow-sm" style="border-radius: 10px;">
                <i class='bx bx-info-circle'></i> Selecciona un grupo arriba para visualizar el horario cargado.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>