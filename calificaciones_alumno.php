<?php
session_start();
require_once 'conexion.php';

// Capturamos los datos de la URL
$id_materia = $_GET['materia'] ?? 0;
$id_grupo = $_GET['grupo'] ?? 0;
$matricula = $_GET['matricula'] ?? null;

// 1. CORRECCIÓN DEL NOMBRE DE MATERIA: 
// He usado "nombre" asumiendo que es el estándar, si falla cámbialo por la columna correcta de la tabla materias
$query_materia = $con->prepare("SELECT * FROM materias WHERE id_materia = ?");
$query_materia->execute([$id_materia]);
$materia_info = $query_materia->fetch(PDO::FETCH_ASSOC);

// 2. CONSULTA A LA TABLA CORRECTA: calificaciones_parciales
if ($matricula) {
    $sql = "SELECT a.nombre, a.apellido_paterno, cp.* FROM alumnos a 
            INNER JOIN calificaciones_parcial cp ON a.id_alumno = cp.id_alumno 
            WHERE a.matricula = ? AND cp.id_materia = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$matricula, $id_materia]);
} else {
    // Si eres el profesor y quieres ver a todo el grupo
    $sql = "SELECT a.nombre, a.apellido_paterno, cp.* FROM alumnos a 
            INNER JOIN calificaciones_parcial cp ON a.id_alumno = cp.id_alumno 
            WHERE a.id_grupo = ? AND cp.id_materia = ?
            ORDER BY a.apellido_paterno ASC, cp.id_parcial ASC";
    $stmt = $con->prepare($sql);
    $stmt->execute([$id_grupo, $id_materia]);
}

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGA | Vista de Calificaciones</title>
    <style>
        :root { --cecyte-green: #1b4d3e; --cecyte-gold: #c5a059; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h2 { color: var(--cecyte-green); border-bottom: 2px solid var(--cecyte-gold); padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: var(--cecyte-green); color: white; padding: 12px; }
        td { padding: 10px; border-bottom: 1px solid #eee; text-align: center; }
        .badge { padding: 5px 10px; border-radius: 15px; color: white; font-weight: bold; }
        .btn-volver { display: inline-block; margin-top: 20px; padding: 10px 20px; background: var(--cecyte-green); color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Resultados: <?= htmlspecialchars($materia_info['nombre_materia'] ?? $materia_info['nombre'] ?? 'Materia') ?></h2>
    
    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Alumno</th>
                <th>Parcial</th>
                <th>Libreta</th>
                <th>Asist.</th>
                <th>Part.</th>
                <th>Examen</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($resultados) > 0): ?>
                <?php foreach ($resultados as $row): 
                    // Cálculo basado en tu escala 1-10 (ajustar si es necesario)
                    $total = ($row['libreta_guia_puntos'] * 0.5) + ($row['asistencia_puntos'] * 0.05) + ($row['participacion_puntos'] * 0.05) + ($row['examen_puntos'] * 0.4);
                ?>
                <tr>
                    <td style="text-align: left;"><?= htmlspecialchars($row['apellido_paterno'] . " " . $row['nombre']) ?></td>
                    <td>Parcial <?= $row['id_parcial'] ?></td>
                    <td><?= number_format($row['libreta_guia_puntos'], 0) ?></td>
                    <td><?= number_format($row['asistencia_puntos'], 0) ?></td>
                    <td><?= number_format($row['participacion_puntos'], 0) ?></td>
                    <td><?= number_format($row['examen_puntos'], 0) ?></td>
                    <td>
                        <span class="badge" style="background: <?= ($total < 7) ? '#e74c3c' : '#27ae60' ?>;">
                            <?= number_format($total, 1) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No se encontraron capturas para este grupo y materia.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="calificaciones.php?materia=<?= $id_materia ?>&grupo=<?= $id_grupo ?>" class="btn-volver">← Volver a Captura</a>
</div>

</body>
</html>