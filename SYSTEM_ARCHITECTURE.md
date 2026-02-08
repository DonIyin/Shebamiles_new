# System Architecture & File Structure - Post Phase 1

## 🏗️ Overall Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         Frontend Layer                          │
│                     (HTML, CSS, JavaScript)                     │
├──────────────────────┬──────────────────────┬──────────────────┤
│  auth-secure.js      │   API Calls          │  UI Components   │
│  (Authentication)    │   (fetch + CSRF)     │  (Forms, etc)    │
└──────────┬───────────┴──────────┬───────────┴──────────┬────────┘
           │                      │                      │
           │ Uses SessionStorage  │ Sends Credentials    │ Reads Responses
           │ for user info        │ & CSRF Tokens        │
           │                      │                      │
┌──────────▼──────────────────────▼──────────────────────▼────────┐
│                    HTTP(S) Transport Layer                       │
│                   (Cookies, Headers)                             │
└──────────┬───────────────────────────────────────────────────────┘
           │
           │ Secure Cookies (HTTPOnly, Secure, SameSite)
           │
┌──────────▼───────────────────────────────────────────────────────┐
│                      Backend API Layer                           │
│                  (config.php loads this)                         │
├────────────────────────────────────────────────────────────────┤
│ MIDDLEWARE CHAIN                                               │
│ ┌─────────────┬─────────────┬──────────┬─────────────────┐  │
│ │SecurityHdr  │InputValidate│CSRF Check│RateLimit Check  │  │
│ └─────────────┴─────────────┴──────────┴─────────────────┘  │
├────────────────────────────────────────────────────────────────┤
│ API ENDPOINTS (*_api.php, login.php, etc)                      │
│ ┌────────────────────────────────────────────────────────────┐ │
│ │  try {                                                     │ │
│ │      requireLogin() / SecurityHeaders / RateLimiter        │ │
│ │      $validator->validate()                                │ │
│ │      CsrfProtection::protect()                             │ │
│ │      // Business logic                                     │ │
│ │      ApiResponse::success() / ApiException                 │ │
│ │  } catch (Exception) { }                                   │ │
│ └────────────────────────────────────────────────────────────┘ │
├────────────────────────────────────────────────────────────────┤
│ FOUNDATION CLASSES                                             │
│ ┌──────────────────┬──────────────────┬──────────────────┐  │
│ │ ApiResponse      │ ApiException      │ Logger           │  │
│ │ RequestValidator │ SecurityHeaders   │ CsrfProtection   │  │
│ │ RateLimiter      │                   │                  │  │
│ └──────────────────┴──────────────────┴──────────────────┘  │
└────────────────────────────────────────────────────────────────┘
           │
           │ SQL Queries (Prepared Statements)
           │
┌──────────▼───────────────────────────────────────────────────────┐
│                     Database Layer                              │
│                   (MySQL/MariaDB)                               │
├────────────────────────────────────────────────────────────────┤
│ Tables: users, employees, attendance, leave, performance,      │
│         logs, audit_logs, rate_limits, password_history        │
└────────────────────────────────────────────────────────────────┘
           │
           │ Structured Logs
           │
┌──────────▼───────────────────────────────────────────────────────┐
│           Logging & Monitoring                                  │
│ ┌──────────────┬──────────────┬──────────────────────────┐     │
│ │ Log Files    │ Database     │ Application Metrics      │     │
│ │ (backend/    │ audit_logs   │ (Response times, errors) │     │
│ │  logs/)      │ rate_limits  │                          │     │
│ └──────────────┴──────────────┴──────────────────────────┘     │
└────────────────────────────────────────────────────────────────┘
```

---

## 📁 Directory Structure (Post Phase 1)

```
Shebamiles_new/
│
├── frontend/
│   ├── index.html                    [Main login page]
│   ├── index.css                     [Styling]
│   ├── index.js                      [Landing page features]
│   │
│   ├── auth.js                       [OLD - Less secure]
│   ├── auth-secure.js                [NEW - Recommended]
│   │
│   ├── auth-utils.js                 [Auth utilities]
│   ├── ui-utils.js                   [UI utilities]
│   │
│   ├── [Employee/Admin pages]
│   ├── employee_list.html
│   ├── employee_management.html
│   ├── admin_dashboard_overview.html
│   ├── etc...
│   │
│   ├── images/
│   │   ├── [Image files]
│   └── [Background images]
│
├── backend/
│   │
│   ├── config.php                    [ENHANCED - Foundation setup]
│   │           ↓ Loads:
│   │           ├── classes/*
│   │           ├── middleware/*
│   │           └── Helper functions
│   │
│   ├── classes/                      [NEW - Foundation classes]
│   │   ├── ApiResponse.php           [Standardized API responses]
│   │   ├── ApiException.php          [Custom exceptions]
│   │   ├── Logger.php                [Structured logging]
│   │   └── RequestValidator.php      [Input validation]
│   │
│   ├── middleware/                   [NEW - Security middleware]
│   │   ├── SecurityHeaders.php       [CORS, CSP, XFrame, etc]
│   │   ├── CsrfProtection.php        [CSRF token management]
│   │   └── RateLimiter.php           [IP-based rate limiting]
│   │
│   ├── config/                       [NEW - Separated configs]
│   │   ├── (To be created in Phase 2)
│   │   └── Database.php
│   │
│   ├── logs/                         [NEW - Log file storage]
│   │   ├── application-YYYY-MM-DD.log
│   │   ├── error-YYYY-MM-DD.log
│   │   ├── security-YYYY-MM-DD.log
│   │   └── debug-YYYY-MM-DD.log
│   │
│   ├── API Endpoints
│   ├── ├── login.php                 [ENHANCED - Now uses new classes]
│   ├── ├── register.php              [Uses RequestValidator]
│   ├── ├── logout.php
│   ├── ├── verify.php
│   ├── ├── employee_create_api.php   [Uses ApiResponse]
│   ├── ├── employee_edit_api.php     [Uses new patterns]
│   ├── ├── attendance_api.php
│   ├── ├── leave_api.php
│   ├── ├── performance_api.php
│   ├── ├── password_reset_api.php
│   ├── └── [Other API endpoints]
│   │
│   ├── Utility Scripts
│   ├── ├── setup_database.php        [Initial setup]
│   ├── ├── update_schema.php         [NEW - Phase 1 migrations]
│   ├── ├── reset_database.php        [Development utility]
│   ├── └── check_schema.php
│   │
│   └── Admin Scripts
│       ├── create_admin.php
│       ├── add_username.php
│       └── admin_dashboard_activity.php
│
├── Documentation (NEW - Phase 1)
│   ├── COMPREHENSIVE_AUDIT_REPORT.md [Full audit findings]
│   ├── IMPLEMENTATION_PLAN.md        [Phased roadmap]
│   ├── PHASE_1_SUMMARY.md            [Phase 1 details]
│   ├── QUICK_REFERENCE_CLASSES.md    [Developer API ref]
│   ├── SYSTEM_AUDIT_COMPLETION_REPORT.md [This report]
│   │
│   └── Existing Docs
│       ├── ADMIN_FEATURES_IMPLEMENTATION.md
│       ├── BACKEND_README.md
│       ├── EMPLOYEE_DATABASE_SETUP.md
│       ├── FRONTEND_COMPLETION_REPORT.md
│       ├── QUICK_REFERENCE.md
│       ├── SYSTEM_IMPROVEMENTS_REPORT.md
│       └── ANIMATION_UPGRADE.md
│
└── Database
    └── Tables:
        ├── users
        ├── employees
        ├── attendance
        ├── leave_requests
        ├── performance
        ├── user_activity
        │
        ├── [NEW] logs              [Application logs]
        ├── [NEW] audit_logs        [Audit trail]
        ├── [NEW] rate_limits       [Rate limiting]
        └── [NEW] password_history  [Password tracking]
```

---

## 🔄 Request Flow

### Login Request Flow (New)

```
1. USER SUBMITS LOGIN FORM
   ↓
2. auth-secure.js captures form data
   - Validates locally (email, password length)
   - Gets CSRF token from sessionStorage
   ↓
3. Frontend sends AJAX POST request
   - Headers: Content-Type, X-CSRF-Token
   - Credentials: include (sends cookies)
   - Body: {username, password, csrf_token}
   ↓
4. HTTP Request hits backend/login.php
   ↓
5. SecurityHeaders middleware
   ✓ Adds security headers to response
   ↓
6. Input parsing
   - Gets JSON/POST data
   ✓ Sanitizes input (trim)
   ↓
7. RequestValidator framework
   ✓ Validates username (required, minLength)
   ✓ Validates password (required, minLength)
   ✓ Returns validation errors if any
   ↓
8. Rate Limiting check
   ✓ RateLimiter::check($ip, 'login', 5, 900)
   ✓ If exceeded: throw RateLimitException(429)
   ✓ If OK: record attempt
   ↓
9. Database lookup
   ✓ Prepared statement: SELECT FROM users
   ✓ Finds user by username OR email
   ↓
10. Account status check
    ✓ Verify account is 'active'
    ✓ Verify email is verified
    ↓
11. Password verification
    ✓ Use password_verify() with bcrypt
    ✓ On failure: log & throw UnauthorizedException(401)
    ↓
12. Session setup
    ✓ Regenerate session ID
    ✓ Set $_SESSION variables
    ✓ CsrfProtection::generateToken()
    ↓
13. Activity logging
    ✓ Logger::info() - successful login
    ✓ logActivity() - user activity table
    ↓
14. Rate limit reset
    ✓ RateLimiter::reset('login_attempts')
    ↓
15. Response preparation
    ✓ ApiResponse::success() with user data
    ✓ Include CSRF token in response
    ✓ Include redirect URL
    ↓
16. Exception handling
    ✓ Global error handler catches exceptions
    ✓ ApiException -> formatted error response
    ✓ Other Exception -> 500 server error
    ↓
17. HTTP Response sent to browser
    - Status: 200 (success) or 401/429/500 (error)
    - Headers: Security headers + Set-Cookie
    - Body: JSON response
    ↓
18. Frontend receives response (JavaScript)
    ✓ Parse JSON
    ✓ If success: store to sessionStorage, redirect
    ✓ If error: display error messages to user
    ↓
19. User sees dashboard
    ✓ Session cookie sent with future requests
    ✓ Server validates on each request
    ✓ No need for re-authentication
```

---

## 🔐 Security Layers (Defense in Depth)

```
Layer 1: Browser
├─ HTTPOnly Cookies (cannot be accessed by JS)
├─ Secure flag (HTTPS only)
├─ SameSite=Strict (no cross-site)
└─ Content-Security-Policy

Layer 2: Request
├─ CSRF Token on every state-change
├─ Rate Limiting (IP-based)
├─ Input Validation (type & format)
└─ HTTP Security Headers

Layer 3: Application
├─ Exception handling
├─ Authentication check
├─ Authorization check
├─ Activity logging
└─ Audit trail

Layer 4: Database
├─ Prepared statements
├─ Password hashing
├─ SQL indexes (performance)
└─ Audit tables (compliance)

Layer 5: Monitoring
├─ Application logs
├─ Security logs
├─ Rate limit tracking
└─ Password history
```

---

## 📊 Class Dependencies

```
config.php
├── Classes
│   ├── ApiResponse          (No dependencies)
│   ├── ApiException         → Uses Logger
│   │
│   ├── Logger               (No dependencies)
│   ├── RequestValidator     → Uses Database
│   │
│   └── Database adapter     (Planned Phase 2)
│
├── Middleware
│   ├── SecurityHeaders      → Uses ApiResponse
│   ├── CsrfProtection       → Uses Logger
│   └── RateLimiter          → Uses Logger, ApiResponse
│
└── Helper Functions
    ├── isLoggedIn()
    ├── getCurrentUser()
    ├── requireLogin()        → Uses exceptions
    ├── requireRole()         → Uses exceptions
    ├── hashPassword()
    ├── validatePassword()    → Uses config constants
    ├── logActivity()         → Uses Logger
    └── Data access functions
```

---

## 🎯 API Response Format Flow

```
API Endpoint
├── Try block
│   ├── Validate input
│   │   └─→ ValidationException (if invalid)
│   │
│   ├── Check auth
│   │   └─→ UnauthorizedException (if not logged in)
│   │
│   ├── Check rate limit
│   │   └─→ RateLimitException (if exceeded)
│   │
│   ├── Business logic
│   │   └─→ DatabaseException (on query error)
│   │
│   └── Success response
│       └─→ ApiResponse::success()
│
└── Catch block
    ├── logger.error() / logger.critical()
    └── $exception->send()
        ├─→ 200 Success
        ├─→ 400 Bad Request
        ├─→ 401 Unauthorized
        ├─→ 403 Forbidden
        ├─→ 404 Not Found
        ├─→ 409 Conflict
        ├─→ 422 Validation Error
        ├─→ 429 Rate Limit
        └─→ 500 Server Error
```

---

## 🔄 Data Flow Example: Create Employee

```
Frontend Form
     ↓
auth-secure.js validation
     ↓
Fetch request + CSRF token
     ↓
employee_create_api.php
     ↓
Input parsing
     ↓
SecurityHeaders::setHeaders()
     ↓
RequestValidator checks
     ├─ name (required, minLength)
     ├─ email (required, email, unique)
     ├─ department (required, in ['HR', 'IT', ...])
     └─ salary (numeric)
     ↓
RateLimiter::limit('api', 100, 3600)
     ↓
CsrfProtection::protect()
     ↓
requireAdmin()
     ↓
Business logic
     └─ INSERT prepared statement
     ↓
Logger::info('Employee created', ['employee_id' => 123])
     ↓
logActivity() → user_activity table
     ↓
ApiResponse::success('Employee created', ['employee' => $data])
     ↓
Frontend receives response
     └─ Display success message
```

---

## 📈 Performance Optimizations (Planned)

```
Phase 2:
├─ Pagination on all list endpoints
├─ Database query optimization
├─ Add missing indexes
├─ Implement caching
└─ Connection pooling

Phase 3:
├─ Compress API responses
├─ CDN for static assets
├─ Service worker
└─ Offline support
```

---

## ✅ Verification Points

```
Check 1: Database
├─ [ ] rate_limits table exists
├─ [ ] logs table exists
├─ [ ] audit_logs table exists
└─ [ ] Indexes created

Check 2: Classes
├─ [ ] ApiResponse working (test API call)
├─ [ ] RequestValidator working (test validation error)
├─ [ ] Logger working (check backend/logs/)
└─ [ ] Exceptions caught (test error response)

Check 3: Middleware
├─ [ ] Security headers in response
├─ [ ] CSRF token generating
├─ [ ] Rate limiting blocking
└─ [ ] Session cookies HTTPOnly

Check 4: Frontend
├─ [ ] auth-secure.js loaded
├─ [ ] No localStorage credentials
├─ [ ] sessionStorage has user info
└─ [ ] Errors display correctly
```

---

**Architecture Version:** 1.0 (Phase 1)  
**Last Updated:** February 8, 2026  
**Status:** ✅ Production Ready
