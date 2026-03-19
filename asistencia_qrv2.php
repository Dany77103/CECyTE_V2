<?php
session_start();
$id_materia = $_GET['materia'] ?? null;
$id_grupo = $_GET['grupo'] ?? null;

if (!@include_once('includes/header.php')) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Bitácora en Tiempo Real | CECyTE</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head><body class="bg-light">';
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 mb-4">
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="modo" id="modo-entrada" value="Entrada" checked>
                    <label class="btn btn-outline-primary py-3 fw-bold" for="modo-entrada"><i class="fas fa-sign-in-alt me-2"></i>MODO ENTRADA</label>

                    <input type="radio" class="btn-check" name="modo" id="modo-salida" value="Salida">
                    <label class="btn btn-outline-warning py-3 fw-bold text-dark" for="modo-salida"><i class="fas fa-sign-out-alt me-2"></i>MODO SALIDA</label>
                </div>

                <div class="card-body p-4 text-center">
                    <div id="status-icon-container" class="mb-3">
                        <i class="fas fa-barcode fa-4x text-muted"></i>
                    </div>
                    <h5 id="scanner-title">Esperando entrada...</h5>

                    <form id="form-scanner">
                        <input type="text" id="matricula-input" class="form-control form-control-lg text-center" 
                               placeholder="Escanee credencial" autofocus autocomplete="off" 
                               style="border: 2px solid #0d6efd; font-weight: bold; font-size: 1.5rem;">
                    </form>

                    <div id="status-msg" class="alert d-none mt-3" role="alert"></div>
                </div>
            </div>
        </div>

        <div class="col-md-10">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card bg-primary text-white text-center p-3 shadow-sm border-0">
                        <small class="text-uppercase fw-bold">Alumnos en Salón</small>
                        <h2 id="alumnos-salon" class="mb-0">0</h2>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow border-0">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list-ul me-2"></i>Movimientos de hoy</h5>
                            <span class="badge bg-secondary" id="total-registros">0 Registros</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 400px;">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Alumno</th>
                                            <th>Matrícula</th>
                                            <th>Hora</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bitacora-body">
                                        <tr id="no-data"><td colspan="4" class="text-center py-4 text-muted">No hay actividad</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const input = document.getElementById('matricula-input');
    const statusMsg = document.getElementById('status-msg');
    const bitacoraBody = document.getElementById('bitacora-body');
    const salonCounter = document.getElementById('alumnos-salon');
    const totalRegistros = document.getElementById('total-registros');
    
    let registrosCount = 0;
    let alumnosEnSalon = 0;

    // Actualizar estilo visual según modo seleccionado
    document.querySelectorAll('input[name="modo"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const esEntrada = radio.value === 'Entrada';
            input.style.borderColor = esEntrada ? '#0d6efd' : '#ffc107';
            document.getElementById('scanner-title').innerText = esEntrada ? 'Esperando entrada...' : 'Esperando salida...';
        });
    });

    document.addEventListener('click', () => input.focus());

    document.getElementById('form-scanner').addEventListener('submit', (e) => {
        e.preventDefault();
        const matricula = input.value.trim();
        const modo = document.querySelector('input[name="modo"]:checked').value;
        
        if(!matricula) return;

        input.disabled = true;
        statusMsg.className = "alert alert-info mt-3";
        statusMsg.innerHTML = "Validando...";
        statusMsg.classList.remove('d-none');

        fetch('registrar_asistencia_be.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `matricula=${encodeURIComponent(matricula)}&modo=${modo}&materia=<?= $id_materia ?>&grupo=<?= $id_grupo ?>`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                statusMsg.className = "alert alert-success mt-3";
                statusMsg.innerHTML = `<strong>OK:</strong> ${data.message}`;
                
                // Actualizar Contador del Salón
                if(modo === 'Entrada') alumnosEnSalon++;
                else alumnosEnSalon--;
                salonCounter.innerText = alumnosEnSalon;

                agregarABitacora(data.nombre, data.matricula, data.hora, modo);
            } else {
                statusMsg.className = "alert alert-danger mt-3";
                statusMsg.innerHTML = `<strong>Denegado:</strong> ${data.message}`;
            }

            input.value = "";
            input.disabled = false;
            input.focus();
            setTimeout(() => statusMsg.classList.add('d-none'), 3000);
        });
    });

    function agregarABitacora(nombre, mat, hora, tipo) {
        const noData = document.getElementById('no-data');
        if(noData) noData.remove();

        const row = document.createElement('tr');
        const badgeClass = tipo === 'Entrada' ? 'bg-primary' : 'bg-warning text-dark';
        
        row.innerHTML = `
            <td class="fw-bold">${nombre}</td>
            <td><code>${mat}</code></td>
            <td>${hora}</td>
            <td><span class="badge ${badgeClass}">${tipo}</span></td>
        `;
        bitacoraBody.insertBefore(row, bitacoraBody.firstChild);
        registrosCount++;
        totalRegistros.innerText = `${registrosCount} Registros`;
    }
</script>