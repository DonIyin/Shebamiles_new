<?php
/**
 * Shebamiles - Create Employees Table
 * This script creates a dedicated employees table and populates it from users
 */

require_once 'config.php';

// Create employees table
$sql = "CREATE TABLE IF NOT EXISTS employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    employee_code VARCHAR(50) UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(100),
    position VARCHAR(100),
    salary DECIMAL(10, 2),
    hire_date DATE,
    manager_id INT,
    status ENUM('active', 'inactive', 'on_leave', 'suspended') DEFAULT 'active',
    bio TEXT,
    profile_picture VARCHAR(255),
    address VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relation VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES employees(employee_id) ON DELETE SET NULL,
    INDEX (user_id),
    INDEX (department),
    INDEX (status),
    INDEX (employee_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Employees table created successfully<br>";
} else {
    echo "✗ Error creating employees table: " . $conn->error . "<br>";
    exit();
}

// Migrate data from users table to employees table
$migrate_sql = "INSERT IGNORE INTO employees (user_id, first_name, last_name, email, phone, department, status, hire_date)
                SELECT user_id, first_name, last_name, email, phone, department, status, created_at 
                FROM users";

if ($conn->query($migrate_sql) === TRUE) {
    $affected = $conn->affected_rows;
    echo "✓ Migrated $affected users to employees table<br>";
} else {
    echo "✗ Error migrating data: " . $conn->error . "<br>";
}

echo "<br><strong>✓ Employees table setup completed!</strong><br>";
echo "You can now use the employees table in your application.<br>";
?>
