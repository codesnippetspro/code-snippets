<?php global $wpdb;	?>
<div class="wrap">
	<div id="icon-snippets" class="icon32"><br /></div><h2>Snippets <a href="<?php echo $this->edit_snippets_url; ?>" class="add-new-h2">Add New</a></h2>
	<?php if ( strlen( $msg ) ) : ?>
		<div id="message" class="updated fade"><p><?php echo $msg; ?></p></div>
	<?php endif; ?>
	<?php $snippets = $wpdb->get_results( 'select * from ' . $this->table_name ); ?>
	<form action="" method="post">
		<div class="tablenav top">
			<div class="alignleft actions">
				<select name='action' class="bulk-actions">
					<option value='-1' selected='selected'>Bulk Actions</option>
					<option value='activate'>Activate</option>
					<option value='deactivate'>Deactivate</option>
					<option value='delete'>Delete</option>
				</select>
				<input type="submit" id="doaction" class="button-secondary" value="Apply"  />
			</div>
		</div>
		<table class="widefat manage-snippets" style="margin-top: .5em">
			<thead>
				<tr>
					<th scope="col" class="check-column"><input type="checkbox" name="toggle" id="toggle" /></th>
					<th scope="col" id="name">Name</th>
					<th scope="col" id="description">Description</th>
				</tr>
			</thead>
			<?php if( count( $snippets ) ): ?>
			<?php foreach( $snippets as $snippet ) : ?>
			<tr class='<?php
				if($snippet->active == false) 
					echo 'inactive'; 
				else 
					echo 'active';
				?>'>
					<th scope="row" class="check-column"><input class="snippets" type="checkbox" name="snippets[]" value="<?php echo $snippet->id; ?>" /></th>
					<td class="snippet-title"><strong><?php echo stripslashes($snippet->name);?></strong>
					<div class="row-actions-visible">
						<?php if( $snippet->active == 0 ) : ?>
							<span class='activate'><a href="<?php echo $this->manage_snippets_url . '&action=activate&id=' . $snippet->id; ?>" title="Activate this plugin" class="edit">Activate</a> | </span>
						<?php else : ?>
							<span class='deactivate'><a href="<?php echo $this->manage_snippets_url . '&action=deactivate&id=' . $snippet->id; ?>" title="Deactivate this plugin" class="edit">Deactivate</a> | </span>
						<?php endif; ?>
						<span class='edit'><a href="<?php echo $this->edit_snippets_url . '&action=edit&id=' . $snippet->id; ?>" title="Edit this Snippet" class="edit">Edit</a> | </span>
						<span class='delete'><a href="<?php echo $this->manage_snippets_url . '&action=delete&id=' . $snippet->id; ?>" title="Delete this plugin" class="delete" onclick="return confirm('Are you sure? This action is non-reversable');">Delete</a></span>
					</div>
				</td>
             <td><?php echo stripslashes( html_entity_decode( $snippet->description ) ); ?></td>
         </tr>
         <?php endforeach; ?>
         <?php else: ?>
			<tr id='no-groups'>
				<th scope="row" class="check-column">&nbsp;</th>
				<td colspan="4">You do not appear to have any snippets available at this time.</td>
			</tr>
		<?php endif;?>
		</table>
		<div class="tablenav bottom">
			<div class="alignleft actions">
				<select name='action2' class="bulk-actions">
					<option value='-1' selected='selected'>Bulk Actions</option>
					<option value='activate'>Activate</option>
					<option value='deactivate'>Deactivate</option>
					<option value='delete'>Delete</option>
				</select>
				<input type="submit" id="doaction2" class="button-secondary action" value="Apply"  />
			</div>
		</div>
	</form>
</div>