<?php
/**
 * Shebamiles - Standardized API Response Class
 * Provides consistent response format for all API endpoints
 * 
 * Usage:
 *   ApiResponse::success('Data saved successfully', ['id' => 1]);
 *   ApiResponse::error('Validation failed', 'VALIDATION_ERROR', 400);
 */

class ApiResponse {
    
    const SUCCESS = 'SUCCESS';
    const ERROR = 'ERROR';
    const VALIDATION_ERROR = 'VALIDATION_ERROR';
    const NOT_FOUND = 'NOT_FOUND';
    const UNAUTHORIZED = 'UNAUTHORIZED';
    const FORBIDDEN = 'FORBIDDEN';
    const SERVER_ERROR = 'SERVER_ERROR';
    const CONFLICT = 'CONFLICT';
    const TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';
    
    /**
     * Send success response
     * 
     * @param string $message User-friendly message
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     */
    public static function success($message = 'Success', $data = [], $statusCode = 200) {
        self::sendResponse(true, self::SUCCESS, $message, $data, null, $statusCode);
    }
    
    /**
     * Send error response
     * 
     * @param string $message User-friendly error message
     * @param string $code Error code constant
     * @param int $statusCode HTTP status code
     * @param array $errors Detailed error information
     */
    public static function error($message = 'Error', $code = self::ERROR, $statusCode = 400, $errors = []) {
        self::sendResponse(false, $code, $message, [], $errors, $statusCode);
    }
    
    /**
     * Send validation error response
     * 
     * @param string $message General validation message
     * @param array $errors Field-level validation errors
     * @param int $statusCode HTTP status code (default 422)
     */
    public static function validationError($message = 'Validation failed', $errors = []) {
        self::sendResponse(false, self::VALIDATION_ERROR, $message, [], $errors, 422);
    }
    
    /**
     * Send not found response
     * 
     * @param string $message Not found message
     */
    public static function notFound($message = 'Resource not found') {
        self::sendResponse(false, self::NOT_FOUND, $message, [], null, 404);
    }
    
    /**
     * Send unauthorized response
     * 
     * @param string $message Unauthorized message
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::sendResponse(false, self::UNAUTHORIZED, $message, [], null, 401);
    }
    
    /**
     * Send forbidden response
     * 
     * @param string $message Forbidden message
     */
    public static function forbidden($message = 'Access forbidden') {
        self::sendResponse(false, self::FORBIDDEN, $message, [], null, 403);
    }
    
    /**
     * Send too many requests response
     * 
     * @param string $message Rate limit message
     */
    public static function tooManyRequests($message = 'Too many requests. Please try again later.') {
        self::sendResponse(false, self::TOO_MANY_REQUESTS, $message, [], null, 429);
    }
    
    /**
     * Send server error response
     * 
     * @param string $message Error message
     * @param array $details Debug details (only in development)
     */
    public static function serverError($message = 'An unexpected error occurred', $details = []) {
        $errors = (defined('DEBUG_MODE') && DEBUG_MODE) ? ['details' => $details] : null;
        self::sendResponse(false, self::SERVER_ERROR, $message, [], $errors, 500);
    }
    
    /**
     * Core response sending method
     * 
     * @param bool $success Operation success status
     * @param string $code Response code
     * @param string $message User message
     * @param array $data Response data
     * @param mixed $errors Error details
     * @param int $statusCode HTTP status code
     */
    private static function sendResponse($success, $code, $message, $data = [], $errors = null, $statusCode = 200) {
        // Set HTTP response code
        http_response_code($statusCode);
        
        // Build response array
        $response = [
            'success' => $success,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
            'version' => '1.0'
        ];
        
        // Include errors if present
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        // Set response headers
        header('Content-Type: application/json; charset=utf-8');
        
        // Output JSON response
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Format validation errors for consistent display
     * 
     * @param array $fieldErrors Key => error message pairs
     * @return array Formatted errors
     */
    public static function formatValidationErrors($fieldErrors) {
        $formatted = [];
        foreach ($fieldErrors as $field => $errors) {
            $formatted[$field] = is_array($errors) ? $errors : [$errors];
        }
        return $formatted;
    }
}

// Set content type for all API responses
header('Content-Type: application/json; charset=utf-8');
?>
