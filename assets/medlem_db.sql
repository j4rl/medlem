-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Värd: 127.0.0.1
-- Tid vid skapande: 24 nov 2025 kl 16:31
-- Serverversion: 10.4.32-MariaDB
-- PHP-version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databas: `medlem_db`
--
-- NOTE: Encrypted columns are stored as ciphertext (base64-safe) to avoid
-- charset/collation issues.

-- --------------------------------------------------------

--
-- Tabellstruktur `tbl_cases`
--

CREATE TABLE `tbl_cases` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `changed` timestamp NOT NULL DEFAULT current_timestamp(),
  `caseheader` blob NOT NULL COMMENT 'Twofish-encrypted data',
  `taker_id` int(11) DEFAULT NULL,
  `member_data` mediumblob NOT NULL COMMENT 'Twofish-encrypted member snapshot (JSON before encryption)',
  `case_data` mediumblob NOT NULL COMMENT 'Twofish-encrypted case JSON payload',
  `status` enum('new','in_progress','resolved','closed','reopened') NOT NULL DEFAULT 'new',
  `prio` enum('low','medium','high','urgent','none') NOT NULL DEFAULT 'medium',
  `created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur `tbl_colors`
--

CREATE TABLE `tbl_colors` (
  `id` int(11) NOT NULL,
  `bg_light` varchar(100) NOT NULL,
  `bg_dark` varchar(100) NOT NULL,
  `surface_light` varchar(100) NOT NULL,
  `surface_dark` varchar(100) NOT NULL,
  `primary_light` varchar(100) NOT NULL,
  `primary_dark` varchar(100) NOT NULL,
  `accent_light` varchar(100) NOT NULL,
  `accent_dark` varchar(100) NOT NULL,
  `text_light` varchar(100) NOT NULL,
  `text_dark` varchar(100) NOT NULL,
  `muted_light` varchar(100) NOT NULL,
  `muted_dark` varchar(100) NOT NULL,
  `border_light` varchar(100) NOT NULL,
  `border_dark` varchar(100) NOT NULL,
  `theme_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur `tbl_members`
--

CREATE TABLE `tbl_members` (
  `id` int(11) NOT NULL,
  `link` text DEFAULT NULL,
  `medlnr` int(11) NOT NULL COMMENT 'Membership number',
  `namn` text DEFAULT NULL,
  `fodelsedatum` text DEFAULT NULL,
  `forening` text DEFAULT NULL,
  `medlemsform` text DEFAULT NULL,
  `befattning` text DEFAULT NULL,
  `verksamhetsform` text DEFAULT NULL,
  `arbetsplats` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur `tbl_member_imports`
--

CREATE TABLE `tbl_member_imports` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `imported_by` int(11) DEFAULT NULL,
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `inserted_rows` int(11) NOT NULL DEFAULT 0,
  `updated_rows` int(11) NOT NULL DEFAULT 0,
  `skipped_rows` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int(11) NOT NULL,
  `username` varchar(12) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(100) NOT NULL,
  `pic` varchar(255) NOT NULL DEFAULT 'default.png',
  `lang` varchar(5) NOT NULL DEFAULT 'sv',
  `colorscheme` int(11) NOT NULL DEFAULT 1,
  `last_login` int(11) NOT NULL DEFAULT current_timestamp(),
  `userlevel` int(11) NOT NULL DEFAULT 10,
  `role` enum('Admin','Användare','','') NOT NULL DEFAULT 'Användare'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index för dumpade tabeller
--

--
-- Index för tabell `tbl_cases`
--
ALTER TABLE `tbl_cases`
  ADD PRIMARY KEY (`id`);

--
-- Index för tabell `tbl_colors`
--
ALTER TABLE `tbl_colors`
  ADD PRIMARY KEY (`id`);

--
-- Index för tabell `tbl_members`
--
ALTER TABLE `tbl_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_medlnr` (`medlnr`);

--
-- Index för tabell `tbl_member_imports`
--
ALTER TABLE `tbl_member_imports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `imported_by` (`imported_by`);

--
-- Index för tabell `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT för dumpade tabeller
--

--
-- AUTO_INCREMENT för tabell `tbl_cases`
--
ALTER TABLE `tbl_cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT för tabell `tbl_colors`
--
ALTER TABLE `tbl_colors`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Standarddata f��r tabell `tbl_colors`
--
INSERT INTO `tbl_colors` (`id`, `bg_light`, `bg_dark`, `surface_light`, `surface_dark`, `primary_light`, `primary_dark`, `accent_light`, `accent_dark`, `text_light`, `text_dark`, `muted_light`, `muted_dark`, `border_light`, `border_dark`, `theme_name`) VALUES
(1, '#f6f7fb', '#0b1220', '#ffffff', '#111827', '#2563eb', '#60a5fa', '#0ea5e9', '#38bdf8', '#0f172a', '#e5e7eb', '#6b7280', '#9ca3af', '#e5e7eb', '#1f2937', 'Nordic Blue'),
(2, '#f5f8f3', '#0f1c14', '#ffffff', '#12231a', '#15803d', '#22c55e', '#65a30d', '#a3e635', '#0b2215', '#e6f4ec', '#4b5563', '#9ca3af', '#d1d5db', '#1f2d24', 'Forest'),
(3, '#fff7ed', '#1a0f0a', '#fff3e6', '#26130c', '#f97316', '#fdba74', '#ec4899', '#f472b6', '#2a1405', '#fef3e8', '#a16246', '#e5b89a', '#fcd9bd', '#3b1d13', 'Sunset Glow'),
(4, '#f7f5ff', '#0f1024', '#ffffff', '#161632', '#7c3aed', '#a78bfa', '#f472b6', '#e879f9', '#1f172a', '#ede9fe', '#6b7280', '#a5b4fc', '#e4e4f7', '#262750', 'Lavender Mist'),
(5, '#f0f9ff', '#0b1720', '#ffffff', '#0f2530', '#0ea5e9', '#38bdf8', '#14b8a6', '#2dd4bf', '#0b1220', '#e2f3ff', '#4b5563', '#94a3b8', '#dbeafe', '#1f3340', 'Ocean Breeze'),
(6, '#fff1f2', '#1f0f13', '#ffffff', '#24141a', '#e11d48', '#f472b6', '#f59e0b', '#fbbf24', '#2e0f17', '#fde2e7', '#9f616a', '#f3bec8', '#fbcfe8', '#3b1c26', 'Rose Quartz'),
(7, '#f3f4f6', '#0e1116', '#ffffff', '#1f2933', '#4b5563', '#9ca3af', '#2563eb', '#60a5fa', '#111827', '#e5e7eb', '#6b7280', '#9ca3af', '#e5e7eb', '#2b3540', 'Slate'),
(8, '#fef7e5', '#20160d', '#fffaf0', '#2a1c12', '#d97706', '#fbbf24', '#10b981', '#22c55e', '#2f1a0f', '#f5e9d7', '#7c6f64', '#c7b8a6', '#eddcc8', '#3a2718', 'Desert Sun')
ON DUPLICATE KEY UPDATE
`bg_light`=VALUES(`bg_light`),`bg_dark`=VALUES(`bg_dark`),`surface_light`=VALUES(`surface_light`),`surface_dark`=VALUES(`surface_dark`),`primary_light`=VALUES(`primary_light`),`primary_dark`=VALUES(`primary_dark`),`accent_light`=VALUES(`accent_light`),`accent_dark`=VALUES(`accent_dark`),`text_light`=VALUES(`text_light`),`text_dark`=VALUES(`text_dark`),`muted_light`=VALUES(`muted_light`),`muted_dark`=VALUES(`muted_dark`),`border_light`=VALUES(`border_light`),`border_dark`=VALUES(`border_dark`),`theme_name`=VALUES(`theme_name`);

--
-- AUTO_INCREMENT för tabell `tbl_members`
--
ALTER TABLE `tbl_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT för tabell `tbl_member_imports`
--
ALTER TABLE `tbl_member_imports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT för tabell `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
