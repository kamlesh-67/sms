<?php

class AdmissionServices {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function submitApplication($studentData) {
        // Personal Information
        $firstName = $this->db->escapeString($studentData['first_name']);
        $lastName = $this->db->escapeString($studentData['last_name']);
        $dob = $this->db->escapeString($studentData['dob']);
        $gender = $this->db->escapeString($studentData['gender']);
        $email = $this->db->escapeString($studentData['email']);
        $phone = $this->db->escapeString($studentData['phone']);
        $address = $this->db->escapeString($studentData['address']);
        $city = $this->db->escapeString($studentData['city']);
        $state = $this->db->escapeString($studentData['state']);
        $zipCode = $this->db->escapeString($studentData['zip_code']);

        // Academic Information
        $previousSchool = $this->db->escapeString($studentData['previous_school']);
        $graduationYear = $this->db->escapeString($studentData['graduation_year']);
        $gpa = $this->db->escapeString($studentData['gpa']);
        $desiredProgram = $this->db->escapeString($studentData['desired_program']);
        $startTerm = $this->db->escapeString($studentData['start_term']);

        // Guardian Information
        $guardianName = $this->db->escapeString($studentData['guardian_name']);
        $guardianRelation = $this->db->escapeString($studentData['guardian_relation']);
        $guardianPhone = $this->db->escapeString($studentData['guardian_phone']);
        $guardianEmail = $this->db->escapeString($studentData['guardian_email']);

        // Additional Information
        $extracurricularActivities = $this->db->escapeString($studentData['extracurricular']);
        $achievements = $this->db->escapeString($studentData['achievements']);
        $applicationDate = date('Y-m-d');
        $status = 'pending';

        $query = "INSERT INTO admission_applications 
                (first_name, last_name, dob, gender, email, phone, address, city, state, zip_code,
                previous_school, graduation_year, gpa, desired_program, start_term,
                guardian_name, guardian_relation, guardian_phone, guardian_email,
                extracurricular_activities, achievements, application_date, status)
                VALUES 
                ('$firstName', '$lastName', '$dob', '$gender', '$email', '$phone', '$address', 
                '$city', '$state', '$zipCode', '$previousSchool', '$graduationYear', '$gpa',
                '$desiredProgram', '$startTerm', '$guardianName', '$guardianRelation', 
                '$guardianPhone', '$guardianEmail', '$extracurricularActivities', 
                '$achievements', '$applicationDate', '$status')";
        
        return $this->db->execute($query);
    }

    public function getApplicationStatus($applicationId) {
        $query = "SELECT status FROM admission_applications WHERE application_id = $applicationId";
        $result = $this->db->select($query);
        return $result->fetch_assoc()['status'];
    }

    public function updateApplicationStatus($applicationId, $newStatus, $remarks = '') {
        $newStatus = $this->db->escapeString($newStatus);
        $remarks = $this->db->escapeString($remarks);
        $query = "UPDATE admission_applications 
                SET status = '$newStatus', 
                    remarks = '$remarks',
                    updated_at = NOW() 
                WHERE application_id = $applicationId";
        return $this->db->execute($query);
    }

    public function getAllApplications() {
        $query = "SELECT * FROM admission_applications ORDER BY application_date DESC";
        return $this->db->select($query);
    }

    public function getApplicationById($applicationId) {
        $query = "SELECT * FROM admission_applications WHERE application_id = $applicationId";
        $result = $this->db->select($query);
        return $result->fetch_assoc();
    }

    public function searchApplications($searchTerm) {
        $searchTerm = $this->db->escapeString($searchTerm);
        $query = "SELECT * FROM admission_applications 
                 WHERE first_name LIKE '%$searchTerm%' 
                 OR last_name LIKE '%$searchTerm%'
                 OR email LIKE '%$searchTerm%'
                 OR desired_program LIKE '%$searchTerm%'";
        return $this->db->select($query);
    }

    public function uploadDocument($applicationId, $documentType, $filePath) {
        $documentType = $this->db->escapeString($documentType);
        $filePath = $this->db->escapeString($filePath);
        
        $query = "INSERT INTO application_documents 
                (application_id, document_type, file_path, upload_date)
                VALUES 
                ($applicationId, '$documentType', '$filePath', NOW())";
        return $this->db->execute($query);
    }

    public function getApplicationDocuments($applicationId) {
        $query = "SELECT * FROM application_documents 
                WHERE application_id = $applicationId 
                ORDER BY upload_date DESC";
        return $this->db->select($query);
    }
}

// Example usage:
/*
$admissionServices = new AdmissionServices();

// Submit new application
$applicationData = [
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'dob' => '2000-05-15',
    'gender' => 'Female',
    'email' => 'jane@example.com',
    'phone' => '9876543210',
    'address' => '456 Park Avenue',
    'city' => 'Springfield',
    'state' => 'IL',
    'zip_code' => '62701',
    'previous_school' => 'Springfield High School',
    'graduation_year' => '2023',
    'gpa' => '3.8',
    'desired_program' => 'Computer Science',
    'start_term' => 'Fall 2024',
    'guardian_name' => 'John Smith',
    'guardian_relation' => 'Father',
    'guardian_phone' => '9876543211',
    'guardian_email' => 'john@example.com',
    'extracurricular' => 'Student Council, Debate Team',
    'achievements' => 'National Merit Scholar'
];
$admissionServices->submitApplication($applicationData);
*/
?>

