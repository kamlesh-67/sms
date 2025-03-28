<?php
class CourseTracker {
    private $db;

    public function __construct() {
        require_once('dbms.php');
        $this->db = new Database();
    }

    public function getCourseProgress($studentId, $courseId) {
        $query = "SELECT * FROM course_progress 
                WHERE student_id = $studentId 
                AND course_id = $courseId";
        $result = $this->db->select($query);
        return $result->fetch_assoc();
    }

    public function updateProgress($studentId, $courseId, $progressData) {
        $completionPercentage = $this->db->escapeString($progressData['completion_percentage']);
        $lastAccessDate = date('Y-m-d H:i:s');
        $currentModule = $this->db->escapeString($progressData['current_module']);
        
        $query = "INSERT INTO course_progress 
                (student_id, course_id, completion_percentage, last_access_date, current_module) 
                VALUES ($studentId, $courseId, $completionPercentage, '$lastAccessDate', '$currentModule')
                ON DUPLICATE KEY UPDATE 
                completion_percentage = $completionPercentage,
                last_access_date = '$lastAccessDate',
                current_module = '$currentModule'";
        
        return $this->db->execute($query);
    }

    public function getStudentCourses($studentId) {
        $query = "SELECT c.*, cp.completion_percentage, cp.last_access_date, cp.current_module 
                FROM courses c
                JOIN course_enrollments ce ON c.course_id = ce.course_id
                LEFT JOIN course_progress cp ON c.course_id = cp.course_id 
                    AND cp.student_id = ce.student_id
                WHERE ce.student_id = $studentId
                ORDER BY ce.enrollment_date DESC";
        return $this->db->select($query);
    }

    public function getModuleCompletion($studentId, $courseId) {
        $query = "SELECT m.*, mc.completion_date 
                FROM course_modules m
                LEFT JOIN module_completion mc ON m.module_id = mc.module_id 
                    AND mc.student_id = $studentId
                WHERE m.course_id = $courseId
                ORDER BY m.module_order";
        return $this->db->select($query);
    }

    public function markModuleComplete($studentId, $moduleId) {
        $completionDate = date('Y-m-d H:i:s');
        $query = "INSERT INTO module_completion 
                (student_id, module_id, completion_date)
                VALUES ($studentId, $moduleId, '$completionDate')";
        return $this->db->execute($query);
    }

    public function getAssignmentStatus($studentId, $courseId) {
        $query = "SELECT a.*, sa.submission_date, sa.status, sa.grade
                FROM course_assignments a
                LEFT JOIN student_assignments sa ON a.assignment_id = sa.assignment_id 
                    AND sa.student_id = $studentId
                WHERE a.course_id = $courseId
                ORDER BY a.due_date";
        return $this->db->select($query);
    }
}

// Example usage:
/*
$courseTracker = new CourseTracker();

// Get student's course progress
$progress = $courseTracker->getCourseProgress(1, 101);

// Update progress
$progressData = [
    'completion_percentage' => 75,
    'current_module' => 'Module 5: Advanced Topics'
];
$courseTracker->updateProgress(1, 101, $progressData);

// Get all courses for a student
$studentCourses = $courseTracker->getStudentCourses(1);
*/
?>
