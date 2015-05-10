<?php

/**
 * This file handles the settings admin menu
 * @package Code_Snippets
 */

/**
 * Register the setting sub-menu
 *
 * @since 2.0
 * @access private
 *
 * @uses add_submenu_page() To register a sub-menu
 */
function code_snippets_add_settings_menu() {

	add_submenu_page(
		code_snippets_get_menu_slug(),
		__( 'Snippets Settings', 'code-snippets' ),
		__( 'Settings', 'code-snippets' ),
		get_snippets_cap(),
		code_snippets_get_menu_slug( 'settings' ),
		'code_snippets_render_settings_menu'
	);
}

add_action( 'admin_menu', 'code_snippets_add_settings_menu' );

/**
 * Displays the settings menu
 *
 * @since 2.0
 */
function code_snippets_render_settings_menu() {
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
