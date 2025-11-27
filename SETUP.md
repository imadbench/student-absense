# Setup Instructions

## Quick Setup Guide

### 1. Database Setup

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `student_absence`
3. Click on the database, then go to "Import" tab
4. Select the file `database/schema.sql`
5. Click "Go" to import

### 2. Configuration

Edit `backend/config/config.php` if your database credentials are different:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password
define('DB_NAME', 'student_absence');
```

### 3. Directory Permissions

Ensure these directories exist and are writable:
- `uploads/` - For justification file uploads
- `backend/logs/` - For error logs

On Windows (WAMP), these should work by default.

### 4. Access the System

1. Open browser: `http://localhost/student%20apsence`
2. Login with default admin:
   - Username: `admin`
   - Password: `admin123`

### 5. Initial Setup Steps

1. **Change Admin Password** (Important!)
2. **Add Students**:
   - Go to Admin → Student Management
   - Add students manually or import from Excel
3. **Create Groups** (if needed):
   - Can be done via SQL or phpMyAdmin
4. **Create Courses**:
   - Assign professors to courses
   - Enroll students in courses

### 6. Test the System

1. Login as professor
2. Create a course session
3. Mark attendance for students
4. Login as student
5. View attendance and submit justification

## Troubleshooting

### Database Connection Error
- Check MySQL service is running
- Verify database name is `student_absence`
- Check credentials in `backend/config/config.php`

### File Upload Issues
- Check `uploads/` directory exists
- Verify directory is writable
- Check PHP `upload_max_filesize` in php.ini

### Session Issues
- Clear browser cookies
- Check PHP session settings
- Ensure `session_start()` is called

## Sample Data

After setup, you can add sample data:

```sql
-- Add a professor
INSERT INTO users (username, email, password_hash, first_name, last_name, role) 
VALUES ('prof1', 'prof1@univ.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Professor', 'One', 'professor');

-- Add a student
INSERT INTO users (username, email, password_hash, first_name, last_name, role, student_id) 
VALUES ('student1', 'student1@univ.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', 'One', 'student', '2024001');

-- Create a course (assuming group_id = 1 and professor_id = 2)
INSERT INTO courses (course_code, course_name, professor_id, group_id, academic_year, semester) 
VALUES ('CS301', 'Advanced Web Programming', 2, 1, '2024/2025', 'Fall');

-- Enroll student in course
INSERT INTO enrollments (student_id, course_id) VALUES (3, 1);
```

Password for all sample users: `password123` (change after first login!)

