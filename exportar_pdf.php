
<?php
// exportar_pdf.php

// Incluir configuración común - usando ruta relativa segura
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    die("Error: Archivo de configuración no encontrado en: " . $configPath);
}

// Verificar sesión
verificarSesion();

// Conectar a la base de datos
$con = conectarDB();

// Verificar si FPDF está disponible
if (!class_exists('FPDF')) {
    // Intentar incluir FPDF manualmente
    $fpdfPath = __DIR__ . '/fpdf/fpdf.php';
    if (file_exists($fpdfPath)) {
        require_once $fpdfPath;
    } else {
        die("Error: La librería FPDF no está instalada. Por favor, descárgala de http://www.fpdf.org/ y colócala en la carpeta 'fpdf'");
    }
}

// Determinar si se exportan todos los maestros o uno específico
$numEmpleado = isset($_GET['numEmpleado']) ? trim($_GET['numEmpleado']) : null;

try {
    if ($numEmpleado) {
        // Exportar un maestro específico con todos sus datos
        $sql = "SELECT 
                    m.*,
                    da.gradoEstudios,
                    da.institucion,
                    da.numCedulaProfesional,
                    da.especialidad,
                    da.anioTitulacion,
                    da.cursosDiplomados,
                    dl.fechaContratacion,
                    dl.tipoContrato,
                    p.puesto,
                    dl.area,
                    dl.horarioLaboral,
                    e.tipoEstatus as estatusLaboral,
                    dl.horarioClases,
                    dl.actividadesExtracurriculares,
                    dl.observaciones as observacionesLaborales
                FROM maestros m
                LEFT JOIN datosacademicosmaestros da ON m.numEmpleado = da.numEmpleado
                LEFT JOIN datoslaboralesmaestros dl ON m.numEmpleado = dl.numEmpleado
                LEFT JOIN puestos p ON dl.id_puesto = p.id_puesto
                LEFT JOIN estatus e ON dl.id_estatus = e.id_estatus
                WHERE m.numEmpleado = :numEmpleado";
        
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':numEmpleado', $numEmpleado, PDO::PARAM_STR);
        $stmt->execute();
        $maestro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$maestro) {
            $_SESSION['error'] = "No se encontró el maestro";
            header('Location: editar_maestro.php?numEmpleado=' . $numEmpleado);
            exit();
        }
        
        $filename = "maestro_" . $numEmpleado . "_" . date('Y-m-d') . ".pdf";
    } else {
        // Exportar todos los maestros con información básica
        $sql = "SELECT m.*,
                    m.numEmpleado,
                    m.nombre,
                    m.apellido_paterno,
                    m.apellido_materno,
                    m.fecha_nacimiento,
                    m.curp,
                    m.rfc,
                   
                    m.correo_institucional,
                    p.puesto,
                    dl.area,
                    dl.tipoContrato,
                    e.tipoEstatus as estatusLaboral,
                    dl.fechaContratacion
                FROM maestros m
                LEFT JOIN datoslaboralesmaestros dl ON m.numEmpleado = dl.numEmpleado
                LEFT JOIN puestos p ON dl.id_puesto = p.id_puesto
                LEFT JOIN estatus e ON dl.id_estatus = e.id_estatus
                ORDER BY m.apellido_paterno, m.apellido_materno, m.nombre";
        
        $stmt = $con->query($sql);
        $maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($maestros)) {
            $_SESSION['error'] = "No hay maestros para exportar";
            header('Location: lista_maestros.php');
            exit();
        }
        
        $filename = "maestros_completo_" . date('Y-m-d') . ".pdf";
    }
    
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

// Crear una clase personalizada de FPDF
class PDF extends FPDF {
    private $reportTitle = '';
    
    // Cambiamos el nombre del método para evitar conflicto con FPDF::SetTitle()
    function setReportTitle($title) {
        $this->reportTitle = $title;
    }
    
    function Header() {
        if ($this->PageNo() == 1) {
            // Logo (si existe)
            if (file_exists('logo.png')) {
                $this->Image('logo.png', 10, 8, 25);
            }
            
            // Título
            $this->SetFont('Arial', 'B', 16);
            $this->SetTextColor(46, 125, 50); // Verde CECyTE
            $this->Cell(0, 10, 'COLEGIO DE ESTUDIOS CIENTÍFICOS Y TECNOLÓGICOS', 0, 1, 'C');
            
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'SISTEMA DE CONTROL DE MAESTROS', 0, 1, 'C');
            
            if ($this->reportTitle) {
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 10, $this->reportTitle, 0, 1, 'C');
            }
            
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 10, 'Fecha: ' . date('d/m/Y'), 0, 1, 'R');
            
            // Línea separadora
            $this->SetLineWidth(0.5);
            $this->SetDrawColor(46, 125, 50);
            $this->Line(10, 40, 200, 40);
            $this->Ln(10);
        }
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    function drawTableHeader($header, $w) {
        // Colores, ancho de línea y fuente en negrita
        $this->SetFillColor(46, 125, 50);
        $this->SetTextColor(255);
        $this->SetDrawColor(46, 125, 50);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 10);
        
        // Cabecera
        for($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Restauración de colores y fuentes
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);
    }
    
    function drawTableRow($data, $w) {
        // Calcular la altura de la fila
        $nb = 0;
        for($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($w[$i], $data[$i]));
        }
        $h = 5 * $nb;
        
        // Salto de página si es necesario
        if($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage();
        }
        
        // Dibujar las celdas
        for($i = 0; $i < count($data); $i++) {
            $x = $this->GetX();
            $y = $this->GetY();
            
            // Dibujar el borde
            $this->Rect($x, $y, $w[$i], $h);
            
            // Imprimir el texto
            $this->MultiCell($w[$i], 5, $data[$i], 0, 'L');
            
            // Posicionarse a la derecha de la celda
            $this->SetXY($x + $w[$i], $y);
        }
        $this->Ln($h);
    }
    
    function NbLines($w, $txt) {
        // Calcula el número de líneas que ocupará un texto en una celda
        $cw = &$this->CurrentFont['cw'];
        if($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb > 0 && $s[$nb-1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i < $nb) {
            $c = $s[$i];
            if($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if($l > $wmax) {
                if($sep == -1) {
                    if($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }
    
    // Método para establecer el título del documento (compatible con FPDF)
    function SetTitle($title, $isUTF8 = false) {
        // Llama al método SetTitle de la clase padre
        parent::SetTitle($title, $isUTF8);
        // También guarda el título para nuestro uso
        $this->reportTitle = $title;
    }
}

// Crear el PDF
$pdf = new PDF('P', 'mm', 'Letter');
$pdf->AliasNbPages();
$pdf->AddPage();

if ($numEmpleado) {
    // PDF para un maestro específico
    $pdf->SetTitle('REPORTE COMPLETO DEL MAESTRO');
    
    // Información personal
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetTextColor(46, 125, 50);
    $pdf->Cell(0, 8, '1. INFORMACIÓN PERSONAL', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0);
    
    // Tabla de información personal
    $pdf->Cell(50, 6, 'Número de Empleado:', 0, 0);
    $pdf->Cell(0, 6, $maestro['numEmpleado'], 0, 1);
    
    $pdf->Cell(50, 6, 'Nombre Completo:', 0, 0);
    $pdf->Cell(0, 6, $maestro['nombre'] . ' ' . $maestro['apellido_paterno'] . ' ' . ($maestro['apellido_materno'] ?? ''), 0, 1);
    
    $pdf->Cell(50, 6, 'CURP:', 0, 0);
    $pdf->Cell(0, 6, $maestro['curp'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'RFC:', 0, 0);
    $pdf->Cell(0, 6, $maestro['rfc'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Fecha Nacimiento:', 0, 0);
    $pdf->Cell(0, 6, $maestro['fecha_nacimiento'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Genero:', 0, 0);
    $pdf->Cell(0, 6, $maestro['id_genero'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Estado Civil:', 0, 0);
    $pdf->Cell(0, 6, $maestro['estado_civil'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Dirección:', 0, 0);
    $pdf->MultiCell(0, 6, $maestro['direccion'] ?? '', 0, 'L');
    
    $pdf->Cell(50, 6, 'Teléfono:', 0, 0);
    $pdf->Cell(0, 6, $maestro['telefono_celular'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Correo Personal:', 0, 0);
    $pdf->Cell(0, 6, $maestro['correo_personal'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Correo Institucional:', 0, 0);
    $pdf->Cell(0, 6, $maestro['correo_institucional'] ?? '', 0, 1);
    
    $pdf->Ln(5);
    
    // Información académica
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(46, 125, 50);
    $pdf->Cell(0, 8, '2. INFORMACIÓN ACADÉMICA', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0);
    
    $pdf->Cell(50, 6, 'Grado de Estudios:', 0, 0);
    $pdf->Cell(0, 6, $maestro['gradoEstudios'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Institución:', 0, 0);
    $pdf->MultiCell(0, 6, $maestro['institucion'] ?? '', 0, 'L');
    
    $pdf->Cell(50, 6, 'Cédula Profesional:', 0, 0);
    $pdf->Cell(0, 6, $maestro['numCedulaProfesional'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Especialidad:', 0, 0);
    $pdf->Cell(0, 6, $maestro['especialidad'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Año de Titulación:', 0, 0);
    $pdf->Cell(0, 6, $maestro['anioTitulacion'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Cursos/Diplomados:', 0, 0);
    $pdf->MultiCell(0, 6, $maestro['cursosDiplomados'] ?? '', 0, 'L');
    
    $pdf->Ln(5);
    
    // Información laboral
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(46, 125, 50);
    $pdf->Cell(0, 8, '3. INFORMACIÓN LABORAL', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0);
    
    $pdf->Cell(50, 6, 'Fecha de Contratación:', 0, 0);
    $pdf->Cell(0, 6, $maestro['fechaContratacion'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Tipo de Contrato:', 0, 0);
    $pdf->Cell(0, 6, $maestro['tipoContrato'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Puesto:', 0, 0);
    $pdf->Cell(0, 6, $maestro['puesto'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Área/Departamento:', 0, 0);
    $pdf->Cell(0, 6, $maestro['area'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Horario Laboral:', 0, 0);
    $pdf->Cell(0, 6, $maestro['horarioLaboral'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Estatus Laboral:', 0, 0);
    $pdf->Cell(0, 6, $maestro['estatusLaboral'] ?? '', 0, 1);
    
    $pdf->Cell(50, 6, 'Horario de Clases:', 0, 0);
    $pdf->MultiCell(0, 6, $maestro['horarioClases'] ?? '', 0, 'L');
    
    $pdf->Cell(50, 6, 'Actividades Extracurriculares:', 0, 0);
    $pdf->MultiCell(0, 6, $maestro['actividadesExtracurriculares'] ?? '', 0, 'L');
    
    $pdf->Cell(50, 6, 'Observaciones:', 0, 0);
    $pdf->MultiCell(0, 6, $maestro['observacionesLaborales'] ?? '', 0, 'L');
    
} else {
    // PDF para lista de todos los maestros
    $pdf->SetTitle('LISTADO COMPLETO DE MAESTROS');
    
    // Cabecera de tabla
    $header = array('No. Empleado', 'Apellidos', 'Nombre', 'Teléfono', 'Correo', 'Puesto', 'Estatus');
    $w = array(25, 35, 35, 25, 40, 30, 20);
    
    $pdf->drawTableHeader($header, $w);
    
    // Datos de la tabla
    $fill = false;
    foreach ($maestros as $maestro) {
        $nombreCompleto = ($maestro['apellido_paterno'] ?? '') . ' ' . ($maestro['apellido_materno'] ?? '');
        $data = array(
            $maestro['numEmpleado'] ?? '',
            $nombreCompleto,
            $maestro['nombre'] ?? '',
            $maestro['telefono_celular'] ?? '',
            $maestro['mailInstitucional'] ?? '',
            $maestro['puesto'] ?? '',
            $maestro['estatusLaboral'] ?? ''
        );
        
        $pdf->drawTableRow($data, $w);
        
        $fill = !$fill;
    }
    
    // Agregar contador al final
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, 'Total de maestros: ' . count($maestros), 0, 1, 'R');
}

// Enviar el PDF al navegador
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$pdf->Output('D', $filename);
exit;

