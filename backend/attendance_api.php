<?php
/**
 * Shebamiles - Get Attendance Records
 */

header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only admins can view/edit attendance
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Check if fetching a single record by ID
    if (isset($_GET['id'])) {
        $attendance_id = (int)$_GET['id'];
        
        $query = "SELECT a.*, e.first_name, e.last_name, e.employee_code 
                  FROM attendance a
                  JOIN employees e ON a.employee_id = e.employee_id
                  WHERE a.attendance_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $attendance_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        
        if ($record) {
            http_response_code(200);
            echo json_encode(['success' => true, 'record' => $record]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Record not found']);
        }
        $stmt->close();
        $conn->close();
        exit();
    }
    
    // Get attendance records by month
    $month = isset($_GET['month']) ? sanitize($_GET['month']) : date('Y-m');
    $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    
    // Validate month format
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid month format (use YYYY-MM)']);
        exit();
    }
    
    $query = "SELECT a.*, e.first_name, e.last_name, e.employee_code 
              FROM attendance a
              JOIN employees e ON a.employee_id = e.employee_id
              WHERE DATE_FORMAT(a.date, '%Y-%m') = ? ";
    
    $params = [$month];
    $types = 's';
    
    if ($employee_id) {
        $query .= " AND a.employee_id = ?";
        $params[] = $employee_id;
        $types .= 'i';
    }
    
    $query .= " ORDER BY a.date DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $records = [];
    
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'records' => $records,
        'month' => $month,
        'count' => count($records)
    ]);
    
} elseif ($method === 'POST') {
    // Add/Update attendance record
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Handle update by attendance_id (from edit modal)
    if (isset($data['attendance_id'])) {
        $attendance_id = (int)$data['attendance_id'];
        $status = isset($data['status']) ? sanitize($data['status']) : null;
        $check_in = isset($data['check_in']) ? sanitize($data['check_in']) : null;
        $check_out = isset($data['check_out']) ? sanitize($data['check_out']) : null;
        $notes = isset($data['notes']) ? sanitize($data['notes']) : '';
        
        if (!$status) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Status is required']);
            exit();
        }
        
        // Validate status
        $valid_statuses = ['present', 'absent', 'late', 'half_day', 'on_leave'];
        if (!in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit();
        }
        
        // Validate times if provided
        if ($check_in && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $check_in)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid check-in time format']);
            exit();
        }
        if ($check_out && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $check_out)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid check-out time format']);
            exit();
        }
        
        $query = "UPDATE attendance 
                  SET status = ?, check_in = ?, check_out = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE attendance_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssssi', $status, $check_in, $check_out, $notes, $attendance_id);
        
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Attendance record updated']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating record']);
        }
        $stmt->close();
        $conn->close();
        exit();
    }
    
    // Handle insert by employee_id and date
    if (!isset($data['employee_id']) || !isset($data['date']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    $employee_id = (int)$data['employee_id'];
    $date = sanitize($data['date']);
    $status = sanitize($data['status']);
    $check_in = isset($data['check_in']) ? sanitize($data['check_in']) : null;
    $check_out = isset($data['check_out']) ? sanitize($data['check_out']) : null;
    $notes = isset($data['notes']) ? sanitize($data['notes']) : '';
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid date format (use YYYY-MM-DD)']);
        exit();
    }
    
    // Validate status
    $valid_statuses = ['present', 'absent', 'late', 'half_day', 'on_leave'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }
    
    // Validate times if provided
    if ($check_in && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $check_in)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid check-in time format']);
        exit();
    }
    if ($check_out && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $check_out)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid check-out time format']);
        exit();
    }
    
    $query = "INSERT INTO attendance (employee_id, date, status, check_in, check_out, notes)
              VALUES (?, ?, ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE
              status = VALUES(status),
              check_in = VALUES(check_in),
              check_out = VALUES(check_out),
              notes = VALUES(notes),
              updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isssss', $employee_id, $date, $status, $check_in, $check_out, $notes);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Attendance record saved']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error saving record']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

if (isset($stmt)) $stmt->close();
$conn->close();
?>
