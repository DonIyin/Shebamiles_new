<?php
/**
 * Shebamiles - Create Attendance, Leave, and Performance Tables
 */

require_once 'config.php';

// Create attendance table
$sql = "CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'half_day', 'on_leave') DEFAULT 'absent',
    check_in TIME,
    check_out TIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    INDEX (employee_id),
    INDEX (date),
    INDEX (status),
    UNIQUE KEY unique_attendance (employee_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Attendance table created successfully<br>";
} else {
    echo "✗ Error creating attendance table: " . $conn->error . "<br>";
}

// Create leave_requests table
$sql = "CREATE TABLE IF NOT EXISTS leave_requests (
    leave_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type ENUM('annual', 'sick', 'personal', 'maternity', 'emergency', 'unpaid') DEFAULT 'annual',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    duration_days INT NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    approved_by INT,
    approved_date DATETIME,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES employees(employee_id) ON DELETE SET NULL,
    INDEX (employee_id),
    INDEX (status),
    INDEX (leave_type),
    INDEX (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Leave Requests table created successfully<br>";
} else {
    echo "✗ Error creating leave_requests table: " . $conn->error . "<br>";
}

// Create performance table
$sql = "CREATE TABLE IF NOT EXISTS performance (
    performance_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    review_period VARCHAR(50),
    reviewer_id INT NOT NULL,
    rating DECIMAL(3, 1) NOT NULL,
    productivity_score INT,
    quality_score INT,
    attendance_score INT,
    teamwork_score INT,
    communication_score INT,
    comments TEXT,
    strengths TEXT,
    areas_for_improvement TEXT,
    goals TEXT,
    review_date DATE NOT NULL,
    status ENUM('draft', 'submitted', 'acknowledged') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES employees(employee_id) ON DELETE RESTRICT,
    INDEX (employee_id),
    INDEX (review_date),
    INDEX (status),
    INDEX (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Performance table created successfully<br>";
} else {
    echo "✗ Error creating performance table: " . $conn->error . "<br>";
}

echo "<br><strong>✓ All tables created successfully!</strong><br>";
?>
