<?php
// captura_horarios_grupos.php
session_start();
require_once 'config.php';
require_once 'conexion.php';

if (!isset($_SESSION['username']) || $_SESSION['rol'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Consultas de preparación (Lógica Original)
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

// Lógica de guardado (Lógica Original)
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
        header("Location: consulta_horarios.php?grupo=" . $id_grupo);
        exit();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captura por Grupos | CECyTE</title>
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
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            color: #333;
            padding-top: 90px;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- NAVBAR --- */
        .navbar {
            background: var(--white);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand { display: flex; align-items: center; gap: 15px; text-decoration: none; }
        .navbar-brand img { height: 45px; }
        .navbar-brand span { font-weight: 700; color: var(--primary); font-size: 1.2rem; }

        .container { 
            max-width: 1300px; 
            margin: 0 auto; 
            padding: 0 20px; 
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* --- CONTENT CARD --- */
        .content-card {
            background: var(--white);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border-left: 6px solid var(--primary);
            margin-bottom: 30px;
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header-flex h2 { color: var(--primary); font-size: 1.6rem; }

        /* --- FORM STYLES --- */
        .form-select, .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 10px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }

        .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26, 83, 48, 0.1); }

        /* --- TABLE STYLES --- */
        .table-horario {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #edf2f7;
        }

        .table-horario th {
            background: #f8f9fa;
            color: var(--primary);
            padding: 15px;
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: 700;
            border-bottom: 2px solid #edf2f7;
        }

        .badge-hora {
            background: #e8f5e9;
            color: var(--primary);
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-block;
        }

        .celda-input {
            padding: 15px !important;
            background: #fff;
            min-width: 200px;
        }

        .select-custom {
            font-size: 0.85rem !important;
            padding: 6px 10px !important;
            margin-bottom: 8px;
            background-color: #fcfcfc;
        }

        .label-mini {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--secondary);
            text-transform: uppercase;
            display: block;
            margin-bottom: 3px;
            text-align: left;
        }

        /* --- BUTTONS --- */
        .btn-action {
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-save { background: var(--primary); color: white; width: 100%; justify-content: center; margin-top: 20px; }
        .btn-save:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: var(--shadow-md); }

        .btn-back { background: #e9ecef; color: var(--secondary); }
        .btn-back:hover { background: #dee2e6; }
    </style>
</head>
<body>
    
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div style="display: flex; align-items: center; gap: 20px;">
            <span style="font-size: 0.9rem; font-weight: 600; color: var(--secondary);">
                <i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
        </div>
    </nav>
    
    <div class="container">
        <div class="content-card">
            <div class="header-flex">
                <div>
                    <h2><i class="fa-solid fa-calendar-plus"></i> Captura de Horario por Grupo</h2>
                    <p style="color: var(--secondary); font-size: 0.9rem;">Asignación de materias, docentes y aulas</p>
                </div>
                <a href="horarios.php" class="btn-action btn-back"><i class="fa-solid fa-arrow-left"></i> Volver</a>
            </div>

            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <label class="label-mini">Seleccionar Grupo</label>
                    <select name="grupo" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Seleccione un grupo para editar --</option>
                        <?php 
                        $grupos_result->execute(); // Reiniciar puntero si es necesario
                        while ($g = $grupos_result->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= $g['id_grupo'] ?>" <?= (isset($_GET['grupo']) && $_GET['grupo'] == $g['id_grupo']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['nombre'] . " - " . $g['semestre'] . "º Semestre") ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="label-mini">Ciclo Escolar</label>
                    <input type="text" class="form-control" value="FEB 2026-JUL 2026" readonly style="background: #f8f9fa; font-weight: 600;">
                </div>
            </form>
        </div>

        <?php if (isset($_GET['grupo'])): ?>
        <form method="POST">
            <input type="hidden" name="id_grupo" value="<?= $_GET['grupo'] ?>">
            <input type="hidden" name="periodo" value="FEB 2026-JUL 2026">
            
            <div class="content-card" style="padding: 10px; border-left: none; border-top: 6px solid var(--primary);">
                <div style="overflow-x: auto;">
                    <table class="table-horario">
                        <thead>
                            <tr>
                                <th style="width: 120px;">Bloque</th>
                                <?php foreach ($dias as $d): ?><th><?= $d ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloques_horarios as $idx => $b): ?>
                            <tr>
                                <td style="text-align: center; background: #fcfdfe; border-right: 1px solid #edf2f7;">
                                    <span class="badge-hora"><?= $b[0] ?> - <?= $b[1] ?></span>
                                </td>
                                <?php foreach ($dias as $dia): 
                                    $h_key = $b[0] . ':00';
                                    $act = $horario_actual[$dia][$h_key] ?? null;
                                ?>
                                <td class="celda-input">
                                    <label class="label-mini">Materia</label>
                                    <select name="horario[<?= $dia ?>][<?= $idx ?>][materia]" class="form-select select-custom">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($materias as $m): ?>
                                            <option value="<?= $m['id_materia'] ?>" <?= ($act && $act['id_materia'] == $m['id_materia']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['materia']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label class="label-mini">Docente</label>
                                    <select name="horario[<?= $dia ?>][<?= $idx ?>][maestro]" class="form-select select-custom">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($maestros as $mtro): ?>
                                            <option value="<?= $mtro['id_maestro'] ?>" <?= ($act && $act['id_maestro'] == $mtro['id_maestro']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($mtro['apellido_paterno'] . " " . $mtro['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label class="label-mini">Aula</label>
                                    <select name="horario[<?= $dia ?>][<?= $idx ?>][aula]" class="form-select select-custom">
                                        <option value="">-- Seleccionar --</option>
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
                
                <button type="submit" class="btn-action btn-save">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Horario Completo
                </button>
            </div>
        </form>
        <?php endif; ?>

        <footer style="text-align: center; padding: 40px 0; color: var(--secondary); font-size: 0.85rem;">
            CECyTE Santa Catarina &copy; <?php echo date('Y'); ?>
        </footer>
    </div>
</body>
</html>