DROP DATABASE IF EXISTS accountmanager;
CREATE DATABASE accountmanager;
USE accountmanager;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL UNIQUE DEFAULT 'User',
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    image_path VARCHAR(255) DEFAULT 'uploads/default.jpg'
) ENGINE=InnoDB;

-- The password hash will be generated and updated by reset_admin_password.php
INSERT INTO users (username, password, role) VALUES 
('admin', 'PLACEHOLDER', 'admin'),
('teacher1', 'PLACEHOLDER', 'teacher'),
('student1', 'PLACEHOLDER', 'student');
