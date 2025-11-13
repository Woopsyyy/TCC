-- Database backup generated on 2025-11-13T14:36:59+01:00
-- Database: accountmanager
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS=0;

-- -------------------------------------------
-- Table structure for `announcements`
DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `year` varchar(10) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `announcements` (`id`, `title`, `content`, `year`, `department`, `date`) VALUES
('1', 'BAGYONG TINO', 'no class', '', '', '2025-11-13 20:55:53'),
('2', 'BAGYONG UWAN', 'no class', '', '', '2025-11-13 20:56:04');

-- -------------------------------------------
-- Table structure for `audit_log`
DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_user` varchar(100) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `target_table` varchar(50) DEFAULT NULL,
  `target_id` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `audit_log` (`id`, `admin_user`, `action`, `target_table`, `target_id`, `details`, `created_at`) VALUES
('1', 'admin', 'create', 'sections', '1', 'created section: Altruim (Year: 3)', '2025-11-13 20:51:18'),
('2', 'admin', 'create', 'sections', '2', 'created section: Benevolence (Year: 3)', '2025-11-13 20:51:25'),
('3', 'admin', 'create', 'sections', '3', 'created section: Charity (Year: 3)', '2025-11-13 20:51:32'),
('4', 'admin', 'create', 'sections', '4', 'created section: Devotion (Year: 3)', '2025-11-13 20:51:43'),
('5', 'admin', 'create', 'user_assignments', '0', 'assigned Joshua Paculaba to 3/Benevolence', '2025-11-13 20:53:07'),
('6', 'admin', 'delete', 'sections', '1', 'deleted section: Altruim (Year: 3)', '2025-11-13 20:53:25'),
('7', 'admin', 'create', 'sections', '5', 'created section: Altruism (Year: 3)', '2025-11-13 20:53:33'),
('8', 'admin', 'delete', 'user_assignments', '1', 'deleted user_assignment for Joshua Paculaba (year: 3, section: Benevolence)', '2025-11-13 20:53:43'),
('9', 'admin', 'create', 'user_assignments', '0', 'assigned Joshua Paculaba to 3/Altruism', '2025-11-13 20:53:59'),
('10', 'admin', 'create', 'announcements', '1', 'created announcement id=1', '2025-11-13 20:55:53'),
('11', 'admin', 'create', 'announcements', '2', 'created announcement id=2', '2025-11-13 20:56:04');

-- -------------------------------------------
-- Table structure for `backup_settings`
DROP TABLE IF EXISTS `backup_settings`;
CREATE TABLE `backup_settings` (
  `id` tinyint(3) unsigned NOT NULL,
  `schedule_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `schedule_time` time DEFAULT NULL,
  `last_backup_at` datetime DEFAULT NULL,
  `last_backup_path` varchar(255) DEFAULT NULL,
  `last_scheduled_run` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `backup_settings` (`id`, `schedule_enabled`, `schedule_time`, `last_backup_at`, `last_backup_path`, `last_scheduled_run`, `updated_at`) VALUES
('1', '0', NULL, '2025-11-13 21:30:24', 'BackEnd/backups/accountmanager_backup_20251113_143024.sql', NULL, '2025-11-13 21:34:08');

-- -------------------------------------------
-- Table structure for `buildings`
DROP TABLE IF EXISTS `buildings`;
CREATE TABLE `buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  `floors` int(11) DEFAULT 4,
  `rooms_per_floor` int(11) DEFAULT 4,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------
-- Table structure for `projects`
DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `budget` varchar(64) DEFAULT NULL,
  `started` date DEFAULT NULL,
  `completed` enum('yes','no') DEFAULT 'no',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------
-- Table structure for `schedules`
DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` varchar(10) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `day` varchar(20) NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `room` varchar(100) DEFAULT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL,
  `building` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_year` (`year`),
  KEY `idx_subject` (`subject`),
  KEY `idx_day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------
-- Table structure for `section_assignments`
DROP TABLE IF EXISTS `section_assignments`;
CREATE TABLE `section_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` varchar(10) NOT NULL,
  `section` varchar(100) NOT NULL,
  `building` varchar(10) NOT NULL,
  `floor` int(11) NOT NULL,
  `room` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_year_section` (`year`,`section`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `section_assignments` (`id`, `year`, `section`, `building`, `floor`, `room`) VALUES
('1', '3', 'Altruism', 'D', '2', '303'),
('2', '3', 'Benevolence', 'D', '3', '301'),
('3', '3', 'Charity', 'C', '2', '301'),
('4', '3', 'Devotion', 'D', '3', '301');

-- -------------------------------------------
-- Table structure for `sections`
DROP TABLE IF EXISTS `sections`;
CREATE TABLE `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_year_name` (`year`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sections` (`id`, `year`, `name`, `created_at`) VALUES
('2', '3', 'Benevolence', '2025-11-13 20:51:25'),
('3', '3', 'Charity', '2025-11-13 20:51:32'),
('4', '3', 'Devotion', '2025-11-13 20:51:43'),
('5', '3', 'Altruism', '2025-11-13 20:53:33');

-- -------------------------------------------
-- Table structure for `student_grades`
DROP TABLE IF EXISTS `student_grades`;
CREATE TABLE `student_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(200) NOT NULL,
  `year` varchar(10) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `prelim_grade` decimal(5,2) DEFAULT NULL,
  `midterm_grade` decimal(5,2) DEFAULT NULL,
  `finals_grade` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_year_semester` (`year`,`semester`),
  CONSTRAINT `student_grades_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------
-- Table structure for `teacher_assignments`
DROP TABLE IF EXISTS `teacher_assignments`;
CREATE TABLE `teacher_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(200) NOT NULL,
  `year` varchar(10) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------
-- Table structure for `user_assignments`
DROP TABLE IF EXISTS `user_assignments`;
CREATE TABLE `user_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(200) NOT NULL,
  `year` varchar(10) NOT NULL,
  `section` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `payment` enum('paid','owing') DEFAULT 'paid',
  `sanctions` text DEFAULT NULL,
  `owing_amount` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_username` (`username`),
  CONSTRAINT `user_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_assignments` (`id`, `user_id`, `username`, `year`, `section`, `department`, `payment`, `sanctions`, `owing_amount`) VALUES
('2', '2', 'Joshua Paculaba', '3', 'Altruism', 'IT', 'paid', '', '');

-- -------------------------------------------
-- Table structure for `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `school_id` varchar(20) DEFAULT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `image_path` varchar(255) DEFAULT '/TCC/public/images/sample.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `school_id` (`school_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `school_id`, `role`, `image_path`, `created_at`) VALUES
('1', 'admin', '$2y$10$g0GVzToZmG4imJ3dzI8IQ.0ct3DGKzvKq19dAUHaInf8GFpgdWmxi', 'Administrator', 'ADMIN - 0000', 'admin', '/TCC/public/images/sample.jpg', '2025-11-13 20:47:52'),
('2', 'Joshua', '$2y$10$2v8DADDSGGEZvGl2EI08xOWnzlo9X5lUA0gEQdhicE.3ZoIFnvGFG', 'Joshua Paculaba', '2025 - 6287', 'student', '/TCC/database/pictures/joshua_paculaba_1763038218.jpg', '2025-11-13 20:49:50');

SET FOREIGN_KEY_CHECKS=1;
-- Backup completed on 2025-11-13T14:36:59+01:00
