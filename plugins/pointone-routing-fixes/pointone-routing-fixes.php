<?php

/**
 * Plugin Name: Point One Nav - Routing Fixes
 * Description: Corrects WordPress URL resolution edge cases for /%category%/%postname%/ permalink structure. Shorthand single-segment URLs (e.g. /case-studies/, /press-release/) are routed to their matching category or tag archive. Unrecognised slugs 404.
 * Version:     1.3.0
 * Author:      Point One Nav
 */

if (! defined('ABSPATH')) exit;

/**
 * Smart routing for single-segment URLs.
 *
 * With /%category%/%postname%/ permalinks, WordPress only natively resolves
 * single-segment slugs as category archives. This means:
 *
 *   /case-studies/  → works (it's a category slug)
 *   /press-release/ → 404s (it's a tag slug, not a category)
 *   /bad-link/      → should 404 (no match)
 *
 * This hook intercepts template_redirect and:
 *   1. Leaves all real WordPress content alone (pages, posts, front page)
 *   2. If the slug matches a tag → 301 redirect to /tag/slug/
 *   3. If the slug matches nothing (empty category archive without /category/ prefix) → 404
 *   4. Everything else is left alone
 */
add_action('template_redirect', function () {
    $category_base = get_option('category_base') ?: 'category';
    $request       = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    // Only act on single-segment URLs (no slashes in the request path)
    if (strpos($request, '/') !== false) return;

    // Skip empty requests and known taxonomy bases
    if (empty($request) || $request === $category_base) return;

    // Don't interfere with real WordPress content
    if (is_page() || is_single() || is_front_page()) return;

    // If WordPress resolved this as a valid category archive with posts, leave it alone
    if (is_category() && have_posts()) return;

    // Check if the slug matches a tag — if so, redirect to the canonical tag URL
    $tag = get_term_by('slug', $request, 'post_tag');
    if ($tag && ! is_wp_error($tag)) {
        wp_redirect(get_tag_link($tag), 301);
        exit;
    }

    // No category with posts and no tag match — force 404
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
});
