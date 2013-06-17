<div class="wrap">
	<div id="icon-snippets" class="icon32"><br /></div><h2>Import Snippets</h2>
	<?php if ( isset( $msg ) ) : ?>
		<div id="message" class="updated fade"><p><?php echo $msg; ?></p></div>
	<?php endif; ?>
	<div class="narrow">
		<p>Howdy! Upload your Code Snippets export file and we&#8217;ll import the snippets to this site.</p>
		<p>You will need to go to the <a href="<?php echo $this->admin_manage_url; ?>">Manage Snippets</a> page to activate the imported snippets.</p>
		<p>Choose a Code Snippets (.xml) file to upload, then click Upload file and import.</p>
		<form enctype="multipart/form-data" id="import-upload-form" method="post" action="" name="cs_import">
			<p>
				<label for="upload">Choose a file from your computer:</label> (Maximum size: 8MB)
				<input type="file" id="upload" name="cs_import_file" size="25" accept="text/xml" />
				<input type="hidden" name="action" value="save" />
				<input type="hidden" name="max_file_size" value="8388608" />
			</p>
			<p class="submit"><input type="submit" name="submit" id="submit" class="button" value="Upload file and import"  /></p></form>
	</div>
</div>