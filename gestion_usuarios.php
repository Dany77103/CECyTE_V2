<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        switch ($accion) {
            case 'cambiar_estado_alumno':
                $sql = "UPDATE alumnos SET activo = :activo WHERE id_alumno = :id_alumno";
                $stmt = $con->prepare($sql);
                $stmt->execute(['activo' => $_POST['nuevo_estado'], 'id_alumno' => $_POST['id_alumno']]);
                $mensaje = "Estado del alumno actualizado.";
                $tipo_mensaje = 'exito';
                break;
            case 'cambiar_estado_maestro':
                $sql = "UPDATE maestros SET activo = :activo WHERE id_maestro = :id_maestro";
                $stmt = $con->prepare($sql);
                $stmt->execute(['activo' => $_POST['nuevo_estado'], 'id_maestro' => $_POST['id_maestro']]);
                $mensaje = "Estado del maestro actualizado.";
                $tipo_mensaje = 'exito';
                break;
        }
    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

$usuarios = $con->query("SELECT * FROM usuarios ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$alumnos = $con->query("SELECT id_alumno, matricula, CONCAT(nombre, ' ', apellido_paterno) as nombre, activo FROM alumnos ORDER BY matricula ASC")->fetchAll(PDO::FETCH_ASSOC);
$maestros = $con->query("SELECT id_maestro, numEmpleado, CONCAT(nombre, ' ', apellido_paterno) as nombre, activo FROM maestros ORDER BY numEmpleado ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Integral | CECyTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --secondary: #6c757d;
            --white: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body { 
            background: #f4f6f9; 
            font-family: 'Inter', sans-serif; 
            color: #333;
            padding-top: 80px; 
        }

        /* --- HEADER BLANCO --- */
        .navbar {
            background: var(--white);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }

        .navbar-brand img {
            height: 45px; 
            width: auto;
        }

        .navbar-brand span {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.2rem;
            letter-spacing: -0.5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--secondary);
            font-size: 0.9rem;
        }

        /* --- CONTENEDOR PRINCIPAL --- */
        .container { 
            max-width: 1100px; 
            margin: 20px auto; 
            padding: 0 20px 40px;
        }

        /* Tabs */
        .tabs { 
            display: flex; 
            background: #e9ecef;
            padding: 5px;
            border-radius: 12px;
            margin-bottom: 30px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .tab-btn { 
            padding: 10px 25px; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            background: transparent; 
            font-weight: 600; 
            color: #6c757d;
            transition: var(--transition);
        }

        .tab-btn.active { 
            background: var(--white); 
            color: var(--primary); 
            box-shadow: var(--shadow-sm);
        }

        /* Tarjetas */
        .card { 
            background: var(--white); 
            border-radius: 20px; 
            padding: 30px; 
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.03);
            display: none;
            animation: fadeIn 0.4s ease;
        }
        .card.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .card-header h2 { 
            font-size: 1.3rem;
            color: var(--primary); 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }

        /* Tablas */
        .table-responsive { overflow-x: auto; }
        .tabla { width: 100%; border-collapse: collapse; }
        .tabla th { 
            text-align: left; 
            padding: 15px; 
            background: #f8f9fa;
            color: #6c757d;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .tabla td { padding: 15px; border-bottom: 1px solid #f1f1f1; }
        .tabla tr:hover { background: #fafafa; }

        /* Badges */
        .estatus-badge { 
            padding: 5px 12px; 
            border-radius: 6px; 
            font-size: 0.75rem; 
            font-weight: 700; 
        }
        .estatus-activo { background: #d4edda; color: #155724; }
        .estatus-inactivo { background: #f8d7da; color: #721c24; }

        /* Botones */
        .btn { 
            padding: 8px 16px; 
            border-radius: 8px; 
            border: none; 
            cursor: pointer; 
            font-weight: 600; 
            font-size: 0.85rem;
            text-decoration: none; 
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-cambiar { background: var(--primary); color: white; }
        .btn-cambiar:hover { background: var(--primary-light); transform: translateY(-2px); }
        .btn-editar { background: #fff3cd; color: #856404; }
        .btn-back { background: #6c757d; color: white; }

        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            text-align: center;
        }

        @media (max-width: 768px) {
            .navbar-brand span { display: none; }
            .tabs { width: 100%; overflow-x: auto; justify-content: flex-start; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="#" class="navbar-brand">
            <img src="img/logo.png" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div class="user-info">
            <i class="fas fa-user-circle fa-lg"></i>
            <span>Administrador</span>
        </div>
    </nav>

    <div class="container">
        
        <?php if ($mensaje): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i> <?= $mensaje ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab(event, 'u')">Usuarios</button>
            <button class="tab-btn" onclick="openTab(event, 'a')">Alumnos</button>
            <button class="tab-btn" onclick="openTab(event, 'm')">Maestros</button>
        </div>

        <div id="u" class="card active">
            <div class="card-header">
                <h2><i class="fas fa-user-shield"></i> Gestión de Usuarios</h2>
                <a href="main.php" class="btn btn-back"><i class="fas fa-home"></i> Inicio</a>
            </div>
            <div class="table-responsive">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= ucfirst($u['rol']) ?></td>
                            <td>
                                <span class="estatus-badge <?= $u['activo'] ? 'estatus-activo' : 'estatus-inactivo' ?>">
                                    <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td><a href="?editar=<?= $u['id'] ?>" class="btn btn-editar">Editar</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="a" class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-graduate"></i> Control de Alumnos</h2>
                <a href="main.php" class="btn btn-back"><i class="fas fa-home"></i> Inicio</a>
            </div>
            <div class="table-responsive">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos as $a): ?>
                        <tr>
                            <td style="color: var(--primary); font-weight: bold;"><?= $a['matricula'] ?></td>
                            <td><?= htmlspecialchars($a['nombre']) ?></td>
                            <td><span class="estatus-badge <?= ($a['activo'] == 'Activo' ? 'estatus-activo' : 'estatus-inactivo') ?>"><?= $a['activo'] ?></span></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="cambiar_estado_alumno">
                                    <input type="hidden" name="id_alumno" value="<?= $a['id_alumno'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="<?= ($a['activo'] == 'Activo' ? 'Inactivo' : 'Activo') ?>">
                                    <button type="submit" class="btn btn-cambiar">Alternar Estado</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="m" class="card">
            <div class="card-header">
                <h2><i class="fas fa-chalkboard-teacher"></i> Control de Maestros</h2>
                <a href="main.php" class="btn btn-back"><i class="fas fa-home"></i> Inicio</a>
            </div>
            <div class="table-responsive">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>N° Empleado</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maestros as $m): ?>
                        <tr>
                            <td style="font-weight: bold;"><?= $m['numEmpleado'] ?></td>
                            <td><?= htmlspecialchars($m['nombre']) ?></td>
                            <td><span class="estatus-badge <?= ($m['activo'] == 'Activo' ? 'estatus-activo' : 'estatus-inactivo') ?>"><?= $m['activo'] ?></span></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="cambiar_estado_maestro">
                                    <input type="hidden" name="id_maestro" value="<?= $m['id_maestro'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="<?= ($m['activo'] == 'Activo' ? 'Inactivo' : 'Activo') ?>">
                                    <button type="submit" class="btn btn-cambiar">Alternar Estado</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            document.querySelectorAll('.card').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('active');
            evt.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>