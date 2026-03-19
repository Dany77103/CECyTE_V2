<?php
// nuevo_alumno.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Verificar permisos del usuario (opcional)
if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'registro') {
    $_SESSION['error'] = "No tiene permisos para registrar nuevos alumnos";
    header('Location: gestion_alumnos.php');
    exit();
}

// Obtener datos para formularios
try {
    $sql_carreras = "SELECT * FROM carreras WHERE activo = 1 ORDER BY nombre";
    $carreras = $con->query($sql_carreras)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_grupos = "SELECT g.*, c.nombre as carrera_nombre 
                   FROM grupos g 
                   LEFT JOIN carreras c ON g.id_carrera = c.id_carrera 
                   WHERE g.activo = 1 
                   ORDER BY g.semestre, g.nombre";
    $grupos = $con->query($sql_grupos)->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener el �ltimo n�mero de matr�cula para sugerir siguiente
    $sql_ultima_matricula = "SELECT matricula FROM alumnos WHERE matricula LIKE CONCAT(YEAR(CURDATE()), '%') ORDER BY id_alumno DESC LIMIT 1";
    $stmt = $con->query($sql_ultima_matricula);
    $ultima_matricula = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $siguiente_matricula = date('Y') . '001'; // Matr�cula por defecto
    if ($ultima_matricula && isset($ultima_matricula['matricula'])) {
        $ultimo_numero = intval(substr($ultima_matricula['matricula'], 4));
        $siguiente_matricula = date('Y') . str_pad($ultimo_numero + 1, 3, '0', STR_PAD_LEFT);
    }
    
} catch (PDOException $e) {
    die("Error al obtener datos para el formulario: " . $e->getMessage());
}

// Generar CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Procesar formulario
$mensajes = [];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errores[] = "Token de seguridad inv�lido. Por favor, recarga la p�gina.";
    } else {
        // Recoger datos del formulario
        $datos = [
            'matricula' => trim($_POST['matricula'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido_paterno' => trim($_POST['apellido_paterno'] ?? ''),
            'apellido_materno' => trim($_POST['apellido_materno'] ?? ''),
            'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? ''),
            'curp' => trim($_POST['curp'] ?? ''),
            'rfc' => trim($_POST['rfc'] ?? ''),
            'id_genero' => trim($_POST['id_genero'] ?? ''),
            'id_nacionalidad' => 1, // Por defecto mexicana
            'id_estado' => trim($_POST['id_estado'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'telefono_celular' => trim($_POST['telefono_celular'] ?? ''),
            'telefono_emergencia' => trim($_POST['telefono_emergencia'] ?? ''),
            'correo_institucional' => trim($_POST['correo_institucional'] ?? ''),
            'correo_personal' => trim($_POST['correo_personal'] ?? ''),
            'id_discapacidad' => trim($_POST['id_discapacidad'] ?? ''),
            'fecha_ingreso' => trim($_POST['fecha_ingreso'] ?? ''),
            'id_carrera' => $_POST['id_carrera'] ?? '',
            'id_semestre' => $_POST['id_semestre'] ?? '',
            'id_grupo' => $_POST['id_grupo'] ?? '',
            'activo' => $_POST['activo'] ?? 'Activo',
            'telefono_casa' => trim($_POST['telefono_casa'] ?? ''),
            'colonia' => trim($_POST['colonia'] ?? ''),
            'turno' => trim($_POST['turno'] ?? ''),
            'tipo_sangre' => trim($_POST['tipo_sangre'] ?? ''),
            'alergias' => trim($_POST['alergias'] ?? ''),
            'enfermedades_cronicas' => trim($_POST['enfermedades_cronicas'] ?? ''),
            'seguro_medico' => trim($_POST['seguro_medico'] ?? ''),
            'escuela_procedencia' => trim($_POST['escuela_procedencia'] ?? ''),
            'promedio_secundaria' => trim($_POST['promedio_secundaria'] ?? ''),
            'observaciones' => trim($_POST['observaciones'] ?? ''),
            'porcentaje_beca' => trim($_POST['porcentaje_beca'] ?? '0'),
            'beca' => trim($_POST['beca'] ?? 'NO'),
            'nombre_padre' => trim($_POST['nombre_padre'] ?? ''),
            'ocupacion_padre' => trim($_POST['ocupacion_padre'] ?? ''),
            'telefono_padre' => trim($_POST['telefono_padre'] ?? ''),
            'nombre_madre' => trim($_POST['nombre_madre'] ?? ''),
            'ocupacion_madre' => trim($_POST['ocupacion_madre'] ?? ''),
            'telefono_madre' => trim($_POST['telefono_madre'] ?? ''),
            'usuario_creacion' => $_SESSION['username'] ?? 'Sistema'
        ];
        
        // Validaciones b�sicas
        $camposRequeridos = [
            'matricula', 'nombre', 'apellido_paterno', 'fecha_nacimiento', 
            'curp', 'id_genero', 'telefono_celular', 'fecha_ingreso', 
            'id_carrera', 'id_semestre'
        ];
        
        foreach ($camposRequeridos as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = ucfirst(str_replace('_', ' ', $campo)) . " es requerido.";
            }
        }
        
        // Validar CURP
        if (!empty($datos['curp'])) {
            $pattern_curp = '/^[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z]{2}$/';
            if (!preg_match($pattern_curp, strtoupper($datos['curp']))) {
                $errores[] = "El CURP no tiene un formato v�lido (18 caracteres alfanum�ricos).";
            }
        }
        
        // Validar RFC (opcional)
        if (!empty($datos['rfc'])) {
            $pattern_rfc = '/^[A-Z&N]{3,4}[0-9]{6}[A-Z0-9]{3}$/';
            if (!preg_match($pattern_rfc, strtoupper($datos['rfc']))) {
                $errores[] = "El RFC no tiene un formato v�lido.";
            }
        }
        
        // Validar correos
        if (!empty($datos['correo_personal']) && !filter_var($datos['correo_personal'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo personal no es v�lido.";
        }
        
        if (!empty($datos['correo_institucional']) && !filter_var($datos['correo_institucional'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo institucional no es v�lido.";
        }
        
        // Validar fechas
        if (!empty($datos['fecha_nacimiento'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $datos['fecha_nacimiento']);
            if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha_nacimiento']) {
                $errores[] = "La fecha de nacimiento no es v�lida.";
            } else {
                $hoy = new DateTime();
                $edad = $hoy->diff($fecha)->y;
                if ($edad < 15) {
                    $errores[] = "El alumno debe tener al menos 15 a�os de edad.";
                }
                if ($edad > 25) {
                    $errores[] = "La edad del alumno parece incorrecta (mayor a 25 a�os).";
                }
            }
        }
        
        if (!empty($datos['fecha_ingreso'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $datos['fecha_ingreso']);
            if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha_ingreso']) {
                $errores[] = "La fecha de ingreso no es v�lida.";
            }
        }
        
        // Validar tel�fono celular
        if (!empty($datos['telefono_celular']) && !preg_match('/^[0-9]{10}$/', $datos['telefono_celular'])) {
            $errores[] = "El tel�fono celular debe tener 10 d�gitos.";
        }
        
        // Verificar matr�cula �nica
        if (!empty($datos['matricula'])) {
            try {
                $sql_check = "SELECT COUNT(*) as count FROM alumnos WHERE matricula = :matricula";
                $stmt_check = $con->prepare($sql_check);
                $stmt_check->bindParam(':matricula', $datos['matricula'], PDO::PARAM_STR);
                $stmt_check->execute();
                $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    $errores[] = "La matr�cula ya existe. Por favor, use otra.";
                }
            } catch (PDOException $e) {
                $errores[] = "Error al verificar la matr�cula: " . $e->getMessage();
            }
        }
        
        // Verificar CURP �nico
        if (!empty($datos['curp'])) {
            try {
                $sql_check = "SELECT COUNT(*) as count FROM alumnos WHERE curp = :curp";
                $stmt_check = $con->prepare($sql_check);
                $stmt_check->bindParam(':curp', $datos['curp'], PDO::PARAM_STR);
                $stmt_check->execute();
                $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    $errores[] = "El CURP ya est� registrado en el sistema.";
                }
            } catch (PDOException $e) {
                $errores[] = "Error al verificar el CURP: " . $e->getMessage();
            }
        }
        
        // Si se presion� el bot�n de previsualizaci�n
        if (isset($_POST['preview'])) {
            // Guardar datos en sesi�n para mostrar en modal
            $_SESSION['preview_data'] = $datos;
            $_SESSION['preview_errors'] = $errores;
            
            // Redirigir de vuelta al formulario para mostrar el modal
            header("Location: nuevo_alumno2.php?preview=1");
            exit();
        }
        
        // Si se presion� el bot�n de guardar y no hay errores
        if (isset($_POST['guardar']) && empty($errores)) {
            try {
                // Iniciar transacci�n
                $con->beginTransaction();
                
                // Preparar SQL para insertar
                $sql = "INSERT INTO alumnos (
                    matricula, apellido_paterno, apellido_materno, nombre, fecha_nacimiento,
                    id_genero, rfc, id_nacionalidad, id_estado,
                    direccion, telefono_celular, telefono_emergencia, correo_institucional, correo_personal,
                    id_discapacidad, fecha_ingreso, id_carrera, id_semestre, id_grupo,
                    activo, curp, telefono_casa, colonia, turno,
                    tipo_sangre, alergias, enfermedades_cronicas, seguro_medico,
                    escuela_procedencia, promedio_secundaria, observaciones, porcentaje_beca, beca,
                    nombre_padre, ocupacion_padre, telefono_padre,
                    nombre_madre, ocupacion_madre, telefono_madre,
                    usuario_creacion, created_at, updated_at
                ) VALUES (
                    :matricula, :apellido_paterno, :apellido_materno, :nombre, :fecha_nacimiento,
                    :id_genero, :rfc, :id_nacionalidad, :id_estado,
                    :direccion, :telefono_celular, :telefono_emergencia, :correo_institucional, :correo_personal,
                    :id_discapacidad, :fecha_ingreso, :id_carrera, :id_semestre, :id_grupo,
                    :activo, :curp, :telefono_casa, :colonia, :turno,
                    :tipo_sangre, :alergias, :enfermedades_cronicas, :seguro_medico,
                    :escuela_procedencia, :promedio_secundaria, :observaciones, :porcentaje_beca, :beca,
                    :nombre_padre, :ocupacion_padre, :telefono_padre,
                    :nombre_madre, :ocupacion_madre, :telefono_madre,
                    :usuario_creacion, NOW(), NOW()
                )";
                
                $stmt = $con->prepare($sql);
                
                // Vincular par�metros
                $stmt->bindParam(':matricula', $datos['matricula']);
                $stmt->bindParam(':nombre', $datos['nombre']);
                $stmt->bindParam(':apellido_paterno', $datos['apellido_paterno']);
                $stmt->bindParam(':apellido_materno', $datos['apellido_materno']);
                $stmt->bindParam(':fecha_nacimiento', $datos['fecha_nacimiento']);
                $stmt->bindParam(':curp', $datos['curp']);
                $stmt->bindParam(':rfc', $datos['rfc']);
                $stmt->bindParam(':id_genero', $datos['id_genero']);
                $stmt->bindParam(':id_nacionalidad', $datos['id_nacionalidad'], PDO::PARAM_INT);
                $stmt->bindParam(':id_estado', $datos['id_estado'], PDO::PARAM_INT);
                $stmt->bindParam(':direccion', $datos['direccion']);
                $stmt->bindParam(':telefono_celular', $datos['telefono_celular']);
                $stmt->bindParam(':telefono_emergencia', $datos['telefono_emergencia']);
                $stmt->bindParam(':correo_institucional', $datos['correo_institucional']);
                $stmt->bindParam(':correo_personal', $datos['correo_personal']);
                $stmt->bindParam(':id_discapacidad', $datos['id_discapacidad'], PDO::PARAM_INT);
                $stmt->bindParam(':fecha_ingreso', $datos['fecha_ingreso']);
                $stmt->bindParam(':id_carrera', $datos['id_carrera'], PDO::PARAM_INT);
                $stmt->bindParam(':id_semestre', $datos['id_semestre'], PDO::PARAM_INT);
                $stmt->bindParam(':id_grupo', $datos['id_grupo'], PDO::PARAM_INT);
                $stmt->bindParam(':activo', $datos['activo']);
                $stmt->bindParam(':telefono_casa', $datos['telefono_casa']);
                $stmt->bindParam(':colonia', $datos['colonia']);
                $stmt->bindParam(':turno', $datos['turno']);
                $stmt->bindParam(':tipo_sangre', $datos['tipo_sangre']);
                $stmt->bindParam(':alergias', $datos['alergias']);
                $stmt->bindParam(':enfermedades_cronicas', $datos['enfermedades_cronicas']);
                $stmt->bindParam(':seguro_medico', $datos['seguro_medico']);
                $stmt->bindParam(':escuela_procedencia', $datos['escuela_procedencia']);
                $stmt->bindParam(':promedio_secundaria', $datos['promedio_secundaria']);
                $stmt->bindParam(':observaciones', $datos['observaciones']);
                $stmt->bindParam(':porcentaje_beca', $datos['porcentaje_beca']);
                $stmt->bindParam(':beca', $datos['beca']);
                $stmt->bindParam(':nombre_padre', $datos['nombre_padre']);
                $stmt->bindParam(':ocupacion_padre', $datos['ocupacion_padre']);
                $stmt->bindParam(':telefono_padre', $datos['telefono_padre']);
                $stmt->bindParam(':nombre_madre', $datos['nombre_madre']);
                $stmt->bindParam(':ocupacion_madre', $datos['ocupacion_madre']);
                $stmt->bindParam(':telefono_madre', $datos['telefono_madre']);
                $stmt->bindParam(':usuario_creacion', $datos['usuario_creacion']);
                
                // Ejecutar inserci�n
                if ($stmt->execute()) {
                    $id_alumno = $con->lastInsertId();
                    
                    // Confirmar transacci�n
                    $con->commit();
                    
                    // Limpiar datos de previsualizaci�n
                    if (isset($_SESSION['preview_data'])) {
                        unset($_SESSION['preview_data']);
                    }
                    
                    $_SESSION['success_message'] = "Alumno registrado exitosamente con matr�cula: " . $datos['matricula'];
                    header('Location: ver_alumno2.php?matricula=' . urlencode($datos['matricula']));
                    exit();
                    
                } else {
                    $con->rollBack();
                    $errores[] = "Error al registrar el alumno en la base de datos.";
                }
            } catch (PDOException $e) {
                $con->rollBack();
                $errores[] = "Error de base de datos: " . $e->getMessage();
            }
        }
    }
}

// Cargar datos de previsualizaci�n si existen
$preview_data = isset($_SESSION['preview_data']) ? $_SESSION['preview_data'] : null;
$show_preview_modal = isset($_GET['preview']) && $preview_data;

// Si hay errores en los datos de previsualizaci�n, mostrarlos
if (isset($_SESSION['preview_errors']) && !empty($_SESSION['preview_errors'])) {
    $errores = array_merge($errores, $_SESSION['preview_errors']);
    unset($_SESSION['preview_errors']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Alumno - CECyTE</title>
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
        
        .badge-required {
            background-color: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 5px;
        }
        
        .input-group-text {
            background-color: var(--cecyte-light);
            color: var(--cecyte-primary);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header-cecyte">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class='bx bx-user-plus'></i> Nuevo Alumno</h2>
                    <p class="mb-0">Sistema de Gesti&oacute;n Escolar - CECyTE</p>
                </div>
                <div>
                    <a href="gestion_alumnos.php" class="btn btn-light">
                        <i class='bx bx-arrow-back'></i> Volver a la Lista de Alumnos
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h4 class="mb-0"><i class='bx bx-user-circle'></i> Registro de Nuevo Alumno</h4>
                <p class="mb-0">Complete todos los campos requeridos <span class="badge-required">*</span></p>
            </div>
            
            <div class="form-body">
                <div class="alert-box">
                    <h6><i class='bx bx-info-circle'></i> Informaci&oacute;n importante</h6>
                    <p class="mb-0">Aseg&uacute;rese de que la matr&iacute;cula y CURP no est&eacute;n registrados previamente. Los campos marcados con <span class="badge-required">*</span> son obligatorios. Use el bot&oacute;n "Ver Datos" para revisar la informaci&oacute;n antes de guardar.</p>
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
                
                <form method="POST" action="" id="formAlumno">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Datos Personales -->
                    <h5 class="section-title"><i class='bx bx-user'></i> Datos Personales</h5>
                    
                    <div class="row form-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">Matr&iacute;cula</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="matricula" required 
                                       value="<?php echo htmlspecialchars($_POST['matricula'] ?? $preview_data['matricula'] ?? $siguiente_matricula); ?>"
                                       maxlength="11" pattern="[0-9]{11}" placeholder="11 d&iacute;gitos">
                                <button class="btn btn-outline-secondary" type="button" onclick="generarMatricula()">
                                    <i class='bx bx-refresh'></i>
                                </button>
                            </div>
                            <div class="info-text">11 d&iacute;gitos num&eacute;ricos. Sugerencia: <?php echo $siguiente_matricula; ?></div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">CURP</label>
                            <input type="text" class="form-control" name="curp" required 
                                   value="<?php echo htmlspecialchars($_POST['curp'] ?? $preview_data['curp'] ?? ''); ?>"
                                   maxlength="18" pattern="[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z]{2}"
                                   onblur="validarCURP()">
                            <div class="info-text" id="curp-feedback"></div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">RFC</label>
                            <input type="text" class="form-control" name="rfc" 
                                   value="<?php echo htmlspecialchars($_POST['rfc'] ?? $preview_data['rfc'] ?? ''); ?>"
                                   maxlength="13" pattern="[A-Z&N]{3,4}[0-9]{6}[A-Z0-9]{3}">
                        </div>
                    </div>
                    
                    <div class="row form-row">
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
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Apellido Materno</label>
                            <input type="text" class="form-control" name="apellido_materno" 
                                   value="<?php echo htmlspecialchars($_POST['apellido_materno'] ?? $preview_data['apellido_materno'] ?? ''); ?>"
                                   maxlength="50" placeholder="Ej: L&oacute;pez">
                        </div>
                    </div>
                    
                    <div class="row form-row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label required">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento" required
                                   value="<?php echo htmlspecialchars($_POST['fecha_nacimiento'] ?? $preview_data['fecha_nacimiento'] ?? ''); ?>"
                                   onchange="calcularEdad()">
                            <div class="info-text" id="edad-display"></div>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label required">G&eacute;nero</label>
                            <select class="form-select" name="id_genero" required>
                                <option value="">Seleccionar...</option>
                                <option value="1" <?php echo (($_POST['id_genero'] ?? $preview_data['id_genero'] ?? '') == '1') ? 'selected' : ''; ?>>Masculino</option>
                                <option value="2" <?php echo (($_POST['id_genero'] ?? $preview_data['id_genero'] ?? '') == '2') ? 'selected' : ''; ?>>Femenino</option>
                                <option value="3" <?php echo (($_POST['id_genero'] ?? $preview_data['id_genero'] ?? '') == '3') ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Tipo de Sangre</label>
                            <select class="form-select" name="tipo_sangre">
                                <option value="">Seleccionar...</option>
                                <option value="o+" <?php echo (($_POST['tipo_sangre'] ?? $preview_data['tipo_sangre'] ?? '') == 'o+') ? 'selected' : ''; ?>>O+</option>
                                <option value="o-" <?php echo (($_POST['tipo_sangre'] ?? $preview_data['tipo_sangre'] ?? '') == 'o-') ? 'selected' : ''; ?>>O-</option>
                                <option value="a+" <?php echo (($_POST['tipo_sangre'] ?? $preview_data['tipo_sangre'] ?? '') == 'a+') ? 'selected' : ''; ?>>A+</option>
                                <option value="a-" <?php echo (($_POST['tipo_sangre'] ?? $preview_data['tipo_sangre'] ?? '') == 'a-') ? 'selected' : ''; ?>>A-</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Informaci�n de Contacto -->
<h5 class="section-title mt-4"><i class='bx bx-envelope'></i> Informaci&oacute;n de Contacto</h5>

<div class="row form-row">
    <div class="col-md-4 mb-3">
        <label class="form-label required">Tel&eacute;fono Celular</label>
        <input type="tel" class="form-control" name="telefono_celular" required 
               value="<?php echo htmlspecialchars($_POST['telefono_celular'] ?? $preview_data['telefono_celular'] ?? ''); ?>"
               pattern="[0-9]{10}" maxlength="10" placeholder="10 d&iacute;gitos">
    </div>
    
    <div class="col-md-4 mb-3">
        <label class="form-label">Tel&eacute;fono de Casa</label>
        <input type="tel" class="form-control" name="telefono_casa" 
               value="<?php echo htmlspecialchars($_POST['telefono_casa'] ?? $preview_data['telefono_casa'] ?? ''); ?>"
               pattern="[0-9]{10}" maxlength="10" placeholder="10 d&iacute;gitos">
    </div>
    
    <div class="col-md-4 mb-3">
        <label class="form-label">Tel&eacute;fono de Emergencia</label>
        <input type="tel" class="form-control" name="telefono_emergencia" 
               value="<?php echo htmlspecialchars($_POST['telefono_emergencia'] ?? $preview_data['telefono_emergencia'] ?? ''); ?>"
               pattern="[0-9]{10}" maxlength="10" placeholder="10 d&iacute;gitos">
    </div>
</div>

<div class="row form-row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Correo Institucional</label>
        <input type="email" class="form-control" name="correo_institucional" 
               value="<?php echo htmlspecialchars($_POST['correo_institucional'] ?? $preview_data['correo_institucional'] ?? ''); ?>"
               maxlength="100" placeholder="alumno@cecytenl.edu.mx">
        <div class="info-text">Si se deja vac&iacute;o, se generar&aacute; autom&aacute;ticamente</div>
    </div>
    
    <div class="col-md-4 mb-3">
        <label class="form-label">Correo Personal</label>
        <input type="email" class="form-control" name="correo_personal" 
               value="<?php echo htmlspecialchars($_POST['correo_personal'] ?? $preview_data['correo_personal'] ?? ''); ?>"
               maxlength="100" placeholder="correo@ejemplo.com">
    </div>
</div>
                    
                    <!-- Informaci�n Acad�mica -->
                    <h5 class="section-title mt-4"><i class='bx bx-book'></i> Informaci&oacute;n Acad&eacute;mica</h5>
                    
                    <div class="row form-row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label required">Fecha de Ingreso</label>
                            <input type="date" class="form-control" name="fecha_ingreso" required
                                   value="<?php echo htmlspecialchars($_POST['fecha_ingreso'] ?? $preview_data['fecha_ingreso'] ?? date('Y-m-d')); ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label required">Carrera</label>
                            <select class="form-select" name="id_carrera" required onchange="actualizarGrupos()">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($carreras as $carrera): ?>
                                    <option value="<?php echo $carrera['id_carrera']; ?>"
                                        <?php echo (($_POST['id_carrera'] ?? $preview_data['id_carrera'] ?? '') == $carrera['id_carrera']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($carrera['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label required">Semestre</label>
                            <select class="form-select" name="id_semestre" required onchange="actualizarGrupos()">
                                <option value="">Seleccionar...</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>"
                                        <?php echo (($_POST['id_semestre'] ?? $preview_data['id_semestre'] ?? '') == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> Semestre
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Turno</label>
                            <select class="form-select" name="turno">
                                <option value="">Seleccionar...</option>
                                <option value="M" <?php echo (($_POST['turno'] ?? $preview_data['turno'] ?? '') == 'M') ? 'selected' : ''; ?>>Matutino</option>
                                <option value="V" <?php echo (($_POST['turno'] ?? $preview_data['turno'] ?? '') == 'V') ? 'selected' : ''; ?>>Vespertino</option>
                                <option value="N" <?php echo (($_POST['turno'] ?? $preview_data['turno'] ?? '') == 'N') ? 'selected' : ''; ?>>Nocturno</option>
                            </select>
                        </div>
						
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label required">Acivo / Inactivo</label>
                            <select class="form-select" name="activo" required>
                                <option value="">Seleccionar...</option>
                                <option value="Activo" <?php echo (($_POST['activo'] ?? $preview_data['activo'] ?? 'Activo') == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="Inactivo" <?php echo (($_POST['activo'] ?? $preview_data['activo'] ?? 'Activo') == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                               <!-- <option value="Egresado" <?php echo (($_POST['activo'] ?? $preview_data['activo'] ?? 'Activo') == 'Egresado') ? 'selected' : ''; ?>>Egresado</option>
                                <option value="Baja" <?php echo (($_POST['activo'] ?? $preview_data['activo'] ?? 'Activo') == 'Baja') ? 'selected' : ''; ?>>Baja</option>-->
                            </select>
                        </div>
                    </div>
                    
                    <div class="row form-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Grupo</label>
                            <select class="form-select" name="id_grupo" id="id_grupo">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($grupos as $grupo): ?>
                                    <option value="<?php echo $grupo['id_grupo']; ?>"
                                        <?php echo (($_POST['id_grupo'] ?? $preview_data['id_grupo'] ?? '') == $grupo['id_grupo']) ? 'selected' : ''; ?>
                                        data-carrera="<?php echo $grupo['id_carrera']; ?>"
                                        data-semestre="<?php echo $grupo['semestre']; ?>">
                                        <?php echo htmlspecialchars($grupo['nombre']); ?> - <?php echo htmlspecialchars($grupo['carrera_nombre'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Escuela de Procedencia</label>
                            <input type="text" class="form-control" name="escuela_procedencia" 
                                   value="<?php echo htmlspecialchars($_POST['escuela_procedencia'] ?? $preview_data['escuela_procedencia'] ?? ''); ?>"
                                   maxlength="100" placeholder="Nombre de la escuela secundaria">
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Promedio Secundaria</label>
                            <input type="number" step="0.01" class="form-control" name="promedio_secundaria" 
                                   value="<?php echo htmlspecialchars($_POST['promedio_secundaria'] ?? $preview_data['promedio_secundaria'] ?? ''); ?>"
                                   min="0" max="10" placeholder="0-10">
                        </div>
                    </div>
                    
                    <!-- Informaci�n de Salud -->
                    <h5 class="section-title mt-4"><i class='bx bx-heart'></i> Informaci&oacute;n de Salud</h5>
                    
                    <div class="row form-row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Alergias</label>
                            <input type="text" class="form-control" name="alergias" 
                                   value="<?php echo htmlspecialchars($_POST['alergias'] ?? $preview_data['alergias'] ?? ''); ?>"
                                   maxlength="100" placeholder="Ej: Polen, mariscos, etc.">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Enfermedades Cr&oacute;nicas</label>
                            <input type="text" class="form-control" name="enfermedades_cronicas" 
                                   value="<?php echo htmlspecialchars($_POST['enfermedades_cronicas'] ?? $preview_data['enfermedades_cronicas'] ?? ''); ?>"
                                   maxlength="100" placeholder="Ej: Asma, diabetes, etc.">
                        </div>
                    </div>
                    
                    <div class="row form-row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Seguro M&eacute;dico</label>
                            <input type="text" class="form-control" name="seguro_medico" 
                                   value="<?php echo htmlspecialchars($_POST['seguro_medico'] ?? $preview_data['seguro_medico'] ?? ''); ?>"
                                   maxlength="100" placeholder="Ej: IMSS, ISSSTE, etc.">
                        </div>
                    </div>
                    
                    <!-- Informaci�n Familiar -->
                    <h5 class="section-title mt-4"><i class='bx bx-group'></i> Informaci&oacute;n Familiar</h5>
                    
                    <div class="row form-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nombre del Padre</label>
                            <input type="text" class="form-control" name="nombre_padre" 
                                   value="<?php echo htmlspecialchars($_POST['nombre_padre'] ?? $preview_data['nombre_padre'] ?? ''); ?>"
                                   maxlength="100">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Ocupaci&oacute;n del Padre</label>
                            <input type="text" class="form-control" name="ocupacion_padre" 
                                   value="<?php echo htmlspecialchars($_POST['ocupacion_padre'] ?? $preview_data['ocupacion_padre'] ?? ''); ?>"
                                   maxlength="100">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tel&eacute;fono del Padre</label>
                            <input type="tel" class="form-control" name="telefono_padre" 
                                   value="<?php echo htmlspecialchars($_POST['telefono_padre'] ?? $preview_data['telefono_padre'] ?? ''); ?>"
                                   pattern="[0-9]{10}" maxlength="10">
                        </div>
                    </div>
                    
                    <div class="row form-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nombre de la Madre</label>
                            <input type="text" class="form-control" name="nombre_madre" 
                                   value="<?php echo htmlspecialchars($_POST['nombre_madre'] ?? $preview_data['nombre_madre'] ?? ''); ?>"
                                   maxlength="100">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Ocupaci&oacute;n de la Madre</label>
                            <input type="text" class="form-control" name="ocupacion_madre" 
                                   value="<?php echo htmlspecialchars($_POST['ocupacion_madre'] ?? $preview_data['ocupacion_madre'] ?? ''); ?>"
                                   maxlength="100">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tel&eacute;fono de la Madre</label>
                            <input type="tel" class="form-control" name="telefono_madre" 
                                   value="<?php echo htmlspecialchars($_POST['telefono_madre'] ?? $preview_data['telefono_madre'] ?? ''); ?>"
                                   pattern="[0-9]{10}" maxlength="10">
                        </div>

                        <div class="col-md-4 mb-3">
        <label class="form-label">Correo del Tutor</label>
        <input type="email" class="form-control" name="correo_tutor" 
               value="<?php echo htmlspecialchars($_POST['correo_tutor'] ?? $preview_data['correo_tutor'] ?? ''); ?>"
               maxlength="100" placeholder="tutor@ejemplo.com">
    </div>
                    </div>
                    
                    <!-- Informaci�n Adicional -->
                    <h5 class="section-title mt-4"><i class='bx bx-note'></i> Informaci&oacute;n Adicional</h5>
                    
                    <div class="row form-row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Direcci&oacute;n</label>
                            <textarea class="form-control" name="direccion" rows="2" maxlength="255"
                                      placeholder="Calle, n&uacute;mero, colonia, etc."><?php echo htmlspecialchars($_POST['direccion'] ?? $preview_data['direccion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Colonia</label>
                            <input type="text" class="form-control" name="colonia" 
                                   value="<?php echo htmlspecialchars($_POST['colonia'] ?? $preview_data['colonia'] ?? ''); ?>"
                                   maxlength="100">
                        </div>
                    </div>
                    
                    <div class="row form-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Porcentaje de Beca</label>
                            <input type="number" class="form-control" name="porcentaje_beca" 
                                   value="<?php echo htmlspecialchars($_POST['porcentaje_beca'] ?? $preview_data['porcentaje_beca'] ?? '0'); ?>"
                                   min="0" max="100" step="1">
                            <div class="info-text">0-100%</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Beca</label>
                            <select class="form-select" name="beca">
                                <option value="NO" <?php echo (($_POST['beca'] ?? $preview_data['beca'] ?? 'NO') == 'NO') ? 'selected' : ''; ?>>NO</option>
                                <option value="SI" <?php echo (($_POST['beca'] ?? $preview_data['beca'] ?? 'NO') == 'SI') ? 'selected' : ''; ?>>SI</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row form-row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="3" maxlength="255"
                                      placeholder="Observaciones adicionales..."><?php echo htmlspecialchars($_POST['observaciones'] ?? $preview_data['observaciones'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Botones de acci�n -->
                    <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                        <a href="gestion_alumnos.php" class="btn btn-cancel">
                            <i class='bx bx-arrow-back'></i> Cancelar
                        </a>
                        <div>
                            <button type="submit" name="preview" class="btn btn-preview">
                                <i class='bx bx-show'></i> Ver Datos
                            </button>
                            <button type="submit" name="guardar" class="btn btn-cecyte ms-2">
                                <i class='bx bx-save'></i> Guardar Alumno
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
                    <h5 class="modal-title"><i class='bx bx-show'></i> Previsualizaci&oacute;n de Datos del Alumno</h5>
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
                                <span class="preview-label">Matr&iacute;cula:</span>
                                <span class="preview-value"><?php echo htmlspecialchars($preview_data['matricula']); ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Nombre Completo:</span>
                                <span class="preview-value"><?php echo htmlspecialchars($preview_data['nombre'] . ' ' . $preview_data['apellido_paterno'] . ' ' . $preview_data['apellido_materno']); ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">CURP:</span>
                                <span class="preview-value"><?php echo htmlspecialchars($preview_data['curp']); ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">RFC:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['rfc']) ? htmlspecialchars($preview_data['rfc']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Fecha de Nacimiento:</span>
                                <span class="preview-value"><?php echo htmlspecialchars($preview_data['fecha_nacimiento']); ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">G&eacute;nero:</span>
                                <span class="preview-value">
                                    <?php 
                                    $genero_text = '';
                                    switch($preview_data['id_genero']) {
                                        case '1': $genero_text = 'Masculino'; break;
                                        case '2': $genero_text = 'Femenino'; break;
                                        case '3': $genero_text = 'Otro'; break;
                                        default: $genero_text = 'No especificado';
                                    }
                                    echo $genero_text;
                                    ?>
                                </span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Tipo de Sangre:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['tipo_sangre']) ? htmlspecialchars($preview_data['tipo_sangre']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                        </div>
                        
                        <div class="preview-section mb-4">
                            <h6 class="section-title mb-3"><i class='bx bx-envelope'></i> Informaci�n de Contacto</h6>
                            <div class="preview-item">
                                <span class="preview-label">Tel&eacute;fono Celular:</span>
                                <span class="preview-value"><?php echo htmlspecialchars($preview_data['telefono_celular']); ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Tel&eacute;fono de Casa:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['telefono_casa']) ? htmlspecialchars($preview_data['telefono_casa']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Tel&eacute;fono de Emergencia:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['telefono_emergencia']) ? htmlspecialchars($preview_data['telefono_emergencia']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Correo Institucional:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['correo_institucional']) ? htmlspecialchars($preview_data['correo_institucional']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Correo Personal:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['correo_personal']) ? htmlspecialchars($preview_data['correo_personal']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                        </div>
                        
                        <div class="preview-section mb-4">
                            <h6 class="section-title mb-3"><i class='bx bx-book'></i> Informaci&oacute;n Acad&eacute;mica</h6>
                            <div class="preview-item">
                                <span class="preview-label">Fecha de Ingreso:</span>
                                <span class="preview-value"><?php echo htmlspecialchars($preview_data['fecha_ingreso']); ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Carrera:</span>
                                <span class="preview-value">
                                    <?php 
                                    $carrera_nombre = '';
                                    foreach ($carreras as $carrera) {
                                        if ($carrera['id_carrera'] == $preview_data['id_carrera']) {
                                            $carrera_nombre = $carrera['nombre'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($carrera_nombre ?: 'No especificada');
                                    ?>
                                </span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Semestre:</span>
                                <span class="preview-value"><?php echo htmlspecialchars($preview_data['id_semestre']) . ' Semestre'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Turno:</span>
                                <span class="preview-value">
                                    <?php 
                                    $turno_text = '';
                                    switch($preview_data['turno'] ?? '') {
                                        case 'M': $turno_text = 'Matutino'; break;
                                        case 'V': $turno_text = 'Vespertino'; break;
                                        case 'N': $turno_text = 'Nocturno'; break;
                                        default: $turno_text = 'No especificado';
                                    }
                                    echo $turno_text;
                                    ?>
                                </span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Estatus:</span>
                                <span class="preview-value"><?php echo htmlspecialchars($preview_data['activo']); ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Grupo:</span>
                                <span class="preview-value">
                                    <?php 
                                    $grupo_nombre = '';
                                    foreach ($grupos as $grupo) {
                                        if ($grupo['id_grupo'] == $preview_data['id_grupo']) {
                                            $grupo_nombre = $grupo['nombre'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($grupo_nombre ?: 'No especificado');
                                    ?>
                                </span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Escuela de Procedencia:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['escuela_procedencia']) ? htmlspecialchars($preview_data['escuela_procedencia']) : '<span class="preview-empty">No especificada</span>'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Promedio Secundaria:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['promedio_secundaria']) ? htmlspecialchars($preview_data['promedio_secundaria']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                        </div>
                        
                        <div class="preview-section mb-4">
                            <h6 class="section-title mb-3"><i class='bx bx-group'></i> Informaci&oacute;n Familiar</h6>
                            <div class="preview-item">
                                <span class="preview-label">Nombre del Padre:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['nombre_padre']) ? htmlspecialchars($preview_data['nombre_padre']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Ocupaci&oacute;n del Padre:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['ocupacion_padre']) ? htmlspecialchars($preview_data['ocupacion_padre']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Tel&eacute;fono del Padre:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['telefono_padre']) ? htmlspecialchars($preview_data['telefono_padre']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Nombre de la Madre:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['nombre_madre']) ? htmlspecialchars($preview_data['nombre_madre']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Ocupaci&oacute;n de la Madre:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['ocupacion_madre']) ? htmlspecialchars($preview_data['ocupacion_madre']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Tel&eacute;fono de la Madre:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['telefono_madre']) ? htmlspecialchars($preview_data['telefono_madre']) : '<span class="preview-empty">No especificado</span>'; ?></span>
                            </div>
                        </div>
                        
                        <div class="preview-section mb-4">
                            <h6 class="section-title mb-3"><i class='bx bx-note'></i> Informaci&oacute;n Adicional</h6>
                            <div class="preview-item">
                                <span class="preview-label">Porcentaje de Beca:</span>
                                <span class="preview-value"><?php echo htmlspecialchars($preview_data['porcentaje_beca']); ?>%</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Beca:</span>
                                <span class="preview-value"><?php echo htmlspecialchars($preview_data['beca']); ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Direcci&oacute;n:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['direccion']) ? nl2br(htmlspecialchars($preview_data['direccion'])) : '<span class="preview-empty">No especificada</span>'; ?></span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Colonia:</span>
                                <span class="preview-value"><?php echo !empty($preview_data['colonia']) ? htmlspecialchars($preview_data['colonia']) : '<span class="preview-empty">No especificada</span>'; ?></span>
                            </div>
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
                        <?php if ($preview_data): ?>
                            <?php foreach ($preview_data as $key => $value): ?>
                                <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
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
        // Funciones de utilidad
        function generarMatricula() {
            const year = new Date().getFullYear();
            const matriculaInput = document.querySelector('input[name="matricula"]');
            
            // Generar n�mero aleatorio entre 1 y 999
            const numero = Math.floor(Math.random() * 999) + 1;
            const matricula = year + numero.toString().padStart(3, '0');
            
            matriculaInput.value = matricula;
            
            // Actualizar correo institucional si est� vac�o
            const correoInput = document.querySelector('input[name="correo_institucional"]');
            if (!correoInput.value || correoInput.value.includes('@')) {
                correoInput.value = matricula.toLowerCase() + '@cecytenl.edu.mx';
            }
        }
        
        function calcularEdad() {
            const fechaNacimiento = new Date(document.querySelector('input[name="fecha_nacimiento"]').value);
            if (isNaN(fechaNacimiento.getTime())) return;
            
            const hoy = new Date();
            let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
            const mes = hoy.getMonth() - fechaNacimiento.getMonth();
            
            if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
                edad--;
            }
            
            const edadDisplay = document.getElementById('edad-display');
            edadDisplay.textContent = `Edad: ${edad} a�os`;
            
            if (edad < 15) {
                edadDisplay.style.color = 'red';
                edadDisplay.innerHTML += ' <small>(Edad m�nima: 15 a�os)</small>';
            } else if (edad > 25) {
                edadDisplay.style.color = 'orange';
                edadDisplay.innerHTML += ' <small>(�Edad correcta?)</small>';
            } else {
                edadDisplay.style.color = 'green';
            }
        }
        
        function validarCURP() {
            const curpInput = document.querySelector('input[name="curp"]');
            const curp = curpInput.value.toUpperCase();
            const feedback = document.getElementById('curp-feedback');
            
            const pattern = /^[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z]{2}$/;
            
            if (pattern.test(curp)) {
                curpInput.style.borderColor = 'green';
                feedback.style.color = 'green';
                feedback.innerHTML = '<i class="bx bx-check-circle"></i> CURP v�lido';
                return true;
            } else {
                curpInput.style.borderColor = 'red';
                feedback.style.color = 'red';
                feedback.innerHTML = '<i class="bx bx-error-circle"></i> Formato inv�lido (18 caracteres alfanum�ricos)';
                return false;
            }
        }
        
        function actualizarGrupos() {
            const carreraId = document.querySelector('select[name="id_carrera"]').value;
            const semestre = document.querySelector('select[name="id_semestre"]').value;
            const grupoSelect = document.getElementById('id_grupo');
            const currentValue = grupoSelect.value;
            
            // Filtrar grupos por carrera y semestre
            const allOptions = <?php echo json_encode($grupos); ?>;
            
            // Limpiar opciones excepto la primera
            while (grupoSelect.options.length > 1) {
                grupoSelect.remove(1);
            }
            
            // Agregar opciones filtradas
            allOptions.forEach(grupo => {
                if ((!carreraId || grupo.id_carrera == carreraId) && 
                    (!semestre || grupo.semestre == semestre)) {
                    const option = document.createElement('option');
                    option.value = grupo.id_grupo;
                    option.textContent = `${grupo.nombre} - ${grupo.carrera_nombre}`;
                    grupoSelect.appendChild(option);
                }
            });
            
            // Restaurar valor seleccionado si a�n existe
            if (currentValue) {
                grupoSelect.value = currentValue;
            }
        }
        
        // Validaci�n de formulario
        document.getElementById('formAlumno').addEventListener('submit', function(e) {
            // Si es el bot�n de previsualizaci�n, validar pero no mostrar confirmaci�n
            if (e.submitter && e.submitter.name === 'preview') {
                const camposRequeridos = [
                    'matricula', 'nombre', 'apellido_paterno', 'fecha_nacimiento',
                    'curp', 'id_genero', 'telefono_celular', 'fecha_ingreso',
                    'id_carrera', 'id_semestre'
                ];
                
                // Validar campos requeridos
                for (let campo of camposRequeridos) {
                    const input = this.querySelector(`[name="${campo}"]`);
                    if (input && !input.value.trim()) {
                        e.preventDefault();
                        alert(`El campo "${campo.replace('_', ' ')}" es requerido.`);
                        input.focus();
                        return false;
                    }
                }
                
                // Validar CURP
                const curp = this.querySelector('input[name="curp"]').value.trim();
                const curpPattern = /^[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z]{2}$/;
                if (curp && !curpPattern.test(curp.toUpperCase())) {
                    e.preventDefault();
                    alert('El CURP no tiene un formato v�lido (18 caracteres alfanum�ricos).');
                    return false;
                }
                
                // Validar tel�fono celular
                const telefono = this.querySelector('input[name="telefono_celular"]').value.trim();
                if (telefono && !/^[0-9]{10}$/.test(telefono)) {
                    e.preventDefault();
                    alert('El tel�fono celular debe tener 10 d&iacute;gitos.');
                    return false;
                }
                
                return true;
            }
            
            // Si es el bot�n de guardar
            if (e.submitter && e.submitter.name === 'guardar') {
                const confirmar = confirm('�Est� seguro de guardar los datos del nuevo alumno?');
                if (!confirmar) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
        
        // Formato autom�tico para CURP y RFC
        const curpInput = document.querySelector('input[name="curp"]');
        if (curpInput) {
            curpInput.addEventListener('input', function(e) {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 18);
            });
        }
        
        const rfcInput = document.querySelector('input[name="rfc"]');
        if (rfcInput) {
            rfcInput.addEventListener('input', function(e) {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9&�]/g, '').substring(0, 13);
            });
        }
        
        // Formato para tel�fonos (solo n�meros)
        const telefonoInputs = document.querySelectorAll('input[type="tel"]');
        telefonoInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
            });
        });
        
        // Auto-generar correo institucional
        const matriculaInput = document.querySelector('input[name="matricula"]');
        if (matriculaInput) {
            matriculaInput.addEventListener('blur', function() {
                const correoInput = document.querySelector('input[name="correo_institucional"]');
                const matricula = this.value.trim();
                
                if (matricula && (!correoInput.value || correoInput.value.includes('@'))) {
                    correoInput.value = matricula.toLowerCase() + '@cecytenl.edu.mx';
                }
            });
        }
        
        // Mostrar modal de previsualizaci�n si hay datos
        <?php if ($show_preview_modal): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            previewModal.show();
            
            // Limpiar par�metro de URL
            const url = new URL(window.location.href);
            url.searchParams.delete('preview');
            window.history.replaceState({}, document.title, url.toString());
        });
        <?php endif; ?>
        
        // Inicializaci�n
        document.addEventListener('DOMContentLoaded', function() {
            calcularEdad();
            actualizarGrupos();
        });
    </script>
</body>
</html>
