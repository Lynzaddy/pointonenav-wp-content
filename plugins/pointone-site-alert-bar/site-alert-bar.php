<?php
/**
 * Plugin Name: Point One Nav - Site Alert Bar (ACF + Elementor)
 * Description: Date-based site-wide alert bar with dismiss, admin enhancements, and clone support.
 * Version: 1.5.0
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

        /* Sortable */
        add_filter('manage_edit_' . self::CPT . '_sortable_columns', [$this, 'make_columns_sortable']);
        add_action('pre_get_posts', [$this, 'handle_column_sorting']);

        /* Admin UI */
        add_action('admin_enqueue_scripts', [$this, 'admin_badge_styles']);

        /* Clone */
        add_filter('post_row_actions', [$this, 'add_clone_link'], 10, 2);
        add_action('admin_action_clone_site_alert', [$this, 'clone_site_alert']);
    }

    /* =====================================================
       CPT
    ===================================================== */

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

    /* =====================================================
       ACF FIELDS
    ===================================================== */

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

    /* =====================================================
       FRONTEND
    ===================================================== */

    public function register_shortcode() {
        add_shortcode(self::SHORTCODE, [$this, 'render_shortcode']);
    }

    public function enqueue_assets() {
        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            plugins_url('assets/site-alert-bar.js', __FILE__),
            [],
            '1.5.0',
            true
        );
    }

    private function get_active_alert_id(): int {

        if (!function_exists('get_field')) return 0;

        $now = current_time('timestamp');
        $now_str = date('Y-m-d H:i:s', $now);

        $q = new WP_Query([
            'post_type'=>self::CPT,
            'post_status'=>'publish',
            'meta_key'=>'alert_start_date',
            'orderby'=>'meta_value',
            'order'=>'DESC',
            'meta_query'=>[
                [
                    'key'=>'alert_start_date',
                    'value'=>$now_str,
                    'compare'=>'<=',
                    'type'=>'DATETIME'
                ]
            ]
        ]);

        foreach ($q->posts as $p) {
            $start = get_field('alert_start_date',$p->ID);
            $end = get_field('alert_end_date',$p->ID);

            if ($start && $end && strtotime($start)<= $now && $now < strtotime($end)) {
                return $p->ID;
            }
        }

        return 0;
    }

    public function render_shortcode(): string {

        $id = $this->get_active_alert_id();
        if (!$id) return '';

        $desktop = get_field('alert_desktop_text',$id);
        $mobile = get_field('alert_mobile_text',$id);
        $cta_text = get_field('alert_cta_text',$id);
        $cta_url = esc_url(get_field('alert_cta_url',$id));
        $position = get_field('alert_cta_position',$id) ?: 'before';
        $new = get_field('alert_cta_new_window',$id);

        if (!$desktop || !$mobile || !$cta_text || !$cta_url) return '';

        $target = $new ? '_blank' : '_self';
        $rel = $new ? 'noopener' : '';

        ob_start(); ?>
        <div class="site-alert-bar" data-alert-id="<?php echo $id; ?>">
            <div class="site-alert-bar__inner">
                <div class="site-alert-bar__message">

                    <?php if ($position==='before'): ?>
                        <a class="site-alert-bar__cta" href="<?php echo $cta_url; ?>" target="<?php echo esc_attr($target); ?>" <?php if($rel) echo 'rel="'.$rel.'"'; ?>>
                            <?php echo esc_html($cta_text); ?>
                        </a>
                    <?php endif; ?>

                    <span class="site-alert-bar__text site-alert-bar__text--desktop"><?php echo esc_html($desktop); ?></span>
                    <span class="site-alert-bar__text site-alert-bar__text--mobile"><?php echo esc_html($mobile); ?></span>

                    <?php if ($position==='after'): ?>
                        <a class="site-alert-bar__cta" href="<?php echo $cta_url; ?>" target="<?php echo esc_attr($target); ?>" <?php if($rel) echo 'rel="'.$rel.'"'; ?>>
                            <?php echo esc_html($cta_text); ?>
                        </a>
                    <?php endif; ?>

                </div>

                <button class="site-alert-bar__close" type="button">&times;</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* =====================================================
       ADMIN COLUMNS
    ===================================================== */

    public function admin_columns($columns) {

        $new = [];

        foreach ($columns as $key => $value) {
            $new[$key] = $value;

            if ($key === 'title') {
                $new['alert_start']  = 'Start Date/Time';
                $new['alert_end']    = 'End Date/Time';
                $new['alert_status'] = 'Status';
            }
        }

        return $new;
    }

    public function admin_column_values($column, $post_id) {

        $start = get_field('alert_start_date', $post_id);
        $end   = get_field('alert_end_date', $post_id);

        if ($column === 'alert_start') {
            echo $start ? esc_html(date_i18n('M j, Y g:i a', strtotime($start))) : '—';
        }

        if ($column === 'alert_end') {
            echo $end ? esc_html(date_i18n('M j, Y g:i a', strtotime($end))) : '—';
        }

        if ($column === 'alert_status') {

            if (!$start || !$end) { echo '—'; return; }

            $now = current_time('timestamp');
            $start_ts = strtotime($start);
            $end_ts = strtotime($end);

            if ($now < $start_ts) {
                echo '<span class="sab-badge sab-scheduled">Scheduled</span>';
            } elseif ($start_ts <= $now && $now < $end_ts) {
                echo '<span class="sab-badge sab-active">Active</span>';
            } else {
                echo '<span class="sab-badge sab-expired">Expired</span>';
            }
        }
    }

    public function make_columns_sortable($columns) {
        $columns['alert_start'] = 'alert_start_date';
        $columns['alert_end'] = 'alert_end_date';
        return $columns;
    }

    public function handle_column_sorting($query) {

        if (!is_admin() || !$query->is_main_query()) return;
        if ($query->get('post_type') !== self::CPT) return;

        if (!$query->get('orderby')) {
            $query->set('meta_key', 'alert_start_date');
            $query->set('orderby', 'meta_value');
            $query->set('order', 'DESC');
        }

        if ($query->get('orderby') === 'alert_start_date') {
            $query->set('meta_key', 'alert_start_date');
            $query->set('orderby', 'meta_value');
        }

        if ($query->get('orderby') === 'alert_end_date') {
            $query->set('meta_key', 'alert_end_date');
            $query->set('orderby', 'meta_value');
        }
    }

    public function admin_badge_styles() {

        ?>
        <style>
            .sab-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;border:1px solid rgba(0,0,0,.08);background:#fff;}
            .sab-badge::before{content:"";width:8px;height:8px;border-radius:50%;}
            .sab-active{background:#e6f9ed;color:#0f5132;}
            .sab-active::before{background:#28a745;}
            .sab-scheduled{background:#fff8e1;color:#856404;}
            .sab-scheduled::before{background:#f59e0b;}
            .sab-expired{background:#f1f3f5;color:#383d41;}
            .sab-expired::before{background:#6b7280;}
        </style>
        <?php
    }

    /* =====================================================
       CLONE
    ===================================================== */

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
        if (!$post || $post->post_type !== self::CPT) wp_die('Invalid alert.');

        $new_post_id = wp_insert_post([
            'post_type'   => self::CPT,
            'post_status' => 'draft',
            'post_title'  => $post->post_title . ' (Copy)',
        ]);

        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $values) {
            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value));
            }
        }

        wp_redirect(admin_url('edit.php?post_type=' . self::CPT));
        exit;
    }
}

new Site_Alert_Bar_Plugin();