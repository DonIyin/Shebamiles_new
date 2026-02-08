<?php
/**
 * Shebamiles - Enhanced Configuration File
 * Database, security, and authentication settings
 * 
 * Includes all foundation classes and initializes security middleware
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors on screen
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php-errors.log');

// ============================================
// ENVIRONMENT & DEBUG MODE
// ============================================

define('DEBUG_MODE', $_ENV['DEBUG'] ?? false);
define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? 'development');

// ============================================
// DATABASE CONFIGURATION
// ============================================

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'shebamiles_db');
define('DB_PORT', $_ENV['DB_PORT'] ?? 3306);

// ============================================
// SESSION CONFIGURATION
// ============================================

define('SESSION_TIMEOUT', 3600);  // 1 hour
define('SESSION_NAME', 'shebamiles_session');
define('SESSION_SECURE_COOKIE', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
define('SESSION_HTTP_ONLY', true);
define('SESSION_SAME_SITE', 'Strict');

// ============================================
// SECURITY CONFIGURATION
// ============================================

define('PASSWORD_MIN_LENGTH', 10);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15);  // minutes

define('MAX_PASSWORD_REUSE', 5);  // Number of old passwords to remember

define('API_RATE_LIMIT', 100);  // requests per minute
define('API_RATE_LIMIT_WINDOW', 3600);  // 1 hour

define('LOGIN_RATE_LIMIT', 5);
define('LOGIN_RATE_LIMIT_WINDOW', 900);  // 15 minutes

// ============================================
// APPLICATION CONFIGURATION
// ============================================

define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost/Shebamiles_new/');
define('APP_NAME', 'Shebamiles');
define('APP_VERSION', '2.0.0');

// ============================================
// LOAD FOUNDATION CLASSES
// ============================================

$classesDir = __DIR__ . '/classes/';
$middlewareDir = __DIR__ . '/middleware/';

require_once $classesDir . 'ApiResponse.php';
require_once $classesDir . 'ApiException.php';
require_once $classesDir . 'Logger.php';
require_once $classesDir . 'RequestValidator.php';
require_once $middlewareDir . 'SecurityHeaders.php';
require_once $middlewareDir . 'CsrfProtection.php';
require_once $middlewareDir . 'RateLimiter.php';

// ============================================
// INITIALIZE SECURITY HEADERS
// ============================================

SecurityHeaders::setHeaders();

// ============================================
// DATABASE CONNECTION
// ============================================

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Set charset to UTF8
    $conn->set_charset("utf8mb4");
    
    // Enable strict mode for better error reporting
    $conn->query("SET SESSION sql_mode='STRICT_TRANS_TABLES'");
    
} catch (Exception $e) {
    Logger::critical('Database connection failed', ['error' => $e->getMessage()]);
    ApiResponse::serverError('Database connection failed');
}

// ============================================
// SESSION INITIALIZATION
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    // Configure session parameters for security
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => SESSION_SECURE_COOKIE,
        'httponly' => SESSION_HTTP_ONLY,
        'samesite' => SESSION_SAME_SITE
    ]);
    
    session_name(SESSION_NAME);
    session_start();
    
    // Regenerate session ID for security
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

// Generate CSRF token on session start
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = CsrfProtection::generateToken();
}

// ============================================
// GLOBAL ERROR HANDLER
// ============================================

set_exception_handler(function ($e) {
    Logger::critical('Uncaught exception', [
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    if ($e instanceof ApiException) {
        $e->send();
    } else {
        ApiResponse::serverError('An unexpected error occurred');
    }
});

// ============================================
// HELPER FUNCTIONS - AUTHENTICATION
// ============================================

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['email']);
}

/**
 * Get current user info
 */
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

/**
 * Require authentication
 */
function requireLogin() {
    if (!isLoggedIn()) {
        throw new UnauthorizedException('You must be logged in to access this resource');
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    
    if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
        throw new ForbiddenException('You do not have permission to access this resource');
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireRole('admin');
}

// ============================================
// HELPER FUNCTIONS - PASSWORD SECURITY
// ============================================

/**
 * Hash password with BCRYPT
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check password strength
 */
function checkPasswordStrength($password) {
    $strength = 0;
    
    // Length check
    if (strlen($password) >= PASSWORD_MIN_LENGTH) $strength++;
    if (strlen($password) >= 12) $strength++;
    
    // Complexity checks
    if (PASSWORD_REQUIRE_UPPERCASE && preg_match('/[A-Z]/', $password)) $strength++;
    if (PASSWORD_REQUIRE_LOWERCASE && preg_match('/[a-z]/', $password)) $strength++;
    if (PASSWORD_REQUIRE_NUMBERS && preg_match('/\d/', $password)) $strength++;
    if (PASSWORD_REQUIRE_SPECIAL && preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|~`]/', $password)) $strength++;
    
    return $strength;
}

/**
 * Validate password against requirements
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
    }
    
    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain an uppercase letter";
    }
    
    if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain a lowercase letter";
    }
    
    if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/\d/', $password)) {
        $errors[] = "Password must contain a number";
    }
    
    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|~`]/', $password)) {
        $errors[] = "Password must contain a special character (!@#$%^&*...)";
    }
    
    return $errors;
}

// ============================================
// HELPER FUNCTIONS - INPUT VALIDATION
// ============================================

/**
 * Sanitize input (trim whitespace)
 */
function sanitize($input) {
    if (is_string($input)) {
        return trim($input);
    }
    return $input;
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ============================================
// HELPER FUNCTIONS - LOGGING & ACTIVITY
// ============================================

/**
 * Log user activity
 */
function logActivity($user_id, $activity, $details = '') {
    try {
        Logger::info('User activity', [
            'user_id' => $user_id,
            'activity' => $activity,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        global $conn;
        if ($conn) {
            $query = "INSERT INTO user_activity (user_id, activity, details, ip_address, timestamp) 
                     VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $stmt->bind_param('isss', $user_id, $activity, $details, $ip_address);
                $stmt->execute();
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        Logger::error('Failed to log activity', ['error' => $e->getMessage()]);
    }
}

// ============================================
// HELPER FUNCTIONS - DATA RETRIEVAL
// ============================================

/**
 * Get employee info
 */
function getEmployeeInfo($user_id) {
    global $conn;
    $query = "SELECT * FROM employees WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) throw new DatabaseException('Query prepare failed');
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    $stmt->close();
    
    return $employee;
}

/**
 * Get all employees with pagination
 */
function getAllEmployees($limit = 50, $offset = 0) {
    global $conn;
    $query = "SELECT e.*, u.email, u.role FROM employees e 
              INNER JOIN users u ON e.user_id = u.user_id 
              WHERE e.status = 'active'
              ORDER BY e.first_name ASC 
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) throw new DatabaseException('Query prepare failed');
    
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $employees = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $employees;
}

/**
 * Get employees by department
 */
function getEmployeesByDepartment($department, $limit = 50, $offset = 0) {
    global $conn;
    $query = "SELECT e.*, u.email, u.role FROM employees e 
              INNER JOIN users u ON e.user_id = u.user_id 
              WHERE e.department = ? AND e.status = 'active'
              ORDER BY e.first_name ASC 
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) throw new DatabaseException('Query prepare failed');
    
    $stmt->bind_param('sii', $department, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $employees = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $employees;
}

/**
 * Get total employee count
 */
function getTotalEmployeeCount() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM employees WHERE status = 'active'";
    $result = $conn->query($query);
    if (!$result) throw new DatabaseException('Query failed');
    
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

?>
