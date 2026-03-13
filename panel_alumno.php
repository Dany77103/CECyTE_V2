<?php
session_start();

// Verificar que sea alumno
if (!isset($_SESSION['loggedin']) || $_SESSION['rol'] !== 'alumno') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Obtener información completa del alumno
$alumno_id = $_SESSION['alumno_id'] ?? 0;
$matricula = $_SESSION['matricula'] ?? '';

$sql = "SELECT a.*, 
               c.nombre as carrera_nombre,
               g.nombre as grupo_nombre,
               s.semestre
        FROM alumnos a
        LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
        LEFT JOIN grupos g ON a.id_grupo = g.id_grupo
        LEFT JOIN semestres s ON a.id_semestre = s.id_semestre
        WHERE a.id_alumno = :alumno_id";

$stmt = $con->prepare($sql);
$stmt->execute(['alumno_id' => $alumno_id]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener calificaciones del alumno
$sql_calificaciones = "SELECT m.materia, cp.* 
                      FROM calificaciones_parcial cp
                      JOIN materias m ON cp.id_materia = m.id_materia
                      WHERE cp.id_alumno = :alumno_id
                      ORDER BY cp.id_parcial, m.materia";
$stmt_cal = $con->prepare($sql_calificaciones);
$stmt_cal->execute(['alumno_id' => $alumno_id]);
$calificaciones = $stmt_cal->fetchAll(PDO::FETCH_ASSOC);

// Obtener asistencias del alumno
$sql_asistencias = "SELECT ac.fecha, ac.estado, m.materia, g.nombre as grupo_nombre
                   FROM asistencias_clase ac
                   JOIN materias m ON ac.id_materia = m.id_materia
                   JOIN grupos g ON ac.id_grupo = g.id_grupo
                   WHERE ac.id_alumno = :alumno_id
                   ORDER BY ac.fecha DESC
                   LIMIT 20";
$stmt_asist = $con->prepare($sql_asistencias);
$stmt_asist->execute(['alumno_id' => $alumno_id]);
$asistencias = $stmt_asist->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Alumno - CECYTE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .info-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        
        .info-label {
            font-weight: 600;
            color: #4a5568;
        }
        
        .info-value {
            color: #333;
        }
        
        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .tabla th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            text-align: left;
            padding: 12px 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .tabla td {
            padding: 10px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .tabla tr:hover {
            background: #f7fafc;
        }
        
        .estado-presente {
            color: #28a745;
            font-weight: 600;
        }
        
        .estado-falta {
            color: #dc3545;
            font-weight: 600;
        }
        
        .estado-retardo {
            color: #ffc107;
            font-weight: 600;
        }
        
        .calificacion-alta {
            color: #28a745;
            font-weight: 600;
        }
        
        .calificacion-media {
            color: #ffc107;
            font-weight: 600;
        }
        
        .calificacion-baja {
            color: #dc3545;
            font-weight: 600;
        }
        
        .section-title {
            margin: 30px 0 15px 0;
            color: #333;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .tabla {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CECYTE - Panel del Alumno</h1>
        <div class="user-info">
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Alumno'); ?></span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Información Personal -->
        <div class="card">
            <h2>Información Personal</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Matrícula:</span>
                    <span class="info-value"><?php echo htmlspecialchars($alumno['matricula'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Nombre:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars(
                            ($alumno['nombre'] ?? '') . ' ' . 
                            ($alumno['apellido_paterno'] ?? '') . ' ' . 
                            ($alumno['apellido_materno'] ?? '')
                        ); ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Carrera:</span>
                    <span class="info-value"><?php echo htmlspecialchars($alumno['carrera_nombre'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Grupo:</span>
                    <span class="info-value"><?php echo htmlspecialchars($alumno['grupo_nombre'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Semestre:</span>
                    <span class="info-value"><?php echo htmlspecialchars($alumno['semestre'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Correo:</span>
                    <span class="info-value"><?php echo htmlspecialchars($alumno['correo_institucional'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value"><?php echo htmlspecialchars($alumno['telefono_celular'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estatus:</span>
                    <span class="info-value" style="color: <?php echo ($alumno['estatus'] ?? '') === 'Activo' ? '#28a745' : '#dc3545'; ?>;">
                        <?php echo htmlspecialchars($alumno['estatus'] ?? 'N/A'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Calificaciones -->
        <?php if (!empty($calificaciones)): ?>
        <div class="card">
            <h2>Calificaciones</h2>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Parcial</th>
                        <th>Libreta/Guía</th>
                        <th>Asistencia</th>
                        <th>Participación</th>
                        <th>Examen</th>
                        <th>Total Formativa</th>
                        <th>Total Sumativa</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calificaciones as $calificacion): 
                        $clase_total = ($calificacion['total'] ?? 0) >= 80 ? 'calificacion-alta' : 
                                      (($calificacion['total'] ?? 0) >= 60 ? 'calificacion-media' : 'calificacion-baja');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($calificacion['materia']); ?></td>
                        <td><?php echo $calificacion['id_parcial']; ?></td>
                        <td><?php echo number_format($calificacion['libreta_guia_puntos'] ?? 0, 2); ?></td>
                        <td><?php echo number_format($calificacion['asistencia_puntos'] ?? 0, 2); ?></td>
                        <td><?php echo number_format($calificacion['participacion_puntos'] ?? 0, 2); ?></td>
                        <td><?php echo number_format($calificacion['examen_puntos'] ?? 0, 2); ?></td>
                        <td><?php echo number_format($calificacion['total_formativa'] ?? 0, 2); ?></td>
                        <td><?php echo number_format($calificacion['total_sumativa'] ?? 0, 2); ?></td>
                        <td class="<?php echo $clase_total; ?>">
                            <?php echo number_format($calificacion['total'] ?? 0, 2); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card">
            <h2>Calificaciones</h2>
            <p style="color: #666; text-align: center; padding: 20px;">
                No hay calificaciones registradas todavía.
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Asistencias Recientes -->
        <?php if (!empty($asistencias)): ?>
        <div class="card">
            <h2>Últimas Asistencias</h2>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Materia</th>
                        <th>Grupo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($asistencias as $asistencia): 
                        $clase_estado = '';
                        if ($asistencia['estado'] === 'Presente') $clase_estado = 'estado-presente';
                        elseif ($asistencia['estado'] === 'Falta') $clase_estado = 'estado-falta';
                        elseif ($asistencia['estado'] === 'Retardo') $clase_estado = 'estado-retardo';
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($asistencia['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($asistencia['materia']); ?></td>
                        <td><?php echo htmlspecialchars($asistencia['grupo_nombre']); ?></td>
                        <td class="<?php echo $clase_estado; ?>">
                            <?php echo htmlspecialchars($asistencia['estado']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card">
            <h2>Asistencias</h2>
            <p style="color: #666; text-align: center; padding: 20px;">
                No hay registros de asistencia.
            </p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>