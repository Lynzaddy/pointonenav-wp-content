<?php
/**
 * Plugin Name: Point One - Card Carousel
 * Description: Auto-detects slider HTML in page content and enqueues Slick + custom JS/CSS. No shortcode required. Back-compat shortcode is available.
 * Version:     0.4.1
 * Author:      Point One Navigation
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'PON_CC_VER',  '0.4.1' );
define( 'PON_CC_URL',  plugin_dir_url( __FILE__ ) );
define( 'PON_CC_PATH', plugin_dir_path( __FILE__ ) );

/* ---------- Assets: register (do not enqueue yet) ---------- */
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
add_action( 'wp_enqueue_scripts', 'pon_cc_register_assets' );

/* ---------- Helper: enqueue now ---------- */
function pon_cc_enqueue_assets_now() {
	wp_enqueue_style( 'pon-cc' );   // pulls slick + theme via deps
	wp_enqueue_script( 'pon-cc' );  // pulls slick via dep
}

/* ---------- Detect slider markup in HTML ---------- */
function pon_cc_contains_slider( $html ) {
	if ( empty( $html ) || ! is_string( $html ) ) {
		return false;
	}
	// Look for class="... slider ... center|responsive ..."
	$pattern = '/class\s*=\s*["\'][^"\']*\bslider\b[^"\']*\b(center|responsive)\b[^"\']*["\']/i';
	return (bool) preg_match( $pattern, $html );
}

/* ---------- Auto-enqueue on /components/carousel/ ---------- */
function pon_cc_auto_enqueue_test_route() {
	if ( is_admin() ) {
		return;
	}
	$req = isset( $_SERVER['REQUEST_URI'] ) ? strtolower( $_SERVER['REQUEST_URI'] ) : '';
	if ( $req === '' ) {
		return;
	}
	$pos = strpos( $req, '?' );
	if ( $pos !== false ) {
		$req = substr( $req, 0, $pos );
	}
	if ( preg_match( '#/components/carousel/?$#', $req ) ) {
		add_action( 'wp_enqueue_scripts', 'pon_cc_enqueue_assets_now', 99 );
	}
}
add_action( 'wp', 'pon_cc_auto_enqueue_test_route' );

/* ---------- Scan classic content (the_content) ---------- */
function pon_cc_scan_the_content( $content ) {
	if ( is_admin() ) {
		return $content;
	}
	if ( is_singular() && pon_cc_contains_slider( $content ) ) {
		add_action( 'wp_enqueue_scripts', 'pon_cc_enqueue_assets_now', 99 );
	}
	return $content;
}
add_filter( 'the_content', 'pon_cc_scan_the_content', 1 );

/* ---------- Elementor: scan each widget render ---------- */
function pon_cc_elementor_bootstrap() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		return;
	}

	if ( ! function_exists( 'pon_cc_elementor_render_content' ) ) {
		function pon_cc_elementor_render_content( $content, $widget ) {
			if ( is_admin() ) {
				return $content;
			}
			if ( pon_cc_contains_slider( $content ) ) {
				add_action( 'wp_enqueue_scripts', 'pon_cc_enqueue_assets_now', 99 );
			}
			return $content;
		}
	}

	add_filter( 'elementor/widget/render_content', 'pon_cc_elementor_render_content', 10, 2 );
}
add_action( 'plugins_loaded', 'pon_cc_elementor_bootstrap' );

/* ---------- Optional shortcode (not required) ---------- */
function pon_cc_shortcode_assets() {
	if ( ! is_admin() ) {
		pon_cc_enqueue_assets_now();
	}
	return '';
}
add_shortcode( 'pon_card_carousel_assets', 'pon_cc_shortcode_assets' );
