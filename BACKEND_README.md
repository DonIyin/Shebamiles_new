# Shebamiles Backend Structure

## Directory Organization

All PHP backend files are now organized in the `/backend/` subdirectory:

```
Shebamiles_new/
├── backend/
│   ├── config.php                 # Database config & helper functions
│   ├── setup_database.php         # Initial database setup (creates schema only)
│   ├── reset_database.php         # Full reset with schema and admin user
│   ├── create_admin.php           # Create default admin user
│   ├── login.php                  # Login handler
│   ├── register.php               # Registration handler
│   ├── logout.php                 # Logout handler
│   ├── verify.php                 # Email verification handler
│   └── check_schema.php           # Debug script to check table structure
├── auth.js                         # Frontend authentication module
├── index.html                      # Login/homepage
├── signup.html                     # Registration page
├── index.js                        # UI animations and interactions
├── index.css                       # Styles and animations
└── [dashboard pages...]            # Employee/admin dashboards
```

## Setup Instructions

### 1. Database Initialization

Run ONE of these scripts:

**Option A - Quick Setup (Recommended):**
```bash
php backend/reset_database.php
```
This creates the database, tables, AND the default admin user in one go.

**Option B - Manual Database Setup:**
```bash
php backend/setup_database.php
php backend/create_admin.php
```

### 2. Default Admin Credentials

After running any setup script:
- **Email:** admin@shebamiles.com
- **Password:** Admin@123456

### 3. Frontend Points to Backend

The JavaScript files automatically point to the backend directory:
- `auth.js` fetches from `backend/login.php`, `backend/register.php`, etc.
- `index.html` and `signup.html` include `auth.js` which handles everything
- All frontend paths are relative to the root directory

## Database Schema

### Users Table
- `user_id` (PRIMARY KEY, auto-increment)
- `email` (UNIQUE, indexed)
- `password` (bcrypt hash)
- `first_name`, `last_name`
- `phone`, `department`
- `role` (admin/manager/employee)
- `status` (active/inactive/suspended)
- `is_verified`, `verification_token`
- `password_reset_token`, `password_reset_expires`
- `last_login`
- `created_at`, `updated_at` (timestamps)

### User Activity Table
- Logs all user actions (login, logout, email verification, etc.)
- Foreign key reference to users table

### Login Attempts Table
- Tracks failed login attempts for brute force protection
- Limits to 5 attempts per 15 minutes

## API Endpoints

All endpoints expect JSON and return JSON:

### POST `/backend/login.php`
```json
{
  "email": "user@example.com",
  "password": "password123",
  "remember": true
}
```

### POST `/backend/register.php`
```json
{
  "email": "user@example.com",
  "password": "Password@123",
  "confirm_password": "Password@123",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890",
  "department": "Engineering"
}
```

### GET `/backend/logout.php`
Destroys session and clears cookies, redirects to index.html

### GET `/backend/verify.php?token=xxx`
Verifies email address via verification token

## Security Features

✓ Bcrypt password hashing (cost 12)
✓ Brute force protection (5 attempts, 15-min lockout)
✓ SQL injection prevention (prepared statements)
✓ Email validation
✓ Password strength requirements:
  - Minimum 8 characters
  - Must include uppercase, lowercase, number, special character
✓ Session management with configurable timeout (1 hour default)
✓ Activity logging for audit trail
✓ Email verification tokens
✓ IP address tracking

## Frontend Authentication Flow

1. User enters credentials on `index.html` or `signup.html`
2. Form submitted → handled by `auth.js`
3. `auth.js` sends JSON to backend PHP endpoint
4. Backend validates, hashes password, stores in MySQL
5. Response sent back as JSON
6. `auth.js` stores user data in `localStorage`
7. User redirected to dashboard (role-based)

## Configuration

Edit `/backend/config.php` to change:
- Database host, user, password, database name
- Session timeout duration
- Password minimum length
- Max login attempts and lockout time
- Base URL for redirects

## Debug Scripts

### Check Database Schema
```bash
php backend/check_schema.php
```
Shows all columns in the users table.

### Manual Admin Creation
```bash
php backend/create_admin.php
```
Creates admin user if setup failed.

## Notes

- All PHP files in the backend folder can be accessed only when properly configured
- Frontend files (HTML, JS, CSS) remain in the root directory
- All API calls from frontend use relative paths (`backend/login.php`, etc.)
- Sessions are stored server-side; localStorage is used for client-side user state
