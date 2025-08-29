<?php
/*
Plugin Name: Auto Send Plugin
Description: Sends post data to a specified endpoint.
Version: 1.0.0
Author: Your Name
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

// Function to send post data to the endpoint
function auto_send_plugin_send_post_data( $post_id ) {
    $api_key = get_option( 'auto_send_plugin_api_key' );
    $endpoint = get_option( 'auto_send_plugin_endpoint' );

    // Debug log the endpoint
    error_log('Attempting to send data to endpoint: ' . $endpoint);

    // Get post data
    $post = get_post( $post_id );

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
    $dto['url'] = get_permalink( $post_id );

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

    // Prepare the request arguments
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key
        ),
        'body' => $body,
        'data_format' => 'body',
        'method' => 'POST',
    );

    // Send the request
    $response = wp_remote_post( $endpoint, $args );

    // Handle the response
    if ( is_wp_error( $response ) ) {
        error_log( 'Error sending post data to ' . $endpoint . ': ' . $response->get_error_message() );
    } else {
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        error_log( 'Post data sent. Response code: ' . $response_code . ', Body: ' . $response_body );
    }
}

// Hook into the save_post action
function auto_send_plugin_save_post( $post_id ) {
    // Check if this is an autosave.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // Check if user has proper permissions to save post.
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Log that the save_post action is being triggered
    error_log( 'save_post action triggered for post ID: ' . $post_id );

    // Send the post data
    auto_send_plugin_send_post_data( $post_id );
}
add_action( 'save_post', 'auto_send_plugin_save_post' );

function auto_send_plugin_enqueue_scripts() {
    wp_enqueue_script( 'jquery' );
}
add_action( 'admin_enqueue_scripts', 'auto_send_plugin_enqueue_scripts' );
?>