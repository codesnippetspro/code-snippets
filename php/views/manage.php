<?php

/**
 * HTML code for the Manage Snippets page
 *
 * @package Code_Snippets
 * @subpackage Views
 *
 * @var Code_Snippets_Manage_Menu $this
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
			code_snippets()->get_menu_url( 'add' )
		);

		printf( '<a href="%2$s" class="page-title-action">%1$s</a>',
			esc_html_x( 'Import', 'snippets', 'code-snippets' ),
			code_snippets()->get_menu_url( 'import' )
		);

		if ( code_snippets()->admin->is_compact_menu() && isset( $admin->menus['settings'] ) ) {
			printf( '<a href="%2$s" class="page-title-action">%1$s</a>',
				esc_html_x( 'Settings', 'snippets', 'code-snippets' ),
				code_snippets()->get_menu_url( 'settings' )
			);
		}

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
		<input type="hidden" id="code_snippets_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'code_snippets_manage' ) ); ?>">

		<?php
		$this->list_table->required_form_fields();
		$this->list_table->display();
		?>
	</form>

	<?php do_action( 'code_snippets/admin/manage' ); ?>
</div>
