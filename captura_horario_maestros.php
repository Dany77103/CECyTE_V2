<?php
// horario_maestros_captura.php - VERSIÓN PDO REESTILIZADA
session_start();

require_once 'config.php';
require_once 'conexion.php';

// Verificar permisos
if (!isset($_SESSION['username']) || $_SESSION['rol'] != 'admin') {
    header('Location: login.php');
    exit();
}

if (!isset($con)) {
    die("Error: No se pudo conectar a la base de datos.");
}

// Obtener maestros activos
$query_maestros = "SELECT m.* FROM maestros m WHERE m.activo = 'Activo' ORDER BY m.apellido_paterno, m.nombre";
$maestros_result = $con->query($query_maestros);

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

// Obtener aulas
try {
    $aulas_result = $con->query("SELECT * FROM aulas WHERE activo = 1 ORDER BY nombre");
    $aulas = $aulas_result->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $aulas = []; // Manejo por si no existe la tabla
}

$dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
$bloques_horarios = [
    ['11:45', '12:30'], ['12:30', '13:15'], ['13:15', '14:00'],
    ['14:00', '14:45'], ['14:45', '15:30'], ['15:30', '16:15'], ['16:15', '17:00']
];

// Procesar Guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_maestro'], $_POST['periodo'])) {
        $id_maestro = intval($_POST['id_maestro']);
        $periodo = $_POST['periodo'];
        
        $con->beginTransaction();
        try {
            $con->prepare("DELETE FROM horarios_maestros WHERE id_maestro = ? AND periodo = ?")->execute([$id_maestro, $periodo]);
            
            $insert_stmt = $con->prepare("INSERT INTO horarios_maestros (id_maestro, id_materia, dia, hora_inicio, hora_fin, id_aula, id_grupo, periodo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($dias as $dia) {
                if (isset($_POST['horario'][$dia])) {
                    foreach ($_POST['horario'][$dia] as $hora_idx => $datos) {
                        if (!empty($datos['materia'])) {
                            $hora = $bloques_horarios[$hora_idx];
                            $insert_stmt->execute([
                                $id_maestro, intval($datos['materia']), $dia, 
                                $hora[0], $hora[1], 
                                !empty($datos['aula']) ? intval($datos['aula']) : NULL,
                                !empty($datos['grupo']) ? intval($datos['grupo']) : NULL, 
                                $periodo
                            ]);
                        }
                    }
                }
            }
            $con->commit();
            $_SESSION['mensaje'] = "Horario actualizado con éxito.";
        } catch (Exception $e) {
            $con->rollBack();
            $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
        }
        header("Location: horario_maestros_captura.php?maestro=" . $id_maestro);
        exit();
    }
}

// Cargar horario actual si se seleccionó maestro
$horario_actual = [];
if (isset($_GET['maestro'])) {
    $id_maestro = intval($_GET['maestro']);
    $stmt = $con->prepare("SELECT * FROM horarios_maestros WHERE id_maestro = ? AND periodo = 'FEB 2026-JUL 2026'");
    $stmt->execute([$id_maestro]);
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
    <title>Gestión de Horarios | CECyTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --secondary: #6c757d;
            --bg: #f4f6f9;
            --white: #ffffff;
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg); font-family: 'Inter', sans-serif; padding-top: 90px; color: #333; }

        /* NAVBAR */
        .navbar {
            background: var(--white); height: 70px; display: flex;
            align-items: center; justify-content: space-between; padding: 0 5%;
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .navbar-brand { display: flex; align-items: center; gap: 15px; text-decoration: none; }
        .navbar-brand img { height: 45px; }
        .navbar-brand span { font-weight: 700; color: var(--primary); font-size: 1.2rem; }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }

        /* CARDS */
        .card { 
            background: var(--white); border-radius: 15px; padding: 25px; 
            margin-bottom: 25px; box-shadow: var(--shadow-md); border: none;
        }
        .card-header { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;
        }
        .card-header h2 { font-size: 1.2rem; color: var(--primary); display: flex; align-items: center; gap: 10px; }

        /* TABLA HORARIO */
        .table-horario { width: 100%; border-collapse: separate; border-spacing: 4px; }
        .table-horario th { background: #f8f9fa; padding: 12px; font-size: 0.8rem; text-transform: uppercase; border-radius: 8px; color: var(--secondary); }
        .bloque-time { background: var(--primary); color: white; padding: 10px; border-radius: 8px; font-weight: 700; text-align: center; min-width: 100px; }
        
        .celda-horario { 
            background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 8px;
            transition: var(--transition); width: 18%;
        }

        .select-horario {
            width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 6px;
            font-size: 0.75rem; margin-bottom: 4px; outline: none;
        }

        .select-horario:focus { border-color: var(--primary-light); }

        /* BOTONES */
        .btn { 
            padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; 
            cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(26,83,48,0.3); }
        .btn-secondary { background: #e9ecef; color: var(--secondary); }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        @media print { .navbar, .btn, .card:first-child { display: none; } body { padding: 0; } .card { box-shadow: none; border: 1px solid #eee; } }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo_cecyte.jpg" alt="Logo">
            <span>CECyTE Gestión Horarios</span>
        </a>
        <div class="nav-actions">
            <a href="main.php" class="btn btn-secondary"><i class="fas fa-home"></i></a>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="GET" class="form-grid" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 20px; align-items: flex-end;">
                <div>
                    <label style="font-size: 0.8rem; font-weight: 700; color: var(--secondary); margin-bottom: 5px; display: block;">DOCENTE</label>
                    <select name="maestro" class="select-horario" style="font-size: 1rem; padding: 10px;" onchange="this.form.submit()">
                        <option value="">Seleccione un maestro...</option>
                        <?php 
                        $maestros_result->execute();
                        while ($maestro = $maestros_result->fetch(PDO::FETCH_ASSOC)): 
                        ?>
                            <option value="<?= $maestro['id_maestro'] ?>" <?= (isset($_GET['maestro']) && $_GET['maestro'] == $maestro['id_maestro']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($maestro['apellido_paterno'] . ' ' . $maestro['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 700; color: var(--secondary); margin-bottom: 5px; display: block;">PERIODO ACTIVO</label>
                    <input type="text" class="select-horario" style="font-size: 1rem; padding: 10px; background: #f8f9fa;" value="FEB 2026-JUL 2026" readonly>
                </div>
                <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i></button>
            </form>
        </div>

        <?php if (isset($_GET['maestro'])): ?>
        <div class="card">
            <div class="card-header">
                <?php 
                    $stmt = $con->prepare("SELECT nombre, apellido_paterno FROM maestros WHERE id_maestro = ?");
                    $stmt->execute([intval($_GET['maestro'])]);
                    $mInfo = $stmt->fetch();
                ?>
                <h2><i class="fas fa-calendar-alt"></i> Horario: <?= htmlspecialchars($mInfo['nombre'] . ' ' . $mInfo['apellido_paterno']) ?></h2>
            </div>

            <form method="POST">
                <input type="hidden" name="id_maestro" value="<?= intval($_GET['maestro']) ?>">
                <input type="hidden" name="periodo" value="FEB 2026-JUL 2026">
                
                <div style="overflow-x: auto;">
                    <table class="table-horario">
                        <thead>
                            <tr>
                                <th>Bloque</th>
                                <?php foreach ($dias as $d): ?><th><?= $d ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloques_horarios as $idx => $b): ?>
                            <tr>
                                <td class="bloque-time"><?= $b[0] ?> <br> <small style="font-weight: 400; font-size: 0.7rem;"><?= $b[1] ?></small></td>
                                <?php foreach ($dias as $dia): 
                                    $hk = $b[0] . ':00';
                                    $ha = isset($horario_actual[$dia][$hk]) ? $horario_actual[$dia][$hk] : null;
                                ?>
                                <td class="celda-horario" id="celda_<?= $dia ?>_<?= $idx ?>">
                                    <select name="horario[<?= $dia ?>][<?= $idx ?>][materia]" class="select-horario materia-select" onchange="applyStyle(this)">
                                        <option value="">- Materia -</option>
                                        <?php foreach ($materias as $idm => $mat): ?>
                                            <option value="<?= $idm ?>" <?= ($ha && $ha['id_materia'] == $idm) ? 'selected' : '' ?> data-color="<?= $mat['color'] ?? '#2e7d32' ?>">
                                                <?= htmlspecialchars($mat['materia']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="horario[<?= $dia ?>][<?= $idx ?>][grupo]" class="select-horario">
                                        <option value="">- Grupo -</option>
                                        <?php foreach ($grupos as $idg => $g): ?>
                                            <option value="<?= $idg ?>" <?= ($ha && $ha['id_grupo'] == $idg) ? 'selected' : '' ?>><?= $g['nombre'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="horario[<?= $dia ?>][<?= $idx ?>][aula]" class="select-horario">
                                        <option value="">- Aula -</option>
                                        <?php foreach ($aulas as $a): ?>
                                            <option value="<?= $a['id_aula'] ?>" <?= ($ha && $ha['id_aula'] == $a['id_aula']) ? 'selected' : '' ?>><?= $a['nombre'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="text-align: right; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 1rem;">
                        <i class="fas fa-save"></i> Guardar Carga Académica
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function applyStyle(select) {
            const option = select.options[select.selectedIndex];
            const color = option.getAttribute('data-color');
            const cell = select.closest('td');
            
            if (select.value) {
                cell.style.backgroundColor = color + '15'; // 15% opacidad
                cell.style.borderLeft = '5px solid ' + color;
            } else {
                cell.style.backgroundColor = '#fff';
                cell.style.borderLeft = '1px solid #e0e0e0';
            }
        }

        // Inicializar colores al cargar
        document.querySelectorAll('.materia-select').forEach(s => applyStyle(s));
    </script>
</body>
</html>