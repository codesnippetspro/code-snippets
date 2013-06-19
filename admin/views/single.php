<?php

/**
 * HTML code for the Add New/Edit Snippet page
 *
 * @package    Code_Snippets
 * @subpackage Admin_Views
 */

if ( ! class_exists( 'Code_Snippets' ) )
	exit;

global $code_snippets;

$table   = $code_snippets->get_table_name();
$screen  = get_current_screen();

$edit_id = ( isset( $_REQUEST['edit'] ) ? absint( $_REQUEST['edit'] ) : 0 );
$snippet = $code_snippets->get_snippet( $edit_id );

$code_snippets->admin->get_messages( 'single' );

?>

<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php
		if ( $edit_id ) {
			esc_html_e('Edit Snippet', 'code-snippets');

			if ( $code_snippets->user_can( 'install' ) )
				printf( ' <a href="%1$s" class="add-new-h2">%2$s</a>',
					$code_snippets->admin->single_url,
					esc_html_x('Add New', 'snippet', 'code-snippets')
				);
		} else {
			esc_html_e('Add New Snippet', 'code-snippets');
		}
	?></h2>

	<form method="post" action="" style="margin-top: 10px;">
		<?php

			/* Output the hidden fields */

			if ( 0 !== $snippet->id )
				printf ( '<input type="hidden" name="snippet_id" value="%d" />', $snippet->id );

			printf ( '<input type="hidden" name="snippet_active" value="%d" />', $snippet->active );
		?>
		<div id="titlediv">
			<div id="titlewrap">
				<label for="title" style="display: none;"><?php _e('Name (short title)', 'code-snippets'); ?></label>
				<input id="title" type="text" autocomplete="off" name="snippet_name" value="<?php echo esc_html( $snippet->name ); ?>" placeholder="<?php _e('Name (short title)', 'code-snippets'); ?>" />
			</div>
		</div>

		<label for="snippet_code">
			<h3><?php _e('Code', 'code-snippets'); ?></h3>
		</label>

		<textarea id="snippet_code" name="snippet_code" rows="20" spellcheck="false" style="font-family: monospace; width:100%;"><?php echo $snippet->code; ?></textarea>

		<?php do_action( 'code_snippets_admin_single', $snippet ); ?>

		<p class="submit">
			<?php
				submit_button( null, 'primary', 'save_snippet', false );

				if ( ! $snippet->active ) {
					echo '&nbsp;&nbsp;&nbsp;';
					submit_button( __( 'Save Changes &amp; Activate', 'code-snippets' ), 'secondary', 'save_snippet_activate', false );
				}
			?>
		</p>

	</form>
</div>
