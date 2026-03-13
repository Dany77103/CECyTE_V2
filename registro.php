<?php
// Incluir configuración común
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    die("Error: Archivo de configuración no encontrado.");
}
verificarSesion();
$con = conectarDB();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CECYTE - Sistema de Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --verde-oscuro: #1a5330; --verde-principal: #2e7d32;
            --verde-medio: #4caf50; --verde-claro: #8bc34a;
            --bg-light: #f1f8e9;
        }
        body { background-color: var(--bg-light); }
        .main-header { background: white; padding: 20px 40px; border-bottom: 3px solid var(--verde-medio); }
        .card-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; padding: 40px; }
        .card-box { background: white; padding: 25px; border-radius: 15px; border-top: 5px solid var(--verde-principal); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .card-box i { font-size: 2.5rem; color: var(--verde-principal); margin-bottom: 15px; }
    </style>
</head>
<body>

    <header class="main-header d-flex justify-content-between align-items-center">
        <h4 class="text-success fw-bold">SISTEMA DE REGISTRO - CECyTE</h4>
        <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
    </header>

    <div class="card-container">
        <div class="card-box" style="border-top-color: var(--verde-oscuro);">
            <i class="fas fa-user-graduate"></i>
            <h5>Alta de Alumnos</h5>
            <p>Registra nuevos alumnos con su información completa.</p>
            <a href="nuevo_alumno.php" class="btn btn-primary">Registrar Alumno</a>
        </div>

        <div class="card-box" style="border-top-color: var(--verde-principal);">
            <i class="fas fa-chalkboard-teacher"></i>
            <h5>Alta de Personal</h5>
            <p>Maestros y administrativo.</p>
            <a href="nuevo_maestro.php" class="btn btn-success">Registrar Personal</a>
        </div>

        <div class="card-box" style="border-top-color: var(--verde-medio);">
            <i class="fas fa-briefcase"></i>
            <h5>Datos Laborales</h5>
            <p>Gestión de información profesional.</p>
            <a href="datos_laborales.php" class="btn btn-info text-white">Ver Laborales</a>
        </div>

        <div class="card-box" style="border-top-color: var(--verde-claro);">
            <i class="fas fa-graduation-cap"></i>
            <h5>Datos Académicos</h5>
            <p>Formación y certificaciones.</p>
            <a href="datos_academicos.php" class="btn btn-warning">Ver Académicos</a>
        </div>

        <div class="card-box" style="border-top-color: #66bb6a;">
            <i class="fas fa-history"></i>
            <h5>Historial Académico</h5>
            <p>Registro del historial completo.</p>
            <a href="historial_academico.php" class="btn btn-danger">Ir al Historial</a>
        </div>

        <div class="card-box" style="border-top-color: #a5d6a7;">
            <i class="fas fa-check-circle"></i>
            <h5>Calificaciones</h5>
            <p>Asistencia y evaluaciones.</p>
            <a href="seleccionar_clase.php" class="btn btn-secondary">Gestionar</a>
        </div>

        <div class="card-box" style="border-top-color: var(--verde-oscuro);">
            <i class="fas fa-calendar-alt"></i>
            <h5>Horarios</h5>
            <p>Gestión de horarios de clase.</p>
            <a href="horario_maestros_captura.php" class="btn btn-dark">Ver Horarios</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>