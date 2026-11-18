/* global jQuery, echoads_ajax */
jQuery(document).ready(function($) {
    // Error fields can echo backend/post content, so escape before inserting as HTML.
    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    // One delegated handler for every error box's Show/Hide Details link.
    $(document).on('click', '#echoads-response-message .echoads-toggle-details', function(e) {
        e.preventDefault();
        var detailsDiv = $('#echoads-response-message .echoads-error-details');
        var toggleLink = $(this);
        if (detailsDiv.is(':visible')) {
            detailsDiv.slideUp();
            toggleLink.text('Show Details');
        } else {
            detailsDiv.slideDown();
            toggleLink.text('Hide Details');
        }
    });

    function formatErrorDetails(errorData) {
        var html = '<div class="echoads-error-summary">';
        html += '<strong>' + esc(errorData.message || 'An error occurred') + '</strong>';

        if (errorData.response_code !== null && errorData.response_code !== undefined) {
            html += '<br><span class="echoads-error-code">HTTP Status: ' + esc(errorData.response_code) + '</span>';
        }

        if (errorData.error_message) {
            html += '<br><span class="echoads-error-message">' + esc(errorData.error_message) + '</span>';
        }

        html += '<br><a href="#" class="echoads-toggle-details">Show Details</a>';
        html += '</div>';

        html += '<div class="echoads-error-details" style="display: none; margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 4px; font-size: 11px; max-height: 400px; overflow-y: auto;">';

        if (errorData.response_code !== null && errorData.response_code !== undefined) {
            html += '<div style="margin-bottom: 8px;"><strong>Response Code:</strong> ' + esc(errorData.response_code) + '</div>';
        }

        if (errorData.response_body) {
            html += '<div style="margin-bottom: 8px;"><strong>Response Body:</strong>';
            html += '<pre style="white-space: pre-wrap; word-wrap: break-word; background: rgba(0,0,0,0.05); padding: 8px; border-radius: 3px; margin-top: 4px; max-height: 200px; overflow-y: auto;">';
            if (errorData.response_body_parsed) {
                html += esc(JSON.stringify(errorData.response_body_parsed, null, 2));
            } else {
                html += esc(errorData.response_body);
            }
            html += '</pre></div>';
        }

        if (errorData.response_headers && Object.keys(errorData.response_headers).length > 0) {
            html += '<div style="margin-bottom: 8px;"><strong>Response Headers:</strong>';
            html += '<pre style="white-space: pre-wrap; word-wrap: break-word; background: rgba(0,0,0,0.05); padding: 8px; border-radius: 3px; margin-top: 4px; max-height: 150px; overflow-y: auto;">';
            html += esc(JSON.stringify(errorData.response_headers, null, 2));
            html += '</pre></div>';
        }

        if (errorData.error_message) {
            html += '<div style="margin-bottom: 8px;"><strong>Error Message:</strong> ' + esc(errorData.error_message) + '</div>';
        }

        html += '</div>';

        return html;
    }

    $('#echoads-preview-btn').click(function(e) {
        e.preventDefault();

        var button = $(this);
        var postId = button.data('post-id');
        var responseDiv = $('#echoads-response-message');

        // Update button state
        button.prop('disabled', true);
        button.html('<span class="echoads-btn-icon">⏳</span> Loading...');
        responseDiv.hide().removeClass('success error').empty();

        $.ajax({
            url: echoads_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'echoads_get_preview_audio',
                post_id: postId,
                nonce: echoads_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.audioUrl) {
                    // Open audio URL in new tab
                    window.open(response.data.audioUrl, '_blank');
                    responseDiv.addClass('success').text('Preview audio opened in new tab').show();

                    // Reset button state after a short delay
                    setTimeout(function() {
                        button.prop('disabled', false);
                        button.html('<span class="echoads-btn-icon">🎧</span> Preview Audio Article Track');
                    }, 1000);
                } else {
                    responseDiv.addClass('error');
                    var errorHtml = formatErrorDetails(response.data || {});
                    responseDiv.html(errorHtml).show();

                    // Reset button state
                    button.prop('disabled', false);
                    button.html('<span class="echoads-btn-icon">🎧</span> Preview Audio Article Track');
                }
            },
            error: function(xhr, status, error) {
                var errorData = {
                    message: 'An error occurred: ' + error,
                    error_message: error
                };

                // Try to parse response if available
                if (xhr.responseText) {
                    try {
                        var parsedResponse = JSON.parse(xhr.responseText);
                        if (parsedResponse.data) {
                            errorData = $.extend(errorData, parsedResponse.data);
                        }
                    } catch(e) {
                        errorData.response_body = xhr.responseText;
                    }
                }

                responseDiv.addClass('error');
                var errorHtml = formatErrorDetails(errorData);
                responseDiv.html(errorHtml).show();

                // Reset button state
                button.prop('disabled', false);
                button.html('<span class="echoads-btn-icon">🎧</span> Preview Audio Article Track');
            }
        });
    });

    // Check Status button handler
    $(document).on('click', '#echoads-check-status-btn', function(e, isAutoCheck) {
        e.preventDefault();

        var button = $(this);
        var postId = button.data('post-id');
        var responseDiv = $('#echoads-response-message');

        // Not after the automatic check on page load: the editor may already be typing.
        function reloadToShowStatus() {
            if (isAutoCheck) {
                button.hide();
                responseDiv.append('<br>Reload the page to see the updated options.');
                return;
            }
            setTimeout(function() {
                location.reload();
            }, 1500);
        }

        // Update button state
        button.prop('disabled', true);
        button.text('Checking...');
        responseDiv.hide().removeClass('success error').empty();

        $.ajax({
            url: echoads_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'echoads_check_audio_status',
                post_id: postId,
                nonce: echoads_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.status) {
                    var status = response.data.status;
                    var statusMessage = 'Status: ' + esc(status);

                    if (status === 'COMPLETED') {
                        responseDiv.addClass('success').html('<strong>Audio Generation Complete!</strong><br>' + statusMessage).show();
                        reloadToShowStatus();
                    } else if (status === 'PENDING' || status === 'PROCESSING') {
                        responseDiv.addClass('info').html('<strong>Audio Still Processing</strong><br>' + statusMessage + '<br>Please check again later.').show();
                        button.prop('disabled', false);
                        button.text('Check Audio Article Status');
                    } else if (status === 'FAILED' || status === 'SKIPPED') {
                        responseDiv.addClass('error').html('<strong>Audio Generation ' + esc(status) + '</strong><br>' + statusMessage + '<br>Audio generation failed. The post will attempt to generate audio again when republished.').show();
                        reloadToShowStatus();
                    } else {
                        responseDiv.addClass('info').html('<strong>Status Checked</strong><br>' + statusMessage).show();
                        button.prop('disabled', false);
                        button.text('Check Audio Article Status');
                    }
                } else {
                    responseDiv.addClass('error').html('<strong>Error</strong><br>' + esc((response.data && response.data.message) || 'Failed to check status')).show();
                    button.prop('disabled', false);
                    button.text('Check Audio Article Status');
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = 'An error occurred: ' + error;
                if (xhr.responseText) {
                    try {
                        var parsedResponse = JSON.parse(xhr.responseText);
                        if (parsedResponse.data && parsedResponse.data.message) {
                            errorMsg = parsedResponse.data.message;
                        }
                    } catch(e) {
                        // Use default error message
                    }
                }
                responseDiv.addClass('error').html('<strong>Error</strong><br>' + esc(errorMsg)).show();
                button.prop('disabled', false);
                button.text('Check Audio Article Status');
            }
        });
    });

    // Covers sites where WP-Cron doesn't run, so nobody has to click Check Status.
    $('#echoads-check-status-btn[data-auto-check]').trigger('click', [true]);

    // Regenerate Audio / Generate Audio (send now) button handler
    $(document).on('click', '#echoads-regenerate-btn, #echoads-generate-btn', function(e) {
        e.preventDefault();

        var button = $(this);
        var postId = button.data('post-id');
        var responseDiv = $('#echoads-response-message');
        var isRegenerate = button.is('#echoads-regenerate-btn');
        var label = button.text();

        // Update button state
        button.prop('disabled', true);
        button.text(isRegenerate ? 'Regenerating...' : 'Sending...');
        responseDiv.hide().removeClass('success error').empty();

        $.ajax({
            url: echoads_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'echoads_generate_audio',
                post_id: postId,
                regenerate: isRegenerate ? 'true' : 'false',
                nonce: echoads_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Immediately update UI to show pending/processing state
                    var metaBox = $('#echoads-meta-box');

                    // Hide preview, regenerate and generate buttons
                    $('#echoads-preview-btn').hide();
                    $('#echoads-regenerate-btn').hide();
                    $('#echoads-generate-btn').hide();

                    // Create or show check status button
                    var checkStatusBtn = $('#echoads-check-status-btn');
                    if (checkStatusBtn.length === 0) {
                        checkStatusBtn = $('<button type="button" id="echoads-check-status-btn" class="button button-primary" data-post-id="' + esc(postId) + '" style="width: 100%;">Check Audio Article Status</button>');
                        metaBox.append(checkStatusBtn);
                    } else {
                        checkStatusBtn.show();
                    }

                    // Update notice to show pending state
                    var existingNotice = metaBox.find('.echoads-notice');
                    if (existingNotice.length > 0) {
                        existingNotice.removeClass('echoads-notice-success echoads-notice-error echoads-notice-warning')
                                     .addClass('echoads-notice-info')
                                     .html('<p><strong>Audio Generation In Progress</strong></p><p>Status: <strong>PENDING</strong></p><p>Please check the status to see when audio generation is complete.</p>');
                    } else {
                        var noticeHtml = '<div class="echoads-notice echoads-notice-info"><p><strong>Audio Generation In Progress</strong></p><p>Status: <strong>PENDING</strong></p><p>Please check the status to see when audio generation is complete.</p></div>';
                        metaBox.prepend(noticeHtml);
                    }

                    responseDiv.addClass('success').html('<strong>' + (isRegenerate ? 'Audio Regeneration Initiated' : 'Audio Generation Initiated') + '</strong><br>' + esc(response.data.message || 'Audio generation has been started. Please check the status to see when it completes.')).show();

                    // Reload as fallback to sync with server state (longer delay since UI is already updated)
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    responseDiv.addClass('error');
                    var errorHtml = formatErrorDetails(response.data || {});
                    responseDiv.html(errorHtml).show();

                    // Reset button state
                    button.prop('disabled', false);
                    button.text(label);
                }
            },
            error: function(xhr, status, error) {
                var errorData = {
                    message: 'An error occurred: ' + error,
                    error_message: error
                };

                // Try to parse response if available
                if (xhr.responseText) {
                    try {
                        var parsedResponse = JSON.parse(xhr.responseText);
                        if (parsedResponse.data) {
                            errorData = $.extend(errorData, parsedResponse.data);
                        }
                    } catch(e) {
                        errorData.response_body = xhr.responseText;
                    }
                }

                responseDiv.addClass('error');
                var errorHtml = formatErrorDetails(errorData);
                responseDiv.html(errorHtml).show();

                // Reset button state
                button.prop('disabled', false);
                button.text(label);
            }
        });
    });
});
