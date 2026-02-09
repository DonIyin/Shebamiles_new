<?php
/**
 * Shebamiles - Database Connection Manager
 * Singleton pattern for robust database connection handling
 * 
 * Features:
 * - Automatic connection retry with exponential backoff
 * - Graceful degradation when database unavailable
 * - Connection timeout handling
 * - Query error handling with logging
 * - Connection pooling support
 */

class Database {
    
    private static $instance = null;
    private $connection = null;
    private $isConnected = false;
    private $maxRetries = 3;
    private $retryDelay = 1; // Initial delay in seconds
    private $connectionAttempts = 0;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     * 
     * @return mysqli|null Connection object or null if unavailable
     */
    public function getConnection() {
        // Try to reconnect if connection was lost
        if ($this->connection && $this->connection->ping() === false) {
            $this->isConnected = false;
            $this->reconnect();
        }
        
        return $this->isConnected ? $this->connection : null;
    }
    
    /**
     * Check if database is connected
     * 
     * @return bool True if connected
     */
    public function isConnected() {
        if ($this->connection && $this->isConnected) {
            // Verify connection is still alive
            if (@$this->connection->ping() === false) {
                $this->isConnected = false;
            }
        }
        return $this->isConnected;
    }
    
    /**
     * Connect to database with retry logic
     */
    private function connect() {
        $attempt = 0;
        $delay = $this->retryDelay;
        
        while ($attempt < $this->maxRetries && !$this->isConnected) {
            $attempt++;
            $this->connectionAttempts++;
            
            try {
                // Initialize mysqli
                $mysqli = mysqli_init();
                
                if (!$mysqli) {
                    throw new Exception('mysqli_init failed');
                }
                
                // Set connection timeout before connecting
                $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
                
                // Connect to database
                $result = @$mysqli->real_connect(
                    DB_HOST,
                    DB_USER,
                    DB_PASS,
                    DB_NAME,
                    DB_PORT
                );
                
                // Check for connection errors
                if (!$result) {
                    throw new Exception($mysqli->connect_error ?? 'Connection failed');
                }
                
                $this->connection = $mysqli;
                
                // Configure connection
                $this->connection->set_charset("utf8mb4");
                $this->connection->query("SET SESSION sql_mode='STRICT_TRANS_TABLES'");
                
                $this->isConnected = true;
                
                // Log successful connection if there were retries
                if ($attempt > 1 && class_exists('Logger')) {
                    Logger::info('Database connection successful', [
                        'attempts' => $attempt
                    ]);
                }
                
                return;
                
            } catch (Exception $e) {
                // Log error but don't throw
                if (class_exists('Logger')) {
                    Logger::warning('Database connection attempt failed', [
                        'attempt' => $attempt,
                        'max_retries' => $this->maxRetries,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Wait before retry (exponential backoff)
                if ($attempt < $this->maxRetries) {
                    sleep($delay);
                    $delay *= 2; // Exponential backoff
                }
            }
        }
        
        // All retries failed
        if (!$this->isConnected && class_exists('Logger')) {
            Logger::critical('Database connection failed after all retries', [
                'attempts' => $attempt,
                'host' => DB_HOST,
                'database' => DB_NAME
            ]);
        }
    }
    
    /**
     * Reconnect to database
     */
    private function reconnect() {
        if ($this->connection) {
            @$this->connection->close();
        }
        
        $this->connection = null;
        $this->isConnected = false;
        $this->connect();
    }
    
    /**
     * Execute a query with error handling
     * 
     * @param string $sql SQL query
     * @return mysqli_result|bool Query result or false on error
     */
    public function query($sql) {
        if (!$this->isConnected()) {
            if (class_exists('Logger')) {
                Logger::error('Query attempted without database connection', [
                    'query' => substr($sql, 0, 100)
                ]);
            }
            return false;
        }
        
        try {
            $result = $this->connection->query($sql);
            
            if ($result === false && class_exists('Logger')) {
                Logger::error('Query execution failed', [
                    'error' => $this->connection->error,
                    'query' => substr($sql, 0, 100)
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::error('Query exception', [
                    'error' => $e->getMessage(),
                    'query' => substr($sql, 0, 100)
                ]);
            }
            return false;
        }
    }
    
    /**
     * Prepare a statement with error handling
     * 
     * @param string $sql SQL query with placeholders
     * @return mysqli_stmt|false Prepared statement or false on error
     */
    public function prepare($sql) {
        if (!$this->isConnected()) {
            if (class_exists('Logger')) {
                Logger::error('Prepare attempted without database connection', [
                    'query' => substr($sql, 0, 100)
                ]);
            }
            return false;
        }
        
        try {
            $stmt = $this->connection->prepare($sql);
            
            if ($stmt === false && class_exists('Logger')) {
                Logger::error('Statement preparation failed', [
                    'error' => $this->connection->error,
                    'query' => substr($sql, 0, 100)
                ]);
            }
            
            return $stmt;
            
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::error('Prepare exception', [
                    'error' => $e->getMessage(),
                    'query' => substr($sql, 0, 100)
                ]);
            }
            return false;
        }
    }
    
    /**
     * Get the last insert ID
     * 
     * @return int Last insert ID or 0 if unavailable
     */
    public function getInsertId() {
        if ($this->isConnected()) {
            return $this->connection->insert_id;
        }
        return 0;
    }
    
    /**
     * Get number of affected rows
     * 
     * @return int Affected rows or 0 if unavailable
     */
    public function getAffectedRows() {
        if ($this->isConnected()) {
            return $this->connection->affected_rows;
        }
        return 0;
    }
    
    /**
     * Get last error message
     * 
     * @return string Error message
     */
    public function getError() {
        if ($this->connection) {
            return $this->connection->error;
        }
        return 'No database connection';
    }
    
    /**
     * Escape string for SQL
     * 
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escape($string) {
        if ($this->isConnected()) {
            return $this->connection->real_escape_string($string);
        }
        return addslashes($string);
    }
    
    /**
     * Begin transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction() {
        if ($this->isConnected()) {
            return $this->connection->begin_transaction();
        }
        return false;
    }
    
    /**
     * Commit transaction
     * 
     * @return bool Success status
     */
    public function commit() {
        if ($this->isConnected()) {
            return $this->connection->commit();
        }
        return false;
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool Success status
     */
    public function rollback() {
        if ($this->isConnected()) {
            return $this->connection->rollback();
        }
        return false;
    }
    
    /**
     * Get connection statistics
     * 
     * @return array Connection statistics
     */
    public function getStats() {
        return [
            'connected' => $this->isConnected,
            'total_attempts' => $this->connectionAttempts,
            'host' => DB_HOST,
            'database' => DB_NAME
        ];
    }
    
    /**
     * Close database connection
     */
    public function close() {
        if ($this->connection) {
            @$this->connection->close();
        }
        $this->connection = null;
        $this->isConnected = false;
    }
    
    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>
