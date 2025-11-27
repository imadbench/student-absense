<?php
require_once __DIR__ . '/../backend/includes/auth.php';
require_once __DIR__ . '/../backend/includes/db_connect.php';
requireRole('administrator');

// Handle form submission
if ($_POST) {
    try {
        $pdo = getConnection();
        $course_id = intval($_POST['course_id'] ?? 0);
        $student_ids = $_POST['student_ids'] ?? [];
        
        if ($course_id > 0 && !empty($student_ids)) {
            $pdo->beginTransaction();
            
            foreach ($student_ids as $student_id) {
                $student_id = intval($student_id);
                if ($student_id > 0) {
                    // Check if enrollment already exists
                    $stmt = $pdo->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND course_id = ?");
                    $stmt->execute([$student_id, $course_id]);
                    
                    if (!$stmt->fetch()) {
                        // Insert new enrollment
                        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
                        $stmt->execute([$student_id, $course_id]);
                    }
                }
            }
            
            $pdo->commit();
            $success_message = "Students enrolled successfully!";
        } else {
            $error_message = "Please select a course and at least one student.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        $error_message = "Error enrolling students: " . $e->getMessage();
    }
}

// Load data for the form
try {
    $pdo = getConnection();
    
    // Get all courses
    $stmt = $pdo->prepare("SELECT c.course_id, c.course_name, c.course_code, g.group_name 
                           FROM courses c 
                           LEFT JOIN `groups` g ON c.group_id = g.group_id 
                           ORDER BY c.course_name");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all students
    $stmt = $pdo->prepare("SELECT user_id, student_id, first_name, last_name 
                           FROM users 
                           WHERE role = 'student' 
                           ORDER BY last_name, first_name");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Error loading data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Students - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../frontend/shared/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Enroll Students in Courses</h1>
            <p>Select students to enroll in a specific course</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message active">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message active">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="justification-form-container">
            <form method="POST" id="enrollmentForm">
                <div class="form-group">
                    <label for="courseSelect">Select Course *</label>
                    <select id="courseSelect" name="course_id" class="form-control" required>
                        <option value="">-- Select a Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>">
                                <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ') - ' . $course['group_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select Students *</label>
                    <div class="student-list">
                        <?php if (empty($students)): ?>
                            <p>No students found. Please add students first.</p>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <div class="student-checkbox">
                                    <label>
                                        <input type="checkbox" name="student_ids[]" value="<?php echo $student['user_id']; ?>">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Enroll Selected Students</button>
                    <button type="button" class="btn btn-secondary" onclick="selectAllStudents()">Select All</button>
                    <button type="button" class="btn btn-secondary" onclick="deselectAllStudents()">Deselect All</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='home.php'">Back to Dashboard</button>
                </div>
            </form>
        </div>
    </main>
    
    <?php include __DIR__ . '/../frontend/shared/footer.php'; ?>
    
    <script>
        function selectAllStudents() {
            $('input[name="student_ids[]"]').prop('checked', true);
        }
        
        function deselectAllStudents() {
            $('input[name="student_ids[]"]').prop('checked', false);
        }
        
        // Add confirmation before submitting
        $('#enrollmentForm').on('submit', function(e) {
            const selectedStudents = $('input[name="student_ids[]"]:checked').length;
            const courseSelected = $('#courseSelect').val();
            
            if (!courseSelected) {
                e.preventDefault();
                alert('Please select a course.');
                return;
            }
            
            if (selectedStudents === 0) {
                e.preventDefault();
                alert('Please select at least one student.');
                return;
            }
            
            if (!confirm(`Are you sure you want to enroll ${selectedStudents} student(s) in this course?`)) {
                e.preventDefault();
            }
        });
    </script>
    
    <style>
        .student-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
        }
        
        .student-checkbox {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .student-checkbox:last-child {
            border-bottom: none;
        }
    </style>
</body>
</html>