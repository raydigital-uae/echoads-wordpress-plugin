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
        $audio_requested = get_post_meta( $post->ID, '_echoads_audio_requested', true );
        $audio_status = get_post_meta( $post->ID, '_echoads_audio_status', true );
        $api_key = EchoAds_Settings::get_api_key();
        $endpoint = EchoAds_Settings::get_endpoint();

        $has_config = ! empty( $api_key ) && ! empty( $endpoint );
        $post_status = isset( $post->post_status ) ? $post->post_status : '';
        $is_valid_status = in_array( $post_status, array( 'draft', 'publish' ), true );
        $post_permalink = get_permalink( $post->ID );

        wp_nonce_field( 'echoads_generate_audio', 'echoads_generate_audio_nonce' );
        ?>
        <div id="echoads-meta-box">
            <?php if ( ! $has_config ) : ?>
                <div class="echoads-notice echoads-notice-warning">
                    <p><strong>Configuration Required</strong></p>
                    <p>Please configure API key and endpoint in <a href="<?php echo admin_url( 'options-general.php?page=auto-send-plugin' ); ?>" target="_blank">EchoAds Settings</a>. Audio will be generated automatically when you publish this post.</p>
                </div>
            <?php elseif ( $audio_requested && ( $audio_status === 'PENDING' || $audio_status === 'PROCESSING' ) ) : ?>
                <div class="echoads-notice echoads-notice-info">
                    <p><strong>Audio Generation In Progress</strong></p>
                    <p>Status: <strong><?php echo esc_html( $audio_status ); ?></strong></p>
                    <p>Please check the status to see when audio generation is complete.</p>
                </div>
                <button type="button" id="echoads-check-status-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                    Check Audio Article Status
                </button>
            <?php elseif ( $audio_generated && $audio_status === 'COMPLETED' ) : ?>
                <div class="echoads-notice echoads-notice-success">
                    <p><strong>Audio Generated</strong></p>
                    <p>Audio was generated on <?php echo date( 'M j, Y g:i A', strtotime( $audio_generated ) ); ?></p>
                    <p>The audio player will be displayed on the front-end for this post.</p>
                </div>
                <button type="button" id="echoads-preview-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                    <span class="echoads-btn-icon">🎧</span>
                    Preview Audio Article Track
                </button>
                <button type="button" id="echoads-regenerate-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                    Regenerate Audio
                </button>
            <?php elseif ( $audio_requested ) : ?>
                <?php if ( $audio_status === 'FAILED' || $audio_status === 'SKIPPED' ) : ?>
                    <div class="echoads-notice echoads-notice-error">
                        <p><strong>Audio Generation <?php echo esc_html( $audio_status ); ?></strong></p>
                        <p>Status: <strong><?php echo esc_html( $audio_status ); ?></strong></p>
                        <p>Audio generation failed. The post will attempt to generate audio again when republished.</p>
                    </div>
                    <button type="button" id="echoads-check-status-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                        Check Audio Article Status
                    </button>
                    <button type="button" id="echoads-regenerate-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                        Regenerate Audio
                    </button>
                <?php else : ?>
                    <div class="echoads-notice echoads-notice-info">
                        <p><strong>Audio Generation Requested</strong></p>
                        <p>Audio generation was requested. Please check the status to see the current state.</p>
                    </div>
                    <button type="button" id="echoads-check-status-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                        Check Audio Article Status
                    </button>
                    <?php if ( $audio_status !== 'PENDING' && $audio_status !== 'PROCESSING' ) : ?>
                        <button type="button" id="echoads-regenerate-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                            Regenerate Audio
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else : ?>
                <div class="echoads-notice echoads-notice-info">
                    <p><strong>Audio Generation</strong></p>
                    <p>Audio will be generated automatically when you publish this post.</p>
                </div>
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

        #echoads-check-status-btn, #echoads-preview-btn, #echoads-regenerate-btn {
            width: 100%;
            padding: 8px 12px;
            font-size: 13px;
            margin-bottom: 12px;
        }

        #echoads-check-status-btn:disabled, #echoads-preview-btn:disabled, #echoads-regenerate-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .echoads-btn-icon {
            margin-right: 6px;
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

        #echoads-response-message.info {
            background: #e7f3ff;
            border-left: 4px solid #0073aa;
            color: #0073aa;
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
                        html += $('<div>').text(errorData.response_body).html();
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

            $('#echoads-preview-btn').click(function(e) {
                e.preventDefault();

                var button = $(this);
                var postId = button.data('post-id');
                var responseDiv = $('#echoads-response-message');

                // Update button state
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
                    success: function(response) {
                        if (response.success && response.data && response.data.audioUrl) {
                            // Open audio URL in new tab
                            window.open(response.data.audioUrl, '_blank');
                            responseDiv.addClass('success').text('Preview audio opened in new tab').show();
                            
                            // Reset button state after a short delay
                            setTimeout(function() {
                                button.prop('disabled', false);
                                button.html('<span class=\"echoads-btn-icon\">🎧</span> Preview Audio Article Track');
                            }, 1000);
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
                            button.html('<span class=\"echoads-btn-icon\">🎧</span> Preview Audio Article Track');
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
                        button.html('<span class=\"echoads-btn-icon\">🎧</span> Preview Audio Article Track');
                    }
                });
            });

            // Check Status button handler
            $(document).on('click', '#echoads-check-status-btn', function(e) {
                e.preventDefault();

                var button = $(this);
                var postId = button.data('post-id');
                var responseDiv = $('#echoads-response-message');

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
                            var statusMessage = 'Status: ' + status;
                            
                            if (status === 'COMPLETED') {
                                responseDiv.addClass('success').html('<strong>Audio Generation Complete!</strong><br>' + statusMessage).show();
                                // Reload to show Preview button
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else if (status === 'PENDING' || status === 'PROCESSING') {
                                responseDiv.addClass('info').html('<strong>Audio Still Processing</strong><br>' + statusMessage + '<br>Please check again later.').show();
                                button.prop('disabled', false);
                                button.text('Check Audio Article Status');
                            } else if (status === 'FAILED' || status === 'SKIPPED') {
                                responseDiv.addClass('error').html('<strong>Audio Generation ' + status + '</strong><br>' + statusMessage + '<br>Audio generation failed. The post will attempt to generate audio again when republished.').show();
                                // Reload to show updated status
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                responseDiv.addClass('info').html('<strong>Status Checked</strong><br>' + statusMessage).show();
                                button.prop('disabled', false);
                                button.text('Check Audio Article Status');
                            }
                        } else {
                            responseDiv.addClass('error').html('<strong>Error</strong><br>' + (response.data.message || 'Failed to check status')).show();
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
                        responseDiv.addClass('error').html('<strong>Error</strong><br>' + errorMsg).show();
                        button.prop('disabled', false);
                        button.text('Check Audio Article Status');
                    }
                });
            });

            // Regenerate Audio button handler
            $(document).on('click', '#echoads-regenerate-btn', function(e) {
                e.preventDefault();

                var button = $(this);
                var postId = button.data('post-id');
                var responseDiv = $('#echoads-response-message');

                // Update button state
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
                    success: function(response) {
                        if (response.success) {
                            // Immediately update UI to show pending/processing state
                            var metaBox = $('#echoads-meta-box');
                            
                            // Hide preview and regenerate buttons
                            $('#echoads-preview-btn').hide();
                            $('#echoads-regenerate-btn').hide();
                            
                            // Create or show check status button
                            var checkStatusBtn = $('#echoads-check-status-btn');
                            if (checkStatusBtn.length === 0) {
                                checkStatusBtn = $('<button type=\"button\" id=\"echoads-check-status-btn\" class=\"button button-primary\" data-post-id=\"' + postId + '\" style=\"width: 100%;\">Check Audio Article Status</button>');
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
                                var noticeHtml = '<div class=\"echoads-notice echoads-notice-info\"><p><strong>Audio Generation In Progress</strong></p><p>Status: <strong>PENDING</strong></p><p>Please check the status to see when audio generation is complete.</p></div>';
                                metaBox.prepend(noticeHtml);
                            }
                            
                            responseDiv.addClass('success').html('<strong>Audio Regeneration Initiated</strong><br>' + (response.data.message || 'Audio generation has been started. Please check the status to see when it completes.')).show();
                            
                            // Reload as fallback to sync with server state (longer delay since UI is already updated)
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
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
                            button.text('Regenerate Audio');
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
                        button.text('Regenerate Audio');
                    }
                });
            });
        });
        ";
    }
}