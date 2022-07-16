<?php
/**
 * HTML for the Add New/Edit Snippet page.
 *
 * @package    Code_Snippets
 * @subpackage Views
 *
 * @var Edit_Menu $this
 */

namespace Code_Snippets;

/* Bail if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$snippet = $this->snippet;
$classes = array();

if ( ! $snippet->id ) {
	$classes[] = 'new-snippet';
} elseif ( 'single-use' === $snippet->scope ) {
	$classes[] = 'single-use-snippet';
} elseif ( 'html' !== $snippet->type ) {
	$classes[] = ( $snippet->active ? '' : 'in' ) . 'active-snippet';
}

?>
<div class="wrap">
	<h1>
		<?php

		if ( $snippet->id ) {
			esc_html_e( 'Edit Snippet', 'code-snippets' );
			printf(
				' <a href="%1$s" class="page-title-action add-new-h2">%2$s</a>',
				esc_url( add_query_arg( 'type', $snippet->type, code_snippets()->get_menu_url( 'add' ) ) ),
				esc_html_x( 'Add New', 'snippet', 'code-snippets' )
			);
		} else {
			esc_html_e( 'Add New Snippet', 'code-snippets' );
		}

		if ( code_snippets()->is_compact_menu() ) {
			$this->page_title_actions( [ 'manage', 'import', 'settings' ] );
		}

		?>
	</h1>

	<?php $this->print_messages(); ?>

	<form method="post" id="snippet-form" action="" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	      data-snippet-type="<?php echo esc_attr( $snippet->type ); ?>">
		<?php
		/* Output the hidden fields */

		if ( 0 !== $snippet->id ) {
			printf( '<input type="hidden" name="snippet_id" value="%d">', esc_attr( $snippet->id ) );
		}

		printf( '<input type="hidden" name="snippet_active" value="%d">', esc_attr( $snippet->active ) );

		printf( '<input type="hidden" name="current_snippet_scope" value="%s">', esc_attr( $snippet->scope ) );

		do_action( 'code_snippets/admin/before_title_input', $snippet );
		?>

		<div id="titlediv">
			<div id="titlewrap">
				<label for="title" style="display: none;"><?php esc_html_e( 'Name', 'code-snippets' ); ?></label>
				<input id="title" type="text" autocomplete="off" name="snippet_name"
				       value="<?php echo esc_attr( $snippet->name ); ?>"
				       placeholder="<?php esc_attr_e( 'Enter title here', 'code-snippets' ); ?>">
			</div>
		</div>

		<?php do_action( 'code_snippets/admin/after_title_input', $snippet ); ?>

		<p class="submit-inline"><?php do_action( 'code_snippets/admin/code_editor_toolbar', $snippet ); ?></p>

		<h2>
			<label for="snippet_code">
				<?php
				esc_html_e( 'Code', 'code-snippets' );

				if ( $snippet->id ) {
					printf(
						' <span class="snippet-type-badge" data-type="%s">%s</span>',
						esc_attr( $snippet->type ),
						esc_html( $snippet->type )
					);
				}

				?>
			</label>
		</h2>

		<?php

		if ( ! $snippet->id ) {
			echo '<h2 class="nav-tab-wrapper" id="snippet-type-tabs">';

			foreach ( Plugin::get_types() as $type_name => $label ) {
				Admin::render_snippet_type_tab( $type_name, $label, $snippet->type );
			}

			echo '</h2>';
		}

		?>

		<div class="snippet-editor">
			<textarea id="snippet_code" name="snippet_code" rows="200" spellcheck="false"
			          style="font-family: monospace; width: 100%;"><?php echo esc_textarea( $snippet->code ); ?></textarea>

			<?php $this->render_view( 'partials/editor-shortcuts' ); ?>
		</div>

		<div class="below-editor">
			<?php
			$this->render_view( 'partials/edit-scopes' );
			do_action( 'code_snippets_below_editor', $snippet );
			?>
		</div>

		<?php

		/* Allow plugins to add fields and content to this page */
		do_action( 'code_snippets_edit_snippet', $snippet );

		/* Add a nonce for security */
		wp_nonce_field( 'save_snippet' );

		?>

		<p class="submit"><?php $this->render_submit_buttons( $snippet ); ?></p>
	</form>
</div>
