-- Improved Student Attendance Management System Database Schema
-- Algiers University
-- Database: student_absence

-- Drop existing tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS justification_requests;
DROP TABLE IF EXISTS participation_records;
DROP TABLE IF EXISTS attendance_records;
DROP TABLE IF EXISTS attendance_sessions;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS `groups`;
DROP TABLE IF EXISTS users;

-- Users table (students, professors, administrators)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'professor', 'administrator') NOT NULL,
    student_id VARCHAR(20) UNIQUE NULL COMMENT 'Only for students',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Groups table
CREATE TABLE `groups` (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(50) NOT NULL,
    group_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_group_code (group_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses table
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(200) NOT NULL,
    description TEXT,
    professor_id INT NOT NULL,
    group_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES `groups`(group_id) ON DELETE CASCADE,
    INDEX idx_course_code (course_code),
    INDEX idx_professor (professor_id),
    INDEX idx_group (group_id),
    INDEX idx_academic_year (academic_year),
    INDEX idx_semester (semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enrollments table (students enrolled in courses)
CREATE TABLE enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date DATE NOT NULL DEFAULT CURRENT_DATE,
    status ENUM('active', 'inactive', 'dropped') DEFAULT 'active',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id),
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance Sessions table - IMPROVED
CREATE TABLE attendance_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    session_number INT NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME DEFAULT '00:00:00',
    status ENUM('open', 'closed', 'cancelled') DEFAULT 'open',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    notes TEXT,
    session_type ENUM('regular', 'exam', 'lab', 'tutorial') DEFAULT 'regular',
    duration_minutes INT DEFAULT 60,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_session (course_id, session_number),
    INDEX idx_course (course_id),
    INDEX idx_status (status),
    INDEX idx_date (session_date),
    INDEX idx_course_date (course_id, session_date),
    INDEX idx_session_type (session_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance Records table - IMPROVED
CREATE TABLE attendance_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
    marked_by INT NOT NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT,
    late_minutes INT DEFAULT 0,
    excused_reason TEXT,
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (session_id, student_id),
    INDEX idx_session (session_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_marked_by (marked_by),
    INDEX idx_session_student (session_id, student_id),
    INDEX idx_course_session (session_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Participation Records table - IMPROVED
CREATE TABLE participation_records (
    participation_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    participation_type ENUM('question', 'answer', 'presentation', 'activity', 'discussion', 'project') DEFAULT 'question',
    marked_by INT NOT NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    score DECIMAL(5,2) DEFAULT 0.00,
    weight DECIMAL(3,2) DEFAULT 1.00,
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_student (student_id),
    INDEX idx_type (participation_type),
    INDEX idx_marked_by (marked_by),
    INDEX idx_session_student (session_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Justification Requests table - IMPROVED
CREATE TABLE justification_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    session_id INT NOT NULL,
    reason TEXT NOT NULL,
    file_path VARCHAR(255) NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_student (student_id),
    INDEX idx_session (session_id),
    INDEX idx_status (status),
    INDEX idx_reviewed_by (reviewed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NEW: Course Attendance Summary Table - For optimized reporting
CREATE TABLE course_attendance_summary (
    summary_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    total_sessions INT DEFAULT 0,
    present_count INT DEFAULT 0,
    absent_count INT DEFAULT 0,
    late_count INT DEFAULT 0,
    excused_count INT DEFAULT 0,
    participation_count INT DEFAULT 0,
    attendance_percentage DECIMAL(5,2) DEFAULT 0.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_summary (course_id, student_id),
    INDEX idx_course (course_id),
    INDEX idx_student (student_id),
    INDEX idx_percentage (attendance_percentage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NEW: Session Statistics Table - For quick session overview
CREATE TABLE session_statistics (
    stat_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL UNIQUE,
    total_students INT DEFAULT 0,
    present_count INT DEFAULT 0,
    absent_count INT DEFAULT 0,
    late_count INT DEFAULT 0,
    excused_count INT DEFAULT 0,
    participation_count INT DEFAULT 0,
    attendance_rate DECIMAL(5,2) DEFAULT 0.00,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(session_id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_attendance_rate (attendance_rate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default administrator account
-- Password: admin123 (should be changed in production)
INSERT INTO users (username, email, password_hash, first_name, last_name, role) 
VALUES ('admin', 'admin@univ-alger.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'System', 'administrator');

-- Insert sample groups
INSERT INTO `groups` (group_name, group_code, description) VALUES
('Group 1', 'G1', 'First year group'),
('Group 2', 'G2', 'Second year group'),
('Group 3', 'G3', 'Third year ISIL group');

-- Create triggers for automatic summary updates
DELIMITER //

-- Trigger to update course attendance summary when attendance record changes
CREATE TRIGGER update_attendance_summary_after_insert
AFTER INSERT ON attendance_records
FOR EACH ROW
BEGIN
    INSERT INTO course_attendance_summary (
        course_id, student_id, total_sessions, 
        present_count, absent_count, late_count, excused_count,
        attendance_percentage
    )
    SELECT 
        asess.course_id,
        NEW.student_id,
        COUNT(*) OVER (PARTITION BY asess.course_id, NEW.student_id),
        SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) OVER (PARTITION BY asess.course_id, NEW.student_id),
        SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) OVER (PARTITION BY asess.course_id, NEW.student_id),
        SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) OVER (PARTITION BY asess.course_id, NEW.student_id),
        SUM(CASE WHEN ar.status = 'excused' THEN 1 ELSE 0 END) OVER (PARTITION BY asess.course_id, NEW.student_id),
        0.00
    FROM attendance_records ar
    JOIN attendance_sessions asess ON ar.session_id = asess.session_id
    WHERE asess.course_id = (SELECT course_id FROM attendance_sessions WHERE session_id = NEW.session_id) 
    AND ar.student_id = NEW.student_id
    ON DUPLICATE KEY UPDATE
        total_sessions = VALUES(total_sessions),
        present_count = VALUES(present_count),
        absent_count = VALUES(absent_count),
        late_count = VALUES(late_count),
        excused_count = VALUES(excused_count),
        attendance_percentage = CASE 
            WHEN VALUES(total_sessions) > 0 
            THEN (VALUES(present_count) + VALUES(late_count) + VALUES(excused_count)) / VALUES(total_sessions) * 100 
            ELSE 0.00 
        END,
        last_updated = CURRENT_TIMESTAMP;
END//

-- Trigger to update session statistics
CREATE TRIGGER update_session_statistics_after_insert
AFTER INSERT ON attendance_records
FOR EACH ROW
BEGIN
    INSERT INTO session_statistics (
        session_id, total_students, present_count, absent_count, 
        late_count, excused_count, attendance_rate
    )
    SELECT 
        NEW.session_id,
        COUNT(*) OVER (PARTITION BY NEW.session_id),
        SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) OVER (PARTITION BY NEW.session_id),
        SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) OVER (PARTITION BY NEW.session_id),
        SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) OVER (PARTITION BY NEW.session_id),
        SUM(CASE WHEN ar.status = 'excused' THEN 1 ELSE 0 END) OVER (PARTITION BY NEW.session_id),
        0.00
    FROM attendance_records ar
    WHERE ar.session_id = NEW.session_id
    ON DUPLICATE KEY UPDATE
        total_students = VALUES(total_students),
        present_count = VALUES(present_count),
        absent_count = VALUES(absent_count),
        late_count = VALUES(late_count),
        excused_count = VALUES(excused_count),
        attendance_rate = CASE 
            WHEN VALUES(total_students) > 0 
            THEN (VALUES(present_count) + VALUES(late_count) + VALUES(excused_count)) / VALUES(total_students) * 100 
            ELSE 0.00 
        END,
        calculated_at = CURRENT_TIMESTAMP;
END//

DELIMITER ;

-- Views for optimized queries
CREATE VIEW student_attendance_overview AS
SELECT 
    u.user_id,
    u.student_id,
    u.first_name,
    u.last_name,
    c.course_id,
    c.course_name,
    c.course_code,
    COUNT(ar.session_id) as total_sessions,
    SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_count,
    SUM(CASE WHEN ar.status = 'excused' THEN 1 ELSE 0 END) as excused_count,
    CASE 
        WHEN COUNT(ar.session_id) > 0 
        THEN (SUM(CASE WHEN ar.status IN ('present', 'late', 'excused') THEN 1 ELSE 0 END) / COUNT(ar.session_id)) * 100 
        ELSE 0 
    END as attendance_percentage
FROM users u
JOIN enrollments e ON u.user_id = e.student_id
JOIN courses c ON e.course_id = c.course_id
LEFT JOIN attendance_records ar ON u.user_id = ar.student_id
LEFT JOIN attendance_sessions asess ON ar.session_id = asess.session_id AND asess.course_id = c.course_id
WHERE u.role = 'student'
GROUP BY u.user_id, c.course_id;

CREATE VIEW session_detail_view AS
SELECT 
    asess.session_id,
    asess.course_id,
    c.course_name,
    c.course_code,
    asess.session_number,
    asess.session_date,
    asess.session_time,
    asess.status as session_status,
    asess.session_type,
    COUNT(ar.student_id) as total_marked,
    SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_count,
    SUM(CASE WHEN ar.status = 'excused' THEN 1 ELSE 0 END) as excused_count,
    COUNT(DISTINCT pr.student_id) as participation_count
FROM attendance_sessions asess
JOIN courses c ON asess.course_id = c.course_id
LEFT JOIN attendance_records ar ON asess.session_id = ar.session_id
LEFT JOIN participation_records pr ON asess.session_id = pr.session_id
GROUP BY asess.session_id;
