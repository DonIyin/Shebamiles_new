/**
 * Shebamiles - Authentication JavaScript Module
 * Handles login and signup form submissions
 */

const AuthModule = {
    
    // Initialize auth listeners
    init: function() {
        this.setupLoginForm();
        this.setupSignupForm();
        this.checkAuthStatus();
    },

    // Setup login form
    setupLoginForm: function() {
        const form = document.getElementById('loginForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin(form);
        });
    },

    // Setup signup form
    setupSignupForm: function() {
        const form = document.getElementById('signupForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSignup(form);
        });
    },

    // Handle login
    handleLogin: async function(form) {
        const username = document.getElementById('username')?.value || '';
        const password = document.getElementById('password')?.value || '';
        const remember = document.getElementById('remember')?.checked || false;

        // Validate
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
            const response = await fetch('../backend/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username,
                    password: password,
                    remember: remember
                })
            });

            const data = await response.json();

            if (data.success) {
                // Store user data (persist if "remember me" is checked)
                const storage = remember ? localStorage : sessionStorage;
                storage.setItem('shebamiles_user', JSON.stringify(data.user));
                
                // Show success message
                this.showSuccess('Login successful! Redirecting...');
                
                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            } else {
                this.showError(data.message || 'Login failed');
            }
        } catch (error) {
            this.showError('Network error: ' + error.message);
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    },

    // Handle signup
    handleSignup: async function(form) {
        const email = document.getElementById('signup-email')?.value || '';
        const password = document.getElementById('signup-password')?.value || '';
        const confirmPassword = document.getElementById('confirm-password')?.value || '';
        const firstName = document.getElementById('first-name')?.value || '';
        const lastName = document.getElementById('last-name')?.value || '';
        const username = document.getElementById('username')?.value || '';
        const phone = document.getElementById('phone')?.value || '';
        const department = document.getElementById('department')?.value || '';

        // Validate
        if (!email || !password || !confirmPassword || !firstName || !lastName || !username) {
            this.showError('Please fill in all required fields');
            return;
        }

        if (password !== confirmPassword) {
            this.showError('Passwords do not match');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="inline-block animate-spin">⏳</span> Creating account...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('../backend/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
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

            // Get the response text first to check if it's valid JSON
            const responseText = await response.text();
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Invalid JSON response:', responseText);
                this.showError('Server error: Invalid response. Please check server configuration.');
                return;
            }

            if (data.success) {
                // Show success message
                this.showSuccess('Account created successfully! Redirecting to login...');
                
                // Redirect to login page after short delay
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 1500);
            } else if (data.errors) {
                this.showErrors(data.errors);
            } else {
                this.showError(data.message || 'Registration failed');
            }
        } catch (error) {
            console.error('Network error:', error);
            this.showError('Network error: ' + error.message);
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    },

    // Check authentication status
    checkAuthStatus: function() {
        const user = localStorage.getItem('shebamiles_user') || sessionStorage.getItem('shebamiles_user');
        if (!user) {
            // User not logged in, might want to redirect
        }
    },

    // Show error message
    showError: function(message) {
        showToast(message, 'error');
    },

    // Show multiple errors
    showErrors: function(errors) {
        errors.forEach(error => {
            showToast(error, 'error');
        });
    },

    // Show success message
    showSuccess: function(message) {
        showToast(message, 'success');
    },

    // Logout
    logout: function() {
        localStorage.removeItem('shebamiles_user');
        sessionStorage.removeItem('shebamiles_user');
        window.location.href = '../backend/logout.php';
    },

    // Get current user
    getCurrentUser: function() {
        const user = localStorage.getItem('shebamiles_user') || sessionStorage.getItem('shebamiles_user');
        return user ? JSON.parse(user) : null;
    },

    // Check if user is logged in
    isLoggedIn: function() {
        return (localStorage.getItem('shebamiles_user') || sessionStorage.getItem('shebamiles_user')) !== null;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    AuthModule.init();
});
