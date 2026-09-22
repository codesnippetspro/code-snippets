<?php

namespace Code_Snippets\Settings;

use WP_Error;

/**
 * Lists the plugin builds published on WordPress.org.
 *
 * This is the switcher's default source. It needs no credentials and is always
 * available, so the unavailable notice it returns is never rendered.
 *
 * @package    Code_Snippets
 * @subpackage Settings
 */
class WordPress_Org_Version_Source implements Version_Source {

	/**
	 * Endpoint listing the released versions of a plugin.
	 */
	private const API_ENDPOINT = 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=code-snippets';

	/**
	 * Transient key under which this source's catalogue is cached.
	 *
	 * @return string
	 */
	public function get_cache_key(): string {
		return 'code_snippets_available_versions';
	}

	/**
	 * Determine whether this source can currently be reached.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return true;
	}

	/**
	 * Explain an unavailable source to the user.
	 *
	 * @return array{message: string, action_url: string, action_label: string}
	 */
	public function get_unavailable_notice(): array {
		return [
			'message'      => __( 'The list of available versions could not be retrieved.', 'code-snippets' ),
			'action_url'   => '',
			'action_label' => '',
		];
	}

	/**
	 * Describe where versions are fetched from.
	 *
	 * @return string
	 */
	public function get_refresh_description(): string {
		return __( 'Check for the latest available plugin versions from WordPress.org.', 'code-snippets' );
	}

	/**
	 * Retrieve the versions available to install, ordered newest first.
	 *
	 * @return array{versions: array<int, array<string, mixed>>, floor: string}|WP_Error
	 */
	public function fetch_catalogue() {
		$response = wp_remote_get( self::API_ENDPOINT );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'version_source_request_error',
				__( 'Failed to retrieve the list of available versions from WordPress.org.', 'code-snippets' )
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || ! isset( $data['versions'] ) || ! is_array( $data['versions'] ) ) {
			return new WP_Error(
				'version_source_response_error',
				__( 'Did not receive a valid version list from WordPress.org.', 'code-snippets' )
			);
		}

		$versions = [];

		foreach ( $data['versions'] as $version => $download_url ) {
			if ( 'trunk' !== $version ) {
				$versions[] = [
					'version' => (string) $version,
					'url'     => (string) $download_url,
				];
			}
		}

		usort(
			$versions,
			function ( array $a, array $b ) {
				return version_compare( $b['version'], $a['version'] );
			}
		);

		return [
			'versions' => $versions,
			'floor'    => '',
		];
	}

	/**
	 * Explain why the catalogue stops at the floor it reported. WordPress.org
	 * lists every released build, so this source reports no floor.
	 *
	 * @param string $floor Oldest installable version.
	 *
	 * @return string
	 */
	public function get_floor_notice( string $floor ): string {
		return '';
	}

	/**
	 * Obtain the package for a version.
	 *
	 * The catalogue entry already carries the download URL, which the installer
	 * fetches itself, so there is nothing to clean up afterwards.
	 *
	 * @param array<string, mixed> $version_info Catalogue entry for the version.
	 *
	 * @return array{package: string, cleanup: bool}|WP_Error
	 */
	public function fetch_package( array $version_info ) {
		if ( empty( $version_info['url'] ) ) {
			return new WP_Error(
				'version_source_package_error',
				__( 'No download is available for that version.', 'code-snippets' )
			);
		}

		return [
			'package' => (string) $version_info['url'],
			'cleanup' => false,
		];
	}

	/**
	 * Record a completed switch. WordPress.org tracks nothing on this site's
	 * behalf, so there is nothing to report.
	 *
	 * @param string $version Version now installed.
	 *
	 * @return void
	 */
	public function report_installed_version( string $version ): void {
	}
}
