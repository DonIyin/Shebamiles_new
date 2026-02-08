<?php
/**
 * Shebamiles - Database Schema Update
 * Adds new tables for logging, rate limiting, and audit trail
 * Run this script once to set up the new schema
 */

require_once 'config.php';

try {
    echo "== Shebamiles Database Schema Update ==\n\n";
    
    // ============================================
    // LOGS TABLE (for application logging)
    // ============================================
    
    $sql = "CREATE TABLE IF NOT EXISTS logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        level VARCHAR(20) NOT NULL DEFAULT 'INFO',
        message TEXT NOT NULL,
        context JSON,
        user_id INT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (level),
        INDEX (user_id),
        INDEX (created_at),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ logs table created successfully\n";
    } else {
        echo "✗ Error creating logs table: " . $conn->error . "\n";
    }
    
    // ============================================
    // RATE LIMITS TABLE
    // ============================================
    
    $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
        limit_id INT AUTO_INCREMENT PRIMARY KEY,
        identifier VARCHAR(255) NOT NULL,
        bucket VARCHAR(50) NOT NULL,
        timestamp INT NOT NULL,
        INDEX (identifier, bucket, timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ rate_limits table created successfully\n";
    } else {
        echo "✗ Error creating rate_limits table: " . $conn->error . "\n";
    }
    
    // ============================================
    // AUDIT LOGS TABLE (for compliance)
    // ============================================
    
    $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
        audit_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        entity_type VARCHAR(50) NOT NULL,
        entity_id INT,
        changes JSON,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (action),
        INDEX (entity_type),
        INDEX (created_at),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ audit_logs table created successfully\n";
    } else {
        echo "✗ Error creating audit_logs table: " . $conn->error . "\n";
    }
    
    // ============================================
    // PASSWORD HISTORY TABLE
    // ============================================
    
    $sql = "CREATE TABLE IF NOT EXISTS password_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ password_history table created successfully\n";
    } else {
        echo "✗ Error creating password_history table: " . $conn->error . "\n";
    }
    
    // ============================================
    // UPDATE USERS TABLE (add missing columns)
    // ============================================
    
    // Check if is_verified column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE";
        if ($conn->query($sql) === TRUE) {
            echo "✓ Added is_verified column to users table\n";
        } else {
            // Column might already exist, continue
        }
    }
    
    // Check if verification_token column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'verification_token'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE users ADD COLUMN verification_token VARCHAR(255)";
        if ($conn->query($sql) === TRUE) {
            echo "✓ Added verification_token column to users table\n";
        }
    }
    
    // ============================================
    // UPDATE EMPLOYEES TABLE
    // ============================================
    
    // Add audit columns if they don't exist
    $result = $conn->query("SHOW COLUMNS FROM employees LIKE 'created_by'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE employees 
                ADD COLUMN created_by INT,
                ADD COLUMN updated_by INT,
                ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        if ($conn->query($sql) === TRUE) {
            echo "✓ Added audit columns to employees table\n";
        }
    }
    
    // ============================================
    // VERIFY INDEXES
    // ============================================
    
    // Add missing indexes for performance
    $indexQueries = [
        "CREATE INDEX idx_users_email ON users(email)" => "users.email",
        "CREATE INDEX idx_users_status ON users(status)" => "users.status",
        "CREATE INDEX idx_users_role ON users(role)" => "users.role",
        "CREATE INDEX idx_employees_status ON employees(status)" => "employees.status",
        "CREATE INDEX idx_employees_department ON employees(department)" => "employees.department",
        "CREATE INDEX idx_employees_user_id ON employees(user_id)" => "employees.user_id"
    ];
    
    foreach ($indexQueries as $query => $description) {
        if (@$conn->query($query)) {
            echo "✓ Index created: $description\n";
        }
        // Ignore errors for indexes that already exist
    }
    
    echo "\n✓ Database schema update completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Review the IMPLEMENTATION_PLAN.md for next phases\n";
    echo "2. Update your frontend to use auth-secure.js instead of auth.js\n";
    echo "3. Test login functionality to ensure everything works\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
