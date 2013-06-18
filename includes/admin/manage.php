<?php

/**
 * HTML code for the Manage Snippets page
 *
 * @package    Code_Snippets
 * @subpackage Administration
 */

if ( ! class_exists( 'Code_Snippets' ) )
	exit;

global $code_snippets;
$screen = get_current_screen();
$code_snippets->admin->get_messages( 'manage' );
?>

<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php
		esc_html_e('Snippets', 'code-snippets');

		if ( $code_snippets->user_can( 'install' ) ) {

			printf ( '<a href="%2$s" class="add-new-h2">%1$s</a>',
				$code_snippets->admin->single_url,
				esc_html_x('Add New', 'snippet', 'code-snippets');
			);
		}

		$code_snippets->list_table->search_notice();
	?></h2>

	<?php $code_snippets->list_table->views(); ?>

	<form method="get" action="">
		<?php
			$code_snippets->list_table->required_form_fields( 'search_box' );
			$code_snippets->list_table->search_box( __( 'Search Installed Snippets', 'code-snippets' ), 'search_id' );
		?>
	</form>
	<form method="post" action="">
		<?php
			$code_snippets->list_table->required_form_fields();
			$code_snippets->list_table->display();
		?>
	</form>

	<?php do_action( 'code_snippets_admin_manage' ); ?>

</div>
