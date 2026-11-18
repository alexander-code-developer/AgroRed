SET NAMES utf8mb4;
-- =====================================================================
-- AgroRed Hidalgo - Esquema MySQL / MariaDB
-- Equipo Nexosoft | ITSOEH | 5° Semestre ISC
-- =====================================================================
CREATE DATABASE IF NOT EXISTS agrored CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agrored;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS password_resets, finanzas_movimientos, mensajes, resenas, pedidos,
  inventario, cultivos, huertos, colonias, municipios, usuarios, roles, estados_pedido, tipos_movimiento;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE usuarios (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  rol_id TINYINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  telefono CHAR(10) NOT NULL,
  pass_hash VARCHAR(255) NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usr_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE municipios (
  id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE colonias (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  municipio_id SMALLINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  CONSTRAINT fk_col_mun FOREIGN KEY (municipio_id) REFERENCES municipios(id)
) ENGINE=InnoDB;

CREATE TABLE huertos (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  dueno_id INT UNSIGNED NULL,
  colonia_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  facebook VARCHAR(255) NULL,
  latitud DECIMAL(9,6) NOT NULL,
  longitud DECIMAL(9,6) NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hue_dueno FOREIGN KEY (dueno_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_hue_col FOREIGN KEY (colonia_id) REFERENCES colonias(id),
  INDEX idx_hue_coord (latitud, longitud)
) ENGINE=InnoDB;

CREATE TABLE cultivos (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  kcal DECIMAL(8,2) NOT NULL,
  proteina_g DECIMAL(8,2) NOT NULL,
  vitamina_c_mg DECIMAL(8,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE inventario (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  huerto_id INT UNSIGNED NOT NULL,
  cultivo_id INT UNSIGNED NOT NULL,
  volumen_kg DECIMAL(12,2) NOT NULL,
  cosecha_estimada DATE NULL,
  actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_inv_hue FOREIGN KEY (huerto_id) REFERENCES huertos(id) ON DELETE CASCADE,
  CONSTRAINT fk_inv_cul FOREIGN KEY (cultivo_id) REFERENCES cultivos(id)
) ENGINE=InnoDB;

CREATE TABLE estados_pedido (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE pedidos (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  huerto_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  estado_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  telefono CHAR(10) NOT NULL,
  correo VARCHAR(150) NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ped_hue FOREIGN KEY (huerto_id) REFERENCES huertos(id) ON DELETE CASCADE,
  CONSTRAINT fk_ped_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_ped_est FOREIGN KEY (estado_id) REFERENCES estados_pedido(id)
) ENGINE=InnoDB;

CREATE TABLE resenas (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  huerto_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  estrellas TINYINT NOT NULL,
  comentario VARCHAR(200) NOT NULL,
  recomienda TINYINT(1) NOT NULL DEFAULT 0,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_resena (huerto_id, usuario_id),
  CONSTRAINT fk_res_hue FOREIGN KEY (huerto_id) REFERENCES huertos(id) ON DELETE CASCADE,
  CONSTRAINT fk_res_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE mensajes (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  de_usuario_id INT UNSIGNED NOT NULL,
  para_huerto_id INT UNSIGNED NOT NULL,
  texto VARCHAR(300) NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_msg_usr FOREIGN KEY (de_usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_hue FOREIGN KEY (para_huerto_id) REFERENCES huertos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tipos_movimiento (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE finanzas_movimientos (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  tipo_id TINYINT UNSIGNED NOT NULL,
  concepto VARCHAR(200) NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  fecha DATE NOT NULL,
  CONSTRAINT fk_mov_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_mov_tipo FOREIGN KEY (tipo_id) REFERENCES tipos_movimiento(id)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expira DATETIME NOT NULL,
  usado TINYINT(1) NOT NULL DEFAULT 0,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pr_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Vista: balance mensual por usuario (finanzas del productor)
CREATE OR REPLACE VIEW v_finanzas_mensual AS
SELECT m.usuario_id,
       DATE_FORMAT(m.fecha,'%Y-%m') AS mes,
       SUM(CASE WHEN t.nombre='ingreso' THEN m.monto ELSE -m.monto END) AS balance
FROM finanzas_movimientos m JOIN tipos_movimiento t ON t.id = m.tipo_id
GROUP BY m.usuario_id, DATE_FORMAT(m.fecha,'%Y-%m');
