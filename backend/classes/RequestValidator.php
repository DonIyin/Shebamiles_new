<?php
/**
 * Shebamiles - Request Validator
 * Comprehensive input validation with detailed error messages
 * 
 * Usage:
 *   $validator = new RequestValidator($_POST);
 *   $validator->required('email')->email();
 *   $validator->required('password')->minLength(10)->passwordStrength();
 *   $validator->validate();
 */

class RequestValidator {
    
    private $data = [];
    private $rules = [];
    private $errors = [];
    private $messages = [];
    
    // Password strength constants
    const PASSWD_WEAK = 0;
    const PASSWD_FAIR = 1;
    const PASSWD_GOOD = 2;
    const PASSWD_STRONG = 3;
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    /**
     * Add required field rule
     */
    public function required($field, $message = null) {
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        $this->rules[$field]['required'] = true;
        
        if ($message) {
            if (!isset($this->messages[$field])) {
                $this->messages[$field] = [];
            }
            $this->messages[$field]['required'] = $message;
        }
        
        return $this;
    }
    
    /**
     * Add optional field rule
     */
    public function optional($field) {
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        $this->rules[$field]['required'] = false;
        
        return $this;
    }
    
    /**
     * Validate email format
     */
    public function email($field = null, $message = null) {
        $field = $field ?? array_key_last($this->rules);
        
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        $this->rules[$field]['email'] = true;
        
        if ($message) {
            if (!isset($this->messages[$field])) {
                $this->messages[$field] = [];
            }
            $this->messages[$field]['email'] = $message;
        }
        
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength($length, $field = null, $message = null) {
        $field = $field ?? array_key_last($this->rules);
        
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        $this->rules[$field]['minLength'] = $length;
        
        if ($message) {
            if (!isset($this->messages[$field])) {
                $this->messages[$field] = [];
            }
            $this->messages[$field]['minLength'] = $message;
        }
        
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength($length, $field = null, $message = null) {
        $field = $field ?? array_key_last($this->rules);
        
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        $this->rules[$field]['maxLength'] = $length;
        
        if ($message) {
            if (!isset($this->messages[$field])) {
                $this->messages[$field] = [];
            }
            $this->messages[$field]['maxLength'] = $message;
        }
        
        return $this;
    }
    
    /**
     * Validate password strength
     */
    public function passwordStrength($field = null, $minStrength = self::PASSWD_STRONG) {
        $field = $field ?? array_key_last($this->rules);
        
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        $this->rules[$field]['passwordStrength'] = $minStrength;
        
        return $this;
    }
    
    /**
     * Validate unique value (against database)
     */
    public function unique($table, $column, $field = null) {
        $field = $field ?? array_key_last($this->rules);
        
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        $this->rules[$field]['unique'] = [
            'table' => $table,
            'column' => $column
        ];
        
        return $this;
    }
    
    /**
     * Validate format with regex
     */
    public function format($pattern, $field = null, $message = null) {
        $field = $field ?? array_key_last($this->rules);
        
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        $this->rules[$field]['format'] = $pattern;
        
        if ($message) {
            if (!isset($this->messages[$field])) {
                $this->messages[$field] = [];
            }
            $this->messages[$field]['format'] = $message;
        }
        
        return $this;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric($field = null, $message = null) {
        $field = $field ?? array_key_last($this->rules);
        
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        $this->rules[$field]['numeric'] = true;
        
        if ($message) {
            if (!isset($this->messages[$field])) {
                $this->messages[$field] = [];
            }
            $this->messages[$field]['numeric'] = $message;
        }
        
        return $this;
    }
    
    /**
     * Validate date format
     */
    public function date($format = 'Y-m-d', $field = null, $message = null) {
        $field = $field ?? array_key_last($this->rules);
        
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        $this->rules[$field]['date'] = $format;
        
        if ($message) {
            if (!isset($this->messages[$field])) {
                $this->messages[$field] = [];
            }
            $this->messages[$field]['date'] = $message;
        }
        
        return $this;
    }
    
    /**
     * Validate against list of allowed values
     */
    public function in($values, $field = null, $message = null) {
        $field = $field ?? array_key_last($this->rules);
        
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        $this->rules[$field]['in'] = $values;
        
        if ($message) {
            if (!isset($this->messages[$field])) {
                $this->messages[$field] = [];
            }
            $this->messages[$field]['in'] = $message;
        }
        
        return $this;
    }
    
    /**
     * Set a field's value
     */
    public function setValue($field, $value) {
        $this->data[$field] = $value;
        return $this;
    }
    
    /**
     * Get validated data
     */
    public function getValidated() {
        return $this->data;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Run all validations
     */
    public function validate() {
        $this->errors = [];
        
        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            
            // Check if field is required
            if (isset($fieldRules['required']) && $fieldRules['required'] === true) {
                if (empty($value)) {
                    $this->addError($field, 'required', 'This field is required');
                    continue;
                }
            }
            
            // Skip validation if field is empty and not required
            if (empty($value) && (!isset($fieldRules['required']) || $fieldRules['required'] === false)) {
                continue;
            }
            
            // Run all validation rules
            $this->validateField($field, $value, $fieldRules);
        }
        
        return $this->passes();
    }
    
    /**
     * Validate a single field against all its rules
     */
    private function validateField($field, $value, $rules) {
        // Email validation
        if (isset($rules['email']) && $rules['email']) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, 'email', 'Please enter a valid email address');
            }
        }
        
        // Minimum length validation
        if (isset($rules['minLength'])) {
            if (strlen($value) < $rules['minLength']) {
                $msg = $this->messages[$field]['minLength'] ?? 
                    "Must be at least {$rules['minLength']} characters long";
                $this->addError($field, 'minLength', $msg);
            }
        }
        
        // Maximum length validation
        if (isset($rules['maxLength'])) {
            if (strlen($value) > $rules['maxLength']) {
                $msg = $this->messages[$field]['maxLength'] ?? 
                    "Cannot exceed {$rules['maxLength']} characters";
                $this->addError($field, 'maxLength', $msg);
            }
        }
        
        // Password strength validation
        if (isset($rules['passwordStrength'])) {
            $strength = $this->checkPasswordStrength($value);
            if ($strength < $rules['passwordStrength']) {
                $this->addError($field, 'passwordStrength', 
                    'Password must contain uppercase, lowercase, numbers, and special characters');
            }
        }
        
        // Numeric validation
        if (isset($rules['numeric']) && $rules['numeric']) {
            if (!is_numeric($value)) {
                $msg = $this->messages[$field]['numeric'] ?? 'Must be a numeric value';
                $this->addError($field, 'numeric', $msg);
            }
        }
        
        // Date validation
        if (isset($rules['date'])) {
            $d = DateTime::createFromFormat($rules['date'], $value);
            if (!$d || $d->format($rules['date']) !== $value) {
                $msg = $this->messages[$field]['date'] ?? 
                    "Invalid date format. Use {$rules['date']}";
                $this->addError($field, 'date', $msg);
            }
        }
        
        // In validation
        if (isset($rules['in'])) {
            if (!in_array($value, $rules['in'])) {
                $msg = $this->messages[$field]['in'] ?? 'Invalid value for this field';
                $this->addError($field, 'in', $msg);
            }
        }
        
        // Regex format validation
        if (isset($rules['format'])) {
            if (!preg_match($rules['format'], $value)) {
                $msg = $this->messages[$field]['format'] ?? 'Invalid format for this field';
                $this->addError($field, 'format', $msg);
            }
        }
        
        // Unique validation
        if (isset($rules['unique'])) {
            $this->validateUnique($field, $value, $rules['unique']);
        }
    }
    
    /**
     * Check password strength
     * Returns strength level from 0 (weak) to 3 (strong)
     */
    private function checkPasswordStrength($password) {
        $strength = 0;
        
        // Length check
        if (strlen($password) >= 10) {
            $strength++;
        }
        
        // Uppercase letter check
        if (preg_match('/[A-Z]/', $password)) {
            $strength++;
        }
        
        // Lowercase letter check
        if (preg_match('/[a-z]/', $password)) {
            $strength++;
        }
        
        // Number check
        if (preg_match('/\d/', $password)) {
            $strength++;
        }
        
        // Special character check
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|~`]/', $password)) {
            $strength++;
        }
        
        // Cap strength at 3 levels (0=Weak, 1=Fair, 2=Good, 3=Strong)
        return min(3, intdiv($strength, 2));
    }
    
    /**
     * Validate unique value against database
     */
    private function validateUnique($field, $value, $uniqueConfig) {
        global $conn;
        
        if (!$conn) {
            return; // Skip if no database connection
        }
        
        $table = $uniqueConfig['table'];
        $column = $uniqueConfig['column'];
        
        $query = "SELECT 1 FROM $table WHERE $column = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param('s', $value);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $this->addError($field, 'unique', "This $field is already in use");
            }
            
            $stmt->close();
        }
    }
    
    /**
     * Add an error for a field
     */
    private function addError($field, $rule, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][$rule] = $message;
    }
}
?>
