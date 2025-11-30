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
