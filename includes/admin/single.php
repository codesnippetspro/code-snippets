<?php
if ( ! class_exists( 'Code_Snippets' ) ) exit;
global $wpdb;

$table = $this->get_table_name();
$screen = get_current_screen();
$can_edit = current_user_can( $screen->is_network ? 'edit_network_snippets' : 'edit_snippets' );
$can_install = current_user_can( $screen->is_network ? 'install_network_snippets' : 'install_snippets' );

if ( isset( $_REQUEST['edit'] ) && ! $can_edit )
	wp_die( __('Sorry, you&#8217;re not allowed to edit snippets', 'code-snippets') );

if ( isset( $_REQUEST['edit'] ) )
	$edit_id = intval( $_REQUEST['edit'] );
?>

<?php if ( isset( $_REQUEST['invalid'] ) && $_REQUEST['invalid'] ) : ?>
	<div id="message" class="error fade"><p><?php _e('Please provide a name for the snippet and its code.', 'code-snippets'); ?></p></div>
<?php elseif ( isset( $_REQUEST['updated'] ) && $_REQUEST['updated'] ) : ?>
	<div id="message" class="updated fade"><p><?php _e('Snippet <strong>updated</strong>.', 'code-snippets'); ?></p></div>
<?php elseif ( isset( $_REQUEST['added'] ) && $_REQUEST['added'] ) : ?>
	<div id="message" class="updated fade"><p><?php _e('Snippet <strong>added</strong>.', 'code-snippets'); ?></p></div>
<?php endif; ?>

<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php
		if ( isset( $edit_id ) ) {
			esc_html_e('Edit Snippet', 'code-snippets');

			if ( $can_install )
				printf( ' <a href="%1$s" class="add-new-h2">%2$s</a>',
					$this->admin_single_url,
					esc_html('Add New', 'code-snippets')
				);
		} else {
			_e('Add New Snippet', 'code-snippets');
		}
	?></h2>

	<form method="post" action="" style="margin-top: 10px;">
		<?php
			if ( isset( $edit_id ) ) {
				$snippet = $wpdb->get_row( "SELECT * FROM $table WHERE id = $edit_id" );
				printf ( '<input type="hidden" name="snippet_id" value="%d" />', $snippet->id );
			} else {
				// define a empty object (or one with default values)
				$snippet = new stdClass();
				$snippet->name = '';
				$snippet->description = '';
				$snippet->code = '';
			}
		?>
		<div id="titlediv">
			<div id="titlewrap">
				<label for="title" style="display: none;"><?php esc_html_e('Name (short title)', 'code-snippets'); ?></label>
				<input id="title" type="text" autocomplete="off" size="30"  maxlength="64" name="snippet_name" value="<?php echo stripslashes( $snippet->name ); ?>" placeholder="<?php _e('Name (short title)', 'code-snippets'); ?>" required="required">
			</div>
		</div>

		<label for="snippet_code">
			<h3 style="display: inline;"><?php esc_html_e('Code', 'code-snippets'); ?></h3>
			<span style="float: right;"><?php _e('Enter or paste the snippet code without the <code>&lt;?php</code> and <code>?&gt;</code> tags.', 'code-snippets'); ?></span>
		</label>

		<textarea id="snippet_code" name="snippet_code" rows="20" spellcheck="false" style="font-family: monospace; width:100%;"><?php echo stripslashes( $snippet->code ); ?></textarea>

		<label for="description">
			<h3>
				<?php esc_html_e('Description', 'code-snippets'); ?>
				<span style="font-weight: normal; font-size: normal;"><?php _e('(Optional)', 'code-snippets'); ?></span>
			</h3>
		</label>

		<?php
		wp_editor(
			htmlspecialchars_decode( stripslashes( $snippet->description ) ),
			'description',
			array(
				'textarea_name' => 'snippet_description',
				'textarea_rows' => 10,
				'media_buttons' => false,
			)
		);
		?>
		<p class="submit">
			<input type="submit" name="save_snippet" class="button-primary" value="<?php _e('Save', 'code-snippets'); ?>" />
			<a href="<?php echo $this->admin_manage_url; ?>" class="button"><?php _e('Cancel', 'code-snippets'); ?></a>
		</p>
	</form>
</div>
<script type="text/javascript">
	var editor = CodeMirror.fromTextArea(document.getElementById("snippet_code"), {
		lineNumbers: true,
		matchBrackets: true,
		lineWrapping: true,
		mode: "application/x-httpd-php-open",
		indentUnit: 4,
		indentWithTabs: true,
		enterMode: "keep",
		tabMode: "shift"
	});
</script>