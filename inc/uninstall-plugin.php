<div class="wrap">
	<div id="icon-snippets" class="icon32"><br /></div><h2>Uninstall Code Snippets</h2>
	<?php if ( strlen($msg) ) : ?>
		<div id="message" class="updated fade"><p><?php echo $msg; ?></p></div>
	<?php endif; ?>
	<p>Checking this box will remove all snippets and the table from the database when the plugin is deactivated.</p>
	<p>Only use if permanently uninstalling the Code Snippets plugin.</p>
	<p>You can come back here before deactivating the plugin and change your choice.</p>
	<form action="" method="post">
		<?php $check_uninstall =  ( get_option( 'cs_complete_uninstall',0 ) == 1 ) ? 'checked' : 'id="unin"'; ?>
		<input type="checkbox" name="ch_unin" <?php echo $check_uninstall; ?> style="margin-right: 5px" />
		<input tabindex="15" type="submit" name="uninstall" class="button-primary" value="Submit"/>
	</form>
	<script type="text/javascript">
	//<![CDATA[
	var cb = jQuery('#unin');
	if(cb.length) {
		cb.click(function() {
			if(confirm('Are you sure? \nYou will permanently lose your snippets when the plugin is deactivated.')) {
				cb.unbind('click');
				return true;
			}
			else {
				return false;
			}
        });
    }
	//]]>
	</script>
</div>