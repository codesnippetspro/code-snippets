<?php

namespace Code_Snippets;

/**
 * HTML code for the Add New/Edit Snippet page
 *
 * @package    Code_Snippets
 * @subpackage Views
 *
 * @var Edit_Menu $this
 */

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
	<h1><?php

		if ( $snippet->id ) {
			esc_html_e( 'Edit Snippet', 'code-snippets' );
			printf( ' <a href="%s" class="page-title-action add-new-h2">%s</a>',
				code_snippets()->get_menu_url( 'add' ),
				esc_html_x( 'Add New', 'snippet', 'code-snippets' )
			);
		} else {
			esc_html_e( 'Add New Snippet', 'code-snippets' );
		}

		if ( code_snippets()->admin->is_compact_menu() ) {
			$this->page_title_actions( [ 'manage', 'import', 'settings' ] );
		}

		?></h1>

	<form method="post" id="snippet-form" action="" class="<?php echo implode( ' ', $classes ); ?>"
	      data-snippet-type="<?php echo esc_attr( $snippet->type ); ?>">
		<?php
		/* Output the hidden fields */

		if ( 0 !== $snippet->id ) {
			printf( '<input type="hidden" name="snippet_id" value="%d" />', $snippet->id );
		}

		printf( '<input type="hidden" name="snippet_active" value="%d" />', $snippet->active );

		printf( '<input type="hidden" name="current_snippet_scope" value="%s" />', $snippet->scope );

		?>
		<div id="titlediv">
			<div id="titlewrap">
				<label for="title" style="display: none;"><?php _e( 'Name', 'code-snippets' ); ?></label>
				<input id="title" type="text" autocomplete="off" name="snippet_name" value="<?php echo esc_attr( $snippet->name ); ?>"
				       placeholder="<?php _e( 'Enter title here', 'code-snippets' ); ?>" />
			</div>
		</div>

		<?php

		if ( apply_filters( 'code_snippets/extra_save_buttons', true ) ) {
			$this->render_view( 'partials/edit-submit-extra' );
		}

		?>

		<h2>
			<label for="snippet_code">
				<?php _e( 'Code', 'code-snippets' ); ?>
			</label>
		</h2>

		<?php if ( ! $snippet->id ) { ?>
			<h2 class="nav-tab-wrapper" id="snippet-type-tabs">
				<?php

				$types = array(
					'php'  => __( 'Functions', 'code-snippets' ),
					'html' => __( 'Content', 'code-snippets' ),
					'css'  => __( 'Styles', 'code-snippets' ),
					'js'   => __( 'Scripts', 'code-snippets' ),
				);

				foreach ( $types as $type => $label ) {
					if ( $snippet->type == $type ) {
						printf( '<a class="nav-tab nav-tab-active" data-type="%s">%s</a>', $type, esc_html( $label ) );
					} else {
						printf(
							'<a class="nav-tab" href="%s" data-type="%s">%s</a>',
							esc_url( add_query_arg( 'type', $type ) ),
							$type, esc_html( $label )
						);
					}
				} ?>
			</h2>
		<?php } ?>

		<div class="snippet-editor">

			<textarea id="snippet_code" name="snippet_code" rows="200" spellcheck="false" style="font-family: monospace; width: 100%;"><?php
				echo esc_textarea( $snippet->code );
				?></textarea>

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
