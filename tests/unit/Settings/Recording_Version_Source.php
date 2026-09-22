<?php

namespace Code_Snippets\Settings;

use WP_Error;

/**
 * Version source that records what the switcher asked it for.
 */
class Recording_Version_Source implements Version_Source {

	/**
	 * Transient key this source caches its catalogue under.
	 *
	 * @var string
	 */
	public string $cache_key = 'code_snippets_test_available_versions';

	/**
	 * Whether this source reports itself as reachable.
	 *
	 * @var bool
	 */
	public bool $available = true;

	/**
	 * Catalogue, or error, handed back to the switcher.
	 *
	 * @var array<string, mixed>|WP_Error
	 */
	public $catalogue = [
		'versions' => [],
		'floor'    => '',
	];

	/**
	 * Package, or error, handed back to the switcher.
	 *
	 * @var array<string, mixed>|WP_Error
	 */
	public $package = [
		'package' => '',
		'cleanup' => false,
	];

	/**
	 * Number of times the catalogue was fetched.
	 *
	 * @var int
	 */
	public int $fetch_catalogue_count = 0;

	/**
	 * Versions reported as installed.
	 *
	 * @var array<int, string>
	 */
	public array $reported = [];

	/**
	 * Transient key under which this source's catalogue is cached.
	 *
	 * @return string
	 */
	public function get_cache_key(): string {
		return $this->cache_key;
	}

	/**
	 * Determine whether this source can currently be reached.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->available;
	}

	/**
	 * Explain an unavailable source to the user.
	 *
	 * @return array{message: string, action_url: string, action_label: string}
	 */
	public function get_unavailable_notice(): array {
		return [
			'message'      => 'The test source is unavailable.',
			'action_url'   => 'https://example.org/account',
			'action_label' => 'Manage account',
		];
	}

	/**
	 * Describe where versions are fetched from.
	 *
	 * @return string
	 */
	public function get_refresh_description(): string {
		return 'Check the test source for available versions.';
	}

	/**
	 * Retrieve the versions available to install.
	 *
	 * @return array{versions: array<int, array<string, mixed>>, floor: string}|WP_Error
	 */
	public function fetch_catalogue() {
		++$this->fetch_catalogue_count;
		return $this->catalogue;
	}

	/**
	 * Explain why the catalogue stops at the floor it reported.
	 *
	 * @param string $floor Oldest installable version.
	 *
	 * @return string
	 */
	public function get_floor_notice( string $floor ): string {
		return 'Versions before ' . $floor . ' are not installable.';
	}

	/**
	 * Obtain the package for a version.
	 *
	 * @param array<string, mixed> $version_info Catalogue entry for the version.
	 *
	 * @return array{package: string, cleanup: bool}|WP_Error
	 */
	public function fetch_package( array $version_info ) {
		return $this->package;
	}

	/**
	 * Record a completed switch.
	 *
	 * @param string $version Version now installed.
	 *
	 * @return void
	 */
	public function report_installed_version( string $version ): void {
		$this->reported[] = $version;
	}
}
