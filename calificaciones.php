<?php
session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['loggedin']) || ($_SESSION['tipo_usuario'] !== 'maestro' && $_SESSION['tipo_usuario'] !== 'sistema' && $_SESSION['tipo_usuario'] !== 'admin')) {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Obtener parámetros de la materia y grupo
$id_materia = isset($_GET['materia']) ? $_GET['materia'] : null;
$id_grupo = isset($_GET['grupo']) ? $_GET['grupo'] : null;
$id_parcial = isset($_GET['parcial']) ? $_GET['parcial'] : null;

$id_maestro_actual = ($_SESSION['tipo_usuario'] === 'maestro') ? $_SESSION['user_id'] : null;

// --- SELECTOR DE CLASE (Si no hay parámetros) ---
if (!$id_materia || !$id_grupo) {
    $sql_materias = "SELECT DISTINCT hm.id_materia, m.materia, hm.id_grupo, g.nombre as grupo_nombre, c.nombre as carrera_nombre
                    FROM horarios_maestros hm
                    JOIN materias m ON hm.id_materia = m.id_materia
                    JOIN grupos g ON hm.id_grupo = g.id_grupo
                    JOIN carreras c ON g.id_carrera = c.id_carrera
                    WHERE hm.id_maestro = :id_maestro AND hm.estatus = 'Activo'
                    ORDER BY m.materia, g.nombre";
    
    $stmt_materias = $con->prepare($sql_materias);
    $stmt_materias->execute(['id_maestro' => $id_maestro_actual]);
    $materias_grupos = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_parciales = "SELECT * FROM parciales WHERE activo = 1 ORDER BY numero_parcial";
    $stmt_parciales = $con->prepare($sql_parciales);
    $stmt_parciales->execute();
    $parciales = $stmt_parciales->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Seleccionar Clase | CECyTE</title>
        <style>
            :root { --primary: #1b4d3e; --accent: #27ae60; --light-green: #e8f5e9; --bg: #f9fbf9; --white: #ffffff; --text: #2c3e50; }
            body { font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 40px 20px; background: var(--bg); color: var(--text); }
            .container { max-width: 900px; margin: 0 auto; background: var(--white); padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
            h1 { text-align: center; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 30px; border-bottom: 4px solid var(--accent); display: inline-block; padding-bottom: 5px; }
            .clase-card { background: #fff; border: 1px solid #e1e8ed; border-left: 6px solid var(--primary); padding: 20px; margin-bottom: 20px; border-radius: 8px; transition: transform 0.2s; }
            .clase-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
            .clase-card h3 { margin: 0 0 10px 0; color: var(--primary); }
            .parcial-group { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; align-items: center; }
            .parcial-btn { padding: 8px 18px; background: var(--white); color: var(--primary); border: 2px solid var(--primary); border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.3s; }
            .parcial-btn:hover { background: var(--primary); color: white; }
            .info-box { background: var(--light-green); padding: 20px; border-radius: 8px; text-align: center; color: var(--primary); }
        </style>
    </head>
    <body>
        <div class="container" style="text-align: center;">
            <div style="margin-bottom: 20px;"><img src="logo_cecyte.png" alt="CECyTE" style="height: 60px;"></div>
            <h1>Mis Clases CECyTE</h1>
            <?php if (empty($materias_grupos)): ?>
                <div class="info-box">No hay clases asignadas actualmente.</div>
            <?php else: 
                $materias_agrupadas = [];
                foreach ($materias_grupos as $mg) {
                    $materias_agrupadas[$mg['id_materia']]['materia'] = $mg['materia'];
                    $materias_agrupadas[$mg['id_materia']]['grupos'][] = $mg;
                }
                foreach ($materias_agrupadas as $id_materia => $m): ?>
                    <div class="clase-card" style="text-align: left;">
                        <h3><?= htmlspecialchars($m['materia']) ?></h3>
                        <?php foreach ($m['grupos'] as $g): ?>
                            <div class="parcial-group">
                                <strong>Grupo <?= htmlspecialchars($g['grupo_nombre']) ?>:</strong>
                                <?php foreach ($parciales as $p): ?>
                                    <a href="calificaciones.php?materia=<?= $id_materia ?>&grupo=<?= $g['id_grupo'] ?>&parcial=<?= $p['id_parcial'] ?>" class="parcial-btn">P<?= $p['numero_parcial'] ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; 
            endif; ?>
        </div>
    </body>
    </html>
    <?php exit();
}

// --- LÓGICA DE BACKEND ---
if ($_SESSION['tipo_usuario'] === 'maestro') {
    $sql_verificar = "SELECT COUNT(*) as count FROM horarios_maestros WHERE id_maestro = :id_maestro AND id_materia = :id_materia AND id_grupo = :id_grupo AND estatus = 'Activo'";
    $stmt_verificar = $con->prepare($sql_verificar);
    $stmt_verificar->execute(['id_maestro' => $id_maestro_actual, 'id_materia' => $id_materia, 'id_grupo' => $id_grupo]);
    if ($stmt_verificar->fetch(PDO::FETCH_ASSOC)['count'] == 0) { die("Acceso denegado."); }
}

$sql_info = "SELECT m.materia, g.nombre as grupo_nombre, p.numero_parcial, p.nombre as parcial_nombre, p.fecha_inicio, p.fecha_fin
             FROM materias m
             LEFT JOIN grupos g ON g.id_grupo = :id_grupo
             LEFT JOIN parciales p ON p.id_parcial = :id_parcial AND p.activo = 1
             WHERE m.id_materia = :id_materia";
$stmt_info = $con->prepare($sql_info);
$stmt_info->execute(['id_materia' => $id_materia, 'id_grupo' => $id_grupo, 'id_parcial' => $id_parcial]);
$info_clase = $stmt_info->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $con->beginTransaction();
        foreach ($_POST['calificaciones'] as $id_alumno => $cal) {
            $libreta = floatval($cal['libreta']); $asistencia = floatval($cal['asistencia']);
            $participacion = floatval($cal['participacion']); $examen = floatval($cal['examen']);
            
            $sql_upsert = "INSERT INTO calificaciones_parcial (id_alumno, matricula, id_materia, id_grupo, id_parcial, libreta_guia_puntos, asistencia_puntos, participacion_puntos, examen_puntos, usuario_registro)
                           VALUES (:id_alumno, (SELECT matricula FROM alumnos WHERE id_alumno = :id_alumno2), :id_materia, :id_grupo, :id_parcial, :libreta, :asistencia, :part, :examen, :usr)
                           ON DUPLICATE KEY UPDATE libreta_guia_puntos=:libreta, asistencia_puntos=:asistencia, participacion_puntos=:part, examen_puntos=:examen, usuario_registro=:usr";
            $stmt = $con->prepare($sql_upsert);
            $stmt->execute(['id_alumno'=>$id_alumno, 'id_alumno2'=>$id_alumno, 'id_materia'=>$id_materia, 'id_grupo'=>$id_grupo, 'id_parcial'=>$id_parcial, 'libreta'=>$libreta, 'asistencia'=>$asistencia, 'part'=>$participacion, 'examen'=>$examen, 'usr'=>$_SESSION['username']]);
        }
        $con->commit(); $mensaje_exito = "Calificaciones guardadas con éxito.";
    } catch (Exception $e) { $con->rollBack(); $mensaje_error = "Error: " . $e->getMessage(); }
}

$sql_alumnos = "SELECT a.id_alumno, a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno, cp.libreta_guia_puntos, cp.asistencia_puntos, cp.participacion_puntos, cp.examen_puntos
                FROM alumnos a
                LEFT JOIN calificaciones_parcial cp ON a.id_alumno = cp.id_alumno AND cp.id_materia = :id_materia AND cp.id_grupo = :id_grupo AND cp.id_parcial = :id_parcial
                WHERE a.id_grupo = :id_grupo2 ORDER BY a.apellido_paterno, a.nombre";
$stmt_alumnos = $con->prepare($sql_alumnos);
$stmt_alumnos->execute(['id_materia' => $id_materia, 'id_grupo' => $id_grupo, 'id_parcial' => $id_parcial, 'id_grupo2' => $id_grupo]);
$alumnos = $stmt_alumnos->fetchAll(PDO::FETCH_ASSOC);

$otros_parciales = $con->query("SELECT * FROM parciales WHERE activo = 1 ORDER BY numero_parcial")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Captura | <?= htmlspecialchars($info_clase['materia']) ?></title>
    <style>
        :root { --primary: #1b4d3e; --accent: #27ae60; --bg: #f4f7f6; --white: #ffffff; --light-green: #e8f5e9; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; background: var(--bg); color: #2d3436; padding-bottom: 50px; }
        .navbar { background: var(--primary); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid var(--accent); position: sticky; top: 0; z-index: 1000; }
        .main-container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        .card-header { background: var(--white); padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; border-top: 5px solid var(--primary); }
        .info-pill { background: var(--light-green); color: var(--primary); padding: 10px 20px; border-radius: 50px; font-size: 14px; font-weight: 700; }
        .table-container { background: var(--white); border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        table { border-collapse: collapse; width: 100%; font-size: 14px; }
        th { background: #f8f9fa; color: var(--primary); font-weight: 700; text-transform: uppercase; padding: 15px 10px; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px 10px; border-bottom: 1px solid #eee; text-align: center; }
        .input-cal { width: 65px; padding: 8px; border: 1px solid #cbd5e0; border-radius: 4px; text-align: center; font-weight: bold; }
        .input-cal:focus { border-color: var(--accent); outline: none; box-shadow: 0 0 5px rgba(39,174,96,0.3); }
        .total-cell { font-weight: 800; color: white; background: var(--primary) !important; }
        .btn-save { background: var(--accent); color: white; padding: 15px 40px; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-save:hover { background: #219150; transform: translateY(-2px); }
        .nav-btn { text-decoration: none; padding: 8px 15px; background: #e2e8f0; color: #4a5568; border-radius: 4px; font-weight: 600; }
        .nav-btn.active { background: var(--primary); color: white; }
        .msg.success { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid var(--accent); }
    </style>
    <script>
        function calcularTotales(id) {
            const l = parseFloat(document.getElementById(`libreta_${id}`).value) || 0;
            const a = parseFloat(document.getElementById(`asistencia_${id}`).value) || 0;
            const p = parseFloat(document.getElementById(`participacion_${id}`).value) || 0;
            const e = parseFloat(document.getElementById(`examen_${id}`).value) || 0;
            const total = l + a + p + e;
            document.getElementById(`total_${id}`).textContent = total.toFixed(2);
        }
    </script>
</head>
<body>
    <div class="navbar">
        <div style="font-weight: 800; font-size: 20px;">CECyTE <span style="color: var(--accent)">SISTEMA</span></div>
        <div>Profesor: <?= htmlspecialchars($_SESSION['username']) ?> | <a href="logout.php" style="color: white; text-decoration: none; font-size: 12px; background: rgba(0,0,0,0.2); padding: 5px 10px; border-radius: 4px;">Salir</a></div>
    </div>

    <div class="main-container">
        <div class="card-header">
            <div>
                <h2 style="margin:0; color: var(--primary);"><?= htmlspecialchars($info_clase['materia']) ?></h2>
                <div style="color: #718096; font-size: 14px; margin-top: 5px;">Grupo: <strong><?= htmlspecialchars($info_clase['grupo_nombre']) ?></strong></div>
            </div>
            <div class="info-pill"><?= htmlspecialchars($info_clase['parcial_nombre']) ?></div>
        </div>

        <div style="margin-bottom: 20px; display: flex; gap: 10px;">
            <?php foreach ($otros_parciales as $p): ?>
                <a href="calificaciones.php?materia=<?= $id_materia ?>&grupo=<?= $id_grupo ?>&parcial=<?= $p['id_parcial'] ?>" 
                   class="nav-btn <?= $p['id_parcial'] == $id_parcial ? 'active' : '' ?>">Parcial <?= $p['numero_parcial'] ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (isset($mensaje_exito)): ?><div class="msg success"><?= $mensaje_exito ?></div><?php endif; ?>

        <form method="POST">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: left; padding-left: 20px;">Alumno</th>
                            <th>Libreta (40)</th>
                            <th>Asist (10)</th>
                            <th>Part (10)</th>
                            <th>Examen (40)</th>
                            <th style="background: var(--primary); color: white;">Total Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos as $alumno): 
                            $id = $alumno['id_alumno'];
                            $l = $alumno['libreta_guia_puntos'] ?? 0;
                            $a = $alumno['asistencia_puntos'] ?? 0;
                            $p = $alumno['participacion_puntos'] ?? 0;
                            $e = $alumno['examen_puntos'] ?? 0;
                        ?>
                        <tr>
                            <td style="text-align: left; font-weight: 600; padding-left: 20px; border-left: 4px solid var(--accent);">
                                <?= htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['nombre']) ?>
                            </td>
                            <td><input type="number" step="0.1" id="libreta_<?= $id ?>" name="calificaciones[<?= $id ?>][libreta]" value="<?= $l ?>" class="input-cal" oninput="calcularTotales(<?= $id ?>)"></td>
                            <td><input type="number" step="0.1" id="asistencia_<?= $id ?>" name="calificaciones[<?= $id ?>][asistencia]" value="<?= $a ?>" class="input-cal" oninput="calcularTotales(<?= $id ?>)"></td>
                            <td><input type="number" step="0.1" id="participacion_<?= $id ?>" name="calificaciones[<?= $id ?>][participacion]" value="<?= $p ?>" class="input-cal" oninput="calcularTotales(<?= $id ?>)"></td>
                            <td><input type="number" step="0.1" id="examen_<?= $id ?>" name="calificaciones[<?= $id ?>][examen]" value="<?= $e ?>" class="input-cal" oninput="calcularTotales(<?= $id ?>)" style="border-color: var(--accent);"></td>
                            <td id="total_<?= $id ?>" class="total-cell"><?= number_format($l+$a+$p+$e, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="text-align: right; margin-top: 30px;">
                <a href="calificaciones.php" style="margin-right: 20px; color: #718096; text-decoration: none; font-weight: 600;">← Volver</a>
                <button type="submit" class="btn-save">GUARDAR CAMBIOS</button>
            </div>
        </form>
    </div>
</body>
</html>