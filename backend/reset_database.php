<?php
$dbHost = getenv('SHEBAMILES_DB_HOST') ?: 'localhost';
$dbUser = getenv('SHEBAMILES_DB_USER') ?: 'root';
$dbPass = getenv('SHEBAMILES_DB_PASS') ?: '';
$dbName = getenv('SHEBAMILES_DB_NAME') ?: 'shebamiles_db';

$conn = new mysqli($dbHost, $dbUser, $dbPass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop and recreate database
$conn->query("DROP DATABASE IF EXISTS {$dbName}");
$conn->query("CREATE DATABASE {$dbName}");
$conn->select_db($dbName);
$conn->set_charset("utf8mb4");

echo "✓ Database reset successfully!<br>";

// Set foreign key checks to 0 temporarily
$conn->query("SET FOREIGN_KEY_CHECKS=0");

// Create users table
$sql = "CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
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
    INDEX (username),
    INDEX (role),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "✓ Users table created<br>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

// Create user_activity table
$sql = "CREATE TABLE user_activity (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity VARCHAR(50),
    details TEXT,
    ip_address VARCHAR(45),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "✓ User Activity table created<br>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

// Create login_attempts table
$sql = "CREATE TABLE login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255),
    ip_address VARCHAR(45),
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX (email),
    INDEX (ip_address),
    INDEX (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "✓ Login Attempts table created<br>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=1");

// Create admin user
$admin_email = 'admin@shebamiles.com';
$admin_password = password_hash('Admin@123456', PASSWORD_BCRYPT);
$admin_username = 'admin';
$admin_sql = $conn->prepare("INSERT INTO users (email, username, password, first_name, last_name, role, status, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$admin_sql->bind_param('sssssssi', $admin_email, $admin_username, $admin_password, $first_name, $last_name, $role, $status, $verified);
$first_name = 'Admin';
$last_name = 'User';
$role = 'admin';
$status = 'active';
$verified = 1;

if ($admin_sql->execute()) {
    echo "✓ Admin user created<br>";
    echo "Email: admin@shebamiles.com<br>";
    echo "Password: Admin@123456<br>";
} else {
    echo "✗ Error creating admin: " . $admin_sql->error . "<br>";
}

echo "<br>✓ Database setup complete!<br>";

$conn->close();
?>
