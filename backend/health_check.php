<?php
/**
 * Shebamiles - Health Check Endpoint
 * Returns system status and diagnostics
 * 
 * Checks:
 * - Database connection
 * - Required tables
 * - Log directory writability
 * - PHP extensions
 * - Configuration validity
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'version' => APP_VERSION ?? '2.0.0',
    'checks' => []
];

// Check database connection
$dbCheck = [
    'name' => 'Database Connection',
    'status' => 'pass',
    'details' => []
];

if (isset($db) && $db->isConnected()) {
    $dbCheck['details']['connected'] = true;
    $dbCheck['details']['host'] = DB_HOST;
    $dbCheck['details']['database'] = DB_NAME;
} else {
    $dbCheck['status'] = 'fail';
    $dbCheck['details']['connected'] = false;
    $dbCheck['details']['error'] = 'Database connection unavailable';
    $health['status'] = 'unhealthy';
}

$health['checks']['database'] = $dbCheck;

// Check required tables
$tablesCheck = [
    'name' => 'Required Tables',
    'status' => 'pass',
    'details' => []
];

$requiredTables = ['users', 'employees', 'user_activity', 'login_attempts', 'rate_limits', 'logs'];

if ($conn) {
    foreach ($requiredTables as $table) {
        $result = @$conn->query("SHOW TABLES LIKE '$table'");
        $exists = $result && $result->num_rows > 0;
        $tablesCheck['details'][$table] = $exists ? 'exists' : 'missing';
        
        if (!$exists) {
            $tablesCheck['status'] = 'warn';
            $health['status'] = 'degraded';
        }
    }
} else {
    $tablesCheck['status'] = 'fail';
    $tablesCheck['details']['error'] = 'Cannot check tables without database connection';
}

$health['checks']['tables'] = $tablesCheck;

// Check log directory
$logsCheck = [
    'name' => 'Logs Directory',
    'status' => 'pass',
    'details' => []
];

$logsDir = __DIR__ . '/logs';
$logsCheck['details']['path'] = $logsDir;
$logsCheck['details']['exists'] = is_dir($logsDir);
$logsCheck['details']['writable'] = is_writable($logsDir);

if (!is_dir($logsDir) || !is_writable($logsDir)) {
    $logsCheck['status'] = 'warn';
    $health['status'] = 'degraded';
}

$health['checks']['logs'] = $logsCheck;

// Check PHP extensions
$extensionsCheck = [
    'name' => 'PHP Extensions',
    'status' => 'pass',
    'details' => []
];

$requiredExtensions = ['mysqli', 'json', 'mbstring', 'session'];

foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    $extensionsCheck['details'][$ext] = $loaded ? 'loaded' : 'missing';
    
    if (!$loaded) {
        $extensionsCheck['status'] = 'fail';
        $health['status'] = 'unhealthy';
    }
}

$health['checks']['extensions'] = $extensionsCheck;

// Check configuration
$configCheck = [
    'name' => 'Configuration',
    'status' => 'pass',
    'details' => []
];

$configCheck['details']['environment'] = ENVIRONMENT ?? 'unknown';
$configCheck['details']['debug_mode'] = DEBUG_MODE ? 'enabled' : 'disabled';
$configCheck['details']['session_started'] = session_status() === PHP_SESSION_ACTIVE;

$health['checks']['configuration'] = $configCheck;

// Set overall status code based on health status
$statusCode = 200; // Default: OK
if ($health['status'] === 'unhealthy') {
    $statusCode = 503; // Service Unavailable
} elseif ($health['status'] === 'degraded') {
    $statusCode = 200; // Still OK but with warnings
}

http_response_code($statusCode);

echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
