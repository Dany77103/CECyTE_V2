<?php
// consulta_horario_grupo.php
session_start();
require_once 'config.php';
require_once 'conexion.php';

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
    
    $stmt_g = $con->prepare("SELECT nombre, semestre FROM grupos WHERE id_grupo = ?");
    $stmt_g->execute([$id_grupo]);
    $g_info = $stmt_g->fetch(PDO::FETCH_ASSOC);
    $grupo_nombre = $g_info['nombre'] . " - " . $g_info['semestre'] . "º Semestre";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Grupal | CECyTE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --secondary: #6c757d;
            --white: #ffffff;
            --bg: #f4f6f9;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            padding-top: 90px;
            color: #333;
        }

        /* --- NAVBAR --- */
        .navbar {
            background: var(--white);
            height: 70px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 5%; position: fixed; top: 0; left: 0; right: 0;
            z-index: 1000; box-shadow: var(--shadow-sm);
        }
        .navbar-brand { display: flex; align-items: center; gap: 15px; text-decoration: none; }
        .navbar-brand img { height: 45px; }
        .navbar-brand span { font-weight: 700; color: var(--primary); font-size: 1.1rem; }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }

        /* --- CARDS --- */
        .content-card {
            background: var(--white); padding: 30px; border-radius: 20px;
            box-shadow: var(--shadow-md); border-left: 6px solid var(--primary);
            margin-bottom: 25px;
        }

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-flex h2 { color: var(--primary); font-size: 1.5rem; font-weight: 700; margin: 0; }

        /* --- FORM ELEMENTS --- */
        .label-mini {
            font-size: 0.75rem; font-weight: 700; color: var(--secondary);
            text-transform: uppercase; display: block; margin-bottom: 6px;
        }
        .form-select {
            border-radius: 12px; border: 1px solid #e0e0e0; padding: 12px;
            font-size: 1rem; transition: var(--transition);
        }
        .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26, 83, 48, 0.1); }

        /* --- TABLE --- */
        .table-responsive { border-radius: 15px; overflow: hidden; background: white; }
        .table-horario { width: 100%; border-collapse: collapse; background: white; }
        .table-horario th {
            background: #f8f9fa; color: var(--primary); padding: 15px;
            font-size: 0.8rem; text-transform: uppercase; font-weight: 700;
            border-bottom: 2px solid #edf2f7;
        }
        .table-horario td {
            padding: 15px; border-bottom: 1px solid #f0f0f0;
            vertical-align: middle; text-align: center;
        }
        
        .badge-hora {
            background: #e8f5e9; color: var(--primary); padding: 8px 12px;
            border-radius: 8px; font-weight: 700; font-size: 0.85rem;
            display: inline-block;
        }

        /* --- INFO BOXES --- */
        .info-materia { font-weight: 700; color: #1e293b; font-size: 0.95rem; line-height: 1.2; margin-bottom: 4px; }
        .info-maestro { color: var(--secondary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-aula {
            display: inline-block; margin-top: 8px; padding: 4px 10px;
            background: #f1f5f9; color: var(--primary); border-radius: 6px;
            font-size: 0.75rem; font-weight: 600;
        }

        .btn-action {
            padding: 10px 20px; border-radius: 10px; text-decoration: none;
            font-weight: 600; font-size: 0.85rem; transition: var(--transition);
            display: inline-flex; align-items: center; gap: 8px; border: none;
        }
        .btn-primary-custom { background: var(--primary); color: white; }
        .btn-outline-custom { border: 1.5px solid #e2e8f0; color: var(--secondary); }
        .btn-outline-custom:hover { background: #f8f9fa; }

        @media print {
            .no-print, .navbar { display: none !important; }
            body { padding-top: 0; background: white; }
            .content-card { box-shadow: none; border: 1px solid #eee; margin: 0; }
            .table-horario th { background: #eee !important; color: black !important; }
        }
    </style>
</head>
<body>

    <nav class="navbar no-print">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="CECyTE Logo">
            <span>Gestión Académica</span>
        </a>
        <div style="font-size: 0.85rem; font-weight: 600; color: var(--secondary);">
            <i class="fa-solid fa-calendar-check"></i> Consulta de Horarios
        </div>
    </nav>

    <div class="container">
        <div class="content-card no-print">
            <div class="header-flex">
                <div>
                    <h2><i class="fa-solid fa-users-rectangle"></i> Horarios por Grupo</h2>
                    <p style="color: var(--secondary); font-size: 0.85rem;">Seleccione el grupo para visualizar su horario</p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn-action btn-outline-custom">
                        <i class="fa-solid fa-print"></i> Imprimir
                    </button>
                    <a href="horarios.php" class="btn-action btn-outline-custom">Volver</a>
                </div>
            </div>

            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="label-mini">Grupo / Semestre</label>
                    <select name="grupo" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Buscar grupo... --</option>
                        <?php 
                        $grupos_res->execute(); // Reiniciar puntero si es necesario
                        while ($g = $grupos_res->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= $g['id_grupo'] ?>" <?= (isset($_GET['grupo']) && $_GET['grupo'] == $g['id_grupo']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['nombre'] . " - " . $g['semestre'] . "º Semestre") ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4 text-center">
                    <label class="label-mini">Ciclo Escolar</label>
                    <div style="background: #f8f9fa; padding: 12px; border-radius: 12px; font-weight: 700; color: var(--primary); border: 1px solid #e0e0e0;">
                        <?= $periodo_actual ?>
                    </div>
                </div>
            </form>
        </div>

        <?php if (!empty($grupo_nombre)): ?>
            <div class="content-card" style="padding: 0; overflow: hidden; border-left: none; border-top: 6px solid var(--primary);">
                <div style="padding: 20px; text-align: center; background: #fff;">
                    <h3 style="font-weight: 800; color: var(--primary); margin: 0; text-transform: uppercase;">
                        Grupo: <?= htmlspecialchars($grupo_nombre) ?>
                    </h3>
                </div>
                
                <div class="table-responsive">
                    <table class="table-horario">
                        <thead>
                            <tr>
                                <th style="width: 120px;">Hora</th>
                                <?php foreach ($dias as $d): ?><th><?= $d ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloques as $b): ?>
                            <tr>
                                <td style="background: #fcfdfe; border-right: 1px solid #edf2f7;">
                                    <span class="badge-hora"><?= $b[0] ?> - <?= $b[1] ?></span>
                                </td>
                                <?php foreach ($dias as $dia): 
                                    $h_key = $b[0] . ':00';
                                    $data = $horario_view[$dia][$h_key] ?? null;
                                ?>
                                <td style="height: 110px; min-width: 170px;">
                                    <?php if ($data): ?>
                                        <div class="info-materia"><?= htmlspecialchars($data['materia']) ?></div>
                                        <div class="info-maestro">
                                            <?= htmlspecialchars($data['m_pat'] . " " . $data['m_nom']) ?>
                                        </div>
                                        <div class="info-aula">
                                            <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($data['aula_nom']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1; font-size: 0.8rem;">---</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-center no-print" style="margin-top: -10px; margin-bottom: 40px;">
                 <a href="exportar_excel_grupo.php?grupo=<?= $_GET['grupo'] ?>" class="btn-action btn-primary-custom">
                    <i class="fa-solid fa-file-excel"></i> Descargar Reporte en Excel
                </a>
            </div>

        <?php else: ?>
            <div class="content-card text-center py-5">
                <i class="fa-solid fa-calendar-days" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 15px;"></i>
                <h4 style="color: var(--secondary);">Esperando selección</h4>
                <p class="text-muted">Elija un grupo del menú superior para generar la vista de horario.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>