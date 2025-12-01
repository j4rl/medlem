-- Medlem schema (merged)
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE IF NOT EXISTS `tbl_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(100) NOT NULL DEFAULT '',
  `pic` varchar(255) NOT NULL DEFAULT 'default.png',
  `lang` varchar(5) NOT NULL DEFAULT 'sv',
  `colorscheme` int(11) NOT NULL DEFAULT 1,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp(),
  `userlevel` int(11) NOT NULL DEFAULT 10,
  `role` enum('Admin','Användare') NOT NULL DEFAULT 'Användare',
  `twofa_secret` varchar(64) DEFAULT NULL,
  `twofa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `theme_mode` varchar(20) DEFAULT 'light',
  `primary_color` varchar(20) DEFAULT '#2563eb',
  `language` varchar(10) DEFAULT 'sv',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_settings` (`user_id`),
  CONSTRAINT `fk_user_settings_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `caseheader` blob NOT NULL COMMENT 'Twofish-encrypted data',
  `taker_id` int(11) DEFAULT NULL,
  `member_data` mediumblob NOT NULL COMMENT 'Twofish-encrypted member snapshot (JSON before encryption)',
  `case_data` mediumblob NOT NULL COMMENT 'Twofish-encrypted case JSON payload',
  `status` enum('new','in_progress','resolved','closed','reopened') NOT NULL DEFAULT 'new',
  `prio` enum('low','medium','high','urgent','none') NOT NULL DEFAULT 'medium',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_case_user` (`user_id`),
  KEY `idx_case_taker` (`taker_id`),
  CONSTRAINT `fk_case_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`),
  CONSTRAINT `fk_case_taker` FOREIGN KEY (`taker_id`) REFERENCES `tbl_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbl_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `link` text DEFAULT NULL,
  `medlnr` int(11) NOT NULL COMMENT 'Membership number',
  `namn` text DEFAULT NULL,
  `fodelsedatum` text DEFAULT NULL,
  `forening` text DEFAULT NULL,
  `medlemsform` text DEFAULT NULL,
  `befattning` text DEFAULT NULL,
  `verksamhetsform` text DEFAULT NULL,
  `arbetsplats` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_medlnr` (`medlnr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_member_imports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `imported_by` int(11) DEFAULT NULL,
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `inserted_rows` int(11) NOT NULL DEFAULT 0,
  `updated_rows` int(11) NOT NULL DEFAULT 0,
  `skipped_rows` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_imported_by` (`imported_by`),
  CONSTRAINT `fk_member_imports_user` FOREIGN KEY (`imported_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `case_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_case_id` (`case_id`),
  KEY `idx_comment_user` (`user_id`),
  CONSTRAINT `fk_comment_case` FOREIGN KEY (`case_id`) REFERENCES `tbl_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_colors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `theme_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tbl_users` (`username`, `password`, `name`, `email`, `phone`, `pic`, `lang`, `colorscheme`, `userlevel`, `role`, `twofa_enabled`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@exempel.se', '', 'default.png', 'sv', 1, 10, 'Admin', 0);

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

COMMIT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
