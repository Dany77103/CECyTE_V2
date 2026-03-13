<?php
session_start();
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$es_admin = ($_SESSION['rol'] == 'admin');

// Verificar que la conexión existe
if (!isset($con)) {
    die("Error: No se pudo conectar a la base de datos.");
}

// Verificar si existe la tabla horarios_maestros
try {
    $table_check = $con->query("SHOW TABLES LIKE 'horarios_maestros'");
    $table_exists = $table_check->rowCount() > 0;
} catch (PDOException $e) {
    $table_exists = false;
}

// Obtener todos los maestros con su horario
if ($table_exists) {
    $query = "
        SELECT m.*, 
               COUNT(hm.id_horario) as total_clases,
               GROUP_CONCAT(DISTINCT hm.periodo) as periodos
        FROM maestros m
        LEFT JOIN horarios_maestros hm ON m.id_maestro = hm.id_maestro
        WHERE m.estado = 'Activo'
        GROUP BY m.id_maestro
        ORDER BY m.apellido_paterno, m.nombre
    ";
} else {
    $query = "
        SELECT m.*, 
               0 as total_clases,
               '' as periodos
        FROM maestros m
        WHERE m.estado = 'Activo'
        ORDER BY m.apellido_paterno, m.nombre
    ";
}

try {
    $stmt = $con->prepare($query);
    $stmt->execute();
    $maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener la lista de maestros: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Maestros - Horarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .card-maestro {
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .card-maestro:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .badge-horario {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class='bx bxs-group'></i> Maestros - Gestión de Horarios</h2>
            <div>
                <a href="main.php" class="btn btn-outline-primary">
                    <i class='bx bx-arrow-back'></i> Regresar
                </a>
                <?php if ($es_admin): ?>
                    <a href="horario_maestros_captura.php" class="btn btn-success">
                        <i class='bx bxs-calendar-plus'></i> Capturar Horario
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!$table_exists): ?>
            <div class="alert alert-info mb-4">
                <h5><i class='bx bx-info-circle'></i> Tabla de horarios no encontrada</h5>
                <p>Para usar todas las funciones, ejecuta el script SQL de creación de tablas en phpMyAdmin.</p>
                <a href="horario_maestros_captura.php" class="btn btn-sm btn-outline-info">
                    <i class='bx bx-wrench'></i> Configurar Base de Datos
                </a>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <?php foreach ($maestros as $maestro): ?>
                <div class="col-md-4 mb-4">
                    <div class="card card-maestro h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido_paterno']); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <strong><i class='bx bx-id-card'></i> No. Empleado:</strong> 
                                <?php echo htmlspecialchars($maestro['numEmpleado']); ?><br>
                                
                                <strong><i class='bx bx-envelope'></i> Correo:</strong> 
                                <?php echo htmlspecialchars($maestro['correo_institucional']); ?><br>
                                
                                <strong><i class='bx bx-phone'></i> Teléfono:</strong> 
                                <?php echo htmlspecialchars($maestro['telefono_celular'] ?: 'No registrado'); ?>
                            </p>
                            
                            <div class="mt-3">
                                <span class="badge bg-success badge-horario">
                                    <i class='bx bx-calendar'></i> 
                                    <?php echo $maestro['total_clases']; ?> clases
                                </span>
                                
                                <?php if (!empty($maestro['periodos'])): ?>
                                    <span class="badge bg-info badge-horario">
                                        <i class='bx bx-time'></i> 
                                        <?php echo substr($maestro['periodos'], 0, 9); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between">
                                <a href="horario_maestro_vista.php?id=<?php echo $maestro['id_maestro']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class='bx bx-show'></i> Ver Horario
                                </a>
                                
                                <?php if ($es_admin): ?>
                                    <a href="horario_maestros_captura.php?maestro=<?php echo $maestro['id_maestro']; ?>" 
                                       class="btn btn-sm btn-outline-success">
                                        <i class='bx bx-edit'></i> Editar
                                    </a>
                                    
                                    <a href="imprimir_horario.php?id=<?php echo $maestro['id_maestro']; ?>" 
                                       target="_blank"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class='bx bx-printer'></i> Imprimir
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4 text-center">
            <p class="text-muted">
                <small>
                    <i class='bx bx-info-circle'></i> Total de maestros activos: <?php echo count($maestros); ?>
                </small>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Hacer clic en toda la tarjeta redirige a ver horario
        document.querySelectorAll('.card-maestro').forEach(card => {
            card.addEventListener('click', function(e) {
                // Solo redirigir si no se hizo clic en un botón o enlace
                if (!e.target.closest('a') && !e.target.closest('button')) {
                    const link = this.querySelector('a[href*="horario_maestro_vista"]');
                    if (link) {
                        window.location = link.href;
                    }
                }
            });
        });
    </script>
</body>
</html>