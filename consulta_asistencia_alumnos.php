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

$fecha_inicio_default = date('Y-m-d', strtotime('-30 days'));
$fecha_fin_default = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['matricula'])) {
    $matricula = trim($_POST['matricula'] ?? $_GET['matricula'] ?? '');
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
            $porcentaje_asistencia = ($total_registros > 0) ? round((($total_asistencias + $total_retardos) / $total_registros) * 100, 1) : 0;
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
    <title>Asistencias | CECyTE Santa Catarina</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --accent: #8bc34a;
            --bg: #f4f6f9;
            --white: #ffffff;
            --text-main: #1e293b;
            --text-sub: #64748b;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow: 0 4px 20px rgba(0,0,0,0.06);
            --radius: 20px;
        }

        body { 
            background-color: var(--bg); 
            font-family: 'Inter', sans-serif; 
            color: var(--text-main);
            padding-top: 90px;
        }

        /* --- NAVBAR --- */
        .navbar {
            background: var(--white);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }
        .navbar-brand { display: flex; align-items: center; gap: 15px; text-decoration: none; }
        .navbar-brand img { height: 45px; }
        .navbar-brand span { font-weight: 700; color: var(--primary); font-size: 1.2rem; }

        /* --- CARDS & FILTERS --- */
        .card-custom {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.02);
            margin-bottom: 30px;
        }

        .input-custom {
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            padding: 10px 15px;
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .input-custom:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26, 83, 48, 0.1); }

        .btn-main {
            background: var(--primary); color: white; border: none; padding: 12px 20px;
            border-radius: 12px; font-weight: 700; transition: 0.3s;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-main:hover { background: var(--primary-light); transform: translateY(-2px); color: white; }

        /* --- STAT CARDS --- */
        .stat-card {
            background: white; padding: 1.5rem; border-radius: 18px; border: 1px solid #f1f5f9;
            display: flex; align-items: center; gap: 15px; height: 100%;
            transition: 0.3s; box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .stat-icon {
            width: 50px; height: 50px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        }
        .icon-p { background: #dcfce7; color: #166534; }
        .icon-f { background: #fee2e2; color: #991b1b; }
        .icon-r { background: #fef9c3; color: #854d0e; }
        .icon-a { background: #e0f2fe; color: #075985; }

        .stat-val { font-size: 1.5rem; font-weight: 800; display: block; line-height: 1; }
        .stat-label { font-size: 0.7rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; letter-spacing: 0.5px; }

        /* --- TABLE --- */
        .report-card {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .report-header {
            background: var(--primary);
            color: white; padding: 20px 30px;
        }
        .table thead th {
            background: #f8fafc; font-weight: 700; text-transform: uppercase;
            font-size: 0.75rem; color: var(--text-sub); padding: 15px 20px; border: none;
        }

        .badge-status {
            padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase;
        }
        .bg-presente { background: #dcfce7; color: #166534; }
        .bg-falta { background: #fee2e2; color: #991b1b; }
        .bg-retardo { background: #fef9c3; color: #854d0e; }
        .bg-justificada { background: #e0f2fe; color: #075985; }

        .animate-up { opacity: 0; transform: translateY(20px); transition: all 0.5s ease-out; }
    </style>
</head>
<body>

<nav class="navbar no-print">
    <a href="main.php" class="navbar-brand">
        <img src="img/logo.png" alt="CECyTE Logo">
        <span>CECyTE Santa Catarina</span>
    </a>
    <div>
        <a href="main.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fa-solid fa-house me-1"></i> Dashboard
        </a>
    </div>
</nav>

<div class="container">
    
    <div class="mb-4 d-flex justify-content-between align-items-end">
        <div>
            <h2 class="fw-800 mb-0" style="color: var(--primary);">Gestión de Asistencias</h2>
            <p class="text-secondary mb-0">Seguimiento detallado por alumno y periodo</p>
        </div>
    </div>

    <div class="card-custom">
        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold small">Matrícula del Alumno</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;"><i class="fa-solid fa-id-card text-muted"></i></span>
                    <input type="text" class="form-control input-custom border-start-0" name="matricula" placeholder="Ej. 2110200..." value="<?= htmlspecialchars($matricula); ?>" required style="border-radius: 0 12px 12px 0;">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small">Fecha Inicial</label>
                <input type="date" class="form-control input-custom" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small">Fecha Final</label>
                <input type="date" class="form-control input-custom" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin); ?>" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-main w-100">
                    <i class="fa-solid fa-magnifying-glass"></i> Consultar
                </button>
            </div>
        </form>
    </div>

    <?php if ($alumno): ?>
        <div class="card-custom animate-up" style="border-left: 6px solid var(--primary);">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="badge bg-success" style="border-radius: 6px;"><?= htmlspecialchars($alumno['estado_nombre']) ?></span>
                        <span class="text-muted small fw-bold">ID: <?= htmlspecialchars($alumno['id_alumno']) ?></span>
                    </div>
                    <h3 class="fw-800 mb-1 text-uppercase"><?= htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno']) ?></h3>
                    <p class="text-secondary mb-0">
                        <i class="fa-solid fa-graduation-cap me-1"></i> <?= htmlspecialchars($alumno['carrera_nombre']) ?> 
                        <span class="mx-2">|</span> 
                        <i class="fa-solid fa-users me-1"></i> Grupo: <strong><?= htmlspecialchars($alumno['grupo_nombre']) ?></strong>
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="stat-label">Periodo consultado</div>
                    <div class="fw-bold text-primary"><?= date('d/M/Y', strtotime($fecha_inicio)) ?> — <?= date('d/M/Y', strtotime($fecha_fin)) ?></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 animate-up">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon icon-p"><i class="fas fa-check"></i></div>
                    <div><span class="stat-val"><?= $total_asistencias ?></span><span class="stat-label">Asistencias</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon icon-f"><i class="fas fa-times"></i></div>
                    <div><span class="stat-val"><?= $total_faltas ?></span><span class="stat-label">Faltas</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon icon-r"><i class="fas fa-clock"></i></div>
                    <div><span class="stat-val"><?= $total_retardos ?></span><span class="stat-label">Retardos</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon icon-a"><i class="fas fa-percentage"></i></div>
                    <div><span class="stat-val"><?= $porcentaje_asistencia ?>%</span><span class="stat-label">Asistencia</span></div>
                </div>
            </div>
        </div>

        <div class="report-card animate-up">
            <div class="report-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-list-check me-2"></i>Registros de Clase</h5>
                <span class="badge bg-white text-dark py-2 px-3 fw-bold" style="border-radius: 8px;"><?= count($asistencias) ?> Sesiones</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Fecha y Hora</th>
                            <th>Materia / Módulo</th>
                            <th>Grupo</th>
                            <th class="text-center">Estatus</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($asistencias)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-secondary">No se encontraron registros en este rango de fechas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($asistencias as $as): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= date('d/m/Y', strtotime($as['fecha'])) ?></div>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= date('H:i', strtotime($as['fecha_registro'] ?? '00:00')) ?></small>
                                    </td>
                                    <td class="fw-500 text-dark"><?= htmlspecialchars($as['materia']) ?></td>
                                    <td><span class="badge bg-light text-dark border fw-600"><?= htmlspecialchars($as['grupo_nombre']) ?></span></td>
                                    <td class="text-center">
                                        <?php 
                                            $clase_bg = 'bg-' . strtolower($as['estado']);
                                            echo "<span class='badge-status $clase_bg'>".$as['estado']."</span>";
                                        ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light border shadow-sm"><i class="fas fa-file-lines text-primary"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
        <div class="card-custom text-center py-5 animate-up">
            <i class="fas fa-search fa-3x mb-3 text-muted opacity-25"></i>
            <h5 class="fw-bold text-secondary">No se encontró el alumno</h5>
            <p class="text-muted mb-0">Verifica que la matrícula <strong><?= htmlspecialchars($matricula) ?></strong> sea correcta y esté activa.</p>
        </div>
    <?php endif; ?>

    <footer class="text-center py-5 text-secondary small">
        SGA CECyTE Santa Catarina &copy; 2026 • Sistema de Control de Asistencias
    </footer>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            document.querySelectorAll('.animate-up').forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }, 100);
    });
</script>

</body>
</html>