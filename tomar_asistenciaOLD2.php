<?php
session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['loggedin']) || ($_SESSION['tipo_usuario'] !== 'maestro' && $_SESSION['tipo_usuario'] !== 'sistema' && $_SESSION['tipo_usuario'] !== 'admin')) {
    header('Location: login.php');
    exit();
}

// Usar la misma conexión que en seleccionar_clase.php
require_once 'conexion.php';

// Obtener parámetros de la materia y grupo
if (!isset($_GET['materia']) || !isset($_GET['grupo'])) {
    die("Error: Parámetros de materia y grupo no proporcionados.");
}

$id_materia = $_GET['materia'];
$id_grupo = $_GET['grupo'];
$fecha = date('Y-m-d');

// Obtener el ID del maestro basado en el tipo de usuario
$id_maestro_actual = null;
if ($_SESSION['tipo_usuario'] === 'maestro') {
    $id_maestro_actual = $_SESSION['user_id'];
}

// Verificar que el maestro tenga permiso para esta materia/grupo
if ($_SESSION['tipo_usuario'] === 'maestro') {
    $sql_verificar = "SELECT COUNT(*) as count FROM horarios_maestros 
                      WHERE id_maestro = :id_maestro 
                      AND id_materia = :id_materia 
                      AND id_grupo = :id_grupo 
                      AND estatus = 'Activo'";
    $stmt_verificar = $con->prepare($sql_verificar);
    $stmt_verificar->execute([
        'id_maestro' => $id_maestro_actual,
        'id_materia' => $id_materia,
        'id_grupo' => $id_grupo
    ]);
    $resultado = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado['count'] == 0) {
        die("Error: No tienes permiso para tomar asistencia en esta clase.");
    }
}

// Obtener alumnos del grupo
$sql = "SELECT a.id_alumno, a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno
        FROM alumnos a
        WHERE a.id_grupo = :id_grupo
        ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre";

$stmt = $con->prepare($sql);
$stmt->execute(['id_grupo' => $id_grupo]);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener información de la materia y grupo para mostrar
$sql_info = "SELECT m.materia, g.nombre as grupo_nombre
             FROM materias m, grupos g
             WHERE m.id_materia = :id_materia AND g.id_grupo = :id_grupo";
$stmt_info = $con->prepare($sql_info);
$stmt_info->execute(['id_materia' => $id_materia, 'id_grupo' => $id_grupo]);
$info_clase = $stmt_info->fetch(PDO::FETCH_ASSOC);

// Procesar formulario de asistencia
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $registros_guardados = 0;
    
    foreach ($_POST['asistencia'] as $id_alumno => $estado) {
        // Verificar si ya existe registro
        $check_sql = "SELECT id_asistencia_clase FROM asistencias_clase 
                      WHERE id_alumno = :id_alumno 
                      AND id_materia = :id_materia 
                      AND id_grupo = :id_grupo 
                      AND fecha = :fecha";
        
        $check_stmt = $con->prepare($check_sql);
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
            
            $update_stmt = $con->prepare($update_sql);
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
            
            $insert_stmt = $con->prepare($insert_sql);
            $insert_stmt->execute([
                'id_alumno' => $id_alumno,
                'id_materia' => $id_materia,
                'id_grupo' => $id_grupo,
                'fecha' => $fecha,
                'estado' => $estado
            ]);
        }
        
        $registros_guardados++;
    }
    
    $mensaje_exito = "<div class='success'>Asistencia guardada correctamente para $registros_guardados alumnos.</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tomar Asistencia</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .success { 
            background-color: #d4edda; 
            color: #155724; 
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 5px;
            border: 1px solid #c3e6cb;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #667eea;
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .btn-guardar {
            background: #48bb78;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
        }
        .btn-guardar:hover {
            background: #38a169;
        }
        .btn-volver {
            background: #6b7280;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
        .clase-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .clase-info p {
            margin: 5px 0;
            font-size: 16px;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ffeaa7;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tomar Asistencia</h1>
            <p>Fecha: <?= date('d/m/Y') ?></p>
        </div>
        
        <div class="clase-info">
            <p><strong>Materia:</strong> <?= htmlspecialchars($info_clase['materia'] ?? 'N/A') ?></p>
            <p><strong>Grupo:</strong> <?= htmlspecialchars($info_clase['grupo_nombre'] ?? 'N/A') ?></p>
            <p><strong>Profesor:</strong> <?= htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['username'] ?? 'Usuario') ?></p>
        </div>
        
        <?php if (isset($mensaje_exito)) echo $mensaje_exito; ?>
        
        <?php if (empty($alumnos)): ?>
            <div class="warning">No hay alumnos registrados en este grupo.</div>
        <?php else: ?>
            <form method="POST">
                <table>
                    <tr>
                        <th>#</th>
                        <th>Matrícula</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                    </tr>
                    <?php foreach ($alumnos as $index => $alumno): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
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
                <button type="submit" class="btn-guardar">Guardar Asistencia</button>
            </form>
        <?php endif; ?>
        
        <a href="seleccionar_clase.php" class="btn-volver">? Volver a Clases</a>
    </div>
</body>
</html>