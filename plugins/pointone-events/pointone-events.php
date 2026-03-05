<?php

/**
 * Plugin Name: Point One Nav - Events
 * Description: Custom Events system for Point One including CPT, ACF fields, admin indicators, Elementor helpers, and automatic section visibility.
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
REGISTER CUSTOM POST TYPE
--------------------------------------------------------------*/

add_action('init', function () {

    register_post_type('event', [

        'labels' => [
            'name' => 'Events',
            'singular_name' => 'Event',
            'add_new_item' => 'Add Event',
            'edit_item' => 'Edit Event'
        ],

        'public' => true,
        'menu_icon' => 'dashicons-calendar',

        'supports' => [
            'title',
            'thumbnail'
        ],

        'has_archive' => true,

        'rewrite' => [
            'slug' => 'events'
        ],

        'show_in_rest' => true

    ]);
});


/*--------------------------------------------------------------
ACF FIELD GROUP
--------------------------------------------------------------*/

add_action('acf/init', function () {

    if (function_exists('acf_add_local_field_group')) {

        acf_add_local_field_group([

            'key' => 'group_pointone_events',
            'title' => 'Event Details',

            'fields' => [

                [
                    'key' => 'event_start_date',
                    'label' => 'Event Start Date',
                    'name' => 'event_start_date',
                    'type' => 'date_picker',
                    'display_format' => 'F j, Y',
                    'return_format' => 'Ymd'
                ],

                [
                    'key' => 'event_end_date',
                    'label' => 'Event End Date',
                    'name' => 'event_end_date',
                    'type' => 'date_picker',
                    'display_format' => 'F j, Y',
                    'return_format' => 'Ymd'
                ],

                [
                    'key' => 'event_location',
                    'label' => 'Location',
                    'name' => 'event_location',
                    'type' => 'text'
                ],

                [
                    'key' => 'registration_link_text',
                    'label' => 'Registration Link Text',
                    'name' => 'registration_link_text',
                    'type' => 'text'
                ],

                [
                    'key' => 'registration_link_url',
                    'label' => 'Registration Link URL',
                    'name' => 'registration_link_url',
                    'type' => 'text'
                ],

                [
                    'key' => 'recording_link_text',
                    'label' => 'Recording Link Text',
                    'name' => 'recording_link_text',
                    'type' => 'text'
                ],

                [
                    'key' => 'recording_link_url',
                    'label' => 'Recording Link URL',
                    'name' => 'recording_link_url',
                    'type' => 'text'
                ]

            ],

            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'event'
                    ]
                ]
            ]

        ]);
    }
});


/*--------------------------------------------------------------
EVENT DATE DISPLAY SHORTCODE
--------------------------------------------------------------*/

function pointone_event_date_display()
{

    $start = get_field('event_start_date');
    $end = get_field('event_end_date');

    if (!$start) return '';

    $start_obj = DateTime::createFromFormat('Ymd', $start);
    $end_obj = $end ? DateTime::createFromFormat('Ymd', $end) : null;

    if ($end_obj && $start != $end) {

        if ($start_obj->format('F Y') == $end_obj->format('F Y')) {

            return $start_obj->format('F j') . '–' . $end_obj->format('j, Y');
        } else {

            return $start_obj->format('F j, Y') . ' – ' . $end_obj->format('F j, Y');
        }
    }

    return $start_obj->format('F j, Y');
}

add_shortcode('event_date_display', 'pointone_event_date_display');


/*--------------------------------------------------------------
CARD LINK SHORTCODE
--------------------------------------------------------------*/

function pointone_event_card_link()
{

    $end = get_field('event_end_date');
    $today = date('Ymd');

    if (!$end) return '';

    if ($end >= $today) {
        $url = get_field('registration_link_url');
    } else {
        $url = get_field('recording_link_url');
    }

    if (!$url) return '';

    if (!preg_match('#^https?://#', $url)) {
        $url = 'https://' . $url;
    }

    return esc_url($url);
}

add_shortcode('event_card_link', 'pointone_event_card_link');


/*--------------------------------------------------------------
CTA TEXT SHORTCODE
--------------------------------------------------------------*/

function pointone_event_cta_text()
{

    $end = get_field('event_end_date');
    $today = date('Ymd');

    if (!$end) return '';

    if ($end >= $today) {

        $text = get_field('registration_link_text');
        return $text ? esc_html($text) : 'Register';
    } else {

        $text = get_field('recording_link_text');
        return $text ? esc_html($text) : 'View Recording';
    }
}

add_shortcode('event_cta_text', 'pointone_event_cta_text');


/*--------------------------------------------------------------
EVENT COUNT SHORTCODES (FOR HIDING SECTIONS)
--------------------------------------------------------------*/

function pointone_current_events_count()
{

    $today = date('Ymd');

    $args = [
        'post_type' => 'event',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => 'event_end_date',
                'value' => $today,
                'compare' => '>=',
                'type' => 'NUMERIC'
            ]
        ]
    ];

    $query = new WP_Query($args);

    return $query->found_posts;
}

add_shortcode('current_events_count', 'pointone_current_events_count');


function pointone_past_events_count()
{

    $today = date('Ymd');

    $args = [
        'post_type' => 'event',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => 'event_end_date',
                'value' => $today,
                'compare' => '<',
                'type' => 'NUMERIC'
            ]
        ]
    ];

    $query = new WP_Query($args);

    return $query->found_posts;
}

add_shortcode('past_events_count', 'pointone_past_events_count');


/*--------------------------------------------------------------
ADMIN COLUMNS
--------------------------------------------------------------*/

add_filter('manage_event_posts_columns', function ($columns) {

    $columns['event_start'] = 'Start Date';
    $columns['event_end'] = 'End Date';
    $columns['event_status'] = 'Status';

    return $columns;
});


add_action('manage_event_posts_custom_column', function ($column, $post_id) {

    if ($column == 'event_start') {
        echo get_field('event_start_date', $post_id);
    }

    if ($column == 'event_end') {
        echo get_field('event_end_date', $post_id);
    }

    if ($column == 'event_status') {

        $end = get_field('event_end_date', $post_id);
        $today = date('Ymd');

        if ($end >= $today) {

            echo '<span style="background:#e6f7ed;color:#1d7a3e;padding:4px 10px;border-radius:20px;font-weight:600;">Active</span>';
        } else {

            echo '<span style="background:#eee;color:#666;padding:4px 10px;border-radius:20px;font-weight:600;">Expired</span>';
        }
    }
}, 10, 2);


/*--------------------------------------------------------------
DEFAULT ADMIN SORTING
--------------------------------------------------------------*/

add_action('pre_get_posts', function ($query) {

    if (!is_admin() || !$query->is_main_query()) return;
    if ($query->get('post_type') != 'event') return;

    $query->set('meta_key', 'event_start_date');
    $query->set('orderby', 'meta_value');
    $query->set('meta_type', 'NUMERIC');
    $query->set('order', 'DESC');
});
