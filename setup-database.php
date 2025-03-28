<?php
// Database Setup Script for MySQL 8
// This script imports the schema and creates the database

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database credentials - update these to match your dbms.php file
$host = 'localhost';
$username = 'root';
$password = ''; // Update with your actual MySQL password
$database = 'college_management';

echo "<h1>Student Management System - Database Setup</h1>";
echo "<pre>";

// Step 1: Connect to MySQL server
echo "Connecting to MySQL server... ";
try {
    $conn = new mysqli($host, $username, $password);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "SUCCESS\n";
} catch (Exception $e) {
    echo "FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your MySQL credentials and make sure MySQL is running.\n";
    exit(1);
}

// Step 2: Create database if not exists
echo "Creating database if not exists... ";
$sql = "CREATE DATABASE IF NOT EXISTS `$database`";
if ($conn->query($sql) === TRUE) {
    echo "SUCCESS\n";
} else {
    echo "FAILED\n";
    echo "Error creating database: " . $conn->error . "\n";
    exit(1);
}

// Step 3: Select the database
echo "Selecting database... ";
if ($conn->select_db($database)) {
    echo "SUCCESS\n";
} else {
    echo "FAILED\n";
    echo "Error selecting database: " . $conn->error . "\n";
    exit(1);
}

// Step 4: Import schema from schema.sql
echo "Importing schema from schema.sql... ";
try {
    // Read schema file
    $schemaFile = 'schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file $schemaFile not found.");
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Split SQL by semicolons
    $queries = explode(';', $sql);
    
    // Execute each query
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        if ($conn->query($query) === FALSE) {
            throw new Exception("Error executing query: " . $conn->error . "\nQuery: " . $query);
        }
    }
    echo "SUCCESS\n";
} catch (Exception $e) {
    echo "FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 5: Check if default admin user exists
echo "Checking admin user... ";
$sql = "SELECT COUNT(*) as count FROM users WHERE username = 'admin'";
$result = $conn->query($sql);
if ($result === FALSE) {
    echo "FAILED\n";
    echo "Error checking admin user: " . $conn->error . "\n";
} else {
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        echo "EXISTS\n";
    } else {
        echo "CREATING... ";
        // Create default admin user
        $sql = "INSERT INTO users (username, password, email, role, full_name, status)
                VALUES ('admin', MD5('admin123'), 'admin@example.com', 'admin', 'System Administrator', 'active')";
        if ($conn->query($sql) === TRUE) {
            echo "SUCCESS\n";
        } else {
            echo "FAILED\n";
            echo "Error creating admin user: " . $conn->error . "\n";
        }
    }
}

// Close connection
$conn->close();

echo "\nDatabase setup complete!\n";
echo "You can now access the Student Management System at: <a href='index.php'>Go to Application</a>\n";
echo "</pre>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        line-height: 1.6;
    }
    h1 {
        color: #4a5568;
    }
    pre {
        background-color: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
        overflow: auto;
    }
    a {
        display: inline-block;
        margin-top: 20px;
        background-color: #3182ce;
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 5px;
    }
    a:hover {
        background-color: #2c5282;
    }
</style> 