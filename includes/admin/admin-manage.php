<?php
if( !class_exists( 'Code_Snippets' ) ) exit;
global $wpdb;
?>
<div class="wrap">
	<div id="icon-snippets" class="icon32"><br /></div><h2>Snippets <a href="<?php echo $this->admin_edit_url; ?>" class="add-new-h2">Add New</a></h2>
	<?php if ( isset( $msg ) ) : ?>
		<div id="message" class="updated fade"><p><?php echo $msg; ?></p></div>
	<?php endif; ?>
	<?php $snippets = $wpdb->get_results( 'select * from ' . $this->table ); ?>
	<form action="" method="post">
		<div class="tablenav top">
			<div class="alignleft actions">
				<select name='action' class="bulk-actions">
					<option value='-1' selected='selected'>Bulk Actions</option>
					<option value='activate'>Activate</option>
					<option value='deactivate'>Deactivate</option>
					<option value='export'>Export</option>
					<option value='delete'>Delete</option>
				</select>
				<input type="submit" id="doaction" class="button-secondary" value="Apply"  />
			</div>
		</div>
		<table class="widefat manage-snippets" style="margin-top: .5em">
			<thead>
				<tr>
					<th scope="col" class="check-column"><input type="checkbox" name="toggle" id="toggle" /></th>
					<th scope="col" id="name" style="min-width:180px">Name</th>
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
					<th scope="row" class="check-column"><input class="snippets" type="checkbox" name="ids[]" value="<?php echo $snippet->id; ?>" /></th>
					<td class="snippet-title"><strong><?php echo stripslashes( $snippet->name );?></strong>
					<div class="row-actions-visible">
						<?php if( $snippet->active == 0 ) : ?>
							<span class='activate'><a href="<?php echo $this->admin_manage_url . '&action=activate&id=' . $snippet->id; ?>" title="Activate this snippet" class="edit">Activate</a> | </span>
						<?php else : ?>
							<span class='deactivate'><a href="<?php echo $this->admin_manage_url . '&action=deactivate&id=' . $snippet->id; ?>" title="Deactivate this snippet" class="edit">Deactivate</a> | </span>
						<?php endif; ?>
						<span class='edit'><a href="<?php echo $this->admin_edit_url . '&action=edit&id=' . $snippet->id; ?>" title="Edit this snippet" class="edit">Edit</a> | </span>
						<span class='edit'><a href="<?php echo $this->admin_manage_url . '&action=export&id=' . $snippet->id; ?>" title="Export this snippet" class="edit">Export</a> | </span>
						<span class='delete'><a href="<?php echo $this->admin_manage_url . '&action=delete&id=' . $snippet->id; ?>" title="Delete this snippet" class="delete" onclick="return confirm('Are you sure? This action is non-reversable');">Delete</a></span>
					</div>
				</td>
             <td><?php echo stripslashes( html_entity_decode( $snippet->description ) ); ?></td>
         </tr>
         <?php endforeach; ?>
         <?php else: ?>
			<tr id='no-groups'>
				<th scope="row" class="check-column">&nbsp;</th>
				<td colspan="4">You do not appear to have any snippets available at this time. <a href="<?php echo $this->admin_edit_url; ?>">Add New&rarr;</a></td>
			</tr>
		<?php endif;?>
		<tfoot>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" name="toggle" id="toggle" /></th>
				<th scope="col" id="name" style="min-width:160px">Name</th>
				<th scope="col" id="description">Description</th>
			</tr>
		</tfoot>
		</table>
		<div class="tablenav bottom">
			<div class="alignleft actions">
				<select name='action2' class="bulk-actions">
					<option value='-1' selected='selected'>Bulk Actions</option>
					<option value='activate'>Activate</option>
					<option value='deactivate'>Dectivate</option>
					<option value='export'>Export</option>
					<option value='delete'>Delete</option>
				</select>
				<input type="submit" id="doaction2" class="button-secondary action" value="Apply"  />
			</div>
		</div>
	</form>
</div>