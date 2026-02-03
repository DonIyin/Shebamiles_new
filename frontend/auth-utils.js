/**
 * Shebamiles - Authentication Utilities
 * Helper functions for authentication and authorization on frontend
 */

const AuthUtils = {
    /**
     * Get current user from localStorage
     */
    getCurrentUser: function() {
        const userStr = localStorage.getItem('shebamiles_user');
        return userStr ? JSON.parse(userStr) : null;
    },

    /**
     * Check if user is logged in
     */
    isLoggedIn: function() {
        return this.getCurrentUser() !== null;
    },

    /**
     * Check if user is admin
     */
    isAdmin: function() {
        const user = this.getCurrentUser();
        return user && user.role === 'admin';
    },

    /**
     * Check if user is manager
     */
    isManager: function() {
        const user = this.getCurrentUser();
        return user && (user.role === 'manager' || user.role === 'admin');
    },

    /**
     * Require login - redirect to login if not logged in
     */
    requireLogin: function() {
        if (!this.isLoggedIn()) {
            window.location.href = 'index.html';
            return false;
        }
        return true;
    },

    /**
     * Require admin role - redirect to dashboard if not admin
     */
    requireAdmin: function() {
        if (!this.isAdmin()) {
            console.warn('Admin access required');
            window.location.href = 'employee_personalized_dashboard.html';
            return false;
        }
        return true;
    },

    /**
     * Logout user
     */
    logout: async function() {

        // Show confirmation dialog
        const confirmed = await this.showLogoutConfirm();
        if (!confirmed) return;
        
        try {
            const response = await fetch('../backend/logout.php', {
                method: 'POST'
            });
            
            // Clear localStorage
            localStorage.removeItem('shebamiles_user');
            
            // Redirect to login
            window.location.href = 'index.html';
        } catch (error) {
            console.error('Logout error:', error);
            // Force redirect anyway
            window.location.href = 'index.html';
        }
    },

    /**
     * Show logout confirmation dialog
     */
    showLogoutConfirm: async function() {
        return new Promise((resolve) => {
            const dialog = document.createElement('div');
            dialog.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            dialog.innerHTML = `
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl max-w-sm w-full mx-4">
                    <div class="bg-amber-50 dark:bg-slate-800 border-b border-amber-200 dark:border-slate-700 p-4 flex items-center gap-3">
                        <span class="material-symbols-outlined text-amber-600">logout</span>
                        <h3 class="font-bold text-gray-900 dark:text-white">Sign Out?</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Are you sure you want to sign out? You'll need to sign in again to access your account.</p>
                        <div class="flex gap-3">
                            <button class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors font-medium">
                                Cancel
                            </button>
                            <button class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
                                Sign Out
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            const cancelBtn = dialog.querySelector('button:first-of-type');
            const confirmBtn = dialog.querySelector('button:last-of-type');
            
            cancelBtn.addEventListener('click', () => {
                dialog.remove();
                resolve(false);
            });
            
            confirmBtn.addEventListener('click', () => {
                dialog.remove();
                resolve(true);
            });
            
            dialog.addEventListener('click', (e) => {
                if (e.target === dialog) {
                    dialog.remove();
                    resolve(false);
                }
            });
            
            document.body.appendChild(dialog);
        });
    },
    /**
     * Get CSRF token from session/server
     */
    getCSRFToken: async function() {
        try {
            const response = await fetch('../backend/get-csrf-token.php');
            const data = await response.json();
            if (data.success) {
                return data.token;
            }
        } catch (error) {
            console.error('Error getting CSRF token:', error);
        }
        return null;
    },

    /**
     * Show toast notification
     */
    showToast: function(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || this.createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-lg">
                    ${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info'}
                </span>
                <span>${message}</span>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Add styles if not already added
        if (!document.getElementById('toastStyles')) {
            const style = document.createElement('style');
            style.id = 'toastStyles';
            style.innerHTML = `
                #toastContainer {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }
                .toast {
                    padding: 12px 16px;
                    border-radius: 8px;
                    animation: slideIn 0.3s ease-out;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    font-size: 14px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                }
                .toast-success {
                    background-color: #10b981;
                    color: white;
                }
                .toast-error {
                    background-color: #ef4444;
                    color: white;
                }
                .toast-info {
                    background-color: #3b82f6;
                    color: white;
                }
                @keyframes slideIn {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    /**
     * Create toast container if it doesn't exist
     */
    createToastContainer: function() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
        return container;
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthUtils;
}
