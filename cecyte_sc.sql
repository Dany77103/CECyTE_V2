/*
SQLyog Ultimate v8.71 
MySQL - 5.5.5-10.4.22-MariaDB : Database - cecyte_sc
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`cecyte_sc` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;

USE `cecyte_sc`;

/*Table structure for table `alumnos` */

DROP TABLE IF EXISTS `alumnos`;

CREATE TABLE `alumnos` (
  `id_alumno` int(11) NOT NULL AUTO_INCREMENT,
  `matriculaAlumno` varchar(20) NOT NULL,
  `apellidoPaterno` varchar(50) NOT NULL,
  `apellidoMaterno` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `fechaNacimiento` date NOT NULL,
  `id_genero` int(11) NOT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `id_nacionalidad` int(11) NOT NULL,
  `id_estadoNacimiento` int(11) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `numCelular` varchar(15) DEFAULT NULL,
  `telefonoEmergencia` varchar(15) DEFAULT NULL,
  `mailInstitucional` varchar(100) NOT NULL,
  `mailPersonal` varchar(100) DEFAULT NULL,
  `id_discapacidad` int(11) DEFAULT NULL,
  `rutaImagen` varchar(255) DEFAULT NULL,
  `fechaAlta` timestamp NOT NULL DEFAULT current_timestamp(),
  `fechaModificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_alumno`),
  UNIQUE KEY `matriculaAlumno` (`matriculaAlumno`),
  UNIQUE KEY `mailInstitucional` (`mailInstitucional`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4;

/*Data for the table `alumnos` */

LOCK TABLES `alumnos` WRITE;

insert  into `alumnos`(`id_alumno`,`matriculaAlumno`,`apellidoPaterno`,`apellidoMaterno`,`nombre`,`fechaNacimiento`,`id_genero`,`rfc`,`id_nacionalidad`,`id_estadoNacimiento`,`direccion`,`numCelular`,`telefonoEmergencia`,`mailInstitucional`,`mailPersonal`,`id_discapacidad`,`rutaImagen`,`fechaAlta`,`fechaModificacion`) values (23,'A1','Ap1','Am1','Alumno1','2000-01-01',2,'RFC1',1,3,'D1','C1','E1','no@no.com','no@no.com',1,'img/aldo.jpg','2025-02-14 18:41:41','2025-02-17 18:07:13'),(25,'A2','Ap2','Am2','Alumno2','2000-01-01',1,'RFC2',1,3,'D2','C2','E2','no2@no.com','no2@no.com',2,'img/carlos.jpg','2025-02-14 18:42:42','2025-02-17 19:26:03'),(26,'A3','Ap3','Am3','Alumno3','2000-01-01',3,'RFC3',1,3,'D3','C3','E3','no3@no.com','no3@no.com',3,'img/juan.jpg','2025-02-14 18:43:28','2025-02-17 23:52:19');

UNLOCK TABLES;

/*Table structure for table `alumnos_qr` */

DROP TABLE IF EXISTS `alumnos_qr`;

CREATE TABLE `alumnos_qr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_alumno` int(11) NOT NULL,
  `codigo_qr` varchar(100) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_qr` (`codigo_qr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*Data for the table `alumnos_qr` */

LOCK TABLES `alumnos_qr` WRITE;

UNLOCK TABLES;

/*Table structure for table `asistencias` */

DROP TABLE IF EXISTS `asistencias`;

CREATE TABLE `asistencias` (
  `id_asistencia` int(11) NOT NULL AUTO_INCREMENT,
  `matriculaAlumno` varchar(20) NOT NULL,
  `fecha` date NOT NULL,
  `asistio` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_asistencia`),
  KEY `idx_matricula_fecha` (`matriculaAlumno`,`fecha`),
  CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`matriculaAlumno`) REFERENCES `alumnos` (`matriculaAlumno`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

/*Data for the table `asistencias` */

LOCK TABLES `asistencias` WRITE;

UNLOCK TABLES;

/*Table structure for table `asistencias_qr` */

DROP TABLE IF EXISTS `asistencias_qr`;

CREATE TABLE `asistencias_qr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_alumno` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `dispositivo` varchar(100) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_asistencia` (`id_alumno`,`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*Data for the table `asistencias_qr` */

LOCK TABLES `asistencias_qr` WRITE;

UNLOCK TABLES;

/*Table structure for table `calificaciones` */

DROP TABLE IF EXISTS `calificaciones`;

CREATE TABLE `calificaciones` (
  `id_calificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_historialAcademicoAlumno` int(11) NOT NULL,
  `calificacion` decimal(5,2) NOT NULL,
  `numEmpleado` varchar(20) NOT NULL,
  `matriculaAlumno` varchar(2) NOT NULL,
  PRIMARY KEY (`id_calificacion`),
  KEY `id_historialAcademicoAlumno` (`id_historialAcademicoAlumno`),
  CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`id_historialAcademicoAlumno`) REFERENCES `historialacademicoalumnos` (`id_historialAcademicoAlumno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*Data for the table `calificaciones` */

LOCK TABLES `calificaciones` WRITE;

UNLOCK TABLES;

/*Table structure for table `datosacademicosmaestros` */

DROP TABLE IF EXISTS `datosacademicosmaestros`;

CREATE TABLE `datosacademicosmaestros` (
  `id_datoAcademicoMaestro` int(11) NOT NULL AUTO_INCREMENT,
  `numEmpleado` varchar(20) NOT NULL,
  `id_gradoEstudio` int(11) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `numCedulaProfesional` varchar(50) DEFAULT NULL,
  `certificacionesoCursos` text DEFAULT NULL,
  `experienciaDocente` text DEFAULT NULL,
  PRIMARY KEY (`id_datoAcademicoMaestro`),
  KEY `numEmpleado` (`numEmpleado`),
  KEY `id_gradoEstudio` (`id_gradoEstudio`),
  CONSTRAINT `datosacademicosmaestros_ibfk_1` FOREIGN KEY (`numEmpleado`) REFERENCES `maestros` (`numEmpleado`),
  CONSTRAINT `datosacademicosmaestros_ibfk_2` FOREIGN KEY (`id_gradoEstudio`) REFERENCES `gradoestudios` (`id_gradoEstudio`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;

/*Data for the table `datosacademicosmaestros` */

LOCK TABLES `datosacademicosmaestros` WRITE;

insert  into `datosacademicosmaestros`(`id_datoAcademicoMaestro`,`numEmpleado`,`id_gradoEstudio`,`especialidad`,`numCedulaProfesional`,`certificacionesoCursos`,`experienciaDocente`) values (7,'M1',1,'historia','NCP1','muchos de todo','cinco años tal vez 10'),(8,'M2',2,'geografia','NCP2','muchos de todo','cinco años tal vez 10'),(9,'M3',3,'fisica','NCP3','muchos de todo','cinco años tal vez 10'),(10,'234',3,'s','sdf','sdf','sdf');

UNLOCK TABLES;

/*Table structure for table `datoslaboralesmaestros` */

DROP TABLE IF EXISTS `datoslaboralesmaestros`;

CREATE TABLE `datoslaboralesmaestros` (
  `id_datosLaborales` int(11) NOT NULL AUTO_INCREMENT,
  `numEmpleado` varchar(20) NOT NULL,
  `fechaContratacion` date NOT NULL,
  `tipoContrato` varchar(50) NOT NULL,
  `id_puesto` int(11) NOT NULL,
  `area` varchar(100) NOT NULL,
  `horarioLaboral` varchar(50) NOT NULL,
  `id_estatus` int(11) NOT NULL,
  `horarioClases` varchar(50) DEFAULT NULL,
  `actividadesExtracurriculares` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_datosLaborales`),
  KEY `numEmpleado` (`numEmpleado`),
  KEY `id_puesto` (`id_puesto`),
  KEY `id_estatus` (`id_estatus`),
  CONSTRAINT `datoslaboralesmaestros_ibfk_1` FOREIGN KEY (`numEmpleado`) REFERENCES `maestros` (`numEmpleado`),
  CONSTRAINT `datoslaboralesmaestros_ibfk_2` FOREIGN KEY (`id_puesto`) REFERENCES `puestos` (`id_puesto`),
  CONSTRAINT `datoslaboralesmaestros_ibfk_3` FOREIGN KEY (`id_estatus`) REFERENCES `estatus` (`id_estatus`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;

/*Data for the table `datoslaboralesmaestros` */

LOCK TABLES `datoslaboralesmaestros` WRITE;

insert  into `datoslaboralesmaestros`(`id_datosLaborales`,`numEmpleado`,`fechaContratacion`,`tipoContrato`,`id_puesto`,`area`,`horarioLaboral`,`id_estatus`,`horarioClases`,`actividadesExtracurriculares`,`observaciones`) values (7,'M1','2025-01-01','siempre contratado',1,'salones','de lunes a viernes de 1 a 2',1,'de lun a vie de 1 a 2 ','futbolista','muy buen trabajo'),(8,'M2','2025-01-01','siempre contratado',1,'salones','de lunes a viernes de 1 a 2',1,'de lun a vie de 1 a 2 ','futbolista','muy buen trabajo'),(9,'M2','2025-01-01','siempre contratado',1,'salones','de lunes a viernes de 1 a 2',1,'de lun a vie de 1 a 2 ','futbolista','muy buen trabajo'),(10,'M3','2025-01-01','siempre contratado',1,'salones','de lunes a viernes de 1 a 2',1,'de lun a vie de 1 a 2 ','futbolista','muy buen trabajo'),(11,'234','0000-00-00','w',4,'er','sd',5,'sg','sg','s');

UNLOCK TABLES;

/*Table structure for table `discapacidades` */

DROP TABLE IF EXISTS `discapacidades`;

CREATE TABLE `discapacidades` (
  `id_discapacidad` int(11) NOT NULL AUTO_INCREMENT,
  `tipoDiscapacidad` varchar(100) NOT NULL,
  PRIMARY KEY (`id_discapacidad`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

/*Data for the table `discapacidades` */

LOCK TABLES `discapacidades` WRITE;

insert  into `discapacidades`(`id_discapacidad`,`tipoDiscapacidad`) values (1,'Discapacidad visual'),(2,'Discapacidad auditiva'),(3,'Discapacidad motriz'),(4,'Discapacidad intelectual'),(5,'Discapacidad psicosocial');

UNLOCK TABLES;

/*Table structure for table `discapacidadesalumnos` */

DROP TABLE IF EXISTS `discapacidadesalumnos`;

CREATE TABLE `discapacidadesalumnos` (
  `id_discapacidadAlumno` int(11) NOT NULL AUTO_INCREMENT,
  `matriculaAlumno` varchar(20) NOT NULL,
  `id_discapacidad` int(11) NOT NULL,
  `gradoDiscapacidad` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fechaEvaluacion` date NOT NULL,
  `areaEvaluada` varchar(100) NOT NULL,
  `necesidadesEspeciales` text DEFAULT NULL,
  `apoyosRequeridos` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `recomendaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_discapacidadAlumno`),
  KEY `fk_matriculaAlumno` (`matriculaAlumno`),
  CONSTRAINT `fk_matriculaAlumno` FOREIGN KEY (`matriculaAlumno`) REFERENCES `alumnos` (`matriculaAlumno`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

/*Data for the table `discapacidadesalumnos` */

LOCK TABLES `discapacidadesalumnos` WRITE;

UNLOCK TABLES;

/*Table structure for table `empleados` */

DROP TABLE IF EXISTS `empleados`;

CREATE TABLE `empleados` (
  `id_empleado` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `fecha_contratacion` date NOT NULL,
  `puesto` varchar(50) DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_empleado`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*Data for the table `empleados` */

LOCK TABLES `empleados` WRITE;

UNLOCK TABLES;

/*Table structure for table `estadonacimiento` */

DROP TABLE IF EXISTS `estadonacimiento`;

CREATE TABLE `estadonacimiento` (
  `id_estadoNacimiento` int(11) NOT NULL AUTO_INCREMENT,
  `estado_Nacimiento` varchar(100) NOT NULL,
  PRIMARY KEY (`id_estadoNacimiento`),
  UNIQUE KEY `estadoNacimiento` (`estado_Nacimiento`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;

/*Data for the table `estadonacimiento` */

LOCK TABLES `estadonacimiento` WRITE;

insert  into `estadonacimiento`(`id_estadoNacimiento`,`estado_Nacimiento`) values (8,'Chiapas'),(5,'Chihuahua'),(1,'Ciudad de México'),(11,'Guerrero'),(2,'Jalisco'),(3,'Nuevo León'),(12,'Puebla'),(10,'Queretaro'),(7,'Sonora'),(9,'Tabasco'),(6,'Tamaulipas'),(4,'Veracruz');

UNLOCK TABLES;

/*Table structure for table `estatus` */

DROP TABLE IF EXISTS `estatus`;

CREATE TABLE `estatus` (
  `id_estatus` int(11) NOT NULL AUTO_INCREMENT,
  `tipoEstatus` varchar(50) NOT NULL,
  PRIMARY KEY (`id_estatus`),
  UNIQUE KEY `tipoEstatus` (`tipoEstatus`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;

/*Data for the table `estatus` */

LOCK TABLES `estatus` WRITE;

insert  into `estatus`(`id_estatus`,`tipoEstatus`) values (1,'Activo'),(5,'Aprobado'),(4,'Baja temporal'),(3,'Egresado'),(2,'Inactivo'),(6,'Reprobado'),(7,'Suspendido');

UNLOCK TABLES;

/*Table structure for table `generos` */

DROP TABLE IF EXISTS `generos`;

CREATE TABLE `generos` (
  `id_genero` int(11) NOT NULL AUTO_INCREMENT,
  `genero` varchar(50) NOT NULL,
  PRIMARY KEY (`id_genero`),
  UNIQUE KEY `genero` (`genero`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

/*Data for the table `generos` */

LOCK TABLES `generos` WRITE;

insert  into `generos`(`id_genero`,`genero`) values (2,'Femenino'),(1,'Masculino'),(3,'No binario'),(4,'Otro');

UNLOCK TABLES;

/*Table structure for table `gradoestudios` */

DROP TABLE IF EXISTS `gradoestudios`;

CREATE TABLE `gradoestudios` (
  `id_gradoEstudio` int(11) NOT NULL AUTO_INCREMENT,
  `gradoEstudio` varchar(100) NOT NULL,
  PRIMARY KEY (`id_gradoEstudio`),
  UNIQUE KEY `gradoEstudio` (`gradoEstudio`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

/*Data for the table `gradoestudios` */

LOCK TABLES `gradoestudios` WRITE;

insert  into `gradoestudios`(`id_gradoEstudio`,`gradoEstudio`) values (3,'Bachillerato'),(6,'Doctorado'),(4,'Licenciatura'),(5,'Maestría'),(1,'Primaria'),(2,'Secundaria');

UNLOCK TABLES;

/*Table structure for table `grupos` */

DROP TABLE IF EXISTS `grupos`;

CREATE TABLE `grupos` (
  `id_grupo` int(11) NOT NULL AUTO_INCREMENT,
  `grupo` varchar(50) NOT NULL,
  PRIMARY KEY (`id_grupo`),
  UNIQUE KEY `grupo` (`grupo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

/*Data for the table `grupos` */

LOCK TABLES `grupos` WRITE;

insert  into `grupos`(`id_grupo`,`grupo`) values (1,'Grupo A'),(5,'Grupo A,B'),(6,'Grupo A,B,C'),(2,'Grupo B'),(3,'Grupo C'),(4,'Grupo D');

UNLOCK TABLES;

/*Table structure for table `historialacademicoalumnos` */

DROP TABLE IF EXISTS `historialacademicoalumnos`;

CREATE TABLE `historialacademicoalumnos` (
  `id_historialAcademicoAlumno` int(11) NOT NULL AUTO_INCREMENT,
  `matriculaAlumno` varchar(20) NOT NULL,
  `id_semestre` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `cicloEscolar` varchar(20) NOT NULL,
  `id_estatus` int(11) NOT NULL,
  `id_asistencia` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_historialAcademicoAlumno`),
  KEY `matriculaAlumno` (`matriculaAlumno`),
  KEY `id_semestre` (`id_semestre`),
  KEY `id_grupo` (`id_grupo`),
  KEY `id_estatus` (`id_estatus`),
  KEY `id_asistencia` (`id_asistencia`),
  CONSTRAINT `historialacademicoalumnos_ibfk_1` FOREIGN KEY (`matriculaAlumno`) REFERENCES `alumnos` (`matriculaAlumno`),
  CONSTRAINT `historialacademicoalumnos_ibfk_2` FOREIGN KEY (`id_semestre`) REFERENCES `semestres` (`id_semestre`),
  CONSTRAINT `historialacademicoalumnos_ibfk_3` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`),
  CONSTRAINT `historialacademicoalumnos_ibfk_4` FOREIGN KEY (`id_estatus`) REFERENCES `estatus` (`id_estatus`),
  CONSTRAINT `historialacademicoalumnos_ibfk_5` FOREIGN KEY (`id_asistencia`) REFERENCES `asistencias` (`id_asistencia`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;

/*Data for the table `historialacademicoalumnos` */

LOCK TABLES `historialacademicoalumnos` WRITE;

insert  into `historialacademicoalumnos`(`id_historialAcademicoAlumno`,`matriculaAlumno`,`id_semestre`,`id_grupo`,`cicloEscolar`,`id_estatus`,`id_asistencia`,`observaciones`) values (15,'A1',1,1,'34  54',1,NULL,'muy buen trabajo'),(16,'A2',2,2,'34  54 44',4,NULL,'muy buen trabajo'),(17,'A3',6,2,'34  54 44',4,NULL,'muy buen trabajo');

UNLOCK TABLES;

/*Table structure for table `horarios` */

DROP TABLE IF EXISTS `horarios`;

CREATE TABLE `horarios` (
  `id_horario` int(11) NOT NULL AUTO_INCREMENT,
  `id_materia` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `dia` varchar(20) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `numEmpleado` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_horario`),
  KEY `id_materia` (`id_materia`),
  KEY `id_grupo` (`id_grupo`),
  CONSTRAINT `horarios_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`),
  CONSTRAINT `horarios_ibfk_2` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

/*Data for the table `horarios` */

LOCK TABLES `horarios` WRITE;

insert  into `horarios`(`id_horario`,`id_materia`,`id_grupo`,`dia`,`hora_inicio`,`hora_fin`,`numEmpleado`) values (6,1,1,'Lunes','12:00:00','13:15:00','');

UNLOCK TABLES;

/*Table structure for table `maestros` */

DROP TABLE IF EXISTS `maestros`;

CREATE TABLE `maestros` (
  `id_maestro` int(11) NOT NULL AUTO_INCREMENT,
  `numEmpleado` varchar(20) NOT NULL,
  `apellidoPaterno` varchar(50) NOT NULL,
  `apellidoMaterno` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `fechaNacimiento` date NOT NULL,
  `id_genero` int(11) NOT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `curp` varchar(18) DEFAULT NULL,
  `id_nacionalidad` int(11) NOT NULL,
  `id_estadoNacimiento` int(11) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `numCelular` varchar(15) DEFAULT NULL,
  `telefonoEmergencia` varchar(15) DEFAULT NULL,
  `mailInstitucional` varchar(100) NOT NULL,
  `mailPersonal` varchar(100) DEFAULT NULL,
  `fechaAlta` timestamp NOT NULL DEFAULT current_timestamp(),
  `fechaModificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_maestro`),
  UNIQUE KEY `numEmpleado` (`numEmpleado`),
  UNIQUE KEY `mailInstitucional` (`mailInstitucional`),
  KEY `id_genero` (`id_genero`),
  KEY `id_nacionalidad` (`id_nacionalidad`),
  KEY `id_estadoNacimiento` (`id_estadoNacimiento`),
  CONSTRAINT `maestros_ibfk_1` FOREIGN KEY (`id_genero`) REFERENCES `generos` (`id_genero`),
  CONSTRAINT `maestros_ibfk_2` FOREIGN KEY (`id_nacionalidad`) REFERENCES `nacionalidades` (`id_nacionalidad`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;

/*Data for the table `maestros` */

LOCK TABLES `maestros` WRITE;

insert  into `maestros`(`id_maestro`,`numEmpleado`,`apellidoPaterno`,`apellidoMaterno`,`nombre`,`fechaNacimiento`,`id_genero`,`rfc`,`curp`,`id_nacionalidad`,`id_estadoNacimiento`,`direccion`,`numCelular`,`telefonoEmergencia`,`mailInstitucional`,`mailPersonal`,`fechaAlta`,`fechaModificacion`) values (11,'M1','Ap1','Am1','Maestro1','2000-01-01',2,'RFCM1','CURPM1',4,4,'DM1','CM1','EM1','noM1@no.com','noM1@no.com','2025-02-14 18:45:13','2025-02-14 18:45:13'),(12,'M2','Ap2','Am2','Maestro2','2000-01-01',1,'RFCM2','CURPM2',3,3,'DM2','CM2','EM2','noM2@no.com','noM2@no.com','2025-02-14 18:45:59','2025-02-14 18:45:59'),(13,'M3','Ap3','Am3','Maestro3','2000-01-01',3,'RFCM3','CURPM3',1,3,'DM3','CM3','EM3','noM3@no.com','noM3@no.com','2025-02-14 18:46:45','2025-02-14 18:46:45'),(14,'234','dvx','c','cv','2024-02-20',2,'agrvs','zfgf',3,7,'xxcv','3555','3534','454@sfg.com','sf45@sdg.com','2026-01-21 18:50:15','2026-01-21 18:50:15');

UNLOCK TABLES;

/*Table structure for table `materias` */

DROP TABLE IF EXISTS `materias`;

CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL AUTO_INCREMENT,
  `materia` varchar(100) NOT NULL,
  `id_semestre` int(11) NOT NULL,
  PRIMARY KEY (`id_materia`),
  KEY `id_semestre` (`id_semestre`),
  CONSTRAINT `materias_ibfk_1` FOREIGN KEY (`id_semestre`) REFERENCES `semestres` (`id_semestre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

/*Data for the table `materias` */

LOCK TABLES `materias` WRITE;

insert  into `materias`(`id_materia`,`materia`,`id_semestre`) values (1,'Matemáticas',1),(2,'Historia',1),(3,'Programación',2),(4,'Física',3);

UNLOCK TABLES;

/*Table structure for table `nacionalidades` */

DROP TABLE IF EXISTS `nacionalidades`;

CREATE TABLE `nacionalidades` (
  `id_nacionalidad` int(11) NOT NULL AUTO_INCREMENT,
  `nacionalidad` varchar(100) NOT NULL,
  PRIMARY KEY (`id_nacionalidad`),
  UNIQUE KEY `nacionalidad` (`nacionalidad`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

/*Data for the table `nacionalidades` */

LOCK TABLES `nacionalidades` WRITE;

insert  into `nacionalidades`(`id_nacionalidad`,`nacionalidad`) values (4,'Argentina'),(3,'Española'),(2,'Estadounidense'),(1,'Mexicana');

UNLOCK TABLES;

/*Table structure for table `puestos` */

DROP TABLE IF EXISTS `puestos`;

CREATE TABLE `puestos` (
  `id_puesto` int(11) NOT NULL AUTO_INCREMENT,
  `puesto` varchar(100) NOT NULL,
  PRIMARY KEY (`id_puesto`),
  UNIQUE KEY `puesto` (`puesto`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

/*Data for the table `puestos` */

LOCK TABLES `puestos` WRITE;

insert  into `puestos`(`id_puesto`,`puesto`) values (4,'Administrativo'),(5,'Asistente'),(2,'Coordinador'),(3,'Director'),(1,'Profesor');

UNLOCK TABLES;

/*Table structure for table `semestres` */

DROP TABLE IF EXISTS `semestres`;

CREATE TABLE `semestres` (
  `id_semestre` int(11) NOT NULL AUTO_INCREMENT,
  `semestre` varchar(50) NOT NULL,
  PRIMARY KEY (`id_semestre`),
  UNIQUE KEY `semestre` (`semestre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

/*Data for the table `semestres` */

LOCK TABLES `semestres` WRITE;

insert  into `semestres`(`id_semestre`,`semestre`) values (4,'Cuarto Semestre'),(1,'Primer Semestre'),(5,'Quinto Semestre'),(2,'Segundo Semestre'),(6,'Sexto Semestre'),(3,'Tercer Semestre');

UNLOCK TABLES;

/*Table structure for table `usuarios` */

DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

/*Data for the table `usuarios` */

LOCK TABLES `usuarios` WRITE;

insert  into `usuarios`(`id`,`username`,`password`,`created_at`) values (1,'admin','$2y$10$w3m7be4gqzX4r5pWg6Nliu.2Eey0/5pJfJ7STqalPbhlDy1z11XIu','2025-01-29 20:36:48'),(3,'eme','$2y$10$w3m7be4gqzX4r5pWg6Nliu.2Eey0/5pJfJ7STqalPbhlDy1z11XIu','2025-01-29 20:58:06');

UNLOCK TABLES;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
