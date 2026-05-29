<?php

/**
 * Plugin Name: Point One Nav - Routing Fixes
 * Description: Corrects WordPress URL resolution edge cases for /%category%/%postname%/ permalink structure. Shorthand single-segment URLs (e.g. /case-studies/, /press-release/) are routed to their matching category or tag archive. Paginated shorthand URLs (e.g. /insights/page/2/) are also handled. Unrecognised slugs 404.
 * Version:     1.3.2
 * Author:      Point One Nav
 */

if (! defined('ABSPATH')) exit;

/**
 * Smart routing for single-segment and paginated shorthand URLs.
 *
 * With /%category%/%postname%/ permalinks, WordPress only natively resolves
 * single-segment slugs as category archives. This means:
 *
 *   /case-studies/        → works (it's a category slug)
 *   /press-release/       → 404s (it's a tag slug, not a category)
 *   /insights/page/2/     → 404s (paginated shorthand, not natively resolved)
 *   /bad-link/            → should 404 (no match)
 *
 * This hook intercepts template_redirect and:
 *   1. Leaves all real WordPress content alone (pages, posts, front page, blog home)
 *   2. Detects paginated shorthand URLs and redirects to the canonical paginated archive URL
 *   3. If the slug matches a tag → 301 redirect to /tag/slug/
 *   4. If the slug matches nothing → 404
 *   5. Everything else is left alone
 */
add_action('template_redirect', function () {
    $category_base = get_option('category_base') ?: 'category';
    $request       = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    // Don't interfere with real WordPress content
    if (is_page() || is_single() || is_front_page() || is_home()) return;

    // Handle paginated shorthand URLs: /slug/page/N/
    // e.g. /insights/page/2/ → /category/insights/page/2/
    //      /press-release/page/2/ → /tag/press-release/page/2/
    if (preg_match('#^([^/]+)/page/(\d+)/?$#', $request, $matches)) {
        $slug = $matches[1];
        $page = $matches[2];

        // Check if slug is a category
        $category = get_term_by('slug', $slug, 'category');
        if ($category && ! is_wp_error($category)) {
            wp_redirect(get_category_link($category) . 'page/' . $page . '/', 301);
            exit;
        }

        // Check if slug is a tag
        $tag = get_term_by('slug', $slug, 'post_tag');
        if ($tag && ! is_wp_error($tag)) {
            wp_redirect(get_tag_link($tag) . 'page/' . $page . '/', 301);
            exit;
        }

        // No match — force 404
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        return;
    }

    // Only act on single-segment URLs (no slashes in the request path)
    if (strpos($request, '/') !== false) return;

    // Skip empty requests and known taxonomy bases
    if (empty($request) || $request === $category_base) return;

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
