<?php
/**
 * Plugin Name: Point One Nav - Site Alert Bar (ACF + Elementor)
 * Description: Date-based site-wide alert bar with dismiss (localStorage) and ACF fields registered in code.
 * Version: 1.1.1
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

    public function register_cpt() {
        $labels = [
            'name'               => 'Site Alerts',
            'singular_name'      => 'Site Alert',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Site Alert',
            'edit_item'          => 'Edit Site Alert',
            'new_item'           => 'New Site Alert',
            'view_item'          => 'View Site Alert',
            'search_items'       => 'Search Site Alerts',
            'not_found'          => 'No alerts found',
            'not_found_in_trash' => 'No alerts found in Trash',
            'menu_name'          => 'Site Alerts',
        ];

        register_post_type(self::CPT, [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-megaphone',
            'supports'           => ['title'],
            'has_archive'        => false,
            'rewrite'            => false,
            'show_in_rest'       => false,
            'capability_type'    => 'post',
        ]);
    }

    public function register_acf_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => self::FIELD_GROUP_KEY,
            'title' => 'Site Alert Bar',
            'fields' => [
                [
                    'key' => 'field_alert_desktop_text',
                    'label' => 'Alert Desktop Text',
                    'name' => 'alert_desktop_text',
                    'type' => 'textarea',
                    'instructions' => 'Shown on screens 768px and larger.',
                    'required' => 1,
                ],
                [
                    'key' => 'field_alert_mobile_text',
                    'label' => 'Alert Mobile Text',
                    'name' => 'alert_mobile_text',
                    'type' => 'textarea',
                    'instructions' => 'Shown on screens 767px and smaller.',
                    'required' => 1,
                ],
                [
                    'key' => 'field_alert_cta_text',
                    'label' => 'Alert CTA Text',
                    'name' => 'alert_cta_text',
                    'type' => 'text',
                    'instructions' => 'Clickable CTA label (e.g., "Register Now").',
                    'required' => 1,
                ],
                [
                    'key' => 'field_alert_cta_url',
                    'label' => 'Alert CTA URL',
                    'name' => 'alert_cta_url',
                    'type' => 'text',
                    'instructions' => 'Full URL including https:// (e.g., https://zoom.us/...).',
                    'required' => 1,
                ],
                [
                    'key' => 'field_alert_cta_position',
                    'label' => 'CTA Position',
                    'name' => 'alert_cta_position',
                    'type' => 'select',
                    'instructions' => 'Place the CTA link before or after the alert text.',
                    'choices' => [
                        'before' => 'Before Alert Text',
                        'after'  => 'After Alert Text',
                    ],
                    'default_value' => 'before',
                    'required' => 1,
                ],
                [
                    'key' => 'field_alert_start_date',
                    'label' => 'Alert Start Date',
                    'name' => 'alert_start_date',
                    'type' => 'date_time_picker',
                    'instructions' => 'Alert becomes visible at/after this date & time (site timezone).',
                    'required' => 1,
                    'display_format' => 'm/d/Y g:i a',
                    'return_format' => 'Y-m-d H:i:s',
                    'first_day' => 0,
                ],
                [
                    'key' => 'field_alert_end_date',
                    'label' => 'Alert End Date',
                    'name' => 'alert_end_date',
                    'type' => 'date_time_picker',
                    'instructions' => 'Alert stops showing at this date & time (end is exclusive).',
                    'required' => 1,
                    'display_format' => 'm/d/Y g:i a',
                    'return_format' => 'Y-m-d H:i:s',
                    'first_day' => 0,
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
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => [],
            'active' => true,
        ]);
    }

    public function register_shortcode() {
        add_shortcode(self::SHORTCODE, [$this, 'render_shortcode']);
    }

    public function enqueue_assets() {
        $src = plugins_url('assets/site-alert-bar.js', __FILE__);
        wp_enqueue_script(self::SCRIPT_HANDLE, $src, [], '1.1.1', true);
    }

    private function get_active_alert_id(): int {
        if (!function_exists('get_field')) {
            return 0;
        }

        $now_ts = current_time('timestamp');
        $now_str = date('Y-m-d H:i:s', $now_ts);

        $q = new WP_Query([
            'post_type'      => self::CPT,
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'orderby'        => 'meta_value',
            'order'          => 'DESC',
            'meta_key'       => 'alert_start_date',
            'meta_query'     => [
                [
                    'key'     => 'alert_start_date',
                    'value'   => $now_str,
                    'compare' => '<=',
                    'type'    => 'DATETIME',
                ],
            ],
            'no_found_rows'  => true,
        ]);

        if (!$q->have_posts()) return 0;

        foreach ($q->posts as $p) {
            $start = (string) get_field('alert_start_date', $p->ID);
            $end   = (string) get_field('alert_end_date', $p->ID);
            if (!$start || !$end) continue;

            $start_ts = strtotime($start);
            $end_ts   = strtotime($end);

            if ($start_ts <= $now_ts && $now_ts < $end_ts) {
                return (int) $p->ID;
            }
        }

        return 0;
    }

    public function render_shortcode($atts = [], $content = null): string {
        $alert_id = $this->get_active_alert_id();
        if (!$alert_id) return '';

        $desktop_text = (string) get_field('alert_desktop_text', $alert_id);
        $mobile_text  = (string) get_field('alert_mobile_text', $alert_id);
        $cta_text     = (string) get_field('alert_cta_text', $alert_id);
        $cta_url_raw  = (string) get_field('alert_cta_url', $alert_id);
        $cta_position = (string) (get_field('alert_cta_position', $alert_id) ?: 'before');

        if (!$desktop_text || !$mobile_text || !$cta_text || !$cta_url_raw) {
            return '';
        }

        $cta_url = esc_url($cta_url_raw);

        ob_start(); ?>
        <div class="site-alert-bar" data-alert-id="<?php echo (int) $alert_id; ?>">
            <div class="site-alert-bar__inner">
                <div class="site-alert-bar__message">

                    <?php if ($cta_position === 'before'): ?>
                        <a class="site-alert-bar__cta" href="<?php echo $cta_url; ?>" target="_blank" rel="noopener">
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
                        <a class="site-alert-bar__cta" href="<?php echo $cta_url; ?>" target="_blank" rel="noopener">
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
        return (string) ob_get_clean();
    }

    /** Admin columns (optional, but helpful) */
    public function admin_columns($cols) {
        $new = [];
        foreach ($cols as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['alert_status'] = 'Status';
                $new['alert_window'] = 'Schedule';
            }
        }
        return $new;
    }

    public function admin_column_values($col, $post_id) {
        if (!function_exists('get_field')) return;

        if ($col === 'alert_status') {
            $now = current_time('timestamp');
            $start = get_field('alert_start_date', $post_id);
            $end   = get_field('alert_end_date', $post_id);

            if (!$start || !$end) { echo '—'; return; }

            $start_ts = strtotime($start);
            $end_ts = strtotime($end);

            if ($now < $start_ts) echo 'Scheduled';
            elseif ($start_ts <= $now && $now < $end_ts) echo 'Active';
            else echo 'Expired';
            return;
        }

        if ($col === 'alert_window') {
            $start = get_field('alert_start_date', $post_id);
            $end   = get_field('alert_end_date', $post_id);

            if (!$start || !$end) { echo '—'; return; }

            $start_disp = date_i18n('M j, Y g:i a', strtotime($start));
            $end_disp   = date_i18n('M j, Y g:i a', strtotime($end));
            echo esc_html($start_disp . ' → ' . $end_disp);
            return;
        }
    }
}

new Site_Alert_Bar_Plugin();