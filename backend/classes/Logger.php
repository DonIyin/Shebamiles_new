<?php
/**
 * Shebamiles - Centralized Logging System
 * Logs errors, activities, and security events to file and database
 * 
 * Usage:
 *   Logger::info('User logged in', ['user_id' => 1]);
 *   Logger::error('Database error', $exception);
 *   Logger::security('Login attempt failed', ['attempt' => 1]);
 */

class Logger {
    
    // Log levels
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    const SECURITY = 'SECURITY';
    
    private static $logDir = null;
    private static $maxFileSize = 10485760; // 10MB
    
    /**
     * Initialize logger
     */
    public static function init($logDirectory = null) {
        self::$logDir = $logDirectory ?? __DIR__ . '/../logs';
        
        // Create logs directory if it doesn't exist
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
    
    /**
     * Log debug message
     */
    public static function debug($message, $context = []) {
        self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     */
    public static function info($message, $context = []) {
        self::log(self::INFO, $message, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning($message, $context = []) {
        self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Log error message
     */
    public static function error($message, $context = []) {
        self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Log critical error and alert
     */
    public static function critical($message, $context = []) {
        self::log(self::CRITICAL, $message, $context);
        // Send alert email or SMS (implement as needed)
    }
    
    /**
     * Log security-related events
     */
    public static function security($message, $context = []) {
        self::log(self::SECURITY, $message, $context);
    }
    
    /**
     * Core logging method
     */
    private static function log($level, $message, $context = []) {
        // Initialize if not done
        if (self::$logDir === null) {
            self::init();
        }
        
        // Format context
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        
        // Build log message
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message$contextStr";
        
        // Determine log file based on level
        $logFile = self::$logDir . '/' . self::getLogFilename($level);
        
        // Write to file
        self::writeToFile($logFile, $logMessage);
        
        // Log to database if available (for auditing)
        if (self::canLogToDatabase()) {
            self::logToDatabase($level, $message, $context);
        }
        
        // For CRITICAL and SECURITY, also log to application log
        if (in_array($level, [self::CRITICAL, self::SECURITY])) {
            self::writeToFile(self::$logDir . '/application.log', $logMessage);
        }
    }
    
    /**
     * Determine log filename based on level
     */
    private static function getLogFilename($level) {
        $date = date('Y-m-d');
        
        switch ($level) {
            case self::DEBUG:
                return "debug-$date.log";
            case self::ERROR:
            case self::CRITICAL:
                return "error-$date.log";
            case self::SECURITY:
                return "security-$date.log";
            case self::INFO:
            case self::WARNING:
            default:
                return "application-$date.log";
        }
    }
    
    /**
     * Write message to file with rotation
     */
    private static function writeToFile($filepath, $message) {
        // Check file size for rotation
        if (file_exists($filepath) && filesize($filepath) > self::$maxFileSize) {
            $rotated = $filepath . '.' . time();
            rename($filepath, $rotated);
        }
        
        // Write message
        $message .= PHP_EOL;
        error_log($message, 3, $filepath);
    }
    
    /**
     * Check if database logging is available
     */
    private static function canLogToDatabase() {
        // Only log to database if we have a connection and specific log levels
        global $conn;
        return isset($conn) && !mysqli_connect_error();
    }
    
    /**
     * Log to database for audit trail
     */
    private static function logToDatabase($level, $message, $context = []) {
        global $conn;
        
        if (!$conn) {
            return;
        }
        
        try {
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $context_json = json_encode($context);
            
            $query = "INSERT INTO logs (level, message, context, user_id, ip_address, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param('sssus', $level, $message, $context_json, $user_id, $ip_address);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            // Fail silently to prevent infinite loops
        }
    }
    
    /**
     * Get recent logs
     */
    public static function getRecentLogs($level = null, $limit = 100) {
        if (!self::canLogToDatabase()) {
            return [];
        }
        
        global $conn;
        
        $query = "SELECT * FROM logs";
        if ($level) {
            $query .= " WHERE level = '$level'";
        }
        $query .= " ORDER BY created_at DESC LIMIT $limit";
        
        $result = $conn->query($query);
        $logs = [];
        
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        return $logs;
    }
    
    /**
     * Clear old logs
     */
    public static function clearOldLogs($days = 30) {
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        if (self::canLogToDatabase()) {
            global $conn;
            $query = "DELETE FROM logs WHERE created_at < ?";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param('s', $cutoff);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// Initialize logger
Logger::init();
?>
