<?php
/**
 * Shebamiles - Admin Dashboard Activity API
 * Provides recent activity and upcoming absences using real employee data.
 */

header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

// Note: Admin user may not be an employee in the database.
// Admin role grants full access to monitor, view, and edit all employees.

function timeAgoLabel($dateTime) {
    if (!$dateTime) return 'Just now';
    $now = new DateTime();
    $then = new DateTime($dateTime);
    $diff = $now->getTimestamp() - $then->getTimestamp();

    if ($diff < 60) return 'Just now';
    $minutes = floor($diff / 60);
    if ($minutes < 60) return $minutes . ' minutes ago';
    $hours = floor($minutes / 60);
    if ($hours < 24) return $hours . ' hours ago';
    $days = floor($hours / 24);
    if ($days === 1) return 'Yesterday';
    if ($days < 7) return $days . ' days ago';
    return $then->format('M j, Y');
}

function formatLeaveType($type) {
    if (!$type) return 'Leave';
    return ucwords(str_replace('_', ' ', $type));
}

$recent_activity = [];
$upcoming_absences = [];

// Recent activity: newest employee onboarded
$employeeQuery = "SELECT first_name, last_name, department, created_at
                  FROM employees
                  ORDER BY created_at DESC
                  LIMIT 1";
$employeeResult = $conn->query($employeeQuery);
if ($employeeResult && $employeeRow = $employeeResult->fetch_assoc()) {
    $recent_activity[] = [
        'icon' => 'person_add',
        'icon_class' => 'text-primary',
        'title' => 'New employee onboarded',
        'description' => trim($employeeRow['first_name'] . ' ' . $employeeRow['last_name']) .
            (empty($employeeRow['department']) ? '' : ' joined the ' . $employeeRow['department'] . ' Team'),
        'time_ago' => timeAgoLabel($employeeRow['created_at'])
    ];
}

// Recent activity: latest performance review submitted/acknowledged
$performanceQuery = "SELECT p.review_period, p.updated_at, p.created_at,
                            e.first_name, e.last_name
                     FROM performance p
                     JOIN employees e ON p.employee_id = e.employee_id
                     WHERE p.status IN ('submitted', 'acknowledged')
                     ORDER BY p.updated_at DESC, p.created_at DESC
                     LIMIT 1";
$performanceResult = $conn->query($performanceQuery);
if ($performanceResult && $performanceRow = $performanceResult->fetch_assoc()) {
    $period = $performanceRow['review_period'] ?: 'Performance Review';
    $recent_activity[] = [
        'icon' => 'verified',
        'icon_class' => 'text-emerald-600',
        'title' => 'Performance review completed',
        'description' => trim($performanceRow['first_name'] . ' ' . $performanceRow['last_name']) . ' - ' . $period,
        'time_ago' => timeAgoLabel($performanceRow['updated_at'] ?: $performanceRow['created_at'])
    ];
}

// Recent activity: latest pending leave request
$leaveQuery = "SELECT l.created_at, l.duration_days, l.leave_type,
                      e.first_name, e.last_name
               FROM leave_requests l
               JOIN employees e ON l.employee_id = e.employee_id
               WHERE l.status = 'pending'
               ORDER BY l.created_at DESC
               LIMIT 1";
$leaveResult = $conn->query($leaveQuery);
if ($leaveResult && $leaveRow = $leaveResult->fetch_assoc()) {
    $days = (int)$leaveRow['duration_days'];
    $recent_activity[] = [
        'icon' => 'request_quote',
        'icon_class' => 'text-amber-600',
        'title' => 'Leave request pending',
        'description' => trim($leaveRow['first_name'] . ' ' . $leaveRow['last_name']) .
            ' requested ' . ($days > 0 ? $days . ' day' . ($days > 1 ? 's' : '') : 'leave') .
            ' of ' . formatLeaveType($leaveRow['leave_type']),
        'time_ago' => timeAgoLabel($leaveRow['created_at'])
    ];
}

// Upcoming absences: approved leave starting today or later
$today = date('Y-m-d');
$absenceQuery = "SELECT l.start_date, l.end_date, l.leave_type,
                        e.first_name, e.last_name, e.department
                 FROM leave_requests l
                 JOIN employees e ON l.employee_id = e.employee_id
                 WHERE l.status = 'approved' AND l.start_date >= ?
                 ORDER BY l.start_date ASC
                 LIMIT 3";
$absenceStmt = $conn->prepare($absenceQuery);
$absenceStmt->bind_param('s', $today);
$absenceStmt->execute();
$absenceResult = $absenceStmt->get_result();

while ($row = $absenceResult->fetch_assoc()) {
    $start = (new DateTime($row['start_date']))->format('M j');
    $end = (new DateTime($row['end_date']))->format('M j');
    $range = $start === $end ? $start : $start . ' - ' . $end;

    $upcoming_absences[] = [
        'name' => trim($row['first_name'] . ' ' . $row['last_name']),
        'department' => $row['department'] ?: 'General',
        'date_range' => $range,
        'leave_type' => formatLeaveType($row['leave_type'])
    ];
}
$absenceStmt->close();

http_response_code(200);
echo json_encode([
    'success' => true,
    'recent_activity' => $recent_activity,
    'upcoming_absences' => $upcoming_absences
]);
$conn->close();
?>
