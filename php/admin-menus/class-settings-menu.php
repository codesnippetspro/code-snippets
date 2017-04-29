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
			_x( 'Settings', 'menu label', 'code-snippets' ),
			__( 'Snippets Settings', 'code-snippets' )
		);
	}

	/**
	 * Executed when the admin page is loaded
	 */
	function load() {
		parent::load();

		if ( is_network_admin() ) {
			wp_redirect( code_snippets()->get_menu_url( 'settings', 'admin' ) );
			exit;
		}
	}

	/**
	 * Render the admin screen
	 */
	function render() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Settings', 'code-snippets' ); ?></h2>

			<?php settings_errors( 'code-snippets-settings-notices' ); ?>

			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
				<?php

				settings_fields( 'code-snippets' );
				do_settings_sections( 'code-snippets' );
				submit_button();

				?>
			</form>
		</div>
		<?php
	}
}
