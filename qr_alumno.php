<?php
// qr_alumno.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['matricula'])) {
    header('Location: lista_alumnos.php?error=matricula_invalida');
    exit();
}

$matricula = trim($_GET['matricula']);

try {
    $sql = "SELECT a.*, 
                   c.nombre as carrera_nombre, 
                   c.clave as carrera_clave,
                   g.nombre as grupo_nombre,
                   e.tipoEstatus as estatus_nombre
            FROM alumnos a
            LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
            LEFT JOIN grupos g ON a.id_grupo = g.id_grupo
            LEFT JOIN estatus e ON a.id_estatus = e.id_estatus
            WHERE a.matricula = :matricula";
    
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('Location: lista_alumnos.php?error=not_found');
        exit();
    }
    
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener datos del alumno: " . $e->getMessage());
}

$datosQR = [
    'tipo' => 'ALUMNO_CECYTE',
    'version' => '1.0',
    'matricula' => $alumno['matricula'],
    'nombre' => $alumno['nombre'] . ' ' . $alumno['apellido_paterno'] . ' ' . ($alumno['apellido_materno'] ?? ''),
    'curp' => $alumno['curp'],
    'carrera' => $alumno['carrera_nombre'] ?? '',
    'semestre' => $alumno['semestre'] ?? '',
    'grupo' => $alumno['grupo_nombre'] ?? '',
    'estatus' => $alumno['estatus_nombre'] ?? 'Activo',
    'fecha_ingreso' => $alumno['fecha_ingreso'] ?? '',
    'fecha_generacion' => date('Y-m-d H:i:s')
];

$qrData = json_encode($datosQR, JSON_UNESCAPED_UNICODE);
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrData);

$tipo = $_GET['tipo'] ?? 'html';

if ($tipo == 'download') {
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="QR_' . $alumno['matricula'] . '.png"');
    readfile($qrUrl);
    exit;
}

if ($tipo == 'print') {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Imprimir QR - <?php echo htmlspecialchars($alumno['matricula']); ?></title>
        <style>
            body { font-family: 'Segoe UI', sans-serif; text-align: center; padding: 40px; }
            .print-card { border: 2px solid #2e7d32; border-radius: 15px; padding: 20px; display: inline-block; }
            .qr-code { width: 250px; margin: 20px 0; }
            h2 { color: #1b5e20; margin: 0; }
            .no-print { margin-top: 20px; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="print-card">
            <img src="logo_cecyte.png" alt="Logo" style="height: 50px;"> <h2>Credencial Digital</h2>
            <p><strong><?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno']); ?></strong></p>
            <p>Matrícula: <?php echo htmlspecialchars($alumno['matricula']); ?></p>
            <img src="<?php echo $qrUrl; ?>" class="qr-code">
            <p style="font-size: 12px; color: #666;">Sistema de Gestión Académica CECyTE 2026</p>
        </div>
        <div class="no-print">
            <button onclick="window.print()" style="padding: 10px 20px; background: #2e7d32; color: white; border: none; border-radius: 5px; cursor: pointer;">Imprimir Ahora</button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer;">Cerrar</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Alumno | CECyTE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-dark: #1b5e20;
            --accent: #8bc34a;
            --bg: #f4f7f6;
            --white: #ffffff;
            --shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; color: #333; }
        
        .header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white; padding: 1rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
        }

        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        
        .main-card {
            background: var(--white); border-radius: 16px; overflow: hidden;
            box-shadow: var(--shadow); display: flex; flex-wrap: wrap;
        }

        /* Lado Izquierdo: Información */
        .info-side { flex: 1; min-width: 300px; padding: 40px; }
        
        /* Lado Derecho: QR */
        .qr-side { 
            flex: 1; min-width: 300px; padding: 40px; 
            background: #f9fbf9; display: flex; flex-direction: column; 
            align-items: center; justify-content: center;
            border-left: 1px solid #eee;
        }

        .student-header h2 { margin: 0; color: var(--primary-dark); font-size: 1.5rem; }
        .student-header p { color: #666; margin: 5px 0 25px 0; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 10px; border-left: 4px solid var(--accent); }
        .info-box label { display: block; font-size: 0.75rem; font-weight: 700; color: #888; text-transform: uppercase; }
        .info-box span { font-size: 0.95rem; font-weight: 600; color: #333; }

        .qr-wrapper {
            background: white; padding: 15px; border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .qr-code { width: 220px; height: 220px; }

        /* Botones estilo CECyTE */
        .btn-group { display: flex; gap: 10px; width: 100%; margin-top: 20px; }
        .btn-cecyte {
            flex: 1; text-decoration: none; text-align: center; padding: 12px;
            border-radius: 8px; font-weight: 600; font-size: 0.85rem; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-primary { background: var(--primary); color: white; border: none; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { border: 1px solid #ddd; color: #555; background: white; }
        .btn-outline:hover { background: #eee; }

        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
            .qr-side { border-left: none; border-top: 1px solid #eee; }
        }
    </style>
</head>
<body>

<header class="header">
    <a href="ver_alumno.php?matricula=<?= urlencode($matricula) ?>" style="color:white; text-decoration:none;">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
    <h1 style="font-size: 1.2rem; margin:0;">Credencial Digital CECyTE</h1>
    <div></div>
</header>

<div class="container">
    <div class="main-card">
        <div class="info-side">
            <div class="student-header">
                <h2><?= htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno']) ?></h2>
                <p><i class="fas fa-id-card"></i> Matrícula: <strong><?= htmlspecialchars($alumno['matricula']) ?></strong></p>
            </div>

            <div class="info-grid">
                <div class="info-box">
                    <label>Carrera</label>
                    <span><?= htmlspecialchars($alumno['carrera_nombre'] ?? 'No asignada') ?></span>
                </div>
                <div class="info-box">
                    <label>Grupo</label>
                    <span><?= htmlspecialchars($alumno['grupo_nombre'] ?? 'Sin grupo') ?></span>
                </div>
                <div class="info-box">
                    <label>Semestre</label>
                    <span><?= htmlspecialchars($alumno['semestre'] ?? '0') ?>°</span>
                </div>
                <div class="info-box">
                    <label>Estatus</label>
                    <span style="color: <?= (strtolower($alumno['estatus_nombre']) == 'activo') ? '#2e7d32' : '#c62828' ?>;">
                        <?= htmlspecialchars($alumno['estatus_nombre'] ?? 'Activo') ?>
                    </span>
                </div>
                <div class="info-box" style="grid-column: 1 / -1;">
                    <label>CURP</label>
                    <span><?= htmlspecialchars($alumno['curp']) ?></span>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <a href="editar_alumnos.php?matricula=<?= urlencode($matricula) ?>" class="btn-cecyte btn-outline" style="width: 100%; box-sizing: border-box;">
                    <i class="fas fa-edit"></i> Editar Datos del Alumno
                </a>
            </div>
        </div>

        <div class="qr-side">
            <div class="qr-wrapper">
                <img src="<?= $qrUrl ?>" alt="QR Alumno" class="qr-code">
            </div>
            
            <p style="font-size: 0.8rem; color: #888; text-align: center; margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i> Escanea este código para registro de asistencia o acceso.
            </p>

            <div class="btn-group">
                <a href="qr_alumno.php?matricula=<?= urlencode($matricula) ?>&tipo=download" class="btn-cecyte btn-primary">
                    <i class="fas fa-download"></i> Descargar
                </a>
                <a href="qr_alumno.php?matricula=<?= urlencode($matricula) ?>&tipo=print" target="_blank" class="btn-cecyte btn-outline">
                    <i class="fas fa-print"></i> Imprimir
                </a>
            </div>
            
            <a href="qr_alumno.php?matricula=<?= urlencode($matricula) ?>&tipo=print&autoprint=1" target="_blank" 
               style="margin-top: 15px; font-size: 0.75rem; color: var(--primary); text-decoration: none; font-weight: 600;">
                <i class="fas fa-magic"></i> Impresión Rápida
            </a>
        </div>
    </div>
</div>

</body>
</html>