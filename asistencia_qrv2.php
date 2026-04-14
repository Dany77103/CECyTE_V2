<?php
session_start();
require_once 'conexion.php'; // Asegúrate de que este archivo exista para la conexión

$id_materia = $_GET['materia'] ?? null;
$id_grupo = $_GET['grupo'] ?? null;

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit();
}

// --- NUEVA LÓGICA: Obtener alumnos reales del grupo seleccionado ---
$alumnos_grupo = [];
if ($id_grupo) {
    // Ajusta los nombres de las columnas 'matricula', 'nombre' e 'id_grupo' según tu tabla
    $sql_alumnos = "SELECT matricula, CONCAT(nombre, ' ', apellido_paterno) as nombre_completo 
                    FROM alumnos 
                    WHERE id_grupo = :id_grupo 
                    ORDER BY apellido_paterno ASC";
    $stmt_alumnos = $con->prepare($sql_alumnos);
    $stmt_alumnos->execute(['id_grupo' => $id_grupo]);
    $alumnos_grupo = $stmt_alumnos->fetchAll(PDO::FETCH_ASSOC);
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
            --danger: #dc3545;
            --white: #ffffff;
            --bg: #f4f6f9;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --transition: all 0.3s ease;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg); padding-top: 80px; }
        
        .navbar { background: var(--white); height: 70px; position: fixed; top: 0; width: 100%; z-index: 1000; box-shadow: var(--shadow-sm); display: flex; align-items: center; padding: 0 5%; }
        .navbar-brand { font-weight: 700; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .navbar-brand img { height: 40px; }

        .glass-card { background: var(--white); border-radius: 15px; border: none; box-shadow: var(--shadow-sm); margin-bottom: 20px; }
        
        /* Estilo de la lista de faltas */
        .list-group-item { border: none; border-bottom: 1px solid #f0f0f0; padding: 12px 15px; }
        .status-pill { font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; font-weight: 700; text-transform: uppercase; }
        .pill-pending { background: #fff3e0; color: #ef6c00; }
        .pill-absent { background: #ffebee; color: #c62828; }

        .badge-entrada { background: #e8f5e9; color: #2e7d32; padding: 6px 12px; border-radius: 8px; font-weight: 700; }
        .badge-salida { background: #fff3e0; color: #ef6c00; padding: 6px 12px; border-radius: 8px; font-weight: 700; }
        
        #reloj-digital { font-weight: 700; color: var(--primary); font-size: 1.1rem; }
    </style>
</head>
<body>

    <nav class="navbar justify-content-between">
        <a href="main.php" class="navbar-brand">
            <img src="img/logo.png" alt="Logo">
            <span>CECyTE Santa Catarina</span>
        </a>
        <div class="d-flex align-items-center gap-4">
            <div id="reloj-digital"><i class="fa-regular fa-clock me-2"></i>--:--:--</div>
            <a href="main.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Volver</a>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="row g-4">
            
            <div class="col-lg-3">
                <div class="glass-card p-4 shadow-sm">
                    <div class="btn-group w-100 mb-4 shadow-sm" style="border-radius: 10px; overflow: hidden;">
                        <input type="radio" class="btn-check" name="modo" id="modo-entrada" value="Entrada" checked>
                        <label class="btn btn-outline-primary py-2 fw-bold border-0" for="modo-entrada">ENTRADA</label>
                        <input type="radio" class="btn-check" name="modo" id="modo-salida" value="Salida">
                        <label class="btn btn-outline-warning py-2 fw-bold text-dark border-0" for="modo-salida">SALIDA</label>
                    </div>

                    <div class="text-center bg-light p-3 rounded-3">
                        <h6 class="fw-bold text-secondary mb-3">Escanear Código QR</h6>
                        <form id="form-scanner">
                            <input type="text" id="matricula-input" class="form-control text-center fw-bold" placeholder="Esperando..." autofocus autocomplete="off">
                        </form>
                        <div id="status-msg" class="alert d-none mt-3 small fw-bold" role="alert"></div>

                        <div class="mt-4 border-top pt-3">
                            <button class="btn btn-link btn-sm text-decoration-none fw-bold" data-bs-toggle="collapse" data-bs-target="#collapseManual">
                                <i class="fa-solid fa-keyboard me-1"></i> Registro Manual
                            </button>
                            <div class="collapse mt-2" id="collapseManual">
                                <input type="text" id="manual-input" class="form-control form-control-sm mb-2" placeholder="Matrícula">
                                <input type="email" id="tutor-email" class="form-control form-control-sm mb-2" placeholder="Correo Tutor">
                                <button class="btn btn-primary btn-sm w-100 fw-bold" id="btn-registrar-manual">REGISTRAR</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="glass-card h-100 d-flex flex-column shadow-sm">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary"><i class="fa-solid fa-bolt me-2"></i>Asistencias del Momento</h6>
                        <span class="badge bg-dark rounded-pill" id="total-registros">0 Registros</span>
                    </div>
                    <div class="table-responsive" style="max-height: 600px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-uppercase">
                                    <th class="ps-3">Alumno</th>
                                    <th>Hora</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="bitacora-body">
                                <tr id="no-data"><td colspan="3" class="text-center py-5 text-muted small">No hay registros hoy</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="glass-card h-100 shadow-sm">
                    <div class="p-3 border-bottom bg-light rounded-top">
                        <h6 class="m-0 fw-bold text-danger"><i class="fa-solid fa-user-clock me-2"></i>Estatus del Grupo</h6>
                    </div>
                    <div class="p-0">
                        <div id="lista-pendientes" class="list-group list-group-flush">
                            </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- RELOJ Y LÓGICA DE TIEMPO ---
        function actualizarReloj() {
            const ahora = new Date();
            const tiempoStr = ahora.toLocaleTimeString('es-MX', { hour12: false });
            document.getElementById('reloj-digital').innerHTML = `<i class="fa-regular fa-clock me-2"></i>${tiempoStr}`;
            
            const limiteFalta = "12:00:00";
            if (tiempoStr >= limiteFalta) {
                document.querySelectorAll('.status-pill.pill-pending').forEach(pill => {
                    pill.innerText = "FALTA";
                    pill.className = "status-pill pill-absent";
                });
            }
        }
        setInterval(actualizarReloj, 1000);

        // --- LÓGICA DE REGISTRO ---
        const input = document.getElementById('matricula-input');
        const manualInput = document.getElementById('manual-input');
        const tutorEmail = document.getElementById('tutor-email');
        const statusMsg = document.getElementById('status-msg');
        const bitacoraBody = document.getElementById('bitacora-body');
        
        document.addEventListener('click', (e) => {
            if (!['manual-input', 'tutor-email', 'btn-registrar-manual'].includes(e.target.id)) {
                input.focus();
            }
        });

        function enviarRegistro(matricula, email = "") {
            if(!matricula) return;
            const modo = document.querySelector('input[name="modo"]:checked').value;
            
            statusMsg.className = "alert alert-info mt-2 py-1 small";
            statusMsg.innerHTML = "Procesando...";
            statusMsg.classList.remove('d-none');

            fetch('registrar_asistencia_be.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `matricula=${encodeURIComponent(matricula)}&modo=${modo}&materia=<?= $id_materia ?>&grupo=<?= $id_grupo ?>&correo_tutor=${encodeURIComponent(email)}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    statusMsg.className = "alert alert-success mt-2 py-1 small";
                    statusMsg.innerHTML = `Listo: ${data.nombre}`;
                    agregarABitacora(data.nombre, data.hora, modo);
                    marcarComoAsistido(matricula); 
                } else {
                    statusMsg.className = "alert alert-danger mt-2 py-1 small";
                    statusMsg.innerHTML = data.message;
                }
                input.value = "";
                manualInput.value = "";
                tutorEmail.value = "";
                setTimeout(() => statusMsg.classList.add('d-none'), 2000);
            });
        }

        function marcarComoAsistido(matricula) {
            const item = document.getElementById(`pend-${matricula}`);
            if(item) {
                item.style.backgroundColor = "#e8f5e9";
                setTimeout(() => item.remove(), 500);
            }
        }

        function agregarABitacora(nombre, hora, tipo) {
            const noData = document.getElementById('no-data');
            if(noData) noData.remove();
            const row = document.createElement('tr');
            const badgeClass = tipo === 'Entrada' ? 'badge-entrada' : 'badge-salida';
            row.innerHTML = `
                <td class="ps-3 fw-bold small">${nombre}</td>
                <td class="small text-secondary">${hora}</td>
                <td class="text-center"><span class="${badgeClass} small">${tipo.toUpperCase()}</span></td>
            `;
            bitacoraBody.insertBefore(row, bitacoraBody.firstChild);
        }

        document.getElementById('form-scanner').addEventListener('submit', (e) => {
            e.preventDefault();
            enviarRegistro(input.value.trim());
        });

        document.getElementById('btn-registrar-manual').addEventListener('click', () => {
            enviarRegistro(manualInput.value.trim(), tutorEmail.value.trim());
        });

        // --- CARGA DE ALUMNOS REALES ---
        const listaPendientes = document.getElementById('lista-pendientes');
        function cargarAlumnosDelGrupo() {
            // Pasamos los datos de PHP a JavaScript de forma segura
            const alumnos = <?= json_encode($alumnos_grupo) ?>;
            
            if (alumnos.length === 0) {
                listaPendientes.innerHTML = '<div class="p-3 text-muted small text-center">No hay alumnos registrados.</div>';
                return;
            }

            listaPendientes.innerHTML = "";
            alumnos.forEach(a => {
                listaPendientes.innerHTML += `
                    <div class="list-group-item d-flex justify-content-between align-items-center" id="pend-${a.matricula}">
                        <div class="small fw-medium">${a.nombre_completo}</div>
                        <span class="status-pill pill-pending">Pendiente</span>
                    </div>
                `;
            });
        }
        
        // Ejecutamos al cargar
        cargarAlumnosDelGrupo();
    </script>
</body>
</html>