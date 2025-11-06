## Platform

-   Confirm this with Dev today. Figma has h16, we are changing to h20, Text Widget Small - Heading (H20 from Figma)
-   Check buttons in Data / API (Staging)
-   Products - Mobile - Text Link needs to be Learn More and link to the same page
-   Products - Mobile - 20 Instrument Sans / Weight Bold
-   Product - RTK - Make the stats have the left line
-   Customer Stories - apply center class
-   Are the Contact Sales boxes on Homepage / Platform
-   Check for consistent padding after last component before footer
-   Products Check above each one to remove padding (it's 20px padding making the top 80px)
-   Check the sections in Solutions (padding)

## Homepage

### Trusted By

-   (X) "Trusted By" needs to go to Case Studies
-   Make sure images are all the same size, physically, or enforce the standard image size

### Blog

-   Blog - Spacing vertical on mobile; Aspect Ratio on Images

## Slider

-   Check padding on slider when built

## Header

-   On mobile, Transparent header, add class to new component hero image (it's always white, never transparent)

## Questions

-   On Figma, the inactive carousel on the homepage looks like dots now in Figma. We had dashes before.

[01-Nov-2025 17:24:54 UTC] Cron reschedule event error for hook: action_scheduler_run_queue, Error code: invalid_schedule, Error message: Event schedule does not exist., Data: {"schedule":"every_minute","args":["WP Cron"],"interval":60}
[01-Nov-2025 17:24:54 UTC] Cron reschedule event error for hook: breeze_purge_cache, Error code: invalid_schedule, Error message: Event schedule does not exist., Data: {"schedule":"breeze_varnish_time","args":[],"interval":300}
[01-Nov-2025 17:24:57 UTC] Cron reschedule event error for hook: imagify_optimize_media_cron, Error code: invalid_schedule, Error message: Event schedule does not exist., Data: {"schedule":"imagify_optimize_media_cron_interval","args":[],"interval":300}
[01-Nov-2025 17:34:03 UTC] PHP Warning: require(/Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/includes/plugin.php): Failed to open stream: No such file or directory in /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/elementor.php on line 62
[01-Nov-2025 17:34:03 UTC] PHP Fatal error: Uncaught Error: Failed opening required '/Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/includes/plugin.php' (include_path='.:/usr/share/php:/www/wp-content/pear') in /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/elementor.php:62
Stack trace:
#0 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-settings.php(545): include_once()
#1 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-config.php(103): require_once('/Users/lynzaddy...')
#2 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-load.php(50): require_once('/Users/lynzaddy...')
#3 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-admin/admin-ajax.php(22): require_once('/Users/lynzaddy...')
#4 {main}
thrown in /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/elementor.php on line 62
[01-Nov-2025 17:36:04 UTC] PHP Fatal error: Uncaught Error: Class "Elementor\Data\V2\Manager" not found in /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/includes/plugin.php:825
Stack trace:
#0 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/includes/plugin.php(600): Elementor\Plugin->**construct()
#1 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/includes/plugin.php(841): Elementor\Plugin::instance()
#2 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/elementor.php(62): require('/Users/lynzaddy...')
#3 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-settings.php(545): include_once('/Users/lynzaddy...')
#4 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-config.php(103): require_once('/Users/lynzaddy...')
#5 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-load.php(50): require_once('/Users/lynzaddy...')
#6 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-admin/admin-ajax.php(22): require_once('/Users/lynzaddy...')
#7 {main}
thrown in /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/includes/plugin.php on line 825
[01-Nov-2025 17:38:05 UTC] PHP Fatal error: Uncaught Error: Class "Elementor\Modules\History\Module" not found in /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/core/modules-manager.php:53
Stack trace:
#0 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/includes/plugin.php(709): Elementor\Core\Modules_Manager->**construct()
#1 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/includes/plugin.php(627): Elementor\Plugin->init_components()
#2 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-includes/class-wp-hook.php(324): Elementor\Plugin->init('')
#3 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-includes/class-wp-hook.php(348): WP_Hook->apply_filters(NULL, Array)
#4 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-includes/plugin.php(517): WP_Hook->do_action(Array)
#5 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-settings.php(727): do_action('init')
#6 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-config.php(103): require_once('/Users/lynzaddy...')
#7 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-load.php(50): require_once('/Users/lynzaddy...')
#8 /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-admin/admin-ajax.php(22): require_once('/Users/lynzaddy...')
#9 {main}
thrown in /Users/lynzaddy/Local Sites/pointonenav/app/public/wp-content/plugins/elementor/core/modules-manager.php on line 53
