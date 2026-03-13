<?php
// editar_datos_academicos.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['numEmpleado'])) {
    header('Location: lista_maestros.php?error=numero_invalido');
    exit();
}

$numEmpleado = trim($_GET['numEmpleado']);

// Verificar que el maestro existe
try {
    $sql_maestro = "SELECT * FROM maestros WHERE numEmpleado = :numEmpleado";
    $stmt_maestro = $con->prepare($sql_maestro);
    $stmt_maestro->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
    $stmt_maestro->execute();
    
    if ($stmt_maestro->rowCount() === 0) {
        header('Location: lista_maestros.php?error=not_found');
        exit();
    }
    
    $maestro = $stmt_maestro->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener datos del maestro: " . $e->getMessage());
}

// Obtener datos académicos actuales
try {
    $sql = "SELECT dam.*, ge.gradoEstudio 
            FROM datosacademicosmaestros dam
            LEFT JOIN gradoestudios ge ON dam.id_gradoEstudio = ge.id_gradoEstudio
            WHERE dam.numEmpleado = :numEmpleado";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $datosAcademicos = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $datosAcademicos = null;
    }
    
} catch (PDOException $e) {
    die("Error al obtener datos académicos: " . $e->getMessage());
}

// Obtener datos para formularios
try {
    $sql_grados = "SELECT * FROM gradoestudios ORDER BY id_gradoEstudio";
    $grados = $con->query($sql_grados)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener grados de estudio: " . $e->getMessage());
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
        $errores[] = "Token de seguridad inv&aacute;lido. Por favor, recarga la p&aacute;gina.";
    } else {
        // Recoger datos
        $id_gradoEstudio = $_POST['id_gradoEstudio'] ?? '';
        $especialidad = trim($_POST['especialidad'] ?? '');
        $numCedulaProfesional = trim($_POST['numCedulaProfesional'] ?? '');
        $certificacionesoCursos = trim($_POST['certificacionesoCursos'] ?? '');
        $experienciaDocente = trim($_POST['experienciaDocente'] ?? '');
        
        // Validaciones
        if (empty($id_gradoEstudio)) {
            $errores[] = "El grado de estudio es requerido";
        }
        
        if (empty($errores)) {
            try {
                // Si ya existen datos académicos, actualizar. Si no, insertar.
                if ($datosAcademicos) {
                    $sql = "UPDATE datosacademicosmaestros 
                            SET id_gradoEstudio = :id_gradoEstudio, 
                                especialidad = :especialidad, 
                                numCedulaProfesional = :numCedulaProfesional, 
                                certificacionesoCursos = :certificacionesoCursos, 
                                experienciaDocente = :experienciaDocente 
                            WHERE numEmpleado = :numEmpleado";
                } else {
                    $sql = "INSERT INTO datosacademicosmaestros (numEmpleado, id_gradoEstudio, especialidad, numCedulaProfesional, certificacionesoCursos, experienciaDocente) 
                            VALUES (:numEmpleado, :id_gradoEstudio, :especialidad, :numCedulaProfesional, :certificacionesoCursos, :experienciaDocente)";
                }
                
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':numEmpleado', $numEmpleado);
                $stmt->bindParam(':id_gradoEstudio', $id_gradoEstudio);
                $stmt->bindParam(':especialidad', $especialidad);
                $stmt->bindParam(':numCedulaProfesional', $numCedulaProfesional);
                $stmt->bindParam(':certificacionesoCursos', $certificacionesoCursos);
                $stmt->bindParam(':experienciaDocente', $experienciaDocente);
                
                if ($stmt->execute()) {
                    $mensajes[] = "Datos acad&eacute;micos guardados correctamente.";
                    // Actualizar variable $datosAcademicos para mostrar en el formulario
                    $stmt = $con->prepare("SELECT dam.*, ge.gradoEstudio 
                                           FROM datosacademicosmaestros dam
                                           LEFT JOIN gradoestudios ge ON dam.id_gradoEstudio = ge.id_gradoEstudio
                                           WHERE dam.numEmpleado = :numEmpleado");
                    $stmt->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
                    $stmt->execute();
                    $datosAcademicos = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $errores[] = "Error al guardar los datos acad&eacute;micos.";
                }
            } catch (PDOException $e) {
                $errores[] = "Error de base de datos: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos Académicos - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 800px;
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
        .maestro-info {
            background-color: #e8f5e9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box h6 {
            color: #856404;
            margin-bottom: 5px;
        }
        .info-box p {
            margin: 0;
            color: #856404;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class='bx bx-book'></i> Datos Acad&eacute;micos del Maestro</h2>
        
        <!-- Información del maestro -->
        <div class="maestro-info">
            <h5><?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido_paterno'] . ' ' . ($maestro['apellido_materno'] ?? '')); ?></h5>
            <p class="mb-1"><strong>N&uacute;mero de Empleado:</strong> <?php echo htmlspecialchars($maestro['numEmpleado']); ?></p>
            <p class="mb-0"><strong>Correo Institucional:</strong> <?php echo htmlspecialchars($maestro['correo_institucional']); ?></p>
        </div>
        
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
        
        <div class="info-box">
            <h6><i class='bx bx-info-circle'></i> Informaci&oacute;n importante</h6>
            <p>Complete los datos acad&eacute;micos del maestro. Estos datos son importantes para la asignaci&oacute;n de materias y grupos.</p>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Grado de Estudio</label>
                        <select class="form-select" name="id_gradoEstudio" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($grados as $grado): ?>
                                <option value="<?php echo $grado['id_gradoEstudio']; ?>" 
                                    <?php echo ($datosAcademicos && $datosAcademicos['id_gradoEstudio'] == $grado['id_gradoEstudio']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($grado['gradoEstudio']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">&Uacute;ltimo grado de estudios obtenido</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Especialidad</label>
                        <input type="text" class="form-control" name="especialidad" 
                               value="<?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['especialidad']) : ''; ?>"
                               maxlength="100" placeholder="Ej: Matemáticas, Física, Historia">
                        <small class="text-muted">&Aacute;rea de especializaci&oacute;n principal</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Número de Cédula Profesional</label>
                        <input type="text" class="form-control" name="numCedulaProfesional" 
                               value="<?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['numCedulaProfesional']) : ''; ?>"
                               maxlength="50" placeholder="Ej: 12345678">
                        <small class="text-muted">Número de cédula profesional (si aplica)</small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Certificaciones o Cursos</label>
                        <textarea class="form-control" name="certificacionesoCursos" rows="4" 
                                  placeholder="Lista de certificaciones, diplomas o cursos relevantes"><?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['certificacionesoCursos']) : ''; ?></textarea>
                        <small class="text-muted">Separe cada certificación con punto y coma (;)</small>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Experiencia Docente</label>
                <textarea class="form-control" name="experienciaDocente" rows="4" 
                          placeholder="Describa la experiencia docente del maestro"><?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['experienciaDocente']) : ''; ?></textarea>
                <small class="text-muted">Incluya años de experiencia, instituciones y niveles educativos</small>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="lista_maestros.php" class="btn btn-secondary btn-cancel">
                    <i class='bx bx-arrow-back'></i> Volver a la Lista
                </a>
                <div>
                    <a href="editar_maestro.php?numEmpleado=<?php echo urlencode($numEmpleado); ?>" class="btn btn-info me-2">
                        <i class='bx bx-user'></i> Datos Personales
                    </a>
                    <a href="editar_datos_laborales.php?numEmpleado=<?php echo urlencode($numEmpleado); ?>" class="btn btn-warning me-2">
                        <i class='bx bx-briefcase'></i> Datos Laborales
                    </a>
                    <button type="submit" class="btn btn-success btn-submit">
                        <i class='bx bx-save'></i> Guardar Datos Académicos
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const id_gradoEstudio = this.querySelector('select[name="id_gradoEstudio"]').value;
            
            if (!id_gradoEstudio) {
                e.preventDefault();
                alert('Por favor, seleccione el grado de estudio');
                return false;
            }
            
            const confirmar = confirm('¿Está seguro de guardar los datos académicos?');
            if (!confirmar) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        // Auto-guardado cada 30 segundos (opcional)
        let autoSaveTimer;
        const form = document.querySelector('form');
        
        form.addEventListener('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                console.log('Cambios detectados, listo para guardar...');
                // Aquí podrías agregar auto-guardado con AJAX
            }, 30000); // 30 segundos
        });
    </script>
</body>
</html>