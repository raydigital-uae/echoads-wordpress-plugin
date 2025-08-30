<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EchoAds_Plugin {

    private static $instance = null;
    private $settings;
    private $post_sender;
    private $audio_player;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->setup_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-settings.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-post-sender.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-audio-player.php';
    }

    private function init_components() {
        $this->settings = new EchoAds_Settings();
        $this->post_sender = new EchoAds_Post_Sender();
        $this->audio_player = new EchoAds_Audio_Player();
    }

    private function setup_hooks() {
        register_activation_hook( dirname( dirname( __FILE__ ) ) . '/echoads-posts-plugin.php', array( $this, 'activate' ) );
        register_deactivation_hook( dirname( dirname( __FILE__ ) ) . '/echoads-posts-plugin.php', array( $this, 'deactivate' ) );
    }

    public function activate() {
        error_log( 'EchoAds Plugin: Activated' );
    }

    public function deactivate() {
        error_log( 'EchoAds Plugin: Deactivated' );
    }

    public function get_settings() {
        return $this->settings;
    }

    public function get_post_sender() {
        return $this->post_sender;
    }

    public function get_audio_player() {
        return $this->audio_player;
    }
}