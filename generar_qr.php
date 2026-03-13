<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['id_maestro'])) {
    header('Location: login.php');
    exit();
}

$id_materia = $_GET['materia'];
$id_grupo = $_GET['grupo'];
$id_maestro = $_SESSION['id_maestro'];
$fecha = date('Y-m-d');

// Generar token único
$token = bin2hex(random_bytes(32));

// Guardar token en la base de datos
$sql = "INSERT INTO qr_clase (id_materia, id_grupo, fecha, token, creado_por)
        VALUES (:id_materia, :id_grupo, :fecha, :token, :creado_por)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'id_materia' => $id_materia,
    'id_grupo' => $id_grupo,
    'fecha' => $fecha,
    'token' => $token,
    'creado_por' => $id_maestro
]);

// URL para escanear QR
$url = "https://tudominio.com/registrar_asistencia_qr.php?token=" . $token;

// Generar código QR (requiere librería como phpqrcode)
require_once 'phpqrcode/qrlib.php';
$qrFile = 'temp_qr/' . $token . '.png';
QRcode::png($url, $qrFile, QR_ECLEVEL_L, 10);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generar QR</title>
</head>
<body>
    <h2>Código QR para la clase de hoy</h2>
    <p>Token: <?= htmlspecialchars($token) ?></p>
    <p>URL: <?= htmlspecialchars($url) ?></p>
    <img src="<?= $qrFile ?>" alt="Código QR">
    <p>Válido solo para hoy: <?= date('d/m/Y') ?></p>
</body>
</html>