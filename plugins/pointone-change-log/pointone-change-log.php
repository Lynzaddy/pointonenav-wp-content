<?php

/**
 * Plugin Name: Point One Nav - Point One Change Log
 * Description: Custom Change Log system with CPT, ACF fields, admin sorting, and Elementor integration.
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) exit;


/*--------------------------------------------------------------
REGISTER CUSTOM POST TYPE
--------------------------------------------------------------*/

add_action('init', function () {

    register_post_type('change_log', [

        'labels' => [

            'name'                  => 'Change Log',
            'singular_name'         => 'Change Log',
            'menu_name'             => 'Change Log',
            'name_admin_bar'        => 'Change Log',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Change Log',
            'new_item'              => 'New Change Log',
            'edit_item'             => 'Edit Change Log',
            'view_item'             => 'View Change Log',
            'all_items'             => 'All Change Logs',
            'search_items'          => 'Search Change Logs',
            'not_found'             => 'No Change Logs found.',
            'not_found_in_trash'    => 'No Change Logs found in Trash.',
            'archives'              => 'Change Logs',
        ],

        'public' => true,

        'menu_icon' => 'dashicons-backup',

        'supports' => [
            'title',
            'editor'
        ],

        'has_archive' => false,

        'rewrite' => [
            'slug' => 'changelog'
        ],

        'show_in_rest' => true
    ]);
});


/*--------------------------------------------------------------
ACF FIELD GROUP (EVENT-STYLE META BOX)
--------------------------------------------------------------*/

add_action('acf/init', function () {

    if (!function_exists('acf_add_local_field_group')) return;

    acf_add_local_field_group([

        'key' => 'group_pointone_change_log',

        'title' => 'Change Log Details',

        'fields' => [

            [
                'key' => 'change_log_date',
                'label' => 'Change Log Date',
                'name' => 'change_log_date',
                'type' => 'date_picker',
                'required' => 1,
                'display_format' => 'F j, Y',
                'return_format' => 'Y-m-d',
                'wrapper' => [
                    'width' => '100'
                ]
            ]

        ],

        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'change_log'
                ]
            ]
        ]
    ]);
});


/*--------------------------------------------------------------
ADMIN COLUMN SETUP (EVENT STYLE)
--------------------------------------------------------------*/

add_filter('manage_change_log_posts_columns', function ($columns) {

    return [

        'cb' => $columns['cb'],
        'title' => 'Title',
        'change_log_date' => 'Change Log Date',
        'date' => 'Published'
    ];
});


add_action('manage_change_log_posts_custom_column', function ($column, $post_id) {

    if ($column === 'change_log_date') {

        $date = get_field('change_log_date', $post_id);

        if ($date) {
            echo esc_html(
                DateTime::createFromFormat('Y-m-d', $date)->format('m-d-Y')
            );
        }
    }
}, 10, 2);


/*--------------------------------------------------------------
ADMIN SORTING (DATE DESC)
--------------------------------------------------------------*/

add_action('pre_get_posts', function ($query) {

    if (!is_admin() || !$query->is_main_query()) return;

    if ($query->get('post_type') !== 'change_log') return;

    if (!$query->get('orderby')) {

        $query->set('meta_key', 'change_log_date');
        $query->set('orderby', 'meta_value');
        $query->set('order', 'DESC');
    }
});


/*--------------------------------------------------------------
ELEMENTOR QUERY ID
--------------------------------------------------------------*/

/**
 * Query ID: pointone_change_log
 */

add_action('elementor/query/pointone_change_log', function ($query) {

    $query->set('post_type', 'change_log');
    $query->set('post_status', 'publish');
    $query->set('meta_key', 'change_log_date');
    $query->set('orderby', 'meta_value');
    $query->set('order', 'DESC');
});


/*--------------------------------------------------------------
ADMIN UI POLISH (MATCH EVENTS FEEL)
--------------------------------------------------------------*/

add_action('admin_head', function () {

    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== 'change_log') return;

?>
    <style>
        /* Make ACF box feel like a primary section like Events */
        #acf-group_pointone_change_log {
            border: 1px solid #dcdcde;
            border-left: 4px solid #2271b1;
            background: #fff;
        }

        #acf-group_pointone_change_log .acf-label label {
            font-weight: 600;
        }
    </style>
<?php
});
