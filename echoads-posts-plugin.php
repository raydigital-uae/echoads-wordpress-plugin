<?php
/*
Plugin Name: EchoAds Audio Player
Description: Sends post data to a specified endpoint, and adds an audio player to the post content, with pre-roll & post-roll ads.
x-release-please-start-version
Version: 1.6.0
x-release-please-end
Author: Hussein Shaltout 
*/

if (!defined('ABSPATH')) {
    exit;
}

define('ECHOADS_PLUGIN_VERSION', '1.6.0'); // x-release-please-version

require_once plugin_dir_path(__FILE__) . 'includes/class-plugin.php';

function echoads_plugin_init()
{
    EchoAds_Plugin::get_instance();
}
add_action('init', 'echoads_plugin_init');
?>