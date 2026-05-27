<?php

/**
 * Plugin Name: Point One Nav - Point One Change Log
 * Description: Custom Change Log system for Point One including CPT, ACF fields, admin sorting, admin columns, Elementor Query ID support, and display shortcodes.
 * Version: 1.2.0
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
        'menu_icon' => 'dashicons-backup',

        /**
         * Do NOT include editor.
         * We are using an ACF WYSIWYG field for the change log content.
         */
        'supports' => [
            'title',
            'author'
        ],

        'has_archive' => false,

        'rewrite' => [
            'slug' => 'changelog'
        ],

        'show_in_rest' => false
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
                    'width' => '100'
                ]
            ],

            [
                'key' => 'change_log_content',
                'label' => 'Change Log Content',
                'name' => 'change_log_content',
                'type' => 'wysiwyg',
                'required' => 1,
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 0,
                'delay' => 0,
                'instructions' => 'Add the bulleted list or summary of changes included in this update.',
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
HELPERS
--------------------------------------------------------------*/

function pointone_change_log_format_admin_date($date): string
{
    if (!$date) return '';

    $date = (string) $date;

    $d = DateTime::createFromFormat('Y-m-d', $date);

    if (!$d) {
        $d = DateTime::createFromFormat('Ymd', $date);
    }

    if (!$d) return $date;

    return $d->format('m-d-Y');
}

function pointone_change_log_format_display_date($date): string
{
    if (!$date) return '';

    $date = (string) $date;

    $d = DateTime::createFromFormat('Y-m-d', $date);

    if (!$d) {
        $d = DateTime::createFromFormat('Ymd', $date);
    }

    if (!$d) return $date;

    return $d->format('F j, Y');
}


/*--------------------------------------------------------------
DISPLAY SHORTCODE: CHANGE LOG DATE
--------------------------------------------------------------*/

/**
 * Usage in Elementor Loop Item:
 * [change_log_date_display]
 *
 * Outputs:
 * May 26, 2026
 */

function pointone_change_log_date_display(): string
{
    $date = '';

    if (function_exists('get_field')) {
        $date = get_field('change_log_date');
    }

    if (!$date) {
        $date = get_post_meta(get_the_ID(), 'change_log_date', true);
    }

    return esc_html(pointone_change_log_format_display_date($date));
}
add_shortcode('change_log_date_display', 'pointone_change_log_date_display');


/*--------------------------------------------------------------
DISPLAY SHORTCODE: CHANGE LOG CONTENT
--------------------------------------------------------------*/

/**
 * Usage in Elementor Loop Item:
 * [change_log_content_display]
 *
 * Preserves WYSIWYG formatting, bullets, links, paragraphs, and line breaks.
 */

function pointone_change_log_content_display(): string
{
    $content = '';

    if (function_exists('get_field')) {
        $content = get_field('change_log_content');
    }

    if (!$content) {
        $content = get_post_meta(get_the_ID(), 'change_log_content', true);
    }

    if (!$content) return '';

    $content = do_shortcode($content);
    $content = wpautop($content);

    return '<div class="pointone-change-log-content">' . wp_kses_post($content) . '</div>';
}
add_shortcode('change_log_content_display', 'pointone_change_log_content_display');


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
