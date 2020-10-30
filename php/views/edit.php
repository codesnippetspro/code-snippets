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
				esc_url( code_snippets()->get_menu_url( 'add' ) ),
				esc_html_x( 'Add New', 'snippet', 'code-snippets' )
			);
		} else {
			esc_html_e( 'Add New Snippet', 'code-snippets' );
		}

		if ( code_snippets()->admin->is_compact_menu() ) {
			$this->page_title_actions( [ 'manage', 'import', 'settings' ] );
		}

		?></h1>

	<?php $this->print_messages(); ?>

	<form method="post" id="snippet-form" action="" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	      data-snippet-type="<?php echo esc_attr( $snippet->type ); ?>">
		<?php
		/* Output the hidden fields */

		if ( 0 !== $snippet->id ) {
			printf( '<input type="hidden" name="snippet_id" value="%d" />', esc_attr( $snippet->id ) );
		}

		printf( '<input type="hidden" name="snippet_active" value="%d" />', esc_attr( $snippet->active ) );

		printf( '<input type="hidden" name="current_snippet_scope" value="%s" />', esc_attr( $snippet->scope ) );

		?>
		<div id="titlediv">
			<div id="titlewrap">
				<label for="title" style="display: none;"><?php esc_html_e( 'Name', 'code-snippets' ); ?></label>
				<input id="title" type="text" autocomplete="off" name="snippet_name" value="<?php echo esc_attr( $snippet->name ); ?>"
				       placeholder="<?php esc_attr_e( 'Enter title here', 'code-snippets' ); ?>" />
			</div>
		</div>

		<h2>
			<label for="snippet_code">
				<?php esc_html_e( 'Code', 'code-snippets' );

				if ( $snippet->id ) {
					printf( ' <span class="snippet-type-badge" data-type="%s">%s</span>', esc_attr( $snippet->type ), esc_html( $snippet->type ) );
				}

				if ( apply_filters( 'code_snippets/extra_save_buttons', true ) ) {
					$this->render_view( 'partials/edit-submit-extra' );
				}

				?>
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

				foreach ( $types as $type_name => $label ) {

					if ( $snippet->type === $type_name ) {
						echo '<a class="nav-tab nav-tab-active"';
					} else {
						printf( '<a class="nav-tab" href="%s"', esc_url( add_query_arg( 'type', $type_name ) ) );
					}

					printf( ' data-type="%s">%s <span>%s</span></a>', esc_attr( $type_name ), esc_html( $label ), esc_html( $type_name ) );
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

		<p class="submit"><?php
			$this->render_submit_buttons( $snippet );

			if ( 'css' === $snippet->type || 'js' === $snippet->type ) {
				$asset_url = code_snippets()->active_snippets->get_asset_url( $snippet->scope );
				$asset_url = add_query_arg( [ 'TB_iframe' => true, 'width' => 600, 'height' => 550 ], $asset_url );

				$scope_names = [
					'site-css'       => __( 'Site front-end stylesheet', 'code-snippets' ),
					'admin-css'      => __( 'Administration area stylesheet', 'code-snippets' ),
					'site-head-js'   => __( 'JavaScript loaded in the site &amp;lt;head&amp;gt; section', 'code-snippets' ),
					'site-footer-js' => __( 'JavaScript loaded just before the closing &amp;lt;/body&amp;gt; tag', 'code-snippets' ),
				];

				printf(
					'<a href="%s" class="button button-secondary thickbox" name="%s">%s</a>',
					esc_url( $asset_url ),
					esc_attr( isset( $scope_names[ $snippet->scope ] ) ? $scope_names[ $snippet->scope ] : $snippet->scope ),
					'css' === $snippet->type ?
						esc_html__( 'View Full Stylesheet', 'code-snippets' ) :
						esc_html__( 'View Full Script', 'code-snippets' )
				);

				add_thickbox();
			}

			?></p>
	</form>
</div>
