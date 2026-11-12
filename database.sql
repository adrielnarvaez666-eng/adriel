CREATE DATABASE IF NOT EXISTS `registro civil`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `registro civil`;

CREATE TABLE IF NOT EXISTS registros (
  numero CHAR(10) NOT NULL PRIMARY KEY,
  primer_nombre VARCHAR(100) NOT NULL,
  segundo_nombre VARCHAR(100) NOT NULL,
  primer_apellido VARCHAR(100) NOT NULL,
  segundo_apellido VARCHAR(100) NOT NULL,
  madre_primer_nombre VARCHAR(100) NOT NULL,
  madre_segundo_nombre VARCHAR(100) NOT NULL,
  madre_primer_apellido VARCHAR(100) NOT NULL,
  madre_segundo_apellido VARCHAR(100) NOT NULL,
  padre_primer_nombre VARCHAR(100) NOT NULL,
  padre_segundo_nombre VARCHAR(100) NOT NULL,
  padre_primer_apellido VARCHAR(100) NOT NULL,
  padre_segundo_apellido VARCHAR(100) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
