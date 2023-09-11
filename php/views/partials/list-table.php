<?php
/**
 * HTML for the all snippets and codevault list table
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

?>

<form method="get" action="">
	<?php
	List_Table::required_form_fields( 'search_box' );
	$this->list_table->search_box( __( 'Search Snippets', 'code-snippets' ), 'search_id' );
	?>
</form>

<form method="post" action="">
	<input type="hidden" id="code_snippets_ajax_nonce"
	       value="<?php echo esc_attr( wp_create_nonce( 'code_snippets_manage_ajax' ) ); ?>">
	<?php
	List_Table::required_form_fields();
	$this->list_table->display();
	?>
</form>
<div class="cloud-key">
	<p><strong><u><?php esc_html_e( 'Cloud Sync Guide', 'code-snippets' ); ?></u></strong></p>
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
