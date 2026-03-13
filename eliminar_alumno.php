<?php
// eliminar_alumno.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Verificar si se recibió la matrícula
if (!isset($_GET['matricula'])) {
    header('Location: lista_alumnos.php?error=matricula_invalida');
    exit();
}

$matricula = trim($_GET['matricula']);

// Verificar si el alumno existe
try {
    $sql_check = "SELECT * FROM alumnos WHERE matricula = :matricula";
    $stmt_check = $con->prepare($sql_check);
    $stmt_check->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() === 0) {
        header('Location: lista_alumnos.php?error=not_found');
        exit();
    }
    
    $alumno = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al verificar alumno: " . $e->getMessage());
}

// Procesar eliminación si se confirmó
$eliminado = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de seguridad inválido";
    } else {
        try {
            // Iniciar transacción
            $con->beginTransaction();
            
            // Opción 1: Eliminación física (descomentar si se quiere eliminar permanentemente)
            /*
            $sql_delete = "DELETE FROM alumnos WHERE matricula = :matricula";
            $stmt_delete = $con->prepare($sql_delete);
            $stmt_delete->bindParam(':matricula', $matricula, PDO::PARAM_STR);
            $stmt_delete->execute();
            */
            
            // Opción 2: Eliminación lógica (recomendado - actualizar estatus)
            $sql_update = "UPDATE alumnos SET 
                          id_estatus = (SELECT id_estatus FROM estatus_alumnos WHERE nombre = 'Baja' LIMIT 1),
                          observaciones = CONCAT(IFNULL(observaciones, ''), '\n[BAJA] - ', :razon, ' - ', NOW()),
                          updated_at = NOW()
                          WHERE matricula = :matricula";
            
            $stmt_update = $con->prepare($sql_update);
            $razon = trim($_POST['razon_baja'] ?? 'Baja administrativa');
            $stmt_update->bindParam(':razon', $razon, PDO::PARAM_STR);
            $stmt_update->bindParam(':matricula', $matricula, PDO::PARAM_STR);
            $stmt_update->execute();
            
            // También podrías moverlo a una tabla de historial
            $sql_historial = "INSERT INTO alumnos_historial 
                             (matricula, nombre, apellido_paterno, apellido_materno, 
                              fecha_nacimiento, curp, fecha_ingreso, id_carrera, 
                              semestre, motivo_baja, fecha_baja, usuario_baja)
                             VALUES 
                             (:matricula, :nombre, :apellido_paterno, :apellido_materno,
                              :fecha_nacimiento, :curp, :fecha_ingreso, :id_carrera,
                              :semestre, :motivo_baja, NOW(), :usuario)";
            
            $stmt_historial = $con->prepare($sql_historial);
            $stmt_historial->bindParam(':matricula', $alumno['matricula']);
            $stmt_historial->bindParam(':nombre', $alumno['nombre']);
            $stmt_historial->bindParam(':apellido_paterno', $alumno['apellido_paterno']);
            $stmt_historial->bindParam(':apellido_materno', $alumno['apellido_materno']);
            $stmt_historial->bindParam(':fecha_nacimiento', $alumno['fecha_nacimiento']);
            $stmt_historial->bindParam(':curp', $alumno['curp']);
            $stmt_historial->bindParam(':fecha_ingreso', $alumno['fecha_ingreso']);
            $stmt_historial->bindParam(':id_carrera', $alumno['id_carrera']);
            $stmt_historial->bindParam(':semestre', $alumno['semestre']);
            $stmt_historial->bindParam(':motivo_baja', $razon);
            $stmt_historial->bindParam(':usuario', $_SESSION['username'] ?? 'Sistema');
            $stmt_historial->execute();
            
            // Confirmar transacción
            $con->commit();
            
            $eliminado = true;
            $_SESSION['mensaje'] = "Alumno dado de baja correctamente.";
            
        } catch (PDOException $e) {
            $con->rollBack();
            $error = "Error al eliminar el alumno: " . $e->getMessage();
        }
    }
}

// Generar CSRF token si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Alumno - CECyTE</title>
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
            color: #dc3545;
            border-bottom: 3px solid #f8d7da;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .danger-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .student-info {
            background-color: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .btn-cancel {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-cancel:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        .btn-delete {
            background-color: #dc3545;
            border-color: #dc3545;
            padding: 10px 30px;
            font-weight: 600;
        }
        .btn-delete:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class='bx bx-trash'></i> Eliminar Alumno</h2>
        
        <?php if ($eliminado): ?>
            <div class="success-box">
                <h5><i class='bx bx-check-circle'></i> Operación Exitosa</h5>
                <p>El alumno ha sido dado de baja correctamente del sistema.</p>
                <div class="mt-3">
                    <a href="lista_alumnos.php" class="btn btn-success">
                        <i class='bx bx-arrow-back'></i> Volver a la Lista
                    </a>
                </div>
            </div>
            <script>
                // Redirigir después de 3 segundos
                setTimeout(function() {
                    window.location.href = 'lista_alumnos.php';
                }, 3000);
            </script>
            
        <?php else: ?>
            <div class="warning-box">
                <h5><i class='bx bx-error'></i> ¡Advertencia!</h5>
                <p>Está a punto de dar de baja a un alumno del sistema. Esta acción cambiará su estatus a "Baja" y registrará la operación en el historial.</p>
                <p class="mb-0"><strong>Nota:</strong> Los datos del alumno se conservarán en el historial pero ya no aparecerá en las listas activas.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="danger-box">
                    <h5><i class='bx bx-error-circle'></i> Error</h5>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Información del alumno -->
            <div class="student-info">
                <h5>Información del Alumno a Eliminar</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Matrícula:</strong> <?php echo htmlspecialchars($alumno['matricula']); ?></p>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno'] . ' ' . ($alumno['apellido_materno'] ?? '')); ?></p>
                        <p><strong>CURP:</strong> <?php echo htmlspecialchars($alumno['curp']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Carrera:</strong> 
                            <?php 
                            try {
                                $sql_carrera = "SELECT nombre FROM carreras WHERE id_carrera = :id_carrera";
                                $stmt_carrera = $con->prepare($sql_carrera);
                                $stmt_carrera->bindParam(':id_carrera', $alumno['id_carrera'], PDO::PARAM_INT);
                                $stmt_carrera->execute();
                                $carrera = $stmt_carrera->fetch(PDO::FETCH_ASSOC);
                                echo htmlspecialchars($carrera['nombre'] ?? 'No asignada');
                            } catch (PDOException $e) {
                                echo 'Error al obtener carrera';
                            }
                            ?>
                        </p>
                        <p><strong>Semestre:</strong> <?php echo htmlspecialchars($alumno['semestre'] ?? ''); ?>°</p>
                        <p><strong>Fecha de Ingreso:</strong> 
                            <?php 
                            if ($alumno['fecha_ingreso']) {
                                $fecha = new DateTime($alumno['fecha_ingreso']);
                                echo htmlspecialchars($fecha->format('d/m/Y'));
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Formulario de confirmación -->
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="mb-4">
                    <label for="razon_baja" class="form-label">
                        <strong>Motivo de la Baja</strong>
                    </label>
                    <select class="form-select mb-2" name="razon_baja" id="razon_baja" required>
                        <option value="">Seleccionar motivo...</option>
                        <option value="Baja voluntaria">Baja voluntaria</option>
                        <option value="Cambio de institución">Cambio de institución</option>
                        <option value="Problemas económicos">Problemas económicos</option>
                        <option value="Rendimiento académico">Rendimiento académico</option>
                        <option value="Situación personal">Situación personal</option>
                        <option value="Baja administrativa">Baja administrativa</option>
                        <option value="Otro">Otro (especificar en observaciones)</option>
                    </select>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones Adicionales</label>
                        <textarea class="form-control" name="observaciones" id="observaciones" rows="3" 
                                  placeholder="Detalles adicionales sobre la baja del alumno..."></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmar" required>
                        <label class="form-check-label" for="confirmar">
                            Confirmo que he revisado la información y deseo proceder con la baja del alumno.
                        </label>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="ver_alumno.php?matricula=<?php echo urlencode($matricula); ?>" class="btn btn-secondary btn-cancel">
                        <i class='bx bx-x'></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-danger btn-delete" id="btnEliminar" disabled>
                        <i class='bx bx-trash'></i> Confirmar Baja del Alumno
                    </button>
                </div>
            </form>
            
            <script>
                // Habilitar botón solo cuando se marque la confirmación
                document.getElementById('confirmar').addEventListener('change', function() {
                    document.getElementById('btnEliminar').disabled = !this.checked;
                });
                
                // Confirmación adicional antes de enviar
                document.querySelector('form').addEventListener('submit', function(e) {
                    if (!confirm('¿ESTÁ ABSOLUTAMENTE SEGURO de dar de baja a este alumno?\n\nEsta acción no se puede deshacer fácilmente.')) {
                        e.preventDefault();
                    }
                });
                
                // Auto-habilitar si ya estaba marcado (en caso de recarga)
                if (document.getElementById('confirmar').checked) {
                    document.getElementById('btnEliminar').disabled = false;
                }
            </script>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>