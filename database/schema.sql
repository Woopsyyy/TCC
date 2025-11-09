-- Clean Database Schema for TCC Account Manager
-- Run this to create a fresh database with all required tables

DROP DATABASE IF EXISTS accountmanager;
CREATE DATABASE accountmanager;
USE accountmanager;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    image_path VARCHAR(255) DEFAULT '/TCC/public/images/sample.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Announcements table
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    year VARCHAR(10),
    department VARCHAR(50),
    date DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Projects table
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    budget VARCHAR(64),
    started DATE,
    completed ENUM('yes','no') DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Buildings table
CREATE TABLE buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(10) NOT NULL UNIQUE,
    floors INT DEFAULT 4,
    rooms_per_floor INT DEFAULT 4
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Section assignments table
CREATE TABLE section_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year VARCHAR(10) NOT NULL,
    section VARCHAR(100) NOT NULL,
    building VARCHAR(10) NOT NULL,
    floor INT NOT NULL,
    room VARCHAR(50) NOT NULL,
    UNIQUE KEY uniq_year_section (year, section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User assignments table
CREATE TABLE user_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    username VARCHAR(200) NOT NULL,
    year VARCHAR(10) NOT NULL,
    section VARCHAR(100) NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    payment ENUM('paid','owing') DEFAULT 'paid',
    sanctions TEXT DEFAULT NULL,
    owing_amount VARCHAR(64) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit log table
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user VARCHAR(100),
    action VARCHAR(50),
    target_table VARCHAR(50),
    target_id VARCHAR(50),
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

