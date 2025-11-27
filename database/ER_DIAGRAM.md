# Entity Relationship Diagram Documentation

## Student Attendance Management System - Database Design

### Entities

#### 1. Users
- **Primary Key**: user_id
- **Attributes**: username, email, password_hash, first_name, last_name, role, student_id, created_at, updated_at
- **Role Types**: student, professor, administrator
- **Relationships**: 
  - One-to-Many with Courses (as professor)
  - One-to-Many with Enrollments (as student)
  - One-to-Many with Attendance Sessions (as creator)
  - One-to-Many with Justification Requests (as reviewer)

#### 2. Groups
- **Primary Key**: group_id
- **Attributes**: group_name, group_code, description, created_at
- **Relationships**: 
  - One-to-Many with Courses

#### 3. Courses
- **Primary Key**: course_id
- **Attributes**: course_code, course_name, description, professor_id, group_id, academic_year, semester, created_at, updated_at
- **Relationships**: 
  - Many-to-One with Users (professor)
  - Many-to-One with Groups
  - One-to-Many with Enrollments
  - One-to-Many with Attendance Sessions

#### 4. Enrollments
- **Primary Key**: enrollment_id
- **Attributes**: student_id, course_id, enrolled_at
- **Relationships**: 
  - Many-to-One with Users (student)
  - Many-to-One with Courses
- **Unique Constraint**: (student_id, course_id)

#### 5. Attendance Sessions
- **Primary Key**: session_id
- **Attributes**: course_id, session_number, session_date, session_time, status, created_by, created_at, closed_at, notes
- **Status**: open, closed
- **Relationships**: 
  - Many-to-One with Courses
  - Many-to-One with Users (creator)
  - One-to-Many with Attendance Records
  - One-to-Many with Participation Records
  - One-to-Many with Justification Requests
- **Unique Constraint**: (course_id, session_number)

#### 6. Attendance Records
- **Primary Key**: record_id
- **Attributes**: session_id, student_id, status, marked_at
- **Status**: present, absent, late, excused
- **Relationships**: 
  - Many-to-One with Attendance Sessions
  - Many-to-One with Users (student)
- **Unique Constraint**: (session_id, student_id)

#### 7. Participation Records
- **Primary Key**: participation_id
- **Attributes**: session_id, student_id, participation_type, notes, marked_at
- **Participation Types**: question, answer, presentation, activity
- **Relationships**: 
  - Many-to-One with Attendance Sessions
  - Many-to-One with Users (student)

#### 8. Justification Requests
- **Primary Key**: request_id
- **Attributes**: student_id, session_id, reason, file_path, status, reviewed_by, reviewed_at, review_notes, submitted_at
- **Status**: pending, approved, rejected
- **Relationships**: 
  - Many-to-One with Users (student)
  - Many-to-One with Attendance Sessions
  - Many-to-One with Users (reviewer)

### Relationships Summary

1. **User → Courses** (1:N) - A professor can teach multiple courses
2. **Group → Courses** (1:N) - A group can have multiple courses
3. **User → Enrollments** (1:N) - A student can enroll in multiple courses
4. **Course → Enrollments** (1:N) - A course can have multiple enrolled students
5. **Course → Attendance Sessions** (1:N) - A course can have multiple sessions
6. **User → Attendance Sessions** (1:N) - A professor can create multiple sessions
7. **Attendance Session → Attendance Records** (1:N) - A session can have multiple attendance records
8. **User → Attendance Records** (1:N) - A student can have multiple attendance records
9. **Attendance Session → Participation Records** (1:N) - A session can have multiple participation records
10. **User → Participation Records** (1:N) - A student can have multiple participation records
11. **User → Justification Requests** (1:N) - A student can submit multiple requests
12. **Attendance Session → Justification Requests** (1:N) - A session can have multiple justification requests
13. **User → Justification Requests** (1:N) - An administrator can review multiple requests

### Constraints

- **Foreign Key Constraints**: All foreign keys have CASCADE delete for referential integrity
- **Unique Constraints**: 
  - Username and email must be unique
  - Student ID must be unique (for students)
  - Course code must be unique
  - Group code must be unique
  - Enrollment (student_id, course_id) must be unique
  - Attendance session (course_id, session_number) must be unique
  - Attendance record (session_id, student_id) must be unique

### Indexes

- Indexes on frequently queried columns (username, email, role, course_code, etc.)
- Composite indexes for common query patterns

