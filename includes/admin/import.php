<?php if( isset( $_REQUEST['imported'] ) && intval( $_REQUEST['imported'] ) != 0 ) : ?>
	<div id="message" class="updated fade"><p><?php
		printf(
			_n(
				'Imported <strong>%s</strong> snippet.',
				'Imported <strong>%s</strong> snippets.',
				$_REQUEST['imported'],
				'code-snippets'
			),
			$_REQUEST['imported']
		);
	?></p></div>
<?php endif; ?>
<div class="wrap">
	<div id="icon-snippets" class="icon32"><br /></div>
	<h2><?php _e('Import Snippets', 'code-snippets'); ?></h2>
	<div class="narrow">
		<p><?php _e('Howdy! Upload your Code Snippets export file and we&#8217;ll import the snippets to this site.', 'code-snippets' ); ?></p>
		<p><?php printf( __('You will need to go to the <a href="%s">Manage Snippets</a> page to activate the imported snippets.', 'code-snippets'), $this->admin_manage_url ); ?></p>
		<p><?php _e('Choose a Code Snippets (.xml) file to upload, then click Upload file and import.', 'code-snippets'); ?></p>
		<form enctype="multipart/form-data" id="import-upload-form" method="post" action="" name="cs_import">
			<p>
				<label for="upload"><?php _e('Choose a file from your computer:', 'code-snippets' ); ?></label> <?php _e('(Maximum size: 8MB)', 'code-snippets'); ?>
				<input type="file" id="upload" name="cs_import_file" size="25" accept="text/xml" />
				<input type="hidden" name="action" value="save" />
				<input type="hidden" name="max_file_size" value="8388608" />
			</p>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button" value="<?php _e('Upload file and import', 'code-snippets'); ?>" />
			</p>
		</form>
	</div>
</div>