<?php

/**
 * Plugin Name: Point One Nav - Routing Fixes
 * Description: Corrects WordPress URL resolution edge cases for /%category%/%postname%/ permalink structure. Tags and categories resolve at shorthand URLs (e.g. /product-announcements/, /insights/) without /tag/ or /category/ prefixes. Canonical URLs, Yoast SEO meta, and pagination are all handled correctly. Unrecognised slugs 404.
 * Version:     1.4.0
 * Author:      Point One Nav
 */

if (! defined('ABSPATH')) exit;

// =============================================================================
// HELPER
// =============================================================================

/**
 * Returns an array of all registered CPT archive slugs.
 * Dynamic — automatically picks up any new CPTs without plugin changes.
 */
function pointone_get_cpt_archive_slugs()
{
    $slugs      = [];
    $post_types = get_post_types(['has_archive' => true], 'objects');

    foreach ($post_types as $post_type) {
        $archive = $post_type->has_archive;
        $slugs[] = ($archive === true) ? $post_type->name : $archive;
    }

    return $slugs;
}

// =============================================================================
// 1. REQUEST FILTER — Resolve tag shorthand URLs in place
// =============================================================================

/**
 * Intercepts WordPress's query vars before the main query runs.
 *
 * With /%category%/%postname%/ permalinks, WordPress sets category_name for
 * any single-segment URL. If that slug isn't a real category but IS a tag,
 * we rewrite the query vars to treat it as a tag archive — no redirect needed.
 *
 * Priority order: Pages/CPTs > Categories > Tags
 *
 * This handles both single-segment and paginated shorthand tag URLs:
 *   /product-announcements/        → renders as tag archive in place
 *   /product-announcements/page/2/ → renders as paginated tag archive in place
 */
add_filter('request', function ($query_vars) {
    if (is_admin()) return $query_vars;
    if (! isset($query_vars['category_name'])) return $query_vars;

    $slug = $query_vars['category_name'];

    // Only handle simple slugs — don't interfere with category hierarchy paths
    if (strpos($slug, '/') !== false) return $query_vars;

    // Real category — leave query vars alone, WordPress handles it natively
    $category = get_term_by('slug', $slug, 'category');
    if ($category && ! is_wp_error($category)) return $query_vars;

    // Matches a tag — rewrite to tag archive, preserving any pagination
    $tag = get_term_by('slug', $slug, 'post_tag');
    if ($tag && ! is_wp_error($tag)) {
        unset($query_vars['category_name']);
        $query_vars['tag'] = $slug;
        // paged is already present in query_vars if WordPress parsed /page/N/
    }

    return $query_vars;
});

// =============================================================================
// 2. REDIRECT /tag/slug/ → /slug/ (make shorthand the canonical)
// =============================================================================

/**
 * Any URL using the explicit /tag/ prefix redirects to the shorthand version.
 * This makes /slug/ the canonical URL for all tag archives.
 *
 *   /tag/product-announcements/        → 301 → /product-announcements/
 *   /tag/product-announcements/page/2/ → 301 → /product-announcements/page/2/
 */
add_action('template_redirect', function () {
    if (! is_tag()) return;

    $tag_base = get_option('tag_base') ?: 'tag';
    $request  = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    // Only redirect if the URL is using the explicit /tag/ prefix
    if (strpos($request, $tag_base . '/') !== 0) return;

    $tag = get_queried_object();
    if (! $tag || is_wp_error($tag)) return;

    $paged = get_query_var('paged');

    if ($paged > 1) {
        $redirect = home_url('/' . $tag->slug . '/page/' . $paged . '/');
    } else {
        $redirect = home_url('/' . $tag->slug . '/');
    }

    wp_redirect($redirect, 301);
    exit;
}, 1); // Priority 1 — fires before other template_redirect hooks

// =============================================================================
// 3. WP ACTION — Handle paginated category shorthand and 404s
// =============================================================================

/**
 * Intercepts /slug/page/N/ URLs before templates load.
 *
 * Tag paginated shorthand (/product-announcements/page/2/) is handled in
 * place by the request filter above and needs no redirect here.
 *
 * Category paginated shorthand (/insights/page/2/) is redirected to the
 * canonical /category/insights/page/2/ URL since WordPress's rewrite rules
 * don't natively resolve child category slugs without the /category/ prefix.
 *
 *   /insights/page/2/              → 301 → /category/insights/page/2/
 *   /product-announcements/page/2/ → left alone (handled by request filter)
 *   /events/page/2/                → left alone (CPT archive)
 *   /blog/page/2/                  → left alone (Posts Page)
 *   /bad-link/page/2/              → 404
 */
add_action('wp', function () {
    $request = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    if (! preg_match('#^([^/]+)/page/(\d+)/?$#', $request, $matches)) return;

    $slug = $matches[1];
    $page = $matches[2];

    // Leave CPT archive pagination alone
    if (in_array($slug, pointone_get_cpt_archive_slugs(), true)) return;

    // Leave Posts Page pagination alone
    $posts_page_id = (int) get_option('page_for_posts');
    if ($posts_page_id) {
        $posts_page = get_post($posts_page_id);
        if ($posts_page && $posts_page->post_name === $slug) return;
    }

    // Leave tag pagination alone — handled in place by the request filter
    $tag = get_term_by('slug', $slug, 'post_tag');
    if ($tag && ! is_wp_error($tag)) return;

    // Category paginated shorthand — redirect to canonical paginated URL
    $category = get_term_by('slug', $slug, 'category');
    if ($category && ! is_wp_error($category)) {
        wp_redirect(get_category_link($category) . 'page/' . $page . '/', 301);
        exit;
    }

    // No match — force 404
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
});

// =============================================================================
// 4. TEMPLATE REDIRECT — Single-segment 404 catch-all
// =============================================================================

/**
 * Final safety net for single-segment URLs that don't resolve to anything real.
 *
 * At this point:
 *   - Tags have been handled in place by the request filter (is_tag() = true)
 *   - Pages, posts, CPTs, home, front page are all real WordPress content
 *   - Valid categories with posts are already resolved by WordPress
 *   - Valid categories with no posts are left to show "nothing found" gracefully
 *   - Everything else → 404
 */
add_action('template_redirect', function () {
    $category_base = get_option('category_base') ?: 'category';
    $request       = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    // Only act on single-segment URLs
    if (strpos($request, '/') !== false) return;

    // Skip empty requests and known taxonomy bases
    if (empty($request) || $request === $category_base) return;

    // Don't interfere with real WordPress content
    if (is_page() || is_single() || is_front_page() || is_home() || is_post_type_archive() || is_tag()) return;

    // Valid category with posts — leave alone
    if (is_category() && have_posts()) return;

    // Valid category with no posts — leave alone to show "nothing found" gracefully
    $category = get_term_by('slug', $request, 'category');
    if ($category && ! is_wp_error($category)) return;

    // Nothing matched — force 404
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
});

// =============================================================================
// 5. YOAST SEO — Canonical and OG URL corrections for tag archives
// =============================================================================

/**
 * Rewrites Yoast's canonical URL for tag archives to the shorthand version.
 * Without this, Yoast would output /tag/slug/ as canonical even when the
 * page is rendering at /slug/.
 */
add_filter('wpseo_canonical', function ($canonical) {
    if (! is_tag()) return $canonical;

    $tag = get_queried_object();
    if (! $tag || is_wp_error($tag)) return $canonical;

    $paged = get_query_var('paged');

    return ($paged > 1)
        ? home_url('/' . $tag->slug . '/page/' . $paged . '/')
        : home_url('/' . $tag->slug . '/');
});

/**
 * Rewrites Yoast's Open Graph URL for tag archives to the shorthand version.
 * Keeps OG:URL consistent with the canonical.
 */
add_filter('wpseo_opengraph_url', function ($url) {
    if (! is_tag()) return $url;

    $tag = get_queried_object();
    if (! $tag || is_wp_error($tag)) return $url;

    $paged = get_query_var('paged');

    return ($paged > 1)
        ? home_url('/' . $tag->slug . '/page/' . $paged . '/')
        : home_url('/' . $tag->slug . '/');
});
