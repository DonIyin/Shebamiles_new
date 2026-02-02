<?php
/**
 * Shebamiles - Configuration File
 * Database and authentication settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP password is empty
define('DB_NAME', 'shebamiles_db');

// Session Configuration
define('SESSION_TIMEOUT', 3600);  // 1 hour
define('SESSION_NAME', 'shebamiles_session');

// Security Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15);  // minutes

// Base URL
define('BASE_URL', 'http://localhost/Shebamiles_new/');

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
        header('Location: ' . BASE_URL . 'index.html');
        exit();
    }
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
?>
