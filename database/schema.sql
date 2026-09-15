-- =====================================================================
--  IPS BIOMED · Historia Clínica Electrónica - Medicina General
--  Esquema de base de datos (MySQL 5.7+ / MariaDB 10.3+)
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_documento VARCHAR(5) NOT NULL DEFAULT 'CC',
  documento VARCHAR(20) NOT NULL,
  nombres VARCHAR(100) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  telefono VARCHAR(30) NULL,
  usuario VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('administrador','medico','recepcion','auxiliar') NOT NULL,
  especialidad VARCHAR(100) NULL,
  registro_medico VARCHAR(50) NULL,
  firma VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos INT NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  ultimo_acceso DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_usuarios_usuario (usuario),
  UNIQUE KEY uk_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expira_en DATETIME NOT NULL,
  usado TINYINT(1) NOT NULL DEFAULT 0,
  ip VARCHAR(45) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_resets_token (token_hash),
  CONSTRAINT fk_resets_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracion (
  clave VARCHAR(60) NOT NULL PRIMARY KEY,
  valor TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eps (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(10) NULL,
  nombre VARCHAR(150) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uk_eps_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pacientes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_documento VARCHAR(5) NOT NULL,
  numero_documento VARCHAR(20) NOT NULL,
  primer_nombre VARCHAR(60) NOT NULL,
  segundo_nombre VARCHAR(60) NULL,
  primer_apellido VARCHAR(60) NOT NULL,
  segundo_apellido VARCHAR(60) NULL,
  fecha_nacimiento DATE NOT NULL,
  sexo ENUM('F','M','I') NOT NULL,
  estado_civil VARCHAR(30) NULL,
  ocupacion VARCHAR(100) NULL,
  escolaridad VARCHAR(40) NULL,
  grupo_sanguineo VARCHAR(4) NULL,
  etnia VARCHAR(60) NULL,
  nacionalidad VARCHAR(60) NULL DEFAULT 'Colombia',
  direccion VARCHAR(150) NULL,
  barrio VARCHAR(100) NULL,
  municipio VARCHAR(100) NULL,
  departamento VARCHAR(100) NULL,
  zona CHAR(1) NULL,
  telefono VARCHAR(30) NOT NULL,
  telefono2 VARCHAR(30) NULL,
  email VARCHAR(150) NULL,
  eps_id INT UNSIGNED NULL,
  regimen VARCHAR(30) NOT NULL,
  tipo_afiliado VARCHAR(30) NULL,
  acompanante_nombre VARCHAR(120) NULL,
  acompanante_telefono VARCHAR(30) NULL,
  acompanante_parentesco VARCHAR(40) NULL,
  responsable_nombre VARCHAR(120) NULL,
  responsable_telefono VARCHAR(30) NULL,
  responsable_parentesco VARCHAR(40) NULL,
  observaciones TEXT NULL,
  creado_por INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_pacientes_documento (tipo_documento, numero_documento),
  KEY idx_pacientes_numero (numero_documento),
  KEY idx_pacientes_nombre (primer_apellido, primer_nombre),
  CONSTRAINT fk_pacientes_eps FOREIGN KEY (eps_id) REFERENCES eps(id) ON DELETE SET NULL,
  CONSTRAINT fk_pacientes_creador FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS antecedentes (
  paciente_id INT UNSIGNED NOT NULL PRIMARY KEY,
  patologicos TEXT NULL,
  quirurgicos TEXT NULL,
  farmacologicos TEXT NULL,
  alergicos TEXT NULL,
  traumaticos TEXT NULL,
  toxicos TEXT NULL,
  gineco_obstetricos TEXT NULL,
  familiares TEXT NULL,
  inmunologicos TEXT NULL,
  otros TEXT NULL,
  actualizado_por INT UNSIGNED NULL,
  actualizado_en DATETIME NULL,
  CONSTRAINT fk_antecedentes_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admisiones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT UNSIGNED NOT NULL,
  medico_id INT UNSIGNED NULL,
  fecha_hora DATETIME NOT NULL,
  tipo_consulta VARCHAR(40) NOT NULL,
  modalidad VARCHAR(40) NOT NULL DEFAULT 'Intramural',
  motivo TEXT NULL,
  finalidad VARCHAR(80) NOT NULL,
  causa_externa VARCHAR(80) NOT NULL,
  eps_id INT UNSIGNED NULL,
  regimen VARCHAR(30) NULL,
  numero_autorizacion VARCHAR(40) NULL,
  valor_copago DECIMAL(12,2) NOT NULL DEFAULT 0,
  prioridad VARCHAR(20) NOT NULL DEFAULT 'Normal',
  estado ENUM('en_espera','en_atencion','atendida','cancelada') NOT NULL DEFAULT 'en_espera',
  motivo_cancelacion VARCHAR(255) NULL,
  creado_por INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atendida_en DATETIME NULL,
  KEY idx_admisiones_fecha (fecha_hora),
  KEY idx_admisiones_estado (estado),
  CONSTRAINT fk_admisiones_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
  CONSTRAINT fk_admisiones_medico FOREIGN KEY (medico_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_admisiones_eps FOREIGN KEY (eps_id) REFERENCES eps(id) ON DELETE SET NULL,
  CONSTRAINT fk_admisiones_creador FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consultas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admision_id INT UNSIGNED NOT NULL,
  paciente_id INT UNSIGNED NOT NULL,
  medico_id INT UNSIGNED NOT NULL,
  motivo_consulta TEXT NULL,
  enfermedad_actual TEXT NULL,
  revision_sistemas TEXT NULL,
  antecedentes_snapshot LONGTEXT NULL,
  ta_sistolica SMALLINT NULL,
  ta_diastolica SMALLINT NULL,
  frecuencia_cardiaca SMALLINT NULL,
  frecuencia_respiratoria SMALLINT NULL,
  temperatura DECIMAL(4,1) NULL,
  saturacion TINYINT UNSIGNED NULL,
  peso DECIMAL(5,2) NULL,
  talla DECIMAL(5,1) NULL,
  imc DECIMAL(5,2) NULL,
  perimetro_abdominal DECIMAL(5,1) NULL,
  glucometria SMALLINT NULL,
  estado_general TEXT NULL,
  cabeza_cuello TEXT NULL,
  torax_cardiopulmonar TEXT NULL,
  abdomen TEXT NULL,
  genitourinario TEXT NULL,
  extremidades TEXT NULL,
  piel_faneras TEXT NULL,
  neurologico TEXT NULL,
  examen_otros TEXT NULL,
  paraclinicos TEXT NULL,
  analisis TEXT NULL,
  plan_manejo TEXT NULL,
  recomendaciones TEXT NULL,
  incapacidad_dias SMALLINT NULL,
  incapacidad_desde DATE NULL,
  proximo_control VARCHAR(120) NULL,
  estado ENUM('borrador','cerrada') NOT NULL DEFAULT 'borrador',
  cerrada_en DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_consultas_admision (admision_id),
  KEY idx_consultas_cerrada (estado, cerrada_en),
  KEY idx_consultas_paciente (paciente_id),
  CONSTRAINT fk_consultas_admision FOREIGN KEY (admision_id) REFERENCES admisiones(id),
  CONSTRAINT fk_consultas_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
  CONSTRAINT fk_consultas_medico FOREIGN KEY (medico_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consulta_diagnosticos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consulta_id INT UNSIGNED NOT NULL,
  codigo VARCHAR(10) NOT NULL,
  descripcion VARCHAR(255) NOT NULL,
  tipo ENUM('Principal','Relacionado') NOT NULL DEFAULT 'Relacionado',
  clase VARCHAR(40) NOT NULL DEFAULT 'Impresión diagnóstica',
  orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_dx_codigo (codigo),
  CONSTRAINT fk_dx_consulta FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS formulas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consulta_id INT UNSIGNED NOT NULL,
  observaciones TEXT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_formulas_consulta (consulta_id),
  CONSTRAINT fk_formulas_consulta FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS formula_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  formula_id INT UNSIGNED NOT NULL,
  medicamento VARCHAR(200) NOT NULL,
  concentracion VARCHAR(60) NULL,
  forma_farmaceutica VARCHAR(60) NULL,
  via VARCHAR(40) NULL,
  dosis VARCHAR(80) NULL,
  frecuencia VARCHAR(80) NULL,
  duracion VARCHAR(60) NULL,
  cantidad INT UNSIGNED NOT NULL DEFAULT 1,
  cantidad_letras VARCHAR(120) NULL,
  indicaciones TEXT NULL,
  orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_items_formula FOREIGN KEY (formula_id) REFERENCES formulas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ordenes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consulta_id INT UNSIGNED NOT NULL,
  tipo VARCHAR(40) NOT NULL,
  codigo VARCHAR(12) NULL,
  descripcion VARCHAR(255) NOT NULL,
  cantidad SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  observacion TEXT NULL,
  orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_ordenes_consulta FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notas_aclaratorias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consulta_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  nota TEXT NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notas_consulta FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE CASCADE,
  CONSTRAINT fk_notas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cie10 (
  codigo VARCHAR(10) NOT NULL PRIMARY KEY,
  descripcion VARCHAR(255) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_cie10_desc (descripcion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS medicamentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(200) NOT NULL,
  concentracion VARCHAR(60) NULL,
  forma_farmaceutica VARCHAR(60) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_medicamentos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auditoria (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  accion VARCHAR(60) NOT NULL,
  entidad VARCHAR(40) NULL,
  entidad_id INT UNSIGNED NULL,
  detalle TEXT NULL,
  ip VARCHAR(45) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_auditoria_fecha (creado_en),
  KEY idx_auditoria_usuario (usuario_id),
  KEY idx_auditoria_entidad (entidad, entidad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
