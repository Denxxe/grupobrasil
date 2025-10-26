-- Migration: 002_create_likes_table.sql
-- Crea la tabla `likes` usada por la funcionalidad de Me Gusta (likes)

CREATE TABLE IF NOT EXISTS `likes` (
  `id_like` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_noticia` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `fecha_like` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id_like`),
  KEY `idx_likes_noticia` (`id_noticia`),
  KEY `idx_likes_usuario` (`id_usuario`),
  CONSTRAINT `fk_likes_noticia` FOREIGN KEY (`id_noticia`) REFERENCES `noticias` (`id_noticia`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_likes_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
