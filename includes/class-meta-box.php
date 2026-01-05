<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EchoAds_Meta_Box {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function add_meta_box() {
        add_meta_box(
            'echoads-audio-generation',
            'EchoAds Audio Generation',
            array( $this, 'render_meta_box' ),
            'post',
            'side',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        $audio_generated = get_post_meta( $post->ID, '_echoads_audio_generated', true );
        $api_key = EchoAds_Settings::get_api_key();
        $endpoint = EchoAds_Settings::get_endpoint();

        $has_config = ! empty( $api_key ) && ! empty( $endpoint );
        $post_status = isset( $post->post_status ) ? $post->post_status : '';
        $is_valid_status = in_array( $post_status, array( 'draft', 'publish' ), true );

        wp_nonce_field( 'echoads_generate_audio', 'echoads_generate_audio_nonce' );
        ?>
        <div id="echoads-meta-box">
            <?php if ( ! $has_config ) : ?>
                <div class="echoads-notice echoads-notice-warning">
                    <p><strong>Configuration Required</strong></p>
                    <p>Please configure API key and endpoint in <a href="<?php echo admin_url( 'options-general.php?page=auto-send-plugin' ); ?>" target="_blank">EchoAds Settings</a> before generating audio.</p>
                </div>
            <?php elseif ( ! $is_valid_status ) : ?>
                <div class="echoads-notice echoads-notice-warning">
                    <p><strong>Post Status Invalid</strong></p>
                    <p>Audio can only be generated for posts with "Draft" or "Published" status. Please save your post as a draft or publish it first.</p>
                </div>
                <button type="button" id="echoads-generate-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" disabled>
                    <span class="echoads-btn-icon">🎵</span>
                    Generate Audio Article
                </button>
            <?php elseif ( $audio_generated ) : ?>
                <div class="echoads-notice echoads-notice-success">
                    <p><strong>Audio Generated</strong></p>
                    <p>Audio was generated on <?php echo date( 'M j, Y g:i A', strtotime( $audio_generated ) ); ?></p>
                    <p>The audio player will be displayed on the front-end for this post.</p>
                </div>
                <div id="echoads-status-display" style="display: none; margin-bottom: 12px; padding: 8px 12px; background: #f0f0f1; border-radius: 4px;">
                    <p style="margin: 0;"><strong>Status:</strong> <span id="echoads-audio-status">-</span></p>
                </div>
                <button type="button" id="echoads-check-status-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%; margin-bottom: 12px;">
                    <span class="echoads-btn-icon">🔄</span>
                    Check Audio Article Status
                </button>
                <button type="button" id="echoads-preview-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="display: none;">
                    <span class="echoads-btn-icon">🎧</span>
                    Preview Audio Article
                </button>
                <button type="button" id="echoads-regenerate-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                    Regenerate Audio
                </button>
            <?php else : ?>
                <div class="echoads-notice echoads-notice-info">
                    <p><strong>Generate Audio Article</strong></p>
                    <p>Click the button below to send this post data to the endpoint and generate audio content.</p>
                </div>
                <button type="button" id="echoads-generate-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                    <span class="echoads-btn-icon">🎵</span>
                    Generate Audio Article
                </button>
                <div id="echoads-status-display" style="display: none; margin-top: 12px; margin-bottom: 12px; padding: 8px 12px; background: #f0f0f1; border-radius: 4px;">
                    <p style="margin: 0;"><strong>Status:</strong> <span id="echoads-audio-status">-</span></p>
                </div>
                <button type="button" id="echoads-check-status-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="display: none; width: 100%; margin-bottom: 12px;">
                    <span class="echoads-btn-icon">🔄</span>
                    Check Audio Article Status
                </button>
            <?php endif; ?>

            <div id="echoads-response-message" style="display: none;"></div>

            <div class="echoads-info">
                <p><small><strong>Note:</strong> Only posts with generated audio will display the audio player on the front-end.</small></p>
            </div>
        </div>

        <style>
        #echoads-meta-box {
            font-size: 13px;
        }

        .echoads-notice {
            padding: 8px 12px;
            margin-bottom: 12px;
            border-radius: 4px;
            border-left: 4px solid;
        }

        .echoads-notice-info {
            background: #e7f3ff;
            border-left-color: #0073aa;
            color: #0073aa;
        }

        .echoads-notice-success {
            background: #ecf7ed;
            border-left-color: #46b450;
            color: #46b450;
        }

        .echoads-notice-warning {
            background: #fff8e5;
            border-left-color: #ffb900;
            color: #b26800;
        }

        .echoads-notice-error {
            background: #fbeaea;
            border-left-color: #dc3232;
            color: #dc3232;
        }

        .echoads-notice p {
            margin: 4px 0;
        }

        .echoads-notice p:first-child {
            margin-top: 0;
        }

        .echoads-notice p:last-child {
            margin-bottom: 0;
        }

        #echoads-generate-btn, #echoads-regenerate-btn, #echoads-preview-btn, #echoads-check-status-btn {
            width: 100%;
            padding: 8px 12px;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .echoads-btn-icon {
            margin-right: 6px;
        }

        #echoads-generate-btn:disabled, #echoads-regenerate-btn:disabled, #echoads-preview-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #echoads-response-message {
            margin-top: 12px;
            padding: 8px 12px;
            border-radius: 4px;
        }

        #echoads-response-message.success {
            background: #ecf7ed;
            border-left: 4px solid #46b450;
            color: #46b450;
        }

        #echoads-response-message.error {
            background: #fbeaea;
            border-left: 4px solid #dc3232;
            color: #dc3232;
        }

        .echoads-info {
            border-top: 1px solid #ddd;
            padding-top: 8px;
            margin-top: 12px;
        }

        .echoads-info p {
            margin: 0;
            color: #666;
        }

        .echoads-error-summary {
            margin-bottom: 8px;
        }

        .echoads-error-code {
            font-size: 12px;
            font-weight: 600;
            color: #dc3232;
        }

        .echoads-error-message {
            font-size: 12px;
            color: #666;
        }

        .echoads-toggle-details {
            font-size: 11px;
            color: #0073aa;
            text-decoration: none;
            cursor: pointer;
        }

        .echoads-toggle-details:hover {
            text-decoration: underline;
        }
        </style>
        <?php
    }

    public function enqueue_admin_assets( $hook ) {
        global $post;

        if ( ( $hook === 'post.php' || $hook === 'post-new.php' ) && $post && $post->post_type === 'post' ) {
            wp_enqueue_script( 'jquery' );

            wp_add_inline_script( 'jquery', $this->get_meta_box_script() );

            $post_status = isset( $post->post_status ) ? $post->post_status : '';
            $is_valid_status = in_array( $post_status, array( 'draft', 'publish' ), true );

            wp_localize_script( 'jquery', 'echoads_ajax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'echoads_generate_audio' ),
                'post_status' => $post_status,
                'is_valid_status' => $is_valid_status
            ) );
        }
    }

    private function get_meta_box_script() {
        return "
        jQuery(document).ready(function($) {
            function formatErrorDetails(errorData) {
                var html = '<div class=\"echoads-error-summary\">';
                html += '<strong>' + (errorData.message || 'An error occurred') + '</strong>';
                
                if (errorData.response_code !== null && errorData.response_code !== undefined) {
                    html += '<br><span class=\"echoads-error-code\">HTTP Status: ' + errorData.response_code + '</span>';
                }
                
                if (errorData.error_message) {
                    html += '<br><span class=\"echoads-error-message\">' + errorData.error_message + '</span>';
                }
                
                html += '<br><a href=\"#\" class=\"echoads-toggle-details\">Show Details</a>';
                html += '</div>';
                
                html += '<div class=\"echoads-error-details\" style=\"display: none; margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 4px; font-size: 11px; max-height: 400px; overflow-y: auto;\">';
                
                if (errorData.response_code !== null && errorData.response_code !== undefined) {
                    html += '<div style=\"margin-bottom: 8px;\"><strong>Response Code:</strong> ' + errorData.response_code + '</div>';
                }
                
                if (errorData.response_body) {
                    html += '<div style=\"margin-bottom: 8px;\"><strong>Response Body:</strong>';
                    html += '<pre style=\"white-space: pre-wrap; word-wrap: break-word; background: rgba(0,0,0,0.05); padding: 8px; border-radius: 3px; margin-top: 4px; max-height: 200px; overflow-y: auto;\">';
                    if (errorData.response_body_parsed) {
                        html += JSON.stringify(errorData.response_body_parsed, null, 2);
                    } else {
                        html += \$('<div>').text(errorData.response_body).html();
                    }
                    html += '</pre></div>';
                }
                
                if (errorData.response_headers && Object.keys(errorData.response_headers).length > 0) {
                    html += '<div style=\"margin-bottom: 8px;\"><strong>Response Headers:</strong>';
                    html += '<pre style=\"white-space: pre-wrap; word-wrap: break-word; background: rgba(0,0,0,0.05); padding: 8px; border-radius: 3px; margin-top: 4px; max-height: 150px; overflow-y: auto;\">';
                    html += JSON.stringify(errorData.response_headers, null, 2);
                    html += '</pre></div>';
                }
                
                if (errorData.error_message) {
                    html += '<div style=\"margin-bottom: 8px;\"><strong>Error Message:</strong> ' + errorData.error_message + '</div>';
                }
                
                html += '</div>';
                
                return html;
            }

            $('#echoads-generate-btn, #echoads-regenerate-btn').click(function(e) {
                e.preventDefault();

                var button = $(this);
                var postId = button.data('post-id');
                var isRegenerate = button.attr('id') === 'echoads-regenerate-btn';
                var responseDiv = $('#echoads-response-message');

                // Check post status before proceeding (only for generate button, not regenerate)
                if (!isRegenerate && typeof echoads_ajax !== 'undefined' && !echoads_ajax.is_valid_status) {
                    responseDiv.removeClass('success').addClass('error');
                    responseDiv.html('<strong>Post Status Invalid</strong><br>Audio can only be generated for posts with Draft or Published status. Please save your post as a draft or publish it first.').show();
                    return;
                }

                // Update button state
                button.prop('disabled', true);
                if (isRegenerate) {
                    button.text('Regenerating...');
                } else {
                    button.html('<span class=\"echoads-btn-icon\">⏳</span> Generating...');
                }
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
                            responseDiv.addClass('success').text(response.data.message).show();
                            // Show Check Status button and status display
                            $('#echoads-check-status-btn').show();
                            $('#echoads-status-display').show();
                            // Hide generate button
                            button.hide();
                        } else {
                            responseDiv.addClass('error');
                            var errorHtml = formatErrorDetails(response.data || {});
                            responseDiv.html(errorHtml).show();
                            
                            // Toggle details
                            responseDiv.find('.echoads-toggle-details').click(function(e) {
                                e.preventDefault();
                                var detailsDiv = responseDiv.find('.echoads-error-details');
                                var toggleLink = $(this);
                                if (detailsDiv.is(':visible')) {
                                    detailsDiv.slideUp();
                                    toggleLink.text('Show Details');
                                } else {
                                    detailsDiv.slideDown();
                                    toggleLink.text('Hide Details');
                                }
                            });
                            
                            // Reset button state
                            button.prop('disabled', false);
                            if (isRegenerate) {
                                button.text('Regenerate Audio');
                            } else {
                                button.html('<span class=\"echoads-btn-icon\">🎵</span> Generate Audio Article');
                            }
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
                        
                        // Toggle details
                        responseDiv.find('.echoads-toggle-details').click(function(e) {
                            e.preventDefault();
                            var detailsDiv = responseDiv.find('.echoads-error-details');
                            var toggleLink = $(this);
                            if (detailsDiv.is(':visible')) {
                                detailsDiv.slideUp();
                                toggleLink.text('Show Details');
                            } else {
                                detailsDiv.slideDown();
                                toggleLink.text('Hide Details');
                            }
                        });
                        
                        // Reset button state
                        button.prop('disabled', false);
                        if (isRegenerate) {
                            button.text('Regenerate Audio');
                        } else {
                            button.html('<span class=\"echoads-btn-icon\">🎵</span> Generate Audio Article');
                        }
                    }
                });
            });

            // Function to check audio status
            function checkAudioStatus(postId) {
                var checkStatusBtn = $('#echoads-check-status-btn');
                var statusDisplay = $('#echoads-status-display');
                var statusSpan = $('#echoads-audio-status');
                var previewBtn = $('#echoads-preview-btn');
                var regenerateBtn = $('#echoads-regenerate-btn');
                var responseDiv = $('#echoads-response-message');

                // Update button state
                checkStatusBtn.prop('disabled', true);
                checkStatusBtn.html('<span class=\"echoads-btn-icon\">⏳</span> Checking...');
                statusSpan.text('Checking...');

                $.ajax({
                    url: echoads_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'echoads_check_audio_status',
                        post_id: postId,
                        nonce: echoads_ajax.nonce
                    },
                    success: function(response) {
                        checkStatusBtn.prop('disabled', false);
                        checkStatusBtn.html('<span class=\"echoads-btn-icon\">🔄</span> Check Audio Article Status');

                        if (response.success && response.data && response.data.audioStatus) {
                            var audioStatus = response.data.audioStatus;
                            statusSpan.text(audioStatus);
                            statusDisplay.show();

                            // Update button visibility based on status
                            if (audioStatus === 'COMPLETED') {
                                previewBtn.show();
                                regenerateBtn.prop('disabled', false);
                                responseDiv.removeClass('error').addClass('success').text('Audio generation completed! You can now preview or regenerate.').show();
                            } else if (audioStatus === 'PENDING' || audioStatus === 'PROCESSING') {
                                previewBtn.hide();
                                regenerateBtn.prop('disabled', true);
                                responseDiv.removeClass('error').addClass('success').text('Audio is still being generated. Status: ' + audioStatus + '. Click "Check Audio Article Status" to refresh.').show();
                            } else if (audioStatus === 'FAILED' || audioStatus === 'SKIPPED') {
                                previewBtn.hide();
                                regenerateBtn.prop('disabled', false);
                                responseDiv.removeClass('success').addClass('error').text('Audio generation ' + audioStatus.toLowerCase() + '. You can try regenerating.').show();
                            } else {
                                previewBtn.hide();
                                regenerateBtn.prop('disabled', false);
                            }
                        } else {
                            statusSpan.text('Unknown');
                            responseDiv.removeClass('success').addClass('error');
                            var errorHtml = formatErrorDetails(response.data || {});
                            responseDiv.html(errorHtml).show();
                            
                            // Toggle details
                            responseDiv.find('.echoads-toggle-details').click(function(e) {
                                e.preventDefault();
                                var detailsDiv = responseDiv.find('.echoads-error-details');
                                var toggleLink = $(this);
                                if (detailsDiv.is(':visible')) {
                                    detailsDiv.slideUp();
                                    toggleLink.text('Show Details');
                                } else {
                                    detailsDiv.slideDown();
                                    toggleLink.text('Hide Details');
                                }
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        checkStatusBtn.prop('disabled', false);
                        checkStatusBtn.html('<span class=\"echoads-btn-icon\">🔄</span> Check Audio Article Status');
                        statusSpan.text('Error');
                        
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
                        
                        responseDiv.removeClass('success').addClass('error');
                        var errorHtml = formatErrorDetails(errorData);
                        responseDiv.html(errorHtml).show();
                        
                        // Toggle details
                        responseDiv.find('.echoads-toggle-details').click(function(e) {
                            e.preventDefault();
                            var detailsDiv = responseDiv.find('.echoads-error-details');
                            var toggleLink = $(this);
                            if (detailsDiv.is(':visible')) {
                                detailsDiv.slideUp();
                                toggleLink.text('Show Details');
                            } else {
                                detailsDiv.slideDown();
                                toggleLink.text('Hide Details');
                            }
                        });
                    }
                });
            }

            // Check Status button click handler
            $('#echoads-check-status-btn').click(function(e) {
                e.preventDefault();
                var postId = $(this).data('post-id');
                checkAudioStatus(postId);
            });

            // Preview button - check status before showing preview
            $('#echoads-preview-btn').click(function(e) {
                e.preventDefault();

                var button = $(this);
                var postId = button.data('post-id');
                var responseDiv = $('#echoads-response-message');

                // First check status
                $.ajax({
                    url: echoads_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'echoads_check_audio_status',
                        post_id: postId,
                        nonce: echoads_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.audioStatus === 'COMPLETED') {
                            // Status is COMPLETED, proceed with preview
                            button.prop('disabled', true);
                            button.html('<span class=\"echoads-btn-icon\">⏳</span> Loading...');
                            responseDiv.hide().removeClass('success error').empty();

                            $.ajax({
                                url: echoads_ajax.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'echoads_get_preview_audio',
                                    post_id: postId,
                                    nonce: echoads_ajax.nonce
                                },
                                success: function(previewResponse) {
                                    if (previewResponse.success && previewResponse.data && previewResponse.data.audioUrl) {
                                        // Open audio URL in new tab
                                        window.open(previewResponse.data.audioUrl, '_blank');
                                        responseDiv.addClass('success').text('Preview audio opened in new tab').show();
                                        
                                        // Reset button state after a short delay
                                        setTimeout(function() {
                                            button.prop('disabled', false);
                                            button.html('<span class=\"echoads-btn-icon\">🎧</span> Preview Audio Article');
                                        }, 1000);
                                    } else {
                                        responseDiv.addClass('error');
                                        var errorHtml = formatErrorDetails(previewResponse.data || {});
                                        responseDiv.html(errorHtml).show();
                                        
                                        // Toggle details
                                        responseDiv.find('.echoads-toggle-details').click(function(e) {
                                            e.preventDefault();
                                            var detailsDiv = responseDiv.find('.echoads-error-details');
                                            var toggleLink = $(this);
                                            if (detailsDiv.is(':visible')) {
                                                detailsDiv.slideUp();
                                                toggleLink.text('Show Details');
                                            } else {
                                                detailsDiv.slideDown();
                                                toggleLink.text('Hide Details');
                                            }
                                        });
                                        
                                        // Reset button state
                                        button.prop('disabled', false);
                                        button.html('<span class=\"echoads-btn-icon\">🎧</span> Preview Audio Article');
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
                                    
                                    // Toggle details
                                    responseDiv.find('.echoads-toggle-details').click(function(e) {
                                        e.preventDefault();
                                        var detailsDiv = responseDiv.find('.echoads-error-details');
                                        var toggleLink = $(this);
                                        if (detailsDiv.is(':visible')) {
                                            detailsDiv.slideUp();
                                            toggleLink.text('Show Details');
                                        } else {
                                            detailsDiv.slideDown();
                                            toggleLink.text('Hide Details');
                                        }
                                    });
                                    
                                    // Reset button state
                                    button.prop('disabled', false);
                                    button.html('<span class=\"echoads-btn-icon\">🎧</span> Preview Audio Article');
                                }
                            });
                        } else {
                            // Status is not COMPLETED
                            responseDiv.removeClass('success').addClass('error').text('Audio is not ready yet. Current status: ' + (response.data && response.data.audioStatus ? response.data.audioStatus : 'Unknown')).show();
                        }
                    },
                    error: function(xhr, status, error) {
                        responseDiv.removeClass('success').addClass('error').text('Failed to check audio status: ' + error).show();
                    }
                });
            });

            // Regenerate button - check status before allowing regeneration
            $('#echoads-regenerate-btn').click(function(e) {
                e.preventDefault();

                var button = $(this);
                var postId = button.data('post-id');
                var responseDiv = $('#echoads-response-message');

                // First check status
                $.ajax({
                    url: echoads_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'echoads_check_audio_status',
                        post_id: postId,
                        nonce: echoads_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.audioStatus) {
                            var audioStatus = response.data.audioStatus;
                            
                            // Don't allow regeneration if status is PENDING or PROCESSING
                            if (audioStatus === 'PENDING' || audioStatus === 'PROCESSING') {
                                responseDiv.removeClass('success').addClass('error').text('Cannot regenerate while audio is ' + audioStatus.toLowerCase() + '. Please wait for the current generation to complete.').show();
                                return;
                            }
                        }
                        
                        // Proceed with regeneration
                        button.prop('disabled', true);
                        button.text('Regenerating...');
                        responseDiv.hide().removeClass('success error').empty();

                        $.ajax({
                            url: echoads_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'echoads_generate_audio',
                                post_id: postId,
                                regenerate: 'true',
                                nonce: echoads_ajax.nonce
                            },
                            success: function(regenerateResponse) {
                                if (regenerateResponse.success) {
                                    responseDiv.addClass('success').text(regenerateResponse.data.message).show();
                                    // Show Check Status button and status display
                                    $('#echoads-check-status-btn').show();
                                    $('#echoads-status-display').show();
                                    // Hide preview button until status is COMPLETED
                                    $('#echoads-preview-btn').hide();
                                } else {
                                    responseDiv.addClass('error');
                                    var errorHtml = formatErrorDetails(regenerateResponse.data || {});
                                    responseDiv.html(errorHtml).show();
                                    
                                    // Toggle details
                                    responseDiv.find('.echoads-toggle-details').click(function(e) {
                                        e.preventDefault();
                                        var detailsDiv = responseDiv.find('.echoads-error-details');
                                        var toggleLink = $(this);
                                        if (detailsDiv.is(':visible')) {
                                            detailsDiv.slideUp();
                                            toggleLink.text('Show Details');
                                        } else {
                                            detailsDiv.slideDown();
                                            toggleLink.text('Hide Details');
                                        }
                                    });
                                }
                                
                                // Reset button state
                                button.prop('disabled', false);
                                button.text('Regenerate Audio');
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
                                
                                // Toggle details
                                responseDiv.find('.echoads-toggle-details').click(function(e) {
                                    e.preventDefault();
                                    var detailsDiv = responseDiv.find('.echoads-error-details');
                                    var toggleLink = $(this);
                                    if (detailsDiv.is(':visible')) {
                                        detailsDiv.slideUp();
                                        toggleLink.text('Show Details');
                                    } else {
                                        detailsDiv.slideDown();
                                        toggleLink.text('Hide Details');
                                    }
                                });
                                
                                // Reset button state
                                button.prop('disabled', false);
                                button.text('Regenerate Audio');
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        // If status check fails, still allow regeneration but show warning
                        responseDiv.removeClass('success').addClass('error').text('Could not verify current status. Proceeding with regeneration...').show();
                        
                        // Proceed with regeneration
                        button.prop('disabled', true);
                        button.text('Regenerating...');

                        $.ajax({
                            url: echoads_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'echoads_generate_audio',
                                post_id: postId,
                                regenerate: 'true',
                                nonce: echoads_ajax.nonce
                            },
                            success: function(regenerateResponse) {
                                if (regenerateResponse.success) {
                                    responseDiv.addClass('success').text(regenerateResponse.data.message).show();
                                    $('#echoads-check-status-btn').show();
                                    $('#echoads-status-display').show();
                                    $('#echoads-preview-btn').hide();
                                } else {
                                    responseDiv.addClass('error');
                                    var errorHtml = formatErrorDetails(regenerateResponse.data || {});
                                    responseDiv.html(errorHtml).show();
                                }
                                
                                button.prop('disabled', false);
                                button.text('Regenerate Audio');
                            },
                            error: function(xhr, status, error) {
                                var errorData = {
                                    message: 'An error occurred: ' + error,
                                    error_message: error
                                };
                                
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
                                
                                button.prop('disabled', false);
                                button.text('Regenerate Audio');
                            }
                        });
                    }
                });
            });
        });
        ";
    }
}