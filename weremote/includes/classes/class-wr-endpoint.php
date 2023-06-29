<?php
/**
 * Class WR_Endpoint
 *
 * Description: This class handles the WR plugin endpoints.
 * 
 * @package We_Remote
 */

if ( ! class_exists( 'WR_Endpoint' ) ) {
    class WR_Endpoint {

        /**
         * Class constructor.
         */
        public function __construct() {
            $this->define_actions();
        }

        /**
		 * Defines the class actions and hooks.
		 */
        public function define_actions() {
            add_action('rest_api_init', array( $this, 'register_endpoints' ) );
        }
    
        /**
         * Register the REST API endpoints.
         */
        public function register_endpoints() {

            // Get all posts
            register_rest_route('react/v1', '/posts', array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_posts'),
                'permission_callback' => array($this, 'authenticate_request'),
            ));
    
            // Get specific post by ID or slug
            register_rest_route( 'react/v1', '/posts/(?P<id>[\w-]+)', array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_post' ),
                'permission_callback' => array( $this, 'authenticate_request' ),
            ) );
            
            // Update post
            register_rest_route('react/v1', '/posts/(?<id>\d+)', array(
                'methods'             => 'PUT',
                'callback'            => array($this, 'update_post'),
                'permission_callback' => array($this, 'authenticate_request'),
            ));
            
            // Delete post
            register_rest_route('react/v1', '/posts/(?<id>\d+)', array(
                'methods'             => 'DELETE',
                'callback'            => array($this, 'delete_post'),
                'permission_callback' => array($this, 'authenticate_request'),
            ));
            
            // Create post
            register_rest_route('react/v1', '/posts', array(
                'methods'             => 'POST',
                'callback'            => array($this, 'create_post'),
                'permission_callback' => array($this, 'authenticate_request'),
            ));
        }
    
        /**
         * Get all posts.
         */
        public function get_posts() {
            $args = array(
                'post_type' => 'post',
                'posts_per_page' => -1,
            );
        
            $query = new WP_Query( $args );
            $posts = $query->get_posts();
        
            $response = array();
        
            foreach ( $posts as $post ) {
                $response[] = build_post_response( $post );
            }
        
            wp_reset_postdata();
        
            wp_send_json( $response );
        }
        
        /**
         * Get a specific post by ID or slug.
         *
         * @param WP_REST_Request $request - The request object.
         */
        public function get_post( $request ) {

            $identifier = $request->get_param('id');

            // Check if the identifier is a post ID
            if ( is_numeric( $identifier ) ) {

                $post_id = $identifier;

                if( !is_wp_error( validate_post_id( $post_id ) ) ) {
                    $post = get_post( $post_id );                    
                    return build_post_response( $post );
    
                } else {
                    return validate_post_id( $post_id );
                }
            } else {
                // If identifier is a slug
                $post = get_page_by_path( $identifier, OBJECT, 'post' );
                
                if( !is_null( $post) ) {
                    return build_post_response( $post );
                } else {
                    return new WP_Error(
                        'post_not_found',
                        __('Post not found.'),
                        array( 'status' => 404 )
                    );
                }
            }
        
        }

        /**
         * Create a new post.
         *
         * @param WP_REST_Request $request - The request object.
         */
        public function create_post( $request ) {

            $data = $request->get_json_params();
        
            if ( empty( $data['title'] ) ) {
                return new WP_Error(
                    'missing_title',
                    __('Title is required.'),
                    array('status' => 400)
                );
            }
        
            // Create the new post
            $new_post = array(
                'post_title'   => sanitize_text_field( $data['title'] ),
                'post_content' => isset( $data['content'] ) ? sanitize_textarea_field( $data['content'] ) : '',
                'post_status'  => 'publish',
            );
        
            // Insert the new post into the database
            $post_id = wp_insert_post( $new_post );
        
            if ( !$post_id ) {
                return new WP_Error(
                    'post_creation_failed',
                    __('Failed to create the post.'),
                    array('status' => 500)
                );
            }
        
            // Save custom meta fields, if provided
            if ( isset( $data['meta_fields'] ) && is_array( $data['meta_fields'] ) ) {
                save_post_meta_fields( $post_id, $data['meta_fields'] );
            }
        
            // Get the newly created post
            $post = get_post( $post_id );
        
            // Build the response for the created post in JSON format
            $response = build_post_response($post);
        
            return $response;
        }
        
        /**
         * Update a post.
         *
         * @param WP_REST_Request $request - The request object.
         */
        public function update_post( $request ) {

            $post_id = $request->get_param('id');
        
            if( !is_wp_error( validate_post_id( $post_id ) ) ) {
                $post = get_post( $post_id );

                // Get the updated data of the post from the request body
                $updated_data = json_decode( $request->get_body(), true );
            
                // Update the post fields with the provided data
                foreach ( $updated_data as $field => $value ) {
    
                    if ($field === 'title') {
                        $post->post_title = sanitize_text_field( $value );
                    
                    } elseif ($field === 'content') {
                        $post->post_content = sanitize_textarea_field( $value );
                    
                    } elseif ( $field === 'meta_fields' ) {
                        
                        // Update the post meta fields
                        foreach ( $value as $meta_field ) {
                            $meta_key   = sanitize_text_field( $meta_field['key'] );
                            $meta_value = sanitize_text_field( $meta_field['value'] );
    
                            update_post_meta($post->ID, $meta_key, $meta_value);
                        }
    
                    }
                }
            
                // Save the changes to the updated post
                wp_update_post($post);
            
                // Build the response for the updated post in JSON format
                $response = build_post_response( $post );

                return $response;

            } else {
                return validate_post_id( $post_id );
            }

        }        

        /**
         * Delete a post.
         *
         * @param WP_REST_Request $request - The request object.
         */
        public function delete_post( $request ) {

            $post_id = $request->get_param('id');

            if( !is_wp_error( validate_post_id( $post_id ) ) ) {
                // Delete the post
                wp_delete_post( $post_id, true );
            
                // Build the response for the deleted post in JSON format
                $response = array(
                    'message' => 'Post deleted successfully.'
                );
        
                return $response;

            } else {
                return validate_post_id( $post_id );
            }
        
        }

        /**
         * Authenticate the request.
         *
         * @param WP_REST_Request $request - The request object.
         * @return bool|WP_Error - True if the request is authenticated, WP_Error object otherwise.
         */
        public function authenticate_request( $request ) {
            // Request token
            $access_token = $request->get_header('Authorization');
            
            // Stored token
            $stored_access_token = get_option('wr_endpoint_access_token');
        
            if ( $access_token === $stored_access_token ) {
                return true;
            } else {
                return new WP_Error(
                    'rest_forbidden',
                    __('Invalid access token.'),
                    array('status' => 403)
                );
            }
        }
    }

    $wr_endpoint = new WR_Endpoint();
}