jQuery(document).ready(function($) {
    'use strict';

    // Tab functionality
    $('.wpdl-tab').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Remove active class from all tabs and content
        $('.wpdl-tab').removeClass('active');
        $('.wpdl-tab-content').removeClass('active');
        
        // Add active class to clicked tab and corresponding content
        $(this).addClass('active');
        $('#' + tabId).addClass('active');
        
        // Load logs if logs tab is clicked
        if (tabId === 'logs') {
            loadActivityLogs();
        }
    });

    // Test Discord connection
    $('#test-connection').on('click', function() {
        var $button = $(this);
        var $status = $('#test-status');
        
        $button.prop('disabled', true).text('Testing...');
        $status.removeClass('success error').addClass('loading').show().html('<span class="wpdl-loading"></span> Testing connection...');
        
        $.ajax({
            url: wpdl_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'test_discord_connection',
                nonce: wpdl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.removeClass('loading').addClass('success').html('<span class="wpdl-icon-success"></span>' + response.data);
                } else {
                    $status.removeClass('loading').addClass('error').html('<span class="wpdl-icon-error"></span>' + response.data);
                }
            },
            error: function() {
                $status.removeClass('loading').addClass('error').html('<span class="wpdl-icon-error"></span>Connection failed. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).text('Send Test Message');
            }
        });
    });

    // Save individual settings
    $('.wpdl-field input').on('blur', function() {
        var $input = $(this);
        var setting = $input.attr('name');
        var value = $input.val();
        
        if (setting && value !== $input.data('original-value')) {
            saveIndividualSetting(setting, value, $input);
        }
    });

    // Store original values
    $('.wpdl-field input').each(function() {
        $(this).data('original-value', $(this).val());
    });

    // Load activity logs
    function loadActivityLogs() {
        var $container = $('#activity-logs');
        
        $container.html('<div class="wpdl-loading"></div> Loading logs...');
        
        $.ajax({
            url: wpdl_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'wpdl_get_logs',
                nonce: wpdl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $container.html(response.data);
                } else {
                    $container.html('<p class="error">Failed to load logs: ' + response.data + '</p>');
                }
            },
            error: function() {
                $container.html('<p class="error">Failed to load logs. Please try again.</p>');
            }
        });
    }

    // Clear logs
    $('#clear-logs').on('click', function() {
        if (!confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
            return;
        }
        
        var $button = $(this);
        var $container = $('#activity-logs');
        
        $button.prop('disabled', true).text('Clearing...');
        
        $.ajax({
            url: wpdl_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'wpdl_clear_logs',
                nonce: wpdl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $container.html('<p>All logs have been cleared.</p>');
                    showNotification('Logs cleared successfully', 'success');
                } else {
                    showNotification('Failed to clear logs: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotification('Failed to clear logs. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Clear Logs');
            }
        });
    });

    // Save individual setting
    function saveIndividualSetting(setting, value, $input) {
        $.ajax({
            url: wpdl_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'wpdl_save_setting',
                setting: setting,
                value: value,
                nonce: wpdl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $input.data('original-value', value);
                    showNotification('Setting saved', 'success');
                } else {
                    showNotification('Failed to save setting: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotification('Failed to save setting. Please try again.', 'error');
            }
        });
    }

    // Export settings
    $('#export-settings').on('click', function() {
        var settings = {
            webhook_url: $('#wpdl_webhook_url').val(),
            bot_name: $('#wpdl_bot_name').val(),
            avatar_url: $('#wpdl_avatar_url').val(),
            export_date: new Date().toISOString()
        };
        
        var dataStr = JSON.stringify(settings, null, 2);
        var dataBlob = new Blob([dataStr], {type: 'application/json'});
        var url = URL.createObjectURL(dataBlob);
        
        var link = document.createElement('a');
        link.href = url;
        link.download = 'discord-logger-settings-' + new Date().toISOString().split('T')[0] + '.json';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        
        showNotification('Settings exported successfully', 'success');
    });

    // Import settings
    $('#import-settings').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var settings = JSON.parse(e.target.result);
                
                if (settings.webhook_url) $('#wpdl_webhook_url').val(settings.webhook_url);
                if (settings.bot_name) $('#wpdl_bot_name').val(settings.bot_name);
                if (settings.avatar_url) $('#wpdl_avatar_url').val(settings.avatar_url);
                
                showNotification('Settings imported successfully. Please save the form to apply changes.', 'success');
            } catch (error) {
                showNotification('Invalid settings file format', 'error');
            }
        };
        reader.readAsText(file);
    });

    // Webhook URL validation
    $('#wpdl_webhook_url').on('input', function() {
        var $input = $(this);
        var $status = $('#webhook-status');
        var url = $input.val();
        
        if (!url) {
            $status.hide();
            return;
        }
        
        var discordWebhookPattern = /^https:\/\/(canary\.|ptb\.)?discord(app)?\.com\/api\/webhooks\/\d+\/[\w-]+$/;
        
        if (discordWebhookPattern.test(url)) {
            $status.removeClass('error').addClass('success').show().html('<span class="wpdl-icon-success"></span>Valid Discord webhook URL');
        } else {
            $status.removeClass('success').addClass('error').show().html('<span class="wpdl-icon-error"></span>Invalid Discord webhook URL format');
        }
    });

    // Avatar URL validation
    $('#wpdl_avatar_url').on('input', function() {
        var $input = $(this);
        var url = $input.val();
        
        if (!url) return;
        
        if (!url.startsWith('https://')) {
            showNotification('Avatar URL must start with https://', 'error');
        }
    });

    // Show notification
    function showNotification(message, type) {
        var $notification = $('<div class="wpdl-notification wpdl-notification-' + type + '">' + message + '</div>');
        
        $('body').append($notification);
        
        $notification.fadeIn(300);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Auto-refresh logs every 30 seconds if logs tab is active
    setInterval(function() {
        if ($('#logs').hasClass('active')) {
            loadActivityLogs();
        }
    }, 30000);

    // Form validation before submit
    $('form').on('submit', function(e) {
        var webhookUrl = $('#wpdl_webhook_url').val();
        var discordWebhookPattern = /^https:\/\/(canary\.|ptb\.)?discord(app)?\.com\/api\/webhooks\/\d+\/[\w-]+$/;
        
        if (webhookUrl && !discordWebhookPattern.test(webhookUrl)) {
            e.preventDefault();
            showNotification('Please enter a valid Discord webhook URL', 'error');
            $('#wpdl_webhook_url').focus();
            return false;
        }
        
        var avatarUrl = $('#wpdl_avatar_url').val();
        if (avatarUrl && !avatarUrl.startsWith('https://')) {
            e.preventDefault();
            showNotification('Avatar URL must start with https://', 'error');
            $('#wpdl_avatar_url').focus();
            return false;
        }
    });

    // Initialize webhook URL validation on page load
    $('#wpdl_webhook_url').trigger('input');

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save settings
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            $('form input[type="submit"]').click();
        }
        
        // Ctrl/Cmd + T to test connection
        if ((e.ctrlKey || e.metaKey) && e.which === 84) {
            e.preventDefault();
            $('#test-connection').click();
        }
    });

    // Tooltip functionality for help text
    $('.description').each(function() {
        $(this).attr('title', $(this).text());
    });

    // Copy webhook URL to clipboard (if supported)
    if (navigator.clipboard) {
        $('#wpdl_webhook_url').after('<button type="button" class="button button-small" id="copy-webhook" style="margin-left: 10px;">Copy</button>');
        
        $('#copy-webhook').on('click', function() {
            var webhookUrl = $('#wpdl_webhook_url').val();
            if (webhookUrl) {
                navigator.clipboard.writeText(webhookUrl).then(function() {
                    showNotification('Webhook URL copied to clipboard', 'success');
                });
            }
        });
    }
});

// Add notification styles dynamically
jQuery(document).ready(function($) {
    var notificationStyles = `
        <style>
        .wpdl-notification {
            position: fixed;
            top: 32px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 4px;
            color: #fff;
            font-weight: 500;
            z-index: 999999;
            display: none;
            max-width: 300px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .wpdl-notification-success {
            background: #28a745;
        }
        
        .wpdl-notification-error {
            background: #dc3545;
        }
        
        .wpdl-notification-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .wpdl-notification-info {
            background: #17a2b8;
        }
        </style>
    `;
    
    $('head').append(notificationStyles);
});
