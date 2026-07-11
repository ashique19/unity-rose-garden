-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 11, 2026 at 12:25 PM
-- Server version: 10.11.18-MariaDB-cll-lve-log
-- PHP Version: 8.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pokaco5_unity`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `bill_for_month` date DEFAULT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `price_per_m3` decimal(10,2) NOT NULL,
  `total_used_m3` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_used_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_bill` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `name`, `bill_for_month`, `price_per_kg`, `price_per_m3`, `total_used_m3`, `total_used_kg`, `total_bill`, `created_at`, `updated_at`) VALUES
(10, 'LPG Gas Bill - May 2026', '2026-05-01', 146.00, 303.68, 45.66, 94.97, 13866.03, '2026-06-04 17:58:14', '2026-06-04 17:58:14'),
(19, 'LPG Gas Bill - June 2026', '2026-06-01', 148.00, 307.59, 113.62, 236.14, 34948.21, '2026-07-01 21:51:43', '2026-07-01 21:51:43');

-- --------------------------------------------------------

--
-- Table structure for table `bill_details`
--

CREATE TABLE `bill_details` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `flat_id` int(11) NOT NULL,
  `previous_reading` decimal(10,2) NOT NULL,
  `current_reading` decimal(10,2) NOT NULL,
  `used_m3` decimal(10,2) NOT NULL,
  `used_kg` decimal(10,2) NOT NULL,
  `bill_for_month` date NOT NULL,
  `payment_status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bill_details`
--

INSERT INTO `bill_details` (`id`, `bill_id`, `flat_id`, `previous_reading`, `current_reading`, `used_m3`, `used_kg`, `bill_for_month`, `payment_status`, `created_at`, `updated_at`) VALUES
(152, 10, 1, 37.40, 40.73, 3.33, 6.93, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-12 20:08:55'),
(153, 10, 2, 22.46, 23.09, 0.63, 1.31, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:10:29'),
(154, 10, 4, 42.62, 44.57, 1.95, 4.06, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:08:08'),
(155, 10, 5, 82.64, 87.56, 4.92, 10.23, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:08:31'),
(156, 10, 6, 2.41, 8.06, 5.65, 11.75, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-14 23:22:40'),
(157, 10, 9, 37.25, 38.75, 1.50, 3.12, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:08:54'),
(158, 10, 10, 26.16, 26.94, 0.78, 1.62, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:09:00'),
(159, 10, 11, 60.60, 62.91, 2.31, 4.80, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:09:10'),
(160, 10, 12, 99.46, 103.84, 4.38, 9.11, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:09:16'),
(161, 10, 13, 58.36, 62.92, 4.56, 9.48, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:09:24'),
(162, 10, 14, 37.90, 38.68, 0.78, 1.62, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:09:29'),
(163, 10, 15, 49.84, 50.33, 0.49, 1.02, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:09:35'),
(164, 10, 16, 44.91, 48.47, 3.56, 7.40, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:09:39'),
(165, 10, 17, 3.08, 9.72, 6.64, 13.81, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:07:20'),
(166, 10, 18, 96.22, 100.40, 4.18, 8.69, '2026-05-01', 'paid', '2026-06-04 17:58:14', '2026-06-11 22:07:14'),
(287, 19, 1, 40.73, 46.28, 5.55, 11.53, '2026-06-01', 'unpaid', '2026-07-01 21:51:43', '2026-07-01 21:51:43'),
(288, 19, 2, 23.09, 23.09, 0.00, 0.00, '2026-06-01', 'unpaid', '2026-07-01 21:51:43', '2026-07-01 21:51:43'),
(289, 19, 4, 44.57, 50.00, 5.43, 11.29, '2026-06-01', 'unpaid', '2026-07-01 21:51:43', '2026-07-01 21:51:43'),
(290, 19, 5, 87.56, 98.46, 10.90, 22.65, '2026-06-01', 'unpaid', '2026-07-01 21:51:43', '2026-07-01 21:51:43'),
(291, 19, 6, 8.06, 19.19, 11.13, 23.13, '2026-06-01', 'unpaid', '2026-07-01 21:51:43', '2026-07-01 21:51:43'),
(292, 19, 9, 38.75, 43.45, 4.70, 9.77, '2026-06-01', 'unpaid', '2026-07-01 21:51:43', '2026-07-01 21:51:43'),
(293, 19, 10, 26.94, 31.81, 4.87, 10.12, '2026-06-01', 'unpaid', '2026-07-01 21:51:43', '2026-07-01 21:51:43'),
(294, 19, 11, 62.91, 69.31, 6.40, 13.30, '2026-06-01', 'unpaid', '2026-07-01 21:51:43', '2026-07-01 21:51:43'),
(295, 19, 12, 103.84, 114.21, 10.37, 21.55, '2026-06-01', 'unpaid', '2026-07-01 21:51:44', '2026-07-01 21:51:44'),
(296, 19, 13, 62.92, 73.02, 10.10, 20.99, '2026-06-01', 'unpaid', '2026-07-01 21:51:44', '2026-07-01 21:51:44'),
(297, 19, 14, 38.68, 44.50, 5.82, 12.10, '2026-06-01', 'unpaid', '2026-07-01 21:51:44', '2026-07-01 21:51:44'),
(298, 19, 15, 50.33, 56.87, 6.54, 13.59, '2026-06-01', 'unpaid', '2026-07-01 21:51:44', '2026-07-01 21:51:44'),
(299, 19, 16, 48.47, 57.66, 9.19, 19.10, '2026-06-01', 'unpaid', '2026-07-01 21:51:44', '2026-07-01 21:51:44'),
(300, 19, 17, 9.72, 20.45, 10.73, 22.30, '2026-06-01', 'unpaid', '2026-07-01 21:51:44', '2026-07-01 21:51:44'),
(301, 19, 18, 100.40, 112.29, 11.89, 24.71, '2026-06-01', 'unpaid', '2026-07-01 21:51:44', '2026-07-01 21:51:44');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(191) NOT NULL,
  `value` longtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(191) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) NOT NULL,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flats`
--

CREATE TABLE `flats` (
  `id` int(11) NOT NULL,
  `name` varchar(45) NOT NULL,
  `status` enum('online','offline') NOT NULL DEFAULT 'online',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `flats`
--

INSERT INTO `flats` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, '2A', 'online', NULL, NULL),
(2, '2B', 'online', NULL, NULL),
(3, '3A', 'offline', NULL, '2026-06-04 16:15:46'),
(4, '3B', 'online', NULL, NULL),
(5, '4A', 'online', NULL, NULL),
(6, '4B', 'online', NULL, NULL),
(7, '5A', 'offline', NULL, NULL),
(8, '5B', 'offline', NULL, NULL),
(9, '6A', 'online', NULL, NULL),
(10, '6B', 'online', NULL, NULL),
(11, '7A', 'online', NULL, NULL),
(12, '7B', 'online', NULL, NULL),
(13, '8A', 'online', NULL, NULL),
(14, '8B', 'online', NULL, NULL),
(15, '9A', 'online', NULL, NULL),
(16, '9B', 'online', NULL, NULL),
(17, '10A', 'online', NULL, NULL),
(18, '10B', 'online', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) NOT NULL,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(4) NOT NULL,
  `reserved_at` int(11) DEFAULT NULL,
  `available_at` int(11) NOT NULL,
  `created_at` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(191) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` text DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meter_readings`
--

CREATE TABLE `meter_readings` (
  `id` int(11) NOT NULL,
  `flat_id` int(11) NOT NULL,
  `reading_date` date NOT NULL,
  `reading_unit` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meter_readings`
--

INSERT INTO `meter_readings` (`id`, `flat_id`, `reading_date`, `reading_unit`, `created_at`, `updated_at`) VALUES
(1, 17, '2026-04-30', 3.08, '2026-06-04 03:35:01', '2026-06-04 03:35:01'),
(2, 18, '2026-04-30', 96.22, '2026-06-04 03:35:20', '2026-06-04 03:35:20'),
(3, 1, '2026-04-30', 37.40, '2026-06-04 03:35:43', '2026-06-04 03:35:43'),
(4, 2, '2026-04-30', 22.46, '2026-06-04 03:36:04', '2026-06-04 03:36:04'),
(5, 3, '2026-04-30', 0.00, '2026-06-04 03:36:20', '2026-06-04 03:36:20'),
(6, 4, '2026-04-30', 42.62, '2026-06-04 03:36:40', '2026-06-04 03:36:40'),
(7, 5, '2026-04-30', 82.64, '2026-06-04 03:37:01', '2026-06-04 03:37:01'),
(11, 6, '2026-04-30', 2.41, '2026-06-04 03:40:00', '2026-06-04 03:40:00'),
(12, 7, '2026-04-30', 21.18, '2026-06-04 03:40:15', '2026-06-04 03:40:15'),
(13, 8, '2026-04-30', 49.98, '2026-06-04 03:40:31', '2026-06-04 03:40:31'),
(14, 9, '2026-04-30', 37.25, '2026-06-04 03:40:54', '2026-06-04 03:40:54'),
(15, 10, '2026-04-30', 26.16, '2026-06-04 03:41:13', '2026-06-04 03:41:13'),
(16, 11, '2026-04-30', 60.60, '2026-06-04 03:41:28', '2026-06-04 03:41:28'),
(17, 12, '2026-04-30', 99.46, '2026-06-04 03:41:43', '2026-06-04 03:41:43'),
(18, 13, '2026-04-30', 58.36, '2026-06-04 03:41:59', '2026-06-04 03:41:59'),
(19, 14, '2026-04-30', 37.90, '2026-06-04 03:42:20', '2026-06-04 03:42:20'),
(20, 15, '2026-04-30', 49.84, '2026-06-04 03:42:40', '2026-06-04 03:42:40'),
(21, 16, '2026-04-30', 44.91, '2026-06-04 03:42:53', '2026-06-04 03:42:53'),
(42, 9, '2026-05-31', 38.75, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(43, 10, '2026-05-31', 26.94, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(44, 4, '2026-05-31', 44.57, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(45, 5, '2026-05-31', 87.56, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(46, 7, '2026-05-31', 22.08, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(47, 2, '2026-05-31', 23.09, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(48, 11, '2026-05-31', 62.91, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(49, 3, '2026-05-31', 0.00, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(50, 12, '2026-05-31', 103.84, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(51, 17, '2026-05-31', 9.72, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(52, 13, '2026-05-31', 62.92, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(53, 6, '2026-05-31', 8.06, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(54, 15, '2026-05-31', 50.33, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(55, 14, '2026-05-31', 38.68, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(56, 18, '2026-05-31', 100.40, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(57, 16, '2026-05-31', 48.47, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(58, 1, '2026-05-31', 40.73, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(59, 8, '2026-05-31', 53.01, '2026-06-04 13:48:54', '2026-06-04 13:48:54'),
(259, 16, '2026-06-30', 57.66, '2026-06-30 23:21:30', '2026-06-30 23:21:30'),
(260, 14, '2026-06-30', 44.50, '2026-06-30 23:21:47', '2026-06-30 23:21:47'),
(261, 12, '2026-06-30', 114.21, '2026-06-30 23:22:04', '2026-06-30 23:22:04'),
(262, 10, '2026-06-30', 31.81, '2026-06-30 23:22:19', '2026-06-30 23:22:19'),
(263, 8, '2026-06-30', 61.92, '2026-06-30 23:22:38', '2026-06-30 23:22:38'),
(264, 6, '2026-06-30', 19.19, '2026-06-30 23:22:56', '2026-06-30 23:22:56'),
(265, 4, '2026-06-30', 50.00, '2026-06-30 23:23:12', '2026-06-30 23:23:12'),
(267, 1, '2026-06-30', 46.28, '2026-06-30 23:23:50', '2026-06-30 23:23:50'),
(268, 3, '2026-06-30', 0.00, '2026-06-30 23:24:04', '2026-06-30 23:24:04'),
(269, 5, '2026-06-30', 98.46, '2026-06-30 23:24:25', '2026-06-30 23:24:25'),
(270, 7, '2026-06-30', 28.39, '2026-06-30 23:24:39', '2026-06-30 23:24:39'),
(271, 9, '2026-06-30', 43.45, '2026-06-30 23:25:02', '2026-06-30 23:25:02'),
(272, 11, '2026-06-30', 69.31, '2026-06-30 23:25:20', '2026-06-30 23:25:20'),
(273, 13, '2026-06-30', 73.02, '2026-06-30 23:25:33', '2026-06-30 23:25:33'),
(274, 15, '2026-06-30', 56.87, '2026-06-30 23:25:48', '2026-06-30 23:25:48'),
(275, 17, '2026-06-30', 20.45, '2026-06-30 23:26:01', '2026-06-30 23:26:01'),
(276, 18, '2026-06-30', 112.29, '2026-06-30 23:26:19', '2026-06-30 23:26:19'),
(277, 2, '2026-06-30', 23.09, '2026-07-01 09:20:08', '2026-07-01 21:33:23');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(11) NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(57, '0001_01_01_000000_create_users_table', 1),
(58, '0001_01_01_000001_create_cache_table', 1),
(59, '0001_01_01_000002_create_jobs_table', 1),
(60, '2026_05_22_115951_create_flats_table', 1),
(61, '2026_05_22_121744_create_meter_readings_table', 1),
(62, '2026_05_24_000247_create_bills_table', 1),
(63, '2026_05_24_000341_create_bill_details_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('UCtCxBwTML7LPlplsQVzfeRMRSCE9hOg6EHbdP23', 1, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJQakRDZVNnWnV6cWlEU01xZHVwc3UyODFuMHBGTXZvUzByVldIV25WIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9tZXRlci1yZWFkaW5ncyIsInJvdXRlIjoibWV0ZXItcmVhZGluZ3MuaW5kZXgifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=', 1780520322);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(45) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Ashique', '01785636359', '$2y$12$KQFLROMBXqTJPbLb2eHseePEXsglxxkwVjcXTmoprYzTpEhYYnHru', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bill_details`
--
ALTER TABLE `bill_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`),
  ADD KEY `flat_id` (`flat_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`failed_at`);

--
-- Indexes for table `flats`
--
ALTER TABLE `flats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meter_readings`
--
ALTER TABLE `meter_readings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `flat_month_unique` (`flat_id`,`reading_date`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `bill_details`
--
ALTER TABLE `bill_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=302;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flats`
--
ALTER TABLE `flats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meter_readings`
--
ALTER TABLE `meter_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bill_details`
--
ALTER TABLE `bill_details`
  ADD CONSTRAINT `bill_details_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bill_details_ibfk_2` FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meter_readings`
--
ALTER TABLE `meter_readings`
  ADD CONSTRAINT `meter_readings_ibfk_1` FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
