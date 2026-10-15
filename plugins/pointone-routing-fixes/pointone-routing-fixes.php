<?php

/**
 * Plugin Name: Point One Nav - Routing Fixes
 * Description: Fixes URL edge cases caused by the /%category%/%postname%/ permalink structure. Category and tag archives work at short URLs without /category/ or /tag/ (e.g. /insights/, /product-announcements/), including pagination (/insights/page/2/). /tag/slug/ redirects to /slug/, Yoast canonical and Open Graph URLs match, and unknown slugs return a proper 404.
 * Version:     1.5.2
 * Author:      Point One Navigation
 */

if (! defined('ABSPATH')) exit;

/*--------------------------------------------------------------
HELPER: CPT ARCHIVE SLUGS
--------------------------------------------------------------*/

// Archive slugs for every post type with an archive (e.g. events). Uses the
// rewrite slug when set, because that's what appears in the URL, not the
// internal post type name. New post types are picked up automatically.
function pointone_get_cpt_archive_slugs()
{
    $slugs      = [];
    $post_types = get_post_types(['has_archive' => true], 'objects');

    foreach ($post_types as $post_type) {
        $archive = $post_type->has_archive;

        if ($archive === true) {
            $slugs[] = (! empty($post_type->rewrite) && ! empty($post_type->rewrite['slug']))
                ? $post_type->rewrite['slug']
                : $post_type->name;
        } else {
            $slugs[] = $archive;
        }
    }

    return $slugs;
}

/*--------------------------------------------------------------
1. RESOLVE SHORT CATEGORY / TAG URLS
--------------------------------------------------------------*/

// With /%category%/%postname%/ permalinks, WordPress treats any one-segment URL
// as a category. This filter checks what the slug really is and sets the query
// so the right archive renders at the short URL, with no redirect.
// Priority: pages/CPTs, then categories, then tags.
//
//   /insights/                     category archive
//   /insights/page/2/              category archive, page 2
//   /product-announcements/        tag archive
//   /product-announcements/page/2/ tag archive, page 2
//
// WordPress has no rewrite rule for "short slug + /page/N/", so paginated URLs
// are matched from the raw request path instead of the parsed query vars.
add_filter('request', function ($query_vars) {
    if (is_admin()) return $query_vars;

    // --- Paginated shorthand: resolve directly from the raw request path ---
    $raw_request = isset($_SERVER['REQUEST_URI'])
        ? trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/')
        : '';

    if ($raw_request && preg_match('#^([^/]+)/page/(\d+)/?$#', $raw_request, $page_match)) {
        $paged_slug = $page_match[1];
        $paged      = (int) $page_match[2];

        $category = get_term_by('slug', $paged_slug, 'category');
        $tag      = (! $category || is_wp_error($category))
            ? get_term_by('slug', $paged_slug, 'post_tag')
            : null;

        if (($category && ! is_wp_error($category)) || ($tag && ! is_wp_error($tag))) {
            // Replace whatever WordPress guessed with the confirmed term.
            unset($query_vars['name'], $query_vars['pagename'], $query_vars['category_name'], $query_vars['tag'], $query_vars['attachment']);
            $query_vars['paged'] = $paged;

            if ($category && ! is_wp_error($category)) {
                $query_vars['category_name'] = $paged_slug;
            } else {
                $query_vars['tag'] = $paged_slug;
            }

            return $query_vars;
        }
        // Not a known category or tag; section 3 will 404 it.
    }

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
    }

    return $query_vars;
});

/*--------------------------------------------------------------
2. REDIRECT /tag/slug/ TO /slug/
--------------------------------------------------------------*/

// Makes the short URL the only canonical address for tag archives (301).
//   /tag/product-announcements/page/2/  ->  /product-announcements/page/2/
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
}, 1); // Priority 1 so it runs before other template_redirect hooks

/*--------------------------------------------------------------
3. 404 UNKNOWN PAGINATED SLUGS
--------------------------------------------------------------*/

// Safety net for /slug/page/N/. Real categories, tags, CPT archives (e.g.
// /events/page/2/) and the Posts Page (/blog/page/2/) are left alone; anything
// else (e.g. /bad-link/page/2/) returns a 404.
add_action('wp', function () {
    $request = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    if (! preg_match('#^([^/]+)/page/(\d+)/?$#', $request, $matches)) return;

    $slug = $matches[1];

    // Leave CPT archive pagination alone
    if (in_array($slug, pointone_get_cpt_archive_slugs(), true)) return;

    // Leave Posts Page pagination alone
    $posts_page_id = (int) get_option('page_for_posts');
    if ($posts_page_id) {
        $posts_page = get_post($posts_page_id);
        if ($posts_page && $posts_page->post_name === $slug) return;
    }

    // Tags and categories are handled by section 1
    $tag = get_term_by('slug', $slug, 'post_tag');
    if ($tag && ! is_wp_error($tag)) return;

    $category = get_term_by('slug', $slug, 'category');
    if ($category && ! is_wp_error($category)) return;

    // No match — force 404
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
});

/*--------------------------------------------------------------
4. 404 UNKNOWN SINGLE-SEGMENT URLS
--------------------------------------------------------------*/

// Safety net for one-segment URLs. Pages, posts, CPT archives, tags (section 1)
// and real categories pass through. An existing category with no posts shows
// its normal "nothing found" template instead of a 404. Everything else 404s.
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

/*--------------------------------------------------------------
5. YOAST CANONICAL AND OPEN GRAPH URLS
--------------------------------------------------------------*/

// get_tag_link() and get_category_link() always return the /tag/ or /category/
// URL, so without this Yoast would point the canonical away from the short URL
// the page actually renders at. Returns null when not on a tag/category archive.
function pointone_shorthand_canonical_url()
{
    if (! is_tag() && ! is_category()) return null;

    $term = get_queried_object();
    if (! $term || is_wp_error($term)) return null;

    $paged = get_query_var('paged');

    return ($paged > 1)
        ? home_url('/' . $term->slug . '/page/' . $paged . '/')
        : home_url('/' . $term->slug . '/');
}

add_filter('wpseo_canonical', function ($canonical) {
    $shorthand = pointone_shorthand_canonical_url();
    return $shorthand ?? $canonical;
});

add_filter('wpseo_opengraph_url', function ($url) {
    $shorthand = pointone_shorthand_canonical_url();
    return $shorthand ?? $url;
});
