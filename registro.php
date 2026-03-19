<?php
// Incluir configuración común (Lógica intacta)
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
    <title>SGA | Sistema de Registro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-dark: #1b5e20;
            --accent: #8bc34a;
            --bg: #f0f2f5;
            --white: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text-main); line-height: 1.5; }

        /* --- HEADER SGA --- */
        .header {
            background: var(--white);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            position: sticky; top: 0; z-index: 100;
        }
        .header-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--primary-dark); font-weight: 800; }
        
        .btn-logout {
            padding: 8px 16px; border-radius: 8px; background: #fee2e2; color: #dc2626;
            text-decoration: none; font-weight: 700; font-size: 0.85rem; transition: 0.3s;
        }
        .btn-logout:hover { background: #dc2626; color: white; }

        .container { max-width: 1200px; margin: 2.5rem auto; padding: 0 20px; }

        .section-title { margin-bottom: 35px; }
        .section-title h2 { font-size: 1.8rem; font-weight: 800; color: var(--primary-dark); }
        .section-title p { color: var(--text-muted); }

        /* --- GRID DE REGISTROS --- */
        .grid-registros {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        /* --- CARD STYLE --- */
        .card-registro {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .card-registro:hover { transform: translateY(-10px); }

        /* Imagen/Ilustración en la cartilla */
        .card-image {
            height: 140px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .card-image img {
            width: 100px;
            height: auto;
            opacity: 0.9;
        }
        .card-image i {
            font-size: 3.5rem;
            color: var(--primary);
            opacity: 0.2;
            position: absolute;
            right: -10px;
            bottom: -10px;
        }

        .card-content { padding: 20px; flex-grow: 1; }
        .card-content h5 { font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin-bottom: 8px; }
        .card-content p { font-size: 0.85rem; color: var(--text-muted); min-height: 40px; }

        /* --- ACCIONES --- */
        .card-actions { padding: 0 20px 20px; }
        .btn-action {
            display: block; width: 100%; padding: 12px; border-radius: 12px;
            text-align: center; font-weight: 700; font-size: 0.85rem;
            text-decoration: none; transition: 0.3s;
            background: var(--bg); color: var(--text-main);
        }
        .btn-action:hover { background: var(--primary); color: white; }

        /* Colores de acento por categoría */
        .accent-bar { height: 4px; width: 100%; background: var(--primary); }

        @media (max-width: 600px) {
            .grid-registros { grid-template-columns: 1fr; }
        }
        
        .animate-fade { opacity: 0; transform: translateY(20px); }
    </style>
</head>
<body>

<header class="header">
    <a href="main.php" class="header-brand">
        <i class="fas fa-graduation-cap"></i>
        <span>SGA CECYTE</span>
    </a>
    <a href="logout.php" class="btn-logout">
        <i class="fas fa-sign-out-alt"></i> Salir
    </a>
</header>

<div class="container">
    
    <div class="section-title">
        <h2>Panel de Administración</h2>
        <p>Gestión integral de alumnos, personal y registros académicos del campus.</p>
    </div>

    <div class="grid-registros">
        
        <div class="card-registro animate-fade">
            <div class="card-image">
                <img src="https://illustrations.popsy.co/green/student-going-to-school.svg" alt="Alumnos">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="accent-bar" style="background: #2e7d32;"></div>
            <div class="card-content">
                <h5>Alta de Alumnos</h5>
                <p>Inscripción y registro de nuevos estudiantes al sistema.</p>
            </div>
            <div class="card-actions">
                <a href="nuevo_alumno.php" class="btn-action">Registrar Alumno</a>
            </div>
        </div>

        <div class="card-registro animate-fade">
            <div class="card-image">
                <img src="https://illustrations.popsy.co/green/work-from-home.svg" alt="Personal">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="accent-bar" style="background: #1b5e20;"></div>
            <div class="card-content">
                <h5>Alta de Personal</h5>
                <p>Gestión de docentes y personal administrativo.</p>
            </div>
            <div class="card-actions">
                <a href="nuevo_maestro.php" class="btn-action">Registrar Personal</a>
            </div>
        </div>

        <div class="card-registro animate-fade">
            <div class="card-image">
                <img src="https://illustrations.popsy.co/green/business-analysis.svg" alt="Laboral">
                <i class="fas fa-briefcase"></i>
            </div>
            <div class="accent-bar" style="background: #4caf50;"></div>
            <div class="card-content">
                <h5>Datos Laborales</h5>
                <p>Información contractual y perfiles profesionales.</p>
            </div>
            <div class="card-actions">
                <a href="datos_laborales.php" class="btn-action">Ver Laborales</a>
            </div>
        </div>

        <div class="card-registro animate-fade">
            <div class="card-image">
                <img src="https://illustrations.popsy.co/green/success.svg" alt="Académicos">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="accent-bar" style="background: #8bc34a;"></div>
            <div class="card-content">
                <h5>Datos Académicos</h5>
                <p>Historial de formación y grados obtenidos.</p>
            </div>
            <div class="card-actions">
                <a href="datos_academicos.php" class="btn-action">Ver Académicos</a>
            </div>
        </div>

        <div class="card-registro animate-fade">
            <div class="card-image">
                <img src="https://illustrations.popsy.co/green/data-analysis.svg" alt="Historial">
                <i class="fas fa-history"></i>
            </div>
            <div class="accent-bar" style="background: #ef5350;"></div>
            <div class="card-content">
                <h5>Historial Académico</h5>
                <p>Consulta de trayectoria y kardex de estudiantes.</p>
            </div>
            <div class="card-actions">
                <a href="historial_academico.php" class="btn-action">Ir al Historial</a>
            </div>
        </div>

        <div class="card-registro animate-fade">
            <div class="card-image">
                <img src="https://illustrations.popsy.co/green/creative-work.svg" alt="Notas">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="accent-bar" style="background: #ff9800;"></div>
            <div class="card-content">
                <h5>Calificaciones</h5>
                <p>Control de evaluaciones y reportes de aprovechamiento.</p>
            </div>
            <div class="card-actions">
                <a href="seleccionar_clase.php" class="btn-action">Gestionar Notas</a>
            </div>
        </div>

        <div class="card-registro animate-fade">
            <div class="card-image">
                <img src="https://illustrations.popsy.co/green/calendar.svg" alt="Horarios">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="accent-bar" style="background: #37474f;"></div>
            <div class="card-content">
                <h5>Horarios</h5>
                <p>Planificación de carga horaria y disponibilidad.</p>
            </div>
            <div class="card-actions">
                <a href="horario_maestros_captura.php" class="btn-action">Ver Horarios</a>
            </div>
        </div>

    </div>
</div>

<footer style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 0.8rem;">
    SGA CECYTE Santa Catarina &copy; 2026
</footer>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('.animate-fade');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 80);
        });
    });
</script>

</body>
</html>