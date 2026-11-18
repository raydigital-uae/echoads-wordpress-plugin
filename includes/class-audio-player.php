<?php

if (!defined('ABSPATH')) {
    exit;
}

class EchoAds_Audio_Player
{

    // Players already registered on this page. the_content can run more than once per request
    // (SEO plugins build descriptions from it), and each player must be initialized only once.
    private $registered_players = array();

    public function __construct()
    {
        // The plugin boots on init, so register right away.
        $this->register_assets();
        add_filter('the_content', array($this, 'add_audio_player'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function add_audio_player($content)
    {
        global $post;

        // The ID check skips other posts' content run through this filter on the same page (e.g. related-post excerpts).
        if (is_singular() && !is_feed() && !is_admin() && $post && (int) $post->ID === get_queried_object_id()) {
            // Render for any post sent to EchoAds, ready or not: the browser resolves the audio and stays
            // hidden until it exists, so a page cached before generation finished still gets the player.
            $sent_to_echoads = get_post_meta($post->ID, '_echoads_audio_generated', true)
                || get_post_meta($post->ID, '_echoads_audio_requested', true);

            if ($sent_to_echoads) {
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
            echoads_log('Error: Audio endpoint URL is not set.');
            return '';
        }

        if (empty($api_key)) {
            echoads_log('Error: API key is not set for audio endpoint.');
            return '';
        }

        return $this->render_audio_player($post_id);
    }

    public static function fetch_audio_data($audio_endpoint, $api_key, $post_id)
    {
        // Add externalId query parameter to the endpoint URL
        $audio_endpoint = add_query_arg('externalId', strval($post_id), $audio_endpoint);

        $args = array(
            'headers' => array(
                'x-api-key' => $api_key
            ),
            'timeout' => 3
        );

        $response = wp_remote_get($audio_endpoint, $args);

        if (is_wp_error($response)) {
            echoads_log('Error fetching audio data: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $audio_info = json_decode($body, true);

        return self::parse_audio_response($audio_info, $body);
    }

    private static function parse_audio_response($audio_info, $raw_body)
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
            echoads_log('Unrecognized audio response format, attempting to parse: ' . wp_json_encode($audio_info));
            return false;
        } else {
            echoads_log('Error: No valid audio links found in response. Response was: ' . $raw_body);
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

    private function render_audio_player($post_id)
    {
        $unique_id = 'audio-player-' . $post_id;
        $tracking_endpoint = EchoAds_Settings::get_tracking_endpoint();
        $api_key = EchoAds_Settings::get_api_key();
        $bg_color = EchoAds_Settings::get_player_bg_color();
        $endpoint = EchoAds_Settings::get_endpoint();

        // Endpoint already includes the base path, e.g. .../website-articles
        $config_endpoint = !empty($endpoint) ? rtrim($endpoint, '/') . '/config' : '';
        // The browser fetches the audio links on every load: ads are matched per visitor and must never be cached.
        $audio_urls_endpoint = add_query_arg('externalId', strval($post_id), EchoAds_Settings::get_audio_endpoint());

        $audio_data = array(
            'preRoll'             => '',
            'article'             => '',
            'postRoll'            => '',
            'prerollTrackingUrl'  => $tracking_endpoint,
            'postrollTrackingUrl' => $tracking_endpoint,
            'apiKey'              => $api_key,
            'configEndpoint'      => $config_endpoint,
            'audioUrlsEndpoint'   => $audio_urls_endpoint,
            'preRollAudioId'      => null,
            'postRollAudioId'     => null,
            'articleAudioId'      => null,
            'articleExternalId'   => strval($post_id),
            'pluginVersion'       => ECHOADS_PLUGIN_VERSION,
        );

        if (isset($this->registered_players[$unique_id])) {
            return $this->get_player_markup($unique_id, $bg_color);
        }
        $this->registered_players[$unique_id] = true;

        // Registers the player before the player script (printed in the footer) runs; it reads
        // window.EchoAdsAudioPlayers and initializes every ID in EchoAdsAudioPlayersPendingInit.
        wp_add_inline_script(
            'echoads-audio-player',
            sprintf(
                'window.EchoAdsAudioPlayers = window.EchoAdsAudioPlayers || {};'
                . 'window.EchoAdsAudioPlayers[%1$s] = %2$s;'
                . 'window.EchoAdsAudioPlayersPendingInit = window.EchoAdsAudioPlayersPendingInit || [];'
                . 'window.EchoAdsAudioPlayersPendingInit.push(%1$s);',
                wp_json_encode($unique_id),
                wp_json_encode($audio_data)
            ),
            'before'
        );

        return $this->get_player_markup($unique_id, $bg_color);
    }

    private function get_player_markup($unique_id, $bg_color)
    {
        // React renders the player UI into this element.
        return sprintf(
            '<div id="%s" data-bg-color="%s"></div>',
            esc_attr($unique_id . '-wrapper'),
            esc_attr($bg_color)
        );
    }

    /**
     * Registered on init: block themes render post content (and so add_audio_player) before
     * wp_enqueue_scripts fires, and wp_add_inline_script needs the handle to exist by then.
     */
    public function register_assets()
    {
        wp_register_style(
            'echoads-audio-player',
            plugins_url('assets/dist/echoads-audio-player.css', ECHOADS_PLUGIN_FILE),
            array(),
            ECHOADS_PLUGIN_VERSION
        );

        wp_register_script(
            'echoads-audio-player',
            plugins_url('assets/dist/echoads-audio-player.js', ECHOADS_PLUGIN_FILE),
            array(),
            ECHOADS_PLUGIN_VERSION,
            true
        );
    }

    public function enqueue_assets()
    {
        wp_enqueue_style('echoads-audio-player');
        wp_enqueue_script('echoads-audio-player');
    }
}