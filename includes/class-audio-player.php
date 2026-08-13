<?php

if (!defined('ABSPATH')) {
    exit;
}

class EchoAds_Audio_Player
{

    public function __construct()
    {
        add_filter('the_content', array($this, 'add_audio_player'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function add_audio_player($content)
    {
        global $post;

        if (is_singular() && !is_feed() && !is_admin()) {
            // Only show audio player for posts that have generated audio
            $audio_generated = get_post_meta($post->ID, '_echoads_audio_generated', true);

            // Audio generation is asynchronous: the generated flag is normally set
            // when an editor clicks "Check Status" in wp-admin. Posts whose audio
            // was auto-generated on publish never get that click, so self-heal by
            // polling the backend status here (throttled per post).
            if (!$audio_generated) {
                $audio_generated = $this->maybe_mark_audio_generated($post->ID);
            }

            if ($audio_generated) {
                $audio_player = $this->generate_audio_player($post->ID);
                // Only append/prepend player if valid audio data was found
                if (!empty($audio_player)) {
                    $player_position = EchoAds_Settings::get_player_position();

                    if ($player_position === 'above') {
                        $content = $audio_player . $content;
                    } else {
                        $content .= $audio_player;
                    }
                }
            }
        }

        return $content;
    }

    private function maybe_mark_audio_generated($post_id)
    {
        $audio_requested = get_post_meta($post_id, '_echoads_audio_requested', true);
        if (empty($audio_requested)) {
            return false;
        }

        $transient_key = 'echoads_status_check_' . $post_id;
        if (get_transient($transient_key)) {
            return false;
        }
        set_transient($transient_key, 1, 5 * MINUTE_IN_SECONDS);

        $status = EchoAds_Post_Sender::fetch_audio_status($post_id);
        if ($status === false) {
            return false;
        }

        update_post_meta($post_id, '_echoads_audio_status', $status);

        if ($status === 'COMPLETED') {
            update_post_meta($post_id, '_echoads_audio_generated', current_time('mysql'));
            delete_transient($transient_key);
            return true;
        }

        if ($status === 'FAILED' || $status === 'SKIPPED') {
            // Terminal states: back off much longer so we don't poll a dead article
            // on every uncached page view. A regeneration + manual status check in
            // wp-admin still updates the meta directly, bypassing this throttle.
            set_transient($transient_key, 1, 6 * HOUR_IN_SECONDS);
        }

        return false;
    }

    public function generate_audio_player($post_id)
    {
        $audio_endpoint = EchoAds_Settings::get_audio_endpoint();
        $api_key = EchoAds_Settings::get_api_key();

        if (empty($audio_endpoint)) {
            error_log('Error: Audio endpoint URL is not set.');
            return '<p>Error: Audio endpoint URL is not set. Please configure it in the plugin settings.</p>';
        }

        if (empty($api_key)) {
            error_log('Error: API key is not set for audio endpoint.');
            return '<p>Error: API key is not configured. Please set it in the plugin settings.</p>';
        }

        $audio_data = $this->fetch_audio_data($audio_endpoint, $api_key, $post_id);

        if (!$audio_data) {
            error_log('No audio data available for post ID: ' . $post_id);
            return '';
        }

        return $this->render_audio_player($post_id, $audio_data);
    }

    private function fetch_audio_data($audio_endpoint, $api_key, $post_id)
    {
        // Add externalId query parameter to the endpoint URL
        $audio_endpoint = add_query_arg('externalId', strval($post_id), $audio_endpoint);

        $args = array(
            'headers' => array(
                'x-api-key' => $api_key
            ),
            'timeout' => 30
        );

        $response = wp_remote_get($audio_endpoint, $args);

        if (is_wp_error($response)) {
            error_log('Error fetching audio data: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $audio_info = json_decode($body, true);

        error_log('Audio endpoint response: ' . $body);
        error_log('Decoded audio info: ' . print_r($audio_info, true));

        return $this->parse_audio_response($audio_info, $body);
    }

    private function parse_audio_response($audio_info, $raw_body)
    {
        $pre_roll_audio_link = null;
        $post_roll_audio_link = null;
        $article_audio_link = null;
        $pre_roll_audio_id = null;
        $post_roll_audio_id = null;
        $article_audio_id = null;

        $audio_urls = null;

        if (isset($audio_info['success']) && $audio_info['success'] === true && isset($audio_info['data'])) {
            if (isset($audio_info['data']['audioUrls'])) {
                $audio_urls = $audio_info['data']['audioUrls'];
            } else {
                $audio_urls = $audio_info['data'];
            }
        } elseif (isset($audio_info['audioUrls'])) {
            $audio_urls = $audio_info['audioUrls'];
        } elseif (isset($audio_info['preRollAudioLink']) || isset($audio_info['postRollAudioLink']) || isset($audio_info['articleAudioLink'])) {
            $audio_urls = $audio_info;
        } elseif (is_array($audio_info) && !empty($audio_info)) {
            error_log('Unrecognized audio response format, attempting to parse: ' . print_r($audio_info, true));
            return false;
        } else {
            error_log('Error: No valid audio links found in response. Response was: ' . $raw_body);
            return false;
        }

        if ($audio_urls) {
            $pre_roll_audio_link = isset($audio_urls['preRollAudioLink']) ? $audio_urls['preRollAudioLink'] : null;
            $post_roll_audio_link = isset($audio_urls['postRollAudioLink']) ? $audio_urls['postRollAudioLink'] : null;
            $article_audio_link = isset($audio_urls['articleAudioLink']) ? $audio_urls['articleAudioLink'] : null;
            $pre_roll_audio_id = isset($audio_urls['preRollAudioId']) ? $audio_urls['preRollAudioId'] : null;
            $post_roll_audio_id = isset($audio_urls['postRollAudioId']) ? $audio_urls['postRollAudioId'] : null;
            $article_audio_id = isset($audio_urls['articleAudioId']) ? $audio_urls['articleAudioId'] : null;
        }

        if (!$pre_roll_audio_link && !$post_roll_audio_link && !$article_audio_link) {
            return false;
        }

        return array(
            'preRoll' => $pre_roll_audio_link,
            'article' => $article_audio_link,
            'postRoll' => $post_roll_audio_link,
            'preRollAudioId' => $pre_roll_audio_id,
            'postRollAudioId' => $post_roll_audio_id,
            'articleAudioId' => $article_audio_id
        );
    }

    private function render_audio_player($post_id, $audio_data)
    {
        $unique_id = 'audio-player-' . $post_id;
        $preroll_tracking_endpoint = EchoAds_Settings::get_preroll_tracking_endpoint();
        $postroll_tracking_endpoint = EchoAds_Settings::get_postroll_tracking_endpoint();
        $api_key = EchoAds_Settings::get_api_key();
        $bg_color = EchoAds_Settings::get_player_bg_color();
        $endpoint = EchoAds_Settings::get_endpoint();

        // Construct status and config endpoint URLs (endpoint already includes base path e.g. .../website-articles)
        $status_endpoint = '';
        $config_endpoint = '';
        if (!empty($endpoint)) {
            $base = rtrim($endpoint, '/');
            $status_endpoint = $base . '/' . $post_id . '/status';
            $config_endpoint = $base . '/config';
        }

        ob_start();
        ?>
        <!-- React will render the player UI here -->
        <div id="<?php echo esc_attr($unique_id); ?>-wrapper" 
             data-bg-color="<?php echo esc_attr($bg_color); ?>">
        </div>

        <script>
            (function () {
                var audioData = {
                    preRoll: "<?php echo esc_js($audio_data['preRoll']); ?>",
                    article: "<?php echo esc_js($audio_data['article']); ?>",
                    postRoll: "<?php echo esc_js($audio_data['postRoll']); ?>",
                    prerollTrackingUrl: "<?php echo esc_js($preroll_tracking_endpoint); ?>",
                    postrollTrackingUrl: "<?php echo esc_js($postroll_tracking_endpoint); ?>",
                    apiKey: "<?php echo esc_js($api_key); ?>",
                    statusEndpoint: "<?php echo esc_js($status_endpoint); ?>",
                    configEndpoint: "<?php echo esc_js($config_endpoint); ?>",
                    preRollAudioId: <?php echo isset($audio_data['preRollAudioId']) && $audio_data['preRollAudioId'] !== null ? json_encode($audio_data['preRollAudioId']) : 'null'; ?>,
                    postRollAudioId: <?php echo isset($audio_data['postRollAudioId']) && $audio_data['postRollAudioId'] !== null ? json_encode($audio_data['postRollAudioId']) : 'null'; ?>,
                    articleAudioId: <?php echo isset($audio_data['articleAudioId']) && $audio_data['articleAudioId'] !== null ? json_encode($audio_data['articleAudioId']) : 'null'; ?>,
                    articleExternalId: "<?php echo esc_js(strval($post_id)); ?>",
                    pluginVersion: "<?php echo esc_js(ECHOADS_PLUGIN_VERSION); ?>"
                };

                var playerId = "<?php echo esc_js($unique_id); ?>";

                if (typeof window.EchoAdsAudioPlayers === "undefined") {
                    window.EchoAdsAudioPlayers = {};
                }

                window.EchoAdsAudioPlayers[playerId] = audioData;

                if (typeof window.EchoAdsAudioPlayersPendingInit === "undefined") {
                    window.EchoAdsAudioPlayersPendingInit = [];
                }
                window.EchoAdsAudioPlayersPendingInit.push(playerId);

                if (typeof window.EchoAdsAudioController !== "undefined") {
                    window.EchoAdsAudioController.init(playerId);
                }
            })();
        </script>
        <?php
        return ob_get_clean();
    }

    public function enqueue_assets()
    {
        $plugin_url = plugin_dir_url(dirname(__FILE__));
        $plugin_path = plugin_dir_path(dirname(__FILE__));

        $css_version = $this->get_file_version($plugin_path . 'assets/dist/echoads-audio-player.css');
        $js_version = $this->get_file_version($plugin_path . 'assets/dist/echoads-audio-player.js');

        wp_enqueue_style(
            'echoads-audio-player',
            $plugin_url . 'assets/dist/echoads-audio-player.css',
            array(),
            $css_version
        );

        wp_enqueue_script(
            'echoads-audio-player',
            $plugin_url . 'assets/dist/echoads-audio-player.js',
            array(),
            $js_version,
            true
        );
    }

    /**
     * Get file modification time for cache busting.
     * Falls back to '1.0.0' if file doesn't exist.
     *
     * @param string $file_path Absolute path to the file.
     * @return string File modification timestamp or '1.0.0' as fallback.
     */
    private function get_file_version($file_path)
    {
        if (file_exists($file_path)) {
            return filemtime($file_path);
        }
        return '1.0.2';
    }
}