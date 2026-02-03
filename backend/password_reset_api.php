<?php
/**
 * Shebamiles - Forgot Password API
 */

header('Content-Type: application/json');
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Step 1: Request password reset
    if (isset($_POST['action']) && $_POST['action'] === 'request_reset') {
        $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Valid email required']);
            exit();
        }
        
        // Check if user exists
        $query = "SELECT u.user_id, u.username, u.email FROM users u WHERE u.email = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit();
        }
        
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Security: Don't reveal if email exists
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'If this email exists, a reset link has been sent']);
            $stmt->close();
            $conn->close();
            exit();
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Generate reset token (valid for 1 hour)
        $reset_token = bin2hex(random_bytes(32));
        $token_expiry = date('Y-m-d H:i:s', time() + 3600);
        
        // Store token in database
        $update_query = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        if (!$update_stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error processing request']);
            exit();
        }
        
        $update_stmt->bind_param('ssi', $reset_token, $token_expiry, $user['user_id']);
        
        if ($update_stmt->execute()) {
            // In production, send email with reset link
            // For now, return token (in real app, send via email only)
            http_response_code(200);
            echo json_encode([
                'success' => true, 
                'message' => 'Password reset link sent to email',
                'reset_token' => $reset_token // Remove in production!
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error processing request']);
        }
        $update_stmt->close();
    }
    // Step 2: Reset password with token
    else if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        $token = isset($_POST['token']) ? sanitize($_POST['token']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        if (empty($token) || empty($password) || empty($confirm_password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields required']);
            exit();
        }
        
        if ($password !== $confirm_password) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit();
        }
        
        if (strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
            exit();
        }
        
        // Verify token exists and hasn't expired
        $query = "SELECT user_id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit();
        }
        
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired reset link']);
            $stmt->close();
            $conn->close();
            exit();
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Hash password and update
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $update_query = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        if (!$update_stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating password']);
            exit();
        }
        
        $update_stmt->bind_param('si', $hashed_password, $user['user_id']);
        
        if ($update_stmt->execute()) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Password reset successful']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating password']);
        }
        $update_stmt->close();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>
