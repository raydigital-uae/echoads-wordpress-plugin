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
            __( 'EchoAds Audio Generation', 'echoads-audio-player' ),
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

        wp_nonce_field( 'echoads_generate_audio', 'echoads_generate_audio_nonce' );
        ?>
        <div id="echoads-meta-box">
            <?php if ( ! $has_config ) : ?>
                <div class="echoads-notice echoads-notice-warning">
                    <p><strong><?php esc_html_e( 'Configuration Required', 'echoads-audio-player' ); ?></strong></p>
                    <p>
                        <?php
                        printf(
                            /* translators: %s: link to the EchoAds settings page. */
                            esc_html__( 'Please configure API key and endpoint in %s. Audio will be generated automatically when you publish this post.', 'echoads-audio-player' ),
                            '<a href="' . esc_url( EchoAds_Settings::get_settings_page_url() ) . '" target="_blank">' . esc_html__( 'EchoAds Settings', 'echoads-audio-player' ) . '</a>'
                        );
                        ?>
                    </p>
                </div>
            <?php elseif ( $audio_requested && ( $audio_status === 'PENDING' || $audio_status === 'PROCESSING' ) ) : ?>
                <div class="echoads-notice echoads-notice-info">
                    <p><strong><?php esc_html_e( 'Audio Generation In Progress', 'echoads-audio-player' ); ?></strong></p>
                    <p><?php esc_html_e( 'Status:', 'echoads-audio-player' ); ?> <strong><?php echo esc_html( $audio_status ); ?></strong></p>
                    <p><?php esc_html_e( 'Please check the status to see when audio generation is complete.', 'echoads-audio-player' ); ?></p>
                </div>
                <button type="button" id="echoads-check-status-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" data-auto-check="1" style="width: 100%;">
                    <?php esc_html_e( 'Check Audio Article Status', 'echoads-audio-player' ); ?>
                </button>
            <?php elseif ( $audio_generated && $audio_status === 'COMPLETED' ) : ?>
                <div class="echoads-notice echoads-notice-success">
                    <p><strong><?php esc_html_e( 'Audio Generated', 'echoads-audio-player' ); ?></strong></p>
                    <p>
                        <?php
                        printf(
                            /* translators: %s: date and time the audio was generated. */
                            esc_html__( 'Audio was generated on %s', 'echoads-audio-player' ),
                            esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $audio_generated ) )
                        );
                        ?>
                    </p>
                    <p><?php esc_html_e( 'The audio player will be displayed on the front-end for this post.', 'echoads-audio-player' ); ?></p>
                </div>
                <button type="button" id="echoads-preview-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                    <span class="echoads-btn-icon">🎧</span>
                    <?php esc_html_e( 'Preview Audio Article Track', 'echoads-audio-player' ); ?>
                </button>
                <button type="button" id="echoads-regenerate-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                    <?php esc_html_e( 'Regenerate Audio', 'echoads-audio-player' ); ?>
                </button>
            <?php elseif ( $audio_requested ) : ?>
                <?php if ( $audio_status === 'FAILED' || $audio_status === 'SKIPPED' ) : ?>
                    <div class="echoads-notice echoads-notice-error">
                        <p><strong><?php esc_html_e( 'Audio Generation', 'echoads-audio-player' ); ?> <?php echo esc_html( $audio_status ); ?></strong></p>
                        <p><?php esc_html_e( 'Status:', 'echoads-audio-player' ); ?> <strong><?php echo esc_html( $audio_status ); ?></strong></p>
                        <p><?php esc_html_e( 'Audio generation failed. The post will attempt to generate audio again when republished.', 'echoads-audio-player' ); ?></p>
                    </div>
                    <button type="button" id="echoads-check-status-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                        <?php esc_html_e( 'Check Audio Article Status', 'echoads-audio-player' ); ?>
                    </button>
                    <button type="button" id="echoads-regenerate-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                        <?php esc_html_e( 'Regenerate Audio', 'echoads-audio-player' ); ?>
                    </button>
                <?php else : ?>
                    <div class="echoads-notice echoads-notice-info">
                        <p><strong><?php esc_html_e( 'Audio Generation Requested', 'echoads-audio-player' ); ?></strong></p>
                        <p><?php esc_html_e( 'Audio generation was requested. Please check the status to see the current state.', 'echoads-audio-player' ); ?></p>
                    </div>
                    <button type="button" id="echoads-check-status-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" data-auto-check="1" style="width: 100%;">
                        <?php esc_html_e( 'Check Audio Article Status', 'echoads-audio-player' ); ?>
                    </button>
                    <?php if ( $audio_status !== 'PENDING' && $audio_status !== 'PROCESSING' ) : ?>
                        <button type="button" id="echoads-regenerate-btn" class="button button-secondary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                            <?php esc_html_e( 'Regenerate Audio', 'echoads-audio-player' ); ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            <?php elseif ( $post->post_status === 'publish' && ! $audio_generated ) : // Never sent, e.g. published before the plugin was configured. ?>
                <div class="echoads-notice echoads-notice-info">
                    <p><strong><?php esc_html_e( 'Audio Generation', 'echoads-audio-player' ); ?></strong></p>
                    <p><?php esc_html_e( 'Audio hasn\'t been generated for this post yet.', 'echoads-audio-player' ); ?></p>
                </div>
                <button type="button" id="echoads-generate-btn" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>" style="width: 100%;">
                    <?php esc_html_e( 'Generate Audio', 'echoads-audio-player' ); ?>
                </button>
            <?php else : ?>
                <div class="echoads-notice echoads-notice-info">
                    <p><strong><?php esc_html_e( 'Audio Generation', 'echoads-audio-player' ); ?></strong></p>
                    <p><?php esc_html_e( 'Audio will be generated automatically when you publish this post.', 'echoads-audio-player' ); ?></p>
                </div>
            <?php endif; ?>

            <div id="echoads-response-message" style="display: none;"></div>

            <div class="echoads-info">
                <p><small><strong><?php esc_html_e( 'Note:', 'echoads-audio-player' ); ?></strong> <?php esc_html_e( 'Only posts with generated audio will display the audio player on the front-end.', 'echoads-audio-player' ); ?></small></p>
            </div>
        </div>

        <?php
    }

    public function enqueue_admin_assets( $hook ) {
        global $post;

        if ( ( 'post.php' !== $hook && 'post-new.php' !== $hook ) || ! $post || 'post' !== $post->post_type ) {
            return;
        }

        wp_enqueue_style(
            'echoads-meta-box',
            plugins_url( 'assets/css/meta-box.css', ECHOADS_PLUGIN_FILE ),
            array(),
            ECHOADS_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'echoads-meta-box',
            plugins_url( 'assets/js/meta-box.js', ECHOADS_PLUGIN_FILE ),
            array( 'jquery' ),
            ECHOADS_PLUGIN_VERSION,
            true
        );

        wp_localize_script( 'echoads-meta-box', 'echoads_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'echoads_generate_audio' ),
        ) );
    }
}
