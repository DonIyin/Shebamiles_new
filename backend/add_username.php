<?php
/**
 * Add username column to users table
 */

$conn = new mysqli('localhost', 'root', '', 'shebamiles_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'username'");
if ($result->num_rows > 0) {
    echo "✓ Username column already exists<br>";
} else {
    $sql = "ALTER TABLE users ADD COLUMN username VARCHAR(100) UNIQUE AFTER email";
    if ($conn->query($sql)) {
        echo "✓ Username column added successfully<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

// Set existing usernames
$conn->query("UPDATE users SET username = CONCAT(LOWER(SUBSTRING(first_name, 1, 1)), LOWER(last_name)) WHERE username IS NULL OR username = ''");

$conn->close();
?>
