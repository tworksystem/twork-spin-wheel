/**
 * Modern Admin JavaScript for T-Work Spin Wheel
 * Enhanced with animations and interactions
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // ============================================
        // Initialize Admin Interface
        // ============================================
        initAdminInterface();
        
        // ============================================
        // Bulk Actions Handler
        // ============================================
        $('.bulk-action-button').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var action = $button.data('action');
            var selected = $('input[name="spin_ids[]"]:checked, input[name="prize_ids[]"]:checked');
            
            if (selected.length === 0) {
                showNotification('Please select items to perform this action.', 'warning');
                return;
            }
            
            var ids = [];
            selected.each(function() {
                ids.push($(this).val());
            });
            
            if (confirm(tworkSpinWheelAdmin.strings?.confirmAction || 'Are you sure?')) {
                $button.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: tworkSpinWheelAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'twork_spin_wheel_bulk_action',
                        bulk_action: action,
                        ids: ids,
                        nonce: tworkSpinWheelAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Action completed successfully!', 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification(response.data?.message || 'Error occurred', 'error');
                            $button.prop('disabled', false).text($button.data('original-text') || 'Apply');
                        }
                    },
                    error: function() {
                        showNotification('An error occurred. Please try again.', 'error');
                        $button.prop('disabled', false).text($button.data('original-text') || 'Apply');
                    }
                });
            }
        });

        // ============================================
        // Auto-refresh Analytics
        // ============================================
        if ($('.twork-analytics-container').length) {
            setInterval(function() {
                refreshAnalytics();
            }, 300000); // Every 5 minutes
        }

        // ============================================
        // Form Validation Enhancement
        // ============================================
        $('form').on('submit', function(e) {
            var $form = $(this);
            var isValid = true;
            
            $form.find('input[required], select[required], textarea[required]').each(function() {
                var $field = $(this);
                if (!$field.val().trim()) {
                    isValid = false;
                    $field.addClass('error');
                    showFieldError($field, 'This field is required.');
                } else {
                    $field.removeClass('error');
                    hideFieldError($field);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Please fill in all required fields.', 'warning');
            }
        });

        // ============================================
        // Real-time Field Validation
        // ============================================
        $('input, select, textarea').on('blur', function() {
            var $field = $(this);
            validateField($field);
        });

        // ============================================
        // Color Picker Enhancement
        // ============================================
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker({
                change: function(event, ui) {
                    $(this).trigger('change');
                }
            });
        }

        // ============================================
        // Template Card Interactions
        // ============================================
        $('.twork-template-card').on('mouseenter', function() {
            $(this).addClass('hover');
        }).on('mouseleave', function() {
            $(this).removeClass('hover');
        });

        // ============================================
        // Health Check Refresh
        // ============================================
        $('.twork-health-container .button').on('click', function() {
            var $button = $(this);
            if ($button.data('action') === 'refresh') {
                $button.prop('disabled', true).text('Checking...');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        });

        // ============================================
        // Smooth Scroll to Errors
        // ============================================
        if ($('.notice-error').length) {
            $('html, body').animate({
                scrollTop: $('.notice-error').first().offset().top - 100
            }, 500);
        }

        // ============================================
        // Tab Switching Animation
        // ============================================
        $('.nav-tab').on('click', function() {
            var $tab = $(this);
            $tab.addClass('loading');
            
            setTimeout(function() {
                $tab.removeClass('loading');
            }, 300);
        });

        // ============================================
        // Statistics Counter Animation
        // ============================================
        animateCounters();

        // ============================================
        // Tooltip Initialization
        // ============================================
        initTooltips();

        // ============================================
        // Copy to Clipboard
        // ============================================
        $('.copy-to-clipboard').on('click', function(e) {
            e.preventDefault();
            var text = $(this).data('copy') || $(this).text();
            copyToClipboard(text);
            showNotification('Copied to clipboard!', 'success');
        });

    });

    // ============================================
    // Initialize Admin Interface
    // ============================================
    function initAdminInterface() {
        // Add loading states
        $('form').on('submit', function() {
            var $form = $(this);
            var $submit = $form.find('input[type="submit"], button[type="submit"]');
            $submit.prop('disabled', true).data('original-text', $submit.val() || $submit.text());
            if ($submit.is('input')) {
                $submit.val('Processing...');
            } else {
                $submit.text('Processing...');
            }
        });

        // Add fade-in animation to content
        $('.twork-tab-content').hide().fadeIn(500);
    }

    // ============================================
    // Show Notification
    // ============================================
    function showNotification(message, type) {
        type = type || 'info';
        var $notification = $('<div class="twork-notification twork-notification-' + type + '">' + message + '</div>');
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }

    // ============================================
    // Refresh Analytics
    // ============================================
    function refreshAnalytics() {
        $.ajax({
            url: tworkSpinWheelAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'twork_spin_wheel_get_analytics',
                nonce: tworkSpinWheelAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateAnalyticsDisplay(response.data);
                }
            }
        });
    }

    // ============================================
    // Update Analytics Display
    // ============================================
    function updateAnalyticsDisplay(data) {
        $('.stat-card').each(function() {
            var $card = $(this);
            var statType = $card.data('stat-type');
            if (data[statType] !== undefined) {
                var $value = $card.find('p, .stat-value');
                animateValue($value, parseInt($value.text().replace(/[^0-9]/g, '')), data[statType], 1000);
            }
        });
    }

    // ============================================
    // Animate Value Counter
    // ============================================
    function animateValue($element, start, end, duration) {
        var startTimestamp = null;
        var step = function(timestamp) {
            if (!startTimestamp) startTimestamp = timestamp;
            var progress = Math.min((timestamp - startTimestamp) / duration, 1);
            var current = Math.floor(progress * (end - start) + start);
            $element.text(current.toLocaleString());
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // ============================================
    // Animate Counters on Load
    // ============================================
    function animateCounters() {
        $('.stat-card p, .stat-value').each(function() {
            var $this = $(this);
            var text = $this.text();
            var number = parseInt(text.replace(/[^0-9]/g, ''));
            if (!isNaN(number) && number > 0) {
                $this.text('0');
                animateValue($this, 0, number, 2000);
            }
        });
    }

    // ============================================
    // Validate Field
    // ============================================
    function validateField($field) {
        var value = $field.val();
        var type = $field.attr('type');
        var isValid = true;
        var errorMessage = '';

        if ($field.prop('required') && !value.trim()) {
            isValid = false;
            errorMessage = 'This field is required.';
        } else if (type === 'email' && value && !isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address.';
        } else if (type === 'url' && value && !isValidUrl(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid URL.';
        } else if ($field.attr('min') && parseFloat(value) < parseFloat($field.attr('min'))) {
            isValid = false;
            errorMessage = 'Value must be at least ' + $field.attr('min') + '.';
        } else if ($field.attr('max') && parseFloat(value) > parseFloat($field.attr('max'))) {
            isValid = false;
            errorMessage = 'Value must be at most ' + $field.attr('max') + '.';
        }

        if (isValid) {
            $field.removeClass('error').addClass('valid');
            hideFieldError($field);
        } else {
            $field.removeClass('valid').addClass('error');
            showFieldError($field, errorMessage);
        }

        return isValid;
    }

    // ============================================
    // Show Field Error
    // ============================================
    function showFieldError($field, message) {
        hideFieldError($field);
        var $error = $('<span class="field-error">' + message + '</span>');
        $field.after($error);
        $error.fadeIn(300);
    }

    // ============================================
    // Hide Field Error
    // ============================================
    function hideFieldError($field) {
        $field.siblings('.field-error').fadeOut(300, function() {
            $(this).remove();
        });
    }

    // ============================================
    // Email Validation
    // ============================================
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // ============================================
    // URL Validation
    // ============================================
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }

    // ============================================
    // Initialize Tooltips
    // ============================================
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            var $this = $(this);
            var tooltip = $this.data('tooltip');
            $this.on('mouseenter', function() {
                showTooltip($this, tooltip);
            }).on('mouseleave', function() {
                hideTooltip();
            });
        });
    }

    // ============================================
    // Show Tooltip
    // ============================================
    function showTooltip($element, text) {
        var $tooltip = $('<div class="twork-tooltip">' + text + '</div>');
        $('body').append($tooltip);
        
        var offset = $element.offset();
        $tooltip.css({
            top: offset.top - $tooltip.outerHeight() - 10,
            left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
        }).fadeIn(200);
    }

    // ============================================
    // Hide Tooltip
    // ============================================
    function hideTooltip() {
        $('.twork-tooltip').fadeOut(200, function() {
            $(this).remove();
        });
    }

    // ============================================
    // Copy to Clipboard
    // ============================================
    function copyToClipboard(text) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
    }

    // ============================================
    // Advanced Modal System
    // ============================================
    window.tworkModal = {
        show: function(title, content, buttons) {
            var modalHtml = '<div class="twork-modal-overlay show">' +
                '<div class="twork-modal">' +
                '<div class="twork-modal-header">' +
                '<h2 class="twork-modal-title">' + title + '</h2>' +
                '<button class="twork-modal-close" aria-label="Close">&times;</button>' +
                '</div>' +
                '<div class="twork-modal-body">' + content + '</div>';
            
            if (buttons && buttons.length > 0) {
                modalHtml += '<div class="twork-modal-footer">';
                buttons.forEach(function(button) {
                    modalHtml += '<button class="button ' + (button.primary ? 'button-primary' : 'button-secondary') + '" data-action="' + (button.action || '') + '">' + button.text + '</button>';
                });
                modalHtml += '</div>';
            }
            
            modalHtml += '</div></div>';
            
            $('body').append(modalHtml);
            
            var $modal = $('.twork-modal-overlay');
            
            $modal.find('.twork-modal-close, .twork-modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    tworkModal.hide();
                }
            });
            
            if (buttons) {
                $modal.find('.twork-modal-footer button').on('click', function() {
                    var action = $(this).data('action');
                    if (action && typeof window['tworkModalAction_' + action] === 'function') {
                        window['tworkModalAction_' + action]();
                    }
                    tworkModal.hide();
                });
            }
            
            // ESC key to close
            $(document).on('keydown.modal', function(e) {
                if (e.key === 'Escape') {
                    tworkModal.hide();
                }
            });
        },
        
        hide: function() {
            $('.twork-modal-overlay').removeClass('show');
            setTimeout(function() {
                $('.twork-modal-overlay').remove();
            }, 300);
            $(document).off('keydown.modal');
        }
    };

    // ============================================
    // Advanced Toast Notification System
    // ============================================
    window.tworkToast = {
        show: function(message, type, duration) {
            type = type || 'info';
            duration = duration || 5000;
            
            if (!$('.twork-toast-container').length) {
                $('body').append('<div class="twork-toast-container"></div>');
            }
            
            var icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };
            
            var toastHtml = '<div class="twork-toast ' + type + '">' +
                '<div class="twork-toast-content">' +
                '<div class="twork-toast-icon">' + (icons[type] || 'ℹ') + '</div>' +
                '<div class="twork-toast-message">' + message + '</div>' +
                '<button class="twork-toast-close" aria-label="Close">&times;</button>' +
                '</div>' +
                '</div>';
            
            var $toast = $(toastHtml);
            $('.twork-toast-container').append($toast);
            
            setTimeout(function() {
                $toast.addClass('show');
            }, 100);
            
            $toast.find('.twork-toast-close').on('click', function() {
                tworkToast.hide($toast);
            });
            
            setTimeout(function() {
                tworkToast.hide($toast);
            }, duration);
        },
        
        hide: function($toast) {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        },
        
        success: function(message, duration) {
            this.show(message, 'success', duration);
        },
        
        error: function(message, duration) {
            this.show(message, 'error', duration);
        },
        
        warning: function(message, duration) {
            this.show(message, 'warning', duration);
        },
        
        info: function(message, duration) {
            this.show(message, 'info', duration);
        }
    };

    // ============================================
    // Enhanced Show Notification (Backward Compatible)
    // ============================================
    function showNotification(message, type) {
        tworkToast.show(message, type);
    }

    // ============================================
    // Advanced Progress Bar
    // ============================================
    window.tworkProgress = {
        create: function(container, label, value) {
            var progressHtml = '<div class="twork-progress-container">' +
                '<div class="twork-progress-label">' +
                '<span>' + label + '</span>' +
                '<span class="twork-progress-value">' + value + '%</span>' +
                '</div>' +
                '<div class="twork-progress-bar" style="width: ' + value + '%"></div>' +
                '</div>';
            
            $(container).append(progressHtml);
            return $(container).find('.twork-progress-bar');
        },
        
        update: function($bar, value) {
            $bar.css('width', value + '%');
            $bar.closest('.twork-progress-container').find('.twork-progress-value').text(value + '%');
        }
    };

    // ============================================
    // Advanced Tooltip System
    // ============================================
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            var $this = $(this);
            var tooltip = $this.data('tooltip');
            var position = $this.data('tooltip-position') || 'top';
            
            if (!$this.hasClass('twork-tooltip-wrapper')) {
                $this.addClass('twork-tooltip-wrapper');
            }
            
            if (!$this.find('.twork-tooltip').length) {
                $this.append('<div class="twork-tooltip ' + position + '">' + tooltip + '</div>');
            }
        });
    }

    // ============================================
    // Advanced Copy to Clipboard with Feedback
    // ============================================
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                tworkToast.success('Copied to clipboard!', 2000);
            }).catch(function() {
                fallbackCopyToClipboard(text);
            });
        } else {
            fallbackCopyToClipboard(text);
        }
    }

    function fallbackCopyToClipboard(text) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        try {
            document.execCommand('copy');
            tworkToast.success('Copied to clipboard!', 2000);
        } catch (err) {
            tworkToast.error('Failed to copy to clipboard', 3000);
        }
        $temp.remove();
    }

    // ============================================
    // Advanced Form Auto-save
    // ============================================
    var autoSaveTimer;
    $('form.twork-settings-form input, form.twork-settings-form select, form.twork-settings-form textarea').on('change', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Auto-save functionality can be added here
            console.log('Form changed - ready for auto-save');
        }, 2000);
    });

    // ============================================
    // Advanced Keyboard Shortcuts
    // ============================================
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            var $form = $('form.twork-settings-form');
            if ($form.length) {
                $form.submit();
                tworkToast.info('Saving...', 1000);
            }
        }
        
        // Ctrl/Cmd + K to search (if search functionality exists)
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            // Search functionality can be added here
        }
    });

    // ============================================
    // Advanced Live Preview
    // ============================================
    $('.twork-preview-button').on('click', function() {
        var formData = $('form.twork-settings-form').serialize();
        tworkModal.show(
            'Preview',
            '<div class="twork-preview-container" style="text-align: center; padding: 40px;">' +
            '<div class="twork-spinner"></div>' +
            '<p>Loading preview...</p>' +
            '</div>',
            [{
                text: 'Close',
                action: 'close'
            }]
        );
        
        // Preview functionality can be implemented here
        setTimeout(function() {
            $('.twork-preview-container').html('<p>Preview functionality coming soon!</p>');
        }, 1000);
    });

    // ============================================
    // Advanced Statistics Dashboard
    // ============================================
    function updateStatisticsDashboard() {
        if ($('.twork-stats-grid').length) {
            $.ajax({
                url: tworkSpinWheelAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'twork_spin_wheel_get_dashboard_stats',
                    nonce: tworkSpinWheelAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        updateStatsCards(response.data);
                    }
                }
            });
        }
    }

    function updateStatsCards(data) {
        $('.stat-card').each(function() {
            var $card = $(this);
            var statType = $card.data('stat-type');
            if (data[statType] !== undefined) {
                var $value = $card.find('p, .stat-value');
                var currentValue = parseInt($value.text().replace(/[^0-9]/g, '')) || 0;
                animateValue($value, currentValue, data[statType], 1000);
            }
        });
    }

    // Auto-refresh dashboard every 30 seconds
    if ($('.twork-analytics-container').length) {
        setInterval(updateStatisticsDashboard, 30000);
    }

})(jQuery);

// ============================================
// Notification Styles (Inline)
// ============================================
jQuery(document).ready(function($) {
    if (!$('#twork-notification-styles').length) {
        $('head').append('<style id="twork-notification-styles">' +
            '.twork-notification { position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 8px; color: #fff; font-weight: 600; z-index: 100000; opacity: 0; transform: translateX(400px); transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }' +
            '.twork-notification.show { opacity: 1; transform: translateX(0); }' +
            '.twork-notification-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }' +
            '.twork-notification-error { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }' +
            '.twork-notification-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }' +
            '.twork-notification-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }' +
            '.field-error { display: block; color: #ef4444; font-size: 12px; margin-top: 5px; font-weight: 600; }' +
            '.twork-tooltip { position: absolute; background: #1f2937; color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 12px; z-index: 100001; pointer-events: none; white-space: nowrap; }' +
            '.twork-tooltip::after { content: ""; position: absolute; top: 100%; left: 50%; transform: translateX(-50%); border: 5px solid transparent; border-top-color: #1f2937; }' +
            'input.error, select.error, textarea.error { border-color: #ef4444 !important; }' +
            'input.valid, select.valid, textarea.valid { border-color: #10b981 !important; }' +
        '</style>');
    }
});
