<?php

/**
 * Plugin Name: Point One Events
 * Description: Custom Events system for Point One including CPT, ACF fields, admin indicators, Elementor helpers, validation, and automatic section visibility.
 * Version: 1.4
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

    if (!function_exists('acf_add_local_field_group')) return;

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
});


/*--------------------------------------------------------------
VALIDATE START/END DATE ORDER
--------------------------------------------------------------*/

add_filter('acf/validate_value/name=event_end_date', function ($valid, $value) {

    if (!$valid) return $valid;

    $start = $_POST['acf']['event_start_date'] ?? '';

    if (!$start || !$value) return $valid;

    if ($value < $start) {
        return 'End Date must be the same as or later than the Start Date.';
    }

    return $valid;
}, 10, 4);


/*--------------------------------------------------------------
DATE FORMATTER
--------------------------------------------------------------*/

function pointone_format_admin_date($date)
{
    if (!$date) return '';

    $d = DateTime::createFromFormat('Ymd', $date);
    if (!$d) return $date;

    return $d->format('m-d-Y');
}


/*--------------------------------------------------------------
ADMIN COLUMN ORDER
--------------------------------------------------------------*/

add_filter('manage_event_posts_columns', function ($columns) {

    return [

        'cb' => $columns['cb'],
        'title' => 'Title',
        'event_start' => 'Start Date',
        'event_end' => 'End Date',
        'event_status' => 'Status',
        'date' => 'Date'

    ];
});


/*--------------------------------------------------------------
POPULATE ADMIN COLUMNS
--------------------------------------------------------------*/

add_action('manage_event_posts_custom_column', function ($column, $post_id) {

    if ($column === 'event_start') {
        echo pointone_format_admin_date(get_field('event_start_date', $post_id));
    }

    if ($column === 'event_end') {
        echo pointone_format_admin_date(get_field('event_end_date', $post_id));
    }

    if ($column === 'event_status') {

        $end = get_field('event_end_date', $post_id);
        $today = date('Ymd');

        if ($end >= $today) {

            echo '<span class="sab-badge sab-active">Active</span>';
        } else {

            echo '<span class="sab-badge sab-expired">Expired</span>';
        }
    }
}, 10, 2);


/*--------------------------------------------------------------
DEFAULT ADMIN SORTING
--------------------------------------------------------------*/

add_action('pre_get_posts', function ($query) {

    if (!is_admin() || !$query->is_main_query()) return;
    if ($query->get('post_type') !== 'event') return;

    if (!$query->get('orderby')) {

        $query->set('meta_key', 'event_start_date');
        $query->set('orderby', 'meta_value');
        $query->set('meta_type', 'NUMERIC');
        $query->set('order', 'DESC');
    }
});


/*--------------------------------------------------------------
ADMIN BADGE STYLES (MATCH ALERTS)
--------------------------------------------------------------*/

add_action('admin_enqueue_scripts', function () {
?>
    <style>
        .sab-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(0, 0, 0, .08);
            background: #fff;
        }

        .sab-badge::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .sab-active {
            background: #e6f9ed;
            color: #0f5132;
        }

        .sab-active::before {
            background: #28a745;
        }

        .sab-expired {
            background: #f1f3f5;
            color: #383d41;
        }

        .sab-expired::before {
            background: #6b7280;
        }
    </style>
<?php
});
