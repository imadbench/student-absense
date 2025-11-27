-- Student Course Attendance Table
-- This table stores attendance and participation data for students across all sessions
-- without requiring actual session records in attendance_sessions table

-- Create the student_course_attendance table
CREATE TABLE IF NOT EXISTS student_course_attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    session_number INT NOT NULL CHECK (session_number BETWEEN 1 AND 6),
    attendance_status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
    participation_count INT DEFAULT 0,
    participation_types TEXT NULL COMMENT 'JSON array of participation types',
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    -- Unique constraint to prevent duplicate records
    UNIQUE KEY unique_student_session (course_id, student_id, session_number),
    
    -- Indexes for better performance
    INDEX idx_course (course_id),
    INDEX idx_student (student_id),
    INDEX idx_session_number (session_number),
    INDEX idx_attendance_status (attendance_status),
    INDEX idx_course_session (course_id, session_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stores attendance and participation data for all 6 sessions per course';
