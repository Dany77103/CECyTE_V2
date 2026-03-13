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
    <title>Gestión de Usuarios - CECYTE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
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
        }
        
        .mensaje.exito {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .card h2 {
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
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
            color: #4a5568;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
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
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #48bb78;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #38a169;
        }
        
        .btn-danger {
            background: #f56565;
            color: white;
        }
        
        .btn-danger:hover {
            background: #e53e3e;
        }
        
        .btn-warning {
            background: #ed8936;
            color: white;
        }
        
        .btn-warning:hover {
            background: #dd6b20;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .tabla-container {
            overflow-x: auto;
        }
        
        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .tabla th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .tabla td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .tabla tr:hover {
            background: #f7fafc;
        }
        
        .estado {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .estado-activo {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .estado-inactivo {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .acciones {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #718096;
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
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CECYTE - Gestión de Usuarios</h1>
        <div class="nav-links">
            <a href="main.php">? Volver al Panel</a>
            <a href="gestion_maestros.php">Gestión de Maestros</a>
            <a href="gestion_alumnos.php">Gestión de Alumnos</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulario para crear/editar usuario -->
        <div class="card">
            <h2>
                <?php echo $usuario_editar ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?>
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
                               value="<?php echo $usuario_editar ? htmlspecialchars($usuario_editar['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            Contraseña <?php echo $usuario_editar ? '(dejar en blanco para no cambiar)' : ''; ?>:
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password"
                               <?php echo !$usuario_editar ? 'required' : ''; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="rol">Rol:</label>
                        <select id="rol" name="rol" required>
                            <option value="">Seleccionar rol</option>
                            <option value="admin" <?php echo ($usuario_editar && $usuario_editar['rol'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
							<option value="maestro" <?php echo ($usuario_editar && $usuario_editar['rol'] === 'maestro') ? 'selected' : ''; ?>>Maestro</option>
							<option value="alumno" <?php echo ($usuario_editar && $usuario_editar['rol'] === 'alumno') ? 'selected' : ''; ?>>Alumno</option>
                            <option value="usuario" <?php echo ($usuario_editar && $usuario_editar['rol'] === 'usuario') ? 'selected' : ''; ?>>Usuario</option>
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
                        <?php echo $usuario_editar ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                    </button>
                    
                    <?php if ($usuario_editar): ?>
                        <a href="gestion_usuarios.php" class="btn btn-warning">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Lista de usuarios -->
        <div class="card">
            <h2>Usuarios del Sistema</h2>
            <p>Total: <?php echo count($usuarios); ?> usuarios registrados</p>
            
            <div class="tabla-container">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo $usuario['id']; ?></td>
                            <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                            <td>
                                <span class="estado <?php echo $usuario['rol'] === 'admin' ? 'estado-activo' : ''; ?>">
                                    <?php echo htmlspecialchars($usuario['rol']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="estado <?php echo $usuario['activo'] ? 'estado-activo' : 'estado-inactivo'; ?>">
                                    <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                            <td>
                                <div class="acciones">
                                    <a href="gestion_usuarios.php?editar=<?php echo $usuario['id']; ?>" 
                                       class="btn btn-secondary btn-sm">Editar</a>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="accion" value="cambiar_estado">
                                        <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                        <input type="hidden" name="activo" value="<?php echo $usuario['activo']; ?>">
                                        <button type="submit" 
                                                class="btn <?php echo $usuario['activo'] ? 'btn-warning' : 'btn-secondary'; ?> btn-sm"
                                                onclick="return confirm('¿Cambiar estado del usuario?')">
                                            <?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                        <button type="submit" 
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('¿Eliminar usuario permanentemente?')">
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
    
    <script>
        // Confirmación para acciones importantes
        document.addEventListener('DOMContentLoaded', function() {
            // Validación del formulario
            const form = document.getElementById('formUsuario');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const password = document.getElementById('password').value;
                    const rol = document.getElementById('rol').value;
                    
                    if (password && password.length < 6) {
                        alert('La contraseña debe tener al menos 6 caracteres');
                        e.preventDefault();
                        return false;
                    }
                    
                    if (!rol) {
                        alert('Por favor selecciona un rol');
                        e.preventDefault();
                        return false;
                    }
                });
            }
            
            // Scroll suave al formulario si estamos editando
            <?php if ($usuario_editar): ?>
                document.querySelector('.card:first-child').scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>