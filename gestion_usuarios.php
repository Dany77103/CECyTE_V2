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
    <title>Gestión CECYTE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --verde-oscuro-1: #1a5330; --verde-oscuro-2: #2e7d32; --blanco: #ffffff; }
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: var(--blanco); border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; background: #ddd; font-weight: bold; }
        .tab-btn.active { background: var(--verde-oscuro-2); color: white; }
        .content-section { display: none; }
        .content-section.active { display: block; }
        .tabla { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla th { background: var(--verde-oscuro-1); color: white; padding: 12px; text-align: left; }
        .tabla td { padding: 12px; border-bottom: 1px solid #eee; }
        .estatus-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .estatus-activo { background: #c8e6c9; color: #2e7d32; }
        .estatus-inactivo { background: #ffcdd2; color: #c62828; }
        .btn { padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; color: white; text-decoration: none; display: inline-block; }
        .btn-editar { background: #ffc107; color: black; }
        .btn-cambiar { background: var(--verde-oscuro-2); }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f1f8e9; padding-bottom: 15px; }
        .header-section h2 { margin: 0; color: var(--verde-oscuro-1); display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestión Integral - CECYTE</h1>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab(event, 'u')">Usuarios</button>
            <button class="tab-btn" onclick="openTab(event, 'a')">Alumnos</button>
            <button class="tab-btn" onclick="openTab(event, 'm')">Maestros</button>
        </div>

        <div id="u" class="content-section active card">
            <div class="header-section">
                <h2><i class="fas fa-user-shield"></i> Gestión de Usuarios</h2>
                <a href="main.php" class="btn" style="background: #555;"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
            </div>
            <table class="tabla">
                <thead><tr><th>Usuario</th><th>Rol</th><th>Estatus</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= $u['rol'] ?></td>
                        <td><span class="estatus-badge <?= $u['activo'] ? 'estatus-activo' : 'estatus-inactivo' ?>"><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                        <td><a href="?editar=<?= $u['id'] ?>" class="btn btn-editar">Editar</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="a" class="content-section card">
            <div class="header-section">
                <h2><i class="fas fa-user-graduate"></i> Gestión de Alumnos</h2>
                <a href="main.php" class="btn" style="background: #555;"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
            </div>
            <table class="tabla">
                <thead><tr><th>Matrícula</th><th>Nombre</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>
                    <?php foreach ($alumnos as $a): ?>
                    <tr>
                        <td><?= $a['matricula'] ?></td>
                        <td><?= htmlspecialchars($a['nombre']) ?></td>
                        <td><span class="estatus-badge <?= ($a['activo'] == 'Activo' ? 'estatus-activo' : 'estatus-inactivo') ?>"><?= $a['activo'] ?></span></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="accion" value="cambiar_estado_alumno">
                                <input type="hidden" name="id_alumno" value="<?= $a['id_alumno'] ?>">
                                <input type="hidden" name="nuevo_estado" value="<?= ($a['activo'] == 'Activo' ? 'Inactivo' : 'Activo') ?>">
                                <button type="submit" class="btn btn-cambiar">Cambiar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="m" class="content-section card">
            <div class="header-section">
                <h2><i class="fas fa-chalkboard-teacher"></i> Gestión de Maestros</h2>
                <a href="main.php" class="btn" style="background: #555;"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
            </div>
            <table class="tabla">
                <thead><tr><th>N° Emp</th><th>Nombre</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>
                    <?php foreach ($maestros as $m): ?>
                    <tr>
                        <td><?= $m['numEmpleado'] ?></td>
                        <td><?= htmlspecialchars($m['nombre']) ?></td>
                        <td><span class="estatus-badge <?= ($m['activo'] == 'Activo' ? 'estatus-activo' : 'estatus-inactivo') ?>"><?= $m['activo'] ?></span></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="accion" value="cambiar_estado_maestro">
                                <input type="hidden" name="id_maestro" value="<?= $m['id_maestro'] ?>">
                                <input type="hidden" name="nuevo_estado" value="<?= ($m['activo'] == 'Activo' ? 'Inactivo' : 'Activo') ?>">
                                <button type="submit" class="btn btn-cambiar">Cambiar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            evt.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>