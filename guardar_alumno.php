<?php
require_once 'conexion.php';

// Función para validar datos
function validarDatosAlumno($datos) {
    $errores = [];
    
    if (empty($datos['matriculaAlumno'])) $errores[] = "Matrícula requerida";
    if (!filter_var($datos['mailInstitucional'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Email institucional inválido";
    }
    // Más validaciones...
    
    return $errores;
}

// Validar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método no permitido");
}

// Sanitizar entradas
$matriculaAlumno = htmlspecialchars(trim($_POST['matriculaAlumno']));
$mailInstitucional = filter_var($_POST['mailInstitucional'], FILTER_SANITIZE_EMAIL);
// ... sanitizar otros campos

// Validar
$errores = validarDatosAlumno($_POST);
if (!empty($errores)) {
    die(implode("<br>", $errores));
}

// Usar transacción
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO alumnos (...) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssissssssssi", 
        $matriculaAlumno,
        $apellidoPaterno,
        $apellidoMaterno,
        $nombre,
        $fechaNacimiento,
        $id_genero,
        $rfc,
        $id_nacionalidad,
        $id_estadoNacimiento,
        $direccion,
        $numCelular,
        $telefonoEmergencia,
        $mailInstitucional,
        $mailPersonal,
        $id_discapacidad
    );
    
    if ($stmt->execute()) {
        $conn->commit();
        echo "Alumno registrado correctamente.";
        // Redirigir o mostrar mensaje
    } else {
        throw new Exception("Error al registrar: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error en guardar_alumno: " . $e->getMessage());
    echo "Error al registrar el alumno. Contacte al administrador.";
}

$conn->close();
?>