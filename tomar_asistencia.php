<?php
session_start();

//require_once __DIR__ . '/vendor/autoload.php';

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
$sql = "SELECT a.id_alumno, a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno, a.correo_asistenciaclases
        FROM alumnos a
        WHERE a.id_grupo = :id_grupo
        ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre";

$stmt = $con->prepare($sql);
$stmt->execute(['id_grupo' => $id_grupo]);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Después de obtener $alumnos
//$datos_alumno = [];
//foreach ($alumnos as $a) {
//    $nombre_completo = $a['apellido_paterno'] . ' ' . $a['apellido_materno'] . ' ' . $a['nombre'];
//    $datos_alumno[$a['id_alumno']] = [
//        'nombre_completo' => trim($nombre_completo),
//        'correo'          => $a['correo_asistenciaclases'] ?? ''
//    ];
//}

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
		
		
		// Enviar correo si el alumno tiene correo_asistenciaclases
if (!empty($datos_alumno[$id_alumno]['correo'])) {
    $nombre_completo = $datos_alumno[$id_alumno]['nombre_completo'];
    $correo_destino  = $datos_alumno[$id_alumno]['correo'];
    $materia_nombre  = $info_clase['materia'];
    $fecha_formateada = date('d/m/Y', strtotime($fecha));

    switch ($estado) {
        case 'Presente':
            $accion = 'asistido';
            break;
        case 'Falta':
            $accion = 'faltado';
            break;
        case 'Retardo':
            $accion = 'llegado tarde';
            break;
        case 'Justificada':
            $accion = 'justificado su falta';
            break;
        default:
            $accion = 'registrado';
    }

    $asunto = "Notificación de asistencia - CECYTE";
    $mensaje_html = "
    <html>
    <head><title>Notificación de Asistencia</title></head>
    <body>
        <p>Estimado padre de familia,</p>
        <p>Le informamos que su hijo <strong>$nombre_completo</strong> ha $accion a la clase de <strong>$materia_nombre</strong> el día <strong>$fecha_formateada</strong>.</p>
        <p>Atentamente,<br>Sistema de Control Escolar CECYTE</p>
    </body>
    </html>
    ";

    $cabeceras = "MIME-Version: 1.0\r\n";
    $cabeceras .= "Content-type: text/html; charset=UTF-8\r\n";
    $cabeceras .= "From: no-reply@cecyte.edu.mx\r\n";

    mail($correo_destino, $asunto, $mensaje_html, $cabeceras);
}
    }
    
    $mensaje_exito = "<div class='success'>Asistencia guardada correctamente para $registros_guardados alumnos.</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tomar Asistencia - CECYTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #1a5330 0%, #2e7d32 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .header-content {
            flex: 1;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 i {
            color: #c8e6c9;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            margin-left: 20px;
            white-space: nowrap;
        }
        
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #4caf50;
        }
        
        .clase-info {
            background: #c8e6c9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #8bc34a;
        }
        
        .clase-info p {
            margin: 10px 0;
            font-size: 16px;
            color: #1a5330;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .clase-info strong {
            color: #2e7d32;
            min-width: 100px;
            display: inline-block;
        }
        
        .success {
            background: #c8e6c9;
            color: #1a5330;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            border: 2px solid #4caf50;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .success i {
            color: #2e7d32;
            font-size: 24px;
        }
        
        .warning {
            background: #ffebee;
            color: #c62828;
            padding: 25px;
            border-radius: 10px;
            border: 2px solid #ef5350;
            text-align: center;
            margin: 20px 0;
        }
        
        .warning i {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
        }
        
        .table-container {
            overflow-x: auto;
            margin: 30px 0;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        thead {
            background: linear-gradient(to right, #1a5330, #2e7d32);
            color: white;
        }
        
        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 15px;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        th:last-child {
            border-right: none;
        }
        
        th i {
            margin-right: 10px;
            opacity: 0.9;
        }
        
        tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.3s;
        }
        
        tbody tr:hover {
            background-color: #c8e6c9;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tbody tr:nth-child(even):hover {
            background-color: #c8e6c9;
        }
        
        td {
            padding: 16px 15px;
            color: #333;
            font-size: 14.5px;
        }
        
        select {
            padding: 10px 15px;
            border: 2px solid #8bc34a;
            border-radius: 8px;
            font-size: 15px;
            background-color: white;
            color: #1a5330;
            font-weight: 500;
            width: 150px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        select:focus {
            outline: none;
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
        }
        
        select option {
            padding: 10px;
            font-size: 14px;
        }
        
        .btn-guardar {
            background: linear-gradient(to right, #2e7d32, #4caf50);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            margin-top: 30px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
        }
        
        .btn-guardar:hover {
            background: linear-gradient(to right, #1a5330, #2e7d32);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(26, 83, 48, 0.4);
        }
        
        .footer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #c8e6c9;
        }
        
        .btn-volver {
            background: #8bc34a;
            color: #1a5330;
            padding: 14px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 2px solid #4caf50;
        }
        
        .btn-volver:hover {
            background: #4caf50;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(139, 195, 74, 0.3);
        }
        
        .form-actions {
            text-align: right;
        }
        
        .contador-alumnos {
            background: #e3f2fd;
            color: #1565c0;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-block;
            border: 2px solid #90caf9;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .btn-back {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
                justify-content: center;
            }
            
            .header-content {
                width: 100%;
            }
            
            .footer-actions {
                flex-direction: column;
                gap: 20px;
                align-items: stretch;
            }
            
            .btn-volver, .btn-guardar {
                width: 100%;
                justify-content: center;
            }
            
            .table-container {
                margin: 20px -15px;
                border-radius: 0;
            }
            
            .clase-info p {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
        
        .estado-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            min-width: 120px;
            text-align: center;
            border: 2px solid transparent;
        }
        
        .estado-presente { background: #c8e6c9; color: #1a5330; border-color: #4caf50; }
        .estado-falta { background: #ffebee; color: #c62828; border-color: #ef5350; }
        .estado-retardo { background: #fff3e0; color: #ef6c00; border-color: #ff9800; }
        .estado-justificada { background: #e3f2fd; color: #1565c0; border-color: #2196f3; }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        
        .user-info span {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1><i class="fas fa-clipboard-check"></i> Tomar Asistencia</h1>
                <p>Fecha: <?= date('d/m/Y') ?></p>
                <div class="user-info">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['username'] ?? 'Usuario') ?></span>
                    <span><i class="fas fa-user-tag"></i> <?= htmlspecialchars($_SESSION['tipo_usuario']) ?></span>
                </div>
            </div>
            <a href="seleccionar_clase.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver a Clases
            </a>
        </div>
        
        <!-- Información de la clase -->
        <div class="card">
            <div class="clase-info">
                <p><strong><i class="fas fa-book"></i> Materia:</strong> <?= htmlspecialchars($info_clase['materia'] ?? 'N/A') ?></p>
                <p><strong><i class="fas fa-users"></i> Grupo:</strong> <?= htmlspecialchars($info_clase['grupo_nombre'] ?? 'N/A') ?></p>
                <p><strong><i class="fas fa-chalkboard-teacher"></i> Profesor:</strong> <?= htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['username'] ?? 'Usuario') ?></p>
            </div>
            
            <?php if (isset($mensaje_exito)) echo $mensaje_exito; ?>
            
            <?php if (empty($alumnos)): ?>
                <div class="warning">
                    <i class="fas fa-user-slash"></i>
                    <h3>No hay alumnos registrados</h3>
                    <p>No hay alumnos registrados en este grupo. Verifica la configuración del grupo.</p>
                    <a href="seleccionar_clase.php" class="btn-volver" style="margin-top: 20px;">
                        <i class="fas fa-arrow-left"></i> Volver a Clases
                    </a>
                </div>
            <?php else: ?>
                <div class="contador-alumnos">
                    <i class="fas fa-users"></i> Total de alumnos: <?= count($alumnos) ?>
                </div>
                
                <form method="POST">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag"></i> #</th>
                                    <th><i class="fas fa-id-card"></i> Matrícula</th>
                                    <th><i class="fas fa-user"></i> Nombre Completo</th>
                                    <th><i class="fas fa-clipboard-check"></i> Estado de Asistencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alumnos as $index => $alumno): 
                                    $nombre_completo = htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'] . ' ' . $alumno['nombre']);
                                ?>
                                <tr>
                                    <td><strong><?= $index + 1 ?></strong></td>
                                    <td><strong><?= htmlspecialchars($alumno['matricula']) ?></strong></td>
                                    <td><?= $nombre_completo ?></td>
                                    <td>
                                        <select name="asistencia[<?= $alumno['id_alumno'] ?>]" class="estado-select">
                                            <option value="Presente" class="estado-presente">Presente</option>
                                            <option value="Falta" class="estado-falta">Falta</option>
                                            <option value="Retardo" class="estado-retardo">Retardo</option>
                                            <option value="Justificada" class="estado-justificada">Justificada</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="footer-actions">
                        <a href="seleccionar_clase.php" class="btn-volver">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-guardar">
                                <i class="fas fa-save"></i> Guardar Asistencia
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Agregar funcionalidad para cambiar el color del select según la opción seleccionada
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('.estado-select');
            
            selects.forEach(select => {
                // Aplicar clase inicial
                applyClassForValue(select);
                
                // Escuchar cambios
                select.addEventListener('change', function() {
                    applyClassForValue(this);
                });
            });
            
            function applyClassForValue(selectElement) {
                // Remover todas las clases de estado
                selectElement.classList.remove('estado-presente', 'estado-falta', 'estado-retardo', 'estado-justificada');
                
                // Añadir la clase correspondiente
                switch(selectElement.value) {
                    case 'Presente':
                        selectElement.classList.add('estado-presente');
                        break;
                    case 'Falta':
                        selectElement.classList.add('estado-falta');
                        break;
                    case 'Retardo':
                        selectElement.classList.add('estado-retardo');
                        break;
                    case 'Justificada':
                        selectElement.classList.add('estado-justificada');
                        break;
                }
            }
            
            // Aplicar estilo inicial a todos los selects
            selects.forEach(select => {
                applyClassForValue(select);
            });
        });
    </script>
</body>
</html>