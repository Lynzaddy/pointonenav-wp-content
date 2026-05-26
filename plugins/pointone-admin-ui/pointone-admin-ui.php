<?php

/**
 * Plugin Name: Point One Nav - Admin UI
 * Description: Creates a unified Point One admin menu and groups custom CMS tools under one parent menu.
 * Version: 2.0.0
 */

if (!defined('ABSPATH')) exit;


/*--------------------------------------------------------------
POINT ONE ADMIN PARENT MENU
--------------------------------------------------------------*/

add_action('admin_menu', function () {

    add_menu_page(
        'Point One',
        'Point One',
        'edit_posts',
        'pointone-cms',
        'pointone_admin_ui_parent_page',
        'dashicons-admin-site-alt3',
        25
    );
}, 1);


/*--------------------------------------------------------------
POINT ONE PARENT PAGE
--------------------------------------------------------------*/

function pointone_admin_ui_parent_page()
{
?>
    <div class="wrap">
        <h1>Point One</h1>
        <p>Use the submenu links to manage Point One content and tools.</p>
    </div>
<?php
}


/*--------------------------------------------------------------
POINT ONE SUBMENU LINKS
--------------------------------------------------------------*/

add_action('admin_menu', function () {

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
ADMIN MENU ORDER
--------------------------------------------------------------*/

add_filter('custom_menu_order', '__return_true');

add_filter('menu_order', function () {

    return [
        'index.php',
        'edit.php',
        'upload.php',
        'edit.php?post_type=page',
        'pointone-cms',
        'elementor',
        'themes.php',
        'plugins.php',
        'users.php',
        'tools.php',
        'options-general.php',
    ];
});
