<?php
// gestion_asistencias_alumnos.php
session_start();
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Variables
$matricula = '';
$fecha_inicio = '';
$fecha_fin = '';
$alumno = null;
$asistencias = [];
$total_asistencias = 0;
$total_faltas = 0;
$total_retardos = 0;
$total_justificadas = 0;

// Procesar parámetros GET
if (isset($_GET['matricula'])) {
    $matricula = trim($_GET['matricula']);
}

// Obtener fechas por defecto (últimos 30 días)
$fecha_inicio_default = date('Y-m-d', strtotime('-30 days'));
$fecha_fin_default = date('Y-m-d');

// Si no hay matrícula en GET, verificar POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = trim($_POST['matricula'] ?? '');
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? $fecha_inicio_default);
    $fecha_fin = trim($_POST['fecha_fin'] ?? $fecha_fin_default);
} else {
    $fecha_inicio = $fecha_inicio_default;
    $fecha_fin = $fecha_fin_default;
}

// Validar fechas
if (empty($fecha_inicio)) $fecha_inicio = $fecha_inicio_default;
if (empty($fecha_fin)) $fecha_fin = $fecha_fin_default;

// Si se proporcionó una matrícula, buscar al alumno y sus asistencias
if (!empty($matricula)) {
    try {
        // Buscar alumno
        $sql_alumno = "SELECT a.*, 
                              c.nombre as carrera_nombre,
                              g.nombre as grupo_nombre,
                              e.estado as estado_nombre
                       FROM alumnos a
                       LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
                       LEFT JOIN grupos g ON a.id_grupo = g.id_grupo
                       LEFT JOIN estados e ON a.id_estado = e.id_estado
                       WHERE a.matricula = :matricula";
        
        $stmt_alumno = $con->prepare($sql_alumno);
        $stmt_alumno->bindParam(':matricula', $matricula);
        $stmt_alumno->execute();
        $alumno = $stmt_alumno->fetch(PDO::FETCH_ASSOC);
        
        if ($alumno) {
            // Buscar asistencias en el rango de fechas
            $sql_asistencias = "SELECT ac.*, 
                                       m.materia,
                                       g.nombre as grupo_nombre
                                FROM asistencias_clase ac
                                LEFT JOIN materias m ON ac.id_materia = m.id_materia
                                LEFT JOIN grupos g ON ac.id_grupo = g.id_grupo
                                WHERE ac.id_alumno = :id_alumno
                                AND ac.fecha BETWEEN :fecha_inicio AND :fecha_fin
                                ORDER BY ac.fecha DESC, m.materia";
            
            $stmt_asistencias = $con->prepare($sql_asistencias);
            $stmt_asistencias->bindParam(':id_alumno', $alumno['id_alumno']);
            $stmt_asistencias->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt_asistencias->bindParam(':fecha_fin', $fecha_fin);
            $stmt_asistencias->execute();
            $asistencias = $stmt_asistencias->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular estadísticas
            foreach ($asistencias as $asistencia) {
                switch ($asistencia['estado']) {
                    case 'Presente': $total_asistencias++; break;
                    case 'Falta': $total_faltas++; break;
                    case 'Retardo': $total_retardos++; break;
                    case 'Justificada': $total_justificadas++; break;
                }
            }
        }
    } catch (PDOException $e) {
        die("Error al obtener datos: " . $e->getMessage());
    }
}

// ... (El código de exportación Excel/PDF permanece intacto por seguridad, omitido para brevedad en esta respuesta)
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asistencias - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary: #006400; /* Verde Institucional Profundo */
            --secondary: #2e7d32;
            --accent: #f4f7f6;
            --light: #ffffff;
            --text-dark: #2c3e50;
        }
        
        body { background-color: var(--accent); font-family: 'Segoe UI', sans-serif; color: var(--text-dark); }
        
        .header-cecyte {
            background: var(--primary);
            color: var(--light);
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card { border: none; border-radius: 12px; box-shadow: 0 6px 12px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
        .card-header { background: var(--secondary); color: white; border-radius: 12px 12px 0 0 !important; padding: 1rem 1.5rem; }
        
        .btn-primary { background-color: var(--primary); border: none; }
        .btn-primary:hover { background-color: #004d00; }

        .stats-card { background: white; padding: 1.5rem; border-radius: 12px; text-align: center; border-left: 5px solid var(--primary); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .stats-number { font-size: 1.8rem; font-weight: 800; color: var(--primary); }
        
        .table-hover tbody tr:hover { background-color: #e8f5e9; }
        .badge { padding: 0.5rem 0.8rem; border-radius: 6px; font-size: 0.85rem; }
    </style>
</head>
<body>

    <header class="header-cecyte">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0"><i class='bx bx-calendar-check'></i> Gestión de Asistencias</h2>
                <small>Sistema de Control Escolar - CECyTE</small>
            </div>
            <a href="gestion_alumnos.php" class="btn btn-outline-light"><i class='bx bx-arrow-back'></i> Volver</a>
        </div>
    </header>

    <main class="container">
        <section class="card p-4">
            <h4 class="mb-3 text-primary"><i class='bx bx-search'></i> Filtros de Consulta</h4>
            <form method="POST" action="" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Matrícula</label>
                    <input type="text" class="form-control" name="matricula" value="<?php echo htmlspecialchars($matricula); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary px-4">Consultar</button>
                </div>
            </form>
        </section>

        <?php if ($alumno): ?>
            <section class="row">
                <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo $total_asistencias; ?></div><small>Asistencias</small></div></div>
                <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo $total_faltas; ?></div><small>Faltas</small></div></div>
                <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo $total_retardos; ?></div><small>Retardos</small></div></div>
                <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo $porcentaje_asistencia ?? 0; ?>%</div><small>Efectividad</small></div></div>
            </section>
        <?php endif; ?>
    </main>

</body>
</html>