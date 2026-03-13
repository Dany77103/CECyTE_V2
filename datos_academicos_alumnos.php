<?php
// datos_academicos_alumnos.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['numControl'])) {
    header('Location: lista_alumnos.php?error=numero_invalido');
    exit();
}

$numControl = trim($_GET['numControl']);

// Verificar que el alumno existe
try {
    $sql_alumno = "SELECT * FROM alumnos WHERE numControl = :numControl";
    $stmt_alumno = $con->prepare($sql_alumno);
    $stmt_alumno->bindParam(':numControl', $numControl, PDO::PARAM_STR);
    $stmt_alumno->execute();
    
    if ($stmt_alumno->rowCount() === 0) {
        header('Location: lista_alumnos.php?error=not_found');
        exit();
    }
    
    $alumno = $stmt_alumno->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener datos del alumno: " . $e->getMessage());
}

// Obtener datos académicos actuales del alumno
try {
    $sql = "SELECT daa.*, ge.gradoEstudio, c.carrera
            FROM datosacademicosalumnos daa
            LEFT JOIN gradoestudios ge ON daa.id_gradoEstudio = ge.id_gradoEstudio
            LEFT JOIN carreras c ON daa.id_carrera = c.id_carrera
            WHERE daa.numControl = :numControl";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':numControl', $numControl, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $datosAcademicos = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $datosAcademicos = null;
    }
    
} catch (PDOException $e) {
    die("Error al obtener datos académicos del alumno: " . $e->getMessage());
}

// Obtener datos para formularios
try {
    // Grados de estudio
    $sql_grados = "SELECT * FROM gradoestudios ORDER BY id_gradoEstudio";
    $grados = $con->query($sql_grados)->fetchAll(PDO::FETCH_ASSOC);
    
    // Carreras
    $sql_carreras = "SELECT * FROM carreras ORDER BY carrera";
    $carreras = $con->query($sql_carreras)->fetchAll(PDO::FETCH_ASSOC);
    
    // Semestres
    $sql_semestres = "SELECT * FROM semestres ORDER BY id_semestre";
    $semestres = $con->query($sql_semestres)->fetchAll(PDO::FETCH_ASSOC);
    
    // Grupos
    $sql_grupos = "SELECT * FROM grupos ORDER BY grupo";
    $grupos = $con->query($sql_grupos)->fetchAll(PDO::FETCH_ASSOC);
    
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
        $errores[] = "Token de seguridad inválido. Por favor, recarga la página.";
    } else {
        // Recoger datos
        $id_gradoEstudio = $_POST['id_gradoEstudio'] ?? '';
        $id_carrera = $_POST['id_carrera'] ?? '';
        $semestre = $_POST['semestre'] ?? '';
        $grupo = trim($_POST['grupo'] ?? '');
        $promedio = trim($_POST['promedio'] ?? '');
        $beca = trim($_POST['beca'] ?? '');
        $tipoBeca = trim($_POST['tipoBeca'] ?? '');
        $materiasReprobadas = trim($_POST['materiasReprobadas'] ?? '');
        $materiasAdeudadas = trim($_POST['materiasAdeudadas'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $tutor = trim($_POST['tutor'] ?? '');
        
        // Validaciones
        if (empty($id_gradoEstudio)) {
            $errores[] = "El grado de estudio es requerido";
        }
        if (empty($id_carrera)) {
            $errores[] = "La carrera es requerida";
        }
        if (empty($semestre)) {
            $errores[] = "El semestre es requerido";
        }
        if (empty($grupo)) {
            $errores[] = "El grupo es requerido";
        }
        
        // Validar promedio
        if (!empty($promedio) && (!is_numeric($promedio) || $promedio < 0 || $promedio > 10)) {
            $errores[] = "El promedio debe ser un número entre 0 y 10";
        }
        
        if (empty($errores)) {
            try {
                // Si ya existen datos académicos, actualizar. Si no, insertar.
                if ($datosAcademicos) {
                    $sql = "UPDATE datosacademicosalumnos 
                            SET id_gradoEstudio = :id_gradoEstudio, 
                                id_carrera = :id_carrera, 
                                semestre = :semestre, 
                                grupo = :grupo, 
                                promedio = :promedio, 
                                beca = :beca, 
                                tipoBeca = :tipoBeca, 
                                materiasReprobadas = :materiasReprobadas, 
                                materiasAdeudadas = :materiasAdeudadas, 
                                observaciones = :observaciones,
                                tutor = :tutor
                            WHERE numControl = :numControl";
                } else {
                    $sql = "INSERT INTO datosacademicosalumnos (numControl, id_gradoEstudio, id_carrera, semestre, grupo, promedio, beca, tipoBeca, materiasReprobadas, materiasAdeudadas, observaciones, tutor) 
                            VALUES (:numControl, :id_gradoEstudio, :id_carrera, :semestre, :grupo, :promedio, :beca, :tipoBeca, :materiasReprobadas, :materiasAdeudadas, :observaciones, :tutor)";
                }
                
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':numControl', $numControl);
                $stmt->bindParam(':id_gradoEstudio', $id_gradoEstudio);
                $stmt->bindParam(':id_carrera', $id_carrera);
                $stmt->bindParam(':semestre', $semestre);
                $stmt->bindParam(':grupo', $grupo);
                $stmt->bindParam(':promedio', $promedio ?: null);
                $stmt->bindParam(':beca', $beca);
                $stmt->bindParam(':tipoBeca', $tipoBeca);
                $stmt->bindParam(':materiasReprobadas', $materiasReprobadas);
                $stmt->bindParam(':materiasAdeudadas', $materiasAdeudadas);
                $stmt->bindParam(':observaciones', $observaciones);
                $stmt->bindParam(':tutor', $tutor);
                
                if ($stmt->execute()) {
                    $mensajes[] = "Datos académicos del alumno guardados correctamente.";
                    // Actualizar variable $datosAcademicos para mostrar en el formulario
                    $stmt = $con->prepare("SELECT daa.*, ge.gradoEstudio, c.carrera
                                           FROM datosacademicosalumnos daa
                                           LEFT JOIN gradoestudios ge ON daa.id_gradoEstudio = ge.id_gradoEstudio
                                           LEFT JOIN carreras c ON daa.id_carrera = c.id_carrera
                                           WHERE daa.numControl = :numControl");
                    $stmt->bindParam(':numControl', $numControl, PDO::PARAM_STR);
                    $stmt->execute();
                    $datosAcademicos = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Actualizar datos del alumno también
                    $stmt_alumno = $con->prepare("SELECT * FROM alumnos WHERE numControl = :numControl");
                    $stmt_alumno->bindParam(':numControl', $numControl, PDO::PARAM_STR);
                    $stmt_alumno->execute();
                    $alumno = $stmt_alumno->fetch(PDO::FETCH_ASSOC);
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
    <title>Datos Académicos del Alumno - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1000px;
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
        .student-info {
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
        .info-box {
            background-color: #d1ecf1;
            border-left: 4px solid #0c5460;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box h6 {
            color: #0c5460;
            margin-bottom: 5px;
        }
        .info-box p {
            margin: 0;
            color: #0c5460;
            font-size: 0.9rem;
        }
        .badge-beca {
            background-color: #ffc107;
            color: #212529;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
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
            <h3><i class='bx bx-book'></i> Datos Académicos del Alumno</h3>
            <p class="mb-0">Complete o modifique los datos académicos del alumno</p>
        </div>
        
        <div class="form-container">
            <!-- Información del alumno -->
            <div class="student-info">
                <div class="row">
                    <div class="col-md-6">
                        <h5><?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno'] . ' ' . ($alumno['apellido_materno'] ?? '')); ?></h5>
                        <p class="mb-1"><strong><i class='bx bx-id-card'></i> Número de Control:</strong> <?php echo htmlspecialchars($alumno['numControl']); ?></p>
                        <p class="mb-1"><strong><i class='bx bx-cake'></i> Fecha de Nacimiento:</strong> <?php echo htmlspecialchars($alumno['fechaNacimiento'] ?? 'No especificada'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong><i class='bx bx-envelope'></i> Correo:</strong> <?php echo htmlspecialchars($alumno['correo'] ?? 'No especificado'); ?></p>
                        <p class="mb-0"><strong><i class='bx bx-phone'></i> Teléfono:</strong> <?php echo htmlspecialchars($alumno['telefono'] ?? 'No especificado'); ?></p>
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
            
            <div class="info-box">
                <h6><i class='bx bx-info-circle'></i> Información importante</h6>
                <p>Complete los datos académicos del alumno. Esta información es crucial para el seguimiento académico, asignación de grupos y control escolar.</p>
            </div>
            
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
                            <small class="text-muted">Último grado de estudios cursado</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Carrera</label>
                            <select class="form-select" name="id_carrera" id="id_carrera" required>
                                <option value="">Seleccionar carrera...</option>
                                <?php foreach ($carreras as $carrera): ?>
                                    <option value="<?php echo $carrera['id_carrera']; ?>" 
                                        <?php echo ($datosAcademicos && $datosAcademicos['id_carrera'] == $carrera['id_carrera']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($carrera['carrera']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Carrera en la que está inscrito</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Semestre</label>
                            <select class="form-select" name="semestre" id="semestre" required>
                                <option value="">Seleccionar semestre...</option>
                                <?php foreach ($semestres as $sem): ?>
                                    <option value="<?php echo $sem['id_semestre']; ?>" 
                                        <?php echo ($datosAcademicos && $datosAcademicos['semestre'] == $sem['id_semestre']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sem['semestre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Semestre actual del alumno</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Grupo</label>
                            <select class="form-select" name="grupo" id="grupo" required>
                                <option value="">Seleccionar grupo...</option>
                                <?php foreach ($grupos as $gru): ?>
                                    <option value="<?php echo $gru['id_grupo']; ?>" 
                                        <?php echo ($datosAcademicos && $datosAcademicos['grupo'] == $gru['id_grupo']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($gru['grupo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Grupo al que pertenece</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Promedio General</label>
                            <input type="number" class="form-control" name="promedio" id="promedio" 
                                   value="<?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['promedio']) : ''; ?>" 
                                   min="0" max="10" step="0.01" placeholder="Ej: 8.5">
                            <small class="text-muted">Promedio general (escala 0-10)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tutor</label>
                            <input type="text" class="form-control" name="tutor" id="tutor"
                                   value="<?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['tutor']) : ''; ?>"
                                   maxlength="100" placeholder="Nombre del tutor asignado">
                            <small class="text-muted">Tutor académico del alumno</small>
                        </div>
                    </div>
                </div>
                
                <h4 class="section-title">Información Adicional</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">¿Cuenta con Beca?</label>
                            <select class="form-select" name="beca" id="beca">
                                <option value="">Seleccionar...</option>
                                <option value="Si" <?php echo ($datosAcademicos && $datosAcademicos['beca'] == 'Si') ? 'selected' : ''; ?>>Sí</option>
                                <option value="No" <?php echo ($datosAcademicos && $datosAcademicos['beca'] == 'No') ? 'selected' : ''; ?>>No</option>
                            </select>
                            <small class="text-muted">Indica si el alumno cuenta con beca</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo de Beca</label>
                            <input type="text" class="form-control" name="tipoBeca" id="tipoBeca"
                                   value="<?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['tipoBeca']) : ''; ?>"
                                   maxlength="50" placeholder="Ej: Excelencia, Manutención, Transporte">
                            <small class="text-muted">Tipo de beca (si aplica)</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Materias Reprobadas</label>
                            <input type="number" class="form-control" name="materiasReprobadas" id="materiasReprobadas"
                                   value="<?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['materiasReprobadas']) : ''; ?>" 
                                   min="0" placeholder="Número de materias reprobadas">
                            <small class="text-muted">Total de materias reprobadas en su historial</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Materias Adeudadas</label>
                            <input type="number" class="form-control" name="materiasAdeudadas" id="materiasAdeudadas"
                                   value="<?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['materiasAdeudadas']) : ''; ?>" 
                                   min="0" placeholder="Número de materias adeudadas">
                            <small class="text-muted">Materias actualmente adeudadas</small>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Observaciones Académicas</label>
                    <textarea class="form-control" name="observaciones" id="observaciones" rows="3" 
                              placeholder="Observaciones adicionales sobre el desempeño académico del alumno"><?php echo $datosAcademicos ? htmlspecialchars($datosAcademicos['observaciones']) : ''; ?></textarea>
                    <small class="text-muted">Notas importantes sobre el rendimiento académico</small>
                </div>
                
                <!-- Previsualización -->
                <div class="preview-card" id="previewSection">
                    <h5><i class='bx bx-show'></i> Vista Previa de los Datos Académicos</h5>
                    <div id="previewContent">
                        <!-- Los datos de previsualización se insertarán aquí mediante JavaScript -->
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <a href="lista_alumnos.php" class="btn btn-secondary btn-custom">
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
                    <p>¿Está seguro de que desea guardar los datos académicos del alumno?</p>
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
            const becaSelect = document.getElementById('beca');
            const tipoBecaInput = document.getElementById('tipoBeca');
            
            // Función para obtener el nombre del grado de estudio
            function getGradoEstudioName(value) {
                const select = document.getElementById('id_gradoEstudio');
                const selectedOption = select.options[select.selectedIndex];
                return selectedOption.text || 'No seleccionado';
            }
            
            // Función para obtener el nombre de la carrera
            function getCarreraName(value) {
                const select = document.getElementById('id_carrera');
                const selectedOption = select.options[select.selectedIndex];
                return selectedOption.text || 'No seleccionado';
            }
            
            // Función para obtener el nombre del semestre
            function getSemestreName(value) {
                const select = document.getElementById('semestre');
                const selectedOption = select.options[select.selectedIndex];
                return selectedOption.text || 'No seleccionado';
            }
            
            // Función para obtener el nombre del grupo
            function getGrupoName(value) {
                const select = document.getElementById('grupo');
                const selectedOption = select.options[select.selectedIndex];
                return selectedOption.text || 'No seleccionado';
            }
            
            // Habilitar/deshabilitar tipo de beca según selección
            becaSelect.addEventListener('change', function() {
                if (this.value === 'Si') {
                    tipoBecaInput.removeAttribute('disabled');
                } else {
                    tipoBecaInput.setAttribute('disabled', 'disabled');
                    tipoBecaInput.value = '';
                }
                
                // Si la previsualización está visible, actualizarla
                if (previewSection.style.display === 'block') {
                    generatePreview();
                }
            });
            
            // Inicializar estado de tipoBeca
            if (becaSelect.value !== 'Si') {
                tipoBecaInput.setAttribute('disabled', 'disabled');
            }
            
            // Función para generar la previsualización
            function generatePreview() {
                const gradoEstudio = getGradoEstudioName(document.getElementById('id_gradoEstudio').value);
                const carrera = getCarreraName(document.getElementById('id_carrera').value);
                const semestre = getSemestreName(document.getElementById('semestre').value);
                const grupo = getGrupoName(document.getElementById('grupo').value);
                const promedio = document.getElementById('promedio').value || 'No especificado';
                const tutor = document.getElementById('tutor').value || 'No asignado';
                const beca = document.getElementById('beca').value || 'No especificado';
                const tipoBeca = tipoBecaInput.value || 'No aplica';
                const materiasRep = document.getElementById('materiasReprobadas').value || '0';
                const materiasAde = document.getElementById('materiasAdeudadas').value || '0';
                const observaciones = document.getElementById('observaciones').value || 'Sin observaciones';
                
                let html = `
                    <div class="preview-item">
                        <span class="preview-label">Grado de Estudio:</span>
                        <span class="preview-value">${gradoEstudio}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Carrera:</span>
                        <span class="preview-value">${carrera}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Semestre:</span>
                        <span class="preview-value">${semestre}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Grupo:</span>
                        <span class="preview-value">${grupo}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Promedio:</span>
                        <span class="preview-value">${promedio}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Tutor:</span>
                        <span class="preview-value">${tutor}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Beca:</span>
                        <span class="preview-value">${beca}</span>
                    </div>
                `;
                
                if (beca === 'Si') {
                    html += `
                    <div class="preview-item">
                        <span class="preview-label">Tipo de Beca:</span>
                        <span class="preview-value">${tipoBeca}</span>
                    </div>
                    `;
                }
                
                html += `
                    <div class="preview-item">
                        <span class="preview-label">Materias Reprobadas:</span>
                        <span class="preview-value">${materiasRep}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Materias Adeudadas:</span>
                        <span class="preview-value">${materiasAde}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Observaciones:</span>
                        <span class="preview-value">${observaciones.replace(/\n/g, '<br>')}</span>
                    </div>
                `;
                
                previewContent.innerHTML = html;
            }
            
            // Mostrar previsualización
            previewBtn.addEventListener('click', function() {
                // Validar campos requeridos
                const requiredFields = [
                    {id: 'id_gradoEstudio', name: 'Grado de Estudio'},
                    {id: 'id_carrera', name: 'Carrera'},
                    {id: 'semestre', name: 'Semestre'},
                    {id: 'grupo', name: 'Grupo'}
                ];
                
                let missingFields = [];
                
                requiredFields.forEach(field => {
                    const element = document.getElementById(field.id);
                    if (!element.value.trim()) {
                        missingFields.push(field.name);
                    }
                });
                
                if (missingFields.length > 0) {
                    alert('Por favor, complete los siguientes campos requeridos antes de ver la previsualización:\n\n' + 
                          missingFields.map(f => `• ${f}`).join('\n'));
                    
                    // Enfocar el primer campo faltante
                    const firstMissing = requiredFields.find(f => !document.getElementById(f.id).value.trim());
                    if (firstMissing) {
                        document.getElementById(firstMissing.id).focus();
                    }
                    return;
                }
                
                generatePreview();
                previewSection.style.display = 'block';
                previewSection.scrollIntoView({ behavior: 'smooth' });
            });
            
            // Validar formulario antes de enviar
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const requiredFields = [
                    {id: 'id_gradoEstudio', name: 'Grado de Estudio'},
                    {id: 'id_carrera', name: 'Carrera'},
                    {id: 'semestre', name: 'Semestre'},
                    {id: 'grupo', name: 'Grupo'}
                ];
                
                let missingFields = [];
                
                requiredFields.forEach(field => {
                    const element = document.getElementById(field.id);
                    if (!element.value.trim()) {
                        missingFields.push(field.name);
                    }
                });
                
                if (missingFields.length > 0) {
                    alert('Por favor, complete los siguientes campos requeridos:\n\n' + 
                          missingFields.map(f => `• ${f}`).join('\n'));
                    
                    // Enfocar el primer campo faltante
                    const firstMissing = requiredFields.find(f => !document.getElementById(f.id).value.trim());
                    if (firstMissing) {
                        document.getElementById(firstMissing.id).focus();
                    }
                    return false;
                }
                
                // Validar promedio
                const promedioInput = document.getElementById('promedio');
                if (promedioInput.value) {
                    const promedio = parseFloat(promedioInput.value);
                    if (isNaN(promedio) || promedio < 0 || promedio > 10) {
                        alert('El promedio debe ser un número entre 0 y 10');
                        promedioInput.focus();
                        return false;
                    }
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