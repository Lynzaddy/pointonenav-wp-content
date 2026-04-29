<?php

/**
 * Plugin Name: Point One Nav - Global Variables
 * Description: Global site variables managed via an ACF Options Page. Values are exposed in Elementor Pro via a custom Dynamic Tag (⚡ picker), via the [global_var key="..."] shortcode, and injected into the front end as a JS object (pointoneGlobalVars). The pricing page toggle script is also managed and enqueued from this plugin.
 * Version: 1.2.1
 */

if (!defined('ABSPATH')) exit;


/*--------------------------------------------------------------
FIELD DEFINITIONS  ← only edit here to add / remove variables
--------------------------------------------------------------*/

/**
 * This array is the single source of truth.
 * It drives the ACF field group, the Elementor Dynamic Tag dropdown,
 * and the JS object injected on the front end (pointoneGlobalVars).
 *
 * Each entry:
 *   'name'         (string)  ACF field name / shortcode key / JS object property
 *   'label'        (string)  Human-readable label shown in admin + tag picker
 *   'type'         (string)  ACF field type: 'number', 'text', 'textarea', etc.
 *   'tab'          (string)  Groups fields under a tab in the Options Page
 *   'instructions' (string)  Helper text shown below the field in the admin
 *   'prepend'      (string)  Optional prefix shown in the admin input (e.g. '$')
 *   'width'        (string)  Column width in the admin: '33', '50', '100', etc.
 *
 * Adding a new variable: append an entry below. Done.
 * Removing a variable:   delete its entry. Tags/shortcodes/JS using it return empty/null.
 */

function pointone_gv_field_definitions(): array
{
    return [

        // ── Pricing ───────────────────────────────────────────────────────
        // Fields are interleaved Virtual (left) / True (right) so that each
        // matching pair sits on the same row in the admin. ACF renders 50%-width
        // fields sequentially: odd = left column, even = right column.

        // Row 1: Monthly prices
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

        // Row 2: Annual prices
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
        'page_title' => 'Global Variables',
        'menu_title' => 'Global Variables',
        'menu_slug'  => 'pointone-global-variables',
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

    $definitions = pointone_gv_field_definitions();
    $fields      = [];
    $seen_tabs   = [];

    foreach ($definitions as $def) {

        $tab = $def['tab'] ?? 'General';

        if (!in_array($tab, $seen_tabs, true)) {
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
        'key'      => 'group_pointone_global_variables',
        'title'    => 'Global Variables',
        'fields'   => $fields,
        'location' => [
            [
                [
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'pointone-global-variables',
                ]
            ]
        ],
    ]);
});


/*--------------------------------------------------------------
FRONT-END JS OBJECT  (pointoneGlobalVars)
--------------------------------------------------------------*/

/**
 * Injects all Global Variable values into the front end as a JS object.
 * Available on every page as: window.pointoneGlobalVars
 *
 * Number fields are cast to floats so JS arithmetic works without parsing.
 * Text fields are passed as strings. Unset fields pass as null.
 *
 * This runs site-wide intentionally — the data is already autoloaded by
 * WordPress from wp_options so there is no extra DB cost, and the output
 * is only ~200–300 bytes. Keeping it global means any future page or
 * component can reference these values without plugin changes.
 */

add_action('wp_enqueue_scripts', function () {

    if (!function_exists('get_field')) return;

    $vars = [];

    foreach (pointone_gv_field_definitions() as $def) {
        $value = get_field($def['name'], 'option');

        if ($value === null || $value === false || $value === '') {
            $vars[$def['name']] = null;
            continue;
        }

        $vars[$def['name']] = ($def['type'] === 'number') ? (float) $value : (string) $value;
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

/**
 * Enqueues the pricing toggle JS only on the /pricing page.
 * The script lives in this plugin's assets/ folder so all pricing
 * logic — data, injection, and toggle behavior — is co-located here.
 *
 * The script is loaded in the footer (true) so the DOM is already
 * parsed and no DOMContentLoaded wrapper is needed in the JS itself.
 *
 * Depends on pointone-global-vars so WordPress ensures that object
 * is always available before this script runs.
 *
 * To update the pricing page slug: change 'pricing' below.
 */

add_action('wp_enqueue_scripts', function () {

    if (!is_page('pricing')) return;

    wp_enqueue_script(
        'pointone-pricing-toggle',
        plugins_url('assets/pricing-toggle.js', __FILE__),
        ['pointone-global-vars'],
        '1.2.0',
        true
    );
});


/*--------------------------------------------------------------
ELEMENTOR PRO DYNAMIC TAG
--------------------------------------------------------------*/

/**
 * Appears in the ⚡ picker as "Global Variable" under the "Point One" group.
 * Works in headings, text editors, button labels, number controls, etc.
 *
 * In the Elementor editor:
 *   1. Click the ⚡ icon on any text or number control.
 *   2. Choose "Global Variable" under the "Point One" group.
 *   3. Pick the variable from the dropdown.
 */

add_action('elementor/dynamic_tags/register', function ($dynamic_tags) {

    if (!class_exists('\Elementor\Modules\DynamicTags\Module')) return;

    $dynamic_tags->register_group('pointone', [
        'title' => 'Point One',
    ]);

    $options = ['' => '— Select a variable —'];
    foreach (pointone_gv_field_definitions() as $def) {
        $options[$def['name']] = '[' . ($def['tab'] ?? 'General') . '] ' . $def['label'];
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
SHORTCODE FALLBACK  [global_var key="field_name"]
--------------------------------------------------------------*/

/**
 * For contexts where Dynamic Tags cannot be used:
 * custom HTML widgets, third-party plugins, PHP templates, etc.
 *
 * Usage in Elementor text widget:  $[global_var key="price_virtual_monthly"]/mo
 * Usage in PHP:                    get_field('price_virtual_monthly', 'option')
 */

add_shortcode('global_var', function ($atts) {

    $atts = shortcode_atts(['key' => ''], $atts);

    if (!$atts['key'] || !function_exists('get_field')) return '';

    $value = get_field($atts['key'], 'option');

    if ($value === null || $value === false || $value === '') return '';

    return esc_html($value);
});
