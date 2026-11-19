<?php
/**
 * Plugin Name:       EchoAds Audio Player
 * Description:       Turns your posts into audio articles with an on-page player and pre-roll and post-roll audio ads, powered by the EchoAds service.
x-release-please-start-version
 * Version:           1.8.0
x-release-please-end
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Hussein Shaltout
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       echoads-audio-player
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ECHOADS_PLUGIN_VERSION', '1.8.0' ); // x-release-please-version
define( 'ECHOADS_PLUGIN_FILE', __FILE__ );

/**
 * Writes a message to the PHP error log, only when WP_DEBUG is on.
 *
 * @param string $message Message to log.
 */
function echoads_log( $message ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only runs with WP_DEBUG on.
        error_log( 'EchoAds: ' . $message );
    }
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php';

function echoads_plugin_init() {
    EchoAds_Plugin::get_instance();
}
add_action( 'init', 'echoads_plugin_init' );
