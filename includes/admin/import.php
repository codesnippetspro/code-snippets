<?php

if ( isset( $_REQUEST['imported'] ) && intval( $_REQUEST['imported'] ) != 0 ) {

	echo '<div id="message" class="updated fade"><p>';

	printf(
		_n(
			'Imported <strong>%d</strong> snippet.',
			'Imported <strong>%d</strong> snippets.',
			$_REQUEST['imported'],
			'code-snippets'
		),
		$_REQUEST['imported']
	);

	echo '</p></div>';
}

?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('Import Snippets', 'code-snippets'); ?></h2>

	<div class="narrow">

		<p><?php _e('Howdy! Upload your Code Snippets export file and we&#8217;ll import the snippets to this site.', 'code-snippets' ); ?></p>

		<p><?php printf( __('You will need to go to the <a href="%s">Manage Snippets</a> page to activate the imported snippets.', 'code-snippets'), $this->admin_manage_url ); ?></p>

		<p><?php _e('Choose a Code Snippets (.xml) file to upload, then click Upload file and import.', 'code-snippets'); ?></p>
		<form enctype="multipart/form-data" id="import-upload-form" method="post" action="" name="code_snippets_import">
			<p>
				<label for="upload"><?php _e('Choose a file from your computer:', 'code-snippets' ); ?></label> <?php _e('(Maximum size: 8MB)', 'code-snippets'); ?>
				<input type="file" id="upload" name="code_snippets_import_file" size="25" accept="text/xml" />
				<input type="hidden" name="action" value="save" />
				<input type="hidden" name="max_file_size" value="8388608" />
			</p>

			<?php do_action( 'code_snippets_admin_import_form' ); ?>

			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button" value="<?php _e('Upload file and import', 'code-snippets'); ?>" />
			</p>
		</form>
	</div>
</div>