<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['id_maestro'])) {
    header('Location: login.php');
    exit();
}

$id_materia = $_GET['materia'];
$id_grupo = $_GET['grupo'];
$fecha = date('Y-m-d'); // o permitir seleccionar fecha

// Obtener alumnos del grupo
$sql = "SELECT a.id_alumno, a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno
        FROM alumnos a
        WHERE a.id_grupo = :id_grupo
        ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id_grupo' => $id_grupo]);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario de asistencia
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['asistencia'] as $id_alumno => $estado) {
        // Verificar si ya existe registro
        $check_sql = "SELECT id_asistencia_clase FROM asistencias_clase 
                      WHERE id_alumno = :id_alumno 
                      AND id_materia = :id_materia 
                      AND id_grupo = :id_grupo 
                      AND fecha = :fecha";
        
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([
            'id_alumno' => $id_alumno,
            'id_materia' => $id_materia,
            'id_grupo' => $id_grupo,
            'fecha' => $fecha
        ]);
        
        if ($check_stmt->rowCount() > 0) {
            // Actualizar
            $update_sql = "UPDATE asistencias_clase 
                          SET estado = :estado, tipo_registro = 'Maestro'
                          WHERE id_alumno = :id_alumno 
                          AND id_materia = :id_materia 
                          AND id_grupo = :id_grupo 
                          AND fecha = :fecha";
            
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                'estado' => $estado,
                'id_alumno' => $id_alumno,
                'id_materia' => $id_materia,
                'id_grupo' => $id_grupo,
                'fecha' => $fecha
            ]);
        } else {
            // Insertar nuevo
            $insert_sql = "INSERT INTO asistencias_clase 
                          (id_alumno, id_materia, id_grupo, fecha, estado, tipo_registro)
                          VALUES (:id_alumno, :id_materia, :id_grupo, :fecha, :estado, 'Maestro')";
            
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([
                'id_alumno' => $id_alumno,
                'id_materia' => $id_materia,
                'id_grupo' => $id_grupo,
                'fecha' => $fecha,
                'estado' => $estado
            ]);
        }
    }
    
    echo "<div class='success'>Asistencia guardada correctamente</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tomar Asistencia</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { background-color: #d4edda; color: #155724; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Tomar Asistencia - <?= date('d/m/Y') ?></h2>
    <form method="POST">
        <table>
            <tr>
                <th>Matrícula</th>
                <th>Nombre</th>
                <th>Estado</th>
            </tr>
            <?php foreach ($alumnos as $alumno): ?>
            <tr>
                <td><?= htmlspecialchars($alumno['matricula']) ?></td>
                <td><?= htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'] . ' ' . $alumno['nombre']) ?></td>
                <td>
                    <select name="asistencia[<?= $alumno['id_alumno'] ?>]">
                        <option value="Presente">Presente</option>
                        <option value="Falta">Falta</option>
                        <option value="Retardo">Retardo</option>
                        <option value="Justificada">Justificada</option>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button type="submit">Guardar Asistencia</button>
    </form>
</body>
</html>