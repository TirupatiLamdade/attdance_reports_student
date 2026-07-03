CREATE DATABASE IF NOT EXISTS attendance_system;
USE attendance_system;

-- =====================================
-- 1. USERS TABLE (Handles Admin & Teacher Logins)
-- =====================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Teacher') NOT NULL DEFAULT 'Teacher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Super Admin Credentials
INSERT INTO users (name, username, password, role)
VALUES ('Administrator', 'admin', 'admin123', 'Admin');


-- =====================================
-- 2. STUDENTS TABLE (Cleaned & Optimized with Branch/Sem)
-- =====================================
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    branch VARCHAR(50) NOT NULL,    -- Added for Dropdown Filter
    sem VARCHAR(20) NOT NULL,       -- Added for Semester Filter
    email VARCHAR(100) DEFAULT NULL,
    contact VARCHAR(20) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =====================================
-- 3. ATTENDANCE TABLE (With Relational Integrity Locked)
-- =====================================
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_time TIME NOT NULL,
    status ENUM('Present', 'Absent') NOT NULL, -- Enum is professional and restricts wrong values
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Ensuring that student actually exists in the system
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);


-- =====================================
-- 4. SYSTEM SETTINGS TABLE
-- =====================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(100) NOT NULL,
    admin_email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO settings (site_name, admin_email)
VALUES ('Attendance ERP System', 'admin@gmail.com');