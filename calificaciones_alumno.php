<?php
// calificaciones_alumno.php

// Configuración de conexión a la base de datos
$host = 'localhost';
$dbname = 'cecyte_sc';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener la matrícula del parámetro GET
$matricula = isset($_GET['matricula']) ? $_GET['matricula'] : '';

if (empty($matricula)) {
    die("Error: No se especificó la matrícula del alumno.");
}

// Paleta de colores en verde
$colores = [
    'verde_muy_oscuro' => '#1a5330',
    'verde_oscuro' => '#2e7d32',
    'verde_medio' => '#4caf50',
    'verde_claro' => '#8bc34a',
    'verde_muy_claro' => '#c8e6c9'
];

// Consulta para obtener información del alumno
$sql_alumno = "SELECT 
                a.matricula,
                CONCAT(a.nombre, ' ', a.apellido_paterno, ' ', a.apellido_materno) as nombre_completo,
                a.id_semestre,
                c.nombre as carrera,
                g.nombre as grupo,
                s.semestre
            FROM alumnos a
            LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
            LEFT JOIN grupos g ON a.id_grupo = g.id_grupo
            LEFT JOIN semestres s ON a.id_semestre = s.id_semestre
            WHERE a.matricula = :matricula";

$stmt_alumno = $pdo->prepare($sql_alumno);
$stmt_alumno->execute(['matricula' => $matricula]);
$alumno = $stmt_alumno->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    die("Error: No se encontró el alumno con matrícula $matricula.");
}

// Consulta para obtener calificaciones del alumno
$sql_calificaciones = "SELECT 
                        cp.id_parcial,
                        p.nombre as parcial_nombre,
                        p.fecha_inicio,
                        p.fecha_fin,
                        m.materia,
                        cp.libreta_guia_puntos,
                        cp.asistencia_puntos,
                        cp.participacion_puntos,
                        cp.examen_puntos,
                        cp.total_formativa,
                        cp.total_sumativa,
                        cp.total,
                        cp.fecha_registro
                    FROM calificaciones_parcial cp
                    LEFT JOIN parciales p ON cp.id_parcial = p.id_parcial
                    LEFT JOIN materias m ON cp.id_materia = m.id_materia
                    WHERE cp.matricula = :matricula
                    ORDER BY cp.id_parcial, m.materia";

$stmt_calificaciones = $pdo->prepare($sql_calificaciones);
$stmt_calificaciones->execute(['matricula' => $matricula]);
$calificaciones = $stmt_calificaciones->fetchAll(PDO::FETCH_ASSOC);

// Agrupar calificaciones por parcial
$calificaciones_por_parcial = [];
$total_general = 0;
$contador_parciales = 0;

foreach ($calificaciones as $cal) {
    $parcial_id = $cal['id_parcial'];
    if (!isset($calificaciones_por_parcial[$parcial_id])) {
        $calificaciones_por_parcial[$parcial_id] = [
            'nombre' => $cal['parcial_nombre'],
            'fecha_inicio' => $cal['fecha_inicio'],
            'fecha_fin' => $cal['fecha_fin'],
            'materias' => [],
            'promedio_parcial' => 0
        ];
    }
    
    $calificaciones_por_parcial[$parcial_id]['materias'][] = $cal;
    $calificaciones_por_parcial[$parcial_id]['promedio_parcial'] += $cal['total'];
    
    // Para el promedio general
    $total_general += $cal['total'];
    $contador_parciales++;
}

// Calcular promedios por parcial
foreach ($calificaciones_por_parcial as $parcial_id => $parcial) {
    $num_materias = count($parcial['materias']);
    if ($num_materias > 0) {
        $calificaciones_por_parcial[$parcial_id]['promedio_parcial'] = 
            round($parcial['promedio_parcial'] / $num_materias, 2);
    }
}

// Calcular promedio general
$promedio_general = $contador_parciales > 0 ? round($total_general / $contador_parciales, 2) : 0;

// Determinar estatus del alumno basado en el promedio
function obtenerEstatus($promedio) {
    if ($promedio >= 90) return ['texto' => 'Excelente', 'clase' => 'excelente'];
    if ($promedio >= 80) return ['texto' => 'Bueno', 'clase' => 'bueno'];
    if ($promedio >= 70) return ['texto' => 'Regular', 'clase' => 'regular'];
    if ($promedio >= 60) return ['texto' => 'Suficiente', 'clase' => 'suficiente'];
    return ['texto' => 'Insuficiente', 'clase' => 'insuficiente'];
}

$estatus_alumno = obtenerEstatus($promedio_general);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Calificaciones - <?php echo htmlspecialchars($alumno['matricula']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, <?php echo $colores['verde_oscuro']; ?>, <?php echo $colores['verde_medio']; ?>);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header h2 {
            font-size: 20px;
            font-weight: 400;
            opacity: 0.9;
        }
        
        .matricula-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: <?php echo $colores['verde_muy_oscuro']; ?>;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .info-alumno {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 25px;
            background-color: <?php echo $colores['verde_muy_claro']; ?>;
            border-bottom: 3px solid <?php echo $colores['verde_claro']; ?>;
        }
        
        .info-item {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 5px solid <?php echo $colores['verde_medio']; ?>;
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.05);
        }
        
        .info-item h3 {
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        
        .info-item p {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .resumen-final {
            background-color: white;
            padding: 25px;
            margin: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 2px solid <?php echo $colores['verde_claro']; ?>;
        }
        
        .promedio-general {
            font-size: 48px;
            font-weight: 800;
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
            margin: 15px 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .estatus-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .excelente { background-color: #4CAF50; color: white; }
        .bueno { background-color: #8BC34A; color: white; }
        .regular { background-color: #FFC107; color: #333; }
        .suficiente { background-color: #FF9800; color: white; }
        .insuficiente { background-color: #F44336; color: white; }
        
        .seccion-parciales {
            padding: 0 25px 25px;
        }
        
        .titulo-seccion {
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid <?php echo $colores['verde_claro']; ?>;
        }
        
        .parcial-container {
            margin-bottom: 40px;
            border: 1px solid <?php echo $colores['verde_claro']; ?>;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.05);
        }
        
        .parcial-header {
            background-color: <?php echo $colores['verde_medio']; ?>;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .parcial-nombre {
            font-size: 20px;
            font-weight: 600;
        }
        
        .parcial-fechas {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .tabla-calificaciones {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tabla-calificaciones th {
            background-color: <?php echo $colores['verde_muy_claro']; ?>;
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            border-bottom: 2px solid <?php echo $colores['verde_claro']; ?>;
        }
        
        .tabla-calificaciones td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        
        .tabla-calificaciones tr:hover {
            background-color: #f9f9f9;
        }
        
        .formativa {
            background-color: rgba(200, 230, 201, 0.3);
            font-weight: 600;
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
        }
        
        .sumativa {
            background-color: rgba(76, 175, 80, 0.1);
            font-weight: 600;
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
        }
        
        .total-parcial {
            background-color: <?php echo $colores['verde_muy_claro']; ?>;
            font-weight: 700;
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
        }
        
        .promedio-parcial {
            padding: 15px;
            background-color: <?php echo $colores['verde_muy_claro']; ?>;
            text-align: right;
            font-weight: 600;
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
            border-top: 2px solid <?php echo $colores['verde_claro']; ?>;
        }
        
        .footer {
            background-color: <?php echo $colores['verde_muy_oscuro']; ?>;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            margin-top: 30px;
        }
        
        .botones {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        
        .btn-imprimir {
            background-color: <?php echo $colores['verde_medio']; ?>;
            color: white;
        }
        
        .btn-imprimir:hover {
            background-color: <?php echo $colores['verde_oscuro']; ?>;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .btn-volver {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-volver:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        @media print {
            .botones, .btn {
                display: none;
            }
            
            body {
                background-color: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                border-radius: 0;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            .matricula-badge {
                position: relative;
                top: 0;
                right: 0;
                display: inline-block;
                margin-top: 10px;
            }
            
            .parcial-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .tabla-calificaciones {
                font-size: 14px;
            }
            
            .tabla-calificaciones th,
            .tabla-calificaciones td {
                padding: 8px 5px;
            }
            
            .promedio-general {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reporte de Calificaciones Acad&eacute;micas</h1>
            <h2>Sistema de Control Escolar - CECyTE</h2>
            <div class="matricula-badge">Matr&iacute;cula: <?php echo htmlspecialchars($alumno['matricula']); ?></div>
        </div>
        
        <div class="info-alumno">
            <div class="info-item">
                <h3>Nombre Completo</h3>
                <p><?php echo htmlspecialchars($alumno['nombre_completo']); ?></p>
            </div>
            <div class="info-item">
                <h3>Carrera</h3>
                <p><?php echo htmlspecialchars($alumno['carrera']); ?></p>
            </div>
            <div class="info-item">
                <h3>Grupo</h3>
                <p><?php echo htmlspecialchars($alumno['grupo']); ?></p>
            </div>
            <div class="info-item">
                <h3>Semestre</h3>
                <p><?php echo htmlspecialchars($alumno['semestre']); ?></p>
            </div>
        </div>
        
        <div class="resumen-final">
            <h3 style="color: <?php echo $colores['verde_muy_oscuro']; ?>; margin-bottom: 10px;">Promedio General del Periodo</h3>
            <div class="promedio-general"><?php echo number_format($promedio_general, 2); ?></div>
            <div class="estatus-badge <?php echo $estatus_alumno['clase']; ?>">
                <?php echo $estatus_alumno['texto']; ?>
            </div>
            <p style="margin-top: 15px; color: #666;">Promedio calculado sobre <?php echo count($calificaciones); ?> calificaciones registradas</p>
        </div>
        
        <?php if (count($calificaciones_por_parcial) > 0): ?>
        <div class="seccion-parciales">
            <h2 class="titulo-seccion">Calificaciones por Parcial</h2>
            
            <?php foreach ($calificaciones_por_parcial as $parcial_id => $parcial): ?>
            <div class="parcial-container">
                <div class="parcial-header">
                    <div class="parcial-nombre"><?php echo htmlspecialchars($parcial['nombre']); ?></div>
                    <div class="parcial-fechas">
                        <?php 
                        echo date('d/m/Y', strtotime($parcial['fecha_inicio'])) . ' - ' . 
                             date('d/m/Y', strtotime($parcial['fecha_fin'])); 
                        ?>
                    </div>
                </div>
                
                <table class="tabla-calificaciones">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Libreta/Gu&iacute;a</th>
                            <th>Asistencia</th>
                            <th>Participaci&oacute;n</th>
                            <th>Examen</th>
                            <th>Total Formativa</th>
                            <th>Total Sumativa</th>
                            <th>Total Parcial</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parcial['materias'] as $materia): ?>
                        <tr>
                            <td style="text-align: left; padding-left: 15px;">
                                <strong><?php echo htmlspecialchars($materia['materia']); ?></strong>
                            </td>
                            <td class="formativa"><?php echo number_format($materia['libreta_guia_puntos'], 2); ?></td>
                            <td class="formativa"><?php echo number_format($materia['asistencia_puntos'], 2); ?></td>
                            <td class="formativa"><?php echo number_format($materia['participacion_puntos'], 2); ?></td>
                            <td class="sumativa"><?php echo number_format($materia['examen_puntos'], 2); ?></td>
                            <td class="formativa"><?php echo number_format($materia['total_formativa'], 2); ?></td>
                            <td class="sumativa"><?php echo number_format($materia['total_sumativa'], 2); ?></td>
                            <td class="total-parcial">
                                <strong><?php echo number_format($materia['total'], 2); ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="promedio-parcial">
                    Promedio del parcial: <strong><?php echo $parcial['promedio_parcial']; ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="padding: 40px; text-align: center; color: #666;">
            <h3>No se encontraron calificaciones registradas para este alumno.</h3>
            <p>El alumno no tiene calificaciones registradas en el sistema.</p>
        </div>
        <?php endif; ?>
        
        <div class="botones">
            <button class="btn btn-imprimir" onclick="window.print()">Imprimir Reporte</button>
            <a href="javascript:history.back()" class="btn btn-volver">Volver Atr&aacute;s</a>
        </div>
        
        <div class="footer">
            <p>Reporte generado el <?php echo date('d/m/Y H:i:s'); ?> | Sistema de Control Escolar CECyTE</p>
            <p>Este documento es confidencial y de uso exclusivo para fines acad&eacute;micos.</p>
        </div>
    </div>
    
    <script>
        // Agregar funcionalidad adicional si es necesario
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Reporte de calificaciones cargado correctamente');
        });
    </script>
</body>
</html>