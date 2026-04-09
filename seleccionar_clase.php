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
    <title>SGA | Seleccionar Clase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-dark: #1b5e20;
            --accent: #8bc34a;
            --bg: #f0f2f5;
            --white: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text-main); line-height: 1.5; }

        /* --- HEADER MODERNO --- */
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

        /* --- FILTROS --- */
        .filter-card {
            background: var(--white);
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .filter-form { display: flex; gap: 12px; }
        select {
            flex-grow: 1; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;
            background: #f8fafc; font-size: 0.95rem; outline: none; transition: 0.2s;
        }
        select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1); }

        /* --- BOTONES --- */
        .btn {
            padding: 12px 24px; border-radius: 10px; border: none; font-weight: 700;
            cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; font-size: 0.9rem;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .btn-qr { background: #334155; color: white; margin-bottom: 25px; }
        .btn-qr:hover { background: #1e293b; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2); }
        
        /* Botón de volver adicional */
        .btn-back { 
            background: #f1f5f9; 
            color: #475569; 
            margin-bottom: 10px;
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        .btn-back:hover { background: #e2e8f0; color: #1e293b; }

        /* --- GRID DE CLASES --- */
        .clases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
        }

        .card-clase {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-clase:hover { transform: translateY(-8px); }

        .card-visual {
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .card-content { padding: 25px; flex-grow: 1; }
        .materia-title { font-size: 1.2rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 5px; }
        
        .badge-grupo {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.75rem;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .info-item {
            display: flex; align-items: center; gap: 10px;
            color: var(--text-muted); font-size: 0.85rem; margin-bottom: 8px;
        }

        /* --- ACCIONES --- */
        .card-actions {
            padding: 20px 25px 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .btn-action {
            padding: 10px; border-radius: 12px; text-align: center;
            font-weight: 700; font-size: 0.8rem; text-decoration: none; transition: 0.2s;
        }
        .btn-asistencia { background: var(--primary); color: white; grid-column: span 2; }
        .btn-qr-card { background: #f1f5f9; color: #475569; }
        .btn-calif { background: #f1f5f9; color: #475569; }
        
        .btn-action:hover { opacity: 0.9; filter: brightness(0.95); }

        @media (max-width: 600px) {
            .clases-grid { grid-template-columns: 1fr; }
            .filter-form { flex-direction: column; }
        }
    </style>
</head>
<body>

<header class="header">
    <a href="main.php" class="header-brand">
        <i class="fas fa-graduation-cap"></i>
        <span>SGA CECYTE</span>
    </a>
    <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Maestro') ?>
    </div>
</header>

<div class="container">
    
    <a href="main.php" class="btn btn-back">
        <i class="fas fa-arrow-left"></i> Volver al Inicio
    </a>

    <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="font-size: 1.8rem; font-weight: 800; color: var(--primary-dark);">Mis Clases</h2>
            <p style="color: var(--text-muted);">Selecciona una materia para gestionar asistencia o calificaciones.</p>
        </div>
    </div>

    <?php if ($es_admin): ?>
    <div class="filter-card">
        <form method="GET" class="filter-form">
            <select name="maestro_id">
                <option value="">Visualizar todos los maestros</option>
                <?php foreach ($maestros as $m): ?>
                    <option value="<?= $m['id_maestro'] ?>" <?= ($id_maestro_seleccionado == $m['id_maestro']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filtrar Clases
            </button>
        </form>
    </div>
    <?php endif; ?>

    <a href="asistencia_qrv2.php" class="btn btn-qr">
        <i class="fas fa-qrcode"></i> Lector QR de Acceso General
    </a>

    <div class="clases-grid">
        <?php if (empty($clases)): ?>
            <div style="grid-column: 1/-1; text-align:center; padding: 60px; background:white; border-radius:20px;">
                <img src="https://illustrations.popsy.co/green/empty-folder.svg" style="width:150px; margin-bottom:20px;">
                <p style="font-weight: 600; color: var(--text-muted);">No se encontraron clases programadas.</p>
            </div>
        <?php else: ?>
            <?php foreach ($clases as $clase): ?>
            <div class="card-clase">
                <div class="card-visual"></div>
                <div class="card-content">
                    <div class="badge-grupo">Grupo <?= htmlspecialchars($clase['grupo_nombre']) ?></div>
                    <h3 class="materia-title"><?= htmlspecialchars($clase['materia']) ?></h3>
                    
                    <?php if ($es_admin): ?>
                    <div class="info-item">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Prof. <?= htmlspecialchars($clase['nombre_maestro']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span>Ciclo Escolar Activo 2026</span>
                    </div>
                </div>

                <div class="card-actions">
                    <a href="tomar_asistencia.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" class="btn-action btn-asistencia">
                        <i class="fas fa-user-check"></i> Tomar Asistencia
                    </a>
                    <a href="asistencia_qrv2.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" class="btn-action btn-qr-card" title="Escanear QR">
                        <i class="fas fa-qrcode"></i> QR
                    </a>
                    <a href="calificaciones.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" class="btn-action btn-calif" title="Calificaciones">
                        <i class="fas fa-star"></i> Notas
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    // Animación de entrada para las tarjetas
    document.querySelectorAll('.card-clase').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
</script>

</body>
</html>