-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 12 أبريل 2025 الساعة 00:25
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hajj_umrah_platform`
--

-- --------------------------------------------------------

--
-- بنية الجدول `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(15, 1, 'register', NULL, '127.0.0.1', '2025-04-11 20:44:30'),
(16, 1, 'login', NULL, '127.0.0.1', '2025-04-11 20:51:05'),
(17, 1, 'create_group', '{\"group_id\":2}', '127.0.0.1', '2025-04-11 20:53:00'),
(18, 1, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-11 20:53:26'),
(19, 1, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-11 20:54:26'),
(20, 1, 'logout', NULL, '127.0.0.1', '2025-04-11 20:55:17'),
(21, 2, 'login', NULL, '127.0.0.1', '2025-04-11 20:55:40'),
(22, 1, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-11 20:56:03'),
(23, 2, 'login', NULL, '127.0.0.1', '2025-04-11 21:20:02'),
(24, 2, 'logout', NULL, '127.0.0.1', '2025-04-11 21:37:35'),
(25, 2, 'login', NULL, '127.0.0.1', '2025-04-11 21:37:39'),
(26, 2, 'logout', NULL, '127.0.0.1', '2025-04-11 21:39:16'),
(27, 1, 'login', NULL, '127.0.0.1', '2025-04-11 21:41:14'),
(28, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 00:23:58'),
(29, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 00:23:59'),
(30, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 00:23:59'),
(31, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 00:23:59'),
(32, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 00:39:10'),
(33, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 00:39:14'),
(34, 2, 'create_group', '{\"group_id\":3}', '127.0.0.1', '2025-04-12 00:44:22'),
(35, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 00:44:34'),
(36, 2, 'create_user', '{\"user_id\":3}', '127.0.0.1', '2025-04-12 00:46:10'),
(37, 2, 'create_service_provider', '{\"provider_id\":6}', '127.0.0.1', '2025-04-12 00:49:31'),
(38, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 01:13:25'),
(39, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 01:13:26'),
(40, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 01:13:26'),
(41, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 01:13:26'),
(42, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 01:14:01'),
(43, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 01:14:01'),
(44, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 01:14:01'),
(45, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 01:14:02'),
(46, 2, 'share_location_all_groups', NULL, '127.0.0.1', '2025-04-12 01:15:26');

-- --------------------------------------------------------

--
-- بنية الجدول `app_feedback`
--

CREATE TABLE `app_feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `category` varchar(50) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `emergencies`
--

CREATE TABLE `emergencies` (
  `id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `type` enum('medical','missing_person','security','other') NOT NULL,
  `description` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `status` enum('requested','in_progress','resolved','cancelled') NOT NULL DEFAULT 'requested',
  `handled_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `invite_code` varchar(20) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `groups`
--

INSERT INTO `groups` (`id`, `name`, `description`, `created_by`, `invite_code`, `active`, `created_at`, `updated_at`) VALUES
(2, 'a', 'aaaaaaa', 1, 'A4QwRBlG', 1, '2025-04-11 20:53:00', '2025-04-11 20:53:00'),
(3, 'aaaa', 'aaaaaaaaaa', 2, 'DisayOWR', 1, '2025-04-12 00:44:22', '2025-04-12 00:44:22');

-- --------------------------------------------------------

--
-- بنية الجدول `group_members`
--

CREATE TABLE `group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `group_members`
--

INSERT INTO `group_members` (`id`, `group_id`, `user_id`, `joined_at`) VALUES
(2, 2, 1, '2025-04-11 20:53:00'),
(3, 3, 2, '2025-04-12 00:44:22');

-- --------------------------------------------------------

--
-- بنية الجدول `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `accuracy` float DEFAULT NULL,
  `altitude` float DEFAULT NULL,
  `heading` float DEFAULT NULL,
  `speed` float DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `locations`
--

INSERT INTO `locations` (`id`, `user_id`, `latitude`, `longitude`, `accuracy`, `altitude`, `heading`, `speed`, `timestamp`) VALUES
(13, 1, 15.33870080, 44.20075520, NULL, NULL, NULL, NULL, '2025-04-11 20:53:26'),
(14, 1, 15.33870080, 44.20075520, NULL, NULL, NULL, NULL, '2025-04-11 20:54:26'),
(15, 1, 15.33870080, 44.20075520, NULL, NULL, NULL, NULL, '2025-04-11 20:56:03'),
(16, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 00:23:58'),
(17, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 00:23:59'),
(18, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 00:23:59'),
(19, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 00:23:59'),
(20, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 00:39:10'),
(21, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 00:39:14'),
(22, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 00:44:34'),
(23, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 01:13:25'),
(24, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 01:13:26'),
(25, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 01:13:26'),
(26, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 01:13:26'),
(27, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 01:14:01'),
(28, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 01:14:01'),
(29, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 01:14:01'),
(30, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 01:14:02'),
(31, 2, 15.35220000, 44.20950000, NULL, NULL, NULL, NULL, '2025-04-12 01:15:26');

-- --------------------------------------------------------

--
-- بنية الجدول `missing_persons`
--

CREATE TABLE `missing_persons` (
  `id` int(11) NOT NULL,
  `emergency_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `last_seen_location` varchar(255) DEFAULT NULL,
  `last_seen_time` datetime DEFAULT NULL,
  `found_location` varchar(255) DEFAULT NULL,
  `found_time` datetime DEFAULT NULL,
  `additional_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `places_of_interest`
--

CREATE TABLE `places_of_interest` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('masjid','historical','ritual','other') NOT NULL,
  `description` text DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `opening_hours` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `places_of_interest`
--

INSERT INTO `places_of_interest` (`id`, `name`, `category`, `description`, `latitude`, `longitude`, `address`, `opening_hours`, `image`, `created_at`, `updated_at`) VALUES
(1, 'Masjid al-Haram', 'masjid', 'The Great Mosque of Mecca is the largest mosque in the world and surrounds Islam\'s holiest place, the Kaaba.', 21.42250000, 39.82620000, 'Mecca, Saudi Arabia', NULL, NULL, '2025-04-11 20:06:11', '2025-04-11 20:06:11'),
(2, 'Masjid al-Nabawi', 'masjid', 'The Prophet\'s Mosque is the second holiest site in Islam and is the final resting place of the Prophet Muhammad.', 24.46720000, 39.61110000, 'Medina, Saudi Arabia', NULL, NULL, '2025-04-11 20:06:11', '2025-04-11 20:06:11'),
(3, 'Mount Arafat', 'ritual', 'A granite hill east of Mecca where pilgrims gather to pray during the Hajj pilgrimage.', 21.35530000, 39.98410000, 'Mecca, Saudi Arabia', NULL, NULL, '2025-04-11 20:06:11', '2025-04-11 20:06:11'),
(4, 'Muzdalifah', 'ritual', 'An open, level area near Mecca where pilgrims spend the night during Hajj.', 21.40410000, 39.93620000, 'Mecca, Saudi Arabia', NULL, NULL, '2025-04-11 20:06:11', '2025-04-11 20:06:11'),
(5, 'Mina', 'ritual', 'A neighborhood of Mecca where pilgrims perform the ritual stoning of the devil during Hajj.', 21.41330000, 39.89330000, 'Mecca, Saudi Arabia', NULL, NULL, '2025-04-11 20:06:11', '2025-04-11 20:06:11'),
(6, 'Jamarat', 'ritual', 'Three stone pillars which represent the devil that are ritually stoned during the annual Hajj pilgrimage.', 21.42320000, 39.87620000, 'Mina, Mecca, Saudi Arabia', NULL, NULL, '2025-04-11 20:06:11', '2025-04-11 20:06:11');

-- --------------------------------------------------------

--
-- بنية الجدول `request_notes`
--

CREATE TABLE `request_notes` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `notes` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `ritual_checkpoints`
--

CREATE TABLE `ritual_checkpoints` (
  `id` int(11) NOT NULL,
  `ritual_progress_id` int(11) NOT NULL,
  `checkpoint_type` varchar(50) NOT NULL,
  `checkpoint_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checkpoint_value`)),
  `notes` text DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `ritual_progress`
--

CREATE TABLE `ritual_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ritual_type` enum('umrah','hajj') NOT NULL,
  `ihram_completed` tinyint(1) DEFAULT 0,
  `tawaf_rounds` int(11) DEFAULT 0,
  `sai_rounds` int(11) DEFAULT 0,
  `halq_completed` tinyint(1) DEFAULT 0,
  `arafat_completed` tinyint(1) DEFAULT 0,
  `muzdalifah_completed` tinyint(1) DEFAULT 0,
  `mina_completed` tinyint(1) DEFAULT 0,
  `jamarat_completed` tinyint(1) DEFAULT 0,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `service_providers`
--

CREATE TABLE `service_providers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `service_type` enum('cart','wheelchair','guide','medical','transport','other') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `last_location_update` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `service_providers`
--

INSERT INTO `service_providers` (`id`, `name`, `service_type`, `phone`, `email`, `license_number`, `available`, `latitude`, `longitude`, `last_location_update`, `created_at`, `updated_at`) VALUES
(1, 'Makkah Cart Services', 'cart', '+966512345678', 'carts@example.com', 'C12345', 1, NULL, NULL, NULL, '2025-04-11 20:06:11', '2025-04-11 20:06:11'),
(2, 'Mobility Assistance', 'wheelchair', '+966523456789', 'mobility@example.com', 'W23456', 1, NULL, NULL, NULL, '2025-04-11 20:06:11', '2025-04-11 20:06:11'),
(3, 'Hajj Guides Association', 'guide', '+966534567890', 'guides@example.com', 'G34567', 1, NULL, NULL, NULL, '2025-04-11 20:06:11', '2025-04-11 20:06:11'),
(4, 'Emergency Medical Team', 'medical', '+966545678901', 'medical@example.com', 'M45678', 1, NULL, NULL, NULL, '2025-04-11 20:06:11', '2025-04-11 20:06:11'),
(5, 'Transport Services', 'transport', '+966556789012', 'transport@example.com', 'T56789', 1, NULL, NULL, NULL, '2025-04-11 20:06:11', '2025-04-11 20:06:11'),
(6, 'Esoft', 'cart', '0775346074', 'aaa@gmail.com', 'aaaaaaaa', 1, NULL, NULL, NULL, '2025-04-12 00:49:31', '2025-04-12 00:49:31');

-- --------------------------------------------------------

--
-- بنية الجدول `service_ratings`
--

CREATE TABLE `service_ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_request_id` int(11) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comments` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `service_type` enum('cart','wheelchair','guide','medical','transport','other') NOT NULL,
  `status` enum('requested','accepted','in_progress','completed','cancelled') NOT NULL DEFAULT 'requested',
  `pickup_location` varchar(255) DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `special_needs` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `num_passengers` int(11) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `accepted_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `passport_number` varchar(30) DEFAULT NULL,
  `user_type` enum('pilgrim','guardian','authority','admin') NOT NULL DEFAULT 'pilgrim',
  `profile_image` varchar(255) DEFAULT NULL,
  `language` varchar(10) DEFAULT 'en',
  `active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `email`, `password`, `phone`, `nationality`, `passport_number`, `user_type`, `profile_image`, `language`, `active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'ee', 'e', 'aa@gmail.com', '$2y$10$oDuJDt/Eqn.ANDgozekbjOsrZ9rq6x/SIo4hCT5uiDpnfq3ykVnU.', '555555555555', 'Turkey', '22222223', 'pilgrim', 'user_67f954fdee149.jpg', 'en', 1, '2025-04-11 21:41:14', '2025-04-11 20:44:30', '2025-04-11 21:41:14'),
(2, 'admin', 'e', 'admin@gmail.com', '$2y$10$oDuJDt/Eqn.ANDgozekbjOsrZ9rq6x/SIo4hCT5uiDpnfq3ykVnU.', '555555555555', 'Turkey', '22222223', 'admin', 'user_67f954fdee149.jpg', 'en', 1, '2025-04-11 21:37:39', '2025-04-11 20:44:30', '2025-04-11 21:37:39'),
(3, '', 'Esoft Ahmed', 'aaa@gmail.com', '$2y$10$ohN5AnDhcKsjCkM8gH5cqur77QfdY6NWvCcqOWRF8jObRsjynRk1q', '0775346074', 'India', NULL, 'pilgrim', NULL, 'en', 1, NULL, '2025-04-12 00:46:10', '2025-04-12 00:46:10');

-- --------------------------------------------------------

--
-- بنية الجدول `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `user_tokens`
--

INSERT INTO `user_tokens` (`id`, `user_id`, `token`, `expires`, `created_at`) VALUES
(1, 1, 'PUa4yTVrF2oJhNwhZCgDUEHQBN54yPMd', '2025-05-11 19:51:05', '2025-04-11 20:51:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `app_feedback`
--
ALTER TABLE `app_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `emergencies`
--
ALTER TABLE `emergencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `handled_by` (`handled_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invite_code` (`invite_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_invite_code` (`invite_code`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_group_member` (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_timestamp` (`user_id`,`timestamp`);

--
-- Indexes for table `missing_persons`
--
ALTER TABLE `missing_persons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emergency_id` (`emergency_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires`);

--
-- Indexes for table `places_of_interest`
--
ALTER TABLE `places_of_interest`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `request_notes`
--
ALTER TABLE `request_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `ritual_checkpoints`
--
ALTER TABLE `ritual_checkpoints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ritual_checkpoint` (`ritual_progress_id`,`checkpoint_type`);

--
-- Indexes for table `ritual_progress`
--
ALTER TABLE `ritual_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_ritual` (`user_id`,`ritual_type`);

--
-- Indexes for table `service_providers`
--
ALTER TABLE `service_providers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service_type` (`service_type`),
  ADD KEY `idx_available` (`available`);

--
-- Indexes for table `service_ratings`
--
ALTER TABLE `service_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_request_rating` (`user_id`,`service_request_id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_service_type` (`service_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_role` (`user_type`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_setting` (`user_id`,`setting_key`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `app_feedback`
--
ALTER TABLE `app_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emergencies`
--
ALTER TABLE `emergencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `missing_persons`
--
ALTER TABLE `missing_persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `places_of_interest`
--
ALTER TABLE `places_of_interest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `request_notes`
--
ALTER TABLE `request_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ritual_checkpoints`
--
ALTER TABLE `ritual_checkpoints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ritual_progress`
--
ALTER TABLE `ritual_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_providers`
--
ALTER TABLE `service_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `service_ratings`
--
ALTER TABLE `service_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `app_feedback`
--
ALTER TABLE `app_feedback`
  ADD CONSTRAINT `app_feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `emergencies`
--
ALTER TABLE `emergencies`
  ADD CONSTRAINT `emergencies_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emergencies_ibfk_2` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `missing_persons`
--
ALTER TABLE `missing_persons`
  ADD CONSTRAINT `missing_persons_ibfk_1` FOREIGN KEY (`emergency_id`) REFERENCES `emergencies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `request_notes`
--
ALTER TABLE `request_notes`
  ADD CONSTRAINT `request_notes_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `ritual_checkpoints`
--
ALTER TABLE `ritual_checkpoints`
  ADD CONSTRAINT `ritual_checkpoints_ibfk_1` FOREIGN KEY (`ritual_progress_id`) REFERENCES `ritual_progress` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `ritual_progress`
--
ALTER TABLE `ritual_progress`
  ADD CONSTRAINT `ritual_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `service_ratings`
--
ALTER TABLE `service_ratings`
  ADD CONSTRAINT `service_ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_ratings_ibfk_2` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_ratings_ibfk_3` FOREIGN KEY (`provider_id`) REFERENCES `service_providers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `service_providers` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
