<?php
/**
 * Functions for cleaning data when the plugin is uninstalled.
 *
 * @package Code_Snippets
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */

namespace Code_Snippets\Uninstall;

/**
 * Determine whether the option for allowing a complete uninstallation is enabled.
 *
 * @return boolean
 */
function complete_uninstall_enabled(): bool {
	$unified = false;

	if ( is_multisite() ) {
		$menu_perms = get_site_option( 'menu_items', [] );
		$unified = empty( $menu_perms['snippets_settings'] );
	}

	$settings = $unified ? get_site_option( 'code_snippets_settings' ) : get_option( 'code_snippets_settings' );

	return isset( $settings['general']['complete_uninstall'] ) && $settings['general']['complete_uninstall'];
}

/**
 * Clean up data created by this plugin for a single site
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
 */
function uninstall_current_site() {
	global $wpdb;

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}snippets" );

	delete_option( 'code_snippets_version' );
	delete_option( 'recently_activated_snippets' );
	delete_option( 'code_snippets_settings' );

	delete_option( 'code_snippets_cloud_settings' );
	delete_transient( 'cs_codevault_snippets' );
	delete_transient( 'cs_local_to_cloud_map' );
}

/**
 * Clean up data created by this plugin on multisite.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
 */
function uninstall_multisite() {
	global $wpdb;

	// Loop through sites.
	$blog_ids = get_sites( [ 'fields' => 'ids' ] );

	foreach ( $blog_ids as $site_id ) {
		switch_to_blog( $site_id );
		uninstall_current_site();
	}

	restore_current_blog();

	// Remove network snippets table.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ms_snippets" );

	// Remove saved options.
	delete_site_option( 'code_snippets_version' );
	delete_site_option( 'recently_activated_snippets' );
}

function delete_flat_files_directory() {
	$flat_files_dir = WP_CONTENT_DIR . '/code-snippets';

	if ( ! is_dir( $flat_files_dir ) ) {
		return;
	}

	if ( ! function_exists( 'request_filesystem_credentials' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	global $wp_filesystem;
	WP_Filesystem();

	if ( $wp_filesystem && $wp_filesystem->is_dir( $flat_files_dir ) ) {
		$wp_filesystem->delete( $flat_files_dir, true );
	}
}

/**
 * Uninstall the Code Snippets plugin.
 *
 * @return void
 */
function uninstall_plugin() {
	if ( complete_uninstall_enabled() ) {

		if ( is_multisite() ) {
			uninstall_multisite();
		} else {
			uninstall_current_site();
		}

		delete_flat_files_directory();
	}
}
