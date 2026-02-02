<?php
require_once 'backend/config.php';

// Test data
$test_data = [
    'email' => 'test@example.com',
    'username' => 'testuser',
    'password' => 'Test@123456',
    'confirm_password' => 'Test@123456',
    'first_name' => 'Test',
    'last_name' => 'User',
    'phone' => '1234567890',
    'department' => 'IT'
];

echo "Testing registration with data:\n";
print_r($test_data);
echo "\n\n";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [];

// Capture output
ob_start();
$_SERVER['REQUEST_METHOD'] = 'POST';

// Create a temporary file with JSON data
$json_data = json_encode($test_data);
$temp_file = tmpfile();
fwrite($temp_file, $json_data);
rewind($temp_file);

// Mock php://input
stream_wrapper_unregister("php");
stream_wrapper_register("php", "MockPHPInputStream");
MockPHPInputStream::$data = $json_data;

// Include the register script
include 'backend/register.php';

$output = ob_get_clean();
fclose($temp_file);

echo "Registration response:\n";
echo $output;
echo "\n\n";

// Check if user was added
$query = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $test_data['email']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✓ User was successfully added to database!\n";
    $user = $result->fetch_assoc();
    echo "User ID: " . $user['user_id'] . "\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    
    // Clean up test user
    $delete_query = "DELETE FROM users WHERE email = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('s', $test_data['email']);
    $delete_stmt->execute();
    echo "\nTest user removed from database.\n";
} else {
    echo "✗ User was NOT added to database!\n";
}

class MockPHPInputStream {
    public static $data = '';
    private $position = 0;

    function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }

    function stream_read($count) {
        $ret = substr(self::$data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    function stream_eof() {
        return $this->position >= strlen(self::$data);
    }

    function stream_stat() {
        return [];
    }
}
?>
