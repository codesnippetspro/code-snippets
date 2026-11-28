<?php

namespace Code_Snippets\Core;

/**
 * Finds out which editions of the plugin are installed, such as the free plugin alongside Pro.
 *
 * Used by uninstall.php before any other plugin code is loaded, so it only calls WordPress functions.
 *
 * @package Code_Snippets
 */
class Editions {

	/**
	 * Determine whether another edition of the plugin is installed besides the one being deleted.
	 *
	 * The editions share their snippets and settings, so these are kept until the last edition is deleted.
	 * WordPress runs uninstall.php before it removes the plugin's files, so the edition being deleted counts as one.
	 *
	 * @return bool
	 */
	public static function is_other_edition_installed(): bool {
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
}
