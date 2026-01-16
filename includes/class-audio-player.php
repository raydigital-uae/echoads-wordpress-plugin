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

        // Construct status endpoint URL
        $status_endpoint = '';
        if (!empty($endpoint)) {
            $status_endpoint = rtrim($endpoint, '/') . '/api/website-articles/' . $post_id . '/status';
        }

        ob_start();
        ?>
        <div class="echoads-player-wrapper"
             id="<?php echo esc_attr($unique_id); ?>-wrapper">
            <!-- Listen Button (Initial State) -->
            <div class="echoads-listen-btn-container"
                 id="<?php echo esc_attr($unique_id); ?>-listen-btn-container"
                 role="button"
                 aria-label="Listen to this article"
                 tabindex="0">
                <button class="echoads-listen-btn"
                        style="background: <?php echo esc_attr($bg_color); ?>;"
                        tabindex="-1">
                    <svg class="echoads-listen-icon"
                         width="24"
                         height="24"
                         viewBox="0 0 24 24"
                         fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.2111 11.1056L9.73666 7.86833C8.93878 7.46939 8 8.04958 8 8.94164V15.0584C8 15.9504 8.93878 16.5306 9.73666 16.1317L16.2111 12.8944C16.9482 12.5259 16.9482 11.4741 16.2111 11.1056Z"
                              fill="white"
                              stroke="white"
                              stroke-width="2"
                              stroke-linecap="round"
                              stroke-linejoin="round" />
                    </svg>
                </button>

                <span class="echoads-listen-text">Listen to this Article</span>
            </div>

            <!-- Audio Player (Hidden Initially) -->
            <div class="echoads-audio-player echoads-hidden"
                 id="<?php echo esc_attr($unique_id); ?>"
                 style="background: <?php echo esc_attr($bg_color); ?>;"
                 tabindex="0"
                 role="region"
                 aria-label="Audio Player">

                <!-- Hidden elements for track info and status -->
                <span class="sr-only"
                      id="<?php echo esc_attr($unique_id); ?>-track">Audio Player</span>
                <span class="sr-only"
                      id="<?php echo esc_attr($unique_id); ?>-status">Ready</span>

                <!-- Play/Pause Button -->
                <button class="echoads-play-pause-btn"
                        id="<?php echo esc_attr($unique_id); ?>-play-pause"
                        title="Play/Pause"
                        aria-label="Play"
                        tabindex="0">

                    <svg class="play-icon"
                         width="24"
                         height="24"
                         viewBox="0 0 24 24"
                         fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.2111 11.1056L9.73666 7.86833C8.93878 7.46939 8 8.04958 8 8.94164V15.0584C8 15.9504 8.93878 16.5306 9.73666 16.1317L16.2111 12.8944C16.9482 12.5259 16.9482 11.4741 16.2111 11.1056Z"
                              fill="white"
                              stroke="white"
                              stroke-width="2"
                              stroke-linecap="round"
                              stroke-linejoin="round" />
                    </svg>

                    <svg class="pause-icon"
                         style="display: none;"
                         width="24"
                         height="24"
                         viewBox="0 0 24 24"
                         fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <rect x="6"
                              y="5"
                              width="4"
                              height="14"
                              rx="1"
                              fill="white" />
                        <rect x="14"
                              y="5"
                              width="4"
                              height="14"
                              rx="1"
                              fill="white" />
                    </svg>
                </button>

                <!-- Waveform Visualization -->
                <div class="echoads-waveform"
                     id="<?php echo esc_attr($unique_id); ?>-progress"
                     role="slider"
                     aria-label="Audio progress"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-valuenow="0"
                     tabindex="0">
                    <div class="echoads-waveform-bars">
                        <?php for ($i = 0; $i < 24; $i++): ?>
                            <div class="echoads-bar"
                                 data-index="<?php echo $i; ?>"></div>
                        <?php endfor; ?>
                    </div>
                    <div class="echoads-waveform-progress"
                         id="<?php echo esc_attr($unique_id); ?>-fill"></div>
                </div>

                <!-- Time Display -->
                <div class="echoads-time-display">
                    <span id="<?php echo esc_attr($unique_id); ?>-current-time">0:00</span>
                </div>

                <!-- Volume Control -->
                <div class="echoads-volume-control"
                     id="<?php echo esc_attr($unique_id); ?>-volume-control">
                    <button class="echoads-volume-btn"
                            id="<?php echo esc_attr($unique_id); ?>-volume-btn"
                            title="Volume"
                            aria-label="Volume"
                            aria-expanded="false"
                            tabindex="0">
                        <svg class="volume-icon"
                             width="24"
                             height="24"
                             viewBox="0 0 24 24"
                             fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.15838 13.9306C2.44537 12.7423 2.44537 11.2577 3.15838 10.0694C3.37596 9.70674 3.73641 9.45272 4.1511 9.36978L5.84413 9.03117C5.94499 9.011 6.03591 8.95691 6.10176 8.87788L8.17085 6.39498C9.3534 4.97592 9.94468 4.26638 10.4723 4.45742C11 4.64846 11 5.57207 11 7.41928L11 16.5807C11 18.4279 11 19.3515 10.4723 19.5426C9.94468 19.7336 9.3534 19.0241 8.17085 17.605L6.10176 15.1221C6.03591 15.0431 5.94499 14.989 5.84413 14.9688L4.1511 14.6302C3.73641 14.5473 3.37596 14.2933 3.15838 13.9306Z"
                                  stroke="white"
                                  stroke-width="2" />
                            <path d="M15.5355 8.46447C16.4684 9.39732 16.9948 10.6611 17 11.9803C17.0052 13.2996 16.4888 14.5674 15.5633 15.5076"
                                  stroke="white"
                                  stroke-width="2"
                                  stroke-linecap="round" />
                            <path d="M19.6569 6.34314C21.1494 7.83572 21.9916 9.85769 21.9999 11.9685C22.0083 14.0793 21.182 16.1078 19.7012 17.6121"
                                  stroke="white"
                                  stroke-width="2"
                                  stroke-linecap="round" />
                        </svg>
                        <svg class="volume-muted-icon"
                             style="display: none;"
                             width="24"
                             height="24"
                             viewBox="0 0 24 24"
                             fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.15838 13.9306C2.44537 12.7423 2.44537 11.2577 3.15838 10.0694C3.37596 9.70674 3.73641 9.45272 4.1511 9.36978L5.84413 9.03117C5.94499 9.011 6.03591 8.95691 6.10176 8.87788L8.17085 6.39498C9.3534 4.97592 9.94468 4.26638 10.4723 4.45742C11 4.64846 11 5.57207 11 7.41928L11 16.5807C11 18.4279 11 19.3515 10.4723 19.5426C9.94468 19.7336 9.3534 19.0241 8.17085 17.605L6.10176 15.1221C6.03591 15.0431 5.94499 14.989 5.84413 14.9688L4.1511 14.6302C3.73641 14.5473 3.37596 14.2933 3.15838 13.9306Z"
                                  stroke="white"
                                  stroke-width="2" />
                        </svg>
                    </button>

                    <!-- Volume Popup -->
                    <div class="echoads-volume-popup"
                         id="<?php echo esc_attr($unique_id); ?>-volume-popup">
                        <div class="echoads-volume-slider-wrapper">
                            <input type="range"
                                   class="echoads-volume-slider"
                                   min="0"
                                   max="100"
                                   value="80"
                                   id="<?php echo esc_attr($unique_id); ?>-volume-input"
                                   aria-label="Volume level"
                                   orient="vertical">
                            <div class="echoads-volume-track">
                                <div class="echoads-volume-fill"
                                     id="<?php echo esc_attr($unique_id); ?>-volume-fill"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden Audio Element -->
                <audio preload="metadata"
                       id="<?php echo esc_attr($unique_id); ?>-audio">
                    Your browser does not support the audio element.
                </audio>

                <!-- Hidden duration for JS -->
                <span class="sr-only"
                      id="<?php echo esc_attr($unique_id); ?>-duration">0:00</span>
            </div>
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
                    preRollAudioId: <?php echo isset($audio_data['preRollAudioId']) && $audio_data['preRollAudioId'] !== null ? json_encode($audio_data['preRollAudioId']) : 'null'; ?>,
                    postRollAudioId: <?php echo isset($audio_data['postRollAudioId']) && $audio_data['postRollAudioId'] !== null ? json_encode($audio_data['postRollAudioId']) : 'null'; ?>,
                    articleAudioId: <?php echo isset($audio_data['articleAudioId']) && $audio_data['articleAudioId'] !== null ? json_encode($audio_data['articleAudioId']) : 'null'; ?>
                };

                var playerId = "<?php echo esc_js($unique_id); ?>";

                if (typeof window.EchoAdsAudioPlayers === "undefined") {
                    window.EchoAdsAudioPlayers = {};
                }

                window.EchoAdsAudioPlayers[playerId] = audioData;

                setTimeout(function () {
                    if (typeof window.EchoAdsAudioController !== "undefined") {
                        window.EchoAdsAudioController.init(playerId);
                    } else {
                        console.error("EchoAdsAudioController not available");
                    }
                }, 100);
            })();
        </script>
        <?php
        return ob_get_clean();
    }

    public function enqueue_assets()
    {
        $plugin_url = plugin_dir_url(dirname(__FILE__));
        $plugin_path = plugin_dir_path(dirname(__FILE__));

        $css_version = $this->get_file_version($plugin_path . 'assets/css/audio-player.css');
        $js_version = $this->get_file_version($plugin_path . 'assets/js/audio-player.js');

        wp_enqueue_style(
            'echoads-audio-player',
            $plugin_url . 'assets/css/audio-player.css',
            array(),
            $css_version
        );

        wp_enqueue_script(
            'echoads-audio-player',
            $plugin_url . 'assets/js/audio-player.js',
            array('jquery'),
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
        return '1.0.0';
    }
}