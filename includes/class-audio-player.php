<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EchoAds_Audio_Player {

    public function __construct() {
        add_filter( 'the_content', array( $this, 'add_audio_player' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function add_audio_player( $content ) {
        global $post;

        if ( is_singular() && ! is_feed() && ! is_admin() ) {
            // Only show audio player for posts that have generated audio
            $audio_generated = get_post_meta( $post->ID, '_echoads_audio_generated', true );

            if ( $audio_generated ) {
                $audio_player = $this->generate_audio_player( $post->ID );
                $content .= $audio_player;
            }
        }

        return $content;
    }

    public function generate_audio_player( $post_id ) {
        $audio_endpoint = EchoAds_Settings::get_audio_endpoint();
        $api_key = EchoAds_Settings::get_api_key();

        if ( empty( $audio_endpoint ) ) {
            error_log( 'Error: Audio endpoint URL is not set.' );
            return '<p>Error: Audio endpoint URL is not set. Please configure it in the plugin settings.</p>';
        }

        if ( empty( $api_key ) ) {
            error_log( 'Error: API key is not set for audio endpoint.' );
            return '<p>Error: API key is not configured. Please set it in the plugin settings.</p>';
        }

        $audio_data = $this->fetch_audio_data( $audio_endpoint, $api_key );

        if ( ! $audio_data ) {
            return '<p>Error fetching audio.</p>';
        }

        return $this->render_audio_player( $post_id, $audio_data );
    }

    private function fetch_audio_data( $audio_endpoint, $api_key ) {
        $args = array(
            'headers' => array(
                'x-api-key' => $api_key
            ),
            'timeout' => 30
        );

        $response = wp_remote_get( $audio_endpoint, $args );

        if ( is_wp_error( $response ) ) {
            error_log( 'Error fetching audio data: ' . $response->get_error_message() );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $audio_info = json_decode( $body, true );

        error_log( 'Audio endpoint response: ' . $body );
        error_log( 'Decoded audio info: ' . print_r( $audio_info, true ) );

        return $this->parse_audio_response( $audio_info, $body );
    }

    private function parse_audio_response( $audio_info, $raw_body ) {
        $pre_roll_audio_link = null;
        $post_roll_audio_link = null;
        $article_audio_link = null;

        if ( isset( $audio_info['success'] ) && $audio_info['success'] === true && isset( $audio_info['data'] ) ) {
            $pre_roll_audio_link = $audio_info['data']['preRollAudioLink'];
            $post_roll_audio_link = $audio_info['data']['postRollAudioLink'];
            $article_audio_link = $audio_info['data']['articleAudioLink'];
        } elseif ( isset( $audio_info['preRollAudioLink'] ) || isset( $audio_info['postRollAudioLink'] ) || isset( $audio_info['articleAudioLink'] ) ) {
            $pre_roll_audio_link = isset( $audio_info['preRollAudioLink'] ) ? $audio_info['preRollAudioLink'] : null;
            $post_roll_audio_link = isset( $audio_info['postRollAudioLink'] ) ? $audio_info['postRollAudioLink'] : null;
            $article_audio_link = isset( $audio_info['articleAudioLink'] ) ? $audio_info['articleAudioLink'] : null;
        } elseif ( is_array( $audio_info ) && ! empty( $audio_info ) ) {
            error_log( 'Unrecognized audio response format, attempting to parse: ' . print_r( $audio_info, true ) );
            return false;
        } else {
            error_log( 'Error: No valid audio links found in response. Response was: ' . $raw_body );
            return false;
        }

        if ( ! $pre_roll_audio_link && ! $post_roll_audio_link && ! $article_audio_link ) {
            return false;
        }

        return array(
            'preRoll' => $pre_roll_audio_link,
            'article' => $article_audio_link,
            'postRoll' => $post_roll_audio_link
        );
    }

    private function render_audio_player( $post_id, $audio_data ) {
        $unique_id = 'audio-player-' . $post_id;
        $preroll_tracking_endpoint = EchoAds_Settings::get_preroll_tracking_endpoint();
        $postroll_tracking_endpoint = EchoAds_Settings::get_postroll_tracking_endpoint();

        ob_start();
        ?>
        <div class="echoads-audio-player" id="<?php echo esc_attr( $unique_id ); ?>">
            <div class="audio-player-header">
                <div class="audio-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="track-info">
                    <div class="current-track" id="<?php echo esc_attr( $unique_id ); ?>-track">Audio Player</div>
                    <div class="track-subtitle">EchoAds Audio Experience</div>
                </div>
                <div class="audio-status" id="<?php echo esc_attr( $unique_id ); ?>-status">Ready</div>
            </div>
            
            <div class="audio-progress-section">
                <div class="progress-bar" id="<?php echo esc_attr( $unique_id ); ?>-progress">
                    <div class="progress-fill" id="<?php echo esc_attr( $unique_id ); ?>-fill"></div>
                    <div class="progress-handle" id="<?php echo esc_attr( $unique_id ); ?>-handle"></div>
                </div>
                <div class="time-display">
                    <span id="<?php echo esc_attr( $unique_id ); ?>-current-time">0:00</span>
                    <span class="time-separator">/</span>
                    <span id="<?php echo esc_attr( $unique_id ); ?>-duration">0:00</span>
                </div>
            </div>
            
            <div class="audio-controls">
                <button class="play-pause-btn" id="<?php echo esc_attr( $unique_id ); ?>-play-pause" title="Play/Pause">
                    <svg class="play-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 5v14l11-7z" fill="currentColor"/>
                    </svg>
                    <svg class="pause-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: none;">
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" fill="currentColor"/>
                    </svg>
                </button>
                
                <div class="volume-control">
                    <button class="control-btn volume-btn" id="<?php echo esc_attr( $unique_id ); ?>-volume-btn" title="Volume">
                        <svg class="volume-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z" fill="currentColor"/>
                        </svg>
                    </button>
                    <div class="volume-slider" id="<?php echo esc_attr( $unique_id ); ?>-volume-slider">
                        <input type="range" min="0" max="100" value="80" id="<?php echo esc_attr( $unique_id ); ?>-volume-input">
                    </div>
                </div>
            </div>
            
            <audio preload="metadata" id="<?php echo esc_attr( $unique_id ); ?>-audio">
                Your browser does not support the audio element.
            </audio>
        </div>
        <script>
        (function() {
            var audioData = {
                preRoll: "<?php echo esc_js( $audio_data['preRoll'] ); ?>",
                article: "<?php echo esc_js( $audio_data['article'] ); ?>",
                postRoll: "<?php echo esc_js( $audio_data['postRoll'] ); ?>",
                prerollTrackingUrl: "<?php echo esc_js( $preroll_tracking_endpoint ); ?>",
                postrollTrackingUrl: "<?php echo esc_js( $postroll_tracking_endpoint ); ?>"
            };
            
            var playerId = "<?php echo esc_js( $unique_id ); ?>";
            
            if (typeof window.EchoAdsAudioPlayers === "undefined") {
                window.EchoAdsAudioPlayers = {};
            }
            
            window.EchoAdsAudioPlayers[playerId] = audioData;
            
            setTimeout(function() {
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

    public function enqueue_assets() {
        $plugin_url = plugin_dir_url( dirname( __FILE__ ) );
        
        wp_enqueue_style( 
            'echoads-audio-player', 
            $plugin_url . 'assets/css/audio-player.css', 
            array(), 
            '1.0.0' 
        );
        
        wp_enqueue_script( 
            'echoads-audio-player', 
            $plugin_url . 'assets/js/audio-player.js', 
            array( 'jquery' ), 
            '1.0.0', 
            true 
        );
    }
}