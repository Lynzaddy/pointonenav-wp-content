<?php
/**
 * Plugin Name: Point One Nav - Card Carousel
 * Description: Loads Slick + carousel assets on all front-end pages. No shortcode required.
 * Version:     0.4.3
 * Author:      Point One Navigation
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'PON_CC_VER',  '0.4.3' );
define( 'PON_CC_URL',  plugin_dir_url( __FILE__ ) );
define( 'PON_CC_PATH', plugin_dir_path( __FILE__ ) );

/* Register assets */
function pon_cc_register_assets() {
	// Slick CSS
	wp_register_style(
		'slick',
		'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css',
		array(),
		'1.8.1'
	);
	wp_register_style(
		'slick-theme',
		'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css',
		array( 'slick' ),
		'1.8.1'
	);

	// Slick JS
	wp_register_script(
		'slick',
		'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js',
		array( 'jquery' ),
		'1.8.1',
		true
	);

	// Plugin CSS + JS
	wp_register_style(
		'pon-cc',
		PON_CC_URL . 'assets/css/pointone-card-carousel.css',
		array( 'slick', 'slick-theme' ),
		PON_CC_VER
	);
	wp_register_script(
		'pon-cc',
		PON_CC_URL . 'assets/js/pointone-card-carousel.js',
		array( 'jquery', 'slick' ),
		PON_CC_VER,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'pon_cc_register_assets', 5 );

/* Enqueue globally on the front end */
function pon_cc_enqueue_everywhere() {
	if ( is_admin() ) return;

	wp_enqueue_style( 'pon-cc' );   // pulls slick + theme via dependencies
	wp_enqueue_script( 'pon-cc' );  // pulls slick via dependency
}
add_action( 'wp_enqueue_scripts', 'pon_cc_enqueue_everywhere', 50 );

/* Optional shortcode kept for compatibility (not required) */
function pon_cc_shortcode_assets() {
	if ( ! is_admin() ) {
		wp_enqueue_style( 'pon-cc' );
		wp_enqueue_script( 'pon-cc' );
	}
	return '';
}
add_shortcode( 'pon_card_carousel_assets', 'pon_cc_shortcode_assets' );
