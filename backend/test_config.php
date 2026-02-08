<?php
/**
 * Test script to check if config.php loads without errors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config.php';
    
    echo "Config loaded successfully!\n";
    echo "Database connected: " . ($conn->ping() ? "YES" : "NO") . "\n";
    echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? "YES" : "NO") . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
