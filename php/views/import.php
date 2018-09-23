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

$max_size_bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
$upload_dir = wp_upload_dir();


?>
<div class="wrap">
	<h1><?php _e( 'Import Snippets', 'code-snippets' ); ?></h1>

	<div class="narrow">

		<p><?php _e( 'Upload one or more Code Snippets export files and the snippets will be imported.', 'code-snippets' ); ?></p>

		<p><?php
			printf(
				__( 'Afterwards, you will need to visit the <a href="%s">All Snippets</a> page to activate the imported snippets.', 'code-snippets' ),
				code_snippets()->get_menu_url( 'manage' )
			); ?></p>

		<?php if ( ! empty( $upload_dir['error'] ) ) : ?>

			<div class="error">
				<p><?php _e( 'Before you can upload your import file, you will need to fix the following error:' ); ?></p>
				<p>
					<strong><?php echo $upload_dir['error']; ?></strong>
				</p>
			</div>

		<?php else : ?>

		<form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form" name="code_snippets_import">

			<h2><?php _e( 'Duplicate Snippets', 'code-snippets' ); ?></h2>

			<p class="description">
				<?php esc_html_e( 'What should happen if an existing snippet is found with an identical name to an imported snippet?', 'code-snippets' ); ?>
			</p>

			<fieldset>
				<p>
					<label>
						<input type="radio" name="duplicate_action" value="ignore" checked="checked">
						<?php esc_html_e( 'Ignore any duplicate snippets: import all snippets from the file regardless and leave all existing snippets unchanged.', 'code-snippets' ); ?>
					</label>
				</p>

				<p>
					<label>
						<input type="radio" name="duplicate_action" value="replace">
						<?php esc_html_e( 'Replace any existing snippets with a newly imported snippet of the same name.', 'code-snippets' ); ?>
					</label>
				</p>

				<p>
					<label>
						<input type="radio" name="duplicate_action" value="skip">
						<?php esc_html_e( 'Do not import any duplicate snippets; leave all existing snippets unchanged.', 'code-snippets' ); ?>
					</label>
				</p>
			</fieldset>

			<h2><?php _e( 'Upload Files', 'code-snippets' ); ?></h2>

			<p class="description">
				<?php _e( 'Choose one or more Code Snippets (.xml or .json) files to upload, then click "Upload files and import".', 'code-snippets' ); ?>
			</p>

			<fieldset>
				<p>
					<label for="upload"><?php esc_html_e( 'Choose files from your computer:', 'code-snippets' ); ?></label>
					<?php printf( esc_html__( '(Maximum size: %s)', 'code-snippets' ), size_format( $max_size_bytes ) ); ?>
					<input type="file" id="upload" name="code_snippets_import_files[]" size="25" accept="application/json,.json,text/xml" multiple="multiple">
					<input type="hidden" name="action" value="save">
					<input type="hidden" name="max_file_size" value="<?php echo esc_attr( $max_size_bytes ); ?>">
				</p>
			</fieldset>

			<?php
			do_action( 'code_snippets/admin/import_form' );
			submit_button( __( 'Upload files and import', 'code-snippets' ) );
			?>
		</form>
		<?php endif; ?>
	</div>
</div>
