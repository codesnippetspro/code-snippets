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
