<?php
// exportarA_pdf.php

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

// Recoger parámetros de búsqueda y filtros
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_genero = isset($_GET['genero']) ? intval($_GET['genero']) : '';
$filtro_discapacidad = isset($_GET['discapacidad']) ? intval($_GET['discapacidad']) : '';

try {
    // Construir consulta con filtros
    $sql = "SELECT a.*, g.genero, d.tipoDiscapacidad 
            FROM alumnos a 
            LEFT JOIN generos g ON a.id_genero = g.id_genero 
            LEFT JOIN discapacidades d ON a.id_discapacidad = d.id_discapacidad 
            WHERE 1=1";
    
    $params = [];
    
    // Aplicar búsqueda
    if (!empty($busqueda)) {
        $sql .= " AND (a.nombre LIKE :busqueda OR 
                      a.apellido_paterno LIKE :busqueda OR 
                      a.apellido_materno LIKE :busqueda OR 
                      a.matricula LIKE :busqueda OR 
                      a.correo_institucional LIKE :busqueda)";
        $params[':busqueda'] = "%$busqueda%";
    }
    
    // Aplicar filtro de género
    if (!empty($filtro_genero)) {
        $sql .= " AND a.id_genero = :genero";
        $params[':genero'] = $filtro_genero;
    }
    
    // Aplicar filtro de discapacidad
    if (!empty($filtro_discapacidad)) {
        $sql .= " AND a.id_discapacidad = :discapacidad";
        $params[':discapacidad'] = $filtro_discapacidad;
    }
    
    // Añadir orden
    $sql .= " ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre";
    
    // Preparar y ejecutar la consulta
    $stmt = $con->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($alumnos)) {
        $_SESSION['error'] = "No hay alumnos para exportar con los filtros seleccionados";
        header('Location: lista_alumnos.php');
        exit();
    }
    
    $filename = "alumnos_" . date('Y-m-d') . ".pdf";
    
    // Obtener datos para los filtros (para mostrar en el PDF)
    if (!empty($filtro_genero)) {
        $sql_genero = "SELECT genero FROM generos WHERE id_genero = :id_genero";
        $stmt_genero = $con->prepare($sql_genero);
        $stmt_genero->bindParam(':id_genero', $filtro_genero);
        $stmt_genero->execute();
        $genero_filtrado = $stmt_genero->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!empty($filtro_discapacidad)) {
        $sql_disc = "SELECT tipoDiscapacidad FROM discapacidades WHERE id_discapacidad = :id_discapacidad";
        $stmt_disc = $con->prepare($sql_disc);
        $stmt_disc->bindParam(':id_discapacidad', $filtro_discapacidad);
        $stmt_disc->execute();
        $discapacidad_filtrada = $stmt_disc->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

// Crear una clase personalizada de FPDF (la misma que usas para maestros)
class PDF extends FPDF {
    private $reportTitle = '';
    
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
            $this->Cell(0, 10, 'SISTEMA DE CONTROL DE ALUMNOS', 0, 1, 'C');
            
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
    
    function SetTitle($title, $isUTF8 = false) {
        parent::SetTitle($title, $isUTF8);
        $this->reportTitle = $title;
    }
}

// Crear el PDF
$pdf = new PDF('L', 'mm', 'Letter'); // Orientación horizontal para más columnas
$pdf->AliasNbPages();
$pdf->AddPage();

// Título del documento
$pdf->SetTitle('LISTADO DE ALUMNOS - CECYTE');

// Información de filtros aplicados
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, 'Criterios de búsqueda aplicados:', 0, 1);
$pdf->SetFont('Arial', '', 9);

$filtros_texto = '';
if (!empty($busqueda)) {
    $filtros_texto .= "Búsqueda: '$busqueda' | ";
}
if (!empty($filtro_genero) && isset($genero_filtrado)) {
    $filtros_texto .= "Género: " . $genero_filtrado['genero'] . " | ";
}
if (!empty($filtro_discapacidad) && isset($discapacidad_filtrada)) {
    $filtros_texto .= "Discapacidad: " . $discapacidad_filtrada['tipoDiscapacidad'] . " | ";
}
if (empty($filtros_texto)) {
    $filtros_texto = "Sin filtros aplicados";
} else {
    $filtros_texto = rtrim($filtros_texto, " | ");
}

$pdf->Cell(0, 6, $filtros_texto, 0, 1);
$pdf->Ln(5);

// Cabecera de tabla
$header = array(
    '#', 
    'Matrícula', 
    'Apellido Paterno', 
    'Apellido Materno', 
    'Nombre(s)', 
    'Género', 
    'Email Institucional', 
    'Celular', 
    'Discapacidad',
    'Fecha Ingreso'
);
$w = array(8, 25, 30, 30, 35, 20, 45, 25, 30, 25);

$pdf->drawTableHeader($header, $w);

// Datos de la tabla
$contador = 1;
foreach ($alumnos as $alumno) {
    $data = array(
        $contador++,
        $alumno['matricula'] ?? '',
        $alumno['apellido_paterno'] ?? '',
        $alumno['apellido_materno'] ?? '',
        $alumno['nombre'] ?? '',
        $alumno['genero'] ?? 'N/A',
        $alumno['correo_institucional'] ?? '',
        $alumno['telefono_celular'] ?? 'N/A',
        $alumno['tipoDiscapacidad'] ?? 'Ninguna',
        date('d/m/Y', strtotime($alumno['fecha_ingreso'] ?? '0000-00-00'))
    );
    
    $pdf->drawTableRow($data, $w);
}

// Agregar estadísticas al final
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 10, 'Total de alumnos: ' . count($alumnos), 0, 1, 'R');

// Agregar información adicional
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1);
$pdf->Cell(0, 6, 'Usuario: ' . ($_SESSION['username'] ?? 'Desconocido'), 0, 1);

// Enviar el PDF al navegador
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$pdf->Output('D', $filename);
exit;