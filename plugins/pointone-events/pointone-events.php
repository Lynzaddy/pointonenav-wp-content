<?php

/**
 * Plugin Name: Point One Nav - Point One Events
 * Description: Custom Events system for Point One including CPT, ACF fields, admin indicators, Elementor helpers, validation, section counts, Elementor Query IDs, plugin-managed CSS, default featured image handling, and author support.
 * Version: 1.7.4
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
            'thumbnail',
            'author'
        ],

        'has_archive' => true,

        'rewrite' => [
            'slug' => 'events'
        ],

        'show_in_rest' => true
    ]);
});


/*--------------------------------------------------------------
ENQUEUE FRONTEND CSS
--------------------------------------------------------------*/

add_action('wp_enqueue_scripts', function () {

    wp_enqueue_style(
        'pointone-events',
        plugins_url('assets/pointone-events.css', __FILE__),
        [],
        '1.7.4'
    );
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
                'key' => 'event_location',
                'label' => 'Event Location',
                'name' => 'event_location',
                'type' => 'text',
                'required' => 1,
                'wrapper' => [
                    'width' => '100'
                ]
            ],

            [
                'key' => 'event_start_date',
                'label' => 'Event Start Date (First day of event)',
                'name' => 'event_start_date',
                'type' => 'date_picker',
                'required' => 1,
                'display_format' => 'F j, Y',
                'return_format' => 'Ymd',
                'wrapper' => [
                    'width' => '50'
                ]
            ],

            [
                'key' => 'event_end_date',
                'label' => 'Event End Date (Last day of event, can be same as start date for single-day events)',
                'name' => 'event_end_date',
                'type' => 'date_picker',
                'required' => 1,
                'display_format' => 'F j, Y',
                'return_format' => 'Ymd',
                'wrapper' => [
                    'width' => '50'
                ]
            ],

            [
                'key' => 'registration_link_text',
                'label' => 'Registration Link Text',
                'name' => 'registration_link_text',
                'type' => 'text',
                'wrapper' => [
                    'width' => '50'
                ]
            ],

            [
                'key' => 'registration_link_url',
                'label' => 'Registration Link URL',
                'name' => 'registration_link_url',
                'type' => 'text',
                'wrapper' => [
                    'width' => '50'
                ]
            ],

            [
                'key' => 'recording_link_text',
                'label' => 'Recording Link Text',
                'name' => 'recording_link_text',
                'type' => 'text',
                'wrapper' => [
                    'width' => '50'
                ]
            ],

            [
                'key' => 'recording_link_url',
                'label' => 'Recording Link URL',
                'name' => 'recording_link_url',
                'type' => 'text',
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

    if ((int) $value < (int) $start) {
        return 'Event End Date must be the same as or later than the Event Start Date.';
    }

    return $valid;
}, 10, 4);


/*--------------------------------------------------------------
DEFAULT FEATURED IMAGE
--------------------------------------------------------------*/

function pointone_events_get_default_image_id(): int
{
    static $attachment_id = null;

    if ($attachment_id !== null) {
        return $attachment_id;
    }

    $relative_path = '2026/03/PointOneNav-Generic-Image3x.png';

    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => '_wp_attached_file',
                'value' => $relative_path,
                'compare' => '='
            ]
        ]
    ]);

    if (!empty($existing)) {
        $attachment_id = (int) $existing[0];
        return $attachment_id;
    }

    $full_url = home_url('/wp-content/uploads/' . $relative_path);
    $attachment_id = (int) attachment_url_to_postid($full_url);

    return $attachment_id;
}

add_action('save_post_event', function ($post_id, $post, $update) {

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (has_post_thumbnail($post_id)) return;

    $default_image_id = pointone_events_get_default_image_id();

    if ($default_image_id) {
        set_post_thumbnail($post_id, $default_image_id);
    }
}, 10, 3);


/*--------------------------------------------------------------
HELPERS
--------------------------------------------------------------*/

function pointone_events_today_ymd(): int
{
    return (int) current_time('Ymd');
}

function pointone_events_format_admin_date($date): string
{
    if (!$date) return '';

    $d = DateTime::createFromFormat('Ymd', (string) $date);
    if (!$d) return (string) $date;

    return $d->format('m-d-Y');
}


/*--------------------------------------------------------------
EVENT DATE DISPLAY SHORTCODE
--------------------------------------------------------------*/

function pointone_event_date_display(): string
{
    $start = get_field('event_start_date');
    $end   = get_field('event_end_date');

    if (!$start) return '';

    $start_obj = DateTime::createFromFormat('Ymd', (string) $start);
    $end_obj   = $end ? DateTime::createFromFormat('Ymd', (string) $end) : null;

    if (!$start_obj) return '';

    if ($end_obj && (string) $start !== (string) $end) {

        if ($start_obj->format('F Y') === $end_obj->format('F Y')) {
            return esc_html($start_obj->format('F j') . '–' . $end_obj->format('j, Y'));
        }

        return esc_html($start_obj->format('F j, Y') . ' – ' . $end_obj->format('F j, Y'));
    }

    return esc_html($start_obj->format('F j, Y'));
}
add_shortcode('event_date_display', 'pointone_event_date_display');


/*--------------------------------------------------------------
CARD LINK SHORTCODE
--------------------------------------------------------------*/

function pointone_event_card_link(): string
{
    $end = (int) get_field('event_end_date');
    $today = pointone_events_today_ymd();

    if (!$end) return '';

    if ($end >= $today) {
        $url = (string) get_field('registration_link_url');
    } else {
        $url = (string) get_field('recording_link_url');
    }

    $url = trim($url);
    if ($url === '') return '';

    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }

    return esc_url($url);
}
add_shortcode('event_card_link', 'pointone_event_card_link');


/*--------------------------------------------------------------
CTA TEXT SHORTCODE
--------------------------------------------------------------*/

function pointone_event_cta_text(): string
{
    $end = (int) get_field('event_end_date');
    $today = pointone_events_today_ymd();

    if (!$end) return '';

    if ($end >= $today) {
        $url = trim((string) get_field('registration_link_url'));
        if ($url === '') return '';

        $text = trim((string) get_field('registration_link_text'));
        if ($text === '') {
            $text = 'Register';
        }

        return '<span class="pointone-event-card__cta-text">' . esc_html($text) . '</span>';
    }

    $url = trim((string) get_field('recording_link_url'));
    $text = trim((string) get_field('recording_link_text'));

    if ($url === '' || $text === '') {
        return '';
    }

    return '<span class="pointone-event-card__cta-text">' . esc_html($text) . '</span>';
}
add_shortcode('event_cta_text', 'pointone_event_cta_text');


/*--------------------------------------------------------------
EVENT COUNT SHORTCODES (OPTIONAL FOR CONDITIONAL DISPLAY)
--------------------------------------------------------------*/

function pointone_current_events_count(): int
{
    $today = pointone_events_today_ymd();

    $q = new WP_Query([
        'post_type' => 'event',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => false,
        'meta_query' => [
            [
                'key' => 'event_end_date',
                'value' => $today,
                'compare' => '>=',
                'type' => 'NUMERIC'
            ]
        ]
    ]);

    return (int) $q->found_posts;
}
add_shortcode('current_events_count', 'pointone_current_events_count');

function pointone_past_events_count(): int
{
    $today = pointone_events_today_ymd();

    $q = new WP_Query([
        'post_type' => 'event',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => false,
        'meta_query' => [
            [
                'key' => 'event_end_date',
                'value' => $today,
                'compare' => '<',
                'type' => 'NUMERIC'
            ]
        ]
    ]);

    return (int) $q->found_posts;
}
add_shortcode('past_events_count', 'pointone_past_events_count');


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
        echo esc_html(pointone_events_format_admin_date(get_field('event_start_date', $post_id)));
    }

    if ($column === 'event_end') {
        echo esc_html(pointone_events_format_admin_date(get_field('event_end_date', $post_id)));
    }

    if ($column === 'event_status') {

        $end = (int) get_field('event_end_date', $post_id);
        $today = pointone_events_today_ymd();

        if ($end && $end >= $today) {
            echo '<span class="sab-badge sab-active">Active</span>';
        } else {
            echo '<span class="sab-badge sab-expired">Expired</span>';
        }
    }
}, 10, 2);


/*--------------------------------------------------------------
DEFAULT ADMIN SORTING (Start Date DESC)
--------------------------------------------------------------*/

add_action('pre_get_posts', function ($query) {

    if (!is_admin() || !$query->is_main_query()) return;
    if ($query->get('post_type') !== 'event') return;

    if (!$query->get('orderby')) {
        $query->set('meta_key', 'event_start_date');
        $query->set('orderby', 'meta_value_num');
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


/*--------------------------------------------------------------
ELEMENTOR QUERY IDs
--------------------------------------------------------------*/

/**
 * Current Events Query ID:
 * pointone_current_events
 */
add_action('elementor/query/pointone_current_events', function ($query) {

    $today = pointone_events_today_ymd();

    $query->set('post_type', 'event');
    $query->set('post_status', 'publish');

    $query->set('meta_key', 'event_start_date');
    $query->set('orderby', 'meta_value_num');
    $query->set('order', 'ASC');

    $query->set('meta_query', [
        [
            'key' => 'event_end_date',
            'value' => $today,
            'compare' => '>=',
            'type' => 'NUMERIC'
        ]
    ]);
});


/**
 * Past Events Query ID:
 * pointone_past_events
 */
add_action('elementor/query/pointone_past_events', function ($query) {

    $today = pointone_events_today_ymd();

    $query->set('post_type', 'event');
    $query->set('post_status', 'publish');

    $query->set('meta_key', 'event_start_date');
    $query->set('orderby', 'meta_value_num');
    $query->set('order', 'DESC');

    $query->set('meta_query', [
        [
            'key' => 'event_end_date',
            'value' => $today,
            'compare' => '<',
            'type' => 'NUMERIC'
        ]
    ]);
});
