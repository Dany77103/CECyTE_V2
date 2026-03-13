<?php
// exportar_excel.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
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
        $maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filename = "maestro_" . $numEmpleado . "_" . date('Y-m-d') . ".xlsx";
    } else {
        // Exportar todos los maestros con información básica
        $sql = "SELECT m.*,
                    m.numEmpleado,
                    m.nombre,
                    m.apellido_paterno,
                    m.apellido_materno,
                    m.fecha_nacimiento,
                    m.id_genero,
                    m.estado_civil,
                    m.curp,
                    m.rfc,
                    m.direccion,
                    m.telefono_celular,
                    m.correo_personal,
                    m.correo_institucional,
                    p.puesto,
                    dl.area,
                    dl.tipoContrato,
                    e.tipoEstatus as estatusLaboral,
                    dl.fechaContratacion,
                    da.gradoEstudios,
                    da.institucion
                FROM maestros m
                LEFT JOIN datosacademicosmaestros da ON m.numEmpleado = da.numEmpleado
                LEFT JOIN datoslaboralesmaestros dl ON m.numEmpleado = dl.numEmpleado
                LEFT JOIN puestos p ON dl.id_puesto = p.id_puesto
                LEFT JOIN estatus e ON dl.id_estatus = e.id_estatus
                ORDER BY m.apellido_paterno, m.apellido_materno, m.nombre";
        
        $stmt = $con->query($sql);
        $maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filename = "maestros_completo_" . date('Y-m-d') . ".xlsx";
    }
    
    if (empty($maestros)) {
        $_SESSION['error'] = "No hay datos para exportar";
        header('Location: ' . ($numEmpleado ? 'editar_maestro.php?numEmpleado=' . $numEmpleado : 'lista_maestros.php'));
        exit();
    }
    
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

// Verificar si existe la librería PhpSpreadsheet
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require $autoloadPath;
} else {
    // Alternativa: generar CSV en lugar de Excel
    generarCSV($maestros, $filename, $numEmpleado);
    exit();
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// Crear una instancia de Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar propiedades del documento
$spreadsheet->getProperties()
    ->setCreator("Sistema CECyTE")
    ->setTitle("Reporte de Maestros")
    ->setSubject($numEmpleado ? "Datos del Maestro" : "Lista de Maestros");

// Estilos reutilizables
$titleStyle = [
    'font' => [
        'bold' => true,
        'size' => 16,
        'color' => ['rgb' => '1B5E20'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2E7D32'], // Verde más oscuro
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '1B5E20'],
        ],
    ],
];

$subHeaderStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '43A047'], // Verde intermedio
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '1B5E20'],
        ],
    ],
];

$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'C8E6C9'],
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
];

$labelStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => '2E7D32'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E8F5E9'], // Verde muy claro
    ],
];

if ($numEmpleado) {
    // Exportar datos completos de un maestro
    $maestro = $maestros[0];
    
    // Título principal
    $sheet->setCellValue('A1', 'REPORTE COMPLETO DEL MAESTRO');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->applyFromArray($titleStyle);
    $sheet->getRowDimension(1)->setRowHeight(30);
    
    // Subtítulo con información básica
    $sheet->setCellValue('A2', 'CECyTE - Sistema de Gestión de Personal');
    $sheet->mergeCells('A2:F2');
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Información personal
    $row = 4;
    $sheet->setCellValue('A' . $row, 'INFORMACIÓN PERSONAL');
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($headerStyle);
    $sheet->getRowDimension($row)->setRowHeight(25);
    
    // Datos personales - usando dos columnas para mejor distribución
    $personalInfo = [
        ['Número de Empleado:', $maestro['numEmpleado'], 'Nombre Completo:', $maestro['nombre'] . ' ' . $maestro['apellido_paterno'] . ' ' . $maestro['apellido_materno']],
        ['CURP:', $maestro['curp'], 'RFC:', $maestro['rfc']],
        ['Fecha Nacimiento:', $maestro['fecha_nacimiento'], 'Genero:', $maestro['id_genero']],
        ['Estado Civil:', $maestro['estado_civil'], 'Teléfono:', $maestro['telefono_celular']],
        ['Dirección:', $maestro['direccion'], '', ''],
        ['Correo Personal:', $maestro['correo_personal'], 'Correo Institucional:', $maestro['correo_institucional']],
    ];
    
    foreach ($personalInfo as $info) {
        $row++;
        $sheet->setCellValue('A' . $row, $info[0]);
        $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
        $sheet->setCellValue('B' . $row, $info[1]);
        $sheet->mergeCells('B' . $row . ':C' . $row);
        
        if (!empty($info[2])) {
            $sheet->setCellValue('D' . $row, $info[2]);
            $sheet->getStyle('D' . $row)->applyFromArray($labelStyle);
            $sheet->setCellValue('E' . $row, $info[3]);
            $sheet->mergeCells('E' . $row . ':F' . $row);
        } else {
            $sheet->mergeCells('B' . $row . ':F' . $row);
        }
    }
    
    // Aplicar bordes a la sección personal
    $sheet->getStyle('A4:F' . $row)->applyFromArray($dataStyle);
    
    // Información académica
    $row += 2;
    $sheet->setCellValue('A' . $row, 'INFORMACIÓN ACADÉMICA');
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($headerStyle);
    $sheet->getRowDimension($row)->setRowHeight(25);
    
    $academicInfo = [
        ['Grado de Estudios:', $maestro['gradoEstudios'], 'Institución:', $maestro['institucion']],
        ['Cédula Profesional:', $maestro['numCedulaProfesional'], 'Especialidad:', $maestro['especialidad']],
        ['Año de Titulación:', $maestro['anioTitulacion'], 'Cursos/Diplomados:', $maestro['cursosDiplomados']],
    ];
    
    foreach ($academicInfo as $info) {
        $row++;
        $sheet->setCellValue('A' . $row, $info[0]);
        $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
        $sheet->setCellValue('B' . $row, $info[1]);
        $sheet->mergeCells('B' . $row . ':C' . $row);
        
        $sheet->setCellValue('D' . $row, $info[2]);
        $sheet->getStyle('D' . $row)->applyFromArray($labelStyle);
        $sheet->setCellValue('E' . $row, $info[3]);
        $sheet->mergeCells('E' . $row . ':F' . $row);
    }
    
    // Aplicar bordes a la sección académica
    $academicStart = $row - count($academicInfo);
    $sheet->getStyle('A' . $academicStart . ':F' . $row)->applyFromArray($dataStyle);
    
    // Información laboral
    $row += 2;
    $sheet->setCellValue('A' . $row, 'INFORMACIÓN LABORAL');
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($headerStyle);
    $sheet->getRowDimension($row)->setRowHeight(25);
    
    $laborInfo = [
        ['Fecha de Contratación:', $maestro['fechaContratacion'], 'Tipo de Contrato:', $maestro['tipoContrato']],
        ['Puesto:', $maestro['puesto'], 'Área/Departamento:', $maestro['area']],
        ['Horario Laboral:', $maestro['horarioLaboral'], 'Estatus Laboral:', $maestro['estatusLaboral']],
        ['Horario de Clases:', $maestro['horarioClases'], 'Actividades Extracurriculares:', $maestro['actividadesExtracurriculares']],
        ['Observaciones:', $maestro['observacionesLaborales'], '', ''],
    ];
    
    foreach ($laborInfo as $info) {
        $row++;
        $sheet->setCellValue('A' . $row, $info[0]);
        $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
        $sheet->setCellValue('B' . $row, $info[1]);
        
        if ($info[0] == 'Observaciones:') {
            $sheet->mergeCells('B' . $row . ':F' . $row);
        } else {
            $sheet->mergeCells('B' . $row . ':C' . $row);
        }
        
        if (!empty($info[2])) {
            $sheet->setCellValue('D' . $row, $info[2]);
            $sheet->getStyle('D' . $row)->applyFromArray($labelStyle);
            $sheet->setCellValue('E' . $row, $info[3]);
            $sheet->mergeCells('E' . $row . ':F' . $row);
        }
    }
    
    // Aplicar bordes a la sección laboral
    $laborStart = $row - count($laborInfo);
    $sheet->getStyle('A' . $laborStart . ':F' . $row)->applyFromArray($dataStyle);
    
    // Pie de página
    $row += 2;
    $sheet->setCellValue('A' . $row, 'Documento generado el: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->setSize(9);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Ajustar anchos de columna
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(20);
    $sheet->getColumnDimension('C')->setWidth(5);
    $sheet->getColumnDimension('D')->setWidth(25);
    $sheet->getColumnDimension('E')->setWidth(20);
    $sheet->getColumnDimension('F')->setWidth(5);
    
} else {
    // Exportar lista de todos los maestros
    // Encabezado principal
    $sheet->setCellValue('A1', 'LISTA DE MAESTROS - CECyTE');
    $sheet->mergeCells('A1:M1');
    $sheet->getStyle('A1')->applyFromArray($titleStyle);
    $sheet->getRowDimension(1)->setRowHeight(30);
    
    // Subtítulo
    $sheet->setCellValue('A2', 'Sistema de Gestión de Personal Docente');
    $sheet->mergeCells('A2:M2');
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(12);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Información de exportación
    $sheet->setCellValue('A3', 'Exportado: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A3:M3');
    $sheet->getStyle('A3')->getFont()->setSize(10);
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension(3)->setRowHeight(20);
    
    // Encabezados de columnas
    $headers = [
        'A' => 'No. Empleado',
        'B' => 'Apellido Paterno',
        'C' => 'Apellido Materno',
        'D' => 'Nombre',
        'E' => 'CURP',
        'F' => 'Teléfono',
        'G' => 'Correo Institucional',
        'H' => 'Puesto',
        'I' => 'Área',
        'J' => 'Tipo Contrato',
        'K' => 'Estatus',
        'L' => 'Fecha Contratación',
        'M' => 'Grado Estudios'
    ];
    
    $row = 5;
    foreach ($headers as $col => $header) {
        $sheet->setCellValue($col . $row, $header);
    }
    $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($headerStyle);
    $sheet->getRowDimension($row)->setRowHeight(25);
    
    // Datos de los maestros con formato alternado (filas zebra)
    $row++;
    foreach ($maestros as $index => $maestro) {
        $currentRow = $row + $index;
        
        // Alternar colores de fondo para mejor lectura
        $rowStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => ($index % 2 == 0) ? 'FFFFFF' : 'F1F8E9'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E8F5E9'],
                ],
            ],
        ];
        
        $sheet->setCellValue('A' . $currentRow, $maestro['numEmpleado']);
        $sheet->setCellValue('B' . $currentRow, $maestro['apellido_paterno']);
        $sheet->setCellValue('C' . $currentRow, $maestro['apellido_materno']);
        $sheet->setCellValue('D' . $currentRow, $maestro['nombre']);
        $sheet->setCellValue('E' . $currentRow, $maestro['curp']);
        $sheet->setCellValue('F' . $currentRow, $maestro['telefono_celular']);
        $sheet->setCellValue('G' . $currentRow, $maestro['correo_institucional']);
        $sheet->setCellValue('H' . $currentRow, $maestro['puesto']);
        $sheet->setCellValue('I' . $currentRow, $maestro['area']);
        $sheet->setCellValue('J' . $currentRow, $maestro['tipoContrato']);
        $sheet->setCellValue('K' . $currentRow, $maestro['estatusLaboral']);
        $sheet->setCellValue('L' . $currentRow, $maestro['fechaContratacion']);
        $sheet->setCellValue('M' . $currentRow, $maestro['gradoEstudios']);
        
        // Aplicar estilo a la fila
        $sheet->getStyle('A' . $currentRow . ':M' . $currentRow)->applyFromArray($rowStyle);
        
        // Centrar algunas columnas
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('L' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    
    // Resumen al final
    $totalRow = $row + count($maestros) + 1;
    $sheet->setCellValue('A' . $totalRow, 'Total de Maestros: ' . count($maestros));
    $sheet->mergeCells('A' . $totalRow . ':C' . $totalRow);
    $sheet->getStyle('A' . $totalRow)->getFont()->setBold(true)->setSize(11);
    $sheet->getStyle('A' . $totalRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C8E6C9');
    
    // Autoajustar anchos de columna con un mínimo
    foreach (range('A', 'M') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
        $maxWidth = $sheet->getColumnDimension($column)->getWidth();
        $sheet->getColumnDimension($column)->setWidth(min($maxWidth, 30)); // Máximo 30 de ancho
    }
    
    // Establecer anchos específicos para algunas columnas
    $sheet->getColumnDimension('A')->setWidth(15); // No. Empleado
    $sheet->getColumnDimension('E')->setWidth(20); // CURP
    $sheet->getColumnDimension('G')->setWidth(25); // Correo
    $sheet->getColumnDimension('L')->setWidth(15); // Fecha
    
    // Congelar paneles (fijar encabezados)
    $sheet->freezePane('A6');
    
    // Aplicar filtros a los encabezados
    $sheet->setAutoFilter('A5:M5');
}

// Añade esta función al final del archivo:
function generarCSV($maestros, $filename, $numEmpleado = null) {
    $filename = str_replace('.xlsx', '.csv', $filename);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // Añadir BOM para Excel en Windows
    fwrite($output, "\xEF\xBB\xBF");
    
    if ($numEmpleado && !empty($maestros)) {
        // Exportar maestro individual con formato organizado
        $maestro = $maestros[0];
        
        fputcsv($output, ['REPORTE COMPLETO DEL MAESTRO - CECyTE']);
        fputcsv($output, ['Generado: ' . date('d/m/Y H:i:s')]);
        fputcsv($output, ['']); // Línea vacía
        
        fputcsv($output, ['INFORMACIÓN PERSONAL']);
        fputcsv($output, ['Número de Empleado', $maestro['numEmpleado']]);
        fputcsv($output, ['Nombre Completo', $maestro['nombre'] . ' ' . $maestro['apellido_paterno'] . ' ' . $maestro['apellido_materno']]);
        fputcsv($output, ['CURP', $maestro['curp']]);
        fputcsv($output, ['RFC', $maestro['rfc']]);
        fputcsv($output, ['Fecha Nacimiento', $maestro['fecha_nacimiento']]);
        fputcsv($output, ['Genero', $maestro['id_genero']]);
        fputcsv($output, ['Estado Civil', $maestro['estado_civil']]);
        fputcsv($output, ['Dirección', $maestro['direccion']]);
        fputcsv($output, ['Teléfono', $maestro['telefono_celular']]);
        fputcsv($output, ['Correo Personal', $maestro['correo_personal']]);
        fputcsv($output, ['Correo Institucional', $maestro['correo_institucional']]);
        fputcsv($output, ['']); // Línea vacía
        
        fputcsv($output, ['INFORMACIÓN ACADÉMICA']);
        fputcsv($output, ['Grado de Estudios', $maestro['gradoEstudios']]);
        fputcsv($output, ['Institución', $maestro['institucion']]);
        fputcsv($output, ['Cédula Profesional', $maestro['numCedulaProfesional']]);
        fputcsv($output, ['Especialidad', $maestro['especialidad']]);
        fputcsv($output, ['Año de Titulación', $maestro['anioTitulacion']]);
        fputcsv($output, ['Cursos/Diplomados', $maestro['cursosDiplomados']]);
        fputcsv($output, ['']); // Línea vacía
        
        fputcsv($output, ['INFORMACIÓN LABORAL']);
        fputcsv($output, ['Fecha de Contratación', $maestro['fechaContratacion']]);
        fputcsv($output, ['Tipo de Contrato', $maestro['tipoContrato']]);
        fputcsv($output, ['Puesto', $maestro['puesto']]);
        fputcsv($output, ['Área/Departamento', $maestro['area']]);
        fputcsv($output, ['Horario Laboral', $maestro['horarioLaboral']]);
        fputcsv($output, ['Estatus Laboral', $maestro['estatusLaboral']]);
        fputcsv($output, ['Horario de Clases', $maestro['horarioClases']]);
        fputcsv($output, ['Actividades Extracurriculares', $maestro['actividadesExtracurriculares']]);
        fputcsv($output, ['Observaciones', $maestro['observacionesLaborales']]);
        
    } else {
        // Exportar lista de maestros
        fputcsv($output, ['LISTA DE MAESTROS - CECyTE']);
        fputcsv($output, ['Generado: ' . date('d/m/Y H:i:s')]);
        fputcsv($output, ['']); // Línea vacía
        
        $headers = [
            'No. Empleado',
            'Apellido Paterno',
            'Apellido Materno',
            'Nombre',
            'CURP',
            'Teléfono',
            'Correo Institucional',
            'Puesto',
            'Área',
            'Tipo Contrato',
            'Estatus',
            'Fecha Contratación',
            'Grado Estudios'
        ];
        
        fputcsv($output, $headers);
        
        foreach ($maestros as $maestro) {
            fputcsv($output, [
                $maestro['numEmpleado'],
                $maestro['apellido_paterno'],
                $maestro['apellido_materno'],
                $maestro['nombre'],
                $maestro['curp'],
                $maestro['telefono_celular'],
                $maestro['correo_institucional'],
                $maestro['puesto'],
                $maestro['area'],
                $maestro['tipoContrato'],
                $maestro['estatusLaboral'],
                $maestro['fechaContratacion'],
                $maestro['gradoEstudios']
            ]);
        }
        
        fputcsv($output, ['']); // Línea vacía
        fputcsv($output, ['Total de Maestros:', count($maestros)]);
    }
    
    fclose($output);
}

// Enviar encabezados para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;