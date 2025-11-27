-- Migration script to add course schedule fields to existing courses table
-- This script should be run on existing databases to add the new columns

ALTER TABLE courses 
ADD COLUMN course_day ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NULL AFTER semester,
ADD COLUMN course_time TIME NULL AFTER course_day;