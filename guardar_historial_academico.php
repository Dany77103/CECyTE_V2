<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cecyte_sc";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Recuperar datos del formulario
$matriculaAlumno = $_POST['matriculaAlumno'];
$id_semestre = $_POST['id_semestre'];
$id_grupo = $_POST['id_grupo'];
$cicloEscolar = $_POST['cicloEscolar'];
$id_estatus = $_POST['id_estatus'];
//$id_asistencia = $_POST['id_asistencia'];
$observaciones = $_POST['observaciones'];

// Insertar datos en la tabla historialAcademicoAlumnos
$sql = "INSERT INTO historialAcademicoAlumnos (matriculaAlumno, id_semestre, id_grupo, cicloEscolar, id_estatus,  observaciones)
VALUES ('$matriculaAlumno', '$id_semestre', '$id_grupo', '$cicloEscolar', '$id_estatus', '$observaciones')";

if ($conn->query($sql) === TRUE) {
    echo "Historial académico registrado correctamente.";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>