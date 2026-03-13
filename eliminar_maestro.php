<?php
// eliminar_maestro.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['numEmpleado'])) {
    header('Location: lista_maestros.php?error=numero_invalido');
    exit();
}

$numEmpleado = $_GET['numEmpleado'];

try {
    // Verificar que el maestro existe
    $sql_check = "SELECT * FROM maestros WHERE numEmpleado = :numEmpleado";
    $stmt_check = $con->prepare($sql_check);
    $stmt_check->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() === 0) {
        header('Location: lista_maestros.php?error=not_found');
        exit();
    }
    
    $maestro = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    // Iniciar transacción para eliminar en cascada
    $con->beginTransaction();
    
    // 1. Eliminar datos académicos del maestro
    try {
        $sql_academicos = "DELETE FROM datosacademicosmaestros WHERE numEmpleado = :numEmpleado";
        $stmt_academicos = $con->prepare($sql_academicos);
        $stmt_academicos->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
        $stmt_academicos->execute();
    } catch (PDOException $e) {
        // Si no hay datos académicos, continuar
    }
    
    // 2. Eliminar datos laborales del maestro
    try {
        $sql_laborales = "DELETE FROM datoslaboralesmaestros WHERE numEmpleado = :numEmpleado";
        $stmt_laborales = $con->prepare($sql_laborales);
        $stmt_laborales->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
        $stmt_laborales->execute();
    } catch (PDOException $e) {
        // Si no hay datos laborales, continuar
    }
    
    // 3. Eliminar el maestro
    $sql_maestro = "DELETE FROM maestros WHERE numEmpleado = :numEmpleado";
    $stmt_maestro = $con->prepare($sql_maestro);
    $stmt_maestro->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
    $stmt_maestro->execute();
    
    // Confirmar transacción
    $con->commit();
    
    // Registrar la eliminación en un log (opcional)
    $nombre_completo = $maestro['nombre'] . ' ' . $maestro['apellidoPaterno'] . ' ' . ($maestro['apellidoMaterno'] ?? '');
    error_log("Maestro eliminado: $numEmpleado - $nombre_completo - " . date('Y-m-d H:i:s'));
    
    header('Location: lista_maestros.php?success=deleted');
    
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    $con->rollBack();
    
    // Registrar error
    error_log("Error al eliminar maestro $numEmpleado: " . $e->getMessage());
    
    header('Location: lista_maestros.php?error=delete');
}
exit();