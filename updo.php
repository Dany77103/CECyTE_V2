<?php
// Conexión a la base de datos usando PDO
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cecyte_sc";

try {
    $con = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

session_start();

// Verificar si el usuario ha iniciado sesión
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
	<title>Panel de Navegación</title>
    <link rel="stylesheet" href="stylesII.css">
    <!-- BOXICONS -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
</head>

<body class="header-page">
    <div class="navbar">
	 <a href="main.php" class="logo">
            <img src="img/logo.png" alt="Logo de la Escuela" class="logo-img">
        </a>
		
		
			<div class="logo-container">
							<div class="logo-name"><?php echo ($_SESSION['loggedin'] !== true) ? 'SISTEMA DE REPORTES' : 'SISTEMA DE REPORTES'; ?></div>
							<i class='bx bx-menu' id="btn-toggle"></i>
						</div>
        <ul class="nav-container">
            <li class="search">
                <i class='bx bx-search'></i>
                <input type="text" placeholder="Buscar">
                <span class="tooltip">Buscar</span>
            </li>

            <li>
                <a href="home.php">
                    <i class='bx bx-home-alt-2'></i>
                    <span class="links">Inicio</span>
                </a>
                <span class="tooltip">Inicio</span>
            </li>

           
            <li>
                <a href="#">
                    <i class='bx bx-file'></i>
                    <span class="links">Solicitudes</span>
                </a>
                <span class="tooltip">Solicitudes</span>
            </li>

            <li>
                <a href="#">
                    <i class='bx bx-user'></i>
                    <span class="links">Perfil</span>
                </a>
                <span class="tooltip">Perfil</span>
            </li>

            <li>
                <a href="#">
                    <i class='bx bx-user-plus'></i>
                    <span class="links">Asignar</span>
                </a>
                <span class="tooltip">Asignar</span>
            </li>

            <li>
                <a href="#">
                    <i class='bx bx-chart'></i>
                    <span class="links">Estadísticas</span>
                </a>
                <span class="tooltip">Estadísticas</span>
            </li>

            <li>
                <a href="#">
                    <i class='bx bx-folder'></i>
                    <span class="links">Archivos</span>
                </a>
                <span class="tooltip">Archivos</span>
            </li>

            <li>
                <a href="#">
                    <i class='bx bx-cog'></i>
                    <span class="links">Configuración</span>
                </a>
                <span class="tooltip">Configuración</span>
            </li>

           
            <li>
                <a href="solicitud.php">
                    <i class='bx bx-pencil'></i>
                    <span class="links">Formulario</span>
                </a>
                <span class="tooltip">Formulario</span>
            </li>

            <li>
                <a href="#">
                    <i class='bx bx-user'></i>
                    <span class="links">Perfil</span>
                </a>
                <span class="tooltip">Perfil</span>
            </li>

            <li>
                <a href="#">
                    <i class='bx bx-folder'></i>
                    <span class="links">Archivos</span>
                </a>
                <span class="tooltip">Archivos</span>
            </li>

            <li>
                <a href="#">
                    <i class='bx bx-bell'></i>
                    <span class="links">Notificaciones</span>
                </a>
                <span class="tooltip">Notificaciones</span>
            </li>

            <li>
                <a href="#">
                    <i class='bx bx-cog'></i>
                    <span class="links">Configuración</span>
                </a>
                <span class="tooltip">Configuración</span>
            </li>
        </ul>

        <div class="user">
            <a href="logout.php">
                <i class='bx bx-log-out-circle' id="logout"></i>
            </a>
        </div>
    </div>
</body>
</html>