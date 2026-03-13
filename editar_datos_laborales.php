<?php
// editar_datos_laborales.php
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

// Obtener datos laborales actuales
try {
    $sql = "SELECT dl.*, p.puesto, e.tipoEstatus 
            FROM datoslaboralesmaestros dl
            LEFT JOIN puestos p ON dl.id_puesto = p.id_puesto
            LEFT JOIN estatus e ON dl.id_estatus = e.id_estatus
            WHERE dl.numEmpleado = :numEmpleado";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $datosLaborales = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $datosLaborales = null;
    }
    
} catch (PDOException $e) {
    die("Error al obtener datos laborales: " . $e->getMessage());
}

// Obtener datos para formularios
try {
    $sql_puestos = "SELECT * FROM puestos ORDER BY puesto";
    $puestos = $con->query($sql_puestos)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_estatus = "SELECT * FROM estatus ORDER BY tipoEstatus";
    $estatus = $con->query($sql_estatus)->fetchAll(PDO::FETCH_ASSOC);
    
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
        $fechaContratacion = trim($_POST['fechaContratacion'] ?? '');
        $tipoContrato = trim($_POST['tipoContrato'] ?? '');
        $id_puesto = $_POST['id_puesto'] ?? '';
        $area = trim($_POST['area'] ?? '');
        $horarioLaboral = trim($_POST['horarioLaboral'] ?? '');
        $id_estatus = $_POST['id_estatus'] ?? '';
        $horarioClases = trim($_POST['horarioClases'] ?? '');
        $actividadesExtracurriculares = trim($_POST['actividadesExtracurriculares'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        
        // Validaciones
        if (empty($fechaContratacion)) {
            $errores[] = "La fecha de contratación es requerida";
        }
        if (empty($tipoContrato)) {
            $errores[] = "El tipo de contrato es requerido";
        }
        if (empty($id_puesto)) {
            $errores[] = "El puesto es requerido";
        }
        if (empty($area)) {
            $errores[] = "El área es requerida";
        }
        if (empty($horarioLaboral)) {
            $errores[] = "El horario laboral es requerido";
        }
        if (empty($id_estatus)) {
            $errores[] = "El estatus es requerido";
        }
        
        // Validar fecha
        if (!empty($fechaContratacion)) {
            $fecha = DateTime::createFromFormat('Y-m-d', $fechaContratacion);
            if (!$fecha || $fecha->format('Y-m-d') !== $fechaContratacion) {
                $errores[] = "La fecha de contratación no es válida (formato: AAAA-MM-DD)";
            }
        }
        
        if (empty($errores)) {
            try {
                // Si ya existen datos laborales, actualizar. Si no, insertar.
                if ($datosLaborales) {
                    $sql = "UPDATE datoslaboralesmaestros 
                            SET fechaContratacion = :fechaContratacion, 
                                tipoContrato = :tipoContrato, 
                                id_puesto = :id_puesto, 
                                area = :area, 
                                horarioLaboral = :horarioLaboral, 
                                id_estatus = :id_estatus, 
                                horarioClases = :horarioClases, 
                                actividadesExtracurriculares = :actividadesExtracurriculares, 
                                observaciones = :observaciones 
                            WHERE numEmpleado = :numEmpleado";
                } else {
                    $sql = "INSERT INTO datoslaboralesmaestros (numEmpleado, fechaContratacion, tipoContrato, id_puesto, area, horarioLaboral, id_estatus, horarioClases, actividadesExtracurriculares, observaciones) 
                            VALUES (:numEmpleado, :fechaContratacion, :tipoContrato, :id_puesto, :area, :horarioLaboral, :id_estatus, :horarioClases, :actividadesExtracurriculares, :observaciones)";
                }
                
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':numEmpleado', $numEmpleado);
                $stmt->bindParam(':fechaContratacion', $fechaContratacion);
                $stmt->bindParam(':tipoContrato', $tipoContrato);
                $stmt->bindParam(':id_puesto', $id_puesto);
                $stmt->bindParam(':area', $area);
                $stmt->bindParam(':horarioLaboral', $horarioLaboral);
                $stmt->bindParam(':id_estatus', $id_estatus);
                $stmt->bindParam(':horarioClases', $horarioClases);
                $stmt->bindParam(':actividadesExtracurriculares', $actividadesExtracurriculares);
                $stmt->bindParam(':observaciones', $observaciones);
                
                if ($stmt->execute()) {
                    $mensajes[] = "Datos laborales guardados correctamente.";
                    // Actualizar variable $datosLaborales para mostrar en el formulario
                    $stmt = $con->prepare("SELECT dl.*, p.puesto, e.tipoEstatus 
                                           FROM datoslaboralesmaestros dl
                                           LEFT JOIN puestos p ON dl.id_puesto = p.id_puesto
                                           LEFT JOIN estatus e ON dl.id_estatus = e.id_estatus
                                           WHERE dl.numEmpleado = :numEmpleado");
                    $stmt->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
                    $stmt->execute();
                    $datosLaborales = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Actualizar datos del maestro también
                    $stmt_maestro = $con->prepare("SELECT * FROM maestros WHERE numEmpleado = :numEmpleado");
                    $stmt_maestro->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
                    $stmt_maestro->execute();
                    $maestro = $stmt_maestro->fetch(PDO::FETCH_ASSOC);
                } else {
                    $errores[] = "Error al guardar los datos laborales.";
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
    <title>Editar Datos Laborales - CECyTE</title>
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
        .contract-types {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 5px;
        }
        .contract-type {
            padding: 5px 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        .contract-type:hover {
            background: #e9ecef;
        }
        .contract-type.active {
            background: #2e7d32;
            color: white;
            border-color: #2e7d32;
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
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .form-container {
                padding: 20px;
            }
            .contract-types {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-card">
            <h3><i class='bx bx-briefcase'></i> Editar Datos Laborales del Maestro</h3>
            <p class="mb-0">Complete o modifique los datos laborales del maestro</p>
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
            
            <div class="info-box">
                <h6><i class='bx bx-info-circle'></i> Información importante</h6>
                <p>Complete los datos laborales del maestro. Esta información es crucial para nómina, asignación de grupos y control administrativo.</p>
            </div>
            
            <!-- Formulario -->
            <form method="POST" action="" id="laboralForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <h4 class="section-title">Información Laboral</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Fecha de Contratación</label>
                            <input type="date" class="form-control" name="fechaContratacion" id="fechaContratacion" required 
                                   value="<?php echo $datosLaborales ? htmlspecialchars($datosLaborales['fechaContratacion']) : ''; ?>">
                            <small class="text-muted">Fecha en que inició labores en la institución</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Tipo de Contrato</label>
                            <input type="text" class="form-control" name="tipoContrato" id="tipoContrato" required 
                                   value="<?php echo $datosLaborales ? htmlspecialchars($datosLaborales['tipoContrato']) : ''; ?>" 
                                   placeholder="Ej: Tiempo completo, medio tiempo, por honorarios">
                            <small class="text-muted">Tipos comunes: Tiempo completo, Medio tiempo, Por proyecto, Por honorarios</small>
                            <div class="contract-types">
                                <span class="contract-type" data-value="Tiempo completo">Tiempo completo</span>
                                <span class="contract-type" data-value="Medio tiempo">Medio tiempo</span>
                                <span class="contract-type" data-value="Por honorarios">Por honorarios</span>
                                <span class="contract-type" data-value="Por proyecto">Por proyecto</span>
                                <span class="contract-type" data-value="Temporal">Temporal</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Puesto</label>
                            <select class="form-select" name="id_puesto" id="id_puesto" required>
                                <option value="">Seleccionar puesto...</option>
                                <?php foreach ($puestos as $puesto): ?>
                                    <option value="<?php echo $puesto['id_puesto']; ?>" 
                                        <?php echo ($datosLaborales && $datosLaborales['id_puesto'] == $puesto['id_puesto']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($puesto['puesto']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Cargo que desempeña en la institución</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Área/Departamento</label>
                            <input type="text" class="form-control" name="area" id="area" required 
                                   value="<?php echo $datosLaborales ? htmlspecialchars($datosLaborales['area']) : ''; ?>" 
                                   placeholder="Ej: Departamento de Matemáticas, Ciencias, Administración">
                            <small class="text-muted">Área o departamento al que pertenece</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Horario Laboral</label>
                            <input type="text" class="form-control" name="horarioLaboral" id="horarioLaboral" required 
                                   value="<?php echo $datosLaborales ? htmlspecialchars($datosLaborales['horarioLaboral']) : ''; ?>" 
                                   placeholder="Ej: Lunes a Viernes de 7:00 a 15:00">
                            <small class="text-muted">Horario general de trabajo</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Estatus Laboral</label>
                            <select class="form-select" name="id_estatus" id="id_estatus" required>
                                <option value="">Seleccionar estatus...</option>
                                <?php foreach ($estatus as $est): ?>
                                    <option value="<?php echo $est['id_estatus']; ?>" 
                                        <?php echo ($datosLaborales && $datosLaborales['id_estatus'] == $est['id_estatus']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($est['tipoEstatus']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Estado actual del empleado</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Horario de Clases</label>
                            <input type="text" class="form-control" name="horarioClases" id="horarioClases" 
                                   value="<?php echo $datosLaborales ? htmlspecialchars($datosLaborales['horarioClases']) : ''; ?>" 
                                   placeholder="Ej: Lunes y Miércoles 10:00-12:00, Martes y Jueves 14:00-16:00">
                            <small class="text-muted">Horario específico de clases (si aplica)</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Actividades Extracurriculares</label>
                            <textarea class="form-control" name="actividadesExtracurriculares" id="actividadesExtracurriculares" rows="3" 
                                      placeholder="Actividades adicionales como tutorías, clubes, comisiones, etc."><?php echo $datosLaborales ? htmlspecialchars($datosLaborales['actividadesExtracurriculares']) : ''; ?></textarea>
                            <small class="text-muted">Actividades adicionales a las clases regulares</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" id="observaciones" rows="3" 
                                      placeholder="Observaciones adicionales sobre la situación laboral"><?php echo $datosLaborales ? htmlspecialchars($datosLaborales['observaciones']) : ''; ?></textarea>
                            <small class="text-muted">Notas importantes sobre el empleado</small>
                        </div>
                    </div>
                </div>
                
                <!-- Previsualización -->
                <div class="preview-card" id="previewSection">
                    <h5><i class='bx bx-show'></i> Vista Previa de los Datos Laborales</h5>
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
                    <p>¿Está seguro de que desea guardar los datos laborales?</p>
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
            const form = document.getElementById('laboralForm');
            const previewBtn = document.getElementById('previewBtn');
            const previewSection = document.getElementById('previewSection');
            const previewContent = document.getElementById('previewContent');
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            const confirmSaveBtn = document.getElementById('confirmSaveBtn');
            const contractTypes = document.querySelectorAll('.contract-type');
            
            // Función para obtener el nombre del puesto
            function getPuestoName(value) {
                const select = document.getElementById('id_puesto');
                const selectedOption = select.options[select.selectedIndex];
                return selectedOption.text || 'No seleccionado';
            }
            
            // Función para obtener el nombre del estatus
            function getEstatusName(value) {
                const select = document.getElementById('id_estatus');
                const selectedOption = select.options[select.selectedIndex];
                return selectedOption.text || 'No seleccionado';
            }
            
            // Formatear fecha
            function formatDate(dateString) {
                if (!dateString) return 'No especificada';
                const date = new Date(dateString);
                return date.toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
            
            // Configurar tipos de contrato clickeables
            contractTypes.forEach(type => {
                type.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    document.getElementById('tipoContrato').value = value;
                    
                    // Actualizar clases activas
                    contractTypes.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Si la previsualización está visible, actualizarla
                    if (previewSection.style.display === 'block') {
                        generatePreview();
                    }
                });
            });
            
            // Función para generar la previsualización
            function generatePreview() {
                const fechaContratacion = formatDate(document.getElementById('fechaContratacion').value);
                const tipoContrato = document.getElementById('tipoContrato').value || 'No especificado';
                const puesto = getPuestoName(document.getElementById('id_puesto').value);
                const area = document.getElementById('area').value || 'No especificada';
                const horarioLaboral = document.getElementById('horarioLaboral').value || 'No especificado';
                const estatus = getEstatusName(document.getElementById('id_estatus').value);
                const horarioClases = document.getElementById('horarioClases').value || 'No especificado';
                const actividades = document.getElementById('actividadesExtracurriculares').value || 'No especificado';
                const observaciones = document.getElementById('observaciones').value || 'No especificado';
                
                let html = `
                    <div class="preview-item">
                        <span class="preview-label">Fecha de Contratación:</span>
                        <span class="preview-value">${fechaContratacion}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Tipo de Contrato:</span>
                        <span class="preview-value">${tipoContrato}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Puesto:</span>
                        <span class="preview-value">${puesto}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Área/Departamento:</span>
                        <span class="preview-value">${area}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Horario Laboral:</span>
                        <span class="preview-value">${horarioLaboral}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Estatus Laboral:</span>
                        <span class="preview-value">${estatus}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Horario de Clases:</span>
                        <span class="preview-value">${horarioClases}</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Actividades Extracurriculares:</span>
                        <span class="preview-value">${actividades.replace(/\n/g, '<br>')}</span>
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
                    {id: 'fechaContratacion', name: 'Fecha de Contratación'},
                    {id: 'tipoContrato', name: 'Tipo de Contrato'},
                    {id: 'id_puesto', name: 'Puesto'},
                    {id: 'area', name: 'Área/Departamento'},
                    {id: 'horarioLaboral', name: 'Horario Laboral'},
                    {id: 'id_estatus', name: 'Estatus Laboral'}
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
                    {id: 'fechaContratacion', name: 'Fecha de Contratación'},
                    {id: 'tipoContrato', name: 'Tipo de Contrato'},
                    {id: 'id_puesto', name: 'Puesto'},
                    {id: 'area', name: 'Área/Departamento'},
                    {id: 'horarioLaboral', name: 'Horario Laboral'},
                    {id: 'id_estatus', name: 'Estatus Laboral'}
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
            
            // Seleccionar tipo de contrato si coincide con alguno predefinido
            const tipoContratoInput = document.getElementById('tipoContrato');
            const currentTipoContrato = tipoContratoInput.value;
            if (currentTipoContrato) {
                contractTypes.forEach(type => {
                    if (type.getAttribute('data-value') === currentTipoContrato) {
                        type.classList.add('active');
                    }
                });
            }
            
            // Inicializar con datos existentes si hay previsualización visible
            if (previewSection.style.display === 'block') {
                generatePreview();
            }
        });
    </script>
</body>
</html>