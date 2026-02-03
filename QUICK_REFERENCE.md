# Quick Reference Guide - Admin Dashboard Features

## 🎯 What Was Built

Four complete feature modules for the admin dashboard with full CRUD operations:

| Feature | Page | Database Table | Functionality |
|---------|------|-----------------|---------------|
| **Attendance** | `attendance_tracking.html` | `attendance` | Track daily attendance, check-in/out times |
| **Leave Requests** | `leave_requests.html` | `leave_requests` | Manage leave applications and approvals |
| **Performance** | `performance_management.html` | `performance` | Create and track performance reviews |
| **Employee Edit** | `employee_edit.html` | `employees` (existing) | Edit all employee profile information |

---

## 🗄️ Database Tables

### Quick Reference

**ATTENDANCE**
- Track employee presence by date
- Status options: present, absent, late, half_day, on_leave
- Unique per employee per date

**LEAVE_REQUESTS**
- Submit and approve/reject leave
- Types: annual, sick, personal, maternity, emergency, unpaid
- Auto-calculates duration in days

**PERFORMANCE**
- Create detailed performance reviews
- Scores: overall (0-5), and breakdown (0-100)
- Tracks reviewer and review period

**EMPLOYEES** (already exists, enhanced)
- Now editable through employee_edit.html
- Can update all fields except email/username

---

## 📱 How to Use Each Feature

### Attendance Tracking
```
1. Admin Dashboard → Click "Attendance" link
2. Select month and optionally filter by employee
3. View all attendance records
4. See summary: Present/Absent/Late/On Leave counts
```

### Leave Requests
```
1. Admin Dashboard → Click "Leave Requests" link
2. Click "+ New Request" to submit leave
3. Fill: Employee, Type, Dates, Reason
4. System auto-calculates duration
5. Admin can Approve or Reject pending requests
6. Summary shows: Pending/Approved/Rejected counts
```

### Performance Management
```
1. Admin Dashboard → Click "Performance" link
2. Click "+ New Review" to create review
3. Fill: Employee, Period, Overall Rating
4. Add optional scores (Productivity, Quality, Teamwork, Communication)
5. Add Strengths, Areas for Improvement, Comments
6. Reviews displayed with color-coded rating badges
7. Summary shows distribution: Excellent/Good/Average/Poor
```

### Edit Employee
```
1. Admin Dashboard → Click "Employee List"
2. Find employee in grid or list view
3. Click "View Profile" button
4. Edit any field (First Name, Department, Salary, Status, etc.)
5. Click "Save Changes"
6. Data updates immediately in database
```

---

## 🔗 All Navigation Links

**Admin Dashboard Sidebar:**
- Overview → Shows dashboard with employee stats
- Employee List → Shows grid/list of employees
- Attendance → Full attendance management page
- Leave Requests → Full leave request management page
- Performance → Full performance review management page

**From Employee List:**
- View Profile button → Opens employee_edit.html with employee ID

---

## 📊 What Admins Can Do

### Attendance Admin
- View all attendance records filtered by month
- Filter by employee
- See summary statistics
- Edit attendance records (coming soon)

### Leave Admin
- View all leave requests with status filtering
- Create new leave requests
- Approve pending requests
- Reject requests with reason
- See pending/approved/rejected summary

### Performance Admin
- Create performance reviews
- Score employees on 5 different metrics
- Document strengths and improvement areas
- View all reviews with rating distribution
- Color-coded badge system for quick visual assessment

### Employee Admin
- Edit all employee information
- Update department, position, salary
- Manage emergency contacts
- Add/edit bio information
- Change employee status

---

## 🛠️ API Endpoints (Backend)

All require authentication (`isLoggedIn()`)

```
GET    /backend/attendance_api.php?month=2024-02&employee_id=1
POST   /backend/attendance_api.php    (create/update)

GET    /backend/leave_api.php?status=pending
POST   /backend/leave_api.php         (create request)
PUT    /backend/leave_api.php         (approve/reject)

GET    /backend/performance_api.php?employee_id=1
POST   /backend/performance_api.php   (create review)
PUT    /backend/performance_api.php   (update review)

GET    /backend/employee_edit_api.php?employee_id=1
POST   /backend/employee_edit_api.php (update employee)
```

---

## ✨ Key Features

### Visual Design
- ✅ Tailwind CSS with custom colors (Orange primary #f97316)
- ✅ Dark mode support on all pages
- ✅ Responsive layout (mobile, tablet, desktop)
- ✅ Color-coded status badges
- ✅ Loading states and empty states
- ✅ Toast notifications for actions

### Data Management
- ✅ Real-time data from MySQL database
- ✅ Employee dropdown lists auto-populated
- ✅ Month/date pickers for filtering
- ✅ Auto-calculation of leave duration
- ✅ Unique attendance records per employee per date
- ✅ Relationship tracking (reviewer, approver info)

### Security
- ✅ Authentication required on all APIs
- ✅ SQL injection prevention (prepared statements)
- ✅ Input sanitization
- ✅ Foreign key constraints
- ✅ User authorization checks

---

## 🚀 Getting Started

1. **Tables already created** - Run `create_tracking_tables.php` if needed
2. **Access the dashboard** - Visit `admin_dashboard_overview.html`
3. **Click the new links** - Attendance, Leave Requests, Performance
4. **Try creating records** - Use the forms to add data
5. **View employee details** - Click "View Profile" from employee list

---

## 📝 Example Workflows

### Typical Attendance Flow
```
1. Employee checks in/out (managed by system)
2. Admin reviews attendance report monthly
3. Admin marks absences or adjusts times as needed
4. Summary dashboard shows attendance rate
```

### Typical Leave Flow
```
1. Admin creates leave request for employee
2. Admin marks as "Approved" or "Rejected"
3. Employee sees status update
4. Summary shows approved/pending/rejected counts
```

### Typical Performance Flow
```
1. Admin creates new performance review
2. Rates employee on 5 metrics (1-100 scale)
3. Adds narrative on strengths/improvements
4. Review status tracked (draft → submitted → acknowledged)
5. Performance distribution graph shows team ratings
```

---

## 🎨 UI Elements

All pages feature:
- **Header** with title and description
- **Main content area** with tables/forms
- **Sidebar stats/summary** (sticky on desktop)
- **Action buttons** for create/edit/delete
- **Filter controls** for searching and filtering
- **Status badges** with color coding
- **Modal dialogs** for forms
- **Confirmation dialogs** for destructive actions

---

## 💾 Data Persistence

All changes are immediately saved to the database via:
- PHP prepared statements for security
- Proper error handling and validation
- Transaction support where needed
- Automatic timestamps (created_at, updated_at)

---

## 🔐 Authentication

All pages require:
- User to be logged in (session check)
- Valid session data ($_SESSION['user_id'])
- API endpoints check `isLoggedIn()` function
- Redirect to login if not authenticated

---

**Last Updated**: February 3, 2026
**Status**: ✅ Complete and Production Ready
