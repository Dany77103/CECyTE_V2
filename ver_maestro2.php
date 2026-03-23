<?php
// ver_maestro.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'registro') {
    $_SESSION['error'] = "No tiene permisos para consultar expedientes";
    header('Location: gestion_maestros.php');
    exit();
}

if (!isset($_GET['numEmpleado'])) {
    header('Location: gestion_maestros.php?error=numEmpleado_invalida');
    exit();
}

$numEmpleado = trim($_GET['numEmpleado']);
$maestro = null;
$error = '';

if (!empty($numEmpleado)) {
    try {
        $sql = "SELECT m.*, 
                       g.genero AS genero_texto,
                       n.nacionalidad AS nacionalidad_texto,
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
            $error = "No se encontró el maestro con número de empleado: $numEmpleado";
        }
    } catch (PDOException $e) {
        $error = "Error al obtener datos: " . $e->getMessage();
    }
} else {
    $error = "Número de empleado no válido";
}

$edad = '';
if ($maestro && !empty($maestro['fecha_nacimiento']) && $maestro['fecha_nacimiento'] != '0000-00-00') {
    $fechaNac = new DateTime($maestro['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($fechaNac)->y;
}

$fechaNacimientoFormateada = ($maestro && !empty($maestro['fecha_nacimiento']) && $maestro['fecha_nacimiento'] != '0000-00-00') ? (new DateTime($maestro['fecha_nacimiento']))->format('d/m/Y') : '';
$fechaAltaFormateada = ($maestro && !empty($maestro['fechaAlta']) && $maestro['fechaAlta'] != '0000-00-00 00:00:00') ? (new DateTime($maestro['fechaAlta']))->format('d/m/Y H:i') : '';

$statusBadge = 'bg-secondary';
if ($maestro && isset($maestro['estado'])) {
    switch($maestro['estado']) {
        case 'Activo': $statusBadge = 'background: #e8f5e9; color: #2e7d32;'; break;
        case 'Inactivo': $statusBadge = 'background: #fdecea; color: #d32f2f;'; break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente | <?php echo $maestro ? htmlspecialchars($maestro['nombre']) : 'Error'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #1a5330; /* Verde CECyTE */
            --primary-light: #1a5330;
            --secondary: #6c757d;
            --white: #ffffff;
            --bg: #f4f6f9;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            color: #333;
            padding-top: 80px;
        }

        /* --- NAVBAR --- */
        .navbar-custom {
            background: var(--white);
            height: 70px;
            display: flex;
            align-items: center;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        /* --- MAIN CARD --- */
        .main-card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            background: var(--white);
        }

        .profile-header {
            padding: 40px;
            background: var(--white);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            border-left: 6px solid var(--primary);
        }

        .avatar-circle {
            width: 100px; height: 100px;
            background: #f8f9fa;
            color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; font-weight: 700;
            border-radius: 24px;
            box-shadow: inset 0 0 0 2px var(--primary);
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            display: inline-block;
        }

        /* --- SECCIONES DE DATOS --- */
        .section-title {
            font-size: 0.9rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #eee;
        }

        .info-group {
            margin-bottom: 1.5rem;
        }

        .info-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--secondary);
            font-weight: 700;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 0.95rem;
            color: #1a1a1a;
            font-weight: 500;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #dee2e6;
        }

        .btn-cecyte {
            background-color: var(--primary);
            color: white;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-cecyte:hover {
            background-color: var(--primary-light);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(109, 29, 50, 0.2);
        }

        @media print {
            .no-print { display: none !important; }
            body { padding-top: 0; background: white; }
            .main-card { box-shadow: none; border: 1px solid #eee; }
        }
    </style>
</head>
<body>

    <nav class="navbar-custom no-print">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-graduation-cap" style="color: var(--primary); font-size: 1.5rem;"></i>
                <div>
                    <h6 class="mb-0 fw-bold">Expediente Docente</h6>
                    <small class="text-muted">CECyTE Santa Catarina</small>
                </div>
            </div>
            <a href="gestion_maestros.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </nav>

    <div class="container mb-5">
        <?php if ($error): ?>
            <div class="alert alert-danger mt-5 border-0 shadow-sm rounded-4">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $error; ?>
            </div>
        <?php elseif ($maestro): ?>
            <div class="main-card mt-4">
                <div class="profile-header">
                    <div class="row align-items-center">
                        <div class="col-md-auto d-flex justify-content-center mb-3 mb-md-0">
                            <div class="avatar-circle">
                                <?php echo strtoupper(substr($maestro['nombre'], 0, 1) . substr($maestro['apellido_paterno'], 0, 1)); ?>
                            </div>
                        </div>
                        <div class="col-md text-center text-md-start ps-md-4">
                            <span class="status-badge mb-2" style="<?php echo $statusBadge; ?>">
                                <i class="fa-solid fa-circle me-1" style="font-size: 0.6rem;"></i> 
                                <?php echo $maestro['estado']; ?>
                            </span>
                            <h2 class="fw-bold mb-1" style="letter-spacing: -1px;">
                                <?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido_paterno'] . ' ' . $maestro['apellido_materno']); ?>
                            </h2>
                            <p class="text-muted fw-medium mb-0">
                                <i class="fa-solid fa-id-badge me-1"></i> No. Empleado: <span class="text-dark fw-bold"><?php echo $maestro['numEmpleado']; ?></span>
                            </p>
                        </div>
                        <div class="col-md-auto no-print mt-4 mt-md-0 d-flex gap-2 justify-content-center">
                            <a href="editar_maestro2.php?numEmpleado=<?php echo urlencode($maestro['numEmpleado']); ?>" class="btn btn-cecyte">
                                <i class="fa-solid fa-user-pen me-1"></i> Editar
                            </a>
                            <button onclick="window.print()" class="btn btn-light border fw-bold" style="border-radius: 10px;">
                                <i class="fa-solid fa-print me-1"></i> Imprimir
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <div class="row g-5">
                        <div class="col-lg-4">
                            <h5 class="section-title"><i class="fa-solid fa-user"></i> Personales</h5>
                            
                            <div class="info-group">
                                <div class="info-label">CURP</div>
                                <div class="info-value"><?php echo $maestro['curp'] ?: 'N/A'; ?></div>
                            </div>

                            <div class="info-group">
                                <div class="info-label">RFC</div>
                                <div class="info-value"><?php echo $maestro['rfc'] ?: 'N/A'; ?></div>
                            </div>

                            <div class="info-group">
                                <div class="info-label">Nacimiento</div>
                                <div class="info-value">
                                    <?php echo $fechaNacimientoFormateada ?: 'N/A'; ?>
                                    <?php if($edad) echo "<small class='text-muted'>( $edad años )</small>"; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <h5 class="section-title"><i class="fa-solid fa-book"></i> Académicos</h5>
                            
                            <div class="info-group">
                                <div class="info-label">Grado / Título</div>
                                <div class="info-value"><?php echo $maestro['titulo'] ?: 'No registrado'; ?></div>
                            </div>

                            <div class="info-group">
                                <div class="info-label">Especialidad</div>
                                <div class="info-value"><?php echo $maestro['especialidad'] ?: 'N/A'; ?></div>
                            </div>

                            <div class="info-group">
                                <div class="info-label">Fecha de Alta</div>
                                <div class="info-value"><?php echo $fechaAltaFormateada ?: 'N/A'; ?></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <h5 class="section-title"><i class="fa-solid fa-address-book"></i> Contacto</h5>
                            
                            <div class="info-group">
                                <div class="info-label">Email Institucional</div>
                                <div class="info-value text-truncate" title="<?php echo $maestro['correo_institucional']; ?>">
                                    <?php echo $maestro['correo_institucional'] ?: 'N/A'; ?>
                                </div>
                            </div>

                            <div class="info-group">
                                <div class="info-label">Teléfono Celular</div>
                                <div class="info-value"><?php echo $maestro['telefono_celular'] ?: 'N/A'; ?></div>
                            </div>

                            <div class="info-group">
                                <div class="info-label">Contacto Emergencia</div>
                                <div class="info-value text-danger fw-bold">
                                    <i class="fa-solid fa-phone-flip me-1"></i> <?php echo $maestro['telefono_emergencia'] ?: 'N/A'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>