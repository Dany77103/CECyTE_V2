
<?php
// ver_maestro.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Verificar permisos del usuario (opcional)
if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'registro') {
    $_SESSION['error'] = "No tiene permisos para registrar nuevos maestros";
    header('Location: gestion_maestros.php');
    exit();
}

if (!isset($_GET['numEmpleado'])) {
    header('Location: gestion_maestros.php?error=numEmpleado_invalida');
    exit();
}

$numEmpleado = trim($_GET['numEmpleado']);

// Inicializar variables
$maestro = null;
$error = '';

// Obtener datos del maestro con JOIN para obtener nombres en lugar de IDs
if (!empty($numEmpleado)) {
    try {
        // Usar PDO
        $sql = "SELECT m.*, 
                       g.genero AS genero,
                       n.nacionalidad AS nacionalidad,
                       e.estado AS estado_nacimiento
                FROM maestros m
                LEFT JOIN generos g ON m.id_genero = g.id_genero
                LEFT JOIN nacionalidades n ON m.id_nacionalidad = n.id_nacionalidad
                LEFT JOIN estados e ON m.id_estado = e.id_estado
                WHERE m.numEmpleado = :numEmpleado";
        
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $maestro = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "No se encontr&oacute; el maestro con n&uacute;mero de empleado: $numEmpleado";
        }
        
    } catch (PDOException $e) {
        $error = "Error al obtener datos del maestro: " . $e->getMessage();
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
} else {
    $error = "N&uacute;mero de empleado de maestro no v&aacute;lido";
}

// Calcular edad
$edad = '';
if ($maestro && !empty($maestro['fecha_nacimiento']) && $maestro['fecha_nacimiento'] != '0000-00-00') {
    $fechaNac = new DateTime($maestro['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($fechaNac)->y;
}

// Formatear fechas
$fechaNacimientoFormateada = '';
if ($maestro && !empty($maestro['fecha_nacimiento']) && $maestro['fecha_nacimiento'] != '0000-00-00') {
    $fecha = new DateTime($maestro['fecha_nacimiento']);
    $fechaNacimientoFormateada = $fecha->format('d/m/Y');
}

$fechaAltaFormateada = '';
if ($maestro && !empty($maestro['fechaAlta']) && $maestro['fechaAlta'] != '0000-00-00 00:00:00') {
    $fecha = new DateTime($maestro['fechaAlta']);
    $fechaAltaFormateada = $fecha->format('d/m/Y H:i:s');
}

$fechaModificacionFormateada = '';
if ($maestro && !empty($maestro['fechaModificacion']) && $maestro['fechaModificacion'] != '0000-00-00 00:00:00') {
    $fecha = new DateTime($maestro['fechaModificacion']);
    $fechaModificacionFormateada = $fecha->format('d/m/Y H:i:s');
}

// Determinar clase de estatus
$statusClass = 'status-secondary';
if ($maestro && isset($maestro['estado'])) {
    switch($maestro['estado']) {
        case 'Activo': $statusClass = 'status-success'; break;
        case 'Inactivo': $statusClass = 'status-warning'; break;
        default: $statusClass = 'status-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informaci&oacute;n del Maestro - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --verde-oscuro: #1a5330;      /* Verde oscuro principal */
            --verde-principal: #2e7d32;   /* Verde principal */
            --verde-medio: #4caf50;       /* Verde medio */
            --verde-claro: #8bc34a;       /* Verde claro/accent */
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
            border-bottom: 4px solid var(--verde-claro);
        }
        
        .teacher-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .teacher-card-header {
            background: linear-gradient(to right, var(--verde-oscuro), var(--verde-principal));
            color: white;
            padding: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .teacher-card-body {
            padding: 30px;
        }
        
        .info-section {
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--verde-medio);
        }
        
        .info-section h5 {
            color: var(--verde-oscuro);
            border-bottom: 2px solid var(--verde-claro);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e8e8e8;
        }
        
        .info-label {
            flex: 0 0 250px;
            font-weight: 600;
            color: var(--verde-oscuro);
            font-size: 0.95rem;
        }
        
        .info-value {
            flex: 1;
            color: #333;
            font-weight: 500;
        }
        
        .badge-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .status-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-secondary {
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }
        
        .qr-container {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid var(--verde-medio);
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .btn-cecyte {
            background: linear-gradient(135deg, var(--verde-principal), var(--verde-medio));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(46, 125, 50, 0.2);
        }
        
        .btn-cecyte:hover {
            background: linear-gradient(135deg, var(--verde-oscuro), var(--verde-principal));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(26, 83, 48, 0.3);
        }
        
        .btn-cecyte-danger {
            background: linear-gradient(135deg, #dc3545, #e35d6a);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(220, 53, 69, 0.2);
        }
        
        .btn-cecyte-danger:hover {
            background: linear-gradient(135deg, #bb2d3b, #c82333);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(187, 45, 59, 0.3);
        }
        
        .matricula-badge {
            background: var(--verde-claro);
            color: #333;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
            border: 2px solid rgba(0,0,0,0.1);
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        
        .teacher-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--verde-medio), var(--verde-claro));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5em;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border: 4px solid white;
        }
        
        .contact-link {
            color: var(--verde-principal);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .contact-link:hover {
            color: var(--verde-oscuro);
            text-decoration: underline;
        }
        
        .alert-cecyte {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 1px solid #c3e6cb;
            color: #155724;
            border-radius: 10px;
            padding: 15px;
            border-left: 5px solid var(--verde-medio);
        }
        
        .badge-pill {
            background-color: var(--verde-claro);
            color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85em;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }
        
        @media (max-width: 768px) {
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-label {
                flex: 0 0 auto;
                margin-bottom: 5px;
            }
            
            .teacher-avatar {
                width: 80px;
                height: 80px;
                font-size: 2em;
            }
            
            .btn-cecyte, .btn-cecyte-danger {
                padding: 10px 15px;
                font-size: 0.9rem;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header-cecyte">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class='bx bx-user-circle'></i> Informaci&oacute;n del Maestro</h2>
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
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <div class="d-flex justify-content-between mt-4 mb-5">
                <a href="gestion_maestros.php" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Volver a la Lista
                </a>
            </div>
        <?php elseif ($maestro): ?>
            <!-- Encabezado del maestro -->
            <div class="teacher-card">
                <div class="teacher-card-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-center gap-4">
                            <div class="teacher-avatar">
                                <?php 
                                $inicial = strtoupper(substr($maestro['nombre'], 0, 1));
                                if (!empty($maestro['apellido_paterno'])) {
                                    $inicial .= strtoupper(substr($maestro['apellido_paterno'], 0, 1));
                                }
                                echo $inicial;
                                ?>
                            </div>
                            <div>
                                <div class="matricula-badge">
                                    <i class='bx bx-id-card'></i> No. Empleado: <?php echo htmlspecialchars($maestro['numEmpleado']); ?>
                                </div>
                                <h3 class="mb-2 text-white">
                                    <i class='bx bx-user'></i> 
                                    <?php 
                                    $nombreCompleto = '';
                                    if (!empty($maestro['apellido_paterno'])) {
                                        $nombreCompleto .= $maestro['apellido_paterno'] . ' ';
                                    }
                                    if (!empty($maestro['apellido_materno'])) {
                                        $nombreCompleto .= $maestro['apellido_materno'] . ' ';
                                    }
                                    $nombreCompleto .= $maestro['nombre'];
                                    echo htmlspecialchars($nombreCompleto);
                                    ?>
                                </h3>
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <?php if (!empty($maestro['titulo'])): ?>
                                        <span class="badge-pill">
                                            <i class='bx bx-briefcase'></i> <?php echo htmlspecialchars($maestro['titulo']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($maestro['especialidad'])): ?>
                                        <span class="badge-pill">
                                            <i class='bx bx-star'></i> <?php echo htmlspecialchars($maestro['especialidad']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="badge-status <?php echo $statusClass; ?>">
                                        <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($maestro['estado'] ?? 'Sin estatus'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="btn-group">
                            <a href="editar_maestro2.php?numEmpleado=<?php echo urlencode($maestro['numEmpleado']); ?>" 
                               class="btn btn-light">
                                <i class='bx bx-edit'></i> Editar
                            </a>
                            <button onclick="window.print()" class="btn btn-light">
                                <i class='bx bx-printer'></i> Imprimir
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="teacher-card-body">
                    <div class="row">
                        <!-- Columna izquierda -->
                        <div class="col-md-8">
                            <div class="row">
                                <!-- Datos Personales -->
                                <div class="col-md-6">
                                    <div class="info-section">
                                        <h5><i class='bx bx-user-circle'></i> Datos Personales</h5>
                                        <div class="info-row">
                                            <div class="info-label">Nombre Completo:</div>
                                            <div class="info-value">
                                                <?php 
                                                $nombreCompleto = '';
                                                if (!empty($maestro['apellido_paterno'])) {
                                                    $nombreCompleto .= $maestro['apellido_paterno'] . ' ';
                                                }
                                                if (!empty($maestro['apellido_materno'])) {
                                                    $nombreCompleto .= $maestro['apellido_materno'] . ' ';
                                                }
                                                $nombreCompleto .= $maestro['nombre'];
                                                echo htmlspecialchars($nombreCompleto);
                                                ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">N&uacute;mero Empleado:</div>
                                            <div class="info-value">
                                                <span class="badge-status status-info">
                                                    <?php echo htmlspecialchars($maestro['numEmpleado']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Fecha Nacimiento:</div>
                                            <div class="info-value">
                                                <?php if ($fechaNacimientoFormateada): ?>
                                                    <strong><?php echo $fechaNacimientoFormateada; ?></strong>
                                                    <?php if ($edad): ?> 
                                                        <span class="text-muted">(<?php echo $edad; ?> a&ntilde;os)</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    No especificada
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">G&eacute;nero:</div>
                                            <div class="info-value">
                                                <?php 
                                                if (!empty($maestro['genero'])) {
                                                    echo htmlspecialchars($maestro['genero']);
                                                } elseif (!empty($maestro['genero'])) {
                                                    echo htmlspecialchars($maestro['genero']);
                                                } else {
                                                    echo 'No especificado';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Estado Civil:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($maestro['estado_civil'] ?? 'No especificado'); ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">CURP:</div>
                                            <div class="info-value">
                                                <code><?php echo htmlspecialchars($maestro['curp'] ?? 'No especificado'); ?></code>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">RFC:</div>
                                            <div class="info-value">
                                                <code><?php echo htmlspecialchars($maestro['rfc'] ?? 'No especificado'); ?></code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Datos Profesionales -->
                                <div class="col-md-6">
                                    <div class="info-section">
                                        <h5><i class='bx bx-briefcase'></i> Datos Profesionales</h5>
                                        <div class="info-row">
                                            <div class="info-label">T&iacute;tulo:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($maestro['titulo'] ?? 'No especificado'); ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Especialidad:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($maestro['especialidad'] ?? 'No especificada'); ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Fecha de Alta:</div>
                                            <div class="info-value">
                                                <?php if ($fechaAltaFormateada): ?>
                                                    <strong><?php echo $fechaAltaFormateada; ?></strong>
                                                <?php else: ?>
                                                    No especificada
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">&Uacute;ltima Modificaci&oacute;n:</div>
                                            <div class="info-value">
                                                <?php if ($fechaModificacionFormateada): ?>
                                                    <strong><?php echo $fechaModificacionFormateada; ?></strong>
                                                <?php else: ?>
                                                    No modificado
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Estado:</div>
                                            <div class="info-value">
                                                <span class="badge-status <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($maestro['estado'] ?? 'Sin estatus'); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dirección -->
                            <div class="info-section">
                                <h5><i class='bx bx-home'></i> Direcci&oacute;n</h5>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="info-row">
                                            <div class="info-label">Direcci&oacute;n:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($maestro['direccion'] ?? 'No especificada'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna derecha -->
                        <div class="col-md-4">
                            <!-- Contacto -->
                            <div class="info-section">
                                <h5><i class='bx bx-phone'></i> Contacto</h5>
                                <div class="info-row">
                                    <div class="info-label">Tel&eacute;fono Celular:</div>
                                    <div class="info-value">
                                        <?php if (!empty($maestro['telefono_celular'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($maestro['telefono_celular']); ?>" class="contact-link">
                                                <i class='bx bx-phone'></i> <?php echo htmlspecialchars($maestro['telefono_celular']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No especificado</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Tel&eacute;fono Emergencia:</div>
                                    <div class="info-value">
                                        <?php if (!empty($maestro['telefono_emergencia'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($maestro['telefono_emergencia']); ?>" class="contact-link">
                                                <i class='bx bx-phone-call'></i> <?php echo htmlspecialchars($maestro['telefono_emergencia']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No especificado</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Correo Institucional:</div>
                                    <div class="info-value">
                                        <?php if (!empty($maestro['correo_institucional'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($maestro['correo_institucional']); ?>" class="contact-link">
                                                <i class='bx bx-envelope'></i> <?php echo htmlspecialchars($maestro['correo_institucional']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No especificado</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Correo Personal:</div>
                                    <div class="info-value">
                                        <?php if (!empty($maestro['correo_personal'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($maestro['correo_personal']); ?>" class="contact-link">
                                                <i class='bx bx-mail-send'></i> <?php echo htmlspecialchars($maestro['correo_personal']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No especificado</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Información Adicional -->
                            <div class="info-section">
                                <h5><i class='bx bx-info-circle'></i> Informaci&oacute;n Adicional</h5>
                                <div class="info-row">
                                    <div class="info-label">G&eacute;nero:</div>
                                    <div class="info-value">
                                        <?php 
                                        if (!empty($maestro['genero'])) {
                                            echo htmlspecialchars($maestro['genero']);
                                        } elseif (!empty($maestro['genero'])) {
                                            echo htmlspecialchars($maestro['genero']);
                                        } else {
                                            echo 'No especificado';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Nacionalidad:</div>
                                    <div class="info-value">
                                        <?php 
                                        if (!empty($maestro['nacionalidad'])) {
                                            echo htmlspecialchars($maestro['nacionalidad']);
                                        } else {
                                            echo 'No especificada';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Estado de Nacimiento:</div>
                                    <div class="info-value">
                                        <?php 
                                        if (!empty($maestro['estado_nacimiento'])) {
                                            echo htmlspecialchars($maestro['estado_nacimiento']);
                                        } else {
                                            echo 'No especificado';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- QR Code -->
                            <div class="qr-container mt-4">
                                <h5 class="text-center mb-3" style="color: var(--verde-oscuro);">
                                    <i class='bx bx-qr-scan'></i> C&oacute;digo QR
                                </h5>
                                <div class="mb-3">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode('CECyTE-Maestro:' . $maestro['numEmpleado'] . '|' . $maestro['nombre'] . ' ' . $maestro['apellido_paterno']); ?>" 
                                         alt="QR Code" class="img-fluid">
                                </div>
                                <p class="text-muted small mb-3">
                                    Escanee este c&oacute;digo para acceder r&aacute;pidamente a la informaci&oacute;n del maestro
                                </p>
                                <a href="qr_maestro2.php?id=<?php echo urlencode($maestro['id_maestro']); ?>" 
                                   class="btn btn-cecyte btn-sm">
                                    <i class='bx bx-download'></i> Descargar QR
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observaciones -->
                    <?php if (!empty($maestro['observaciones'])): ?>
                    <div class="info-section mt-4">
                        <h5><i class='bx bx-note'></i> Observaciones</h5>
                        <div class="alert-cecyte">
                            <?php echo nl2br(htmlspecialchars($maestro['observaciones'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <div class="d-flex justify-content-between mt-4 mb-5 flex-wrap gap-3">
                <a href="gestion_maestros.php" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Volver a la Lista
                </a>
                <div class="d-flex flex-wrap gap-2">
                    <a href="editar_maestro2.php?numEmpleado=<?php echo urlencode($maestro['numEmpleado']); ?>" 
                       class="btn btn-cecyte">
                        <i class='bx bx-edit'></i> Editar Maestro
                    </a>
                    <a href="eliminar_maestro2.php?numEmpleado=<?php echo urlencode($maestro['numEmpleado']); ?>" 
                       class="btn btn-cecyte-danger"
                       onclick="return confirm('¿Est&aacute; seguro de eliminar este maestro? Esta acci&oacute;n no se puede deshacer.')">
                        <i class='bx bx-trash'></i> Eliminar Maestro
                    </a>
                    <a href="qr_maestro2.php?numEmpleado=<?php echo urlencode($maestro['numEmpleado']); ?>" 
                       class="btn btn-cecyte" style="background: linear-gradient(135deg, var(--verde-claro), var(--verde-medio));">
                        <i class='bx bx-qr'></i> Generar QR
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para copiar el número de empleado al portapapeles
        function copiarNumEmpleado() {
            const numEmpleado = '<?php echo $maestro["numEmpleado"]; ?>';
            navigator.clipboard.writeText(numEmpleado).then(() => {
                alert('Número de empleado copiado: ' + numEmpleado);
            });
        }
        
        // Función para imprimir solo la tarjeta del maestro
        document.addEventListener('DOMContentLoaded', function() {
            const printButtons = document.querySelectorAll('[onclick="window.print()"]');
            printButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Crear contenido para imprimir
                    const originalContent = document.body.innerHTML;
                    const printContent = document.querySelector('.teacher-card').outerHTML;
                    
                    document.body.innerHTML = `
                        <div style="padding: 20px;">
                            <h2 class="text-center" style="color: var(--verde-oscuro);">
                                <i class='bx bx-user-circle'></i> Información del Maestro - CECyTE
                            </h2>
                            <hr>
                            ${printContent}
                            <div class="text-center mt-4" style="color: #666;">
                                <small>Documento generado el: <?php echo date('d/m/Y H:i:s'); ?></small>
                            </div>
                        </div>
                    `;
                    
                    window.print();
                    
                    // Restaurar contenido original
                    document.body.innerHTML = originalContent;
                    window.location.reload();
                });
            });
        });
    </script>
</body>
</html>