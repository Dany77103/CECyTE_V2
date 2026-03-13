<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cecyte_sc";

try {
    $con = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión']));
}

// Función para generar código QR único
function generarCodigoQR($alumno_id) {
    return 'CECYTE-' . $alumno_id . '-' . uniqid() . '-' . bin2hex(random_bytes(4));
}

// Función para obtener IP del dispositivo
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Manejar diferentes acciones
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'registrar':
        registrarAsistencia();
        break;
    
    case 'generar_qr':
        generarQRIndividual();
        break;
    
    case 'generar_todos_qr':
        generarTodosQR();
        break;
    
    case 'ver_qr':
        verQRExistente();
        break;
    
    case 'get_alumnos':
        obtenerAlumnos();
        break;
    
    case 'get_asistencias':
        obtenerAsistencias();
        break;
    
    case 'get_stats':
        obtenerEstadisticas();
        break;
    
    case 'get_grupos':
        obtenerGrupos();
        break;
    
    case 'export_excel':
        exportarExcel();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

function registrarAsistencia() {
    global $con;
    
    $codigo_qr = $_POST['codigo_qr'] ?? '';
    
    if (empty($codigo_qr)) {
        echo json_encode(['success' => false, 'message' => 'Código QR no válido']);
        return;
    }
    
    // Buscar alumno por código QR
    $sql = "SELECT a.id, a.matricula, a.nombre FROM alumnos a 
            JOIN alumnos_qr q ON a.id = q.alumno_id 
            WHERE q.codigo_qr = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$codigo_qr]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$alumno) {
        echo json_encode(['success' => false, 'message' => 'Alumno no encontrado']);
        return;
    }
    
    $alumno_id = $alumno['id'];
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i:s');
    $ip_address = getClientIP();
    $dispositivo = $_SERVER['HTTP_USER_AGENT'];
    
    // Verificar si ya existe registro para hoy
    $sql = "SELECT * FROM asistencias_qr 
            WHERE alumno_id = ? AND fecha = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$alumno_id, $fecha_actual]);
    $asistencia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($asistencia) {
        // Si ya tiene entrada pero no tiene salida
        if ($asistencia['hora_entrada'] && !$asistencia['hora_salida']) {
            $sql = "UPDATE asistencias_qr 
                    SET hora_salida = ?, ip_address = ?, dispositivo = ? 
                    WHERE id = ?";
            $stmt = $con->prepare($sql);
            $stmt->execute([$hora_actual, $ip_address, $dispositivo, $asistencia['id']]);
            
            echo json_encode([
                'success' => true, 
                'message' => "✅ Salida registrada para {$alumno['nombre']} a las {$hora_actual}"
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => "⚠️ {$alumno['nombre']} ya tiene registro completo para hoy"
            ]);
        }
    } else {
        // Registrar entrada
        $sql = "INSERT INTO asistencias_qr (alumno_id, fecha, hora_entrada, ip_address, dispositivo) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->execute([$alumno_id, $fecha_actual, $hora_actual, $ip_address, $dispositivo]);
        
        echo json_encode([
            'success' => true, 
            'message' => "✅ Entrada registrada para {$alumno['nombre']} a las {$hora_actual}"
        ]);
    }
}

function generarQRIndividual() {
    global $con;
    
    $alumno_id = $_POST['alumno_id'] ?? 0;
    
    // Obtener datos del alumno
    $sql = "SELECT id, matricula, nombre, grupo FROM alumnos WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$alumno_id]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$alumno) {
        echo json_encode(['success' => false, 'message' => 'Alumno no encontrado']);
        return;
    }
    
    // Verificar si ya tiene QR
    $sql = "SELECT codigo_qr FROM alumnos_qr WHERE alumno_id = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$alumno_id]);
    $qr_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($qr_existente) {
        // Ya tiene QR, retornarlo
        echo json_encode([
            'success' => true,
            'message' => 'QR ya generado anteriormente',
            'qr_code' => $qr_existente['codigo_qr'],
            'alumno' => $alumno
        ]);
        return;
    }
    
    // Generar nuevo código QR
    $codigo_qr = generarCodigoQR($alumno_id);
    
    // Guardar en base de datos
    $sql = "INSERT INTO alumnos_qr (alumno_id, codigo_qr) VALUES (?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->execute([$alumno_id, $codigo_qr]);
    
    echo json_encode([
        'success' => true,
        'message' => "QR generado exitosamente para {$alumno['nombre']}",
        'qr_code' => $codigo_qr,
        'alumno' => $alumno
    ]);
}

function generarTodosQR() {
    global $con;
    
    // Obtener todos los alumnos sin QR
    $sql = "SELECT a.id, a.nombre FROM alumnos a 
            LEFT JOIN alumnos_qr q ON a.id = q.alumno_id 
            WHERE q.id IS NULL";
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $generados = 0;
    $errores = 0;
    
    foreach ($alumnos as $alumno) {
        try {
            $codigo_qr = generarCodigoQR($alumno['id']);
            
            $sql = "INSERT INTO alumnos_qr (alumno_id, codigo_qr) VALUES (?, ?)";
            $stmt = $con->prepare($sql);
            $stmt->execute([$alumno['id'], $codigo_qr]);
            
            $generados++;
        } catch (Exception $e) {
            $errores++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Se generaron $generados códigos QR. Errores: $errores"
    ]);
}

function verQRExistente() {
    global $con;
    
    $alumno_id = $_POST['alumno_id'] ?? 0;
    
    $sql = "SELECT a.*, q.codigo_qr 
            FROM alumnos a 
            JOIN alumnos_qr q ON a.id = q.alumno_id 
            WHERE a.id = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$alumno_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'qr_code' => $result['codigo_qr'],
            'alumno' => [
                'id' => $result['id'],
                'nombre' => $result['nombre'],
                'matricula' => $result['matricula'],
                'grupo' => $result['grupo']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'QR no encontrado']);
    }
}

function obtenerAlumnos() {
    global $con;
    
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT a.*, 
            CASE WHEN q.id IS NOT NULL THEN 1 ELSE 0 END as qr_generado
            FROM alumnos a 
            LEFT JOIN alumnos_qr q ON a.id = q.alumno_id 
            WHERE a.nombre LIKE ? OR a.matricula LIKE ?
            ORDER BY a.grupo, a.nombre";
    $stmt = $con->prepare($sql);
    $stmt->execute(["%$search%", "%$search%"]);
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($alumnos);
}

function obtenerAsistencias() {
    global $con;
    
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $grupo = $_GET['grupo'] ?? '';
    
    $sql = "SELECT a.matricula, a.nombre, a.grupo, 
            asis.fecha, asis.hora_entrada, asis.hora_salida
            FROM alumnos a 
            LEFT JOIN asistencias_qr asis ON a.id = asis.alumno_id AND asis.fecha = ?
            WHERE (? = '' OR a.grupo = ?)
            ORDER BY a.grupo, a.nombre";
    $stmt = $con->prepare($sql);
    $stmt->execute([$fecha, $grupo, $grupo]);
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($asistencias);
}

function obtenerEstadisticas() {
    global $con;
    
    $fecha_actual = date('Y-m-d');
    
    // Total asistencias hoy
    $sql = "SELECT COUNT(DISTINCT alumno_id) as total 
            FROM asistencias_qr 
            WHERE fecha = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$fecha_actual]);
    $total_hoy = $stmt->fetchColumn();
    
    // Pendientes de salida
    $sql = "SELECT COUNT(*) as pendientes 
            FROM asistencias_qr 
            WHERE fecha = ? AND hora_entrada IS NOT NULL AND hora_salida IS NULL";
    $stmt = $con->prepare($sql);
    $stmt->execute([$fecha_actual]);
    $pendientes = $stmt->fetchColumn();
    
    echo json_encode([
        'total_hoy' => $total_hoy,
        'pendientes_salida' => $pendientes
    ]);
}

function obtenerGrupos() {
    global $con;
    
    $sql = "SELECT DISTINCT grupo FROM alumnos WHERE grupo IS NOT NULL ORDER BY grupo";
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $grupos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode($grupos);
}

function exportarExcel() {
    global $con;
    
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $grupo = $_GET['grupo'] ?? '';
    
    $sql = "SELECT 
            a.matricula as 'Matrícula',
            a.nombre as 'Nombre',
            a.grupo as 'Grupo',
            asis.fecha as 'Fecha',
            asis.hora_entrada as 'Hora Entrada',
            asis.hora_salida as 'Hora Salida',
            CASE 
                WHEN asis.hora_salida IS NOT NULL THEN 'Completo'
                WHEN asis.hora_entrada IS NOT NULL THEN 'En clase'
                ELSE 'Sin registro'
            END as 'Estado'
            FROM alumnos a 
            LEFT JOIN asistencias_qr asis ON a.id = asis.alumno_id AND asis.fecha = ?
            WHERE (? = '' OR a.grupo = ?)
            ORDER BY a.grupo, a.nombre";
    $stmt = $con->prepare($sql);
    $stmt->execute([$fecha, $grupo, $grupo]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($data);
}
?>