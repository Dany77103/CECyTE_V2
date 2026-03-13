<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cecyte_sc";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener datos de las tablas relacionadas
$generos = $conn->query("SELECT id_genero, genero FROM generos");
$nacionalidades = $conn->query("SELECT id_nacionalidad, nacionalidad FROM nacionalidades");
$estados = $conn->query("SELECT id_estado, nombreEstado FROM estados");
$discapacidades = $conn->query("SELECT id_discapacidad, tipoDiscapacidad FROM discapacidades");
$puestos = $conn->query("SELECT id_puesto, puesto FROM puestos");
$estatus = $conn->query("SELECT id_estatus, tipoEstatus FROM estatus");
$gradosEstudio = $conn->query("SELECT id_gradoEstudio, gradoEstudio FROM gradoestudios");
$semestres = $conn->query("SELECT id_semestre, semestre FROM semestres");
$grupos = $conn->query("SELECT id_grupo, grupo FROM grupos");
$materias = $conn->query("SELECT id_materia, materia FROM materias");
$estadonacimiento = $conn->query("SELECT id_estadoNacimiento, estado_Nacimiento FROM estadonacimiento");
?>

<!DOCTYPE html>
<html lang="es">
<body>

<!-- Modal para Alta de Alumnos -->
<div class="modal fade" id="modalAlumnos" tabindex="-1" aria-labelledby="modalAlumnosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAlumnosLabel">Registrar Alta de Alumnos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="guardar_alumno.php" method="POST">
                    <!-- Campos del formulario para alumnos -->
                    <div class="mb-3">
                        <label for="matriculaAlumno" class="form-label">Matricula</label>
                        <input type="text" class="form-control" id="matriculaAlumno" name="matriculaAlumno" required>
                    </div>
                    <div class="mb-3">
                        <label for="apellidoPaterno" class="form-label">Apellido Paterno</label>
                        <input type="text" class="form-control" id="apellidoPaterno" name="apellidoPaterno" required>
                    </div>
                    <div class="mb-3">
                        <label for="apellidoMaterno" class="form-label">Apellido Materno</label>
                        <input type="text" class="form-control" id="apellidoMaterno" name="apellidoMaterno" required>
                    </div>
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="fechaNacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fechaNacimiento" name="fechaNacimiento" required>
                    </div>
                    <div class="mb-3">
                        <label for="id_genero" class="form-label">Genero</label>
                        <select class="form-select" id="id_genero" name="id_genero" required>
                            <option value="">Selecciona un genero</option>
                            <?php while ($row = $generos->fetch_assoc()): ?>
                                <option value="<?= $row['id_genero'] ?>"><?= $row['genero'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="rfc" class="form-label">RFC</label>
                        <input type="text" class="form-control" id="rfc" name="rfc" required>
                    </div>
                    <div class="mb-3">
                        <label for="id_nacionalidad" class="form-label">Nacionalidad</label>
                        <select class="form-select" id="id_nacionalidad" name="id_nacionalidad" required>
                            <option value="">Selecciona una nacionalidad</option>
                            <?php while ($row = $nacionalidades->fetch_assoc()): ?>
                                <option value="<?= $row['id_nacionalidad'] ?>"><?= $row['nacionalidad'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id_estadoNacimiento" class="form-label">Estado de Nacimiento</label>
                        <select class="form-select" id="id_estadoNacimiento" name="id_estadoNacimiento" required>
                            <option value="">Selecciona estado donde Naciste</option>
                            <?php while ($row = $estadonacimiento->fetch_assoc()): ?>
                                <option value="<?= $row['id_estadoNacimiento'] ?>"><?= $row['estado_Nacimiento'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Direccion</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" required>
                    </div>
                    <div class="mb-3">
                        <label for="numCelular" class="form-label">Numero de Celular</label>
                        <input type="text" class="form-control" id="numCelular" name="numCelular" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefonoEmergencia" class="form-label">Telefono de Emergencia</label>
                        <input type="text" class="form-control" id="telefonoEmergencia" name="telefonoEmergencia" required>
                    </div>
                    <div class="mb-3">
                        <label for="mailInstitucional" class="form-label">Correo Institucional</label>
                        <input type="email" class="form-control" id="mailInstitucional" name="mailInstitucional" required>
                    </div>
                    <div class="mb-3">
                        <label for="mailPersonal" class="form-label">Correo Personal</label>
                        <input type="email" class="form-control" id="mailPersonal" name="mailPersonal" required>
                    </div>
                    <div class="mb-3">
                        <label for="id_discapacidad" class="form-label">Discapacidad</label>
                        <select class="form-select" id="id_discapacidad" name="id_discapacidad" required>
                            <option value="">Selecciona tu discapacidad</option>
                            <?php while ($row = $discapacidades->fetch_assoc()): ?>
                                <option value="<?= $row['id_discapacidad'] ?>"><?= $row['tipoDiscapacidad'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>                    
					<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
					</div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Alta de Maestros -->
<div class="modal fade" id="modalMaestros" tabindex="-1" aria-labelledby="modalMaestrosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMaestrosLabel">Registro Alta de Maestros o Admvos.</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="guardar_maestro.php" method="POST">
                    <!-- Campos del formulario para maestros -->
                    <div class="mb-3">
                        <label for="numEmpleado" class="form-label">Numero de Empleado</label>
                        <input type="text" class="form-control" id="numEmpleado" name="numEmpleado" required>
                    </div>
                    <div class="mb-3">
                        <label for="apellidoPaterno" class="form-label">Apellido Paterno</label>
                        <input type="text" class="form-control" id="apellidoPaterno" name="apellidoPaterno" required>
                    </div>
                    <div class="mb-3">
                        <label for="apellidoMaterno" class="form-label">Apellido Materno</label>
                        <input type="text" class="form-control" id="apellidoMaterno" name="apellidoMaterno" required>
                    </div>
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="fechaNacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fechaNacimiento" name="fechaNacimiento" required>
                    </div>
                    <div class="mb-3">
                        <label for="id_genero" class="form-label">Genero</label>
                        <select class="form-select" id="id_genero" name="id_genero" required>
                            <option value="">Selecciona tu genero</option>
                            <?php
                            // Reiniciar el puntero del resultado
                            $generos->data_seek(0);
                            while ($row = $generos->fetch_assoc()): ?>
                                <option value="<?= $row['id_genero'] ?>"><?= $row['genero'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="rfc" class="form-label">RFC</label>
                        <input type="text" class="form-control" id="rfc" name="rfc" required>
                    </div>
                    <div class="mb-3">
                        <label for="curp" class="form-label">CURP</label>
                        <input type="text" class="form-control" id="curp" name="curp" required>
                    </div>
                    <div class="mb-3">
                        <label for="id_nacionalidad" class="form-label">Nacionalidad</label>
                        <select class="form-select" id="id_nacionalidad" name="id_nacionalidad" required>
                            <option value="">Selecciona tu nacionalidad</option>
                            <?php
                            // Reiniciar el puntero del resultado
                            $nacionalidades->data_seek(0);
                            while ($row = $nacionalidades->fetch_assoc()): ?>
                                <option value="<?= $row['id_nacionalidad'] ?>"><?= $row['nacionalidad'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id_estadoNacimiento" class="form-label">Estado de Nacimiento</label>
                        <select class="form-select" id="id_estadoNacimiento" name="id_estadoNacimiento" required>
                            <option value="">Selecciona tu estado de Nacimiento</option>
                            <?php
                            // Reiniciar el puntero del resultado
                            $estadonacimiento->data_seek(0);
                            while ($row = $estadonacimiento->fetch_assoc()): ?>
                                <option value="<?= $row['id_estadoNacimiento'] ?>"><?= $row['estado_Nacimiento'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Direccion</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" required>
                    </div>
                    <div class="mb-3">
                        <label for="numCelular" class="form-label">Numero de Celular</label>
                        <input type="text" class="form-control" id="numCelular" name="numCelular" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefonoEmergencia" class="form-label">Telefono de Emergencia</label>
                        <input type="text" class="form-control" id="telefonoEmergencia" name="telefonoEmergencia" required>
                    </div>
                    <div class="mb-3">
                        <label for="mailInstitucional" class="form-label">Correo Institucional</label>
                        <input type="email" class="form-control" id="mailInstitucional" name="mailInstitucional" required>
                    </div>
                    <div class="mb-3">
                        <label for="mailPersonal" class="form-label">Correo Personal</label>
                        <input type="email" class="form-control" id="mailPersonal" name="mailPersonal" required>
                    </div>
                    <div class="modal-footer">
					<button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
					</div>
                </form>
            </div>
        </div>
    </div>
</div>




<!-- Modal para Datos Laborales -->
<div class="modal fade" id="modalDatosLaborales" tabindex="-1" aria-labelledby="modalMaestrosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDatosLaborales">Registro Alta de Datos Laborales Maestros o Admvos.</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="guardar_datos_laborales.php" method="POST">
                    <!-- Campos del formulario para maestros -->
                    <div class="mb-3">
                        <label for="numEmpleado" class="form-label">Numero de Empleado</label>
                        <input type="text" class="form-control" id="numEmpleado" name="numEmpleado" required>
                    </div>                    
					<div class="mb-3">
                        <label for="fechaContratacion" class="form-label">Fecha de Contratacion</label>
                        <input type="text" class="form-control" id="fechaContratacion" name="fechaContratacion" required>
                    </div>					
					<div class="mb-3">
                        <label for="tipoContrato" class="form-label">Tipo de Contrato</label>
                        <input type="text" class="form-control" id="tipoContrato" name="tipoContrato" required>
                    </div>					
					<div class="mb-3">
                        <label for="id_puesto" class="form-label">Puesto</label>
                        <select class="form-select" id="id_puesto" name="id_puesto" required>
                            <option value="">Selecciona tu Puesto</option>
                            <?php
                            // Reiniciar el puntero del resultado
                            $puestos->data_seek(0);
                            while ($row = $puestos->fetch_assoc()): ?>
                                <option value="<?= $row['id_puesto'] ?>"><?= $row['puesto'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>					
					<div class="mb-3">
                        <label for="area" class="form-label">Area</label>
                        <input type="text" class="form-control" id="area" name="area" required>
                    </div>
					<div class="mb-3">
                        <label for="horarioLaboral" class="form-label">Horario Laboral</label>
                        <input type="text" class="form-control" id="horarioLaboral" name="horarioLaboral" required>
                    </div>				
					<div class="mb-3">
                        <label for="id_estatus" class="form-label">Estatus</label>
                        <select class="form-select" id="id_estatus" name="id_estatus" required>
                            <option value="">Selecciona un Estatus</option>
                            <?php
                            // Reiniciar el puntero del resultado
                            $estatus->data_seek(0);
                            while ($row = $estatus->fetch_assoc()): ?>
                                <option value="<?= $row['id_estatus'] ?>"><?= $row['tipoEstatus'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
					<div class="mb-3">
                        <label for="horarioClases" class="form-label">Horario Clases</label>
                        <input type="text" class="form-control" id="horarioClases" name="horarioClases" required>
                    </div>
					<div class="mb-3">
                        <label for="actividadesExtracurriculares" class="form-label">Actividades Extracurriculares</label>
                        <input type="text" class="form-control" id="actividadesExtracurriculares" name="actividadesExtracurriculares" required>
                    </div>
					<div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <input type="text" class="form-control" id="observaciones" name="observaciones" required>
                    </div>					
                    <div class="modal-footer">
					<button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
					</div>
                </form>
            </div>
        </div>
    </div>
</div>








<!-- Modal para Datos Academicos -->
<div class="modal fade" id="modalDatosAcademicos" tabindex="-1" aria-labelledby="modalMaestrosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDatosAcademicos">Alta de Datos Academicos Maestros o Admvos.</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="guardar_datos_academicos.php" method="POST">
                    <!-- Campos del formulario para Datos Academicos -->
                    <div class="mb-3">
                        <label for="numEmpleado" class="form-label">Numero de Empleado</label>
                        <input type="text" class="form-control" id="numEmpleado" name="numEmpleado" required>
                    </div>                    
					<div class="mb-3">
                        <label for="id_gradoEstudio" class="form-label">Grado de Estudios</label>
                        <select class="form-select" id="id_gradoEstudio" name="id_gradoEstudio" required>
                            <option value="">Selecciona el Grado Maximo de Estudios</option>
                            <?php
                            // Reiniciar el puntero del resultado
                            $gradosEstudio->data_seek(0);
                            while ($row = $gradosEstudio->fetch_assoc()): ?>
                                <option value="<?= $row['id_gradoEstudio'] ?>"><?= $row['gradoEstudio'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
					
					<div class="mb-3">
                        <label for="especialidad" class="form-label">Especialidad</label>
                        <input type="text" class="form-control" id="especialidad" name="especialidad" required>
                    </div>
					<div class="mb-3">
                        <label for="numCedulaProfesional" class="form-label">Numero de Cedula Profecional</label>
                        <input type="text" class="form-control" id="numCedulaProfesional" name="numCedulaProfesional" required>
                    </div>
					<div class="mb-3">
                        <label for="certificacionesoCursos" class="form-label">Certificaciones o Cursos</label>
                        <input type="text" class="form-control" id="certificacionesoCursos" name="certificacionesoCursos" required>
                    </div>
					<div class="mb-3">
                        <label for="experienciaDocente" class="form-label">Experiencia Docente</label>
                        <input type="text" class="form-control" id="experienciaDocente" name="experienciaDocente" required>
                    </div>
					<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
					</div>
                </form>
            </div>
        </div>
    </div>
</div>






<!-- Modal para Historial Academico -->
<div class="modal fade" id="modalHistorialAcademico" tabindex="-1" aria-labelledby="modalHistorialAcademicoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHistorialAcademico">Alta de Historial Academico Alumnos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="guardar_historial_academico.php" method="POST">
                    <!-- Campos del formulario para Historial Academico -->
                    <div class="mb-3">
                        <label for="matriculaAlumno" class="form-label">Matricula del Alumno</label>
                        <input type="text" class="form-control" id="matriculaAlumno" name="matriculaAlumno" required>
                    </div>    				
					<div class="mb-3">
                        <label for="id_semestre" class="form-label">Semestre</label>
                        <select class="form-select" id="id_semestre" name="id_semestre" required>
                            <option value="">Selecciona un Semestre</option>
                            <?php
                            // Reiniciar el puntero del resultado
                            $semestres->data_seek(0);
                            while ($row = $semestres->fetch_assoc()): ?>
                                <option value="<?= $row['id_semestre'] ?>"><?= $row['semestre'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
					<div class="mb-3">
                        <label for="id_grupo" class="form-label">Grupo</label>
                        <select class="form-select" id="id_grupo" name="id_grupo" required>
                            <option value="">Selecciona el Grupo</option>
                            <?php
                            // Reiniciar el puntero del resultado
                            $grupos->data_seek(0);
                            while ($row = $grupos->fetch_assoc()): ?>
                                <option value="<?= $row['id_grupo'] ?>"><?= $row['grupo'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
					<div class="mb-3">
                        <label for="cicloEscolar" class="form-label">Ciclo Escolar</label>
                        <input type="text" class="form-control" id="cicloEscolar" name="cicloEscolar" required>
                    </div>
					<div class="mb-3">
                        <label for="id_estatus" class="form-label">Estatus</label>
                        <select class="form-select" id="id_estatus" name="id_estatus" required>
                            <option value="">Selecciona tu Estatus</option>
                            <?php
                            // Reiniciar el puntero del resultado
                            $estatus->data_seek(0);
                            while ($row = $estatus->fetch_assoc()): ?>
                                <option value="<?= $row['id_estatus'] ?>"><?= $row['tipoEstatus'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
					<div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <input type="text" class="form-control" id="observaciones" name="observaciones" required>
                    </div>										
                    <div class="modal-footer">
					<button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
					</div>
                </form>
            </div>
        </div>
    </div>
</div>



<!-- Modal para Horarios -->
<div class="modal fade" id="modalHorarios" tabindex="-1" aria-labelledby="modalHorariosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHorarios">Alta de Horarios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="guardar_horarios.php" method="POST">
                    <!-- Campos del formulario para Horarios -->
                   
					<div class="mb-3">
                        <label for="id_materia" class="form-label">Matria</label>
                        <select class="form-select" id="id_materia" name="id_materia" required>
                            <option value="">Selecciona una Materia</option>
                            <?php
                            // Reiniciar el puntero del resultado
                            $materias->data_seek(0);
                            while ($row = $materias->fetch_assoc()): ?>
                                <option value="<?= $row['id_materia'] ?>"><?= $row['materia'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                   
					<div class="mb-3">
                        <label for="id_grupo" class="form-label">Grupo</label>
                        <select class="form-select" id="id_grupo" name="id_grupo" required>
                            <option value="">Selecciona un Grupo</option>
                            <?php
                            // Reiniciar el puntero del resultado
                            $grupos->data_seek(0);
                            while ($row = $grupos->fetch_assoc()): ?>
                                <option value="<?= $row['id_grupo'] ?>"><?= $row['grupo'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
					<div class="mb-3">
                        <label for="dia" class="form-label">Dia</label>
                        <input type="text" class="form-control" id="dia" name="dia" required>
                    </div>
					<div class="mb-3">
                        <label for="hora_inicio" class="form-label">Hora Inicio</label>
                        <input type="text" class="form-control" id="hora_inicio" name="hora_inicio" required>
                    </div>
					<div class="mb-3">
                        <label for="hora_fin" class="form-label">Hora Fin</label>
                        <input type="text" class="form-control" id="hora_fin" name="hora_fin" required>
                    </div>
					<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
					</div>
                </form>
            </div>
        </div>
    </div>
</div>



<!-- Modal Subir Foto-->
<div class="modal fade" id="modalSubirFoto" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Subir Foto del Alumno</h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>        
      </div>
      <div class="modal-body">
        <form action="subir_foto.php" method="post" enctype="multipart/form-data">
          <div class="form-group">
            <label for="matriculaAlumno">Matricula del Alumno</label>
            <input type="text" class="form-control" id="matriculaAlumno" name="matriculaAlumno" required>
          </div>
          <div class="form-group">
            <label for="foto">Seleccionar Foto</label>
            <input type="file" class="form-control-file" id="foto" name="foto" required>
          </div>
		  <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Subir Foto</button>
		  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
		  </div>
        </form>
      </div>
    </div>
  </div>
</div>




</body>
</html>