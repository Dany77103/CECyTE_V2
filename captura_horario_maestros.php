<?php
// horario_maestros_captura.php
session_start();

require_once 'config.php';
require_once 'conexion.php';

if (!isset($_SESSION['username']) || $_SESSION['rol'] != 'admin') {
    header('Location: login.php');
    exit();
}

if (!isset($con)) {
    die("Error: No se pudo conectar a la base de datos.");
}

$dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
$bloques_horarios = [
    ['11:45:00', '12:30:00'], ['12:30:00', '13:15:00'], ['13:15:00', '14:00:00'],
    ['14:00:00', '14:45:00'], ['14:45:00', '15:30:00'], ['15:30:00', '16:15:00'], ['16:15:00', '17:00:00']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_maestro'])) {
    $id_maestro = intval($_POST['id_maestro']);
    $periodo = $_POST['periodo'];
    
    try {
        $con->beginTransaction();
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
                            $id_maestro, $materia, $dia, $hora[0], $hora[1], $aula_id, $grupo_id, $periodo
                        ]);
                    }
                }
            }
        }
        $con->commit();
        $_SESSION['mensaje'] = "Horario guardado exitosamente";
        header("Location: consulta_horario_maestro.php?maestro=" . $id_maestro);
        exit();

    } catch (Exception $e) {
        if ($con->inTransaction()) $con->rollBack();
        $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
    }
}

$query_maestros = "SELECT * FROM maestros WHERE activo = 'Activo' ORDER BY apellido_paterno, nombre";
$maestros_result = $con->query($query_maestros);
$materias = $con->query("SELECT * FROM materias ORDER BY materia")->fetchAll(PDO::FETCH_ASSOC);
$grupos = $con->query("SELECT * FROM grupos WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$aulas = $con->query("SELECT * FROM aulas WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Captura Maestro | CECyTE</title>
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

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            padding-top: 90px;
            color: #333;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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

        .container { 
            max-width: 1400px; margin: 0 auto; padding: 0 20px; 
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* --- UI COMPONENTS --- */
        .content-card {
            background: var(--white); padding: 30px; border-radius: 20px;
            box-shadow: var(--shadow-md); border-left: 6px solid var(--primary);
            margin-bottom: 25px;
        }

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-flex h2 { color: var(--primary); font-size: 1.5rem; font-weight: 700; }

        .label-mini {
            font-size: 0.7rem; font-weight: 700; color: var(--secondary);
            text-transform: uppercase; display: block; margin-bottom: 4px;
        }

        .form-select, .form-control {
            border-radius: 10px; border: 1px solid #e0e0e0; padding: 10px;
            font-size: 0.9rem; transition: var(--transition);
        }

        /* --- TABLE STYLE --- */
        .table-horario {
            width: 100%; border-collapse: separate; border-spacing: 0;
            border-radius: 15px; overflow: hidden; border: 1px solid #edf2f7;
        }
        .table-horario th {
            background: #f8f9fa; color: var(--primary); padding: 15px;
            font-size: 0.8rem; text-transform: uppercase; font-weight: 700;
        }
        .celda-horario {
            padding: 12px !important; background: #fff; min-width: 180px;
            border-bottom: 1px solid #f0f0f0; border-right: 1px solid #f0f0f0;
            transition: var(--transition);
        }
        .badge-hora {
            background: #e8f5e9; color: var(--primary); padding: 6px 10px;
            border-radius: 8px; font-weight: 700; font-size: 0.8rem;
        }

        .select-horario { font-size: 0.8rem !important; margin-bottom: 5px; background: #fcfcfc; }

        /* --- BUTTONS --- */
        .btn-action {
            padding: 10px 20px; border-radius: 10px; text-decoration: none;
            font-weight: 600; font-size: 0.85rem; transition: var(--transition);
            display: inline-flex; align-items: center; gap: 8px; border: none;
        }
        .btn-save { background: var(--primary); color: white; width: 100%; justify-content: center; margin-top: 20px; padding: 15px; font-size: 1rem; }
        .btn-save:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-back { background: #e9ecef; color: var(--secondary); }
    </style>
</head>
<body>
    
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="CECyTE Logo">
            <span>Gestión Académica</span>
        </a>
        <div style="font-size: 0.85rem; font-weight: 600; color: var(--secondary);">
            <i class="fa-solid fa-user-tie"></i> Admin: <?php echo $_SESSION['username']; ?>
        </div>
    </nav>
    
    <div class="container">
        <div class="content-card">
            <div class="header-flex">
                <div>
                    <h2><i class="fa-solid fa-chalkboard-user"></i> Captura por Maestro</h2>
                    <p style="color: var(--secondary); font-size: 0.85rem;">Asignación de carga académica individual</p>
                </div>
                <a href="horarios.php" class="btn-action btn-back"><i class="fa-solid fa-arrow-left"></i> Volver</a>
            </div>

            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <label class="label-mini">Docente a configurar</label>
                    <select name="maestro" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Seleccionar Maestro --</option>
                        <?php foreach ($maestros_result as $maestro): ?>
                            <option value="<?php echo $maestro['id_maestro']; ?>" <?php echo (isset($_GET['maestro']) && $_GET['maestro'] == $maestro['id_maestro']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($maestro['apellido_paterno'] . ' ' . $maestro['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="label-mini">Periodo Escolar</label>
                    <input type="text" class="form-control" value="FEB 2026-JUL 2026" readonly style="background: #f8f9fa; font-weight: 600;">
                </div>
            </form>
        </div>

        <?php if (isset($_GET['maestro'])): 
            $id_maestro = intval($_GET['maestro']);
            $stmt = $con->prepare("SELECT * FROM maestros WHERE id_maestro = ?");
            $stmt->execute([$id_maestro]);
            $m = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <form method="POST">
            <input type="hidden" name="id_maestro" value="<?php echo $id_maestro; ?>">
            <input type="hidden" name="periodo" value="FEB 2026-JUL 2026">
            
            <div class="content-card" style="padding: 10px; border-left: none; border-top: 6px solid var(--primary);">
                <div style="overflow-x: auto;">
                    <table class="table-horario">
                        <thead>
                            <tr class="text-center">
                                <th style="width: 100px;">Bloque</th>
                                <?php foreach ($dias as $d): ?><th><?php echo $d; ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloques_horarios as $idx => $b): ?>
                            <tr>
                                <td style="text-align: center; background: #fcfdfe; border-right: 1px solid #edf2f7;">
                                    <span class="badge-hora"><?php echo substr($b[0], 0, 5); ?></span>
                                </td>
                                <?php foreach ($dias as $dia): 
                                    $h_inicio = $b[0];
                                    $ha = $horario_actual[$dia][$h_inicio] ?? null;
                                ?>
                                <td class="celda-horario materia-container">
                                    <label class="label-mini">Materia</label>
                                    <select name="horario[<?php echo $dia; ?>][<?php echo $idx; ?>][materia]" class="form-select select-horario materia-select">
                                        <option value="">- Materia -</option>
                                        <?php foreach ($materias as $mat): ?>
                                            <option value="<?php echo $mat['id_materia']; ?>" 
                                                <?php echo ($ha && $ha['id_materia'] == $mat['id_materia']) ? 'selected' : ''; ?>
                                                data-color="<?php echo $mat['color'] ?? '#3b82f6'; ?>">
                                                <?php echo htmlspecialchars($mat['materia']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <div class="row g-1">
                                        <div class="col-6">
                                            <label class="label-mini">Grupo</label>
                                            <select name="horario[<?php echo $dia; ?>][<?php echo $idx; ?>][grupo]" class="form-select select-horario">
                                                <option value="">- G -</option>
                                                <?php foreach ($grupos as $g): ?>
                                                    <option value="<?php echo $g['id_grupo']; ?>" <?php echo ($ha && $ha['id_grupo'] == $g['id_grupo']) ? 'selected' : ''; ?>>
                                                        <?php echo $g['nombre']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="label-mini">Aula</label>
                                            <select name="horario[<?php echo $dia; ?>][<?php echo $idx; ?>][aula]" class="form-select select-horario">
                                                <option value="">- A -</option>
                                                <?php foreach ($aulas as $a): ?>
                                                    <option value="<?php echo $a['id_aula']; ?>" <?php echo ($ha && $ha['id_aula'] == $a['id_aula']) ? 'selected' : ''; ?>>
                                                        <?php echo $a['nombre']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <button type="submit" class="btn-action btn-save">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Carga Académica
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        function applyColor(s) {
            const opt = s.options[s.selectedIndex];
            const col = opt.getAttribute('data-color') || '#ffffff';
            const cell = s.closest('td');
            if (s.value) {
                cell.style.backgroundColor = col + '12'; // Opacidad muy baja para fondo
                cell.style.borderLeft = '4px solid ' + col;
            } else {
                cell.style.backgroundColor = '';
                cell.style.borderLeft = 'none';
            }
        }
        
        document.querySelectorAll('.materia-select').forEach(s => {
            s.addEventListener('change', () => applyColor(s));
            applyColor(s); 
        });
    </script>
</body>
</html>