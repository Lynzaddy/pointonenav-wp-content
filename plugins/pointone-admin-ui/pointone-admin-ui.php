<?php

/**
 * Plugin Name: Point One Nav - Admin UI
 * Description: Creates a unified Point One admin menu and groups custom CMS tools under one parent menu.
 * Version: 2.1.0
 */

if (!defined('ABSPATH')) exit;


/*--------------------------------------------------------------
POINT ONE ADMIN MENU
--------------------------------------------------------------*/

add_action('admin_menu', function () {

    add_menu_page(
        'Point One',
        'Point One',
        'edit_posts',
        'pointone-cms',
        'pointone_admin_ui_redirect_to_first_item',
        'dashicons-admin-site-alt3',
        25
    );
}, 1);


/*--------------------------------------------------------------
POINT ONE MENU REDIRECT
--------------------------------------------------------------*/

function pointone_admin_ui_redirect_to_first_item()
{
    wp_safe_redirect(admin_url('admin.php?page=pointone-global-variables'));
    exit;
}


/*--------------------------------------------------------------
POINT ONE SUBMENU LINKS
--------------------------------------------------------------*/

add_action('admin_menu', function () {

    /**
     * Remove default duplicate parent submenu item.
     */
    remove_submenu_page('pointone-cms', 'pointone-cms');

    /**
     * Alphabetical order.
     */
    add_submenu_page(
        'pointone-cms',
        'Change Log',
        'Change Log',
        'edit_posts',
        'edit.php?post_type=change_log'
    );

    add_submenu_page(
        'pointone-cms',
        'Competitors',
        'Competitors',
        'edit_posts',
        'edit.php?post_type=competitor'
    );

    add_submenu_page(
        'pointone-cms',
        'Events',
        'Events',
        'edit_posts',
        'edit.php?post_type=event'
    );

    add_submenu_page(
        'pointone-cms',
        'FAQs',
        'FAQs',
        'edit_posts',
        'edit.php?post_type=faq'
    );

    add_submenu_page(
        'pointone-cms',
        'Global Variables',
        'Global Variables',
        'manage_options',
        'pointone-global-variables'
    );

    add_submenu_page(
        'pointone-cms',
        'GNSS Terms',
        'GNSS Terms',
        'edit_posts',
        'edit.php?post_type=gnss_term'
    );

    add_submenu_page(
        'pointone-cms',
        'Site Alerts',
        'Site Alerts',
        'edit_posts',
        'edit.php?post_type=site_alert'
    );

    add_submenu_page(
        'pointone-cms',
        'States',
        'States',
        'edit_posts',
        'edit.php?post_type=state'
    );
}, 99);


/*--------------------------------------------------------------
REMOVE DUPLICATE TOP-LEVEL MENUS
--------------------------------------------------------------*/

add_action('admin_menu', function () {

    remove_menu_page('edit.php?post_type=change_log');
    remove_menu_page('edit.php?post_type=competitor');
    remove_menu_page('edit.php?post_type=event');
    remove_menu_page('edit.php?post_type=faq');
    remove_menu_page('edit.php?post_type=gnss_term');
    remove_menu_page('edit.php?post_type=site_alert');
    remove_menu_page('edit.php?post_type=state');
}, 999);


/*--------------------------------------------------------------
ADMIN MENU ORDER + SPACERS
--------------------------------------------------------------*/

add_action('admin_menu', function () {

    global $menu;

    /**
     * Add spacers before and after Point One.
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
}, 9998);


add_filter('custom_menu_order', '__return_true');

add_filter('menu_order', function () {

    return [
        'index.php',
        'edit.php',
        'upload.php',
        'edit.php?post_type=page',

        'separator-pointone-before',

        'pointone-cms',

        'separator-pointone-after',

        'elementor',
        'themes.php',
        'plugins.php',
        'users.php',
        'tools.php',
        'options-general.php',
    ];
});
