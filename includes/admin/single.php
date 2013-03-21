<?php

/**
 * HTML code for the Add New/Edit Snippet page
 *
 * @package    Code Snippets
 * @subpackage Administration
 */

if ( ! class_exists( 'Code_Snippets' ) ) exit;

$table = $this->get_table_name();
$screen = get_current_screen();
$can_install = current_user_can( $screen->is_network ? 'install_network_snippets' : 'install_snippets' );

if ( isset( $_REQUEST['edit'] ) ) {
	$edit_id = intval( $_REQUEST['edit'] );
	$snippet = $this->get_snippet( $edit_id );
} else {
	$snippet = $this->get_snippet();
}

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
					esc_html_x('Add New', 'snippet', 'code-snippets')
				);
		} else {
			esc_html_e('Add New Snippet', 'code-snippets');
		}
	?></h2>

	<form method="post" action="" style="margin-top: 10px;">
		<?php if ( isset( $edit_id ) )
				printf ( '<input type="hidden" name="snippet_id" value="%d" />', $snippet->id );
		?>
		<div id="titlediv">
			<div id="titlewrap">
				<label for="title" style="display: none;"><?php _e('Name (short title)', 'code-snippets'); ?></label>
				<input id="title" type="text" autocomplete="off" name="snippet_name" value="<?php echo esc_html( $snippet->name ); ?>" placeholder="<?php _e('Name (short title)', 'code-snippets'); ?>" required="required" />
			</div>
		</div>

		<label for="snippet_code">
			<h3><?php _e('Code', 'code-snippets'); ?></h3>
		</label>

		<textarea id="snippet_code" name="snippet_code" rows="20" spellcheck="false" style="font-family: monospace; width:100%;"><?php echo $snippet->code; ?></textarea>

		<?php do_action( 'code_snippets_admin_single', $snippet ); ?>

		<?php submit_button( null, 'primary', 'save_snippet' ); ?>

	</form>
</div>
<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("snippet_code"), {
	lineNumbers: true,
	matchBrackets: true,
	lineWrapping: true,
	mode: "text/x-php",
	indentUnit: 4,
	indentWithTabs: true,
	enterMode: "keep",
	tabMode: "shift"
});
</script>