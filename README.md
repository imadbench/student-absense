# Student Attendance Management System
## Algiers University

A comprehensive web-based attendance management system for tracking student attendance, participation, and justifications.

## Features

### For Professors
- View all assigned courses
- Create and manage attendance sessions
- Mark student attendance (Present, Absent, Late, Excused)
- Track student participation
- View attendance summaries and statistics
- Export attendance data to Excel

### For Students
- View enrolled courses
- Check attendance records per course
- View attendance statistics
- Submit absence justifications with supporting documents
- Track justification request status

### For Administrators
- Manage students (add, remove, import/export)
- View system-wide statistics and charts
- Review and approve/reject justification requests
- Manage courses and enrollments
- Generate comprehensive reports

## Technology Stack

- **Frontend**: HTML5, CSS3, jQuery, Chart.js
- **Backend**: PHP 7.4+
- **Database**: MariaDB/MySQL
- **Design**: Mobile-first responsive design

## Installation

### Prerequisites
- WAMP/XAMPP/LAMP server
- PHP 7.4 or higher
- MariaDB/MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Safari, Edge)

### Setup Steps

1. **Clone or extract the project**
   ```
   Place the project in your web server directory:
   C:\wamp64\www\student apsence
   ```

2. **Create the database**
   - Open phpMyAdmin or MySQL command line
   - Create a new database named `student_absence`
   - Import the schema file: `database/schema.sql`

3. **Configure database connection**
   - Edit `backend/config/config.php`
   - Update database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'student_absence');
     ```

4. **Set up directories**
   - Ensure `uploads/` directory exists and is writable
   - Ensure `backend/logs/` directory exists and is writable

5. **Access the application**
   - Open browser: `http://localhost/student%20apsence`
   - Default admin credentials:
     - Username: `admin`
     - Password: `admin123`
     - **Change this password immediately after first login!**

## Project Structure

```
student apsence/
├── backend/
│   ├── api/              # API endpoints
│   │   ├── auth.php
│   │   ├── attendance.php
│   │   ├── courses.php
│   │   ├── justification.php
│   │   ├── participation.php
│   │   ├── statistics.php
│   │   └── students.php
│   ├── config/            # Configuration files
│   │   └── config.php
│   ├── includes/          # Shared PHP files
│   │   ├── auth.php
│   │   ├── db_connect.php
│   │   └── functions.php
│   └── logs/              # Error logs
├── database/
│   ├── schema.sql         # Database schema
│   └── ER_DIAGRAM.md      # ER diagram documentation
├── frontend/
│   └── shared/            # Shared components
│       ├── header.php
│       └── footer.php
├── professor/             # Professor pages
│   ├── home.php
│   ├── session.php
│   └── summary.php
├── student/               # Student pages
│   ├── home.php
│   └── attendance.php
├── admin/                 # Administrator pages
│   ├── home.php
│   ├── statistics.php
│   └── students.php
├── assets/
│   ├── css/
│   │   └── main.css       # Main stylesheet
│   └── js/                # JavaScript files
│       ├── login.js
│       ├── professor/
│       ├── student/
│       └── admin/
├── uploads/               # Uploaded files (justifications)
├── login.php
├── logout.php
├── index.php
└── README.md
```

## Database Schema

The system uses the following main tables:
- `users` - Students, professors, administrators
- `groups` - Student groups
- `courses` - Course information
- `enrollments` - Student course enrollments
- `attendance_sessions` - Attendance session records
- `attendance_records` - Individual attendance records
- `participation_records` - Student participation tracking
- `justification_requests` - Absence justification requests

See `database/ER_DIAGRAM.md` for detailed entity relationship documentation.

## Usage Guide

### For Administrators

1. **Add Students**
   - Go to Student Management
   - Click "Add Student" or "Import Excel"
   - Fill in student details
   - Default password is the student ID (students should change it)

2. **Import Students from Excel**
   - Prepare CSV file with columns: Student ID, First Name, Last Name, Email
   - Click "Import Excel" and select the file
   - System will import students automatically

3. **View Statistics**
   - Go to Statistics page
   - View charts and reports
   - Analyze attendance trends

### For Professors

1. **Create Attendance Session**
   - Select a course from home page
   - Create a new session
   - Mark attendance for enrolled students

2. **Mark Attendance**
   - Open a session
   - Select status for each student (Present/Absent/Late/Excused)
   - Close session when done

3. **View Summary**
   - Access attendance summary for any course
   - Export data to Excel if needed

### For Students

1. **View Attendance**
   - Select a course from home page
   - View all attendance records
   - Check attendance statistics

2. **Submit Justification**
   - Find an absent session
   - Click "Submit Justification"
   - Provide reason and optional supporting document
   - Track request status

## Security Features

- Password hashing using PHP `password_hash()`
- SQL injection prevention with prepared statements
- Session-based authentication
- Role-based access control
- File upload validation
- Error logging

## API Endpoints

All API endpoints are in `backend/api/`:
- `auth.php` - Authentication (login, logout, check)
- `attendance.php` - Attendance management
- `courses.php` - Course operations
- `justification.php` - Justification requests
- `participation.php` - Participation tracking
- `statistics.php` - Statistics and reports
- `students.php` - Student management

## Troubleshooting

### Database Connection Error
- Check database credentials in `backend/config/config.php`
- Ensure MySQL/MariaDB service is running
- Verify database `student_absence` exists

### File Upload Issues
- Check `uploads/` directory permissions (should be writable)
- Verify `MAX_FILE_SIZE` in config.php
- Check PHP `upload_max_filesize` setting

### Session Issues
- Ensure PHP sessions are enabled
- Check session directory permissions
- Clear browser cookies if needed

## Development Notes

- All paths use relative paths from project root
- jQuery is loaded from CDN
- Chart.js is used for statistics visualization
- Mobile-first responsive design
- All user input is sanitized
- Error logging is enabled

## License

This project is developed for Algiers University.

## Support

For issues or questions, contact the system administrator.

---

**Note**: Remember to change default admin password and configure proper security settings for production use.
