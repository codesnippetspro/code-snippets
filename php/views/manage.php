<?php

namespace Code_Snippets;

/**
 * HTML code for the Manage Snippets page
 *
 * @package    Code_Snippets
 * @subpackage Views
 *
 * @var Manage_Menu $this
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

		$admin = code_snippets()->admin;

		if ( $admin->is_compact_menu() && isset( $admin->menus['settings'] ) ) {
			printf( '<a href="%2$s" class="page-title-action">%1$s</a>',
				esc_html_x( 'Settings', 'snippets', 'code-snippets' ),
				code_snippets()->get_menu_url( 'settings' )
			);
		}

		$this->list_table->search_notice();
		?></h1>

	<form method="get" action="">
		<?php
		$this->list_table->required_form_fields( 'search_box' );
		$this->list_table->search_box( __( 'Search Snippets', 'code-snippets' ), 'search_id' );
		?>
	</form>

	<h2 class="nav-tab-wrapper" id="snippet-type-tabs">
		<?php

		$types = array(
			'all'  => __( 'All Snippets', 'code-snippets' ),
			'php'  => __( 'Functions', 'code-snippets' ),
			'html' => __( 'Content', 'code-snippets' ),
			'css'  => __( 'Styles', 'code-snippets' ),
			'js'   => __( 'Scripts', 'code-snippets' ),
		);

		$current_type = isset( $_GET['type'], $types[ $_GET['type'] ] ) ? $_GET['type'] : 'all';

		foreach ( $types as $type => $label ) {
			if ( $type == $current_type ) {
				printf( '<a class="nav-tab nav-tab-active" data-type="%s">%s</a>', $type, esc_html( $label ) );
			} else {
				printf(
					'<a class="nav-tab" href="%s" data-type="%s">%s</a>',
					esc_url( add_query_arg( 'type', $type ) ),
					$type, esc_html( $label )
				);
			}
		}

		?>
	</h2>

	<?php $this->list_table->views(); ?>

	<form method="post" action="">
		<input type="hidden" id="code_snippets_ajax_nonce" value="<?= esc_attr( wp_create_nonce( 'code_snippets_manage' ) ); ?>">

		<?php
		$this->list_table->required_form_fields();
		$this->list_table->display();
		?>
	</form>

	<?php do_action( 'code_snippets/admin/manage' ); ?>
</div>
