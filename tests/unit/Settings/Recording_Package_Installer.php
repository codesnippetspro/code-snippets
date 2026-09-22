<?php

namespace Code_Snippets\Settings;

use WP_Error;

/**
 * Package installer that records the packages it was handed.
 */
class Recording_Package_Installer implements Package_Installer {

	/**
	 * Packages this installer was asked to install.
	 *
	 * @var array<int, string>
	 */
	public array $installed = [];

	/**
	 * Result handed back to the switcher.
	 *
	 * @var array<string, mixed>|WP_Error
	 */
	public $result = [ 'version' => '' ];

	/**
	 * Install a plugin package.
	 *
	 * @param string $package Local path to a package, or a download URL.
	 *
	 * @return array{version: string}|WP_Error
	 */
	public function install( string $package ) {
		$this->installed[] = $package;
		return $this->result;
	}
}
