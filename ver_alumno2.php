
<?php
// ver_alumno.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['matricula'])) {
    header('Location: gestion_alumnos.php?error=matricula_invalida');
    exit();
}

$matricula = trim($_GET['matricula']);

try {
    $sql = "SELECT a.*, 
                   g.genero AS genero_nombre,
                   n.nacionalidad AS nacionalidad_nombre,
                   e.estado AS estado_nacimiento_nombre,
                   c.nombre AS carrera_nombre, 
                   gr.nombre AS grupo_nombre,
                   est.tipoEstatus AS estatus_nombre,
                   d.tipo_discapacidad AS discapacidad_nombre,
                   gen.genero AS genero_actual_nombre,
                   nac.nacionalidad AS nacionalidad_actual_nombre
                  
            FROM alumnos a
            LEFT JOIN generos g ON a.id_genero = g.id_genero
            LEFT JOIN nacionalidades n ON a.id_nacionalidad = n.id_nacionalidad
            LEFT JOIN estados e ON a.id_estado = e.id_estado
            LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
            LEFT JOIN grupos gr ON a.id_grupo = gr.id_grupo
            LEFT JOIN estatus est ON a.id_estatus = est.id_estatus
            LEFT JOIN discapacidades d ON a.id_discapacidad = d.id_discapacidad
            LEFT JOIN generos gen ON a.id_genero = gen.id_genero
            LEFT JOIN nacionalidades nac ON a.id_nacionalidad = nac.id_nacionalidad
            
            WHERE a.matricula = :matricula";
    
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('Location: gestion_alumnos.php?error=not_found');
        exit();
    }
    
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener datos del alumno: " . $e->getMessage());
}

// Calcular edad
$edad = '';
if ($alumno['fecha_nacimiento'] && $alumno['fecha_nacimiento'] != '0000-00-00') {
    $fechaNac = new DateTime($alumno['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($fechaNac)->y;
}

// Formatear fechas
$fechaNacimientoFormateada = '';
if ($alumno['fecha_nacimiento'] && $alumno['fecha_nacimiento'] != '0000-00-00') {
    $fecha = new DateTime($alumno['fecha_nacimiento']);
    $fechaNacimientoFormateada = $fecha->format('d/m/Y');
}

$fechaIngresoFormateada = '';
if ($alumno['fecha_ingreso'] && $alumno['fecha_ingreso'] != '0000-00-00') {
    $fecha = new DateTime($alumno['fecha_ingreso']);
    $fechaIngresoFormateada = $fecha->format('d/m/Y');
}

// Determinar clase de estatus
$statusClass = 'status-secondary';
if ($alumno['estatus_nombre']) {
    switch($alumno['estatus_nombre']) {
        case 'Activo': $statusClass = 'status-success'; break;
        case 'Inactivo': $statusClass = 'status-warning'; break;
        case 'Egresado': $statusClass = 'status-info'; break;
        case 'Baja': $statusClass = 'status-danger'; break;
        default: $statusClass = 'status-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informaci&oacute;n del Alumno - CECyTE</title>
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
        
        .student-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .student-card-header {
            background: linear-gradient(to right, var(--verde-oscuro), var(--verde-principal));
            color: white;
            padding: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .student-card-body {
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
        
        .student-avatar {
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
        
        .student-photo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .student-photo {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid var(--verde-medio);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .no-photo {
            width: 200px;
            height: 200px;
            border-radius: 10px;
            background: linear-gradient(135deg, #f5f5f5, #e8e8e8);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px dashed #b0b0b0;
            margin: 0 auto;
        }
        
        .badge-foto {
            background-color: #4caf50;
            color: white;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 10px;
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
            
            .student-avatar {
                width: 80px;
                height: 80px;
                font-size: 2em;
            }
            
            .student-photo, .no-photo {
                width: 150px;
                height: 150px;
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
                    <h2><i class='bx bx-user-circle'></i> Informaci&oacute;n del Alumno</h2>
                    <p class="mb-0">Sistema de Gesti&oacute;n Escolar - CECyTE</p>
                </div>
                <div>
                    <a href="gestion_alumnos.php" class="btn btn-light">
                        <i class='bx bx-arrow-back'></i> Volver a la Lista
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Encabezado del alumno -->
        <div class="student-card">
            <div class="student-card-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex align-items-center gap-4">
                        <div class="student-avatar">
                            <?php 
                            $inicial = strtoupper(substr($alumno['nombre'], 0, 1));
                            if (!empty($alumno['apellido_paterno'])) {
                                $inicial .= strtoupper(substr($alumno['apellido_paterno'], 0, 1));
                            }
                            echo $inicial;
                            ?>
                        </div>
                        <div>
                            <div class="matricula-badge">
                                <i class='bx bx-id-card'></i> Matr&iacute;cula: <?php echo htmlspecialchars($alumno['matricula']); ?>
                            </div>
                            <h3 class="mb-2 text-white">
                                <i class='bx bx-user'></i> 
                                <?php 
                                $nombreCompleto = '';
                                if (!empty($alumno['apellido_paterno'])) {
                                    $nombreCompleto .= $alumno['apellido_paterno'] . ' ';
                                }
                                if (!empty($alumno['apellido_materno'])) {
                                    $nombreCompleto .= $alumno['apellido_materno'] . ' ';
                                }
                                $nombreCompleto .= $alumno['nombre'];
                                echo htmlspecialchars($nombreCompleto);
                                ?>
                            </h3>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <?php if (!empty($alumno['carrera_nombre'])): ?>
                                    <span class="badge-pill">
                                        <i class='bx bx-book'></i> <?php echo htmlspecialchars($alumno['carrera_nombre']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($alumno['semestre'])): ?>
                                    <span class="badge-pill">
                                        <i class='bx bx-calendar'></i> Semestre <?php echo htmlspecialchars($alumno['semestre']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($alumno['grupo_nombre'])): ?>
                                    <span class="badge-pill">
                                        <i class='bx bx-group'></i> <?php echo htmlspecialchars($alumno['grupo_nombre']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="badge-status <?php echo $statusClass; ?>">
                                    <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($alumno['estatus_nombre'] ?? 'Sin estatus'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="btn-group">
                        <a href="editar_alumnos2.php?matricula=<?php echo urlencode($alumno['matricula']); ?>" 
                           class="btn btn-light">
                            <i class='bx bx-edit'></i> Editar
                        </a>
                        <button onclick="window.print()" class="btn btn-light">
                            <i class='bx bx-printer'></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="student-card-body">
                <div class="row">
                    <!-- Columna izquierda - Foto y datos básicos -->
                    <div class="col-md-4">
                        <div class="student-photo-container">
                            <?php if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])): ?>
                                <img src="<?php echo htmlspecialchars($alumno['rutaImagen']); ?>" 
                                     alt="Foto del alumno" class="student-photo">
                                <div class="badge-foto">FOTO DISPONIBLE</div>
                            <?php else: ?>
                                <div class="no-photo">
                                    <i class='bx bx-user' style="font-size: 5rem; color: #999;"></i>
                                </div>
                                <div class="badge-foto" style="background-color: #ff9800;">SIN FOTO</div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Contacto -->
                        <div class="info-section">
                            <h5><i class='bx bx-phone'></i> Contacto</h5>
                            <div class="info-row">
                                <div class="info-label">Tel&eacute;fono Celular:</div>
                                <div class="info-value">
                                    <?php if (!empty($alumno['telefono_celular'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($alumno['telefono_celular']); ?>" class="contact-link">
                                            <i class='bx bx-phone'></i> <?php echo htmlspecialchars($alumno['telefono_celular']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Tel&eacute;fono Casa:</div>
                                <div class="info-value">
                                    <?php if (!empty($alumno['telefono_casa'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($alumno['telefono_casa']); ?>" class="contact-link">
                                            <i class='bx bx-home'></i> <?php echo htmlspecialchars($alumno['telefono_casa']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Tel&eacute;fono Emergencia:</div>
                                <div class="info-value">
                                    <?php if (!empty($alumno['telefono_emergencia'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($alumno['telefono_emergencia']); ?>" class="contact-link">
                                            <i class='bx bx-phone-call'></i> <?php echo htmlspecialchars($alumno['telefono_emergencia']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Correo Institucional:</div>
                                <div class="info-value">
                                    <?php if (!empty($alumno['correo_institucional'])): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($alumno['correo_institucional']); ?>" class="contact-link">
                                            <i class='bx bx-envelope'></i> <?php echo htmlspecialchars($alumno['correo_institucional']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No asignado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Correo Personal:</div>
                                <div class="info-value">
                                    <?php if (!empty($alumno['correo_personal'])): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($alumno['correo_personal']); ?>" class="contact-link">
                                            <i class='bx bx-mail-send'></i> <?php echo htmlspecialchars($alumno['correo_personal']); ?>
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
                                    if (!empty($alumno['genero_nombre'])) {
                                        echo htmlspecialchars($alumno['genero_nombre']);
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
                                    if (!empty($alumno['nacionalidad_nombre'])) {
                                        echo htmlspecialchars($alumno['nacionalidad_nombre']);
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
                                    if (!empty($alumno['estado_nacimiento_nombre'])) {
                                        echo htmlspecialchars($alumno['estado_nacimiento_nombre']);
                                    } else {
                                        echo 'No especificado';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Discapacidad:</div>
                                <div class="info-value">
                                    <?php 
                                    if (!empty($alumno['discapacidad_nombre'])) {
                                        echo htmlspecialchars($alumno['discapacidad_nombre']);
                                    } else {
                                        echo 'Ninguna';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Columna derecha - Datos completos -->
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
                                            if (!empty($alumno['apellido_paterno'])) {
                                                $nombreCompleto .= $alumno['apellido_paterno'] . ' ';
                                            }
                                            if (!empty($alumno['apellido_materno'])) {
                                                $nombreCompleto .= $alumno['apellido_materno'] . ' ';
                                            }
                                            $nombreCompleto .= $alumno['nombre'];
                                            echo htmlspecialchars($nombreCompleto);
                                            ?>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Matr&iacute;cula:</div>
                                        <div class="info-value">
                                            <span class="badge-status status-info">
                                                <?php echo htmlspecialchars($alumno['matricula']); ?>
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
                                        <div class="info-label">CURP:</div>
                                        <div class="info-value">
                                            <code><?php echo htmlspecialchars($alumno['curp'] ?? 'No especificado'); ?></code>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">RFC:</div>
                                        <div class="info-value">
                                            <code><?php echo htmlspecialchars($alumno['rfc'] ?? 'No especificado'); ?></code>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Tipo de Sangre:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($alumno['tipo_sangre'] ?? 'No especificado'); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Datos Académicos -->
                            <div class="col-md-6">
                                <div class="info-section">
                                    <h5><i class='bx bx-book-reader'></i> Datos Acad&eacute;micos</h5>
                                    <div class="info-row">
                                        <div class="info-label">Carrera:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($alumno['carrera_nombre'] ?? 'No asignada'); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Semestre:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($alumno['semestre'] ?? '') . '° Semestre'; ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Grupo:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($alumno['grupo_nombre'] ?? 'No asignado'); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Turno:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($alumno['turno'] ?? 'No especificado'); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Fecha de Ingreso:</div>
                                        <div class="info-value">
                                            <?php if ($fechaIngresoFormateada): ?>
                                                <strong><?php echo $fechaIngresoFormateada; ?></strong>
                                            <?php else: ?>
                                                No especificada
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Estatus:</div>
                                        <div class="info-value">
                                            <span class="badge-status <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($alumno['estatus_nombre'] ?? 'Sin estatus'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dirección -->
                        <div class="info-section">
                            <h5><i class='bx bx-home'></i> Direcci&oacute;n</h5>
                            <div class="info-row">
                                <div class="info-label">Direcci&oacute;n:</div>
                                <div class="info-value"><?php echo htmlspecialchars($alumno['direccion'] ?? 'No especificada'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Colonia:</div>
                                <div class="info-value"><?php echo htmlspecialchars($alumno['colonia'] ?? 'No especificada'); ?></div>
                            </div>
                        </div>
                        
                        <!-- Salud -->
                        <div class="info-section">
                            <h5><i class='bx bx-plus-medical'></i> Informaci&oacute;n de Salud</h5>
                            <div class="info-row">
                                <div class="info-label">Alergias:</div>
                                <div class="info-value"><?php echo htmlspecialchars($alumno['alergias'] ?? 'No especificadas'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Enfermedades Cr&oacute;nicas:</div>
                                <div class="info-value"><?php echo htmlspecialchars($alumno['enfermedades_cronicas'] ?? 'No especificadas'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Seguro M&eacute;dico:</div>
                                <div class="info-value"><?php echo htmlspecialchars($alumno['seguro_medico'] ?? 'No especificado'); ?></div>
                            </div>
                        </div>
                        
                        <!-- Procedencia y Beca -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-section">
                                    <h5><i class='bx bx-school'></i> Procedencia</h5>
                                    <div class="info-row">
                                        <div class="info-label">Escuela Procedencia:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($alumno['escuela_procedencia'] ?? 'No especificada'); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Promedio Secundaria:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($alumno['promedio_secundaria'] ?? 'No especificado'); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-section">
                                    <h5><i class='bx bx-award'></i> Beca</h5>
                                    <div class="info-row">
                                        <div class="info-label">Beca:</div>
                                        <div class="info-value">
                                            <?php 
                                            $beca_text = $alumno['beca'] == 'SI' ? 'Sí' : ($alumno['beca'] == 'NO' ? 'No' : 'No especificado');
                                            echo $beca_text;
                                            ?>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Porcentaje Beca:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($alumno['porcentaje_beca'] ?? '0'); ?>%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información de Padres/Tutores -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="info-section">
                            <h5><i class='bx bx-male'></i> Informaci&oacute;n del Padre</h5>
                            <div class="info-row">
                                <div class="info-label">Nombre:</div>
                                <div class="info-value"><?php echo htmlspecialchars($alumno['nombre_padre'] ?? 'No especificado'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Ocupaci&oacute;n:</div>
                                <div class="info-value"><?php echo htmlspecialchars($alumno['ocupacion_padre'] ?? 'No especificada'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Tel&eacute;fono:</div>
                                <div class="info-value">
                                    <?php if (!empty($alumno['telefono_padre'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($alumno['telefono_padre']); ?>" class="contact-link">
                                            <i class='bx bx-phone'></i> <?php echo htmlspecialchars($alumno['telefono_padre']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-section">
                            <h5><i class='bx bx-female'></i> Informaci&oacute;n de la Madre</h5>
                            <div class="info-row">
                                <div class="info-label">Nombre:</div>
                                <div class="info-value"><?php echo htmlspecialchars($alumno['nombre_madre'] ?? 'No especificado'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Ocupaci&oacute;n:</div>
                                <div class="info-value"><?php echo htmlspecialchars($alumno['ocupacion_madre'] ?? 'No especificada'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Tel&eacute;fono:</div>
                                <div class="info-value">
                                    <?php if (!empty($alumno['telefono_madre'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($alumno['telefono_madre']); ?>" class="contact-link">
                                            <i class='bx bx-phone'></i> <?php echo htmlspecialchars($alumno['telefono_madre']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- QR Code -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="qr-container">
                            <h5 class="text-center mb-3" style="color: var(--verde-oscuro);">
                                <i class='bx bx-qr-scan'></i> C&oacute;digo QR del Alumno
                            </h5>
                            <div class="mb-3">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode('CECyTE-Alumno:' . $alumno['matricula'] . '|' . $alumno['nombre'] . ' ' . $alumno['apellido_paterno']); ?>" 
                                     alt="QR Code" class="img-fluid">
                            </div>
                            <p class="text-muted small mb-3">
                                Escanee este c&oacute;digo para acceder r&aacute;pidamente a la informaci&oacute;n del alumno
                            </p>
                            <a href="qr_alumno2.php?matricula=<?php echo urlencode($alumno['matricula']); ?>" 
                               class="btn btn-cecyte btn-sm">
                                <i class='bx bx-download'></i> Descargar QR
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Observaciones -->
                <?php if (!empty($alumno['observaciones'])): ?>
                <div class="info-section mt-4">
                    <h5><i class='bx bx-note'></i> Observaciones</h5>
                    <div class="alert-cecyte">
                        <?php echo nl2br(htmlspecialchars($alumno['observaciones'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Botones de acción -->
        <div class="d-flex justify-content-between mt-4 mb-5 flex-wrap gap-3">
            <a href="gestion_alumnos.php" class="btn btn-secondary">
                <i class='bx bx-arrow-back'></i> Volver a la Lista
            </a>
            <div class="d-flex flex-wrap gap-2">
                <a href="editar_alumnos2.php?matricula=<?php echo urlencode($alumno['matricula']); ?>" 
                   class="btn btn-cecyte">
                    <i class='bx bx-edit'></i> Editar Alumno
                </a>
                <a href="eliminar_alumno2.php?matricula=<?php echo urlencode($alumno['matricula']); ?>" 
                   class="btn btn-cecyte-danger"
                   onclick="return confirm('¿Est&aacute; seguro de eliminar este alumno? Esta acci&oacute;n no se puede deshacer.')">
                    <i class='bx bx-trash'></i> Eliminar Alumno
                </a>
                <a href="qr_alumno2.php?matricula=<?php echo urlencode($alumno['matricula']); ?>" 
                   class="btn btn-cecyte" style="background: linear-gradient(135deg, var(--verde-claro), var(--verde-medio));">
                    <i class='bx bx-qr'></i> Generar QR
                </a>
                <?php if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])): ?>
                    <a href="gestionar_fotos.php?busqueda=<?php echo urlencode($alumno['matricula']); ?>" 
                       class="btn btn-cecyte" style="background: linear-gradient(135deg, #ff9800, #ffb74d);">
                        <i class='bx bx-camera'></i> Gestionar Foto
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para copiar la matrícula al portapapeles
        function copiarMatricula() {
            const matricula = '<?php echo $alumno["matricula"]; ?>';
            navigator.clipboard.writeText(matricula).then(() => {
                alert('Matrícula copiada: ' + matricula);
            });
        }
        
        // Función para imprimir solo la tarjeta del alumno
        document.addEventListener('DOMContentLoaded', function() {
            const printButtons = document.querySelectorAll('[onclick="window.print()"]');
            printButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Crear contenido para imprimir
                    const originalContent = document.body.innerHTML;
                    const printContent = document.querySelector('.student-card').outerHTML;
                    
                    document.body.innerHTML = `
                        <div style="padding: 20px;">
                            <h2 class="text-center" style="color: var(--verde-oscuro);">
                                <i class='bx bx-user-circle'></i> Información del Alumno - CECyTE
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