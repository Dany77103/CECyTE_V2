<?php
// acerca_de.php
$pageTitle = "Acerca de CECyTE NL";
$currentPage = "acerca_de";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - CECyTE Nuevo León</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Boxicons CSS -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Estilos personalizados -->
    <style>
        :root {
            --verde-principal: #1e5631;
            --verde-secundario: #2a7c3e;
            --verde-acento: #4c9c2e;
            --verde-claro: #76b041;
            --verde-suave: #e8f5e9;
            --gris-oscuro: #333333;
            --gris-claro: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--gris-oscuro);
            background-color: #f9f9f9;
            line-height: 1.6;
        }
        
        /* Estilos para el encabezado */
        .site-header {
            background: linear-gradient(to right, var(--verde-principal), var(--verde-secundario));
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .site-title {
            font-weight: 700;
            font-size: 1.8rem;
            margin: 0;
        }
        
        .site-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
        }
        
        /* Navegación */
        .navbar-custom {
            background-color: white;
            border-bottom: 3px solid var(--verde-acento);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .nav-link {
            color: var(--verde-principal) !important;
            font-weight: 500;
            padding: 0.5rem 1.2rem !important;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--verde-acento) !important;
            background-color: var(--verde-suave);
            border-radius: 4px;
        }
        
        /* Contenedor principal */
        .main-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        
        /* Historia Container */
        .historia-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .historia-header {
            border-bottom: 3px solid var(--verde-acento);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .historia-title {
            color: var(--verde-principal);
            font-weight: 700;
            position: relative;
            padding-left: 15px;
        }
        
        .historia-title:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
            background-color: var(--verde-claro);
            border-radius: 3px;
        }
        
        .historia-content p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            text-align: justify;
        }
        
        /* Tarjetas informativas */
        .card-verde {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
        .card-verde:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }
        
        .card-verde .card-body {
            padding: 2rem 1.5rem;
        }
        
        .card-verde .card-title {
            font-weight: 700;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .card-verde .card-title i {
            font-size: 1.8rem;
        }
        
        .text-verde-principal {
            color: var(--verde-principal) !important;
        }
        
        .text-verde-secundario {
            color: var(--verde-secundario) !important;
        }
        
        .text-verde-acento {
            color: var(--verde-acento) !important;
        }
        
        .text-verde-claro {
            color: var(--verde-claro) !important;
        }
        
        /* Pie de página */
        .site-footer {
            background-color: var(--verde-principal);
            color: white;
            padding: 2.5rem 0;
            margin-top: 3rem;
        }
        
        .footer-title {
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--verde-claro);
        }
        
        .footer-links a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s ease;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .footer-links a:hover {
            color: var(--verde-claro);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
            margin-top: 2rem;
            text-align: center;
            font-size: 0.9rem;
            color: #ccc;
        }
        
        /* Responsividad */
        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem;
            }
            
            .historia-title {
                font-size: 1.5rem;
            }
            
            .card-verde .card-body {
                padding: 1.5rem 1rem;
            }
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .historia-container, .card-verde {
            animation: fadeIn 0.8s ease-out;
        }
        
        /* Estilo para los párrafos importantes */
        .historia-content p:first-of-type {
            font-size: 1.15rem;
            background-color: var(--verde-suave);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--verde-acento);
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <header class="site-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="site-title">Colegio de Estudios Científicos y Tecnológicos del Estado de Nuevo León</h1>
                    <p class="site-subtitle">Educación Media Superior Tecnológica de Calidad</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <img src="https://cecyte.nl.gob.mx/wp-content/uploads/2024/03/logo_cecyte_2024.png" alt="Logo CECyTE NL" style="max-height: 80px;" class="img-fluid">
                </div>
            </div>
        </div>
    </header>
    
    <!-- Navegación -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="acerca_de.php">Acerca de</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="oferta_educativa.php">Oferta Educativa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="planteles.php">Planteles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contacto.php">Contacto</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="https://cecyte.nl.gob.mx/" target="_blank" class="btn btn-success btn-sm">Sitio Oficial</a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Contenido principal -->
    <main class="container">
        <div class="main-content">
            <div class="historia-container">
                <div class="historia-header">
                    <h2 class="historia-title">Reseña Histórica - CECyTE Nuevo León</h2>
                </div>
                <div class="historia-content">
                    <p>
                        El Colegio de Estudios Científicos y Tecnológicos del Estado de Nuevo León (CECyTE NL) se creó el 18 de agosto de 1993, a través de un acuerdo de colaboración entre la secretaría de educación pública del gobierno federal, en representación del Dr. Ernesto Zedillo Ponce de León, y el Gobierno de Nuevo León, en representación del Lic. Sócrates Uauhtémoc Rizzo García, presidente del Tribunal Constitucional.
                    </p>
                    <p>
                        Este acuerdo se ratificó mediante el decreto de creación 287, emitido el 11 de mayo de 1994, y modificado con el decreto 340 el 19 de mayo de 2003. En sus comienzos, el CECyTE NL se fundó como una nueva alternativa de educación de nivel medio superior en la región, proporcionando servicios en cuatro establecimientos situados en los municipios de Apodaca, García, Linares y Marín.
                    </p>
                    <p>
                        El programa educativo local contemplaba tres profesiones técnicas: administración, electrónica y programación. El Colegio implementó el bachillerato general conocido como Educación Media Superior a Distancia (EMSAD), comenzando con un establecimiento en el municipio de Lampazos de Naranjo, N.L.
                    </p>
                    <p>
                        En la actualidad, el CECyTE NL dispone de 17 establecimientos que imparten el Bachillerato Tecnológico con 20 áreas técnicas autorizadas, junto con 17 Centros EMSAD que ofrecen educación remota. El Colegio se ha enfocado en potencializar sus índices estadísticos fundamentales, tales como la eficiencia para terminal con el abandono escolar y la reprobación, además de reforzar la formación de los profesores, el trabajo en equipo, la educación dual, la electromovilidad, la inclusión, el crecimiento socioemocional y la salud integral de la comunidad educativa.
                    </p>
                </div>
                
                <!-- Tarjetas informativas con los colores de la paleta -->
                <div class="row mt-5">
                    <div class="col-md-3 mb-4">
                        <div class="card card-verde h-100 text-center shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title text-verde-principal">
                                    <i class='bx bxs-graduation'></i> Misión
                                </h5>
                                <p class="card-text">Formar técnicos profesionales a través de un bachillerato tecnológico de calidad, con valores éticos, responsabilidad social y competencias para la vida y el trabajo.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card card-verde h-100 text-center shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title text-verde-secundario">
                                    <i class='bx bxs-bulb'></i> Visión
                                </h5>
                                <p class="card-text">Ser la mejor opción de educación media superior tecnológica en Nuevo León, reconocida por su excelencia académica, innovación educativa y vinculación con el sector productivo.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card card-verde h-100 text-center shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title text-verde-acento">
                                    <i class='bx bxs-star'></i> Valores
                                </h5>
                                <p class="card-text">Excelencia, responsabilidad, honestidad, respeto, trabajo en equipo, inclusión, innovación y compromiso con el desarrollo sostenible.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card card-verde h-100 text-center shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title text-verde-claro">
                                    <i class='bx bxs-compass'></i> Compromiso
                                </h5>
                                <p class="card-text">Educación integral para el desarrollo de competencias profesionales y personales que permitan a nuestros egresados insertarse exitosamente en la sociedad.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información adicional -->
                <div class="row mt-5 pt-4 border-top">
                    <div class="col-md-6">
                        <h4 class="text-verde-principal mb-3">
                            <i class='bx bxs-building'></i> Nuestros Planteles
                        </h4>
                        <p>Contamos con 17 planteles distribuidos estratégicamente en el estado de Nuevo León, atendiendo a más de 15,000 estudiantes anualmente en modalidades escolarizada y no escolarizada.</p>
                        <ul>
                            <li>Bachillerato Tecnológico en 20 especialidades</li>
                            <li>17 Centros EMSAD (Educación a Distancia)</li>
                            <li>Programas de vinculación con el sector productivo</li>
                            <li>Educación dual en colaboración con empresas</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h4 class="text-verde-secundario mb-3">
                            <i class='bx bxs-trophy'></i> Logros y Reconocimientos
                        </h4>
                        <p>El CECyTE NL ha sido reconocido por su calidad educativa y contribución al desarrollo del estado:</p>
                        <ul>
                            <li>Certificaciones en competencias laborales</li>
                            <li>Participación en concursos nacionales e internacionales</li>
                            <li>Programas de intercambio académico</li>
                            <li>Vinculación con más de 200 empresas locales</li>
                            <li>Implementación de tecnologías educativas innovadoras</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Pie de página -->
    <footer class="site-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title">CECyTE Nuevo León</h5>
                    <p>Formando técnicos profesionales desde 1993, con calidad educativa y compromiso social.</p>
                    <p><i class='bx bxs-map'></i> Av. Universidad, Col. Ciudad Universitaria, San Nicolás de los Garza, N.L.</p>
                    <p><i class='bx bxs-phone'></i> Tel: (81) 2020-5050</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title">Enlaces Rápidos</h5>
                    <div class="footer-links">
                        <a href="index.php">Inicio</a>
                        <a href="acerca_de.php">Acerca de Nosotros</a>
                        <a href="oferta_educativa.php">Oferta Educativa</a>
                        <a href="planteles.php">Planteles</a>
                        <a href="contacto.php">Contacto</a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title">Síguenos</h5>
                    <div class="footer-links">
                        <a href="https://facebook.com/CECyTENL" target="_blank"><i class='bx bxl-facebook-circle'></i> Facebook</a>
                        <a href="https://twitter.com/CECyTENL" target="_blank"><i class='bx bxl-twitter'></i> Twitter</a>
                        <a href="https://instagram.com/CECyTENL" target="_blank"><i class='bx bxl-instagram'></i> Instagram</a>
                        <a href="https://youtube.com/CECyTENL" target="_blank"><i class='bx bxl-youtube'></i> YouTube</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Colegio de Estudios Científicos y Tecnológicos del Estado de Nuevo León. Todos los derechos reservados.</p>
                <p>Este sitio es una demostración de desarrollo web con fines educativos.</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS Bundle con Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script>
        // Agregar clase active al elemento de navegación actual
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = '<?php echo $currentPage; ?>';
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage + '.php') {
                    link.classList.add('active');
                }
            });
            
            // Efecto de desplazamiento suave para enlaces internos
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if(targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if(targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>