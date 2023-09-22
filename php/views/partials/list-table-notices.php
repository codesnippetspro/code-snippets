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

switch ( $result ) {
	case 'cloud-key-deleted':
		?>
		<div id="message" class="notice notice-info fade is-dismissible">
			<p><strong><?php esc_html_e( 'Cannot find a snippet with a cloud key.', 'code-snippets' ); ?></strong></p>
			<p>
				<a href="https://codesnippets.cloud/cloud-setup-guide" target="_blank">
					<?php esc_html_e( 'If this is your first time using Cloud, read our setup guide.', 'code-snippets' ); ?></a>
				<?php esc_html_e( 'Otherwise, please import the snippet and activate it to try again.', 'code-snippets' ); ?>
			</p>
		</div>
		<?php
		break;

	case 'cloud-key-no-codevault':
		?>
		<div id="message" class="notice notice-error fade is-dismissible">
			<p><strong><?php esc_html_e( 'There is no codevault set up on the cloud.', 'code-snippets' ); ?></strong></p>
			<p>
				<a href="https://codesnippets.cloud/user/profile#codevaultSection" target="_blank">
					<?php esc_html_e( 'Please log into your Code Snippet Cloud account and set up a codevault.', 'code-snippets' ); ?></a>
			</p>
		</div>
		<?php
		break;

	case 'cloud-key-invalid':
	case 'cloud-key-inactive':
	case 'cloud-key-expired':
		$notices = [
			'cloud-key-invalid'  => __( 'The snippet with the cloud key is invalid. Please re-download from the cloud and try again.', 'code-snippets' ),
			'cloud-key-inactive' => __( 'The snippet with the cloud key is deactivated. Please activate it and try again.', 'code-snippets' ),
			'cloud-key-expired'  => __( 'The cloud access snippet has expired. Please re-download from the cloud and try again.', 'code-snippets' ),
		];

		?>
		<div class="notice notice-error fade">
			<p><strong><?php echo esc_html( $notices[ $result ] ); ?></strong></p>
			<p>
				<?php
				// translators: %s: cloud setup URL.
				$text = __( 'See our <a href="%s" target="_blank">cloud setup guide</a> for more details.', 'code-snippets' );
				echo wp_kses_post( sprintf( $text, 'https://codesnippets.cloud/cloud-setup-guide' ) );
				?>
			</p>
		</div>
		<?php
		break;

	default:
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

		break;
}
