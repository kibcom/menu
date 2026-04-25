-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2026 at 02:05 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `menus`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `username`, `password`, `created_at`) VALUES
(1, 'Super Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-13 11:29:19');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `menu_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `menu_id`, `name`, `sort_order`, `created_at`) VALUES
(1, 1, 'food', 1, '2026-04-13 11:32:22'),
(2, 2, 'Starters', 1, '2026-04-13 11:34:02'),
(3, 2, 'Main Course', 2, '2026-04-13 11:34:02'),
(4, 2, 'Desserts', 3, '2026-04-13 11:34:03'),
(5, 2, 'Drinks', 4, '2026-04-13 11:34:03');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(10) UNSIGNED NOT NULL,
  `menu_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `menu_id`, `category_id`, `name`, `description`, `price`, `image`, `sort_order`, `is_visible`, `created_at`) VALUES
(1, 1, 1, 'Piza', '', '200.00', 'uploads/items/20260413103308_03620188.jpg', 0, 1, '2026-04-13 11:33:08'),
(2, 2, 2, 'Truffle Fries', 'Crispy fries with truffle oil and parmesan.', '6.50', NULL, 1, 1, '2026-04-13 11:34:03'),
(3, 2, 2, 'Bruschetta', 'Toasted bread with tomato basil and olive oil.', '5.90', NULL, 2, 1, '2026-04-13 11:34:03'),
(4, 2, 3, 'Grilled Salmon', 'Served with herbed rice and lemon butter.', '16.75', NULL, 1, 1, '2026-04-13 11:34:03'),
(5, 2, 3, 'Beef Burger Deluxe', 'Angus patty, cheddar, caramelized onion, and fries.', '12.90', NULL, 2, 1, '2026-04-13 11:34:03'),
(6, 2, 4, 'Chocolate Lava Cake', 'Warm chocolate cake with vanilla cream.', '7.40', NULL, 1, 1, '2026-04-13 11:34:03'),
(7, 2, 4, 'Classic Tiramisu', 'Coffee layered mascarpone dessert.', '6.80', NULL, 2, 1, '2026-04-13 11:34:03'),
(8, 2, 5, 'Iced Latte', 'Chilled espresso with milk and ice.', '4.20', NULL, 1, 0, '2026-04-13 11:34:03'),
(9, 2, 5, 'Fresh Orange Juice', 'Cold-pressed and unsweetened.', '3.90', NULL, 2, 0, '2026-04-13 11:34:03');

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `menu_code` varchar(40) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_image` varchar(255) DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `name`, `menu_code`, `description`, `logo_image`, `banner_image`, `views`, `created_at`) VALUES
(1, 'Africa', 'eu0e6v0zk1', 'hotel', 'uploads/menus/20260413103030_02f11311.jpg', 'uploads/menus/20260413103030_21f7cb2c.jpg', 13, '2026-04-13 11:30:30'),
(2, 'Demo Restaurant', 'demo2026', 'Fresh flavors, premium ingredients, and a modern dining experience.', 'uploads/menus/20260413133352_245e1d92.png', 'uploads/menus/20260413133404_3f51fe97.jpg', 157, '2026-04-13 11:34:02');

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `menu_id` int(10) UNSIGNED NOT NULL,
  `qr_path` varchar(255) NOT NULL,
  `qr_url` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_codes`
--

INSERT INTO `qr_codes` (`id`, `menu_id`, `qr_path`, `qr_url`, `created_at`) VALUES
(1, 1, 'qrcodes/qr_eu0e6v0zk1.png', 'http://localhost:8080/menus/menu.php?id=eu0e6v0zk1', '2026-04-13 11:30:31'),
(2, 2, 'qrcodes/qr_demo2026.png', 'http://localhost:8080/menus/menu.php?id=demo2026', '2026-04-13 11:34:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_categories_menu` (`menu_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_items_menu` (`menu_id`),
  ADD KEY `fk_items_category` (`category_id`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `menu_code` (`menu_code`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_qr_menu` (`menu_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_items_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `fk_qr_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
