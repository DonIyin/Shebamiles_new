<?php
/**
 * Shebamiles - Employee Create/Update API
 * Saves employee profile with all additional fields
 */

header('Content-Type: application/json');

require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Verify admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

$action = isset($_POST['action']) ? sanitize($_POST['action']) : 'create';
$employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;

if ($action === 'update' && !$employee_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Employee ID required for update']);
    exit();
}

$first_name = isset($_POST['first_name']) ? sanitize($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? sanitize($_POST['last_name']) : '';
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
$employee_code = isset($_POST['employee_code']) ? sanitize($_POST['employee_code']) : '';
$department = isset($_POST['department']) ? sanitize($_POST['department']) : '';
$position = isset($_POST['position']) ? sanitize($_POST['position']) : '';
$salary = isset($_POST['salary']) ? floatval($_POST['salary']) : 0;
$hire_date = isset($_POST['hire_date']) ? sanitize($_POST['hire_date']) : '';
$status = isset($_POST['status']) ? sanitize($_POST['status']) : 'active';
$address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
$city = isset($_POST['city']) ? sanitize($_POST['city']) : '';
$state = isset($_POST['state']) ? sanitize($_POST['state']) : '';
$postal_code = isset($_POST['postal_code']) ? sanitize($_POST['postal_code']) : '';
$country = isset($_POST['country']) ? sanitize($_POST['country']) : '';
$emergency_contact_name = isset($_POST['emergency_contact_name']) ? sanitize($_POST['emergency_contact_name']) : '';
$emergency_contact_relation = isset($_POST['emergency_contact_relation']) ? sanitize($_POST['emergency_contact_relation']) : '';
$emergency_contact_phone = isset($_POST['emergency_contact_phone']) ? sanitize($_POST['emergency_contact_phone']) : '';
$bio = isset($_POST['bio']) ? sanitize($_POST['bio']) : '';

// Validation
if (empty($first_name) || empty($last_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'First and last name required']);
    exit();
}

if (empty($department) || empty($position)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Department and position required']);
    exit();
}

if ($action === 'update') {
    // Update existing employee
    $query = "UPDATE employees SET 
              first_name = ?,
              last_name = ?,
              email = ?,
              phone = ?,
              department = ?,
              position = ?,
              salary = ?,
              hire_date = ?,
              status = ?,
              address = ?,
              city = ?,
              state = ?,
              postal_code = ?,
              country = ?,
              emergency_contact_name = ?,
              emergency_contact_relation = ?,
              emergency_contact_phone = ?,
              bio = ?
              WHERE employee_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssssdssssssssssi', 
        $first_name, $last_name, $email, $phone, $department, $position, 
        $salary, $hire_date, $status, $address, $city, $state, $postal_code, 
        $country, $emergency_contact_name, $emergency_contact_relation, 
        $emergency_contact_phone, $bio, $employee_id);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
        exit();
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Employee updated successfully',
        'employee_id' => $employee_id
    ]);
} else {
    // Create new employee
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required for new employee']);
        exit();
    }
    
    // Check if employee already exists for this user
    $check_query = "SELECT employee_id FROM employees WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('i', $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee record already exists for this user']);
        exit();
    }
    
    $query = "INSERT INTO employees (
              user_id, first_name, last_name, email, phone, employee_code,
              department, position, salary, hire_date, status, address, city,
              state, postal_code, country, emergency_contact_name,
              emergency_contact_relation, emergency_contact_phone, bio
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isssssssdsssssssss',
        $user_id, $first_name, $last_name, $email, $phone, $employee_code,
        $department, $position, $salary, $hire_date, $status, $address, $city,
        $state, $postal_code, $country, $emergency_contact_name,
        $emergency_contact_relation, $emergency_contact_phone, $bio);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Creation failed: ' . $conn->error]);
        exit();
    }
    
    $new_employee_id = $stmt->insert_id;
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Employee created successfully',
        'employee_id' => $new_employee_id
    ]);
}

$stmt->close();
?>