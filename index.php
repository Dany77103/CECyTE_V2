<?php
// Agrega esto al PRINCIPIO del archivo index.php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CECYTE - Iniciar Sesión</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* PALETA DE 4 TONOS VERDE - INSPIRADA EN CECYTE */
            --verde-oscuro: #1a5330;      /* Verde más oscuro */
            --verde-principal: #2e7d32;   /* Verde principal */
            --verde-medio: #4caf50;       /* Verde medio */
            --verde-claro: #8bc34a;       /* Verde claro */
            --verde-brillante: #81c784;   /* Verde brillante para acentos */
        }
        
        body {
            background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header personalizado */
        .main-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(46, 125, 50, 0.1);
            border-bottom: 3px solid var(--verde-medio);
        }
        
        /* Contenedor principal del login */
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: url('img/mascota1.png') no-repeat left 50px center;
            background-size: 400px auto;
            min-height: calc(100vh - 200px);
        }
        
        @media (max-width: 1200px) {
            .login-container {
                background-size: 300px auto;
            }
        }
        
        @media (max-width: 992px) {
            .login-container {
                background-image: none;
            }
        }
        
        /* Card de login */
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(46, 125, 50, 0.15);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            border-top: 5px solid var(--verde-oscuro);
            background: white;
        }
        
        .login-header {
            background: linear-gradient(90deg, var(--verde-oscuro), var(--verde-principal));
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .login-body {
            padding: 30px;
        }
        
        /* Estilos para el formulario */
        .form-label {
            font-weight: 600;
            color: var(--verde-oscuro);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--verde-medio);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }
        
        .input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--verde-medio);
            cursor: pointer;
            z-index: 10;
        }
        
        /* Botón personalizado */
        .btn-login {
            background: linear-gradient(90deg, var(--verde-oscuro), var(--verde-principal));
            border: none;
            color: white;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
            background: linear-gradient(90deg, #144028, #256028);
        }
        
        /* Mensajes de error */
        .alert-error {
            background: linear-gradient(90deg, #ff5252, #ff8a80);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        /* Info box */
        .info-box {
            background: #f1f8e9;
            border-left: 4px solid var(--verde-claro);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .info-box h5 {
            color: var(--verde-oscuro);
            margin-bottom: 5px;
            font-size: 1rem;
        }
        
        .info-box p {
            color: #5d6d5f;
            font-size: 0.9rem;
            margin: 0;
        }
        
        /* Welcome section */
        .welcome-section {
            max-width: 600px;
            margin-right: 50px;
        }
        
        @media (max-width: 992px) {
            .welcome-section {
                margin-right: 0;
                margin-bottom: 40px;
                text-align: center;
            }
        }
        
        .welcome-title {
            color: var(--verde-oscuro);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .welcome-subtitle {
            color: var(--verde-principal);
            font-size: 1.3rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .welcome-text {
            color: #5d6d5f;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
        }
        
        .features-list li {
            padding: 10px 0;
            color: var(--verde-oscuro);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .features-list li i {
            color: var(--verde-medio);
            font-size: 1.2rem;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(90deg, var(--verde-oscuro), var(--verde-principal));
            color: white;
            padding: 25px 0;
            text-align: center;
            margin-top: auto;
        }
        
        .footer-logo {
            height: 50px;
            margin-bottom: 15px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                padding: 20px;
                flex-direction: column;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .login-card {
                margin-top: 20px;
            }
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-card {
            animation: fadeIn 0.6s ease-out;
        }
        
        .welcome-section {
            animation: fadeIn 0.8s ease-out;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="img/logo_cecyte.jpg" alt="CECyTE Logo" height="50" class="me-3">
                    <div>
                        <h5 class="mb-0 text-success">CECyTE Santa Catarina N.L.</h5>
                        <small class="text-muted">Sistema de Gestión Académica</small>
                    </div>
                </div>
                <div>
                    <span class="badge bg-success">Versión 2.0</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="login-container">
        <div class="container">
            <div class="row align-items-center justify-content-end">
                <!-- Welcome Section -->
                <div class="col-lg-8 welcome-section">
                    <h1 class="welcome-title">
                        Sistema de Gestión Académica<br>
                        <span style="color: var(--verde-principal);">CECyTE Santa Catarina</span>
                    </h1>
                    
                    <h3 class="welcome-subtitle">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Gestión Académica Integral
                    </h3>
                    
                    <p class="welcome-text">
                        Sistema especializado para la administración de alumnos, maestros, calificaciones, 
                        horarios y toda la información académica del plantel.
                    </p>
                    
                    <ul class="features-list">
                        <li><i class="fas fa-check-circle"></i> Registro completo de alumnos y maestros</li>
                        <li><i class="fas fa-check-circle"></i> Control de calificaciones y asistencias</li>
                        <li><i class="fas fa-check-circle"></i> Generación de reportes detallados</li>
                        <li><i class="fas fa-check-circle"></i> Sistema de QR para asistencias</li>
                        <li><i class="fas fa-check-circle"></i> Gestión de horarios y grupos</li>
                    </ul>
                    
                    <div class="info-box">
                        <h5><i class="fas fa-info-circle me-2"></i>Acceso al Sistema</h5>
                        <p>Utiliza tus credenciales proporcionadas por el administrador para acceder al sistema.</p>
                    </div>
                </div>
                
                <!-- Login Card -->
                <div class="col-lg-4">
                    <div class="login-card">
                        <div class="login-header">
                            <h1><i class="fas fa-lock me-2"></i>Iniciar Sesión</h1>
                            <p>Ingresa tus credenciales para acceder al sistema</p>
                        </div>
                        
                        <div class="login-body">
                            <?php
                            // Mostrar mensaje de error si existe
                            if (isset($_SESSION['error'])) {
                                echo '<div class="alert-error">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    ' . htmlspecialchars($_SESSION['error']) . '
                                </div>';
                                unset($_SESSION['error']);
                            }
                            
                            if (isset($_GET['error'])) {
                                echo '<div class="alert-error">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    Usuario o contraseña incorrectos.
                                </div>';
                            }
                            
                            if (isset($_GET['logout'])) {
                                echo '<div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Sesión cerrada exitosamente.
                                </div>';
                            }
                            ?>
                            
                            <form action="login.php" method="post" id="loginForm">
                                <div class="mb-4">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user"></i> Usuario
                                    </label>
                                    <input type="text" 
                                           id="username" 
                                           name="username" 
                                           class="form-control" 
                                           required 
                                           placeholder="Ingresa tu nombre de usuario"
                                           autocomplete="username">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-key"></i> Contraseña
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               id="password" 
                                               name="password" 
                                               class="form-control" 
                                               required 
                                               placeholder="Ingresa tu contraseña"
                                               autocomplete="current-password">
                                        <button type="button" class="password-toggle" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="eyeIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn-login">
                                    <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
                                </button>
                                
                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Tu información está protegida
                                    </small>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            ¿Problemas para acceder? Contacta al administrador del sistema
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4 text-md-start">
                    <img src="img/logo_cecyte.jpg" alt="CECyTE Logo" class="footer-logo">
                </div>
                <div class="col-md-4">
                    <h5>CECyTE Santa Catarina N.L.</h5>
                    <p>Sistema de Gestión Académica</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-phone me-2"></i> (81) 1234-5678<br>
                        <i class="fas fa-envelope me-2"></i> contacto@cecyte.edu.mx
                    </p>
                </div>
            </div>
            <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
            <div class="row">
                <div class="col-12">
                    <p class="mb-0">
                        © <?php echo date("Y"); ?> Colegio de Estudios Científicos y Tecnológicos del Estado de Nuevo León.
                        Todos los derechos reservados.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Toggle para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
        
        // Validación del formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Por favor, completa todos los campos requeridos.');
                return false;
            }
            
            // Mostrar loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Verificando...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });
        
        // Efecto de carga
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s ease';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
            
            // Focus en el primer campo
            document.getElementById('username').focus();
        });
        
        // Efecto hover en botón
        const loginBtn = document.querySelector('.btn-login');
        loginBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        loginBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
        
        // Detectar tecla Enter
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.target.matches('button')) {
                const form = document.getElementById('loginForm');
                if (form.checkValidity()) {
                    form.querySelector('button[type="submit"]').click();
                }
            }
        });
    </script>
</body>
</html>