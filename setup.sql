SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS active_sessions;
DROP TABLE IF EXISTS student_subject;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'lecturer') NOT NULL,
    mfa_secret VARCHAR(32) DEFAULT NULL,
    is_mfa_verified BOOLEAN DEFAULT 0
);

CREATE TABLE subjects (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    lecturer_id VARCHAR(20),
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (lecturer_id) REFERENCES users(id)
);

CREATE TABLE student_subject (
    student_id VARCHAR(20),
    subject_id VARCHAR(20),
    PRIMARY KEY (student_id, subject_id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);

CREATE TABLE active_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id VARCHAR(20) NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    current_qr_token VARCHAR(64),
    token_expires_at INT,
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT,
    student_id VARCHAR(20),
    status ENUM('Present', 'Absent', 'Absent with reason') DEFAULT 'Absent',
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES active_sessions(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    UNIQUE(session_id, student_id)
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modified_by VARCHAR(20),
    student_affected VARCHAR(20),
    old_status VARCHAR(20),
    new_status VARCHAR(20),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (modified_by) REFERENCES users(id)
);

CREATE TABLE demo_time_control (
    id INT PRIMARY KEY DEFAULT 1,
    is_demo_mode BOOLEAN DEFAULT 0,
    demo_day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') DEFAULT 'Monday',
    demo_time TIME DEFAULT '00:00:00',
    updated_by VARCHAR(20),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cooldown_until DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Initialize the single record
INSERT INTO demo_time_control (id, is_demo_mode, demo_day, demo_time) VALUES (1, 0, 'Monday', '00:00:00');

-- Insert original + 3 new Lecturers
INSERT INTO users (id, name, password_hash, role) VALUES 
('LEC123', 'Dr. Lecturer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('LEC456', 'Dr. Another', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('LEC789', 'Dr. Alice Roberts', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('LEC101', 'Prof. Charlie Day', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('LEC202', 'Dr. Eve Adams', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer');

-- Insert original + 10 new Students
INSERT INTO users (id, name, password_hash, role) VALUES 
('BSW01084686', 'Hazmi Shafiq Bin Hamiruddin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01085129', 'Muhammad Zakarria Bin Ahmad Radzif', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01084986', 'Wan Muhammad Aqilrasydan Bin Wan Arman', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01085016', 'Muhammad Faredzul Azri Bin Mohd Ruzi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01085030', 'Muhammad Ainul Khalis bin Mod Radzi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01081111', 'John Doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01082222', 'Jane Smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01083333', 'Michael Johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01084444', 'Emily Davis', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01085555', 'William Brown', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01086666', 'Olivia Garcia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01087777', 'James Miller', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01088888', 'Sophia Wilson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01089999', 'Benjamin Martinez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('BSW01080000', 'Isabella Anderson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Insert 10 Subjects with schedules
INSERT INTO subjects (id, name, lecturer_id, day_of_week, start_time, end_time) VALUES 
('SEC01B', 'Software Security', 'LEC123', 'Wednesday', '00:00:00', '08:00:00'),
('DAT06A', 'Database Systems', 'LEC123', 'Wednesday', '13:00:00', '15:00:00'),
('NET07A', 'Computer Networks', 'LEC123', 'Thursday', '10:00:00', '12:00:00'),
('AI10B', 'Artificial Intelligence', 'LEC123', 'Friday', '15:00:00', '17:00:00'),
('WEB02C', 'Web Development', 'LEC456', 'Monday', '10:00:00', '12:00:00'),
('PRG03X', 'Programming Java', 'LEC456', 'Tuesday', '08:00:00', '10:00:00'),
('MAT03A', 'Discrete Mathematics', 'LEC789', 'Monday', '08:00:00', '10:00:00'),
('PHY04B', 'Physics I', 'LEC101', 'Tuesday', '14:00:00', '16:00:00'),
('CHM05C', 'Chemistry', 'LEC202', 'Thursday', '08:00:00', '10:00:00'),
('OS08C', 'Operating Systems', 'LEC202', 'Friday', '08:00:00', '10:00:00');

-- Assign Students to Subjects
INSERT INTO student_subject (student_id, subject_id) VALUES
('BSW01084686', 'SEC01B'),
('BSW01084686', 'DAT06A'),
('BSW01084686', 'NET07A'),
('BSW01084686', 'AI10B'),
('BSW01085129', 'SEC01B'),
('BSW01084986', 'SEC01B'),
('BSW01085016', 'SEC01B'),
('BSW01081111', 'WEB02C'),
('BSW01082222', 'WEB02C'),
('BSW01083333', 'MAT03A'),
('BSW01084444', 'PHY04B'),
('BSW01085555', 'CHM05C'),
('BSW01086666', 'OS08C'),
('BSW01087777', 'SEC01B'),
('BSW01088888', 'SEC01B'),
('BSW01089999', 'DAT06A'),
('BSW01080000', 'NET07A');
