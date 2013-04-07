<?php

/**
 * HTML code for the Manage Snippets page
 *
 * @package    Code Snippets
 * @subpackage Administration
 */

if ( ! class_exists( 'Code_Snippets' ) ) exit;

global $code_snippets;
$screen = get_current_screen();
?>
<?php if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) : ?>
	<div class="error"><p><strong>Warning:</strong> Safe mode is active and snippets will not execute! Remove the <code>CODE_SNIPPETS_SAFE_MODE</code> constant from <code>wp-config.php</code> to turn off safe mode. <a href="http://code-snippets.bungeshea.com/docs/safe-mode/" target="_blank">Help</a></p></div>
<?php endif; ?>

<?php if ( isset($_GET['activate']) ) : ?>
	<div id="message" class="updated"><p><?php _e('Snippet <strong>activated</strong>.', 'code-snippets') ?></p></div>
<?php elseif (isset($_GET['activate-multi'])) : ?>
	<div id="message" class="updated"><p><?php _e('Selected snippets <strong>activated</strong>.', 'code-snippets'); ?></p></div>
<?php elseif ( isset($_GET['deactivate']) ) : ?>
	<div id="message" class="updated"><p><?php _e('Snippet <strong>deactivated</strong>.', 'code-snippets') ?></p></div>
<?php elseif (isset($_GET['deactivate-multi'])) : ?>
	<div id="message" class="updated"><p><?php _e('Selected snippets <strong>deactivated</strong>.', 'code-snippets'); ?></p></div>
<?php elseif ( isset($_GET['delete']) ) : ?>
	<div id="message" class="updated"><p><?php _e('Snippet <strong>deleted</strong>.', 'code-snippets') ?></p></div>
<?php elseif (isset($_GET['delete-multi'])) : ?>
	<div id="message" class="updated"><p><?php _e('Selected snippets <strong>deleted</strong>.', 'code-snippets'); ?></p></div>
<?php endif; ?>

<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php esc_html_e('Snippets', 'code-snippets'); ?>
	<?php if ( $code_snippets->user_can( 'install' ) ) { ?>
	<a href="<?php echo $code_snippets->admin->single_url; ?>" class="add-new-h2"><?php echo esc_html_x('Add New', 'snippet', 'code-snippets'); ?></a>
<?php }
	$code_snippets->list_table->search_notice(); ?></h2>

	<?php $code_snippets->list_table->views(); ?>

	<form method="get" action="">
		<?php
			$code_snippets->list_table->required_form_fields( 'search_box' );
			$code_snippets->list_table->search_box( __( 'Search Installed Snippets', 'code-snippets' ), 'search_id' );
		?>
	</form>
	<form method="post" action="">
		<?php $code_snippets->list_table->required_form_fields(); ?>
		<?php $code_snippets->list_table->display(); ?>
	</form>

	<?php do_action( 'code_snippets_admin_manage' ); ?>

</div>
