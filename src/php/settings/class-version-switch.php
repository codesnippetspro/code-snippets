<?php
/**
 * Class-based version switching functionality for the Code Snippets plugin.
 *
 * Converted from procedural `version-switch.php` to an OO class `Version_Switch`.
 *
 * @package    Code_Snippets
 * @subpackage Settings
 */

namespace Code_Snippets\Settings;

// Configuration constants for version switching
const VERSION_CACHE_KEY = 'code_snippets_available_versions';
const PROGRESS_KEY = 'code_snippets_version_switch_progress';
const VERSION_CACHE_DURATION = HOUR_IN_SECONDS;
const PROGRESS_TIMEOUT = 5 * MINUTE_IN_SECONDS;
const WORDPRESS_API_ENDPOINT = 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=code-snippets';

class Version_Switch {
	/**
	 * Initialize hook registrations.
	 * Call this after the file is required.
	 */
	public static function init(): void {
		add_action( 'wp_ajax_code_snippets_switch_version', [ __CLASS__, 'ajax_switch_version' ] );
		add_action( 'wp_ajax_code_snippets_refresh_versions', [ __CLASS__, 'ajax_refresh_versions' ] );
	}

	public static function get_available_versions(): array {
		$versions = get_transient( VERSION_CACHE_KEY );

		if ( false === $versions ) {
			$response = wp_remote_get( WORDPRESS_API_ENDPOINT );

			if ( is_wp_error( $response ) ) {
				return [];
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( ! $data || ! isset( $data['versions'] ) ) {
				return [];
			}

			// Filter out 'trunk' and sort versions
			$versions = [];
			foreach ( $data['versions'] as $version => $download_url ) {
				if ( 'trunk' !== $version ) {
					$versions[] = [
						'version' => $version,
						'url' => $download_url,
					];
				}
			}

			// Sort versions in descending order
			usort( $versions, function( $a, $b ) {
				return version_compare( $b['version'], $a['version'] );
			});

			// Cache for configured duration
			set_transient( VERSION_CACHE_KEY, $versions, VERSION_CACHE_DURATION );
		}

		return $versions;
	}

	public static function get_current_version(): string {
		return defined( 'CODE_SNIPPETS_VERSION' ) ? CODE_SNIPPETS_VERSION : '0.0.0';
	}

	public static function is_version_switch_in_progress(): bool {
		return get_transient( PROGRESS_KEY ) !== false;
	}

	public static function clear_version_caches(): void {
		delete_transient( VERSION_CACHE_KEY );
		delete_transient( PROGRESS_KEY );
	}

	public static function validate_target_version( string $target_version, array $available_versions ): array {
		if ( empty( $target_version ) ) {
			return [
				'success' => false,
				'message' => __( 'No target version specified.', 'code-snippets' ),
				'download_url' => '',
			];
		}

		foreach ( $available_versions as $version_info ) {
			if ( $version_info['version'] === $target_version ) {
				return [
					'success' => true,
					'message' => '',
					'download_url' => $version_info['url'],
				];
			}
		}

		return [
			'success' => false,
			'message' => __( 'Invalid version specified.', 'code-snippets' ),
			'download_url' => '',
		];
	}

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

	public static function perform_version_install( string $download_url ) {
		if ( ! function_exists( 'wp_update_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		if ( ! function_exists( 'show_message' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$update_handler = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $update_handler );

		global $code_snippets_last_update_handler, $code_snippets_last_upgrader;
		$code_snippets_last_update_handler = $update_handler;
		$code_snippets_last_upgrader = $upgrader;

		return $upgrader->install( $download_url, [
			'overwrite_package'   => true,
			'clear_update_cache'  => true,
		] );
	}

	public static function extract_handler_messages( $update_handler, $upgrader ): string {
		$handler_messages = '';

		if ( isset( $update_handler ) ) {
			if ( method_exists( $update_handler, 'get_errors' ) ) {
				$errs = $update_handler->get_errors();
				if ( $errs instanceof \WP_Error && $errs->has_errors() ) {
					$handler_messages .= implode( "\n", $errs->get_error_messages() );
				}
			}
			if ( method_exists( $update_handler, 'get_error_messages' ) ) {
				$em = $update_handler->get_error_messages();
				if ( $em ) {
					$handler_messages .= "\n" . $em;
				}
			}
			if ( method_exists( $update_handler, 'get_upgrade_messages' ) ) {
				$upgrade_msgs = $update_handler->get_upgrade_messages();
				if ( is_array( $upgrade_msgs ) ) {
					$handler_messages .= "\n" . implode( "\n", $upgrade_msgs );
				} elseif ( $upgrade_msgs ) {
					$handler_messages .= "\n" . (string) $upgrade_msgs;
				}
			}
		}

		if ( empty( $handler_messages ) && isset( $upgrader->result ) ) {
			if ( is_wp_error( $upgrader->result ) ) {
				$handler_messages = implode( "\n", $upgrader->result->get_error_messages() );
			} else {
				$handler_messages = is_scalar( $upgrader->result ) ? (string) $upgrader->result : print_r( $upgrader->result, true );
			}
		}

		return trim( $handler_messages );
	}

	public static function log_version_switch_attempt( string $target_version, $result, string $details = '' ): void {
		if ( function_exists( 'error_log' ) ) {
			error_log( sprintf( 'Code Snippets version switch failed. target=%s, result=%s, details=%s', $target_version, var_export( $result, true ), $details ) );
		}
	}

	public static function handle_installation_failure( string $target_version, string $download_url, $install_result ): array {
		global $code_snippets_last_update_handler, $code_snippets_last_upgrader;

		$handler_messages = self::extract_handler_messages( $code_snippets_last_update_handler, $code_snippets_last_upgrader );
		self::log_version_switch_attempt( $target_version, $install_result, "URL: $download_url, Messages: $handler_messages" );

		$fallback_message = __( 'Failed to switch versions. Please try again.', 'code-snippets' );
		if ( ! empty( $handler_messages ) ) {
			$short = wp_trim_words( wp_strip_all_tags( $handler_messages ), 40, '...' );
			$fallback_message = sprintf( '%s %s', $fallback_message, $short );
		}

		return [
			'success' => false,
			'message' => $fallback_message,
		];
	}

	public static function handle_version_switch( string $target_version ): array {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return self::create_error_response( __( 'You do not have permission to update plugins.', 'code-snippets' ) );
		}

		$available_versions = self::get_available_versions();
		$validation = self::validate_target_version( $target_version, $available_versions );

		if ( ! $validation['success'] ) {
			return self::create_error_response( $validation['message'] );
		}

		if ( self::get_current_version() === $target_version ) {
			return self::create_error_response( __( 'Already on the specified version.', 'code-snippets' ) );
		}

		set_transient( PROGRESS_KEY, $target_version, PROGRESS_TIMEOUT );

		$install_result = self::perform_version_install( $validation['download_url'] );

		delete_transient( PROGRESS_KEY );

		if ( is_wp_error( $install_result ) ) {
			return self::create_error_response( $install_result->get_error_message() );
		}

		if ( $install_result ) {
			delete_transient( VERSION_CACHE_KEY );

			return [
				'success' => true,
				'message' => sprintf( __( 'Successfully switched to version %s. Please refresh the page to see changes.', 'code-snippets' ), $target_version ),
			];
		}

		return self::handle_installation_failure( $target_version, $validation['download_url'], $install_result );
	}

	public static function render_version_switch_field( array $args ): void {
		$current_version = self::get_current_version();
		$available_versions = self::get_available_versions();
		$is_switching = self::is_version_switch_in_progress();

		?>
		<div class="code-snippets-version-switch">
			<p>
				<strong><?php esc_html_e( 'Current Version:', 'code-snippets' ); ?></strong> 
				<span class="current-version"><?php echo esc_html( $current_version ); ?></span>
			</p>

			<?php if ( $is_switching ) : ?>
				<div class="notice notice-info inline">
					<p><?php esc_html_e( 'Version switch in progress. Please wait...', 'code-snippets' ); ?></p>
				</div>
			<?php else : ?>
				<p>
					<label for="target_version">
						<?php esc_html_e( 'Switch to Version:', 'code-snippets' ); ?>
					</label>
					<select id="target_version" name="target_version" <?php disabled( empty( $available_versions ) ); ?>>
						<option value=""><?php esc_html_e( 'Select a version...', 'code-snippets' ); ?></option>
						<?php foreach ( $available_versions as $version_info ) : ?>
							<option value="<?php echo esc_attr( $version_info['version'] ); ?>" 
								<?php selected( $version_info['version'], $current_version ); ?>>
								<?php echo esc_html( $version_info['version'] ); ?>
								<?php if ( $version_info['version'] === $current_version ) : ?>
									<?php esc_html_e( ' (Current)', 'code-snippets' ); ?>
								<?php endif; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<button type="button" id="switch-version-btn" class="button button-secondary" disabled
						<?php disabled( empty( $available_versions ) ); ?>>
						<?php esc_html_e( 'Switch Version', 'code-snippets' ); ?>
					</button>
				</p>

				<div id="version-switch-result" class="notice" style="display: none;"></div>
			<?php endif; ?>
		</div><?php
	}

	public static function ajax_switch_version(): void {
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'code_snippets_version_switch' ) ) {
			wp_die( __( 'Security check failed.', 'code-snippets' ) );
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( [
				'message' => __( 'You do not have permission to update plugins.', 'code-snippets' ),
			] );
		}

		$target_version = sanitize_text_field( $_POST['target_version'] ?? '' );

		if ( empty( $target_version ) ) {
			wp_send_json_error( [
				'message' => __( 'No target version specified.', 'code-snippets' ),
			] );
		}

		$result = self::handle_version_switch( $target_version );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	public static function render_refresh_versions_field( array $args ): void {
		?>
		<button type="button" id="refresh-versions-btn" class="button button-secondary">
			<?php esc_html_e( 'Refresh Available Versions', 'code-snippets' ); ?>
		</button>
		<p class="description">
			<?php esc_html_e( 'Check for the latest available plugin versions from WordPress.org.', 'code-snippets' ); ?>
		</p><?php
	}

	public static function ajax_refresh_versions(): void {
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'code_snippets_refresh_versions' ) ) {
			wp_die( __( 'Security check failed.', 'code-snippets' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'You do not have permission to manage options.', 'code-snippets' ),
			] );
		}

		delete_transient( VERSION_CACHE_KEY );
		self::get_available_versions();

		wp_send_json_success( [
			'message' => __( 'Available versions updated successfully.', 'code-snippets' ),
		] );
	}

	public static function render_version_switch_warning(): void {
		?>
		<div id="version-switch-warning" class="notice notice-warning" style="display: none; margin-block-start: 20px;">
			<p>
				<strong><?php esc_html_e( 'Warning:', 'code-snippets' ); ?></strong>
				<?php esc_html_e( 'Switching versions may cause compatibility issues. Always backup your site before switching versions.', 'code-snippets' ); ?>
			</p>
		</div>
		<?php
	}
}

// Initialize hooks when the file is loaded.
Version_Switch::init();
