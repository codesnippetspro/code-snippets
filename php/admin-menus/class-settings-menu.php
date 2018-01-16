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

			if ( code_snippets_unified_settings() ) {
				$this->update_network_options();
			} else {
				wp_redirect( code_snippets()->get_menu_url( 'settings', 'admin' ) );
				exit;
			}
		}
	}

	/**
	 * Render the admin screen
	 */
	function render() {
		$update_url = is_network_admin() ? add_query_arg( 'update_site_option', true ) : admin_url( 'options.php' );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Settings', 'code-snippets' ); ?></h1>

			<?php settings_errors( 'code-snippets-settings-notices' ); ?>

			<form action="<?php echo esc_url( $update_url ); ?>" method="post">
				<?php

				settings_fields( 'code-snippets' );
				do_settings_sections( 'code-snippets' );
				submit_button();

				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Fill in for the Settings API in the Network Admin
	 */
	function update_network_options() {

		/* Ensure the settings have been saved */
		if ( ! isset( $_GET['update_site_option'], $_POST['code_snippets_settings'] ) || ! $_GET['update_site_option'] ) {
			return;
		}

		check_admin_referer( 'code-snippets-options' );

		/* Retrieve the saved options and save them to the database */
		$value = wp_unslash( $_POST['code_snippets_settings'] );
		update_site_option( 'code_snippets_settings', $value );

		/* Add an updated notice */
		if ( ! count( get_settings_errors() ) ) {
			add_settings_error( 'general', 'settings_updated', __( 'Settings saved.' ), 'updated' );
		}
		set_transient( 'settings_errors', get_settings_errors(), 30 );

		/* Redirect back to the settings menu */
		$goback = add_query_arg( 'settings-updated', 'true', remove_query_arg( 'update_site_option', wp_get_referer() ) );
		wp_redirect( $goback );
		exit;
	}
}
