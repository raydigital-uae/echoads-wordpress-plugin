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

        wp_nonce_field( 'echoads_generate_audio', 'echoads_generate_audio_nonce' );
        ?>
        <div id="echoads-meta-box">
            <?php if ( ! $has_config ) : ?>
                <div class="echoads-notice echoads-notice-warning">
                    <p><strong>Configuration Required</strong></p>
                    <p>Please configure API key and endpoint in <a href="<?php echo admin_url( 'options-general.php?page=auto-send-plugin' ); ?>" target="_blank">EchoAds Settings</a> before generating audio.</p>
                </div>
            <?php elseif ( $audio_generated ) : ?>
                <div class="echoads-notice echoads-notice-success">
                    <p><strong>Audio Generated</strong></p>
                    <p>Audio was generated on <?php echo date( 'M j, Y g:i A', strtotime( $audio_generated ) ); ?></p>
                    <p>The audio player will be displayed on the front-end for this post.</p>
                </div>
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

        #echoads-generate-btn, #echoads-regenerate-btn {
            width: 100%;
            padding: 8px 12px;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .echoads-btn-icon {
            margin-right: 6px;
        }

        #echoads-generate-btn:disabled, #echoads-regenerate-btn:disabled {
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
        </style>
        <?php
    }

    public function enqueue_admin_assets( $hook ) {
        global $post;

        if ( ( $hook === 'post.php' || $hook === 'post-new.php' ) && $post && $post->post_type === 'post' ) {
            wp_enqueue_script( 'jquery' );

            wp_add_inline_script( 'jquery', $this->get_meta_box_script() );

            wp_localize_script( 'jquery', 'echoads_ajax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'echoads_generate_audio' )
            ) );
        }
    }

    private function get_meta_box_script() {
        return "
        jQuery(document).ready(function($) {
            $('#echoads-generate-btn, #echoads-regenerate-btn').click(function(e) {
                e.preventDefault();

                var button = $(this);
                var postId = button.data('post-id');
                var isRegenerate = button.attr('id') === 'echoads-regenerate-btn';
                var responseDiv = $('#echoads-response-message');

                // Update button state
                button.prop('disabled', true);
                if (isRegenerate) {
                    button.text('Regenerating...');
                } else {
                    button.html('<span class=\"echoads-btn-icon\">⏳</span> Generating...');
                }
                responseDiv.hide().removeClass('success error');

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
                            // Reload the page to show the updated meta box
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            responseDiv.addClass('error').text(response.data.message).show();
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
                        responseDiv.addClass('error').text('An error occurred: ' + error).show();
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
        });
        ";
    }
}