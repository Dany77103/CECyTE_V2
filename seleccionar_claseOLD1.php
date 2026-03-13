<?php
session_start();

// PERMITIR TANTO MAESTROS COMO USUARIOS DEL SISTEMA
if (!isset($_SESSION['loggedin']) || ($_SESSION['tipo_usuario'] !== 'maestro' && $_SESSION['tipo_usuario'] !== 'sistema'   && $_SESSION['tipo_usuario'] !== 'admin')) {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Si es maestro, obtener solo sus clases
if ($_SESSION['tipo_usuario'] === 'maestro') {
    $id_maestro = $_SESSION['user_id'];
    $sql = "SELECT DISTINCT h.id_materia, h.id_grupo, m.materia, g.nombre as grupo_nombre, 
                   CONCAT(ma.nombre, ' ', ma.apellido_paterno, ' ', ma.apellido_materno) as nombre_maestro
            FROM horarios_maestros h
            JOIN materias m ON h.id_materia = m.id_materia
            JOIN grupos g ON h.id_grupo = g.id_grupo
            JOIN maestros ma ON h.id_maestro = ma.id_maestro
            WHERE h.id_maestro = :id_maestro AND h.estatus = 'Activo'
            ORDER BY m.materia, g.nombre";
    $stmt = $con->prepare($sql);
    $stmt->execute(['id_maestro' => $id_maestro]);
} else {
    // Para usuarios del sistema, mostrar todas las clases
    $sql = "SELECT DISTINCT h.id_materia, h.id_grupo, m.materia, g.nombre as grupo_nombre,
                   CONCAT(ma.nombre, ' ', ma.apellido_paterno, ' ', ma.apellido_materno) as nombre_maestro
            FROM horarios_maestros h
            JOIN materias m ON h.id_materia = m.id_materia
            JOIN grupos g ON h.id_grupo = g.id_grupo
            JOIN maestros ma ON h.id_maestro = ma.id_maestro
            WHERE h.estatus = 'Activo'
            ORDER BY m.materia, g.nombre";
    $stmt = $con->prepare($sql);
    $stmt->execute();
}

$clases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Clase - CECYTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .welcome-card h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #666;
            font-size: 16px;
        }
        
        .clases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .clase-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .clase-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .clase-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .clase-info {
            margin-bottom: 15px;
        }
        
        .clase-info p {
            margin-bottom: 5px;
            color: #666;
        }
        
        .clase-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-action {
            display: block;
            text-align: center;
            padding: 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-action i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #48bb78;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #38a169;
        }
        
        .btn-tertiary {
            background: #ed8936;
            color: white;
        }
        
        .btn-tertiary:hover {
            background: #dd6b20;
        }
        
        .no-clases {
            text-align: center;
            padding: 50px;
            color: #666;
            background: white;
            border-radius: 10px;
        }
        
        @media (max-width: 768px) {
            .clases-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CECYTE - Sistema de Gestión Escolar</h1>
        <div class="user-info">
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['username']); ?></span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <h2><?php echo $_SESSION['tipo_usuario'] === 'maestro' ? 'Panel del Maestro' : 'Gestión de Asistencia y Calificaciones'; ?></h2>
            <p>
                <?php 
                if ($_SESSION['tipo_usuario'] === 'maestro') {
                    echo 'Selecciona una clase para gestionar asistencia y calificaciones';
                } else {
                    echo 'Selecciona una clase para gestionar asistencia y calificaciones (modo administrador)';
                }
                ?>
            </p>
        </div>
        
        <?php if (empty($clases)): ?>
            <div class="no-clases">
                <h3>No hay clases disponibles</h3>
                <p>
                    <?php 
                    if ($_SESSION['tipo_usuario'] === 'maestro') {
                        echo 'No tienes clases asignadas. Contacta con el administrador del sistema';
                    } else {
                        echo 'No hay clases activas en el sistema';
                    }
                    ?>
                </p>
            </div>
        <?php else: ?>
            <div class="clases-grid">
                <?php foreach ($clases as $clase): ?>
                <div class="clase-card">
                    <h3><?php echo htmlspecialchars($clase['materia']); ?></h3>
                    <div class="clase-info">
                        <p><strong>Grupo:</strong> <?php echo htmlspecialchars($clase['grupo_nombre']); ?></p>
                        <?php if ($_SESSION['tipo_usuario'] === 'sistema'): ?>
                        <p><strong>Maestro:</strong> <?php echo htmlspecialchars($clase['nombre_maestro']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="clase-actions">
                        <a href="tomar_asistencia.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" 
                           class="btn-action btn-primary">
                            <i class="fas fa-clipboard-check"></i> Tomar Asistencia
                        </a>
                        
                        <a href="calificaciones.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" 
                           class="btn-action btn-secondary">
                            <i class="fas fa-chart-bar"></i> Calificaciones
                        </a>
                        
                        <a href="generar_qr.php?materia=<?= $clase['id_materia'] ?>&grupo=<?= $clase['id_grupo'] ?>" 
                           class="btn-action btn-tertiary">
                            <i class="fas fa-qrcode"></i> Generar QR
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>