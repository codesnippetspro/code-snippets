<?php

namespace Code_Snippets\Settings;

use Plugin_Upgrader;
use WP_Ajax_Upgrader_Skin;
use WP_Error;
use WP_Upgrader_Skin;

/**
 * Installs a package with WordPress's own plugin upgrader.
 *
 * This is the switcher's default installer. `Plugin_Upgrader::install()` accepts
 * a local path as readily as a URL: `WP_Upgrader::download_package()` returns
 * its argument unchanged when it is not an http(s) or FTP URL and the file
 * exists.
 *
 * @package    Code_Snippets
 * @subpackage Settings
 */
class Upgrader_Package_Installer implements Package_Installer {

	/**
	 * Install a plugin package.
	 *
	 * @param string $package Local path to a package, or a download URL.
	 *
	 * @return array{version: string}|WP_Error
	 */
	public function install( string $package ) {
		if ( ! function_exists( 'wp_update_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		if ( ! function_exists( 'show_message' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$update_handler = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $update_handler );

		$result = $upgrader->install(
			$package,
			[
				'overwrite_package'  => true,
				'clear_update_cache' => true,
			]
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			$messages = $this->extract_handler_messages( $update_handler, $upgrader );

			return new WP_Error(
				'package_install_failed',
				$messages
					? wp_trim_words( wp_strip_all_tags( $messages ), 40 )
					: __( 'The package could not be installed.', 'code-snippets' )
			);
		}

		// The upgrader reports nothing about what it wrote, so the caller falls
		// back to the version it asked for.
		return [ 'version' => '' ];
	}

	/**
	 * Extract error messages from an upgrade handler.
	 *
	 * @param WP_Upgrader_Skin|null $update_handler Update handler.
	 * @param Plugin_Upgrader|null  $upgrader       Plugin upgrader.
	 *
	 * @return string
	 *
	 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
	 */
	public function extract_handler_messages( ?WP_Upgrader_Skin $update_handler, ?Plugin_Upgrader $upgrader ): string {
		$handler_messages = '';

		if ( isset( $update_handler ) ) {
			if ( method_exists( $update_handler, 'get_errors' ) ) {
				$errs = $update_handler->get_errors();
				if ( $errs instanceof WP_Error && $errs->has_errors() ) {
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
					$handler_messages .= "\n" . $upgrade_msgs;
				}
			}
		}

		if ( empty( $handler_messages ) && isset( $upgrader->result ) ) {
			if ( is_wp_error( $upgrader->result ) ) {
				$handler_messages = implode( "\n", $upgrader->result->get_error_messages() );
			} else {
				$handler_messages = is_scalar( $upgrader->result )
					? (string) $upgrader->result
					: print_r( $upgrader->result, true );
			}
		}

		return trim( $handler_messages );
	}
}
