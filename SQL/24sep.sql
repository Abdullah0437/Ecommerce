-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 24, 2026 at 06:01 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
  `id` int(10) UNSIGNED NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `STATUS` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `NAME`, `email`, `PASSWORD`, `STATUS`, `created_at`) VALUES
(1, 'Abdullah', 'admin@gmail.com', '$2y$10$rcfsdXq9m9LbXWnEqvWaDeUDbks/ntRh.ecmLbwIA3ae09XFdJ6Am', 'Active', '2026-09-14 08:46:50');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`) VALUES
(1, 'Electronics', 'Products such as smartphones, laptops, tablets, headphones, cameras, and other electronic devices.', 'Active', '2026-09-14 08:57:15'),
(2, 'Fashion & Apparel', 'Clothing, shoes, accessories, jewelry, handbags, and fashion-related items for men, women, and children.', 'Active', '2026-09-14 09:19:02'),
(3, 'Home & Furniture', 'Furniture, home décor, kitchen appliances, bedding, lighting, and household essentials.', 'Active', '2026-09-14 09:19:52'),
(4, 'Health & Beauty', 'Skincare, cosmetics, personal care products, fragrances, supplements, and wellness items.', 'Active', '2026-09-14 09:20:49'),
(5, 'Sports & Outdoor', 'Fitness equipment, sports gear, outdoor adventure products, camping equipment, and activewear.', 'Active', '2026-09-14 09:21:21'),
(6, 'Grocery & Food', 'Everyday food items, beverages, snacks, and cooking essentials.', 'Active', '2026-09-14 12:24:27'),
(7, 'Pet Supplies', 'Food, toys, accessories, and healthcare products for pets.', 'Active', '2026-09-14 12:25:15'),
(8, 'Automotive', 'Vehicle accessories, maintenance tools, and automotive care products.', 'Active', '2026-09-14 12:25:43'),
(9, 'Toys & Games', 'Toys, puzzles, board games, and entertainment products for children and adults.', 'Active', '2026-09-14 12:26:17'),
(10, 'Books & Stationery', 'Books & Stationery\r\nBooks, notebooks, office supplies, and educational materials.', 'Active', '2026-09-14 12:26:40');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash on Delivery',
  `STATUS` enum('Pending','Confirmed','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `email`, `phone`, `address`, `city`, `country`, `subtotal`, `shipping`, `total`, `payment_method`, `STATUS`, `created_at`) VALUES
(1, 1, 'Muhammad Abdullah', 'abcd@gmail.com', '03493519437', 'house no 63, Goheer Town, Bahawalpur, Punjab, 63100', 'Bahawalpur', 'Pakistan', 175398.00, 200.00, 175598.00, 'Cash on Delivery', 'Delivered', '2026-09-22 09:05:15'),
(2, 1, 'Muhammad Abdullah', 'abcd@gmail.com', '03493519437', 'house no 63, Goheer Town,Bahawalpur, Bahawalpur, Punjab, 63100', 'Bahawalpur', 'Pakistan', 69999.00, 200.00, 70199.00, 'Cash on Delivery', 'Delivered', '2026-09-22 10:24:34'),
(3, 1, 'Abdullah Abdullah', 'abcd@gmail.com', '03493519437', 'house no 63, Goheer Town,Bahawalpur, Bahawalpur, Punjab, 63100', 'Bahawalpur', 'Pakistan', 518993.00, 200.00, 519193.00, 'Cash on Delivery', 'Delivered', '2026-09-22 18:27:50');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES
(1, 1, 3, 'Laptop', 85499.00, 2, 170998.00),
(2, 1, 2, 'T-Shirt', 2200.00, 2, 4400.00),
(3, 2, 6, 'Tablet', 69999.00, 1, 69999.00),
(4, 3, 3, 'Laptop', 85499.00, 6, 512994.00),
(5, 3, 5, 'Smartwatch', 5999.00, 1, 5999.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `NAME` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `STATUS` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `NAME`, `description`, `price`, `stock_quantity`, `image`, `STATUS`, `created_at`, `updated_at`) VALUES
(1, 1, 'iPhone 14 Pro', 'A portable electronic device used for communication, internet browsing, photography, and running applications.', 45000.00, 75, '6aa7eca194fa7.jpg', 'Active', '2026-09-14 09:29:31', '2026-09-14 12:46:25'),
(2, 2, 'T-Shirt', 'A comfortable casual garment made from cotton or other fabrics, worn as everyday clothing.', 2200.00, 33, '6aa7ec94d4f00.jpg', 'Active', '2026-09-14 09:32:10', '2026-09-22 09:05:15'),
(3, 1, 'Laptop', 'Portable computer for work and study.', 85499.00, 7, '6aa7ec81732ec.jpg', 'Active', '2026-09-14 12:28:09', '2026-09-22 18:27:50'),
(4, 1, 'Wireless Earbuds', 'Bluetooth audio device for music and calls.', 3500.00, 25, '6aa7f21922d6d.jpg', 'Active', '2026-09-14 13:06:06', '2026-09-14 13:09:45'),
(5, 1, 'Smartwatch', 'Wearable device for fitness and notifications.', 5999.00, 23, '6aa7f1fd8444e.jpg', 'Active', '2026-09-14 13:08:41', '2026-09-22 18:27:50'),
(6, 1, 'Tablet', 'Touchscreen device for browsing and media.', 69999.00, 14, '6aa7f722632c0.jpg', 'Active', '2026-09-14 13:11:49', '2026-09-22 10:24:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `STATUS` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `NAME`, `email`, `phone`, `PASSWORD`, `address`, `city`, `country`, `STATUS`, `created_at`) VALUES
(1, 'Abdullah', 'abcd@gmail.com', '03493519437', '$2y$10$DJzgBHrCybY2Hyf3Glmk9.CTZNSH1gO3rJ3iePYgcPnWOcAaXzYym', 'Goheer Town,Bahawalpur', 'Bahawalpur', 'Pakistan', 'Active', '2026-09-14 09:52:35');

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
  ADD UNIQUE KEY `NAME` (`name`);

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
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
