-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-06-2026 a las 00:57:48
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
-- Base de datos: `cafeteria_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id_insumo` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cantidad_actual` decimal(10,2) NOT NULL,
  `unidad_medida` varchar(20) NOT NULL,
  `stock_minimo` decimal(10,2) NOT NULL,
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id_insumo`, `nombre`, `cantidad_actual`, `unidad_medida`, `stock_minimo`, `fecha_actualizacion`) VALUES
(1, 'Café en grano', 499.97, 'kg', 10.00, '2026-06-14 17:50:52'),
(2, 'Leche entera', 899.76, 'L', 20.00, '2026-06-14 17:52:10'),
(3, 'Azúcar', 200.00, 'kg', 10.00, '2026-06-14 17:31:20'),
(4, 'Vasos desechables', 5000.00, 'uds', 100.00, '2026-06-14 17:31:20'),
(5, 'Tazas cerámica', 300.00, 'uds', 50.00, '2026-06-14 17:31:20'),
(6, 'Croissants', 500.00, 'uds', 20.00, '2026-06-14 17:31:20'),
(7, 'Mantequilla', 50.00, 'kg', 5.00, '2026-06-14 17:31:20'),
(8, 'Harina', 200.00, 'kg', 20.00, '2026-06-14 17:31:20'),
(9, 'Agua', 4999.85, 'L', 100.00, '2026-06-14 17:50:52'),
(10, 'Hielo', 500.00, 'kg', 20.00, '2026-06-14 17:31:20'),
(11, 'Cacao en polvo', 100.00, 'kg', 5.00, '2026-06-14 17:31:20'),
(12, 'Té Verde Matcha', 50.00, 'kg', 2.00, '2026-06-14 17:31:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesas`
--

CREATE TABLE `mesas` (
  `id_mesa` int(10) UNSIGNED NOT NULL,
  `numero_mesa` int(11) NOT NULL,
  `capacidad` int(11) NOT NULL,
  `estado` enum('disponible','ocupada','mantenimiento') DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mesas`
--

INSERT INTO `mesas` (`id_mesa`, `numero_mesa`, `capacidad`, `estado`) VALUES
(1, 1, 2, 'disponible'),
(2, 2, 2, 'ocupada'),
(3, 3, 2, 'disponible'),
(4, 4, 2, 'disponible'),
(5, 5, 2, 'disponible'),
(6, 6, 2, 'disponible'),
(7, 7, 4, 'disponible'),
(8, 8, 4, 'disponible'),
(9, 9, 4, 'disponible'),
(10, 10, 4, 'disponible'),
(11, 11, 4, 'disponible'),
(12, 12, 4, 'disponible'),
(13, 13, 4, 'disponible'),
(14, 14, 4, 'disponible'),
(15, 15, 4, 'disponible'),
(16, 16, 4, 'disponible'),
(17, 17, 6, 'disponible'),
(18, 18, 6, 'disponible'),
(19, 19, 6, 'disponible'),
(20, 20, 6, 'disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `nombre`, `descripcion`, `precio`, `categoria`, `disponible`, `fecha_creacion`) VALUES
(1, 'Espresso', 'Café puro y concentrado', 5.00, 'Bebidas Calientes', 1, '2026-06-14 17:31:20'),
(2, 'Americano', 'Espresso con agua caliente', 6.00, 'Bebidas Calientes', 1, '2026-06-14 17:31:20'),
(3, 'Cappuccino', 'Espresso con leche espumada', 8.50, 'Bebidas Calientes', 1, '2026-06-14 17:31:20'),
(4, 'Latte', 'Espresso con abundante leche al vapor', 9.00, 'Bebidas Calientes', 1, '2026-06-14 17:31:20'),
(5, 'Macchiato', 'Espresso manchado con espuma de leche', 6.50, 'Bebidas Calientes', 1, '2026-06-14 17:31:20'),
(6, 'Mocha', 'Espresso con chocolate y leche', 10.00, 'Bebidas Calientes', 1, '2026-06-14 17:31:20'),
(7, 'Flat White', 'Doble ristretto con leche microespumada', 9.50, 'Bebidas Calientes', 1, '2026-06-14 17:31:20'),
(8, 'Té Verde', 'Infusión de hojas de té verde orgánico', 6.00, 'Bebidas Calientes', 1, '2026-06-14 17:31:20'),
(9, 'Té Negro', 'Infusión intensa de té negro', 6.00, 'Bebidas Calientes', 1, '2026-06-14 17:31:20'),
(10, 'Chocolate Caliente', 'Cacao puro fundido con leche entera', 11.00, 'Bebidas Calientes', 1, '2026-06-14 17:31:20'),
(11, 'Iced Coffee', 'Café pasado en frío con hielos', 7.50, 'Bebidas Frías', 1, '2026-06-14 17:31:20'),
(12, 'Iced Latte', 'Latte clásico servido frío', 10.00, 'Bebidas Frías', 1, '2026-06-14 17:31:20'),
(13, 'Frappuccino Clásico', 'Café batido con hielo y crema', 12.00, 'Bebidas Frías', 1, '2026-06-14 17:31:20'),
(14, 'Frappuccino Mocha', 'Frappe con chocolate y crema chantilly', 13.50, 'Bebidas Frías', 1, '2026-06-14 17:31:20'),
(15, 'Smoothie Vainilla', 'Batido helado sabor vainilla', 11.00, 'Bebidas Frías', 1, '2026-06-14 17:31:20'),
(16, 'Limonada', 'Limonada fresca endulzada', 6.00, 'Bebidas Frías', 1, '2026-06-14 17:31:20'),
(17, 'Jugo Naranja', 'Naranjas recién exprimidas', 8.00, 'Bebidas Frías', 1, '2026-06-14 17:31:20'),
(18, 'Té Helado', 'Té negro frío con limón', 7.00, 'Bebidas Frías', 1, '2026-06-14 17:31:20'),
(19, 'Cold Brew', 'Café macerado en frío por 24 horas', 11.00, 'Bebidas Frías', 1, '2026-06-14 17:31:20'),
(20, 'Iced Matcha', 'Té verde matcha con leche y hielo', 14.00, 'Bebidas Frías', 1, '2026-06-14 17:31:20'),
(21, 'Torta de Chocolate', 'Bizcocho húmedo de chocolate con fudge', 12.00, 'Postres', 1, '2026-06-14 17:31:20'),
(22, 'Cheesecake', 'Tarta de queso con salsa de frutos rojos', 14.00, 'Postres', 1, '2026-06-14 17:31:20'),
(23, 'Tiramisú', 'Postre italiano con mascarpone y café', 15.00, 'Postres', 1, '2026-06-14 17:31:20'),
(24, 'Pie Limón', 'Masa crujiente cubierta con crema de limón', 10.00, 'Postres', 1, '2026-06-14 17:31:20'),
(25, 'Brownie', 'Cuadrado denso de chocolate y nueces', 7.00, 'Postres', 1, '2026-06-14 17:31:20'),
(26, 'Alfajor', 'Dulce tradicional relleno de manjar blanco', 4.50, 'Postres', 1, '2026-06-14 17:31:20'),
(27, 'Tres Leches', 'Bizcocho bañado en tres tipos de leches', 11.00, 'Postres', 1, '2026-06-14 17:31:20'),
(28, 'Crema Volteada', 'Postre clásico peruano con caramelo', 9.00, 'Postres', 1, '2026-06-14 17:31:20'),
(29, 'Flan', 'Flan de vainilla casero', 8.00, 'Postres', 1, '2026-06-14 17:31:20'),
(30, 'Red Velvet', 'Torta de terciopelo rojo con queso crema', 13.00, 'Postres', 1, '2026-06-14 17:31:20'),
(31, 'Croissant Clásico', 'Pan francés hojaldrado de mantequilla', 5.50, 'Panadería', 1, '2026-06-14 17:31:20'),
(32, 'Croissant Relleno', 'Hojaldre relleno de jamón y queso', 8.00, 'Panadería', 1, '2026-06-14 17:31:20'),
(33, 'Pan de Queso', 'Pan horneado con queso parmesano', 4.00, 'Panadería', 1, '2026-06-14 17:31:20'),
(34, 'Empanada Carne', 'Empanada horneada rellena de carne', 7.00, 'Panadería', 1, '2026-06-14 17:31:20'),
(35, 'Empanada Pollo', 'Empanada horneada rellena de pollo', 7.00, 'Panadería', 1, '2026-06-14 17:31:20'),
(36, 'Sandwich Mixto', 'Pan de molde con jamón y queso derretido', 9.00, 'Panadería', 1, '2026-06-14 17:31:20'),
(37, 'Triple', 'Sándwich de tres pisos con palta, huevo y tomate', 10.00, 'Panadería', 1, '2026-06-14 17:31:20'),
(38, 'Pastel de Acelga', 'Pastel salado relleno de acelga y huevo', 8.50, 'Panadería', 1, '2026-06-14 17:31:20'),
(39, 'Cachito Mantequilla', 'Pan dulce enrollado sabor mantequilla', 3.50, 'Panadería', 1, '2026-06-14 17:31:20'),
(40, 'Cachito Manjar', 'Cachito relleno de abundante manjar blanco', 4.50, 'Panadería', 1, '2026-06-14 17:31:20'),
(41, 'Pan de Ajo', 'Baguette rebanado con ajo y orégano', 6.00, 'Panadería', 1, '2026-06-14 17:31:20'),
(42, 'Muffin Arándanos', 'Quequito horneado con arándanos frescos', 6.50, 'Panadería', 1, '2026-06-14 17:31:20'),
(43, 'Muffin Chocolate', 'Quequito denso de puro chocolate', 6.50, 'Panadería', 1, '2026-06-14 17:31:20'),
(44, 'Galleta Avena', 'Galleta crocante de avena y pasas', 3.00, 'Panadería', 1, '2026-06-14 17:31:20'),
(45, 'Galleta Chocochip', 'Galleta con chispas de chocolate', 3.50, 'Panadería', 1, '2026-06-14 17:31:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_mermas`
--

CREATE TABLE `registro_mermas` (
  `id_merma` int(10) UNSIGNED NOT NULL,
  `insumo_id` int(10) UNSIGNED NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `fecha_registro` date NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `registro_mermas`
--

INSERT INTO `registro_mermas` (`id_merma`, `insumo_id`, `cantidad`, `motivo`, `fecha_registro`, `fecha_creacion`) VALUES
(1, 2, 100.00, 'vencimiento', '2026-06-15', '2026-06-14 17:52:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id_reserva` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `capacidad_mesa` int(10) UNSIGNED NOT NULL,
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `mesa_id` int(10) UNSIGNED NOT NULL,
  `estado` enum('Activa','Cancelada','Cumplida') NOT NULL DEFAULT 'Activa',
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`id_reserva`, `fecha`, `hora`, `capacidad_mesa`, `cliente_id`, `mesa_id`, `estado`, `observaciones`) VALUES
(1, '2026-06-14', '17:00:00', 2, 3, 2, 'Activa', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('Administrador','Barista','Cliente') NOT NULL DEFAULT 'Cliente',
  `estado` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `usuario`, `password_hash`, `rol`, `estado`, `direccion`, `telefono`, `fecha_creacion`) VALUES
(1, 'Administrador Principal', 'admin', '$2y$10$fvNig82UrUUy1VPZ7skIwONKnlKgpWcANfx/DjJ87aRWprKmQMpKC', 'Administrador', 'Activo', 'Local Central', '999999999', '2026-06-14 17:31:20'),
(2, 'Barista Turno Mañana', 'barista', '$2y$10$nVnIG8ZH/uQ.6zgB8ICodutsix65V.b3k9.1fkD21pueciDP3eWay', 'Barista', 'Activo', 'Local Central', '888888888', '2026-06-14 17:31:20'),
(3, 'Augusto Flores', 'augusto', '$2y$10$VyxwGwX4LRc6HqFIUGQYk.jecIg0Y0n4CygIfPFW1I6iU9T1av0zi', 'Cliente', 'Activo', 'Av. Universitaria 1250, Los Olivos - Frente al Parque', '912345678', '2026-06-14 17:31:20'),
(4, 'Andres Flores', 'andres', '$2y$10$.WwlYtpVbNQflnBiJDRs3eKMflsYMomgwk80U.kxInSeKKn2dEgYC', 'Cliente', 'Activo', 'UPN', '954877852', '2026-06-14 17:47:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` enum('Efectivo','Tarjeta') NOT NULL DEFAULT 'Efectivo',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `usuario_id`, `total`, `metodo_pago`, `fecha_creacion`) VALUES
(1, 3, 19.00, 'Efectivo', '2026-06-14 17:46:11'),
(2, 2, 16.00, 'Tarjeta', '2026-06-14 17:50:41'),
(3, 2, 35.00, 'Efectivo', '2026-06-14 17:50:52');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id_insumo`);

--
-- Indices de la tabla `mesas`
--
ALTER TABLE `mesas`
  ADD PRIMARY KEY (`id_mesa`),
  ADD UNIQUE KEY `numero_mesa` (`numero_mesa`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `registro_mermas`
--
ALTER TABLE `registro_mermas`
  ADD PRIMARY KEY (`id_merma`),
  ADD KEY `fk_mermas_insumo` (`insumo_id`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id_reserva`),
  ADD KEY `fk_res_cliente` (`cliente_id`),
  ADD KEY `fk_res_mesa` (`mesa_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `fk_ventas_usuario` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id_insumo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `mesas`
--
ALTER TABLE `mesas`
  MODIFY `id_mesa` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `registro_mermas`
--
ALTER TABLE `registro_mermas`
  MODIFY `id_merma` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id_reserva` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `registro_mermas`
--
ALTER TABLE `registro_mermas`
  ADD CONSTRAINT `fk_mermas_insumo` FOREIGN KEY (`insumo_id`) REFERENCES `inventario` (`id_insumo`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_res_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `fk_res_mesa` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id_mesa`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
