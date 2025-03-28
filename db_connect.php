<?php
// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_management');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input
function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user permissions
function hasPermission($required_role) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $role_hierarchy = [
        'admin' => ['admin'],
        'teacher' => ['admin', 'teacher'],
        'student' => ['admin', 'teacher', 'student'],
        'staff' => ['admin', 'staff']
    ];
    
    return in_array($_SESSION['role'], $role_hierarchy[$required_role] ?? []);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to log user activity
function logUserActivity($user_id, $action, $details = '') {
    global $conn;
    $sql = "INSERT INTO login_history (user_id, login_time, ip_address) VALUES (?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("is", $user_id, $ip);
    $stmt->execute();
}

// Function to get user data
function getUserData($user_id) {
    global $conn;
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get student data
function getStudentData($student_id) {
    global $conn;
    $sql = "SELECT s.*, u.username, u.email as user_email 
            FROM students s 
            LEFT JOIN users u ON s.user_id = u.user_id 
            WHERE s.student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get course data
function getCourseData($course_id) {
    global $conn;
    $sql = "SELECT * FROM courses WHERE course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get department data
function getDepartmentData($department_id) {
    global $conn;
    $sql = "SELECT * FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get student courses
function getStudentCourses($student_id) {
    global $conn;
    $sql = "SELECT sc.*, c.course_name, c.course_code 
            FROM student_courses sc 
            JOIN courses c ON sc.course_id = c.course_id 
            WHERE sc.student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get student attendance
function getStudentAttendance($student_id, $course_id = null) {
    global $conn;
    $sql = "SELECT a.*, c.course_name 
            FROM attendance a 
            JOIN courses c ON a.course_id = c.course_id 
            WHERE a.student_id = ?";
    
    if ($course_id) {
        $sql .= " AND a.course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $student_id, $course_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get student fees
function getStudentFees($student_id) {
    global $conn;
    $sql = "SELECT * FROM fees WHERE student_id = ? ORDER BY payment_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get student leaves
function getStudentLeaves($student_id) {
    global $conn;
    $sql = "SELECT l.*, u.full_name as approved_by_name 
            FROM leaves l 
            LEFT JOIN users u ON l.approved_by = u.user_id 
            WHERE l.student_id = ? 
            ORDER BY l.request_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get upcoming events
function getUpcomingEvents($limit = 5) {
    global $conn;
    $sql = "SELECT * FROM events WHERE event_date >= CURRENT_DATE ORDER BY event_date ASC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get student event registrations
function getStudentEventRegistrations($student_id) {
    global $conn;
    $sql = "SELECT er.*, e.event_title, e.event_date, e.event_time, e.venue 
            FROM event_registrations er 
            JOIN events e ON er.event_id = e.event_id 
            WHERE er.student_id = ? 
            ORDER BY e.event_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?> 