<?php

/**
 * Plugin Name: Point One Nav - Global Variables
 * Description: Global site variables managed via an ACF Options Page. Values are exposed in Elementor Pro via a custom Dynamic Tag, via the [global_var key="..."] shortcode, and injected into the front end as a JS object.
 * Version: 1.3.0
 */

if (!defined('ABSPATH')) exit;


/*--------------------------------------------------------------
FIELD DEFINITIONS
--------------------------------------------------------------*/

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
OPTIONS PAGE
--------------------------------------------------------------*/

add_action('acf/init', function () {

    if (!function_exists('acf_add_options_page')) return;

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
            'wrapper'      => [
                'width' => $def['width'] ?? '100',
            ],
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
                ],
            ],
        ],
    ]);
});


/*--------------------------------------------------------------
FRONT-END JS OBJECT
--------------------------------------------------------------*/

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

add_action('wp_enqueue_scripts', function () {

    if (!is_page('pricing')) return;

    wp_enqueue_script(
        'pointone-pricing-toggle',
        plugins_url('assets/pricing-toggle.js', __FILE__),
        ['pointone-global-vars'],
        '1.3.0',
        true
    );
});


/*--------------------------------------------------------------
ELEMENTOR PRO DYNAMIC TAG
--------------------------------------------------------------*/

add_action('elementor/dynamic_tags/register', function ($dynamic_tags) {

    if (!class_exists('\Elementor\Modules\DynamicTags\Module')) return;

    $dynamic_tags->register_group('pointone', [
        'title' => 'Point One',
    ]);

    $options = [
        '' => '— Select a variable —',
    ];

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
SHORTCODE FALLBACK
--------------------------------------------------------------*/

add_shortcode('global_var', function ($atts) {

    $atts = shortcode_atts([
        'key' => '',
    ], $atts);

    if (!$atts['key'] || !function_exists('get_field')) return '';

    $value = get_field($atts['key'], 'option');

    if ($value === null || $value === false || $value === '') return '';

    return esc_html($value);
});
