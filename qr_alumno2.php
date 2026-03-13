<?php
// qr_alumno.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['matricula'])) {
    header('Location: lista_alumnos.php?error=matricula_invalida');
    exit();
}

$matricula = trim($_GET['matricula']);

try {
    $sql = "SELECT a.*, 
                   c.nombre as carrera_nombre, 
                   c.clave as carrera_clave,
                   g.nombre as grupo_nombre,
                   e.tipoEstatus as estatus_nombre
            FROM alumnos a
            LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
            LEFT JOIN grupos g ON a.id_grupo = g.id_grupo
            LEFT JOIN estatus e ON a.id_estatus = e.id_estatus
            WHERE a.matricula = :matricula";
    
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('Location: lista_alumnos.php?error=not_found');
        exit();
    }
    
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener datos del alumno: " . $e->getMessage());
}

// Formatear datos para el QR
$datosQR = [
    'tipo' => 'ALUMNO_CECYTE',
    'version' => '1.0',
    'matricula' => $alumno['matricula'],
    'nombre' => $alumno['nombre'] . ' ' . $alumno['apellido_paterno'] . ' ' . ($alumno['apellido_materno'] ?? ''),
    'curp' => $alumno['curp'],
    'carrera' => $alumno['carrera_nombre'] ?? '',
    'semestre' => $alumno['semestre'] ?? '',
    'grupo' => $alumno['grupo_nombre'] ?? '',
    'estatus' => $alumno['estatus_nombre'] ?? 'Activo',
    'fecha_ingreso' => $alumno['fecha_ingreso'] ?? '',
    'fecha_generacion' => date('Y-m-d H:i:s')
];

// Convertir a JSON para el QR
$qrData = json_encode($datosQR, JSON_UNESCAPED_UNICODE);

// URL para el código QR (usando servicio externo o librería local)
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrData);

// Determinar tipo de salida
$tipo = $_GET['tipo'] ?? 'html'; // html, download, print

// Si se solicita descarga directa
if ($tipo == 'download') {
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="QR_' . $alumno['matricula'] . '.png"');
    readfile($qrUrl);
    exit;
}

// Si se solicita impresión
if ($tipo == 'print') {
    // Mostrar página optimizada para impresión
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Código QR - <?php echo htmlspecialchars($alumno['matricula']); ?></title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 0; 
                padding: 20px; 
                background: white;
            }
            .qr-container { 
                text-align: center; 
                max-width: 800px; 
                margin: 0 auto;
            }
            .qr-code { 
                max-width: 400px; 
                margin: 0 auto 20px; 
            }
            .info { 
                margin: 20px 0; 
                text-align: center;
            }
            .info h2 { 
                color: #1565c0; 
                margin-bottom: 10px;
            }
            .info p { 
                margin: 5px 0; 
                font-size: 14px;
            }
            .footer { 
                margin-top: 30px; 
                font-size: 12px; 
                color: #666; 
                text-align: center;
            }
            @media print {
                .no-print { display: none; }
                body { padding: 0; }
                .qr-container { max-width: 100%; }
            }
        </style>
    </head>
    <body>
        <div class="qr-container">
            <div class="info">
                <h2>CECyTE - Credencial Digital</h2>
                <p><strong>Matr&iacute;cula:</strong> <?php echo htmlspecialchars($alumno['matricula']); ?></p>
                <p><strong>Alumno:</strong> <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno'] . ' ' . ($alumno['apellido_materno'] ?? '')); ?></p>
                <p><strong>Carrera:</strong> <?php echo htmlspecialchars($alumno['carrera_nombre'] ?? ''); ?></p>
                <p><strong>Semestre:</strong> <?php echo htmlspecialchars($alumno['semestre'] ?? ''); ?>°</p>
                <p><strong>Generado:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>
            
            <div class="qr-code">
                <img src="<?php echo $qrUrl; ?>" alt="C&oacute;digo QR" style="width: 100%;">
            </div>
            
            <div class="footer">
                <p>Escanea este c&oacute;digo para verificar la informaci&oacute;n del alumno</p>
                <p>Sistema CECyTE - <?php echo date('Y'); ?></p>
            </div>
            
            <div class="no-print" style="margin-top: 30px;">
                <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
                <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
            </div>
        </div>
        
        <script>
            // Auto-imprimir si se solicita
            window.onload = function() {
                <?php if (isset($_GET['autoprint']) && $_GET['autoprint'] == '1'): ?>
                window.print();
                <?php endif; ?>
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Alumno - CECyTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1565c0;
            border-bottom: 3px solid #42a5f5;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .qr-section {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .qr-container {
            max-width: 300px;
            margin: 0 auto 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .qr-code {
            width: 100%;
            height: auto;
        }
        .student-info {
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .info-item {
            padding: 10px;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #1565c0;
        }
        .info-label {
            font-weight: 600;
            color: #1565c0;
            font-size: 0.9rem;
        }
        .info-value {
            color: #212529;
            font-size: 1rem;
        }
        .btn-download {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-download:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .btn-print {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        .btn-print:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class='bx bx-qr'></i> C&oacute;digo QR del Alumno</h2>
        
        <div class="student-info">
            <h5>
                <i class='bx bx-user'></i> 
                <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno'] . ' ' . ($alumno['apellido_materno'] ?? '')); ?>
            </h5>
            <p class="mb-2"><strong>Matr&iacute;cula:</strong> <?php echo htmlspecialchars($alumno['matricula']); ?></p>
            <p class="mb-0"><strong>Carrera:</strong> <?php echo htmlspecialchars($alumno['carrera_nombre'] ?? 'No asignada'); ?></p>
        </div>
        
        <div class="qr-section">
            <h4 class="mb-4">C&oacute;digo QR de Identificaci&oacute;n</h4>
            
            <div class="qr-container">
                <img src="<?php echo $qrUrl; ?>" alt="C&oacute;digo QR" class="qr-code">
            </div>
            
            <p class="text-muted mb-4">
                <i class='bx bx-info-circle'></i>
                Este c&oacute;digo QR contiene informaci&oacute;n b&aacute;sica del alumno para identificaci&oacute;n r&aacute;pida
            </p>
            
            <div class="row justify-content-center g-3">
                <div class="col-auto">
                    <a href="qr_alumno.php?matricula=<?php echo urlencode($matricula); ?>&tipo=download" 
                       class="btn btn-download">
                        <i class='bx bx-download'></i> Descargar QR
                    </a>
                </div>
                <div class="col-auto">
                    <a href="qr_alumno.php?matricula=<?php echo urlencode($matricula); ?>&tipo=print" 
                       target="_blank"
                       class="btn btn-print">
                        <i class='bx bx-printer'></i> Imprimir QR
                    </a>
                </div>
                <div class="col-auto">
                    <a href="qr_alumno.php?matricula=<?php echo urlencode($matricula); ?>&tipo=print&autoprint=1" 
                       target="_blank"
                       class="btn btn-primary">
                        <i class='bx bx-printer'></i> Imprimir Autom&aacute;tico
                    </a>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <h5><i class='bx bx-info-circle'></i> Informaci&oacute;n Contenida en el QR</h5>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Matr&iacute;cula</div>
                    <div class="info-value"><?php echo htmlspecialchars($alumno['matricula']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nombre Completo</div>
                    <div class="info-value"><?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno'] . ' ' . ($alumno['apellido_materno'] ?? '')); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">CURP</div>
                    <div class="info-value"><?php echo htmlspecialchars($alumno['curp']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Carrera</div>
                    <div class="info-value"><?php echo htmlspecialchars($alumno['carrera_nombre'] ?? 'No asignada'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Semestre</div>
                    <div class="info-value"><?php echo htmlspecialchars($alumno['semestre'] ?? ''); ?>°</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Grupo</div>
                    <div class="info-value"><?php echo htmlspecialchars($alumno['grupo_nombre'] ?? 'No asignado'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Estatus</div>
                    <div class="info-value"><?php echo htmlspecialchars($alumno['estatus_nombre'] ?? 'Activo'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fecha de Generaci&oacute;n</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i:s'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info">
            <h6><i class='bx bx-bulb'></i> Usos del C&oacute;digo QR</h6>
            <ul class="mb-0">
                <li><strong>Identificaci&oacute;n r&aacute;pida:</strong> Escanear para verificar datos del alumno</li>
                <li><strong>Control de acceso:</strong> Validar entrada a instalaciones</li>
                <li><strong>Registro de asistencia:</strong> Marcar entrada/salida con esc&aacute;ner</li>
                <li><strong>Pr&eacute;stamo de material:</strong> Registrar pr&eacute;stamos en biblioteca</li>
                <li><strong>Eventos escolares:</strong> Control de acceso a actividades</li>
            </ul>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="ver_alumno.php?matricula=<?php echo urlencode($matricula); ?>" class="btn btn-secondary">
                <i class='bx bx-arrow-back'></i> Volver al Alumno
            </a>
            <div>
                <a href="editar_alumnos.php?matricula=<?php echo urlencode($matricula); ?>" class="btn btn-primary me-2">
                    <i class='bx bx-edit'></i> Editar Alumno
                </a>
                <a href="lista_alumnos.php" class="btn btn-outline-secondary">
                    <i class='bx bx-list-ul'></i> Lista de Alumnos
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para copiar datos al portapapeles
        function copiarDatosQR() {
            const datos = <?php echo json_encode($datosQR, JSON_UNESCAPED_UNICODE); ?>;
            const texto = JSON.stringify(datos, null, 2);
            
            navigator.clipboard.writeText(texto).then(() => {
                alert('Datos del QR copiados al portapapeles');
            }).catch(err => {
                console.error('Error al copiar: ', err);
            });
        }
        
        // Agregar botón para copiar datos
        const qrSection = document.querySelector('.qr-section');
        if (qrSection) {
            const copyButton = document.createElement('button');
            copyButton.className = 'btn btn-outline-primary mt-3';
            copyButton.innerHTML = '<i class="bx bx-copy"></i> Copiar Datos QR';
            copyButton.onclick = copiarDatosQR;
            qrSection.querySelector('.row').appendChild(copyButton);
        }
        
        // Preview del QR en diferentes tamaños
        function cambiarTamanoQR(tamano) {
            const qrImg = document.querySelector('.qr-code');
            const nuevaUrl = '<?php echo str_replace("300x300", "SIZE", $qrUrl); ?>'.replace('SIZE', tamano);
            qrImg.src = nuevaUrl;
        }
        
        // Agregar controles de tamaño
        const qrContainer = document.querySelector('.qr-container');
        if (qrContainer) {
            const sizeControls = document.createElement('div');
            sizeControls.className = 'btn-group btn-group-sm mt-3';
            sizeControls.innerHTML = `
                <button class="btn btn-outline-secondary" onclick="cambiarTamanoQR('200x200')">Pequeno</button>
                <button class="btn btn-outline-secondary active" onclick="cambiarTamanoQR('300x300')">Mediano</button>
                <button class="btn btn-outline-secondary" onclick="cambiarTamanoQR('400x400')">Grande</button>
            `;
            qrContainer.appendChild(sizeControls);
        }
    </script>
</body>
</html>