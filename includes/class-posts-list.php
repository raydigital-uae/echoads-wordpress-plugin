<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EchoAds audio on Posts > All Posts: an icon column showing each post's audio status, and a
 * "Generate audio" button in Quick Edit. The button uses the meta box's admin-ajax handler
 * (echoads_generate_audio), so the API call and post meta updates live in one place.
 */
class EchoAds_Posts_List {

    const COLUMN = 'echoads_audio';

    public function __construct() {
        add_filter( 'manage_post_posts_columns', array( $this, 'add_column' ) );
        add_action( 'manage_post_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
        add_action( 'quick_edit_custom_box', array( $this, 'render_quick_edit' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Adds the narrow icon column right after the title.
     *
     * @param array $columns Column keys and headers.
     * @return array
     */
    public function add_column( $columns ) {
        // Screen Options and the mobile layout use the header with tags stripped, so the
        // screen-reader text doubles as the column's visible name there.
        $header = sprintf(
            '<span class="dashicons dashicons-format-audio" aria-hidden="true" title="%1$s"></span><span class="screen-reader-text">%2$s</span>',
            esc_attr__( 'EchoAds audio', 'echoads-audio-player' ),
            esc_html__( 'EchoAds audio', 'echoads-audio-player' )
        );

        $with_audio = array();
        foreach ( $columns as $key => $label ) {
            $with_audio[ $key ] = $label;
            if ( 'title' === $key ) {
                $with_audio[ self::COLUMN ] = $header;
            }
        }
        if ( ! isset( $with_audio[ self::COLUMN ] ) ) {
            $with_audio[ self::COLUMN ] = $header;
        }

        return $with_audio;
    }

    /**
     * Prints the status icon. Quick Edit reads the row's state and action from its data attributes.
     *
     * @param string $column  Column key.
     * @param int    $post_id Post ID.
     */
    public function render_column( $column, $post_id ) {
        if ( self::COLUMN !== $column ) {
            return;
        }

        $status = self::get_audio_status( $post_id );

        printf(
            '<span class="echoads-audio-status echoads-audio-status--%1$s" data-echoads-state="%1$s" data-echoads-action="%2$s" title="%3$s"><span class="dashicons %4$s" aria-hidden="true"></span><span class="screen-reader-text">%5$s</span></span>',
            esc_attr( $status['state'] ),
            esc_attr( self::get_generate_action( $post_id, $status['state'] ) ),
            esc_attr( $status['label'] ),
            esc_attr( $status['icon'] ),
            esc_html( $status['label'] )
        );
    }

    /**
     * The Quick Edit panel is one hidden template for the whole list; posts-list.js fills it per row.
     *
     * @param string $column    Column key.
     * @param string $post_type Post type of the list.
     */
    public function render_quick_edit( $column, $post_type ) {
        if ( self::COLUMN !== $column || 'post' !== $post_type ) {
            return;
        }
        ?>
        <fieldset class="inline-edit-col-right echoads-quick-edit">
            <div class="inline-edit-col">
                <span class="title"><?php esc_html_e( 'EchoAds audio', 'echoads-audio-player' ); ?></span>
                <?php if ( self::is_configured() ) : ?>
                    <p class="echoads-quick-edit-status"></p>
                    <p class="echoads-quick-edit-actions">
                        <button type="button" class="button echoads-quick-edit-generate"><?php esc_html_e( 'Generate audio', 'echoads-audio-player' ); ?></button>
                        <span class="spinner"></span>
                    </p>
                    <p class="echoads-quick-edit-message"></p>
                <?php elseif ( current_user_can( 'manage_options' ) ) : ?>
                    <p>
                        <?php
                        printf(
                            /* translators: %s: link to the EchoAds settings page. */
                            esc_html__( 'Add your API key in %s to generate audio.', 'echoads-audio-player' ),
                            '<a href="' . esc_url( admin_url( 'options-general.php?page=auto-send-plugin' ) ) . '">' . esc_html__( 'EchoAds Settings', 'echoads-audio-player' ) . '</a>'
                        );
                        ?>
                    </p>
                <?php else : ?>
                    <p><?php esc_html_e( 'Audio generation is not set up yet. Ask an administrator to add the API key in EchoAds Settings.', 'echoads-audio-player' ); ?></p>
                <?php endif; ?>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Loads the column styles and the Quick Edit script on Posts > All Posts only.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_assets( $hook ) {
        if ( 'edit.php' !== $hook ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || 'post' !== $screen->post_type ) {
            return;
        }

        $plugin_url = plugin_dir_url( dirname( __FILE__ ) );

        wp_enqueue_style(
            'echoads-posts-list',
            $plugin_url . 'assets/css/posts-list.css',
            array(),
            ECHOADS_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'echoads-posts-list',
            $plugin_url . 'assets/js/posts-list.js',
            array( 'jquery', 'inline-edit-post', 'wp-a11y' ),
            ECHOADS_PLUGIN_VERSION,
            true
        );

        wp_localize_script(
            'echoads-posts-list',
            'echoadsPostsList',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                // Same nonce action as the meta box: both call the echoads_generate_audio handler.
                'nonce'   => wp_create_nonce( 'echoads_generate_audio' ),
                // What a row switches to once generation starts (the handler saves PENDING).
                'queued'  => self::describe( 'generating', 'PENDING' ),
                'i18n'    => array(
                    'publishFirst' => __( 'Audio is generated automatically when the post is published.', 'echoads-audio-player' ),
                    'sending'      => __( 'Starting audio generation…', 'echoads-audio-player' ),
                    'started'      => __( 'Audio generation started.', 'echoads-audio-player' ),
                    'failed'       => __( 'Audio generation could not be started.', 'echoads-audio-player' ),
                ),
            )
        );
    }

    /**
     * Works out a post's audio status from the meta the post sender saves, with the same
     * precedence as the meta box so the icon and the meta box always agree.
     *
     * @param int $post_id Post ID.
     * @return array { state: ready|generating|failed|none, label: string, icon: string }
     */
    public static function get_audio_status( $post_id ) {
        $generated = get_post_meta( $post_id, '_echoads_audio_generated', true );
        $requested = get_post_meta( $post_id, '_echoads_audio_requested', true );
        $status    = (string) get_post_meta( $post_id, '_echoads_audio_status', true );

        if ( $requested && in_array( $status, array( 'PENDING', 'PROCESSING' ), true ) ) {
            $state = 'generating';
        } elseif ( $generated && 'COMPLETED' === $status ) {
            $state = 'ready';
        } elseif ( $requested && in_array( $status, array( 'FAILED', 'SKIPPED' ), true ) ) {
            $state = 'failed';
        } elseif ( $requested ) {
            // Sent, but no final status saved yet (WP-Cron or the meta box's check updates it).
            $state = 'generating';
        } else {
            $state = 'none';
        }

        return self::describe( $state, $status );
    }

    /**
     * Icon and tooltip / screen-reader label for a state.
     *
     * @param string $state  ready|generating|failed|none.
     * @param string $status Raw _echoads_audio_status value, for a more precise label.
     * @return array
     */
    private static function describe( $state, $status ) {
        switch ( $state ) {
            case 'ready':
                $icon  = 'dashicons-controls-volumeon';
                $label = __( 'Audio ready', 'echoads-audio-player' );
                break;
            case 'generating':
                $icon = 'dashicons-clock';
                if ( 'PENDING' === $status ) {
                    $label = __( 'Audio queued for generation', 'echoads-audio-player' );
                } elseif ( 'PROCESSING' === $status ) {
                    $label = __( 'Audio is being generated', 'echoads-audio-player' );
                } else {
                    $label = __( 'Audio generation requested', 'echoads-audio-player' );
                }
                break;
            case 'failed':
                $icon  = 'dashicons-warning';
                $label = 'SKIPPED' === $status
                    ? __( 'Audio generation was skipped', 'echoads-audio-player' )
                    : __( 'Audio generation failed', 'echoads-audio-player' );
                break;
            default:
                $state = 'none';
                $icon  = 'dashicons-controls-volumeoff';
                $label = __( 'No audio', 'echoads-audio-player' );
        }

        return array(
            'state' => $state,
            'label' => $label,
            'icon'  => $icon,
        );
    }

    /**
     * Which request Quick Edit's button sends, mirroring the meta box: "Generate Audio" for a
     * published post that was never sent, "Regenerate Audio" after a failure. Empty means no button.
     *
     * @param int    $post_id Post ID.
     * @param string $state   State from get_audio_status().
     * @return string generate|regenerate|''
     */
    private static function get_generate_action( $post_id, $state ) {
        if ( ! self::is_configured() || ! current_user_can( 'edit_post', $post_id ) ) {
            return '';
        }

        if ( 'failed' === $state ) {
            return 'regenerate';
        }

        if ( 'none' === $state
            && 'publish' === get_post_status( $post_id )
            && ! get_post_meta( $post_id, '_echoads_audio_generated', true ) ) {
            return 'generate';
        }

        return '';
    }

    private static function is_configured() {
        return ! empty( EchoAds_Settings::get_api_key() ) && ! empty( EchoAds_Settings::get_endpoint() );
    }
}
