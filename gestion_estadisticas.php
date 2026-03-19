<?php
// --- LÓGICA DE BACK-END INTACTA ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cecyte_sc";

try {
    $con = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

function obtenerDato($query, $params = []) {
    global $con;
    try {
        $stmt = $con->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : null;
    } catch (PDOException $e) { return null; }
}

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Consultas para los contadores superiores
$total_alumnos = obtenerDato("SELECT COUNT(*) as total FROM alumnos")['total'] ?? 0;
$total_maestros = obtenerDato("SELECT COUNT(*) as total FROM maestros")['total'] ?? 0;
$total_carreras = obtenerDato("SELECT COUNT(*) as total FROM carreras")['total'] ?? 0;
$promedio_general = obtenerDato("SELECT ROUND(AVG(calificacion), 2) as promedio FROM calificaciones")['promedio'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas Institucionales | CECyTE</title>
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

        /* --- STAT CARDS --- */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 25px; }
        
        .stat-item {
            background: var(--white); border-radius: 20px; padding: 20px;
            text-align: center; box-shadow: var(--shadow-sm);
            border-left: 5px solid var(--primary);
        }

        .stat-item i { font-size: 1.8rem; color: var(--primary-light); margin-bottom: 10px; }
        .stat-value { font-size: 2rem; font-weight: 800; color: var(--primary); display: block; }
        .stat-label { font-size: 0.8rem; color: var(--secondary); font-weight: 600; text-transform: uppercase; }

        /* --- COMPONENTES --- */
        .btn { 
            padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; 
            font-weight: 600; font-size: 0.85rem; display: inline-flex; 
            align-items: center; gap: 8px; text-decoration: none; 
        }

        .btn-secondary { background: #e9ecef; color: var(--secondary); }
        .btn-primary { background: var(--primary); color: white; }

        /* MANTENIMIENTO */
        .mantenimiento-seccion {
            padding: 40px 20px;
            text-align: center;
        }

        .mantenimiento-seccion i {
            font-size: 45px;
            color: #ffa000;
            margin-bottom: 15px;
        }

        .mantenimiento-seccion h3 { color: var(--primary); font-size: 1.2rem; margin-bottom: 8px; }
        .mantenimiento-seccion p { color: var(--secondary); font-size: 0.9rem; max-width: 400px; margin: 0 auto; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo_cecyte.jpg" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div class="nav-actions">
            <a href="main.php" class="btn btn-secondary"><i class="fas fa-home"></i></a>
            <a href="logout.php" class="btn btn-secondary" style="color: #dc3545;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                <div>
                    <h2 style="margin-bottom: 5px;"><i class="fas fa-chart-line"></i> Estadísticas Académicas</h2>
                    <p style="color: var(--secondary); font-size: 0.9rem;">Indicadores generales de rendimiento institucional</p>
                </div>
                <button class="btn btn-primary" disabled><i class="fas fa-file-excel"></i> Exportar Reporte</button>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-item">
                <i class="fas fa-user-graduate"></i>
                <span class="stat-value"><?= $total_alumnos ?></span>
                <span class="stat-label">Alumnos Activos</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span class="stat-value"><?= $total_maestros ?></span>
                <span class="stat-label">Plantilla Docente</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-star"></i>
                <span class="stat-value"><?= $promedio_general ?></span>
                <span class="stat-label">Promedio General</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-graduation-cap"></i>
                <span class="stat-value"><?= $total_carreras ?></span>
                <span class="stat-label">Carreras Técnicas</span>
            </div>
        </div>

        <div class="row" style="display: flex; gap: 20px;">
            <div class="card" style="flex: 1;">
                <div class="card-header">
                    <h2><i class="fas fa-venus-mars"></i> Distribución por Género</h2>
                </div>
                <div class="mantenimiento-seccion">
                    <i class="fas fa-tools"></i>
                    <h3>Gráfico no disponible</h3>
                    <p>Estamos procesando los datos demográficos para esta visualización.</p>
                </div>
            </div>

            <div class="card" style="flex: 1;">
                <div class="card-header">
                    <h2><i class="fas fa-layer-group"></i> Alumnos por Semestre</h2>
                </div>
                <div class="mantenimiento-seccion">
                    <i class="fas fa-tools"></i>
                    <h3>Gráfico en revisión</h3>
                    <p>La visualización de distribución académica estará disponible próximamente.</p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>