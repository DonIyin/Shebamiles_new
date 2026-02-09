<?php
/**
 * Shebamiles - User Registration Handler
 */

// Ensure JSON output no matter what happens
header('Content-Type: application/json; charset=utf-8');

// Set error handler to ensure JSON output on fatal errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
});

// Set exception handler to ensure JSON output
set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $exception->getMessage()
    ]);
    exit;
});

require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!$input) {
    $input = $_POST;
}

$email = isset($input['email']) ? sanitize($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$confirm_password = isset($input['confirm_password']) ? $input['confirm_password'] : '';
$first_name = isset($input['first_name']) ? sanitize($input['first_name']) : '';
$last_name = isset($input['last_name']) ? sanitize($input['last_name']) : '';
$username = isset($input['username']) ? sanitize($input['username']) : '';
$phone = isset($input['phone']) ? sanitize($input['phone']) : '';
$department = isset($input['department']) ? sanitize($input['department']) : '';

// Validation
$errors = [];

if (empty($email) || !isValidEmail($email)) {
    $errors[] = 'Please enter a valid email address';
}

if (empty($username) || strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters long';
}

if (empty($first_name) || strlen($first_name) < 2) {
    $errors[] = 'Please enter a valid first name';
}

if (empty($last_name) || strlen($last_name) < 2) {
    $errors[] = 'Please enter a valid last name';
}

if (empty($password) || strlen($password) < PASSWORD_MIN_LENGTH) {
    $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

// Check password strength
if (!preg_match('/[A-Z]/', $password)) {
    $errors[] = 'Password must contain at least one uppercase letter';
}
if (!preg_match('/[a-z]/', $password)) {
    $errors[] = 'Password must contain at least one lowercase letter';
}
if (!preg_match('/[0-9]/', $password)) {
    $errors[] = 'Password must contain at least one number';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// Check if email already exists
$query = "SELECT user_id FROM users WHERE email = ? OR username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $email, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email or username already registered']);
    exit();
}

// Hash password
$password_hash = hashPassword($password);

// Insert user (auto-verified for demo)
$query = "INSERT INTO users (email, username, password, first_name, last_name, phone, department, role, status, is_verified) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$role = 'employee';
$status = 'active';

$stmt->bind_param('sssssssss', $email, $username, $password_hash, $first_name, $last_name, $phone, $department, $role, $status);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;
    
    // Log the activity
    logActivity($user_id, 'SIGNUP', 'User registered successfully');
    
    // Email verification disabled for demo project
    
    // Auto-login after registration (optional)
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['username'] = $username;
    $_SESSION['name'] = $first_name . ' ' . $last_name;
    $_SESSION['role'] = $role;
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Please log in with your credentials.',
        'user' => [
            'id' => $user_id,
            'email' => $email,
            'username' => $username,
            'name' => $first_name . ' ' . $last_name,
            'role' => $role
        ]
    ]);
    
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $conn->error]);
}

$stmt->close();
?>
