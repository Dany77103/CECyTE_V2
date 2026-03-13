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
$numEmpleado = $_POST['numEmpleado'];
$id_gradoEstudio = $_POST['id_gradoEstudio'];
$especialidad = $_POST['especialidad'];
$numCedulaProfesional = $_POST['numCedulaProfesional'];
$certificacionesoCursos = $_POST['certificacionesoCursos'];
$experienciaDocente = $_POST['experienciaDocente'];

// Insertar datos en la tabla datosAcademicosMaestros
$sql = "INSERT INTO datosAcademicosMaestros (numEmpleado, id_gradoEstudio, especialidad, numCedulaProfesional, certificacionesoCursos, experienciaDocente)
VALUES ('$numEmpleado', '$id_gradoEstudio', '$especialidad', '$numCedulaProfesional', '$certificacionesoCursos', '$experienciaDocente')";

if ($conn->query($sql) === TRUE) {
    echo "Datos academicos registrados correctamente.";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>