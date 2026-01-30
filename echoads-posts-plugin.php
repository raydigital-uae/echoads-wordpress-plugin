<?php
/*
Plugin Name: EchoAds Audio Player
Description: Sends post data to a specified endpoint, and adds an audio player to the post content, with pre-roll & post-roll ads.
Version: 1.0.1
Author: Hussein Shaltout 
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-plugin.php';

function echoads_plugin_init()
{
    EchoAds_Plugin::get_instance();
}
add_action('init', 'echoads_plugin_init');
?>