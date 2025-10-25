-- Migration: 001_create_vivienda_detalle.sql
-- Crea la tabla `vivienda_detalle` para almacenar metadatos adicionales de una vivienda.
-- Requisitos: la tabla `vivienda` debe existir y tener la PK `id_vivienda`.

-- Nota: Ejecuta esto en la base de datos correspondiente (usa el mismo usuario/charset que tu proyecto).

CREATE TABLE IF NOT EXISTS `vivienda_detalle` (
  `id_vivienda_detalle` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_vivienda` INT UNSIGNED NOT NULL,
  `habitaciones` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `banos` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `servicios` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_vivienda_detalle`),
  UNIQUE KEY `uq_vivienda_detalle_id_vivienda` (`id_vivienda`),
  CONSTRAINT `fk_vivienda_detalle_vivienda` FOREIGN KEY (`id_vivienda`) REFERENCES `vivienda`(`id_vivienda`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Revert: eliminar la tabla
-- DROP TABLE IF EXISTS `vivienda_detalle`;

-- Ejemplo de uso (opcional):
-- INSERT INTO `vivienda_detalle` (id_vivienda, habitaciones, banos, servicios) VALUES (1, 3, 1, 'agua,luz,gas');
