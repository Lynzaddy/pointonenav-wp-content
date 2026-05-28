<?php

/**
 * Plugin Name: Point One Routing Fixes
 * Description: Corrects WordPress URL resolution edge cases — forces 404 for unknown single-segment slugs that WordPress misinterprets as category archives due to /%category%/%postname%/ permalink structure.
 * Version:     1.1.0
 * Author:      Point One Nav
 */

if (! defined('ABSPATH')) exit;

/**
 * Fix 3: Single-segment unknown slugs like /bad-link/ are interpreted by
 * WordPress as category archives due to /%category%/%postname%/ permalinks.
 * If the URL doesn't use the explicit /category/ base, force a 404.
 *
 * Note: /category/bad-link/ (with the explicit category base) intentionally
 * shows the "nothing found" archive state — this is the correct UX for a
 * navigational failure where context is available.
 */
add_action('template_redirect', function () {
    if (! is_category() || have_posts()) return;

    // Get the category base (defaults to 'category' if not customized)
    $category_base = get_option('category_base') ?: 'category';
    $request       = trim($_SERVER['REQUEST_URI'], '/');

    // If the URL doesn't start with the category base, it's an ambiguous
    // slug that fell through — not a real category URL
    if (strpos($request, $category_base . '/') !== 0) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }
});
