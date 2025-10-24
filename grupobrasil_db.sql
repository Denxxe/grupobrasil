-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-10-2025 a las 20:32:18
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `grupobrasil_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calle`
--

CREATE TABLE `calle` (
  `id_calle` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `calle`
--

INSERT INTO `calle` (`id_calle`, `nombre`, `sector`, `descripcion`, `activo`, `fecha_registro`, `fecha_actualizacion`) VALUES
(1, 'Vereda 6', NULL, NULL, 1, '2025-10-21 03:17:21', '2025-10-21 04:01:51'),
(2, 'Vereda 7', NULL, NULL, 1, '2025-10-21 03:17:21', '2025-10-21 04:02:02'),
(3, 'Vereda 8', NULL, NULL, 1, '2025-10-21 03:17:21', '2025-10-21 04:02:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carga_familiar`
--

CREATE TABLE `carga_familiar` (
  `id_carga` int(10) UNSIGNED NOT NULL,
  `id_habitante` int(10) UNSIGNED DEFAULT NULL,
  `id_jefe` int(10) UNSIGNED DEFAULT NULL,
  `parentesco` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre`, `descripcion`, `fecha_registro`) VALUES
(1, 'Noticias Locales', 'Información relevante sobre eventos y sucesos de la comunidad.', '2025-10-21 05:36:21'),
(2, 'Tecnología', 'Artículos sobre avances tecnológicos, gadgets y software.', '2025-10-21 05:36:21'),
(3, 'Cultura', 'Contenido relacionado con arte, literatura, historia y tradiciones.', '2025-10-21 05:36:21'),
(4, 'Deportes', 'Novedades y resultados de eventos deportivos locales e internacionales.', '2025-10-21 05:36:21'),
(5, 'Música', 'Noticias de artistas, lanzamientos y reseñas musicales.', '2025-10-21 05:36:21'),
(6, 'Economía', 'Análisis y reportes sobre el mercado, finanzas y negocios.', '2025-10-21 05:36:21'),
(7, 'Salud', 'Información sobre bienestar, medicina y estilos de vida saludables.', '2025-10-21 05:36:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `id_comentario` int(10) UNSIGNED NOT NULL,
  `id_noticia` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona con la noticia comentada',
  `id_usuario` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona con el usuario que comenta',
  `contenido` text NOT NULL COMMENT 'Contenido del comentario',
  `fecha_comentario` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora de publicación',
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=inactivo, 1=activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `concepto_pago`
--

CREATE TABLE `concepto_pago` (
  `id_concepto` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evento`
--

CREATE TABLE `evento` (
  `id_evento` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_evento` date DEFAULT NULL,
  `lugar` varchar(100) DEFAULT NULL,
  `creado_por` int(10) UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitante`
--

CREATE TABLE `habitante` (
  `id_habitante` int(10) UNSIGNED NOT NULL,
  `id_persona` int(10) UNSIGNED DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `condicion` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `habitante`
--

INSERT INTO `habitante` (`id_habitante`, `id_persona`, `fecha_ingreso`, `condicion`, `activo`, `fecha_registro`, `fecha_actualizacion`) VALUES
(1, 7, '2025-10-22', 'Residente', 1, '2025-10-21 20:34:19', '2025-10-21 20:34:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitante_vivienda`
--

CREATE TABLE `habitante_vivienda` (
  `id_habitante` int(10) UNSIGNED NOT NULL,
  `id_vivienda` int(10) UNSIGNED NOT NULL,
  `es_jefe_familia` tinyint(1) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_salida` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `indicador_gestion`
--

CREATE TABLE `indicador_gestion` (
  `id_indicador` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL,
  `generado_por` int(10) UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lider_calle`
--

CREATE TABLE `lider_calle` (
  `id_habitante` int(10) UNSIGNED NOT NULL,
  `id_calle` int(10) UNSIGNED NOT NULL,
  `fecha_designacion` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `lider_calle`
--

INSERT INTO `lider_calle` (`id_habitante`, `id_calle`, `fecha_designacion`, `activo`, `fecha_registro`, `fecha_actualizacion`) VALUES
(1, 1, '2025-10-22', 1, '2025-10-22 14:26:56', '2025-10-22 14:26:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lider_comunal`
--

CREATE TABLE `lider_comunal` (
  `id_habitante` int(10) UNSIGNED NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_actividad`
--

CREATE TABLE `log_actividad` (
  `id_log` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL COMMENT 'Usuario que realizó la acción (NULL para acciones del sistema)',
  `tipo` varchar(50) NOT NULL COMMENT 'Categoría de la acción (ej: pago_ok, usuario_creado, error)',
  `mensaje` text NOT NULL COMMENT 'Descripción detallada de la actividad',
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticias`
--

CREATE TABLE `noticias` (
  `id_noticia` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_categoria` int(10) UNSIGNED DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` text NOT NULL,
  `imagen_principal` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `estado` enum('publicado','borrador') NOT NULL DEFAULT 'borrador',
  `fecha_publicacion` datetime DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(10) UNSIGNED NOT NULL,
  `id_usuario_destino` int(10) UNSIGNED NOT NULL COMMENT 'ID del usuario que debe ver la notificación',
  `id_usuario_origen` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID del usuario que disparó la notificación (ej. quien comenta)',
  `tipo` varchar(50) NOT NULL,
  `mensaje` text NOT NULL,
  `id_referencia` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID del recurso relacionado (noticia, comentario, etc.)',
  `leido` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=No leído, 1=Leído',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `id_pago` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `id_concepto` int(10) UNSIGNED DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `estado_pago` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL COMMENT 'Usuario que recibe el pago/beneficio',
  `id_tipo_beneficio` int(10) UNSIGNED NOT NULL,
  `id_periodo` int(10) UNSIGNED NOT NULL,
  `monto` decimal(10,2) NOT NULL COMMENT 'Monto pagado o valor del beneficio',
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha real en que se registró el pago/entrega',
  `concepto` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','procesado','fallido','vencido') NOT NULL DEFAULT 'procesado',
  `registrado_por_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID del admin o líder que registró el pago'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_periodos`
--

CREATE TABLE `pagos_periodos` (
  `id_periodo` int(10) UNSIGNED NOT NULL,
  `nombre_periodo` varchar(100) NOT NULL COMMENT 'Ej: Enero 2025, Campaña Q1',
  `fecha_inicio` date NOT NULL COMMENT 'Fecha desde la que se puede pagar',
  `fecha_limite` date NOT NULL COMMENT 'Fecha límite para realizar el pago',
  `estado` enum('activo','cerrado','archivado') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participacion_evento`
--

CREATE TABLE `participacion_evento` (
  `id_participacion` int(10) UNSIGNED NOT NULL,
  `id_evento` int(10) UNSIGNED DEFAULT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `persona`
--

CREATE TABLE `persona` (
  `id_persona` int(10) UNSIGNED NOT NULL,
  `cedula` varchar(19) DEFAULT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `sexo` varchar(10) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `id_calle` int(10) UNSIGNED DEFAULT NULL,
  `numero_casa` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `estado` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `persona`
--

INSERT INTO `persona` (`id_persona`, `cedula`, `nombres`, `apellidos`, `fecha_nacimiento`, `sexo`, `telefono`, `direccion`, `id_calle`, `numero_casa`, `correo`, `estado`, `activo`, `fecha_registro`, `fecha_actualizacion`) VALUES
(3, '12345678', 'Lider', 'Comunidad', '1980-01-01', 'F', '0987654321', 'Oficina Central', 1, '2', 'admin@grupobrasil.com', NULL, 1, '2025-10-20 23:29:39', '2025-10-21 07:52:21'),
(6, '31044092', 'Cristian Jesus', 'Correa Pinto', '0000-00-00', '', '12345678', 'Urbanización Brasil', 3, '6', 'cristiancorreaxd@gmail.com', 'Residente', 1, '2025-10-21 04:48:38', '2025-10-21 07:52:54'),
(7, '87654321', 'Luis', 'Arredondo', NULL, NULL, '04147852753', '', 1, '', '', 'Residente', 1, '2025-10-21 20:33:17', '2025-10-21 20:33:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre`, `descripcion`, `activo`, `fecha_registro`, `fecha_actualizacion`) VALUES
(1, 'Administrador Principal', 'Máximo nivel de acceso y gestión del sistema.', 1, '2025-10-20 23:27:06', '2025-10-20 23:27:06'),
(2, 'Sub-Administrador', 'Acceso a la administración limitada.', 1, '2025-10-20 23:27:06', '2025-10-20 23:27:06'),
(3, 'Miembro de Comunidad', 'Usuario estándar con acceso al dashboard.', 1, '2025-10-20 23:27:06', '2025-10-20 23:27:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_beneficio`
--

CREATE TABLE `tipos_beneficio` (
  `id_tipo_beneficio` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Ej: Bolsa de Comida, Bombona de Gas, Campo Soberano',
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_beneficio`
--

INSERT INTO `tipos_beneficio` (`id_tipo_beneficio`, `nombre`, `descripcion`) VALUES
(1, 'Bolsa de Comida', NULL),
(2, 'Bombona de Gas', NULL),
(3, 'Campo Soberano', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_persona` int(10) UNSIGNED DEFAULT NULL,
  `id_rol` int(10) UNSIGNED DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `estado` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username` varchar(255) NOT NULL DEFAULT 'Usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `id_persona`, `id_rol`, `password`, `email`, `fecha_registro`, `estado`, `activo`, `fecha_actualizacion`, `username`) VALUES
(2, 3, 1, '$2y$10$6OPuIKDZ12i0vwqYqf0pwevZS9hkghvPzKrfcAZrxtlxUgvv4TQcu', NULL, '2025-10-20 23:29:39', 'activo', 1, '2025-10-21 05:55:07', 'Admin'),
(3, 6, 3, '$2y$10$kqO8xp6ijfL.yMNsd7n8vO2RyrxZndg8wUsi5n2y4JJUPQymWhHMW', 'cristiancorreaxd@gmail.com', '2025-10-21 07:19:28', NULL, 1, '2025-10-21 07:19:28', 'Usuario'),
(4, 7, 2, '$2y$10$q7LKe3E9j6cfLYiFzn/VG.FhuJFYJjHkCeunqDMq/M57cxLZmBt1S', 'lfarredondot14@gmail.com', '2025-10-21 20:34:19', NULL, 1, '2025-10-22 14:26:56', 'Usuario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vivienda`
--

CREATE TABLE `vivienda` (
  `id_vivienda` int(10) UNSIGNED NOT NULL,
  `id_calle` int(10) UNSIGNED DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `estado` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `calle`
--
ALTER TABLE `calle`
  ADD PRIMARY KEY (`id_calle`);

--
-- Indices de la tabla `carga_familiar`
--
ALTER TABLE `carga_familiar`
  ADD PRIMARY KEY (`id_carga`),
  ADD KEY `carga_familiar_id_habitante_fkey` (`id_habitante`),
  ADD KEY `carga_familiar_id_jefe_fkey` (`id_jefe`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre_unico` (`nombre`);

--
-- Indices de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `idx_noticia` (`id_noticia`),
  ADD KEY `idx_usuario` (`id_usuario`);

--
-- Indices de la tabla `concepto_pago`
--
ALTER TABLE `concepto_pago`
  ADD PRIMARY KEY (`id_concepto`);

--
-- Indices de la tabla `evento`
--
ALTER TABLE `evento`
  ADD PRIMARY KEY (`id_evento`),
  ADD KEY `evento_creado_por_fkey` (`creado_por`);

--
-- Indices de la tabla `habitante`
--
ALTER TABLE `habitante`
  ADD PRIMARY KEY (`id_habitante`),
  ADD UNIQUE KEY `id_persona` (`id_persona`);

--
-- Indices de la tabla `habitante_vivienda`
--
ALTER TABLE `habitante_vivienda`
  ADD PRIMARY KEY (`id_habitante`,`id_vivienda`),
  ADD KEY `hv_id_vivienda_fkey` (`id_vivienda`);

--
-- Indices de la tabla `indicador_gestion`
--
ALTER TABLE `indicador_gestion`
  ADD PRIMARY KEY (`id_indicador`),
  ADD KEY `indicador_gestion_generado_por_fkey` (`generado_por`);

--
-- Indices de la tabla `lider_calle`
--
ALTER TABLE `lider_calle`
  ADD PRIMARY KEY (`id_habitante`,`id_calle`),
  ADD KEY `lider_calle_id_calle_fkey` (`id_calle`);

--
-- Indices de la tabla `lider_comunal`
--
ALTER TABLE `lider_comunal`
  ADD PRIMARY KEY (`id_habitante`,`fecha_inicio`);

--
-- Indices de la tabla `log_actividad`
--
ALTER TABLE `log_actividad`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD PRIMARY KEY (`id_noticia`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_noticias_usuario` (`id_usuario`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `idx_destino_leido` (`id_usuario_destino`,`leido`),
  ADD KEY `fk_notificacion_origen` (`id_usuario_origen`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `pago_id_usuario_fkey` (`id_usuario`),
  ADD KEY `pago_id_concepto_fkey` (`id_concepto`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_tipo_beneficio` (`id_tipo_beneficio`),
  ADD KEY `id_periodo` (`id_periodo`);

--
-- Indices de la tabla `pagos_periodos`
--
ALTER TABLE `pagos_periodos`
  ADD PRIMARY KEY (`id_periodo`);

--
-- Indices de la tabla `participacion_evento`
--
ALTER TABLE `participacion_evento`
  ADD PRIMARY KEY (`id_participacion`),
  ADD KEY `pe_id_evento_fkey` (`id_evento`),
  ADD KEY `pe_id_usuario_fkey` (`id_usuario`);

--
-- Indices de la tabla `persona`
--
ALTER TABLE `persona`
  ADD PRIMARY KEY (`id_persona`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `fk_persona_calle` (`id_calle`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `tipos_beneficio`
--
ALTER TABLE `tipos_beneficio`
  ADD PRIMARY KEY (`id_tipo_beneficio`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `id_persona` (`id_persona`),
  ADD KEY `usuario_id_rol_fkey` (`id_rol`);

--
-- Indices de la tabla `vivienda`
--
ALTER TABLE `vivienda`
  ADD PRIMARY KEY (`id_vivienda`),
  ADD KEY `vivienda_id_calle_fkey` (`id_calle`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `calle`
--
ALTER TABLE `calle`
  MODIFY `id_calle` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `carga_familiar`
--
ALTER TABLE `carga_familiar`
  MODIFY `id_carga` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id_comentario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `concepto_pago`
--
ALTER TABLE `concepto_pago`
  MODIFY `id_concepto` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `evento`
--
ALTER TABLE `evento`
  MODIFY `id_evento` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `habitante`
--
ALTER TABLE `habitante`
  MODIFY `id_habitante` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `indicador_gestion`
--
ALTER TABLE `indicador_gestion`
  MODIFY `id_indicador` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `log_actividad`
--
ALTER TABLE `log_actividad`
  MODIFY `id_log` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `noticias`
--
ALTER TABLE `noticias`
  MODIFY `id_noticia` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `id_pago` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos_periodos`
--
ALTER TABLE `pagos_periodos`
  MODIFY `id_periodo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `participacion_evento`
--
ALTER TABLE `participacion_evento`
  MODIFY `id_participacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `persona`
--
ALTER TABLE `persona`
  MODIFY `id_persona` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipos_beneficio`
--
ALTER TABLE `tipos_beneficio`
  MODIFY `id_tipo_beneficio` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `vivienda`
--
ALTER TABLE `vivienda`
  MODIFY `id_vivienda` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carga_familiar`
--
ALTER TABLE `carga_familiar`
  ADD CONSTRAINT `carga_familiar_id_habitante_fkey` FOREIGN KEY (`id_habitante`) REFERENCES `habitante` (`id_habitante`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `carga_familiar_id_jefe_fkey` FOREIGN KEY (`id_jefe`) REFERENCES `habitante` (`id_habitante`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `fk_comentario_noticia` FOREIGN KEY (`id_noticia`) REFERENCES `noticias` (`id_noticia`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comentario_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `evento`
--
ALTER TABLE `evento`
  ADD CONSTRAINT `evento_creado_por_fkey` FOREIGN KEY (`creado_por`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `habitante`
--
ALTER TABLE `habitante`
  ADD CONSTRAINT `habitante_id_persona_fkey` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `habitante_vivienda`
--
ALTER TABLE `habitante_vivienda`
  ADD CONSTRAINT `hv_id_habitante_fkey` FOREIGN KEY (`id_habitante`) REFERENCES `habitante` (`id_habitante`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `hv_id_vivienda_fkey` FOREIGN KEY (`id_vivienda`) REFERENCES `vivienda` (`id_vivienda`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `indicador_gestion`
--
ALTER TABLE `indicador_gestion`
  ADD CONSTRAINT `indicador_gestion_generado_por_fkey` FOREIGN KEY (`generado_por`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `lider_calle`
--
ALTER TABLE `lider_calle`
  ADD CONSTRAINT `lider_calle_id_calle_fkey` FOREIGN KEY (`id_calle`) REFERENCES `calle` (`id_calle`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `lider_calle_id_habitante_fkey` FOREIGN KEY (`id_habitante`) REFERENCES `habitante` (`id_habitante`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `lider_comunal`
--
ALTER TABLE `lider_comunal`
  ADD CONSTRAINT `lider_comunal_id_habitante_fkey` FOREIGN KEY (`id_habitante`) REFERENCES `habitante` (`id_habitante`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `log_actividad`
--
ALTER TABLE `log_actividad`
  ADD CONSTRAINT `log_actividad_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD CONSTRAINT `fk_noticias_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notificacion_destino` FOREIGN KEY (`id_usuario_destino`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notificacion_origen` FOREIGN KEY (`id_usuario_origen`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `pago_id_concepto_fkey` FOREIGN KEY (`id_concepto`) REFERENCES `concepto_pago` (`id_concepto`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `pago_id_usuario_fkey` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`id_tipo_beneficio`) REFERENCES `tipos_beneficio` (`id_tipo_beneficio`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_3` FOREIGN KEY (`id_periodo`) REFERENCES `pagos_periodos` (`id_periodo`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `participacion_evento`
--
ALTER TABLE `participacion_evento`
  ADD CONSTRAINT `pe_id_evento_fkey` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id_evento`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `pe_id_usuario_fkey` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `persona`
--
ALTER TABLE `persona`
  ADD CONSTRAINT `fk_persona_calle` FOREIGN KEY (`id_calle`) REFERENCES `calle` (`id_calle`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_id_persona_fkey` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `usuario_id_rol_fkey` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `vivienda`
--
ALTER TABLE `vivienda`
  ADD CONSTRAINT `vivienda_id_calle_fkey` FOREIGN KEY (`id_calle`) REFERENCES `calle` (`id_calle`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- Migración: Agregar rol secundario a la tabla usuario
-- Fecha: 2025-10-23
-- Descripción: Permite que un usuario tenga un rol secundario adicional

-- Agregar columna id_rol_secundario
ALTER TABLE `usuario` 
ADD COLUMN `id_rol_secundario` int(10) UNSIGNED DEFAULT NULL COMMENT 'Rol secundario opcional (ej: Líder que también es Jefe de Familia)' 
AFTER `id_rol`;

-- Agregar foreign key constraint
ALTER TABLE `usuario`
ADD CONSTRAINT `fk_usuario_rol_secundario` 
FOREIGN KEY (`id_rol_secundario`) REFERENCES `rol`(`id_rol`) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- Comentarios para documentación
ALTER TABLE `usuario` 
MODIFY COLUMN `id_rol` int(10) UNSIGNED DEFAULT NULL COMMENT 'Rol principal del usuario';

-- Ejemplo de uso:
-- UPDATE `usuario` SET `id_rol_secundario` = 3 WHERE `id_usuario` = 4 AND `id_rol` = 2;
-- Esto haría que el usuario con id_rol=2 (Líder) también tenga permisos de id_rol=3 (Jefe de Familia)
