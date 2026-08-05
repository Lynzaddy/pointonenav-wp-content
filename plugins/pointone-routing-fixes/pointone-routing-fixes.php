<?php

/**
 * Plugin Name: Point One Nav - Routing Fixes
 * Description: Corrects WordPress URL resolution edge cases for /%category%/%postname%/ permalink structure. Tags and categories resolve at shorthand URLs (e.g. /product-announcements/, /insights/) without /tag/ or /category/ prefixes, including paginated archives (/insights/page/2/). Canonical URLs, Yoast SEO meta, and pagination are all handled correctly. Unrecognised slugs 404.
 * Version:     1.5.1
 * Author:      Point One Nav
 */

if (! defined('ABSPATH')) exit;

// =============================================================================
// HELPER
// =============================================================================

/**
 * Returns an array of all registered CPT archive slugs.
 * Dynamic — automatically picks up any new CPTs without plugin changes.
 *
 * v1.4.1: When has_archive is `true` (boolean), WordPress core resolves the
 * actual archive slug from the post type's rewrite slug
 * ($post_type->rewrite['slug']), falling back to the post type name only if
 * no rewrite slug is set. This helper previously always used $post_type->name,
 * which is wrong whenever a CPT's internal name differs from its archive
 * slug (e.g. name "case_study" with rewrite slug "case-studies"). That
 * mismatch caused pagination for such archives (/case-studies/page/2/) to be
 * treated as unrecognised and force-404'd by the handler in section 3, even
 * though /case-studies/ itself resolved fine natively.
 */
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

// =============================================================================
// 1. REQUEST FILTER — Resolve category/tag shorthand URLs in place
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
 * --- v1.5.1: resolve shorthand pagination straight from the raw URL --------
 *
 * There is no dedicated rewrite rule for "shorthand archive + pagination"
 * under a /%category%/%postname%/ structure, so a URL like /insights/page/2/
 * either gets mis-parsed by whatever rule WordPress falls back to, or — if
 * nothing matches at all — WordPress never populates category_name/tag/paged
 * in $query_vars in the first place. Either way, by the time this filter
 * runs there's nothing reliable in $query_vars to repair, which is why
 * page 1 and single posts work fine but every paginated shorthand URL
 * (category or tag) 404s.
 *
 * So for the paginated case, skip $query_vars entirely and resolve directly
 * from the raw request path: if the slug before "/page/N/" matches a real
 * category or tag, set the query vars ourselves. This doesn't depend on any
 * assumption about how WordPress's rewrite engine parsed (or failed to
 * parse) the URL.
 *
 * This handles both single-segment and paginated shorthand URLs:
 *   /insights/                → renders as category archive in place
 *   /insights/page/2/         → renders as paginated category archive in place
 *   /product-announcements/   → renders as tag archive in place
 *   /product-announcements/page/2/ → renders as paginated tag archive in place
 */
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
            // Discard whatever (likely incorrect, possibly empty) vars
            // WordPress derived for this URL and set the correct ones
            // directly, since we've just confirmed the real term.
            unset($query_vars['name'], $query_vars['pagename'], $query_vars['category_name'], $query_vars['tag'], $query_vars['attachment']);
            $query_vars['paged'] = $paged;

            if ($category && ! is_wp_error($category)) {
                $query_vars['category_name'] = $paged_slug;
            } else {
                $query_vars['tag'] = $paged_slug;
            }

            return $query_vars;
        }
        // Not a recognised category or tag shorthand — fall through to the
        // page-1 logic below (harmless; category_name likely isn't set for
        // this request anyway) and ultimately to section 3's 404 net.
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
        // paged is already present in query_vars if WordPress parsed /page/N/
        // (the paginated-shorthand branch above already returned early if so)
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
// 3. WP ACTION — Final 404 confirmation for unrecognised paginated slugs
// =============================================================================

/**
 * Intercepts /slug/page/N/ URLs before templates load.
 *
 * Both tag and category paginated shorthand (/product-announcements/page/2/,
 * /insights/page/2/) are now repaired and handled in place by the request
 * filter in section 1 — no redirect needed for either.
 *
 * v1.5.0: this hook previously redirected category pagination to the
 * /category/slug/page/N/ prefix. That's no longer necessary now that the
 * request filter resolves it correctly at the shorthand URL, and doing so
 * would have fought the fix by bouncing working shorthand URLs back to the
 * prefixed form. This hook now only exists as a final safety net that
 * force-404s slugs that don't match anything real.
 *
 *   /insights/page/2/              → left alone (handled by request filter)
 *   /product-announcements/page/2/ → left alone (handled by request filter)
 *   /case-studies/page/2/          → left alone (handled by request filter)
 *   /events/page/2/                → left alone (CPT archive)
 *   /blog/page/2/                  → left alone (Posts Page)
 *   /bad-link/page/2/              → 404
 */
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

    // Leave tag pagination alone — handled in place by the request filter
    $tag = get_term_by('slug', $slug, 'post_tag');
    if ($tag && ! is_wp_error($tag)) return;

    // Leave category pagination alone — handled in place by the request filter
    $category = get_term_by('slug', $slug, 'category');
    if ($category && ! is_wp_error($category)) return;

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
// 5. YOAST SEO — Canonical and OG URL corrections for tag/category archives
// =============================================================================

/**
 * Computes the shorthand canonical URL for the current tag or category
 * archive, preserving pagination.
 *
 * Both get_tag_link() and get_category_link() always return the prefixed
 * URL (/tag/slug/, /category/slug/) regardless of where the page actually
 * rendered. Without this override Yoast would emit that prefixed URL as
 * canonical even when the page is rendering at the shorthand URL — for
 * categories this only became visible once v1.5.0 made shorthand pagination
 * render in place instead of 404ing.
 *
 * Returns null if the current request isn't a tag or category archive.
 */
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

/**
 * Rewrites Yoast's canonical URL for tag and category archives to the
 * shorthand version.
 */
add_filter('wpseo_canonical', function ($canonical) {
    $shorthand = pointone_shorthand_canonical_url();
    return $shorthand ?? $canonical;
});

/**
 * Rewrites Yoast's Open Graph URL for tag and category archives to the
 * shorthand version. Keeps OG:URL consistent with the canonical.
 */
add_filter('wpseo_opengraph_url', function ($url) {
    $shorthand = pointone_shorthand_canonical_url();
    return $shorthand ?? $url;
});
