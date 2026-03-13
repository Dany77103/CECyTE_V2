<?php
session_start();

// Verificar permisos
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Verificar que hay resultados en sesión
if (!isset($_SESSION['agregar_alumnos_resultado'])) {
    header('Location: gestion_carreras.php');
    exit();
}

$resultado = $_SESSION['agregar_alumnos_resultado'];
$id_grupo = $resultado['grupo_id'];

// Obtener información actualizada del grupo
require_once 'conexion.php';
$sql_grupo = "SELECT g.*, c.nombre as carrera_nombre 
              FROM grupos g 
              LEFT JOIN carreras c ON g.id_carrera = c.id_carrera 
              WHERE g.id_grupo = :id_grupo";
$stmt_grupo = $con->prepare($sql_grupo);
$stmt_grupo->bindValue(':id_grupo', $id_grupo, PDO::PARAM_INT);
$stmt_grupo->execute();
$grupo = $stmt_grupo->fetch(PDO::FETCH_ASSOC);

// Limpiar la sesión después de mostrar
unset($_SESSION['agregar_alumnos_resultado']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación - Alumnos Agregados - CECYTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #1a5330 0%, #2e7d32 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header h1 i {
            color: #c8e6c9;
        }

        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
        }

        .nav-links a:nth-child(1) { background: #2e7d32; }
        .nav-links a:nth-child(2) { background: #4caf50; }
        .nav-links a:nth-child(3) { background: #8bc34a; }

        .nav-links a:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            filter: brightness(110%);
        }

        .card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #4caf50;
        }

        .card h2 {
            color: #1a5330;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #c8e6c9;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h2 i {
            color: #2e7d32;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 15px;
        }

        .btn-primary {
            background: linear-gradient(to right, #2e7d32, #4caf50);
            color: white;
            border: 1px solid #1a5330;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.4);
            background: linear-gradient(to right, #4caf50, #2e7d32);
        }

        .btn-secondary {
            background: #8bc34a;
            color: #1a5330;
            border: 1px solid #4caf50;
        }

        .btn-secondary:hover {
            background: #4caf50;
            color: white;
            transform: translateY(-3px);
        }

        .btn-success {
            background: #2e7d32;
            color: white;
            border: 1px solid #1a5330;
        }

        .btn-success:hover {
            background: #1a5330;
            transform: translateY(-3px);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-volver {
            background: #8bc34a;
            color: #1a5330;
            border: 1px solid #4caf50;
        }

        .btn-volver:hover {
            background: #4caf50;
            color: white;
        }

        .success-message {
            background: #c8e6c9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #4caf50;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .success-icon {
            font-size: 40px;
            color: #2e7d32;
        }

        .success-text h3 {
            color: #1a5330;
            margin-bottom: 5px;
            font-size: 20px;
        }

        .success-text p {
            color: #2e7d32;
            margin: 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-section {
            background: #f9fff9;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #c8e6c9;
        }

        .info-section h3 {
            color: #1a5330;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c8e6c9;
            font-size: 18px;
        }

        .info-row {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #c8e6c9;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #1a5330;
            min-width: 150px;
        }

        .info-value {
            color: #2e7d32;
        }

        .alumnos-lista {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            padding: 15px;
            background: #f9fff9;
        }

        .alumno-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #c8e6c9;
            transition: background-color 0.3s;
        }

        .alumno-item:hover {
            background-color: #c8e6c9;
        }

        .alumno-item:last-child {
            border-bottom: none;
        }

        .alumno-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alumno-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        .alumno-details {
            flex: 1;
        }

        .alumno-nombre {
            font-weight: 600;
            color: #1a5330;
            margin-bottom: 3px;
        }

        .alumno-matricula {
            font-size: 13px;
            color: #2e7d32;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .badge-success {
            background: #c8e6c9;
            color: #1a5330;
            border: 1px solid #4caf50;
        }

        .capacidad-box {
            background: #f9fff9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #c8e6c9;
            border-left: 4px solid #2e7d32;
        }

        .capacidad-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .capacidad-header h3 {
            color: #1a5330;
            margin: 0;
            font-size: 18px;
        }

        .capacidad-numeros {
            font-weight: 700;
            color: #2e7d32;
            font-size: 20px;
        }

        .capacidad-bar {
            background: #c8e6c9;
            height: 15px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .capacidad-fill {
            height: 100%;
            background: linear-gradient(to right, #4caf50, #2e7d32);
            border-radius: 8px;
            transition: width 1s ease;
        }

        .capacidad-labels {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #1a5330;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-label {
                min-width: auto;
            }
            
            .alumno-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .capacidad-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-check-circle"></i> Confirmación de Operación</h1>
            <div class="nav-links">
                <a href="main.php"><i class="fas fa-home"></i> Panel Principal</a>
                <a href="ver_grupo.php?id=<?php echo $id_grupo; ?>"><i class="fas fa-eye"></i> Ver Grupo</a>
                <a href="alumnos_grupo.php?id=<?php echo $id_grupo; ?>"><i class="fas fa-users"></i> Alumnos</a>
            </div>
        </div>
        
        <!-- Mensaje de Éxito -->
        <div class="success-message">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="success-text">
                <h3>¡Operación Exitosa!</h3>
                <p><?php echo $resultado['mensaje']; ?></p>
            </div>
        </div>
        
        <!-- Información del Grupo -->
        <div class="card">
            <h2><i class="fas fa-info-circle"></i> Información del Grupo</h2>
            
            <div class="info-grid">
                <div class="info-section">
                    <h3><i class="fas fa-users"></i> Grupo</h3>
                    <div class="info-row">
                        <div class="info-label">Nombre:</div>
                        <div class="info-value"><?php echo htmlspecialchars($resultado['grupo_nombre']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Carrera:</div>
                        <div class="info-value"><?php echo htmlspecialchars($resultado['carrera_nombre']); ?></div>
                    </div>
                    <?php if ($grupo): ?>
                    <div class="info-row">
                        <div class="info-label">Semestre:</div>
                        <div class="info-value"><?php echo $grupo['semestre']; ?>° Semestre</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Turno:</div>
                        <div class="info-value"><?php echo htmlspecialchars($grupo['turno']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-chart-bar"></i> Estadísticas</h3>
                    <div class="info-row">
                        <div class="info-label">Alumnos agregados:</div>
                        <div class="info-value">
                            <span style="font-weight: 700; color: #1a5330; font-size: 18px;">
                                <?php echo $resultado['total_agregados']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Capacidad anterior:</div>
                        <div class="info-value">
                            <?php echo $resultado['capacidad_nueva'] - $resultado['total_agregados']; ?> / <?php echo $resultado['capacidad_maxima']; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Capacidad actual:</div>
                        <div class="info-value">
                            <?php echo $resultado['capacidad_nueva']; ?> / <?php echo $resultado['capacidad_maxima']; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Cupos disponibles:</div>
                        <div class="info-value">
                            <?php echo $resultado['capacidad_maxima'] - $resultado['capacidad_nueva']; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Barra de Capacidad -->
            <div class="capacidad-box">
                <div class="capacidad-header">
                    <h3>Capacidad del Grupo</h3>
                    <div class="capacidad-numeros">
                        <?php echo $resultado['capacidad_nueva']; ?> / <?php echo $resultado['capacidad_maxima']; ?>
                    </div>
                </div>
                <div class="capacidad-bar">
                    <?php 
                    $porcentaje = $resultado['capacidad_maxima'] > 0 ? 
                        round(($resultado['capacidad_nueva'] / $resultado['capacidad_maxima']) * 100) : 0;
                    $color = $porcentaje >= 90 ? 'linear-gradient(to right, #dc3545, #c82333)' : 
                            ($porcentaje >= 75 ? 'linear-gradient(to right, #ffc107, #e0a800)' : 
                            'linear-gradient(to right, #4caf50, #2e7d32)');
                    ?>
                    <div class="capacidad-fill" 
                         style="width: <?php echo min($porcentaje, 100); ?>%; background: <?php echo $color; ?>;">
                    </div>
                </div>
                <div class="capacidad-labels">
                    <span>Vacío</span>
                    <span><?php echo $porcentaje; ?>% de capacidad</span>
                    <span>Lleno</span>
                </div>
            </div>
        </div>
        
        <!-- Lista de Alumnos Agregados -->
        <div class="card">
            <h2><i class="fas fa-list"></i> Alumnos Agregados al Grupo</h2>
            
            <div class="alumnos-lista">
                <?php foreach ($resultado['alumnos_agregados'] as $index => $alumno): ?>
                <div class="alumno-item">
                    <div class="alumno-info">
                        <div class="alumno-avatar">
                            <?php 
                            $iniciales = strtoupper(
                                substr($alumno['nombre'], 0, 1) . 
                                substr($alumno['apellido_paterno'], 0, 1)
								
                            );
                            echo $iniciales;
                            ?>
                        </div>
                        <div class="alumno-details">
                            <div class="alumno-nombre">
                                <?php echo $index + 1; ?>. 
                                <?php echo htmlspecialchars($alumno['apellido_paterno'] . '  ' . $alumno['nombre']); ?>
                            </div>
                            <div class="alumno-matricula">
                                <i class="fas fa-id-card"></i> 
                                <?php echo htmlspecialchars($alumno['matricula']); ?>
                            </div>
                        </div>
                    </div>
                    <span class="badge badge-success">Agregado</span>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($resultado['alumnos_agregados'])): ?>
                <div style="text-align: center; padding: 30px; color: #8bc34a;">
                    <i class="fas fa-info-circle" style="font-size: 40px; margin-bottom: 15px;"></i>
                    <p>No se agregaron alumnos al grupo.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Botones de Acción -->
        <div class="action-buttons">
            <a href="alumnos_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-primary">
                <i class="fas fa-users"></i> Ver Alumnos del Grupo
            </a>
            <a href="ver_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-success">
                <i class="fas fa-eye"></i> Ver Detalles del Grupo
            </a>
            <a href="grupos_carrera.php?id=<?php echo $grupo['id_carrera']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Grupos
            </a>
            <a href="gestion_alumnos.php?grupo=<?php echo $id_grupo; ?>" class="btn btn-volver">
                <i class="fas fa-user-plus"></i> Agregar Más Alumnos
            </a>
            <a href="exportar_alumnos.php?grupo=<?php echo $id_grupo; ?>&nuevos=1" class="btn btn-secondary">
                <i class="fas fa-file-export"></i> Exportar Lista
            </a>
        </div>
    </div>
    
    <script>
        // Animación de la barra de capacidad
        document.addEventListener('DOMContentLoaded', function() {
            const capacidadFill = document.querySelector('.capacidad-fill');
            const porcentaje = capacidadFill.style.width;
            capacidadFill.style.width = '0%';
            
            setTimeout(() => {
                capacidadFill.style.width = porcentaje;
            }, 300);
        });
        
        // Mostrar mensaje de éxito con animación
        const successMessage = document.querySelector('.success-message');
        successMessage.style.opacity = '0';
        successMessage.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            successMessage.style.transition = 'all 0.5s ease';
            successMessage.style.opacity = '1';
            successMessage.style.transform = 'translateY(0)';
        }, 100);
    </script>
</body>
</html>