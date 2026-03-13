<?php
// exportarA_excel.php

// Incluir configuración común
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    die("Error: Archivo de configuración no encontrado");
}

// Verificar sesión
verificarSesion();

// Conectar a la base de datos
$con = conectarDB();

// Recoger parámetros de búsqueda y filtros
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_genero = isset($_GET['genero']) ? intval($_GET['genero']) : '';
$filtro_discapacidad = isset($_GET['discapacidad']) ? intval($_GET['discapacidad']) : '';

try {
    // Construir consulta con filtros (similar a lista_alumnos.php)
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
    
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

// Configurar headers para descarga de Excel
$filename = "alumnos_" . date('Y-m-d') . ".xls";

// Headers para forzar descarga como Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Configurar separador (tab para Excel)
$separator = "\t";

// Comenzar la salida
echo "LISTADO DE ALUMNOS - CECyTE\n";
echo "Fecha de generación: " . date('d/m/Y H:i:s') . "\n";
echo "Total de registros: " . count($alumnos) . "\n";

// Información de filtros aplicados
if (!empty($busqueda) || !empty($filtro_genero) || !empty($filtro_discapacidad)) {
    echo "Filtros aplicados: ";
    
    $filtros = [];
    if (!empty($busqueda)) {
        $filtros[] = "Búsqueda: '$busqueda'";
    }
    if (!empty($filtro_genero)) {
        // Obtener nombre del género
        $sql_genero = "SELECT genero FROM generos WHERE id_genero = :id_genero";
        $stmt_genero = $con->prepare($sql_genero);
        $stmt_genero->bindParam(':id_genero', $filtro_genero);
        $stmt_genero->execute();
        $genero_filtrado = $stmt_genero->fetch(PDO::FETCH_ASSOC);
        $filtros[] = "Género: " . ($genero_filtrado['genero'] ?? '');
    }
    if (!empty($filtro_discapacidad)) {
        // Obtener nombre de la discapacidad
        $sql_disc = "SELECT tipoDiscapacidad FROM discapacidades WHERE id_discapacidad = :id_discapacidad";
        $stmt_disc = $con->prepare($sql_disc);
        $stmt_disc->bindParam(':id_discapacidad', $filtro_discapacidad);
        $stmt_disc->execute();
        $discapacidad_filtrada = $stmt_disc->fetch(PDO::FETCH_ASSOC);
        $filtros[] = "Discapacidad: " . ($discapacidad_filtrada['tipoDiscapacidad'] ?? '');
    }
    
    echo implode(" | ", $filtros) . "\n";
} else {
    echo "Filtros aplicados: Ninguno\n";
}

echo "\n"; // Línea en blanco

// Encabezados de columnas
$headers = array(
    'No.',
    'Matrícula',
    'Apellido Paterno',
    'Apellido Materno',
    'Nombre(s)',
    'Fecha Nacimiento',
    'Género',
    'CURP',
    'RFC',
    'Email Institucional',
    'Email Personal',
    'Teléfono Celular',
    'Teléfono Casa',
    'Dirección',
    'Colonia',
    'Ciudad',
    'Estado',
    'Código Postal',
    'Discapacidad',
    'Tipo Discapacidad',
    'Porcentaje Discapacidad',
    'Nacionalidad',
    'Estado Civil',
    'Escuela Procedencia',
    'Fecha Ingreso',
    'Fecha Alta',
    'Estatus',
    'Observaciones'
);

// Imprimir encabezados
echo implode($separator, $headers) . "\n";

// Datos de los alumnos
$contador = 1;
foreach ($alumnos as $alumno) {
    $row = array(
        $contador++,
        $alumno['matricula'] ?? '',
        $alumno['apellido_paterno'] ?? '',
        $alumno['apellido_materno'] ?? '',
        $alumno['nombre'] ?? '',
        !empty($alumno['fecha_nacimiento']) ? date('d/m/Y', strtotime($alumno['fecha_nacimiento'])) : '',
        $alumno['genero'] ?? '',
        $alumno['curp'] ?? '',
        $alumno['rfc'] ?? '',
        $alumno['correo_institucional'] ?? '',
        $alumno['correo_personal'] ?? '',
        $alumno['telefono_celular'] ?? '',
        $alumno['telefono_casa'] ?? '',
        $alumno['direccion'] ?? '',
        $alumno['colonia'] ?? '',
        $alumno['ciudad'] ?? '',
        $alumno['estado'] ?? '',
        $alumno['codigo_postal'] ?? '',
        !empty($alumno['tipoDiscapacidad']) ? 'Sí' : 'No',
        $alumno['tipoDiscapacidad'] ?? 'Ninguna',
        $alumno['porcentajeDiscapacidad'] ?? '0',
        $alumno['nacionalidad'] ?? '',
        $alumno['estado_civil'] ?? '',
        $alumno['escuela_procedencia'] ?? '',
        !empty($alumno['fecha_ingreso']) ? date('d/m/Y', strtotime($alumno['fecha_ingreso'])) : '',
        !empty($alumno['fecha_alta']) ? date('d/m/Y H:i:s', strtotime($alumno['fecha_alta'])) : '',
        $alumno['estatus'] ?? 'Activo',
        str_replace(array("\r", "\n", "\t"), ' ', $alumno['observaciones'] ?? '') // Limpiar saltos de línea
    );
    
    // Asegurar que todos los valores estén limpios para Excel
    $row = array_map(function($value) {
        // Escapar comillas y convertir a UTF-8 si es necesario
        $value = str_replace('"', '""', $value);
        // Si contiene tabuladores o saltos de línea, rodear con comillas
        if (preg_match('/[\t\n\r]/', $value)) {
            $value = '"' . $value . '"';
        }
        return $value;
    }, $row);
    
    echo implode($separator, $row) . "\n";
}

// Pie de página con información adicional
echo "\n\n";
echo "INFORMACIÓN DEL SISTEMA\n";
echo "Generado por: " . ($_SESSION['username'] ?? 'Desconocido') . "\n";
echo "Fecha de exportación: " . date('d/m/Y H:i:s') . "\n";
echo "Sistema: CECyTE Santa Catarina N.L.\n";
echo "Módulo: Control de Alumnos\n";

exit;