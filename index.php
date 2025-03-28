<?php
require_once 'db_connect.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user data
$userData = getUserData($_SESSION['user_id']);
$userRole = $userData['role'] ?? '';
$userFullName = $userData['full_name'] ?? 'User';

// Get dashboard statistics
$studentStats = [
    'total_students' => 0,
    'active_students' => 0,
    'inactive_students' => 0
];

$attendanceStats = [
    'total_attendance' => 0,
    'present_count' => 0,
    'absent_count' => 0,
    'late_count' => 0
];

$feeStats = [
    'total_fees' => 0,
    'collected_fees' => 0,
    'pending_fees' => 0,
    'overdue_fees' => 0
];

// Get student statistics
$sql = "SELECT 
            COUNT(*) as total_students,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_students,
            COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_students
        FROM students";
$result = $conn->query($sql);
if ($result) {
    $studentStats = $result->fetch_assoc();
}

// Get attendance statistics for today
$today = date('Y-m-d');
$sql = "SELECT 
            COUNT(*) as total_attendance,
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
            COUNT(CASE WHEN status = 'late' THEN 1 END) as late_count
        FROM attendance 
        WHERE date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $attendanceStats = $result->fetch_assoc();
}

// Get fee statistics
$sql = "SELECT 
            IFNULL(SUM(amount), 0) as total_fees,
            IFNULL(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as collected_fees,
            IFNULL(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_fees,
            IFNULL(SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END), 0) as overdue_fees
        FROM fees";
$result = $conn->query($sql);
if ($result) {
    $feeStats = $result->fetch_assoc();
}

// Get recent activities
$recentActivities = [];
$sql = "(SELECT 
            'attendance' as type,
            s.name as student_name,
            CONCAT('Marked as ', a.status) as action,
            c.course_name as entity,
            a.date as activity_date
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        JOIN courses c ON a.course_id = c.course_id
        ORDER BY a.attendance_date DESC
        LIMIT 10)
        
        UNION ALL
        
        (SELECT 
            'fee' as type,
            s.name as student_name,
            CONCAT('Payment of $', f.amount, ' (', f.status, ')') as action,
            f.fee_type as entity,
            f.payment_date as activity_date
        FROM fees f
        JOIN students s ON f.student_id = s.student_id
        ORDER BY f.created_at DESC
        LIMIT 10)
        
        UNION ALL
        
        (SELECT 
            'leave' as type,
            s.name as student_name,
            CONCAT('Leave ', l.status) as action,
            CONCAT(l.start_date, ' to ', l.end_date) as entity,
            l.request_date as activity_date
        FROM leaves l
        JOIN students s ON l.student_id = s.student_id
        ORDER BY l.request_date DESC
        LIMIT 10)
        
        ORDER BY activity_date DESC
        LIMIT 10";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentActivities[] = $row;
    }
}

// Get upcoming events
$upcomingEvents = getUpcomingEvents(5);

// Get total leaves
$sql = "SELECT COUNT(*) as total_leaves FROM leaves WHERE status = 'pending'";
$result = $conn->query($sql);
$totalLeaves = $result ? $result->fetch_assoc()['total_leaves'] : 0;
?>

<?php include 'includes/header.php'; ?>

        <main class="main-content">
            <section class="quick-stats">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <p><?php 
                        $sql = "SELECT COUNT(*) as total FROM students";
                        $result = $conn->query($sql);
                        echo $result ? $result->fetch_assoc()['total'] : 0;
                    ?></p>
                </div>
                <div class="stat-card">
                    <h3>Upcoming Events</h3>
                    <p><?php
                        $sql = "SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE()";
                        $result = $conn->query($sql);
                        echo $result ? $result->fetch_assoc()['total'] : 0;
                    ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Leaves</h3>
                    <p><?php
                        $sql = "SELECT COUNT(*) as total FROM leaves WHERE status = 'pending'";
                        $result = $conn->query($sql);
                        echo $result ? $result->fetch_assoc()['total'] : 0;
                    ?></p>
                </div>
                <div class="stat-card">
                    <h3>Fees Due</h3>
                    <p>$<?php
                        $sql = "SELECT SUM(amount) as total FROM fees WHERE status = 'pending'";
                        $result = $conn->query($sql);
                        echo number_format($result ? $result->fetch_assoc()['total'] : 0, 2);
                    ?></p>
                </div>
            </section>

            <section class="recent-activity">
                <h2>Recent Activity</h2>
                <?php if (empty($recentActivities)): ?>
                    <p class="text-muted text-center">No recent activities found</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($recentActivities as $activity): 
                            $activityType = ucfirst($activity['type']);
                            $studentName = htmlspecialchars($activity['student_name']);
                            $action = htmlspecialchars($activity['action']); 
                            $entity = htmlspecialchars($activity['entity']);
                            $date = date('M d, Y', strtotime($activity['activity_date']));
                        ?>
                            <li>
                                <span class="activity-type <?php echo $activity['type']; ?>">
                                    <?php echo $activityType; ?>
                                </span>
                                <span class="activity-details">
                                    <?php echo "$studentName - $action - $entity"; ?>
                                </span>
                                <span class="activity-date">
                                    <?php echo $date; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="upcoming-events">
                <h2>Upcoming Events</h2>
                <?php
                // Get upcoming events
                $sql = "SELECT * FROM events 
                        WHERE event_date >= CURDATE()
                        ORDER BY event_date ASC, event_time ASC";
                $result = $conn->query($sql);
                $upcomingEvents = [];
                
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $upcomingEvents[] = $row;
                    }
                }
                
                if (empty($upcomingEvents)): ?>
                    <p class="text-muted text-center">No upcoming events found</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($upcomingEvents as $event): ?>
                            <li>
                                <div class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></div>
                                <div class="event-details">
                                    <span class="event-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                    </span>
                                    <?php if ($event['event_time']): ?>
                                        <span class="event-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('h:i A', strtotime($event['event_time'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($event['venue']): ?>
                                        <span class="event-venue">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($event['venue']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($event['description']): ?>
                                    <div class="event-description">
                                        <?php echo htmlspecialchars($event['description']); ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="text-center mt-3">
                        <a href="events.php" class="btn btn-sm btn-outline-warning">View All Events</a>
                    </div>
                <?php endif; ?>
            </section>

            <section class="attendance-overview">
                <h2>Today's Attendance Overview</h2>
                <?php
                // Get today's date
                $today = date('Y-m-d');
                
                // Fetch today's attendance summary
                $sql = "SELECT 
                        COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
                        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
                        COUNT(CASE WHEN status = 'late' THEN 1 END) as late_count
                        FROM attendance 
                        WHERE date = ?";
                        
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $today);
                $stmt->execute();
                $result = $stmt->get_result();
                $attendanceStats = $result->fetch_assoc();
                ?>
                
                <div class="attendance-stats">
                    <div class="stat-item">
                        <span class="stat-label">Present</span>
                        <span class="stat-value present"><?php echo $attendanceStats['present_count']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Absent</span>
                        <span class="stat-value absent"><?php echo $attendanceStats['absent_count']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Late</span>
                        <span class="stat-value late"><?php echo $attendanceStats['late_count']; ?></span>
                    </div>
                </div>
            </section>
        </main>
         <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Sample chart
        var ctx = document.getElementById('attendanceChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['IT', 'CS', 'Mechanical', 'Electrical', 'Civil'],
                datasets: [{
                    label: 'Attendance Percentage',
                    data: [
                        <?php
                        $sql = "SELECT 
                            department,
                            (COUNT(CASE WHEN status = 'present' THEN 1 END) * 100.0 / COUNT(*)) as attendance_percentage
                        FROM attendance a
                        JOIN students s ON a.student_id = s.student_id 
                        WHERE date = CURDATE()
                        GROUP BY department
                        ORDER BY department";
                        $result = $conn->query($sql);
                        $percentages = [];
                        while($row = $result->fetch_assoc()) {
                            $percentages[] = round($row['attendance_percentage'], 1);
                        }
                        echo implode(', ', $percentages);
                        ?>
                    ],
                    backgroundColor: [
                        'rgba(74, 144, 226, 0.7)',
                        'rgba(245, 166, 35, 0.7)',
                        'rgba(80, 227, 194, 0.7)',
                        'rgba(184, 233, 134, 0.7)',
                        'rgba(248, 102, 185, 0.7)'
                    ],
                    borderColor: [
                        'rgba(74, 144, 226, 1)',
                        'rgba(245, 166, 35, 1)',
                        'rgba(80, 227, 194, 1)',
                        'rgba(184, 233, 134, 1)',
                        'rgba(248, 102, 185, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                responsive: true,
                maintainAspectRatio: true
            }
        });

        // Toggle profile dropdown
        document.querySelector('.profile-section').addEventListener('click', function () {
            document.querySelector('.profile-dropdown').classList.toggle('active');
        });
    </script>

<?php include 'includes/footer.php'; ?>
