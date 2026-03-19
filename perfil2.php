<?php
session_start();

// Verificar si el usuario ha iniciado sesi�n
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Simulaci�n de datos del usuario
$user_data = [
    'nombre' => $_SESSION['nombre'] ?? 'Juan P�rez',
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
    
    <style>
        :root {
            --verde-oscuro-1: #1a5330;   /* Textos importantes y badges */
            --verde-oscuro-2: #2e7d32;   /* Botones principales */
            --verde-medio: #4caf50;      /* Acentos y fondos */
            --verde-claro: #8bc34a;      /* Bordes y detalles */
            --verde-muy-claro: #c8e6c9;  /* Fondos y hover */
            --texto-oscuro: #2c3e50;
            --texto-claro: #6c757d;
            --fondo-claro: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, var(--verde-muy-claro) 0%, #f8f9fa 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Navbar superior */
        .main-navbar {
            background: var(--verde-oscuro-1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 12px 0;
        }
        
        .nav-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1.2rem;
            text-decoration: none;
        }
        
        .nav-brand i {
            font-size: 1.5rem;
            margin-right: 8px;
        }
        
        .nav-link-custom {
            color: rgba(255,255,255,0.9) !important;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s ease;
            margin: 0 5px;
        }
        
        .nav-link-custom:hover {
            background: rgba(255,255,255,0.1);
            color: white !important;
        }
        
        .nav-link-custom.active {
            background: var(--verde-medio);
            color: white !important;
        }
        
        .user-dropdown .dropdown-toggle {
            color: white;
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 50px;
            padding: 8px 16px;
        }
        
        .user-dropdown .dropdown-toggle:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* Estilos para perfil */
        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid var(--verde-claro);
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--verde-oscuro-1), var(--verde-oscuro-2));
            color: white;
            padding: 50px 30px 40px;
            text-align: center;
            position: relative;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-medio));
        }
        
        .profile-avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 5px solid rgba(255,255,255,0.3);
            margin-bottom: 25px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: var(--verde-oscuro-1);
            margin: 0 auto 25px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .profile-name {
            font-size: 2rem;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .profile-role {
            opacity: 0.95;
            font-size: 1.2rem;
            background: rgba(255,255,255,0.15);
            padding: 8px 20px;
            border-radius: 50px;
            display: inline-block;
        }
        
        .profile-body {
            padding: 40px;
        }
        
        .info-section {
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 2px solid var(--verde-muy-claro);
        }
        
        .info-section:last-child {
            border-bottom: none;
        }
        
        .info-title {
            color: var(--verde-oscuro-1);
            border-bottom: 2px solid var(--verde-claro);
            padding-bottom: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .info-title i {
            font-size: 1.4rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            background: var(--verde-muy-claro);
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid var(--verde-claro);
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left-color: var(--verde-medio);
        }
        
        .info-label {
            font-weight: 600;
            color: var(--verde-oscuro-1);
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .info-value {
            color: var(--texto-oscuro);
            font-size: 1.1rem;
        }
        
        .badge-verde {
            background-color: var(--verde-medio);
            color: white;
            font-weight: 500;
            padding: 6px 15px;
            border-radius: 50px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 2px solid var(--verde-muy-claro);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            border-color: var(--verde-claro);
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--verde-oscuro-2);
            margin-bottom: 10px;
            line-height: 1;
        }
        
        .stat-label {
            color: var(--texto-claro);
            font-size: 0.95rem;
        }
        
        /* Botones con la paleta de colores */
        .btn-verde-primario {
            background-color: var(--verde-oscuro-2);
            border-color: var(--verde-oscuro-2);
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-verde-primario:hover {
            background-color: var(--verde-oscuro-1);
            border-color: var(--verde-oscuro-1);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 83, 48, 0.2);
        }
        
        .btn-verde-outline {
            background-color: white;
            border: 2px solid var(--verde-medio);
            color: var(--verde-medio);
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-verde-outline:hover {
            background-color: var(--verde-muy-claro);
            border-color: var(--verde-oscuro-2);
            color: var(--verde-oscuro-2);
            transform: translateY(-2px);
        }
        
        .btn-verde-claro {
            background-color: var(--verde-claro);
            border-color: var(--verde-claro);
            color: var(--verde-oscuro-1);
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-verde-claro:hover {
            background-color: var(--verde-medio);
            border-color: var(--verde-medio);
            color: white;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            border: 2px solid var(--verde-muy-claro);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--texto-oscuro);
        }
        
        .action-card:hover {
            border-color: var(--verde-claro);
            transform: translateY(-5px);
            color: var(--verde-oscuro-1);
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        
        .action-card i {
            font-size: 2.5rem;
            color: var(--verde-medio);
            margin-bottom: 15px;
            display: block;
        }
        
        .action-card h5 {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .action-card p {
            color: var(--texto-claro);
            font-size: 0.9rem;
            margin: 0;
        }
        
        /* Footer */
        .main-footer {
            background: var(--verde-oscuro-1);
            color: white;
            padding: 25px 0;
            margin-top: 60px;
        }
        
        .footer-brand {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0 15px;
        }
        
        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .profile-header {
                padding: 40px 20px 30px;
            }
            
            .profile-body {
                padding: 25px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-link-custom {
                margin: 5px 0;
                display: block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar superior -->
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container">
            <a class="navbar-brand nav-brand" href="main.php">
                <i class='bx bx-line-chart'></i>
                Sistema de Reportes
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon" style="color: white;">?</span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="main.php">
                            <i class='bx bx-home-alt-2'></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="registro.php">
                            <i class='bx bx-file'></i> Registro
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="reportes.php">
                            <i class='bx bx-pencil'></i> Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="estadisticas.php">
                            <i class='bx bx-chart'></i> Estad&iacute;sticas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="updo.php">
                            <i class='bx bx-folder'></i> Archivos
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <div class="nav-item dropdown user-dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <i class='bx bx-user-circle me-2' style="font-size: 1.5rem;"></i>
                            <span><?php echo htmlspecialchars($user_data['usuario']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="perfil.php"><i class='bx bx-user'></i> Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="configuracion.php"><i class='bx bx-cog'></i> Configuraci&oacute;n</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class='bx bx-log-out'></i> Cerrar Sesi&oacute;n</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <main class="container-fluid">
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
                    <!-- Informaci�n personal -->
                    <div class="info-section">
                        <h4 class="info-title">
                            <i class='bx bx-info-circle'></i>
                            Informaci&oacute;n Personal
                        </h4>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Nombre Completo</div>
                                <div class="info-value"><?php echo htmlspecialchars($user_data['nombre']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Nombre de Usuario</div>
                                <div class="info-value"><?php echo htmlspecialchars($user_data['usuario']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Correo Electr&oacute;nico</div>
                                <div class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Tel&eacute;fono</div>
                                <div class="info-value"><?php echo htmlspecialchars($user_data['telefono']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Extensi&oacute;n</div>
                                <div class="info-value"><?php echo htmlspecialchars($user_data['extension']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informaci�n institucional -->
                    <div class="info-section">
                        <h4 class="info-title">
                            <i class='bx bx-building'></i>
                            Informaci&oacute;n Institucional
                        </h4>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Plantel</div>
                                <div class="info-value"><?php echo htmlspecialchars($user_data['plantel']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Departamento</div>
                                <div class="info-value"><?php echo htmlspecialchars($user_data['departamento']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Rol</div>
                                <div class="info-value">
                                    <span class="badge-verde"><?php echo htmlspecialchars($user_data['rol']); ?></span>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Fecha de Registro</div>
                                <div class="info-value"><?php echo htmlspecialchars($user_data['fecha_registro']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estad�sticas -->
                    <div class="info-section">
                        <h4 class="info-title">
                            <i class='bx bx-stats'></i>
                            Estad&iacute;sticas
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
                                <div class="stat-label">Tasa de Finalizaci&oacute;n</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="info-section">
                        <h4 class="info-title">
                            <i class='bx bx-cog'></i>
                            Acciones
                        </h4>
                        
                        <div class="actions-grid">
                            <a href="editar_perfil.php" class="action-card">
                                <i class='bx bx-edit-alt'></i>
                                <h5>Editar Perfil</h5>
                                <p>Actualiza tu informaci&oacute;n personal</p>
                            </a>
                            
                            <a href="#" class="action-card" data-bs-toggle="modal" data-bs-target="#cambiarPasswordModal">
                                <i class='bx bx-key'></i>
                                <h5>Cambiar Contrase&ntilde;a</h5>
                                <p>Actualiza tu contrase&ntilde;a de acceso</p>
                            </a>
                            
                            <a href="mis_reportes.php" class="action-card">
                                <i class='bx bx-file'></i>
                                <h5>Mis Reportes</h5>
                                <p>Consulta tus reportes creados</p>
                            </a>
                            
                            <a href="exportar_datos.php" class="action-card">
                                <i class='bx bx-download'></i>
                                <h5>Exportar Datos</h5>
                                <p>Descarga tu informaci&oacute;n</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <a href="main.php" class="footer-brand">
                        <i class='bx bx-line-chart'></i> Sistema de Reportes
                    </a>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <div class="footer-links">
                        <a href="main.php">Inicio</a>
                        <a href="configuracion.php">Configuraci&oacute;n</a>
                        <a href="ayuda.php">Ayuda</a>
                        <a href="contacto.php">Contacto</a>
                    </div>
                    <p class="mt-2 mb-0" style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                        � <?php echo date("Y"); ?> CECyTE Santa Catarina N.L.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal para cambiar contrase�a -->
    <div class="modal fade" id="cambiarPasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--verde-oscuro-1); color: white;">
                    <h5 class="modal-title"><i class='bx bx-key me-2'></i>Cambiar Contrase&ntilde;a</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="cambiarPasswordForm">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Contrase&ntilde;a Actual</label>
                            <input type="password" class="form-control" id="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Nueva Contrase&ntilde;a</label>
                            <input type="password" class="form-control" id="newPassword" required>
                            <div class="form-text" style="color: var(--verde-medio);">M&iacute;nimo 8 caracteres, incluir may&uacute;sculas, min&uacute;sculas y n&uacute;meros.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirmar Nueva Contrase&ntilde;a</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-verde-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-verde-primario" id="guardarPassword">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Validaci�n para cambiar contrase�a
        document.getElementById('guardarPassword').addEventListener('click', function() {
            const currentPass = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            
            if (!currentPass || !newPass || !confirmPass) {
                alert('Por favor, complete todos los campos.');
                return;
            }
            
            if (newPass !== confirmPass) {
                alert('Las nuevas contrase�as no coinciden.');
                return;
            }
            
            if (newPass.length < 8) {
                alert('La nueva contrase�a debe tener al menos 8 caracteres.');
                return;
            }
            
            // Validaci�n de seguridad b�sica
            const hasUpperCase = /[A-Z]/.test(newPass);
            const hasLowerCase = /[a-z]/.test(newPass);
            const hasNumbers = /\d/.test(newPass);
            
            if (!hasUpperCase || !hasLowerCase || !hasNumbers) {
                alert('La contrase�a debe incluir may�sculas, min�sculas y n�meros.');
                return;
            }
            
            // Aqu� ir�a la llamada AJAX para cambiar la contrase�a
            // Por ahora solo mostramos un mensaje de �xito
            alert('Contrase�a cambiada exitosamente.');
            document.getElementById('cambiarPasswordForm').reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('cambiarPasswordModal'));
            modal.hide();
        });
        
        // Efecto de animaci�n en las tarjetas de estad�sticas al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>