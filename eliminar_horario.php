<?php
require_once 'conexion.php';

// Depuración: Verifica si el parámetro está llegando correctamente
if (isset($_GET['id_horario'])) {
    $id = $_GET['id_horario'];
    echo "Matricula recibida: " . htmlspecialchars($id) . "<br>"; // Depuración

    $sql = "DELETE FROM horarios WHERE id_horario = :id";

    try {
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "Registro eliminado correctamente.";
        } else {
            echo "No se encontró el registro con la matrícula proporcionada.";
        }
    } catch (PDOException $e) {
        echo "Error al eliminar el registro: " . $e->getMessage();
    }
} else {
    echo "ID no proporcionado.";
}

// Cerrar la conexión asignando null a la variable
$con = null;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eliminado completo</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Botón que regresa anteriormente al inicio -->
    <div class="button-container">
        <a href="reportes.php" class="btn-agregar">Regresa a la pagina anterior</a>
    </div>
</body>
</html>