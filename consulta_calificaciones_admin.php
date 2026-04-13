<?php
session_start();
// Verificar sesión administrativa o de maestro
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['rol'], ['maestro', 'Maestro', 'admin', 'administrador', 'usuario', 'sistema'])) {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Inicializar variables
$id_grupo = $_GET['grupo'] ?? '';
$id_materia = $_GET['materia'] ?? '';
$parcial = $_GET['parcial'] ?? '1'; 

// Consultas para los filtros
$grupos = $con->query("SELECT id_grupo, nombre, semestre FROM grupos WHERE activo = 1 ORDER BY semestre, nombre")->fetchAll(PDO::FETCH_ASSOC);
$materias = $con->query("SELECT id_materia, materia FROM materias ORDER BY materia")->fetchAll(PDO::FETCH_ASSOC);

// Consulta de calificaciones
$calificaciones = [];
if ($id_grupo && $id_materia) {
    $sql = "SELECT a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno, 
                   c.libreta_guia_puntos, c.asistencia_puntos, c.participacion_puntos, c.examen_puntos,
                   ((IFNULL(c.libreta_guia_puntos,0) * 0.50) + (IFNULL(c.asistencia_puntos,0) * 0.05) + (IFNULL(c.participacion_puntos,0) * 0.05) + (IFNULL(c.examen_puntos,0) * 0.40)) as calificacion_final
            FROM alumnos a
            LEFT JOIN calificaciones_parcial c ON a.id_alumno = c.id_alumno 
                AND c.id_materia = :materia 
                AND c.id_parcial = :parcial
            WHERE a.id_grupo = :grupo
            ORDER BY a.apellido_paterno, a.apellido_materno";
    
    $stmt = $con->prepare($sql);
    $stmt->execute([
        ':materia' => $id_materia,
        ':parcial' => $parcial,
        ':grupo'   => $id_grupo
    ]);
    $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificaciones | CECyTE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --secondary: #6c757d;
            --white: #ffffff;
            --bg: #f4f6f9;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            color: #333;
            padding-top: 90px;
        }

        /* --- NAVBAR (IDÉNTICA AL DASHBOARD) --- */
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

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }

        .navbar-brand img { height: 45px; width: auto; }

        .navbar-brand span {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.2rem;
            letter-spacing: -0.5px;
        }

        /* --- FILTROS ESTILO DASHBOARD --- */
        .card-custom {
            background: var(--white);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.02);
            margin-bottom: 30px;
        }

        .input-custom {
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            padding: 10px 15px;
            transition: var(--transition);
        }

        .input-custom:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 83, 48, 0.1);
        }

        .btn-main {
            background: var(--primary);
            color: white;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-main:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            color: white;
        }

        /* --- TABLA Y REPORTES --- */
        .report-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .report-header {
            background: var(--primary);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table thead th {
            background: #f8fafc;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: var(--secondary);
            padding: 15px;
            border-bottom: 2px solid #eee;
        }

        .reprobado { color: #dc3545; font-weight: 700; }
        .aprobado { color: #198754; font-weight: 700; }

        @media print {
            .no-print { display: none !important; }
            body { padding-top: 0; background: white; }
            .report-card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>

    <nav class="navbar no-print">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div class="user-controls">
            <a href="main.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-house me-1"></i> Inicio
            </a>
        </div>
    </nav>

    <div class="container">
        
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h2 class="fw-bold" style="color: var(--primary);">Control de Calificaciones</h2>
                <p class="text-secondary mb-0">Consulta y exportación de historial académico por parcial</p>
            </div>
        </div>

        <div class="card-custom no-print">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Grupo Escolar</label>
                    <select name="grupo" class="form-select input-custom" required>
                        <option value="">Seleccionar grupo...</option>
                        <?php foreach ($grupos as $g): ?>
                            <option value="<?= $g['id_grupo'] ?>" <?= $id_grupo == $g['id_grupo'] ? 'selected' : '' ?>>
                                <?= $g['semestre'] ?>º - <?= htmlspecialchars($g['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Materia / Módulo</label>
                    <select name="materia" class="form-select input-custom" required>
                        <option value="">Seleccionar materia...</option>
                        <?php foreach ($materias as $m): ?>
                            <option value="<?= $m['id_materia'] ?>" <?= $id_materia == $m['id_materia'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['materia']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small">Periodo</label>
                    <select name="parcial" class="form-select input-custom">
                        <option value="1" <?= $parcial == '1' ? 'selected' : '' ?>>1er Parcial</option>
                        <option value="2" <?= $parcial == '2' ? 'selected' : '' ?>>2do Parcial</option>
                        <option value="3" <?= $parcial == '3' ? 'selected' : '' ?>>3er Parcial</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn-main w-100 justify-content-center">
                        <i class="fa-solid fa-magnifying-glass"></i> Consultar Reporte
                    </button>
                </div>
            </form>
        </div>

        <?php if ($id_grupo && $id_materia): ?>
        <div class="report-card" id="reporte-pdf">
            <div class="report-header">
                <div>
                    <h5 class="mb-0 fw-bold">Reporte de Calificaciones</h5>
                    <small style="opacity: 0.9;">CECyTE Santa Catarina • Parcial <?= $parcial ?></small>
                </div>
                
                <div class="no-print">
                    <button onclick="descargarPDF()" class="btn btn-danger btn-sm px-3 fw-bold me-2">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </button>
                    <button onclick="window.print()" class="btn btn-light btn-sm px-3 fw-bold">
                        <i class="fa-solid fa-print"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Matrícula</th>
                            <th>Nombre del Alumno</th>
                            <th class="text-center">Libreta (50%)</th>
                            <th class="text-center">Asist (5%)</th>
                            <th class="text-center">Part (5%)</th>
                            <th class="text-center">Exam (40%)</th>
                            <th class="text-center">Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($calificaciones) > 0): ?>
                            <?php foreach ($calificaciones as $row): 
                                $nota = $row['calificacion_final'];
                                $clase_nota = ($nota < 6) ? 'reprobado' : 'aprobado';
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary small"><?= $row['matricula'] ?></td>
                                <td class="text-uppercase small" style="font-weight: 500;">
                                    <?= htmlspecialchars($row['apellido_paterno'] . ' ' . ($row['apellido_materno'] ?? '') . ' ' . $row['nombre']) ?>
                                </td>
                                <td class="text-center text-secondary"><?= $row['libreta_guia_puntos'] ?? '-' ?></td>
                                <td class="text-center text-secondary"><?= $row['asistencia_puntos'] ?? '-' ?></td>
                                <td class="text-center text-secondary"><?= $row['participacion_puntos'] ?? '-' ?></td>
                                <td class="text-center text-secondary"><?= $row['examen_puntos'] ?? '-' ?></td>
                                <td class="text-center">
                                    <span class="<?= $clase_nota ?> bg-light border rounded-pill px-3 py-1">
                                        <?= number_format($nota, 1) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-secondary">No se encontraron registros para esta selección.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fa-solid fa-clipboard-list fa-4x mb-3" style="color: #e2e8f0;"></i>
            <h5 class="text-secondary">Selecciona los criterios para generar el historial</h5>
        </div>
        <?php endif; ?>

        <footer class="text-center py-5 text-secondary small">
            Sistema de Gestión Académica &copy; <?php echo date('Y'); ?>
        </footer>
    </div>

    <script>
        function descargarPDF() {
            const elemento = document.getElementById('reporte-pdf');
            const opciones = {
                margin: 10,
                filename: 'Reporte_Calificaciones_Parcial_<?php echo $parcial; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 3, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };
            html2pdf().set(opciones).from(elemento).save();
        }
    </script>
</body>
</html>