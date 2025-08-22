<?php
/**
 * Handles version switching functionality for the Code Snippets plugin.
 *
 * @package    Code_Snippets
 * @subpackage Settings
 */

namespace Code_Snippets\Settings\VersionSwitch;

use function Code_Snippets\code_snippets;

/**
 * Get available plugin versions from WordPress.org repository
 *
 * @return array Array of version information
 */
function get_available_versions(): array {
	$transient_key = 'code_snippets_available_versions';
	$versions = get_transient( $transient_key );

	if ( false === $versions ) {
		$response = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=code-snippets' );

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

		// Cache for 1 hour
		set_transient( $transient_key, $versions, HOUR_IN_SECONDS );
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
	return get_transient( 'code_snippets_version_switch_progress' ) !== false;
}

/**
 * Handle version switch request
 *
 * @param string $target_version Target version to switch to
 * @return array Result array with success status and message
 */
function handle_version_switch( string $target_version ): array {
	// Check user capabilities
	if ( ! current_user_can( 'update_plugins' ) ) {
		return [
			'success' => false,
			'message' => __( 'You do not have permission to update plugins.', 'code-snippets' ),
		];
	}

	// Validate target version
	$available_versions = get_available_versions();
	$version_exists = false;
	$download_url = '';

	foreach ( $available_versions as $version_info ) {
		if ( $version_info['version'] === $target_version ) {
			$version_exists = true;
			$download_url = $version_info['url'];
			break;
		}
	}

	if ( ! $version_exists ) {
		return [
			'success' => false,
			'message' => __( 'Invalid version specified.', 'code-snippets' ),
		];
	}

	// Check if already on target version
	if ( get_current_version() === $target_version ) {
		return [
			'success' => false,
			'message' => __( 'Already on the specified version.', 'code-snippets' ),
		];
	}

	// Set switch in progress
	set_transient( 'code_snippets_version_switch_progress', $target_version, 5 * MINUTE_IN_SECONDS );

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

	// Create upgrader instance
	$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );

	// Perform the upgrade/downgrade
	$result = $upgrader->upgrade( plugin_basename( CODE_SNIPPETS_FILE ), [
		'clear_destination' => true,
		'overwrite_package' => true,
	] );

	// Clear progress transient
	delete_transient( 'code_snippets_version_switch_progress' );

	if ( is_wp_error( $result ) ) {
		return [
			'success' => false,
			'message' => $result->get_error_message(),
		];
	}

	if ( $result ) {
		// Clear version cache
		delete_transient( 'code_snippets_available_versions' );

		return [
			'success' => true,
			'message' => sprintf(
				__( 'Successfully switched to version %s. Please refresh the page to see changes.', 'code-snippets' ),
				$target_version
			),
		];
	}

	return [
		'success' => false,
		'message' => __( 'Failed to switch versions. Please try again.', 'code-snippets' ),
	];
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
				<button type="button" id="switch-version-btn" class="button button-secondary" 
						<?php disabled( empty( $available_versions ) ); ?>>
					<?php esc_html_e( 'Switch Version', 'code-snippets' ); ?>
				</button>
			</p>

			<div id="version-switch-result" class="notice" style="display: none;"></div>

			<p class="description">
				<?php
				esc_html_e( 'Warning: Switching versions may cause compatibility issues. Always backup your site before switching versions. This feature allows you to install different versions of the Code Snippets plugin.', 'code-snippets' );
				?>
			</p>
		<?php endif; ?>
	</div>

	<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#switch-version-btn').on('click', function() {
			var targetVersion = $('#target_version').val();
			var $button = $(this);
			var $result = $('#version-switch-result');
			
			if (!targetVersion) {
				$result.removeClass('notice-success notice-error').addClass('notice-warning')
					.html('<p><?php esc_html_e( 'Please select a version to switch to.', 'code-snippets' ); ?></p>')
					.show();
				return;
			}

			// Disable button and show loading
			$button.prop('disabled', true).text('<?php esc_html_e( 'Switching...', 'code-snippets' ); ?>');
			$result.removeClass('notice-success notice-error notice-warning').addClass('notice-info')
				.html('<p><?php esc_html_e( 'Processing version switch. Please wait...', 'code-snippets' ); ?></p>')
				.show();

			// Make AJAX request
			$.post(ajaxurl, {
				action: 'code_snippets_switch_version',
				target_version: targetVersion,
				nonce: '<?php echo esc_js( wp_create_nonce( 'code_snippets_version_switch' ) ); ?>'
			})
			.done(function(response) {
				if (response.success) {
					$result.removeClass('notice-info notice-error').addClass('notice-success')
						.html('<p>' + response.data.message + '</p>');
					
					// Refresh page after 3 seconds
					setTimeout(function() {
						window.location.reload();
					}, 3000);
				} else {
					$result.removeClass('notice-info notice-success').addClass('notice-error')
						.html('<p>' + response.data.message + '</p>');
					$button.prop('disabled', false).text('<?php esc_html_e( 'Switch Version', 'code-snippets' ); ?>');
				}
			})
			.fail(function() {
				$result.removeClass('notice-info notice-success').addClass('notice-error')
					.html('<p><?php esc_html_e( 'An error occurred while switching versions. Please try again.', 'code-snippets' ); ?></p>');
				$button.prop('disabled', false).text('<?php esc_html_e( 'Switch Version', 'code-snippets' ); ?>');
			});
		});
	});
	</script>
	<?php
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
		<?php esc_html_e( 'Clear the cached list of available versions and fetch the latest from WordPress.org.', 'code-snippets' ); ?>
	</p>

	<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#refresh-versions-btn').on('click', function() {
			var $button = $(this);
			$button.prop('disabled', true).text('<?php esc_html_e( 'Refreshing...', 'code-snippets' ); ?>');

			$.post(ajaxurl, {
				action: 'code_snippets_refresh_versions',
				nonce: '<?php echo esc_js( wp_create_nonce( 'code_snippets_refresh_versions' ) ); ?>'
			})
			.done(function(response) {
				$button.text('<?php esc_html_e( 'Refreshed!', 'code-snippets' ); ?>');
				setTimeout(function() {
					$button.prop('disabled', false).text('<?php esc_html_e( 'Refresh Available Versions', 'code-snippets' ); ?>');
					// Reload page to show updated versions
					window.location.reload();
				}, 1000);
			})
			.fail(function() {
				$button.prop('disabled', false).text('<?php esc_html_e( 'Refresh Available Versions', 'code-snippets' ); ?>');
			});
		});
	});
	</script>
	<?php
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

	// Clear the cache
	delete_transient( 'code_snippets_available_versions' );
	
	// Fetch fresh data
	get_available_versions();

	wp_send_json_success( [
		'message' => __( 'Versions cache refreshed successfully.', 'code-snippets' ),
	] );
}

// Register AJAX handler
add_action( 'wp_ajax_code_snippets_refresh_versions', __NAMESPACE__ . '\\ajax_refresh_versions' );
