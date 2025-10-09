<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EchoAds_Post_Sender {

    public function __construct() {
        add_action( 'wp_ajax_echoads_generate_audio', array( $this, 'handle_ajax_generate_audio' ) );
    }

    public function send_post_data( $post_id ) {
        $api_key = EchoAds_Settings::get_api_key();
        $endpoint = EchoAds_Settings::get_endpoint();

        if ( empty( $api_key ) || empty( $endpoint ) ) {
            error_log( 'EchoAds Plugin: Missing API key or endpoint configuration' );
            return;
        }

        error_log( 'Attempting to send data to endpoint: ' . $endpoint );

        $post = get_post( $post_id );
        
        if ( ! $post ) {
            error_log( 'EchoAds Plugin: Post not found with ID: ' . $post_id );
            return;
        }

        $dto = $this->prepare_post_dto( $post_id, $post );
        
        if ( ! $dto ) {
            error_log( 'EchoAds Plugin: Failed to prepare DTO for post: ' . $post_id );
            return;
        }

        $this->send_request( $endpoint, $api_key, $dto, $post_id );
    }

    private function prepare_post_dto( $post_id, $post ) {
        $tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
        if ( is_wp_error( $tags ) ) {
            $tags = array();
        }

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

        $dto = array();

        $dto['externalId'] = strval( $post_id );
        $dto['authorName'] = get_the_author_meta( 'display_name', $post->post_author );
        $dto['title'] = get_the_title( $post_id );
        $dto['content'] = $post->post_content;
        $dto['excerpt'] = get_the_excerpt( $post_id );
        $dto['slug'] = $post->post_name;
        $dto['url'] = $this->get_post_url( $post_id );

        $dto['cmsPlatform'] = 'WORDPRESS';

        if ( ! empty( $tags ) ) {
            $dto['tags'] = $tags;
        }

        if ( ! empty( $category_names ) ) {
            $dto['categories'] = $category_names;
        }

        $image_url = get_the_post_thumbnail_url( $post_id, 'full' );
        if ( $image_url && is_string( $image_url ) ) {
            $dto['imageUrl'] = $image_url;
        }

        if ( isset( $post->post_status ) ) {
            $dto['isPublished'] = ( $post->post_status == 'publish' );
        }

        $dto['metadata'] = array( 'source' => 'imported', 'views' => 0 );

        $published_at = get_the_date( 'c', $post_id );
        if ( $published_at && is_string( $published_at ) ) {
            $dto['publishedAt'] = $published_at;
        }

        return $dto;
    }

    private function get_post_url( $post_id ) {
        $permalink = get_permalink( $post_id );
        
        if ( ! $permalink || $permalink === false || ! filter_var( $permalink, FILTER_VALIDATE_URL ) ) {
            $home_url = get_home_url();
            $fallback_url = trailingslashit( $home_url ) . '?p=' . $post_id;
            error_log( 'Using fallback URL for post ' . $post_id . ': ' . $fallback_url );
            return $fallback_url;
        }

        return $permalink;
    }

    private function send_request( $endpoint, $api_key, $dto, $post_id ) {
        $body = wp_json_encode( $dto );
        
        error_log( 'EchoAds Plugin: Sending DTO for post ' . $post_id . ': ' . $body );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            error_log( 'EchoAds Plugin: JSON encoding error: ' . json_last_error_msg() );
            return;
        }

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

        $response = wp_remote_post( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            error_log( 'EchoAds Plugin: Error sending post data to ' . $endpoint . ': ' . $response->get_error_message() );
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            
            if ( $response_code >= 200 && $response_code < 300 ) {
                error_log( 'EchoAds Plugin: Successfully sent post data. Response code: ' . $response_code );
            } else {
                error_log( 'EchoAds Plugin: Failed to send post data. Response code: ' . $response_code . ', Body: ' . $response_body );
            }
        }
    }

    public function handle_ajax_generate_audio() {
        check_ajax_referer( 'echoads_generate_audio', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
            return;
        }

        $post_id = intval( $_POST['post_id'] );

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => 'Invalid post ID' ) );
            return;
        }

        // Check if this is a regeneration request
        $is_regenerate = isset( $_POST['regenerate'] ) && $_POST['regenerate'] === 'true';

        if ( ! $is_regenerate && get_post_meta( $post_id, '_echoads_audio_generated', true ) ) {
            wp_send_json_error( array( 'message' => 'Audio already generated for this post' ) );
            return;
        }

        $api_key = EchoAds_Settings::get_api_key();
        $endpoint = EchoAds_Settings::get_endpoint();

        if ( empty( $api_key ) || empty( $endpoint ) ) {
            wp_send_json_error( array( 'message' => 'Missing API key or endpoint configuration' ) );
            return;
        }

        $post = get_post( $post_id );

        if ( ! $post ) {
            wp_send_json_error( array( 'message' => 'Post not found' ) );
            return;
        }

        $dto = $this->prepare_post_dto( $post_id, $post );

        if ( ! $dto ) {
            wp_send_json_error( array( 'message' => 'Failed to prepare post data' ) );
            return;
        }

        $success = $this->send_request_sync( $endpoint, $api_key, $dto, $post_id );

        if ( $success ) {
            update_post_meta( $post_id, '_echoads_audio_generated', current_time( 'mysql' ) );
            wp_send_json_success( array( 'message' => 'Audio generation initiated successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to send post data to endpoint' ) );
        }
    }

    private function send_request_sync( $endpoint, $api_key, $dto, $post_id ) {
        $body = wp_json_encode( $dto );

        error_log( 'EchoAds Plugin: Sending DTO for post ' . $post_id . ': ' . $body );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            error_log( 'EchoAds Plugin: JSON encoding error: ' . json_last_error_msg() );
            return false;
        }

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

        $response = wp_remote_post( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            error_log( 'EchoAds Plugin: Error sending post data to ' . $endpoint . ': ' . $response->get_error_message() );
            return false;
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );

            if ( $response_code >= 200 && $response_code < 300 ) {
                error_log( 'EchoAds Plugin: Successfully sent post data. Response code: ' . $response_code );
                return true;
            } else {
                error_log( 'EchoAds Plugin: Failed to send post data. Response code: ' . $response_code . ', Body: ' . $response_body );
                return false;
            }
        }
    }
}