<?php
/**
 * Shebamiles - Security Headers Middleware
 * Adds essential security headers to all API responses
 * 
 * Usage: 
 *   SecurityHeaders::setHeaders();
 */

class SecurityHeaders {
    
    /**
     * Set all security headers
     */
    public static function setHeaders() {
        self::setCSPHeader();
        self::setNoCacheHeaders();
        self::setSecurityHeaders();
        self::setCORSHeaders();
    }
    
    /**
     * Set Content Security Policy header
     * Prevents XSS attacks by controlling what resources can load
     */
    private static function setCSPHeader() {
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com; " .
               "font-src 'self' https://fonts.gstatic.com data:; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' https:; " .
               "frame-ancestors 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self';";
        
        header('Content-Security-Policy: ' . $csp);
        header('X-Content-Security-Policy: ' . $csp);
    }
    
    /**
     * Set no-cache headers
     * Prevents sensitive data in browser cache
     */
    private static function setNoCacheHeaders() {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }
    
    /**
     * Set X-* security headers
     * Additional protection against common attacks
     */
    private static function setSecurityHeaders() {
        // Prevent clickjacking attacks
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection in browsers
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy for privacy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Feature Policy (now Permissions-Policy)
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // HSTS (HTTP Strict Transport Security) - only if HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
    
    /**
     * Set CORS headers for API requests
     * Controls which origins can access the API
     */
    private static function setCORSHeaders() {
        // Get allowed origins (from config or environment)
        $allowed_origins = self::getAllowedOrigins();
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Check if origin is allowed
        if (in_array($origin, $allowed_origins) || in_array('*', $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        }
        
        // Allow credentials
        header('Access-Control-Allow-Credentials: true');
        
        // Allow methods
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        
        // Allow headers
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
        
        // Max cache time for preflight requests
        header('Access-Control-Max-Age: 86400');
    }
    
    /**
     * Get allowed CORS origins from configuration
     */
    private static function getAllowedOrigins() {
        // Local development
        $origins = [
            'http://localhost',
            'http://localhost:3000',
            'http://localhost:8000',
            'http://127.0.0.1',
        ];
        
        // Add from environment if set
        if (isset($_ENV['CORS_ALLOWED_ORIGINS'])) {
            $origins = array_merge($origins, explode(',', $_ENV['CORS_ALLOWED_ORIGINS']));
        }
        
        // Always include current domain
        if (isset($_SERVER['HTTP_HOST'])) {
            $origins[] = 'http://' . $_SERVER['HTTP_HOST'];
            $origins[] = 'https://' . $_SERVER['HTTP_HOST'];
        }
        
        return $origins;
    }
    
    /**
     * Handle preflight requests (OPTIONS)
     */
    public static function handlePreflight() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::setHeaders();
            http_response_code(200);
            exit;
        }
    }
}
?>
