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
    <title>Clases | SGA CECyTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --accent: #8bc34a;
            --bg: #f4f7f6;
            --white: #ffffff;
            --text-main: #1e293b;
            --text-sub: #64748b;
            --shadow: 0 4px 25px rgba(0,0,0,0.08);
            --radius: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 5%; position: fixed;
            top: 0; left: 0; right: 0; z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.04);
        }
        .navbar-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--primary); font-weight: 800; }
        .navbar-brand img { height: 42px; }

        .container { max-width: 1300px; margin: 0 auto; padding: 0 25px; }

        /* --- TITULOS --- */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-title h2 { font-size: 1.8rem; font-weight: 800; color: var(--primary); }
        .page-title p { color: var(--text-sub); font-size: 0.95rem; }

        /* --- FILTROS (ADMIN) --- */
        .filter-card { 
            background: var(--white); border-radius: var(--radius); 
            padding: 25px; margin-bottom: 30px; box-shadow: var(--shadow);
        }
        .filter-form { display: grid; grid-template-columns: 1fr auto; gap: 15px; }
        .input-custom {
            width: 100%; padding: 12px 18px; border-radius: 12px; border: 1.5px solid #e2e8f0; 
            font-size: 0.9rem; font-family: 'Inter'; outline: none;
        }
        .input-custom:focus { border-color: var(--primary); }

        /* --- BOTONES --- */
        .btn-main {
            background: var(--primary); color: white; padding: 12px 25px; 
            border-radius: 12px; text-decoration: none; font-weight: 700; 
            font-size: 0.9rem; display: inline-flex; align-items: center; gap: 10px;
            border: none; cursor: pointer; transition: 0.3s;
        }
        .btn-main:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(26, 83, 48, 0.2); }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-qr-full { background: #1e293b; color: white; margin-bottom: 30px; width: 100%; justify-content: center; }

        /* --- GRID DE CLASES --- */
        .clases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .card-clase {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.02);
        }
        .card-clase:hover { transform: translateY(-8px); }

        .card-top {
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .card-body { padding: 25px; flex-grow: 1; }
        .badge-grupo {
            background: #f0fdf4; color: var(--primary);
            padding: 6px 14px; border-radius: 10px; font-size: 0.7rem;
            font-weight: 800; text-transform: uppercase; margin-bottom: 15px;
            display: inline-block;
        }
        .materia-name { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 10px; line-height: 1.3; }
        
        .info-row {
            display: flex; align-items: center; gap: 10px;
            color: var(--text-sub); font-size: 0.85rem; margin-bottom: 8px;
            font-weight: 500;
        }
        .info-row i { color: var(--primary-light); width: 18px; }

        /* --- ACCIONES DE TARJETA --- */
        .card-footer {
            padding: 20px 25px 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            background: #fafbfc;
        }
        .btn-action {
            padding: 10px; border-radius: 12px; text-align: center;
            font-weight: 700; font-size: 0.8rem; text-decoration: none;
            transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-asistencia { background: var(--primary); color: white; grid-column: span 2; padding: 12px; }
        .btn-opt { background: white; color: #475569; border: 1.5px solid #e2e8f0; }
        .btn-opt:hover { border-color: var(--primary); color: var(--primary); }

        @media (max-width: 768px) {
            .clases-grid { grid-template-columns: 1fr; }
            .filter-form { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="Logo">
            <span>CECyTE Gestión</span>
        </a>
        <div style="display: flex; gap: 15px;">
            <a href="main.php" class="btn-main btn-secondary"><i class="fas fa-home"></i></a>
            <a href="logout.php" class="btn-main" style="background: #fee2e2; color: #dc2626;"><i class="fas fa-power-off"></i></a>
        </div>
    </nav>

    <div class="container">
        
        <div class="page-header">
            <div class="page-title">
                <h2>Mis Clases</h2>
                <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Catedrático') ?></strong></p>
            </div>
        </div>

        <?php if ($es_admin): ?>
        <div class="filter-card">
            <form method="GET" class="filter-form">
                <select name="maestro_id" class="input-custom">
                    <option value="">Visualizar todos los maestros</option>
                    <?php foreach ($maestros as $m): ?>
                        <option value="<?= $m['id_maestro'] ?>" <?= ($id_maestro_seleccionado == $m['id_maestro']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-main">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </form>
        </div>
        <?php endif; ?>

        <a href="asistencia_qrv2.php" class="btn-main btn-qr-full">
            <i class="fas fa-qrcode"></i> Lector QR Acceso General
        </a>

        <div class="clases-grid">
            <?php if (empty($clases)): ?>
                <div style="grid-column: 1/-1; text-align:center; padding: 80px 20px; background:white; border-radius:20px; box-shadow: var(--shadow);">
                    <i class="fas fa-folder-open" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 20px;"></i>
                    <p style="font-weight: 600; color: var(--text-sub);">No hay clases asignadas para mostrar.</p>
                </div>
            <?php else: ?>
                <?php foreach ($clases as $clase): ?>
                <div class="card-clase">
                    <div class="card-top"></div>
                    <div class="card-body">
                        <span class="badge-grupo">Grupo <?= htmlspecialchars($clase['grupo_nombre']) ?></span>
                        <h3 class="materia-name"><?= htmlspecialchars($clase['materia']) ?></h3>
                        
                        <?php if ($es_admin): ?>
                        <div class="info-row">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Prof. <?= htmlspecialchars($clase['nombre_maestro']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-row">
                            <i class="far fa-calendar-check"></i>
                            <span>Semestre Enero - Junio 2026</span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-info-circle"></i>
                            <span>Registro de asistencia activo</span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="tomar_asistencia.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" class="btn-action btn-asistencia">
                            <i class="fas fa-check-double"></i> Tomar Asistencia
                        </a>
                        <a href="asistencia_qrv2.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" class="btn-action btn-opt" title="QR">
                            <i class="fas fa-qrcode"></i> Escanear
                        </a>
                        <a href="calificaciones.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" class="btn-action btn-opt" title="Notas">
                            <i class="fas fa-star"></i> Notas
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div style="height: 40px;"></div>
</body>
</html>