<?php
/**
 * Shebamiles - Custom API Exception
 * Provides consistent exception handling for API operations
 */

class ApiException extends Exception {
    
    protected $errorCode = ApiResponse::ERROR;
    protected $statusCode = 400;
    protected $errors = [];
    
    public function __construct(
        $message = 'An error occurred',
        $code = ApiResponse::ERROR,
        $statusCode = 400,
        $errors = []
    ) {
        $this->errorCode = $code;
        $this->statusCode = $statusCode;
        $this->errors = $errors;
        
        parent::__construct($message);
    }
    
    public function getErrorCode() {
        return $this->errorCode;
    }
    
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Send the exception as an API response
     */
    public function send() {
        if (!empty($this->errors)) {
            ApiResponse::validationError($this->message, $this->errors);
        } else {
            ApiResponse::error($this->message, $this->errorCode, $this->statusCode);
        }
    }
}

/**
 * Specialized exceptions for common scenarios
 */

class ValidationException extends ApiException {
    public function __construct($message = 'Validation failed', $errors = []) {
        parent::__construct($message, ApiResponse::VALIDATION_ERROR, 422, $errors);
    }
}

class NotFoundException extends ApiException {
    public function __construct($message = 'Resource not found') {
        parent::__construct($message, ApiResponse::NOT_FOUND, 404);
    }
}

class UnauthorizedException extends ApiException {
    public function __construct($message = 'Unauthorized access') {
        parent::__construct($message, ApiResponse::UNAUTHORIZED, 401);
    }
}

class ForbiddenException extends ApiException {
    public function __construct($message = 'Access forbidden') {
        parent::__construct($message, ApiResponse::FORBIDDEN, 403);
    }
}

class ConflictException extends ApiException {
    public function __construct($message = 'Conflict') {
        parent::__construct($message, ApiResponse::CONFLICT, 409);
    }
}

class RateLimitException extends ApiException {
    public function __construct($message = 'Too many requests') {
        parent::__construct($message, ApiResponse::TOO_MANY_REQUESTS, 429);
    }
}

class DatabaseException extends ApiException {
    public function __construct($message = 'Database error', $details = '') {
        Logger::error('Database operation failed', ['error' => $message . ' ' . $details]);
        parent::__construct('A database error occurred', ApiResponse::SERVER_ERROR, 500);
    }
}

class ServerException extends ApiException {
    public function __construct($message = 'Server error', $statusCode = 500) {
        Logger::critical('Server error', ['error' => $message]);
        parent::__construct('An unexpected error occurred', ApiResponse::SERVER_ERROR, $statusCode);
    }
}
?>
