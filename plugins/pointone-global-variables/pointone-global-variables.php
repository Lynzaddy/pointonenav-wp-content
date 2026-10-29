<?php

/**
 * Plugin Name: Point One Nav - Global Variables
 * Description: Site-wide values (currently plan pricing) edited in one place on an ACF Options Page. Values are available in Elementor through a "Point One" Dynamic Tag, through the [global_var key="..." format="currency"] shortcode, and on the front end as window.pointoneGlobalVars. Also prints the /pricing/ page structured data and the [pointone_pricing_summary] shortcode.
 * Version:     1.4.0
 * Author:      Point One Navigation
 */

if (! defined('ABSPATH')) exit;


/*--------------------------------------------------------------
FIELD DEFINITIONS
--------------------------------------------------------------*/

// Single source of truth. A field added here automatically appears on the
// options page, in the Elementor Dynamic Tag list, in the shortcode and in the
// JS object. Fields are grouped into admin tabs by 'tab'. ACF lays out 50%-width
// fields left to right, so list them in the order they should pair up.

function pointone_gv_field_definitions(): array
{
    return [

        [
            'name'         => 'price_virtual_monthly',
            'label'        => 'Virtual — Monthly',
            'type'         => 'number',
            'tab'          => 'Pricing',
            'instructions' => 'Per-license monthly rate for the Virtual plan. Example: 50',
            'prepend'      => '$',
            'width'        => '50',
        ],

        [
            'name'         => 'price_true_monthly',
            'label'        => 'True — Monthly',
            'type'         => 'number',
            'tab'          => 'Pricing',
            'instructions' => 'Per-license monthly rate for the True plan. Example: 150',
            'prepend'      => '$',
            'width'        => '50',
        ],

        [
            'name'         => 'price_virtual_annual',
            'label'        => 'Virtual — Annual',
            'type'         => 'number',
            'tab'          => 'Pricing',
            'instructions' => 'Total charged per license per year for the Virtual plan. Example: 500',
            'prepend'      => '$',
            'width'        => '50',
        ],

        [
            'name'         => 'price_true_annual',
            'label'        => 'True — Annual',
            'type'         => 'number',
            'tab'          => 'Pricing',
            'instructions' => 'Total charged per license per year for the True plan. Example: 1500',
            'prepend'      => '$',
            'width'        => '50',
        ],

    ];
}


/*--------------------------------------------------------------
PLANS, FORMATTING AND COMPUTED VALUES
--------------------------------------------------------------*/

// The plans sold on /pricing/. Used by the summary shortcode and the structured data.
function pointone_gv_plans(): array
{
    return [
        [
            'name'    => 'Virtual RTK',
            'monthly' => 'price_virtual_monthly',
            'annual'  => 'price_virtual_annual',
            'savings' => 'savings_virtual',
        ],
        [
            'name'    => 'True RTK',
            'monthly' => 'price_true_monthly',
            'annual'  => 'price_true_annual',
            'savings' => 'savings_true',
        ],
    ];
}

// Same rules as pgDollar() in assets/pricing-toggle.js.
// Whole numbers: 1500 -> "$1,500". Decimals: 42.5 -> "$42.50".
function pointone_gv_format_dollar($value): string
{
    if ($value === null || $value === false || $value === '') return '';

    $num      = (float) $value;
    $decimals = floor($num) == $num ? 0 : 2;

    return '$' . number_format($num, $decimals, '.', ',');
}

// Same rules as pgSavings() in assets/pricing-toggle.js. Empty string when it cannot be computed.
function pointone_gv_savings_text($monthly, $annual): string
{
    if (! $monthly || ! $annual) return '';

    $saved = (float) $monthly * 12 - (float) $annual;

    return $saved > 0 ? 'Save ' . pointone_gv_format_dollar($saved) . ' yr/license' : '';
}

// Values calculated from the stored ones. They have no ACF field.
function pointone_gv_computed_definitions(): array
{
    $computed = [];

    foreach (pointone_gv_plans() as $plan) {
        $computed[$plan['savings']] = [
            'label'   => $plan['name'] . ' — Annual savings badge',
            'tab'     => 'Pricing',
            'monthly' => $plan['monthly'],
            'annual'  => $plan['annual'],
        ];
    }

    return $computed;
}

// One place that turns a variable name into display text.
// $format: 'raw' (as stored) or 'currency' ($1,500). Computed values ignore $format.
function pointone_gv_value(string $key, string $format = 'raw'): string
{
    if (! function_exists('get_field')) return '';

    $computed = pointone_gv_computed_definitions();

    if (isset($computed[$key])) {
        return pointone_gv_savings_text(
            get_field($computed[$key]['monthly'], 'option'),
            get_field($computed[$key]['annual'], 'option')
        );
    }

    if (! in_array($key, array_column(pointone_gv_field_definitions(), 'name'), true)) return '';

    $value = get_field($key, 'option');

    if ($value === null || $value === false || $value === '') return '';

    return $format === 'currency' ? pointone_gv_format_dollar($value) : (string) $value;
}


/*--------------------------------------------------------------
OPTIONS PAGE
--------------------------------------------------------------*/

add_action('acf/init', function () {

    if (! function_exists('acf_add_options_page')) return;

    acf_add_options_page([
        'page_title'  => 'Global Variables',
        'menu_title'  => 'Global Variables',
        'menu_slug'   => 'pointone-global-variables',
        'capability'  => 'manage_options',
        'parent_slug' => 'pointone-cms',
        'redirect'    => false,
    ]);
});


/*--------------------------------------------------------------
ACF FIELD GROUP
--------------------------------------------------------------*/

add_action('acf/init', function () {

    if (! function_exists('acf_add_local_field_group')) return;

    $definitions = pointone_gv_field_definitions();
    $fields      = [];
    $seen_tabs   = [];

    foreach ($definitions as $def) {

        $tab = $def['tab'] ?? 'General';

        if (! in_array($tab, $seen_tabs, true)) {

            $fields[] = [
                'key'   => 'field_gv_tab_' . sanitize_key($tab),
                'label' => $tab,
                'name'  => '',
                'type'  => 'tab',
            ];

            $seen_tabs[] = $tab;
        }

        $field = [
            'key'          => 'field_gv_' . $def['name'],
            'label'        => $def['label'],
            'name'         => $def['name'],
            'type'         => $def['type'],
            'required'     => 0,
            'instructions' => $def['instructions'] ?? '',
            'wrapper'      => [
                'width' => $def['width'] ?? '100',
            ],
        ];

        if (! empty($def['prepend'])) {
            $field['prepend'] = $def['prepend'];
        }

        if ($def['type'] === 'number') {
            $field['min']  = 0;
            $field['step'] = 0.01;
        }

        $fields[] = $field;
    }

    acf_add_local_field_group([
        'key'      => 'group_pointone_global_variables',
        'title'    => 'Global Variables',
        'fields'   => $fields,
        'location' => [
            [
                [
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'pointone-global-variables',
                ],
            ],
        ],
    ]);
});


/*--------------------------------------------------------------
FRONT-END JS OBJECT
--------------------------------------------------------------*/

add_action('wp_enqueue_scripts', function () {

    if (! function_exists('get_field')) return;

    $vars = [];

    foreach (pointone_gv_field_definitions() as $def) {

        $value = get_field($def['name'], 'option');

        if ($value === null || $value === false || $value === '') {
            $vars[$def['name']] = null;
            continue;
        }

        $vars[$def['name']] = ($def['type'] === 'number') ? (float) $value : (string) $value;
    }

    foreach (array_keys(pointone_gv_computed_definitions()) as $key) {
        $text        = pointone_gv_value($key);
        $vars[$key]  = $text !== '' ? $text : null;
    }

    wp_register_script('pointone-global-vars', false, [], null, false);
    wp_enqueue_script('pointone-global-vars');

    wp_add_inline_script(
        'pointone-global-vars',
        'window.pointoneGlobalVars = ' . wp_json_encode($vars) . ';'
    );
});


/*--------------------------------------------------------------
PRICING PAGE TOGGLE SCRIPT
--------------------------------------------------------------*/

// Monthly/annual toggle on /pricing/. Reads prices from window.pointoneGlobalVars.
// Its styles live in the child theme (css/molecules/pricing-toggle.css).

add_action('wp_enqueue_scripts', function () {

    if (! is_page('pricing')) return;

    wp_enqueue_script(
        'pointone-pricing-toggle',
        plugins_url('assets/pricing-toggle.js', __FILE__),
        ['pointone-global-vars'],
        filemtime(__DIR__ . '/assets/pricing-toggle.js'),
        true
    );
});


/*--------------------------------------------------------------
ELEMENTOR PRO DYNAMIC TAG
--------------------------------------------------------------*/

add_action('elementor/dynamic_tags/register', function ($dynamic_tags) {

    if (! class_exists('\Elementor\Modules\DynamicTags\Module')) return;

    $dynamic_tags->register_group('pointone', [
        'title' => 'Point One',
    ]);

    $options = [
        '' => '— Select a variable —',
    ];

    foreach (pointone_gv_field_definitions() as $def) {
        $options[$def['name']] = '[' . ($def['tab'] ?? 'General') . '] ' . $def['label'];
    }

    foreach (pointone_gv_computed_definitions() as $key => $def) {
        $options[$key] = '[' . $def['tab'] . '] ' . $def['label'];
    }

    $tag_class = new class($options) extends \Elementor\Core\DynamicTags\Tag {

        private array $field_options;

        public function __construct(array $options)
        {
            $this->field_options = $options;
            parent::__construct();
        }

        public function get_name(): string
        {
            return 'pointone-global-variable';
        }

        public function get_title(): string
        {
            return 'Global Variable';
        }

        public function get_group(): string
        {
            return 'pointone';
        }

        public function get_categories(): array
        {
            return [
                \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
                \Elementor\Modules\DynamicTags\Module::NUMBER_CATEGORY,
                \Elementor\Modules\DynamicTags\Module::POST_META_CATEGORY,
            ];
        }

        protected function register_controls(): void
        {
            $this->add_control('field_key', [
                'label'   => 'Variable',
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => $this->field_options,
                'default' => '',
            ]);

            $this->add_control('format', [
                'label'   => 'Format',
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'raw'      => 'As stored (1500)',
                    'currency' => 'Currency ($1,500)',
                ],
                'default' => 'raw',
            ]);
        }

        public function render(): void
        {
            $key = $this->get_settings('field_key');

            if (! $key) return;

            echo esc_html(pointone_gv_value($key, (string) $this->get_settings('format')));
        }
    };

    $dynamic_tags->register($tag_class);
});


/*--------------------------------------------------------------
SHORTCODE FALLBACK
--------------------------------------------------------------*/

add_shortcode('global_var', function ($atts) {

    $atts = shortcode_atts([
        'key'    => '',
        'format' => 'raw',
    ], $atts);

    if (! $atts['key']) return '';

    return esc_html(pointone_gv_value($atts['key'], $atts['format']));
});


/*--------------------------------------------------------------
PRICING SUMMARY SHORTCODE
--------------------------------------------------------------*/

// [pointone_pricing_summary] prints both billing periods as plain text, so the
// page states every price without needing the monthly/annual toggle.
add_shortcode('pointone_pricing_summary', function () {

    $parts = [];

    foreach (pointone_gv_plans() as $plan) {

        $monthly = pointone_gv_value($plan['monthly'], 'currency');
        $annual  = pointone_gv_value($plan['annual'], 'currency');

        if ($monthly === '' || $annual === '') continue;

        $parts[] = sprintf('%s: %s/month or %s/year per license', $plan['name'], $monthly, $annual);
    }

    if (! $parts) return '';

    return '<p class="pointone-pricing-summary">' . esc_html(implode(' · ', $parts)) . '</p>';
});


/*--------------------------------------------------------------
PRICING PAGE STRUCTURED DATA
--------------------------------------------------------------*/

// Product and Offer JSON-LD for /pricing/, built from the same Global Variables
// as the visible prices. A plan with a missing price is left out.
add_action('wp_head', function () {

    if (! is_page('pricing') || ! function_exists('get_field')) return;

    $url      = get_permalink();
    $products = [];

    foreach (pointone_gv_plans() as $plan) {

        $monthly = get_field($plan['monthly'], 'option');
        $annual  = get_field($plan['annual'], 'option');

        if ($monthly === null || $monthly === false || $monthly === '' || $annual === null || $annual === false || $annual === '') continue;

        $offers = [];

        foreach ([['Monthly', $monthly, 'P1M'], ['Annual', $annual, 'P1Y']] as [$label, $value, $duration]) {

            $price = number_format((float) $value, 2, '.', '');

            $offers[] = [
                '@type'              => 'Offer',
                'name'               => $plan['name'] . ' — ' . $label,
                'url'                => $url,
                'price'              => $price,
                'priceCurrency'      => 'USD',
                'priceSpecification' => [
                    '@type'           => 'UnitPriceSpecification',
                    'price'           => $price,
                    'priceCurrency'   => 'USD',
                    'billingDuration' => $duration,
                    'unitText'        => 'license',
                ],
            ];
        }

        $products[] = [
            '@type'  => 'Product',
            'name'   => $plan['name'],
            'url'    => $url,
            'brand'  => [
                '@type' => 'Brand',
                'name'  => 'Point One Navigation',
            ],
            'offers' => $offers,
        ];
    }

    if (! $products) return;

    $schema = [
        '@context' => 'https://schema.org',
        '@graph'   => $products,
    ];

    // HEX_TAG prevents the content from terminating this script element.
    $json = wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    if ($json !== false) {
        echo '<script type="application/ld+json" class="pointone-pricing-schema">' . $json . '</script>' . "\n";
    }
}, 20);
