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
    $css_dir = get_stylesheet_directory() . '/css/';
    $css_uri = $theme_uri . '/css/';

    // Enqueue each partial with its own filemtime version (instead of CSS @import, which
    // can't be versioned and gets cached for a year). Deps are chained to keep the order.
    $enqueue_group = function ($group, $files, $deps) use ($css_dir, $css_uri) {
        $prev = $deps;
        foreach ($files as $file) {
            $handle = 'pointone-' . sanitize_title(str_replace(array('/', '.css'), array('-', ''), $file));
            wp_enqueue_style($handle, $css_uri . $file, $prev, filemtime($css_dir . $file));
            $prev = array($handle);
        }
        wp_enqueue_style($group . '-css', $css_uri . $group . '.css', $prev, filemtime($css_dir . $group . '.css'));
        return array($group . '-css');
    };

    $last = $enqueue_group('base', array(
        'base/color.css',
        'base/typography.css',
        'base/component.css',
        'base/link.css',
        'base/blog-post.css',
    ), array('child-style'));

    $last = $enqueue_group('layout', array(
        'layout/footer.css',
        'layout/header-desktop.css',
        'layout/header-mobile.css',
        'layout/sections.css',
        'layout/blog.css',
        'layout/retire-with-components.css',
    ), $last);

    $last = $enqueue_group('components', array(
        'atoms/buttons.css',
        'atoms/icon.css',
        'atoms/image.css',
        'atoms/floating-icon.css',
        'molecules/banner-logo.css',
        'molecules/card-avatar.css',
        'molecules/cards.css',
        'molecules/custom-slider.css',
        'molecules/feature-container.css',
        'molecules/pricing-toggle.css',
        'molecules/text-feature.css',
        'molecules/single-image-sections.css',
        'molecules/text-widget.css',
        'organisms/section-image-split-feature.css',
        'organisms/homepage-hero.css',
        'organisms/rtk-comparison-table.css',
        'organisms/faq-section.css',
    ), $last);

    $last = $enqueue_group('utilities', array(
        'utilities/helpers.css',
        'utilities/hubspot.css',
        'utilities/inverse.css',
        'utilities/text-align.css',
        'utilities/site-alert-bar.css',
        'utilities/ketch.css',
    ), $last);

    wp_enqueue_style('elementor-css', $css_uri . 'elementor.css', $last, filemtime($css_dir . 'elementor.css'));
}
add_action('wp_enqueue_scripts', 'my_child_theme_styles', 20);

// Enqueue JS for sticky header or other behavior
// function my_child_theme_scripts() {
//     $theme_uri = get_stylesheet_directory_uri();
//     wp_enqueue_script('sticky-header', $theme_uri . '/js/header-sticky.js', array(), filemtime(get_stylesheet_directory() . '/js/header-sticky.js'), true);
// }
// add_action('wp_enqueue_scripts', 'my_child_theme_scripts', 20);


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

/**
 * Read current per-device plan prices from the Point One Global Variables ACF
 * options page. Do not silently display obsolete prices if a value is missing.
 */
function pointone_state_table_price($field_name)
{
    if (! function_exists('get_field')) {
        return null;
    }

    $price = get_field($field_name, 'option');
    return is_numeric($price) && (float) $price > 0 ? (float) $price : null;
}

/** Preserve cents when present, without adding .00 to whole-dollar prices. */
function pointone_state_table_format_amount($amount)
{
    $decimals = (floor($amount) == $amount) ? 0 : 2;
    return number_format($amount, $decimals, '.', ',');
}

/** Format a plan's monthly and annual costs for the State comparison table. */
function pointone_state_table_price_text($monthly, $annual)
{
    if ($monthly === null || $annual === null) {
        return 'See pricing';
    }

    return sprintf(
        '$%s/mo or $%s/yr ($%s/mo billed annually)',
        pointone_state_table_format_amount($monthly),
        pointone_state_table_format_amount($annual),
        number_format($annual / 12, 2)
    );
}

/** Normalize the legacy ACF provider values without changing their stored data. */
function pointone_state_table_competitor_value($value)
{
    if ($value === null || $value === false || $value === '') {
        return '—';
    }

    $value = trim((string) $value);
    if ($value === '') {
        return '—';
    }

    if (strcasecmp($value, 'Y') === 0 || strcasecmp($value, 'Yes') === 0) {
        return 'Yes';
    }
    if (strcasecmp($value, 'N') === 0 || strcasecmp($value, 'No') === 0) {
        return 'No';
    }

    // Known typos in the old State competitor table data.
    if (strcasecmp($value, 'Not. reported') === 0) {
        return 'Not reported';
    }
    if (strcasecmp($value, 'Free to us') === 0) {
        return 'Free to use';
    }

    return $value;
}

function render_competitor_table_for_state()
{
    // Competitors are still managed independently for each State in ACF.
    $competitors = get_field('competitors');
    if (! is_array($competitors)) {
        $competitors = [];
    }

    $state_slug = strtolower((string) get_post_field('post_name', get_the_ID()));
    $virtual_available = ! in_array($state_slug, ['hawaii', 'alaska'], true);

    $true_monthly    = pointone_state_table_price('price_true_monthly');
    $true_annual     = pointone_state_table_price('price_true_annual');
    $virtual_monthly = pointone_state_table_price('price_virtual_monthly');
    $virtual_annual  = pointone_state_table_price('price_virtual_annual');

    $rows = [
        'Cost per device, per month'      => [
            'acf' => 'cost_per_month',
            'true' => pointone_state_table_price_text($true_monthly, $true_annual),
            'virtual' => $virtual_available
                ? pointone_state_table_price_text($virtual_monthly, $virtual_annual)
                : 'Not available in this state',
        ],
        'Accuracy'                        => ['acf' => 'accuracy', 'true' => '1–3 cm', 'virtual' => '~10 cm'],
        'Total physical base stations'    => ['acf' => 'total_physical_base_stations', 'true' => '4,000+', 'virtual' => '4,000+'],
        'Uptime'                          => ['acf' => 'uptime', 'true' => '99.9%', 'virtual' => '99.9%'],
        'Coverage'                        => ['acf' => 'coverage', 'true' => 'International', 'virtual' => 'Contiguous US, UK, EU'],
        'Global coverage'                 => ['acf' => 'global_coverage', 'true' => 'No (regional availability)', 'virtual' => 'No (regional availability)'],
        'GraphQL API'                     => ['acf' => 'graphql_api', 'true' => 'Yes', 'virtual' => 'Yes'],
        'Automatic base station assignment' => ['acf' => 'automated_reference_station_association', 'true' => 'Yes', 'virtual' => 'Yes'],
        'GNSS Frequency Bands'            => ['acf' => 'gnss_frequency_bands_supported', 'true' => 'L1, L2, L5', 'virtual' => 'L1, L2, L5'],
        'True RTK'                        => ['acf' => 'true_rtk', 'true' => 'Single baseline and Network RTK', 'virtual' => 'No (SSR-modeled, RTCM output)'],
    ];

    ob_start(); ?>
    <div class="competitor-table-outer">
        <div class="competitor-table-wrapper">
            <table class="competitor-table">
                <thead>
                    <tr>
                        <th scope="col" class="sticky-col">Brand</th>
                        <th scope="col" class="sticky-col">Point One RTK Network – True RTK</th>
                        <th scope="col" class="pointone-plan-virtual">Point One RTK Network – Virtual RTK</th>
                        <?php foreach ($competitors as $comp) : ?>
                            <th scope="col"><?php echo esc_html(get_the_title($comp)); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $label => $meta) : ?>
                        <tr>
                            <th scope="row" class="sticky-col"><?php echo esc_html($label); ?></th>
                            <td class="sticky-col"><?php echo esc_html($meta['true']); ?></td>
                            <td class="pointone-plan-virtual"><?php echo esc_html($meta['virtual']); ?></td>
                            <?php foreach ($competitors as $comp) : ?>
                                <td><?php echo esc_html(pointone_state_table_competitor_value(get_field($meta['acf'], $comp->ID))); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (! $virtual_available) : ?>
        <p class="competitor-table-availability">Virtual RTK is not available in <?php echo esc_html(get_the_title()); ?>. True RTK is the available Point One product.</p>
    <?php endif; ?>
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

// Ensure the 192x192 favicon appears before the 32x32 in <head>
// so Google sees a qualifying size first
remove_action('wp_head', 'wp_site_icon', 99);
add_action('wp_head', function () {
    $icon_32  = get_site_icon_url(32);
    $icon_192 = get_site_icon_url(192);
    if ($icon_192) {
        echo '<link rel="icon" href="' . esc_url($icon_192) . '" sizes="192x192" />' . "\n";
    }
    if ($icon_32) {
        echo '<link rel="icon" href="' . esc_url($icon_32) . '" sizes="32x32" />' . "\n";
    }
    // Apple touch icon
    $icon_180 = get_site_icon_url(180);
    if ($icon_180) {
        echo '<link rel="apple-touch-icon" href="' . esc_url($icon_180) . '" />' . "\n";
    }
}, 99);
