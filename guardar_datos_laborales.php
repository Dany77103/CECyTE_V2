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
$fechaContratacion = $_POST['fechaContratacion'];
$tipoContrato = $_POST['tipoContrato'];
$id_puesto = $_POST['id_puesto'];
$area = $_POST['area'];
$horarioLaboral = $_POST['horarioLaboral'];
$id_estatus = $_POST['id_estatus'];
$horarioClases = $_POST['horarioClases'];
$actividadesExtracurriculares = $_POST['actividadesExtracurriculares'];
$observaciones = $_POST['observaciones'];

// Insertar datos en la tabla datosLaboralesMaestros
$sql = "INSERT INTO datosLaboralesMaestros (numEmpleado, fechaContratacion, tipoContrato, id_puesto, area, horarioLaboral, id_estatus, horarioClases, actividadesExtracurriculares, observaciones)
VALUES ('$numEmpleado', '$fechaContratacion', '$tipoContrato', '$id_puesto', '$area', '$horarioLaboral', '$id_estatus', '$horarioClases', '$actividadesExtracurriculares', '$observaciones')";

if ($conn->query($sql) === TRUE) {
    echo "Datos laborales registrados correctamente.";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>