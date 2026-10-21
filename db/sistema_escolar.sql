CREATE DATABASE IF NOT EXISTS sistema_escolar CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;

USE sistema_escolar;

-- --------------------------------------------------------
-- Tabla USUARIOS (autenticación y rol de cada persona)
-- --------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario      INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario  VARCHAR(50)  NOT NULL UNIQUE,
    contrasena      VARCHAR(255) NOT NULL,          -- se guarda con password_hash()
    nombre_completo VARCHAR(120) NOT NULL,
    correo          VARCHAR(120),
    rol             ENUM('admin', 'profesor', 'alumno') NOT NULL,
    fecha_registro  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- --------------------------------------------------------
-- Tabla ALUMNOS (1 a 1 con usuarios)
-- --------------------------------------------------------
CREATE TABLE alumnos (
    id_alumno   INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario  INT NOT NULL UNIQUE,
    matricula   VARCHAR(20) NOT NULL UNIQUE,
    carrera     VARCHAR(100) NOT NULL,
    CONSTRAINT fk_alumno_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE = InnoDB;

-- --------------------------------------------------------
-- Tabla PROFESORES (1 a 1 con usuarios)
-- --------------------------------------------------------
CREATE TABLE profesores (
    id_profesor     INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario      INT NOT NULL UNIQUE,
    numero_empleado VARCHAR(20) NOT NULL UNIQUE,
    especialidad    VARCHAR(100),
    CONSTRAINT fk_profesor_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE = InnoDB;

-- --------------------------------------------------------
-- Tabla MATERIAS
-- Ahora solo describe la materia en sí (clave, nombre y
-- créditos); ya NO guarda semestre ni profesor: eso se
-- define por cada "grupo" abierto de esa materia.
-- --------------------------------------------------------
CREATE TABLE materias (
    id_materia  INT AUTO_INCREMENT PRIMARY KEY,
    clave       VARCHAR(20) NOT NULL UNIQUE,
    nombre      VARCHAR(120) NOT NULL,
    creditos    INT NOT NULL DEFAULT 0
) ENGINE = InnoDB;

-- --------------------------------------------------------
-- Tabla SEMESTRES_PERIODOS
-- Cada periodo escolar (ej. "Enero-Junio 2026").
-- --------------------------------------------------------
CREATE TABLE semestres_periodos (
    id_semestre     INT AUTO_INCREMENT PRIMARY KEY,
    nombre_semestre VARCHAR(50) NOT NULL,
    fecha_inicio    DATE NOT NULL,
    fecha_fin       DATE NOT NULL
) ENGINE = InnoDB;

-- --------------------------------------------------------
-- Tabla GRUPOS
-- Es el "salón" de una materia: una materia impartida por
-- un profesor durante un periodo/semestre específico.
-- Los alumnos se inscriben a un GRUPO, no a la materia en
-- general (así puede haber varios grupos de la misma materia
-- con distinto profesor o periodo).
-- --------------------------------------------------------
CREATE TABLE grupos (
    id_grupo     INT AUTO_INCREMENT PRIMARY KEY,
    id_materia   INT NOT NULL,
    id_semestre  INT NOT NULL,
    id_profesor  INT,
    nombre_grupo VARCHAR(10) NOT NULL,   -- ej. "001", "A", etc.
    capacidad    INT NOT NULL DEFAULT 30,
    CONSTRAINT fk_grupo_materia FOREIGN KEY (id_materia)
        REFERENCES materias(id_materia) ON DELETE CASCADE,
    CONSTRAINT fk_grupo_semestre FOREIGN KEY (id_semestre)
        REFERENCES semestres_periodos(id_semestre) ON DELETE CASCADE,
    CONSTRAINT fk_grupo_profesor FOREIGN KEY (id_profesor)
        REFERENCES profesores(id_profesor) ON DELETE SET NULL,
    -- no se repite el mismo nombre de grupo para la misma materia+semestre
    UNIQUE KEY unico_grupo (id_materia, id_semestre, nombre_grupo)
) ENGINE = InnoDB;

-- --------------------------------------------------------
-- Tabla INSCRIPCIONES
-- Relación alumno <-> grupo. Un alumno no puede inscribirse
-- dos veces al mismo grupo.
-- --------------------------------------------------------
CREATE TABLE inscripciones (
    id_inscripcion    INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno         INT NOT NULL,
    id_grupo          INT NOT NULL,
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inscripcion_alumno FOREIGN KEY (id_alumno)
        REFERENCES alumnos(id_alumno) ON DELETE CASCADE,
    CONSTRAINT fk_inscripcion_grupo FOREIGN KEY (id_grupo)
        REFERENCES grupos(id_grupo) ON DELETE CASCADE,
    UNIQUE KEY unico_alumno_grupo (id_alumno, id_grupo)
) ENGINE = InnoDB;

-- --------------------------------------------------------
-- Tabla CALIFICACIONES
-- Ahora se relaciona con la INSCRIPCIÓN (no con alumno+materia
-- directo), y distingue entre evaluación ordinaria y extra.
-- --------------------------------------------------------
CREATE TABLE calificaciones (
    id_calificacion   INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion    INT NOT NULL,
    calificacion_final DECIMAL(5,2) NOT NULL,
    tipo_evaluacion   ENUM('ORDINARIO', 'EXTRA') NOT NULL DEFAULT 'ORDINARIO',
    fecha_calificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_calif_inscripcion FOREIGN KEY (id_inscripcion)
        REFERENCES inscripciones(id_inscripcion) ON DELETE CASCADE,
    -- una inscripción no puede tener 2 calificaciones del mismo tipo
    UNIQUE KEY unico_inscripcion_tipo (id_inscripcion, tipo_evaluacion)
) ENGINE = InnoDB;

-- --------------------------------------------------------
-- Usuario ADMINISTRADOR de prueba
-- Usuario: admin   Contraseña: admin123
-- --------------------------------------------------------
INSERT INTO usuarios (nombre_usuario, contrasena, nombre_completo, correo, rol)
VALUES (
    'admin',
    '$2b$10$zTVmpJYCmUi5CtlcIZDvBeLr791wjsUnR6tzGeIJj5gFn9ua60fQ2',
    'Administrador del Sistema',
    'admin@escuela.edu.mx',
    'admin'
);

-- --------------------------------------------------------
-- Un periodo de ejemplo, para poder crear grupos de inmediato
-- --------------------------------------------------------
INSERT INTO semestres_periodos (nombre_semestre, fecha_inicio, fecha_fin)
VALUES ('Enero-Junio 2026', '2026-01-19', '2026-06-05');
