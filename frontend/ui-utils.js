/**
 * Shebamiles UI Utilities - Enhanced UX Features
 */

// Button Loading State Manager
class ButtonLoader {
    static enable(button) {
        button.disabled = true;
        button.classList.add('opacity-75', 'cursor-not-allowed');
        
        const icon = button.querySelector('.material-symbols-outlined');
        if (icon && !button.hasAttribute('data-original-icon')) {
            button.setAttribute('data-original-icon', icon.textContent);
            icon.textContent = 'hourglass_empty';
            icon.classList.add('animate-spin');
        }
    }
    
    static disable(button) {
        button.disabled = false;
        button.classList.remove('opacity-75', 'cursor-not-allowed');
        
        const icon = button.querySelector('.material-symbols-outlined');
        if (icon && button.hasAttribute('data-original-icon')) {
            icon.textContent = button.getAttribute('data-original-icon');
            icon.classList.remove('animate-spin');
            button.removeAttribute('data-original-icon');
        }
    }
}

// Confirmation Dialog Manager
class ConfirmDialog {
    static async confirm(title, message, confirmText = 'Confirm', cancelText = 'Cancel', type = 'warning') {
        return new Promise((resolve) => {
            const dialog = document.createElement('div');
            dialog.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            
            const colorMap = {
                'warning': { bg: 'bg-amber-50', border: 'border-amber-200', icon: 'warning', iconColor: 'text-amber-600' },
                'danger': { bg: 'bg-red-50', border: 'border-red-200', icon: 'error', iconColor: 'text-red-600' },
                'info': { bg: 'bg-blue-50', border: 'border-blue-200', icon: 'info', iconColor: 'text-blue-600' }
            };
            
            const colors = colorMap[type] || colorMap['warning'];
            
            dialog.innerHTML = `
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl max-w-sm w-full mx-4">
                    <div class="${colors.bg} dark:bg-slate-800 border-b ${colors.border} dark:border-slate-700 p-4 flex items-center gap-3">
                        <span class="material-symbols-outlined ${colors.iconColor}">${colors.icon}</span>
                        <h3 class="font-bold text-gray-900 dark:text-white">${title}</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 dark:text-gray-400 mb-6">${message}</p>
                        <div class="flex gap-3">
                            <button class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors font-medium cancel-btn">
                                ${cancelText}
                            </button>
                            <button class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium confirm-btn">
                                ${confirmText}
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            dialog.querySelector('.cancel-btn').addEventListener('click', () => {
                dialog.remove();
                resolve(false);
            });
            
            dialog.querySelector('.confirm-btn').addEventListener('click', () => {
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
    }
}

// Empty State Manager
class EmptyState {
    static create(icon, title, message, actionText = null, actionCallback = null) {
        const container = document.createElement('div');
        container.className = 'flex flex-col items-center justify-center p-12 text-center';
        container.innerHTML = `
            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-3xl text-slate-400">${icon}</span>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">${title}</h3>
            <p class="text-gray-600 dark:text-gray-400 max-w-sm mb-6">${message}</p>
            ${actionText && actionCallback ? `
                <button onclick="event.target.dispatchEvent(new CustomEvent('empty-state-action'))" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                    ${actionText}
                </button>
            ` : ''}
        `;
        
        if (actionCallback) {
            container.addEventListener('empty-state-action', actionCallback);
        }
        
        return container;
    }
}

// Toast Notification with Auto-dismiss
function showToast(message, type = 'info', duration = 4000) {
    const toast = document.createElement('div');
    const colors = {
        'success': 'bg-green-500',
        'error': 'bg-red-500',
        'warning': 'bg-amber-500',
        'info': 'bg-blue-500'
    };
    
    const icons = {
        'success': 'check_circle',
        'error': 'error',
        'warning': 'warning',
        'info': 'info'
    };
    
    toast.className = `fixed top-4 right-4 ${colors[type] || colors['info']} text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center gap-3 animate-slide-in`;
    toast.innerHTML = `
        <span class="material-symbols-outlined">${icons[type]}</span>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('animate-slide-out');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Form Validation Helper
class FormValidator {
    static validateEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    static validatePhone(phone) {
        const regex = /^[\d\s\-\+\(\)]+$/;
        return regex.test(phone) && phone.replace(/\D/g, '').length >= 10;
    }
    
    static validatePassword(password) {
        return password.length >= 8;
    }
    
    static showFieldError(field, message) {
        field.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        
        let error = field.parentElement.querySelector('.field-error');
        if (!error) {
            error = document.createElement('p');
            error.className = 'field-error text-sm text-red-500 mt-1';
            field.parentElement.appendChild(error);
        }
        error.textContent = message;
    }
    
    static clearFieldError(field) {
        field.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        const error = field.parentElement.querySelector('.field-error');
        if (error) error.remove();
    }
}

// Export for use
window.UIUtils = {
    ButtonLoader,
    ConfirmDialog,
    EmptyState,
    showToast,
    FormValidator
};
