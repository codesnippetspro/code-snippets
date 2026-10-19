<?php

namespace Code_Snippets\Settings;

use Code_Snippets\UnitTestCase;
use WP_Error;

require_once __DIR__ . '/Recording_Version_Source.php';
require_once __DIR__ . '/Recording_Package_Installer.php';

/**
 * Tests for the version switcher on the maintenance settings screen.
 *
 * @group version-switch
 */
class Version_Switch_Test extends UnitTestCase {

	/**
	 * Transient key the WordPress.org source caches its catalogue under.
	 */
	private const WP_ORG_CACHE_KEY = 'code_snippets_available_versions';

	/**
	 * Transient key holding the in-progress marker.
	 */
	private const PROGRESS_KEY = 'code_snippets_version_switch_progress';

	/**
	 * Transient key holding the reason the last catalogue request failed.
	 */
	private const ERROR_KEY = 'code_snippets_version_switch_error';

	/**
	 * Source registered on the seam filter for the duration of a test.
	 *
	 * @var Recording_Version_Source|null
	 */
	private ?Recording_Version_Source $source = null;

	/**
	 * Installer registered on the seam filter for the duration of a test.
	 *
	 * @var Recording_Package_Installer|null
	 */
	private ?Recording_Package_Installer $installer = null;

	/**
	 * Callbacks to detach from the seam filters during tear-down.
	 *
	 * @var array<int, array{string, callable}>
	 */
	private array $registered_filters = [];

	/**
	 * Response the next outbound request will be answered with.
	 *
	 * @var array<string, mixed>|WP_Error
	 */
	private $next_response = [];

	/**
	 * URLs of every outbound request made during a test.
	 *
	 * @var array<int, string>
	 */
	private array $request_urls = [];

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		Version_Switch::clear_version_caches();
		delete_transient( self::WP_ORG_CACHE_KEY );

		$this->next_response = $this->build_response( 200, wp_json_encode( [ 'versions' => [] ] ) );
		$this->request_urls = [];

		add_filter( 'pre_http_request', [ $this, 'answer_http_request' ], 10, 3 );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'answer_http_request' ] );

		Version_Switch::clear_version_caches();

		foreach ( $this->registered_filters as list( $hook, $callback ) ) {
			remove_filter( $hook, $callback );
		}

		$this->registered_filters = [];
		$this->source = null;
		$this->installer = null;

		delete_transient( self::WP_ORG_CACHE_KEY );

		parent::tear_down();
	}

	/**
	 * Answer an outbound request without touching the network.
	 *
	 * @param mixed                $preempt     Existing preempted value.
	 * @param array<string, mixed> $parsed_args Request arguments.
	 * @param string               $url         Request URL.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public function answer_http_request( $preempt, array $parsed_args, string $url ) {
		$this->request_urls[] = $url;
		return $this->next_response;
	}

	/**
	 * Assemble a fake HTTP response.
	 *
	 * @param int    $status Status code.
	 * @param string $body   Response body.
	 *
	 * @return array<string, mixed>
	 */
	private function build_response( int $status, string $body ): array {
		return [
			'headers'  => [],
			'body'     => $body,
			'response' => [
				'code'    => $status,
				'message' => 'OK',
			],
			'cookies'  => [],
		];
	}

	/**
	 * Put a recording source and installer behind the seam filters.
	 *
	 * @return void
	 */
	private function register_test_services(): void {
		$this->source = new Recording_Version_Source();
		$this->installer = new Recording_Package_Installer();

		$source = $this->source;
		$installer = $this->installer;

		$supply_source = function () use ( $source ) {
			return $source;
		};

		$supply_installer = function () use ( $installer ) {
			return $installer;
		};

		add_filter( 'code_snippets_version_source', $supply_source );
		add_filter( 'code_snippets_package_installer', $supply_installer );

		$this->registered_filters[] = [ 'code_snippets_version_source', $supply_source ];
		$this->registered_filters[] = [ 'code_snippets_package_installer', $supply_installer ];
	}

	/**
	 * Seed the recording source's cache with a single-entry catalogue.
	 *
	 * @param string $version Version to list.
	 *
	 * @return void
	 */
	private function seed_catalogue( string $version ): void {
		set_transient(
			$this->source->get_cache_key(),
			[
				'versions' => [
					[
						'version' => $version,
						'url'     => 'https://example.org/code-snippets.' . $version . '.zip',
					],
				],
				'floor'    => '',
			],
			HOUR_IN_SECONDS
		);
	}

	/**
	 * Sign in as a user who may update plugins.
	 *
	 * @return void
	 */
	private function authorise(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Create a throwaway file standing in for a downloaded package.
	 *
	 * @return string
	 */
	private function create_package_file(): string {
		return tempnam( get_temp_dir(), 'cs-pkg' );
	}

	/**
	 * Run a callback with the PHP error log written to a temporary file.
	 *
	 * @param callable $callback Code that may write to the error log.
	 *
	 * @return string What was written to the log.
	 */
	private function capture_error_log( callable $callback ): string {
		$log_file = tempnam( get_temp_dir(), 'cs-log' );
		$previous_log = ini_set( 'error_log', $log_file ); // phpcs:ignore WordPress.PHP.IniSet.Risky

		try {
			$callback();
			return (string) file_get_contents( $log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		} finally {
			ini_set( 'error_log', (string) $previous_log ); // phpcs:ignore WordPress.PHP.IniSet.Risky
			wp_delete_file( $log_file );
		}
	}

	/**
	 * Retrieve the notice added by the most recent cache reset.
	 *
	 * @return array{type: string, message: string}
	 */
	private function get_reset_caches_notice(): array {
		$notices = wp_list_filter( get_settings_errors( OPTION_NAME ), [ 'code' => 'snippet_caches_reset' ] );

		$this->assertNotEmpty( $notices, 'resetting caches should add a notice' );

		return end( $notices );
	}

	/**
	 * Capture the markup the version switcher field renders.
	 *
	 * @return string
	 */
	private function render_version_switch_field(): string {
		ob_start();
		Version_Switch::render_version_switch_field();
		return (string) ob_get_clean();
	}

	/**
	 * With nothing registered on the seam filters, the switcher lists builds from
	 * WordPress.org and installs them with the WordPress plugin upgrader.
	 *
	 * @return void
	 */
	public function test_defaults_are_the_wordpress_org_source_and_upgrader_installer(): void {
		$this->assertInstanceOf( WordPress_Org_Version_Source::class, Version_Switch::get_source() );
		$this->assertInstanceOf( Upgrader_Package_Installer::class, Version_Switch::get_installer() );
	}

	/**
	 * The seam filters replace both services, and removing them restores the
	 * defaults the plugin ships with.
	 *
	 * @return void
	 */
	public function test_filters_replace_the_source_and_installer(): void {
		$this->register_test_services();

		$this->assertSame( $this->source, Version_Switch::get_source() );
		$this->assertSame( $this->installer, Version_Switch::get_installer() );

		foreach ( $this->registered_filters as list( $hook, $callback ) ) {
			remove_filter( $hook, $callback );
		}

		$this->registered_filters = [];

		$this->assertInstanceOf( WordPress_Org_Version_Source::class, Version_Switch::get_source() );
		$this->assertInstanceOf( Upgrader_Package_Installer::class, Version_Switch::get_installer() );
	}

	/**
	 * A source registered after the switcher was first used still takes effect,
	 * as an edition may add its filter later in the request than the first lookup.
	 *
	 * @return void
	 */
	public function test_a_source_registered_after_first_use_is_honoured(): void {
		$this->assertInstanceOf( WordPress_Org_Version_Source::class, Version_Switch::get_source() );

		$source = new Recording_Version_Source();
		$supply_source = function () use ( $source ) {
			return $source;
		};

		add_filter( 'code_snippets_version_source', $supply_source );
		$this->registered_filters[] = [ 'code_snippets_version_source', $supply_source ];

		$this->assertSame( $source, Version_Switch::get_source() );
	}

	/**
	 * A source that returns something other than a Version_Source is ignored, so
	 * a misbehaving filter cannot leave the switcher without one.
	 *
	 * @return void
	 */
	public function test_an_invalid_filter_return_falls_back_to_the_default(): void {
		$strip = function () {
			return null;
		};

		add_filter( 'code_snippets_version_source', $strip, 99 );
		add_filter( 'code_snippets_package_installer', $strip, 99 );
		$this->registered_filters[] = [ 'code_snippets_version_source', $strip ];
		$this->registered_filters[] = [ 'code_snippets_package_installer', $strip ];

		$this->assertInstanceOf( WordPress_Org_Version_Source::class, Version_Switch::get_source() );
		$this->assertInstanceOf( Upgrader_Package_Installer::class, Version_Switch::get_installer() );
	}

	/**
	 * The catalogue is cached under the key the source names, and a catalogue
	 * left behind under another source's key is neither read nor overwritten.
	 *
	 * @return void
	 */
	public function test_catalogue_is_cached_under_the_source_cache_key(): void {
		$this->register_test_services();

		$stale = [
			'versions' => [
				[
					'version' => '3.6.5',
					'url'     => 'https://downloads.wordpress.org/plugin/code-snippets.3.6.5.zip',
				],
			],
			'floor'    => '',
		];

		set_transient( self::WP_ORG_CACHE_KEY, $stale, HOUR_IN_SECONDS );

		$this->source->catalogue = [
			'versions' => [
				[
					'version' => '3.9.2',
					'url'     => 'https://example.org/code-snippets.3.9.2.zip',
				],
			],
			'floor'    => '3.6.0',
		];

		$versions = Version_Switch::get_available_versions();

		$this->assertSame( [ '3.9.2' ], wp_list_pluck( $versions, 'version' ) );
		$this->assertSame( '3.6.0', Version_Switch::get_version_floor() );
		$this->assertIsArray( get_transient( $this->source->get_cache_key() ) );
		$this->assertSame( $stale, get_transient( self::WP_ORG_CACHE_KEY ) );
	}

	/**
	 * A cached catalogue answers without troubling the source again.
	 *
	 * @return void
	 */
	public function test_cached_catalogue_is_not_refetched(): void {
		$this->register_test_services();
		$this->seed_catalogue( '3.9.2' );

		$this->assertSame( [ '3.9.2' ], wp_list_pluck( Version_Switch::get_available_versions(), 'version' ) );
		$this->assertSame( 0, $this->source->fetch_catalogue_count );
	}

	/**
	 * A failed request must not be cached: a transient outage would otherwise
	 * blank the switcher for the full cache duration.
	 *
	 * @return void
	 */
	public function test_source_error_is_not_cached(): void {
		$this->register_test_services();
		$this->source->catalogue = new WP_Error( 'version_source_request_error', 'Request failed.' );

		$this->assertSame( [], Version_Switch::get_available_versions() );
		$this->assertFalse( get_transient( $this->source->get_cache_key() ) );
		$this->assertSame( 'version_source_request_error', Version_Switch::get_last_error_code() );
	}

	/**
	 * An unavailable source is not asked for a catalogue at all.
	 *
	 * @return void
	 */
	public function test_unavailable_source_is_never_fetched_from(): void {
		$this->register_test_services();
		$this->source->available = false;

		$this->assertFalse( Version_Switch::is_available() );
		$this->assertSame( [], Version_Switch::get_available_versions() );
		$this->assertSame( 0, $this->source->fetch_catalogue_count );
		$this->assertNotEmpty( Version_Switch::get_unavailable_notice()['message'] );
	}

	/**
	 * Refreshing discards the cached catalogue and fetches a current one, so a
	 * build published moments ago shows up straight away.
	 *
	 * @return void
	 */
	public function test_refresh_refetches_the_catalogue_from_the_source(): void {
		$this->register_test_services();
		$this->seed_catalogue( '3.9.2' );

		$this->source->catalogue = [
			'versions' => [
				[
					'version' => '4.0.0',
					'url'     => 'https://example.org/code-snippets.4.0.0.zip',
				],
			],
			'floor'    => '',
		];

		// The cached catalogue would still answer this without a refetch.
		$this->assertSame( [ '3.9.2' ], wp_list_pluck( Version_Switch::get_available_versions(), 'version' ) );

		$refreshed = Version_Switch::refresh_available_versions();

		$this->assertSame( [ '4.0.0' ], wp_list_pluck( $refreshed, 'version' ) );
		$this->assertSame( 1, $this->source->fetch_catalogue_count );
	}

	/**
	 * A version absent from the catalogue is rejected before anything is fetched.
	 *
	 * @return void
	 */
	public function test_validate_target_version_rejects_an_unlisted_version(): void {
		$available = [
			[
				'version' => '3.9.2',
				'url'     => 'https://example.org/code-snippets.3.9.2.zip',
			],
		];

		$rejected = Version_Switch::validate_target_version( '3.0.0', $available );
		$empty = Version_Switch::validate_target_version( '', $available );
		$accepted = Version_Switch::validate_target_version( '3.9.2', $available );

		$this->assertFalse( $rejected['success'] );
		$this->assertNotEmpty( $rejected['message'] );
		$this->assertFalse( $empty['success'] );
		$this->assertTrue( $accepted['success'] );
		$this->assertArrayNotHasKey( 'download_url', $accepted );
	}

	/**
	 * A user without the plugin update capability cannot switch versions.
	 *
	 * @return void
	 */
	public function test_switch_requires_the_update_plugins_capability(): void {
		$this->register_test_services();
		$this->seed_catalogue( '3.9.2' );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$result = Version_Switch::handle_version_switch( '3.9.2' );

		$this->assertFalse( $result['success'] );
		$this->assertSame( [], $this->installer->installed );
	}

	/**
	 * A site whose source cannot be reached cannot start a switch at all.
	 *
	 * @return void
	 */
	public function test_switch_is_refused_when_the_source_is_unavailable(): void {
		$this->register_test_services();
		$this->authorise();
		$this->source->available = false;

		$result = Version_Switch::handle_version_switch( '3.9.2' );

		$this->assertFalse( $result['success'] );
		$this->assertSame( [], $this->installer->installed );
		$this->assertFalse( get_transient( self::PROGRESS_KEY ) );
	}

	/**
	 * Switching to the version already installed is a no-op.
	 *
	 * @return void
	 */
	public function test_switching_to_the_current_version_is_refused(): void {
		$this->register_test_services();
		$this->authorise();
		$this->seed_catalogue( Version_Switch::get_current_version() );

		$result = Version_Switch::handle_version_switch( Version_Switch::get_current_version() );

		$this->assertFalse( $result['success'] );
		$this->assertSame( [], $this->installer->installed );
	}

	/**
	 * A package the source cannot supply must never reach the installer, and must
	 * leave neither a wedged progress marker nor a catalogue known to be stale.
	 *
	 * @return void
	 */
	public function test_failed_package_fetch_never_reaches_the_installer(): void {
		$this->register_test_services();
		$this->authorise();
		$this->seed_catalogue( '3.9.2' );

		$this->source->package = new WP_Error( 'version_source_package_error', 'No download is available.' );

		$result = Version_Switch::handle_version_switch( '3.9.2' );

		$this->assertFalse( $result['success'] );
		$this->assertSame( [], $this->installer->installed );
		$this->assertFalse( get_transient( self::PROGRESS_KEY ) );
		$this->assertFalse( get_transient( $this->source->get_cache_key() ) );
		$this->assertSame( 'version_source_package_error', Version_Switch::get_last_error_code() );
	}

	/**
	 * A failed install clears the progress marker and deletes the package it was
	 * handed, rather than leaving the switcher wedged until the marker expires.
	 *
	 * @return void
	 */
	public function test_failed_install_clears_the_progress_marker_and_package(): void {
		$this->register_test_services();
		$this->authorise();
		$this->seed_catalogue( '3.9.2' );

		$package_path = $this->create_package_file();

		$this->source->package = [
			'package' => $package_path,
			'cleanup' => true,
		];

		$this->installer->result = new WP_Error( 'package_install_failed', 'The package could not be installed.' );

		$result = [];
		$this->capture_error_log(
			function () use ( &$result ) {
				$result = Version_Switch::handle_version_switch( '3.9.2' );
			}
		);

		$this->assertFalse( $result['success'] );
		$this->assertSame( [ $package_path ], $this->installer->installed );
		$this->assertFileDoesNotExist( $package_path );
		$this->assertFalse( get_transient( self::PROGRESS_KEY ) );
		$this->assertSame( [], $this->source->reported );
	}

	/**
	 * A failed install is written to the server log with the version and the
	 * installer's reason, so it can be diagnosed after the browser message is gone.
	 *
	 * @return void
	 */
	public function test_failed_install_is_logged_with_its_reason(): void {
		$this->register_test_services();
		$this->authorise();
		$this->seed_catalogue( '3.9.2' );

		$this->source->package = [
			'package' => 'https://example.org/code-snippets.3.9.2.zip',
			'cleanup' => false,
		];

		$this->installer->result = new WP_Error( 'package_install_failed', 'Could not create the plugin directory.' );

		$logged = $this->capture_error_log(
			function () {
				Version_Switch::handle_version_switch( '3.9.2' );
			}
		);

		$this->assertStringContainsString( '3.9.2', $logged );
		$this->assertStringContainsString( 'Could not create the plugin directory.', $logged );
	}

	/**
	 * A completed switch deletes the package, clears the progress marker and the
	 * now-outdated catalogue, and tells the source what landed on disk.
	 *
	 * @return void
	 */
	public function test_successful_switch_cleans_up_and_reports_the_installed_version(): void {
		$this->register_test_services();
		$this->authorise();
		$this->seed_catalogue( '3.9.2' );

		$package_path = $this->create_package_file();

		$this->source->package = [
			'package' => $package_path,
			'cleanup' => true,
		];

		$this->installer->result = [ 'version' => '' ];

		$result = Version_Switch::handle_version_switch( '3.9.2' );

		$this->assertTrue( $result['success'], $result['message'] );
		$this->assertStringContainsString( '3.9.2', $result['message'] );
		$this->assertSame( [ $package_path ], $this->installer->installed );
		$this->assertFileDoesNotExist( $package_path );
		$this->assertFalse( get_transient( self::PROGRESS_KEY ) );
		$this->assertFalse( get_transient( $this->source->get_cache_key() ) );
		$this->assertSame( [ '3.9.2' ], $this->source->reported );
	}

	/**
	 * An installer that can read the version it wrote is believed over the one the
	 * switch asked for, since the package decides what actually landed.
	 *
	 * @return void
	 */
	public function test_installer_reported_version_wins_over_the_requested_one(): void {
		$this->register_test_services();
		$this->authorise();
		$this->seed_catalogue( '3.9.2' );

		$this->source->package = [
			'package' => 'https://example.org/code-snippets.3.9.2.zip',
			'cleanup' => false,
		];

		$this->installer->result = [ 'version' => '3.9.3' ];

		$result = Version_Switch::handle_version_switch( '3.9.2' );

		$this->assertTrue( $result['success'], $result['message'] );
		$this->assertSame( [ '3.9.3' ], $this->source->reported );
	}

	/**
	 * A package the source did not ask to have cleaned up is left alone.
	 *
	 * @return void
	 */
	public function test_package_is_kept_when_cleanup_is_not_requested(): void {
		$this->register_test_services();
		$this->authorise();
		$this->seed_catalogue( '3.9.2' );

		$package_path = $this->create_package_file();

		$this->source->package = [
			'package' => $package_path,
			'cleanup' => false,
		];

		$this->installer->result = [ 'version' => '' ];

		try {
			$result = Version_Switch::handle_version_switch( '3.9.2' );

			$this->assertTrue( $result['success'], $result['message'] );
			$this->assertFileExists( $package_path );
		} finally {
			wp_delete_file( $package_path );
		}
	}

	/**
	 * A catalogue request that failed is explained in the field itself, rather
	 * than leaving a dead control with nothing to act on.
	 *
	 * @return void
	 */
	public function test_failed_catalogue_request_renders_its_message_instead_of_the_switcher(): void {
		$this->register_test_services();
		$this->source->catalogue = new WP_Error( 'version_source_request_error', 'The version list could not be retrieved.' );

		$output = $this->render_version_switch_field();

		$this->assertStringContainsString( 'The version list could not be retrieved.', $output );
		$this->assertStringContainsString( 'notice-warning', $output );
		$this->assertStringNotContainsString( '<select', $output );
		$this->assertStringContainsString( 'Current Version:', $output );
	}

	/**
	 * A source that answered with nothing to install says so, instead of
	 * presenting the same dead control a failed request would.
	 *
	 * @return void
	 */
	public function test_empty_catalogue_renders_an_explicit_nothing_to_install_line(): void {
		$this->register_test_services();

		$output = $this->render_version_switch_field();

		$this->assertStringContainsString( 'no versions available to install', $output );
		$this->assertStringNotContainsString( '<select', $output );
		$this->assertStringContainsString( 'Current Version:', $output );
	}

	/**
	 * A catalogue with versions in it still renders the dropdown, with an entry
	 * for each version.
	 *
	 * @return void
	 */
	public function test_populated_catalogue_renders_the_version_dropdown(): void {
		$this->register_test_services();
		$this->seed_catalogue( '3.9.2' );

		$output = $this->render_version_switch_field();

		$this->assertStringContainsString( '<select', $output );
		$this->assertStringContainsString( 'value="3.9.2"', $output );
		$this->assertStringNotContainsString( 'no versions available to install', $output );
	}

	/**
	 * The warning sits inside the switcher, above the button, and is marked
	 * inline so WordPress does not move it to the top of the screen.
	 *
	 * @return void
	 */
	public function test_warning_renders_inline_above_the_switch_button(): void {
		$this->register_test_services();
		$this->seed_catalogue( '3.9.2' );

		$output = $this->render_version_switch_field();

		$this->assertMatchesRegularExpression( '/id="version-switch-warning" class="[^"]*\\binline\\b/', $output );
		$this->assertLessThan( strpos( $output, 'id="switch-version-btn"' ), strpos( $output, 'id="version-switch-warning"' ) );
	}

	/**
	 * A version the source warns about carries that warning on its option, and
	 * other versions carry none.
	 *
	 * @return void
	 */
	public function test_source_switch_warning_is_attached_to_its_version(): void {
		$this->register_test_services();
		$this->seed_catalogue( '3.9.2' );
		$this->source->switch_warnings = [ '3.9.2' => 'Switching back needs another route.' ];

		$this->assertMatchesRegularExpression(
			'/value="3\\.9\\.2"\\s+data-warning="Switching back needs another route\\."/',
			$this->render_version_switch_field()
		);

		$this->source->switch_warnings = [];
		$this->assertStringNotContainsString( 'data-warning=', $this->render_version_switch_field() );
	}

	/**
	 * The refresh button is dropped while the source is unavailable, and kept
	 * otherwise.
	 *
	 * @return void
	 */
	public function test_refresh_button_is_hidden_while_the_source_is_unavailable(): void {
		$this->register_test_services();

		$contents = [ 'advanced' => [ [ 'version-switch', 'version_switcher' ], [ 'version-switch', 'refresh_versions' ] ] ];

		$this->assertSame( $contents, Version_Switch::hide_refresh_when_unavailable( $contents ) );

		$this->source->available = false;

		$this->assertSame(
			[ [ 'version-switch', 'version_switcher' ] ],
			Version_Switch::hide_refresh_when_unavailable( $contents )['advanced']
		);
	}

	/**
	 * The message behind a failed request is kept alongside its code, and a
	 * successful fetch clears both.
	 *
	 * @return void
	 */
	public function test_last_error_message_is_stored_and_cleared_with_the_code(): void {
		$this->register_test_services();

		$this->assertSame( '', Version_Switch::get_last_error_message() );

		$this->source->catalogue = new WP_Error( 'version_source_request_error', 'The version list could not be retrieved.' );

		Version_Switch::get_available_versions();

		$this->assertSame( 'version_source_request_error', Version_Switch::get_last_error_code() );
		$this->assertSame( 'The version list could not be retrieved.', Version_Switch::get_last_error_message() );

		$this->source->catalogue = [
			'versions' => [
				[
					'version' => '3.9.2',
					'url'     => 'https://example.org/code-snippets.3.9.2.zip',
				],
			],
			'floor'    => '',
		];

		Version_Switch::refresh_available_versions();

		$this->assertSame( '', Version_Switch::get_last_error_code() );
		$this->assertSame( '', Version_Switch::get_last_error_message() );
	}

	/**
	 * A site still holding the previous transient shape — a bare error code —
	 * reports that code rather than a mangled one.
	 *
	 * @return void
	 */
	public function test_last_error_code_tolerates_the_previous_transient_shape(): void {
		set_transient( self::ERROR_KEY, 'version_source_request_error', MINUTE_IN_SECONDS );

		$this->assertSame( 'version_source_request_error', Version_Switch::get_last_error_code() );
		$this->assertSame( '', Version_Switch::get_last_error_message() );
	}

	/**
	 * Clearing the caches also removes a catalogue cached by the default source,
	 * which a site keeps after another source takes over.
	 *
	 * @return void
	 */
	public function test_clearing_caches_removes_the_default_source_catalogue(): void {
		$this->register_test_services();
		$this->seed_catalogue( '3.9.2' );
		set_transient(
			self::WP_ORG_CACHE_KEY,
			[
				'versions' => [],
				'floor'    => '',
			],
			HOUR_IN_SECONDS
		);

		Version_Switch::clear_version_caches();

		$this->assertFalse( get_transient( $this->source->get_cache_key() ) );
		$this->assertFalse( get_transient( self::WP_ORG_CACHE_KEY ) );
	}

	/**
	 * Resetting the caches reports why versions could not be fetched again,
	 * rather than reporting success with none available.
	 *
	 * @return void
	 */
	public function test_reset_caches_reports_a_failed_refresh(): void {
		$this->register_test_services();
		$this->source->catalogue = new WP_Error( 'version_source_request_error', 'The version service is down.' );

		process_settings_actions( [ 'debug' => [ 'reset_caches' => '1' ] ] );

		$notice = $this->get_reset_caches_notice();
		$this->assertSame( 'error', $notice['type'] );
		$this->assertStringContainsString( 'The version service is down.', $notice['message'] );
	}

	/**
	 * Resetting the caches with no source available explains why rather than
	 * reporting none to install.
	 *
	 * @return void
	 */
	public function test_reset_caches_reports_an_unavailable_source(): void {
		$this->register_test_services();
		$this->source->available = false;

		process_settings_actions( [ 'debug' => [ 'reset_caches' => '1' ] ] );

		$notice = $this->get_reset_caches_notice();
		$this->assertSame( 'error', $notice['type'] );
		$this->assertStringContainsString( Version_Switch::get_unavailable_notice()['message'], $notice['message'] );
	}

	/**
	 * A source that reports no floor has nothing to explain, so the floor notice
	 * is not rendered at all.
	 *
	 * @return void
	 */
	public function test_floor_notice_is_omitted_when_the_source_reports_no_floor(): void {
		$this->register_test_services();
		$this->seed_catalogue( '3.9.2' );

		$output = $this->render_version_switch_field();

		$this->assertStringContainsString( '<select', $output );
		$this->assertStringNotContainsString( 'are not installable', $output );
	}

	/**
	 * A source that reports a floor explains where the catalogue stops.
	 *
	 * @return void
	 */
	public function test_floor_notice_is_rendered_when_the_source_reports_a_floor(): void {
		$this->register_test_services();

		set_transient(
			$this->source->get_cache_key(),
			[
				'versions' => [
					[
						'version' => '3.9.2',
						'url'     => 'https://example.org/code-snippets.3.9.2.zip',
					],
				],
				'floor'    => '3.6.0',
			],
			HOUR_IN_SECONDS
		);

		$output = $this->render_version_switch_field();

		$this->assertStringContainsString( 'Versions before 3.6.0 are not installable.', $output );
	}

	/**
	 * The WordPress.org source drops the trunk entry and lists releases newest
	 * first, and reports no floor because every release remains installable.
	 *
	 * @return void
	 */
	public function test_wordpress_org_source_orders_releases_and_drops_trunk(): void {
		$this->next_response = $this->build_response(
			200,
			wp_json_encode(
				[
					'versions' => [
						'3.6.5' => 'https://downloads.wordpress.org/plugin/code-snippets.3.6.5.zip',
						'trunk' => 'https://downloads.wordpress.org/plugin/code-snippets.zip',
						'3.9.2' => 'https://downloads.wordpress.org/plugin/code-snippets.3.9.2.zip',
						'3.7.0' => 'https://downloads.wordpress.org/plugin/code-snippets.3.7.0.zip',
					],
				]
			)
		);

		$catalogue = ( new WordPress_Org_Version_Source() )->fetch_catalogue();

		$this->assertSame( [ '3.9.2', '3.7.0', '3.6.5' ], wp_list_pluck( $catalogue['versions'], 'version' ) );
		$this->assertSame( '', $catalogue['floor'] );
		$this->assertStringContainsString( 'api.wordpress.org', $this->request_urls[0] );
	}

	/**
	 * A malformed response is an error rather than an empty catalogue, so it is
	 * not cached in place of the real list.
	 *
	 * @return void
	 */
	public function test_wordpress_org_source_rejects_a_malformed_response(): void {
		$this->next_response = $this->build_response( 200, 'not json' );

		$this->assertWPError( ( new WordPress_Org_Version_Source() )->fetch_catalogue() );
	}

	/**
	 * The catalogue entry already carries the download URL, which the installer
	 * fetches itself, so nothing is left to clean up.
	 *
	 * @return void
	 */
	public function test_wordpress_org_source_hands_over_the_download_url(): void {
		$source = new WordPress_Org_Version_Source();

		$package = $source->fetch_package(
			[
				'version' => '3.9.2',
				'url'     => 'https://downloads.wordpress.org/plugin/code-snippets.3.9.2.zip',
			]
		);

		$this->assertSame( 'https://downloads.wordpress.org/plugin/code-snippets.3.9.2.zip', $package['package'] );
		$this->assertFalse( $package['cleanup'] );
		$this->assertWPError( $source->fetch_package( [ 'version' => '3.9.2' ] ) );
	}

	/**
	 * WordPress.org is always reachable and tracks nothing on this site's behalf.
	 *
	 * @return void
	 */
	public function test_wordpress_org_source_is_always_available_and_reports_nothing(): void {
		$source = new WordPress_Org_Version_Source();

		$this->assertTrue( $source->is_available() );
		$this->assertSame( self::WP_ORG_CACHE_KEY, $source->get_cache_key() );
		$this->assertSame( '', $source->get_floor_notice( '3.6.0' ) );
		$this->assertSame( '', $source->get_switch_warning( [ 'version' => '3.0.0' ] ) );

		$source->report_installed_version( '3.9.2' );

		$this->assertSame( [], $this->request_urls );
	}
}
