<?php

if ( ! defined('ABSPATH') ) {
    die('Direct access not permitted.');
}

/**
 * Get url status code
 * 
 * @param string $url - Url to check status.
 *  
 * @return string $status - Status of url that retrieve wp_remote_get.
 */
function get_url_status( $url ) {
    $response = wp_remote_get( $url );
    $status   = '';

    if ( is_wp_error( $response ) ) {
        $http_code = wp_remote_retrieve_response_code( $response );
        
        if ( $http_code ) {
            $status = $http_code . ' ' . get_status_header_desc( $http_code );
        } else {
            // Host resolution error, asume 404 Not Found
            $status = '404 Not Found';
        }
    } else {
        $http_code = wp_remote_retrieve_response_code( $response );
        
        if ( $http_code ) {
            $status = $http_code . ' ' . get_status_header_desc( $http_code );
        } else {
            $status = 'Incorrect Status Code';
        }
    }

    return $status;
}

/**
 * Checks if a post is in draft status.
 * 
 * @param int $post_id - The ID of the post.
 * 
 * @return boolean $draft - True if the post is in draft status, false otherwise.
 */
function check_is_draft( $post_id ) {
    $draft = false;

    if( get_post_status( $post_id ) == 'draft' ) {
        $draft = true;
    }

    if( get_post_status( $post_id ) == 'auto-draft' ) {
        $draft = true;
    }

    return $draft;
}