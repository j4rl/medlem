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
