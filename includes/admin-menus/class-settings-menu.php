<?php

/**
 * This class handles the settings admin menu
 * @since 2.4.0
 * @package Code_Snippets
 */
class Code_Snippets_Settings_Menu extends Code_Snippets_Admin_Menu {

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct( 'settings',
			__( 'Settings', 'code-snippets' ),
			__( 'Snippets Settings', 'code-snippets' )
		);
	}

	/**
	 * Shortcircuit help tabs function
	 */
	function load_help_tabs() {}

	/**
	 * Render the admin screen
	 */
	function render() {
		?>
		<div class="wrap">
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
