-- Migration: 003_create_noticia_visibilidad.sql
-- Crea la tabla noticia_visibilidad para controlar qué noticias son visibles
-- por vereda (calle) y/o por habitante específico.

CREATE TABLE IF NOT EXISTS `noticia_visibilidad` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_noticia` INT(10) UNSIGNED NOT NULL,
  `id_calle` INT(10) UNSIGNED NULL,
  `id_habitante` INT(10) UNSIGNED NULL,
  `visible` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_noticia` (`id_noticia`),
  INDEX `idx_calle` (`id_calle`),
  INDEX `idx_habitante` (`id_habitante`),
  CONSTRAINT `fk_nv_noticia` FOREIGN KEY (`id_noticia`) REFERENCES `noticias` (`id_noticia`) ON DELETE CASCADE,
  CONSTRAINT `fk_nv_calle` FOREIGN KEY (`id_calle`) REFERENCES `calle` (`id_calle`) ON DELETE CASCADE,
  CONSTRAINT `fk_nv_habitante` FOREIGN KEY (`id_habitante`) REFERENCES `habitante` (`id_habitante`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nota: este esquema permite asignar visibilidad por vereda (id_calle)
-- o por habitante (id_habitante). Si deseas evitar duplicados estrictos
-- por combinaciones, se puede añadir un UNIQUE INDEX sobre (id_noticia, id_calle, id_habitante)
-- pero en este diseño se permiten filas separadas para mayor flexibilidad.
