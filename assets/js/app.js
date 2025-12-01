// Main application JavaScript

// Theme management
function setTheme(theme) {
    if (!theme) return;
    document.documentElement.setAttribute('data-theme', theme);
}

function getTheme() {
    return document.documentElement.getAttribute('data-theme') || 'light';
}

// Load theme on page load
document.addEventListener('DOMContentLoaded', function() {
    const theme = getTheme();
    setTheme(theme);
});

// Primary color management
function setPrimaryColor(color) {
    document.documentElement.style.setProperty('--primary', color);
    
    // Calculate hover color (darker)
    const hoverColor = adjustColor(color, -20);
    document.documentElement.style.setProperty('--primary-hover', hoverColor);
}

function getPrimaryColor() {
    const current = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();
    return current || '#2563eb';
}

// Adjust color brightness
function adjustColor(color, amount) {
    const num = parseInt(color.replace('#', ''), 16);
    const r = Math.max(0, Math.min(255, (num >> 16) + amount));
    const g = Math.max(0, Math.min(255, ((num >> 8) & 0x00FF) + amount));
    const b = Math.max(0, Math.min(255, (num & 0x0000FF) + amount));
    return '#' + (0x1000000 + (r << 16) + (g << 8) + b).toString(16).slice(1);
}

// Dropdown menu toggle
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.user-menu') && !event.target.closest('.nav-dropdown')) {
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.main-content .container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'just nu';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} minut${minutes > 1 ? 'er' : ''} sedan`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} timm${hours > 1 ? 'ar' : 'e'} sedan`;
    } else if (diffInSeconds < 604800) {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} dag${days > 1 ? 'ar' : ''} sedan`;
    } else {
        return date.toLocaleDateString('sv-SE');
    }
}

// Apply relative dates
document.addEventListener('DOMContentLoaded', function() {
    const dateElements = document.querySelectorAll('[data-date]');
    dateElements.forEach(element => {
        const date = element.getAttribute('data-date');
        element.textContent = formatDate(date);
    });
});

// File upload preview
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || 'Är du säker på att du vill ta bort detta?');
}

// Auto-resize textarea
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('.form-textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
});

// Search/filter functionality
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}
