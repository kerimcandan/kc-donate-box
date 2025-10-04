<?php
// Run only when WordPress triggers uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * KC Donate Box - uninstall cleanup
 *
 * - Delete options:
 *   - kc_donate_box_opts   (current)
 *   - kc_support_box_opts  (legacy)
 * - No CPT/tables to drop.
 */

// Safety: load WP if needed (WordPress does this for us during uninstall)

// Remove options
delete_option('kc_donate_box_opts');
delete_option('kc_support_box_opts');

// If multisite and options were saved per-site, also iterate blogs (optional):
if (is_multisite()) {
    $sites = get_sites(array('fields' => 'ids'));
    foreach ($sites as $site_id) {
        switch_to_blog($site_id);
        delete_option('kc_donate_box_opts');
        delete_option('kc_support_box_opts');
        restore_current_blog();
    }
}
