-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2026 at 06:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `email` varchar(254) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `STATUS` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `NAME`, `email`, `PASSWORD`, `STATUS`, `created_at`) VALUES
(2, 'Administrator', 'admin@ecommerce.com', '$2y$10$T2miL5DnhRcahU5Ur7Qxzulb0tfltu54kdKYEBDLgnvNhZrHIkep6', 'active', '2026-08-13 18:06:04');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`) VALUES
(1, 'Electronics', 'Smartphones, laptops, tablets, headphones, cameras, and other electronic devices.', 'Active', '2026-08-16 16:00:00'),
(2, 'Clothing', 'Men\'s, women\'s, and children\'s clothing including shirts, pants, dresses, and jackets.', 'Active', '2026-08-16 16:00:57'),
(3, 'Shoes', 'Sneakers, formal shoes, sandals, boots, and other footwear for men, women, and children.', 'Active', '2026-08-16 16:01:43'),
(4, 'Home & Kitchen', 'Furniture, kitchen appliances, cookware, home decorations, and household products.', 'Active', '2026-08-16 16:02:28'),
(5, 'Beauty & Personal Care', 'Skincare, haircare, makeup, perfumes, and personal grooming products.', 'Active', '2026-08-16 16:04:25'),
(6, 'Sports & Fitness', 'Sports equipment, gym accessories, fitness products, and outdoor activity gear.', 'Active', '2026-08-16 16:05:00'),
(7, 'Books', 'Educational books, novels, magazines, textbooks, and other reading materials.', 'Active', '2026-08-16 16:06:03'),
(8, 'Toys & Games', 'Children\'s toys, board games, puzzles, educational toys, and gaming accessories.', 'Active', '2026-08-16 16:06:29'),
(9, 'Jewelry & Accessories', 'Rings, watches, necklaces, bracelets, bags, wallets, and fashion accessories.', 'Active', '2026-08-16 16:07:19'),
(10, 'Grocery & Food', 'Packaged foods, beverages, snacks, cooking ingredients, and everyday grocery items.', 'Active', '2026-08-16 16:07:50');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `email` varchar(254) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `STATUS` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `STATUS` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `NAME`, `description`, `price`, `stock_quantity`, `image`, `STATUS`, `created_at`, `updated_at`) VALUES
(1, 1, 'Wireless Bluetooth Headphones', 'Over-ear wireless headphones with clear sound and long battery life.', 4500.00, 25, '6a887bdf64894.png', 'Active', '2026-08-16 19:29:10', '2026-08-21 16:25:03'),
(2, 1, 'Smart Watch Series 5', 'Smart watch with fitness tracking, heart-rate monitoring and notifications.', 7500.00, 15, '6a887bd2aafbb.png', 'Active', '2026-08-16 19:31:45', '2026-08-21 16:24:50'),
(3, 6, 'Stainless Steel Water Bottle', 'Reusable stainless steel bottle that keeps beverages cool for hours.', 1500.00, 45, '6a86bf6fb3375.png', 'Active', '2026-08-16 19:37:36', '2026-08-21 14:59:03'),
(5, 4, 'LED Desk Lamp', 'Adjustable LED desk lamp with multiple brightness levels for study and work.', 2200.00, 35, '../Images/6a8214a99d507.png', 'Active', '2026-08-16 19:51:05', '2026-08-16 21:32:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `email` varchar(254) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `address` varchar(254) NOT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `STATUS` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `NAME`, `email`, `phone`, `PASSWORD`, `address`, `city`, `country`, `STATUS`, `created_at`) VALUES
(2, 'Muhammad Abdullah', 'abcd@gmail.com', '03493519437', '$2y$10$9jN5COQmwGV3PG6su9vI1eT8tGgHZtfudBbx6yAdueiXCrmyHy/vW', 'LAHORE', 'Lahore', 'Pakistan', 'Active', '2026-08-26 14:19:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
