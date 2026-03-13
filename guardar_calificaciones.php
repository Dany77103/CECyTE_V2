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
$id_materia = $_POST['id_materia'];
$id_grupo = $_POST['id_grupo'];
$dia = $_POST['dia'];
$hora_inicio = $_POST['hora_inicio'];
$hora_fin = $_POST['hora_fin'];

// Insertar datos en la tabla horarios
$sql = "INSERT INTO horarios (id_materia, id_grupo, dia, hora_inicio, hora_fin)
VALUES ('$id_materia', '$id_grupo', '$dia', '$hora_inicio', '$hora_fin')";

if ($conn->query($sql) === TRUE) {
    echo "Horario registrado correctamente.";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?> 