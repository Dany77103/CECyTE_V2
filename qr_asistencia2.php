<?php
ob_start();
// Configuración de la base de datos de CECyTE SC
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cecyte_sc";

try {
    $con = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $con->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
session_start();

// --- LÓGICA DE GRUPOS ADAPTADA ---
$grupos = [];
try {
    // Intentamos traer los grupos para el selector del diseño
    $sql_grupos = "SELECT id, nombre FROM grupos ORDER BY nombre ASC";
    $stmt_grupos = $con->prepare($sql_grupos);
    $stmt_grupos->execute();
    $grupos = $stmt_grupos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la tabla grupos no existe aún, usamos un valor por defecto para no romper el diseño
    $grupos = [['id' => 1, 'nombre' => 'General']];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Físico | CECyTE SC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        :root { --primary-color: #064e3b; --accent-color: #10b981; --corp-red: #be123c; --bg-light: #f1f5f9; }
        body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; }
        .navbar-custom { background: #ffffff; border-bottom: 3px solid var(--accent-color); padding: 1rem 0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card-custom { border: none; border-radius: 20px; background: #ffffff; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .scanner-visual { background: #f8fafc; border: 3px dashed #cbd5e1; border-radius: 20px; padding: 40px; transition: 0.3s; }
        .mode-entrada .scanner-visual { border-color: var(--accent-color); background: rgba(16, 185, 129, 0.05); }
        .mode-salida .scanner-visual { border-color: var(--corp-red); background: rgba(190, 18, 60, 0.05); }
        #physicalScannerInput { position: absolute; opacity: 0; top: 0; left: 0; }
        .btn-mode { border: none; padding: 10px 25px; border-radius: 8px; font-weight: 700; color: #64748b; background: transparent; }
        .btn-mode.active-in { background: var(--accent-color); color: white !important; }
        .btn-mode.active-out { background: var(--corp-red); color: white !important; }
        .stat-card { padding: 2rem; border-left: 5px solid var(--primary-color); }
        .stat-number { font-size: 3.5rem; font-weight: 800; color: var(--primary-color); }
        .badge-entrada { background: rgba(16, 185, 129, 0.1); color: #065f46; font-weight: 700; }
        .badge-salida { background: rgba(190, 18, 60, 0.1); color: #9f1239; font-weight: 700; }
        
        .room-selector { 
            max-height: 150px; 
            overflow-y: auto; 
            padding: 10px;
            background: #fff;
            border-radius: 12px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }
        .btn-room { margin: 2px; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        .manual-input { border-radius: 10px; border: 1px solid #dee2e6; padding: 10px; margin-bottom: 10px; }
        .btn-manual-submit { background-color: var(--primary-color); color: white; border-radius: 10px; width: 100%; padding: 12px; border: none; }
    </style>
</head>
<body class="mode-entrada">
    <input type="text" id="physicalScannerInput" autofocus autocomplete="off">

    <nav class="navbar navbar-custom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class='bx bxs-barcode-reader fs-3 me-2'></i>
                <span>CECyTE SC <span class="fw-light text-muted">| Gestión de Asistencia</span></span>
            </a>
            <div class="mode-selector-corp">
                <button class="btn-mode active-in" id="btnModoEntrada">ENTRADA</button>
                <button class="btn-mode" id="btnModoSalida">SALIDA</button>
            </div>
            <a href="main.php" class="btn btn-outline-secondary btn-sm">VOLVER</a>
        </div>
    </nav>

    <div class="container mt-5">
        <div id="alertContainer"></div>
        
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card card-custom h-100">
                    <div class="card-body p-5 text-center">
                        <div class="scanner-visual mb-4">
                            <i class='bx bx-barcode-reader display-1 mb-3' style="color: #64748b;"></i>
                            <h3 class="fw-bold" id="tituloEscaner">ESPERANDO ESCANEO...</h3>
                            <p class="text-muted">Escanee el código QR o Matrícula</p>
                            <div id="statusPulse" class="spinner-grow text-success" role="status"></div>
                        </div>

                        <div class="room-selector mb-2">
                            <p class="small text-muted fw-bold mb-2">UBICACIÓN ACTUAL:</p>
                            <div class="btn-group flex-wrap w-100" id="roomButtonGroup">
                                <?php foreach ($grupos as $index => $g): ?>
                                    <button class="btn btn-outline-dark btn-sm btn-room <?= $index === 0 ? 'active' : '' ?>" 
                                            data-room-id="<?= $g['id'] ?>"
                                            data-room-name="<?= htmlspecialchars($g['nombre']) ?>">
                                        <?= htmlspecialchars($g['nombre']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="salonSeleccionadoID" value="<?= $grupos[0]['id'] ?? '' ?>">
                            <input type="hidden" id="salonSeleccionadoNombre" value="<?= htmlspecialchars($grupos[0]['nombre'] ?? 'General') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card card-custom stat-card mb-4">
                    <p class="text-muted small fw-bold mb-0">ASISTENCIAS HOY</p>
                    <h2 id="totalHoy" class="stat-number">0</h2>
                </div>
                
                <div class="card card-custom">
                    <div class="card-body p-4 text-center">
                        <h6 class="fw-bold mb-3"><i class='bx bx-user-pin me-2'></i>Estado del Sistema</h6>
                        <div class="p-3 bg-light rounded-3 text-start small">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Servidor:</span> <span class="text-success fw-bold">Activo</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Campus:</span> <span class="fw-bold">Santa Catarina</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-custom mt-5 mb-5">
            <div class="card-body">
                <h5 class="fw-bold mb-4"><i class='bx bx-history me-2'></i>Últimos Registros</h5>
                <div class="table-responsive">
                    <table class="table" id="tablaAsistencias">
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Nombre</th>
                                <th>Hora</th>
                                <th class="text-end">Tipo</th>
                            </tr>
                        </thead>
                        <tbody id="asistenciasBody">
                            <tr><td colspan="4" class="text-center py-3">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let modoActual = 'entrada';
        const inputFisico = $('#physicalScannerInput');

        // Selección de ubicación/grupo
        $('.btn-room').click(function() {
            $('.btn-room').removeClass('active');
            $(this).addClass('active');
            $('#salonSeleccionadoID').val($(this).data('room-id'));
            $('#salonSeleccionadoNombre').val($(this).data('room-name'));
        });

        function procesarRegistro(codigo) {
            const salon = $('#salonSeleccionadoNombre').val();
            
            $('.scanner-visual').css('opacity', '0.5');

            $.ajax({
                url: 'procesar_qr.php',
                type: 'POST',
                data: { 
                    codigo_qr: codigo, 
                    action: 'registrar', 
                    tipo_registro: modoActual,
                    salon: salon
                },
                success: function(response) {
                    $('.scanner-visual').css('opacity', '1');
                    try {
                        // Manejo robusto de la respuesta JSON
                        const data = (typeof response === 'object') ? response : JSON.parse(response);
                        showAlert(data.message, data.success ? 'success' : 'danger');
                        actualizarEstadisticas();
                        cargarHistorial();
                    } catch(e) { 
                        console.error("Error respuesta:", response);
                        showAlert('Error en proceso de datos', 'danger'); 
                    }
                }
            });
        }

        inputFisico.on('keypress', function(e) {
            if (e.which == 13) {
                const codigo = $(this).val().trim();
                if (codigo !== "") procesarRegistro(codigo);
                $(this).val("");
            }
        });

        function showAlert(m, t) {
            $('#alertContainer').html(`<div class="alert alert-${t} fw-bold text-center shadow-sm">${m}</div>`).show();
            setTimeout(() => $("#alertContainer").fadeOut(), 3000);
        }

        function actualizarEstadisticas() {
            $.get('procesar_qr.php', { action: 'get_stats' }, function(res) {
                try {
                    const s = (typeof res === 'object') ? res : JSON.parse(res);
                    $('#totalHoy').text(s.total_hoy || 0);
                } catch(e) {}
            });
        }

        function cargarHistorial() {
            $.get('procesar_qr.php', { action: 'get_asistencias' }, function(res) {
                try {
                    const asistencias = (typeof res === 'object') ? res : JSON.parse(res);
                    let html = '';
                    if(asistencias.length > 0) {
                        asistencias.forEach(r => {
                            const esSalida = r.hora_salida && r.hora_salida !== '00:00:00';
                            const tipoLabel = esSalida ? 'SALIDA' : 'ENTRADA';
                            const badgeClass = esSalida ? 'badge-salida' : 'badge-entrada';
                            const horaMostrar = esSalida ? r.hora_salida : r.hora_entrada;

                            html += `<tr>
                                <td>${r.matricula}</td>
                                <td>${r.nombre}</td>
                                <td>${horaMostrar.substring(0,5)}</td>
                                <td class="text-end"><span class="badge ${badgeClass} px-3 py-2 rounded-pill">${tipoLabel}</span></td>
                            </tr>`;
                        });
                        $('#asistenciasBody').html(html);
                    } else {
                        $('#asistenciasBody').html('<tr><td colspan="4" class="text-center">No hay actividad hoy</td></tr>');
                    }
                } catch(e) {
                    console.error("Error historial:", res);
                }
            });
        }

        $(document).ready(function() {
            actualizarEstadisticas();
            cargarHistorial();
            // Mantiene el foco en el input invisible para el escáner físico
            setInterval(() => { if(!$('input:focus').length) inputFisico.focus(); }, 1000);
        });

        $('#btnModoEntrada').click(function() {
            modoActual = 'entrada';
            $('body').attr('class', 'mode-entrada');
            $(this).addClass('active-in'); 
            $('#btnModoSalida').removeClass('active-out');
        });

        $('#btnModoSalida').click(function() {
            modoActual = 'salida';
            $('body').attr('class', 'mode-salida');
            $(this).addClass('active-out'); 
            $('#btnModoEntrada').removeClass('active-in');
        });
    </script>
</body>
</html>