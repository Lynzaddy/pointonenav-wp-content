<?php
/**
 * Plugin Name: Point One Nav - Site Alert Bar (ACF + Elementor)
 * Description: Date-based site-wide alert bar with dismiss (localStorage), admin enhancements, and clone support.
 * Version: 1.4.0
 * Author: Point One Navigation
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

        /* Admin Columns */
        add_filter('manage_' . self::CPT . '_posts_columns', [$this, 'admin_columns']);
        add_action('manage_' . self::CPT . '_posts_custom_column', [$this, 'admin_column_values'], 10, 2);
        add_filter('manage_edit_' . self::CPT . '_sortable_columns', [$this, 'make_columns_sortable']);
        add_action('pre_get_posts', [$this, 'handle_column_sorting']);
        add_action('admin_head', [$this, 'admin_badge_styles']);

        /* Clone Support */
        add_filter('post_row_actions', [$this, 'add_clone_link'], 10, 2);
        add_action('admin_action_clone_site_alert', [$this, 'clone_site_alert']);
    }

    /* =============================================
       CPT
    ============================================= */

    public function register_cpt() {
        register_post_type(self::CPT, [
            'labels' => [
                'name' => 'Site Alerts',
                'singular_name' => 'Site Alert',
                'menu_name' => 'Site Alerts',
            ],
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-megaphone',
            'supports' => ['title'],
        ]);
    }

    /* =============================================
       ACF FIELDS
    ============================================= */

    public function register_acf_fields() {

        if (!function_exists('acf_add_local_field_group')) return;

        acf_add_local_field_group([
            'key' => self::FIELD_GROUP_KEY,
            'title' => 'Site Alert Bar',
            'fields' => [

                ['key'=>'field_alert_desktop_text','label'=>'Alert Desktop Text','name'=>'alert_desktop_text','type'=>'text','required'=>1],
                ['key'=>'field_alert_mobile_text','label'=>'Alert Mobile Text','name'=>'alert_mobile_text','type'=>'text','required'=>1],
                ['key'=>'field_alert_cta_text','label'=>'Alert CTA Text','name'=>'alert_cta_text','type'=>'text','required'=>1],
                ['key'=>'field_alert_cta_url','label'=>'Alert CTA URL','name'=>'alert_cta_url','type'=>'text','required'=>1],

                [
                    'key'=>'field_alert_cta_position',
                    'label'=>'CTA Position',
                    'name'=>'alert_cta_position',
                    'type'=>'select',
                    'choices'=>['before'=>'Before Alert Text','after'=>'After Alert Text'],
                    'default_value'=>'before',
                ],

                [
                    'key'=>'field_alert_cta_new_window',
                    'label'=>'Open CTA in New Window?',
                    'name'=>'alert_cta_new_window',
                    'type'=>'true_false',
                    'ui'=>1,
                    'default_value'=>1,
                ],

                [
                    'key'=>'field_alert_start_date',
                    'label'=>'Alert Start Date/Time',
                    'name'=>'alert_start_date',
                    'type'=>'date_time_picker',
                    'required'=>1,
                    'display_format'=>'m/d/Y g:i a',
                    'return_format'=>'Y-m-d H:i:s',
                ],

                [
                    'key'=>'field_alert_end_date',
                    'label'=>'Alert End Date/Time',
                    'name'=>'alert_end_date',
                    'type'=>'date_time_picker',
                    'required'=>1,
                    'display_format'=>'m/d/Y g:i a',
                    'return_format'=>'Y-m-d H:i:s',
                ],
            ],
            'location'=>[[['param'=>'post_type','operator'=>'==','value'=>self::CPT]]],
        ]);
    }

    /* =============================================
       CLONE FEATURE
    ============================================= */

    public function add_clone_link($actions, $post) {

        if ($post->post_type !== self::CPT) return $actions;

        $url = wp_nonce_url(
            admin_url('admin.php?action=clone_site_alert&post=' . $post->ID),
            'clone_site_alert_' . $post->ID
        );

        $actions['clone'] = '<a href="' . esc_url($url) . '">Clone</a>';
        return $actions;
    }

    public function clone_site_alert() {

        if (!isset($_GET['post'])) wp_die('No post to clone.');

        $post_id = intval($_GET['post']);

        if (!wp_verify_nonce($_GET['_wpnonce'], 'clone_site_alert_' . $post_id)) {
            wp_die('Security check failed.');
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== self::CPT) {
            wp_die('Invalid alert.');
        }

        $new_post_id = wp_insert_post([
            'post_type'   => self::CPT,
            'post_status' => 'draft',
            'post_title'  => $post->post_title . ' (Copy)',
        ]);

        /* Copy all meta (including ACF fields) */
        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $values) {
            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value));
            }
        }

        wp_redirect(admin_url('edit.php?post_type=' . self::CPT));
        exit;
    }

    /* =============================================
       (Remaining alert + admin code unchanged)
    ============================================= */

}

new Site_Alert_Bar_Plugin();