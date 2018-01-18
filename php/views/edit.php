<?php

/**
 * HTML code for the Add New/Edit Snippet page
 *
 * @package Code_Snippets
 * @subpackage Views
 */

/* Bail if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$table = code_snippets()->db->get_table_name();
$edit_id = code_snippets()->get_menu_slug( 'edit' ) === $_REQUEST['page'] ? absint( $_REQUEST['id'] ) : 0;
$snippet = get_snippet( $edit_id );

?>
<div class="wrap">
	<h1><?php
	if ( $edit_id ) {
		esc_html_e( 'Edit Snippet', 'code-snippets' );
		printf( ' <a href="%1$s" class="page-title-action add-new-h2">%2$s</a>',
			code_snippets()->get_menu_url( 'add' ),
			esc_html_x( 'Add New', 'snippet', 'code-snippets' )
		);
	} else {
		esc_html_e( 'Add New Snippet', 'code-snippets' );
	}
	?></h1>

	<form method="post" id="snippet-form" action="" style="margin-top: 10px;">
		<?php
		/* Output the hidden fields */

		if ( 0 !== $snippet->id ) {
			printf( '<input type="hidden" name="snippet_id" value="%d" />', $snippet->id );
		}

		printf( '<input type="hidden" name="snippet_active" value="%d" />', $snippet->active );

		?>
		<div id="titlediv">
			<div id="titlewrap">
				<label for="title" style="display: none;"><?php _e( 'Name', 'code-snippets' ); ?></label>
				<input id="title" type="text" autocomplete="off" name="snippet_name" value="<?php echo esc_attr( $snippet->name ); ?>" placeholder="<?php _e( 'Enter title here', 'code-snippets' ); ?>" />
			</div>
		</div>

		<h2><label for="snippet_code">
			<?php _e( 'Code', 'code-snippets' ); ?>
		</label></h2>

		<textarea id="snippet_code" name="snippet_code" rows="200" spellcheck="false" style="font-family: monospace; width: 100%;"><?php
			echo esc_textarea( $snippet->code );
			?></textarea>

		<?php
		/* Allow plugins to add fields and content to this page */
		do_action( 'code_snippets/admin/single', $snippet );

		/* Add a nonce for security */
		wp_nonce_field( 'save_snippet' );

		?>

		<p class="submit">
			<?php

			/* Make the 'Save and Activate' button the default if the setting is enabled */

			if ( 'single-use' === $snippet->scope ) {

				submit_button( null, 'primary', 'save_snippet', false );

				submit_button( __( 'Save Changes and Execute Once', 'code-snippets' ), 'secondary', 'save_snippet_execute', false );

			} elseif ( $snippet->shared_network && is_network_admin() ) {

				submit_button( null, 'primary', 'save_snippet', false );

			} elseif ( ! $snippet->active && code_snippets_get_setting( 'general', 'activate_by_default' ) ) {

				submit_button(
					__( 'Save Changes and Activate', 'code-snippets' ),
					'primary', 'save_snippet_activate', false
				);

				submit_button( null, 'secondary', 'save_snippet', false );

			} else {

				/* Save Snippet button */
				submit_button( null, 'primary', 'save_snippet', false );

				/* Save Snippet and Activate/Deactivate button */
				if ( ! $snippet->active ) {
					submit_button(
						__( 'Save Changes and Activate', 'code-snippets' ),
						'secondary', 'save_snippet_activate', false
					);

				} else {
					submit_button(
						__( 'Save Changes and Deactivate', 'code-snippets' ),
						'secondary', 'save_snippet_deactivate', false
					);
				}
			}

			if ( 0 !== $snippet->id ) {

				/* Download button */

				if ( apply_filters( 'code_snippets/enable_downloads', true ) ) {
					submit_button( __( 'Download', 'code-snippets' ), 'secondary', 'download_snippet', false );
				}

				/* Export button */

				submit_button( __( 'Export', 'code-snippets' ), 'secondary', 'export_snippet', false );

				/* Delete button */

				$confirm_delete_js = esc_js(
					sprintf(
						'return confirm("%s");',
						__( 'You are about to permanently delete this snippet.', 'code-snippets' ) . "\n" .
						__( "'Cancel' to stop, 'OK' to delete.", 'code-snippets' )
					)
				);

				submit_button(
					__( 'Delete', 'code-snippets' ),
					'secondary', 'delete_snippet', false,
					sprintf( 'onclick="%s"', $confirm_delete_js )
				);
			}

			?>
		</p>
	</form>
</div>

<script>
/* Loads CodeMirror on the snippet editor */
(function() {

	var atts = [];
	atts = <?php
		$atts = array( 'mode' => 'text/x-php' );
		echo code_snippets_get_editor_atts( $atts, true );
	?>;
	atts['viewportMargin'] = Infinity;

	atts['extraKeys'] = {
		'Ctrl-Enter': function (cm) {
			document.getElementById('snippet-form').submit();
		}
	};

	CodeMirror.fromTextArea(document.getElementById('snippet_code'), atts);
})();
</script>
