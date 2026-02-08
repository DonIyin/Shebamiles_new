<?php
/**
 * Shebamiles - CSRF Protection Middleware
 * Prevents Cross-Site Request Forgery attacks
 * 
 * Usage:
 *   CsrfProtection::generateToken(); // Call once per session
 *   CsrfProtection::validateToken($_POST['csrf_token']); // Validate on form submission
 */

class CsrfProtection {
    
    const TOKEN_LENGTH = 32;
    const SESSION_KEY = 'csrf_token';
    const TOKEN_FIELD = 'csrf_token';
    
    /**
     * Generate or retrieve CSRF token for session
     */
    public static function generateToken() {
        // Check if token already exists in session
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }
        
        return $_SESSION[self::SESSION_KEY];
    }
    
    /**
     * Get current CSRF token
     */
    public static function getToken() {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }
    
    /**
     * Validate CSRF token from request
     * 
     * @param string $token Token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateToken($token = null) {
        // Get token from parameter or request
        if ($token === null) {
            $token = self::getTokenFromRequest();
        }
        
        // Check if token exists
        if (!$token || !isset($_SESSION[self::SESSION_KEY])) {
            Logger::security('CSRF validation failed - missing token', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
            return false;
        }
        
        // Compare tokens using hash_equals to prevent timing attacks
        $valid = hash_equals($_SESSION[self::SESSION_KEY], $token);
        
        if (!$valid) {
            Logger::security('CSRF validation failed - token mismatch', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
        }
        
        return $valid;
    }
    
    /**
     * Extract token from request (POST, header, or cookie)
     */
    private static function getTokenFromRequest() {
        // Check POST data first
        if (isset($_POST[self::TOKEN_FIELD])) {
            return $_POST[self::TOKEN_FIELD];
        }
        
        // Check JSON body
        $json = json_decode(file_get_contents('php://input'), true);
        if (isset($json[self::TOKEN_FIELD])) {
            return $json[self::TOKEN_FIELD];
        }
        
        // Check headers
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        // Check custom header
        if (isset($_SERVER['HTTP_X_CSRF_PROTECTION'])) {
            return $_SERVER['HTTP_X_CSRF_PROTECTION'];
        }
        
        return null;
    }
    
    /**
     * Protect endpoint by requiring and validating CSRF token
     * Call this at the start of POST/PUT/DELETE endpoints
     * 
     * @return void Exits with error if validation fails
     */
    public static function protect() {
        // Skip validation for GET requests (except preflight)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return;
        }
        
        // Skip validation for OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return;
        }
        
        // Validate token
        if (!self::validateToken()) {
            Logger::warning('CSRF protection triggered', [
                'method' => $_SERVER['REQUEST_METHOD'],
                'uri' => $_SERVER['REQUEST_URI'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            ApiResponse::forbidden('CSRF token validation failed');
        }
    }
    
    /**
     * Get HTML input field for CSRF token
     * Use in HTML forms: <?php echo CsrfProtection::field(); ?>
     */
    public static function field() {
        $token = self::generateToken();
        return "<input type=\"hidden\" name=\"" . self::TOKEN_FIELD . "\" value=\"" . htmlspecialchars($token) . "\">";
    }
    
    /**
     * Validate token and throw exception if invalid
     * Useful for JSON APIs
     * 
     * @throws ForbiddenException If token is invalid
     */
    public static function protectJson() {
        if (!self::validateToken()) {
            throw new ForbiddenException('CSRF token validation failed');
        }
    }
    
    /**
     * Get token as JSON array for API responses
     * Include in responses to provide fresh token to client
     */
    public static function asJson() {
        return [
            'csrf_token' => self::generateToken(),
            'csrf_field' => self::TOKEN_FIELD
        ];
    }
    
    /**
     * Regenerate token after sensitive operation
     * Call after successful login
     */
    public static function regenerateToken() {
        unset($_SESSION[self::SESSION_KEY]);
        return self::generateToken();
    }
}
?>
