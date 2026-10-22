<?php
/**
 * Plugin Name: Point One - State ACF CSV Importer
 * Description: Import and export selected ACF content for Point One State pages. Includes CSV preview, all-or-nothing validation, and rollback backups.
 * Version: 1.2.0
 * Author: Point One / Webmaster Tools
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) { exit; }

/** Scope deliberately limited to the States post type and these ACF fields. */
const PONCSV_PAGE = 'pon-state-acf-csv';
const PONCSV_BACKUPS = 'poncsv_backups_index';

function poncsv_columns() {
    $columns = ['slug', 'state', 'hero_text', 'location_headline'];
    for ($n = 1; $n <= 6; $n++) {
        $columns[] = "faq_{$n}_question";
        $columns[] = "faq_{$n}_answer";
    }
    return $columns;
}

add_action('admin_menu', function () {
    add_management_page('Point One: CSV ACF', 'Point One: CSV ACF', 'manage_options', PONCSV_PAGE, 'poncsv_screen');
});

function poncsv_admin_url($args = []) {
    return add_query_arg(array_merge(['page' => PONCSV_PAGE], $args), admin_url('tools.php'));
}

function poncsv_notice($message, $type = 'error') {
    echo '<div class="notice notice-' . esc_attr($type) . '"><p>' . esc_html($message) . '</p></div>';
}

function poncsv_file_rows($file) {
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return new WP_Error('upload', 'No valid CSV file was uploaded.');
    }
    if (!empty($file['error']) || (int) $file['size'] > 3 * 1024 * 1024 || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        return new WP_Error('upload', 'Upload a UTF-8 .csv file no larger than 3 MB.');
    }
    $handle = fopen($file['tmp_name'], 'rb');
    if (!$handle) { return new WP_Error('csv', 'Unable to open the CSV file.'); }
    $first = fgets($handle);
    if ($first === false) { fclose($handle); return new WP_Error('csv', 'The CSV file is empty.'); }
    rewind($handle);
    // Excel can export CSV using commas or semicolons depending on locale.
    $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
    $headers = fgetcsv($handle, 1048576, $delimiter, '"', '');
    if (!$headers) { fclose($handle); return new WP_Error('csv', 'CSV column headers are empty.'); }
    $headers = array_map(function ($v) { return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $v)); }, $headers);
    if (count(array_unique($headers)) !== count($headers)) { fclose($handle); return new WP_Error('csv', 'Duplicate CSV column headers were found.'); }
    $unknown = array_diff($headers, poncsv_columns());
    if ($unknown || !in_array('slug', $headers, true)) {
        fclose($handle);
        return new WP_Error('csv', 'Unknown CSV columns or missing required slug column: ' . implode(', ', $unknown));
    }
    $result = [];
    $i = 1;
    while (($data = fgetcsv($handle, 1048576, $delimiter, '"', '')) !== false) {
        $i++;
        if (count($data) === 1 && trim((string)$data[0]) === '') { continue; }
        if (count($data) !== count($headers)) {
            fclose($handle);
            return new WP_Error('csv', "Row {$i}: incorrect number of CSV columns (check quotation marks and line breaks).");
        }
        $row = array_combine($headers, $data);
        $result[] = $row;
        if (count($result) > 200) { fclose($handle); return new WP_Error('csv', 'The maximum is 200 states per CSV file.'); }
    }
    fclose($handle);
    return $result;
}

function poncsv_placeholder($text) {
    return (bool) preg_match('/\[\[|\b(PENDIENTE|REVISAR|TODO|PLACEHOLDER)\b/i', (string) $text);
}

function poncsv_prepare($rows) {
    $valid = [];
    $errors = [];
    $seen = [];
    foreach ($rows as $index => $raw) {
        $line = $index + 2;
        $slug = sanitize_title(trim((string) ($raw['slug'] ?? '')));
        if ($slug === '' || isset($seen[$slug])) {
            $errors[] = "Row {$line}: missing or duplicate slug.";
            continue;
        }
        $seen[$slug] = true;
        $post = get_page_by_path($slug, OBJECT, 'states');
        if (!$post || $post->post_type !== 'states' || $post->post_status !== 'publish') {
            $errors[] = "Row {$line}: no published State page exists for slug {$slug}.";
            continue;
        }
        if (isset($raw['state']) && trim($raw['state']) !== '' && strcasecmp(trim($raw['state']), get_the_title($post)) !== 0) {
            $errors[] = "Row {$line}: state name '{$raw['state']}' does not match the existing State page '{$post->post_title}'.";
            continue;
        }
        $changes = [];
        foreach (['hero_text', 'location_headline'] as $field) {
            if (!array_key_exists($field, $raw)) { continue; }
            $value = trim((string) $raw[$field]);
            if ($value === '') { continue; } // blanks ALWAYS mean leave unchanged.
            if (poncsv_placeholder($value)) {
                $errors[] = "Row {$line}: a pending placeholder was found in {$field}.";
                continue 2;
            }
            $changes[$field] = $value === '__CLEAR__' ? '' : ($field === 'hero_text' ? wp_kses_post($value) : sanitize_text_field($value));
        }
        $faq_supplied = false;
        for ($n = 1; $n <= 6; $n++) {
            if (trim((string) ($raw["faq_{$n}_question"] ?? '')) !== '' || trim((string) ($raw["faq_{$n}_answer"] ?? '')) !== '') {
                $faq_supplied = true; break;
            }
        }
        if ($faq_supplied) {
            $faqs = [];
            $trailing_blank = false;
            for ($n = 1; $n <= 6; $n++) {
                $q = trim((string) ($raw["faq_{$n}_question"] ?? ''));
                $a = trim((string) ($raw["faq_{$n}_answer"] ?? ''));
                if ($q === '' && $a === '') {
                    $trailing_blank = true;
                    continue; // Permit an empty FAQ 6 for states with five approved FAQs.
                }
                if ($trailing_blank || $q === '' || $a === '' || poncsv_placeholder($q) || poncsv_placeholder($a)) {
                    $errors[] = "Row {$line}: incomplete FAQs, gaps or unresolved placeholders (FAQ {$n}). This state will not be updated.";
                    continue 2;
                }
                $faqs[] = [
                    'faq_question' => sanitize_text_field($q),
                    'faq_answer' => wp_kses_post($a),
                ];
            }
            if (count($faqs) < 5 || count($faqs) > 6) {
                $errors[] = "Row {$line}: provide exactly five or six complete FAQs without gaps.";
                continue;
            }
            $changes['location_specific_faq'] = $faqs;
        }
        if (!$changes) { continue; }
        $different = [];
        foreach ($changes as $field => $value) {
            $current = get_field($field, $post->ID, false);
            if (wp_json_encode($current) !== wp_json_encode($value)) { $different[$field] = $value; }
        }
        if ($different) {
            $valid[] = ['id' => (int) $post->ID, 'state' => get_the_title($post), 'slug' => $slug, 'changes' => $different];
        }
    }
    return ['valid' => $valid, 'errors' => $errors];
}

function poncsv_screen() {
    if (!current_user_can('manage_options')) { wp_die('Access denied.'); }
    $result = null;
    $token = '';
    if (isset($_POST['poncsv_preview'])) {
        check_admin_referer('poncsv_preview');
        $parsed = poncsv_file_rows($_FILES['poncsv_file'] ?? []);
        $result = is_wp_error($parsed) ? $parsed : poncsv_prepare($parsed);
        if (!is_wp_error($result) && !empty($result['valid']) && empty($result['errors'])) {
            $token = wp_generate_password(22, false, false);
            set_transient('poncsv_preview_' . get_current_user_id() . '_' . $token, $result, 25 * MINUTE_IN_SECONDS);
        }
    }
    echo '<div class="wrap"><h1>Point One · State ACF CSV Importer</h1>';
    echo '<p><strong>Current website:</strong> ' . esc_html(home_url('/')) . '</p>';
    echo '<p>Only updates <code>hero_text</code>, <code>location_headline</code> and/or five or six rows in the ACF repeater <code>location_specific_faq</code>. A missing column or blank cell leaves existing content unchanged; <code>__CLEAR__</code> clears an individual text field. Coordinates, competitors, SEO settings and other post types are never changed.</p>';
    echo '<p><strong>Safety:</strong> preview before import, then confirm. A rollback backup is saved to this WordPress database before any changes are applied.</p>';
    echo '<p><a class="button" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=poncsv_export'), 'poncsv_export')) . '">Download current State inventory (CSV)</a> <span class="description">Read-only inventory; not an import file.</span></p>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('poncsv_preview');
    echo '<p><label><strong>UTF-8 CSV file: </strong><span class="button">Choose CSV file<input type="file" name="poncsv_file" accept=".csv,text/csv" required style="position:absolute;width:1px;height:1px;opacity:0" onchange="this.closest(\'label\').querySelector(\'.poncsv-filename\').textContent=this.files.length?this.files[0].name:\'No file selected\'"></span> <span class="poncsv-filename" aria-live="polite">No file selected</span></label> ';
    submit_button('1 · Preview CSV (no changes)', 'secondary', 'poncsv_preview', false);
    echo '</p></form>';
    if (is_wp_error($result)) {
        poncsv_notice($result->get_error_message());
    } elseif (is_array($result)) {
        echo '<h2>Import preview</h2>';
        if ($result['errors']) {
            poncsv_notice(count($result['errors']) . ' rows have errors. No changes can be applied until all errors are fixed.', 'warning');
            echo '<details><summary>Show errors (' . count($result['errors']) . ')</summary><ol>';
            foreach ($result['errors'] as $err) { echo '<li>' . esc_html($err) . '</li>'; }
            echo '</ol></details>';
        }
        echo '<p><strong>' . count($result['valid']) . '</strong> State pages have valid changes. Imports are blocked if any CSV row contains errors.</p>';
        if ($result['valid']) {
            echo '<table class="widefat striped"><thead><tr><th>State</th><th>ACF fields to update</th></tr></thead><tbody>';
            foreach ($result['valid'] as $entry) {
                $names = array_keys($entry['changes']);
                echo '<tr><td>' . esc_html($entry['state']) . '</td><td>' . esc_html(implode(', ', $names)) . '</td></tr>';
            }
            echo '</tbody></table>';
            if (empty($result['errors'])) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin-top:18px">';
            wp_nonce_field('poncsv_apply');
            echo '<input type="hidden" name="action" value="poncsv_apply"><input type="hidden" name="preview_token" value="' . esc_attr($token) . '">';
            echo '<p><label><input type="checkbox" required name="checked_staging" value="1"> I checked the website and reviewed the changes. This is the correct environment.</label></p>';
            echo '<p><label>Type <strong>IMPORT</strong> to confirm: <input type="text" name="confirmation" autocomplete="off" required></label></p>';
            submit_button('2 · Apply validated changes', 'primary');
            echo '</form>';
            }
        }
    }
    $backups = get_option(PONCSV_BACKUPS, []);
    if ($backups) {
        echo '<hr><h2>Backups and rollback</h2><table class="widefat striped"><thead><tr><th>Date (WordPress time)</th><th>States</th><th>Actions</th></tr></thead><tbody>';
        foreach (array_slice($backups, 0, 15) as $item) {
            echo '<tr><td>' . esc_html($item['date']) . '</td><td>' . (int) $item['count'] . '</td><td>';
            echo '<a class="button button-small" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=poncsv_backup_download&id=' . rawurlencode($item['id'])), 'poncsv_backup_download_' . $item['id'])) . '">Download JSON backup</a> ';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline" onsubmit="return confirm(\'Restore ACF content from this backup? Later edits to those specific fields will be overwritten.\');">';
            wp_nonce_field('poncsv_restore_' . $item['id']);
            echo '<input type="hidden" name="action" value="poncsv_restore"><input type="hidden" name="id" value="' . esc_attr($item['id']) . '">';
            echo '<button class="button button-small">Restore</button></form></td></tr>';
        }
        echo '</tbody></table>';
    }
    if (isset($_GET['poncsv_message'])) {
        $message = sanitize_text_field(wp_unslash($_GET['poncsv_message']));
        poncsv_notice($message, 'success');
    }
    echo '</div>';
}

add_action('admin_post_poncsv_apply', function () {
    if (!current_user_can('manage_options')) { wp_die('Insufficient permissions.'); }
    check_admin_referer('poncsv_apply');
    $token = sanitize_text_field(wp_unslash($_POST['preview_token'] ?? ''));
    if (empty($_POST['checked_staging']) || (string)($_POST['confirmation'] ?? '') !== 'IMPORT') {
        wp_die('Confirmation is incomplete.');
    }
    $key = 'poncsv_preview_' . get_current_user_id() . '_' . $token;
    $preview = get_transient($key);
    if (empty($preview['valid']) || !empty($preview['errors'])) { wp_die('Preview expired or failed validation. Upload the CSV again.'); }
    if (!function_exists('update_field')) { wp_die('ACF is not active.'); }
    delete_transient($key); // Avoid double submission.
    $records = [];
    foreach ($preview['valid'] as $entry) {
        $id = $entry['id'];
        if (get_post_type($id) !== 'states') { wp_die('A post type changed. The import was aborted.'); }
        $fields = [];
        foreach ($entry['changes'] as $field => $_value) {
            $fields[$field] = get_field($field, $id, false);
        }
        $records[] = ['id' => $id, 'slug' => $entry['slug'], 'fields' => $fields];
    }
    $backup_id = gmdate('YmdHis') . '_' . wp_generate_password(8, false, false);
    $backup = ['date' => current_time('mysql'), 'records' => $records];
    if (!add_option('poncsv_backup_' . $backup_id, $backup, '', false)) {
        wp_die('Unable to create the backup. No changes were applied.');
    }
    $index = get_option(PONCSV_BACKUPS, []);
    array_unshift($index, ['id' => $backup_id, 'date' => $backup['date'], 'count' => count($records)]);
    update_option(PONCSV_BACKUPS, array_slice($index, 0, 40), false);
    $success = 0;
    foreach ($preview['valid'] as $entry) {
        foreach ($entry['changes'] as $field => $value) {
            update_field($field, $value, $entry['id']);
        }
        // Republish the state so post_modified/dateModified reflect the new ACF content.
        wp_update_post(['ID' => $entry['id']]);
        $success++;
    }
    wp_safe_redirect(poncsv_admin_url(['poncsv_message' => "Updated {$success} State pages. A rollback backup was saved."]));
    exit;
});

add_action('admin_post_poncsv_restore', function () {
    if (!current_user_can('manage_options')) { wp_die('Insufficient permissions.'); }
    $id = sanitize_text_field(wp_unslash($_POST['id'] ?? ''));
    check_admin_referer('poncsv_restore_' . $id);
    $backup = get_option('poncsv_backup_' . $id, false);
    if (!$backup || empty($backup['records'])) { wp_die('Backup not found.'); }
    // Save another recovery point of the fields about to be overwritten.
    $before_restore = [];
    foreach ($backup['records'] as $item) {
        $values = [];
        foreach ($item['fields'] as $field => $_) { $values[$field] = get_field($field, $item['id'], false); }
        $before_restore[] = ['id' => $item['id'], 'slug' => $item['slug'], 'fields' => $values];
    }
    $restore_id = gmdate('YmdHis') . '_' . wp_generate_password(8, false, false);
    $restore_data = ['date' => current_time('mysql'), 'records' => $before_restore];
    if (!add_option('poncsv_backup_' . $restore_id, $restore_data, '', false)) { wp_die('Unable to save a rollback backup before restoring.'); }
    $index = get_option(PONCSV_BACKUPS, []);
    array_unshift($index, ['id' => $restore_id, 'date' => $restore_data['date'] . ' (before restore)', 'count' => count($before_restore)]);
    update_option(PONCSV_BACKUPS, array_slice($index, 0, 40), false);
    foreach ($backup['records'] as $item) {
        if (get_post_type($item['id']) !== 'states') { continue; }
        foreach ($item['fields'] as $field => $value) {
            update_field($field, $value, $item['id']);
        }
    }
    wp_safe_redirect(poncsv_admin_url(['poncsv_message' => 'Backup restored.']));
    exit;
});

add_action('admin_post_poncsv_backup_download', function () {
    if (!current_user_can('manage_options')) { wp_die('Insufficient permissions.'); }
    $id = sanitize_text_field(wp_unslash($_GET['id'] ?? ''));
    check_admin_referer('poncsv_backup_download_' . $id);
    $backup = get_option('poncsv_backup_' . $id, false);
    if (!$backup) { wp_die('Backup not found.'); }
    nocache_headers();
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="pon-states-acf-backup-' . $id . '.json"');
    echo wp_json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
});

add_action('admin_post_poncsv_export', function () {
    if (!current_user_can('manage_options')) { wp_die('Insufficient permissions.'); }
    check_admin_referer('poncsv_export');
    if (!function_exists('get_field')) { wp_die('ACF is not active.'); }
    $posts = get_posts(['post_type' => 'states', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="pointone-states-acf-inventory-' . gmdate('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    if (!$out) { exit; }
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['post_id', 'slug', 'state', 'hero_text', 'location_headline', 'existing_faq_json', 'competitor_names'], ',', '"', '');
    foreach ($posts as $post) {
        $faqs = get_field('location_specific_faq', $post->ID);
        $competitors = get_field('competitors', $post->ID);
        $names = [];
        foreach ((array)$competitors as $comp) {
            if ($comp instanceof WP_Post) { $names[] = $comp->post_title; }
            elseif (is_numeric($comp)) { $names[] = get_the_title((int)$comp); }
        }
        fputcsv($out, [
            $post->ID, $post->post_name, $post->post_title,
            (string)get_field('hero_text', $post->ID, false),
            (string)get_field('location_headline', $post->ID, false),
            wp_json_encode($faqs, JSON_UNESCAPED_UNICODE),
            implode(' | ', $names),
        ], ',', '"', '');
    }
    fclose($out);
    exit;
});
