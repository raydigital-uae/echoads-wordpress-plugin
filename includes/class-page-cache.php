<?php
/**
 * Clears a post's cached page when its EchoAds audio status changes.
 *
 * Whether the player is on a post's page depends on that status (the player is added once the
 * post is sent to EchoAds, and dropped when EchoAds has no audio or no website for its language).
 * A page cache can keep serving a copy saved before the change, so visitors don't see the
 * player until the cache expires or someone purges it by hand. This asks the page cache plugins
 * we know about to drop that one post's page whenever its status changes, and the whole site's
 * cache when a setting written into every player changes (position, colour, API key, URL).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EchoAds_Page_Cache {

    /**
     * Posts to purge at the end of this request, keyed by ID. A single request can change a
     * status more than once (PENDING, then FAILED); each post is purged only once.
     */
    private static $queued = array();

    /**
     * Whether the whole site's page cache is cleared at the end of this request.
     */
    private static $purge_all_queued = false;

    /**
     * Remembers whether the site sits behind the server's Varnish (see server_varnish_active).
     */
    const VARNISH_TRANSIENT = 'echoads_server_varnish';

    public function __construct() {
        // update_metadata() skips these hooks when the value is unchanged, so routine status
        // polls that come back with the same status purge nothing.
        add_action( 'added_post_meta', array( $this, 'handle_meta_change' ), 10, 3 );
        add_action( 'updated_post_meta', array( $this, 'handle_meta_change' ), 10, 3 );
        add_action( 'deleted_post_meta', array( $this, 'handle_meta_change' ), 10, 3 );

        // These settings are written into the player on every post page, so a cached page keeps
        // the old value. update_option_* fires only when the value actually changes.
        $player_settings = array(
            EchoAds_Settings::OPTION_PLAYER_POSITION,
            EchoAds_Settings::OPTION_PLAYER_BG_COLOR,
            EchoAds_Settings::OPTION_API_KEY,
            EchoAds_Settings::OPTION_BASE_URL,
        );
        foreach ( $player_settings as $option ) {
            add_action( 'update_option_' . $option, array( __CLASS__, 'queue_purge_all' ), 10, 0 );
            add_action( 'add_option_' . $option, array( __CLASS__, 'queue_purge_all' ), 10, 0 );
        }
    }

    public function handle_meta_change( $meta_ids, $post_id, $meta_key ) {
        if ( '_echoads_audio_status' !== $meta_key ) {
            return;
        }
        self::queue( (int) $post_id );
    }

    public static function queue( $post_id ) {
        if ( $post_id <= 0 || isset( self::$queued[ $post_id ] ) ) {
            return;
        }
        self::add_shutdown_purge();
        self::$queued[ $post_id ] = true;
    }

    /**
     * Clears the whole site's page cache at the end of this request, once, however many
     * settings changed (Settings > EchoAds saves them all in one request).
     */
    public static function queue_purge_all() {
        self::add_shutdown_purge();
        self::$purge_all_queued = true;
    }

    private static function add_shutdown_purge() {
        if ( ! has_action( 'shutdown', array( __CLASS__, 'purge_queued' ) ) ) {
            add_action( 'shutdown', array( __CLASS__, 'purge_queued' ) );
        }
    }

    public static function purge_queued() {
        $post_ids     = array_keys( self::$queued );
        self::$queued = array();

        if ( self::$purge_all_queued ) {
            // The site-wide purge covers every post page as well.
            self::$purge_all_queued = false;
            self::purge_all();
            return;
        }

        foreach ( $post_ids as $post_id ) {
            self::purge_post( $post_id );
        }
    }

    public static function purge_post( $post_id ) {
        // Only published posts have a public page to cache.
        if ( 'publish' !== get_post_status( $post_id ) ) {
            return;
        }

        /**
         * Filters whether to clear the page cache for a post whose EchoAds audio status changed.
         *
         * @param bool $purge   Whether to purge. Default true.
         * @param int  $post_id Post ID.
         */
        if ( ! apply_filters( 'echoads_purge_page_cache', true, $post_id ) ) {
            return;
        }

        $url = get_permalink( $post_id );

        clean_post_cache( $post_id );

        // A failing cache plugin must never break saving the audio status.
        try {
            self::purge_cache_plugins( $post_id, $url );
        } catch ( Throwable $e ) {
            echoads_log( 'Page cache purge failed for post ' . $post_id . ': ' . $e->getMessage() );
        }

        /**
         * Fires after EchoAds asked the known page cache plugins to clear a post's page.
         * Hook in here to purge a cache EchoAds doesn't know about (a CDN, a host's proxy).
         *
         * @param int          $post_id Post ID.
         * @param string|false $url     The post's permalink.
         */
        do_action( 'echoads_purge_post_cache', $post_id, $url );

        echoads_log( 'Cleared page cache for post ' . $post_id );
    }

    public static function purge_all() {
        /**
         * Filters whether to clear the whole site's page cache after an EchoAds player setting
         * (position, colour, API key or URL) changed.
         *
         * @param bool $purge Whether to purge. Default true.
         */
        if ( ! apply_filters( 'echoads_purge_all_page_cache', true ) ) {
            return;
        }

        // A failing cache plugin must never break saving the settings.
        try {
            self::purge_all_cache_plugins();
        } catch ( Throwable $e ) {
            echoads_log( 'Site page cache purge failed: ' . $e->getMessage() );
        }

        /**
         * Fires after EchoAds asked the known page cache plugins to clear the whole site.
         * Hook in here to purge a cache EchoAds doesn't know about (a CDN, a host's proxy).
         */
        do_action( 'echoads_purge_all_cache' );

        echoads_log( 'Cleared the site page cache after a player setting changed' );
    }

    /**
     * Calls each page cache plugin's own "clear everything". Each call does nothing when that
     * plugin isn't active.
     */
    private static function purge_all_cache_plugins() {
        // Breeze (Cloudways): its own page cache, Varnish and Cloudflare. Without Breeze,
        // purge the server's Varnish ourselves.
        if ( class_exists( 'Breeze_PurgeCache' ) ) {
            do_action( 'breeze_clear_all_cache' );
        } else {
            self::purge_server_varnish( home_url( '/' ), true );
        }

        // LiteSpeed Cache.
        do_action( 'litespeed_purge_all' );

        // WP Rocket.
        if ( function_exists( 'rocket_clean_domain' ) ) {
            rocket_clean_domain();
        }

        // W3 Total Cache.
        if ( function_exists( 'w3tc_flush_all' ) ) {
            w3tc_flush_all();
        }

        // WP Super Cache.
        if ( function_exists( 'wp_cache_clear_cache' ) ) {
            wp_cache_clear_cache();
        }

        // WP Fastest Cache.
        do_action( 'wpfc_clear_all_cache' );

        // Cache Enabler.
        do_action( 'cache_enabler_clear_complete_cache' );

        // Hummingbird: no post ID clears the whole page cache.
        do_action( 'wphb_clear_page_cache' );

        // SiteGround Optimizer.
        if ( function_exists( 'sg_cachepress_purge_everything' ) ) {
            sg_cachepress_purge_everything();
        }

        // Nginx Helper (FastCGI or Redis page cache).
        global $nginx_purger;
        if ( is_object( $nginx_purger ) && method_exists( $nginx_purger, 'purge_all' ) ) {
            $nginx_purger->purge_all();
        }

        // Proxy Cache Purge (Varnish): its regex purge of the whole site.
        global $purger;
        if ( class_exists( 'VarnishPurger' ) && $purger instanceof VarnishPurger ) {
            $purger->purge_url( untrailingslashit( home_url() ) . '/?vhp-regex' );
        }

        // WP Engine: no post ID clears the whole site.
        if ( class_exists( 'WpeCommon' ) && method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
            WpeCommon::purge_varnish_cache();
        }
    }

    /**
     * Calls each page cache plugin's own per-post purge. Each call does nothing when that
     * plugin isn't active.
     */
    private static function purge_cache_plugins( $post_id, $url ) {
        // Breeze (Cloudways): its own page cache and Cloudflare (purge_post_cache, which exists
        // only while Breeze's page cache is on), then the server's Varnish.
        if ( class_exists( 'Breeze_PurgeCache' ) ) {
            if ( has_action( 'purge_post_cache' ) ) {
                do_action( 'purge_post_cache', $post_id );
            }
            // purge_post_cache only queues the Varnish purge on Action Scheduler, and a site
            // whose queue doesn't run keeps serving the old page from Varnish. Breeze sends the
            // purge right away when it runs inside its own breeze_purge_varnish_urls action,
            // so call it through that action. The "?breeze" suffix is Breeze's per-page purge.
            if ( $url ) {
                do_action( 'breeze_purge_varnish_urls', array( untrailingslashit( $url ) . '/?breeze' ) );
            }
        } elseif ( $url ) {
            // No Breeze: nothing else purges the server's Varnish (Cloudways sites running
            // another page cache plugin, or none).
            self::purge_server_varnish( $url, false );
        }

        // LiteSpeed Cache.
        do_action( 'litespeed_purge_post', $post_id );

        // WP Rocket.
        if ( function_exists( 'rocket_clean_post' ) ) {
            rocket_clean_post( $post_id );
        }

        // W3 Total Cache.
        if ( function_exists( 'w3tc_flush_post' ) ) {
            w3tc_flush_post( $post_id );
        }

        // WP Super Cache.
        if ( function_exists( 'wp_cache_post_change' ) ) {
            wp_cache_post_change( $post_id );
        }

        // WP Fastest Cache.
        do_action( 'wpfc_clear_post_cache_by_id', false, $post_id );

        // Cache Enabler.
        do_action( 'cache_enabler_clear_page_cache_by_post', $post_id );

        // Hummingbird.
        do_action( 'wphb_clear_page_cache', $post_id );

        // SiteGround Optimizer.
        if ( $url && function_exists( 'sg_cachepress_purge_cache' ) ) {
            sg_cachepress_purge_cache( $url );
        }

        // Nginx Helper (FastCGI or Redis page cache).
        global $nginx_purger;
        if ( is_object( $nginx_purger ) && method_exists( $nginx_purger, 'purge_post' ) ) {
            $nginx_purger->purge_post( $post_id );
        }

        // Proxy Cache Purge (Varnish).
        global $purger;
        if ( class_exists( 'VarnishPurger' ) && $purger instanceof VarnishPurger ) {
            $purger->purge_post( $post_id );
        }

        // WP Engine.
        if ( class_exists( 'WpeCommon' ) && method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
            WpeCommon::purge_varnish_cache( $post_id );
        }
    }

    /**
     * Purges the server's Varnish (Cloudways) the way Breeze does: a request to the Varnish
     * on this server, with the site's host name. URLPURGE drops one page; PURGE drops the
     * whole site (Cloudways' Varnish purges everything for the host on PURGE).
     *
     * @param string $url The page to purge, or any URL of the site when $all is true.
     * @param bool   $all Whether to purge every page of the site.
     */
    private static function purge_server_varnish( $url, $all ) {
        if ( ! self::server_varnish_active() ) {
            return;
        }

        $parts = wp_parse_url( $url );
        if ( empty( $parts['host'] ) ) {
            return;
        }

        /**
         * Filters the address of the server's Varnish that EchoAds sends purges to.
         *
         * @param string $host Default '127.0.0.1', where Cloudways runs it.
         */
        $varnish_host = apply_filters( 'echoads_varnish_host', '127.0.0.1' );
        $scheme       = isset( $parts['scheme'] ) ? $parts['scheme'] : 'http';
        $path         = $all ? '/.*' : ( isset( $parts['path'] ) ? $parts['path'] : '/' );

        $response = wp_remote_request(
            $scheme . '://' . $varnish_host . $path,
            array(
                'method'    => $all ? 'PURGE' : 'URLPURGE',
                'headers'   => array( 'Host' => $parts['host'] ),
                'sslverify' => false,
                'blocking'  => false,
                'timeout'   => 1,
            )
        );
        if ( is_wp_error( $response ) ) {
            echoads_log( 'Varnish purge failed for ' . $url . ': ' . $response->get_error_message() );
        }
    }

    /**
     * Whether pages of this site are cached by a Varnish on the server. A request that came
     * through Varnish carries its X-Varnish header (Cloudways sets X-Application to
     * "varnishpass" when Varnish is turned off for the site). Background requests (WP-Cron,
     * WP-CLI) may not come through Varnish, so otherwise ask the home page and remember the
     * answer for 12 hours.
     */
    private static function server_varnish_active() {
        if ( isset( $_SERVER['HTTP_X_VARNISH'] ) ) {
            $application = isset( $_SERVER['HTTP_X_APPLICATION'] ) ? trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_APPLICATION'] ) ) ) : '';
            return ! in_array( $application, array( 'varnishpass', 'bypass' ), true );
        }

        $known = get_transient( self::VARNISH_TRANSIENT );
        if ( false !== $known ) {
            return '1' === $known;
        }

        $response = wp_remote_head( home_url( '/' ), array( 'timeout' => 5 ) );
        $active   = ! is_wp_error( $response ) && (
            0 === stripos( (string) wp_remote_retrieve_header( $response, 'cache-provider' ), 'CLOUDWAYS' )
            || '' !== (string) wp_remote_retrieve_header( $response, 'x-varnish' )
        );
        set_transient( self::VARNISH_TRANSIENT, $active ? '1' : '0', 12 * HOUR_IN_SECONDS );

        return $active;
    }
}
