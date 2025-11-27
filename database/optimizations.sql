-- Database Optimizations for Student Attendance Management System
-- This file contains performance improvements and additional indexes

-- Add composite indexes for better query performance
-- These indexes will significantly improve the attendance and enrollment queries

-- Index for attendance records queries (session + student)
CREATE INDEX idx_attendance_session_student ON attendance_records(session_id, student_id);

-- Index for enrollment queries (course + student)
CREATE INDEX idx_enrollment_course_student ON enrollments(course_id, student_id);

-- Index for course queries (professor + group)
CREATE INDEX idx_courses_professor_group ON courses(professor_id, group_id);

-- Index for session queries (course + date)
CREATE INDEX idx_sessions_course_date ON attendance_sessions(course_id, session_date);

-- Index for participation records (session + student)
CREATE INDEX idx_participation_session_student ON participation_records(session_id, student_id);

-- Index for justification requests (student + status)
CREATE INDEX idx_justification_student_status ON justification_requests(student_id, status);

-- Index for justification requests (session + status)
CREATE INDEX idx_justification_session_status ON justification_requests(session_id, status);

-- Add index for users role-based queries
CREATE INDEX idx_users_role_student ON users(role, user_id) WHERE role = 'student';

-- Add index for users student_id lookup
CREATE INDEX idx_users_student_id_lookup ON users(student_id) WHERE student_id IS NOT NULL;

-- Analyze tables to update index statistics
ANALYZE TABLE users;
ANALYZE TABLE `groups`;
ANALYZE TABLE courses;
ANALYZE TABLE enrollments;
ANALYZE TABLE attendance_sessions;
ANALYZE TABLE attendance_records;
ANALYZE TABLE participation_records;
ANALYZE TABLE justification_requests;

-- Show optimization summary
SELECT 'Database optimizations completed successfully' as status;
