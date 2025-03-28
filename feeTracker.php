<?php
class FeeTracker {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function addFeeRecord($studentData) {
        $studentId = $this->db->escapeString($studentData['student_id']);
        $feeType = $this->db->escapeString($studentData['fee_type']);
        $amount = $this->db->escapeString($studentData['amount']);
        $dueDate = $this->db->escapeString($studentData['due_date']);
        $status = 'pending';

        $query = "INSERT INTO fee_records 
                (student_id, fee_type, amount, due_date, status, created_at)
                VALUES 
                ($studentId, '$feeType', $amount, '$dueDate', '$status', NOW())";
        
        return $this->db->execute($query);
    }

    public function getFeeRecords($studentId) {
        $query = "SELECT * FROM fee_records 
                WHERE student_id = $studentId 
                ORDER BY due_date DESC";
        return $this->db->select($query);
    }

    public function updatePaymentStatus($recordId, $status, $paymentDate = null) {
        $status = $this->db->escapeString($status);
        $paymentDate = $paymentDate ?? date('Y-m-d');
        
        $query = "UPDATE fee_records 
                SET status = '$status',
                    payment_date = '$paymentDate',
                    updated_at = NOW()
                WHERE record_id = $recordId";
        return $this->db->execute($query);
    }

    public function getOverdueFees($studentId) {
        $currentDate = date('Y-m-d');
        $query = "SELECT * FROM fee_records 
                WHERE student_id = $studentId 
                AND status = 'pending'
                AND due_date < '$currentDate'
                ORDER BY due_date";
        return $this->db->select($query);
    }

    public function getFeeHistory($studentId) {
        $query = "SELECT * FROM fee_records 
                WHERE student_id = $studentId 
                AND status = 'paid'
                ORDER BY payment_date DESC";
        return $this->db->select($query);
    }

    public function getTotalOutstanding($studentId) {
        $query = "SELECT SUM(amount) as total 
                FROM fee_records 
                WHERE student_id = $studentId 
                AND status = 'pending'";
        $result = $this->db->select($query);
        return $result->fetch_assoc()['total'] ?? 0;
    }

    public function searchFeeRecords($searchTerm) {
        $searchTerm = $this->db->escapeString($searchTerm);
        $query = "SELECT fr.*, s.name as student_name 
                FROM fee_records fr
                JOIN students s ON fr.student_id = s.student_id
                WHERE s.name LIKE '%$searchTerm%'
                OR fr.fee_type LIKE '%$searchTerm%'
                ORDER BY fr.due_date DESC";
        return $this->db->select($query);
    }
}

// Example usage:
/*
$feeTracker = new FeeTracker();

// Add new fee record
$feeData = [
    'student_id' => 1,
    'fee_type' => 'Tuition',
    'amount' => 5000,
    'due_date' => '2024-01-15'
];
$feeTracker->addFeeRecord($feeData);

// Get student's fee records
$feeRecords = $feeTracker->getFeeRecords(1);

// Update payment status
$feeTracker->updatePaymentStatus(1, 'paid');

// Get overdue fees
$overdueFees = $feeTracker->getOverdueFees(1);
*/
?>
