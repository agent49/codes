<?php
/*
Plugin Name: Custom ACF API
Description: Adds a custom REST API endpoint for ACF values with media URL conversion.
Version: 1.0
Author: Agent 49
*/

// Register the custom API endpoint
function custom_acf_api_init() {
    register_rest_route('agent-api/v1', '/(?P<post_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'custom_get_acf_values',
    ));
}
add_action('rest_api_init', 'custom_acf_api_init');

// Callback function to retrieve ACF values and convert media IDs to URLs
function custom_get_acf_values($request) {
    $post_id = $request->get_param('post_id');
    
    // Check if the post ID is valid
    if (empty($post_id)) {
        return new WP_Error('invalid_post_id', 'Invalid post ID.', array('status' => 400));
    }

    // Retrieve ACF values
    $acf_values = get_fields($post_id);
    
    // Convert media IDs to URLs
    foreach ($acf_values as &$value) {
        if (is_array($value)) {
            array_walk_recursive($value, function(&$item, $key) {
                if ($key === 'id' && is_numeric($item)) {
                    $item = wp_get_attachment_url($item);
                }
            });
        } elseif ($value && is_numeric($value)) {
            $value = wp_get_attachment_url($value);
        }
    }

    return rest_ensure_response($acf_values);
}

// Disable all other REST API endpoints
add_filter('rest_endpoints', function ($endpoints) {
    $allowed_endpoints = array(
        '/agent-api/v1/(?P<post_id>\d+)' // Your custom endpoint
    );

    foreach ($endpoints as $route => $data) {
        if (!in_array($route, $allowed_endpoints)) {
            unset($endpoints[$route]);
        }
    }

    return $endpoints;
});