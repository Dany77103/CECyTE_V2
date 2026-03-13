<?php
session_start();

// Verificar permisos - solo admin puede acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['tipo_usuario'] !== 'sistema' || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'conexion.php';

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'crear':
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $rol = $_POST['rol'];
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                // Validar que el usuario no exista
                $sql_check = "SELECT id FROM usuarios WHERE username = :username";
                $stmt_check = $con->prepare($sql_check);
                $stmt_check->execute(['username' => $username]);
                
                if ($stmt_check->rowCount() > 0) {
                    $mensaje = "El nombre de usuario ya existe";
                    $tipo_mensaje = 'error';
                } else {
                    // Crear hash de contraseña
                    $hash_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $sql = "INSERT INTO usuarios (username, password, rol, activo) 
                            VALUES (:username, :password, :rol, :activo)";
                    
                    $stmt = $con->prepare($sql);
                    $stmt->execute([
                        'username' => $username,
                        'password' => $hash_password,
                        'rol' => $rol,
                        'activo' => $activo
                    ]);
                    
                    $mensaje = "Usuario creado exitosamente";
                    $tipo_mensaje = 'exito';
                }
                break;
                
            case 'editar':
                $id = $_POST['id'];
                $username = trim($_POST['username']);
                $rol = $_POST['rol'];
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                // Si se proporciona nueva contraseña
                if (!empty($_POST['password'])) {
                    $hash_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $sql = "UPDATE usuarios SET username = :username, password = :password, 
                            rol = :rol, activo = :activo WHERE id = :id";
                    $params = [
                        'username' => $username,
                        'password' => $hash_password,
                        'rol' => $rol,
                        'activo' => $activo,
                        'id' => $id
                    ];
                } else {
                    $sql = "UPDATE usuarios SET username = :username, rol = :rol, 
                            activo = :activo WHERE id = :id";
                    $params = [
                        'username' => $username,
                        'rol' => $rol,
                        'activo' => $activo,
                        'id' => $id
                    ];
                }
                
                $stmt = $con->prepare($sql);
                $stmt->execute($params);
                
                $mensaje = "Usuario actualizado exitosamente";
                $tipo_mensaje = 'exito';
                break;
                
            case 'eliminar':
                $id = $_POST['id'];
                
                // No permitir eliminar al usuario actual
                if ($id == $_SESSION['user_id']) {
                    $mensaje = "No puedes eliminar tu propio usuario";
                    $tipo_mensaje = 'error';
                } else {
                    $sql = "DELETE FROM usuarios WHERE id = :id";
                    $stmt = $con->prepare($sql);
                    $stmt->execute(['id' => $id]);
                    
                    $mensaje = "Usuario eliminado exitosamente";
                    $tipo_mensaje = 'exito';
                }
                break;
                
            case 'cambiar_estado':
                $id = $_POST['id'];
                $activo = $_POST['activo'] == 1 ? 0 : 1;
                
                $sql = "UPDATE usuarios SET activo = :activo WHERE id = :id";
                $stmt = $con->prepare($sql);
                $stmt->execute(['activo' => $activo, 'id' => $id]);
                
                $mensaje = "Estado del usuario actualizado";
                $tipo_mensaje = 'exito';
                break;
                
            // NUEVAS ACCIONES PARA ALUMNOS Y MAESTROS
            case 'cambiar_estado_alumno':
                $id_alumno = $_POST['id_alumno'];
                $matricula = $_POST['matricula'];
                $nuevo_estado = $_POST['nuevo_estado'];
                
                // Actualizar estado del alumno
                $sql = "UPDATE alumnos SET activo = :activo WHERE id_alumno = :id_alumno";
                $stmt = $con->prepare($sql);
                $stmt->execute([
                    'activo' => $nuevo_estado,
                    'id_alumno' => $id_alumno
                ]);
                
                $mensaje = "Estado del alumno $matricula actualizado a $nuevo_estado";
                $tipo_mensaje = 'exito';
                break;
                
            case 'cambiar_estado_maestro':
                $id_maestro = $_POST['id_maestro'];
                $numEmpleado = $_POST['numEmpleado'];
                $nuevo_estado = $_POST['nuevo_estado'];
                
                // Actualizar estado del maestro
                $sql = "UPDATE maestros SET activo = :activo WHERE id_maestro = :id_maestro";
                $stmt = $con->prepare($sql);
                $stmt->execute([
                    'activo' => $nuevo_estado,
                    'id_maestro' => $id_maestro
                ]);
                
                $mensaje = "Estado del maestro $numEmpleado actualizado a $nuevo_estado";
                $tipo_mensaje = 'exito';
                break;
        }
    } catch (PDOException $e) {
        error_log("Error en gestión de usuarios: " . $e->getMessage());
        $mensaje = "Error en la operación: " . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener lista de usuarios
$sql = "SELECT id, username, rol, activo, created_at 
        FROM usuarios 
        ORDER BY created_at DESC";
$usuarios = $con->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de alumnos
$sql_alumnos = "SELECT id_alumno, matricula, CONCAT(nombre, ' ', apellido_paterno, ' ', COALESCE(apellido_materno, '')) as nombre_completo, 
                activo, fecha_ingreso, id_carrera 
                FROM alumnos 
                ORDER BY matricula ASC";
$alumnos = $con->query($sql_alumnos)->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de maestros
$sql_maestros = "SELECT id_maestro, numEmpleado, CONCAT(nombre, ' ', apellido_paterno, ' ', COALESCE(apellido_materno, '')) as nombre_completo, 
                 activo, fechaAlta 
                 FROM maestros 
                 ORDER BY numEmpleado ASC";
$maestros = $con->query($sql_maestros)->fetchAll(PDO::FETCH_ASSOC);

// Obtener usuario específico para editar (si se solicita)
$usuario_editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $sql = "SELECT * FROM usuarios WHERE id = :id";
    $stmt = $con->prepare($sql);
    $stmt->execute(['id' => $_GET['editar']]);
    $usuario_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gesti&oacute;n de Usuarios - CECYTE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            /* PALETA DE 5 TONOS VERDE */
            --verde-oscuro-1: #1a5330;    /* Verde muy oscuro (textos importantes) */
            --verde-oscuro-2: #2e7d32;    /* Verde oscuro (botones principales) */
            --verde-medio: #4caf50;       /* Verde medio (acentos y fondos) */
            --verde-claro: #8bc34a;       /* Verde claro (bordes y detalles) */
            --verde-muy-claro: #c8e6c9;   /* Verde muy claro (fondos y hover) */
            
            background: var(--verde-muy-claro);
            color: var(--verde-oscuro-1);
        }
        
        .header {
            background: linear-gradient(135deg, var(--verde-oscuro-1) 0%, var(--verde-oscuro-2) 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(26, 83, 48, 0.3);
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
            border-left: 4px solid;
        }
        
        .mensaje.exito {
            background: #e8f5e9;
            color: var(--verde-oscuro-1);
            border-color: var(--verde-medio);
        }
        
        .mensaje.error {
            background: #ffebee;
            color: #c62828;
            border-color: #f44336;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.1);
            border-top: 4px solid var(--verde-claro);
        }
        
        .card h2 {
            color: var(--verde-oscuro-1);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--verde-muy-claro);
        }
        
        .card h3 {
            color: var(--verde-oscuro-2);
            margin-bottom: 15px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--verde-oscuro-1);
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--verde-claro);
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
            color: var(--verde-oscuro-1);
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--verde-medio);
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input {
            width: auto;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--verde-oscuro-2);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--verde-oscuro-1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 83, 48, 0.3);
        }
        
        .btn-secondary {
            background: var(--verde-medio);
            color: white;
        }
        
        .btn-secondary:hover {
            background: var(--verde-oscuro-2);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #e53935;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c62828;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: #ff9800;
            color: white;
        }
        
        .btn-warning:hover {
            background: #f57c00;
            transform: translateY(-2px);
        }
        
        .btn-info {
            background: var(--verde-claro);
            color: var(--verde-oscuro-1);
        }
        
        .btn-info:hover {
            background: var(--verde-medio);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .tabla-container {
            overflow-x: auto;
            margin-top: 20px;
            border: 1px solid var(--verde-claro);
            border-radius: 5px;
            background: white;
        }
        
        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .tabla th {
            background: var(--verde-muy-claro);
            color: var(--verde-oscuro-1);
            font-weight: 600;
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid var(--verde-claro);
        }
        
        .tabla td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--verde-muy-claro);
            color: var(--verde-oscuro-2);
        }
        
        .tabla tr:hover {
            background: var(--verde-muy-claro);
        }
        
        .estado {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .estado-activo {
            background: #e8f5e9;
            color: var(--verde-oscuro-1);
            border: 1px solid var(--verde-medio);
        }
        
        .estado-inactivo {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #f44336;
        }
        
        .estado-pendiente {
            background: #fff3e0;
            color: #ef6c00;
            border: 1px solid #ff9800;
        }
        
        .acciones {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .seccion-toggle {
            background: var(--verde-muy-claro);
            color: var(--verde-oscuro-1);
            border: 1px solid var(--verde-claro);
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .seccion-toggle.active {
            background: var(--verde-oscuro-2);
            color: white;
            border-color: var(--verde-oscuro-1);
        }
        
        .seccion-toggle:hover:not(.active) {
            background: var(--verde-claro);
            transform: translateY(-2px);
        }
        
        .seccion-contenido {
            display: none;
        }
        
        .seccion-contenido.active {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .acciones {
                flex-direction: column;
            }
            
            .container {
                padding: 0 15px;
            }
        }
        
        /* Badges para roles */
        .badge-admin {
            background: var(--verde-oscuro-1);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-maestro {
            background: var(--verde-oscuro-2);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-alumno {
            background: var(--verde-medio);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-usuario {
            background: var(--verde-claro);
            color: var(--verde-oscuro-1);
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CECYTE - Gesti&oacute;n de Usuarios y Estatus</h1>
        <div class="nav-links">
            <a href="main.php"> Volver al Panel</a>
            <a href="gestion_maestros.php">Gesti&oacute;n de Maestros</a>
            <a href="gestion_alumnos.php">Gesti&oacute;n de Alumnos</a>
            <a href="logout.php">Cerrar Sesi&oacute;n</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <!-- Botones para cambiar entre secciones -->
        <div style="margin-bottom: 20px;">
            <button class="seccion-toggle active" onclick="mostrarSeccion('usuarios')">Usuarios del Sistema</button>
            <button class="seccion-toggle" onclick="mostrarSeccion('alumnos')">Estatus de Alumnos</button>
            <button class="seccion-toggle" onclick="mostrarSeccion('maestros')">Estatus de Maestros</button>
        </div>
        
        <!-- Sección: Usuarios del Sistema -->
        <div id="seccion-usuarios" class="seccion-contenido active">
            <!-- Formulario para crear/editar usuario -->
            <div class="card">
                <h2>
                    <?php echo $usuario_editar ? ' Editar Usuario' : ' Crear Nuevo Usuario'; ?>
                </h2>
                
                <form method="POST" id="formUsuario">
                    <?php if ($usuario_editar): ?>
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id" value="<?php echo $usuario_editar['id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="accion" value="crear">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Nombre de Usuario:</label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   required
                                   value="<?php echo $usuario_editar ? htmlspecialchars($usuario_editar['username']) : ''; ?>"
                                   placeholder="Ej: admin, maestro01, alumno001">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">
                                Contrase&ntilde;a <?php echo $usuario_editar ? '(dejar en blanco para no cambiar)' : ''; ?>:
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password"
                                   <?php echo !$usuario_editar ? 'required' : ''; ?>
                                   placeholder="M&iacute;nimo 6 caracteres">
                        </div>
                        
                        <div class="form-group">
                            <label for="rol">Rol:</label>
                            <select id="rol" name="rol" required>
                                <option value="">Seleccionar rol</option>
                                <option value="admin" <?php echo ($usuario_editar && $usuario_editar['rol'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                <option value="maestro" <?php echo ($usuario_editar && $usuario_editar['rol'] === 'maestro') ? 'selected' : ''; ?>>Maestro</option>
                                <option value="alumno" <?php echo ($usuario_editar && $usuario_editar['rol'] === 'alumno') ? 'selected' : ''; ?>>Alumno</option>
                                <option value="usuario" <?php echo ($usuario_editar && $usuario_editar['rol'] === 'usuario') ? 'selected' : ''; ?>>Usuario General</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" 
                                       id="activo" 
                                       name="activo" 
                                       value="1"
                                       <?php echo (!$usuario_editar || $usuario_editar['activo'] == 1) ? 'checked' : ''; ?>>
                                <label for="activo">Usuario Activo</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $usuario_editar ? ' Actualizar Usuario' : ' Crear Usuario'; ?>
                        </button>
                        
                        <?php if ($usuario_editar): ?>
                            <a href="gestion_usuarios.php" class="btn btn-warning"> Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Lista de usuarios -->
            <div class="card">
                <h2> Usuarios del Sistema</h2>
                <p><strong>Total:</strong> <?php echo count($usuarios); ?> usuarios registrados</p>
                
                <div class="tabla-container">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha Creaci&oacute;n</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                                <td>
                                    <span class="badge-<?php echo $usuario['rol']; ?>">
                                        <?php echo htmlspecialchars($usuario['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="estado <?php echo $usuario['activo'] ? 'estado-activo' : 'estado-inactivo'; ?>">
                                        <?php echo $usuario['activo'] ? ' Activo' : ' Inactivo'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                <td>
                                    <div class="acciones">
                                        <a href="gestion_usuarios.php?editar=<?php echo $usuario['id']; ?>" 
                                           class="btn btn-secondary btn-sm"> Editar</a>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="accion" value="cambiar_estado">
                                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                            <input type="hidden" name="activo" value="<?php echo $usuario['activo']; ?>">
                                            <button type="submit" 
                                                    class="btn <?php echo $usuario['activo'] ? 'btn-warning' : 'btn-secondary'; ?> btn-sm"
                                                    onclick="return confirm('¿Cambiar estado del usuario <?php echo $usuario['username']; ?>?')">
                                                <?php echo $usuario['activo'] ? ' Desactivar' : ' Activar'; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                            <button type="submit" 
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('¿Eliminar usuario <?php echo $usuario['username']; ?> permanentemente?')">
                                                 Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Sección: Estatus de Alumnos -->
        <div id="seccion-alumnos" class="seccion-contenido">
            <div class="card">
                <h2> Gesti&oacute;n de Estatus - Alumnos</h2>
                <p><strong>Total:</strong> <?php echo count($alumnos); ?> alumnos registrados</p>
                
                <div class="tabla-container">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Matr&iacute;cula</th>
                                <th>Nombre Completo</th>
                                <th>Carrera</th>
                                <th>Fecha Ingreso</th>
                                <th>Estado Actual</th>
                                <th>Cambiar Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumnos as $alumno): ?>
                            <tr>
                                <td><?php echo $alumno['id_alumno']; ?></td>
                                <td><?php echo htmlspecialchars($alumno['matricula']); ?></td>
                                <td><?php echo htmlspecialchars($alumno['nombre_completo']); ?></td>
                                <td><?php echo $alumno['id_carrera'] ? 'Carrera ' . $alumno['id_carrera'] : 'N/A'; ?></td>
                                <td><?php echo $alumno['fecha_ingreso'] ? date('d/m/Y', strtotime($alumno['fecha_ingreso'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="estado <?php echo $alumno['activo'] === 'Activo' ? 'estado-activo' : 'estado-inactivo'; ?>">
                                        <?php echo htmlspecialchars($alumno['activo']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="accion" value="cambiar_estado_alumno">
                                        <input type="hidden" name="id_alumno" value="<?php echo $alumno['id_alumno']; ?>">
                                        <input type="hidden" name="matricula" value="<?php echo $alumno['matricula']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="<?php echo $alumno['activo'] === 'Activo' ? 'Inactivo' : 'Activo'; ?>">
                                        <button type="submit" 
                                                class="btn <?php echo $alumno['activo'] === 'Activo' ? 'btn-warning' : 'btn-secondary'; ?> btn-sm"
                                                onclick="return confirm('¿Cambiar estado del alumno <?php echo $alumno['matricula']; ?>?')">
                                            <?php echo $alumno['activo'] === 'Activo' ? ' Inactivar' : ' Activar'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Sección: Estatus de Maestros -->
        <div id="seccion-maestros" class="seccion-contenido">
            <div class="card">
                <h2> Gesti&oacute;n de Estatus - Maestros</h2>
                <p><strong>Total:</strong> <?php echo count($maestros); ?> maestros registrados</p>
                
                <div class="tabla-container">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>N° Empleado</th>
                                <th>Nombre Completo</th>
                                <th>Fecha Alta</th>
                                <th>Estado Actual</th>
                                <th>Cambiar Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($maestros as $maestro): ?>
                            <tr>
                                <td><?php echo $maestro['id_maestro']; ?></td>
                                <td><?php echo htmlspecialchars($maestro['numEmpleado']); ?></td>
                                <td><?php echo htmlspecialchars($maestro['nombre_completo']); ?></td>
                                <td><?php echo $maestro['fechaAlta'] ? date('d/m/Y', strtotime($maestro['fechaAlta'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="estado <?php echo $maestro['activo'] === 'Activo' ? 'estado-activo' : 'estado-inactivo'; ?>">
                                        <?php echo htmlspecialchars($maestro['activo']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="accion" value="cambiar_estado_maestro">
                                        <input type="hidden" name="id_maestro" value="<?php echo $maestro['id_maestro']; ?>">
                                        <input type="hidden" name="numEmpleado" value="<?php echo $maestro['numEmpleado']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="<?php echo $maestro['activo'] === 'Activo' ? 'Inactivo' : 'Activo'; ?>">
                                        <button type="submit" 
                                                class="btn <?php echo $maestro['activo'] === 'Activo' ? 'btn-warning' : 'btn-secondary'; ?> btn-sm"
                                                onclick="return confirm('¿Cambiar estado del maestro <?php echo $maestro['numEmpleado']; ?>?')">
                                            <?php echo $maestro['activo'] === 'Activo' ? ' Inactivar' : ' Activar'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Función para mostrar/ocultar secciones
        function mostrarSeccion(seccion) {
            // Ocultar todas las secciones
            document.querySelectorAll('.seccion-contenido').forEach(function(div) {
                div.classList.remove('active');
            });
            
            // Mostrar la sección seleccionada
            document.getElementById('seccion-' + seccion).classList.add('active');
            
            // Actualizar botones activos
            document.querySelectorAll('.seccion-toggle').forEach(function(boton) {
                boton.classList.remove('active');
            });
            
            // Marcar el botón correspondiente como activo
            event.target.classList.add('active');
            
            // Guardar preferencia en localStorage
            localStorage.setItem('seccion-activa', seccion);
        }
        
        // Cargar sección activa desde localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const seccionGuardada = localStorage.getItem('seccion-activa');
            if (seccionGuardada) {
                mostrarSeccion({target: document.querySelector(`[onclick="mostrarSeccion('${seccionGuardada}')"]`)});
            }
            
            // Validación del formulario de usuarios
            const form = document.getElementById('formUsuario');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const password = document.getElementById('password').value;
                    const rol = document.getElementById('rol').value;
                    
                    if (!<?php echo $usuario_editar ? 'false' : 'true'; ?> && password && password.length < 6) {
                        alert('La contrase&ntilde;a debe tener al menos 6 caracteres');
                        e.preventDefault();
                        return false;
                    }
                    
                    if (!rol) {
                        alert('Por favor selecciona un rol');
                        e.preventDefault();
                        return false;
                    }
                    
                    // Mostrar indicador de carga
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '? Procesando...';
                });
            }
            
            // Scroll suave al formulario si estamos editando
            <?php if ($usuario_editar): ?>
                document.querySelector('.card:first-child').scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            <?php endif; ?>
            
            // Añadir animación a las filas de la tabla
            const tablaRows = document.querySelectorAll('.tabla tbody tr');
            tablaRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.05}s`;
                row.style.animation = 'fadeIn 0.5s ease-out';
            });
        });
    </script>
</body>
</html>