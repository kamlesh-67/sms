<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f5a623;
            --background-color: #f8f9fa;
            --text-color: #333333;
            --sidebar-color: #2c3e50;
            --card-color: #ffffff;
            --hover-color: #3a7bd5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        header {
            background-color: var(--card-color);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            z-index: 1000;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .search-bar {
            flex-grow: 1;
            margin: 0 2rem;
        }

        .search-bar input {
            width: 100%;
            padding: 0.5rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 50px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .profile-section {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .profile-section i {
            font-size: 1.5rem;
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .profile-dropdown {
            position: absolute;
            right: 2rem;
            top: 100%;
            background-color: var(--card-color);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: none;
            overflow: hidden;
        }

        .profile-dropdown.active {
            display: block;
        }

        .profile-dropdown ul {
            list-style-type: none;
        }

        .profile-dropdown li a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--text-color);
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .profile-dropdown li a:hover {
            background-color: #f0f0f0;
        }

        .profile-dropdown i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }

        .sidebar {
            width: 250px;
            background-color: var(--sidebar-color);
            color: #ecf0f1;
            padding: 2rem 1rem;
            padding-top: 5rem;
        }

        .sidebar ul {
            list-style-type: none;
        }

        .sidebar li {
            margin-bottom: 0.5rem;
        }

        .sidebar a {
            color: #ecf0f1;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .main-content {
            flex-grow: 1;
            padding: 2rem;
            padding-top: 5rem;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding-top: 1rem;
            }

            .main-content {
                padding-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">SMS Dashboard</div>
        <div class="search-bar">
            <input type="text" placeholder="Search...">
        </div>
        <div class="profile-section">
            <i class="fas fa-user-circle"></i>
            <span>Admin</span>
            <i class="fas fa-caret-down"></i>
            <div class="profile-dropdown">
                <ul>
                    <li><a href="setting.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="studentInfo.php"><i class="fas fa-user-graduate"></i> Student Information</a></li>
                <li><a href="admissionDetail.php"><i class="fas fa-user-plus"></i> Admission Details</a></li>
                <li><a href="courseTracker.php"><i class="fas fa-book"></i> Course Tracker</a></li>
                <li><a href="leaveTracker.php"><i class="fas fa-calendar-minus"></i> Leave Tracker</a></li>
                <li><a href="feeTracker.php"><i class="fas fa-money-bill-wave"></i> Fee Tracker</a></li>
                <li><a href="collegeEvent.php"><i class="fas fa-calendar-alt"></i> College Events</a></li>
                <li><a href="attendance.php"><i class="fas fa-clipboard-check"></i> Attendance</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
        </nav>