-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2026 at 02:01 AM
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
  `role` enum('super_admin','admin') NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `username`, `password`, `role`, `is_active`, `created_at`) VALUES
(1, 'Super Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 1, '2026-04-13 11:29:19'),
(2, 'africa admin', 'adminaf', '$2y$10$V/zzDnkU.wSLOnDNO0qoseIO10QAvrN09PqCpSE9RCddfEQGNxg5a', 'admin', 1, '2026-04-15 10:06:10');

-- --------------------------------------------------------

--
-- Table structure for table `admin_menu_assignments`
--

CREATE TABLE `admin_menu_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `menu_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_menu_assignments`
--

INSERT INTO `admin_menu_assignments` (`id`, `admin_id`, `menu_id`, `created_at`) VALUES
(1, 2, 1, '2026-04-15 10:06:10');

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
(5, 2, 'Drinks', 4, '2026-04-13 11:34:03'),
(6, 1, 'Breakfast', 0, '2026-04-23 17:21:01'),
(7, 1, 'Lunch', 0, '2026-04-23 17:21:01'),
(8, 1, 'Dinner', 0, '2026-04-23 17:21:01'),
(10, 3, 'Breakfast', 0, '2026-04-23 18:10:36'),
(11, 3, 'Lunch', 0, '2026-04-23 18:10:36'),
(12, 3, 'Dinner', 0, '2026-04-23 18:10:36'),
(13, 3, 'Appetizers', 0, '2026-04-23 18:10:36'),
(14, 3, 'Main Course', 0, '2026-04-23 18:10:36'),
(15, 3, 'Desserts', 0, '2026-04-23 18:10:36'),
(16, 3, 'Drinks', 0, '2026-04-23 18:10:36'),
(17, 3, 'Hot Beverages', 0, '2026-04-23 18:10:36'),
(18, 3, 'Cold Beverages', 0, '2026-04-23 18:10:36'),
(19, 3, 'Kids Menu', 0, '2026-04-23 18:10:36'),
(20, 3, 'Special Offers', 0, '2026-04-23 18:10:36'),
(21, 2, 'Garden & Bowls', 5, '2026-04-24 01:09:00'),
(22, 2, 'Chef Sides', 6, '2026-04-24 01:09:00'),
(23, 2, 'Brunch', 7, '2026-04-24 01:09:00');

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
(2, 2, 2, 'Truffle Fries', 'Crispy fries, truffle oil, parmesan, and fresh herbs.', '6.50', 'https://images.unsplash.com/photo-1573080496219-bb080dd4dcee?auto=format&fit=crop&w=800&q=80', 1, 1, '2026-04-13 11:34:03'),
(3, 2, 2, 'Bruschetta', 'Toasted bread with tomato basil and olive oil.', '5.90', NULL, 2, 1, '2026-04-13 11:34:03'),
(4, 2, 3, 'Grilled Salmon', 'Herbed rice, charred lemon, brown butter capers.', '18.50', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=800&q=80', 1, 1, '2026-04-13 11:34:03'),
(5, 2, 3, 'Beef Burger Deluxe', 'Angus patty, aged cheddar, caramelized onion, brioche, fries.', '14.50', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=800&q=80', 2, 1, '2026-04-13 11:34:03'),
(6, 2, 4, 'Chocolate Lava Cake', 'Molten center, salted caramel, and crème fraîche.', '7.90', 'https://images.unsplash.com/photo-1606313564200-e75d5e39b904?auto=format&fit=crop&w=800&q=80', 1, 1, '2026-04-13 11:34:03'),
(7, 2, 4, 'Classic Tiramisu', 'Espresso-soaked ladyfingers, mascarpone, cocoa dust.', '7.20', 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?auto=format&fit=crop&w=800&q=80', 2, 1, '2026-04-13 11:34:03'),
(8, 2, 5, 'Iced Latte', 'Double espresso, oat milk, and vanilla bean.', '4.50', 'https://images.unsplash.com/photo-1517701604599-bb29b565ddc9?auto=format&fit=crop&w=800&q=80', 1, 0, '2026-04-13 11:34:03'),
(9, 2, 5, 'Fresh Orange Juice', 'Cold-pressed Valencia, no added sugar.', '3.95', 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=800&q=80', 2, 0, '2026-04-13 11:34:03'),
(10, 2, 2, 'Bruschetta Trio', 'Tomato basil, whipped ricotta, and olive tapenade on grilled sourdough.', '7.20', 'https://images.unsplash.com/photo-1572695157199-89b34b1728d8?auto=format&fit=crop&w=800&q=80', 2, 1, '2026-04-24 01:09:00'),
(11, 2, 2, 'Crispy Calamari', 'Lemon aioli, pickled chili, and micro cilantro.', '9.50', 'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?auto=format&fit=crop&w=800&q=80', 3, 1, '2026-04-24 01:09:00'),
(12, 2, 2, 'Korean BBQ Wings', 'Gochujang glaze, sesame, scallion, and cucumber ribbons.', '8.90', 'https://images.unsplash.com/photo-1527477396000-e27137b194f3?auto=format&fit=crop&w=800&q=80', 4, 1, '2026-04-24 01:09:00'),
(13, 2, 2, 'Charred Edamame', 'Tossed in garlic butter, togarashi, and flaky sea salt.', '5.40', 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=800&q=80', 5, 1, '2026-04-24 01:09:00'),
(14, 2, 21, 'Mediterranean Grain Bowl', 'Farro, chickpeas, feta, olives, cucumber, and lemon tahini.', '12.50', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=800&q=80', 1, 1, '2026-04-24 01:09:00'),
(15, 2, 21, 'Thai Crunch Salad', 'Cabbage slaw, peanut dressing, mango, and crispy shallots.', '11.25', 'https://images.unsplash.com/photo-1546793665-c74683f339c1?auto=format&fit=crop&w=800&q=80', 2, 1, '2026-04-24 01:09:00'),
(16, 2, 21, 'Roasted Beet & Citrus', 'Arugula, goat cheese mousse, pistachio, and blood orange.', '10.80', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=800&q=80', 3, 1, '2026-04-24 01:09:00'),
(17, 2, 21, 'Avocado Caesar', 'Little gem, white anchovy, parmesan crisp, and lime caesar.', '10.20', 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=800&q=80', 4, 1, '2026-04-24 01:09:00'),
(18, 2, 21, 'Smoked Salmon Poke', 'Sushi rice, edamame, avocado, ponzu, and crispy nori.', '13.90', 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80', 5, 1, '2026-04-24 01:09:00'),
(19, 2, 3, 'Miso Black Cod', 'Overnight marinade, bok choy, ginger dashi, and jasmine rice.', '22.00', 'https://images.unsplash.com/photo-1580476262798-bddd9f4b7369?auto=format&fit=crop&w=800&q=80', 3, 1, '2026-04-24 01:09:00'),
(20, 2, 3, 'Wild Mushroom Pappardelle', 'Porcini cream, truffle oil, pecorino, and crispy sage.', '16.25', 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&w=800&q=80', 4, 1, '2026-04-24 01:09:00'),
(21, 2, 3, 'Herb-Crusted Lamb Rack', 'Roasted garlic jus, fondant potato, and spring peas.', '28.00', 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80', 5, 1, '2026-04-24 01:09:00'),
(22, 2, 3, 'Smoked Brisket Plate', 'House BBQ, pickles, slaw, and buttered cornbread.', '19.75', 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?auto=format&fit=crop&w=800&q=80', 6, 1, '2026-04-24 01:09:00'),
(23, 2, 22, 'Truffle Parmesan Fries', 'Double-fried, black truffle salt, pecorino snow.', '6.00', 'https://images.unsplash.com/photo-1630384060881-c525f580c310?auto=format&fit=crop&w=800&q=80', 1, 1, '2026-04-24 01:09:00'),
(24, 2, 22, 'Maple Roasted Carrots', 'Harissa yogurt, dukkah, and mint.', '5.50', 'https://images.unsplash.com/photo-1511690743698-d9d85f2fbf38?auto=format&fit=crop&w=800&q=80', 2, 1, '2026-04-24 01:09:00'),
(25, 2, 22, 'Charred Broccolini', 'Calabrian chili, lemon zest, and toasted almonds.', '5.25', 'https://images.unsplash.com/photo-1584270354949-c26b0d5b4a0c?auto=format&fit=crop&w=800&q=80', 3, 1, '2026-04-24 01:09:00'),
(26, 2, 22, 'Creamy Polenta', 'Fontina, roasted mushrooms, and chive oil.', '6.75', 'https://images.unsplash.com/photo-1476124369491-e7f408a3753d?auto=format&fit=crop&w=800&q=80', 4, 1, '2026-04-24 01:09:00'),
(27, 2, 4, 'Yuzu Lemon Tart', 'Italian meringue, almond shell, and candied zest.', '6.80', 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?auto=format&fit=crop&w=800&q=80', 3, 1, '2026-04-24 01:09:00'),
(28, 2, 4, 'Matcha Basque Cheesecake', 'Burnt top, white chocolate crémeux, and red bean.', '8.25', 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?auto=format&fit=crop&w=800&q=80', 4, 1, '2026-04-24 01:09:00'),
(29, 2, 4, 'Seasonal Fruit Sorbet', 'Three scoops, mint, and almond tuile.', '5.90', 'https://images.unsplash.com/photo-1497034825429-c86d4636745c?auto=format&fit=crop&w=800&q=80', 5, 1, '2026-04-24 01:09:00'),
(30, 2, 5, 'Sparkling Yuzu Cooler', 'Yuzu, elderflower, soda, and cucumber ribbon.', '4.25', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=800&q=80', 3, 1, '2026-04-24 01:09:00'),
(31, 2, 5, 'Cold Brew Float', 'Vanilla bean ice cream and 18-hour cold brew.', '5.10', 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=800&q=80', 4, 1, '2026-04-24 01:09:00'),
(32, 2, 5, 'Mango Lassi Spritz', 'House yogurt lassi, sparkling water, cardamom.', '4.75', 'https://images.unsplash.com/photo-1546171753-97d7676e4602?auto=format&fit=crop&w=800&q=80', 5, 1, '2026-04-24 01:09:00'),
(33, 2, 23, 'Smoked Salmon Benedict', 'Brioche, hollandaise, capers, and everything spice.', '13.50', 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=800&q=80', 1, 1, '2026-04-24 01:09:00'),
(34, 2, 23, 'Ricotta Hotcakes', 'Blueberry compote, maple butter, and lemon zest.', '11.90', 'https://images.unsplash.com/photo-1506086679524-493c00c17acd?auto=format&fit=crop&w=800&q=80', 2, 1, '2026-04-24 01:09:00'),
(35, 2, 23, 'Shakshuka Skillet', 'Spiced tomato, feta, baked eggs, and grilled sourdough.', '12.25', 'https://images.unsplash.com/photo-1596797038530-2c107229654b?auto=format&fit=crop&w=800&q=80', 3, 1, '2026-04-24 01:09:00'),
(36, 2, 23, 'Avocado Toast Deluxe', 'Smashed avocado, poached eggs, chili crunch, radish.', '10.50', 'https://images.unsplash.com/photo-1541519227354-08fa5d50c44d?auto=format&fit=crop&w=800&q=80', 4, 1, '2026-04-24 01:09:00');

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `menu_code` varchar(40) NOT NULL,
  `description` text DEFAULT NULL,
  `menu_type` varchar(30) NOT NULL DEFAULT 'other',
  `logo_image` varchar(255) DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `banner_image_docked` varchar(255) DEFAULT NULL,
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `admin_id`, `name`, `menu_code`, `description`, `menu_type`, `logo_image`, `banner_image`, `banner_image_docked`, `views`, `created_at`) VALUES
(1, 1, 'Africa', 'eu0e6v0zk1', 'hotel', 'other', 'uploads/menus/20260413103030_02f11311.jpg', 'uploads/menus/20260413103030_21f7cb2c.jpg', NULL, 22, '2026-04-13 11:30:30'),
(2, 1, 'Demo Restaurant', 'demo2026', '', 'other', 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=400&h=400&q=85', 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=2000&q=85', 'uploads/menus/20260424012216_9f3ede58.webp', 647, '2026-04-13 11:34:02'),
(3, NULL, 'Sami Cafe', 'sami-cafe', 'Fast and testy food', 'cafe', 'uploads/menus/20260423170628_e902ac52.png', NULL, NULL, 9, '2026-04-23 18:06:28');

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
(2, 2, 'qrcodes/qr_demo2026.png', 'http://localhost:8080/menus/menu.php?id=demo2026', '2026-04-13 11:34:04'),
(3, 3, 'qrcodes/qr_sami-cafe.png', 'http://localhost:8080/Menus/menu.php?id=sami-cafe', '2026-04-23 18:06:30');

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
-- Indexes for table `admin_menu_assignments`
--
ALTER TABLE `admin_menu_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_admin_menu` (`admin_id`,`menu_id`),
  ADD KEY `fk_admin_menu_menu` (`menu_id`);

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
  ADD UNIQUE KEY `menu_code` (`menu_code`),
  ADD KEY `idx_menus_admin_id` (`admin_id`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_menu_assignments`
--
ALTER TABLE `admin_menu_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_menu_assignments`
--
ALTER TABLE `admin_menu_assignments`
  ADD CONSTRAINT `fk_admin_menu_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_admin_menu_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `fk_menus_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `fk_qr_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
