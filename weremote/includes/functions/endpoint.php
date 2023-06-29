<?php

if ( ! defined('ABSPATH') ) {
    die('Direct access not permitted.');
}

/**
 * Retrieves the categories of a post.
 * 
 * @param int $post_id - Post ID.
 * 
 * @return array $categories - Array of categories with their ID, title, and description.
*/
function get_post_categories( $post_id ) {
    $categories = array();
    $terms      = get_the_terms( $post_id, 'category' );

    if ( $terms && !is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $categories[] = array(
                'id'          => $term->term_id,
                'title'       => $term->name,
                'description' => $term->description,
            );
        }
    }

    return $categories;
}

/**
 * Retrieves the meta fields of a post.
 * 
 * @param int $post_id - Post ID.
 * 
 * @return array $meta_fields - Array of meta fields with their key and value.
*/
function get_post_meta_fields( $post_id ) {
    $meta_fields = array();
    $fields      = get_post_meta( $post_id );

    foreach ( $fields as $key => $value ) {
        $meta_fields[] = array(
            'key'   => $key,
            'value' => $value[0],
        );
    }

    return $meta_fields;
}

/**
 * 
 * Builds the response object for a post.
 * 
 * @param WP_Post $post - Post object.
 * 
 * @return array $response - Response object with post details, including categories and meta fields.
*/
function build_post_response( $post ) {
    $response = array(
        'id'             => $post->ID,
        'slug'           => $post->post_name,
        'link'           => get_permalink( $post->ID ),
        'title'          => $post->post_title,
        'featured_image' => get_the_post_thumbnail_url( $post->I ),
        'categories'     => get_post_categories( $post->ID ),
        'content'        => $post->post_content,
        'meta_fields'    => get_post_meta_fields( $post->ID ),
    );

    return $response;
}

/**
 * Validates the post ID and checks if it exists.
 *
 * @param int $post_id - Post ID to validate.
 * @return WP_Error|null - WP_Error object if the ID is invalid or the post doesn't exist, or null if the validation is successful.
 */
function validate_post_id( $post_id ) {

    if ( empty( $post_id ) || !is_numeric( $post_id ) ) {
        return new WP_Error(
            'invalid_post_id',
            __('Invalid post ID.'),
            array('status' => 400)
        );
    }

    $post = get_post( $post_id );

    if ( empty( $post) ) {
        return new WP_Error(
            'post_not_found',
            __('Post not found.'),
            array('status' => 404)
        );
    }

    return null;
}

/**
 * Saves the custom meta fields of a post.
 *
 * @param int $post_id - Post ID.
 * @param array $meta_fields - Custom meta fields.
 */
function save_post_meta_fields( $post_id, $meta_fields ) {
    
    if ( isset( $meta_fields ) && is_array( $meta_fields ) ) {
        
        foreach ( $meta_fields as $meta_field ) {
            $meta_key   = isset( $meta_field['key'] ) ? sanitize_text_field( $meta_field['key'] ) : '';
            $meta_value = isset( $meta_field['value'] ) ? sanitize_text_field( $meta_field['value'] ) : '';

            if ( !empty( $meta_key ) && !empty( $meta_value ) ) {
                update_post_meta( $post_id, $meta_key, $meta_value );
            }
        }
    }
}
