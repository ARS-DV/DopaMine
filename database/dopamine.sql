-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-05-2026 a las 10:57:27
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
-- Base de datos: `dopaminedb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habit`
--

CREATE TABLE `habit` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `descrip` varchar(500) DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `frecuency` enum('daily','weekly','monthly') DEFAULT 'daily',
  `dayOfMonth` tinyint(4) DEFAULT NULL,
  `best_streak` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `habit`
--

INSERT INTO `habit` (`id`, `user_id`, `title`, `descrip`, `icon`, `frecuency`, `dayOfMonth`, `best_streak`) VALUES
(21, 1, 'Beber 1,5L de agua', '', '🫗', 'daily', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habit_day`
--

CREATE TABLE `habit_day` (
  `habit_id` int(11) NOT NULL,
  `dayOfWeek` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habit_record`
--

CREATE TABLE `habit_record` (
  `id` int(11) NOT NULL,
  `habit_id` int(11) NOT NULL,
  `dateOfHabit` date NOT NULL,
  `done` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `habit_record`
--

INSERT INTO `habit_record` (`id`, `habit_id`, `dateOfHabit`, `done`) VALUES
(33, 21, '2026-05-17', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `routine`
--

CREATE TABLE `routine` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `descrip` varchar(500) DEFAULT NULL,
  `frecuency` enum('daily','weekly','monthly') DEFAULT 'daily',
  `dayOfMonth` tinyint(4) DEFAULT NULL,
  `best_streak` int(11) DEFAULT 0,
  `color` varchar(7) DEFAULT '#6B8FA3',
  `hour` time DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `routine`
--

INSERT INTO `routine` (`id`, `user_id`, `title`, `descrip`, `frecuency`, `dayOfMonth`, `best_streak`, `color`, `hour`, `icon`) VALUES
(5, 1, 'Rutina mañanera', '', 'daily', NULL, 0, '#6B8FA3', '00:00:00', '🌄');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `routine_checklist`
--

CREATE TABLE `routine_checklist` (
  `id` int(11) NOT NULL,
  `routine_id` int(11) NOT NULL,
  `title` varchar(300) NOT NULL,
  `done` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `routine_checklist`
--

INSERT INTO `routine_checklist` (`id`, `routine_id`, `title`, `done`, `sort_order`) VALUES
(12, 5, 'Desayunar', 1, 1),
(13, 5, 'Revisar correo', 1, 2),
(14, 5, 'Sacar al perro', 1, 3),
(15, 5, 'Limpieza general de 20 minutos', 0, 4),
(16, 5, 'Fregar cacharros del desayuno', 0, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `routine_day`
--

CREATE TABLE `routine_day` (
  `routine_id` int(11) NOT NULL,
  `dayOfWeek` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `routine_record`
--

CREATE TABLE `routine_record` (
  `id` int(11) NOT NULL,
  `routine_id` int(11) NOT NULL,
  `dateOfRoutine` date NOT NULL,
  `totalSubtasks` tinyint(4) NOT NULL,
  `doneSubtasks` tinyint(4) NOT NULL,
  `done` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `routine_record`
--

INSERT INTO `routine_record` (`id`, `routine_id`, `dateOfRoutine`, `totalSubtasks`, `doneSubtasks`, `done`) VALUES
(11, 5, '2026-05-17', 5, 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `task`
--

CREATE TABLE `task` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `descrip` varchar(500) DEFAULT NULL,
  `startDate` datetime DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `done` tinyint(1) DEFAULT 0,
  `createdDate` datetime DEFAULT current_timestamp(),
  `expDate` datetime NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `url2` varchar(500) DEFAULT NULL,
  `url3` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `task`
--

INSERT INTO `task` (`id`, `user_id`, `title`, `descrip`, `startDate`, `difficulty`, `done`, `createdDate`, `expDate`, `url`, `icon`, `url2`, `url3`) VALUES
(6, 1, 'Dejar el codigo finalmente todo listo', 'Revisar las pantallas que faltan, la seguridad y las funciones incorrectas', '2026-05-17 00:00:00', 'medium', 1, '2026-05-17 09:42:31', '2026-05-17 23:59:00', NULL, '😫', NULL, NULL),
(7, 1, 'Arreglar 1º parrafo de documen', 'Adaptar a un formato más humano la documentación del proyecto', '2026-05-17 00:00:00', 'easy', 0, '2026-05-17 09:49:34', '2026-05-17 23:59:00', NULL, NULL, NULL, NULL),
(8, 1, 'Ejercicios de Like', 'Hacer ejercicios de ingles sobre like', '2026-05-17 00:00:00', 'medium', 0, '2026-05-17 10:31:27', '2026-05-17 23:59:00', NULL, '📔', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `task_checklist`
--

CREATE TABLE `task_checklist` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `title` varchar(300) NOT NULL,
  `done` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `task_checklist`
--

INSERT INTO `task_checklist` (`id`, `task_id`, `title`, `done`, `sort_order`) VALUES
(8, 6, 'Tener alguna vista en la panta', 1, 1),
(9, 6, 'Proteger enlaces de la web', 1, 1),
(10, 6, 'Cambiar el el array de habitos', 1, 2),
(11, 6, 'Añadir la opcion de poner link', 1, 3),
(14, 6, 'Añadir emojis tambien a tareas', 1, 5),
(17, 6, 'cambiar el header para que sea', 1, 8),
(18, 6, 'que aparezca algo en los enlac', 1, 9),
(19, 8, 'Links and AS', 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `task_record`
--

CREATE TABLE `task_record` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `doneDate` datetime DEFAULT current_timestamp(),
  `onTime` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `task_record`
--

INSERT INTO `task_record` (`id`, `task_id`, `doneDate`, `onTime`) VALUES
(4, 6, '2026-05-17 10:45:54', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nickName` varchar(30) NOT NULL,
  `email` varchar(50) NOT NULL,
  `pswd` varchar(200) NOT NULL,
  `energy` enum('low','medium','high') DEFAULT 'medium',
  `role` enum('user','admin') DEFAULT 'user',
  `createdDate` datetime DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user`
--

INSERT INTO `user` (`id`, `nickName`, `email`, `pswd`, `energy`, `role`, `createdDate`, `avatar`) VALUES
(1, 'Ainara', 'ainararuizsierra@gmail.com', '$2y$10$B0ynWbTU6uFojKwAyj5raerjQ8k3fkb1YGuU0IIqm7SpluHfs3d9q', 'high', 'admin', '2026-05-15 23:28:08', 'avatar_1_1778920054.jpg'),
(2, 'Pepe', 'pepegonzalez@gmail.com', '$2y$10$8LdQr/JpMBxiZ73uC17vQuo5A4.X41yu8Zgyd3pZk6F1u7e/T7VS.', 'medium', 'user', '2026-05-15 23:28:08', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `habit`
--
ALTER TABLE `habit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `habit_day`
--
ALTER TABLE `habit_day`
  ADD PRIMARY KEY (`habit_id`,`dayOfWeek`);

--
-- Indices de la tabla `habit_record`
--
ALTER TABLE `habit_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `habit_id` (`habit_id`);

--
-- Indices de la tabla `routine`
--
ALTER TABLE `routine`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `routine_checklist`
--
ALTER TABLE `routine_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `routine_id` (`routine_id`);

--
-- Indices de la tabla `routine_day`
--
ALTER TABLE `routine_day`
  ADD PRIMARY KEY (`routine_id`,`dayOfWeek`);

--
-- Indices de la tabla `routine_record`
--
ALTER TABLE `routine_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `routine_id` (`routine_id`);

--
-- Indices de la tabla `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `task_checklist`
--
ALTER TABLE `task_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indices de la tabla `task_record`
--
ALTER TABLE `task_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indices de la tabla `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `habit`
--
ALTER TABLE `habit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `habit_record`
--
ALTER TABLE `habit_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `routine`
--
ALTER TABLE `routine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `routine_checklist`
--
ALTER TABLE `routine_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `routine_record`
--
ALTER TABLE `routine_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `task`
--
ALTER TABLE `task`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `task_checklist`
--
ALTER TABLE `task_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `task_record`
--
ALTER TABLE `task_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `habit`
--
ALTER TABLE `habit`
  ADD CONSTRAINT `habit_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `habit_day`
--
ALTER TABLE `habit_day`
  ADD CONSTRAINT `habit_day_ibfk_1` FOREIGN KEY (`habit_id`) REFERENCES `habit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `habit_record`
--
ALTER TABLE `habit_record`
  ADD CONSTRAINT `habit_record_ibfk_1` FOREIGN KEY (`habit_id`) REFERENCES `habit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `routine`
--
ALTER TABLE `routine`
  ADD CONSTRAINT `routine_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `routine_checklist`
--
ALTER TABLE `routine_checklist`
  ADD CONSTRAINT `routine_checklist_ibfk_1` FOREIGN KEY (`routine_id`) REFERENCES `routine` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `routine_day`
--
ALTER TABLE `routine_day`
  ADD CONSTRAINT `routine_day_ibfk_1` FOREIGN KEY (`routine_id`) REFERENCES `routine` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `routine_record`
--
ALTER TABLE `routine_record`
  ADD CONSTRAINT `routine_record_ibfk_1` FOREIGN KEY (`routine_id`) REFERENCES `routine` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `task_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `task_checklist`
--
ALTER TABLE `task_checklist`
  ADD CONSTRAINT `task_checklist_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `task_record`
--
ALTER TABLE `task_record`
  ADD CONSTRAINT `task_record_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
