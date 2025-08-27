<?php
/**
 * HTML for the cloud sync guide.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

use function Code_Snippets\code_snippets;

$icon_descriptions = [
	'downloaded'     => __( 'Snippet downloaded from cloud but not synced with codevault.', 'code-snippets' ),
	'synced'         => __( 'Snippet downloaded and in sync with codevault.', 'code-snippets' ),
	'not-downloaded' => __( 'Snippet in codevault but not downloaded to local site.', 'code-snippets' ),
	'update'         => __( 'Snippet update available.', 'code-snippets' ),
];

$is_licensed = code_snippets()->licensing->is_licensed();

$pro_icons = [ 'synced', 'not-downloaded', 'update' ];

?>
<div class="tooltip tooltip-block tooltip-end cloud-legend-tooltip">
	<span class="dashicons dashicons-editor-help"></span>
	<div class="tooltip-content">
		<h3><?php esc_html_e( 'Cloud Sync Guide', 'code-snippets' ); ?></h3>
		<table>
			<tbody>
				<?php foreach ( $icon_descriptions as $icon => $description ) { ?>
					<tr>
						<td><span class="dashicons dashicons-cloud cloud-<?php echo esc_attr( $icon ); ?>"></span></td>
						<td>
							<?php

							echo esc_html( $description );

							if ( ! $is_licensed && in_array( $icon, $pro_icons, true ) ) {
								printf(
									'<span class="badge pro-badge small-badge">%s</span>',
									esc_html__( 'Pro', 'code-snippets' )
								);
							}

							?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>

	</div>
</div>
