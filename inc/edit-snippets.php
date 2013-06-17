<?php
	$edit = isset( $_GET['action'] ) && $_GET['action'] == 'edit';
	if( $edit )
		$id = intval( $_GET['id'] );	
?>
<div class="wrap">
	<div id="icon-snippets" class="icon32"><br /></div><h2><?php
	if( $edit ) :
	?>Edit Snippet<a href="<?php echo $this->edit_snippets_url; ?>" class="add-new-h2">Add New</a></h2><?php
	else:
	?>Add New Snippet</h2>
	<?php endif; ?>
	<?php if ( strlen( $msg ) ) : ?>
		<div id="message" class="updated fade"><p><?php echo $msg; ?></p></div>
	<?php else: ?>
		<br />
	<?php endif; ?>
	<form method="post" action="">
		<?php if( $edit ) : ?>
		<?php $record = $wpdb->get_row( "SELECT * FROM `$this->table_name` WHERE `id` = '$id';" ); ?>
		<input type="hidden" name="edit_id" value="<?php echo $id;?>" />
		<?php else: ?>
		<?php
			// define a empty object (or one with default values)
			$record					= new stdClass();
			$record->name			= '';
			$record->description	= '';
			$record->code			= '';
		?>
		<?php endif; ?>
            
		<div id="titlediv">
			<div id="titlewrap">
				<label for="title" style="display:none">Name (short title)</label>
				<input id="title" type="text" autocomplete="off" size="30" name="snippet_name" value="<?php echo stripslashes( $record->name ); ?>" placeholder="Name (short title)">
			</div>
		</div>

		<label for="snippet_code"><h3 style="display:inline">Code</h3>
		<span style="float:right">Enter or paste the snippet code without the <code>&lt;?php</code> and <code>?&gt;</code> tags.</span></label><br />
		<textarea id="snippet_code" name="snippet_code" rows="20" spellcheck="false" style="font-family:monospace;width:100%"><?php echo htmlspecialchars_decode( stripslashes( $record->code ) );?></textarea>
		<br style="margin: 20px;" />
		<div id="desclabel">
			<label for="description" style="text-align:center; margin: 10px auto"><h3 style="display:inline">Description</h3> (Optional)</label><br />
		</div>		
			<?php wp_editor( htmlspecialchars_decode( stripslashes( $record->description ) ), 'description', array( 'textarea_name' => 'snippet_description', 'textarea_rows' => 10 ) ); ?>
		<p class="submit">
			<input tabindex="15" type="submit" name="save_snippet" class="button-primary" value="Save" />
			<a href="<?php echo $this->manage_snippets_url; ?>" class="button">Cancel</a>
		</p>
	</form>
</div>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function() {

		jQuery('textarea').tabby();

	});
//]]>
</script>