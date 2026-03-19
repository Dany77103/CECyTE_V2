<?php
// gestion_asistencias_alumnos.php
session_start();
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// --- LÓGICA DE DATOS ---
$matricula = '';
$fecha_inicio = '';
$fecha_fin = '';
$alumno = null;
$asistencias = [];
$total_asistencias = 0;
$total_faltas = 0;
$total_retardos = 0;
$total_justificadas = 0;

if (isset($_GET['matricula'])) {
    $matricula = trim($_GET['matricula']);
}

$fecha_inicio_default = date('Y-m-d', strtotime('-30 days'));
$fecha_fin_default = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = trim($_POST['matricula'] ?? '');
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? $fecha_inicio_default);
    $fecha_fin = trim($_POST['fecha_fin'] ?? $fecha_fin_default);
} else {
    $fecha_inicio = $fecha_inicio_default;
    $fecha_fin = $fecha_fin_default;
}

if (!empty($matricula)) {
    try {
        $sql_alumno = "SELECT a.*, c.nombre as carrera_nombre, g.nombre as grupo_nombre, e.estado as estado_nombre
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
            $sql_asistencias = "SELECT ac.*, m.materia, g.nombre as grupo_nombre
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
            
            foreach ($asistencias as $asistencia) {
                switch ($asistencia['estado']) {
                    case 'Presente': $total_asistencias++; break;
                    case 'Falta': $total_faltas++; break;
                    case 'Retardo': $total_retardos++; break;
                    case 'Justificada': $total_justificadas++; break;
                }
            }
            $total_registros = count($asistencias);
            $porcentaje_asistencia = ($total_registros > 0) ? round(($total_asistencias / $total_registros) * 100, 1) : 0;
        }
    } catch (PDOException $e) {
        die("Error al obtener datos: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA | Historial de Asistencias</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-dark: #1b5e20;
            --accent: #8bc34a;
            --bg: #f0f2f5;
            --white: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text-main); }

        /* --- HEADER MODERNO (Identidad Visual SGA) --- */
        .header {
            background: var(--white);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            position: sticky; top: 0; z-index: 100;
        }
        .header-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--primary-dark); font-weight: 800; }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 20px; }

        /* --- FILTROS (Filter Card) --- */
        .filter-card {
            background: var(--white);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .form-control {
            border-radius: 10px; border: 1px solid #e2e8f0;
            padding: 10px 15px; font-size: 0.95rem;
        }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1); }

        /* --- BOTONES --- */
        .btn-cecyte {
            background: var(--primary); color: white; border: none; padding: 12px 24px;
            border-radius: 10px; font-weight: 700; transition: 0.3s;
        }
        .btn-cecyte:hover { background: var(--primary-dark); transform: translateY(-2px); color: white; }

        /* --- TARJETAS DE DATOS Y ESTADÍSTICAS --- */
        .card-custom {
            background: var(--white);
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.03);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 25px;
        }
        .card-header-accent {
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .stat-card {
            background: white; padding: 1.5rem; border-radius: 18px; border: 1px solid #f1f5f9;
            display: flex; align-items: center; gap: 15px; height: 100%;
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow); }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        }
        .icon-p { background: #e8f5e9; color: #2e7d32; }
        .icon-f { background: #fee2e2; color: #dc2626; }
        .icon-r { background: #fef9c3; color: #ca8a04; }
        .icon-a { background: #e0f2fe; color: #0284c7; }

        .stat-val { font-size: 1.4rem; font-weight: 800; color: var(--text-main); display: block; line-height: 1; }
        .stat-label { font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

        /* --- TABLA --- */
        .table-responsive { border-radius: 0 0 20px 20px; }
        .table thead th { 
            background: #f8fafc; color: var(--text-muted); 
            font-size: 0.75rem; text-transform: uppercase; font-weight: 700;
            padding: 18px 20px; border: none;
        }
        .table tbody td { padding: 18px 20px; vertical-align: middle; border-color: #f1f5f9; }

        .badge-status {
            padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase;
        }
        .bg-presente { background: #dcfce7; color: #166534; }
        .bg-falta { background: #fee2e2; color: #991b1b; }
        .bg-retardo { background: #fef9c3; color: #854d0e; }
        .bg-justificada { background: #e0f2fe; color: #075985; }

        .animate-up { opacity: 0; transform: translateY(20px); }
    </style>
</head>
<body>

<header class="header">
    <a href="main.php" class="header-brand">
        <i class="fas fa-graduation-cap"></i>
        <span>SGA CECYTE</span>
    </a>
    <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Administrador') ?>
    </div>
</header>

<div class="container">
    
    <div style="margin-bottom: 30px;">
        <h2 style="font-size: 1.8rem; font-weight: 800; color: var(--primary-dark);">Consulta de Asistencias</h2>
        <p style="color: var(--text-muted);">Visualiza el historial detallado y estadísticas por alumno.</p>
    </div>

    <div class="filter-card">
        <form method="POST" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Matrícula del Estudiante</label>
                <input type="text" class="form-control" name="matricula" placeholder="Ej. 202400123" value="<?= htmlspecialchars($matricula); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Desde</label>
                <input type="date" class="form-control" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Hasta</label>
                <input type="date" class="form-control" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin); ?>" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-cecyte w-100">
                    <i class="fas fa-search me-1"></i> Consultar
                </button>
            </div>
        </form>
    </div>

    <?php if ($alumno): ?>
        <div class="card-custom animate-up">
            <div class="card-header-accent"></div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-4">
                    <div class="bg-light p-3 rounded-circle d-none d-md-block text-primary">
                        <i class="fas fa-user-graduate fa-2x"></i>
                    </div>
                    <div>
                        <div class="badge bg-success mb-2" style="font-size: 0.65rem; padding: 5px 10px;"><?= htmlspecialchars($alumno['estado_nombre']) ?></div>
                        <h3 class="fw-800 mb-1" style="font-weight: 800; color: var(--primary-dark);">
                            <?= htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno']) ?>
                        </h3>
                        <p class="text-muted mb-0 small">
                            <i class="fas fa-id-card me-1"></i> <strong><?= htmlspecialchars($alumno['matricula']) ?></strong> | 
                            <?= htmlspecialchars($alumno['carrera_nombre']) ?> | 
                            Grupo: <strong><?= htmlspecialchars($alumno['grupo_nombre']) ?></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 animate-up" style="transition-delay: 0.1s;">
            <div class="col-md-3">
                <div class="stat-card shadow-sm">
                    <div class="stat-icon icon-p"><i class="fas fa-check"></i></div>
                    <div><span class="stat-val"><?= $total_asistencias ?></span><span class="stat-label">Asistencias</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card shadow-sm">
                    <div class="stat-icon icon-f"><i class="fas fa-times"></i></div>
                    <div><span class="stat-val"><?= $total_faltas ?></span><span class="stat-label">Faltas</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card shadow-sm">
                    <div class="stat-icon icon-r"><i class="fas fa-clock"></i></div>
                    <div><span class="stat-val"><?= $total_retardos ?></span><span class="stat-label">Retardos</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card shadow-sm">
                    <div class="stat-icon icon-a"><i class="fas fa-chart-pie"></i></div>
                    <div><span class="stat-val"><?= $porcentaje_asistencia ?>%</span><span class="stat-label">Promedio</span></div>
                </div>
            </div>
        </div>

        <div class="card-custom animate-up" style="transition-delay: 0.2s;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Fecha</th>
                            <th>Materia</th>
                            <th>Grupo</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($asistencias)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron registros en el rango de fechas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($asistencias as $as): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= date('d/m/Y', strtotime($as['fecha'])) ?></div>
                                        <div class="text-muted small"><?= date('H:i', strtotime($as['fecha_registro'] ?? '00:00')) ?> hrs</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($as['materia']) ?></div>
                                        <div class="text-muted small">Materia Académica</div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($as['grupo_nombre']) ?></span></td>
                                    <td>
                                        <?php 
                                            $clase_bg = 'bg-' . strtolower($as['estado']);
                                            echo "<span class='badge-status $clase_bg'>".$as['estado']."</span>";
                                        ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light border" title="Ver observación"><i class="fas fa-search-plus"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
        <div class="alert bg-white border-0 shadow-sm rounded-4 p-4 text-center">
            <img src="https://illustrations.popsy.co/green/falling.svg" style="width:120px; margin-bottom:15px;">
            <h5 class="fw-bold text-danger">Alumno no encontrado</h5>
            <p class="text-muted mb-0">La matrícula <strong><?= htmlspecialchars($matricula) ?></strong> no existe en nuestra base de datos.</p>
        </div>
    <?php endif; ?>
</div>

<footer class="text-center py-5 text-muted small">
    SGA - CECyTE Santa Catarina © 2026 | Desarrollado para Control Académico
</footer>

<script>
    // Animación de entrada secuencial
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.animate-up').forEach((el, i) => {
            setTimeout(() => {
                el.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, i * 150);
        });
    });
</script>

</body>
</html>