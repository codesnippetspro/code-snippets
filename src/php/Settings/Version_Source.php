<?php

namespace Code_Snippets\Settings;

use WP_Error;

/**
 * Supplies the plugin versions the version switcher may install, and the
 * packages for them.
 *
 * The edition decides where versions come from: the free plugin lists builds
 * published on WordPress.org, while Code Snippets Pro lists the builds its
 * cloud account is licensed to install. Everything else about the switcher —
 * caching, the progress marker, capability and nonce checks, and rendering — is
 * shared, so an implementation of this interface is the only thing an edition
 * needs to replace.
 *
 * Register an implementation on the `code_snippets_version_source` filter.
 *
 * @package    Code_Snippets
 * @subpackage Settings
 */
interface Version_Source {

	/**
	 * Transient key under which this source's catalogue is cached.
	 *
	 * Every source must use its own key. A site that changes edition, or whose
	 * source is replaced, must not go on serving a catalogue the previous source
	 * cached — that would offer packages the new source cannot install.
	 *
	 * @return string
	 */
	public function get_cache_key(): string;

	/**
	 * Determine whether this source can currently be reached.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Explain an unavailable source to the user, and offer a way to resolve it.
	 *
	 * Only consulted when is_available() is false. An empty `action_url` renders
	 * the message with no call to action.
	 *
	 * @return array{message: string, action_url: string, action_label: string}
	 */
	public function get_unavailable_notice(): array;

	/**
	 * Describe where versions are fetched from, shown under the refresh button.
	 *
	 * @return string
	 */
	public function get_refresh_description(): string;

	/**
	 * Retrieve the versions available to install, ordered newest first.
	 *
	 * Each entry must carry a `version` key, and may carry `release_mode`
	 * (`latest`, `released`, `beta` or `rc`), `tested_up_to` and `is_latest`,
	 * which the switcher renders when present. A source may include any further
	 * keys it needs; the entry is handed back to fetch_package() unchanged.
	 *
	 * An empty version list is a valid catalogue, not an error. `floor` names the
	 * oldest installable version, or is empty when there is no such limit.
	 *
	 * @return array{versions: array<int, array<string, mixed>>, floor: string}|WP_Error
	 */
	public function fetch_catalogue();

	/**
	 * Explain why the catalogue stops at the floor it reported.
	 *
	 * Only consulted when fetch_catalogue() returned a non-empty `floor`.
	 *
	 * @param string $floor Oldest installable version.
	 *
	 * @return string
	 */
	public function get_floor_notice( string $floor ): string;

	/**
	 * Obtain the package for a version.
	 *
	 * `package` is either a local file path or a download URL — the installer
	 * accepts both. Set `cleanup` when the switcher should delete the package
	 * once the install has finished, as it must for a temporary download.
	 *
	 * @param array<string, mixed> $version_info Catalogue entry for the version.
	 *
	 * @return array{package: string, cleanup: bool}|WP_Error
	 */
	public function fetch_package( array $version_info );

	/**
	 * Record a completed switch with whatever service tracks this site.
	 *
	 * Best-effort: an implementation must never turn a successful switch into a
	 * reported failure.
	 *
	 * @param string $version Version now installed.
	 *
	 * @return void
	 */
	public function report_installed_version( string $version ): void;
}
