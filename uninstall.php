<?php
// Exit if accessed directly or if not triggered by WP uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * KC Donate Box — uninstall cleanup
 * Removes all options created/used by the plugin.
 */

$option_keys = array(
	'kcdobo_options',
	'kc_donate_box_opts',
	'kc_support_box_opts',
);

if ( is_multisite() ) {
	$sites = get_sites( array( 'fields' => 'ids' ) );
	if ( $sites && is_array( $sites ) ) {
		$current_blog_id = get_current_blog_id();
		foreach ( $sites as $site_id ) {
			switch_to_blog( (int) $site_id );
			foreach ( $option_keys as $key ) {
				delete_option( $key );
			}
		}
		switch_to_blog( $current_blog_id );
	}
} else {
	foreach ( $option_keys as $key ) {
		delete_option( $key );
	}
}
