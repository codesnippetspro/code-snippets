<?php

/**
 * This file handles the settings admin menu
 * @package Code_Snippets
 */

class Code_Snippets_Settings_Menu extends Code_Snippets_Admin_Menu {

	public function __construct() {

		parent::__construct( 'add',
			__( 'Settings', 'code-snippets' ),
			__( 'Snippets Settings', 'code-snippets' )
		);
	}

	function load_help_tabs() {}

	/**
	 * Displays the settings menu
	 *
	 * @since 2.0
	 */
	function render() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php esc_html_e( 'Settings', 'code-snippets' ); ?></h2>

			<?php settings_errors( 'code-snippets-settings-notices' ); ?>

			<form action="options.php" method="post">
				<?php settings_fields( 'code-snippets' ); ?>
				<table class="form-table">
					<?php do_settings_sections( 'code-snippets' ); ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
