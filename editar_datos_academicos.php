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
        $errores[] = "Token de seguridad inválido. Por favor, recarga la página.";
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
                    $mensajes[] = "Datos académicos guardados correctamente.";
                    // Actualizar variable $datosAcademicos para mostrar en el formulario
                    $stmt = $con->prepare("SELECT dam.*, ge.gradoEstudio 
                                           FROM datosacademicosmaestros dam
                                           LEFT JOIN gradoestudios ge ON dam.id_gradoEstudio = ge.id_gradoEstudio
                                           WHERE dam.numEmpleado = :numEmpleado");
                    $stmt->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
                    $stmt->execute();
                    $datosAcademicos = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Actualizar datos del maestro también
                    $stmt_maestro = $con->prepare("SELECT * FROM maestros WHERE numEmpleado = :numEmpleado");
                    $stmt_maestro->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
                    $stmt_maestro->execute();
                    $maestro = $stmt_maestro->fetch(PDO::FETCH_ASSOC);
                } else {
                    $errores[] = "Error al guardar los datos académicos.";
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
    <title>Editar Datos Académicos - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .header-card {
            background-color: #2e7d32;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #2e7d32;
            border-bottom: 2px solid #8bc34a;
            padding-bottom: 8px;
            margin-bottom: 20px;
            margin-top: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #1a5330;
            margin-bottom: 5px;
        }
        .required::after {
            content: " *";
            color: red;
        }
        .btn-custom {
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 5px;
        }
        .btn-success {
            background-color: #2e7d32;
            border-color: #2e7d32;
        }
        .btn-success:hover {
            background-color: #1b5e20;
            border-color: #1b5e20;
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        .btn-info:hover {
            background-color: #138496;
            border-color: #138496;
        }
        .teacher-info {
            background-color: #e8f5e9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #4caf50;
        }
        .alert-container {
            margin-bottom: 20px;
        }
        .preview-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            display: none;
        }
        .preview-item {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #dee2e6;
        }
        .preview-label {
            font-weight: 600;
            color: #495057;
            display: inline-block;
            width: 200px;
        }
        .preview-value {
            color: #212529;
        }
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-card">
            <h3><i class='bx bx-book'></i> Editar Datos Académicos del Maestro</h3>
            <p class="mb-0">Complete o modifique los datos académicos del maestro</p>
        </div>
        
        <div class="form-container">
            <!-- Información del maestro -->
            <div class="teacher-info">
                <div class="row">
                    <div class="col-md-6">
                        <h5><?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido_paterno'] . ' ' . ($maestro['apellido_materno'] ?? '')); ?></h5>
                        <p class="mb-1"><strong><i class='bx bx-id-card'></i> Número de Empleado:</strong> <?php echo htmlspecialchars($maestro['numEmpleado']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong><i class='bx bx-envelope'></i> Correo Institucional:</strong> <?php echo htmlspecialchars($maestro['correo_institucional']); ?></p>
                        <p class="mb-0"><strong><i class='bx bx-phone'></i> Teléfono:</strong> <?php echo htmlspecialchars($maestro['telefono'] ?? 'No especificado'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Mensajes de éxito o error -->
            <?php if (!empty($mensajes)): ?>
                <div class="alert alert-success alert-container">
                    <h5><i class='bx bx-check-circle'></i> Operación exitosa</h5>
                    <?php foreach ($mensajes as $mensaje): ?>
                        <p class="mb-0"><?php echo htmlspecialchars($mensaje); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger alert-container">
                    <h5><i class='bx bx-error'></i> Errores encontrados</h5>
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Formulario -->
            <form method="POST" action="" id="academicForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <h4 class="section-title">Información Académica</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Grado de Estudio</label>
                            <select class="form-select" name="id_gradoEstudio" id="id_gradoEstudio" required>
                                <option value="">Seleccionar grado de estudio...</option>
                                <?php foreach ($grados as $grado): ?>
                                    <option value="<?php echo $grado['id_gradoEstudio']; ?>" 
                                        <?php echo ($datosAcademicos && $datosAcademicos['id_gradoEstudio'] == $grado['id_gradoEstudio']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($grado['gradoEstudio']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Último grado de estudios obtenido</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Especialidad</label>
                            <input type="text" class="form-control" name="especialidad" id="especialidad"
                                   value="<?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['especialidad']) : ''; ?>"
                                   maxlength="100" placeholder="Ej: Matemáticas, Física, Historia">
                            <small class="text-muted">Área de especialización principal</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Número de Cédula Profesional</label>
                            <input type="text" class="form-control" name="numCedulaProfesional" id="numCedulaProfesional"
                                   value="<?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['numCedulaProfesional']) : ''; ?>"
                                   maxlength="50" placeholder="Ej: 12345678">
                            <small class="text-muted">Número de cédula profesional (si aplica)</small>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Certificaciones o Cursos</label>
                    <textarea class="form-control" name="certificacionesoCursos" id="certificacionesoCursos" rows="3" 
                              placeholder="Lista de certificaciones, diplomas o cursos relevantes (sepárelos con punto y coma)"><?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['certificacionesoCursos']) : ''; ?></textarea>
                    <small class="text-muted">Separe cada certificación con punto y coma (;)</small>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Experiencia Docente</label>
                    <textarea class="form-control" name="experienciaDocente" id="experienciaDocente" rows="4" 
                              placeholder="Describa la experiencia docente del maestro (años de experiencia, instituciones, niveles educativos)"><?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['experienciaDocente']) : ''; ?></textarea>
                    <small class="text-muted">Incluya años de experiencia, instituciones y niveles educativos</small>
                </div>
                
                <!-- Previsualización -->
                <div class="preview-card" id="previewSection">
                    <h5><i class='bx bx-show'></i> Vista Previa de los Datos</h5>
                    <div id="previewContent">
                        <!-- Los datos de previsualización se insertarán aquí mediante JavaScript -->
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <a href="lista_maestros.php" class="btn btn-secondary btn-custom">
                            <i class='bx bx-arrow-back'></i> Volver a Lista
                        </a>
                        <button type="button" class="btn btn-info btn-custom ms-2" id="previewBtn">
                            <i class='bx bx-show'></i> Ver Datos
                        </button>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-success btn-custom">
                            <i class='bx bx-save'></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel"><i class='bx bx-check-circle'></i> Confirmar Guardado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea guardar los datos académicos?</p>
                    <div class="alert alert-info">
                        <i class='bx bx-info-circle'></i> Los datos serán actualizados en el sistema.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirmSaveBtn">Sí, Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('academicForm');
            const previewBtn = document.getElementById('previewBtn');
            const previewSection = document.getElementById('previewSection');
            const previewContent = document.getElementById('previewContent');
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            const confirmSaveBtn = document.getElementById('confirmSaveBtn');
            
            // Función para obtener el nombre del grado de estudio
            function getGradoEstudioName(value) {
                const select = document.getElementById('id_gradoEstudio');
                const selectedOption = select.options[select.selectedIndex];
                return selectedOption.text || 'No seleccionado';
            }
            
            // Función para generar la previsualización
            function generatePreview() {
                const gradoEstudio = getGradoEstudioName(document.getElementById('id_gradoEstudio').value);
                const especialidad = document.getElementById('especialidad').value || 'No especificado';
                const cedula = document.getElementById('numCedulaProfesional').value || 'No especificado';
                const certificaciones = document.getElementById('certificacionesoCursos').value || 'No especificado';
                const experiencia = document.getElementById('experienciaDocente').value || 'No especificado';
                
                let html = `
                    <div class="preview-item">
                        <span class="preview-label">Grado de Estudio:</span>
                        <span class="preview-value">${gradoEstudio}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Especialidad:</span>
                        <span class="preview-value">${especialidad}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Cédula Profesional:</span>
                        <span class="preview-value">${cedula}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Certificaciones/Cursos:</span>
                        <span class="preview-value">${certificaciones.replace(/\n/g, '<br>')}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Experiencia Docente:</span>
                        <span class="preview-value">${experiencia.replace(/\n/g, '<br>')}</span>
                    </div>
                `;
                
                previewContent.innerHTML = html;
            }
            
            // Mostrar previsualización
            previewBtn.addEventListener('click', function() {
                // Validar campo requerido
                const gradoEstudio = document.getElementById('id_gradoEstudio').value;
                
                if (!gradoEstudio) {
                    alert('Por favor, seleccione el grado de estudio antes de ver la previsualización.');
                    document.getElementById('id_gradoEstudio').focus();
                    return;
                }
                
                generatePreview();
                previewSection.style.display = 'block';
                previewSection.scrollIntoView({ behavior: 'smooth' });
            });
            
            // Validar formulario antes de enviar
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const gradoEstudio = document.getElementById('id_gradoEstudio').value;
                
                if (!gradoEstudio) {
                    alert('Por favor, seleccione el grado de estudio.');
                    document.getElementById('id_gradoEstudio').focus();
                    return false;
                }
                
                // Mostrar modal de confirmación
                confirmModal.show();
                return false;
            });
            
            // Confirmar guardado
            confirmSaveBtn.addEventListener('click', function() {
                form.removeEventListener('submit', arguments.callee);
                form.submit();
            });
            
            // Cerrar modal si se cancela
            document.querySelector('#confirmModal .btn-secondary').addEventListener('click', function() {
                confirmModal.hide();
            });
            
            // Auto-generar previsualización al cambiar datos (opcional)
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (previewSection.style.display === 'block') {
                        generatePreview();
                    }
                });
            });
            
            // Inicializar con datos existentes si hay previsualización visible
            if (previewSection.style.display === 'block') {
                generatePreview();
            }
        });
    </script>
</body>
</html>

