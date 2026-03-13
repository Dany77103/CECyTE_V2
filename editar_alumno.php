<?php
// editar_alumnos.php - VERSIÓN CORREGIDA
session_start();
require_once 'conexion.php';

// ========== VERIFICAR SESIÓN Y PERMISOS ==========
// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Verificar permisos (asumiendo que solo admins pueden editar)
// Si no tienes rol, comenta esta línea o ajusta según tus necesidades
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Si no es admin, puedes redirigir o mostrar error
    // header('Location: main.php?error=no_autorizado');
    // exit();
}

// ========== VALIDAR PARÁMETROS GET ==========
if (!isset($_GET['matriculaAlumno']) || empty($_GET['matriculaAlumno'])) {
    // No recibimos matrícula válida
    header('Location: lista_alumnos.php?error=matricula_invalida');
    exit();
}

$matricula = trim($_GET['matriculaAlumno']);

// ========== OBTENER DATOS DEL ALUMNO ==========
try {
    $sql = "SELECT * FROM alumnos WHERE matriculaAlumno = :matricula";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Alumno no encontrado
        header('Location: lista_alumnos.php?error=alumno_no_encontrado');
        exit();
    }
    
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener datos del alumno: " . $e->getMessage());
}

// ========== GENERAR TOKEN CSRF ==========
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ========== PROCESAR FORMULARIO POST ==========
$mensajes = [];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errores[] = "Token de seguridad inválido. Por favor, recarga la página.";
    } else {
        // Validaciones
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidoPaterno = trim($_POST['apellidoPaterno'] ?? '');
        $apellidoMaterno = trim($_POST['apellidoMaterno'] ?? '');
        $mailInstitucional = trim($_POST['mailInstitucional'] ?? '');
        $mailPersonal = trim($_POST['mailPersonal'] ?? '');
        $fechaNacimiento = trim($_POST['fechaNacimiento'] ?? '');
        $numCelular = trim($_POST['numCelular'] ?? '');
        $id_genero = $_POST['id_genero'] ?? '';
        $id_nacionalidad = $_POST['id_nacionalidad'] ?? '';
        $id_estadoNacimiento = $_POST['id_estadoNacimiento'] ?? '';
        
        // Validar campos requeridos
        if (empty($nombre)) $errores[] = "El nombre es requerido";
        if (empty($apellidoPaterno)) $errores[] = "El apellido paterno es requerido";
        if (empty($mailInstitucional)) $errores[] = "El correo institucional es requerido";
        
        // Validar formato de email
        if (!filter_var($mailInstitucional, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo institucional no tiene un formato válido";
        }
        
        // Validar email personal si se proporciona
        if (!empty($mailPersonal) && !filter_var($mailPersonal, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo personal no tiene un formato válido";
        }
        
        // Validar fecha
        if (!empty($fechaNacimiento)) {
            $fecha = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
            if (!$fecha || $fecha->format('Y-m-d') !== $fechaNacimiento) {
                $errores[] = "La fecha de nacimiento no es válida (formato: AAAA-MM-DD)";
            }
        }
        
        // Si no hay errores, proceder con la actualización
        if (empty($errores)) {
            try {
                $sql_update = "UPDATE alumnos SET 
                    nombre = :nombre,
                    apellidoPaterno = :apellidoPaterno,
                    apellidoMaterno = :apellidoMaterno,
                    fechaNacimiento = :fechaNacimiento,
                    id_genero = :id_genero,
                    rfc = :rfc,
                    id_nacionalidad = :id_nacionalidad,
                    id_estadoNacimiento = :id_estadoNacimiento,
                    direccion = :direccion,
                    numCelular = :numCelular,
                    telefonoEmergencia = :telefonoEmergencia,
                    mailInstitucional = :mailInstitucional,
                    mailPersonal = :mailPersonal,
                    id_discapacidad = :id_discapacidad,
                    fechaModificacion = CURRENT_TIMESTAMP
                    WHERE matriculaAlumno = :matricula";
                
                $stmt_update = $con->prepare($sql_update);
                
                // Asignar parámetros
                $stmt_update->bindParam(':matricula', $matricula, PDO::PARAM_STR);
                $stmt_update->bindParam(':nombre', $nombre, PDO::PARAM_STR);
                $stmt_update->bindParam(':apellidoPaterno', $apellidoPaterno, PDO::PARAM_STR);
                $stmt_update->bindParam(':apellidoMaterno', $apellidoMaterno, PDO::PARAM_STR);
                $stmt_update->bindParam(':fechaNacimiento', $fechaNacimiento, PDO::PARAM_STR);
                $stmt_update->bindParam(':id_genero', $id_genero, PDO::PARAM_INT);
                $stmt_update->bindParam(':rfc', $_POST['rfc'] ?? null, PDO::PARAM_STR);
                $stmt_update->bindParam(':id_nacionalidad', $id_nacionalidad, PDO::PARAM_INT);
                $stmt_update->bindParam(':id_estadoNacimiento', $id_estadoNacimiento, PDO::PARAM_INT);
                $stmt_update->bindParam(':direccion', $_POST['direccion'] ?? null, PDO::PARAM_STR);
                $stmt_update->bindParam(':numCelular', $numCelular, PDO::PARAM_STR);
                $stmt_update->bindParam(':telefonoEmergencia', $_POST['telefonoEmergencia'] ?? null, PDO::PARAM_STR);
                $stmt_update->bindParam(':mailInstitucional', $mailInstitucional, PDO::PARAM_STR);
                $stmt_update->bindParam(':mailPersonal', $mailPersonal, PDO::PARAM_STR);
                $stmt_update->bindParam(':id_discapacidad', $_POST['id_discapacidad'] ?? null, PDO::PARAM_INT);
                
                if ($stmt_update->execute()) {
                    $mensajes[] = "Alumno actualizado correctamente";
                    
                    // Actualizar datos del alumno en la variable local
                    $sql = "SELECT * FROM alumnos WHERE matriculaAlumno = :matricula";
                    $stmt = $con->prepare($sql);
                    $stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);
                    $stmt->execute();
                    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                } else {
                    $errores[] = "Error al actualizar el alumno";
                }
                
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    // Error de duplicado (correo o matrícula)
                    $errores[] = "El correo institucional ya está registrado para otro alumno";
                } else {
                    $errores[] = "Error de base de datos: " . $e->getMessage();
                }
            }
        }
    }
}

// ========== OBTENER DATOS PARA FORMULARIO ==========
// Obtener listas para combobox
try {
    // Géneros
    $sql_generos = "SELECT * FROM generos ORDER BY genero";
    $generos = $con->query($sql_generos)->fetchAll(PDO::FETCH_ASSOC);
    
    // Nacionalidades
    $sql_nacionalidades = "SELECT * FROM nacionalidades ORDER BY nacionalidad";
    $nacionalidades = $con->query($sql_nacionalidades)->fetchAll(PDO::FETCH_ASSOC);
    
    // Estados
    $sql_estados = "SELECT * FROM estadonacimiento ORDER BY estado_Nacimiento";
    $estados = $con->query($sql_estados)->fetchAll(PDO::FETCH_ASSOC);
    
    // Discapacidades
    $sql_discapacidades = "SELECT * FROM discapacidades ORDER BY tipoDiscapacidad";
    $discapacidades = $con->query($sql_discapacidades)->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener datos para el formulario: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Alumno - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2e7d32;
            border-bottom: 3px solid #8bc34a;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .alert {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 600;
            color: #1a5330;
        }
        .required::after {
            content: " *";
            color: red;
        }
        .btn-submit {
            background-color: #2e7d32;
            border-color: #2e7d32;
            padding: 10px 30px;
            font-weight: 600;
        }
        .btn-submit:hover {
            background-color: #1b5e20;
            border-color: #1b5e20;
        }
        .btn-cancel {
            background-color: #6c757d;
            border-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class='bx bx-edit'></i> Editar Alumno</h2>
        
        <!-- Mensajes de éxito/error -->
        <?php if (!empty($mensajes)): ?>
            <div class="alert alert-success">
                <?php foreach ($mensajes as $mensaje): ?>
                    <p><i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($mensaje); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <strong><i class='bx bx-error'></i> Errores:</strong>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Formulario -->
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row">
                <!-- Columna Izquierda -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Matrícula</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($alumno['matriculaAlumno']); ?>" disabled>
                        <small class="text-muted">La matrícula no se puede modificar</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Nombre(s)</label>
                        <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($alumno['nombre']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Apellido Paterno</label>
                        <input type="text" class="form-control" name="apellidoPaterno" value="<?php echo htmlspecialchars($alumno['apellidoPaterno']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Apellido Materno</label>
                        <input type="text" class="form-control" name="apellidoMaterno" value="<?php echo htmlspecialchars($alumno['apellidoMaterno'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" name="fechaNacimiento" value="<?php echo htmlspecialchars($alumno['fechaNacimiento']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Género</label>
                        <select class="form-select" name="id_genero" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($generos as $genero): ?>
                                <option value="<?php echo $genero['id_genero']; ?>" <?php echo ($genero['id_genero'] == $alumno['id_genero']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($genero['genero']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Columna Derecha -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Correo Institucional</label>
                        <input type="email" class="form-control" name="mailInstitucional" value="<?php echo htmlspecialchars($alumno['mailInstitucional']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Correo Personal</label>
                        <input type="email" class="form-control" name="mailPersonal" value="<?php echo htmlspecialchars($alumno['mailPersonal'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Número de Celular</label>
                        <input type="text" class="form-control" name="numCelular" value="<?php echo htmlspecialchars($alumno['numCelular'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Teléfono de Emergencia</label>
                        <input type="text" class="form-control" name="telefonoEmergencia" value="<?php echo htmlspecialchars($alumno['telefonoEmergencia'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Nacionalidad</label>
                        <select class="form-select" name="id_nacionalidad" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($nacionalidades as $nacionalidad): ?>
                                <option value="<?php echo $nacionalidad['id_nacionalidad']; ?>" <?php echo ($nacionalidad['id_nacionalidad'] == $alumno['id_nacionalidad']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($nacionalidad['nacionalidad']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Estado de Nacimiento</label>
                        <select class="form-select" name="id_estadoNacimiento" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?php echo $estado['id_estadoNacimiento']; ?>" <?php echo ($estado['id_estadoNacimiento'] == $alumno['id_estadoNacimiento']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($estado['estado_Nacimiento']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Discapacidad</label>
                        <select class="form-select" name="id_discapacidad">
                            <option value="">Sin discapacidad</option>
                            <?php foreach ($discapacidades as $discapacidad): ?>
                                <option value="<?php echo $discapacidad['id_discapacidad']; ?>" <?php echo (isset($alumno['id_discapacidad']) && $discapacidad['id_discapacidad'] == $alumno['id_discapacidad']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($discapacidad['tipoDiscapacidad']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Campos adicionales -->
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">RFC</label>
                        <input type="text" class="form-control" name="rfc" value="<?php echo htmlspecialchars($alumno['rfc'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <textarea class="form-control" name="direccion" rows="2"><?php echo htmlspecialchars($alumno['direccion'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Botones -->
            <div class="d-flex justify-content-between mt-4">
                <a href="lista_alumnos.php" class="btn btn-secondary btn-cancel">
                    <i class='bx bx-arrow-back'></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success btn-submit">
                    <i class='bx bx-save'></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const nombre = this.querySelector('input[name="nombre"]').value.trim();
            const apellidoPaterno = this.querySelector('input[name="apellidoPaterno"]').value.trim();
            const mailInstitucional = this.querySelector('input[name="mailInstitucional"]').value.trim();
            
            if (!nombre || !apellidoPaterno || !mailInstitucional) {
                e.preventDefault();
                alert('Por favor, complete los campos requeridos (*)');
                return false;
            }
            
            // Validar formato de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(mailInstitucional)) {
                e.preventDefault();
                alert('El correo institucional no tiene un formato válido');
                return false;
            }
        });
    </script>
</body>
</html>