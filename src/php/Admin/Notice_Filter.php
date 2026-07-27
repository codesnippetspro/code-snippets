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

		add_action( 'admin_head', [ $this, 'print_fallback_styles' ] );
	}

	/**
	 * Print inline styles that hide foreign notices in the notice region.
	 *
	 * @return void
	 */
	public function print_fallback_styles() {
		?>
		<style>
			#wpbody-content > .notice:not(.code-snippets-notice):not(.code-snippets-promotion),
			#wpbody-content > .update-nag,
			#wpbody-content > .updated:not(.code-snippets-notice),
			#wpbody-content > .error:not(.code-snippets-notice),
			#manage-snippets-container > .notice:not(.code-snippets-notice):not(.code-snippets-promotion),
			#manage-snippets-container > .update-nag,
			#manage-snippets-container > .updated:not(.code-snippets-notice),
			#manage-snippets-container > .error:not(.code-snippets-notice),
			.code-snippets-settings > .notice:not(.code-snippets-notice):not(.code-snippets-promotion):not(.settings-error),
			.code-snippets-settings > .update-nag:not(.code-snippets-notice):not(.settings-error),
			.code-snippets-settings > .updated:not(.code-snippets-notice):not(.settings-error),
			.code-snippets-settings > .error:not(.code-snippets-notice):not(.settings-error) {
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
}
