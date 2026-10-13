<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EchoAds_Plugin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
    }

    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-settings.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-post-sender.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-audio-player.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-meta-box.php';
    }

    private function init_components() {
        new EchoAds_Settings();
        new EchoAds_Post_Sender();
        new EchoAds_Audio_Player();
        new EchoAds_Meta_Box();
    }
}
