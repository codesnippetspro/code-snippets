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

use wpdb;

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

$licensed = code_snippets()->licensing->is_licensed();

?>
<div class="wrap<?php echo $this->read_only ? ' read-only-snippet' : ''; ?>">
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

		printf( '<input type="hidden" name="snippet_cloud_id" value="%s">', esc_attr( $snippet->cloud_id ) );

		do_action( 'code_snippets/admin/before_title_input', $snippet );
		?>

		<div id="titlediv">
			<div id="titlewrap">
				<label for="title" style="display: none;"><?php esc_html_e( 'Name', 'code-snippets' ); ?></label>
				<input id="title" type="text" autocomplete="off"
				       name="snippet_name"<?php disabled( $this->read_only ); ?>
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

		if ( ! $snippet->id && ! isset( $_REQUEST['preview'] ) ) {
			echo '<h2 class="nav-tab-wrapper" id="snippet-type-tabs">';

			$snippet_types = Plugin::get_types();
			unset( $snippet_types['cloud'] );

			foreach ( $snippet_types as $type_name => $label ) {
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

		<div class="below-snippet-editor">
			<?php do_action( 'code_snippets_below_editor', $snippet ); ?>
		</div>

		

		<?php
		//Updated Cloud Snippet Check and Hidden Input Injection
		$cloud = false;
		$cloud_update = false;
		if ( isset( $snippet->cloud_id ) ) {
			$cloud = true;
			//If so check if update available using instance of plugin
			$cloud_api = code_snippets()->cloud_api;
			if( $cloud_api->is_update_available( $snippet->id ) ) {
				$cloud_id_owner =  $cloud_api->get_cloud_id_and_ownership( $snippet->cloud_id );
				if( $cloud_id_owner['is_owner'] ) {
					$cloud_update = true;
					$updated_snippet = $cloud_api->get_single_cloud_snippet( $cloud_id_owner['cloud_id'] );
					$updated_code = $updated_snippet->code;
					echo '<input type="hidden" id="updated_snippet_code" name="updated_snippet_code" value="' . $updated_code . '">					
						<div id="updated-code">
							<h2><label>Snippet Update from the Cloud*</label></h2>
							<p>There is an update to this snippet from the cloud. The original snippet is shown on the <b>Left Hand Side</b> and updated code on the <b>Right Hand Side</b> with the differences highlighted.</p> 
							<p><b>Important:</b> Please review updated code below as updating this snippet will overwrite the code with the below.</p>
						</div>';
				}
	
			}
		}
		/* Allow plugins to add fields and content to this page */
		do_action( 'code_snippets_edit_snippet', $snippet );

		/* Add a nonce for security */
		wp_nonce_field( 'save_snippet' );

		?>

		<p class="submit">
			<?php
			$this->render_submit_buttons( $snippet, '', true, $cloud_update );
			if ( $licensed && ( 'css' === $snippet->type || 'js' === $snippet->type ) ) {
				$asset_url = add_query_arg(
					array(
						'TB_iframe' => true,
						'width'     => 600,
						'height'    => 550,
					),
					code_snippets()->active_snippets->get_asset_url( $snippet->scope )
				);

				$scope_names = [
					'site-css'       => __( 'Site front-end stylesheet', 'code-snippets' ),
					'admin-css'      => __( 'Administration area stylesheet', 'code-snippets' ),
					'site-head-js'   => __( 'JavaScript loaded in the site &amp;lt;head&amp;gt; section', 'code-snippets' ),
					'site-footer-js' => __( 'JavaScript loaded just before the closing &amp;lt;/body&amp;gt; tag', 'code-snippets' ),
				];

				printf(
					'<a href="%s" class="button button-secondary thickbox" name="%s">%s</a>',
					esc_url( $asset_url ),
					isset( $scope_names[ $snippet->scope ] ) ? esc_attr( $scope_names[ $snippet->scope ] ) : esc_attr( $snippet->scope ),
					esc_html(
						'css' === $snippet->type ?
							__( 'View Full Stylesheet', 'code-snippets' ) :
							__( 'View Full Script', 'code-snippets' )
					)
				);

				add_thickbox();
			}

			?>
		</p>
	</form>
</div>
