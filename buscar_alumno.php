<?php
// Conexión a la base de datos
require_once 'conexion.php'; // Usar archivo común

// Validar y sanitizar entrada
$matriculaAlumno = isset($_GET['matricula']) ? trim($_GET['matricula']) : '';

if (empty($matriculaAlumno)) {
    http_response_code(400);
    echo json_encode(["error" => "No se proporcionó una matrícula"]);
    exit;
}

// Validar formato de matrícula (ejemplo)
if (!preg_match('/^[A-Z0-9]{6,10}$/', $matriculaAlumno)) {
    echo json_encode(["error" => "Formato de matrícula inválido"]);
    exit;
}

// Usar prepared statement
$query = "SELECT * FROM alumnos a ... WHERE a.matriculaAlumno = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $matriculaAlumno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $alumno = $result->fetch_assoc();
    // Limpiar datos sensibles si es necesario
    unset($alumno['password']); // si existe
    echo json_encode($alumno);
} else {
    echo json_encode(["error" => "No se encontró ningún alumno"]);
}

$stmt->close();
$conn->close();
?>