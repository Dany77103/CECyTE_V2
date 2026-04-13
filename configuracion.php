<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Simulación de configuración del sistema (Lógica intacta)
$configuracion = [
    'notificaciones_email' => true,
    'notificaciones_push' => false,
    'tema_oscuro' => false,
    'idioma' => 'es',
    'resultados_por_pagina' => 25,
    'formato_fecha' => 'd/m/Y',
    'auto_guardado' => true,
    'zona_horaria' => 'America/Mexico_City'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración | SGA CECyTE</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --accent: #8bc34a;
            --bg: #f4f6f9;
            --white: #ffffff;
            --text-main: #1e293b;
            --text-sub: #64748b;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow: 0 4px 20px rgba(0,0,0,0.06);
            --radius: 20px;
        }

        body { 
            background-color: var(--bg); 
            font-family: 'Inter', sans-serif; 
            color: var(--text-main);
            padding-top: 90px;
        }

        /* --- NAVBAR --- */
        .navbar {
            background: var(--white);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }
        .navbar-brand { display: flex; align-items: center; gap: 15px; text-decoration: none; }
        .navbar-brand img { height: 45px; }
        .navbar-brand span { font-weight: 700; color: var(--primary); font-size: 1.2rem; }

        /* --- CONFIG CARDS --- */
        .config-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.02);
            margin-bottom: 25px;
            overflow: hidden;
            animation: slideUp 0.5s ease forwards;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .config-card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .config-card-header i {
            width: 42px; height: 42px;
            background: #f0fdf4;
            color: var(--primary-light);
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px; font-size: 1.2rem;
        }

        .config-item {
            padding: 20px 25px;
            border-bottom: 1px solid #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .config-item:last-child { border-bottom: none; }

        .item-label { font-weight: 700; color: var(--text-main); margin: 0; font-size: 0.95rem; }
        .item-desc { font-size: 0.85rem; color: var(--text-sub); margin: 0; }

        /* --- ZONA DE PELIGRO --- */
        .danger-zone { border: 1px solid #fee2e2; }
        .danger-zone .config-card-header i { background: #fef2f2; color: #ef4444; }

        /* --- BARRA DE GUARDADO --- */
        .save-bar {
            background: var(--white);
            padding: 20px 30px;
            border-radius: 18px;
            box-shadow: var(--shadow);
            margin-top: 20px;
            margin-bottom: 50px;
            border: 1px solid rgba(0,0,0,0.05);
        }

        /* --- FORM CONTROLS --- */
        .form-switch .form-check-input { width: 2.8em; height: 1.4em; cursor: pointer; }
        .form-check-input:checked { background-color: var(--primary-light); border-color: var(--primary-light); }
        
        .form-select {
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            font-size: 0.9rem;
            padding: 8px 15px;
            cursor: pointer;
            transition: 0.3s;
        }
        .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26, 83, 48, 0.1); }

        .btn-primary-custom {
            background: var(--primary);
            color: white; border: none; font-weight: 700;
            padding: 10px 25px; border-radius: 12px; transition: 0.3s;
        }
        .btn-primary-custom:hover { background: var(--primary-light); transform: translateY(-2px); color: white; }

        footer { color: var(--text-sub); font-size: 0.8rem; padding-bottom: 40px; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="main.php" class="navbar-brand">
        <img src="img/logo.png" alt="CECyTE Logo">
        <span>CECyTE Santa Catarina</span>
    </a>
    <div class="d-flex align-items-center gap-3">
        <a href="main.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </div>
</nav>

<div class="container">
    <div class="mb-5">
        <h2 class="fw-800 mb-1" style="color: var(--primary);">Ajustes del Sistema</h2>
        <p class="text-secondary">Personaliza tu experiencia de usuario y gestiona las preferencias del portal.</p>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="config-card">
                <div class="config-card-header">
                    <i class="fas fa-desktop"></i>
                    <h5 class="m-0 fw-bold">Personalización Visual</h5>
                </div>
                <div class="config-body">
                    <div class="config-item">
                        <div>
                            <p class="item-label">Modo Oscuro</p>
                            <p class="item-desc">Optimiza la interfaz para entornos con poca iluminación.</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="tema_oscuro" <?php echo $configuracion['tema_oscuro'] ? 'checked' : ''; ?>>
                        </div>
                    </div>
                    <div class="config-item">
                        <div>
                            <p class="item-label">Idioma del Portal</p>
                            <p class="item-desc">Selecciona el lenguaje predeterminado.</p>
                        </div>
                        <select class="form-select w-auto">
                            <option value="es" <?php echo ($configuracion['idioma'] == 'es') ? 'selected' : ''; ?>>Español (MX)</option>
                            <option value="en" <?php echo ($configuracion['idioma'] == 'en') ? 'selected' : ''; ?>>English (US)</option>
                        </select>
                    </div>
                    <div class="config-item">
                        <div>
                            <p class="item-label">Densidad de Datos</p>
                            <p class="item-desc">Cantidad de registros mostrados en las tablas.</p>
                        </div>
                        <select class="form-select w-auto">
                            <option value="25" selected>25 filas</option>
                            <option value="50">50 filas</option>
                            <option value="100">100 filas</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="config-card">
                <div class="config-card-header">
                    <i class="fas fa-bell"></i>
                    <h5 class="m-0 fw-bold">Alertas y Notificaciones</h5>
                </div>
                <div class="config-body">
                    <div class="config-item">
                        <div>
                            <p class="item-label">Alertas por Correo</p>
                            <p class="item-desc">Enviar notificaciones de asistencia a tutores.</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" checked>
                        </div>
                    </div>
                    <div class="config-item">
                        <div>
                            <p class="item-label">Notificaciones de Escritorio</p>
                            <p class="item-desc">Mostrar ventanas emergentes en el navegador.</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="config-card">
                <div class="config-card-header">
                    <i class="fas fa-clock"></i>
                    <h5 class="m-0 fw-bold">Región y Tiempo</h5>
                </div>
                <div class="p-4">
                    <div class="mb-4">
                        <label class="item-label mb-2 d-block">Zona Horaria</label>
                        <select class="form-select w-100">
                            <option selected><?php echo $configuracion['zona_horaria']; ?> (GMT-6)</option>
                            <option>America/Monterrey</option>
                            <option>America/New_York</option>
                        </select>
                    </div>
                    <div>
                        <label class="item-label mb-2 d-block">Formato de Fecha</label>
                        <div class="d-flex gap-2">
                            <div class="flex-grow-1">
                                <input type="radio" class="btn-check" name="datefmt" id="fmt1" checked>
                                <label class="btn btn-outline-secondary w-100 btn-sm fw-bold" for="fmt1" style="border-radius: 8px;">DD/MM/AAAA</label>
                            </div>
                            <div class="flex-grow-1">
                                <input type="radio" class="btn-check" name="datefmt" id="fmt2">
                                <label class="btn btn-outline-secondary w-100 btn-sm fw-bold" for="fmt2" style="border-radius: 8px;">AAAA-MM-DD</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="config-card danger-zone">
                <div class="config-card-header">
                    <i class="fas fa-shield-halved"></i>
                    <h5 class="m-0 fw-bold text-danger">Seguridad y Privacidad</h5>
                </div>
                <div class="p-4">
                    <p class="item-desc mb-3">Acciones de mantenimiento y protección de cuenta.</p>
                    <button class="btn btn-outline-danger btn-sm w-100 mb-2 fw-bold py-2" style="border-radius: 10px;">
                        <i class="fas fa-trash-can me-2"></i>Limpiar Caché del Sistema
                    </button>
                    <button class="btn btn-danger btn-sm w-100 fw-bold py-2" style="border-radius: 10px;">
                        <i class="fas fa-power-off me-2"></i>Cerrar sesiones activas
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="save-bar d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <div class="spinner-grow spinner-grow-sm text-success" role="status"></div>
            <span class="text-secondary small fw-500">Los cambios se sincronizan en la nube.</span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-light px-4 fw-bold text-secondary" style="border-radius: 12px; border: 1.5px solid #e2e8f0;">Descartar</button>
            <button class="btn-primary-custom px-4 shadow-sm">
                <i class="fas fa-floppy-disk me-2"></i>Guardar Cambios
            </button>
        </div>
    </div>

    <footer class="text-center">
        CECyTE Santa Catarina &copy; 2026 | Sistema de Gestión Académica (SGA)
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>