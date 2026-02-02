<?php
/**
 * Shebamiles - Get Employees List
 */

header('Content-Type: application/json');

require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get query parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$department = isset($_GET['department']) ? sanitize($_GET['department']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Build query
$query = "SELECT user_id, email, username, first_name, last_name, phone, department, role, status, created_at, last_login 
          FROM users WHERE 1=1";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssss';
}

if (!empty($department)) {
    $query .= " AND department = ?";
    $params[] = $department;
    $types .= 's';
}

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= 's';
}

$query .= " ORDER BY first_name ASC, last_name ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = [
        'id' => $row['user_id'],
        'email' => $row['email'],
        'username' => $row['username'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'full_name' => $row['first_name'] . ' ' . $row['last_name'],
        'phone' => $row['phone'],
        'department' => $row['department'],
        'role' => $row['role'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'last_login' => $row['last_login']
    ];
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$countParams = [];
$countTypes = '';

if (!empty($search)) {
    $countQuery .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countTypes .= 'ssss';
}

if (!empty($department)) {
    $countQuery .= " AND department = ?";
    $countParams[] = $department;
    $countTypes .= 's';
}

if (!empty($status)) {
    $countQuery .= " AND status = ?";
    $countParams[] = $status;
    $countTypes .= 's';
}

$countStmt = $conn->prepare($countQuery);
if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalCount = $countResult->fetch_assoc()['total'];

// Get departments list
$deptQuery = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
$deptResult = $conn->query($deptQuery);
$departments = [];
while ($row = $deptResult->fetch_assoc()) {
    $departments[] = $row['department'];
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'employees' => $employees,
    'total' => $totalCount,
    'departments' => $departments,
    'filters' => [
        'search' => $search,
        'department' => $department,
        'status' => $status
    ]
]);

$stmt->close();
$conn->close();
?>
