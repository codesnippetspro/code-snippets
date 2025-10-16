<?php
/**
 * Handles version switching functionality for the Code Snippets plugin.
 *
 * This file provides a complete version switching system that allows users to:
 * - View available plugin versions from WordPress.org
 * - Switch between different versions safely
 * - Track progress during version switching
 * - Handle errors gracefully with detailed logging
 *
 * @package    Code_Snippets
 * @subpackage Settings
 */

namespace Code_Snippets\Settings\VersionSwitch;

// Configuration constants for version switching
const VERSION_CACHE_KEY = 'code_snippets_available_versions';
const PROGRESS_KEY = 'code_snippets_version_switch_progress';
const VERSION_CACHE_DURATION = HOUR_IN_SECONDS;
const PROGRESS_TIMEOUT = 5 * MINUTE_IN_SECONDS;
const WORDPRESS_API_ENDPOINT = 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=code-snippets';

/**
 * Get available plugin versions from WordPress.org repository
 *
 * @return array Array of version information
 */
function get_available_versions(): array {
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

/**
 * Get current plugin version
 *
 * @return string Current version
 */
function get_current_version(): string {
	return defined( 'CODE_SNIPPETS_VERSION' ) ? CODE_SNIPPETS_VERSION : '0.0.0';
}

/**
 * Check if a version switch is in progress
 *
 * @return bool True if switch is in progress
 */
function is_version_switch_in_progress(): bool {
	return get_transient( PROGRESS_KEY ) !== false;
}

/**
 * Clear version-related caches
 *
 * @return void
 */
function clear_version_caches(): void {
	delete_transient( VERSION_CACHE_KEY );
	delete_transient( PROGRESS_KEY );
}

/**
 * Validate target version against available versions
 *
 * @param string $target_version Target version to validate
 * @param array  $available_versions Array of available versions
 * @return array Validation result with success status, message, and download URL
 */
function validate_target_version( string $target_version, array $available_versions ): array {
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

/**
 * Create a standardized error response
 *
 * @param string $message User-friendly error message
 * @param string $technical_details Technical details for debugging (optional)
 * @return array Error response array
 */
function create_error_response( string $message, string $technical_details = '' ): array {
	if ( ! empty( $technical_details ) ) {
		// Log technical details for debugging
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
 * Perform the actual version installation using WordPress upgrader
 *
 * @param string $download_url URL to download the plugin version
 * @return bool|\WP_Error Installation result
 */
function perform_version_install( string $download_url ) {
	// Include WordPress upgrade functions
	if ( ! function_exists( 'wp_update_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/update.php';
	}
	if ( ! function_exists( 'show_message' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	if ( ! class_exists( 'Plugin_Upgrader' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}

	// Create update handler (captures Ajax responses and errors) and upgrader instance
	$update_handler = new \WP_Ajax_Upgrader_Skin();
	$upgrader = new \Plugin_Upgrader( $update_handler );

	// Store the handler globally so we can access it later for error extraction
	global $code_snippets_last_update_handler, $code_snippets_last_upgrader;
	$code_snippets_last_update_handler = $update_handler;
	$code_snippets_last_upgrader = $upgrader;

	// Perform the install/overwrite using the package download URL from WordPress.org
	return $upgrader->install( $download_url, [
		'overwrite_package'   => true,
		'clear_update_cache'  => true,
	] );
}

/**
 * Handle installation failure and extract useful error information
 *
 * @param string $target_version The target version that failed to install
 * @param string $download_url The download URL used
 * @param mixed  $install_result The result from the upgrader
 * @return array Error response with extracted information
 */
function handle_installation_failure( string $target_version, string $download_url, $install_result ): array {
	global $code_snippets_last_update_handler, $code_snippets_last_upgrader;

	$handler_messages = extract_handler_messages( $code_snippets_last_update_handler, $code_snippets_last_upgrader );
	
	// Log details for server-side debugging
	log_version_switch_attempt( $target_version, $install_result, "URL: $download_url, Messages: $handler_messages" );

	// Return a more informative message when possible (still user-friendly)
	$fallback_message = __( 'Failed to switch versions. Please try again.', 'code-snippets' );
	if ( ! empty( $handler_messages ) ) {
		// Trim and sanitize a bit for output
		$short = wp_trim_words( wp_strip_all_tags( $handler_messages ), 40, '...' );
		$fallback_message = sprintf( '%s %s', $fallback_message, $short );
	}

	return [
		'success' => false,
		'message' => $fallback_message,
	];
}

/**
 * Extract helpful messages from the update handler
 *
 * @param mixed $update_handler The WP_Ajax_Upgrader_Skin instance
 * @param mixed $upgrader The Plugin_Upgrader instance
 * @return string Extracted messages
 */
function extract_handler_messages( $update_handler, $upgrader ): string {
	$handler_messages = '';
	
	if ( isset( $update_handler ) ) {
		// Errors (WP_Ajax_Upgrader_Skin stores them)
		if ( method_exists( $update_handler, 'get_errors' ) ) {
			$errs = $update_handler->get_errors();
			if ( $errs instanceof \WP_Error && $errs->has_errors() ) {
				$handler_messages .= implode( "\n", $errs->get_error_messages() );
			}
		}
		// Error messages string
		if ( method_exists( $update_handler, 'get_error_messages' ) ) {
			$em = $update_handler->get_error_messages();
			if ( $em ) {
				$handler_messages .= "\n" . $em;
			}
		}
		// Upgrade messages (feedback/info)
		if ( method_exists( $update_handler, 'get_upgrade_messages' ) ) {
			$upgrade_msgs = $update_handler->get_upgrade_messages();
			if ( is_array( $upgrade_msgs ) ) {
				$handler_messages .= "\n" . implode( "\n", $upgrade_msgs );
			} elseif ( $upgrade_msgs ) {
				$handler_messages .= "\n" . (string) $upgrade_msgs;
			}
		}
	}

	// Fallback: if upgrader populated result with info, include it
	if ( empty( $handler_messages ) && isset( $upgrader->result ) ) {
		if ( is_wp_error( $upgrader->result ) ) {
			$handler_messages = implode( "\n", $upgrader->result->get_error_messages() );
		} else {
			$handler_messages = is_scalar( $upgrader->result ) ? (string) $upgrader->result : print_r( $upgrader->result, true );
		}
	}

	return trim( $handler_messages );
}

/**
 * Log version switch attempt for debugging
 *
 * @param string $target_version Target version
 * @param mixed  $result Installation result
 * @param string $details Additional details
 * @return void
 */
function log_version_switch_attempt( string $target_version, $result, string $details = '' ): void {
	if ( function_exists( 'error_log' ) ) {
		error_log( sprintf( 
			'Code Snippets version switch failed. target=%s, result=%s, details=%s', 
			$target_version, 
			var_export( $result, true ), 
			$details 
		) );
	}
}

/**
 * Handle version switch request
 *
 * @param string $target_version Target version to switch to
 * @return array Result array with success status and message
 */
function handle_version_switch( string $target_version ): array {

	if ( ! current_user_can( 'update_plugins' ) ) {
		return create_error_response( __( 'You do not have permission to update plugins.', 'code-snippets' ) );
	}

	$available_versions = get_available_versions();
	$validation = validate_target_version( $target_version, $available_versions );
	
	if ( ! $validation['success'] ) {
		return create_error_response( $validation['message'] );
	}

	if ( get_current_version() === $target_version ) {
		return create_error_response( __( 'Already on the specified version.', 'code-snippets' ) );
	}

	set_transient( PROGRESS_KEY, $target_version, PROGRESS_TIMEOUT );

	$install_result = perform_version_install( $validation['download_url'] );

	delete_transient( PROGRESS_KEY );

	if ( is_wp_error( $install_result ) ) {
		return create_error_response( $install_result->get_error_message() );
	}

	if ( $install_result ) {
		delete_transient( VERSION_CACHE_KEY );

		return [
			'success' => true,
			'message' => sprintf(
				__( 'Successfully switched to version %s. Please refresh the page to see changes.', 'code-snippets' ),
				$target_version
			),
		];
	}

	return handle_installation_failure( $target_version, $validation['download_url'], $install_result );
}

/**
 * Render the version switch field
 *
 * @param array $args Field arguments
 */
function render_version_switch_field( array $args ): void {
	$current_version = get_current_version();
	$available_versions = get_available_versions();
	$is_switching = is_version_switch_in_progress();

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

/**
 * AJAX handler for version switch
 */
function ajax_switch_version(): void {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'code_snippets_version_switch' ) ) {
		wp_die( __( 'Security check failed.', 'code-snippets' ) );
	}

	// Check user capabilities
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

	$result = handle_version_switch( $target_version );

	if ( $result['success'] ) {
		wp_send_json_success( $result );
	} else {
		wp_send_json_error( $result );
	}
}

// Register AJAX handler
add_action( 'wp_ajax_code_snippets_switch_version', __NAMESPACE__ . '\\ajax_switch_version' );

/**
 * Render refresh versions cache button
 *
 * @param array $args Field arguments
 */
function render_refresh_versions_field( array $args ): void {
	?>
	<button type="button" id="refresh-versions-btn" class="button button-secondary">
		<?php esc_html_e( 'Refresh Available Versions', 'code-snippets' ); ?>
	</button>
	<p class="description">
		<?php esc_html_e( 'Check for the latest available plugin versions from WordPress.org.', 'code-snippets' ); ?>
	</p><?php
}

/**
 * AJAX handler for refreshing versions cache
 */
function ajax_refresh_versions(): void {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'code_snippets_refresh_versions' ) ) {
		wp_die( __( 'Security check failed.', 'code-snippets' ) );
	}

	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [
			'message' => __( 'You do not have permission to manage options.', 'code-snippets' ),
		] );
	}

	// Clear the cache using our helper function
	delete_transient( VERSION_CACHE_KEY );
	
	// Fetch fresh data
	get_available_versions();

	wp_send_json_success( [
		'message' => __( 'Available versions updated successfully.', 'code-snippets' ),
	] );
}

// Register AJAX handler
add_action( 'wp_ajax_code_snippets_refresh_versions', __NAMESPACE__ . '\\ajax_refresh_versions' );

/**
 * Render the version switch warning that appears at the bottom
 * This should be called after all other version-related fields
 */
function render_version_switch_warning(): void {
	?>
	<div id="version-switch-warning" class="notice notice-warning" style="display: none; margin-top: 20px;">
		<p>
			<strong><?php esc_html_e( 'Warning:', 'code-snippets' ); ?></strong>
			<?php esc_html_e( 'Switching versions may cause compatibility issues. Always backup your site before switching versions.', 'code-snippets' ); ?>
		</p>
	</div>
	<?php
}
