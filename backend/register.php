<?php
/**
 * Shebamiles - User Registration Handler
 */

header('Content-Type: application/json');

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
$phone = isset($input['phone']) ? sanitize($input['phone']) : '';
$department = isset($input['department']) ? sanitize($input['department']) : '';

// Validation
$errors = [];

if (empty($email) || !isValidEmail($email)) {
    $errors[] = 'Please enter a valid email address';
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
if (!preg_match('/[!@#$%^&*]/', $password)) {
    $errors[] = 'Password must contain at least one special character (!@#$%^&*)';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// Check if email already exists
$query = "SELECT user_id FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit();
}

// Hash password
$password_hash = hashPassword($password);

// Create verification token
$verification_token = bin2hex(random_bytes(32));

// Insert user
$query = "INSERT INTO users (email, password, first_name, last_name, phone, department, verification_token, role, status) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$role = 'employee';
$status = 'active';

$stmt->bind_param('sssssssss', $email, $password_hash, $first_name, $last_name, $phone, $department, $verification_token, $role, $status);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;
    
    // Log the activity
    logActivity($user_id, 'SIGNUP', 'User registered successfully');
    
    // Send verification email (optional)
    $verification_link = BASE_URL . 'backend/verify.php?token=' . $verification_token;
    $subject = 'Verify Your Shebamiles Account';
    $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Welcome to Shebamiles!</h2>
            <p>Hi {$first_name},</p>
            <p>Thank you for registering with Shebamiles Employment Management System.</p>
            <p>Please verify your email address by clicking the link below:</p>
            <p><a href='{$verification_link}' style='background-color: #f97316; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
            <p>Or copy this link: {$verification_link}</p>
            <p>This link will expire in 24 hours.</p>
            <p>Best regards,<br>Shebamiles Team</p>
        </body>
        </html>
    ";
    
    // Send email (optional - comment out if email not configured)
    // sendEmail($email, $subject, $message);
    
    // Auto-login after registration (optional)
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['name'] = $first_name . ' ' . $last_name;
    $_SESSION['role'] = $role;
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! You are now logged in.',
        'redirect' => BASE_URL . 'employee_personalized_dashboard.html'
    ]);
    
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $conn->error]);
}

$stmt->close();
?>
