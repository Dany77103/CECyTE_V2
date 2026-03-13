<?php
// horario_maestro.php

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

// Obtener el número de empleado del parámetro GET
$numEmpleado = isset($_GET['numEmpleado']) ? $_GET['numEmpleado'] : '';

if (empty($numEmpleado)) {
    die("Error: No se especific&oacute; el n&uacute;mero de empleado del maestro.");
}

// Paleta de colores en verde
$colores = [
    'verde_muy_oscuro' => '#1a5330',
    'verde_oscuro' => '#2e7d32',
    'verde_medio' => '#4caf50',
    'verde_claro' => '#8bc34a',
    'verde_muy_claro' => '#c8e6c9'
];

// Consulta para obtener información del maestro
$sql_maestro = "SELECT 
                m.id_maestro,
                m.numEmpleado,
                CONCAT(m.nombre, ' ', m.apellido_paterno, ' ', m.apellido_materno) as nombre_completo,
                m.especialidad,
                m.correo_institucional,
                m.activo
            FROM maestros m
            WHERE m.numEmpleado = :numEmpleado";

$stmt_maestro = $pdo->prepare($sql_maestro);
$stmt_maestro->execute(['numEmpleado' => $numEmpleado]);
$maestro = $stmt_maestro->fetch(PDO::FETCH_ASSOC);

if (!$maestro) {
    die("Error: No se encontr&oacute; el maestro con n&uacute;mero de empleado $numEmpleado.");
}

// Obtener el periodo escolar actual o más reciente
$sql_periodo = "SELECT DISTINCT periodo 
                FROM horarios_maestros 
                WHERE id_maestro = :id_maestro 
                ORDER BY periodo DESC 
                LIMIT 1";
$stmt_periodo = $pdo->prepare($sql_periodo);
$stmt_periodo->execute(['id_maestro' => $maestro['id_maestro']]);
$periodo_actual = $stmt_periodo->fetchColumn();

// Consulta para obtener el horario del maestro
$sql_horario = "SELECT 
                    hm.id_horario,
                    hm.dia,
                    hm.hora_inicio,
                    hm.hora_fin,
                    m.materia,
                    m.id_materia,
                    g.nombre as grupo,
                    a.nombre as aula,
                    c.nombre as carrera,
                    hm.periodo,
                    hm.estatus
                FROM horarios_maestros hm
                LEFT JOIN materias m ON hm.id_materia = m.id_materia
                LEFT JOIN grupos g ON hm.id_grupo = g.id_grupo
                LEFT JOIN carreras c ON g.id_carrera = c.id_carrera
                LEFT JOIN aulas a ON hm.id_aula = a.id_aula
                WHERE hm.id_maestro = :id_maestro 
                AND hm.periodo = :periodo
                AND hm.estatus = 'Activo'
                ORDER BY 
                    FIELD(hm.dia, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'),
                    hm.hora_inicio";

$stmt_horario = $pdo->prepare($sql_horario);
$stmt_horario->execute([
    'id_maestro' => $maestro['id_maestro'],
    'periodo' => $periodo_actual
]);
$horarios = $stmt_horario->fetchAll(PDO::FETCH_ASSOC);

// Organizar horarios por día
$horarios_por_dia = [
    'Lunes' => [],
    'Martes' => [],
    'Miércoles' => [],
    'Jueves' => [],
    'Viernes' => [],
    'Sábado' => [],
    'Domingo' => []
];

foreach ($horarios as $horario) {
    $dia = $horario['dia'];
    if (isset($horarios_por_dia[$dia])) {
        $horarios_por_dia[$dia][] = $horario;
    }
}

// Calcular estadísticas CORREGIDAS (clases de 45 minutos)
$total_clases = count($horarios);
$dias_con_clases = array_filter($horarios_por_dia, function($clases) {
    return count($clases) > 0;
});
$total_dias = count($dias_con_clases);

// Calcular horas por semana (asumiendo clases de 45 minutos = 0.75 horas cada una)
$horas_por_semana = round($total_clases * 0.75, 1);

// Definir horas del día para la tabla (períodos de 45 minutos)
$horas_del_dia = [
    "11:45", "12:30",
    "13:15", "14:00",
    "14:45", "15:30",
    "16:15", "17:00"
];

// Función para determinar el color de la materia
function obtenerColorMateria($materia_id, $colores) {
    $colores_materias = [
        1 => '#4caf50', // Matemáticas
        2 => '#2e7d32', // Historia
        3 => '#1a5330', // Programación
        4 => '#388e3c', // Física
    ];
    
    $color_base = isset($colores_materias[$materia_id]) ? 
                  $colores_materias[$materia_id] : 
                  $colores['verde_medio'];
    
    return $color_base;
}

// Función para verificar si una hora está dentro de un horario
function estaEnHorario($hora_actual, $hora_inicio, $hora_fin) {
    $hora_actual_ts = strtotime($hora_actual);
    $hora_inicio_ts = strtotime($hora_inicio);
    $hora_fin_ts = strtotime($hora_fin);
    
    return $hora_actual_ts >= $hora_inicio_ts && $hora_actual_ts < $hora_fin_ts;
}

// Función para formatear hora
function formatearHora($hora) {
    return date('g:i A', strtotime($hora));
}

// Calcular la duración en minutos de una clase
function calcularDuracionMinutos($hora_inicio, $hora_fin) {
    $inicio = strtotime($hora_inicio);
    $fin = strtotime($hora_fin);
    return ($fin - $inicio) / 60;
}

// Obtener día actual en español
function obtenerDiaActual() {
    $dias = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miercoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sabado',
        'Sunday' => 'Domingo'
    ];
    
    $dia_ingles = date('l');
    return isset($dias[$dia_ingles]) ? $dias[$dia_ingles] : $dia_ingles;
}

$dia_actual = obtenerDiaActual();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario del Maestro - <?php echo htmlspecialchars($maestro['nombre_completo']); ?></title>
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
            max-width: 1400px;
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
        
        .empleado-badge {
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
        
        .info-maestro {
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
        
        .estadisticas {
            background-color: white;
            padding: 25px;
            margin: 25px;
            border-radius: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 2px solid <?php echo $colores['verde_claro']; ?>;
        }
        
        .estadistica-item {
            text-align: center;
            padding: 20px;
            background-color: <?php echo $colores['verde_muy_claro']; ?>;
            border-radius: 8px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .estadistica-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .estadistica-valor {
            font-size: 36px;
            font-weight: 800;
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
            margin-bottom: 10px;
        }
        
        .estadistica-label {
            font-size: 16px;
            color: #666;
            font-weight: 600;
        }
        
        .nota-horas {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        
        .horario-container {
            padding: 0 25px 25px;
            overflow-x: auto;
        }
        
        .titulo-seccion {
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid <?php echo $colores['verde_claro']; ?>;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .periodo-badge {
            background-color: <?php echo $colores['verde_medio']; ?>;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .tabla-horario {
            width: 100%;
            border-collapse: collapse;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .tabla-horario th {
            background-color: <?php echo $colores['verde_muy_oscuro']; ?>;
            color: white;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            border-right: 1px solid <?php echo $colores['verde_medio']; ?>;
        }
        
        .tabla-horario th:first-child {
            width: 100px;
        }
        
        .tabla-horario th:last-child {
            border-right: none;
        }
        
        .tabla-horario td {
            padding: 10px;
            text-align: center;
            border: 1px solid #eee;
            height: 70px;
            vertical-align: top;
            position: relative;
        }
        
        .hora-col {
            background-color: <?php echo $colores['verde_muy_claro']; ?>;
            font-weight: 600;
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
            border-right: 2px solid <?php echo $colores['verde_claro']; ?>;
        }
        
        .clase {
            color: white;
            padding: 8px;
            border-radius: 5px;
            margin: 2px 0;
            font-size: 12px;
            position: absolute;
            top: 2px;
            left: 2px;
            right: 2px;
            bottom: 2px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .clase:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            border-color: <?php echo $colores['verde_claro']; ?>;
            z-index: 10;
        }
        
        .clase-titulo {
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 3px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .clase-detalle {
            font-size: 11px;
            opacity: 0.9;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .clase-horario {
            font-size: 11px;
            font-weight: 600;
            margin-top: 3px;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 2px 5px;
            border-radius: 3px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .leyenda {
            padding: 20px;
            background-color: <?php echo $colores['verde_muy_claro']; ?>;
            border-radius: 10px;
            margin: 25px;
            border: 2px solid <?php echo $colores['verde_claro']; ?>;
        }
        
        .leyenda h3 {
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .leyenda-items {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .leyenda-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 2px solid rgba(0, 0, 0, 0.1);
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
            padding: 0 25px 25px;
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
        
        .sin-clases {
            background-color: #f8f9fa;
            color: #6c757d;
            font-style: italic;
            font-size: 14px;
        }
        
        .dia-actual {
            background-color: rgba(139, 195, 74, 0.15);
            border-left: 3px solid <?php echo $colores['verde_medio']; ?>;
            border-right: 3px solid <?php echo $colores['verde_medio']; ?>;
        }
        
        .hora-actual {
            background-color: rgba(76, 175, 80, 0.2);
            font-weight: 700;
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
            
            .clase {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .estadistica-item:hover {
                transform: none;
                box-shadow: none;
            }
        }
        
        @media (max-width: 1200px) {
            .container {
                margin: 10px;
            }
            
            .horario-container {
                padding: 0 15px 15px;
            }
            
            .tabla-horario {
                font-size: 13px;
            }
            
            .clase {
                font-size: 11px;
                padding: 5px;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            .empleado-badge {
                position: relative;
                top: 0;
                right: 0;
                display: inline-block;
                margin-top: 10px;
            }
            
            .info-maestro {
                grid-template-columns: 1fr;
                padding: 15px;
            }
            
            .estadisticas {
                grid-template-columns: 1fr;
                padding: 15px;
                margin: 15px;
            }
            
            .tabla-horario th,
            .tabla-horario td {
                padding: 8px 5px;
            }
            
            .hora-col {
                font-size: 12px;
            }
            
            .clase-titulo {
                font-size: 11px;
            }
            
            .clase-detalle, .clase-horario {
                font-size: 10px;
            }
            
            .leyenda {
                margin: 15px;
                padding: 15px;
            }
            
            .botones {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
                text-align: center;
            }
        }
        
        /* Estilos para modal de detalles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal-contenido {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        
        .modal-cerrar {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
        }
        
        .modal-titulo {
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .modal-info {
            display: grid;
            gap: 10px;
        }
        
        .modal-info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .modal-info-label {
            font-weight: 600;
            color: <?php echo $colores['verde_muy_oscuro']; ?>;
        }
        
        .modal-info-valor {
            color: #333;
            text-align: right;
            max-width: 300px;
            word-break: break-word;
        }
        
        /* Indicador de día actual */
        .indicador-dia-actual {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: <?php echo $colores['verde_medio']; ?>;
            border-radius: 50%;
            margin-left: 5px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Horario del Maestro</h1>
            <h2>Sistema de Control Escolar - CECyTE</h2>
            <div class="empleado-badge">Empleado: <?php echo htmlspecialchars($maestro['numEmpleado']); ?></div>
        </div>
        
        <div class="info-maestro">
            <div class="info-item">
                <h3>Nombre Completo</h3>
                <p><?php echo htmlspecialchars($maestro['nombre_completo']); ?></p>
            </div>
            <div class="info-item">
                <h3>Especialidad</h3>
                <p><?php echo htmlspecialchars($maestro['especialidad'] ?: 'No especificada'); ?></p>
            </div>
            <div class="info-item">
                <h3>Correo Institucional</h3>
                <p><?php echo htmlspecialchars($maestro['correo_institucional']); ?></p>
            </div>
            <div class="info-item">
                <h3>Estatus</h3>
                <p style="color: <?php echo $maestro['activo'] == 'Activo' ? $colores['verde_medio'] : '#dc3545'; ?>">
                    <?php echo htmlspecialchars($maestro['activo']); ?>
                </p>
            </div>
        </div>
        
        <div class="estadisticas">
            <div class="estadistica-item">
                <div class="estadistica-valor"><?php echo $total_clases; ?></div>
                <div class="estadistica-label">Clases por semana</div>
                <div class="nota-horas">(Cada clase: 45 min)</div>
            </div>
            <div class="estadistica-item">
                <div class="estadistica-valor"><?php echo $total_dias; ?></div>
                <div class="estadistica-label">D&iacute;as con clases</div>
            </div>
            <div class="estadistica-item">
                <div class="estadistica-valor"><?php echo $periodo_actual; ?></div>
                <div class="estadistica-label">Periodo Escolar</div>
            </div>
            <div class="estadistica-item">
                <div class="estadistica-valor">
                    <?php echo $horas_por_semana; ?>
                </div>
                <div class="estadistica-label">Horas por semana</div>
                <div class="nota-horas">(Total: <?php echo $total_clases; ?> clases × 0.75h)</div>
            </div>
        </div>
        
        <div class="horario-container">
            <h2 class="titulo-seccion">
                Horario Semanal
                <span class="periodo-badge"><?php echo htmlspecialchars($periodo_actual); ?></span>
            </h2>
            
            <table class="tabla-horario">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Lunes <?php if ($dia_actual == 'Lunes'): ?><span class="indicador-dia-actual" title="Hoy"></span><?php endif; ?></th>
                        <th>Martes <?php if ($dia_actual == 'Martes'): ?><span class="indicador-dia-actual" title="Hoy"></span><?php endif; ?></th>
                        <th>Mi&eacute;rcoles <?php if ($dia_actual == 'Miercoles'): ?><span class="indicador-dia-actual" title="Hoy"></span><?php endif; ?></th>
                        <th>Jueves <?php if ($dia_actual == 'Jueves'): ?><span class="indicador-dia-actual" title="Hoy"></span><?php endif; ?></th>
                        <th>Viernes <?php if ($dia_actual == 'Viernes'): ?><span class="indicador-dia-actual" title="Hoy"></span><?php endif; ?></th>
                        <th>S&aacute;bado <?php if ($dia_actual == 'Sabado'): ?><span class="indicador-dia-actual" title="Hoy"></span><?php endif; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $hora_actual = date('H:i');
                    foreach ($horas_del_dia as $hora): 
                        $es_hora_actual = (strtotime($hora) <= strtotime($hora_actual) && strtotime($hora_actual) < strtotime($hora) + 45*60);
                    ?>
                        <tr>
                            <td class="hora-col <?php echo $es_hora_actual ? 'hora-actual' : ''; ?>">
                                <?php echo date('g:i A', strtotime($hora)); ?>
                                <?php if ($es_hora_actual): ?>
                                    <br><span style="font-size: 10px; color: <?php echo $colores['verde_medio']; ?>;">? Ahora</span>
                                <?php endif; ?>
                            </td>
                            
                            <?php foreach (['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'] as $dia): ?>
                                <td class="<?php echo $dia_actual == $dia ? 'dia-actual' : ''; ?>">
                                    <?php 
                                    $clase_en_hora = null;
                                    foreach ($horarios_por_dia[$dia] as $horario) {
                                        if (estaEnHorario($hora, $horario['hora_inicio'], $horario['hora_fin'])) {
                                            $clase_en_hora = $horario;
                                            break;
                                        }
                                    }
                                    
                                    if ($clase_en_hora): 
                                        // Calcular duración de la clase en minutos
                                        $duracion_minutos = calcularDuracionMinutos(
                                            $clase_en_hora['hora_inicio'], 
                                            $clase_en_hora['hora_fin']
                                        );
                                        
                                        // Calcular altura proporcional (70px por 45 minutos)
                                        $altura = ($duracion_minutos / 45) * 70 - 4;
                                        
                                        // Obtener color de la materia
                                        $color_clase = obtenerColorMateria(
                                            $clase_en_hora['id_materia'] ?? 1, 
                                            $colores
                                        );
                                    ?>
                                        <div class="clase" 
                                             style="height: <?php echo $altura; ?>px; background-color: <?php echo $color_clase; ?>;"
                                             onclick="mostrarDetallesClase(<?php echo htmlspecialchars(json_encode($clase_en_hora)); ?>)">
                                            <div class="clase-titulo"><?php echo htmlspecialchars($clase_en_hora['materia']); ?></div>
                                            <div class="clase-detalle"><?php echo htmlspecialchars($clase_en_hora['grupo']); ?></div>
                                            <div class="clase-detalle"><?php echo htmlspecialchars($clase_en_hora['aula']); ?></div>
                                            <div class="clase-horario">
                                                <?php echo formatearHora($clase_en_hora['hora_inicio']); ?> - 
                                                <?php echo formatearHora($clase_en_hora['hora_fin']); ?>
                                                <br>
                                                <span style="font-size: 10px;">(<?php echo $duracion_minutos; ?> min)</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="leyenda">
            <h3>Leyenda de Colores</h3>
            <div class="leyenda-items">
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background-color: #4caf50;"></div>
                    <span>Matem&aacute;ticas</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background-color: #2e7d32;"></div>
                    <span>Historia</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background-color: #1a5330;"></div>
                    <span>Programaci&oacute;n</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background-color: #388e3c;"></div>
                    <span>F&iacute;sica</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background-color: #8bc34a;"></div>
                    <span>Otras Materias</span>
                </div>
            </div>
            <div style="margin-top: 15px; font-size: 14px; color: <?php echo $colores['verde_muy_oscuro']; ?>;">
                <strong>Nota:</strong> Cada clase tiene una duraci&oacute;n de 45 minutos (0.75 horas).
            </div>
        </div>
        
        <div class="botones">
            <button class="btn btn-imprimir" onclick="window.print()">Imprimir Horario</button>
            <button class="btn btn-imprimir" onclick="descargarHorario()">Descargar PDF</button>
            <a href="javascript:history.back()" class="btn btn-volver">Volver Atr&aacute;s</a>
        </div>
        
        <div class="footer">
            <p>Horario generado el <?php echo date('d/m/Y H:i:s'); ?> | Sistema de Control Escolar CECyTE</p>
            <p>Este horario es v&aacute;lido para el periodo <?php echo htmlspecialchars($periodo_actual); ?></p>
            <p><strong>Nota:</strong> Los c&aacute;lculos de horas se basan en clases de 45 minutos (0.75 horas cada una)</p>
        </div>
    </div>
    
    <!-- Modal para detalles de clase -->
    <div id="modalDetalles" class="modal">
        <div class="modal-contenido">
            <span class="modal-cerrar" onclick="cerrarModal()">&times;</span>
            <h3 class="modal-titulo" id="modalTitulo"></h3>
            <div class="modal-info" id="modalInfo"></div>
        </div>
    </div>
    
    <script>
        // Función para mostrar detalles de la clase
        function mostrarDetallesClase(clase) {
            const modal = document.getElementById('modalDetalles');
            const titulo = document.getElementById('modalTitulo');
            const info = document.getElementById('modalInfo');
            
            // Calcular duración en minutos
            const inicio = new Date('1970-01-01T' + clase.hora_inicio + 'Z');
            const fin = new Date('1970-01-01T' + clase.hora_fin + 'Z');
            const duracionMs = fin - inicio;
            const duracionMinutos = Math.round(duracionMs / 60000);
            const duracionHoras = (duracionMinutos / 60).toFixed(2);
            
            titulo.textContent = clase.materia;
            
            info.innerHTML = `
                <div class="modal-info-item">
                    <span class="modal-info-label">Grupo:</span>
                    <span class="modal-info-valor">${clase.grupo}</span>
                </div>
                <div class="modal-info-item">
                    <span class="modal-info-label">Aula:</span>
                    <span class="modal-info-valor">${clase.aula}</span>
                </div>
                <div class="modal-info-item">
                    <span class="modal-info-label">Carrera:</span>
                    <span class="modal-info-valor">${clase.carrera || 'No especificada'}</span>
                </div>
                <div class="modal-info-item">
                    <span class="modal-info-label">D&iacute;a:</span>
                    <span class="modal-info-valor">${clase.dia}</span>
                </div>
                <div class="modal-info-item">
                    <span class="modal-info-label">Horario:</span>
                    <span class="modal-info-valor">${formatearHoraJS(clase.hora_inicio)} - ${formatearHoraJS(clase.hora_fin)}</span>
                </div>
                <div class="modal-info-item">
                    <span class="modal-info-label">Duraci&oacute;n:</span>
                    <span class="modal-info-valor">${duracionMinutos} minutos (${duracionHoras} horas)</span>
                </div>
                <div class="modal-info-item">
                    <span class="modal-info-label">Periodo:</span>
                    <span class="modal-info-valor">${clase.periodo}</span>
                </div>
                <div class="modal-info-item">
                    <span class="modal-info-label">Estatus:</span>
                    <span class="modal-info-valor" style="color: ${clase.estatus === 'Activo' ? '#4caf50' : '#dc3545'}">
                        ${clase.estatus}
                    </span>
                </div>
            `;
            
            modal.style.display = 'flex';
        }
        
        // Función para cerrar modal
        function cerrarModal() {
            document.getElementById('modalDetalles').style.display = 'none';
        }
        
        // Función para formatear hora en JavaScript
        function formatearHoraJS(horaStr) {
            const hora = new Date('1970-01-01T' + horaStr + 'Z');
            return hora.toLocaleTimeString('es-MX', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
        }
        
        // Función para descargar horario como PDF (simulada)
        function descargarHorario() {
            alert('Función de descarga PDF en desarrollo. Por ahora, use la opción de imprimir y seleccione "Guardar como PDF" en el diálogo de impresión.');
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalDetalles');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Resaltar el día actual y hora actual
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Horario del maestro cargado correctamente');
            
            // Agregar tooltip a los indicadores de día actual
            const indicadores = document.querySelectorAll('.indicador-dia-actual');
            indicadores.forEach(ind => {
                ind.title = 'Hoy';
            });
        });
    </script>
</body>
</html>