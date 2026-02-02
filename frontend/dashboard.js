/**
 * Shebamiles - Dashboard Initialization
 * Loads user data from localStorage and displays personalized content
 */

const DashboardInit = {
    init: function() {
        this.checkAuth();
        this.loadUserProfile();
        this.setupLogout();
    },

    checkAuth: function() {
        const user = localStorage.getItem('shebamiles_user');
        if (!user) {
            // User not logged in, redirect to login
            window.location.href = 'index.html';
            return;
        }
    },

    loadUserProfile: function() {
        const userJson = localStorage.getItem('shebamiles_user');
        if (!userJson) return;

        const user = JSON.parse(userJson);
        
        // Update welcome message
        this.updateWelcomeMessage(user.name);
        
        // Update sidebar profile if exists
        this.updateProfileSection(user.name);
        
        // Update header profile if exists
        this.updateHeaderProfile(user.name);
    },

    updateWelcomeMessage: function(name) {
        // Look for welcome message elements
        const welcomeElements = document.querySelectorAll('[data-welcome-name], .welcome-name, #welcomeName');
        welcomeElements.forEach(el => {
            el.textContent = name;
        });

        // Also try to find and update h1 or header with "Welcome"
        const headers = document.querySelectorAll('h1, h2, .welcome-header');
        headers.forEach(el => {
            if (el.textContent.includes('Welcome') || el.textContent.includes('Dashboard')) {
                if (!el.textContent.includes(name)) {
                    el.textContent = `Welcome, ${name}`;
                }
            }
        });
    },

    updateProfileSection: function(name) {
        // Update sidebar profile name (common pattern)
        const sidebarNames = document.querySelectorAll('[data-sidebar-name], .sidebar-user-name, .profile-name');
        sidebarNames.forEach(el => {
            const nameParts = name.split(' ');
            el.textContent = nameParts[0]; // First name
        });

        // Update full sidebar profile area
        const profileH1 = document.querySelector('aside h1, .profile-section h1');
        if (profileH1) {
            profileH1.textContent = name;
        }
    },

    updateHeaderProfile: function(name) {
        // Update top header/right side profile area
        const headerNames = document.querySelectorAll('[data-header-name], .header-user-name');
        headerNames.forEach(el => {
            el.textContent = name;
        });

        // Update user profile card in header/sidebar
        const profileCards = document.querySelectorAll('.profile-card, [role="region"][data-profile]');
        profileCards.forEach(card => {
            const nameEl = card.querySelector('p, h3, span');
            if (nameEl) {
                nameEl.textContent = name;
            }
        });
    },

    setupLogout: function() {
        // Find all logout buttons/links
        const logoutElements = document.querySelectorAll('[data-logout], .logout-btn, a[href="logout.html"]');
        logoutElements.forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                this.logout();
            });
        });

        // Also update logout link to point to backend
        const logoutLinks = document.querySelectorAll('a[href*="logout"]');
        logoutLinks.forEach(link => {
            if (!link.href.includes('backend')) {
                link.href = '../backend/logout.php';
            }
        });
    },

    logout: function() {
        localStorage.removeItem('shebamiles_user');
        window.location.href = '../backend/logout.php';
    },

    getCurrentUser: function() {
        const userJson = localStorage.getItem('shebamiles_user');
        return userJson ? JSON.parse(userJson) : null;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    DashboardInit.init();
});
