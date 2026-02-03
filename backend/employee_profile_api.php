<?php
/**
 * Shebamiles - Employee Profile API
 * Returns the logged-in employee's profile details
 */

header('Content-Type: application/json');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT 
            u.user_id, u.username, u.email, u.first_name, u.last_name, u.role, u.status, u.created_at, u.last_login,
            e.employee_id, e.employee_code, e.department, e.position, e.phone, e.address, e.city, e.state, e.postal_code, e.country, e.hire_date, e.bio
          FROM users u
          LEFT JOIN employees e ON u.user_id = e.user_id
          WHERE u.user_id = ?
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Profile not found']);
    exit();
}

$row = $result->fetch_assoc();

http_response_code(200);
echo json_encode([
    'success' => true,
    'profile' => [
        'user_id' => $row['user_id'],
        'username' => $row['username'] ?? '',
        'email' => $row['email'] ?? '',
        'first_name' => $row['first_name'] ?? '',
        'last_name' => $row['last_name'] ?? '',
        'role' => $row['role'] ?? 'employee',
        'status' => $row['status'] ?? 'active',
        'created_at' => $row['created_at'] ?? '',
        'last_login' => $row['last_login'] ?? '',
        'employee_id' => $row['employee_id'] ?? '',
        'employee_code' => $row['employee_code'] ?? '',
        'department' => $row['department'] ?? '',
        'position' => $row['position'] ?? '',
        'phone' => $row['phone'] ?? '',
        'address' => $row['address'] ?? '',
        'city' => $row['city'] ?? '',
        'state' => $row['state'] ?? '',
        'postal_code' => $row['postal_code'] ?? '',
        'country' => $row['country'] ?? '',
        'hire_date' => $row['hire_date'] ?? '',
        'bio' => $row['bio'] ?? ''
    ]
]);

$stmt->close();
?>