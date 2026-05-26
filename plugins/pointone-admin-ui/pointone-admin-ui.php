<?php

/**
 * Plugin Name: Point One Nav - Admin UI
 * Description: Creates a unified Point One admin menu and groups custom CMS tools under one parent menu.
 * Version: 2.8.0
 */

if (!defined('ABSPATH')) exit;


/*--------------------------------------------------------------
POINT ONE SVG ICON
--------------------------------------------------------------*/

function pointone_admin_ui_icon()
{
    return 'data:image/svg+xml;base64,' . base64_encode('
    <svg width="20" height="20" viewBox="15 15 41 42" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M44.9902 48.0251L38.5176 54.5016C36.9437 56.0759 34.3932 56.0758 32.8193 54.5016L26.3467 48.0251L28.7177 45.652L35.1904 52.1286C35.4549 52.393 35.882 52.393 36.1465 52.1286L42.6191 45.652L44.9902 48.0251ZM26.0234 29.0456L19.5508 35.5222C19.2868 35.7865 19.2868 36.2158 19.5508 36.4802L26.0234 42.9567L23.6533 45.3288L17.1806 38.8522C15.6071 37.2776 15.6071 34.7247 17.1806 33.1501L23.6533 26.6735L26.0234 29.0456ZM54.1562 33.1501C55.7298 34.7248 55.7298 37.2776 54.1562 38.8522L47.6845 45.3288L45.3135 42.9567L51.7861 36.4792C52.0497 36.2148 52.0499 35.7864 51.7861 35.5222L45.3135 29.0456L47.6845 26.6725L54.1562 33.1501ZM39.1592 42.0212H35.8056V34.7809C34.8287 35.3454 33.6948 35.6686 32.4844 35.6686V32.3122C34.307 32.3122 35.791 30.8305 35.791 28.987H39.1592V42.0212ZM31.1308 38.111C32.171 38.111 33.0145 38.9573 33.0146 40.0016C33.0146 41.0461 32.1711 41.8933 31.1308 41.8933C30.0906 41.8932 29.247 41.0461 29.247 40.0016C29.2472 38.9573 30.0907 38.111 31.1308 38.111ZM32.8193 17.5007C34.3932 15.9261 36.9437 15.9261 38.5176 17.5007L44.9902 23.9772L42.6191 26.3503L36.1465 19.8727C35.8821 19.6089 35.4548 19.6089 35.1904 19.8727L28.7177 26.3503L26.3467 23.9772L32.8193 17.5007Z" fill="black"/>
    </svg>
    ');
}


/*--------------------------------------------------------------
POINT ONE POST TYPES
--------------------------------------------------------------*/

function pointone_admin_ui_post_types(): array
{
    return [
        'change_log',
        'competitor',
        'event',
        'faq',
        'gnss_term',
        'site_alert',
        'state',
    ];
}


/*--------------------------------------------------------------
POINT ONE SCREEN CHECK
--------------------------------------------------------------*/

function pointone_admin_ui_is_pointone_screen(): bool
{
    $post_type = $_GET['post_type'] ?? '';

    if (!$post_type && isset($_GET['post'])) {
        $post_type = get_post_type((int) $_GET['post']);
    }

    if ($post_type && in_array($post_type, pointone_admin_ui_post_types(), true)) {
        return true;
    }

    if (isset($_GET['page']) && $_GET['page'] === 'pointone-global-variables') {
        return true;
    }

    return false;
}


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
        pointone_admin_ui_icon(),
        25
    );
}, 1);


/*--------------------------------------------------------------
POINT ONE MENU REDIRECT
--------------------------------------------------------------*/

function pointone_admin_ui_redirect_to_first_item()
{
    wp_safe_redirect(admin_url('edit.php?post_type=change_log'));
    exit;
}


/*--------------------------------------------------------------
POINT ONE SUBMENU LINKS
--------------------------------------------------------------*/

add_action('admin_menu', function () {

    global $submenu;

    remove_submenu_page('pointone-cms', 'pointone-cms');

    if (isset($submenu['pointone-cms'])) {
        foreach ($submenu['pointone-cms'] as $index => $item) {
            if (isset($item[2]) && $item[2] === 'pointone-global-variables') {
                unset($submenu['pointone-cms'][$index]);
            }
        }
    }

    add_submenu_page('pointone-cms', 'Change Log', 'Change Log', 'edit_posts', 'edit.php?post_type=change_log');
    add_submenu_page('pointone-cms', 'Competitors', 'Competitors', 'edit_posts', 'edit.php?post_type=competitor');
    add_submenu_page('pointone-cms', 'Events', 'Events', 'edit_posts', 'edit.php?post_type=event');
    add_submenu_page('pointone-cms', 'FAQs', 'FAQs', 'edit_posts', 'edit.php?post_type=faq');
    add_submenu_page('pointone-cms', 'Global Variables', 'Global Variables', 'manage_options', 'pointone-global-variables');
    add_submenu_page('pointone-cms', 'GNSS Terms', 'GNSS Terms', 'edit_posts', 'edit.php?post_type=gnss_term');
    add_submenu_page('pointone-cms', 'Site Alerts', 'Site Alerts', 'edit_posts', 'edit.php?post_type=site_alert');
    add_submenu_page('pointone-cms', 'States', 'States', 'edit_posts', 'edit.php?post_type=state');
}, 99);


/*--------------------------------------------------------------
KEEP POINT ONE MENU ACTIVE ON CHILD SCREENS
--------------------------------------------------------------*/

add_filter('parent_file', function ($parent_file) {

    if (pointone_admin_ui_is_pointone_screen()) {
        return 'pointone-cms';
    }

    return $parent_file;
});


add_filter('submenu_file', function ($submenu_file) {

    $post_type = $_GET['post_type'] ?? '';

    if (!$post_type && isset($_GET['post'])) {
        $post_type = get_post_type((int) $_GET['post']);
    }

    if ($post_type && in_array($post_type, pointone_admin_ui_post_types(), true)) {
        return 'edit.php?post_type=' . $post_type;
    }

    if (isset($_GET['page']) && $_GET['page'] === 'pointone-global-variables') {
        return 'pointone-global-variables';
    }

    return $submenu_file;
});


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

    $menu[] = ['', 'read', 'separator-pointone-before', '', 'wp-menu-separator'];
    $menu[] = ['', 'read', 'separator-pointone-after', '', 'wp-menu-separator'];
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


/*--------------------------------------------------------------
FORCE POINT ONE MENU OPEN VISUALLY
--------------------------------------------------------------*/

add_action('admin_head', function () {

    if (!pointone_admin_ui_is_pointone_screen()) return;

?>
    <style>
        #adminmenu #toplevel_page_pointone-cms {
            background: #2271b1;
        }

        #adminmenu #toplevel_page_pointone-cms>a {
            background: #2271b1;
            color: #fff;
            position: relative;
        }

        #adminmenu #toplevel_page_pointone-cms>a::after {
            content: "";
            position: absolute;
            top: 50%;
            right: 0;
            transform: translateY(-50%);
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
            border-right: 8px solid #f0f0f1;
        }

        #adminmenu #toplevel_page_pointone-cms>a .wp-menu-name,
        #adminmenu #toplevel_page_pointone-cms>a .wp-menu-image::before {
            color: #fff;
        }

        #adminmenu #toplevel_page_pointone-cms.wp-not-current-submenu .wp-submenu,
        #adminmenu #toplevel_page_pointone-cms .wp-submenu {
            position: relative;
            top: auto;
            left: auto;
            right: auto;
            bottom: auto;
            display: block;
            width: auto;
            min-width: 0;
            margin: 0;
            padding: 7px 0 8px;
            box-shadow: none;
            background: #2c3338;
        }

        #adminmenu #toplevel_page_pointone-cms .wp-submenu li a {
            padding-left: 34px;
        }
    </style>
<?php
});
