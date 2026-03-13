<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['id_maestro'])) {
    header('Location: login.php');
    exit();
}

$id_materia = $_GET['materia'];
$id_grupo = $_GET['grupo'];

// Obtener parciales activos
$sql_parciales = "SELECT * FROM parciales WHERE activo = 1 ORDER BY numero_parcial";
$parciales = $pdo->query($sql_parciales)->fetchAll(PDO::FETCH_ASSOC);

// Obtener alumnos del grupo
$sql_alumnos = "SELECT a.id_alumno, a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno
                FROM alumnos a
                WHERE a.id_grupo = :id_grupo
                ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre";

$stmt_alumnos = $pdo->prepare($sql_alumnos);
$stmt_alumnos->execute(['id_grupo' => $id_grupo]);
$alumnos = $stmt_alumnos->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario de calificaciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_parcial = $_POST['id_parcial'];
    $usuario_registro = $_SESSION['username'] ?? 'maestro';
    
    foreach ($_POST['calificaciones'] as $id_alumno => $calificacion) {
        $sql = "INSERT INTO calificaciones_parcial 
                (id_alumno, id_materia, id_grupo, id_parcial, 
                 libreta_guia_puntos, asistencia_puntos, participacion_puntos, examen_puntos, usuario_registro)
                VALUES (:id_alumno, :id_materia, :id_grupo, :id_parcial,
                        :libreta_guia, :asistencia, :participacion, :examen, :usuario)
                ON DUPLICATE KEY UPDATE
                libreta_guia_puntos = :libreta_guia2,
                asistencia_puntos = :asistencia2,
                participacion_puntos = :participacion2,
                examen_puntos = :examen2,
                usuario_registro = :usuario2";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_alumno' => $id_alumno,
            'id_materia' => $id_materia,
            'id_grupo' => $id_grupo,
            'id_parcial' => $id_parcial,
            'libreta_guia' => $calificacion['libreta_guia'],
            'asistencia' => $calificacion['asistencia'],
            'participacion' => $calificacion['participacion'],
            'examen' => $calificacion['examen'],
            'usuario' => $usuario_registro,
            'libreta_guia2' => $calificacion['libreta_guia'],
            'asistencia2' => $calificacion['asistencia'],
            'participacion2' => $calificacion['participacion'],
            'examen2' => $calificacion['examen'],
            'usuario2' => $usuario_registro
        ]);
    }
    
    echo "<div class='success'>Calificaciones guardadas correctamente</div>";
}

// Obtener calificaciones existentes si se selecciona un parcial
$calificaciones_existentes = [];
if (isset($_GET['parcial'])) {
    $id_parcial = $_GET['parcial'];
    
    $sql_calificaciones = "SELECT * FROM calificaciones_parcial 
                          WHERE id_materia = :id_materia 
                          AND id_grupo = :id_grupo 
                          AND id_parcial = :id_parcial";
    
    $stmt_cal = $pdo->prepare($sql_calificaciones);
    $stmt_cal->execute([
        'id_materia' => $id_materia,
        'id_grupo' => $id_grupo,
        'id_parcial' => $id_parcial
    ]);
    
    while ($row = $stmt_cal->fetch(PDO::FETCH_ASSOC)) {
        $calificaciones_existentes[$row['id_alumno']] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Calificaciones</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .success { background-color: #d4edda; color: #155724; padding: 10px; margin: 10px 0; }
        .form-group { margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Registro de Calificaciones</h2>
    
    <div class="form-group">
        <label>Seleccionar Parcial:</label>
        <select id="select-parcial" onchange="cargarParcial()">
            <option value="">Seleccione un parcial</option>
            <?php foreach ($parciales as $parcial): ?>
            <option value="<?= $parcial['id_parcial'] ?>" 
                    <?= (isset($_GET['parcial']) && $_GET['parcial'] == $parcial['id_parcial']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($parcial['nombre']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <?php if (isset($_GET['parcial'])): ?>
    <form method="POST">
        <input type="hidden" name="id_parcial" value="<?= $_GET['parcial'] ?>">
        
        <table>
            <tr>
                <th>Matrícula</th>
                <th>Nombre</th>
                <th>Libreta/Guía (0-40)</th>
                <th>Asistencia (0-10)</th>
                <th>Participación (0-10)</th>
                <th>Examen (0-40)</th>
                <th>Total Formativa</th>
                <th>Total Sumativa</th>
                <th>Total</th>
            </tr>
            
            <?php foreach ($alumnos as $alumno): 
                $calificacion = $calificaciones_existentes[$alumno['id_alumno']] ?? null;
            ?>
            <tr>
                <td><?= htmlspecialchars($alumno['matricula']) ?></td>
                <td><?= htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'] . ' ' . $alumno['nombre']) ?></td>
                
                <td><input type="number" step="0.01" min="0" max="40" 
                           name="calificaciones[<?= $alumno['id_alumno'] ?>][libreta_guia]"
                           value="<?= $calificacion ? $calificacion['libreta_guia_puntos'] : '' ?>"></td>
                
                <td><input type="number" step="0.01" min="0" max="10" 
                           name="calificaciones[<?= $alumno['id_alumno'] ?>][asistencia]"
                           value="<?= $calificacion ? $calificacion['asistencia_puntos'] : '' ?>"></td>
                
                <td><input type="number" step="0.01" min="0" max="10" 
                           name="calificaciones[<?= $alumno['id_alumno'] ?>][participacion]"
                           value="<?= $calificacion ? $calificacion['participacion_puntos'] : '' ?>"></td>
                
                <td><input type="number" step="0.01" min="0" max="40" 
                           name="calificaciones[<?= $alumno['id_alumno'] ?>][examen]"
                           value="<?= $calificacion ? $calificacion['examen_puntos'] : '' ?>"></td>
                
                <td><?= $calificacion ? $calificacion['total_formativa'] : '0.00' ?></td>
                <td><?= $calificacion ? $calificacion['total_sumativa'] : '0.00' ?></td>
                <td><strong><?= $calificacion ? $calificacion['total'] : '0.00' ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <button type="submit">Guardar Calificaciones</button>
    </form>
    <?php endif; ?>
    
    <script>
    function cargarParcial() {
        var parcial = document.getElementById('select-parcial').value;
        if (parcial) {
            window.location.href = 'calificaciones.php?materia=<?= $id_materia ?>&grupo=<?= $id_grupo ?>&parcial=' + parcial;
        }
    }
    </script>
</body>
</html>