// GameLend JavaScript functionality

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips and confirmations
    initializeConfirmations();
    initializeFormValidation();
    initializeSearchFilter();
});

// Initialize confirmation dialogs
function initializeConfirmations() {
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// Initialize form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

// Form validation function
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email address');
            isValid = false;
        }
    });
    
    // Password confirmation validation
    const passwordField = form.querySelector('input[name="password"]');
    const confirmPasswordField = form.querySelector('input[name="confirm_password"]');
    if (passwordField && confirmPasswordField && passwordField.value !== confirmPasswordField.value) {
        showFieldError(confirmPasswordField, 'Passwords do not match');
        isValid = false;
    }
    
    return isValid;
}

// Show field error
function showFieldError(field, message) {
    clearFieldError(field);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '0.25rem';
    field.parentNode.appendChild(errorDiv);
    field.style.borderColor = '#dc3545';
}

// Clear field error
function clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.style.borderColor = '#ddd';
}

// Email validation
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Initialize search and filter functionality
function initializeSearchFilter() {
    const searchInput = document.getElementById('search-games');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterGames(this.value);
        });
    }
}

// Filter games based on search input
function filterGames(searchTerm) {
    const gameRows = document.querySelectorAll('.game-row');
    const searchLower = searchTerm.toLowerCase();
    
    gameRows.forEach(row => {
        const title = row.querySelector('.game-title').textContent.toLowerCase();
        const platform = row.querySelector('.game-platform').textContent.toLowerCase();
        
        if (title.includes(searchLower) || platform.includes(searchLower)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Show/hide password functionality
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.querySelector(`[onclick="togglePasswordVisibility('${inputId}')"] i`);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Auto-hide alerts after 5 seconds
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// Initialize auto-hide for alerts
document.addEventListener('DOMContentLoaded', function() {
    autoHideAlerts();
});

// Smooth scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Add scroll to top button
window.addEventListener('scroll', function() {
    const scrollTopBtn = document.getElementById('scroll-top-btn');
    if (scrollTopBtn) {
        if (window.pageYOffset > 300) {
            scrollTopBtn.style.display = 'block';
        } else {
            scrollTopBtn.style.display = 'none';
        }
    }
});

// Utility function to format dates
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Utility function to calculate days difference
function getDaysDifference(date1, date2) {
    const oneDay = 24 * 60 * 60 * 1000;
    const diffTime = Math.abs(date2 - date1);
    return Math.ceil(diffTime / oneDay);
}

// Export functions for use in other scripts
window.GameLend = {
    validateForm,
    filterGames,
    togglePasswordVisibility,
    formatDate,
    getDaysDifference
};
