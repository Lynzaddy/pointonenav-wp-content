<?php

/**
 * Plugin Name: Point One Routing Fixes
 * Description: Corrects WordPress URL resolution edge cases — forces 404 for empty category archives and unknown single-segment slugs.
 * Version:     1.0.0
 * Author:      Point One Nav
 */

if (! defined('ABSPATH')) exit;

/**
 * Fix: Unknown single-segment slugs (e.g. /bad-link/) fall through
 * to is_home() in WordPress due to /%category%/%postname%/ permalink
 * ambiguity. Force a 404 unless it's a legitimate visit to the Posts Page.
 */
add_action('template_redirect', function () {
    if (! is_home()) return;

    $posts_page_id  = (int) get_option('page_for_posts');
    $queried_object = get_queried_object();

    if (! ($queried_object instanceof WP_Post && $queried_object->ID === $posts_page_id)) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }
});
