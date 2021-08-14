<?php

namespace Code_Snippets;

$license = code_snippets()->licensing;

?>
<h2 id="license-settings" class="settings-section-title">
	<?php esc_html_e( 'License', 'code-snippets' ); ?>
</h2>
<table class="form-table settings-section license-settings">
	<?php
	if ( ! $license->key ) {

		$text = __( 'In order to use all of the features of Code Snippets Pro, you need a valid license key. If you do not have one, you can purchase a subscription at <a href="https://codesnippets.pro/buy-pro/" target="_blank">codesnippets.pro/buy-pro</a>.', 'code-snippets' );

		echo '<p>', wp_kses( $text, [ 'a' => [ 'href' => true, 'target' => true ] ] ), '</p>';

		?>
		<tbody>

		<tr>
			<th scope="row">
				<label for="code_snippets_license_key">
					<?php esc_html_e( 'License Key', 'code-snippets' ); ?>
				</label>
			</th>
			<td>
				<input type="text" name="code_snippets_license_key" id="code_snippets_license_key" class="regular-text">
			</td>
		</tr>
		</tbody>
		<tfoot>
		<tr>
			<td>&nbsp;</td>
			<td><?php
				submit_button(
					__( 'Activate License', 'code-snippets' ),
					'secondary', 'code_snippets_activate_license', false
				);
				?></td>
		</tr>
		</tfoot>

	<?php } else { ?>
		<tbody>
		<tr>
			<th scope="row"><?php esc_html_e( 'License Key', 'code-snippets' ); ?></th>
			<td><?php
				echo esc_html(
					substr( $license->key, 0, 4 ) .
					str_repeat( '*', strlen( $license->key ) - 8 ) .
					substr( $license->key, - 4 )
				);
				?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Product Name', 'code-snippets' ); ?></th>
			<td><?php echo esc_html( Licensing::EDD_ITEM_NAME ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Type', 'code-snippets' ); ?></th>
			<td><?php echo esc_html( sprintf(
					_n( '%d activation left', '%d activations left', $license->activations_left, 'code-snippets' ),
					$license->activations_left
				) ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Status', 'code-snippets' ); ?></th>
			<td><span class="license-status license-status-<?php echo esc_attr( $license->license ); ?>">
				<?php

				$statuses = [
					'valid'    => __( 'Active', 'code-snippets' ),
					'disabled' => __( 'Disabled', 'code-snippets' ),
					'expired'  => __( 'Expired', 'code-snippets' ),
				];

				echo esc_html( isset( $statuses[ $license->license ] ) ?
					$statuses[ $license->license ] :
					__( 'Invalid', 'code-snippets' ) );

				?></span>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Expiration Date', 'code-snippets' ); ?></th>
			<td>
				<time datetime="<?php echo esc_attr( $license->expires ); ?>"><?php
					echo date_i18n(
						get_option( 'date_format' ),
						strtotime( $license->expires, current_time( 'timestamp' )
						) ); ?></time>
			</td>
		</tr>
		</tbody>
		<tfoot>
		<tr>
			<td>
				<a href="https://codesnippets.pro/buy-pro" class="button button-secondary" target="_blank">
					<?php esc_html_e( 'Renew Now', 'code-snippets' ); ?></a>
			</td>
			<td><?php
				submit_button(
					__( 'Remove License', 'code-snippets' ),
					'secondary', 'code_snippets_remove_license', false
				);
				?></td>
		</tr>
		</tfoot>
	<?php } ?>
</table>
