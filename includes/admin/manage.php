<?php

/**
 * HTML code for the Manage Snippets page
 *
 * @package    Code Snippets
 * @subpackage Administration
 */

if ( ! class_exists( 'Code_Snippets' ) ) exit;

require_once $this->plugin_dir . 'includes/class-list-table.php';

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
	<h2><?php _e('Snippets', 'code-snippets'); ?>
	<?php if ( current_user_can( $screen->is_network ? 'install_network_snippets' : 'install_snippets' ) ) { ?>
	<a href="<?php echo $this->admin_single_url; ?>" class="add-new-h2"><?php echo esc_html_x('Add New', 'snippet', 'code-snippets'); ?></a>
<?php }
if ( isset( $s ) && $s )
	printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;', 'code-snippets') . '</span>', esc_html( $s ) ); ?></h2>

	<?php $this->list_table->views(); ?>

	<form method="get" action="">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<?php $this->list_table->search_box( __( 'Search Installed Snippets', 'code-snippets' ), 'search_id' ); ?>
	</form>
	<form method="post" action="">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<?php $this->list_table->display(); ?>
	</form>

	<?php do_action( 'code_snippets_admin_manage' ); ?>

</div>