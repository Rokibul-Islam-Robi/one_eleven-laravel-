-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2026 at 05:48 AM
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
-- Database: `bherb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity` varchar(80) DEFAULT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `user_id`, `action`, `entity`, `entity_id`, `detail`, `ip`, `created_at`) VALUES
(1, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:24:26'),
(2, 1, 'logout', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:24:28'),
(3, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:25:48'),
(4, 1, 'doctor.create', 'doctor', 4, 'Dr. Test Qazi · DOC-T24T3F', '::1', '2026-10-17 09:25:48'),
(5, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:26:15'),
(6, 1, 'doctor.delete', 'doctor', 4, 'Dr. Test Qazi', '::1', '2026-10-17 09:26:18'),
(7, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:28:07'),
(8, 1, 'doctor.create', 'doctor', 5, 'Dr. Edit Test · DOC-8ELQLL', '::1', '2026-10-17 09:28:07'),
(9, 1, 'doctor.toggle', 'doctor', 5, 'Dr. Edit Test → inactive', '::1', '2026-10-17 09:28:09'),
(10, 1, 'doctor.delete', 'doctor', 5, 'Dr. Edit Test', '::1', '2026-10-17 09:28:09'),
(11, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:29:48'),
(12, 1, 'doctor.create', 'doctor', 6, 'Dr. Update Test · DOC-A9TK7W', '::1', '2026-10-17 09:29:48'),
(13, 1, 'doctor.update', 'doctor', 6, 'Dr. Update Test · DOC-UPD99', '::1', '2026-10-17 09:29:51'),
(14, 1, 'doctor.delete', 'doctor', 6, 'Dr. Update Test', '::1', '2026-10-17 09:29:51'),
(15, 3, 'login', 'user', 3, 'staff@rbspharma.com', '::1', '2026-10-17 09:30:25'),
(16, 2, 'login', 'user', 2, 'manager@rbspharma.com', '::1', '2026-10-17 09:30:25'),
(17, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:30:26'),
(18, 3, 'login', 'user', 3, 'staff@rbspharma.com', '::1', '2026-10-17 09:32:41'),
(19, 2, 'login', 'user', 2, 'manager@rbspharma.com', '::1', '2026-10-17 09:32:42'),
(20, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:32:42'),
(21, 2, 'login', 'user', 2, 'manager@rbspharma.com', '::1', '2026-10-17 09:33:42'),
(22, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:34:10'),
(23, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:35:05'),
(24, 1, 'content.update', 'content', 5, 'flash_bar', '::1', '2026-10-17 09:35:05'),
(25, 1, 'content.update', 'content', 5, 'flash_bar', '::1', '2026-10-17 09:35:05'),
(26, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:35:50'),
(27, 1, 'content.update', 'content', 1, 'hero_1', '::1', '2026-10-17 09:35:50'),
(28, 1, 'content.update', 'content', 1, 'hero_1', '::1', '2026-10-17 09:35:50'),
(29, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:36:42'),
(30, 1, 'content.update', 'content', 1, 'hero_1', '::1', '2026-10-17 09:36:42'),
(31, 1, 'content.update', 'content', 1, 'hero_1', '::1', '2026-10-17 09:36:44'),
(32, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:39:50'),
(33, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:52:10'),
(34, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:52:52'),
(35, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:54:25'),
(36, 1, 'order.update', 'order', 1, 'BH000001 → shipped', '::1', '2026-10-17 09:54:26'),
(37, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:55:04'),
(38, 1, 'order.update', 'order', 1, 'BH000001 → shipped', '::1', '2026-10-17 09:55:04'),
(39, 1, 'order.update', 'order', 1, 'BH000001 → delivered', '::1', '2026-10-17 09:55:05'),
(40, 1, 'order.update', 'order', 1, 'BH000001 → pending', '::1', '2026-10-17 09:55:08'),
(41, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:55:48'),
(42, 1, 'order.update', 'order', 1, 'BH000001 → processing', '::1', '2026-10-17 09:55:49'),
(43, 1, 'order.update', 'order', 1, 'BH000001 → processing', '::1', '2026-10-17 09:55:50'),
(44, 1, 'order.update', 'order', 1, 'BH000001 → processing', '::1', '2026-10-17 09:55:50'),
(45, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 09:57:30'),
(46, 1, 'order.update', 'order', 1, 'BH000001 → shipped', '::1', '2026-10-17 09:57:33'),
(47, 1, 'order.update', 'order', 1, 'BH000001 → shipped', '::1', '2026-10-17 09:57:35'),
(48, 3, 'login', 'user', 3, 'staff@rbspharma.com', '::1', '2026-10-17 09:57:36'),
(49, 3, 'order.update', 'order', 1, 'BH000001 → processing', '::1', '2026-10-17 09:57:36'),
(50, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 10:03:03'),
(51, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 10:08:35'),
(52, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 10:14:01'),
(53, 1, 'order.update', 'order', 1, 'BH000001 → shipped', '::1', '2026-10-17 10:14:01'),
(54, 1, 'order.update', 'order', 1, 'BH000001 → delivered', '::1', '2026-10-17 10:14:02'),
(55, 1, 'order.update', 'order', 1, 'BH000001 → cancelled', '::1', '2026-10-17 10:14:03'),
(56, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 10:14:45'),
(57, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-17 10:15:23'),
(58, 1, 'courier.create', 'courier', 5, 'AuditTest Courier', '::1', '2026-10-17 10:15:26'),
(59, 1, 'courier.delete', 'courier', 5, 'AuditTest Courier', '::1', '2026-10-17 10:15:28'),
(60, 3, 'login', 'user', 3, 'staff@rbspharma.com', '::1', '2026-10-17 10:15:28'),
(61, 1, 'login', 'user', 1, 'admin@rbspharma.com', '127.0.0.1', '2026-10-18 06:08:53'),
(62, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-20 04:19:50'),
(63, 2, 'login', 'user', 2, 'manager@rbspharma.com', '::1', '2026-10-20 06:01:26'),
(64, 2, 'logout', 'user', 2, 'manager@rbspharma.com', '::1', '2026-10-20 06:06:58'),
(65, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-20 06:07:03'),
(66, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-20 07:10:20'),
(67, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-21 07:36:33'),
(68, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-21 08:08:46'),
(69, 3, 'login', 'user', 3, 'staff@rbspharma.com', '::1', '2026-10-21 08:08:47'),
(70, 2, 'login', 'user', 2, 'manager@rbspharma.com', '::1', '2026-10-21 08:08:47'),
(71, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-21 08:25:16'),
(72, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-21 08:26:49'),
(73, 1, 'payout.paid', 'payout', 6, 'TRXPROOF001', '::1', '2026-10-21 08:26:49'),
(74, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-21 09:12:01'),
(75, 1, 'payout.paid', 'payout', 7, 'TRXLINK001', '::1', '2026-10-21 09:12:01'),
(76, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-21 09:15:48'),
(77, 1, 'payout.paid', 'payout', 8, 'TRXLINK002', '::1', '2026-10-21 09:15:49'),
(78, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-21 09:20:40'),
(79, 1, 'payout.paid', 'payout', 9, 'TRXFINAL', '::1', '2026-10-21 09:20:40'),
(80, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-21 09:24:44'),
(81, 1, 'payout.paid', 'payout', 10, 'TRXDBG', '::1', '2026-10-21 09:24:44'),
(82, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-21 09:28:28'),
(83, 1, 'payout.paid', 'payout', 11, 'TRXPV', '::1', '2026-10-21 09:28:29'),
(84, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-21 09:29:06'),
(85, 1, 'payout.paid', 'payout', 12, 'TRXCT', '::1', '2026-10-21 09:29:06'),
(86, 1, 'login', 'user', 1, 'admin@rbspharma.com', '::1', '2026-10-21 09:34:25');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`) VALUES
(1, 'Bherb Essentials', 'bherb-essentials'),
(2, 'Nature Labs', 'nature-labs'),
(3, 'Daily Balance', 'daily-balance'),
(4, 'Pure Wellness', 'pure-wellness'),
(5, 'Vital Roots', 'vital-roots'),
(6, 'Green Choice', 'green-choice'),
(2167, 'bHerb Gold Nutrition', 'bherb-gold-nutrition'),
(2168, 'Vitalife', 'vitalife'),
(2170, 'PureRoot Labs', 'pureroot-labs'),
(2172, 'HerbaCore', 'herbacore'),
(2177, 'SunPeak Organics', 'sunpeak-organics'),
(2191, 'NMN Foods', 'nmn-foods'),
(2192, 'NOW Foods', 'now-foods'),
(2195, 'Herb Pharm', 'herb-pharm'),
(2275, 'California Gold Nutrition', 'california-gold-nutrition'),
(2276, 'Sports Research', 'sports-research'),
(2277, 'Doctor\'s Best', 'doctor-s-best'),
(2278, 'NatureWise', 'naturewise'),
(2279, 'Jarrow Formulas', 'jarrow-formulas'),
(2280, 'Solgar', 'solgar'),
(2281, 'Enzymedica', 'enzymedica'),
(2282, 'KSM Research', 'ksm-research'),
(2283, 'Nature\'s Way', 'nature-s-way'),
(2284, 'Pure Encapsulations', 'pure-encapsulations'),
(2285, 'Life Extension', 'life-extension'),
(2286, 'TruSkin', 'truskin'),
(2287, 'Mario Badescu', 'mario-badescu'),
(2288, 'The Ordinary', 'the-ordinary'),
(2289, 'ArtNaturals', 'artnaturals'),
(2290, 'Neutrogena', 'neutrogena'),
(2291, 'COSRX', 'cosrx'),
(2292, 'Briogeo', 'briogeo'),
(2293, 'Bio-Oil', 'bio-oil'),
(2294, 'CeraVe', 'cerave'),
(2295, 'SheaMoisture', 'sheamoisture'),
(2296, 'Dr. Bronner\'s', 'dr-bronner-s'),
(2297, 'Tom\'s of Maine', 'tom-s-of-maine'),
(2298, 'Optimum Nutrition', 'optimum-nutrition'),
(2299, 'MuscleTech', 'muscletech'),
(2300, 'Scivation', 'scivation'),
(2301, 'Gridiron', 'gridiron'),
(2302, 'Naked Nutrition', 'naked-nutrition'),
(2303, 'Navitas Organics', 'navitas-organics'),
(2304, 'Nature\'s Path', 'nature-s-path'),
(2305, 'Green Giant', 'green-giant'),
(2306, 'Nutiva', 'nutiva'),
(2307, 'Bob\'s Red Mill', 'bob-s-red-mill'),
(2308, 'California Olive Ranch', 'california-olive-ranch'),
(2309, 'L\'il Critters', 'l-il-critters'),
(2310, 'BioGaia', 'biogaia'),
(2311, 'Culturelle', 'culturelle'),
(2312, 'Nordic Naturals', 'nordic-naturals'),
(2313, 'Plum Organics', 'plum-organics'),
(2314, 'VetriScience', 'vetriscience'),
(2315, 'Nutramax', 'nutramax'),
(2316, 'Zesty Paws', 'zesty-paws'),
(2317, 'Pura', 'pura'),
(2318, 'Seventh Generation', 'seventh-generation'),
(2319, 'Grab Green', 'grab-green'),
(2320, 'Better Life', 'better-life'),
(2321, 'Vitruvi', 'vitruvi'),
(2322, 'doTERRA', 'doterra'),
(2323, 'Poo-Pourri', 'poo-pourri'),
(2324, 'Ello', 'ello'),
(2325, 'Full Circle', 'full-circle'),
(2326, 'Glasslock', 'glasslock');

-- --------------------------------------------------------

--
-- Table structure for table `catalog_stock`
--

CREATE TABLE `catalog_stock` (
  `json_id` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `requires_prescription` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `catalog_stock`
--

INSERT INTO `catalog_stock` (`json_id`, `stock`, `requires_prescription`, `updated_at`) VALUES
(1, 50, 0, '2026-10-20 06:02:40'),
(2, 53, 0, '2026-10-20 06:00:31'),
(3, 53, 0, '2026-10-18 11:28:14'),
(4, 50, 0, '2026-10-20 07:47:12'),
(5, 51, 0, '2026-10-20 06:02:40'),
(6, 50, 0, '2026-10-17 10:59:01'),
(7, 52, 0, '2026-10-18 06:06:02'),
(8, 51, 0, '2026-10-20 04:19:09'),
(9, 56, 0, '2026-10-18 06:06:43'),
(10, 50, 0, '2026-10-17 10:59:01'),
(11, 51, 0, '2026-10-18 06:07:07'),
(12, 50, 0, '2026-10-17 10:59:01'),
(13, 52, 0, '2026-10-20 06:03:14'),
(14, 50, 0, '2026-10-17 10:59:01'),
(15, 51, 0, '2026-10-18 06:08:21'),
(16, 50, 0, '2026-10-17 10:59:01'),
(17, 50, 0, '2026-10-17 10:59:01'),
(18, 50, 0, '2026-10-17 10:59:01'),
(19, 50, 0, '2026-10-17 10:59:01'),
(20, 50, 0, '2026-10-17 10:59:01'),
(21, 50, 0, '2026-10-17 10:59:01'),
(22, 50, 0, '2026-10-17 10:59:01'),
(23, 50, 0, '2026-10-17 10:59:01'),
(24, 50, 0, '2026-10-17 10:59:01'),
(25, 50, 0, '2026-10-17 10:59:01'),
(26, 50, 0, '2026-10-17 10:59:01'),
(27, 50, 0, '2026-10-17 10:59:01'),
(28, 50, 0, '2026-10-17 10:59:01'),
(29, 50, 0, '2026-10-17 10:59:01'),
(30, 50, 0, '2026-10-17 10:59:01'),
(31, 50, 0, '2026-10-17 10:59:01'),
(32, 50, 0, '2026-10-17 10:59:01'),
(33, 50, 0, '2026-10-17 10:59:01'),
(34, 50, 0, '2026-10-17 10:59:01'),
(35, 50, 0, '2026-10-17 10:59:01'),
(36, 53, 0, '2026-10-20 04:59:55'),
(37, 50, 0, '2026-10-17 10:59:01'),
(38, 50, 0, '2026-10-17 10:59:01'),
(39, 50, 0, '2026-10-17 10:59:01'),
(40, 50, 0, '2026-10-17 10:59:01'),
(41, 50, 0, '2026-10-17 10:59:01'),
(42, 50, 0, '2026-10-17 10:59:01'),
(43, 50, 0, '2026-10-17 10:59:01'),
(44, 50, 0, '2026-10-17 10:59:01'),
(45, 50, 0, '2026-10-17 10:59:01'),
(46, 50, 0, '2026-10-17 10:59:01'),
(47, 50, 0, '2026-10-17 10:59:01'),
(48, 50, 0, '2026-10-17 10:59:01'),
(49, 50, 0, '2026-10-17 10:59:01'),
(50, 50, 0, '2026-10-17 10:59:01'),
(51, 50, 0, '2026-10-17 10:59:01'),
(52, 50, 0, '2026-10-17 10:59:01'),
(53, 50, 0, '2026-10-17 10:59:01'),
(54, 50, 0, '2026-10-17 10:59:01'),
(55, 50, 0, '2026-10-17 10:59:01'),
(56, 50, 0, '2026-10-17 10:59:01'),
(57, 51, 0, '2026-10-18 11:17:39'),
(58, 50, 0, '2026-10-17 10:59:01'),
(59, 50, 0, '2026-10-17 10:59:01'),
(60, 50, 0, '2026-10-17 10:59:01'),
(61, 50, 0, '2026-10-17 10:59:01'),
(62, 50, 0, '2026-10-17 10:59:01'),
(63, 50, 0, '2026-10-17 10:59:01'),
(64, 50, 0, '2026-10-17 10:59:01');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `icon` varchar(20) DEFAULT '✣'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Supplements', 'supplements', 'i-cat-supplements'),
(2, 'Sports', 'sports', 'i-cat-sports'),
(3, 'Bath & Personal Care', 'bath-personal-care', 'i-cat-bath'),
(4, 'Beauty', 'beauty', 'i-cat-beauty'),
(5, 'Grocery', 'grocery', 'i-cat-grocery'),
(6, 'Home', 'home', 'i-cat-home'),
(7, 'Baby & Kids', 'kids-babies', 'i-cat-baby'),
(8, 'Pets', 'pets', 'i-cat-pets'),
(521, 'Vitamins & Minerals', 'daily-vitamins', '✣'),
(522, 'Immune Support', 'immune-support', '✣'),
(523, 'Herbs & Botanicals', 'herbs-botanicals', '✣'),
(524, 'Omega & Fish Oil', 'omega-dha', '✣'),
(525, 'Digestive Support', 'digestive-support', '✣'),
(526, 'Amino Acids', 'amino-acids', '✣'),
(527, 'Bone & Joint', 'bone-joint', '✣'),
(528, 'Sleep & Stress', 'sleep-stress', '✣'),
(529, 'Heart & Cardiovascular', 'heart-health', '✣'),
(530, 'Weight Management', 'weight-management', '✣'),
(531, 'Protein & Performance', 'plant-protein', '✣'),
(532, 'Kids Vitamins', 'kids-nutrition', '✣'),
(533, 'Skincare', 'skincare', '✣'),
(534, 'K-Beauty', 'k-beauty', '✣'),
(535, 'Sun Care', 'sun-care', '✣'),
(536, 'Makeup', 'makeup', '✣'),
(537, 'Serums & Oils', 'serums-oils', '✣'),
(538, 'Face Masks', 'face-masks', '✣'),
(539, 'Beauty Tools', 'beauty-tools', '✣'),
(540, 'Fragrance', 'fragrance', '✣'),
(541, 'Pre-Workout', 'pre-workout', '✣'),
(542, 'Creatine', 'creatine', '✣'),
(543, 'Recovery & Glutamine', 'recovery-glutamine', '✣'),
(544, 'BCAAs & EAAs', 'bcaas', '✣'),
(545, 'Endurance & Energy', 'endurance', '✣'),
(546, 'Seeds & Greens', 'seeds-greens', '✣'),
(547, 'Oats & Cereal', 'oats', '✣'),
(548, 'Cooking Oils', 'cooking-oils', '✣'),
(549, 'Snacks', 'snacks', '✣'),
(550, 'Beverages', 'beverages', '✣'),
(551, 'Superfoods', 'superfoods', '✣'),
(552, 'Pantry Staples', 'pantry', '✣'),
(553, 'Honey & Sweeteners', 'sweeteners', '✣'),
(554, 'Shampoo & Conditioner', 'shampoo-conditioner', '✣'),
(555, 'Body Wash', 'body-wash', '✣'),
(556, 'Oral Care', 'toothpaste', '✣'),
(557, 'Hair Treatments', 'hair-treatments', '✣'),
(558, 'Hand Wash', 'hand-wash', '✣'),
(559, 'Deodorants', 'deodorants', '✣'),
(560, 'Shaving Care', 'shaving', '✣'),
(561, 'Soap Bars', 'soap-bars', '✣'),
(562, 'All-Purpose Cleaners', 'all-purpose', '✣'),
(563, 'Laundry', 'laundry', '✣'),
(564, 'Dishwashing', 'dishwashing', '✣'),
(565, 'Bathroom Cleaners', 'bathroom-cleaners', '✣'),
(566, 'Air Care', 'air-care', '✣'),
(567, 'Essential Oils', 'essential-oils', '✣'),
(568, 'Diffusers', 'diffusers', '✣'),
(569, 'Kitchen Storage', 'kitchen-storage', '✣'),
(570, 'Water Bottles', 'water-bottles', '✣'),
(571, 'Dish Tools', 'dish-tools', '✣'),
(572, 'Baby Bath & Skin', 'baby-bath', '✣'),
(573, 'Feeding', 'feeding', '✣'),
(574, 'Diapers & Wipes', 'diapers-wipes', '✣'),
(575, 'Baby Care', 'baby-care', '✣'),
(576, 'Kids Snacks', 'kids-snacks', '✣'),
(577, 'Pet Vitamins', 'pet-vitamins', '✣'),
(578, 'Pet Probiotics', 'pet-probiotics', '✣'),
(579, 'Flea & Tick', 'flea-tick', '✣'),
(580, 'Grooming', 'pet-grooming', '✣'),
(581, 'Treats', 'pet-treats', '✣'),
(582, 'Joint Care', 'pet-joint', '✣'),
(583, 'Toners', 'toners', '✣'),
(584, 'Bath', 'bath', '✣'),
(585, 'Kids', 'kids', '✣');

-- --------------------------------------------------------

--
-- Table structure for table `commission_rules`
--

CREATE TABLE `commission_rules` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `scope` enum('global','category','seller','product') NOT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `rate` decimal(5,2) NOT NULL,
  `effective_from` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `effective_to` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code` varchar(50) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `min_order` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_uses` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `used_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1,
  `doctor_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_percent`, `discount_type`, `min_order`, `max_uses`, `used_count`, `starts_at`, `ends_at`, `created_at`, `active`, `doctor_id`, `description`) VALUES
(1, 'BHERB20', 20.00, 'percent', 0.00, 0, 1, NULL, NULL, '2026-10-17 08:58:17', 1, NULL, NULL),
(2, 'WELCOME10', 10.00, 'percent', 0.00, 0, 1, NULL, NULL, '2026-10-17 08:58:17', 1, NULL, NULL),
(3, 'DOCRAHIM10', 10.00, 'percent', 0.00, 0, 0, '2026-09-17 00:00:00', NULL, '2026-10-17 09:01:58', 1, 1, 'Reference discount for patients of Dr. Rahim Uddin'),
(4, 'DOCFARH8', 8.00, 'percent', 0.00, 0, 0, '2026-09-17 00:00:00', NULL, '2026-10-17 09:01:58', 1, 2, 'Reference discount for patients of Dr. Farhana Chowdhury'),
(5, 'DOKAMAL5', 5.00, 'percent', 0.00, 0, 0, '2026-09-17 00:00:00', NULL, '2026-10-17 09:01:58', 0, 3, 'Reference discount for patients of Dr. Kamal Ahmed'),
(6, 'TEST10', 10.00, 'percent', 0.00, 0, 0, NULL, NULL, '2026-10-17 10:48:10', 1, NULL, 'E2E test coupon');

-- --------------------------------------------------------

--
-- Table structure for table `courier_methods`
--

CREATE TABLE `courier_methods` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cod_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `free_delivery_min` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estimated_days` varchar(40) DEFAULT NULL,
  `contact_phone` varchar(40) DEFAULT NULL,
  `tracking_url` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courier_methods`
--

INSERT INTO `courier_methods` (`id`, `name`, `code`, `fee`, `cod_fee`, `free_delivery_min`, `estimated_days`, `contact_phone`, `tracking_url`, `is_default`, `status`, `sort_order`, `created_at`) VALUES
(1, 'RedX', 'redx', 60.00, 10.00, 1000.00, '2–3 days', '09612345678', 'https://redx.com.bd/track?track={code}', 1, 'active', 1, '2026-10-17 09:01:58'),
(2, 'Pathao', 'pathao', 70.00, 15.00, 1200.00, '1–2 days', '09610000001', 'https://pathao.com/bd/tracking?track={code}', 0, 'active', 2, '2026-10-17 09:01:58'),
(3, 'SA Paribahan', 'saparibahan', 55.00, 10.00, 900.00, '3–5 days', '09610000002', '', 0, 'active', 3, '2026-10-17 09:01:58'),
(4, 'Steadfast Courier', 'steadfast', 65.00, 12.00, 1100.00, '2–4 days', '09610000003', '', 0, 'inactive', 4, '2026-10-17 09:01:58');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `full_name` varchar(190) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `full_name`, `email`, `phone`, `password_hash`, `created_at`) VALUES
(1, 'Rahim Uddin', 'rahim.test@example.com', NULL, '$2y$10$4nRObVpzSl5wsE51NNq11eiXjqPI4YP5Ce1DydevWIGEoNkl/d7iK', '2026-10-18 09:20:27'),
(2, 'Nusrat Jahan', 'nusrat@example.com', NULL, '$2y$10$6d3bQNFSykx/TQUcAIKdP.w28G1x1ayUEqVPa5524Mb1/AFQhOx3K', '2026-10-18 09:22:05'),
(3, 'Sadman', NULL, '+01970010098', '$2y$10$1Xur/voyHgnyqiZGMmjWKuEg47JBN/iKXuNX.90H9eCdkDFwM60ta', '2026-10-18 10:42:53'),
(4, 'Munna', 'munna020@gmail.com', NULL, '$2y$10$fWq5IrZW/IXWE1GFDKy57OgG7oQVLmVhgPXKsCYzoMmMiHARqWPYW', '2026-10-20 04:44:21');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(150) NOT NULL,
  `specialty` varchar(120) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `alternate_phone` varchar(40) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `registration_number` varchar(80) DEFAULT NULL,
  `registration_council` varchar(120) DEFAULT NULL,
  `registration_verified_at` date DEFAULT NULL,
  `verification_status` enum('unverified','pending','verified','rejected') NOT NULL DEFAULT 'unverified',
  `reference_code` varchar(20) NOT NULL,
  `reference_discount` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `name`, `specialty`, `email`, `phone`, `alternate_phone`, `address`, `city`, `registration_number`, `registration_council`, `registration_verified_at`, `verification_status`, `reference_code`, `reference_discount`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Dr. Rahim Uddin', 'Cardiology', 'rahim.uddin@clinicbd.com', '+8801711223344', '+8801711223345', 'House 12, Road 5, Dhanmondi', 'Dhaka', 'BMDC-A-45211', 'Bangladesh Medical & Dental Council', '2026-01-12', 'verified', 'DOC-RAHIM1', 10.00, 'active', 'Key partner for cardiology referrals.', 1, '2026-10-17 09:01:58', NULL),
(2, 'Dr. Farhana Chowdhury', 'Gynecology', 'farhana.c@cityhospital.com', '+8801822334455', NULL, 'Flat B4, Uttara Sector 7', 'Dhaka', 'BMDC-B-77881', 'Bangladesh Medical & Dental Council', NULL, 'pending', 'DOC-FARHAN', 8.00, 'active', 'Awaiting BMDC certificate scan.', 1, '2026-10-17 09:01:58', NULL),
(3, 'Dr. Kamal Ahmed', 'General Medicine', 'kamal.ahmed@ruralcare.org', '+8801933445566', NULL, 'Pouro Bazar Road, Bogura', 'Bogura', 'BMDC-C-10293', 'Bangladesh Medical & Dental Council', NULL, 'unverified', 'DOC-KAMAL1', 5.00, 'inactive', 'On pause until license renewal.', 1, '2026-10-17 09:01:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_referrals`
--

CREATE TABLE `doctor_referrals` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `doctor_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `order_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','credited','cancelled') NOT NULL DEFAULT 'pending',
  `referred_at` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_referrals`
--

INSERT INTO `doctor_referrals` (`id`, `doctor_id`, `order_id`, `customer_id`, `coupon_code`, `customer_name`, `order_amount`, `discount_amount`, `credit_amount`, `status`, `referred_at`, `notes`, `created_at`) VALUES
(1, 1, 1, 4, 'DOCRAHIM10', 'Nusrat Jahan', 12.06, 1.21, 1.21, 'credited', '2026-10-12', 'Referred for immune-support order BH000001.', '2026-10-17 09:01:58'),
(2, 1, NULL, NULL, 'DOCRAHIM10', 'Walk-in patient (offline sale)', 32.50, 3.25, 3.25, 'pending', '2026-09-28', 'Manual offline referral recorded by staff.', '2026-10-17 09:01:58'),
(3, 2, NULL, NULL, 'DOCFARH8', 'Ayesha Siddika', 45.00, 3.60, 3.60, 'credited', '2026-10-05', 'Prenatal supplement package.', '2026-10-17 09:01:58'),
(4, 3, NULL, NULL, 'DOKAMAL5', 'Test entry — cancelled', 20.00, 1.00, 0.00, 'cancelled', '2026-08-15', 'Coupon was inactive; referral cancelled.', '2026-10-17 09:01:58');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_batches`
--

CREATE TABLE `inventory_batches` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` int(11) NOT NULL,
  `batch_number` varchar(80) NOT NULL,
  `qty_received` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `qty_remaining` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `supplier` varchar(120) DEFAULT NULL,
  `received_at` date DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_batches`
--

INSERT INTO `inventory_batches` (`id`, `product_id`, `batch_number`, `qty_received`, `qty_remaining`, `cost_price`, `expiry_date`, `supplier`, `received_at`, `notes`, `created_at`) VALUES
(1, 1001, 'B-2401-A', 120, 40, 4.10, '2026-09-17', 'MedSource Ltd.', '2025-09-12', 'Expired lot — quarantine shelf.', '2026-10-17 09:01:58'),
(2, 1002, 'B-2402-B', 150, 90, 3.85, '2026-11-06', 'PharmaLink', '2025-12-21', 'Expiring within 30 days — prioritise dispatch.', '2026-10-17 09:01:58'),
(3, 1003, 'B-2403-C', 200, 200, 4.90, '2027-06-14', 'MedSource Ltd.', '2026-06-19', 'Fresh stock.', '2026-10-17 09:01:58');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` varchar(190) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip`, `success`, `created_at`) VALUES
(1, 'admin@rbspharma.com', '::1', 0, '2026-10-17 09:24:09'),
(2, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:24:26'),
(3, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:25:48'),
(4, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:26:15'),
(5, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:28:07'),
(6, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:29:48'),
(7, 'staff@rbspharma.com', '::1', 1, '2026-10-17 09:30:25'),
(8, 'manager@rbspharma.com', '::1', 1, '2026-10-17 09:30:25'),
(9, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:30:26'),
(10, 'staff@rbspharma.com', '::1', 1, '2026-10-17 09:32:41'),
(11, 'manager@rbspharma.com', '::1', 1, '2026-10-17 09:32:42'),
(12, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:32:42'),
(13, 'manager@rbspharma.com', '::1', 1, '2026-10-17 09:33:42'),
(14, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:34:10'),
(15, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:35:05'),
(16, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:35:50'),
(17, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:36:42'),
(18, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:39:50'),
(19, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:52:10'),
(20, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:52:52'),
(21, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:54:25'),
(22, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:55:04'),
(23, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:55:48'),
(24, 'admin@rbspharma.com', '::1', 1, '2026-10-17 09:57:30'),
(25, 'staff@rbspharma.com', '::1', 1, '2026-10-17 09:57:36'),
(26, 'admin@rbspharma.com', '::1', 1, '2026-10-17 10:03:03'),
(27, 'admin@rbspharma.com', '::1', 1, '2026-10-17 10:08:35'),
(28, 'admin@rbspharma.com', '::1', 1, '2026-10-17 10:14:01'),
(29, 'admin@rbspharma.com', '::1', 1, '2026-10-17 10:14:45'),
(30, 'admin@rbspharma.com', '::1', 1, '2026-10-17 10:15:23'),
(31, 'staff@rbspharma.com', '::1', 1, '2026-10-17 10:15:28'),
(32, 'admin@rbspharma.com', '127.0.0.1', 1, '2026-10-18 06:08:53'),
(33, 'admin@rbspharma.com', '::1', 1, '2026-10-20 04:19:50'),
(34, 'manager@rbspharma.com', '::1', 1, '2026-10-20 06:01:26'),
(35, 'admin@rbspharma.com', '::1', 1, '2026-10-20 06:07:03'),
(36, 'admin@rbspharma.com', '::1', 1, '2026-10-20 07:10:20'),
(37, 'admin@rbspharma.com', '::1', 1, '2026-10-21 07:36:33'),
(38, 'admin@rbspharma.com', '::1', 1, '2026-10-21 08:08:46'),
(39, 'staff@rbspharma.com', '::1', 1, '2026-10-21 08:08:47'),
(40, 'manager@rbspharma.com', '::1', 1, '2026-10-21 08:08:47'),
(41, 'admin@rbspharma.com', '::1', 1, '2026-10-21 08:25:16'),
(42, 'admin@rbspharma.com', '::1', 1, '2026-10-21 08:26:49'),
(43, 'admin@rbspharma.com', '::1', 1, '2026-10-21 09:12:01'),
(44, 'admin@rbspharma.com', '::1', 1, '2026-10-21 09:15:48'),
(45, 'admin@rbspharma.com', '::1', 1, '2026-10-21 09:20:40'),
(46, 'admin@rbspharma.com', '::1', 1, '2026-10-21 09:24:44'),
(47, 'admin@rbspharma.com', '::1', 1, '2026-10-21 09:28:28'),
(48, 'admin@rbspharma.com', '::1', 1, '2026-10-21 09:29:06'),
(49, 'ahmedsadman2001@gmail.com', '::1', 0, '2026-10-21 09:34:21'),
(50, 'admin@rbspharma.com', '::1', 1, '2026-10-21 09:34:25');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_number` varchar(40) DEFAULT NULL,
  `idempotency_key` varchar(64) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cod_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','awaiting_prescription','shipped','delivered','failed_delivery','cancelled','return_requested','returned') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(40) DEFAULT 'Cash on Delivery',
  `payment_status` enum('unpaid','pending','paid','failed','refunded') NOT NULL DEFAULT 'unpaid',
  `val_id` varchar(100) DEFAULT NULL,
  `bank_tran_id` varchar(100) DEFAULT NULL,
  `card_type` varchar(40) DEFAULT NULL,
  `demo_payment` tinyint(1) NOT NULL DEFAULT 0,
  `coupon_id` int(11) DEFAULT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `requires_prescription` tinyint(1) NOT NULL DEFAULT 0,
  `prescription_id` int(10) UNSIGNED DEFAULT NULL,
  `courier_id` int(10) UNSIGNED DEFAULT NULL,
  `courier_code` varchar(50) DEFAULT NULL,
  `tracking_code` varchar(80) DEFAULT NULL,
  `shipping_name` varchar(120) NOT NULL,
  `shipping_phone` varchar(40) NOT NULL,
  `customer_email` varchar(190) DEFAULT NULL,
  `customer_ip` varchar(45) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `shipping_area` varchar(120) DEFAULT NULL,
  `shipping_postal` varchar(20) DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `failed_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `idempotency_key`, `user_id`, `subtotal`, `shipping`, `cod_fee`, `total`, `status`, `payment_method`, `payment_status`, `val_id`, `bank_tran_id`, `card_type`, `demo_payment`, `coupon_id`, `coupon_code`, `discount_amount`, `requires_prescription`, `prescription_id`, `courier_id`, `courier_code`, `tracking_code`, `shipping_name`, `shipping_phone`, `customer_email`, `customer_ip`, `shipping_address`, `shipping_area`, `shipping_postal`, `shipped_at`, `delivered_at`, `admin_notes`, `failed_reason`, `created_at`, `paid_at`) VALUES
(1, 'BH000001', NULL, 4, 12.06, 60.00, 0.00, 72.06, 'processing', 'Cash on Delivery', 'unpaid', NULL, NULL, NULL, 0, NULL, NULL, 0.00, 0, NULL, 1, NULL, 'RX-TRK-998234', 'Nusrat Jahan', '+8801812345678', NULL, NULL, 'House 45, Flat 3B, Mirpur 10, Dhaka 1216', NULL, NULL, NULL, NULL, 'Demo order created by seed script.', NULL, '2026-10-12 05:20:00', NULL),
(9, 'BH261018C4558B', 'e2fe6173719bf81770da554cb94f5096cb39b564', NULL, 1490.00, 0.00, 10.00, 1500.00, 'pending', 'cash_on_delivery', 'unpaid', NULL, NULL, NULL, 0, NULL, NULL, 0.00, 0, NULL, 1, 'redx', NULL, 'Nusrat Jahan', '01798765432', 'n@example.com', '', 'House 12 Road 5 Dhanmondi', 'Dhanmondi', '1209', NULL, NULL, NULL, NULL, '2026-10-18 06:04:33', NULL),
(10, 'BH26101897A60F', '09141fcae4da3e8d88b36004ab6d7a35ff2d61c5', NULL, 1390.00, 0.00, 15.00, 1266.00, 'pending', 'cash_on_delivery', 'unpaid', NULL, NULL, NULL, 0, 2, 'WELCOME10', 139.00, 0, NULL, 2, 'pathao', NULL, 'Test Customer', '01811111111', 't@e.com', '127.0.0.1', 'House 1 Road 1 Mirpur', 'Mirpur, Dhaka', '1216', NULL, NULL, NULL, NULL, '2026-10-18 06:04:54', NULL),
(11, 'BH26101834E255', '317927298082a560905ae5812fb10aead75cac09', NULL, 990.00, 60.00, 0.00, 1050.00, 'pending', 'sslcommerz', 'unpaid', NULL, NULL, NULL, 0, NULL, NULL, 0.00, 0, NULL, 1, 'redx', NULL, 'Pay Test', '01922222222', 'p@e.com', '127.0.0.1', 'House 2 Gulshan 1', 'Gulshan, Dhaka', '1212', NULL, NULL, NULL, NULL, '2026-10-18 06:05:13', NULL),
(12, 'BH26101827AA78', '65f3b464da96ad6f21d6472a83f378e6a9cf1027', NULL, 990.00, 60.00, 0.00, 1050.00, 'processing', 'sslcommerz', 'paid', 'DEMO2610180806024A697F', 'DEMO-BANK-261018080602', 'VISA', 1, NULL, NULL, 0.00, 0, NULL, 1, 'redx', NULL, 'Pay Test', '01922222222', 'p@e.com', '127.0.0.1', 'House 2 Gulshan 1', 'Gulshan, Dhaka', '1212', NULL, NULL, NULL, NULL, '2026-10-18 06:06:02', NULL),
(13, 'BH261018F0B781', 'ad1f13dc03a65a02122dd634a3473ad297cb94fc', NULL, 1150.00, 70.00, 0.00, 1220.00, 'pending', 'sslcommerz', 'failed', NULL, NULL, NULL, 0, NULL, NULL, 0.00, 0, NULL, 2, 'pathao', NULL, 'Fail Test', '01533333333', 'f@e.com', '127.0.0.1', 'House 3 Uttara Sector 7', 'Uttara, Dhaka', '1230', NULL, NULL, NULL, 'Customer redirected from fail_url', '2026-10-18 06:06:41', NULL),
(14, 'BH2610182EB320', '8032b2697056129d926cd50c1902d4324f853d38', NULL, 2300.00, 0.00, 0.00, 2300.00, 'pending', 'sslcommerz', 'failed', NULL, NULL, NULL, 0, NULL, NULL, 0.00, 0, NULL, 2, 'pathao', NULL, 'Fail Test', '01533333333', 'f@e.com', '127.0.0.1', 'House 3 Uttara Sector 7', 'Uttara, Dhaka', '1230', NULL, NULL, NULL, 'Customer cancelled at gateway', '2026-10-18 06:06:43', NULL),
(15, 'BH261018FAAB22', '77425e62313c2c0fb3a59e4ac094b6bc237e32fe', NULL, 1890.00, 0.00, 0.00, 1890.00, 'processing', 'sslcommerz', 'paid', 'DEMO261018120707611119', '', '', 1, NULL, NULL, 0.00, 0, NULL, 1, 'redx', NULL, 'IPN Test', '01644444444', 'i@e.com', '127.0.0.1', 'House 4 Banani', 'Banani, Dhaka', '1213', NULL, NULL, NULL, NULL, '2026-10-18 06:07:07', NULL),
(17, 'BH2610183CAE08', '5ed148738eedad684a7c2cf2962973ba08169baf', NULL, 1090.00, 70.00, 15.00, 1175.00, 'pending', 'cash_on_delivery', 'unpaid', NULL, NULL, NULL, 0, NULL, NULL, 0.00, 0, 4, 2, 'pathao', NULL, 'NoJS Test', '01666666666', 'nojs@e.com', '127.0.0.1', 'House 6 Badda Link Road', 'Badda, Dhaka', '1229', NULL, NULL, NULL, NULL, '2026-10-18 06:08:21', NULL),
(18, 'BH261018A7F27C', '46e8abc498b94cf7fe4fac002a855059e466fc6c', NULL, 3840.00, 0.00, 0.00, 3840.00, 'processing', 'sslcommerz', 'paid', 'DEMO2610180822558D9C0A', 'DEMO-BANK-261018082255', 'VISA', 1, NULL, NULL, 0.00, 0, NULL, 2, 'pathao', NULL, 'rakib', '01710878287', '', '::1', 'Mirpur Dhaka', 'Mirpir,Dhaka', '', NULL, NULL, NULL, NULL, '2026-10-18 06:22:54', NULL),
(19, 'BH261018F153A7', '82f6690e9f5b80cee4ae897712d495cc51db3213', NULL, 6730.00, 0.00, 0.00, 5384.00, 'processing', 'sslcommerz', 'paid', 'DEMO2610181317398AF470', 'DEMO-BANK-261018131739', 'VISA', 1, 1, 'BHERB20', 1346.00, 0, NULL, 2, 'pathao', NULL, 'Arman', '01711147903', '', '::1', '8 no word,Bhola Sadar', 'Bhola', NULL, NULL, NULL, NULL, NULL, '2026-10-18 11:17:39', NULL),
(20, 'BH26101829C193', '97d46147f26be8a3daeb366a8ee8813e382a8f7e', NULL, 1490.00, 0.00, 0.00, 1490.00, 'processing', 'sslcommerz', 'paid', 'DEMO261018132814CACB0C', 'DEMO-BANK-261018132814', 'VISA', 1, NULL, NULL, 0.00, 0, NULL, 2, 'pathao', NULL, 'ratul', '01710878287', '', '::1', 'mirpur1,Dhaka', 'Dhaka', '', NULL, NULL, NULL, NULL, '2026-10-18 11:28:14', NULL),
(21, 'BH261020C3F3FF', '1ff6e568edeb199ea55eb682e2bfc3152816c45e', NULL, 3640.00, 0.00, 0.00, 3640.00, 'processing', 'sslcommerz', 'paid', 'DEMO2610200619097FAB82', 'DEMO-BANK-261020061909', 'VISA', 1, NULL, NULL, 0.00, 0, NULL, 3, 'saparibahan', NULL, 'Sadman', '01970010098', '', '::1', 'house 493,road 07,f block,Bashundhara', 'Dhaka', NULL, NULL, NULL, NULL, NULL, '2026-10-20 04:19:09', NULL),
(22, 'BH261020A4F011', 'a60afc7b4a57e2e714625358672fdae1596b18f7', NULL, 3790.00, 0.00, 0.00, 3790.00, 'pending', 'sslcommerz', 'failed', NULL, NULL, NULL, 0, NULL, NULL, 0.00, 0, NULL, 1, 'redx', NULL, 'kanchon', '01752381557', '', '::1', 'house 289,road 07,Mirpur', 'Dhaka', NULL, NULL, NULL, NULL, 'Customer cancelled at gateway', '2026-10-20 04:59:48', NULL),
(23, 'BH261020387929', 'f34ce8ce9489ae4d8fe65aa0cbd58e61f31e9b5f', NULL, 3790.00, 0.00, 10.00, 3800.00, 'pending', 'cash_on_delivery', 'unpaid', NULL, NULL, NULL, 0, NULL, NULL, 0.00, 0, NULL, 1, 'redx', NULL, 'kanchon', '01752381557', '', '::1', 'house 289,road 07,Mirpur', 'Dhaka', NULL, NULL, NULL, NULL, NULL, '2026-10-20 04:59:55', NULL),
(62, 'BH2610200770D3', '0b906789f7ca6435e16048e479b8679ca7ba59b9', NULL, 1450.00, 0.00, 0.00, 1450.00, 'processing', 'bkash', 'paid', 'DEMO2610200803198E7AB3', 'DEMO-TRX-261020080319', 'bKash', 1, NULL, NULL, 0.00, 0, NULL, 3, 'saparibahan', NULL, 'kawsar', '01712878287', '', '::1', 'house293,block,bashundhara,dhaka', 'Dhaka', NULL, NULL, NULL, NULL, NULL, '2026-10-20 06:03:14', NULL),
(63, 'BH2610206F5ED4', 'ce98014405814432522b5650c2f0a7d94f341f72', NULL, 2350.00, 0.00, 0.00, 2350.00, 'processing', 'bkash', 'paid', 'DEMO2610200947127EA833', 'DEMO-TRX-261020094712', 'bKash', 1, NULL, NULL, 0.00, 0, NULL, 3, 'saparibahan', NULL, 'Ahmed Sadman', '01902887593', 'ahmedsadman2001@gmail.com', '::1', 'e block,7 no road house 285/b,Dhaka', 'Dhaka', NULL, NULL, NULL, NULL, NULL, '2026-10-20 07:47:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `variant_label` varchar(120) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `variant_label`, `image_url`, `price`, `qty`) VALUES
(1, 1, 1001, 'Immune Support Formula with Zinc & Vitamin C, 60 Capsules', NULL, NULL, 6.30, 1),
(2, 1, 1002, 'Magnesium Bisglycinate, 200 mg, 120 Tablets', NULL, NULL, 5.76, 1),
(3, 9, 1059, 'Omega-3 Fish Oil 1000mg', '', 'https://images.pexels.com/photos/29060398/pexels-photo-29060398.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 1490.00, 1),
(4, 10, 1061, 'Magnesium Glycinate', '', 'https://images.pexels.com/photos/29062090/pexels-photo-29062090.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 1390.00, 1),
(5, 11, 1063, 'Vitamin D3 5000 IU', '', 'https://images.unsplash.com/photo-1607619056574-7b8d3ee536b2?w=640&q=80&auto=format&fit=crop', 990.00, 1),
(6, 12, 1063, 'Vitamin D3 5000 IU', '', 'https://images.unsplash.com/photo-1607619056574-7b8d3ee536b2?w=640&q=80&auto=format&fit=crop', 990.00, 1),
(7, 13, 1065, 'B-Complex Stress Formula', '', 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=640&q=80&auto=format&fit=crop', 1150.00, 1),
(8, 14, 1065, 'B-Complex Stress Formula', '', 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=640&q=80&auto=format&fit=crop', 1150.00, 2),
(9, 15, 1067, 'Ashwagandha KSM-66 600mg', '', 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=640&q=80&auto=format&fit=crop', 1890.00, 1),
(11, 17, 1071, 'Iron Citrate + Vitamin C', '', 'https://images.unsplash.com/photo-1567427017947-545c5f8d16ad?w=640&q=80&auto=format&fit=crop', 1090.00, 1),
(12, 18, 1060, 'Collagen Peptides Powder', '', 'https://images.pexels.com/photos/29107585/pexels-photo-29107585.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 2350.00, 1),
(13, 18, 1059, 'Omega-3 Fish Oil 1000mg', '1000 mg', 'https://images.pexels.com/photos/29060398/pexels-photo-29060398.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 1490.00, 1),
(14, 19, 1058, 'Daily Multivitamin', '', 'https://images.pexels.com/photos/15897776/pexels-photo-15897776.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 1680.00, 3),
(15, 19, 1113, 'Laundry Detergent Free & Clear', '', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 1690.00, 1),
(16, 20, 1059, 'Omega-3 Fish Oil 1000mg', '1000 mg', 'https://images.pexels.com/photos/29060398/pexels-photo-29060398.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 1490.00, 1),
(17, 21, 1069, 'Vitamin K2 + D3 (MK-7)', '', 'https://images.pexels.com/photos/7904419/pexels-photo-7904419.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 1450.00, 1),
(18, 21, 1064, 'CoQ10 Ubiquinol 100mg', '100 mg', 'https://images.unsplash.com/photo-1550572017-edd951b55104?w=640&q=80&auto=format&fit=crop', 2190.00, 1),
(19, 22, 1092, 'Naked Pea Protein', '', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 3790.00, 1),
(20, 23, 1092, 'Naked Pea Protein', '', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 3790.00, 1),
(91, 62, 1069, 'Vitamin K2 + D3 (MK-7)', '', 'https://images.pexels.com/photos/7904419/pexels-photo-7904419.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 1450.00, 1),
(92, 63, 1060, 'Collagen Peptides Powder', '', 'https://images.pexels.com/photos/29107585/pexels-photo-29107585.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 2350.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `order_returns`
--

CREATE TABLE `order_returns` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` enum('requested','approved','rejected','received','refunded') NOT NULL DEFAULT 'requested',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `page_content`
--

CREATE TABLE `page_content` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `section_key` varchar(80) NOT NULL,
  `kicker` varchar(100) DEFAULT NULL,
  `title` varchar(190) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `link_label` varchar(120) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `image_url_2` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `page_content`
--

INSERT INTO `page_content` (`id`, `section_key`, `kicker`, `title`, `subtitle`, `body`, `link_url`, `link_label`, `image_url`, `image_url_2`, `is_active`, `sort_order`, `updated_at`) VALUES
(1, 'hero_1', 'LIMITED TIME', '20% OFF\nSUPPLEMENTS', 'Top brands, everyday essentials and more.', 'OCT26SUPP1', 'category/supplements', 'Get 20% off first order', NULL, NULL, 1, 0, '2026-10-17 09:38:45'),
(2, 'hero_2', 'LIMITED TIME', 'UP TO 50% OFF\nTOP DEALS', 'Big-name savings on the brands you love.', 'DEAL50', 'deals', 'Shop the top deals', 'https://images.pexels.com/photos/15897776/pexels-photo-15897776.jpeg?auto=compress&cs=tinysrgb&w=560&h=560&fit=crop', 'https://images.pexels.com/photos/29060398/pexels-photo-29060398.jpeg?auto=compress&cs=tinysrgb&w=320&h=320&fit=crop', 1, 2, NULL),
(3, 'pill_1', NULL, '20% off Supplements ›', NULL, NULL, 'category/supplements', NULL, NULL, NULL, 1, 1, NULL),
(4, 'pill_2', NULL, 'Up to 50% off Deals ›', NULL, NULL, 'deals', NULL, NULL, NULL, 1, 2, NULL),
(5, 'flash_bar', NULL, 'Flash Deals', 'Extra savings on bestsellers ù today only', NULL, 'deals', NULL, NULL, NULL, 1, 0, '2026-10-17 09:43:59');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` int(11) DEFAULT NULL,
  `order_number` varchar(40) NOT NULL,
  `gateway` varchar(20) NOT NULL,
  `gateway_ref` varchar(160) NOT NULL,
  `trx_id` varchar(160) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'BDT',
  `status` enum('initiated','success','failed','cancelled') NOT NULL DEFAULT 'initiated',
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `raw` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payout_requests`
--

CREATE TABLE `payout_requests` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(10) NOT NULL,
  `account` varchar(120) NOT NULL,
  `account_name` varchar(120) DEFAULT NULL,
  `status` enum('requested','approved','paid','rejected') NOT NULL DEFAULT 'requested',
  `reference` varchar(120) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `requested_at` datetime NOT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `proof_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `customer_id` int(11) DEFAULT NULL,
  `doctor_id` int(10) UNSIGNED DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(80) DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `status` enum('pending','reviewing','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `customer_id`, `doctor_id`, `order_id`, `customer_name`, `phone`, `file_path`, `original_name`, `mime_type`, `file_size`, `notes`, `admin_notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 1, NULL, 'Nusrat Jahan', '+8801812345678', NULL, NULL, NULL, NULL, 'Needs Omega-3 and Vitamin D3 as advised by Dr. Rahim after the October check-up.', NULL, 'pending', '2026-10-13 03:15:00', '2026-10-17 09:57:36'),
(2, NULL, 2, NULL, 'Tanvir Hasan', '+8801999887766', NULL, NULL, NULL, NULL, 'Iron + folacin course for 90 days.', 'Verified with City Hospital reception on 1 Oct.', 'approved', '2026-09-30 10:40:00', NULL),
(3, NULL, NULL, NULL, 'Rx Test', '01755555555', 'uploads/prescriptions/20261018-8fc8bc0751d4a015.png', 'rx-test.png', 'image/png', 69, NULL, NULL, 'pending', '2026-10-18 06:07:47', '2026-10-18 06:07:47'),
(4, NULL, NULL, 17, 'NoJS Test', '01666666666', 'uploads/prescriptions/20261018-f9968a082a7a7210.png', 'rx-test.png', 'image/png', 69, NULL, NULL, 'pending', '2026-10-18 06:08:21', '2026-10-18 06:08:21');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(280) NOT NULL,
  `short_description` varchar(255) DEFAULT '',
  `description` text DEFAULT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `old_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `requires_prescription` tinyint(1) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(10) UNSIGNED NOT NULL DEFAULT 10,
  `rating` decimal(2,1) DEFAULT 4.5,
  `reviews_count` int(11) DEFAULT 0,
  `badge` varchar(50) DEFAULT '',
  `tone` varchar(30) DEFAULT '#eef2ed',
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `bestseller` tinyint(1) DEFAULT 0,
  `trending` tinyint(1) DEFAULT 0,
  `new_arrival` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `seller_id` int(10) UNSIGNED DEFAULT NULL,
  `approval_status` enum('draft','pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `json_id` int(11) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `review_count` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `brand_id`, `name`, `slug`, `short_description`, `description`, `sku`, `price`, `old_price`, `stock`, `requires_prescription`, `low_stock_threshold`, `rating`, `reviews_count`, `badge`, `tone`, `image`, `featured`, `bestseller`, `trending`, `new_arrival`, `created_at`, `updated_at`, `seller_id`, `approval_status`, `rejection_reason`, `submitted_at`, `reviewed_by`, `reviewed_at`, `is_active`, `json_id`, `subtitle`, `review_count`) VALUES
(1001, 1, 2167, 'Immune Support Formula with Zinc & Vitamin C, 60 Capsules', 'immune-support-zinc-vitamin-c', 'Everyday immune support with zinc, vitamin C and botanicals.', 'Everyday immune support with zinc, vitamin C and botanicals. Immune Support Formula with Zinc & Vitamin C, 60 Capsules', 'IMMUNE-SUPPORT-ZINC-VITA-1001', 6.30, 7.88, 100, 0, 10, 4.5, 10673, '20% OFF', '#f6f7f6', 'assets/images/products/immune-support.jpg', 1, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 10673),
(1002, 1, 2168, 'Magnesium Bisglycinate, 200 mg, 120 Tablets', 'magnesium-bisglycinate-120-tablets', 'Highly absorbable magnesium for muscle and nerve support.', 'Highly absorbable magnesium for muscle and nerve support. Magnesium Bisglycinate, 200 mg, 120 Tablets', 'MAGNESIUM-BISGLYCINATE-1-1002', 5.76, 7.20, 100, 0, 10, 4.6, 4573, '20% OFF', '#f6f7f6', 'assets/images/products/magneb6.jpg', 1, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 4573),
(1003, 1, 2167, 'Resveratrol Complex with Quercetin, 60 Veggie Capsules', 'resveratrol-complex-quercetin', 'Antioxidant blend inspired by red wine polyphenols.', 'Antioxidant blend inspired by red wine polyphenols. Resveratrol Complex with Quercetin, 60 Veggie Capsules', 'RESVERATROL-COMPLEX-QUER-1003', 7.20, 9.00, 100, 0, 10, 4.7, 3308, '20% OFF', '#f6f7f6', 'assets/images/products/bio-strath.jpg', 1, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 3308),
(1004, 1, 2170, 'Chelated Magnesium, 200 mg, 120 Tablets', 'chelated-magnesium-120-tablets', 'Gentle, chelated magnesium for daily relaxation support.', 'Gentle, chelated magnesium for daily relaxation support. Chelated Magnesium, 200 mg, 120 Tablets', 'CHELATED-MAGNESIUM-120-T-1004', 7.20, 9.00, 100, 0, 10, 4.6, 6890, '20% OFF', '#f6f7f6', 'assets/images/products/magnesium.jpg', 1, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 6890),
(1005, 1, 2167, 'PSQ 120 mg, Phosphatidylserine, 60 Softgels', 'psq-phosphatidylserine-60-softgels', 'DHA and phosphatidylserine for everyday brain support.', 'DHA and phosphatidylserine for everyday brain support. PSQ 120 mg, Phosphatidylserine, 60 Softgels', 'PSQ-PHOSPHATIDYLSERINE-6-1005', 6.58, 8.23, 100, 0, 10, 4.6, 11444, '20% OFF', '#f6f7f6', 'assets/images/products/dha.jpg', 1, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 11444),
(1006, 1, 2172, 'Vitamin K2 (MK-7), 100 mcg, 60 Softgels', 'vitamin-k2-mk7-60-softgels', 'Supports bone and cardiovascular health with vitamin K2.', 'Supports bone and cardiovascular health with vitamin K2. Vitamin K2 (MK-7), 100 mcg, 60 Softgels', 'VITAMIN-K2-MK7-60-SOFTGE-1006', 6.46, 8.08, 100, 0, 10, 4.7, 7879, '20% OFF', '#f6f7f6', 'assets/images/products/ginkgo.jpg', 1, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 7879),
(1011, 1, 2167, 'Triple Strength Omega-3, 1000 mg, 60 Softgels', 'triple-strength-omega-3-60-softgels', 'Purified fish oil providing 600 mg EPA & DHA per serving.', 'Purified fish oil providing 600 mg EPA & DHA per serving. Triple Strength Omega-3, 1000 mg, 60 Softgels', 'TRIPLE-STRENGTH-OMEGA-3--1011', 26.39, 32.99, 100, 0, 10, 4.8, 12573, '20% OFF', '#f6f7f6', 'assets/images/products/omega3.jpg', 0, 0, 1, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 12573),
(1012, 1, 2168, 'N-Acetyl Cysteine (NAC), 600 mg, 100 Capsules', 'n-acetyl-cysteine-600mg', 'Precursor to glutathione, one of the body\\u2019s key antioxidants.', 'Precursor to glutathione, one of the body\\u2019s key antioxidants. N-Acetyl Cysteine (NAC), 600 mg, 100 Capsules', 'N-ACETYL-CYSTEINE-600MG-1012', 3.30, 4.10, 100, 0, 10, 4.7, 1144, '20% OFF', '#f6f7f6', 'assets/images/products/nac.jpg', 0, 0, 1, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 1144),
(1013, 1, 2170, 'Arctic Cod Liver Oil, 500 mg, 100 Softgels', 'arctic-cod-liver-oil-100-softgels', 'Traditional omega-3 source with vitamins A and D.', 'Traditional omega-3 source with vitamins A and D. Arctic Cod Liver Oil, 500 mg, 100 Softgels', 'ARCTIC-COD-LIVER-OIL-100-1013', 6.81, 8.52, 100, 0, 10, 4.8, 44102, '20% OFF', '#f6f7f6', 'assets/images/products/fishoil.jpg', 0, 0, 1, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 44102),
(1014, 2, 2167, 'Grass-Fed Whey Protein Isolate, 1 lb (454 g)', 'grass-fed-whey-protein-isolate', '25 g of clean protein per scoop with a smooth, mixable texture.', '25 g of clean protein per scoop with a smooth, mixable texture. Grass-Fed Whey Protein Isolate, 1 lb (454 g)', 'GRASS-FED-WHEY-PROTEIN-I-1014', 31.19, 38.99, 100, 0, 10, 4.7, 9145, '20% OFF', '#f6f7f6', 'assets/images/products/protein-powder.jpg', 0, 1, 1, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 9145),
(1015, 4, 2177, 'Marine Collagen Peptides, 300 g', 'marine-collagen-peptides-300g', 'Hydrolyzed type I collagen peptides that dissolve in any drink.', 'Hydrolyzed type I collagen peptides that dissolve in any drink. Marine Collagen Peptides, 300 g', 'MARINE-COLLAGEN-PEPTIDES-1015', 19.92, 24.90, 100, 0, 10, 4.6, 3344, '20% OFF', '#f6f7f6', 'assets/images/products/collagen.jpg', 0, 0, 1, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 3344),
(1016, 1, 2172, 'Green Coffee Bean Extract, 400 mg, 90 Capsules', 'green-coffee-bean-extract-90-capsules', 'Standardised green coffee extract for a gentle energy lift.', 'Standardised green coffee extract for a gentle energy lift. Green Coffee Bean Extract, 400 mg, 90 Capsules', 'GREEN-COFFEE-BEAN-EXTRAC-1016', 5.76, 7.20, 100, 0, 10, 4.5, 1079, '20% OFF', '#f6f7f6', 'assets/images/products/green-coffee.jpg', 0, 0, 1, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 1079),
(1021, 1, 2168, 'High Absorption Magnesium, 240 mg, 120 Capsules', 'high-absorption-magnesium-120-capsules', 'Fully reacted magnesium glycinate for everyday routines.', 'Fully reacted magnesium glycinate for everyday routines. High Absorption Magnesium, 240 mg, 120 Capsules', 'HIGH-ABSORPTION-MAGNESIU-1021', 23.38, 29.23, 100, 0, 10, 4.7, 44201, '20% OFF', '#f6f7f6', 'assets/images/products/multivitamin.jpg', 0, 1, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 44201),
(1022, 1, 2170, 'High Absorption L-Lysine, 1000 mg, 100 Tablets', 'l-lysine-1000mg-100-tablets', 'Essential amino acid in an easy-to-swallow tablet.', 'Essential amino acid in an easy-to-swallow tablet. High Absorption L-Lysine, 1000 mg, 100 Tablets', 'L-LYSINE-1000MG-100-TABL-1022', 12.75, 15.94, 100, 0, 10, 4.6, 8103, '20% OFF', '#f6f7f6', 'assets/images/products/folic-acid.jpg', 0, 1, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 8103),
(1023, 1, 2167, 'Vitamin D3 & K2 (MK-7), 125 mcg, 120 Softgels', 'vitamin-d3-k2-120-softgels', 'Synergistic D3 and K2 pairing for bone and immune support.', 'Synergistic D3 and K2 pairing for bone and immune support. Vitamin D3 & K2 (MK-7), 125 mcg, 120 Softgels', 'VITAMIN-D3-K2-120-SOFTGE-1023', 24.62, 30.78, 100, 0, 10, 4.7, 10141, '20% OFF', '#f6f7f6', 'assets/images/products/gummies.jpg', 0, 1, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 10141),
(1024, 1, 2168, 'BioActive B-Complex with Methylfolate, 60 Capsules', 'bioactive-b-complex-methylfolate', 'Active forms of B vitamins for energy and metabolism.', 'Active forms of B vitamins for energy and metabolism. BioActive B-Complex with Methylfolate, 60 Capsules', 'BIOACTIVE-B-COMPLEX-METH-1024', 15.00, 18.75, 100, 0, 10, 4.6, 2018, '20% OFF', '#f6f7f6', 'assets/images/products/reversage.jpg', 0, 1, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 2018),
(1025, 5, 2177, 'Organic Psyllium Husk Powder, 12 oz (340 g)', 'organic-psyllium-husk-powder', 'Soluble fibre that supports regularity and gut comfort.', 'Soluble fibre that supports regularity and gut comfort. Organic Psyllium Husk Powder, 12 oz (340 g)', 'ORGANIC-PSYLLIUM-HUSK-PO-1025', 11.20, 14.00, 100, 0, 10, 4.8, 5641, '20% OFF', '#f6f7f6', 'assets/images/products/psyllium.jpg', 0, 1, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 5641),
(1026, 2, 2167, 'Electrolyte Recovery Powder, 16 oz (454 g)', 'electrolyte-recovery-powder', 'Balanced electrolytes for hydration during training.', 'Balanced electrolytes for hydration during training. Electrolyte Recovery Powder, 16 oz (454 g)', 'ELECTROLYTE-RECOVERY-POW-1026', 15.20, 19.00, 100, 0, 10, 4.6, 1310, '20% OFF', '#f6f7f6', 'assets/images/products/keto-powder.jpg', 0, 1, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 1310),
(1031, 1, 2167, 'Pure Resveratrol 500 mg, 100 Capsules', 'pure-resveratrol-500mg-100-capsules', 'Trans-resveratrol from Japanese knotweed, standardised extract.', 'Trans-resveratrol from Japanese knotweed, standardised extract. Pure Resveratrol 500 mg, 100 Capsules', 'PURE-RESVERATROL-500MG-1-1031', 18.38, 22.98, 100, 0, 10, 4.6, 1043, '20% OFF', '#f6f7f6', 'assets/images/products/resveratrol.jpg', 0, 0, 0, 1, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 1043),
(1032, 2, 2177, 'Beetroot Juice Powder, 8.8 oz (250 g)', 'beetroot-juice-powder-250g', 'Concentrated beet nitrates to support everyday endurance.', 'Concentrated beet nitrates to support everyday endurance. Beetroot Juice Powder, 8.8 oz (250 g)', 'BEETROOT-JUICE-POWDER-25-1032', 34.48, 43.10, 100, 0, 10, 4.5, 453, '20% OFF', '#f6f7f6', 'assets/images/products/shred.jpg', 0, 0, 0, 1, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 453),
(1033, 1, 2170, 'Plant-Based Iron, 25 mg, 90 Tablets', 'plant-based-iron-90-tablets', 'Gentle iron bisglycinate with vitamin C for absorption.', 'Gentle iron bisglycinate with vitamin C for absorption. Plant-Based Iron, 25 mg, 90 Tablets', 'PLANT-BASED-IRON-90-TABL-1033', 25.28, 31.60, 100, 0, 10, 4.6, 5813, '20% OFF', '#f6f7f6', 'assets/images/products/preworkout.jpg', 0, 0, 0, 1, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 5813),
(1034, 1, 2168, 'Bio-Rhodiola Rosea, 200 mg, 60 Capsules', 'rhodiola-rosea-200mg-60-capsules', 'Adaptogen root extract traditionally used for stress support.', 'Adaptogen root extract traditionally used for stress support. Bio-Rhodiola Rosea, 200 mg, 60 Capsules', 'RHODIOLA-ROSEA-200MG-60--1034', 23.16, 28.95, 100, 0, 10, 4.7, 3340, '20% OFF', '#f6f7f6', 'assets/images/products/bcaa.jpg', 0, 0, 0, 1, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 3340),
(1035, 5, 2167, 'Organic Berry Greens Blend, 8.1 oz (230 g)', 'organic-berry-greens-blend', 'Whole-food greens and berries in one fruity daily scoop.', 'Whole-food greens and berries in one fruity daily scoop. Organic Berry Greens Blend, 8.1 oz (230 g)', 'ORGANIC-BERRY-GREENS-BLE-1035', 36.60, 45.75, 100, 0, 10, 4.6, 480, '20% OFF', '#f6f7f6', 'assets/images/products/spirulina.jpg', 0, 0, 0, 1, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 480),
(1036, 1, 2172, 'Sulforaphane Broccoli Seed Extract, 400 mcg, 60 Capsules', 'sulforaphane-broccoli-seed-extract', 'Broccoli seed extract standardised for sulforaphane content.', 'Broccoli seed extract standardised for sulforaphane content. Sulforaphane Broccoli Seed Extract, 400 mcg, 60 Capsules', 'SULFORAPHANE-BROCCOLI-SE-1036', 39.61, 49.51, 100, 0, 10, 4.7, 294, '20% OFF', '#f6f7f6', 'assets/images/products/prenatal.jpg', 0, 0, 0, 1, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 294),
(1041, 1, 2191, 'NMN 1000 mg, 60 Tablets', 'nmn-1000mg-60-tablets', 'Nicotinamide mononucleotide tablets for cellular health.', 'Nicotinamide mononucleotide tablets for cellular health. NMN 1000 mg, 60 Tablets', 'NMN-1000MG-60-TABLETS-1041', 18.23, 22.50, 100, 0, 10, 4.6, 76, '', '#f6f7f6', 'assets/images/products/nmn.jpg', 0, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 76),
(1042, 1, 2192, 'Vitamin D3, 2000 IU, 120 Capsules', 'vitamin-d3-2000-iu-120-capsules', 'Everyday vitamin D3 in easy-to-take capsules.', 'Everyday vitamin D3 in easy-to-take capsules. Vitamin D3, 2000 IU, 120 Capsules', 'VITAMIN-D3-2000-IU-120-C-1042', 79.67, 96.00, 100, 0, 10, 4.8, 213, '', '#f6f7f6', 'assets/images/products/gold-vit.jpg', 0, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 213),
(1043, 2, 2177, 'CLA Conjugated Linoleic Acid, 1000 mg, 90 Softgels', 'cla-1000mg-90-softgels', 'Safflower oil derived CLA for active lifestyles.', 'Safflower oil derived CLA for active lifestyles. CLA Conjugated Linoleic Acid, 1000 mg, 90 Softgels', 'CLA-1000MG-90-SOFTGELS-1043', 66.42, 78.00, 100, 0, 10, 4.5, 88, '', '#f6f7f6', 'assets/images/products/digestive-enzymes.jpg', 0, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 88),
(1044, 3, 2170, 'Clarifying Shampoo, 16 fl oz (473 ml)', 'clarifying-shampoo-473ml', 'Sulphate-free daily shampoo for a fresh, clean scalp.', 'Sulphate-free daily shampoo for a fresh, clean scalp. Clarifying Shampoo, 16 fl oz (473 ml)', 'CLARIFYING-SHAMPOO-473ML-1044', 56.28, 66.00, 100, 0, 10, 4.6, 41, '', '#f6f7f6', 'assets/images/products/shampoo.jpg', 0, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 41),
(1045, 4, 2195, 'Herbal Skin Serum, 1 fl oz (30 ml)', 'herbal-skin-serum-30ml', 'Lightweight botanical serum for daily moisture.', 'Lightweight botanical serum for daily moisture. Herbal Skin Serum, 1 fl oz (30 ml)', 'HERBAL-SKIN-SERUM-30ML-1045', 14.97, 18.50, 100, 0, 10, 4.7, 67, '', '#f6f7f6', 'assets/images/products/face-cream.jpg', 0, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 67),
(1046, 4, 2168, 'Vitamin C Brightening Serum, 1 fl oz (30 ml)', 'vitamin-c-serum-30ml', 'Stabilised vitamin C serum for a more even-looking tone.', 'Stabilised vitamin C serum for a more even-looking tone. Vitamin C Brightening Serum, 1 fl oz (30 ml)', 'VITAMIN-C-SERUM-30ML-1046', 22.80, 28.00, 100, 0, 10, 4.6, 54, '', '#f6f7f6', 'assets/images/products/serum.jpg', 0, 0, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 54),
(1051, 3, 2167, 'Gentle Daily Cleanser, 16 fl oz (473 ml)', 'gentle-daily-cleanser-473ml', 'Sulphate-free cleansing care for hair and scalp.', 'Sulphate-free cleansing care for hair and scalp. Gentle Daily Cleanser, 16 fl oz (473 ml)', 'GENTLE-DAILY-CLEANSER-47-1051', 10.08, 12.60, 100, 0, 10, 4.5, 962, '20% OFF', '#f6f7f6', 'assets/images/products/conditioner.jpg', 0, 1, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 962),
(1052, 4, 2168, 'Urea Repair Hand Cream, 2.5 fl oz (75 ml)', 'urea-hand-cream-75ml', 'Rich but fast-absorbing care for very dry hands.', 'Rich but fast-absorbing care for very dry hands. Urea Repair Hand Cream, 2.5 fl oz (75 ml)', 'UREA-HAND-CREAM-75ML-1052', 7.00, 8.75, 100, 0, 10, 4.7, 2841, '20% OFF', '#f6f7f6', 'assets/images/products/hand-cream.jpg', 0, 1, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 2841),
(1053, 3, 2170, 'Natural Toothpaste, Fresh Mint, 3.4 fl oz (100 ml)', 'natural-toothpaste-fresh-mint', 'Fluoride-free mineral toothpaste with a clean mint taste.', 'Fluoride-free mineral toothpaste with a clean mint taste. Natural Toothpaste, Fresh Mint, 3.4 fl oz (100 ml)', 'NATURAL-TOOTHPASTE-FRESH-1053', 5.00, 6.25, 100, 0, 10, 4.6, 1520, '20% OFF', '#f6f7f6', 'assets/images/products/toothpaste.jpg', 0, 1, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 1520),
(1054, 6, 2177, 'Everyday Deodorant Refill, 1.4 oz (40 g)', 'everyday-deodorant-refill', 'Aluminium-free refill that keeps waste to a minimum.', 'Aluminium-free refill that keeps waste to a minimum. Everyday Deodorant Refill, 1.4 oz (40 g)', 'EVERYDAY-DEODORANT-REFIL-1054', 10.80, 13.50, 100, 0, 10, 4.4, 733, '20% OFF', '#f6f7f6', 'assets/images/products/deodorant.jpg', 0, 1, 0, 0, '2026-10-07 09:21:59', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 733),
(1055, 7, 2172, 'Kids Multivitamin Gummies, 60 Gummies', 'kids-multivitamin-gummies', 'Berry flavoured daily gummies children actually enjoy.', 'Berry flavoured daily gummies children actually enjoy. Kids Multivitamin Gummies, 60 Gummies', 'KIDS-MULTIVITAMIN-GUMMIE-1055', 11.52, 14.40, 100, 0, 10, 4.8, 6104, '20% OFF', '#f6f7f6', 'assets/images/products/gummies.jpg', 0, 1, 0, 0, '2026-10-07 09:23:18', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 6104),
(1056, 8, 2167, 'Pet Omega-3 Support Capsules, 90 Count', 'pet-omega-3-capsules', 'Skin and coat support for dogs and cats.', 'Skin and coat support for dogs and cats. Pet Omega-3 Support Capsules, 90 Count', 'PET-OMEGA-3-CAPSULES-1056', 12.80, 16.00, 100, 0, 10, 4.7, 1285, '20% OFF', '#f6f7f6', 'assets/images/products/fishoil.jpg', 0, 1, 0, 0, '2026-10-07 09:23:18', NULL, NULL, 'approved', NULL, NULL, NULL, NULL, 1, NULL, NULL, 1285),
(1057, 1, 2275, 'Vitamin C 1000mg', 'vitamin-c-1000mg-1', 'Immune Support • 100 Tablets', NULL, 'BH-0001', 1250.00, 1590.00, 50, 0, 10, 4.6, 0, '20% OFF', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 1, 'Immune Support • 100 Tablets', 1205),
(1058, 1, 2275, 'Daily Multivitamin', 'daily-multivitamin-2', 'Complete Daily Nutrition • 60 Caps', NULL, 'BH-0002', 1680.00, 2100.00, 53, 0, 10, 4.5, 0, '20% OFF', '#eef2ed', 'https://images.pexels.com/photos/15897776/pexels-photo-15897776.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 2, 'Complete Daily Nutrition • 60 Caps', 854),
(1059, 1, 2275, 'Omega-3 Fish Oil 1000mg', 'omega-3-fish-oil-1000mg-3', 'Heart & Brain Support • 120 Softgels', NULL, 'BH-0003', 1490.00, 1890.00, 53, 0, 10, 4.7, 0, 'Extra 10%', '#eef2ed', 'https://images.pexels.com/photos/29060398/pexels-photo-29060398.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 3, 'Heart & Brain Support • 120 Softgels', 2104),
(1060, 1, 2276, 'Collagen Peptides Powder', 'collagen-peptides-powder-4', 'Beauty & Wellness • 300g', NULL, 'BH-0004', 2350.00, 2790.00, 50, 0, 10, 4.4, 0, '20% OFF', '#eef2ed', 'https://images.pexels.com/photos/29107585/pexels-photo-29107585.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 4, 'Beauty & Wellness • 300g', 309),
(1061, 1, 2192, 'Magnesium Glycinate', 'magnesium-glycinate-5', 'Relaxation Support • 180 Caps', NULL, 'BH-0005', 1390.00, 1720.00, 51, 0, 10, 4.8, 0, 'Best Seller', '#eef2ed', 'https://images.pexels.com/photos/29062090/pexels-photo-29062090.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 5, 'Relaxation Support • 180 Caps', 3214),
(1062, 1, 2277, 'Probiotic 10 Billion CFU', 'probiotic-10-billion-cfu-6', 'Digestive Wellness • 30 Caps', NULL, 'BH-0006', 1790.00, 2190.00, 50, 0, 10, 4.5, 0, 'Extra 10%', '#eef2ed', 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 6, 'Digestive Wellness • 30 Caps', 128),
(1063, 1, 2278, 'Vitamin D3 5000 IU', 'vitamin-d3-5000-iu-7', 'Bone & Immune Health • 120 Caps', NULL, 'BH-0007', 990.00, 1250.00, 52, 0, 10, 4.7, 0, '20% OFF', '#eef2ed', 'https://images.unsplash.com/photo-1607619056574-7b8d3ee536b2?w=640&q=80&auto=format&fit=crop', 0, 0, 1, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 7, 'Bone & Immune Health • 120 Caps', 987),
(1064, 1, 2279, 'CoQ10 Ubiquinol 100mg', 'coq10-ubiquinol-100mg-8', 'Energy & Heart Support • 60 Softgels', NULL, 'BH-0008', 2190.00, 2650.00, 51, 0, 10, 4.6, 0, '', '#eef2ed', 'https://images.unsplash.com/photo-1550572017-edd951b55104?w=640&q=80&auto=format&fit=crop', 0, 0, 1, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 8, 'Energy & Heart Support • 60 Softgels', 456),
(1065, 1, 2280, 'B-Complex Stress Formula', 'b-complex-stress-formula-9', 'Nervous System Support • 100 Caps', NULL, 'BH-0009', 1150.00, 1450.00, 56, 0, 10, 4.5, 0, 'Extra 10%', '#eef2ed', 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=640&q=80&auto=format&fit=crop', 0, 0, 1, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 9, 'Nervous System Support • 100 Caps', 765),
(1066, 1, 2281, 'Digestive Enzyme Blend', 'digestive-enzyme-blend-10', 'Digestive Wellness • 90 Caps', NULL, 'BH-0010', 1590.00, 1990.00, 50, 0, 10, 4.4, 0, '', '#eef2ed', 'https://images.unsplash.com/photo-1512069772995-ec65ed45afd6?w=640&q=80&auto=format&fit=crop', 0, 0, 1, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 10, 'Digestive Wellness • 90 Caps', 233),
(1067, 1, 2282, 'Ashwagandha KSM-66 600mg', 'ashwagandha-ksm-66-600mg-11', 'Stress & Mood Support • 120 Caps', NULL, 'BH-0011', 1890.00, 2300.00, 51, 0, 10, 4.7, 0, '20% OFF', '#eef2ed', 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=640&q=80&auto=format&fit=crop', 0, 0, 1, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 11, 'Stress & Mood Support • 120 Caps', 1567),
(1068, 1, 2192, 'Zinc Picolinate 50mg', 'zinc-picolinate-50mg-12', 'Immune Support • 100 Caps', NULL, 'BH-0012', 790.00, 990.00, 50, 0, 10, 4.6, 0, '', '#eef2ed', 'https://images.pexels.com/photos/15897780/pexels-photo-15897780.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 1, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 12, 'Immune Support • 100 Caps', 642),
(1069, 1, 2275, 'Vitamin K2 + D3 (MK-7)', 'vitamin-k2-d3-mk-7-13', 'Bone & Cardiovascular • 60 Caps', NULL, 'BH-0013', 1450.00, 1790.00, 52, 0, 10, 4.8, 0, 'New', '#eef2ed', 'https://images.pexels.com/photos/7904419/pexels-photo-7904419.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 1, '2026-10-21 03:57:46', '2026-10-21 08:00:12', NULL, 'approved', NULL, NULL, NULL, NULL, 0, 13, 'Bone & Cardiovascular • 60 Caps', 412),
(1070, 1, 2283, 'Elderberry Immune Gummies', 'elderberry-immune-gummies-14', 'Immune Support • 60 Gummies', NULL, 'BH-0014', 1290.00, 1590.00, 50, 0, 10, 4.6, 0, 'New', '#eef2ed', 'https://images.pexels.com/photos/14433549/pexels-photo-14433549.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 1, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 14, 'Immune Support • 60 Gummies', 289),
(1071, 1, 2284, 'Iron Citrate + Vitamin C', 'iron-citrate-vitamin-c-15', 'Gentle Iron • 90 Caps', NULL, 'BH-0015', 1090.00, 1350.00, 51, 0, 10, 4.5, 0, 'New', '#eef2ed', 'https://images.unsplash.com/photo-1567427017947-545c5f8d16ad?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 1, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 15, 'Gentle Iron • 90 Caps', 174),
(1072, 1, 2275, 'Hair, Skin & Nails Biotin', 'hair-skin-nails-biotin-16', 'Biotin 5000mcg • 60 Veggie Caps', NULL, 'BH-0016', 950.00, 1190.00, 50, 0, 10, 4.7, 0, '20% OFF', '#eef2ed', 'https://images.pexels.com/photos/10825668/pexels-photo-10825668.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 1, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 16, 'Biotin 5000mcg • 60 Veggie Caps', 521),
(1073, 1, 2285, 'Marine Collagen Liquid', 'marine-collagen-liquid-17', 'Youthful Skin • 16 fl oz', NULL, 'BH-0017', 2690.00, 3150.00, 50, 0, 10, 4.4, 0, 'New', '#eef2ed', 'https://images.pexels.com/photos/7795661/pexels-photo-7795661.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 1, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 17, 'Youthful Skin • 16 fl oz', 96),
(1074, 1, 2275, 'Melatonin 5mg', 'melatonin-5mg-18', 'Sleep Support • 60 Tablets', NULL, 'BH-0018', 650.00, 820.00, 50, 0, 10, 4.6, 0, '20% OFF', '#eef2ed', 'https://images.unsplash.com/photo-1628771065518-0d82f1938462?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 1, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 18, 'Sleep Support • 60 Tablets', 834),
(1075, 4, 2286, 'Hyaluronic Acid Serum', 'hyaluronic-acid-serum-19', 'Deep Hydration • 1 fl oz', NULL, 'BH-0019', 1890.00, 2250.00, 50, 0, 10, 4.7, 0, '20% OFF', '#eef2ed', 'https://images.pexels.com/photos/29675494/pexels-photo-29675494.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 19, 'Deep Hydration • 1 fl oz', 1544),
(1076, 4, 2287, 'Vitamin C Brightening Serum', 'vitamin-c-brightening-serum-20', 'Glow & Even Tone • 1 fl oz', NULL, 'BH-0020', 1650.00, 1990.00, 50, 0, 10, 4.5, 0, '', '#eef2ed', 'https://images.pexels.com/photos/12602356/pexels-photo-12602356.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 08:00:12', NULL, 'approved', NULL, NULL, NULL, NULL, 0, 20, 'Glow & Even Tone • 1 fl oz', 702),
(1077, 4, 2288, 'Nourishing Face Oil', 'nourishing-face-oil-21', 'Squalane + Rosehip • 1 fl oz', NULL, 'BH-0021', 1250.00, 1490.00, 50, 0, 10, 4.8, 0, 'Best Seller', '#eef2ed', 'https://images.pexels.com/photos/29044029/pexels-photo-29044029.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 21, 'Squalane + Rosehip • 1 fl oz', 2310),
(1078, 4, 2289, 'Keratin Repair Hair Mask', 'keratin-repair-hair-mask-22', 'Deep Conditioning • 8 oz', NULL, 'BH-0022', 1090.00, 1350.00, 50, 0, 10, 4.4, 0, 'Extra 10%', '#eef2ed', 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 22, 'Deep Conditioning • 8 oz', 388),
(1079, 535, 2290, 'Ultra Sheer Sunscreen SPF 50', 'ultra-sheer-sunscreen-spf-50-23', 'Broad Spectrum • 3 fl oz', NULL, 'BH-0023', 1450.00, 1690.00, 50, 0, 10, 4.7, 0, '20% OFF', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 23, 'Broad Spectrum • 3 fl oz', 1876),
(1080, 583, 2291, 'AHA/BHA Clarifying Toner', 'aha-bha-clarifying-toner-24', 'K-Beauty Daily Peel • 150 ml', NULL, 'BH-0024', 1650.00, 1950.00, 50, 0, 10, 4.8, 0, 'New', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 24, 'K-Beauty Daily Peel • 150 ml', 2043),
(1081, 554, 2292, 'Be Gentle, Be Kind Shampoo', 'be-gentle-be-kind-shampoo-25', 'Banana & Coconut • 16 fl oz', NULL, 'BH-0025', 2190.00, 2550.00, 50, 0, 10, 4.5, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 25, 'Banana & Coconut • 16 fl oz', 467),
(1082, 584, 2293, 'Vitamin E Body Oil', 'vitamin-e-body-oil-26', 'Scar & Stretch Mark Care • 200ml', NULL, 'BH-0026', 1450.00, 1750.00, 50, 0, 10, 4.6, 0, '', '#eef2ed', 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 26, 'Scar & Stretch Mark Care • 200ml', 1876),
(1083, 584, 2294, 'Hydrating Body Lotion', 'hydrating-body-lotion-27', 'Ceramides & Hyaluronic Acid • 16 oz', NULL, 'BH-0027', 1790.00, 2100.00, 50, 0, 10, 4.7, 0, '20% OFF', '#eef2ed', 'https://images.unsplash.com/photo-1596755389378-c31d21fd1273?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 27, 'Ceramides & Hyaluronic Acid • 16 oz', 945),
(1084, 584, 2295, 'Co-Wash Cleansing Conditioner', 'co-wash-cleansing-conditioner-28', 'Sulfate-Free • 13 fl oz', NULL, 'BH-0028', 1290.00, 1550.00, 50, 0, 10, 4.5, 0, '', '#eef2ed', 'https://images.pexels.com/photos/10825668/pexels-photo-10825668.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 28, 'Sulfate-Free • 13 fl oz', 412),
(1085, 555, 2296, 'Pure-Castile Body Wash', 'pure-castile-body-wash-29', 'Peppermint • 16 fl oz', NULL, 'BH-0029', 1390.00, 1650.00, 50, 0, 10, 4.7, 0, 'Best Seller', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 29, 'Peppermint • 16 fl oz', 1420),
(1086, 556, 2297, 'Fluoride Toothpaste', 'fluoride-toothpaste-30', 'Wintermint • 5.5 oz', NULL, 'BH-0030', 690.00, 850.00, 50, 0, 10, 4.6, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 30, 'Wintermint • 5.5 oz', 733),
(1087, 557, 2292, 'Don’t Despair, Repair! Hair Mask', 'don-t-despair-repair-hair-mask-31', 'Deep Conditioning • 8 oz', NULL, 'BH-0031', 2190.00, 2590.00, 50, 0, 10, 4.7, 0, '20% OFF', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 31, 'Deep Conditioning • 8 oz', 864),
(1088, 2, 2298, 'Gold Standard Whey Protein', 'gold-standard-whey-protein-32', '24g Protein • 2 lb Double Chocolate', NULL, 'BH-0032', 4890.00, 5650.00, 50, 0, 10, 4.8, 0, '20% OFF', '#eef2ed', 'https://images.pexels.com/photos/29060401/pexels-photo-29060401.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 32, '24g Protein • 2 lb Double Chocolate', 5621),
(1089, 2, 2299, 'Creatine Monohydrate Powder', 'creatine-monohydrate-powder-33', '5g Per Serving • 60 Servings', NULL, 'BH-0033', 2190.00, 2590.00, 50, 0, 10, 4.7, 0, '', '#eef2ed', 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 33, '5g Per Serving • 60 Servings', 2044),
(1090, 2, 2300, 'BCAA Amino Energy', 'bcaa-amino-energy-34', 'Muscle Recovery • 30 Servings', NULL, 'BH-0034', 2650.00, 3100.00, 50, 0, 10, 4.5, 0, 'Extra 10%', '#eef2ed', 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 34, 'Muscle Recovery • 30 Servings', 766),
(1091, 2, 2301, 'Pre-Workout Ignite', 'pre-workout-ignite-35', 'Focus & Energy • 30 Servings', NULL, 'BH-0035', 2390.00, 2790.00, 50, 0, 10, 4.4, 0, 'New', '#eef2ed', 'https://images.pexels.com/photos/15897780/pexels-photo-15897780.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 35, 'Focus & Energy • 30 Servings', 530),
(1092, 531, 2302, 'Naked Pea Protein', 'naked-pea-protein-36', 'Unflavored Plant Protein • 30 Servings', NULL, 'BH-0036', 3790.00, 4250.00, 53, 0, 10, 4.7, 0, 'Extra 10%', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 36, 'Unflavored Plant Protein • 30 Servings', 512),
(1093, 543, 2192, 'Sports L-Glutamine Powder', 'sports-l-glutamine-powder-37', 'Muscle Recovery • 300 g', NULL, 'BH-0037', 1890.00, 2190.00, 50, 0, 10, 4.6, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 37, 'Muscle Recovery • 300 g', 289),
(1094, 5, 2303, 'Organic Cacao Powder', 'organic-cacao-powder-38', 'Superfood Staple • 16 oz', NULL, 'BH-0038', 1150.00, 1390.00, 50, 0, 10, 4.6, 0, '', '#eef2ed', 'https://images.unsplash.com/photo-1610725664285-7c57e6eeac3f?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 38, 'Superfood Staple • 16 oz', 297),
(1095, 5, 2304, 'Organic Superfood Granola', 'organic-superfood-granola-39', 'Whole Grain • 12 oz', NULL, 'BH-0039', 990.00, 1190.00, 50, 0, 10, 4.5, 0, '20% OFF', '#eef2ed', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 39, 'Whole Grain • 12 oz', 186),
(1096, 5, 2305, 'Everyday Veggie Blend', 'everyday-veggie-blend-40', 'Frozen Fresh • 12 oz', NULL, 'BH-0040', 750.00, 890.00, 50, 0, 10, 4.4, 0, '', '#eef2ed', 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 40, 'Frozen Fresh • 12 oz', 98),
(1097, 546, 2306, 'Organic Chia Seeds', 'organic-chia-seeds-41', 'Superfood Staple • 12 oz', NULL, 'BH-0041', 990.00, 1190.00, 50, 0, 10, 4.6, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 41, 'Superfood Staple • 12 oz', 312),
(1098, 547, 2307, 'Old Fashioned Rolled Oats', 'old-fashioned-rolled-oats-42', 'Whole Grain • 32 oz', NULL, 'BH-0042', 850.00, 990.00, 50, 0, 10, 4.7, 0, '20% OFF', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 42, 'Whole Grain • 32 oz', 521),
(1099, 548, 2308, 'Extra Virgin Olive Oil', 'extra-virgin-olive-oil-43', 'Cold Pressed • 16.9 fl oz', NULL, 'BH-0043', 1890.00, 2190.00, 50, 0, 10, 4.5, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 43, 'Cold Pressed • 16.9 fl oz', 266),
(1100, 585, 2309, 'Kids Multivitamin Gummies', 'kids-multivitamin-gummies-44', 'Ages 2+ • 60 Gummies', NULL, 'BH-0044', 1090.00, 1290.00, 50, 0, 10, 4.7, 0, 'Best Seller', '#eef2ed', 'https://images.pexels.com/photos/14433549/pexels-photo-14433549.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 08:00:12', NULL, 'approved', NULL, NULL, NULL, NULL, 0, 44, 'Ages 2+ • 60 Gummies', 654),
(1101, 585, 2310, 'Kids Probiotic Drops', 'kids-probiotic-drops-45', 'Digestive Support • 5 ml', NULL, 'BH-0045', 1490.00, 1750.00, 50, 0, 10, 4.8, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 45, 'Digestive Support • 5 ml', 321),
(1102, 585, 2311, 'Kids Chewable Probiotic', 'kids-chewable-probiotic-46', 'Immune & Digestive • 30 Chewables', NULL, 'BH-0046', 1250.00, 1490.00, 50, 0, 10, 4.5, 0, '20% OFF', '#eef2ed', 'https://images.unsplash.com/photo-1512069772995-ec65ed45afd6?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 46, 'Immune & Digestive • 30 Chewables', 143),
(1103, 524, 2312, 'Omega-3 Gummies for Kids', 'omega-3-gummies-for-kids-47', 'DHA for Brain Health • 60 Gummies', NULL, 'BH-0047', 1590.00, 1890.00, 50, 0, 10, 4.8, 0, 'New', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 47, 'DHA for Brain Health • 60 Gummies', 392),
(1104, 532, 2313, 'Organic Fruit Snack Rolls', 'organic-fruit-snack-rolls-48', 'Toddler Snack • 12 Pouches', NULL, 'BH-0048', 790.00, 950.00, 50, 0, 10, 4.5, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 48, 'Toddler Snack • 12 Pouches', 178),
(1105, 8, 2312, 'Omega-3 Cat Gel', 'omega-3-cat-gel-49', 'Skin & Coat Health • 2 fl oz', NULL, 'BH-0049', 1650.00, 1950.00, 50, 0, 10, 4.7, 0, '20% OFF', '#eef2ed', 'https://images.unsplash.com/photo-1450778869180-41d0601e046e?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 49, 'Skin & Coat Health • 2 fl oz', 412),
(1106, 8, 2314, 'Calm & Relax Chews', 'calm-relax-chews-50', 'Stress Support for Cats • 30 Chews', NULL, 'BH-0050', 1890.00, 2200.00, 50, 0, 10, 4.6, 0, '', '#eef2ed', 'https://images.unsplash.com/photo-1607619056574-7b8d3ee536b2?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 50, 'Stress Support for Cats • 30 Chews', 229),
(1107, 8, 2315, 'Joint Care Soft Chews', 'joint-care-soft-chews-51', 'Mobility for Dogs • 60 Chews', NULL, 'BH-0051', 2150.00, 2490.00, 50, 0, 10, 4.8, 0, 'Best Seller', '#eef2ed', 'https://images.pexels.com/photos/29062090/pexels-photo-29062090.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 51, 'Mobility for Dogs • 60 Chews', 765),
(1108, 521, 2316, 'Daily Multivitamin for Dogs', 'daily-multivitamin-for-dogs-52', 'All-In-One Wellness • 90 Chews', NULL, 'BH-0052', 2450.00, 2850.00, 50, 0, 10, 4.8, 0, 'Best Seller', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 52, 'All-In-One Wellness • 90 Chews', 1105),
(1109, 578, 2315, 'Probiotic Soft Chews for Dogs', 'probiotic-soft-chews-for-dogs-53', 'Digestive Health • 60 Chews', NULL, 'BH-0053', 1950.00, 2290.00, 50, 0, 10, 4.6, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 53, 'Digestive Health • 60 Chews', 458),
(1110, 6, 2317, 'Natural Room Spray', 'natural-room-spray-54', 'Lavender & Cedar • 6.8 fl oz', NULL, 'BH-0054', 1190.00, 1390.00, 50, 0, 10, 4.5, 0, '', '#eef2ed', 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 54, 'Lavender & Cedar • 6.8 fl oz', 120),
(1111, 562, 2318, 'Eco Household Cleaner', 'eco-household-cleaner-55', 'Plant-Powered • 23 oz', NULL, 'BH-0055', 890.00, 1050.00, 50, 0, 10, 4.6, 0, '20% OFF', '#eef2ed', 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=640&q=80&auto=format&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 55, 'Plant-Powered • 23 oz', 344),
(1112, 564, 2319, 'Automatic Dishwasher Detergent Pods', 'automatic-dishwasher-detergent-pods-56', 'Plant-Based • 60 Loads', NULL, 'BH-0056', 1350.00, 1590.00, 50, 0, 10, 4.6, 0, '20% OFF', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 56, 'Plant-Based • 60 Loads', 412),
(1113, 563, 2318, 'Laundry Detergent Free & Clear', 'laundry-detergent-free-clear-57', 'Fresh Linen • 64 Loads', NULL, 'BH-0057', 1690.00, 1990.00, 51, 0, 10, 4.5, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 57, 'Fresh Linen • 64 Loads', 298),
(1114, 565, 2320, 'Bathroom Cleaner', 'bathroom-cleaner-58', 'Plant-Powered Scrub • 24 oz', NULL, 'BH-0058', 950.00, 1150.00, 50, 0, 10, 4.4, 0, 'Extra 10%', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 58, 'Plant-Powered Scrub • 24 oz', 187),
(1115, 568, 2321, 'Stone Essential Oil Diffuser', 'stone-essential-oil-diffuser-59', 'Ultrasonic • 7 hr Runtime', NULL, 'BH-0059', 2890.00, 3400.00, 50, 0, 10, 4.7, 0, 'Best Seller', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 59, 'Ultrasonic • 7 hr Runtime', 623),
(1116, 567, 2322, 'Lavender Essential Oil', 'lavender-essential-oil-60', '100% Pure CPTG • 15 ml', NULL, 'BH-0060', 2150.00, 2490.00, 50, 0, 10, 4.8, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 60, '100% Pure CPTG • 15 ml', 1542),
(1117, 566, 2323, 'Original Bathroom Spray', 'original-bathroom-spray-61', 'Citrus Fresh • 4 fl oz', NULL, 'BH-0061', 1190.00, 1390.00, 50, 0, 10, 4.6, 0, '20% OFF', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 61, 'Citrus Fresh • 4 fl oz', 2210),
(1118, 570, 2324, 'Cole Sport Water Bottle', 'cole-sport-water-bottle-62', 'BPA-Free Tritan • 20 oz', NULL, 'BH-0062', 890.00, 1050.00, 50, 0, 10, 4.5, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 62, 'BPA-Free Tritan • 20 oz', 341),
(1119, 571, 2325, 'Dish Brush with Holder', 'dish-brush-with-holder-63', 'Hemp Fiber & Bamboo', NULL, 'BH-0063', 750.00, 890.00, 50, 0, 10, 4.4, 0, '', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 63, 'Hemp Fiber & Bamboo', 156),
(1120, 569, 2326, 'Glass Food Storage Set', 'glass-food-storage-set-64', 'Airtight • 5-Piece', NULL, 'BH-0064', 2450.00, 2890.00, 50, 0, 10, 4.6, 0, 'New', '#eef2ed', 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=480&h=480&fit=crop', 0, 0, 0, 0, '2026-10-21 03:57:46', '2026-10-21 03:57:46', NULL, 'approved', NULL, NULL, NULL, NULL, 1, 64, 'Airtight • 5-Piece', 204);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `review` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `id` varchar(80) NOT NULL,
  `title` varchar(190) DEFAULT NULL,
  `applied_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`id`, `title`, `applied_at`) VALUES
('001_base_tables', 'Baseline CMS tables (fresh installs only)', '2026-10-21 09:54:45'),
('002a_settings_normalize', 'Settings table: legacy k/v columns become skey/svalue (+ is_secret, updated_at)', '2026-10-21 09:57:46'),
('002_marketplace_core', 'Marketplace: sellers, commission, ledger, payouts, settings, payment transactions', '2026-10-21 09:54:47'),
('003_catalog_sync', 'Website catalogue now reads from the CMS database (import JSON products once)', '2026-10-21 09:57:46'),
('004_marketplace_hardening', 'Marketplace hardening: commission effective_to, payout proof_file, review_count backfill', '2026-10-21 09:57:46');

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `shop_name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `owner_name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `trade_license` varchar(80) DEFAULT NULL,
  `drug_license` varchar(80) DEFAULT NULL,
  `status` enum('pending','active','rejected','suspended') NOT NULL DEFAULT 'pending',
  `status_reason` varchar(255) DEFAULT NULL,
  `payout_method` enum('bkash','nagad','bank') DEFAULT NULL,
  `payout_account` varchar(120) DEFAULT NULL,
  `payout_account_name` varchar(120) DEFAULT NULL,
  `payout_verified` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_ledger`
--

CREATE TABLE `seller_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `entry_type` enum('sale','refund','payout','payout_reversal','adjustment') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `available_at` datetime NOT NULL,
  `ref_type` varchar(30) DEFAULT NULL,
  `ref_id` bigint(20) DEFAULT NULL,
  `idem_key` varchar(80) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_logs`
--

CREATE TABLE `seller_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `seller_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_order_items`
--

CREATE TABLE `seller_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `discount_alloc` decimal(10,2) NOT NULL DEFAULT 0.00,
  `commission_rate` decimal(5,2) NOT NULL,
  `commission_source` varchar(40) DEFAULT NULL,
  `commission_amount` decimal(10,2) NOT NULL,
  `seller_earning` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','packed','shipped','delivered','cancelled','returned') NOT NULL DEFAULT 'pending',
  `tracking_code` varchar(80) DEFAULT NULL,
  `seller_note` varchar(255) DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `skey` varchar(80) NOT NULL,
  `svalue` mediumtext DEFAULT NULL,
  `is_secret` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`skey`, `svalue`, `is_secret`, `updated_at`) VALUES
('catalog_source', 'db', 0, '2026-10-21 07:57:46'),
('catalog_version', '6b846a53f8d034a39e40ba306b37bc16', 0, '2026-10-21 07:57:46'),
('mp_auto_approve_products', '0', 0, '2026-10-21 07:57:46'),
('mp_default_commission', '10', 0, '2026-10-21 07:57:46'),
('mp_discount_policy', 'platform', 0, '2026-10-21 07:57:46'),
('mp_hold_days', '0', 0, '2026-10-21 08:05:38'),
('mp_min_payout', '100', 0, '2026-10-21 08:05:38'),
('mp_registration_open', '1', 0, '2026-10-21 07:57:46'),
('pay_enabled_bkash', '1', 0, '2026-10-21 07:57:46'),
('pay_enabled_cod', '1', 0, '2026-10-21 07:57:46'),
('pay_enabled_nagad', '1', 0, '2026-10-21 07:57:46'),
('pay_enabled_sslcommerz', '1', 0, '2026-10-21 07:57:46');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `delta` int(11) NOT NULL,
  `type` enum('order_hold','order_release','order_ship','order_return','manual_adjust') NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `order_id`, `delta`, `type`, `note`, `created_at`) VALUES
(1, 1059, 9, -1, 'order_hold', 'Checkout order BH261018C4558B', '2026-10-18 06:04:34'),
(2, 1061, 10, -1, 'order_hold', 'Checkout order BH26101897A60F', '2026-10-18 06:04:54'),
(3, 1063, 11, -1, 'order_hold', 'Checkout order BH26101834E255', '2026-10-18 06:05:13'),
(4, 1063, 12, -1, 'order_hold', 'Checkout order BH26101827AA78', '2026-10-18 06:06:02'),
(5, 1065, 13, -1, 'order_hold', 'Checkout order BH261018F0B781', '2026-10-18 06:06:41'),
(6, 1065, 13, 1, 'order_release', 'payment_failed', '2026-10-18 06:06:41'),
(7, 1065, 14, -2, 'order_hold', 'Checkout order BH2610182EB320', '2026-10-18 06:06:43'),
(8, 1065, 14, 2, 'order_release', 'payment_failed', '2026-10-18 06:06:43'),
(9, 1067, 15, -1, 'order_hold', 'Checkout order BH261018FAAB22', '2026-10-18 06:07:07'),
(11, 1071, 17, -1, 'order_hold', 'Checkout order BH2610183CAE08', '2026-10-18 06:08:21'),
(12, 1060, 18, -1, 'order_hold', 'Checkout order BH261018A7F27C', '2026-10-18 06:22:54'),
(13, 1059, 18, -1, 'order_hold', 'Checkout order BH261018A7F27C', '2026-10-18 06:22:54'),
(14, 1058, 19, -3, 'order_hold', 'Checkout order BH261018F153A7', '2026-10-18 11:17:39'),
(15, 1113, 19, -1, 'order_hold', 'Checkout order BH261018F153A7', '2026-10-18 11:17:39'),
(16, 1059, 20, -1, 'order_hold', 'Checkout order BH26101829C193', '2026-10-18 11:28:14'),
(17, 1069, 21, -1, 'order_hold', 'Checkout order BH261020C3F3FF', '2026-10-20 04:19:09'),
(18, 1064, 21, -1, 'order_hold', 'Checkout order BH261020C3F3FF', '2026-10-20 04:19:09'),
(19, 1092, 22, -1, 'order_hold', 'Checkout order BH261020A4F011', '2026-10-20 04:59:48'),
(20, 1092, 22, 1, 'order_release', 'payment_failed', '2026-10-20 04:59:51'),
(21, 1092, 23, -1, 'order_hold', 'Checkout order BH261020387929', '2026-10-20 04:59:55'),
(112, 1069, 62, -1, 'order_hold', 'Checkout order BH2610200770D3', '2026-10-20 06:03:14'),
(113, 1060, 63, -1, 'order_hold', 'Checkout order BH2610206F5ED4', '2026-10-20 07:47:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('customer','admin','manager','staff') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `status`, `last_login_at`, `last_login_ip`, `created_at`) VALUES
(1, 'System Admin', 'admin@rbspharma.com', '+8801700000001', '$2y$10$xv4t948Q0wKUq5bHd4jA4.X.vr7rVC44OVo.4htaQXAnlYBnTz9Me', 'admin', 'active', '2026-10-21 15:34:25', '::1', '2026-10-17 09:01:58'),
(2, 'Operations Manager', 'manager@rbspharma.com', '+8801700000002', '$2y$10$J6ZUoie2lfH4txxExgUcgOuVwkqpp5aSTM0iee0o.WKCSzcHpteca', 'manager', 'active', '2026-10-21 14:08:47', '::1', '2026-10-17 09:01:58'),
(3, 'Support Staff', 'staff@rbspharma.com', '+8801700000003', '$2y$10$mObrKctIqcpTNYEFLJws9uFMvqylq4djthbBh.bRD3Zy6RJElPx2W', 'staff', 'active', '2026-10-21 14:08:47', '::1', '2026-10-17 09:01:58'),
(4, 'Nusrat Jahan', 'nusrat@example.com', '+8801812345678', '$2y$10$ppQ5MrphPLDbaswshboPbewkHTIrk89TjLDKnJ86kbYXpcSabU0Wm', 'customer', 'active', NULL, NULL, '2026-10-17 09:01:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  
  ADD KEY `idx_log_user` (`user_id`),
  ADD KEY `idx_log_created` (`created_at`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `catalog_stock`
--
ALTER TABLE `catalog_stock`
  ADD PRIMARY KEY (`json_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `commission_rules`
--
ALTER TABLE `commission_rules`
  
  ADD KEY `idx_cr_lookup` (`scope`,`ref_id`,`is_active`,`effective_from`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_coupons_doctor` (`doctor_id`);

--
-- Indexes for table `courier_methods`
--
ALTER TABLE `courier_methods`
  
  ADD UNIQUE KEY `uq_courier_code` (`code`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  
  ADD UNIQUE KEY `uq_doctors_refcode` (`reference_code`),
  ADD UNIQUE KEY `uq_doctors_reg` (`registration_number`),
  ADD UNIQUE KEY `uq_doctors_email` (`email`),
  ADD KEY `idx_doctors_status` (`status`),
  ADD KEY `idx_doctors_verify` (`verification_status`),
  ADD KEY `idx_doctors_name` (`name`),
  ADD KEY `fk_doctors_creator` (`created_by`);
ALTER TABLE `doctors` ADD KEY `ft_doctors` (`name`(100),`specialty`(100));

--
-- Indexes for table `doctor_referrals`
--
ALTER TABLE `doctor_referrals`
  
  ADD KEY `idx_ref_doctor` (`doctor_id`),
  ADD KEY `idx_ref_order` (`order_id`),
  ADD KEY `idx_ref_date` (`referred_at`),
  ADD KEY `fk_ref_customer` (`customer_id`);

--
-- Indexes for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  
  ADD KEY `idx_batch_product` (`product_id`),
  ADD KEY `idx_batch_expiry` (`expiry_date`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  
  ADD KEY `idx_attempt_email` (`email`,`created_at`),
  ADD KEY `idx_attempt_ip` (`ip`,`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  
  ADD UNIQUE KEY `uq_orders_number` (`order_number`),
  ADD UNIQUE KEY `uq_orders_idem` (`idempotency_key`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `fk_orders_courier` (`courier_id`),
  ADD KEY `fk_orders_coupon` (`coupon_id`),
  ADD KEY `fk_orders_rx` (`prescription_id`),
  ADD KEY `idx_orders_val_id` (`val_id`),
  ADD KEY `idx_orders_created` (`created_at`),
  ADD KEY `idx_orders_payment` (`payment_status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_returns`
--
ALTER TABLE `order_returns`
  
  ADD KEY `idx_or_order` (`order_id`);

--
-- Indexes for table `page_content`
--
ALTER TABLE `page_content`
  
  ADD UNIQUE KEY `uq_content_key` (`section_key`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  
  ADD UNIQUE KEY `uq_ptx_ref` (`gateway`,`gateway_ref`),
  ADD KEY `idx_ptx_order` (`order_number`),
  ADD KEY `idx_ptx_status` (`status`,`created_at`);

--
-- Indexes for table `payout_requests`
--
ALTER TABLE `payout_requests`
  
  ADD UNIQUE KEY `uq_payout_ref` (`reference`),
  ADD KEY `idx_pr_status` (`status`,`requested_at`),
  ADD KEY `idx_pr_seller` (`seller_id`,`requested_at`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  
  ADD KEY `idx_rx_status` (`status`),
  ADD KEY `idx_rx_doctor` (`doctor_id`),
  ADD KEY `fk_rx_customer` (`customer_id`),
  ADD KEY `fk_rx_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `uq_products_json` (`json_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `price` (`price`),
  ADD KEY `idx_products_seller` (`seller_id`),
  ADD KEY `idx_products_visible` (`is_active`,`approval_status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  
  ADD UNIQUE KEY `one_review` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `schema_migrations`
--
--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  
  ADD UNIQUE KEY `uq_sellers_slug` (`slug`),
  ADD UNIQUE KEY `uq_sellers_email` (`email`),
  ADD KEY `idx_sellers_status` (`status`);

--
-- Indexes for table `seller_ledger`
--
ALTER TABLE `seller_ledger`
  
  ADD UNIQUE KEY `uq_ledger_idem` (`idem_key`),
  ADD KEY `idx_ledger_seller` (`seller_id`,`available_at`),
  ADD KEY `idx_ledger_created` (`seller_id`,`created_at`);

--
-- Indexes for table `seller_logs`
--
ALTER TABLE `seller_logs`
  
  ADD KEY `idx_sl_seller` (`seller_id`,`created_at`),
  ADD KEY `idx_sl_action` (`action`,`ip`,`created_at`);

--
-- Indexes for table `seller_order_items`
--
ALTER TABLE `seller_order_items`
  
  ADD UNIQUE KEY `uq_soi_item` (`order_item_id`),
  ADD KEY `idx_soi_seller` (`seller_id`,`status`),
  ADD KEY `idx_soi_order` (`order_id`),
  ADD KEY `idx_soi_created` (`seller_id`,`created_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`skey`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  
  ADD KEY `idx_sm_product` (`product_id`),
  ADD KEY `idx_sm_order` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  
  ADD UNIQUE KEY `email` (`email`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `coupons`
--
ALTER TABLE `coupons`
  ADD CONSTRAINT `fk_coupons_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `fk_doctors_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `doctor_referrals`
--
ALTER TABLE `doctor_referrals`
  ADD CONSTRAINT `fk_ref_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ref_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ref_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD CONSTRAINT `fk_batch_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_courier` FOREIGN KEY (`courier_id`) REFERENCES `courier_methods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_rx` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_returns`
--
ALTER TABLE `order_returns`
  ADD CONSTRAINT `fk_or_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payout_requests`
--
ALTER TABLE `payout_requests`
  ADD CONSTRAINT `fk_pr_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `fk_rx_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rx_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rx_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_ledger`
--
ALTER TABLE `seller_ledger`
  ADD CONSTRAINT `fk_ledger_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`);

--
-- Constraints for table `seller_order_items`
--
ALTER TABLE `seller_order_items`
  ADD CONSTRAINT `fk_soi_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `fk_sm_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
