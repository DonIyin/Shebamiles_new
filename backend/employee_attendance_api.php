<?php
/**
 * Shebamiles - Employee Attendance API
 * Enables employees to clock in/out and view today's attendance status
 */

header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Admin can manage attendance for any employee; employee can only manage their own
$is_admin = $_SESSION['role'] === 'admin';
$user_id = $_SESSION['user_id'];
$employee_id = null;

if ($is_admin) {
    // Admin must provide employee_id via query parameter or request body
    $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    if (!$employee_id) {
        $data = json_decode(file_get_contents('php://input'), true);
        $employee_id = isset($data['employee_id']) ? (int)$data['employee_id'] : null;
    }
    if (!$employee_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'employee_id required for admin']);
        exit();
    }
} else {
    // Employee can only access their own attendance
    $employee = getEmployeeInfo($user_id);
    
    if (!$employee || empty($employee['employee_id'])) {
        // Employee record doesn't exist, create one if needed
        // First check if user exists in users table
        $userQuery = "SELECT user_id, first_name, last_name, email FROM users WHERE user_id = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param('i', $user_id);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userRow = $userResult->fetch_assoc();
        $userStmt->close();
        
        if ($userRow) {
            // Create minimal employee record
            $firstName = $userRow['first_name'] ?? 'Employee';
            $lastName = $userRow['last_name'] ?? '';
            $email = $userRow['email'];
            
            $createQuery = "INSERT INTO employees (user_id, first_name, last_name, email, status) VALUES (?, ?, ?, ?, 'active')";
            $createStmt = $conn->prepare($createQuery);
            $createStmt->bind_param('isss', $user_id, $firstName, $lastName, $email);
            $createStmt->execute();
            $employee_id = $conn->insert_id;
            $createStmt->close();
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }
    } else {
        $employee_id = (int)$employee['employee_id'];
    }
}

$today = date('Y-m-d');

function getTodayAttendance($conn, $employee_id, $date) {
    $query = "SELECT attendance_id, employee_id, date, status, check_in, check_out, notes FROM attendance WHERE employee_id = ? AND date = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('is', $employee_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();
    return $record ?: null;
}

function getDurationSeconds($date, $check_in, $check_out = null) {
    if (!$check_in) return 0;
    $start = strtotime($date . ' ' . $check_in);
    $end = $check_out ? strtotime($date . ' ' . $check_out) : time();
    if (!$start || !$end) return 0;
    $duration = $end - $start;
    return $duration > 0 ? $duration : 0;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $attendance = getTodayAttendance($conn, $employee_id, $today);
    $is_clocked_in = ($attendance && !empty($attendance['check_in']) && empty($attendance['check_out']));
    $duration_seconds = $attendance ? getDurationSeconds($today, $attendance['check_in'], $attendance['check_out']) : 0;

    echo json_encode([
        'success' => true,
        'attendance' => $attendance,
        'is_clocked_in' => $is_clocked_in,
        'duration_seconds' => $duration_seconds
    ]);
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = isset($data['action']) ? sanitize($data['action']) : '';

    if (!in_array($action, ['clock_in', 'clock_out'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }

    $attendance = getTodayAttendance($conn, $employee_id, $today);

    if ($action === 'clock_in') {
        if ($attendance && !empty($attendance['check_in']) && empty($attendance['check_out'])) {
            echo json_encode([
                'success' => true,
                'message' => 'You are already clocked in.',
                'attendance' => $attendance,
                'is_clocked_in' => true,
                'duration_seconds' => getDurationSeconds($today, $attendance['check_in'])
            ]);
            exit();
        }

        if ($attendance && !empty($attendance['check_out'])) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'You have already clocked out for today.']);
            exit();
        }

        $check_in = date('H:i:s');
        $status = 'present';

        $query = "INSERT INTO attendance (employee_id, date, status, check_in, check_out, notes)
                  VALUES (?, ?, ?, ?, NULL, '')
                  ON DUPLICATE KEY UPDATE
                  status = VALUES(status),
                  check_in = VALUES(check_in),
                  check_out = NULL,
                  updated_at = CURRENT_TIMESTAMP";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('isss', $employee_id, $today, $status, $check_in);
        $stmt->execute();
        $stmt->close();

        $attendance = getTodayAttendance($conn, $employee_id, $today);
        echo json_encode([
            'success' => true,
            'message' => 'Clock-in recorded.',
            'attendance' => $attendance,
            'is_clocked_in' => true,
            'duration_seconds' => getDurationSeconds($today, $check_in)
        ]);
        exit();
    }

    if ($action === 'clock_out') {
        if (!$attendance || empty($attendance['check_in'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You must clock in before clocking out.']);
            exit();
        }

        if (!empty($attendance['check_out'])) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'You have already clocked out for today.']);
            exit();
        }

        $check_out = date('H:i:s');

        $query = "UPDATE attendance
                  SET check_out = ?, status = 'present', updated_at = CURRENT_TIMESTAMP
                  WHERE employee_id = ? AND date = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sis', $check_out, $employee_id, $today);
        $stmt->execute();
        $stmt->close();

        $attendance = getTodayAttendance($conn, $employee_id, $today);
        echo json_encode([
            'success' => true,
            'message' => 'Clock-out recorded.',
            'attendance' => $attendance,
            'is_clocked_in' => false,
            'duration_seconds' => getDurationSeconds($today, $attendance['check_in'], $attendance['check_out'])
        ]);
        exit();
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
