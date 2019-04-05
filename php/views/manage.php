<?php
/**
 * HTML code for the Manage Snippets page
 *
 * @package    Code_Snippets
 * @subpackage Views
 *
 * @var Manage_Menu $this
 */

namespace Code_Snippets;

/* Bail if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

?>

<div class="wrap">
	<h1><?php
		esc_html_e( 'Snippets', 'code-snippets' );

		$admin = code_snippets()->admin;
		$this->page_title_actions( $admin->is_compact_menu() ? [ 'add', 'import', 'settings' ] : [ 'add', 'import' ] );

		$this->list_table->search_notice();
		?></h1>

	<?php $this->print_messages(); ?>

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

		$current_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'all';
		$current_type = isset( $types[ $current_type ] ) ? $current_type : 'all';

		foreach ( $types as $type_name => $label ) {
			$badge = ( 'all' === $type_name ? '' : ' <span>' . esc_html( $type_name  ) . '</span>' );

			if ( $type_name === $current_type ) {
				printf( '<a class="nav-tab nav-tab-active" data-type="%s">%s</a>', esc_attr( $type_name ), esc_html( $label ) . $badge );
			} else {
				printf(
					'<a class="nav-tab" href="%s" data-type="%s">%s</a>',
					esc_url( add_query_arg( 'type', $type_name ) ),
					esc_attr( $type_name ),
					esc_html( $label ) . $badge
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
