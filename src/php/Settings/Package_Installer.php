<?php

namespace Code_Snippets\Settings;

use WP_Error;

/**
 * Installs a plugin package over the currently installed plugin.
 *
 * Register an implementation on the `code_snippets_package_installer` filter to
 * replace how the version switcher writes a package to disk.
 *
 * @package    Code_Snippets
 * @subpackage Settings
 */
interface Package_Installer {

	/**
	 * Install a plugin package.
	 *
	 * @param string $package Local path to a package, or a download URL.
	 *
	 * @return array{version: string}|WP_Error Version installed, or '' when the
	 *                                         installer cannot determine it.
	 */
	public function install( string $package );
}
