<?php
/**
 * Shebamiles - Performance API
 */

header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only admins can view/edit performance
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Check if fetching a single review by ID
    if (isset($_GET['id'])) {
        $performance_id = (int)$_GET['id'];
        
        $query = "SELECT p.*, e.first_name, e.last_name, e.employee_code,
                         reviewer.first_name as reviewer_first_name, reviewer.last_name as reviewer_last_name
                  FROM performance p
                  JOIN employees e ON p.employee_id = e.employee_id
                  LEFT JOIN employees reviewer ON p.reviewer_id = reviewer.employee_id
                  WHERE p.performance_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $performance_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $review = $result->fetch_assoc();
        
        if ($review) {
            http_response_code(200);
            echo json_encode(['success' => true, 'review' => $review]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Review not found']);
        }
        $stmt->close();
        $conn->close();
        exit();
    }
    
    // Get performance reviews
    $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    $period = isset($_GET['period']) ? sanitize($_GET['period']) : '';
    
    $query = "SELECT p.*, e.first_name, e.last_name, e.employee_code,
                     reviewer.first_name as reviewer_first_name, reviewer.last_name as reviewer_last_name
              FROM performance p
              JOIN employees e ON p.employee_id = e.employee_id
              LEFT JOIN employees reviewer ON p.reviewer_id = reviewer.employee_id
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($employee_id) {
        $query .= " AND p.employee_id = ?";
        $params[] = $employee_id;
        $types .= 'i';
    }
    
    if (!empty($period)) {
        $query .= " AND p.review_period = ?";
        $params[] = $period;
        $types .= 's';
    }
    
    $query .= " ORDER BY p.review_date DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = [];
    
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'count' => count($reviews)
    ]);
    
} elseif ($method === 'POST') {
    // Create or update performance review
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Handle update by performance_id
    if (isset($data['performance_id'])) {
        $performance_id = (int)$data['performance_id'];
        $rating = isset($data['rating']) ? (float)$data['rating'] : null;
        $review_period = isset($data['review_period']) ? sanitize($data['review_period']) : null;
        
        if (!$rating || !$review_period) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Rating and review period are required']);
            exit();
        }
        
        // Validate rating (0-5)
        if ($rating < 0 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Rating must be between 0 and 5']);
            exit();
        }
        
        $productivity_score = isset($data['productivity_score']) ? (int)$data['productivity_score'] : null;
        $quality_score = isset($data['quality_score']) ? (int)$data['quality_score'] : null;
        $teamwork_score = isset($data['teamwork_score']) ? (int)$data['teamwork_score'] : null;
        $communication_score = isset($data['communication_score']) ? (int)$data['communication_score'] : null;
        
        // Validate scores (0-100)
        $scores = [$productivity_score, $quality_score, $teamwork_score, $communication_score];
        foreach ($scores as $score) {
            if ($score !== null && ($score < 0 || $score > 100)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'All scores must be between 0 and 100']);
                exit();
            }
        }
        
        $comments = isset($data['comments']) ? sanitize($data['comments']) : '';
        $strengths = isset($data['strengths']) ? sanitize($data['strengths']) : '';
        $areas_for_improvement = isset($data['areas_for_improvement']) ? sanitize($data['areas_for_improvement']) : '';
        
        $query = "UPDATE performance 
                  SET review_period = ?, rating = ?, productivity_score = ?, quality_score = ?, 
                      teamwork_score = ?, communication_score = ?, comments = ?, 
                      strengths = ?, areas_for_improvement = ?
                  WHERE performance_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sdiiiiisssi', $review_period, $rating, $productivity_score, $quality_score,
                          $teamwork_score, $communication_score, $comments, $strengths,
                          $areas_for_improvement, $performance_id);
        
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Performance review updated']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating review']);
        }
        $stmt->close();
        $conn->close();
        exit();
    }
    
    // Handle create
    if (!isset($data['employee_id']) || !isset($data['rating']) || !isset($data['review_period'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    $employee_id = (int)$data['employee_id'];
    $rating = (float)$data['rating'];
    $review_period = sanitize($data['review_period']);
    
    // Validate rating (0-5)
    if ($rating < 0 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rating must be between 0 and 5']);
        exit();
    }
    
    $productivity_score = isset($data['productivity_score']) ? (int)$data['productivity_score'] : null;
    $quality_score = isset($data['quality_score']) ? (int)$data['quality_score'] : null;
    $attendance_score = isset($data['attendance_score']) ? (int)$data['attendance_score'] : null;
    $teamwork_score = isset($data['teamwork_score']) ? (int)$data['teamwork_score'] : null;
    $communication_score = isset($data['communication_score']) ? (int)$data['communication_score'] : null;
    
    // Validate all scores (0-100)
    $scores = [$productivity_score, $quality_score, $attendance_score, $teamwork_score, $communication_score];
    foreach ($scores as $score) {
        if ($score !== null && ($score < 0 || $score > 100)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All scores must be between 0 and 100']);
            exit();
        }
    }
    
    $comments = isset($data['comments']) ? sanitize($data['comments']) : '';
    $strengths = isset($data['strengths']) ? sanitize($data['strengths']) : '';
    $areas_for_improvement = isset($data['areas_for_improvement']) ? sanitize($data['areas_for_improvement']) : '';
    $goals = isset($data['goals']) ? sanitize($data['goals']) : '';
    
    // Get reviewer_id from current user
    $user_id = $_SESSION['user_id'];
    $reviewer_query = "SELECT employee_id FROM employees WHERE user_id = ?";
    $reviewer_stmt = $conn->prepare($reviewer_query);
    $reviewer_stmt->bind_param('i', $user_id);
    $reviewer_stmt->execute();
    $reviewer_result = $reviewer_stmt->get_result();
    $reviewer_row = $reviewer_result->fetch_assoc();
    $reviewer_id = $reviewer_row ? $reviewer_row['employee_id'] : null;
    $reviewer_stmt->close();
    
    if (!$reviewer_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Reviewer profile not found']);
        exit();
    }
    
    $query = "INSERT INTO performance 
              (employee_id, review_period, reviewer_id, rating, productivity_score, quality_score, 
               attendance_score, teamwork_score, communication_score, comments, strengths, 
               areas_for_improvement, goals, review_date, status)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'draft')";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param('isidiiiiissss', $employee_id, $review_period, $reviewer_id, $rating,
                      $productivity_score, $quality_score, $attendance_score, $teamwork_score,
                      $communication_score, $comments, $strengths, $areas_for_improvement, $goals);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Performance review created', 'review_id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error creating review']);
    }
    $stmt->close();
    
} elseif ($method === 'PUT') {
    // Update performance review
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['performance_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing performance_id']);
        exit();
    }
    
    $performance_id = (int)$data['performance_id'];
    $status = isset($data['status']) ? sanitize($data['status']) : '';
    
    // Validate status
    if (!empty($status)) {
        $valid_statuses = ['draft', 'submitted', 'acknowledged'];
        if (!in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit();
        }
    }
    
    $query = "UPDATE performance SET ";
    $params = [];
    $types = '';
    
    if (!empty($status)) {
        $query .= "status = ?, ";
        $params[] = $status;
        $types .= 's';
    }
    
    if (isset($data['rating'])) {
        $rating = (float)$data['rating'];
        if ($rating < 0 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Rating must be between 0 and 5']);
            exit();
        }
        $query .= "rating = ?, ";
        $params[] = $rating;
        $types .= 'd';
    }
    
    if (empty($params)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit();
    }
    
    $query = rtrim($query, ", ");
    $query .= " WHERE performance_id = ?";
    $params[] = $performance_id;
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
        echo json_encode(['success' => true, 'message' => 'Performance review updated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating review']);
    }
    $stmt->close();

} elseif ($method === 'DELETE') {
    // Delete performance review
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['performance_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing performance_id']);
        exit();
    }
    
    $performance_id = (int)$data['performance_id'];
    
    $query = "DELETE FROM performance WHERE performance_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $performance_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Performance review deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error deleting review']);
    }
    $stmt->close();

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>
