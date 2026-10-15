<?php

/**
 * Plugin Name: Point One Nav - Card Carousel
 * Description: Loads Slick and the Point One carousel styles/scripts on every front-end page. Any Elementor container with the .slider class (plus .center or .tall) becomes a carousel. No shortcode required.
 * Version:     0.4.4
 * Author:      Point One Navigation
 */

if (! defined('ABSPATH')) exit;

define('PON_CC_VER', '0.4.4');
define('PON_CC_URL', plugin_dir_url(__FILE__));
define('PON_CC_PATH', plugin_dir_path(__FILE__));


/*--------------------------------------------------------------
REGISTER ASSETS
--------------------------------------------------------------*/

// Slick 1.8.1 is loaded from the jsDelivr CDN. This plugin is the only place Slick is loaded.
function pon_cc_register_assets()
{
	wp_register_style(
		'slick',
		'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css',
		[],
		'1.8.1'
	);
	wp_register_style(
		'slick-theme',
		'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css',
		['slick'],
		'1.8.1'
	);
	wp_register_script(
		'slick',
		'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js',
		['jquery'],
		'1.8.1',
		true
	);

	wp_register_style(
		'pon-cc',
		PON_CC_URL . 'assets/css/pointone-card-carousel.css',
		['slick', 'slick-theme'],
		PON_CC_VER
	);
	wp_register_script(
		'pon-cc',
		PON_CC_URL . 'assets/js/pointone-card-carousel.js',
		['jquery', 'slick'],
		PON_CC_VER,
		true
	);
}
add_action('wp_enqueue_scripts', 'pon_cc_register_assets', 5);


/*--------------------------------------------------------------
ENQUEUE ON ALL FRONT-END PAGES
--------------------------------------------------------------*/

// Dependencies pull in Slick automatically.
function pon_cc_enqueue_everywhere()
{
	if (is_admin()) return;

	wp_enqueue_style('pon-cc');
	wp_enqueue_script('pon-cc');
}
add_action('wp_enqueue_scripts', 'pon_cc_enqueue_everywhere', 50);


/*--------------------------------------------------------------
LEGACY SHORTCODE
--------------------------------------------------------------*/

// [pon_card_carousel_assets] is no longer needed, but is kept so any page
// that still contains it doesn't show the raw shortcode text.
function pon_cc_shortcode_assets()
{
	if (! is_admin()) {
		wp_enqueue_style('pon-cc');
		wp_enqueue_script('pon-cc');
	}
	return '';
}
add_shortcode('pon_card_carousel_assets', 'pon_cc_shortcode_assets');
