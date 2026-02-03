<?php
/**
 * Shebamiles - Leave Requests API
 */

header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only admins can view/edit leave requests
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Check if fetching a single leave request by ID
    if (isset($_GET['id'])) {
        $leave_id = (int)$_GET['id'];
        
        $query = "SELECT l.*, e.first_name, e.last_name, e.employee_code, 
                         reviewer.first_name as reviewer_first_name, reviewer.last_name as reviewer_last_name,
                         CONCAT(reviewer.first_name, ' ', reviewer.last_name) as reviewer_name
                  FROM leave_requests l
                  JOIN employees e ON l.employee_id = e.employee_id
                  LEFT JOIN employees reviewer ON l.approved_by = reviewer.employee_id
                  WHERE l.leave_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $leave_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        
        if ($request) {
            http_response_code(200);
            echo json_encode(['success' => true, 'request' => $request]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Leave request not found']);
        }
        $stmt->close();
        $conn->close();
        exit();
    }
    
    // Get leave requests
    $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
    $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    
    $query = "SELECT l.*, e.first_name, e.last_name, e.employee_code, 
                     reviewer.first_name as reviewer_first_name, reviewer.last_name as reviewer_last_name,
                     CONCAT(reviewer.first_name, ' ', reviewer.last_name) as reviewer_name
              FROM leave_requests l
              JOIN employees e ON l.employee_id = e.employee_id
              LEFT JOIN employees reviewer ON l.approved_by = reviewer.employee_id
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($status)) {
        // Validate status
        $valid_statuses = ['pending', 'approved', 'rejected', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit();
        }
        $query .= " AND l.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if ($employee_id) {
        $query .= " AND l.employee_id = ?";
        $params[] = $employee_id;
        $types .= 'i';
    }
    
    $query .= " ORDER BY l.start_date DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = [];
    
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'count' => count($requests)
    ]);
    
} elseif ($method === 'POST') {
    // Create/Update leave request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['employee_id']) || !isset($data['start_date']) || !isset($data['end_date']) || !isset($data['leave_type'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    $employee_id = (int)$data['employee_id'];
    $start_date = sanitize($data['start_date']);
    $end_date = sanitize($data['end_date']);
    $leave_type = sanitize($data['leave_type']);
    $reason = isset($data['reason']) ? sanitize($data['reason']) : '';
    
    // Validate dates
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid date format (use YYYY-MM-DD)']);
        exit();
    }
    
    $startObj = new DateTime($start_date);
    $endObj = new DateTime($end_date);
    
    // End date must be after start date
    if ($endObj < $startObj) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'End date must be after or equal to start date']);
        exit();
    }
    
    // Validate leave type
    $valid_types = ['annual', 'sick', 'personal', 'maternity', 'emergency', 'unpaid'];
    if (!in_array($leave_type, $valid_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid leave type']);
        exit();
    }
    
    // Calculate duration in business days (excluding weekends)
    $duration_days = 0;
    $current = clone $startObj;
    while ($current <= $endObj) {
        // Count only weekdays (Monday=1 to Friday=5)
        if ((int)$current->format('N') <= 5) {
            $duration_days++;
        }
        $current->modify('+1 day');
    }
    
    $query = "INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, duration_days, reason, status)
              VALUES (?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param('isssds', $employee_id, $leave_type, $start_date, $end_date, $duration_days, $reason);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Leave request submitted', 'leave_id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error creating leave request']);
    }
    $stmt->close();
    
} elseif ($method === 'PUT') {
    // Approve/Reject leave request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['leave_id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    // Validate status
    $valid_statuses = ['pending', 'approved', 'rejected', 'cancelled'];
    if (!in_array($data['status'], $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }
    
    $leave_id = (int)$data['leave_id'];
    $status = sanitize($data['status']);
    $rejection_reason = isset($data['rejection_reason']) ? sanitize($data['rejection_reason']) : '';
    
    // Get current user's employee_id
    $user_id = $_SESSION['user_id'];
    $emp_query = "SELECT employee_id FROM employees WHERE user_id = ?";
    $emp_stmt = $conn->prepare($emp_query);
    $emp_stmt->bind_param('i', $user_id);
    $emp_stmt->execute();
    $emp_result = $emp_stmt->get_result();
    $emp_row = $emp_result->fetch_assoc();
    $approved_by = $emp_row ? $emp_row['employee_id'] : null;
    $emp_stmt->close();
    
    $query = "UPDATE leave_requests 
              SET status = ?, approved_by = ?, approved_date = CURRENT_TIMESTAMP";
    $params = [$status, $approved_by];
    $types = 'si';
    
    if (!empty($rejection_reason)) {
        $query .= ", rejection_reason = ?";
        $params[] = $rejection_reason;
        $types .= 's';
    }
    
    $query .= " WHERE leave_id = ?";
    $params[] = $leave_id;
    $types .= 'i';
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }
    
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Leave request updated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating leave request']);
    }
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>
    
} elseif ($method === 'PUT') {
    // Approve/Reject leave request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['leave_id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    $leave_id = (int)$data['leave_id'];
    $status = sanitize($data['status']);
    $rejection_reason = isset($data['rejection_reason']) ? sanitize($data['rejection_reason']) : '';
    $approved_by = $_SESSION['employee_id'] ?? null;
    
    $query = "UPDATE leave_requests 
              SET status = ?, approved_by = ?, approved_date = CURRENT_TIMESTAMP";
    $params = [$status, $approved_by];
    $types = 'si';
    
    if (!empty($rejection_reason)) {
        $query .= ", rejection_reason = ?";
        $params[] = $rejection_reason;
        $types .= 's';
    }
    
    $query .= " WHERE leave_id = ?";
    $params[] = $leave_id;
    $types .= 'i';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Leave request updated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating leave request']);
    }
}

$stmt->close();
$conn->close();
?>
