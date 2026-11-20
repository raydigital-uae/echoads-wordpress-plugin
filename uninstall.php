<?php
/**
 * Removes the plugin's settings, post meta and scheduled events when the plugin is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$echoads_options = array(
    'echoads_api_key',
    'echoads_base_url',
    'echoads_timeout',
    'echoads_player_bg_color',
    'echoads_player_position',
    'echoads_options_migrated',
    // Option names used before 1.7.
    'auto_send_plugin_api_key',
    'auto_send_plugin_base_url',
    'auto_send_plugin_timeout',
    'auto_send_plugin_player_bg_color',
    'auto_send_plugin_player_position',
);

foreach ( $echoads_options as $echoads_option ) {
    delete_option( $echoads_option );
}

foreach ( array( '_echoads_audio_generated', '_echoads_audio_requested', '_echoads_audio_status', '_echoads_audio_scheduled' ) as $echoads_meta_key ) {
    delete_post_meta_by_key( $echoads_meta_key );
}

delete_transient( 'echoads_server_varnish' );

wp_unschedule_hook( 'echoads_poll_audio_status' );
wp_unschedule_hook( 'echoads_send_post_data' );
