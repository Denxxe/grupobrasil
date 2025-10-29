-- Migration: 004_create_events.sql
-- Crea las tablas necesarias para la sección de eventos y el registro de asistencia

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `eventos` (
  `id_evento` INT NOT NULL AUTO_INCREMENT,
  `titulo` VARCHAR(255) NOT NULL,
  `descripcion` TEXT NULL,
  `ubicacion` VARCHAR(255) NULL,
  `fecha` DATE NOT NULL,
  `hora_inicio` TIME NULL,
  `hora_fin` TIME NULL,
  `categoria_edad` ENUM('ninos','jovenes','adultos','adultos_mayores','todos') NOT NULL DEFAULT 'todos',
  `alcance` ENUM('comunidad','vereda') NOT NULL DEFAULT 'comunidad',
  `id_calle` int(10) UNSIGNED NULL,
  `creado_por` int(10) UNSIGNED NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_por` int(10) UNSIGNED NULL,
  `fecha_actualizacion` TIMESTAMP NULL DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_evento`),
  KEY `idx_eventos_fecha` (`fecha`),
  KEY `idx_eventos_id_calle` (`id_calle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para marcar asistencia/confirmación de usuarios a un evento
CREATE TABLE IF NOT EXISTS `evento_asistentes` (
  `id_asistencia` INT NOT NULL AUTO_INCREMENT,
  `id_evento` INT NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `estado` ENUM('si','no','talvez') NOT NULL DEFAULT 'si',
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_asistencia`),
  UNIQUE KEY `ux_evento_usuario` (`id_evento`,`id_usuario`),
  KEY `idx_asistentes_evento` (`id_evento`),
  KEY `idx_asistentes_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir FK después de crear tablas (si existen las tablas referenciadas)
-- Relacionar creado_por/actualizado_por con la tabla usuario y id_calle con calle
ALTER TABLE `eventos`
  ADD CONSTRAINT `fk_eventos_calle` FOREIGN KEY (`id_calle`) REFERENCES `calle` (`id_calle`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `eventos`
  ADD CONSTRAINT `fk_eventos_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eventos_actualizado_por` FOREIGN KEY (`actualizado_por`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `evento_asistentes`
  ADD CONSTRAINT `fk_asistentes_evento` FOREIGN KEY (`id_evento`) REFERENCES `eventos` (`id_evento`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asistentes_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

-- Índices adicionales para consultas de métricas
CREATE INDEX IF NOT EXISTS `idx_eventos_categoria_fecha` ON `eventos` (`categoria_edad`, `fecha`);
CREATE INDEX IF NOT EXISTS `idx_eventos_alcance_fecha` ON `eventos` (`alcance`, `fecha`);

-- Fin de migration
