<?php
// exportar_csv.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

$numEmpleado = isset($_GET['numEmpleado']) ? trim($_GET['numEmpleado']) : null;

try {
    if ($numEmpleado) {
        $sql = "SELECT * FROM maestros WHERE numEmpleado = :numEmpleado";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
        $stmt->execute();
        $maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filename = "maestro_" . $numEmpleado . "_" . date('Y-m-d') . ".csv";
    } else {
        $sql = "SELECT * FROM maestros ORDER BY apellidoPaterno, apellidoMaterno, nombre";
        $stmt = $con->query($sql);
        $maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filename = "maestros_" . date('Y-m-d') . ".csv";
    }
    
    if (empty($maestros)) {
        $_SESSION['error'] = "No hay datos para exportar";
        header('Location: ' . ($numEmpleado ? 'editar_maestro.php?numEmpleado=' . $numEmpleado : 'lista_maestros.php'));
        exit();
    }
    
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

// Crear el archivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Agregar BOM para UTF-8 (opcional, ayuda con Excel)
fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Escribir encabezados
if (!empty($maestros)) {
    fputcsv($output, array_keys($maestros[0]));
}

// Escribir datos
foreach ($maestros as $maestro) {
    fputcsv($output, $maestro);
}

fclose($output);
exit;