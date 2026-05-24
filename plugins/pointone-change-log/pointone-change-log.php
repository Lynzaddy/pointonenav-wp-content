<?php

/**
 * Plugin Name: Point One Nav - Point One Change Log
 * Description: Custom Change Log system for Point One including CPT, ACF fields, admin sorting, admin columns, and Elementor Query ID support.
 * Version: 1.0.0
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
            'view_items'            => 'View Change Logs',
            'all_items'             => 'All Change Logs',
            'search_items'          => 'Search Change Logs',
            'not_found'             => 'No Change Logs found.',
            'not_found_in_trash'    => 'No Change Logs found in Trash.',
            'archives'              => 'Change Logs',
            'attributes'            => 'Change Log Attributes',
            'insert_into_item'      => 'Insert into change log',
            'uploaded_to_this_item' => 'Uploaded to this change log',
            'filter_items_list'     => 'Filter change log list',
            'items_list_navigation' => 'Change Log list navigation',
            'items_list'            => 'Change Log list',
        ],

        'public' => true,

        /**
         * Dashicon:
         * https://developer.wordpress.org/resource/dashicons/
         */
        'menu_icon' => 'dashicons-backup',

        'supports' => [
            'title',
            'editor'
        ],

        /**
         * We are using a normal Elementor page
         * at /changelog instead of a CPT archive.
         */
        'has_archive' => false,

        'rewrite' => [
            'slug' => 'changelog'
        ],

        'show_in_rest' => true
    ]);
});


/*--------------------------------------------------------------
ACF FIELD GROUP
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

                'first_day' => 0,

                'wrapper' => [
                    'width' => '50'
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
HELPERS
--------------------------------------------------------------*/

function pointone_change_log_format_admin_date($date): string
{
    if (!$date) return '';

    $d = DateTime::createFromFormat('Y-m-d', (string) $date);

    if (!$d) return (string) $date;

    return $d->format('m-d-Y');
}


/*--------------------------------------------------------------
ADMIN COLUMN ORDER
--------------------------------------------------------------*/

add_filter('manage_change_log_posts_columns', function ($columns) {

    return [

        'cb' => $columns['cb'],

        'title' => 'Title',

        'change_log_date' => 'Change Log Date',

        'date' => 'Published'
    ];
});


/*--------------------------------------------------------------
POPULATE ADMIN COLUMNS
--------------------------------------------------------------*/

add_action('manage_change_log_posts_custom_column', function ($column, $post_id) {

    if ($column === 'change_log_date') {

        echo esc_html(
            pointone_change_log_format_admin_date(
                get_field('change_log_date', $post_id)
            )
        );
    }
}, 10, 2);


/*--------------------------------------------------------------
DEFAULT ADMIN SORTING
(Change Log Date DESC)
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
 * Query ID:
 * pointone_change_log
 */

add_action('elementor/query/pointone_change_log', function ($query) {

    $query->set('post_type', 'change_log');

    $query->set('post_status', 'publish');

    $query->set('meta_key', 'change_log_date');

    $query->set('orderby', 'meta_value');

    $query->set('order', 'DESC');
});
