<nav class="navbar">
    <div class="container">
        <!-- Logo -->
        <a href="main.php" class="logo">
            <img src="img/logo.png" alt="Logo de la Escuela" class="logo-img">
        </a>

        <!-- Enlaces de navegación -->
        <ul class="nav-links">
            <li><a href="registro.php"><h2>Registro de Info.</h2></a></li>
            <li><a href="reportes.php"><h2>Reportes</h2></a></li>
            <li><a href="estadisticas.php"><h2>Estadisticas</h2></a></li>
            <li><a href="updo.php"><h2>Upload Download</h2></a></li>

            <!-- Mostrar el botón de "Cerrar Sesión" solo si el usuario ha iniciado sesión -->
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <li>
                    <a href="cerrar_sesion.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-sign-out-alt"></i> CERRAR SESION
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>