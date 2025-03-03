/**
 * Tournament Bracket System - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Flash message auto-dismiss
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert.alert-dismissible');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Confirm delete actions
    const confirmDeleteForms = document.querySelectorAll('.confirm-delete');
    Array.from(confirmDeleteForms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                event.preventDefault();
            }
        });
    });
    
    // AJAX form submissions
    const ajaxForms = document.querySelectorAll('.ajax-form');
    Array.from(ajaxForms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(form);
            const submitButton = form.querySelector('[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Disable submit button and show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
            
            // Send AJAX request
            fetch(form.action, {
                method: form.method,
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                // Handle response
                if (data.success) {
                    // Show success message
                    showAlert('success', data.message || 'Operation completed successfully.');
                    
                    // Reset form if specified
                    if (form.dataset.reset === 'true') {
                        form.reset();
                    }
                    
                    // Redirect if specified
                    if (form.dataset.redirect) {
                        window.location.href = form.dataset.redirect;
                    }
                    
                    // Reload page if specified
                    if (form.dataset.reload === 'true') {
                        window.location.reload();
                    }
                    
                    // Trigger custom event
                    form.dispatchEvent(new CustomEvent('ajax:success', { detail: data }));
                } else {
                    // Show error message
                    showAlert('danger', data.error || 'An error occurred. Please try again.');
                    
                    // Trigger custom event
                    form.dispatchEvent(new CustomEvent('ajax:error', { detail: data }));
                }
            })
            .catch(error => {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                // Show error message
                showAlert('danger', 'An error occurred. Please try again.');
                console.error('Error:', error);
                
                // Trigger custom event
                form.dispatchEvent(new CustomEvent('ajax:error', { detail: { error: error.message } }));
            });
        });
    });
    
    // Function to show alert
    window.showAlert = function(type, message) {
        const alertContainer = document.getElementById('alert-container');
        if (!alertContainer) {
            // Create alert container if it doesn't exist
            const container = document.createElement('div');
            container.id = 'alert-container';
            container.className = 'position-fixed top-0 start-50 translate-middle-x p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        
        const alertElement = document.createElement('div');
        alertElement.className = `alert alert-${type} alert-dismissible fade show`;
        alertElement.role = 'alert';
        
        let icon = '';
        switch (type) {
            case 'success':
                icon = '<i class="fas fa-check-circle me-2"></i>';
                break;
            case 'danger':
                icon = '<i class="fas fa-exclamation-circle me-2"></i>';
                break;
            case 'warning':
                icon = '<i class="fas fa-exclamation-triangle me-2"></i>';
                break;
            case 'info':
                icon = '<i class="fas fa-info-circle me-2"></i>';
                break;
        }
        
        alertElement.innerHTML = `
            ${icon}${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.getElementById('alert-container').appendChild(alertElement);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alertElement);
            bsAlert.close();
        }, 5000);
    };
    
    // Function to show loading spinner
    window.showLoading = function(message = 'Loading...') {
        // Remove existing spinner if any
        hideLoading();
        
        // Create spinner overlay
        const spinnerOverlay = document.createElement('div');
        spinnerOverlay.className = 'spinner-overlay';
        spinnerOverlay.id = 'loading-spinner';
        
        spinnerOverlay.innerHTML = `
            <div class="spinner-container">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-text">${message}</div>
            </div>
        `;
        
        document.body.appendChild(spinnerOverlay);
        document.body.style.overflow = 'hidden';
    };
    
    // Function to hide loading spinner
    window.hideLoading = function() {
        const existingSpinner = document.getElementById('loading-spinner');
        if (existingSpinner) {
            existingSpinner.remove();
            document.body.style.overflow = '';
        }
    };
    
    // Handle print button
    const printButtons = document.querySelectorAll('.print-button');
    Array.from(printButtons).forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            window.print();
        });
    });
});

/**
 * Format date
 * 
 * @param {string} dateString ISO date string
 * @param {string} format Format string (default: 'MM/DD/YYYY')
 * @return {string} Formatted date
 */
function formatDate(dateString, format = 'MM/DD/YYYY') {
    const date = new Date(dateString);
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    
    return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day)
        .replace('HH', hours)
        .replace('mm', minutes)
        .replace('ss', seconds);
}

/**
 * Truncate string
 * 
 * @param {string} str String to truncate
 * @param {number} length Maximum length
 * @param {string} suffix Suffix to append if truncated
 * @return {string} Truncated string
 */
function truncateString(str, length = 100, suffix = '...') {
    if (str.length <= length) {
        return str;
    }
    
    return str.substring(0, length).trim() + suffix;
}