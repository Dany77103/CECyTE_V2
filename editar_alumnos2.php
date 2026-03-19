<?php
// editar_alumnos.php
$debug = false; // Cambia a false en producci�n
session_start();

require_once 'conexion.php';

// Mostrar errores en el navegador (solo para desarrollo)
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Determinar si es edici�n o nuevo alumno
$matricula = isset($_GET['matricula']) ? trim($_GET['matricula']) : null;
$modoEdicion = $matricula !== null;

// Obtener datos del alumno si estamos editando
$alumno = null;
if ($modoEdicion) {
    try {
        $sql = "SELECT a.*, 
                   c.nombre AS carrera_nombre, 
                   g.nombre AS grupo_nombre,
                   e.tipoEstatus AS estatus_nombre,
                   d.tipo_discapacidad AS discapacidad_nombre,
                   gen.genero AS genero_nombre,
                   nac.nacionalidad AS nacionalidad_nombre,
                   es.estado AS estado_nombre,
                   s.semestre AS semestre_nombre,
                   cd.ciudad AS ciudad
            FROM alumnos a
            LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
            LEFT JOIN grupos g ON a.id_grupo = g.id_grupo
            LEFT JOIN estatus e ON a.id_estatus = e.id_estatus
            LEFT JOIN discapacidades d ON a.id_discapacidad = d.id_discapacidad
            LEFT JOIN generos gen ON a.id_genero = gen.id_genero
            LEFT JOIN nacionalidades nac ON a.id_nacionalidad = nac.id_nacionalidad
            LEFT JOIN estados es ON a.id_estado = es.id_estado
            LEFT JOIN ciudades cd ON a.id_ciudad = cd.ciudad
            LEFT JOIN semestres s ON a.id_semestre = s.id_semestre
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
}

// Obtener datos para formularios
try {
    $sql_carreras = "SELECT * FROM carreras ORDER BY nombre";
    $carreras = $con->query($sql_carreras)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_grupos = "SELECT * FROM grupos ORDER BY nombre";
    $grupos = $con->query($sql_grupos)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_estatus = "SELECT * FROM estatus ORDER BY tipoEstatus";
    $estatus = $con->query($sql_estatus)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_generos = "SELECT * FROM generos ORDER BY genero";
    $generos = $con->query($sql_generos)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_nacionalidades = "SELECT * FROM nacionalidades ORDER BY nacionalidad";
    $nacionalidades = $con->query($sql_nacionalidades)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_estados = "SELECT * FROM estados ORDER BY estado";
    $estados = $con->query($sql_estados)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_ciudades = "SELECT * FROM ciudades ORDER BY ciudad";
    $ciudades = $con->query($sql_ciudades)->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_discapacidades = "SELECT * FROM discapacidades ORDER BY tipo_discapacidad";
    $discapacidades = $con->query($sql_discapacidades)->fetchAll(PDO::FETCH_ASSOC);
    
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
        $errores[] = "Token de seguridad inv�lido. Por favor, recarga la p�gina.";
    } else {
        // Recoger datos del formulario
        $datos = [
            'matricula' => trim($_POST['matricula'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido_paterno' => trim($_POST['apellido_paterno'] ?? ''),
            'apellido_materno' => trim($_POST['apellido_materno'] ?? ''),
            'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? ''),
            'curp' => trim($_POST['curp'] ?? ''),
            'rfc' => trim($_POST['rfc'] ?? ''),
            'id_genero' => trim($_POST['id_genero'] ?? ''),
            'estado_civil' => trim($_POST['estado_civil'] ?? ''),
            'id_nacionalidad' => trim($_POST['id_nacionalidad'] ?? ''),
            'lugar_nacimiento' => trim($_POST['lugar_nacimiento'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'colonia' => trim($_POST['colonia'] ?? ''),
            'codigo_postal' => trim($_POST['codigo_postal'] ?? ''),
            'id_ciudad' => trim($_POST['id_ciudad'] ?? ''),
            'id_estado' => trim($_POST['id_estado'] ?? ''),
            'telefono_casa' => trim($_POST['telefono_casa'] ?? ''),
            'telefono_celular' => trim($_POST['telefono_celular'] ?? ''),
            'correo_personal' => trim($_POST['correo_personal'] ?? ''),
            'correo_institucional' => trim($_POST['correo_institucional'] ?? ''),
            'correo_tutor' => trim($_POST['correo_tutor'] ?? ''),
            'fecha_ingreso' => trim($_POST['fecha_ingreso'] ?? ''),
            'id_carrera' => $_POST['id_carrera'] ?? '',
            'id_semestre' => $_POST['id_semestre'] ?? '',
            'id_grupo' => $_POST['id_grupo'] ?? '',
            'turno' => trim($_POST['turno'] ?? ''),
            'id_estatus' => $_POST['id_estatus'] ?? '',
            'observaciones' => trim($_POST['observaciones'] ?? ''),
            'tipo_sangre' => trim($_POST['tipo_sangre'] ?? ''),
            'alergias' => trim($_POST['alergias'] ?? ''),
            'enfermedades_cronicas' => trim($_POST['enfermedades_cronicas'] ?? ''),
            'seguro_medico' => trim($_POST['seguro_medico'] ?? ''),
            'escuela_procedencia' => trim($_POST['escuela_procedencia'] ?? ''),
            'promedio_secundaria' => trim($_POST['promedio_secundaria'] ?? ''),
            'beca' => trim($_POST['beca'] ?? ''),
            'porcentaje_beca' => trim($_POST['porcentaje_beca'] ?? ''),
            'id_discapacidad' => $_POST['id_discapacidad'] ?? '',
            'telefono_emergencia' => trim($_POST['telefono_emergencia'] ?? ''),
            'nombre_padre' => trim($_POST['nombre_padre'] ?? ''),
            'ocupacion_padre' => trim($_POST['ocupacion_padre'] ?? ''),
            'telefono_padre' => trim($_POST['telefono_padre'] ?? ''),
            'nombre_madre' => trim($_POST['nombre_madre'] ?? ''),
            'ocupacion_madre' => trim($_POST['ocupacion_madre'] ?? ''),
            'telefono_madre' => trim($_POST['telefono_madre'] ?? '')
        ];
        
        // Validaciones b�sicas
        $camposRequeridos = ['matricula', 'nombre', 'apellido_paterno', 'fecha_nacimiento', 'curp', 'id_genero', 'fecha_ingreso', 'id_semestre'];
        foreach ($camposRequeridos as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = ucfirst(str_replace('_', ' ', $campo)) . " es requerido.";
            }
        }
        
        // Validar CURP
        if (!empty($datos['curp']) && !preg_match('/^[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z]{2}$/', $datos['curp'])) {
            $errores[] = "El CURP no tiene un formato v�lido.";
        }
        
        // Validar RFC
        if (!empty($datos['rfc']) && !preg_match('/^[A-Z&N]{3,4}[0-9]{6}[A-Z0-9]{3}$/', $datos['rfc'])) {
            $errores[] = "El RFC no tiene un formato v�lido.";
        }
        
        // Validar correos
        if (!empty($datos['correo_personal']) && !filter_var($datos['correo_personal'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo personal no es v�lido.";
        }
        
        if (!empty($datos['correo_institucional']) && !filter_var($datos['correo_institucional'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo institucional no es v�lido.";
        }
        
        // Validar fechas
        if (!empty($datos['fecha_nacimiento'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $datos['fecha_nacimiento']);
            if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha_nacimiento']) {
                $errores[] = "La fecha de nacimiento no es v�lida.";
            }
        }
        
        if (!empty($datos['fecha_ingreso'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $datos['fecha_ingreso']);
            if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha_ingreso']) {
                $errores[] = "La fecha de ingreso no es v�lida.";
            }
        }
        
        // Validaciones num�ricas
        if (!empty($datos['codigo_postal']) && !is_numeric($datos['codigo_postal'])) {
            $errores[] = "El c�digo postal debe ser un n�mero.";
        }
        
        if (!empty($datos['promedio_secundaria']) && !is_numeric($datos['promedio_secundaria'])) {
            $errores[] = "El promedio de secundaria debe ser un n�mero.";
        }
        
        if (!empty($datos['porcentaje_beca']) && !is_numeric($datos['porcentaje_beca'])) {
            $errores[] = "El porcentaje de beca debe ser un n�mero.";
        }
        
        // Verificar matr�cula �nica (solo para nuevo alumno)
        if (!$modoEdicion) {
            try {
                $sql_check = "SELECT COUNT(*) as count FROM alumnos WHERE matricula = :matricula";
                $stmt_check = $con->prepare($sql_check);
                $stmt_check->bindParam(':matricula', $datos['matricula'], PDO::PARAM_STR);
                $stmt_check->execute();
                $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    $errores[] = "La matr�cula ya existe. Por favor, use otra.";
                }
            } catch (PDOException $e) {
                $errores[] = "Error al verificar la matr�cula: " . $e->getMessage();
            }
        }
        
        if (empty($errores)) {
            try {
                if ($modoEdicion) {
                    // Actualizar alumno existente
                    $sql = "UPDATE alumnos SET
                            nombre = :nombre,
                            apellido_paterno = :apellido_paterno,
                            apellido_materno = :apellido_materno,
                            fecha_nacimiento = :fecha_nacimiento,
                            curp = :curp,
                            rfc = :rfc,
                            id_genero = :id_genero,
                            estado_civil = :estado_civil,
                            id_nacionalidad = :id_nacionalidad,
                            lugar_nacimiento = :lugar_nacimiento,
                            direccion = :direccion,
                            colonia = :colonia,
                            codigo_postal = :codigo_postal,
                            id_ciudad = :id_ciudad,
                            id_estado = :id_estado,
                            telefono_casa = :telefono_casa,
                            telefono_celular = :telefono_celular,
                            correo_personal = :correo_personal,
                            correo_institucional = :correo_institucional,
                            correo_tutor = :correo_tutor,
                            fecha_ingreso = :fecha_ingreso,
                            id_carrera = :id_carrera,
                            id_semestre = :id_semestre,
                            id_grupo = :id_grupo,
                            turno = :turno,
                            id_estatus = :id_estatus,
                            observaciones = :observaciones,
                            tipo_sangre = :tipo_sangre,
                            alergias = :alergias,
                            enfermedades_cronicas = :enfermedades_cronicas,
                            seguro_medico = :seguro_medico,
                            escuela_procedencia = :escuela_procedencia,
                            promedio_secundaria = :promedio_secundaria,
                            beca = :beca,
                            porcentaje_beca = :porcentaje_beca,
                            id_discapacidad = :id_discapacidad,
                            telefono_emergencia = :telefono_emergencia,
                            nombre_padre = :nombre_padre,
                            ocupacion_padre = :ocupacion_padre,
                            telefono_padre = :telefono_padre,
                            nombre_madre = :nombre_madre,
                            ocupacion_madre = :ocupacion_madre,
                            telefono_madre = :telefono_madre,
                            updated_at = NOW()
                            WHERE matricula = :matricula";
                    
                    $stmt = $con->prepare($sql);
                    
                    // Vincular todos los par�metros
                    foreach ($datos as $key => $value) {
                        if ($key == 'matricula') continue;
                        
                        // Determinar el tipo de par�metro
                        $paramType = PDO::PARAM_STR;
                        if (in_array($key, ['id_carrera', 'id_semestre', 'id_grupo', 'id_estatus', 'id_genero', 
                                             'id_nacionalidad', 'id_estado', 'id_ciudad', 'id_discapacidad', 
                                             'codigo_postal', 'promedio_secundaria', 'porcentaje_beca',
                                             'telefono_casa', 'telefono_celular', 'telefono_emergencia',
                                             'telefono_padre', 'telefono_madre'])) {
                            if (is_numeric($value) && $value != '') {
                                $paramType = PDO::PARAM_INT;
                            }
                        }
                        
                        $stmt->bindValue(':' . $key, $value, $paramType);
                    }
                    
                    // Vincular la matr�cula para el WHERE
                    $stmt->bindValue(':matricula', $matricula, PDO::PARAM_STR);
                    
                    // Ejecutar la consulta
                    $result = $stmt->execute();
                    
                    if ($result) {
                        $rowCount = $stmt->rowCount();
                        if ($rowCount > 0) {
                            $mensajes[] = "Alumno actualizado correctamente.";
                            
                            // Actualizar variable $alumno con los nuevos datos
                            $sql_select = "SELECT a.*, c.nombre as carrera_nombre, g.nombre as grupo_nombre,
                                           s.semestre as semestre_nombre
                                           FROM alumnos a
                                           LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
                                           LEFT JOIN grupos g ON a.id_grupo = g.id_grupo
                                           LEFT JOIN semestres s ON a.id_semestre = s.id_semestre
                                           WHERE a.matricula = :matricula";
                            
                            $stmt_select = $con->prepare($sql_select);
                            $stmt_select->bindParam(':matricula', $matricula, PDO::PARAM_STR);
                            $stmt_select->execute();
                            $alumno = $stmt_select->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$alumno) {
                                $errores[] = "Error al recuperar datos actualizados del alumno.";
                            }
                        } else {
                            $errores[] = "La actualizaci�n no afect� a ninguna fila. Verifique que los datos sean diferentes o que la matr�cula exista.";
                            
                            // Verificar si el alumno existe
                            $sql_check = "SELECT COUNT(*) as count FROM alumnos WHERE matricula = :matricula";
                            $stmt_check = $con->prepare($sql_check);
                            $stmt_check->bindParam(':matricula', $matricula, PDO::PARAM_STR);
                            $stmt_check->execute();
                            $checkResult = $stmt_check->fetch(PDO::FETCH_ASSOC);
                            
                            if ($checkResult['count'] == 0) {
                                $errores[] = "El alumno con matr�cula $matricula no existe en la base de datos.";
                            } else {
                                $errores[] = "El alumno existe pero no hubo cambios en los datos.";
                            }
                        }
                    } else {
                        $errorInfo = $stmt->errorInfo();
                        $errores[] = "Error al ejecutar la consulta UPDATE: " . $errorInfo[2];
                    }
                } else {
                    // Insertar nuevo alumno
                    $sql = "INSERT INTO alumnos (
                            matricula, nombre, apellido_paterno, apellido_materno, fecha_nacimiento,
                            curp, rfc, id_genero, estado_civil, id_nacionalidad, lugar_nacimiento,
                            direccion, colonia, codigo_postal, id_ciudad, id_estado,
                            telefono_casa, telefono_celular, correo_personal, correo_institucional,
                            fecha_ingreso, id_carrera, id_semestre, id_grupo, turno, id_estatus,
                            observaciones, tipo_sangre, alergias, enfermedades_cronicas,
                            seguro_medico, escuela_procedencia, promedio_secundaria, beca, porcentaje_beca,
                            id_discapacidad, telefono_emergencia, nombre_padre, ocupacion_padre, telefono_padre,
                            nombre_madre, ocupacion_madre, telefono_madre, created_at, updated_at
                        ) VALUES (
                            :matricula, :nombre, :apellido_paterno, :apellido_materno, :fecha_nacimiento,
                            :curp, :rfc, :id_genero, :estado_civil, :id_nacionalidad, :lugar_nacimiento,
                            :direccion, :colonia, :codigo_postal, :id_ciudad, :id_estado,
                            :telefono_casa, :telefono_celular, :correo_personal, :correo_institucional, :correo_tutor,
                            :fecha_ingreso, :id_carrera, :id_semestre, :id_grupo, :turno, :id_estatus,
                            :observaciones, :tipo_sangre, :alergias, :enfermedades_cronicas,
                            :seguro_medico, :escuela_procedencia, :promedio_secundaria, :beca, :porcentaje_beca,
                            :id_discapacidad, :telefono_emergencia, :nombre_padre, :ocupacion_padre, :telefono_padre,
                            :nombre_madre, :ocupacion_madre, :telefono_madre, NOW(), NOW()
                        )";
                    
                    $stmt = $con->prepare($sql);
                    foreach ($datos as $key => $value) {
                        $stmt->bindValue(':' . $key, $value);
                    }
                    
                    if ($stmt->execute()) {
                        $matricula = $datos['matricula']; // Para redirigir o mostrar mensaje
                        $mensajes[] = "Alumno registrado correctamente. Matr�cula: " . $datos['matricula'];
                        
                        // Obtener datos del nuevo alumno
                        $stmt = $con->prepare("SELECT a.*, c.nombre as carrera_nombre, g.nombre as grupo_nombre 
                                               FROM alumnos a
                                               LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
                                               LEFT JOIN grupos g ON a.id_grupo = g.id_grupo
                                               WHERE a.matricula = :matricula");
                        $stmt->bindParam(':matricula', $datos['matricula'], PDO::PARAM_STR);
                        $stmt->execute();
                        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
                        $modoEdicion = true;
                    } else {
                        $errores[] = "Error al registrar el alumno.";
                    }
                }
            } catch (PDOException $e) {
                $errorMessage = "Error de base de datos: " . $e->getMessage();
                $errores[] = $errorMessage;
                if ($debug) {
                    error_log($errorMessage);
                }
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
    <title><?php echo $modoEdicion ? 'Editar Alumno' : 'Nuevo Alumno'; ?> - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --verde-oscuro: #1a5330;
            --verde-principal: #2e7d32;
            --verde-medio: #4caf50;
            --verde-claro: #8bc34a;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
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
        
        .form-label {
            font-weight: 600;
            color: var(--verde-oscuro);
        }
        
        .required::after {
            content: " *";
            color: red;
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
        
        .btn-cecyte-secondary {
            background: linear-gradient(135deg, #6c757d, #8e9ba4);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-cecyte-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #6c757d);
            color: white;
            transform: translateY(-2px);
        }
        
        .section-title {
            background-color: #e3f2fd;
            padding: 10px 15px;
            border-left: 4px solid var(--verde-medio);
            margin: 20px 0;
            border-radius: 5px;
            color: var(--verde-oscuro);
            font-weight: 600;
        }
        
        .tab-content {
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        
        .nav-tabs {
            border-bottom: 2px solid var(--verde-medio);
        }
        
        .nav-tabs .nav-link {
            color: var(--verde-oscuro);
            font-weight: 600;
            border: 1px solid transparent;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            margin-bottom: -2px;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--verde-principal);
            border-color: #e9ecef #e9ecef #dee2e6;
        }
        
        .nav-tabs .nav-link.active {
            background-color: #e8f5e9;
            border-color: var(--verde-medio) var(--verde-medio) #e8f5e9;
            color: var(--verde-oscuro);
            border-bottom-color: transparent;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--verde-medio);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            border-left: 5px solid var(--verde-medio);
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .info-section {
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--verde-medio);
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
        
        @media (max-width: 768px) {
            .student-card-header, .student-card-body {
                padding: 15px;
            }
            
            .btn-cecyte, .btn-cecyte-secondary {
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
                    <h2><i class='bx <?php echo $modoEdicion ? 'bx-edit' : 'bx-user-plus'; ?>'></i> 
                        <?php echo $modoEdicion ? 'Editar Alumno' : 'Nuevo Alumno'; ?>
                    </h2>
                    <p class="mb-0">Sistema de Gesti&oacute;n Escolar - CECyTE</p>
                </div>
                <div>
                    <a href="gestion_alumnos.php" class="btn btn-light">
                        <i class='bx bx-arrow-back'></i> Volver a la Lista de Alumnos
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="student-card">
            <div class="student-card-body">
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
                
                <form method="POST" action="" id="alumnoForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Pestanas -->
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                                <i class='bx bx-user'></i> Datos Personales
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="academicos-tab" data-bs-toggle="tab" data-bs-target="#academicos" type="button" role="tab">
                                <i class='bx bx-book'></i> Datos Acad&eacute;micos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contacto-tab" data-bs-toggle="tab" data-bs-target="#contacto" type="button" role="tab">
                                <i class='bx bx-phone'></i> Contacto y Emergencias
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="myTabContent">
                        <!-- Pestana 1: Datos Personales -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Matr&iacute;cula</label>
                                        <input type="text" class="form-control" name="matricula" required
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['matricula']) : ''; ?>"
                                               <?php echo $modoEdicion ? 'readonly' : ''; ?>>
                                        <small class="text-muted">N&uacute;mero de matr&iacute;cula &uacute;nico del alumno</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">Nombre(s)</label>
                                        <input type="text" class="form-control" name="nombre" required
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['nombre']) : ''; ?>">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label required">Apellido Paterno</label>
                                                <input type="text" class="form-control" name="apellido_paterno" required
                                                       value="<?php echo $alumno ? htmlspecialchars($alumno['apellido_paterno']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Apellido Materno</label>
                                                <input type="text" class="form-control" name="apellido_materno"
                                                       value="<?php echo $alumno ? htmlspecialchars($alumno['apellido_materno']) : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">Fecha de Nacimiento</label>
                                        <input type="date" class="form-control" name="fecha_nacimiento" required
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['fecha_nacimiento']) : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">CURP</label>
                                        <input type="text" class="form-control" name="curp" required maxlength="18"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['curp']) : ''; ?>"
                                               pattern="[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z]{2}"
                                               title="Formato de CURP v�lido: 4 letras, 6 n�meros, 6 letras, 2 caracteres">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">RFC</label>
                                        <input type="text" class="form-control" name="rfc" maxlength="13"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['rfc']) : ''; ?>"
                                               pattern="[A-Z&N]{3,4}[0-9]{6}[A-Z0-9]{3}"
                                               title="Formato de RFC v�lido">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Lugar de Nacimiento</label>
                                        <input type="text" class="form-control" name="lugar_nacimiento"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['lugar_nacimiento'] ?? '') : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Genero</label>
                                        <select class="form-select" name="id_genero" required>
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($generos as $gen): ?>
                                                <option value="<?php echo $gen['id_genero']; ?>"
                                                    <?php echo ($alumno && $alumno['id_genero'] == $gen['id_genero']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($gen['genero']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Estado Civil</label>
                                        <select class="form-select" name="estado_civil">
                                            <option value="">Seleccionar...</option>
                                            <option value="Soltero" <?php echo ($alumno && $alumno['estado_civil'] == 'Soltero') ? 'selected' : ''; ?>>Soltero</option>
                                            <option value="Casado" <?php echo ($alumno && $alumno['estado_civil'] == 'Casado') ? 'selected' : ''; ?>>Casado</option>
                                            <option value="Divorciado" <?php echo ($alumno && $alumno['estado_civil'] == 'Divorciado') ? 'selected' : ''; ?>>Divorciado</option>
                                            <option value="Viudo" <?php echo ($alumno && $alumno['estado_civil'] == 'Viudo') ? 'selected' : ''; ?>>Viudo</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tipo de Sangre</label>
                                        <input type="text" class="form-control" name="tipo_sangre"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['tipo_sangre'] ?? '') : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Discapacidad</label>
                                        <select class="form-select" name="id_discapacidad">
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($discapacidades as $disc): ?>
                                                <option value="<?php echo $disc['id_discapacidad']; ?>"
                                                    <?php echo ($alumno && $alumno['id_discapacidad'] == $disc['id_discapacidad']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($disc['tipo_discapacidad']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nacionalidad</label>
                                        <select class="form-select" name="id_nacionalidad">
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($nacionalidades as $nac): ?>
                                                <option value="<?php echo $nac['id_nacionalidad']; ?>"
                                                    <?php echo ($alumno && $alumno['id_nacionalidad'] == $nac['id_nacionalidad']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($nac['nacionalidad']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Direcci&oacute;n</label>
                                        <textarea class="form-control" name="direccion" rows="2"><?php echo $alumno ? htmlspecialchars($alumno['direccion']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Colonia</label>
                                                <input type="text" class="form-control" name="colonia"
                                                       value="<?php echo $alumno ? htmlspecialchars($alumno['colonia']) : ''; ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">C&oacute;digo Postal</label>
                                                <input type="text" class="form-control" name="codigo_postal"
                                                       value="<?php echo $alumno ? htmlspecialchars($alumno['codigo_postal'] ?? '') : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Ciudad</label>
                                                <select class="form-select" name="id_ciudad">
                                                    <option value="">Seleccionar...</option>
                                                    <?php foreach ($ciudades as $cd): ?>
                                                        <option value="<?php echo $cd['id_ciudad']; ?>"
                                                            <?php echo ($alumno && $alumno['id_ciudad'] == $cd['id_ciudad']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($cd['ciudad']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Estado</label>
                                                <select class="form-select" name="id_estado">
                                                    <option value="">Seleccionar...</option>
                                                    <?php foreach ($estados as $est): ?>
                                                        <option value="<?php echo $est['id_estado']; ?>"
                                                            <?php echo ($alumno && $alumno['id_estado'] == $est['id_estado']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($est['estado']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestana 2: Datos Acad�micos -->
                        <div class="tab-pane fade" id="academicos" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Fecha de Ingreso</label>
                                        <input type="date" class="form-control" name="fecha_ingreso" required
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['fecha_ingreso']) : date('Y-m-d'); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">Carrera</label>
                                        <select class="form-select" name="id_carrera" required>
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($carreras as $carrera): ?>
                                                <option value="<?php echo $carrera['id_carrera']; ?>"
                                                    <?php echo ($alumno && $alumno['id_carrera'] == $carrera['id_carrera']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($carrera['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">Semestre</label>
                                        <select class="form-select" name="id_semestre" required>
                                            <option value="">Seleccionar...</option>
                                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                                <option value="<?php echo $i; ?>"
                                                    <?php echo ($alumno && $alumno['id_semestre'] == $i) ? 'selected' : ''; ?>>
                                                    <?php echo $i; ?>o Semestre
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Grupo</label>
                                        <select class="form-select" name="id_grupo">
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($grupos as $grupo): ?>
                                                <option value="<?php echo $grupo['id_grupo']; ?>"
                                                    <?php echo ($alumno && $alumno['id_grupo'] == $grupo['id_grupo']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($grupo['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Turno</label>
                                        <select class="form-select" name="turno">
                                            <option value="">Seleccionar...</option>
                                            <option value="Matutino" <?php echo ($alumno && $alumno['turno'] == 'Matutino') ? 'selected' : ''; ?>>Matutino</option>
                                            <option value="Vespertino" <?php echo ($alumno && $alumno['turno'] == 'Vespertino') ? 'selected' : ''; ?>>Vespertino</option>
                                            <option value="Nocturno" <?php echo ($alumno && $alumno['turno'] == 'Nocturno') ? 'selected' : ''; ?>>Nocturno</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Estatus</label>
                                        <select class="form-select" name="id_estatus" required>
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($estatus as $est): ?>
                                                <option value="<?php echo $est['id_estatus']; ?>"
                                                    <?php echo ($alumno && $alumno['id_estatus'] == $est['id_estatus']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($est['tipoEstatus']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Correo Institucional</label>
                                        <input type="email" class="form-control" name="correo_institucional"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['correo_institucional']) : ''; ?>"
                                               placeholder="alumno@cecytenl.edu.mx">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Observaciones</label>
                                        <textarea class="form-control" name="observaciones" rows="4"><?php echo $alumno ? htmlspecialchars($alumno['observaciones']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="section-title">Informaci&oacute;n de Salud</div>
                                    <div class="mb-3">
                                        <label class="form-label">Alergias</label>
                                        <textarea class="form-control" name="alergias" rows="2"><?php echo $alumno ? htmlspecialchars($alumno['alergias'] ?? '') : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Enfermedades Cr&oacute;nicas</label>
                                        <textarea class="form-control" name="enfermedades_cronicas" rows="2"><?php echo $alumno ? htmlspecialchars($alumno['enfermedades_cronicas'] ?? '') : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Seguro M&eacute;dico</label>
                                        <input type="text" class="form-control" name="seguro_medico"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['seguro_medico'] ?? '') : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="section-title">Procedencia</div>
                                    <div class="mb-3">
                                        <label class="form-label">Escuela de Procedencia</label>
                                        <input type="text" class="form-control" name="escuela_procedencia"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['escuela_procedencia'] ?? '') : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Promedio Secundaria</label>
                                        <input type="number" step="0.01" class="form-control" name="promedio_secundaria"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['promedio_secundaria'] ?? '') : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="section-title">Beca</div>
                                    <div class="mb-3">
                                        <label class="form-label">Beca</label>
                                        <select class="form-select" name="beca">
                                            <option value="">Seleccionar...</option>
                                            <option value="SI" <?php echo ($alumno && $alumno['beca'] == 'SI') ? 'selected' : ''; ?>>S&iacute;</option>
                                            <option value="NO" <?php echo ($alumno && $alumno['beca'] == 'NO') ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Porcentaje de Beca</label>
                                        <input type="number" class="form-control" name="porcentaje_beca" min="0" max="100"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['porcentaje_beca'] ?? '') : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestana 3: Contacto y Emergencias -->
                        <div class="tab-pane fade" id="contacto" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="section-title">Informaci&oacute;n de Contacto</div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tel&eacute;fono de Casa</label>
                                        <input type="tel" class="form-control" name="telefono_casa"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['telefono_casa']) : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tel&eacute;fono Celular</label>
                                        <input type="tel" class="form-control" name="telefono_celular"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['telefono_celular']) : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tel&eacute;fono de Emergencia</label>
                                        <input type="tel" class="form-control" name="telefono_emergencia"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['telefono_emergencia'] ?? '') : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
    <label class="form-label">Correo Personal</label>
    <input type="email" class="form-control" name="correo_personal"
           value="<?php echo $alumno ? htmlspecialchars($alumno['correo_personal']) : ''; ?>">
</div>

<div class="mb-3">
    <label class="form-label">Correo del Tutor</label>
    <input type="email" class="form-control" name="correo_tutor"
           value="<?php echo $alumno ? htmlspecialchars($alumno['correo_tutor'] ?? '') : ''; ?>"
           placeholder="ejemplo@correo.com">
</div>
                                    
                                    <div class="section-title">Informaci&oacute;n del Padre</div>
                                    <div class="mb-3">
                                        <label class="form-label">Nombre del Padre</label>
                                        <input type="text" class="form-control" name="nombre_padre"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['nombre_padre'] ?? '') : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Ocupaci&oacute;n del Padre</label>
                                        <input type="text" class="form-control" name="ocupacion_padre"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['ocupacion_padre'] ?? '') : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tel&eacute;fono del Padre</label>
                                        <input type="tel" class="form-control" name="telefono_padre"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['telefono_padre'] ?? '') : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="section-title">Informaci&oacute;n de la Madre</div>
                                    <div class="mb-3">
                                        <label class="form-label">Nombre de la Madre</label>
                                        <input type="text" class="form-control" name="nombre_madre"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['nombre_madre'] ?? '') : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Ocupaci&oacute;n de la Madre</label>
                                        <input type="text" class="form-control" name="ocupacion_madre"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['ocupacion_madre'] ?? '') : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tel&eacute;fono de la Madre</label>
                                        <input type="tel" class="form-control" name="telefono_madre"
                                               value="<?php echo $alumno ? htmlspecialchars($alumno['telefono_madre'] ?? '') : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="gestion_alumnos.php" class="btn btn-cecyte-secondary">
                            <i class='bx bx-arrow-back'></i> Volver a la Lista de Alumnos
                        </a>
                        <div>
                            <?php if ($modoEdicion && isset($alumno['matricula'])): ?>
                                <a href="ver_alumno2.php?matricula=<?php echo urlencode($alumno['matricula']); ?>" class="btn btn-cecyte me-2" style="background: linear-gradient(135deg, var(--verde-claro), var(--verde-medio));">
                                    <i class='bx bx-show'></i> Ver Alumno
                                </a>
                                <a href="qr_alumno2.php?matricula=<?php echo urlencode($alumno['matricula']); ?>" class="btn btn-cecyte me-2" style="background: linear-gradient(135deg, #ff9800, #ffb74d);">
                                    <i class='bx bx-qr'></i> Generar QR
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-cecyte">
                                <i class='bx bx-save'></i> <?php echo $modoEdicion ? 'Actualizar Alumno' : 'Guardar Alumno'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validaci�n del formulario
        document.getElementById('alumnoForm').addEventListener('submit', function(e) {
            const tabs = document.querySelectorAll('.tab-pane');
            let faltanCampos = false;
            let mensajeError = 'Por favor, complete los siguientes campos requeridos:\n\n';
            
            // Verificar campos requeridos en cada pestana activa
            tabs.forEach(tab => {
                const requiredFields = tab.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        faltanCampos = true;
                        const label = field.closest('.mb-3')?.querySelector('.form-label');
                        if (label) {
                            const fieldName = label.textContent.replace(' *', '');
                            mensajeError += `- ${fieldName}\n`;
                        }
                    }
                });
            });
            
            if (faltanCampos) {
                e.preventDefault();
                alert(mensajeError + '\nPor favor, revise todas las pestanas del formulario.');
                
                // Activar la primera pestana con campos faltantes
                const firstInvalidTab = document.querySelector('.tab-pane:has([required]:invalid)');
                if (firstInvalidTab) {
                    const tabId = firstInvalidTab.id;
                    const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                    if (tabButton) {
                        new bootstrap.Tab(tabButton).show();
                    }
                }
            }
        });

        // Auto-generar correo institucional si est� vac�o
        document.querySelector('[name="matricula"]')?.addEventListener('blur', function() {
            const correoInput = document.querySelector('[name="correo_institucional"]');
            const matricula = this.value.trim();
            
            if (matricula && (!correoInput.value || correoInput.value.includes('@'))) {
                correoInput.value = matricula.toLowerCase() + '@cecytenl.edu.mx';
            }
        });
    </script>
</body>
</html>