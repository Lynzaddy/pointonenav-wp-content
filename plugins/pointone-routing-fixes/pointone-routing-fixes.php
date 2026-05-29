<?php

/**
 * Plugin Name: Point One Nav - Routing Fixes
 * Description: Corrects WordPress URL resolution edge cases for /%category%/%postname%/ permalink structure. Shorthand single-segment URLs (e.g. /case-studies/, /press-release/) are routed to their matching category or tag archive. Paginated shorthand URLs (e.g. /insights/page/2/) are also handled. Custom post type archives and real WordPress content are left untouched. Unrecognised slugs 404.
 * Version:     1.3.5
 * Author:      Point One Nav
 */

if (! defined('ABSPATH')) exit;

/**
 * Helper: get all registered CPT archive slugs.
 * Returns an array of slugs like ['events', 'gnss-terms'].
 */
function pointone_get_cpt_archive_slugs()
{
    $slugs      = [];
    $post_types = get_post_types(['has_archive' => true], 'objects');

    foreach ($post_types as $post_type) {
        $archive = $post_type->has_archive;
        // has_archive can be true (uses the post type name) or a custom string
        $slugs[] = ($archive === true) ? $post_type->name : $archive;
    }

    return $slugs;
}

/**
 * Handle paginated shorthand URLs: /slug/page/N/
 *
 * Fires on the `wp` action. We check the raw request path directly rather
 * than relying on is_404(), because WordPress may resolve /slug/page/N/
 * ambiguously under /%category%/%postname%/ permalinks and not reliably
 * mark it as a 404 at this hook point.
 *
 *   /insights/page/2/      → /category/insights/page/2/
 *   /press-release/page/2/ → /tag/press-release/page/2/
 *   /events/page/2/        → left alone (CPT archive)
 *   /bad-link/page/2/      → 404
 */
add_action('wp', function () {
    $request = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    if (! preg_match('#^([^/]+)/page/(\d+)/?$#', $request, $matches)) return;

    $slug = $matches[1];
    $page = $matches[2];

    // Leave CPT archive pagination alone
    if (in_array($slug, pointone_get_cpt_archive_slugs(), true)) return;

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
});

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
 *   1. Leaves all real WordPress content alone (pages, posts, front page, blog home, CPT archives)
 *   2. If the slug matches a tag → 301 redirect to /tag/slug/
 *   3. If the slug matches nothing → 404
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
    if (is_page() || is_single() || is_front_page() || is_home() || is_post_type_archive()) return;

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
