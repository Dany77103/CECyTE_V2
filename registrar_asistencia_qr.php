<?php
session_start();
require_once 'config.php';

// Este endpoint es para que los alumnos escaneen el QR
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Token no proporcionado");
}

// Verificar token
$sql = "SELECT q.*, m.materia, g.nombre as grupo_nombre
        FROM qr_clase q
        JOIN materias m ON q.id_materia = m.id_materia
        JOIN grupos g ON q.id_grupo = g.id_grupo
        WHERE q.token = :token AND q.activo = 1 AND q.fecha = CURDATE()";

$stmt = $pdo->prepare($sql);
$stmt->execute(['token' => $token]);
$qr_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$qr_data) {
    die("Token inválido o expirado");
}

// Verificar si el alumno ha iniciado sesión
if (!isset($_SESSION['id_alumno'])) {
    // Redirigir a login o pedir matrícula
    header('Location: login_alumno.php?token=' . $token);
    exit();
}

$id_alumno = $_SESSION['id_alumno'];

// Verificar si el alumno pertenece al grupo
$check_sql = "SELECT id_alumno FROM alumnos 
              WHERE id_alumno = :id_alumno AND id_grupo = :id_grupo";
$check_stmt = $pdo->prepare($check_sql);
$check_stmt->execute([
    'id_alumno' => $id_alumno,
    'id_grupo' => $qr_data['id_grupo']
]);

if ($check_stmt->rowCount() == 0) {
    die("No perteneces a este grupo");
}

// Registrar asistencia
$insert_sql = "INSERT INTO asistencias_clase 
              (id_alumno, id_materia, id_grupo, fecha, estado, tipo_registro)
              VALUES (:id_alumno, :id_materia, :id_grupo, CURDATE(), 'Presente', 'QR')
              ON DUPLICATE KEY UPDATE estado = 'Presente', tipo_registro = 'QR'";

$insert_stmt = $pdo->prepare($insert_sql);
$insert_stmt->execute([
    'id_alumno' => $id_alumno,
    'id_materia' => $qr_data['id_materia'],
    'id_grupo' => $qr_data['id_grupo']
]);

echo "Asistencia registrada correctamente para " . htmlspecialchars($qr_data['materia']) . 
     " - Grupo " . htmlspecialchars($qr_data['grupo_nombre']);
?>