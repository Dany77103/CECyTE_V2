<?php
session_start();
require_once 'conexion.php';

// Obtener ID del maestro
if (!isset($_GET['id'])) {
    die('ID de maestro no especificado');
}

$id_maestro = intval($_GET['id']);

// Verificar que la conexión existe
if (!isset($con)) {
    die("Error: No se pudo conectar a la base de datos.");
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

if ($table_exists) {
    // Obtener horario
    $query_horario = "
        SELECT hm.*, m.materia, m.color, g.nombre as grupo_nombre, a.nombre as aula_nombre
        FROM horarios_maestros hm
        LEFT JOIN materias m ON hm.id_materia = m.id_materia
        LEFT JOIN grupos g ON hm.id_grupo = g.id_grupo
        LEFT JOIN aulas a ON hm.id_aula = a.id_aula
        WHERE hm.id_maestro = ? AND hm.periodo = '2024-2025' AND hm.estatus = 'Activo'
        ORDER BY 
            CASE hm.dia 
                WHEN 'Lunes' THEN 1
                WHEN 'Martes' THEN 2
                WHEN 'Miércoles' THEN 3
                WHEN 'Jueves' THEN 4
                WHEN 'Viernes' THEN 5
                ELSE 6
            END,
            hm.hora_inicio
    ";
    
    try {
        $stmt_horario = $con->prepare($query_horario);
        $stmt_horario->execute([$id_maestro]);
        $horario = $stmt_horario->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al obtener el horario: " . $e->getMessage());
    }

    // Organizar por día
    $horario_por_dia = [];
    $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];

    foreach ($dias as $dia) {
        $horario_por_dia[$dia] = [];
    }

    foreach ($horario as $row) {
        $horario_por_dia[$row['dia']][] = $row;
    }
} else {
    $horario_por_dia = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario - <?php echo htmlspecialchars($maestro['nombre']); ?></title>
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 15mm;
            }
            
            body {
                font-family: Arial, sans-serif;
                font-size: 10pt;
                color: #000;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        
        .encabezado-institucional {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #064e3b;
        }
        
        .encabezado-institucional h3 {
            color: #064e3b;
            margin: 0;
            font-size: 16pt;
        }
        
        .encabezado-institucional h5 {
            color: #10b981;
            margin: 5px 0 15px 0;
            font-size: 12pt;
        }
        
        .info-maestro {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #064e3b;
            font-size: 10pt;
        }
        
        .tabla-horario {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 9pt;
        }
        
        .tabla-horario th {
            background-color: #064e3b;
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        
        .tabla-horario td {
            padding: 6px;
            text-align: center;
            border: 1px solid #ddd;
            height: 60px;
            vertical-align: top;
        }
        
        .celda-hora {
            background-color: #f1f5f9;
            font-weight: bold;
            width: 100px;
        }
        
        .celda-clase {
            font-size: 8pt;
        }
        
        .nombre-materia {
            font-weight: bold;
            margin-bottom: 2px;
            font-size: 9pt;
        }
        
        .detalles-clase {
            font-size: 7pt;
            color: #555;
        }
        
        .receso {
            background-color: #f8f9fa;
            text-align: center;
            font-weight: bold;
            font-style: italic;
        }
        
        .leyenda {
            margin-top: 20px;
            page-break-inside: avoid;
            font-size: 9pt;
        }
        
        .item-leyenda {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 8px;
        }
        
        .color-leyenda {
            display: inline-block;
            width: 12px;
            height: 12px;
            margin-right: 5px;
            vertical-align: middle;
            border: 1px solid #ccc;
        }
        
        .firma {
            margin-top: 40px;
            page-break-inside: avoid;
            font-size: 9pt;
        }
        
        .linea-firma {
            width: 250px;
            border-top: 1px solid #000;
            margin-top: 50px;
            text-align: center;
            padding-top: 5px;
        }
        
        .sello {
            position: fixed;
            bottom: 30px;
            right: 30px;
            opacity: 0.08;
            font-size: 60pt;
            transform: rotate(-45deg);
            color: #064e3b;
        }
        
        .mensaje-error {
            background-color: #ffeaa7;
            border: 1px solid #fdcb6e;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Sello de agua -->
    <div class="sello">CECyTE SC</div>
    
    <!-- Encabezado institucional -->
    <div class="encabezado-institucional">
        <h3>COLEGIO DE ESTUDIOS CIENTÍFICOS Y TECNOLÓGICOS DEL ESTADO</h3>
        <h5>SISTEMA DE GESTIÓN ESCOLAR - HORARIO DOCENTE</h5>
        <p>Periodo Escolar: 2024-2025 | Fecha de impresión: <?php echo date('d/m/Y'); ?></p>
    </div>
    
    <!-- Información del maestro -->
    <div class="info-maestro">
        <table width="100%">
            <tr>
                <td width="33%">
                    <strong>Nombre:</strong><br>
                    <?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido_paterno'] . ' ' . ($maestro['apellido_materno'] ?: '')); ?>
                </td>
                <td width="33%">
                    <strong>No. Empleado:</strong><br>
                    <?php echo htmlspecialchars($maestro['numEmpleado']); ?>
                </td>
                <td width="33%">
                    <strong>Correo:</strong><br>
                    <?php echo htmlspecialchars($maestro['correo_institucional']); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>RFC:</strong><br>
                    <?php echo htmlspecialchars($maestro['rfc'] ?: 'N/A'); ?>
                </td>
                <td>
                    <strong>CURP:</strong><br>
                    <?php echo htmlspecialchars($maestro['curp'] ?: 'N/A'); ?>
                </td>
                <td>
                    <strong>Teléfono:</strong><br>
                    <?php echo htmlspecialchars($maestro['telefono_celular'] ?: 'N/A'); ?>
                </td>
            </tr>
        </table>
    </div>
    
    <?php if (!$table_exists): ?>
        <div class="mensaje-error">
            <h4><i class='bx bx-error'></i> Horario no disponible</h4>
            <p>La tabla de horarios no ha sido configurada aún.</p>
            <p>Contacte al administrador del sistema para configurar el horario.</p>
        </div>
    <?php else: ?>
    
    <!-- Horario -->
    <table class="tabla-horario">
        <thead>
            <tr>
                <th>HORA</th>
                <th>LUNES</th>
                <th>MARTES</th>
                <th>MIÉRCOLES</th>
                <th>JUEVES</th>
                <th>VIERNES</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $bloques = [
                "11:45 - 12:45",
                "12:45 - 13:45",
                "13:45 - 14:45",
                "15:15 - 16:15",
                "16:15 - 17:15",
                "17:15 - 17:50"
            ];
            
            foreach ($bloques as $idx => $bloque):
                if ($idx == 3): // Receso
            ?>
                <tr>
                    <td class="celda-hora">14:45 - 15:15</td>
                    <td colspan="5" class="receso">
                        <strong>R E C E S O</strong>
                    </td>
                </tr>
            <?php endif; ?>
            
            <tr>
                <td class="celda-hora"><?php echo $bloque; ?></td>
                
                <?php foreach ($dias as $dia): 
                    $clase_encontrada = false;
                    $clase_actual = null;
                    
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
                    <td class="celda-clase" style="background-color: <?php echo $clase_encontrada ? ($clase_actual['color'] ?: '#dbeafe') : 'transparent'; ?>;">
                        <?php if ($clase_encontrada): ?>
                            <div class="nombre-materia">
                                <?php echo htmlspecialchars($clase_actual['materia']); ?>
                            </div>
                            <div class="detalles-clase">
                                Grupo: <?php echo htmlspecialchars($clase_actual['grupo_nombre'] ?: '---'); ?><br>
                                Aula: <?php echo htmlspecialchars($clase_actual['aula_nombre'] ?: '---'); ?>
                            </div>
                        <?php else: ?>
                            <div style="color: #ccc; font-style: italic;">
                                ---
                            </div>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Leyenda de colores -->
    <?php
    $colores_materias = [];
    foreach ($horario_por_dia as $dia => $clases) {
        foreach ($clases as $clase) {
            if (!isset($colores_materias[$clase['materia']]) && !empty($clase['color'])) {
                $colores_materias[$clase['materia']] = $clase['color'] ?: '#dbeafe';
            }
        }
    }
    
    if (!empty($colores_materias)):
    ?>
    <div class="leyenda">
        <h5>Leyenda de Materias:</h5>
        <?php foreach ($colores_materias as $materia => $color): ?>
            <div class="item-leyenda">
                <span class="color-leyenda" style="background-color: <?php echo $color; ?>;"></span>
                <?php echo htmlspecialchars($materia); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php endif; // Fin del if table_exists ?>
    
    <!-- Firmas -->
    <div class="firma">
        <table width="100%">
            <tr>
                <td width="50%" align="center">
                    <div class="linea-firma">
                        <strong>Firma del Docente</strong><br>
                        <?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido_paterno']); ?>
                    </div>
                </td>
                <td width="50%" align="center">
                    <div class="linea-firma">
                        <strong>Vo.Bo. Coordinación Académica</strong><br>
                        _______________________________
                    </div>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Botón de impresión (solo visible en pantalla) -->
    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" class="btn btn-primary">
            <i class='bx bx-printer'></i> Imprimir Horario
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i class='bx bx-x'></i> Cerrar
        </button>
    </div>
    
    <script>
        // Imprimir automáticamente al cargar la página (opcional)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 1000);
        // };
        
        // Volver al listado después de imprimir
        window.onafterprint = function() {
            setTimeout(function() {
                window.close();
            }, 1000);
        };
    </script>
</body>
</html>