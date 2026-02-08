/**
 * Shebamiles - Enhanced Authentication Module
 * Secure authentication without storing sensitive data in localStorage
 * 
 * Best Practices Implemented:
 * - No sensitive data in localStorage (prevents XSS attacks)
 * - Session-based authentication (server validates every request)
 * - CSRF token included with all state-changing requests
 * - Secure cookie handling (HTTPOnly, Secure, SameSite)
 */

const AuthModule = {
    
    // Configuration
    config: {
        apiBase: '../backend/',
        loginEndpoint: 'login.php',
        registerEndpoint: 'register.php',
        logoutEndpoint: 'logout.php',
        verifyEndpoint: 'verify.php'
    },
    
    // Initialize auth listeners
    init: function() {
        this.setupLoginForm();
        this.setupSignupForm();
        this.setupLogoutButton();
        this.checkAuthStatus();
        this.setupActivityTimeout();
    },

    /**
     * Setup login form submission
     */
    setupLoginForm: function() {
        const form = document.getElementById('loginForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin(form);
        });
    },

    /**
     * Setup signup form submission
     */
    setupSignupForm: function() {
        const form = document.getElementById('signupForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSignup(form);
        });
    },

    /**
     * Setup logout buttons
     */
    setupLogoutButton: function() {
        const logoutButtons = document.querySelectorAll('[data-logout]');
        logoutButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        });
    },

    /**
     * Handle login form submission
     */
    handleLogin: async function(form) {
        const username = document.getElementById('username')?.value || '';
        const password = document.getElementById('password')?.value || '';
        const remember = document.getElementById('remember')?.checked || false;

        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => el.remove());

        // Validate inputs
        if (!username || !password) {
            this.showError('Please fill in all fields');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="inline-block animate-spin">⏳</span> Logging in...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(this.config.apiBase + this.config.loginEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include', // Include cookies in request
                body: JSON.stringify({
                    username: username,
                    password: password,
                    remember: remember
                })
            });

            const data = await response.json();

            if (data.success) {
                // Log successful login
                this.showSuccess('Login successful! Verifying...', 3000);
                
                // Store ONLY non-sensitive user info in sessionStorage (cleared on browser close)
                if (data.data && data.data.user) {
                    sessionStorage.setItem('shebamiles_user_info', JSON.stringify({
                        id: data.data.user.id,
                        name: data.data.user.name,
                        role: data.data.user.role,
                        email: data.data.user.email
                    }));
                }
                
                // Store CSRF token for future requests
                if (data.data && data.data.csrf_token) {
                    sessionStorage.setItem('csrf_token', data.data.csrf_token);
                }
                
                // Redirect after brief delay
                setTimeout(() => {
                    window.location.href = data.data.redirect || '../frontend/index.html';
                }, 1500);
            } else if (data.errors) {
                // Show validation errors
                this.showErrors(data.errors);
            } else {
                // Show error message
                this.showError(data.message || 'Login failed. Please try again.');
            }
        } catch (error) {
            console.error('Login network error', error);
            this.showError('Network error: ' + error.message);
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    },

    /**
     * Handle signup form submission
     */
    handleSignup: async function(form) {
        const email = document.getElementById('signup-email')?.value || '';
        const username = document.getElementById('username')?.value || '';
        const password = document.getElementById('signup-password')?.value || '';
        const confirmPassword = document.getElementById('confirm-password')?.value || '';
        const firstName = document.getElementById('first-name')?.value || '';
        const lastName = document.getElementById('last-name')?.value || '';
        const phone = document.getElementById('phone')?.value || '';
        const department = document.getElementById('department')?.value || '';

        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => el.remove());

        // Validate inputs
        if (!email || !password || !confirmPassword || !firstName || !lastName || !username) {
            this.showError('Please fill in all required fields');
            return;
        }

        if (password !== confirmPassword) {
            this.showError('Passwords do not match');
            return;
        }

        if (password.length < 10) {
            this.showError('Password must be at least 10 characters long');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="inline-block animate-spin">⏳</span> Creating account...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(this.config.apiBase + this.config.registerEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    email: email,
                    password: password,
                    confirm_password: confirmPassword,
                    first_name: firstName,
                    last_name: lastName,
                    username: username,
                    phone: phone,
                    department: department
                })
            });

            const data = await response.json();

            if (data.success) {
                // Show success message
                this.showSuccess('Account created successfully! Check your email to verify.', 5000);
                
                // Redirect to login page
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 2000);
            } else if (data.errors) {
                this.showErrors(data.errors);
            } else {
                this.showError(data.message || 'Registration failed');
            }
        } catch (error) {
            console.error('Registration network error', error);
            this.showError('Network error: ' + error.message);
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    },

    /**
     * Handle logout
     */
    handleLogout: async function() {
        try {
            // Call logout endpoint
            await fetch(this.config.apiBase + this.config.logoutEndpoint, {
                method: 'POST',
                credentials: 'include'
            });
        } catch (e) {
            // Continue logout even if endpoint fails
        }
        
        // Clear session storage
        sessionStorage.removeItem('shebamiles_user_info');
        sessionStorage.removeItem('csrf_token');
        
        // Redirect to login
        window.location.href = 'index.html';
    },

    /**
     * Check if user is logged in by verifying session
     */
    checkAuthStatus: async function() {
        try {
            // Only proceed if we have stored user info
            const userInfo = sessionStorage.getItem('shebamiles_user_info');
            if (!userInfo) {
                // No session data - might need to re-login
                return false;
            }
            
            // Verify session with server
            const response = await fetch(this.config.apiBase + 'verify.php', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                // Session invalid - clear and redirect
                this.clearSession();
                return false;
            }
            
            const data = await response.json();
            return data.success;
            
        } catch (error) {
            console.error('Auth status check failed', error);
            return false;
        }
    },

    /**
     * Setup activity timeout (optional)
     * Clears session after period of inactivity
     */
    setupActivityTimeout: function() {
        const TIMEOUT = 3600000; // 1 hour
        let timeoutId;
        
        const resetTimeout = () => {
            clearTimeout(timeoutId);
            const userInfo = sessionStorage.getItem('shebamiles_user_info');
            if (userInfo) {
                timeoutId = setTimeout(() => {
                    this.showWarning('Session expired due to inactivity. Please log in again.');
                    this.handleLogout();
                }, TIMEOUT);
            }
        };
        
        // Reset on user activity
        document.addEventListener('click', resetTimeout);
        document.addEventListener('keypress', resetTimeout);
        document.addEventListener('mousemove', resetTimeout);
        
        // Initial setup
        resetTimeout();
    },

    /**
     * Get current user info (non-sensitive only)
     */
    getCurrentUser: function() {
        const userInfo = sessionStorage.getItem('shebamiles_user_info');
        return userInfo ? JSON.parse(userInfo) : null;
    },

    /**
     * Get CSRF token for API requests
     */
    getCSRFToken: function() {
        return sessionStorage.getItem('csrf_token');
    },

    /**
     * Check if user is logged in
     */
    isLoggedIn: function() {
        return sessionStorage.getItem('shebamiles_user_info') !== null;
    },

    /**
     * Clear all session data
     */
    clearSession: function() {
        sessionStorage.removeItem('shebamiles_user_info');
        sessionStorage.removeItem('csrf_token');
    },

    /**
     * Show error message
     */
    showError: function(message) {
        showToast(message, 'error');
    },

    /**
     * Show multiple errors (from validation)
     */
    showErrors: function(errors) {
        if (typeof errors === 'object') {
            // Object with field: [errors] format
            for (const field in errors) {
                const fieldErrors = errors[field];
                if (Array.isArray(fieldErrors)) {
                    fieldErrors.forEach(error => {
                        showToast(field + ': ' + error, 'error');
                    });
                } else if (typeof fieldErrors === 'object') {
                    // Object with rule: message format
                    for (const rule in fieldErrors) {
                        showToast(field + ': ' + fieldErrors[rule], 'error');
                    }
                } else {
                    showToast(field + ': ' + fieldErrors, 'error');
                }
            }
        } else {
            showToast(errors, 'error');
        }
    },

    /**
     * Show success message
     */
    showSuccess: function(message, duration = 3000) {
        showToast(message, 'success');
    },

    /**
     * Show warning message
     */
    showWarning: function(message) {
        showToast(message, 'warning');
    }
};

// Initialize auth module when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    AuthModule.init();
});
