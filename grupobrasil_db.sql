-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-10-2025 a las 07:47:28
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
-- Estructura de tabla para la tabla `vivienda_detalle`
--

CREATE TABLE `vivienda_detalle` (
  `id_vivienda_detalle` int(10) UNSIGNED NOT NULL,
  `id_vivienda` int(10) UNSIGNED NOT NULL,
  `habitaciones` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `banos` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `servicios` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vivienda_detalle`
--

INSERT INTO `vivienda_detalle` (`id_vivienda_detalle`, `id_vivienda`, `habitaciones`, `banos`, `servicios`, `created_at`, `updated_at`) VALUES
(1, 4, 3, 2, 'Agua, Luz, Internet', '2025-10-30 00:52:23', '2025-10-30 00:52:23');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `vivienda_detalle`
--
ALTER TABLE `vivienda_detalle`
  ADD PRIMARY KEY (`id_vivienda_detalle`),
  ADD UNIQUE KEY `uq_vivienda_detalle_id_vivienda` (`id_vivienda`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `vivienda_detalle`
--
ALTER TABLE `vivienda_detalle`
  MODIFY `id_vivienda_detalle` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `vivienda_detalle`
--
ALTER TABLE `vivienda_detalle`
  ADD CONSTRAINT `fk_vivienda_detalle_vivienda` FOREIGN KEY (`id_vivienda`) REFERENCES `vivienda` (`id_vivienda`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
