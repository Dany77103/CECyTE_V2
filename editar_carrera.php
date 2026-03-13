<?php
session_start();

// Verificar permisos
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Verificar que se proporcionó un ID de carrera
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_carreras.php');
    exit();
}

$id_carrera = (int)$_GET['id'];

// Obtener información de la carrera
$sql_carrera = "SELECT * FROM carreras WHERE id_carrera = :id_carrera";
$stmt_carrera = $con->prepare($sql_carrera);
$stmt_carrera->bindValue(':id_carrera', $id_carrera, PDO::PARAM_INT);
$stmt_carrera->execute();
$carrera = $stmt_carrera->fetch(PDO::FETCH_ASSOC);

if (!$carrera) {
    header('Location: gestion_carreras.php');
    exit();
}

// Procesar el formulario de edición
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $clave = trim($_POST['clave'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $duracion_semestres = (int)($_POST['duracion_semestres'] ?? 6);
    $modalidad = trim($_POST['modalidad'] ?? 'Escolarizada');
    $jefe_departamento = trim($_POST['jefe_departamento'] ?? '');
    $correo_departamento = trim($_POST['correo_departamento'] ?? '');
    $telefono_departamento = trim($_POST['telefono_departamento'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones básicas
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = 'El nombre de la carrera es obligatorio.';
    }
    
    if (empty($clave)) {
        $errores[] = 'La clave de la carrera es obligatoria.';
    }
    
    if ($duracion_semestres < 1 || $duracion_semestres > 12) {
        $errores[] = 'La duración debe estar entre 1 y 12 semestres.';
    }
    
    // Verificar si la clave ya existe (excluyendo la actual)
    if (!empty($clave)) {
        $sql_verificar = "SELECT COUNT(*) FROM carreras WHERE clave = :clave AND id_carrera != :id_carrera";
        $stmt_verificar = $con->prepare($sql_verificar);
        $stmt_verificar->bindValue(':clave', $clave);
        $stmt_verificar->bindValue(':id_carrera', $id_carrera);
        $stmt_verificar->execute();
        $existe = $stmt_verificar->fetchColumn();
        
        if ($existe > 0) {
            $errores[] = 'Ya existe una carrera con esta clave.';
        }
    }
    
    // Si no hay errores, actualizar la carrera
    if (empty($errores)) {
        try {
            $sql_update = "UPDATE carreras SET 
                          nombre = :nombre,
                          clave = :clave,
                          descripcion = :descripcion,
                          duracion_semestres = :duracion_semestres,
                          modalidad = :modalidad,
                          jefe_departamento = :jefe_departamento,
                          correo_departamento = :correo_departamento,
                          telefono_departamento = :telefono_departamento,
                          activo = :activo,
                          updated_at = NOW()
                          WHERE id_carrera = :id_carrera";
            
            $stmt_update = $con->prepare($sql_update);
            $stmt_update->bindValue(':nombre', $nombre);
            $stmt_update->bindValue(':clave', $clave);
            $stmt_update->bindValue(':descripcion', $descripcion);
            $stmt_update->bindValue(':duracion_semestres', $duracion_semestres);
            $stmt_update->bindValue(':modalidad', $modalidad);
            $stmt_update->bindValue(':jefe_departamento', $jefe_departamento);
            $stmt_update->bindValue(':correo_departamento', $correo_departamento);
            $stmt_update->bindValue(':telefono_departamento', $telefono_departamento);
            $stmt_update->bindValue(':activo', $activo, PDO::PARAM_INT);
            $stmt_update->bindValue(':id_carrera', $id_carrera, PDO::PARAM_INT);
            
            if ($stmt_update->execute()) {
                $mensaje = 'Carrera actualizada correctamente.';
                $tipo_mensaje = 'success';
                
                // Actualizar los datos de la carrera
                $carrera = array_merge($carrera, [
                    'nombre' => $nombre,
                    'clave' => $clave,
                    'descripcion' => $descripcion,
                    'duracion_semestres' => $duracion_semestres,
                    'modalidad' => $modalidad,
                    'jefe_departamento' => $jefe_departamento,
                    'correo_departamento' => $correo_departamento,
                    'telefono_departamento' => $telefono_departamento,
                    'activo' => $activo
                ]);
            } else {
                $mensaje = 'Error al actualizar la carrera.';
                $tipo_mensaje = 'error';
            }
        } catch (PDOException $e) {
            $mensaje = 'Error en la base de datos: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = implode('<br>', $errores);
        $tipo_mensaje = 'error';
    }
}

// Obtener modalidades existentes para el select
$modalidades_existentes = $con->query("SELECT DISTINCT modalidad FROM carreras WHERE modalidad IS NOT NULL AND modalidad != '' ORDER BY modalidad")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar <?php echo htmlspecialchars($carrera['nombre']); ?> - CECYTE</title>
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
            max-width: 1200px;
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
        .nav-links a:nth-child(4) { background: #1a5330; }
        .nav-links a:nth-child(5) { background: #4caf50; }

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

        .btn-warning {
            background: #8bc34a;
            color: #1a5330;
            border: 1px solid #4caf50;
        }

        .btn-warning:hover {
            background: #7cb342;
            color: white;
            transform: translateY(-3px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: 1px solid #c82333;
        }

        .btn-danger:hover {
            background: #c82333;
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

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1a5330;
            font-size: 15px;
        }

        .form-group label i {
            color: #4caf50;
            margin-right: 8px;
            width: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #8bc34a;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            background-color: #f9fff9;
            color: #1a5330;
        }

        .form-control:focus {
            border-color: #2e7d32;
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #4caf50;
        }

        .checkbox-group label {
            margin-bottom: 0;
            font-weight: 600;
            color: #1a5330;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background-color: #c8e6c9;
            border-color: #4caf50;
            color: #1a5330;
        }

        .alert-error {
            background-color: #ffebee;
            border-color: #ef5350;
            color: #c62828;
        }

        .alert i {
            font-size: 20px;
        }

        .info-box {
            background: #f9fff9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #c8e6c9;
            border-left: 4px solid #2e7d32;
        }

        .info-box h3 {
            color: #1a5330;
            margin-bottom: 10px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-box p {
            color: #2e7d32;
            margin-bottom: 0;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }

        .required {
            color: #dc3545;
        }

        .help-text {
            font-size: 13px;
            color: #8bc34a;
            margin-top: 5px;
            font-style: italic;
        }

        .char-count {
            font-size: 12px;
            color: #8bc34a;
            text-align: right;
            margin-top: 5px;
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

        .badge-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-edit"></i> Editar Carrera</h1>
            <div class="nav-links">
                <a href="main.php"><i class="fas fa-home"></i> Panel Principal</a>
                <a href="gestion_carreras.php"><i class="fas fa-graduation-cap"></i> Carreras</a>
                <a href="ver_carrera.php?id=<?php echo $id_carrera; ?>"><i class="fas fa-eye"></i> Ver Carrera</a>
                <a href="gestion_alumnos.php"><i class="fas fa-users"></i> Alumnos</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesi&oacute;n</a>
            </div>
        </div>
        
        <!-- Información -->
        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> Información</h3>
            <p>Complete el formulario para actualizar la información de la carrera. Los campos marcados con <span class="required">*</span> son obligatorios.</p>
        </div>
        
        <!-- Mensajes -->
        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje === 'success' ? 'success' : 'error'; ?>">
            <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <span><?php echo $mensaje; ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Formulario de Edición -->
        <div class="card">
            <h2><i class="fas fa-graduation-cap"></i> Editar: <?php echo htmlspecialchars($carrera['nombre']); ?></h2>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre"><i class="fas fa-graduation-cap"></i> Nombre de la Carrera <span class="required">*</span></label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($carrera['nombre']); ?>" 
                               required
                               maxlength="100">
                        <div class="char-count" id="nombre-count"><?php echo strlen($carrera['nombre']); ?>/100 caracteres</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="clave"><i class="fas fa-hashtag"></i> Clave <span class="required">*</span></label>
                        <input type="text" 
                               id="clave" 
                               name="clave" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($carrera['clave']); ?>" 
                               required
                               maxlength="20">
                        <div class="char-count" id="clave-count"><?php echo strlen($carrera['clave']); ?>/20 caracteres</div>
                        <div class="help-text">Clave única para identificar la carrera (ej: ING-SIS, LIC-ADM)</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion"><i class="fas fa-align-left"></i> Descripción</label>
                    <textarea id="descripcion" 
                              name="descripcion" 
                              class="form-control" 
                              rows="4"
                              maxlength="500"><?php echo htmlspecialchars($carrera['descripcion'] ?? ''); ?></textarea>
                    <div class="char-count" id="descripcion-count"><?php echo strlen($carrera['descripcion'] ?? ''); ?>/500 caracteres</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="duracion_semestres"><i class="fas fa-clock"></i> Duración (semestres)</label>
                        <select id="duracion_semestres" name="duracion_semestres" class="form-control" required>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" 
                                <?php echo ($carrera['duracion_semestres'] ?? 6) == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?> semestre<?php echo $i != 1 ? 's' : ''; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalidad"><i class="fas fa-university"></i> Modalidad</label>
                        <select id="modalidad" name="modalidad" class="form-control" required>
                            <option value="">Seleccionar modalidad...</option>
                            <option value="Escolarizada" <?php echo ($carrera['modalidad'] ?? '') == 'Escolarizada' ? 'selected' : ''; ?>>Escolarizada</option>
                            <option value="No Escolarizada" <?php echo ($carrera['modalidad'] ?? '') == 'No Escolarizada' ? 'selected' : ''; ?>>No Escolarizada</option>
                            <option value="Mixta" <?php echo ($carrera['modalidad'] ?? '') == 'Mixta' ? 'selected' : ''; ?>>Mixta</option>
                            <option value="Virtual" <?php echo ($carrera['modalidad'] ?? '') == 'Virtual' ? 'selected' : ''; ?>>Virtual</option>
                            <?php foreach ($modalidades_existentes as $modalidad): ?>
                                <?php if (!in_array($modalidad['modalidad'], ['Escolarizada', 'No Escolarizada', 'Mixta', 'Virtual'])): ?>
                                <option value="<?php echo htmlspecialchars($modalidad['modalidad']); ?>"
                                    <?php echo ($carrera['modalidad'] ?? '') == $modalidad['modalidad'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($modalidad['modalidad']); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text">O puede escribir una modalidad personalizada</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="jefe_departamento"><i class="fas fa-user-tie"></i> Jefe de Departamento</label>
                        <input type="text" 
                               id="jefe_departamento" 
                               name="jefe_departamento" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($carrera['jefe_departamento'] ?? ''); ?>"
                               maxlength="100">
                        <div class="char-count" id="jefe-count"><?php echo strlen($carrera['jefe_departamento'] ?? ''); ?>/100 caracteres</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="correo_departamento"><i class="fas fa-envelope"></i> Correo del Departamento</label>
                        <input type="email" 
                               id="correo_departamento" 
                               name="correo_departamento" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($carrera['correo_departamento'] ?? ''); ?>"
                               maxlength="100">
                        <div class="char-count" id="correo-count"><?php echo strlen($carrera['correo_departamento'] ?? ''); ?>/100 caracteres</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono_departamento"><i class="fas fa-phone"></i> Teléfono del Departamento</label>
                        <input type="text" 
                               id="telefono_departamento" 
                               name="telefono_departamento" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($carrera['telefono_departamento'] ?? ''); ?>"
                               maxlength="20"
                               pattern="[0-9+\-\s\(\)]{7,20}"
                               title="Formato: números, espacios, paréntesis, guiones y signo +">
                        <div class="help-text">Ej: +52 (55) 1234-5678</div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-circle"></i> Estatus de la Carrera</label>
                        <div class="checkbox-group">
                            <input type="checkbox" 
                                   id="activo" 
                                   name="activo" 
                                   value="1"
                                   <?php echo $carrera['activo'] ? 'checked' : ''; ?>>
                            <label for="activo">Carrera Activa</label>
                            <?php if ($carrera['activo']): ?>
                                <span class="badge badge-success">Activa</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Inactiva</span>
                            <?php endif; ?>
                        </div>
                        <div class="help-text">Desmarque para desactivar la carrera temporalmente</div>
                    </div>
                </div>
                
                <!-- Botones del Formulario -->
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="ver_carrera.php?id=<?php echo $id_carrera; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <a href="gestion_carreras.php" class="btn btn-volver">
                        <i class="fas fa-arrow-left"></i> Volver a Carreras
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Información Adicional -->
        <div class="card">
            <h2><i class="fas fa-history"></i> Información Adicional</h2>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar-plus"></i> Fecha de Creación</label>
                    <input type="text" 
                           class="form-control" 
                           value="<?php echo date('d/m/Y H:i:s', strtotime($carrera['created_at'])); ?>" 
                           readonly
                           style="background-color: #e8f5e9;">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar-check"></i> Última Actualización</label>
                    <input type="text" 
                           class="form-control" 
                           value="<?php echo date('d/m/Y H:i:s', strtotime($carrera['updated_at'])); ?>" 
                           readonly
                           style="background-color: #e8f5e9;">
                </div>
            </div>
        </div>
        
        <!-- Botones de Acción Adicionales -->
        <div class="action-buttons">
            <?php if ($carrera['activo']): ?>
                <a href="desactivar_carrera.php?id=<?php echo $id_carrera; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('¿Está seguro de desactivar esta carrera?')">
                    <i class="fas fa-ban"></i> Desactivar Carrera
                </a>
            <?php else: ?>
                <a href="activar_carrera.php?id=<?php echo $id_carrera; ?>" 
                   class="btn btn-success"
                   onclick="return confirm('¿Está seguro de activar esta carrera?')">
                    <i class="fas fa-check"></i> Activar Carrera
                </a>
            <?php endif; ?>
            <a href="grupos_carrera.php?id=<?php echo $id_carrera; ?>" class="btn btn-warning">
                <i class="fas fa-users"></i> Gestionar Grupos
            </a>
            <a href="plan_estudios.php?id=<?php echo $id_carrera; ?>" class="btn btn-info">
                <i class="fas fa-book"></i> Plan de Estudios
            </a>
            <a href="reporte_carrera.php?id=<?php echo $id_carrera; ?>" class="btn btn-secondary">
                <i class="fas fa-chart-pie"></i> Generar Reporte
            </a>
        </div>
    </div>
    
    <script>
        // Contador de caracteres
        document.getElementById('nombre').addEventListener('input', function() {
            document.getElementById('nombre-count').textContent = this.value.length + '/100 caracteres';
        });
        
        document.getElementById('clave').addEventListener('input', function() {
            document.getElementById('clave-count').textContent = this.value.length + '/20 caracteres';
        });
        
        document.getElementById('descripcion').addEventListener('input', function() {
            document.getElementById('descripcion-count').textContent = this.value.length + '/500 caracteres';
        });
        
        document.getElementById('jefe_departamento').addEventListener('input', function() {
            document.getElementById('jefe-count').textContent = this.value.length + '/100 caracteres';
        });
        
        document.getElementById('correo_departamento').addEventListener('input', function() {
            document.getElementById('correo-count').textContent = this.value.length + '/100 caracteres';
        });
        
        // Validación del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const clave = document.getElementById('clave').value.trim();
            
            if (!nombre) {
                e.preventDefault();
                alert('El nombre de la carrera es obligatorio.');
                document.getElementById('nombre').focus();
                return false;
            }
            
            if (!clave) {
                e.preventDefault();
                alert('La clave de la carrera es obligatoria.');
                document.getElementById('clave').focus();
                return false;
            }
            
            // Confirmar antes de enviar
            if (!confirm('¿Está seguro de guardar los cambios en la carrera?')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Permitir modalidad personalizada
        const modalidadSelect = document.getElementById('modalidad');
        modalidadSelect.addEventListener('change', function() {
            if (this.value === '') {
                // Permitir escribir si selecciona "Seleccionar modalidad..."
                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'modalidad';
                input.id = 'modalidad_input';
                input.className = 'form-control';
                input.placeholder = 'Escriba la modalidad...';
                input.maxLength = 50;
                input.required = true;
                
                this.parentNode.replaceChild(input, this);
                input.focus();
            }
        });
    </script>
</body>
</html>