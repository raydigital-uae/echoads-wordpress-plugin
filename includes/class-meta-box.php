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
            __( 'EchoAds Audio Generation', 'echoads-posts-plugin' ),
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
        $dir_attr = is_rtl() ? 'rtl' : 'ltr';
        ?>
        <div id="echoads-meta-box" dir="<?php echo esc_attr( $dir_attr ); ?>">
            <?php if ( ! $has_config ) : ?>
                <div class="echoads-notice echoads-notice-warning">
                    <p><strong><?php echo esc_html__( 'Configuration Required', 'echoads-posts-plugin' ); ?></strong></p>
                    <p><?php echo esc_html__( 'Please configure API key and endpoint in', 'echoads-posts-plugin' ); ?> <a href="<?php echo esc_url( admin_url( 'options-general.php?page=auto-send-plugin' ) ); ?>" target="_blank"><?php echo esc_html__( 'EchoAds Settings', 'echoads-posts-plugin' ); ?></a>. <?php echo esc_html__( 'Audio will be generated automatically when you publish this post.', 'echoads-posts-plugin' ); ?></p>
                </div>
            <?php elseif ( $audio_requested && ( $audio_status === 'PENDING' || $audio_status === 'PROCESSING' ) ) : ?>
                <div class="echoads-notice echoads-notice-info">
                    <p><strong><?php echo esc_html__( 'Audio Generation In Progress', 'echoads-posts-plugin' ); ?></strong></p>
                    <p><?php echo esc_html__( 'Status:', 'echoads-posts-plugin' ); ?> <strong><?php echo esc_html( $audio_status ); ?></strong></p>
                    <p><?php echo esc_html__( 'Please check the status to see when audio generation is complete.', 'echoads-posts-plugin' ); ?></p>
                </div>
                <button type="button" id="echoads-check-status-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                    <?php echo esc_html__( 'Check Audio Article Status', 'echoads-posts-plugin' ); ?>
                </button>
            <?php elseif ( $audio_generated && $audio_status === 'COMPLETED' ) : ?>
                <div class="echoads-notice echoads-notice-success">
                    <p><strong><?php echo esc_html__( 'Audio Generated', 'echoads-posts-plugin' ); ?></strong></p>
                    <p><?php echo esc_html__( 'Audio was generated on', 'echoads-posts-plugin' ); ?> <?php echo esc_html( date_i18n( __( 'M j, Y g:i A', 'echoads-posts-plugin' ), strtotime( $audio_generated ) ) ); ?></p>
                    <p><?php echo esc_html__( 'The audio player will be displayed on the front-end for this post.', 'echoads-posts-plugin' ); ?></p>
                </div>
                <button type="button" id="echoads-preview-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                    <span class="echoads-btn-icon">🎧</span>
                    <?php echo esc_html__( 'Preview Audio Article Track', 'echoads-posts-plugin' ); ?>
                </button>
                <button type="button" id="echoads-regenerate-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                    <?php echo esc_html__( 'Regenerate Audio', 'echoads-posts-plugin' ); ?>
                </button>
            <?php elseif ( $audio_requested ) : ?>
                <?php if ( $audio_status === 'FAILED' || $audio_status === 'SKIPPED' ) : ?>
                    <div class="echoads-notice echoads-notice-error">
                        <p><strong><?php echo esc_html__( 'Audio Generation', 'echoads-posts-plugin' ); ?> <?php echo esc_html( $audio_status ); ?></strong></p>
                        <p><?php echo esc_html__( 'Status:', 'echoads-posts-plugin' ); ?> <strong><?php echo esc_html( $audio_status ); ?></strong></p>
                        <p><?php echo esc_html__( 'Audio generation failed. The post will attempt to generate audio again when republished.', 'echoads-posts-plugin' ); ?></p>
                    </div>
                    <button type="button" id="echoads-check-status-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                        <?php echo esc_html__( 'Check Audio Article Status', 'echoads-posts-plugin' ); ?>
                    </button>
                    <button type="button" id="echoads-regenerate-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                        <?php echo esc_html__( 'Regenerate Audio', 'echoads-posts-plugin' ); ?>
                    </button>
                <?php else : ?>
                    <div class="echoads-notice echoads-notice-info">
                        <p><strong><?php echo esc_html__( 'Audio Generation Requested', 'echoads-posts-plugin' ); ?></strong></p>
                        <p><?php echo esc_html__( 'Audio generation was requested. Please check the status to see the current state.', 'echoads-posts-plugin' ); ?></p>
                    </div>
                    <button type="button" id="echoads-check-status-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                        <?php echo esc_html__( 'Check Audio Article Status', 'echoads-posts-plugin' ); ?>
                    </button>
                    <?php if ( $audio_status !== 'PENDING' && $audio_status !== 'PROCESSING' ) : ?>
                        <button type="button" id="echoads-regenerate-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                            <?php echo esc_html__( 'Regenerate Audio', 'echoads-posts-plugin' ); ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else : ?>
                <div class="echoads-notice echoads-notice-info">
                    <p><strong><?php echo esc_html__( 'Audio Generation', 'echoads-posts-plugin' ); ?></strong></p>
                    <p><?php echo esc_html__( 'Audio will be generated automatically when you publish this post.', 'echoads-posts-plugin' ); ?></p>
                </div>
            <?php endif; ?>

            <div id="echoads-response-message" style="display: none;"></div>

            <div class="echoads-info">
                <p><small><strong><?php echo esc_html__( 'Note:', 'echoads-posts-plugin' ); ?></strong> <?php echo esc_html__( 'Only posts with generated audio will display the audio player on the front-end.', 'echoads-posts-plugin' ); ?></small></p>
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
            border-inline-start: 4px solid;
        }

        .echoads-notice-info {
            background: #e7f3ff;
            border-inline-start-color: #0073aa;
            color: #0073aa;
        }

        .echoads-notice-success {
            background: #ecf7ed;
            border-inline-start-color: #46b450;
            color: #46b450;
        }

        .echoads-notice-warning {
            background: #fff8e5;
            border-inline-start-color: #ffb900;
            color: #b26800;
        }

        .echoads-notice-error {
            background: #fbeaea;
            border-inline-start-color: #dc3232;
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
            margin-inline-end: 6px;
        }


        #echoads-response-message {
            margin-top: 12px;
            padding: 8px 12px;
            border-radius: 4px;
        }

        #echoads-response-message.success {
            background: #ecf7ed;
            border-inline-start: 4px solid #46b450;
            color: #46b450;
        }

        #echoads-response-message.error {
            background: #fbeaea;
            border-inline-start: 4px solid #dc3232;
            color: #dc3232;
        }

        #echoads-response-message.info {
            background: #e7f3ff;
            border-inline-start: 4px solid #0073aa;
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

            $post_status = isset( $post->post_status ) ? $post->post_status : '';
            $is_valid_status = in_array( $post_status, array( 'draft', 'publish' ), true );

            wp_localize_script( 'jquery', 'echoads_ajax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'echoads_generate_audio' ),
                'post_status' => $post_status,
                'is_valid_status' => $is_valid_status
            ) );
            wp_localize_script( 'jquery', 'echoads_meta_i18n', $this->get_meta_box_i18n() );
            wp_add_inline_script( 'jquery', $this->get_meta_box_script() );
        }
    }

    /**
     * Returns translated strings for the meta box script.
     *
     * @return array<string, string>
     */
    private function get_meta_box_i18n() {
        return array(
            'error_occurred' => __( 'An error occurred', 'echoads-posts-plugin' ),
            'show_details' => __( 'Show Details', 'echoads-posts-plugin' ),
            'hide_details' => __( 'Hide Details', 'echoads-posts-plugin' ),
            'loading' => __( 'Loading...', 'echoads-posts-plugin' ),
            'preview_opened' => __( 'Preview audio opened in new tab', 'echoads-posts-plugin' ),
            'preview_audio_track' => __( 'Preview Audio Article Track', 'echoads-posts-plugin' ),
            'checking' => __( 'Checking...', 'echoads-posts-plugin' ),
            'status_label' => __( 'Status:', 'echoads-posts-plugin' ),
            'audio_generation_complete' => __( 'Audio Generation Complete!', 'echoads-posts-plugin' ),
            'audio_still_processing' => __( 'Audio Still Processing', 'echoads-posts-plugin' ),
            'check_again_later' => __( 'Please check again later.', 'echoads-posts-plugin' ),
            'check_audio_status' => __( 'Check Audio Article Status', 'echoads-posts-plugin' ),
            'audio_generation_failed_republish' => __( 'Audio generation failed. The post will attempt to generate audio again when republished.', 'echoads-posts-plugin' ),
            'status_checked' => __( 'Status Checked', 'echoads-posts-plugin' ),
            'error' => __( 'Error', 'echoads-posts-plugin' ),
            'failed_to_check_status' => __( 'Failed to check status', 'echoads-posts-plugin' ),
            'regenerating' => __( 'Regenerating...', 'echoads-posts-plugin' ),
            'regenerate_audio' => __( 'Regenerate Audio', 'echoads-posts-plugin' ),
            'regeneration_initiated' => __( 'Audio Regeneration Initiated', 'echoads-posts-plugin' ),
            'regeneration_started_message' => __( 'Audio generation has been started. Please check the status to see when it completes.', 'echoads-posts-plugin' ),
            'audio_generation' => __( 'Audio Generation', 'echoads-posts-plugin' ),
            'audio_generation_in_progress' => __( 'Audio Generation In Progress', 'echoads-posts-plugin' ),
            'check_status_when_complete' => __( 'Please check the status to see when audio generation is complete.', 'echoads-posts-plugin' ),
            'http_status' => __( 'HTTP Status:', 'echoads-posts-plugin' ),
            'response_code' => __( 'Response Code:', 'echoads-posts-plugin' ),
            'response_body' => __( 'Response Body:', 'echoads-posts-plugin' ),
            'response_headers' => __( 'Response Headers:', 'echoads-posts-plugin' ),
            'error_message_label' => __( 'Error Message:', 'echoads-posts-plugin' ),
        );
    }

    private function get_meta_box_script() {
        return "
        jQuery(document).ready(function($) {
            var i18n = typeof echoads_meta_i18n !== 'undefined' ? echoads_meta_i18n : {};
            function formatErrorDetails(errorData) {
                var html = '<div class=\"echoads-error-summary\">';
                html += '<strong>' + (errorData.message || i18n.error_occurred || 'An error occurred') + '</strong>';
                
                if (errorData.response_code !== null && errorData.response_code !== undefined) {
                    html += '<br><span class=\"echoads-error-code\">' + (i18n.http_status || 'HTTP Status:') + ' ' + errorData.response_code + '</span>';
                }
                
                if (errorData.error_message) {
                    html += '<br><span class=\"echoads-error-message\">' + errorData.error_message + '</span>';
                }
                
                html += '<br><a href=\"#\" class=\"echoads-toggle-details\">' + (i18n.show_details || 'Show Details') + '</a>';
                html += '</div>';
                
                html += '<div class=\"echoads-error-details\" style=\"display: none; margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 4px; font-size: 11px; max-height: 400px; overflow-y: auto;\">';
                
                if (errorData.response_code !== null && errorData.response_code !== undefined) {
                    html += '<div style=\"margin-bottom: 8px;\"><strong>' + (i18n.response_code || 'Response Code:') + '</strong> ' + errorData.response_code + '</div>';
                }
                
                if (errorData.response_body) {
                    html += '<div style=\"margin-bottom: 8px;\"><strong>' + (i18n.response_body || 'Response Body:') + '</strong>';
                    html += '<pre style=\"white-space: pre-wrap; word-wrap: break-word; background: rgba(0,0,0,0.05); padding: 8px; border-radius: 3px; margin-top: 4px; max-height: 200px; overflow-y: auto;\">';
                    if (errorData.response_body_parsed) {
                        html += JSON.stringify(errorData.response_body_parsed, null, 2);
                    } else {
                        html += $('<div>').text(errorData.response_body).html();
                    }
                    html += '</pre></div>';
                }
                
                if (errorData.response_headers && Object.keys(errorData.response_headers).length > 0) {
                    html += '<div style=\"margin-bottom: 8px;\"><strong>' + (i18n.response_headers || 'Response Headers:') + '</strong>';
                    html += '<pre style=\"white-space: pre-wrap; word-wrap: break-word; background: rgba(0,0,0,0.05); padding: 8px; border-radius: 3px; margin-top: 4px; max-height: 150px; overflow-y: auto;\">';
                    html += JSON.stringify(errorData.response_headers, null, 2);
                    html += '</pre></div>';
                }
                
                if (errorData.error_message) {
                    html += '<div style=\"margin-bottom: 8px;\"><strong>' + (i18n.error_message_label || 'Error Message:') + '</strong> ' + errorData.error_message + '</div>';
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
                button.html('<span class=\"echoads-btn-icon\">⏳</span> ' + (i18n.loading || 'Loading...'));
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
                            responseDiv.addClass('success').text(i18n.preview_opened || 'Preview audio opened in new tab').show();
                            
                            // Reset button state after a short delay
                            setTimeout(function() {
                                button.prop('disabled', false);
                                button.html('<span class=\"echoads-btn-icon\">🎧</span> ' + (i18n.preview_audio_track || 'Preview Audio Article Track'));
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
                                    toggleLink.text(i18n.show_details || 'Show Details');
                                } else {
                                    detailsDiv.slideDown();
                                    toggleLink.text(i18n.hide_details || 'Hide Details');
                                }
                            });
                            
                            // Reset button state
                            button.prop('disabled', false);
                            button.html('<span class=\"echoads-btn-icon\">🎧</span> ' + (i18n.preview_audio_track || 'Preview Audio Article Track'));
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorData = {
                            message: (i18n.error_occurred || 'An error occurred') + ': ' + error,
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
                                toggleLink.text(i18n.show_details || 'Show Details');
                            } else {
                                detailsDiv.slideDown();
                                toggleLink.text(i18n.hide_details || 'Hide Details');
                            }
                        });
                        
                        // Reset button state
                        button.prop('disabled', false);
                        button.html('<span class=\"echoads-btn-icon\">🎧</span> ' + (i18n.preview_audio_track || 'Preview Audio Article Track'));
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
                button.text(i18n.checking || 'Checking...');
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
                            var statusLabel = i18n.status_label || 'Status:';
                            var statusMessage = statusLabel + ' ' + status;
                            
                            if (status === 'COMPLETED') {
                                responseDiv.addClass('success').html('<strong>' + (i18n.audio_generation_complete || 'Audio Generation Complete!') + '</strong><br>' + statusMessage).show();
                                // Reload to show Preview button
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else if (status === 'PENDING' || status === 'PROCESSING') {
                                responseDiv.addClass('info').html('<strong>' + (i18n.audio_still_processing || 'Audio Still Processing') + '</strong><br>' + statusMessage + '<br>' + (i18n.check_again_later || 'Please check again later.')).show();
                                button.prop('disabled', false);
                                button.text(i18n.check_audio_status || 'Check Audio Article Status');
                            } else if (status === 'FAILED' || status === 'SKIPPED') {
                                responseDiv.addClass('error').html('<strong>' + (i18n.audio_generation || 'Audio Generation') + ' ' + status + '</strong><br>' + statusMessage + '<br>' + (i18n.audio_generation_failed_republish || 'Audio generation failed. The post will attempt to generate audio again when republished.')).show();
                                // Reload to show updated status
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                responseDiv.addClass('info').html('<strong>' + (i18n.status_checked || 'Status Checked') + '</strong><br>' + statusMessage).show();
                                button.prop('disabled', false);
                                button.text(i18n.check_audio_status || 'Check Audio Article Status');
                            }
                        } else {
                            responseDiv.addClass('error').html('<strong>' + (i18n.error || 'Error') + '</strong><br>' + (response.data.message || (i18n.failed_to_check_status || 'Failed to check status'))).show();
                            button.prop('disabled', false);
                            button.text(i18n.check_audio_status || 'Check Audio Article Status');
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMsg = (i18n.error_occurred || 'An error occurred') + ': ' + error;
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
                        responseDiv.addClass('error').html('<strong>' + (i18n.error || 'Error') + '</strong><br>' + errorMsg).show();
                        button.prop('disabled', false);
                        button.text(i18n.check_audio_status || 'Check Audio Article Status');
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
                button.text(i18n.regenerating || 'Regenerating...');
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
                                checkStatusBtn = $('<button type=\"button\" id=\"echoads-check-status-btn\" class=\"button button-primary\" data-post-id=\"' + postId + '\" style=\"width: 100%;\">' + (i18n.check_audio_status || 'Check Audio Article Status') + '</button>');
                                metaBox.append(checkStatusBtn);
                            } else {
                                checkStatusBtn.show();
                            }
                            
                            // Update notice to show pending state
                            var existingNotice = metaBox.find('.echoads-notice');
                            var inProgressTitle = i18n.audio_generation_in_progress || 'Audio Generation In Progress';
                            var statusPending = (i18n.status_label || 'Status:') + ' <strong>PENDING</strong>';
                            var checkWhenComplete = i18n.check_status_when_complete || 'Please check the status to see when audio generation is complete.';
                            if (existingNotice.length > 0) {
                                existingNotice.removeClass('echoads-notice-success echoads-notice-error echoads-notice-warning')
                                             .addClass('echoads-notice-info')
                                             .html('<p><strong>' + inProgressTitle + '</strong></p><p>' + statusPending + '</p><p>' + checkWhenComplete + '</p>');
                            } else {
                                var noticeHtml = '<div class=\"echoads-notice echoads-notice-info\"><p><strong>' + inProgressTitle + '</strong></p><p>' + statusPending + '</p><p>' + checkWhenComplete + '</p></div>';
                                metaBox.prepend(noticeHtml);
                            }
                            
                            responseDiv.addClass('success').html('<strong>' + (i18n.regeneration_initiated || 'Audio Regeneration Initiated') + '</strong><br>' + (response.data.message || (i18n.regeneration_started_message || 'Audio generation has been started. Please check the status to see when it completes.'))).show();
                            
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
                                    toggleLink.text(i18n.show_details || 'Show Details');
                                } else {
                                    detailsDiv.slideDown();
                                    toggleLink.text(i18n.hide_details || 'Hide Details');
                                }
                            });
                            
                            // Reset button state
                            button.prop('disabled', false);
                            button.text(i18n.regenerate_audio || 'Regenerate Audio');
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorData = {
                            message: (i18n.error_occurred || 'An error occurred') + ': ' + error,
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
                                toggleLink.text(i18n.show_details || 'Show Details');
                            } else {
                                detailsDiv.slideDown();
                                toggleLink.text(i18n.hide_details || 'Hide Details');
                            }
                        });
                        
                        // Reset button state
                        button.prop('disabled', false);
                        button.text(i18n.regenerate_audio || 'Regenerate Audio');
                    }
                });
            });
        });
        ";
    }
}