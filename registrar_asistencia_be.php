<?php
// 1. CARGA MANUAL DE PHPMAILER
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'conexion.php'; 
date_default_timezone_set('America/Monterrey'); 

$entrada_qr = isset($_POST['matricula']) ? $_POST['matricula'] : '';
$modo_solicitado = $_POST['modo'] ?? 'Entrada';
$fecha_hoy = date('Y-m-d');

// --- LIMPIEZA DEL QR ---
if (strpos($entrada_qr, '`^') !== false) {
    $partes = explode('`^', $entrada_qr);
    $entrada_qr = $partes[0] . '`';
}
$json_limpio = trim($entrada_qr, "^` "); 
$datos_decoded = json_decode($json_limpio, true);
$matricula = ($datos_decoded && isset($datos_decoded['matricula'])) ? $datos_decoded['matricula'] : (preg_match('/(\d{7})/', $entrada_qr, $m) ? $m[1] : trim($entrada_qr));

try {
    $stmt = $con->prepare("SELECT nombre, apellido_paterno, correo_tutor FROM alumnos WHERE matricula = :mat LIMIT 1");
    $stmt->execute(['mat' => $matricula]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$alumno) {
        echo json_encode(['success' => false, 'message' => "Matrícula $matricula no registrada"]);
        exit;
    }

    $nombre_completo = $alumno['nombre'] . ' ' . $alumno['apellido_paterno'];
    $correo_tutor = $alumno['correo_tutor']; 

    // --- REGISTRO DE MOVIMIENTO ---
    if ($modo_solicitado === 'Entrada') {
        $sql = "INSERT INTO asistencias_qr (id_alumno, fecha, hora_entrada, dispositivo) VALUES (:id, :fec, :hor, 'Scanner USB')";
        $ins = $con->prepare($sql);
        $ins->execute(['id' => $matricula, 'fec' => $fecha_hoy, 'hor' => date('H:i:s')]);
        $msg = "Entrada registrada correctamente";
    } 
    else { 
        $check = $con->prepare("SELECT id FROM asistencias_qr WHERE id_alumno = :mat AND fecha = :fec AND hora_salida IS NULL ORDER BY id DESC LIMIT 1");
        $check->execute(['mat' => $matricula, 'fec' => $fecha_hoy]);
        $registro = $check->fetch(PDO::FETCH_ASSOC);

        if ($registro) {
            $upd = $con->prepare("UPDATE asistencias_qr SET hora_salida = :hor WHERE id = :id");
            $upd->execute(['hor' => date('H:i:s'), 'id' => $registro['id']]);
            $msg = "Salida registrada correctamente";
        } else {
            $sql = "INSERT INTO asistencias_qr (id_alumno, fecha, hora_salida, dispositivo) VALUES (:id, :fec, :hor, 'Scanner USB')";
            $ins = $con->prepare($sql);
            $ins->execute(['id' => $matricula, 'fec' => $fecha_hoy, 'hor' => date('H:i:s')]);
            $msg = "Salida registrada (sin entrada)";
        }
    }

    // --- ENVÍO DE CORREO (ANTES DEL JSON PARA ASEGURAR SALIDA) ---
    if (!empty($correo_tutor)) {
        enviarAvisoTutor($correo_tutor, $nombre_completo, $modo_solicitado, date('h:i:s A'));
    }

    // --- RESPUESTA FINAL ---
    echo json_encode([
        'success' => true,
        'nombre' => $nombre_completo,
        'matricula' => $matricula,
        'hora' => date('h:i:s A'),
        'message' => $msg
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function enviarAvisoTutor($destinatario, $alumno, $tipo, $hora) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admprueva@gmail.com'; 
        $mail->Password   = 'ofkthykygjvkwcjh'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('admprueva@gmail.com', 'Seguridad CECyTE Santa Catarina');
        $mail->addAddress($destinatario);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        // Usamos el modo solicitado para el asunto y cuerpo
        $mail->Subject = "AVISO DE " . strtoupper($tipo) . ": $alumno";
        
        $color = ($tipo == 'Entrada') ? '#0d6efd' : '#ff9800'; // Azul entrada, Naranja salida
        
        $mail->Body = "
            <div style='font-family: sans-serif; border: 1px solid #eee; padding: 20px;'>
                <h2 style='color: $color;'>Notificación de Asistencia</h2>
                <p>Se informa que el alumno <b>$alumno</b> ha registrado su <b>$tipo</b>.</p>
                <p><b>Hora:</b> $hora</p>
                <p><b>Fecha:</b> " . date('d/m/Y') . "</p>
            </div>";

        $mail->send();
    } catch (Exception $e) {
        // Error silencioso
    }
}
?>