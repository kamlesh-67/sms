<?php
require_once 'db_connect.php';

// Check if user is logged in and has permission
if (!isLoggedIn() || !hasPermission('admin')) {
    header("Location: index.php");
    exit();
}

$student = null;
$isEdit = false;
$error = '';

// Get departments and courses for dropdowns
$departments = $conn->query("SELECT * FROM departments ORDER BY department_name");
$courses = $conn->query("SELECT * FROM courses ORDER BY course_name");

// If editing, get student data
if (isset($_GET['id'])) {
    $student_id = (int)$_GET['id'];
    $student = getStudentData($student_id);
    
    if ($student) {
        $isEdit = true;
    } else {
        header("Location: studentInfo.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $department_id = (int)$_POST['department_id'];
    $course_id = (int)$_POST['course_id'];
    $status = sanitizeInput($_POST['status']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $address = sanitizeInput($_POST['address']);
    $admission_date = sanitizeInput($_POST['admission_date']);
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($date_of_birth) || 
        empty($gender) || empty($admission_date)) {
        $error = "Please fill in all required fields";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            if ($isEdit) {
                // Check if email is already taken by another student
                $sql = "SELECT student_id FROM students WHERE email = ? AND student_id != ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $email, $student['student_id']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Email address is already registered with another student");
                }
                
                // Update student
                $sql = "UPDATE students SET 
                        name = ?, 
                        email = ?, 
                        phone = ?, 
                        department_id = ?, 
                        course_id = ?, 
                        status = ?,
                        date_of_birth = ?,
                        gender = ?,
                        address = ?,
                        admission_date = ?
                        WHERE student_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssiisssssi", 
                    $name, $email, $phone, $department_id, $course_id, $status,
                    $date_of_birth, $gender, $address, $admission_date, $student['student_id']
                );
                $stmt->execute();
                
                // Update user account if exists
                if ($student['user_id']) {
                    $sql = "UPDATE users SET 
                            email = ?, 
                            status = ? 
                            WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssi", $email, $status, $student['user_id']);
                    $stmt->execute();
                }
            } else {
                // Check if email is already taken
                $sql = "SELECT student_id FROM students WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Email address is already registered");
                }
                
                // Create user account first
                $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999);
                $password = password_hash($username, PASSWORD_DEFAULT); // Default password is username
                
                $sql = "INSERT INTO users (username, password, email, role, full_name, status) 
                        VALUES (?, ?, ?, 'student', ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $username, $password, $email, $name, $status);
                $stmt->execute();
                $user_id = $conn->insert_id;
                
                // Add student
                $sql = "INSERT INTO students (
                            user_id, name, email, phone, department_id, course_id, 
                            status, date_of_birth, gender, address, admission_date
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssiisssss", 
                    $user_id, $name, $email, $phone, $department_id, $course_id, 
                    $status, $date_of_birth, $gender, $address, $admission_date
                );
                $stmt->execute();
            }
            
            $conn->commit();
            $_SESSION['success'] = $isEdit ? "Student updated successfully" : "Student added successfully";
            header("Location: studentInfo.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

        <main class="main-content">
            <div class="content-header">
                <h1><?php echo $isEdit ? 'Edit Student' : 'Add New Student'; ?></h1>
                <a href="studentInfo.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['name'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="department_id">Department *</label>
                                <select id="department_id" name="department_id" class="form-control" required>
                                    <option value="">Select Department</option>
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['department_id']; ?>" 
                                                <?php echo (isset($student['department_id']) && $student['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="course_id">Course *</label>
                                <select id="course_id" name="course_id" class="form-control" required>
                                    <option value="">Select Course</option>
                                    <?php while ($course = $courses->fetch_assoc()): ?>
                                        <option value="<?php echo $course['course_id']; ?>" 
                                                <?php echo (isset($student['course_id']) && $student['course_id'] == $course['course_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="status">Status *</label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="active" <?php echo (isset($student['status']) && $student['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo (isset($student['status']) && $student['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="graduated" <?php echo (isset($student['status']) && $student['status'] == 'graduated') ? 'selected' : ''; ?>>Graduated</option>
                                    <option value="suspended" <?php echo (isset($student['status']) && $student['status'] == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="date_of_birth">Date of Birth *</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="gender">Gender *</label>
                                <select id="gender" name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo (isset($student['gender']) && $student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($student['gender']) && $student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo (isset($student['gender']) && $student['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="admission_date">Admission Date *</label>
                                <input type="date" id="admission_date" name="admission_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['admission_date'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <?php if ($isEdit && isset($student['username'])): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                Student's login username: <strong><?php echo htmlspecialchars($student['username']); ?></strong>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $isEdit ? 'Update Student' : 'Add Student'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <style>
            .content-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
            }

            .content-header h1 {
                color: var(--text-color);
                font-size: 1.8rem;
                margin: 0;
            }

            .card {
                background-color: var(--card-color);
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                margin-bottom: 2rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .form-row {
                display: flex;
                flex-wrap: wrap;
                margin-right: -0.5rem;
                margin-left: -0.5rem;
                margin-bottom: 1rem;
            }

            .form-group {
                padding: 0 0.5rem;
                margin-bottom: 1rem;
            }

            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                color: var(--text-color);
                font-weight: 500;
            }

            .form-control {
                width: 100%;
                padding: 0.75rem 1rem;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                font-size: 1rem;
                transition: all 0.3s ease;
            }

            .form-control:focus {
                outline: none;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
            }

            textarea.form-control {
                resize: vertical;
                min-height: 100px;
            }

            .alert {
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .alert-danger {
                background-color: #fee2e2;
                color: #991b1b;
                border: 1px solid #fecaca;
            }

            .alert-info {
                background-color: #e3f2fd;
                color: #1e40af;
                border: 1px solid #dbeafe;
            }

            .form-actions {
                margin-top: 2rem;
                text-align: right;
            }

            .btn {
                padding: 0.5rem 1rem;
                border-radius: 6px;
                font-size: 0.875rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }

            .btn-primary {
                background-color: var(--primary-color);
                color: white;
                border: none;
            }

            .btn-primary:hover {
                background-color: var(--hover-color);
            }

            .btn-secondary {
                background-color: #6c757d;
                color: white;
                border: none;
            }

            .btn-secondary:hover {
                background-color: #5a6268;
            }

            @media (max-width: 768px) {
                .form-group {
                    flex: 0 0 100%;
                }
            }
        </style>

<?php include 'includes/footer.php'; ?> 