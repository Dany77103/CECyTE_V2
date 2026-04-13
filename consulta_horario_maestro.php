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
    
    $stmt_m = $con->prepare("SELECT nombre, apellido_paterno FROM maestros WHERE id_maestro = ?");
    $stmt_m->execute([$id_maestro]);
    $maestro_seleccionado = $stmt_m->fetch(PDO::FETCH_ASSOC);

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
        $horario_maestro[$row['dia']][$row['hora_inicio']] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Docente | CECyTE</title>
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

        /* --- FORM SELECT --- */
        .label-mini {
            font-size: 0.75rem; font-weight: 700; color: var(--secondary);
            text-transform: uppercase; display: block; margin-bottom: 6px;
        }
        .form-select {
            border-radius: 12px; border: 1px solid #e0e0e0; padding: 12px;
            font-size: 1rem; transition: var(--transition); appearance: none;
            background-image: url("data:image/svg+xml,..."); /* SVG arrow opcional */
        }
        .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26, 83, 48, 0.1); }

        /* --- TABLE STYLE --- */
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
        }

        .info-materia { font-weight: 700; color: #1e293b; font-size: 0.9rem; line-height: 1.2; margin-bottom: 4px; }
        .info-sub { color: var(--secondary); font-size: 0.75rem; font-weight: 500; }
        .info-sub i { color: var(--primary-light); margin-right: 3px; }

        .btn-action {
            padding: 10px 20px; border-radius: 10px; text-decoration: none;
            font-weight: 600; font-size: 0.85rem; transition: var(--transition);
            display: inline-flex; align-items: center; gap: 8px; border: none;
        }
        .btn-primary-custom { background: var(--primary); color: white; cursor: pointer; }
        .btn-outline-custom { border: 1.5px solid #e2e8f0; color: var(--secondary); }

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
            <img src="img/logo.png" alt="Logo">
            <span>Control Escolar</span>
        </a>
        <div style="font-size: 0.85rem; font-weight: 600; color: var(--secondary);">
            <i class="fa-solid fa-chalkboard-user"></i> Agenda del Personal Docente
        </div>
    </nav>

    <div class="container">
        <div class="content-card no-print">
            <div class="header-flex">
                <div>
                    <h2><i class="fa-solid fa-magnifying-glass"></i> Consultar Agenda</h2>
                    <p style="color: var(--secondary); font-size: 0.85rem;">Busque al docente para generar su horario semanal</p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn-action btn-outline-custom">
                        <i class="fa-solid fa-print"></i> Imprimir
                    </button>
                    <a href="horarios.php" class="btn-action btn-outline-custom">Cerrar</a>
                </div>
            </div>

            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label class="label-mini">Nombre del Docente</label>
                    <select name="maestro" class="form-select select2" onchange="this.form.submit()">
                        <option value="">-- Buscar por apellido... --</option>
                        <?php foreach ($maestros as $m): ?>
                            <option value="<?= $m['id_maestro'] ?>" <?= (isset($_GET['maestro']) && $_GET['maestro'] == $m['id_maestro']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['apellido_paterno'] . " " . $m['apellido_materno'] . " " . $m['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="label-mini">Periodo Activo</label>
                    <div style="padding: 12px; background: #f8f9fa; border-radius: 12px; font-weight: 700; color: var(--primary); text-align: center; border: 1px solid #e2e8f0;">
                        FEB - JUL 2026
                    </div>
                </div>
            </form>
        </div>

        <?php if ($maestro_seleccionado): ?>
            <div class="content-card" style="padding: 0; overflow: hidden; border-left: none; border-top: 6px solid var(--primary);">
                <div style="padding: 25px; text-align: center; background: #fff; border-bottom: 1px solid #f0f0f0;">
                    <span style="color: var(--secondary); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">CECyTE Santa Catarina</span>
                    <h3 style="font-weight: 800; color: var(--primary); margin: 5px 0 0 0;">
                        <?= htmlspecialchars($maestro_seleccionado['nombre'] . " " . $maestro_seleccionado['apellido_paterno']) ?>
                    </h3>
                </div>
                
                <div class="table-responsive">
                    <table class="table-horario">
                        <thead>
                            <tr>
                                <th style="width: 130px;">Bloque</th>
                                <?php foreach ($dias as $d): ?><th><?= $d ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloques_horarios as $b): ?>
                            <tr>
                                <td style="background: #fcfdfe; border-right: 1px solid #edf2f7;">
                                    <span class="badge-hora"><?= $b[0] ?> - <?= $b[1] ?></span>
                                </td>
                                <?php foreach ($dias as $dia): 
                                    $h_inicio = $b[0] . ':00';
                                    $clase = $horario_maestro[$dia][$h_inicio] ?? null;
                                ?>
                                <td style="height: 115px; min-width: 170px;">
                                    <?php if ($clase): ?>
                                        <div class="info-materia"><?= htmlspecialchars($clase['materia']) ?></div>
                                        <div class="info-sub">
                                            <span><i class="fa-solid fa-users"></i> <?= htmlspecialchars($clase['semestre'] . "º " . $clase['grupo_nombre']) ?></span><br>
                                            <span style="margin-top: 4px; display: inline-block;">
                                                <i class="fa-solid fa-location-arrow"></i> <?= htmlspecialchars($clase['aula_nombre'] ?: 'S/A') ?>
                                            </span>
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

            <div class="text-center no-print" style="margin-bottom: 50px;">
                <button onclick="window.print()" class="btn-action btn-primary-custom">
                    <i class="fa-solid fa-print"></i> Generar Versión Impresa
                </button>
            </div>

        <?php else: ?>
            <div class="content-card text-center py-5" style="border-left: none; border-top: 6px solid #e2e8f0;">
                <div style="background: #f8f9fa; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class="fa-solid fa-user-tie" style="font-size: 2rem; color: #cbd5e1;"></i>
                </div>
                <h4 style="color: var(--secondary); font-weight: 600;">Sin selección</h4>
                <p class="text-muted small">Por favor, utilice el buscador superior para cargar los datos del docente.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>