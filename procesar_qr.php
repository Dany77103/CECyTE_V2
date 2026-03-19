<?php
// 1. Limpieza de salida para evitar el error "Respuesta no válida"
ob_start(); 
error_reporting(0); // Evita que los warnings de PHP ensucien el JSON
header('Content-Type: application/json; charset=utf-8');

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cecyte_sc";

try {
    $con = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']));
}

// Funciones de utilidad
function generarCodigoQR($alumno_id) {
    return 'CECYTE-' . $alumno_id . '-' . uniqid() . '-' . bin2hex(random_bytes(4));
}

function getClientIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'registrar':
        registrarAsistencia();
        break;
    case 'get_asistencias':
        obtenerAsistencias();
        break;
    case 'get_stats':
        obtenerEstadisticas();
        break;
    case 'generar_qr':
        generarQRIndividual();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

// Limpia cualquier espacio en blanco accidental y envía el resultado
ob_end_flush();

function registrarAsistencia() {
    global $con;
    
    $codigo_qr = $_POST['codigo_qr'] ?? '';
    $tipo_solicitado = $_POST['tipo_registro'] ?? 'entrada'; 
    $salon = $_POST['salon'] ?? 'General';
    
    if (empty($codigo_qr)) {
        echo json_encode(['success' => false, 'message' => 'Código no válido']);
        return;
    }
    
    // Buscar alumno
    $sql = "SELECT a.id, a.nombre, a.matricula FROM alumnos a 
            LEFT JOIN alumnos_qr q ON a.id = q.alumno_id 
            WHERE q.codigo_qr = ? OR a.matricula = ? LIMIT 1";
    $stmt = $con->prepare($sql);
    $stmt->execute([$codigo_qr, $codigo_qr]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$alumno) {
        echo json_encode(['success' => false, 'message' => 'Alumno no encontrado']);
        return;
    }
    
    $alumno_id = $alumno['id'];
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i:s');
    
    // Verificar registro previo de hoy
    $sql = "SELECT * FROM asistencias_qr WHERE alumno_id = ? AND fecha = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$alumno_id, $fecha_actual]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tipo_solicitado === 'entrada') {
        if ($registro) {
            echo json_encode(['success' => false, 'message' => "⚠️ {$alumno['nombre']} ya tiene entrada"]);
        } else {
            $sql = "INSERT INTO asistencias_qr (alumno_id, fecha, hora_entrada, salon, ip_address) VALUES (?, ?, ?, ?, ?)";
            $stmt = $con->prepare($sql);
            $stmt->execute([$alumno_id, $fecha_actual, $hora_actual, $salon, getClientIP()]);
            echo json_encode(['success' => true, 'message' => "✅ ENTRADA: {$alumno['nombre']}"]);
        }
    } else { 
        if (!$registro) {
            echo json_encode(['success' => false, 'message' => "❌ No hay entrada previa para {$alumno['nombre']}"]);
        } elseif (!empty($registro['hora_salida']) && $registro['hora_salida'] !== '00:00:00') {
            echo json_encode(['success' => false, 'message' => "⚠️ {$alumno['nombre']} ya registró salida"]);
        } else {
            $sql = "UPDATE asistencias_qr SET hora_salida = ? WHERE id = ?";
            $stmt = $con->prepare($sql);
            $stmt->execute([$hora_actual, $registro['id']]);
            echo json_encode(['success' => true, 'message' => "🔴 SALIDA: {$alumno['nombre']}"]);
        }
    }
}

function obtenerAsistencias() {
    global $con;
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    
    $sql = "SELECT a.matricula, a.nombre, asis.hora_entrada, asis.hora_salida, asis.salon 
            FROM asistencias_qr asis
            JOIN alumnos a ON asis.alumno_id = a.id
            WHERE asis.fecha = ?
            ORDER BY asis.id DESC LIMIT 10";
    $stmt = $con->prepare($sql);
    $stmt->execute([$fecha]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function obtenerEstadisticas() {
    global $con;
    $fecha = date('Y-m-d');
    
    $sql = "SELECT 
            COUNT(*) as total_hoy,
            SUM(CASE WHEN hora_salida IS NULL OR hora_salida = '00:00:00' THEN 1 ELSE 0 END) as pendientes_salida
            FROM asistencias_qr WHERE fecha = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$fecha]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
}

function generarQRIndividual() {
    global $con;
    $alumno_id = $_POST['alumno_id'] ?? 0;

    $codigo_qr = generarCodigoQR($alumno_id);
    
    $sql = "INSERT INTO alumnos_qr (alumno_id, codigo_qr) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE codigo_qr = VALUES(codigo_qr)";
    $stmt = $con->prepare($sql);
    $stmt->execute([$alumno_id, $codigo_qr]);
    
    echo json_encode(['success' => true, 'qr_code' => $codigo_qr]);
}