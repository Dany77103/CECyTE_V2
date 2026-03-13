<?php
// fotoalu.php - Reporte de Fotos de Alumnos
session_start();
include 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Consulta para obtener alumnos con sus datos
$sql = "SELECT 
            a.id_alumno,
            a.matriculaAlumno,
            a.nombre,
            a.apellidoPaterno,
            a.apellidoMaterno,
            a.rutaImagen,
            a.telefonoEmergencia,
            a.mailInstitucional,
            h.id_grupo,
            g.grupo,
            e.tipoEstatus,
            d.tipoDiscapacidad,
            es.estado_Nacimiento,
            gen.genero
        FROM alumnos a
        LEFT JOIN historialacademicoalumnos h ON a.matriculaAlumno = h.matriculaAlumno
        LEFT JOIN grupos g ON h.id_grupo = g.id_grupo
        LEFT JOIN estatus e ON h.id_estatus = e.id_estatus
        LEFT JOIN discapacidades d ON a.id_discapacidad = d.id_discapacidad
        LEFT JOIN estadonacimiento es ON a.id_estadoNacimiento = es.id_estadoNacimiento
        LEFT JOIN generos gen ON a.id_genero = gen.id_genero
        WHERE a.nombre LIKE ? 
           OR a.apellidoPaterno LIKE ? 
           OR a.apellidoMaterno LIKE ? 
           OR a.matriculaAlumno LIKE ?
        GROUP BY a.id_alumno
        ORDER BY a.apellidoPaterno, a.apellidoMaterno, a.nombre";

$stmt = $con->prepare($sql);
$searchTerm = '%' . $search . '%';
$stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$alumnos = [];
while ($row = $result->fetch_assoc()) {
    $alumnos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CECyTE SC - Reporte de Fotos de Alumnos</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
    <style>
        /* Estilos con cuatro colores verdes */
        :root {
            --verde-oscuro: #1a472a;
            --verde-medio: #2e7d32;
            --verde-claro: #4caf50;
            --verde-suave: #c8e6c9;
            --blanco: #ffffff;
            --gris-claro: #f5f5f5;
            --texto-oscuro: #333333;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--gris-claro);
            color: var(--texto-oscuro);
            line-height: 1.6;
        }
        
        /* Barra de Menú */
        .navbar {
            background: linear-gradient(135deg, var(--verde-oscuro) 0%, var(--verde-medio) 100%);
            padding: 15px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .menu-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .logo-img {
            height: 60px;
            transition: transform 0.3s ease;
        }
        
        .logo-img:hover {
            transform: scale(1.05);
        }
        
        .nav {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 10px 15px;
        }
        
        .btn a {
            color: var(--blanco);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .btn a:hover {
            background-color: var(--verde-claro);
            color: var(--verde-oscuro);
            transform: translateY(-2px);
        }
        
        /* Contenido Principal */
        .contai {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        h1 {
            color: var(--verde-oscuro);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--verde-medio);
            font-size: 2.2rem;
        }
        
        /* Buscador */
        .search-container {
            background: var(--blanco);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border-left: 5px solid var(--verde-claro);
        }
        
        .search-container form {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .search-container input[type="text"] {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid var(--verde-suave);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .search-container input[type="text"]:focus {
            outline: none;
            border-color: var(--verde-claro);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }
        
        .search-container button {
            background: linear-gradient(to right, var(--verde-medio), var(--verde-claro));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .search-container button:hover {
            background: linear-gradient(to right, var(--verde-claro), var(--verde-medio));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }
        
        /* Contenedor de Alumnos */
        .alumnos-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .alumno {
            background: var(--blanco);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            border-top: 4px solid;
        }
        
        /* 4 colores diferentes para las tarjetas */
        .alumno:nth-child(4n+1) {
            border-top-color: var(--verde-oscuro);
        }
        
        .alumno:nth-child(4n+2) {
            border-top-color: var(--verde-medio);
        }
        
        .alumno:nth-child(4n+3) {
            border-top-color: var(--verde-claro);
        }
        
        .alumno:nth-child(4n+4) {
            border-top-color: #388e3c; /* Un verde intermedio */
        }
        
        .alumno:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }
        
        .alumno img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 3px solid var(--verde-suave);
        }
        
        .alumno-info {
            padding: 20px;
        }
        
        .alumno p {
            margin-bottom: 8px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
        }
        
        .alumno strong {
            color: var(--verde-oscuro);
            min-width: 120px;
        }
        
        .alumno span {
            text-align: right;
            flex: 1;
        }
        
        .alumno-estatus {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--verde-medio);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Sin resultados */
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            background: var(--blanco);
            border-radius: 10px;
            color: var(--verde-oscuro);
            font-size: 18px;
            border: 2px dashed var(--verde-suave);
        }
        
        /* Botones de acción */
        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .btn-agregar, .btn-editar1 {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 150px;
        }
        
        .btn-agregar {
            background: linear-gradient(to right, var(--verde-oscuro), var(--verde-medio));
            color: white;
            border: none;
        }
        
        .btn-agregar:hover {
            background: linear-gradient(to right, var(--verde-medio), var(--verde-oscuro));
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(26, 71, 42, 0.3);
        }
        
        .btn-editar1 {
            background: linear-gradient(to right, var(--verde-claro), #388e3c);
            color: white;
            border: none;
        }
        
        .btn-editar1:hover {
            background: linear-gradient(to right, #388e3c, var(--verde-claro));
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.3);
        }
        
        /* Pie de página */
        .footer {
            background: linear-gradient(135deg, var(--verde-oscuro) 0%, var(--verde-medio) 100%);
            color: var(--blanco);
            padding: 30px 0;
            margin-top: 50px;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
        }
        
        .logo-img1 {
            height: 50px;
        }
        
        .footer-linea {
            width: 2px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.3);
            margin: 0 20px;
        }
        
        .footer-info {
            flex: 1;
            text-align: center;
        }
        
        .footer-year {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .alumnos-container {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
            
            .search-container form {
                flex-direction: column;
            }
            
            .search-container input[type="text"],
            .search-container button {
                width: 100%;
            }
            
            .button-container {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-agregar, .btn-editar1 {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Barra de Menú -->
    <nav class="navbar">
        <div class="menu-container">
            <a href="index.php" class="logo">
                <img src="img/logo.png" alt="Logo CECyTE" class="logo-img">
            </a>
            <div class="nav">
                <div class="btn"><a href="inicio.php">Inicio</a></div>
                <div class="btn"><a href="calificacion.php">Calificaciones</a></div>
                <div class="btn"><a href="LDA.php">Disponibilidad</a></div>
                <div class="btn"><a href="index.php">Reportes</a></div>
                <div class="btn"><a href="HEP.php">Evaluaciones</a></div>
                <div class="btn"><a href="fotoalu.php">Foto Alumnos</a></div>
                <div class="btn"><a href="logout.php">Cerrar Sesión</a></div>
            </div>
        </div>
    </nav>
    
    <!-- Contenido Principal -->
    <div class="contai">
        <h1>📸 Reporte de Fotos de Alumnos</h1>    
        
        <!-- Buscador -->        
        <div class="search-container">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Buscar por nombre, apellido o matrícula..." 
                       value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit">🔍 Buscar</button>
            </form>
        </div>
        
        <div class="alumnos-container">
            <?php if (count($alumnos) > 0): ?>
                <?php foreach ($alumnos as $alumno): ?>
                    <div class="alumno">
                        <?php
                        // Manejo de la imagen del alumno
                        if (!empty($alumno['rutaImagen']) && file_exists($alumno['rutaImagen'])) {
                            $foto = $alumno['rutaImagen'];
                        } else {
                            $foto = 'img/alumno_default.jpg'; // Imagen predeterminada
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($foto, ENT_QUOTES, 'UTF-8'); ?>" 
                             alt="Foto de <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellidoPaterno'], ENT_QUOTES, 'UTF-8'); ?>"
                             onerror="this.src='img/alumno_default.jpg'">
                        
                        <div class="alumno-estatus">
                            <?php echo htmlspecialchars($alumno['tipoEstatus'] ?? 'Sin estatus', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        
                        <div class="alumno-info">
                            <p><strong>Nombre:</strong> 
                                <span><?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellidoPaterno'] . ' ' . $alumno['apellidoMaterno'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>
                            <p><strong>Matrícula:</strong> 
                                <span><?php echo htmlspecialchars($alumno['matriculaAlumno'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>
                            <p><strong>Grupo:</strong> 
                                <span><?php echo htmlspecialchars($alumno['grupo'] ?? 'Sin grupo', ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>
                            <p><strong>Género:</strong> 
                                <span><?php echo htmlspecialchars($alumno['genero'] ?? 'No especificado', ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>
                            <p><strong>Emergencia:</strong> 
                                <span><?php echo htmlspecialchars($alumno['telefonoEmergencia'] ?? 'No disponible', ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>
                            <p><strong>Email:</strong> 
                                <span><?php echo htmlspecialchars($alumno['mailInstitucional'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>
                            <p><strong>Origen:</strong> 
                                <span><?php echo htmlspecialchars($alumno['estado_Nacimiento'] ?? 'No especificado', ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>
                            <p><strong>Discapacidad:</strong> 
                                <span><?php echo htmlspecialchars($alumno['tipoDiscapacidad'] ?? 'Ninguna', ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>No se encontraron alumnos que coincidan con la búsqueda.</p>
                    <p>Intenta con otro término o verifica la base de datos.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="button-container">
            <a href="agregarfotoalu.php" class="btn-agregar">➕ Agregar Alumno</a>
            <a href="editarfotoalu.php" class="btn-editar1">✏️ Editar Fotos</a>
        </div>
    </div>
    
    <!-- Pie de Página -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-logo">
                <a href="index.php"><img src="img/logo1.jpg" alt="Logo CECyTE" class="logo-img1"></a>
            </div>
            <div class="footer-linea"></div>
            <div class="footer-info">
                <p class="footer-year">© 2025 CECyTE Santa Catarina, N.L. | Sistema de Gestión Escolar</p>
                <p class="footer-year">Contacto: informatica@cecytesc.edu.mx | Tel: (81) 1234-5678</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Script para mejorar la experiencia de usuario
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar animación a las tarjetas al cargar
            const cards = document.querySelectorAll('.alumno');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.animation = 'fadeInUp 0.6s ease forwards';
            });
            
            // Confirmación antes de eliminar (si se implementa en el futuro)
            const deleteButtons = document.querySelectorAll('.btn-eliminar');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('¿Estás seguro de que deseas eliminar este alumno?')) {
                        e.preventDefault();
                    }
                });
            });
        });
        
        // Agregar CSS para animación
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .alumno {
                opacity: 0;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>