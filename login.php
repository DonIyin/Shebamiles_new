<?php
/**
 * Shebamiles - Login Handler
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

if (!$input) {
    $input = $_POST;
}

$email = isset($input['email']) ? sanitize($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$remember = isset($input['remember']) ? (bool)$input['remember'] : false;

// Validation
if (empty($email) || !isValidEmail($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email']);
    exit();
}

if (empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter your password']);
    exit();
}

$ip_address = $_SERVER['REMOTE_ADDR'];

// Check login attempts (brute force protection)
$query = "SELECT COUNT(*) as attempts FROM login_attempts 
          WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
$stmt = $conn->prepare($query);
$lockout_minutes = LOCKOUT_TIME;
$stmt->bind_param('si', $email, $lockout_minutes);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['attempts'] >= MAX_LOGIN_ATTEMPTS) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many login attempts. Please try again later.']);
    
    // Log failed attempt
    $query = "INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 0)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $email, $ip_address);
    $stmt->execute();
    
    exit();
}

// Find user
$query = "SELECT user_id, email, password, first_name, last_name, role, status FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Log failed attempt
    $query = "INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 0)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $email, $ip_address);
    $stmt->execute();
    
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit();
}

$user = $result->fetch_assoc();

// Check user status
if ($user['status'] !== 'active') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your account is ' . $user['status']]);
    exit();
}

// Verify password
if (!verifyPassword($password, $user['password'])) {
    // Log failed attempt
    $query = "INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 0)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $email, $ip_address);
    $stmt->execute();
    
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit();
}

// Login successful - set session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['email'] = $user['email'];
$_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['role'] = $user['role'];

// Set remember me cookie (optional)
if ($remember) {
    $cookie_token = bin2hex(random_bytes(32));
    setcookie('remember_token', $cookie_token, time() + (86400 * 30), '/');
    
    // Store token in database (optional)
}

// Update last login
$query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user['user_id']);
$stmt->execute();

// Clear failed login attempts
$query = "DELETE FROM login_attempts WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();

// Log successful login
logActivity($user['user_id'], 'LOGIN', 'Successful login from ' . $ip_address);

// Determine redirect based on role
$redirect = BASE_URL . 'employee_personalized_dashboard.html';
if ($user['role'] === 'admin') {
    $redirect = BASE_URL . 'admin_dashboard_overview_orange.html';
} elseif ($user['role'] === 'manager') {
    $redirect = BASE_URL . 'employee_directory_management.html';
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Login successful!',
    'user' => [
        'id' => $user['user_id'],
        'email' => $user['email'],
        'name' => $user['first_name'] . ' ' . $user['last_name'],
        'role' => $user['role']
    ],
    'redirect' => $redirect
]);

$stmt->close();
?>
