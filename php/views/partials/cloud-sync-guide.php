<?php
/**
 * HTML for the cloud sync guide.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

?>
<div class="tooltip-box">
	<span class="dashicons dashicons-editor-help"></span>
	<div class="tooltip-text cloud-key">
		<p class="tooltip-text-title">
			<strong><u><?php esc_html_e( 'Cloud Sync Guide', 'code-snippets' ); ?></u></strong>
		</p>
		<p>
			<span class="dashicons dashicons-cloud cloud-icon cloud-downloaded"></span>
			<?php esc_html_e( 'Snippet downloaded from cloud but not synced with codevault.', 'code-snippets' ); ?>
		</p>
		<p>
			<span class="dashicons dashicons-cloud cloud-icon cloud-synced-legend "></span>
			<?php
			esc_html_e( 'Snippet downloaded and in sync with codevault.', 'code-snippets' );
			$this->print_pro_message();
			?>
		</p>
		<p><span class="dashicons dashicons-cloud cloud-icon cloud-not-downloaded"></span>
			<?php
			esc_html_e( 'Snippet in codevault but not downloaded to local site.', 'code-snippets' );
			$this->print_pro_message();
			?>
		</p>
		<p>
			<span class="dashicons dashicons-cloud cloud-icon cloud-update"></span>
			<?php
			esc_html_e( 'Snippet update available.', 'code-snippets' );
			$this->print_pro_message();
			?>
		</p>
	</div>
</div>
