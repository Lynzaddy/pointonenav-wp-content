<?php

/**
 * Plugin Name: Point One Nav - Admin UI
 * Description: Organizes the WordPress admin menu for Point One custom CMS tools.
 * Version: 1.2.0
 */

if (!defined('ABSPATH')) exit;


/*--------------------------------------------------------------
ADMIN MENU ORGANIZATION
--------------------------------------------------------------*/

add_action('admin_menu', function () {

    global $menu;

    /**
     * Desired top-level admin order.
     *
     * These slugs must match the actual menu slugs WordPress uses.
     */
    $desired_order = [

        'index.php',                        // Dashboard
        'edit.php',                         // Posts
        'upload.php',                       // Media
        'edit.php?post_type=page',          // Pages

        'separator-pointone-before',

        'edit.php?post_type=change_log',    // Change Log
        'edit.php?post_type=competitor',    // Competitors
        'edit.php?post_type=event',         // Events
        'edit.php?post_type=faq',           // FAQs
        'edit.php?post_type=gnss_term',     // GNSS Terms
        'edit.php?post_type=site_alert',    // Site Alerts
        'edit.php?post_type=state',         // States

        'separator-pointone-after',

        'elementor',                        // Elementor

        'separator-wordpress-admin',

        'themes.php',                       // Appearance
        'plugins.php',                      // Plugins
        'users.php',                        // Users
        'tools.php',                        // Tools
        'options-general.php',              // Settings
    ];

    /**
     * Add our custom separators.
     */
    $menu[] = [
        '',
        'read',
        'separator-pointone-before',
        '',
        'wp-menu-separator'
    ];

    $menu[] = [
        '',
        'read',
        'separator-pointone-after',
        '',
        'wp-menu-separator'
    ];

    $menu[] = [
        '',
        'read',
        'separator-wordpress-admin',
        '',
        'wp-menu-separator'
    ];

    /**
     * Rebuild menu based on desired order.
     */
    $ordered_menu = [];

    foreach ($desired_order as $slug) {

        foreach ($menu as $index => $item) {

            if (!isset($item[2])) continue;

            if ($item[2] === $slug) {
                $ordered_menu[] = $item;
                unset($menu[$index]);
                break;
            }
        }
    }

    /**
     * Append anything not explicitly listed.
     * This prevents plugin/admin items from disappearing.
     */
    foreach ($menu as $item) {
        $ordered_menu[] = $item;
    }

    $menu = $ordered_menu;
}, 9999);
