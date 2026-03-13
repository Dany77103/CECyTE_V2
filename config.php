<?php



//define('SMTP_HOST', 'smtp.gmail.com');
//define('SMTP_PORT', 587);
//define('SMTP_USER', 'emilio.montalvo@gmail.com');
//define('SMTP_PASS', 'tucontrase�a'); // O usa una contrase�a de aplicaci�n si es Gmail
//define('SMTP_ENCRYPTION', 'tls');
//define('MAIL_FROM', 'emilio.montalvo@gmail.com');
//define('MAIL_FROM_NAME', 'Sistema CECYTE');


// Configuraci�n com�n para toda la aplicaci�n

// Verificar si la sesi�n ya est� activa antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuraci�n de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cecyte_scv2');

// Paleta de colores CECyTE
define('VERDE_OSCURO', '#1a5330');
define('VERDE_PRINCIPAL', '#2e7d32');
define('VERDE_MEDIO', '#4caf50');
define('VERDE_CLARO', '#8bc34a');
define('VERDE_BRILLANTE', '#81c784');

// Verificar sesi�n
function verificarSesion() {
    // Asegurarse de que la sesi�n est� iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: index.php');
        exit;
    }
}

// Conexi�n a la base de datos
function conectarDB() {
    try {
        $con = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $con;
    } catch (PDOException $e) {
        die("Error de conexi�n a la base de datos: " . $e->getMessage());
    }
}
?>