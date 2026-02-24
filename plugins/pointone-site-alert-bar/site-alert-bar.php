<?php
/**
 * Plugin Name: Point One Nav - Site Alert Bar (ACF + Elementor)
 * Description: Date-based site-wide alert bar with dismiss (localStorage) and ACF fields registered in code.
 * Version: 1.1.0
 * Author: Point One Navigation
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

final class Site_Alert_Bar_Plugin {

    const CPT = 'site_alert';
    const SHORTCODE = 'site_alert_bar';
    const SCRIPT_HANDLE = 'site-alert-bar';
    const FIELD_GROUP_KEY = 'group_site_alert_bar';

    public function __construct() {
        add_action('init', [$this, 'register_cpt']);
        add_action('init', [$this, 'register_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('acf/init', [$this, 'register_acf_fields']);

        add_filter('manage_' . self::CPT . '_posts_columns', [$this, 'admin_columns']);
        add_action('manage_' . self::CPT . '_posts_custom_column', [$this, 'admin_column_values'], 10, 2);
    }

    /* ---------------------------------------------
       CPT
    --------------------------------------------- */

    public function register_cpt() {

        register_post_type(self::CPT, [
            'labels' => [
                'name' => 'Site Alerts',
                'singular_name' => 'Site Alert',
                'add_new_item' => 'Add New Site Alert',
                'edit_item' => 'Edit Site Alert',
                'menu_name' => 'Site Alerts',
            ],
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-megaphone',
            'supports' => ['title'],
        ]);
    }

    /* ---------------------------------------------
       ACF FIELDS (UPDATED)
    --------------------------------------------- */

    public function register_acf_fields() {

        if (!function_exists('acf_add_local_field_group')) return;

        acf_add_local_field_group([
            'key' => self::FIELD_GROUP_KEY,
            'title' => 'Site Alert Bar',
            'fields' => [

                [
                    'key' => 'field_alert_desktop_text',
                    'label' => 'Alert Desktop Text',
                    'name' => 'alert_desktop_text',
                    'type' => 'textarea',
                    'instructions' => 'This text displays on screens 768px and larger.',
                    'required' => 1,
                ],

                [
                    'key' => 'field_alert_mobile_text',
                    'label' => 'Alert Mobile Text',
                    'name' => 'alert_mobile_text',
                    'type' => 'textarea',
                    'instructions' => 'This text displays on screens 767px and smaller.',
                    'required' => 1,
                ],

                [
                    'key' => 'field_alert_cta_text',
                    'label' => 'Alert CTA Text',
                    'name' => 'alert_cta_text',
                    'type' => 'text',
                    'instructions' => 'CTA label text (example: Register Now).',
                    'required' => 1,
                ],

                [
                    'key' => 'field_alert_cta_url',
                    'label' => 'Alert CTA URL',
                    'name' => 'alert_cta_url',
                    'type' => 'text',
                    'instructions' => 'Enter full URL including https:// (example: https://zoom.us/...)',
                    'required' => 1,
                ],

                [
                    'key' => 'field_alert_cta_position',
                    'label' => 'CTA Position',
                    'name' => 'alert_cta_position',
                    'type' => 'select',
                    'instructions' => 'Choose whether the CTA appears before or after the alert text.',
                    'choices' => [
                        'before' => 'Before Alert Text',
                        'after' => 'After Alert Text',
                    ],
                    'default_value' => 'before',
                    'required' => 1,
                ],

                [
                    'key' => 'field_alert_start_date',
                    'label' => 'Alert Start Date',
                    'name' => 'alert_start_date',
                    'type' => 'date_time_picker',
                    'required' => 1,
                    'display_format' => 'm/d/Y g:i a',
                    'return_format' => 'Y-m-d H:i:s',
                ],

                [
                    'key' => 'field_alert_end_date',
                    'label' => 'Alert End Date',
                    'name' => 'alert_end_date',
                    'type' => 'date_time_picker',
                    'required' => 1,
                    'display_format' => 'm/d/Y g:i a',
                    'return_format' => 'Y-m-d H:i:s',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => self::CPT,
                    ],
                ],
            ],
        ]);
    }

    /* ---------------------------------------------
       ASSETS
    --------------------------------------------- */

    public function enqueue_assets() {
        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            plugins_url('assets/site-alert-bar.js', __FILE__),
            [],
            '1.1.0',
            true
        );
    }

    /* ---------------------------------------------
       ACTIVE ALERT LOGIC
    --------------------------------------------- */

    private function get_active_alert_id(): int {

        if (!function_exists('get_field')) return 0;

        $now_ts = current_time('timestamp');
        $now_str = date('Y-m-d H:i:s', $now_ts);

        $q = new WP_Query([
            'post_type' => self::CPT,
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'meta_key' => 'alert_start_date',
            'orderby' => 'meta_value',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => 'alert_start_date',
                    'value' => $now_str,
                    'compare' => '<=',
                    'type' => 'DATETIME',
                ],
            ],
        ]);

        foreach ($q->posts as $p) {
            $start = get_field('alert_start_date', $p->ID);
            $end = get_field('alert_end_date', $p->ID);

            if (!$start || !$end) continue;

            if (strtotime($start) <= $now_ts && $now_ts < strtotime($end)) {
                return (int) $p->ID;
            }
        }

        return 0;
    }

    /* ---------------------------------------------
       SHORTCODE RENDER (UPDATED)
    --------------------------------------------- */

    public function render_shortcode(): string {

        $alert_id = $this->get_active_alert_id();
        if (!$alert_id) return '';

        $desktop_text = get_field('alert_desktop_text', $alert_id);
        $mobile_text  = get_field('alert_mobile_text', $alert_id);
        $cta_text     = get_field('alert_cta_text', $alert_id);
        $cta_url      = get_field('alert_cta_url', $alert_id);
        $cta_position = get_field('alert_cta_position', $alert_id) ?: 'before';

        if (!$desktop_text || !$mobile_text || !$cta_text || !$cta_url) {
            return '';
        }

        ob_start(); ?>

        <div class="site-alert-bar" data-alert-id="<?php echo (int) $alert_id; ?>">
            <div class="site-alert-bar__inner">

                <div class="site-alert-bar__message">

                    <?php if ($cta_position === 'before'): ?>
                        <a class="site-alert-bar__cta" href="<?php echo esc_url($cta_url); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($cta_text); ?>
                        </a>
                    <?php endif; ?>

                    <span class="site-alert-bar__text site-alert-bar__text--desktop">
                        <?php echo esc_html($desktop_text); ?>
                    </span>

                    <span class="site-alert-bar__text site-alert-bar__text--mobile">
                        <?php echo esc_html($mobile_text); ?>
                    </span>

                    <?php if ($cta_position === 'after'): ?>
                        <a class="site-alert-bar__cta" href="<?php echo esc_url($cta_url); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($cta_text); ?>
                        </a>
                    <?php endif; ?>

                </div>

                <button class="site-alert-bar__close" type="button" aria-label="Dismiss alert">
                    &times;
                </button>

            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    /* ---------------------------------------------
       ADMIN COLUMNS (unchanged)
    --------------------------------------------- */

    public function admin_columns($cols) { return $cols; }
    public function admin_column_values($col, $post_id) {}

}

new Site_Alert_Bar_Plugin();