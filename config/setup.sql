-- Create database
CREATE DATABASE IF NOT EXISTS medlem_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medlem_db;

-- Users table (matches tbl_users from medlem_db.sql)
CREATE TABLE IF NOT EXISTS tbl_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(100) NOT NULL DEFAULT '',
    pic VARCHAR(255) NOT NULL DEFAULT 'default.png',
    lang VARCHAR(5) NOT NULL DEFAULT 'sv',
    colorscheme INT NOT NULL DEFAULT 1,
    last_login TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    userlevel INT NOT NULL DEFAULT 10,
    role ENUM('Admin','Användare') NOT NULL DEFAULT 'Användare',
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional user settings (themes)
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    theme_mode VARCHAR(20) DEFAULT 'light',
    primary_color VARCHAR(20) DEFAULT '#2563eb',
    language VARCHAR(10) DEFAULT 'sv',
    FOREIGN KEY (user_id) REFERENCES tbl_users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_settings (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Color themes (for CSS variables)
CREATE TABLE IF NOT EXISTS tbl_colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bg_light VARCHAR(100) NOT NULL,
    bg_dark VARCHAR(100) NOT NULL,
    surface_light VARCHAR(100) NOT NULL,
    surface_dark VARCHAR(100) NOT NULL,
    primary_light VARCHAR(100) NOT NULL,
    primary_dark VARCHAR(100) NOT NULL,
    accent_light VARCHAR(100) NOT NULL,
    accent_dark VARCHAR(100) NOT NULL,
    text_light VARCHAR(100) NOT NULL,
    text_dark VARCHAR(100) NOT NULL,
    muted_light VARCHAR(100) NOT NULL,
    muted_dark VARCHAR(100) NOT NULL,
    border_light VARCHAR(100) NOT NULL,
    border_dark VARCHAR(100) NOT NULL,
    theme_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tbl_colors (id, bg_light, bg_dark, surface_light, surface_dark, primary_light, primary_dark, accent_light, accent_dark, text_light, text_dark, muted_light, muted_dark, border_light, border_dark, theme_name) VALUES
(1, '#f6f7fb', '#0b1220', '#ffffff', '#111827', '#2563eb', '#60a5fa', '#0ea5e9', '#38bdf8', '#0f172a', '#e5e7eb', '#6b7280', '#9ca3af', '#e5e7eb', '#1f2937', 'Nordic Blue'),
(2, '#f5f8f3', '#0f1c14', '#ffffff', '#12231a', '#15803d', '#22c55e', '#65a30d', '#a3e635', '#0b2215', '#e6f4ec', '#4b5563', '#9ca3af', '#d1d5db', '#1f2d24', 'Forest'),
(3, '#fff7ed', '#1a0f0a', '#fff3e6', '#26130c', '#f97316', '#fdba74', '#ec4899', '#f472b6', '#2a1405', '#fef3e8', '#a16246', '#e5b89a', '#fcd9bd', '#3b1d13', 'Sunset Glow'),
(4, '#f7f5ff', '#0f1024', '#ffffff', '#161632', '#7c3aed', '#a78bfa', '#f472b6', '#e879f9', '#1f172a', '#ede9fe', '#6b7280', '#a5b4fc', '#e4e4f7', '#262750', 'Lavender Mist'),
(5, '#f0f9ff', '#0b1720', '#ffffff', '#0f2530', '#0ea5e9', '#38bdf8', '#14b8a6', '#2dd4bf', '#0b1220', '#e2f3ff', '#4b5563', '#94a3b8', '#dbeafe', '#1f3340', 'Ocean Breeze'),
(6, '#fff1f2', '#1f0f13', '#ffffff', '#24141a', '#e11d48', '#f472b6', '#f59e0b', '#fbbf24', '#2e0f17', '#fde2e7', '#9f616a', '#f3bec8', '#fbcfe8', '#3b1c26', 'Rose Quartz'),
(7, '#f3f4f6', '#0e1116', '#ffffff', '#1f2933', '#4b5563', '#9ca3af', '#2563eb', '#60a5fa', '#111827', '#e5e7eb', '#6b7280', '#9ca3af', '#e5e7eb', '#2b3540', 'Slate'),
(8, '#fef7e5', '#20160d', '#fffaf0', '#2a1c12', '#d97706', '#fbbf24', '#10b981', '#22c55e', '#2f1a0f', '#f5e9d7', '#7c6f64', '#c7b8a6', '#eddcc8', '#3a2718', 'Desert Sun')
ON DUPLICATE KEY UPDATE
    bg_light = VALUES(bg_light),
    bg_dark = VALUES(bg_dark),
    surface_light = VALUES(surface_light),
    surface_dark = VALUES(surface_dark),
    primary_light = VALUES(primary_light),
    primary_dark = VALUES(primary_dark),
    accent_light = VALUES(accent_light),
    accent_dark = VALUES(accent_dark),
    text_light = VALUES(text_light),
    text_dark = VALUES(text_dark),
    muted_light = VALUES(muted_light),
    muted_dark = VALUES(muted_dark),
    border_light = VALUES(border_light),
    border_dark = VALUES(border_dark),
    theme_name = VALUES(theme_name);

-- Cases table (Twofish payloads stored as BLOB/JSON)
CREATE TABLE IF NOT EXISTS tbl_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    changed TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    caseheader BLOB NOT NULL COMMENT 'Twofish-encrypted data',
    taker_id INT NULL,
    member_data MEDIUMBLOB NOT NULL COMMENT 'Twofish-encrypted member snapshot (JSON before encryption)',
    case_data MEDIUMBLOB NOT NULL COMMENT 'Twofish-encrypted case JSON payload',
    status ENUM('new','in_progress','resolved','closed','reopened') NOT NULL DEFAULT 'new',
    prio ENUM('low','medium','high','urgent','none') NOT NULL DEFAULT 'medium',
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tbl_users(id),
    FOREIGN KEY (taker_id) REFERENCES tbl_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Members table (encrypted at rest; all columns except id/medlnr stored as ciphertext)
CREATE TABLE IF NOT EXISTS tbl_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link TEXT DEFAULT NULL,
    medlnr INT NOT NULL COMMENT 'Membership number',
    namn TEXT DEFAULT NULL,
    fodelsedatum TEXT DEFAULT NULL,
    forening TEXT DEFAULT NULL,
    medlemsform TEXT DEFAULT NULL,
    befattning TEXT DEFAULT NULL,
    verksamhetsform TEXT DEFAULT NULL,
    arbetsplats TEXT DEFAULT NULL,
    UNIQUE KEY uniq_medlnr (medlnr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Member import audit log
CREATE TABLE IF NOT EXISTS tbl_member_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    imported_by INT NULL,
    imported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_rows INT NOT NULL DEFAULT 0,
    inserted_rows INT NOT NULL DEFAULT 0,
    updated_rows INT NOT NULL DEFAULT 0,
    skipped_rows INT NOT NULL DEFAULT 0,
    FOREIGN KEY (imported_by) REFERENCES tbl_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Case comments table
CREATE TABLE IF NOT EXISTS case_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES tbl_cases(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES tbl_users(id),
    INDEX idx_case_id (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a default admin user (password: admin123)
INSERT INTO tbl_users (username, email, password, name, phone, pic, lang, colorscheme, userlevel, role) 
VALUES ('admin', 'admin@exempel.se', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '', 'default.png', 'sv', 1, 10, 'Admin')
ON DUPLICATE KEY UPDATE username=username;
