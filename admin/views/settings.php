<?php

/**
 * HTML code for the Code Snippets Settings page
 *
 * @package    Code_Snippets
 * @subpackage Admin_Views
 */

/* Bail if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

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
