<?php
class ProfileServices {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getProfile($userId) {
        $query = "SELECT u.*, s.* 
                FROM users u
                LEFT JOIN students s ON u.user_id = s.user_id 
                WHERE u.user_id = $userId";
        return $this->db->select($query)->fetch_assoc();
    }

    public function updateProfile($userId, $data) {
        $name = $this->db->escapeString($data['name']);
        $email = $this->db->escapeString($data['email']);
        $phone = $this->db->escapeString($data['phone']);
        $address = $this->db->escapeString($data['address']);

        $query = "UPDATE students 
                SET name = '$name',
                    email = '$email', 
                    phone = '$phone',
                    address = '$address'
                WHERE user_id = $userId";
        return $this->db->execute($query);
    }

    public function updatePassword($userId, $oldPassword, $newPassword) {
        $oldPassword = md5($oldPassword);
        $newPassword = md5($newPassword);

        // First verify old password
        $query = "SELECT user_id FROM users 
                WHERE user_id = $userId 
                AND password = '$oldPassword'";
        $result = $this->db->select($query);

        if ($result && $result->num_rows > 0) {
            $query = "UPDATE users 
                    SET password = '$newPassword' 
                    WHERE user_id = $userId";
            return $this->db->execute($query);
        }
        return false;
    }

    public function updateProfilePhoto($userId, $photoPath) {
        $photoPath = $this->db->escapeString($photoPath);
        $query = "UPDATE students 
                SET profile_photo = '$photoPath' 
                WHERE user_id = $userId";
        return $this->db->execute($query);
    }

    public function getProfileStats($userId) {
        $query = "SELECT 
                    (SELECT COUNT(*) FROM leave_requests 
                     WHERE student_id = s.student_id) as total_leaves,
                    (SELECT SUM(amount) FROM fee_records 
                     WHERE student_id = s.student_id 
                     AND status = 'pending') as pending_fees,
                    (SELECT COUNT(*) FROM attendance 
                     WHERE student_id = s.student_id 
                     AND status = 'present') as attendance_count
                FROM students s
                WHERE s.user_id = $userId";
        return $this->db->select($query)->fetch_assoc();
    }
}

// Example usage:
/*
$profileServices = new ProfileServices();

// Get user profile
$profile = $profileServices->getProfile(1);

// Update profile
$profileData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '1234567890',
    'address' => '123 Main St'
];
$profileServices->updateProfile(1, $profileData);

// Change password
$profileServices->updatePassword(1, 'oldpass', 'newpass');

// Update profile photo
$profileServices->updateProfilePhoto(1, 'uploads/photo.jpg');

// Get profile statistics
$stats = $profileServices->getProfileStats(1);
*/
?>
