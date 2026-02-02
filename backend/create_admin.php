<?php
/**
 * Shebamiles - Create Default Admin User
 * Run this after database setup to create the default admin account
 */

require_once 'config.php';

$admin_email = 'admin@shebamiles.com';
$admin_password = 'Admin@123456';
$first_name = 'Admin';
$last_name = 'User';
$role = 'admin';
$status = 'active';
$verified = 1;

$admin_hash = hashPassword($admin_password);

$query = "INSERT INTO users (email, password, first_name, last_name, role, status, is_verified) 
          VALUES (?, ?, ?, ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE password = VALUES(password)";

$stmt = $conn->prepare($query);

if (!$stmt) {
    echo "✗ Error preparing statement: " . $conn->error . "<br>";
    exit();
}

$stmt->bind_param('ssssssi', $admin_email, $admin_hash, $first_name, $last_name, $role, $status, $verified);

if ($stmt->execute()) {
    echo "✓ Admin user created successfully!<br>";
    echo "Email: <strong>admin@shebamiles.com</strong><br>";
    echo "Password: <strong>Admin@123456</strong><br>";
} else {
    echo "✗ Error creating admin user: " . $stmt->error . "<br>";
}

$stmt->close();
$conn->close();
?>
