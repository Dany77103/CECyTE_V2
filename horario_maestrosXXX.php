<?php
session_start();

$materias = [
    ['id' => 1, 'nombre' => 'Base de Datos', 'profe' => 'Ing. Mario Hdez', 'aula' => 'Lab 2', 'color' => '#dcfce7'],
    ['id' => 2, 'nombre' => 'Desarrollo Web', 'profe' => 'Ing. R. Silva', 'aula' => 'Lab 1', 'color' => '#dbeafe'],
    ['id' => 3, 'nombre' => 'Cálculo Integral', 'profe' => 'Ing. P. Gómez', 'aula' => 'Aula 3', 'color' => '#fef9c3'],
    ['id' => 4, 'nombre' => 'Inglés IV', 'profe' => 'Lic. Ana Ruiz', 'aula' => 'Aula 5', 'color' => '#ffedd5'],
    ['id' => 5, 'nombre' => 'Ecología', 'profe' => 'Biol. Morales', 'aula' => 'Aula 12', 'color' => '#f3e8ff'],
];

$bloques = [
    "11:45 - 12:45",
    "12:45 - 13:45",
    "13:45 - 14:45",
    "14:45 - 15:15", // Receso
    "15:15 - 16:15",
    "16:15 - 17:15",
    "17:15 - 17:50"
];

$dias = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes"];
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
        
        /* Botón Regresar Personalizado */
        .btn-back-home {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 8px 18px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back-home:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(-5px);
        }

        .section-title { font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 10px; }
        .glass-card { background: white; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: none; padding: 25px; margin-bottom: 30px; }
        
        /* Selectores de Captura */
        .table-capture th { font-size: 0.7rem; text-transform: uppercase; color: #64748b; text-align: center; }
        .slot-selector {
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            font-size: 0.75rem;
            width: 100%;
            padding: 8px;
            border-radius: 10px;
            transition: 0.3s;
        }
        .slot-selector:focus { border-color: var(--accent-color); outline: none; background: white; }

        /* Tabla de Vista Previa */
        .table-view { width: 100%; border-collapse: separate; border-spacing: 8px; }
        .table-view th { color: #64748b; font-size: 0.8rem; text-align: center; padding: 10px; }
        .table-view td { 
            background: #fff; 
            border-radius: 12px; 
            height: 90px; 
            width: 18%; 
            vertical-align: middle; 
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.4s ease;
        }

        .view-time { background: #f8fafc !important; font-weight: 700; color: var(--primary-color); font-size: 0.75rem; width: 10% !important; border: none !important; }
        .preview-block { padding: 5px; animation: fadeIn 0.5s; }
        .preview-subject { font-weight: 700; font-size: 0.75rem; color: var(--primary-color); display: block; line-height: 1.2; }
        .preview-room { font-size: 0.65rem; color: #64748b; margin-top: 4px; display: block; }
        .receso-bar { background: #f1f5f9 !important; font-weight: 800; letter-spacing: 10px; color: #cbd5e1; font-size: 0.7rem; height: 40px !important; border: none !important;}

        .btn-capture {
            background: var(--primary-color); color: white; border: none; padding: 12px 35px;
            border-radius: 12px; font-weight: 700; transition: 0.3s;
        }
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
            <i class='bx bx-arrow-back'></i> Regresar al Menú
        </a>
    </div>

    <div class="glass-card animate__animated animate__fadeIn">
        <p class="text-muted small mb-4"><i class='bx bx-info-circle'></i> Selecciona las materias en cada bloque para ver la vista previa debajo.</p>
        <form id="horarioForm">
            <div class="table-responsive">
                <table class="table table-borderless align-middle table-capture">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Rango Horario</th>
                            <?php foreach($dias as $dia): ?> <th><?php echo $dia; ?></th> <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bloques as $idx => $hora): ?>
                            <?php if($idx == 3): ?>
                                <tr class="text-center"><td colspan="6" class="py-3 bg-light rounded-4 small text-muted fw-bold">-- INTERVALO DE RECESO --</td></tr>
                            <?php else: ?>
                                <tr>
                                    <td class="fw-bold small text-success"><?php echo $hora; ?></td>
                                    <?php for($d=0; $d<5; $d++): ?>
                                    <td>
                                        <select class="slot-selector" data-hora="<?php echo $idx; ?>" data-dia="<?php echo $d; ?>" onchange="updatePreview(this)">
                                            <option value="">Vacío</option>
                                            <?php foreach($materias as $m): ?>
                                                <option value="<?php echo $m['id']; ?>" 
                                                        data-nombre="<?php echo $m['nombre']; ?>" 
                                                        data-aula="<?php echo $m['aula']; ?>" 
                                                        data-color="<?php echo $m['color']; ?>">
                                                    <?php echo $m['nombre']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-4">
                <button type="button" class="btn-capture" onclick="alert('Horario capturado con éxito')">
                    <i class='bx bx-save me-1'></i> Finalizar Captura
                </button>
            </div>
        </form>
    </div>

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
                        <?php foreach($dias as $dia): ?> <th><?php echo $dia; ?></th> <?php endforeach; ?>
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
                                <td class="view-time"><?php echo explode(' ', $hora)[0]; ?></td>
                                <?php for($d=0; $d<5; $d++): ?>
                                    <td id="preview-<?php echo $idx; ?>-<?php echo $d; ?>">
                                        <span class="text-muted small" style="opacity: 0.2;">--</span>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Función para actualizar la tabla de abajo en tiempo real
    function updatePreview(select) {
        const hora = select.getAttribute('data-hora');
        const dia = select.getAttribute('data-dia');
        const targetId = `preview-${hora}-${dia}`;
        const targetCell = document.getElementById(targetId);
        
        const selectedOption = select.options[select.selectedIndex];
        
        if (select.value === "") {
            targetCell.innerHTML = '<span class="text-muted small" style="opacity: 0.2;">--</span>';
            targetCell.style.backgroundColor = "#fff";
            targetCell.style.borderColor = "#e2e8f0";
        } else {
            const nombre = selectedOption.getAttribute('data-nombre');
            const aula = selectedOption.getAttribute('data-aula');
            const color = selectedOption.getAttribute('data-color');
            
            targetCell.style.backgroundColor = color;
            targetCell.style.borderColor = color;
            targetCell.innerHTML = `
                <div class="preview-block animate__animated animate__pulse">
                    <span class="preview-subject">${nombre}</span>
                    <span class="preview-room"><i class='bx bx-map-pin'></i> ${aula}</span>
                </div>
            `;
        }
    }
</script>

</body>
</html>