<?php
/**
 * HTML for displaying notices for the manage table.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;

/**
 * Loaded from the manage menu.
 *
 * @var Manage_Menu $this
 */

if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) {
	?>
	<div id="message" class="notice notice-error fade is-dismissible">
		<p>
			<?php echo wp_kses_post( __( '<strong>Warning:</strong> Safe mode is active and snippets will not execute! Remove the <code>CODE_SNIPPETS_SAFE_MODE</code> constant from <code>wp-config.php</code> to turn off safe mode.', 'code-snippets' ) ); ?>

			<a href="https://help.codesnippets.pro/article/12-safe-mode" target="_blank">
				<?php esc_html_e( 'Help', 'code-snippets' ); ?>
			</a>
		</p>
	</div>
	<?php
}

if ( empty( $_REQUEST['result'] ) ) {
	return;
}

$result = sanitize_key( $_REQUEST['result'] );

$result_messages = apply_filters(
	'code_snippets/manage/result_messages',
	[
		'executed'          => __( 'Snippet <strong>executed</strong>.', 'code-snippets' ),
		'activated'         => __( 'Snippet <strong>activated</strong>.', 'code-snippets' ),
		'activated-multi'   => __( 'Selected snippets <strong>activated</strong>.', 'code-snippets' ),
		'deactivated'       => __( 'Snippet <strong>deactivated</strong>.', 'code-snippets' ),
		'deactivated-multi' => __( 'Selected snippets <strong>deactivated</strong>.', 'code-snippets' ),
		'deleted'           => __( 'Snippet <strong>deleted</strong>.', 'code-snippets' ),
		'deleted-multi'     => __( 'Selected snippets <strong>deleted</strong>.', 'code-snippets' ),
		'cloned'            => __( 'Snippet <strong>cloned</strong>.', 'code-snippets' ),
		'cloned-multi'      => __( 'Selected snippets <strong>cloned</strong>.', 'code-snippets' ),
		'cloud-refreshed'   => __( 'Synced cloud data has been <strong>successfully</strong> refreshed.', 'code-snippets' ),
	]
);

if ( isset( $result_messages[ $result ] ) ) {
	printf(
		'<div id="message" class="notice notice-success fade is-dismissible"><p>%s</p></div>',
		wp_kses_post( $result_messages[ $result ] )
	);
}
