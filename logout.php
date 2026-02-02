<?php
/**
 * Shebamiles - Logout Handler
 */

require_once 'config.php';

// Log the logout activity
if (isLoggedIn()) {
    $user = getCurrentUser();
    logActivity($user['user_id'], 'LOGOUT', 'User logged out');
}

// Destroy session
session_destroy();

// Clear cookies
setcookie(SESSION_NAME, '', time() - 3600, '/');
setcookie('remember_token', '', time() - 3600, '/');

// Redirect to login page
header('Location: ' . BASE_URL . 'index.html');
exit();
?>
