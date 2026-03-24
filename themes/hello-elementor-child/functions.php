<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Enqueue parent and child theme styles, plus modular custom CSS
function my_child_theme_styles()
{
    // Load parent theme style
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');

    // Load child theme style (typically used for theme metadata or last-resort overrides)
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'), filemtime(get_stylesheet_directory() . '/style.css'));

    // Modular CSS (from /css/ folder inside your child theme)
    $theme_uri = get_stylesheet_directory_uri();

    wp_enqueue_style('reset-css', $theme_uri . '/css/reset.css', array('child-style'), filemtime(get_stylesheet_directory() . '/css/reset.css'));
    wp_enqueue_style('base-css', $theme_uri . '/css/base.css', array('child-style'), filemtime(get_stylesheet_directory() . '/css/base.css'));
    wp_enqueue_style('layout-css', $theme_uri . '/css/layout.css', array('child-style'), filemtime(get_stylesheet_directory() . '/css/layout.css'));
    wp_enqueue_style('components-css', $theme_uri . '/css/components.css', array('child-style'), filemtime(get_stylesheet_directory() . '/css/components.css'));
    wp_enqueue_style('utilities-css', $theme_uri . '/css/utilities.css', array('child-style'), filemtime(get_stylesheet_directory() . '/css/utilities.css'));
    wp_enqueue_style('elementor-css', $theme_uri . '/css/elementor.css', array('child-style'), filemtime(get_stylesheet_directory() . '/css/elementor.css'));
}
add_action('wp_enqueue_scripts', 'my_child_theme_styles', 20);

// Enqueue JS for sticky header or other behavior
// function my_child_theme_scripts() {
//     $theme_uri = get_stylesheet_directory_uri();
//     wp_enqueue_script('sticky-header', $theme_uri . '/js/header-sticky.js', array(), filemtime(get_stylesheet_directory() . '/js/header-sticky.js'), true);
// }
// add_action('wp_enqueue_scripts', 'my_child_theme_scripts', 20);


// Remove ?ver query strings from all enqueued CSS and JS (including Elementor)
function remove_css_js_versioning($src)
{
    // Only strip ver from files NOT in your child theme
    if (strpos($src, get_stylesheet_directory_uri()) !== false) {
        return $src; // leave child theme files alone
    }
    if (strpos($src, '?ver=') !== false) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
add_filter('style_loader_src', 'remove_css_js_versioning', 9999);
add_filter('script_loader_src', 'remove_css_js_versioning', 9999);


// Disable Elementor CSS file cache (forces regeneration on every load)
add_filter('elementor/css-file/enable_cache_busting', '__return_true');
add_filter('elementor/css-file/post/enable_cache_busting', '__return_true');

// Page indicator as to which pages need the transparent header (Currently just the homepage)
add_filter('body_class', function ($classes) {
    if (is_front_page() || is_page('homepage-components')) {
        $classes[] = 'has-transparent-header';
    }
    return $classes;
});


function enqueue_slick_carousel()
{
    wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
    wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_slick_carousel');

function render_elementor_template($atts)
{
    if (!isset($atts['id'])) return '';
    return Elementor\Plugin::instance()->frontend->get_builder_content_for_display($atts['id']);
}
add_shortcode('render-template', 'render_elementor_template');

// Filters out additional query parameters from the Search box
add_action('pre_get_posts', function ($query) {
    if (!is_admin() && $query->is_main_query() && $query->is_search()) {
        // Remove e_search_props from the query vars
        if (isset($query->query_vars['e_search_props'])) {
            unset($query->query_vars['e_search_props']);
        }

        // Ensure only 's' is used
        if (isset($_GET['s'])) {
            $query->set('s', sanitize_text_field($_GET['s']));
        }
    }
});

// Trim Excerpt function to limit the length of excerpts
add_filter('get_the_excerpt', function ($excerpt, $post) {
    if (empty($post->post_excerpt)) {
        $content = strip_shortcodes($post->post_content);
        $content = wp_strip_all_tags($content);
        $words = explode(' ', $content);
        $trimmed = array_slice($words, 0, 30);
        $excerpt = implode(' ', $trimmed) . '…';
    }
    return $excerpt;
}, 10, 2);


function lynzaddy_enqueue_swiper_css()
{
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), null);
}
add_action('wp_enqueue_scripts', 'lynzaddy_enqueue_swiper_css');

// Function for MapBox Code on States Pages
function render_mapbox_state_div()
{
    $lat = get_field('latitude');
    $lng = get_field('longitude');
    $state = get_the_title();

    if (!$lat || !$lng) {
        return '<p style="color: red;">Missing coordinates for this state.</p>';
    }

    ob_start();
?>
    <div
        id="map"
        data-lat="<?php echo esc_attr($lat); ?>"
        data-lng="<?php echo esc_attr($lng); ?>"
        data-state="<?php echo esc_attr($state); ?>"
        style="width: 100%; height: 500px;"></div>
<?php
    return ob_get_clean();
}
add_shortcode('state_map_div', 'render_mapbox_state_div');

function render_competitor_table_for_state()
{
    // ACF relationship field on the current State post
    $competitors = get_field('competitors');

    if (! $competitors || ! is_array($competitors)) {
        return '<p>No competitor data available for this state.</p>';
    }

    /* Polaris hard-coded column */
    $rows = [
        'Cost per month'                => ['acf' => 'cost_per_month',                       'polaris' => '$150'],
        'Accuracy'                      => ['acf' => 'accuracy',                             'polaris' => '1 cm'],
        'Total physical base stations'  => ['acf' => 'total_physical_base_stations',         'polaris' => '> 1440'],
        'Uptime'                        => ['acf' => 'uptime',                               'polaris' => '99.99 %'],
        'Coverage'                      => ['acf' => 'coverage',                             'polaris' => 'International'],
        'Global coverage'               => ['acf' => 'global_coverage',                      'polaris' => 'Yes'],
        'GraphQL API'                   => ['acf' => 'graphql_api',                          'polaris' => 'Yes'],
        'Automated Ref. Station Assoc.' => ['acf' => 'automated_reference_station_association', 'polaris' => 'Yes'],
        'GNSS Frequency Bands'          => ['acf' => 'gnss_frequency_bands_supported',       'polaris' => 'L1, L2, L5'],
        'True RTK'                      => ['acf' => 'true_rtk',                             'polaris' => 'Yes'],
    ];

    ob_start(); ?>
    <div class="competitor-table-outer"><!-- NEW scroll container -->
        <div class="competitor-table-wrapper"><!-- width only -->
            <table class="competitor-table">
                <thead>
                    <tr>
                        <th class="sticky-col">Brand</th>
                        <th class="sticky-col">Polaris</th>
                        <?php foreach ($competitors as $comp) : ?>
                            <th><?php echo esc_html(get_the_title($comp)); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $label => $meta) : ?>
                        <tr>
                            <td class="sticky-col"><?php echo esc_html($label); ?></td>
                            <td class="sticky-col"><?php echo esc_html($meta['polaris']); ?></td>
                            <?php foreach ($competitors as $comp) : ?>
                                <td>
                                    <?php
                                    $value = get_field($meta['acf'], $comp->ID);
                                    echo esc_html($value ?: '—');
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('competitor_table', 'render_competitor_table_for_state');

// === FAQ Accordion Shortcode ===
function render_state_faq_accordion()
{
    ob_start();

    $faqs = get_field('location_specific_faq');
    $state_name = get_the_title();

    if ($faqs && is_array($faqs)) :
    ?>
        <div class="faq-accordion">
            <?php foreach ($faqs as $index => $faq) :
                $question = $faq['faq_question'] ?? '';
                $answer = $faq['faq_answer'] ?? '';
                if (!$question || !$answer) continue;
            ?>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false" aria-controls="faq-<?php echo $index; ?>">
                        <span class="faq-toggle-indicator" aria-hidden="true"></span>
                        <span class="faq-question-text"><?php echo esc_html($question); ?></span>
                    </button>
                    <div id="faq-<?php echo $index; ?>" class="faq-answer" hidden>
                        <?php echo wp_kses_post($answer); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php
    else :
        echo '<p>No FAQs for ' . esc_html($state_name) . '.</p>';
    endif;

    return ob_get_clean();
}
add_shortcode('state_faq_accordion', 'render_state_faq_accordion');

add_action('wp_head', function () {
    ?>
    <!-- Ketch Consent Manager – EARLY LOAD -->
    <script>
        ! function() {
            window.semaphore = window.semaphore || [];
            window.ketch = function() {
                window.semaphore.push(arguments);
            };

            var e = document.createElement("script");
            e.type = "text/javascript";
            e.src = "https://global.ketchcdn.com/web/v3/config/point_one_nav/website_smart_tag/boot.js";
            e.defer = true;
            e.async = true;

            document.getElementsByTagName("head")[0].appendChild(e);
        }();
    </script>
<?php
}, 0);

/**
 * Sync ACF term_slug to WordPress post_name (slug)
 */
add_action('acf/save_post', function ($post_id) {

    // Only for glossary terms
    if (get_post_type($post_id) !== 'glossary_term') {
        return;
    }

    // Get the custom slug field
    $custom_slug = get_field('term_slug', $post_id);

    if (!$custom_slug) {
        return;
    }

    // Sanitize
    $custom_slug = sanitize_title($custom_slug);

    // Only update if different
    if (get_post_field('post_name', $post_id) !== $custom_slug) {

        // Prevent infinite loop
        remove_action('acf/save_post', __FUNCTION__);

        wp_update_post([
            'ID'        => $post_id,
            'post_name' => $custom_slug,
        ]);

        add_action('acf/save_post', __FUNCTION__);
    }
}, 20);


/**
 * ======================================================
 * GLOSSARY EXCERPT CONFIGURATION
 * ======================================================
 */

/**
 * Get glossary excerpt word count (admin configurable)
 */
function glossary_excerpt_word_count()
{
    return (int) get_option('glossary_excerpt_word_count', 18);
}

/**
 * ======================================================
 * AUTO-GENERATE EXCERPT ON FIRST SAVE
 * ======================================================
 */
add_action('acf/save_post', function ($post_id) {

    if (get_post_type($post_id) !== 'glossary_term') {
        return;
    }

    // Respect manual excerpts
    if (!empty(get_post_field('post_excerpt', $post_id))) {
        return;
    }

    $definition = get_field('term_definition', $post_id);

    if (!$definition) {
        return;
    }

    $excerpt = wp_trim_words(
        wp_strip_all_tags($definition),
        glossary_excerpt_word_count(),
        '…'
    );

    remove_action('acf/save_post', __FUNCTION__);

    wp_update_post([
        'ID'           => $post_id,
        'post_excerpt' => $excerpt,
    ]);

    add_action('acf/save_post', __FUNCTION__);
}, 20);

/**
 * ======================================================
 * SINGLE TERM: REGENERATE EXCERPT BUTTON
 * ======================================================
 */
add_action('post_submitbox_misc_actions', function () {

    global $post;

    if (!$post || $post->post_type !== 'glossary_term') {
        return;
    }

    $url = wp_nonce_url(
        admin_url(
            'admin-post.php?action=regenerate_glossary_excerpt&post_id=' . $post->ID
        ),
        'regenerate_glossary_excerpt_' . $post->ID
    );

    echo '<div class="misc-pub-section">';
    echo '<a href="' . esc_url($url) . '" class="button button-secondary" style="width:100%;text-align:center;">';
    echo 'Regenerate Excerpt';
    echo '</a>';
    echo '</div>';
});

/**
 * Handle single glossary excerpt regeneration
 */
add_action('admin_post_regenerate_glossary_excerpt', function () {

    if (
        empty($_GET['post_id']) ||
        !current_user_can('edit_post', $_GET['post_id'])
    ) {
        wp_die('Unauthorized');
    }

    $post_id = (int) $_GET['post_id'];

    check_admin_referer('regenerate_glossary_excerpt_' . $post_id);

    if (get_post_type($post_id) !== 'glossary_term') {
        wp_die('Invalid post type');
    }

    $definition = get_field('term_definition', $post_id);

    if ($definition) {
        wp_update_post([
            'ID'           => $post_id,
            'post_excerpt' => wp_trim_words(
                wp_strip_all_tags($definition),
                glossary_excerpt_word_count(),
                '…'
            ),
        ]);
    }

    wp_safe_redirect(
        admin_url('post.php?post=' . $post_id . '&action=edit&excerpt_regenerated=1')
    );
    exit;
});

/**
 * ======================================================
 * ADMIN NOTICE (SUCCESS)
 * ======================================================
 */
add_action('admin_notices', function () {

    if (!isset($_GET['excerpt_regenerated'])) {
        return;
    }

    echo '<div class="notice notice-success is-dismissible">';
    echo '<p><strong>Excerpt regenerated successfully.</strong></p>';
    echo '</div>';
});

/**
 * ======================================================
 * ADMIN SETTINGS PAGE (WORD COUNT + BULK REGENERATE)
 * ======================================================
 */
add_action('admin_menu', function () {

    add_submenu_page(
        'edit.php?post_type=glossary_term',
        'Glossary Excerpts',
        'Excerpt Settings',
        'manage_options',
        'glossary-excerpt-settings',
        'render_glossary_excerpt_settings_page'
    );
});

/**
 * Render glossary excerpt settings page
 */
function render_glossary_excerpt_settings_page()
{

    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['glossary_word_count'])) {
        check_admin_referer('save_glossary_excerpt_settings');

        update_option(
            'glossary_excerpt_word_count',
            max(1, (int) $_POST['glossary_word_count'])
        );

        echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
    }

    $word_count = glossary_excerpt_word_count();
?>

    <div class="wrap">
        <h1>Glossary Excerpt Settings</h1>

        <form method="post">
            <?php wp_nonce_field('save_glossary_excerpt_settings'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Excerpt Word Count</th>
                    <td>
                        <input type="number" name="glossary_word_count" value="<?php echo esc_attr($word_count); ?>" min="1" />
                        <p class="description">Controls how many words appear in glossary cards.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Save Settings'); ?>
        </form>

        <hr>

        <h2>Bulk Regenerate All Excerpts</h2>
        <p><strong>Warning:</strong> This will overwrite all glossary excerpts.</p>

        <a href="<?php echo esc_url(
                        wp_nonce_url(
                            admin_url('admin-post.php?action=regenerate_all_glossary_excerpts'),
                            'regenerate_all_glossary_excerpts'
                        )
                    ); ?>" class="button button-primary">
            Regenerate All Glossary Excerpts
        </a>
    </div>

<?php
}

/**
 * ======================================================
 * BULK REGENERATE ALL GLOSSARY EXCERPTS
 * ======================================================
 */
add_action('admin_post_regenerate_all_glossary_excerpts', function () {

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    check_admin_referer('regenerate_all_glossary_excerpts');

    $terms = get_posts([
        'post_type'      => 'glossary_term',
        'posts_per_page' => -1,
        'post_status'    => 'any',
    ]);

    foreach ($terms as $term) {
        $definition = get_field('term_definition', $term->ID);
        if (!$definition) continue;

        wp_update_post([
            'ID'           => $term->ID,
            'post_excerpt' => wp_trim_words(
                wp_strip_all_tags($definition),
                glossary_excerpt_word_count(),
                '…'
            ),
        ]);
    }

    wp_safe_redirect(
        admin_url('edit.php?post_type=glossary_term&page=glossary-excerpt-settings&bulk_done=1')
    );
    exit;
});
