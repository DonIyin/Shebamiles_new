<?php
/**
 * Migration: Add Performance Indexes
 * Adds indexes on frequently queried columns to improve performance
 */

return [
    'description' => 'Add performance indexes to existing tables',
    
    'up' => function($conn) {
        $queries = [];
        
        // Check and add index on user_activity.timestamp if not exists
        $result = $conn->query("SHOW INDEX FROM user_activity WHERE Key_name = 'idx_timestamp'");
        if ($result->num_rows === 0) {
            $queries[] = "ALTER TABLE user_activity ADD INDEX idx_timestamp (timestamp)";
        }
        
        // Check and add index on user_activity (user_id, timestamp) if not exists
        $result = $conn->query("SHOW INDEX FROM user_activity WHERE Key_name = 'idx_user_timestamp'");
        if ($result->num_rows === 0) {
            $queries[] = "ALTER TABLE user_activity ADD INDEX idx_user_timestamp (user_id, timestamp)";
        }
        
        // Check and add index on login_attempts.attempt_time if not exists
        $result = $conn->query("SHOW INDEX FROM login_attempts WHERE Key_name = 'idx_attempt_time'");
        if ($result->num_rows === 0) {
            $queries[] = "ALTER TABLE login_attempts ADD INDEX idx_attempt_time (attempt_time)";
        }
        
        // Check and add index on users.last_login if not exists
        $result = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'idx_last_login'");
        if ($result->num_rows === 0) {
            $queries[] = "ALTER TABLE users ADD INDEX idx_last_login (last_login)";
        }
        
        // Check and add composite index on employees (department, status) if not exists
        $result = $conn->query("SHOW INDEX FROM employees WHERE Key_name = 'idx_dept_status'");
        if ($result->num_rows === 0) {
            $queries[] = "ALTER TABLE employees ADD INDEX idx_dept_status (department, status)";
        }
        
        $results = [];
        foreach ($queries as $query) {
            $result = $conn->query($query);
            if ($result === false) {
                throw new Exception("Migration failed: " . $conn->error);
            }
            $results[] = $result;
        }
        
        return $results;
    },
    
    'down' => function($conn) {
        $queries = [
            "ALTER TABLE user_activity DROP INDEX IF EXISTS idx_timestamp",
            "ALTER TABLE user_activity DROP INDEX IF EXISTS idx_user_timestamp",
            "ALTER TABLE login_attempts DROP INDEX IF EXISTS idx_attempt_time",
            "ALTER TABLE users DROP INDEX IF EXISTS idx_last_login",
            "ALTER TABLE employees DROP INDEX IF EXISTS idx_dept_status"
        ];
        
        $results = [];
        foreach ($queries as $query) {
            // Ignore errors on DROP INDEX IF EXISTS as older MySQL versions don't support it
            $result = @$conn->query($query);
            $results[] = $result;
        }
        
        return $results;
    }
];
?>
