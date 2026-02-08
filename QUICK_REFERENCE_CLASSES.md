# Quick Reference Guide - New Classes & Utilities

## Foundation Classes Overview

### 1. ApiResponse - Standardized API Responses

**Send Success:**
```php
ApiResponse::success('Data saved', ['id' => 123]); // Returns 200
ApiResponse::success('Created', $data, 201); // Custom status code
```

**Send Errors:**
```php
ApiResponse::error('General error');
ApiResponse::validationError('Validation failed', ['field' => 'Invalid']);
ApiResponse::notFound('Resource not found');
ApiResponse::unauthorized('You must login');
ApiResponse::forbidden('Access denied');
ApiResponse::tooManyRequests('Rate limit exceeded');
ApiResponse::serverError('Something went wrong', $details);
```

**Response Constants:**
```php
ApiResponse::SUCCESS              // 'SUCCESS'
ApiResponse::ERROR                // 'ERROR'
ApiResponse::VALIDATION_ERROR     // 'VALIDATION_ERROR'
ApiResponse::NOT_FOUND            // 'NOT_FOUND'
ApiResponse::UNAUTHORIZED         // 'UNAUTHORIZED'
ApiResponse::FORBIDDEN            // 'FORBIDDEN'
ApiResponse::TOO_MANY_REQUESTS    // 'TOO_MANY_REQUESTS'
ApiResponse::SERVER_ERROR         // 'SERVER_ERROR'
```

---

### 2. ApiException - Custom Exceptions

**Throw Exceptions:**
```php
throw new ApiException('Message', ApiResponse::ERROR, 400, ['field' => 'error']);
throw new ValidationException('Validation failed', $errors);
throw new NotFoundException('User not found');
throw new UnauthorizedException('Invalid credentials');
throw new ForbiddenException('Admin access required');
throw new RateLimitException('Too many requests');
throw new DatabaseException('Query failed');
```

**Exception Classes:**
- `ApiException` - Base (default 400 error)
- `ValidationException` - 422 Validation Error
- `NotFoundException` - 404 Not Found
- `UnauthorizedException` - 401 Unauthorized
- `ForbiddenException` - 403 Forbidden
- `ConflictException` - 409 Conflict
- `RateLimitException` - 429 Too Many Requests
- `DatabaseException` - 500 Server Error
- `ServerException` - 500 Server Error

---

### 3. Logger - Structured Logging

**Log Levels:**
```php
Logger::debug('Debug message', $context);     // Development
Logger::info('User login', ['user_id' => 1]); // Info events
Logger::warning('Unusual activity', $data);   // Warnings
Logger::error('Operation failed', $data);     // Errors
Logger::critical('System alert', $data);      // Critical
Logger::security('Login attempt', $data);     // Security
```

**Output:**
- Files in `backend/logs/` directory
- Database `logs` table (if connected)
- Automatic daily rotation
- 10MB file size limit

**Get Recent Logs:**
```php
$logs = Logger::getRecentLogs('ERROR', 50); // 50 recent errors
$logs = Logger::getRecentLogs(null, 100);   // 100 of all types
```

---

### 4. RequestValidator - Input Validation

**Basic Setup:**
```php
$validator = new RequestValidator($_POST);
$validator->required('email')->email();
$validator->required('password')->minLength(10);
$validator->optional('phone');

if ($validator->validate()) {
    $data = $validator->getValidated();
} else {
    throw new ValidationException('Invalid', $validator->errors());
}
```

**Validation Rules:**
```php
// Required/Optional
$validator->required('field');           // Must have value
$validator->optional('field');           // Can be empty

// String Rules
$validator->minLength(10);               // Minimum length
$validator->maxLength(255);              // Maximum length
$validator->email();                     // Valid email
$validator->format('/^[A-Z]+$/');        // Regex pattern

// Number/Date
$validator->numeric();                   // Must be number
$validator->date('Y-m-d');              // Date format

// Enumeration
$validator->in(['admin', 'user', 'guest']); // One of values

// Security
$validator->passwordStrength();          // Password complexity
$validator->unique('table', 'column');   // No duplicates in DB
```

**Chaining:**
```php
$validator = new RequestValidator($_POST);
$validator
    ->required('username')->minLength(3)->maxLength(50)
    ->required('password')->minLength(10)->passwordStrength()
    ->optional('phone')->format('/^\+?[0-9\-\s()]+$/');

if ($validator->validate()) {
    // All passed
}
```

---

### 5. SecurityHeaders - Security Headers Middleware

**Usage:**
```php
// Automatic - called by config.php
SecurityHeaders::setHeaders(); // Sets all headers

// Or individual
SecurityHeaders::handlePreflight(); // Handle OPTIONS requests
```

**Headers Added:**
- CSP (Content Security Policy)
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- CORS headers
- HSTS (on HTTPS)
- Permissions-Policy

---

### 6. CsrfProtection - CSRF Token Management

**Generate Token:**
```php
// Automatic - called by config.php
$token = CsrfProtection::generateToken();

// HTML form
<?php echo CsrfProtection::field(); ?>

// JSON response
$data = CsrfProtection::asJson();
// Returns: ['csrf_token' => '...', 'csrf_field' => 'csrf_token']
```

**Validate Token:**
```php
// Automatic protection
CsrfProtection::protect(); // Throws if invalid

// Manual validation
if (!CsrfProtection::validateToken($_POST['csrf_token'])) {
    throw new ForbiddenException('CSRF token invalid');
}
```

**Get Token:**
```php
$token = CsrfProtection::getToken();
```

---

### 7. RateLimiter - Request Rate Limiting

**Check Limit:**
```php
// Returns true if within limit, false if exceeded
if (!RateLimiter::check($identifier, $bucket, $limit, $window)) {
    throw new RateLimitException();
}

// Or shorthand
RateLimiter::limit('api', 100, 3600); // 100 per hour

// Login attempts
RateLimiter::limit('login', 5, 900); // 5 per 15 minutes
```

**Identifier Options:**
```php
// IP address (automatic)
$ip = $_SERVER['REMOTE_ADDR'];

// User ID
$id = $_SESSION['user_id'];

// Custom
$key = 'user_' . $user_id;
```

**Reset Limit:**
```php
// After successful login
RateLimiter::reset('login_attempts', $ip);
```

**Get Status:**
```php
$status = RateLimiter::getStatus('api', 100, 3600);
echo "Remaining: {$status['remaining']}/{$status['limit']}";
```

---

## Common Patterns

### API Endpoint Template

```php
<?php
require_once 'config.php';

try {
    // 1. Check method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new ApiException('Method not allowed', ApiResponse::ERROR, 405);
    }
    
    // 2. Get input
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // 3. Validate input
    $validator = new RequestValidator($input);
    $validator->required('email')->email();
    $validator->required('name')->minLength(2);
    if (!$validator->validate()) {
        throw new ValidationException('Invalid input', $validator->errors());
    }
    
    // 4. Authenticate
    requireLogin();
    
    // 5. Rate limit
    RateLimiter::limit('api_calls', 100, 3600);
    
    // 6. CSRF check
    CsrfProtection::protect();
    
    // 7. Authorization (if needed)
    requireAdmin();
    
    // 8. Business logic
    $result = doSomething($input['email'], $input['name']);
    
    // 9. Log activity
    logActivity(getCurrentUser()['user_id'], 'ACTION_NAME', 'Details');
    
    // 10. Return success
    ApiResponse::success('Operation successful', ['result' => $result]);
    
} catch (ApiException $e) {
    $e->send();
} catch (Exception $e) {
    Logger::critical('Unexpected error', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ApiResponse::serverError('An error occurred');
}
?>
```

### Frontend Integration

```javascript
// Secure API call
async function apiCall(endpoint, method, data) {
    const response = await fetch('../backend/' + endpoint, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': AuthModule.getCSRFToken()
        },
        credentials: 'include', // Include cookies
        body: JSON.stringify(data)
    });
    
    const result = await response.json();
    
    if (result.success) {
        showToast(result.message, 'success');
        return result.data;
    } else if (result.errors) {
        // Show validation errors
        for (const field in result.errors) {
            showToast(field + ': ' + result.errors[field], 'error');
        }
    } else {
        showToast(result.message || 'Error', 'error');
    }
    
    throw new Error(result.message);
}

// Usage
try {
    const data = await apiCall('some_api.php', 'POST', {
        email: 'user@example.com'
    });
    console.log('Success:', data);
} catch (e) {
    console.error('Failed:', e.message);
}
```

---

## Configuration Constants (config.php)

```php
// Database
DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT

// Sessions
SESSION_TIMEOUT (3600)
SESSION_SECURE_COOKIE (true for HTTPS)
SESSION_HTTP_ONLY (true)
SESSION_SAME_SITE ('Strict')

// Password requirements
PASSWORD_MIN_LENGTH (10)
PASSWORD_REQUIRE_UPPERCASE (true)
PASSWORD_REQUIRE_LOWERCASE (true)
PASSWORD_REQUIRE_NUMBERS (true)
PASSWORD_REQUIRE_SPECIAL (true)

// Rate limits
MAX_LOGIN_ATTEMPTS (5)
LOCKOUT_TIME (15)
LOGIN_RATE_LIMIT (5)
LOGIN_RATE_LIMIT_WINDOW (900)
API_RATE_LIMIT (100)
API_RATE_LIMIT_WINDOW (3600)

// Application
BASE_URL
APP_NAME
APP_VERSION
DEBUG_MODE
ENVIRONMENT
```

---

## Helper Functions (config.php)

```php
// Authentication
isLoggedIn()            // Check if logged in
getCurrentUser()        // Get current user array
requireLogin()          // Throw if not logged in
requireRole($role)      // Throw if wrong role
requireAdmin()          // Throw if not admin

// Passwords
hashPassword($pwd)      // Hash a password
verifyPassword($pwd, $hash) // Verify password
checkPasswordStrength($pwd) // Get strength 0-3
validatePassword($pwd)  // Get validation errors array

// Input
sanitize($input)        // Trim whitespace
isValidEmail($email)    // Validate email format

//Data
getEmployeeInfo($user_id)
getAllEmployees($limit, $offset)
getTotalEmployeeCount()

// Activity
logActivity($user_id, $activity, $details)
```

---

## Testing Endpoints

**Test API Response Format:**
```bash
curl -X POST http://localhost/Shebamiles_new/backend/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"test"}'
```

**Test Rate Limiting:**
```bash
# Fast requests - should fail on 6th
for i in {1..10}; do
  curl http://localhost/Shebamiles_new/backend/some_api.php
done
```

**Test CSRF Protection:**
```bash
# Without CSRF token - should fail
curl -X POST http://localhost/Shebamiles_new/backend/protected_api.php \
  -H "Content-Type: application/json" \
  -d '{"action":"delete"}'
```

---

**Last Updated:** February 8, 2026  
**Version:** 1.0  
**Status:** Phase 1 Complete
