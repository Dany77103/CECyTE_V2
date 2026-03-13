<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Simulación de datos del usuario (en un sistema real esto vendría de la base de datos)
$user_data = [
    'nombre' => $_SESSION['nombre'] ?? 'Juan Pérez',
    'email' => $_SESSION['email'] ?? 'juan.perez@cecyte.edu.mx',
    'usuario' => $_SESSION['username'] ?? 'jperez',
    'rol' => $_SESSION['rol'] ?? 'Profesor',
    'plantel' => $_SESSION['plantel'] ?? 'CECyTE Santa Catarina',
    'departamento' => $_SESSION['departamento'] ?? 'Ciencias',
    'fecha_registro' => $_SESSION['fecha_registro'] ?? '2023-01-15',
    'telefono' => $_SESSION['telefono'] ?? '8123456789',
    'extension' => $_SESSION['extension'] ?? '101'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Sistema de Reportes</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="styles.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed: 80px;
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --text-color: #ecf0f1;
            --hover-color: #1abc9c;
        }
        
        .main-container {
            display: flex;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: var(--text-color);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 3px 0 15px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }
        
        .sidebar-header {
            padding: 20px 15px;
            background-color: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed .logo-name {
            opacity: 0;
            width: 0;
        }
        
        #btn-toggle {
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            background: rgba(255,255,255,0.1);
            padding: 8px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        #btn-toggle:hover {
            background: var(--accent-color);
            transform: rotate(90deg);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            list-style: none;
            margin: 5px 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            border-left: 4px solid transparent;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: rgba(255,255,255,0.1);
            border-left-color: var(--hover-color);
            color: white;
        }
        
        .nav-link i {
            font-size: 1.3rem;
            min-width: 40px;
            text-align: center;
        }
        
        .link-text {
            margin-left: 10px;
            white-space: nowrap;
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .link-text {
            opacity: 0;
            width: 0;
        }
        
        .tooltip {
            position: absolute;
            left: calc(var(--sidebar-collapsed) + 10px);
            background: var(--primary-color);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1001;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .sidebar.collapsed .nav-link:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        .user-section {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-link {
            display: flex;
            align-items: center;
            color: var(--text-color);
            text-decoration: none;
            padding: 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .user-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .content-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            background: #f8f9fa;
        }
        
        .sidebar.collapsed ~ .content-wrapper {
            margin-left: var(--sidebar-collapsed);
        }
        
        .main-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        /* Estilos específicos para perfil */
        .profile-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .profile-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid rgba(255,255,255,0.3);
            margin-bottom: 20px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-color);
            margin: 0 auto 20px;
        }
        
        .profile-name {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .profile-role {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .profile-body {
            padding: 30px;
        }
        
        .info-section {
            margin-bottom: 30px;
        }
        
        .info-title {
            color: var(--primary-color);
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-label {
            font-weight: 600;
            min-width: 150px;
            color: #495057;
        }
        
        .info-value {
            color: #212529;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-top: 4px solid var(--accent-color);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: var(--sidebar-collapsed);
            }
            
            .sidebar:not(.collapsed) {
                width: var(--sidebar-width);
            }
            
            .content-wrapper {
                margin-left: var(--sidebar-collapsed);
            }
            
            .sidebar:not(.collapsed) ~ .content-wrapper {
                margin-left: var(--sidebar-width);
            }
            
            .info-item {
                flex-direction: column;
            }
            
            .info-label {
                min-width: auto;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <div class="logo-name">SISTEMA DE REPORTES</div>
                    <i class='bx bx-menu' id="btn-toggle"></i>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li class="nav-item">
                    <a href="main.php" class="nav-link">
                        <i class='bx bx-home-alt-2'></i>
                        <span class="link-text">Inicio</span>
                        <span class="tooltip">Inicio</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="registro.php" class="nav-link">
                        <i class='bx bx-file'></i>
                        <span class="link-text">Registro</span>
                        <span class="tooltip">Registro de Información</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="reportes.php" class="nav-link">
                        <i class='bx bx-pencil'></i>
                        <span class="link-text">Reportes</span>
                        <span class="tooltip">Generar Reportes</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="estadisticas.php" class="nav-link">
                        <i class='bx bx-chart'></i>
                        <span class="link-text">Estadísticas</span>
                        <span class="tooltip">Ver Estadísticas</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="updo.php" class="nav-link">
                        <i class='bx bx-folder'></i>
                        <span class="link-text">Archivos</span>
                        <span class="tooltip">Subir/Descargar Archivos</span>
                    </a>
                </li>
                
                <li class="nav-item my-4">
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 0 20px;">
                </li>
                
                <li class="nav-item">
                    <a href="configuracion.php" class="nav-link">
                        <i class='bx bx-cog'></i>
                        <span class="link-text">Configuración</span>
                        <span class="tooltip">Configuración del Sistema</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="perfil.php" class="nav-link active">
                        <i class='bx bx-user'></i>
                        <span class="link-text">Mi Perfil</span>
                        <span class="tooltip">Mi Perfil de Usuario</span>
                    </a>
                </li>
            </ul>
            
            <div class="user-section">
                <a href="logout.php" class="user-link">
                    <i class='bx bx-log-out-circle' style="font-size: 1.5rem;"></i>
                    <span class="link-text">Cerrar Sesión</span>
                    <span class="tooltip">Cerrar Sesión</span>
                </a>
            </div>
        </aside>
        
        <!-- Contenido principal -->
        <div class="content-wrapper">
            <header class="main-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mi Perfil</h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-3">CECyTE Santa Catarina N.L.</span>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class='bx bx-user-circle'></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="container-fluid py-4">
                <div class="profile-container">
                    <div class="profile-card">
                        <!-- Encabezado del perfil -->
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class='bx bx-user'></i>
                            </div>
                            <h2 class="profile-name"><?php echo htmlspecialchars($user_data['nombre']); ?></h2>
                            <p class="profile-role"><?php echo htmlspecialchars($user_data['rol']); ?></p>
                        </div>
                        
                        <!-- Cuerpo del perfil -->
                        <div class="profile-body">
                            <!-- Información personal -->
                            <div class="info-section">
                                <h4 class="info-title">
                                    <i class='bx bx-info-circle'></i>
                                    Información Personal
                                </h4>
                                
                                <div class="info-item">
                                    <span class="info-label">Nombre Completo:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user_data['nombre']); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Nombre de Usuario:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user_data['usuario']); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Correo Electrónico:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Teléfono:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user_data['telefono']); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Extensión:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user_data['extension']); ?></span>
                                </div>
                            </div>
                            
                            <!-- Información institucional -->
                            <div class="info-section">
                                <h4 class="info-title">
                                    <i class='bx bx-building'></i>
                                    Información Institucional
                                </h4>
                                
                                <div class="info-item">
                                    <span class="info-label">Plantel:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user_data['plantel']); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Departamento:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user_data['departamento']); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Rol:</span>
                                    <span class="info-value">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($user_data['rol']); ?></span>
                                    </span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Fecha de Registro:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user_data['fecha_registro']); ?></span>
                                </div>
                            </div>
                            
                            <!-- Estadísticas (ejemplo) -->
                            <div class="info-section">
                                <h4 class="info-title">
                                    <i class='bx bx-stats'></i>
                                    Estadísticas
                                </h4>
                                
                                <div class="stats-grid">
                                    <div class="stat-card">
                                        <div class="stat-number">24</div>
                                        <div class="stat-label">Reportes Creados</div>
                                    </div>
                                    
                                    <div class="stat-card">
                                        <div class="stat-number">18</div>
                                        <div class="stat-label">Reportes Completados</div>
                                    </div>
                                    
                                    <div class="stat-card">
                                        <div class="stat-number">6</div>
                                        <div class="stat-label">Reportes Pendientes</div>
                                    </div>
                                    
                                    <div class="stat-card">
                                        <div class="stat-number">92%</div>
                                        <div class="stat-label">Tasa de Finalización</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Acciones -->
                            <div class="info-section">
                                <h4 class="info-title">
                                    <i class='bx bx-cog'></i>
                                    Acciones
                                </h4>
                                
                                <div class="d-flex flex-wrap gap-3">
                                    <a href="editar_perfil.php" class="btn btn-primary">
                                        <i class='bx bx-edit'></i> Editar Perfil
                                    </a>
                                    
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#cambiarPasswordModal">
                                        <i class='bx bx-key'></i> Cambiar Contraseña
                                    </button>
                                    
                                    <a href="mis_reportes.php" class="btn btn-outline-success">
                                        <i class='bx bx-file'></i> Ver Mis Reportes
                                    </a>
                                    
                                    <a href="exportar_datos.php" class="btn btn-outline-info">
                                        <i class='bx bx-download'></i> Exportar Mis Datos
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- Footer -->
            <footer class="bg-success text-white text-center py-3 mt-4">
                <div class="container">
                    <p class="mb-1">CECyTE SANTA CATARINA N.L.</p>
                    <p class="mb-0">© <?php echo date("Y"); ?> Sistema de Reportes. Todos los derechos reservados.</p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Modal para cambiar contraseña -->
    <div class="modal fade" id="cambiarPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="cambiarPasswordForm">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Contraseña Actual</label>
                            <input type="password" class="form-control" id="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="newPassword" required>
                            <div class="form-text">Mínimo 8 caracteres, incluir mayúsculas, minúsculas y números.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="guardarPassword">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Toggle del sidebar
        document.getElementById('btn-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            
            // Cambiar icono
            const icon = this;
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.remove('bx-menu');
                icon.classList.add('bx-menu-alt-right');
            } else {
                icon.classList.remove('bx-menu-alt-right');
                icon.classList.add('bx-menu');
            }
        });
        
        // Validación para cambiar contraseña
        document.getElementById('guardarPassword').addEventListener('click', function() {
            const currentPass = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            
            if (!currentPass || !newPass || !confirmPass) {
                alert('Por favor, complete todos los campos.');
                return;
            }
            
            if (newPass !== confirmPass) {
                alert('Las nuevas contraseñas no coinciden.');
                return;
            }
            
            if (newPass.length < 8) {
                alert('La nueva contraseña debe tener al menos 8 caracteres.');
                return;
            }
            
            // Aquí iría la llamada AJAX para cambiar la contraseña
            // Por ahora solo mostramos un mensaje de éxito
            alert('Contraseña cambiada exitosamente.');
            document.getElementById('cambiarPasswordForm').reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('cambiarPasswordModal'));
            modal.hide();
        });
    </script>
</body>
</html>