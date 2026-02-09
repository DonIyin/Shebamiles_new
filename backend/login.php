<?php
/**
 * Shebamiles - Enhanced Login Handler
 * Implements security best practices:
 * - Rate limiting for brute force protection
 * - Secure password hashing
 * - Session security
 * - Activity logging
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

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new ApiException('Method not allowed', ApiResponse::ERROR, 405);
    }
    
    // Get input (JSON or form data)
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // Get credentials
    $username = sanitize($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $remember = (bool)($input['remember'] ?? false);
    
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // ============================================
    // VALIDATION
    // ============================================
    
    $validator = new RequestValidator($input);
    $validator->required('username')->minLength(3);
    $validator->required('password')->minLength(8);
    
    if (!$validator->validate()) {
        throw new ValidationException('Please fill in all required fields', $validator->errors());
    }
    
    // ============================================
    // RATE LIMITING
    // ============================================
    
    // Check login attempt rate limits
    if (!RateLimiter::check($ip_address, 'login_attempts', LOGIN_RATE_LIMIT, LOGIN_RATE_LIMIT_WINDOW)) {
        Logger::security('Login rate limit exceeded', [
            'ip' => $ip_address,
            'username' => $username
        ]);
        throw new RateLimitException('Too many login attempts. Please try again in 15 minutes.');
    }
    
    // ============================================
    // USER LOOKUP
    // ============================================
    
    // Query database for user
    $query = "SELECT user_id, email, password, first_name, last_name, role, status, is_verified 
             FROM users 
             WHERE username = ? OR email = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new DatabaseException('Query failed', $conn->error);
    }
    
    $stmt->bind_param('ss', $username, $username);
    if (!$stmt->execute()) {
        throw new DatabaseException('Login query failed', $stmt->error);
    }
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // User not found
    if (!$user) {
        Logger::warning('Login attempt with non-existent user', [
            'username' => $username,
            'ip' => $ip_address
        ]);
        
        // Don't reveal whether account exists
        throw new UnauthorizedException('Invalid username or password');
    }
    
    // ============================================
    // ACCOUNT STATUS CHECKS
    // ============================================
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        Logger::warning('Login attempt on ' . $user['status'] . ' account', [
            'user_id' => $user['user_id'],
            'ip' => $ip_address
        ]);
        
        throw new ForbiddenException('Your account is ' . $user['status'] . '. Please contact support.');
    }
    
    // Email verification disabled for demo project
    
    // ============================================
    // PASSWORD VERIFICATION
    // ============================================
    
    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        Logger::warning('Failed login attempt', [
            'user_id' => $user['user_id'],
            'ip' => $ip_address,
            'reason' => 'invalid_password'
        ]);
        
        throw new UnauthorizedException('Invalid username or password');
    }
    
    // ============================================
    // SESSION SETUP
    // ============================================
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // ============================================
    // LAST LOGIN UPDATE
    // ============================================
    
    // Update last login timestamp
    $query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('i', $user['user_id']);
        $stmt->execute();
        $stmt->close();
    }
    
    // ============================================
    // ACTIVITY LOGGING
    // ============================================
    
    // Log successful login
    logActivity($user['user_id'], 'LOGIN_SUCCESS', $ip_address);
    Logger::info('User logged in successfully', [
        'user_id' => $user['user_id'],
        'email' => $user['email'],
        'ip' => $ip_address
    ]);
    
    // ============================================
    // RESET RATE LIMITING
    // ============================================
    
    // Reset rate limiter for this IP after successful login
    RateLimiter::reset('login_attempts', $ip_address);
    
    // ============================================
    // DETERMINE REDIRECT
    // ============================================
    
    // Redirect based on role (using relative paths from frontend folder)
    $redirect = 'employee_personalized_dashboard_1.html';
    
    if ($user['role'] === 'admin') {
        $redirect = 'admin_dashboard_overview.html';
    } elseif ($user['role'] === 'manager') {
        $redirect = 'employee_list.html';
    }
    
    // ============================================
    // RESPONSE
    // ============================================
    
    ApiResponse::success('Login successful! Redirecting...', [
        'user' => [
            'id' => $user['user_id'],
            'email' => $user['email'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'role' => $user['role']
        ],
        'redirect' => $redirect,
        'csrf_token' => CsrfProtection::getToken()
    ]);

} catch (Exception $e) {
    if ($e instanceof ApiException) {
        $e->send();
    } else {
        Logger::critical('Login error', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        ApiResponse::serverError('An error occurred during login');
    }
}
?>
