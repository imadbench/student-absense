-- SQL Script to create an Attendance Table
-- Based on requirement: student ID, 6 sessions, course ID, presence status for each session, and participation

-- Select the database (change 'student_absence' to your actual database name if different)
USE student_absence;

CREATE TABLE student_course_attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    session_1_status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
    session_1_participation BOOLEAN DEFAULT FALSE,
    session_2_status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
    session_2_participation BOOLEAN DEFAULT FALSE,
    session_3_status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
    session_3_participation BOOLEAN DEFAULT FALSE,
    session_4_status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
    session_4_participation BOOLEAN DEFAULT FALSE,
    session_5_status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
    session_5_participation BOOLEAN DEFAULT FALSE,
    session_6_status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
    session_6_participation BOOLEAN DEFAULT FALSE,
    total_present INT GENERATED ALWAYS AS (
        (CASE WHEN session_1_status IN ('present', 'late', 'excused') THEN 1 ELSE 0 END) +
        (CASE WHEN session_2_status IN ('present', 'late', 'excused') THEN 1 ELSE 0 END) +
        (CASE WHEN session_3_status IN ('present', 'late', 'excused') THEN 1 ELSE 0 END) +
        (CASE WHEN session_4_status IN ('present', 'late', 'excused') THEN 1 ELSE 0 END) +
        (CASE WHEN session_5_status IN ('present', 'late', 'excused') THEN 1 ELSE 0 END) +
        (CASE WHEN session_6_status IN ('present', 'late', 'excused') THEN 1 ELSE 0 END)
    ) STORED,
    total_participation INT GENERATED ALWAYS AS (
        (CASE WHEN session_1_participation THEN 1 ELSE 0 END) +
        (CASE WHEN session_2_participation THEN 1 ELSE 0 END) +
        (CASE WHEN session_3_participation THEN 1 ELSE 0 END) +
        (CASE WHEN session_4_participation THEN 1 ELSE 0 END) +
        (CASE WHEN session_5_participation THEN 1 ELSE 0 END) +
        (CASE WHEN session_6_participation THEN 1 ELSE 0 END)
    ) STORED,
    attendance_percentage DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE 
            WHEN 6 > 0 THEN (total_present / 6.0) * 100 
            ELSE 0 
        END
    ) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_course (student_id, course_id),
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_attendance_percentage (attendance_percentage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample insert statement
-- INSERT INTO student_course_attendance (
--     student_id, course_id,
--     session_1_status, session_1_participation,
--     session_2_status, session_2_participation,
--     session_3_status, session_3_participation,
--     session_4_status, session_4_participation,
--     session_5_status, session_5_participation,
--     session_6_status, session_6_participation
-- ) VALUES (
--     1, 1,
--     'present', TRUE,
--     'present', FALSE,
--     'absent', FALSE,
--     'late', TRUE,
--     'present', TRUE,
--     'excused', FALSE
-- );