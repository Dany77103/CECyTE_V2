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
    <title>Datos Laborales - CECyTE</title>
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
        .maestro-info {
            background-color: #e8f5e9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
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
        .contract-types {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 5px;
        }
        .contract-type {
            padding: 5px 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .contract-type:hover {
            background: #e9ecef;
        }
        .contract-type.active {
            background: #2e7d32;
            color: white;
            border-color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class='bx bx-briefcase'></i> Datos Laborales del Maestro</h2>
        
        <!-- Información del maestro -->
        <div class="maestro-info">
            <h5><?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellidoPaterno'] . ' ' . ($maestro['apellidoMaterno'] ?? '')); ?></h5>
            <p class="mb-1"><strong>Número de Empleado:</strong> <?php echo htmlspecialchars($maestro['numEmpleado']); ?></p>
            <p class="mb-0"><strong>Correo Institucional:</strong> <?php echo htmlspecialchars($maestro['mailInstitucional']); ?></p>
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
            <h6><i class='bx bx-info-circle'></i> Información importante</h6>
            <p>Complete los datos laborales del maestro. Esta información es crucial para nómina, asignación de grupos y control administrativo.</p>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Fecha de Contratación</label>
                        <input type="date" class="form-control" name="fechaContratacion" required 
                               value="<?php echo $datosLaborales ? htmlspecialchars($datosLaborales['fechaContratacion']) : ''; ?>">
                        <small class="text-muted">Fecha en que inició labores en la institución</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Tipo de Contrato</label>
                        <input type="text" class="form-control" name="tipoContrato" required 
                               value="<?php echo $datosLaborales ? htmlspecialchars($datosLaborales['tipoContrato']) : ''; ?>" 
                               placeholder="Ej: Tiempo completo, medio tiempo, por honorarios">
                        <small class="text-muted">Tipos comunes: Tiempo completo, Medio tiempo, Por proyecto, Por honorarios</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Puesto</label>
                        <select class="form-select" name="id_puesto" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($puestos as $puesto): ?>
                                <option value="<?php echo $puesto['id_puesto']; ?>" 
                                    <?php echo ($datosLaborales && $datosLaborales['id_puesto'] == $puesto['id_puesto']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($puesto['puesto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Cargo que desempeña en la institución</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Área/Departamento</label>
                        <input type="text" class="form-control" name="area" required 
                               value="<?php echo $datosLaborales ? htmlspecialchars($datosLaborales['area']) : ''; ?>" 
                               placeholder="Ej: Departamento de Matemáticas, Ciencias, Administración">
                        <small class="text-muted">Área o departamento al que pertenece</small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Horario Laboral</label>
                        <input type="text" class="form-control" name="horarioLaboral" required 
                               value="<?php echo $datosLaborales ? htmlspecialchars($datosLaborales['horarioLaboral']) : ''; ?>" 
                               placeholder="Ej: Lunes a Viernes de 7:00 a 15:00">
                        <small class="text-muted">Horario general de trabajo</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Estatus Laboral</label>
                        <select class="form-select" name="id_estatus" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($estatus as $est): ?>
                                <option value="<?php echo $est['id_estatus']; ?>" 
                                    <?php echo ($datosLaborales && $datosLaborales['id_estatus'] == $est['id_estatus']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($est['tipoEstatus']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Estado actual del empleado</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Horario de Clases</label>
                        <input type="text" class="form-control" name="horarioClases" 
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
                        <textarea class="form-control" name="actividadesExtracurriculares" rows="3" 
                                  placeholder="Actividades adicionales como tutorías, clubes, comisiones, etc."><?php echo $datosLaborales ? htmlspecialchars($datosLaborales['actividadesExtracurriculares']) : ''; ?></textarea>
                        <small class="text-muted">Actividades adicionales a las clases regulares</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones" rows="3" 
                                  placeholder="Observaciones adicionales sobre la situación laboral"><?php echo $datosLaborales ? htmlspecialchars($datosLaborales['observaciones']) : ''; ?></textarea>
                        <small class="text-muted">Notas importantes sobre el empleado</small>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="lista_maestros.php" class="btn btn-secondary btn-cancel">
                    <i class='bx bx-arrow-back'></i> Volver a la Lista
                </a>
                <div>
                    <a href="editar_maestro.php?numEmpleado=<?php echo urlencode($numEmpleado); ?>" class="btn btn-info me-2">
                        <i class='bx bx-user'></i> Datos Personales
                    </a>
                    <a href="editar_datos_academicos.php?numEmpleado=<?php echo urlencode($numEmpleado); ?>" class="btn btn-primary me-2">
                        <i class='bx bx-book'></i> Datos Académicos
                    </a>
                    <button type="submit" class="btn btn-success btn-submit">
                        <i class='bx bx-save'></i> Guardar Datos Laborales
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const camposRequeridos = [
                'fechaContratacion',
                'tipoContrato', 
                'id_puesto',
                'area',
                'horarioLaboral',
                'id_estatus'
            ];
            
            let faltanCampos = false;
            let mensajeError = 'Por favor, complete los siguientes campos requeridos:\n';
            
            camposRequeridos.forEach(campo => {
                const elemento = this.querySelector(`[name="${campo}"]`);
                if (!elemento.value.trim()) {
                    faltanCampos = true;
                    const label = this.querySelector(`label[for="${campo}"]`) || 
                                 this.querySelector(`label:has([name="${campo}"])`);
                    const nombreCampo = label ? label.textContent.replace(' *', '') : campo;
                    mensajeError += `- ${nombreCampo}\n`;
                }
            });
            
            if (faltanCampos) {
                e.preventDefault();
                alert(mensajeError);
            }
        });
    </script>
</body>
</html>