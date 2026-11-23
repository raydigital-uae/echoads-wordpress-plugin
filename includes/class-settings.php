<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EchoAds_Settings {

    const OPTION_API_KEY         = 'echoads_api_key';
    const OPTION_BASE_URL        = 'echoads_base_url';
    const OPTION_TIMEOUT         = 'echoads_timeout';
    const OPTION_PLAYER_BG_COLOR = 'echoads_player_bg_color';
    const OPTION_PLAYER_POSITION = 'echoads_player_position';

    const SETTINGS_GROUP = 'echoads_settings';
    const PAGE_SLUG      = 'echoads-audio-player';

    const DEFAULT_TIMEOUT  = 120;
    const DEFAULT_BG_COLOR = '#5D33F5';
    const DEFAULT_POSITION = 'below';

    // Option names used up to 1.6.x, copied to the new names once on upgrade.
    const LEGACY_OPTIONS = array(
        'auto_send_plugin_api_key'         => self::OPTION_API_KEY,
        'auto_send_plugin_base_url'        => self::OPTION_BASE_URL,
        'auto_send_plugin_timeout'         => self::OPTION_TIMEOUT,
        'auto_send_plugin_player_bg_color' => self::OPTION_PLAYER_BG_COLOR,
        'auto_send_plugin_player_position' => self::OPTION_PLAYER_POSITION,
    );

    public function __construct() {
        self::maybe_migrate_legacy_options();

        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_echoads_health_check', array( $this, 'handle_ajax_health_check' ) );
    }

    /**
     * Copies settings saved under the pre-1.7 option names, so upgraded sites keep their API key.
     * The old options are left in place so a downgrade still works; uninstall removes them.
     */
    private static function maybe_migrate_legacy_options() {
        if ( get_option( 'echoads_options_migrated' ) ) {
            return;
        }

        foreach ( self::LEGACY_OPTIONS as $legacy_name => $new_name ) {
            $legacy_value = get_option( $legacy_name, null );
            if ( null !== $legacy_value && null === get_option( $new_name, null ) ) {
                add_option( $new_name, $legacy_value );
            }
        }

        add_option( 'echoads_options_migrated', ECHOADS_PLUGIN_VERSION );
    }

    public function add_settings_page() {
        add_options_page(
            __( 'EchoAds Settings', 'echoads-audio-player' ),
            __( 'EchoAds', 'echoads-audio-player' ),
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'settings_page_content' )
        );
    }

    public static function get_settings_page_url() {
        return admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
    }

    public function settings_page_content() {
        ?>
        <div class="echoads-admin-page">
            <div class="wrap">
                <div class="echoads-settings-container">
                    <div class="echoads-settings-header">
                        <div class="echoads-settings-icon">
                            <img src="<?php echo esc_url( plugins_url( 'assets/white-logo.png', ECHOADS_PLUGIN_FILE ) ); ?>"
                                 alt="<?php esc_attr_e( 'EchoAds Logo', 'echoads-audio-player' ); ?>"
                                 style="max-width: 200px; height: auto;" />
                        </div>
                        <div class="echoads-settings-title">
                            <h1><?php esc_html_e( 'EchoAds Settings', 'echoads-audio-player' ); ?></h1>
                            <p class="echoads-settings-subtitle"><?php esc_html_e( 'Configure your audio player and API endpoints', 'echoads-audio-player' ); ?></p>
                        </div>
                    </div>

                    <div class="echoads-content-area">
                        <form method="post"
                              action="options.php">
                            <?php settings_fields( self::SETTINGS_GROUP ); ?>

                            <div class="echoads-form-section">
                                <h2><?php esc_html_e( 'API Configuration', 'echoads-audio-player' ); ?></h2>
                                <p><?php esc_html_e( 'Configure your API credentials and main endpoint for post data transmission.', 'echoads-audio-player' ); ?></p>

                                <div class="echoads-field-group">
                                    <label class="echoads-field-label"
                                           for="<?php echo esc_attr( self::OPTION_API_KEY ); ?>"><?php esc_html_e( 'API Key', 'echoads-audio-player' ); ?></label>
                                    <div class="echoads-field-wrapper">
                                        <input type="password"
                                               id="<?php echo esc_attr( self::OPTION_API_KEY ); ?>"
                                               name="<?php echo esc_attr( self::OPTION_API_KEY ); ?>"
                                               value="<?php echo esc_attr( self::get_api_key() ); ?>"
                                               class="echoads-field-input has-toggle"
                                               placeholder="<?php esc_attr_e( 'Enter your API key', 'echoads-audio-player' ); ?>"
                                               autocomplete="off" />
                                        <button type="button"
                                                class="echoads-toggle-button"
                                                id="api-key-toggle"
                                                title="<?php esc_attr_e( 'Show API Key', 'echoads-audio-player' ); ?>">
                                            <svg class="eye-open"
                                                 viewBox="0 0 24 24"
                                                 fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                                                      stroke="currentColor"
                                                      stroke-width="2"
                                                      stroke-linecap="round"
                                                      stroke-linejoin="round" />
                                                <circle cx="12"
                                                        cy="12"
                                                        r="3"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                            </svg>
                                            <svg class="eye-closed"
                                                 viewBox="0 0 24 24"
                                                 fill="none"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 style="display: none;">
                                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"
                                                      stroke="currentColor"
                                                      stroke-width="2"
                                                      stroke-linecap="round"
                                                      stroke-linejoin="round" />
                                                <line x1="1"
                                                      y1="1"
                                                      x2="23"
                                                      y2="23"
                                                      stroke="currentColor"
                                                      stroke-width="2"
                                                      stroke-linecap="round"
                                                      stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="echoads-field-description"><?php esc_html_e( 'Your secure API key for authentication with the endpoint.', 'echoads-audio-player' ); ?></p>
                                </div>

                                <div class="echoads-field-group">
                                    <label class="echoads-field-label"
                                           for="<?php echo esc_attr( self::OPTION_BASE_URL ); ?>"><?php esc_html_e( 'Base URL', 'echoads-audio-player' ); ?></label>
                                    <input type="text"
                                           id="<?php echo esc_attr( self::OPTION_BASE_URL ); ?>"
                                           name="<?php echo esc_attr( self::OPTION_BASE_URL ); ?>"
                                           value="<?php echo esc_attr( self::get_base_url() ); ?>"
                                           class="echoads-field-input"
                                           placeholder="example.com/api"
                                           autocomplete="url" />
                                    <p class="echoads-field-description"><?php esc_html_e( 'The base URL for all API endpoints (e.g., example.com/api). All endpoint routes will be appended to this base URL.', 'echoads-audio-player' ); ?></p>
                                </div>

                                <div class="echoads-field-group">
                                    <label class="echoads-field-label"
                                           for="<?php echo esc_attr( self::OPTION_TIMEOUT ); ?>"><?php esc_html_e( 'Request Timeout (seconds)', 'echoads-audio-player' ); ?></label>
                                    <input type="number"
                                           id="<?php echo esc_attr( self::OPTION_TIMEOUT ); ?>"
                                           name="<?php echo esc_attr( self::OPTION_TIMEOUT ); ?>"
                                           value="<?php echo esc_attr( self::get_timeout() ); ?>"
                                           class="echoads-field-input"
                                           min="10"
                                           max="600"
                                           step="1"
                                           placeholder="120" />
                                    <p class="echoads-field-description"><?php esc_html_e( 'Maximum time to wait for the backend to respond (10-600 seconds). Longer articles may require more time. Default: 120 seconds.', 'echoads-audio-player' ); ?></p>
                                </div>
                            </div>

                            <div class="echoads-form-section">
                                <h2><?php esc_html_e( 'Audio Configuration', 'echoads-audio-player' ); ?></h2>
                                <p><?php esc_html_e( 'Configure audio player appearance and placement settings.', 'echoads-audio-player' ); ?></p>

                                <div class="echoads-field-group">
                                    <label class="echoads-field-label"
                                           for="<?php echo esc_attr( self::OPTION_PLAYER_BG_COLOR ); ?>"><?php esc_html_e( 'Player Background Color', 'echoads-audio-player' ); ?></label>
                                    <input type="text"
                                           id="<?php echo esc_attr( self::OPTION_PLAYER_BG_COLOR ); ?>"
                                           name="<?php echo esc_attr( self::OPTION_PLAYER_BG_COLOR ); ?>"
                                           value="<?php echo esc_attr( self::get_player_bg_color() ); ?>"
                                           class="echoads-color-picker" />
                                    <p class="echoads-field-description"><?php esc_html_e( 'Choose a background color for the audio player. Default: #5D33F5', 'echoads-audio-player' ); ?></p>
                                </div>

                                <div class="echoads-field-group">
                                    <label class="echoads-field-label"
                                           for="<?php echo esc_attr( self::OPTION_PLAYER_POSITION ); ?>"><?php esc_html_e( 'Player Position', 'echoads-audio-player' ); ?></label>
                                    <select id="<?php echo esc_attr( self::OPTION_PLAYER_POSITION ); ?>"
                                            name="<?php echo esc_attr( self::OPTION_PLAYER_POSITION ); ?>"
                                            class="echoads-field-input">
                                        <option value="above" <?php selected( self::get_player_position(), 'above' ); ?>>
                                            <?php esc_html_e( 'Above Article', 'echoads-audio-player' ); ?>
                                        </option>
                                        <option value="below" <?php selected( self::get_player_position(), 'below' ); ?>>
                                            <?php esc_html_e( 'Below Article', 'echoads-audio-player' ); ?>
                                        </option>
                                    </select>
                                    <p class="echoads-field-description"><?php esc_html_e( 'Choose where to display the audio player in relation to your post content. Default: Below Article', 'echoads-audio-player' ); ?></p>
                                </div>
                            </div>

                            <?php
                            $multilingual_plugin = EchoAds_Language::get_multilingual_plugin();
                            $site_languages      = EchoAds_Language::get_site_languages();
                            ?>
                            <div class="echoads-form-section echoads-languages">
                                <h2><?php esc_html_e( 'Languages', 'echoads-audio-player' ); ?></h2>
                                <?php if ( '' === $multilingual_plugin ) : ?>
                                    <p><?php esc_html_e( 'No multilingual plugin (Polylang or WPML) is active, so all posts are sent to EchoAds in your website\'s language.', 'echoads-audio-player' ); ?></p>
                                <?php else : ?>
                                    <p>
                                        <?php
                                        printf(
                                            /* translators: %s: multilingual plugin name, Polylang or WPML. */
                                            esc_html__( '%s is active. Each language below is sent to EchoAds as a website of its own, with its own voice, ads and statistics, using this same API key.', 'echoads-audio-player' ),
                                            esc_html( 'polylang' === $multilingual_plugin ? 'Polylang' : 'WPML' )
                                        );
                                        ?>
                                    </p>
                                    <?php if ( ! empty( $site_languages ) ) : ?>
                                        <ul class="echoads-language-list">
                                            <?php foreach ( $site_languages as $code => $name ) : ?>
                                                <li><?php echo esc_html( $name ); ?> <code><?php echo esc_html( $code ); ?></code></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <p class="echoads-field-description"><?php esc_html_e( 'In your EchoAds dashboard, open this website and add every language listed here. Posts in a language that is not set up get no audio until it is added.', 'echoads-audio-player' ); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="echoads-form-actions">
                                <button type="submit"
                                        class="echoads-button echoads-button-primary">
                                    <svg width="16"
                                         height="16"
                                         viewBox="0 0 24 24"
                                         fill="none"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"
                                              fill="currentColor" />
                                        <polyline points="17,21 17,13 7,13 7,21"
                                                  fill="white" />
                                        <polyline points="7,3 7,8 15,8"
                                                  fill="white" />
                                    </svg>
                                    <?php esc_html_e( 'Save Settings', 'echoads-audio-player' ); ?>
                                </button>
                            </div>
                        </form>

                        <div class="echoads-health-check">
                            <h3>
                                <svg width="16"
                                     height="16"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="2" />
                                    <path d="M12 6v6l4 2"
                                          stroke="currentColor"
                                          stroke-width="2"
                                          stroke-linecap="round"
                                          stroke-linejoin="round" />
                                </svg>
                                <?php esc_html_e( 'Health Check', 'echoads-audio-player' ); ?>
                            </h3>
                            <div class="echoads-health-check-content">
                                <div class="echoads-health-check-actions">
                                    <button id="health-check-button"
                                            class="echoads-button">
                                        <svg width="16"
                                             height="16"
                                             viewBox="0 0 24 24"
                                             fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <polyline points="23 4 23 10 17 10"
                                                      stroke="currentColor"
                                                      stroke-width="2"
                                                      stroke-linecap="round"
                                                      stroke-linejoin="round" />
                                            <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"
                                                  stroke="currentColor"
                                                  stroke-width="2"
                                                  stroke-linecap="round"
                                                  stroke-linejoin="round" />
                                        </svg>
                                        <?php esc_html_e( 'Test Connection', 'echoads-audio-player' ); ?>
                                    </button>
                                    <div class="echoads-status-indicator echoads-status-ready"
                                         id="health-status">
                                        <svg width="12"
                                             height="12"
                                             viewBox="0 0 24 24"
                                             fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="12"
                                                    cy="12"
                                                    r="10"
                                                    fill="currentColor" />
                                        </svg>
                                        <?php esc_html_e( 'Ready', 'echoads-audio-player' ); ?>
                                    </div>
                                </div>
                                <div id="health-check-response"
                                     class="echoads-health-response"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting( self::SETTINGS_GROUP, self::OPTION_API_KEY, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( self::SETTINGS_GROUP, self::OPTION_BASE_URL, array(
            'type'              => 'string',
            'sanitize_callback' => array( __CLASS__, 'sanitize_base_url_setting' ),
        ) );
        register_setting( self::SETTINGS_GROUP, self::OPTION_TIMEOUT, array(
            'type'              => 'integer',
            'sanitize_callback' => array( __CLASS__, 'sanitize_timeout' ),
            'default'           => self::DEFAULT_TIMEOUT,
        ) );
        register_setting( self::SETTINGS_GROUP, self::OPTION_PLAYER_BG_COLOR, array(
            'type'              => 'string',
            'sanitize_callback' => array( __CLASS__, 'sanitize_color' ),
            'default'           => self::DEFAULT_BG_COLOR,
        ) );
        register_setting( self::SETTINGS_GROUP, self::OPTION_PLAYER_POSITION, array(
            'type'              => 'string',
            'sanitize_callback' => array( __CLASS__, 'sanitize_position' ),
            'default'           => self::DEFAULT_POSITION,
        ) );
    }

    /**
     * Cleans a Base URL and returns it without protocol or trailing slash
     * (https:// is added when the URL is used). Returns '' if it is not a valid URL.
     *
     * @param string $value Base URL, with or without https://.
     * @return string
     */
    public static function sanitize_base_url( $value ) {
        $value = trim( sanitize_text_field( (string) $value ) );
        $value = rtrim( preg_replace( '#^https?://#i', '', $value ), '/' );
        if ( '' === $value ) {
            return '';
        }

        $url = esc_url_raw( 'https://' . $value, array( 'https' ) );
        if ( '' === $url || false === filter_var( $url, FILTER_VALIDATE_URL ) || ! wp_parse_url( $url, PHP_URL_HOST ) ) {
            return '';
        }

        return rtrim( substr( $url, strlen( 'https://' ) ), '/' );
    }

    /**
     * Sanitize callback for the Base URL setting. Keeps the saved value and shows
     * an error when the new value is not a valid URL.
     *
     * @param string $value Submitted Base URL.
     * @return string
     */
    public static function sanitize_base_url_setting( $value ) {
        $submitted = trim( sanitize_text_field( (string) $value ) );
        $sanitized = self::sanitize_base_url( $submitted );

        if ( '' !== $submitted && '' === $sanitized ) {
            add_settings_error(
                self::OPTION_BASE_URL,
                'echoads_invalid_base_url',
                __( 'The Base URL is not a valid URL, so it was not changed.', 'echoads-audio-player' )
            );
            return self::get_base_url();
        }

        return $sanitized;
    }

    public static function sanitize_timeout( $value ) {
        return min( 600, max( 10, absint( $value ) ) );
    }

    public static function sanitize_color( $value ) {
        $color = sanitize_hex_color( trim( (string) $value ) );
        return $color ? $color : self::DEFAULT_BG_COLOR;
    }

    public static function sanitize_position( $value ) {
        return in_array( $value, array( 'above', 'below' ), true ) ? $value : self::DEFAULT_POSITION;
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'echoads-admin-settings',
            plugins_url( 'assets/css/admin-settings.css', ECHOADS_PLUGIN_FILE ),
            array( 'wp-color-picker' ),
            ECHOADS_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'echoads-admin-settings',
            plugins_url( 'assets/js/admin-settings.js', ECHOADS_PLUGIN_FILE ),
            array( 'jquery', 'wp-color-picker' ),
            ECHOADS_PLUGIN_VERSION,
            true
        );

        wp_localize_script( 'echoads-admin-settings', 'echoadsSettings', array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'echoads_health_check' ),
            'apiKeyField'   => self::OPTION_API_KEY,
            'baseUrlField'  => self::OPTION_BASE_URL,
            'i18n'          => array(
                'showKey'          => __( 'Show API Key', 'echoads-audio-player' ),
                'hideKey'          => __( 'Hide API Key', 'echoads-audio-player' ),
                'missingConfig'    => __( 'Missing Configuration', 'echoads-audio-player' ),
                'missingConfigMsg' => __( 'Please fill in both API Key and Base URL before testing.', 'echoads-audio-player' ),
                'testing'          => __( 'Testing...', 'echoads-audio-player' ),
                'success'          => __( 'Connection Successful', 'echoads-audio-player' ),
                'failed'           => __( 'Connection Failed', 'echoads-audio-player' ),
            ),
        ) );
    }

    /**
     * Tests the API key and Base URL typed in the form (saved or not) from the server,
     * so the result reflects what the plugin itself will see, not the browser's CORS rules.
     */
    public function handle_ajax_health_check() {
        check_ajax_referer( 'echoads_health_check', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'echoads-audio-player' ) ) );
        }

        $api_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        $base_url = isset( $_POST['base_url'] ) ? self::sanitize_base_url( sanitize_text_field( wp_unslash( $_POST['base_url'] ) ) ) : '';

        if ( '' === $api_key || '' === $base_url ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in both API Key and Base URL before testing.', 'echoads-audio-player' ) ) );
        }

        $response = wp_remote_get( 'https://' . $base_url . '/website-articles/health-check', array(
            'headers' => array( 'x-api-key' => $api_key ),
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $json = json_decode( $body, true );
        $data = array(
            'status' => $code,
            'body'   => null === $json ? $body : $json,
        );

        if ( $code >= 200 && $code < 300 ) {
            wp_send_json_success( $data );
        }

        /* translators: %d: HTTP status code. */
        $data['message'] = sprintf( __( 'HTTP %d response received', 'echoads-audio-player' ), $code );
        wp_send_json_error( $data );
    }

    public static function get_api_key() {
        return (string) get_option( self::OPTION_API_KEY, '' );
    }

    private static function get_base_url_with_protocol() {
        $base_url = self::sanitize_base_url( self::get_base_url() );
        return '' === $base_url ? '' : 'https://' . $base_url;
    }

    public static function get_base_url() {
        return (string) get_option( self::OPTION_BASE_URL, '' );
    }

    private static function get_api_route( $route ) {
        $base_url = self::get_base_url_with_protocol();
        return '' === $base_url ? '' : trailingslashit( $base_url ) . $route;
    }

    public static function get_endpoint() {
        return self::get_api_route( 'website-articles' );
    }

    public static function get_audio_endpoint() {
        return self::get_api_route( 'website-articles/audio-urls' );
    }

    public static function get_tracking_endpoint() {
        return self::get_api_route( 'website-articles/track' );
    }

    public static function get_timeout() {
        return self::sanitize_timeout( get_option( self::OPTION_TIMEOUT, self::DEFAULT_TIMEOUT ) );
    }

    public static function get_player_bg_color() {
        return self::sanitize_color( get_option( self::OPTION_PLAYER_BG_COLOR, self::DEFAULT_BG_COLOR ) );
    }

    public static function get_player_position() {
        return self::sanitize_position( get_option( self::OPTION_PLAYER_POSITION, self::DEFAULT_POSITION ) );
    }
}
