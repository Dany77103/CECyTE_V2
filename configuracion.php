<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Simulación de configuración del sistema (Mantenemos tu lógica intacta)
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
    <title>Configuración - Sistema de Reportes</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed: 85px;
            --primary-color: #1a5330;    /* Verde CECyTE */
            --secondary-color: #2e7d32;
            --accent-color: #4caf50;
            --light-bg: #f4f7f5;
            --card-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Layout Responsivo */
        .main-container { display: flex; min-height: 100vh; }
        
        .content-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            width: calc(100% - var(--sidebar-width));
        }

        /* Ajustes para móviles */
        @media (max-width: 992px) {
            .content-wrapper { 
                margin-left: 0; 
                width: 100%;
            }
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
        }

        /* Estilización de Tarjetas de Configuración */
        .config-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .config-card-header {
            background: #fff;
            padding: 20px 25px;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .config-card-header i {
            font-size: 1.5rem;
            color: var(--secondary-color);
            background: #e8f5e9;
            padding: 10px;
            border-radius: 10px;
        }

        .config-item {
            padding: 20px 25px;
            border-bottom: 1px solid #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }

        .config-item:last-child { border-bottom: none; }
        .config-item:hover { background-color: #fcfdfc; }

        .item-label { font-weight: 600; color: #334155; margin-bottom: 2px; }
        .item-desc { font-size: 0.85rem; color: #64748b; margin: 0; }

        /* Custom Switches */
        .form-check-input:checked {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .danger-zone {
            border: 1px solid #fee2e2;
            background: #fffafb;
        }
        
        .danger-zone .config-card-header i {
            color: #ef4444;
            background: #fef2f2;
        }

        .save-bar {
            position: sticky;
            bottom: 20px;
            background: white;
            padding: 15px 30px;
            border-radius: 12px;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.05);
            z-index: 900;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="content-wrapper">
        <header class="main-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
            <button class="btn d-lg-none" id="mobile-toggle"><i class='bx bx-menu'></i></button>
            <h5 class="m-0 text-dark fw-bold">Ajustes del Sistema</h5>
            <span class="badge rounded-pill px-3 py-2" style="background: var(--primary-color);">CECyTE SC</span>
        </header>

        <main class="p-3 p-md-5">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="config-card">
                            <div class="config-card-header">
                                <i class='bx bx-user-settings'></i>
                                <h5 class="m-0 fw-bold">Preferencias de Interfaz</h5>
                            </div>
                            <div class="config-body">
                                <div class="config-item">
                                    <div>
                                        <p class="item-label">Tema del Sistema</p>
                                        <p class="item-desc">Alternar entre modo claro y oscuro.</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="tema_oscuro" <?php echo $configuracion['tema_oscuro'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                <div class="config-item">
                                    <div>
                                        <p class="item-label">Idioma</p>
                                        <p class="item-desc">Selecciona tu lenguaje de preferencia.</p>
                                    </div>
                                    <select class="form-select form-select-sm w-auto" name="idioma">
                                        <option value="es" <?php echo ($configuracion['idioma'] == 'es') ? 'selected' : ''; ?>>Español</option>
                                        <option value="en" <?php echo ($configuracion['idioma'] == 'en') ? 'selected' : ''; ?>>English</option>
                                    </select>
                                </div>
                                <div class="config-item">
                                    <div>
                                        <p class="item-label">Resultados por página</p>
                                        <p class="item-desc">Cantidad de registros en tablas.</p>
                                    </div>
                                    <select class="form-select form-select-sm w-auto">
                                        <option value="25" selected>25 registros</option>
                                        <option value="50">50 registros</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="config-card">
                            <div class="config-card-header">
                                <i class='bx bx-bell'></i>
                                <h5 class="m-0 fw-bold">Notificaciones</h5>
                            </div>
                            <div class="config-body">
                                <div class="config-item">
                                    <div>
                                        <p class="item-label">Correos Electrónicos</p>
                                        <p class="item-desc">Recibir alertas de entrada/salida por email.</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked>
                                    </div>
                                </div>
                                <div class="config-item">
                                    <div>
                                        <p class="item-label">Alertas de Escritorio</p>
                                        <p class="item-desc">Notificaciones push en el navegador.</p>
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
                                <i class='bx bx-time-five'></i>
                                <h5 class="m-0 fw-bold">Región y Tiempo</h5>
                            </div>
                            <div class="config-body p-4">
                                <div class="mb-3">
                                    <label class="form-label item-label">Zona Horaria</label>
                                    <select class="form-select">
                                        <option><?php echo $configuracion['zona_horaria']; ?></option>
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label item-label">Formato de Fecha</label>
                                    <select class="form-select">
                                        <option value="d/m/Y">DD/MM/AAAA</option>
                                        <option value="Y-m-d">AAAA-MM-DD</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="config-card danger-zone">
                            <div class="config-card-header">
                                <i class='bx bx-shield-x'></i>
                                <h5 class="m-0 fw-bold text-danger">Zona de Peligro</h5>
                            </div>
                            <div class="p-4">
                                <p class="item-desc mb-3">Estas acciones afectan la integridad de tus datos locales.</p>
                                <button class="btn btn-outline-danger btn-sm w-100 mb-2">Limpiar Cache</button>
                                <button class="btn btn-danger btn-sm w-100">Borrar cuenta</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="save-bar d-flex justify-content-between align-items-center">
                    <span class="text-muted d-none d-md-inline small"><i class='bx bx-info-circle'></i> Los cambios se aplicarán al recargar.</span>
                    <div>
                        <button class="btn btn-light px-4 me-2">Cancelar</button>
                        <button class="btn btn-success px-4" style="background-color: var(--secondary-color);">Guardar Cambios</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>