<?php
// editar_maestro.php
session_start();
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Verificar que se proporcionó el número de empleado
if (!isset($_GET['numEmpleado']) || empty($_GET['numEmpleado'])) {
    $_SESSION['error'] = "No se especificó el número de empleado.";
    header('Location: gestion_maestros.php');
    exit();
}

$numEmpleado = trim($_GET['numEmpleado']);

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtener datos actuales del maestro
try {
    $sql_maestro = "SELECT m.*, g.genero, n.nacionalidad, e.estado 
                    FROM maestros m 
                    LEFT JOIN generos g ON m.id_genero = g.id_genero 
                    LEFT JOIN nacionalidades n ON m.id_nacionalidad = n.id_nacionalidad 
                    LEFT JOIN estados e ON m.id_estado = e.id_estado 
                    WHERE m.numEmpleado = :numEmpleado";
    
    $stmt_maestro = $con->prepare($sql_maestro);
    $stmt_maestro->bindParam(':numEmpleado', $numEmpleado);
    $stmt_maestro->execute();
    $maestro = $stmt_maestro->fetch(PDO::FETCH_ASSOC);
    
    if (!$maestro) {
        $_SESSION['error'] = "Maestro no encontrado.";
        header('Location: gestion_maestros.php');
        exit();
    }
    
    $generos = $con->query("SELECT * FROM generos ORDER BY genero")->fetchAll(PDO::FETCH_ASSOC);
    $nacionalidades = $con->query("SELECT * FROM nacionalidades ORDER BY nacionalidad")->fetchAll(PDO::FETCH_ASSOC);
    $estados = $con->query("SELECT * FROM estados ORDER BY estado")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errores[] = "Token de seguridad inválido.";
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
        // ... (recopilación de otros campos del POST igual que tu original)

        if (empty($errores)) {
            try {
                $sql = "UPDATE maestros SET 
                        nombre = :nombre, apellido_paterno = :apellido_paterno, 
                        apellido_materno = :apellido_materno, fecha_nacimiento = :fecha_nacimiento,
                        id_genero = :id_genero, rfc = :rfc, curp = :curp, 
                        id_nacionalidad = :id_nacionalidad, id_estado = :id_estado,
                        direccion = :direccion, telefono_celular = :telefono_celular,
                        telefono_emergencia = :telefono_emergencia, correo_institucional = :correo_institucional,
                        correo_personal = :correo_personal, estado_civil = :estado_civil,
                        especialidad = :especialidad, titulo = :titulo, 
                        observaciones = :observaciones, activo = :activo,
                        fechaModificacion = NOW()
                        WHERE numEmpleado = :numEmpleado";
                
                $stmt = $con->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre, ':apellido_paterno' => $apellido_paterno,
                    ':apellido_materno' => trim($_POST['apellido_materno'] ?? ''),
                    ':fecha_nacimiento' => $_POST['fecha_nacimiento'],
                    ':id_genero' => $_POST['id_genero'], ':rfc' => $_POST['rfc'],
                    ':curp' => $_POST['curp'], ':id_nacionalidad' => $_POST['id_nacionalidad'],
                    ':id_estado' => $_POST['id_estado'], ':direccion' => $_POST['direccion'],
                    ':telefono_celular' => $_POST['telefono_celular'],
                    ':telefono_emergencia' => $_POST['telefono_emergencia'],
                    ':correo_institucional' => $_POST['correo_institucional'],
                    ':correo_personal' => $_POST['correo_personal'],
                    ':estado_civil' => $_POST['estado_civil'],
                    ':especialidad' => $_POST['especialidad'], ':titulo' => $_POST['titulo'],
                    ':observaciones' => $_POST['observaciones'], ':activo' => $_POST['activo'],
                    ':numEmpleado' => $numEmpleado
                ]);

                $_SESSION['success_message'] = "Datos actualizados correctamente.";
                header('Location: ver_maestro.php?numEmpleado=' . urlencode($numEmpleado));
                exit();
            } catch (PDOException $e) {
                $errores[] = "Error: " . $e->getMessage();
            }
        }
    }
}

$form_data = $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $maestro;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Maestro - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary-color: #1a5330; /* verde CECyTE */
            --primary-hover: #1a5330;
            --bg-body: #f8f9fa;
        }

        body { background-color: var(--bg-body); font-family: 'Segoe UI', sans-serif; }

        /* Estilo del Header similar al anterior */
        .main-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section { display: flex; align-items: center; gap: 15px; }
        .header-logo { height: 45px; width: auto; }
        .system-title { font-size: 1.2rem; font-weight: bold; }

        .nav-menu ul { list-style: none; display: flex; gap: 20px; margin: 0; padding: 0; }
        .nav-menu a { color: white; text-decoration: none; font-size: 0.95rem; transition: opacity 0.3s; }
        .nav-menu a:hover { opacity: 0.7; }

        /* Tarjeta de Formulario */
        .form-container {
            background: white; border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow: hidden; border: 1px solid #dee2e6;
        }

        .form-header {
            background-color: #f1f1f1;
            padding: 20px 30px;
            border-bottom: 1px solid #dee2e6;
            color: var(--primary-color);
        }

        .section-title {
            color: var(--primary-color);
            font-weight: 700;
            border-left: 4px solid var(--primary-color);
            padding-left: 12px;
            margin: 30px 0 20px 0;
            font-size: 1rem;
            text-transform: uppercase;
        }

        .btn-cecyte {
            background-color: var(--primary-color); color: white;
            padding: 10px 25px; border-radius: 6px; font-weight: 600;
            border: none; transition: 0.3s;
        }

        .btn-cecyte:hover { background-color: var(--primary-hover); color: white; }
    </style>
</head>
<body>

<header class="main-header">
    <div class="container header-container">
        <div class="logo-section">
            <img src="img/logo-cecyte.png" alt="Logo" class="header-logo">
            <span class="system-title">CECyTE Santa Catarina</span>
        </div>
        <nav class="nav-menu">
            <ul>
                <li><a href="dashboard.php">Panel</a></li>
                <li><a href="gestion_maestros.php">Personal</a></li>
                <li><a href="logout.php" style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 4px;">Salir</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container mb-5">
    <div class="form-container">
        <div class="form-header">
            <h4 class="mb-0"><i class='bx bx-user-check'></i> Editar Perfil: <?php echo htmlspecialchars($maestro['nombre']); ?></h4>
        </div>
        
        <div class="p-4">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <h5 class="section-title">Datos de Identidad</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nombre(s)</label>
                        <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($form_data['nombre']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Apellido Paterno</label>
                        <input type="text" class="form-control" name="apellido_paterno" value="<?php echo htmlspecialchars($form_data['apellido_paterno']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Apellido Materno</label>
                        <input type="text" class="form-control" name="apellido_materno" value="<?php echo htmlspecialchars($form_data['apellido_materno']); ?>">
                    </div>
                </div>

                <h5 class="section-title">Información de Contacto</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Correo Institucional</label>
                        <input type="email" class="form-control" name="correo_institucional" value="<?php echo htmlspecialchars($form_data['correo_institucional']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Estatus</label>
                        <select class="form-select" name="activo">
                            <option value="Activo" <?php echo ($form_data['activo'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                            <option value="Inactivo" <?php echo ($form_data['activo'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center">
                    <a href="gestion_maestros.php" class="text-muted text-decoration-none"><i class='bx bx-arrow-back'></i> Volver al listado</a>
                    <button type="submit" class="btn btn-cecyte">
                        <i class='bx bx-save'></i> Actualizar Expediente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>