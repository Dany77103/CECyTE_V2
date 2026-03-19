<?php
session_start();

// Control de acceso: Solo maestros, admin o usuarios del sistema
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['rol'], ['maestro', 'Maestro', 'admin', 'administrador', 'usuario'])) {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// 1. Obtener lista de maestros para el filtro (Solo para Administradores)
$maestros = [];
$es_admin = in_array(strtolower($_SESSION['rol']), ['admin', 'administrador', 'usuario']);

if ($es_admin) {
    $sql_maestros = "SELECT id_maestro, CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) as nombre_completo 
                     FROM maestros 
                     WHERE activo = 'Activo' 
                     ORDER BY apellido_paterno ASC";
    $stmt_maestros = $con->prepare($sql_maestros);
    $stmt_maestros->execute();
    $maestros = $stmt_maestros->fetchAll(PDO::FETCH_ASSOC);
}

// 2. Definir qué clases mostrar
$id_maestro_seleccionado = $_GET['maestro_id'] ?? null;

if (!$es_admin) {
    $id_maestro = $_SESSION['maestro_id'] ?? 0; 
    $sql = "SELECT DISTINCT h.id_materia, h.id_grupo, m.materia, g.nombre as grupo_nombre
            FROM horarios_maestros h
            JOIN materias m ON h.id_materia = m.id_materia
            JOIN grupos g ON h.id_grupo = g.id_grupo
            WHERE h.id_maestro = :id_maestro AND h.estatus = 'Activo'
            ORDER BY m.materia ASC";
    $stmt = $con->prepare($sql);
    $stmt->execute(['id_maestro' => $id_maestro]);
} else {
    $query = "SELECT DISTINCT h.id_materia, h.id_grupo, m.materia, g.nombre as grupo_nombre,
                       CONCAT(ma.nombre, ' ', ma.apellido_paterno) as nombre_maestro
              FROM horarios_maestros h
              JOIN materias m ON h.id_materia = m.id_materia
              JOIN grupos g ON h.id_grupo = g.id_grupo
              JOIN maestros ma ON h.id_maestro = ma.id_maestro
              WHERE h.estatus = 'Activo'";
    
    if ($id_maestro_seleccionado) {
        $query .= " AND h.id_maestro = :id_maestro";
        $stmt = $con->prepare($query);
        $stmt->execute(['id_maestro' => $id_maestro_seleccionado]);
    } else {
        $query .= " ORDER BY ma.apellido_paterno ASC, m.materia ASC";
        $stmt = $con->prepare($query);
        $stmt->execute();
    }
}

$clases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Clase | CECYTE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-dark: #1b5e20;
            --accent: #8bc34a;
            --bg: #f4f7f6;
            --white: #ffffff;
            --shadow: 0 4px 15px rgba(0,0,0,0.08);
            --dark-qr: #2c3e50;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; color: #333; }
        
        .header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white; padding: 1rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky; top: 0; z-index: 100;
        }

        .header h1 { font-size: 1.4rem; margin: 0; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .top-actions {
            margin-bottom: 25px;
            display: flex;
            justify-content: flex-start;
        }

        .btn-qr-global {
            background: var(--dark-qr);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: 0.3s;
        }

        .btn-qr-global:hover {
            background: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
            color: white;
        }

        .clases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .card-clase {
            background: var(--white); border-radius: 16px; overflow: hidden;
            box-shadow: var(--shadow); transition: 0.3s;
            display: flex; flex-direction: column;
        }

        .card-clase:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .card-header { background: #f9fbf9; padding: 20px; border-bottom: 1px solid #eee; }
        .card-header h3 { margin: 0; color: var(--primary-dark); font-size: 1.1rem; }

        .group-badge {
            display: inline-block; background: var(--accent); color: var(--primary-dark);
            padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.8rem; margin-top: 10px;
        }

        .card-body { padding: 20px; flex-grow: 1; font-size: 0.95rem; color: #666; }
        .info-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .card-footer { padding: 15px; background: #fdfdfd; display: grid; gap: 8px; }

        .btn-action {
            text-decoration: none; text-align: center; padding: 10px;
            border-radius: 8px; font-weight: 600; font-size: 0.85rem;
            transition: 0.2s;
        }
        .btn-main { background: var(--primary); color: white; border: none; }
        .btn-main:hover { background: var(--primary-dark); color: white; }
        
        .btn-qr-sec { 
            background: var(--dark-qr); 
            color: white; 
            border: 1px solid var(--dark-qr);
        }
        .btn-qr-sec:hover { background: #1a252f; color: white; }

        .btn-outline { border: 1px solid #ddd; color: #555; }
        .btn-outline:hover { background: #eee; }

        .filter-section {
            background: white; padding: 20px; border-radius: 12px;
            margin-bottom: 20px; box-shadow: var(--shadow);
            display: flex; gap: 15px; align-items: center;
        }

        select { padding: 10px; border-radius: 8px; border: 1px solid #ddd; flex-grow: 1; }

        @media (max-width: 600px) {
            .filter-section { flex-direction: column; align-items: stretch; }
            .btn-qr-global { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<header class="header">
    <a href="main.php" style="color:white; text-decoration:none;"><i class="fas fa-arrow-left"></i> Volver</a>
    <h1>CECYTE Santa Catarina</h1>
    <div class="user-info">
        <small><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Usuario') ?></small>
    </div>
</header>

<div class="container">
    
    <?php if ($es_admin): ?>
    <div class="filter-section">
        <form method="GET" style="display:flex; width:100%; gap:10px;">
            <select name="maestro_id">
                <option value="">-- Todos los Maestros --</option>
                <?php foreach ($maestros as $m): ?>
                    <option value="<?= $m['id_maestro'] ?>" <?= ($id_maestro_seleccionado == $m['id_maestro']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-main" style="padding:0 20px; border-radius:8px; cursor:pointer;">
                Filtrar
            </button>
        </form>
    </div>
    <?php endif; ?>

    <div class="top-actions">
        <a href="asistencia_qrv2.php" class="btn-qr-global">
            <i class="fas fa-qrcode"></i> Lector QR General
        </a>
    </div>

    <div class="clases-grid">
        <?php if (empty($clases)): ?>
            <div style="grid-column: 1/-1; text-align:center; padding: 50px;">
                <i class="fas fa-folder-open" style="font-size: 3rem; color: #ccc;"></i>
                <p>No hay clases disponibles en este momento.</p>
            </div>
        <?php else: ?>
            <?php foreach ($clases as $clase): ?>
            <div class="card-clase">
                <div class="card-header">
                    <h3><?= htmlspecialchars($clase['materia']) ?></h3>
                    <div class="group-badge">GRUPO <?= htmlspecialchars($clase['grupo_nombre']) ?></div>
                </div>
                <div class="card-body">
                    <?php if ($es_admin): ?>
                    <div class="info-row">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Prof: <?= htmlspecialchars($clase['nombre_maestro']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Ciclo Escolar 2026</span>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="tomar_asistencia.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" class="btn-action btn-main">
                        <i class="fas fa-user-check"></i> Asistencia Manual
                    </a>
                    
                    <a href="asistencia_qrv2.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" class="btn-action btn-qr-sec">
                        <i class="fas fa-qrcode"></i> Escanear QR
                    </a>

                    <a href="calificaciones.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" class="btn-action btn-outline">
                        <i class="fas fa-star"></i> Calificaciones
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    document.querySelectorAll('.card-clase').forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(15px)';
        setTimeout(() => {
            card.style.transition = 'all 0.4s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, i * 80);
    });
</script>

</body>
</html>