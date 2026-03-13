<?php
// logging.php
function logAction($action, $details, $user_id = null) {
    $logFile = __DIR__ . '/logs/actions_' . date('Y-m-d') . '.log';
    $message = date('Y-m-d H:i:s') . " | ";
    $message .= "Usuario: " . ($user_id ?? $_SESSION['usuario_id'] ?? 'Sistema') . " | ";
    $message .= "Acción: $action | ";
    $message .= "Detalles: " . json_encode($details) . PHP_EOL;
    
    file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
}
?>