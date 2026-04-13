<?php
session_start();
$id_materia = $_GET['materia'] ?? null;
$id_grupo = $_GET['grupo'] ?? null;

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora en Tiempo Real | CECyTE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5330;
            --primary-light: #2e7d32;
            --secondary: #6c757d;
            --white: #ffffff;
            --bg: #f4f6f9;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            color: #333;
            padding-top: 90px;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .navbar {
            background: var(--white);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand { display: flex; align-items: center; gap: 15px; text-decoration: none; }
        .navbar-brand img { height: 45px; }
        .navbar-brand span { font-weight: 700; color: var(--primary); font-size: 1.2rem; }

        .container-fluid { 
            max-width: 1300px; 
            margin: 0 auto; 
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .glass-card {
            background: var(--white);
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-sm);
        }

        .welcome-section { 
            background: var(--white); 
            padding: 25px 30px; 
            border-radius: 20px; 
            margin-bottom: 25px; 
            box-shadow: var(--shadow-md); 
            border-left: 6px solid var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .scanner-box {
            border: 2px solid #eee;
            border-radius: 15px;
            padding: 20px;
        }

        #matricula-input {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            font-weight: 700;
            font-size: 1.5rem;
            text-align: center;
            color: var(--primary);
        }

        .manual-section {
            border-top: 1px dashed #ddd;
            margin-top: 20px;
            padding-top: 20px;
        }

        .btn-manual {
            color: var(--secondary);
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 600;
        }

        .table thead {
            background-color: var(--primary);
            color: white;
        }

        .badge-entrada { background: #e8f5e9; color: #2e7d32; padding: 6px 12px; border-radius: 8px; font-weight: 700; }
        .badge-salida { background: #fff3e0; color: #ef6c00; padding: 6px 12px; border-radius: 8px; font-weight: 700; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="CECyTE Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div class="user-controls">
            <a href="main.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-house"></i> Volver al Menú
            </a>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="welcome-section">
            <div class="welcome-text">
                <h2 style="color: var(--primary); font-weight: 700; margin: 0;">
                    <i class="fa-solid fa-qrcode me-2"></i> Bitácora en Tiempo Real
                </h2>
                <p class="text-secondary m-0">Registro de asistencia y movimientos</p>
            </div>
            <div class="stats-box text-end">
                <span class="text-secondary small fw-bold text-uppercase d-block">Alumnos en Salón</span>
                <span id="alumnos-salon" style="font-size: 2rem; font-weight: 800; color: var(--primary);">0</span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="glass-card p-4 h-100 shadow-sm">
                    <div class="btn-group w-100 mb-4 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                        <input type="radio" class="btn-check" name="modo" id="modo-entrada" value="Entrada" checked>
                        <label class="btn btn-outline-primary py-3 fw-bold border-0" for="modo-entrada">ENTRADA</label>

                        <input type="radio" class="btn-check" name="modo" id="modo-salida" value="Salida">
                        <label class="btn btn-outline-warning py-3 fw-bold text-dark border-0" for="modo-salida">SALIDA</label>
                    </div>

                    <div class="scanner-box text-center bg-light">
                        <h5 class="fw-bold text-secondary mb-3">Escanear Código QR</h5>
                        <form id="form-scanner">
                            <input type="text" id="matricula-input" class="form-control" placeholder="Esperando QR..." autofocus autocomplete="off">
                        </form>

                        <div id="status-msg" class="alert d-none mt-3 fw-bold shadow-sm" role="alert"></div>

                        <div class="manual-section">
                            <a class="btn-manual" data-bs-toggle="collapse" href="#collapseManual" role="button">
                                <i class="fa-solid fa-keyboard me-1"></i> Registro Manual
                            </a>
                            <div class="collapse mt-3" id="collapseManual">
                                <div class="card card-body border-0 bg-white shadow-sm p-3 text-start">
                                    <div class="mb-3">
                                        <label class="small fw-bold text-muted">Matrícula del Alumno:</label>
                                        <input type="text" id="manual-input" class="form-control form-control-sm" placeholder="Ej: 21340001">
                                    </div>
                                    <div class="mb-3">
                                        <label class="small fw-bold text-muted">Correo del Tutor (Opcional):</label>
                                        <input type="email" id="tutor-email" class="form-control form-control-sm" placeholder="tutor@ejemplo.com">
                                    </div>
                                    <button class="btn btn-primary btn-sm w-100 fw-bold" type="button" id="btn-registrar-manual">
                                        REGISTRAR MANUALMENTE
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="glass-card h-100 d-flex flex-column shadow-sm">
                    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="m-0 fw-bold">Actividad Reciente</h5>
                        <span class="badge bg-dark rounded-pill px-3 py-2" id="total-registros">0 Registros</span>
                    </div>
                    <div class="table-responsive flex-grow-1" style="max-height: 500px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Alumno</th>
                                    <th>Matrícula</th>
                                    <th>Hora</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="bitacora-body">
                                <tr id="no-data"><td colspan="4" class="text-center py-5 text-muted">Esperando registros...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const input = document.getElementById('matricula-input');
        const manualInput = document.getElementById('manual-input');
        const tutorEmail = document.getElementById('tutor-email');
        const statusMsg = document.getElementById('status-msg');
        const bitacoraBody = document.getElementById('bitacora-body');
        const salonCounter = document.getElementById('alumnos-salon');
        const totalRegistros = document.getElementById('total-registros');
        
        let registrosCount = 0;
        let alumnosEnSalon = 0;

        document.addEventListener('click', (e) => {
            if (!['manual-input', 'tutor-email', 'btn-registrar-manual'].includes(e.target.id)) {
                input.focus();
            }
        });

        function enviarRegistro(matricula, email = "") {
            if(!matricula) return;
            const modo = document.querySelector('input[name="modo"]:checked').value;
            
            input.disabled = true;
            statusMsg.className = "alert alert-info mt-3 border-0";
            statusMsg.innerHTML = "Procesando...";
            statusMsg.classList.remove('d-none');

            // Enviamos el correo del tutor como un parámetro extra
            fetch('registrar_asistencia_be.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `matricula=${encodeURIComponent(matricula)}&modo=${modo}&materia=<?= $id_materia ?>&grupo=<?= $id_grupo ?>&correo_tutor=${encodeURIComponent(email)}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    statusMsg.className = "alert alert-success mt-3 border-0";
                    statusMsg.innerHTML = `Registrado: ${data.nombre}`;
                    if(modo === 'Entrada') alumnosEnSalon++; else alumnosEnSalon--;
                    salonCounter.innerText = alumnosEnSalon;
                    agregarABitacora(data.nombre, data.matricula, data.hora, modo);
                    
                    // Limpiar campos manuales
                    manualInput.value = "";
                    tutorEmail.value = "";
                } else {
                    statusMsg.className = "alert alert-danger mt-3 border-0";
                    statusMsg.innerHTML = data.message;
                }
                input.value = "";
                input.disabled = false;
                input.focus();
                setTimeout(() => statusMsg.classList.add('d-none'), 3000);
            });
        }

        document.getElementById('form-scanner').addEventListener('submit', (e) => {
            e.preventDefault();
            enviarRegistro(input.value.trim());
        });

        document.getElementById('btn-registrar-manual').addEventListener('click', () => {
            enviarRegistro(manualInput.value.trim(), tutorEmail.value.trim());
        });

        function agregarABitacora(nombre, mat, hora, tipo) {
            const noData = document.getElementById('no-data');
            if(noData) noData.remove();
            const row = document.createElement('tr');
            const badgeClass = tipo === 'Entrada' ? 'badge-entrada' : 'badge-salida';
            row.innerHTML = `
                <td class="ps-4 fw-bold">${nombre}</td>
                <td><code class="text-primary fw-bold">${mat}</code></td>
                <td class="text-secondary">${hora}</td>
                <td class="text-center"><span class="${badgeClass}">${tipo.toUpperCase()}</span></td>
            `;
            bitacoraBody.insertBefore(row, bitacoraBody.firstChild);
            registrosCount++;
            totalRegistros.innerText = `${registrosCount} Registros`;
        }
    </script>
</body>
</html>