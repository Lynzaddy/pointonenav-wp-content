<?php

/**
 * Plugin Name: Point One Nav - Site Settings
 * Description: Global site variables managed via an ACF Options Page. Values are exposed in Elementor Pro via a custom Dynamic Tag (⚡ picker) and via the [site_setting key="..."] shortcode as a fallback.
 * Version: 1.0.1
 */

if (!defined('ABSPATH')) exit;


/*--------------------------------------------------------------
FIELD DEFINITIONS  ← only edit here to add / remove variables
--------------------------------------------------------------*/

/**
 * This array is the single source of truth.
 * It drives both the ACF field group AND the Elementor Dynamic Tag dropdown.
 *
 * Each entry:
 *   'name'         (string)  ACF field name / shortcode key
 *   'label'        (string)  Human-readable label shown in admin + tag picker
 *   'type'         (string)  ACF field type: 'number', 'text', 'textarea', etc.
 *   'tab'          (string)  Groups fields under a tab in the Options Page
 *   'instructions' (string)  Helper text shown below the field in the admin
 *   'prepend'      (string)  Optional prefix shown in the admin input (e.g. '$')
 *   'width'        (string)  Column width in the admin: '33', '50', '100', etc.
 *
 * Adding a new variable: append an entry below. Done.
 * Removing a variable:   delete its entry. Any tags/shortcodes using it return empty.
 */

function pointone_ss_field_definitions(): array
{
    return [

        // ── Pricing ───────────────────────────────────────────────────────

        [
            'name'         => 'price_monthly',
            'label'        => 'Monthly Price',
            'type'         => 'number',
            'tab'          => 'Pricing',
            'instructions' => 'Displayed as the monthly billing rate. Example: 99',
            'prepend'      => '$',
            'width'        => '33',
        ],
        [
            'name'         => 'price_annual',
            'label'        => 'Annual Price',
            'type'         => 'number',
            'tab'          => 'Pricing',
            'instructions' => 'Total billed annually. Example: 990',
            'prepend'      => '$',
            'width'        => '33',
        ],
        [
            'name'         => 'price_annual_per_month',
            'label'        => 'Annual Price (per month)',
            'type'         => 'number',
            'tab'          => 'Pricing',
            'instructions' => 'The "billed as $X/mo" breakdown figure. Example: 82.50',
            'prepend'      => '$',
            'width'        => '33',
        ],

        // ── Add more groups below ─────────────────────────────────────────
        // Use a new 'tab' value to create a new tab section in the admin.
        //
        // [
        //     'name'         => 'stat_customer_count',
        //     'label'        => 'Customer Count',
        //     'type'         => 'text',
        //     'tab'          => 'Stats',
        //     'instructions' => 'Example: 10,000+',
        //     'width'        => '50',
        // ],
        // [
        //     'name'         => 'stat_years',
        //     'label'        => 'Years in Business',
        //     'type'         => 'number',
        //     'tab'          => 'Stats',
        //     'instructions' => 'Example: 8',
        //     'width'        => '50',
        // ],

    ];
}


/*--------------------------------------------------------------
OPTIONS PAGE
--------------------------------------------------------------*/

add_action('acf/init', function () {

    if (!function_exists('acf_add_options_page')) return;

    acf_add_options_page([
        'page_title' => 'Site Settings',
        'menu_title' => 'Site Settings',
        'menu_slug'  => 'pointone-site-settings',
        'capability' => 'manage_options',
        'icon_url'   => 'dashicons-admin-settings',
        'position'   => 30,
        'redirect'   => false,
    ]);
});


/*--------------------------------------------------------------
ACF FIELD GROUP  (built from field definitions above)
--------------------------------------------------------------*/

add_action('acf/init', function () {

    if (!function_exists('acf_add_local_field_group')) return;

    $definitions = pointone_ss_field_definitions();
    $fields      = [];
    $seen_tabs   = [];

    foreach ($definitions as $def) {

        $tab = $def['tab'] ?? 'General';

        // Insert a tab field the first time we see this tab name
        if (!in_array($tab, $seen_tabs, true)) {
            $fields[] = [
                'key'   => 'field_ps_tab_' . sanitize_key($tab),
                'label' => $tab,
                'name'  => '',
                'type'  => 'tab',
            ];
            $seen_tabs[] = $tab;
        }

        $field = [
            'key'          => 'field_ps_' . $def['name'],
            'label'        => $def['label'],
            'name'         => $def['name'],
            'type'         => $def['type'],
            'required'     => 0,
            'instructions' => $def['instructions'] ?? '',
            'wrapper'      => ['width' => $def['width'] ?? '100'],
        ];

        if (!empty($def['prepend'])) {
            $field['prepend'] = $def['prepend'];
        }

        if ($def['type'] === 'number') {
            $field['min']  = 0;
            $field['step'] = 0.01;
        }

        $fields[] = $field;
    }

    acf_add_local_field_group([
        'key'      => 'group_pointone_site_settings',
        'title'    => 'Site Settings',
        'fields'   => $fields,
        'location' => [
            [
                [
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'pointone-site-settings',
                ]
            ]
        ],
    ]);
});


/*--------------------------------------------------------------
ELEMENTOR PRO DYNAMIC TAG
--------------------------------------------------------------*/

/**
 * Registered tag name:  pointone-site-setting
 * Appears in picker as: Site Setting  (under the "Point One" group)
 * Categories:           Text, Number — works in headings, text, buttons, etc.
 *
 * In the Elementor editor:
 *   1. Click the lightning bolt (⚡) icon on any text or number control.
 *   2. Choose "Site Setting" under the "Point One" group.
 *   3. Pick the variable from the dropdown.
 */

add_action('elementor/dynamic_tags/register', function ($dynamic_tags) {

    if (!class_exists('\Elementor\Modules\DynamicTags\Module')) return;

    // Register a "Point One" group in the tag picker
    $dynamic_tags->register_group('pointone', [
        'title' => 'Point One',
    ]);

    // Build the select options from field definitions — auto-updates as you add fields
    $options = ['' => '— Select a field —'];
    foreach (pointone_ss_field_definitions() as $def) {
        $options[$def['name']] = '[' . ($def['tab'] ?? 'General') . '] ' . $def['label'];
    }

    // Anonymous class — no separate file needed
    $tag_class = new class($options) extends \Elementor\Core\DynamicTags\Tag {

        private array $field_options;

        public function __construct(array $options)
        {
            $this->field_options = $options;
            parent::__construct();
        }

        public function get_name(): string
        {
            return 'pointone-site-setting';
        }

        public function get_title(): string
        {
            return 'Site Setting';
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
        }

        public function render(): void
        {
            $key = $this->get_settings('field_key');

            if (!$key || !function_exists('get_field')) return;

            $value = get_field($key, 'option');

            if ($value === null || $value === false || $value === '') return;

            echo esc_html($value);
        }
    };

    $dynamic_tags->register($tag_class);
});


/*--------------------------------------------------------------
SHORTCODE FALLBACK  [site_setting key="field_name"]
--------------------------------------------------------------*/

/**
 * Kept as a fallback for contexts where Dynamic Tags cannot be used:
 * custom HTML widgets, third-party plugins, PHP templates, etc.
 *
 * Usage in Elementor text widget:  $[site_setting key="price_monthly"]/mo
 * Usage in PHP template:           <?php echo do_shortcode('[site_setting key="price_monthly"]'); ?>
 * Direct PHP:                      get_field('price_monthly', 'option')
 */

add_shortcode('site_setting', function ($atts) {

    $atts = shortcode_atts(['key' => ''], $atts);

    if (!$atts['key'] || !function_exists('get_field')) return '';

    $value = get_field($atts['key'], 'option');

    if ($value === null || $value === false || $value === '') return '';

    return esc_html($value);
});
