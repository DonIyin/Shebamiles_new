<?php
require_once 'backend/config.php';

$query = "UPDATE users SET username = 'admin' WHERE role = 'admin'";
$result = $conn->query($query);

if ($result) {
    echo "✓ Admin username updated successfully to 'admin'\n";
} else {
    echo "Error updating username: " . $conn->error . "\n";
}

// Verify the change
$verify = $conn->query("SELECT username FROM users WHERE role = 'admin'");
$row = $verify->fetch_assoc();
echo "Current admin username: " . $row['username'] . "\n";

$conn->close();
?>
