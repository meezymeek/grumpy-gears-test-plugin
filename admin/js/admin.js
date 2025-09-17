/**
 * Grumpy Gears Plugin Admin JavaScript
 */

(function($) {
    'use strict';
    
    // DOM Ready
    $(document).ready(function() {
        initUpdateChecker();
        initModal();
    });
    
    /**
     * Initialize update checker functionality
     */
    function initUpdateChecker() {
        const $checkButton = $('#check-updates-btn');
        const $spinner = $('#check-updates-spinner');
        const $updateStatus = $('#update-status');
        
        // Check for updates button click
        $checkButton.on('click', function() {
            checkForUpdates();
        });
        
        // View changelog button click
        $(document).on('click', '#view-changelog-btn', function() {
            const currentStatus = window.grumpyGearsUpdateStatus || {};
            if (currentStatus.changelog) {
                showChangelog(currentStatus.changelog);
            } else {
                showMessage('No changelog available.', 'error');
            }
        });
        
        /**
         * Check for updates via AJAX
         */
        function checkForUpdates() {
            // Disable button and show spinner
            $checkButton.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Update button text
            const originalText = $checkButton.find('.dashicons').next().text() || $checkButton.text().trim();
            $checkButton.find('span:not(.dashicons)').text('Checking...');
            
            $.ajax({
                url: grumpyGearsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'grumpy_gears_check_update',
                    nonce: grumpyGearsAjax.check_update_nonce
                },
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        updateStatusDisplay(response.data);
                        
                        // Store status globally for changelog access
                        window.grumpyGearsUpdateStatus = response.data;
                        
                        if (response.data.update_available) {
                            showMessage('Update check completed! New version available.', 'info');
                        } else {
                            showMessage('Update check completed! Plugin is up to date.', 'success');
                        }
                    } else {
                        showMessage('Error checking for updates: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Failed to check for updates.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Update check timed out. Please try again.';
                    } else if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = xhr.responseJSON.data;
                    } else if (error) {
                        errorMessage += ' Error: ' + error;
                    }
                    
                    showMessage(errorMessage, 'error');
                },
                complete: function() {
                    // Re-enable button and hide spinner
                    $checkButton.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    $checkButton.find('span:not(.dashicons)').text('Check for Updates');
                }
            });
        }
        
        /**
         * Update the status display
         */
        function updateStatusDisplay(data) {
            let statusHtml = '';
            
            if (data.update_available) {
                statusHtml = `
                    <div class="notice notice-warning inline">
                        <p><strong>Update Available!</strong></p>
                        <p>Current version: <strong>${escapeHtml(data.current_version)}</strong></p>
                        <p>New version: <strong>${escapeHtml(data.new_version)}</strong></p>
                        <p><button type="button" id="view-changelog-btn" class="button button-primary">View Changelog</button></p>
                    </div>
                `;
            } else if (data.current_version) {
                statusHtml = `
                    <div class="notice notice-success inline">
                        <p><strong>Plugin is up to date!</strong></p>
                        <p>Current version: <strong>${escapeHtml(data.current_version)}</strong></p>
                    </div>
                `;
            } else {
                statusHtml = `
                    <div class="notice notice-error inline">
                        <p><strong>Error:</strong> ${escapeHtml(data.message || 'Unable to check for updates.')}</p>
                    </div>
                `;
            }
            
            $updateStatus.html(statusHtml);
        }
    }
    
    /**
     * Initialize modal functionality
     */
    function initModal() {
        const $modal = $('#changelog-modal');
        const $modalContent = $('#changelog-content');
        const $closeBtn = $('.grumpy-gears-modal-close');
        
        // Close modal when clicking the X
        $closeBtn.on('click', function() {
            $modal.hide();
        });
        
        // Close modal when clicking outside of it
        $(window).on('click', function(e) {
            if (e.target === $modal[0]) {
                $modal.hide();
            }
        });
        
        // Close modal on escape key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27 && $modal.is(':visible')) {
                $modal.hide();
            }
        });
        
        /**
         * Show changelog in modal
         */
        window.showChangelog = function(changelog) {
            if (!changelog || changelog.trim() === '') {
                changelog = 'No changelog available.';
            }
            
            $modalContent.html(changelog);
            $modal.fadeIn(300);
            
            // Focus on modal for accessibility
            $modal.focus();
        };
    }
    
    /**
     * Show temporary message
     */
    function showMessage(message, type) {
        type = type || 'info';
        
        const $message = $(`
            <div class="grumpy-gears-message ${type}" style="display: none;">
                ${escapeHtml(message)}
            </div>
        `);
        
        // Insert after the update status
        $('#update-status').after($message);
        
        // Fade in the message
        $message.fadeIn(300);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (typeof text !== 'string') {
            return text;
        }
        
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }
    
    /**
     * Handle form submission feedback
     */
    $(document).on('submit', 'form', function() {
        // Show loading indicator on submit buttons
        $(this).find('input[type="submit"], button[type="submit"]').each(function() {
            const $btn = $(this);
            $btn.prop('disabled', true);
            
            if (!$btn.data('original-value')) {
                $btn.data('original-value', $btn.val() || $btn.text());
            }
            
            $btn.val('Saving...').text('Saving...');
        });
    });
    
    /**
     * Restore button states on page load (in case of validation errors)
     */
    $(window).on('load', function() {
        $('input[type="submit"], button[type="submit"]').each(function() {
            const $btn = $(this);
            if ($btn.data('original-value')) {
                $btn.prop('disabled', false);
                $btn.val($btn.data('original-value')).text($btn.data('original-value'));
            }
        });
    });
    
    /**
     * Handle settings saved message
     */
    if (window.location.search.indexOf('settings-updated=true') > -1) {
        showMessage('Settings saved successfully!', 'success');
    }
    
    /**
     * Auto-focus on GitHub token field if it's empty
     */
    const $tokenField = $('#github_token');
    if ($tokenField.length && $tokenField.val().trim() === '') {
        // Don't auto-focus immediately, wait a bit for the page to settle
        setTimeout(function() {
            if ($(window).scrollTop() < 100) {
                $tokenField.focus();
            }
        }, 500);
    }
    
    /**
     * Show/hide password field toggle
     */
    if ($tokenField.length) {
        const $toggleBtn = $('<button type="button" class="button button-secondary" style="margin-left: 10px;">Show</button>');
        
        $toggleBtn.on('click', function() {
            if ($tokenField.attr('type') === 'password') {
                $tokenField.attr('type', 'text');
                $toggleBtn.text('Hide');
            } else {
                $tokenField.attr('type', 'password');
                $toggleBtn.text('Show');
            }
        });
        
        $tokenField.after($toggleBtn);
    }
    
})(jQuery);
