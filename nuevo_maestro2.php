<?php
// nuevo_maestro.php
session_start();
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtener datos para formularios (selects)
try {
    // Géneros
    $sql_generos = "SELECT * FROM generos ORDER BY genero";
    $generos = $con->query($sql_generos)->fetchAll(PDO::FETCH_ASSOC);
    
    // Nacionalidades
    $sql_nacionalidades = "SELECT * FROM nacionalidades ORDER BY nacionalidad";
    $nacionalidades = $con->query($sql_nacionalidades)->fetchAll(PDO::FETCH_ASSOC);
    
    // Estados
    $sql_estados = "SELECT * FROM estados ORDER BY estado";
    $estados = $con->query($sql_estados)->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener datos para el formulario: " . $e->getMessage());
}

// Procesar formulario
$mensajes = [];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errores[] = "Token de seguridad inválido. Por favor, recarga la página.";
    } else {
        // Recoger y validar datos
        $numEmpleado = trim($_POST['numEmpleado'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
        $apellido_materno = trim($_POST['apellido_materno'] ?? '');
        $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $id_genero = $_POST['id_genero'] ?? '';
        $rfc = trim($_POST['rfc'] ?? '');
        $curp = trim($_POST['curp'] ?? '');
        $id_nacionalidad = $_POST['id_nacionalidad'] ?? '';
        $id_estado = $_POST['id_estado'] ?? '';
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono_celular = trim($_POST['telefono_celular'] ?? '');
        $telefono_emergencia = trim($_POST['telefono_emergencia'] ?? '');
        $correo_institucional = trim($_POST['correo_institucional'] ?? '');
        $correo_personal = trim($_POST['correo_personal'] ?? '');        
        $estado_civil = trim($_POST['estado_civil'] ?? '');
		// Después de $estado_civil = trim($_POST['estado_civil'] ?? '');
			$especialidad = trim($_POST['especialidad'] ?? '');
			$titulo = trim($_POST['titulo'] ?? '');
			$observaciones = trim($_POST['observaciones'] ?? '');
			$activo = $_POST['activo'] ?? 'Activo'; // Valor por defecto

        // Obtener nombres de las opciones seleccionadas
        $genero_nombre = '';
        $nacionalidad_nombre = '';
        $estado_nombre = '';
        
        foreach ($generos as $genero) {
            if ($genero['id_genero'] == $id_genero) {
                $genero_nombre = $genero['genero'];
                break;
            }
        }
        
        foreach ($nacionalidades as $nacionalidad) {
            if ($nacionalidad['id_nacionalidad'] == $id_nacionalidad) {
                $nacionalidad_nombre = $nacionalidad['nacionalidad'];
                break;
            }
        }
        
        foreach ($estados as $estado) {
            if ($estado['id_estado'] == $id_estado) {
                $estado_nombre = $estado['estado'];
                break;
            }
        }

        // Validaciones básicas
        if (empty($numEmpleado)) $errores[] = "El número de empleado es requerido";
        if (empty($nombre)) $errores[] = "El nombre es requerido";
        if (empty($apellido_paterno)) $errores[] = "El apellido paterno es requerido";
        if (empty($fecha_nacimiento)) $errores[] = "La fecha de nacimiento es requerida";
        if (empty($correo_institucional)) $errores[] = "El correo institucional es requerido";
        
        // Validar correo institucional
        if (!empty($correo_institucional) && !filter_var($correo_institucional, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo institucional no es válido";
        }
        
        // Validar correo personal si se proporciona
        if (!empty($correo_personal) && !filter_var($correo_personal, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo personal no es válido";
        }
        
        // Validar RFC si se proporciona
        if (!empty($rfc) && !preg_match('/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/', strtoupper($rfc))) {
            $errores[] = "El RFC no tiene un formato válido";
        }
        
        // Validar CURP si se proporciona
        if (!empty($curp) && !preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$/', strtoupper($curp))) {
            $errores[] = "El CURP no tiene un formato válido";
        }

        // Si se presionó el botón de previsualización
        if (isset($_POST['preview'])) {
            // Guardar datos en sesión para mostrar en modal
            $_SESSION['preview_data'] = [
                'numEmpleado' => $numEmpleado,
                'nombre' => $nombre,
                'apellido_paterno' => $apellido_paterno,
                'apellido_materno' => $apellido_materno,
                'fecha_nacimiento' => $fecha_nacimiento,
                'id_genero' => $id_genero,
                'genero_nombre' => $genero_nombre,
                'rfc' => $rfc,
                'curp' => $curp,
                'id_nacionalidad' => $id_nacionalidad,
                'nacionalidad_nombre' => $nacionalidad_nombre,
                'id_estado' => $id_estado,
                'estado_nombre' => $estado_nombre,
                'direccion' => $direccion,
                'telefono_celular' => $telefono_celular,
                'telefono_emergencia' => $telefono_emergencia,
                'correo_institucional' => $correo_institucional,
                'correo_personal' => $correo_personal,
                'estado_civil' => $estado_civil,
                'errores' => $errores
            ];
            
            // Redirigir de vuelta al formulario para mostrar el modal
            header("Location: nuevo_maestro.php?preview=1");
            exit();
        }
        
        // Si se presionó el botón de guardar y no hay errores
        if (isset($_POST['guardar']) && empty($errores)) {
            try {
                $sql = "INSERT INTO maestros (numEmpleado, nombre, apellido_paterno, apellido_materno, fecha_nacimiento, id_genero, rfc, curp, id_nacionalidad, id_estado, direccion, telefono_celular, telefono_emergencia, correo_institucional, correo_personal, estado_civil, especialidad, titulo, observaciones, activo) 
        VALUES (:numEmpleado, :nombre, :apellido_paterno, :apellido_materno, :fecha_nacimiento, :id_genero, :rfc, :curp, :id_nacionalidad, :id_estado, :direccion, :telefono_celular, :telefono_emergencia, :correo_institucional, :correo_personal, :estado_civil, :especialidad, :titulo, :observaciones, :activo)";
                
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':numEmpleado', $numEmpleado);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':apellido_paterno', $apellido_paterno);
                $stmt->bindParam(':apellido_materno', $apellido_materno);
                $stmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
                $stmt->bindParam(':id_genero', $id_genero);
                $stmt->bindParam(':rfc', $rfc);
                $stmt->bindParam(':curp', $curp);
                $stmt->bindParam(':id_nacionalidad', $id_nacionalidad);
                $stmt->bindParam(':id_estado', $id_estado);
                $stmt->bindParam(':direccion', $direccion);
                $stmt->bindParam(':telefono_celular', $telefono_celular);
                $stmt->bindParam(':telefono_emergencia', $telefono_emergencia);
                $stmt->bindParam(':correo_institucional', $correo_institucional);
                $stmt->bindParam(':correo_personal', $correo_personal);
                $stmt->bindParam(':estado_civil', $estado_civil);
				$stmt->bindParam(':especialidad', $especialidad);
				$stmt->bindParam(':titulo', $titulo);
				$stmt->bindParam(':observaciones', $observaciones);
				$stmt->bindParam(':activo', $activo);
				

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Maestro creado exitosamente.";
                    // Limpiar datos de previsualización
                    if (isset($_SESSION['preview_data'])) {
                        unset($_SESSION['preview_data']);
                    }
                    header('Location: gestion_maestros.php?success=created');
                    exit();
                } else {
                    $errores[] = "Error al crear el maestro.";
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errores[] = "El número de empleado o correo electrónico ya existe.";
                } else {
                    $errores[] = "Error de base de datos: " . $e->getMessage();
                }
            }
        }
    }
}

// Cargar datos de previsualización si existen
$preview_data = isset($_SESSION['preview_data']) ? $_SESSION['preview_data'] : null;
$show_preview_modal = isset($_GET['preview']) && $preview_data;

// Si hay errores en los datos de previsualización, mostrarlos
if ($preview_data && !empty($preview_data['errores'])) {
    $errores = array_merge($errores, $preview_data['errores']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Maestro - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --cecyte-primary: #1a5330;
            --cecyte-secondary: #2e7d32;
            --cecyte-accent: #4caf50;
            --cecyte-light: #8bc34a;
        }
        
        body {
            background-color: #f1f8e9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header-cecyte {
            background: linear-gradient(135deg, var(--cecyte-primary), var(--cecyte-secondary));
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 4px solid var(--cecyte-accent);
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid #e0e0e0;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .form-header {
            background: linear-gradient(to right, var(--cecyte-primary), var(--cecyte-secondary));
            color: white;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .form-body {
            padding: 30px;
        }
        
        .section-title {
            color: var(--cecyte-primary);
            border-bottom: 2px solid var(--cecyte-accent);
            padding-bottom: 8px;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .form-label.required::after {
            content: " *";
            color: #dc3545;
        }
        
        .form-control, .form-select {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 10px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--cecyte-secondary);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
        }
        
        .alert-box {
            background-color: #e8f5e9;
            border-left: 4px solid var(--cecyte-secondary);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .btn-cecyte {
            background: var(--cecyte-secondary);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-cecyte:hover {
            background: var(--cecyte-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-preview {
            background: #ff9800;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-preview:hover {
            background: #f57c00;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 152, 0, 0.2);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
            color: white;
        }
        
        .info-text {
            color: #666;
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        .form-row {
            margin-bottom: 20px;
        }
        
        .preview-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .preview-label {
            font-weight: 600;
            color: var(--cecyte-primary);
            min-width: 200px;
            display: inline-block;
        }
        
        .preview-value {
            color: #333;
        }
        
        .preview-empty {
            color: #999;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .form-body {
                padding: 20px;
            }
            
            .preview-label {
                min-width: 150px;
            }
        }
        
        .modal-preview {
            max-width: 700px;
        }
        
        .modal-preview .modal-body {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="header-cecyte">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class='bx bx-user-plus'></i> Nuevo Maestro</h2>
                    <p class="mb-0">Sistema de Gesti&oacute;n Escolar - CECyTE</p>
                </div>
                <div>
                    <a href="gestion_maestros.php" class="btn btn-light">
                        <i class='bx bx-arrow-back'></i> Volver a la Lista de Maestros
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h4 class="mb-0"><i class='bx bx-user-circle'></i> Registro de Nuevo Maestro</h4>
                <p class="mb-0">Complete todos los campos requeridos (*)</p>
            </div>
            
            <div class="form-body">
                <div class="alert-box">
                    <h6><i class='bx bx-info-circle'></i> Informaci&oacute;n importante</h6>
                    <p class="mb-0">Aseg&uacute;rese de que el n&uacute;mero de empleado y correo electr&oacute;nico institucional no est&eacute;n registrados previamente. Los campos marcados con * son obligatorios. Use el bot&oacute;n "Ver Datos" para revisar la informaci&oacute;n antes de guardar.</p>
                </div>
                
                <?php if (!empty($mensajes)): ?>
                    <div class="alert alert-success">
                        <?php foreach ($mensajes as $mensaje): ?>
                            <p class="mb-0"><?php echo htmlspecialchars($mensaje); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">Errores encontrados:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errores as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="formMaestro">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Datos Básicos -->
                    <h5 class="section-title"><i class='bx bx-user'></i> Datos Personales</h5>
                    
                    <div class="row form-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">N&uacute;mero de Empleado</label>
                            <input type="text" class="form-control" name="numEmpleado" required 
                                   value="<?php echo htmlspecialchars($_POST['numEmpleado'] ?? $preview_data['numEmpleado'] ?? ''); ?>"
                                   maxlength="20" placeholder="Ej: M001">
                            <div class="info-text">Identificador &uacute;nico del maestro</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">Nombre(s)</label>
                            <input type="text" class="form-control" name="nombre" required 
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? $preview_data['nombre'] ?? ''); ?>"
                                   maxlength="100" placeholder="Ej: Juan Carlos">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">Apellido Paterno</label>
                            <input type="text" class="form-control" name="apellido_paterno" required 
                                   value="<?php echo htmlspecialchars($_POST['apellido_paterno'] ?? $preview_data['apellido_paterno'] ?? ''); ?>"
                                   maxlength="50" placeholder="Ej: Gonz&aacute;lez">
                        </div>
                    </div>
                    
                    <div class="row form-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Apellido Materno</label>
                            <input type="text" class="form-control" name="apellido_materno" 
                                   value="<?php echo htmlspecialchars($_POST['apellido_materno'] ?? $preview_data['apellido_materno'] ?? ''); ?>"
                                   maxlength="50" placeholder="Ej: L&oacute;pez">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento" required
                                   value="<?php echo htmlspecialchars($_POST['fecha_nacimiento'] ?? $preview_data['fecha_nacimiento'] ?? ''); ?>">
                            <div class="info-text">Formato: AAAA-MM-DD</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">G&eacute;nero</label>
                            <select class="form-select" name="id_genero" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($generos as $genero): ?>
                                    <option value="<?php echo $genero['id_genero']; ?>" 
                                        <?php echo (isset($_POST['id_genero']) && $_POST['id_genero'] == $genero['id_genero']) || (isset($preview_data['id_genero']) && $preview_data['id_genero'] == $genero['id_genero']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($genero['genero']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row form-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estado Civil</label>
                            <input type="text" class="form-control" name="estado_civil" 
                                   value="<?php echo htmlspecialchars($_POST['estado_civil'] ?? $preview_data['estado_civil'] ?? ''); ?>"
                                   maxlength="20" placeholder="Ej: Soltero">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Especialidad</label>
                            <input type="text" class="form-control" name="especialidad" 
                                   value="<?php echo htmlspecialchars($_POST['especialidad'] ?? $preview_data['especialidad'] ?? ''); ?>"
                                   maxlength="50" placeholder="Ej: Matem&aacute;ticas">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">T&iacute;tulo</label>
                            <input type="text" class="form-control" name="titulo" 
                                   value="<?php echo htmlspecialchars($_POST['titulo'] ?? $preview_data['titulo'] ?? ''); ?>"
                                   maxlength="30" placeholder="Ej: Licenciatura">
                        </div>
                    </div>
                    
                    <div class="row form-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">N&uacute;mero de Celular</label>
                            <input type="text" class="form-control" name="telefono_celular" 
                                   value="<?php echo htmlspecialchars($_POST['telefono_celular'] ?? $preview_data['telefono_celular'] ?? ''); ?>"
                                   maxlength="15" placeholder="Ej: 8123456789">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Activo / Inactivo</label>
                            <select class="form-select" name="activo">
                                <option value="Activo" <?php echo (($_POST['activo'] ?? $preview_data['activo'] ?? 'Activo') == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="Inactivo" <?php echo (($_POST['activo'] ?? $preview_data['activo'] ?? 'Activo') == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Documentos e Identificación -->
                    <h5 class="section-title mt-4"><i class='bx bx-id-card'></i> Documentos de Identificaci&oacute;n</h5>
                    
                    <div class="row form-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">RFC</label>
                            <input type="text" class="form-control" name="rfc" 
                                   value="<?php echo htmlspecialchars($_POST['rfc'] ?? $preview_data['rfc'] ?? ''); ?>" 
                                   maxlength="13" placeholder="Ej: GOLJ800101ABC">
                            <div class="info-text">13 caracteres (opcional)</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">CURP</label>
                            <input type="text" class="form-control" name="curp" 
                                   value="<?php echo htmlspecialchars($_POST['curp'] ?? $preview_data['curp'] ?? ''); ?>" 
                                   maxlength="18" placeholder="Ej: GOLJ800101HDFNPS09">
                            <div class="info-text">18 caracteres (opcional)</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">Nacionalidad</label>
                            <select class="form-select" name="id_nacionalidad" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($nacionalidades as $nacionalidad): ?>
                                    <option value="<?php echo $nacionalidad['id_nacionalidad']; ?>" 
                                        <?php echo (isset($_POST['id_nacionalidad']) && $_POST['id_nacionalidad'] == $nacionalidad['id_nacionalidad']) || (isset($preview_data['id_nacionalidad']) && $preview_data['id_nacionalidad'] == $nacionalidad['id_nacionalidad']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nacionalidad['nacionalidad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row form-row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Estado de Nacimiento</label>
                            <select class="form-select" name="id_estado" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?php echo $estado['id_estado']; ?>" 
                                        <?php echo (isset($_POST['id_estado']) && $_POST['id_estado'] == $estado['id_estado']) || (isset($preview_data['id_estado']) && $preview_data['id_estado'] == $estado['id_estado']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($estado['estado']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Información de Contacto -->
                    <h5 class="section-title mt-4"><i class='bx bx-envelope'></i> Informaci&oacute;n de Contacto</h5>
                    
                    <div class="row form-row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Correo Institucional</label>
                            <input type="email" class="form-control" name="correo_institucional" required 
                                   value="<?php echo htmlspecialchars($_POST['correo_institucional'] ?? $preview_data['correo_institucional'] ?? ''); ?>"
                                   maxlength="100" placeholder="Ej: maestro@cecyte.edu.mx">
                            <div class="info-text">Correo oficial de la instituci&oacute;n</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Correo Personal</label>
                            <input type="email" class="form-control" name="correo_personal" 
                                   value="<?php echo htmlspecialchars($_POST['correo_personal'] ?? $preview_data['correo_personal'] ?? ''); ?>"
                                   maxlength="100" placeholder="Ej: ejemplo@gmail.com">
                        </div>
                    </div>
                    
                    <div class="row form-row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tel&eacute;fono de Emergencia</label>
                            <input type="text" class="form-control" name="telefono_emergencia" 
                                   value="<?php echo htmlspecialchars($_POST['telefono_emergencia'] ?? $preview_data['telefono_emergencia'] ?? ''); ?>"
                                   maxlength="15" placeholder="Ej: 8187654321">
                        </div>
                    </div>
                    
                    <!-- Dirección -->
                    <h5 class="section-title mt-4"><i class='bx bx-home'></i> Direcci&oacute;n</h5>
                    
                    <div class="row form-row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Direcci&oacute;n Completa</label>
                            <textarea class="form-control" name="direccion" rows="3" maxlength="255" 
                                      placeholder="Calle, n&uacute;mero, colonia, etc."><?php echo htmlspecialchars($_POST['direccion'] ?? $preview_data['direccion'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Observaciones -->
                    <h5 class="section-title mt-4"><i class='bx bx-note'></i> Observaciones</h5>
                    
                    <div class="row form-row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="2" maxlength="255"
                                      placeholder="Observaciones adicionales..."><?php echo htmlspecialchars($_POST['observaciones'] ?? $preview_data['observaciones'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                        <a href="gestion_maestros.php" class="btn btn-cancel">
                            <i class='bx bx-arrow-back'></i> Cancelar
                        </a>
                        <div>
                            <button type="submit" name="preview" class="btn btn-preview">
                                <i class='bx bx-show'></i> Ver Datos
                            </button>
                            <button type="submit" name="guardar" class="btn btn-cecyte ms-2">
                                <i class='bx bx-save'></i> Guardar Maestro
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

 <!-- Modal para previsualizar datos -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-preview">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class='bx bx-show'></i> Previsualizaci&oacute;n de Datos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if ($show_preview_modal && $preview_data): ?>
                    <div class="alert alert-info mb-3">
                        <i class='bx bx-info-circle'></i> Revise cuidadosamente todos los datos antes de guardar.
                    </div>
                    
                    <div class="preview-section mb-4">
                        <h6 class="section-title mb-3"><i class='bx bx-user'></i> Datos Personales</h6>
                        <div class="preview-item">
                            <span class="preview-label">N&uacute;mero de Empleado:</span>
                            <span class="preview-value"><?php echo htmlspecialchars($preview_data['numEmpleado']); ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Nombre Completo:</span>
                            <span class="preview-value"><?php echo htmlspecialchars($preview_data['nombre'] . ' ' . $preview_data['apellido_paterno'] . ' ' . $preview_data['apellido_materno']); ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Fecha de Nacimiento:</span>
                            <span class="preview-value"><?php echo htmlspecialchars($preview_data['fecha_nacimiento']); ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">G&eacute;nero:</span>
                            <span class="preview-value"><?php echo htmlspecialchars($preview_data['genero_nombre'] ?? 'No seleccionado'); ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Estado Civil:</span>
                            <span class="preview-value"><?php echo !empty($preview_data['estado_civil']) ? htmlspecialchars($preview_data['estado_civil']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Especialidad:</span>
                            <span class="preview-value"><?php echo !empty($preview_data['especialidad']) ? htmlspecialchars($preview_data['especialidad']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">T&iacute;tulo:</span>
                            <span class="preview-value"><?php echo !empty($preview_data['titulo']) ? htmlspecialchars($preview_data['titulo']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Activo:</span>
                            <span class="preview-value"><?php echo htmlspecialchars($preview_data['activo'] ?? 'Activo'); ?></span>
                        </div>
                    </div>
                    
                    <div class="preview-section mb-4">
                        <h6 class="section-title mb-3"><i class='bx bx-id-card'></i> Documentos de Identificaci&oacute;n</h6>
                        <div class="preview-item">
                            <span class="preview-label">RFC:</span>
                            <span class="preview-value"><?php echo !empty($preview_data['rfc']) ? htmlspecialchars($preview_data['rfc']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">CURP:</span>
                            <span class="preview-value"><?php echo !empty($preview_data['curp']) ? htmlspecialchars($preview_data['curp']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Nacionalidad:</span>
                            <span class="preview-value"><?php echo htmlspecialchars($preview_data['nacionalidad_nombre'] ?? 'No seleccionado'); ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Estado de Nacimiento:</span>
                            <span class="preview-value"><?php echo htmlspecialchars($preview_data['estado_nombre'] ?? 'No seleccionado'); ?></span>
                        </div>
                    </div>
                    
                    <div class="preview-section mb-4">
                        <h6 class="section-title mb-3"><i class='bx bx-envelope'></i> Informaci&oacute;n de Contacto</h6>
                        <div class="preview-item">
                            <span class="preview-label">Correo Institucional:</span>
                            <span class="preview-value"><?php echo htmlspecialchars($preview_data['correo_institucional']); ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Correo Personal:</span>
                            <span class="preview-value"><?php echo !empty($preview_data['correo_personal']) ? htmlspecialchars($preview_data['correo_personal']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Tel&eacute;fono Celular:</span>
                            <span class="preview-value"><?php echo !empty($preview_data['telefono_celular']) ? htmlspecialchars($preview_data['telefono_celular']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Tel&eacute;fono de Emergencia:</span>
                            <span class="preview-value"><?php echo !empty($preview_data['telefono_emergencia']) ? htmlspecialchars($preview_data['telefono_emergencia']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                        </div>
                    </div>
                    
                    <div class="preview-section mb-4">
                        <h6 class="section-title mb-3"><i class='bx bx-home'></i> Direcci&oacute;n</h6>
                        <div class="preview-item">
                            <span class="preview-label">Direcci&oacute;n Completa:</span>
                            <span class="preview-value"><?php echo !empty($preview_data['direccion']) ? nl2br(htmlspecialchars($preview_data['direccion'])) : '<span class="preview-empty">No especificada</span>'; ?></span>
                        </div>
                    </div>
                    
                    <div class="preview-section mb-4">
                        <h6 class="section-title mb-3"><i class='bx bx-note'></i> Observaciones</h6>
                        <div class="preview-item">
                            <span class="preview-label">Observaciones:</span>
                            <span class="preview-value"><?php echo !empty($preview_data['observaciones']) ? nl2br(htmlspecialchars($preview_data['observaciones'])) : '<span class="preview-empty">No especificadas</span>'; ?></span>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class='bx bx-alarm-exclamation'></i> 
                        <strong>Nota:</strong> Si los datos son correctos, haga clic en "Confirmar y Guardar". 
                        Si necesita realizar cambios, cierre este modal y modifique los datos en el formulario.
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class='bx bx-edit'></i> Modificar Datos
                </button>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="numEmpleado" value="<?php echo $preview_data['numEmpleado'] ?? ''; ?>">
                    <input type="hidden" name="nombre" value="<?php echo $preview_data['nombre'] ?? ''; ?>">
                    <input type="hidden" name="apellido_paterno" value="<?php echo $preview_data['apellido_paterno'] ?? ''; ?>">
                    <input type="hidden" name="apellido_materno" value="<?php echo $preview_data['apellido_materno'] ?? ''; ?>">
                    <input type="hidden" name="fecha_nacimiento" value="<?php echo $preview_data['fecha_nacimiento'] ?? ''; ?>">
                    <input type="hidden" name="id_genero" value="<?php echo $preview_data['id_genero'] ?? ''; ?>">
                    <input type="hidden" name="rfc" value="<?php echo $preview_data['rfc'] ?? ''; ?>">
                    <input type="hidden" name="curp" value="<?php echo $preview_data['curp'] ?? ''; ?>">
                    <input type="hidden" name="id_nacionalidad" value="<?php echo $preview_data['id_nacionalidad'] ?? ''; ?>">
                    <input type="hidden" name="id_estado" value="<?php echo $preview_data['id_estado'] ?? ''; ?>">
                    <input type="hidden" name="direccion" value="<?php echo $preview_data['direccion'] ?? ''; ?>">
                    <input type="hidden" name="telefono_celular" value="<?php echo $preview_data['telefono_celular'] ?? ''; ?>">
                    <input type="hidden" name="telefono_emergencia" value="<?php echo $preview_data['telefono_emergencia'] ?? ''; ?>">
                    <input type="hidden" name="correo_institucional" value="<?php echo $preview_data['correo_institucional'] ?? ''; ?>">
                    <input type="hidden" name="correo_personal" value="<?php echo $preview_data['correo_personal'] ?? ''; ?>">
                    <input type="hidden" name="estado_civil" value="<?php echo $preview_data['estado_civil'] ?? ''; ?>">
                    <input type="hidden" name="especialidad" value="<?php echo $preview_data['especialidad'] ?? ''; ?>">
                    <input type="hidden" name="titulo" value="<?php echo $preview_data['titulo'] ?? ''; ?>">
                    <input type="hidden" name="observaciones" value="<?php echo $preview_data['observaciones'] ?? ''; ?>">
                    <input type="hidden" name="activo" value="<?php echo $preview_data['activo'] ?? 'Activo'; ?>">
                    <button type="submit" name="guardar" class="btn btn-success">
                        <i class='bx bx-check'></i> Confirmar y Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de formulario
        document.getElementById('formMaestro').addEventListener('submit', function(e) {
            // Si es el botón de previsualización, validar pero no mostrar confirmación
            if (e.submitter && e.submitter.name === 'preview') {
                const numEmpleado = this.querySelector('input[name="numEmpleado"]').value.trim();
                const nombre = this.querySelector('input[name="nombre"]').value.trim();
                const apellido_paterno = this.querySelector('input[name="apellido_paterno"]').value.trim();
                const fecha_nacimiento = this.querySelector('input[name="fecha_nacimiento"]').value.trim();
                const correo_institucional = this.querySelector('input[name="correo_institucional"]').value.trim();
                const id_genero = this.querySelector('select[name="id_genero"]').value;
                const id_nacionalidad = this.querySelector('select[name="id_nacionalidad"]').value;
                const id_estado = this.querySelector('select[name="id_estado"]').value;
                
                // Validar campos requeridos
                if (!numEmpleado || !nombre || !apellido_paterno || !fecha_nacimiento || !correo_institucional || !id_genero || !id_nacionalidad || !id_estado) {
                    e.preventDefault();
                    alert('Por favor, complete todos los campos requeridos (*) antes de ver los datos.');
                    return false;
                }
                
                // Validar formato de email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(correo_institucional)) {
                    e.preventDefault();
                    alert('El correo institucional no tiene un formato v&aacute;lido');
                    return false;
                }
                
                // Validar RFC si se proporciona
                const rfc = this.querySelector('input[name="rfc"]').value.trim();
                if (rfc && !/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/.test(rfc.toUpperCase())) {
                    e.preventDefault();
                    alert('El RFC no tiene un formato v&aacute;lido (debe tener 13 caracteres alfanum&eacute;ricos)');
                    return false;
                }
                
                // Validar CURP si se proporciona
                const curp = this.querySelector('input[name="curp"]').value.trim();
                if (curp && !/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$/.test(curp.toUpperCase())) {
                    e.preventDefault();
                    alert('El CURP no tiene un formato v&aacute;lido (debe tener 18 caracteres alfanum&eacute;ricos)');
                    return false;
                }
                
                // Validar fecha de nacimiento (no puede ser futura)
                const fechaNac = new Date(fecha_nacimiento);
                const hoy = new Date();
                if (fechaNac > hoy) {
                    e.preventDefault();
                    alert('La fecha de nacimiento no puede ser futura');
                    return false;
                }
                
                return true;
            }
            
            // Si es el botón de guardar
            if (e.submitter && e.submitter.name === 'guardar') {
                const confirmar = confirm('¿Est&aacute; seguro de guardar los datos del nuevo maestro?');
                if (!confirmar) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
        
        // Formato automático para RFC y CURP
        const rfcInput = document.querySelector('input[name="rfc"]');
        if (rfcInput) {
            rfcInput.addEventListener('input', function(e) {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9&Ñ]/g, '').substring(0, 13);
            });
        }
        
        const curpInput = document.querySelector('input[name="curp"]');
        if (curpInput) {
            curpInput.addEventListener('input', function(e) {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 18);
            });
        }
        
        // Formato para teléfonos (solo números)
        const telefonoInputs = document.querySelectorAll('input[name="telefono_celular"], input[name="telefono_emergencia"]');
        telefonoInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
        
        // Prevenir envío con Enter en campos individuales
        const formInputs = document.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                }
            });
        });
        
        // Mostrar modal de previsualización si hay datos
        <?php if ($show_preview_modal): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            previewModal.show();
            
            // Limpiar parámetro de URL
            const url = new URL(window.location.href);
            url.searchParams.delete('preview');
            window.history.replaceState({}, document.title, url.toString());
        });
        <?php endif; ?>
        
        // Función para mostrar previsualización rápida en consola (para desarrollo)
        function previewInConsole() {
            const formData = new FormData(document.getElementById('formMaestro'));
            const data = {};
            for (let [key, value] of formData.entries()) {
                if (key !== 'csrf_token') {
                    data[key] = value;
                }
            }
            console.log('Datos del formulario:', data);
            return false;
        }
        
        // Atajo de teclado para previsualización: Ctrl+Shift+P
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                e.preventDefault();
                previewInConsole();
                alert('Datos mostrados en consola (F12 para ver)');
            }
        });
    </script>
</body>
</html>