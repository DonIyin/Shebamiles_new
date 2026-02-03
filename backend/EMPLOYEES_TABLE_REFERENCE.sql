-- ========================================
-- Shebamiles - Employees Table Reference
-- ========================================

-- TABLE STRUCTURE
-- ==============

CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    employee_code VARCHAR(50) UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(100),
    position VARCHAR(100),
    salary DECIMAL(10, 2),
    hire_date DATE,
    manager_id INT,
    status ENUM('active', 'inactive', 'on_leave', 'suspended') DEFAULT 'active',
    bio TEXT,
    profile_picture VARCHAR(255),
    address VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relation VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES employees(employee_id) ON DELETE SET NULL,
    INDEX (user_id),
    INDEX (department),
    INDEX (status),
    INDEX (employee_code)
);

-- USEFUL QUERIES
-- ==============

-- View all employees with user details
SELECT 
    e.employee_id,
    e.first_name,
    e.last_name,
    e.email,
    e.department,
    e.position,
    e.status,
    u.username,
    u.role
FROM employees e
LEFT JOIN users u ON e.user_id = u.user_id
ORDER BY e.first_name, e.last_name;

-- Count employees by department
SELECT department, COUNT(*) as count
FROM employees
WHERE status = 'active'
GROUP BY department
ORDER BY count DESC;

-- Count employees by status
SELECT status, COUNT(*) as count
FROM employees
GROUP BY status
ORDER BY count DESC;

-- Find active employees in a specific department
SELECT *
FROM employees
WHERE department = 'Engineering' AND status = 'active'
ORDER BY first_name, last_name;

-- Get manager and subordinates
SELECT 
    m.first_name as manager_name,
    COUNT(e.employee_id) as subordinate_count
FROM employees m
LEFT JOIN employees e ON e.manager_id = m.employee_id
WHERE m.status = 'active'
GROUP BY m.employee_id;

-- Find employees hired in last 30 days
SELECT *
FROM employees
WHERE hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
ORDER BY hire_date DESC;

-- Search employees by name or email
SELECT *
FROM employees
WHERE first_name LIKE '%john%' 
   OR last_name LIKE '%smith%'
   OR email LIKE '%john%'
ORDER BY first_name, last_name;

-- Get employee count statistics
SELECT 
    COUNT(*) as total,
    SUM(IF(status = 'active', 1, 0)) as active,
    SUM(IF(status = 'inactive', 1, 0)) as inactive,
    SUM(IF(status = 'on_leave', 1, 0)) as on_leave,
    SUM(IF(status = 'suspended', 1, 0)) as suspended
FROM employees;

-- Update employee information
UPDATE employees
SET 
    position = 'Senior Engineer',
    salary = 75000.00,
    updated_at = CURRENT_TIMESTAMP
WHERE employee_id = 1;

-- Add employee (must have user_id from users table first)
INSERT INTO employees 
(user_id, employee_code, first_name, last_name, email, phone, department, position, hire_date, status)
VALUES 
(5, 'EMP001', 'John', 'Doe', 'john@example.com', '555-1234', 'Engineering', 'Software Engineer', CURDATE(), 'active');

-- API ENDPOINTS
-- =============

-- Get all employees (with filtering)
GET /backend/get_employees.php

-- Get employees with search
GET /backend/get_employees.php?search=john

-- Get employees by department
GET /backend/get_employees.php?department=Engineering

-- Get employees by status
GET /backend/get_employees.php?status=active

-- Get with pagination
GET /backend/get_employees.php?limit=20&offset=0

-- Combine filters
GET /backend/get_employees.php?search=john&department=Engineering&status=active

-- EXPECTED API RESPONSE
-- ====================

{
  "success": true,
  "employees": [
    {
      "id": 1,
      "user_id": 1,
      "email": "admin@example.com",
      "username": "admin",
      "first_name": "Admin",
      "last_name": "User",
      "full_name": "Admin User",
      "phone": "555-0000",
      "department": "Management",
      "position": null,
      "role": "admin",
      "status": "active",
      "hire_date": "2026-02-02",
      "profile_picture": null
    }
  ],
  "total": 4,
  "departments": ["Management", "Engineering", "Sales"],
  "filters": {
    "search": "",
    "department": "",
    "status": ""
  }
}

-- RELATIONS
-- =========

1. One-to-One: users ↔ employees
   - Each user can have one employee record
   - Uses user_id as foreign key

2. One-to-Many: employees → employees (Manager relationship)
   - One manager can have multiple subordinates
   - Uses manager_id as self-referencing foreign key

3. CASCADE Delete: When user is deleted, employee record is automatically deleted
4. SET NULL: When manager is deleted, subordinate's manager_id is set to NULL

-- INDEXES FOR PERFORMANCE
-- =======================

CREATE INDEX idx_user_id ON employees(user_id);
CREATE INDEX idx_department ON employees(department);
CREATE INDEX idx_status ON employees(status);
CREATE INDEX idx_employee_code ON employees(employee_code);

-- These indexes are already created in the table definition

-- HELPER FUNCTIONS (PHP - config.php)
-- ===================================

// Get single employee info
$employee = getEmployeeInfo($user_id);

// Get all employees
$employees = getAllEmployees($limit = 50, $offset = 0);

// Get employees by department
$dept_employees = getEmployeesByDepartment($department, $limit = 50, $offset = 0);

// Get total count
$count = getTotalEmployeeCount();

-- ADMIN DASHBOARD INTEGRATION
-- ============================

Dashboard cards now display:
✓ Total Employees (from employees table count)
✓ Active Employees (filtered by status = 'active')
✓ Departments (DISTINCT department values)
✓ Inactive Employees (filtered by status = 'inactive')

All statistics update dynamically when page loads via API call to /backend/get_employees.php
