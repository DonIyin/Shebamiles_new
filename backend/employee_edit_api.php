<?php
/**
 * Shebamiles - Employee Edit API
 */

header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only admins can edit employee profiles
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get employee details
    $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    
    if (!$employee_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing employee_id']);
        exit();
    }
    
    $query = "SELECT e.*, u.username, u.email as user_email, u.role
              FROM employees e
              LEFT JOIN users u ON e.user_id = u.user_id
              WHERE e.employee_id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }
    
    $stmt->bind_param('i', $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    $stmt->close();
    
    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit();
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'employee' => $employee
    ]);
    
} elseif ($method === 'POST' || $method === 'PUT') {
    // Update employee details
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['employee_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing employee_id']);
        exit();
    }
    
    $employee_id = (int)$data['employee_id'];
    
    $query = "UPDATE employees SET ";
    $updates = [];
    $params = [];
    $types = '';
    
    // Allowed fields to update (email and username are NOT allowed)
    $allowed_fields = [
        'first_name', 'last_name', 'phone', 'department', 'position', 'salary',
        'hire_date', 'status', 'bio', 'profile_picture', 'address', 'city',
        'state', 'postal_code', 'country', 'emergency_contact_name',
        'emergency_contact_phone', 'emergency_contact_relation', 'employee_code'
    ];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field]) && $data[$field] !== '') {
            // Validate specific fields
            if ($field === 'salary') {
                $salary_val = (float)$data[$field];
                if ($salary_val < 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Salary cannot be negative']);
                    exit();
                }
                $updates[] = "$field = ?";
                $params[] = $salary_val;
                $types .= 'd';
            } elseif ($field === 'hire_date') {
                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data[$field])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid hire date format (use YYYY-MM-DD)']);
                    exit();
                }
                $updates[] = "$field = ?";
                $params[] = sanitize($data[$field]);
                $types .= 's';
            } else {
                $updates[] = "$field = ?";
                $params[] = sanitize($data[$field]);
                $types .= 's';
            }
        }
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
        exit();
    }
    
    $query .= implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE employee_id = ?";
    $params[] = $employee_id;
    $types .= 'i';
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating employee']);
    }
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>
