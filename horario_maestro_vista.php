<?php
session_start();
require_once 'conexion.php';  // Esto define $con (PDO)

// Si el usuario es maestro, usar su ID, si no, permitir selección
if (isset($_SESSION['id_maestro'])) {
    $id_maestro = $_SESSION['id_maestro'];
} elseif (isset($_GET['id'])) {
    $id_maestro = intval($_GET['id']);
} else {
    // Redirigir a selección
    header('Location: captura_horario_maestros.php');
    exit();
}

// Verificar que la conexión existe
if (!isset($con)) {
    die("Error: No se pudo conectar a la base de datos. Verifica el archivo conexion.php");
}

// Obtener información del maestro
$query_maestro = "SELECT * FROM maestros WHERE id_maestro = ?";
try {
    $stmt_maestro = $con->prepare($query_maestro);
    $stmt_maestro->execute([$id_maestro]);
    $maestro = $stmt_maestro->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener información del maestro: " . $e->getMessage());
}

// Verificar que el maestro exista
if (!$maestro) {
    die("Maestro no encontrado");
}

// Verificar si la tabla horarios_maestros existe
try {
    $table_check = $con->query("SHOW TABLES LIKE 'horarios_maestros'");
    $table_exists = $table_check->rowCount() > 0;
} catch (PDOException $e) {
    $table_exists = false;
}

if (!$table_exists) {
    // Si la tabla no existe, mostrar mensaje y datos estáticos
    $horario_por_dia = [];
    $materias_impartidas = [];
    $grupos_asignados = [];
    $total_clases = 0;
} else {
    // Obtener horario del maestro
    $query_horario = "
        SELECT hm.*, m.materia, m.color, g.nombre as grupo_nombre, a.nombre as aula_nombre
        FROM horarios_maestros hm
        LEFT JOIN materias m ON hm.id_materia = m.id_materia
        LEFT JOIN grupos g ON hm.id_grupo = g.id_grupo
        LEFT JOIN aulas a ON hm.id_aula = a.id_aula
        WHERE hm.id_maestro = ? AND hm.periodo = 'FEB 2026-JUL 2026' AND hm.estatus = 'Activo'
        ORDER BY 
            CASE hm.dia 
                WHEN 'Lunes' THEN 1
                WHEN 'Martes' THEN 2
                WHEN 'Miercoles' THEN 3
                WHEN 'Jueves' THEN 4
                WHEN 'Viernes' THEN 5
                ELSE 6
            END,
            hm.hora_inicio
    ";
    
    try {
        $stmt_horario = $con->prepare($query_horario);
        $stmt_horario->execute([$id_maestro]);
        $resultados = $stmt_horario->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al obtener el horario: " . $e->getMessage());
    }

    // Organizar horario por día
    $horario_por_dia = [];
    $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
    foreach ($dias as $dia) {
        $horario_por_dia[$dia] = [];
    }

    foreach ($resultados as $row) {
        $horario_por_dia[$row['dia']][] = $row;
    }

    // Calcular materias impartidas
    $materias_impartidas = [];
    $grupos_asignados = [];
    $total_clases = 0;
    
    foreach ($horario_por_dia as $dia => $clases) {
        $total_clases += count($clases);
        foreach ($clases as $clase) {
            if (!in_array($clase['materia'], $materias_impartidas)) {
                $materias_impartidas[] = $clase['materia'];
            }
            if ($clase['grupo_nombre'] && !in_array($clase['grupo_nombre'], $grupos_asignados)) {
                $grupos_asignados[] = $clase['grupo_nombre'];
            }
        }
    }
}

// Bloques horarios para la tabla
$bloques = [
    "11:45 - 12:30",
    "12:30 - 13:15",
    "13:15 - 14:00",
    "14:00 - 14:45",
    "14:45 - 15:30",
    "15:30 - 16:15",
	"16:15 - 17:00"
	
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Horario - <?php echo htmlspecialchars($maestro['nombre']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12pt; }
            .table { border: 1px solid #000; }
            .card { border: 1px solid #000; }
        }
        
        .header-horario {
            background: linear-gradient(135deg, #064e3b, #10b981);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .table-horario-vista {
            border-collapse: separate;
            border-spacing: 3px;
        }
        
        .table-horario-vista th {
            background-color: #f8f9fa;
            text-align: center;
            padding: 15px;
            font-weight: bold;
        }
        
        .celda-hora {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            width: 120px;
        }
        
        .celda-clase {
            padding: 10px;
            text-align: center;
            border-radius: 8px;
            color: #333;
            font-size: 0.9rem;
            min-height: 90px;
            vertical-align: top;
            transition: transform 0.3s;
        }
        
        .celda-clase:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .nombre-materia {
            font-weight: bold;
            font-size: 0.95rem;
            margin-bottom: 5px;
        }
        
        .detalle-clase {
            font-size: 0.8rem;
            color: #666;
        }
        
        .badge-grupo {
            background-color: rgba(0,0,0,0.1);
            color: #333;
            font-size: 0.75rem;
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
        }
        
        .receso-row {
            background-color: #f8f9fa !important;
            text-align: center;
            font-weight: bold;
            color: #6c757d;
            height: 40px;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Encabezado con datos del maestro -->
        <div class="header-horario">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class='bx bxs-calendar'></i> Mi Horario</h2>
                    <h4><?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido_paterno']); ?></h4>
                    <p class="mb-0">
                        <i class='bx bx-id-card'></i> No. Empleado: <?php echo htmlspecialchars($maestro['numEmpleado']); ?> | 
                        <i class='bx bx-envelope'></i> <?php echo htmlspecialchars($maestro['correo_institucional']); ?>
                    </p>
                </div>
                <div class="col-md-4 text-end no-print">
                    <button onclick="window.print()" class="btn btn-light">
                        <i class='bx bx-printer'></i> Imprimir Horario
                    </button>
                    <a href="captura_horario_maestros.php" class="btn btn-outline-light">
                        <i class='bx bx-home'></i> Inicio
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (!$table_exists): ?>
            <div class="alert alert-warning">
                <h5><i class='bx bx-error'></i> Tabla de horarios no encontrada</h5>
                <p>La tabla 'horarios_maestros' no existe en la base de datos. Ejecuta el siguiente script SQL:</p>
                <pre class="bg-dark text-light p-3 rounded">
CREATE TABLE IF NOT EXISTS `horarios_maestros` (
  `id_horario` INT(11) NOT NULL AUTO_INCREMENT,
  `id_maestro` INT(11) NOT NULL,
  `id_materia` INT(11) DEFAULT NULL,
  `dia` VARCHAR(15) NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  `id_aula` INT(11) DEFAULT NULL,
  `id_grupo` INT(11) DEFAULT NULL,
  `periodo` VARCHAR(20) DEFAULT 'FEB 2026-JUL 2026',
  `estatus` ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_horario`),
  KEY `id_maestro` (`id_maestro`),
  KEY `id_materia` (`id_materia`),
  KEY `id_aula` (`id_aula`),
  KEY `id_grupo` (`id_grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                </pre>
                <p>También necesitarás crear la tabla 'aulas' si no existe.</p>
            </div>
        <?php else: ?>
        
        <!-- Tabla de horario -->
        <div class="card shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-horario-vista mb-0">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <?php foreach ($dias as $dia): ?>
                                    <th><?php echo $dia; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloques as $idx => $bloque): ?>
                                <?php if ($idx == 3): ?>
                                    <!-- Receso -->
                                   <!-- <tr class="receso-row">
                                        <td colspan="6">
                                            <i class='bx bx-coffee'></i> RECESO (14:45 - 15:15)
                                        </td>
                                    </tr>  -->
                                <?php endif; ?>
                                
                                <tr>
                                    <td class="celda-hora"><?php echo $bloque; ?></td>
                                    
                                    <?php foreach ($dias as $dia): 
                                        $clase_encontrada = false;
                                        $clase_actual = null;
                                        
                                        // Buscar si hay clase en este día y hora
                                        if (isset($horario_por_dia[$dia])) {
                                            foreach ($horario_por_dia[$dia] as $clase) {
                                                list($hora_inicio, $hora_fin) = explode(' - ', $bloque);
                                                if ($clase['hora_inicio'] == $hora_inicio . ':00') {
                                                    $clase_encontrada = true;
                                                    $clase_actual = $clase;
                                                    break;
                                                }
                                            }
                                        }
                                    ?>
                                        <td class="celda-clase" 
                                            style="background-color: <?php echo $clase_encontrada ? ($clase_actual['color'] . '80') : '#ffffff'; ?>">
                                            
                                            <?php if ($clase_encontrada): ?>
                                                <div class="nombre-materia">
                                                    <?php echo htmlspecialchars($clase_actual['materia']); ?>
                                                </div>
                                                
                                                <div class="detalle-clase">
                                                    <div>
                                                        <i class='bx bx-group'></i> 
                                                        <span class="badge-grupo">
                                                            <?php echo htmlspecialchars($clase_actual['grupo_nombre'] ?: 'Sin grupo'); ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div>
                                                        <i class='bx bx-building'></i> 
                                                        <?php echo htmlspecialchars($clase_actual['aula_nombre'] ?: 'Sin aula'); ?>
                                                    </div>
                                                    
                                                    <div class="mt-1">
                                                        <small>
                                                            <?php echo substr($clase_actual['hora_inicio'], 0, 5) . ' - ' . substr($clase_actual['hora_fin'], 0, 5); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted" style="opacity: 0.3;">
                                                    <i class='bx bx-x-circle'></i><br>
                                                    Sin clase
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Resumen -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body">
                        <h5 class="card-title"><i class='bx bx-book'></i> Materias Impartidas</h5>
                        <ul class="list-group list-group-flush">
                            <?php if (empty($materias_impartidas)): ?>
                                <li class="list-group-item text-muted">No hay materias asignadas</li>
                            <?php else: ?>
                                <?php foreach ($materias_impartidas as $materia): ?>
                                    <li class="list-group-item"><?php echo htmlspecialchars($materia); ?></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body">
                        <h5 class="card-title"><i class='bx bx-group'></i> Grupos Asignados</h5>
                        <ul class="list-group list-group-flush">
                            <?php if (empty($grupos_asignados)): ?>
                                <li class="list-group-item text-muted">No hay grupos asignados</li>
                            <?php else: ?>
                                <?php foreach ($grupos_asignados as $grupo): ?>
                                    <li class="list-group-item"><?php echo htmlspecialchars($grupo); ?></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body">
                        <h5 class="card-title"><i class='bx bx-calendar-check'></i> Resumen Semanal</h5>
                        <div class="text-center">
                            <h2 class="text-success">
                                <?php echo $total_clases; ?>
                            </h2>
                            <p class="mb-0">Clases por semana</p>
                            <p class="text-muted small">Periodo: FEB 2026-JUL 2026</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Leyenda de colores -->
        <?php 
        // Obtener colores únicos de materias
        $colores_materias = [];
        foreach ($horario_por_dia as $dia => $clases) {
            foreach ($clases as $clase) {
                if (!isset($colores_materias[$clase['materia']]) && !empty($clase['color'])) {
                    $colores_materias[$clase['materia']] = $clase['color'];
                }
            }
        }
        
        if (!empty($colores_materias)): ?>
        <div class="card mt-4 no-print">
            <div class="card-header">
                <h5 class="mb-0"><i class='bx bx-palette'></i> Leyenda</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($colores_materias as $materia => $color): ?>
                        <div class="col-md-3 mb-2">
                            <div class="d-flex align-items-center">
                                <div style="width: 20px; height: 20px; background-color: <?php echo $color; ?>; 
                                            border-radius: 4px; margin-right: 10px;"></div>
                                <span><?php echo htmlspecialchars($materia); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; // Fin del else si la tabla existe ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>