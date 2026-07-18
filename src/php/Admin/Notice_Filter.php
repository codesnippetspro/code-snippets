<?php

namespace Code_Snippets\Admin;

use ReflectionFunction;
use ReflectionMethod;
use Throwable;
use WP_Screen;
use function Code_Snippets\code_snippets;
use const Code_Snippets\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * Filters out admin notices that do not originate from Code Snippets while on a Code Snippets admin screen,
 * preventing foreign notices from disrupting the plugin's navigation and sub-tab layout.
 *
 * @package Code_Snippets
 */
class Notice_Filter {

	/**
	 * Notice hooks cleared of foreign callbacks on Code Snippets screens.
	 *
	 * @var string[]
	 */
	private const NOTICE_HOOKS = [
		'admin_notices',
		'all_admin_notices',
		'user_admin_notices',
		'network_admin_notices',
	];

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'current_screen', [ $this, 'register_filtering' ] );
	}

	/**
	 * Activate notice filtering when the current screen belongs to Code Snippets.
	 *
	 * @param WP_Screen $screen Current admin screen.
	 *
	 * @return void
	 */
	public function register_filtering( WP_Screen $screen ) {
		if ( ! $this->is_code_snippets_screen( $screen ) ) {
			return;
		}

		if ( ! apply_filters( 'code_snippets/admin/filter_foreign_notices', true ) ) {
			return;
		}

		add_action( 'admin_head', [ $this, 'filter_foreign_notices' ], 0 );
		add_action( 'admin_head', [ $this, 'print_fallback_styles' ] );
	}

	/**
	 * Remove every notice callback that is not defined within the Code Snippets plugin directory.
	 *
	 * @return void
	 */
	public function filter_foreign_notices() {
		global $wp_filter;

		foreach ( self::NOTICE_HOOKS as $hook ) {
			if ( empty( $wp_filter[ $hook ] ) ) {
				continue;
			}

			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					if ( ! $this->is_code_snippets_callback( $callback['function'] ) ) {
						remove_action( $hook, $callback['function'], $priority );
					}
				}
			}
		}
	}

	/**
	 * Print inline styles that hide any residual foreign notices left in the notice region.
	 *
	 * @return void
	 */
	public function print_fallback_styles() {
		?>
		<style>
			#wpbody-content > .notice:not(.code-snippets-notice):not(.code-snippets-promotion),
			#wpbody-content > .update-nag,
			#wpbody-content > .updated:not(.code-snippets-notice),
			#wpbody-content > .error:not(.code-snippets-notice) {
				display: none !important;
			}
		</style>
		<?php
	}

	/**
	 * Determine whether a screen is one of the plugin's own admin screens.
	 *
	 * @param WP_Screen $screen Current admin screen.
	 *
	 * @return bool
	 */
	private function is_code_snippets_screen( WP_Screen $screen ): bool {
		if ( ! isset( code_snippets()->admin ) ) {
			return false;
		}

		foreach ( code_snippets()->admin->menus as $menu ) {
			foreach ( $menu->get_hooknames() as $hookname ) {
				foreach ( [ $hookname, $hookname . '-network' ] as $candidate ) {
					if ( $screen->id === $candidate || $screen->base === $candidate ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Determine whether a callback is defined within the Code Snippets plugin directory.
	 *
	 * Ownership is resolved from the callback's defining file. Reflection failures are treated as
	 * Code Snippets-owned so unknown callbacks are never removed.
	 *
	 * @param callable|string|array $callback Hook callback as stored in the filter registry.
	 *
	 * @return bool
	 */
	private function is_code_snippets_callback( $callback ): bool {
		try {
			if ( is_array( $callback ) ) {
				$file = ( new ReflectionMethod( $callback[0], $callback[1] ) )->getFileName();
			} elseif ( is_string( $callback ) && false !== strpos( $callback, '::' ) ) {
				[ $class, $method ] = explode( '::', $callback, 2 );
				$file = ( new ReflectionMethod( $class, $method ) )->getFileName();
			} else {
				$file = ( new ReflectionFunction( $callback ) )->getFileName();
			}
		} catch ( Throwable $error ) {
			return true;
		}

		if ( ! $file ) {
			return true;
		}

		return $this->is_code_snippets_file( $file );
	}

	/**
	 * Determine whether a file is beneath the Code Snippets plugin directory.
	 *
	 * @param string $file File path to inspect.
	 *
	 * @return bool
	 */
	private function is_code_snippets_file( string $file ): bool {
		$plugin_directory = trailingslashit( wp_normalize_path( dirname( PLUGIN_FILE ) ) );
		return 0 === strpos( wp_normalize_path( $file ), $plugin_directory );
	}
}
