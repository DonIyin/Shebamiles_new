<?php
/**
 * Shebamiles - Database Setup Script
 * Run this once to create all necessary tables
 */

$conn = new mysqli('localhost', 'root', '');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS shebamiles_db";
if ($conn->query($sql) === TRUE) {
    echo "✓ Database created successfully<br>";
} else {
    echo "✗ Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db('shebamiles_db');
$conn->set_charset("utf8mb4");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(100),
    role ENUM('admin', 'manager', 'employee') DEFAULT 'employee',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    is_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    password_reset_token VARCHAR(255),
    password_reset_expires DATETIME,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (email),
    INDEX (role),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Users table created successfully<br>";
} else {
    echo "✗ Error creating users table: " . $conn->error . "<br>";
}

// Create user_activity table (for logging)
$sql = "CREATE TABLE IF NOT EXISTS user_activity (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity VARCHAR(50),
    details TEXT,
    ip_address VARCHAR(45),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ User Activity table created successfully<br>";
} else {
    echo "✗ Error creating user_activity table: " . $conn->error . "<br>";
}

// Create login_attempts table (for security)
$sql = "CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255),
    ip_address VARCHAR(45),
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX (email),
    INDEX (ip_address),
    INDEX (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Login Attempts table created successfully<br>";
} else {
    echo "✗ Error creating login_attempts table: " . $conn->error . "<br>";
}

// Create admin user (default)
$admin_email = 'admin@shebamiles.com';
$admin_password = 'Admin@123456';
$admin_hash = password_hash($admin_password, PASSWORD_BCRYPT);

$sql = "INSERT INTO users (email, password, first_name, last_name, role, status, is_verified) 
        VALUES (?, ?, ?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE password=VALUES(password)";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ssssssi', $admin_email, $admin_hash, $first_name, $last_name, $role, $status, $verified);

$first_name = 'Admin';
$last_name = 'User';
$role = 'admin';
$status = 'active';
$verified = 1;

if ($stmt->execute()) {
    echo "✓ Admin user created (Email: {$admin_email}, Password: {$admin_password})<br>";
} else {
    echo "✗ Error creating admin user: " . $conn->error . "<br>";
}

echo "<br><strong>✓ Database setup completed!</strong><br>";
echo "You can now use the login system. Default admin credentials:<br>";
echo "Email: <strong>admin@shebamiles.com</strong><br>";
echo "Password: <strong>Admin@123456</strong><br>";

$conn->close();
?>
