<?php
/**
 * Shebamiles - Database Migration Runner
 * Runs pending database migrations in order
 * 
 * Usage:
 *   php run_migrations.php           - Run pending migrations
 *   php run_migrations.php --rollback - Rollback last migration
 *   php run_migrations.php --status   - Show migration status
 */

// Load configuration
require_once __DIR__ . '/config.php';

class MigrationRunner {
    
    private $conn;
    private $migrationsDir;
    private $migrationsTable = 'migrations';
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->migrationsDir = __DIR__ . '/migrations';
        $this->ensureMigrationsTable();
    }
    
    /**
     * Ensure migrations tracking table exists
     */
    private function ensureMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($this->conn->query($sql) === false) {
            die("Failed to create migrations table: " . $this->conn->error . "\n");
        }
    }
    
    /**
     * Get list of executed migrations
     */
    private function getExecutedMigrations() {
        $executed = [];
        $result = $this->conn->query("SELECT migration FROM {$this->migrationsTable} ORDER BY migration");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $executed[] = $row['migration'];
            }
        }
        
        return $executed;
    }
    
    /**
     * Get list of available migration files
     */
    private function getAvailableMigrations() {
        $migrations = [];
        
        if (!is_dir($this->migrationsDir)) {
            return $migrations;
        }
        
        $files = scandir($this->migrationsDir);
        
        foreach ($files as $file) {
            if (preg_match('/^(\d+_.+)\.php$/', $file, $matches)) {
                $migrations[] = $matches[1];
            }
        }
        
        sort($migrations);
        return $migrations;
    }
    
    /**
     * Get pending migrations
     */
    private function getPendingMigrations() {
        $available = $this->getAvailableMigrations();
        $executed = $this->getExecutedMigrations();
        
        return array_diff($available, $executed);
    }
    
    /**
     * Run pending migrations
     */
    public function runMigrations() {
        $pending = $this->getPendingMigrations();
        
        if (empty($pending)) {
            echo "No pending migrations.\n";
            return true;
        }
        
        echo "Found " . count($pending) . " pending migration(s).\n\n";
        
        foreach ($pending as $migration) {
            echo "Running migration: $migration\n";
            
            try {
                $this->runMigration($migration, 'up');
                $this->recordMigration($migration);
                echo "✓ Migration completed: $migration\n\n";
            } catch (Exception $e) {
                echo "✗ Migration failed: $migration\n";
                echo "Error: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "All migrations completed successfully!\n";
        return true;
    }
    
    /**
     * Run a single migration
     */
    private function runMigration($migration, $direction = 'up') {
        $file = $this->migrationsDir . '/' . $migration . '.php';
        
        if (!file_exists($file)) {
            throw new Exception("Migration file not found: $file");
        }
        
        $migrationConfig = require $file;
        
        if (!isset($migrationConfig[$direction])) {
            throw new Exception("Migration direction '$direction' not defined");
        }
        
        $callable = $migrationConfig[$direction];
        
        if (!is_callable($callable)) {
            throw new Exception("Migration '$direction' is not callable");
        }
        
        // Execute migration
        return $callable($this->conn);
    }
    
    /**
     * Record migration as executed
     */
    private function recordMigration($migration) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->migrationsTable} (migration) VALUES (?)");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $this->conn->error);
        }
        
        $stmt->bind_param('s', $migration);
        $result = $stmt->execute();
        $stmt->close();
        
        if (!$result) {
            throw new Exception("Failed to record migration: " . $this->conn->error);
        }
    }
    
    /**
     * Rollback last migration
     */
    public function rollbackLastMigration() {
        $executed = $this->getExecutedMigrations();
        
        if (empty($executed)) {
            echo "No migrations to rollback.\n";
            return true;
        }
        
        $lastMigration = end($executed);
        echo "Rolling back migration: $lastMigration\n";
        
        try {
            $this->runMigration($lastMigration, 'down');
            $this->removeMigrationRecord($lastMigration);
            echo "✓ Migration rolled back: $lastMigration\n";
            return true;
        } catch (Exception $e) {
            echo "✗ Rollback failed: $lastMigration\n";
            echo "Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Remove migration record
     */
    private function removeMigrationRecord($migration) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->migrationsTable} WHERE migration = ?");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $this->conn->error);
        }
        
        $stmt->bind_param('s', $migration);
        $result = $stmt->execute();
        $stmt->close();
        
        if (!$result) {
            throw new Exception("Failed to remove migration record: " . $this->conn->error);
        }
    }
    
    /**
     * Show migration status
     */
    public function showStatus() {
        $available = $this->getAvailableMigrations();
        $executed = $this->getExecutedMigrations();
        $pending = $this->getPendingMigrations();
        
        echo "Migration Status\n";
        echo "================\n\n";
        
        echo "Available migrations: " . count($available) . "\n";
        echo "Executed migrations:  " . count($executed) . "\n";
        echo "Pending migrations:   " . count($pending) . "\n\n";
        
        if (!empty($executed)) {
            echo "Executed:\n";
            foreach ($executed as $migration) {
                echo "  ✓ $migration\n";
            }
            echo "\n";
        }
        
        if (!empty($pending)) {
            echo "Pending:\n";
            foreach ($pending as $migration) {
                echo "  ○ $migration\n";
            }
            echo "\n";
        }
    }
}

// Main execution
if (!$conn) {
    die("Database connection not available. Please check your configuration.\n");
}

$runner = new MigrationRunner($conn);

// Parse command line arguments
$action = $argv[1] ?? 'run';

switch ($action) {
    case '--rollback':
        $runner->rollbackLastMigration();
        break;
        
    case '--status':
        $runner->showStatus();
        break;
        
    case 'run':
    default:
        $runner->runMigrations();
        break;
}
?>
