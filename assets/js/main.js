/**
 * Texon Dashboard - Main JavaScript
 * 
 * This file contains the core JavaScript functionality for the dashboard.
 */

// Initialize when the document is ready
$(document).ready(function() {
    // Enable Bootstrap tooltips (Bootstrap 5 syntax)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Enable Bootstrap popovers (Bootstrap 5 syntax)
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Fix Bootstrap 5 modals
    fixBootstrapModals();
    
    // Initialize data tables if they exist
    if ($.fn.DataTable && $('.data-table').length) {
        $('.data-table').DataTable({
            responsive: true,
            "pageLength": 25
        });
    }
    
    // Form validation for all forms with 'needs-validation' class
    validateForms();
    
    // Handle sidebar toggle on mobile
    handleSidebarToggle();
    
    // Setup AJAX defaults
    setupAjaxDefaults();
    
    // Handle school search functionality if it exists
    if ($('#schoolSearch').length) {
        handleSchoolSearch();
    }
    
    // Handle data refresh buttons
    $('.refresh-data-btn').on('click', function(e) {
        e.preventDefault();
        refreshSchoolData($(this).data('domain'));
    });
});

/**
 * Fix Bootstrap 5 modal backdrop and accessibility issues
 */
function fixBootstrapModals() {
    // Helper function to properly clean up modal elements
    function cleanupModal() {
        // Remove modal-backdrop if it exists
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        
        // Reset body classes and styles
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    // Add listeners to all modals
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            // Clean up any remaining backdrop
            cleanupModal();
            
            // Find the button that opened this modal
            const modalId = this.id;
            const triggerButton = document.querySelector(`[data-bs-target="#${modalId}"]`);
            
            // Focus the trigger button or a fallback
            setTimeout(function() {
                if (triggerButton) {
                    triggerButton.focus();
                } else {
                    const mainContent = document.querySelector('main');
                    if (mainContent) {
                        mainContent.setAttribute('tabindex', '-1');
                        mainContent.focus();
                        mainContent.removeAttribute('tabindex');
                    }
                }
            }, 10);
        });
    });
    
    // Handle ESC key globally
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.querySelector('.modal.show')) {
            cleanupModal();
        }
    });
}

/**
 * Setup AJAX defaults for the application
 */
function setupAjaxDefaults() {
    // Set AJAX defaults
    $.ajaxSetup({
        cache: false,
        error: function(xhr, status, error) {
            // Log error to console
            console.error('AJAX Error:', status, error);
            
            // Show error message to user
            let errorMessage = 'An error occurred. Please try again.';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            showNotification(errorMessage, 'error');
        }
    });
    
    // Add CSRF token to all AJAX requests if it exists
    let csrfToken = $('meta[name="csrf-token"]').attr('content');
    if (csrfToken) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
    }
}

/**
 * Handle client-side form validation
 */
function validateForms() {
    'use strict';
    
    // Fetch all forms with the 'needs-validation' class
    var forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Handle sidebar toggle on mobile
 */
function handleSidebarToggle() {
    // Toggle sidebar on mobile (Bootstrap 5 syntax)
    document.querySelector('.navbar-toggler')?.addEventListener('click', function() {
        document.querySelector('#sidebarMenu')?.classList.toggle('show');
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768) {
            const sidebar = document.querySelector('#sidebarMenu');
            const toggler = document.querySelector('.navbar-toggler');
            
            if (sidebar && !sidebar.contains(e.target) && 
                toggler && !toggler.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
}

/**
 * Handle school search functionality
 */
function handleSchoolSearch() {
    $('#schoolSearch').on('keyup', function() {
        var searchText = $(this).val().toLowerCase();
        
        $('.school-item').each(function() {
            var schoolName = $(this).data('name').toLowerCase();
            var schoolDomain = $(this).data('domain').toLowerCase();
            
            if (schoolName.indexOf(searchText) > -1 || schoolDomain.indexOf(searchText) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
}

/**
 * Refresh school data from all sources
 * 
 * @param {string} domain School domain to refresh
 */
function refreshSchoolData(domain) {
    if (!domain) {
        showNotification('No school domain specified', 'error');
        return;
    }
    
    // Show loading indicator
    showLoading();
    
    // Make AJAX request to refresh data
    $.ajax({
        url: 'includes/api/refresh_school.php',
        type: 'POST',
        data: {
            domain: domain
        },
        dataType: 'json',
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                showNotification(response.message, 'success');
                
                // Reload the page after a short delay
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showNotification(response.message, 'error');
            }
        },
        error: function() {
            hideLoading();
            showNotification('Failed to refresh school data', 'error');
        }
    });
}

/**
 * Show a notification to the user
 * 
 * @param {string} message Message to display
 * @param {string} type Notification type (success, error, info, warning)
 * @param {number} duration Duration in milliseconds
 */
function showNotification(message, type = 'info', duration = 5000) {
    // Create notification element if it doesn't exist
    if ($('#notification-container').length === 0) {
        $('body').append('<div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
    }
    
    // Set notification class based on type
    let notificationClass = 'bg-info';
    switch (type) {
        case 'success':
            notificationClass = 'bg-success';
            break;
        case 'error':
            notificationClass = 'bg-danger';
            break;
        case 'warning':
            notificationClass = 'bg-warning';
            break;
    }
    
    // Create a unique ID for this notification
    const notificationId = 'notification-' + Date.now();
    
    // Create notification HTML with Bootstrap 5 syntax
    const notificationHtml = `
        <div id="${notificationId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="${duration}">
            <div class="toast-header ${notificationClass} text-white">
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    // Append notification to container
    $('#notification-container').append(notificationHtml);
    
    // Initialize and show the toast with Bootstrap 5
    var toast = new bootstrap.Toast(document.getElementById(notificationId));
    toast.show();
    
    // Remove notification after it's hidden
    $(`#${notificationId}`).on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

/**
 * Show loading overlay
 */
function showLoading() {
    // Create loading overlay if it doesn't exist
    if ($('#loading-overlay').length === 0) {
        $('body').append(`
            <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 9999;">
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white;">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2">Loading...</div>
                </div>
            </div>
        `);
    }
    
    $('#loading-overlay').fadeIn(200);
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    $('#loading-overlay').fadeOut(200);
}

/**
 * Format a date string into a more human-readable format
 * 
 * @param {string} dateString Date string to format
 * @param {string} format Format to use (short, medium, long)
 * @return {string} Formatted date string
 */
function formatDate(dateString, format = 'medium') {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    
    // Check if date is valid
    if (isNaN(date.getTime())) {
        return dateString;
    }
    
    switch (format) {
        case 'short':
            return `${date.getMonth() + 1}/${date.getDate()}/${date.getFullYear()}`;
        case 'long':
            return date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        case 'time':
            return date.toLocaleTimeString('en-US');
        case 'datetime':
            return date.toLocaleDateString('en-US') + ' ' + date.toLocaleTimeString('en-US');
        case 'medium':
        default:
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
    }
}

/**
 * Format a number as currency
 * 
 * @param {number} amount Amount to format
 * @param {string} currencyCode Currency code (USD, EUR, etc.)
 * @return {string} Formatted currency string
 */
function formatCurrency(amount, currencyCode = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode
    }).format(amount);
}

/**
 * Format a number as a percentage
 * 
 * @param {number} value Value to format as percentage
 * @param {number} decimalPlaces Number of decimal places
 * @return {string} Formatted percentage string
 */
function formatPercentage(value, decimalPlaces = 2) {
    return new Intl.NumberFormat('en-US', {
        style: 'percent',
        minimumFractionDigits: decimalPlaces,
        maximumFractionDigits: decimalPlaces
    }).format(value / 100);
}