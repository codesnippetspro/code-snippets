<?php

/**
 * HTML code for the Import Snippets page
 *
 * @package Code_Snippets
 * @subpackage Views
 */

/* Bail if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

?>
<div class="wrap">
	<h1><?php _e( 'Import Snippets', 'code-snippets' ); ?></h1>

	<div class="narrow">

		<p><?php _e( 'Upload one or more Code Snippets export files and the snippets will be imported.', 'code-snippets' ); ?></p>

		<p><?php printf( __( 'Afterwards, you will need to go to the <a href="%s">All Snippets</a> page to activate the imported snippets.', 'code-snippets' ), code_snippets()->get_menu_url( 'manage' ) ); ?></p>

		<p><?php _e( 'Choose one or more Code Snippets (.xml or .json) files to upload, then click "Upload files and import".', 'code-snippets' ); ?></p>

		<form enctype="multipart/form-data" method="post" action="" id="import-upload-form" name="code_snippets_import">
			<p>
				<input type="hidden" name="action" value="save">
				<input type="hidden" name="max_file_size" value="8388608">

				<label for="upload"><?php _e( 'Choose a file from your computer:', 'code-snippets' ); ?></label>
				<?php _e( '(Maximum size: 8MB)', 'code-snippets' ); ?>
				<input type="file" id="upload" name="code_snippets_import_files[]" size="25" accept="text/xml,application/json" multiple="multiple">
			</p>

			<?php
				do_action( 'code_snippets/admin/import_form' );
				submit_button( __( 'Upload files and import', 'code-snippets' ) );
			?>
		</form>
	</div>
</div>
