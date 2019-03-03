<?php

/**
 * HTML code for the Add New/Edit Snippet page
 *
 * @package Code_Snippets
 * @subpackage Views
 *
 * @var Code_Snippets_Edit_Menu $this
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
} else {
	$classes[] = ( $snippet->active ? '' : 'in' ) . 'active-snippet';
}

?>
<div class="wrap">
	<h1><?php

		if ( $snippet->id ) {
			esc_html_e( 'Edit Snippet', 'code-snippets' );
			printf( ' <a href="%1$s" class="page-title-action add-new-h2">%2$s</a>',
				code_snippets()->get_menu_url( 'add' ),
				esc_html_x( 'Add New', 'snippet', 'code-snippets' )
			);
		} else {
			esc_html_e( 'Add New Snippet', 'code-snippets' );
		}

		$admin = code_snippets()->admin;

		if ( code_snippets()->admin->is_compact_menu() ) {

			printf( '<a href="%2$s" class="page-title-action">%1$s</a>',
				esc_html_x( 'Manage', 'snippets', 'code-snippets' ),
				code_snippets()->get_menu_url()
			);

			printf( '<a href="%2$s" class="page-title-action">%1$s</a>',
				esc_html_x( 'Import', 'snippets', 'code-snippets' ),
				code_snippets()->get_menu_url( 'import' )
			);

			if ( isset( $admin->menus['settings'] ) ) {

				printf( '<a href="%2$s" class="page-title-action">%1$s</a>',
					esc_html_x( 'Settings', 'snippets', 'code-snippets' ),
					code_snippets()->get_menu_url( 'settings' )
				);
			}
		}

		?></h1>

	<form method="post" id="snippet-form" action="" style="margin-top: 10px;" class="<?php echo implode( ' ', $classes ); ?>">
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

		<?php if ( apply_filters( 'code_snippets/extra_save_buttons', true ) ) { ?>
			<p class="submit-inline">
				<?php

				$actions['save_snippet'] = array(
					__( 'Save Changes', 'code-snippets' ),
					__( 'Save Snippet', 'code-snippets' )
				);

				if ( 'single-use' === $snippet->scope ) {
					$actions['save_snippet_execute'] = array(
						__( 'Execute Once', 'code-snippets' ),
						__( 'Save Snippet and Execute Once', 'code-snippets' )
					);

				} elseif ( ! $snippet->shared_network || ! is_network_admin() ) {

					if ( $snippet->active ) {
						$actions['save_snippet_deactivate'] = array(
							__( 'Deactivate', 'code-snippets' ),
							__( 'Save Snippet and Deactivate', 'code-snippets' )
						);

					} else {
						$actions['save_snippet_activate'] = array(
							__( 'Activate', 'code-snippets' ),
							__( 'Save Snippet and Activate', 'code-snippets' )
						);
					}
				}

				foreach ( $actions as $action => $labels ) {
					submit_button( $labels[0], 'secondary small', $action, false, array( 'title' => $labels[1] ) );
				}

				?>
			</p>
		<?php } ?>

		<h2>
			<label for="snippet_code">
				<?php _e( 'Code', 'code-snippets' ); ?>
			</label>
		</h2>

		<div class="snippet-editor">
			<textarea id="snippet_code" name="snippet_code" rows="200" spellcheck="false" style="font-family: monospace; width: 100%;"><?php
				echo esc_textarea( $snippet->code );
				?></textarea>

			<div class="snippet-editor-help">

				<div class="editor-help-tooltip cm-s-<?php
				echo esc_attr( code_snippets_get_setting( 'editor', 'theme' ) ); ?>"><?php
					echo esc_html_x( '?', 'help tooltip', 'code-snippets' ); ?></div>

				<?php

				$keys = array(
					'Cmd'    => esc_html_x( 'Cmd', 'keyboard key', 'code-snippets' ),
					'Ctrl'   => esc_html_x( 'Ctrl', 'keyboard key', 'code-snippets' ),
					'Shift'  => esc_html_x( 'Shift', 'keyboard key', 'code-snippets' ),
					'Option' => esc_html_x( 'Option', 'keyboard key', 'code-snippets' ),
					'Alt'    => esc_html_x( 'Alt', 'keyboard key', 'code-snippets' ),
					'F'      => esc_html_x( 'F', 'keyboard key', 'code-snippets' ),
					'G'      => esc_html_x( 'G', 'keyboard key', 'code-snippets' ),
					'R'      => esc_html_x( 'R', 'keyboard key', 'code-snippets' ),
					'S'      => esc_html_x( 'S', 'keyboard key', 'code-snippets' ),
				);

				?>

				<div class="editor-help-text">
					<table>
						<tr>
							<td><?php esc_html_e( 'Save changes', 'code-snippets' ); ?></td>
							<td>
								<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php
									echo $keys['Cmd']; ?></kbd>&hyphen;<kbd><?php echo $keys['S']; ?></kbd>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Begin searching', 'code-snippets' ); ?></td>
							<td>
								<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php
									echo $keys['Cmd']; ?></kbd>&hyphen;<kbd><?php echo $keys['F']; ?></kbd>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Find next', 'code-snippets' ); ?></td>
							<td>
								<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php echo $keys['Cmd']; ?></kbd>&hyphen;<kbd><?php echo $keys['G']; ?></kbd>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Find previous', 'code-snippets' ); ?></td>
							<td>
								<kbd><?php echo $keys['Shift']; ?></kbd>-<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php echo $keys['Cmd']; ?></kbd>&hyphen;<kbd><?php echo $keys['G']; ?></kbd>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Replace', 'code-snippets' ); ?></td>
							<td>
								<kbd><?php echo $keys['Shift']; ?></kbd>&hyphen;<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php echo $keys['Cmd']; ?></kbd>&hyphen;<kbd><?php echo $keys['F']; ?></kbd>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Replace all', 'code-snippets' ); ?></td>
							<td>
								<kbd><?php echo $keys['Shift']; ?></kbd>&hyphen;<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php echo $keys['Cmd']; ?></kbd><span class="mac-key">&hyphen;</span><kbd class="mac-key"><?php echo $keys['Option']; ?></kbd>&hyphen;<kbd><?php echo $keys['R']; ?></kbd>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Persistent search', 'code-snippets' ); ?></td>
							<td>
								<kbd><?php echo $keys['Alt']; ?></kbd>&hyphen;<kbd><?php echo $keys['F']; ?></kbd>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<?php
		/* Allow plugins to add fields and content to this page */
		do_action( 'code_snippets/admin/single', $snippet );

		/* Add a nonce for security */
		wp_nonce_field( 'save_snippet' );

		?>

		<p class="submit"><?php $this->render_submit_buttons( $snippet ); ?></p>
	</form>
</div>
