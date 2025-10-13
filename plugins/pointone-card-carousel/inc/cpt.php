<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('init', function () {
    register_post_type('pointone_carousel', [
        'labels' => [
            'name' => 'Carousels',
            'singular_name' => 'Carousel',
            'add_new_item' => 'Add New Carousel',
            'edit_item' => 'Edit Carousel',
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-images-alt2',
        'supports' => ['title'],
        'show_in_rest' => false,
    ]);
});
