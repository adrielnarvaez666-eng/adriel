CREATE DATABASE IF NOT EXISTS number_lookup
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE number_lookup;

CREATE TABLE IF NOT EXISTS registros (
  numero CHAR(10) NOT NULL PRIMARY KEY,
  primer_nombre VARCHAR(100) NOT NULL,
  segundo_nombre VARCHAR(100) NOT NULL,
  primer_apellido VARCHAR(100) NOT NULL,
  segundo_apellido VARCHAR(100) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
