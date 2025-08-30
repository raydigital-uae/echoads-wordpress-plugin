<?php
/*
Plugin Name: EchoAds Audio Player
Description: Sends post data to a specified endpoint, and adds an audio player to the post content, with pre-roll & post-roll ads.
Version: 1.0.0
Author: Hussein Shaltout 
*/

// Function to register the settings page
function auto_send_plugin_settings_page() {
    add_options_page(
        'Auto Send Plugin Settings',
        'Auto Send Plugin',
        'manage_options',
        'auto-send-plugin',
        'auto_send_plugin_settings_page_content'
    );
}
add_action( 'admin_menu', 'auto_send_plugin_settings_page' );

// Function to display the settings page content
function auto_send_plugin_settings_page_content() {
    ?>
    <div class="wrap">
        <h1>Auto Send Plugin Settings</h1>
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
    <script>
        jQuery(document).ready(function($) {
            $('#health-check-button').click(function() {
                var apiKey = $('input[name="auto_send_plugin_api_key"]').val();
                var endpoint = $('input[name="auto_send_plugin_endpoint"]').val();
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
    </script>
    <?php
}

// Function to register the settings
function auto_send_plugin_register_settings() {
    register_setting(
        'auto_send_plugin_settings',
        'auto_send_plugin_api_key'
    );
    register_setting(
        'auto_send_plugin_settings',
        'auto_send_plugin_endpoint'
    );
    register_setting(
        'auto_send_plugin_settings',
        'auto_send_plugin_audio_endpoint'
    );
    register_setting(
        'auto_send_plugin_settings',
        'auto_send_plugin_preroll_tracking_endpoint'
    );
    register_setting(
        'auto_send_plugin_settings',
        'auto_send_plugin_postroll_tracking_endpoint'
    );
    add_settings_section(
        'auto_send_plugin_settings_section',
        'Plugin Settings',
        'auto_send_plugin_settings_section_content',
        'auto-send-plugin'
    );
    add_settings_field(
        'auto_send_plugin_api_key',
        'API Key',
        'auto_send_plugin_api_key_field',
        'auto-send-plugin',
        'auto_send_plugin_settings_section'
    );
    add_settings_field(
        'auto_send_plugin_endpoint',
        'Endpoint URL',
        'auto_send_plugin_endpoint_field',
        'auto-send-plugin',
        'auto_send_plugin_settings_section'
    );
    add_settings_field(
        'auto_send_plugin_audio_endpoint',
        'Audio Endpoint URL',
        'auto_send_plugin_audio_endpoint_field',
        'auto-send-plugin',
        'auto_send_plugin_settings_section'
    );
    add_settings_field(
        'auto_send_plugin_preroll_tracking_endpoint',
        'Pre-Roll Tracking Endpoint',
        'auto_send_plugin_preroll_tracking_endpoint_field',
        'auto-send-plugin',
        'auto_send_plugin_settings_section'
    );
    add_settings_field(
        'auto_send_plugin_postroll_tracking_endpoint',
        'Post-Roll Tracking Endpoint',
        'auto_send_plugin_postroll_tracking_endpoint_field',
        'auto-send-plugin',
        'auto_send_plugin_settings_section'
    );
}
add_action( 'admin_init', 'auto_send_plugin_register_settings' );

// Function to display the settings section content
function auto_send_plugin_settings_section_content() {
    echo '<p>Enter your API key and endpoint URL.</p>';
}

// Function to display the API key field
function auto_send_plugin_api_key_field() {
    $api_key = get_option( 'auto_send_plugin_api_key' );
    echo '<input type="text" name="auto_send_plugin_api_key" value="' . esc_attr( $api_key ) . '" />';
}

// Function to display the endpoint URL field
function auto_send_plugin_endpoint_field() {
    $endpoint = get_option( 'auto_send_plugin_endpoint' );
    echo '<input type="text" name="auto_send_plugin_endpoint" value="' . esc_attr( $endpoint ) . '" />';
    echo '<p class="description">If your WordPress instance is running in a Docker container or a virtual machine, <code>localhost</code> may not be accessible. Use an IP address or hostname that is accessible from within the WordPress environment.</p>';
}

// Function to display the audio endpoint URL field
function auto_send_plugin_audio_endpoint_field() {
    $audio_endpoint = get_option( 'auto_send_plugin_audio_endpoint' );
    echo '<input type="text" name="auto_send_plugin_audio_endpoint" value="' . esc_attr( $audio_endpoint ) . '" />';
    echo '<p class="description">Enter the URL to fetch audio links from.</p>';
}

// Function to display the pre-roll tracking endpoint field
function auto_send_plugin_preroll_tracking_endpoint_field() {
    $preroll_endpoint = get_option( 'auto_send_plugin_preroll_tracking_endpoint' );
    echo '<input type="text" name="auto_send_plugin_preroll_tracking_endpoint" value="' . esc_attr( $preroll_endpoint ) . '" />';
    echo '<p class="description">Enter the URL to call when pre-roll ad starts playing.</p>';
}

// Function to display the post-roll tracking endpoint field
function auto_send_plugin_postroll_tracking_endpoint_field() {
    $postroll_endpoint = get_option( 'auto_send_plugin_postroll_tracking_endpoint' );
    echo '<input type="text" name="auto_send_plugin_postroll_tracking_endpoint" value="' . esc_attr( $postroll_endpoint ) . '" />';
    echo '<p class="description">Enter the URL to call when post-roll ad starts playing.</p>';
}

// Function to send post data to the endpoint
function auto_send_plugin_send_post_data( $post_id ) {
    $api_key = get_option( 'auto_send_plugin_api_key' );
    $endpoint = get_option( 'auto_send_plugin_endpoint' );

    // Validate configuration
    if ( empty( $api_key ) || empty( $endpoint ) ) {
        error_log( 'Auto Send Plugin: Missing API key or endpoint configuration' );
        return;
    }

    // Debug log the endpoint
    error_log('Attempting to send data to endpoint: ' . $endpoint);

    // Get post data
    $post = get_post( $post_id );
    
    if ( ! $post ) {
        error_log( 'Auto Send Plugin: Post not found with ID: ' . $post_id );
        return;
    }

    // Get tags
    $tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
    if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
      $tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
    } else {
      $tags = array();
    }

    // Get categories
    $categories = wp_get_post_categories( $post_id );
    $category_names = array();
    if ( ! empty( $categories ) ) {
        foreach( $categories as $category_id ) {
            $category = get_category( $category_id );
            if ( $category && ! is_wp_error( $category ) ) {
                $category_names[] = $category->name;
            }
        }
    }

    // Prepare the DTO according to NestJS validation
    $dto = array();

    // Required fields
    $dto['externalId'] = strval($post_id);
    $dto['authorName'] = get_the_author_meta( 'display_name', $post->post_author );
    $dto['title'] = get_the_title( $post_id );
    $dto['content'] = $post->post_content;
    $dto['excerpt'] = get_the_excerpt( $post_id );
    $dto['slug'] = $post->post_name;
    
    // Generate a proper URL - handle cases where permalink might not be ready
    $permalink = get_permalink( $post_id );
    if ( !$permalink || $permalink === false || !filter_var( $permalink, FILTER_VALIDATE_URL ) ) {
        // Fallback: construct URL manually
        $home_url = get_home_url();
        $dto['url'] = trailingslashit( $home_url ) . '?p=' . $post_id;
        error_log( 'Using fallback URL for post ' . $post_id . ': ' . $dto['url'] );
    } else {
        $dto['url'] = $permalink;
    }

    // Optional fields
    $dto['cmsPlatform'] = 'WORDPRESS';

    if (!empty($tags)) {
        $dto['tags'] = $tags;
    }
    if (!empty($category_names)) {
        $dto['categories'] = $category_names;
    }

    $image_url = get_the_post_thumbnail_url( $post_id, 'full' );
    if ($image_url && is_string($image_url)) {
        $dto['imageUrl'] = $image_url;
    }

    if (isset($post->post_status)) {
        $dto['isPublished'] = ($post->post_status == 'publish');
    }

    $metadata = array('source' => 'imported', 'views' => 0);
    $dto['metadata'] = $metadata;

    $published_at = get_the_date( 'c', $post_id );
    if ($published_at && is_string($published_at)) {
        $dto['publishedAt'] = $published_at;
    }

    // Convert DTO to JSON
    $body = wp_json_encode( $dto );
    
    // Debug: Log the DTO being sent (excluding sensitive data)
    $debug_dto = $dto;
    error_log( 'Auto Send Plugin: Sending DTO for post ' . $post_id . ': ' . wp_json_encode( $debug_dto ) );

    // Validate that we have a properly formed JSON
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        error_log( 'Auto Send Plugin: JSON encoding error: ' . json_last_error_msg() );
        return;
    }

    // Prepare the request arguments
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key
        ),
        'body' => $body,
        'data_format' => 'body',
        'method' => 'POST',
        'timeout' => 30,
    );

    // Send the request
    $response = wp_remote_post( $endpoint, $args );

    // Handle the response
    if ( is_wp_error( $response ) ) {
        error_log( 'Auto Send Plugin: Error sending post data to ' . $endpoint . ': ' . $response->get_error_message() );
    } else {
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        
        if ( $response_code >= 200 && $response_code < 300 ) {
            error_log( 'Auto Send Plugin: Successfully sent post data. Response code: ' . $response_code );
        } else {
            error_log( 'Auto Send Plugin: Failed to send post data. Response code: ' . $response_code . ', Body: ' . $response_body );
        }
    }
}

// Function to generate the audio player HTML
function auto_send_plugin_generate_audio_player( $post_id ) {
    $audio_endpoint = get_option( 'auto_send_plugin_audio_endpoint' );
    $preroll_tracking_endpoint = get_option( 'auto_send_plugin_preroll_tracking_endpoint' );
    $postroll_tracking_endpoint = get_option( 'auto_send_plugin_postroll_tracking_endpoint' );
    $api_key = get_option( 'auto_send_plugin_api_key' );

    if ( empty( $audio_endpoint ) ) {
        error_log( 'Error: Audio endpoint URL is not set.' );
        return '<p>Error: Audio endpoint URL is not set. Please configure it in the plugin settings.</p>';
    }

    if ( empty( $api_key ) ) {
        error_log( 'Error: API key is not set for audio endpoint.' );
        return '<p>Error: API key is not configured. Please set it in the plugin settings.</p>';
    }

    // Add API key authentication to the audio request
    $args = array(
        'headers' => array(
            'x-api-key' => $api_key
        ),
        'timeout' => 30
    );

    $response = wp_remote_get( $audio_endpoint, $args );

    if ( is_wp_error( $response ) ) {
        error_log( 'Error fetching audio data: ' . $response->get_error_message() );
        return '<p>Error fetching audio.</p>';
    }

    $body = wp_remote_retrieve_body( $response );
    $audio_info = json_decode( $body, true );

    // Debug: Log the actual response to understand the format
    error_log( 'Audio endpoint response: ' . $body );
    error_log( 'Decoded audio info: ' . print_r( $audio_info, true ) );

    // Handle different possible response formats
    $pre_roll_audio_link = null;
    $post_roll_audio_link = null;
    $article_audio_link = null;

    if ( isset( $audio_info['success'] ) && $audio_info['success'] === true && isset( $audio_info['data'] ) ) {
        // Format 1: { success: true, data: { preRollAudioLink: ..., ... } }
        $pre_roll_audio_link = $audio_info['data']['preRollAudioLink'];
        $post_roll_audio_link = $audio_info['data']['postRollAudioLink'];
        $article_audio_link = $audio_info['data']['articleAudioLink'];
    } elseif ( isset( $audio_info['preRollAudioLink'] ) || isset( $audio_info['postRollAudioLink'] ) || isset( $audio_info['articleAudioLink'] ) ) {
        // Format 2: Direct object { preRollAudioLink: ..., postRollAudioLink: ..., articleAudioLink: ... }
        $pre_roll_audio_link = isset( $audio_info['preRollAudioLink'] ) ? $audio_info['preRollAudioLink'] : null;
        $post_roll_audio_link = isset( $audio_info['postRollAudioLink'] ) ? $audio_info['postRollAudioLink'] : null;
        $article_audio_link = isset( $audio_info['articleAudioLink'] ) ? $audio_info['articleAudioLink'] : null;
    } elseif ( is_array( $audio_info ) && !empty( $audio_info ) ) {
        // Format 3: Array of audio objects or other structures
        error_log( 'Unrecognized audio response format, attempting to parse: ' . print_r( $audio_info, true ) );
    }

    // Check if we have at least one valid audio link
    if ( $pre_roll_audio_link || $post_roll_audio_link || $article_audio_link ) {
        $unique_id = 'audio-player-' . $post_id;

        $audio_player_html = '
        <div class="echoads-audio-player" id="' . $unique_id . '">
            <div class="audio-controls">
                <button class="play-pause-btn" id="' . $unique_id . '-play-pause">▶</button>
                <div class="progress-container">
                    <div class="progress-bar" id="' . $unique_id . '-progress">
                        <div class="progress-fill" id="' . $unique_id . '-fill"></div>
                    </div>
                    <div class="time-display">
                        <span id="' . $unique_id . '-current-time">0:00</span> / 
                        <span id="' . $unique_id . '-duration">0:00</span>
                    </div>
                </div>
                <div class="current-track" id="' . $unique_id . '-track">Audio Player</div>
            </div>
            <audio preload="metadata" id="' . $unique_id . '-audio">
                Your browser does not support the audio element.
            </audio>
        </div>
        <script>
        (function() {
            var audioData = {
                preRoll: "' . esc_js( $pre_roll_audio_link ) . '",
                article: "' . esc_js( $article_audio_link ) . '",
                postRoll: "' . esc_js( $post_roll_audio_link ) . '",
                prerollTrackingUrl: "' . esc_js( $preroll_tracking_endpoint ) . '",
                postrollTrackingUrl: "' . esc_js( $postroll_tracking_endpoint ) . '"
            };
            
            var playerId = "' . $unique_id . '";
            
            if (typeof window.EchoAdsAudioPlayers === "undefined") {
                window.EchoAdsAudioPlayers = {};
            }
            
            window.EchoAdsAudioPlayers[playerId] = audioData;
            
            // Initialize the player immediately after DOM elements are created
            setTimeout(function() {
                if (typeof window.EchoAdsAudioController !== "undefined") {
                    window.EchoAdsAudioController.init(playerId);
                } else {
                    console.error("EchoAdsAudioController not available");
                }
            }, 100);
        })();
        </script>';

        return $audio_player_html;
    } else {
        error_log( 'Error: No valid audio links found in response. Response was: ' . $body );
        return '<p>Error: No audio content available.</p>';
    }
}

// Function to add the audio player to the post content
function auto_send_plugin_add_audio_player( $content ) {
    global $post;

    if ( is_singular() && !is_feed() && !is_admin() ) {
        $audio_player = auto_send_plugin_generate_audio_player( $post->ID );
        $content .= $audio_player;
    }

    return $content;
}
add_filter( 'the_content', 'auto_send_plugin_add_audio_player' );

// Hook to send post data when posts are published or updated
add_action( 'publish_post', 'auto_send_plugin_send_post_data' );
add_action( 'post_updated', 'auto_send_plugin_send_post_data' );

function auto_send_plugin_enqueue_scripts() {
    wp_enqueue_script( 'jquery' );
    
    wp_add_inline_script( 'jquery', '
    window.EchoAdsAudioController = {
        init: function(playerId) {
            var audioData = window.EchoAdsAudioPlayers[playerId];
            if (!audioData) return;
            
            var audio = document.getElementById(playerId + "-audio");
            var playPauseBtn = document.getElementById(playerId + "-play-pause");
            var progressBar = document.getElementById(playerId + "-progress");
            var progressFill = document.getElementById(playerId + "-fill");
            var currentTimeSpan = document.getElementById(playerId + "-current-time");
            var durationSpan = document.getElementById(playerId + "-duration");
            var trackDisplay = document.getElementById(playerId + "-track");
            
            if (!audio || !playPauseBtn || !progressBar) {
                console.error("Audio player elements not found for", playerId);
                return;
            }
            
            var currentTrack = 0; // 0: preroll, 1: article, 2: postroll
            var tracks = [
                { url: audioData.preRoll, name: "Pre-Roll Ad", trackingUrl: audioData.prerollTrackingUrl },
                { url: audioData.article, name: "Article Audio", trackingUrl: null },
                { url: audioData.postRoll, name: "Post-Roll Ad", trackingUrl: audioData.postrollTrackingUrl }
            ];
            
            function loadTrack(index) {
                if (index >= tracks.length) return;
                
                currentTrack = index;
                audio.src = tracks[index].url;
                trackDisplay.textContent = tracks[index].name;
                audio.load();
            }
            
            function callTrackingEndpoint(url) {
                if (!url || typeof jQuery === "undefined") return;
                
                jQuery.ajax({
                    url: url,
                    type: "POST",
                    success: function(response) {
                        console.log("Tracking call successful:", response);
                    },
                    error: function(xhr, status, error) {
                        console.error("Tracking call failed:", error);
                    }
                });
            }
            
            function formatTime(seconds) {
                var minutes = Math.floor(seconds / 60);
                var secs = Math.floor(seconds % 60);
                return minutes + ":" + (secs < 10 ? "0" : "") + secs;
            }
            
            audio.addEventListener("loadedmetadata", function() {
                durationSpan.textContent = formatTime(audio.duration);
            });
            
            audio.addEventListener("timeupdate", function() {
                var progress = (audio.currentTime / audio.duration) * 100;
                progressFill.style.width = progress + "%";
                currentTimeSpan.textContent = formatTime(audio.currentTime);
            });
            
            audio.addEventListener("ended", function() {
                if (currentTrack < tracks.length - 1) {
                    loadTrack(currentTrack + 1);
                    audio.play();
                } else {
                    playPauseBtn.textContent = "▶";
                    progressFill.style.width = "0%";
                    currentTimeSpan.textContent = "0:00";
                }
            });
            
            audio.addEventListener("play", function() {
                var track = tracks[currentTrack];
                if (track.trackingUrl) {
                    callTrackingEndpoint(track.trackingUrl);
                }
            });
            
            playPauseBtn.addEventListener("click", function() {
                if (audio.paused) {
                    audio.play();
                    playPauseBtn.textContent = "⏸";
                } else {
                    audio.pause();
                    playPauseBtn.textContent = "▶";
                }
            });
            
            progressBar.addEventListener("click", function(e) {
                var rect = progressBar.getBoundingClientRect();
                var clickX = e.clientX - rect.left;
                var width = rect.width;
                var clickPercent = clickX / width;
                audio.currentTime = clickPercent * audio.duration;
            });
            
            loadTrack(0);
        }
    };
    ' );
    
    wp_add_inline_style( 'wp-block-library', '
    .echoads-audio-player {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    
    .audio-controls {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .play-pause-btn {
        background: #007cba;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        color: white;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s;
    }
    
    .play-pause-btn:hover {
        background: #005a87;
    }
    
    .progress-container {
        flex: 1;
        min-width: 200px;
    }
    
    .progress-bar {
        background: #e9ecef;
        height: 6px;
        border-radius: 3px;
        cursor: pointer;
        position: relative;
        margin-bottom: 8px;
    }
    
    .progress-fill {
        background: #007cba;
        height: 100%;
        border-radius: 3px;
        width: 0%;
        transition: width 0.1s;
    }
    
    .time-display {
        font-size: 14px;
        color: #6c757d;
        font-weight: 500;
    }
    
    .current-track {
        font-weight: 600;
        color: #495057;
        font-size: 14px;
        padding: 8px 12px;
        background: #e9ecef;
        border-radius: 4px;
        white-space: nowrap;
    }
    
    @media (max-width: 600px) {
        .audio-controls {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        
        .current-track {
            text-align: center;
            order: -1;
        }
    }
    ' );
}
add_action( 'wp_enqueue_scripts', 'auto_send_plugin_enqueue_scripts' );
?>