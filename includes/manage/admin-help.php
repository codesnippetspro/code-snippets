<?php

/**
 * Register and handle the help tabs for the
 * manage snippets admin page
 *
 * @package Code_Snippets
 * @subpackage Manage
 */

$screen = get_current_screen();

$screen->add_help_tab( array(
	'id'      => 'overview',
	'title'   => __( 'Overview', 'code-snippets' ),
	'content' =>
		'<p>' . __( 'Snippets are similar to plugins - they both extend and expand the functionality of WordPress. Snippets are more light-weight, just a few lines of code, and do not put as much load on your server. Here you can manage your existing snippets and preform tasks on them such as activating, deactivating, deleting and exporting.', 'code-snippets' ) . '</p>'
) );

$screen->add_help_tab( array(
	'id'      => 'safe-mode',
	'title'   => __( 'Safe Mode', 'code-snippets' ),
	'content' =>
		'<p>' . __( 'Be sure to check your snippets for errors before you activate them, as a faulty snippet could bring your whole blog down. If your site starts doing strange things, deactivate all your snippets and activate them one at a time.', 'code-snippets' ) . '</p>' .
		'<p>' . __( "If something goes wrong with a snippet and you can't use WordPress, you can cause all snippets to stop executing by adding <code>define('CODE_SNIPPETS_SAFE_MODE', true);</code> to your <code>wp-config.php</code> file. After you have deactivated the offending snippet, you can turn off safe mode by removing this line or replacing <strong>true</strong> with <strong>false</strong>.", 'code-snippets' ) . '</p>'
) );

$screen->add_help_tab( array(
	'id'      => 'uninstall',
	'title'   => __( 'Uninstall', 'code-snippets' ),
	'content' =>
		'<p>' . sprintf( __( 'When you delete Code Snippets through the Plugins menu in WordPress it will clear up the <code>%1$s</code> table and a few other bits of data stored in the database. If you want to keep this data (ie: you are only temporally uninstalling Code Snippets) then remove the <code>%2$s</code> folder using FTP.', 'code-snippets' ), get_snippets_table_name(), dirname( CODE_SNIPPETS_FILE ) ) .
		'<p>' . __( "Even if you're sure that you don't want to use Code Snippets ever again on this WordPress installation, you may want to use the export feature to back up your snippets.", 'code-snippets' ) . '</p>'
) );

$screen->set_help_sidebar(
	'<p><strong>' . __( 'For more information:', 'code-snippets' ) . '</strong></p>' .
	'<p>' . __( '<a href="http://wordpress.org/plugins/code-snippets" target="_blank">WordPress Extend</a></p>', 'code-snippets' ) . '</p>' .
	'<p>' . __( '<a href="http://wordpress.org/support/plugin/code-snippets" target="_blank">Support Forums</a>', 'code-snippets' ) . '</p>' .
	'<p>' . __( '<a href="http://bungeshea.com/plugins/code-snippets/" target="_blank">Project Website</a>', 'code-snippets' ) .  '</p>'
);
