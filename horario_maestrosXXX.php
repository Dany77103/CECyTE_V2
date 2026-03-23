<?php
// horario_maestrosXXX.php
session_start();

// 1. INCLUSIÓN DE CONEXIÓN
require_once 'config.php';
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['username']) || $_SESSION['rol'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Obtener el nombre de ESTE archivo dinámicamente para evitar el error 404
$nombre_archivo_actual = basename(__FILE__);

// 2. CONFIGURACIÓN DE BLOQUES
$bloques = [
    ["11:45:00", "12:45:00"],
    ["12:45:00", "13:45:00"],
    ["13:45:00", "14:45:00"],
    ["14:45:00", "15:15:00"], // Receso
    ["15:15:00", "16:15:00"],
    ["16:15:00", "17:15:00"],
    ["17:15:00", "17:50:00"]
];
$dias_nombres = ["Lunes", "Martes", "Miercoles", "Jueves", "Viernes"];

// 3. PROCESAR GUARDADO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_maestro'])) {
    $id_maestro = intval($_POST['id_maestro']);
    $periodo = "FEB 2026-JUL 2026";

    try {
        $con->beginTransaction();
        
        // Limpiar horario previo
        $del = $con->prepare("DELETE FROM horarios_maestros WHERE id_maestro = ? AND periodo = ?");
        $del->execute([$id_maestro, $periodo]);

        $ins = $con->prepare("INSERT INTO horarios_maestros (id_maestro, id_materia, dia, hora_inicio, hora_fin, id_aula, id_grupo, periodo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        if (isset($_POST['horario'])) {
            foreach ($_POST['horario'] as $dia => $bloques_data) {
                foreach ($bloques_data as $idx => $materia_id) {
                    if (!empty($materia_id)) {
                        $hora = $bloques[$idx];
                        $ins->execute([$id_maestro, $materia_id, $dia, $hora[0], $hora[1], 1, 1, $periodo]);
                    }
                }
            }
        }
        $con->commit();
        $_SESSION['mensaje'] = "¡Horario guardado correctamente!";
        
        // REDIRECCIÓN DINÁMICA: Esto evita el error 404
        header("Location: " . $nombre_archivo_actual . "?maestro=" . $id_maestro);
        exit();
    } catch (Exception $e) {
        if ($con->inTransaction()) $con->rollBack();
        die("Error al guardar: " . $e->getMessage());
    }
}

// 4. CARGAR DATOS PARA LA VISTA
$maestros = $con->query("SELECT * FROM maestros WHERE activo = 'Activo' ORDER BY apellido_paterno")->fetchAll(PDO::FETCH_ASSOC);
$materias_db = $con->query("SELECT * FROM materias ORDER BY materia")->fetchAll(PDO::FETCH_ASSOC);

$horario_actual = [];
if (isset($_GET['maestro'])) {
    $stmt = $con->prepare("SELECT * FROM horarios_maestros WHERE id_maestro = ? AND periodo = 'FEB 2026-JUL 2026'");
    $stmt->execute([intval($_GET['maestro'])]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $horario_actual[$row['dia']][$row['hora_inicio']] = $row['id_materia'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captura y Vista de Horarios | CECyTE SC</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <style>
        :root { --primary-color: #064e3b; --accent-color: #10b981; --bg-body: #f1f5f9; }
        body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; color: #1e293b; }
        .btn-back-home { background: white; color: var(--primary-color); border: 2px solid var(--primary-color); padding: 8px 18px; border-radius: 12px; font-weight: 600; text-decoration: none; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .btn-back-home:hover { background: var(--primary-color); color: white; transform: translateX(-5px); }
        .section-title { font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 10px; }
        .glass-card { background: white; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: none; padding: 25px; margin-bottom: 30px; }
        .table-capture th { font-size: 0.7rem; text-transform: uppercase; color: #64748b; text-align: center; }
        .slot-selector { border: 2px solid #f1f5f9; background: #f8fafc; font-size: 0.75rem; width: 100%; padding: 8px; border-radius: 10px; transition: 0.3s; }
        .slot-selector:focus { border-color: var(--accent-color); outline: none; background: white; }
        .table-view { width: 100%; border-collapse: separate; border-spacing: 8px; }
        .table-view th { color: #64748b; font-size: 0.8rem; text-align: center; padding: 10px; }
        .table-view td { background: #fff; border-radius: 12px; height: 90px; width: 18%; vertical-align: middle; text-align: center; border: 1px solid #e2e8f0; transition: all 0.4s ease; }
        .view-time { background: #f8fafc !important; font-weight: 700; color: var(--primary-color); font-size: 0.75rem; width: 10% !important; border: none !important; }
        .preview-subject { font-weight: 700; font-size: 0.75rem; color: var(--primary-color); display: block; line-height: 1.2; }
        .preview-room { font-size: 0.65rem; color: #64748b; margin-top: 4px; display: block; }
        .receso-bar { background: #f1f5f9 !important; font-weight: 800; letter-spacing: 10px; color: #cbd5e1; font-size: 0.7rem; height: 40px !important; border: none !important;}
        .btn-capture { background: var(--primary-color); color: white; border: none; padding: 12px 35px; border-radius: 12px; font-weight: 700; transition: 0.3s; }
        .btn-capture:hover { background: var(--accent-color); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3); }
    </style>
</head>
<body>

<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="section-title">
            <i class='bx bxs-edit-alt fs-2'></i> 
            <h3 class="mb-0">Gestor de Horarios Vespertinos</h3>
        </div>
        <a href="main.php" class="btn-back-home">
            <i class='bx bx-arrow-back'></i> Regresar
        </a>
    </div>

    <?php if(isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show animate__animated animate__headShake">
            <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="glass-card mb-4">
        <form method="GET" action="">
            <label class="form-label fw-bold small">Seleccionar Maestro para Capturar:</label>
            <select name="maestro" class="form-select" onchange="this.form.submit()">
                <option value="">-- Seleccione un docente --</option>
                <?php foreach($maestros as $maes): ?>
                    <option value="<?= $maes['id_maestro'] ?>" <?= (isset($_GET['maestro']) && $_GET['maestro'] == $maes['id_maestro']) ? 'selected' : '' ?>>
                        <?= $maes['apellido_paterno'] . " " . $maes['nombre'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if(isset($_GET['maestro'])): ?>
    <div class="glass-card animate__animated animate__fadeIn">
        <form id="horarioForm" method="POST">
            <input type="hidden" name="id_maestro" value="<?= $_GET['maestro'] ?>">
            <div class="table-responsive">
                <table class="table table-borderless align-middle table-capture">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Rango Horario</th>
                            <?php foreach($dias_nombres as $dia): ?> <th><?php echo $dia; ?></th> <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bloques as $idx => $hora): ?>
                            <?php if($idx == 3): ?>
                                <tr class="text-center"><td colspan="6" class="py-3 bg-light rounded-4 small text-muted fw-bold">-- INTERVALO DE RECESO --</td></tr>
                            <?php else: ?>
                                <tr>
                                    <td class="fw-bold small text-success"><?php echo $hora[0] . " - " . $hora[1]; ?></td>
                                    <?php foreach($dias_nombres as $dia): 
                                        $val_actual = $horario_actual[$dia][$hora[0]] ?? '';
                                    ?>
                                    <td>
                                        <select name="horario[<?= $dia ?>][<?= $idx ?>]" class="slot-selector materia-select" data-hora="<?php echo $idx; ?>" data-dia="<?php echo $dia; ?>" onchange="updatePreview(this)">
                                            <option value="">Vacío</option>
                                            <?php foreach($materias_db as $m): ?>
                                                <option value="<?php echo $m['id_materia']; ?>" 
                                                        <?php echo ($val_actual == $m['id_materia']) ? 'selected' : ''; ?>
                                                        data-nombre="<?php echo $m['materia']; ?>" 
                                                        data-aula="Aula" 
                                                        data-color="<?= $m['color'] ?? '#dbeafe' ?>">
                                                    <?php echo $m['materia']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-4">
                <button type="submit" class="btn-capture">
                    <i class='bx bx-save me-1'></i> Guardar en Base de Datos
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="section-title mb-3">
        <i class='bx bx-calendar-check fs-3'></i> 
        <span>Vista Previa del Horario</span>
    </div>

    <div class="glass-card animate__animated animate__fadeInUp">
        <div class="table-responsive">
            <table class="table-view" id="horarioPreview">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <?php foreach($dias_nombres as $dia): ?> <th><?php echo $dia; ?></th> <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bloques as $idx => $hora): ?>
                        <?php if($idx == 3): ?>
                            <tr>
                                <td class="view-time">14:45</td>
                                <td colspan="5" class="receso-bar">RECESO</td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td class="view-time"><?php echo substr($hora[0], 0, 5); ?></td>
                                <?php foreach($dias_nombres as $dia): ?>
                                    <td id="preview-<?php echo $idx; ?>-<?php echo $dia; ?>">
                                        <span class="text-muted small" style="opacity: 0.2;">--</span>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function updatePreview(select) {
        const hora = select.getAttribute('data-hora');
        const dia = select.getAttribute('data-dia');
        const targetId = `preview-${hora}-${dia}`;
        const targetCell = document.getElementById(targetId);
        const selectedOption = select.options[select.selectedIndex];
        
        if (select.value === "") {
            targetCell.innerHTML = '<span class="text-muted small" style="opacity: 0.2;">--</span>';
            targetCell.style.backgroundColor = "#fff";
        } else {
            const nombre = selectedOption.getAttribute('data-nombre');
            const color = selectedOption.getAttribute('data-color');
            targetCell.style.backgroundColor = color;
            targetCell.innerHTML = `
                <div class="preview-block animate__animated animate__pulse">
                    <span class="preview-subject">${nombre}</span>
                </div>
            `;
        }
    }

    // Inicializar vista previa al cargar
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.materia-select').forEach(select => {
            if(select.value !== "") updatePreview(select);
        });
    });
</script>

</body>
</html>