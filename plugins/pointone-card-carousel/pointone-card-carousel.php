<?php
/**
 * Plugin Name: Point One Card Carousel
 * Description: Reusable Swiper carousel powered by ACF + Elementor widget (Point One).
 * Version: 1.0.0
 * Author: Point One
 * Text Domain: pointone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'POC_PATH', plugin_dir_path( __FILE__ ) );
define( 'POC_URL',  plugin_dir_url( __FILE__ ) );

add_action('plugins_loaded', function () {
    // Require ACF
    if ( ! class_exists('ACF') ) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Point One Card Carousel</strong> requires Advanced Custom Fields.</p></div>';
        });
        return;
    }

    require_once POC_PATH . 'inc/cpt.php';
    require_once POC_PATH . 'inc/acf-fields.php';
    require_once POC_PATH . 'inc/shortcode.php';
    require_once POC_PATH . 'inc/elementor-widget.php';
});

// Assets
add_action('wp_enqueue_scripts', function () {
    // Swiper via CDN (simple + reliable). Swap to vendor file if you prefer.
    wp_enqueue_style('poc-swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css', [], '10');
    wp_enqueue_script('poc-swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js', [], '10', true);

    // Init script
    wp_enqueue_script('poc-init-swiper', POC_URL . 'assets/js/init-swiper.js', ['poc-swiper-js'], '1.0.0', true);
});
