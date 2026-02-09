<?php
/**
 * Migration: Add Missing Tables
 * Creates rate_limits and logs tables required by the application
 */

return [
    'description' => 'Add rate_limits and logs tables',
    
    'up' => function($conn) {
        $queries = [];
        
        // Create rate_limits table
        $queries[] = "
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                identifier VARCHAR(255) NOT NULL,
                bucket VARCHAR(100) NOT NULL,
                timestamp INT NOT NULL,
                INDEX idx_identifier_bucket (identifier, bucket),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        // Create logs table
        $queries[] = "
            CREATE TABLE IF NOT EXISTS logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                level VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                context JSON,
                user_id INT NULL,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_level (level),
                INDEX idx_created_at (created_at),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
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
            "DROP TABLE IF EXISTS logs",
            "DROP TABLE IF EXISTS rate_limits"
        ];
        
        $results = [];
        foreach ($queries as $query) {
            $result = $conn->query($query);
            if ($result === false) {
                throw new Exception("Rollback failed: " . $conn->error);
            }
            $results[] = $result;
        }
        
        return $results;
    }
];
?>
