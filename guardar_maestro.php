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
$apellidoPaterno = $_POST['apellidoPaterno'];
$apellidoMaterno = $_POST['apellidoMaterno'];
$nombre = $_POST['nombre'];
$fechaNacimiento = $_POST['fechaNacimiento'];
$id_genero = $_POST['id_genero'];
$rfc = $_POST['rfc'];
$curp = $_POST['curp'];
$id_nacionalidad = $_POST['id_nacionalidad'];
$id_estadoNacimiento = $_POST['id_estadoNacimiento'];
$direccion = $_POST['direccion'];
$numCelular = $_POST['numCelular'];
$telefonoEmergencia = $_POST['telefonoEmergencia'];
$mailInstitucional = $_POST['mailInstitucional'];
$mailPersonal = $_POST['mailPersonal'];

// Insertar datos en la tabla maestros
$sql = "INSERT INTO maestros (numEmpleado, apellidoPaterno, apellidoMaterno, nombre, fechaNacimiento, id_genero, rfc, curp, id_nacionalidad, id_estadoNacimiento, direccion, numCelular, telefonoEmergencia, mailInstitucional, mailPersonal)
VALUES ('$numEmpleado', '$apellidoPaterno', '$apellidoMaterno', '$nombre', '$fechaNacimiento', '$id_genero', '$rfc', '$curp', '$id_nacionalidad', '$id_estadoNacimiento', '$direccion', '$numCelular', '$telefonoEmergencia', '$mailInstitucional', '$mailPersonal')";

if ($conn->query($sql) === TRUE) {
    echo "Maestro registrado correctamente.";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>