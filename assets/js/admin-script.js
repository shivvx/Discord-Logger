jQuery(document).ready(function($) {
    // Tab functionality
    $('.wpdl-tab').click(function() {
        var tabId = $(this).data('tab');
        
        $('.wpdl-tab').removeClass('active');
        $('.wpdl-tab-content').removeClass('active');
        
        $(this).addClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Webhook URL validation
    $('#wpdl_webhook_url').on('blur', function() {
        var url = $(this).val();
        var $status = $('#webhook-status');
        
        if (url && !isValidDiscordWebhook(url)) {
            $status.removeClass('success').addClass('error')
                   .text('Invalid Discord webhook URL format');
        } else if (url) {
            $status.removeClass('error').addClass('success')
                   .text('Valid webhook URL format');
        } else {
            $status.removeClass('success error').text('');
        }
    });
    
    // Test connection with improved feedback
    $('#test-connection').click(function() {
        var $button = $(this);
        var $status = $('#test-status');
        var originalText = $button.text();
        
        $button.prop('disabled', true).text('Testing...');
        $status.removeClass('success error').text('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_discord_connection',
                nonce: wpdl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.addClass('success').text('✅ Test message sent successfully!');
                } else {
                    $status.addClass('error').text('❌ Failed: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                $status.addClass('error').text('❌ Connection failed: ' + error);
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Auto-save settings
    $('.wpdl-field input').on('change', function() {
        var $field = $(this);
        var fieldName = $field.attr('name');
        var fieldValue = $field.val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpdl_save_setting',
                setting: fieldName,
                value: fieldValue,
                nonce: wpdl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Settings saved automatically', 'success');
                }
            }
        });
    });
    
    // Load activity logs
    function loadActivityLogs() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpdl_get_logs',
                nonce: wpdl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#activity-logs').html(response.data);
                }
            }
        });
    }
    
    // Clear logs
    $('#clear-logs').click(function() {
        if (confirm('Are you sure you want to clear all logs?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpdl_clear_logs',
                    nonce: wpdl_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        loadActivityLogs();
                        showNotice('Logs cleared successfully', 'success');
                    }
                }
            });
        }
    });
    
    // Export settings
    $('#export-settings').click(function() {
        var settings = {
            webhook_url: $('#wpdl_webhook_url').val(),
            bot_name: $('#wpdl_bot_name').val(),
            avatar_url: $('#wpdl_avatar_url').val()
        };
        
        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(settings, null, 2));
        var downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "discord-logger-settings.json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    });
    
    // Import settings
    $('#import-settings').change(function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var settings = JSON.parse(e.target.result);
                    $('#wpdl_webhook_url').val(settings.webhook_url || '');
                    $('#wpdl_bot_name').val(settings.bot_name || '');
                    $('#wpdl_avatar_url').val(settings.avatar_url || '');
                    showNotice('Settings imported successfully', 'success');
                } catch (error) {
                    showNotice('Invalid settings file', 'error');
                }
            };
            reader.readAsText(file);
        }
    });
    
    // Load logs on page load
    if ($('#activity-logs').length) {
        loadActivityLogs();
    }
    
    // Helper functions
    function isValidDiscordWebhook(url) {
        var pattern = /^https:\/\/(canary\.|ptb\.)?discord(app)?\.com\/api\/webhooks\/\d+\/[\w-]+$/;
        return pattern.test(url);
    }
    
    function showNotice(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wpdl-admin-wrap').prepend($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
});
