<?php
/**
 * Shebamiles - Email Verification Handler
 */

require_once 'config.php';

$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

if (empty($token)) {
    echo "Invalid verification link";
    exit();
}

// Find user with token
$query = "SELECT user_id FROM users WHERE verification_token = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Invalid or expired verification link";
    exit();
}

$user = $result->fetch_assoc();

// Mark as verified
$query = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user['user_id']);
$stmt->execute();

// Log activity
logActivity($user['user_id'], 'EMAIL_VERIFIED', 'Email address verified');

echo "Email verified successfully! You can now <a href='../index.html'>login</a>";
?>
