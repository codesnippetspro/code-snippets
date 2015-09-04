<?php

/**
 * HTML code for the Manage Snippets page
 *
 * @package Code_Snippets
 * @subpackage Views
 */

/* Bail if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

?>

<div class="wrap">
	<h1><?php
	esc_html_e( 'Snippets', 'code-snippets' );

	printf( '<a href="%2$s" class="page-title-action add-new-h2">%1$s</a>',
		esc_html_x( 'Add New', 'snippet', 'code-snippets' ),
		code_snippets_get_menu_url( 'add' )
	);

	$this->list_table->search_notice();
	?></h1>

	<?php $this->list_table->views(); ?>

	<form method="get" action="">
		<?php
			$this->list_table->required_form_fields( 'search_box' );
			$this->list_table->search_box( __( 'Search Installed Snippets', 'code-snippets' ), 'search_id' );
		?>
	</form>
	<form method="post" action="">
		<?php
			$this->list_table->required_form_fields();
			$this->list_table->display();
		?>
	</form>

	<?php do_action( 'code_snippets/admin/manage' ); ?>
</div>
