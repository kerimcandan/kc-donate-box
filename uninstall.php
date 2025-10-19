<?php
// Exit if accessed directly or if not triggered by WP uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * KC Donate Box — uninstall cleanup
 *
 * Delete options:
 * - kcdobo_options        (new)
 * - kc_donate_box_opts    (legacy-newer)
 * - kc_support_box_opts   (legacy-older)
 */

$option_keys = array(
    'kcdobo_options',
    'kc_donate_box_opts',
    'kc_support_box_opts',
);

// Single-site cleanup
foreach ( $option_keys as $key ) {
    delete_option( $key );
}

// Multisite cleanup (per-site options)
if ( is_multisite() ) {
    $sites = get_sites( array( 'fields' => 'ids' ) );
    foreach ( $sites as $site_id ) {
        switch_to_blog( $site_id );
        foreach ( $option_keys as $key ) {
            delete_option( $key );
        }
        restore_current_blog();
    }
}
