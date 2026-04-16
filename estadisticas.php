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

// Función para obtener datos
function obtenerDatos($query) {
    global $con;
    try {
        $stmt = $con->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// --- CONSULTAS (Se mantienen igual que en tu original) ---
$alumnosActivos = obtenerDatos("SELECT COUNT(*) AS alumnosActivos FROM historialacademicoalumnos haa INNER JOIN estatus e ON e.id_estatus = haa.id_estatus WHERE e.tipoEstatus = 'activo'")[0]['alumnosActivos'] ?? 0;
$totalAlumnos = obtenerDatos("SELECT COUNT(*) AS total FROM alumnos")[0]['total'] ?? 0;
$totalMaestros = obtenerDatos("SELECT COUNT(*) AS total FROM maestros")[0]['total'] ?? 0;
$totalCalificaciones = obtenerDatos("SELECT COUNT(*) AS total FROM calificaciones")[0]['total'] ?? 0;
// ... (Aquí irían el resto de tus consultas de lógica que ya tienes)

// Datos JSON para JS
$dataJSON = json_encode([
    'alumnosActivos' => $alumnosActivos,
    'totalAlumnos' => $totalAlumnos,
    'totalMaestros' => $totalMaestros,
    // ... (Asegúrate de incluir todos los campos que usas en tus gráficas)
]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CECYTE - Sistema de Estadísticas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --verde-oscuro: #1a5330;
            --verde-principal: #2e7d32;
            --verde-medio: #4caf50;
            --verde-claro: #8bc34a;
            --verde-brillante: #81c784;
        }
        
        body {
            background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Removido el sidebar, el contenedor ahora es full width */
        .main-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(46, 125, 50, 0.1);
            border-bottom: 3px solid var(--verde-medio);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-wrapper {
            width: 100%;
            padding: 20px;
        }

        .page-title {
            color: var(--verde-oscuro);
            font-weight: 700;
            border-bottom: 3px solid var(--verde-claro);
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .stat-card-main {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            border: 1px solid rgba(139, 195, 74, 0.2);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card-main:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--verde-principal);
        }

        .chart-wrapper {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo-container">
            <img src="img/logo.png" alt="Logo" height="40">
            <span style="font-weight: bold; color: var(--verde-oscuro); margin-left: 10px;">CECYTE SC - Dashboard</span>
        </div>
        <div class="user-actions">
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar Sesión</a>
        </div>
    </header>

    <main class="content-wrapper">
        <h2 class="page-title">Panel de Estadísticas Institucionales</h2>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="stat-card-main">
                    <i class='bx bxs-user-badge stat-number' style="font-size: 2rem;"></i>
                    <div class="stat-number"><?php echo $totalAlumnos; ?></div>
                    <div class="stat-label">Total Alumnos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-main">
                    <i class='bx bxs-briefcase stat-number' style="font-size: 2rem;"></i>
                    <div class="stat-number"><?php echo $totalMaestros; ?></div>
                    <div class="stat-label">Docentes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-main">
                    <i class='bx bxs-check-circle stat-number' style="font-size: 2rem;"></i>
                    <div class="stat-number"><?php echo $alumnosActivos; ?></div>
                    <div class="stat-label">Alumnos Activos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-main">
                    <i class='bx bxs-book-bookmark stat-number' style="font-size: 2rem;"></i>
                    <div class="stat-number"><?php echo $totalCalificaciones; ?></div>
                    <div class="stat-label">Registros Académicos</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="chart-wrapper">
                    <h5>Distribución por Género</h5>
                    <canvas id="chartGenero"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-wrapper">
                    <h5>Rendimiento Académico</h5>
                    <canvas id="chartRendimiento"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script>
        const data = <?php echo $dataJSON; ?>;
        // Aquí incluirías tu lógica de Chart.js usando el objeto 'data'
    </script>
</body>
</html>