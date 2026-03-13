<?php
// eliminar_foto.php
session_start();
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['matricula'])) {
    $_SESSION['error'] = "Matrícula no especificada.";
    header('Location: lista_alumnos.php');
    exit();
}

$matricula = trim($_GET['matricula']);

try {
    // Obtener ruta de la foto actual
    $sql = "SELECT rutaImagen FROM alumnos WHERE matricula = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$matricula]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($alumno && !empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])) {
        // Eliminar archivo físico
        unlink($alumno['rutaImagen']);
        
        // Actualizar base de datos
        $sql_update = "UPDATE alumnos SET rutaImagen = NULL WHERE matricula = ?";
        $stmt_update = $con->prepare($sql_update);
        $stmt_update->execute([$matricula]);
        
        $_SESSION['success'] = "Foto eliminada correctamente.";
    } else {
        $_SESSION['error'] = "No se encontró foto para eliminar.";
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al eliminar la foto: " . $e->getMessage();
}

header('Location: editar_alumnos.php?matricula=' . urlencode($matricula));
exit();
?>