<?php
// editar_maestro.php
session_start();
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Verificar que se proporcionó el número de empleado
if (!isset($_GET['numEmpleado']) || empty($_GET['numEmpleado'])) {
    $_SESSION['error'] = "No se especificó el número de empleado.";
    header('Location: gestion_maestros.php');
    exit();
}

$numEmpleado = trim($_GET['numEmpleado']);

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtener datos actuales del maestro
try {
    $sql_maestro = "SELECT m.*, g.genero, n.nacionalidad, e.estado 
                    FROM maestros m 
                    LEFT JOIN generos g ON m.id_genero = g.id_genero 
                    LEFT JOIN nacionalidades n ON m.id_nacionalidad = n.id_nacionalidad 
                    LEFT JOIN estados e ON m.id_estado = e.id_estado 
                    WHERE m.numEmpleado = :numEmpleado";
    
    $stmt_maestro = $con->prepare($sql_maestro);
    $stmt_maestro->bindParam(':numEmpleado', $numEmpleado);
    $stmt_maestro->execute();
    $maestro = $stmt_maestro->fetch(PDO::FETCH_ASSOC);
    
    if (!$maestro) {
        $_SESSION['error'] = "Maestro no encontrado.";
        header('Location: gestion_maestros.php');
        exit();
    }
    
    // Obtener datos para formularios (selects)
    $sql_generos = "SELECT * FROM generos ORDER BY genero";
    $generos = $con->query($sql_generos)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_nacionalidades = "SELECT * FROM nacionalidades ORDER BY nacionalidad";
    $nacionalidades = $con->query($sql_nacionalidades)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_estados = "SELECT * FROM estados ORDER BY estado";
    $estados = $con->query($sql_estados)->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener datos del maestro: " . $e->getMessage());
}

// Procesar formulario de actualización
$mensajes = [];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errores[] = "Token de seguridad inválido. Por favor, recarga la página.";
    } else {
        // Recoger y validar datos
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
        $especialidad = trim($_POST['especialidad'] ?? '');
        $titulo = trim($_POST['titulo'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $activo = $_POST['activo'] ?? 'Activo';

        // Validaciones básicas
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
        if (!empty($rfc) && !preg_match('/^[A-Z&N]{3,4}[0-9]{6}[A-Z0-9]{3}$/', strtoupper($rfc))) {
            $errores[] = "El RFC no tiene un formato válido";
        }
        
        // Validar CURP si se proporciona
        if (!empty($curp) && !preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$/', strtoupper($curp))) {
            $errores[] = "El CURP no tiene un formato válido";
        }

        // Si no hay errores, actualizar en la base de datos
        if (empty($errores)) {
            try {
                $sql = "UPDATE maestros SET 
                        nombre = :nombre,
                        apellido_paterno = :apellido_paterno,
                        apellido_materno = :apellido_materno,
                        fecha_nacimiento = :fecha_nacimiento,
                        id_genero = :id_genero,
                        rfc = :rfc,
                        curp = :curp,
                        id_nacionalidad = :id_nacionalidad,
                        id_estado = :id_estado,
                        direccion = :direccion,
                        telefono_celular = :telefono_celular,
                        telefono_emergencia = :telefono_emergencia,
                        correo_institucional = :correo_institucional,
                        correo_personal = :correo_personal,
                        estado_civil = :estado_civil,
                        especialidad = :especialidad,
                        titulo = :titulo,
                        observaciones = :observaciones,
                        activo = :activo,
                        fechaModificacion = NOW()
                        WHERE numEmpleado = :numEmpleado";
                
                $stmt = $con->prepare($sql);
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
                $stmt->bindParam(':numEmpleado', $numEmpleado);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Maestro actualizado exitosamente.";
                    header('Location: ver_maestro.php?numEmpleado=' . urlencode($numEmpleado) . '&success=updated');
                    exit();
                } else {
                    $errores[] = "Error al actualizar el maestro.";
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errores[] = "El correo electrónico ya existe para otro maestro.";
                } else {
                    $errores[] = "Error de base de datos: " . $e->getMessage();
                }
            }
        }
    }
}

// Si hay datos POST, usarlos; de lo contrario, usar datos actuales
$form_data = $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $maestro;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Maestro - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --verde-oscuro: #1a5330;      /* Verde muy oscuro */
            --verde-principal: #2e7d32;   /* Verde oscuro */
            --verde-medio: #4caf50;       /* Verde medio */
            --verde-claro: #8bc34a;       /* Verde claro */
            --verde-muy-claro: #c8e6c9;   /* Verde muy claro */
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header-cecyte {
            background: linear-gradient(135deg, var(--verde-oscuro), var(--verde-principal));
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 4px solid var(--verde-medio);
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
            background: linear-gradient(to right, var(--verde-oscuro), var(--verde-principal));
            color: white;
            padding: 20px;
            border-bottom: 1px solid var(--verde-medio);
        }
        
        .form-body {
            padding: 30px;
            background-color: var(--verde-muy-claro);
        }
        
        .section-title {
            color: var(--verde-oscuro);
            border-bottom: 2px solid var(--verde-claro);
            padding-bottom: 8px;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--verde-oscuro);
            margin-bottom: 5px;
        }
        
        .form-label.required::after {
            content: " *";
            color: #dc3545;
        }
        
        .form-control, .form-select {
            border: 1px solid var(--verde-claro);
            border-radius: 5px;
            padding: 10px;
            transition: all 0.3s;
            background-color: white;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--verde-principal);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
        }
        
        .alert-box {
            background-color: var(--verde-muy-claro);
            border-left: 4px solid var(--verde-principal);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .btn-cecyte {
            background: var(--verde-principal);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-cecyte:hover {
            background: var(--verde-oscuro);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(26, 83, 48, 0.2);
        }
        
        .btn-preview {
            background: var(--verde-medio);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-preview:hover {
            background: var(--verde-principal);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 125, 50, 0.2);
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
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--verde-claro);
        }
        
        .badge-cecyte {
            background-color: var(--verde-oscuro);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        
        .info-card {
            background: white;
            border-left: 4px solid var(--verde-principal);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        @media (max-width: 768px) {
            .form-body {
                padding: 20px;
            }
            
            .form-row {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header-cecyte">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class='bx bx-user-edit'></i> Editar Maestro</h2>
                    <p class="mb-0">Sistema de Gesti&oacute;n Escolar - CECyTE</p>
                </div>
                <div>
                    <a href="gestion_maestros.php" class="btn btn-light">
                        <i class='bx bx-arrow-back'></i> Volver a la Lista
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Información actual del maestro -->
        <div class="info-card">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class='bx bx-info-circle'></i> Informaci&oacute;n Actual</h5>
                    <p class="mb-1"><strong>N&uacute;mero de Empleado:</strong> <span class="badge-cecyte"><?php echo htmlspecialchars($maestro['numEmpleado']); ?></span></p>
                    <p class="mb-1"><strong>Nombre:</strong> <?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido_paterno'] . ' ' . $maestro['apellido_materno']); ?></p>
                    <p class="mb-0"><strong>Fecha de Alta:</strong> <?php echo date('d/m/Y', strtotime($maestro['fechaAlta'])); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Correo Institucional:</strong> <?php echo htmlspecialchars($maestro['correo_institucional']); ?></p>
                    <p class="mb-1"><strong>G&eacute;nero:</strong> <?php echo htmlspecialchars($maestro['genero']); ?></p>
                    <p class="mb-0"><strong>Estatus:</strong> <span class="badge <?php echo $maestro['activo'] == 'Activo' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo htmlspecialchars($maestro['activo']); ?></span></p>
                </div>
            </div>
        </div>
        
        <div class="form-container">
            <div class="form-header">
                <h4 class="mb-0"><i class='bx bx-edit-alt'></i> Editar Informaci&oacute;n del Maestro</h4>
                <p class="mb-0">Modifique los campos que desee actualizar. Campos obligatorios (*)</p>
            </div>
            
            <div class="form-body">
                <div class="alert-box">
                    <h6><i class='bx bx-info-circle'></i> Informaci&oacute;n importante</h6>
                    <p class="mb-0">El n&uacute;mero de empleado no se puede modificar. Aseg&uacute;rese de que el correo electr&oacute;nico institucional no est&eacute; registrado por otro maestro.</p>
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
                
                <form method="POST" action="" id="formEditarMaestro">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Datos Básicos -->
                    <h5 class="section-title"><i class='bx bx-user'></i> Datos Personales</h5>
                    
                    <div class="form-row">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">N&uacute;mero de Empleado</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($maestro['numEmpleado']); ?>" readonly disabled>
                                <div class="info-text">Identificador &uacute;nico (no modificable)</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Nombre(s)</label>
                                <input type="text" class="form-control" name="nombre" required 
                                       value="<?php echo htmlspecialchars($form_data['nombre'] ?? ''); ?>"
                                       maxlength="100" placeholder="Ej: Juan Carlos">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Apellido Paterno</label>
                                <input type="text" class="form-control" name="apellido_paterno" required 
                                       value="<?php echo htmlspecialchars($form_data['apellido_paterno'] ?? ''); ?>"
                                       maxlength="50" placeholder="Ej: Gonz&aacute;lez">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellido Materno</label>
                                <input type="text" class="form-control" name="apellido_materno" 
                                       value="<?php echo htmlspecialchars($form_data['apellido_materno'] ?? ''); ?>"
                                       maxlength="50" placeholder="Ej: L&oacute;pez">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" name="fecha_nacimiento" required
                                       value="<?php echo htmlspecialchars($form_data['fecha_nacimiento'] ?? ''); ?>">
                                <div class="info-text">Formato: AAAA-MM-DD</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">G&eacute;nero</label>
                                <select class="form-select" name="id_genero" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($generos as $genero): ?>
                                        <option value="<?php echo $genero['id_genero']; ?>" 
                                            <?php echo (($form_data['id_genero'] ?? '') == $genero['id_genero']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($genero['genero']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Estado Civil</label>
                                <input type="text" class="form-control" name="estado_civil" 
                                       value="<?php echo htmlspecialchars($form_data['estado_civil'] ?? ''); ?>"
                                       maxlength="20" placeholder="Ej: Soltero">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información Profesional -->
                    <h5 class="section-title mt-4"><i class='bx bx-briefcase'></i> Informaci&oacute;n Profesional</h5>
                    
                    <div class="form-row">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Especialidad</label>
                                <input type="text" class="form-control" name="especialidad" 
                                       value="<?php echo htmlspecialchars($form_data['especialidad'] ?? ''); ?>"
                                       maxlength="50" placeholder="Ej: Matem&aacute;ticas">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">T&iacute;tulo</label>
                                <input type="text" class="form-control" name="titulo" 
                                       value="<?php echo htmlspecialchars($form_data['titulo'] ?? ''); ?>"
                                       maxlength="30" placeholder="Ej: Licenciatura">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Documentos e Identificación -->
                    <h5 class="section-title mt-4"><i class='bx bx-id-card'></i> Documentos de Identificaci&oacute;n</h5>
                    
                    <div class="form-row">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">RFC</label>
                                <input type="text" class="form-control" name="rfc" 
                                       value="<?php echo htmlspecialchars($form_data['rfc'] ?? ''); ?>" 
                                       maxlength="13" placeholder="Ej: GOLJ800101ABC">
                                <div class="info-text">13 caracteres (opcional)</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">CURP</label>
                                <input type="text" class="form-control" name="curp" 
                                       value="<?php echo htmlspecialchars($form_data['curp'] ?? ''); ?>" 
                                       maxlength="18" placeholder="Ej: GOLJ800101HDFNPS09">
                                <div class="info-text">18 caracteres (opcional)</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">Nacionalidad</label>
                                <select class="form-select" name="id_nacionalidad" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($nacionalidades as $nacionalidad): ?>
                                        <option value="<?php echo $nacionalidad['id_nacionalidad']; ?>" 
                                            <?php echo (($form_data['id_nacionalidad'] ?? '') == $nacionalidad['id_nacionalidad']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($nacionalidad['nacionalidad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Estado de Nacimiento</label>
                                <select class="form-select" name="id_estado" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($estados as $estado): ?>
                                        <option value="<?php echo $estado['id_estado']; ?>" 
                                            <?php echo (($form_data['id_estado'] ?? '') == $estado['id_estado']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($estado['estado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Activo / Inactivo</label>
                                <select class="form-select" name="activo">
                                    <option value="Activo" <?php echo (($form_data['activo'] ?? '') == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="Inactivo" <?php echo (($form_data['activo'] ?? '') == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información de Contacto -->
                    <h5 class="section-title mt-4"><i class='bx bx-envelope'></i> Informaci&oacute;n de Contacto</h5>
                    
                    <div class="form-row">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Correo Institucional</label>
                                <input type="email" class="form-control" name="correo_institucional" required 
                                       value="<?php echo htmlspecialchars($form_data['correo_institucional'] ?? ''); ?>"
                                       maxlength="100" placeholder="Ej: maestro@cecyte.edu.mx">
                                <div class="info-text">Correo oficial de la instituci&oacute;n</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo Personal</label>
                                <input type="email" class="form-control" name="correo_personal" 
                                       value="<?php echo htmlspecialchars($form_data['correo_personal'] ?? ''); ?>"
                                       maxlength="100" placeholder="Ej: ejemplo@gmail.com">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">N&uacute;mero de Celular</label>
                                <input type="text" class="form-control" name="telefono_celular" 
                                       value="<?php echo htmlspecialchars($form_data['telefono_celular'] ?? ''); ?>"
                                       maxlength="15" placeholder="Ej: 8123456789">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tel&eacute;fono de Emergencia</label>
                                <input type="text" class="form-control" name="telefono_emergencia" 
                                       value="<?php echo htmlspecialchars($form_data['telefono_emergencia'] ?? ''); ?>"
                                       maxlength="15" placeholder="Ej: 8187654321">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dirección -->
                    <h5 class="section-title mt-4"><i class='bx bx-home'></i> Direcci&oacute;n</h5>
                    
                    <div class="form-row">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Direcci&oacute;n Completa</label>
                                <textarea class="form-control" name="direccion" rows="3" maxlength="255" 
                                          placeholder="Calle, n&uacute;mero, colonia, etc."><?php echo htmlspecialchars($form_data['direccion'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observaciones -->
                    <h5 class="section-title mt-4"><i class='bx bx-note'></i> Observaciones</h5>
                    
                    <div class="form-row">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" rows="2" maxlength="255"
                                          placeholder="Observaciones adicionales..."><?php echo htmlspecialchars($form_data['observaciones'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                        <div>
                            <a href="ver_maestro2.php?numEmpleado=<?php echo urlencode($numEmpleado); ?>" class="btn btn-cancel me-2">
                                <i class='bx bx-show'></i> Ver Maestro
                            </a>
                            <a href="gestion_maestros.php" class="btn btn-secondary">
                                <i class='bx bx-arrow-back'></i> Cancelar
                            </a>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-cecyte">
                                <i class='bx bx-save'></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de formulario
        document.getElementById('formEditarMaestro').addEventListener('submit', function(e) {
            const nombre = this.querySelector('input[name="nombre"]').value.trim();
            const apellido_paterno = this.querySelector('input[name="apellido_paterno"]').value.trim();
            const fecha_nacimiento = this.querySelector('input[name="fecha_nacimiento"]').value.trim();
            const correo_institucional = this.querySelector('input[name="correo_institucional"]').value.trim();
            const id_genero = this.querySelector('select[name="id_genero"]').value;
            const id_nacionalidad = this.querySelector('select[name="id_nacionalidad"]').value;
            const id_estado = this.querySelector('select[name="id_estado"]').value;
            
            // Validar campos requeridos
            if (!nombre || !apellido_paterno || !fecha_nacimiento || !correo_institucional || !id_genero || !id_nacionalidad || !id_estado) {
                e.preventDefault();
                alert('Por favor, complete todos los campos requeridos (*).');
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
            if (rfc && !/^[A-Z&N]{3,4}[0-9]{6}[A-Z0-9]{3}$/.test(rfc.toUpperCase())) {
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
            
            const confirmar = confirm('¿Est&aacute; seguro de guardar los cambios en los datos del maestro?');
            if (!confirmar) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        // Formato automático para RFC y CURP
        const rfcInput = document.querySelector('input[name="rfc"]');
        if (rfcInput) {
            rfcInput.addEventListener('input', function(e) {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9&N]/g, '').substring(0, 13);
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
        
        // Validación en tiempo real de correo personal si se escribe
        const correoPersonal = document.querySelector('input[name="correo_personal"]');
        if (correoPersonal) {
            correoPersonal.addEventListener('blur', function() {
                if (this.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
                    alert('El correo personal no tiene un formato válido');
                    this.focus();
                }
            });
        }
        
        // Autoformato de estado civil (primera letra mayúscula)
        const estadoCivil = document.querySelector('input[name="estado_civil"]');
        if (estadoCivil) {
            estadoCivil.addEventListener('blur', function() {
                if (this.value) {
                    this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase();
                }
            });
        }
        
        // Autoformato de especialidad y título (primera letra mayúscula)
        const especialidad = document.querySelector('input[name="especialidad"]');
        if (especialidad) {
            especialidad.addEventListener('blur', function() {
                if (this.value) {
                    this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase();
                }
            });
        }
        
        const titulo = document.querySelector('input[name="titulo"]');
        if (titulo) {
            titulo.addEventListener('blur', function() {
                if (this.value) {
                    this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase();
                }
            });
        }
    </script>
</body>
</html>