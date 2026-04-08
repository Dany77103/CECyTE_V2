<?php
session_start();
require_once 'conexion.php';

// --- BLOQUE DE GUARDADO Y REDIRECCIÓN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['calificaciones'])) {
    try {
        $id_materia = $_GET['materia'] ?? 0;
        $id_grupo = $_GET['grupo'] ?? 0;

        foreach ($_POST['calificaciones'] as $id_parcial => $alumnos_data) {
            foreach ($alumnos_data as $id_alumno => $puntos) {
                $lib = $puntos['libreta'] ?? 0;
                $asi = $puntos['asistencia'] ?? 0;
                $par = $puntos['participacion'] ?? 0;
                $exa = $puntos['examen'] ?? 0;

                // Guardar o actualizar
                $sql = "INSERT INTO calificaciones_parcial 
                        (id_alumno, id_materia, id_grupo, id_parcial, libreta_guia_puntos, asistencia_puntos, participacion_puntos, examen_puntos) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        libreta_guia_puntos = VALUES(libreta_guia_puntos),
                        asistencia_puntos = VALUES(asistencia_puntos),
                        participacion_puntos = VALUES(participacion_puntos),
                        examen_puntos = VALUES(examen_puntos)";
                
                $stmt = $con->prepare($sql);
                $stmt->execute([$id_alumno, $id_materia, $id_grupo, $id_parcial, $lib, $asi, $par, $exa]);
            }
        }

        header("Location: calificaciones_alumno.php?materia=$id_materia&grupo=$id_grupo");
        exit();

    } catch (Exception $e) {
        echo "Error al guardar: " . $e->getMessage();
    }
}

// Consultas originales
$id_materia = $_GET['materia'] ?? 0;
$id_grupo = $_GET['grupo'] ?? 0;
$parciales_db = $con->query("SELECT * FROM parciales WHERE activo = 1 ORDER BY numero_parcial")->fetchAll(PDO::FETCH_ASSOC);
$alumnos = $con->prepare("SELECT id_alumno, nombre, apellido_paterno FROM alumnos WHERE id_grupo = ? ORDER BY apellido_paterno");
$alumnos->execute([$id_grupo]);
$lista_alumnos = $alumnos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGA | Captura por Parcial</title>
    <style>
        :root { --cecyte-green: #1b4d3e; --cecyte-gold: #c5a059; --light-bg: #f8f9fa; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light-bg); margin: 0; padding: 20px; }
        .selector-container { max-width: 1100px; margin: 40px auto; text-align: center; position: relative; }
        
        /* ESTILO DEL NUEVO BOTÓN VOLVER */
        .btn-volver-seccion {
            position: absolute;
            top: -10px;
            left: 0;
            padding: 10px 20px;
            background: #fff;
            color: var(--cecyte-green);
            border: 2px solid var(--cecyte-green);
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-volver-seccion:hover {
            background: var(--cecyte-green);
            color: #fff;
        }

        .btn-parcial-selector {
            width: 250px; padding: 40px 20px; margin: 15px;
            font-size: 1.2rem; font-weight: bold; color: white;
            background: var(--cecyte-green); border: none; border-radius: 15px;
            cursor: pointer; transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: inline-block;
        }
        .btn-parcial-selector.active { background: var(--cecyte-gold); outline: 4px solid var(--cecyte-green); }
        .tabla-desplegable { display: none; background: white; padding: 30px; border-radius: 20px; margin-top: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f1f2f6; color: var(--cecyte-green); padding: 15px; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #eee; text-align: center; }
        .input-cal { width: 75px; padding: 8px; border: 1px solid #ccc; border-radius: 5px; text-align: center; font-weight: bold; }
        .total-badge { background: var(--cecyte-green); color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold; min-width: 50px; display: inline-block; }
        .btn-save-all { background: #27ae60; color: white; padding: 15px 50px; border: none; border-radius: 10px; font-size: 1.1rem; cursor: pointer; margin-top: 25px; }
    </style>
</head>
<body>

    <div class="selector-container">
        <a href="seleccionar_clase.php" class="btn-volver-seccion">← Cambiar Clase</a>

        <h2 style="color: var(--cecyte-green);">Captura de Calificaciones (Escala 1-10)</h2>
        
        <div class="botones-container">
            <?php foreach ($parciales_db as $p): ?>
                <button type="button" class="btn-parcial-selector" onclick="toggleParcial(<?= $p['id_parcial'] ?>, this)">
                    PARCIAL <?= $p['numero_parcial'] ?>
                </button>
            <?php endforeach; ?>
        </div>

        <form method="POST" id="formCalificaciones">
            <?php foreach ($parciales_db as $p): 
                $id_p = $p['id_parcial'];
                $sql_c = "SELECT * FROM calificaciones_parcial WHERE id_materia = ? AND id_grupo = ? AND id_parcial = ?";
                $stmt_c = $con->prepare($sql_c); $stmt_c->execute([$id_materia, $id_grupo, $id_p]);
                $existentes = $stmt_c->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
            ?>
                <div id="panel_<?= $id_p ?>" class="tabla-desplegable">
                    <h3>Captura de Datos: Parcial <?= $p['numero_parcial'] ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align: left;">Nombre del Alumno</th>
                                <th>Libreta (50%)</th>
                                <th>Asist. (5%)</th>
                                <th>Part. (5%)</th>
                                <th>Examen (40%)</th>
                                <th>Total Parcial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_alumnos as $al): 
                                $uid = $id_p . "_" . $al['id_alumno'];
                                $c = $existentes[$al['id_alumno']] ?? [];
                                $v_lib = $c['libreta_guia_puntos'] ?? 0;
                                $v_asi = $c['asistencia_puntos'] ?? 0;
                                $v_par = $c['participacion_puntos'] ?? 0;
                                $v_exa = $c['examen_puntos'] ?? 0;
                                $total_actual = ($v_lib * 0.50) + ($v_asi * 0.05) + ($v_par * 0.05) + ($v_exa * 0.40);
                            ?>
                                <tr>
                                    <td style="text-align: left;"><?= htmlspecialchars($al['apellido_paterno'] . " " . $al['nombre']) ?></td>
                                    <td>
                                        <select name="calificaciones[<?= $id_p ?>][<?= $al['id_alumno'] ?>][libreta]" class="input-cal" onchange="actualizarTotal('<?= $uid ?>')" id="l_<?= $uid ?>">
                                            <?php for($i=0; $i<=10; $i++): ?>
                                                <option value="<?= $i ?>" <?= $v_lib == $i ? 'selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="calificaciones[<?= $id_p ?>][<?= $al['id_alumno'] ?>][asistencia]" class="input-cal" onchange="actualizarTotal('<?= $uid ?>')" id="a_<?= $uid ?>">
                                            <?php for($i=0; $i<=10; $i++): ?>
                                                <option value="<?= $i ?>" <?= $v_asi == $i ? 'selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="calificaciones[<?= $id_p ?>][<?= $al['id_alumno'] ?>][participacion]" class="input-cal" onchange="actualizarTotal('<?= $uid ?>')" id="p_<?= $uid ?>">
                                            <?php for($i=0; $i<=10; $i++): ?>
                                                <option value="<?= $i ?>" <?= $v_par == $i ? 'selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="calificaciones[<?= $id_p ?>][<?= $al['id_alumno'] ?>][examen]" class="input-cal" onchange="actualizarTotal('<?= $uid ?>')" id="e_<?= $uid ?>">
                                            <?php for($i=0; $i<=10; $i++): ?>
                                                <option value="<?= $i ?>" <?= $v_exa == $i ? 'selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <span class="total-badge" id="t_<?= $uid ?>" 
                                              style="background: <?= ($total_actual < 7) ? '#e74c3c' : (($total_actual >= 9) ? '#27ae60' : '#2980b9') ?>;">
                                            <?= number_format($total_actual, 1) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn-save-all">GUARDAR Y VER RESULTADOS</button>
                </div>
            <?php endforeach; ?>
        </form>
    </div>

    <script>
        function toggleParcial(id, btn) {
            document.querySelectorAll('.tabla-desplegable').forEach(panel => panel.style.display = 'none');
            document.querySelectorAll('.btn-parcial-selector').forEach(b => b.classList.remove('active'));
            const panel = document.getElementById('panel_' + id);
            panel.style.display = 'block';
            btn.classList.add('active');
            panel.scrollIntoView({ behavior: 'smooth' });
        }

        function actualizarTotal(uid) {
            const l = parseFloat(document.getElementById('l_' + uid).value) || 0;
            const a = parseFloat(document.getElementById('a_' + uid).value) || 0;
            const p = parseFloat(document.getElementById('p_' + uid).value) || 0;
            const e = parseFloat(document.getElementById('e_' + uid).value) || 0;
            const total = (l * 0.50) + (a * 0.05) + (p * 0.05) + (e * 0.40);
            const badge = document.getElementById('t_' + uid);
            badge.textContent = total.toFixed(1);

            if (total >= 9) { badge.style.background = "#27ae60"; } 
            else if (total >= 6) { badge.style.background = "#2980b9"; } 
            else { badge.style.background = "#e74c3c"; }
        }
    </script>
</body>
</html>