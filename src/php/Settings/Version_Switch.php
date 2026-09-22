<?php

namespace Code_Snippets\Settings;

use WP_Error;
use function Code_Snippets\code_snippets;

/**
 * Version switching functionality for the Code Snippets plugin.
 *
 * This class owns the parts of switching that do not vary: the caches, the
 * progress marker, the capability and nonce checks, and the rendering. Where
 * versions come from, and how a package is written to disk, are supplied by a
 * Version_Source and a Package_Installer, which an edition replaces through the
 * `code_snippets_version_source` and `code_snippets_package_installer` filters.
 *
 * @package    Code_Snippets
 * @subpackage Settings
 */
class Version_Switch {

	/**
	 * Transient key used to indicate when a version switch is currently taking place.
	 */
	private const PROGRESS_KEY = 'code_snippets_version_switch_progress';

	/**
	 * Transient key holding the code and message of the last failed catalogue
	 * request.
	 */
	private const ERROR_KEY = 'code_snippets_version_switch_error';

	/**
	 * Duration of the version cache transient.
	 */
	private const VERSION_CACHE_DURATION = HOUR_IN_SECONDS;

	/**
	 * Duration of the last-error transient.
	 */
	private const ERROR_CACHE_DURATION = 5 * MINUTE_IN_SECONDS;

	/**
	 * Duration of the 'in progress' transient.
	 */
	private const PROGRESS_TIMEOUT = 5 * MINUTE_IN_SECONDS;

	/**
	 * Source supplying the available versions.
	 *
	 * @var Version_Source|null
	 */
	private static ?Version_Source $source = null;

	/**
	 * Initialise class.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_ajax_code_snippets_switch_version', [ __CLASS__, 'ajax_switch_version' ] );
		add_action( 'wp_ajax_code_snippets_refresh_versions', [ __CLASS__, 'ajax_refresh_versions' ] );
	}

	/**
	 * Retrieve the source supplying available versions.
	 *
	 * @return Version_Source
	 */
	public static function get_source(): Version_Source {
		if ( null === self::$source ) {
			/**
			 * Filters the source the version switcher lists versions from.
			 *
			 * @param Version_Source $source Default source.
			 */
			$source = apply_filters( 'code_snippets_version_source', new WordPress_Org_Version_Source() );

			self::$source = $source instanceof Version_Source ? $source : new WordPress_Org_Version_Source();
		}

		return self::$source;
	}

	/**
	 * Retrieve the installer that writes a package to disk.
	 *
	 * @return Package_Installer
	 */
	public static function get_installer(): Package_Installer {
		/**
		 * Filters the installer the version switcher writes packages with.
		 *
		 * @param Package_Installer $installer Default installer.
		 */
		$installer = apply_filters( 'code_snippets_package_installer', new Upgrader_Package_Installer() );

		return $installer instanceof Package_Installer ? $installer : new Upgrader_Package_Installer();
	}

	/**
	 * Discard the memoised source, so the next call resolves the filter again.
	 *
	 * @return void
	 */
	public static function reset_source(): void {
		self::$source = null;
	}

	/**
	 * Determine whether versions can currently be listed.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return self::get_source()->is_available();
	}

	/**
	 * Explain why the switcher is unavailable.
	 *
	 * @return array{message: string, action_url: string, action_label: string}
	 */
	public static function get_unavailable_notice(): array {
		return self::get_source()->get_unavailable_notice();
	}

	/**
	 * Retrieve the cached catalogue, fetching it from the source when the cache
	 * is cold.
	 *
	 * A failed request is not cached: a transient outage must not blank the
	 * switcher for the full cache duration.
	 *
	 * @return array{versions: array<int, array<string, mixed>>, floor: string}
	 */
	private static function get_catalogue(): array {
		$source = self::get_source();
		$cached = get_transient( $source->get_cache_key() );

		if ( is_array( $cached ) && isset( $cached['versions'] ) ) {
			return $cached;
		}

		$empty = [
			'versions' => [],
			'floor'    => '',
		];

		if ( ! $source->is_available() ) {
			return $empty;
		}

		$result = $source->fetch_catalogue();

		if ( is_wp_error( $result ) ) {
			set_transient(
				self::ERROR_KEY,
				[
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
				],
				self::ERROR_CACHE_DURATION
			);
			return $empty;
		}

		$catalogue = [
			'versions' => $result['versions'],
			'floor'    => $result['floor'],
		];

		delete_transient( self::ERROR_KEY );
		set_transient( $source->get_cache_key(), $catalogue, self::VERSION_CACHE_DURATION );

		return $catalogue;
	}

	/**
	 * Retrieve a list of plugin versions available for switching.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_available_versions(): array {
		return self::get_catalogue()['versions'];
	}

	/**
	 * Retrieve the oldest installable version, if the source reported one.
	 *
	 * @return string
	 */
	public static function get_version_floor(): string {
		return self::get_catalogue()['floor'];
	}

	/**
	 * Retrieve the code of the last catalogue request failure.
	 *
	 * Sites upgrading mid-cache still hold the previous shape — a bare code —
	 * for up to ERROR_CACHE_DURATION.
	 *
	 * @return string
	 */
	public static function get_last_error_code(): string {
		$error = get_transient( self::ERROR_KEY );

		if ( is_array( $error ) ) {
			return isset( $error['code'] ) ? (string) $error['code'] : '';
		}

		return is_string( $error ) ? $error : '';
	}

	/**
	 * Retrieve the message explaining the last catalogue request failure.
	 *
	 * @return string
	 */
	public static function get_last_error_message(): string {
		$error = get_transient( self::ERROR_KEY );

		return is_array( $error ) && isset( $error['message'] ) ? (string) $error['message'] : '';
	}

	/**
	 * Discard the cached catalogue and fetch a fresh one from the source.
	 *
	 * Used by the maintenance tools, where the point of clearing caches is to see
	 * what the source is serving right now rather than what it served an hour ago.
	 *
	 * @return array<int, array<string, mixed>> The freshly fetched versions.
	 */
	public static function refresh_available_versions(): array {
		delete_transient( self::get_source()->get_cache_key() );
		delete_transient( self::ERROR_KEY );

		return self::get_available_versions();
	}

	/**
	 * Retrieve the current plugin version.
	 *
	 * @return string
	 */
	public static function get_current_version(): string {
		return defined( 'CODE_SNIPPETS_VERSION' ) ? CODE_SNIPPETS_VERSION : '0.0.0';
	}

	/**
	 * Determine if a version switch is currently taking place.
	 *
	 * @return bool
	 */
	public static function is_version_switch_in_progress(): bool {
		return get_transient( self::PROGRESS_KEY ) !== false;
	}

	/**
	 * Purge transient data associated with this class.
	 *
	 * @return void
	 */
	public static function clear_version_caches(): void {
		delete_transient( self::get_source()->get_cache_key() );
		delete_transient( self::PROGRESS_KEY );
		delete_transient( self::ERROR_KEY );
	}

	/**
	 * Locate a version in a catalogue.
	 *
	 * @param string                           $target_version     Version to find.
	 * @param array<int, array<string, mixed>> $available_versions Catalogue entries.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function find_version( string $target_version, array $available_versions ): ?array {
		foreach ( $available_versions as $version_info ) {
			if ( isset( $version_info['version'] ) && $version_info['version'] === $target_version ) {
				return $version_info;
			}
		}

		return null;
	}

	/**
	 * Validate that a target version is valid.
	 *
	 * This is a usability guard rather than a security boundary: a source is
	 * free to refuse the version again when the package is requested.
	 *
	 * @param string                           $target_version     Target version for switching.
	 * @param array<int, array<string, mixed>> $available_versions List of available versions.
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function validate_target_version( string $target_version, array $available_versions ): array {
		if ( empty( $target_version ) ) {
			return [
				'success' => false,
				'message' => __( 'No target version specified.', 'code-snippets' ),
			];
		}

		if ( null === self::find_version( $target_version, $available_versions ) ) {
			return [
				'success' => false,
				'message' => __( 'Invalid version specified.', 'code-snippets' ),
			];
		}

		return [
			'success' => true,
			'message' => '',
		];
	}

	/**
	 * Create a response indicating an error occurred.
	 *
	 * @param string $message           Error message.
	 * @param string $technical_details Additional details.
	 *
	 * @return array
	 *
	 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
	 */
	public static function create_error_response( string $message, string $technical_details = '' ): array {
		if ( ! empty( $technical_details ) ) {
			if ( function_exists( 'error_log' ) ) {
				error_log( sprintf( 'Code Snippets version switch error: %s. Details: %s', $message, $technical_details ) );
			}
		}

		return [
			'success' => false,
			'message' => $message,
		];
	}

	/**
	 * Install a plugin version from a package.
	 *
	 * @param string $package Local path to a package, or a download URL.
	 *
	 * @return array{version: string}|WP_Error
	 */
	public static function perform_version_install( string $package ) {
		return self::get_installer()->install( $package );
	}

	/**
	 * Handle switching to a different plugin version.
	 *
	 * @param string $target_version Target version to switch to.
	 *
	 * @return array Result data.
	 */
	public static function handle_version_switch( string $target_version ): array {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return self::create_error_response( __( 'You do not have permission to update plugins.', 'code-snippets' ) );
		}

		$source = self::get_source();

		if ( ! $source->is_available() ) {
			return self::create_error_response( $source->get_unavailable_notice()['message'] );
		}

		$available_versions = self::get_available_versions();
		$validation = self::validate_target_version( $target_version, $available_versions );

		if ( ! $validation['success'] ) {
			return self::create_error_response( $validation['message'] );
		}

		if ( self::get_current_version() === $target_version ) {
			return self::create_error_response( __( 'Already on the specified version.', 'code-snippets' ) );
		}

		set_transient( self::PROGRESS_KEY, $target_version, self::PROGRESS_TIMEOUT );

		$package = $source->fetch_package( self::find_version( $target_version, $available_versions ) );

		if ( is_wp_error( $package ) ) {
			delete_transient( self::PROGRESS_KEY );

			// The cached catalogue is stale when the source no longer recognises a
			// version it listed, so discard it and let the next render refetch.
			delete_transient( $source->get_cache_key() );

			return self::create_error_response( $package->get_error_message() );
		}

		try {
			$install_result = self::perform_version_install( $package['package'] );
		} finally {
			if ( $package['cleanup'] ) {
				wp_delete_file( $package['package'] );
			}

			delete_transient( self::PROGRESS_KEY );
		}

		if ( is_wp_error( $install_result ) ) {
			return self::create_error_response(
				sprintf(
					// translators: %s: reason the installation failed.
					__( 'Failed to switch versions: %s', 'code-snippets' ),
					$install_result->get_error_message()
				)
			);
		}

		// The version on disk is the authoritative one when the installer can read
		// it, since the package decides what was actually written.
		$installed_version = $install_result['version'] ? $install_result['version'] : $target_version;

		delete_transient( $source->get_cache_key() );
		$source->report_installed_version( $installed_version );

		return [
			'success' => true,
			'message' => sprintf(
				// translators: %s: new version number.
				__( 'Successfully switched to version %s. Please refresh the page to see changes.', 'code-snippets' ),
				$installed_version
			),
		];
	}

	/**
	 * Render settings page field for the version switcher.
	 *
	 * @return void
	 */
	public static function render_version_switch_field(): void {
		$current_version = self::get_current_version();

		?>
		<div class="code-snippets-version-switch">
		<p>
			<strong><?php esc_html_e( 'Current Version:', 'code-snippets' ); ?></strong>
			<span class="current-version"><?php echo esc_html( $current_version ); ?></span>
		</p>

		<?php

		if ( self::is_version_switch_in_progress() ) {
			?>
			<div class="notice code-snippets-notice notice-info inline">
				<p><?php esc_html_e( 'Version switch in progress. Please wait…', 'code-snippets' ); ?></p>
			</div>
			</div>
			<?php
			return;
		}

		if ( ! self::is_available() ) {
			self::render_unavailable_notice();
			?>
			</div>
			<?php
			return;
		}

		$available_versions = self::get_available_versions();

		if ( ! $available_versions ) {
			self::render_empty_catalogue_notice();
			?>
			</div>
			<?php
			return;
		}

		$floor_notice = self::get_source()->get_floor_notice( self::get_version_floor() );

		?>
			<p>
				<label for="target_version">
					<?php esc_html_e( 'Switch to Version:', 'code-snippets' ); ?>
				</label>
				<select id="target_version" name="target_version">
					<option value=""><?php esc_html_e( 'Select a version…', 'code-snippets' ); ?></option>
					<?php foreach ( $available_versions as $version_info ) { ?>
						<option value="<?php echo esc_attr( $version_info['version'] ); ?>"
							<?php selected( $version_info['version'], $current_version ); ?>>
							<?php echo esc_html( self::describe_version( $version_info, $current_version ) ); ?>
						</option>
					<?php } ?>
				</select>
			</p>

			<?php if ( $floor_notice ) { ?>
				<p class="description"><?php echo esc_html( $floor_notice ); ?></p>
			<?php } ?>

			<p>
				<button type="button" id="switch-version-btn" class="button button-secondary" disabled>
					<?php esc_html_e( 'Switch Version', 'code-snippets' ); ?>
				</button>
			</p>

			<div id="version-switch-result" class="notice code-snippets-notice" style="display: none;"></div>
		</div>
		<?php
	}

	/**
	 * Build the label shown for a single version in the dropdown.
	 *
	 * @param array<string, mixed> $version_info    Catalogue entry.
	 * @param string               $current_version Version currently installed.
	 *
	 * @return string
	 */
	private static function describe_version( array $version_info, string $current_version ): string {
		$version = (string) $version_info['version'];
		$release_mode = isset( $version_info['release_mode'] ) ? (string) $version_info['release_mode'] : '';
		$tested_up_to = isset( $version_info['tested_up_to'] ) ? (string) $version_info['tested_up_to'] : '';

		if ( $version === $current_version ) {
			// translators: %s: plugin version number.
			$label = sprintf( __( '%s (current)', 'code-snippets' ), $version );
		} elseif ( 'beta' === $release_mode ) {
			// translators: %s: plugin version number.
			$label = sprintf( __( '%s (beta)', 'code-snippets' ), $version );
		} elseif ( 'rc' === $release_mode ) {
			// translators: %s: plugin version number.
			$label = sprintf( __( '%s (release candidate)', 'code-snippets' ), $version );
		} elseif ( ! empty( $version_info['is_latest'] ) ) {
			// translators: %s: plugin version number.
			$label = sprintf( __( '%s (latest)', 'code-snippets' ), $version );
		} else {
			$label = $version;
		}

		if ( ! $tested_up_to ) {
			return $label;
		}

		return sprintf(
			// translators: 1: labelled plugin version, 2: WordPress version number.
			__( '%1$s — tested up to WordPress %2$s', 'code-snippets' ),
			$label,
			$tested_up_to
		);
	}

	/**
	 * Render the explanation shown in place of the switcher when the catalogue
	 * holds no versions.
	 *
	 * The list is empty both when the request for it failed and when the source
	 * answered with nothing to install, which are not the same thing to the user.
	 *
	 * @return void
	 */
	private static function render_empty_catalogue_notice(): void {
		$error_message = self::get_last_error_message();

		if ( $error_message ) {
			?>
			<div class="notice code-snippets-notice notice-warning inline">
				<p><?php echo esc_html( $error_message ); ?></p>
			</div>
			<?php
			return;
		}

		?>
		<p class="description">
			<?php esc_html_e( 'There are no versions available to install.', 'code-snippets' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the notice shown in place of the switcher when versions cannot be
	 * listed for this site.
	 *
	 * @return void
	 */
	private static function render_unavailable_notice(): void {
		$notice = self::get_unavailable_notice();

		?>
		<div class="notice code-snippets-notice notice-warning inline">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
			<?php if ( $notice['action_url'] && $notice['action_label'] ) { ?>
				<p>
					<a href="<?php echo esc_url( $notice['action_url'] ); ?>" class="button button-primary">
						<?php echo esc_html( $notice['action_label'] ); ?>
					</a>
				</p>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Handle version switching through AJAX.
	 *
	 * @return void
	 */
	public static function ajax_switch_version(): void {
		check_ajax_referer( 'code_snippets_version_switch', 'nonce' );

		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to update plugins.', 'code-snippets' ) ] );
		}

		$target_version = sanitize_text_field( wp_unslash( $_POST['target_version'] ?? '' ) );

		if ( empty( $target_version ) ) {
			wp_send_json_error( [ 'message' => __( 'No target version specified.', 'code-snippets' ) ] );
		}

		$result = self::handle_version_switch( $target_version );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Render settings page field for the refresh version button.
	 *
	 * @return void
	 */
	public static function render_refresh_versions_field(): void {
		printf(
			'<button type="button" id="refresh-versions-btn" class="button button-secondary">%s</button>',
			esc_html__( 'Refresh Available Versions', 'code-snippets' )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html( self::get_source()->get_refresh_description() )
		);
	}

	/**
	 * AJAX handler for refreshing the list of available versions.
	 *
	 * @return void
	 */
	public static function ajax_refresh_versions(): void {
		check_ajax_referer( 'code_snippets_refresh_versions', 'nonce' );

		if ( ! code_snippets()->current_user_can() ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to manage options.', 'code-snippets' ) ] );
		}

		self::refresh_available_versions();

		wp_send_json_success( [ 'message' => __( 'Available versions updated successfully.', 'code-snippets' ) ] );
	}

	/**
	 * Render warning notice.
	 *
	 * @return void
	 */
	public static function render_version_switch_warning(): void {
		?>
		<div id="version-switch-warning" class="notice code-snippets-notice notice-warning hidden" role="region">
			<p>
				<strong><?php esc_html_e( 'Warning:', 'code-snippets' ); ?></strong>
				<?php esc_html_e( 'Switching versions may cause compatibility issues. Always backup your site before switching versions.', 'code-snippets' ); ?>
			</p>
			<p><?php esc_html_e( 'Beta and release candidate builds are not recommended for production sites.', 'code-snippets' ); ?></p>
		</div>
		<?php
	}
}
