<?php
require_once 'db_connect.php';

// Check if user is logged in and has permission
if (!isLoggedIn() || (!hasPermission('admin') && !hasPermission('teacher'))) {
    header("Location: index.php");
    exit();
}

// Handle search and filtering
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$department_filter = isset($_GET['department']) ? (int)$_GET['department'] : 0;

// Build base query
$sql = "SELECT s.*, c.course_name, d.department_name, u.username, u.status as user_status 
        FROM students s 
        LEFT JOIN courses c ON s.course_id = c.course_id 
        LEFT JOIN departments d ON s.department_id = d.department_id 
        LEFT JOIN users u ON s.user_id = u.user_id 
        WHERE 1=1";

$params = [];
$types = "";

// Add search filters
if ($search) {
    $sql .= " AND (s.name LIKE ? OR s.email LIKE ? OR s.student_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($status_filter) {
    $sql .= " AND s.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($department_filter) {
    $sql .= " AND s.department_id = ?";
    $params[] = $department_filter;
    $types .= "i";
}

// Add sorting
$sql .= " ORDER BY s.student_id DESC";

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total records for pagination
$count_sql = str_replace("s.*, c.course_name", "COUNT(*) as total", $sql);
$count_sql = preg_replace("/ORDER BY.*/", "", $count_sql);
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Add pagination to main query
$sql .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

// Execute main query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get departments for filter dropdown
$departments = $conn->query("SELECT * FROM departments ORDER BY department_name");

// Handle student deletion
if (isset($_POST['delete_student']) && isset($_POST['student_id'])) {
    $student_id = (int)$_POST['student_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get student data using the helper function
        $student_data = getStudentData($student_id);
        if (!$student_data) {
            throw new Exception("Student not found");
        }
        
        // Delete student
        $sql = "DELETE FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        
        // Delete associated user account if exists
        if ($student_data['user_id']) {
            $sql = "DELETE FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $student_data['user_id']);
            $stmt->execute();
        }
        
        $conn->commit();
        $_SESSION['success'] = "Student deleted successfully";
        
        // Log the activity
        logUserActivity($_SESSION['user_id'], 'delete_student', "Deleted student ID: $student_id");
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
    }
    
    header("Location: studentInfo.php");
    exit();
}
?>

<?php include 'includes/header.php'; ?>

        <main class="main-content">
            <div class="content-header">
                <h1>Student Information</h1>
                <a href="add_Edit_Student.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Student
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <!-- Search and Filter Form -->
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by name, email, or ID"
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="graduated" <?php echo $status_filter === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <select name="department" class="form-control">
                                    <option value="">All Departments</option>
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['department_id']; ?>" 
                                                <?php echo $department_filter == $dept['department_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['course_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $row['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="add_Edit_Student.php?id=<?php echo $row['student_id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="view_student.php?id=<?php echo $row['student_id']; ?>" 
                                                       class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to delete this student? This action cannot be undone.');">
                                                        <input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
                                                        <button type="submit" name="delete_student" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No students found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="pagination">
                            <ul>
                                <?php if ($page > 1): ?>
                                    <li>
                                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&department=<?php echo $department_filter; ?>" 
                                           class="btn btn-sm btn-secondary">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&department=<?php echo $department_filter; ?>" 
                                           class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li>
                                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&department=<?php echo $department_filter; ?>" 
                                           class="btn btn-sm btn-secondary">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
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

            .filter-form {
                margin-bottom: 2rem;
            }

            .table {
                width: 100%;
                border-collapse: collapse;
            }

            .table th,
            .table td {
                padding: 1rem;
                text-align: left;
                border-bottom: 1px solid #eee;
            }

            .table th {
                background-color: #f8f9fa;
                font-weight: 600;
                color: var(--text-color);
            }

            .table tr:hover {
                background-color: #f8f9fa;
            }

            .status-badge {
                padding: 0.25rem 0.75rem;
                border-radius: 50px;
                font-size: 0.875rem;
                font-weight: 500;
            }

            .status-active {
                background-color: #e3fcef;
                color: #0a7b3e;
            }

            .status-inactive {
                background-color: #fee2e2;
                color: #991b1b;
            }

            .action-buttons {
                display: flex;
                gap: 0.5rem;
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

            .btn-danger {
                background-color: #dc3545;
                color: white;
                border: none;
            }

            .btn-danger:hover {
                background-color: #c82333;
            }

            .btn-info {
                background-color: #17a2b8;
                color: white;
                border: none;
            }

            .btn-info:hover {
                background-color: #138496;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            .table-responsive {
                overflow-x: auto;
            }

            .pagination {
                margin-top: 2rem;
            }

            .pagination ul {
                display: flex;
                justify-content: center;
                gap: 0.5rem;
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .pagination li {
                margin: 0;
            }

            .alert {
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .alert-success {
                background-color: #e3fcef;
                color: #0a7b3e;
                border: 1px solid #d1fae5;
            }

            .alert-danger {
                background-color: #fee2e2;
                color: #991b1b;
                border: 1px solid #fecaca;
            }
        </style>

<?php include 'includes/footer.php'; ?> 