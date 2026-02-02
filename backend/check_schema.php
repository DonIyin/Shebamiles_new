<?php
$conn = new mysqli('localhost', 'root', '', 'shebamiles_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check users table structure
$result = $conn->query("DESCRIBE users");

if ($result) {
    echo "Users table columns:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
