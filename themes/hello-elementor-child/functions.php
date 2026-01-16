<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// Enqueue parent and child theme styles, plus modular custom CSS
function my_child_theme_styles() {
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
function remove_css_js_versioning($src) {
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
add_filter( 'body_class', function( $classes ) {
    if ( is_front_page() || is_page('homepage-components') ) {
        $classes[] = 'has-transparent-header';
    }
    return $classes;
} );


function enqueue_slick_carousel() {
    wp_enqueue_style( 'slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css' );
    wp_enqueue_script( 'slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), null, true );
}
add_action( 'wp_enqueue_scripts', 'enqueue_slick_carousel' );

function render_elementor_template($atts) {
  if (!isset($atts['id'])) return '';
  return Elementor\Plugin::instance()->frontend->get_builder_content_for_display($atts['id']);
}
add_shortcode('render-template', 'render_elementor_template');

// Filters out additional query parameters from the Search box
add_action('pre_get_posts', function($query) {
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
add_filter('get_the_excerpt', function($excerpt, $post) {
    if (empty($post->post_excerpt)) {
        $content = strip_shortcodes($post->post_content);
        $content = wp_strip_all_tags($content);
        $words = explode(' ', $content);
        $trimmed = array_slice($words, 0, 30);
        $excerpt = implode(' ', $trimmed) . '…';
    }
    return $excerpt;
}, 10, 2);


function lynzaddy_enqueue_swiper_css() {
  wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), null);
}
add_action('wp_enqueue_scripts', 'lynzaddy_enqueue_swiper_css');

// Function for MapBox Code on States Pages
function render_mapbox_state_div() {
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
      style="width: 100%; height: 500px;"
    ></div>
  <?php
  return ob_get_clean();
}
add_shortcode('state_map_div', 'render_mapbox_state_div');

function render_competitor_table_for_state() {
    // ACF relationship field on the current State post
    $competitors = get_field( 'competitors' );

    if ( ! $competitors || ! is_array( $competitors ) ) {
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
        'Automated Ref. Station Assoc.' => ['acf' => 'automated_reference_station_association','polaris' => 'Yes'],
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
                        <?php foreach ( $competitors as $comp ) : ?>
                            <th><?php echo esc_html( get_the_title( $comp ) ); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $label => $meta ) : ?>
                        <tr>
                            <td class="sticky-col"><?php echo esc_html( $label ); ?></td>
                            <td class="sticky-col"><?php echo esc_html( $meta['polaris'] ); ?></td>
                            <?php foreach ( $competitors as $comp ) : ?>
                                <td>
                                    <?php
                                    $value = get_field( $meta['acf'], $comp->ID );
                                    echo esc_html( $value ?: '—' );
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
add_shortcode( 'competitor_table', 'render_competitor_table_for_state' );

// === FAQ Accordion Shortcode ===
function render_state_faq_accordion() {
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
    <!-- Enzuzo Consent Mode + Cookie Bar (EARLY LOAD) -->
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}

      gtag('consent', 'default', {
        'ad_storage': 'denied',
        'ad_user_data': 'denied',
        'ad_personalization': 'denied',
        'analytics_storage': 'denied',
        'personalization_storage': 'denied',
        'functionality_storage': 'granted',
        'security_storage': 'granted',
        'wait_for_update': 500
      });

      window.__enzuzo = window.__enzuzo || {};
      window.__enzuzo.consentMode = window.__enzuzo.consentMode || {};
      window.__enzuzo.consentMode.gtagScriptVersion = 1;
    </script>

    <script src="https://app.enzuzo.com/scripts/cookiebar/1c73a6f4-f1a5-11f0-9d9b-efb8bced1ecb" async></script>
    <?php
}, 0);

