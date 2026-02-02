<?php
require_once 'backend/config.php';

$query = "SELECT user_id, email, username, first_name, last_name FROM users WHERE role = 'admin'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Admin User Details:\n";
    echo "ID: " . $row['user_id'] . "\n";
    echo "Email: " . $row['email'] . "\n";
    echo "Username: " . $row['username'] . "\n";
    echo "Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
} else {
    echo "No admin user found\n";
}

$conn->close();
?>
