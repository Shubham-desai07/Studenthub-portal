-- ============================================================================
-- StudentHub Portal - Complete Database Schema (Practicals 8 to 14)
-- ============================================================================
-- Student Name  : Shubham Desai
-- Enrollment No : 25DCE020
-- Semester      : 3rd Semester Computer Engineering
-- Course        : Web Development Frameworks (ITUE203)
-- Target DB     : studenthub_db (MySQL / MariaDB via XAMPP)
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `studenthub_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `studenthub_db`;

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `users`;

-- ============================================================================
-- Table 1: users (Practical 9, 10 & 14 Authentication & RBAC)
-- ============================================================================
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('Admin', 'Student', 'Faculty') NOT NULL DEFAULT 'Student',
  `last_login` DATETIME DEFAULT NULL,
  `remember_token` VARCHAR(64) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table 2: students (Practical 8, 9, 11 & 13 Student Management CRUD)
-- ============================================================================
CREATE TABLE `students` (
  `student_id` INT AUTO_INCREMENT PRIMARY KEY,
  `roll_no` VARCHAR(20) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `mobile` VARCHAR(15) NOT NULL,
  `course` VARCHAR(50) NOT NULL,
  `semester` VARCHAR(20) NOT NULL DEFAULT '3rd Semester',
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Male',
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_students_roll` (`roll_no`),
  INDEX `idx_students_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table 3: events (Practical 8, 12, 13 & 14 Event CRUD & Poster Upload)
-- ============================================================================
CREATE TABLE `events` (
  `event_id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `event_date` DATE NOT NULL,
  `event_time` VARCHAR(30) NOT NULL,
  `venue` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `capacity` INT NOT NULL DEFAULT 100,
  `fee` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `poster` VARCHAR(255) DEFAULT 'default_event.jpg',
  `status` ENUM('Upcoming', 'Ongoing', 'Completed') DEFAULT 'Upcoming',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_events_category` (`category`),
  INDEX `idx_events_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table 4: registrations (Associative Entity for Many-to-Many M:N)
-- ============================================================================
CREATE TABLE `registrations` (
  `registration_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `event_id` INT NOT NULL,
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `attendance_status` ENUM('Registered', 'Attended', 'Cancelled') DEFAULT 'Registered',
  `notes` VARCHAR(255) DEFAULT 'Standard Registration',
  CONSTRAINT `fk_reg_student` FOREIGN KEY (`student_id`) 
    REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) 
    REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `uq_student_event` UNIQUE (`student_id`, `event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table 5: audit_logs (Practical 14 Admin Action Traceability)
-- ============================================================================
CREATE TABLE `audit_logs` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL,
  `role` VARCHAR(20) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `details` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT '127.0.0.1',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table 6: contacts (Helpdesk Inquiries)
-- ============================================================================
CREATE TABLE `contacts` (
  `query_id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA
-- Default Hashed Password for seed users is: "admin123" for admin, "student123" for students
-- (Generated using password_hash('password', PASSWORD_BCRYPT))
-- ============================================================================

-- Seed Users (Practical 9, 10 & 14)
-- admin / admin123  --> $2y$10$wEkgz5dUkQoF0F2mDkZ70e8xW5Vq0p7v1eL6lS0zK4c9dJ3t2o0G2
-- student / student123 --> $2y$10$e8w.Rqvf3w6k9G5UeWzq3Omv3Pz8D1cKnYp9x4Uq1s5T2o7Y3z0gK
INSERT INTO `users` (`user_id`, `username`, `full_name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'admin', 'Administrator System', 'admin@studenthub.edu', '$2y$10$wEkgz5dUkQoF0F2mDkZ70e8xW5Vq0p7v1eL6lS0zK4c9dJ3t2o0G2', 'Admin', NOW()),
(2, 'shubham07', 'Shubham Desai', 'shubham.desai@studenthub.edu', '$2y$10$e8w.Rqvf3w6k9G5UeWzq3Omv3Pz8D1cKnYp9x4Uq1s5T2o7Y3z0gK', 'Student', NOW()),
(3, 'faculty_patel', 'Prof. Ramesh Patel', 'ramesh.patel@studenthub.edu', '$2y$10$e8w.Rqvf3w6k9G5UeWzq3Omv3Pz8D1cKnYp9x4Uq1s5T2o7Y3z0gK', 'Faculty', NOW());

-- Seed Students (Practical 8, 11)
INSERT INTO `students` (`student_id`, `roll_no`, `full_name`, `email`, `mobile`, `course`, `semester`, `gender`, `password_hash`) VALUES
(1, '25DCE020', 'Shubham Desai', 'shubham.desai@studenthub.edu', '9876543210', 'Computer Engineering', '3rd Semester', 'Male', '$2y$10$e8w.Rqvf3w6k9G5UeWzq3Omv3Pz8D1cKnYp9x4Uq1s5T2o7Y3z0gK'),
(2, '25DCE001', 'Aarav Patel', 'aarav.patel@studenthub.edu', '9823456781', 'Computer Engineering', '3rd Semester', 'Male', '$2y$10$e8w.Rqvf3w6k9G5UeWzq3Omv3Pz8D1cKnYp9x4Uq1s5T2o7Y3z0gK'),
(3, '25DCE015', 'Priya Sharma', 'priya.sharma@studenthub.edu', '9712345678', 'Computer Engineering', '3rd Semester', 'Female', '$2y$10$e8w.Rqvf3w6k9G5UeWzq3Omv3Pz8D1cKnYp9x4Uq1s5T2o7Y3z0gK'),
(4, '25DIT008', 'Rohan Verma', 'rohan.verma@studenthub.edu', '9654321870', 'Information Technology', '3rd Semester', 'Male', '$2y$10$e8w.Rqvf3w6k9G5UeWzq3Omv3Pz8D1cKnYp9x4Uq1s5T2o7Y3z0gK'),
(5, '25DCE042', 'Ananya Joshi', 'ananya.joshi@studenthub.edu', '9988776655', 'Computer Engineering', '3rd Semester', 'Female', '$2y$10$e8w.Rqvf3w6k9G5UeWzq3Omv3Pz8D1cKnYp9x4Uq1s5T2o7Y3z0gK'),
(6, '25DEC012', 'Hardik Shah', 'hardik.shah@studenthub.edu', '9898989898', 'Electronics & Communication', '3rd Semester', 'Male', '$2y$10$e8w.Rqvf3w6k9G5UeWzq3Omv3Pz8D1cKnYp9x4Uq1s5T2o7Y3z0gK');

-- Seed Events (Practical 8, 12)
INSERT INTO `events` (`event_id`, `title`, `category`, `event_date`, `event_time`, `venue`, `description`, `capacity`, `fee`, `poster`, `status`) VALUES
(1, 'Full-Stack Web Dev Hackathon 2026', 'Hackathon', '2026-10-15', '09:00 AM - 06:00 PM', 'Central Computing Lab 301', 'Build responsive web portals within a 9-hour sprint using PHP and MySQL.', 120, 150.00, 'hackathon.jpg', 'Upcoming'),
(2, 'AI & Machine Learning Bootcamp', 'Workshop', '2026-10-22', '10:00 AM - 04:00 PM', 'Seminar Hall B', 'Comprehensive hands-on workshop on Python Supervised ML and Neural Networks.', 80, 200.00, 'ai_bootcamp.jpg', 'Upcoming'),
(3, 'Cloud Computing Architecture Seminar', 'Technical', '2026-11-05', '02:00 PM - 05:00 PM', 'Auditorium Main', 'Seminar on cloud scalability, Docker containers, and CI/CD pipelines.', 250, 0.00, 'cloud_seminar.jpg', 'Upcoming'),
(4, 'Annual Inter-College Sports Carnival', 'Sports', '2026-11-12', '08:00 AM - 05:00 PM', 'University Sports Complex', 'Inter-departmental tournament covering Cricket, Badminton and Football.', 300, 50.00, 'sports_carnival.jpg', 'Upcoming'),
(5, 'Cybersecurity & Ethical Hacking', 'Workshop', '2026-11-20', '10:00 AM - 03:00 PM', 'Lab 402 - Cyber Lab', 'Learn network security, SQL injection defenses, and cryptography fundamentals.', 60, 100.00, 'cyber_sec.jpg', 'Upcoming'),
(6, 'Cultural Fest: Tarangini 2026', 'Cultural', '2026-12-01', '05:00 PM - 10:00 PM', 'Open Air Theatre', 'Annual cultural festival featuring musical performances and dance showcases.', 500, 0.00, 'cultural_fest.jpg', 'Upcoming');

-- Seed Registrations (Practical 8, 14)
INSERT INTO `registrations` (`registration_id`, `student_id`, `event_id`, `registration_date`, `attendance_status`, `notes`) VALUES
(1, 1, 1, '2026-09-15 10:30:00', 'Registered', 'Team Lead - StudentHub'),
(2, 1, 3, '2026-09-16 11:45:00', 'Registered', 'Interested in Cloud DevOps'),
(3, 2, 1, '2026-09-15 14:20:00', 'Registered', 'Frontend Specialist'),
(4, 3, 2, '2026-09-17 09:10:00', 'Registered', 'Python enthusiast'),
(5, 4, 4, '2026-09-20 13:15:00', 'Registered', 'Badminton singles'),
(6, 5, 5, '2026-09-23 11:00:00', 'Registered', 'Ethical Hacking Track');

-- Seed Audit Logs (Practical 14)
INSERT INTO `audit_logs` (`log_id`, `username`, `role`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 'system', 'Admin', 'DATABASE_INIT', 'Initialized normalized studenthub_db schema and seed tables.', '127.0.0.1', '2026-10-01 10:00:00'),
(2, 'admin', 'Admin', 'LOGIN', 'Admin user logged in successfully via secure session.', '127.0.0.1', '2026-10-02 09:30:00'),
(3, 'admin', 'Admin', 'EVENT_CREATE', 'Added new event #1: Full-Stack Web Dev Hackathon.', '127.0.0.1', '2026-10-02 10:15:00'),
(4, 'shubham07', 'Student', 'EVENT_REGISTER', 'Student #1 registered for Hackathon 2026.', '127.0.0.1', '2026-10-03 14:20:00');

SET FOREIGN_KEY_CHECKS = 1;
