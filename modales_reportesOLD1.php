<?php
// Conexión a la base de datos usando PDO
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cecyte_sc";

try {
    $con = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Obtener el término de búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registrosPorPagina = 10;
$offset = ($pagina - 1) * $registrosPorPagina;

// Construir la consulta SQL para alumnos
$sqlAlumnos = "
    SELECT 
        a.id_alumno, 
        a.matriculaAlumno, 
        a.apellidoPaterno, 
        a.apellidoMaterno, 
        a.nombre, 
        a.fechaNacimiento, 
        g.genero, 
        a.rfc, 
        n.nacionalidad
    FROM alumnos a
    LEFT JOIN generos g ON g.id_genero = a.id_genero
    LEFT JOIN nacionalidades n ON n.id_nacionalidad = a.id_nacionalidad
    WHERE a.nombre LIKE :search OR a.matriculaAlumno LIKE :search
    LIMIT $offset, $registrosPorPagina
";
$stmtAlumnos = $con->prepare($sqlAlumnos);
$stmtAlumnos->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmtAlumnos->execute();
$resultAlumnos = $stmtAlumnos->fetchAll(PDO::FETCH_ASSOC);

// Contar el total de registros para la paginación de alumnos
$totalRegistrosAlumnos = $con->query("SELECT COUNT(*) FROM alumnos")->fetchColumn();
$totalPaginasAlumnos = ceil($totalRegistrosAlumnos / $registrosPorPagina);

// Construir la consulta SQL para maestros
$sqlMaestros = "
    SELECT 
        m.id_maestro, 
        m.numEmpleado, 
        m.apellidoPaterno, 
        m.apellidoMaterno, 
        m.nombre, 
        m.fechaNacimiento, 
        g.genero, 
        m.rfc,
        m.curp,
        n.nacionalidad
    FROM maestros m
    LEFT JOIN generos g ON g.id_genero = m.id_genero
    LEFT JOIN nacionalidades n ON n.id_nacionalidad = m.id_nacionalidad
    WHERE m.nombre LIKE :search OR m.numEmpleado LIKE :search
    LIMIT $offset, $registrosPorPagina
";
$stmtMaestros = $con->prepare($sqlMaestros);
$stmtMaestros->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmtMaestros->execute();
$resultMaestros = $stmtMaestros->fetchAll(PDO::FETCH_ASSOC);

// Contar el total de registros para la paginación de maestros
$totalRegistrosMaestros = $con->query("SELECT COUNT(*) FROM maestros")->fetchColumn();
$totalPaginasMaestros = ceil($totalRegistrosMaestros / $registrosPorPagina);

// Construir la consulta SQL para datos académicos de maestros
$sqlDatosAcademicosMaestros = "
    SELECT 
        dam.id_datoAcademicoMaestro,
        dam.numEmpleado,
        ge.gradoEstudio,
        dam.especialidad,
        dam.numCedulaProfesional,
        dam.certificacionesoCursos,
        dam.experienciaDocente
    FROM datosacademicosmaestros dam
    INNER JOIN gradoestudios ge ON ge.id_gradoEstudio = dam.id_gradoEstudio
    WHERE dam.numEmpleado LIKE :search
    LIMIT $offset, $registrosPorPagina
";
$stmtDatosAcademicosMaestros = $con->prepare($sqlDatosAcademicosMaestros);
$stmtDatosAcademicosMaestros->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmtDatosAcademicosMaestros->execute();
$resultDatosAcademicosMaestros = $stmtDatosAcademicosMaestros->fetchAll(PDO::FETCH_ASSOC);

// Contar el total de registros para la paginación de datos académicos de maestros
$totalRegistrosDatosAcademicosMaestros = $con->query("SELECT COUNT(*) FROM datosacademicosmaestros")->fetchColumn();
$totalPaginasDatosAcademicosMaestros = ceil($totalRegistrosDatosAcademicosMaestros / $registrosPorPagina);

// Construir la consulta SQL para Horarios
$sqlHorarios = "
     SELECT 
	hor.id_horario,
	ma.materia,
	g.grupo,
	hor.dia,
	hor.hora_inicio,
	hor.hora_fin,
	maes.nombre
FROM horarios hor
LEFT JOIN materias ma ON ma.id_materia=hor.id_materia
LEFT JOIN grupos g ON g.id_grupo=hor.id_grupo
LEFT JOIN maestros maes ON maes.numEmpleado=hor.numEmpleado
    WHERE ma.materia LIKE :search OR maes.nombre LIKE :search
    LIMIT $offset, $registrosPorPagina
";
$stmtHorarios = $con->prepare($sqlHorarios);
$stmtHorarios->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmtHorarios->execute();
$resultHorarios = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);

// Contar el total de registros para la paginación de Horarios
$totalRegistrosHorarios = $con->query("SELECT COUNT(*) FROM horarios")->fetchColumn();
$totalPaginasHorarios = ceil($totalRegistrosHorarios / $registrosPorPagina);

// Obtener los datos de los alumnos
$sql = "SELECT a.matriculaAlumno, a.rutaImagen, e.tipoEstatus, d.tipoDiscapacidad 
        FROM alumnos a
        INNER JOIN historialacademicoalumnos haa ON haa.matriculaAlumno = a.matriculaAlumno
        INNER JOIN estatus e ON e.id_estatus = haa.id_estatus 
        INNER JOIN discapacidades d ON d.id_discapacidad = a.id_discapacidad";
$stmt = $con->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($result) > 0) {
    foreach ($result as $row) {
        $matriculaAlumno = $row['matriculaAlumno'];
        $rutaImagen = $row['rutaImagen'];
        $tipoEstatus = $row['tipoEstatus'];
        $tipoDiscapacidad = $row['tipoDiscapacidad'];
		
		  } // Cierre del foreach
} // Cierre del if



?>
        


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="img/favicon.ico" type="img/x-icon">
    <link rel="stylesheet" href="styles.css"> <!-- Archivo CSS externo -->
</head>
<body>
    <!-- Modal para Reporte de Alumnos -->
    <div class="modal fade" id="modalReporteAlumnos" tabindex="-1" aria-labelledby="modalReporteAlumnosLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalReporteAlumnosLabel">Reporte de Alumnos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <!-- Buscador -->
                        <div class="search-container">
                            <form method="GET" action="buscar_alumno.php">
                                <input type="text" name="search" id="buscarMatricula" placeholder="Buscar matricula alumno" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit">Buscar</button>
                            </form>
                        </div>

                        <!-- Tabla de Alumnos -->
                        <div class="scrollable-table">
                            <table id="alumnosTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Matricula</th>
                                        <th>Apellido Paterno</th>
                                        <th>Apellido Materno</th>
                                        <th>Nombre</th>
                                        <th>Fecha de Nacimiento</th>
                                        <th>Genero</th>
                                        <th>RFC</th>
                                        <th>Nacionalidad</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (count($resultAlumnos) > 0) {
                                        $contador = 1;
                                        foreach ($resultAlumnos as $row) {
                                            echo "<tr>
                                                    <td>" . $contador . "</td>
                                                    <td>" . htmlspecialchars($row["matriculaAlumno"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["apellidoPaterno"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["apellidoMaterno"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["nombre"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["fechaNacimiento"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["genero"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["rfc"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["nacionalidad"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>
                                                        <a href='editar_alumno.php?id=" . $row['id_alumno'] . "' class='btn btn-warning btn-sm btn-action'><i class='fas fa-edit'></i> Editar</a>
                                                        <button onclick='confirmarEliminacionAlumno(" . $row['id_alumno'] . ")' class='btn btn-danger btn-sm btn-action'><i class='fas fa-trash'></i> Eliminar</button>
                                                    </td>
                                                  </tr>";
                                            $contador++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='10'>No se encontraron resultados.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <nav>
                            <ul class="pagination">
                                <li class="page-item"><a class="page-link" href="?pagina=1">Primera</a></li>
                                <?php for ($i = 1; $i <= $totalPaginasAlumnos; $i++): ?>
                                    <li class="page-item"><a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                                <?php endfor; ?>
                                <li class="page-item"><a class="page-link" href="?pagina=<?php echo $totalPaginasAlumnos; ?>">Última</a></li>
                            </ul>
                        </nav>

                        <!-- Botones de acción -->
                        <div class="button-container">
                            <a href="modales_reportes.php?export_alumnos=pdf" class="btn btn-success"><i class="fas fa-file-pdf"></i> Exportar a PDF</a>
                            <a href="modales_reportes.php?export_alumnos=excel" class="btn btn-success"><i class="fas fa-file-excel"></i> Exportar a Excel</a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Reporte de Maestros -->
    <div class="modal fade" id="modalReporteMaestros" tabindex="-1" aria-labelledby="modalReporteMaestrosLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalReporteMaestrosLabel">Reporte de Maestros</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <!-- Buscador -->
                        <div class="search-container">
                            <form method="GET" action="">
                                <input type="text" name="search" placeholder="Buscar maestro" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit">Buscar</button>
                            </form>
                        </div>

                        <!-- Tabla de Maestros -->
                        <div class="scrollable-table">
                            <table id="maestrosTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Numero de Empleado</th>
                                        <th>Apellido Paterno</th>
                                        <th>Apellido Materno</th>
                                        <th>Nombre</th>
                                        <th>Fecha de Nacimiento</th>
                                        <th>Genero</th>
                                        <th>RFC</th>
                                        <th>CURP</th>
                                        <th>Nacionalidad</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (count($resultMaestros) > 0) {
                                        $contador = 1;
                                        foreach ($resultMaestros as $row) {
                                            echo "<tr>
                                                    <td>" . $contador . "</td>
                                                    <td>" . htmlspecialchars($row["numEmpleado"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["apellidoPaterno"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["apellidoMaterno"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["nombre"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["fechaNacimiento"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["genero"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["rfc"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["curp"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["nacionalidad"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>
                                                        <a href='editar_maestro.php?id=" . $row['id_maestro'] . "' class='btn btn-warning btn-sm btn-action'><i class='fas fa-edit'></i> Editar</a>
                                                        <button onclick='confirmarEliminacionMaestro(" . $row['numEmpleado'] . ")' class='btn btn-danger btn-sm btn-action'><i class='fas fa-trash'></i> Eliminar</button>
                                                    </td>
                                                  </tr>";
                                            $contador++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='11'>No se encontraron resultados.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <nav>
                            <ul class="pagination">
                                <li class="page-item"><a class="page-link" href="?pagina=1">Primera</a></li>
                                <?php for ($i = 1; $i <= $totalPaginasMaestros; $i++): ?>
                                    <li class="page-item"><a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                                <?php endfor; ?>
                                <li class="page-item"><a class="page-link" href="?pagina=<?php echo $totalPaginasMaestros; ?>">Última</a></li>
                            </ul>
                        </nav>

                        <!-- Botones de acción -->
                        <div class="button-container">
                            <a href="modales_reportes.php?export_maestros=pdf" class="btn btn-success"><i class="fas fa-file-pdf"></i> Exportar a PDF</a>
                            <a href="modales_reportes.php?export_maestros=excel" class="btn btn-success"><i class="fas fa-file-excel"></i> Exportar a Excel</a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Reporte de datos académicos Maestros -->
    <div class="modal fade" id="modalReporteDatosAcademicosMaestros" tabindex="-1" aria-labelledby="modalReporteDatosAcademicosMaestrosLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalReporteDatosAcademicosMaestrosLabel">Reporte de Datos Academicos Maestros</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <!-- Buscador -->
                        <div class="search-container">
                            <form method="GET" action="">
                                <input type="text" name="search" placeholder="Buscar maestro nombre o numero empleado" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit">Buscar</button>
                            </form>
                        </div>

                        <!-- Tabla de Datos Academicos Maestros -->
                        <div class="scrollable-table">
                            <table id="datosacademicosmaestrosTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Numero de Empleado</th>
                                        <th>Grado de Estudios</th>
                                        <th>Especialidad</th>
                                        <th>Cedula Profecional</th>
                                        <th>Certificaciones o Cursos</th>
                                        <th>GExperiencia Docente</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (count($resultDatosAcademicosMaestros) > 0) {
                                        $contador = 1;
                                        foreach ($resultDatosAcademicosMaestros as $row) {
                                            echo "<tr>
                                                    <td>" . $contador . "</td>                                                    
                                                    <td>" . htmlspecialchars($row["numEmpleado"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["gradoEstudio"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["especialidad"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["numCedulaProfesional"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["certificacionesoCursos"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["experienciaDocente"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>
                                                        <a href='editar_datosacademicosmaestro.php?id=" . $row['numEmpleado'] . "' class='btn btn-warning btn-sm btn-action'><i class='fas fa-edit'></i> Editar</a>
                                                        <button onclick='confirmarEliminacionDatosAcademicosMaestros(" . $row['numEmpleado'] . ")' class='btn btn-danger btn-sm btn-action'><i class='fas fa-trash'></i> Eliminar</button>
                                                    </td>
                                                  </tr>";
                                            $contador++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='11'>No se encontraron resultados.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <nav>
                            <ul class="pagination">
                                <li class="page-item"><a class="page-link" href="?pagina=1">Primera</a></li>
                                <?php for ($i = 1; $i <= $totalPaginasDatosAcademicosMaestros; $i++): ?>
                                    <li class="page-item"><a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                                <?php endfor; ?>
                                <li class="page-item"><a class="page-link" href="?pagina=<?php echo $totalPaginasDatosAcademicosMaestros; ?>">Última</a></li>
                            </ul>
                        </nav>

                        <!-- Botones de acción -->
                        <div class="button-container">
                            <a href="modales_reportes.php?export_datosacademicosmaestros=pdf" class="btn btn-success"><i class="fas fa-file-pdf"></i> Exportar a PDF</a>
                            <a href="modales_reportes.php?export_datosacademicosmaestros=excel" class="btn btn-success"><i class="fas fa-file-excel"></i> Exportar a Excel</a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Reporte de Horarios -->
    <div class="modal fade" id="modalReporteHorarios" tabindex="-1" aria-labelledby="modalReporteHorariosLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalReporteHorariosLabel">Reporte de Horarios</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <!-- Buscador -->
                        <div class="search-container">
                            <form method="GET" action="">
                                <input type="text" name="search" placeholder="Buscar Horarios" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit">Buscar</button>
                            </form>
                        </div>

                        <!-- Tabla de Horarios -->
                        <div class="scrollable-table">
                            <table id="HorariosTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Materia</th>
                                        <th>Grupo</th>
                                        <th>Dia</th>
                                        <th>Hora de Inicio</th>
                                        <th>Hora de Fin</th>
                                        <th>Maestro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (count($resultHorarios) > 0) {
                                        $contador = 1;
                                        foreach ($resultHorarios as $row) {
                                            echo "<tr>
                                                    <td>" . $contador . "</td>
                                                    <td>" . htmlspecialchars($row["materia"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["grupo"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["dia"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["hora_inicio"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["hora_fin"], ENT_QUOTES, 'UTF-8') . "</td>
                                                    <td>" . htmlspecialchars($row["nombre"], ENT_QUOTES, 'UTF-8') . "</td>                                                    
                                                    <td>
                                                        <a href='editar_Horarios.php?id=" . $row['id_horario'] . "' class='btn btn-warning btn-sm btn-action'><i class='fas fa-edit'></i> Editar</a>
                                                        <button onclick='confirmarEliminacionHorarios(" . $row['id_horario'] . ")' class='btn btn-danger btn-sm btn-action'><i class='fas fa-trash'></i> Eliminar</button>
                                                    </td>
                                                  </tr>";
                                            $contador++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='10'>No se encontraron resultados.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <nav>
                            <ul class="pagination">
                                <li class="page-item"><a class="page-link" href="?pagina=1">Primera</a></li>
                                <?php for ($i = 1; $i <= $totalPaginasHorarios; $i++): ?>
                                    <li class="page-item"><a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                                <?php endfor; ?>
                                <li class="page-item"><a class="page-link" href="?pagina=<?php echo $totalPaginasHorarios; ?>">Última</a></li>
                            </ul>
                        </nav>

                        <!-- Botones de acción -->
                        <div class="button-container">
                            <a href="modales_reportes.php?export_horarios=pdf" class="btn btn-success"><i class="fas fa-file-pdf"></i> Exportar a PDF</a>
                            <a href="modales_reportes.php?export_horarios=excel" class="btn btn-success"><i class="fas fa-file-excel"></i> Exportar a Excel</a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
	
	
	
	
	
	
	<!-- Modal para cada foto del alumno -->
        <div class="modal fade" id="modalReporteFotoAlumno<?php echo $matriculaAlumno; ?>" tabindex="-1" role="dialog" aria-labelledby="modalReporteFotoAlumnoLabel<?php echo $matriculaAlumno; ?>" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalReporteFotoAlumnoLabel<?php echo $matriculaAlumno; ?>">Información del Alumno</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
					      <!-- Mensaje de depuración -->
							<p>Modal cargado correctamente para el alumno: <?php echo $matriculaAlumno; ?></p>
                        <!-- Mostrar la imagen del alumno -->
                        <div class="text-center">
                            <img src="<?php echo $rutaImagen; ?>" alt="Foto del Alumno" class="img-fluid" style="max-width: 200px;">
                        </div>
                        <!-- Mostrar la matrícula -->
                        <p><strong>Matrícula:</strong> <?php echo $matriculaAlumno; ?></p>
                        <!-- Mostrar el estatus -->
                        <p><strong>Estatus:</strong> <?php echo $tipoEstatus; ?></p>
                        <!-- Mostrar el tipo de discapacidad -->
                        <p><strong>Tipo de Discapacidad:</strong> <?php echo $tipoDiscapacidad; ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
		
		
		
		
		

    <!-- JavaScript para confirmar eliminación -->
    <script>
        function confirmarEliminacionAlumno(id_alumno) {
            if (confirm("¿Estás seguro de que deseas eliminar este registro de alumno?")) {
                window.location.href = 'eliminar_alumno.php?id_alumno=' + id_alumno;
            }
        }

        function confirmarEliminacionMaestro(numEmpleado) {
            if (confirm("¿Estás seguro de que deseas eliminar este registro de maestro?")) {
                window.location.href = 'eliminar_maestro.php?numEmpleado=' + numEmpleado;
            }
        }

        function confirmarEliminacionDatosAcademicosMaestros(numEmpleado) {
            if (confirm("¿Estás seguro de que deseas eliminar este registro?")) {
                window.location.href = "eliminar_datosacademicosmaestro.php?numEmpleado=" + numEmpleado;
            }
        }

        function confirmarEliminacionHorarios(id_horario) {
            if (confirm("¿Estás seguro de que deseas eliminar este registro de Horarios?")) {
                window.location.href = 'eliminar_horario.php?id_horario=' + id_horario;
            }
        }
		
		
		
		function exportarPDF() {
            // Obtener la tabla por su ID
            const table = document.getElementById("alumnosTable");

            // Usar html2canvas para convertir la tabla en una imagen
            html2canvas(table).then((canvas) => {
                // Crear un objeto jsPDF
                const pdf = new jspdf.jsPDF('p', 'mm', 'a4'); // Orientación: vertical, formato: A4

                // Obtener la imagen en formato Data URL
                const imgData = canvas.toDataURL('image/png');

                // Calcular el ancho y alto de la imagen en el PDF
                const imgWidth = 190; // Ancho máximo en mm
                const imgHeight = (canvas.height * imgWidth) / canvas.width;

                // Agregar la imagen al PDF
                pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);

                // Guardar el PDF con un nombre específico
                pdf.save('export_alumnos.pdf');
            });
        }
		
		
		
		
    </script>

    <!-- Bootstrap JS y dependencias (Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- jQuery -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<!-- Bootstrap JS -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>