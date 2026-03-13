<?php
session_start();

// Verificar permisos
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Verificar que se recibió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gestion_carreras.php');
    exit();
}

// Obtener y validar datos del formulario
$id_grupo = isset($_POST['id_grupo']) ? (int)$_POST['id_grupo'] : 0;
$alumnos_ids = isset($_POST['alumnos']) ? $_POST['alumnos'] : [];

// Validar que se recibió un grupo válido
if ($id_grupo <= 0) {
    $_SESSION['error'] = 'ID de grupo inválido.';
    header('Location: alumnos_grupo.php');
    exit();
}

// Validar que se seleccionaron alumnos
if (empty($alumnos_ids) || !is_array($alumnos_ids)) {
    $_SESSION['error'] = 'No se seleccionaron alumnos para agregar.';
    header("Location: alumnos_grupo.php?id=$id_grupo");
    exit();
}

// Obtener información del grupo para validaciones
$sql_grupo = "SELECT g.*, 
                     c.nombre as carrera_nombre,
                     COUNT(DISTINCT a.id_alumno) as total_alumnos
              FROM grupos g 
              LEFT JOIN carreras c ON g.id_carrera = c.id_carrera 
              LEFT JOIN alumnos a ON g.id_grupo = a.id_grupo AND a.activo = 'Activo'
              WHERE g.id_grupo = :id_grupo";
$stmt_grupo = $con->prepare($sql_grupo);
$stmt_grupo->bindValue(':id_grupo', $id_grupo, PDO::PARAM_INT);
$stmt_grupo->execute();
$grupo = $stmt_grupo->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    $_SESSION['error'] = 'El grupo no existe.';
    header('Location: gestion_carreras.php');
    exit();
}

// Verificar que el grupo está activo
if (!$grupo['activo']) {
    $_SESSION['error'] = 'No se pueden agregar alumnos a un grupo inactivo.';
    header("Location: alumnos_grupo.php?id=$id_grupo");
    exit();
}

// Verificar capacidad del grupo
$alumnos_seleccionados = count($alumnos_ids);
$capacidad_disponible = $grupo['capacidad_maxima'] - ($grupo['total_alumnos'] ?? 0);

if ($alumnos_seleccionados > $capacidad_disponible) {
    $_SESSION['error'] = "El grupo tiene capacidad para $capacidad_disponible alumnos más. Seleccionaste $alumnos_seleccionados.";
    header("Location: alumnos_grupo.php?id=$id_grupo");
    exit();
}

// Validar cada ID de alumno
$alumnos_ids_validos = [];
foreach ($alumnos_ids as $alumno_id) {
    $alumno_id = (int)$alumno_id;
    if ($alumno_id > 0) {
        $alumnos_ids_validos[] = $alumno_id;
    }
}

if (empty($alumnos_ids_validos)) {
    $_SESSION['error'] = 'No se recibieron IDs de alumnos válidos.';
    header("Location: alumnos_grupo.php?id=$id_grupo");
    exit();
}

// Verificar que los alumnos existan y no tengan grupo asignado
$placeholders = str_repeat('?,', count($alumnos_ids_validos) - 1) . '?';
$sql_verificar = "SELECT id_alumno, nombre, apellido_paterno, matricula 
                  FROM alumnos 
                  WHERE id_alumno IN ($placeholders) 
                  AND (id_grupo IS NULL OR id_grupo = 0 OR id_grupo = '') 
                  AND activo = 'Activo'";
$stmt_verificar = $con->prepare($sql_verificar);
$stmt_verificar->execute($alumnos_ids_validos);
$alumnos_validos = $stmt_verificar->fetchAll(PDO::FETCH_ASSOC);

if (count($alumnos_validos) !== count($alumnos_ids_validos)) {
    $_SESSION['error'] = 'Algunos alumnos ya tienen grupo asignado o no existen.';
    header("Location: alumnos_grupo.php?id=$id_grupo");
    exit();
}

// Iniciar transacción
$con->beginTransaction();

try {
    // Actualizar cada alumno con el nuevo grupo
    $sql_actualizar = "UPDATE alumnos 
                       SET id_grupo = :id_grupo, 
                           updated_at = NOW() 
                       WHERE id_alumno = :id_alumno";
    $stmt_actualizar = $con->prepare($sql_actualizar);
    
    $alumnos_actualizados = [];
    $alumnos_error = [];
    
    foreach ($alumnos_validos as $alumno) {
        $stmt_actualizar->bindValue(':id_grupo', $id_grupo, PDO::PARAM_INT);
        $stmt_actualizar->bindValue(':id_alumno', $alumno['id_alumno'], PDO::PARAM_INT);
        
        if ($stmt_actualizar->execute()) {
            $alumnos_actualizados[] = $alumno;
        } else {
            $alumnos_error[] = $alumno;
        }
    }
    
    // Si hay errores, revertir transacción
    if (!empty($alumnos_error)) {
        $con->rollBack();
        $_SESSION['error'] = 'Error al agregar algunos alumnos al grupo.';
        header("Location: alumnos_grupo.php?id=$id_grupo");
        exit();
    }
    
    // Confirmar transacción
    $con->commit();
    
    // Guardar mensaje de éxito con detalles
    $mensaje_exito = "Se agregaron " . count($alumnos_actualizados) . " alumnos al grupo " . htmlspecialchars($grupo['nombre']) . ".";
    
    // Crear lista de alumnos agregados
    $lista_alumnos = [];
    foreach ($alumnos_actualizados as $alumno) {
        $lista_alumnos[] = $alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'] . ' ' . $alumno['nombre'] . ' (' . $alumno['matricula'] . ')';
    }
    
    // Guardar en sesión para mostrar en la página de confirmación
    $_SESSION['agregar_alumnos_resultado'] = [
        'success' => true,
        'mensaje' => $mensaje_exito,
        'grupo_id' => $id_grupo,
        'grupo_nombre' => $grupo['nombre'],
        'carrera_nombre' => $grupo['carrera_nombre'],
        'alumnos_agregados' => $alumnos_actualizados,
        'lista_alumnos' => $lista_alumnos,
        'total_agregados' => count($alumnos_actualizados),
        'capacidad_nueva' => ($grupo['total_alumnos'] ?? 0) + count($alumnos_actualizados),
        'capacidad_maxima' => $grupo['capacidad_maxima']
    ];
    
    // Redirigir a página de confirmación
    header("Location: confirmacion_agregar_alumnos.php?grupo=$id_grupo");
    exit();
    
} catch (Exception $e) {
    $con->rollBack();
    $_SESSION['error'] = 'Error en la transacción: ' . $e->getMessage();
    header("Location: alumnos_grupo.php?id=$id_grupo");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesando - Agregar Alumnos al Grupo - CECYTE</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .loading-container {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 15px;
            padding: 50px 40px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            border-left: 5px solid #4caf50;
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .loading-icon {
            font-size: 60px;
            color: #4caf50;
            margin-bottom: 25px;
            animation: spin 1.5s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-title {
            color: #1a5330;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .loading-message {
            color: #2e7d32;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .progress-container {
            background: #c8e6c9;
            border-radius: 10px;
            height: 12px;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(to right, #4caf50, #2e7d32);
            width: 0%;
            animation: progress 2s ease-in-out forwards;
        }

        @keyframes progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }

        .details-box {
            background: #f9fff9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #c8e6c9;
            text-align: left;
        }

        .details-box h3 {
            color: #1a5330;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .details-box h3 i {
            color: #4caf50;
        }

        .detail-item {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #c8e6c9;
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #1a5330;
            min-width: 150px;
        }

        .detail-value {
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
            margin: 5px;
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

        .error-container {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            border-left: 5px solid #dc3545;
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .error-icon {
            font-size: 60px;
            color: #dc3545;
            margin-bottom: 25px;
        }

        .error-title {
            color: #c62828;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .error-message {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .loading-container,
            .error-container {
                padding: 30px 20px;
            }
            
            .loading-title,
            .error-title {
                font-size: 22px;
            }
            
            .detail-item {
                flex-direction: column;
                gap: 5px;
            }
            
            .detail-label {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h1 class="error-title">Error</h1>
            <p class="error-message"><?php echo htmlspecialchars($_SESSION['error']); ?></p>
            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <a href="alumnos_grupo.php?id=<?php echo $id_grupo; ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver al Grupo
                </a>
                <a href="gestion_carreras.php" class="btn btn-secondary">
                    <i class="fas fa-graduation-cap"></i> Ver Carreras
                </a>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php else: ?>
        <div class="loading-container">
            <div class="loading-icon">
                <i class="fas fa-cog"></i>
            </div>
            <h1 class="loading-title">Agregando Alumnos al Grupo</h1>
            <p class="loading-message">Procesando la solicitud. Por favor, espere un momento...</p>
            
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
            
            <div class="details-box">
                <h3><i class="fas fa-info-circle"></i> Detalles de la Operación</h3>
                <div class="detail-item">
                    <div class="detail-label">Grupo:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($grupo['nombre'] ?? 'N/A'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Carrera:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($grupo['carrera_nombre'] ?? 'N/A'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Alumnos a agregar:</div>
                    <div class="detail-value"><?php echo count($alumnos_ids_validos); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Capacidad disponible:</div>
                    <div class="detail-value">
                        <?php 
                        $actual = $grupo['total_alumnos'] ?? 0;
                        $maxima = $grupo['capacidad_maxima'];
                        $disponible = $maxima - $actual;
                        echo "$actual / $maxima ($disponible disponibles)";
                        ?>
                    </div>
                </div>
            </div>
            
            <p style="color: #8bc34a; font-size: 14px; margin-top: 20px;">
                <i class="fas fa-sync-alt"></i> Redireccionando automáticamente...
            </p>
        </div>
        
        <script>
            // Redirección automática después de 3 segundos (como respaldo)
            setTimeout(function() {
                window.location.href = "confirmacion_agregar_alumnos.php?grupo=<?php echo $id_grupo; ?>";
            }, 3000);
        </script>
    <?php endif; ?>
</body>
</html>