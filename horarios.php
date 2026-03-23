<?php
session_start();
// Validación de sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios | CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --verde-primario: #1a5330;
            --verde-secundario: #2e7d32;
            --verde-acento: #4caf50;
            --verde-fondo: #f1f8f1;
        }

        body { 
            background-color: var(--verde-fondo); 
            font-family: 'Segoe UI', sans-serif; 
        }

        .card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }

        .card-selector {
            border: none;
            border-radius: 20px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            cursor: pointer;
            border: 2px solid transparent;
            background: white;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .card-selector:hover {
            transform: translateY(-10px);
            border-color: var(--verde-acento) !important;
            box-shadow: 0 15px 35px rgba(26, 83, 48, 0.15);
        }

        .card-selector i {
            font-size: 4.5rem;
            color: var(--verde-primario);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .card-selector:hover i {
            transform: scale(1.1);
        }

        .card-selector h4 {
            color: var(--verde-primario);
            font-weight: 700;
            margin-bottom: 15px;
        }

        .header-section {
            padding: 60px 0 40px;
        }
    </style>
</head>
<body>

<div class="container pb-5">
    <div class="header-section text-center">
        <h2 style="color: var(--verde-primario); font-weight: 800; font-size: 2.5rem;">Gestión de Horarios Académicos</h2>
        <p class="text-muted fs-5">Panel central de administración y visualización de carga académica</p>
        <hr class="mx-auto" style="width: 100px; height: 4px; background-color: var(--verde-acento); border: none; opacity: 1;">
    </div>

    <div class="row justify-content-center g-4">
        <div class="col-lg-4 col-md-6">
            <a href="captura_horario_grupos.php" class="card-link">
                <div class="card-selector p-5 text-center shadow-sm">
                    <i class='bx bxs-group'></i>
                    <h4>Capturar por Grupo</h4>
                    <p class="text-muted">Configuración detallada de materias y aulas para cada grupo y semestre.</p>
                </div>
            </a>
        </div>

        <div class="col-lg-4 col-md-6">
            <a href="captura_horario_maestros.php" class="card-link">
                <div class="card-selector p-5 text-center shadow-sm">
                    <i class='bx bxs-graduation'></i>
                    <h4>Capturar por Maestro</h4>
                    <p class="text-muted">Asignación de carga horaria y disponibilidad por docente del plantel.</p>
                </div>
            </a>
        </div>

        <div class="col-lg-4 col-md-6">
            <a href="consulta_horarios.php" class="card-link">
                <div class="card-selector p-5 text-center shadow-sm" style="background: linear-gradient(to bottom, #ffffff, #f9fffb);">
                    <i class='bx bx-calendar-check' style="color: var(--verde-secundario);"></i>
                    <h4>Consulta de Horarios</h4>
                    <p class="text-muted">Vista final de horarios, impresión y exportación para alumnos y personal.</p>
                </div>
            </a>
        </div>
    </div>

    <div class="text-center mt-5">
        <a href="main.php" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 10px;">
            <i class='bx bx-left-arrow-alt'></i> Regresar al Panel Principal
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>