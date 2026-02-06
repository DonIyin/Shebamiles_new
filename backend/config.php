<?php
/**
 * Shebamiles - Configuration File
 * Database and authentication settings
 */

// Database Configuration
define('DB_HOST', getenv('SHEBAMILES_DB_HOST') ?: 'localhost');
define('DB_USER', getenv('SHEBAMILES_DB_USER') ?: 'root');
define('DB_PASS', getenv('SHEBAMILES_DB_PASS') ?: '');  // Default XAMPP password is empty
define('DB_NAME', getenv('SHEBAMILES_DB_NAME') ?: 'shebamiles_db');

// Session Configuration
define('SESSION_TIMEOUT', 3600);  // 1 hour
define('SESSION_NAME', 'shebamiles_session');

// Security Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15);  // minutes
define('API_RATE_LIMIT', 100);  // requests per minute
define('API_RATE_LIMIT_WINDOW', 60);  // seconds

// Base URL
define('BASE_URL', getenv('SHEBAMILES_BASE_URL') ?: 'http://localhost/Shebamiles_new/');

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Set charset to UTF8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Connection Error: " . $e->getMessage());
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    $secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_name(SESSION_NAME);
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['email']);
}

// Function to get current user
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            'name' => $_SESSION['name'] ?? '',
            'role' => $_SESSION['role'] ?? 'employee'
        ];
    }
    return null;
}

// Function to redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'frontend/index.html');
        exit();
    }
}

// Function to check user role
function requireRole($role) {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden - admin access required']);
        exit();
    }
}

// Function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Function to validate CSRF token
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Function to sanitize input
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(trim($input));
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Function to log user activity
function logActivity($user_id, $activity, $details = '') {
    global $conn;
    $timestamp = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $query = "INSERT INTO user_activity (user_id, activity, details, ip_address, timestamp) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('issss', $user_id, $activity, $details, $ip_address, $timestamp);
    $stmt->execute();
}

// Function to send email (for password reset, verification, etc.)
function sendEmail($to, $subject, $message) {
    // Configure SMTP settings or use mail()
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@shebamiles.com" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Function to get employee info
function getEmployeeInfo($user_id) {
    global $conn;
    $query = "SELECT * FROM employees WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to get all employees
function getAllEmployees($limit = 50, $offset = 0) {
    global $conn;
    $query = "SELECT e.*, u.username, u.role FROM employees e 
              LEFT JOIN users u ON e.user_id = u.user_id 
              ORDER BY e.first_name ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get employees by department
function getEmployeesByDepartment($department, $limit = 50, $offset = 0) {
    global $conn;
    $query = "SELECT e.*, u.username, u.role FROM employees e 
              LEFT JOIN users u ON e.user_id = u.user_id 
              WHERE e.department = ? 
              ORDER BY e.first_name ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sii', $department, $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get total employee count
function getTotalEmployeeCount() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM employees";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}
?>
