<?php
/**
 * Functions for cleaning data when the plugin is uninstalled.
 *
 * @package Code_Snippets
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */

namespace Code_Snippets\Core;

use Code_Snippets\Admin\Feedback_Panel;
use Code_Snippets\Client\Docs_Client;
use Code_Snippets\Client\Feedback_Client;
use Code_Snippets\Flat_Files\Snippet_Files;
use Code_Snippets\Model\Feedback_Connection;
use Code_Snippets\Search\Universal_Search;
use Code_Snippets\Settings\Version_Switch;
use function Code_Snippets\flush_versioned_cache_groups;

/**
 * Uninstaller class.
 *
 * This class handles the uninstallation of the Code Snippets plugin, including
 * cleaning up data created by the plugin on both single sites and multisite installations.
 *
 * @package Code_Snippets\Core
 */
class Uninstaller {

	/**
	 * Uninstall the Code Snippets plugin.
	 *
	 * @return void
	 */
	public function uninstall_plugin() {
		if ( ! $this->is_complete_uninstall_enabled() || self::is_other_edition_installed() ) {
			return;
		}

		if ( is_multisite() ) {
			$this->uninstall_multisite();
		} else {
			$this->uninstall_current_site();
		}

		$this->delete_flat_files_directory();
	}

	/**
	 * Determine whether another edition of the plugin is installed, such as Pro alongside the free plugin.
	 *
	 * The editions share their snippets and settings, so these are kept until the last edition is deleted.
	 * WordPress runs uninstall.php before it removes the plugin's files, so the edition being deleted counts as one.
	 *
	 * @return bool
	 */
	private static function is_other_edition_installed(): bool {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$editions = 0;

		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			// The plugin list is cached for the request, so files deleted earlier in a bulk delete can still be listed.
			if ( 'code-snippets' === $plugin_data['TextDomain'] && 0 === strpos( $plugin_data['Name'], 'Code Snippets' ) &&
			     file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
				++$editions;
			}
		}

		return $editions > 1;
	}

	/**
	 * Determine whether the option for allowing a complete uninstallation is enabled.
	 *
	 * @return bool
	 */
	public function is_complete_uninstall_enabled(): bool {
		$unified = false;

		if ( is_multisite() ) {
			$menu_perms = get_site_option( 'menu_items', array() );
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
	private function uninstall_current_site() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Removes the plugin's own table on uninstall.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}snippets" );

		delete_option( 'code_snippets_version' );

		// Shed cached snippet objects before dropping the recorded cache version,
		// otherwise a same-version reinstall can read data this uninstall removed.
		flush_versioned_cache_groups( (string) get_option( 'code_snippets_cache_version', '' ) );
		delete_option( 'code_snippets_cache_version' );
		delete_option( 'recently_active_snippets' );
		delete_option( 'recently_activated_snippets' );
		delete_option( 'code_snippets_settings' );
		delete_option( 'code_snippets_stats' );
		delete_option( 'code_snippets_demos_seen' );
		delete_option( Snippet_Files::DIR_NAMES_OPTION );

		delete_option( 'code_snippets_cloud_settings' );
		delete_transient( 'code_snippets_cloud_links' );
		delete_transient( 'cs_codevault_snippets' );
		delete_transient( 'cs_local_to_cloud_map' );

		Docs_Client::clear_cache();
		Universal_Search::clear_caches();

		delete_option( Feedback_Connection::CREDENTIALS_OPTION );
		delete_transient( Feedback_Panel::SUMMARY_TRANSIENT );
		delete_transient( Feedback_Client::REGISTRATION_FAILURE_TRANSIENT );

		// The switcher caches under a key belonging to whichever source is in
		// force, so it has to do its own clearing rather than name the keys here.
		Version_Switch::clear_version_caches();
	}

	/**
	 * Clean up data created by this plugin on multisite.
	 *
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
	 */
	private function uninstall_multisite() {
		global $wpdb;

		// Loop through sites.
		$blog_ids = get_sites( [ 'fields' => 'ids' ] );

		foreach ( $blog_ids as $site_id ) {
			switch_to_blog( $site_id );
			$this->uninstall_current_site();
		}

		restore_current_blog();

		// Remove network snippets table.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Removes the plugin's own table on uninstall.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ms_snippets" );

		// Remove saved options.
		delete_site_option( 'code_snippets_version' );
		delete_site_option( 'recently_active_snippets' );
		delete_site_option( 'recently_activated_snippets' );
		delete_site_option( Snippet_Files::DIR_NAMES_OPTION );
	}

	/**
	 * Clean up directory used to store snippet flat files.
	 *
	 * @return void
	 */
	private function delete_flat_files_directory() {
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
}
