<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EchoAds_Settings {

    const OPTION_API_KEY = 'auto_send_plugin_api_key';
    const OPTION_ENDPOINT = 'auto_send_plugin_endpoint';
    const OPTION_AUDIO_ENDPOINT = 'auto_send_plugin_audio_endpoint';
    const OPTION_PREROLL_TRACKING = 'auto_send_plugin_preroll_tracking_endpoint';
    const OPTION_POSTROLL_TRACKING = 'auto_send_plugin_postroll_tracking_endpoint';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    public function add_settings_page() {
        add_options_page(
            'EchoAds Settings',
            'EchoAds',
            'manage_options',
            'auto-send-plugin',
            array( $this, 'settings_page_content' )
        );
    }

    public function settings_page_content() {
        ?>
        <div class="wrap">
            <h1>EchoAds Settings</h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields( 'auto_send_plugin_settings' );
                    do_settings_sections( 'auto-send-plugin' );
                    submit_button();
                ?>
            </form>
            <button id="health-check-button" class="button button-primary">Check Health</button>
            <div id="health-check-response"></div>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting( 'auto_send_plugin_settings', self::OPTION_API_KEY );
        register_setting( 'auto_send_plugin_settings', self::OPTION_ENDPOINT );
        register_setting( 'auto_send_plugin_settings', self::OPTION_AUDIO_ENDPOINT );
        register_setting( 'auto_send_plugin_settings', self::OPTION_PREROLL_TRACKING );
        register_setting( 'auto_send_plugin_settings', self::OPTION_POSTROLL_TRACKING );

        add_settings_section(
            'auto_send_plugin_settings_section',
            'Plugin Settings',
            array( $this, 'settings_section_content' ),
            'auto-send-plugin'
        );

        add_settings_field(
            self::OPTION_API_KEY,
            'API Key',
            array( $this, 'api_key_field' ),
            'auto-send-plugin',
            'auto_send_plugin_settings_section'
        );

        add_settings_field(
            self::OPTION_ENDPOINT,
            'Endpoint URL',
            array( $this, 'endpoint_field' ),
            'auto-send-plugin',
            'auto_send_plugin_settings_section'
        );

        add_settings_field(
            self::OPTION_AUDIO_ENDPOINT,
            'Audio Endpoint URL',
            array( $this, 'audio_endpoint_field' ),
            'auto-send-plugin',
            'auto_send_plugin_settings_section'
        );

        add_settings_field(
            self::OPTION_PREROLL_TRACKING,
            'Pre-Roll Tracking Endpoint',
            array( $this, 'preroll_tracking_field' ),
            'auto-send-plugin',
            'auto_send_plugin_settings_section'
        );

        add_settings_field(
            self::OPTION_POSTROLL_TRACKING,
            'Post-Roll Tracking Endpoint',
            array( $this, 'postroll_tracking_field' ),
            'auto-send-plugin',
            'auto_send_plugin_settings_section'
        );
    }

    public function settings_section_content() {
        echo '<p>Enter your API key and endpoint URL.</p>';
    }

    public function api_key_field() {
        $value = get_option( self::OPTION_API_KEY );
        echo '<input type="text" name="' . self::OPTION_API_KEY . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
    }

    public function endpoint_field() {
        $value = get_option( self::OPTION_ENDPOINT );
        echo '<input type="text" name="' . self::OPTION_ENDPOINT . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
    }

    public function audio_endpoint_field() {
        $value = get_option( self::OPTION_AUDIO_ENDPOINT );
        echo '<input type="text" name="' . self::OPTION_AUDIO_ENDPOINT . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">Enter the URL to fetch audio links from.</p>';
    }

    public function preroll_tracking_field() {
        $value = get_option( self::OPTION_PREROLL_TRACKING );
        echo '<input type="text" name="' . self::OPTION_PREROLL_TRACKING . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">Enter the URL to call when pre-roll ad starts playing.</p>';
    }

    public function postroll_tracking_field() {
        $value = get_option( self::OPTION_POSTROLL_TRACKING );
        echo '<input type="text" name="' . self::OPTION_POSTROLL_TRACKING . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">Enter the URL to call when post-roll ad starts playing.</p>';
    }

    public function enqueue_admin_scripts() {
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'auto-send-plugin' ) {
            wp_enqueue_script( 'jquery' );
            wp_add_inline_script( 'jquery', $this->get_health_check_script() );
        }
    }

    private function get_health_check_script() {
        return "
        jQuery(document).ready(function($) {
            $('#health-check-button').click(function() {
                var apiKey = $('input[name=\"" . self::OPTION_API_KEY . "\"]').val();
                var endpoint = $('input[name=\"" . self::OPTION_ENDPOINT . "\"]').val();
                var healthCheckUrl = endpoint + '/health-check';

                $.ajax({
                    url: healthCheckUrl,
                    type: 'GET',
                    headers: {
                        'x-api-key': apiKey
                    },
                    success: function(response) {
                        $('#health-check-response').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
                    },
                    error: function(xhr, status, error) {
                        $('#health-check-response').html('<pre>Error: ' + error + '</pre>');
                    }
                });
            });
        });
        ";
    }

    public static function get_api_key() {
        return get_option( self::OPTION_API_KEY );
    }

    public static function get_endpoint() {
        return get_option( self::OPTION_ENDPOINT );
    }

    public static function get_audio_endpoint() {
        return get_option( self::OPTION_AUDIO_ENDPOINT );
    }

    public static function get_preroll_tracking_endpoint() {
        return get_option( self::OPTION_PREROLL_TRACKING );
    }

    public static function get_postroll_tracking_endpoint() {
        return get_option( self::OPTION_POSTROLL_TRACKING );
    }
}