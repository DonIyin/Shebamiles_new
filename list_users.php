<?php
require_once 'backend/config.php';

$query = "SELECT user_id, email, username, first_name, last_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 10";
$result = $conn->query($query);

echo "Recent users in database:\n";
echo str_repeat("=", 80) . "\n";
printf("%-5s %-25s %-15s %-20s %-10s %-20s\n", "ID", "Email", "Username", "Name", "Role", "Created");
echo str_repeat("-", 80) . "\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        printf("%-5s %-25s %-15s %-20s %-10s %-20s\n", 
            $row['user_id'],
            $row['email'],
            $row['username'] ?? 'N/A',
            $row['first_name'] . ' ' . $row['last_name'],
            $row['role'],
            $row['created_at']
        );
    }
} else {
    echo "No users found in database.\n";
}

echo str_repeat("=", 80) . "\n";
echo "Total users: " . $result->num_rows . "\n";

$conn->close();
?>
