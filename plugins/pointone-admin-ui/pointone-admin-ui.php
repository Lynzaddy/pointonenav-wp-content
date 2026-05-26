<?php

/**
 * Plugin Name: Point One Nav - Admin UI
 * Description: Organizes the WordPress admin menu for Point One custom CMS tools.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;


/*--------------------------------------------------------------
ADMIN MENU ORGANIZATION
--------------------------------------------------------------*/

/**
 * This plugin centralizes the WordPress admin menu order.
 *
 * Custom Point One CMS items are grouped together after Pages,
 * alphabetized, and separated visually from the rest of WordPress.
 */

add_filter('custom_menu_order', '__return_true');

add_filter('menu_order', function ($menu_order) {

    return [

        /* Core WordPress content */
        'index.php',                        // Dashboard
        'edit.php',                         // Posts
        'upload.php',                       // Media
        'edit.php?post_type=page',          // Pages

        'separator1',

        /* Point One CMS */
        'edit.php?post_type=change_log',    // Change Log
        'edit.php?post_type=competitor',    // Competitors
        'edit.php?post_type=event',         // Events
        'edit.php?post_type=faq',           // FAQs
        'edit.php?post_type=gnss_term',     // GNSS Terms
        'admin.php?page=global-variables',  // Global Variables
        'edit.php?post_type=site_alert',    // Site Alerts
        'edit.php?post_type=state',         // States

        'separator2',

        /* Builder */
        'elementor',                        // Elementor

        'separator3',

        /* WordPress admin */
        'themes.php',                       // Appearance
        'plugins.php',                      // Plugins
        'users.php',                        // Users
        'tools.php',                        // Tools
        'options-general.php',              // Settings

    ];
});


/*--------------------------------------------------------------
ADMIN SEPARATOR CLEANUP
--------------------------------------------------------------*/

/**
 * WordPress already has native separators.
 * This makes sure our grouped layout has cleaner spacing.
 */

add_action('admin_menu', function () {

    global $menu;

    /**
     * Add a separator after Pages.
     */
    $menu[25] = [
        '',
        'read',
        'separator-pointone-before',
        '',
        'wp-menu-separator'
    ];

    /**
     * Add a separator after Point One CMS items.
     */
    $menu[59] = [
        '',
        'read',
        'separator-pointone-after',
        '',
        'wp-menu-separator'
    ];
}, 999);
