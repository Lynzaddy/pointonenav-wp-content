<?php

/**
 * Plugin Name: Point One Nav - Point One Change Log
 * Description: Custom Change Log system for Point One including CPT, ACF fields, admin sorting, admin columns, Elementor Query ID support, REST API support, and display shortcodes.
 * Version: 1.3.0
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
         * We are using ACF fields for date/content.
         */
        'supports' => [
            'title',
            'author'
        ],

        'has_archive' => false,

        'rewrite' => [
            'slug' => 'changelog'
        ],

        /**
         * Enables /wp-json/wp/v2/change_log for n8n/API automation.
         * The classic admin layout is preserved separately below.
         */
        'show_in_rest' => true,

        /**
         * Explicit REST base for clarity.
         */
        'rest_base' => 'change_log',
    ]);
});


/*--------------------------------------------------------------
KEEP CLASSIC ADMIN EDITOR LAYOUT
--------------------------------------------------------------*/

/**
 * show_in_rest must be true for API access, but that can cause WordPress
 * to prefer the block editor. This forces Change Log to keep the classic
 * admin layout so the ACF field flow still matches Events.
 */

add_filter('use_block_editor_for_post_type', function ($use_block_editor, $post_type) {

    if ($post_type === 'change_log') {
        return false;
    }

    return $use_block_editor;
}, 10, 2);


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

                /**
                 * Keep Ymd for consistent sorting with Elementor and admin.
                 * Example stored value: 20260526
                 */
                'return_format' => 'Ymd',

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
        ],

        /**
         * Allows ACF fields to be read/written via REST API using the "acf" object.
         */
        'show_in_rest' => 1,
    ]);
});


/*--------------------------------------------------------------
REST API META REGISTRATION
--------------------------------------------------------------*/

/**
 * Registers the underlying ACF meta fields with WordPress REST.
 *
 * This makes the fields available in the standard "meta" object too,
 * which is useful for n8n or other automation tools that may not use
 * the ACF REST "acf" object.
 *
 * REST write examples:
 *
 * Option A — ACF object:
 * {
 *   "title": "Change Log 2.0.0",
 *   "status": "publish",
 *   "acf": {
 *     "change_log_date": "20260526",
 *     "change_log_content": "<ul><li>Added X</li><li>Fixed Y</li></ul>"
 *   }
 * }
 *
 * Option B — meta object:
 * {
 *   "title": "Change Log 2.0.0",
 *   "status": "publish",
 *   "meta": {
 *     "change_log_date": "20260526",
 *     "change_log_content": "<ul><li>Added X</li><li>Fixed Y</li></ul>"
 *   }
 * }
 */

add_action('init', function () {

    register_post_meta('change_log', 'change_log_date', [
        'single' => true,
        'type' => 'string',
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);

    register_post_meta('change_log', 'change_log_content', [
        'single' => true,
        'type' => 'string',
        'show_in_rest' => true,
        'sanitize_callback' => 'wp_kses_post',
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
});


/*--------------------------------------------------------------
HELPERS
--------------------------------------------------------------*/

function pointone_change_log_format_admin_date($date): string
{
    if (!$date) return '';

    $date = (string) $date;

    $d = DateTime::createFromFormat('Ymd', $date);

    if (!$d) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
    }

    if (!$d) return $date;

    return $d->format('m-d-Y');
}

function pointone_change_log_format_display_date($date): string
{
    if (!$date) return '';

    $date = (string) $date;

    $d = DateTime::createFromFormat('Ymd', $date);

    if (!$d) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
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

    /**
     * wpautop protects formatting if the content is plain text,
     * while still allowing WYSIWYG HTML such as ul/li/p/a.
     */
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
        $query->set('orderby', 'meta_value_num');
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
    $query->set('orderby', 'meta_value_num');
    $query->set('order', 'DESC');
});
