<?php
require_once 'dbms.php';

class StudentServices {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Student Profile Methods
    public function createStudent($data) {
        try {
            // Validate required fields
            $requiredFields = ['name', 'email', 'phone'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("$field is required");
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Check if email already exists
            $email = $this->db->escapeString($data['email']);
            $checkQuery = "SELECT COUNT(*) as count FROM students WHERE email = '$email'";
            $result = $this->db->select($checkQuery);
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                throw new Exception("Email already exists");
            }

            // Prepare data for insertion
            $name = $this->db->escapeString($data['name']);
            $phone = $this->db->escapeString($data['phone']);
            $address = $this->db->escapeString($data['address'] ?? '');
            $dob = !empty($data['date_of_birth']) ? "'" . $this->db->escapeString($data['date_of_birth']) . "'" : "NULL";
            $gender = !empty($data['gender']) ? "'" . $this->db->escapeString($data['gender']) . "'" : "NULL";
            $admissionDate = !empty($data['admission_date']) ? "'" . $this->db->escapeString($data['admission_date']) . "'" : "'" . date('Y-m-d') . "'";
            $status = $this->db->escapeString($data['status'] ?? 'active');

            // Start transaction
            $this->db->beginTransaction();

            // Insert into students table
            $query = "INSERT INTO students (name, email, phone, address, date_of_birth, gender, admission_date, status) 
                     VALUES ('$name', '$email', '$phone', '$address', $dob, $gender, $admissionDate, '$status')";
            $this->db->execute($query);
            
            $studentId = $this->db->getLastInsertId();
            
            // If user account creation is requested
            if (!empty($data['create_account']) && $data['create_account'] === true) {
                // Create user account
                $username = strtolower(str_replace(' ', '', $name)) . $studentId;
                $defaultPassword = md5('student123'); // In production, generate a random password
                
                $userQuery = "INSERT INTO users (username, password, email, role, full_name, status) 
                             VALUES ('$username', '$defaultPassword', '$email', 'student', '$name', 'active')";
                $this->db->execute($userQuery);
                
                $userId = $this->db->getLastInsertId();
                
                // Link user account to student
                $updateQuery = "UPDATE students SET user_id = $userId WHERE student_id = $studentId";
                $this->db->execute($updateQuery);
            }
            
            // Commit transaction
            $this->db->commitTransaction();
            
            return ['success' => true, 'student_id' => $studentId, 'message' => 'Student created successfully'];
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollbackTransaction();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateStudent($studentId, $data) {
        try {
            // Validate student exists
            if (!$this->studentExists($studentId)) {
                throw new Exception("Student not found");
            }

            // Validate email if it's being updated
            if (isset($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format");
                }
                
                // Check if email already exists for another student
                $email = $this->db->escapeString($data['email']);
                $checkQuery = "SELECT COUNT(*) as count FROM students 
                              WHERE email = '$email' AND student_id != $studentId";
                $result = $this->db->select($checkQuery);
                $row = $result->fetch_assoc();
                
                if ($row['count'] > 0) {
                    throw new Exception("Email already in use by another student");
                }
            }

            // Prepare update fields
            $updates = [];
            
            $updateFields = ['name', 'email', 'phone', 'address', 'date_of_birth', 'gender', 'status'];
            
            foreach ($updateFields as $field) {
                if (isset($data[$field])) {
                    $value = $this->db->escapeString($data[$field]);
                    
                    // Handle date fields
                    if (in_array($field, ['date_of_birth']) && !empty($value)) {
                        $updates[] = "$field = '$value'";
                    } else if (!empty($value)) {
                        $updates[] = "$field = '$value'";
                    }
                }
            }
            
            if (empty($updates)) {
                throw new Exception("No valid fields to update");
            }
            
            $updateString = implode(', ', $updates);
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Update student record
            $query = "UPDATE students SET $updateString WHERE student_id = $studentId";
            $this->db->execute($query);
            
            // Update linked user account if email or name is changed
            if (isset($data['email']) || isset($data['name'])) {
                $studentData = $this->getStudentById($studentId);
                
                if ($studentData && $studentData['user_id']) {
                    $userId = $studentData['user_id'];
                    $userUpdates = [];
                    
                    if (isset($data['email'])) {
                        $email = $this->db->escapeString($data['email']);
                        $userUpdates[] = "email = '$email'";
                    }
                    
                    if (isset($data['name'])) {
                        $name = $this->db->escapeString($data['name']);
                        $userUpdates[] = "full_name = '$name'";
                    }
                    
                    if (!empty($userUpdates)) {
                        $userUpdateString = implode(', ', $userUpdates);
                        $userQuery = "UPDATE users SET $userUpdateString WHERE user_id = $userId";
                        $this->db->execute($userQuery);
                    }
                }
            }
            
            // Commit transaction
            $this->db->commitTransaction();
            
            return ['success' => true, 'message' => 'Student updated successfully'];
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollbackTransaction();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteStudent($studentId) {
        try {
            // Validate student exists
            if (!$this->studentExists($studentId)) {
                throw new Exception("Student not found");
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Get user_id if exists
            $studentData = $this->getStudentById($studentId);
            $userId = $studentData['user_id'] ?? null;
            
            // Delete student record
            $query = "DELETE FROM students WHERE student_id = $studentId";
            $this->db->execute($query);
            
            // Delete associated user account if exists
            if ($userId) {
                $userQuery = "DELETE FROM users WHERE user_id = $userId";
                $this->db->execute($userQuery);
            }
            
            // Commit transaction
            $this->db->commitTransaction();
            
            return ['success' => true, 'message' => 'Student deleted successfully'];
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollbackTransaction();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Fee Management Methods
    public function addFeePayment($studentId, $amount, $type, $date, $status = 'paid', $paymentMethod = null, $remarks = null) {
        try {
            // Validate student exists
            if (!$this->studentExists($studentId)) {
                throw new Exception("Student not found");
            }
            
            // Validate amount
            if (!is_numeric($amount) || $amount <= 0) {
                throw new Exception("Invalid amount");
            }
            
            // Prepare data
            $amount = (float) $amount;
            $type = $this->db->escapeString($type);
            $date = $this->db->escapeString($date);
            $status = $this->db->escapeString($status);
            $paymentMethod = $paymentMethod ? "'" . $this->db->escapeString($paymentMethod) . "'" : "NULL";
            $remarks = $remarks ? "'" . $this->db->escapeString($remarks) . "'" : "NULL";
            $receiptNumber = "RCPT" . time() . rand(1000, 9999);
            
            // Insert fee record
            $query = "INSERT INTO fees (student_id, amount, fee_type, payment_date, status, payment_method, receipt_number, remarks) 
                     VALUES ($studentId, $amount, '$type', '$date', '$status', $paymentMethod, '$receiptNumber', $remarks)";
            $this->db->execute($query);
            
            $feeId = $this->db->getLastInsertId();
            
            return ['success' => true, 'fee_id' => $feeId, 'receipt_number' => $receiptNumber, 'message' => 'Fee payment recorded successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getFeeHistory($studentId) {
        try {
            // Validate student exists
            if (!$this->studentExists($studentId)) {
                throw new Exception("Student not found");
            }
            
            $query = "SELECT * FROM fees WHERE student_id = $studentId ORDER BY payment_date DESC";
            $result = $this->db->select($query);
            
            $fees = [];
            while ($row = $result->fetch_assoc()) {
                $fees[] = $row;
            }
            
            return ['success' => true, 'data' => $fees];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Attendance Methods
    public function markAttendance($studentId, $courseId, $date, $status, $remarks = null) {
        try {
            // Validate student exists
            if (!$this->studentExists($studentId)) {
                throw new Exception("Student not found");
            }
            
            // Validate course exists
            $courseQuery = "SELECT COUNT(*) as count FROM courses WHERE course_id = $courseId";
            $courseResult = $this->db->select($courseQuery);
            $courseRow = $courseResult->fetch_assoc();
            
            if ($courseRow['count'] == 0) {
                throw new Exception("Course not found");
            }
            
            // Validate student is enrolled in the course
            $enrollmentQuery = "SELECT COUNT(*) as count FROM student_courses 
                               WHERE student_id = $studentId AND course_id = $courseId";
            $enrollmentResult = $this->db->select($enrollmentQuery);
            $enrollmentRow = $enrollmentResult->fetch_assoc();
            
            if ($enrollmentRow['count'] == 0) {
                throw new Exception("Student is not enrolled in this course");
            }
            
            // Prepare data
            $date = $this->db->escapeString($date);
            $status = $this->db->escapeString($status);
            $remarks = $remarks ? "'" . $this->db->escapeString($remarks) . "'" : "NULL";
            $recordedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "NULL";
            
            // Check if attendance already marked for this date and course
            $checkQuery = "SELECT id FROM attendance 
                          WHERE student_id = $studentId AND course_id = $courseId AND date = '$date'";
            $checkResult = $this->db->select($checkQuery);
            
            if ($checkResult->num_rows > 0) {
                // Update existing attendance
                $attendanceRow = $checkResult->fetch_assoc();
                $attendanceId = $attendanceRow['id'];
                
                $updateQuery = "UPDATE attendance 
                               SET status = '$status', remarks = $remarks, recorded_by = $recordedBy 
                               WHERE id = $attendanceId";
                $this->db->execute($updateQuery);
                
                return ['success' => true, 'message' => 'Attendance updated successfully'];
            } else {
                // Insert new attendance record
                $query = "INSERT INTO attendance (student_id, course_id, date, status, remarks, recorded_by) 
                         VALUES ($studentId, $courseId, '$date', '$status', $remarks, $recordedBy)";
                $this->db->execute($query);
                
                return ['success' => true, 'message' => 'Attendance marked successfully'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAttendanceReport($studentId, $startDate, $endDate, $courseId = null) {
        try {
            // Validate student exists
            if (!$this->studentExists($studentId)) {
                throw new Exception("Student not found");
            }
            
            // Prepare query
            $query = "SELECT a.*, c.course_name, c.course_code 
                     FROM attendance a
                     JOIN courses c ON a.course_id = c.course_id
                     WHERE a.student_id = $studentId 
                     AND a.date BETWEEN '$startDate' AND '$endDate'";
            
            if ($courseId) {
                $query .= " AND a.course_id = $courseId";
            }
            
            $query .= " ORDER BY a.date DESC";
            
            $result = $this->db->select($query);
            
            $attendance = [];
            while ($row = $result->fetch_assoc()) {
                $attendance[] = $row;
            }
            
            return ['success' => true, 'data' => $attendance];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Leave Management Methods
    public function applyLeave($studentId, $startDate, $endDate, $reason) {
        try {
            // Validate student exists
            if (!$this->studentExists($studentId)) {
                throw new Exception("Student not found");
            }
            
            // Validate dates
            if (strtotime($startDate) > strtotime($endDate)) {
                throw new Exception("End date cannot be before start date");
            }
            
            if (strtotime($startDate) < strtotime(date('Y-m-d'))) {
                throw new Exception("Start date cannot be in the past");
            }
            
            // Prepare data
            $startDate = $this->db->escapeString($startDate);
            $endDate = $this->db->escapeString($endDate);
            $reason = $this->db->escapeString($reason);
            
            // Check for overlapping leaves
            $overlapQuery = "SELECT COUNT(*) as count FROM leaves 
                           WHERE student_id = $studentId 
                           AND ((start_date BETWEEN '$startDate' AND '$endDate') 
                           OR (end_date BETWEEN '$startDate' AND '$endDate')
                           OR ('$startDate' BETWEEN start_date AND end_date))
                           AND status != 'rejected'";
            $overlapResult = $this->db->select($overlapQuery);
            $overlapRow = $overlapResult->fetch_assoc();
            
            if ($overlapRow['count'] > 0) {
                throw new Exception("Leave application overlaps with existing approved or pending leave");
            }
            
            // Insert leave record
            $query = "INSERT INTO leaves (student_id, start_date, end_date, reason, status) 
                     VALUES ($studentId, '$startDate', '$endDate', '$reason', 'pending')";
            $this->db->execute($query);
            
            $leaveId = $this->db->getLastInsertId();
            
            return ['success' => true, 'leave_id' => $leaveId, 'message' => 'Leave application submitted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateLeaveStatus($leaveId, $status, $remarks = null) {
        try {
            // Validate leave exists
            $leaveQuery = "SELECT * FROM leaves WHERE leave_id = $leaveId";
            $leaveResult = $this->db->select($leaveQuery);
            
            if ($leaveResult->num_rows == 0) {
                throw new Exception("Leave application not found");
            }
            
            // Validate status
            $validStatuses = ['pending', 'approved', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status value");
            }
            
            // Prepare data
            $status = $this->db->escapeString($status);
            $remarks = $remarks ? "'" . $this->db->escapeString($remarks) . "'" : "NULL";
            $approvedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "NULL";
            
            // Update leave status
            $query = "UPDATE leaves 
                     SET status = '$status', 
                         remarks = $remarks, 
                         approved_by = $approvedBy, 
                         response_date = NOW() 
                     WHERE leave_id = $leaveId";
            $this->db->execute($query);
            
            return ['success' => true, 'message' => 'Leave status updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Course Management Methods
    public function enrollCourse($studentId, $courseId) {
        try {
            // Validate student exists
            if (!$this->studentExists($studentId)) {
                throw new Exception("Student not found");
            }
            
            // Validate course exists
            $courseQuery = "SELECT COUNT(*) as count FROM courses WHERE course_id = $courseId";
            $courseResult = $this->db->select($courseQuery);
            $courseRow = $courseResult->fetch_assoc();
            
            if ($courseRow['count'] == 0) {
                throw new Exception("Course not found");
            }
            
            // Check if already enrolled
            $enrollmentQuery = "SELECT COUNT(*) as count FROM student_courses 
                               WHERE student_id = $studentId AND course_id = $courseId";
            $enrollmentResult = $this->db->select($enrollmentQuery);
            $enrollmentRow = $enrollmentResult->fetch_assoc();
            
            if ($enrollmentRow['count'] > 0) {
                throw new Exception("Student is already enrolled in this course");
            }
            
            // Enroll student in course
            $query = "INSERT INTO student_courses (student_id, course_id) 
                     VALUES ($studentId, $courseId)";
            $this->db->execute($query);
            
            return ['success' => true, 'message' => 'Student enrolled in course successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function dropCourse($studentId, $courseId) {
        try {
            // Validate enrollment exists
            $enrollmentQuery = "SELECT COUNT(*) as count FROM student_courses 
                               WHERE student_id = $studentId AND course_id = $courseId";
            $enrollmentResult = $this->db->select($enrollmentQuery);
            $enrollmentRow = $enrollmentResult->fetch_assoc();
            
            if ($enrollmentRow['count'] == 0) {
                throw new Exception("Student is not enrolled in this course");
            }
            
            // Drop course
            $query = "DELETE FROM student_courses 
                     WHERE student_id = $studentId AND course_id = $courseId";
            $this->db->execute($query);
            
            return ['success' => true, 'message' => 'Course dropped successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getEnrolledCourses($studentId) {
        try {
            // Validate student exists
            if (!$this->studentExists($studentId)) {
                throw new Exception("Student not found");
            }
            
            $query = "SELECT c.*, sc.enrollment_date, sc.status, sc.grade 
                     FROM courses c 
                     JOIN student_courses sc ON c.course_id = sc.course_id 
                     WHERE sc.student_id = $studentId
                     ORDER BY sc.enrollment_date DESC";
            $result = $this->db->select($query);
            
            $courses = [];
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
            
            return ['success' => true, 'data' => $courses];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Event Management Methods
    public function registerForEvent($studentId, $eventId) {
        try {
            // Validate student exists
            if (!$this->studentExists($studentId)) {
                throw new Exception("Student not found");
            }
            
            // Validate event exists
            $eventQuery = "SELECT * FROM events WHERE event_id = $eventId";
            $eventResult = $this->db->select($eventQuery);
            
            if ($eventResult->num_rows == 0) {
                throw new Exception("Event not found");
            }
            
            $event = $eventResult->fetch_assoc();
            
            // Check if event date has passed
            if (strtotime($event['event_date']) < strtotime(date('Y-m-d'))) {
                throw new Exception("Cannot register for past events");
            }
            
            // Check if already registered
            $registrationQuery = "SELECT COUNT(*) as count FROM event_registrations 
                                 WHERE student_id = $studentId AND event_id = $eventId";
            $registrationResult = $this->db->select($registrationQuery);
            $registrationRow = $registrationResult->fetch_assoc();
            
            if ($registrationRow['count'] > 0) {
                throw new Exception("Student is already registered for this event");
            }
            
            // Register for event
            $query = "INSERT INTO event_registrations (student_id, event_id) 
                     VALUES ($studentId, $eventId)";
            $this->db->execute($query);
            
            return ['success' => true, 'message' => 'Registered for event successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function cancelEventRegistration($studentId, $eventId) {
        try {
            // Check if registration exists
            $registrationQuery = "SELECT * FROM event_registrations 
                                 WHERE student_id = $studentId AND event_id = $eventId";
            $registrationResult = $this->db->select($registrationQuery);
            
            if ($registrationResult->num_rows == 0) {
                throw new Exception("Student is not registered for this event");
            }
            
            // Validate event hasn't occurred yet
            $eventQuery = "SELECT * FROM events WHERE event_id = $eventId";
            $eventResult = $this->db->select($eventQuery);
            $event = $eventResult->fetch_assoc();
            
            if (strtotime($event['event_date']) < strtotime(date('Y-m-d'))) {
                throw new Exception("Cannot cancel registration for past events");
            }
            
            // Cancel registration
            $query = "DELETE FROM event_registrations 
                     WHERE student_id = $studentId AND event_id = $eventId";
            $this->db->execute($query);
            
            return ['success' => true, 'message' => 'Event registration cancelled successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getRegisteredEvents($studentId) {
        try {
            // Validate student exists
            if (!$this->studentExists($studentId)) {
                throw new Exception("Student not found");
            }
            
            $query = "SELECT e.*, er.registration_date, er.status
                     FROM events e 
                     JOIN event_registrations er ON e.event_id = er.event_id 
                     WHERE er.student_id = $studentId
                     ORDER BY e.event_date ASC";
            $result = $this->db->select($query);
            
            $events = [];
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
            
            return ['success' => true, 'data' => $events];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Helper Methods
    public function getStudentById($studentId) {
        $query = "SELECT * FROM students WHERE student_id = $studentId";
        $result = $this->db->select($query);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }

    public function searchStudents($searchTerm, $limit = 20, $offset = 0) {
        $searchTerm = $this->db->escapeString($searchTerm);
        
        $query = "SELECT * FROM students 
                 WHERE name LIKE '%$searchTerm%' 
                 OR email LIKE '%$searchTerm%'
                 OR phone LIKE '%$searchTerm%'
                 LIMIT $limit OFFSET $offset";
        
        $result = $this->db->select($query);
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        return $students;
    }

    public function getAllStudents($limit = 20, $offset = 0, $status = null) {
        $query = "SELECT * FROM students";
        
        if ($status) {
            $status = $this->db->escapeString($status);
            $query .= " WHERE status = '$status'";
        }
        
        $query .= " ORDER BY name ASC LIMIT $limit OFFSET $offset";
        
        $result = $this->db->select($query);
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        return $students;
    }

    private function studentExists($studentId) {
        $query = "SELECT COUNT(*) as count FROM students WHERE student_id = $studentId";
        $result = $this->db->select($query);
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
}

// Example usage:
/*
$studentServices = new StudentServices();

// Create new student
$studentData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '1234567890',
    'address' => '123 Main St'
];
$studentServices->createStudent($studentData);

// Mark attendance
$studentServices->markAttendance(1, '2023-10-15', 'present');

// Apply for leave
$studentServices->applyLeave(1, '2023-11-01', '2023-11-03', 'Family function');

// Enroll in course
$studentServices->enrollCourse(1, 101);
*/
?>
