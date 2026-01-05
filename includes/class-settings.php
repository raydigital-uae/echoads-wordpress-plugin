<?php

if (!defined('ABSPATH')) {
    exit;
}

class EchoAds_Settings
{

    const OPTION_API_KEY = 'auto_send_plugin_api_key';
    const OPTION_BASE_URL = 'auto_send_plugin_base_url';
    const OPTION_TIMEOUT = 'auto_send_plugin_timeout';
    const OPTION_PLAYER_BG_COLOR = 'auto_send_plugin_player_bg_color';

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_settings_page()
    {
        add_options_page(
            'EchoAds Settings',
            'EchoAds',
            'manage_options',
            'auto-send-plugin',
            array($this, 'settings_page_content')
        );
    }

    public function settings_page_content()
    {
        ?>
        <div class="echoads-admin-page">
            <div class="wrap">
                <div class="echoads-settings-container">
                    <div class="echoads-settings-header">
                        <div class="echoads-settings-icon">
                            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/white-logo.png'; ?>"
                                 alt="EchoAds Logo"
                                 style="max-width: 200px; height: auto;" />
                        </div>
                        <div class="echoads-settings-title">
                            <h1>EchoAds Settings</h1>
                            <p class="echoads-settings-subtitle">Configure your audio player and API endpoints</p>
                        </div>
                    </div>

                    <div class="echoads-content-area">
                        <form method="post"
                              action="options.php">
                            <?php settings_fields('auto_send_plugin_settings'); ?>

                            <div class="echoads-form-section">
                                <h2>API Configuration</h2>
                                <p>Configure your API credentials and main endpoint for post data transmission.</p>

                                <div class="echoads-field-group">
                                    <label class="echoads-field-label"
                                           for="<?php echo esc_attr(self::OPTION_API_KEY); ?>">API Key</label>
                                    <div class="echoads-field-wrapper">
                                        <input type="password"
                                               id="<?php echo esc_attr(self::OPTION_API_KEY); ?>"
                                               name="<?php echo esc_attr(self::OPTION_API_KEY); ?>"
                                               value="<?php echo esc_attr(get_option(self::OPTION_API_KEY)); ?>"
                                               class="echoads-field-input has-toggle"
                                               placeholder="Enter your API key"
                                               autocomplete="off" />
                                        <button type="button"
                                                class="echoads-toggle-button"
                                                id="api-key-toggle"
                                                title="Show/Hide API Key">
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
                                    <p class="echoads-field-description">Your secure API key for authentication with the
                                        endpoint.</p>
                                </div>

                                <div class="echoads-field-group">
                                    <label class="echoads-field-label"
                                           for="<?php echo esc_attr(self::OPTION_BASE_URL); ?>">Base URL</label>
                                    <input type="text"
                                           id="<?php echo esc_attr(self::OPTION_BASE_URL); ?>"
                                           name="<?php echo esc_attr(self::OPTION_BASE_URL); ?>"
                                           value="<?php echo esc_attr(get_option(self::OPTION_BASE_URL)); ?>"
                                           class="echoads-field-input"
                                           placeholder="example.com/api"
                                           autocomplete="url" />
                                    <p class="echoads-field-description">The base URL for all API endpoints (e.g., example.com/api). All endpoint routes will be appended to this base URL.</p>
                                </div>

                                <div class="echoads-field-group">
                                    <label class="echoads-field-label"
                                           for="<?php echo esc_attr(self::OPTION_TIMEOUT); ?>">Request Timeout
                                        (seconds)</label>
                                    <input type="number"
                                           id="<?php echo esc_attr(self::OPTION_TIMEOUT); ?>"
                                           name="<?php echo esc_attr(self::OPTION_TIMEOUT); ?>"
                                           value="<?php echo esc_attr(get_option(self::OPTION_TIMEOUT, 120)); ?>"
                                           class="echoads-field-input"
                                           min="10"
                                           max="600"
                                           step="1"
                                           placeholder="120" />
                                    <p class="echoads-field-description">Maximum time to wait for the backend to respond (10-600
                                        seconds). Longer articles may require more time. Default: 120 seconds.</p>
                                </div>
                            </div>

                            <div class="echoads-form-section">
                                <h2>Audio Configuration</h2>
                                <p>Configure audio player appearance settings.</p>

                                <div class="echoads-field-group">
                                    <label class="echoads-field-label"
                                           for="<?php echo esc_attr(self::OPTION_PLAYER_BG_COLOR); ?>">Player Background
                                        Color</label>
                                    <input type="text"
                                           id="<?php echo esc_attr(self::OPTION_PLAYER_BG_COLOR); ?>"
                                           name="<?php echo esc_attr(self::OPTION_PLAYER_BG_COLOR); ?>"
                                           value="<?php echo esc_attr(get_option(self::OPTION_PLAYER_BG_COLOR, '#5D33F5')); ?>"
                                           class="echoads-color-picker" />
                                    <p class="echoads-field-description">Choose a background color for the audio player. Default:
                                        #5D33F5</p>
                                </div>
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
                                    Save Settings
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
                                Health Check
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
                                        Test Connection
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
                                        Ready
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

    public function register_settings()
    {
        register_setting('auto_send_plugin_settings', self::OPTION_API_KEY);
        register_setting('auto_send_plugin_settings', self::OPTION_BASE_URL, array(
            'sanitize_callback' => array($this, 'sanitize_base_url')
        ));
        register_setting('auto_send_plugin_settings', self::OPTION_TIMEOUT, array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_timeout'),
            'default' => 120
        ));
        register_setting('auto_send_plugin_settings', self::OPTION_PLAYER_BG_COLOR, array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_color'),
            'default' => '#5D33F5'
        ));
    }

    public function sanitize_base_url($value)
    {
        $value = trim($value);
        // Remove protocol if present
        $value = preg_replace('#^https?://#', '', $value);
        // Remove trailing slash
        $value = rtrim($value, '/');
        return $value;
    }

    public function sanitize_timeout($value)
    {
        $value = absint($value);
        if ($value < 10) {
            $value = 10;
        }
        if ($value > 600) {
            $value = 600;
        }
        return $value;
    }

    public function sanitize_color($value)
    {
        // Remove any whitespace
        $value = trim($value);
        
        // Validate hex color format
        if (preg_match('/^#[a-fA-F0-9]{6}$/', $value)) {
            return $value;
        }
        
        // Return default color if invalid
        return '#5D33F5';
    }


    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'settings_page_auto-send-plugin') {
            return;
        }

        $plugin_url = plugin_dir_url(dirname(__FILE__));

        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        
        wp_enqueue_style(
            'echoads-admin-settings',
            $plugin_url . 'assets/css/admin-settings.css',
            array('wp-color-picker'),
            '1.0.0'
        );

        wp_enqueue_script('jquery');
        wp_enqueue_script('wp-color-picker');
        wp_add_inline_script('jquery', $this->get_health_check_script());
    }

    private function get_health_check_script()
    {
        return "
        jQuery(document).ready(function($) {
            // Initialize color picker
            $('.echoads-color-picker').wpColorPicker();

            // API Key visibility toggle
            $('#api-key-toggle').click(function(e) {
                e.preventDefault();
                var input = $('#" . self::OPTION_API_KEY . "');
                var eyeOpen = $(this).find('.eye-open');
                var eyeClosed = $(this).find('.eye-closed');

                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    eyeOpen.hide();
                    eyeClosed.show();
                    $(this).attr('title', 'Hide API Key');
                } else {
                    input.attr('type', 'password');
                    eyeOpen.show();
                    eyeClosed.hide();
                    $(this).attr('title', 'Show API Key');
                }
            });

            // Health check functionality
            $('#health-check-button').click(function() {
                var apiKey = $('input[name=\"" . self::OPTION_API_KEY . "\"]').val();
                var baseUrl = $('input[name=\"" . self::OPTION_BASE_URL . "\"]').val();
                
                if (!apiKey || !baseUrl) {
                    var statusIndicator = $('#health-status');
                    var responseDiv = $('#health-check-response');
                    statusIndicator.removeClass().addClass('echoads-status-indicator echoads-status-error').text('Missing Configuration');
                    responseDiv.text('Please fill in both API Key and Base URL before testing.');
                    return;
                }
                
                // Ensure base URL has protocol
                var baseUrlWithProtocol = baseUrl;
                if (!baseUrl.match(/^https?:\/\//)) {
                    baseUrlWithProtocol = 'https://' + baseUrl;
                }
                
                var healthCheckUrl = baseUrlWithProtocol + '/website-articles/health-check';
                var statusIndicator = $('#health-status');
                var responseDiv = $('#health-check-response');

                statusIndicator.removeClass().addClass('echoads-status-indicator echoads-status-loading').text('Testing...');
                responseDiv.empty();

                $.ajax({
                    url: healthCheckUrl,
                    type: 'GET',
                    headers: {
                        'x-api-key': apiKey
                    },
                    timeout: 10000,
                    success: function(response) {
                        statusIndicator.removeClass().addClass('echoads-status-indicator echoads-status-success').text('Connection Successful');
                        responseDiv.text(JSON.stringify(response, null, 2));
                    },
                    error: function(xhr, status, error) {
                        statusIndicator.removeClass().addClass('echoads-status-indicator echoads-status-error').text('Connection Failed');
                        var errorMsg = 'Error: ' + error;
                        if (xhr.responseText) {
                            try {
                                var errorResponse = JSON.parse(xhr.responseText);
                                errorMsg += '\\n' + JSON.stringify(errorResponse, null, 2);
                            } catch (e) {
                                errorMsg += '\\n' + xhr.responseText;
                            }
                        }
                        responseDiv.text(errorMsg);
                    }
                });
            });
        });
        ";
    }

    public static function get_api_key()
    {
        return get_option(self::OPTION_API_KEY);
    }

    private static function get_base_url_with_protocol()
    {
        $base_url = get_option(self::OPTION_BASE_URL);
        if (empty($base_url)) {
            return '';
        }
        // Remove protocol if present
        $base_url = preg_replace('#^https?://#', '', $base_url);
        // Remove trailing slash
        $base_url = rtrim($base_url, '/');
        // Add https protocol
        return 'https://' . $base_url;
    }

    public static function get_base_url()
    {
        return get_option(self::OPTION_BASE_URL);
    }

    public static function get_endpoint()
    {
        $base_url = self::get_base_url_with_protocol();
        if (empty($base_url)) {
            return '';
        }
        return trailingslashit($base_url) . 'website-articles';
    }

    public static function get_audio_endpoint()
    {
        $base_url = self::get_base_url_with_protocol();
        if (empty($base_url)) {
            return '';
        }
        return trailingslashit($base_url) . 'website-articles/audio-urls';
    }

    public static function get_preroll_tracking_endpoint()
    {
        $base_url = self::get_base_url_with_protocol();
        if (empty($base_url)) {
            return '';
        }
        return trailingslashit($base_url) . 'website-articles/track';
    }

    public static function get_postroll_tracking_endpoint()
    {
        $base_url = self::get_base_url_with_protocol();
        if (empty($base_url)) {
            return '';
        }
        return trailingslashit($base_url) . 'website-articles/track';
    }

    public static function get_status_endpoint($external_id)
    {
        if (empty($external_id)) {
            return '';
        }
        $base_url = self::get_base_url_with_protocol();
        if (empty($base_url)) {
            return '';
        }
        return trailingslashit($base_url) . 'website-articles/' . absint($external_id) . '/status';
    }

    public static function get_timeout()
    {
        $timeout = get_option(self::OPTION_TIMEOUT, 120);
        $timeout = absint($timeout);
        if ($timeout < 10) {
            $timeout = 10;
        }
        if ($timeout > 600) {
            $timeout = 600;
        }
        return $timeout;
    }

    public static function get_player_bg_color()
    {
        return get_option(self::OPTION_PLAYER_BG_COLOR, '#5D33F5');
    }
}