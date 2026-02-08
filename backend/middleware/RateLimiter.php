<?php
/**
 * Shebamiles - Rate Limiter Middleware
 * Prevents brute force and DDoS attacks with request rate limiting
 * 
 * Usage:
 *   RateLimiter::limit('login', 5, 900); // 5 attempts per 15 minutes
 *   RateLimiter::limit('api', 100, 3600); // 100 requests per hour
 */

class RateLimiter {
    
    private static $limiters = [];
    
    /**
     * Check rate limit for an endpoint/user
     * 
     * @param string $identifier Unique identifier (IP, user_id, etc)
     * @param string $bucket Rate limit bucket name (e.g., 'login', 'api')
     * @param int $limit Maximum requests allowed
     * @param int $window Time window in seconds
     * @return bool True if within limit, false if limit exceeded
     */
    public static function check($identifier, $bucket = 'default', $limit = 100, $window = 3600) {
        global $conn;
        
        $now = time();
        $window_start = $now - $window;
        
        // Use database for rate limiting (persistent across requests)
        if ($conn && !mysqli_connect_error()) {
            return self::checkDatabase($identifier, $bucket, $limit, $window_start, $now);
        }
        
        // Fallback to file-based rate limiting
        return self::checkFile($identifier, $bucket, $limit, $window_start);
    }
    
    /**
     * Check rate limit using database
     */
    private static function checkDatabase($identifier, $bucket, $limit, $window_start, $now) {
        global $conn;
        
        // Count requests in the time window
        $query = "SELECT COUNT(*) as count FROM rate_limits 
                 WHERE identifier = ? AND bucket = ? AND timestamp > ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return true; // Allow request if DB error (fail open)
        }
        
        $stmt->bind_param('ssi', $identifier, $bucket, $window_start);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $current_count = $row['count'] ?? 0;
        
        // Check if limit exceeded
        if ($current_count >= $limit) {
            Logger::warning('Rate limit exceeded', [
                'identifier' => $identifier,
                'bucket' => $bucket,
                'limit' => $limit,
                'current_count' => $current_count
            ]);
            return false;
        }
        
        // Record this request
        $query = "INSERT INTO rate_limits (identifier, bucket, timestamp) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param('ssi', $identifier, $bucket, $now);
            $stmt->execute();
            $stmt->close();
        }
        
        // Clean old records periodically
        if (rand(1, 100) === 1) {
            self::cleanup();
        }
        
        return true;
    }
    
    /**
     * Check rate limit using file-based storage
     */
    private static function checkFile($identifier, $bucket, $limit, $window_start) {
        $cache_dir = sys_get_temp_dir() . '/shebamiles_rate_limit';
        
        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, 0755, true);
        }
        
        $file = $cache_dir . '/' . md5($identifier . '_' . $bucket) . '.json';
        
        // Read existing requests
        $requests = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            $requests = $data['requests'] ?? [];
        }
        
        // Filter out old requests
        $requests = array_filter($requests, fn($timestamp) => $timestamp > $window_start);
        
        // Check if limit exceeded
        if (count($requests) >= $limit) {
            Logger::warning('Rate limit exceeded (file-based)', [
                'identifier' => $identifier,
                'bucket' => $bucket,
                'limit' => $limit,
                'current_count' => count($requests)
            ]);
            return false;
        }
        
        // Add current request
        $requests[] = time();
        
        // Save updated requests
        file_put_contents($file, json_encode(['requests' => $requests]), LOCK_EX);
        
        return true;
    }
    
    /**
     * Clean up old rate limit records from database
     */
    public static function cleanup() {
        global $conn;
        
        if (!$conn || mysqli_connect_error()) {
            return;
        }
        
        $cutoff = time() - (7 * 24 * 3600); // 7 days
        
        $query = "DELETE FROM rate_limits WHERE timestamp < ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param('i', $cutoff);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Generic rate limit protection for endpoints
     * Call at the start of sensitive endpoints
     * 
     * @param string $bucket Name of the rate limit bucket
     * @param int $limit Maximum requests allowed
     * @param int $window Time window in seconds
     * @return void Exits with error if limit exceeded
     */
    public static function limit($bucket, $limit, $window) {
        $identifier = self::getIdentifier();
        
        if (!self::check($identifier, $bucket, $limit, $window)) {
            ApiResponse::tooManyRequests(
                "Rate limit exceeded for $bucket. Please try again later."
            );
        }
    }
    
    /**
     * Get request identifier (IP address)
     * Handles proxies and load balancers
     */
    private static function getIdentifier() {
        // Check for IP from shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        // Default to REMOTE_ADDR
        else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        // Validate IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        return 'unknown';
    }
    
    /**
     * Reset rate limit for specific identifier
     * Call after successful login or verification
     */
    public static function reset($bucket, $identifier = null) {
        $identifier = $identifier ?? self::getIdentifier();
        
        global $conn;
        
        if ($conn && !mysqli_connect_error()) {
            $query = "DELETE FROM rate_limits WHERE identifier = ? AND bucket = ?";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param('ss', $identifier, $bucket);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    /**
     * Get current rate limit status
     */
    public static function getStatus($bucket, $limit, $window) {
        global $conn;
        
        $identifier = self::getIdentifier();
        $window_start = time() - $window;
        
        if ($conn && !mysqli_connect_error()) {
            $query = "SELECT COUNT(*) as count FROM rate_limits 
                     WHERE identifier = ? AND bucket = ? AND timestamp > ?";
            
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param('ssi', $identifier, $bucket, $window_start);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                return [
                    'current' => $row['count'] ?? 0,
                    'limit' => $limit,
                    'remaining' => max(0, $limit - ($row['count'] ?? 0))
                ];
            }
        }
        
        return [
            'current' => 0,
            'limit' => $limit,
            'remaining' => $limit
        ];
    }
}
?>
