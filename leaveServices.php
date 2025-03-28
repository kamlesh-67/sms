<?php

class LeaveServices {
    private $db;

    public function __construct() {
        require_once 'database.php';
        $this->db = new Database();
    }

    public function applyLeave($studentId, $startDate, $endDate, $reason, $type, $attachmentPath = null) {
        $studentId = $this->db->escapeString($studentId);
        $startDate = $this->db->escapeString($startDate);
        $endDate = $this->db->escapeString($endDate);
        $reason = $this->db->escapeString($reason);
        $type = $this->db->escapeString($type);
        $attachmentPath = $attachmentPath ? $this->db->escapeString($attachmentPath) : null;
        
        $query = "INSERT INTO leave_requests 
                (student_id, start_date, end_date, reason, leave_type, attachment, status, applied_date)
                VALUES ($studentId, '$startDate', '$endDate', '$reason', '$type', " .
                ($attachmentPath ? "'$attachmentPath'" : "NULL") . 
                ", 'pending', NOW())";
        return $this->db->execute($query);
    }

    public function getLeaveRequests($studentId) {
        $query = "SELECT * FROM leave_requests 
                WHERE student_id = $studentId 
                ORDER BY applied_date DESC";
        return $this->db->select($query);
    }

    public function updateLeaveStatus($requestId, $status, $remarks = null) {
        $status = $this->db->escapeString($status);
        $remarks = $remarks ? $this->db->escapeString($remarks) : null;
        
        $query = "UPDATE leave_requests 
                SET status = '$status'" .
                ($remarks ? ", remarks = '$remarks'" : "") .
                " WHERE request_id = $requestId";
        return $this->db->execute($query);
    }

    public function cancelLeaveRequest($requestId, $studentId) {
        $query = "UPDATE leave_requests 
                SET status = 'cancelled' 
                WHERE request_id = $requestId 
                AND student_id = $studentId 
                AND status = 'pending'";
        return $this->db->execute($query);
    }

    public function getPendingLeaves() {
        $query = "SELECT lr.*, s.name as student_name 
                FROM leave_requests lr
                JOIN students s ON lr.student_id = s.student_id
                WHERE lr.status = 'pending'
                ORDER BY lr.applied_date ASC";
        return $this->db->select($query);
    }

    public function getLeaveHistory($studentId, $startDate = null, $endDate = null) {
        $query = "SELECT * FROM leave_requests 
                WHERE student_id = $studentId";
        
        if ($startDate && $endDate) {
            $startDate = $this->db->escapeString($startDate);
            $endDate = $this->db->escapeString($endDate);
            $query .= " AND start_date BETWEEN '$startDate' AND '$endDate'";
        }
        
        $query .= " ORDER BY applied_date DESC";
        return $this->db->select($query);
    }

    public function getLeaveStats($studentId, $year = null) {
        $year = $year ?: date('Y');
        $query = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                FROM leave_requests 
                WHERE student_id = $studentId 
                AND YEAR(applied_date) = $year";
        return $this->db->select($query)->fetch_assoc();
    }
}

// Example usage:
/*
$leaveServices = new LeaveServices();

// Apply for leave
$leaveData = [
    'student_id' => 1,
    'start_date' => '2023-12-01',
    'end_date' => '2023-12-03',
    'reason' => 'Family function',
    'type' => 'personal'
];
$leaveServices->applyLeave(
    $leaveData['student_id'],
    $leaveData['start_date'],
    $leaveData['end_date'],
    $leaveData['reason'],
    $leaveData['type']
);

// Get student's leave requests
$leaveRequests = $leaveServices->getLeaveRequests(1);

// Update leave status
$leaveServices->updateLeaveStatus(1, 'approved', 'Request approved by admin');

// Get leave statistics
$leaveStats = $leaveServices->getLeaveStats(1, 2023);
*/
?>
