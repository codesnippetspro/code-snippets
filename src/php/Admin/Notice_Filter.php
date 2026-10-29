<?php

namespace Code_Snippets\Admin;

use WP_Screen;
use function Code_Snippets\code_snippets;

/**
 * Hides admin notices that do not originate from Code Snippets while on a Code Snippets admin screen,
 * preventing foreign notices from disrupting the plugin's navigation and sub-tab layout.
 *
 * @package Code_Snippets
 */
class Notice_Filter {

	/**
	 * Instance of plugin admin class.
	 *
	 * @var Bootstrap_Admin
	 */
	private Bootstrap_Admin $admin;

	/**
	 * Class constructor.
	 *
	 * @param Bootstrap_Admin $admin Instance of plugin admin class.
	 */
	public function __construct( Bootstrap_Admin $admin ) {
		$this->admin = $admin;
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

		add_action( 'admin_head', [ $this, 'print_fallback_styles' ] );
	}

	/**
	 * Print inline styles that hide foreign notices in the notice region.
	 *
	 * Printed only on the plugin's own screens, so notices are matched at any depth:
	 * each screen renders into its own container, and other plugins inject relative
	 * to whichever wrapper they find.
	 *
	 * @return void
	 */
	public function print_fallback_styles() {
		?>
		<style>
			#wpbody-content :is(.notice, .update-nag, .updated, .error):not(.code-snippets-notice):not(.code-snippets-promotion):not(.settings-error) {
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
		foreach ( $this->admin->get_menus() as $menu ) {
			foreach ( $menu->get_hooknames() as $hookname ) {
				foreach ( [ $hookname, "$hookname-network" ] as $candidate ) {
					if ( $screen->id === $candidate || $screen->base === $candidate ) {
						return true;
					}
				}
			}
		}

		return false;
	}
}
