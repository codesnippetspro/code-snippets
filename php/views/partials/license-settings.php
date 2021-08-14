<?php

namespace Code_Snippets;

$license = code_snippets()->licensing;
$license->update_status();

$tiers = [
	__( 'Personal', 'code-snippets' ),
	__( 'Freelancer', 'code-snippets' ),
	__( 'Agency', 'code-snippets' ),
	__( 'Founder', 'code-snippets' ),
	__( 'Founder Personal Plus', 'code-snippets' ),
	__( 'Pillar', 'code-snippets' ),
];

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
			<th scope="row"><?php esc_html_e( 'License Owner', 'code-snippets' ); ?></th>
			<td><?php
				/* translators: 1: customer name, 2: customer email */
				echo esc_html( sprintf( __( '%s <%s>', 'code-snippets' ),
					$license->customer_name, $license->customer_email ) ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Type', 'code-snippets' ); ?></th>
			<td><?php
				$tier = $tiers[ isset( $tiers[ $license->price_id - 1 ] ) ? $license->price_id - 1 : 0 ];

				if ( ! is_numeric( $license->activations_left ) ) {
					/* translators: 1: tier name, 2: remaining activations */
					$text = __( '%1$s tier; %2$s activations left', 'code-snippets' );
				} else {
					/* translators: 1: tier name, 2: remaining activations */
					$text = _n( '%1$s tier; %2$s activation left', '%1$s tier, %2$s activations left',
						$license->activations_left );
				}

				echo esc_html( sprintf( $text, $tier, $license->activations_left ) ); ?></td>
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
			<td><?php
				if ( 'lifetime' === $license->expires ) {
					esc_html_e( 'Lifetime', 'code-snippets' );
				} else {
					echo date_i18n(
						get_option( 'date_format' ),
						strtotime( $license->expires, current_time( 'timestamp' )
						) );
				} ?>
			</td>
		</tr>
		</tbody>
		<tfoot>
		<tr>
			<td>
				<a href="<?php
				echo esc_url( add_query_arg( [
					'edd_license_key' => $license->key,
					'download_id'     => Licensing::EDD_ITEM_ID,
				], 'https://codesnippets.pro/checkout/' ) );
				?>" class="button button-secondary" target="_blank">
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
