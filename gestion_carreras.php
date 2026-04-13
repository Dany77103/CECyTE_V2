<?php
session_start();

// Verificar permisos
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Carreras | CECyTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --secondary: #6c757d;
            --bg: #f4f6f9;
            --white: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { 
            background-color: var(--bg); 
            font-family: 'Inter', sans-serif; 
            color: #333;
            padding-top: 90px;
        }

        /* --- NAVBAR FIJA --- */
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

        .navbar-brand {
            display: flex; align-items: center; gap: 15px; text-decoration: none;
        }

        .navbar-brand img { height: 45px; width: auto; }
        .navbar-brand span { font-weight: 700; color: var(--primary); font-size: 1.2rem; }

        .nav-actions { display: flex; gap: 10px; }

        /* --- CONTENEDOR --- */
        .container { max-width: 1300px; margin: 0 auto; padding: 0 20px 40px; }

        .card { 
            background: var(--white); border-radius: 20px; padding: 25px; 
            margin-bottom: 25px; box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.02);
        }

        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;
        }

        .card-header h2 { font-size: 1.25rem; color: var(--primary); display: flex; align-items: center; gap: 10px; }

        /* --- TABLA --- */
        .table-responsive { overflow-x: auto; }
        .tabla { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla th { 
            text-align: left; padding: 15px; color: var(--secondary); 
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 2px solid #eee; background: #f8f9fa;
        }

        /* --- BOTONES --- */
        .btn { 
            padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; 
            font-weight: 600; font-size: 0.85rem; display: inline-flex; 
            align-items: center; gap: 8px; text-decoration: none; 
        }

        .btn-secondary { background: #e9ecef; color: var(--secondary); }

        /* MANTENIMIENTO */
        .mantenimiento-container {
            padding: 60px 20px;
            text-align: center;
        }

        .mantenimiento-container i {
            font-size: 55px;
            color: #ffa000;
            margin-bottom: 20px;
        }

        .mantenimiento-container h3 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .mantenimiento-container p {
            color: var(--secondary);
            font-size: 1rem;
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div class="nav-actions">
            <a href="main.php" class="btn btn-secondary"><i class="fas fa-home"></i></a>
            <a href="logout.php" class="btn btn-secondary" style="color: #dc3545;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-search"></i> Filtros de Carreras</h2>
            </div>
            <p style="color: var(--secondary); font-size: 0.9rem; font-style: italic;">
                Las opciones de búsqueda no están disponibles por el momento.
            </p>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-graduation-cap"></i> Listado de Carreras</h2>
            </div>
            
            <div class="table-responsive">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Nombre de la Carrera</th>
                            <th>Alumnos</th>
                            <th>Estatus</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>

            <div class="mantenimiento-container">
                <i class="fas fa-tools"></i>
                <h3>Apartado en mantenimiento</h3>
                <p>Estamos realizando mejoras técnicas en este módulo. Por favor, vuelva a intentarlo más tarde o contacte al soporte del plantel.</p>
            </div>
        </div>
    </div>

</body>
</html>